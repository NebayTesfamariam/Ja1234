<?php
/**
 * Test Database Connection
 * Uses the same config as the system
 */
require __DIR__ . '/config.php';

echo "🔍 Database Connectie Test\n";
echo "==========================\n\n";

// Test 1: Connection
echo "1. Database Connectie...\n";
try {
    $test = $conn->query("SELECT 1");
    echo "   ✅ Database verbonden\n";
} catch (Exception $e) {
    echo "   ❌ Database fout: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Database exists
echo "\n2. Database 'pornfree'...\n";
try {
    $test = $conn->query("SELECT DATABASE()");
    $db_name = $test->fetch_array()[0];
    echo "   ✅ Database: $db_name\n";
} catch (Exception $e) {
    echo "   ❌ Database check fout: " . $e->getMessage() . "\n";
}

// Test 3: Required tables
echo "\n3. Database Tabellen...\n";
$required_tables = ['users', 'devices', 'whitelist', 'subscriptions'];
foreach ($required_tables as $table) {
    try {
        $stmt = $conn->query("SELECT COUNT(*) FROM `$table`");
        $count = $stmt->fetch_array()[0];
        echo "   ✅ Tabel '$table': $count records\n";
    } catch (Exception $e) {
        echo "   ❌ Tabel '$table' ontbreekt\n";
    }
}

// Test 4: Admin user
echo "\n4. Admin User...\n";
try {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 1");
    $admin_count = $stmt->fetch_assoc()['count'];
    if ($admin_count > 0) {
        echo "   ✅ Admin user bestaat\n";
    } else {
        echo "   ⚠️  Geen admin user gevonden\n";
    }
} catch (Exception $e) {
    echo "   ❌ Admin check fout: " . $e->getMessage() . "\n";
}

// Test 5: Devices
echo "\n5. Devices...\n";
try {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM devices");
    $device_count = $stmt->fetch_assoc()['count'];
    echo "   ✅ Devices: $device_count\n";
} catch (Exception $e) {
    echo "   ❌ Device check fout: " . $e->getMessage() . "\n";
}

// Test 6: Whitelist
echo "\n6. Whitelist...\n";
try {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM whitelist WHERE enabled = 1");
    $whitelist_count = $stmt->fetch_assoc()['count'];
    echo "   ✅ Whitelist entries: $whitelist_count\n";
} catch (Exception $e) {
    echo "   ❌ Whitelist check fout: " . $e->getMessage() . "\n";
}

echo "\n==========================\n";
echo "✅ Database Test Compleet!\n";
echo "\n";
