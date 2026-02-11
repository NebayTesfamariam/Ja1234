<?php
/**
 * Remove Blocklist Tables
 * Removes all blocklist tables from database (whitelist-only system)
 * 
 * ⚠️ WARNING: This will permanently delete all blocklist data!
 * Make sure you have a backup before running this!
 */

require __DIR__ . '/config.php';

header('Content-Type: application/json');

// Check if running via web (for safety)
$is_web = isset($_SERVER['REQUEST_METHOD']);
$confirm = $_GET['confirm'] ?? $_POST['confirm'] ?? '';

if ($is_web && $confirm !== 'yes') {
    echo json_encode([
        'status' => 'confirmation_required',
        'message' => '⚠️ WARNING: This will permanently delete all blocklist tables!',
        'tables_to_remove' => [
            'blocklist_global',
            'blocklist_device',
            'blocklist_permanent',
            'blocklist_subscription'
        ],
        'instruction' => 'Add ?confirm=yes to URL to proceed, or use: DELETE /api/remove_blocklist_tables.php with confirm=yes in body'
    ], JSON_PRETTY_PRINT);
    exit;
}

$results = [];
$errors = [];

// List of blocklist tables to remove
$blocklist_tables = [
    'blocklist_global',
    'blocklist_device',
    'blocklist_permanent',
    'blocklist_subscription'
];

// First, check which tables exist
$existing_tables = [];
foreach ($blocklist_tables as $table) {
    try {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            // Get row count before deletion
            $count_result = $conn->query("SELECT COUNT(*) as count FROM `$table`");
            $row_count = $count_result ? (int)$count_result->fetch_assoc()['count'] : 0;
            
            $existing_tables[$table] = [
                'exists' => true,
                'row_count' => $row_count
            ];
        } else {
            $existing_tables[$table] = [
                'exists' => false,
                'row_count' => 0
            ];
        }
    } catch (Exception $e) {
        $existing_tables[$table] = [
            'exists' => false,
            'error' => $e->getMessage()
        ];
    }
}

// If confirm=yes, proceed with deletion
if ($confirm === 'yes') {
    foreach ($blocklist_tables as $table) {
        if (isset($existing_tables[$table]['exists']) && $existing_tables[$table]['exists']) {
            try {
                $row_count = $existing_tables[$table]['row_count'] ?? 0;
                
                // Drop table
                $conn->query("DROP TABLE IF EXISTS `$table`");
                
                // Verify deletion
                $check = $conn->query("SHOW TABLES LIKE '$table'");
                $still_exists = $check && $check->num_rows > 0;
                
                if (!$still_exists) {
                    $results[] = [
                        'table' => $table,
                        'status' => 'removed',
                        'rows_deleted' => $row_count,
                        'message' => "Tabel '$table' verwijderd ($row_count rows)"
                    ];
                } else {
                    $errors[] = [
                        'table' => $table,
                        'status' => 'error',
                        'message' => "Tabel '$table' kon niet worden verwijderd"
                    ];
                }
            } catch (Exception $e) {
                $errors[] = [
                    'table' => $table,
                    'status' => 'error',
                    'message' => "Fout bij verwijderen '$table': " . $e->getMessage()
                ];
            }
        } else {
            $results[] = [
                'table' => $table,
                'status' => 'not_found',
                'message' => "Tabel '$table' bestaat niet (al verwijderd?)"
            ];
        }
    }
    
    // Final verification
    $remaining = [];
    foreach ($blocklist_tables as $table) {
        $check = $conn->query("SHOW TABLES LIKE '$table'");
        if ($check && $check->num_rows > 0) {
            $remaining[] = $table;
        }
    }
    
    echo json_encode([
        'status' => empty($errors) && empty($remaining) ? 'success' : 'partial',
        'message' => empty($errors) && empty($remaining) 
            ? 'Alle blocklist tabellen verwijderd' 
            : 'Sommige tabellen konden niet worden verwijderd',
        'removed' => $results,
        'errors' => $errors,
        'remaining' => $remaining,
        'summary' => [
            'total' => count($blocklist_tables),
            'removed' => count(array_filter($results, fn($r) => $r['status'] === 'removed')),
            'not_found' => count(array_filter($results, fn($r) => $r['status'] === 'not_found')),
            'errors' => count($errors),
            'remaining' => count($remaining)
        ]
    ], JSON_PRETTY_PRINT);
} else {
    // Preview mode - show what would be deleted
    echo json_encode([
        'status' => 'preview',
        'message' => 'Preview mode - geen tabellen verwijderd',
        'tables_found' => array_filter($existing_tables, fn($t) => $t['exists'] ?? false),
        'tables_to_remove' => $blocklist_tables,
        'instruction' => 'Add ?confirm=yes to URL to actually remove tables'
    ], JSON_PRETTY_PRINT);
}
