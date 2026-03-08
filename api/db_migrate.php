<?php
/**
 * Veritabanı Migrasyon (Migration) API'si
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/functions.php';

// Sadece yönetici erişebilir
if (currentUser()['role'] !== ROLE_ADMIN) {
    jsonResponse(false, 'Bu işlem için yetkiniz yok.');
}

function runMigrations()
{
    if (function_exists('update_log'))
        update_log("runMigrations() başladı.");

    $currentDbVersion = get_setting('db_version', '1.0.0');
    $migrationsDir = ROOT_PATH . '/database/migrations';

    if (!is_dir($migrationsDir)) {
        if (function_exists('update_log'))
            update_log("Migrasyon dizini yok: $migrationsDir");
        return ['success' => true, 'message' => 'Migrasyon dizini bulunamadı.', 'data' => ['performed' => 0]];
    }

    $files = scandir($migrationsDir);
    $migrationFiles = [];

    foreach ($files as $file) {
        if (preg_match('/^v?(\d+\.\d+\.\d+).*\.sql$/i', $file, $matches)) {
            $version = $matches[1];
            if (version_compare($version, $currentDbVersion, '>')) {
                $migrationFiles[$version] = $file;
            }
        }
    }

    if (empty($migrationFiles)) {
        if (function_exists('update_log'))
            update_log("Uygulanacak migrasyon dosyası yok.");
        return ['success' => true, 'message' => 'Veritabanı zaten güncel.', 'data' => ['performed' => 0]];
    }

    // Versiyona göre sırala
    uksort($migrationFiles, 'version_compare');

    $performedCount = 0;
    $appliedVersions = [];

    if (function_exists('update_log'))
        update_log(count($migrationFiles) . " adet migrasyon dosyası bulundu.");

    foreach ($migrationFiles as $version => $file) {
        $sql = file_get_contents($migrationsDir . '/' . $file);
        if ($sql === false) {
            if (function_exists('update_log'))
                update_log("Dosya okunamadı: $file");
            continue;
        }

        try {
            if (function_exists('update_log'))
                update_log("Migrasyon çalıştırılıyor: $file");
            Database::executeSql($sql);
            set_setting('db_version', $version);
            if (function_exists('update_log'))
                update_log("Migrasyon başarılı: $version");

            $performedCount++;
            $appliedVersions[] = $version;
        } catch (Exception $e) {
            if (function_exists('update_log'))
                update_log("MİGRASYON HATASI ($file): " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Migrasyon hatası ($file): " . $e->getMessage(),
                'data' => ['performed' => $performedCount, 'applied' => $appliedVersions]
            ];
        }
    }

    if (function_exists('update_log'))
        update_log("runMigrations() tamamlandı. Uygulanan: $performedCount");

    return [
        'success' => true,
        'message' => "$performedCount adet veritabanı güncellemesi başarıyla uygulandı.",
        'data' => ['performed' => $performedCount, 'applied' => $appliedVersions]
    ];
}

// Eğer doğrudan çağrıldıysa (AJAX)
if (basename($_SERVER['PHP_SELF']) === 'db_migrate.php') {
    $result = runMigrations();
    jsonResponse($result['success'], $result['message'], $result['data']);
}
