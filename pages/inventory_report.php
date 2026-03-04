<?php
/**
 * Depo Sayım Karşılaştırma Raporu
 */
requireRole(ROLE_ADMIN, ROLE_USER);

$sessionId = (int) ($_GET['id'] ?? 0);
if (!$sessionId) {
    echo '<div class="alert alert-danger">Sayım oturumu bulunamadı.</div>';
    return;
}

$session = Database::fetchOne("
    SELECT s.*, 
    DATE_FORMAT(s.created_at, '%d.%m.%Y [%H:%i]') as created_at,
    DATE_FORMAT(s.closed_at, '%d.%m.%Y [%H:%i]') as closed_at,
    w.name as warehouse_name, u.name as creator_name
    FROM inventory_sessions s
    JOIN tbl_dp_warehouses w ON w.id = s.warehouse_id
    LEFT JOIN tbl_dp_users u ON u.id = s.created_by
    WHERE s.id = ?
", [$sessionId]);

if (!$session) {
    echo '<div class="alert alert-danger">Sayım oturumu bulunamadı.</div>';
    return;
}

$items = Database::fetchAll("
    SELECT i.*, 
    DATE_FORMAT(i.counted_at, '%d.%m.%Y [%H:%i]') as counted_at,
    p.name as product_name, p.code as product_code, p.unit
    FROM inventory_items i
    JOIN tbl_dp_products p ON p.id = i.product_id
    WHERE i.session_id = ?
    ORDER BY ABS(i.difference) DESC, p.name ASC
", [$sessionId]);

$totalDiff = 0;
foreach ($items as $it)
    if ($it['difference'] != 0)
        $totalDiff++;
?>

<div class="row">
    <div class="col-12">
        <div class="card card-info card-outline shadow-sm no-print">
            <div class="card-header border-0">
                <h3 class="card-title text-bold"><i class="fas fa-file-invoice me-2 text-info"></i>Sayım Detayı -
                    <?= $session['warehouse_name'] ?>
                </h3>
                <div class="card-tools">
                    <button class="btn btn-default btn-sm shadow-sm me-1" onclick="window.print()">
                        <i class="fas fa-print me-1"></i> Yazdır
                    </button>
                    <a href="<?= BASE_URL ?>/index.php?page=inventory" class="btn btn-secondary btn-sm shadow-sm">
                        <i class="fas fa-arrow-left me-1"></i> Listeye Dön
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label class="text-muted small">Oluşturma Tarihi</label>
                        <p class="fw-bold">
                            <?= $session['created_at'] ?>
                        </p>
                    </div>
                    <div class="col-md-3">
                        <label class="text-muted small">Kapanış Tarihi</label>
                        <p class="fw-bold">
                            <?= $session['closed_at'] ?? '<span class="text-success blink_me">DEVAM EDİYOR</span>' ?>
                        </p>
                    </div>
                    <div class="col-md-3">
                        <label class="text-muted small">Personel</label>
                        <p class="fw-bold">
                            <?= $session['creator_name'] ?>
                        </p>
                    </div>
                    <div class="col-md-3">
                        <label class="text-muted small">Durum</label>
                        <p class="fw-bold">
                            <?= $session['status'] === 'open' ? '<span class="text-success">Açık</span>' : '<span class="text-secondary">Kapalı</span>' ?>
                        </p>
                    </div>
                </div>

                <?php if ($totalDiff > 0): ?>
                    <div class="alert alert-warning border-0 shadow-sm mt-3 mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i> Bu sayımda <strong>
                            <?= $totalDiff ?>
                        </strong> üründe stok farkı tespit edildi.
                    </div>
                <?php else: ?>
                    <div class="alert alert-success border-0 shadow-sm mt-3 mb-0">
                        <i class="fas fa-check-circle me-2"></i> Her şey yolunda! Sayılan tüm kalemler sistem stoklarıyla
                        eşleşiyor.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0 table-responsive">
                <!-- Mobil Görünüm (Kartlar) -->
                <div class="d-md-none p-2 bg-light">
                    <?php if (empty($items)): ?>
                        <div class="text-center p-4 text-muted">Henüz ürün okutulmamış.</div>
                    <?php else:
                        foreach ($items as $row):
                            $diffClass = '';
                            $diffIcon = 'fa-equals';
                            if ($row['difference'] > 0) {
                                $diffClass = 'text-success';
                                $diffIcon = 'fa-plus-circle';
                            } elseif ($row['difference'] < 0) {
                                $diffClass = 'text-danger';
                                $diffIcon = 'fa-minus-circle';
                            }
                            ?>
                            <?php
                            $isMatch = ($row['difference'] == 0);
                            $cardBg = $isMatch ? 'rgba(40, 167, 69, 0.12)' : 'rgba(220, 53, 69, 0.15)';
                            $cardBorder = $isMatch ? 'rgba(40, 167, 69, 0.2)' : 'rgba(220, 53, 69, 0.25)';
                            ?>
                            <div class="card mb-2 border shadow-none"
                                style="background-color: <?= $cardBg ?>; border-color: <?= $cardBorder ?> !important;">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div style="flex: 1;">
                                            <div class="fw-bold text-dark lh-sm"><?= $row['product_name'] ?></div>
                                            <small class="text-muted"><?= $row['product_code'] ?></small>
                                        </div>
                                        <div class="text-end ms-2">
                                            <span class="badge bg-light text-dark border"><?= $row['unit'] ?></span>
                                        </div>
                                    </div>
                                    <div class="row g-0 py-2 border-top border-bottom"
                                        style="background: rgba(255,255,255,0.4);">
                                        <div class="col-4 text-center">
                                            <small class="d-block text-muted">Sistem</small>
                                            <span class="fw-bold"><?= formatQty($row['expected_qty']) ?></span>
                                        </div>
                                        <div class="col-4 text-center border-start border-end">
                                            <small class="d-block text-muted">Sayılan</small>
                                            <span class="fw-bold text-primary"><?= formatQty($row['counted_qty']) ?></span>
                                        </div>
                                        <div class="col-4 text-center">
                                            <small class="d-block text-muted">Fark</small>
                                            <span
                                                class="fw-bold <?= $diffClass ?>"><?= ($row['difference'] > 0 ? '+' : '') . formatQty($row['difference']) ?></span>
                                        </div>
                                    </div>
                                    <?php if ($row['note']): ?>
                                        <div class="mt-2 small text-muted">
                                            <i class="fas fa-comment-dots me-1"></i> <?= $row['note'] ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                </div>

                <!-- Masaüstü Görünüm (Tablo) -->
                <table class="table table-hover m-0 d-none d-md-table">
                    <thead class="bg-light">
                        <tr>
                            <th>#</th>
                            <th>Ürün / Kod</th>
                            <th class="text-center">Sistem Stoku</th>
                            <th class="text-center">Sayılan Miktar</th>
                            <th class="text-center">Fark</th>
                            <th>Birim</th>
                            <th>Not</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="7" class="text-center p-4 text-muted">Bu sayım oturumunda henüz ürün
                                    okutulmamış.</td>
                            </tr>
                        <?php else:
                            $idx = 1;
                            foreach ($items as $row):
                                $diffClass = '';
                                $rowStyle = '';
                                if ($row['difference'] != 0) {
                                    $rowStyle = 'background-color: rgba(220, 53, 69, 0.15);';
                                } else {
                                    $rowStyle = 'background-color: rgba(40, 167, 69, 0.12);';
                                }

                                if ($row['difference'] > 0)
                                    $diffClass = 'text-success fw-bold';
                                elseif ($row['difference'] < 0)
                                    $diffClass = 'text-danger fw-bold';
                                ?>
                                <tr style="<?= $rowStyle ?>">
                                    <td>
                                        <?= $idx++ ?>
                                    </td>
                                    <td>
                                        <strong>
                                            <?= $row['product_name'] ?>
                                        </strong><br>
                                        <small class="text-muted">
                                            <?= $row['product_code'] ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <?= formatQty($row['expected_qty']) ?>
                                    </td>
                                    <td class="text-center fw-bold text-primary">
                                        <?= formatQty($row['counted_qty']) ?>
                                    </td>
                                    <td class="text-center <?= $diffClass ?>">
                                        <?= ($row['difference'] > 0 ? '+' : '') . formatQty($row['difference']) ?>
                                    </td>
                                    <td><small class="text-muted">
                                            <?= $row['unit'] ?>
                                        </small></td>
                                    <td><small>
                                            <?= $row['note'] ?>
                                        </small></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        .no-print {
            display: none !important;
        }

        .card {
            border: none !important;
            box-shadow: none !important;
        }

        .table td,
        .table th {
            border-bottom: 1px solid #dee2e6 !important;
        }
    }

    .blink_me {
        animation: blinker 1.5s linear infinite;
    }

    @keyframes blinker {
        50% {
            opacity: 0.2;
        }
    }
</style>