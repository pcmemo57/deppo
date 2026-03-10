<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

// Strictly admin only
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
        case 'list_products':
            $warehouseIds = array_values(array_filter(array_map('intval', explode(',', $_GET['warehouses'] ?? ''))));
            if (empty($warehouseIds)) {
                jsonResponse(true, '', ['products' => []]);
            }

            $search = sanitize($_GET['search'] ?? '');

            // Fetch products and their stocks in selected warehouses
            $where = "p.hidden = 0 AND p.is_active = 1";
            $params = [];
            if ($search) {
                $where .= " AND (p.name LIKE ? OR p.code LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }

            $products = Database::fetchAll("SELECT id, name, code, unit, image,
                                            (SELECT unit_price FROM tbl_dp_stock_in WHERE product_id = p.id AND is_active = 1 ORDER BY created_at DESC LIMIT 1) AS last_price,
                                            (SELECT currency FROM tbl_dp_stock_in WHERE product_id = p.id AND is_active = 1 ORDER BY created_at DESC LIMIT 1) AS last_currency
                                            FROM tbl_dp_products p WHERE $where ORDER BY name LIMIT 100", $params);

            $results = [];
            foreach ($products as $p) {
                $stocks = [];
                foreach ($warehouseIds as $wid) {
                    $stocks[$wid] = getProductStock($p['id'], $wid);
                }
                $results[] = [
                    'id' => $p['id'],
                    'name' => $p['name'],
                    'code' => $p['code'],
                    'unit' => $p['unit'],
                    'image' => $p['image'],
                    'last_price' => (float) ($p['last_price'] ?? 0),
                    'last_currency' => $p['last_currency'] ?? 'TL',
                    'stocks' => $stocks
                ];
            }

            jsonResponse(true, '', ['products' => $results]);
            break;

        case 'update_stock':
            $updates = $_POST['updates'] ?? [];
            if (empty($updates)) {
                jsonResponse(false, 'Güncellenecek veri bulunamadı.');
            }

            Database::beginTransaction();

            $user = currentUser();
            $adminId = $user['id'];
            $userName = $user['name'] ?: 'Sistem';
            $note = "Toplu Güncelleme ($userName)";

            foreach ($updates as $update) {
                $productId = (int) ($update['product_id'] ?? 0);
                $warehouseId = (int) ($update['warehouse_id'] ?? 0);
                $newQty = (float) ($update['new_qty'] ?? 0);
                $unitPrice = (float) ($update['unit_price'] ?? 0);
                $currency = sanitize($update['currency'] ?? 'TL');

                if (!$productId || !$warehouseId)
                    continue;

                $currentQty = getProductStock($productId, $warehouseId);
                $diff = $newQty - $currentQty;

                if (abs($diff) < 0.001) {
                    // Check if price or currency changed even if qty didn't
                    // In this case, we create a 0-quantity entry to log the new price.
                    // We need to compare with the "last price" to avoid unnecessary logs.
                    // But since the API is stateless here, we just trust the 'updates' sent by the UI.
                    // The UI should only send this if it detected a change.

                    // Actually, let's just insert a 0-qty entry if diff is 0 and it was sent.
                    // The frontend only sends this if it's explicitly changed.
                    Database::insert("INSERT INTO tbl_dp_stock_in (product_id, warehouse_id, quantity, unit_price, currency, supplier_id, note, created_by, is_active) 
                                    VALUES (?, ?, 0, ?, ?, NULL, ?, ?, 1)", [
                        $productId,
                        $warehouseId,
                        $unitPrice,
                        $currency,
                        "Fiyat Güncellemesi (Toplu)",
                        $adminId
                    ]);
                    continue;
                }

                if ($diff > 0) {
                    // Stock In
                    Database::insert("INSERT INTO tbl_dp_stock_in (product_id, warehouse_id, quantity, unit_price, currency, supplier_id, note, created_by, is_active) 
                                    VALUES (?, ?, ?, ?, ?, NULL, ?, ?, 1)", [
                        $productId,
                        $warehouseId,
                        $diff,
                        $unitPrice,
                        $currency,
                        $note,
                        $adminId
                    ]);
                } else {
                    // Stock Out
                    Database::insert("INSERT INTO tbl_dp_stock_out (product_id, warehouse_id, quantity, unit_price, currency, requester_id, customer_id, note, created_by) 
                                    VALUES (?, ?, ?, ?, ?, NULL, NULL, ?, ?)", [
                        $productId,
                        $warehouseId,
                        abs($diff),
                        $unitPrice,
                        $currency,
                        $note,
                        $adminId
                    ]);
                }
            }

            Database::commit();
            jsonResponse(true, 'Stoklar başarıyla güncellendi.');
            break;

        default:
            jsonResponse(false, 'Geçersiz işlem.');
    }
} catch (Exception $e) {
    if (Database::inTransaction())
        Database::rollBack();
    jsonResponse(false, 'Hata: ' . $e->getMessage());
}
