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
    exit(0);
} else {
    $err = implode("\n", $output);
    if (!file_exists($path) || filesize($path) == 0) {
        $err .= "\nDosya oluşturulamadı veya boş.";
    }
    log_msg('HATA: Yedekleme başarısız oldu.');
    log_msg('Hata detayı: ' . $err);
    exit(1);
}
