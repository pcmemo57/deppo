<?php
/**
 * Stok Durumu — Depo bazlı anlık stok görünümü
 */
requireRole(ROLE_ADMIN, ROLE_USER);
$warehouses = Database::fetchAll("SELECT id,name FROM tbl_dp_warehouses WHERE hidden=0 AND is_active=1 ORDER BY name");
?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h3 class="card-title mb-0"><i class="fas fa-chart-bar me-2"></i>Stok Durumu</h3>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-success btn-sm" id="btnExcel">
                            <i class="fas fa-file-excel me-1"></i>Excel İndir
                        </button>
                        <button class="btn btn-info btn-sm text-white" id="btnEmail">
                            <i class="fas fa-envelope me-1"></i>E-posta Gönder
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Filtreler -->
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <input type="text" id="searchBox" class="form-control form-control-sm"
                            placeholder="Ürün adı ile ara...">
                    </div>
                    <div class="col-md-5">
                        <select id="warehouseFilter" class="form-select form-select-sm" multiple style="height:auto">
                            <?php foreach ($warehouses as $w): ?>
                            <option value="<?= e($w['id'])?>" selected>
                                <?= e($w['name'])?>
                            </option>
                            <?php
endforeach; ?>
                        </select>
                        <small class="text-muted">Birden fazla depo seçilebilir (Ctrl+click)</small>
                    </div>
                    <div class="col-md-3">
                        <select id="perPage" class="form-select form-select-sm" style="width:auto">
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="999">Tümü</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-striped table-bordered table-sm" id="stockTable">
                        <thead class="table-dark" id="stockHead"></thead>
                        <tbody id="stockBody"></tbody>
                    </table>
                </div>
                <div id="pagination" class="d-flex justify-content-center mt-2"></div>
                <span id="totalCount" class="text-muted small"></span>
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
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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
                    <i class="fas fa-paper-plane me-1"></i>Gönder
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    var curPage = 1, curPerPage = 25, curSearch = '', searchTimer, currentData = [], currentCols = [];
    var apiUrl = '<?= BASE_URL?>/api/stock_status.php';
    function esc(v) { return $('<span>').text(v || '').html(); }

    function getSelectedWarehouses() {
        var ids = [];
        $('#warehouseFilter option:selected').each(function () { ids.push($(this).val()); });
        return ids;
    }

    function load() {
        var whs = getSelectedWarehouses();
        if (!whs.length) { $('#stockHead').html(''); $('#stockBody').html('<tr><td class="text-center text-muted p-3">Depo seçin</td></tr>'); return; }

        $.get(apiUrl, { action: 'list', page: curPage, per_page: curPerPage, search: curSearch, warehouses: whs.join(',') }, function (r) {
            if (!r.success) { $('#stockBody').html('<tr><td colspan="99" class="text-center text-muted p-3">Veri alınamadı</td></tr>'); return; }
            currentData = r.data;
            currentCols = r.columns;

            // Thead
            var headHtml = '<tr><th>#</th><th>Ürün</th>';
            $.each(r.columns, function (i, col) { headHtml += '<th>' + esc(col) + '</th>'; });
            headHtml += '<th>Toplam</th></tr>';
            $('#stockHead').html(headHtml);

            // Tbody
            var html = '';
            $.each(r.data, function (i, row) {
                html += '<tr><td>' + (offset() + i + 1) + '</td><td>';
                if (row.image) { html += '<img src="<?= BASE_URL?>/images/UrunResim/' + encodeURIComponent(row.image) + '" style="width:32px;height:32px;object-fit:cover;border-radius:4px;margin-right:6px;" onerror="this.remove()">'; }
                html += esc(row.product) + '</td>';
                $.each(r.columns, function (j, col) {
                    var qty = row.stocks[col] || 0;
                    html += '<td class="text-center">' + (qty > 0 ? '<strong>' + qty + '</strong>' : '-') + '</td>';
                });
                html += '<td class="text-center"><strong>' + row.total + '</strong></td>';
                html += '</tr>';
            });
            $('#stockBody').html(html || '<tr><td colspan="' + (r.columns.length + 3) + '" class="text-center text-muted p-3">Kayıt bulunamadı</td></tr>');
            $('#totalCount').text('Toplam: ' + r.total + ' ürün');
            renderPag(r.total, r.columns.length + 3);
        }, 'json');
    }
    function offset() { return (curPage - 1) * curPerPage; }
    function renderPag(total, cols) { var pages = Math.ceil(total / curPerPage); if (pages <= 1) { $('#pagination').html(''); return; } var html = '<ul class="pagination pagination-sm">', s = Math.max(1, curPage - 2), e = Math.min(pages, curPage + 2); if (curPage > 1) html += '<li class="page-item"><a class="page-link" data-p="' + (curPage - 1) + '" href="#">&laquo;</a></li>'; for (var p = s; p <= e; p++)html += '<li class="page-item' + (p === curPage ? ' active' : '') + '"><a class="page-link" data-p="' + p + '" href="#">' + p + '</a></li>'; if (curPage < pages) html += '<li class="page-item"><a class="page-link" data-p="' + (curPage + 1) + '" href="#">&raquo;</a></li>'; html += '</ul>'; $('#pagination').html(html).find('a').on('click', function (e) { e.preventDefault(); curPage = parseInt($(this).data('p')); load(); }); }

    // Excel export (client-side XLSX)
    $('#btnExcel').on('click', function () {
        if (!currentData.length) { showInfo('Önce stok verisini yükleyin.'); return; }
        var rows = [['Ürün'].concat(currentCols).concat(['Toplam'])];
        $.each(currentData, function (i, row) {
            var r = [row.product];
            $.each(currentCols, function (j, col) { r.push(row.stocks[col] || 0); });
            r.push(row.total); rows.push(r);
        });
        var ws = XLSX.utils.aoa_to_sheet(rows);
        var wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Stok');
        XLSX.writeFile(wb, 'stok_durumu_' + new Date().toISOString().slice(0, 10) + '.xlsx');
    });

    // Email
    $('#btnEmail').on('click', function () { $('#emailModal').modal('show'); });
    $('#btnSendEmail').on('click', function () {
        var to = $('#emailTo').val();
        if (!to) { showError('E-posta adresi girin.'); return; }
        var whs = getSelectedWarehouses();
        $.post(apiUrl, { action: 'send_email', to: to, with_images: $('#withImages').is(':checked') ? 1 : 0, warehouses: whs.join(','), search: curSearch }, function (r) {
            if (r.success) { showSuccess('E-posta gönderildi!'); $('#emailModal').modal('hide'); }
            else showError(r.message);
        }, 'json');
    });

    $('#searchBox').on('input', function () { clearTimeout(searchTimer); curSearch = $(this).val(); searchTimer = setTimeout(function () { curPage = 1; load(); }, 400); });
    $('#warehouseFilter').on('change', function () { curPage = 1; load(); });
    $('#perPage').on('change', function () { curPerPage = parseInt($(this).val()); curPage = 1; load(); });
    load();
</script>