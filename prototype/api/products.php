<?php

declare(strict_types=1);

require __DIR__ . '/common.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    kc_json(['ok' => false, 'message' => 'Only GET requests are allowed.'], 405);
}

$config = kc_config();

try {
    $pdo = kc_db_required($config);
    kc_install_schema($pdo);
    $statement = $pdo->query(
        'SELECT
            p.slug AS id,
            c.code AS `group`,
            p.name,
            p.tag,
            p.price_minor,
            p.currency,
            p.visual_type AS type,
            p.image_url,
            p.summary,
            p.detail,
            p.knowledge_usage,
            p.knowledge_material,
            p.knowledge_faq,
            p.stock_mode
        FROM products p
        INNER JOIN product_categories c ON c.id = p.category_id
        WHERE p.is_active = 1 AND c.is_active = 1
        ORDER BY c.sort_order, p.sort_order, p.id'
    );

    $products = array_map(static function (array $product): array {
        return [
            'id' => $product['id'],
            'group' => $product['group'],
            'name' => $product['name'],
            'tag' => $product['tag'],
            'price' => ((int)$product['price_minor']) / 100,
            'currency' => $product['currency'],
            'type' => $product['type'],
            'imageUrl' => $product['image_url'] ?: '',
            'summary' => $product['summary'],
            'detail' => $product['detail'],
            'knowledgeUsage' => $product['knowledge_usage'],
            'knowledgeMaterial' => $product['knowledge_material'],
            'knowledgeFaq' => $product['knowledge_faq'],
            'stockMode' => $product['stock_mode'],
        ];
    }, $statement->fetchAll());

    kc_json(['ok' => true, 'products' => $products]);
} catch (Throwable $error) {
    error_log('Product API error: ' . $error->getMessage());
    kc_json(['ok' => false, 'message' => 'Urunler su anda yuklenemiyor.'], 503);
}
