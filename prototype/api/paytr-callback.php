<?php

declare(strict_types=1);

require __DIR__ . '/common.php';
require __DIR__ . '/payment.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    exit('Only POST requests are allowed.');
}

$config = kc_config();
if (!kc_paytr_configured($config)) {
    http_response_code(503);
    exit('PayTR is not configured.');
}

$paymentReference = trim((string)($_POST['merchant_oid'] ?? ''));
$status = trim((string)($_POST['status'] ?? ''));
$totalAmount = max(0, (int)($_POST['total_amount'] ?? 0));
$paymentAmount = max(0, (int)($_POST['payment_amount'] ?? 0));
$incomingHash = trim((string)($_POST['hash'] ?? ''));
$paytr = (array)$config['paytr'];
$expectedHash = base64_encode(hash_hmac(
    'sha256',
    $paymentReference . (string)$paytr['merchant_salt'] . $status . $totalAmount,
    (string)$paytr['merchant_key'],
    true
));

if ($paymentReference === '' || $incomingHash === '' || !hash_equals($expectedHash, $incomingHash)) {
    http_response_code(400);
    exit('PAYTR notification failed: bad hash');
}

$pdo = kc_db_required($config);
kc_install_schema($pdo);
$pdo->beginTransaction();

try {
    $statement = $pdo->prepare('SELECT * FROM orders WHERE payment_reference = :reference LIMIT 1 FOR UPDATE');
    $statement->execute(['reference' => $paymentReference]);
    $order = $statement->fetch();
    if (!$order) {
        throw new RuntimeException('Order not found.');
    }

    if (in_array($order['payment_status'], ['paid', 'failed'], true)) {
        $pdo->commit();
        exit('OK');
    }

    if ($status === 'success') {
        $expectedAmount = (int)$order['payment_amount_minor'];
        if ($expectedAmount < 1 || $paymentAmount !== $expectedAmount) {
            throw new RuntimeException('Payment amount mismatch.');
        }

        $statement = $pdo->prepare(
            "UPDATE orders
            SET status = 'confirmed', payment_status = 'paid', provider_total_minor = :provider_total,
                payment_failure_code = '', payment_failure_message = NULL, paid_at = NOW()
            WHERE id = :id"
        );
        $statement->execute([
            'provider_total' => $totalAmount,
            'id' => $order['id'],
        ]);
    } else {
        $statement = $pdo->prepare(
            "UPDATE orders
            SET status = 'cancelled', payment_status = 'failed',
                payment_failure_code = :failure_code, payment_failure_message = :failure_message
            WHERE id = :id"
        );
        $statement->execute([
            'failure_code' => kc_substr(trim((string)($_POST['failed_reason_code'] ?? '')), 40),
            'failure_message' => kc_substr(trim((string)($_POST['failed_reason_msg'] ?? '')), 1000),
            'id' => $order['id'],
        ]);
    }

    $pdo->commit();
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('PayTR callback error: ' . $error->getMessage());
    http_response_code(400);
    exit('PAYTR notification failed.');
}

if ($status === 'success') {
    $statement = $pdo->prepare('SELECT * FROM orders WHERE payment_reference = :reference LIMIT 1');
    $statement->execute(['reference' => $paymentReference]);
    $paidOrder = $statement->fetch();
    $statement = $pdo->prepare('SELECT * FROM order_items WHERE order_id = :id ORDER BY id');
    $statement->execute(['id' => $paidOrder['id']]);
    $items = $statement->fetchAll();
    $mailSent = kc_send_paid_order_emails($config, $paidOrder, $items);
    kc_db_set_mail_status($config, 'orders', 'order_code', (string)$paidOrder['order_code'], $mailSent);
}

exit('OK');
