<?php
/**
 * Debug script to test login.php configuration loading
 */

// Start output buffering
ob_start();

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

header('Content-Type: application/json');

$debug = [];

try {
  // Test 1: Check if config.php exists
  $config_path = __DIR__ . '/../config.php';
  $debug['config_exists'] = file_exists($config_path);
  $debug['config_path'] = $config_path;
  
  if (!file_exists($config_path)) {
    throw new Exception("config.php not found at: " . $config_path);
  }
  
  // Test 2: Try to load config.php
  $old_error_reporting = error_reporting(E_ALL);
  $old_display_errors = ini_get('display_errors');
  ini_set('display_errors', 0);
  
  try {
    require $config_path;
    $debug['config_loaded'] = true;
  } catch (Throwable $e) {
    $debug['config_loaded'] = false;
    $debug['config_error'] = $e->getMessage();
    $debug['config_error_file'] = $e->getFile();
    $debug['config_error_line'] = $e->getLine();
    throw $e;
  } finally {
    error_reporting($old_error_reporting);
    ini_set('display_errors', $old_display_errors);
  }
  
  // Test 3: Check if required functions exist
  $debug['functions'] = [
    'json_out' => function_exists('json_out'),
    'get_bearer_token' => function_exists('get_bearer_token'),
    'require_user' => function_exists('require_user'),
  ];
  
  // Test 4: Check if required classes exist
  $debug['classes'] = [
    'BruteForceProtection' => class_exists('BruteForceProtection'),
    'PasswordSecurity' => class_exists('PasswordSecurity'),
  ];
  
  // Test 5: Check database connection
  if (isset($conn)) {
    $debug['db_connection'] = [
      'exists' => true,
      'is_mysqli' => $conn instanceof mysqli,
      'connected' => false,
      'error' => null,
    ];
    
    if ($conn instanceof mysqli) {
      try {
        $conn->ping();
        $debug['db_connection']['connected'] = true;
        $debug['db_connection']['host_info'] = $conn->host_info;
      } catch (Throwable $e) {
        $debug['db_connection']['error'] = $e->getMessage();
      }
    } else {
      $debug['db_connection']['error'] = 'Not a mysqli instance';
    }
  } else {
    $debug['db_connection'] = ['exists' => false];
  }
  
  // Test 6: Check variables
  $debug['variables'] = [
    'is_production' => isset($is_production) ? $is_production : 'not set',
    'DB_HOST' => isset($DB_HOST) ? $DB_HOST : 'not set',
    'DB_USER' => isset($DB_USER) ? $DB_USER : 'not set',
    'DB_NAME' => isset($DB_NAME) ? $DB_NAME : 'not set',
  ];
  
  $debug['status'] = 'success';
  $debug['message'] = 'All checks passed';
  
} catch (Throwable $e) {
  $debug['status'] = 'error';
  $debug['message'] = $e->getMessage();
  $debug['error_file'] = $e->getFile();
  $debug['error_line'] = $e->getLine();
  $debug['error_trace'] = $e->getTraceAsString();
}

ob_clean();
echo json_encode($debug, JSON_PRETTY_PRINT);
ob_end_flush();
