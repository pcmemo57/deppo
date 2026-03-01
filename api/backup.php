<?php
/**
 * Veritabanı Yedekleme ve Geri Yükleme API
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireRole(ROLE_ADMIN);
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
        foreach ($files as $file) {
            $mtime = filemtime($file);
            $data[] = [
                'filename' => basename($file),
                'size' => round(filesize($file) / 1024, 2) . ' KB',
                'date' => date('d.m.Y H:i:s', $mtime),
                'timestamp' => $mtime
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
            jsonResponse(true, 'Yedekleme başarıyla oluşturuldu: ' . $filename);
        } else {
            $err = implode("\n", $output);
            if (!file_exists($path) || filesize($path) == 0) {
                $err .= "\nDosya oluşturulamadı veya boş.";
            }
            jsonResponse(false, 'Yedekleme başarısız oldu.', ['error' => $err, 'command' => $command]);
        }
        break;

    case 'restore':
        $filename = sanitize($_POST['filename'] ?? '');
        $path = realpath($backupDir . $filename);

        if (!$filename || strpos($path, realpath($backupDir)) !== 0 || !file_exists($path)) {
            jsonResponse(false, 'Geçersiz dosya.');
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
