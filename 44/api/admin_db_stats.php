<?php
/**
 * Get database statistics
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

$stats = [];

// Get table sizes
// Whitelist-only system: only check whitelist tables
$tables = ['users', 'devices', 'whitelist', 'subscriptions', 'activity_logs'];
// Note: blocklist tables removed from stats - whitelist-only system
foreach ($tables as $table) {
  try {
    $result = $conn->query("SELECT COUNT(*) as count FROM `{$table}`");
    if ($result) {
      $row = $result->fetch_assoc();
      $stats[$table . '_count'] = (int)$row['count'];
    }
  } catch (Exception $e) {
    $stats[$table . '_count'] = 0;
  }
}

// Get database size
try {
  $result = $conn->query("
    SELECT 
      ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
    FROM information_schema.tables 
    WHERE table_schema = DATABASE()
  ");
  if ($result) {
    $row = $result->fetch_assoc();
    $stats['database_size_mb'] = round($row['size_mb'] ?? 0, 2);
  }
} catch (Exception $e) {
  $stats['database_size_mb'] = 0;
}

json_out(['stats' => $stats]);

