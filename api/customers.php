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
$table = 'tbl_dp_customers';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = min(100, max(5, (int) ($_GET['per_page'] ?? 10)));
$search = sanitize($_GET['search'] ?? '');
$offset = ($page - 1) * $perPage;

switch ($action) {
    case 'list':
        $where = 'hidden=0';
        $params = [];
        if ($search) {
            $where .= " AND (name LIKE ? OR contact LIKE ? OR email LIKE ?)";
            $params = ["%$search%", "%$search%", "%$search%"];
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
            jsonResponse(false, 'Müşteri adı zorunludur.');
        Database::insert("INSERT INTO `$table` (name,contact,email,phone,address,is_active) VALUES (?,?,?,?,?,?)", [$name, sanitize($_POST['contact'] ?? ''), sanitize($_POST['email'] ?? ''), sanitize($_POST['phone'] ?? ''), sanitize($_POST['address'] ?? ''), (int) ($_POST['is_active'] ?? 1)]);
        jsonResponse(true, 'Müşteri eklendi.');
    case 'edit':
        $id = (int) ($_POST['id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        if (!$id || !$name)
            jsonResponse(false, 'Müşteri adı zorunludur.');
        Database::execute("UPDATE `$table` SET name=?,contact=?,email=?,phone=?,address=?,is_active=? WHERE id=?", [sanitize($_POST['name'] ?? ''), sanitize($_POST['contact'] ?? ''), sanitize($_POST['email'] ?? ''), sanitize($_POST['phone'] ?? ''), sanitize($_POST['address'] ?? ''), (int) ($_POST['is_active'] ?? 1), $id]);
        jsonResponse(true, 'Müşteri güncellendi.');
    case 'toggle':
        $id = (int) ($_POST['id'] ?? 0);
        $status = (int) ($_POST['status'] ?? 0);
        Database::execute("UPDATE `$table` SET is_active=? WHERE id=?", [$status, $id]);
        jsonResponse(true, $status ? 'Aktifleştirildi.' : 'Pasifize edildi.');
    case 'delete':
        $id = (int) ($_POST['id'] ?? 0);
        if (hasMovement($table, 'id', $id)) {
            Database::execute("UPDATE `$table` SET hidden=1 WHERE id=?", [$id]);
            jsonResponse(true, 'Müşteri gizlendi (hareketi olduğu için silinemez).');
        }
        Database::execute("DELETE FROM `$table` WHERE id=?", [$id]);
        jsonResponse(true, 'Müşteri silindi.');
    case 'active_list':
        $rows = Database::fetchAll("SELECT id,name FROM `$table` WHERE hidden=0 AND is_active=1 ORDER BY name");
        jsonResponse(true, '', $rows);

    case 'search':
        $q = sanitize($_GET['q'] ?? '');
        $rows = Database::fetchAll("SELECT id, name as text FROM `$table` WHERE hidden=0 AND is_active=1 AND name LIKE ? ORDER BY name LIMIT 20", ["%$q%"]);
        echo json_encode(['results' => $rows]);
        exit;
    default:
        jsonResponse(false, 'Geçersiz işlem.');
}