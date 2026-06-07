<?php

declare(strict_types=1);

function kc_paytr_configured(array $config): bool
{
    $paytr = (array)($config['paytr'] ?? []);

    return trim((string)($paytr['merchant_id'] ?? '')) !== ''
        && trim((string)($paytr['merchant_key'] ?? '')) !== ''
        && trim((string)($paytr['merchant_salt'] ?? '')) !== '';
}

function kc_public_base_url(array $config): string
{
    $configured = rtrim(trim((string)($config['paytr']['base_url'] ?? '')), '/');
    if ($configured !== '') {
        return $configured;
    }

    $https = !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off';
    $scheme = $https ? 'https' : 'http';
    $host = preg_replace('/[^a-zA-Z0-9.:-]/', '', (string)($_SERVER['HTTP_HOST'] ?? ''));
    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/api/order.php'));
    $basePath = preg_replace('#/api/[^/]+$#', '', $script) ?? '';

    return $scheme . '://' . $host . rtrim($basePath, '/');
}

function kc_client_ip(): string
{
    $candidates = [];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $candidates = array_merge($candidates, explode(',', (string)$_SERVER['HTTP_X_FORWARDED_FOR']));
    }
    $candidates[] = (string)($_SERVER['HTTP_CLIENT_IP'] ?? '');
    $candidates[] = (string)($_SERVER['REMOTE_ADDR'] ?? '');

    foreach ($candidates as $candidate) {
        $candidate = trim($candidate);
        if (filter_var($candidate, FILTER_VALIDATE_IP)) {
            return $candidate;
        }
    }

    return '127.0.0.1';
}

function kc_paytr_basket(array $items): string
{
    $basket = [];
    foreach ($items as $item) {
        $basket[] = [
            kc_substr((string)($item['name'] ?? 'Urun'), 120),
            number_format(((int)($item['price_minor'] ?? 0)) / 100, 2, '.', ''),
            max(1, (int)($item['qty'] ?? 1)),
        ];
    }

    return base64_encode((string)json_encode($basket, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function kc_paytr_create_iframe(
    array $config,
    string $orderCode,
    string $paymentReference,
    array $customer,
    array $items,
    int $totalMinor
): array {
    if (!kc_paytr_configured($config)) {
        throw new RuntimeException('PayTR is not configured.');
    }
    if (!extension_loaded('curl')) {
        throw new RuntimeException('PHP cURL extension is not enabled.');
    }

    $paytr = (array)$config['paytr'];
    $merchantId = trim((string)$paytr['merchant_id']);
    $merchantKey = (string)$paytr['merchant_key'];
    $merchantSalt = (string)$paytr['merchant_salt'];
    $userIp = kc_client_ip();
    $email = (string)$customer['email'];
    $userBasket = kc_paytr_basket($items);
    $noInstallment = !empty($paytr['no_installment']) ? 1 : 0;
    $maxInstallment = max(0, (int)($paytr['max_installment'] ?? 0));
    $testMode = !empty($paytr['test_mode']) ? 1 : 0;
    $debugOn = !empty($paytr['debug_on']) ? 1 : 0;
    $currency = 'TL';
    $baseUrl = kc_public_base_url($config);

    $hashString = $merchantId
        . $userIp
        . $paymentReference
        . $email
        . $totalMinor
        . $userBasket
        . $noInstallment
        . $maxInstallment
        . $currency
        . $testMode;
    $token = base64_encode(hash_hmac('sha256', $hashString . $merchantSalt, $merchantKey, true));

    $postValues = [
        'merchant_id' => $merchantId,
        'user_ip' => $userIp,
        'merchant_oid' => $paymentReference,
        'email' => $email,
        'payment_amount' => $totalMinor,
        'paytr_token' => $token,
        'user_basket' => $userBasket,
        'debug_on' => $debugOn,
        'no_installment' => $noInstallment,
        'max_installment' => $maxInstallment,
        'user_name' => (string)$customer['name'],
        'user_address' => (string)$customer['address'],
        'user_phone' => (string)$customer['phone'],
        'merchant_ok_url' => $baseUrl . '/payment/return.php?result=success&order=' . rawurlencode($orderCode),
        'merchant_fail_url' => $baseUrl . '/payment/return.php?result=failed&order=' . rawurlencode($orderCode),
        'timeout_limit' => max(5, (int)($paytr['timeout_limit'] ?? 30)),
        'currency' => $currency,
        'test_mode' => $testMode,
    ];

    $curl = curl_init('https://www.paytr.com/odeme/api/get-token');
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postValues,
        CURLOPT_FRESH_CONNECT => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $rawResult = curl_exec($curl);
    $curlError = curl_error($curl);
    curl_close($curl);

    if ($rawResult === false) {
        throw new RuntimeException('PayTR connection error: ' . $curlError);
    }

    $result = json_decode((string)$rawResult, true);
    if (!is_array($result) || ($result['status'] ?? '') !== 'success' || empty($result['token'])) {
        throw new RuntimeException((string)($result['reason'] ?? 'PayTR token could not be created.'));
    }

    return [
        'iframe_url' => 'https://www.paytr.com/odeme/guvenli/' . rawurlencode((string)$result['token']),
        'test_mode' => $testMode === 1,
    ];
}

function kc_send_paid_order_emails(array $config, array $order, array $items): bool
{
    $lines = [];
    foreach ($items as $item) {
        $lines[] = '- ' . $item['product_name'] . ' x ' . (int)$item['quantity'] . ' / ' . $item['price_text'];
    }
    $itemText = implode("\n", $lines);
    $orderCode = (string)$order['order_code'];
    $name = (string)$order['name'];
    $email = (string)$order['email'];
    $phone = (string)$order['phone'];
    $address = (string)$order['address'];
    $total = (string)$order['total_text'];

    $body = <<<MAIL
Yeni odemesi tamamlanmis magaza siparisi alindi.

Siparis No: {$orderCode}

Musteri:
Ad Soyad: {$name}
Telefon: {$phone}
E-posta: {$email}
Adres:
{$address}

Urunler:
{$itemText}

Toplam: {$total}
Odeme: PayTR ile basarili
MAIL;

    $internalSent = kc_send_mail(
        (array)$config['notification_emails'],
        'Odemesi Tamamlanan Magaza Siparisi - ' . $orderCode,
        $body,
        $config,
        $email
    );

    $customerBody = <<<MAIL
Merhaba {$name},

Karadag Celik magaza siparisinizin odemesi basariyla alindi.

Siparis No: {$orderCode}
Toplam: {$total}

Siparisiniz hazirlik surecine alinacaktir.

Tesekkurler,
Karadag Celik
MAIL;

    kc_send_mail(
        [$email],
        'Karadag Celik odemeniz alindi - ' . $orderCode,
        $customerBody,
        $config
    );

    return $internalSent;
}
