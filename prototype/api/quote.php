<?php

declare(strict_types=1);

require __DIR__ . '/common.php';

kc_require_post();

$config = kc_config();
$name = kc_text('name', 160);
$company = kc_text('company', 160);
$phone = kc_text('phone', 80);
$email = kc_email('email');
$city = kc_text('city', 120);
$material = kc_text('material', 120);
$thickness = kc_text('thickness', 80);
$quantity = kc_text('quantity', 80);
$message = kc_text('message', 3000);

if ($name === '' || $phone === '' || $email === '') {
    kc_json(['ok' => false, 'message' => 'Ad soyad, telefon ve e-posta zorunludur.'], 400);
}

$requestId = kc_request_id('KCQ');
$files = kc_save_uploads('files', $requestId, $config);

$metadata = [
    'type' => 'quote_request',
    'request_id' => $requestId,
    'created_at' => date(DATE_ATOM),
    'customer' => [
        'name' => $name,
        'company' => $company,
        'phone' => $phone,
        'email' => $email,
        'city' => $city,
    ],
    'project' => [
        'material' => $material,
        'thickness' => $thickness,
        'quantity' => $quantity,
        'message' => $message,
    ],
    'files' => $files,
];

kc_store_metadata($requestId, $metadata, $config);

$fileLines = $files
    ? implode("\n", array_map(fn($file) => '- ' . $file['original_name'] . ' (' . $file['stored_name'] . ')', $files))
    : '- Dosya yuklenmedi';

$body = <<<MAIL
Yeni online teklif talebi alindi.

Talep No: {$requestId}

Musteri:
Ad Soyad: {$name}
Firma: {$company}
Telefon: {$phone}
E-posta: {$email}
Sehir: {$city}

Proje:
Malzeme: {$material}
Kalinlik: {$thickness}
Adet: {$quantity}
Not:
{$message}

Dosyalar:
{$fileLines}

Not: Dosyalar sunucudaki ozel yukleme klasorune kaydedildi. Admin panel baglanana kadar FTP/File Manager uzerinden incelenebilir.
MAIL;

$internalSent = kc_send_mail((array)$config['notification_emails'], 'Yeni Online Teklif Talebi - ' . $requestId, $body, $config, $email);

$customerBody = <<<MAIL
Merhaba {$name},

Karadag Celik online teklif talebiniz alindi.

Talep No: {$requestId}

Ekibimiz dosyanizi ve proje notlarinizi inceleyip size geri donus yapacaktir.

Tesekkurler,
Karadag Celik
MAIL;

kc_send_mail([$email], 'Karadag Celik teklif talebiniz alindi - ' . $requestId, $customerBody, $config);

kc_json([
    'ok' => true,
    'request_id' => $requestId,
    'mail_sent' => $internalSent,
    'message' => 'Teklif talebiniz alindi.',
]);
