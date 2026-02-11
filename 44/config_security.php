<?php

/**
 * Security Configuration & Helpers
 * Professional security features for production
 */

/**
 * Set security headers
 */
function set_security_headers(): void
{
  // Only set headers when running in web context (not CLI)
  if (php_sapi_name() === 'cli') {
    return;
  }

  // XSS Protection
  header('X-XSS-Protection: 1; mode=block');

  // Prevent MIME type sniffing
  header('X-Content-Type-Options: nosniff');

  // Clickjacking protection
  header('X-Frame-Options: DENY');

  // HSTS (if HTTPS)
  if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
  }

  // Referrer Policy
  header('Referrer-Policy: strict-origin-when-cross-origin');

  // Content Security Policy (adjust as needed)
  header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:;");

  // Permissions Policy
  header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

/**
 * Sanitize input string
 */
function sanitize_string(string $input, int $max_length = 255): string
{
  $input = trim($input);
  $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
  if (strlen($input) > $max_length) {
    $input = substr($input, 0, $max_length);
  }
  return $input;
}

/**
 * Validate email
 */
function validate_email(string $email): bool
{
  return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate IP address
 */
function validate_ip(string $ip): bool
{
  return filter_var($ip, FILTER_VALIDATE_IP) !== false;
}

/**
 * Generate CSRF token
 */
function generate_csrf_token(): string
{
  if (session_status() === PHP_SESSION_NONE) {
    session_start();
  }
  $token = bin2hex(random_bytes(32));
  $_SESSION['csrf_token'] = $token;
  return $token;
}

/**
 * Verify CSRF token
 */
function verify_csrf_token(string $token): bool
{
  if (session_status() === PHP_SESSION_NONE) {
    session_start();
  }
  return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Rate limit check with IP tracking
 */
function check_rate_limit(string $endpoint, int $max_requests = 60, int $window = 60): bool
{
  require_once __DIR__ . '/config_rate_limit.php';
  $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
  $identifier = $endpoint . '_' . $ip;
  return RateLimiter::check($identifier);
}

/**
 * Log security event
 */
function log_security_event(mysqli $conn, string $event_type, string $details, ?int $user_id = null): void
{
  try {
    // Check if security_logs table exists
    $result = $conn->query("SHOW TABLES LIKE 'security_logs'");
    if ($result && $result->num_rows > 0) {
      $stmt = $conn->prepare("
        INSERT INTO security_logs (user_id, event_type, details, ip_address, user_agent, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
      ");
      $ip = $_SERVER['REMOTE_ADDR'] ?? null;
      $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
      $stmt->bind_param("issss", $user_id, $event_type, $details, $ip, $user_agent);
      $stmt->execute();
    }
  } catch (Exception $e) {
    error_log("Security log error: " . $e->getMessage());
  }
}

// Auto-set security headers
set_security_headers();
