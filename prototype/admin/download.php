<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

kc_admin_require_login();
$pdo = kc_admin_pdo();
$id = max(1, (int)($_GET['id'] ?? 0));
$statement = $pdo->prepare('SELECT original_name, stored_path FROM quote_files WHERE id = :id LIMIT 1');
$statement->execute(['id' => $id]);
$file = $statement->fetch();

if (!$file || !is_file($file['stored_path'])) {
    http_response_code(404);
    exit('Dosya bulunamadi.');
}

$downloadName = str_replace(["\r", "\n", '"'], '', basename((string)$file['original_name']));
header('Content-Type: application/octet-stream');
header('Content-Length: ' . filesize($file['stored_path']));
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('X-Content-Type-Options: nosniff');
readfile($file['stored_path']);
exit;

