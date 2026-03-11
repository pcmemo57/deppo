<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireRole(ROLE_ADMIN, ROLE_USER);

$id = (int) ($_GET['id'] ?? 0);
$pl = Database::fetchOne("
    SELECT pl.*, c.name as customer_name
    FROM tbl_dp_packing_lists pl
    JOIN tbl_dp_customers c ON c.id = pl.customer_id
    WHERE pl.id = ?
", [$id]);

if (!$pl)
    die('Liste bulunamadı.');

$parcels = Database::fetchAll("SELECT * FROM tbl_dp_packing_list_parcels WHERE packing_list_id = ? ORDER BY parcel_no ASC", [$id]);

foreach ($parcels as &$p) {
    $p['items'] = Database::fetchAll("
        SELECT i.*, pr.name as product_name, pr.code as product_code, pr.unit, pr.image, pr.description
        FROM tbl_dp_packing_list_items i
        JOIN tbl_dp_products pr ON pr.id = i.product_id
        WHERE i.parcel_id = ?
    ", [$p['id']]);
}
unset($p);

$fontSizeScale = get_setting('font_size_scale', '100');
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Çeki Listesi - <?= e($pl['list_no']) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/plugins/fontawesome-free/css/all.min.css">
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm;
        }

        html {
            font-size: calc(100% *
                    <?= (float) $fontSizeScale / 100 ?>
                ) !important;
        }

        body {
            font-family: 'Open Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 0.8125rem;
            line-height: 1.4;
            color: #1a202c;
            margin: 0;
            padding: 0;
            background: #fff;
        }

        .container {
            width: 100%;
            max-width: 210mm;
            margin: 0 auto;
        }

        /* ───────────────────────────────────────────
           HEADER
        ─────────────────────────────────────────── */
        .info-header-table {
            width: 100%;
            border: 1px solid #cbd5e0;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .info-header-table tr:nth-child(odd) {
            background-color: #f7fafc;
        }

        .info-header-table tr:nth-child(even) {
            background-color: #ffffff;
        }

        .info-header-table td {
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            border: none;
        }

        .info-label {
            font-weight: 500;
            color: #4a5568;
            text-align: left;
        }

        .info-value {
            font-weight: 700;
            color: #000;
            text-align: right;
        }

        /* ───────────────────────────────────────────
           TABLE
        ─────────────────────────────────────────── */
        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #cbd5e0;
        }

        th {
            background-color: #f7fafc;
            color: #2d3748;
            font-weight: 700;
            font-size: 0.6875rem;
            text-transform: uppercase;
            border: 1px solid #cbd5e0;
            padding: 0.625rem 0.5rem;
            text-align: left;
        }

        td {
            border: 1px solid #cbd5e0;
            padding: 0.5rem;
            vertical-align: top;
        }

        .col-no {
            width: 10%;
            text-align: center;
            font-weight: 700;
            font-size: 0.6875rem;
        }

        .col-weight {
            width: 12%;
        }

        .col-dims {
            width: 16%;
        }

        .col-contents {
            width: 62%;
            padding: 0;
        }

        /* ───────────────────────────────────────────
           BOX CONTENT (NESTED)
        ─────────────────────────────────────────── */
        .item-row {
            display: flex;
            align-items: flex-start;
            padding: 10px;
            border-bottom: 1px solid #edf2f7;
        }

        .item-row:last-child {
            border-bottom: none;
        }

        .item-img-container {
            width: 70px;
            height: 70px;
            margin-right: 12px;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: #fff;
        }

        .item-img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .item-details {
            flex-grow: 1;
        }

        .item-name {
            font-weight: 700;
            font-size: 0.75rem;
            text-transform: uppercase;
            margin-bottom: 0.125rem;
            display: block;
        }

        .item-qty {
            font-size: 0.6875rem;
            font-weight: 500;
            color: #4a5568;
            margin-bottom: 0.25rem;
        }

        .item-desc {
            font-size: 0.6875rem;
            color: #718096;
            margin-top: 0.125rem;
        }

        .item-desc b {
            color: #4a5568;
        }

        /* ───────────────────────────────────────────
           FOOTER
        ─────────────────────────────────────────── */
        .list-end {
            text-align: center;
            padding: 0.9375rem;
            font-weight: 700;
            font-size: 0.75rem;
            text-transform: uppercase;
            border: 1px solid #cbd5e0;
            border-top: none;
            background: #fdfdfd;
        }

        /* ───────────────────────────────────────────
           CONTROL BUTTONS
        ─────────────────────────────────────────── */
        .no-print {
            position: fixed;
            bottom: 25px;
            right: 25px;
            background: rgba(255, 255, 255, 0.95);
            padding: 12px;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            display: flex;
            gap: 10px;
            z-index: 1000;
        }

        .btn {
            padding: 0.625rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-primary {
            background: #3b82f6;
            color: #fff;
        }

        .btn-primary:hover {
            background: #2563eb;
        }

        .btn-secondary {
            background: #64748b;
            color: #fff;
        }

        .btn-secondary:hover {
            background: #475569;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                padding: 0;
            }

            table {
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }
    </style>
    <script src="<?= BASE_URL ?>/assets/vendor/js/html2pdf.bundle.min.js"></script>
</head>

<body>

    <div class="no-print">
        <button class="btn btn-primary shadow-sm" onclick="window.print()">
            <i class="fas fa-print"></i> Yazdır
        </button>
        <button class="btn btn-secondary shadow-sm" onclick="window.close()">
            <i class="fas fa-times"></i> Kapat
        </button>
    </div>

    <div class="container" id="pdfContent">
        <table class="info-header-table">
            <tr>
                <td class="info-label">Müşteri (Customer):</td>
                <td class="info-value"><?= e($pl['customer_name']) ?></td>
            </tr>
            <tr>
                <td class="info-label">Tarih (Date):</td>
                <td class="info-value"><?= date('d.m.Y', strtotime($pl['created_at'])) ?></td>
            </tr>
            <tr>
                <td class="info-label">Toplam Koli Sayısı (Total Box Count):</td>
                <td class="info-value"><?= $pl['total_parcels'] ?></td>
            </tr>
            <tr>
                <td class="info-label">Toplam Ağırlık (Total Weight):</td>
                <td class="info-value"><?= formatPrice($pl['total_weight_kg']) ?> kg</td>
            </tr>
            <tr>
                <td class="info-label">Toplam Desi Değeri (Total Volumetric Weight):</td>
                <td class="info-value"><?= number_format($pl['total_vol_desi'], 2, ',', '.') ?> desi</td>
            </tr>
        </table>

        <table>
            <thead>
                <tr>
                    <th class="col-no">Koli No (Box No)</th>
                    <th class="col-weight">Ağırlık (Weight) (kg)</th>
                    <th class="col-dims">Boyutlar (Dimensions) (cm)</th>
                    <th class="col-contents"><span style="margin-left: 10px">Koli İçeriği (Box Contents)</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($parcels as $p): ?>
                    <tr>
                        <td class="col-no"><?= $p['parcel_no'] ?></td>
                        <td class="col-weight"><?= formatPrice($p['weight_kg']) ?> kg.</td>
                        <td class="col-dims">
                            <?= $p['width_cm'] ?>x<?= $p['length_cm'] ?>x<?= $p['height_cm'] ?> cm.
                        </td>
                        <td class="col-contents">
                            <?php foreach ($p['items'] as $item): ?>
                                <div class="item-row">
                                    <div class="item-img-container">
                                        <?php if ($item['image']): ?>
                                            <img src="<?= UPLOAD_URL . e($item['image']) ?>" class="item-img" alt="product">
                                        <?php else: ?>
                                            <i class="fas fa-image text-muted opacity-25" style="font-size: 1.5rem;"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="item-details">
                                        <span class="item-name"><?= e($item['product_name']) ?></span>
                                        <div class="item-qty">(<?= formatPrice($item['quantity']) ?>
                                            <?= e($item['unit'] ?: 'adet') ?>)
                                        </div>
                                        <div class="item-desc"><?= e($item['description'] ?: '-') ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($p['items'])): ?>
                                <div class="p-3 text-muted x-small italic text-center">İçerik bilgisi yok</div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="list-end">
            LİSTE SONU (THE END OF LIST)
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('format') === 'pdf') {
                const element = document.getElementById('pdfContent');
                const listNo = "<?= e($pl['list_no']) ?>";

                const opt = {
                    margin: 10,
                    filename: 'Ceki-Listesi-' + listNo + '.pdf',
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: { scale: 2, useCORS: true },
                    jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
                };

                // Generate PDF
                html2pdf().set(opt).from(element).save().then(() => {
                    // Sadece indirme işlemi bittikten sonra pencereyi kapatmak isterseniz:
                    // setTimeout(() => window.close(), 1000);
                });
            }
        });
    </script>

</body>

</html>