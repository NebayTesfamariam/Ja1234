<?php
/**
 * Simple In-Memory Cache for API Responses
 * Cache whitelist/blocklist for 10 seconds to reduce database load
 */

class SimpleCache {
  private static $cache = [];
  private static $cache_ttl = 10; // 10 seconds as specified

  /**
   * Get cached value
   */
  public static function get(string $key): ?array {
    if (!isset(self::$cache[$key])) {
      return null;
    }
    
    $entry = self::$cache[$key];
    
    // Check if expired
    if (time() - $entry['time'] > self::$cache_ttl) {
      unset(self::$cache[$key]);
      return null;
    }
    
    return $entry['data'];
  }

  /**
   * Set cached value
   */
  public static function set(string $key, array $data): void {
    self::$cache[$key] = [
      'data' => $data,
      'time' => time()
    ];
  }

  /**
   * Clear cache for a specific key
   */
  public static function clear(string $key): void {
    unset(self::$cache[$key]);
  }

  /**
   * Clear all cache
   */
  public static function clearAll(): void {
    self::$cache = [];
  }

  /**
   * Generate cache key
   */
  public static function key(string $prefix, ...$params): string {
    return $prefix . '_' . md5(serialize($params));
  }
}
