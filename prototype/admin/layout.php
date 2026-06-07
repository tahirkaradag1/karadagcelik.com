<?php

declare(strict_types=1);

function kc_admin_page_start(string $title, string $active = ''): void
{
    $user = kc_admin_user();
    $liveRefresh = in_array($active, ['dashboard', 'quotes', 'orders', 'customers', 'messages'], true);
    $nav = [
        'dashboard' => ['index.php', 'Genel Bakis'],
        'quotes' => ['quotes.php', 'Teklifler'],
        'orders' => ['orders.php', 'Siparisler'],
        'products' => ['products.php', 'Urunler'],
        'customers' => ['customers.php', 'Musteriler'],
        'messages' => ['messages.php', 'Mesajlar'],
    ];
    ?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?= kc_admin_escape($title) ?> | Karadag Celik Yonetim</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body<?= $liveRefresh ? ' data-live-refresh="true"' : '' ?>>
<div class="admin-shell">
    <aside class="sidebar">
        <a class="brand" href="index.php">
            <span class="brand-mark">KÇ</span>
            <span><strong>Karadag Celik</strong><small>Yonetim Paneli</small></span>
        </a>
        <nav>
            <?php foreach ($nav as $key => [$href, $label]): ?>
                <a class="<?= $active === $key ? 'active' : '' ?>" href="<?= $href ?>">
                    <?= kc_admin_escape($label) ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-footer">
            <span><?= kc_admin_escape($user['name'] ?? '') ?></span>
            <a href="logout.php">Cikis yap</a>
        </div>
    </aside>
    <main class="content">
        <header class="page-header">
            <div>
                <p class="eyebrow">Karadag Celik operasyon merkezi</p>
                <h1><?= kc_admin_escape($title) ?></h1>
            </div>
            <a class="site-link" href="../" target="_blank" rel="noopener">Siteyi ac</a>
        </header>
<?php
}

function kc_admin_page_end(): void
{
    ?>
    </main>
</div>
<script>
(() => {
    if (document.body.dataset.liveRefresh !== 'true') return;

    let hiddenAt = 0;
    let loadedAt = Date.now();

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            hiddenAt = Date.now();
            return;
        }

        if (hiddenAt && Date.now() - hiddenAt > 1000) {
            window.location.reload();
        }
    });

    window.setInterval(() => {
        if (!document.hidden && Date.now() - loadedAt >= 30000) {
            loadedAt = Date.now();
            window.location.reload();
        }
    }, 30000);
})();
</script>
</body>
</html>
<?php
}

function kc_admin_empty(string $message): void
{
    echo '<div class="empty">' . kc_admin_escape($message) . '</div>';
}

function kc_admin_status(string $status): string
{
    return '<span class="status status-' . kc_admin_escape($status) . '">'
        . kc_admin_escape(kc_admin_status_label($status))
        . '</span>';
}
