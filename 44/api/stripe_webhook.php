<?php
/**
 * Stripe Webhook Handler
 * Handles Stripe events (payment succeeded, subscription updated, etc.)
 * 
 * Configure webhook in Stripe Dashboard:
 * - Go to Developers > Webhooks
 * - Add endpoint: https://yourdomain.com/free/api/stripe_webhook.php
 * - Select events: customer.subscription.created, customer.subscription.updated, customer.subscription.deleted, invoice.payment_succeeded, invoice.payment_failed
 */
require __DIR__ . '/../config.php';

// Check if Stripe is configured
if (defined('STRIPE_NOT_CONFIGURED')) {
  http_response_code(500);
  die('Stripe is niet geconfigureerd');
}

require __DIR__ . '/../config_stripe.php';

// Get webhook payload
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

try {
  $event = \Stripe\Webhook::constructEvent(
    $payload,
    $sig_header,
    STRIPE_WEBHOOK_SECRET
  );
} catch (\Stripe\Exception\SignatureVerificationException $e) {
  http_response_code(400);
  die('Invalid signature');
}

// Handle event
switch ($event->type) {
  case 'customer.subscription.created':
  case 'customer.subscription.updated':
    $subscription = $event->data->object;
    handleSubscriptionUpdate($conn, $subscription);
    break;
    
  case 'customer.subscription.deleted':
    $subscription = $event->data->object;
    handleSubscriptionCancelled($conn, $subscription);
    break;
    
  case 'invoice.payment_succeeded':
    $invoice = $event->data->object;
    handlePaymentSucceeded($conn, $invoice);
    break;
    
  case 'invoice.payment_failed':
    $invoice = $event->data->object;
    handlePaymentFailed($conn, $invoice);
    break;
}

http_response_code(200);
echo json_encode(['received' => true]);

function handleSubscriptionUpdate($conn, $subscription) {
  $stripe_subscription_id = $subscription->id;
  $customer_id = $subscription->customer;
  $plan = $subscription->metadata->plan ?? 'basic';
  $user_id = (int)($subscription->metadata->user_id ?? 0);
  
  if (!$user_id) {
    // Try to find user by customer_id
    $stmt = $conn->prepare("SELECT user_id FROM subscriptions WHERE stripe_customer_id = ? LIMIT 1");
    $stmt->bind_param("s", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    if ($result) {
      $user_id = (int)$result['user_id'];
    }
  }
  
  if (!$user_id) return;
  
  $status = $subscription->status === 'active' ? 'active' : 'expired';
  $start_date = date('Y-m-d', $subscription->current_period_start);
  $end_date = date('Y-m-d', $subscription->current_period_end);
  
  // Update or create subscription
  $stmt = $conn->prepare("
    INSERT INTO subscriptions (user_id, plan, status, start_date, end_date, stripe_subscription_id, stripe_customer_id)
    VALUES (?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
      status = VALUES(status),
      start_date = VALUES(start_date),
      end_date = VALUES(end_date),
      plan = VALUES(plan)
  ");
  $stmt->bind_param("issssss", $user_id, $plan, $status, $start_date, $end_date, $stripe_subscription_id, $customer_id);
  $stmt->execute();
  
  // If subscription becomes active again, unblock devices automatically
  if ($status === 'active') {
    // Unblock all devices for this user (only if not permanent_blocked by admin)
    $stmt = $conn->prepare("
      UPDATE devices 
      SET status = 'active'
      WHERE user_id = ? 
        AND status = 'blocked'
        AND permanent_blocked = 0
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
  }
}

function handleSubscriptionCancelled($conn, $subscription) {
  $stripe_subscription_id = $subscription->id;
  
  $stmt = $conn->prepare("UPDATE subscriptions SET status = 'cancelled' WHERE stripe_subscription_id = ?");
  $stmt->bind_param("s", $stripe_subscription_id);
  $stmt->execute();
}

function handlePaymentSucceeded($conn, $invoice) {
  $subscription_id = $invoice->subscription;
  if (!$subscription_id) return;
  
  $subscription = \Stripe\Subscription::retrieve($subscription_id);
  handleSubscriptionUpdate($conn, $subscription);
}

function handlePaymentFailed($conn, $invoice) {
  $subscription_id = $invoice->subscription;
  if (!$subscription_id) return;
  
  // Mark subscription as expired and block all devices (but NOT permanent - can be unblocked when subscription reactivates)
  $stmt = $conn->prepare("
    SELECT user_id 
    FROM subscriptions 
    WHERE stripe_subscription_id = ?
  ");
  $stmt->bind_param("s", $subscription_id);
  $stmt->execute();
  $sub_info = $stmt->get_result()->fetch_assoc();
  
  if ($sub_info) {
    $user_id = (int)$sub_info['user_id'];
    
    // Block all devices (only if not permanent_blocked by admin)
    $stmt = $conn->prepare("
      UPDATE devices 
      SET status = 'blocked'
      WHERE user_id = ? 
        AND permanent_blocked = 0
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
  }
  
  $stmt = $conn->prepare("UPDATE subscriptions SET status = 'expired' WHERE stripe_subscription_id = ?");
  $stmt->bind_param("s", $subscription_id);
  $stmt->execute();
}

