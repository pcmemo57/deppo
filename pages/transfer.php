<?php
/**
 * Depolar Arası Transfer & Geçmiş
 */
requireRole(ROLE_ADMIN, ROLE_USER);
$warehouses = Database::fetchAll("SELECT id,name FROM tbl_dp_warehouses WHERE hidden=0 AND is_active=1 ORDER BY name");
?>
<style>
    /* Select2 Modal Border & Height Fix */
    .select2-container--bootstrap-4 .select2-selection {
        border: 1px solid #ced4da !important;
        border-radius: 0.25rem !important;
        height: calc(2.25rem + 2px) !important;
    }

    .select2-container--bootstrap-4 .select2-selection--single .select2-selection__rendered {
        line-height: 2.25rem !important;
        padding-left: 0.75rem !important;
    }

    .select2-container--bootstrap-4 .select2-selection--single .select2-selection__arrow {
        height: 2.25rem !important;
    }

    .select2-container--bootstrap-4.select2-container--focus .select2-selection {
        border-color: #80bdff !important;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
    }

    /* ───────────────────────────────────────────
     EXPANDABLE ROWS
  ─────────────────────────────────────────── */
    .transfer-row {
        cursor: pointer;
        transition: background 0.2s;
    }

    .transfer-row:hover {
        background: #f8f9fa !important;
    }

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

    /* ───────────────────────────────────────────
     MODAL GENEL (Premium Style)
  ─────────────────────────────────────────── */
    #transferModal .modal-content,
    #detailModal .modal-content {
        border: none;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.18);
    }

    #transferModal .modal-header {
        background: linear-gradient(135deg, #1a56db 0%, #0c3daa 100%);
        padding: 20px 28px;
        border-bottom: none;
    }

    #detailModal .modal-header {
        background: linear-gradient(135deg, #6b7280 0%, #374151 100%);
        padding: 20px 28px;
        border-bottom: none;
    }

    #transferModal .modal-title,
    #detailModal .modal-title {
        font-size: 1.05rem;
        font-weight: 600;
        letter-spacing: 0.01em;
        color: #fff;
    }

    #transferModal .modal-body,
    #detailModal .modal-body {
        padding: 28px 32px 12px;
        background: #f8fafd;
    }

    #transferModal .modal-footer,
    #detailModal .modal-footer {
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
    #transferModal .form-control,
    #detailModal .form-control {
        border: 1.5px solid #d1d9e6;
        border-radius: 8px;
        padding: 9px 13px !important;
        font-size: 0.88rem;
        color: #1f2937;
        background: #fff;
        transition: border-color 0.2s, box-shadow 0.2s;
        height: auto !important;
    }

    #transferModal .form-control:focus,
    #detailModal .form-control:focus {
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

    .input-icon-wrap .form-control {
        padding-left: 32px !important;
    }

    .input-icon-wrap textarea.form-control {
        padding-left: 13px !important;
    }

    /* Butonlar */
    .btn-modal-cancel {
        background: transparent;
        border: 1.5px solid #c9d3e0;
        color: #4a5568 !important;
        border-radius: 8px;
        padding: 9px 22px;
        font-size: 0.87rem;
        font-weight: 500;
        transition: all 0.2s;
    }

    .btn-modal-cancel:hover {
        background: #f0f4f9;
        border-color: #a0aec0;
        color: #1f2937 !important;
    }

    .btn-modal-save {
        background: linear-gradient(135deg, #1a56db, #0c3daa);
        border: none;
        color: #fff !important;
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
        color: #fff !important;
    }

    /* Select2 boostrap-4 focus styling overrides for our custom modal form controls */
    .select2-container--bootstrap-4 .select2-selection {
        border: 1.5px solid #d1d9e6 !important;
        border-radius: 8px !important;
        height: auto !important;
        min-height: 40px !important;
        padding: 3px 10px 3px 32px !important;
    }

    .select2-container--bootstrap-4 .select2-selection--single .select2-selection__rendered {
        line-height: normal !important;
        padding-top: 5px !important;
        padding-left: 0 !important;
    }

    .select2-container--bootstrap-4 .select2-selection--single .select2-selection__arrow {
        height: 38px !important;
    }

    .select2-container--bootstrap-4.select2-container--focus .select2-selection {
        border-color: #1a56db !important;
        box-shadow: 0 0 0 3px rgba(26, 86, 219, 0.12) !important;
    }
</style>
<div class="row">
    <div class="col-12">
        <div class="card card-warning card-outline">
            <div class="card-header">
                <h3 class="card-title text-bold"><i class="fas fa-exchange-alt me-2"></i>Depolar Arası Transfer</h3>
                <div class="card-tools d-flex gap-2">
                    <select id="perPage" class="form-select form-select-sm" style="width:auto">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <div class="input-group input-group-sm" style="width: 200px;">
                        <input type="text" id="searchBox" class="form-control" placeholder="Ara...">
                        <div class="input-group-append">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                    </div>
                    <button class="btn btn-success btn-sm" onclick="openTransferModal()">
                        <i class="fas fa-plus me-1"></i>Yeni Transfer Yap
                    </button>
                </div>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover table-striped m-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th style="width:50px" class="ps-3 text-center">#</th>
                            <th>Kaynak Depo</th>
                            <th>Hedef Depo</th>
                            <th class="num-align">Kalem Sayısı</th>
                            <th>Tarih</th>
                            <th style="width:60px" class="text-center pe-3">Detay</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <tr>
                            <td colspan="6" class="text-center p-4"><i class="fas fa-spinner fa-spin"></i> Yükleniyor...
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

<!-- Transfer Modal -->
<div class="modal fade" id="transferModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-truck-moving me-2"></i>Yeni Depo Transferi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formTransfer">
                    <div class="modal-section-label">
                        <i class="fas fa-map-marker-alt"></i> Konum Bilgileri
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kaynak Depo *</label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-warehouse field-icon"></i>
                                <select id="fromWarehouse" class="form-control select2-modal" required>
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
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Hedef Depo *</label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-warehouse field-icon text-danger"></i>
                                <select id="toWarehouse" class="form-control select2-modal" required>
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
                    </div>
                    <!-- Ürün Bilgileri -->
                    <div class="modal-section-label mt-2">
                        <i class="fas fa-boxes"></i> Ürün &amp; Miktar
                    </div>
                    <div class="row align-items-end mb-4">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Ürün Seçin *</label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-box field-icon"></i>
                                <select id="productSelect" class="form-control" style="width:100%"></select>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Miktar</label>
                            <div class="input-group input-group-sm" style="height: 40px;">
                                <input type="number" id="transferQty" class="form-control text-end" min="0.001"
                                    step="any" placeholder="0.00"
                                    style="padding-left:13px!important; height:100% !important;">
                                <div class="input-group-append h-100">
                                    <span class="input-group-text d-flex align-items-center" id="transferUnitLabel"
                                        style="border-radius: 0; background: #f8fafd; border-left: 0; border-right: 0;">Adet</span>
                                    <button type="button" class="btn btn-primary h-100" id="btnAddTransferLine"
                                        style="border-top-right-radius: 8px; border-bottom-right-radius: 8px;">
                                        <i class="fas fa-plus"></i> Ekle
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 mb-3">
                            <div id="transferLineContainer" style="display:none">
                                <div class="table-responsive"
                                    style="max-height: 200px; border-radius: 8px; border: 1.5px solid #d1d9e6;">
                                    <table class="table table-sm table-striped m-0">
                                        <thead class="bg-light text-muted small text-uppercase">
                                            <tr>
                                                <th class="ps-3 border-0">Ürün</th>
                                                <th class="num-align border-0" style="width:120px">Miktar</th>
                                                <th style="width:40px" class="border-0"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="transferLineBody"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Not Bilgisi -->
                    <div class="modal-section-label mt-2">
                        <i class="fas fa-sticky-note"></i> Ek Bilgiler
                    </div>
                    <div class="row">
                        <div class="col-12 mb-0">
                            <label class="form-label">Transfer Notu</label>
                            <textarea id="transferNote" class="form-control" rows="2" placeholder="Not..."></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn-modal-save" id="btnSubmitTransfer">Transferi Onayla</button>
            </div>
        </div>
    </div>
</div>



<script>
    var curPage = 1, curPerPage = 10, curSearch = '', searchTimer;
    var transferLines = [];
    var apiUrl = '<?= BASE_URL ?>/api/transfer.php';
    function esc(v) { return $('<span>').text(v || '').html(); }

    function load() {
        $.get(apiUrl, { action: 'list', page: curPage, per_page: curPerPage, search: curSearch }, function (r) {
            if (!r.success) { showError(r.message); return; }
            var html = '';
            $.each(r.data.data, function (i, d) {
                html += '<tr class="transfer-row" onclick="toggleDetail(' + d.id + ', this)">' +
                    '<td class="ps-3 text-center"><i class="fas fa-chevron-right expand-icon"></i></td>' +
                    '<td><div class="fw-bold text-primary">' + esc(d.source) + '</div></td>' +
                    '<td><div class="fw-bold text-danger">' + esc(d.target) + '</div></td>' +
                    '<td class="num-align"><span class="badge bg-light border text-dark ms-2" style="font-size:0.85rem; padding:5px 10px;">' + d.item_count + ' Ürün</span></td>' +
                    '<td><span class="text-muted small"><i class="far fa-calendar-alt me-1"></i> ' + d.created_at + '</span></td>' +
                    '<td class="text-center pe-3"><button class="btn btn-xs btn-outline-warning"><i class="fas fa-eye"></i></button></td>' +
                    '</tr>' +
                    '<tr class="detail-row" id="detail-' + d.id + '">' +
                    '<td colspan="6">' +
                    '<div class="detail-container">' +
                    '<div class="mb-3"><strong><i class="fas fa-info-circle me-1 text-warning"></i> Transfer Notu:</strong> <span class="text-muted">' + esc(d.note || '—') + '</span></div>' +
                    '<div id="cont-' + d.id + '"><div class="text-center p-2"><i class="fas fa-spinner fa-spin"></i></div></div>' +
                    '</div>' +
                    '</td>' +
                    '</tr>';
            });
            $('#tableBody').html(html || '<tr><td colspan="6" class="text-center text-muted p-4">Henüz transfer kaydı bulunmuyor.</td></tr>');
            $('#totalCount').text('Toplam: ' + r.data.total + ' transfer');
            renderPag(r.data.total);
        }, 'json');
    }

    function toggleDetail(id, el) {
        var row = $(el);
        var detailRow = $('#detail-' + id);

        if (detailRow.is(':visible')) {
            detailRow.hide();
            row.removeClass('row-expanded');
        } else {
            row.addClass('row-expanded');
            detailRow.show();
            loadTransferItems(id);
        }
    }

    function loadTransferItems(id) {
        var cont = $('#cont-' + id);
        if (cont.data('loaded')) return;

        $.get(apiUrl, { action: 'get_items', id: id }, function (r) {
            if (!r.success) { cont.html('<div class="text-danger">' + r.message + '</div>'); return; }

            var html = '<table class="table table-sm table-bordered m-0 bg-white shadow-sm">' +
                '<thead class="bg-light"><tr><th class="ps-3">Ürün Adı</th><th class="num-align" style="width:150px">Miktar</th></tr></thead><tbody>';

            $.each(r.data, function (i, d) {
                html += '<tr>' +
                    '<td class="ps-3">' + esc(d.product) + '</td>' +
                    '<td class="num-align"><strong class="text-success">' + formatQty(d.quantity) + '</strong> <small class="text-muted">' + esc(d.unit) + '</small></td>' +
                    '</tr>';
            });

            html += '</tbody></table>';
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



    // Modal Logic
    function openTransferModal() {
        transferLines = [];
        renderTransferLines();
        $('#formTransfer')[0].reset();
        $('#fromWarehouse, #toWarehouse, #productSelect').val(null).trigger('change');
        $('#transferModal').modal('show');
    }

    function renderTransferLines() {
        if (!transferLines.length) { $('#transferLineContainer').hide(); return; }
        $('#transferLineContainer').show();
        var html = '';
        $.each(transferLines, function (i, l) {
            html += '<tr><td>' + esc(l.product_name) + '</td><td class="num-align">' + formatQty(l.quantity) + ' ' + esc(l.unit) + '</td>';
            html += '<td class="text-center"><button type="button" class="btn btn-xs btn-outline-danger" onclick="removeTransferLine(' + i + ')"><i class="fas fa-times"></i></button></td></tr>';
        });
        $('#transferLineBody').html(html);
    }
    function removeTransferLine(i) { transferLines.splice(i, 1); renderTransferLines(); }

    $(document).ready(function () {
        load();

        $('.select2-modal').select2({
            theme: 'bootstrap-4',
            placeholder: '— Seçiniz —',
            width: '100%',
            dropdownParent: $('#transferModal')
        });

        $('#productSelect').select2({
            theme: 'bootstrap-4',
            placeholder: '— Ürün arayın —',
            width: '100%',
            dropdownParent: $('#transferModal'),
            ajax: {
                url: '<?= BASE_URL ?>/api/products.php',
                data: function (p) { return { action: 'search_select2', q: p.term || '' }; },
                processResults: function (d) { return { results: d.results }; },
                delay: 300
            }
        });

        $('#fromWarehouse').on('select2:select', function () { $(this).select2('close'); $('#toWarehouse').select2('open'); });
        $('#toWarehouse').on('select2:select', function () { $(this).select2('close'); $('#productSelect').select2('open'); });
        $('#productSelect').on('select2:select', function (e) {
            $(this).select2('close');
            $('#transferUnitLabel').text(e.params.data.unit || 'Adet');
            $('#transferQty').focus();
        });

        $('#btnAddTransferLine').on('click', function () {
            var sel = $('#productSelect').select2('data');
            if (!sel || !sel[0] || !sel[0].id) { showError('Lütfen ürün seçin.'); return; }
            var qty = parseFloat($('#transferQty').val());
            if (!qty || qty <= 0) { showError('Geçerli bir miktar girin.'); return; }
            var pid = sel[0].id, pname = sel[0].text, unit = sel[0].unit || 'Adet';
            var found = false;
            $.each(transferLines, function (i, l) { if (l.product_id == pid) { l.quantity += qty; found = true; return false; } });
            if (!found) transferLines.push({ product_id: pid, product_name: pname, quantity: qty, unit: unit });
            renderTransferLines();
            $('#productSelect').val(null).trigger('change').select2('open');
            $('#transferQty').val('');
        });

        $('#btnSubmitTransfer').on('click', function () {
            if (!transferLines.length) { showError('En az 1 ürün ekleyin.'); return; }
            var srcId = $('#fromWarehouse').val(), tgtId = $('#toWarehouse').val();
            if (!srcId || !tgtId) { showError('Kaynak ve hedef depo seçin.'); return; }
            if (srcId === tgtId) { showError('Kaynak ve hedef aynı olamaz.'); return; }

            var btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> İşleniyor...');

            $.post(apiUrl, {
                action: 'add', source_warehouse_id: srcId, target_warehouse_id: tgtId, note: $('#transferNote').val(), lines: JSON.stringify(transferLines)
            }, function (r) {
                btn.prop('disabled', false).html('Transferi Onayla');
                if (r.success) {
                    showSuccess(r.message || 'Transfer başarıyla tamamlandı!');
                    $('#transferModal').modal('hide');
                    curPage = 1; load();
                } else showError(r.message);
            }, 'json').fail(function () {
                btn.prop('disabled', false).html('Transferi Onayla');
                showError('Sistem hatası oluştu.');
            });
        });

        $('#searchBox').on('input', function () { clearTimeout(searchTimer); curSearch = $(this).val(); searchTimer = setTimeout(function () { curPage = 1; load(); }, 400); });
        $('#perPage').on('change', function () { curPerPage = parseInt($(this).val()); curPage = 1; load(); });
    });
</script>