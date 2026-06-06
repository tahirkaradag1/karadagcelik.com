<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/api/common.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('kc_admin_session');
    session_set_cookie_params([
        'lifetime' => 0,
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

function kc_admin_escape(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function kc_admin_config(): array
{
    static $config;
    if (!$config) {
        $config = kc_config();
    }

    return $config;
}

function kc_admin_pdo(): PDO
{
    return kc_db_required(kc_admin_config());
}

function kc_admin_user(): ?array
{
    return isset($_SESSION['kc_admin']) && is_array($_SESSION['kc_admin'])
        ? $_SESSION['kc_admin']
        : null;
}

function kc_admin_require_login(): array
{
    $user = kc_admin_user();
    if (!$user) {
        header('Location: login.php');
        exit;
    }

    return $user;
}

function kc_admin_csrf(): string
{
    if (empty($_SESSION['kc_admin_csrf'])) {
        $_SESSION['kc_admin_csrf'] = bin2hex(random_bytes(24));
    }

    return (string)$_SESSION['kc_admin_csrf'];
}

function kc_admin_verify_csrf(): void
{
    $provided = (string)($_POST['csrf'] ?? '');
    if ($provided === '' || !hash_equals(kc_admin_csrf(), $provided)) {
        http_response_code(419);
        exit('Gecersiz veya suresi dolmus istek.');
    }
}

function kc_admin_status_label(string $status): string
{
    return [
        'new' => 'Yeni',
        'in_review' => 'Inceleniyor',
        'quoted' => 'Teklif verildi',
        'confirmed' => 'Onaylandi',
        'preparing' => 'Hazirlaniyor',
        'shipped' => 'Kargolandi',
        'completed' => 'Tamamlandi',
        'cancelled' => 'Iptal edildi',
        'read' => 'Okundu',
        'replied' => 'Yanıtlandi',
        'closed' => 'Kapatildi',
    ][$status] ?? ucfirst($status);
}

function kc_admin_datetime(string $value): string
{
    $timestamp = strtotime($value);
    return $timestamp ? date('d.m.Y H:i', $timestamp) : $value;
}

