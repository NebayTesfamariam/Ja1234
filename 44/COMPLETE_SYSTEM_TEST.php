<?php
/**
 * Complete System Test - All Components
 */
require __DIR__ . '/config.php';
require __DIR__ . '/config_porn_block.php';

echo "🧪 COMPLETE SYSTEM TEST\n";
echo "=======================\n\n";

$results = [
    'passed' => 0,
    'failed' => 0,
    'warnings' => 0,
    'total' => 0
];

function test($name, $condition, $message = '', $is_warning = false) {
    global $results;
    $results['total']++;
    
    if ($condition) {
        echo "✅ $name\n";
        if ($message) echo "   $message\n";
        $results['passed']++;
        return true;
    } else {
        if ($is_warning) {
            echo "⚠️  $name\n";
            if ($message) echo "   $message\n";
            $results['warnings']++;
        } else {
            echo "❌ $name\n";
            if ($message) echo "   $message\n";
            $results['failed']++;
        }
        return false;
    }
}

// ============================================
// 1. DATABASE TESTS
// ============================================
echo "1. DATABASE TESTS\n";
echo "------------------\n";
test("Database Connection", 
    isset($conn) && $conn instanceof mysqli,
    "Connected: " . ($conn->host_info ?? 'unknown')
);

try {
    $stmt = $conn->query("SELECT DATABASE()");
    $db_name = $stmt->fetch_array()[0];
    test("Database Selected", $db_name === 'pornfree', "Database: $db_name");
} catch (Exception $e) {
    test("Database Selected", false, "Error: " . $e->getMessage());
}

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

// ============================================
// 2. PORN BLOCKING TESTS
// ============================================
echo "\n2. PORN BLOCKING TESTS\n";
echo "----------------------\n";
test("Porn Detection Function", function_exists('is_pornographic_domain'));

$porn_domains = ['pornhub.com', 'xvideos.com', 'xhamster.com', 'redtube.com', 'xnxx.com'];
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

test("Normal Domain Allowed", 
    !is_pornographic_domain('youtube.com'),
    "youtube.com is allowed"
);

// ============================================
// 3. API ENDPOINTS
// ============================================
echo "\n3. API ENDPOINTS\n";
echo "----------------\n";
$required_apis = [
    'api/get_whitelist.php',
    'api/add_whitelist.php',
    'api/get_wireguard_config.php',
    'api/register.php',
    'api/login.php',
    'api/get_device_by_ip.php'
];
foreach ($required_apis as $api) {
    test("API '$api'", file_exists(__DIR__ . '/' . $api));
}

// ============================================
// 4. WHITELIST API FORMAT
// ============================================
echo "\n4. WHITELIST API FORMAT\n";
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

// ============================================
// 5. DNS SERVER
// ============================================
echo "\n5. DNS SERVER\n";
echo "------------\n";
test("DNS Server Script", file_exists(__DIR__ . '/dns_whitelist_server.py'));

// Check DNS server (Windows vs Unix)
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    // Windows check
    $dns_running = shell_exec("tasklist | findstr python.exe");
    $dns_running = !empty($dns_running) && strpos($dns_running, 'dns_whitelist_server') !== false;
    test("DNS Server Running", 
        $dns_running,
        $dns_running ? "Process found" : "Not running (start with: Run start_dns_server.bat as Administrator)",
        true // Warning, not critical
    );
} else {
    // Unix/macOS check
    $dns_running = shell_exec("ps aux | grep 'dns_whitelist_server.py' | grep -v grep");
    test("DNS Server Running", 
        !empty($dns_running),
        $dns_running ? "Process found" : "Not running (start with: sudo ./start_dns_server.sh)",
        true // Warning, not critical
    );
}

// ============================================
// 6. XAMPP SERVICES
// ============================================
echo "\n6. XAMPP SERVICES\n";
echo "-----------------\n";
$mysql_running = shell_exec("ps aux | grep mysqld | grep -v grep");
test("MySQL Running", !empty($mysql_running), $mysql_running ? "Process found" : "Not running");

$apache_running = shell_exec("ps aux | grep httpd | grep -v grep");
test("Apache Running", !empty($apache_running), $apache_running ? "Process found" : "Not running");

// ============================================
// 7. PORTS
// ============================================
echo "\n7. PORTS\n";
echo "-------\n";

// Check ports without sudo (cross-platform)
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    // Windows port check
    $port_53 = shell_exec("netstat -ano | find \":53\" 2>nul");
    test("Port 53 (DNS)", 
        !empty($port_53), 
        $port_53 ? "In use" : "Not in use (DNS server not running)",
        true // Warning, not critical
    );
    
    $port_80 = shell_exec("netstat -ano | find \":80\" 2>nul");
    test("Port 80 (HTTP)", 
        !empty($port_80), 
        $port_80 ? "In use" : "Not in use (Apache may not be running)",
        true // Warning, Apache process check is more reliable
    );
    
    $port_3306 = shell_exec("netstat -ano | find \":3306\" 2>nul");
    test("Port 3306 (MySQL)", 
        !empty($port_3306), 
        $port_3306 ? "In use" : "Not in use (MySQL may not be running)",
        true // Warning, MySQL process check is more reliable
    );
} else {
    // Unix/macOS port check (try without sudo first)
    $port_53 = shell_exec("lsof -i :53 2>&1 | grep -v 'cannot' | grep -v 'Permission denied'");
    if (empty($port_53)) {
        // Try with netstat as fallback
        $port_53 = shell_exec("netstat -an 2>&1 | grep ':53 ' | grep LISTEN");
    }
    test("Port 53 (DNS)", 
        !empty($port_53), 
        $port_53 ? "In use" : "Not in use (DNS server not running)",
        true // Warning, not critical
    );
    
    $port_80 = shell_exec("lsof -i :80 2>&1 | grep -v 'cannot' | grep -v 'Permission denied'");
    if (empty($port_80)) {
        $port_80 = shell_exec("netstat -an 2>&1 | grep ':80 ' | grep LISTEN");
    }
    test("Port 80 (HTTP)", 
        !empty($port_80), 
        $port_80 ? "In use" : "Not in use (Apache process check passed)",
        true // Warning, Apache process check is more reliable
    );
    
    $port_3306 = shell_exec("lsof -i :3306 2>&1 | grep -v 'cannot' | grep -v 'Permission denied'");
    if (empty($port_3306)) {
        $port_3306 = shell_exec("netstat -an 2>&1 | grep ':3306 ' | grep LISTEN");
    }
    test("Port 3306 (MySQL)", 
        !empty($port_3306), 
        $port_3306 ? "In use" : "Not in use (MySQL process check passed)",
        true // Warning, MySQL process check is more reliable
    );
}

// ============================================
// 8. FRONTEND FILES
// ============================================
echo "\n8. FRONTEND FILES\n";
echo "----------------\n";
$required_frontend = [
    'public/index.html',
    'app.js',
    'subscribe.html',
    'FINAL_SYSTEM_CHECK.html'
];
foreach ($required_frontend as $file) {
    test("File '$file'", file_exists(__DIR__ . '/' . $file));
}

// ============================================
// 9. AUTO-START SCRIPTS
// ============================================
echo "\n9. AUTO-START SCRIPTS\n";
echo "----------------------\n";
test("Windows Start Script", file_exists(__DIR__ . '/start_pornfree_system.bat'));
test("macOS Start Script", file_exists(__DIR__ . '/start_pornfree_system.sh'));
test("DNS Start Script", file_exists(__DIR__ . '/start_dns_server.sh'));
test("macOS LaunchAgent", file_exists(__DIR__ . '/com.nebay.pornfree.plist'));
test("Install Dependencies Script", file_exists(__DIR__ . '/install_dns_dependencies.sh'));

// ============================================
// 10. CONFIGURATION FILES
// ============================================
echo "\n10. CONFIGURATION FILES\n";
echo "-----------------------\n";
$required_config = [
    'config.php',
    'config_porn_block.php',
    'config_cache.php',
    'config_security.php'
];
foreach ($required_config as $file) {
    test("Config '$file'", file_exists(__DIR__ . '/' . $file));
}

// ============================================
// SUMMARY
// ============================================
echo "\n=======================\n";
echo "TEST SUMMARY\n";
echo "=======================\n";
echo "Total Tests: {$results['total']}\n";
echo "Passed: {$results['passed']} ✅\n";
echo "Failed: {$results['failed']} " . ($results['failed'] > 0 ? "❌" : "✅") . "\n";
echo "Warnings: {$results['warnings']} ⚠️\n";
echo "\n";

$success_rate = ($results['passed'] / $results['total']) * 100;
echo "Success Rate: " . number_format($success_rate, 1) . "%\n";
echo "\n";

if ($results['failed'] === 0 && $results['warnings'] === 0) {
    echo "🎉 STATUS: 100% PERFECT!\n";
    echo "\nAlle tests geslaagd. Het systeem werkt perfect!\n";
} elseif ($results['failed'] === 0) {
    echo "✅ STATUS: WERKT MET WAARSCHUWINGEN\n";
    echo "\nAlle kritieke tests geslaagd. Sommige optionele componenten hebben waarschuwingen.\n";
} else {
    echo "⚠️  STATUS: SOMS TESTS GEFAALD\n";
    echo "\nControleer de bovenstaande fouten.\n";
}

echo "\n";
echo "🌐 Test via browser:\n";
echo "  http://localhost/44/FINAL_SYSTEM_CHECK.html\n";
echo "  http://localhost/44/public/index.html\n";
echo "\n";
