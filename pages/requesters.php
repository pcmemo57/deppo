<?php
/**
 * Talep Eden Yönetimi
 */
requireRole(ROLE_ADMIN, ROLE_USER);
?>
<style>
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
<div class="row">
    <div class="col-12">
        <div class="card card-info card-outline">
            <div class="card-header">
                <h3 class="card-title text-bold"><i class="fas fa-users me-2"></i>Talep Eden Listesi</h3>
                <div class="card-tools d-flex gap-2">
                    <select id="perPage" class="form-select form-select-sm" style="width:auto">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <div class="input-group input-group-sm" style="width: 220px;">
                        <input type="text" id="searchBox" class="form-control" placeholder="Ara...">
                        <div class="input-group-append">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                    </div>
                    <button class="btn btn-primary btn-sm px-3 shadow-sm" onclick="openModal()"
                        title="Yeni Talep Eden Ekle">
                        <i class="fas fa-plus me-1"></i> Yeni Talep Eden Ekle
                    </button>
                </div>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover table-striped m-0 table-valign-middle">
                    <thead class="bg-light">
                        <tr>
                            <th>Ad</th>
                            <th>Soyad</th>
                            <th>E-posta</th>
                            <th>Görev</th>
                            <th style="width:100px">Durum</th>
                            <th style="width:120px" class="text-center pe-3">İşlem</th>
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

<!-- Modal -->
<div class="modal fade" id="crudModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalTitle">Talep Eden Ekle</h5>
                <button type="button" class="btn btn-link text-white p-0 border-0" data-bs-dismiss="modal"><i
                        class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form id="crudForm">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="formId" value="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Ad <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Soyad <span class="text-danger">*</span></label>
                                <input type="text" name="surname" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">E-posta</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Görev / Ünvan</label>
                                <input type="text" name="title" class="form-control"
                                    placeholder="Muhasebe, Satın Alma...">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="d-block mb-2">Durum</label>
                                <input type="hidden" name="is_active" id="is_active_input" value="1">
                                <div class="status-btn-group">
                                    <button type="button" class="status-btn-item" id="set_active"
                                        onclick="setStatus(1)">
                                        <i class="fas fa-check-circle me-1"></i> AKTİF
                                    </button>
                                    <button type="button" class="status-btn-item" id="set_inactive"
                                        onclick="setStatus(0)">
                                        <i class="fas fa-times-circle me-1"></i> PASİF
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                <button type="button" class="btn btn-success" id="btnSave"><i
                        class="fas fa-save me-1"></i>Kaydet</button>
            </div>
        </div>
    </div>
</div>

<script>
    var curPage = 1, curPerPage = 10, curSearch = '', searchTimer;
    var apiUrl = '<?= BASE_URL ?>/api/requesters.php';

    function load() {
        $.get(apiUrl, { action: 'list', page: curPage, per_page: curPerPage, search: curSearch }, function (r) {
            if (!r.success || !r.data.data) return;
            var html = '';
            $.each(r.data.data, function (i, u) {
                html += '<tr><td>' + esc(u.name) + '</td><td>' + esc(u.surname) + '</td>';
                html += '<td>' + esc(u.email || '—') + '</td><td>' + esc(u.title || '—') + '</td>';
                html += '<td>' +
                    (u.is_active == 1
                        ? '<span class="status-badge active" onclick="toggleRow(' + u.id + ',1)"><i class="fas fa-check"></i> AKTİF</span>'
                        : '<span class="status-badge inactive" onclick="toggleRow(' + u.id + ',0)"><i class="fas fa-times"></i> PASİF</span>'
                    ) +
                    '</td>';
                html += '<td>';
                html += '<button class="btn btn-xs btn-info me-1" onclick="editRow(' + u.id + ')"><i class="fas fa-edit"></i></button>';
                html += '<button class="btn btn-xs btn-danger" onclick="deleteRow(' + u.id + ')"><i class="fas fa-trash"></i></button>';
                html += '</td></tr>';
            });
            $('#tableBody').html(html || '<tr><td colspan="6" class="text-center text-muted p-3">Kayıt bulunamadı</td></tr>');
            $('#totalCount').text('Toplam: ' + formatQty(r.data.total) + ' kayıt');
            renderPag(r.data.total);
        }, 'json');
    }

    function renderPag(total) {
        var pages = Math.ceil(total / curPerPage); var html = '';
        if (pages <= 1) { $('#pagination').html(''); return; }
        html += '<ul class="pagination pagination-sm">';
        if (curPage > 1) html += '<li class="page-item"><a class="page-link" data-p="' + (curPage - 1) + '" href="#">&laquo;</a></li>';
        var s = Math.max(1, curPage - 2), e = Math.min(pages, curPage + 2);
        for (var p = s; p <= e; p++) html += '<li class="page-item' + (p === curPage ? ' active' : '') + '"><a class="page-link" data-p="' + p + '" href="#">' + p + '</a></li>';
        if (curPage < pages) html += '<li class="page-item"><a class="page-link" data-p="' + (curPage + 1) + '" href="#">&raquo;</a></li>';
        html += '</ul>';
        $('#pagination').html(html).find('a').on('click', function (e) { e.preventDefault(); curPage = parseInt($(this).data('p')); load(); });
    }

    function openModal() { $('#formAction').val('add'); $('#formId').val(''); $('#crudForm')[0].reset(); setStatus(1); $('#modalTitle').text('Talep Eden Ekle'); $('#crudModal').modal('show'); }
    function editRow(id) {
        $.get(apiUrl, { action: 'get', id: id }, function (r) {
            if (!r.success) return showError(r.message);
            var u = r.data;
            $('#formAction').val('edit'); $('#formId').val(u.id);
            $('[name="name"]').val(u.name); $('[name="surname"]').val(u.surname);
            $('[name="email"]').val(u.email); $('[name="title"]').val(u.title);
            setStatus(u.is_active);
            $('#modalTitle').text('Talep Eden Düzenle'); $('#crudModal').modal('show');
        }, 'json');
    }

    function setStatus(val) {
        $('#is_active_input').val(val);
        $('.status-btn-item').removeClass('active-state inactive-state');
        if (val == 1) {
            $('#set_active').addClass('active-state');
        } else {
            $('#set_inactive').addClass('inactive-state');
        }
    }
    function toggleRow(id, cur) { $.post(apiUrl, { action: 'toggle', id: id, status: cur == 1 ? 0 : 1 }, function (r) { r.success ? showSuccess(r.message) : showError(r.message); load(); }, 'json'); }
    function deleteRow(id) { confirmAction('Bu kaydı silmek istediğinize emin misiniz?', function () { $.post(apiUrl, { action: 'delete', id: id }, function (r) { r.success ? showSuccess(r.message) : showError(r.message); load(); }, 'json'); }); }
    function esc(v) { return $('<span>').text(v || '').html(); }

    $('#btnSave').on('click', function () { $.post(apiUrl, $('#crudForm').serialize(), function (r) { if (r.success) { showSuccess(r.message); $('#crudModal').modal('hide'); load(); } else showError(r.message); }, 'json'); });
    $('#searchBox').on('input', function () { clearTimeout(searchTimer); curSearch = $(this).val(); searchTimer = setTimeout(function () { curPage = 1; load(); }, 400); });
    $('#perPage').on('change', function () { curPerPage = parseInt($(this).val()); curPage = 1; load(); });
    load();
</script>