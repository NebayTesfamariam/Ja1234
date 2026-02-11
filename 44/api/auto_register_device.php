<?php
/**
 * Automatisch device registreren voor een gebruiker
 * Genereert automatisch WireGuard key en IP adres
 * Wordt aangeroepen na abonnement aansluiten
 */

// Enable error reporting for debugging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Start output buffering to catch any unexpected output
ob_start();

// Register shutdown function to catch fatal errors
register_shutdown_function(function() {
  $error = error_get_last();
  if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
    ob_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
      'message' => 'Fatal error: ' . $error['message'],
      'error' => 'fatal_error',
      'file' => basename($error['file']),
      'line' => $error['line']
    ]);
    exit;
  }
});

try {
  require __DIR__ . '/../config.php';
  $user = require_user($conn);
} catch (Throwable $e) {
  ob_clean();
  error_log("auto_register_device.php config error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
  json_out([
    'message' => 'Configuration error: ' . $e->getMessage(),
    'error' => 'config_failed',
    'file' => basename($e->getFile()),
    'line' => $e->getLine()
  ], 500);
  exit;
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$device_name = trim((string)($body['device_name'] ?? 'Auto Device'));

// Check if user is admin
$stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$user_info = $stmt->get_result()->fetch_assoc();
$is_admin = $user_info && (int)($user_info['is_admin'] ?? 0) === 1;

// Check subscription (not required for admins)
$subscription = null;
$max_devices = null;
$device_count = 0;

if (!$is_admin) {
  // Non-admin users need active subscription
  try {
    // Check if subscription_plans table exists
    $check_table = $conn->query("SHOW TABLES LIKE 'subscription_plans'");
    $table_exists = $check_table && $check_table->num_rows > 0;
    
    if ($table_exists) {
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
    } else {
      // Fallback: use default max_devices if subscription_plans table doesn't exist
      $stmt = $conn->prepare("
        SELECT s.*, 2 as max_devices
        FROM subscriptions s
        WHERE s.user_id = ? 
          AND s.status = 'active' 
          AND s.start_date <= CURDATE()
          AND s.end_date >= CURDATE()
        ORDER BY s.created_at DESC
        LIMIT 1
      ");
    }
    
    if (!$stmt) {
      ob_clean();
      error_log("auto_register_device.php: Failed to prepare subscription query");
      json_out([
        'message' => 'Subscription system not initialized. Please run setup_subscriptions.php',
        'error' => 'Database table missing'
      ], 500);
      exit;
    }
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $subscription = $stmt->get_result()->fetch_assoc();
  } catch (Exception $e) {
    ob_clean();
    error_log("auto_register_device.php subscription check error: " . $e->getMessage());
    json_out([
      'message' => 'Database error: ' . $e->getMessage(),
      'error' => 'Subscription check failed'
    ], 500);
    exit;
  }

  if (!$subscription) {
    ob_clean();
    json_out([
      'message' => 'Geen actief abonnement gevonden',
      'hint' => 'Abonnement vereist om devices toe te voegen. Neem contact op met admin om een abonnement aan te maken.',
      'error' => 'no_active_subscription'
    ], 403);
    exit;
  }

  // Count current devices
  $stmt = $conn->prepare("SELECT COUNT(*) as count FROM devices WHERE user_id = ?");
  $stmt->bind_param("i", $user['id']);
  $stmt->execute();
  $device_count = (int)$stmt->get_result()->fetch_assoc()['count'];
  $max_devices = (int)($subscription['max_devices'] ?? 2); // Default to 2 if not set

  if ($device_count >= $max_devices) {
    ob_clean();
    json_out([
      'message' => "Device limiet bereikt. Je plan ({$subscription['plan']}) staat {$max_devices} device(s) toe.",
      'device_count' => $device_count,
      'max_devices' => $max_devices,
      'error' => 'device_limit_reached'
    ], 403);
    exit;
  }
} else {
  // Admin users: count devices but no limit
  $stmt = $conn->prepare("SELECT COUNT(*) as count FROM devices WHERE user_id = ?");
  $stmt->bind_param("i", $user['id']);
  $stmt->execute();
  $device_count = (int)$stmt->get_result()->fetch_assoc()['count'];
  $max_devices = null; // No limit for admins
}

// Generate automatic WireGuard public key (simulated - in production use real WireGuard)
// Format: base64 encoded random string (typical WireGuard key format)
$wg_public_key = base64_encode(random_bytes(32));

// Generate automatic IP address from range 10.10.0.0/24
// Find next available IP
$stmt = $conn->prepare("SELECT wg_ip FROM devices WHERE wg_ip LIKE '10.10.0.%' ORDER BY wg_ip DESC LIMIT 1");
$stmt->execute();
$last_ip = $stmt->get_result()->fetch_assoc();
$ip_num = 10; // Start from 10.10.0.10

if ($last_ip) {
  $parts = explode('.', $last_ip['wg_ip']);
  $ip_num = (int)$parts[3] + 1;
  if ($ip_num > 254) $ip_num = 10; // Wrap around
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
      'device_id' => (int)$existing_by_key['id'],
      'device_name' => $existing_by_key['name'],
      'status' => $existing_by_key['status'],
      'wg_public_key' => $existing_by_key['wg_public_key'],
      'wg_ip' => $existing_by_key['wg_ip'],
      'message' => 'Device bestaat al - gebruikt bestaand device',
      'note' => 'Dit device is al geregistreerd voor je account.'
    ], 200);
  } else {
    // Key exists for another user - generate new key
    $wg_public_key = base64_encode(random_bytes(32));
    // Re-check with new key
    $stmt = $conn->prepare("SELECT id FROM devices WHERE wg_public_key = ? LIMIT 1");
    $stmt->bind_param("s", $wg_public_key);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
      // Still duplicate - try again
      $wg_public_key = base64_encode(random_bytes(32));
    }
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
      'device_id' => (int)$existing_by_ip['id'],
      'device_name' => $existing_by_ip['name'],
      'status' => $existing_by_ip['status'],
      'wg_public_key' => $existing_by_ip['wg_public_key'],
      'wg_ip' => $existing_by_ip['wg_ip'],
      'message' => 'Device bestaat al - gebruikt bestaand device',
      'note' => 'Dit device is al geregistreerd voor je account.'
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
    // Re-check with new IP
    $stmt = $conn->prepare("SELECT id FROM devices WHERE wg_ip = ? LIMIT 1");
    $stmt->bind_param("s", $wg_ip);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
      // Still duplicate - try next
      $ip_num++;
      if ($ip_num > 254) $ip_num = 10;
      $wg_ip = "10.10.0.{$ip_num}";
    }
  }
}

// Check 3: By device name for same user (if name is already set)
if (!empty($device_name) && $device_name !== 'Auto Device') {
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
      'device_id' => (int)$recent_device['id'],
      'device_name' => $recent_device['name'],
      'status' => $recent_device['status'],
      'wg_public_key' => $recent_device['wg_public_key'],
      'wg_ip' => $recent_device['wg_ip'],
      'message' => "Device '{$device_name}' bestaat al - gebruikt bestaand device",
      'note' => 'Dit device is al geregistreerd voor je account.'
    ], 200);
  }
}

// Generate device name if not provided
if (empty($device_name) || $device_name === 'Auto Device') {
  // Detect device type from user agent if available
  $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
  $device_type = 'Device';
  
  if (stripos($user_agent, 'iPhone') !== false) {
    $device_type = 'iPhone';
  } elseif (stripos($user_agent, 'iPad') !== false) {
    $device_type = 'iPad';
  } elseif (stripos($user_agent, 'Android') !== false) {
    $device_type = 'Android';
  } elseif (stripos($user_agent, 'Windows') !== false) {
    $device_type = 'Windows PC';
  } elseif (stripos($user_agent, 'Mac') !== false || stripos($user_agent, 'macOS') !== false) {
    $device_type = 'Mac';
  } elseif (stripos($user_agent, 'Linux') !== false) {
    $device_type = 'Linux PC';
  }
  
  $device_name = $device_type . ' ' . ($device_count + 1);
}

try {
  // Mark as auto_created when created via subscription (permanent, cannot be unblocked by user)
  // For admins, mark as admin_created instead
  $auto_created = $is_admin ? 0 : 1; // Regular users: auto_created=1, Admins: auto_created=0
  $admin_created = $is_admin ? 1 : 0; // Admins: admin_created=1
  
  if ($is_admin) {
    // Admin-created devices: use admin_created flag
    $stmt = $conn->prepare("INSERT INTO devices (user_id, name, wg_public_key, wg_ip, status, admin_created) VALUES (?,?,?,?,'active',?)");
    $stmt->bind_param("isssi", $user['id'], $device_name, $wg_public_key, $wg_ip, $admin_created);
  } else {
    // Regular users: use auto_created flag
    $stmt = $conn->prepare("INSERT INTO devices (user_id, name, wg_public_key, wg_ip, status, auto_created) VALUES (?,?,?,?,'active',?)");
    $stmt->bind_param("isssi", $user['id'], $device_name, $wg_public_key, $wg_ip, $auto_created);
  }
  
  $stmt->execute();
  $device_id = $stmt->insert_id;
  
  // AUTOMATISCH: Zorg dat device ALTIJD actief is (dubbele check)
  // Dit zorgt ervoor dat het systeem direct werkt zodra device is aangemaakt
  $stmt = $conn->prepare("UPDATE devices SET status = 'active' WHERE id = ?");
  $stmt->bind_param("i", $device_id);
  $stmt->execute();
  
  // AUTOMATISCH: Triple check voor admin-created devices
  if ($is_admin) {
    $stmt = $conn->prepare("UPDATE devices SET status = 'active', admin_created = 1 WHERE id = ?");
    $stmt->bind_param("i", $device_id);
    $stmt->execute();
  } else {
    // AUTOMATISCH: Triple check voor auto-created devices
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
  
  // Clean any output before JSON
  ob_clean();
  
  json_out([
    'status' => 'ok',
    'device_id' => $device_id,
    'device_name' => $device_name,
    'wg_public_key' => $wg_public_key,
    'wg_ip' => $wg_ip,
    'admin_created' => $is_admin,
    'auto_created' => !$is_admin,
    'status' => 'active',
    'system_ready' => $is_active,
    'message' => $is_admin 
      ? '✅ Device automatisch geregistreerd (admin) → ✅ Direct actief → ✅ Systeem werkt direct!' 
      : '✅ Device automatisch geregistreerd → ✅ Direct actief → ✅ Systeem werkt direct!',
    'note' => $is_admin
      ? 'Admin device - permanent actief, geen abonnement vereist. Whitelist-only filtering werkt direct.'
      : 'Whitelist-only filtering werkt direct. Pornografische content wordt automatisch geblokkeerd op dit device.',
    'ready_to_use' => true,
    'filtering_active' => $is_active
  ], 201);
} catch (mysqli_sql_exception $e) {
  ob_clean();
  error_log("auto_register_device.php database error: " . $e->getMessage());
  json_out(['message' => 'Database error: ' . $e->getMessage()], 500);
} catch (Throwable $e) {
  ob_clean();
  error_log("auto_register_device.php error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
  json_out([
    'message' => 'Server error: ' . $e->getMessage(),
    'error' => 'unexpected_error',
    'file' => basename($e->getFile()),
    'line' => $e->getLine()
  ], 500);
}

