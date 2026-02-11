<?php
/**
 * Test script for auto_register_device.php
 * Run this to see what error occurs
 */

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Testing auto_register_device.php...\n\n";

// Simulate POST request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)';

// Set test data
file_put_contents('php://memory', json_encode(['device_name' => 'Test Device']));

// Try to include the file
try {
  require __DIR__ . '/api/auto_register_device.php';
} catch (Throwable $e) {
  echo "ERROR: " . $e->getMessage() . "\n";
  echo "File: " . $e->getFile() . "\n";
  echo "Line: " . $e->getLine() . "\n";
  echo "\nStack trace:\n";
  echo $e->getTraceAsString() . "\n";
}
