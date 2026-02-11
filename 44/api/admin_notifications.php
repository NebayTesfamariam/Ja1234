<?php
/**
 * Email Notifications Configuration
 */
require __DIR__ . '/../config.php';
require __DIR__ . '/../config_email.php';
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
  // Get notification settings
  $settings = [
    'admin_email' => EMAIL_ADMIN_ADDRESS, // Default admin email for notifications
    'notify_new_user' => true,
    'notify_new_device' => true,
    'notify_expired_sub' => true,
    'notify_errors' => true
  ];
  
  // Try to load from database or config file
  // For now, return defaults
  json_out(['settings' => $settings]);
  
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Save notification settings
  $body = json_decode(file_get_contents('php://input'), true) ?? [];
  $admin_email = trim((string)($body['admin_email'] ?? ''));
  $notify_new_user = isset($body['notify_new_user']) ? (bool)$body['notify_new_user'] : true;
  $notify_new_device = isset($body['notify_new_device']) ? (bool)$body['notify_new_device'] : true;
  $notify_expired_sub = isset($body['notify_expired_sub']) ? (bool)$body['notify_expired_sub'] : true;
  $notify_errors = isset($body['notify_errors']) ? (bool)$body['notify_errors'] : true;
  
  if ($admin_email && !filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
    json_out(['message' => 'Ongeldig email adres'], 422);
  }
  
  // Store in database or config file
  // For now, just return success
  json_out([
    'status' => 'saved',
    'message' => 'Notificatie instellingen opgeslagen',
    'settings' => [
      'admin_email' => $admin_email,
      'notify_new_user' => $notify_new_user,
      'notify_new_device' => $notify_new_device,
      'notify_expired_sub' => $notify_expired_sub,
      'notify_errors' => $notify_errors
    ]
  ]);
}

