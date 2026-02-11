<?php
/**
 * Remove Bing and Microsoft domains from whitelist
 * These domains can show pornographic content via search results
 */

require __DIR__ . '/config.php';

header('Content-Type: application/json');

// Check if user is admin
$user = require_user($conn);
$stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$user_info = $stmt->get_result()->fetch_assoc();
$is_admin = $user_info && (int)($user_info['is_admin'] ?? 0) === 1;

if (!$is_admin) {
    json_out(['error' => 'Admin privileges required'], 403);
    exit;
}

$device_id = (int)($_GET['device_id'] ?? 0);
$remove_all = (bool)($_GET['remove_all'] ?? false);

$domains_to_remove = [
    'bing.com',
    'www.bing.com',
    'microsoft.com',
    'www.microsoft.com',
    'live.com',
    'www.live.com',
    'msn.com',
    'www.msn.com'
];

$results = [
    'removed' => [],
    'not_found' => [],
    'errors' => []
];

try {
    if ($remove_all) {
        // Remove from all devices
        foreach ($domains_to_remove as $domain) {
            $stmt = $conn->prepare("DELETE FROM whitelist WHERE domain = ?");
            $stmt->bind_param("s", $domain);
            if ($stmt->execute()) {
                $affected = $stmt->affected_rows;
                if ($affected > 0) {
                    $results['removed'][] = "$domain (from $affected device(s))";
                } else {
                    $results['not_found'][] = $domain;
                }
            } else {
                $results['errors'][] = "Error removing $domain: " . $conn->error;
            }
        }
    } elseif ($device_id > 0) {
        // Remove from specific device
        foreach ($domains_to_remove as $domain) {
            $stmt = $conn->prepare("DELETE FROM whitelist WHERE device_id = ? AND domain = ?");
            $stmt->bind_param("is", $device_id, $domain);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $results['removed'][] = "$domain (device $device_id)";
                } else {
                    $results['not_found'][] = "$domain (device $device_id)";
                }
            } else {
                $results['errors'][] = "Error removing $domain: " . $conn->error;
            }
        }
    } else {
        json_out(['error' => 'device_id or remove_all parameter required'], 400);
        exit;
    }
    
    $results['success'] = count($results['removed']) > 0;
    $results['message'] = count($results['removed']) > 0 
        ? '✅ Bing/Microsoft domeinen verwijderd uit whitelist'
        : 'ℹ️ Geen Bing/Microsoft domeinen gevonden in whitelist';
    
    json_out($results);
    
} catch (Exception $e) {
    json_out([
        'error' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ], 500);
}
