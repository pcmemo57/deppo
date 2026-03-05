<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireRole(ROLE_ADMIN, ROLE_USER, ROLE_REQUESTER);

$batchId = sanitize($_GET['batch_id'] ?? '');

if (!$batchId) {
    die('Sipariş bulunamadı.');
}

$items = Database::fetchAll(
    "SELECT so.*, p.name AS product_name, p.unit, w.name AS warehouse_name, p.description, p.image,
            c.name AS customer_name, r.name AS requester_name, r.surname AS requester_surname
     FROM tbl_dp_stock_out so
     JOIN tbl_dp_products p ON p.id=so.product_id
     LEFT JOIN tbl_dp_warehouses w ON w.id=so.warehouse_id
     LEFT JOIN tbl_dp_customers c ON c.id=so.customer_id
     LEFT JOIN tbl_dp_requesters r ON r.id=so.requester_id
     WHERE so.batch_id=?",
    [$batchId]
);

if (empty($items)) {
    die('Sipariş bulunamadı.');
}

$first = $items[0];
$warehouseName = $first['warehouse_name'] ?? '—';
$customerName = $first['customer_name'] ?? '—';
$requesterName = ($first['requester_name'] || $first['requester_surname']) ? trim($first['requester_name'] . ' ' . $first['requester_surname']) : '—';
$orderNo = $first['order_no'];
$note = $first['note'] ?? '—';
$date = date('d.m.Y H:i', strtotime($first['created_at']));
$createdBy = $first['created_by_name'] ?? '—';

$totalBase = 0;
// Note: total limits and price processing removed per user request
unset($item);
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Çıkış Fişi -
        <?= e($batchId) ?>
    </title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/plugins/fontawesome-free/css/all.min.css">
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm;
        }

        body {
            font-family: 'Open Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 13px;
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
            padding: 4px 8px;
            font-size: 11px;
            border: none;
        }

        .info-label {
            font-weight: 500;
            color: #4a5568;
            text-align: left;
            width: 40%;
        }

        .info-value {
            font-weight: 700;
            color: #000;
            text-align: right;
        }

        table.items-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #cbd5e0;
        }

        table.items-table th {
            background-color: #f7fafc;
            color: #2d3748;
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
            border: 1px solid #cbd5e0;
            padding: 6px 8px;
            text-align: left;
        }

        table.items-table td {
            border: 1px solid #cbd5e0;
            padding: 4px 8px;
            vertical-align: middle;
        }

        .num-align {
            text-align: right !important;
        }

        .list-end {
            text-align: center;
            padding: 15px;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            border: 1px solid #cbd5e0;
            border-top: none;
            background: #fdfdfd;
        }

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
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
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

        /* Item styling */
        .item-row {
            display: flex;
            align-items: center;
        }

        .item-img-container {
            width: 30px;
            height: 30px;
            margin-right: 8px;
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

        .item-name {
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
            margin-bottom: 0;
            display: block;
        }

        .item-desc {
            font-size: 11px;
            color: #718096;
            margin-top: 2px;
        }
    </style>
</head>

<body>
    <div class="no-print">
        <button class="btn btn-primary shadow-sm" onclick="window.print()"><i class="fas fa-print"></i> Yazdır</button>
        <button class="btn btn-secondary shadow-sm" onclick="window.close()"><i class="fas fa-times"></i> Kapat</button>
    </div>
    <div class="container" id="pdfContent">
        <div style="text-align: center; margin-bottom: 20px; border-bottom: 2px solid #cbd5e0; padding-bottom: 15px;">
            <h2 style="margin: 0; color: #2d3748; font-size: 20px; text-transform: uppercase;">Stok Çıkış Fişi / Stock
                Out Slip</h2>
            <div style="color: #718096; margin-top: 5px; font-weight: 600;">İşlem No (Batch No): <span
                    style="color: #2d3748;">
                    <?= e($batchId) ?>
                </span></div>
        </div>
        <table class="info-header-table">
            <tr>
                <td class="info-label">Müşteri (Customer):</td>
                <td class="info-value">
                    <?= e($customerName) ?>
                </td>
            </tr>
            <tr>
                <td class="info-label">Talep Eden (Requester):</td>
                <td class="info-value">
                    <?= e($requesterName) ?>
                </td>
            </tr>
            <tr>
                <td class="info-label">Sipariş No (Order No):</td>
                <td class="info-value">
                    <?= e($orderNo) ?>
                </td>
            </tr>
            <tr>
                <td class="info-label">Tarih (Date):</td>
                <td class="info-value">
                    <?= e($date) ?>
                </td>
            </tr>
            <tr>
                <td class="info-label">Not (Note):</td>
                <td class="info-value" style="font-weight: 500; font-style: italic;">
                    <?= e($note) ?>
                </td>
            </tr>
        </table>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Ürün (Product)</th>
                    <th class="num-align" style="width: 25%">Miktar (Qty)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <div class="item-row">
                                <div class="item-img-container">
                                    <?php if ($item['image']): ?>
                                        <img src="<?= UPLOAD_URL . e($item['image']) ?>" class="item-img" alt="product">
                                    <?php else: ?>
                                        <i class="fas fa-image text-muted opacity-25" style="font-size: 20px;"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <span class="item-name">
                                        <?= e($item['product_name']) ?>
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td class="num-align" style="font-weight: 600; font-size: 11px;">
                            <?= formatQty($item['quantity']) ?> <span
                                style="font-size: 10px; font-weight: normal; color: #718096;">
                                <?= e($item['unit']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="list-end">
            LİSTE SONU (THE END OF LIST)
        </div>
    </div>
</body>

</html>