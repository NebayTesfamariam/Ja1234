<?php
/**
 * Genereer een speciale link om een device toe te voegen zonder in te loggen
 * Admin kan links genereren voor gebruikers
 * Gebruikers kunnen ook hun eigen links genereren
 */
require __DIR__ . '/../config.php';
$user = require_user($conn);

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$user_id = (int)($body['user_id'] ?? $user['id']); // Default to current user
$expires_in_days = (int)($body['expires_in_days'] ?? 7); // Default 7 days
$max_uses = (int)($body['max_uses'] ?? 1); // Default 1 use

// Check if user is admin or requesting their own link
$is_admin = false;
$stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$user_info = $stmt->get_result()->fetch_assoc();
if ($user_info && (int)$user_info['is_admin'] === 1) {
  $is_admin = true;
}

// If not admin, can only generate link for themselves
if (!$is_admin && $user_id !== $user['id']) {
  json_out(['message' => 'Je kunt alleen links genereren voor jezelf'], 403);
}

// Check if target user exists
$stmt = $conn->prepare("SELECT id, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$target_user = $stmt->get_result()->fetch_assoc();
if (!$target_user) {
  json_out(['message' => 'Gebruiker niet gevonden'], 404);
}

// Generate unique token
$token = bin2hex(random_bytes(32)); // 64 character hex string

// Calculate expiration date
$expires_at = date('Y-m-d H:i:s', strtotime("+{$expires_in_days} days"));

// Create device_registration_links table if it doesn't exist
try {
  // Check if table exists
  $result = $conn->query("SHOW TABLES LIKE 'device_registration_links'");
  if ($result->num_rows === 0) {
    // Table doesn't exist, create it
    // First try with foreign keys
    $create_sql = "
      CREATE TABLE `device_registration_links` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT UNSIGNED NOT NULL,
        `token` VARCHAR(64) NOT NULL UNIQUE,
        `expires_at` DATETIME NOT NULL,
        `max_uses` INT UNSIGNED DEFAULT 1,
        `uses_count` INT UNSIGNED DEFAULT 0,
        `created_by` INT UNSIGNED NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_token` (`token`),
        INDEX `idx_user_id` (`user_id`),
        INDEX `idx_expires_at` (`expires_at`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    if (!$conn->query($create_sql)) {
      // If creation fails, try without foreign keys
      $create_sql = "
        CREATE TABLE `device_registration_links` (
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `user_id` INT UNSIGNED NOT NULL,
          `token` VARCHAR(64) NOT NULL UNIQUE,
          `expires_at` DATETIME NOT NULL,
          `max_uses` INT UNSIGNED DEFAULT 1,
          `uses_count` INT UNSIGNED DEFAULT 0,
          `created_by` INT UNSIGNED NOT NULL,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          INDEX `idx_token` (`token`),
          INDEX `idx_user_id` (`user_id`),
          INDEX `idx_expires_at` (`expires_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
      ";
      if (!$conn->query($create_sql)) {
        throw new Exception("Failed to create device_registration_links table: " . $conn->error);
      }
    }
    
    // Try to add foreign keys after table creation
    try {
      $conn->query("ALTER TABLE `device_registration_links` ADD CONSTRAINT `fk_link_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE");
    } catch (Exception $e) {
      // Foreign key might fail, that's okay
    }
    try {
      $conn->query("ALTER TABLE `device_registration_links` ADD CONSTRAINT `fk_link_created_by` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE");
    } catch (Exception $e) {
      // Foreign key might fail, that's okay
    }
  }
} catch (Exception $e) {
  error_log("Error creating device_registration_links table: " . $e->getMessage());
  // Continue anyway - table might already exist
}

// Insert link
try {
  $stmt = $conn->prepare("
    INSERT INTO device_registration_links (user_id, token, expires_at, max_uses, created_by)
    VALUES (?, ?, ?, ?, ?)
  ");
  if (!$stmt) {
    throw new Exception("Prepare failed: " . $conn->error);
  }
  $stmt->bind_param("issii", $user_id, $token, $expires_at, $max_uses, $user['id']);
  if (!$stmt->execute()) {
    throw new Exception("Execute failed: " . $stmt->error);
  }
  $link_id = $stmt->insert_id;
} catch (Exception $e) {
  error_log("Error inserting device registration link: " . $e->getMessage());
  json_out(['message' => 'Fout bij aanmaken link: ' . $e->getMessage()], 500);
}

// Generate full URL
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script_path = $_SERVER['SCRIPT_NAME'] ?? '/api/generate_device_link.php';
$base_path = dirname($script_path);
// Remove double slashes
$base_path = str_replace('//', '/', $base_path);
$base_url = $protocol . '://' . $host . $base_path;
$registration_url = $base_url . '/add_device_via_link.php?token=' . urlencode($token);

json_out([
  'status' => 'created',
  'link_id' => $link_id,
  'token' => $token,
  'url' => $registration_url,
  'expires_at' => $expires_at,
  'max_uses' => $max_uses,
  'user_email' => $target_user['email'],
  'message' => 'Device registratie link gegenereerd'
], 201);

