<?php
require __DIR__ . '/../config.php';
$user = require_user($conn);

// Check admin
$stmt = $conn->prepare("SELECT is_admin FROM users WHERE id=?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
if (!$result || (int)($result['is_admin'] ?? 0) !== 1) {
  json_out(['message' => 'Access denied - Admin privileges required'], 403);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  // Get all users with subscription status
  $stmt = $conn->prepare("
    SELECT u.id, u.email, u.is_admin, u.created_at,
           COUNT(DISTINCT d.id) as device_count,
           COUNT(DISTINCT w.id) as whitelist_count,
           MAX(CASE 
             WHEN s.status = 'active' 
               AND s.start_date <= CURDATE() 
               AND s.end_date >= CURDATE() 
             THEN 1 
             ELSE 0 
           END) as has_active_subscription,
           MAX(s.plan) as subscription_plan,
           MAX(s.end_date) as subscription_end_date
    FROM users u
    LEFT JOIN devices d ON d.user_id = u.id
    LEFT JOIN whitelist w ON w.device_id = d.id
    LEFT JOIN subscriptions s ON s.user_id = u.id
    GROUP BY u.id
    ORDER BY u.id DESC
  ");
  $stmt->execute();
  $res = $stmt->get_result();
  
  $users = [];
  while ($row = $res->fetch_assoc()) {
    $row['id'] = (int)$row['id'];
    $row['is_admin'] = (bool)$row['is_admin'];
    $row['device_count'] = (int)$row['device_count'];
    $row['whitelist_count'] = (int)$row['whitelist_count'];
    $row['has_active_subscription'] = (bool)($row['has_active_subscription'] ?? false);
    $users[] = $row;
  }
  
  json_out(['users' => $users]);
  
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Create user
  $body = json_decode(file_get_contents('php://input'), true) ?? [];
  $email = trim((string)($body['email'] ?? ''));
  $password = (string)($body['password'] ?? '');
  $is_admin = isset($body['is_admin']) ? (int)(bool)$body['is_admin'] : 0;
  
  if (!$email || !$password) {
    json_out(['message' => 'Email and password required'], 422);
  }
  
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_out(['message' => 'Invalid email format'], 422);
  }
  
  $hash = password_hash($password, PASSWORD_DEFAULT);
  
  try {
    $stmt = $conn->prepare("INSERT INTO users (email, password_hash, is_admin) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $email, $hash, $is_admin);
    $stmt->execute();
    $user_id = $stmt->insert_id;
    
    // Send notification email
    try {
      require __DIR__ . '/../config_notifications.php';
      notify_new_user($email, $user_id);
    } catch (Exception $e) {
      // Notification failed, but user creation succeeded
      error_log("Notification error: " . $e->getMessage());
    }
    
    json_out(['status' => 'created', 'user_id' => $user_id], 201);
  } catch (mysqli_sql_exception $e) {
    if ($conn->errno === 1062) {
      json_out(['message' => 'Email already exists'], 409);
    }
    json_out(['message' => 'Database error'], 500);
  }
  
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
  // Update user
  $body = json_decode(file_get_contents('php://input'), true) ?? [];
  $user_id = (int)($body['user_id'] ?? 0);
  $email = trim((string)($body['email'] ?? ''));
  $password = (string)($body['password'] ?? '');
  $is_admin = isset($body['is_admin']) ? (int)(bool)$body['is_admin'] : null;
  
  if ($user_id <= 0) {
    json_out(['message' => 'user_id required'], 422);
  }
  
  // Don't allow editing yourself to non-admin
  if ($user_id === (int)$user['id'] && $is_admin === 0) {
    json_out(['message' => 'Cannot remove admin rights from yourself'], 400);
  }
  
  $updates = [];
  $params = [];
  $types = '';
  
  if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $updates[] = "email = ?";
    $params[] = $email;
    $types .= "s";
  }
  
  if ($password && strlen($password) >= 6) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $updates[] = "password_hash = ?";
    $params[] = $hash;
    $types .= "s";
  }
  
  if ($is_admin !== null) {
    $updates[] = "is_admin = ?";
    $params[] = $is_admin;
    $types .= "i";
  }
  
  if (empty($updates)) {
    json_out(['message' => 'No valid updates provided'], 422);
  }
  
  $params[] = $user_id;
  $types .= "i";
  
  $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param($types, ...$params);
  $stmt->execute();
  
  json_out(['status' => 'updated']);
  
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
  // Delete user
  $body = json_decode(file_get_contents('php://input'), true) ?? [];
  $user_id = (int)($body['user_id'] ?? 0);
  
  if ($user_id <= 0) {
    json_out(['message' => 'user_id required'], 422);
  }
  
  // Don't allow deleting yourself
  if ($user_id === (int)$user['id']) {
    json_out(['message' => 'Cannot delete yourself'], 400);
  }
  
  $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  
  json_out(['status' => 'deleted']);
}

