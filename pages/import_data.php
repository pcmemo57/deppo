<?php
/**
 * Veri İçe Aktarma Sayfası (Admin ve User/Program Yöneticisi için)
 */
requireRole(ROLE_ADMIN, ROLE_USER);

$importType = $_GET['type'] ?? '';
?>

<div class="row">
    <div class="col-12">
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header">
                <h3 class="card-title fw-bold text-primary">
                    <i class="fas fa-file-excel me-2"></i>Toplu Veri İçe Aktar (Excel)
                </h3>
            </div>
            <div class="card-body">
                <div class="alert alert-info border-left-info shadow-sm mb-4">
                    <h5><i class="icon fas fa-info-circle"></i> Nasıl Kullanılır?</h5>
                    <ol class="mb-0">
                        <li>Aşağıdan aktarmak istediğiniz <strong>Tablo Türünü</strong> seçin.</li>
                        <li><strong>"Örnek Şablonu İndir"</strong> butonuna basarak Excel dosyasını bilgisayarınıza
                            indirin.</li>
                        <li>İndirdiğiniz dosyadaki sütun başlıklarını değiştirmeden verilerinizi doldurun.</li>
                        <li>Doldurduğunuz dosyayı <strong>"Excel Dosyası"</strong> alanından seçin ve <strong>"İçe
                                Aktar"</strong> butonuna basın.</li>
                    </ol>
                </div>

                <div class="row align-items-end mb-4">
                    <div class="col-md-5">
                        <label class="form-label fw-bold">1. Aktarılacak Tablo Türü</label>
                        <select id="importType" class="form-select select2-simple">
                            <option value="" disabled selected>İndirilecek Şablonu Seçin</option>
                            <option value="products" <?= $importType === 'products' ? 'selected' : '' ?>>Ürünler</option>
                            <option value="customers" <?= $importType === 'customers' ? 'selected' : '' ?>>Müşteriler
                            </option>
                            <option value="suppliers" <?= $importType === 'suppliers' ? 'selected' : '' ?>>Tedarikçiler
                            </option>
                        </select>
                    </div>
                    <div class="col-md-7 text-md-end mt-3 mt-md-0">
                        <button type="button" class="btn btn-outline-success btn-lg" onclick="downloadImportTemplate()">
                            <i class="fas fa-download me-2"></i> Örnek Şablonu İndir
                        </button>
                    </div>
                </div>

                <hr class="my-4">

                <div class="row align-items-end">
                    <div class="col-md-8">
                        <label class="form-label fw-bold">2. Excel Dosyası Seçin (.xlsx)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-file-upload"></i></span>
                            <input class="form-control" type="file" id="importFile" accept=".xlsx, .xls">
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <button type="button" class="btn btn-primary w-100 fw-bold" id="btnImportData"
                            style="height: 38px;">
                            <i class="fas fa-upload me-2"></i> Verileri İçe Aktar
                        </button>
                    </div>
                </div>
                <div class="small text-muted mt-2">
                    <i class="fas fa-exclamation-circle me-1"></i> Sadece <strong>.xlsx</strong> veya
                    <strong>.xls</strong> formatındaki dosyalar kabul edilir.
                </div>
            </div>
            <div class="card-footer bg-light">
                <small class="text-muted text-uppercase fw-bold">Önemli Not:</small>
                <small class="text-muted d-block mt-1">
                    Veri aktarımı sırasında mevcut verileriniz silinmez, Excel dosyasındaki satırlar yeni kayıt olarak
                    eklenir.
                    Müşteri ve Tedarikçi aktarımında e-posta adresi sistemde kayıtlıysa o satır atlanacaktır.
                </small>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        // Select2 Simple Init
        $('.select2-simple').each(function () {
            $(this).select2({
                theme: 'bootstrap-5',
                width: '100%',
                dropdownParent: $(this).parent(),
                minimumResultsForSearch: Infinity
            });
        });

        // ═══════════ EXCEL İÇE AKTAR (IMPORT) İŞLEMLERİ ═══════════
        $('#btnImportData').on('click', function () {
            var fileInput = document.getElementById('importFile');
            if (!fileInput.files || fileInput.files.length === 0) {
                showError("Lütfen önce bir Excel (.xlsx) dosyası seçin.");
                return;
            }

            var btn = $(this);
            var originalBtnText = btn.html();
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Dosya Okunuyor...');

            var file = fileInput.files[0];
            var reader = new FileReader();

            reader.onload = function (e) {
                try {
                    var data = new Uint8Array(e.target.result);
                    var workbook = XLSX.read(data, { type: 'array' });

                    // İlk sayfayı al
                    var firstSheetName = workbook.SheetNames[0];
                    var worksheet = workbook.Sheets[firstSheetName];

                    // JSON formatına çevir (İlk satır başlık kabul edilir)
                    var jsonRows = XLSX.utils.sheet_to_json(worksheet, { defval: "" });

                    if (jsonRows.length === 0) {
                        showError("Dosya içi boş görünüyor. Örnek şablona göre doldurun.");
                        btn.prop('disabled', false).html(originalBtnText);
                        return;
                    }

                    // API'ye gönder
                    btn.html('<i class="fas fa-spinner fa-spin me-2"></i> Sunucuya Kaydediliyor...');
                    var type = $('#importType').val();

                    if (!type) {
                        showError("Lütfen önce bir tablo türü seçin.");
                        btn.prop('disabled', false).html(originalBtnText);
                        return;
                    }

                    $.post('<?= BASE_URL ?>/api/import.php', {
                        type: type,
                        data: JSON.stringify(jsonRows),
                        csrf_token: $('meta[name="csrf-token"]').attr('content')
                    }, function (r) {
                        btn.prop('disabled', false).html(originalBtnText);

                        if (r.success) {
                            var msg = r.message;
                            if (r.data && r.data.errors && r.data.errors.length > 0) {
                                msg += "\n\nHatalar:\n" + r.data.errors.slice(0, 10).join("\n");
                                if (r.data.errors.length > 10) msg += "\n... ve " + (r.data.errors.length - 10) + " hata daha.";

                                Swal.fire({
                                    title: 'İşlem Tamamlandı',
                                    text: msg,
                                    icon: 'info',
                                    confirmButtonText: 'Tamam'
                                }).then(() => { location.reload(); });
                            } else {
                                showSuccess(msg);
                                setTimeout(() => { location.reload(); }, 1500);
                            }
                        } else {
                            // Tamamen başarısız
                            var errorMsg = r.message;
                            if (r.data && r.data.errors && r.data.errors.length > 0) {
                                errorMsg += "\n\nHata Detayları:\n" + r.data.errors.slice(0, 10).join("\n");
                            }
                            showError(errorMsg);
                        }
                    }, 'json').fail(function () {
                        btn.prop('disabled', false).html(originalBtnText);
                        showError("Sunucu bağlantı hatası oluştu.");
                    });

                } catch (ex) {
                    btn.prop('disabled', false).html(originalBtnText);
                    showError("Excel dosyası okunurken hata: " + ex.message);
                }
            };

            reader.onerror = function () {
                btn.prop('disabled', false).html(originalBtnText);
                showError("Dosya okunurken donanımsal hata oluştu.");
            };

            reader.readAsArrayBuffer(file);
        });
    });

    function downloadImportTemplate() {
        var type = $('#importType').val();
        if (!type) {
            showError("Lütfen indirilecek şablon için bir tablo türü seçin.");
            return;
        }
        var wb = XLSX.utils.book_new();
        var data = [];
        var sheetName = "";
        var fileName = "";

        if (type === 'products') {
            sheetName = "Urunler";
            fileName = "Ornek_Urun_Sablonu.xlsx";
            data = [
                ["Ürün Adı", "Ürün Kodu", "Birim (Adet vb.)", "Açıklama", "Alarm Seviyesi"],
                ["Örnek Ürün", "URUN-001", "Adet", "Ürün açıklaması buraya gelecek", 10]
            ];
        } else if (type === 'customers') {
            sheetName = "Musteriler";
            fileName = "Ornek_Musteri_Sablonu.xlsx";
            data = [
                ["Ad / Ünvan", "Yetkili Kişi", "E-posta", "Telefon", "Adres"],
                ["Örnek Müşteri", "Mehmet Yılmaz", "musteri@ornek.com", "05554443322", "Müşteri adresi..."]
            ];
        } else if (type === 'suppliers') {
            sheetName = "Tedarikciler";
            fileName = "Ornek_Tedarikci_Sablonu.xlsx";
            data = [
                ["Ad / Ünvan", "Yetkili Kişi", "E-posta", "Telefon", "Adres"],
                ["Örnek Tedarikçi", "Ayşe Kaya", "tedarikci@ornek.com", "02123334455", "Tedarikçi adresi..."]
            ];
        }

        var ws = XLSX.utils.aoa_to_sheet(data);
        // Sütun genişlikleri
        ws['!cols'] = [{ wch: 30 }, { wch: 20 }, { wch: 20 }, { wch: 30 }, { wch: 50 }];

        XLSX.utils.book_append_sheet(wb, ws, sheetName);
        XLSX.utils.book_append_sheet(wb, ws, "Açıklama"); // Boş ikinci sayfa (örnek olsun diye)
        XLSX.writeFile(wb, fileName);
    }
</script>