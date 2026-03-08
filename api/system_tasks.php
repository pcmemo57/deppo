<?php
/**
 * Sistem Görevleri Tetikleme API'si
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/functions.php';

// Sadece yönetici erişebilir
if (currentUser()['role'] !== ROLE_ADMIN) {
    jsonResponse(false, 'Bu işlem için yetkiniz yok.');
}

$task = sanitize($_POST['task'] ?? $_GET['task'] ?? '');

if (!$task) {
    jsonResponse(false, 'Görev belirtilmedi.');
}

$allowedTasks = ['update_currency', 'entrusted_reminder', 'clear_data', 'clear_selective_data'];

if (!in_array($task, $allowedTasks)) {
    jsonResponse(false, 'Geçersiz görev.');
}

// Log admin action for critical tasks
if ($task === 'clear_data' || $task === 'clear_selective_data') {
    $adminId = currentUser()['id'] ?? 'UNKNOWN';
    error_log("CRITICAL: Admin ID " . $adminId . " triggered $task task at " . date('Y-m-d H:i:s'));
}

/**
 * 1. DÖVİZ GÜNCELLEME (update_currency)
 */
if ($task === 'update_currency') {
    // TCMB XML
    $xml = @simplexml_load_file('https://www.tcmb.gov.tr/kurlar/today.xml');
    if (!$xml)
        $xml = @simplexml_load_file('https://www.tcmb.gov.tr/kurlar/' . date('Ym') . '/' . date('dmy') . '.xml');

    if (!$xml)
        jsonResponse(false, 'TCMB bağlantısı sağlanamadı.');

    $usd = null;
    $eur = null;
    foreach ($xml->Currency as $cur) {
        $code = (string) $cur->attributes()->CurrencyCode;
        if ($code === 'USD')
            $usd = (float) $cur->ForexSelling;
        if ($code === 'EUR')
            $eur = (float) $cur->ForexSelling;
    }
    if (!$usd || !$eur)
        jsonResponse(false, 'Kur verisi alınamadı.');

    set_setting('usd_rate', (string) $usd);
    set_setting('eur_rate', (string) $eur);
    set_setting('currency_updated', date('Y-m-d H:i:s'));

    jsonResponse(true, 'Kurlar başarıyla güncellendi.');
}

/**
 * 2. EMANET HATIRLATMA (entrusted_reminder)
 */
if ($task === 'entrusted_reminder') {
    jsonResponse(false, 'Emanet hatırlatma için doğrudan cron kullanılması önerilir.');
}

/**
 * 3. SEÇMELİ VERİ SİLME (clear_selective_data)
 */
if ($task === 'clear_selective_data' || $task === 'clear_data') {
    $category_map = [
        'stock_in' => ['tbl_dp_stock_in'],
        'stock_out' => ['tbl_dp_stock_out'],
        'entrusted' => ['tbl_dp_entrusted', 'tbl_dp_entrusted_actions'],
        'transfers' => ['tbl_dp_transfers', 'tbl_dp_transfer_items'],
        'products' => ['tbl_dp_products'],
        'customers' => ['tbl_dp_customers'],
        'suppliers' => ['tbl_dp_suppliers'],
        'requesters' => ['tbl_dp_requesters'],
        'warehouses' => ['tbl_dp_warehouses'],
        'inventory' => ['inventory_sessions', 'inventory_items'],
        'packing_lists' => ['tbl_dp_packing_lists', 'tbl_dp_packing_list_parcels', 'tbl_dp_packing_list_items']
    ];

    $selected_categories = [];
    if ($task === 'clear_data') {
        $selected_categories = array_keys($category_map);
    } else {
        $categories_raw = $_POST['categories'] ?? '[]';
        $selected_categories = json_decode($categories_raw, true);
    }

    if (empty($selected_categories) || !is_array($selected_categories)) {
        jsonResponse(false, 'Hata: Silinmek üzere hiçbir kategori seçilmedi.');
    }

    try {
        $db = Database::getInstance();
        $db->exec("SET FOREIGN_KEY_CHECKS = 0");

        $cleared_tables = [];
        foreach ($selected_categories as $cat) {
            if (isset($category_map[$cat])) {
                foreach ($category_map[$cat] as $table) {
                    $db->exec("TRUNCATE TABLE `$table`");
                    $cleared_tables[] = $table;
                }
            }
        }

        $db->exec("SET FOREIGN_KEY_CHECKS = 1");

        $clearedCount = count(array_unique($cleared_tables));
        jsonResponse(true, "Başarılı! Toplam $clearedCount tablo temizlendi.", [
            'cleared_tables' => array_unique($cleared_tables)
        ]);

    } catch (Exception $e) {
        jsonResponse(false, 'Veritabanı silme hatası: ' . $e->getMessage());
    }
}

jsonResponse(false, 'Bilinmeyen bir görev.');
