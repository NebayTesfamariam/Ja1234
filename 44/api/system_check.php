<?php
/**
 * System Health Check API
 * Checks all components without requiring authentication
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
    $checks[] = check_item('PHP Version', 'error', 'Kon PHP versie niet controleren');
}

// 2. Check Database Connection (try-catch to handle gracefully)
$db_connected = false;
try {
    require __DIR__ . '/../config.php';
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->ping();
        $db_connected = true;
        $db_name = $conn->query("SELECT DATABASE()")->fetch_row()[0] ?? 'unknown';
        $checks[] = check_item('Database Connection', 'ok', 'Database verbinding werkt', [
            'database' => $db_name
        ]);
    }
} catch (Exception $e) {
    $checks[] = check_item('Database Connection', 'warning', 'Database verbinding niet beschikbaar (mogelijk normaal in CLI)', [
        'error' => $e->getMessage()
    ]);
}

// 3. Check Required Database Tables (only if DB connected)
if ($db_connected) {
    $required_tables = ['users', 'devices', 'whitelist'];
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
            $checks[] = check_item("Table: $table", 'error', "Fout bij checken tabel '$table'");
        }
    }
    
    // Check for blocklist tables (should NOT exist in whitelist-only system)
    try {
        $result = $conn->query("SHOW TABLES LIKE 'blocklist%'");
        $blocklist_count = $result ? $result->num_rows : 0;
        $checks[] = check_item(
            'Blocklist Tables',
            $blocklist_count === 0 ? 'ok' : 'warning',
            $blocklist_count === 0 ? 'Geen blocklist tabellen (whitelist-only)' : "$blocklist_count blocklist tabel(len) gevonden"
        );
    } catch (Exception $e) {
        // Ignore
    }
}

// 4. Check API Endpoints
$required_apis = [
    'get_whitelist.php',
    'get_device_by_ip.php',
    'add_whitelist.php',
    'get_devices.php',
    'login.php',
    'auto_register_device.php'
];

foreach ($required_apis as $api) {
    $path = __DIR__ . '/' . $api;
    $exists = file_exists($path);
    $checks[] = check_item(
        "API: $api",
        $exists ? 'ok' : 'error',
        $exists ? "API endpoint bestaat" : "API endpoint ontbreekt"
    );
}

// 5. Check get_whitelist.php Format (whitelist-only)
$whitelist_file = __DIR__ . '/get_whitelist.php';
if (file_exists($whitelist_file)) {
    $content = file_get_contents($whitelist_file);
    $has_array_return = strpos($content, 'json_out($domains') !== false || strpos($content, 'json_out([],') !== false;
    $has_blocklist = stripos($content, 'blocklist') !== false;
    
    $checks[] = check_item(
        'get_whitelist.php Format',
        $has_array_return && !$has_blocklist ? 'ok' : 'warning',
        $has_array_return && !$has_blocklist ? 'Returns array format (whitelist-only)' : 'Mogelijk verkeerd format of blocklist referenties',
        [
            'has_array_return' => $has_array_return,
            'has_blocklist_refs' => $has_blocklist
        ]
    );
}

// 6. Check DNS Server Script
$dns_script = __DIR__ . '/../dns_whitelist_server.py';
$dns_exists = file_exists($dns_script);
if ($dns_exists) {
    $dns_content = file_get_contents($dns_script);
    $has_whitelist_logic = strpos($dns_content, 'get_whitelist_for_device') !== false;
    $has_nxdomain = strpos($dns_content, 'NXDOMAIN') !== false;
    
    $checks[] = check_item(
        'DNS Server Script',
        $has_whitelist_logic && $has_nxdomain ? 'ok' : 'warning',
        $has_whitelist_logic && $has_nxdomain ? 'DNS server script heeft whitelist logica' : 'DNS server script mist belangrijke logica',
        [
            'has_whitelist_logic' => $has_whitelist_logic,
            'has_nxdomain' => $has_nxdomain
        ]
    );
} else {
    $checks[] = check_item('DNS Server Script', 'warning', 'DNS server script niet gevonden');
}

// 7. Check Firewall Scripts
$firewall_scripts = [
    'block_quic_udp443.sh',
    'block_dot_tcp853.sh',
    'force_dns_only.sh'
];

foreach ($firewall_scripts as $script) {
    $path = __DIR__ . '/../' . $script;
    $exists = file_exists($path);
    $executable = $exists && is_executable($path);
    
    $checks[] = check_item(
        "Firewall Script: $script",
        $exists && $executable ? 'ok' : ($exists ? 'warning' : 'error'),
        $exists && $executable ? 'Script bestaat en is uitvoerbaar' : ($exists ? 'Script bestaat maar is niet uitvoerbaar' : 'Script niet gevonden')
    );
}

// 8. Check Frontend Files
$frontend_files = [
    'app.js',
    'public/index.html',
    'admin/admin.js'
];

foreach ($frontend_files as $file) {
    $path = __DIR__ . '/../' . $file;
    $exists = file_exists($path);
    
    if ($exists && $file === 'app.js') {
        // Check if autoAddDevice has API call
        $content = file_get_contents($path);
        $has_auto_add_api = strpos($content, 'auto_register_device.php') !== false;
        $checks[] = check_item(
            "Frontend: $file",
            $has_auto_add_api ? 'ok' : 'warning',
            $has_auto_add_api ? 'Bestand gevonden en heeft autoAddDevice API call' : 'Bestand gevonden maar mist mogelijk API call'
        );
    } else {
        $checks[] = check_item(
            "Frontend: $file",
            $exists ? 'ok' : 'warning',
            $exists ? 'Bestand gevonden' : 'Bestand niet gevonden'
        );
    }
}

// 9. Check for Blocklist References in Frontend (should be removed)
$frontend_path = __DIR__ . '/../app.js';
if (file_exists($frontend_path)) {
    $content = file_get_contents($frontend_path);
    $blocklist_refs = substr_count(strtolower($content), 'blocklist');
    $has_blocklist_api = strpos($content, 'admin_blocklist') !== false;
    
    $checks[] = check_item(
        'Frontend Blocklist References',
        !$has_blocklist_api ? 'ok' : 'warning',
        !$has_blocklist_api ? 'Geen blocklist API calls in frontend' : 'Blocklist API calls gevonden in frontend',
        ['blocklist_refs_count' => $blocklist_refs, 'has_blocklist_api' => $has_blocklist_api]
    );
}

// 10. Check Documentation Files
$docs = [
    'CHROME_DOH_DISABLE.md',
    'DNS_FORCE_SETUP.md',
    'QUIC_BLOCK_SETUP.md',
    'DOT_BLOCK_SETUP.md'
];

foreach ($docs as $doc) {
    $path = __DIR__ . '/../' . $doc;
    $exists = file_exists($path);
    $checks[] = check_item(
        "Documentation: $doc",
        $exists ? 'ok' : 'info',
        $exists ? 'Documentatie gevonden' : 'Documentatie niet gevonden'
    );
}

// Summary
$ok_count = count(array_filter($checks, fn($c) => $c['status'] === 'ok'));
$warning_count = count(array_filter($checks, fn($c) => $c['status'] === 'warning'));
$error_count = count(array_filter($checks, fn($c) => $c['status'] === 'error'));

// Adjust overall status based on errors
if ($error_count > 0) {
    $overall_status = 'error';
} elseif ($warning_count > 0) {
    $overall_status = 'warning';
}

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
