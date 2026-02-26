<?php
/**
 * API — Kullanıcı Yönetimi (admin & user tabloları)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireRole(ROLE_ADMIN);
header('Content-Type: application/json; charset=utf-8');

$action = sanitize($_POST['action'] ?? $_GET['action'] ?? '');
$type = sanitize($_POST['type'] ?? $_GET['type'] ?? 'admin');
$table = $type === 'admin' ? 'tbl_dp_admins' : 'tbl_dp_users';

switch ($action) {
    case 'list':
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(100, max(5, (int)($_GET['per_page'] ?? 25)));
        $search = sanitize($_GET['search'] ?? '');
        $offset = ($page - 1) * $perPage;

        $where = "hidden=0";
        $params = [];
        if ($search) {
            $where .= " AND (name LIKE ? OR email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $total = Database::fetchOne("SELECT COUNT(*) AS c FROM `$table` WHERE $where", $params)['c'] ?? 0;
        $rows = Database::fetchAll(
            "SELECT id, name, email, is_active, DATE_FORMAT(last_login,'%d.%m.%Y %H:%i') AS last_login
             FROM `$table` WHERE $where ORDER BY id DESC LIMIT $perPage OFFSET $offset",
            $params
        );
        jsonResponse(true, '', ['data' => $rows, 'total' => (int)$total]);

    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        $row = Database::fetchOne("SELECT id, name, email, is_active FROM `$table` WHERE id=? AND hidden=0", [$id]);
        if (!$row)
            jsonResponse(false, 'Kayıt bulunamadı.');
        jsonResponse(true, '', $row);

    case 'add_user':
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $pass = $_POST['password'] ?? '';
        $active = (int)($_POST['is_active'] ?? 1);

        if (!$name || !$email || !$pass)
            jsonResponse(false, 'Ad, e-posta ve şifre zorunludur.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            jsonResponse(false, 'Geçerli bir e-posta girin.');
        if (strlen($pass) < 6)
            jsonResponse(false, 'Şifre en az 6 karakter olmalıdır.');

        $exists = Database::fetchOne("SELECT id FROM `$table` WHERE email=?", [$email]);
        if ($exists)
            jsonResponse(false, 'Bu e-posta zaten kayıtlı.');

        Database::insert(
            "INSERT INTO `$table` (name, email, password, is_active) VALUES (?, ?, ?, ?)",
        [$name, $email, hashPassword($pass), $active]
        );
        jsonResponse(true, 'Kullanıcı eklendi.');

    case 'edit_user':
        $id = (int)($_POST['id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $pass = $_POST['password'] ?? '';
        $active = (int)($_POST['is_active'] ?? 1);

        if (!$id || !$name || !$email)
            jsonResponse(false, 'Ad ve e-posta zorunludur.');

        $exists = Database::fetchOne("SELECT id FROM `$table` WHERE email=? AND id!=?", [$email, $id]);
        if ($exists)
            jsonResponse(false, 'Bu e-posta başka kullanıcıda kayıtlı.');

        if ($pass) {
            if (strlen($pass) < 6)
                jsonResponse(false, 'Şifre en az 6 karakter olmalıdır.');
            Database::execute("UPDATE `$table` SET name=?, email=?, password=?, is_active=? WHERE id=?",
            [$name, $email, hashPassword($pass), $active, $id]);
        }
        else {
            Database::execute("UPDATE `$table` SET name=?, email=?, is_active=? WHERE id=?",
            [$name, $email, $active, $id]);
        }
        jsonResponse(true, 'Kullanıcı güncellendi.');

    case 'toggle_user':
        $id = (int)($_POST['id'] ?? 0);
        $status = (int)($_POST['status'] ?? 0);
        Database::execute("UPDATE `$table` SET is_active=? WHERE id=?", [$status, $id]);
        jsonResponse(true, $status ? 'Kullanıcı aktifleştirildi.' : 'Kullanıcı pasifize edildi.');

    case 'delete_user':
        $id = (int)($_POST['id'] ?? 0);
        if (hasMovement($table, 'id', $id)) {
            // Hareket görenleri gizle
            Database::execute("UPDATE `$table` SET hidden=1 WHERE id=?", [$id]);
            jsonResponse(true, 'Kullanıcı gizlendi (hareketi olduğu için fiziksel silinemez).');
        }
        Database::execute("DELETE FROM `$table` WHERE id=?", [$id]);
        jsonResponse(true, 'Kullanıcı silindi.');

    default:
        jsonResponse(false, 'Geçersiz işlem.');
}