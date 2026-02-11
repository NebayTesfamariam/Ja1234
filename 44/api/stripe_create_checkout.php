<?php
/**
 * Create Stripe Checkout Session
 * Called when user clicks "Abonnement Aansluiten"
 */
require __DIR__ . '/../config.php';

// Check if Stripe is configured
if (defined('STRIPE_NOT_CONFIGURED')) {
  json_out([
    'message' => 'Stripe is niet geconfigureerd. Vul je API keys in in config_stripe.php',
    'error' => 'stripe_not_configured',
    'setup_url' => '/free/install_stripe.php'
  ], 500);
}

require __DIR__ . '/../config_stripe.php';

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$email = trim((string)($body['email'] ?? ''));
$password = (string)($body['password'] ?? '');
$plan = trim((string)($body['plan'] ?? ''));

// Validate
if (!$email || !$password) {
  json_out(['message' => 'Email en wachtwoord zijn verplicht'], 422);
}

if (strlen($password) < 6) {
  json_out(['message' => 'Wachtwoord moet minimaal 6 karakters lang zijn'], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  json_out(['message' => 'Ongeldig e-mailadres'], 422);
}

if (!in_array($plan, ['basic', 'family', 'premium'])) {
  json_out(['message' => 'Ongeldig abonnement plan'], 422);
}

// Map plan to Stripe Price ID
$price_map = [
  'basic' => STRIPE_PRICE_BASIC,
  'family' => STRIPE_PRICE_FAMILY,
  'premium' => STRIPE_PRICE_PREMIUM
];

$price_id = $price_map[$plan] ?? null;
if (!$price_id) {
  json_out(['message' => 'Stripe price ID niet geconfigureerd voor dit plan'], 500);
}

try {
  // Check if user already exists
  $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $existing_user = $stmt->get_result()->fetch_assoc();
  
  $user_id = null;
  $is_new_user = false;
  
  if ($existing_user) {
    // User exists - verify password
    $stmt = $conn->prepare("SELECT id, password_hash FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user_data = $stmt->get_result()->fetch_assoc();
    
    if (!password_verify($password, $user_data['password_hash'])) {
      json_out(['message' => 'Ongeldig wachtwoord voor dit e-mailadres'], 401);
    }
    
    $user_id = (int)$user_data['id'];
  } else {
    // Create new user (subscription will be created after payment)
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (email, password_hash) VALUES (?, ?)");
    $stmt->bind_param("ss", $email, $password_hash);
    $stmt->execute();
    $user_id = $stmt->insert_id;
    $is_new_user = true;
  }
  
  // Create Stripe Checkout Session
  $checkout_session = \Stripe\Checkout\Session::create([
    'customer_email' => $email,
    'payment_method_types' => ['card'],
    'line_items' => [[
      'price' => $price_id,
      'quantity' => 1,
    ]],
    'mode' => 'subscription',
    'success_url' => STRIPE_BASE_URL . '/stripe_success.php?session_id={CHECKOUT_SESSION_ID}&user_id=' . $user_id . '&plan=' . $plan,
    'cancel_url' => STRIPE_BASE_URL . '/subscribe.html?canceled=1',
    'metadata' => [
      'user_id' => $user_id,
      'plan' => $plan,
      'is_new_user' => $is_new_user ? '1' : '0'
    ],
    'subscription_data' => [
      'metadata' => [
        'user_id' => $user_id,
        'plan' => $plan
      ]
    ]
  ]);
  
  json_out([
    'checkout_url' => $checkout_session->url,
    'session_id' => $checkout_session->id,
    'user_id' => $user_id,
    'plan' => $plan
  ]);
  
} catch (\Stripe\Exception\ApiErrorException $e) {
  json_out(['message' => 'Stripe error: ' . $e->getMessage()], 500);
} catch (Exception $e) {
  json_out(['message' => 'Error: ' . $e->getMessage()], 500);
}

