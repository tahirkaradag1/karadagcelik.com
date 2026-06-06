<?php

declare(strict_types=1);

require __DIR__ . '/common.php';

kc_require_post();

$config = kc_config();
$name = kc_text('name', 160);
$phone = kc_text('phone', 80);
$email = kc_email('email');
$address = kc_text('address', 1600);
$itemsJson = (string)($_POST['items'] ?? '[]');

if ($name === '' || $phone === '' || $email === '' || $address === '') {
    kc_json(['ok' => false, 'message' => 'Ad soyad, telefon, e-posta ve adres zorunludur.'], 400);
}

$items = json_decode($itemsJson, true);
if (!is_array($items) || count($items) === 0) {
    kc_json(['ok' => false, 'message' => 'Sepet bos gorunuyor.'], 400);
}

try {
    $resolvedOrder = kc_db_resolve_order_items($config, $items);
} catch (InvalidArgumentException $error) {
    kc_json(['ok' => false, 'message' => $error->getMessage()], 400);
} catch (Throwable $error) {
    error_log('Order price resolution error: ' . $error->getMessage());
    kc_json(['ok' => false, 'message' => 'Urun fiyatlari su anda dogrulanamiyor. Lutfen daha sonra tekrar deneyin.'], 503);
}

$items = $resolvedOrder['items'];
$total = $resolvedOrder['total'];
$requestId = kc_request_id('KCO');
$lines = [];
foreach ($items as $item) {
    $itemName = trim((string)($item['name'] ?? 'Urun'));
    $qty = max(1, (int)($item['qty'] ?? 1));
    $price = trim((string)($item['price'] ?? ''));
    $lines[] = '- ' . $itemName . ' x ' . $qty . ' / ' . $price;
}

$itemText = implode("\n", $lines);

$metadata = [
    'type' => 'store_order_request',
    'request_id' => $requestId,
    'customer_id' => kc_current_customer_id(),
    'created_at' => date(DATE_ATOM),
    'customer' => [
        'name' => $name,
        'phone' => $phone,
        'email' => $email,
        'address' => $address,
    ],
    'items' => $items,
    'total' => $total,
    'total_minor' => $resolvedOrder['total_minor'],
    'currency' => $resolvedOrder['currency'],
    'payment_status' => 'payment_not_connected_yet',
];

kc_store_metadata($requestId, $metadata, $config);
$databaseSaved = kc_db_store_order($config, $metadata);

$body = <<<MAIL
Yeni magaza siparis talebi alindi.

Siparis No: {$requestId}

Musteri:
Ad Soyad: {$name}
Telefon: {$phone}
E-posta: {$email}
Adres:
{$address}

Urunler:
{$itemText}

Toplam: {$total}

Not: Canli odeme entegrasyonu henuz baglanmadi. Bu kayit magaza siparis talebi olarak olusturuldu.
MAIL;

$internalSent = kc_send_mail((array)$config['notification_emails'], 'Yeni Magaza Siparis Talebi - ' . $requestId, $body, $config, $email);
kc_db_set_mail_status($config, 'orders', 'order_code', $requestId, $internalSent);

$customerBody = <<<MAIL
Merhaba {$name},

Karadag Celik magaza siparis talebiniz alindi.

Siparis No: {$requestId}
Toplam: {$total}

Odeme entegrasyonu tamamlandiginda bu akis dogrudan online odemeye baglanacaktir. Simdilik ekibimiz talebiniz icin size geri donus yapacaktir.

Tesekkurler,
Karadag Celik
MAIL;

kc_send_mail([$email], 'Karadag Celik siparis talebiniz alindi - ' . $requestId, $customerBody, $config);

if (!$internalSent) {
    kc_json([
        'ok' => false,
        'request_id' => $requestId,
        'mail_sent' => false,
        'message' => 'Siparis kaydedildi fakat e-posta gonderilemedi. Siparis no: ' . $requestId,
    ], 502);
}

kc_json([
    'ok' => true,
    'request_id' => $requestId,
    'mail_sent' => $internalSent,
    'database_saved' => $databaseSaved,
    'message' => 'Siparis talebiniz alindi.',
]);
