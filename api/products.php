<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireRole(ROLE_ADMIN, ROLE_USER);
header('Content-Type: application/json; charset=utf-8');

$action = sanitize($_POST['action'] ?? $_GET['action'] ?? '');
$table = 'tbl_dp_products';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = min(100, max(5, (int) ($_GET['per_page'] ?? 10)));
$search = sanitize($_GET['search'] ?? '');
$offset = ($page - 1) * $perPage;

switch ($action) {
    case 'list':
        $where = 'hidden=0';
        $params = [];
        if ($search) {
            $where .= " AND (name LIKE ? OR code LIKE ?)";
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
            jsonResponse(false, 'Ürün adı zorunludur.');

        // Resim yükleme
        $imageFile = null;
        if (!empty($_FILES['image']['name'])) {
            $imageFile = uploadImage($_FILES['image'], 'prod_');
            if (!$imageFile)
                jsonResponse(false, 'Resim yüklenemedi. Desteklenen format: jpg, png, webp. Maks: 5MB');
        }

        Database::insert(
            "INSERT INTO `$table` (name,code,unit,description,image,is_active) VALUES (?,?,?,?,?,?)",
            [
                $name,
                sanitize($_POST['code'] ?? ''),
                sanitize($_POST['unit'] ?? 'Adet'),
                sanitize($_POST['description'] ?? ''),
                $imageFile,
                (int) ($_POST['is_active'] ?? 1)
            ]
        );
        jsonResponse(true, 'Ürün eklendi.');

    case 'edit':
        $id = (int) ($_POST['id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        if (!$id || !$name)
            jsonResponse(false, 'Ürün adı zorunludur.');

        $current = Database::fetchOne("SELECT image FROM `$table` WHERE id=?", [$id]);
        $imageFile = $current['image'] ?? null;

        if (!empty($_FILES['image']['name'])) {
            $newImage = uploadImage($_FILES['image'], 'prod_');
            if (!$newImage) {
                $errCode = $_FILES['image']['error'] ?? 'Bilinmeyen Hata';
                jsonResponse(false, 'Resim yüklenemedi. (Hata Kodu: ' . $errCode . ') Desteklenen format: jpg, png, webp. Maks: 5MB');
            }
            // Eski resmi sil
            if ($imageFile && file_exists(UPLOAD_PATH . $imageFile)) {
                @unlink(UPLOAD_PATH . $imageFile);
            }
            $imageFile = $newImage;
        }

        Database::execute(
            "UPDATE `$table` SET name=?,code=?,unit=?,description=?,image=?,is_active=? WHERE id=?",
            [
                $name,
                sanitize($_POST['code'] ?? ''),
                sanitize($_POST['unit'] ?? 'Adet'),
                sanitize($_POST['description'] ?? ''),
                $imageFile,
                (int) ($_POST['is_active'] ?? 1),
                $id
            ]
        );
        jsonResponse(true, 'Ürün güncellendi.');

    case 'toggle':
        $id = (int) ($_POST['id'] ?? 0);
        $status = (int) ($_POST['status'] ?? 0);
        Database::execute("UPDATE `$table` SET is_active=? WHERE id=?", [$status, $id]);
        jsonResponse(true, $status ? 'Ürün aktifleştirildi.' : 'Ürün pasifize edildi.');

    case 'delete':
        $id = (int) ($_POST['id'] ?? 0);
        if (hasMovement($table, 'id', $id)) {
            Database::execute("UPDATE `$table` SET hidden=1 WHERE id=?", [$id]);
            jsonResponse(true, 'Ürün gizlendi (hareketi olduğu için silinemez).');
        }
        // Resim dosyasını sil
        $row = Database::fetchOne("SELECT image FROM `$table` WHERE id=?", [$id]);
        if ($row && $row['image'] && file_exists(UPLOAD_PATH . $row['image'])) {
            @unlink(UPLOAD_PATH . $row['image']);
        }
        Database::execute("DELETE FROM `$table` WHERE id=?", [$id]);
        jsonResponse(true, 'Ürün silindi.');

    // Select2 için arama (ürün resimli dropdown)
    case 'search_select2':
        $q = sanitize($_GET['q'] ?? '');
        $rows = Database::fetchAll(
            "SELECT id, name, code, image, unit FROM `$table` WHERE hidden=0 AND is_active=1 AND (name LIKE ? OR code LIKE ?) ORDER BY name LIMIT 30",
            ["%$q%", "%$q%"]
        );
        $results = array_map(function ($r) {
            return [
                'id' => $r['id'],
                'text' => $r['name'] . ($r['code'] ? ' [' . $r['code'] . ']' : ''),
                'image' => $r['image'],
                'unit' => $r['unit']
            ];
        }, $rows);
        echo json_encode(['results' => $results]);
        exit;

    case 'active_list':
        $rows = Database::fetchAll("SELECT id,name,code,image,unit FROM `$table` WHERE hidden=0 AND is_active=1 ORDER BY name");
        jsonResponse(true, '', $rows);

    default:
        jsonResponse(false, 'Geçersiz işlem.');
}