<?php
/**
 * Depoya Ürün Girişi
 */
requireRole(ROLE_ADMIN, ROLE_USER);

$warehouses = Database::fetchAll("SELECT id, name FROM tbl_dp_warehouses WHERE hidden=0 AND is_active=1 ORDER BY name");
?>

<div class="row">
    <div class="col-lg-5">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-plus-circle me-2"></i>Yeni Giriş Kaydı</h3>
            </div>
            <div class="card-body">
                <form id="formStockIn">
                    <div class="mb-3">
                        <label class="form-label">Depo <span class="text-danger">*</span></label>
                        <select name="warehouse_id" id="warehouseSelect" class="form-select" required>
                            <option value="">— Depo Seçin —</option>
                            <?php foreach ($warehouses as $w): ?>
                            <option value="<?= e($w['id'])?>">
                                <?= e($w['name'])?>
                            </option>
                            <?php
endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ürün <span class="text-danger">*</span></label>
                        <select name="product_id" id="productSelect" class="form-select" style="width:100%" required>
                            <option value="">— Ürün Seçin —</option>
                        </select>
                        <small class="text-muted">Yazmaya başlayın, ürün adı veya kodu ile arama yapın.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Adet <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" name="quantity" id="quantity" class="form-control" min="0.001"
                                step="any" placeholder="0" required>
                            <span class="input-group-text" id="unitLabel">Adet</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tedarikçi</label>
                        <select name="supplier_id" id="supplierSelect" class="form-select" style="width:100%">
                            <option value="">— Tedarikçi Seçin —</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Birim Fiyat</label>
                        <input type="text" name="unit_price" id="unitPrice" class="form-control price-format"
                            placeholder="0,00">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Para Birimi <span class="text-danger">*</span></label>
                        <select name="currency" id="currency" class="form-select" required>
                            <option value="">— Seçiniz —</option>
                            <option value="TL">TL</option>
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                        </select>
                        <small id="conversionNote" class="text-info" style="display:none"></small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Not</label>
                        <textarea name="note" class="form-control" rows="2"
                            placeholder="İsteğe bağlı not..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-save me-1"></i>Girişi Kaydet
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title"><i class="fas fa-history me-2"></i>Son Girişler</h3>
                <a href="<?= BASE_URL?>/index.php?page=stock_in_list" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-list me-1"></i>Tüm Liste
                </a>
            </div>
            <div class="card-body p-0" id="recentList">
                <div class="text-center p-4 text-muted">Yükleniyor...</div>
            </div>
        </div>
    </div>
</div>

<script>
    // Döviz kurları
    var usdRate = <?=(float)get_setting('usd_rate',  '0')?>;
    var eurRate = <?=(float)get_setting('eur_rate',  '0')?>;

    // Supplier Select2
    $.get('<?= BASE_URL?>/api/suppliers.php', { action: 'active_list' }, function (r) {
        if (!r.success) return;
        $.each(r.data, function (i, s) {
            $('#supplierSelect').append('<option value="' + s.id + '">' + $('<span>').text(s.name).html() + '</option>');
        });
        $('#supplierSelect').select2({ theme: 'bootstrap-5', placeholder: '— Tedarikçi Seçin —', allowClear: true, width: '100%' });
    }, 'json');

    // Product Select2 (resimli)
    $('#productSelect').select2({
        theme: 'bootstrap-5',
        placeholder: '— Ürün arayın —',
        allowClear: true,
        width: '100%',
        ajax: {
            url: '<?= BASE_URL?>/api/products.php',
            data: function (p) { return { action: 'search_select2', q: p.term || '' }; },
            processResults: function (d) {
                return { results: d.results };
            },
            delay: 300
        },
        templateResult: function (item) {
            if (item.loading) return item.text;
            var noImg = '<?= BASE_URL?>/assets/no-image.png';
            var img = item.image ? '<?= BASE_URL?>/images/UrunResim/' + item.image : noImg;
            return $('<span><img src="' + img + '" class="select2-product-img" onerror="this.src=\'' + noImg + '\'"> ' + $('<span>').text(item.text).html() + '</span>');
        },
        templateSelection: function (item) { return item.text || item.id; },
        minimumInputLength: 0
    });

    // Ürün seçilince birimi güncelle
    $('#productSelect').on('select2:select', function (e) {
        var data = e.params.data;
        $('#unitLabel').text(data.unit || 'Adet');
        $('#quantity').focus();
    });

    // Para birimi değişince not göster
    $('#currency').on('change', function () {
        var cur = $(this).val();
        if ((cur === 'TL' || cur === 'USD') && eurRate > 0) {
            $('#conversionNote').text(cur + ' → EUR dönüşümü otomatik yapılacak (1 EUR = ' + (cur === 'TL' ? formatTurkish(eurRate.toFixed(2)) : formatTurkish(eurRate.toFixed(2))) + ' TL)').show();
        } else {
            $('#conversionNote').hide();
        }
    });

    // Form gönder
    $('#formStockIn').on('submit', function (e) {
        e.preventDefault();
        var data = $(this).serialize();
        $.post('<?= BASE_URL?>/api/stock_in.php', data += '&action=add', function (r) {
            if (r.success) {
                showSuccess('Stok girişi kaydedildi!');
                $('#formStockIn')[0].reset();
                $('#productSelect').val(null).trigger('change');
                $('#supplierSelect').val(null).trigger('change');
                $('#unitLabel').text('Adet');
                $('#conversionNote').hide();
                loadRecent();
            } else showError(r.message);
        }, 'json');
    });

    // Son giriş listesi
    function loadRecent() {
        $.get('<?= BASE_URL?>/api/stock_in.php', { action: 'recent' }, function (r) {
            if (!r.success) { $('#recentList').html('<div class="text-center p-3 text-muted">Veri alınamadı</div>'); return; }
            if (!r.data.length) { $('#recentList').html('<div class="text-center p-3 text-muted">Henüz giriş yok</div>'); return; }
            var html = '<table class="table table-sm table-striped mb-0"><thead><tr><th>Ürün</th><th>Depo</th><th>Adet</th><th>Fiyat</th><th>Tarih</th></tr></thead><tbody>';
            $.each(r.data, function (i, d) {
                html += '<tr><td>' + esc(d.product) + '</td><td>' + esc(d.warehouse) + '</td><td>' + d.quantity + ' ' + esc(d.unit) + '</td>';
                html += '<td>' + formatTurkish(parseFloat(d.unit_price).toFixed(2)) + ' ' + esc(d.currency) + '</td>';
                html += '<td><small>' + esc(d.created_at) + '</small></td></tr>';
            });
            html += '</tbody></table>';
            $('#recentList').html(html);
        }, 'json');
    }
    function esc(v) { return $('<span>').text(v || '').html(); }
    loadRecent();
</script>