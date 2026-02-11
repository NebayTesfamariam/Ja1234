<?php
/**
 * Health Check Endpoint
 * Monitor system status - database, API, etc.
 * Can be used for monitoring tools (UptimeRobot, etc.)
 */
require __DIR__ . '/../config.php';

$health = [
  'status' => 'healthy',
  'timestamp' => date('Y-m-d H:i:s'),
  'checks' => []
];

// 1. Database Connection Check
try {
  $conn->ping();
  $health['checks']['database'] = [
    'status' => 'ok',
    'message' => 'Database verbinding succesvol'
  ];
} catch (Exception $e) {
  $health['status'] = 'unhealthy';
  $health['checks']['database'] = [
    'status' => 'error',
    'message' => 'Database verbinding mislukt: ' . $e->getMessage()
  ];
}

// 2. Database Tables Check
// Whitelist-only system: only check whitelist tables
$required_tables = ['users', 'devices', 'subscriptions', 'whitelist'];
// Note: blocklist_permanent removed - whitelist-only system
$missing_tables = [];
foreach ($required_tables as $table) {
  try {
    $result = $conn->query("SHOW TABLES LIKE '{$table}'");
    if ($result->num_rows === 0) {
      $missing_tables[] = $table;
    }
  } catch (Exception $e) {
    $missing_tables[] = $table;
  }
}

if (empty($missing_tables)) {
  $health['checks']['tables'] = [
    'status' => 'ok',
    'message' => 'Alle vereiste tabellen aanwezig'
  ];
} else {
  $health['status'] = 'unhealthy';
  $health['checks']['tables'] = [
    'status' => 'error',
    'message' => 'Ontbrekende tabellen: ' . implode(', ', $missing_tables)
  ];
}

// 3. Active Devices Count
try {
  $result = $conn->query("SELECT COUNT(*) as count FROM devices WHERE status = 'active'");
  $active_devices = $result->fetch_assoc()['count'];
  $health['checks']['devices'] = [
    'status' => 'ok',
    'message' => "{$active_devices} actieve devices"
  ];
} catch (Exception $e) {
  $health['checks']['devices'] = [
    'status' => 'warning',
    'message' => 'Kon devices niet tellen'
  ];
}

// 4. Active Subscriptions Count
try {
  $result = $conn->query("
    SELECT COUNT(*) as count 
    FROM subscriptions 
    WHERE status = 'active' 
      AND start_date <= CURDATE() 
      AND end_date >= CURDATE()
  ");
  $active_subs = $result->fetch_assoc()['count'];
  $health['checks']['subscriptions'] = [
    'status' => 'ok',
    'message' => "{$active_subs} actieve abonnementen"
  ];
} catch (Exception $e) {
  $health['checks']['subscriptions'] = [
    'status' => 'warning',
    'message' => 'Kon abonnementen niet tellen'
  ];
}

// Set HTTP status code based on health
$http_code = $health['status'] === 'healthy' ? 200 : 503;

json_out($health, $http_code);
