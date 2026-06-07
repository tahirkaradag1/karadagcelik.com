<?php

declare(strict_types=1);

require __DIR__ . '/common.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    kc_json(['ok' => false, 'message' => 'Only GET requests are allowed.'], 405);
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$config = kc_config();
$orderCode = trim((string)($_GET['order'] ?? ''));
$checkToken = trim((string)($_GET['token'] ?? ''));
if (!preg_match('/^KCO-[A-Z0-9-]{10,50}$/', $orderCode) || !preg_match('/^[a-f0-9]{48}$/', $checkToken)) {
    kc_json(['ok' => false, 'message' => 'Gecersiz odeme sorgusu.'], 400);
}

$pdo = kc_db_required($config);
kc_install_schema($pdo);
$statement = $pdo->prepare(
    'SELECT payment_status, status, total_text
    FROM orders WHERE order_code = :code AND payment_check_hash = :token_hash LIMIT 1'
);
$statement->execute([
    'code' => $orderCode,
    'token_hash' => hash('sha256', $checkToken),
]);
$order = $statement->fetch();
if (!$order) {
    kc_json(['ok' => false, 'message' => 'Odeme kaydi bulunamadi.'], 404);
}

kc_json([
    'ok' => true,
    'order_code' => $orderCode,
    'payment_status' => $order['payment_status'],
    'order_status' => $order['status'],
    'total' => $order['total_text'],
]);
