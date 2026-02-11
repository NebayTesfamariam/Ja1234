<?php
/**
 * Database Setup Script
 * Creates database and tables if they don't exist
 */

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "🗄️  Database Setup\n";
echo "==================\n\n";

// Database configuration
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";  // XAMPP default
$DB_NAME = "pornfree";

try {
    // Connect without database first
    $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS);
    
    if ($conn->connect_error) {
        die("❌ Connection failed: " . $conn->connect_error . "\n");
    }
    
    echo "✅ Connected to MySQL\n\n";
    
    // Create database if it doesn't exist
    echo "📋 Creating database '$DB_NAME'...\n";
    $conn->query("CREATE DATABASE IF NOT EXISTS `$DB_NAME` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Database created/verified\n\n";
    
    // Select database
    $conn->select_db($DB_NAME);
    
    // Read and execute SQL file if it exists
    $sql_file = __DIR__ . '/ALL_DATABASE.sql';
    if (file_exists($sql_file)) {
        echo "📋 Reading SQL file: $sql_file\n";
        $sql = file_get_contents($sql_file);
        
        // Split by semicolon and execute each statement
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) {
                return !empty($stmt) && 
                       !preg_match('/^--/', $stmt) && 
                       !preg_match('/^\/\*/', $stmt);
            }
        );
        
        $tables_created = 0;
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement)) continue;
            
            // Skip comments
            if (strpos($statement, '--') === 0) continue;
            if (strpos($statement, '/*') === 0) continue;
            
            // Normalize whitespace for better regex matching
            $normalized = preg_replace('/\s+/', ' ', $statement);
            
            try {
                $conn->query($statement);
                
                // Better regex to match CREATE TABLE statements (handles multi-line)
                if (preg_match('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?[`"]?(\w+)[`"]?/i', $normalized, $matches)) {
                    $table_name = $matches[1] ?? 'unknown';
                    // Only show if it's a real table name (not single character)
                    if (strlen($table_name) > 1) {
                        echo "  ✅ Table '$table_name' created/verified\n";
                        $tables_created++;
                    }
                }
            } catch (Exception $e) {
                // Ignore "table already exists" errors
                if (strpos($e->getMessage(), 'already exists') === false && 
                    strpos($e->getMessage(), 'Duplicate key') === false) {
                    echo "  ⚠️  Warning: " . $e->getMessage() . "\n";
                }
            }
        }
        
        echo "\n✅ SQL file processed ($tables_created tables)\n\n";
    } else {
        echo "⚠️  SQL file not found: $sql_file\n";
        echo "   Creating basic tables manually...\n\n";
        
        // Create basic tables
        $tables = [
            "CREATE TABLE IF NOT EXISTS `users` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `email` VARCHAR(255) NOT NULL UNIQUE,
                `password_hash` VARCHAR(255) NOT NULL,
                `is_admin` TINYINT(1) DEFAULT 0,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            "CREATE TABLE IF NOT EXISTS `devices` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT UNSIGNED NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `wg_public_key` VARCHAR(255),
                `wg_ip` VARCHAR(45),
                `status` ENUM('active', 'inactive') DEFAULT 'active',
                `admin_created` TINYINT(1) DEFAULT 0,
                `auto_created` TINYINT(1) DEFAULT 0,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            "CREATE TABLE IF NOT EXISTS `whitelist` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `device_id` INT UNSIGNED NOT NULL,
                `domain` VARCHAR(255) NOT NULL,
                `enabled` TINYINT(1) DEFAULT 1,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`device_id`) REFERENCES `devices`(`id`) ON DELETE CASCADE,
                UNIQUE KEY `device_domain` (`device_id`, `domain`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            "CREATE TABLE IF NOT EXISTS `subscriptions` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT UNSIGNED NOT NULL,
                `plan` VARCHAR(50) DEFAULT 'basic',
                `status` ENUM('active', 'expired', 'cancelled', 'pending') DEFAULT 'pending',
                `start_date` DATE NOT NULL,
                `end_date` DATE NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        ];
        
        foreach ($tables as $table_sql) {
            try {
                $conn->query($table_sql);
                echo "✅ Table created/verified\n";
            } catch (Exception $e) {
                echo "⚠️  " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Verify tables
    echo "\n📋 Verifying tables...\n";
    $result = $conn->query("SHOW TABLES");
    $tables = [];
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
    
    $required_tables = ['users', 'devices', 'whitelist', 'subscriptions'];
    foreach ($required_tables as $table) {
        if (in_array($table, $tables)) {
            echo "  ✅ Table '$table' exists\n";
        } else {
            echo "  ❌ Table '$table' missing\n";
        }
    }
    
    // Create admin user if it doesn't exist
    echo "\n📋 Checking admin user...\n";
    $result = $conn->query("SELECT id FROM users WHERE is_admin = 1 LIMIT 1");
    if ($result->num_rows === 0) {
        echo "  ⚠️  No admin user found. Creating default admin...\n";
        $email = "admin@test.com";
        $password = password_hash("admin123", PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (email, password_hash, is_admin) VALUES (?, ?, 1)");
        $stmt->bind_param("ss", $email, $password);
        $stmt->execute();
        echo "  ✅ Admin user created: $email / admin123\n";
    } else {
        echo "  ✅ Admin user exists\n";
    }
    
    echo "\n✅ Database setup complete!\n";
    echo "\n📊 Database: $DB_NAME\n";
    echo "📊 Tables: " . count($tables) . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
