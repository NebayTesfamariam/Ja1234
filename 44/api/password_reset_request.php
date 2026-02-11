<?php
/**
 * Password Reset Request
 * User requests password reset - sends email with reset link
 */
require __DIR__ . '/../config.php';
require __DIR__ . '/../config_email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  json_out(['message' => 'Method not allowed'], 405);
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$email = trim((string)($body['email'] ?? ''));

if (empty($email)) {
  json_out(['message' => 'Email is required'], 422);
}

// Check if user exists
$stmt = $conn->prepare("SELECT id, email FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Always return success (don't reveal if email exists)
if (!$user) {
  json_out([
    'status' => 'success',
    'message' => 'Als dit email adres bestaat, ontvang je een wachtwoord reset link.'
  ]);
}

// Generate secure token
$token = bin2hex(random_bytes(32));
$expires_at = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token valid for 1 hour

// Create new token
try {
  // Check if table exists, create if not
  $table_check = $conn->query("SHOW TABLES LIKE 'password_reset_tokens'");
  if ($table_check->num_rows === 0) {
    // Create table if it doesn't exist (try with foreign key first, then without if it fails)
    try {
      $create_table = "CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT UNSIGNED NOT NULL,
        `token` VARCHAR(64) NOT NULL UNIQUE,
        `expires_at` DATETIME NOT NULL,
        `used` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        INDEX `idx_user_id` (`user_id`),
        INDEX `idx_token` (`token`),
        INDEX `idx_expires_at` (`expires_at`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
      $conn->query($create_table);
    } catch (mysqli_sql_exception $e) {
      // If foreign key fails, create without it
      $create_table = "CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT UNSIGNED NOT NULL,
        `token` VARCHAR(64) NOT NULL UNIQUE,
        `expires_at` DATETIME NOT NULL,
        `used` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_user_id` (`user_id`),
        INDEX `idx_token` (`token`),
        INDEX `idx_expires_at` (`expires_at`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
      $conn->query($create_table);
    }
  }
  
  // Invalidate any existing tokens for this user (only if table exists)
  try {
    $stmt = $conn->prepare("UPDATE password_reset_tokens SET used = 1 WHERE user_id = ? AND used = 0");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
  } catch (mysqli_sql_exception $e) {
    // Table might not exist yet, ignore
  }
  
  $stmt = $conn->prepare("INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
  $stmt->bind_param("iss", $user['id'], $token, $expires_at);
  $stmt->execute();
} catch (mysqli_sql_exception $e) {
  // Log error and return user-friendly message
  error_log("Password reset token creation error: " . $e->getMessage());
  json_out(['message' => 'Er is een fout opgetreden. Probeer het later opnieuw of neem contact op met de administrator.'], 500);
}

// Generate reset link
$reset_link = RESET_LINK_BASE_URL . '?token=' . urlencode($token);

// Email subject and message
$subject = "Wachtwoord Reset - Porno-vrij Platform";
$message = "
<!DOCTYPE html>
<html>
<head>
  <meta charset='UTF-8'>
  <style>
    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
    .header { background: linear-gradient(135deg, #4f7df9 0%, #3b5fe6 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
    .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
    .button { display: inline-block; background: #4f7df9; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; margin: 20px 0; font-weight: bold; }
    .button:hover { background: #3b5fe6; }
    .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
    .warning { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 8px; margin: 20px 0; }
  </style>
</head>
<body>
  <div class='container'>
    <div class='header'>
      <h1>🛡️ Wachtwoord Reset</h1>
    </div>
    <div class='content'>
      <p>Hallo,</p>
      <p>Je hebt een wachtwoord reset aangevraagd voor je account: <strong>" . htmlspecialchars($email) . "</strong></p>
      <p>Klik op de onderstaande knop om je wachtwoord te resetten:</p>
      <p style='text-align: center;'>
        <a href='" . htmlspecialchars($reset_link) . "' class='button'>Wachtwoord Resetten</a>
      </p>
      <p>Of kopieer en plak deze link in je browser:</p>
      <p style='word-break: break-all; background: #fff; padding: 10px; border-radius: 5px; font-size: 12px;'>" . htmlspecialchars($reset_link) . "</p>
      <div class='warning'>
        <strong>⚠️ Belangrijk:</strong>
        <ul style='margin: 10px 0; padding-left: 20px;'>
          <li>Deze link is <strong>1 uur</strong> geldig</li>
          <li>De link kan maar <strong>één keer</strong> worden gebruikt</li>
          <li>Als je deze reset niet hebt aangevraagd, negeer deze email dan</li>
        </ul>
      </div>
      <p>Met vriendelijke groet,<br><strong>Porno-vrij Platform</strong></p>
    </div>
    <div class='footer'>
      <p>Deze email is automatisch gegenereerd. Reageer niet op deze email.</p>
    </div>
  </div>
</body>
</html>
";

// Send email
$email_sent = send_email($email, $subject, $message, true);

if ($email_sent) {
  json_out([
    'status' => 'success',
    'message' => 'Als dit email adres bestaat, ontvang je een wachtwoord reset link.'
  ]);
} else {
  json_out([
    'status' => 'error',
    'message' => 'Er is een fout opgetreden bij het verzenden van de email. Probeer het later opnieuw of neem contact op met de administrator.'
  ], 500);
}

