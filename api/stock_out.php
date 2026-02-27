<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireRole(ROLE_ADMIN, ROLE_USER, ROLE_REQUESTER);
header('Content-Type: application/json; charset=utf-8');

$action = sanitize($_POST['action'] ?? $_GET['action'] ?? '');

switch ($action) {
    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        $row = Database::fetchOne(
            "SELECT so.*, p.name AS product_name, p.unit, w.name AS warehouse_name,
                    r.name AS requester_name, r.surname AS requester_surname, c.name AS customer_name
             FROM tbl_dp_stock_out so
             JOIN tbl_dp_products p ON p.id=so.product_id
             JOIN tbl_dp_warehouses w ON w.id=so.warehouse_id
             LEFT JOIN tbl_dp_requesters r ON r.id=so.requester_id
             LEFT JOIN tbl_dp_customers c ON c.id=so.customer_id
             WHERE so.id=?", [$id]
        );
        if (!$row)
            jsonResponse(false, 'Kayıt bulunamadı.');
        jsonResponse(true, '', $row);

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
        $batchId = 'SO-' . date('YmdHis') . '-' . rand(1000, 9999);

        foreach ($lines as $line) {
            $productId = (int)($line['product_id'] ?? 0);
            $quantity = (float)($line['quantity'] ?? 0);
            $unitPrice = (float)($line['unit_price'] ?? 0);
            $totalPrice = (float)($line['total'] ?? 0);

            if (!$productId || $quantity <= 0)
                continue;

            Database::insert(
                "INSERT INTO tbl_dp_stock_out (batch_id, warehouse_id, requester_id, customer_id, product_id, quantity, unit_price, total_price, note, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?)",
            [$batchId, $warehouseId, $requesterId, $customerId, $productId, $quantity, $unitPrice, $totalPrice, $note, $userId]
            );
        }
        jsonResponse(true, 'Çıkış kaydedildi.');

    case 'edit':
        $id = (int)($_POST['id'] ?? 0);
        $warehouseId = (int)($_POST['warehouse_id'] ?? 0);
        $requesterId = (int)($_POST['requester_id'] ?? 0) ?: null;
        $customerId = (int)($_POST['customer_id'] ?? 0) ?: null;
        $note = sanitize($_POST['note'] ?? '');

        // Düzenlemede modal yapısı gereği lines içindeki İLK ürünü alıyoruz (çünkü liste tablosundaki her satır tek ürünü temsil eder)
        $linesJson = $_POST['lines'] ?? '[]';
        $linesArr = json_decode($linesJson, true);
        $line = $linesArr[0] ?? null;

        if (!$id)
            jsonResponse(false, 'ID geçersiz.');
        if (!$warehouseId)
            jsonResponse(false, 'Depo seçimi zorunludur.');
        if (!$line)
            jsonResponse(false, 'Ürün bilgisi eksik.');

        $productId = (int)($line['product_id'] ?? 0);
        $quantity = (float)($line['quantity'] ?? 0);
        $unitPrice = (float)($line['unit_price'] ?? 0);
        $totalPrice = (float)($line['total'] ?? 0);

        Database::execute(
            "UPDATE tbl_dp_stock_out SET 
                warehouse_id=?, requester_id=?, customer_id=?, product_id=?, 
                quantity=?, unit_price=?, total_price=?, note=? 
             WHERE id=?",
        [$warehouseId, $requesterId, $customerId, $productId, $quantity, $unitPrice, $totalPrice, $note, $id]
        );
        jsonResponse(true, 'Çıkış kaydı güncellendi.');

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
                    DATE_FORMAT(so.created_at,'%d.%m.%Y') AS created_at
             FROM tbl_dp_stock_out so
             JOIN tbl_dp_products p ON p.id=so.product_id
             JOIN tbl_dp_warehouses w ON w.id=so.warehouse_id
             ORDER BY so.created_at DESC LIMIT 15"
        );
        jsonResponse(true, '', $rows);

    case 'list':
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(100, max(5, (int)($_GET['per_page'] ?? 10)));
        $search = sanitize($_GET['search'] ?? '');
        $offset = ($page - 1) * $perPage;
        $where = "1=1";
        $params = [];
        if ($search) {
            $where .= " AND (p.name LIKE ? OR w.name LIKE ? OR c.name LIKE ?)";
            $params = ["%$search%", "%$search%", "%$search%"];
        }
        $total = Database::fetchOne(
            "SELECT COUNT(*) AS c FROM tbl_dp_stock_out so
             JOIN tbl_dp_products p ON p.id=so.product_id
             JOIN tbl_dp_warehouses w ON w.id=so.warehouse_id WHERE $where", $params
        )['c'] ?? 0;
        $rows = Database::fetchAll(
            "SELECT so.id, so.batch_id, p.name AS product, p.unit, w.name AS warehouse,
                    r.name AS requester_name, r.surname AS requester_surname,
                    c.name AS customer, so.quantity, so.unit_price, so.total_price, so.note,
                    DATE_FORMAT(so.created_at,'%d.%m.%Y') AS created_at
             FROM tbl_dp_stock_out so
             JOIN tbl_dp_products p ON p.id=so.product_id
             JOIN tbl_dp_warehouses w ON w.id=so.warehouse_id
             LEFT JOIN tbl_dp_requesters r ON r.id=so.requester_id
             LEFT JOIN tbl_dp_customers c ON c.id=so.customer_id
             WHERE $where ORDER BY so.created_at DESC LIMIT $perPage OFFSET $offset", $params
        );
        jsonResponse(true, '', ['data' => $rows, 'total' => (int)$total]);

    case 'list_grouped':
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(100, max(5, (int)($_GET['per_page'] ?? 10)));
        $search = sanitize($_GET['search'] ?? '');
        $offset = ($page - 1) * $perPage;
        $where = "so.batch_id IS NOT NULL";
        $params = [];
        if ($search) {
            $where .= " AND (c.name LIKE ? OR w.name LIKE ? OR so.note LIKE ?)";
            $params = ["%$search%", "%$search%", "%$search%"];
        }

        $total = Database::fetchOne(
            "SELECT COUNT(DISTINCT so.batch_id) AS c 
             FROM tbl_dp_stock_out so
             LEFT JOIN tbl_dp_customers c ON c.id=so.customer_id
             LEFT JOIN tbl_dp_warehouses w ON w.id=so.warehouse_id
             WHERE $where", $params
        )['c'] ?? 0;

        $rows = Database::fetchAll(
            "SELECT so.batch_id, c.name AS customer_name, w.name AS warehouse_name,
                    COUNT(so.id) AS item_count, SUM(so.total_price) AS total_eur,
                    MAX(so.created_at) AS created_at, so.note
             FROM tbl_dp_stock_out so
             LEFT JOIN tbl_dp_customers c ON c.id=so.customer_id
             LEFT JOIN tbl_dp_warehouses w ON w.id=so.warehouse_id
             WHERE $where 
             GROUP BY so.batch_id
             ORDER BY created_at DESC LIMIT $perPage OFFSET $offset", $params
        );

        // Tarih formatla
        foreach ($rows as &$r) {
            $r['created_at_fmt'] = date('d.m.Y', strtotime($r['created_at']));
        }

        jsonResponse(true, '', ['data' => $rows, 'total' => (int)$total]);

    case 'get_batch':
        $batchId = sanitize($_GET['batch_id'] ?? '');
        if (!$batchId)
            jsonResponse(false, 'Batch ID eksik.');

        $items = Database::fetchAll(
            "SELECT so.*, p.name AS product_name, p.unit
             FROM tbl_dp_stock_out so
             JOIN tbl_dp_products p ON p.id=so.product_id
             WHERE so.batch_id=?", [$batchId]
        );
        jsonResponse(true, '', $items);

    default:
        jsonResponse(false, 'Geçersiz işlem.');
}