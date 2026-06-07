<?php

declare(strict_types=1);

function kc_database_configured(array $config): bool
{
    $database = (array)($config['database'] ?? []);

    return trim((string)($database['host'] ?? '')) !== ''
        && trim((string)($database['name'] ?? '')) !== ''
        && trim((string)($database['user'] ?? '')) !== '';
}

function kc_database_connect(array $database): PDO
{
    if (!extension_loaded('pdo_mysql')) {
        throw new RuntimeException('PDO MySQL extension is not enabled.');
    }

    $host = trim((string)($database['host'] ?? 'localhost'));
    $port = max(1, (int)($database['port'] ?? 3306));
    $name = trim((string)($database['name'] ?? ''));
    $charset = trim((string)($database['charset'] ?? 'utf8mb4'));
    $user = trim((string)($database['user'] ?? ''));
    $password = (string)($database['password'] ?? '');
    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";

    return new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function kc_db(array $config): ?PDO
{
    static $connections = [];

    if (!kc_database_configured($config)) {
        return null;
    }

    $database = (array)$config['database'];
    $key = hash('sha256', json_encode($database));

    if (!isset($connections[$key])) {
        $connections[$key] = kc_database_connect($database);
    }

    return $connections[$key];
}

function kc_db_required(array $config): PDO
{
    $pdo = kc_db($config);
    if (!$pdo) {
        throw new RuntimeException('Database is not configured.');
    }

    return $pdo;
}

function kc_db_safe(array $config): ?PDO
{
    try {
        return kc_db($config);
    } catch (Throwable $error) {
        error_log('Karadag Celik database connection error: ' . $error->getMessage());
        return null;
    }
}

function kc_install_schema(PDO $pdo): void
{
    $queries = [
        <<<'SQL'
CREATE TABLE IF NOT EXISTS admin_users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(40) NOT NULL DEFAULT 'admin',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        <<<'SQL'
CREATE TABLE IF NOT EXISTS quote_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_code VARCHAR(40) NOT NULL UNIQUE,
    status VARCHAR(40) NOT NULL DEFAULT 'new',
    name VARCHAR(160) NOT NULL,
    company VARCHAR(160) NOT NULL DEFAULT '',
    phone VARCHAR(80) NOT NULL,
    email VARCHAR(190) NOT NULL,
    city VARCHAR(120) NOT NULL DEFAULT '',
    material VARCHAR(120) NOT NULL DEFAULT '',
    thickness VARCHAR(80) NOT NULL DEFAULT '',
    quantity VARCHAR(80) NOT NULL DEFAULT '',
    message TEXT NOT NULL,
    mail_sent TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_quote_status_created (status, created_at),
    INDEX idx_quote_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        <<<'SQL'
CREATE TABLE IF NOT EXISTS quote_files (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quote_request_id BIGINT UNSIGNED NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    stored_path TEXT NOT NULL,
    size_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_quote_files_request
        FOREIGN KEY (quote_request_id) REFERENCES quote_requests(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        <<<'SQL'
CREATE TABLE IF NOT EXISTS contact_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    message_code VARCHAR(40) NOT NULL UNIQUE,
    status VARCHAR(40) NOT NULL DEFAULT 'new',
    name VARCHAR(160) NOT NULL,
    email VARCHAR(190) NOT NULL,
    message TEXT NOT NULL,
    mail_sent TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_contact_status_created (status, created_at),
    INDEX idx_contact_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        <<<'SQL'
CREATE TABLE IF NOT EXISTS orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_code VARCHAR(40) NOT NULL UNIQUE,
    status VARCHAR(40) NOT NULL DEFAULT 'new',
    payment_status VARCHAR(50) NOT NULL DEFAULT 'not_connected',
    payment_provider VARCHAR(30) NOT NULL DEFAULT '',
    payment_reference VARCHAR(64) NOT NULL DEFAULT '',
    payment_amount_minor BIGINT UNSIGNED NOT NULL DEFAULT 0,
    provider_total_minor BIGINT UNSIGNED NULL,
    payment_check_hash CHAR(64) NOT NULL DEFAULT '',
    payment_failure_code VARCHAR(40) NOT NULL DEFAULT '',
    payment_failure_message TEXT NULL,
    paid_at DATETIME NULL,
    name VARCHAR(160) NOT NULL,
    phone VARCHAR(80) NOT NULL,
    email VARCHAR(190) NOT NULL,
    address TEXT NOT NULL,
    total_text VARCHAR(80) NOT NULL DEFAULT '',
    mail_sent TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_order_status_created (status, created_at),
    INDEX idx_order_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        <<<'SQL'
CREATE TABLE IF NOT EXISTS order_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    product_reference VARCHAR(120) NOT NULL DEFAULT '',
    product_name VARCHAR(255) NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    price_text VARCHAR(80) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id) REFERENCES orders(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        <<<'SQL'
CREATE TABLE IF NOT EXISTS product_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(80) NOT NULL UNIQUE,
    name VARCHAR(160) NOT NULL,
    description TEXT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        <<<'SQL'
CREATE TABLE IF NOT EXISTS products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id BIGINT UNSIGNED NOT NULL,
    slug VARCHAR(190) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    tag VARCHAR(120) NOT NULL DEFAULT '',
    summary TEXT NOT NULL,
    detail TEXT NOT NULL,
    price_minor BIGINT UNSIGNED NOT NULL DEFAULT 0,
    currency CHAR(3) NOT NULL DEFAULT 'TRY',
    visual_type VARCHAR(40) NOT NULL DEFAULT 'plate',
    image_url TEXT NULL,
    knowledge_usage TEXT NOT NULL,
    knowledge_material TEXT NOT NULL,
    knowledge_faq TEXT NOT NULL,
    stock_mode VARCHAR(40) NOT NULL DEFAULT 'made_to_order',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_product_active_sort (is_active, sort_order),
    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id) REFERENCES product_categories(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        <<<'SQL'
CREATE TABLE IF NOT EXISTS customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    google_sub VARCHAR(255) NOT NULL UNIQUE,
    email VARCHAR(190) NOT NULL UNIQUE,
    name VARCHAR(190) NOT NULL,
    avatar_url TEXT NULL,
    phone VARCHAR(80) NOT NULL DEFAULT '',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customer_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        <<<'SQL'
CREATE TABLE IF NOT EXISTS customer_addresses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id BIGINT UNSIGNED NOT NULL,
    label VARCHAR(80) NOT NULL DEFAULT 'Teslimat',
    recipient_name VARCHAR(190) NOT NULL,
    phone VARCHAR(80) NOT NULL,
    city VARCHAR(120) NOT NULL,
    district VARCHAR(120) NOT NULL,
    address_line TEXT NOT NULL,
    postal_code VARCHAR(30) NOT NULL DEFAULT '',
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_address_customer (customer_id),
    CONSTRAINT fk_addresses_customer
        FOREIGN KEY (customer_id) REFERENCES customers(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
    ];

    foreach ($queries as $query) {
        $pdo->exec($query);
    }

    kc_add_column_if_missing(
        $pdo,
        'quote_requests',
        'customer_id',
        'ALTER TABLE quote_requests ADD COLUMN customer_id BIGINT UNSIGNED NULL AFTER id, ADD INDEX idx_quote_customer (customer_id)'
    );
    kc_add_column_if_missing(
        $pdo,
        'orders',
        'customer_id',
        'ALTER TABLE orders ADD COLUMN customer_id BIGINT UNSIGNED NULL AFTER id, ADD INDEX idx_order_customer (customer_id)'
    );
    kc_add_column_if_missing(
        $pdo,
        'orders',
        'payment_provider',
        "ALTER TABLE orders ADD COLUMN payment_provider VARCHAR(30) NOT NULL DEFAULT '' AFTER payment_status"
    );
    kc_add_column_if_missing(
        $pdo,
        'orders',
        'payment_reference',
        "ALTER TABLE orders ADD COLUMN payment_reference VARCHAR(64) NOT NULL DEFAULT '' AFTER payment_provider, ADD INDEX idx_order_payment_reference (payment_reference)"
    );
    kc_add_column_if_missing(
        $pdo,
        'orders',
        'payment_amount_minor',
        'ALTER TABLE orders ADD COLUMN payment_amount_minor BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER payment_provider'
    );
    kc_add_column_if_missing(
        $pdo,
        'orders',
        'provider_total_minor',
        'ALTER TABLE orders ADD COLUMN provider_total_minor BIGINT UNSIGNED NULL AFTER payment_amount_minor'
    );
    kc_add_column_if_missing(
        $pdo,
        'orders',
        'payment_check_hash',
        "ALTER TABLE orders ADD COLUMN payment_check_hash CHAR(64) NOT NULL DEFAULT '' AFTER provider_total_minor"
    );
    kc_add_column_if_missing(
        $pdo,
        'orders',
        'payment_failure_code',
        "ALTER TABLE orders ADD COLUMN payment_failure_code VARCHAR(40) NOT NULL DEFAULT '' AFTER payment_check_hash"
    );
    kc_add_column_if_missing(
        $pdo,
        'orders',
        'payment_failure_message',
        'ALTER TABLE orders ADD COLUMN payment_failure_message TEXT NULL AFTER payment_failure_code'
    );
    kc_add_column_if_missing(
        $pdo,
        'orders',
        'paid_at',
        'ALTER TABLE orders ADD COLUMN paid_at DATETIME NULL AFTER payment_failure_message'
    );

    kc_seed_catalog($pdo);
}

function kc_add_column_if_missing(PDO $pdo, string $table, string $column, string $alterSql): void
{
    $statement = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name'
    );
    $statement->execute(['table_name' => $table, 'column_name' => $column]);
    if ((int)$statement->fetchColumn() === 0) {
        $pdo->exec($alterSql);
    }
}

function kc_seed_catalog(PDO $pdo): void
{
    $categoryStatement = $pdo->prepare(
        'INSERT INTO product_categories (code, name, description, sort_order)
        VALUES (:code, :name, :description, :sort_order)
        ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description)'
    );
    $categories = [
        ['industrial', 'Endustriyel Parcalar', 'Makine, enerji ve uretim hatlari icin teknik metal parcalar.', 10],
        ['retail', 'Urunlerimiz', 'Hazir satisa uygun metal urunler.', 20],
    ];
    foreach ($categories as [$code, $name, $description, $sortOrder]) {
        $categoryStatement->execute([
            'code' => $code,
            'name' => $name,
            'description' => $description,
            'sort_order' => $sortOrder,
        ]);
    }

    if ((int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn() > 0) {
        return;
    }

    $categoryIds = [];
    foreach ($pdo->query('SELECT id, code FROM product_categories')->fetchAll() as $category) {
        $categoryIds[$category['code']] = (int)$category['id'];
    }

    $products = [
        ['industrial', 'makine-baglanti-plakasi', 'Makine Baglanti Plakasi', 'Endustriyel', 148000, 'plate', 'Lazer kesim ve hassas delik toleransi ile montaja hazir baglanti plakasi.', 'Makine govdeleri, konveyor sistemleri ve uretim hatlarinda kullanilabilecek cok amacli metal baglanti parcasi.'],
        ['industrial', 'disli-flans', 'Disli Flans Parca', 'Endustriyel', 235000, 'ring', 'Kalip, makine ve aktarim sistemleri icin yuvarlak kesim flans parcasi.', 'Celik, paslanmaz veya aluminyum malzeme secenekleriyle uretime uygun teknik flans altyapisi.'],
        ['industrial', 'gunes-paneli-ayagi', 'Gunes Paneli Ayagi', 'Enerji', 89000, 'bracket', 'Gunes enerji sistemleri icin bukumlu ve delikli montaj ayagi.', 'Saha montajinda hiz, dayaniklilik ve olculu kurulum gerektiren panel tasiyici parcasi.'],
        ['industrial', 'metal-kablo-kanali', 'Metal Kablo Kanali', 'Tesisat', 76000, 'rail', 'Endustriyel tesisat ve pano hatlari icin bukumlu kablo tasima kanali.', 'Elektrik ve otomasyon projelerinde kablo gecislerini duzenli tutmak icin tasarlandi.'],
        ['retail', 'dekoratif-metal-raf', 'Dekoratif Metal Raf', 'Urun', 125000, 'bracket', 'Minimal ic mekanlar icin lazer kesim metal raf ve tasiyici set.', 'Magaza, ofis ve ev kullanimi icin sade gorunumlu, dayanikli metal raf urunu.'],
        ['retail', 'bahce-paneli', 'Lazer Kesim Bahce Paneli', 'Urun', 315000, 'plate', 'Dis mekanlarda dekoratif bolme veya cephe etkisi icin metal panel.', 'Desenli panel altyapisi ileride gercek modeller, olculer ve kaplama secenekleriyle doldurulabilir.'],
        ['retail', 'masa-ayagi-seti', 'Metal Masa Ayagi Seti', 'Urun', 198000, 'rail', 'Ahsap veya kompozit tablalar icin sabit fiyatli metal masa ayagi.', 'Lazer kesim ve bukumle uretilen masa ayagi setleri icin satin alma sayfasi ornegi.'],
        ['retail', 'duvar-logo-panelleri', 'Duvar Logo Paneli', 'Urun', 225000, 'plate', 'Isletmeler icin metal logo, tabela veya dekoratif duvar paneli.', 'Marka uygulamalarinda kullanilabilecek lazer kesim dekoratif metal panel altyapisi.'],
    ];
    $insert = $pdo->prepare(
        'INSERT INTO products
        (category_id, slug, name, tag, summary, detail, price_minor, visual_type, knowledge_usage, knowledge_material, knowledge_faq, sort_order)
        VALUES (:category_id, :slug, :name, :tag, :summary, :detail, :price_minor, :visual_type, :usage, :material, :faq, :sort_order)'
    );
    foreach ($products as $index => [$group, $slug, $name, $tag, $price, $type, $summary, $detail]) {
        $insert->execute([
            'category_id' => $categoryIds[$group],
            'slug' => $slug,
            'name' => $name,
            'tag' => $tag,
            'summary' => $summary,
            'detail' => $detail,
            'price_minor' => $price,
            'visual_type' => $type,
            'usage' => 'Bu urunun kullanildigi sektorler ve montaj senaryolari burada anlatilabilir.',
            'material' => 'Malzeme, kalinlik, yuzey islemi ve tolerans bilgileri burada tutulabilir.',
            'faq' => 'Urune ozel sik sorulan sorular ve net cevaplar burada yayinlanabilir.',
            'sort_order' => ($index + 1) * 10,
        ]);
    }
}

function kc_db_store_quote(array $config, array $metadata): bool
{
    $pdo = kc_db_safe($config);
    if (!$pdo) {
        return false;
    }

    try {
        $pdo->beginTransaction();
        $customer = (array)$metadata['customer'];
        $project = (array)$metadata['project'];
        $statement = $pdo->prepare(
            'INSERT INTO quote_requests
            (customer_id, request_code, name, company, phone, email, city, material, thickness, quantity, message)
            VALUES (:customer_id, :code, :name, :company, :phone, :email, :city, :material, :thickness, :quantity, :message)'
        );
        $statement->execute([
            'customer_id' => $metadata['customer_id'] ?? null,
            'code' => $metadata['request_id'],
            'name' => $customer['name'],
            'company' => $customer['company'],
            'phone' => $customer['phone'],
            'email' => $customer['email'],
            'city' => $customer['city'],
            'material' => $project['material'],
            'thickness' => $project['thickness'],
            'quantity' => $project['quantity'],
            'message' => $project['message'],
        ]);

        $quoteId = (int)$pdo->lastInsertId();
        $fileStatement = $pdo->prepare(
            'INSERT INTO quote_files
            (quote_request_id, original_name, stored_name, stored_path, size_bytes)
            VALUES (:quote_id, :original_name, :stored_name, :stored_path, :size_bytes)'
        );

        foreach ((array)$metadata['files'] as $file) {
            $fileStatement->execute([
                'quote_id' => $quoteId,
                'original_name' => $file['original_name'],
                'stored_name' => $file['stored_name'],
                'stored_path' => $file['path'],
                'size_bytes' => $file['bytes'],
            ]);
        }

        $pdo->commit();
        return true;
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Quote database save error: ' . $error->getMessage());
        return false;
    }
}

function kc_db_store_contact(array $config, array $metadata): bool
{
    $pdo = kc_db_safe($config);
    if (!$pdo) {
        return false;
    }

    try {
        $customer = (array)$metadata['customer'];
        $statement = $pdo->prepare(
            'INSERT INTO contact_messages (message_code, name, email, message)
            VALUES (:code, :name, :email, :message)'
        );
        return $statement->execute([
            'code' => $metadata['request_id'],
            'name' => $customer['name'],
            'email' => $customer['email'],
            'message' => $metadata['message'],
        ]);
    } catch (Throwable $error) {
        error_log('Contact database save error: ' . $error->getMessage());
        return false;
    }
}

function kc_db_store_order(array $config, array $metadata): bool
{
    $pdo = kc_db_safe($config);
    if (!$pdo) {
        return false;
    }

    try {
        $pdo->beginTransaction();
        $customer = (array)$metadata['customer'];
        $statement = $pdo->prepare(
            'INSERT INTO orders
            (customer_id, order_code, status, payment_status, payment_provider, payment_reference, payment_amount_minor,
             payment_check_hash, name, phone, email, address, total_text)
            VALUES (:customer_id, :code, :status, :payment_status, :payment_provider, :payment_reference, :payment_amount_minor,
             :payment_check_hash, :name, :phone, :email, :address, :total)'
        );
        $statement->execute([
            'customer_id' => $metadata['customer_id'] ?? null,
            'code' => $metadata['request_id'],
            'status' => $metadata['status'] ?? 'new',
            'payment_status' => $metadata['payment_status'],
            'payment_provider' => $metadata['payment_provider'] ?? '',
            'payment_reference' => $metadata['payment_reference'] ?? '',
            'payment_amount_minor' => $metadata['total_minor'] ?? 0,
            'payment_check_hash' => $metadata['payment_check_hash'] ?? '',
            'name' => $customer['name'],
            'phone' => $customer['phone'],
            'email' => $customer['email'],
            'address' => $customer['address'],
            'total' => $metadata['total'],
        ]);

        $orderId = (int)$pdo->lastInsertId();
        $itemStatement = $pdo->prepare(
            'INSERT INTO order_items
            (order_id, product_reference, product_name, quantity, price_text)
            VALUES (:order_id, :reference, :name, :quantity, :price)'
        );

        foreach ((array)$metadata['items'] as $item) {
            $itemStatement->execute([
                'order_id' => $orderId,
                'reference' => (string)($item['id'] ?? ''),
                'name' => (string)($item['name'] ?? 'Urun'),
                'quantity' => max(1, (int)($item['qty'] ?? 1)),
                'price' => (string)($item['price'] ?? ''),
            ]);
        }

        $pdo->commit();
        return true;
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Order database save error: ' . $error->getMessage());
        return false;
    }
}

function kc_db_update_order_payment_status(
    array $config,
    string $orderCode,
    string $paymentStatus,
    string $status = ''
): void {
    $pdo = kc_db_safe($config);
    if (!$pdo) {
        return;
    }

    try {
        $sql = 'UPDATE orders SET payment_status = :payment_status';
        $params = ['payment_status' => $paymentStatus, 'code' => $orderCode];
        if ($status !== '') {
            $sql .= ', status = :status';
            $params['status'] = $status;
        }
        $sql .= ' WHERE order_code = :code';
        $statement = $pdo->prepare($sql);
        $statement->execute($params);
    } catch (Throwable $error) {
        error_log('Order payment status update error: ' . $error->getMessage());
    }
}

function kc_db_resolve_order_items(array $config, array $submittedItems): array
{
    if ($submittedItems === [] || count($submittedItems) > 50) {
        throw new InvalidArgumentException('Sepet gecersiz.');
    }

    $quantities = [];
    foreach ($submittedItems as $item) {
        $reference = trim((string)($item['id'] ?? ''));
        $quantity = (int)($item['qty'] ?? 0);
        if (!preg_match('/^[a-z0-9-]{3,190}$/', $reference) || $quantity < 1 || $quantity > 1000) {
            throw new InvalidArgumentException('Sepette gecersiz bir urun veya adet var.');
        }
        $quantities[$reference] = min(1000, ($quantities[$reference] ?? 0) + $quantity);
    }

    $pdo = kc_db_required($config);
    kc_install_schema($pdo);
    $references = array_keys($quantities);
    $placeholders = implode(',', array_fill(0, count($references), '?'));
    $statement = $pdo->prepare(
        "SELECT p.slug, p.name, p.price_minor, p.currency
        FROM products p
        INNER JOIN product_categories c ON c.id = p.category_id
        WHERE p.slug IN ({$placeholders}) AND p.is_active = 1 AND c.is_active = 1"
    );
    $statement->execute($references);

    $catalog = [];
    foreach ($statement->fetchAll() as $product) {
        $catalog[$product['slug']] = $product;
    }
    if (count($catalog) !== count($references)) {
        throw new InvalidArgumentException('Sepette artik satista olmayan bir urun var. Sepeti yenileyip tekrar deneyin.');
    }

    $resolvedItems = [];
    $totalMinor = 0;
    foreach ($references as $reference) {
        $product = $catalog[$reference];
        if ($product['currency'] !== 'TRY') {
            throw new RuntimeException('Desteklenmeyen urun para birimi.');
        }
        $quantity = $quantities[$reference];
        $priceMinor = (int)$product['price_minor'];
        $totalMinor += $priceMinor * $quantity;
        $resolvedItems[] = [
            'id' => $reference,
            'name' => $product['name'],
            'qty' => $quantity,
            'price' => kc_format_try($priceMinor),
            'price_minor' => $priceMinor,
        ];
    }

    return [
        'items' => $resolvedItems,
        'total' => kc_format_try($totalMinor),
        'total_minor' => $totalMinor,
        'currency' => 'TRY',
    ];
}

function kc_format_try(int $amountMinor): string
{
    return number_format($amountMinor / 100, 2, ',', '.') . ' TL';
}

function kc_db_set_mail_status(array $config, string $table, string $codeColumn, string $code, bool $sent): void
{
    $allowed = [
        'quote_requests' => 'request_code',
        'contact_messages' => 'message_code',
        'orders' => 'order_code',
    ];

    if (!isset($allowed[$table]) || $allowed[$table] !== $codeColumn) {
        return;
    }

    $pdo = kc_db_safe($config);
    if (!$pdo) {
        return;
    }

    try {
        $statement = $pdo->prepare("UPDATE {$table} SET mail_sent = :sent WHERE {$codeColumn} = :code");
        $statement->execute(['sent' => $sent ? 1 : 0, 'code' => $code]);
    } catch (Throwable $error) {
        error_log('Mail status database update error: ' . $error->getMessage());
    }
}
