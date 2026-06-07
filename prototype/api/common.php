<?php

declare(strict_types=1);

require_once __DIR__ . '/database.php';

function kc_config(): array
{
    $default = [
        'company_name' => 'Karadag Celik',
        'from_email' => 'info@karadagcelik.com',
        'from_name' => 'Karadag Celik',
        'reply_to' => 'info@karadagcelik.com',
        'notification_emails' => ['info@karadagcelik.com'],
        'upload_dir' => dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'karadag_private_uploads',
        'max_file_bytes' => 25 * 1024 * 1024,
        'allowed_extensions' => ['dxf', 'dwg', 'pdf', 'step', 'stp', 'iges', 'igs', 'zip', 'rar', 'jpg', 'jpeg', 'png'],
        'setup_key' => '',
        'database' => [
            'host' => '',
            'port' => 3306,
            'name' => '',
            'user' => '',
            'password' => '',
            'charset' => 'utf8mb4',
        ],
        'google' => [
            'client_id' => '',
            'client_secret' => '',
            'redirect_uri' => '',
        ],
        'paytr' => [
            'merchant_id' => '',
            'merchant_key' => '',
            'merchant_salt' => '',
            'test_mode' => true,
            'debug_on' => true,
            'no_installment' => false,
            'max_installment' => 0,
            'timeout_limit' => 30,
            'base_url' => '',
        ],
    ];

    $file = __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
    if (is_file($file)) {
        $custom = require $file;
    }

    $config = $default;
    if (isset($custom) && is_array($custom)) {
        $config = array_replace_recursive($config, $custom);
    }

    $databaseFile = __DIR__ . DIRECTORY_SEPARATOR . 'database.local.php';
    if (is_file($databaseFile)) {
        $database = require $databaseFile;
        if (is_array($database)) {
            $config['database'] = array_replace($config['database'], $database);
        }
    }

    return $config;
}

function kc_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function kc_require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        kc_json(['ok' => false, 'message' => 'Only POST requests are allowed.'], 405);
    }
}

function kc_substr(string $value, int $max): string
{
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $max, 'UTF-8');
    }

    return substr($value, 0, $max);
}

function kc_mime_header(string $value): string
{
    if (function_exists('mb_encode_mimeheader')) {
        return mb_encode_mimeheader($value, 'UTF-8');
    }

    return $value;
}

function kc_text(string $key, int $max = 1200): string
{
    $value = trim((string)($_POST[$key] ?? ''));
    $value = str_replace(["\r\n", "\r"], "\n", $value);
    $value = preg_replace('/[^\P{C}\n\t]/u', '', $value) ?? '';
    return kc_substr($value, $max);
}

function kc_email(string $key): string
{
    $email = trim((string)($_POST[$key] ?? ''));
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
}

function kc_request_id(string $prefix): string
{
    return $prefix . '-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

function kc_current_customer_id(): ?int
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_name('kc_customer_session');
        session_start();
    }

    $id = (int)($_SESSION['kc_customer']['id'] ?? 0);
    return $id > 0 ? $id : null;
}

function kc_ensure_dir(string $path): string
{
    if (!is_dir($path)) {
        @mkdir($path, 0755, true);
    }

    if (is_dir($path) && is_writable($path)) {
        return $path;
    }

    $fallback = __DIR__ . DIRECTORY_SEPARATOR . 'private_uploads';
    if (!is_dir($fallback)) {
        @mkdir($fallback, 0755, true);
    }

    if (!is_dir($fallback) || !is_writable($fallback)) {
        kc_json(['ok' => false, 'message' => 'Upload directory is not writable.'], 500);
    }

    return $fallback;
}

function kc_save_uploads(string $field, string $requestId, array $config): array
{
    if (empty($_FILES[$field]) || !is_array($_FILES[$field]['name'])) {
        return [];
    }

    $baseDir = kc_ensure_dir((string)$config['upload_dir']);
    $requestDir = $baseDir . DIRECTORY_SEPARATOR . $requestId;
    if (!is_dir($requestDir)) {
        @mkdir($requestDir, 0755, true);
    }

    $saved = [];
    $allowed = array_map('strtolower', (array)$config['allowed_extensions']);
    $maxBytes = (int)$config['max_file_bytes'];
    $names = $_FILES[$field]['name'];

    foreach ($names as $index => $originalName) {
        if ($_FILES[$field]['error'][$index] === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if ($_FILES[$field]['error'][$index] !== UPLOAD_ERR_OK) {
            kc_json(['ok' => false, 'message' => 'A file upload failed. Please try again.'], 400);
        }

        $size = (int)$_FILES[$field]['size'][$index];
        if ($size > $maxBytes) {
            kc_json(['ok' => false, 'message' => 'One of the uploaded files is too large.'], 400);
        }

        $extension = strtolower(pathinfo((string)$originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, $allowed, true)) {
            kc_json(['ok' => false, 'message' => 'Unsupported file type: ' . $extension], 400);
        }

        $safeName = preg_replace('/[^a-zA-Z0-9._-]+/', '-', pathinfo((string)$originalName, PATHINFO_FILENAME));
        $safeName = trim((string)$safeName, '-_.');
        if ($safeName === '') {
            $safeName = 'file';
        }

        $finalName = $safeName . '-' . ($index + 1) . '.' . $extension;
        $target = $requestDir . DIRECTORY_SEPARATOR . $finalName;

        if (!move_uploaded_file($_FILES[$field]['tmp_name'][$index], $target)) {
            kc_json(['ok' => false, 'message' => 'File could not be saved.'], 500);
        }

        $saved[] = [
            'original_name' => (string)$originalName,
            'stored_name' => $finalName,
            'bytes' => $size,
            'path' => $target,
        ];
    }

    return $saved;
}

function kc_mail_headers(array $config, string $replyTo = ''): string
{
    $fromName = kc_mime_header((string)$config['from_name']);
    $fromEmail = (string)$config['from_email'];
    $replyTo = $replyTo !== '' ? $replyTo : (string)$config['reply_to'];

    return implode("\r\n", [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . $fromName . ' <' . $fromEmail . '>',
        'Reply-To: ' . $replyTo,
        'X-Mailer: PHP/' . phpversion(),
    ]);
}

function kc_send_mail(array $to, string $subject, string $body, array $config, string $replyTo = ''): bool
{
    $subject = kc_mime_header($subject);
    $headers = kc_mail_headers($config, $replyTo);
    $success = true;

    foreach ($to as $recipient) {
        $recipient = trim((string)$recipient);
        if ($recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            continue;
        }

        $sent = @mail($recipient, $subject, $body, $headers);
        $success = $success && $sent;
    }

    return $success;
}

function kc_store_metadata(string $requestId, array $data, array $config): void
{
    $baseDir = kc_ensure_dir((string)$config['upload_dir']);
    $requestDir = $baseDir . DIRECTORY_SEPARATOR . $requestId;
    if (!is_dir($requestDir)) {
        @mkdir($requestDir, 0755, true);
    }

    file_put_contents(
        $requestDir . DIRECTORY_SEPARATOR . 'metadata.json',
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );
}
