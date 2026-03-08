<?php
// Hata ayıklama: Herhangi bir çıktının JSON'ı bozmasını engelle
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Zaman aşımını engelle - Windows/XAMPP yavaş olabilir
set_time_limit(600);
ignore_user_abort(true);

function update_log($msg)
{
    $log_file = dirname(__DIR__) . '/update_debug.log';
    $date = date('Y-m-d H:i:s');
    @file_put_contents($log_file, "[$date] $msg\n", FILE_APPEND);
}

update_log("--- GÜNCELLEME İŞLEMİ BAŞLATILDI ---");

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/functions.php';

// Sadece yönetici erişebilir
if (currentUser()['role'] !== ROLE_ADMIN) {
    ob_end_clean();
    jsonResponse(false, 'Bu işlem için yetkiniz yok.');
}

// 0. Git yüklü mü kontrol et
exec('git --version', $test_output, $test_return);
if ($test_return !== 0) {
    ob_end_clean();
    jsonResponse(false, 'HATA: "git" komutu bu bilgisayarda tanınmıyor. Güncelleme yapabilmek için bilgisayarınızda Git yüklü olmalı.', [
        'output' => 'Git not found in PATH'
    ]);
}

// Git Güvenlik Ayarı
$currentDir = str_replace('\\', '/', ROOT_PATH);
exec("git config --global --add safe.directory $currentDir 2>&1");

// 1. Git pull komutunu çalıştır
$force = isset($_GET['force']) && $_GET['force'] === '1';

if ($force) {
    $command = 'git fetch --all && git reset --hard origin/main 2>&1';
} else {
    $command = 'git pull origin main 2>&1';
}

// Git'in terminalden input beklemesini engelle
putenv('GIT_TERMINAL_PROMPT=0');

$output = [];
$return_var = 0;

// Proje kök dizinine git
chdir(ROOT_PATH);

update_log("Komut çalıştırılıyor: $command");
exec($command, $output, $return_var);
update_log("Komut bitti. Return: $return_var");

$output_str = implode("\n", $output);

if ($return_var === 0) {
    // PHP OPcache'i temizle (Eğer yüklüyse)
    if (function_exists('opcache_reset')) {
        opcache_reset();
    }

    update_log("Git başarılı, migrasyona geçiliyor.");
    // 2. Veritabanı migrasyonlarını çalıştır
    require_once __DIR__ . '/db_migrate.php';
    $migrationResult = runMigrations();
    update_log("Migrasyon sonucu: " . json_encode($migrationResult));

    $msg = 'Güncelleme başarıyla tamamlandı.';
    if ($migrationResult['data']['performed'] > 0) {
        $msg .= ' ' . $migrationResult['message'];
    }

    // Başarılı güncelleme sonrası mesaj
    ob_end_clean();
    jsonResponse(true, $msg, [
        'output' => $output_str,
        'migration' => $migrationResult
    ]);
} else {
    // Hata durumunda detaylı bilgi ver
    $error_msg = 'Güncelleme sırasında bir hata oluştu.';
    if (str_contains($output_str, 'local changes to the following files would be overwritten by merge')) {
        $error_msg = 'Yerel dosyalarda yapılan değişiklikler güncellemeye engel oluyor. Lütfen değişiklikleri geri alıp tekrar deneyin.';
    } elseif (str_contains($output_str, 'Could not resolve host')) {
        $error_msg = 'İnternet bağlantısı kurulamadı.';
    } elseif (str_contains($output_str, 'Permission denied')) {
        $error_msg = 'GitHub erişim yetkisi hatası. Lütfen Git ayarlarınızı kontrol edin.';
    }

    ob_end_clean();
    jsonResponse(false, $error_msg, [
        'output' => $output_str,
        'return_code' => $return_var
    ]);
}