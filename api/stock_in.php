<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireRole(ROLE_ADMIN, ROLE_USER);
header('Content-Type: application/json; charset=utf-8');

$action = sanitize($_POST['action'] ?? $_GET['action'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = min(100, max(5, (int)($_GET['per_page'] ?? 25)));
$search = sanitize($_GET['search'] ?? '');
$offset = ($page - 1) * $perPage;

switch ($action) {
    case 'add':
        $warehouseId = (int)($_POST['warehouse_id'] ?? 0);
        $productId = (int)($_POST['product_id'] ?? 0);
        $supplierId = (int)($_POST['supplier_id'] ?? 0) ?: null;
        $quantity = (float)($_POST['quantity'] ?? 0);
        $currency = sanitize($_POST['currency'] ?? '');
        $note = sanitize($_POST['note'] ?? '');

        // Fiyatı temizle (Türkçe format → float)
        $unitPriceRaw = str_replace(['.', ','], ['', '.'], sanitize($_POST['unit_price'] ?? '0'));
        $unitPrice = (float)$unitPriceRaw;

        if (!$warehouseId || !$productId || $quantity <= 0) {
            jsonResponse(false, 'Depo, ürün ve adet zorunludur.');
        }
        if (!in_array($currency, ['TL', 'USD', 'EUR'])) {
            jsonResponse(false, 'Para birimi seçimi zorunludur.');
        }

        // EUR'a çevir
        $priceEur = toEur($unitPrice, $currency);

        $userId = currentUser()['id'];

        Database::insert(
            "INSERT INTO tbl_dp_stock_in (warehouse_id, product_id, supplier_id, quantity, unit_price, currency, price_eur, note, created_by)
             VALUES (?,?,?,?,?,?,?,?,?)",
        [$warehouseId, $productId, $supplierId, $quantity, $unitPrice, $currency, $priceEur, $note, $userId]
        );
        jsonResponse(true, 'Stok girişi kaydedildi.');

    case 'recent':
        $rows = Database::fetchAll(
            "SELECT si.id, p.name AS product, w.name AS warehouse, si.quantity, p.unit,
                    si.unit_price, si.currency, DATE_FORMAT(si.created_at,'%d.%m.%Y %H:%i') AS created_at
             FROM tbl_dp_stock_in si
             JOIN tbl_dp_products p ON p.id=si.product_id
             JOIN tbl_dp_warehouses w ON w.id=si.warehouse_id
             ORDER BY si.created_at DESC LIMIT 15"
        );
        jsonResponse(true, '', $rows);

    case 'list':
        $whereBase = "1=1";
        $params = [];
        if ($search) {
            $whereBase .= " AND (p.name LIKE ? OR w.name LIKE ? OR s.name LIKE ?)";
            $params = ["%$search%", "%$search%", "%$search%"];
        }

        $warehouseFilter = (int)($_GET['warehouse_id'] ?? 0);
        if ($warehouseFilter) {
            $whereBase .= " AND si.warehouse_id = ?";
            $params[] = $warehouseFilter;
        }

        $total = Database::fetchOne(
            "SELECT COUNT(*) AS c FROM tbl_dp_stock_in si
             JOIN tbl_dp_products p ON p.id=si.product_id
             JOIN tbl_dp_warehouses w ON w.id=si.warehouse_id
             LEFT JOIN tbl_dp_suppliers s ON s.id=si.supplier_id
             WHERE $whereBase", $params
        )['c'] ?? 0;

        $rows = Database::fetchAll(
            "SELECT si.id, p.name AS product, p.unit, w.name AS warehouse, s.name AS supplier,
                    si.quantity, si.unit_price, si.currency, si.price_eur, si.note,
                    DATE_FORMAT(si.created_at,'%d.%m.%Y %H:%i') AS created_at
             FROM tbl_dp_stock_in si
             JOIN tbl_dp_products p ON p.id=si.product_id
             JOIN tbl_dp_warehouses w ON w.id=si.warehouse_id
             LEFT JOIN tbl_dp_suppliers s ON s.id=si.supplier_id
             WHERE $whereBase
             ORDER BY si.created_at DESC
             LIMIT $perPage OFFSET $offset", $params
        );
        jsonResponse(true, '', ['data' => $rows, 'total' => (int)$total]);

    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        $row = Database::fetchOne(
            "SELECT si.*, p.name AS product_name, p.unit, w.name AS warehouse_name
             FROM tbl_dp_stock_in si
             JOIN tbl_dp_products p ON p.id=si.product_id
             JOIN tbl_dp_warehouses w ON w.id=si.warehouse_id
             WHERE si.id=?", [$id]
        );
        if (!$row)
            jsonResponse(false, 'Kayıt bulunamadı.');
        jsonResponse(true, '', $row);

    case 'edit':
        $id = (int)($_POST['id'] ?? 0);
        $warehouseId = (int)($_POST['warehouse_id'] ?? 0);
        $productId = (int)($_POST['product_id'] ?? 0);
        $supplierId = (int)($_POST['supplier_id'] ?? 0) ?: null;
        $quantity = (float)($_POST['quantity'] ?? 0);
        $currency = sanitize($_POST['currency'] ?? '');
        $note = sanitize($_POST['note'] ?? '');
        $unitPriceRaw = str_replace(['.', ','], ['', '.'], sanitize($_POST['unit_price'] ?? '0'));
        $unitPrice = (float)$unitPriceRaw;

        if (!$id || !$warehouseId || !$productId || $quantity <= 0)
            jsonResponse(false, 'Zorunlu alanlar eksik.');
        $priceEur = toEur($unitPrice, $currency);

        Database::execute(
            "UPDATE tbl_dp_stock_in SET warehouse_id=?,product_id=?,supplier_id=?,quantity=?,unit_price=?,currency=?,price_eur=?,note=? WHERE id=?",
        [$warehouseId, $productId, $supplierId, $quantity, $unitPrice, $currency, $priceEur, $note, $id]
        );
        jsonResponse(true, 'Kayıt güncellendi.');

    default:
        jsonResponse(false, 'Geçersiz işlem.');
}