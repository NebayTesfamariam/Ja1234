<?php
/**
 * Check Pornographic Video Blocking
 * Verifies all components are working correctly
 */

require __DIR__ . '/config.php';
require __DIR__ . '/config_porn_block.php';

echo "🔍 PORN VIDEO BLOCKING CHECK\n";
echo "===========================\n\n";

$checks = [];
$errors = [];

// 1. Check DNS Server
echo "1️⃣ DNS Server Check...\n";
$dns_running = false;
exec("ps aux | grep '[d]ns_whitelist_server.py'", $output);
if (count($output) > 0) {
  $checks[] = ['name' => 'DNS Server Running', 'status' => '✅ OK'];
  echo "   ✅ DNS server is running\n";
  $dns_running = true;
} else {
  $checks[] = ['name' => 'DNS Server Running', 'status' => '❌ NOT RUNNING'];
  $errors[] = "DNS server is not running - start with: sudo python3 dns_whitelist_server.py";
  echo "   ❌ DNS server is NOT running\n";
  echo "   💡 Start: sudo python3 dns_whitelist_server.py\n";
}

// 2. Check Porn Domain Detection
echo "\n2️⃣ Porn Domain Detection Check...\n";
$test_domains = [
  'pornhub.com' => true,
  'xvideos.com' => true,
  'xhamster.com' => true,
  'phncdn.com' => true,  // Video CDN
  'xvcdn.com' => true,   // Video CDN
  'google.com' => false,
  'wikipedia.org' => false
];

$detection_ok = true;
foreach ($test_domains as $domain => $should_block) {
  $is_porn = is_pornographic_domain($domain);
  if ($is_porn === $should_block) {
    echo "   ✅ {$domain}: " . ($is_porn ? 'BLOCKED' : 'ALLOWED') . "\n";
  } else {
    $detection_ok = false;
    echo "   ❌ {$domain}: Expected " . ($should_block ? 'BLOCKED' : 'ALLOWED') . ", got " . ($is_porn ? 'BLOCKED' : 'ALLOWED') . "\n";
    $errors[] = "Porn domain detection failed for: {$domain}";
  }
}

if ($detection_ok) {
  $checks[] = ['name' => 'Porn Domain Detection', 'status' => '✅ OK'];
} else {
  $checks[] = ['name' => 'Porn Domain Detection', 'status' => '❌ FAILED'];
}

// 3. Check Whitelist for Porn Domains
echo "\n3️⃣ Whitelist Cleanup Check...\n";
try {
  $stmt = $conn->prepare("SELECT COUNT(*) as count FROM whitelist");
  $stmt->execute();
  $total = $stmt->get_result()->fetch_assoc()['count'];
  
  $stmt = $conn->prepare("SELECT id, domain FROM whitelist");
  $stmt->execute();
  $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  
  $porn_in_whitelist = [];
  foreach ($rows as $row) {
    if (is_pornographic_domain($row['domain'])) {
      $porn_in_whitelist[] = $row['domain'];
    }
  }
  
  if (count($porn_in_whitelist) === 0) {
    $checks[] = ['name' => 'Whitelist Clean', 'status' => '✅ OK'];
    echo "   ✅ Whitelist is clean (no porn domains)\n";
  } else {
    $checks[] = ['name' => 'Whitelist Clean', 'status' => '⚠️  WARNING'];
    echo "   ⚠️  Found " . count($porn_in_whitelist) . " porn domains in whitelist:\n";
    foreach ($porn_in_whitelist as $domain) {
      echo "      • {$domain}\n";
    }
    echo "   💡 Remove with: php -r \"require 'config.php'; require 'config_porn_block.php'; remove_pornographic_domains_from_whitelist(\$conn);\"\n";
    $errors[] = "Porn domains found in whitelist: " . implode(', ', $porn_in_whitelist);
  }
} catch (Exception $e) {
  $checks[] = ['name' => 'Whitelist Check', 'status' => '❌ ERROR', 'error' => $e->getMessage()];
  $errors[] = "Whitelist check failed: " . $e->getMessage();
  echo "   ❌ Error checking whitelist: " . $e->getMessage() . "\n";
}

// 4. Check API Porn Blocking
echo "\n4️⃣ API Porn Blocking Check...\n";
if (function_exists('is_pornographic_domain')) {
  $checks[] = ['name' => 'API Porn Blocking', 'status' => '✅ OK'];
  echo "   ✅ API porn blocking function exists\n";
} else {
  $checks[] = ['name' => 'API Porn Blocking', 'status' => '❌ MISSING'];
  $errors[] = "API porn blocking function not found";
  echo "   ❌ API porn blocking function not found\n";
}

// 5. Check Firewall Rules (if on Linux)
echo "\n5️⃣ Firewall Rules Check...\n";
if (PHP_OS_FAMILY === 'Linux') {
  // Check QUIC blocking
  exec("sudo iptables -S FORWARD 2>/dev/null | grep -E 'udp.*443.*DROP'", $quic_rules);
  if (count($quic_rules) > 0) {
    $checks[] = ['name' => 'QUIC Blocking', 'status' => '✅ OK'];
    echo "   ✅ QUIC (UDP 443) is blocked\n";
  } else {
    $checks[] = ['name' => 'QUIC Blocking', 'status' => '⚠️  NOT BLOCKED'];
    $errors[] = "QUIC (UDP 443) is not blocked - videos can still load via QUIC";
    echo "   ⚠️  QUIC (UDP 443) is NOT blocked\n";
    echo "   💡 Block with: sudo iptables -A FORWARD -s 10.10.0.0/24 -p udp --dport 443 -j DROP\n";
  }
  
  // Check DNS-over-TLS blocking
  exec("sudo iptables -S FORWARD 2>/dev/null | grep -E 'tcp.*853.*DROP'", $dot_rules);
  if (count($dot_rules) > 0) {
    $checks[] = ['name' => 'DNS-over-TLS Blocking', 'status' => '✅ OK'];
    echo "   ✅ DNS-over-TLS (TCP 853) is blocked\n";
  } else {
    $checks[] = ['name' => 'DNS-over-TLS Blocking', 'status' => '⚠️  NOT BLOCKED'];
    echo "   ⚠️  DNS-over-TLS (TCP 853) is NOT blocked\n";
    echo "   💡 Block with: sudo iptables -A FORWARD -s 10.10.0.0/24 -p tcp --dport 853 -j DROP\n";
  }
} else {
  $checks[] = ['name' => 'Firewall Rules', 'status' => '⚠️  SKIP (Not Linux)'];
  echo "   ⚠️  Firewall check skipped (not Linux)\n";
  echo "   💡 On VPN server (Linux), check firewall rules manually\n";
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
if (!$dns_running) {
  echo "   1. Start DNS server: sudo python3 dns_whitelist_server.py\n";
}
if (count($porn_in_whitelist ?? []) > 0) {
  echo "   2. Remove porn domains from whitelist\n";
}
if (PHP_OS_FAMILY === 'Linux' && count($quic_rules ?? []) === 0) {
  echo "   3. Block QUIC (UDP 443) on VPN server: sudo ./vpn_firewall_setup.sh\n";
}
echo "   4. Test: nslookup pornhub.com 10.10.0.1 (should return NXDOMAIN)\n";
echo "   5. Test: Try to load porn video on VPN client (should not load)\n";

if ($error_count === 0 && $warning_count === 0) {
  echo "\n✅ All checks passed! Porn video blocking should work correctly.\n";
} else {
  echo "\n⚠️  Some issues found. Please fix them for complete porn video blocking.\n";
}
