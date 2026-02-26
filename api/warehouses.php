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
        jsonResponse(true, '', ['data' => $rows, 'total' => (int)$total]);

    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        $row = Database::fetchOne("SELECT * FROM `$table` WHERE id=? AND hidden=0", [$id]);
        if (!$row)
            jsonResponse(false, 'Kayıt bulunamadı.');
        jsonResponse(true, '', $row);

    case 'add':
        $name = sanitize($_POST['name'] ?? '');
        if (!$name)
            jsonResponse(false, 'Depo adı zorunludur.');
        Database::insert("INSERT INTO `$table` (name, address, description, is_active) VALUES (?,?,?,?)", [
            $name, sanitize($_POST['address'] ?? ''), sanitize($_POST['description'] ?? ''), (int)($_POST['is_active'] ?? 1)
        ]);
        jsonResponse(true, 'Depo eklendi.');

    case 'edit':
        $id = (int)($_POST['id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        if (!$id || !$name)
            jsonResponse(false, 'Depo adı zorunludur.');
        Database::execute("UPDATE `$table` SET name=?, address=?, description=?, is_active=? WHERE id=?", [
            $name, sanitize($_POST['address'] ?? ''), sanitize($_POST['description'] ?? ''), (int)($_POST['is_active'] ?? 1), $id
        ]);
        jsonResponse(true, 'Depo güncellendi.');

    case 'toggle':
        $id = (int)($_POST['id'] ?? 0);
        $status = (int)($_POST['status'] ?? 0);
        Database::execute("UPDATE `$table` SET is_active=? WHERE id=?", [$status, $id]);
        jsonResponse(true, $status ? 'Depo aktifleştirildi.' : 'Depo pasifize edildi.');

    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        if (hasMovement($table, 'id', $id)) {
            Database::execute("UPDATE `$table` SET hidden=1 WHERE id=?", [$id]);
            jsonResponse(true, 'Depo gizlendi (hareketi olduğu için silinemez).');
        }
        Database::execute("DELETE FROM `$table` WHERE id=?", [$id]);
        jsonResponse(true, 'Depo silindi.');

    // Aktif depo listesi (dropdown için)
    case 'active_list':
        $rows = Database::fetchAll("SELECT id, name FROM `$table` WHERE hidden=0 AND is_active=1 ORDER BY name");
        jsonResponse(true, '', $rows);

    default:
        jsonResponse(false, 'Geçersiz işlem.');
}