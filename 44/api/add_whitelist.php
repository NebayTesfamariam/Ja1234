<?php
require __DIR__ . '/../config.php';
require __DIR__ . '/../config_cache.php';
require __DIR__ . '/../config_porn_block.php';
$user = require_user($conn);

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$device_id = (int)($body['device_id'] ?? 0);
$raw_domain = trim((string)($body['domain'] ?? ''));

// Normalize domain - remove http/https, paths, www
if (function_exists('normalize_domain')) {
  $domain = normalize_domain($raw_domain);
} else {
  // Fallback normalization
  $domain = strtolower(trim($raw_domain));
  $domain = preg_replace('#^https?://#', '', $domain);
  $domain = explode('/', $domain)[0];
  $domain = ltrim($domain, 'www.');
  $domain = trim($domain, '.');
}

$comment = trim((string)($body['comment'] ?? ''));

if ($device_id <= 0 || $domain === '') json_out(['message' => 'device_id and domain required'], 422);

// PERMANENT BLOCK: Check if domain is pornographic - CANNOT BE ADDED
$domain_validation = validate_domain_for_whitelist($domain);
if (!$domain_validation['valid']) {
    if (isset($domain_validation['blocked']) && $domain_validation['blocked']) {
        json_out([
            'message' => 'Pornografisch domein gedetecteerd - permanent geblokkeerd',
            'reason' => 'Dit domein kan niet worden toegevoegd aan de whitelist',
            'blocked' => true,
            'permanent' => true
        ], 403);
    }
    json_out(['message' => $domain_validation['reason'] ?? 'Invalid domain'], 422);
}
$domain = $domain_validation['domain'];

// check device belongs to user
$stmt = $conn->prepare("SELECT id FROM devices WHERE id=? AND user_id=? AND status='active'");
$stmt->bind_param("ii", $device_id, $user['id']);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) json_out(['message' => 'Device not found'], 404);

try {
  $stmt = $conn->prepare("INSERT INTO whitelist (device_id, domain, enabled, comment) VALUES (?,?,1,?)");
  $stmt->bind_param("iss", $device_id, $domain, $comment);
  $stmt->execute();
  
  // Clear cache for this device (dashboard + DNS server)
  $cache_key = SimpleCache::key('whitelist', $device_id, $user['id']);
  SimpleCache::clear($cache_key);
  SimpleCache::clear(SimpleCache::key('whitelist_dns', $device_id));
  
  json_out(['status' => 'added', 'id' => $stmt->insert_id], 201);
} catch (mysqli_sql_exception $e) {
  if ($conn->errno === 1062) { // Duplicate entry
    json_out(['message' => 'Dit domein staat al in de whitelist voor dit device'], 409);
  }
  json_out(['message' => 'Database error: ' . $e->getMessage()], 500);
}
