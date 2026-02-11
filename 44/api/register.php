<?php
/**
 * User registration endpoint
 * Creates user account and optionally creates subscription
 */
require __DIR__ . '/../config.php';

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$email = trim((string)($body['email'] ?? ''));
$password = (string)($body['password'] ?? '');
$name = trim((string)($body['name'] ?? ''));

if ($email === '' || $password === '') {
  json_out(['message' => 'Email en wachtwoord zijn verplicht'], 422);
}

if (strlen($password) < 6) {
  json_out(['message' => 'Wachtwoord moet minimaal 6 karakters lang zijn'], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  json_out(['message' => 'Ongeldig e-mailadres'], 422);
}

try {
  // Check if user already exists
  $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  if ($stmt->get_result()->fetch_assoc()) {
    json_out(['message' => 'Dit e-mailadres is al geregistreerd'], 409);
  }

  // Create user
  $password_hash = password_hash($password, PASSWORD_DEFAULT);
  $stmt = $conn->prepare("INSERT INTO users (email, password_hash) VALUES (?, ?)");
  $stmt->bind_param("ss", $email, $password_hash);
  $stmt->execute();
  $user_id = $stmt->insert_id;

  // Create subscription if plan is provided
  $plan = trim((string)($body['plan'] ?? ''));
  
  // Automatisch device naam detecteren uit user agent (als niet opgegeven)
  $device_name = trim((string)($body['device_name'] ?? ''));
  if (empty($device_name)) {
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (stripos($user_agent, 'iPhone') !== false || stripos($user_agent, 'iPad') !== false) {
      $device_name = 'iPhone/iPad';
    } elseif (stripos($user_agent, 'Android') !== false) {
      $device_name = 'Android Device';
    } elseif (stripos($user_agent, 'Windows') !== false) {
      $device_name = 'Windows PC';
    } elseif (stripos($user_agent, 'Mac') !== false || stripos($user_agent, 'macOS') !== false) {
      $device_name = 'Mac';
    } elseif (stripos($user_agent, 'Linux') !== false) {
      $device_name = 'Linux PC';
    } else {
      $device_name = 'Mijn Device';
    }
  }
  
  if (in_array($plan, ['basic', 'family', 'premium'])) {
    $start_date = date('Y-m-d');
    $end_date = date('Y-m-d', strtotime('+1 month'));
    
    // Cancel any existing subscriptions
    $stmt = $conn->prepare("UPDATE subscriptions SET status = 'cancelled' WHERE user_id = ? AND status = 'active'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    // Create new subscription - DIRECT ACTIVE so pornographic content is blocked immediately
    $stmt = $conn->prepare("INSERT INTO subscriptions (user_id, plan, status, start_date, end_date) VALUES (?, ?, 'active', ?, ?)");
    $stmt->bind_param("isss", $user_id, $plan, $start_date, $end_date);
    $stmt->execute();
    $subscription_id = $stmt->insert_id;
    
    // AUTOMATISCH DEVICE AANMAKEN na abonnement
    // Genereer automatisch WireGuard key en IP
    $wg_public_key = base64_encode(random_bytes(32));
    
    // Find next available IP from range 10.10.0.0/24
    $stmt = $conn->prepare("SELECT wg_ip FROM devices WHERE wg_ip LIKE '10.10.0.%' ORDER BY wg_ip DESC LIMIT 1");
    $stmt->execute();
    $last_ip = $stmt->get_result()->fetch_assoc();
    $ip_num = 10; // Start from 10.10.0.10
    
    if ($last_ip) {
      $parts = explode('.', $last_ip['wg_ip']);
      $ip_num = (int)$parts[3] + 1;
      if ($ip_num > 254) $ip_num = 10; // Wrap around
    }
    
    $wg_ip = "10.10.0.{$ip_num}";
    
    // Generate device name if not provided
    if (empty($device_name) || $device_name === 'Mijn Device') {
      $device_name = "Device 1";
    }
    
    // Create device automatically - DIRECT ACTIVE
    // auto_created = 1 betekent dat dit device NOOIT kan worden deblokkeerd
    $device_id = null;
    try {
      $auto_created = 1; // Automatisch aangemaakt bij abonnement - permanent geblokkeerd
      $stmt = $conn->prepare("INSERT INTO devices (user_id, name, wg_public_key, wg_ip, status, auto_created) VALUES (?,?,?,?,'active',?)");
      $stmt->bind_param("isssi", $user_id, $device_name, $wg_public_key, $wg_ip, $auto_created);
      $stmt->execute();
      $device_id = $stmt->insert_id;
    } catch (mysqli_sql_exception $e) {
      // Device creation failed, but subscription is created - continue
      error_log("Device creation failed: " . $e->getMessage());
    }
    
    // Generate login token for immediate access
    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_data = $stmt->get_result()->fetch_assoc();
    $prefix = substr($user_data['password_hash'], 0, 12);
    $token = base64_encode($user_id . ':' . $prefix);
    
    json_out([
      'status' => 'created',
      'token' => $token,
      'user_id' => $user_id,
      'subscription_id' => $subscription_id,
      'device_id' => $device_id,
      'device_name' => $device_name,
      'wg_public_key' => $wg_public_key,
      'wg_ip' => $wg_ip,
      'plan' => $plan,
      'active' => true,
      'message' => 'Account, abonnement en device automatisch aangemaakt! Pornografische content wordt DIRECT geblokkeerd - permanent actief.',
      'note' => 'Je device is direct actief en beschermd. Geen extra configuratie nodig!'
    ], 201);
  } else {
    json_out([
      'status' => 'created',
      'user_id' => $user_id,
      'message' => 'Account aangemaakt'
    ], 201);
  }

} catch (mysqli_sql_exception $e) {
  if ($conn->errno === 1062) {
    json_out(['message' => 'Dit e-mailadres is al geregistreerd'], 409);
  }
  json_out(['message' => 'Database error: ' . $e->getMessage()], 500);
}

