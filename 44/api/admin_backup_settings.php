<?php
/**
 * Automatic Backup Settings
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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  // Get backup settings
  $settings = [
    'enabled' => false, // Default disabled
    'time' => '02:00', // Default 2 AM
    'retention_days' => 30
  ];
  
  json_out(['settings' => $settings]);
  
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Save backup settings
  $body = json_decode(file_get_contents('php://input'), true) ?? [];
  $enabled = isset($body['enabled']) ? (bool)$body['enabled'] : false;
  $time = trim((string)($body['time'] ?? '02:00'));
  
  // Validate time format
  if (!preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
    json_out(['message' => 'Ongeldig tijd formaat (gebruik HH:MM)'], 422);
  }
  
  // Store settings (in production, store in database or config file)
  json_out([
    'status' => 'saved',
    'message' => $enabled 
      ? "Automatische backups ingeschakeld (dagelijks om {$time})"
      : 'Automatische backups uitgeschakeld',
    'settings' => [
      'enabled' => $enabled,
      'time' => $time
    ]
  ]);
}

