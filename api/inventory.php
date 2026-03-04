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

try {
    switch ($action) {
        case 'start_session':
            $warehouseId = (int) ($_POST['warehouse_id'] ?? 0);
            $notes = sanitize($_POST['notes'] ?? '');

            if (!$warehouseId)
                jsonResponse(false, 'Depo seçimi zorunludur.');

            // Aktif oturum kontrolü
            $activeSession = Database::fetchOne("SELECT id FROM inventory_sessions WHERE warehouse_id = ? AND status = 'open'", [$warehouseId]);
            if ($activeSession)
                jsonResponse(false, 'Bu depo için zaten açık bir sayım oturumu mevcut.');

            $sessionId = Database::insert("INSERT INTO inventory_sessions (warehouse_id, created_by, notes) VALUES (?,?,?)", [
                $warehouseId,
                $_SESSION['user_id'] ?? 1, // Fallback if session missing
                $notes
            ]);

            jsonResponse(true, 'Sayım oturumu başlatıldı.', ['session_id' => $sessionId]);

        case 'get_product_by_barcode':
            $barcode = sanitize($_GET['barcode'] ?? '');
            if (!$barcode)
                jsonResponse(false, 'Barkod okutulmadı.');

            $product = Database::fetchOne("SELECT id, name, code, unit, image FROM tbl_dp_products WHERE (code = ? OR id = ?) AND hidden = 0", [$barcode, $barcode]);

            if (!$product)
                jsonResponse(false, 'Ürün bulunamadı.');

            jsonResponse(true, '', $product);

        case 'save_count':
            $sessionId = (int) ($_POST['session_id'] ?? 0);
            $productId = (int) ($_POST['product_id'] ?? 0);
            $qty = (float) ($_POST['qty'] ?? 0);
            $note = sanitize($_POST['note'] ?? '');

            if (!$sessionId || !$productId)
                jsonResponse(false, 'Eksik parametre.');

            // Oturum açık mı kontrol et
            $session = Database::fetchOne("SELECT warehouse_id FROM inventory_sessions WHERE id = ? AND status = 'open'", [$sessionId]);
            if (!$session)
                jsonResponse(false, 'Sayım oturumu kapalı veya bulunamadı.');

            // Beklenen stoku hesapla
            $expectedQty = getProductStock($productId, $session['warehouse_id']);
            $diff = $qty - $expectedQty;

            // Varsa güncelle yoksa ekle
            $existing = Database::fetchOne("SELECT id, counted_qty FROM inventory_items WHERE session_id = ? AND product_id = ?", [$sessionId, $productId]);

            if ($existing) {
                $newQty = $existing['counted_qty'] + $qty;
                $newDiff = $newQty - $expectedQty;
                Database::execute("UPDATE inventory_items SET counted_qty = ?, difference = ?, note = ?, counted_at = CURRENT_TIMESTAMP WHERE id = ?", [
                    $newQty,
                    $newDiff,
                    $note,
                    $existing['id']
                ]);
            } else {
                Database::insert("INSERT INTO inventory_items (session_id, product_id, expected_qty, counted_qty, difference, note) VALUES (?,?,?,?,?,?)", [
                    $sessionId,
                    $productId,
                    $expectedQty,
                    $qty,
                    $diff,
                    $note
                ]);
            }

            jsonResponse(true, 'Kayıt güncellendi.');

        case 'list_sessions':
            $rows = Database::fetchAll("
            SELECT s.*, 
            DATE_FORMAT(s.created_at, '%d.%m.%Y [%H:%i]') as created_at,
            DATE_FORMAT(s.closed_at, '%d.%m.%Y [%H:%i]') as closed_at,
            w.name as warehouse_name, u.name as creator_name,
            (SELECT COUNT(*) FROM inventory_items WHERE session_id = s.id) as item_count
            FROM inventory_sessions s
            JOIN tbl_dp_warehouses w ON w.id = s.warehouse_id
            LEFT JOIN tbl_dp_users u ON u.id = s.created_by
            ORDER BY s.id DESC
        ");
            jsonResponse(true, '', $rows);

        case 'get_session_details':
            $id = (int) ($_GET['id'] ?? 0);
            $items = Database::fetchAll("
            SELECT i.*, 
            DATE_FORMAT(i.counted_at, '%d.%m.%Y [%H:%i]') as counted_at,
            p.name as product_name, p.code as product_code, p.unit
            FROM inventory_items i
            JOIN tbl_dp_products p ON p.id = i.product_id
            WHERE i.session_id = ?
            ORDER BY i.id DESC
        ", [$id]);
            jsonResponse(true, '', $items);

        case 'close_session':
            $id = (int) ($_POST['id'] ?? 0);
            Database::execute("UPDATE inventory_sessions SET status = 'closed', closed_at = CURRENT_TIMESTAMP WHERE id = ?", [$id]);
            jsonResponse(true, 'Sayım oturumu kapatıldı.');
            break;

        case 'delete_inventory_item':
            $id = (int) ($_POST['id'] ?? 0);
            if (!$id)
                jsonResponse(false, 'ID eksik.');
            Database::execute("DELETE FROM inventory_items WHERE id = ?", [$id]);
            jsonResponse(true, 'Sayım satırı silindi.');
            break;

        case 'delete_session':
            $id = (int) ($_POST['id'] ?? 0);
            if (!$id)
                jsonResponse(false, 'ID eksik.');
            Database::execute("DELETE FROM inventory_sessions WHERE id = ?", [$id]);
            jsonResponse(true, 'Sayım oturumu silindi.');
            break;

        default:
            jsonResponse(false, 'Geçersiz işlem.');
    }
} catch (Exception $e) {
    jsonResponse(false, 'Hata: ' . $e->getMessage());
}
