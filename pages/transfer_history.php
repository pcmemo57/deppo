<?php
/**
 * Transfer Geçmişi
 */
requireRole(ROLE_ADMIN, ROLE_USER);
?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title"><i class="fas fa-history me-2"></i>Depolar Arası Transfer Geçmişi</h3>
                <a href="<?= BASE_URL?>/index.php?page=transfer" class="btn btn-warning btn-sm text-dark">
                    <i class="fas fa-exchange-alt me-1"></i>Yeni Transfer
                </a>
            </div>
            <div class="card-body">
                <div class="d-flex gap-2 mb-3">
                    <input type="text" id="searchBox" class="form-control form-control-sm" placeholder="Depo adı ara..."
                        style="width:220px">
                    <select id="perPage" class="form-select form-select-sm" style="width:auto">
                        <option value="10">10</option>
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span id="totalCount" class="text-muted small align-self-center"></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-striped table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Kaynak Depo</th>
                                <th>Hedef Depo</th>
                                <th>Kalem Sayısı</th>
                                <th>Not</th>
                                <th>Tarih</th>
                                <th>Detay</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody"></tbody>
                    </table>
                </div>
                <div id="pagination" class="d-flex justify-content-center mt-2"></div>
            </div>
        </div>
    </div>
</div>

<!-- Detay Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title">Transfer Detayı</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailBody">Yükleniyor...</div>
        </div>
    </div>
</div>

<script>
    var curPage = 1, curPerPage = 25, curSearch = '', searchTimer;
    var apiUrl = '<?= BASE_URL?>/api/transfer.php';
    function esc(v) { return $('<span>').text(v || '').html(); }

    function load() {
        $.get(apiUrl, { action: 'list', page: curPage, per_page: curPerPage, search: curSearch }, function (r) {
            if (!r.success) return;
            var html = '';
            $.each(r.data, function (i, d) {
                html += '<tr><td>' + d.id + '</td><td><span class="badge bg-success">' + esc(d.source) + '</span></td>';
                html += '<td><span class="badge bg-danger">' + esc(d.target) + '</span></td>';
                html += '<td><span class="badge bg-info">' + d.item_count + ' kalem</span></td>';
                html += '<td>' + esc(d.note || '—') + '</td><td>' + esc(d.created_at) + '</td>';
                html += '<td><button class="btn btn-xs btn-secondary" onclick="showDetail(' + d.id + ')"><i class="fas fa-eye me-1"></i>Detay</button></td>';
                html += '</tr>';
            });
            $('#tableBody').html(html || '<tr><td colspan="7" class="text-center text-muted p-3">Kayıt bulunamadı</td></tr>');
            $('#totalCount').text('Toplam: ' + r.total);
            renderPag(r.total);
        }, 'json');
    }
    function renderPag(total) { var pages = Math.ceil(total / curPerPage); if (pages <= 1) { $('#pagination').html(''); return; } var html = '<ul class="pagination pagination-sm">', s = Math.max(1, curPage - 2), e = Math.min(pages, curPage + 2); if (curPage > 1) html += '<li class="page-item"><a class="page-link" data-p="' + (curPage - 1) + '" href="#">&laquo;</a></li>'; for (var p = s; p <= e; p++)html += '<li class="page-item' + (p === curPage ? ' active' : '') + '"><a class="page-link" data-p="' + p + '" href="#">' + p + '</a></li>'; if (curPage < pages) html += '<li class="page-item"><a class="page-link" data-p="' + (curPage + 1) + '" href="#">&raquo;</a></li>'; html += '</ul>'; $('#pagination').html(html).find('a').on('click', function (e) { e.preventDefault(); curPage = parseInt($(this).data('p')); load(); }); }

    function showDetail(id) {
        $('#detailBody').html('<div class="text-center p-3"><i class="fas fa-spinner fa-spin"></i> Yükleniyor...</div>');
        $('#detailModal').modal('show');
        $.get(apiUrl, { action: 'get_items', id: id }, function (r) {
            if (!r.success) { $('#detailBody').html('<div class="alert alert-danger">Veri alınamadı</div>'); return; }
            var html = '<table class="table table-bordered table-sm"><thead><tr><th>Ürün</th><th>Adet</th></tr></thead><tbody>';
            $.each(r.data, function (i, d) { html += '<tr><td>' + esc(d.product) + '</td><td>' + d.quantity + ' ' + esc(d.unit) + '</td></tr>'; });
            html += '</tbody></table>';
            $('#detailBody').html(html || '<div class="text-muted p-3">Kalem bulunamadı</div>');
        }, 'json');
    }
    $('#searchBox').on('input', function () { clearTimeout(searchTimer); curSearch = $(this).val(); searchTimer = setTimeout(function () { curPage = 1; load(); }, 400); });
    $('#perPage').on('change', function () { curPerPage = parseInt($(this).val()); curPage = 1; load(); });
    load();
</script>