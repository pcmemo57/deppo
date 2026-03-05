<?php
/**
 * API — Ayarlar
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireLogin();
header('Content-Type: application/json; charset=utf-8');

// CSRF check
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($token)) {
        jsonResponse(false, 'Güvenlik doğrulaması başarısız (CSRF).');
    }
}

$action = sanitize($_POST['action'] ?? $_GET['action'] ?? '');

switch ($action) {
    case 'save_mail':
        requireRole(ROLE_ADMIN);
        $keys = ['mail_host', 'mail_port', 'mail_secure', 'mail_user', 'mail_from', 'mail_from_name', 'program_manager_email'];
        foreach ($keys as $key) {
            if (isset($_POST[$key])) {
                set_setting($key, sanitize($_POST[$key]));
            }
        }
        // Şifre (boşsa değiştirme)
        if (!empty($_POST['mail_pass'])) {
            set_setting('mail_pass', $_POST['mail_pass']);
        }
        jsonResponse(true, 'Mail ayarları kaydedildi.');

    case 'save_appearance':
        requireRole(ROLE_ADMIN);
        $keys = ['header_bg', 'header_color', 'footer_bg', 'footer_color', 'footer_text', 'google_font', 'system_logo_width', 'system_logo_height'];
        foreach ($keys as $key) {
            if (isset($_POST[$key])) {
                set_setting($key, sanitize($_POST[$key]));
            }
        }
        jsonResponse(true, 'Görünüm ayarları kaydedildi.');

    case 'save_currency':
        requireRole(ROLE_ADMIN);
        foreach (['usd_rate', 'eur_rate', 'base_currency'] as $key) {
            if (isset($_POST[$key])) {
                if ($key === 'base_currency') {
                    set_setting($key, sanitize($_POST[$key]));
                } else {
                    $val = str_replace(['.', ','], ['', '.'], sanitize($_POST[$key]));
                    set_setting($key, (string) (float) $val);
                }
            }
        }
        jsonResponse(true, 'Döviz kurları ve ayarları kaydedildi.');

    case 'save_general':
        requireRole(ROLE_ADMIN);
        if (isset($_POST['site_name'])) {
            set_setting('site_name', sanitize($_POST['site_name']));
        }
        if (isset($_POST['allow_passive_with_stock'])) {
            set_setting('allow_passive_with_stock', sanitize($_POST['allow_passive_with_stock']));
        }
        jsonResponse(true, 'Genel ayarlar kaydedildi.');

    case 'save_pdf':
        requireRole(ROLE_ADMIN);
        if (isset($_POST['pdf_scale'])) {
            set_setting('pdf_scale', sanitize($_POST['pdf_scale']));
        }
        if (isset($_POST['pdf_quality'])) {
            set_setting('pdf_quality', sanitize($_POST['pdf_quality']));
        }
        jsonResponse(true, 'PDF ayarları kaydedildi.');

    case 'update_currency':
        requireRole(ROLE_ADMIN, ROLE_USER);
        // TCMB XML
        $xml = @simplexml_load_file('https://www.tcmb.gov.tr/kurlar/today.xml');
        if (!$xml) {
            // Alternatif URL
            $xml = @simplexml_load_file('https://www.tcmb.gov.tr/kurlar/' . date('Ym') . '/' . date('dmy') . '.xml');
        }
        if (!$xml) {
            jsonResponse(false, 'TCMB bağlantısı sağlanamadı.');
        }
        $usd = null;
        $eur = null;
        foreach ($xml->Currency as $cur) {
            $code = (string) $cur->attributes()->CurrencyCode;
            if ($code === 'USD')
                $usd = (float) $cur->ForexSelling;
            if ($code === 'EUR')
                $eur = (float) $cur->ForexSelling;
        }
        if (!$usd || !$eur) {
            jsonResponse(false, 'Kur verisi alınamadı.');
        }
        set_setting('usd_rate', (string) $usd);
        set_setting('eur_rate', (string) $eur);
        set_setting('currency_updated', date('Y-m-d H:i:s'));
        jsonResponse(true, 'Kurlar güncellendi.', [
            'usd_formatted' => number_format($usd, 2, ',', '.'),
            'eur_formatted' => number_format($eur, 2, ',', '.'),
        ]);

    case 'test_mail':
        requireRole(ROLE_ADMIN);
        $to = sanitize($_POST['to'] ?? '');
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(false, 'Geçerli bir e-posta adresi girin.');
        }
        $sent = send_mail($to, 'Test Maili', '<p>Bu bir test mailidir. Mail ayarları doğru çalışıyor!</p>');
        if ($sent) {
            jsonResponse(true, 'Test maili gönderildi.');
        } else {
            jsonResponse(false, 'Mail gönderilemedi. Ayarları kontrol edin.');
        }

    case 'save_logo':
        requireRole(ROLE_ADMIN);
        if (!isset($_FILES['system_logo_file']) || $_FILES['system_logo_file']['error'] !== UPLOAD_ERR_OK) {
            jsonResponse(false, 'Dosya yüklenemedi veya seçilmedi.');
        }

        $file = $_FILES['system_logo_file'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            jsonResponse(false, 'Sadece JPG, PNG, GIF veya WEBP dosyaları yüklenebilir.');
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'logo_' . time() . '.' . $ext;
        $uploadDir = __DIR__ . '/../uploads/system/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Eski logoyu sil (isteğe bağlı)
        $oldLogo = get_setting('system_logo');
        if ($oldLogo && file_exists(__DIR__ . '/../' . $oldLogo)) {
            @unlink(__DIR__ . '/../' . $oldLogo);
        }

        if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
            $dbPath = 'uploads/system/' . $filename;
            set_setting('system_logo', $dbPath);

            // Eğer boyutlar da gönderildiyse kaydet
            if (isset($_POST['system_logo_width']))
                set_setting('system_logo_width', sanitize($_POST['system_logo_width']));
            if (isset($_POST['system_logo_height']))
                set_setting('system_logo_height', sanitize($_POST['system_logo_height']));

            jsonResponse(true, 'Logo başarıyla yüklendi.', ['url' => BASE_URL . '/' . $dbPath]);
        } else {
            jsonResponse(false, 'Dosya sunucuya kaydedilemedi.');
        }

    default:
        jsonResponse(false, 'Geçersiz işlem.');
}