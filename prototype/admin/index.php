<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/layout.php';

kc_admin_require_login();
$pdo = kc_admin_pdo();

$counts = [
    'quotes' => (int)$pdo->query("SELECT COUNT(*) FROM quote_requests WHERE status = 'new'")->fetchColumn(),
    'orders' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'new'")->fetchColumn(),
    'messages' => (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'")->fetchColumn(),
    'all' => (int)$pdo->query(
        'SELECT
            (SELECT COUNT(*) FROM quote_requests) +
            (SELECT COUNT(*) FROM orders) +
            (SELECT COUNT(*) FROM contact_messages)'
    )->fetchColumn(),
];

$recentQuotes = $pdo->query(
    'SELECT id, request_code AS code, name, company, status, created_at
    FROM quote_requests ORDER BY created_at DESC LIMIT 5'
)->fetchAll();
$recentOrders = $pdo->query(
    'SELECT id, order_code AS code, name, total_text, status, created_at
    FROM orders ORDER BY created_at DESC LIMIT 5'
)->fetchAll();

kc_admin_page_start('Genel Bakis', 'dashboard');
?>
<section class="stat-grid">
    <a class="stat-card" href="quotes.php">
        <span>Yeni teklifler</span>
        <strong><?= $counts['quotes'] ?></strong>
        <small>Incelenme bekliyor</small>
    </a>
    <a class="stat-card" href="orders.php">
        <span>Yeni siparisler</span>
        <strong><?= $counts['orders'] ?></strong>
        <small>Islem bekliyor</small>
    </a>
    <a class="stat-card" href="messages.php">
        <span>Yeni mesajlar</span>
        <strong><?= $counts['messages'] ?></strong>
        <small>Yanıt bekliyor</small>
    </a>
    <div class="stat-card muted">
        <span>Toplam kayit</span>
        <strong><?= $counts['all'] ?></strong>
        <small>Tum zamanlar</small>
    </div>
</section>

<section class="split-grid">
    <div class="panel">
        <div class="panel-heading">
            <div><p class="eyebrow">Son hareketler</p><h2>Teklif talepleri</h2></div>
            <a href="quotes.php">Tumunu gor</a>
        </div>
        <?php if (!$recentQuotes): ?>
            <?php kc_admin_empty('Henuz teklif talebi yok.'); ?>
        <?php else: ?>
            <div class="compact-list">
                <?php foreach ($recentQuotes as $quote): ?>
                    <a href="record.php?type=quote&id=<?= (int)$quote['id'] ?>">
                        <span>
                            <strong><?= kc_admin_escape($quote['name']) ?></strong>
                            <small><?= kc_admin_escape($quote['company'] ?: $quote['code']) ?></small>
                        </span>
                        <span class="list-meta">
                            <?= kc_admin_status($quote['status']) ?>
                            <small><?= kc_admin_datetime($quote['created_at']) ?></small>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="panel">
        <div class="panel-heading">
            <div><p class="eyebrow">Magaza</p><h2>Son siparisler</h2></div>
            <a href="orders.php">Tumunu gor</a>
        </div>
        <?php if (!$recentOrders): ?>
            <?php kc_admin_empty('Henuz siparis talebi yok.'); ?>
        <?php else: ?>
            <div class="compact-list">
                <?php foreach ($recentOrders as $order): ?>
                    <a href="record.php?type=order&id=<?= (int)$order['id'] ?>">
                        <span>
                            <strong><?= kc_admin_escape($order['name']) ?></strong>
                            <small><?= kc_admin_escape($order['code']) ?></small>
                        </span>
                        <span class="list-meta">
                            <?= kc_admin_status($order['status']) ?>
                            <small><?= kc_admin_escape($order['total_text']) ?></small>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php kc_admin_page_end(); ?>

