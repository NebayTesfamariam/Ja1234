<?php
/**
 * Make user admin (for setup/debugging)
 * WARNING: This should be removed or secured in production!
 */
require __DIR__ . '/../config.php';

// Allow direct access for setup (remove in production!)
// In production, add authentication check here

$email = $_GET['email'] ?? $_POST['email'] ?? '';
if (empty($email)) {
  json_out(['message' => 'Email required'], 422);
}

try {
  // Find user
  $stmt = $conn->prepare("SELECT id, email, is_admin FROM users WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $user = $stmt->get_result()->fetch_assoc();
  
  if (!$user) {
    json_out(['message' => 'User not found'], 404);
  }
  
  // Make user admin
  $stmt = $conn->prepare("UPDATE users SET is_admin = 1 WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  
  json_out([
    'success' => true,
    'message' => "User {$email} is now admin",
    'user' => [
      'id' => (int)$user['id'],
      'email' => $user['email'],
      'is_admin' => true
    ]
  ]);
} catch (Exception $e) {
  json_out(['message' => 'Error: ' . $e->getMessage()], 500);
}
