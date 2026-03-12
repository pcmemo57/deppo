<?php
/**
* Ürün Yönetimi — Resimli
* QR Kod kütüphanesi eklendi
*/
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<?php
requireRole(ROLE_ADMIN, ROLE_USER);
$warehouses = Database::fetchAll("SELECT id, name FROM tbl_dp_warehouses WHERE hidden=0 AND is_active=1 ORDER BY name");
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
                    <div class="input-group input-group-sm" style="width: 240px;">
                        <input type="text" id="searchBox" class="form-control" placeholder="Ara...">
                        <button class="btn btn-outline-secondary border-start-0 d-none" id="btnClearSearch" type="button">
                            <i class="fas fa-times"></i>
                        </button>
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
                <button type="button" class="btn btn-link text-white p-0 border-0" data-bs-dismiss="modal"><i
                        class="fas fa-times"></i></button>
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
                            <input type="file" name="image" id="imageInput" class="form-control" accept="image/*">
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
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Genel (Toplam) Stok Alarmı <i
                                                class="fas fa-bell text-warning ms-1"></i></label>
                                        <input type="number" name="stock_alarm" class="form-control" value="0" min="0" step="1"
                                               oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                        <small class="text-muted">Tüm depoların toplam stoğu bu değerin altına düşerse uyarı verir.</small>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3"><label class="form-label">Açıklama</label>
                                        <textarea name="description" class="form-control" rows="3"></textarea>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label text-primary font-weight-bold">
                                            <i class="fas fa-warehouse me-1"></i> Depo Bazlı Alarm Seviyeleri (Bağımsız)
                                        </label>
                                        <div class="row g-2" id="warehouseAlarmsContainer">
                                            <?php foreach ($warehouses as $w): ?>
                                                <div class="col-sm-6 col-md-4">
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text border-end-0 bg-light" style="font-size: 0.75rem; width: 60%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= e($w['name']) ?>">
                                                            <?= e($w['name']) ?>
                                                        </span>
                                                        <input type="number" name="warehouse_alarms[<?= (int) $w['id'] ?>]" 
                                                               class="form-control wh-alarm-input" placeholder="0" min="0" step="1"
                                                               oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                                               data-wid="<?= (int) $w['id'] ?>" style="width: 40%;">
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <small class="text-muted">İlgili depodaki stok bu değerin altına düşerse (toplam stoktan bağımsız) uyarı verir.</small>
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

<!-- Image Crop Modal -->
<div class="modal fade" id="cropModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-crop-alt me-2"></i>Görseli Düzenle</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="img-container" style="max-height: 500px;">
                    <img id="cropImage" src="" style="max-width: 100%;">
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" id="btnDoCrop">
                    <i class="fas fa-check me-1"></i>Kırp ve Uygula
                </button>
            </div>
        </div>
    </div>
</div>
<!-- QR Code Modal -->
<div class="modal fade" id="qrModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white py-2">
                <h6 class="modal-title">Ürün Barkodu</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-4">
                <div id="qrcode" class="d-inline-block p-3 bg-white rounded shadow-sm"></div>
                <div class="mt-3 fw-bold text-dark" id="qrText"></div>
                <div class="text-muted small" id="qrSubText"></div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm w-100" data-bs-dismiss="modal">Kapat</button>
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
            var baseCurrency = '<?= get_setting('base_currency', 'EUR') ?>';
            $.each(r.data.data, function (i, u) {
                var imgSrc = u.image ? '<?= BASE_URL ?>/images/UrunResim/' + encodeURIComponent(u.image) : noImg;
                var isLowStock = (u.stock_alarm > 0 && (u.total_stock || 0) < u.stock_alarm);
                var alarmText = u.stock_alarm > 0 ? u.stock_alarm : 'Kapalı';
                var stockClass = isLowStock ? 'text-danger fw-bold' : 'text-muted';

                html += '<tr class="' + (isLowStock ? 'table-warning' : '') + '">';
                html += '<td><img src="' + imgSrc + '" style="width:50px;height:50px;object-fit:cover;border-radius:6px; cursor:pointer;" onerror="this.src=\'' + noImg + '\'" onclick="showImagePreview(\'' + imgSrc + '\')" title="Büyütmek için tıklayın"></td>';
                html += '<td><strong>' + esc(u.name) + '</strong></td>';
                html += '<td><code>' + esc(u.code || '—') + '</code></td>';
                html += '<td>' + esc(u.unit) + ' <br><small class="' + stockClass + '">' +
                    (isLowStock ? '<i class="fas fa-exclamation-triangle me-1"></i> ' : '') +
                    'Alarm: ' + alarmText + ' (Stok: ' + formatQty(u.total_stock || 0) + ')</small></td>';
                html += '<td><strong>' + (u.last_price_eur ? formatTurkish(parseFloat(u.last_price_eur).toFixed(2)) : '—') + '</strong> <small>' + baseCurrency + '</small></td>';
                html += '<td>';
                html += '<button class="btn btn-xs btn-outline-dark me-1" onclick="showQR(\'' + (u.code || u.id) + '\', \'' + esc(u.name) + '\')" title="Barkod Göster"><i class="fas fa-qrcode"></i></button>';
                html += '<button class="btn btn-xs btn-info me-1" onclick="editRow(' + u.id + ')"><i class="fas fa-edit"></i></button>';
                html += '<button class="btn btn-xs btn-danger" onclick="deleteRow(' + u.id + ')"><i class="fas fa-trash"></i></button>';
                html += '</td></tr>';
            });
            $('#tableBody').html(html || '<tr><td colspan="6" class="text-center text-muted p-3">Kayıt bulunamadı</td></tr>');
            $('#totalCount').text('Toplam: ' + formatQty(r.data.total) + ' kayıt');
            renderPag(r.data.total);
        }, 'json');
    }
    function renderPag(total) { var pages = Math.ceil(total / curPerPage); if (pages <= 1) { $('#pagination').html(''); return; } var html = '<ul class="pagination pagination-sm">', s = Math.max(1, curPage - 2), e = Math.min(pages, curPage + 2); if (curPage > 1) html += '<li class="page-item"><a class="page-link" data-p="' + (curPage - 1) + '" href="#">&laquo;</a></li>'; for (var p = s; p <= e; p++)html += '<li class="page-item' + (p === curPage ? ' active' : '') + '"><a class="page-link" data-p="' + p + '" href="#">' + p + '</a></li>'; if (curPage < pages) html += '<li class="page-item"><a class="page-link" data-p="' + (curPage + 1) + '" href="#">&raquo;</a></li>'; html += '</ul>'; $('#pagination').html(html).find('a').on('click', function (e) { e.preventDefault(); curPage = parseInt($(this).data('p')); load(); }); }

    function openModal() {
        $('#formAction').val('add'); $('#formId').val(''); $('#crudForm')[0].reset();
        $('[name="unit"]').val('Adet').trigger('change');
        setStatus(1);
        $('#previewImg').attr('src', noImg); $('#modalTitle').text('Ürün Ekle'); 
        $('.wh-alarm-input').val(''); // Reset warehouse alarms
        $('#crudModal').modal('show');
    }
    function editRow(id) {
        $.get(apiUrl, { action: 'get', id: id }, function (r) {
            if (!r.success) return showError(r.message);
            var u = r.data;
            $('#formAction').val('edit'); $('#formId').val(u.id);
            $('[name="name"]').val(u.name); $('[name="code"]').val(u.code);
            $('[name="unit"]').val(u.unit).trigger('change');
            $('[name="stock_alarm"]').val(u.stock_alarm);
            $('[name="description"]').val(u.description);
            setStatus(u.is_active);
            var imgSrc = u.image ? '<?= BASE_URL ?>/images/UrunResim/' + u.image : noImg;
            $('#previewImg').attr('src', imgSrc);

            // Depo bazlı alarmları doldur
            $('.wh-alarm-input').val(''); // Önce temizle
            if (u.warehouse_alarms) {
                u.warehouse_alarms.forEach(function(wa) {
                    $('.wh-alarm-input[data-wid="' + wa.warehouse_id + '"]').val(wa.stock_alarm);
                });
            }

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

    var cropper;
    var croppedBlob = null;

    // Resim seçildiğinde cropper modali aç
    $('#imageInput').on('change', function () {
        var file = this.files[0];
        if (!file) return;

        var reader = new FileReader();
        reader.onload = function (e) {
            $('#cropImage').attr('src', e.target.result);
            $('#cropModal').modal('show');
        };
        reader.readAsDataURL(file);
    });

    // Modal açıldığında cropper'ı başlat
    $('#cropModal').on('shown.bs.modal', function () {
        cropper = new Cropper(document.getElementById('cropImage'), {
            aspectRatio: 1,
            viewMode: 2,
            autoCropArea: 1,
        });
    }).on('hidden.bs.modal', function () {
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
        // Eğer kırpma yapılmadıysa inputu temizle (isteğe bağlı)
        if (!croppedBlob) {
            $('#imageInput').val('');
        }
    });

    // Kırpma işlemini uygula
    $('#btnDoCrop').on('click', function () {
        if (!cropper) return;

        var canvas = cropper.getCroppedCanvas({
            width: 800,
            height: 800,
        });

        canvas.toBlob(function (blob) {
            croppedBlob = blob;
            var url = URL.createObjectURL(blob);
            $('#previewImg').attr('src', url);
            $('#cropModal').modal('hide');
        }, 'image/jpeg', 0.9);
    });

    // Form kaydet (FormData ile dosya yükleme)
    $('#btnSave').on('click', function () {
        var formData = new FormData($('#crudForm')[0]);

        // Eğer yeni bir görsel kırpıldıysa onu ekle
        if (croppedBlob) {
            formData.set('image', croppedBlob, 'product.jpg');
        }

        $.ajax({
            url: apiUrl, type: 'POST', data: formData, processData: false, contentType: false,
            success: function (r) {
                if (r.success) {
                    showSuccess(r.message);
                    $('#crudModal').modal('hide');
                    load();
                    croppedBlob = null; // Reset
                } else showError(r.message);
            },
            error: function () { showError('Bağlantı hatası.'); },
            dataType: 'json'
        });
    });

    // QR Code Function
    var qr;
    function showQR(code, name) {
        $('#qrText').text(name);
        $('#qrSubText').text('Kod: ' + code);
        $('#qrcode').html('');
        qr = new QRCode(document.getElementById("qrcode"), {
            text: code,
            width: 200,
            height: 200,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
        $('#qrModal').modal('show');
    }

    $('#searchBox').on('input', function () { 
        const val = $(this).val();
        $('#btnClearSearch').toggleClass('d-none', !val);
        clearTimeout(searchTimer); 
        curSearch = val; 
        searchTimer = setTimeout(function () { curPage = 1; load(); }, 400); 
    });
    $('#btnClearSearch').on('click', function () {
        $('#searchBox').val('').trigger('input');
    });
    $('#perPage').on('change', function () { curPerPage = parseInt($(this).val()); curPage = 1; load(); });
    load();
</script>