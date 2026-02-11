<?php
/**
 * Validate WireGuard client configuration
 * Checks if config has AllowedIPs = 0.0.0.0/0 (full-tunnel)
 */
require __DIR__ . '/../config.php';

$config_content = $_POST['config'] ?? file_get_contents('php://input') ?? '';

if (empty($config_content)) {
  json_out([
    'valid' => false,
    'error' => 'No config content provided',
    'message' => 'Upload your WireGuard config file content'
  ], 400);
}

// Check for required full-tunnel settings
$has_allowed_ips = false;
$has_dns = false;
$allowed_ips_value = '';
$dns_value = '';

// Parse config
$lines = explode("\n", $config_content);
$in_peer_section = false;

foreach ($lines as $line) {
  $line = trim($line);
  
  if ($line === '[Peer]') {
    $in_peer_section = true;
    continue;
  }
  
  if ($line === '[Interface]') {
    $in_peer_section = false;
    continue;
  }
  
  if (preg_match('/^AllowedIPs\s*=\s*(.+)$/i', $line, $matches)) {
    $allowed_ips_value = trim($matches[1]);
    if ($allowed_ips_value === '0.0.0.0/0' || $allowed_ips_value === '0.0.0.0/0,::/0') {
      $has_allowed_ips = true;
    }
  }
  
  if (preg_match('/^DNS\s*=\s*(.+)$/i', $line, $matches)) {
    $dns_value = trim($matches[1]);
    $has_dns = true;
  }
}

// Validation results
$is_valid = $has_allowed_ips && $has_dns;
$errors = [];
$warnings = [];

if (!$has_allowed_ips) {
  $errors[] = 'AllowedIPs is not set to 0.0.0.0/0 (full-tunnel required)';
  if (!empty($allowed_ips_value)) {
    $errors[] = "Current AllowedIPs value: {$allowed_ips_value}";
  }
}

if (!$has_dns) {
  $warnings[] = 'DNS is not configured (recommended: 10.10.0.1)';
}

json_out([
  'valid' => $is_valid,
  'has_full_tunnel' => $has_allowed_ips,
  'has_dns' => $has_dns,
  'allowed_ips' => $allowed_ips_value ?: 'not found',
  'dns' => $dns_value ?: 'not found',
  'errors' => $errors,
  'warnings' => $warnings,
  'message' => $is_valid 
    ? '✅ Config is valid - full-tunnel enabled' 
    : '❌ Config is invalid - full-tunnel not enabled'
]);
