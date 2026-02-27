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

$usdRate = get_setting('usd_rate', '0');
$eurRate = get_setting('eur_rate', '0');
$currencyUpdated = get_setting('currency_updated', '');
?>

<!-- İstatistik Kartları -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>
                    <?= e($warehouseCount) ?>
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
                    <?= e($productCount) ?>
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
                    <?= e($customerCount) ?>
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
                    <?= e($supplierCount) ?>
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
                    <?= e($stockInToday) ?> kayıt
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
                    <?= e($stockOutToday) ?> kayıt
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
                    <?= e($transferCount) ?> işlem
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Döviz Kuru Kartı -->
<div class="row">
    <div class="col-md-6">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-money-bill-wave me-2"></i>Döviz Kurları (TCMB)</h3>
                <?php if ($currencyUpdated): ?>
                    <span class="badge bg-secondary float-right mt-1">
                        Güncelleme:
                        <?= e(date('d.m.Y H:i', strtotime($currencyUpdated))) ?>
                    </span>
                    <?php
                endif; ?>
            </div>
            <div class="card-body py-2">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="p-3 bg-light rounded">
                            <span class="text-muted d-block small">USD / TL</span>
                            <span class="h4 fw-bold text-success">
                                <?= e($usdRate > 0 ? formatPrice((float) $usdRate) : '—') ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-light rounded">
                            <span class="text-muted d-block small">EUR / TL</span>
                            <span class="h4 fw-bold text-primary">
                                <?= e($eurRate > 0 ? formatPrice((float) $eurRate) : '—') ?>
                            </span>
                        </div>
                    </div>
                </div>
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
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Ürün</th>
                            <th class="num-align">Adet</th>
                            <th>Tarih</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentIn as $r): ?>
                        <tr>
                            <td>
                                <strong><?= e($r['product']) ?></strong><br>
                                <small class="text-muted"><i class="fas fa-warehouse me-1"></i> <?= e($r['warehouse']) ?></small>
                            </td>
                            <td class="num-align">
                                <span class="text-success fw-bold"><?= e(formatQty($r['quantity'])) ?></span>
                                <small class="text-muted"><?= e($r['unit']) ?></small>
                            </td>
                            <td><small><?= e(date('d.m.Y H:i', strtotime($r['created_at']))) ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentIn)): ?>
                        <tr><td colspan="3" class="text-center text-muted p-3">Henüz giriş yok</td></tr>
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
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Ürün</th>
                            <th class="num-align">Adet</th>
                            <th>Tarih</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOut as $r): ?>
                        <tr>
                            <td>
                                <strong><?= e($r['product']) ?></strong><br>
                                <small class="text-muted"><i class="fas fa-warehouse me-1"></i> <?= e($r['warehouse']) ?></small>
                            </td>
                            <td class="num-align">
                                <span class="text-danger fw-bold"><?= e(formatQty($r['quantity'])) ?></span>
                                <small class="text-muted"><?= e($r['unit']) ?></small>
                            </td>
                            <td><small><?= e(date('d.m.Y H:i', strtotime($r['created_at']))) ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentOut)): ?>
                        <tr><td colspan="3" class="text-center text-muted p-3">Henüz çıkış yok</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>