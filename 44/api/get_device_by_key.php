<?php
/**
 * Get device_id from WireGuard public key
 * Used by DNS/VPN filter to identify which device is making a request
 * 
 * Alternative to get_device_by_ip.php - uses WireGuard key instead of IP
 */
require __DIR__ . '/../config.php';

$key = trim((string)($_GET['key'] ?? $_POST['key'] ?? ''));
if (empty($key)) {
    json_out(['message' => 'key parameter required'], 422);
}

// Find device by WireGuard public key
$stmt = $conn->prepare("SELECT id, user_id, name, status, admin_created, wg_ip FROM devices WHERE wg_public_key = ?");
$stmt->bind_param("s", $key);
$stmt->execute();
$device = $stmt->get_result()->fetch_assoc();

if (!$device) {
    json_out([
        'device_id' => null,
        'found' => false,
        'message' => 'No device found with this WireGuard key'
    ], 404);
}

json_out([
    'device_id' => (int)$device['id'],
    'user_id' => (int)$device['user_id'],
    'name' => $device['name'],
    'status' => $device['status'],
    'admin_created' => (int)($device['admin_created'] ?? 0) === 1,
    'wg_ip' => $device['wg_ip'],
    'found' => true,
    'active' => $device['status'] === 'active'
]);

