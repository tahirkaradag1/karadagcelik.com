<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

if (!kc_google_configured()) {
    header('Location: ../#profile');
    exit;
}

$google = (array)kc_customer_config()['google'];
$state = bin2hex(random_bytes(24));
$_SESSION['kc_google_state'] = $state;

$query = http_build_query([
    'client_id' => $google['client_id'],
    'redirect_uri' => $google['redirect_uri'],
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'state' => $state,
    'prompt' => 'select_account',
    'include_granted_scopes' => 'true',
]);

header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $query);
exit;

