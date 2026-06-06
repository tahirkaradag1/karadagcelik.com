<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$config = kc_admin_config();
$expectedKey = (string)($config['setup_key'] ?? '');
$providedKey = trim((string)($_POST['setup_key'] ?? $_GET['key'] ?? ''));
$authorized = $expectedKey !== '' && $providedKey !== '' && hash_equals($expectedKey, $providedKey);
$error = '';
$success = false;
$alreadyInstalled = false;

if (!$authorized) {
    http_response_code(403);
}

if ($authorized && kc_database_configured($config)) {
    try {
        $alreadyInstalled = (int)kc_admin_pdo()->query('SELECT COUNT(*) FROM admin_users')->fetchColumn() > 0;
    } catch (Throwable $exception) {
        $error = 'Mevcut veritabanina ulasilamiyor: ' . $exception->getMessage();
    }
}

if ($authorized && !$alreadyInstalled && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    kc_admin_verify_csrf();

    $database = [
        'host' => trim((string)($_POST['db_host'] ?? 'localhost')),
        'port' => max(1, (int)($_POST['db_port'] ?? 3306)),
        'name' => trim((string)($_POST['db_name'] ?? '')),
        'user' => trim((string)($_POST['db_user'] ?? '')),
        'password' => (string)($_POST['db_password'] ?? ''),
        'charset' => 'utf8mb4',
    ];
    $adminName = trim((string)($_POST['admin_name'] ?? ''));
    $adminEmail = filter_var(trim((string)($_POST['admin_email'] ?? '')), FILTER_VALIDATE_EMAIL) ?: '';
    $adminPassword = (string)($_POST['admin_password'] ?? '');

    if ($database['name'] === '' || $database['user'] === '' || $adminName === '' || $adminEmail === '') {
        $error = 'Veritabani ve yonetici alanlarinin tumunu doldurun.';
    } elseif (strlen($adminPassword) < 12) {
        $error = 'Yonetici parolasi en az 12 karakter olmali.';
    } else {
        try {
            $pdo = kc_database_connect($database);
            kc_install_schema($pdo);

            $adminCount = (int)$pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
            if ($adminCount > 0) {
                throw new RuntimeException('Yonetim paneli daha once kurulmus.');
            }

            $databaseFile = dirname(__DIR__) . '/api/database.local.php';
            $databasePhp = "<?php\n\nreturn " . var_export($database, true) . ";\n";
            if (file_put_contents($databaseFile, $databasePhp, LOCK_EX) === false) {
                throw new RuntimeException('Veritabani ayar dosyasi yazilamadi.');
            }
            @chmod($databaseFile, 0600);

            $statement = $pdo->prepare(
                'INSERT INTO admin_users (name, email, password_hash)
                VALUES (:name, :email, :password_hash)'
            );
            $statement->execute([
                'name' => $adminName,
                'email' => $adminEmail,
                'password_hash' => password_hash($adminPassword, PASSWORD_DEFAULT),
            ]);
            $success = true;
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Yonetim Paneli Kurulumu | Karadag Celik</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="auth-page">
<main class="auth-panel install-panel">
    <a class="brand auth-brand" href="../">
        <span class="brand-mark">KÇ</span>
        <span><strong>Karadag Celik</strong><small>Guvenli kurulum</small></span>
    </a>
    <?php if (!$authorized): ?>
        <div class="auth-copy">
            <p class="eyebrow">Erisim reddedildi</p>
            <h1>Kurulum anahtari gerekli</h1>
            <p>Bu ekran yalnızca gizli kurulum baglantisi ile acilabilir.</p>
        </div>
    <?php elseif ($success || $alreadyInstalled): ?>
        <div class="auth-copy">
            <p class="eyebrow">Kurulum tamamlandi</p>
            <h1>Yonetim paneli hazir</h1>
            <p>Veritabani tablolari ve ilk yonetici hesabi olusturuldu.</p>
        </div>
        <a class="primary-link" href="login.php">Yonetici girisine git</a>
    <?php else: ?>
        <div class="auth-copy">
            <p class="eyebrow">Tek seferlik kurulum</p>
            <h1>Veritabani ve yonetici</h1>
            <p>Hostinger MySQL bilgilerini ve ilk yonetici hesabini girin.</p>
        </div>
        <?php if ($error !== ''): ?>
            <div class="alert alert-error"><?= kc_admin_escape($error) ?></div>
        <?php endif; ?>
        <form method="post" class="admin-form install-form">
            <input type="hidden" name="csrf" value="<?= kc_admin_escape(kc_admin_csrf()) ?>">
            <input type="hidden" name="setup_key" value="<?= kc_admin_escape($providedKey) ?>">
            <div class="form-section">
                <h2>MySQL baglantisi</h2>
                <div class="form-grid">
                    <label><span>Sunucu</span><input name="db_host" value="localhost" required></label>
                    <label><span>Port</span><input name="db_port" type="number" value="3306" required></label>
                    <label><span>Veritabani adi</span><input name="db_name" required></label>
                    <label><span>Veritabani kullanicisi</span><input name="db_user" required></label>
                </div>
                <label><span>Veritabani parolasi</span><input name="db_password" type="password" autocomplete="new-password" required></label>
            </div>
            <div class="form-section">
                <h2>Ilk yonetici</h2>
                <label><span>Ad soyad</span><input name="admin_name" required></label>
                <label><span>E-posta</span><input name="admin_email" type="email" required></label>
                <label>
                    <span>Parola</span>
                    <input name="admin_password" type="password" minlength="12" autocomplete="new-password" required>
                    <small>En az 12 karakter.</small>
                </label>
            </div>
            <button type="submit">Guvenli kurulumu tamamla</button>
        </form>
    <?php endif; ?>
</main>
</body>
</html>
