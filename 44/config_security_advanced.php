<?php

/**
 * Advanced Security Features
 * Enhanced security for production systems
 */

/**
 * Brute Force Protection
 */
class BruteForceProtection
{
  private static $max_attempts = 5;
  private static $lockout_duration = 900; // 15 minutes
  private static $attempts_table = 'login_attempts';

  /**
   * Check if IP is blocked
   */
  public static function is_blocked(mysqli $conn, string $identifier): bool
  {
    try {
      // Create table if it doesn't exist
      self::create_table($conn);

      $stmt = $conn->prepare("
        SELECT COUNT(*) as attempts, MAX(attempted_at) as last_attempt
        FROM " . self::$attempts_table . "
        WHERE identifier = ? 
          AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
      ");
      $window = self::$lockout_duration;
      $stmt->bind_param("si", $identifier, $window);
      $stmt->execute();
      $result = $stmt->get_result()->fetch_assoc();

      if ($result && (int)$result['attempts'] >= self::$max_attempts) {
        return true;
      }

      return false;
    } catch (Exception $e) {
      error_log("Brute force check error: " . $e->getMessage());
      return false; // Fail open for availability
    }
  }

  /**
   * Record failed attempt
   */
  public static function record_attempt(mysqli $conn, string $identifier, bool $success = false): void
  {
    try {
      self::create_table($conn);

      if ($success) {
        // Clear attempts on successful login
        $stmt = $conn->prepare("DELETE FROM " . self::$attempts_table . " WHERE identifier = ?");
        $stmt->bind_param("s", $identifier);
        $stmt->execute();
      } else {
        // Record failed attempt
        $stmt = $conn->prepare("
          INSERT INTO " . self::$attempts_table . " (identifier, ip_address, user_agent, attempted_at)
          VALUES (?, ?, ?, NOW())
        ");
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $stmt->bind_param("sss", $identifier, $ip, $user_agent);
        $stmt->execute();
      }
    } catch (Exception $e) {
      error_log("Brute force record error: " . $e->getMessage());
    }
  }

  /**
   * Get remaining attempts
   */
  public static function remaining_attempts(mysqli $conn, string $identifier): int
  {
    try {
      self::create_table($conn);

      $stmt = $conn->prepare("
        SELECT COUNT(*) as attempts
        FROM " . self::$attempts_table . "
        WHERE identifier = ? 
          AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
      ");
      $window = self::$lockout_duration;
      $stmt->bind_param("si", $identifier, $window);
      $stmt->execute();
      $result = $stmt->get_result()->fetch_assoc();

      $attempts = (int)($result['attempts'] ?? 0);
      return max(0, self::$max_attempts - $attempts);
    } catch (Exception $e) {
      return self::$max_attempts; // Fail open
    }
  }

  /**
   * Create attempts table
   */
  private static function create_table(mysqli $conn): void
  {
    $conn->query("
      CREATE TABLE IF NOT EXISTS `" . self::$attempts_table . "` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `identifier` VARCHAR(255) NOT NULL,
        `ip_address` VARCHAR(45) DEFAULT NULL,
        `user_agent` TEXT DEFAULT NULL,
        `attempted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_identifier` (`identifier`),
        INDEX `idx_attempted_at` (`attempted_at`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
  }

  /**
   * Clean old attempts
   */
  public static function cleanup(mysqli $conn): void
  {
    try {
      self::create_table($conn);
      $conn->query("DELETE FROM " . self::$attempts_table . " WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    } catch (Exception $e) {
      error_log("Brute force cleanup error: " . $e->getMessage());
    }
  }
}

/**
 * Session Security
 */
class SecureSession
{
  /**
   * Start secure session
   */
  public static function start(): void
  {
    if (session_status() === PHP_SESSION_NONE) {
      // Secure session configuration
      ini_set('session.cookie_httponly', '1');
      ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? '1' : '0');
      ini_set('session.cookie_samesite', 'Strict');
      ini_set('session.use_strict_mode', '1');
      ini_set('session.cookie_lifetime', '0'); // Session cookie (not persistent)

      session_start();

      // Regenerate session ID periodically
      if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
      } elseif (time() - $_SESSION['created'] > 1800) { // 30 minutes
        session_regenerate_id(true);
        $_SESSION['created'] = time();
      }

      // Validate session
      if (!isset($_SESSION['ip_address'])) {
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
      } elseif ($_SESSION['ip_address'] !== ($_SERVER['REMOTE_ADDR'] ?? 'unknown')) {
        // IP changed - destroy session
        session_destroy();
        session_start();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
      }
    }
  }

  /**
   * Destroy session securely
   */
  public static function destroy(): void
  {
    $_SESSION = [];
    if (isset($_COOKIE[session_name()])) {
      setcookie(session_name(), '', time() - 3600, '/');
    }
    session_destroy();
  }
}

/**
 * Token Security
 */
class TokenSecurity
{
  /**
   * Generate secure token
   */
  public static function generate(int $length = 32): string
  {
    return bin2hex(random_bytes($length));
  }

  /**
   * Hash token for storage
   */
  public static function hash(string $token): string
  {
    return hash('sha256', $token);
  }

  /**
   * Verify token
   */
  public static function verify(string $token, string $hash): bool
  {
    return hash_equals($hash, self::hash($token));
  }

  /**
   * Generate time-limited token
   */
  public static function generate_timed(int $expires_in = 3600): array
  {
    $token = self::generate();
    $expires_at = time() + $expires_in;
    return [
      'token' => $token,
      'hash' => self::hash($token),
      'expires_at' => $expires_at
    ];
  }

  /**
   * Verify time-limited token
   */
  public static function verify_timed(string $token, string $hash, int $expires_at): bool
  {
    if (time() > $expires_at) {
      return false; // Expired
    }
    return self::verify($token, $hash);
  }
}

/**
 * IP Whitelisting for Admin
 */
class IPWhitelist
{
  /**
   * Check if IP is whitelisted
   */
  public static function is_allowed(mysqli $conn, string $ip, int $user_id): bool
  {
    try {
      // Check if IP whitelist table exists
      $result = $conn->query("SHOW TABLES LIKE 'admin_ip_whitelist'");
      if ($result->num_rows === 0) {
        return true; // No whitelist = allow all
      }

      // Check if user has IP restrictions
      $stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM admin_ip_whitelist
        WHERE user_id = ? AND ip_address = ? AND enabled = 1
      ");
      $stmt->bind_param("is", $user_id, $ip);
      $stmt->execute();
      $result = $stmt->get_result()->fetch_assoc();

      // If user has any whitelist entries, IP must be whitelisted
      $stmt = $conn->prepare("SELECT COUNT(*) as total FROM admin_ip_whitelist WHERE user_id = ?");
      $stmt->bind_param("i", $user_id);
      $stmt->execute();
      $total = (int)$stmt->get_result()->fetch_assoc()['total'];

      if ($total > 0) {
        return (int)($result['count'] ?? 0) > 0;
      }

      return true; // No restrictions = allow all
    } catch (Exception $e) {
      error_log("IP whitelist check error: " . $e->getMessage());
      return true; // Fail open
    }
  }
}

/**
 * Password Security
 */
class PasswordSecurity
{
  /**
   * Hash password with current best practices
   */
  public static function hash(string $password): string
  {
    return password_hash($password, PASSWORD_DEFAULT, [
      'memory_cost' => 65536, // 64 MB
      'time_cost' => 4, // 4 iterations
      'threads' => 3 // 3 threads
    ]);
  }

  /**
   * Verify password
   */
  public static function verify(string $password, string $hash): bool
  {
    return password_verify($password, $hash);
  }

  /**
   * Check if password needs rehashing
   */
  public static function needs_rehash(string $hash): bool
  {
    return password_needs_rehash($hash, PASSWORD_DEFAULT, [
      'memory_cost' => 65536,
      'time_cost' => 4,
      'threads' => 3
    ]);
  }

  /**
   * Generate secure random password
   */
  public static function generate(int $length = 16): string
  {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
      $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
  }
}

/**
 * Encryption Helper
 */
class Encryption
{
  private static $method = 'AES-256-GCM';

  /**
   * Encrypt sensitive data
   */
  public static function encrypt(string $data, string $key): string
  {
    $iv_length = openssl_cipher_iv_length(self::$method);
    $iv = openssl_random_pseudo_bytes($iv_length);
    $encrypted = openssl_encrypt($data, self::$method, $key, OPENSSL_RAW_DATA, $iv, $tag);
    return base64_encode($iv . $tag . $encrypted);
  }

  /**
   * Decrypt sensitive data
   */
  public static function decrypt(string $encrypted_data, string $key): ?string
  {
    $data = base64_decode($encrypted_data);
    $iv_length = openssl_cipher_iv_length(self::$method);
    $iv = substr($data, 0, $iv_length);
    $tag_length = 16;
    $tag = substr($data, $iv_length, $tag_length);
    $encrypted = substr($data, $iv_length + $tag_length);

    $decrypted = openssl_decrypt($encrypted, self::$method, $key, OPENSSL_RAW_DATA, $iv, $tag);
    return $decrypted !== false ? $decrypted : null;
  }

  /**
   * Generate encryption key
   */
  public static function generate_key(): string
  {
    return base64_encode(random_bytes(32));
  }
}

/**
 * Security Headers Enhancement
 */
function set_advanced_security_headers(): void
{
  // Existing headers from config_security.php
  set_security_headers();

  // Additional security headers
  header('X-Permitted-Cross-Domain-Policies: none');
  header('Cross-Origin-Embedder-Policy: require-corp');
  header('Cross-Origin-Opener-Policy: same-origin');
  header('Cross-Origin-Resource-Policy: same-origin');

  // Remove server information
  header_remove('X-Powered-By');
  header_remove('Server');
}

/**
 * Input Sanitization Enhancement
 */
function sanitize_input($input, string $type = 'string'): mixed
{
  if (is_array($input)) {
    return array_map(function ($item) use ($type) {
      return sanitize_input($item, $type);
    }, $input);
  }

  switch ($type) {
    case 'string':
      return sanitize_string($input);
    case 'email':
      return filter_var($input, FILTER_SANITIZE_EMAIL);
    case 'url':
      return filter_var($input, FILTER_SANITIZE_URL);
    case 'int':
      return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
    case 'float':
      return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    default:
      return sanitize_string($input);
  }
}

/**
 * SQL Injection Prevention Enhancement
 */
function safe_query(mysqli $conn, string $query, array $params = []): mysqli_result|bool
{
  $stmt = $conn->prepare($query);
  if (!$stmt) {
    throw new Exception("Query preparation failed: " . $conn->error);
  }

  if (!empty($params)) {
    $types = '';
    $values = [];
    foreach ($params as $param) {
      if (is_int($param)) {
        $types .= 'i';
      } elseif (is_float($param)) {
        $types .= 'd';
      } else {
        $types .= 's';
      }
      $values[] = $param;
    }
    $stmt->bind_param($types, ...$values);
  }

  $stmt->execute();
  return $stmt->get_result();
}

// Auto-set advanced security headers
set_advanced_security_headers();
