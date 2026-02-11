<?php
/**
 * Admin Activity Logs API
 * Get activity logs with filtering and statistics
 */
require __DIR__ . '/../config.php';
$user = require_user($conn);

// Check admin
$stmt = $conn->prepare("SELECT is_admin FROM users WHERE id=?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
if (!$result || !$result['is_admin']) {
  json_out(['message' => 'Access denied'], 403);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  // Check if table exists
  $result = $conn->query("SHOW TABLES LIKE 'activity_logs'");
  if (!$result || $result->num_rows === 0) {
    json_out([
      'logs' => [],
      'stats' => [],
      'message' => 'Activity logging is not enabled. Run setup_activity_logs.php to enable it.'
    ]);
  }
  
  // Get filters
  $action = $_GET['action'] ?? null;
  $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
  $device_id = isset($_GET['device_id']) ? (int)$_GET['device_id'] : null;
  $reason = $_GET['reason'] ?? null;
  $category = $_GET['category'] ?? null;
  $date_from = $_GET['date_from'] ?? null;
  $date_to = $_GET['date_to'] ?? null;
  $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 1000) : 100;
  $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
  
  // Build query
  $where = [];
  $params = [];
  $types = '';
  
  if ($action) {
    $where[] = "al.action = ?";
    $params[] = $action;
    $types .= 's';
  }
  if ($user_id) {
    $where[] = "al.user_id = ?";
    $params[] = $user_id;
    $types .= 'i';
  }
  if ($device_id) {
    $where[] = "al.device_id = ?";
    $params[] = $device_id;
    $types .= 'i';
  }
  if ($reason) {
    $where[] = "al.reason = ?";
    $params[] = $reason;
    $types .= 's';
  }
  if ($category) {
    $where[] = "al.category = ?";
    $params[] = $category;
    $types .= 's';
  }
  if ($date_from) {
    $where[] = "DATE(al.created_at) >= ?";
    $params[] = $date_from;
    $types .= 's';
  }
  if ($date_to) {
    $where[] = "DATE(al.created_at) <= ?";
    $params[] = $date_to;
    $types .= 's';
  }
  
  $where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
  
  // Get logs
  $sql = "
    SELECT 
      al.id,
      al.user_id,
      al.device_id,
      al.action,
      al.domain,
      al.url,
      al.reason,
      al.category,
      al.ip_address,
      al.created_at,
      u.email as user_email,
      d.name as device_name
    FROM activity_logs al
    LEFT JOIN users u ON u.id = al.user_id
    LEFT JOIN devices d ON d.id = al.device_id
    $where_clause
    ORDER BY al.created_at DESC
    LIMIT ? OFFSET ?
  ";
  
  $params[] = $limit;
  $params[] = $offset;
  $types .= 'ii';
  
  $stmt = $conn->prepare($sql);
  if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
  }
  $stmt->execute();
  $result = $stmt->get_result();
  
  $logs = [];
  while ($row = $result->fetch_assoc()) {
    $row['id'] = (int)$row['id'];
    $row['user_id'] = $row['user_id'] ? (int)$row['user_id'] : null;
    $row['device_id'] = $row['device_id'] ? (int)$row['device_id'] : null;
    $logs[] = $row;
  }
  
  // Get statistics
  $stats = [];
  
  // Total logs
  $result = $conn->query("SELECT COUNT(*) as count FROM activity_logs $where_clause");
  if ($result) {
    $stats['total'] = (int)$result->fetch_assoc()['count'];
  }
  
  // Blocked vs Allowed
  $result = $conn->query("
    SELECT action, COUNT(*) as count 
    FROM activity_logs 
    $where_clause
    GROUP BY action
  ");
  $stats['by_action'] = [];
  while ($row = $result->fetch_assoc()) {
    $stats['by_action'][$row['action']] = (int)$row['count'];
  }
  
  // By reason
  $result = $conn->query("
    SELECT reason, COUNT(*) as count 
    FROM activity_logs 
    WHERE reason IS NOT NULL
    $where_clause
    GROUP BY reason
    ORDER BY count DESC
    LIMIT 10
  ");
  $stats['by_reason'] = [];
  while ($row = $result->fetch_assoc()) {
    $stats['by_reason'][$row['reason']] = (int)$row['count'];
  }
  
  // By category
  $result = $conn->query("
    SELECT category, COUNT(*) as count 
    FROM activity_logs 
    WHERE category IS NOT NULL
    $where_clause
    GROUP BY category
    ORDER BY count DESC
  ");
  $stats['by_category'] = [];
  while ($row = $result->fetch_assoc()) {
    $stats['by_category'][$row['category']] = (int)$row['count'];
  }
  
  // Top blocked domains
  $result = $conn->query("
    SELECT domain, COUNT(*) as count 
    FROM activity_logs 
    WHERE action = 'blocked' AND domain IS NOT NULL
    $where_clause
    GROUP BY domain
    ORDER BY count DESC
    LIMIT 20
  ");
  $stats['top_blocked_domains'] = [];
  while ($row = $result->fetch_assoc()) {
    $stats['top_blocked_domains'][] = [
      'domain' => $row['domain'],
      'count' => (int)$row['count']
    ];
  }
  
  // Daily stats (last 30 days)
  $result = $conn->query("
    SELECT 
      DATE(created_at) as date,
      action,
      COUNT(*) as count
    FROM activity_logs
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    $where_clause
    GROUP BY DATE(created_at), action
    ORDER BY date DESC
  ");
  $stats['daily'] = [];
  while ($row = $result->fetch_assoc()) {
    $date = $row['date'];
    if (!isset($stats['daily'][$date])) {
      $stats['daily'][$date] = [];
    }
    $stats['daily'][$date][$row['action']] = (int)$row['count'];
  }
  
  json_out([
    'logs' => $logs,
    'stats' => $stats,
    'pagination' => [
      'limit' => $limit,
      'offset' => $offset,
      'total' => $stats['total'] ?? 0
    ]
  ]);
}

