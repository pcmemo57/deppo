<?php
/**
 * Depolar Arası Transfer
 */
requireRole(ROLE_ADMIN, ROLE_USER);
$warehouses = Database::fetchAll("SELECT id,name FROM tbl_dp_warehouses WHERE hidden=0 AND is_active=1 ORDER BY name");
?>

<div class="row">
    <div class="col-lg-5">
        <div class="card card-warning card-outline">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title"><i class="fas fa-exchange-alt me-2"></i>Depolar Arası Transfer</h3>
                <a href="<?= BASE_URL?>/index.php?page=transfer_history" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-history me-1"></i>Transfer Geçmişi
                </a>
            </div>
            <div class="card-body">
                <form id="formTransfer">

                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold text-success">Kaynak Depo <span
                                    class="text-danger">*</span></label>
                            <select id="sourceWarehouse" class="form-select" required>
                                <option value="">— Seç —</option>
                                <?php foreach ($warehouses as $w): ?>
                                <option value="<?= e($w['id'])?>">
                                    <?= e($w['name'])?>
                                </option>
                                <?php
endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold text-danger">Hedef Depo <span
                                    class="text-danger">*</span></label>
                            <select id="targetWarehouse" class="form-select" required>
                                <option value="">— Seç —</option>
                                <?php foreach ($warehouses as $w): ?>
                                <option value="<?= e($w['id'])?>" id="tw_<?= e($w['id'])?>">
                                    <?= e($w['name'])?>
                                </option>
                                <?php
endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ürün Seç (Enter ile listeye ekle)</label>
                        <select id="transferProduct" class="form-select" style="width:100%">
                            <option value="">— Ürün arayın —</option>
                        </select>
                    </div>
                    <div class="input-group mb-3">
                        <span class="input-group-text">Adet</span>
                        <input type="number" id="transferQty" class="form-control" min="0.001" step="any"
                            placeholder="Adet...">
                        <span class="input-group-text" id="transferUnitLabel">Adet</span>
                        <button type="button" class="btn btn-outline-primary" id="btnAddTransferLine">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>

                    <!-- Transfer listesi -->
                    <div id="transferLineContainer" style="display:none" class="mb-3">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-secondary">
                                    <tr>
                                        <th>Ürün</th>
                                        <th>Adet</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="transferLineBody"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Not</label>
                        <textarea name="note" id="transferNote" class="form-control" rows="2"></textarea>
                    </div>

                    <button type="submit" class="btn btn-warning w-100 text-dark fw-bold">
                        <i class="fas fa-truck-moving me-1"></i>Transfer Et
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list me-2"></i>Son Transferler</h3>
            </div>
            <div class="card-body p-0" id="recentTransfers">
                <div class="text-center p-4 text-muted">Yükleniyor...</div>
            </div>
        </div>
    </div>
</div>

<script>
    var transferLines = [];
    function esc(v) { return $('<span>').text(v || '').html(); }

    // Ürün Select2 (resimli)
    $('#transferProduct').select2({
        theme: 'bootstrap-5', placeholder: '— Ürün arayın —', width: '100%',
        ajax: { url: '<?= BASE_URL?>/api/products.php', data: function (p) { return { action: 'search_select2', q: p.term || '' }; }, processResults: function (d) { return { results: d.results }; }, delay: 300 },
        templateResult: function (i) { if (i.loading) return i.text; var no = '<?= BASE_URL?>/assets/no-image.png', img = i.image ? '<?= BASE_URL?>/images/UrunResim/' + i.image : no; return $('<span><img src="' + img + '" class="select2-product-img" onerror="this.src=\'' + no + '\'"> ' + esc(i.text) + '</span>'); }
    });

    $('#transferProduct').on('select2:select', function (e) {
        $('#transferUnitLabel').text(e.params.data.unit || 'Adet');
        $('#transferQty').val('').focus();
    });

    // Enter ile ekle
    $('#transferQty').on('keypress', function (e) { if (e.which === 13) { e.preventDefault(); $('#btnAddTransferLine').click(); } });

    // Kaynak depo değişince hedef depoda disable yap
    $('#sourceWarehouse').on('change', function () {
        var srcId = $(this).val();
        $('#targetWarehouse option').prop('disabled', false);
        if (srcId) { $('#targetWarehouse option[value="' + srcId + '"]').prop('disabled', true); }
        // Hedefte aynısı seçiliyse sıfırla
        if ($('#targetWarehouse').val() == srcId) { $('#targetWarehouse').val(''); }
    });

    $('#btnAddTransferLine').on('click', function () {
        var sel = $('#transferProduct').select2('data');
        if (!sel || !sel[0] || !sel[0].id) { showError('Lütfen ürün seçin.'); return; }
        var qty = parseFloat($('#transferQty').val());
        if (!qty || qty <= 0) { showError('Geçerli adet girin.'); return; }
        var pid = sel[0].id, pname = sel[0].text, unit = sel[0].unit || 'Adet';
        // Aynı ürün varsa adet ekle
        var found = false;
        $.each(transferLines, function (i, l) { if (l.product_id == pid) { l.quantity += qty; found = true; return false; } });
        if (!found) transferLines.push({ product_id: pid, product_name: pname, quantity: qty, unit: unit });
        renderTransferLines();
        $('#transferProduct').val(null).trigger('change');
        $('#transferQty').val(''); $('#transferUnitLabel').text('Adet');
    });

    function renderTransferLines() {
        if (!transferLines.length) { $('#transferLineContainer').hide(); return; }
        $('#transferLineContainer').show();
        var html = '';
        $.each(transferLines, function (i, l) {
            html += '<tr><td>' + esc(l.product_name) + '</td><td>' + l.quantity + ' ' + esc(l.unit) + '</td>';
            html += '<td><button class="btn btn-xs btn-danger" onclick="removeTransferLine(' + i + ')"><i class="fas fa-times"></i></button></td></tr>';
        });
        $('#transferLineBody').html(html);
    }
    function removeTransferLine(i) { transferLines.splice(i, 1); renderTransferLines(); }

    $('#formTransfer').on('submit', function (e) {
        e.preventDefault();
        if (!transferLines.length) { showError('En az 1 ürün ekleyin.'); return; }
        var srcId = $('#sourceWarehouse').val(), tgtId = $('#targetWarehouse').val();
        if (!srcId || !tgtId) { showError('Kaynak ve hedef depo seçin.'); return; }
        if (srcId === tgtId) { showError('Kaynak ve hedef depo aynı olamaz.'); return; }

        $.post('<?= BASE_URL?>/api/transfer.php', {
            action: 'add',
            source_warehouse_id: srcId,
            target_warehouse_id: tgtId,
            note: $('#transferNote').val(),
            lines: JSON.stringify(transferLines)
        }, function (r) {
            if (r.success) {
                showSuccess('Transfer tamamlandı!');
                transferLines = []; renderTransferLines();
                $('#formTransfer')[0].reset();
                $('#transferProduct').val(null).trigger('change');
                loadRecentTr();
            } else showError(r.message);
        }, 'json');
    });

    function loadRecentTr() {
        $.get('<?= BASE_URL?>/api/transfer.php', { action: 'recent' }, function (r) {
            if (!r.success || !r.data.length) { $('#recentTransfers').html('<div class="p-3 text-muted">Henüz transfer yok</div>'); return; }
            var html = '<table class="table table-sm table-striped mb-0"><thead><tr><th>Kaynak</th><th>Hedef</th><th>Ürün Sayısı</th><th>Tarih</th></tr></thead><tbody>';
            $.each(r.data, function (i, d) { html += '<tr><td>' + esc(d.source) + '</td><td>' + esc(d.target) + '</td><td>' + d.item_count + ' kalem</td><td><small>' + esc(d.created_at) + '</small></td></tr>'; });
            html += '</tbody></table>';
            $('#recentTransfers').html(html);
        }, 'json');
    }
    loadRecentTr();
</script>