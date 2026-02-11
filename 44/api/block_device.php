<?php
/**
 * Block device endpoint
 * 
 * REGELS:
 * 1. Alleen gebruikers met actief abonnement kunnen devices blokkeren/deblokkeren
 * 2. Devices die automatisch zijn aangemaakt bij abonnement (auto_created=1) kunnen NOOIT worden deblokkeerd
 * 3. Gebruikers kunnen hun eigen devices blokkeren, maar niet deblokkeren (alleen admin)
 */
require __DIR__ . '/../config.php';
$user = require_user($conn);

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$device_id = (int)($body['device_id'] ?? 0);
$action = trim((string)($body['action'] ?? '')); // 'block' or 'unblock'

if ($device_id <= 0) {
  json_out(['message' => 'device_id required'], 422);
}

if (!in_array($action, ['block', 'unblock'])) {
  json_out(['message' => 'action must be "block" or "unblock"'], 422);
}

// Check if user is admin
$stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$user_info = $stmt->get_result()->fetch_assoc();
$is_admin = $user_info && $user_info['is_admin'];

// Check if user has active subscription (REQUIRED for blocking/unblocking)
$stmt = $conn->prepare("
  SELECT s.* 
  FROM subscriptions s
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

if (!$subscription && !$is_admin) {
  json_out([
    'message' => 'Alleen gebruikers met actief abonnement kunnen devices blokkeren/deblokkeren',
    'error' => 'no_active_subscription'
  ], 403);
}

// Check device belongs to user (or admin can access any device)
if (!$is_admin) {
  $stmt = $conn->prepare("SELECT id, name, status, auto_created FROM devices WHERE id = ? AND user_id = ?");
  $stmt->bind_param("ii", $device_id, $user['id']);
  $stmt->execute();
  $device = $stmt->get_result()->fetch_assoc();
} else {
  // Admin can access any device
  $stmt = $conn->prepare("SELECT id, name, status, auto_created FROM devices WHERE id = ?");
  $stmt->bind_param("i", $device_id);
  $stmt->execute();
  $device = $stmt->get_result()->fetch_assoc();
}

if (!$device) {
  json_out(['message' => 'Device not found'], 404);
}

// CRITICAL: Devices die permanent geblokkeerd zijn door admin kunnen NOOIT worden deblokkeerd
// Note: Devices blocked due to expired subscription can be unblocked automatically when subscription reactivates
// Only devices with permanent_blocked = 1 (set by admin) cannot be unblocked
$stmt = $conn->prepare("SELECT auto_created, permanent_blocked FROM devices WHERE id = ?");
$stmt->bind_param("i", $device_id);
$stmt->execute();
$device_flags = $stmt->get_result()->fetch_assoc();

if ($action === 'unblock' && (int)$device_flags['permanent_blocked'] === 1) {
  json_out([
    'message' => 'Dit device is permanent geblokkeerd door admin. Het kan NOOIT worden deblokkeerd.',
    'error' => 'permanent_blocked',
    'device_name' => $device['name'],
    'permanent_blocked' => true,
    'note' => 'Permanent geblokkeerde devices (door admin) kunnen niet worden deblokkeerd'
  ], 403);
}

// If device is blocked due to expired subscription, check if subscription is active again
if ($action === 'unblock' && (int)$device_flags['permanent_blocked'] === 0) {
  // Check if user has active subscription
  $stmt = $conn->prepare("
    SELECT s.id 
    FROM subscriptions s
    WHERE s.user_id = ? 
      AND s.status = 'active' 
      AND s.start_date <= CURDATE()
      AND s.end_date >= CURDATE()
    ORDER BY s.created_at DESC
    LIMIT 1
  ");
  $stmt->bind_param("i", $user['id']);
  $stmt->execute();
  $active_sub = $stmt->get_result()->fetch_assoc();
  
  if (!$active_sub) {
    json_out([
      'message' => 'Abonnement is niet actief. Betaal opnieuw om devices te deblokkeren.',
      'error' => 'no_active_subscription',
      'device_name' => $device['name']
    ], 403);
  }
}

// Users CANNOT block or unblock devices - only admins can
// Once a device is added, it must remain active for users
if (!$is_admin) {
  json_out([
    'message' => 'Gebruikers kunnen devices niet blokkeren of deblokkeren. Devices blijven actief zodra ze zijn toegevoegd. Neem contact op met een administrator als je hulp nodig hebt.',
    'error' => 'block_not_allowed'
  ], 403);
}

// Users CANNOT unblock devices - only admins can (except auto_created devices which can never be unblocked)
if ($action === 'unblock' && !$is_admin) {
  json_out([
    'message' => 'Gebruikers kunnen devices niet deblokkeren. Neem contact op met een administrator.',
    'error' => 'unblock_not_allowed'
  ], 403);
}

$new_status = $action === 'block' ? 'blocked' : 'active';

// Update device status
if (!$is_admin) {
  // Users can only update their own devices
  $stmt = $conn->prepare("UPDATE devices SET status = ? WHERE id = ? AND user_id = ?");
  $stmt->bind_param("sii", $new_status, $device_id, $user['id']);
} else {
  // Admins can update any device (but auto_created devices are still protected above)
  $stmt = $conn->prepare("UPDATE devices SET status = ? WHERE id = ?");
  $stmt->bind_param("si", $new_status, $device_id);
}
$stmt->execute();

if ($action === 'block') {
  json_out([
    'status' => 'blocked',
    'message' => 'Device geblokkeerd - geen internet toegang meer',
    'device_name' => $device['name'],
    'note' => $is_admin ? '' : 'Neem contact op met admin om dit device te deblokkeren'
  ]);
} else {
  json_out([
    'status' => 'active',
    'message' => 'Device deblokkeerd - internet toegang hersteld',
    'device_name' => $device['name']
  ]);
}

