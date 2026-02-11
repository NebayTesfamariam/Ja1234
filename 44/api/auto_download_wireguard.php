<?php
/**
 * Auto-download WireGuard config after registration
 * Returns config file for automatic download
 */
require __DIR__ . '/../config.php';
require __DIR__ . '/../config_cache.php';

header('Content-Type: application/json');

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$device_id = (int)($body['device_id'] ?? 0);
$token = trim((string)($body['token'] ?? ''));

if ($device_id <= 0) {
    json_out(['message' => 'device_id required'], 422);
}

// Verify token or session
$user = null;
if ($token) {
    // Verify token
    $parts = explode(':', base64_decode($token));
    if (count($parts) === 2) {
        $user_id = (int)$parts[0];
        $stmt = $conn->prepare("SELECT id, email FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
    }
} else {
    // Try session
    try {
        $user = require_user($conn);
    } catch (Exception $e) {
        json_out(['message' => 'Authentication required'], 401);
    }
}

if (!$user) {
    json_out(['message' => 'Authentication failed'], 401);
}

// Get device
$stmt = $conn->prepare("SELECT d.*, u.email as user_email FROM devices d JOIN users u ON d.user_id = u.id WHERE d.id = ? AND d.user_id = ?");
$stmt->bind_param("ii", $device_id, $user['id']);
$stmt->execute();
$device = $stmt->get_result()->fetch_assoc();

if (!$device) {
    json_out(['message' => 'Device not found'], 404);
}

// Return config data (not file download - let frontend handle download)
$config_data = [
    'device_id' => $device['id'],
    'device_name' => $device['name'],
    'wg_public_key' => $device['wg_public_key'],
    'wg_ip' => $device['wg_ip'],
    'config_url' => "api/get_wireguard_config.php?device_id={$device_id}",
    'download_url' => "api/get_wireguard_config.php?device_id={$device_id}&download=1"
];

json_out($config_data);
