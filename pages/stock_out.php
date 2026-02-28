<?php
/**
 * Depodan Çıkış Listesi & Yönetimi
 */
requireRole(ROLE_ADMIN, ROLE_USER, ROLE_REQUESTER);
$warehouses = Database::fetchAll("SELECT id,name FROM tbl_dp_warehouses WHERE hidden=0 AND is_active=1 ORDER BY name");
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
    .stock-out-row {
        margin-top: 1.25rem;
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

    /* Form label */
    .form-label {
        font-size: 0.8rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 6px;
        display: block;
    }

    /* Input & Select */
    #addModal .form-control,
    #addModal .form-select,
    #viewModal .form-control,
    #viewModal .form-select {
        border: 1.5px solid #d1d9e6;
        border-radius: 8px;
        padding: 9px 13px;
        font-size: 0.88rem;
        color: #1f2937;
        background: #fff;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    #addModal .form-control:focus,
    #addModal .form-select:focus,
    #viewModal .form-control:focus,
    #viewModal .form-select:focus {
        border-color: #1a56db;
        box-shadow: 0 0 0 3px rgba(26, 86, 219, 0.12);
        outline: none;
    }

    /* İkonlu input wrapper */
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

    .input-icon-wrap .form-control,
    .input-icon-wrap .form-select {
        padding-left: 32px;
    }

    .input-icon-wrap textarea.form-control {
        padding-left: 13px;
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

    .select2-container--bootstrap-5 .select2-selection {
        border: 1.5px solid #d1d9e6 !important;
        border-radius: 8px !important;
        min-height: 40px !important;
        padding: 5px 10px 5px 32px !important;
    }
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
                    <div class="input-group input-group-sm me-2" style="width: 200px;">
                        <input type="text" id="searchBox" class="form-control" placeholder="Ara...">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                    </div>
                    <a href="?page=stock_out_orders" class="btn btn-secondary btn-sm px-3 shadow-sm">
                        <i class="fas fa-list-ul me-1"></i> Sipariş Bazlı Liste
                    </a>
                </div>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover table-striped m-0 table-valign-middle">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th style="width:60px" class="ps-3">#</th>
                            <th>Ürün</th>
                            <th>Depo</th>
                            <th>Alan / Müşteri</th>
                            <th class="num-align">Miktar</th>
                            <th class="num-align">Toplam (EUR)</th>
                            <th style="width:120px">İşlemi Yapan</th>
                            <th style="width:120px">Tarih</th>
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
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formStockOut">
                    <input type="hidden" name="id" id="editId" value="">
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
                                        <option value="<?= e($w['id']) ?>">
                                            <?= e($w['name']) ?>
                                        </option>
                                        <?php
                                    endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Talep Eden</label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-user field-icon"></i>
                                <select name="requester_id" id="requesterSelect" class="form-select"></select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Müşteri</label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-handshake field-icon"></i>
                                <select name="customer_id" id="customerSelect" class="form-select"></select>
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
                                <select id="productAdd" class="form-select"></select>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Miktar</label>
                            <div class="input-group">
                                <input type="number" id="qtyInput" class="form-control text-end" placeholder="0.00"
                                    step="any">
                                <span class="input-group-text" id="unitAddLabel">Adet</span>
                                <button type="button" class="btn btn-primary" id="btnAddLine"><i
                                        class="fas fa-plus"></i></button>
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
                                        <th class="num-align" style="width:150px">Birim (EUR)</th>
                                        <th class="num-align" style="width:150px">Toplam</th>
                                        <th style="width:40px"></th>
                                    </tr>
                                </thead>
                                <tbody id="lineBody"></tbody>
                                <tfoot id="lineFoot">
                                    <tr class="bg-light fw-bold">
                                        <td colspan="3" class="num-align">GENEL TOPLAM:</td>
                                        <td id="totalSumLabel" class="num-align">0.00 EUR</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Not / Açıklama</label>
                        <textarea name="note" class="form-control" rows="2"
                            placeholder="İşlem ile ilgili not ekleyin..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn-modal-save" id="btnSubmitOut">
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
                <h5 class="modal-title"><i class="fas fa-eye me-2"></i> Çıkış Kaydı Detayı</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewBody">
                <!-- Detay İçeriği JS ile dolacak -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-warning me-auto" id="btnMoveToEdit" style="display:none">
                    <i class="fas fa-edit me-1"></i> Düzenle
                </button>
                <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<script>
    var curPage = 1, curPerPage = 10, curSearch = '', searchTimer;
    var apiUrl = '<?= BASE_URL ?>/api/stock_out.php';
    var lines = [];

    function esc(v) { return $('<span>').text(v || '').html(); }

    function load() {
        $.get(apiUrl, { action: 'list', page: curPage, per_page: curPerPage, search: curSearch }, function (r) {
            if (!r.success || !r.data.data) return;
            var html = '';
            $.each(r.data.data, function (i, d) {
                var muhatap = '—';
                if (d.requester_name) muhatap = '<i class="fas fa-user me-1 opacity-50"></i> ' + esc(d.requester_name + ' ' + d.requester_surname);
                else if (d.customer) muhatap = '<i class="fas fa-building me-1 opacity-50"></i> ' + esc(d.customer);

                html += '<tr>' +
                    '<td class="ps-3">' + d.id + '</td>' +
                    '<td><b>' + esc(d.product) + '</b></td>' +
                    '<td>' + esc(d.warehouse) + '</td>' +
                    '<td>' + muhatap + '</td>' +
                    '<td class="num-align">' + (+d.quantity) + ' <small class="text-muted">' + esc(d.unit) + '</small></td>' +
                    '<td class="num-align"><strong>' + formatTurkish(parseFloat(d.total_price).toFixed(2)) + '</strong> <small>EUR</small></td>' +
                    '<td><small>' + esc(d.created_by_name || '—') + '</small></td>' +
                    '<td><small>' + d.created_at + '</small></td>' +
                    '<td class="text-center pe-3"><button class="btn btn-xs btn-outline-primary" onclick="viewRow(' + d.id + ')"><i class="fas fa-eye"></i></button></td>' +
                    '</tr>';
            });
            $('#tableBody').html(html || '<tr><td colspan="8" class="text-center text-muted p-4">Kayıt bulunamadı</td></tr>');
            $('#totalCount').text('Toplam: ' + r.data.total + ' kayıt');
            renderPag(r.data.total);
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

    function editRow(id) {
        $.get(apiUrl, { action: 'get', id: id }, function (r) {
            if (!r.success) { showError(r.message); return; }
            var d = r.data;
            lines = [{
                product_id: d.product_id,
                product_name: d.product_name,
                quantity: parseFloat(d.quantity),
                unit: d.unit,
                unit_price: parseFloat(d.unit_price),
                total: parseFloat(d.total_price)
            }];
            renderLines();
            $('#editId').val(d.id);
            $('#warehouseSelect').val(d.warehouse_id).trigger('change');
            $('#requesterSelect').val(d.requester_id).trigger('change');
            $('#customerSelect').val(d.customer_id).trigger('change');
            $('[name="note"]').val(d.note);

            $('#addModal .modal-title').html('<i class="fas fa-edit me-2"></i> Çıkış Kaydını Düzenle (#' + d.id + ')');
            $('#btnSubmitText').text('Güncelle');
            $('#viewModal').modal('hide');
            $('#addModal').modal('show');
        }, 'json');
    }

    function openAddModal() {
        lines = [];
        renderLines();
        $('#editId').val('');
        $('#formStockOut')[0].reset();
        $('#warehouseSelect, #requesterSelect, #customerSelect, #productAdd').val(null).trigger('change');
        $('#addModal .modal-title').html('<i class="fas fa-sign-out-alt me-2"></i> Yeni Stok Çıkışı');
        $('#btnSubmitText').text('Çıkışı Kaydet');
        $('#addModal').modal('show');
    }

    function renderLines() {
        if (!lines.length) { $('#lineContainer').hide(); return; }
        $('#lineContainer').show();
        var html = '', totalSum = 0;
        $.each(lines, function (i, l) {
            totalSum += l.total;
            html += '<tr>' +
                '<td>' + esc(l.product_name) + '</td>' +
                '<td class="num-align">' + (+l.quantity) + ' ' + esc(l.unit) + '</td>' +
                '<td class="num-align">' + formatTurkish(l.unit_price.toFixed(4)) + '</td>' +
                '<td class="num-align"><strong>' + formatTurkish(l.total.toFixed(2)) + '</strong></td>' +
                '<td class="text-center"><button type="button" class="btn btn-xs btn-link text-danger p-0" onclick="removeLine(' + i + ')"><i class="fas fa-times"></i></button></td>' +
                '</tr>';
        });
        $('#lineBody').html(html);
        $('#totalSumLabel').text(formatTurkish(totalSum.toFixed(2)) + ' EUR');
    }

    function removeLine(i) { lines.splice(i, 1); renderLines(); }

    function viewRow(id) {
        $('#viewBody').html('<div class="text-center p-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
        $('#viewModal').modal('show');
        $.get(apiUrl, { action: 'get', id: id }, function (r) {
            if (!r.success) { $('#viewBody').html('<div class="alert alert-danger">' + r.message + '</div>'); return; }
            var d = r.data;
            var html = '<div class="modal-section-label"><i class="fas fa-map-marker-alt"></i> Konum & Muhatap Bilgileri</div>' +
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
                '<th style="width:150px" class="num-align">Birim (EUR)</th>' +
                '<th style="width:150px" class="num-align">Toplam</th>' +
                '</tr></thead>' +
                '<tbody><tr>' +
                '<td>' + esc(d.product_name) + '</td>' +
                '<td class="num-align">' + (+d.quantity) + ' ' + esc(d.unit) + '</td>' +
                '<td class="num-align">' + formatTurkish(parseFloat(d.unit_price).toFixed(4)) + '</td>' +
                '<td class="num-align"><strong>' + formatTurkish(parseFloat(d.total_price).toFixed(2)) + '</strong></td>' +
                '</tr></tbody>' +
                '<tfoot class="bg-light fw-bold">' +
                '<tr><td colspan="3" class="num-align">TOPLAM:</td><td class="num-align">' + formatTurkish(parseFloat(d.total_price).toFixed(2)) + ' EUR</td></tr>' +
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
            $('#btnMoveToEdit').show().off('click').on('click', function () { editRow(d.id); });
        }, 'json');
    }

    $(document).ready(function () {
        load();

        // Select2 Styles & Logic
        $('#warehouseSelect').select2({ theme: 'bootstrap-5', placeholder: '— Depo Seçin —', width: '100%', dropdownParent: $('#addModal') });

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
            ajax: { url: '<?= BASE_URL ?>/api/products.php', data: function (p) { return { action: 'search_select2', q: p.term || '' }; }, processResults: function (d) { return { results: d.results }; }, delay: 300 },
            templateResult: function (i) { if (i.loading) return i.text; var no = '<?= BASE_URL ?>/assets/no-image.png', img = i.image ? '<?= BASE_URL ?>/images/UrunResim/' + i.image : no; return $('<span><img src="' + img + '" class="select2-product-img" onerror="this.src=\'' + no + '\'"> ' + esc(i.text) + '</span>'); }
        });

        $('#productAdd').on('select2:select', function (e) {
            $('#unitAddLabel').text(e.params.data.unit || 'Adet');
            $('#qtyInput').val('').focus();
        });

        $('#btnAddLine').on('click', function () {
            var sel = $('#productAdd').select2('data');
            if (!sel || !sel[0] || !sel[0].id) { showError('Lütfen bir ürün seçin.'); return; }
            var qty = parseFloat($('#qtyInput').val());
            if (!qty || qty <= 0) { showError('Geçerli bir adet girin.'); return; }

            var productId = sel[0].id, productName = sel[0].text, unit = $('#unitAddLabel').text(), warehouseId = $('#warehouseSelect').val();
            if (!warehouseId) { showError('Önce depo seçmelisiniz.'); return; }

            $.get(apiUrl, { action: 'get_last_price', product_id: productId, warehouse_id: warehouseId }, function (r) {
                var unitPrice = r.success && r.data ? parseFloat(r.data.price_eur) : 0;
                lines.push({ product_id: productId, product_name: productName, quantity: qty, unit: unit, unit_price: unitPrice, total: unitPrice * qty });
                renderLines();
                $('#productAdd').val(null).trigger('change');
                $('#qtyInput').val('');
                $('#unitAddLabel').text('Adet');
            }, 'json');
        });

        $('#btnSubmitOut').on('click', function () {
            if (!lines.length) { showError('En az 1 ürün ekleyin.'); return; }
            var btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Kaydediliyor...');

            var data = {
                action: $('#editId').val() ? 'edit' : 'add',
                id: $('#editId').val(),
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