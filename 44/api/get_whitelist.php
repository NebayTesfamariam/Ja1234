<?php
/**
 * Whitelist-only API
 * Returns array of domains for a device
 * Empty array = NO INTERNET ACCESS
 *
 * Auth: (1) Bearer token for dashboard, or (2) X-DNS-Internal-Key for DNS server.
 */

try {
  require __DIR__ . '/../config.php';
  require __DIR__ . '/../config_cache.php';
  require __DIR__ . '/../config_rate_limit.php';

  $internal_dns_request = false;
  $dns_key = getenv('DNS_INTERNAL_KEY');
  $header_key = $_SERVER['HTTP_X_DNS_INTERNAL_KEY'] ?? '';
  if ($dns_key !== false && $dns_key !== '' && $header_key === $dns_key) {
    $internal_dns_request = true;
    $user = ['id' => 0];
  } else {
    $user = require_user($conn);
  }
} catch (Throwable $e) {
  json_out([], 200);
}

$device_id = (int)($_GET['device_id'] ?? 0);
if ($device_id <= 0) {
  json_out([], 200);
}

// Rate limiting (skip for internal DNS to avoid blocking VPN traffic)
if (!$internal_dns_request) {
  try {
    $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (!RateLimiter::check("whitelist_{$client_ip}")) {
      json_out([], 200);
    }
  } catch (Throwable $e) {}
}

// Check cache first
try {
  $cache_key = $internal_dns_request
    ? SimpleCache::key('whitelist_dns', $device_id)
    : SimpleCache::key('whitelist', $device_id, $user['id']);
  $cached = SimpleCache::get($cache_key);
  if ($cached !== null && is_array($cached)) {
    header('X-Cache: HIT');
    json_out($cached, 200);
  }
} catch (Throwable $e) {}

// Check if user is admin (not used for internal DNS)
$is_admin = false;
if (!$internal_dns_request) {
  try {
    $stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $user_info = $stmt->get_result()->fetch_assoc();
    if ($user_info && (int)$user_info['is_admin'] === 1) {
      $is_admin = true;
    }
  } catch (Throwable $e) {}
}

// Check device exists (and ownership for non-internal)
try {
  if ($internal_dns_request) {
    $stmt = $conn->prepare("SELECT id, status FROM devices WHERE id=?");
    $stmt->bind_param("i", $device_id);
  } elseif ($is_admin) {
    $stmt = $conn->prepare("SELECT id, status FROM devices WHERE id=?");
    $stmt->bind_param("i", $device_id);
  } else {
    $stmt = $conn->prepare("SELECT id, status FROM devices WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $device_id, $user['id']);
  }
  $stmt->execute();
  $device = $stmt->get_result()->fetch_assoc();
} catch (Throwable $e) {
  json_out([], 200);
}

if (!$device || $device['status'] !== 'active') {
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
