<?php
/**
 * Export database as SQL dump
 */
require __DIR__ . '/../config.php';
$user = require_user($conn);

// Check admin
$stmt = $conn->prepare("SELECT is_admin FROM users WHERE id=?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
if (!$result || !$result['is_admin']) {
  http_response_code(403);
  die('Access denied');
}

// Get all tables
$tables = [];
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_array()) {
  $tables[] = $row[0];
}

// Generate SQL dump
$output = "-- PornFree Database Export\n";
$output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
$output .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
$output .= "SET time_zone = \"+00:00\";\n\n";

foreach ($tables as $table) {
  $output .= "-- Table: {$table}\n";
  $output .= "DROP TABLE IF EXISTS `{$table}`;\n";
  
  // Get table structure
  $result = $conn->query("SHOW CREATE TABLE `{$table}`");
  $row = $result->fetch_array();
  $output .= $row[1] . ";\n\n";
  
  // Get table data
  $result = $conn->query("SELECT * FROM `{$table}`");
  if ($result->num_rows > 0) {
    $output .= "INSERT INTO `{$table}` VALUES\n";
    $rows = [];
    while ($row = $result->fetch_assoc()) {
      $values = [];
      foreach ($row as $value) {
        if ($value === null) {
          $values[] = 'NULL';
        } else {
          $values[] = "'" . $conn->real_escape_string($value) . "'";
        }
      }
      $rows[] = "(" . implode(", ", $values) . ")";
    }
    $output .= implode(",\n", $rows) . ";\n\n";
  }
}

// Send as download
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="pornfree_backup_' . date('Y-m-d') . '.sql"');
header('Content-Length: ' . strlen($output));
echo $output;
exit;

