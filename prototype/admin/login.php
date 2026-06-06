<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

if (kc_admin_user()) {
    header('Location: index.php');
    exit;
}

$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    kc_admin_verify_csrf();
    $email = filter_var(trim((string)($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL) ?: '';
    $password = (string)($_POST['password'] ?? '');

    try {
        $pdo = kc_admin_pdo();
        $statement = $pdo->prepare(
            'SELECT id, name, email, password_hash, role
            FROM admin_users WHERE email = :email AND is_active = 1 LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['kc_admin'] = [
                'id' => (int)$user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
            ];
            $pdo->prepare('UPDATE admin_users SET last_login_at = NOW() WHERE id = :id')
                ->execute(['id' => $user['id']]);
            header('Location: index.php');
            exit;
        }

        $error = 'E-posta veya parola hatali.';
    } catch (Throwable $exception) {
        $error = 'Yonetim paneli henuz kurulmamış veya veritabanina ulasilamiyor.';
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Yonetici Girisi | Karadag Celik</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="auth-page">
<main class="auth-panel">
    <a class="brand auth-brand" href="../">
        <span class="brand-mark">KÇ</span>
        <span><strong>Karadag Celik</strong><small>Yonetim Paneli</small></span>
    </a>
    <div class="auth-copy">
        <p class="eyebrow">Guvenli giris</p>
        <h1>Operasyon paneli</h1>
        <p>Teklif, siparis ve musteri mesajlarini tek yerden yonetin.</p>
    </div>
    <?php if ($error !== ''): ?>
        <div class="alert alert-error"><?= kc_admin_escape($error) ?></div>
    <?php endif; ?>
    <form method="post" class="admin-form">
        <input type="hidden" name="csrf" value="<?= kc_admin_escape(kc_admin_csrf()) ?>">
        <label>
            <span>E-posta</span>
            <input type="email" name="email" autocomplete="username" required>
        </label>
        <label>
            <span>Parola</span>
            <input type="password" name="password" autocomplete="current-password" required>
        </label>
        <button type="submit">Giris yap</button>
    </form>
</main>
</body>
</html>

