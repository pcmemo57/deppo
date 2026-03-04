<?php
/**
 * Toplu Stok Güncelleme — Sadece Admin
 */
requireRole(ROLE_ADMIN);
$warehouses = Database::fetchAll("SELECT id, name FROM tbl_dp_warehouses WHERE hidden=0 AND is_active=1 ORDER BY name");
?>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius: 16px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-4 px-4">
                <div class="row align-items-center g-3">
                    <div class="col-12 col-lg-4">
                        <div class="d-flex align-items-center">
                            <div class="icon-shape bg-warning text-white rounded-3 shadow-sm d-flex align-items-center justify-content-center me-3"
                                style="width: 48px; height: 48px; min-width: 48px;">
                                <i class="fas fa-boxes fa-lg"></i>
                            </div>
                            <div>
                                <h3 class="h5 mb-0 fw-bold text-dark">Toplu Stok Güncelleme</h3>
                                <p class="text-muted small mb-0">Hızlı ve güvenli stok düzenleme paneli</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-8 col-lg-8 text-md-end">
                        <div class="d-flex align-items-center justify-content-md-end gap-3 h-100 mt-md-4">
                            <button class="btn btn-outline-warning btn-sm fw-bold px-4 py-2 shadow-xs" 
                                    data-bs-toggle="modal" data-bs-target="#warehouseModal" 
                                    style="border-radius: 10px; border-color: #e2e8f0; color: #64748b;">
                                <i class="fas fa-warehouse me-2 text-warning"></i>Depo Seç
                            </button>
                            <div class="input-group search-group shadow-xs" style="max-width: 280px;">
                                <span class="input-group-text bg-white border-end-0 text-muted ps-3">
                                    <i class="fas fa-search small"></i>
                                </span>
                                <input type="text" id="productSearch" class="form-control border-start-0 ps-0" placeholder="Ürünlerde ara...">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-header bg-light border-bottom-0 py-3 px-4">
                <div id="selectedWarehouses" class="d-flex flex-wrap gap-3">
                    <span class="text-muted small">Herhangi bir depo seçilmedi</span>
                </div>
            </div>

            <div class="card-body p-0 table-responsive" style="min-height: 500px; border-top: 1px solid #f0f0f0;">
                <table class="table table-hover table-striped m-0 align-middle" id="bulkUpdateTable">
                    <thead class="bg-light sticky-top shadow-sm">
                        <tr id="tableHead">
                            <th style="width: 70px;" class="ps-3 text-center">Resim</th>
                            <th style="min-width: 200px;">Ürün Bilgisi</th>
                            <th style="width: 240px;">Birim Fiyat & Para Birimi</th>
                            <!-- Dinamik depolar -->
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <tr>
                            <td colspan="3" class="text-center p-5 text-muted">
                                <i class="fas fa-warehouse fa-3x mb-3 d-block opacity-25"></i>
                                Lütfen güncellenecek depoları seçin.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                class="card-footer bg-white border-top d-flex flex-column flex-md-row justify-content-between align-items-center p-4 gap-3">
                <div class="d-flex align-items-center text-secondary small">
                    <div class="bg-warning-light text-warning rounded-circle p-2 me-3"
                        style="background: rgba(255,193,7,0.1);">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div>
                        Sadece <strong>adedi değişen</strong> ürünler için kayıt oluşturulacaktır.<br>
                        Günün kurları üzerinden TL karşılığı otomatik hesaplanır.
                    </div>
                </div>
                <button id="btnSaveAll" class="btn btn-warning btn-lg px-5 fw-bold shadow-sm rounded-pill" disabled
                    style="min-width: 250px;">
                    <i class="fas fa-save me-2"></i>DEĞİŞİKLİKLERİ KAYDET
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Warehouse Selection Modal -->
<div class="modal fade" id="warehouseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header bg-warning text-white border-0 py-3">
                <h5 class="modal-title fw-bold"><i class="fas fa-warehouse me-3"></i>Depo Seçimi</h5>
                <button type="button" class="btn btn-link text-white p-0 border-0" data-bs-dismiss="modal"><i
                        class="fas fa-times"></i></button>
            </div>
            <div class="modal-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($warehouses as $w): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-4 warehouse-item"
                            style="cursor: pointer; transition: background 0.2s;"
                            onclick="toggleWarehouse(<?= (int) $w['id'] ?>)">
                            <span class="fw-500 text-dark d-flex align-items-center">
                                <i class="fas fa-store me-3 text-muted small opacity-75"></i><?= e($w['name']) ?>
                            </span>
                            <div class="premium-switch">
                                <input class="wh-switch d-none" type="checkbox" value="<?= e($w['id']) ?>"
                                    data-name="<?= e($w['name']) ?>" id="wh_<?= e($w['id']) ?>">
                                <label class="switch-label mb-0"></label>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnSelectAll">Hepsini Seç</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnDeselectAll">Temizle</button>
                <button type="button" class="btn btn-warning btn-sm px-4" data-bs-dismiss="modal">Tamam</button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Header & Icon */
    .icon-shape {
        transition: transform 0.3s ease;
    }

    .card:hover .icon-shape {
        transform: scale(1.05);
    }

    /* Search Group Styling */
    .search-group {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        overflow: hidden;
        transition: all 0.2s ease;
        background: #fff;
    }

    .search-group:focus-within {
        border-color: #ffc107;
        box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.1);
    }

    .search-group .form-control {
        border: none !important;
        box-shadow: none !important;
        padding: 10px 15px;
        font-size: 0.9rem;
    }

    .search-group .input-group-text {
        border: none !important;
        padding-right: 0;
    }

    /* Select2 Adjustment for Header */
    .select2-container--bootstrap-5 .select2-selection {
        border-radius: 10px !important;
        border-color: #e2e8f0 !important;
        min-height: 42px !important;
        display: flex !important;
        align-items: center !important;
    }

    .select2-container--bootstrap-5.select2-container--focus .select2-selection {
        border-color: #ffc107 !important;
        box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.1) !important;
    }

    /* Table Styling */
    #bulkUpdateTable thead th {
        font-weight: 600;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        background: #f8fafc;
        border-bottom: 2px solid #edf2f7;
        padding: 16px 10px;
    }

    #bulkUpdateTable tbody td {
        padding: 12px 10px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.95rem;
    }

    /* Input Group Synergy */
    .synced-input-group {
        display: flex;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.2s ease;
        background: #fff;
    }

    .synced-input-group:focus-within {
        border-color: #ffc107;
        box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.15);
    }

    .synced-input-group .form-control {
        border: none !important;
        box-shadow: none !important;
        padding: 8px 12px;
        font-weight: 600;
        text-align: right;
    }

    .synced-input-group .form-select {
        border: none !important;
        border-left: 1px solid #e2e8f0 !important;
        box-shadow: none !important;
        background-color: #f8fafc;
        width: 80px;
        flex: none;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.85rem;
    }

    /* Quantity Inputs */
    .qty-input-box {
        max-width: 120px;
        margin: 0 auto;
    }

    .qty-input {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        text-align: center;
        font-weight: 700;
        transition: all 0.2s ease;
    }

    .qty-input:focus {
        border-color: #ffc107;
        box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.15);
    }

    .qty-input.changed {
        background-color: #fffbeb !important;
        border-color: #f59e0b !important;
        color: #92400e !important;
    }

    .current-qty-badge {
        font-size: 0.75rem;
        color: #94a3b8;
        margin-top: 4px;
        display: block;
    }

    /* Product Info Card */
    .product-cell {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .product-img {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .sticky-top {
        top: -1px;
        /* Antialiasing gap fix */
    }

    /* Premium Switch (from stock_status) */
    .premium-switch {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 22px;
        vertical-align: middle;
        pointer-events: none;
    }

    .premium-switch .switch-label {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #e4e9f0;
        transition: .3s;
        border-radius: 22px;
    }

    .premium-switch .switch-label:before {
        position: absolute;
        content: "";
        height: 16px;
        width: 16px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .3s;
        border-radius: 50%;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .wh-switch:checked+.switch-label {
        background-color: #f59e0b;
    }

    .wh-switch:checked+.switch-label:before {
        transform: translateX(22px);
    }

    .warehouse-item:hover {
        background-color: #fdf6e7 !important;
    }

    .fw-500 {
        font-weight: 500;
        font-size: 0.95rem;
    }

    /* Warehouse Pastel Badge Colors (from stock_status) */
    .wh-badge-0 {
        background-color: #e3f2fd !important;
        color: #0d47a1 !important;
        border: 1px solid #bbdefb !important;
    }

    .wh-badge-1 {
        background-color: #f1f8e9 !important;
        color: #33691e !important;
        border: 1px solid #dcedc8 !important;
    }

    .wh-badge-2 {
        background-color: #fff3e0 !important;
        color: #e65100 !important;
        border: 1px solid #ffe0b2 !important;
    }

    .wh-badge-3 {
        background-color: #f3e5f5 !important;
        color: #4a148c !important;
        border: 1px solid #e1bee7 !important;
    }

    .wh-badge-4 {
        background-color: #e0f2f1 !important;
        color: #004d40 !important;
        border: 1px solid #b2dfdb !important;
    }

    .wh-badge-5 {
        background-color: #fffde7 !important;
        color: #f57f17 !important;
        border: 1px solid #fff9c4 !important;
    }

    .wh-badge-6 {
        background-color: #fbe9e7 !important;
        color: #bf360c !important;
        border: 1px solid #ffccbc !important;
    }

    .wh-badge-7 {
        background-color: #e1f5fe !important;
        color: #01579b !important;
        border: 1px solid #b3e5fc !important;
    }
</style>

<script>
    $(function () {
        // Formatter Helpers
        function formatPrice(val) { return typeof formatTurkish === 'function' ? formatTurkish(val, 2) : val; }
        function formatQty(val) { return typeof formatTurkish === 'function' ? formatTurkish(val, 0) : val; }

        // Warehouse Color Mapping (from stock_status)
        const warehouseColors = {};
        <?php
        foreach ($warehouses as $index => $w) {
            $colorIdx = $index % 8;
            echo "warehouseColors['" . addslashes($w['name']) . "'] = 'wh-badge-$colorIdx';\n";
        }
        ?>

        function getWarehouseBadgeClass(name) {
            return warehouseColors[name] || 'bg-light text-dark border';
        }

        window.toggleWarehouse = function (id) {
            var cb = $('#wh_' + id);
            cb.prop('checked', !cb.prop('checked')).trigger('change');
        };

        function getSelectedWarehouses() {
            var ids = [];
            $('.wh-switch:checked').each(function () { ids.push($(this).val()); });
            return ids;
        }

        function updateBadges() {
            var badges = '';
            $('.wh-switch:checked').each(function () {
                var name = $(this).data('name');
                var colorClass = getWarehouseBadgeClass(name);
                badges += '<span class="badge ' + colorClass + ' px-3 py-2">' + name + '</span>';
            });
            if (!badges) badges = '<span class="text-muted small">Herhangi bir depo seçilmedi</span>';
            $('#selectedWarehouses').html(badges);
        }

        let searchTimer;
        $(document).on('change', '.wh-switch', function () {
            updateBadges();
            loadData();
        });

        $('#btnSelectAll').on('click', function () { $('.wh-switch').prop('checked', true).trigger('change'); });
        $('#btnDeselectAll').on('click', function () { $('.wh-switch').prop('checked', false).trigger('change'); });

        $('#productSearch').on('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(loadData, 500);
        });

        function loadData() {
            const whs = getSelectedWarehouses();
            const search = $('#productSearch').val();

            if (whs.length === 0) {
                $('#tableHead').html('<th style="width: 70px;" class="ps-3 text-center">Resim</th><th>Ürün Bilgisi</th><th style="width: 240px;">Birim Fiyat & Para Birimi</th>');
                $('#tableBody').html('<tr><td colspan="3" class="text-center p-5 text-muted"><i class="fas fa-warehouse fa-3x mb-3 d-block opacity-25"></i>Lütfen güncellenecek depoları seçin.</td></tr>');
                $('#btnSaveAll').prop('disabled', true);
                return;
            }

            $('#tableBody').html(`<tr><td colspan="${3 + whs.length}" class="text-center p-5"><i class="fas fa-spinner fa-spin fa-2x text-warning opacity-50"></i></td></tr>`);

            $.get('<?= BASE_URL ?>/api/bulk_stock_update.php', {
                action: 'list_products',
                warehouses: whs.join(','),
                search: search
            }, function (r) {
                if (r.success) {
                    renderTable(r.data.products, whs);
                } else {
                    showError(r.message);
                }
            });
        }

        function renderTable(products, whs) {
            // Headers
            let headHtml = '<th style="width: 70px;" class="ps-3 text-center">Resim</th><th>Ürün Bilgisi</th><th style="width: 240px;">Birim Fiyat & Para Birimi</th>';
            $('.wh-switch:checked').each(function () {
                headHtml += `<th class="text-center" style="min-width: 140px;">${$(this).data('name')}</th>`;
            });
            $('#tableHead').html(headHtml);

            // Body
            if (products.length === 0) {
                $('#tableBody').html(`<tr><td colspan="${3 + whs.length}" class="text-center p-5 text-muted">Arama kriterine uygun ürün bulunamadı.</td></tr>`);
                $('#btnSaveAll').prop('disabled', true);
                return;
            }

            let bodyHtml = '';
            products.forEach(p => {
                const imgSrc = p.image ? '<?= BASE_URL ?>/images/UrunResim/' + p.image : '<?= BASE_URL ?>/assets/no-image.png';
                bodyHtml += `
                <tr data-id="${p.id}">
                    <td class="text-center ps-3">
                        <img src="${imgSrc}" class="product-img shadow-sm border">
                    </td>
                    <td>
                        <div class="fw-bold text-dark">${p.name}</div>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <span class="badge bg-light text-secondary border font-weight-normal">${p.code || 'Kodsuz'}</span>
                            <span class="text-muted small">${p.unit}</span>
                        </div>
                    </td>
                    <td>
                        <div class="synced-input-group shadow-xs">
                            <input type="number" step="any" class="form-control price-input" 
                                   data-pid="${p.id}" data-old-price="${p.last_price}" value="${p.last_price || ''}" placeholder="0.00">
                            <select class="form-select currency-select" data-pid="${p.id}" data-old-curr="${p.last_currency || 'TL'}">
                                <option value="TL" ${p.last_currency === 'TL' ? 'selected' : ''}>TL</option>
                                <option value="USD" ${p.last_currency === 'USD' ? 'selected' : ''}>USD</option>
                                <option value="EUR" ${p.last_currency === 'EUR' ? 'selected' : ''}>EUR</option>
                            </select>
                        </div>
                        <div class="small text-muted mt-1 text-end" style="font-size:0.75rem">
                            Son Alış: <span class="text-primary font-weight-bold">${formatPrice(p.last_price)} ${p.last_currency || 'TL'}</span>
                        </div>
                    </td>
            `;
                whs.forEach(wid => {
                    const currentStock = p.stocks[wid] || 0;
                    bodyHtml += `
                    <td class="text-center">
                        <div class="qty-input-box mx-auto">
                            <input type="number" step="any" class="form-control qty-input form-control-sm" 
                                   data-pid="${p.id}" data-wid="${wid}" data-old="${currentStock}" value="${currentStock}">
                            <span class="current-qty-badge">Mevcut: <strong>${formatQty(currentStock)}</strong></span>
                        </div>
                    </td>
                `;
                });
                bodyHtml += '</tr>';
            });
            $('#tableBody').html(bodyHtml);
            $('#btnSaveAll').prop('disabled', false);

            // Change detection
            $('.qty-input, .price-input, .currency-select').on('input change', function () {
                const isQty = $(this).hasClass('qty-input');
                const isPrice = $(this).hasClass('price-input');
                const isCurr = $(this).hasClass('currency-select');

                let changed = false;
                if (isQty) {
                    const oldVal = parseFloat($(this).data('old'));
                    const newVal = parseFloat($(this).val());
                    changed = Math.abs(oldVal - newVal) > 0.001;
                } else if (isPrice) {
                    const oldVal = parseFloat($(this).data('old-price') || 0);
                    const newVal = parseFloat($(this).val() || 0);
                    changed = Math.abs(oldVal - newVal) > 0.001;
                } else if (isCurr) {
                    const oldVal = $(this).data('old-curr');
                    const newVal = $(this).val();
                    changed = oldVal !== newVal;
                }

                if (changed) {
                    $(this).addClass('changed');
                } else {
                    $(this).removeClass('changed');
                }
            });
        }

        $('#btnSaveAll').on('click', function () {
            const updates = [];

            // 1. First process all quantity changes
            $('.qty-input.changed').each(function () {
                const pid = $(this).data('pid');
                const wid = $(this).data('wid');
                const row = $(this).closest('tr');
                const unitPrice = row.find(`.price-input[data-pid="${pid}"]`).val();
                const currency = row.find(`.currency-select[data-pid="${pid}"]`).val();

                updates.push({
                    product_id: pid,
                    warehouse_id: wid,
                    new_qty: $(this).val(),
                    unit_price: unitPrice || 0,
                    currency: currency || 'TL'
                });
            });

            // 2. Process price-only changes for rows where NO quantity changed
            $('.price-input.changed, .currency-select.changed').each(function () {
                const pid = $(this).data('pid');
                const row = $(this).closest('tr');

                // If this row already has a quantity update, skip it (price is already included)
                const hasQtyUpdate = row.find('.qty-input.changed').length > 0;
                if (hasQtyUpdate) return;

                // Check if we already added a price-only update for this product in this batch
                const alreadyAdded = updates.some(u => u.product_id === pid);
                if (alreadyAdded) return;

                // Pick the first available warehouse ID in this row's inputs to attach the 0-qty entry
                const firstWhInput = row.find('.qty-input').first();
                const wid = firstWhInput.data('wid');
                const currentQty = firstWhInput.data('old');
                const unitPrice = row.find(`.price-input[data-pid="${pid}"]`).val();
                const currency = row.find(`.currency-select[data-pid="${pid}"]`).val();

                updates.push({
                    product_id: pid,
                    warehouse_id: wid,
                    new_qty: currentQty, // Keep same qty
                    unit_price: unitPrice || 0,
                    currency: currency || 'TL'
                });
            });

            if (updates.length === 0) {
                showInfo('Herhangi bir değişiklik yapmadınız.');
                return;
            }

            confirmAction(`${updates.length} kalem veri güncellenecek. Onaylıyor musunuz?`, function () {
                $('#btnSaveAll').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>KAYDEDİLİYOR...');

                $.post('<?= BASE_URL ?>/api/bulk_stock_update.php', {
                    action: 'update_stock',
                    updates: updates,
                    csrf_token: $('meta[name="csrf-token"]').attr('content')
                }, function (r) {
                    if (r.success) {
                        showSuccess(r.message);
                        loadData();
                    } else {
                        showError(r.message);
                    }
                }).always(() => {
                    $('#btnSaveAll').prop('disabled', false).html('<i class="fas fa-save me-2"></i>DEĞİŞİKLİKLERİ KAYDET');
                });
            });
        });
    });
</script>