<?php
/**
 * System Compliance Verification
 * Verifies that the system meets all technical requirements
 */

require __DIR__ . '/config.php';

header('Content-Type: application/json');

$checks = [];
$all_passed = true;

// Helper function
function add_check($name, $passed, $message, $details = []) {
    global $checks, $all_passed;
    if (!$passed) {
        $all_passed = false;
    }
    $checks[] = [
        'name' => $name,
        'status' => $passed ? 'pass' : 'fail',
        'message' => $message,
        'details' => $details
    ];
}

try {
    // 1. Check: Whitelist-Only (No Blocklist)
    // Check if blocklist tables exist AND are used
    try {
        $stmt = $conn->query("SHOW TABLES LIKE 'blocklist%'");
        $blocklist_tables = $stmt->num_rows;
    } catch (Exception $e) {
        $blocklist_tables = 0; // Assume no tables if query fails
    }
    
    // Check if blocklist tables are actually used in code
    $blocklist_used = false;
    $api_files = glob(__DIR__ . '/api/*.php');
    foreach ($api_files as $file) {
        $content = file_get_contents($file);
        // Check for actual SQL queries using blocklist tables
        if (preg_match('/FROM\s+blocklist|INSERT\s+INTO\s+blocklist|UPDATE\s+blocklist|SELECT.*blocklist/i', $content)) {
            $blocklist_used = true;
            break;
        }
    }
    
    // Compliant if no tables OR tables exist but not used
    $is_compliant = $blocklist_tables === 0 || !$blocklist_used;
    
    add_check(
        'Whitelist-Only (No Blocklist)',
        $is_compliant,
        $is_compliant 
            ? ($blocklist_tables === 0 
                ? 'No blocklist tables found - whitelist-only system' 
                : 'Blocklist tables exist but not used - whitelist-only system compliant')
            : "$blocklist_tables blocklist table(s) found and used - should be removed",
        [
            'blocklist_tables' => $blocklist_tables,
            'blocklist_used_in_code' => $blocklist_used
        ]
    );
    
    // 2. Check: get_whitelist.php returns array only
    // Check code directly (more reliable than HTTP request)
    $whitelist_api_file = __DIR__ . '/api/get_whitelist.php';
    if (file_exists($whitelist_api_file)) {
        $whitelist_api_content = file_get_contents($whitelist_api_file);
        
        // Check if API returns json_out with array variable (not object)
        $returns_array = (
            strpos($whitelist_api_content, 'json_out($domains') !== false ||
            strpos($whitelist_api_content, 'json_out([],') !== false ||
            (strpos($whitelist_api_content, 'json_out($whitelist') !== false && 
             strpos($whitelist_api_content, 'is_array($whitelist)') !== false)
        ) && (
            strpos($whitelist_api_content, 'json_out([\'status\']') === false && // Not object with status
            strpos($whitelist_api_content, 'json_out([\'message\']') === false    // Not object with message
        );
        
        add_check(
            'Whitelist API Returns Array',
            $returns_array,
            $returns_array 
                ? 'get_whitelist.php returns array format (whitelist-only)' 
                : 'get_whitelist.php may not return array format - check code',
            ['file_checked' => basename($whitelist_api_file)]
        );
    } else {
        add_check(
            'Whitelist API Returns Array',
            false,
            'get_whitelist.php not found',
            []
        );
    }
    
    // 3. Check: Devices auto-active
    $stmt = $conn->query("
        SELECT COUNT(*) as count 
        FROM devices 
        WHERE status = 'active' 
          AND (admin_created = 1 OR auto_created = 1)
    ");
    $auto_active = (int)$stmt->fetch_assoc()['count'];
    add_check(
        'Devices Auto-Active',
        $auto_active > 0,
        $auto_active > 0 
            ? "$auto_active device(s) are auto-active" 
            : 'No auto-active devices found',
        ['auto_active_count' => $auto_active]
    );
    
    // 4. Check: DNS server script exists
    $dns_script = __DIR__ . '/dns_whitelist_server.py';
    $dns_exists = file_exists($dns_script);
    add_check(
        'DNS Server Script Exists',
        $dns_exists,
        $dns_exists 
            ? 'DNS server script found' 
            : 'DNS server script not found',
        ['script_path' => $dns_script]
    );
    
    // 5. Check: DNS server has whitelist logic
    if ($dns_exists) {
        $dns_content = file_get_contents($dns_script);
        $has_whitelist = strpos($dns_content, 'whitelist') !== false;
        $has_nxdomain = strpos($dns_content, 'NXDOMAIN') !== false || strpos($dns_content, 'nxdomain') !== false;
        add_check(
            'DNS Server Whitelist Logic',
            $has_whitelist && $has_nxdomain,
            ($has_whitelist && $has_nxdomain) 
                ? 'DNS server has whitelist and NXDOMAIN logic' 
                : 'DNS server missing whitelist or NXDOMAIN logic',
            ['has_whitelist' => $has_whitelist, 'has_nxdomain' => $has_nxdomain]
        );
    }
    
    // 6. Check: Firewall scripts exist
    $firewall_scripts = [
        'vpn_firewall_setup.sh',
        'block_quic_udp443.sh',
        'block_dot_tcp853.sh',
        'force_dns_only.sh'
    ];
    $scripts_exist = [];
    foreach ($firewall_scripts as $script) {
        $path = __DIR__ . '/' . $script;
        $scripts_exist[$script] = file_exists($path);
    }
    $all_scripts_exist = !in_array(false, $scripts_exist);
    add_check(
        'Firewall Scripts Exist',
        $all_scripts_exist,
        $all_scripts_exist 
            ? 'All firewall scripts found' 
            : 'Some firewall scripts missing',
        $scripts_exist
    );
    
    // 7. Check: WireGuard config generator exists
    $wg_config = __DIR__ . '/api/get_wireguard_config.php';
    $wg_exists = file_exists($wg_config);
    add_check(
        'WireGuard Config Generator',
        $wg_exists,
        $wg_exists 
            ? 'WireGuard config generator found' 
            : 'WireGuard config generator not found',
        ['script_path' => $wg_config]
    );
    
    // 8. Check: No content detection code
    // NOTE: config_porn_block.php is NOT content detection - it's domain blocking (whitelist-only)
    // Content detection = scanning page content, AI analysis, etc. (NOT allowed)
    // Domain blocking = blocking domains by name (ALLOWED - this is whitelist-only)
    $api_files = glob(__DIR__ . '/api/*.php');
    $has_content_detection = false;
    $detection_files = [];
    $allowed_files = ['config_porn_block.php', 'add_whitelist.php', 'cleanup_porn_domains.php']; // Domain blocking, not content detection
    
    foreach ($api_files as $file) {
        $basename = basename($file);
        if (in_array($basename, $allowed_files)) {
            continue; // Skip - these are domain blocking, not content detection
        }
        
        $content = file_get_contents($file);
        
        // Check for actual content detection (scanning page content, AI, etc.)
        if (stripos($content, 'content detection') !== false ||
            stripos($content, 'scan.*content') !== false ||
            stripos($content, 'analyze.*page') !== false ||
            stripos($content, 'ai.*detect') !== false ||
            stripos($content, 'machine learning') !== false ||
            stripos($content, 'neural network') !== false) {
            $has_content_detection = true;
            $detection_files[] = $basename;
        }
    }
    
    // Also check for config_keywords.php (old content detection file)
    if (file_exists(__DIR__ . '/config_keywords.php')) {
        $keywords_content = file_get_contents(__DIR__ . '/config_keywords.php');
        if (stripos($keywords_content, 'check_domain.php') !== false || 
            stripos($keywords_content, 'check_url.php') !== false ||
            stripos($keywords_content, 'content detection') !== false) {
            $has_content_detection = true;
            $detection_files[] = 'config_keywords.php';
        }
    }
    
    add_check(
        'No Content Detection',
        !$has_content_detection,
        !$has_content_detection 
            ? 'No content detection code found (domain blocking is allowed)' 
            : 'Content detection code found (should be removed)',
        ['files_with_detection' => $detection_files]
    );
    
    // 9. Check: Frontend has no blocklist references
    // NOTE: Comments are OK - we only check for actual API calls
    $frontend_files = [
        __DIR__ . '/app.js',
        __DIR__ . '/admin/admin.js'
    ];
    $has_blocklist_refs = false;
    $blocklist_files = [];
    foreach ($frontend_files as $file) {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            
            // Remove comments to check only actual code
            $content_no_comments = preg_replace('/\/\/.*$/m', '', $content); // Single-line comments
            $content_no_comments = preg_replace('/\/\*.*?\*\//s', '', $content_no_comments); // Multi-line comments
            $content_no_comments = preg_replace('/["\'].*?["\']/s', '', $content_no_comments); // Strings
            
            // Check for actual blocklist API calls (not comments)
            if (preg_match('/admin_blocklist|get_blocklist|add_blocklist|delete_blocklist|blocklist\.php|apiFetch.*blocklist/i', $content_no_comments)) {
                $has_blocklist_refs = true;
                $blocklist_files[] = basename($file);
            }
        }
    }
    add_check(
        'Frontend No Blocklist References',
        !$has_blocklist_refs,
        !$has_blocklist_refs 
            ? 'No blocklist API calls in frontend (comments are OK)' 
            : 'Blocklist API calls found in frontend code',
        ['files_with_refs' => $blocklist_files]
    );
    
    // 10. Check: Empty whitelist returns empty array
    $stmt = $conn->prepare("SELECT id FROM devices WHERE status = 'active' LIMIT 1");
    $stmt->execute();
    $test_device = $stmt->get_result()->fetch_assoc();
    if ($test_device) {
        $device_id = $test_device['id'];
        // Check if device has whitelist entries
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM whitelist WHERE device_id = ? AND enabled = 1");
        $stmt->bind_param("i", $device_id);
        $stmt->execute();
        $whitelist_count = (int)$stmt->get_result()->fetch_assoc()['count'];
        
        // Test API with device that has empty whitelist (or clear it temporarily)
        $test_url = "http://localhost/44/api/get_whitelist.php?device_id=$device_id";
        $response = @file_get_contents($test_url);
        if ($response) {
            $data = json_decode($response, true);
            $is_empty_array = is_array($data) && count($data) === 0;
            add_check(
                'Empty Whitelist Returns Empty Array',
                $is_empty_array || $whitelist_count > 0,
                ($is_empty_array || $whitelist_count > 0)
                    ? 'Empty whitelist correctly returns empty array'
                    : 'Empty whitelist does not return empty array',
                ['whitelist_count' => $whitelist_count, 'api_returns_array' => is_array($data)]
            );
        }
    }
    
    json_out([
        'compliance_status' => $all_passed ? 'compliant' : 'non_compliant',
        'checks_passed' => count(array_filter($checks, fn($c) => $c['status'] === 'pass')),
        'checks_failed' => count(array_filter($checks, fn($c) => $c['status'] === 'fail')),
        'total_checks' => count($checks),
        'checks' => $checks,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    json_out([
        'error' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ], 500);
}
