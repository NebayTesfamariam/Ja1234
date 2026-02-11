<?php
/**
 * Debug endpoint to check authentication
 * Remove this file in production!
 */
require __DIR__ . '/../config.php';

$debug = [
    'method' => $_SERVER['REQUEST_METHOD'],
    'headers_getallheaders' => getallheaders(),
    'server_auth' => $_SERVER['HTTP_AUTHORIZATION'] ?? 'not set',
    'server_redirect_auth' => $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? 'not set',
    'all_server_http' => array_filter($_SERVER, fn($k) => str_starts_with($k, 'HTTP_'), ARRAY_FILTER_USE_KEY),
    'token_extracted' => get_bearer_token(),
];

if ($token = get_bearer_token()) {
    try {
        $decoded = base64_decode($token, true);
        if ($decoded && str_contains($decoded, ':')) {
            [$userId, $hashPrefix] = explode(':', $decoded, 2);
            $debug['decoded'] = ['user_id' => (int)$userId, 'hash_prefix' => $hashPrefix];
            
            $stmt = $conn->prepare("SELECT id, email, password_hash FROM users WHERE id=?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            
            if ($user) {
                $debug['user_found'] = true;
                $debug['hash_match'] = str_starts_with($user['password_hash'], $hashPrefix);
            } else {
                $debug['user_found'] = false;
            }
        }
    } catch (Exception $e) {
        $debug['error'] = $e->getMessage();
    }
}

json_out($debug);

