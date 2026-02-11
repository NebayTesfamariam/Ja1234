<?php
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
    error_log("Fatal error in admin_stats.php: " . $error['message'] . " in " . $error['file'] . ":" . $error['line']);
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
    error_log("Database connection error in admin_stats.php");
    json_out(['message' => 'Database connection error'], 500);
    exit;
  }
  
  $user = require_user($conn);
  
  // Clear any buffered output
  ob_clean();
} catch (Throwable $e) {
  error_log("Config loading error in admin_stats.php: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
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
    error_log("Prepare failed in admin_stats.php: " . $conn->error);
    json_out(['message' => 'Database error'], 500);
  }
  
  $stmt->bind_param("i", $user['id']);
  $stmt->execute();
  $result = $stmt->get_result()->fetch_assoc();
  
  if (!$result || (int)($result['is_admin'] ?? 0) !== 1) {
    json_out(['message' => 'Access denied'], 403);
  }
} catch (Throwable $e) {
  error_log("Admin check error in admin_stats.php: " . $e->getMessage());
  json_out(['message' => 'Database error'], 500);
}

// Get statistics
try {
  $stats = [];

  // Total users
  $result = $conn->query("SELECT COUNT(*) as count FROM users");
  if ($result) {
    $stats['total_users'] = (int)$result->fetch_assoc()['count'];
  } else {
    $stats['total_users'] = 0;
  }

  // Total admins
  $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 1");
  if ($result) {
    $stats['total_admins'] = (int)$result->fetch_assoc()['count'];
  } else {
    $stats['total_admins'] = 0;
  }

  // Total devices
  $result = $conn->query("SELECT COUNT(*) as count FROM devices");
  if ($result) {
    $stats['total_devices'] = (int)$result->fetch_assoc()['count'];
  } else {
    $stats['total_devices'] = 0;
  }

  // Active devices
  $result = $conn->query("SELECT COUNT(*) as count FROM devices WHERE status = 'active'");
  if ($result) {
    $stats['active_devices'] = (int)$result->fetch_assoc()['count'];
  } else {
    $stats['active_devices'] = 0;
  }

  // Total whitelist entries
  $result = $conn->query("SELECT COUNT(*) as count FROM whitelist");
  if ($result) {
    $stats['total_whitelist'] = (int)$result->fetch_assoc()['count'];
  } else {
    $stats['total_whitelist'] = 0;
  }

  // Enabled whitelist entries
  $result = $conn->query("SELECT COUNT(*) as count FROM whitelist WHERE enabled = 1");
  if ($result) {
    $stats['enabled_whitelist'] = (int)$result->fetch_assoc()['count'];
  } else {
    $stats['enabled_whitelist'] = 0;
  }

  // Recent users (last 7 days)
  $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
  if ($result) {
    $stats['recent_users'] = (int)$result->fetch_assoc()['count'];
  } else {
    $stats['recent_users'] = 0;
  }

  // Recent devices (last 7 days)
  $result = $conn->query("SELECT COUNT(*) as count FROM devices WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
  if ($result) {
    $stats['recent_devices'] = (int)$result->fetch_assoc()['count'];
  } else {
    $stats['recent_devices'] = 0;
  }

  json_out(['stats' => $stats]);
} catch (Throwable $e) {
  error_log("Stats query error in admin_stats.php: " . $e->getMessage());
  json_out(['message' => 'Database error', 'stats' => []], 500);
}

