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
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = min(100, max(5, (int) ($_GET['per_page'] ?? 10)));
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
        jsonResponse(true, '', ['data' => $rows, 'total' => (int) $total]);

    case 'get':
        $id = (int) ($_GET['id'] ?? 0);
        $row = Database::fetchOne("SELECT * FROM `$table` WHERE id=? AND hidden=0", [$id]);
        if (!$row)
            jsonResponse(false, 'Kayıt bulunamadı.');
        jsonResponse(true, '', $row);

    case 'add':
        $name = sanitize($_POST['name'] ?? '');
        $surname = sanitize($_POST['surname'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $title = sanitize($_POST['title'] ?? '');
        $active = (int) ($_POST['is_active'] ?? 1);

        if (!$name || !$surname || !$password)
            jsonResponse(false, 'Ad, soyad ve şifre zorunludur.');

        if ($email) {
            $exists = Database::fetchOne("SELECT id FROM `$table` WHERE email = ? AND hidden = 0", [$email]);
            if ($exists)
                jsonResponse(false, 'Bu e-posta adresi zaten bir talep eden tarafından kullanılıyor.');
        }

        $hashed = hashPassword($password);

        $id = Database::insert(
            "INSERT INTO `$table` (name, surname, email, phone, password, title, is_active) VALUES (?,?,?,?,?,?,?)",
            [$name, $surname, $email, $phone, $hashed, $title, $active]
        );
        jsonResponse(true, 'Talep eden eklendi.', ['id' => $id, 'name' => "$name $surname"]);

    case 'edit':
        $id = (int) ($_POST['id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $surname = sanitize($_POST['surname'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $title = sanitize($_POST['title'] ?? '');
        $active = (int) ($_POST['is_active'] ?? 1);

        if (!$id || !$name || !$surname)
            jsonResponse(false, 'Ad ve soyad zorunludur.');

        if ($email) {
            $exists = Database::fetchOne("SELECT id FROM `$table` WHERE email = ? AND id != ? AND hidden = 0", [$email, $id]);
            if ($exists)
                jsonResponse(false, 'Bu e-posta adresi başka bir talep eden tarafından kullanılıyor.');
        }

        $sql = "UPDATE `$table` SET name=?, surname=?, email=?, phone=?, title=?, is_active=? WHERE id=?";
        $params = [$name, $surname, $email, $phone, $title, $active, $id];

        if (!empty($password)) {
            $sql = "UPDATE `$table` SET name=?, surname=?, email=?, phone=?, title=?, is_active=?, password=? WHERE id=?";
            $params = [$name, $surname, $email, $phone, $title, $active, hashPassword($password), $id];
        }

        Database::execute($sql, $params);
        jsonResponse(true, 'Talep eden güncellendi.');

    case 'toggle':
        $id = (int) ($_POST['id'] ?? 0);
        $status = (int) ($_POST['status'] ?? 0);
        Database::execute("UPDATE `$table` SET is_active=? WHERE id=?", [$status, $id]);
        jsonResponse(true, $status ? 'Aktifleştirildi.' : 'Pasifize edildi.');

    case 'delete':
        $id = (int) ($_POST['id'] ?? 0);
        if (hasMovement($table, 'id', $id)) {
            Database::execute("UPDATE `$table` SET hidden=1 WHERE id=?", [$id]);
            jsonResponse(true, 'Kayıt gizlendi (hareketi olduğu için silinemez).');
        }
        Database::execute("DELETE FROM `$table` WHERE id=?", [$id]);
        jsonResponse(true, 'Kayıt silindi.');

    case 'active_list':
        $where = "hidden=0 AND is_active=1";
        $params = [];
        $q = sanitize($_GET['search'] ?? $_GET['q'] ?? '');
        if ($q) {
            $where .= " AND (name LIKE ? OR surname LIKE ?)";
            $params = ["%$q%", "%$q%"];
        }
        $rows = Database::fetchAll("SELECT id, name, surname FROM `$table` WHERE $where ORDER BY name ASC", $params);
        jsonResponse(true, '', $rows);

    case 'check_email':
        $email = sanitize($_GET['email'] ?? '');
        $id = (int) ($_GET['id'] ?? 0);
        if (!$email)
            jsonResponse(true);
        $exists = Database::fetchOne("SELECT id FROM `$table` WHERE email = ? AND id != ? AND hidden = 0", [$email, $id]);
        jsonResponse(true, '', ['exists' => (bool) $exists]);

    default:
        jsonResponse(false, 'Geçersiz işlem.');
}