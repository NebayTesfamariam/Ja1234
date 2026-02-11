<?php
/**
 * Notification Helper Functions
 * Send email notifications for important events
 */
require __DIR__ . '/config_email.php';

/**
 * Send notification email to admin
 */
function send_admin_notification($subject, $message, $priority = 'normal') {
  // Get admin email from config or database
  $admin_email = EMAIL_ADMIN_ADDRESS; // Default admin email for notifications
  
  // In production, load from database or config file if available
  // For now, use default from config_email.php
  
  if (empty($admin_email)) {
    return false; // No admin email configured
  }
  
  $html_message = "
    <html>
    <body style='font-family: Arial, sans-serif; padding: 20px;'>
      <h2 style='color: #1e3a8a;'>{$subject}</h2>
      <div style='background: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;'>
        {$message}
      </div>
      <p style='color: #6b7280; font-size: 12px; margin-top: 20px;'>
        Dit is een automatische notificatie van het Porno-vrij Platform.<br>
        Tijd: " . date('Y-m-d H:i:s') . "
      </p>
    </body>
    </html>
  ";
  
  return send_email($admin_email, "[Porno-vrij] {$subject}", $html_message, true);
}

/**
 * Notify when new user is created
 */
function notify_new_user($user_email, $user_id) {
  $subject = "Nieuwe gebruiker geregistreerd";
  $message = "
    <p><strong>Nieuwe gebruiker:</strong></p>
    <ul>
      <li>Email: <strong>{$user_email}</strong></li>
      <li>User ID: <strong>{$user_id}</strong></li>
      <li>Tijd: " . date('Y-m-d H:i:s') . "</li>
    </ul>
    <p>Je kunt deze gebruiker beheren via het Admin Panel.</p>
  ";
  
  return send_admin_notification($subject, $message);
}

/**
 * Notify when new device is added
 */
function notify_new_device($device_name, $user_email, $device_id) {
  $subject = "Nieuw device toegevoegd";
  $message = "
    <p><strong>Nieuw device geregistreerd:</strong></p>
    <ul>
      <li>Device: <strong>{$device_name}</strong></li>
      <li>Gebruiker: <strong>{$user_email}</strong></li>
      <li>Device ID: <strong>{$device_id}</strong></li>
      <li>Tijd: " . date('Y-m-d H:i:s') . "</li>
    </ul>
  ";
  
  return send_admin_notification($subject, $message);
}

/**
 * Notify when subscription expires
 */
function notify_expired_subscription($user_email, $plan, $end_date) {
  $subject = "⚠️ Abonnement verlopen";
  $message = "
    <p><strong>Abonnement is verlopen:</strong></p>
    <ul>
      <li>Gebruiker: <strong>{$user_email}</strong></li>
      <li>Plan: <strong>{$plan}</strong></li>
      <li>Einddatum: <strong>{$end_date}</strong></li>
      <li>Tijd: " . date('Y-m-d H:i:s') . "</li>
    </ul>
    <p style='color: #ef4444;'><strong>⚠️ Devices zijn automatisch geblokkeerd.</strong></p>
  ";
  
  return send_admin_notification($subject, $message, 'high');
}

/**
 * Notify on system errors
 */
function notify_system_error($error_message, $context = []) {
  $subject = "🚨 Systeem Error";
  $context_str = !empty($context) ? "<pre>" . print_r($context, true) . "</pre>" : "";
  $message = "
    <p style='color: #ef4444;'><strong>Er is een systeem error opgetreden:</strong></p>
    <div style='background: #fee2e2; padding: 15px; border-radius: 8px; border-left: 4px solid #ef4444; margin: 15px 0;'>
      <strong>Error:</strong><br>
      {$error_message}
    </div>
    {$context_str}
    <p>Tijd: " . date('Y-m-d H:i:s') . "</p>
  ";
  
  return send_admin_notification($subject, $message, 'high');
}

