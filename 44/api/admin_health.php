<?php
/**
 * System Health Check
 * Checks database, API endpoints, disk space, etc.
 */

// CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

require __DIR__ . '/../config.php';

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
    'message' => 'Database verbinding succesvol',
    'response_time' => 'OK'
  ];
} catch (Exception $e) {
  $health['status'] = 'unhealthy';
  $health['checks']['database'] = [
    'status' => 'error',
    'message' => 'Database verbinding mislukt: ' . $e->getMessage()
  ];
}

// 2. Database Tables Check
// Critical tables (required for system to work)
// Whitelist-only system: only check whitelist tables
$critical_tables = ['users', 'devices', 'whitelist', 'subscriptions'];
// Optional tables (nice to have but not critical)
$optional_tables = ['activity_logs'];
// Note: blocklist tables removed - whitelist-only system

$missing_critical = [];
$missing_optional = [];

try {
  // Check critical tables
  foreach ($critical_tables as $table) {
    try {
      $result = $conn->query("SHOW TABLES LIKE '{$table}'");
      if ($result && $result->num_rows === 0) {
        $missing_critical[] = $table;
      }
    } catch (Exception $e) {
      $missing_critical[] = $table;
    }
  }
  
  // Check optional tables and create if missing
  foreach ($optional_tables as $table) {
    try {
      $result = $conn->query("SHOW TABLES LIKE '{$table}'");
      if ($result && $result->num_rows === 0) {
        // Whitelist-only system: no automatic table creation for blocklist tables
        // Optional tables are just checked, not created
        $missing_optional[] = $table;
      }
    } catch (Exception $e) {
      // If creation failed, add to missing
      $missing_optional[] = $table;
    }
  }
  
  if (empty($missing_critical)) {
    $message = 'Alle kritieke tabellen aanwezig';
    if (!empty($missing_optional)) {
      $message .= ' (' . count($missing_optional) . ' optionele tabellen ontbreken)';
    }
    $health['checks']['tables'] = [
      'status' => empty($missing_optional) ? 'ok' : 'warning',
      'message' => $message,
      'count' => count($critical_tables) + count($optional_tables) - count($missing_optional) - count($missing_critical),
      'missing_optional' => $missing_optional
    ];
  } else {
    $health['status'] = 'unhealthy';
    $health['checks']['tables'] = [
      'status' => 'error',
      'message' => 'Ontbrekende kritieke tabellen: ' . implode(', ', $missing_critical),
      'missing' => $missing_critical,
      'missing_optional' => $missing_optional
    ];
  }
} catch (Exception $e) {
  $health['checks']['tables'] = [
    'status' => 'warning',
    'message' => 'Kon tabellen niet volledig controleren: ' . $e->getMessage()
  ];
}

// 3. API Endpoints Check
// Critical endpoints (required)
$critical_endpoints = ['login.php', 'get_devices.php', 'check_domain.php'];
// Optional endpoints (nice to have)
$optional_endpoints = ['admin_stats.php'];

$endpoint_status = [];
$has_missing_critical = false;
$has_missing_optional = false;

foreach ($critical_endpoints as $endpoint) {
  $file = __DIR__ . '/' . $endpoint;
  if (file_exists($file)) {
    $endpoint_status[$endpoint] = 'ok';
  } else {
    $endpoint_status[$endpoint] = 'missing';
    $has_missing_critical = true;
    $health['status'] = 'unhealthy';
  }
}

foreach ($optional_endpoints as $endpoint) {
  $file = __DIR__ . '/' . $endpoint;
  if (file_exists($file)) {
    $endpoint_status[$endpoint] = 'ok';
  } else {
    $endpoint_status[$endpoint] = 'missing';
    $has_missing_optional = true;
  }
}

$health['checks']['api'] = [
  'status' => $has_missing_critical ? 'error' : ($has_missing_optional ? 'warning' : 'ok'),
  'message' => $has_missing_critical 
    ? 'Kritieke endpoints ontbreken' 
    : ($has_missing_optional 
      ? 'Alle kritieke endpoints aanwezig (sommige optionele ontbreken)'
      : 'Alle API endpoints aanwezig'),
  'endpoints' => $endpoint_status
];

// 4. Disk Space Check
try {
  $disk_free = disk_free_space(__DIR__);
  $disk_total = disk_total_space(__DIR__);
  if ($disk_free !== false && $disk_total !== false) {
    $disk_used_percent = (($disk_total - $disk_free) / $disk_total) * 100;
    // Only mark as error if disk is > 95% full, warning if > 90%
    $disk_status = 'ok';
    if ($disk_used_percent > 95) {
      $disk_status = 'error';
      $health['status'] = 'unhealthy'; // Critical - mark as unhealthy
    } elseif ($disk_used_percent > 90) {
      $disk_status = 'warning';
    }
    
    $health['checks']['disk'] = [
      'status' => $disk_status,
      'message' => sprintf('Disk gebruik: %.1f%% (%.2f GB vrij van %.2f GB)', 
        $disk_used_percent, 
        $disk_free / 1024 / 1024 / 1024,
        $disk_total / 1024 / 1024 / 1024
      ),
      'free_gb' => round($disk_free / 1024 / 1024 / 1024, 2),
      'total_gb' => round($disk_total / 1024 / 1024 / 1024, 2),
      'used_percent' => round($disk_used_percent, 1)
    ];
  } else {
    $health['checks']['disk'] = [
      'status' => 'warning',
      'message' => 'Kon disk space niet bepalen'
    ];
  }
} catch (Exception $e) {
  $health['checks']['disk'] = [
    'status' => 'warning',
    'message' => 'Kon disk space niet controleren'
  ];
}

// 5. PHP Version Check
$php_version = PHP_VERSION;
$php_ok = version_compare($php_version, '7.4', '>=');
$health['checks']['php'] = [
  'status' => $php_ok ? 'ok' : 'warning',
  'message' => 'PHP versie: ' . $php_version . ($php_ok ? ' (OK)' : ' (Aanbevolen: 7.4+)'),
  'version' => $php_version
];

// 6. Database Size Check
try {
  $result = $conn->query("
    SELECT 
      ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb,
      COUNT(*) as table_count
    FROM information_schema.tables 
    WHERE table_schema = DATABASE()
  ");
  $db_info = $result->fetch_assoc();
  $health['checks']['database_size'] = [
    'status' => 'ok',
    'message' => sprintf('Database grootte: %.2f MB (%d tabellen)', 
      $db_info['size_mb'] ?? 0,
      $db_info['table_count'] ?? 0
    ),
    'size_mb' => round($db_info['size_mb'] ?? 0, 2),
    'table_count' => (int)($db_info['table_count'] ?? 0)
  ];
} catch (Exception $e) {
  $health['checks']['database_size'] = [
    'status' => 'error',
    'message' => 'Kon database grootte niet bepalen'
  ];
}

// 7. Recent Activity Check (optional - doesn't affect health)
try {
  $result = $conn->query("SHOW TABLES LIKE 'activity_logs'");
  if ($result && $result->num_rows > 0) {
    // Table exists, get activity count
    $result = $conn->query("
      SELECT COUNT(*) as count 
      FROM activity_logs 
      WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $activity = $result->fetch_assoc();
    $health['checks']['activity'] = [
      'status' => 'ok',
      'message' => sprintf('%d activiteiten in laatste 24 uur', $activity['count'] ?? 0),
      'count_24h' => (int)($activity['count'] ?? 0)
    ];
  } else {
    // Table doesn't exist - create it automatically
    try {
      $conn->query("
        CREATE TABLE IF NOT EXISTS `activity_logs` (
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `user_id` INT UNSIGNED DEFAULT NULL,
          `device_id` INT UNSIGNED DEFAULT NULL,
          `action` VARCHAR(50) NOT NULL COMMENT 'blocked, allowed, login, logout, device_added, etc.',
          `domain` VARCHAR(255) DEFAULT NULL,
          `url` TEXT DEFAULT NULL,
          `reason` VARCHAR(100) DEFAULT NULL COMMENT 'permanent_blocklist, global_blocklist, keyword_detection, etc.',
          `category` VARCHAR(50) DEFAULT NULL COMMENT 'pornography, gambling, etc.',
          `ip_address` VARCHAR(45) DEFAULT NULL,
          `user_agent` TEXT DEFAULT NULL,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          INDEX `idx_user_id` (`user_id`),
          INDEX `idx_device_id` (`device_id`),
          INDEX `idx_action` (`action`),
          INDEX `idx_domain` (`domain`(100)),
          INDEX `idx_reason` (`reason`),
          INDEX `idx_category` (`category`),
          INDEX `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
      ");
      $conn->query("CREATE INDEX IF NOT EXISTS `idx_created_at_date` ON `activity_logs` (`created_at`, `action`)");
      $health['checks']['activity'] = [
        'status' => 'ok',
        'message' => 'Activity logs tabel automatisch aangemaakt - 0 activiteiten in laatste 24 uur',
        'count_24h' => 0,
        'auto_created' => true
      ];
    } catch (Exception $e) {
      $health['checks']['activity'] = [
        'status' => 'warning',
        'message' => 'Activity logs tabel niet beschikbaar en kon niet worden aangemaakt: ' . $e->getMessage()
      ];
    }
  }
} catch (Exception $e) {
  $health['checks']['activity'] = [
    'status' => 'warning',
    'message' => 'Kon activity logs niet controleren: ' . $e->getMessage()
  ];
}

// 8. Last Backup Check
$backup_dir = __DIR__ . '/../backups';
$last_backup = null;

// Create backup directory if it doesn't exist
if (!is_dir($backup_dir)) {
  try {
    mkdir($backup_dir, 0755, true);
    $health['checks']['backup'] = [
      'status' => 'ok',
      'message' => 'Backup directory automatisch aangemaakt - nog geen backups',
      'last_backup' => null,
      'auto_created' => true
    ];
  } catch (Exception $e) {
    $health['checks']['backup'] = [
      'status' => 'warning',
      'message' => 'Backup directory kon niet worden aangemaakt: ' . $e->getMessage()
    ];
  }
} else {
  // Directory exists, check for backups
  $files = glob($backup_dir . '/*.sql');
  if (!empty($files)) {
    usort($files, function($a, $b) {
      return filemtime($b) - filemtime($a);
    });
    $last_backup = [
      'file' => basename($files[0]),
      'date' => date('Y-m-d H:i:s', filemtime($files[0])),
      'age_hours' => round((time() - filemtime($files[0])) / 3600, 1)
    ];
  }

  // Backup check is informational only - doesn't affect health status
  $backup_status = 'warning';
  if ($last_backup) {
    if ($last_backup['age_hours'] <= 24) {
      $backup_status = 'ok';
    } elseif ($last_backup['age_hours'] <= 48) {
      $backup_status = 'warning';
    } else {
      $backup_status = 'warning'; // Still warning, not error
    }
  }

  $health['checks']['backup'] = [
    'status' => $backup_status,
    'message' => $last_backup 
      ? sprintf('Laatste backup: %s (%.1f uur geleden)', $last_backup['date'], $last_backup['age_hours'])
      : 'Geen backups gevonden (optioneel)',
    'last_backup' => $last_backup
  ];
}
// Note: Backup warnings don't mark system as unhealthy

json_out($health);

