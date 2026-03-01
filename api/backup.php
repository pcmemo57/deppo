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

        // XAMPP mysql/bin yolunu kontrol et (macOS varsayılan)
        $mysqldumpPath = '/Applications/XAMPP/xamppfiles/bin/mysqldump';
        if (!file_exists($mysqldumpPath)) {
            $mysqldumpPath = 'mysqldump'; // PATH'de varsa
        }

        $command = sprintf(
            '%s --user=%s --password=%s --host=%s %s > %s 2>&1',
            escapeshellarg($mysqldumpPath),
            escapeshellarg(DB_USER),
            escapeshellarg(DB_PASS),
            escapeshellarg(DB_HOST),
            escapeshellarg(DB_NAME),
            escapeshellarg($path)
        );

        exec($command, $output, $returnVar);

        if ($returnVar === 0) {
            jsonResponse(true, 'Yedekleme başarıyla oluşturuldu: ' . $filename);
        } else {
            jsonResponse(false, 'Yedekleme başarısız oldu.', ['error' => implode("\n", $output)]);
        }
        break;

    case 'restore':
        $filename = sanitize($_POST['filename'] ?? '');
        $path = realpath($backupDir . $filename);

        if (!$filename || strpos($path, realpath($backupDir)) !== 0 || !file_exists($path)) {
            jsonResponse(false, 'Geçersiz dosya.');
        }

        $mysqlPath = '/Applications/XAMPP/xamppfiles/bin/mysql';
        if (!file_exists($mysqlPath)) {
            $mysqlPath = 'mysql'; // PATH'de varsa
        }

        $command = sprintf(
            '%s --user=%s --password=%s --host=%s %s < %s 2>&1',
            escapeshellarg($mysqlPath),
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
            jsonResponse(false, 'Geri yükleme başarısız oldu.', ['error' => implode("\n", $output)]);
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
