<?php
/**
 * Super Admin Registration endpoint
 * Allows first user to register as super admin
 * If admin already exists, creates normal user
 */

// CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

require __DIR__ . '/../config.php';

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$email = trim((string)($body['email'] ?? ''));
$password = (string)($body['password'] ?? '');

if ($email === '' || $password === '') {
  json_out(['message' => 'Email en wachtwoord zijn verplicht'], 422);
}

if (strlen($password) < 6) {
  json_out(['message' => 'Wachtwoord moet minimaal 6 karakters lang zijn'], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  json_out(['message' => 'Ongeldig e-mailadres'], 422);
}

try {
  // Check if user already exists
  $stmt = $conn->prepare("SELECT id, is_admin FROM users WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $existing = $stmt->get_result()->fetch_assoc();
  
  if ($existing) {
    json_out(['message' => 'Dit e-mailadres is al geregistreerd'], 409);
  }

  // Check if any admin exists
  $stmt = $conn->prepare("SELECT COUNT(*) as admin_count FROM users WHERE is_admin = 1");
  $stmt->execute();
  $admin_check = $stmt->get_result()->fetch_assoc();
  $admin_exists = ($admin_check['admin_count'] ?? 0) > 0;

  // If no admin exists, create as super admin
  // Otherwise, create as normal user
  $is_admin = $admin_exists ? 0 : 1;

  // Create user
  $password_hash = password_hash($password, PASSWORD_DEFAULT);
  $stmt = $conn->prepare("INSERT INTO users (email, password_hash, is_admin) VALUES (?, ?, ?)");
  $stmt->bind_param("ssi", $email, $password_hash, $is_admin);
  $stmt->execute();
  $user_id = $stmt->insert_id;

  if ($is_admin) {
    json_out([
      'status' => 'created',
      'user_id' => $user_id,
      'is_admin' => true,
      'message' => 'Super Admin account succesvol aangemaakt! Je kunt nu inloggen.'
    ], 201);
  } else {
    json_out([
      'status' => 'created',
      'user_id' => $user_id,
      'is_admin' => false,
      'message' => 'Account aangemaakt als normale gebruiker (er bestaat al een admin).'
    ], 201);
  }

} catch (mysqli_sql_exception $e) {
  if ($conn->errno === 1062) {
    json_out(['message' => 'Dit e-mailadres is al geregistreerd'], 409);
  }
  error_log("Super admin registration error: " . $e->getMessage());
  json_out(['message' => 'Database error: ' . $e->getMessage()], 500);
}

