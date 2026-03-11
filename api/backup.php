<?php
/**
 * Veritabanı Yedekleme ve Geri Yükleme API
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireRole(ROLE_ADMIN);
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

$action = sanitize($_POST['action'] ?? $_GET['action'] ?? '');
$backupDir = ROOT_PATH . '/backups/';

if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}

switch ($action) {
    case 'list':
        $files = glob($backupDir . '*.sql');
        $data = [];
        $driveFiles = [];

        // Google Drive aktifse dosyaları getir
        if (get_setting('google_drive_active', '0') === '1') {
            $folderId = get_setting('google_drive_folder_id', '');
            $jsonAuth = get_setting('google_drive_credentials_json', '');
            $tokenJson = get_setting('google_drive_token', '');

            if (!empty($folderId) && !empty($jsonAuth) && !empty($tokenJson)) {
                if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
                    require_once __DIR__ . '/../vendor/autoload.php';
                    try {
                        $authConfig = json_decode($jsonAuth, true);
                        $client = new \Google\Client();
                        $client->setAuthConfig($authConfig);
                        $client->addScope(\Google\Service\Drive::DRIVE_FILE);
                        $client->setAccessToken(json_decode($tokenJson, true));

                        if ($client->isAccessTokenExpired()) {
                            if ($client->getRefreshToken()) {
                                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                                set_setting('google_drive_token', json_encode($client->getAccessToken()));
                            }
                        }

                        $driveService = new \Google\Service\Drive($client);
                        $q = "trashed=false and '" . $folderId . "' in parents";
                        $results = $driveService->files->listFiles([
                            'q' => $q,
                            'fields' => 'files(id, name, size, createdTime)',
                            'pageSize' => 100
                        ]);

                        foreach ($results->getFiles() as $file) {
                            $driveFiles[$file->getName()] = [
                                'id' => $file->getId(),
                                'size' => $file->getSize() ? round($file->getSize() / 1024, 2) . ' KB' : 'Bilinmiyor',
                                'date' => date('d.m.Y H:i:s', strtotime($file->getCreatedTime())),
                                'timestamp' => strtotime($file->getCreatedTime())
                            ];
                        }
                    } catch (Exception $e) {
                        // Sessizce geç, hatayı API'ye yansıtma ki listeleme bozulmasın
                    }
                }
            }
        }

        foreach ($files as $file) {
            $mtime = filemtime($file);
            $basename = basename($file);
            $item = [
                'filename' => $basename,
                'size' => round(filesize($file) / 1024, 2) . ' KB',
                'date' => date('d.m.Y H:i:s', $mtime),
                'timestamp' => $mtime,
                'is_local' => true,
                'on_drive' => false
            ];

            if (isset($driveFiles[$basename])) {
                $item['on_drive'] = true;
                $item['drive_id'] = $driveFiles[$basename]['id'];
                unset($driveFiles[$basename]); // işleneni sil
            }
            $data[] = $item;
        }

        // Kalan (sadece Drive'da olan) dosyaları ekle
        foreach ($driveFiles as $name => $info) {
            $data[] = [
                'filename' => $name,
                'size' => $info['size'],
                'date' => $info['date'],
                'timestamp' => $info['timestamp'],
                'is_local' => false,
                'on_drive' => true,
                'drive_id' => $info['id']
            ];
        }

        // Yeniden eskiye sırala
        usort($data, function ($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });

        jsonResponse(true, '', $data);
        break;

    case 'create':
        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $path = $backupDir . $filename;

        // Binary yolu bulma (Cross-Platform)
        $dumpBinary = 'mysqldump';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows XAMPP varsayılan yolları
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
            // macOS / Linux yolları
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
            $msg = 'Yedekleme başarıyla oluşturuldu: ' . $filename;

            // Google Drive entegrasyonu
            if (get_setting('google_drive_active', '0') === '1') {
                $folderId = get_setting('google_drive_folder_id', '');
                $jsonAuth = get_setting('google_drive_credentials_json', '');
                $tokenJson = get_setting('google_drive_token', '');

                if (!empty($folderId) && !empty($jsonAuth)) {
                    // Sadece gerekliyse autoload et
                    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
                        require_once __DIR__ . '/../vendor/autoload.php';
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
                                    set_setting('google_drive_token', json_encode($newAccessToken));
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

                            $file = $driveService->files->create($fileMetadata, [
                                'data' => $content,
                                'mimeType' => 'application/octet-stream',
                                'uploadType' => 'multipart',
                                'fields' => 'id'
                            ]);

                            $msg .= ' Ayrıca Google Drive hesabınıza başarıyla yüklendi.';
                        } catch (Exception $e) {
                            $msg .= ' Ancak Google Drive\'a yüklenirken hata oluştu: ' . $e->getMessage();
                        }
                    } else {
                        $msg .= ' Ancak Google API kütüphanesi (vender) bulunamadığı için Drive\'a yüklenemedi.';
                    }
                } else {
                    $msg .= ' Ancak Google Drive ayarları eksik olduğu için yüklenemedi.';
                }
            }

            // Bildirim E-postası Gönder
            $notifEmail = get_setting('backup_notification_email', '');
            if (!empty($notifEmail)) {
                $subject = "Yedekleme Bildirimi: Başarılı (" . APP_NAME . ")";
                $gDriveTxt = 'Aktif değil';
                if (get_setting('google_drive_active', '0') === '1') {
                    $gDriveTxt = (str_contains($msg, 'başarıyla yüklendi') ? '<span style="color: #28a745;">✓ Yüklendi</span>' : '<span style="color: #dc3545;">✗ Yüklenemedi</span>');
                }

                $body = generate_email_body(
                    'Yedekleme Tamamlandı',
                    [
                        'Dosya Adı' => $filename,
                        'Tarih' => date('d.m.Y H:i:s'),
                        'Google Drive' => $gDriveTxt
                    ],
                    'success',
                    'Sistem üzerinden manuel yedekleme gerçekleştirildi.'
                );
                send_mail($notifEmail, $subject, $body);
            }

            jsonResponse(true, $msg);
        } else {
            $err = implode("\n", $output);
            if (!file_exists($path) || filesize($path) == 0) {
                $err .= "\nDosya oluşturulamadı veya boş.";
            }

            // Hata Bildirim E-postası Gönder
            $notifEmail = get_setting('backup_notification_email', '');
            if (!empty($notifEmail)) {
                $subject = "Yedekleme Bildirimi: HATA! (" . APP_NAME . ")";
                $body = generate_email_body(
                    'Yedekleme Başarısız',
                    [
                        'Hata Detayı' => '<pre style="white-space: pre-wrap; font-size: 12px; background: #fff1f0; padding: 10px; border-radius: 4px; color: #a8071a; border: 1px solid #ffa39e;">' . e($err) . '</pre>',
                        'Tarih' => date('d.m.Y H:i:s')
                    ],
                    'error',
                    'Lütfen sunucu ayarlarını veya disk alanını kontrol edin.'
                );
                send_mail($notifEmail, $subject, $body);
            }

            jsonResponse(false, 'Yedekleme başarısız oldu.', ['error' => $err, 'command' => $command]);
        }
        break;

    case 'restore':
        $filename = sanitize($_POST['filename'] ?? '');
        $driveId = sanitize($_POST['drive_id'] ?? '');
        $path = $backupDir . $filename;

        // Eğer dosya yerelde yoksa ama Drive ID varsa, önce Drive'dan indir
        if (!file_exists($path) && !empty($driveId)) {
            $jsonAuth = get_setting('google_drive_credentials_json', '');
            $tokenJson = get_setting('google_drive_token', '');

            if (!empty($jsonAuth) && !empty($tokenJson)) {
                if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
                    require_once __DIR__ . '/../vendor/autoload.php';
                    try {
                        $authConfig = json_decode($jsonAuth, true);
                        $client = new \Google\Client();
                        $client->setAuthConfig($authConfig);
                        $client->addScope(\Google\Service\Drive::DRIVE_FILE);
                        $client->setAccessToken(json_decode($tokenJson, true));

                        if ($client->isAccessTokenExpired()) {
                            if ($client->getRefreshToken()) {
                                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                                set_setting('google_drive_token', json_encode($client->getAccessToken()));
                            }
                        }

                        $driveService = new \Google\Service\Drive($client);
                        $response = $driveService->files->get($driveId, ['alt' => 'media']);
                        $content = $response->getBody()->getContents();

                        file_put_contents($path, $content);
                    } catch (Exception $e) {
                        jsonResponse(false, 'Dosya Google Drive\'dan indirilemedi: ' . $e->getMessage());
                    }
                } else {
                    jsonResponse(false, 'Google API kütüphanesi bulunamadı.');
                }
            } else {
                jsonResponse(false, 'Google Drive bağlantı ayarları eksik.');
            }
        }

        $path = realpath($path);

        if (!$filename || strpos($path, realpath($backupDir)) !== 0 || !file_exists($path)) {
            jsonResponse(false, 'Geçersiz dosya veya dosya bulunamadı.');
        }

        // Binary yolu bulma (Cross-Platform)
        $mysqlBinary = 'mysql';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $paths = [
                'C:/xampp/mysql/bin/mysql.exe',
                'D:/xampp/mysql/bin/mysql.exe',
                'mysql'
            ];
            foreach ($paths as $p) {
                if (file_exists($p) || @shell_exec("where $p")) {
                    $mysqlBinary = $p;
                    break;
                }
            }
        } else {
            $paths = [
                '/Applications/XAMPP/xamppfiles/bin/mysql',
                '/opt/lampp/bin/mysql',
                'mysql'
            ];
            foreach ($paths as $p) {
                if (file_exists($p) || @shell_exec("which $p")) {
                    $mysqlBinary = $p;
                    break;
                }
            }
        }

        $command = sprintf(
            '%s --user=%s --password=%s --host=%s %s < %s 2>&1',
            escapeshellarg($mysqlBinary),
            escapeshellarg(DB_USER),
            escapeshellarg(DB_PASS),
            escapeshellarg(DB_HOST),
            escapeshellarg(DB_NAME),
            escapeshellarg($path)
        );

        exec($command, $output, $returnVar);

        if ($returnVar === 0) {
            jsonResponse(true, 'Veritabanı başarıyla geri yüklendi.');
        } else {
            jsonResponse(false, 'Geri yükleme başarısız oldu.', ['error' => implode("\n", $output), 'command' => $command]);
        }
        break;

    case 'delete':
        $filename = sanitize($_POST['filename'] ?? '');
        $path = realpath($backupDir . $filename);

        if (!$filename || strpos($path, realpath($backupDir)) !== 0 || !file_exists($path)) {
            jsonResponse(false, 'Geçersiz dosya.');
        }

        if (unlink($path)) {
            jsonResponse(true, 'Yedek dosyası silindi.');
        } else {
            jsonResponse(false, 'Dosya silinemedi.');
        }
        break;

    case 'download':
        $filename = sanitize($_GET['filename'] ?? '');
        $path = realpath($backupDir . $filename);

        if (!$filename || strpos($path, realpath($backupDir)) !== 0 || !file_exists($path)) {
            die('Geçersiz dosya.');
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;

    default:
        jsonResponse(false, 'Geçersiz işlem.');
}
