<?php
/**
 * Enforce Unique Devices
 * Add database constraints to prevent duplicate devices
 * Run this once to add unique constraints to the database
 */

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

$results = [];

try {
  // Check if unique constraint on wg_public_key exists
  $stmt = $conn->query("SHOW INDEX FROM devices WHERE Key_name = 'unique_wg_public_key'");
  if ($stmt->num_rows === 0) {
    // Add unique constraint on wg_public_key
    $conn->query("ALTER TABLE devices ADD UNIQUE KEY `unique_wg_public_key` (`wg_public_key`)");
    $results[] = "✅ Unique constraint added on wg_public_key";
  } else {
    $results[] = "ℹ️ Unique constraint on wg_public_key already exists";
  }
} catch (Exception $e) {
  $results[] = "⚠️ Error adding unique constraint on wg_public_key: " . $e->getMessage();
}

try {
  // Check if unique constraint on wg_ip exists
  $stmt = $conn->query("SHOW INDEX FROM devices WHERE Key_name = 'unique_wg_ip'");
  if ($stmt->num_rows === 0) {
    // Add unique constraint on wg_ip
    $conn->query("ALTER TABLE devices ADD UNIQUE KEY `unique_wg_ip` (`wg_ip`)");
    $results[] = "✅ Unique constraint added on wg_ip";
  } else {
    $results[] = "ℹ️ Unique constraint on wg_ip already exists";
  }
} catch (Exception $e) {
  $results[] = "⚠️ Error adding unique constraint on wg_ip: " . $e->getMessage();
}

// Check for duplicate devices
try {
  // Find duplicate wg_public_key
  $duplicates_key = $conn->query("
    SELECT wg_public_key, COUNT(*) as count, GROUP_CONCAT(id) as device_ids
    FROM devices
    GROUP BY wg_public_key
    HAVING count > 1
  ")->fetch_all(MYSQLI_ASSOC);
  
  if (!empty($duplicates_key)) {
    $results[] = "⚠️ Found " . count($duplicates_key) . " duplicate WireGuard keys";
    foreach ($duplicates_key as $dup) {
      $results[] = "  - Key: {$dup['wg_public_key']} (devices: {$dup['device_ids']})";
    }
  } else {
    $results[] = "✅ No duplicate WireGuard keys found";
  }
  
  // Find duplicate wg_ip
  $duplicates_ip = $conn->query("
    SELECT wg_ip, COUNT(*) as count, GROUP_CONCAT(id) as device_ids
    FROM devices
    GROUP BY wg_ip
    HAVING count > 1
  ")->fetch_all(MYSQLI_ASSOC);
  
  if (!empty($duplicates_ip)) {
    $results[] = "⚠️ Found " . count($duplicates_ip) . " duplicate IP addresses";
    foreach ($duplicates_ip as $dup) {
      $results[] = "  - IP: {$dup['wg_ip']} (devices: {$dup['device_ids']})";
    }
  } else {
    $results[] = "✅ No duplicate IP addresses found";
  }
} catch (Exception $e) {
  $results[] = "⚠️ Error checking duplicates: " . $e->getMessage();
}

json_out([
  'status' => 'success',
  'message' => 'Unique device constraints enforced',
  'results' => $results
]);
