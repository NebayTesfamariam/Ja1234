<?php
/**
 * Stripe Configuration
 * 
 * Get your API keys from: https://dashboard.stripe.com/apikeys
 * 
 * For testing, use test keys (starts with sk_test_ and pk_test_)
 * For production, use live keys (starts with sk_live_ and pk_live_)
 */

// Stripe API Keys
define('STRIPE_SECRET_KEY', 'sk_test_YOUR_SECRET_KEY_HERE'); // Replace with your Stripe Secret Key
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_YOUR_PUBLISHABLE_KEY_HERE'); // Replace with your Stripe Publishable Key

// Stripe Webhook Secret (get from Stripe Dashboard > Webhooks)
define('STRIPE_WEBHOOK_SECRET', 'whsec_YOUR_WEBHOOK_SECRET_HERE'); // Replace with your webhook secret

// Stripe Price IDs (create these in Stripe Dashboard > Products)
// Go to Stripe Dashboard > Products > Create Product > Create Price
// Then copy the Price ID (starts with price_)
define('STRIPE_PRICE_BASIC', 'price_YOUR_BASIC_PRICE_ID'); // Basic plan - 2 devices - €9.99
define('STRIPE_PRICE_FAMILY', 'price_YOUR_FAMILY_PRICE_ID'); // Family plan - 5 devices - €19.99
define('STRIPE_PRICE_PREMIUM', 'price_YOUR_PREMIUM_PRICE_ID'); // Premium plan - 10 devices - €29.99

// Base URL for webhooks and redirects
define('STRIPE_BASE_URL', 'http://localhost/free'); // Change to your domain in production

// Load Stripe PHP library
// Try multiple paths for flexibility
$stripe_loaded = false;

// Path 1: Composer autoload (recommended)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
  require_once __DIR__ . '/vendor/autoload.php';
  $stripe_loaded = true;
}

// Path 2: Direct Stripe library (if downloaded manually)
if (!$stripe_loaded && file_exists(__DIR__ . '/stripe-php/init.php')) {
  require_once __DIR__ . '/stripe-php/init.php';
  $stripe_loaded = true;
}

// Path 3: Alternative vendor path
if (!$stripe_loaded && file_exists(__DIR__ . '/../vendor/autoload.php')) {
  require_once __DIR__ . '/../vendor/autoload.php';
  $stripe_loaded = true;
}

if (!$stripe_loaded) {
  // Stripe library not found - show helpful error
  if (php_sapi_name() !== 'cli') {
    // Web request - return JSON error
    http_response_code(500);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode([
      'error' => 'stripe_library_missing',
      'message' => 'Stripe PHP library niet gevonden. Installeer via: composer require stripe/stripe-php of download van https://github.com/stripe/stripe-php',
      'install_url' => '/free/install_stripe.php',
      'note' => 'Open http://localhost/free/install_stripe.php om automatisch te installeren'
    ]);
    exit;
  } else {
    // CLI - show error message
    die("ERROR: Stripe PHP library niet gevonden.\nInstalleer via: composer require stripe/stripe-php\nOf download van: https://github.com/stripe/stripe-php\n");
  }
}

// Initialize Stripe (only if keys are configured)
if (STRIPE_SECRET_KEY !== 'sk_test_YOUR_SECRET_KEY_HERE' && STRIPE_SECRET_KEY !== 'sk_live_YOUR_SECRET_KEY_HERE') {
  \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
} else {
  // Keys not configured - will show error when trying to use Stripe
  define('STRIPE_NOT_CONFIGURED', true);
}

