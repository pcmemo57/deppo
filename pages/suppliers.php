<?php
/**
 * Tedarikçi Yönetimi
 */
requireRole(ROLE_ADMIN, ROLE_USER);
?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title"><i class="fas fa-truck me-2"></i>Tedarikçi Listesi</h3>
                <button class="btn btn-success btn-sm" onclick="openModal()"><i class="fas fa-plus me-1"></i>Tedarikçi
                    Ekle</button>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <input type="text" id="searchBox" class="form-control form-control-sm" placeholder="Ara..."
                        style="width:220px">
                    <select id="perPage" class="form-select form-select-sm" style="width:auto">
                        <option value="10">10</option>
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span id="totalCount" class="text-muted small"></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-striped table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Tedarikçi Adı</th>
                                <th>Yetkili</th>
                                <th>E-posta</th>
                                <th>Telefon</th>
                                <th>Durum</th>
                                <th style="width:110px">İşlem</th>
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

<div class="modal fade" id="crudModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalTitle">Tedarikçi Ekle</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="crudForm">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="formId" value="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3"><label class="form-label">Firma Adı <span
                                        class="text-danger">*</span></label><input type="text" name="name"
                                    class="form-control" required></div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3"><label class="form-label">Yetkili Kişi</label><input type="text"
                                    name="contact" class="form-control"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3"><label class="form-label">E-posta</label><input type="email" name="email"
                                    class="form-control"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3"><label class="form-label">Telefon</label><input type="text" name="phone"
                                    class="form-control"></div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3"><label class="form-label">Adres</label><textarea name="address"
                                    class="form-control" rows="2"></textarea></div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3"><label class="form-label">Durum</label><select name="is_active"
                                    class="form-select">
                                    <option value="1">Aktif</option>
                                    <option value="0">Pasif</option>
                                </select></div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                <button type="button" class="btn btn-danger" id="btnSave"><i
                        class="fas fa-save me-1"></i>Kaydet</button>
            </div>
        </div>
    </div>
</div>
<script>
    var curPage = 1, curPerPage = 25, curSearch = '', searchTimer;
    var apiUrl = '<?= BASE_URL?>/api/suppliers.php';
    function esc(v) { return $('<span>').text(v || '').html(); }
    function load() { $.get(apiUrl, { action: 'list', page: curPage, per_page: curPerPage, search: curSearch }, function (r) { if (!r.success) return; var html = ''; $.each(r.data, function (i, u) { html += '<tr><td>' + u.id + '</td><td><b>' + esc(u.name) + '</b></td><td>' + esc(u.contact || '—') + '</td><td>' + esc(u.email || '—') + '</td><td>' + esc(u.phone || '—') + '</td><td>' + (u.is_active == 1 ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Pasif</span>') + '</td><td><button class="btn btn-xs btn-info me-1" onclick="editRow(' + u.id + ')"><i class="fas fa-edit"></i></button><button class="btn btn-xs btn-warning me-1" onclick="toggleRow(' + u.id + ',' + u.is_active + ')"><i class="fas fa-power-off"></i></button><button class="btn btn-xs btn-danger" onclick="deleteRow(' + u.id + ')"><i class="fas fa-trash"></i></button></td></tr>'; }); $('#tableBody').html(html || '<tr><td colspan="7" class="text-center text-muted p-3">Kayıt bulunamadı</td></tr>'); $('#totalCount').text('Toplam: ' + r.total + ' kayıt'); renderPag(r.total); }, 'json'); }
    function renderPag(total) { var pages = Math.ceil(total / curPerPage); if (pages <= 1) { $('#pagination').html(''); return; } var html = '<ul class="pagination pagination-sm">', s = Math.max(1, curPage - 2), e = Math.min(pages, curPage + 2); if (curPage > 1) html += '<li class="page-item"><a class="page-link" data-p="' + (curPage - 1) + '" href="#">&laquo;</a></li>'; for (var p = s; p <= e; p++)html += '<li class="page-item' + (p === curPage ? ' active' : '') + '"><a class="page-link" data-p="' + p + '" href="#">' + p + '</a></li>'; if (curPage < pages) html += '<li class="page-item"><a class="page-link" data-p="' + (curPage + 1) + '" href="#">&raquo;</a></li>'; html += '</ul>'; $('#pagination').html(html).find('a').on('click', function (e) { e.preventDefault(); curPage = parseInt($(this).data('p')); load(); }); }
    function openModal() { $('#formAction').val('add'); $('#formId').val(''); $('#crudForm')[0].reset(); $('#modalTitle').text('Tedarikçi Ekle'); $('#crudModal').modal('show'); }
    function editRow(id) { $.get(apiUrl, { action: 'get', id: id }, function (r) { if (!r.success) return showError(r.message); var u = r.data; $('#formAction').val('edit'); $('#formId').val(u.id); $('[name="name"]').val(u.name); $('[name="contact"]').val(u.contact); $('[name="email"]').val(u.email); $('[name="phone"]').val(u.phone); $('[name="address"]').val(u.address); $('[name="is_active"]').val(u.is_active); $('#modalTitle').text('Tedarikçi Düzenle'); $('#crudModal').modal('show'); }, 'json'); }
    function toggleRow(id, cur) { confirmAction(cur == 1 ? 'Pasifize et?' : 'Aktifleştir?', function () { $.post(apiUrl, { action: 'toggle', id: id, status: cur == 1 ? 0 : 1 }, function (r) { r.success ? showSuccess(r.message) : showError(r.message); load(); }, 'json'); }); }
    function deleteRow(id) { confirmAction('Bu tedarikçiyi silmek istediğinize emin misiniz?', function () { $.post(apiUrl, { action: 'delete', id: id }, function (r) { r.success ? showSuccess(r.message) : showError(r.message); load(); }, 'json'); }); }
    $('#btnSave').on('click', function () { $.post(apiUrl, $('#crudForm').serialize(), function (r) { if (r.success) { showSuccess(r.message); $('#crudModal').modal('hide'); load(); } else showError(r.message); }, 'json'); });
    $('#searchBox').on('input', function () { clearTimeout(searchTimer); curSearch = $(this).val(); searchTimer = setTimeout(function () { curPage = 1; load(); }, 400); });
    $('#perPage').on('change', function () { curPerPage = parseInt($(this).val()); curPage = 1; load(); });
    load();
</script>