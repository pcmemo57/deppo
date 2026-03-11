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
    // Toplu Stok Güncelleme ayarı kapalıysa USER bu sayfayı göremez
    if ($currentPage === 'bulk_stock_update' && get_setting('show_bulk_stock_update_to_user', '0') !== '1') {
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

// ─── Kritik Stok Uyarıları (Global Header) ──────────────────────────────────
$alerts = getLowStockAlerts();
if ($alerts['total'] > 0) {
    echo '<div class="global-stock-alerts px-3 pt-3">
        <div class="card card-danger card-outline shadow-sm mb-0" style="border-radius: 12px; border-top-width: 3px;">
            <div class="card-header border-0 py-2 px-3 d-flex align-items-center bg-light">
                <h3 class="card-title fw-bold text-danger mb-0 d-flex align-items-center" style="font-size: 1rem;">
                    <i class="fas fa-exclamation-triangle me-2"></i> Kritik Stok Uyarıları!
                    <span class="badge bg-danger text-white fw-bold rounded-pill shadow-xs ms-2" style="font-size: 0.75rem; padding: 4px 10px;">'. $alerts['total'] .' Ürün</span>
                </h3>
            </div>
            <div class="card-body p-0 overflow-hidden">
                <div class="list-group list-group-flush" style="max-height: 400px; overflow-y: auto;">';
    
    // JS escaping helper
    $jsEsc = function($str) {
        return addslashes(preg_replace("/\r|\n/", " ", $str ?? ''));
    };

    $pStatuses = [
        0 => ['text' => 'Beklemede', 'class' => 'bg-secondary'],
        1 => ['text' => 'Teklifler Değerlendiriliyor', 'class' => 'bg-info'],
        2 => ['text' => 'Bütçe Araştırılıyor', 'class' => 'bg-primary'],
        3 => ['text' => 'Sipariş Verildi', 'class' => 'bg-success'],
        4 => ['text' => 'Tedarikçi Araştırılıyor', 'class' => 'bg-warning text-dark'],
        5 => ['text' => 'Tamamlandı', 'class' => 'bg-secondary opacity-50']
    ];

    // Global Alerts
    foreach ($alerts['global'] as $lp) {
        $pStatus = (int)($lp['procurement_status'] ?? 0);
        $pInfo = $pStatuses[$pStatus] ?? $pStatuses[0];
        $pNote = $jsEsc($lp['procurement_note']);
        $pImg = $jsEsc($lp['image']);
        $pCode = $jsEsc($lp['code']);
        $pName = $jsEsc($lp['name']);

        echo '<div class="list-group-item py-2 px-3 hover-bg-light border-0 border-bottom">
                <div class="d-flex align-items-center justify-content-between w-100">
                    
                    <!-- Sol Taraf: Ürün Bilgisi -->
                    <div class="d-flex align-items-center gap-3 flex-grow-1 min-width-0">
                        <div class="fw-bold text-dark text-truncate" style="font-size:0.95rem; max-width:400px" title="'.e($lp['name']).'">'.e($lp['name']).'</div>
                    </div>

                    <!-- Sağ Taraf: Bilgi Rozetleri -->
                    <div class="d-flex align-items-center flex-shrink-0" style="gap: 10px;">
                        <!-- GENEL Etiketi -->
                        <span class="alert-badge bg-danger-light text-danger border-danger-subtle">GENEL ALARM</span>

                        <!-- Mevcut Stok -->
                        <div class="alert-badge bg-white border border-danger-subtle">
                            <span class="text-muted opacity-75">STOK: </span>
                            <span class="text-danger ms-1">'.formatQty($lp['current_stock']).'</span>
                            <span class="text-muted ms-1">'.e($lp['unit']).'</span>
                        </div>
                        
                        <!-- Alarm Limit -->
                        <div class="alert-badge bg-light border">
                            <span class="text-muted opacity-75">LİMİT: </span>
                            <span class="text-secondary ms-1">'.formatQty($lp['stock_alarm']).'</span>
                        </div>

                        <!-- Tedarik Durumu (Clickable) -->
                        <button type="button" class="btn alert-badge '.$pInfo['class'].' border-0 shadow-xs" 
                                style="min-width:160px"
                                onclick="openProcurementModal('.(int)$lp['id'].', \''.$pName.'\', '.$pStatus.', \''.$pNote.'\', \''.$pImg.'\', \''.$pCode.'\')"
                                title="Süreci Güncelle: '.$pInfo['text'].'">
                            <i class="fas fa-truck-loading me-2 opacity-75 mt-0"></i>
                            <span>'.$pInfo['text'].'</span>
                        </button>

                        <!-- Detay Linki -->
                        <a href="'.BASE_URL.'/index.php?page=stock_status&search='.urlencode($lp['name']).'" class="btn alert-badge btn-outline-primary border-2 px-0 shadow-xs" style="width:32px" title="Detay">
                            <i class="fas fa-search m-0"></i>
                        </a>
                    </div>
                </div>
              </div>';
    }
    
    // Warehouse Specific Alerts
    foreach ($alerts['warehouse'] as $lwp) {
        $pStatus = (int)($lwp['procurement_status'] ?? 0);
        $pInfo = $pStatuses[$pStatus] ?? $pStatuses[0];
        $pNote = $jsEsc($lwp['procurement_note']);
        $pImg = $jsEsc($lwp['image']);
        $pCode = $jsEsc($lwp['code']);
        $pName = $jsEsc($lwp['name']);

        echo '<div class="list-group-item py-2 px-3 hover-bg-light border-0 border-bottom">
                <div class="d-flex align-items-center justify-content-between w-100">
                    
                    <!-- Sol Taraf: Ürün Bilgisi -->
                    <div class="d-flex align-items-center gap-3 flex-grow-1 min-width-0">
                        <div class="fw-bold text-dark text-truncate" style="font-size:0.95rem; max-width:400px" title="'.e($lwp['name']).'">'.e($lwp['name']).'</div>
                    </div>

                    <!-- Sağ Taraf: Bilgi Rozetleri -->
                    <div class="d-flex align-items-center flex-shrink-0" style="gap: 10px;">
                        <!-- Depo Bilgisi -->
                        <span class="alert-badge bg-warning-light text-dark border-warning-subtle">
                            <i class="fas fa-warehouse me-2 opacity-75 mt-0"></i>'.e($lwp['warehouse_name']).'
                        </span>

                        <!-- Depo Stoğu -->
                        <div class="alert-badge bg-white border border-danger-subtle">
                            <span class="text-muted opacity-75">DEPO: </span>
                            <span class="text-danger ms-1">'.formatQty($lwp['current_stock']).'</span>
                            <span class="text-muted ms-1">'.e($lwp['unit']).'</span>
                        </div>
                        
                        <!-- Alarm Limit -->
                        <div class="alert-badge bg-light border">
                            <span class="text-muted opacity-75">ALARM: </span>
                            <span class="text-secondary ms-1">'.formatQty($lwp['wh_stock_alarm']).'</span>
                        </div>

                        <!-- Tedarik Durumu (Clickable) -->
                        <button type="button" class="btn alert-badge '.$pInfo['class'].' border-0 shadow-xs" 
                                style="min-width:160px"
                                onclick="openProcurementModal('.(int)$lwp['id'].', \''.$pName.'\', '.$pStatus.', \''.$pNote.'\', \''.$pImg.'\', \''.$pCode.'\')"
                                title="Durum: '.$pInfo['text'].'">
                            <i class="fas fa-truck-loading me-2 opacity-75 mt-0"></i>
                            <span>'.$pInfo['text'].'</span>
                        </button>

                        <!-- Detay Linki -->
                        <a href="'.BASE_URL.'/index.php?page=stock_status&search='.urlencode($lwp['name']).'" class="btn alert-badge btn-outline-primary border-2 px-0 shadow-xs" style="width:32px" title="Detay">
                            <i class="fas fa-search m-0"></i>
                        </a>
                    </div>
                </div>
              </div>';
    }
    
    echo '</div>
            </div>
        </div>
        <style>
            .hover-bg-light:hover { background-color: #f8f9fa !important; }
            .bg-danger-light { background-color: rgba(220, 53, 69, 0.08); }
            .bg-warning-light { background-color: rgba(255, 193, 7, 0.12); }
            .shadow-xs { box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
            .alert-badge { 
                height: 30px; 
                display: inline-flex !important; 
                align-items: center; 
                justify-content: center; 
                padding: 0 10px !important; 
                border-radius: 6px !important; 
                font-size: 0.72rem !important; 
                font-weight: 700 !important; 
                border: 1px solid rgba(0,0,0,0.1);
                white-space: nowrap;
                transition: all 0.2s;
                gap: 5px;
            }
            .alert-badge i { margin: 0 !important; }
        </style>
    </div>';
}


echo '<div class="content">
    <div class="container-fluid pt-3">';

echo $pageHtml;

echo '</div></div>'; // This closes container-fluid and content. content-wrapper is closed in footer.php.

include __DIR__ . '/includes/modals.php';
include __DIR__ . '/includes/footer.php';

if ($pageScripts) {
    echo "\n" . $pageScripts . "\n";
}