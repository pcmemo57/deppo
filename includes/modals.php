<?php
// Global Modals accessible across the application
$procurementStatuses = [
    0 => 'Beklemede',
    1 => 'Teklifler Değerlendiriliyor',
    2 => 'Bütçe Araştırılıyor',
    3 => 'Sipariş Verildi',
    4 => 'Tedarikçi Araştırılıyor',
    5 => 'Tamamlandı'
];
?>

<!-- Tedarik Süreci Modalı -->
<div class="modal fade" id="procurementModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content premium-modal border-0 shadow-lg">
            <div class="modal-header bg-premium py-3" style="background: linear-gradient(135deg, #1a56db, #0c3daa);">
                <h5 class="modal-title fw-bold text-white"><i class="fas fa-truck-loading me-3 text-warning"></i>Tedarik Süreci Güncelleme</h5>
                <button type="button" class="btn btn-link text-white p-0 border-0" data-bs-dismiss="modal"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body p-0">
                <div class="row g-0">
                    <!-- Sol Taraf: Ürün Kartı -->
                    <div class="col-md-5 bg-light p-4 border-end">
                        <div class="text-center mb-3">
                            <img id="proc_product_img" class="img-fluid rounded shadow-sm mb-3 border bg-white p-1"
                                src="" alt="" style="max-height: 200px; width: 100%; object-fit: contain;">
                            <div id="proc_no_img"
                                class="bg-white d-flex align-items-center justify-content-center rounded shadow-sm mb-3 mx-auto border"
                                style="height: 180px; width: 180px;">
                                <i class="fas fa-box text-muted fa-4x"></i>
                            </div>
                        </div>
                        <div class="px-2">
                            <h4 id="proc_product_name_h" class="fw-bold text-dark text-center mb-1"></h4>
                            <p id="proc_product_code_p" class="text-muted text-center small mb-4 border-bottom pb-2">
                            </p>

                            <div class="modal-section-label" style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #6b7a99; margin-bottom: 14px; padding-bottom: 6px; border-bottom: 2px solid #e4e9f0; display: flex; align-items: center; gap: 6px;">
                                <i class="fas fa-history text-primary me-2"></i> Geçmiş Tedarikçiler
                            </div>
                            <div id="proc_supplier_history" class="small text-muted ps-2 bg-white p-3 rounded border">
                                <div class="spinner-border spinner-border-sm text-primary"></div> Yükleniyor...
                            </div>
                        </div>
                    </div>

                    <!-- Sağ Taraf: Süreç Formu -->
                    <div class="col-md-7 p-4 bg-white">
                        <input type="hidden" id="proc_product_id">

                        <div class="modal-section-label" style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #6b7a99; margin-bottom: 14px; padding-bottom: 6px; border-bottom: 2px solid #e4e9f0; display: flex; align-items: center; gap: 6px;">
                            <i class="fas fa-info-circle text-primary me-2"></i> Süreç Bilgileri
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Güncel Durum</label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-tasks field-icon" style="position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: #9aa5be; font-size: 0.82rem; z-index: 5;"></i>
                                <select id="proc_status" class="form-select border-2 shadow-sm ps-5 py-2">
                                    <?php foreach ($procurementStatuses as $val => $label): ?>
                                        <option value="<?= $val ?>"><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-bold">Süreç Notları / Açıklama</label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-comment-dots field-icon" style="position: absolute; left: 11px; top: 15px; color: #9aa5be; font-size: 0.82rem; z-index: 5;"></i>
                                <textarea id="proc_note" class="form-control border-2 shadow-sm ps-5" rows="8"
                                    placeholder="Tedarik süreci ile ilgili gelişmeleri buraya not edin..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top py-3 px-4">
                <button type="button" class="btn btn-outline-secondary me-auto" data-bs-dismiss="modal" style="border-radius: 8px; padding: 9px 22px;">Vazgeç</button>
                <button type="button" class="btn btn-primary" id="btnSaveProcurement" style="background: linear-gradient(135deg, #1a56db, #0c3daa); border: none; border-radius: 8px; padding: 9px 32px; font-weight: 600;">
                    <i class="fas fa-save me-2"></i>Değişiklikleri Kaydet
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function esc(v) { return $('<div/>').text(v || '').html(); }

    function openProcurementModal(id, name, status, note, image, code) {
        $('#proc_product_id').val(id);
        $('#proc_product_name_h').text(name);
        $('#proc_product_code_p').text(code || 'Kodsuz Ürün');
        $('#proc_status').val(status);
        $('#proc_note').val(note);

        if (image) {
            $('#proc_product_img').attr('src', '<?= BASE_URL ?>/images/UrunResim/' + encodeURIComponent(image)).show();
            $('#proc_no_img').hide();
        } else {
            $('#proc_product_img').hide();
            $('#proc_no_img').show();
        }

        $('#proc_supplier_history').html('<div class="spinner-border spinner-border-sm text-primary"></div> Yükleniyor...');
        $.get('<?= BASE_URL ?>/api/products.php', { action: 'get_supplier_history', id: id }, function (r) {
            if (r.success && r.data.length > 0) {
                let sHtml = '<ul class="list-unstyled mb-0">';
                $.each(r.data, function (i, s) {
                    sHtml += `<li class="mb-1"><i class="fas fa-truck text-muted me-2 small"></i>${esc(s.supplier_name)}</li>`;
                });
                sHtml += '</ul>';
                $('#proc_supplier_history').html(sHtml);
            } else {
                $('#proc_supplier_history').html('<span class="text-muted font-italic">Kayıtlı tedarikçi bulunamadı.</span>');
            }
        }, 'json');

        $('#procurementModal').modal('show');
    }

    $(function () {
        $('#btnSaveProcurement').on('click', function () {
            var id = $('#proc_product_id').val();
            var status = $('#proc_status').val();
            var note = $('#proc_note').val();
            var $btn = $(this);

            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Kaydediliyor...');

            $.post('<?= BASE_URL ?>/api/products.php', {
                action: 'update_procurement',
                id: id,
                status: status,
                note: note
            }, function (r) {
                if (r.success) {
                    if(typeof showSuccess === "function") showSuccess(r.message);
                    else alert(r.message);
                    $('#procurementModal').modal('hide');
                    setTimeout(function () { location.reload(); }, 800);
                } else {
                    if(typeof showError === "function") showError(r.message);
                    else alert(r.message);
                    $btn.prop('disabled', false).html('<i class="fas fa-check-circle me-1"></i>Değişiklikleri Kaydet');
                }
            }, 'json').fail(function () {
                alert('Bağlantı hatası oluştu.');
                $btn.prop('disabled', false).html('<i class="fas fa-check-circle me-1"></i>Değişiklikleri Kaydet');
            });
        });
    });
</script>
