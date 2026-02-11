<?php
/**
 * Whitelist-only API
 * Returns array of domains for a device
 * Empty array = NO INTERNET ACCESS
 */

// Fail-safe: wrap everything in try-catch
try {
  require __DIR__ . '/../config.php';
  require __DIR__ . '/../config_cache.php';
  require __DIR__ . '/../config_rate_limit.php';
  $user = require_user($conn);
} catch (Throwable $e) {
  // Fail-safe: return empty array on any error
  json_out([], 200);
}

$device_id = (int)($_GET['device_id'] ?? 0);
if ($device_id <= 0) {
  // Fail-safe: return empty array
  json_out([], 200);
}

// Rate limiting - prevent abuse
try {
  $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
  if (!RateLimiter::check("whitelist_{$client_ip}")) {
    // Fail-safe: return empty array on rate limit
    json_out([], 200);
  }
} catch (Throwable $e) {
  // Fail-safe: continue on rate limit error
}

// Check cache first (10 second cache)
try {
  $cache_key = SimpleCache::key('whitelist', $device_id, $user['id']);
  $cached = SimpleCache::get($cache_key);
  if ($cached !== null && is_array($cached)) {
    // Cache hit - return cached array
    header('X-Cache: HIT');
    json_out($cached, 200);
  }
} catch (Throwable $e) {
  // Fail-safe: continue if cache fails
}

// Check if user is admin
$is_admin = false;
try {
  $stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
  $stmt->bind_param("i", $user['id']);
  $stmt->execute();
  $user_info = $stmt->get_result()->fetch_assoc();
  if ($user_info && (int)$user_info['is_admin'] === 1) {
    $is_admin = true;
  }
} catch (Throwable $e) {
  // Fail-safe: continue, assume not admin
}

// Check device ownership
try {
  if ($is_admin) {
    $stmt = $conn->prepare("SELECT id, status FROM devices WHERE id=?");
    $stmt->bind_param("i", $device_id);
  } else {
    $stmt = $conn->prepare("SELECT id, status FROM devices WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $device_id, $user['id']);
  }
  $stmt->execute();
  $device = $stmt->get_result()->fetch_assoc();
} catch (Throwable $e) {
  // Fail-safe: return empty array on DB error
  json_out([], 200);
}

// Device doesn't exist → empty array
if (!$device) {
  json_out([], 200);
}

// Device not active → empty array
if ($device['status'] !== 'active') {
  json_out([], 200);
}

// Get whitelist entries - only enabled ones
try {
  $stmt = $conn->prepare("SELECT domain FROM whitelist WHERE device_id=? AND enabled=1 ORDER BY domain ASC");
  $stmt->bind_param("i", $device_id);
  $stmt->execute();
  $res = $stmt->get_result();
  
  $domains = [];
  while ($row = $res->fetch_assoc()) {
    $domain = trim($row['domain']);
    if (!empty($domain)) {
      // Normalize domain (remove www, http, etc)
      if (function_exists('normalize_domain')) {
        $domain = normalize_domain($domain);
      } else {
        // Fallback normalization
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = explode('/', $domain)[0];
        $domain = ltrim($domain, 'www.');
      }
      
      // CRITICAL: Filter out pornographic domains even if in whitelist
      if (function_exists('is_pornographic_domain')) {
        require_once __DIR__ . '/../config_porn_block.php';
        if (is_pornographic_domain($domain)) {
          // Skip pornographic domains - they should NEVER be in whitelist
          error_log("Pornographic domain filtered from whitelist: {$domain}");
          continue;
        }
      }
      
      if (!empty($domain)) {
        $domains[] = $domain;
      }
    }
  }
  
  // Cache response for 10 seconds
  try {
    SimpleCache::set($cache_key, $domains);
  } catch (Throwable $e) {
    // Ignore cache errors
  }
  
  // Add cache headers
  header('X-Cache: MISS');
  header('Cache-Control: public, max-age=10');
  
  // Return array of domains
  json_out($domains, 200);
  
} catch (Throwable $e) {
  // Fail-safe: return empty array on any error
  json_out([], 200);
}
