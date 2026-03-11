<?php
/**
 * Dashboard — Kontrol Paneli
 */
// Özet istatistikleri
$warehouseCount = Database::fetchOne('SELECT COUNT(*) AS c FROM tbl_dp_warehouses WHERE hidden=0')['c'] ?? 0;
$productCount = Database::fetchOne('SELECT COUNT(*) AS c FROM tbl_dp_products WHERE hidden=0')['c'] ?? 0;
$customerCount = Database::fetchOne('SELECT COUNT(*) AS c FROM tbl_dp_customers WHERE hidden=0')['c'] ?? 0;
$supplierCount = Database::fetchOne('SELECT COUNT(*) AS c FROM tbl_dp_suppliers WHERE hidden=0')['c'] ?? 0;
$requesterCount = Database::fetchOne('SELECT COUNT(*) AS c FROM tbl_dp_requesters WHERE is_active=1')['c'] ?? 0;
$stockInToday = Database::fetchOne('SELECT COUNT(*) AS c FROM tbl_dp_stock_in WHERE DATE(created_at)=CURDATE()')['c'] ?? 0;
$stockOutToday = Database::fetchOne('SELECT COUNT(*) AS c FROM tbl_dp_stock_out WHERE DATE(created_at)=CURDATE()')['c'] ?? 0;
$transferCount = Database::fetchOne('SELECT COUNT(*) AS c FROM tbl_dp_transfers WHERE DATE(created_at)=CURDATE()')['c'] ?? 0;
$pendingRequestsCount = Database::fetchOne('SELECT COUNT(DISTINCT batch_id) AS c FROM tbl_dp_stock_out WHERE status = 0')['c'] ?? 0;

// Son 5 stok girişi
$recentIn = Database::fetchAll(
    'SELECT si.created_at, p.name AS product, w.name AS warehouse, si.quantity, p.unit
     FROM tbl_dp_stock_in si
     JOIN tbl_dp_products p ON p.id=si.product_id
     JOIN tbl_dp_warehouses w ON w.id=si.warehouse_id
     ORDER BY si.created_at DESC LIMIT 5'
);

// Son 5 stok çıkışı
$recentOut = Database::fetchAll(
    'SELECT so.created_at, p.name AS product, w.name AS warehouse, so.quantity, p.unit
     FROM tbl_dp_stock_out so
     JOIN tbl_dp_products p ON p.id=so.product_id
     JOIN tbl_dp_warehouses w ON w.id=so.warehouse_id
     ORDER BY so.created_at DESC LIMIT 5'
);

// Emanetteki ürünler (iade edilmeyenler)
$entrustedList = Database::fetchAll(
    "SELECT e.*, p.name AS product, p.unit, w.name AS warehouse, r.name AS req_name, r.surname AS req_surname
     FROM tbl_dp_entrusted e
     JOIN tbl_dp_products p ON p.id=e.product_id
     JOIN tbl_dp_warehouses w ON w.id=e.warehouse_id
     LEFT JOIN tbl_dp_requesters r ON r.id=e.requester_id
     WHERE e.remaining_quantity > 0
      ORDER BY e.created_at DESC"
);

// Stok Alarmı (Kritik Seviye Altındakiler) - Global
$lowStockProducts = Database::fetchAll("
    SELECT p.id, p.name, p.code, p.unit, p.image, p.stock_alarm, p.procurement_status, p.procurement_note,
           (COALESCE(si.total_in, 0) - COALESCE(so.total_out, 0)) AS current_stock
    FROM tbl_dp_products p
    LEFT JOIN (
        SELECT product_id, SUM(quantity) as total_in 
        FROM tbl_dp_stock_in 
        WHERE is_active = 1 
        GROUP BY product_id
    ) si ON si.product_id = p.id
    LEFT JOIN (
        SELECT product_id, SUM(quantity) as total_out 
        FROM tbl_dp_stock_out 
        WHERE status = 1
        GROUP BY product_id
    ) so ON so.product_id = p.id
    WHERE p.hidden = 0 AND p.is_active = 1 AND p.stock_alarm > 0
    HAVING current_stock < stock_alarm
    ORDER BY current_stock ASC
");

// Stok Alarmı (Depo Bazlı)
$lowStockWarehouseProducts = Database::fetchAll("
    SELECT p.id, p.name, p.code, p.unit, p.image, p.procurement_status, p.procurement_note,
           w.name AS warehouse_name, pwa.stock_alarm AS wh_stock_alarm,
           (COALESCE(si.total_in, 0) - COALESCE(so.total_out, 0)) AS current_stock
    FROM tbl_dp_product_warehouse_alarms pwa
    JOIN tbl_dp_products p ON p.id = pwa.product_id
    JOIN tbl_dp_warehouses w ON w.id = pwa.warehouse_id
    LEFT JOIN (
        SELECT product_id, warehouse_id, SUM(quantity) as total_in 
        FROM tbl_dp_stock_in 
        WHERE is_active = 1 
        GROUP BY product_id, warehouse_id
    ) si ON si.product_id = pwa.product_id AND si.warehouse_id = pwa.warehouse_id
    LEFT JOIN (
        SELECT product_id, warehouse_id, SUM(quantity) as total_out 
        FROM tbl_dp_stock_out 
        WHERE status = 1
        GROUP BY product_id, warehouse_id
    ) so ON so.product_id = pwa.product_id AND so.warehouse_id = pwa.warehouse_id
    WHERE p.hidden = 0 AND p.is_active = 1 AND pwa.stock_alarm > 0
    HAVING current_stock < wh_stock_alarm
    ORDER BY current_stock ASC
");

$procurementStatuses = [
    0 => 'Beklemede',
    1 => 'Teklifler Değerlendiriliyor',
    2 => 'Bütçe Araştırılıyor',
    3 => 'Sipariş Verildi',
    4 => 'Tedarikçi Araştırılıyor',
    5 => 'Tamamlandı'
];
?>

<style>
    /* ───────────────────────────────────────────
   MODAL TASARIMI (entrusted.php ile senkron)
─────────────────────────────────────────── */
    #addModal .modal-dialog,
    #actionModal .modal-dialog,
    #historyModal .modal-dialog {
        max-width: 860px;
    }

    #addModal .modal-content,
    #actionModal .modal-content,
    #historyModal .modal-content {
        border: none;
        border-radius: var(--radius-xl);
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.18);
    }

    #addModal .modal-header {
        background: linear-gradient(135deg, #1a56db 0%, #0c3daa 100%);
        padding: 20px 28px;
        border-bottom: none;
    }

    #actionModal .modal-header {
        background: linear-gradient(135deg, #17a2b8 0%, #117a8b 100%);
        padding: 20px 28px;
        border-bottom: none;
    }

    #addModal .modal-title,
    #actionModal .modal-title,
    #historyModal .modal-title {
        font-size: 1.05rem;
        font-weight: 600;
    }

    #addModal .modal-body,
    #actionModal .modal-body,
    #historyModal .modal-body {
        padding: 28px 32px 12px;
        background: #f8fafd;
    }

    #addModal .modal-footer,
    #actionModal .modal-footer {
        padding: 16px 32px 20px;
        background: #f8fafd;
        border-top: 1px solid #e4e9f0;
    }

    .modal-section-label {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #6b7a99;
        margin-bottom: 14px;
        padding-bottom: 6px;
        border-bottom: 2px solid #e4e9f0;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .input-icon-wrap {
        position: relative;
    }

    .input-icon-wrap .field-icon {
        position: absolute;
        left: 11px;
        top: 50%;
        transform: translateY(-50%);
        color: #9aa5be;
        font-size: 0.82rem;
        z-index: 5;
        pointer-events: none;
    }

    .btn-modal-cancel {
        background: transparent;
        border: 1.5px solid #c9d3e0;
        color: #4a5568;
        border-radius: var(--radius-md);
        padding: 9px 22px;
        font-size: 0.87rem;
        font-weight: 500;
    }

    .btn-modal-save {
        background: linear-gradient(135deg, #1a56db, #0c3daa);
        border: none;
        color: #fff;
        border-radius: var(--radius-md);
        padding: 9px 32px;
        font-size: 0.87rem;
        font-weight: 600;
        box-shadow: 0 4px 12px rgba(26, 86, 219, 0.3);
    }
</style>

<?php
// Hareket Özetleri
$stockInCount = Database::fetchOne('SELECT COUNT(*) AS c FROM tbl_dp_stock_in')['c'] ?? 0;
$stockOutCount = Database::fetchOne('SELECT COUNT(DISTINCT batch_id) AS c FROM tbl_dp_stock_out')['c'] ?? 0;
$transferCount = Database::fetchOne('SELECT COUNT(*) AS c FROM tbl_dp_transfers')['c'] ?? 0;
$entrustedCount = Database::fetchOne('SELECT COUNT(*) AS c FROM tbl_dp_entrusted WHERE remaining_quantity > 0')['c'] ?? 0;
?>

<!-- Bilgi Rozetleri -->
<div class="row mt-1 mb-3">
    <div class="col-lg-3 col-6">
        <a href="<?= BASE_URL ?>/index.php?page=customers" class="text-decoration-none">
            <span
                class="badge rounded shadow-sm py-2 px-3 fw-normal bg-white text-dark info-badge-blue-border info-badge-hover d-block w-100 text-start">
                <i class="fas fa-users text-primary me-2"></i> Müşteri: <span
                    class="fw-bold ms-1 text-primary float-end"><?= e(formatQty($customerCount)) ?></span>
            </span>
        </a>
    </div>
    <div class="col-lg-3 col-6">
        <a href="<?= BASE_URL ?>/index.php?page=suppliers" class="text-decoration-none">
            <span
                class="badge rounded shadow-sm py-2 px-3 fw-normal bg-white text-dark info-badge-blue-border info-badge-hover d-block w-100 text-start">
                <i class="fas fa-truck-moving text-success me-2"></i> Tedarikçi: <span
                    class="fw-bold ms-1 text-success float-end"><?= e(formatQty($supplierCount)) ?></span>
            </span>
        </a>
    </div>
    <div class="col-lg-3 col-6">
        <a href="<?= BASE_URL ?>/index.php?page=requesters" class="text-decoration-none">
            <span
                class="badge rounded shadow-sm py-2 px-3 fw-normal bg-white text-dark info-badge-blue-border info-badge-hover d-block w-100 text-start">
                <i class="fas fa-user-friends text-info me-2"></i> Personel: <span
                    class="fw-bold ms-1 text-info float-end"><?= e(formatQty($requesterCount)) ?></span>
            </span>
        </a>
    </div>
    <div class="col-lg-3 col-6">
        <a href="<?= BASE_URL ?>/index.php?page=products" class="text-decoration-none">
            <span
                class="badge rounded shadow-sm py-2 px-3 fw-normal bg-white text-dark info-badge-blue-border info-badge-hover d-block w-100 text-start">
                <i class="fas fa-boxes text-danger me-2"></i> Ürün: <span
                    class="fw-bold ms-1 text-danger float-end"><?= e(formatQty($productCount)) ?></span>
            </span>
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-3 col-6">
        <a href="<?= BASE_URL ?>/index.php?page=stock_out_orders" class="small-box bg-success shadow-sm">
            <div class="inner">
                <h3><?= e(formatQty($stockOutCount)) ?></h3>
                <p>Depodan Çıkış</p>
            </div>
            <div class="icon"><i class="fas fa-sign-out-alt"></i></div>
        </a>
    </div>
    <div class="col-lg-3 col-6">
        <a href="<?= BASE_URL ?>/index.php?page=entrusted" class="small-box bg-danger shadow-sm">
            <div class="inner">
                <h3><?= e(formatQty($entrustedCount)) ?></h3>
                <p>Emanetler</p>
            </div>
            <div class="icon"><i class="fas fa-hand-holding"></i></div>
        </a>
    </div>
    <div class="col-lg-3 col-6">
        <a href="<?= BASE_URL ?>/index.php?page=stock_in_list" class="small-box bg-info shadow-sm">
            <div class="inner">
                <h3><?= e(formatQty($stockInCount)) ?></h3>
                <p>Stok Girişleri</p>
            </div>
            <div class="icon"><i class="fas fa-sign-in-alt"></i></div>
        </a>
    </div>
    <div class="col-lg-3 col-6">
        <a href="<?= BASE_URL ?>/index.php?page=stock_out_pending" class="small-box bg-warning shadow-sm">
            <div class="inner">
                <h3><?= e(formatQty($pendingRequestsCount)) ?></h3>
                <p>Onay Bekleyen Talepler</p>
            </div>
            <div class="icon"><i class="fas fa-clock"></i></div>
        </a>
    </div>
</div>

<style>
    .info-badge-blue-border {
        border: 1px solid #c7d9f5 !important;
    }

    .info-badge-hover {
        transition: all 0.2s ease-in-out;
        cursor: pointer;
    }

    .info-badge-hover:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08) !important;
        border-color: #3b82f6 !important;
    }
</style>
<!-- 
<div class="row">
    <div class="col-lg-3 col-6">
        <a href="<?= BASE_URL ?>/index.php?page=warehouses" class="small-box bg-info">
            <div class="inner">
                <h3><?= e(formatQty($warehouseCount)) ?></h3>
                <p>Aktif Depo</p>
            </div>
            <div class="icon"><i class="fas fa-warehouse"></i></div>
        </a>
    </div>
    <div class="col-lg-3 col-6">
        <a href="<?= BASE_URL ?>/index.php?page=products" class="small-box bg-success">
            <div class="inner">
                <h3><?= e(formatQty($productCount)) ?></h3>
                <p>Tanımlı Ürün</p>
            </div>
            <div class="icon"><i class="fas fa-boxes"></i></div>
        </a>
    </div>
    <div class="col-lg-3 col-6">
        <a href="<?= BASE_URL ?>/index.php?page=customers" class="small-box bg-warning">
            <div class="inner">
                <h3><?= e(formatQty($customerCount)) ?></h3>
                <p>Müşteri</p>
            </div>
            <div class="icon"><i class="fas fa-users"></i></div>
        </a>
    </div>
    <div class="col-lg-3 col-6">
        <a href="<?= BASE_URL ?>/index.php?page=suppliers" class="small-box bg-danger">
            <div class="inner">
                <h3><?= e(formatQty($supplierCount)) ?></h3>
                <p>Tedarikçi</p>
            </div>
            <div class="icon"><i class="fas fa-truck"></i></div>
        </a>
    </div>
</div> -->

<!-- Bugünkü Hareketler -->
<!-- <div class="row">
    <div class="col-md-4">
        <div class="info-box">
            <span class="info-box-icon bg-success elevation-1"><i class="fas fa-arrow-down"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Bugün Giriş</span>
                <span class="info-box-number">
                    <?= e(formatQty($stockInToday)) ?> kayıt
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box">
            <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-arrow-up"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Bugün Çıkış</span>
                <span class="info-box-number">
                    <?= e(formatQty($stockOutToday)) ?> kayıt
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box">
            <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-exchange-alt"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Bugün Transfer</span>
                <span class="info-box-number">
                    <?= e(formatQty($transferCount)) ?> işlem
                </span>
            </div>
        </div>
    </div>
</div> -->

<?php if (!empty($entrustedList)): ?>
    <!-- Emanetteki Ürünler (Tam Genişlik) -->
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title text-bold"><i class="fas fa-hand-holding me-2"></i>Emanetteki Ürünler
                        (Bekleyenler)</h3>
                    <div class="card-tools">
                        <a href="<?= BASE_URL ?>/index.php?page=entrusted" class="btn btn-sm btn-primary shadow-sm">
                            Tümünü Yönet <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body p-0 table-responsive text-nowrap">
                    <table class="table table-sm table-hover table-striped mb-0">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-3">Ürün</th>
                                <th>Emanet Alan</th>
                                <th>Depo</th>
                                <th class="num-align">Kalan Miktar</th>
                                <th class="num-align">İade Tarihi</th>
                                <th class="num-align">Kayıt Tarihi</th>
                                <th class="text-center pe-3">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($entrustedList as $e): ?>
                                <tr>
                                    <td class="ps-3"><strong><?= e($e['product']) ?></strong></td>
                                    <td><?= e($e['req_name'] . ' ' . $e['req_surname']) ?></td>
                                    <td><small class="text-muted"><?= e($e['warehouse']) ?></small></td>
                                    <td class="num-align">
                                        <span
                                            class="badge bg-warning text-dark px-2"><?= e(formatQty($e['remaining_quantity'])) ?></span>
                                        <small class="text-muted"><?= e($e['unit']) ?></small>
                                    </td>
                                    <td class="num-align">
                                        <?php if ($e['expected_return_at']): ?>
                                            <small
                                                class="<?= (strtotime($e['expected_return_at']) < time()) ? 'text-danger fw-bold' : 'text-muted' ?>">
                                                <i class="far fa-calendar-alt me-1"
                                                    style="margin-right: 5px;"></i><?= e(date('d.m.Y', strtotime($e['expected_return_at']))) ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="num-align">
                                        <small><?= e(date('d.m.Y H:i', strtotime($e['created_at']))) ?></small>
                                    </td>
                                    <td class="text-center pe-3">
                                        <?php
                                        $actionData = [
                                            'id' => $e['id'],
                                            'product_name' => $e['product'],
                                            'remaining_quantity' => $e['remaining_quantity'],
                                            'unit' => $e['unit']
                                        ];
                                        ?>
                                        <button class="btn btn-xs btn-info text-white shadow-sm"
                                            onclick="openActionModal(<?= e(json_encode($actionData)) ?>)" title="İşlem Yap">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($entrustedList)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted p-4">Sistemde aktif emanet kaydı bulunamadı.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="modal fade" id="actionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-white fw-bold"><i class="fas fa-exchange-alt me-2 opacity-75"></i>Emanet
                    İşlemi</h5>
                <button type="button" class="btn btn-link text-white p-0 border-0" data-bs-dismiss="modal"><i
                        class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <div class="modal-section-label">
                    <i class="fas fa-box"></i> Ürün Bilgisi
                </div>
                <h6 id="actionProduct" class="fw-bold text-primary mb-2"></h6>
                <div class="alert alert-light border small py-2 mb-4 bg-white shadow-sm">
                    Emanette Kalan: <b id="actionRemaining" class="text-danger">0</b>
                </div>

                <form id="formAction">
                    <input type="hidden" id="actionId">

                    <div class="modal-section-label">
                        <i class="fas fa-cog"></i> İşlem Detayları
                    </div>
                    <div class="mb-3">
                        <label class="form-label d-block text-center mb-3">İşlem Türü</label>
                        <div class="d-flex justify-content-center">
                            <div class="status-btn-group shadow-sm">
                                <button type="button" class="status-btn-item" id="set_return"
                                    onclick="setActionType('return')">
                                    <i class="fas fa-undo me-1"></i> İade Al
                                </button>
                                <button type="button" class="status-btn-item" id="set_sale"
                                    onclick="setActionType('sale')">
                                    <i class="fas fa-shopping-cart me-1"></i> Müşteriye Çık
                                </button>
                            </div>
                        </div>
                        <input type="hidden" name="type" id="actionTypeValue" value="return">
                    </div>

                    <div class="mb-3" id="customerDiv" style="display:none">
                        <label class="form-label">Müşteri Seçin <span class="text-danger">*</span></label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-building field-icon"></i>
                            <select id="actionCustomerId" class="form-select"></select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">İşlem Miktarı <span class="text-danger">*</span></label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-sort-numeric-up field-icon"></i>
                            <input type="number" id="actionQty" class="form-control" step="any" required>
                        </div>
                    </div>

                    <div class="modal-section-label mt-4">
                        <i class="fas fa-comment-dots"></i> Not
                    </div>
                    <div class="mb-3">
                        <textarea id="actionNote" class="form-control" rows="2" placeholder="İşlem notu..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-modal-cancel me-auto" data-bs-dismiss="modal">Kapat</button>
                <button type="button" class="btn btn-info text-white shadow-sm fw-bold px-4" id="btnSubmitAction">İşlemi
                    Onayla</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Emanet İşlemleri Scripti
    function openActionModal(d) {
        $('#actionId').val(d.id);
        $('#actionProduct').text(d.product_name);
        var rem = parseFloat(d.remaining_quantity) || 0;
        $('#actionRemaining').text(formatQty(rem) + ' ' + (d.unit || ''));
        $('#actionQty').val(rem).attr('max', rem);
        $('#actionNote').val('');
        setActionType('return');
        $('#actionCustomerId').val(null).trigger('change');
        $('#actionModal').modal('show');
    }

    function setActionType(type) {
        $('#actionTypeValue').val(type);
        $('.status-btn-item').removeClass('active-state inactive-state');
        if (type === 'return') {
            $('#set_return').addClass('active-state');
            $('#customerDiv').hide();
        } else {
            $('#set_sale').addClass('inactive-state');
            $('#customerDiv').show();
        }
    }

    $(document).ready(function () {
        $('#actionCustomerId').select2({
            theme: 'bootstrap-5', placeholder: '— Müşteri Seçin —', width: '100%', dropdownParent: $('#actionModal'),
            ajax: { url: '<?= BASE_URL ?>/api/customers.php', data: function (p) { return { action: 'active_list', search: p.term || '' }; }, processResults: function (d) { return { results: $.map(d.data, function (u) { return { id: u.id, text: u.name }; }) }; }, delay: 300 }
        });

        $('#btnSubmitAction').on('click', function () {
            var qty = parseFloat($('#actionQty').val());
            if (!qty || qty <= 0) { showError('Geçerli bir miktar girin.'); return; }

            var btn = $(this);
            var btnHtml = btn.html();
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> E-posta gönderiliyor...');

            Swal.fire({
                title: 'İşlem yapılıyor...',
                text: 'Lütfen bekleyiniz, e-posta bildirimi gönderiliyor.',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            $.post('<?= BASE_URL ?>/api/entrusted.php', {
                action: 'process_action',
                id: $('#actionId').val(),
                type: $('#actionTypeValue').val(),
                quantity: qty,
                customer_id: $('#actionCustomerId').val(),
                note: $('#actionNote').val()
            }, function (r) {
                if (r.success) {
                    showSuccess(r.message);
                    $('#actionModal').modal('hide');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showError(r.message);
                }
            }, 'json').always(function () {
                btn.prop('disabled', false).html(btnHtml);
            });
        });
    });
</script>

<div class="row">
    <!-- Son Stok Çıkışları -->
    <div class="col-md-6">
        <div class="card card-outline card-danger">
            <div class="card-header">
                <h3 class="card-title text-bold"><i class="fas fa-sign-out-alt me-2"></i>Son Stok Çıkışları</h3>
                <div class="card-tools">
                    <a href="<?= BASE_URL ?>/index.php?page=stock_out" class="btn btn-sm btn-danger shadow-sm">
                        Hepsini Gör <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Ürün</th>
                            <th class="num-align">Adet</th>
                            <th class="num-align">Tarih</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOut as $r): ?>
                            <tr>
                                <td>
                                    <strong><?= e($r['product']) ?></strong><br>
                                    <small class="text-muted"><i class="fas fa-warehouse me-1"></i>
                                        <?= e($r['warehouse']) ?></small>
                                </td>
                                <td class="num-align">
                                    <span class="text-danger fw-bold"><?= e(formatQty($r['quantity'])) ?></span>
                                    <small class="text-muted"><?= e($r['unit']) ?></small>
                                </td>
                                <td class="num-align">
                                    <small><?= e(date('d.m.Y H:i', strtotime($r['created_at']))) ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentOut)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted p-3">Henüz çıkış yok</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Son Stok Girişleri -->
    <div class="col-md-6">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title text-bold"><i class="fas fa-sign-in-alt me-2"></i>Son Stok Girişleri</h3>
                <div class="card-tools">
                    <a href="<?= BASE_URL ?>/index.php?page=stock_in_list" class="btn btn-sm btn-success shadow-sm">
                        Hepsini Gör <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Ürün</th>
                            <th class="num-align">Adet</th>
                            <th class="num-align">Tarih</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentIn as $r): ?>
                            <tr>
                                <td>
                                    <strong><?= e($r['product']) ?></strong><br>
                                    <small class="text-muted"><i class="fas fa-warehouse me-1"></i>
                                        <?= e($r['warehouse']) ?></small>
                                </td>
                                <td class="num-align">
                                    <span class="text-success fw-bold"><?= e(formatQty($r['quantity'])) ?></span>
                                    <small class="text-muted"><?= e($r['unit']) ?></small>
                                </td>
                                <td class="num-align">
                                    <small><?= e(date('d.m.Y H:i', strtotime($r['created_at']))) ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentIn)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted p-3">Henüz giriş yok</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>



</div>