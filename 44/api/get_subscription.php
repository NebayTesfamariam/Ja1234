<?php
require __DIR__ . '/../config.php';
$user = require_user($conn);

// Get user's active subscription
// Check start_date <= today so it works immediately when created
try {
  $stmt = $conn->prepare("
    SELECT s.*, p.max_devices, p.name as plan_name, p.description
    FROM subscriptions s
    LEFT JOIN subscription_plans p ON p.name = s.plan
    WHERE s.user_id = ? 
      AND s.status = 'active' 
      AND s.start_date <= CURDATE()
      AND s.end_date >= CURDATE()
    ORDER BY s.created_at DESC
    LIMIT 1
  ");
  if (!$stmt) {
    throw new Exception("Database error: " . $conn->error);
  }
  $stmt->bind_param("i", $user['id']);
  $stmt->execute();
  $subscription = $stmt->get_result()->fetch_assoc();
} catch (Exception $e) {
  // If subscription_plans table doesn't exist, return empty subscription
  json_out([
    'subscription' => null,
    'device_count' => 0,
    'has_active_subscription' => false,
    'error' => 'Subscription system not initialized. Please run setup_subscriptions.php'
  ]);
  exit;
}

// Count user's devices
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM devices WHERE user_id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$device_count = (int)$stmt->get_result()->fetch_assoc()['count'];

if ($subscription) {
  $subscription['id'] = (int)$subscription['id'];
  $subscription['max_devices'] = (int)$subscription['max_devices'];
  $subscription['device_count'] = $device_count;
  $subscription['can_add_device'] = $device_count < $subscription['max_devices'];
}

json_out([
  'subscription' => $subscription,
  'device_count' => $device_count,
  'has_active_subscription' => $subscription !== null
]);

