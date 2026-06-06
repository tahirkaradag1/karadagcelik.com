<?php

declare(strict_types=1);

require dirname(__DIR__) . '/account/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    kc_json(['ok' => false, 'message' => 'Only GET requests are allowed.'], 405);
}

$session = kc_customer_session();
if (!$session) {
    kc_json([
        'ok' => true,
        'logged_in' => false,
        'google_configured' => kc_google_configured(),
        'login_url' => 'account/google-start.php',
    ]);
}

$pdo = kc_customer_pdo();
$statement = $pdo->prepare('SELECT id, name, email, avatar_url, phone FROM customers WHERE id=:id AND is_active=1 LIMIT 1');
$statement->execute(['id' => $session['id']]);
$customer = $statement->fetch();
if (!$customer) {
    unset($_SESSION['kc_customer']);
    kc_json(['ok' => true, 'logged_in' => false, 'google_configured' => kc_google_configured()]);
}

$statement = $pdo->prepare(
    'SELECT id, label, recipient_name, phone, city, district, address_line, postal_code, is_default
    FROM customer_addresses WHERE customer_id=:id ORDER BY is_default DESC, id DESC'
);
$statement->execute(['id' => $customer['id']]);
$addresses = $statement->fetchAll();

$statement = $pdo->prepare(
    'SELECT order_code AS code, status, payment_status, total_text, created_at
    FROM orders WHERE customer_id=:id ORDER BY created_at DESC LIMIT 20'
);
$statement->execute(['id' => $customer['id']]);
$orders = $statement->fetchAll();

$statement = $pdo->prepare(
    'SELECT request_code AS code, status, material, thickness, created_at
    FROM quote_requests WHERE customer_id=:id ORDER BY created_at DESC LIMIT 20'
);
$statement->execute(['id' => $customer['id']]);
$quotes = $statement->fetchAll();

kc_json([
    'ok' => true,
    'logged_in' => true,
    'csrf' => kc_customer_csrf(),
    'customer' => [
        'name' => $customer['name'],
        'email' => $customer['email'],
        'avatar' => $customer['avatar_url'] ?: '',
        'phone' => $customer['phone'],
    ],
    'addresses' => $addresses,
    'orders' => $orders,
    'quotes' => $quotes,
]);

