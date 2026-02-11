<?php
/**
 * Automated Database Backup
 * Creates SQL dump of the database
 */

require __DIR__ . '/../config.php';
require __DIR__ . '/../config_security.php';

// CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

try {
  $user = require_user($conn);
} catch (Exception $e) {
  json_out(['message' => 'Authentication required'], 401);
}

// Check admin
$stmt = $conn->prepare("SELECT is_admin FROM users WHERE id=?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
if (!$result || (int)($result['is_admin'] ?? 0) !== 1) {
  json_out(['message' => 'Access denied'], 403);
}

// Ensure backups directory exists
$backup_dir = __DIR__ . '/../backups';
if (!is_dir($backup_dir)) {
  mkdir($backup_dir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Create backup
  $backup_name = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
  $backup_path = $backup_dir . '/' . $backup_name;
  
  // Get database name
  $db_name = $conn->query("SELECT DATABASE()")->fetch_row()[0];
  
  // Get all tables
  $tables = [];
  $result = $conn->query("SHOW TABLES");
  while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
  }
  
  $backup_content = "-- Database Backup\n";
  $backup_content .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
  $backup_content .= "-- Database: {$db_name}\n\n";
  $backup_content .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
  
  foreach ($tables as $table) {
    // Get table structure
    $result = $conn->query("SHOW CREATE TABLE `{$table}`");
    $row = $result->fetch_row();
    $backup_content .= "-- Table structure for `{$table}`\n";
    $backup_content .= "DROP TABLE IF EXISTS `{$table}`;\n";
    $backup_content .= $row[1] . ";\n\n";
    
    // Get table data
    $result = $conn->query("SELECT * FROM `{$table}`");
    if ($result->num_rows > 0) {
      $backup_content .= "-- Data for table `{$table}`\n";
      $backup_content .= "LOCK TABLES `{$table}` WRITE;\n";
      
      while ($row = $result->fetch_assoc()) {
        $keys = array_keys($row);
        $values = array_map(function($val) use ($conn) {
          if ($val === null) return 'NULL';
          return "'" . $conn->real_escape_string($val) . "'";
        }, array_values($row));
        
        $backup_content .= "INSERT INTO `{$table}` (`" . implode('`, `', $keys) . "`) VALUES (" . implode(', ', $values) . ");\n";
      }
      
      $backup_content .= "UNLOCK TABLES;\n\n";
    }
  }
  
  $backup_content .= "SET FOREIGN_KEY_CHECKS=1;\n";
  
  // Write backup file
  file_put_contents($backup_path, $backup_content);
  
  // Log audit event
  audit_log($conn, 'database_backup_created', 'backup', null, [
    'backup_file' => $backup_name,
    'tables_count' => count($tables)
  ]);
  
  json_out([
    'status' => 'success',
    'message' => 'Backup created successfully',
    'backup_file' => $backup_name,
    'backup_path' => $backup_path,
    'tables_count' => count($tables),
    'created_at' => date('Y-m-d H:i:s')
  ]);
  
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
  // List backups
  $backups = [];
  if (is_dir($backup_dir)) {
    $files = glob($backup_dir . '/*.sql');
    foreach ($files as $file) {
      $backups[] = [
        'filename' => basename($file),
        'size' => filesize($file),
        'size_mb' => round(filesize($file) / 1024 / 1024, 2),
        'created_at' => date('Y-m-d H:i:s', filemtime($file)),
        'age_hours' => round((time() - filemtime($file)) / 3600, 1)
      ];
    }
    
    // Sort by creation date (newest first)
    usort($backups, function($a, $b) {
      return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
  }
  
  json_out([
    'backups' => $backups,
    'count' => count($backups)
  ]);
} else {
  json_out(['message' => 'Method not allowed'], 405);
}
