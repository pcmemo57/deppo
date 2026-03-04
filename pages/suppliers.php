<?php
/**
 * Tedarikçi Yönetimi
 */
requireRole(ROLE_ADMIN, ROLE_USER);
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
    .suppliers-row {}

    /* ───────────────────────────────────────────
     MODAL GENEL (Premium Style)
  ─────────────────────────────────────────── */
    #crudModal .modal-dialog {
        max-width: 860px;
    }

    #crudModal .modal-content {
        border: none;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.18);
    }

    #crudModal .modal-header {
        background: linear-gradient(135deg, #1a56db 0%, #0c3daa 100%);
        padding: 20px 28px;
        border-bottom: none;
    }

    #crudModal .modal-title {
        font-size: 1.05rem;
        font-weight: 600;
        letter-spacing: 0.01em;
        color: #fff;
    }

    #crudModal .modal-body {
        padding: 28px 32px 12px;
        background: #f8fafd;
    }

    #crudModal .modal-footer {
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
    #crudModal .form-label {
        font-size: 0.8rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 6px;
        display: block;
    }

    /* Input & Select */
    #crudModal .form-control,
    #crudModal .form-select {
        border: 1.5px solid #d1d9e6;
        border-radius: 8px;
        padding: 9px 13px;
        font-size: 0.88rem;
        color: #1f2937;
        background: #fff;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    #crudModal .form-control:focus {
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

<div class="row suppliers-row">
    <div class="col-12">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title text-bold"><i class="fas fa-truck me-2"></i> Tedarikçi Listesi</h3>
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
                    <button class="btn btn-primary btn-sm px-3 shadow-sm" onclick="openModal()">
                        <i class="fas fa-plus me-1"></i> Tedarikçi Ekle
                    </button>
                </div>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover table-striped m-0 table-valign-middle">
                    <thead class="bg-light">
                        <tr>
                            <th>Tedarikçi Adı</th>
                            <th>Yetkili</th>
                            <th>E-posta</th>
                            <th>Telefon</th>
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
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">
                    <i class="fas fa-truck-loading me-2 opacity-75"></i> Tedarikçi Yönetimi
                </h5>
                <button type="button" class="btn btn-link text-white p-0 border-0" data-bs-dismiss="modal"><i
                        class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form id="crudForm" autocomplete="off">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="formId">

                    <!-- Temel Bilgiler -->
                    <div class="modal-section-label">
                        <i class="fas fa-info-circle"></i> Temel Bilgiler
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Firma Adı <span class="text-danger">*</span></label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-building field-icon"></i>
                                <input type="text" name="name" class="form-control" placeholder="Örn: ABC Lojistik"
                                    required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Yetkili Kişi</label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-user field-icon"></i>
                                <input type="text" name="contact" class="form-control" placeholder="Ad Soyad">
                            </div>
                        </div>
                    </div>

                    <!-- İletişim Bilgileri -->
                    <div class="modal-section-label">
                        <i class="fas fa-address-book"></i> İletişim Bilgileri
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">E-posta</label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-envelope field-icon"></i>
                                <input type="email" name="email" class="form-control" placeholder="ornek@firma.com">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Telefon</label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-phone field-icon"></i>
                                <input type="text" name="phone" class="form-control" placeholder="05xx xxx xx xx">
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Adres</label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-map-marker-alt field-icon"></i>
                                <textarea name="address" class="form-control" rows="2"
                                    placeholder="Tam adres bilgisi..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Durum -->
                    <div class="modal-section-label">
                        <i class="fas fa-toggle-on"></i> Sistem Ayarları
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Hesap Durumu</label>
                            <input type="hidden" name="is_active" id="is_active_input" value="1">
                            <div class="status-btn-group">
                                <button type="button" class="status-btn-item" id="set_active" onclick="setStatus(1)">
                                    <i class="fas fa-check-circle me-1"></i> AKTİF
                                </button>
                                <button type="button" class="status-btn-item" id="set_inactive" onclick="setStatus(0)">
                                    <i class="fas fa-times-circle me-1"></i> PASİF
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Vazgeç
                </button>
                <button type="button" class="btn-modal-save" id="btnSave">
                    <i class="fas fa-save me-1"></i> Kaydet
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    var curPage = 1, curPerPage = 10, curSearch = '', searchTimer;
    var apiUrl = '<?= BASE_URL ?>/api/suppliers.php';

    function esc(v) { return $('<span>').text(v || '').html(); }

    function load() {
        $.get(apiUrl, { action: 'list', page: curPage, per_page: curPerPage, search: curSearch }, function (r) {
            if (!r.success || !r.data.data) return;
            var html = '';
            $.each(r.data.data, function (i, u) {
                html += '<tr>' +
                    '<td><b>' + esc(u.name) + '</b></td>' +
                    '<td>' + esc(u.contact || '—') + '</td>' +
                    '<td>' + esc(u.email || '—') + '</td>' +
                    '<td>' + esc(u.phone || '—') + '</td>' +
                    '<td>' +
                    (u.is_active == 1
                        ? '<span class="status-badge active" onclick="toggleRow(' + u.id + ',1)"><i class="fas fa-check"></i> AKTİF</span>'
                        : '<span class="status-badge inactive" onclick="toggleRow(' + u.id + ',0)"><i class="fas fa-times"></i> PASİF</span>'
                    ) +
                    '</td>' +
                    '<td class="text-center pe-3">' +
                    '<button class="btn btn-xs btn-outline-info me-1" onclick="editRow(' + u.id + ')" title="Düzenle"><i class="fas fa-edit"></i></button>' +
                    '<button class="btn btn-xs btn-outline-danger" onclick="deleteRow(' + u.id + ')" title="Sil"><i class="fas fa-trash"></i></button>' +
                    '</td></tr>';
            });
            $('#tableBody').html(html || '<tr><td colspan="6" class="text-center text-muted p-4">Kayıt bulunamadı</td></tr>');
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

    function openModal() {
        $('#formAction').val('add');
        $('#formId').val('');
        $('#crudForm')[0].reset();
        setStatus(1);
        $('#modalTitle').html('<i class="fas fa-plus-circle me-2 opacity-75"></i> Yeni Tedarikçi Ekle');
        $('#crudModal').modal('show');
    }

    function editRow(id) {
        $.get(apiUrl, { action: 'get', id: id }, function (r) {
            if (!r.success) return showError(r.message);
            var u = r.data;
            $('#formAction').val('edit');
            $('#formId').val(u.id);
            $('[name="name"]').val(u.name);
            $('[name="contact"]').val(u.contact);
            $('[name="email"]').val(u.email);
            $('[name="phone"]').val(u.phone);
            $('[name="address"]').val(u.address);
            setStatus(u.is_active);
            $('#modalTitle').html('<i class="fas fa-edit me-2 opacity-75"></i> Tedarikçiyi Düzenle');
            $('#crudModal').modal('show');
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
    function deleteRow(id) { confirmAction('Bu tedarikçiyi silmek istediğinize emin misiniz?', function () { $.post(apiUrl, { action: 'delete', id: id }, function (r) { r.success ? showSuccess(r.message) : showError(r.message); load(); }, 'json'); }); }

    $('#btnSave').on('click', function () {
        if (!$('[name="name"]').val()) { showError('Firma adı zorunludur.'); return; }
        $.post(apiUrl, $('#crudForm').serialize(), function (r) {
            if (r.success) {
                showSuccess(r.message);
                $('#crudModal').modal('hide');
                load();
            } else showError(r.message);
        }, 'json');
    });

    $('#searchBox').on('input', function () { clearTimeout(searchTimer); curSearch = $(this).val(); searchTimer = setTimeout(function () { curPage = 1; load(); }, 400); });
    $('#perPage').on('change', function () { curPerPage = parseInt($(this).val()); curPage = 1; load(); });

    $(document).ready(function () {
        load();
    });
</script>