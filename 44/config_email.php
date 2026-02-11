<?php
/**
 * Email Configuration
 * Configure your email settings here
 */

// Email settings
define('EMAIL_FROM_ADDRESS', 'noreply@yourdomain.com');
define('EMAIL_FROM_NAME', 'Porno-vrij Platform');
define('EMAIL_REPLY_TO', 'support@yourdomain.com');
define('EMAIL_ADMIN_ADDRESS', 'admin@yourdomain.com'); // Admin email for notifications

// SMTP Settings (optional - if not set, uses PHP mail())
define('SMTP_ENABLED', false); // Set to true to use SMTP
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_ENCRYPTION', 'tls'); // 'tls' or 'ssl'

// Password reset link base URL
// Automatically detect base URL or use default
if (!defined('RESET_LINK_BASE_URL')) {
    if (isset($_SERVER['HTTP_HOST']) && isset($_SERVER['REQUEST_URI'])) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || 
                     (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) 
                     ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        // Get base path from current script location
        $script_path = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
        $script_path = str_replace('/api', '', $script_path);
        $script_path = rtrim($script_path, '/');
        if (empty($script_path) || $script_path === '.') {
            $script_path = '';
        } else {
            $script_path = '/' . ltrim($script_path, '/');
        }
        define('RESET_LINK_BASE_URL', $protocol . '://' . $host . $script_path . '/reset_password.php');
    } else {
        // Fallback for CLI or when SERVER vars not available
        define('RESET_LINK_BASE_URL', 'http://localhost/free/reset_password.php');
    }
}

/**
 * Send email using PHP mail() or SMTP
 */
function send_email($to, $subject, $message, $html = true) {
  if (SMTP_ENABLED) {
    return send_email_smtp($to, $subject, $message, $html);
  } else {
    return send_email_php($to, $subject, $message, $html);
  }
}

/**
 * Send email using PHP mail() function
 */
function send_email_php($to, $subject, $message, $html = true) {
  $headers = [];
  $headers[] = "From: " . EMAIL_FROM_NAME . " <" . EMAIL_FROM_ADDRESS . ">";
  $headers[] = "Reply-To: " . EMAIL_REPLY_TO;
  $headers[] = "X-Mailer: PHP/" . phpversion();
  
  if ($html) {
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-Type: text/html; charset=UTF-8";
  } else {
    $headers[] = "Content-Type: text/plain; charset=UTF-8";
  }
  
  $headers_string = implode("\r\n", $headers);
  
  return @mail($to, $subject, $message, $headers_string);
}

/**
 * Send email using SMTP
 */
function send_email_smtp($to, $subject, $message, $html = true) {
  // Simple SMTP implementation using sockets
  // For production, consider using PHPMailer or SwiftMailer
  
  $smtp = fsockopen(SMTP_HOST, SMTP_PORT, $errno, $errstr, 30);
  if (!$smtp) {
    error_log("SMTP Error: $errstr ($errno)");
    return false;
  }
  
  $response = fgets($smtp, 515);
  if (substr($response, 0, 3) != '220') {
    fclose($smtp);
    return false;
  }
  
  fputs($smtp, "EHLO " . SMTP_HOST . "\r\n");
  $response = fgets($smtp, 515);
  
  if (SMTP_ENCRYPTION === 'tls') {
    fputs($smtp, "STARTTLS\r\n");
    $response = fgets($smtp, 515);
    stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    fputs($smtp, "EHLO " . SMTP_HOST . "\r\n");
    $response = fgets($smtp, 515);
  }
  
  fputs($smtp, "AUTH LOGIN\r\n");
  $response = fgets($smtp, 515);
  
  fputs($smtp, base64_encode(SMTP_USERNAME) . "\r\n");
  $response = fgets($smtp, 515);
  
  fputs($smtp, base64_encode(SMTP_PASSWORD) . "\r\n");
  $response = fgets($smtp, 515);
  
  if (substr($response, 0, 3) != '235') {
    fclose($smtp);
    return false;
  }
  
  fputs($smtp, "MAIL FROM: <" . EMAIL_FROM_ADDRESS . ">\r\n");
  $response = fgets($smtp, 515);
  
  fputs($smtp, "RCPT TO: <" . $to . ">\r\n");
  $response = fgets($smtp, 515);
  
  fputs($smtp, "DATA\r\n");
  $response = fgets($smtp, 515);
  
  $email_headers = "From: " . EMAIL_FROM_NAME . " <" . EMAIL_FROM_ADDRESS . ">\r\n";
  $email_headers .= "Reply-To: " . EMAIL_REPLY_TO . "\r\n";
  $email_headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
  
  if ($html) {
    $email_headers .= "MIME-Version: 1.0\r\n";
    $email_headers .= "Content-Type: text/html; charset=UTF-8\r\n";
  } else {
    $email_headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
  }
  
  $email_headers .= "Subject: " . $subject . "\r\n";
  $email_headers .= "\r\n";
  
  fputs($smtp, $email_headers . $message . "\r\n.\r\n");
  $response = fgets($smtp, 515);
  
  fputs($smtp, "QUIT\r\n");
  fclose($smtp);
  
  return substr($response, 0, 3) == '250';
}

