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
    if (empty($warehouseIds))
        return ['data' => [], 'total' => 0, 'columns' => []];

    $placeholders = implode(',', array_fill(0, count($warehouseIds), '?'));

    // Warehouse adlarını al
    $warehouses = Database::fetchAll(
        "SELECT id, name FROM tbl_dp_warehouses WHERE id IN ($placeholders) ORDER BY name",
        $warehouseIds
    );
    $warehouseNames = array_column($warehouses, 'name');
    $warehouseMap = array_column($warehouses, 'name', 'id');

    // Tüm ürünleri al (arama + sayfalama)
    $where = "p.hidden=0 AND p.is_active=1";
    $params = [];
    if ($search) {
        $where .= " AND p.name LIKE ?";
        $params[] = "%$search%";
    }

    $total = Database::fetchOne("SELECT COUNT(*) AS c FROM tbl_dp_products p WHERE $where", $params)['c'] ?? 0;
    $offset = ($page - 1) * $perPage;

    $products = Database::fetchAll(
        "SELECT p.id, p.name, p.image, p.unit FROM tbl_dp_products p WHERE $where ORDER BY p.name LIMIT $perPage OFFSET $offset",
        $params
    );

    if (empty($products))
        return ['data' => [], 'total' => (int)$total, 'columns' => $warehouseNames];

    $productIds = array_column($products, 'id');
    $prodPH = implode(',', array_fill(0, count($productIds), '?'));

    // Giriş toplamları (depo + ürün bazlı)
    $inParams = array_merge($warehouseIds, $productIds);
    $ins = Database::fetchAll(
        "SELECT warehouse_id, product_id, SUM(quantity) AS qty
         FROM tbl_dp_stock_in
         WHERE warehouse_id IN ($placeholders) AND product_id IN ($prodPH)
         GROUP BY warehouse_id, product_id",
        $inParams
    );

    // Çıkış toplamları
    $outs = Database::fetchAll(
        "SELECT warehouse_id, product_id, SUM(quantity) AS qty
         FROM tbl_dp_stock_out
         WHERE warehouse_id IN ($placeholders) AND product_id IN ($prodPH)
         GROUP BY warehouse_id, product_id",
        $inParams
    );

    // İndeksle
    $inMap = $outMap = [];
    foreach ($ins as $r)
        $inMap[$r['warehouse_id']][$r['product_id']] = (float)$r['qty'];
    foreach ($outs as $r)
        $outMap[$r['warehouse_id']][$r['product_id']] = (float)$r['qty'];

    $data = [];
    foreach ($products as $p) {
        $row = ['product' => $p['name'], 'image' => $p['image'], 'unit' => $p['unit'], 'stocks' => [], 'total' => 0];
        foreach ($warehouseIds as $wid) {
            $in = $inMap[$wid][$p['id']] ?? 0;
            $out = $outMap[$wid][$p['id']] ?? 0;
            $qty = round($in - $out, 3);
            $row['stocks'][$warehouseMap[$wid]] = $qty;
            $row['total'] += $qty;
        }
        $row['total'] = round($row['total'], 3);
        $data[] = $row;
    }

    return ['data' => $data, 'total' => (int)$total, 'columns' => $warehouseNames];
}

switch ($action) {
    case 'list':
        $warehouseIds = array_filter(array_map('intval', explode(',', $_GET['warehouses'] ?? '')));
        $search = sanitize($_GET['search'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(999, max(5, (int)($_GET['per_page'] ?? 25)));
        $result = getStockData($warehouseIds, $search, $page, $perPage);
        jsonResponse(true, '', $result);

    case 'send_email':
        $to = sanitize($_POST['to'] ?? '');
        $withImages = (bool)($_POST['with_images'] ?? false);
        $warehouseIds = array_filter(array_map('intval', explode(',', $_POST['warehouses'] ?? '')));
        $search = sanitize($_POST['search'] ?? '');
        if (!filter_var($to, FILTER_VALIDATE_EMAIL))
            jsonResponse(false, 'Geçersiz e-posta.');

        $result = getStockData($warehouseIds, $search, 1, 999);
        if (empty($result['data']))
            jsonResponse(false, 'Gösterilecek stok verisi yok.');

        $body = '<h2>' . e(get_setting('site_name', 'Deppo')) . ' — Stok Durumu</h2>';
        $body .= '<p>Tarih: ' . date('d.m.Y H:i') . '</p>';
        $body .= '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;font-family:sans-serif;font-size:13px">';
        $body .= '<thead style="background:#343a40;color:#fff"><tr><th>Ürün</th>';
        foreach ($result['columns'] as $col)
            $body .= '<th>' . e($col) . '</th>';
        $body .= '<th>Toplam</th></tr></thead><tbody>';

        foreach ($result['data'] as $row) {
            $body .= '<tr>';
            if ($withImages && $row['image']) {
                $imgPath = UPLOAD_PATH . $row['image'];
                if (file_exists($imgPath)) {
                    $imgData = base64_encode(file_get_contents($imgPath));
                    $ext = pathinfo($row['image'], PATHINFO_EXTENSION);
                    $body .= '<td><img src="data:image/' . $ext . ';base64,' . $imgData . '" style="width:40px;height:40px;object-fit:cover"> ' . e($row['product']) . '</td>';
                }
                else {
                    $body .= '<td>' . e($row['product']) . '</td>';
                }
            }
            else {
                $body .= '<td>' . e($row['product']) . '</td>';
            }
            foreach ($result['columns'] as $col) {
                $body .= '<td style="text-align:center">' . ($row['stocks'][$col] ?? 0) . '</td>';
            }
            $body .= '<td style="text-align:center;font-weight:bold">' . $row['total'] . '</td></tr>';
        }
        $body .= '</tbody></table>';

        $sent = send_mail($to, e(get_setting('site_name', 'Deppo')) . ' — Stok Durumu', $body);
        if ($sent)
            jsonResponse(true, 'E-posta gönderildi.');
        else
            jsonResponse(false, 'Mail gönderilemedi. Ayarları kontrol edin.');

    default:
        jsonResponse(false, 'Geçersiz işlem.');
}