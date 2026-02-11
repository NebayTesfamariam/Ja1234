<?php
/**
 * Simple Rate Limiting
 * Prevent API abuse and DDoS attacks
 */

class RateLimiter {
  private static $limits = [];
  private static $max_requests = 100; // Max requests per minute
  private static $window = 60; // 1 minute window

  /**
   * Check if request is allowed
   */
  public static function check(string $identifier): bool {
    $now = time();
    $key = $identifier;
    
    // Clean old entries
    if (isset(self::$limits[$key])) {
      self::$limits[$key] = array_filter(
        self::$limits[$key],
        function($timestamp) use ($now) {
          return ($now - $timestamp) < self::$window;
        }
      );
    } else {
      self::$limits[$key] = [];
    }
    
    // Check limit
    if (count(self::$limits[$key]) >= self::$max_requests) {
      return false;
    }
    
    // Add current request
    self::$limits[$key][] = $now;
    
    return true;
  }

  /**
   * Get remaining requests
   */
  public static function remaining(string $identifier): int {
    $key = $identifier;
    if (!isset(self::$limits[$key])) {
      return self::$max_requests;
    }
    
    $now = time();
    $recent = array_filter(
      self::$limits[$key],
      function($timestamp) use ($now) {
        return ($now - $timestamp) < self::$window;
      }
    );
    
    return max(0, self::$max_requests - count($recent));
  }
}
