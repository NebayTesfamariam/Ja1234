<?php
require __DIR__ . '/../config.php';
$user = require_user($conn);

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$name = trim((string)($body['name'] ?? ''));
$wg_public_key = trim((string)($body['wg_public_key'] ?? ''));
$wg_ip = trim((string)($body['wg_ip'] ?? ''));

if ($name === '' || $wg_public_key === '' || $wg_ip === '') {
  json_out(['message' => 'name, wg_public_key, wg_ip required'], 422);
}

// Basic validation
if (strlen($name) > 255) {
  json_out(['message' => 'Naam is te lang (max 255 karakters)'], 422);
}
if (strlen($wg_public_key) > 255) {
  json_out(['message' => 'WireGuard key is te lang'], 422);
}
if (!filter_var($wg_ip, FILTER_VALIDATE_IP)) {
  json_out(['message' => 'Ongeldig IP adres'], 422);
}

// Check subscription and device limit
// Subscription must be active and start_date must be today or earlier (works immediately)
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
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$subscription = $stmt->get_result()->fetch_assoc();

if (!$subscription) {
  json_out([
    'message' => 'Geen actief abonnement. Abonnement vereist om devices toe te voegen.',
    'hint' => 'Neem contact op met admin om een abonnement aan te maken.'
  ], 403);
}

// Count current devices
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM devices WHERE user_id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$device_count = (int)$stmt->get_result()->fetch_assoc()['count'];
$max_devices = (int)$subscription['max_devices'];

if ($device_count >= $max_devices) {
  json_out([
    'message' => "Device limiet bereikt. Je plan ({$subscription['plan']}) staat {$max_devices} device(s) toe. Upgrade je plan om meer devices toe te voegen.",
    'device_count' => $device_count,
    'max_devices' => $max_devices,
    'plan' => $subscription['plan']
  ], 403);
}

// STRICT: Prevent duplicate devices - check multiple criteria
// Check 1: By WireGuard key (GLOBAL - must be unique across all users)
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
      'message' => 'Device bestaat al - dit device is al geregistreerd voor je account',
      'note' => 'Je kunt dit device gebruiken zonder het opnieuw toe te voegen.'
    ], 200);
  } else {
    json_out([
      'status' => 'exists',
      'message' => 'Dit WireGuard key is al in gebruik door een ander device',
      'error' => 'duplicate_wg_key'
    ], 409);
  }
}

// Check 2: By IP address (GLOBAL - must be unique across all users)
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
      'message' => 'Device bestaat al - dit device is al geregistreerd voor je account',
      'note' => 'Je kunt dit device gebruiken zonder het opnieuw toe te voegen.'
    ], 200);
  } else {
    json_out([
      'status' => 'exists',
      'message' => 'Dit IP adres is al in gebruik door een ander device',
      'error' => 'duplicate_ip'
    ], 409);
  }
}

// Check 3: By device name for same user (prevent same name for same user)
if (!empty($name)) {
  $stmt = $conn->prepare("
    SELECT id, name, status, wg_public_key, wg_ip, created_at
    FROM devices 
    WHERE user_id = ? 
      AND name = ?
      AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ORDER BY created_at DESC
    LIMIT 1
  ");
  $stmt->bind_param("is", $user['id'], $name);
  $stmt->execute();
  $recent_device = $stmt->get_result()->fetch_assoc();
  
  if ($recent_device) {
    json_out([
      'status' => 'exists',
      'device_id' => (int)$recent_device['id'],
      'device_name' => $recent_device['name'],
      'status' => $recent_device['status'],
      'message' => "Device '{$name}' bestaat al - dit device is al geregistreerd voor je account",
      'note' => 'Je kunt dit device gebruiken zonder het opnieuw toe te voegen.'
    ], 200);
  }
}

try {
  // AUTOMATISCH: Device wordt direct actief gemaakt - systeem werkt direct
  $stmt = $conn->prepare("INSERT INTO devices (user_id, name, wg_public_key, wg_ip, status) VALUES (?,?,?,?,'active')");
  $stmt->bind_param("isss", $user['id'], $name, $wg_public_key, $wg_ip);
  $stmt->execute();
  $device_id = $stmt->insert_id;
  
  // AUTOMATISCH: Dubbele check - zorg dat device ALTIJD actief is
  $stmt = $conn->prepare("UPDATE devices SET status = 'active' WHERE id = ?");
  $stmt->bind_param("i", $device_id);
  $stmt->execute();
  
  // Log successful device creation
  require_once __DIR__ . '/../config_security_advanced.php';
  audit_log($conn, 'device_created', 'device', $device_id, [
    'user_id' => $user['id'],
    'device_name' => $name,
    'status' => 'active',
    'system_ready' => true
  ]);
  
  json_out([
    'status' => 'ok', 
    'device_id' => $device_id,
    'status' => 'active',
    'system_ready' => true,
    'message' => '✅ Device toegevoegd → ✅ Direct actief → ✅ Systeem werkt direct!'
  ], 201);
} catch (mysqli_sql_exception $e) {
  if ($conn->errno === 1062) {
    // Duplicate entry - device already exists (database constraint violation)
    // Try to find the existing device
    $stmt = $conn->prepare("SELECT id, name, status FROM devices WHERE wg_public_key = ? OR wg_ip = ? LIMIT 1");
    $stmt->bind_param("ss", $wg_public_key, $wg_ip);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    
    if ($existing) {
      json_out([
        'status' => 'exists',
        'device_id' => (int)$existing['id'],
        'device_name' => $existing['name'],
        'status' => $existing['status'],
        'message' => 'Device bestaat al - dit device is al geregistreerd (database constraint)'
      ], 200);
    } else {
      json_out([
        'status' => 'exists',
        'message' => 'Device bestaat al - dit device is al geregistreerd voor je account'
      ], 200);
    }
  } else {
    json_out(['message' => 'Database error: ' . $e->getMessage()], 500);
  }
}
