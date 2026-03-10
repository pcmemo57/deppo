<?php
/**
 * Ana Router — index.php?page=sayfa_adı
 * OB: Sayfa script'leri footer'dan SONRA inject edilir (jQuery yükleme sırası)
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';

requireLogin();

$role = currentUser()['role'];
// ─── Erişim Kontrol Matrisi ───────────────────────────────────────────────
$adminOnly = ['settings', 'admin_users'];
$requesterOk = ['stock_out_requests', 'stock_out_orders_for_requesters'];

if ($role === ROLE_ADMIN) {
    // Admin her sayfaya erişebilir
    $currentPage = sanitize($_GET['page'] ?? 'dashboard');
} elseif ($role === ROLE_USER) {
    $currentPage = sanitize($_GET['page'] ?? 'dashboard');
    if (in_array($currentPage, $adminOnly, true)) {
        $currentPage = 'dashboard';
    }
} elseif ($role === ROLE_REQUESTER) {
    // Default page for requester if no page or dashboard is requested
    if (!isset($_GET['page']) || $_GET['page'] === 'dashboard') {
        $currentPage = 'stock_out_orders_for_requesters';
    } else {
        $currentPage = sanitize($_GET['page']);
    }

    if (!in_array($currentPage, $requesterOk, true)) {
        $currentPage = 'stock_out_orders_for_requesters';
    }
}

// ─── Sayfa Dosyası ─────────────────────────────────────────────────────────
$pageFile = __DIR__ . '/pages/' . preg_replace('/[^a-z0-9_]/', '', $currentPage) . '.php';
if (!file_exists($pageFile)) {
    $pageFile = __DIR__ . '/pages/dashboard.php';
    $currentPage = 'dashboard';
}

$pageTitles = [
    'dashboard' => 'Kontrol Paneli',
    'settings' => 'Ayarlar',
    'admin_users' => 'Kullanıcı Yönetimi',
    'requesters' => 'Talep Eden Yönetimi',
    'warehouses' => 'Depo Yönetimi',
    'products' => 'Ürün Yönetimi',
    'customers' => 'Müşteriler',
    'suppliers' => 'Tedarikçiler',
    'stock_in' => 'Depoya Ürün Girişi',
    'stock_in_list' => 'Ürün Giriş Listesi',
    'stock_out' => 'Depodan Çıkış (Satır Bazlı)',
    'stock_out_orders' => 'Depodan Çıkış Listesi',
    'stock_out_pending' => 'Onay Bekleyen Talepler',
    'stock_out_requests' => 'Taleplerim (Satır Bazlı)',
    'stock_out_orders_for_requesters' => 'Taleplerim',
    'transfer' => 'Depolar Arası Transfer',
    'transfer_history' => 'Transfer Geçmişi',
    'stock_status' => 'Stok Durumu',
    'product_history' => 'Ürün Hareket Geçmişi',
    'bulk_stock_update' => 'Toplu Stok Güncelleme',
];
$pageTitle = $pageTitles[$currentPage] ?? 'Sayfa';

// ─── Sayfa içeriğini buffer'a al ────────────────────────────────────────────
ob_start();
include $pageFile;
$pageContent = ob_get_clean();

// <script> bloklarını HTML'den ayır
$scriptPattern = '/<script\b[^>]*>(.*?)<\/script>/is';
preg_match_all($scriptPattern, $pageContent, $scriptMatches);
$pageHtml = preg_replace($scriptPattern, '', $pageContent);
$pageScripts = implode("\n", $scriptMatches[0] ?? []);

// ─── Render ────────────────────────────────────────────────────────────────
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

echo '<div class="content-wrapper">';

echo '<div class="content">
    <div class="container-fluid pt-3">';

echo $pageHtml;

echo '</div></div>'; // This closes container-fluid and content. content-wrapper is closed in footer.php.

include __DIR__ . '/includes/footer.php';

if ($pageScripts) {
    echo "\n" . $pageScripts . "\n";
}