<?php
/**
 * Automatic Porn Domain Cleanup
 * Runs automatically via cronjob - removes porn domains from whitelist
 * CANNOT BE DISABLED
 */

require __DIR__ . '/config_porn_block.php';

// Try to connect to database
try {
    require __DIR__ . '/config.php';
    
    // Remove pornographic domains
    $removed = remove_pornographic_domains_from_whitelist($conn);
    
    if ($removed > 0) {
        error_log("Auto cleanup: Removed $removed pornographic domain(s) from whitelist");
    }
    
    echo "Cleanup complete: $removed domain(s) removed\n";
    
} catch (Exception $e) {
    error_log("Auto cleanup error: " . $e->getMessage());
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
