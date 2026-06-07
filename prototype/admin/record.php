<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/layout.php';

kc_admin_require_login();
$pdo = kc_admin_pdo();
$type = (string)($_GET['type'] ?? '');
$id = max(1, (int)($_GET['id'] ?? 0));

$definitions = [
    'quote' => [
        'table' => 'quote_requests',
        'title' => 'Teklif Detayi',
        'active' => 'quotes',
        'statuses' => ['new', 'in_review', 'quoted', 'completed', 'cancelled'],
    ],
    'order' => [
        'table' => 'orders',
        'title' => 'Siparis Detayi',
        'active' => 'orders',
        'statuses' => ['awaiting_payment', 'new', 'confirmed', 'preparing', 'shipped', 'completed', 'cancelled'],
    ],
    'message' => [
        'table' => 'contact_messages',
        'title' => 'Mesaj Detayi',
        'active' => 'messages',
        'statuses' => ['new', 'read', 'replied', 'closed'],
    ],
];

if (!isset($definitions[$type])) {
    http_response_code(404);
    exit('Kayit turu bulunamadi.');
}

$definition = $definitions[$type];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    kc_admin_verify_csrf();
    $status = (string)($_POST['status'] ?? '');
    if (in_array($status, $definition['statuses'], true)) {
        $statement = $pdo->prepare("UPDATE {$definition['table']} SET status = :status WHERE id = :id");
        $statement->execute(['status' => $status, 'id' => $id]);
    }
    header('Location: record.php?type=' . rawurlencode($type) . '&id=' . $id);
    exit;
}

$statement = $pdo->prepare("SELECT * FROM {$definition['table']} WHERE id = :id LIMIT 1");
$statement->execute(['id' => $id]);
$record = $statement->fetch();
if (!$record) {
    http_response_code(404);
    exit('Kayit bulunamadi.');
}

$files = [];
$items = [];
if ($type === 'quote') {
    $statement = $pdo->prepare('SELECT * FROM quote_files WHERE quote_request_id = :id ORDER BY id');
    $statement->execute(['id' => $id]);
    $files = $statement->fetchAll();
}
if ($type === 'order') {
    $statement = $pdo->prepare('SELECT * FROM order_items WHERE order_id = :id ORDER BY id');
    $statement->execute(['id' => $id]);
    $items = $statement->fetchAll();
}

kc_admin_page_start($definition['title'], $definition['active']);
?>
<section class="detail-grid">
    <div class="panel detail-main">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">Kayit numarasi</p>
                <h2><?= kc_admin_escape($record['request_code'] ?? $record['order_code'] ?? $record['message_code']) ?></h2>
            </div>
            <?= kc_admin_status($record['status']) ?>
        </div>

        <?php if ($type === 'quote'): ?>
            <div class="info-grid">
                <div><span>Ad soyad</span><strong><?= kc_admin_escape($record['name']) ?></strong></div>
                <div><span>Firma</span><strong><?= kc_admin_escape($record['company'] ?: '-') ?></strong></div>
                <div><span>Telefon</span><strong><a href="tel:<?= kc_admin_escape($record['phone']) ?>"><?= kc_admin_escape($record['phone']) ?></a></strong></div>
                <div><span>E-posta</span><strong><a href="mailto:<?= kc_admin_escape($record['email']) ?>"><?= kc_admin_escape($record['email']) ?></a></strong></div>
                <div><span>Sehir</span><strong><?= kc_admin_escape($record['city'] ?: '-') ?></strong></div>
                <div><span>Malzeme</span><strong><?= kc_admin_escape($record['material'] ?: '-') ?></strong></div>
                <div><span>Kalinlik</span><strong><?= kc_admin_escape($record['thickness'] ?: '-') ?></strong></div>
                <div><span>Adet</span><strong><?= kc_admin_escape($record['quantity'] ?: '-') ?></strong></div>
            </div>
            <div class="text-block"><span>Proje notu</span><p><?= nl2br(kc_admin_escape($record['message'] ?: 'Not eklenmedi.')) ?></p></div>
            <div class="subsection">
                <h3>Teknik dosyalar</h3>
                <?php if (!$files): ?>
                    <?php kc_admin_empty('Bu talepte dosya bulunmuyor.'); ?>
                <?php else: ?>
                    <div class="file-list">
                        <?php foreach ($files as $file): ?>
                            <a href="download.php?id=<?= (int)$file['id'] ?>">
                                <span><strong><?= kc_admin_escape($file['original_name']) ?></strong><small><?= number_format((int)$file['size_bytes'] / 1024, 1) ?> KB</small></span>
                                <span>Indir</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php elseif ($type === 'order'): ?>
            <div class="info-grid">
                <div><span>Ad soyad</span><strong><?= kc_admin_escape($record['name']) ?></strong></div>
                <div><span>Telefon</span><strong><a href="tel:<?= kc_admin_escape($record['phone']) ?>"><?= kc_admin_escape($record['phone']) ?></a></strong></div>
                <div><span>E-posta</span><strong><a href="mailto:<?= kc_admin_escape($record['email']) ?>"><?= kc_admin_escape($record['email']) ?></a></strong></div>
                <div><span>Toplam</span><strong><?= kc_admin_escape($record['total_text']) ?></strong></div>
                <div><span>Odeme</span><strong><?= kc_admin_escape(kc_admin_payment_status_label($record['payment_status'])) ?></strong></div>
                <div><span>Odeme saglayicisi</span><strong><?= kc_admin_escape($record['payment_provider'] ?: '-') ?></strong></div>
            </div>
            <div class="text-block"><span>Teslimat adresi</span><p><?= nl2br(kc_admin_escape($record['address'])) ?></p></div>
            <div class="subsection">
                <h3>Siparis kalemleri</h3>
                <div class="order-items">
                    <?php foreach ($items as $item): ?>
                        <div>
                            <span><strong><?= kc_admin_escape($item['product_name']) ?></strong><small><?= kc_admin_escape($item['product_reference']) ?></small></span>
                            <span><?= (int)$item['quantity'] ?> adet</span>
                            <strong><?= kc_admin_escape($item['price_text']) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="info-grid">
                <div><span>Ad soyad</span><strong><?= kc_admin_escape($record['name']) ?></strong></div>
                <div><span>E-posta</span><strong><a href="mailto:<?= kc_admin_escape($record['email']) ?>"><?= kc_admin_escape($record['email']) ?></a></strong></div>
            </div>
            <div class="text-block"><span>Mesaj</span><p><?= nl2br(kc_admin_escape($record['message'])) ?></p></div>
        <?php endif; ?>
    </div>

    <aside class="panel detail-side">
        <p class="eyebrow">Is akisi</p>
        <h2>Durumu guncelle</h2>
        <form method="post" class="status-form">
            <input type="hidden" name="csrf" value="<?= kc_admin_escape(kc_admin_csrf()) ?>">
            <label>
                <span>Kayit durumu</span>
                <select name="status">
                    <?php foreach ($definition['statuses'] as $status): ?>
                        <option value="<?= kc_admin_escape($status) ?>" <?= $record['status'] === $status ? 'selected' : '' ?>>
                            <?= kc_admin_escape(kc_admin_status_label($status)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit">Durumu kaydet</button>
        </form>
        <dl class="meta-list">
            <div><dt>Olusturulma</dt><dd><?= kc_admin_datetime($record['created_at']) ?></dd></div>
            <div><dt>Son guncelleme</dt><dd><?= kc_admin_datetime($record['updated_at']) ?></dd></div>
            <div><dt>E-posta</dt><dd><?= !empty($record['mail_sent']) ? 'Gonderildi' : 'Gonderilemedi' ?></dd></div>
        </dl>
    </aside>
</section>
<?php kc_admin_page_end(); ?>
