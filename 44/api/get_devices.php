<?php
require __DIR__ . '/../config.php';
$user = require_user($conn);

$stmt = $conn->prepare("SELECT id, name, wg_ip, wg_public_key, status, auto_created, permanent_blocked, admin_created, created_at FROM devices WHERE user_id=? ORDER BY id DESC");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$res = $stmt->get_result();

$devices = [];
while ($row = $res->fetch_assoc()) {
  $row['id'] = (int)$row['id'];
  $row['auto_created'] = (bool)$row['auto_created'];
  $row['permanent_blocked'] = (bool)($row['permanent_blocked'] ?? false);
  $row['admin_created'] = (bool)($row['admin_created'] ?? false);
  $devices[] = $row;
}
json_out(['devices' => $devices]);
