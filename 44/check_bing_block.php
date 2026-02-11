<?php
/**
 * Check if Bing is blocked and why it might still work
 */

require __DIR__ . '/config.php';

header('Content-Type: application/json');

$device_id = (int)($_GET['device_id'] ?? 0);

if (!$device_id) {
    json_out(['error' => 'device_id parameter required'], 400);
    exit;
}

$results = [
    'device_id' => $device_id,
    'bing_in_whitelist' => false,
    'bing_domains_found' => [],
    'whitelist' => [],
    'recommendations' => []
];

try {
    // Get whitelist for device
    $stmt = $conn->prepare("SELECT domain FROM whitelist WHERE device_id = ? AND enabled = 1");
    $stmt->bind_param("i", $device_id);
    $stmt->execute();
    $whitelist_result = $stmt->get_result();
    
    $whitelist = [];
    $bing_domains = [];
    
    while ($row = $whitelist_result->fetch_assoc()) {
        $domain = strtolower(trim($row['domain']));
        $whitelist[] = $domain;
        
        // Check if any Bing-related domains are in whitelist
        if (strpos($domain, 'bing') !== false || 
            strpos($domain, 'microsoft') !== false ||
            $domain === 'www.bing.com' ||
            $domain === 'bing.com') {
            $bing_domains[] = $domain;
            $results['bing_in_whitelist'] = true;
        }
    }
    
    $results['whitelist'] = $whitelist;
    $results['bing_domains_found'] = $bing_domains;
    
    // Recommendations
    if ($results['bing_in_whitelist']) {
        $results['recommendations'][] = '❌ BING.COM STAAT IN WHITELIST - Dit is het probleem!';
        $results['recommendations'][] = 'Verwijder bing.com en alle microsoft.com domeinen uit whitelist';
        $results['recommendations'][] = 'Bing kan porno tonen via search results - moet geblokkeerd worden';
    } else {
        $results['recommendations'][] = '✅ Bing.com staat NIET in whitelist - dit is goed';
        $results['recommendations'][] = 'Als Bing nog werkt, check:';
        $results['recommendations'][] = '  1. VPN verbinding (moet VPN IP zijn)';
        $results['recommendations'][] = '  2. DNS server (moet draaien op 10.10.0.1:53)';
        $results['recommendations'][] = '  3. Chrome DoH (moet UIT zijn)';
        $results['recommendations'][] = '  4. Firewall regels (QUIC, DoT, DNS forcing)';
    }
    
    // Check device status
    $stmt = $conn->prepare("SELECT status FROM devices WHERE id = ?");
    $stmt->bind_param("i", $device_id);
    $stmt->execute();
    $device = $stmt->get_result()->fetch_assoc();
    
    if ($device) {
        $results['device_status'] = $device['status'];
        if ($device['status'] !== 'active') {
            $results['recommendations'][] = '⚠️ Device is niet actief - activeer device eerst';
        }
    }
    
    json_out($results);
    
} catch (Exception $e) {
    json_out([
        'error' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ], 500);
}
