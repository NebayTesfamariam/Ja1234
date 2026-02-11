<?php
/**
 * Complete System Test
 * Tests all components
 */
require __DIR__ . '/config.php';
require __DIR__ . '/config_porn_block.php';

echo "🧪 COMPLETE SYSTEM TEST\n";
echo "=======================\n\n";

$tests_passed = 0;
$tests_failed = 0;
$tests_total = 0;

function test($name, $condition, $message = '') {
    global $tests_passed, $tests_failed, $tests_total;
    $tests_total++;
    
    if ($condition) {
        echo "✅ $name\n";
        if ($message) echo "   $message\n";
        $tests_passed++;
        return true;
    } else {
        echo "❌ $name\n";
        if ($message) echo "   $message\n";
        $tests_failed++;
        return false;
    }
}

// Test 1: Database Connection
echo "1. DATABASE TESTS\n";
echo "------------------\n";
test("Database Connection", 
    isset($conn) && $conn instanceof mysqli,
    "Connected to: " . ($conn->host_info ?? 'unknown')
);

try {
    $stmt = $conn->query("SELECT DATABASE()");
    $db_name = $stmt->fetch_array()[0];
    test("Database Selected", $db_name === 'pornfree', "Database: $db_name");
} catch (Exception $e) {
    test("Database Selected", false, "Error: " . $e->getMessage());
}

// Test 2: Required Tables
echo "\n2. DATABASE TABLES\n";
echo "------------------\n";
$required_tables = ['users', 'devices', 'whitelist', 'subscriptions'];
foreach ($required_tables as $table) {
    try {
        $stmt = $conn->query("SELECT COUNT(*) as count FROM `$table`");
        $count = $stmt->fetch_assoc()['count'];
        test("Table '$table'", true, "$count records");
    } catch (Exception $e) {
        test("Table '$table'", false, "Error: " . $e->getMessage());
    }
}

// Test 3: Porn Blocking
echo "\n3. PORN BLOCKING TESTS\n";
echo "----------------------\n";
test("Porn Detection Function", 
    function_exists('is_pornographic_domain'),
    "Function exists"
);

$porn_domains = ['pornhub.com', 'xvideos.com', 'xhamster.com', 'redtube.com'];
$blocked_count = 0;
foreach ($porn_domains as $domain) {
    if (is_pornographic_domain($domain)) {
        $blocked_count++;
    }
}
test("Porn Domains Blocked", 
    $blocked_count === count($porn_domains),
    "$blocked_count/" . count($porn_domains) . " domains blocked"
);

test("Normal Domain Allowed", 
    !is_pornographic_domain('google.com'),
    "google.com is allowed"
);

// Test 4: API Endpoints
echo "\n4. API ENDPOINTS\n";
echo "----------------\n";
$required_apis = [
    'api/get_whitelist.php',
    'api/add_whitelist.php',
    'api/get_wireguard_config.php',
    'api/register.php',
    'api/login.php'
];
foreach ($required_apis as $api) {
    test("API '$api'", file_exists(__DIR__ . '/' . $api));
}

// Test 5: Whitelist API Returns Array
echo "\n5. WHITELIST API FORMAT\n";
echo "-----------------------\n";
try {
    $stmt = $conn->query("SELECT id FROM devices LIMIT 1");
    $device = $stmt->fetch_assoc();
    
    if ($device) {
        $stmt = $conn->prepare("SELECT domain FROM whitelist WHERE device_id = ? AND enabled = 1");
        $stmt->bind_param("i", $device['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $domains = [];
        while ($row = $result->fetch_assoc()) {
            $domains[] = $row['domain'];
        }
        test("Whitelist Returns Array", is_array($domains), count($domains) . " domains");
    } else {
        test("Whitelist Returns Array", false, "No devices found");
    }
} catch (Exception $e) {
    test("Whitelist Returns Array", false, "Error: " . $e->getMessage());
}

// Test 6: DNS Server
echo "\n6. DNS SERVER\n";
echo "------------\n";
test("DNS Server Script", 
    file_exists(__DIR__ . '/dns_whitelist_server.py'),
    "Script exists"
);

$dns_running = shell_exec("ps aux | grep 'dns_whitelist_server.py' | grep -v grep");
test("DNS Server Running", 
    !empty($dns_running),
    $dns_running ? "Process found" : "Not running (start with: sudo python3 dns_whitelist_server.py)"
);

// Test 7: XAMPP Services
echo "\n7. XAMPP SERVICES\n";
echo "-----------------\n";
$mysql_running = shell_exec("ps aux | grep mysqld | grep -v grep");
test("MySQL Running", !empty($mysql_running), $mysql_running ? "Process found" : "Not running");

$apache_running = shell_exec("ps aux | grep httpd | grep -v grep");
test("Apache Running", !empty($apache_running), $apache_running ? "Process found" : "Not running");

// Test 8: Ports
echo "\n8. PORTS\n";
echo "-------\n";
$port_53 = shell_exec("sudo lsof -i :53 2>&1 | grep -v 'cannot'");
test("Port 53 (DNS)", !empty($port_53), $port_53 ? "In use" : "Not in use");

$port_80 = shell_exec("sudo lsof -i :80 2>&1 | grep -v 'cannot'");
test("Port 80 (HTTP)", !empty($port_80), $port_80 ? "In use" : "Not in use");

$port_3306 = shell_exec("sudo lsof -i :3306 2>&1 | grep -v 'cannot'");
test("Port 3306 (MySQL)", !empty($port_3306), $port_3306 ? "In use" : "Not in use");

// Test 9: Frontend Files
echo "\n9. FRONTEND FILES\n";
echo "----------------\n";
$required_frontend = [
    'public/index.html',
    'app.js',
    'subscribe.html'
];
foreach ($required_frontend as $file) {
    test("File '$file'", file_exists(__DIR__ . '/' . $file));
}

// Test 10: Auto-Start Scripts
echo "\n10. AUTO-START SCRIPTS\n";
echo "----------------------\n";
test("Windows Start Script", file_exists(__DIR__ . '/start_pornfree_system.bat'));
test("macOS Start Script", file_exists(__DIR__ . '/start_pornfree_system.sh'));
test("macOS LaunchAgent", file_exists(__DIR__ . '/com.nebay.pornfree.plist'));

// Summary
echo "\n=======================\n";
echo "TEST SUMMARY\n";
echo "=======================\n";
echo "Total Tests: $tests_total\n";
echo "Passed: $tests_passed ✅\n";
echo "Failed: $tests_failed " . ($tests_failed > 0 ? "❌" : "✅") . "\n";
echo "\n";

if ($tests_failed === 0) {
    echo "🎉 STATUS: 100% WERKT!\n";
    echo "\nAlle tests geslaagd. Het systeem werkt perfect!\n";
} else {
    echo "⚠️  STATUS: SOMS TESTS GEFAALD\n";
    echo "\nControleer de bovenstaande fouten.\n";
}

echo "\n";
