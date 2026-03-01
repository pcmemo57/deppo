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

// Git pull komutunu çalıştır
$command = 'git pull origin main 2>&1';
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
    }

    jsonResponse(false, $error_msg, [
        'output' => $output_str,
        'return_code' => $return_var
    ]);
}
