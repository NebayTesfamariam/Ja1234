<?php
/**
 * Password Reset
 * User resets password using token from email
 */
require __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  json_out(['message' => 'Method not allowed'], 405);
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$token = trim((string)($body['token'] ?? ''));
$new_password = trim((string)($body['password'] ?? ''));

if (empty($token)) {
  json_out(['message' => 'Token is required'], 422);
}

if (empty($new_password)) {
  json_out(['message' => 'Password is required'], 422);
}

if (strlen($new_password) < 6) {
  json_out(['message' => 'Password must be at least 6 characters'], 422);
}

// Find valid token
$stmt = $conn->prepare("
  SELECT prt.id, prt.user_id, u.email
  FROM password_reset_tokens prt
  JOIN users u ON u.id = prt.user_id
  WHERE prt.token = ?
    AND prt.used = 0
    AND prt.expires_at > NOW()
  LIMIT 1
");
$stmt->bind_param("s", $token);
$stmt->execute();
$token_data = $stmt->get_result()->fetch_assoc();

if (!$token_data) {
  json_out([
    'message' => 'Ongeldige of verlopen reset link. Vraag een nieuwe reset link aan.'
  ], 400);
}

// Hash new password
$password_hash = password_hash($new_password, PASSWORD_DEFAULT);

// Update user password
$stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
$stmt->bind_param("si", $password_hash, $token_data['user_id']);
$stmt->execute();

// Mark token as used
$stmt = $conn->prepare("UPDATE password_reset_tokens SET used = 1 WHERE id = ?");
$stmt->bind_param("i", $token_data['id']);
$stmt->execute();

json_out([
  'status' => 'success',
  'message' => 'Wachtwoord succesvol gereset. Je kunt nu inloggen met je nieuwe wachtwoord.'
]);

