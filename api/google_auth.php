<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireRole(ROLE_ADMIN);

$jsonAuth = get_setting('google_drive_credentials_json', '');

if (empty($jsonAuth)) {
    die("Hata: Önce Ayarlar kısmından Client Secret JSON dosyasını yüklemeli ve kaydetmelisiniz. <br><a href='../index.php?page=settings&tab=gdrive'>Ayarlara Dön</a>");
}

if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    die("Hata: Google API kütüphanesi (vendor klasörü) bulunamadı.");
}

require_once __DIR__ . '/../vendor/autoload.php';

try {
    $authConfig = json_decode($jsonAuth, true);
    if (!$authConfig || !isset($authConfig['web'])) {
        die("Hata: Geçersiz JSON dosyası. Lütfen OAuth 2.0 Web Client kimliği (Client Secret) indirdiğinizden emin olun.<br><a href='../index.php?page=settings&tab=gdrive'>Ayarlara Dön</a>");
    }

    $client = new \Google\Client();
    $client->setAuthConfig($authConfig);
    $client->addScope(\Google\Service\Drive::DRIVE_FILE);
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');

    // Redirect URI must exactly match the authorized URI in Google Cloud Console
    // Construct an absolute URL even if BASE_URL is just a relative path (like '/deppo')
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];

    // If BASE_URL already contains http, use it directly, else prepend protocol and host
    if (strpos(BASE_URL, 'http') === 0) {
        $redirectUri = BASE_URL . '/api/google_auth.php';
    } else {
        $redirectUri = $protocol . '://' . $host . BASE_URL . '/api/google_auth.php';
    }

    $client->setRedirectUri($redirectUri);

    if (isset($_GET['code'])) {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

        if (isset($token['error'])) {
            die('Google OAuth Hatası: ' . (isset($token['error_description']) ? $token['error_description'] : $token['error']));
        }

        $client->setAccessToken($token);

        // Save the access/refresh token to the settings database
        set_setting('google_drive_token', json_encode($token));

        header('Location: ' . BASE_URL . '/index.php?page=settings&tab=gdrive');
        exit;
    } else {
        $authUrl = $client->createAuthUrl();
        header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
        exit;
    }
} catch (Exception $e) {
    die("Sistemsel Hata: " . $e->getMessage() . "<br><a href='../index.php?page=settings&tab=gdrive'>Ayarlara Dön</a>");
}
