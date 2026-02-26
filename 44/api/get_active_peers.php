<?php
/**
 * Return active WireGuard peers for VPN server sync.
 * Used by the VPN server to add/update peers (wg set wg0 peer ... allowed-ips ...).
 * Requires X-VPN-Sync-Key header matching VPN_SYNC_KEY (or DNS_INTERNAL_KEY).
 */
require __DIR__ . '/../config.php';

$key = $_SERVER['HTTP_X_VPN_SYNC_KEY'] ?? $_GET['key'] ?? '';
$expected = getenv('VPN_SYNC_KEY') ?: getenv('DNS_INTERNAL_KEY');
if ($expected === false || $expected === '' || $key !== $expected) {
  header('Content-Type: application/json');
  json_out(['message' => 'Unauthorized'], 401);
  exit;
}

$stmt = $conn->prepare("SELECT wg_public_key, wg_ip FROM devices WHERE status = 'active' AND wg_public_key != '' AND wg_ip != '' ORDER BY id ASC");
$stmt->execute();
$res = $stmt->get_result();
$peers = [];
while ($row = $res->fetch_assoc()) {
  $peers[] = [
    'public_key' => trim($row['wg_public_key']),
    'allowed_ips' => trim($row['wg_ip']) . '/32',
  ];
}

header('Content-Type: application/json');
header('Cache-Control: no-store');
echo json_encode(['peers' => $peers], JSON_UNESCAPED_SLASHES);
