<?php
require_once 'config/db.php';
require_once 'config/auth.php';
require_once 'config/calendar_integration.php';

$user = require_auth($conn);
$userId = (int)($user['usuario_id'] ?? $_SESSION['usuario_id'] ?? 0);
$companyId = calendar_user_company_id($conn, $userId);
$provider = strtolower(trim((string)($_GET['provider'] ?? $_SESSION['calendar_oauth_provider'] ?? '')));
$state = (string)($_GET['state'] ?? '');

function calendar_auth_fail(string $message): void
{
    header('Location: configuracion.php?tab=calendar&calendar_error=' . urlencode($message));
    exit;
}

if (!in_array($provider, ['google', 'microsoft'], true)) {
    calendar_auth_fail('Selecciona Google o Microsoft para continuar.');
}
if (!calendar_provider_configured($provider)) {
    calendar_auth_fail('El administrador todavía no ha configurado las credenciales de ' . calendar_provider_label($provider) . '.');
}

if (isset($_GET['code'])) {
    if ($state === '' || !hash_equals((string)($_SESSION['calendar_oauth_state'] ?? ''), $state) || $provider !== ($_SESSION['calendar_oauth_provider'] ?? '')) {
        calendar_auth_fail('La solicitud de conexión venció. Intenta nuevamente.');
    }
    try {
        if ($provider === 'google') {
            require_once 'vendor/autoload.php';
            $client = new Google\Client();
            $client->setAuthConfig(__DIR__ . '/credentials.json');
            $client->setRedirectUri(calendar_redirect_uri());
            $client->addScope(Google\Service\Calendar::CALENDAR);
            $client->setAccessType('offline');
            $token = $client->fetchAccessTokenWithAuthCode((string)$_GET['code']);
            if (!empty($token['error'])) {
                throw new RuntimeException($token['error_description'] ?? 'Google rechazó la conexión.');
            }
            $token['created'] = time();
            calendar_save_connection($conn, $userId, $companyId, 'google', $token);
            $_SESSION['google_access_token'] = $token;
        } else {
            $tenant = trim((string)(getenv('MICROSOFT_TENANT_ID') ?: 'common'));
            $token = calendar_http_request("https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/token", [
                'method' => 'POST',
                'headers' => ['Content-Type: application/x-www-form-urlencoded'],
                'body' => http_build_query([
                    'client_id' => getenv('MICROSOFT_CLIENT_ID'),
                    'client_secret' => getenv('MICROSOFT_CLIENT_SECRET'),
                    'code' => $_GET['code'],
                    'redirect_uri' => calendar_redirect_uri(),
                    'grant_type' => 'authorization_code',
                    'scope' => 'openid profile email offline_access Calendars.ReadWrite',
                ]),
            ]);
            $token['created'] = time();
            $profile = calendar_http_request('https://graph.microsoft.com/v1.0/me?$select=mail,userPrincipalName', [
                'headers' => ['Authorization: Bearer ' . $token['access_token']],
            ]);
            $email = $profile['mail'] ?? $profile['userPrincipalName'] ?? null;
            calendar_save_connection($conn, $userId, $companyId, 'microsoft', $token, $email);
            unset($_SESSION['google_access_token']);
        }
        unset($_SESSION['calendar_oauth_state'], $_SESSION['calendar_oauth_provider']);
        header('Location: configuracion.php?tab=calendar&calendar=connected');
        exit;
    } catch (Throwable $e) {
        calendar_auth_fail($e->getMessage());
    }
}

$oauthState = bin2hex(random_bytes(24));
$_SESSION['calendar_oauth_state'] = $oauthState;
$_SESSION['calendar_oauth_provider'] = $provider;

if ($provider === 'google') {
    require_once 'vendor/autoload.php';
    $client = new Google\Client();
    $client->setAuthConfig(__DIR__ . '/credentials.json');
    $client->setRedirectUri(calendar_redirect_uri());
    $client->addScope(Google\Service\Calendar::CALENDAR);
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');
    $client->setState($oauthState);
    header('Location: ' . filter_var($client->createAuthUrl(), FILTER_SANITIZE_URL));
    exit;
}

$tenant = trim((string)(getenv('MICROSOFT_TENANT_ID') ?: 'common'));
$url = "https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/authorize?" . http_build_query([
    'client_id' => getenv('MICROSOFT_CLIENT_ID'),
    'response_type' => 'code',
    'redirect_uri' => calendar_redirect_uri(),
    'response_mode' => 'query',
    'scope' => 'openid profile email offline_access Calendars.ReadWrite',
    'state' => $oauthState,
    'prompt' => 'select_account',
]);
header('Location: ' . $url);
exit;
