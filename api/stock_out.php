<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireRole(ROLE_ADMIN, ROLE_USER, ROLE_REQUESTER);
header('Content-Type: application/json; charset=utf-8');

$action = sanitize($_POST['action'] ?? $_GET['action'] ?? '');

switch ($action) {
    case 'add':
        $warehouseId = (int)($_POST['warehouse_id'] ?? 0);
        $requesterId = (int)($_POST['requester_id'] ?? 0) ?: null;
        $customerId = (int)($_POST['customer_id'] ?? 0) ?: null;
        $note = sanitize($_POST['note'] ?? '');
        $linesJson = $_POST['lines'] ?? '[]';
        $lines = json_decode($linesJson, true);

        if (!$warehouseId)
            jsonResponse(false, 'Depo seçimi zorunludur.');
        if (empty($lines))
            jsonResponse(false, 'En az 1 ürün gereklidir.');

        $userId = currentUser()['id'];

        foreach ($lines as $line) {
            $productId = (int)($line['product_id'] ?? 0);
            $quantity = (float)($line['quantity'] ?? 0);
            $unitPrice = (float)($line['unit_price'] ?? 0);
            $totalPrice = (float)($line['total'] ?? 0);

            if (!$productId || $quantity <= 0)
                continue;

            Database::insert(
                "INSERT INTO tbl_dp_stock_out (warehouse_id,requester_id,customer_id,product_id,quantity,unit_price,total_price,note,created_by)
                 VALUES (?,?,?,?,?,?,?,?,?)",
            [$warehouseId, $requesterId, $customerId, $productId, $quantity, $unitPrice, $totalPrice, $note, $userId]
            );
        }
        jsonResponse(true, 'Çıkış kaydedildi.');

    case 'get_last_price':
        $productId = (int)($_GET['product_id'] ?? 0);
        $warehouseId = (int)($_GET['warehouse_id'] ?? 0);

        // Önce aynı depodaki son girişe bak, yoksa herhangi bir depodan al
        $row = null;
        if ($warehouseId) {
            $row = Database::fetchOne(
                "SELECT price_eur FROM tbl_dp_stock_in WHERE product_id=? AND warehouse_id=? ORDER BY created_at DESC LIMIT 1",
            [$productId, $warehouseId]
            );
        }
        if (!$row) {
            $row = Database::fetchOne(
                "SELECT price_eur FROM tbl_dp_stock_in WHERE product_id=? ORDER BY created_at DESC LIMIT 1",
            [$productId]
            );
        }
        jsonResponse(true, '', $row ?: ['price_eur' => 0]);

    case 'recent':
        $rows = Database::fetchAll(
            "SELECT so.id, p.name AS product, w.name AS warehouse, so.quantity, p.unit, so.total_price,
                    DATE_FORMAT(so.created_at,'%d.%m.%Y %H:%i') AS created_at
             FROM tbl_dp_stock_out so
             JOIN tbl_dp_products p ON p.id=so.product_id
             JOIN tbl_dp_warehouses w ON w.id=so.warehouse_id
             ORDER BY so.created_at DESC LIMIT 15"
        );
        jsonResponse(true, '', $rows);

    case 'list':
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(100, max(5, (int)($_GET['per_page'] ?? 25)));
        $search = sanitize($_GET['search'] ?? '');
        $offset = ($page - 1) * $perPage;
        $where = "1=1";
        $params = [];
        if ($search) {
            $where .= " AND (p.name LIKE ? OR w.name LIKE ?)";
            $params = ["%$search%", "%$search%"];
        }
        $total = Database::fetchOne(
            "SELECT COUNT(*) AS c FROM tbl_dp_stock_out so
             JOIN tbl_dp_products p ON p.id=so.product_id
             JOIN tbl_dp_warehouses w ON w.id=so.warehouse_id WHERE $where", $params
        )['c'] ?? 0;
        $rows = Database::fetchAll(
            "SELECT so.id, p.name AS product, p.unit, w.name AS warehouse,
                    r.name AS requester_name, r.surname AS requester_surname,
                    c.name AS customer, so.quantity, so.unit_price, so.total_price, so.note,
                    DATE_FORMAT(so.created_at,'%d.%m.%Y %H:%i') AS created_at
             FROM tbl_dp_stock_out so
             JOIN tbl_dp_products p ON p.id=so.product_id
             JOIN tbl_dp_warehouses w ON w.id=so.warehouse_id
             LEFT JOIN tbl_dp_requesters r ON r.id=so.requester_id
             LEFT JOIN tbl_dp_customers c ON c.id=so.customer_id
             WHERE $where ORDER BY so.created_at DESC LIMIT $perPage OFFSET $offset", $params
        );
        jsonResponse(true, '', ['data' => $rows, 'total' => (int)$total]);

    default:
        jsonResponse(false, 'Geçersiz işlem.');
}