<?php
require __DIR__ . '/../config.php';
require __DIR__ . '/../config_cache.php';
$user = require_user($conn);

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$id = (int)($body['id'] ?? 0);
if ($id <= 0) json_out(['message' => 'id required'], 422);

// Get device_id before deleting
$stmt = $conn->prepare("
  SELECT w.device_id 
  FROM whitelist w
  JOIN devices d ON d.id = w.device_id
  WHERE w.id=? AND d.user_id=?
");
$stmt->bind_param("ii", $id, $user['id']);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if ($result) {
  $device_id = (int)$result['device_id'];
  
  // Delete whitelist entry
  $stmt = $conn->prepare("
    DELETE w FROM whitelist w
    JOIN devices d ON d.id = w.device_id
    WHERE w.id=? AND d.user_id=?
  ");
  $stmt->bind_param("ii", $id, $user['id']);
  $stmt->execute();
  
  // Clear cache for this device
  $cache_key = SimpleCache::key('whitelist', $device_id, $user['id']);
  SimpleCache::clear($cache_key);
  
  json_out(['ok' => true]);
} else {
  json_out(['message' => 'Whitelist entry not found'], 404);
}
