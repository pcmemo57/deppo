<?php
/**
 * Stok Durumu — Depo bazlı anlık stok görünümü
 */
requireRole(ROLE_ADMIN, ROLE_USER);
$warehouses = Database::fetchAll("SELECT id,name FROM tbl_dp_warehouses WHERE hidden=0 AND is_active=1 ORDER BY name");
?>
<div class="row">
    <div class="col-12">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title text-bold"><i class="fas fa-chart-bar me-2"></i>Stok Durumu</h3>
                <div class="card-tools d-flex gap-1 align-items-center">
                    <select id="perPage" class="form-select form-select-sm" style="width:auto">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="999">Tümü</option>
                    </select>
                    <div class="input-group input-group-sm" style="width: 200px;">
                        <input type="text" id="searchBox" class="form-control" placeholder="Ürün adı ara...">
                        <div class="input-group-append">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                    </div>
                    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                        data-bs-target="#warehouseModal">
                        <i class="fas fa-warehouse me-1" style="margin-right: 5px"></i>Depo Seç
                    </button>
                    <button class="btn btn-success btn-sm" id="btnExcel" title="Excel İndir">
                        <i class="fas fa-file-excel" style="margin-right: 5px"></i>Excel Çıktısı
                    </button>
                    <button class="btn btn-info btn-sm text-white" id="btnEmail" title="E-posta Gönder">
                        <i class="fas fa-envelope" style="margin-right: 5px"></i>E-posta Gönder
                    </button>
                </div>
            </div>
            <div class="card-header bg-light border-bottom-0 py-3">
                <div id="selectedWarehouses" class="d-flex flex-wrap gap-3">
                    <!-- Seçili depoların rozetleri buraya gelecek -->
                </div>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover table-striped m-0 table-valign-middle" id="stockTable">
                    <thead class="bg-light" id="stockHead"></thead>
                    <tbody id="stockBody"></tbody>
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

<!-- Warehouse Selection Modal -->
<div class="modal fade" id="warehouseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header bg-primary text-white border-0 py-3">
                <h5 class="modal-title fw-bold"><i class="fas fa-warehouse me-3"></i>Depo Seçimi</h5>
                <button type="button" class="btn btn-link text-white p-0 border-0" data-bs-dismiss="modal"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($warehouses as $w): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-4 warehouse-item" 
                            style="cursor: pointer; transition: background 0.2s;" 
                            onclick="toggleWarehouse(<?= (int)$w['id'] ?>)">
                            <span class="fw-500 text-dark d-flex align-items-center">
                                <i class="fas fa-store me-3 text-muted small opacity-75"></i><?= e($w['name']) ?>
                            </span>
                            <div class="premium-switch">
                                <input class="wh-switch d-none" type="checkbox" 
                                    value="<?= e($w['id']) ?>" data-name="<?= e($w['name']) ?>" id="wh_<?= e($w['id']) ?>"
                                    checked>
                                <label class="switch-label mb-0"></label>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnSelectAll">Hepsini Seç</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnDeselectAll">Temizle</button>
                <button type="button" class="btn btn-primary btn-sm px-4" data-bs-dismiss="modal">Tamam</button>
            </div>
        </div>
    </div>
</div>

<!-- Email Modal -->
<div class="modal fade" id="emailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">Stok Listesini E-posta Gönder</h5>
                <button type="button" class="btn btn-link text-white p-0 border-0" data-bs-dismiss="modal"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Alıcı E-posta <span class="text-danger">*</span></label>
                    <input type="email" id="emailTo" class="form-control" placeholder="ornek@mail.com">
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="withImages" checked>
                    <label class="form-check-label" for="withImages">Ürün resimleriyle gönder</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-info text-white" id="btnSendEmail">
                    <i class="fas fa-paper-plane me-1" style="margin-right: 5px"></i>Gönder
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Image Preview Modal -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="background: transparent; border: none; box-shadow: none;">
            <div class="modal-header border-0" style="padding: 0; position: absolute; right: 0; top: 0; z-index: 10;">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"
                    style="background-color: rgba(0,0,0,0.5); padding: 10px; margin: 10px; border-radius: 50%;"></button>
            </div>
            <div class="modal-body p-0 text-center">
                <img id="fullSizeImage" src="" class="img-fluid rounded shadow" style="max-height: 85vh;">
            </div>
        </div>
    </div>
</div>
</div>
<style>
    .premium-switch {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 22px;
        vertical-align: middle;
        pointer-events: none; /*li clicks only*/
    }
    .premium-switch .switch-label {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
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
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .wh-switch:checked + .switch-label { background-color: #1a56db; }
    .wh-switch:checked + .switch-label:before { transform: translateX(22px); }
    
    .warehouse-item:hover { background-color: #f0f7ff !important; }
    .fw-500 { font-weight: 500; font-size: 0.95rem; }

    /* Warehouse Pastel Badge Colors */
    .wh-badge-0 { background-color: #e3f2fd !important; color: #0d47a1 !important; border: 1px solid #bbdefb !important; } /* Blue */
    .wh-badge-1 { background-color: #f1f8e9 !important; color: #33691e !important; border: 1px solid #dcedc8 !important; } /* Green */
    .wh-badge-2 { background-color: #fff3e0 !important; color: #e65100 !important; border: 1px solid #ffe0b2 !important; } /* Orange */
    .wh-badge-3 { background-color: #f3e5f5 !important; color: #4a148c !important; border: 1px solid #e1bee7 !important; } /* Purple */
    .wh-badge-4 { background-color: #e0f2f1 !important; color: #004d40 !important; border: 1px solid #b2dfdb !important; } /* Teal */
    .wh-badge-5 { background-color: #fffde7 !important; color: #f57f17 !important; border: 1px solid #fff9c4 !important; } /* Yellow */
    .wh-badge-6 { background-color: #fbe9e7 !important; color: #bf360c !important; border: 1px solid #ffccbc !important; } /* Deep Orange */
    .wh-badge-7 { background-color: #e1f5fe !important; color: #01579b !important; border: 1px solid #b3e5fc !important; } /* Light Blue */
</style>
<script>
    var curPage = 1, curPerPage = 10, curSearch = '', searchTimer, currentData = [], currentCols = [];
    var apiUrl = '<?= BASE_URL ?>/api/stock_status.php';
    
    // Warehouse Color Mapping
    var warehouseColors = {};
    <?php 
    foreach ($warehouses as $index => $w) {
        $colorIdx = $index % 8;
        echo "warehouseColors['" . addslashes($w['name']) . "'] = 'wh-badge-$colorIdx';\n";
    }
    ?>

    function getWarehouseBadgeClass(name) {
        return warehouseColors[name] || 'bg-light text-dark border';
    }

    function esc(v) { return $('<span>').text(v || '').html(); }

    function showImagePreview(src) {
        if (!src) return;
        $('#fullSizeImage').attr('src', src);
        $('#imagePreviewModal').modal('show');
    }

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
            badges += '<span class="badge ' + colorClass + ' px-3 py-2" style="margin-right: 8px; margin-bottom: 8px;">' + name + '</span>';
        });
        if (!badges) badges = '<span class="text-muted small">Herhangi bir depo seçilmedi</span>';
        $('#selectedWarehouses').html(badges);
    }

    function toggleWarehouse(id) {
        var cb = $('#wh_' + id);
        cb.prop('checked', !cb.prop('checked')).trigger('change');
    }

    function load() {
        var whs = getSelectedWarehouses();
        updateBadges();
        if (!whs.length) {
            $('#stockHead').html('');
            $('#stockBody').html('<tr><td class="text-center text-muted p-3">Lütfen depo seçin</td></tr>');
            return;
        }

        $('#stockBody').html('<tr><td colspan="5" class="text-center p-4"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Yükleniyor...</td></tr>');

        $.get(apiUrl, { action: 'list', page: curPage, per_page: curPerPage, search: curSearch, warehouses: whs.join(',') }, function (r) {
            if (!r.success) {
                $('#stockBody').html('<tr><td colspan="5" class="text-center text-danger p-3"><i class="fas fa-exclamation-triangle me-2"></i>Hata: ' + esc(r.message || 'Veri alınamadı') + '</td></tr>');
                return;
            }
            if (!r.data || !r.data.data) {
                $('#stockBody').html('<tr><td colspan="5" class="text-center text-muted p-3">Veri bulunamadı</td></tr>');
                return;
            }
            currentData = r.data.data;
            currentCols = r.data.columns;

            // Thead
            var headHtml = '<tr><th style="width:60px">#</th><th>Ürün</th><th>Depo</th><th class="num-align" style="width:120px">Kalan Miktar</th><th style="width:80px" class="text-center">İşlemler</th></tr>';
            $('#stockHead').html(headHtml);

            // Tbody
            var html = '';
            var rowIndex = offset() + 1;
            $.each(r.data.data, function (i, row) {
                var isLowStock = (row.stock_alarm > 0 && row.total < row.stock_alarm);
                var totalClass = isLowStock ? 'text-danger fw-bold' : 'text-success text-bold';

                $.each(r.data.columns, function (j, col) {
                    var qty = row.stocks[col] || 0;
                    if (qty == 0) return; // Sadece 0 olanları gizle, negatifleri göster

                    var rowIndexText = rowIndex++;
                    var imgSrc = row.image ? '<?= BASE_URL ?>/images/UrunResim/' + encodeURIComponent(row.image) : '';
                    var qtyClass = qty < 0 ? 'text-danger fw-bold' : 'text-success text-bold';

                    html += '<tr class="' + (isLowStock ? 'table-warning' : '') + '">';
                    html += '<td class="text-muted">' + rowIndexText + '</td><td>';
                    if (row.image) { 
                        html += '<img src="' + imgSrc + '" style="width:32px;height:32px;object-fit:cover;border-radius:4px;margin-right:6px;cursor:pointer;" onerror="this.remove()" onclick="showImagePreview(\'' + imgSrc + '\')" title="Büyütmek için tıklayın">'; 
                    }
                    html += '<strong>' + esc(row.product) + '</strong>' + 
                            (isLowStock ? ' <span class="badge bg-danger ms-1" title="Global Stok Alarm Seviyesi Altında!"><i class="fas fa-exclamation-triangle"></i></span>' : '') + 
                            '</td>';
                    var colorClass = getWarehouseBadgeClass(col);
                    html += '<td><span class="badge ' + colorClass + '"><i class="fas fa-warehouse opacity-75 me-1"></i> ' + esc(col) + '</span></td>';
                    html += '<td class="num-align ' + qtyClass + '">' + formatQty(qty) + ' <small class="text-muted fw-normal">' + esc(row.unit || 'Adet') + '</small></td>';
                    
                    html += '<td class="text-center">' +
                            '<button class="btn btn-xs btn-outline-info" onclick="showHistory(' + row.product_id + ', \'' + esc(row.product) + '\')" title="Tüm Geçmişi Gör">' +
                            '<i class="fas fa-history"></i>' +
                            '</button>' +
                            '</td>';
                    html += '</tr>';
                });
            });
            $('#stockBody').html(html || '<tr><td colspan="5" class="text-center text-muted p-3">Kayıt bulunamadı</td></tr>');
            $('#totalCount').text('Toplam: ' + formatQty(r.data.total) + ' ürün');
            renderPag(r.data.total);
        }, 'json');
    }
    function offset() { return (curPage - 1) * curPerPage; }

    function showHistory(productId, productName) {
        window.location.href = '<?= BASE_URL ?>/index.php?page=product_history&product_id=' + productId;
    }

    function renderPag(total) { var pages = Math.ceil(total / curPerPage); if (pages <= 1) { $('#pagination').html(''); return; } var html = '<ul class="pagination pagination-sm">', s = Math.max(1, curPage - 2), e = Math.min(pages, curPage + 2); if (curPage > 1) html += '<li class="page-item"><a class="page-link" data-p="' + (curPage - 1) + '" href="#">&laquo;</a></li>'; for (var p = s; p <= e; p++)html += '<li class="page-item' + (p === curPage ? ' active' : '') + '"><a class="page-link" data-p="' + p + '" href="#">' + p + '</a></li>'; if (curPage < pages) html += '<li class="page-item"><a class="page-link" data-p="' + (curPage + 1) + '" href="#">&raquo;</a></li>'; html += '</ul>'; $('#pagination').html(html).find('a').on('click', function (e) { e.preventDefault(); curPage = parseInt($(this).data('p')); load(); }); }

    // Excel export (Styled with xlsx-js-style)
    $('#btnExcel').on('click', function () {
        if (!currentData.length) { showInfo('Önce stok verisini yükleyin.'); return; }

        var wb = XLSX.utils.book_new();
        var ws_data = [];

        // 1. Header: DEPO STOK RAPORU
        ws_data.push(["DEPO STOK RAPORU", "", ""]);
        
        // 2. Info Rows
        var now = new Date();
        var dateStr = now.toLocaleDateString('tr-TR') + ' ' + now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
        var selectedWhNames = $('.wh-switch:checked').map(function() { return $(this).data('name'); }).get().join(', ');

        ws_data.push(["Rapor Tarihi", "", dateStr]);
        ws_data.push(["Depo", "", selectedWhNames]);
        ws_data.push(["", "", ""]); // Spacer

        // 3. Table Header
        ws_data.push(["Ürün Adı", "Stok Adeti", "Depo"]);

        // 4. Data Rows
        $.each(currentData, function (i, row) {
            $.each(currentCols, function (j, col) {
                var qty = row.stocks[col] || 0;
                ws_data.push([row.product, Math.round(qty), col]);
            });
        });

        var ws = XLSX.utils.aoa_to_sheet(ws_data);

        // --- STYLING ---
        var range = XLSX.utils.decode_range(ws['!ref']);

        // Column Widths (A4 Width optimization)
        ws['!cols'] = [
            { wch: 60 }, // Ürün Adı
            { wch: 15 }, // Stok Adeti
            { wch: 25 }  // Depo
        ];

        // Row Heights (Double height for title)
        ws['!rows'] = [
            { hpt: 40 } // Row 0 (Title)
        ];

        // Style objects
        var styleTitle = {
            fill: { fgColor: { rgb: "444444" } },
            font: { color: { rgb: "FFFFFF" }, bold: true, sz: 16 },
            alignment: { horizontal: "center", vertical: "center" }
        };
        var styleInfoLabel = {
            font: { bold: true },
            border: {
                top: { style: "thin" }, bottom: { style: "thin" }, left: { style: "thin" }, right: { style: "thin" }
            }
        };
        var styleInfoValue = {
            alignment: { horizontal: "right", wrapText: true },
            border: {
                top: { style: "thin" }, bottom: { style: "thin" }, left: { style: "thin" }, right: { style: "thin" }
            }
        };
        var styleTableHeader = {
            fill: { fgColor: { rgb: "F2F2F2" } },
            font: { bold: true },
            alignment: { horizontal: "center" },
            border: {
                top: { style: "thin" }, bottom: { style: "thin" }, left: { style: "thin" }, right: { style: "thin" }
            }
        };
        var styleDataNum = {
            alignment: { horizontal: "right" },
            border: {
                top: { style: "thin" }, bottom: { style: "thin" }, left: { style: "thin" }, right: { style: "thin" }
            }
        };
        var styleDataCenter = {
            alignment: { horizontal: "center" },
            border: {
                top: { style: "thin" }, bottom: { style: "thin" }, left: { style: "thin" }, right: { style: "thin" }
            }
        };
        var styleDataText = {
            border: {
                top: { style: "thin" }, bottom: { style: "thin" }, left: { style: "thin" }, right: { style: "thin" }
            }
        };

        // Merge Title
        ws['!merges'] = [
            { s: { r: 0, c: 0 }, e: { r: 0, c: 2 } } // Title
        ];

        // Apply styles to all cells
        for (var R = range.s.r; R <= range.e.r; ++R) {
            for (var C = range.s.c; C <= range.e.c; ++C) {
                var cell_ref = XLSX.utils.encode_cell({ r: R, c: C });
                if (!ws[cell_ref]) continue;

                if (R === 0) {
                    ws[cell_ref].s = styleTitle;
                } else if (R === 1 || R === 2) {
                    if (C < 2) {
                        ws[cell_ref].s = styleInfoLabel;
                    } else {
                        ws[cell_ref].s = styleInfoValue;
                    }
                    // Merge info labels (A and B)
                    if (C === 0) {
                        if (!ws['!merges']) ws['!merges'] = [];
                        ws['!merges'].push({ s: { r: R, c: 0 }, e: { r: R, c: 1 } });
                    }
                } else if (R === 4) {
                    ws[cell_ref].s = styleTableHeader;
                } else if (R > 4) {
                    if (C === 1) ws[cell_ref].s = styleDataNum;
                    else if (C === 2) ws[cell_ref].s = styleDataCenter;
                    else ws[cell_ref].s = styleDataText;
                }
            }
        }

        // Print settings
        ws['!margins'] = { left: 0.5, right: 0.5, top: 0.5, bottom: 0.5, header: 0.3, footer: 0.3 };
        
        XLSX.utils.book_append_sheet(wb, ws, "Stok Durumu");
        XLSX.writeFile(wb, 'stok_durumu_' + new Date().toISOString().slice(0, 10) + '.xlsx');
    });

    // Email
    $('#btnEmail').on('click', function () { $('#emailModal').modal('show'); });
    $('#btnSendEmail').on('click', function () {
        var to = $('#emailTo').val();
        if (!to) { showError('E-posta adresi girin.'); return; }
        var whs = getSelectedWarehouses();

        var $btn = $(this);
        var originalHtml = $btn.html();

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin" style="margin-right: 5px"></i>Gönderiliyor...');

        $.post(apiUrl, { action: 'send_email', to: to, with_images: $('#withImages').is(':checked') ? 1 : 0, warehouses: whs.join(','), search: curSearch }, function (r) {
            if (r.success) {
                showSuccess('E-posta gönderildi!');
                $('#emailModal').modal('hide');
            } else {
                showError(r.message);
            }
        }, 'json').always(function () {
            $btn.prop('disabled', false).html(originalHtml);
        });
    });

    $('#searchBox').on('input', function () { clearTimeout(searchTimer); curSearch = $(this).val(); searchTimer = setTimeout(function () { curPage = 1; load(); }, 400); });
    $('#perPage').on('change', function () { curPerPage = parseInt($(this).val()); curPage = 1; load(); });

    $(document).on('change', '.wh-switch', function () { curPage = 1; load(); });
    $('#btnSelectAll').on('click', function () { $('.wh-switch').prop('checked', true); curPage = 1; load(); });
    $('#btnDeselectAll').on('click', function () { $('.wh-switch').prop('checked', false); curPage = 1; load(); });

    $(document).ready(function () {
        load();
    });
</script>