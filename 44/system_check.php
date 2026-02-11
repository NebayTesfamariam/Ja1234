<?php
/**
 * Comprehensive System Health Check
 * Checks all components of the whitelist-only filtering system
 */

header('Content-Type: application/json');

$checks = [];
$overall_status = 'ok';

// Helper function
function check_item($name, $status, $message, $details = []) {
    global $overall_status;
    if ($status !== 'ok') {
        $overall_status = 'error';
    }
    return [
        'name' => $name,
        'status' => $status,
        'message' => $message,
        'details' => $details
    ];
}

// 1. Check PHP Configuration
try {
    $php_version = PHP_VERSION;
    $php_ok = version_compare($php_version, '7.4.0', '>=');
    $checks[] = check_item(
        'PHP Version',
        $php_ok ? 'ok' : 'warning',
        $php_ok ? "PHP $php_version is OK" : "PHP $php_version is oud (min 7.4.0 vereist)",
        ['version' => $php_version]
    );
} catch (Exception $e) {
    $checks[] = check_item('PHP Version', 'error', 'Kon PHP versie niet controleren', ['error' => $e->getMessage()]);
}

// 2. Check Database Connection
try {
    require __DIR__ . '/config.php';
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->ping();
        $checks[] = check_item('Database Connection', 'ok', 'Database verbinding werkt', [
            'host' => $conn->host_info,
            'database' => $conn->query("SELECT DATABASE()")->fetch_row()[0] ?? 'unknown'
        ]);
    } else {
        $checks[] = check_item('Database Connection', 'error', 'Database verbinding niet gevonden');
    }
} catch (Exception $e) {
    $checks[] = check_item('Database Connection', 'error', 'Database verbinding faalt', ['error' => $e->getMessage()]);
}

// 3. Check Required Database Tables
if (isset($conn)) {
    $required_tables = ['users', 'devices', 'whitelist', 'subscriptions'];
    foreach ($required_tables as $table) {
        try {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            $exists = $result && $result->num_rows > 0;
            $checks[] = check_item(
                "Table: $table",
                $exists ? 'ok' : 'error',
                $exists ? "Tabel '$table' bestaat" : "Tabel '$table' ontbreekt"
            );
        } catch (Exception $e) {
            $checks[] = check_item("Table: $table", 'error', "Fout bij checken tabel '$table'", ['error' => $e->getMessage()]);
        }
    }
}

// 4. Check API Endpoints
$required_apis = [
    'get_whitelist.php',
    'get_device_by_ip.php',
    'add_whitelist.php',
    'get_devices.php',
    'login.php'
];

foreach ($required_apis as $api) {
    $path = __DIR__ . '/api/' . $api;
    $exists = file_exists($path);
    $checks[] = check_item(
        "API: $api",
        $exists ? 'ok' : 'error',
        $exists ? "API endpoint bestaat" : "API endpoint ontbreekt",
        ['path' => $path]
    );
}

// 5. Check DNS Server Script
$dns_script = __DIR__ . '/dns_whitelist_server.py';
$dns_exists = file_exists($dns_script);
$checks[] = check_item(
    'DNS Server Script',
    $dns_exists ? 'ok' : 'warning',
    $dns_exists ? 'DNS server script gevonden' : 'DNS server script niet gevonden',
    ['path' => $dns_script]
);

// 6. Check Firewall Scripts
$firewall_scripts = [
    'block_quic_udp443.sh',
    'block_dot_tcp853.sh',
    'force_dns_only.sh',
    'vpn_firewall_setup.sh'
];

foreach ($firewall_scripts as $script) {
    $path = __DIR__ . '/' . $script;
    $exists = file_exists($path);
    $checks[] = check_item(
        "Firewall Script: $script",
        $exists ? 'ok' : 'warning',
        $exists ? 'Script gevonden' : 'Script niet gevonden',
        ['path' => $path]
    );
}

// 7. Check get_whitelist.php Logic
try {
    // Test if get_whitelist.php returns array format
    $test_file = __DIR__ . '/api/get_whitelist.php';
    if (file_exists($test_file)) {
        $content = file_get_contents($test_file);
        $has_json_out = strpos($content, 'json_out') !== false;
        $has_array_return = strpos($content, 'json_out($domains') !== false || strpos($content, 'json_out([],') !== false;
        
        $checks[] = check_item(
            'get_whitelist.php Format',
            $has_array_return ? 'ok' : 'warning',
            $has_array_return ? 'Returns array format (whitelist-only)' : 'Mogelijk verkeerd format',
            ['has_json_out' => $has_json_out, 'has_array_return' => $has_array_return]
        );
    }
} catch (Exception $e) {
    $checks[] = check_item('get_whitelist.php Format', 'error', 'Kon niet controleren', ['error' => $e->getMessage()]);
}

// 8. Check Frontend Files
$frontend_files = [
    'app.js',
    'public/index.html',
    'admin/admin.js',
    'admin/index.html'
];

foreach ($frontend_files as $file) {
    $path = __DIR__ . '/' . $file;
    $exists = file_exists($path);
    $checks[] = check_item(
        "Frontend: $file",
        $exists ? 'ok' : 'warning',
        $exists ? 'Bestand gevonden' : 'Bestand niet gevonden',
        ['path' => $path]
    );
}

// 9. Check for Blocklist References (should be removed)
if (isset($conn)) {
    try {
        // Check if blocklist tables exist (they shouldn't in whitelist-only system)
        $result = $conn->query("SHOW TABLES LIKE 'blocklist%'");
        $blocklist_tables = $result ? $result->num_rows : 0;
        
        $checks[] = check_item(
            'Blocklist Tables',
            $blocklist_tables === 0 ? 'ok' : 'warning',
            $blocklist_tables === 0 ? 'Geen blocklist tabellen (whitelist-only)' : "$blocklist_tables blocklist tabel(len) gevonden",
            ['count' => $blocklist_tables]
        );
    } catch (Exception $e) {
        // Ignore
    }
}

// 10. Check File Permissions
$important_files = [
    'api/get_whitelist.php',
    'api/get_device_by_ip.php',
    'dns_whitelist_server.py'
];

foreach ($important_files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $readable = is_readable($path);
        $checks[] = check_item(
            "Permissions: $file",
            $readable ? 'ok' : 'error',
            $readable ? 'Bestand is leesbaar' : 'Bestand is niet leesbaar',
            ['readable' => $readable]
        );
    }
}

// Summary
$ok_count = count(array_filter($checks, fn($c) => $c['status'] === 'ok'));
$warning_count = count(array_filter($checks, fn($c) => $c['status'] === 'warning'));
$error_count = count(array_filter($checks, fn($c) => $c['status'] === 'error'));

echo json_encode([
    'status' => $overall_status,
    'summary' => [
        'total' => count($checks),
        'ok' => $ok_count,
        'warning' => $warning_count,
        'error' => $error_count
    ],
    'checks' => $checks,
    'timestamp' => date('Y-m-d H:i:s')
], JSON_PRETTY_PRINT);
