<?php
/**
 * Get device_id from VPN IP address
 * Used by DNS/VPN filter to identify which device is making a request
 * 
 * This endpoint allows the DNS/VPN filter to map VPN IP addresses to device IDs
 */
require __DIR__ . '/../config.php';

$ip = trim((string)($_GET['ip'] ?? $_POST['ip'] ?? ''));
if (empty($ip)) {
    json_out(['message' => 'ip parameter required'], 422);
}

// Validate IP format
if (!filter_var($ip, FILTER_VALIDATE_IP)) {
    json_out(['message' => 'Invalid IP address format'], 422);
}

// Find device by VPN IP
$stmt = $conn->prepare("SELECT id, user_id, name, status, admin_created FROM devices WHERE wg_ip = ?");
$stmt->bind_param("s", $ip);
$stmt->execute();
$device = $stmt->get_result()->fetch_assoc();

if (!$device) {
    json_out([
        'device_id' => null,
        'found' => false,
        'message' => 'No device found with this IP address'
    ], 404);
}

json_out([
    'device_id' => (int)$device['id'],
    'user_id' => (int)$device['user_id'],
    'name' => $device['name'],
    'status' => $device['status'],
    'admin_created' => (int)($device['admin_created'] ?? 0) === 1,
    'found' => true,
    'active' => $device['status'] === 'active'
]);

