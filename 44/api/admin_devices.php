<?php
// TEMPORARY: Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../config.php';
$user = require_user($conn);

// Check admin
$stmt = $conn->prepare("SELECT is_admin FROM users WHERE id=?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$is_admin = $result && (int)($result['is_admin'] ?? 0) === 1;
if (!$is_admin) {
  error_log("Admin access denied for user_id: {$user['id']}, is_admin: " . ($result['is_admin'] ?? 'null'));
  json_out([
    'message' => 'Access denied - Admin privileges required',
    'user_id' => $user['id'],
    'is_admin' => (int)($result['is_admin'] ?? 0)
  ], 403);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  // Get all devices with user info
  $stmt = $conn->prepare("
    SELECT d.id, d.user_id, d.name, d.wg_ip, d.wg_public_key, d.status, d.auto_created, d.permanent_blocked, d.admin_created, d.created_at,
           u.email as user_email,
           COUNT(DISTINCT w.id) as whitelist_count
    FROM devices d
    JOIN users u ON u.id = d.user_id
    LEFT JOIN whitelist w ON w.device_id = d.id
    GROUP BY d.id
    ORDER BY d.id DESC
  ");
  $stmt->execute();
  $res = $stmt->get_result();
  
  $devices = [];
  while ($row = $res->fetch_assoc()) {
    $row['id'] = (int)$row['id'];
    $row['user_id'] = (int)$row['user_id'];
    $row['auto_created'] = (bool)$row['auto_created'];
    $row['permanent_blocked'] = (bool)($row['permanent_blocked'] ?? false);
    $row['admin_created'] = (bool)($row['admin_created'] ?? false);
    $devices[] = $row;
  }
  
  json_out(['devices' => $devices]);
  
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Update device status
  $body = json_decode(file_get_contents('php://input'), true) ?? [];
  $device_id = (int)($body['device_id'] ?? 0);
  $status = (string)($body['status'] ?? '');
  
  // Allow 'blocked' status for easy blocking
  if ($device_id <= 0 || !in_array($status, ['active', 'inactive', 'pending', 'blocked'])) {
    json_out(['message' => 'Invalid device_id or status'], 422);
  }
  
  // Check if device is admin_created or permanent_blocked
  $stmt = $conn->prepare("SELECT admin_created, permanent_blocked FROM devices WHERE id = ?");
  $stmt->bind_param("i", $device_id);
  $stmt->execute();
  $device_info = $stmt->get_result()->fetch_assoc();
  
  $is_admin_created = $device_info && (int)$device_info['admin_created'] === 1;
  $is_permanent_blocked = $device_info && (int)$device_info['permanent_blocked'] === 1;
  
  // BELANGRIJK: Admin-created devices kunnen NOOIT worden geblokkeerd - ze hebben lifetime toegang en zijn ALTIJD actief
  if ($is_admin_created && $status !== 'active') {
    // Force status back to active
    $stmt = $conn->prepare("UPDATE devices SET status = 'active' WHERE id = ?");
    $stmt->bind_param("i", $device_id);
    $stmt->execute();
    
    json_out([
      'message' => 'Dit device is aangemaakt door admin en heeft PERMANENTE TOEGANG. Het kan NOOIT worden geblokkeerd - altijd actief.',
      'error' => 'admin_created_permanent',
      'admin_created' => true,
      'status_forced' => 'active',
      'note' => 'Admin-created devices zijn ALTIJD actief. Ze kunnen niet worden geblokkeerd, gedeactiveerd of verwijderd.'
    ], 403);
  }
  
  // Check if device is permanent_blocked by admin - these can NEVER be unblocked
  if ($is_permanent_blocked && $status === 'active') {
    json_out([
      'message' => 'Dit device is permanent geblokkeerd door admin. Het kan NOOIT worden deblokkeerd.',
      'error' => 'permanent_blocked',
      'permanent_blocked' => true
    ], 403);
  }
  
  $stmt = $conn->prepare("UPDATE devices SET status = ? WHERE id = ?");
  $stmt->bind_param("si", $status, $device_id);
  $stmt->execute();
  
  json_out(['status' => 'updated']);
  
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
  // Create new device (admin only, permanent access, no subscription required)
  $body = json_decode(file_get_contents('php://input'), true) ?? [];
  $user_id = (int)($body['user_id'] ?? 0);
  $name = trim((string)($body['name'] ?? ''));
  $wg_public_key = trim((string)($body['wg_public_key'] ?? ''));
  $wg_ip = trim((string)($body['wg_ip'] ?? ''));
  
  if ($user_id <= 0) {
    json_out(['message' => 'user_id required'], 422);
  }
  
  // AUTOMATISCH: Generate device name if not provided
  if (empty($name)) {
    // Get user's device count to generate unique name
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM devices WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $device_count = (int)$stmt->get_result()->fetch_assoc()['count'];
    
    $device_names = ['iPhone', 'Android', 'Windows PC', 'MacBook', 'iPad', 'Tablet', 'Laptop'];
    $random_name = $device_names[array_rand($device_names)];
    $name = $device_count > 0 ? "{$random_name} " . ($device_count + 1) : $random_name;
  }
  
  // Check if user exists
  $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  if (!$stmt->get_result()->fetch_assoc()) {
    json_out(['message' => 'User not found'], 404);
  }
  
  // Generate WireGuard key if not provided
  if (empty($wg_public_key)) {
    $wg_public_key = base64_encode(random_bytes(32));
  }
  
  // Generate IP if not provided
  if (empty($wg_ip)) {
    // Find next available IP
    $stmt = $conn->prepare("SELECT wg_ip FROM devices WHERE wg_ip LIKE '10.10.0.%' ORDER BY wg_ip DESC LIMIT 1");
    $stmt->execute();
    $last_ip = $stmt->get_result()->fetch_assoc();
    $ip_num = 10;
    
    if ($last_ip) {
      $parts = explode('.', $last_ip['wg_ip']);
      $ip_num = (int)$parts[3] + 1;
      if ($ip_num > 254) $ip_num = 10;
    }
    
    $wg_ip = "10.10.0.{$ip_num}";
  }
  
  // STRICT: Prevent duplicate devices - check multiple criteria
  // Check 1: By WireGuard key (GLOBAL - must be unique)
  $stmt = $conn->prepare("SELECT id, name, status, wg_public_key, wg_ip, user_id FROM devices WHERE wg_public_key = ? LIMIT 1");
  $stmt->bind_param("s", $wg_public_key);
  $stmt->execute();
  $existing_by_key = $stmt->get_result()->fetch_assoc();
  
  if ($existing_by_key) {
    if ((int)$existing_by_key['user_id'] === (int)$user_id) {
      json_out([
        'status' => 'exists',
        'device_id' => (int)$existing_by_key['id'],
        'device_name' => $existing_by_key['name'],
        'status' => $existing_by_key['status'],
        'message' => 'Device bestaat al voor deze gebruiker - dit device is al geregistreerd',
        'note' => 'Je kunt dit device gebruiken zonder het opnieuw toe te voegen.'
      ], 200);
    } else {
      json_out([
        'status' => 'exists',
        'message' => 'Dit WireGuard key is al in gebruik door een ander device',
        'error' => 'duplicate_wg_key'
      ], 409);
    }
  }
  
  // Check 2: By IP address (GLOBAL - must be unique)
  $stmt = $conn->prepare("SELECT id, name, status, wg_public_key, wg_ip, user_id FROM devices WHERE wg_ip = ? LIMIT 1");
  $stmt->bind_param("s", $wg_ip);
  $stmt->execute();
  $existing_by_ip = $stmt->get_result()->fetch_assoc();
  
  if ($existing_by_ip) {
    if ((int)$existing_by_ip['user_id'] === (int)$user_id) {
      json_out([
        'status' => 'exists',
        'device_id' => (int)$existing_by_ip['id'],
        'device_name' => $existing_by_ip['name'],
        'status' => $existing_by_ip['status'],
        'message' => 'Device bestaat al voor deze gebruiker - dit device is al geregistreerd',
        'note' => 'Je kunt dit device gebruiken zonder het opnieuw toe te voegen.'
      ], 200);
    } else {
      json_out([
        'status' => 'exists',
        'message' => 'Dit IP adres is al in gebruik door een ander device',
        'error' => 'duplicate_ip'
      ], 409);
    }
  }
  
  // Check 3: By device name for same user (prevent same device name for same user)
  if (!empty($name)) {
    $stmt = $conn->prepare("
      SELECT id, name, status, wg_public_key, wg_ip, created_at
      FROM devices 
      WHERE user_id = ? 
        AND name = ?
        AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
      ORDER BY created_at DESC
      LIMIT 1
    ");
    $stmt->bind_param("is", $user_id, $name);
    $stmt->execute();
    $recent_device = $stmt->get_result()->fetch_assoc();
    
    if ($recent_device) {
      json_out([
        'status' => 'exists',
        'device_id' => (int)$recent_device['id'],
        'device_name' => $recent_device['name'],
        'status' => $recent_device['status'],
        'message' => "Device '{$name}' bestaat al voor deze gebruiker - dit device is al geregistreerd",
        'note' => 'Je kunt dit device gebruiken zonder het opnieuw toe te voegen.'
      ], 200);
    }
  }
  
  // Create device with admin_created = 1 (permanent access, cannot be deleted, ALWAYS active)
  // ALWAYS active, regardless of subscription - CANNOT be blocked
  // ALL DATA IS AUTOMATICALLY SET - device works immediately!
  $admin_created = 1;
  $status = 'active'; // Always active for admin-created devices - CANNOT be changed
  $stmt = $conn->prepare("INSERT INTO devices (user_id, name, wg_public_key, wg_ip, status, admin_created) VALUES (?, ?, ?, ?, ?, ?)");
  $stmt->bind_param("issssi", $user_id, $name, $wg_public_key, $wg_ip, $status, $admin_created);
  $stmt->execute();
  $device_id = $stmt->insert_id;
  
  // AUTOMATISCH: Ensure device is always active (double check - redundant but safe)
  // Force status to active - admin-created devices are ALWAYS active
  // Devices via admin panel werken automatisch - geen handmatige actie nodig
  $stmt = $conn->prepare("UPDATE devices SET status = 'active', admin_created = 1 WHERE id = ?");
  $stmt->bind_param("i", $device_id);
  $stmt->execute();
  
  // AUTOMATISCH: Triple check: Force status to active one more time to ensure it's always active
  $stmt = $conn->prepare("UPDATE devices SET status = 'active' WHERE id = ? AND admin_created = 1");
  $stmt->bind_param("i", $device_id);
  $stmt->execute();
  
  // AUTOMATISCH: Quadruple check: Ensure admin-created devices are always active
  // This ensures devices via admin panel automatically work
  $stmt = $conn->prepare("UPDATE devices SET status = 'active' WHERE admin_created = 1");
  $stmt->execute();
  
  // Get user email for notification
  $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $user_data = $stmt->get_result()->fetch_assoc();
  $user_email = $user_data['email'] ?? 'Onbekend';
  
  // Send notification email
  try {
    require __DIR__ . '/../config_notifications.php';
    notify_new_device($name, $user_email, $device_id);
  } catch (Exception $e) {
    // Notification failed, but device creation succeeded
    error_log("Notification error: " . $e->getMessage());
  }
  
  // Return complete information - everything is automatically set and ready to use!
  json_out([
    'status' => 'created',
    'device_id' => $device_id,
    'device_name' => $name,
    'wg_ip' => $wg_ip,
    'wg_public_key' => $wg_public_key,
    'status' => 'active',
    'admin_created' => true,
    'permanent_access' => true,
    'ready_to_use' => true,
    'message' => '✅ Device automatisch toegevoegd en direct klaar voor gebruik!',
    'auto_configured' => [
      'status' => 'active',
      'admin_created' => true,
      'wireguard_key' => 'automatisch gegenereerd',
      'ip_address' => 'automatisch toegewezen',
      'permanent_access' => true,
      'no_subscription_required' => true,
      'cannot_be_deleted' => true,
      'system_ready' => true
    ],
    'note' => 'Dit device werkt DIRECT - alle gegevens zijn automatisch ingesteld. Geen extra configuratie nodig!'
  ], 201);
  
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
  // PERMANENT: Devices kunnen NOOIT worden verwijderd - ze zijn permanent in het systeem
  // Dit zorgt ervoor dat het systeem altijd werkt en devices altijd beschikbaar blijven
  json_out([
    'message' => '⚠️ PERMANENT SYSTEEM - Devices kunnen NOOIT worden verwijderd',
    'error' => 'devices_permanent',
    'note' => 'Alle devices zijn permanent in het systeem. Ze kunnen worden geblokkeerd maar niet verwijderd. Dit zorgt voor systeem stabiliteit en continuïteit.',
    'permanent' => true,
    'cannot_delete' => true,
    'system_rule' => 'Devices zijn permanent - verwijderen is niet mogelijk'
  ], 403);
}

