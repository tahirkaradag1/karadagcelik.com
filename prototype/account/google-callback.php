<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$state = (string)($_GET['state'] ?? '');
$code = (string)($_GET['code'] ?? '');
$expectedState = (string)($_SESSION['kc_google_state'] ?? '');
unset($_SESSION['kc_google_state']);

if (!kc_google_configured() || $state === '' || $code === '' || $expectedState === '' || !hash_equals($expectedState, $state)) {
    header('Location: ../#profile');
    exit;
}

try {
    $google = (array)kc_customer_config()['google'];
    $token = kc_google_http('https://oauth2.googleapis.com/token', [
        'post' => [
            'code' => $code,
            'client_id' => $google['client_id'],
            'client_secret' => $google['client_secret'],
            'redirect_uri' => $google['redirect_uri'],
            'grant_type' => 'authorization_code',
        ],
    ]);

    $accessToken = (string)($token['access_token'] ?? '');
    if ($accessToken === '') {
        throw new RuntimeException('Google access token bulunamadi.');
    }

    $profile = kc_google_http('https://openidconnect.googleapis.com/v1/userinfo', [
        'headers' => [
            'Accept: application/json',
            'Authorization: Bearer ' . $accessToken,
        ],
    ]);

    $sub = trim((string)($profile['sub'] ?? ''));
    $email = filter_var((string)($profile['email'] ?? ''), FILTER_VALIDATE_EMAIL) ?: '';
    $name = trim((string)($profile['name'] ?? 'Google Kullanıcısı'));
    $avatar = trim((string)($profile['picture'] ?? ''));
    $verified = filter_var($profile['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);
    if ($sub === '' || $email === '' || !$verified) {
        throw new RuntimeException('Dogrulanmis Google hesabi bilgisi alinamadi.');
    }

    $pdo = kc_customer_pdo();
    $statement = $pdo->prepare('SELECT id FROM customers WHERE google_sub = :sub OR email = :email LIMIT 1');
    $statement->execute(['sub' => $sub, 'email' => $email]);
    $customerId = (int)($statement->fetchColumn() ?: 0);

    if ($customerId > 0) {
        $statement = $pdo->prepare(
            'UPDATE customers SET google_sub=:sub, email=:email, name=:name, avatar_url=:avatar,
            is_active=1, last_login_at=NOW() WHERE id=:id'
        );
        $statement->execute(['sub' => $sub, 'email' => $email, 'name' => $name, 'avatar' => $avatar, 'id' => $customerId]);
    } else {
        $statement = $pdo->prepare(
            'INSERT INTO customers (google_sub, email, name, avatar_url, last_login_at)
            VALUES (:sub, :email, :name, :avatar, NOW())'
        );
        $statement->execute(['sub' => $sub, 'email' => $email, 'name' => $name, 'avatar' => $avatar]);
        $customerId = (int)$pdo->lastInsertId();
    }

    $pdo->prepare('UPDATE orders SET customer_id=:customer_id WHERE customer_id IS NULL AND email=:email')
        ->execute(['customer_id' => $customerId, 'email' => $email]);
    $pdo->prepare('UPDATE quote_requests SET customer_id=:customer_id WHERE customer_id IS NULL AND email=:email')
        ->execute(['customer_id' => $customerId, 'email' => $email]);

    session_regenerate_id(true);
    $_SESSION['kc_customer'] = ['id' => $customerId, 'email' => $email, 'name' => $name, 'avatar' => $avatar];
} catch (Throwable $error) {
    error_log('Google login error: ' . $error->getMessage());
}

header('Location: ../#profile');
exit;

