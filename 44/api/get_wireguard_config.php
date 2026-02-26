<?php
/**
 * Generate WireGuard client configuration file
 * ENFORCES FULL-TUNNEL: AllowedIPs = 0.0.0.0/0
 * 
 * This ensures ALL traffic goes through VPN
 */
require __DIR__ . '/../config.php';

// Support both authenticated and token-based access
$user = null;
$token = trim((string)($_GET['token'] ?? ''));

// Also check Authorization header for Bearer token
if (empty($token)) {
  $token = get_bearer_token();
}

if ($token) {
  // Token-based access (for auto-download after registration)
  try {
    $decoded = base64_decode($token, true);
    if ($decoded && strpos($decoded, ':') !== false) {
      $parts = explode(':', $decoded, 2);
      $user_id = (int)$parts[0];
      $stmt = $conn->prepare("SELECT id, email FROM users WHERE id = ?");
      $stmt->bind_param("i", $user_id);
      $stmt->execute();
      $user = $stmt->get_result()->fetch_assoc();
    }
  } catch (Exception $e) {
    // Token invalid, try normal auth
  }
}

if (!$user) {
  // Authenticated access (normal flow)
  try {
    $user = require_user($conn);
  } catch (Exception $e) {
    // Return as JSON error if not authenticated
    header('Content-Type: application/json');
    json_out(['message' => 'Authentication required'], 401);
    exit;
  }
}

$device_id = (int)($_GET['device_id'] ?? 0);
if ($device_id <= 0) {
  json_out(['message' => 'device_id required'], 422);
}

// Get device info
$stmt = $conn->prepare("SELECT id, name, wg_ip, wg_public_key, status FROM devices WHERE id=? AND user_id=?");
$stmt->bind_param("ii", $device_id, $user['id']);
$stmt->execute();
$device = $stmt->get_result()->fetch_assoc();

if (!$device) {
  json_out(['message' => 'Device not found'], 404);
}

if ($device['status'] !== 'active') {
  json_out(['message' => 'Device is not active'], 403);
}

// VPN Server Configuration (set once so downloaded configs are ready — no user editing)
if (file_exists(__DIR__ . '/../config_vpn.php')) {
  require __DIR__ . '/../config_vpn.php';
}
$VPN_SERVER_ENDPOINT = $VPN_SERVER_ENDPOINT ?? getenv('VPN_SERVER_ENDPOINT');
$VPN_SERVER_PUBLIC_KEY = $VPN_SERVER_PUBLIC_KEY ?? getenv('VPN_SERVER_PUBLIC_KEY');
$VPN_DNS = $VPN_DNS ?? getenv('VPN_DNS') ?: '10.10.0.1';

// Auto-derive endpoint from site domain if still placeholder (so config works with minimal setup)
$placeholder_endpoint = 'your-vpn-server.com:51820';
$placeholder_key = 'YOUR_VPN_SERVER_PUBLIC_KEY';
if (empty($VPN_SERVER_ENDPOINT) || $VPN_SERVER_ENDPOINT === $placeholder_endpoint) {
  $host = '';
  if (!empty(getenv('BASE_URL')) && preg_match('#^https?://([^/]+)#', getenv('BASE_URL'), $m)) {
    $host = strtolower(trim($m[1]));
  }
  if ($host === '' && !empty($_SERVER['HTTP_HOST'])) {
    $host = strtolower(trim(explode(':', $_SERVER['HTTP_HOST'])[0]));
  }
  if ($host !== '' && $host !== 'localhost' && $host !== '127.0.0.1') {
    $VPN_SERVER_ENDPOINT = 'vpn.' . $host . ':51820';
  } else {
    $VPN_SERVER_ENDPOINT = $placeholder_endpoint;
  }
}
if (empty($VPN_SERVER_PUBLIC_KEY) || $VPN_SERVER_PUBLIC_KEY === $placeholder_key) {
  $VPN_SERVER_PUBLIC_KEY = $placeholder_key;
}

// Client IP from device
$client_ip = $device['wg_ip'];
$client_public_key = $device['wg_public_key'];

// Generate WireGuard config with FULL-TUNNEL enforcement
$config = "[Interface]\n";
$config .= "Address = {$client_ip}/32\n";
$config .= "DNS = {$VPN_DNS}\n";
$config .= "PrivateKey = YOUR_PRIVATE_KEY_HERE\n";
$config .= "\n";
$config .= "[Peer]\n";
$config .= "PublicKey = {$VPN_SERVER_PUBLIC_KEY}\n";
$config .= "AllowedIPs = 0.0.0.0/0\n";  // FULL-TUNNEL: All traffic through VPN
$config .= "Endpoint = {$VPN_SERVER_ENDPOINT}\n";
$config .= "PersistentKeepalive = 25\n";

// Return as text/plain for download
header('Content-Type: text/plain');
header('Content-Disposition: attachment; filename="wireguard-' . $device['name'] . '.conf"');
echo $config;
exit;
