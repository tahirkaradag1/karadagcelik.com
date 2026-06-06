<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/layout.php';

kc_admin_require_login();
$pdo = kc_admin_pdo();
$rows = $pdo->query(
    'SELECT id, order_code, status, payment_status, name, phone, email, total_text, created_at
    FROM orders ORDER BY created_at DESC LIMIT 250'
)->fetchAll();

kc_admin_page_start('Magaza Siparisleri', 'orders');
?>
<section class="panel">
    <div class="panel-heading">
        <div><p class="eyebrow">Magaza operasyonu</p><h2>Siparis talepleri</h2></div>
        <span class="count-label"><?= count($rows) ?> kayit</span>
    </div>
    <?php if (!$rows): ?>
        <?php kc_admin_empty('Henuz magaza siparisi bulunmuyor.'); ?>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr><th>Siparis</th><th>Musteri</th><th>Toplam</th><th>Durum</th><th>Tarih</th></tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><a class="record-link" href="record.php?type=order&id=<?= (int)$row['id'] ?>"><?= kc_admin_escape($row['order_code']) ?></a></td>
                        <td><strong><?= kc_admin_escape($row['name']) ?></strong><small><?= kc_admin_escape($row['email']) ?></small></td>
                        <td><?= kc_admin_escape($row['total_text']) ?></td>
                        <td><?= kc_admin_status($row['status']) ?></td>
                        <td><?= kc_admin_datetime($row['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php kc_admin_page_end(); ?>

