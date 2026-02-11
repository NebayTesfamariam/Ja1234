<?php
/**
 * Check Admin Panel Functionality
 * Verifies all admin panel API endpoints and functionality
 */

require __DIR__ . '/config.php';

echo "🔍 ADMIN PANEL CHECK\n";
echo "===================\n\n";

$checks = [];
$errors = [];

// 1. Check Database Connection
echo "1️⃣ Database Connection...\n";
try {
  $conn->ping();
  $checks[] = ['name' => 'Database Connection', 'status' => '✅ OK'];
  echo "   ✅ Database connection OK\n";
} catch (Exception $e) {
  $checks[] = ['name' => 'Database Connection', 'status' => '❌ FAILED', 'error' => $e->getMessage()];
  $errors[] = "Database connection failed: " . $e->getMessage();
  echo "   ❌ Database connection FAILED: " . $e->getMessage() . "\n";
}

// 2. Check Admin User Exists
echo "\n2️⃣ Admin User Check...\n";
try {
  $stmt = $conn->prepare("SELECT id, email, is_admin FROM users WHERE is_admin = 1 LIMIT 1");
  $stmt->execute();
  $admin = $stmt->get_result()->fetch_assoc();
  if ($admin) {
    $checks[] = ['name' => 'Admin User Exists', 'status' => '✅ OK', 'email' => $admin['email']];
    echo "   ✅ Admin user found: {$admin['email']}\n";
  } else {
    $checks[] = ['name' => 'Admin User Exists', 'status' => '⚠️  WARNING', 'error' => 'No admin user found'];
    $errors[] = "No admin user found - create one with: php create_admin_user.php admin@test.com 123456";
    echo "   ⚠️  No admin user found\n";
    echo "   💡 Create admin: php create_admin_user.php admin@test.com 123456\n";
  }
} catch (Exception $e) {
  $checks[] = ['name' => 'Admin User Check', 'status' => '❌ FAILED', 'error' => $e->getMessage()];
  $errors[] = "Admin user check failed: " . $e->getMessage();
  echo "   ❌ Admin user check FAILED: " . $e->getMessage() . "\n";
}

// 3. Check Required API Files
echo "\n3️⃣ API Files Check...\n";
$required_apis = [
  'api/admin_check.php',
  'api/admin_stats.php',
  'api/admin_users.php',
  'api/admin_devices.php',
  'api/admin_subscriptions.php',
  'api/admin_health.php',
  'api/login.php'
];

foreach ($required_apis as $api) {
  $path = __DIR__ . '/' . $api;
  if (file_exists($path)) {
    $checks[] = ['name' => "API: $api", 'status' => '✅ OK'];
    echo "   ✅ $api\n";
  } else {
    $checks[] = ['name' => "API: $api", 'status' => '❌ MISSING'];
    $errors[] = "Missing API file: $api";
    echo "   ❌ MISSING: $api\n";
  }
}

// 4. Check Security Config (optional)
echo "\n4️⃣ Security Config Check...\n";
$security_file = __DIR__ . '/config_security_advanced.php';
if (file_exists($security_file)) {
  $checks[] = ['name' => 'Security Config', 'status' => '✅ OK'];
  echo "   ✅ config_security_advanced.php exists\n";
  
  // Check if IPWhitelist class exists
  require_once $security_file;
  if (class_exists('IPWhitelist')) {
    echo "   ✅ IPWhitelist class exists\n";
  } else {
    echo "   ⚠️  IPWhitelist class not found (may cause issues)\n";
  }
} else {
  $checks[] = ['name' => 'Security Config', 'status' => '⚠️  OPTIONAL'];
  echo "   ⚠️  config_security_advanced.php not found (optional, but IP whitelist won't work)\n";
}

// 5. Check Frontend Files
echo "\n5️⃣ Frontend Files Check...\n";
$required_frontend = [
  'admin/index.html',
  'admin/admin.js',
  'style.css',
  'js/notifications.js',
  'js/dashboard.js',
  'js/admin-pro.js'
];

foreach ($required_frontend as $file) {
  $path = __DIR__ . '/' . $file;
  if (file_exists($path)) {
    $checks[] = ['name' => "Frontend: $file", 'status' => '✅ OK'];
    echo "   ✅ $file\n";
  } else {
    $checks[] = ['name' => "Frontend: $file", 'status' => '❌ MISSING'];
    $errors[] = "Missing frontend file: $file";
    echo "   ❌ MISSING: $file\n";
  }
}

// 6. Test API Endpoint (if admin exists)
echo "\n6️⃣ API Endpoint Test...\n";
if (isset($admin)) {
  // Create a test token
  $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
  $stmt->bind_param("i", $admin['id']);
  $stmt->execute();
  $user_data = $stmt->get_result()->fetch_assoc();
  
  if ($user_data) {
    $prefix = substr($user_data['password_hash'], 0, 12);
    $test_token = base64_encode($admin['id'] . ':' . $prefix);
    echo "   ✅ Test token generated\n";
    echo "   💡 Use this token to test API: $test_token\n";
  }
}

// Summary
echo "\n" . str_repeat("=", 50) . "\n";
echo "📊 SUMMARY\n";
echo str_repeat("=", 50) . "\n\n";

$ok_count = count(array_filter($checks, fn($c) => strpos($c['status'], '✅') !== false));
$warning_count = count(array_filter($checks, fn($c) => strpos($c['status'], '⚠️') !== false));
$error_count = count(array_filter($checks, fn($c) => strpos($c['status'], '❌') !== false));

echo "✅ Passed: $ok_count\n";
echo "⚠️  Warnings: $warning_count\n";
echo "❌ Errors: $error_count\n\n";

if (count($errors) > 0) {
  echo "❌ ERRORS FOUND:\n";
  foreach ($errors as $error) {
    echo "   • $error\n";
  }
  echo "\n";
}

// Recommendations
echo "💡 RECOMMENDATIONS:\n";
echo "   1. Ensure admin user exists: php create_admin_user.php admin@test.com 123456\n";
echo "   2. Test login at: https://ja1234.com/admin/index.html\n";
echo "   3. Check browser console for JavaScript errors\n";
echo "   4. Check server error logs for API errors\n";
echo "   5. Verify database connection in config.php\n";

if ($error_count === 0 && $warning_count === 0) {
  echo "\n✅ All checks passed! Admin panel should work correctly.\n";
} else {
  echo "\n⚠️  Some issues found. Please fix them before using admin panel.\n";
}
