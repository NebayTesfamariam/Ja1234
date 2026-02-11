<?php
/**
 * Check Device Status and Whitelist
 * Helps diagnose why porno videos still load
 */

require __DIR__ . '/config.php';

header('Content-Type: application/json');

$device_id = (int)($_GET['device_id'] ?? 0);
$device_ip = trim($_GET['ip'] ?? '');

$results = [
    'device_found' => false,
    'device_info' => null,
    'whitelist' => [],
    'whitelist_count' => 0,
    'subscription' => null,
    'issues' => [],
    'recommendations' => []
];

try {
    // Find device by ID or IP
    if ($device_id > 0) {
        $stmt = $conn->prepare("SELECT * FROM devices WHERE id = ?");
        $stmt->bind_param("i", $device_id);
    } elseif (!empty($device_ip)) {
        $stmt = $conn->prepare("SELECT * FROM devices WHERE wg_ip = ?");
        $stmt->bind_param("s", $device_ip);
    } else {
        json_out([
            'error' => 'device_id or ip parameter required',
            'usage' => '?device_id=X or ?ip=10.10.0.X'
        ], 400);
        exit;
    }
    
    $stmt->execute();
    $device = $stmt->get_result()->fetch_assoc();
    
    if (!$device) {
        json_out([
            'device_found' => false,
            'message' => 'Device not found',
            'hint' => 'Check device_id or IP address'
        ], 404);
        exit;
    }
    
    $results['device_found'] = true;
    $results['device_info'] = [
        'id' => (int)$device['id'],
        'name' => $device['name'],
        'status' => $device['status'],
        'wg_ip' => $device['wg_ip'],
        'admin_created' => (bool)($device['admin_created'] ?? false),
        'auto_created' => (bool)($device['auto_created'] ?? false)
    ];
    
    // Check whitelist
    $stmt = $conn->prepare("SELECT domain FROM whitelist WHERE device_id = ? AND enabled = 1");
    $stmt->bind_param("i", $device['id']);
    $stmt->execute();
    $whitelist_result = $stmt->get_result();
    
    $whitelist = [];
    while ($row = $whitelist_result->fetch_assoc()) {
        $whitelist[] = $row['domain'];
    }
    
    $results['whitelist'] = $whitelist;
    $results['whitelist_count'] = count($whitelist);
    
    // Check subscription
    $stmt = $conn->prepare("
        SELECT s.*, p.max_devices
        FROM subscriptions s
        LEFT JOIN subscription_plans p ON p.name = s.plan
        WHERE s.user_id = ? 
          AND s.status = 'active' 
          AND s.start_date <= CURDATE()
          AND s.end_date >= CURDATE()
        ORDER BY s.created_at DESC
        LIMIT 1
    ");
    $stmt->bind_param("i", $device['user_id']);
    $stmt->execute();
    $subscription = $stmt->get_result()->fetch_assoc();
    
    if ($subscription) {
        $results['subscription'] = [
            'plan' => $subscription['plan'],
            'max_devices' => (int)($subscription['max_devices'] ?? 0),
            'status' => $subscription['status']
        ];
    }
    
    // Diagnose issues
    if ($device['status'] !== 'active') {
        $results['issues'][] = [
            'severity' => 'critical',
            'issue' => 'Device is not active',
            'status' => $device['status'],
            'fix' => 'Device must be active for filtering to work'
        ];
    }
    
    if (empty($whitelist)) {
        $results['issues'][] = [
            'severity' => 'info',
            'issue' => 'Whitelist is empty',
            'fix' => 'This is correct - nothing should work with empty whitelist'
        ];
    } else {
        // Check for common porno domains in whitelist
        $porno_keywords = ['porn', 'xxx', 'adult', 'sex'];
        $found_porno = [];
        foreach ($whitelist as $domain) {
            foreach ($porno_keywords as $keyword) {
                if (stripos($domain, $keyword) !== false) {
                    $found_porno[] = $domain;
                    break;
                }
            }
        }
        
        if (!empty($found_porno)) {
            $results['issues'][] = [
                'severity' => 'critical',
                'issue' => 'Porno domains found in whitelist',
                'domains' => $found_porno,
                'fix' => 'Remove these domains from whitelist immediately!'
            ];
        }
    }
    
    if (!$subscription && !$device['admin_created']) {
        $results['issues'][] = [
            'severity' => 'warning',
            'issue' => 'No active subscription',
            'fix' => 'Device may not work correctly without subscription'
        ];
    }
    
    // Recommendations
    if ($device['status'] !== 'active') {
        $results['recommendations'][] = 'Activate device in admin panel';
    }
    
    if (empty($whitelist)) {
        $results['recommendations'][] = 'Whitelist is empty - this is correct for whitelist-only system';
        $results['recommendations'][] = 'If porno still loads, check: VPN connection, DNS server, Chrome DoH, Firewall rules';
    }
    
    if (!empty($found_porno)) {
        $results['recommendations'][] = 'URGENT: Remove porno domains from whitelist!';
    }
    
    json_out($results);
    
} catch (Exception $e) {
    json_out([
        'error' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ], 500);
}
