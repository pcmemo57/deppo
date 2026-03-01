<?php
/**
 * Güncelleme uygulama API'si
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/functions.php';

// Sadece yönetici erişebilir
if (currentUser()['role'] !== ROLE_ADMIN) {
    jsonResponse(false, 'Bu işlem için yetkiniz yok.');
}

// 0. Git yüklü mü kontrol et
exec('git --version', $test_output, $test_return);
if ($test_return !== 0) {
    jsonResponse(false, 'HATA: "git" komutu bu bilgisayarda tanınmıyor. Güncelleme yapabilmek için bilgisayarınızda Git yüklü olmalı ve sistem yoluna (PATH) eklenmiş olmalıdır.', [
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

$output = [];
$return_var = 0;

// Proje kök dizinine git
chdir(ROOT_PATH);

exec($command, $output, $return_var);

$output_str = implode("\n", $output);

if ($return_var === 0) {
    // Başarılı güncelleme sonrası mesaj
    jsonResponse(true, 'Güncelleme başarıyla tamamlandı.', [
        'output' => $output_str
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

    jsonResponse(false, $error_msg, [
        'output' => $output_str,
        'return_code' => $return_var
    ]);
}
