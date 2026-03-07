<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireRole(ROLE_ADMIN, ROLE_USER);
header('Content-Type: application/json; charset=utf-8');

// CSRF check
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($token)) {
        jsonResponse(false, 'Güvenlik doğrulaması başarısız (CSRF).');
    }
}

$action = sanitize($_POST['action'] ?? $_GET['action'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = min(100, max(5, (int) ($_GET['per_page'] ?? 10)));
$search = sanitize($_GET['search'] ?? '');
$offset = ($page - 1) * $perPage;
$table = 'tbl_dp_warehouses';

switch ($action) {
    case 'list':
        $where = "hidden=0";
        $params = [];
        if ($search) {
            $where .= " AND (name LIKE ? OR address LIKE ?)";
            $params = ["%$search%", "%$search%"];
        }
        $total = Database::fetchOne("SELECT COUNT(*) AS c FROM `$table` WHERE $where", $params)['c'] ?? 0;
        $rows = Database::fetchAll("SELECT * FROM `$table` WHERE $where ORDER BY name ASC LIMIT $perPage OFFSET $offset", $params);
        jsonResponse(true, '', ['data' => $rows, 'total' => (int) $total]);

    case 'get':
        $id = (int) ($_GET['id'] ?? 0);
        $row = Database::fetchOne("SELECT * FROM `$table` WHERE id=? AND hidden=0", [$id]);
        if (!$row)
            jsonResponse(false, 'Kayıt bulunamadı.');
        jsonResponse(true, '', $row);

    case 'add':
        $name = sanitize($_POST['name'] ?? '');
        if (!$name)
            jsonResponse(false, 'Depo adı zorunludur.');
        Database::insert("INSERT INTO `$table` (name, address, description, is_active) VALUES (?,?,?,?)", [
            $name,
            sanitize($_POST['address'] ?? ''),
            sanitize($_POST['description'] ?? ''),
            (int) ($_POST['is_active'] ?? 1)
        ]);
        jsonResponse(true, 'Depo eklendi.');

    case 'edit':
        $id = (int) ($_POST['id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $is_active = (int) ($_POST['is_active'] ?? 1);
        if (!$id || !$name)
            jsonResponse(false, 'Depo adı zorunludur.');

        // Deaktivasyon kontrolü
        if ($is_active == 0 && get_setting('allow_passive_with_stock', '0') == '0') {
            if (!isWarehouseEmpty($id)) {
                jsonResponse(false, 'Pasif etmek istediğiniz depoda ürünler mevcuttur. Önce depolar arası transferle depoyu boşaltın.');
            }
        }

        Database::execute("UPDATE `$table` SET name=?, address=?, description=?, is_active=? WHERE id=?", [
            $name,
            sanitize($_POST['address'] ?? ''),
            sanitize($_POST['description'] ?? ''),
            $is_active,
            $id
        ]);
        jsonResponse(true, 'Depo güncellendi.');

    case 'toggle':
        $id = (int) ($_POST['id'] ?? 0);
        $status = (int) ($_POST['status'] ?? 0);

        // Deaktivasyon kontrolü
        if ($status == 0 && get_setting('allow_passive_with_stock', '0') == '0') {
            if (!isWarehouseEmpty($id)) {
                jsonResponse(false, 'Pasif etmek istediğiniz depoda ürünler mevcuttur. Önce depolar arası transferle depoyu boşaltın.');
            }
        }

        Database::execute("UPDATE `$table` SET is_active=? WHERE id=?", [$status, $id]);
        jsonResponse(true, $status ? 'Depo aktifleştirildi.' : 'Depo pasifize edildi.');

    case 'delete':
        $id = (int) ($_POST['id'] ?? 0);
        if (hasMovement($table, 'id', $id)) {
            Database::execute("UPDATE `$table` SET hidden=1 WHERE id=?", [$id]);
            jsonResponse(true, 'Depo gizlendi (hareketi olduğu için silinemez).');
        }
        Database::execute("DELETE FROM `$table` WHERE id=?", [$id]);
        jsonResponse(true, 'Depo silindi.');

    // Aktif depo listesi (dropdown için)
    case 'active_list':
        $where = "w.hidden=0 AND w.is_active=1";
        $params = [];
        $q = sanitize($_GET['search'] ?? $_GET['q'] ?? '');
        if ($q) {
            $where .= " AND w.name LIKE ?";
            $params = ["%$q%"];
        }
        $rows = Database::fetchAll("
            SELECT w.id, w.name, 
            (SELECT COUNT(*) FROM inventory_sessions WHERE warehouse_id = w.id AND status = 'open') > 0 as is_inventory_open 
            FROM `$table` w 
            WHERE $where
            ORDER BY w.name
        ", $params);
        jsonResponse(true, '', $rows);

    default:
        jsonResponse(false, 'Geçersiz işlem.');
}