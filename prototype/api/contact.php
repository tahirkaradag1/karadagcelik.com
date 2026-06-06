<?php

declare(strict_types=1);

require __DIR__ . '/common.php';

kc_require_post();

$config = kc_config();
$name = kc_text('name', 160);
$email = kc_email('email');
$message = kc_text('message', 4000);

if ($name === '' || $email === '' || $message === '') {
    kc_json(['ok' => false, 'message' => 'Ad soyad, e-posta ve mesaj zorunludur.'], 400);
}

$requestId = kc_request_id('KCM');
$body = <<<MAIL
Yeni iletisim mesaji alindi.

Mesaj No: {$requestId}

Ad Soyad: {$name}
E-posta: {$email}

Mesaj:
{$message}
MAIL;

$sent = kc_send_mail((array)$config['notification_emails'], 'Yeni Iletisim Mesaji - ' . $requestId, $body, $config, $email);

kc_json([
    'ok' => true,
    'request_id' => $requestId,
    'mail_sent' => $sent,
    'message' => 'Mesajiniz alindi.',
]);
