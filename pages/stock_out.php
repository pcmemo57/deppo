<?php
/**
 * Depodan Çıkış
 */
requireRole(ROLE_ADMIN, ROLE_USER, ROLE_REQUESTER);
$warehouses = Database::fetchAll("SELECT id,name FROM tbl_dp_warehouses WHERE hidden=0 AND is_active=1 ORDER BY name");
?>
<div class="row">
    <div class="col-lg-5">
        <div class="card card-danger card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-sign-out-alt me-2"></i>Depodan Çıkış</h3>
            </div>
            <div class="card-body">
                <form id="formStockOut">

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
                        <label class="form-label">Talep Eden</label>
                        <select name="requester_id" id="requesterSelect" class="form-select" style="width:100%">
                            <option value="">— Talep Eden Seçin —</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Müşteri</label>
                        <select name="customer_id" id="customerSelect" class="form-select" style="width:100%">
                            <option value="">— Müşteri Seçin —</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ürün Seç (Enter ile listeye ekle)</label>
                        <select id="productAdd" class="form-select" style="width:100%">
                            <option value="">— Ürün arayın —</option>
                        </select>
                    </div>
                    <div class="input-group mb-3">
                        <span class="input-group-text">Adet</span>
                        <input type="number" id="qtyInput" class="form-control" min="0.001" step="any"
                            placeholder="Adet...">
                        <span class="input-group-text" id="unitAddLabel">Adet</span>
                        <button type="button" class="btn btn-outline-primary" id="btnAddLine">
                            <i class="fas fa-plus"></i> Ekle
                        </button>
                    </div>

                    <!-- Seçilen ürün listesi -->
                    <div class="table-responsive mb-3" id="lineContainer" style="display:none">
                        <table class="table table-sm table-bordered" id="lineTable">
                            <thead class="table-secondary">
                                <tr>
                                    <th>Ürün</th>
                                    <th>Adet</th>
                                    <th>Birim Fiyat</th>
                                    <th>Toplam</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="lineBody"></tbody>
                        </table>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Not</label>
                        <textarea name="note" class="form-control" rows="2"></textarea>
                    </div>

                    <button type="submit" class="btn btn-danger w-100" id="btnSubmitOut">
                        <i class="fas fa-save me-1"></i>Çıkışı Kaydet
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history me-2"></i>Son Çıkışlar</h3>
            </div>
            <div class="card-body p-0" id="recentOut">
                <div class="text-center p-4 text-muted">Yükleniyor...</div>
            </div>
        </div>
    </div>
</div>

<script>
    var lines = [];
    function esc(v) { return $('<span>').text(v || '').html(); }

    // Load requesters
    $.get('<?= BASE_URL?>/api/requesters.php', { action: 'list', per_page: 200, search: '' }, function (r) {
        if (!r.success) return;
        $.each(r.data, function (i, u) { $('#requesterSelect').append('<option value="' + u.id + '">' + esc(u.name + ' ' + u.surname + (u.title ? ' (' + u.title + ')' : '')) + '</option>'); });
        $('#requesterSelect').select2({ theme: 'bootstrap-5', placeholder: '— Talep Eden —', allowClear: true, width: '100%' });
    }, 'json');

    // Load customers
    $.get('<?= BASE_URL?>/api/customers.php', { action: 'active_list' }, function (r) {
        if (!r.success) return;
        $.each(r.data, function (i, u) { $('#customerSelect').append('<option value="' + u.id + '">' + esc(u.name) + '</option>'); });
        $('#customerSelect').select2({ theme: 'bootstrap-5', placeholder: '— Müşteri —', allowClear: true, width: '100%' });
    }, 'json');

    // Product Select2
    $('#productAdd').select2({
        theme: 'bootstrap-5', placeholder: '— Ürün arayın —', width: '100%',
        ajax: { url: '<?= BASE_URL?>/api/products.php', data: function (p) { return { action: 'search_select2', q: p.term || '' }; }, processResults: function (d) { return { results: d.results }; }, delay: 300 },
        templateResult: function (i) { if (i.loading) return i.text; var no = '<?= BASE_URL?>/assets/no-image.png', img = i.image ? '<?= BASE_URL?>/images/UrunResim/' + i.image : no; return $('<span><img src="' + img + '" class="select2-product-img" onerror="this.src=\'' + no + '\'"> ' + esc(i.text) + '</span>'); }
    });

    $('#productAdd').on('select2:select', function (e) {
        var data = e.params.data;
        $('#unitAddLabel').text(data.unit || 'Adet');
        $('#qtyInput').val('').focus();
    });

    // Enter ile ürün ekle
    $('#qtyInput').on('keypress', function (e) {
        if (e.which === 13) { e.preventDefault(); $('#btnAddLine').click(); }
    });

    $('#btnAddLine').on('click', function () {
        var sel = $('#productAdd').select2('data');
        if (!sel || !sel[0] || !sel[0].id) { showError('Lütfen bir ürün seçin.'); return; }
        var qty = parseFloat($('#qtyInput').val());
        if (!qty || qty <= 0) { showError('Geçerli bir adet girin.'); return; }

        var productId = sel[0].id;
        var productName = sel[0].text;
        var unit = $('#unitAddLabel').text();
        var warehouseId = $('#warehouseSelect').val();

        // Birim fiyatı son girişten al (depo bazlı)
        $.get('<?= BASE_URL?>/api/stock_out.php', { action: 'get_last_price', product_id: productId, warehouse_id: warehouseId }, function (r) {
            var unitPrice = r.success && r.data ? parseFloat(r.data.price_eur) : 0;
            var total = unitPrice * qty;
            lines.push({ product_id: productId, product_name: productName, quantity: qty, unit: unit, unit_price: unitPrice, total: total });
            renderLines();
            $('#productAdd').val(null).trigger('change');
            $('#qtyInput').val('');
            $('#unitAddLabel').text('Adet');
        }, 'json');
    });

    function renderLines() {
        if (!lines.length) { $('#lineContainer').hide(); return; }
        $('#lineContainer').show();
        var html = '';
        $.each(lines, function (i, l) {
            html += '<tr>';
            html += '<td>' + esc(l.product_name) + '</td>';
            html += '<td>' + l.quantity + ' ' + esc(l.unit) + '</td>';
            html += '<td>' + formatTurkish(l.unit_price.toFixed(4)) + ' EUR</td>';
            html += '<td><strong>' + formatTurkish(l.total.toFixed(4)) + ' EUR</strong></td>';
            html += '<td><button class="btn btn-xs btn-danger" onclick="removeLine(' + i + ')"><i class="fas fa-times"></i></button></td>';
            html += '</tr>';
        });
        $('#lineBody').html(html);
    }

    function removeLine(i) { lines.splice(i, 1); renderLines(); }

    $('#formStockOut').on('submit', function (e) {
        e.preventDefault();
        if (!lines.length) { showError('En az 1 ürün ekleyin.'); return; }
        var note = $('[name="note"]').val();
        var data = {
            action: 'add',
            warehouse_id: $('#warehouseSelect').val(),
            requester_id: $('#requesterSelect').val(),
            customer_id: $('#customerSelect').val(),
            note: note,
            lines: JSON.stringify(lines)
        };
        $.post('<?= BASE_URL?>/api/stock_out.php', data, function (r) {
            if (r.success) {
                showSuccess('Çıkış kaydedildi!');
                lines = []; renderLines();
                $('#formStockOut')[0].reset();
                $('#productAdd').val(null).trigger('change');
                $('#requesterSelect').val(null).trigger('change');
                $('#customerSelect').val(null).trigger('change');
                loadRecentOut();
            } else showError(r.message);
        }, 'json');
    });

    function loadRecentOut() {
        $.get('<?= BASE_URL?>/api/stock_out.php', { action: 'recent' }, function (r) {
            if (!r.success) { $('#recentOut').html('<div class="p-3 text-muted">Veri alınamadı</div>'); return; }
            if (!r.data.length) { $('#recentOut').html('<div class="p-3 text-muted">Henüz çıkış yok</div>'); return; }
            var html = '<table class="table table-sm table-striped mb-0"><thead><tr><th>Ürün</th><th>Depo</th><th>Adet</th><th>Toplam</th><th>Tarih</th></tr></thead><tbody>';
            $.each(r.data, function (i, d) {
                html += '<tr><td>' + esc(d.product) + '</td><td>' + esc(d.warehouse) + '</td><td>' + d.quantity + '</td>';
                html += '<td>' + formatTurkish(parseFloat(d.total_price || 0).toFixed(2)) + ' EUR</td>';
                html += '<td><small>' + esc(d.created_at) + '</small></td></tr>';
            });
            html += '</tbody></table>';
            $('#recentOut').html(html);
        }, 'json');
    }
    loadRecentOut();
</script>