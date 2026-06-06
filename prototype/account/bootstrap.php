<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/api/common.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('kc_customer_session');
    session_set_cookie_params([
        'lifetime' => 60 * 60 * 24 * 30,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function kc_customer_config(): array
{
    static $config;
    if (!$config) {
        $config = kc_config();
    }
    return $config;
}

function kc_customer_pdo(): PDO
{
    static $pdo;
    if (!$pdo) {
        $pdo = kc_db_required(kc_customer_config());
        kc_install_schema($pdo);
    }
    return $pdo;
}

function kc_google_configured(): bool
{
    $google = (array)(kc_customer_config()['google'] ?? []);
    return trim((string)($google['client_id'] ?? '')) !== ''
        && trim((string)($google['client_secret'] ?? '')) !== ''
        && trim((string)($google['redirect_uri'] ?? '')) !== '';
}

function kc_customer_session(): ?array
{
    return isset($_SESSION['kc_customer']) && is_array($_SESSION['kc_customer'])
        ? $_SESSION['kc_customer']
        : null;
}

function kc_customer_require(): array
{
    $customer = kc_customer_session();
    if (!$customer) {
        kc_json(['ok' => false, 'message' => 'Oturum acmaniz gerekiyor.'], 401);
    }
    return $customer;
}

function kc_customer_csrf(): string
{
    if (empty($_SESSION['kc_customer_csrf'])) {
        $_SESSION['kc_customer_csrf'] = bin2hex(random_bytes(24));
    }
    return (string)$_SESSION['kc_customer_csrf'];
}

function kc_customer_verify_csrf(): void
{
    $token = (string)($_POST['csrf'] ?? '');
    if ($token === '' || !hash_equals(kc_customer_csrf(), $token)) {
        kc_json(['ok' => false, 'message' => 'Gecersiz veya suresi dolmus istek.'], 419);
    }
}

function kc_google_http(string $url, array $options = []): array
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('cURL extension is not enabled.');
    }

    $handle = curl_init($url);
    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTPHEADER => $options['headers'] ?? ['Accept: application/json'],
    ]);
    if (isset($options['post'])) {
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($options['post']));
    }

    $body = curl_exec($handle);
    $status = (int)curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    $error = curl_error($handle);
    curl_close($handle);

    if ($body === false || $status < 200 || $status >= 300) {
        throw new RuntimeException('Google baglantisi basarisiz: ' . ($error ?: 'HTTP ' . $status));
    }

    $payload = json_decode($body, true);
    if (!is_array($payload)) {
        throw new RuntimeException('Google yaniti okunamadi.');
    }
    return $payload;
}

