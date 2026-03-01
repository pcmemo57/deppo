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
$currentPage = sanitize($_GET['page'] ?? 'dashboard');

// ─── Erişim Kontrol Matrisi ───────────────────────────────────────────────
$adminOnly = ['settings', 'admin_users'];
$requesterOk = ['dashboard', 'stock_out'];

if ($role === ROLE_ADMIN) {
    // Admin her sayfaya erişebilir
} elseif ($role === ROLE_USER) {
    if (in_array($currentPage, $adminOnly, true)) {
        $currentPage = 'dashboard';
    }
} elseif ($role === ROLE_REQUESTER) {
    if (!in_array($currentPage, $requesterOk, true)) {
        $currentPage = 'stock_out';
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
    'stock_out' => 'Depodan Çıkış',
    'stock_out_orders' => 'Sipariş Bazlı Çıkış Listesi',
    'transfer' => 'Depolar Arası Transfer',
    'transfer_history' => 'Transfer Geçmişi',
    'stock_status' => 'Stok Durumu',
    'product_history' => 'Ürün Hareket Geçmişi',
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