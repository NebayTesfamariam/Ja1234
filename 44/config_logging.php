<?php

/**
 * Professional Logging System
 * Centralized logging with different levels
 */

class Logger
{
  const LEVEL_DEBUG = 0;
  const LEVEL_INFO = 1;
  const LEVEL_WARNING = 2;
  const LEVEL_ERROR = 3;
  const LEVEL_CRITICAL = 4;

  private static $log_file = null;
  private static $min_level = self::LEVEL_INFO;

  /**
   * Initialize logger
   */
  public static function init(?string $log_file = null, int $min_level = self::LEVEL_INFO): void
  {
    self::$log_file = $log_file ?? __DIR__ . '/../logs/app.log';
    self::$min_level = $min_level;

    // Create logs directory if it doesn't exist
    $log_dir = dirname(self::$log_file);
    if (!is_dir($log_dir)) {
      mkdir($log_dir, 0755, true);
    }
  }

  /**
   * Log debug message
   */
  public static function debug(string $message, array $context = []): void
  {
    self::log(self::LEVEL_DEBUG, 'DEBUG', $message, $context);
  }

  /**
   * Log info message
   */
  public static function info(string $message, array $context = []): void
  {
    self::log(self::LEVEL_INFO, 'INFO', $message, $context);
  }

  /**
   * Log warning message
   */
  public static function warning(string $message, array $context = []): void
  {
    self::log(self::LEVEL_WARNING, 'WARNING', $message, $context);
  }

  /**
   * Log error message
   */
  public static function error(string $message, array $context = []): void
  {
    self::log(self::LEVEL_ERROR, 'ERROR', $message, $context);
  }

  /**
   * Log critical message
   */
  public static function critical(string $message, array $context = []): void
  {
    self::log(self::LEVEL_CRITICAL, 'CRITICAL', $message, $context);
  }

  /**
   * Internal log method
   */
  private static function log(int $level, string $level_name, string $message, array $context = []): void
  {
    if ($level < self::$min_level) {
      return;
    }

    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_id = $context['user_id'] ?? 'unknown';
    $request_id = $context['request_id'] ?? uniqid();

    $log_entry = sprintf(
      "[%s] [%s] [%s] [%s] [%s] %s",
      $timestamp,
      $level_name,
      $ip,
      $user_id,
      $request_id,
      $message
    );

    if (!empty($context)) {
      $log_entry .= ' ' . json_encode($context);
    }

    $log_entry .= PHP_EOL;

    // Write to file
    if (self::$log_file) {
      file_put_contents(self::$log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }

    // Also log to PHP error log for critical errors
    if ($level >= self::LEVEL_ERROR) {
      error_log($log_entry);
    }
  }

  /**
   * Log API request
   */
  public static function api_request(string $endpoint, string $method, int $status_code, float $duration_ms, array $context = []): void
  {
    $context['endpoint'] = $endpoint;
    $context['method'] = $method;
    $context['status_code'] = $status_code;
    $context['duration_ms'] = $duration_ms;

    if ($status_code >= 500) {
      self::error("API Request: {$method} {$endpoint} - {$status_code} ({$duration_ms}ms)", $context);
    } elseif ($status_code >= 400) {
      self::warning("API Request: {$method} {$endpoint} - {$status_code} ({$duration_ms}ms)", $context);
    } else {
      self::info("API Request: {$method} {$endpoint} - {$status_code} ({$duration_ms}ms)", $context);
    }
  }

  /**
   * Log database query
   */
  public static function database_query(string $query, float $duration_ms, bool $success = true): void
  {
    if (!$success) {
      self::error("Database Query Failed: {$query} ({$duration_ms}ms)");
    } elseif ($duration_ms > 1000) {
      self::warning("Slow Database Query: {$query} ({$duration_ms}ms)");
    }
  }
}

// Initialize logger
Logger::init();
