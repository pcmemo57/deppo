<?php
/**
 * Seçmeli Veri Silme Görevi
 * Kullanıcının seçtiği kategorilere göre ilgili tabloları boşaltır.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Kategoriler ve ilgili tabloları
$category_map = [
    'stock_in' => ['tbl_dp_stock_in'],
    'stock_out' => ['tbl_dp_stock_out'],
    'entrusted' => ['tbl_dp_entrusted', 'tbl_dp_entrusted_actions'],
    'transfers' => ['tbl_dp_transfers', 'tbl_dp_transfer_items'],
    'products' => ['tbl_dp_products'],
    'customers' => ['tbl_dp_customers'],
    'suppliers' => ['tbl_dp_suppliers'],
    'requesters' => ['tbl_dp_requesters'],
    'warehouses' => ['tbl_dp_warehouses']
];

// JSON formatında kategorileri al
$selected_categories = json_decode($argv[1] ?? '[]', true);

if (empty($selected_categories)) {
    echo "Hata: Hiçbir kategori seçilmedi.\n";
    exit(1);
}

try {
    $db = Database::getInstance();

    // Yabancı anahtar kontrollerini geçici olarak kapat
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");

    $cleared_tables = [];
    foreach ($selected_categories as $cat) {
        if (isset($category_map[$cat])) {
            foreach ($category_map[$cat] as $table) {
                $db->exec("TRUNCATE TABLE `$table` ");
                $cleared_tables[] = $table;
            }
        }
    }

    // Yabancı anahtar kontrollerini geri aç
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");

    if (empty($cleared_tables)) {
        echo "Hiçbir tablo temizlenmedi.\n";
    } else {
        echo "Temizlenen tablolar:\n" . implode("\n", array_unique($cleared_tables)) . "\n";
        echo "\nToplam " . count(array_unique($cleared_tables)) . " tablo başarıyla temizlendi.\n";
    }
    exit(0);

} catch (Exception $e) {
    echo "HATA: " . $e->getMessage() . "\n";
    exit(1);
}
