<?php

/**
 * Production Configuration
 * 
 * Gebruik dit bestand voor productie hosting
 * Kopieer naar config.php en pas de waarden aan
 */

declare(strict_types=1);

// Security: Disable error display in production
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// CORS Headers (pas aan naar jouw domein)
$allowed_origins = [
    'https://ja1234.com',
    'https://www.ja1234.com',
    // Voeg meer domeinen toe indien nodig
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: {$origin}");
} else {
    // In productie: alleen specifieke origins toestaan
    // header("Access-Control-Allow-Origin: https://ja1234.com");
}

header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Database Configuration - Hosting Gegevens
// Gebruik environment variables als beschikbaar, anders gebruik directe waarden
// Voor de meeste hosting providers: gebruik de directe waarden hieronder
// Define constants for use in config.php
if (!defined('PROD_DB_HOST')) {
  define('PROD_DB_HOST', getenv('DB_HOST') ?: "localhost");  // Meestal "localhost" op shared hosting
  define('PROD_DB_USER', getenv('DB_USER') ?: "u402299403_nebaytes");  // MySQL-gebruiker
  define('PROD_DB_PASS', getenv('DB_PASS') ?: "!@#Zebib2001#@!");  // ⚠️ VUL JE WACHTWOORD IN (MySQL-wachtwoord)
  define('PROD_DB_NAME', getenv('DB_NAME') ?: "u402299403_ja1234");  // MySQL-database naam
}

// Base URL (voor links en redirects)
$BASE_URL = getenv('BASE_URL') ?: "https://ja1234.com";

// Timezone
date_default_timezone_set('Europe/Amsterdam');

try {
    $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        // In productie: geen details naar gebruiker
        json_out(['message' => 'Database connection error'], 500);
    }
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    error_log("Database error: " . $e->getMessage());
    json_out(['message' => 'Database error'], 500);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    json_out(['message' => 'System error'], 500);
}

function json_out($data, int $code = 200): void
{
    http_response_code($code);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($data);
    exit;
}

function get_bearer_token(): ?string
{
    $headers = getallheaders();
    if ($headers) {
        $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    } else {
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    }

    if (empty($auth)) {
        return null;
    }

    if (preg_match('/Bearer\s+(\S+)/i', $auth, $matches)) {
        return trim($matches[1]);
    }

    return null;
}

// Token authentication
function require_user(mysqli $conn): array
{
    $token = get_bearer_token();
    if (!$token) json_out(['message' => 'Missing token'], 401);

    $decoded = base64_decode($token, true);
    if (!$decoded || !str_contains($decoded, ':')) json_out(['message' => 'Invalid token'], 401);

    [$userId, $hashPrefix] = explode(':', $decoded, 2);
    $userId = (int)$userId;

    $stmt = $conn->prepare("SELECT id, email, password_hash FROM users WHERE id=?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) json_out(['message' => 'Invalid token'], 401);
    if (!str_starts_with($user['password_hash'], $hashPrefix)) json_out(['message' => 'Invalid token'], 401);

    // Subscription check (same as config.php)
    static $check_counter = 0;
    $check_counter++;
    $should_check = ($check_counter % 10 === 0);

    if ($should_check) {
        try {
            // Expired subscriptions check
            $stmt = $conn->prepare("
                SELECT s.id, s.user_id
                FROM subscriptions s
                WHERE s.status = 'active'
                  AND s.end_date < CURDATE()
                LIMIT 10
            ");
            $stmt->execute();
            $expired_subs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            foreach ($expired_subs as $expired) {
                $user_id_expired = (int)$expired['user_id'];
                $sub_id = (int)$expired['id'];

                $stmt = $conn->prepare("UPDATE subscriptions SET status = 'expired' WHERE id = ?");
                $stmt->bind_param("i", $sub_id);
                $stmt->execute();

                $stmt = $conn->prepare("
                    UPDATE devices 
                    SET status = 'blocked'
                    WHERE user_id = ? 
                      AND status != 'blocked'
                      AND permanent_blocked = 0
                ");
                $stmt->bind_param("i", $user_id_expired);
                $stmt->execute();
            }
        } catch (Exception $e) {
            error_log("Error checking expired subscriptions: " . $e->getMessage());
        }
    }

    return $user;
}

function normalize_domain(string $domain): string
{
    $d = strtolower(trim($domain));
    $d = preg_replace('#^https?://#', '', $d);
    $d = preg_replace('#/.*$#', '', $d);
    $d = trim($d, ".");

    $parts = explode('.', $d);
    if (count($parts) >= 2) {
        $known_two_part_tlds = ['co.uk', 'com.au', 'co.za', 'com.br', 'co.jp', 'com.mx', 'com.ar', 'co.nz', 'com.sg', 'com.hk', 'com.tw', 'com.tr', 'com.pl', 'com.ru', 'com.cn', 'com.in', 'co.in'];
        $last_two = $parts[count($parts) - 2] . '.' . $parts[count($parts) - 1];

        if (count($parts) >= 3 && in_array($last_two, $known_two_part_tlds)) {
            $d = $parts[count($parts) - 3] . '.' . $last_two;
        } else {
            $d = $parts[count($parts) - 2] . '.' . $parts[count($parts) - 1];
        }
    }

    return $d;
}
