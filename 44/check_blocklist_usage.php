<?php
/**
 * Check if blocklist tables are still being used
 * In whitelist-only system, these should NOT be used
 */

require __DIR__ . '/config.php';

header('Content-Type: application/json');

$results = [
    'tables_found' => [],
    'tables_used' => [],
    'tables_unused' => [],
    'recommendation' => ''
];

// Find all blocklist tables
try {
    $result = $conn->query("SHOW TABLES LIKE 'blocklist%'");
    while ($row = $result->fetch_row()) {
        $results['tables_found'][] = $row[0];
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT);
    exit;
}

// Check each table for usage
$api_files = glob(__DIR__ . '/api/*.php');
$used_tables = [];

foreach ($api_files as $file) {
    $content = file_get_contents($file);
    
    foreach ($results['tables_found'] as $table) {
        // Check for SQL queries using this table
        if (preg_match('/FROM\s+`?' . preg_quote($table, '/') . '`?/i', $content) ||
            preg_match('/INSERT\s+INTO\s+`?' . preg_quote($table, '/') . '`?/i', $content) ||
            preg_match('/UPDATE\s+`?' . preg_quote($table, '/') . '`?/i', $content) ||
            preg_match('/DELETE\s+FROM\s+`?' . preg_quote($table, '/') . '`?/i', $content)) {
            if (!in_array($table, $used_tables)) {
                $used_tables[] = $table;
            }
        }
    }
}

// Categorize tables
foreach ($results['tables_found'] as $table) {
    if (in_array($table, $used_tables)) {
        $results['tables_used'][] = $table;
    } else {
        $results['tables_unused'][] = $table;
    }
}

// Check critical APIs
$critical_apis = [
    'get_whitelist.php',
    'add_whitelist.php',
    'get_devices.php',
    'auto_register_device.php'
];

$critical_apis_clean = true;
foreach ($critical_apis as $api) {
    $file = __DIR__ . '/api/' . $api;
    if (file_exists($file)) {
        $content = file_get_contents($file);
        foreach ($results['tables_found'] as $table) {
            if (stripos($content, $table) !== false) {
                $critical_apis_clean = false;
                break 2;
            }
        }
    }
}

// Recommendation
if (empty($results['tables_used']) && $critical_apis_clean) {
    $results['recommendation'] = 'safe_to_remove';
    $results['message'] = 'Blocklist tabellen worden NIET gebruikt in filtering logica. Ze kunnen veilig verwijderd worden (optioneel).';
} elseif (!empty($results['tables_used'])) {
    $results['recommendation'] = 'still_used';
    $results['message'] = 'Sommige blocklist tabellen worden nog gebruikt. Verwijder deze referenties eerst.';
} else {
    $results['recommendation'] = 'check_needed';
    $results['message'] = 'Controleer handmatig of blocklist tabellen gebruikt worden.';
}

$results['critical_apis_clean'] = $critical_apis_clean;
$results['summary'] = [
    'total_tables' => count($results['tables_found']),
    'used' => count($results['tables_used']),
    'unused' => count($results['tables_unused'])
];

echo json_encode($results, JSON_PRETTY_PRINT);
