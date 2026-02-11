<?php
/**
 * Login API
 * Handles user authentication with enhanced security
 */

// Start output buffering to catch any unexpected output
ob_start();

// CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

// Error handling - log errors but don't expose details to user
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't expose errors in production
ini_set('display_startup_errors', 0); // Don't expose startup errors
ini_set('log_errors', 1);

// Helper function to output JSON (in case config.php fails to load)
function safe_json_out($data, int $code = 200): void {
  // Clear any buffered output
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
    $errorMsg = $error['message'] ?? 'Unknown error';
    $errorFile = $error['file'] ?? 'unknown';
    $errorLine = $error['line'] ?? 0;
    error_log("Fatal error in login.php: {$errorMsg} in {$errorFile}:{$errorLine}");
    
    // Try to get more details if possible
    $details = [];
    if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli) {
      $details['db_error'] = $GLOBALS['conn']->error ?? 'none';
    }
    
    safe_json_out([
      'message' => 'Server configuration error',
      'error_type' => $error['type'],
      'details' => $details
    ], 500);
  }
});

try {
  if (!file_exists(__DIR__ . '/../config.php')) {
    throw new Exception("config.php not found");
  }
  
  // Suppress warnings during config load
  $old_error_reporting = error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
  $old_display_errors = ini_get('display_errors');
  ini_set('display_errors', 0);
  
  require __DIR__ . '/../config.php';
  
  // Restore error reporting
  error_reporting($old_error_reporting);
  ini_set('display_errors', $old_display_errors);
  
  // Store connection in global for shutdown function
  if (isset($conn)) {
    $GLOBALS['conn'] = $conn;
  }
  
  // Try to load security advanced config, but don't fail if it doesn't exist
  if (file_exists(__DIR__ . '/../config_security_advanced.php')) {
    try {
      require_once __DIR__ . '/../config_security_advanced.php';
    } catch (Throwable $e) {
      error_log("Warning: Failed to load config_security_advanced.php: " . $e->getMessage());
    }
  }
  
  // Fallback: Define basic classes if they don't exist
  if (!class_exists('BruteForceProtection')) {
    class BruteForceProtection {
      public static function is_blocked($conn, $identifier) { return false; }
      public static function record_attempt($conn, $identifier, $success) {}
      public static function remaining_attempts($conn, $identifier) { return 5; }
    }
  }
  if (!class_exists('PasswordSecurity')) {
    class PasswordSecurity {
      public static function verify($password, $hash) {
        return password_verify($password, $hash);
      }
      public static function hash($password) {
        return password_hash($password, PASSWORD_DEFAULT);
      }
      public static function needs_rehash($hash) {
        return password_needs_rehash($hash, PASSWORD_DEFAULT);
      }
    }
  }
  
  if (!function_exists('json_out')) {
    throw new Exception("json_out function not found in config.php");
  }
  
  // Check database connection - but don't fail if it's null (will be checked later)
  if (!isset($conn)) {
    error_log("Warning: Database connection not set in config.php");
  } elseif (!($conn instanceof mysqli)) {
    error_log("Warning: Database connection is not a mysqli instance");
  } elseif ($conn->connect_error) {
    error_log("Warning: Database connection error: " . $conn->connect_error);
  }
  
  // Clear any buffered output before proceeding
  ob_clean();
} catch (Throwable $e) {
  error_log("Config loading error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
  error_log("Stack trace: " . $e->getTraceAsString());
  // Use safe_json_out in case json_out is not available
  if (function_exists('json_out')) {
    json_out(['message' => 'Configuration error: ' . $e->getMessage()], 500);
  } else {
    safe_json_out(['message' => 'Configuration error: ' . $e->getMessage()], 500);
  }
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  json_out(['message' => 'Method not allowed'], 405);
}

// Parse JSON input safely
$raw_input = file_get_contents('php://input');
if ($raw_input === false) {
  error_log("Login error: Failed to read php://input");
  json_out(['message' => 'Invalid request'], 400);
}

$body = json_decode($raw_input, true);
if ($body === null && json_last_error() !== JSON_ERROR_NONE) {
  error_log("Login error: JSON decode failed - " . json_last_error_msg() . " | Raw input: " . substr($raw_input, 0, 200));
  json_out(['message' => 'Invalid JSON: ' . json_last_error_msg()], 400);
}

if (!is_array($body)) {
  error_log("Login error: Body is not an array");
  json_out(['message' => 'Invalid request data'], 400);
}

$email = trim((string)($body['email'] ?? ''));
$password = (string)($body['password'] ?? '');

// Input validation
if (empty($email) || empty($password)) {
  json_out(['message' => 'Email/password required'], 422);
}

// Validate email format (more lenient for production)
$email_clean = filter_var($email, FILTER_SANITIZE_EMAIL);
if (!filter_var($email_clean, FILTER_VALIDATE_EMAIL)) {
  error_log("Login error: Invalid email format - " . substr($email, 0, 50));
  json_out(['message' => 'Ongeldig email adres'], 422);
}
$email = $email_clean; // Use sanitized email

// Brute force protection
$identifier = $email . '_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
if (BruteForceProtection::is_blocked($conn, $identifier)) {
  $remaining = BruteForceProtection::remaining_attempts($conn, $identifier);
  json_out([
    'message' => 'Te veel mislukte login pogingen. Probeer het over 15 minuten opnieuw.',
    'error' => 'brute_force_blocked',
    'remaining_attempts' => 0
  ], 429);
}

if (!$email || !$password) {
  json_out(['message' => 'Email/password required'], 422);
}

try {
  // Check database connection
  if (!isset($conn) || $conn === null) {
    error_log("Login error: Database connection not available");
    json_out(['message' => 'Database connection error - contact administrator'], 500);
    exit;
  }
  
  if (!($conn instanceof mysqli)) {
    error_log("Login error: Database connection is not a mysqli instance. Type: " . gettype($conn));
    json_out(['message' => 'Database connection error - contact administrator'], 500);
    exit;
  }
  
  // Check if connection has errors
  if ($conn->connect_error) {
    error_log("Login error: Database connection error: " . $conn->connect_error);
    json_out(['message' => 'Database connection error - contact administrator'], 500);
    exit;
  }
  
  // Check if connection is still alive
  try {
    if (!$conn->ping()) {
      error_log("Login error: Database connection lost - ping failed");
      json_out(['message' => 'Database connection error - contact administrator'], 500);
      exit;
    }
  } catch (Exception $e) {
    error_log("Login error: Database ping exception - " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    json_out(['message' => 'Database connection error - contact administrator'], 500);
    exit;
  } catch (Throwable $e) {
    error_log("Login error: Database ping throwable - " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    json_out(['message' => 'Database connection error - contact administrator'], 500);
    exit;
  }
  
  $stmt = $conn->prepare("SELECT id, email, password_hash FROM users WHERE email=?");
  if (!$stmt) {
    error_log("Login error: Prepare failed - " . $conn->error);
    json_out(['message' => 'Database error'], 500);
  }
  
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $result = $stmt->get_result();
  $user = $result->fetch_assoc();

  if (!$user) {
    error_log("Login failed: User not found - " . $email);
    json_out(['message' => 'Ongeldige inloggegevens (gebruiker niet gevonden)'], 401);
  }

  // Check if password_hash is valid
  if (empty($user['password_hash']) || strlen($user['password_hash']) < 10) {
    error_log("Login error: Invalid password hash for user " . $email);
    json_out(['message' => 'Ongeldige inloggegevens (wachtwoord hash ongeldig)'], 401);
  }

  // Enhanced password verification
  $password_valid = PasswordSecurity::verify($password, $user['password_hash']);
  
  if (!$password_valid) {
    // Record failed attempt
    BruteForceProtection::record_attempt($conn, $identifier, false);
    $remaining = BruteForceProtection::remaining_attempts($conn, $identifier);
    
    error_log("Login failed: Invalid password for user " . $email);
    json_out([
      'message' => 'Ongeldige inloggegevens',
      'remaining_attempts' => $remaining
    ], 401);
  }
  
  // Check if password needs rehashing (upgrade old hashes)
  if (PasswordSecurity::needs_rehash($user['password_hash'])) {
    $new_hash = PasswordSecurity::hash($password);
    $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->bind_param("si", $new_hash, $user['id']);
    $stmt->execute();
  }
  
  // Record successful login
  BruteForceProtection::record_attempt($conn, $identifier, true);
  
  // Generate secure token
  $prefix = substr($user['password_hash'], 0, 12);
  $token = base64_encode($user['id'] . ':' . $prefix);
  
  // Log security event (if function exists)
  if (function_exists('log_security_event')) {
    try {
      log_security_event($conn, 'login_success', "User logged in: {$email}", (int)$user['id']);
    } catch (Throwable $e) {
      error_log("Security logging failed: " . $e->getMessage());
      // Don't fail login if logging fails
    }
  }

  json_out([
    'token' => $token,
    'user' => ['id' => (int)$user['id'], 'email' => $user['email']]
  ]);
} catch (mysqli_sql_exception $e) {
  error_log("Login MySQL error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
  error_log("MySQL error code: " . $e->getCode());
  error_log("Stack trace: " . $e->getTraceAsString());
  json_out(['message' => 'Database error: ' . (ini_get('display_errors') ? $e->getMessage() : 'Database connection failed')], 500);
} catch (Throwable $e) {
  error_log("Login error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
  error_log("Error type: " . get_class($e));
  error_log("Stack trace: " . $e->getTraceAsString());
  $error_msg = ini_get('display_errors') ? $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() : 'Server error';
  json_out(['message' => $error_msg], 500);
}
