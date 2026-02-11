<?php
/**
 * Test Login API - Debug tool
 * Gebruik: https://ja1234.com/api/test_login.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain; charset=utf-8');

echo "=== Login API Test ===\n\n";

try {
    // Test 1: Config loading
    echo "1. Testing config.php...\n";
    require __DIR__ . '/../config.php';
    echo "   ✓ Config loaded\n";
    echo "   DB_HOST: " . $DB_HOST . "\n";
    echo "   DB_USER: " . $DB_USER . "\n";
    echo "   DB_NAME: " . $DB_NAME . "\n";
    echo "   DB_PASS: " . (empty($DB_PASS) ? 'LEEG' : 'INGEVULD') . "\n\n";
    
    // Test 2: Database connection
    echo "2. Testing database connection...\n";
    if (isset($conn) && $conn instanceof mysqli) {
        echo "   ✓ Database connected\n";
        echo "   Server info: " . $conn->server_info . "\n\n";
    } else {
        throw new Exception("Database connection not available");
    }
    
    // Test 3: Users table
    echo "3. Testing users table...\n";
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows > 0) {
        echo "   ✓ users table exists\n";
        
        // Count users
        $count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
        echo "   Users in database: " . $count . "\n\n";
        
        // Test 4: Find admin user
        echo "4. Testing admin user lookup...\n";
        $stmt = $conn->prepare("SELECT id, email, is_admin FROM users WHERE email = ?");
        $test_email = 'admin@test.com';
        $stmt->bind_param("s", $test_email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if ($user) {
            echo "   ✓ Admin user found\n";
            echo "   ID: " . $user['id'] . "\n";
            echo "   Email: " . $user['email'] . "\n";
            echo "   Is Admin: " . ($user['is_admin'] ? 'Ja' : 'Nee') . "\n\n";
            
            // Test 5: Password verification
            echo "5. Testing password verification...\n";
            $stmt = $conn->prepare("SELECT password_hash FROM users WHERE email = ?");
            $stmt->bind_param("s", $test_email);
            $stmt->execute();
            $user_data = $stmt->get_result()->fetch_assoc();
            
            if ($user_data) {
                $test_password = '123456';
                $verify = password_verify($test_password, $user_data['password_hash']);
                echo "   Password '123456' valid: " . ($verify ? 'Ja' : 'Nee') . "\n\n";
            }
        } else {
            echo "   ✗ Admin user NOT found\n\n";
        }
    } else {
        echo "   ✗ users table does NOT exist\n\n";
    }
    
    echo "=== All Tests Complete ===\n";
    echo "\nIf all tests pass, the login API should work.\n";
    echo "If tests fail, check the error messages above.\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
