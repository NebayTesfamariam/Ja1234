<?php

declare(strict_types=1);

/* =====================
   CORS
===================== */
// Only set headers when running in web context (not CLI)
if (php_sapi_name() !== 'cli') {
  header("Access-Control-Allow-Origin: *");
  header("Access-Control-Allow-Headers: Content-Type, Authorization");
  header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");

  if (($_SERVER['REQUEST_METHOD'] ?? null) === 'OPTIONS') {
    http_response_code(204);
    exit;
  }
}

/* =====================
   ENV DETECTION (MUST BE FIRST)
===================== */
$is_production = (
  isset($_SERVER['HTTP_HOST']) &&
  (strpos($_SERVER['HTTP_HOST'], 'ja1234.com') !== false)
) || getenv('ENVIRONMENT') === 'production';

/* =====================
   PROFESSIONAL FEATURES
===================== */
// Load security configuration (optional)
if (file_exists(__DIR__ . '/config_security.php')) {
  try {
    require_once __DIR__ . '/config_security.php';
  } catch (Throwable $e) {
    error_log("Warning: Failed to load config_security.php: " . $e->getMessage());
  }
}

// Load advanced security features (optional)
if (file_exists(__DIR__ . '/config_security_advanced.php')) {
  try {
    require_once __DIR__ . '/config_security_advanced.php';
  } catch (Throwable $e) {
    error_log("Warning: Failed to load config_security_advanced.php: " . $e->getMessage());
  }
}

// Load validation helpers (optional)
if (file_exists(__DIR__ . '/config_validation.php')) {
  try {
    require_once __DIR__ . '/config_validation.php';
  } catch (Throwable $e) {
    error_log("Warning: Failed to load config_validation.php: " . $e->getMessage());
  }
}

// Load logging system (optional)
if (file_exists(__DIR__ . '/config_logging.php')) {
  try {
    require_once __DIR__ . '/config_logging.php';
  } catch (Throwable $e) {
    error_log("Warning: Failed to load config_logging.php: " . $e->getMessage());
  }
}

/* =====================
   MYSQL ERROR MODE
===================== */
// Only enable strict reporting if not in production (to prevent fatal errors)
if (!$is_production) {
  mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
} else {
  // In production, use less strict reporting to prevent fatal errors
  mysqli_report(MYSQLI_REPORT_OFF);
}

/* =====================
   DATABASE CONFIG
===================== */
if ($is_production) {
  // Production database settings
  // Check if config_production.php exists and load constants from it
  if (file_exists(__DIR__ . '/config_production.php')) {
    try {
      // Only load constants, not the database connection
      // config_production.php might try to create its own $conn which conflicts
      $prod_config_content = @file_get_contents(__DIR__ . '/config_production.php');
      if ($prod_config_content !== false) {
        // Extract constants using regex (simple approach)
        if (preg_match("/define\s*\(\s*['\"]PROD_DB_HOST['\"]\s*,\s*['\"]([^'\"]+)['\"]/", $prod_config_content, $matches)) {
          $DB_HOST = $matches[1];
        }
        if (preg_match("/define\s*\(\s*['\"]PROD_DB_USER['\"]\s*,\s*['\"]([^'\"]+)['\"]/", $prod_config_content, $matches)) {
          $DB_USER = $matches[1];
        }
        if (preg_match("/define\s*\(\s*['\"]PROD_DB_PASS['\"]\s*,\s*['\"]([^'\"]+)['\"]/", $prod_config_content, $matches)) {
          $DB_PASS = $matches[1];
        }
        if (preg_match("/define\s*\(\s*['\"]PROD_DB_NAME['\"]\s*,\s*['\"]([^'\"]+)['\"]/", $prod_config_content, $matches)) {
          $DB_NAME = $matches[1];
        }
      }
    } catch (Throwable $e) {
      error_log("Warning: Failed to parse config_production.php: " . $e->getMessage());
    }
  }
  
  // Fallback to default production settings if not set
  if (!isset($DB_HOST)) $DB_HOST = "localhost";
  if (!isset($DB_USER)) $DB_USER = "u402299403_nebaytes";
  if (!isset($DB_PASS)) $DB_PASS = "JE_ECHTE_DB_WACHTWOORD"; // 🔴 VERPLICHT - Update this!
  if (!isset($DB_NAME)) $DB_NAME = "u402299403_ja1234";
} else {
  // Development: env vars override defaults (Kubuntu/Ubuntu often use a dedicated DB user)
  $DB_HOST = getenv('DB_HOST') ?: "localhost";
  $DB_USER = getenv('DB_USER') ?: "root";
  $DB_PASS = getenv('DB_PASS') ?: "";
  $DB_NAME = getenv('DB_NAME') ?: "pornfree";
}

/* =====================
   DB CONNECT
===================== */
$conn = null;
$db_error = null;

try {
  // Ensure variables are set
  if (!isset($DB_HOST)) $DB_HOST = "localhost";
  if (!isset($DB_USER)) $DB_USER = "root";
  if (!isset($DB_PASS)) $DB_PASS = "";
  if (!isset($DB_NAME)) $DB_NAME = "pornfree";
  
  // Production servers typically use TCP/IP, not sockets
  if ($is_production) {
    // Production: Use TCP/IP connection (port 3306)
    $conn = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, 3306);
  } else {
    // Development: Try socket first (XAMPP/macOS or Linux), then TCP/IP
    $socket_paths = [
      "/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock", // XAMPP macOS
      "/var/run/mysqld/mysqld.sock",                         // Linux
      "/tmp/mysql.sock",                                     // Some Linux/macOS
    ];
    $socket_found = null;
    foreach ($socket_paths as $path) {
      if (file_exists($path)) {
        $socket_found = $path;
        break;
      }
    }
    if ($socket_found) {
      $conn = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, null, $socket_found);
    } else {
      // Fallback to TCP/IP (works on Windows, WSL, and when socket not found)
      $conn = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    }
  }
  
  // Check for connection errors
  if ($conn && $conn->connect_error) {
    $db_error = $conn->connect_error;
    $conn = null;
  }
  
  if ($conn) {
    $conn->set_charset("utf8mb4");
  }
} catch (Throwable $e) {
  $db_error = $e->getMessage();
  $conn = null;
  
  // Try TCP/IP connection as fallback (for production)
  if ($is_production) {
    try {
      $conn = @new mysqli("127.0.0.1", $DB_USER, $DB_PASS, $DB_NAME, 3306);
      if ($conn && $conn->connect_error) {
        $db_error = $conn->connect_error;
        $conn = null;
      }
      if ($conn) {
        $conn->set_charset("utf8mb4");
      }
    } catch (Throwable $e2) {
      $db_error = $e2->getMessage();
      $conn = null;
    }
  }
}

// Log database connection errors (but don't crash immediately)
// Let the API endpoints handle the error gracefully
if ($conn === null && $db_error) {
  error_log("Database connection failed: " . $db_error);
  // Don't output JSON here - let the API endpoints handle it
  // This allows the config to load even if DB is down
}

// Ensure $conn is set (even if null)
if (!isset($conn)) {
  $conn = null;
}

/* =====================
   HELPERS
===================== */
function json_out($data, int $code = 200): void
{
  http_response_code($code);
  header("Content-Type: application/json; charset=utf-8");
  echo json_encode($data);
  exit;
}

/**
 * Normalize domain - remove http/https, paths, www
 * Returns clean domain like "wikipedia.org"
 */
function normalize_domain(string $domain): string
{
  $d = strtolower(trim($domain));
  // Remove http:// or https://
  $d = preg_replace('#^https?://#', '', $d);
  // Remove path and query string
  $d = explode('/', $d)[0];
  $d = explode('?', $d)[0];
  // Remove www. prefix
  $d = ltrim($d, 'www.');
  // Remove trailing dots
  $d = trim($d, '.');
  
  return $d;
}

function get_bearer_token(): ?string
{
  // Try getallheaders() first (if available)
  $auth = '';
  if (function_exists('getallheaders')) {
    $headers = getallheaders();
    if ($headers) {
      $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }
  }

  // Fallback to $_SERVER if getallheaders() not available or didn't find auth
  if (empty($auth)) {
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
  }

  if (empty($auth)) {
    return null;
  }

  if (preg_match('/Bearer\s+(\S+)/i', $auth, $matches)) {
    return trim($matches[1]);
  }

  return null;
}

// Token authentication
function require_user(mysqli $conn): array
{
  $token = get_bearer_token();
  if (!$token) json_out(['message' => 'Missing token'], 401);

  $decoded = base64_decode($token, true);
  if (!$decoded || strpos($decoded, ':') === false) json_out(['message' => 'Invalid token'], 401);

  [$userId, $hashPrefix] = explode(':', $decoded, 2);
  $userId = (int)$userId;

  $stmt = $conn->prepare("SELECT id, email, password_hash FROM users WHERE id=?");
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $user = $stmt->get_result()->fetch_assoc();

  if (!$user) json_out(['message' => 'Invalid token'], 401);
  if (strpos($user['password_hash'], $hashPrefix) !== 0) json_out(['message' => 'Invalid token'], 401);

  return $user;
}

/**
 * Automatisch controleren en blokkeren van devices bij verlopen abonnementen
 * Wordt automatisch aangeroepen om ervoor te zorgen dat devices geblokkeerd worden
 */
function auto_check_expired_subscriptions(mysqli $conn): void
{
  // Early return if connection is invalid
  if (!$conn || !($conn instanceof mysqli) || $conn->connect_error) {
    return;
  }
  
  static $last_check = 0;
  $now = time();

  // Check elke 60 seconden (niet bij elke request om performance te behouden)
  if ($now - $last_check < 60) {
    return;
  }
  $last_check = $now;

  try {
    // Find all expired subscriptions
    $stmt = $conn->prepare("
      SELECT s.id, s.user_id, s.plan, s.end_date
      FROM subscriptions s
      WHERE s.status = 'active'
        AND s.end_date < CURDATE()
      LIMIT 50
    ");
    $stmt->execute();
    $expired_subs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($expired_subs as $sub) {
      $user_id = (int)$sub['user_id'];
      $sub_id = (int)$sub['id'];

      // Mark subscription as expired
      $stmt = $conn->prepare("UPDATE subscriptions SET status = 'expired' WHERE id = ?");
      $stmt->bind_param("i", $sub_id);
      $stmt->execute();

      // Block ALL devices for this user (but NOT admin_created and NOT permanent_blocked)
      // Admin-created devices zijn ALTIJD actief - kunnen NOOIT worden geblokkeerd
      $stmt = $conn->prepare("
        UPDATE devices 
        SET status = 'blocked'
        WHERE user_id = ? 
          AND status != 'blocked'
          AND permanent_blocked = 0
          AND admin_created = 0
      ");
      $stmt->bind_param("i", $user_id);
      $stmt->execute();

      // Ensure admin_created devices are ALWAYS active (redundant but safe)
      $stmt = $conn->prepare("UPDATE devices SET status = 'active' WHERE user_id = ? AND admin_created = 1");
      $stmt->bind_param("i", $user_id);
      $stmt->execute();
    }

    // Also check if subscriptions became active again - unblock devices automatically
    $stmt = $conn->prepare("
      SELECT DISTINCT d.id, d.user_id
      FROM devices d
      INNER JOIN subscriptions s ON s.user_id = d.user_id
      WHERE d.status = 'blocked'
        AND d.permanent_blocked = 0
        AND d.admin_created = 0
        AND s.status = 'active'
        AND s.start_date <= CURDATE()
        AND s.end_date >= CURDATE()
      LIMIT 50
    ");
    $stmt->execute();
    $devices_to_unblock = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($devices_to_unblock as $device) {
      $device_id = (int)$device['id'];
      // AUTOMATISCH: Unblock device - subscription is active again
      $stmt = $conn->prepare("UPDATE devices SET status = 'active' WHERE id = ? AND permanent_blocked = 0 AND admin_created = 0");
      $stmt->bind_param("i", $device_id);
      $stmt->execute();
    }

    // AUTOMATISCH: Ensure ALL admin_created devices are ALWAYS active (redundant but safe)
    $stmt = $conn->prepare("UPDATE devices SET status = 'active' WHERE admin_created = 1 AND status != 'active'");
    $stmt->execute();

    // AUTOMATISCH: Ensure devices with active subscriptions are ALWAYS active
    // This ensures devices via abonnement automatically work
    $stmt = $conn->prepare("
      UPDATE devices d
      INNER JOIN subscriptions s ON s.user_id = d.user_id
      SET d.status = 'active'
      WHERE d.status != 'active'
        AND d.permanent_blocked = 0
        AND d.admin_created = 0
        AND s.status = 'active'
        AND s.start_date <= CURDATE()
        AND s.end_date >= CURDATE()
      LIMIT 50
    ");
    $stmt->execute();
  } catch (Exception $e) {
    // Silent fail - don't break the request if this check fails
    error_log("Error in auto_check_expired_subscriptions: " . $e->getMessage());
  }
}

// Automatisch controleren bij elke request (lightweight check)
// Only run if database connection is available
if ($conn !== null && $conn instanceof mysqli && !$conn->connect_error) {
  try {
    auto_check_expired_subscriptions($conn);
  } catch (Throwable $e) {
    // Silent fail - don't break the request if this check fails
    error_log("Error calling auto_check_expired_subscriptions: " . $e->getMessage());
  }
}
