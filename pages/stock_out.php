<?php
/**
 * Depodan Çıkış Listesi & Yönetimi
 */
requireRole(ROLE_ADMIN, ROLE_USER);
$warehouses = Database::fetchAll("
    SELECT w.id, w.name, 
    (SELECT COUNT(*) FROM inventory_sessions WHERE warehouse_id = w.id AND status = 'open') > 0 as is_inventory_open 
    FROM tbl_dp_warehouses w 
    WHERE w.hidden=0 AND w.is_active=1 
    ORDER BY w.name
");
?>

<style>
    /* ───────────────────────────────────────────
     KART HEADER — araç çubuğu hizalama
  ─────────────────────────────────────────── */
    .card-header .card-tools {
        align-items: center;
        gap: 10px;
    }

    .card-header .card-title {
        font-size: 1.75rem !important;
        display: flex;
        align-items: center;
    }

    .card-header .card-title i {
        font-size: 1.5rem;
        margin-right: 12px;
    }

    /* Tüm header araçlarını aynı yüksekliğe sabitle */
    .card-header .card-tools .form-select-sm,
    .card-header .card-tools .input-group-sm .form-control,
    .card-header .card-tools .input-group-sm .input-group-text,
    .card-header .card-tools .btn-sm {
        height: 32px;
        line-height: 1 !important;
        font-size: 0.8125rem;
        padding-top: 0;
        padding-bottom: 0;
        box-sizing: border-box;
        display: flex;
        align-items: center;
    }

    .card-header .card-tools .input-group-sm .input-group-text {
        background: #f4f6f9;
        border-color: #ced4da;
        color: #6c757d;
        padding: 0 10px;
        justify-content: center;
    }

    /* Sayfa tepesindeki boşluk */
    .stock-out-row {}

    /* ───────────────────────────────────────────
     MODAL GENEL (Premium Style)
  ─────────────────────────────────────────── */
    #addModal .modal-content,
    #viewModal .modal-content {
        border: none;
        border-radius: var(--radius-xl);
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.18);
    }

    @media (min-width: 1200px) {
        .modal-xl-custom {
            max-width: 1200px;
        }
    }

    #addModal .modal-header {
        background: linear-gradient(135deg, #1a56db 0%, #0c3daa 100%);
        padding: 20px 28px;
        border-bottom: none;
    }

    #viewModal .modal-header {
        background: linear-gradient(135deg, #6b7280 0%, #374151 100%);
        padding: 20px 28px;
        border-bottom: none;
    }

    #addModal .modal-title,
    #viewModal .modal-title {
        font-size: 1.05rem;
        font-weight: 600;
        letter-spacing: 0.01em;
        color: #fff;
    }

    #addModal .modal-body,
    #viewModal .modal-body {
        padding: 28px 32px 12px;
        background: #f8fafd;
    }

    #addModal .modal-footer,
    #viewModal .modal-footer {
        padding: 16px 32px 20px;
        background: #f8fafd;
        border-top: 1px solid #e4e9f0;
    }

    /* Bölüm başlıkları */
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

    /* Butonlar */
    .btn-modal-cancel {
        background: transparent;
        border: 1.5px solid #c9d3e0;
        color: #4a5568;
        border-radius: var(--radius-md);
        padding: 9px 22px;
        font-size: 0.87rem;
        font-weight: 500;
        transition: all 0.2s;
    }

    .btn-modal-cancel:hover {
        background: #f0f4f9;
        border-color: #a0aec0;
        color: #1f2937;
    }

    .btn-modal-save {
        background: linear-gradient(135deg, #1a56db, #0c3daa);
        border: none;
        color: #fff;
        border-radius: var(--radius-md);
        padding: 9px 32px;
        font-size: 0.87rem;
        font-weight: 600;
        letter-spacing: 0.01em;
        box-shadow: 0 4px 12px rgba(26, 86, 219, 0.3);
        transition: all 0.2s;
    }

    .btn-modal-save:hover {
        background: linear-gradient(135deg, #1d4ed8, #0a35a0);
        box-shadow: 0 6px 16px rgba(26, 86, 219, 0.38);
        transform: translateY(-1px);
        color: #fff;
    }

    /* Card footer flex düzeni */
    .card-footer.clearfix {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .card-footer .float-start,
    .card-footer .float-end {
        float: none !important;
    }

    /* Durum Rozetleri */
    .badge-pending { background-color: #fce7f3; color: #9d174d; border: 1px solid #f9a8d4; }
    .badge-approved { background-color: #dcfce7; color: #166534; border: 1px solid #86efac; }
    .badge-rejected { background-color: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
    .badge-status { padding: 4px 8px; border-radius: var(--radius-md); font-weight: 600; font-size: 0.75rem; text-transform: uppercase; }
</style>

<div class="row stock-out-row">
    <div class="col-12">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title text-bold"><i class="fas fa-sign-out-alt me-2"></i> Depodan Çıkış Listesi</h3>
                <div class="card-tools d-flex">
                    <select id="perPage" class="form-select form-select-sm me-2" style="width:auto">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <div class="input-group input-group-sm me-2" style="width: 150px;">
                        <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                        <input type="date" id="startDate" class="form-control" title="Başlangıç Tarihi">
                    </div>
                    <div class="input-group input-group-sm me-2" style="width: 150px;">
                        <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                        <input type="date" id="endDate" class="form-control" title="Bitiş Tarihi">
                    </div>
                    <div class="input-group input-group-sm me-2" style="width: 140px;">
                        <span class="input-group-text"><i class="fas fa-filter"></i></span>
                        <select id="statusFilter" class="form-select">
                            <option value="">— Durum —</option>
                            <option value="0">Beklemede</option>
                            <option value="1">Onaylandı</option>
                            <option value="2">Reddedildi</option>
                        </select>
                    </div>
                    <div class="input-group input-group-sm me-2" style="width: 180px;">
                        <input type="text" id="searchBox" class="form-control" placeholder="Ara...">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                    </div>
                    <button id="btnExport" class="btn btn-success btn-sm px-3 shadow-sm me-2">
                        <i class="fas fa-file-excel me-1"></i> Excel
                    </button>
                    <button class="btn btn-primary btn-sm px-3 shadow-sm me-2" onclick="openAddModal()">
                        <i class="fas fa-plus me-1"></i> Yeni Çıkış
                    </button>
                    <a href="?page=stock_out_orders" class="btn btn-secondary btn-sm px-3 shadow-sm">
                        <i class="fas fa-list-ul me-1"></i> Sipariş Bazlı Liste
                    </a>
                </div>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover table-striped m-0 table-valign-middle">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th>Ürün</th>
                            <th>Depo</th>
                            <th>Alan / Müşteri</th>
                            <th class="num-align">Miktar</th>
                            <th class="num-align">Toplam (<?= getCurrencySymbol() ?>)</th>
                            <th style="width:100px">İşlemi Yapan</th>
                            <th style="width:100px">Durum</th>
                            <th style="width:100px" class="num-align">Tarih</th>
                            <th style="width:80px" class="text-center pe-3">Detay</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody"></tbody>
                </table>
            </div>
            <div class="card-footer clearfix">
                <div class="float-start">
                    <span id="totalCount" class="text-muted small"></span>
                </div>
                <div id="pagination" class="float-end m-0"></div>
            </div>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-xl-custom">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-sign-out-alt me-2"></i> Yeni Stok Çıkışı</h5>
                <button type="button" class="btn btn-link text-white p-0 border-0" data-bs-dismiss="modal"><i
                        class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form id="formStockOut">
                    <input type="hidden" name="id" id="editId" value="">
                    <input type="hidden" name="batch_id" id="editBatchId" value="">
                    <!-- Konum Bilgileri -->
                    <div class="modal-section-label">
                        <i class="fas fa-map-marker-alt"></i> Konum &amp; Muhatap Bilgileri
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Depo <span class="text-danger">*</span></label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-warehouse field-icon"></i>
                                <select name="warehouse_id" id="warehouseSelect" class="form-select" required>
                                    <option value="">— Seçiniz —</option>
                                    <?php foreach ($warehouses as $w): ?>
                                        <option value="<?= e($w['id']) ?>" 
                                            <?= count($warehouses) === 1 && !$w['is_inventory_open'] ? 'selected' : '' ?>
                                            <?= $w['is_inventory_open'] ? 'disabled style="color:red"' : '' ?>>
                                            <?= e($w['name']) ?><?= $w['is_inventory_open'] ? ' (SAYIM DEVAM EDİYOR)' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Talep Eden</label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-user field-icon"></i>
                                <select name="requester_id" id="requesterSelect" class="form-select" disabled></select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Müşteri <span class="text-danger">*</span></label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-handshake field-icon"></i>
                                <select name="customer_id" id="customerSelect" class="form-select" disabled></select>
                            </div>
                        </div>
                    </div>

                    <!-- Ürün Bilgileri -->
                    <div class="modal-section-label mt-4">
                        <i class="fas fa-boxes"></i> Ürün &amp; Miktar
                    </div>
                    <div class="row g-3 mb-3 align-items-end">
                        <div class="col-md-7">
                            <label class="form-label">Ürün Seçin <span class="text-danger">*</span></label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-box field-icon"></i>
                                <select id="productAdd" class="form-select" disabled></select>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Miktar</label>
                            <div class="input-group">
                                <input type="number" id="qtyInput" class="form-control text-end" placeholder="0.00"
                                    step="any" style="border-top-right-radius: 0 !important; border-bottom-right-radius: 0 !important; border-right: 0 !important;" disabled>
                                <button type="button" class="btn btn-primary px-3" id="btnAddLine" style="border-top-left-radius: 0 !important; border-bottom-left-radius: 0 !important;">
                                    <i class="fas fa-plus me-1"></i> Ekle
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Line Items Table -->
                    <div id="lineContainer" style="display:none" class="mb-4">
                        <div class="table-responsive" style="max-height:200px">
                            <table class="table table-sm table-bordered">
                                <thead class="bg-light small">
                                    <tr>
                                        <th>Ürün</th>
                                        <th class="num-align" style="width:150px">Miktar</th>
                                        <th class="num-align" style="width:150px">Birim
                                            (<?= e(get_setting('base_currency', 'EUR')) ?>)</th>
                                        <th class="num-align" style="width:150px">Toplam</th>
                                        <th style="width:40px"></th>
                                    </tr>
                                </thead>
                                <tbody id="lineBody"></tbody>
                                <tfoot id="lineFoot">
                                    <tr class="bg-light fw-bold">
                                        <td colspan="3" class="num-align">GENEL TOPLAM:</td>
                                        <td id="totalSumLabel" class="num-align">0.00
                                            <?= e(get_setting('base_currency', 'EUR')) ?>
                                        </td>
                                        <td></td>
                                    </tr>
                                    <tr class="fw-bold">
                                        <td colspan="3" class="num-align text-primary">TL TOPLAM:</td>
                                        <td id="totalSumTLLabel" class="num-align text-primary">0.00 TL</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Not / Açıklama</label>
                        <textarea name="note" class="form-control" rows="2"
                            placeholder="İşlem ile ilgili not ekleyin..." disabled></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn-modal-save" id="btnSubmitOut" disabled>
                    <i class="fas fa-save me-1"></i> <span id="btnSubmitText">Çıkışı Kaydet</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- View Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-xl-custom">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewModalTitle"><i class="fas fa-eye me-2"></i> Çıkış Kaydı Detayı</h5>
                <button type="button" class="btn btn-link text-white p-0 border-0" data-bs-dismiss="modal"><i
                        class="fas fa-times"></i></button>
            </div>
            <div class="modal-body" id="viewBody">
                <!-- Detay İçeriği JS ile dolacak -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-warning me-auto" id="btnMoveToEdit" style="display:none">
                    <i class="fas fa-edit me-1"></i> Düzenle
                </button>
                <div id="approvalActions" style="display:none">
                    <button type="button" class="btn btn-danger" id="btnReject">Reddet</button>
                    <button type="button" class="btn btn-success" id="btnApprove">Onayla</button>
                </div>
                <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<script>
    var eurExchangeRate = <?= (float) get_setting('eur_rate', '0') ?>;
    var curPage = 1, curPerPage = 10, curSearch = '', searchTimer;
    var apiUrl = '<?= BASE_URL ?>/api/stock_out.php';
    var lines = [];
    var isSingleWarehouse = <?= count($warehouses) === 1 ? 'true' : 'false' ?>;
    var userRole = '<?= $_SESSION['dp_role'] ?>';
    var userId = '<?= $_SESSION['dp_user_id'] ?>';

    function esc(v) { return $('<span>').text(v || '').html(); }

    function load() {
        var startDate = $('#startDate').val();
        var endDate = $('#endDate').val();
        var status = $('#statusFilter').val();
        $.get(apiUrl, {
            action: 'list',
            page: curPage,
            per_page: curPerPage,
            search: curSearch,
            start_date: startDate,
            end_date: endDate,
            status: status
        }, function (r) {
            if (!r.success || !r.data.data) return;
            var html = '';
            $.each(r.data.data, function (i, d) {
                var muhatap = [];
                if (d.requester_name) muhatap.push('<i class="fas fa-user me-1 opacity-50"></i> ' + esc(d.requester_name + ' ' + d.requester_surname));
                if (d.customer) muhatap.push('<i class="fas fa-building me-1 opacity-50"></i> ' + esc(d.customer));

                var muhatapHtml = muhatap.length ? muhatap.join('<br>') : '—';

                var stClass = 'badge-pending', stText = 'Beklemede';
                if (d.status == 1) { stClass = 'badge-approved'; stText = 'Onaylandı'; }
                else if (d.status == 2) { stClass = 'badge-rejected'; stText = 'Reddedildi'; }

                html += '<tr>' +
                    '<td><b>' + esc(d.product) + '</b></td>' +
                    '<td>' + esc(d.warehouse) + '</td>' +
                    '<td><small>' + muhatapHtml + '</small></td>' +
                    '<td class="num-align">' + formatQty(d.quantity) + ' <small class="text-muted">' + esc(d.unit) + '</small></td>' +
                    '<td class="num-align"><strong>' + formatTurkish((parseFloat(d.total_price) || 0).toFixed(2)) + '</strong> <small>' + '<?= get_setting('base_currency', 'EUR') ?>' + '</small></td>' +
                    '<td><small>' + esc(d.created_by_name || '—') + '</small></td>' +
                    '<td><span class="badge-status ' + stClass + '">' + stText + '</span></td>' +
                    '<td class="num-align"><small>' + d.created_at + '</small></td>' +
                    '<td class="text-center pe-3"><button class="btn btn-xs btn-outline-primary" onclick="viewRow(' + d.id + ')"><i class="fas fa-eye"></i></button></td>' +
                    '</tr>';
            });
            $('#tableBody').html(html || '<tr><td colspan="9" class="text-center text-muted p-4">Kayıt bulunamadı</td></tr>');
            $('#totalCount').text('Toplam: ' + formatQty(r.data.total) + ' kayıt');
            renderPag(r.data.total);
        }, 'json');
    }

    function exportExcel() {
        var startDate = $('#startDate').val();
        var endDate = $('#endDate').val();
        var url = apiUrl + '?action=export_excel&search=' + encodeURIComponent(curSearch) +
            '&start_date=' + startDate + '&end_date=' + endDate;
        window.location.href = url;
    }

    function renderPag(total) {
        var pages = Math.ceil(total / curPerPage);
        if (pages <= 1) { $('#pagination').html(''); return; }
        var html = '<ul class="pagination pagination-sm">', s = Math.max(1, curPage - 2), e = Math.min(pages, curPage + 2);
        if (curPage > 1) html += '<li class="page-item"><a class="page-link" data-p="' + (curPage - 1) + '" href="#">&laquo;</a></li>';
        for (var p = s; p <= e; p++) html += '<li class="page-item' + (p === curPage ? ' active' : '') + '"><a class="page-link" data-p="' + p + '" href="#">' + p + '</a></li>';
        if (curPage < pages) html += '<li class="page-item"><a class="page-link" data-p="' + (curPage + 1) + '" href="#">&raquo;</a></li>';
        html += '</ul>';
        $('#pagination').html(html).find('a').on('click', function (e) { e.preventDefault(); curPage = parseInt($(this).data('p')); load(); });
    }

    function editRow(idOrBatch, isBatch = false) {
        var params = isBatch ? { action: 'get_batch', batch_id: idOrBatch } : { action: 'get', id: idOrBatch };
        $.get(apiUrl, params, function (r) {
            if (!r.success) { showError(r.message); return; }
            
            var d = isBatch ? r.data.meta : r.data;
            lines = [];
            
            if (isBatch && r.data.items) {
                $.each(r.data.items, function(i, item) {
                    lines.push({
                        product_id: item.product_id,
                        product_name: item.product_name,
                        quantity: parseFloat(item.quantity),
                        unit: item.unit,
                        unit_price: parseFloat(item.unit_price_orig || item.unit_price),
                        currency: item.currency || 'EUR',
                        total: parseFloat(item.total_price_orig || item.total_price)
                    });
                });
            } else {
                lines = [{
                    product_id: d.product_id,
                    product_name: d.product_name,
                    quantity: parseFloat(d.quantity),
                    unit: d.unit,
                    unit_price: parseFloat(d.unit_price),
                    currency: d.currency || 'EUR',
                    total: parseFloat(d.total_price)
                }];
            }
            
            renderLines();
            if (isBatch) {
                $('#editId').val(''); // Clear item ID
                $('#editBatchId').val(d.batch_id); // New hidden field for batch ID
            } else {
                $('#editId').val(d.id);
                $('#editBatchId').val('');
            }
            
            $('#warehouseSelect').val(d.warehouse_id).trigger('change');
            $('#requesterSelect').val(d.requester_id).trigger('change');
            $('#customerSelect').val(d.customer_id).trigger('change');
            $('[name="note"]').val(d.note);

            var titlePrefix = 'Çıkış Kaydını Düzenle';
            $('#addModal .modal-title').html('<i class="fas fa-edit me-2"></i> ' + titlePrefix + ' (#' + (d.order_no || d.id) + ')');
            
            var btnText = 'Güncelle';
            $('#btnSubmitText').text(btnText);
            
            $('#viewModal').modal('hide');
            $('#addModal').modal('show');
        }, 'json');
    }

    function openAddModal() {
        lines = [];
        renderLines();
        $('#editId').val('');
        $('#editBatchId').val('');
        $('#formStockOut')[0].reset();
        
        $('#requesterSelect, #customerSelect, #productAdd').val(null).trigger('change');

        if (!isSingleWarehouse) {
            $('#warehouseSelect').val(null).trigger('change');
        } else {
            $('#warehouseSelect').trigger('change');
        }
        
        $('#addModal .modal-title').html('<i class="fas fa-sign-out-alt me-2"></i> Yeni Stok Çıkışı');
        $('#btnSubmitText').text('Çıkışı Kaydet');
        $('#addModal').modal('show');
    }

    $(function () {
        $('#startDate, #endDate, #statusFilter').on('change', function () {
            curPage = 1;
            load();
        });

        $('#btnExport').on('click', function () {
            exportExcel();
        });
    });

    function renderLines() {
        if (!lines.length) { $('#lineContainer').hide(); return; }
        $('#lineContainer').show();
        var html = '', totalSum = 0;
        $.each(lines, function (i, l) {
            totalSum += l.total;
            html += '<tr>' +
                '<td>' + esc(l.product_name) + '</td>' +
                '<td class="num-align">' + (+l.quantity) + ' ' + esc(l.unit) + '</td>' +
                '<td class="num-align">' + formatTurkish((parseFloat(l.unit_price) || 0).toFixed(4)) + '</td>' +
                '<td class="num-align"><strong>' + formatTurkish((parseFloat(l.total) || 0).toFixed(2)) + '</strong></td>' +
                '<td class="text-center"><button type="button" class="btn btn-xs btn-link text-danger p-0" onclick="removeLine(' + i + ')"><i class="fas fa-times"></i></button></td>' +
                '</tr>';
        });
        $('#lineBody').html(html);
        $('#totalSumLabel').text(formatTurkish((totalSum || 0).toFixed(2)) + ' <?= get_setting('base_currency', 'EUR') ?>');

        var totalSumTL = totalSum * eurExchangeRate;
        $('#totalSumTLLabel').text(formatTurkish((totalSumTL || 0).toFixed(2)) + ' TL');
    }

    function removeLine(i) { lines.splice(i, 1); renderLines(); }

    function viewRow(id) {
        $('#viewBody').html('<div class="text-center p-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
        $('#viewModal').modal('show');
        $.get(apiUrl, { action: 'get', id: id }, function (r) {
            if (!r.success) { $('#viewBody').html('<div class="alert alert-danger">' + r.message + '</div>'); return; }
            var d = r.data;
            var baseCurrency = '<?= get_setting('base_currency', 'EUR') ?>';
            var stClass = 'badge-pending', stText = 'Beklemede';
            if (d.status == 1) { stClass = 'badge-approved'; stText = 'Onaylandı'; }
            else if (d.status == 2) { stClass = 'badge-rejected'; stText = 'Reddedildi'; }

            $('#viewModalTitle').html('<i class="fas fa-eye me-2"></i> Çıkış Kaydı Detayı');
            var html = '<div class="d-flex justify-content-between align-items-center mb-3">' +
                '<div class="modal-section-label mb-0 border-0"><i class="fas fa-map-marker-alt"></i> Konum & Muhatap Bilgileri</div>' +
                '<span class="badge-status ' + stClass + '">' + stText + '</span>' +
                '</div>' +
                '<div class="row g-3 mb-4">' +
                '<div class="col-md-4"><label class="form-label small text-muted mb-1">Depo</label><div class="fw-bold"><i class="fas fa-warehouse me-1 opacity-50"></i> ' + esc(d.warehouse_name) + '</div></div>' +
                '<div class="col-md-4"><label class="form-label small text-muted mb-1">Talep Eden</label><div class="fw-bold"><i class="fas fa-user me-1 opacity-50"></i> ' + esc(d.requester_name ? d.requester_name + ' ' + d.requester_surname : '—') + '</div></div>' +
                '<div class="col-md-4"><label class="form-label small text-muted mb-1">Müşteri</label><div class="fw-bold"><i class="fas fa-handshake me-1 opacity-50"></i> ' + esc(d.customer_name || '—') + '</div></div>' +
                '</div>' +
                '<div class="modal-section-label mt-4"><i class="fas fa-boxes"></i> Ürün Bilgileri</div>' +
                '<div class="table-responsive mb-4">' +
                '<table class="table table-sm table-bordered m-0">' +
                '<thead class="bg-light small"><tr>' +
                '<th>Ürün</th>' +
                '<th style="width:150px" class="num-align">Miktar</th>' +
                '<th style="width:150px" class="num-align">Birim (' + baseCurrency + ')</th>' +
                '<th style="width:150px" class="num-align">Toplam</th>' +
                '</tr></thead>' +
                '<tbody><tr>' +
                '<td>' + esc(d.product_name) + '</td>' +
                '<td class="num-align">' + (+d.quantity) + ' ' + esc(d.unit) + '</td>' +
                '<td class="num-align">' + formatTurkish((parseFloat(d.unit_price) || 0).toFixed(4)) + '</td>' +
                '<td class="num-align"><strong>' + formatTurkish((parseFloat(d.total_price) || 0).toFixed(2)) + '</strong></td>' +
                '</tr></tbody>' +
                '<tfoot class="bg-light fw-bold">' +
                '<tr><td colspan="3" class="num-align">TOPLAM:</td><td class="num-align">' + formatTurkish((parseFloat(d.total_price) || 0).toFixed(2)) + ' ' + baseCurrency + '</td></tr>' +
                '<tr class=""><td colspan="3" class="num-align text-primary">TL TOPLAM:</td><td class="num-align text-primary">' + formatTurkish(((parseFloat(d.total_price) || 0) * eurExchangeRate).toFixed(2)) + ' TL</td></tr>' +
                '</tfoot>' +
                '</table>' +
                '</div>' +
                '<div class="modal-section-label mt-4"><i class="fas fa-info-circle"></i> İşlem Bilgileri</div>' +
                '<div class="row g-3">' +
                '<div class="col-md-6"><label class="form-label small text-muted mb-1">Not</label><div class="bg-light p-2 rounded border" style="min-height:50px">' + esc(d.note || '—') + '</div></div>' +
                '<div class="col-md-3"><label class="form-label small text-muted mb-1">İşlemi Yapan</label><div class="small fw-bold text-muted">' + esc(d.created_by_name || '—') + '</div><label class="form-label small text-muted mt-2 mb-1">Güncelleyen</label><div class="small fw-bold text-muted">' + esc(d.updated_by_name || '—') + '</div></div>' +
                '<div class="col-md-3 text-end"><label class="form-label small text-muted mb-1">İşlem Tarihi</label><div class="small fw-bold text-muted">' + d.created_at + '</div></div>' +
                '</div>';
            $('#viewBody').html(html);

            // Access control for approval
            if (d.status == 0) {
                $('#approvalActions').show();
                $('#btnApprove').off('click').on('click', function () {
                    if (!confirm('Onaylamak istediğinize emin misiniz?')) return;
                    $.post(apiUrl, { action: 'approve', batch_id: d.batch_id }, function (r) {
                        if (r.success) {
                            showSuccess(r.message);
                            $('#viewModal').modal('hide');
                            if (typeof updatePendingBadges === 'function') updatePendingBadges();
                            load();
                        }
                        else showError(r.message);
                    }, 'json');
                });
                $('#btnReject').off('click').on('click', function () {
                    if (!confirm('Reddetmek istediğinize emin misiniz?')) return;
                    $.post(apiUrl, { action: 'reject', batch_id: d.batch_id }, function (r) {
                        if (r.success) {
                            showSuccess(r.message);
                            $('#viewModal').modal('hide');
                            if (typeof updatePendingBadges === 'function') updatePendingBadges();
                            load();
                        }
                        else showError(r.message);
                    }, 'json');
                });
            } else {
                $('#approvalActions').hide();
            }

            $('#btnMoveToEdit').show().off('click').on('click', function () { editRow(d.batch_id, true); });
        }, 'json');
    }

    $(document).ready(function () {
        load();

            $('#warehouseSelect').select2({
                theme: 'bootstrap-5', placeholder: '— Depo Seçin —', width: '100%', dropdownParent: $('#addModal'),
                templateResult: function (data) {
                    if (!data.id) return data.text;
                    if (data.text.indexOf('(SAYIM DEVAM EDİYOR)') !== -1) {
                        return $('<span class="text-danger fw-bold"><i class="fas fa-exclamation-triangle me-1"></i> ' + data.text + '</span>');
                    }
                    return data.text;
                }
            });

            // Modal açıldığında depoya odaklan
            $('#addModal').on('shown.bs.modal', function() {
                $('#warehouseSelect').select2('open');
            });

            $('#requesterSelect').select2({
                theme: 'bootstrap-5', placeholder: '— Talep Eden —', allowClear: true, width: '100%', dropdownParent: $('#addModal'),
                ajax: {
                    url: '<?= BASE_URL ?>/api/requesters.php',
                    data: function (p) { return { action: 'active_list', search: p.term || '' }; },
                    processResults: function (d) {
                        return { results: $.map(d.data, function (u) { return { id: u.id, text: u.name + ' ' + u.surname }; }) };
                    },
                    delay: 300
                }
            });

        $('#customerSelect').select2({
            theme: 'bootstrap-5', placeholder: '— Müşteri —', allowClear: true, width: '100%', dropdownParent: $('#addModal'),
            ajax: { url: '<?= BASE_URL ?>/api/customers.php', data: function (p) { return { action: 'active_list', search: p.term || '' }; }, processResults: function (d) { return { results: $.map(d.data, function (u) { return { id: u.id, text: u.name }; }) }; }, delay: 300 }
        });

        $('#productAdd').select2({
            theme: 'bootstrap-5', placeholder: '— Ürün arayın —', width: '100%', dropdownParent: $('#addModal'),
            ajax: { 
                url: '<?= BASE_URL ?>/api/products.php', 
                data: function (p) { 
                    return { action: 'search_select2', q: p.term || '', warehouse_id: $('#warehouseSelect').val() || 0 }; 
                }, 
                processResults: function (d) { return { results: d.results }; }, 
                delay: 300 
            },
            templateResult: function (i) { 
                if (i.loading) return i.text; 
                var no = '<?= BASE_URL ?>/assets/no-image.png', img = i.image ? '<?= BASE_URL ?>/images/UrunResim/' + i.image : no; 
                var stockText = 'Stok: ' + formatQty(i.stock || 0);
                var stockColor = (i.stock > 0) ? 'text-success' : 'text-danger';
                
                return $('<div class="d-flex justify-content-between align-items-center">' +
                    '<span><img src="' + img + '" class="select2-product-img" onerror="this.src=\'' + no + '\'"> ' + esc(i.text) + '</span>' +
                    '<span class="badge rounded-pill ' + (i.stock > 0 ? 'bg-light text-dark' : 'bg-danger-subtle text-danger') + ' border small">' + stockText + ' ' + esc(i.unit) + '</span>' +
                '</div>');
            }
        });

        // Depo seçilince diğerlerini aktif et
        $('#warehouseSelect').on('change', function() {
            var val = $(this).val();
            var selects = $('#requesterSelect, #customerSelect, #productAdd');
            var others = $('#qtyInput, [name="note"], #btnSubmitOut');
            
            if(val) {
                selects.prop('disabled', false).trigger('change');
                others.prop('disabled', false);
            } else {
                selects.prop('disabled', true).trigger('change');
                others.prop('disabled', true);
            }
        });

        // Müşteri seçilince ürün menüsünü aktif et
        $('#customerSelect').on('change', function() {
            var val = $(this).val();
            var productAdd = $('#productAdd');
            var others = $('#qtyInput, [name="note"], #btnSubmitOut');
            
            if(val) {
                productAdd.prop('disabled', false);
                others.prop('disabled', false);
            } else {
                productAdd.prop('disabled', true);
                others.prop('disabled', true);
            }
        });

        // Sayfa/Modal ilk açıldığında kilitleri uygula
        $('#warehouseSelect').trigger('change');

        $('#productAdd').on('select2:select', function (e) {
            $('#qtyInput').val('').focus();
        });

        $('#btnAddLine').on('click', function () {
            var sel = $('#productAdd').select2('data');
            if (!sel || !sel[0] || !sel[0].id) { showError('Lütfen bir ürün seçin.'); return; }
            var qty = parseFloat($('#qtyInput').val());
            if (!qty || qty <= 0) { showError('Geçerli bir adet girin.'); return; }

            var productId = sel[0].id, productName = sel[0].text, unit = sel[0].unit || '', warehouseId = $('#warehouseSelect').val();
            if (!warehouseId) { showError('Önce depo seçmelisiniz.'); return; }
            var availableStock = parseFloat(sel[0].stock || 0);

            var existingIndex = lines.findIndex(function (l) { return l.product_id == productId; });
            var totalQtyAfterAdd = qty + (existingIndex !== -1 ? lines[existingIndex].quantity : 0);

            if (totalQtyAfterAdd > availableStock) {
                showError('Yetersiz stok! Toplam talep (' + totalQtyAfterAdd + ') mevcut stoğu (' + formatQty(availableStock) + ') aşıyor.');
                return;
            }

            // Sync update if product already in list
            if (existingIndex !== -1) {
                lines[existingIndex].quantity = totalQtyAfterAdd;
                lines[existingIndex].total = lines[existingIndex].quantity * lines[existingIndex].unit_price;
                Swal.fire({ icon: 'success', title: 'Ürün Miktarı Güncellendi', text: productName + ' miktarı ' + lines[existingIndex].quantity + ' ' + unit + ' olarak güncellendi.', position: 'center', showConfirmButton: false, timer: 2000 });
                renderLines();
                $('#productAdd').val(null).trigger('change');
                $('#qtyInput').val('');
                return;
            }

            var btn = $('#btnAddLine');
            var originalHtml = btn.html();
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

            $.get(apiUrl, { action: 'get_last_price', product_id: productId, warehouse_id: warehouseId }, function (r) {
                btn.prop('disabled', false).html(originalHtml);
                var unitPrice = r.success && r.data ? parseFloat(r.data.price_eur) : 0;
                
                var finalIndex = lines.findIndex(function (l) { return l.product_id == productId; });
                if (finalIndex !== -1) {
                    lines[finalIndex].quantity += qty;
                    lines[finalIndex].total = lines[finalIndex].quantity * lines[finalIndex].unit_price;
                } else {
                    lines.push({ product_id: productId, product_name: productName, quantity: qty, unit: unit, unit_price: unitPrice, total: unitPrice * qty });
                }
                
                renderLines();
                $('#productAdd').val(null).trigger('change');
                $('#qtyInput').val('');
            }, 'json').fail(function() { btn.prop('disabled', false).html(originalHtml); });
        });

        $('#btnSubmitOut').on('click', function () {
            if (!lines.length) { showError('En az 1 ürün ekleyin.'); return; }
            var btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Kaydediliyor...');

            var action = $('#editBatchId').val() ? 'save_batch' : ($('#editId').val() ? 'edit' : 'add');
            var data = {
                action: action,
                id: $('#editId').val(),
                batch_id: $('#editBatchId').val(),
                warehouse_id: $('#warehouseSelect').val(),
                requester_id: $('#requesterSelect').val(),
                customer_id: $('#customerSelect').val(),
                note: $('[name="note"]').val(),
                lines: JSON.stringify(lines)
            };

            $.post(apiUrl, data, function (r) {
                btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> ' + $('#btnSubmitText').text());
                if (r.success) {
                    showSuccess(r.message);
                    $('#addModal').modal('hide');
                    if (typeof updatePendingBadges === 'function') updatePendingBadges();
                    curPage = 1; load();
                } else showError(r.message);
            }, 'json');
        });

        $('#formStockOut').on('submit', function (e) { e.preventDefault(); });
        $('#qtyInput').on('keydown', function (e) { if (e.which == 13) { e.preventDefault(); $('#btnAddLine').trigger('click'); } });

        $('#searchBox').on('input', function () { clearTimeout(searchTimer); curSearch = $(this).val(); searchTimer = setTimeout(function () { curPage = 1; load(); }, 400); });
        $('#perPage').on('change', function () { curPerPage = parseInt($(this).val()); curPage = 1; load(); });
    });
</script>