<?php

declare(strict_types=1);

require dirname(__DIR__) . '/account/bootstrap.php';

kc_require_post();
$customer = kc_customer_require();
kc_customer_verify_csrf();

$label = kc_text('label', 80);
$recipientName = kc_text('recipient_name', 190);
$phone = kc_text('phone', 80);
$city = kc_text('city', 120);
$district = kc_text('district', 120);
$addressLine = kc_text('address_line', 1200);
$postalCode = kc_text('postal_code', 30);

if ($recipientName === '' || $phone === '' || $city === '' || $district === '' || $addressLine === '') {
    kc_json(['ok' => false, 'message' => 'Adres alanlarini eksiksiz doldurun.'], 400);
}

$pdo = kc_customer_pdo();
$pdo->beginTransaction();
try {
    $countStatement = $pdo->prepare('SELECT COUNT(*) FROM customer_addresses WHERE customer_id=:id');
    $countStatement->execute(['id' => $customer['id']]);
    $isDefault = (int)$countStatement->fetchColumn() === 0 ? 1 : 0;

    $statement = $pdo->prepare(
        'INSERT INTO customer_addresses
        (customer_id, label, recipient_name, phone, city, district, address_line, postal_code, is_default)
        VALUES (:customer_id, :label, :recipient_name, :phone, :city, :district, :address_line, :postal_code, :is_default)'
    );
    $statement->execute([
        'customer_id' => $customer['id'],
        'label' => $label ?: 'Teslimat',
        'recipient_name' => $recipientName,
        'phone' => $phone,
        'city' => $city,
        'district' => $district,
        'address_line' => $addressLine,
        'postal_code' => $postalCode,
        'is_default' => $isDefault,
    ]);
    $pdo->commit();
    kc_json(['ok' => true, 'message' => 'Adres kaydedildi.']);
} catch (Throwable $error) {
    $pdo->rollBack();
    kc_json(['ok' => false, 'message' => 'Adres kaydedilemedi.'], 500);
}
