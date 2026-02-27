<?php
/**
 * Ürün Yönetimi — Resimli
 */
requireRole(ROLE_ADMIN, ROLE_USER);
?>
<div class="row">
    <div class="col-12">
        <div class="card card-success card-outline">
            <div class="card-header">
                <h3 class="card-title text-bold"><i class="fas fa-boxes me-2"></i>Ürün Listesi</h3>
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
                    <button class="btn btn-primary btn-sm px-3 shadow-sm" onclick="openModal()" title="Yeni Ürün Ekle">
                        <i class="fas fa-plus me-1"></i> Yeni Ürün Ekle
                    </button>
                </div>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover table-striped m-0 table-valign-middle">
                    <thead class="bg-light">
                        <tr>
                            <th style="width:60px" class="ps-3">#</th>
                            <th style="width:70px">Resim</th>
                            <th>Ürün Adı</th>
                            <th>Kod</th>
                            <th>Birim</th>
                            <th>Açıklama</th>
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

<!-- Modal: Ürün Ekle/Düzenle -->
<div class="modal fade" id="crudModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalTitle">Ürün Ekle</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="crudForm" enctype="multipart/form-data">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="formId" value="">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <div class="mb-2">
                                <img id="previewImg" src="<?= BASE_URL ?>/assets/no-image.png" alt="Ürün Resmi"
                                    style="width:150px;height:150px;object-fit:cover;border-radius:10px;border:2px solid #dee2e6;">
                            </div>
                            <label class="form-label">Ürün Resmi</label>
                            <input type="file" name="image" id="imageInput" class="form-control form-control-sm"
                                accept="image/*">
                            <small class="text-muted">Maks. 5MB — jpg, png, webp</small>
                        </div>
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3"><label class="form-label">Ürün Adı <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3"><label class="form-label">Birim</label>
                                        <select name="unit" class="form-select">
                                            <option value="Adet">Adet</option>
                                            <option value="Kg">Kg</option>
                                            <option value="Litre">Litre</option>
                                            <option value="Metre">Metre</option>
                                            <option value="Kutu">Kutu</option>
                                            <option value="Paket">Paket</option>
                                            <option value="Ton">Ton</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3"><label class="form-label">Ürün Kodu</label>
                                        <input type="text" name="code" class="form-control" placeholder="SKU-001">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3"><label class="form-label">Açıklama</label>
                                        <textarea name="description" class="form-control" rows="3"></textarea>
                                    </div>
                                </div>
                                <div class="col-md-3">
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

<script>
    $(function () {
        $('[name="unit"]').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#crudModal'),
            width: '100%',
            minimumResultsForSearch: -1
        });
    });
    var curPage = 1, curPerPage = 10, curSearch = '', searchTimer;
    var apiUrl = '<?= BASE_URL ?>/api/products.php';
    var noImg = '<?= BASE_URL ?>/assets/no-image.png';
    function esc(v) { return $('<span>').text(v || '').html(); }

    function showImagePreview(src) {
        if (!src || src.includes('no-image.png')) return;
        $('#fullSizeImage').attr('src', src);
        $('#imagePreviewModal').modal('show');
    }

    function load() {
        $.get(apiUrl, { action: 'list', page: curPage, per_page: curPerPage, search: curSearch }, function (r) {
            if (!r.success || !r.data.data) return;
            var html = '';
            $.each(r.data.data, function (i, u) {
                var imgSrc = u.image ? '<?= BASE_URL ?>/images/UrunResim/' + encodeURIComponent(u.image) : noImg;
                html += '<tr>';
                html += '<td>' + u.id + '</td>';
                html += '<td><img src="' + imgSrc + '" style="width:50px;height:50px;object-fit:cover;border-radius:6px; cursor:pointer;" onerror="this.src=\'' + noImg + '\'" onclick="showImagePreview(\'' + imgSrc + '\')" title="Büyütmek için tıklayın"></td>';
                html += '<td><strong>' + esc(u.name) + '</strong></td>';
                html += '<td><code>' + esc(u.code || '—') + '</code></td>';
                html += '<td>' + esc(u.unit) + '</td>';
                html += '<td>' + esc(u.description || '—').substring(0, 60) + (u.description && u.description.length > 60 ? '...' : '') + '</td>';
                html += '<td>';
                html += '<button class="btn btn-xs btn-info me-1" onclick="editRow(' + u.id + ')"><i class="fas fa-edit"></i></button>';
                html += '<button class="btn btn-xs btn-danger" onclick="deleteRow(' + u.id + ')"><i class="fas fa-trash"></i></button>';
                html += '</td></tr>';
            });
            $('#tableBody').html(html || '<tr><td colspan="8" class="text-center text-muted p-3">Kayıt bulunamadı</td></tr>');
            $('#totalCount').text('Toplam: ' + r.data.total + ' kayıt');
            renderPag(r.data.total);
        }, 'json');
    }
    function renderPag(total) { var pages = Math.ceil(total / curPerPage); if (pages <= 1) { $('#pagination').html(''); return; } var html = '<ul class="pagination pagination-sm">', s = Math.max(1, curPage - 2), e = Math.min(pages, curPage + 2); if (curPage > 1) html += '<li class="page-item"><a class="page-link" data-p="' + (curPage - 1) + '" href="#">&laquo;</a></li>'; for (var p = s; p <= e; p++)html += '<li class="page-item' + (p === curPage ? ' active' : '') + '"><a class="page-link" data-p="' + p + '" href="#">' + p + '</a></li>'; if (curPage < pages) html += '<li class="page-item"><a class="page-link" data-p="' + (curPage + 1) + '" href="#">&raquo;</a></li>'; html += '</ul>'; $('#pagination').html(html).find('a').on('click', function (e) { e.preventDefault(); curPage = parseInt($(this).data('p')); load(); }); }

    function openModal() {
        $('#formAction').val('add'); $('#formId').val(''); $('#crudForm')[0].reset();
        $('[name="unit"]').val('Adet').trigger('change');
        setStatus(1);
        $('#previewImg').attr('src', noImg); $('#modalTitle').text('Ürün Ekle'); $('#crudModal').modal('show');
    }
    function editRow(id) {
        $.get(apiUrl, { action: 'get', id: id }, function (r) {
            if (!r.success) return showError(r.message);
            var u = r.data;
            $('#formAction').val('edit'); $('#formId').val(u.id);
            $('[name="name"]').val(u.name); $('[name="code"]').val(u.code);
            $('[name="unit"]').val(u.unit).trigger('change'); $('[name="description"]').val(u.description);
            setStatus(u.is_active);
            var imgSrc = u.image ? '<?= BASE_URL ?>/images/UrunResim/' + u.image : noImg;
            $('#previewImg').attr('src', imgSrc);
            $('#modalTitle').text('Ürün Düzenle'); $('#crudModal').modal('show');
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
    function deleteRow(id) { confirmAction('Bu ürünü silmek istediğinize emin misiniz?', function () { $.post(apiUrl, { action: 'delete', id: id }, function (r) { r.success ? showSuccess(r.message) : showError(r.message); load(); }, 'json'); }); }

    // Resim önizleme
    $('#imageInput').on('change', function () {
        var file = this.files[0];
        if (file) { var reader = new FileReader(); reader.onload = function (e) { $('#previewImg').attr('src', e.target.result); }; reader.readAsDataURL(file); }
    });

    // Form kaydet (FormData ile dosya yükleme)
    $('#btnSave').on('click', function () {
        var formData = new FormData($('#crudForm')[0]);
        $.ajax({
            url: apiUrl, type: 'POST', data: formData, processData: false, contentType: false,
            success: function (r) { if (r.success) { showSuccess(r.message); $('#crudModal').modal('hide'); load(); } else showError(r.message); },
            error: function () { showError('Bağlantı hatası.'); },
            dataType: 'json'
        });
    });

    $('#searchBox').on('input', function () { clearTimeout(searchTimer); curSearch = $(this).val(); searchTimer = setTimeout(function () { curPage = 1; load(); }, 400); });
    $('#perPage').on('change', function () { curPerPage = parseInt($(this).val()); curPage = 1; load(); });
    load();
</script>