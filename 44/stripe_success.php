<?php
/**
 * Stripe Checkout Success Page
 * Called after successful payment
 */
require __DIR__ . '/config.php';

// Check if Stripe is configured
if (defined('STRIPE_NOT_CONFIGURED')) {
  die('Stripe is niet geconfigureerd. Vul je API keys in in config_stripe.php');
}

require __DIR__ . '/config_stripe.php';

$session_id = $_GET['session_id'] ?? '';
$user_id = (int)($_GET['user_id'] ?? 0);
$plan = trim($_GET['plan'] ?? '');

if (!$session_id || !$user_id || !$plan) {
  die('Invalid parameters');
}

try {
  // Retrieve Stripe session
  $session = \Stripe\Checkout\Session::retrieve($session_id);
  
  if ($session->payment_status !== 'paid') {
    die('Payment not completed');
  }
  
  // Get subscription from Stripe
  $subscription_id = $session->subscription;
  $subscription = \Stripe\Subscription::retrieve($subscription_id);
  $customer_id = $subscription->customer;
  
  // Create or update subscription in database
  $start_date = date('Y-m-d', $subscription->current_period_start);
  $end_date = date('Y-m-d', $subscription->current_period_end);
  
  // Cancel any existing subscriptions
  $stmt = $conn->prepare("UPDATE subscriptions SET status = 'cancelled' WHERE user_id = ? AND status = 'active'");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  
  // Create new subscription - DIRECT ACTIVE
  $stmt = $conn->prepare("INSERT INTO subscriptions (user_id, plan, status, start_date, end_date, stripe_subscription_id, stripe_customer_id) VALUES (?, ?, 'active', ?, ?, ?, ?)");
  $stmt->bind_param("isssss", $user_id, $plan, $start_date, $end_date, $subscription_id, $customer_id);
  $stmt->execute();
  $db_subscription_id = $stmt->insert_id;
  
  // AUTOMATISCH: Activeer ALLE bestaande devices direct (als ze geblokkeerd waren door geen abonnement)
  // Dit zorgt ervoor dat het systeem direct werkt na abonnement aansluiten
  $stmt = $conn->prepare("UPDATE devices SET status = 'active' WHERE user_id = ? AND status = 'blocked' AND permanent_blocked = 0 AND admin_created = 0");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $activated_devices = $stmt->affected_rows;
  
  // AUTOMATISCH DEVICE AANMAKEN (alleen als gebruiker nog geen devices heeft)
  $stmt = $conn->prepare("SELECT COUNT(*) as count FROM devices WHERE user_id = ?");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $device_count = (int)$stmt->get_result()->fetch_assoc()['count'];
  
  $device_id = null;
  
  // If user has no devices yet, automatically create one
  if ($device_count === 0) {
    // Detect device type from user agent
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $device_name = "Device";
    
    if (stripos($user_agent, 'iPhone') !== false) {
      $device_name = 'iPhone';
    } elseif (stripos($user_agent, 'iPad') !== false) {
      $device_name = 'iPad';
    } elseif (stripos($user_agent, 'Android') !== false) {
      $device_name = 'Android Device';
    } elseif (stripos($user_agent, 'Windows') !== false) {
      $device_name = 'Windows PC';
    } elseif (stripos($user_agent, 'Mac') !== false || stripos($user_agent, 'macOS') !== false) {
      $device_name = 'Mac';
    } elseif (stripos($user_agent, 'Linux') !== false) {
      $device_name = 'Linux PC';
    }
    
    // Generate WireGuard key and IP
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
    
    // STRICT: Prevent duplicate devices - check before creating
    $stmt = $conn->prepare("SELECT id FROM devices WHERE user_id = ? AND (wg_public_key = ? OR wg_ip = ?) LIMIT 1");
    $stmt->bind_param("iss", $user_id, $wg_public_key, $wg_ip);
    $stmt->execute();
    $existing_device = $stmt->get_result()->fetch_assoc();
    
    if (!$existing_device) {
      // Create device automatically with auto_created = 1
      try {
        $auto_created = 1; // Automatisch aangemaakt bij abonnement
        $stmt = $conn->prepare("INSERT INTO devices (user_id, name, wg_public_key, wg_ip, status, auto_created) VALUES (?,?,?,?,'active',?)");
        $stmt->bind_param("isssi", $user_id, $device_name, $wg_public_key, $wg_ip, $auto_created);
        $stmt->execute();
        $device_id = $stmt->insert_id;
      } catch (mysqli_sql_exception $e) {
        // Device creation failed, but subscription is active
        error_log("Device creation failed: " . $e->getMessage());
      }
    } else {
      // Device already exists - use existing device
      $device_id = (int)$existing_device['id'];
      // Activate it if it was blocked
      $stmt = $conn->prepare("UPDATE devices SET status = 'active' WHERE id = ? AND permanent_blocked = 0");
      $stmt->bind_param("i", $device_id);
      $stmt->execute();
    }
  }
  
  // Generate login token
  $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $user_data = $stmt->get_result()->fetch_assoc();
  $prefix = substr($user_data['password_hash'], 0, 12);
  $token = base64_encode($user_id . ':' . $prefix);
  
  // Store token in session for auto-login
  session_start();
  $_SESSION['token'] = $token;
  $_SESSION['user_id'] = $user_id;
  
  // Redirect to control panel
  header('Location: public/index.html?success=1&token=' . urlencode($token));
  exit;
  
} catch (Exception $e) {
  die('Error: ' . $e->getMessage());
}

