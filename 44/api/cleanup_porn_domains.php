<?php
/**
 * Permanent Cleanup: Remove all pornographic domains from whitelist
 * This runs automatically and cannot be disabled
 */

require __DIR__ . '/../config.php';
require __DIR__ . '/../config_porn_block.php';

// Check admin
$user = require_user($conn);
$stmt = $conn->prepare("SELECT is_admin FROM users WHERE id=?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
if (!$result || (int)($result['is_admin'] ?? 0) !== 1) {
    json_out(['message' => 'Access denied - Admin privileges required'], 403);
}

// Remove all pornographic domains
$removed = remove_pornographic_domains_from_whitelist($conn);

json_out([
    'status' => 'cleaned',
    'removed' => $removed,
    'message' => "$removed pornografische domein(en) permanent verwijderd uit whitelist",
    'permanent' => true
]);
