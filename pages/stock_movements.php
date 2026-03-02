<?php
/**
 * Stok Hareketleri Landing Page
 */
requireRole(ROLE_ADMIN, ROLE_USER);

// Widget Verileri
$stockInCount = Database::fetchOne('SELECT COUNT(*) AS c FROM tbl_dp_stock_in')['c'] ?? 0;
$stockOutCount = Database::fetchOne('SELECT COUNT(DISTINCT batch_id) AS c FROM tbl_dp_stock_out')['c'] ?? 0;
$transferCount = Database::fetchOne('SELECT COUNT(*) AS c FROM tbl_dp_transfers')['c'] ?? 0;
$entrustedCount = Database::fetchOne('SELECT COUNT(*) AS c FROM tbl_dp_entrusted WHERE remaining_quantity > 0')['c'] ?? 0;
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-bold"><i class="fas fa-exchange-alt me-2"></i> Stok Hareketleri</h1>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <!-- Stok Girişleri Widget -->
            <div class="col-lg-3 col-6">
                <a href="<?= BASE_URL ?>/index.php?page=stock_in_list" class="small-box bg-info shadow-sm h-100">
                    <div class="inner">
                        <h3>
                            <?= e(formatQty($stockInCount)) ?>
                        </h3>
                        <p>Stok Girişleri</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                </a>
            </div>

            <!-- Stok Çıkışları Widget -->
            <div class="col-lg-3 col-6">
                <a href="<?= BASE_URL ?>/index.php?page=stock_out_orders" class="small-box bg-success shadow-sm h-100">
                    <div class="inner">
                        <h3>
                            <?= e(formatQty($stockOutCount)) ?>
                        </h3>
                        <p>Depodan Çıkış (Sipariş)</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-sign-out-alt"></i>
                    </div>
                </a>
            </div>

            <!-- Transferler Widget -->
            <div class="col-lg-3 col-6">
                <a href="<?= BASE_URL ?>/index.php?page=transfer" class="small-box bg-warning shadow-sm h-100">
                    <div class="inner">
                        <h3>
                            <?= e(formatQty($transferCount)) ?>
                        </h3>
                        <p>Depolar Arası Transfer</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                </a>
            </div>

            <!-- Emanetler Widget -->
            <div class="col-lg-3 col-6">
                <a href="<?= BASE_URL ?>/index.php?page=entrusted" class="small-box bg-danger shadow-sm h-100">
                    <div class="inner">
                        <h3>
                            <?= e(formatQty($entrustedCount)) ?>
                        </h3>
                        <p>Aktif Emanetler</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-hand-holding"></i>
                    </div>
                </a>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card card-outline card-primary shadow-sm">
                    <div class="card-header border-0">
                        <h3 class="card-title text-bold">Hızlı İşlemler</h3>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-3">
                            <a href="<?= BASE_URL ?>/index.php?page=stock_in_list"
                                class="btn btn-outline-info btn-lg flex-fill py-3">
                                <i class="fas fa-plus-circle mb-2 d-block fa-2x"></i>
                                Yeni Stok Girişi
                            </a>
                            <a href="<?= BASE_URL ?>/index.php?page=stock_out_orders"
                                class="btn btn-outline-success btn-lg flex-fill py-3">
                                <i class="fas fa-minus-circle mb-2 d-block fa-2x"></i>
                                Yeni Stok Çıkışı
                            </a>
                            <a href="<?= BASE_URL ?>/index.php?page=transfer"
                                class="btn btn-outline-warning btn-lg flex-fill py-3">
                                <i class="fas fa-random mb-2 d-block fa-2x"></i>
                                Depo Transferi
                            </a>
                            <a href="<?= BASE_URL ?>/index.php?page=entrusted"
                                class="btn btn-outline-danger btn-lg flex-fill py-3">
                                <i class="fas fa-hand-holding-heart mb-2 d-block fa-2x"></i>
                                Emanet İşlemi
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .small-box {
        border-radius: 15px;
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .small-box:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
        text-decoration: none;
        color: #fff;
    }

    .small-box .inner {
        padding: 20px;
    }

    .small-box .icon {
        top: 10px;
        right: 15px;
        font-size: 50px;
        opacity: 0.3;
    }

    .btn-lg {
        border-radius: 12px;
        transition: all 0.3s;
        min-width: 180px;
    }

    .btn-lg:hover {
        transform: scale(1.02);
    }

    .gap-3 {
        gap: 1rem !important;
    }
</style>