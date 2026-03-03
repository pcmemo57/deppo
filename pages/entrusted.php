<?php
requireRole(ROLE_ADMIN, ROLE_USER);
$warehouses = Database::fetchAll("SELECT id,name FROM tbl_dp_warehouses WHERE hidden=0 AND is_active=1 ORDER BY name");
?>

<style>
    /* ───────────────────────────────────────────
   MODAL TASARIMI (stock_in_list.php ile senkron)
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

    #historyModal .modal-header {
        background: linear-gradient(135deg, #343a40 0%, #212529 100%);
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

    .input-icon-wrap .form-control,
    .input-icon-wrap .form-select,
    .input-icon-wrap .select2-container {
        padding-left: 0;
    }

    .input-icon-wrap .select2-container--bootstrap-5 .select2-selection {
        padding-left: 32px !important;
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

    .select2-product-img {
        width: 32px;
        height: 32px;
        object-fit: cover;
        border-radius: 4px;
        margin-right: 10px;
        border: 1px solid #eee;
        background: #fff;
    }
</style>

<div class="row stock-out-row">
    <div class="col-12">
        <div class="card card-info card-outline">
            <div class="card-header">
                <h3 class="card-title text-bold"><i class="fas fa-hand-holding-heart me-2"></i> Emanet Yönetimi</h3>
                <div class="card-tools d-flex gap-2">
                    <select id="perPage" class="form-select form-select-sm" style="width:auto">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    <div class="input-group input-group-sm" style="width: 200px;">
                        <input type="text" id="searchBox" class="form-control" placeholder="Ara...">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                    </div>
                    <button class="btn btn-primary btn-sm px-3 shadow-sm" onclick="openAddModal()">
                        <i class="fas fa-plus me-1"></i> Yeni Emanet Ver
                    </button>
                </div>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover m-0 table-valign-middle">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th>Ürün</th>
                            <th>Alan Kişi</th>
                            <th>Depo</th>
                            <th class="num-align">Verilen</th>
                            <th class="num-align">Kalan</th>
                            <th style="width:120px" class="num-align">İade Tarihi</th>
                            <th style="width:120px">Durum</th>
                            <th style="width:150px" class="num-align">Tarih</th>
                            <th style="width:100px" class="text-center pe-3">İşlem</th>
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
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-white"><i class="fas fa-plus-circle me-2 opacity-75"></i>Yeni Emanet Girişi
                </h5>
                <button type="button" class="btn btn-link text-white p-0 border-0" data-bs-dismiss="modal"><i
                        class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form id="formEntrusted">
                    <!-- Üst Bilgiler -->
                    <div class="modal-section-label">
                        <i class="fas fa-info-circle"></i> Temel Bilgiler
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Depo <span class="text-danger">*</span></label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-warehouse field-icon"></i>
                                <select name="warehouse_id" id="warehouseSelect" class="form-select" required>
                                    <option value="">— Seçiniz —</option>
                                    <?php foreach ($warehouses as $w): ?>
                                        <option value="<?= e($w['id']) ?>" <?= count($warehouses) === 1 ? 'selected' : '' ?>>
                                            <?= e($w['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Emanet Alan Kişi <span class="text-danger">*</span></label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-user field-icon"></i>
                                <select name="requester_id" id="requesterSelect" class="form-select" required></select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tahmini İade Tarihi</label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-calendar-alt field-icon"></i>
                                <input type="date" name="expected_return_at" class="form-control">
                            </div>
                        </div>
                    </div>

                    <!-- Ürün Ekleme Alanı -->
                    <div class="modal-section-label">
                        <i class="fas fa-plus"></i> Ürün Ekle
                    </div>
                    <div class="p-3 bg-white rounded border shadow-sm mb-3">
                        <div class="row g-2">
                            <div class="col-md-8">
                                <div class="input-icon-wrap">
                                    <i class="fas fa-box field-icon"></i>
                                    <select id="productAdd" class="form-select"></select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="input-icon-wrap">
                                    <i class="fas fa-sort-numeric-up field-icon"></i>
                                    <input type="number" id="qtyInput" class="form-control" placeholder="Miktar"
                                        step="any">
                                </div>
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-success w-100" id="btnAddLine"><i
                                        class="fas fa-plus"></i></button>
                            </div>
                        </div>
                    </div>

                    <!-- Eklenen Ürünler Listesi -->
                    <div id="lineContainer" style="display:none">
                        <table class="table table-sm table-bordered bg-white shadow-sm">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-2">Ürün</th>
                                    <th style="width:100px" class="text-center">Miktar</th>
                                    <th style="width:40px"></th>
                                </tr>
                            </thead>
                            <tbody id="lineBody"></tbody>
                        </table>
                    </div>

                    <!-- Not -->
                    <div class="modal-section-label mt-4">
                        <i class="fas fa-comment-dots"></i> Not
                    </div>
                    <div class="col-12 mb-3">
                        <textarea name="note" class="form-control" rows="2" placeholder="İşlem notu..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-modal-cancel" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-modal-save px-4" id="btnSubmit">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<!-- Action Modal (Return/Sale) -->
<div class="modal fade" id="actionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-white"><i class="fas fa-exchange-alt me-2 opacity-75"></i>Emanet İşlemi</h5>
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
                <button type="button" class="btn btn-modal-cancel" data-bs-dismiss="modal">Kapat</button>
                <button type="button" class="btn btn-info text-white shadow-sm fw-bold px-4" id="btnSubmitAction">İşlemi
                    Onayla</button>
            </div>
        </div>
    </div>
</div>

<!-- History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-white"><i class="fas fa-history me-2 opacity-75"></i>Emanet Hareket Geçmişi
                </h5>
                <button type="button" class="btn btn-link text-white p-0 border-0" data-bs-dismiss="modal"><i
                        class="fas fa-times"></i></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped m-0 table-valign-middle">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4">Tarih</th>
                                <th>İşlem</th>
                                <th class="num-align">Miktar</th>
                                <th>Detay</th>
                                <th class="pe-4">Yapan</th>
                            </tr>
                        </thead>
                        <tbody id="historyBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-modal-cancel" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<script>
    var curPage = 1, curPerPage = 10, curSearch = '', searchTimer;
    var apiUrl = '<?= BASE_URL ?>/api/entrusted.php';
    var lines = [];
    var isSingleWarehouse = <?= count($warehouses) === 1 ? 'true' : 'false' ?>;

    function esc(v) { return $('<span>').text(v || '').html(); }

    function load() {
        $.get(apiUrl, { action: 'list', page: curPage, per_page: curPerPage, search: curSearch }, function (r) {
            if (!r.success) return;
            var html = '';
            $.each(r.data.data, function (i, d) {
                var statusBadge = '';
                if (d.status == 0) statusBadge = '<span class="badge bg-warning">Emanette</span>';
                else if (d.status == 1) statusBadge = '<span class="badge bg-success">İade Edildi</span>';
                else if (d.status == 2) statusBadge = '<span class="badge bg-danger">Müşteriye Verildi</span>';
                else statusBadge = '<span class="badge bg-secondary">Kapalı</span>';

                html += '<tr>' +
                    '<td><b>' + esc(d.product_name) + '</b></td>' +
                    '<td>' + esc(d.requester_name + ' ' + d.requester_surname) + '</td>' +
                    '<td>' + esc(d.warehouse_name) + '</td>' +
                    '<td class="num-align">' + formatQty(d.quantity) + ' <small>' + esc(d.unit) + '</small></td>' +
                    '<td class="num-align"><b class="text-primary">' + formatQty(d.remaining_quantity) + '</b></td>' +
                    '<td class="num-align text-muted">' + (d.expected_return_at_fmt || '—') + '</td>' +
                    '<td>' + statusBadge + '</td>' +
                    '<td class="num-align">' + (d.created_at_fmt || '—') + '</td>' +
                    '<td class="text-center pe-3">' +
                    '<div class="btn-group">' +
                    (d.remaining_quantity > 0 ? '<button class="btn btn-xs btn-info text-white" onclick="openActionModal(' + JSON.stringify(d).replace(/"/g, '&quot;') + ')" title="İşlem Yap"><i class="fas fa-edit"></i></button>' : '') +
                    '<button class="btn btn-xs btn-secondary" onclick="viewHistory(' + d.id + ')" title="Geçmiş"><i class="fas fa-history"></i></button>' +
                    '</div>' +
                    '</td></tr>';
            });
            $('#tableBody').html(html || '<tr><td colspan="9" class="text-center p-4">Kayıt bulunamadı</td></tr>');
            $('#totalCount').text('Toplam: ' + formatQty(r.data.total) + ' kayıt');
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

    function openAddModal() {
        lines = [];
        renderLines();
        $('#formEntrusted')[0].reset();
        $('#requesterSelect, #productAdd').val(null).trigger('change');
        if (!isSingleWarehouse) {
            $('#warehouseSelect').val(null).trigger('change');
        } else {
            $('#warehouseSelect').trigger('change');
        }

        // Alanları aktif bırakıyoruz (sıralı girişe zorlamıyoruz)
        $('#requesterSelect, [name="expected_return_at"], #productAdd, #qtyInput, #btnAddLine').prop('disabled', false);

        $('#addModal').modal('show');
        // Modal tamamen açılınca ilk alana odaklan
        setTimeout(() => { $('#warehouseSelect').select2('open'); }, 400);
    }

    function renderLines() {
        if (!lines.length) { $('#lineContainer').hide(); return; }
        $('#lineContainer').show();
        var html = '';
        $.each(lines, function (i, l) {
            html += '<tr><td>' + esc(l.product_name) + '</td><td>' + formatQty(l.quantity) + '</td>' +
                '<td><button type="button" class="btn btn-xs btn-link text-danger p-0" onclick="removeLine(' + i + ')"><i class="fas fa-times"></i></button></td></tr>';
        });
        $('#lineBody').html(html);
    }
    function removeLine(i) { lines.splice(i, 1); renderLines(); }

    function openActionModal(d) {
        $('#actionId').val(d.id);
        $('#actionProduct').text(d.product_name);

        // Quantity formatting fix
        var rem = parseFloat(d.remaining_quantity) || 0;

        $('#actionRemaining').text(formatQty(rem) + ' ' + d.unit);
        $('#actionQty').val(rem).attr('max', rem);
        $('#actionNote').val('');

        // Reset toggle to "Return"
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
            $('#set_sale').addClass('inactive-state'); // Red color for "Sale" (matches Customers.php behavior for inactive)
            $('#customerDiv').show();
        }
    }

    function viewHistory(id) {
        $('#historyBody').html('<tr><td colspan="5" class="text-center p-3"><i class="fas fa-spinner fa-spin"></i></td></tr>');
        $('#historyModal').modal('show');
        $.get(apiUrl, { action: 'get_history', id: id }, function (r) {
            var html = '';
            $.each(r.data, function (i, h) {
                var type = h.action_type === 'return' ? '<span class="text-success"><i class="fas fa-undo"></i> İade</span>' : '<span class="text-danger"><i class="fas fa-shopping-cart"></i> Satış</span>';
                html += '<tr>' +
                    '<td class="ps-3 small">' + h.created_at_fmt + '</td>' +
                    '<td>' + type + '</td>' +
                    '<td class="num-align"><b>' + formatQty(h.quantity) + '</b></td>' +
                    '<td><small>' + esc(h.customer_name || h.note || '—') + '</small></td>' +
                    '<td><small>' + esc(h.created_by_name) + '</small></td>' +
                    '</tr>';
            });
            $('#historyBody').html(html || '<tr><td colspan="5" class="text-center p-3 text-muted">Hareket kaydı yok.</td></tr>');
        }, 'json');
    }

    $(document).ready(function () {
        load();

        $('#warehouseSelect').select2({
            theme: 'bootstrap-5', placeholder: '— Seçiniz —', width: '100%', dropdownParent: $('#addModal')
        }).on('select2:select', function () {
            $(this).select2('close');
            $('#requesterSelect').prop('disabled', false);
            setTimeout(() => { $('#requesterSelect').select2('open'); }, 50);
        });

        $('#requesterSelect').select2({
            theme: 'bootstrap-5', placeholder: '— Seçiniz —', width: '100%', dropdownParent: $('#addModal'),
            ajax: { url: '<?= BASE_URL ?>/api/requesters.php', data: function (p) { return { action: 'active_list', search: p.term || '' }; }, processResults: function (d) { return { results: $.map(d.data, function (u) { return { id: u.id, text: u.name + ' ' + u.surname }; }) }; }, delay: 300 }
        }).on('select2:select', function () {
            $(this).select2('close');
            $('[name="expected_return_at"]').prop('disabled', false);
            setTimeout(() => { $('[name="expected_return_at"]').focus(); }, 50);
        });

        $('[name="expected_return_at"]').on('keydown', function (e) {
            if (e.which == 13) {
                e.preventDefault();
                $('#productAdd').prop('disabled', false);
                setTimeout(() => { $('#productAdd').select2('open'); }, 50);
            }
        });

        var currentStock = 0;
        $('#productAdd').select2({
            theme: 'bootstrap-5', placeholder: '— Ürün arayın —', width: '100%', dropdownParent: $('#addModal'),
            ajax: {
                url: '<?= BASE_URL ?>/api/products.php',
                data: function (p) {
                    return {
                        action: 'search_select2',
                        q: p.term || '',
                        warehouse_id: $('#warehouseSelect').val()
                    };
                },
                processResults: function (d) {
                    var warehouseId = $('#warehouseSelect').val();
                    var results = $.map(d.results, function (item) {
                        if (warehouseId && parseFloat(item.stock || 0) <= 0) {
                            item.disabled = true;
                        }
                        return item;
                    });
                    return { results: results };
                },
                delay: 300
            },
            templateResult: function (i) {
                if (i.loading) return i.text;
                var no = '<?= BASE_URL ?>/assets/no-image.png', img = i.image ? '<?= BASE_URL ?>/images/UrunResim/' + i.image : no;
                var stockVal = parseFloat(i.stock || 0);
                var badgeClass = stockVal <= 0 ? 'bg-danger' : 'bg-success';
                var stockInfo = i.id ? ' <span class="badge ' + badgeClass + ' float-end">' + formatQty(stockVal) + ' ' + (i.unit || '') + '</span>' : '';
                return $('<span><img src="' + img + '" class="select2-product-img" onerror="this.src=\'' + no + '\'"> ' + esc(i.text) + stockInfo + '</span>');
            }
        }).on('select2:select', function (e) {
            var data = e.params.data;
            currentStock = parseFloat(data.stock || 0);
            $(this).select2('close');
            $('#qtyInput').prop('disabled', false);
            $('#btnAddLine').prop('disabled', false);
            setTimeout(() => { $('#qtyInput').focus().select(); }, 50);
        });

        $('#btnAddLine').on('click', function () {
            var sel = $('#productAdd').select2('data');
            if (!sel || !sel[0]) return;
            var qty = parseFloat($('#qtyInput').val());
            if (!qty || qty <= 0) return;

            // Stok kontrolü
            if (qty > currentStock) {
                showError('Yetersiz stok! Mevcut: ' + formatQty(currentStock));
                return;
            }

            lines.push({ product_id: sel[0].id, product_name: sel[0].text, quantity: qty });
            renderLines();
            $('#productAdd').val(null).trigger('change');
            $('#qtyInput').val('');
            currentStock = 0;
            // Tekrar ürün seçimine dön (multi-line döngüsü)
            setTimeout(() => { $('#productAdd').select2('open'); }, 50);
        });

        $('#actionCustomerId').select2({
            theme: 'bootstrap-5', placeholder: '— Müşteri Seçin —', width: '100%', dropdownParent: $('#actionModal'),
            ajax: { url: '<?= BASE_URL ?>/api/customers.php', data: function (p) { return { action: 'active_list', search: p.term || '' }; }, processResults: function (d) { return { results: $.map(d.data, function (u) { return { id: u.id, text: u.name }; }) }; }, delay: 300 }
        });

        // Initial state for action modal toggle is set in openActionModal via setActionType


        $('#qtyInput').on('keydown', function (e) {
            if (e.which == 13) {
                e.preventDefault();
                $('#btnAddLine').click();
            }
        });

        $('#btnSubmit').on('click', function () {
            if (!lines.length) { showError('En az 1 ürün ekleyin.'); return; }
            $.post(apiUrl, {
                action: 'add',
                warehouse_id: $('#warehouseSelect').val(),
                requester_id: $('#requesterSelect').val(),
                expected_return_at: $('[name="expected_return_at"]').val(),
                note: $('[name="note"]').val(),
                lines: JSON.stringify(lines)
            }, function (r) {
                if (r.success) { showSuccess(r.message); $('#addModal').modal('hide'); load(); }
                else showError(r.message);
            }, 'json');
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

            $.post(apiUrl, {
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
                    load();
                } else {
                    showError(r.message);
                }
            }, 'json').always(function () {
                btn.prop('disabled', false).html(btnHtml);
            });
        });

        $('#searchBox').on('input', function () { clearTimeout(searchTimer); curSearch = $(this).val(); searchTimer = setTimeout(function () { curPage = 1; load(); }, 400); });
    });
</script>