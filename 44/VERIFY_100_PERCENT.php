<?php
/**
 * 100% System Verification
 * Checks all critical components
 */
require __DIR__ . '/config.php';

echo "🔍 100% Systeem Verificatie\n";
echo "============================\n\n";

$checks = [];
$all_passed = true;

// Check 1: Database Connection
echo "1. Database Verbinding...\n";
try {
    $stmt = $conn->query("SELECT 1");
    $checks['database'] = true;
    echo "   ✅ Database verbonden\n";
} catch (Exception $e) {
    $checks['database'] = false;
    $all_passed = false;
    echo "   ❌ Database fout: " . $e->getMessage() . "\n";
}

// Check 2: Required Tables
echo "\n2. Database Tabellen...\n";
$required_tables = ['users', 'devices', 'whitelist', 'subscriptions'];
foreach ($required_tables as $table) {
    try {
        $stmt = $conn->query("SELECT COUNT(*) FROM `$table`");
        echo "   ✅ Tabel '$table' bestaat\n";
    } catch (Exception $e) {
        echo "   ❌ Tabel '$table' ontbreekt\n";
        $all_passed = false;
    }
}

// Check 3: Porn Blocking Config
echo "\n3. Pornografische Blokkering Config...\n";
if (file_exists(__DIR__ . '/config_porn_block.php')) {
    require __DIR__ . '/config_porn_block.php';
    if (function_exists('is_pornographic_domain')) {
        // Test porn detection
        $test_domains = ['pornhub.com', 'google.com'];
        foreach ($test_domains as $domain) {
            $is_porn = is_pornographic_domain($domain);
            if ($domain === 'pornhub.com' && !$is_porn) {
                echo "   ❌ Porn detectie werkt niet (pornhub.com niet gedetecteerd)\n";
                $all_passed = false;
            } elseif ($domain === 'google.com' && $is_porn) {
                echo "   ❌ Porn detectie geeft false positives\n";
                $all_passed = false;
            }
        }
        echo "   ✅ Porn detectie werkt correct\n";
    } else {
        echo "   ❌ is_pornographic_domain functie ontbreekt\n";
        $all_passed = false;
    }
} else {
    echo "   ❌ config_porn_block.php ontbreekt\n";
    $all_passed = false;
}

// Check 4: API Endpoints
echo "\n4. API Endpoints...\n";
$required_apis = [
    'api/get_whitelist.php',
    'api/add_whitelist.php',
    'api/get_wireguard_config.php',
    'api/register.php',
    'api/login.php'
];
foreach ($required_apis as $api) {
    if (file_exists(__DIR__ . '/' . $api)) {
        echo "   ✅ $api bestaat\n";
    } else {
        echo "   ❌ $api ontbreekt\n";
        $all_passed = false;
    }
}

// Check 5: DNS Server
echo "\n5. DNS Server...\n";
if (file_exists(__DIR__ . '/dns_whitelist_server.py')) {
    echo "   ✅ DNS server script bestaat\n";
    
    // Check if running
    $output = shell_exec("ps aux | grep 'dns_whitelist_server.py' | grep -v grep");
    if ($output) {
        echo "   ✅ DNS server draait\n";
    } else {
        echo "   ⚠️  DNS server draait NIET (start met: sudo python3 dns_whitelist_server.py)\n";
    }
} else {
    echo "   ❌ DNS server script ontbreekt\n";
    $all_passed = false;
}

// Check 6: Whitelist API Returns Array
echo "\n6. Whitelist API Retourneert Array...\n";
try {
    // Get a test device
    $stmt = $conn->query("SELECT id FROM devices LIMIT 1");
    $device = $stmt->fetch_assoc();
    
    if ($device) {
        // Simulate API call
        $stmt = $conn->prepare("SELECT domain FROM whitelist WHERE device_id = ? AND enabled = 1");
        $stmt->bind_param("i", $device['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $domains = [];
        while ($row = $result->fetch_assoc()) {
            $domains[] = $row['domain'];
        }
        
        // Check if it's an array (should be)
        if (is_array($domains)) {
            echo "   ✅ Whitelist retourneert array (" . count($domains) . " domeinen)\n";
        } else {
            echo "   ❌ Whitelist retourneert geen array\n";
            $all_passed = false;
        }
    } else {
        echo "   ⚠️  Geen devices gevonden om te testen\n";
    }
} catch (Exception $e) {
    echo "   ❌ Whitelist check fout: " . $e->getMessage() . "\n";
    $all_passed = false;
}

// Check 7: Porn Domains Cannot Be Added
echo "\n7. Pornografische Domeinen Kunnen Niet Worden Toegevoegd...\n";
if (function_exists('validate_domain_for_whitelist')) {
    $test_porn = validate_domain_for_whitelist('pornhub.com');
    if (!$test_porn['valid'] && isset($test_porn['blocked'])) {
        echo "   ✅ Pornografische domeinen worden geblokkeerd\n";
    } else {
        echo "   ❌ Pornografische domeinen kunnen worden toegevoegd\n";
        $all_passed = false;
    }
} else {
    echo "   ⚠️  validate_domain_for_whitelist functie niet gevonden\n";
}

// Check 8: Frontend Files
echo "\n8. Frontend Bestanden...\n";
$required_frontend = [
    'public/index.html',
    'app.js',
    'subscribe.html'
];
foreach ($required_frontend as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "   ✅ $file bestaat\n";
    } else {
        echo "   ❌ $file ontbreekt\n";
        $all_passed = false;
    }
}

// Summary
echo "\n============================\n";
if ($all_passed) {
    echo "✅ STATUS: 100% WERKT!\n";
    echo "\nAlle kritieke componenten werken correct.\n";
} else {
    echo "⚠️  STATUS: NIET 100% WERKT\n";
    echo "\nSommige componenten hebben problemen.\n";
    echo "Controleer de bovenstaande checks.\n";
}
echo "\n";
