<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/layout.php';

kc_admin_require_login();
$pdo = kc_admin_pdo();
$id = max(0, (int)($_GET['id'] ?? $_POST['id'] ?? 0));
$categories = $pdo->query('SELECT id, name FROM product_categories WHERE is_active = 1 ORDER BY sort_order')->fetchAll();
$error = '';

$product = [
    'category_id' => $categories[0]['id'] ?? 0,
    'slug' => '',
    'name' => '',
    'tag' => 'Urun',
    'summary' => '',
    'detail' => '',
    'price_minor' => 0,
    'currency' => 'TRY',
    'visual_type' => 'plate',
    'image_url' => '',
    'knowledge_usage' => '',
    'knowledge_material' => '',
    'knowledge_faq' => '',
    'stock_mode' => 'made_to_order',
    'is_active' => 1,
    'sort_order' => 100,
];

if ($id > 0) {
    $statement = $pdo->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $id]);
    $stored = $statement->fetch();
    if (!$stored) {
        http_response_code(404);
        exit('Urun bulunamadi.');
    }
    $product = $stored;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    kc_admin_verify_csrf();
    $priceInput = str_replace(',', '.', trim((string)($_POST['price'] ?? '0')));
    $imageUrl = trim((string)($_POST['image_url'] ?? ''));
    $product = [
        'category_id' => max(1, (int)($_POST['category_id'] ?? 0)),
        'slug' => strtolower(trim((string)($_POST['slug'] ?? ''))),
        'name' => trim((string)($_POST['name'] ?? '')),
        'tag' => trim((string)($_POST['tag'] ?? '')),
        'summary' => trim((string)($_POST['summary'] ?? '')),
        'detail' => trim((string)($_POST['detail'] ?? '')),
        'price_minor' => max(0, (int)round((float)$priceInput * 100)),
        'currency' => 'TRY',
        'visual_type' => in_array($_POST['visual_type'] ?? '', ['plate', 'ring', 'bracket', 'rail'], true) ? $_POST['visual_type'] : 'plate',
        'image_url' => $imageUrl,
        'knowledge_usage' => trim((string)($_POST['knowledge_usage'] ?? '')),
        'knowledge_material' => trim((string)($_POST['knowledge_material'] ?? '')),
        'knowledge_faq' => trim((string)($_POST['knowledge_faq'] ?? '')),
        'stock_mode' => ($_POST['stock_mode'] ?? '') === 'stocked' ? 'stocked' : 'made_to_order',
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
        'sort_order' => (int)($_POST['sort_order'] ?? 100),
    ];

    $imageScheme = $imageUrl === '' ? '' : strtolower((string)parse_url($imageUrl, PHP_URL_SCHEME));
    if ($product['name'] === '' || !preg_match('/^[a-z0-9-]{3,190}$/', $product['slug'])) {
        $error = 'Urun adi zorunludur. URL kodu yalnizca kucuk harf, rakam ve tire icermeli.';
    } elseif ($imageUrl !== '' && (!filter_var($imageUrl, FILTER_VALIDATE_URL) || !in_array($imageScheme, ['http', 'https'], true))) {
        $error = 'Gorsel adresi gecerli bir http veya https URL olmali.';
    } else {
        try {
            if ($id > 0) {
                $statement = $pdo->prepare(
                    'UPDATE products SET category_id=:category_id, slug=:slug, name=:name, tag=:tag,
                    summary=:summary, detail=:detail, price_minor=:price_minor, currency=:currency,
                    visual_type=:visual_type, image_url=:image_url, knowledge_usage=:knowledge_usage,
                    knowledge_material=:knowledge_material, knowledge_faq=:knowledge_faq,
                    stock_mode=:stock_mode, is_active=:is_active, sort_order=:sort_order WHERE id=:id'
                );
                $statement->execute($product + ['id' => $id]);
            } else {
                $statement = $pdo->prepare(
                    'INSERT INTO products
                    (category_id, slug, name, tag, summary, detail, price_minor, currency, visual_type,
                    image_url, knowledge_usage, knowledge_material, knowledge_faq, stock_mode, is_active, sort_order)
                    VALUES (:category_id, :slug, :name, :tag, :summary, :detail, :price_minor, :currency,
                    :visual_type, :image_url, :knowledge_usage, :knowledge_material, :knowledge_faq,
                    :stock_mode, :is_active, :sort_order)'
                );
                $statement->execute($product);
            }
            header('Location: products.php');
            exit;
        } catch (Throwable $exception) {
            $error = str_contains($exception->getMessage(), 'Duplicate') ? 'Bu URL kodu baska bir urunde kullaniliyor.' : 'Urun kaydedilemedi.';
        }
    }
}

kc_admin_page_start($id > 0 ? 'Urunu Duzenle' : 'Yeni Urun', 'products');
?>
<section class="panel form-panel">
    <div class="panel-heading">
        <div><p class="eyebrow">Magaza katalogu</p><h2><?= $id > 0 ? kc_admin_escape($product['name']) : 'Yeni urun bilgileri' ?></h2></div>
        <a href="products.php">Listeye don</a>
    </div>
    <?php if ($error !== ''): ?><div class="alert alert-error form-alert"><?= kc_admin_escape($error) ?></div><?php endif; ?>
    <form method="post" class="editor-form">
        <input type="hidden" name="csrf" value="<?= kc_admin_escape(kc_admin_csrf()) ?>">
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="editor-grid">
            <label><span>Urun adi</span><input name="name" value="<?= kc_admin_escape($product['name']) ?>" required></label>
            <label><span>URL kodu</span><input name="slug" value="<?= kc_admin_escape($product['slug']) ?>" placeholder="ornek-urun-adi" required></label>
            <label><span>Kategori</span><select name="category_id"><?php foreach ($categories as $category): ?><option value="<?= (int)$category['id'] ?>" <?= (int)$product['category_id'] === (int)$category['id'] ? 'selected' : '' ?>><?= kc_admin_escape($category['name']) ?></option><?php endforeach; ?></select></label>
            <label><span>Etiket</span><input name="tag" value="<?= kc_admin_escape($product['tag']) ?>"></label>
            <label><span>Fiyat (TL)</span><input name="price" inputmode="decimal" value="<?= number_format((int)$product['price_minor'] / 100, 2, '.', '') ?>" required></label>
            <label><span>Uretim modeli</span><select name="stock_mode"><option value="made_to_order" <?= $product['stock_mode'] === 'made_to_order' ? 'selected' : '' ?>>Siparisle uretim</option><option value="stocked" <?= $product['stock_mode'] === 'stocked' ? 'selected' : '' ?>>Stoklu</option></select></label>
            <label><span>Ornek gorsel tipi</span><select name="visual_type"><?php foreach (['plate' => 'Plaka', 'ring' => 'Halka/Flans', 'bracket' => 'Braket', 'rail' => 'Profil/Kanal'] as $value => $label): ?><option value="<?= $value ?>" <?= $product['visual_type'] === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></label>
            <label><span>Siralama</span><input type="number" name="sort_order" value="<?= (int)$product['sort_order'] ?>"></label>
        </div>
        <label><span>Gorsel URL (istege bagli)</span><input type="url" name="image_url" value="<?= kc_admin_escape($product['image_url']) ?>" placeholder="https://..."></label>
        <label><span>Kisa aciklama</span><textarea name="summary" rows="3" required><?= kc_admin_escape($product['summary']) ?></textarea></label>
        <label><span>Urun detay aciklamasi</span><textarea name="detail" rows="5" required><?= kc_admin_escape($product['detail']) ?></textarea></label>
        <div class="editor-grid">
            <label><span>Kullanim alanlari</span><textarea name="knowledge_usage" rows="6"><?= kc_admin_escape($product['knowledge_usage']) ?></textarea></label>
            <label><span>Malzeme ve olcu</span><textarea name="knowledge_material" rows="6"><?= kc_admin_escape($product['knowledge_material']) ?></textarea></label>
        </div>
        <label><span>Sik sorular ve cevaplar</span><textarea name="knowledge_faq" rows="7"><?= kc_admin_escape($product['knowledge_faq']) ?></textarea></label>
        <label class="check-field"><input type="checkbox" name="is_active" value="1" <?= $product['is_active'] ? 'checked' : '' ?>><span>Urun magazada yayinda olsun</span></label>
        <button class="editor-submit" type="submit">Urunu kaydet</button>
    </form>
</section>
<?php kc_admin_page_end(); ?>
