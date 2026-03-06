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

// Stok Alarmı (Kritik Seviye Altındakiler)
$lowStockProducts = Database::fetchAll("
    SELECT p.id, p.name, p.code, p.unit, p.image, p.stock_alarm, p.procurement_status, p.procurement_note,
           (SELECT COALESCE(SUM(quantity), 0) FROM tbl_dp_stock_in WHERE product_id = p.id AND is_active = 1) -
           (SELECT COALESCE(SUM(quantity), 0) FROM tbl_dp_stock_out WHERE product_id = p.id) AS current_stock
    FROM tbl_dp_products p
    WHERE p.hidden = 0 AND p.is_active = 1 AND p.stock_alarm > 0
      AND EXISTS (SELECT 1 FROM tbl_dp_stock_in si WHERE si.product_id = p.id)
    HAVING current_stock < p.stock_alarm
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

<!-- İstatistik Kartları -->
<?php if (!empty($lowStockProducts)): ?>
    <div class="row">
        <div class="col-12">
            <div class="alert alert-danger shadow-sm border-0 bg-soft-red text-dark p-0 overflow-hidden mb-4">
                <div class="bg-danger text-white px-4 py-2 d-flex align-items-center justify-content-between">
                    <h5 class="fw-bold mb-0" style="font-size: 1.1rem;">
                        <i class="fas fa-exclamation-triangle me-2"></i> Kritik Stok Uyarıları!
                    </h5>
                    <span class="badge bg-white text-danger fw-bold rounded-pill px-3"><?= count($lowStockProducts) ?>
                        Ürün</span>
                </div>
                <div class="list-group list-group-flush px-4 py-2">
                    <?php foreach ($lowStockProducts as $lp): ?>
                        <div
                            class="list-group-item d-flex align-items-center justify-content-between flex-wrap bg-transparent border-0 px-0 py-2">
                            <div class="me-auto">
                                <span class="fw-bold fs-6 text-dark"><?= e($lp['name']) ?></span>
                            </div>
                            <div class="d-flex align-items-center flex-wrap">
                                <span
                                    class="badge rounded bg-white text-dark border shadow-sm py-1 px-3 fw-normal badge-spacing"
                                    style="font-size: 0.8125rem;">
                                    <i class="fas fa-cubes text-muted me-2"></i> Mevcut Stok: <span
                                        class="text-danger fw-bold ms-1"><?= e(formatQty($lp['current_stock'])) ?></span>
                                </span>
                                <span
                                    class="badge rounded bg-white text-dark border shadow-sm py-1 px-3 fw-normal badge-spacing"
                                    style="font-size: 0.8125rem;">
                                    <i class="fas fa-bell text-muted me-2"></i> Alarm Seviyesi: <span
                                        class="fw-bold ms-1"><?= e(formatQty($lp['stock_alarm'])) ?></span>
                                </span>
                                <a href="<?= BASE_URL ?>/index.php?page=stock_status&search=<?= urlencode($lp['name']) ?>"
                                    class="badge rounded bg-white text-info border shadow-sm py-1 px-3 text-decoration-none hover-shadow fw-bold badge-spacing"
                                    style="font-size: 0.8125rem;" title="Stok Detayı">
                                    <i class="fas fa-search me-2"></i> Detayları Gör
                                </a>
                                <a href="javascript:void(0)"
                                    onclick="openProcurementModal(<?= (int) $lp['id'] ?>, '<?= e(addslashes($lp['name'])) ?>', <?= (int) $lp['procurement_status'] ?>, '<?= e(addslashes($lp['procurement_note'])) ?>', '<?= e($lp['image']) ?>', '<?= e(addslashes($lp['code'])) ?>')"
                                    class="badge rounded bg-white text-primary border shadow-sm py-1 px-3 text-decoration-none hover-shadow fw-bold badge-spacing"
                                    style="font-size: 0.8125rem;">
                                    <i class="fas fa-truck-loading me-2"></i> Tedarik:
                                    <span
                                        class="ms-1"><?= $procurementStatuses[$lp['procurement_status']] ?? 'Bilinmiyor' ?></span>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <style>
        .bg-soft-red {
            background-color: #fff8f8;
            border-left: 5px solid #dc3545 !important;
        }

        .hover-shadow:hover {
            filter: brightness(0.95);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1) !important;
            transform: translateY(-1px);
        }

        .badge {
            transition: all 0.2s ease-in-out;
        }

        .badge-spacing {
            margin: 3px 6px !important;
        }
    </style>
<?php endif; ?>

<!-- Tedarik Süreci Modalı -->
<div class="modal fade" id="procurementModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content premium-modal border-0 shadow-lg">
            <div class="modal-header bg-premium py-3">
                <h5 class="modal-title fw-bold text-white"><i class="fas fa-truck-loading me-3 text-warning"
                        style="margin-right: 10px;"></i>Tedarik Süreci Güncelleme</h5>
                <button type="button" class="btn btn-link text-white p-0 border-0" data-bs-dismiss="modal"><i
                        class="fas fa-times"></i></button>
            </div>
            <div class="modal-body p-0">
                <div class="row g-0">
                    <!-- Sol Taraf: Ürün Kartı -->
                    <div class="col-md-5 bg-light p-4 border-end">
                        <div class="text-center mb-3">
                            <img id="proc_product_img" class="img-fluid rounded shadow-sm mb-3 border bg-white p-1"
                                src="" alt="" style="max-height: 200px; width: 100%; object-fit: contain;">
                            <div id="proc_no_img"
                                class="bg-white d-flex align-items-center justify-content-center rounded shadow-sm mb-3 mx-auto border"
                                style="height: 180px; width: 180px;">
                                <i class="fas fa-box text-muted fa-4x"></i>
                            </div>
                        </div>
                        <div class="px-2">
                            <h4 id="proc_product_name_h" class="fw-bold text-dark text-center mb-1"></h4>
                            <p id="proc_product_code_p" class="text-muted text-center small mb-4 border-bottom pb-2">
                            </p>

                            <div class="modal-section-label">
                                <i class="fas fa-history text-primary me-2"></i> Geçmiş Tedarikçiler
                            </div>
                            <div id="proc_supplier_history" class="small text-muted ps-2 bg-white p-3 rounded border">
                                <div class="spinner-border spinner-border-sm text-primary"></div> Yükleniyor...
                            </div>
                        </div>
                    </div>

                    <!-- Sağ Taraf: Süreç Formu -->
                    <div class="col-md-7 p-4 bg-white">
                        <input type="hidden" id="proc_product_id">

                        <div class="modal-section-label">
                            <i class="fas fa-info-circle text-primary me-2"></i> Süreç Bilgileri
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Güncel Durum</label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-tasks field-icon"></i>
                                <select id="proc_status" class="form-select border-2 shadow-sm ps-5 py-2">
                                    <?php foreach ($procurementStatuses as $val => $label): ?>
                                        <option value="<?= $val ?>"><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-bold">Süreç Notları / Açıklama</label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-comment-dots field-icon" style="top: 15px; transform: none;"></i>
                                <textarea id="proc_note" class="form-control border-2 shadow-sm ps-5" rows="8"
                                    placeholder="Tedarik süreci ile ilgili gelişmeleri buraya not edin..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top py-3 px-4">
                <button type="button" class="btn-modal-cancel me-auto" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn-modal-save" id="btnSaveProcurement">
                    <i class="fas fa-save me-2"></i>Değişiklikleri Kaydet
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function esc(v) { return $('<div/>').text(v || '').html(); }

    function openProcurementModal(id, name, status, note, image, code) {
        $('#proc_product_id').val(id);
        $('#proc_product_name_h').text(name);
        $('#proc_product_code_p').text(code || 'Kodsuz Ürün');
        $('#proc_status').val(status);
        $('#proc_note').val(note);

        // Resim ayarı
        if (image) {
            $('#proc_product_img').attr('src', '<?= BASE_URL ?>/images/UrunResim/' + encodeURIComponent(image)).show();
            $('#proc_no_img').hide();
        } else {
            $('#proc_product_img').hide();
            $('#proc_no_img').show();
        }

        // Tedarikçi geçmişini getir
        $('#proc_supplier_history').html('<div class="spinner-border spinner-border-sm text-primary"></div> Yükleniyor...');
        $.get('<?= BASE_URL ?>/api/products.php', { action: 'get_supplier_history', id: id }, function (r) {
            if (r.success && r.data.length > 0) {
                let sHtml = '<ul class="list-unstyled mb-0">';
                $.each(r.data, function (i, s) {
                    sHtml += `<li class="mb-1"><i class="fas fa-truck text-muted me-2 small"></i>${esc(s.supplier_name)}</li>`;
                });
                sHtml += '</ul>';
                $('#proc_supplier_history').html(sHtml);
            } else {
                $('#proc_supplier_history').html('<span class="text-muted font-italic">Kayıtlı tedarikçi bulunamadı.</span>');
            }
        }, 'json');

        $('#procurementModal').modal('show');
    }

    $(function () {
        $('#btnSaveProcurement').on('click', function () {
            var id = $('#proc_product_id').val();
            var status = $('#proc_status').val();
            var note = $('#proc_note').val();
            var $btn = $(this);

            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Kaydediliyor...');

            $.post('<?= BASE_URL ?>/api/products.php', {
                action: 'update_procurement',
                id: id,
                status: status,
                note: note
            }, function (r) {
                if (r.success) {
                    showSuccess(r.message);
                    $('#procurementModal').modal('hide');
                    setTimeout(function () { location.reload(); }, 800);
                } else {
                    showError(r.message);
                    $btn.prop('disabled', false).html('<i class="fas fa-check-circle me-1"></i>Değişiklikleri Kaydet');
                }
            }, 'json').fail(function () {
                showError('Bağlantı hatası oluştu.');
                $btn.prop('disabled', false).html('<i class="fas fa-check-circle me-1"></i>Değişiklikleri Kaydet');
            });
        });
    });
</script>

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
        border-radius: 16px;
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
        border-radius: 8px;
        padding: 9px 22px;
        font-size: 0.87rem;
        font-weight: 500;
    }

    .btn-modal-save {
        background: linear-gradient(135deg, #1a56db, #0c3daa);
        border: none;
        color: #fff;
        border-radius: 8px;
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