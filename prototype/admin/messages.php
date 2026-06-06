<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/layout.php';

kc_admin_require_login();
$pdo = kc_admin_pdo();
$rows = $pdo->query(
    'SELECT id, message_code, status, name, email, message, created_at
    FROM contact_messages ORDER BY created_at DESC LIMIT 250'
)->fetchAll();

kc_admin_page_start('Iletisim Mesajlari', 'messages');
?>
<section class="panel">
    <div class="panel-heading">
        <div><p class="eyebrow">Musteri iletisimi</p><h2>Gelen mesajlar</h2></div>
        <span class="count-label"><?= count($rows) ?> kayit</span>
    </div>
    <?php if (!$rows): ?>
        <?php kc_admin_empty('Henuz iletisim mesaji bulunmuyor.'); ?>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr><th>Mesaj</th><th>Gonderen</th><th>Onizleme</th><th>Durum</th><th>Tarih</th></tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><a class="record-link" href="record.php?type=message&id=<?= (int)$row['id'] ?>"><?= kc_admin_escape($row['message_code']) ?></a></td>
                        <td><strong><?= kc_admin_escape($row['name']) ?></strong><small><?= kc_admin_escape($row['email']) ?></small></td>
                        <td class="message-preview"><?= kc_admin_escape(kc_substr($row['message'], 100)) ?></td>
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

