<?php

declare(strict_types=1);

$result = ($_GET['result'] ?? '') === 'success' ? 'success' : 'failed';
$order = preg_replace('/[^A-Z0-9-]/', '', strtoupper((string)($_GET['order'] ?? '')));
$title = $result === 'success' ? 'Odeme sonucu kontrol ediliyor' : 'Odeme tamamlanamadi';
$message = $result === 'success'
    ? 'Bankadan gelen sonuc guvenli sekilde dogrulaniyor. Bu pencereyi kapatmayin.'
    : 'Odeme tamamlanamadi. Sepetinize donup yeniden deneyebilirsiniz.';
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        *{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:#f4f6f5;color:#131716;font:16px/1.5 Arial,sans-serif}
        main{max-width:480px;padding:32px;border:1px solid #dfe5e2;border-radius:8px;background:#fff;text-align:center;box-shadow:0 20px 50px rgba(20,35,30,.1)}
        h1{margin:0 0 12px;font-size:26px}p{margin:0;color:#66706d}
    </style>
</head>
<body>
<main>
    <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
    <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
</main>
<script>
window.parent.postMessage({
    source: 'kc-paytr-return',
    result: <?= json_encode($result) ?>,
    order: <?= json_encode($order) ?>
}, window.location.origin);
</script>
</body>
</html>
