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
    ];

    foreach ($queries as $query) {
        $pdo->exec($query);
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
            (request_code, name, company, phone, email, city, material, thickness, quantity, message)
            VALUES (:code, :name, :company, :phone, :email, :city, :material, :thickness, :quantity, :message)'
        );
        $statement->execute([
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
            (order_code, payment_status, name, phone, email, address, total_text)
            VALUES (:code, :payment_status, :name, :phone, :email, :address, :total)'
        );
        $statement->execute([
            'code' => $metadata['request_id'],
            'payment_status' => $metadata['payment_status'],
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
