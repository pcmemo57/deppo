<?php
/**
 * CRUD API Şablonu — Requesters, Warehouses, Customers, Suppliers
 * Her entity için tekrar eden CRUD mantığını tek dosyada tutmak için generic handler
 */
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

$table = 'tbl_dp_requesters';

switch ($action) {
    case 'list':
        $where = "hidden=0";
        $params = [];
        if ($search) {
            $where .= " AND (name LIKE ? OR surname LIKE ? OR email LIKE ? OR title LIKE ?)";
            $params = ["%$search%", "%$search%", "%$search%", "%$search%"];
        }
        $total = Database::fetchOne("SELECT COUNT(*) AS c FROM `$table` WHERE $where", $params)['c'] ?? 0;
        $rows = Database::fetchAll(
            "SELECT * FROM `$table` WHERE $where ORDER BY id DESC LIMIT $perPage OFFSET $offset",
            $params
        );
        jsonResponse(true, '', ['data' => $rows, 'total' => (int)$total]);

    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        $row = Database::fetchOne("SELECT * FROM `$table` WHERE id=? AND hidden=0", [$id]);
        if (!$row)
            jsonResponse(false, 'Kayıt bulunamadı.');
        jsonResponse(true, '', $row);

    case 'add':
        $name = sanitize($_POST['name'] ?? '');
        $surname = sanitize($_POST['surname'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $title = sanitize($_POST['title'] ?? '');
        $active = (int)($_POST['is_active'] ?? 1);

        if (!$name || !$surname)
            jsonResponse(false, 'Ad ve soyad zorunludur.');

        Database::insert(
            "INSERT INTO `$table` (name, surname, email, title, is_active) VALUES (?,?,?,?,?)",
        [$name, $surname, $email, $title, $active]
        );
        jsonResponse(true, 'Talep eden eklendi.');

    case 'edit':
        $id = (int)($_POST['id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $surname = sanitize($_POST['surname'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $title = sanitize($_POST['title'] ?? '');
        $active = (int)($_POST['is_active'] ?? 1);

        if (!$id || !$name || !$surname)
            jsonResponse(false, 'Ad ve soyad zorunludur.');

        Database::execute(
            "UPDATE `$table` SET name=?, surname=?, email=?, title=?, is_active=? WHERE id=?",
        [$name, $surname, $email, $title, $active, $id]
        );
        jsonResponse(true, 'Talep eden güncellendi.');

    case 'toggle':
        $id = (int)($_POST['id'] ?? 0);
        $status = (int)($_POST['status'] ?? 0);
        Database::execute("UPDATE `$table` SET is_active=? WHERE id=?", [$status, $id]);
        jsonResponse(true, $status ? 'Aktifleştirildi.' : 'Pasifize edildi.');

    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        if (hasMovement($table, 'id', $id)) {
            Database::execute("UPDATE `$table` SET hidden=1 WHERE id=?", [$id]);
            jsonResponse(true, 'Kayıt gizlendi (hareketi olduğu için silinemez).');
        }
        Database::execute("DELETE FROM `$table` WHERE id=?", [$id]);
        jsonResponse(true, 'Kayıt silindi.');

    default:
        jsonResponse(false, 'Geçersiz işlem.');
}