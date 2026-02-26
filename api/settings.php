<?php
/**
 * API — Ayarlar
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireRole(ROLE_ADMIN);
header('Content-Type: application/json; charset=utf-8');

$action = sanitize($_POST['action'] ?? $_GET['action'] ?? '');

switch ($action) {
    case 'save_mail':
        $keys = ['mail_host', 'mail_port', 'mail_secure', 'mail_user', 'mail_from', 'mail_from_name'];
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
        $keys = ['header_bg', 'header_color', 'footer_bg', 'footer_color', 'footer_text', 'google_font'];
        foreach ($keys as $key) {
            if (isset($_POST[$key])) {
                set_setting($key, sanitize($_POST[$key]));
            }
        }
        jsonResponse(true, 'Görünüm ayarları kaydedildi.');

    case 'save_currency':
        foreach (['usd_rate', 'eur_rate'] as $key) {
            if (isset($_POST[$key])) {
                $val = str_replace(['.', ','], ['', '.'], sanitize($_POST[$key]));
                set_setting($key, (string)(float)$val);
            }
        }
        jsonResponse(true, 'Döviz kurları kaydedildi.');

    case 'save_general':
        if (isset($_POST['site_name'])) {
            set_setting('site_name', sanitize($_POST['site_name']));
        }
        jsonResponse(true, 'Genel ayarlar kaydedildi.');

    case 'update_currency':
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
            $code = (string)$cur->attributes()->CurrencyCode;
            if ($code === 'USD')
                $usd = (float)$cur->ForexSelling;
            if ($code === 'EUR')
                $eur = (float)$cur->ForexSelling;
        }
        if (!$usd || !$eur) {
            jsonResponse(false, 'Kur verisi alınamadı.');
        }
        set_setting('usd_rate', (string)$usd);
        set_setting('eur_rate', (string)$eur);
        set_setting('currency_updated', date('Y-m-d H:i:s'));
        jsonResponse(true, 'Kurlar güncellendi.', [
            'usd_formatted' => number_format($usd, 2, ',', '.'),
            'eur_formatted' => number_format($eur, 2, ',', '.'),
        ]);

    case 'test_mail':
        $to = sanitize($_POST['to'] ?? '');
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(false, 'Geçerli bir e-posta adresi girin.');
        }
        $sent = send_mail($to, 'Test Maili', '<p>Bu bir test mailidir. Mail ayarları doğru çalışıyor!</p>');
        if ($sent) {
            jsonResponse(true, 'Test maili gönderildi.');
        }
        else {
            jsonResponse(false, 'Mail gönderilemedi. Ayarları kontrol edin.');
        }

    default:
        jsonResponse(false, 'Geçersiz işlem.');
}