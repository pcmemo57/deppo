<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireRole(ROLE_ADMIN, ROLE_USER);
header('Content-Type: application/json; charset=utf-8');

$action = sanitize($_POST['action'] ?? $_GET['action'] ?? '');

switch ($action) {
    case 'add':
        $sourceId = (int) ($_POST['source_warehouse_id'] ?? 0);
        $targetId = (int) ($_POST['target_warehouse_id'] ?? 0);
        $note = sanitize($_POST['note'] ?? '');
        $lines = json_decode($_POST['lines'] ?? '[]', true);

        if (!$sourceId || !$targetId)
            jsonResponse(false, 'Kaynak ve hedef depo zorunludur.');
        if ($sourceId === $targetId)
            jsonResponse(false, 'Kaynak ve hedef aynı olamaz.');
        if (empty($lines))
            jsonResponse(false, 'En az 1 ürün gerekli.');

        foreach ($lines as $line) {
            $productId = (int) ($line['product_id'] ?? 0);
            $quantity = (float) ($line['quantity'] ?? 0);
            if (!$productId || $quantity <= 0)
                continue;

            $available = getProductStock($productId, $sourceId);
            if ($quantity > $available) {
                jsonResponse(false, 'Yetersiz stok miktarı. Ürün ID: ' . $productId . ', Mevcut: ' . $available . ', Talep: ' . $quantity);
            }
        }

        $currentUser = currentUser();
        $userId = $currentUser['id'];
        $userName = $currentUser['name'];
        $transferId = Database::insert(
            "INSERT INTO tbl_dp_transfers (source_warehouse_id, target_warehouse_id, note, created_by, created_by_name) VALUES (?,?,?,?,?)",
            [$sourceId, $targetId, $note, $userId, $userName]
        );

        foreach ($lines as $line) {
            $productId = (int) ($line['product_id'] ?? 0);
            $quantity = (float) ($line['quantity'] ?? 0);
            if (!$productId || $quantity <= 0)
                continue;

            Database::insert(
                "INSERT INTO tbl_dp_transfer_items (transfer_id, product_id, quantity) VALUES (?,?,?)",
                [$transferId, $productId, $quantity]
            );

            // Kaynak depodan çıkar → hedef depoya giriş
            // Hedef: En son fiyatı ve birimi bul
            $lastPriceData = Database::fetchOne(
                "SELECT unit_price, currency FROM tbl_dp_stock_in WHERE product_id=? ORDER BY created_at DESC LIMIT 1",
                [$productId]
            );
            $unitPriceOrig = $lastPriceData ? (float) $lastPriceData['unit_price'] : 0;
            $currencyOrig = $lastPriceData ? $lastPriceData['currency'] : 'EUR';
            $priceInBase = toBaseCurrencyDisplay($unitPriceOrig, $currencyOrig);

            // Kaynak: stock_out kaydı
            Database::insert(
                "INSERT INTO tbl_dp_stock_out (warehouse_id,product_id,quantity,currency,unit_price,total_price,note,created_by)
                 VALUES (?,?,?,?,?,?,?,?)",
                [$sourceId, $productId, $quantity, $currencyOrig, $unitPriceOrig, $unitPriceOrig * $quantity, "Transfer #$transferId", $userId]
            );

            // Hedef: stock_in kaydı
            Database::insert(
                "INSERT INTO tbl_dp_stock_in (warehouse_id,product_id,quantity,unit_price,currency,price_eur,note,created_by)
                 VALUES (?,?,?,?,?,?,?,?)",
                [$targetId, $productId, $quantity, $unitPriceOrig, $currencyOrig, $priceInBase, "Transfer #$transferId", $userId]
            );
        }
        jsonResponse(true, 'Transfer tamamlandı.');
        break;

    case 'recent':
        $rows = Database::fetchAll(
            "SELECT t.id, sw.name AS source, tw.name AS target, t.created_by_name,
                    (SELECT COUNT(*) FROM tbl_dp_transfer_items WHERE transfer_id=t.id) AS item_count,
                    DATE_FORMAT(t.created_at,'%d.%m.%Y %H:%i') AS created_at
             FROM tbl_dp_transfers t
             JOIN tbl_dp_warehouses sw ON sw.id=t.source_warehouse_id
             JOIN tbl_dp_warehouses tw ON tw.id=t.target_warehouse_id
             ORDER BY t.created_at DESC LIMIT 10"
        );
        jsonResponse(true, '', $rows);
        break;

    case 'list':
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(100, max(5, (int) ($_GET['per_page'] ?? 10)));
        $search = sanitize($_GET['search'] ?? '');
        $offset = ($page - 1) * $perPage;
        $where = "1=1";
        $params = [];
        if ($search) {
            $where .= " AND (sw.name LIKE ? OR tw.name LIKE ? OR t.note LIKE ? OR EXISTS (
                SELECT 1 FROM tbl_dp_transfer_items ti 
                JOIN tbl_dp_products p ON p.id = ti.product_id 
                WHERE ti.transfer_id = t.id AND p.name LIKE ?
            ))";
            $params = ["%$search%", "%$search%", "%$search%", "%$search%"];
        }
        $total = Database::fetchOne(
            "SELECT COUNT(*) AS c FROM tbl_dp_transfers t
             JOIN tbl_dp_warehouses sw ON sw.id=t.source_warehouse_id
             JOIN tbl_dp_warehouses tw ON tw.id=t.target_warehouse_id WHERE $where",
            $params
        )['c'] ?? 0;
        $rows = Database::fetchAll(
            "SELECT t.id, sw.name AS source, tw.name AS target, t.note,
                    (SELECT COUNT(*) FROM tbl_dp_transfer_items WHERE transfer_id=t.id) AS item_count,
                    DATE_FORMAT(t.created_at,'%d.%m.%Y %H:%i') AS created_at
             FROM tbl_dp_transfers t
             JOIN tbl_dp_warehouses sw ON sw.id=t.source_warehouse_id
             JOIN tbl_dp_warehouses tw ON tw.id=t.target_warehouse_id
             WHERE $where ORDER BY t.created_at DESC LIMIT $perPage OFFSET $offset",
            $params
        );
        foreach ($rows as &$r) {
            $r['created_by_name'] = Database::fetchOne("SELECT created_by_name FROM tbl_dp_transfers WHERE id=?", [$r['id']])['created_by_name'] ?? '—';
        }
        jsonResponse(true, '', ['data' => $rows, 'total' => (int) $total]);
        break;

    case 'get_items':
        $id = (int) ($_GET['id'] ?? 0);
        $rows = Database::fetchAll(
            "SELECT ti.quantity, p.name AS product, p.unit
             FROM tbl_dp_transfer_items ti
             JOIN tbl_dp_products p ON p.id=ti.product_id
             WHERE ti.transfer_id=?",
            [$id]
        );
        jsonResponse(true, '', $rows);
        break;

    default:
        jsonResponse(false, 'Geçersiz işlem.');
}