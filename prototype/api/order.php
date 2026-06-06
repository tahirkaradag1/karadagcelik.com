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
$total = kc_text('total', 80);

if ($name === '' || $phone === '' || $email === '' || $address === '') {
    kc_json(['ok' => false, 'message' => 'Ad soyad, telefon, e-posta ve adres zorunludur.'], 400);
}

$items = json_decode($itemsJson, true);
if (!is_array($items) || count($items) === 0) {
    kc_json(['ok' => false, 'message' => 'Sepet bos gorunuyor.'], 400);
}

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
    'created_at' => date(DATE_ATOM),
    'customer' => [
        'name' => $name,
        'phone' => $phone,
        'email' => $email,
        'address' => $address,
    ],
    'items' => $items,
    'total' => $total,
    'payment_status' => 'payment_not_connected_yet',
];

kc_store_metadata($requestId, $metadata, $config);

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

kc_json([
    'ok' => true,
    'request_id' => $requestId,
    'mail_sent' => $internalSent,
    'message' => 'Siparis talebiniz alindi.',
]);
