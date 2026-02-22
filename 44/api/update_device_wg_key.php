<?php
/**
 * Update a device's WireGuard public key (client-side key flow).
 * Only the public key is sent; private key never touches the server.
 */
require __DIR__ . '/../config.php';

$user = null;
$body = json_decode(file_get_contents('php://input'), true) ?? [];
$token = trim((string)($body['token'] ?? $_GET['token'] ?? ''));
if (empty($token)) {
  $token = get_bearer_token();
}
if ($token) {
  try {
    $decoded = base64_decode($token, true);
    if ($decoded && strpos($decoded, ':') !== false) {
      $parts = explode(':', $decoded, 2);
      $user_id = (int)$parts[0];
      $stmt = $conn->prepare("SELECT id, email FROM users WHERE id = ?");
      $stmt->bind_param("i", $user_id);
      $stmt->execute();
      $user = $stmt->get_result()->fetch_assoc();
    }
  } catch (Exception $e) {
    // fall through to require_user
  }
}
if (!$user) {
  try {
    $user = require_user($conn);
  } catch (Exception $e) {
    header('Content-Type: application/json');
    json_out(['message' => 'Authentication required'], 401);
    exit;
  }
}

$device_id = (int)($body['device_id'] ?? 0);
$wg_public_key = trim((string)($body['wg_public_key'] ?? ''));

if ($device_id <= 0 || $wg_public_key === '') {
  json_out(['message' => 'device_id and wg_public_key required'], 422);
}

if (strlen($wg_public_key) > 255) {
  json_out(['message' => 'wg_public_key too long'], 422);
}

$stmt = $conn->prepare("SELECT id, name, status FROM devices WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $device_id, $user['id']);
$stmt->execute();
$device = $stmt->get_result()->fetch_assoc();

if (!$device) {
  json_out(['message' => 'Device not found'], 404);
}

// Optional: allow only active devices to update key
if ($device['status'] !== 'active') {
  json_out(['message' => 'Device is not active'], 403);
}

// Check key uniqueness (global - no duplicate public keys)
$stmt = $conn->prepare("SELECT id, user_id FROM devices WHERE wg_public_key = ? AND id != ?");
$stmt->bind_param("si", $wg_public_key, $device_id);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
if ($existing) {
  json_out(['message' => 'This WireGuard public key is already in use by another device'], 409);
}

$stmt = $conn->prepare("UPDATE devices SET wg_public_key = ? WHERE id = ? AND user_id = ?");
$stmt->bind_param("sii", $wg_public_key, $device_id, $user['id']);
$stmt->execute();

if ($stmt->affected_rows === 0) {
  json_out(['message' => 'Update failed'], 500);
}

json_out([
  'status' => 'ok',
  'message' => 'WireGuard public key updated',
  'device_id' => $device_id,
  'device_name' => $device['name'],
], 200);
