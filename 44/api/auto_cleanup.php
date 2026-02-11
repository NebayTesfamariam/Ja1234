<?php
/**
 * Automatic Cleanup Script
 * Cleans up old data: expired links, old logs, etc.
 * Should be called via cronjob daily
 */
require __DIR__ . '/../config.php';
require __DIR__ . '/../config_logging.php';

$cleaned = [
  'expired_links' => 0,
  'old_logs' => 0,
  'old_activity_logs' => 0
];

try {
  // 1. Clean expired device registration links (older than 30 days)
  $stmt = $conn->prepare("
    DELETE FROM device_registration_links 
    WHERE expires_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
  ");
  $stmt->execute();
  $cleaned['expired_links'] = $stmt->affected_rows;
  
  // 2. Clean old activity logs (older than 90 days) - keep recent for monitoring
  if (is_logging_enabled($conn)) {
    $stmt = $conn->prepare("
      DELETE FROM activity_logs 
      WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)
    ");
    $stmt->execute();
    $cleaned['old_activity_logs'] = $stmt->affected_rows;
  }
  
  // 3. Clean old temporary files/logs if any
  // (Add more cleanup tasks as needed)
  
  json_out([
    'status' => 'ok',
    'cleaned' => $cleaned,
    'message' => 'Cleanup voltooid: ' . 
      $cleaned['expired_links'] . ' verlopen links, ' . 
      $cleaned['old_activity_logs'] . ' oude logs verwijderd'
  ]);
  
} catch (Exception $e) {
  error_log("Auto cleanup error: " . $e->getMessage());
  json_out([
    'status' => 'error',
    'message' => 'Cleanup fout: ' . $e->getMessage()
  ], 500);
}
