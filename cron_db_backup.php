<?php
/**
 * CRON - Veritabanı Yedekleme (CLI)
 * Bu dosya arayüzden bağımsız olarak, komut satırı (CMD/Terminal)
 * veya arka plan görevleriyle çalıştırılmak üzere tasarlanmıştır.
 */

// Sadece komut satırından çalışsın
if (php_sapi_name() !== 'cli' && !isset($_GET['force'])) {
    die("Bu script sadece komut satırından veya zamanlanmış görevler ile çalıştırılabilir.\n");
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';

function log_msg(string $msg): void
{
    echo '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
}

log_msg('Veritabanı yedekleme işlemi başlatılıyor...');

$backupDir = ROOT_PATH . '/backups/';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}

$filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
$path = $backupDir . $filename;

// Binary yolu bulma (Cross-Platform) - api/backup.php'den uyarlandı
$dumpBinary = 'mysqldump';
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    $paths = [
        'C:/xampp/mysql/bin/mysqldump.exe',
        'D:/xampp/mysql/bin/mysqldump.exe',
        'mysqldump'
    ];
    foreach ($paths as $p) {
        if (file_exists($p) || @shell_exec("where $p")) {
            $dumpBinary = $p;
            break;
        }
    }
} else {
    $paths = [
        '/Applications/XAMPP/xamppfiles/bin/mysqldump',
        '/opt/lampp/bin/mysqldump',
        'mysqldump'
    ];
    foreach ($paths as $p) {
        if (file_exists($p) || @shell_exec("which $p")) {
            $dumpBinary = $p;
            break;
        }
    }
}

$command = sprintf(
    '%s --user=%s --password=%s --host=%s %s > %s 2>&1',
    escapeshellarg($dumpBinary),
    escapeshellarg(DB_USER),
    escapeshellarg(DB_PASS),
    escapeshellarg(DB_HOST),
    escapeshellarg(DB_NAME),
    escapeshellarg($path)
);

exec($command, $output, $returnVar);

if ($returnVar === 0 && file_exists($path) && filesize($path) > 0) {
    log_msg('BAŞARILI: Yedekleme oluşturuldu: ' . $filename);
    log_msg('Dosya yolu: ' . $path);

    // Google Drive Entegrasyonu
    if (get_setting('google_drive_active', '0') === '1') {
        log_msg('Google Drive yedeği yükleniyor...');

        $folderId = get_setting('google_drive_folder_id', '');
        $jsonAuth = get_setting('google_drive_credentials_json', '');
        $tokenJson = get_setting('google_drive_token', '');

        if (!empty($folderId) && !empty($jsonAuth)) {
            $autoloadPath = ROOT_PATH . '/vendor/autoload.php';
            if (file_exists($autoloadPath)) {
                require_once $autoloadPath;
                try {
                    if (empty($tokenJson)) {
                        throw new Exception("Google hesabı ile yetkilendirme (bağlantı) yapılmamış. Ayarlardan 'Google ile Bağlan' butonunu kullanın.");
                    }

                    $authConfig = json_decode($jsonAuth, true);
                    if (!$authConfig) {
                        throw new Exception("Geçersiz Client Secret JSON formatı.");
                    }

                    $client = new \Google\Client();
                    $client->setAuthConfig($authConfig);
                    $client->addScope(\Google\Service\Drive::DRIVE_FILE);
                    $client->setAccessType('offline');

                    $accessToken = json_decode($tokenJson, true);
                    $client->setAccessToken($accessToken);

                    // Token refreshToken ile yenilenmişse veritabanına geri kaydet
                    if ($client->isAccessTokenExpired()) {
                        if ($client->getRefreshToken()) {
                            $newAccessToken = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                            // CLI ortamında set_setting fonksiyonunun SQL'e yazabildiğinden emin olmalıyız (database.php ve functions.php sayesinde yazabilir)
                            set_setting('google_drive_token', json_encode($newAccessToken));
                            log_msg('Google Token yenilendi ve kaydedildi.');
                        } else {
                            throw new Exception("Google Token süresi dolmuş. Lütfen ayarlardan Google ile tekrar bağlanın.");
                        }
                    }

                    $driveService = new \Google\Service\Drive($client);

                    $fileMetadata = new \Google\Service\Drive\DriveFile([
                        'name' => $filename,
                        'parents' => [$folderId]
                    ]);

                    $content = file_get_contents($path);

                    $driveFile = $driveService->files->create($fileMetadata, [
                        'data' => $content,
                        'mimeType' => 'application/octet-stream',
                        'uploadType' => 'multipart',
                        'fields' => 'id'
                    ]);

                    log_msg('BAŞARILI: Google Drive yedeği yüklendi. Drive ID: ' . $driveFile->id);
                    $driveStatus = "Yüklendi (ID: " . $driveFile->id . ")";
                } catch (Exception $e) {
                    log_msg('HATA: Google Drive yüklemesi başarısız: ' . $e->getMessage());
                    $driveStatus = "Hata: " . $e->getMessage();
                }
            } else {
                log_msg('HATA: vendor/autoload.php bulunamadı. Drive yüklemesi yapılamadı.');
                $driveStatus = "Hata: vendor/autoload.php bulunamadı.";
            }
        } else {
            log_msg('HATA: Google Drive ayarları (Klasör ID veya Client Secret) eksik.');
            $driveStatus = "Hata: Ayarlar eksik.";
        }
    }

    // Bildirim E-postası Gönder
    $notifEmail = get_setting('backup_notification_email', '');
    if (!empty($notifEmail)) {
        $subject = "Cron Yedekleme Bildirimi: Başarılı (" . APP_NAME . ")";
        $gDriveTxt = 'Aktif değil';
        if (get_setting('google_drive_active', '0') === '1') {
            $gDriveTxt = (isset($driveStatus) && str_contains($driveStatus, 'Yüklendi')) ? '<span style="color: #28a745;">✓ Yüklendi</span>' : '<span style="color: #dc3545;">✗ Hata</span>';
        }

        $body = generate_email_body(
            'Otomatik Yedekleme Tamamlandı',
            [
                'Dosya Adı' => $filename,
                'Tarih' => date('d.m.Y H:i:s'),
                'Google Drive' => $gDriveTxt
            ],
            'success',
            'Sistem üzerinden zamanlanmış görev (CRON) ile yedekleme gerçekleştirildi.'
        );
        send_mail($notifEmail, $subject, $body);
    }

    exit(0);
} else {
    $err = implode("\n", $output);
    if (!file_exists($path) || filesize($path) == 0) {
        $err .= "\nDosya oluşturulamadı veya boş.";
    }
    log_msg('HATA: Yedekleme başarısız oldu.');
    log_msg('Hata detayı: ' . $err);

    // Hata Bildirim E-postası Gönder
    $notifEmail = get_setting('backup_notification_email', '');
    if (!empty($notifEmail)) {
        $subject = "Cron Yedekleme Bildirimi: HATA! (" . APP_NAME . ")";
        $body = generate_email_body(
            'Otomatik Yedekleme Başarısız',
            [
                'Hata Detayı' => '<pre style="white-space: pre-wrap; font-size: 12px; background: #fff1f0; padding: 10px; border-radius: 4px; color: #a8071a; border: 1px solid #ffa39e;">' . e($err) . '</pre>',
                'Tarih' => date('d.m.Y H:i:s')
            ],
            'error',
            'Lütfen sunucu ayarlarını, mysqldump yolunu veya disk alanını kontrol edin.'
        );
        send_mail($notifEmail, $subject, $body);
    }

    exit(1);
}
