<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireRole(ROLE_ADMIN, ROLE_USER);
header('Content-Type: application/json; charset=utf-8');

$action = sanitize($_POST['action'] ?? $_GET['action'] ?? '');

// Stok hesaplama: giriş - çıkış (transfer mirror içerir)
function getStockData(array $warehouseIds, string $search = '', int $page = 1, int $perPage = 25): array
{
    try {
        if (empty($warehouseIds)) {
            return ['data' => [], 'total' => 0, 'columns' => []];
        }

        // Warehouse adlarını al
        $placeholders = implode(',', array_fill(0, count($warehouseIds), '?'));
        $warehouses = Database::fetchAll(
            "SELECT id, name FROM tbl_dp_warehouses WHERE id IN ($placeholders) ORDER BY name",
            array_values($warehouseIds)
        );
        $warehouseNames = array_column($warehouses, 'name');
        $warehouseMap = array_column($warehouses, 'name', 'id');

        // Tüm ürünleri al (arama + sayfalama)
        // Sadece seçili depolara en az bir kez girmiş ürünleri getir
        $placeholders = implode(',', array_fill(0, count($warehouseIds), '?'));
        $where = "p.hidden=0 AND p.is_active=1 AND EXISTS (SELECT 1 FROM tbl_dp_stock_in si WHERE si.product_id = p.id AND si.is_active=1 AND si.warehouse_id IN ($placeholders))";
        $params = array_values($warehouseIds);

        if ($search) {
            $where .= " AND p.name LIKE ?";
            $params[] = "%$search%";
        }

        $totalResult = Database::fetchOne("SELECT COUNT(*) AS c FROM tbl_dp_products p WHERE $where", $params);
        $total = $totalResult['c'] ?? 0;

        $offset = ($page - 1) * $perPage;
        $products = Database::fetchAll(
            "SELECT p.id, p.name, p.image, p.unit, p.stock_alarm FROM tbl_dp_products p WHERE $where ORDER BY p.name LIMIT $perPage OFFSET $offset",
            $params
        );

        if (empty($products)) {
            return ['data' => [], 'total' => (int) $total, 'columns' => $warehouseNames];
        }

        $productIds = array_column($products, 'id');
        $prodPH = implode(',', array_fill(0, count($productIds), '?'));

        // Query parameters: warehouses first, then products
        $queryParams = array_merge(array_values($warehouseIds), array_values($productIds));

        // Giriş toplamları
        $ins = Database::fetchAll(
            "SELECT warehouse_id, product_id, SUM(quantity) AS qty
             FROM tbl_dp_stock_in
             WHERE is_active=1 AND warehouse_id IN ($placeholders) AND product_id IN ($prodPH)
             GROUP BY warehouse_id, product_id",
            $queryParams
        );

        // Çıkış toplamları
        $outs = Database::fetchAll(
            "SELECT warehouse_id, product_id, SUM(quantity) AS qty
             FROM tbl_dp_stock_out
             WHERE warehouse_id IN ($placeholders) AND product_id IN ($prodPH)
             GROUP BY warehouse_id, product_id",
            $queryParams
        );

        // İndeksle
        $inMap = $outMap = [];
        foreach ($ins as $r) {
            $inMap[$r['warehouse_id']][$r['product_id']] = (float) $r['qty'];
        }
        foreach ($outs as $r) {
            $outMap[$r['warehouse_id']][$r['product_id']] = (float) $r['qty'];
        }

        $data = [];
        foreach ($products as $p) {
            $row = [
                'product_id' => $p['id'],
                'product' => $p['name'],
                'image' => $p['image'],
                'unit' => $p['unit'],
                'stock_alarm' => (int) $p['stock_alarm'],
                'stocks' => [],
                'total' => 0
            ];
            foreach ($warehouseIds as $wid) {
                $in = $inMap[$wid][$p['id']] ?? 0;
                $out = $outMap[$wid][$p['id']] ?? 0;
                $qty = round($in - $out, 3);
                $row['stocks'][$warehouseMap[$wid] ?? $wid] = $qty;
                $row['total'] += $qty;
            }
            $row['total'] = round($row['total'], 3);
            $data[] = $row;
        }

        return ['data' => $data, 'total' => (int) $total, 'columns' => $warehouseNames];

    } catch (Exception $e) {
        error_log("Stock Status Error: " . $e->getMessage());
        throw $e;
    }
}

switch ($action) {
    case 'list':
        $warehouseIds = array_values(array_filter(array_map('intval', explode(',', $_GET['warehouses'] ?? ''))));
        $search = sanitize($_GET['search'] ?? '');
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(999, max(5, (int) ($_GET['per_page'] ?? 10)));
        $result = getStockData($warehouseIds, $search, $page, $perPage);
        jsonResponse(true, '', $result);
        break;

    case 'send_email':
        try {
            $to = sanitize($_POST['to'] ?? '');
            $withImages = (bool) ($_POST['with_images'] ?? false);
            $warehouseIds = array_values(array_filter(array_map('intval', explode(',', $_POST['warehouses'] ?? ''))));
            $search = sanitize($_POST['search'] ?? '');
            if (!filter_var($to, FILTER_VALIDATE_EMAIL))
                jsonResponse(false, 'Geçersiz e-posta.');

            $result = getStockData($warehouseIds, $search, 1, 999);
            if (empty($result['data']))
                jsonResponse(false, 'Gösterilecek stok verisi yok.');

            $body = '<h2>' . e(get_setting('site_name', 'Deppo')) . ' — Stok Durumu</h2>';
            $body .= '<p>Tarih: ' . date('d.m.Y H:i') . '</p>';
            $body .= '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;font-family:sans-serif;font-size:13px;width:100%">';
            $body .= '<thead style="background:#343a40;color:#fff">';
            $body .= '<tr>';
            if ($withImages)
                $body .= '<th style="width:60px">Görsel</th>';
            $body .= '<th>Ürün</th><th>Depo</th><th style="width:100px">Miktar</th></tr></thead><tbody>';

            $embeddedImages = [];
            $tmpFiles = [];
            foreach ($result['data'] as $row) {
                foreach ($result['columns'] as $col) {
                    $qty = $row['stocks'][$col] ?? 0;
                    if ($qty <= 0)
                        continue; // Sıfır stoklu kayıtları mailde gösterme

                    $body .= '<tr>';

                    // Resim Sütunu
                    if ($withImages) {
                        $body .= '<td style="text-align:center; vertical-align:middle;">';
                        if ($row['image']) {
                            $imgPath = UPLOAD_PATH . $row['image'];
                            if (file_exists($imgPath)) {
                                $cid = 'prod_' . md5($row['image']);
                                if (!isset($embeddedImages[$cid])) {
                                    // Thumbnail oluştur (80x80)
                                    $tmpPath = sys_get_temp_dir() . '/thumb_' . md5($row['image']) . '.jpg';
                                    if (resize_image($imgPath, $tmpPath, 80, 80)) {
                                        $embeddedImages[$cid] = $tmpPath;
                                        $tmpFiles[] = $tmpPath;
                                    } else {
                                        $embeddedImages[$cid] = $imgPath;
                                    }
                                }
                                $body .= '<img src="cid:' . $cid . '" style="width:50px;height:50px;object-fit:cover;border:1px solid #ddd;border-radius:4px;">';
                            }
                        }
                        $body .= '</td>';
                    }

                    // Ürün Hücresi
                    $body .= '<td>' . e($row['product']) . '</td>';

                    // Depo
                    $body .= '<td>' . e($col) . '</td>';

                    // Miktar
                    $body .= '<td style="text-align:right;font-weight:bold;color:#198754">' . formatQty($qty) . ' <small style="font-weight:normal;color:#666">' . e($row['unit'] ?? 'Adet') . '</small></td>';

                    $body .= '</tr>';
                }
            }
            $body .= '</tbody></table>';

            $sent = send_mail($to, e(get_setting('site_name', 'Deppo')) . ' — Stok Durumu', $body, true, $embeddedImages);

            // Geçici dosyaları temizle
            foreach ($tmpFiles as $f) {
                @unlink($f);
            }
            if ($sent)
                jsonResponse(true, 'E-posta gönderildi.');
            else
                jsonResponse(false, 'Mail gönderilemedi. Ayarları kontrol edin.');
        } catch (Exception $e) {
            jsonResponse(false, 'E-posta işlemi sırasında hata oluştu: ' . $e->getMessage());
        }

    case 'get_history':
        $productId = (int) ($_GET['product_id'] ?? 0);
        $warehouseId = (int) ($_GET['warehouse_id'] ?? 0);
        $search = sanitize($_GET['search'] ?? '');

        if (!$productId) {
            jsonResponse(false, 'Ürün ID eksik.');
        }

        $searchWhere = "";
        $searchParams = [];
        if ($search) {
            $searchWhere = " AND (note LIKE ? OR EXISTS (SELECT 1 FROM tbl_dp_suppliers WHERE id = i.supplier_id AND name LIKE ?))";
        }

        // Girişler ve Çıkışlar UNION
        // Not: Transferler ve Emanetler zaten bu tablolara kayıt atıyor.
        $query = "
            (SELECT 'Giriş' AS type, i.id AS record_id, NULL AS batch_id, i.quantity, i.unit_price, i.currency, i.note, i.created_at,
                    (SELECT name FROM tbl_dp_suppliers WHERE id = i.supplier_id) AS contact,
                    (SELECT name FROM tbl_dp_admins WHERE id = i.created_by) AS creator,
                    w.name AS warehouse_name
             FROM tbl_dp_stock_in i
             JOIN tbl_dp_warehouses w ON w.id = i.warehouse_id
             WHERE i.product_id = ? " . ($warehouseId ? "AND i.warehouse_id = ?" : "") .
            ($search ? " AND (i.note LIKE ? OR EXISTS (SELECT 1 FROM tbl_dp_suppliers s WHERE s.id = i.supplier_id AND s.name LIKE ?))" : "") . ")
            UNION ALL
            (SELECT 'Çıkış' AS type, o.id AS record_id, o.batch_id, o.quantity, o.unit_price, o.currency, o.note, o.created_at,
                    COALESCE(
                        (SELECT CONCAT(name, ' ', surname) FROM tbl_dp_requesters WHERE id = o.requester_id),
                        (SELECT name FROM tbl_dp_customers WHERE id = o.customer_id)
                    ) AS contact,
                    (SELECT name FROM tbl_dp_admins WHERE id = o.created_by) AS creator,
                    w.name AS warehouse_name
             FROM tbl_dp_stock_out o
             JOIN tbl_dp_warehouses w ON w.id = o.warehouse_id
             WHERE o.product_id = ? " . ($warehouseId ? "AND o.warehouse_id = ?" : "") .
            ($search ? " AND (o.note LIKE ? OR EXISTS (SELECT 1 FROM tbl_dp_customers c WHERE c.id = o.customer_id AND c.name LIKE ?) OR EXISTS (SELECT 1 FROM tbl_dp_requesters r WHERE r.id = o.requester_id AND (r.name LIKE ? OR r.surname LIKE ?)))" : "") . ")
            ORDER BY created_at DESC
        ";

        $params = [$productId];
        if ($warehouseId)
            $params[] = $warehouseId;
        if ($search) {
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        $params[] = $productId; // Second part of UNION
        if ($warehouseId)
            $params[] = $warehouseId;
        if ($search) {
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $rows = Database::fetchAll($query, $params);

        foreach ($rows as &$r) {
            $r['created_at_fmt'] = date('d.m.Y H:i', strtotime($r['created_at']));
            $r['quantity_fmt'] = formatQty($r['quantity']);
            $r['price_base'] = toBaseCurrencyDisplay($r['unit_price'], $r['currency'] ?: 'EUR');
            $r['total_base'] = $r['price_base'] * $r['quantity'];
        }

        jsonResponse(true, '', $rows);
        break;
}