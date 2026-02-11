<?php
/**
 * Check expired subscriptions and block all devices
 * This should be called periodically (cronjob) or on each request
 * 
 * REGELS:
 * - Als abonnement stopt/verloopt → alle devices van die gebruiker worden automatisch geblokkeerd
 * - Deze devices kunnen NOOIT worden deblokkeerd (permanent_blocked = 1)
 */
require __DIR__ . '/../config.php';

// Find all expired subscriptions
$stmt = $conn->prepare("
  SELECT s.id, s.user_id, s.plan, s.end_date
  FROM subscriptions s
  WHERE s.status = 'active'
    AND s.end_date < CURDATE()
");
$stmt->execute();
$expired_subs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$blocked_devices = 0;
$updated_subscriptions = 0;

foreach ($expired_subs as $sub) {
  $user_id = (int)$sub['user_id'];
  $sub_id = (int)$sub['id'];
  
  // Mark subscription as expired
  $stmt = $conn->prepare("UPDATE subscriptions SET status = 'expired' WHERE id = ?");
  $stmt->bind_param("i", $sub_id);
  $stmt->execute();
  $updated_subscriptions++;
  
  // Block ALL devices for this user (but NOT permanent_blocked - can be unblocked when subscription reactivates)
  // Only block if not already permanent_blocked by admin
  $stmt = $conn->prepare("
    UPDATE devices 
    SET status = 'blocked'
    WHERE user_id = ? 
      AND status != 'blocked'
      AND permanent_blocked = 0
  ");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $blocked_devices += $stmt->affected_rows;
}

// Also check subscriptions that are cancelled
$stmt = $conn->prepare("
  SELECT DISTINCT user_id
  FROM subscriptions
  WHERE status = 'cancelled'
    AND user_id NOT IN (
      SELECT DISTINCT user_id 
      FROM subscriptions 
      WHERE status = 'active' 
        AND start_date <= CURDATE() 
        AND end_date >= CURDATE()
    )
");
$stmt->execute();
$cancelled_users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

foreach ($cancelled_users as $cancelled) {
  $user_id = (int)$cancelled['user_id'];
  
  // Block ALL devices for this user (but NOT permanent_blocked - can be unblocked when subscription reactivates)
  // Only block if not already permanent_blocked by admin
  $stmt = $conn->prepare("
    UPDATE devices 
    SET status = 'blocked'
    WHERE user_id = ? 
      AND status != 'blocked'
      AND permanent_blocked = 0
  ");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $blocked_devices += $stmt->affected_rows;
}

// Check if subscriptions became active again - unblock devices automatically
// Only unblock devices that are NOT permanent_blocked by admin
$stmt = $conn->prepare("
  SELECT DISTINCT d.id, d.user_id
  FROM devices d
  INNER JOIN subscriptions s ON s.user_id = d.user_id
  WHERE d.status = 'blocked'
    AND d.permanent_blocked = 0
    AND s.status = 'active'
    AND s.start_date <= CURDATE()
    AND s.end_date >= CURDATE()
");
$stmt->execute();
$devices_to_unblock = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$unblocked_devices = 0;
foreach ($devices_to_unblock as $device) {
  $device_id = (int)$device['id'];
  // Unblock device - subscription is active again
  $stmt = $conn->prepare("UPDATE devices SET status = 'active' WHERE id = ? AND permanent_blocked = 0");
  $stmt->bind_param("i", $device_id);
  $stmt->execute();
  $unblocked_devices += $stmt->affected_rows;
}

json_out([
  'status' => 'ok',
  'expired_subscriptions' => count($expired_subs),
  'updated_subscriptions' => $updated_subscriptions,
  'blocked_devices' => $blocked_devices,
  'unblocked_devices' => $unblocked_devices,
  'message' => "Gecontroleerd: {$updated_subscriptions} abonnement(en) verlopen ({$blocked_devices} devices geblokkeerd), {$unblocked_devices} device(s) weer actief"
]);

