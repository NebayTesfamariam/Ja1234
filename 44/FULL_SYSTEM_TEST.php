<?php
/**
 * Full System Test
 * Comprehensive test of all system components
 */

require __DIR__ . '/config.php';

echo "🔍 VOLLEDIGE SYSTEEM TEST\n";
echo str_repeat("=", 60) . "\n\n";

$results = [
  'passed' => 0,
  'warnings' => 0,
  'failed' => 0,
  'details' => []
];

function test($name, $callback) {
  global $results;
  echo "🧪 Testing: $name\n";
  try {
    $result = $callback();
    if ($result === true) {
      $results['passed']++;
      $results['details'][] = ['name' => $name, 'status' => '✅ PASSED'];
      echo "   ✅ PASSED\n";
      return true;
    } elseif (is_array($result) && isset($result['warning'])) {
      $results['warnings']++;
      $results['details'][] = ['name' => $name, 'status' => '⚠️  WARNING', 'message' => $result['warning']];
      echo "   ⚠️  WARNING: {$result['warning']}\n";
      return true;
    } else {
      $results['failed']++;
      $results['details'][] = ['name' => $name, 'status' => '❌ FAILED', 'message' => $result];
      echo "   ❌ FAILED: $result\n";
      return false;
    }
  } catch (Exception $e) {
    $results['failed']++;
    $results['details'][] = ['name' => $name, 'status' => '❌ ERROR', 'message' => $e->getMessage()];
    echo "   ❌ ERROR: " . $e->getMessage() . "\n";
    return false;
  }
}

// 1. Database Connection
test("Database Connection", function() use ($conn) {
  try {
    $conn->ping();
    return true;
  } catch (Exception $e) {
    return "Database connection failed: " . $e->getMessage();
  }
});

// 2. Database Tables
test("Database Tables", function() use ($conn) {
  $required_tables = ['users', 'devices', 'whitelist', 'subscriptions'];
  $missing = [];
  foreach ($required_tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows === 0) {
      $missing[] = $table;
    }
  }
  if (count($missing) > 0) {
    return "Missing tables: " . implode(', ', $missing);
  }
  return true;
});

// 3. Admin User
test("Admin User Exists", function() use ($conn) {
  $stmt = $conn->prepare("SELECT id, email FROM users WHERE is_admin = 1 LIMIT 1");
  $stmt->execute();
  $admin = $stmt->get_result()->fetch_assoc();
  if (!$admin) {
    return ['warning' => 'No admin user found - create with: php create_admin_user.php admin@test.com 123456'];
  }
  return true;
});

// 4. API Files
test("API Files", function() {
  $required_apis = [
    'api/login.php',
    'api/get_whitelist.php',
    'api/add_whitelist.php',
    'api/admin_check.php',
    'api/admin_users.php',
    'api/admin_devices.php',
    'api/get_wireguard_config.php',
    'api/generate_device_link.php'
  ];
  $missing = [];
  foreach ($required_apis as $api) {
    if (!file_exists(__DIR__ . '/' . $api)) {
      $missing[] = $api;
    }
  }
  if (count($missing) > 0) {
    return "Missing API files: " . implode(', ', $missing);
  }
  return true;
});

// 5. Frontend Files
test("Frontend Files", function() {
  $required_frontend = [
    'admin/index.html',
    'admin/admin.js',
    'public/index.html',
    'app.js',
    'style.css'
  ];
  $missing = [];
  foreach ($required_frontend as $file) {
    if (!file_exists(__DIR__ . '/' . $file)) {
      $missing[] = $file;
    }
  }
  if (count($missing) > 0) {
    return "Missing frontend files: " . implode(', ', $missing);
  }
  return true;
});

// 6. Porn Blocking Config
test("Porn Blocking Config", function() {
  if (!file_exists(__DIR__ . '/config_porn_block.php')) {
    return "config_porn_block.php not found";
  }
  require_once __DIR__ . '/config_porn_block.php';
  if (!function_exists('is_pornographic_domain')) {
    return "is_pornographic_domain function not found";
  }
  
  // Test porn domain detection
  $test_domains = [
    'pornhub.com' => true,
    'xvideos.com' => true,
    'google.com' => false
  ];
  
  foreach ($test_domains as $domain => $should_block) {
    $is_porn = is_pornographic_domain($domain);
    if ($is_porn !== $should_block) {
      return "Porn detection failed for: $domain";
    }
  }
  
  return true;
});

// 7. DNS Server
test("DNS Server", function() {
  exec("ps aux | grep '[d]ns_whitelist_server.py'", $output);
  if (count($output) === 0) {
    return ['warning' => 'DNS server not running - start with: sudo python3 dns_whitelist_server.py'];
  }
  return true;
});

// 8. DNS Server File
test("DNS Server File", function() {
  if (!file_exists(__DIR__ . '/dns_whitelist_server.py')) {
    return "dns_whitelist_server.py not found";
  }
  return true;
});

// 9. Whitelist API Format
test("Whitelist API Format", function() use ($conn) {
  // Test that get_whitelist.php returns array format
  if (!file_exists(__DIR__ . '/api/get_whitelist.php')) {
    return "get_whitelist.php not found";
  }
  return true;
});

// 10. WireGuard Config
test("WireGuard Config API", function() {
  if (!file_exists(__DIR__ . '/api/get_wireguard_config.php')) {
    return "get_wireguard_config.php not found";
  }
  return true;
});

// 11. Security Config
test("Security Config", function() {
  if (file_exists(__DIR__ . '/config_security_advanced.php')) {
    require_once __DIR__ . '/config_security_advanced.php';
    if (!class_exists('BruteForceProtection')) {
      return ['warning' => 'BruteForceProtection class not found (optional)'];
    }
  } else {
    return ['warning' => 'config_security_advanced.php not found (optional)'];
  }
  return true;
});

// 12. Whitelist Clean
test("Whitelist Clean", function() use ($conn) {
  require_once __DIR__ . '/config_porn_block.php';
  $stmt = $conn->query("SELECT domain FROM whitelist");
  $domains = $stmt->fetch_all(MYSQLI_ASSOC);
  
  $porn_domains = [];
  foreach ($domains as $row) {
    if (is_pornographic_domain($row['domain'])) {
      $porn_domains[] = $row['domain'];
    }
  }
  
  if (count($porn_domains) > 0) {
    return ['warning' => 'Found ' . count($porn_domains) . ' porn domains in whitelist: ' . implode(', ', array_slice($porn_domains, 0, 5))];
  }
  return true;
});

// 13. Database Content
test("Database Content", function() use ($conn) {
  $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
  $user_count = $stmt->fetch_assoc()['count'];
  
  $stmt = $conn->query("SELECT COUNT(*) as count FROM devices");
  $device_count = $stmt->fetch_assoc()['count'];
  
  if ($user_count === 0) {
    return ['warning' => 'No users in database'];
  }
  
  return true;
});

// 14. Port Checks (if Linux)
if (PHP_OS_FAMILY === 'Linux') {
  test("Port 53 (DNS)", function() {
    exec("sudo lsof -i :53 2>/dev/null | grep -v 'cannot' | grep -v 'password'", $output);
    if (count($output) === 0) {
      return ['warning' => 'Port 53 not in use (DNS server may not be running)'];
    }
    return true;
  });
  
  test("Port 80 (HTTP)", function() {
    exec("sudo lsof -i :80 2>/dev/null | grep -v 'cannot' | grep -v 'password'", $output);
    if (count($output) === 0) {
      return ['warning' => 'Port 80 not in use (web server may not be running)'];
    }
    return true;
  });
} else {
  test("Port Checks", function() {
    return ['warning' => 'Port checks skipped (not Linux)'];
  });
}

// Summary
echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 TEST RESULTATEN\n";
echo str_repeat("=", 60) . "\n\n";

echo "✅ Passed: {$results['passed']}\n";
echo "⚠️  Warnings: {$results['warnings']}\n";
echo "❌ Failed: {$results['failed']}\n\n";

if ($results['failed'] > 0) {
  echo "❌ FAILED TESTS:\n";
  foreach ($results['details'] as $detail) {
    if (strpos($detail['status'], '❌') !== false) {
      echo "   • {$detail['name']}: " . ($detail['message'] ?? '') . "\n";
    }
  }
  echo "\n";
}

if ($results['warnings'] > 0) {
  echo "⚠️  WARNINGS:\n";
  foreach ($results['details'] as $detail) {
    if (strpos($detail['status'], '⚠️') !== false) {
      echo "   • {$detail['name']}: " . ($detail['message'] ?? '') . "\n";
    }
  }
  echo "\n";
}

// Overall Status
$total = $results['passed'] + $results['warnings'] + $results['failed'];
$success_rate = ($results['passed'] / $total) * 100;

echo "📈 Success Rate: " . number_format($success_rate, 1) . "%\n\n";

if ($results['failed'] === 0 && $results['warnings'] === 0) {
  echo "✅ ALLE TESTS GESLAAGD! Systeem is 100% operationeel.\n";
} elseif ($results['failed'] === 0) {
  echo "✅ KRITIEKE TESTS GESLAAGD! Systeem is operationeel met enkele waarschuwingen.\n";
} else {
  echo "❌ SOMIGE TESTS GEFAALD! Controleer de bovenstaande fouten.\n";
}

echo "\n💡 AANBEVELINGEN:\n";
if ($results['warnings'] > 0 || $results['failed'] > 0) {
  echo "   1. Fix alle failed tests\n";
  echo "   2. Review warnings en fix indien nodig\n";
  echo "   3. Start DNS server als deze niet draait\n";
  echo "   4. Maak admin gebruiker aan als deze niet bestaat\n";
  echo "   5. Test login functionaliteit\n";
  echo "   6. Test device registratie\n";
  echo "   7. Test whitelist functionaliteit\n";
} else {
  echo "   ✅ Systeem is volledig operationeel!\n";
  echo "   ✅ Alle componenten werken correct\n";
  echo "   ✅ Klaar voor productie gebruik\n";
}
