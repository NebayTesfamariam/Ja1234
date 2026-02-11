<?php
/**
 * Cleanup old activity logs (older than 90 days)
 */
require __DIR__ . '/../config.php';
$user = require_user($conn);

// Check admin
$stmt = $conn->prepare("SELECT is_admin FROM users WHERE id=?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
if (!$result || !$result['is_admin']) {
  json_out(['message' => 'Access denied'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  json_out(['message' => 'Method not allowed'], 405);
}

// Delete logs older than 90 days
$stmt = $conn->prepare("DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
$stmt->execute();
$deleted = $stmt->affected_rows;

json_out([
  'message' => "{$deleted} oude logs verwijderd (ouder dan 90 dagen)",
  'deleted_count' => $deleted
]);

