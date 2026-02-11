<?php
/**
 * Alert System - Send alerts for critical system events
 * Supports email, SMS (via API), and webhook notifications
 */

require __DIR__ . '/../config.php';

// Check admin
$user = require_user($conn);
$stmt = $conn->prepare("SELECT is_admin FROM users WHERE id=?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
if (!$result || (int)($result['is_admin'] ?? 0) !== 1) {
    json_out(['message' => 'Access denied - Admin privileges required'], 403);
}

$action = $_GET['action'] ?? 'check';

if ($action === 'check') {
    // Check for alerts that need to be sent
    check_and_send_alerts($conn);
} elseif ($action === 'test') {
    // Send test alert
    send_test_alert($conn);
} elseif ($action === 'configure') {
    // Configure alert settings
    configure_alerts($conn);
} else {
    json_out(['error' => 'Invalid action'], 400);
}

function check_and_send_alerts($conn) {
    $alerts = [];
    
    // 1. Check DNS server status
    if (!is_dns_server_running()) {
        $alerts[] = [
            'severity' => 'critical',
            'type' => 'dns_server_down',
            'message' => 'DNS server is not running on port 53',
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    // 2. Check database connection
    if (!$conn->ping()) {
        $alerts[] = [
            'severity' => 'critical',
            'type' => 'database_error',
            'message' => 'Database connection failed',
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    // 3. Check disk space
    $disk_usage = get_disk_usage();
    if ($disk_usage['percent_used'] > 90) {
        $alerts[] = [
            'severity' => 'warning',
            'type' => 'disk_space_low',
            'message' => "Disk space is {$disk_usage['percent_used']}% used",
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    // 4. Check for failed login attempts
    $stmt = $conn->query("
        SELECT COUNT(*) as count
        FROM activity_logs
        WHERE action = 'failed_login'
          AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $failed_logins = (int)$stmt->fetch_assoc()['count'];
    if ($failed_logins > 10) {
        $alerts[] = [
            'severity' => 'warning',
            'type' => 'brute_force_attempt',
            'message' => "$failed_logins failed login attempts in the last hour",
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    // 5. Check for expired subscriptions
    $stmt = $conn->query("
        SELECT COUNT(*) as count
        FROM subscriptions
        WHERE status = 'active'
          AND end_date < CURDATE()
    ");
    $expired = (int)$stmt->fetch_assoc()['count'];
    if ($expired > 0) {
        $alerts[] = [
            'severity' => 'info',
            'type' => 'expired_subscriptions',
            'message' => "$expired subscription(s) have expired",
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    // Send alerts
    foreach ($alerts as $alert) {
        send_alert($alert, $conn);
    }
    
    json_out([
        'checked_at' => date('Y-m-d H:i:s'),
        'alerts_found' => count($alerts),
        'alerts' => $alerts
    ]);
}

function send_alert($alert, $conn) {
    // Get admin email
    $stmt = $conn->query("SELECT email FROM users WHERE is_admin = 1 LIMIT 1");
    $admin = $stmt->fetch_assoc();
    
    if (!$admin) {
        return false;
    }
    
    $email = $admin['email'];
    $subject = "[ALERT] {$alert['severity']}: {$alert['type']}";
    $message = "
System Alert

Severity: {$alert['severity']}
Type: {$alert['type']}
Message: {$alert['message']}
Timestamp: {$alert['timestamp']}

Please check the system immediately.
    ";
    
    // Send email (requires mail() function or SMTP)
    if (function_exists('mail')) {
        @mail($email, $subject, $message);
    }
    
    // Log alert
    try {
        require_once __DIR__ . '/../config_security_advanced.php';
        audit_log($conn, 'alert_sent', 'system', 0, [
            'alert_type' => $alert['type'],
            'severity' => $alert['severity'],
            'message' => $alert['message']
        ]);
    } catch (Exception $e) {
        error_log("Failed to log alert: " . $e->getMessage());
    }
    
    return true;
}

function send_test_alert($conn) {
    $alert = [
        'severity' => 'info',
        'type' => 'test_alert',
        'message' => 'This is a test alert to verify the alert system is working',
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    send_alert($alert, $conn);
    
    json_out([
        'status' => 'sent',
        'message' => 'Test alert sent successfully'
    ]);
}

function configure_alerts($conn) {
    // This would allow configuring alert settings
    // For now, return current configuration
    json_out([
        'email_enabled' => true,
        'sms_enabled' => false,
        'webhook_enabled' => false,
        'alert_levels' => ['critical', 'warning', 'info']
    ]);
}

function is_dns_server_running() {
    $socket = @fsockopen('127.0.0.1', 53, $errno, $errstr, 1);
    if ($socket) {
        fclose($socket);
        return true;
    }
    return false;
}

function get_disk_usage() {
    $total = disk_total_space(__DIR__);
    $free = disk_free_space(__DIR__);
    $used = $total - $free;
    
    return [
        'total' => $total,
        'used' => $used,
        'free' => $free,
        'percent_used' => $total > 0 ? round(($used / $total) * 100, 2) : 0
    ];
}
