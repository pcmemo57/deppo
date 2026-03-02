<?php
/**
 * Çeki Listesi (Packing List) — Yönetim Sayfası
 */
requireRole(ROLE_ADMIN, ROLE_USER);

$customers = Database::fetchAll("SELECT id, name FROM tbl_dp_customers WHERE hidden=0 AND is_active=1 ORDER BY name ASC");
?>
<style>
    /* ───────────────────────────────────────────
     KART HEADER — araç çubuğu hizalama (stock_out_orders v3 ile tam uyumlu)
    ─────────────────────────────────────────── */
    .card-header {
        display: block !important;
        /* D-flex'i bozuyoruz ki float:right çalışsın */
    }

    .card-header .card-title {
        font-size: 1.5rem !important;
        font-weight: 700;
        margin: 0;
        float: left;
    }

    .card-header .card-tools {
        float: right;
    }

    /* Sayfa tepesindeki tablo stilleri */
    .packing-list-table th {
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.02em;
        color: #6c757d;
        background-color: #f8f9fa;
    }

    .x-small {
        font-size: 0.75rem !important;
    }

    .btn-soft-primary {
        background: #e0e7ff;
        color: #4338ca;
        border: none;
        font-weight: 600;
        font-size: 0.75rem;
        padding: 4px 12px;
        border-radius: 6px;
    }

    .btn-soft-primary:hover {
        background: #c7d2fe;
        color: #3730a3;
    }

    .parcel-card {
        border: 1px solid #e2e8f0 !important;
        transition: all 0.2s;
    }

    .parcel-card:hover {
        border-color: #cbd5e1 !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .modal-xl {
        max-width: 1150px;
    }

    .desi-badge {
        font-size: 0.7rem;
        padding: 2px 8px;
        background: #f1f5f9;
        color: #475569;
        border-radius: 4px;
        font-weight: 600;
    }

    /* ───────────────────────────────────────────
     SELECT2 COMPACT STYLE (Gereksiz genişlik ve yükseklik düzeltmesi)
    ─────────────────────────────────────────── */
    .select2-container--bootstrap-5 .select2-selection {
        min-height: 30px !important;
        padding: 0 8px !important;
        font-size: 0.8rem !important;
        border-radius: 4px !important;
        border: 1px solid #e2e8f0 !important;
        background-color: #fff !important;
    }

    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
        padding-left: 0 !important;
        line-height: 28px !important;
        color: #334155 !important;
    }

    .select2-container--bootstrap-5 .select2-dropdown .select2-results__option {
        padding: 4px 10px !important;
        font-size: 0.8rem !important;
    }

    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
        height: 28px !important;
    }

    /* Ürün satırındaki select2 için ekstra kompaktlık */
    .item-row .select2-container--bootstrap-5 .select2-selection {
        background-color: #fff !important;
        border-color: #e2e8f0 !important;
    }

    .select2-container {
        width: 100% !important;
    }

    /* İkonları gizle veya küçült (Seçim kutusu içindekiler) */
    .input-icon-wrap .field-icon {
        display: none;
        /* Select2 içinde ikon kalabalık yapıyor */
    }

    .select2-container--bootstrap-5 .select2-selection {
        padding-left: 8px !important;
        /* İkon kalkınca soldan boşluğu düzelt */
    }

    /* Miktar ve Ölçü Inputları için Select2 uyumlu CSS */
    .parcel-card .form-control-sm {
        height: 30px !important;
        font-size: 0.8rem !important;
        border-radius: 4px !important;
        border: 1px solid #e2e8f0 !important;
        padding: 0 8px !important;
        background-color: #fff !important;
    }

    .parcel-card .form-control-sm:focus {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1) !important;
    }
</style>

<div class="row">
    <div class="col-12">
        <div class="card card-primary card-outline shadow-sm">
            <div class="card-header py-3">
                <h3 class="card-title"><i class="fas fa-boxes me-2 text-primary"></i> Çeki Listeleri (Packing List)</h3>
                <div class="card-tools">
                    <button class="btn btn-primary btn-sm px-3 shadow-sm" onclick="showPackingListModal()">
                        <i class="fas fa-plus me-1"></i> Yeni Çeki Listesi
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 packing-list-table" id="packingListTable">
                        <thead>
                            <tr>
                                <th class="ps-3" style="width: 140px;">Liste No</th>
                                <th>Müşteri</th>
                                <th class="text-center">Koli Adedi</th>
                                <th class="text-end">Top. Ağırlık</th>
                                <th class="text-end">Top. Hacim (Desi)</th>
                                <th class="text-center">Tarih</th>
                                <th class="text-end pe-3" style="width: 150px;">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody id="packingListBody">
                            <tr>
                                <td colspan="7" class="text-center p-4">Yükleniyor...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Packing List Modal -->
<div class="modal fade" id="packingListModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius:12px; overflow:hidden;">
            <div class="modal-header bg-primary text-white border-0 py-3">
                <h5 class="modal-title fw-bold" id="packingListModalTitle">Yeni Çeki Listesi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light p-4">
                <form id="packingListForm">
                    <input type="hidden" name="id" id="pl_id">

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Müşteri Seçin <span
                                    class="text-danger">*</span></label>
                            <select name="customer_id" id="pl_customer_id" class="form-select select2-customer"
                                required></select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Liste No <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white"><i class="fas fa-hashtag text-muted"></i></span>
                                <input type="text" name="list_no" id="pl_list_no" class="form-control"
                                    placeholder="PL-2024-001" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Notlar</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white"><i
                                        class="fas fa-sticky-note text-muted"></i></span>
                                <input type="text" name="notes" id="pl_notes" class="form-control"
                                    placeholder="İşlem notu...">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold m-0 text-dark">
                            <i class="fas fa-layer-group me-2 text-warning"></i>Koli & Ürün Detayları
                            <span class="ms-3 text-muted x-small fw-normal">(Desi = W*L*H / 3000)</span>
                        </h6>
                        <button type="button" class="btn btn-outline-primary btn-xs px-3" onclick="addParcel()">
                            <i class="fas fa-plus me-1"></i> Koli Ekle
                        </button>
                    </div>

                    <div id="parcelsContainer">
                        <!-- AJAX -->
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-white border-0 shadow-sm">
                <div class="me-auto">
                    <span class="text-muted small">Genel Toplam: </span>
                    <span class="fw-bold text-dark" id="modalTotalWeight">0.00</span> <small>kg</small> /
                    <span class="fw-bold text-primary" id="modalTotalDesi">0.00</span> <small>Desi</small>
                </div>
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-primary px-5 shadow-sm" onclick="savePackingList()">
                    <i class="fas fa-save me-2"></i> Kaydet
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Template: Koli Kartı -->
<template id="parcelTemplate">
    <div class="card parcel-card mb-3 border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-2 border-bottom">
            <span class="fw-bold text-dark small">
                <i class="fas fa-box text-warning me-2"></i>KOLİ #<span class="parcel-no-label">1</span>
                <span class="ms-2 desi-badge parcel-desi-info">0.00 Desi</span>
            </span>
            <button type="button" class="btn btn-link text-danger btn-sm p-0 text-decoration-none"
                onclick="removeParcel(this)">
                <i class="fas fa-trash-alt me-1"></i> Koli Sil
            </button>
        </div>
        <div class="card-body p-3">
            <div class="row g-2 mb-3 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label x-small fw-bold text-muted mb-1">Ağırlık (kg)</label>
                    <input type="number" step="0.01" class="form-control form-control-sm parcel-weight" value="0"
                        oninput="calculateModalTotals()">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label x-small fw-bold text-muted mb-1">En (cm)</label>
                    <input type="number" step="0.1" class="form-control form-control-sm parcel-width" value="0"
                        oninput="calculateModalTotals()">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label x-small fw-bold text-muted mb-1">Boy (cm)</label>
                    <input type="number" step="0.1" class="form-control form-control-sm parcel-length" value="0"
                        oninput="calculateModalTotals()">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label x-small fw-bold text-muted mb-1">Yükseklik (cm)</label>
                    <input type="number" step="0.1" class="form-control form-control-sm parcel-height" value="0"
                        oninput="calculateModalTotals()">
                </div>
                <div class="col-12 col-md-4 text-end">
                    <button type="button" class="btn btn-soft-primary btn-sm w-100" onclick="addItemToParcel(this)">
                        <i class="fas fa-cart-plus me-1"></i> Ürün Ekle
                    </button>
                </div>
            </div>

            <div class="table-responsive rounded border">
                <table class="table table-sm table-striped m-0">
                    <thead class="bg-light x-small fw-bold">
                        <tr>
                            <th style="width: 70%;">Ürün Adı / Kodu</th>
                            <th class="text-center" style="width: 25%;">Miktar</th>
                            <th class="text-center" style="width: 5%;"></th>
                        </tr>
                    </thead>
                    <tbody class="parcel-items-body">
                        <!-- AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<!-- Template: Ürün Satırı -->
<template id="itemRowTemplate">
    <tr class="item-row align-middle">
        <td>
            <select class="form-select form-select-sm select2-product-item" required></select>
        </td>
        <td>
            <div class="input-group input-group-sm">
                <input type="number" step="0.01" class="form-control form-control-sm text-center item-qty" value="1"
                    required>
            </div>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-link text-danger btn-sm p-0" onclick="removeItemRow(this)">
                <i class="fas fa-times"></i>
            </button>
        </td>
    </tr>
</template>

<script>
    function loadPackingLists() {
        $.get('api/packing_list.php', { action: 'list' }, function (r) {
            if (r.success) {
                let html = '';
                r.data.forEach(row => {
                    html += `
                        <tr class="border-bottom">
                            <td class="ps-3 fw-bold text-dark font-monospace small">${row.list_no}</td>
                            <td>
                                <div class="fw-bold">${row.customer_name}</div>
                                <div class="text-muted x-small">${row.notes || ''}</div>
                            </td>
                            <td class="text-center"><span class="badge bg-light border text-dark font-weight-normal">${row.parcel_count} Koli</span></td>
                            <td class="text-end fw-bold">${formatTurkish(row.total_weight_kg)} <small>kg</small></td>
                            <td class="text-end fw-bold text-primary">${formatTurkish(row.total_vol_desi)} <small>Desi</small></td>
                            <td class="text-center text-muted x-small">${new Date(row.created_at).toLocaleDateString('tr-TR')}</td>
                            <td class="text-end pe-3">
                                <div class="btn-group btn-group-xs">
                                    <button class="btn btn-outline-secondary" onclick="printPackingList(${row.id})" title="Yazdır"><i class="fas fa-print"></i></button>
                                    <button class="btn btn-outline-warning" onclick="downloadPDF(${row.id})" title="PDF İndir"><i class="fas fa-file-pdf"></i></button>
                                    <button class="btn btn-outline-primary" onclick="editPackingList(${row.id})" title="Düzenle"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-outline-danger" onclick="deletePackingList(${row.id})" title="Sil"><i class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                    `;
                });
                $('#packingListBody').html(html || '<tr><td colspan="7" class="text-center p-5 text-muted">Henüz kayıtlı çeki listesi bulunmuyor.</td></tr>');
            }
        });
    }

    function calculateModalTotals() {
        let totalW = 0;
        let totalD = 0;
        $('#parcelsContainer .parcel-card').each(function () {
            let $card = $(this);
            let w = parseFloat($card.find('.parcel-weight').val()) || 0;
            let width = parseFloat($card.find('.parcel-width').val()) || 0;
            let length = parseFloat($card.find('.parcel-length').val()) || 0;
            let height = parseFloat($card.find('.parcel-height').val()) || 0;

            let desi = (width * length * height) / 3000;
            $card.find('.parcel-desi-info').text(desi.toFixed(2) + ' Desi');

            totalW += w;
            totalD += desi;
        });

        $('#modalTotalWeight').text(totalW.toFixed(2));
        $('#modalTotalDesi').text(totalD.toFixed(2));
    }

    function initSelect2Customer(selector) {
        $(selector).select2({
            theme: 'bootstrap-5',
            width: '100%',
            dropdownParent: $('#packingListModal'),
            ajax: {
                url: 'api/customers.php',
                data: params => ({ action: 'search', q: params.term }),
                processResults: data => data
            }
        });
    }

    function initSelect2Product(selector) {
        $(selector).select2({
            theme: 'bootstrap-5',
            width: '100%',
            dropdownParent: $('#packingListModal'),
            ajax: {
                url: 'api/products.php',
                data: params => ({ action: 'search_select2', q: params.term }),
                processResults: data => ({
                    results: data.results.map(i => ({ id: i.id, text: i.text, unit: i.unit }))
                })
            }
        }).on('select2:select', function (e) {
            let data = e.params.data;
            let $row = $(this).closest('tr');
            let $card = $(this).closest('.parcel-card');

            // Duplicate Check
            let isDuplicate = false;
            $card.find('.select2-product-item').not(this).each(function () {
                if ($(this).val() == data.id) {
                    isDuplicate = true;
                    return false;
                }
            });

            if (isDuplicate) {
                showError('Bu ürün bu koliye zaten eklenmiş!');
                $(this).val(null).trigger('change');
                return;
            }

            $row.find('.item-unit').text((data.unit || 'ADET').toUpperCase());
        });
    }

    function showPackingListModal() {
        $('#pl_id').val('');
        $('#packingListForm')[0].reset();
        $('#pl_customer_id').val(null).trigger('change');
        $('#parcelsContainer').empty();
        $('#packingListModalTitle').text('Yeni Çeki Listesi Oluştur');

        $.get('api/packing_list.php', { action: 'generate_no' }, function (r) {
            if (r.success) $('#pl_list_no').val(r.data);
        });

        addParcel();
        initSelect2Customer('.select2-customer');
        calculateModalTotals();
        $('#packingListModal').modal('show');
    }

    function addParcel() {
        let template = document.getElementById('parcelTemplate').innerHTML;
        let count = $('#parcelsContainer .parcel-card').length + 1;
        let $parcel = $(template);
        $parcel.find('.parcel-no-label').text(count);
        $('#parcelsContainer').append($parcel);
        calculateModalTotals();
    }

    function removeParcel(btn) {
        $(btn).closest('.parcel-card').fadeOut(200, function () {
            $(this).remove();
            $('#parcelsContainer .parcel-card').each(function (index) {
                $(this).find('.parcel-no-label').text(index + 1);
            });
            calculateModalTotals();
        });
    }

    function addItemToParcel(btn) {
        let template = document.getElementById('itemRowTemplate').innerHTML;
        let $row = $(template);
        $(btn).closest('.parcel-card').find('.parcel-items-body').append($row);
        initSelect2Product($row.find('.select2-product-item'));
    }

    function removeItemRow(btn) {
        $(btn).closest('tr').remove();
    }

    function savePackingList() {
        let customerId = $('#pl_customer_id').val();
        let listNo = $('#pl_list_no').val();
        if (!customerId || !listNo) {
            showError('Lütfen müşteri ve liste numarasını giriniz.');
            return;
        }

        let parcels = [];
        $('#parcelsContainer .parcel-card').each(function () {
            let $card = $(this);
            let items = [];
            $card.find('.item-row').each(function () {
                let pid = $(this).find('.select2-product-item').val();
                let qty = $(this).find('.item-qty').val();
                if (pid && qty) items.push({ product_id: pid, quantity: qty });
            });

            parcels.push({
                weight: $card.find('.parcel-weight').val(),
                width: $card.find('.parcel-width').val(),
                length: $card.find('.parcel-length').val(),
                height: $card.find('.parcel-height').val(),
                items: items
            });
        });

        if (parcels.length === 0) {
            showError('En az bir koli eklemelisiniz.');
            return;
        }

        let data = {
            action: 'save',
            id: $('#pl_id').val(),
            customer_id: customerId,
            list_no: listNo,
            notes: $('#pl_notes').val(),
            parcels: JSON.stringify(parcels) // JSON olarak gönderiyoruz
        };

        $.post('api/packing_list.php', data, function (r) {
            if (r.success) {
                showSuccess(r.message);
                $('#packingListModal').modal('hide');
                loadPackingLists();
            } else {
                showError(r.message);
            }
        });
    }

    function editPackingList(id) {
        $.get('api/packing_list.php', { action: 'get', id: id }, function (r) {
            if (r.success) {
                showPackingListModal();
                $('#pl_id').val(r.data.list.id);
                $('#pl_list_no').val(r.data.list.list_no);
                $('#pl_notes').val(r.data.list.notes);
                $('#packingListModalTitle').text('Çeki Listesini Düzenle');

                let opt = new Option(r.data.list.customer_name, r.data.list.customer_id, true, true);
                $('#pl_customer_id').append(opt).trigger('change');

                $('#parcelsContainer').empty();
                r.data.parcels.forEach((p, pIdx) => {
                    addParcel();
                    let $card = $('#parcelsContainer .parcel-card').last();
                    $card.find('.parcel-weight').val(p.weight_kg);
                    $card.find('.parcel-width').val(p.width_cm);
                    $card.find('.parcel-length').val(p.length_cm);
                    $card.find('.parcel-height').val(p.height_cm);

                    p.items.forEach(item => {
                        addItemToParcel($card.find('button[onclick="addItemToParcel(this)"]')[0]);
                        let $row = $card.find('.item-row').last();
                        let pOpt = new Option(item.product_name + (item.product_code ? ' [' + item.product_code + ']' : ''), item.product_id, true, true);
                        $row.find('.select2-product-item').append(pOpt).trigger('change');
                        $row.find('.item-qty').val(item.quantity);
                        $row.find('.item-unit').text((item.unit || 'ADET').toUpperCase());
                    });
                });
                calculateModalTotals();
            }
        });
    }

    function deletePackingList(id) {
        confirmAction('Bu çeki listesini silmek istediğinize emin misiniz? Bu işlem geri alınamaz.', function () {
            $.post('api/packing_list.php', { action: 'delete', id: id }, function (r) {
                if (r.success) {
                    showSuccess(r.message);
                    loadPackingLists();
                } else {
                    showError(r.message);
                }
            });
        });
    }

    function printPackingList(id) {
        window.open('pages/packing_list_print.php?id=' + id, '_blank');
    }

    function downloadPDF(id) {
        window.open('pages/packing_list_print.php?id=' + id + '&format=pdf', '_blank');
    }

    $(function () {
        loadPackingLists();
    });
</script>