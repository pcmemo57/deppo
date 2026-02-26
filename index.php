<?php
/**
 * Ana Router — index.php?page=sayfa_adı
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
$userAndUp = ['warehouses', 'products', 'customers', 'suppliers', 'requesters',
    'stock_in', 'stock_in_list', 'stock_out', 'transfer', 'transfer_history', 'stock_status', 'dashboard'];
$requesterOk = ['dashboard', 'stock_out'];

if ($role === ROLE_ADMIN) {
// Admin her sayfaya erişebilir
}
elseif ($role === ROLE_USER) {
    if (in_array($currentPage, $adminOnly, true)) {
        $currentPage = 'dashboard';
    }
}
elseif ($role === ROLE_REQUESTER) {
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
    'transfer' => 'Depolar Arası Transfer',
    'transfer_history' => 'Transfer Geçmişi',
    'stock_status' => 'Stok Durumu',
];
$pageTitle = $pageTitles[$currentPage] ?? 'Sayfa';

// ─── Render ────────────────────────────────────────────────────────────────
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

echo '<div class="content-wrapper"><div class="content-header"><div class="container-fluid">';
echo '<div class="row mb-2"><div class="col-sm-6">';
echo '<h1 class="m-0">' . e($pageTitle) . '</h1>';
echo '</div><div class="col-sm-6">';
echo '<ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="' . BASE_URL . '/index.php">Ana Sayfa</a></li>';
echo '<li class="breadcrumb-item active">' . e($pageTitle) . '</li></ol>';
echo '</div></div></div></div>';
echo '<div class="content"><div class="container-fluid">';

include $pageFile;

echo '</div></div>';

include __DIR__ . '/includes/footer.php';