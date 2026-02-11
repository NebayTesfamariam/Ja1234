<?php
/**
 * Admin IP Whitelist Management
 * Manage IP whitelist for admin access
 */

require __DIR__ . '/../config.php';
require_once __DIR__ . '/../config_security_advanced.php';

// CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

try {
  $user = require_user($conn);
} catch (Exception $e) {
  json_out(['message' => 'Authentication required'], 401);
}

// Check admin
$stmt = $conn->prepare("SELECT is_admin FROM users WHERE id=?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
if (!$result || (int)($result['is_admin'] ?? 0) !== 1) {
  json_out(['message' => 'Access denied'], 403);
}

// Create table if it doesn't exist
try {
  $conn->query("
    CREATE TABLE IF NOT EXISTS `admin_ip_whitelist` (
      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      `user_id` INT UNSIGNED NOT NULL,
      `ip_address` VARCHAR(45) NOT NULL,
      `description` VARCHAR(255) DEFAULT NULL,
      `enabled` TINYINT(1) DEFAULT 1,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `created_by` INT UNSIGNED NOT NULL,
      INDEX `idx_user_id` (`user_id`),
      INDEX `idx_ip` (`ip_address`),
      UNIQUE KEY `unique_user_ip` (`user_id`, `ip_address`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  ");
} catch (Exception $e) {
  error_log("IP whitelist table creation error: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  // Get whitelist entries
  $user_id_filter = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
  
  if ($user_id_filter) {
    $stmt = $conn->prepare("
      SELECT w.*, u.email as user_email, creator.email as created_by_email
      FROM admin_ip_whitelist w
      LEFT JOIN users u ON u.id = w.user_id
      LEFT JOIN users creator ON creator.id = w.created_by
      WHERE w.user_id = ?
      ORDER BY w.created_at DESC
    ");
    $stmt->bind_param("i", $user_id_filter);
  } else {
    $stmt = $conn->prepare("
      SELECT w.*, u.email as user_email, creator.email as created_by_email
      FROM admin_ip_whitelist w
      LEFT JOIN users u ON u.id = w.user_id
      LEFT JOIN users creator ON creator.id = w.created_by
      ORDER BY w.created_at DESC
    ");
  }
  
  $stmt->execute();
  $entries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  
  json_out(['entries' => $entries]);
  
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Add IP to whitelist
  $body = json_decode(file_get_contents('php://input'), true) ?? [];
  $target_user_id = (int)($body['user_id'] ?? 0);
  $ip_address = trim((string)($body['ip_address'] ?? ''));
  $description = trim((string)($body['description'] ?? ''));
  
  if (!$target_user_id || !$ip_address) {
    json_out(['message' => 'user_id and ip_address required'], 422);
  }
  
  // Validate IP
  if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
    json_out(['message' => 'Invalid IP address'], 422);
  }
  
  // Check if already exists
  $stmt = $conn->prepare("SELECT id FROM admin_ip_whitelist WHERE user_id = ? AND ip_address = ?");
  $stmt->bind_param("is", $target_user_id, $ip_address);
  $stmt->execute();
  if ($stmt->get_result()->fetch_assoc()) {
    json_out(['message' => 'IP address already whitelisted'], 409);
  }
  
  // Add to whitelist
  $stmt = $conn->prepare("
    INSERT INTO admin_ip_whitelist (user_id, ip_address, description, created_by)
    VALUES (?, ?, ?, ?)
  ");
  $stmt->bind_param("issi", $target_user_id, $ip_address, $description, $user['id']);
  $stmt->execute();
  
  // Log audit event
  audit_log($conn, 'ip_whitelist_added', 'admin_ip_whitelist', $stmt->insert_id, [
    'user_id' => $target_user_id,
    'ip_address' => $ip_address
  ]);
  
  json_out([
    'status' => 'success',
    'message' => 'IP address added to whitelist',
    'entry_id' => $stmt->insert_id
  ]);
  
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
  // Remove IP from whitelist
  $entry_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  
  if (!$entry_id) {
    json_out(['message' => 'id required'], 422);
  }
  
  // Get entry info for audit
  $stmt = $conn->prepare("SELECT user_id, ip_address FROM admin_ip_whitelist WHERE id = ?");
  $stmt->bind_param("i", $entry_id);
  $stmt->execute();
  $entry = $stmt->get_result()->fetch_assoc();
  
  if (!$entry) {
    json_out(['message' => 'Entry not found'], 404);
  }
  
  // Delete entry
  $stmt = $conn->prepare("DELETE FROM admin_ip_whitelist WHERE id = ?");
  $stmt->bind_param("i", $entry_id);
  $stmt->execute();
  
  // Log audit event
  audit_log($conn, 'ip_whitelist_removed', 'admin_ip_whitelist', $entry_id, [
    'user_id' => $entry['user_id'],
    'ip_address' => $entry['ip_address']
  ]);
  
  json_out(['status' => 'success', 'message' => 'IP address removed from whitelist']);
  
} else {
  json_out(['message' => 'Method not allowed'], 405);
}
