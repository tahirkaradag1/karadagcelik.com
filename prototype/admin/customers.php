<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/layout.php';

kc_admin_require_login();
$pdo = kc_admin_pdo();
$rows = $pdo->query(
    'SELECT c.id, c.name, c.email, c.phone, c.last_login_at, c.created_at,
        (SELECT COUNT(*) FROM orders o WHERE o.customer_id = c.id) AS order_count,
        (SELECT COUNT(*) FROM quote_requests q WHERE q.customer_id = c.id) AS quote_count
    FROM customers c ORDER BY c.created_at DESC LIMIT 250'
)->fetchAll();

kc_admin_page_start('Musteriler', 'customers');
?>
<section class="panel">
    <div class="panel-heading">
        <div><p class="eyebrow">Google hesaplari</p><h2>Kayitli musteriler</h2></div>
        <span class="count-label"><?= count($rows) ?> musteri</span>
    </div>
    <?php if (!$rows): ?>
        <?php kc_admin_empty('Google ile giris yapan musteri henuz yok.'); ?>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Musteri</th><th>Telefon</th><th>Teklif</th><th>Siparis</th><th>Son giris</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><strong><?= kc_admin_escape($row['name']) ?></strong><small><?= kc_admin_escape($row['email']) ?></small></td>
                        <td><?= kc_admin_escape($row['phone'] ?: '-') ?></td>
                        <td><?= (int)$row['quote_count'] ?></td>
                        <td><?= (int)$row['order_count'] ?></td>
                        <td><?= $row['last_login_at'] ? kc_admin_datetime($row['last_login_at']) : 'Henuz yok' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php kc_admin_page_end(); ?>

