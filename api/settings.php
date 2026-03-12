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
        $keys = ['mail_host', 'mail_port', 'mail_secure', 'mail_user', 'mail_from', 'mail_from_name', 'program_manager_email', 'critical_stock_notification_email', 'backup_notification_email'];
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
        $keys = ['header_bg_type', 'header_bg', 'header_color', 'footer_bg', 'footer_color', 'footer_text', 'google_font', 'font_size_scale', 'system_logo_width', 'system_logo_height'];
        foreach ($keys as $key) {
            if (isset($_POST[$key])) {
                set_setting($key, sanitize($_POST[$key]));
            }
        }

        // Eğer rastgele renk seçildiyse üret
        if (($_POST['header_bg_type'] ?? '') === 'random') {
            set_setting('header_bg', generateRandomSafeColor());
        }

        // Genel Form ("Kaydet" butonu) üzerinden logo yüklemesi
        if (isset($_FILES['system_logo_file']) && $_FILES['system_logo_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['system_logo_file'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (in_array($file['type'], $allowedTypes)) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'logo_' . time() . '.' . $ext;
                $uploadDir = __DIR__ . '/../uploads/system/';
                if (!is_dir($uploadDir))
                    mkdir($uploadDir, 0777, true);

                $oldLogo = get_setting('system_logo');
                if ($oldLogo && file_exists(__DIR__ . '/../' . $oldLogo))
                    @unlink(__DIR__ . '/../' . $oldLogo);

                if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                    set_setting('system_logo', 'uploads/system/' . $filename);
                }
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
        if (isset($_POST['backup_notification_email'])) {
            set_setting('backup_notification_email', sanitize($_POST['backup_notification_email']));
        }
        if (isset($_POST['allow_passive_with_stock'])) {
            set_setting('allow_passive_with_stock', sanitize($_POST['allow_passive_with_stock']));
        }
        if (isset($_POST['show_bulk_stock_update_to_user'])) {
            set_setting('show_bulk_stock_update_to_user', sanitize($_POST['show_bulk_stock_update_to_user']));
        }
        if (isset($_POST['kargo_gonderici'])) {
            set_setting('kargo_gonderici', sanitize($_POST['kargo_gonderici']));
        }
        if (isset($_POST['stock_alert_visibility'])) {
            set_setting('stock_alert_visibility', sanitize($_POST['stock_alert_visibility']));
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

    case 'save_gdrive':
        requireRole(ROLE_ADMIN);

        if (isset($_POST['google_drive_active'])) {
            set_setting('google_drive_active', sanitize($_POST['google_drive_active']));
        } else {
            set_setting('google_drive_active', '0'); // Checkbox gönderilmemişse kapalı kabul et
        }

        if (isset($_POST['google_drive_folder_id'])) {
            set_setting('google_drive_folder_id', sanitize($_POST['google_drive_folder_id']));
        }

        // JSON dosyası boşluk veya tırnak içeriyor olabilir, ham halini sanitization'dan hafif geçirerek kaydedelim
        if (isset($_POST['google_drive_credentials_json'])) {
            $jsonContent = trim($_POST['google_drive_credentials_json']);
            // JSON string içerisindeki XSS risklerini azaltmak için htmlspecialchars ile kaydedebiliriz ancak
            // API ile direkt okuyacağımız için ham JSON olarak veritabanına koymak daha güvenilirdir.
            // Fakat set_setting değerleri alırken sorun olmaması içi güvenli kaydet.
            set_setting('google_drive_credentials_json', $jsonContent);
        }

        jsonResponse(true, 'Google Drive yedekleme ayarları başarıyla kaydedildi.');

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

    case 'random_navbar_color':
        // Tüm roller görebilir dendiği için requireLogin() yeterli (başta var)
        $newColor = generateRandomSafeColor();
        set_setting('header_bg', $newColor);
        set_setting('header_bg_type', 'random'); // Tercihi de random'a çekelim ki ayarlar sayfasında uyumlu olsun
        jsonResponse(true, 'Navbar rengi güncellendi.', ['color' => $newColor]);

    default:
        jsonResponse(false, 'Geçersiz işlem.');
}