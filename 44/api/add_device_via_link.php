<?php
/**
 * Voeg een device toe via een registratie link (zonder login vereist)
 */
require __DIR__ . '/../config.php';

// Get token from query parameter or POST body
$token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
if (empty($token)) {
  $body = json_decode(file_get_contents('php://input'), true) ?? [];
  $token = trim((string)($body['token'] ?? ''));
}

if (empty($token)) {
  json_out(['message' => 'Token vereist'], 422);
}

// Create device_registration_links table if it doesn't exist
try {
  // Check if table exists
  $result = $conn->query("SHOW TABLES LIKE 'device_registration_links'");
  if ($result->num_rows === 0) {
    // Table doesn't exist, create it
    $create_sql = "
      CREATE TABLE `device_registration_links` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT UNSIGNED NOT NULL,
        `token` VARCHAR(64) NOT NULL UNIQUE,
        `expires_at` DATETIME NOT NULL,
        `max_uses` INT UNSIGNED DEFAULT 1,
        `uses_count` INT UNSIGNED DEFAULT 0,
        `created_by` INT UNSIGNED NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_token` (`token`),
        INDEX `idx_user_id` (`user_id`),
        INDEX `idx_expires_at` (`expires_at`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    if (!$conn->query($create_sql)) {
      throw new Exception("Failed to create device_registration_links table: " . $conn->error);
    }
    
    // Try to add foreign keys after table creation
    try {
      $conn->query("ALTER TABLE `device_registration_links` ADD CONSTRAINT `fk_link_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE");
    } catch (Exception $e) {
      // Foreign key might fail, that's okay
    }
    try {
      $conn->query("ALTER TABLE `device_registration_links` ADD CONSTRAINT `fk_link_created_by` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE");
    } catch (Exception $e) {
      // Foreign key might fail, that's okay
    }
  }
} catch (Exception $e) {
  error_log("Error creating device_registration_links table: " . $e->getMessage());
  // Continue anyway - table might already exist
}

// Validate token
$stmt = $conn->prepare("
  SELECT id, user_id, expires_at, max_uses, uses_count
  FROM device_registration_links
  WHERE token = ?
");
$stmt->bind_param("s", $token);
$stmt->execute();
$link = $stmt->get_result()->fetch_assoc();

if (!$link) {
  json_out(['message' => 'Ongeldige of verlopen link'], 404);
}

// Check if link is expired
if (strtotime($link['expires_at']) < time()) {
  json_out(['message' => 'Deze link is verlopen'], 410);
}

// Check if max uses reached
if ((int)$link['uses_count'] >= (int)$link['max_uses']) {
  json_out(['message' => 'Deze link is al gebruikt (max aantal keer bereikt)'], 410);
}

$user_id = (int)$link['user_id'];

// Get device name from POST body or auto-detect
$body = json_decode(file_get_contents('php://input'), true) ?? [];
$device_name = trim((string)($body['device_name'] ?? ''));

// Auto-detect device name if not provided
if (empty($device_name)) {
  $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
  if (stripos($user_agent, 'iPhone') !== false) {
    $device_name = 'iPhone';
  } elseif (stripos($user_agent, 'iPad') !== false) {
    $device_name = 'iPad';
  } elseif (stripos($user_agent, 'Android') !== false) {
    $device_name = 'Android Device';
  } elseif (stripos($user_agent, 'Windows') !== false) {
    $device_name = 'Windows PC';
  } elseif (stripos($user_agent, 'Mac') !== false || stripos($user_agent, 'macOS') !== false) {
    $device_name = 'Mac';
  } elseif (stripos($user_agent, 'Linux') !== false) {
    $device_name = 'Linux PC';
  } else {
    $device_name = 'Device';
  }
  
  // Add number if user already has devices with this name
  $stmt = $conn->prepare("SELECT COUNT(*) as count FROM devices WHERE user_id = ? AND name LIKE ?");
  $like_name = $device_name . '%';
  $stmt->bind_param("is", $user_id, $like_name);
  $stmt->execute();
  $count = (int)$stmt->get_result()->fetch_assoc()['count'];
  if ($count > 0) {
    $device_name = $device_name . ' ' . ($count + 1);
  }
}

// Generate WireGuard key and IP
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
  if ((int)$existing_by_key['user_id'] === (int)$user_id) {
    // Device already exists for this user - increment uses and return existing device
    $stmt = $conn->prepare("UPDATE device_registration_links SET uses_count = uses_count + 1 WHERE id = ?");
    $stmt->bind_param("i", $link['id']);
    $stmt->execute();
    
    json_out([
      'status' => 'exists',
      'device_id' => (int)$existing_by_key['id'],
      'device_name' => $existing_by_key['name'],
      'status' => $existing_by_key['status'],
      'message' => 'Device bestaat al - gebruikt bestaand device'
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
  if ((int)$existing_by_ip['user_id'] === (int)$user_id) {
    // Device already exists for this user - increment uses and return existing device
    $stmt = $conn->prepare("UPDATE device_registration_links SET uses_count = uses_count + 1 WHERE id = ?");
    $stmt->bind_param("i", $link['id']);
    $stmt->execute();
    
    json_out([
      'status' => 'exists',
      'device_id' => (int)$existing_by_ip['id'],
      'device_name' => $existing_by_ip['name'],
      'status' => $existing_by_ip['status'],
      'message' => 'Device bestaat al - gebruikt bestaand device'
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

// Check 3: By device name for same user (prevent same device name for same user)
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
  $stmt->bind_param("is", $user_id, $device_name);
  $stmt->execute();
  $recent_device = $stmt->get_result()->fetch_assoc();
  
  if ($recent_device) {
    // Increment uses count
    $stmt = $conn->prepare("UPDATE device_registration_links SET uses_count = uses_count + 1 WHERE id = ?");
    $stmt->bind_param("i", $link['id']);
    $stmt->execute();
    
    json_out([
      'status' => 'exists',
      'device_id' => (int)$recent_device['id'],
      'device_name' => $recent_device['name'],
      'status' => $recent_device['status'],
      'message' => "Device '{$device_name}' bestaat al - gebruikt bestaand device"
    ], 200);
  }
}

// Check subscription (optional - admin-created devices don't need subscription)
// But for regular users, we should check
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
$stmt->bind_param("i", $user_id);
$stmt->execute();
$subscription = $stmt->get_result()->fetch_assoc();

// Count current devices
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM devices WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$device_count = (int)$stmt->get_result()->fetch_assoc()['count'];

// If subscription exists, check device limit
if ($subscription) {
  $max_devices = (int)$subscription['max_devices'];
  if ($device_count >= $max_devices) {
    json_out([
      'message' => "Device limiet bereikt. Je plan staat {$max_devices} device(s) toe.",
      'device_count' => $device_count,
      'max_devices' => $max_devices
    ], 403);
  }
}

// Create device - ALWAYS active, regardless of subscription
// Mark as admin_created so it always works (permanent access)
$admin_created = 1; // Devices via links get permanent access, always active
$stmt = $conn->prepare("INSERT INTO devices (user_id, name, wg_public_key, wg_ip, status, admin_created) VALUES (?,?,?,?,'active',?)");
$stmt->bind_param("isssi", $user_id, $device_name, $wg_public_key, $wg_ip, $admin_created);
$stmt->execute();
$device_id = $stmt->insert_id;

// Ensure device is always active (double check)
$stmt = $conn->prepare("UPDATE devices SET status = 'active' WHERE id = ?");
$stmt->bind_param("i", $device_id);
$stmt->execute();

// Increment uses count
$stmt = $conn->prepare("UPDATE device_registration_links SET uses_count = uses_count + 1 WHERE id = ?");
$stmt->bind_param("i", $link['id']);
$stmt->execute();

json_out([
  'status' => 'created',
  'device_id' => $device_id,
  'device_name' => $device_name,
  'wg_public_key' => $wg_public_key,
  'wg_ip' => $wg_ip,
  'message' => '✅ Device toegevoegd via link!'
], 201);

