<?php
/**
 * Automatisch device registreren na login
 * Wordt aangeroepen na succesvolle login om het device automatisch toe te voegen
 */
require __DIR__ . '/../config.php';
$user = require_user($conn);

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$client_ip = $_SERVER['REMOTE_ADDR'] ?? '';
$device_fingerprint_from_client = trim((string)($body['device_fingerprint'] ?? ''));

// Detect device type from user agent
$device_type = "Device";
if (stripos($user_agent, 'iPhone') !== false) {
  $device_type = 'iPhone';
} elseif (stripos($user_agent, 'iPad') !== false) {
  $device_type = 'iPad';
} elseif (stripos($user_agent, 'Android') !== false) {
  $device_type = 'Android Device';
} elseif (stripos($user_agent, 'Windows') !== false) {
  $device_type = 'Windows PC';
} elseif (stripos($user_agent, 'Mac') !== false || stripos($user_agent, 'macOS') !== false) {
  $device_type = 'Mac';
} elseif (stripos($user_agent, 'Linux') !== false) {
  $device_type = 'Linux PC';
}

// FIRST: Check if user already has devices - prevent duplicate registration
// Strategy: Check if user has only 1 device of this type, or if device was created recently
$stmt = $conn->prepare("
  SELECT id, name, status, wg_public_key, wg_ip, created_at
  FROM devices 
  WHERE user_id = ? 
  ORDER BY created_at DESC
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$all_devices = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Filter devices by type
$devices_of_same_type = array_filter($all_devices, function($d) use ($device_type) {
  return stripos($d['name'], $device_type) === 0;
});

// If user has only 1 device of this type, it's likely the same device - don't create duplicate
if (count($devices_of_same_type) === 1) {
  $existing_device = reset($devices_of_same_type);
  $days_ago = (int)((time() - strtotime($existing_device['created_at'])) / 86400);
  
  // If it's the only device of this type, assume it's the same device
  json_out([
    'status' => 'exists',
    'device_id' => (int)$existing_device['id'],
    'device_name' => $existing_device['name'],
    'wg_public_key' => $existing_device['wg_public_key'],
    'wg_ip' => $existing_device['wg_ip'],
    'status' => $existing_device['status'],
    'message' => "Device '{$existing_device['name']}' herkend - gebruikt bestaand device",
    'skip' => true
  ], 200);
}

// If user has multiple devices of same type, check if any was created recently (within 1 day)
// This handles the case where user logs in again from the same device shortly after registration
if (count($devices_of_same_type) > 1) {
  foreach ($devices_of_same_type as $device) {
    $days_ago = (int)((time() - strtotime($device['created_at'])) / 86400);
    // If device was created within last 1 day, assume it's the same device
    if ($days_ago <= 1) {
      json_out([
        'status' => 'exists',
        'device_id' => (int)$device['id'],
        'device_name' => $device['name'],
        'wg_public_key' => $device['wg_public_key'],
        'wg_ip' => $device['wg_ip'],
        'status' => $device['status'],
        'message' => "Device '{$device['name']}' herkend - gebruikt bestaand device",
        'skip' => true
      ], 200);
    }
  }
}

// Check if user already has devices
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM devices WHERE user_id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$device_count = (int)$stmt->get_result()->fetch_assoc()['count'];

// Generate device name with number
$device_name = $device_type . ' ' . ($device_count + 1);

// Check subscription
try {
  $stmt = $conn->prepare("
    SELECT s.*, p.max_devices
    FROM subscriptions s
    LEFT JOIN subscription_plans p ON p.name = s.plan
    WHERE s.user_id = ? 
      AND s.status = 'active' 
      AND s.start_date <= CURDATE()
      AND s.end_date >= CURDATE()
    ORDER BY s.created_at DESC
    LIMIT 1
  ");
  if (!$stmt) {
    // If subscription_plans table doesn't exist, allow device creation without subscription
    $subscription = null;
  } else {
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $subscription = $stmt->get_result()->fetch_assoc();
  }
} catch (Exception $e) {
  // If subscription_plans table doesn't exist, allow device creation without subscription
  $subscription = null;
}

// If no subscription, still allow device creation (admin can add subscription later)
// This allows users to register devices even without subscription
$max_devices = 999; // Unlimited if no subscription
if ($subscription) {
  $max_devices = (int)$subscription['max_devices'];
}

if ($device_count >= $max_devices) {
  $plan_name = $subscription ? $subscription['plan'] : 'geen abonnement';
  // Return existing device if limit reached
  if (count($all_devices) > 0) {
    $existing_device = $all_devices[0];
    json_out([
      'status' => 'exists',
      'device_id' => (int)$existing_device['id'],
      'device_name' => $existing_device['name'],
      'wg_public_key' => $existing_device['wg_public_key'],
      'wg_ip' => $existing_device['wg_ip'],
      'status' => $existing_device['status'],
      'message' => "Device limiet bereikt. Gebruikt bestaand device '{$existing_device['name']}'.",
      'device_count' => $device_count,
      'max_devices' => $max_devices,
      'plan' => $subscription['plan'] ?? null,
      'skip' => true
    ], 200);
  } else {
    json_out([
      'message' => "Device limiet bereikt. Je plan ({$plan_name}) staat {$max_devices} device(s) toe. Upgrade je plan om meer devices toe te voegen.",
      'device_count' => $device_count,
      'max_devices' => $max_devices,
      'plan' => $subscription['plan'] ?? null,
      'skip' => true
    ], 200);
  }
}

// Generate automatic WireGuard key and IP first (we need these to check for duplicates)
$wg_public_key = base64_encode(random_bytes(32));

// Find next available IP
$stmt = $conn->prepare("SELECT wg_ip FROM devices WHERE wg_ip LIKE '10.10.0.%' ORDER BY wg_ip DESC LIMIT 1");
$stmt->execute();
$last_ip = $stmt->get_result()->fetch_assoc();
$ip_num = 10;

if ($last_ip) {
  $parts = explode('.', $last_ip['wg_ip']);
  $ip_num = (int)$parts[3] + 1;
  if ($ip_num > 254) $ip_num = 10;
}

$wg_ip = "10.10.0.{$ip_num}";

// STRICT: Prevent duplicate devices - check multiple criteria
// Check 1: By WireGuard key (GLOBAL - must be unique)
$stmt = $conn->prepare("SELECT id, name, status, wg_public_key, wg_ip, user_id FROM devices WHERE wg_public_key = ? LIMIT 1");
$stmt->bind_param("s", $wg_public_key);
$stmt->execute();
$existing_by_key = $stmt->get_result()->fetch_assoc();

if ($existing_by_key) {
  if ((int)$existing_by_key['user_id'] === (int)$user['id']) {
    json_out([
      'status' => 'exists',
      'message' => "Device bestaat al. Je kunt dit device gebruiken zonder het opnieuw toe te voegen.",
      'device_id' => (int)$existing_by_key['id'],
      'device_name' => $existing_by_key['name'],
      'status' => $existing_by_key['status'],
      'skip' => true,
      'wg_public_key' => $existing_by_key['wg_public_key'],
      'wg_ip' => $existing_by_key['wg_ip']
    ], 200);
  } else {
    // Key exists for another user - generate new key
    $wg_public_key = base64_encode(random_bytes(32));
  }
}

// Check 2: By IP address (GLOBAL - must be unique)
$stmt = $conn->prepare("SELECT id, name, status, wg_public_key, wg_ip, user_id FROM devices WHERE wg_ip = ? LIMIT 1");
$stmt->bind_param("s", $wg_ip);
$stmt->execute();
$existing_by_ip = $stmt->get_result()->fetch_assoc();

if ($existing_by_ip) {
  if ((int)$existing_by_ip['user_id'] === (int)$user['id']) {
    json_out([
      'status' => 'exists',
      'message' => "Device bestaat al. Je kunt dit device gebruiken zonder het opnieuw toe te voegen.",
      'device_id' => (int)$existing_by_ip['id'],
      'device_name' => $existing_by_ip['name'],
      'status' => $existing_by_ip['status'],
      'skip' => true,
      'wg_public_key' => $existing_by_ip['wg_public_key'],
      'wg_ip' => $existing_by_ip['wg_ip']
    ], 200);
  } else {
    // IP exists for another user - find next available IP
    $stmt = $conn->prepare("SELECT wg_ip FROM devices WHERE wg_ip LIKE '10.10.0.%' ORDER BY wg_ip DESC LIMIT 1");
    $stmt->execute();
    $last_ip = $stmt->get_result()->fetch_assoc();
    $ip_num = 10;
    if ($last_ip) {
      $parts = explode('.', $last_ip['wg_ip']);
      $ip_num = (int)$parts[3] + 1;
      if ($ip_num > 254) $ip_num = 10;
    }
    $wg_ip = "10.10.0.{$ip_num}";
  }
}

// Check 3: By device name for same user (prevent same device name for same user created recently)
if (!empty($device_name)) {
  $stmt = $conn->prepare("
    SELECT id, name, status, wg_public_key, wg_ip, created_at
    FROM devices 
    WHERE user_id = ? 
      AND name = ?
      AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ORDER BY created_at DESC
    LIMIT 1
  ");
  $stmt->bind_param("is", $user['id'], $device_name);
  $stmt->execute();
  $recent_device = $stmt->get_result()->fetch_assoc();
  
  if ($recent_device) {
    json_out([
      'status' => 'exists',
      'message' => "Device '{$device_name}' bestaat al. Je kunt dit device gebruiken zonder het opnieuw toe te voegen.",
      'device_id' => (int)$recent_device['id'],
      'device_name' => $recent_device['name'],
      'status' => $recent_device['status'],
      'skip' => true,
      'wg_public_key' => $recent_device['wg_public_key'],
      'wg_ip' => $recent_device['wg_ip']
    ], 200);
  }
}

// Check 4: If user has only 1 device of this type, don't create duplicate
$stmt = $conn->prepare("
  SELECT id, name, status, wg_public_key, wg_ip, created_at
  FROM devices 
  WHERE user_id = ? 
    AND name LIKE ?
  ORDER BY created_at DESC
");
$like_name = $device_name . '%';
$stmt->bind_param("is", $user['id'], $like_name);
$stmt->execute();
$devices_of_same_type = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// If user has only 1 device of this type, it's likely the same device - don't create duplicate
if (count($devices_of_same_type) === 1) {
  $existing_device = reset($devices_of_same_type);
  json_out([
    'status' => 'exists',
    'device_id' => (int)$existing_device['id'],
    'device_name' => $existing_device['name'],
    'status' => $existing_device['status'],
    'wg_public_key' => $existing_device['wg_public_key'],
    'wg_ip' => $existing_device['wg_ip'],
    'message' => "Device '{$existing_device['name']}' bestaat al - gebruikt bestaand device",
    'skip' => true
  ], 200);
}

try {
  // Check if user has active subscription - if yes, mark as auto_created (permanent)
  $has_active_sub = $subscription && $subscription['status'] === 'active';
  $auto_created = $has_active_sub ? 1 : 0; // Auto-created devices bij abonnement zijn permanent
  
  $stmt = $conn->prepare("INSERT INTO devices (user_id, name, wg_public_key, wg_ip, status, auto_created) VALUES (?,?,?,?,'active',?)");
  $stmt->bind_param("isssi", $user['id'], $device_name, $wg_public_key, $wg_ip, $auto_created);
  $stmt->execute();
  $device_id = $stmt->insert_id;
  
  // AUTOMATISCH: Zorg dat device ALTIJD actief is (dubbele check)
  // Dit zorgt ervoor dat het systeem direct werkt zodra device is aangemaakt
  $stmt = $conn->prepare("UPDATE devices SET status = 'active' WHERE id = ?");
  $stmt->bind_param("i", $device_id);
  $stmt->execute();
  
  // AUTOMATISCH: Triple check voor auto-created devices
  if ($auto_created) {
    $stmt = $conn->prepare("UPDATE devices SET status = 'active', auto_created = 1 WHERE id = ?");
    $stmt->bind_param("i", $device_id);
    $stmt->execute();
  }
  
  // AUTOMATISCH: Verifieer dat device actief is en systeem direct werkt
  $stmt = $conn->prepare("SELECT status FROM devices WHERE id = ?");
  $stmt->bind_param("i", $device_id);
  $stmt->execute();
  $verify = $stmt->get_result()->fetch_assoc();
  $is_active = $verify && $verify['status'] === 'active';
  
  $message = $has_active_sub 
    ? "✅ Abonnement → ✅ Device '{$device_name}' automatisch geregistreerd → ✅ Direct actief → ✅ Systeem werkt direct!"
    : "✅ Device '{$device_name}' automatisch geregistreerd → ✅ Direct actief → ✅ Systeem werkt direct!";
  
  json_out([
    'status' => 'ok',
    'device_id' => $device_id,
    'device_name' => $device_name,
    'wg_public_key' => $wg_public_key,
    'wg_ip' => $wg_ip,
    'status' => 'active',
    'system_ready' => $is_active,
    'message' => $message,
    'note' => 'Whitelist-only filtering werkt direct. Pornografische content wordt automatisch geblokkeerd op dit device.',
    'ready_to_use' => true,
    'filtering_active' => $is_active,
    'subscription_info' => $subscription ? [
      'plan' => $subscription['plan'],
      'max_devices' => $max_devices,
      'device_count' => $device_count + 1
    ] : null
  ], 201);
} catch (mysqli_sql_exception $e) {
  if ($conn->errno === 1062) {
    // Device already exists - try to return existing device info
    $stmt = $conn->prepare("SELECT id, name, status, wg_public_key, wg_ip FROM devices WHERE user_id = ? AND (wg_public_key = ? OR wg_ip = ?) LIMIT 1");
    $stmt->bind_param("iss", $user['id'], $wg_public_key, $wg_ip);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    
    if ($existing) {
      json_out([
        'status' => 'exists',
        'device_id' => (int)$existing['id'],
        'device_name' => $existing['name'],
        'status' => $existing['status'],
        'wg_public_key' => $existing['wg_public_key'],
        'wg_ip' => $existing['wg_ip'],
        'message' => 'Device bestaat al',
        'skip' => true
      ], 200);
    } else {
      json_out(['message' => 'Device bestaat al', 'skip' => true], 200);
    }
  } else {
    json_out(['message' => 'Database error: ' . $e->getMessage()], 500);
  }
}

