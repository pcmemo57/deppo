<?php
/**
 * Verileri Sıfırlama Görevi
 * Admin, Kullanıcı ve Ayar tabloları HARİÇ tüm tabloları boşaltır.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// CLI veya güvenli tetikleme kontrolü (isteğe bağlı, system_tasks üzerinden geliyorsa zaten admin kontrolü yapılıyor)

$exclude_tables = [
    'tbl_dp_admins',
    'tbl_dp_users',
    'tbl_dp_settings'
];

try {
    $db = Database::getInstance();

    // Yabancı anahtar kontrollerini geçici olarak kapat
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");

    $tables_result = $db->query("SHOW TABLES");
    $tables = $tables_result->fetchAll(PDO::FETCH_COLUMN);

    $cleared_count = 0;
    foreach ($tables as $table) {
        if (!in_array($table, $exclude_tables)) {
            $db->exec("TRUNCATE TABLE `$table` ");
            $cleared_count++;
            echo "Temizlendi: $table\n";
        }
    }

    // Yabancı anahtar kontrollerini geri aç
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "\nToplam $cleared_count tablo başarıyla temizlendi.\n";
    exit(0);

} catch (Exception $e) {
    echo "HATA: " . $e->getMessage() . "\n";
    exit(1);
}
