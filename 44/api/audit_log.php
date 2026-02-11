<?php
/**
 * Audit Log API
 * Track all admin actions and important system events
 */

require __DIR__ . '/../config.php';
require __DIR__ . '/../config_security.php';

// CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
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

// Create audit_logs table if it doesn't exist
try {
  $result = $conn->query("SHOW TABLES LIKE 'audit_logs'");
  if ($result->num_rows === 0) {
    $conn->query("
      CREATE TABLE `audit_logs` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT UNSIGNED NOT NULL,
        `action` VARCHAR(100) NOT NULL,
        `resource_type` VARCHAR(50) DEFAULT NULL,
        `resource_id` INT UNSIGNED DEFAULT NULL,
        `details` TEXT DEFAULT NULL,
        `ip_address` VARCHAR(45) DEFAULT NULL,
        `user_agent` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_user_id` (`user_id`),
        INDEX `idx_action` (`action`),
        INDEX `idx_resource` (`resource_type`, `resource_id`),
        INDEX `idx_created_at` (`created_at`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
  }
} catch (Exception $e) {
  error_log("Audit log table creation error: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  // Get audit logs with filters
  $action = $_GET['action'] ?? null;
  $user_id_filter = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
  $resource_type = $_GET['resource_type'] ?? null;
  $date_from = $_GET['date_from'] ?? null;
  $date_to = $_GET['date_to'] ?? null;
  $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 1000) : 100;
  $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
  
  $where = [];
  $params = [];
  $types = '';
  
  if ($action) {
    $where[] = "action = ?";
    $params[] = $action;
    $types .= 's';
  }
  
  if ($user_id_filter) {
    $where[] = "user_id = ?";
    $params[] = $user_id_filter;
    $types .= 'i';
  }
  
  if ($resource_type) {
    $where[] = "resource_type = ?";
    $params[] = $resource_type;
    $types .= 's';
  }
  
  if ($date_from) {
    $where[] = "DATE(created_at) >= ?";
    $params[] = $date_from;
    $types .= 's';
  }
  
  if ($date_to) {
    $where[] = "DATE(created_at) <= ?";
    $params[] = $date_to;
    $types .= 's';
  }
  
  $where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
  
  // Get total count
  $count_sql = "SELECT COUNT(*) as total FROM audit_logs {$where_sql}";
  $count_stmt = $conn->prepare($count_sql);
  if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
  }
  $count_stmt->execute();
  $total = (int)$count_stmt->get_result()->fetch_assoc()['total'];
  
  // Get logs
  $sql = "
    SELECT 
      al.*,
      u.email as user_email
    FROM audit_logs al
    LEFT JOIN users u ON u.id = al.user_id
    {$where_sql}
    ORDER BY al.created_at DESC
    LIMIT ? OFFSET ?
  ";
  
  $stmt = $conn->prepare($sql);
  $types .= 'ii';
  $params[] = $limit;
  $params[] = $offset;
  $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  
  // Parse details JSON
  foreach ($logs as &$log) {
    if ($log['details']) {
      $log['details'] = json_decode($log['details'], true);
    }
  }
  
  json_out([
    'logs' => $logs,
    'total' => $total,
    'limit' => $limit,
    'offset' => $offset
  ]);
} else {
  json_out(['message' => 'Method not allowed'], 405);
}

/**
 * Helper function to log audit events
 */
function audit_log(mysqli $conn, string $action, ?string $resource_type = null, ?int $resource_id = null, array $details = []): void {
  try {
    $result = $conn->query("SHOW TABLES LIKE 'audit_logs'");
    if ($result && $result->num_rows > 0) {
      $user_id = $_SESSION['user_id'] ?? null;
      if (!$user_id) {
        // Try to get from token
        require_once __DIR__ . '/../config.php';
        try {
          $user = require_user($conn);
          $user_id = $user['id'];
        } catch (Exception $e) {
          $user_id = null;
        }
      }
      
      if ($user_id) {
        $stmt = $conn->prepare("
          INSERT INTO audit_logs (user_id, action, resource_type, resource_id, details, ip_address, user_agent)
          VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $details_json = json_encode($details);
        $stmt->bind_param("ississs", $user_id, $action, $resource_type, $resource_id, $details_json, $ip, $user_agent);
        $stmt->execute();
      }
    }
  } catch (Exception $e) {
    error_log("Audit log error: " . $e->getMessage());
  }
}
