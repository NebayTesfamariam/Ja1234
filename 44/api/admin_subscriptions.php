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
  // Get all subscriptions with user info
  $stmt = $conn->prepare("
    SELECT s.*, u.email as user_email, p.max_devices, p.name as plan_name,
           (SELECT COUNT(*) FROM devices WHERE user_id = s.user_id) as device_count
    FROM subscriptions s
    JOIN users u ON u.id = s.user_id
    LEFT JOIN subscription_plans p ON p.name = s.plan
    ORDER BY s.created_at DESC
  ");
  $stmt->execute();
  $res = $stmt->get_result();
  
  $subscriptions = [];
  while ($row = $res->fetch_assoc()) {
    $row['id'] = (int)$row['id'];
    $row['user_id'] = (int)$row['user_id'];
    $row['max_devices'] = (int)($row['max_devices'] ?? 0);
    $row['device_count'] = (int)$row['device_count'];
    $subscriptions[] = $row;
  }
  
  // Also get all plans
  $stmt = $conn->prepare("SELECT * FROM subscription_plans ORDER BY max_devices ASC");
  $stmt->execute();
  $res = $stmt->get_result();
  
  $plans = [];
  while ($row = $res->fetch_assoc()) {
    $row['id'] = (int)$row['id'];
    $row['max_devices'] = (int)$row['max_devices'];
    // Convert price_monthly to float (MySQL DECIMAL returns as string)
    $row['price_monthly'] = (float)($row['price_monthly'] ?? 0);
    $plans[] = $row;
  }
  
  json_out([
    'subscriptions' => $subscriptions,
    'plans' => $plans
  ]);
  
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Create or update subscription
  $body = json_decode(file_get_contents('php://input'), true) ?? [];
  $user_id = (int)($body['user_id'] ?? 0);
  $plan = trim((string)($body['plan'] ?? 'basic'));
  $status = trim((string)($body['status'] ?? 'active'));
  $start_date = trim((string)($body['start_date'] ?? date('Y-m-d')));
  $end_date = trim((string)($body['end_date'] ?? ''));
  
  if ($user_id <= 0) {
    json_out(['message' => 'user_id required'], 422);
  }
  
  if (!in_array($plan, ['basic', 'family', 'premium'])) {
    json_out(['message' => 'Invalid plan'], 422);
  }
  
  if (!in_array($status, ['active', 'expired', 'cancelled', 'pending'])) {
    json_out(['message' => 'Invalid status'], 422);
  }
  
  // Calculate end_date if not provided (default: 1 month from start)
  if (empty($end_date)) {
    $end_date = date('Y-m-d', strtotime($start_date . ' +1 month'));
  }
  
  // Ensure status is 'active' for new subscriptions (so it works immediately)
  if ($status === 'pending') {
    $status = 'active';
  }
  
  // Cancel existing active subscriptions for this user
  $stmt = $conn->prepare("UPDATE subscriptions SET status = 'cancelled' WHERE user_id = ? AND status = 'active'");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  
  // Create new subscription (always active by default so it works immediately)
  // Ensure start_date is today or earlier so it works immediately
  if (empty($start_date) || strtotime($start_date) > strtotime('today')) {
    $start_date = date('Y-m-d'); // Today - works immediately
  }
  
  $stmt = $conn->prepare("INSERT INTO subscriptions (user_id, plan, status, start_date, end_date) VALUES (?, ?, 'active', ?, ?)");
  $stmt->bind_param("isss", $user_id, $plan, $start_date, $end_date);
  $stmt->execute();
  
  $subscription_id = $stmt->insert_id;
  
  // Get plan details
  $stmt = $conn->prepare("SELECT max_devices FROM subscription_plans WHERE name = ?");
  $stmt->bind_param("s", $plan);
  $stmt->execute();
  $plan_info = $stmt->get_result()->fetch_assoc();
  $max_devices = (int)($plan_info['max_devices'] ?? 0);
  
  // AUTOMATISCH: Activeer ALLE bestaande devices direct (als ze geblokkeerd waren door geen abonnement)
  // Dit zorgt ervoor dat het systeem direct werkt na abonnement aansluiten
  // Devices via abonnement werken automatisch - geen handmatige actie nodig
  $stmt = $conn->prepare("UPDATE devices SET status = 'active' WHERE user_id = ? AND status = 'blocked' AND permanent_blocked = 0 AND admin_created = 0");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $activated_devices = $stmt->affected_rows;
  
  // AUTOMATISCH: Zorg dat alle devices met actief abonnement actief zijn
  // Triple check om te zorgen dat devices automatisch werken
  $stmt = $conn->prepare("UPDATE devices SET status = 'active' WHERE user_id = ? AND status != 'active' AND permanent_blocked = 0 AND admin_created = 0");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  
  // AUTOMATISCH DEVICE REGISTREREN na abonnement aanmaken
  // Check if user already has devices
  $stmt = $conn->prepare("SELECT COUNT(*) as count FROM devices WHERE user_id = ?");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $device_count = (int)$stmt->get_result()->fetch_assoc()['count'];
  
  $auto_device_created = false;
  $device_info = null;
  
  // If user has no devices yet, automatically create one
  if ($device_count === 0) {
    // Generate automatic WireGuard key and IP
    $wg_public_key = base64_encode(random_bytes(32));
    
    // Find next available IP
    $stmt = $conn->prepare("SELECT wg_ip FROM devices WHERE wg_ip LIKE '10.10.0.%' ORDER BY wg_ip DESC LIMIT 1");
    $stmt->execute();
    $last_ip = $stmt->get_result()->fetch_assoc();
    $ip_num = 10;
    
    if ($last_ip) {
      $parts = explode('.', $last_ip['wg_ip']);
      $ip_num = (int)$parts[3] + 1;
      if ($ip_num > 254) $ip_num = 10;
    }
    
    $wg_ip = "10.10.0.{$ip_num}";
    
    // Generate device name
    $device_name = "Device 1";
    
    // Create device with auto_created = 1 (permanent, cannot be unblocked by user)
    try {
      $stmt = $conn->prepare("INSERT INTO devices (user_id, name, wg_public_key, wg_ip, status, auto_created) VALUES (?,?,?,?,'active',1)");
      $stmt->bind_param("isss", $user_id, $device_name, $wg_public_key, $wg_ip);
      $stmt->execute();
      $device_id = $stmt->insert_id;
      
      $auto_device_created = true;
      $device_info = [
        'device_id' => $device_id,
        'device_name' => $device_name,
        'wg_public_key' => $wg_public_key,
        'wg_ip' => $wg_ip
      ];
    } catch (Exception $e) {
      // Device creation failed, but subscription is still created
      error_log("Failed to auto-create device for user {$user_id}: " . $e->getMessage());
    }
  }
  
  $response = [
    'status' => 'created',
    'subscription_id' => $subscription_id,
    'plan' => $plan,
    'max_devices' => $max_devices,
    'active' => true,
    'message' => 'Abonnement direct actief - klant kan nu devices toevoegen'
  ];
  
  if ($auto_device_created && $device_info) {
    $response['auto_device_created'] = true;
    $response['device'] = $device_info;
    $response['message'] = '✅ Abonnement actief → ✅ Device automatisch geregistreerd → ✅ Direct beschermd!';
  }
  
  if ($activated_devices > 0) {
    $response['devices_activated'] = $activated_devices;
    if (!$auto_device_created) {
      $response['message'] = "✅ Abonnement actief → ✅ {$activated_devices} device(s) geactiveerd → ✅ Direct beschermd!";
    }
  }
  
  json_out($response, 201);
  
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
  // Update subscription
  $body = json_decode(file_get_contents('php://input'), true) ?? [];
  $subscription_id = (int)($body['subscription_id'] ?? 0);
  $plan = trim((string)($body['plan'] ?? ''));
  $status = trim((string)($body['status'] ?? ''));
  $start_date = trim((string)($body['start_date'] ?? ''));
  $end_date = trim((string)($body['end_date'] ?? ''));
  
  if ($subscription_id <= 0) {
    json_out(['message' => 'subscription_id required'], 422);
  }
  
  $updates = [];
  $params = [];
  $types = '';
  
  if ($plan && in_array($plan, ['basic', 'family', 'premium'])) {
    $updates[] = "plan = ?";
    $params[] = $plan;
    $types .= "s";
  }
  
  if ($status && in_array($status, ['active', 'expired', 'cancelled', 'pending'])) {
    $updates[] = "status = ?";
    $params[] = $status;
    $types .= "s";
  }
  
  if ($start_date) {
    $updates[] = "start_date = ?";
    $params[] = $start_date;
    $types .= "s";
  }
  
  if ($end_date) {
    $updates[] = "end_date = ?";
    $params[] = $end_date;
    $types .= "s";
  }
  
  if (empty($updates)) {
    json_out(['message' => 'No valid updates provided'], 422);
  }
  
  $params[] = $subscription_id;
  $types .= "i";
  
  $sql = "UPDATE subscriptions SET " . implode(", ", $updates) . " WHERE id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param($types, ...$params);
  $stmt->execute();
  
  json_out(['status' => 'updated']);
  
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
  // Cancel subscription
  $body = json_decode(file_get_contents('php://input'), true) ?? [];
  $subscription_id = (int)($body['subscription_id'] ?? 0);
  
  if ($subscription_id <= 0) {
    json_out(['message' => 'subscription_id required'], 422);
  }
  
  $stmt = $conn->prepare("UPDATE subscriptions SET status = 'cancelled' WHERE id = ?");
  $stmt->bind_param("i", $subscription_id);
  $stmt->execute();
  
  json_out(['status' => 'cancelled']);
}

