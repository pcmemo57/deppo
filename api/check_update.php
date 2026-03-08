<?php
/**
 * Güncelleme kontrol API'si
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/functions.php';

// Sadece yönetici erişebilir
if (currentUser()['role'] !== ROLE_ADMIN) {
    jsonResponse(false, 'Bu işlem için yetkiniz yok.');
}

// GitHub'dank config.php dosyasını çekerek versiyonu kontrol et (Cache engellemek için zaman damgası ekliyoruz)
$repo_url = "https://raw.githubusercontent.com/pcmemo57/deppo/main/config/config.php?t=" . time();

$ctx = stream_context_create([
    'http' => [
        'timeout' => 5,
        'header' => "User-Agent: Deppo-Update-Checker\r\n"
    ]
]);

$remote_config = @file_get_contents($repo_url, false, $ctx);

if ($remote_config === false) {
    jsonResponse(false, 'Güncelleme sunucusuna bağlanılamadı. Lütfen internet bağlantınızı kontrol edin.');
}

// Uzak dosyadaki APP_VERSION değerini regex ile bul
if (preg_match("/define\('APP_VERSION',\s*'([^']+)'\)/", $remote_config, $matches)) {
    $remote_version = $matches[1];

    $update_available = version_compare($remote_version, APP_VERSION, '>');

    // Kontrol tarihini veritabanına kaydet
    set_setting('last_update_check', date('Y-m-d'));

    jsonResponse(true, 'Kontrol tamamlandı.', [
        'current_version' => APP_VERSION,
        'current_db_version' => get_setting('db_version', '1.0.0'),
        'remote_version' => $remote_version,
        'update_available' => $update_available,
        'message' => $update_available ? "Yeni bir sürüm mevcut: v$remote_version" : "Sisteminiz güncel."
    ]);
} else {
    jsonResponse(false, 'Uzak sunucuda versiyon bilgisi bulunamadı.');
}
