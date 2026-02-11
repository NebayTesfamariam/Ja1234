<?php

/**
 * Input Validation & Sanitization
 * Centralized validation helpers
 */

class Validator
{
  /**
   * Validate and sanitize string
   */
  public static function string(string $value, int $min_length = 1, int $max_length = 255, bool $required = true): ?string
  {
    $value = trim($value);

    if ($required && empty($value)) {
      return null;
    }

    if (strlen($value) < $min_length) {
      return null;
    }

    if (strlen($value) > $max_length) {
      $value = substr($value, 0, $max_length);
    }

    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
  }

  /**
   * Validate email
   */
  public static function email(string $email): bool
  {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
  }

  /**
   * Validate integer
   */
  public static function integer($value, ?int $min = null, ?int $max = null): ?int
  {
    if (!is_numeric($value)) {
      return null;
    }

    $int = (int)$value;

    if ($min !== null && $int < $min) {
      return null;
    }

    if ($max !== null && $int > $max) {
      return null;
    }

    return $int;
  }

  /**
   * Validate IP address
   */
  public static function ip(string $ip): bool
  {
    return filter_var($ip, FILTER_VALIDATE_IP) !== false;
  }

  /**
   * Validate URL
   */
  public static function url(string $url): bool
  {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
  }

  /**
   * Validate domain
   */
  public static function domain(string $domain): bool
  {
    $domain = trim($domain);
    if (empty($domain)) {
      return false;
    }

    // Remove protocol if present
    $domain = preg_replace('#^https?://#', '', $domain);

    // Remove path if present
    $domain = explode('/', $domain)[0];

    // Validate domain format
    return preg_match('/^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i', $domain) === 1;
  }

  /**
   * Validate date
   */
  public static function date(string $date, string $format = 'Y-m-d'): bool
  {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
  }

  /**
   * Validate password strength
   */
  public static function password_strength(string $password): array
  {
    $strength = 0;
    $feedback = [];

    if (strlen($password) >= 8) {
      $strength++;
    } else {
      $feedback[] = 'Wachtwoord moet minimaal 8 karakters lang zijn';
    }

    if (preg_match('/[a-z]/', $password)) {
      $strength++;
    } else {
      $feedback[] = 'Wachtwoord moet minimaal één kleine letter bevatten';
    }

    if (preg_match('/[A-Z]/', $password)) {
      $strength++;
    } else {
      $feedback[] = 'Wachtwoord moet minimaal één hoofdletter bevatten';
    }

    if (preg_match('/[0-9]/', $password)) {
      $strength++;
    } else {
      $feedback[] = 'Wachtwoord moet minimaal één cijfer bevatten';
    }

    if (preg_match('/[^a-zA-Z0-9]/', $password)) {
      $strength++;
    }

    return [
      'strength' => $strength,
      'is_strong' => $strength >= 4,
      'feedback' => $feedback
    ];
  }
}
