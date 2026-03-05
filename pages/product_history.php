<?php
/**
 * Ürün Hareket Geçmişi — Detaylı görünüm
 */
requireRole(ROLE_ADMIN, ROLE_USER);

$productId = (int) ($_GET['product_id'] ?? 0);
$warehouseId = (int) ($_GET['warehouse_id'] ?? 0);

if (!$productId) {
    echo '<div class="alert alert-danger">Geçersiz Ürün ID.</div>';
    return;
}

// Ürün bilgilerini al
$product = Database::fetchOne("SELECT * FROM tbl_dp_products WHERE id = ?", [$productId]);
if (!$product) {
    echo '<div class="alert alert-danger">Ürün bulunamadı.</div>';
    return;
}

// Depo bilgisini al (filtre varsa)
$warehouse = null;
if ($warehouseId) {
    $warehouse = Database::fetchOne("SELECT name FROM tbl_dp_warehouses WHERE id = ?", [$warehouseId]);
}

// Stok bilgilerini al (Depo bazlı ve toplam)
$stockQuery = "
    SELECT w.name AS warehouse_name, 
           (SELECT COALESCE(SUM(quantity), 0) FROM tbl_dp_stock_in WHERE product_id = ? AND warehouse_id = w.id) -
           (SELECT COALESCE(SUM(quantity), 0) FROM tbl_dp_stock_out WHERE product_id = ? AND warehouse_id = w.id) AS balance
    FROM tbl_dp_warehouses w
    WHERE w.hidden = 0 AND w.is_active = 1
";
$stocks = Database::fetchAll($stockQuery, [$productId, $productId]);
$totalBalance = array_sum(array_column($stocks, 'balance'));

?>

<div class="row">
    <!-- Ürün Kartı -->
    <div class="col-md-4">
        <div class="card card-primary card-outline shadow-sm">
            <div class="card-body box-profile">
                <div class="text-center mb-3">
                    <?php if ($product['image']): ?>
                        <img class="img-fluid rounded shadow-sm"
                            src="<?= BASE_URL ?>/images/UrunResim/<?= e($product['image']) ?>"
                            alt="<?= e($product['name']) ?>" style="max-height: 200px; object-fit: contain;">
                    <?php else: ?>
                        <div class="bg-light d-flex align-items-center justify-content-center rounded"
                            style="height: 150px;">
                            <i class="fas fa-box text-muted fa-4x"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <h3 class="profile-username text-center font-weight-bold">
                    <?= e($product['name']) ?>
                </h3>
                <p class="text-muted text-center">
                    <?= e($product['code'] ?: 'Kodsuz Ürün') ?>
                </p>

                <div class="mt-4">
                    <h6 class="text-uppercase text-muted small font-weight-bold mb-3 border-bottom pb-2">
                        <i class="fas fa-warehouse mr-1"></i> Depo Stokları
                    </h6>
                    <div class="table-responsive border rounded bg-white mt-2">
                        <table class="table table-sm table-striped mb-0 w-100" style="font-size: 0.85rem;">
                            <tbody>
                                <?php foreach ($stocks as $s): ?>
                                    <tr>
                                        <td class="text-muted border-0 py-2 ps-3"><?= e($s['warehouse_name']) ?></td>
                                        <td class="text-right border-0 py-2 pe-3 font-weight-bold <?= $s['balance'] > 0 ? 'text-success' : 'text-danger' ?>"
                                            style="width: 120px; white-space: nowrap;">
                                            <?= formatQty($s['balance']) ?>
                                            <small
                                                class="text-muted ml-1 font-weight-normal"><?= e($product['unit']) ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <a href="javascript:void(0)" onclick="editProduct(<?= $productId ?>)"
                    class="btn btn-outline-primary btn-block mt-4"><b>Ürünü Düzenle</b></a>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="crudModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="modalTitle">Ürün Düzenle</h5>
                    <button type="button" class="btn btn-link text-white p-0 border-0" data-bs-dismiss="modal"><i
                            class="fas fa-times"></i></button>
                </div>
                <div class="modal-body">
                    <form id="crudForm" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?= $productId ?>">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <div class="mb-2">
                                    <img id="previewImg"
                                        src="<?= $product['image'] ? BASE_URL . '/images/UrunResim/' . $product['image'] : BASE_URL . '/assets/no-image.png' ?>"
                                        alt="Ürün Resmi"
                                        style="width:150px;height:150px;object-fit:cover;border-radius:10px;border:2px solid #dee2e6;">
                                </div>
                                <label class="form-label">Ürün Resmi</label>
                                <input type="file" name="image" id="imageInput" class="form-control form-control-sm"
                                    accept="image/*">
                            </div>
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-3"><label class="form-label">Ürün Adı <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="name" class="form-control"
                                                value="<?= e($product['name']) ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3"><label class="form-label">Birim</label>
                                            <select name="unit" class="form-select">
                                                <option value="Adet" <?= $product['unit'] == 'Adet' ? 'selected' : '' ?>>
                                                    Adet</option>
                                                <option value="Kg" <?= $product['unit'] == 'Kg' ? 'selected' : '' ?>>Kg
                                                </option>
                                                <option value="Litre" <?= $product['unit'] == 'Litre' ? 'selected' : '' ?>>
                                                    Litre</option>
                                                <option value="Metre" <?= $product['unit'] == 'Metre' ? 'selected' : '' ?>>
                                                    Metre</option>
                                                <option value="Kutu" <?= $product['unit'] == 'Kutu' ? 'selected' : '' ?>>
                                                    Kutu</option>
                                                <option value="Paket" <?= $product['unit'] == 'Paket' ? 'selected' : '' ?>>
                                                    Paket</option>
                                                <option value="Ton" <?= $product['unit'] == 'Ton' ? 'selected' : '' ?>>Ton
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3"><label class="form-label">Ürün Kodu</label>
                                            <input type="text" name="code" class="form-control"
                                                value="<?= e($product['code']) ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Alarm Seviyesi</label>
                                            <input type="number" name="stock_alarm" class="form-control"
                                                value="<?= (int) $product['stock_alarm'] ?>" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3"><label class="form-label">Açıklama</label>
                                            <textarea name="description" class="form-control"
                                                rows="3"><?= e($product['description']) ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                    <button type="button" class="btn btn-success" id="btnSaveProduct">Kaydet</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hareket Geçmişi -->
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-history mr-2 text-primary"></i>
                    Hareket Geçmişi
                </h3>
                <div class="card-tools d-flex align-items-center">
                    <div class="input-group input-group-sm mr-3" style="width: 250px;">
                        <input type="text" id="historySearch" class="form-control"
                            placeholder="Müşteri, Not veya Kurum ara...">
                        <div class="input-group-append">
                            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                        </div>
                    </div>
                    <button class="btn btn-tool" onclick="location.reload()"><i class="fas fa-sync-alt"></i></button>
                    <a href="<?= BASE_URL ?>/index.php?page=stock_status" class="btn btn-sm btn-outline-secondary ml-2">
                        <i class="fas fa-arrow-left mr-1"></i> Geri Dön
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0 align-middle" id="historyPageTable">
                        <thead class="bg-light">
                            <tr>
                                <th style="width: 100px">İşlem</th>
                                <th class="num-align" style="width: 120px">Miktar</th>
                                <th>Depo</th>
                                <th>Kişi / Kurum / Detay</th>
                                <th class="num-align">Birim (<?= getCurrencySymbol() ?>)</th>
                                <th class="num-align">Toplam</th>
                                <th class="num-align">Tarih</th>
                                <th style="width: 80px" class="text-center">Detay</th>
                            </tr>
                        </thead>
                        <tbody id="historyPageBody">
                            <tr>
                                <td colspan="8" class="text-center p-4">
                                    <div class="spinner-border spinner-border-sm text-primary"></div> Yükleniyor...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(function () {
        const productId = <?= $productId ?>;
        const warehouseId = <?= $warehouseId ?>;
        const apiUrl = '<?= BASE_URL ?>/api/stock_status.php';
        let searchTimer;


        function loadHistory(search = '') {
            $.get(apiUrl, {
                action: 'get_history',
                product_id: productId,
                warehouse_id: warehouseId,
                search: search
            }, function (r) {
                if (!r.success) {
                    $('#historyPageBody').html('<tr><td colspan="8" class="text-center text-danger p-4">' + r.message + '</td></tr>');
                    return;
                }

                if (r.data.length === 0) {
                    $('#historyPageBody').html('<tr><td colspan="8" class="text-center text-muted p-4">Bu ürün için henüz hareket kaydı bulunmuyor.</td></tr>');
                    return;
                }

                let html = '';
                $.each(r.data, function (i, d) {
                    const typeClass = d.type === 'Giriş' ? 'badge-success' : 'badge-danger';
                    const contactInfo = d.contact || d.creator || '—';

                    let detailCall = '';
                    if (d.type === 'Giriş') {
                        detailCall = `showInDetail(${d.record_id})`;
                    } else {
                        detailCall = `showOutDetail('${d.batch_id}')`;
                    }

                    html += `<tr>
                        <td><span class="badge ${typeClass} px-2 py-1">${d.type}</span></td>
                        <td class="num-align font-weight-bold text-dark">${d.quantity_fmt}</td>
                        <td><span class="text-muted small">${d.warehouse_name || '—'}</span></td>
                        <td>
                            <div class="font-weight-600">${contactInfo}</div>
                        </td>
                        <td class="num-align">${formatTurkish(d.price_base, 2)}</td>
                        <td class="num-align"><strong>${formatTurkish(d.total_base, 2)}</strong></td>
                        <td class="num-align"><small class="text-muted">${d.created_at_fmt}</small></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-xs btn-outline-primary" onclick="${detailCall}" title="Kayda Git">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>`;
                });
                $('#historyPageBody').html(html);
            }, 'json').fail(function () {
                $('#historyPageBody').html('<tr><td colspan="8" class="text-center text-danger p-4">Bağlantı hatası oluştu.</td></tr>');
            });
        }

        loadHistory();

        $('#historySearch').on('input', function () {
            clearTimeout(searchTimer);
            const val = $(this).val();
            searchTimer = setTimeout(function () {
                loadHistory(val);
            }, 400);
        });
    });

    function esc(v) { return $('<span>').text(v || '').html(); }

    function showInDetail(id) {
        $.get('<?= BASE_URL ?>/api/stock_in.php', { action: 'get', id: id }, function (r) {
            if (!r.success) { Swal.fire('Hata', r.message, 'error'); return; }
            const d = r.data;
            $('#viDepo').val(d.warehouse_name);
            $('#viUrun').val(d.product_name);
            $('#viMiktar').val(formatQty(d.quantity) + ' ' + d.unit);
            $('#viTedarikci').val(d.supplier_name || '—');
            $('#viFiyat').val(formatTurkish((parseFloat(d.unit_price) || 0).toFixed(2)) + ' ' + d.currency);
            $('#viEur').val(formatTurkish((parseFloat(d.price_eur) || 0).toFixed(2)) + ' ' + '<?= get_setting('base_currency', 'EUR') ?>');
            $('#viTarih').val(d.created_at);
            $('#viYapan').val(d.created_by_name);
            $('#viNot').val(d.note || '—');
            $('#viewInModal').modal('show');
        }, 'json');
    }

    function showOutDetail(batchId) {
        $.get('<?= BASE_URL ?>/api/stock_out.php', { action: 'get_batch', batch_id: batchId }, function (r) {
            if (!r.success) { Swal.fire('Hata', r.message, 'error'); return; }
            let html = '';
            let total = 0;
            $.each(r.data, function (i, d) {
                total += parseFloat(d.total_price);
                html += `<tr>
                    <td>${esc(d.product_name)}</td>
                    <td class="num-align">${formatQty(d.quantity)} ${esc(d.unit)}</td>
                    <td class="num-align">${formatTurkish(d.unit_price, 4)}</td>
                    <td class="num-align"><strong>${formatTurkish(d.total_price, 2)}</strong></td>
                </tr>`;
                if (i === 0) {
                    $('#voNot').text(d.note || '—');
                    $('#voTarih').text(d.created_at);
                    $('#voYapan').text(d.created_by_name);
                    $('#voMusteri').text(d.customer_name || '—');
                    $('#voDepo').text(d.warehouse_name || '—');
                    let requester = (d.requester_name || '') + ' ' + (d.requester_surname || '');
                    $('#voTalepEden').text(requester.trim() || '—');
                }
            });
            $('#voTableBody').html(html);
            $('#voTotalSum').text(formatTurkish(total, 2) + ' ' + '<?= get_setting('base_currency', 'EUR') ?>');
            $('#viewOutModal').modal('show');
        }, 'json');
    }
    function editProduct(id) {
        $('#crudModal').modal('show');
    }

    $(function () {
        $('#imageInput').on('change', function () {
            var file = this.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function (e) { $('#previewImg').attr('src', e.target.result); };
                reader.readAsDataURL(file);
            }
        });

        $('#btnSaveProduct').on('click', function () {
            var formData = new FormData($('#crudForm')[0]);
            var $btn = $(this);
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Kaydediliyor...');

            $.ajax({
                url: '<?= BASE_URL ?>/api/products.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (r) {
                    if (r.success) {
                        showSuccess(r.message);
                        $('#crudModal').modal('hide');
                        setTimeout(function () { location.reload(); }, 1000);
                    } else {
                        showError(r.message);
                        $btn.prop('disabled', false).text('Kaydet');
                    }
                },
                error: function () {
                    showError('Bağlantı hatası.');
                    $btn.prop('disabled', false).text('Kaydet');
                },
                dataType: 'json'
            });
        });
    });
</script>

<!-- MODAL: Giriş Detayı -->
<div class="modal fade" id="viewInModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-eye"></i> Stok Giriş Detayı</h5>
                <button type="button" class="btn btn-link text-white p-0 border-0" data-bs-dismiss="modal"><i
                        class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <div class="modal-section-label">
                    <i class="fas fa-info-circle"></i> Genel Bilgiler
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Depo</label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-warehouse field-icon"></i>
                            <input type="text" id="viDepo" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Ürün</label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-box field-icon"></i>
                            <input type="text" id="viUrun" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Miktar</label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-boxes field-icon"></i>
                            <input type="text" id="viMiktar" class="form-control font-weight-bold" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tedarikçi</label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-truck field-icon"></i>
                            <input type="text" id="viTedarikci" class="form-control" readonly>
                        </div>
                    </div>
                </div>

                <div class="modal-section-label">
                    <i class="fas fa-tag"></i> Fiyatlandırma & Sistem
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Birim Fiyat</label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-lira-sign field-icon"></i>
                            <input type="text" id="viFiyat" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?= e(get_setting('base_currency', 'EUR')) ?> Karşılığı</label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-coins field-icon"></i>
                            <input type="text" id="viEur" class="form-control text-primary font-weight-bold" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tarih</label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-calendar-alt field-icon"></i>
                            <input type="text" id="viTarih" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">İşlemi Yapan</label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-user field-icon"></i>
                            <input type="text" id="viYapan" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Not</label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-comment-dots field-icon"></i>
                            <textarea id="viNot" class="form-control" rows="2" readonly></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Çıkış Detayı -->
<div class="modal fade" id="viewOutModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-eye"></i> Stok Çıkış Detayı</h5>
                <button type="button" class="btn btn-link text-white p-0 border-0" data-bs-dismiss="modal"><i
                        class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <div
                    class="mb-4 p-3 bg-white border rounded shadow-sm d-flex justify-content-between align-items-center">
                    <div class="row w-100 g-2">
                        <div class="col-md-3">
                            <div class="small text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Müşteri
                            </div>
                            <div id="voMusteri" class="fw-bold text-dark"></div>
                        </div>
                        <div class="col-md-3">
                            <div class="small text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Depo</div>
                            <div id="voDepo" class="text-dark"></div>
                        </div>
                        <div class="col-md-3">
                            <div class="small text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Talep Eden
                            </div>
                            <div id="voTalepEden" class="text-dark"></div>
                        </div>
                        <div class="col-md-3 text-end">
                            <div class="small text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Toplam
                                Tutar</div>
                            <div id="voTotalSum" class="h5 mb-0 font-weight-bold text-primary"></div>
                        </div>
                        <div class="col-12 mt-2 pt-2 border-top d-flex gap-5 small text-muted">
                            <span class="d-flex align-items-center"><i class="fas fa-calendar-alt text-warning"></i>
                                Tarih: <span id="voTarih" class="ms-1 text-dark fw-bold"></span></span>
                            <span class="d-flex align-items-center"><i class="fas fa-user text-primary"></i> İşlemi
                                Yapan: <span id="voYapan" class="ms-1 text-dark fw-bold"></span></span>
                        </div>
                    </div>
                </div>

                <div class="modal-section-label">
                    <i class="fas fa-list"></i> Ürün Listesi
                </div>
                <div class="table-responsive border rounded bg-white mb-4">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="bg-light small">
                            <tr>
                                <th class="ps-3">Ürün</th>
                                <th class="text-end">Miktar</th>
                                <th class="text-end">Birim (<?= getCurrencySymbol() ?>)</th>
                                <th class="text-end pe-3">Toplam</th>
                            </tr>
                        </thead>
                        <tbody id="voTableBody" class="small"></tbody>
                    </table>
                </div>

                <div class="modal-section-label">
                    <i class="fas fa-comment-dots"></i> Not
                </div>
                <div id="voNot" class="p-3 bg-white border rounded small text-muted font-italic"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<style>
    .profile-username {
        font-size: 1.5rem;
        margin-top: 10px;
    }

    .font-weight-600 {
        font-weight: 600;
    }

    .table th {
        border-top: none !important;
        color: #555;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
    }

    .table td {
        vertical-align: middle;
    }

    #historyPageTable .num-align {
        text-align: right;
        padding-right: 20px;
    }

    /* ─── MODAL PREMIUM TASARIM (stock_in_list uyumlu) ─── */
    .modal-content {
        border: none;
        border-radius: 16px !important;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.18);
    }

    .modal-header {
        padding: 20px 28px;
        border-bottom: none;
    }

    .modal-header.bg-success {
        background: linear-gradient(135deg, #0e9f6e 0%, #057a55 100%) !important;
    }

    .modal-header.bg-danger {
        background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%) !important;
    }

    .modal-body {
        padding: 28px 32px;
        background: #f8fafd;
    }

    .modal-footer {
        padding: 16px 32px 20px;
        background: #f8fafd;
        border-top: 1px solid #e4e9f0;
    }

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

    .input-icon-wrap .form-control {
        padding-left: 32px;
        border: 1.5px solid #d1d9e6;
        border-radius: 8px;
        background-color: #fff !important;
    }

    .form-label {
        font-size: 0.8rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 6px;
        display: block;
    }

    .btn-modal-cancel {
        background: transparent;
        border: 1.5px solid #c9d3e0;
        color: #4a5568;
        border-radius: 8px;
        padding: 8px 20px;
        font-size: 0.87rem;
        transition: all 0.2s;
    }

    .btn-modal-cancel:hover {
        background: #f0f4f9;
        color: #1f2937;
    }

    /* İkon ve Metin Arası Boşluk Standartı */
    .modal-content i:not(.btn-close i, .field-icon) {
        margin-right: 10px;
    }

    .modal-section-label i {
        margin-right: 12px;
    }
</style>