<?php
/**
 * Create Admin User Script
 * Creates an admin user if it doesn't exist
 */

require __DIR__ . '/config.php';

$email = $argv[1] ?? 'admin@test.com';
$password = $argv[2] ?? '123456';

echo "🔧 Creating Admin User\n";
echo "=====================\n\n";

// Check if user already exists
$stmt = $conn->prepare("SELECT id, email, is_admin FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();

if ($existing) {
  echo "⚠️  User already exists: {$email}\n";
  
  // Check if admin
  if ($existing['is_admin']) {
    echo "✅ User is already admin\n";
  } else {
    echo "📝 Updating user to admin...\n";
    $stmt = $conn->prepare("UPDATE users SET is_admin = 1 WHERE id = ?");
    $stmt->bind_param("i", $existing['id']);
    $stmt->execute();
    echo "✅ User updated to admin\n";
  }
  
  // Update password if provided
  if (isset($argv[2])) {
    echo "🔑 Updating password...\n";
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->bind_param("si", $hash, $existing['id']);
    $stmt->execute();
    echo "✅ Password updated\n";
  }
  
  exit(0);
}

// Create new admin user
echo "📝 Creating new admin user...\n";
echo "   Email: {$email}\n";
echo "   Password: {$password}\n\n";

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (email, password_hash, is_admin, created_at) VALUES (?, ?, 1, NOW())");
$stmt->bind_param("ss", $email, $hash);

if ($stmt->execute()) {
  $user_id = $conn->insert_id;
  echo "✅ Admin user created successfully!\n";
  echo "   User ID: {$user_id}\n";
  echo "   Email: {$email}\n";
  echo "   Is Admin: Yes\n\n";
  echo "🎉 You can now login with:\n";
  echo "   Email: {$email}\n";
  echo "   Password: {$password}\n";
} else {
  echo "❌ Error creating user: " . $conn->error . "\n";
  exit(1);
}
