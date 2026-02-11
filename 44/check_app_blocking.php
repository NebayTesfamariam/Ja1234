<?php
/**
 * Check App Blocking Configuration
 * Verifies that apps cannot bypass DNS/whitelist
 */

echo "🔍 APP BLOCKING CHECK\n";
echo "====================\n\n";

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
  $errors[] = "DNS server is not running - apps can bypass DNS";
  echo "   ❌ DNS server is NOT running\n";
  echo "   💡 Start: sudo python3 dns_whitelist_server.py\n";
}

// 2. Check Firewall Rules (if on Linux)
echo "\n2️⃣ Firewall Rules Check...\n";
if (PHP_OS_FAMILY === 'Linux') {
  // Check DNS forcing
  exec("sudo iptables -S FORWARD 2>/dev/null | grep -E 'dport 53.*DROP'", $dns_drop);
  exec("sudo iptables -S FORWARD 2>/dev/null | grep -E 'dport 53.*ACCEPT.*10.10.0.1'", $dns_allow);
  
  if (count($dns_drop) > 0 && count($dns_allow) > 0) {
    $checks[] = ['name' => 'DNS Forcing', 'status' => '✅ OK'];
    echo "   ✅ DNS forcing is active\n";
  } else {
    $checks[] = ['name' => 'DNS Forcing', 'status' => '⚠️  NOT ACTIVE'];
    $errors[] = "DNS forcing not active - apps can use alternative DNS";
    echo "   ⚠️  DNS forcing is NOT active\n";
    echo "   💡 Run: sudo ./vpn_firewall_app_blocking.sh\n";
  }
  
  // Check QUIC blocking
  exec("sudo iptables -S FORWARD 2>/dev/null | grep -E 'udp.*443.*DROP'", $quic_rules);
  if (count($quic_rules) > 0) {
    $checks[] = ['name' => 'QUIC Blocking', 'status' => '✅ OK'];
    echo "   ✅ QUIC (UDP 443) is blocked\n";
  } else {
    $checks[] = ['name' => 'QUIC Blocking', 'status' => '⚠️  NOT BLOCKED'];
    $errors[] = "QUIC not blocked - apps can use QUIC for video streaming";
    echo "   ⚠️  QUIC (UDP 443) is NOT blocked\n";
    echo "   💡 Block with: sudo iptables -A FORWARD -s 10.10.0.0/24 -p udp --dport 443 -j DROP\n";
  }
  
  // Check DoT blocking
  exec("sudo iptables -S FORWARD 2>/dev/null | grep -E 'tcp.*853.*DROP'", $dot_rules);
  if (count($dot_rules) > 0) {
    $checks[] = ['name' => 'DNS-over-TLS Blocking', 'status' => '✅ OK'];
    echo "   ✅ DNS-over-TLS (TCP 853) is blocked\n";
  } else {
    $checks[] = ['name' => 'DNS-over-TLS Blocking', 'status' => '⚠️  NOT BLOCKED'];
    $errors[] = "DNS-over-TLS not blocked - apps can bypass DNS";
    echo "   ⚠️  DNS-over-TLS (TCP 853) is NOT blocked\n";
    echo "   💡 Block with: sudo iptables -A FORWARD -s 10.10.0.0/24 -p tcp --dport 853 -j DROP\n";
  }
  
  // Check direct IP blocking
  exec("sudo iptables -S FORWARD 2>/dev/null | grep -E 'Host:.*ACCEPT'", $host_rules);
  exec("sudo iptables -S FORWARD 2>/dev/null | grep -E 'dport 443.*NEW.*DROP'", $ip_drop);
  
  if (count($host_rules) > 0 && count($ip_drop) > 0) {
    $checks[] = ['name' => 'Direct IP Blocking', 'status' => '✅ OK'];
    echo "   ✅ Direct IP access is blocked\n";
  } else {
    $checks[] = ['name' => 'Direct IP Blocking', 'status' => '⚠️  NOT BLOCKED'];
    $errors[] = "Direct IP access not blocked - apps can bypass DNS";
    echo "   ⚠️  Direct IP access is NOT blocked\n";
    echo "   💡 Block with: sudo ./vpn_firewall_app_blocking.sh\n";
  }
  
  // Check app bypass methods
  exec("sudo iptables -S FORWARD 2>/dev/null | grep -E 'dport 5353.*DROP'", $mdns_rules);
  if (count($mdns_rules) > 0) {
    $checks[] = ['name' => 'mDNS Blocking', 'status' => '✅ OK'];
    echo "   ✅ mDNS (UDP 5353) is blocked\n";
  } else {
    $checks[] = ['name' => 'mDNS Blocking', 'status' => '⚠️  NOT BLOCKED'];
    echo "   ⚠️  mDNS (UDP 5353) is NOT blocked\n";
  }
} else {
  $checks[] = ['name' => 'Firewall Rules', 'status' => '⚠️  SKIP (Not Linux)'];
  echo "   ⚠️  Firewall check skipped (not Linux)\n";
  echo "   💡 On VPN server (Linux), run: sudo ./vpn_firewall_app_blocking.sh\n";
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
if (PHP_OS_FAMILY === 'Linux') {
  echo "   2. Run app blocking script: sudo ./vpn_firewall_app_blocking.sh\n";
  echo "   3. Or update firewall: sudo ./vpn_firewall_setup.sh\n";
}
echo "   4. Test: Try to use app with direct IP (should fail)\n";
echo "   5. Test: Try to use alternative DNS (should fail)\n";
echo "   6. Test: Try to load video via QUIC (should fail)\n";

if ($error_count === 0 && $warning_count === 0) {
  echo "\n✅ All checks passed! Apps cannot bypass DNS/whitelist.\n";
} else {
  echo "\n⚠️  Some issues found. Apps may be able to bypass blocking.\n";
}
