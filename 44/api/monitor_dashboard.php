<?php
/**
 * Real-Time Monitoring Dashboard API
 * Provides comprehensive system monitoring data
 */

require __DIR__ . '/../config.php';
$user = require_user($conn);

// Check admin
$stmt = $conn->prepare("SELECT is_admin FROM users WHERE id=?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
if (!$result || (int)($result['is_admin'] ?? 0) !== 1) {
    json_out(['message' => 'Access denied - Admin privileges required'], 403);
}

$metrics = [
    'timestamp' => date('Y-m-d H:i:s'),
    'system' => [],
    'devices' => [],
    'dns' => [],
    'security' => [],
    'performance' => []
];

try {
    // System Metrics
    $metrics['system'] = [
        'php_version' => PHP_VERSION,
        'server_time' => date('Y-m-d H:i:s'),
        'uptime' => get_system_uptime(),
        'memory_usage' => [
            'current' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'limit' => ini_get('memory_limit')
        ],
        'disk_usage' => get_disk_usage()
    ];
    
    // Device Metrics
    $stmt = $conn->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive,
            SUM(CASE WHEN admin_created = 1 THEN 1 ELSE 0 END) as admin_created,
            SUM(CASE WHEN auto_created = 1 THEN 1 ELSE 0 END) as auto_created
        FROM devices
    ");
    $device_stats = $stmt->fetch_assoc();
    
    $metrics['devices'] = [
        'total' => (int)$device_stats['total'],
        'active' => (int)$device_stats['active'],
        'inactive' => (int)$device_stats['inactive'],
        'admin_created' => (int)$device_stats['admin_created'],
        'auto_created' => (int)$device_stats['auto_created'],
        'recent_activity' => get_recent_device_activity($conn)
    ];
    
    // DNS Metrics
    $metrics['dns'] = [
        'server_status' => check_dns_server_status(),
        'queries_last_hour' => get_dns_query_count($conn, 3600),
        'blocked_queries' => get_blocked_query_count($conn, 3600),
        'cache_hit_rate' => get_dns_cache_hit_rate($conn)
    ];
    
    // Security Metrics
    $stmt = $conn->query("
        SELECT 
            COUNT(*) as total_logins,
            SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 ELSE 0 END) as logins_24h,
            SUM(CASE WHEN action = 'failed_login' THEN 1 ELSE 0 END) as failed_logins
        FROM activity_logs
        WHERE action IN ('login', 'failed_login')
    ");
    $security_stats = $stmt->fetch_assoc();
    
    $metrics['security'] = [
        'total_logins' => (int)$security_stats['total_logins'],
        'logins_24h' => (int)$security_stats['logins_24h'],
        'failed_logins' => (int)$security_stats['failed_logins'],
        'recent_threats' => get_recent_security_threats($conn),
        'rate_limit_blocks' => get_rate_limit_blocks($conn, 3600)
    ];
    
    // Performance Metrics
    $metrics['performance'] = [
        'api_response_times' => get_api_response_times($conn),
        'database_queries' => get_database_metrics($conn),
        'cache_performance' => get_cache_metrics($conn)
    ];
    
    // Subscription Metrics
    $stmt = $conn->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN end_date < CURDATE() THEN 1 ELSE 0 END) as expired
        FROM subscriptions
    ");
    $sub_stats = $stmt->fetch_assoc();
    
    $metrics['subscriptions'] = [
        'total' => (int)$sub_stats['total'],
        'active' => (int)$sub_stats['active'],
        'expired' => (int)$sub_stats['expired']
    ];
    
    // Whitelist Metrics
    $stmt = $conn->query("
        SELECT 
            COUNT(DISTINCT device_id) as devices_with_whitelist,
            COUNT(*) as total_domains,
            AVG(domain_count) as avg_domains_per_device
        FROM (
            SELECT device_id, COUNT(*) as domain_count
            FROM whitelist
            WHERE enabled = 1
            GROUP BY device_id
        ) as wl_stats
    ");
    $whitelist_stats = $stmt->fetch_assoc();
    
    $metrics['whitelist'] = [
        'devices_with_whitelist' => (int)($whitelist_stats['devices_with_whitelist'] ?? 0),
        'total_domains' => (int)($whitelist_stats['total_domains'] ?? 0),
        'avg_domains_per_device' => round((float)($whitelist_stats['avg_domains_per_device'] ?? 0), 2)
    ];
    
    json_out($metrics);
    
} catch (Exception $e) {
    json_out([
        'error' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ], 500);
}

// Helper Functions
function get_system_uptime() {
    if (PHP_OS_FAMILY === 'Linux') {
        $uptime = shell_exec('uptime -s 2>/dev/null');
        return $uptime ? trim($uptime) : 'Unknown';
    }
    return 'N/A';
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

function get_recent_device_activity($conn) {
    $stmt = $conn->query("
        SELECT device_id, COUNT(*) as activity_count
        FROM activity_logs
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        GROUP BY device_id
        ORDER BY activity_count DESC
        LIMIT 10
    ");
    
    $activities = [];
    while ($row = $stmt->fetch_assoc()) {
        $activities[] = [
            'device_id' => (int)$row['device_id'],
            'activity_count' => (int)$row['activity_count']
        ];
    }
    return $activities;
}

function check_dns_server_status() {
    // Check if DNS server is running on port 53
    $socket = @fsockopen('127.0.0.1', 53, $errno, $errstr, 1);
    if ($socket) {
        fclose($socket);
        return 'running';
    }
    return 'stopped';
}

function get_dns_query_count($conn, $seconds) {
    // This would need to be implemented with actual DNS query logging
    // For now, return placeholder
    return 0;
}

function get_blocked_query_count($conn, $seconds) {
    // This would need to be implemented with actual DNS query logging
    return 0;
}

function get_dns_cache_hit_rate($conn) {
    // This would need to be implemented with actual cache statistics
    return 0.0;
}

function get_recent_security_threats($conn) {
    $stmt = $conn->query("
        SELECT action, COUNT(*) as count
        FROM activity_logs
        WHERE action IN ('failed_login', 'rate_limit_exceeded', 'suspicious_activity')
          AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        GROUP BY action
        ORDER BY count DESC
    ");
    
    $threats = [];
    while ($row = $stmt->fetch_assoc()) {
        $threats[] = [
            'action' => $row['action'],
            'count' => (int)$row['count']
        ];
    }
    return $threats;
}

function get_rate_limit_blocks($conn, $seconds) {
    $stmt = $conn->query("
        SELECT COUNT(*) as count
        FROM activity_logs
        WHERE action = 'rate_limit_exceeded'
          AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
    ");
    $stmt->bind_param("i", $seconds);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return (int)($result['count'] ?? 0);
}

function get_api_response_times($conn) {
    // This would need to be implemented with actual API response time logging
    return [
        'avg_ms' => 0,
        'p95_ms' => 0,
        'p99_ms' => 0
    ];
}

function get_database_metrics($conn) {
    $stmt = $conn->query("SHOW STATUS LIKE 'Slow_queries'");
    $slow_queries = 0;
    if ($row = $stmt->fetch_assoc()) {
        $slow_queries = (int)$row['Value'];
    }
    
    return [
        'slow_queries' => $slow_queries,
        'connections' => $conn->thread_id
    ];
}

function get_cache_metrics($conn) {
    // This would need to be implemented with actual cache statistics
    return [
        'hit_rate' => 0.0,
        'miss_rate' => 0.0
    ];
}
