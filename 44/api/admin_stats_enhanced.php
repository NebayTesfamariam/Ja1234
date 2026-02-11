<?php
/**
 * Enhanced Admin Statistics
 * Provides detailed statistics for professional dashboard
 */

// Start output buffering
ob_start();

// CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't expose errors in production
ini_set('log_errors', 1);

// Helper function to output JSON (in case config.php fails to load)
function safe_json_out($data, int $code = 200): void {
  ob_clean();
  http_response_code($code);
  header('Content-Type: application/json');
  echo json_encode($data);
  ob_end_flush();
  exit;
}

// Catch fatal errors
register_shutdown_function(function() {
  $error = error_get_last();
  if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
    error_log("Fatal error in admin_stats_enhanced.php: " . $error['message'] . " in " . $error['file'] . ":" . $error['line']);
    safe_json_out(['message' => 'Server configuration error'], 500);
  }
});

try {
  require __DIR__ . '/../config.php';
  
  if (!function_exists('require_user')) {
    throw new Exception("require_user function not found");
  }
  
  if (!function_exists('json_out')) {
    throw new Exception("json_out function not found");
  }
  
  // Check database connection before proceeding
  if (!isset($conn) || $conn === null || !($conn instanceof mysqli) || $conn->connect_error) {
    error_log("Database connection error in admin_stats_enhanced.php");
    json_out(['message' => 'Database connection error'], 500);
    exit;
  }
  
  $user = require_user($conn);
  
  // Clear any buffered output
  ob_clean();
} catch (Throwable $e) {
  error_log("Config loading error in admin_stats_enhanced.php: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
  if (function_exists('json_out')) {
    json_out(['message' => 'Configuration error: ' . $e->getMessage()], 500);
  } else {
    safe_json_out(['message' => 'Configuration error'], 500);
  }
  exit;
}

// Check admin
try {
  $stmt = $conn->prepare("SELECT is_admin FROM users WHERE id=?");
  if (!$stmt) {
    error_log("Prepare failed in admin_stats_enhanced.php: " . $conn->error);
    json_out(['message' => 'Database error'], 500);
  }
  
  $stmt->bind_param("i", $user['id']);
  $stmt->execute();
  $result = $stmt->get_result()->fetch_assoc();
  
  if (!$result || (int)($result['is_admin'] ?? 0) !== 1) {
    json_out(['message' => 'Access denied'], 403);
  }
} catch (Throwable $e) {
  error_log("Admin check error in admin_stats_enhanced.php: " . $e->getMessage());
  json_out(['message' => 'Database error'], 500);
}

$stats = [];

try {
  // Basic stats
  $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
  $stats['total_users'] = $stmt ? (int)$stmt->fetch_assoc()['count'] : 0;

  $stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 1");
  $stats['total_admins'] = $stmt ? (int)$stmt->fetch_assoc()['count'] : 0;

  $stmt = $conn->query("SELECT COUNT(*) as count FROM devices");
  $stats['total_devices'] = $stmt ? (int)$stmt->fetch_assoc()['count'] : 0;

  $stmt = $conn->query("SELECT COUNT(*) as count FROM devices WHERE status = 'active'");
  $stats['active_devices'] = $stmt ? (int)$stmt->fetch_assoc()['count'] : 0;

  // Subscription stats (table might not exist)
  try {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM subscriptions WHERE status = 'active'");
    $stats['active_subscriptions'] = $stmt ? (int)$stmt->fetch_assoc()['count'] : 0;

    $stmt = $conn->query("SELECT COUNT(*) as count FROM subscriptions WHERE status = 'expired'");
    $stats['expired_subscriptions'] = $stmt ? (int)$stmt->fetch_assoc()['count'] : 0;
  } catch (Exception $e) {
    $stats['active_subscriptions'] = 0;
    $stats['expired_subscriptions'] = 0;
  }

  // New users today
  $stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()");
  $stats['new_users_today'] = $stmt ? (int)$stmt->fetch_assoc()['count'] : 0;

  // Activity logs (table might not exist)
  try {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM activity_logs WHERE action = 'blocked' AND DATE(created_at) = CURDATE()");
    $stats['blocked_requests_today'] = $stmt ? (int)$stmt->fetch_assoc()['count'] : 0;

    $stmt = $conn->query("SELECT COUNT(*) as count FROM activity_logs");
    $stats['total_logs'] = $stmt ? (int)$stmt->fetch_assoc()['count'] : 0;

    $stmt = $conn->query("SELECT COUNT(*) as count FROM activity_logs WHERE DATE(created_at) = CURDATE()");
    $stats['logs_today'] = $stmt ? (int)$stmt->fetch_assoc()['count'] : 0;

    // Activity data for last 7 days
    $activity_data = [];
    $activity_labels = [];
    for ($i = 6; $i >= 0; $i--) {
      $date = date('Y-m-d', strtotime("-{$i} days"));
      $day_name = date('D', strtotime($date));
      $activity_labels[] = $day_name;
      
      $stmt = $conn->prepare("SELECT COUNT(*) as count FROM activity_logs WHERE action = 'blocked' AND DATE(created_at) = ?");
      $stmt->bind_param("s", $date);
      $stmt->execute();
      $result = $stmt->get_result()->fetch_assoc();
      $activity_data[] = (int)($result['count'] ?? 0);
    }
    $stats['activity_labels'] = $activity_labels;
    $stats['activity_data'] = $activity_data;
  } catch (Exception $e) {
    $stats['blocked_requests_today'] = 0;
    $stats['total_logs'] = 0;
    $stats['logs_today'] = 0;
    $stats['activity_labels'] = ['Ma', 'Di', 'Wo', 'Do', 'Vr', 'Za', 'Zo'];
    $stats['activity_data'] = [0, 0, 0, 0, 0, 0, 0];
  }

  // Whitelist count
  $stmt = $conn->query("SELECT COUNT(*) as count FROM whitelist");
  $stats['whitelist_count'] = $stmt ? (int)$stmt->fetch_assoc()['count'] : 0;

  // Device status breakdown
  try {
    $stmt = $conn->query("SELECT status, COUNT(*) as count FROM devices GROUP BY status");
    $device_status = [];
    if ($stmt) {
      while ($row = $stmt->fetch_assoc()) {
        $device_status[$row['status']] = (int)$row['count'];
      }
    }
    $stats['device_status'] = $device_status;
  } catch (Exception $e) {
    $stats['device_status'] = [];
  }

  // Subscription plan breakdown (table might not exist)
  try {
    $stmt = $conn->query("SELECT plan, COUNT(*) as count FROM subscriptions WHERE status = 'active' GROUP BY plan");
    $subscription_plans = [];
    if ($stmt) {
      while ($row = $stmt->fetch_assoc()) {
        $subscription_plans[$row['plan']] = (int)$row['count'];
      }
    }
    $stats['subscription_plans'] = $subscription_plans;
  } catch (Exception $e) {
    $stats['subscription_plans'] = [];
  }

  json_out(['stats' => $stats]);
} catch (Throwable $e) {
  error_log("Admin stats enhanced error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
  json_out(['message' => 'Error loading statistics', 'stats' => []], 500);
}
