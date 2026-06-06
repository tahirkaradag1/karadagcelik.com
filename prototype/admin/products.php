<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/layout.php';

kc_admin_require_login();
$pdo = kc_admin_pdo();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    kc_admin_verify_csrf();
    $id = max(1, (int)($_POST['id'] ?? 0));
    $statement = $pdo->prepare('UPDATE products SET is_active = IF(is_active = 1, 0, 1) WHERE id = :id');
    $statement->execute(['id' => $id]);
    header('Location: products.php');
    exit;
}

$rows = $pdo->query(
    'SELECT p.id, p.slug, p.name, p.tag, p.price_minor, p.currency, p.stock_mode,
        p.is_active, p.sort_order, c.name AS category_name
    FROM products p
    INNER JOIN product_categories c ON c.id = p.category_id
    ORDER BY c.sort_order, p.sort_order, p.id'
)->fetchAll();

kc_admin_page_start('Urun Yonetimi', 'products');
?>
<section class="panel">
    <div class="panel-heading">
        <div><p class="eyebrow">Magaza katalogu</p><h2>Urunler</h2></div>
        <a class="panel-action" href="product-edit.php">Yeni urun</a>
    </div>
    <?php if (!$rows): ?>
        <?php kc_admin_empty('Henuz urun bulunmuyor.'); ?>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr><th>Urun</th><th>Kategori</th><th>Fiyat</th><th>Uretim</th><th>Durum</th><th>Islem</th></tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><a class="record-link" href="product-edit.php?id=<?= (int)$row['id'] ?>"><?= kc_admin_escape($row['name']) ?></a><small><?= kc_admin_escape($row['slug']) ?></small></td>
                        <td><?= kc_admin_escape($row['category_name']) ?></td>
                        <td><?= number_format((int)$row['price_minor'] / 100, 2, ',', '.') ?> <?= kc_admin_escape($row['currency']) ?></td>
                        <td><?= $row['stock_mode'] === 'stocked' ? 'Stoklu' : 'Siparisle uretim' ?></td>
                        <td><span class="status <?= $row['is_active'] ? 'status-new' : 'status-cancelled' ?>"><?= $row['is_active'] ? 'Yayinda' : 'Pasif' ?></span></td>
                        <td>
                            <form method="post" class="inline-form">
                                <input type="hidden" name="csrf" value="<?= kc_admin_escape(kc_admin_csrf()) ?>">
                                <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                <button class="table-button" type="submit"><?= $row['is_active'] ? 'Pasife al' : 'Yayinla' ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php kc_admin_page_end(); ?>

