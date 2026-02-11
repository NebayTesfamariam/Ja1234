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
    error_log("Fatal error in admin_check.php: " . $error['message'] . " in " . $error['file'] . ":" . $error['line']);
    safe_json_out(['message' => 'Server configuration error'], 500);
  }
});

try {
  require __DIR__ . '/../config.php';
  
  if (!function_exists('get_bearer_token')) {
    throw new Exception("get_bearer_token function not found");
  }
  
  if (!function_exists('require_user')) {
    throw new Exception("require_user function not found");
  }
  
  if (!function_exists('json_out')) {
    throw new Exception("json_out function not found");
  }
  
  // Clear any buffered output
  ob_clean();
} catch (Throwable $e) {
  error_log("Config loading error in admin_check.php: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
  if (function_exists('json_out')) {
    json_out(['message' => 'Configuration error: ' . $e->getMessage()], 500);
  } else {
    safe_json_out(['message' => 'Configuration error'], 500);
  }
}

// Check if token is provided
$token = get_bearer_token();
if (!$token) {
  json_out([
    'message' => 'Authentication required',
    'is_admin' => false,
    'error' => 'not_authenticated'
  ], 401);
}

// Get user (require_user will handle token validation)
$user = require_user($conn);

// Check if user is admin
$stmt = $conn->prepare("SELECT is_admin FROM users WHERE id=?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

// Properly check is_admin (must be 1, not just truthy)
if (!$result || (int)($result['is_admin'] ?? 0) !== 1) {
  // Log unauthorized admin access attempt (if logging function exists)
  // Make this completely optional to prevent 500 errors
  try {
    if (file_exists(__DIR__ . '/../config_security_advanced.php')) {
      require_once __DIR__ . '/../config_security_advanced.php';
      if (function_exists('log_security_event')) {
        try {
          log_security_event($conn, 'unauthorized_admin_access', "Unauthorized admin access attempt", (int)$user['id']);
        } catch (Throwable $e) {
          error_log("Security logging error: " . $e->getMessage());
          // Don't fail - just log the error
        }
      }
    }
  } catch (Throwable $e) {
    error_log("Security config loading error: " . $e->getMessage());
    // Don't fail - continue without logging
  }
  
  json_out([
    'message' => 'Access denied - admin only',
    'is_admin' => false,
    'user_id' => (int)$user['id'],
    'user_email' => $user['email'],
    'is_admin_value' => $result['is_admin'] ?? null
  ], 403);
}

// IP Whitelist check for admin (if enabled)
// Only check if config_security_advanced.php exists and IPWhitelist class is available
// Make this completely optional to prevent 500 errors
try {
  if (file_exists(__DIR__ . '/../config_security_advanced.php')) {
    require_once __DIR__ . '/../config_security_advanced.php';
    if (class_exists('IPWhitelist')) {
      $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
      try {
        if (!IPWhitelist::is_allowed($conn, $ip, (int)$user['id'])) {
          if (function_exists('log_security_event')) {
            try {
              log_security_event($conn, 'ip_not_whitelisted', "Admin access denied - IP not whitelisted: {$ip}", (int)$user['id']);
            } catch (Throwable $e) {
              error_log("Security logging error: " . $e->getMessage());
            }
          }
          json_out([
            'message' => 'Access denied - IP address not whitelisted',
            'is_admin' => false,
            'error' => 'ip_not_whitelisted'
          ], 403);
        }
      } catch (Throwable $e) {
        // IP whitelist check failed - log but don't block access
        error_log("IP whitelist check error: " . $e->getMessage());
        // Continue with admin access
      }
    }
  }
} catch (Throwable $e) {
  error_log("IP whitelist config loading error: " . $e->getMessage());
  // Don't fail - continue without IP whitelist check
}

json_out([
  'is_admin' => true,
  'user' => [
    'id' => (int)$user['id'], 
    'email' => $user['email'],
    'is_admin' => true
  ]
]);

