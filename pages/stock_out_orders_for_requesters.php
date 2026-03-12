<?php
/**
 * Sipariş Bazlı Çıkış Listesi — Gruplandırılmış Görünüm
 */
requireRole(ROLE_REQUESTER);
$warehouses = Database::fetchAll("
    SELECT w.id, w.name, 
    (SELECT COUNT(*) FROM inventory_sessions WHERE warehouse_id = w.id AND status = 'open') > 0 as is_inventory_open 
    FROM tbl_dp_warehouses w 
    WHERE w.hidden=0 AND w.is_active=1 
    ORDER BY w.name
");
?>
<style>
    .order-row {
        cursor: pointer;
        transition: background 0.2s;
    }

    .order-row:hover {
        background: #f8f9fa !important;
    }

    /* ───────────────────────────────────────────
     KART HEADER — araç çubuğu hizalama
  ─────────────────────────────────────────── */
    .card-header .card-tools {
        align-items: center;
        gap: 10px;
    }

    .card-header .card-title {
        font-size: 1.5rem;
        display: flex;
        align-items: center;
    }

    .card-header .card-title i {
        font-size: 1.25rem;
        margin-right: 0.75rem;
    }

    /* Tüm header araçlarını aynı yüksekliğe sabitle */
    .card-header .card-tools .form-select-sm,
    .card-header .card-tools .input-group-sm .form-control,
    .card-header .card-tools .input-group-sm .input-group-text,
    .card-header .card-tools .btn-sm {
        height: 2rem;
        line-height: 1;
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

    .detail-row {
        display: none;
        background: #fdfdfd;
    }

    .detail-container {
        padding: 15px;
        border-left: 4px solid #ffc107;
        margin: 10px;
        box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.02);
    }

    .expand-icon {
        transition: transform 0.3s;
        color: #adb5bd;
    }

    .row-expanded .expand-icon {
        transform: rotate(90deg);
        color: #ffc107;
    }

    /* Görsel Gruplandırma (Dashed Border) */
    .orders-table tr.row-expanded>td {
        border-top: 2px dashed #ffc107 !important;
        background: #fffdf5 !important;
    }

    .orders-table tr.row-expanded>td:first-child {
        border-left: 2px dashed #ffc107 !important;
    }

    .orders-table tr.row-expanded>td:last-child {
        border-right: 2px dashed #ffc107 !important;
    }

    .orders-table tr.detail-row.show-detail>td {
        border-bottom: 2px dashed #ffc107 !important;
        background: #fffdf5 !important;
        border-top: none !important;
    }

    .orders-table tr.detail-row.show-detail>td:first-child {
        border-left: 2px dashed #ffc107 !important;
    }

    .orders-table tr.detail-row.show-detail>td:last-child {
        border-right: 2px dashed #ffc107 !important;
    }

    /* Detay içindeki tablo standart kalsın */
    .detail-container table {
        border-collapse: collapse !important;
    }

    .detail-container table td,
    .detail-container table th {
        border: 1px solid #dee2e6 !important;
        /* Standart border */
    }

    .detail-container {
        padding: 10px 15px;
        margin: 5px 0;
        border-left: 3px solid #ffc107;
    }

    .badge-item-count {
        font-size: 0.85rem;
        padding: 5px 10px;
    }

    /* ───────────────────────────────────────────
     MODAL GENEL (Premium Style)
  ─────────────────────────────────────────── */
    #addModal .modal-content,
    #viewModal .modal-content {
        border: none;
        border-radius: 16px;
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
        border-radius: 8px;
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
        border-radius: 8px;
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

    /* Select2 boostrap-5 focus styling */
    .select2-container--bootstrap-5.select2-container--focus .select2-selection,
    .select2-container--bootstrap-5.select2-container--open .select2-selection {
        border-color: #1a56db !important;
        box-shadow: 0 0 0 3px rgba(26, 86, 219, 0.12) !important;
    }

    .select2-container--bootstrap-5 .select2-dropdown {
        border-color: #1a56db !important;
        border-radius: 8px !important;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
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
</style>

<div class="row stock-out-row">
    <div class="col-12">
        <div class="card card-warning card-outline">
            <div class="card-header">
                <h3 class="card-title text-bold"><i class="fas fa-list-ul me-2"></i> Taleplerim (Sipariş Bazlı)</h3>
                <div class="card-tools d-flex gap-2">
                    <select id="perPage" class="form-select form-select-sm" style="width:auto">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <div class="input-group input-group-sm me-2" style="width: 200px;">
                        <input type="text" id="searchBox" class="form-control" placeholder="Müşteri ara...">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                    </div>
                    <a href="?page=stock_out_requests" class="btn btn-secondary btn-sm px-3 shadow-sm">
                        <i class="fas fa-list me-1" style="margin-right: 5px;"></i>Ürün Talep Listesi</a>
                    <button class="btn btn-primary btn-sm px-3 shadow-sm" onclick="openAddModal()">
                        <i class="fas fa-plus me-1" style="margin-right: 5px;"></i>Yeni Talep Oluştur
                    </button>
                </div>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover m-0 table-valign-middle orders-table" id="ordersTable">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th width="100">Sip. #</th>
                            <th>Müşteri / Muhatap</th>
                            <th class="num-align">Kalem Sayısı</th>
                            <th class="num-align">Toplam Tutar</th>
                            <th class="num-align">Tarih</th>
                            <th class="text-center">Durum</th>
                            <th style="width:80px" class="text-center pe-3">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <td colspan="8" class="text-center p-4"><i class="fas fa-spinner fa-spin"></i> Yükleniyor...
                        </td>
                        </tr>
                    </tbody>
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
                <h5 class="modal-title"><i class="fas fa-sign-out-alt me-2"></i> Ürün Talep Ekranı</h5>
                <button type="button" class="btn btn-link text-white p-0 border-0" data-bs-dismiss="modal"><i
                        class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form id="formStockOut">
                    <input type="hidden" name="id" id="editId" value="">
                    <input type="hidden" name="edit_batch_id" id="editBatchId" value="">
                    <!-- Konum Bilgileri -->
                    <div class="modal-section-label">
                        <i class="fas fa-map-marker-alt"></i> Konum &amp; Muhatap Bilgileri
                    </div>
                    <div class="row g-3 mb-4">
                        <input type="hidden" name="warehouse_id" id="warehouseSelect" value="">
                        <input type="hidden" name="requester_id" id="requesterSelect"
                            value="<?= $_SESSION['dp_user_id'] ?>">
                        <div class="col-md-12">
                            <label class="form-label">Müşteri <span class="text-danger">*</span></label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-handshake field-icon"></i>
                                <select name="customer_id" id="customerSelect" class="form-select" required></select>
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
                                <input type="number" id="qtyInput" class="form-control text-end" placeholder="0"
                                    min="1" step="1"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                    style="border-top-right-radius: 0 !important; border-bottom-right-radius: 0 !important; border-right: 0 !important;"
                                    required disabled>
                                <button type="button" class="btn btn-primary px-3" id="btnAddLine"
                                    style="border-top-left-radius: 0 !important; border-bottom-left-radius: 0 !important;">
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
                                            (<?= getCurrencySymbol() ?>)</th>
                                        <th class="num-align" style="width:150px">Toplam</th>
                                        <th style="width:40px"></th>
                                    </tr>
                                </thead>
                                <tbody id="lineBody"></tbody>
                                <tfoot id="lineFoot">
                                    <tr class="bg-light fw-bold">
                                        <td colspan="3" class="num-align">GENEL TOPLAM:</td>
                                        <td id="totalSumLabel" class="num-align">0.00
                                            <?= getCurrencySymbol() ?>
                                        </td>
                                        <td></td>
                                    </tr>
                                    <tr class="fw-bold">
                                        <td colspan="3" class="num-align text-primary">TL GENEL TOPLAM:</td>
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
                    <span id="btnSubmitText">Ürün Talebini Kaydet</span>
                </button>
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


    function esc(v) { return $('<span>').text(v || '').html(); }

    function load() {
        $.get(apiUrl, { action: 'list_grouped', page: curPage, per_page: curPerPage, search: curSearch }, function (r) {
            if (!r.success) { showError(r.message); return; }
            var html = '';
            var baseCurrency = '<?= get_setting('base_currency', 'EUR') ?>';
            $.each(r.data.data, function (i, d) {
                html += '<tr class="order-row" onclick="toggleDetail(\'' + d.batch_id + '\', this)">' +
                    '<td><span class="badge bg-primary px-2">' + d.order_no + '</span></td>' +
                    '<td><div class="fw-bold text-primary">' + esc(d.customer_name || '—') + '</div></td>' +
                    '<td class="num-align"><span class="badge bg-light border text-dark ms-2 badge-item-count">' + formatQty(d.item_count) + ' Ürün</span></td>' +
                    '<td class="num-align"><strong>' + formatTurkish((parseFloat(d.total_eur) || 0).toFixed(2)) + '</strong> <small>' + baseCurrency + '</small></td>' +
                    '<td class="num-align"><span class="text-muted small"><i class="far fa-calendar-alt me-1"></i> ' + d.created_at_fmt + '</span></td>' +
                    '<td class="text-center">' +
                    (d.status == 0 ? '<span class="badge bg-warning">BEKLEMEDE</span>' :
                        (d.status == 2 ? '<span class="badge bg-danger">REDDEDİLDİ</span>' :
                            '<span class="badge bg-success">TESLİM EDİLDİ</span>')) +
                    '</td>' +
                    '<td class="text-center pe-3 text-nowrap">' +
                    '<a href="pages/stock_out_print.php?batch_id=' + d.batch_id + '" target="_blank" class="btn btn-xs btn-outline-secondary me-1" onclick="event.stopPropagation();" title="Yazdır"><i class="fas fa-print"></i></a>' +
                    (d.status == 0 ? '<button class="btn btn-xs btn-outline-warning me-1" onclick="event.stopPropagation(); editBatch(\'' + d.batch_id + '\')" title="Düzenle"><i class="fas fa-edit"></i></button>' : '') +
                    '<button class="btn btn-xs btn-outline-info" title="İncele"><i class="fas fa-eye"></i></button>' +
                    '</td>' +
                    '</tr>' +
                    '<tr class="detail-row" id="detail-' + d.batch_id + '">' +
                    '<td colspan="8">' +
                    '<div class="detail-container">' +
                    '<div class="mb-3"><strong><i class="fas fa-info-circle me-1 text-warning"></i> İşlem Notu:</strong> <span class="text-muted">' + esc(d.note || '—') + '</span></div>' +
                    '<div id="cont-' + d.batch_id + '"><div class="text-center p-2"><i class="fas fa-spinner fa-spin"></i></div></div>' +
                    '</div>' +
                    '</td>' +
                    '</tr>';
            });
            $('#tableBody').html(html || '<tr><td colspan="8" class="text-center text-muted p-4">Henüz kayıt bulunmuyor.</td></tr>');
            $('#totalCount').text('Toplam: ' + formatQty(r.data.total) + ' sipariş');
            renderPag(r.data.total);
        }, 'json');
    }

    function openAddModal() {
        lines = [];
        renderLines();
        $('#editId').val('');
        $('#editBatchId').val('');
        $('#formStockOut')[0].reset();

        // Reset Select2 fields
        $('#customerSelect').val(null).trigger('change');
        $('#productAdd').val(null).trigger('change');

        // Explicitly disable dependent fields
        $('#productAdd, #qtyInput, #btnAddLine, [name="note"], #btnSubmitOut').prop('disabled', true);

        $('#addModal .modal-title').html('<i class="fas fa-sign-out-alt me-2"></i> Ürün Talep Ekranı');
        $('#btnSubmitOut').html('<span id="btnSubmitText">Ürün Talebini Kaydet</span>');
        $('#addModal').modal('show');
    }

    function editBatch(batchId) {
        $.get(apiUrl, { action: 'get_batch', batch_id: batchId }, function (r) {
            if (!r.success) { showError(r.message); return; }

            lines = [];
            $('#editBatchId').val(batchId);
            $('#addModal .modal-title').html('<i class="fas fa-edit me-2"></i> Siparişi Düzenle: ' + batchId);
            $('#btnSubmitText').text('Güncellemeleri Kaydet');

            var d = r.data.items[0];
            $('[name="note"]').val(d.note || '');

            $('#warehouseSelect').val(d.warehouse_id).trigger('change');

            // Requesters & Customers are AJAX, need to set manually
            if (d.requester_id) {
                var reqOpt = new Option(d.requester_name + ' ' + (d.requester_surname || ''), d.requester_id, true, true);
                $('#requesterSelect').append(reqOpt).trigger('change');
            }
            if (d.customer_id) {
                var custOpt = new Option(d.customer_name, d.customer_id, true, true);
                $('#customerSelect').append(custOpt).trigger('change');
            }

            $.each(r.data.items, function (i, item) {
                lines.push({
                    product_id: item.product_id,
                    product_name: item.product_name,
                    quantity: parseFloat(item.quantity),
                    unit: item.unit,
                    unit_price: parseFloat(item.unit_price_orig), // Use original price
                    total: parseFloat(item.total_price_orig),
                    currency: item.currency || 'EUR'
                });
            });

            renderLines();
            $('#requesterSelect, #customerSelect, #productAdd').prop('disabled', false).trigger('change');
            $('#qtyInput, #btnAddLine, [name="note"], #btnSubmitOut').prop('disabled', false);
            $('#addModal').modal('show');
        }, 'json');
    }

    function renderLines() {
        if (!lines.length) { $('#lineContainer').hide(); return; }
        $('#lineContainer').show();
        var html = '', totalSum = 0;
        $.each(lines, function (i, l) {
            totalSum += l.total;
            html += '<tr>' +
                '<td>' + esc(l.product_name) + '</td>' +
                '<td class="num-align">' + formatQty(l.quantity) + ' ' + esc(l.unit) + '</td>' +
                '<td class="num-align">' + formatTurkish((parseFloat(l.unit_price) || 0).toFixed(4)) + '</td>' +
                '<td class="num-align"><strong>' + formatTurkish((parseFloat(l.total) || 0).toFixed(2)) + '</strong></td>' +
                '<td class="text-center"><button type="button" class="btn btn-xs btn-link text-danger p-0" onclick="removeLine(' + i + ')"><i class="fas fa-times"></i></button></td>' +
                '</tr>';
        });
        $('#lineBody').html(html);
        $('#totalSumLabel').text(formatTurkish((totalSum || 0).toFixed(2)) + ' ' + '<?= get_setting('base_currency', 'EUR') ?>');

        var totalSumTL = totalSum * eurExchangeRate;
        $('#totalSumTLLabel').text(formatTurkish((totalSumTL || 0).toFixed(2)) + ' TL');
    }

    function removeLine(i) { lines.splice(i, 1); renderLines(); }


    function toggleDetail(batchId, el) {
        var row = $(el);
        var detailRow = $('#detail-' + batchId);

        if (detailRow.is(':visible')) {
            detailRow.hide().removeClass('show-detail');
            row.removeClass('row-expanded');
        } else {
            row.addClass('row-expanded');
            detailRow.show().addClass('show-detail');
            loadBatchItems(batchId);
        }
    }

    function loadBatchItems(batchId) {
        var cont = $('#cont-' + batchId);
        if (cont.data('loaded')) return;

        var baseCurrency = '<?= get_setting('base_currency', 'EUR') ?>';
        $.get(apiUrl, { action: 'get_batch', batch_id: batchId }, function (r) {
            if (!r.success) { cont.html('<div class="text-danger">' + r.message + '</div>'); return; }

            var html = '<table class="table table-sm table-bordered m-0 bg-white shadow-sm">' +
                '<thead class="bg-light"><tr><th>Ürün Adı</th><th class="num-align" style="width:120px">Miktar</th><th class="num-align" style="width:150px">Birim Fiyat</th><th class="num-align" style="width:150px">Toplam</th></tr></thead><tbody>';

            $.each(r.data.items, function (i, d) {
                html += '<tr>' +
                    '<td>' + esc(d.product_name) + '</td>' +
                    '<td class="num-align">' + formatQty(d.quantity) + ' <small class="text-muted">' + esc(d.unit) + '</small></td>' +
                    '<td class="num-align">' + formatTurkish((parseFloat(d.unit_price) || 0).toFixed(4)) + ' <small>' + baseCurrency + '</small></td>' +
                    '<td class="num-align"><strong>' + formatTurkish((parseFloat(d.total_price) || 0).toFixed(2)) + '</strong> <small>' + baseCurrency + '</small></td>' +
                    '</tr>';
            });

            html += '</tbody>' +
                '<tfoot class="bg-light fw-bold">' +
                '<tr><td colspan="3" class="num-align">TOPLAM:</td><td class="num-align">' + formatTurkish((parseFloat(r.data.data_total_eur || 0)).toFixed(2)) + ' ' + baseCurrency + '</td></tr>' +
                '<tr class=""><td colspan="3" class="num-align text-primary">TL TOPLAM:</td><td class="num-align text-primary">' + (parseFloat(r.data.data_total_eur || 0) * eurExchangeRate).toFixed(2).replace(/\./g, ',') + ' TL</td></tr>' +
                '</tfoot></table>';
            cont.html(html).data('loaded', true);
        }, 'json');
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

    $(document).ready(function () {
        load();
        // Select2 Styles & Logic
        $('#customerSelect').select2({
            theme: 'bootstrap-5', placeholder: '— Müşteri —', allowClear: true, width: '100%', dropdownParent: $('#addModal'),
            ajax: { url: '<?= BASE_URL ?>/api/customers.php', data: function (p) { return { action: 'active_list', search: p.term || '' }; }, processResults: function (d) { return { results: $.map(d.data, function (u) { return { id: u.id, text: u.name }; }) }; }, delay: 300 }
        });

        $('#productAdd').select2({
            theme: 'bootstrap-5', placeholder: '— Ürün arayın —', width: '100%', dropdownParent: $('#addModal'),
            ajax: {
                url: '<?= BASE_URL ?>/api/products.php',
                data: function (p) {
                    return {
                        action: 'search_select2',
                        q: p.term || ''
                    };
                },
                processResults: function (d) {
                    return { results: d.results };
                },
                delay: 300
            },
            templateResult: function (i) {
                if (i.loading) return i.text;
                var no = '<?= BASE_URL ?>/assets/no-image.png', img = i.image ? '<?= BASE_URL ?>/images/UrunResim/' + i.image : no;
                var stockVal = parseFloat(i.stock || 0);
                var stockInfo = i.id ? ' <span class="badge bg-light text-dark border float-end">Toplam Stok: ' + formatQty(stockVal) + ' ' + (i.unit || '') + '</span>' : '';
                return $('<span><img src="' + img + '" class="select2-product-img" onerror="this.src=\'' + no + '\'"> ' + esc(i.text) + stockInfo + '</span>');
            }
        });

        // Müşteri seçilince ürün menüsünü aktif et
        $('#customerSelect').on('change', function () {
            var val = $(this).val();
            var productAdd = $('#productAdd');
            var others = $('#qtyInput, [name="note"], #btnSubmitOut');

            if (val) {
                productAdd.prop('disabled', false);
                others.prop('disabled', false);
            } else {
                productAdd.prop('disabled', true);
                others.prop('disabled', true);
            }
        });

        $('#productAdd').on('select2:select', function (e) {
            var data = e.params.data;
            $(this).data('current-unit', data.unit || '');
            $(this).data('current-stock', parseFloat(data.stock || 0));
            $('#qtyInput').prop('disabled', false).val('').focus();
            $('#btnAddLine').prop('disabled', false);
        });

        $('#btnAddLine').on('click', function () {
            var sel = $('#productAdd').select2('data');
            if (!sel || !sel[0] || !sel[0].id) { showError('Lütfen bir ürün seçin.'); return; }
            var qty = parseFloat($('#qtyInput').val());
            if (!qty || qty <= 0) { showError('Geçerli bir adet girin.'); return; }

            var productId = sel[0].id, productName = sel[0].text, unit = $('#productAdd').data('current-unit') || '', warehouseId = '';
            var availableStock = $('#productAdd').data('current-stock') || 0;

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
                $('#qtyInput').val('').prop('disabled', true);
                $('#btnAddLine').prop('disabled', true);
                setTimeout(function () { $('#productAdd').select2('open'); }, 100);
                return;
            }

            // Fetch price for new product
            var btn = $('#btnAddLine');
            var originalHtml = btn.html();
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

            $.get(apiUrl, { action: 'get_last_price', product_id: productId, warehouse_id: warehouseId }, function (r) {
                btn.prop('disabled', false).html(originalHtml);
                var unitPrice = r.success && r.data ? parseFloat(r.data.price_eur) : 0;

                // Final check before push
                var finalIndex = lines.findIndex(function (l) { return l.product_id == productId; });
                if (finalIndex !== -1) {
                    lines[finalIndex].quantity += qty;
                    lines[finalIndex].total = lines[finalIndex].quantity * lines[finalIndex].unit_price;
                } else {
                    lines.push({ product_id: productId, product_name: productName, quantity: qty, unit: unit, unit_price: unitPrice, total: unitPrice * qty });
                }

                renderLines();
                $('#productAdd').val(null).trigger('change');
                $('#qtyInput').val('').prop('disabled', true);
                $('#btnAddLine').prop('disabled', true);
                setTimeout(function () { $('#productAdd').select2('open'); }, 100);
            }, 'json').fail(function () { btn.prop('disabled', false).html(originalHtml); });
        });

        $('#btnSubmitOut').on('click', function () {
            if (!lines.length) { showError('En az 1 ürün ekleyin.'); return; }
            if (!$('#requesterSelect').val() || !$('#customerSelect').val()) {
                showError('Lütfen tüm zorunlu alanları doldurun.');
                return;
            }
            var btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Kaydediliyor...');

            var isEdit = $('#editBatchId').val() !== '';
            var data = {
                action: isEdit ? 'save_batch' : 'add',
                batch_id: $('#editBatchId').val(),
                warehouse_id: $('#warehouseSelect').val(),
                requester_id: $('#requesterSelect').val(),
                customer_id: $('#customerSelect').val(),
                note: $('[name="note"]').val(),
                lines: JSON.stringify(lines)
            };

            $.post(apiUrl, data, function (r) {
                var btnText = isEdit ? 'Güncellemeleri Kaydet' : 'Ürün Talebini Kaydet';
                btn.prop('disabled', false).html('<span id="btnSubmitText">' + btnText + '</span>');
                if (r.success) {
                    showSuccess(r.message);
                    $('#addModal').modal('hide');
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