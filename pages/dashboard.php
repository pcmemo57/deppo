<?php
/**
 * Dashboard — Kontrol Paneli
 */
// Özet istatistikleri
$warehouseCount = Database::fetchOne('SELECT COUNT(*) AS c FROM tbl_dp_warehouses WHERE hidden=0')['c'] ?? 0;
$productCount = Database::fetchOne('SELECT COUNT(*) AS c FROM tbl_dp_products WHERE hidden=0')['c'] ?? 0;
$customerCount = Database::fetchOne('SELECT COUNT(*) AS c FROM tbl_dp_customers WHERE hidden=0')['c'] ?? 0;
$supplierCount = Database::fetchOne('SELECT COUNT(*) AS c FROM tbl_dp_suppliers WHERE hidden=0')['c'] ?? 0;
$stockInToday = Database::fetchOne('SELECT COUNT(*) AS c FROM tbl_dp_stock_in WHERE DATE(created_at)=CURDATE()')['c'] ?? 0;
$stockOutToday = Database::fetchOne('SELECT COUNT(*) AS c FROM tbl_dp_stock_out WHERE DATE(created_at)=CURDATE()')['c'] ?? 0;
$transferCount = Database::fetchOne('SELECT COUNT(*) AS c FROM tbl_dp_transfers WHERE DATE(created_at)=CURDATE()')['c'] ?? 0;

// Son 5 stok girişi
$recentIn = Database::fetchAll(
    'SELECT si.created_at, p.name AS product, w.name AS warehouse, si.quantity, p.unit
     FROM tbl_dp_stock_in si
     JOIN tbl_dp_products p ON p.id=si.product_id
     JOIN tbl_dp_warehouses w ON w.id=si.warehouse_id
     ORDER BY si.created_at DESC LIMIT 5'
);

// Son 5 stok çıkışı
$recentOut = Database::fetchAll(
    'SELECT so.created_at, p.name AS product, w.name AS warehouse, so.quantity, p.unit
     FROM tbl_dp_stock_out so
     JOIN tbl_dp_products p ON p.id=so.product_id
     JOIN tbl_dp_warehouses w ON w.id=so.warehouse_id
     ORDER BY so.created_at DESC LIMIT 5'
);

// Emanetteki ürünler (iade edilmeyenler)
$entrustedList = Database::fetchAll(
    "SELECT e.*, p.name AS product, p.unit, w.name AS warehouse, r.name AS req_name, r.surname AS req_surname
     FROM tbl_dp_entrusted e
     JOIN tbl_dp_products p ON p.id=e.product_id
     JOIN tbl_dp_warehouses w ON w.id=e.warehouse_id
     LEFT JOIN tbl_dp_requesters r ON r.id=e.requester_id
     WHERE e.remaining_quantity > 0
      ORDER BY e.created_at DESC"
);

// Stok Alarmı (Kritik Seviye Altındakiler)
$lowStockProducts = Database::fetchAll("
    SELECT p.id, p.name, p.unit, p.stock_alarm, 
           (SELECT COALESCE(SUM(si.quantity),0) FROM tbl_dp_stock_in si WHERE si.product_id = p.id) - 
           (SELECT COALESCE(SUM(so.quantity),0) FROM tbl_dp_stock_out so WHERE so.product_id = p.id) as current_stock
    FROM tbl_dp_products p
    WHERE p.hidden = 0 AND p.is_active = 1 AND p.stock_alarm > 0
    HAVING current_stock < p.stock_alarm
    ORDER BY current_stock ASC
");
?>

<!-- İstatistik Kartları -->
<?php if (!empty($lowStockProducts)): ?>
    <div class="row">
        <div class="col-12">
            <div class="alert alert-danger shadow-sm border-left-danger">
                <h5><i class="icon fas fa-exclamation-triangle"></i> Kritik Stok Uyarıları!</h5>
                <ul class="mb-0">
                    <?php foreach ($lowStockProducts as $lp): ?>
                        <li>
                            <strong><?= e($lp['name']) ?></strong>: Mevcut Stok
                            <span class="badge bg-white text-danger px-2 mx-1"><?= e(formatQty($lp['current_stock'])) ?></span>
                            (Alarm Seviyesi: <?= e(formatQty($lp['stock_alarm'])) ?>         <?= e($lp['unit']) ?>)
                            <a href="<?= BASE_URL ?>/index.php?page=stock_status&search=<?= urlencode($lp['name']) ?>"
                                class="ms-2 text-white text-decoration-underline small">Detaylı Gör</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>
                    <?= e(formatQty($warehouseCount)) ?>
                </h3>
                <p>Aktif Depo</p>
            </div>
            <div class="icon"><i class="fas fa-warehouse"></i></div>
            <?php if (hasRole(ROLE_ADMIN, ROLE_USER)): ?>
                <a href="<?= BASE_URL ?>/index.php?page=warehouses" class="small-box-footer">Görüntüle <i
                        class="fas fa-arrow-circle-right"></i></a>
                <?php
            endif; ?>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>
                    <?= e(formatQty($productCount)) ?>
                </h3>
                <p>Tanımlı Ürün</p>
            </div>
            <div class="icon"><i class="fas fa-boxes"></i></div>
            <?php if (hasRole(ROLE_ADMIN, ROLE_USER)): ?>
                <a href="<?= BASE_URL ?>/index.php?page=products" class="small-box-footer">Görüntüle <i
                        class="fas fa-arrow-circle-right"></i></a>
                <?php
            endif; ?>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>
                    <?= e(formatQty($customerCount)) ?>
                </h3>
                <p>Müşteri</p>
            </div>
            <div class="icon"><i class="fas fa-users"></i></div>
            <?php if (hasRole(ROLE_ADMIN, ROLE_USER)): ?>
                <a href="<?= BASE_URL ?>/index.php?page=customers" class="small-box-footer">Görüntüle <i
                        class="fas fa-arrow-circle-right"></i></a>
                <?php
            endif; ?>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>
                    <?= e(formatQty($supplierCount)) ?>
                </h3>
                <p>Tedarikçi</p>
            </div>
            <div class="icon"><i class="fas fa-truck"></i></div>
            <?php if (hasRole(ROLE_ADMIN, ROLE_USER)): ?>
                <a href="<?= BASE_URL ?>/index.php?page=suppliers" class="small-box-footer">Görüntüle <i
                        class="fas fa-arrow-circle-right"></i></a>
                <?php
            endif; ?>
        </div>
    </div>
</div>

<!-- Bugünkü Hareketler -->
<div class="row">
    <div class="col-md-4">
        <div class="info-box">
            <span class="info-box-icon bg-success elevation-1"><i class="fas fa-arrow-down"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Bugün Giriş</span>
                <span class="info-box-number">
                    <?= e(formatQty($stockInToday)) ?> kayıt
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box">
            <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-arrow-up"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Bugün Çıkış</span>
                <span class="info-box-number">
                    <?= e(formatQty($stockOutToday)) ?> kayıt
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box">
            <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-exchange-alt"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Bugün Transfer</span>
                <span class="info-box-number">
                    <?= e(formatQty($transferCount)) ?> işlem
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Emanetteki Ürünler (Tam Genişlik) -->
<div class="row">
    <div class="col-12">
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title text-bold"><i class="fas fa-hand-holding me-2"></i>Emanetteki Ürünler
                    (Bekleyenler)</h3>
                <div class="card-tools">
                    <a href="<?= BASE_URL ?>/index.php?page=entrusted" class="btn btn-sm btn-primary shadow-sm">
                        Tümünü Yönet <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
            <div class="card-body p-0 table-responsive text-nowrap">
                <table class="table table-sm table-hover table-striped mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-3">Ürün</th>
                            <th>Emanet Alan</th>
                            <th>Depo</th>
                            <th class="num-align">Kalan Miktar</th>
                            <th class="num-align">İade Tarihi</th>
                            <th class="num-align">Kayıt Tarihi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entrustedList as $e): ?>
                            <tr>
                                <td class="ps-3"><strong><?= e($e['product']) ?></strong></td>
                                <td><?= e($e['req_name'] . ' ' . $e['req_surname']) ?></td>
                                <td><small class="text-muted"><?= e($e['warehouse']) ?></small></td>
                                <td class="num-align">
                                    <span
                                        class="badge bg-warning text-dark px-2"><?= e(formatQty($e['remaining_quantity'])) ?></span>
                                    <small class="text-muted"><?= e($e['unit']) ?></small>
                                </td>
                                <td class="num-align">
                                    <?php if ($e['expected_return_at']): ?>
                                        <small
                                            class="<?= (strtotime($e['expected_return_at']) < time()) ? 'text-danger fw-bold' : 'text-muted' ?>">
                                            <i
                                                class="far fa-calendar-alt me-1"></i><?= e(date('d.m.Y', strtotime($e['expected_return_at']))) ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="num-align">
                                    <small><?= e(date('d.m.Y H:i', strtotime($e['created_at']))) ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($entrustedList)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted p-4">Sistemde aktif emanet kaydı bulunamadı.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Son Stok Girişleri -->
    <div class="col-md-6">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title text-bold"><i class="fas fa-sign-in-alt me-2"></i>Son Stok Girişleri</h3>
                <div class="card-tools">
                    <a href="<?= BASE_URL ?>/index.php?page=stock_in_list" class="btn btn-sm btn-success shadow-sm">
                        Hepsini Gör <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Ürün</th>
                            <th class="num-align">Adet</th>
                            <th class="num-align">Tarih</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentIn as $r): ?>
                            <tr>
                                <td>
                                    <strong><?= e($r['product']) ?></strong><br>
                                    <small class="text-muted"><i class="fas fa-warehouse me-1"></i>
                                        <?= e($r['warehouse']) ?></small>
                                </td>
                                <td class="num-align">
                                    <span class="text-success fw-bold"><?= e(formatQty($r['quantity'])) ?></span>
                                    <small class="text-muted"><?= e($r['unit']) ?></small>
                                </td>
                                <td class="num-align">
                                    <small><?= e(date('d.m.Y H:i', strtotime($r['created_at']))) ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentIn)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted p-3">Henüz giriş yok</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Son Stok Çıkışları -->
    <div class="col-md-6">
        <div class="card card-outline card-danger">
            <div class="card-header">
                <h3 class="card-title text-bold"><i class="fas fa-sign-out-alt me-2"></i>Son Stok Çıkışları</h3>
                <div class="card-tools">
                    <a href="<?= BASE_URL ?>/index.php?page=stock_out" class="btn btn-sm btn-danger shadow-sm">
                        Hepsini Gör <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Ürün</th>
                            <th class="num-align">Adet</th>
                            <th class="num-align">Tarih</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOut as $r): ?>
                            <tr>
                                <td>
                                    <strong><?= e($r['product']) ?></strong><br>
                                    <small class="text-muted"><i class="fas fa-warehouse me-1"></i>
                                        <?= e($r['warehouse']) ?></small>
                                </td>
                                <td class="num-align">
                                    <span class="text-danger fw-bold"><?= e(formatQty($r['quantity'])) ?></span>
                                    <small class="text-muted"><?= e($r['unit']) ?></small>
                                </td>
                                <td class="num-align">
                                    <small><?= e(date('d.m.Y H:i', strtotime($r['created_at']))) ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentOut)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted p-3">Henüz çıkış yok</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>