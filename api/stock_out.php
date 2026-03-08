<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireRole(ROLE_ADMIN, ROLE_USER, ROLE_REQUESTER);
header('Content-Type: application/json; charset=utf-8');

// CSRF check for write actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($token)) {
        jsonResponse(false, 'Geçersiz CSRF token. Lütfen sayfayı yenileyin.');
    }
}

$action = sanitize($_POST['action'] ?? $_GET['action'] ?? '');

switch ($action) {
    case 'get':
        $id = (int) ($_GET['id'] ?? 0);
        $row = Database::fetchOne(
            "SELECT so.*, p.name AS product_name, p.unit, w.name AS warehouse_name,
                    r.name AS requester_name, r.surname AS requester_surname, c.name AS customer_name
             FROM tbl_dp_stock_out so
             JOIN tbl_dp_products p ON p.id=so.product_id
             LEFT JOIN tbl_dp_warehouses w ON w.id=so.warehouse_id
             LEFT JOIN tbl_dp_requesters r ON r.id=so.requester_id
             LEFT JOIN tbl_dp_customers c ON c.id=so.customer_id
             WHERE so.id=?",
            [$id]
        );
        if (!$row)
            jsonResponse(false, 'Kayıt bulunamadı.');
        jsonResponse(true, '', $row);

    case 'add':
        $warehouseId = (int) ($_POST['warehouse_id'] ?? 0) ?: null;
        $requesterId = (int) ($_POST['requester_id'] ?? 0) ?: null;
        $customerId = (int) ($_POST['customer_id'] ?? 0) ?: null;
        $note = sanitize($_POST['note'] ?? '');
        $linesJson = $_POST['lines'] ?? '[]';
        $lines = json_decode($linesJson, true);

        if (hasRole(ROLE_ADMIN, ROLE_USER) && !$warehouseId)
            jsonResponse(false, 'Depo seçimi zorunludur.');
        if (empty($lines))
            jsonResponse(false, 'En az 1 ürün gereklidir.');

        // Sayım kontrolü
        if ($warehouseId && isInventoryOpen($warehouseId)) {
            jsonResponse(false, 'Bu depo için açık bir sayım oturumu bulunmaktadır. Sayım bitmeden stok hareketi yapılamaz.');
        }

        $currentUser = currentUser();
        $userId = $currentUser['id'];
        $userName = $currentUser['name'];
        $batchId = 'SO-' . date('YmdHis') . '-' . rand(1000, 9999);
        $orderNo = (int) Database::fetchOne("SELECT MAX(order_no) as max_no FROM tbl_dp_stock_out")['max_no'] + 1;

        Database::beginTransaction();
        try {
            foreach ($lines as $line) {
                $productId = (int) ($line['product_id'] ?? 0);
                $quantity = (float) ($line['quantity'] ?? 0);
                $unitPrice = (float) ($line['unit_price'] ?? 0);
                $totalPrice = (float) ($line['total'] ?? 0);

                if (!$productId || $quantity <= 0)
                    continue;

                $currency = sanitize($line['currency'] ?? 'EUR');

                // If the user matches the requester being recorded, OR if we want to be strict and say ALL requesters need approval:
                // Actually, the user's requirement is: "Talep eden leri de login olup onaylanmayı bekleyecek şekilde depo çıkış kaydı oluşturmalarına izin ver"
                // So if ROLE_REQUESTER is adding, status=0. If Admin/User is adding, status=1 (approved).
                $status = (hasRole(ROLE_ADMIN, ROLE_USER)) ? 1 : 0;

                Database::insert(
                    "INSERT INTO tbl_dp_stock_out (batch_id, order_no, warehouse_id, requester_id, customer_id, product_id, quantity, currency, unit_price, total_price, note, created_by, created_by_name, status)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                    [$batchId, $orderNo, $warehouseId, $requesterId, $customerId, $productId, $quantity, $currency, $unitPrice, $totalPrice, $note, $userId, $userName, $status]
                );
            }
            Database::commit();
        } catch (Exception $e) {
            Database::rollBack();
            jsonResponse(false, 'Kaydetme sırasında bir hata oluştu: ' . $e->getMessage());
        }

        // --- E-posta Bildirimi ---
        if ($requesterId) {
            $requester = Database::fetchOne("SELECT name, surname, email FROM tbl_dp_requesters WHERE id=?", [$requesterId]);
            if ($requester && !empty($requester['email'])) {
                $warehouseName = '— (Atanmadı)';
                if ($warehouseId) {
                    $warehouse = Database::fetchOne("SELECT name FROM tbl_dp_warehouses WHERE id=?", [$warehouseId]);
                    $warehouseName = $warehouse['name'] ?? '—';
                }

                $items = Database::fetchAll(
                    "SELECT so.*, p.name AS product_name, p.unit 
                     FROM tbl_dp_stock_out so
                     JOIN tbl_dp_products p ON p.id=so.product_id
                     WHERE so.batch_id=?",
                    [$batchId]
                );

                $customerName = '—';
                if ($customerId) {
                    $customer = Database::fetchOne("SELECT name FROM tbl_dp_customers WHERE id=?", [$customerId]);
                    $customerName = $customer['name'] ?? '—';
                }

                if (!empty($items)) {
                    $totalEur = 0;
                    $tableRows = "";
                    foreach ($items as $item) {
                        $totalEur += (float) $item['total_price'];
                        $tableRows .= "
                            <tr style='border-bottom: 1px solid #eee;'>
                                <td style='padding: 10px; border: 1px solid #e5e7eb;'>" . e($item['product_name']) . "</td>
                                <td style='padding: 10px; border: 1px solid #e5e7eb; text-align: right;'>" . formatQty($item['quantity']) . " " . e($item['unit']) . "</td>
                                <td style='padding: 10px; border: 1px solid #e5e7eb; text-align: right;'>" . number_format($item['unit_price'], 2, ',', '.') . " " . getCurrencySymbol() . "</td>
                                <td style='padding: 10px; border: 1px solid #e5e7eb; text-align: right; font-weight: bold;'>" . number_format($item['total_price'], 2, ',', '.') . " " . getCurrencySymbol() . "</td>
                            </tr>
                        ";
                    }

                    $subject = "📦 Stok Çıkışı Bilgilendirmesi: " . e($batchId);
                    $warehouseRow = $warehouseId ? "<p style='margin: 0; font-size: 14px;'><strong>Depo:</strong> " . e($warehouseName) . "</p>" : "";

                    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                    $host = $_SERVER['HTTP_HOST'];
                    $directUrl = $protocol . '://' . $host . BASE_URL . '/?page=stock_out_pending&batch_id=' . urlencode($batchId);

                    $commonHeader = "
                        <div style='background-color: #343a40; color: #ffffff; padding: 20px; text-align: center;'>
                            <h2 style='margin: 0;'>" . e(get_setting('site_name', APP_NAME)) . "</h2>
                        </div>
                    ";

                    $commonDetails = "
                        <div style='margin: 20px 0; padding: 15px; background-color: #f8fafc; border-left: 4px solid #3b82f6;'>
                            $warehouseRow
                            <p style='margin: 5px 0 0 0; font-size: 14px;'><strong>Müşteri:</strong> " . e($customerName) . "</p>
                            <p style='margin: 5px 0 0 0; font-size: 14px;'><strong>Sipariş No:</strong> " . e($orderNo) . "</p>
                            <p style='margin: 5px 0 0 0; font-size: 14px;'><strong>Not:</strong> " . ($note ? e($note) : '—') . "</p>
                        </div>
                        <table style='width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px;'>
                            <thead>
                                <tr style='background-color: #f1f5f9;'>
                                    <th style='padding: 12px; border: 1px solid #e5e7eb; text-align: left;'>Ürün</th>
                                    <th style='padding: 12px; border: 1px solid #e5e7eb; text-align: right; width: 60px;'>Miktar</th>
                                    <th style='padding: 12px; border: 1px solid #e5e7eb; text-align: right; width: 80px;'>Birim Fiyat</th>
                                    <th style='padding: 12px; border: 1px solid #e5e7eb; text-align: right; width: 90px;'>Toplam</th>
                                </tr>
                            </thead>
                            <tbody>$tableRows</tbody>
                            <tfoot>
                                <tr style='background-color: #f8fafc;'>
                                    <td colspan='3' style='padding: 12px; border: 1px solid #e5e7eb; text-align: right; font-weight: bold;'>GENEL TOPLAM</td>
                                    <td style='padding: 12px; border: 1px solid #e5e7eb; text-align: right; font-weight: bold; color: #1e40af; font-size: 16px;'>" . number_format($totalEur, 2, ',', '.') . " " . getCurrencySymbol() . "</td>
                                </tr>
                            </tfoot>
                        </table>
                    ";

                    $commonFooter = "
                        <div style='background-color: #f3f4f6; color: #9ca3af; padding: 15px; text-align: center; font-size: 12px; margin-top:30px;'>
                            Bu e-posta otomatik olarak gönderilmiştir. Lütfen yanıtlamayınız.
                        </div>
                    ";

                    // --- Talep Edene Giden Mail (Butonsuz) ---
                    $requesterBody = "
                        <div style='font-family: sans-serif; max-width: 650px; margin: 0 auto; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;'>
                            $commonHeader
                            <div style='padding: 30px;'>
                                <p style='font-size: 16px; color: #374151;'>Sayın <strong>{$requester['name']} {$requester['surname']}</strong>,</p>
                                <p style='color: #6b7280; line-height: 1.5;'>Talebiniz başarıyla oluşturuldu ve onaya gönderildi.</p>
                                $commonDetails
                            </div>
                            $commonFooter
                        </div>
                    ";
                    send_mail($requester['email'], $subject, $requesterBody);

                    // --- Program Yöneticisine Bildirim (Butonlu) ---
                    $pmEmail = get_setting('program_manager_email');
                    if (!empty($pmEmail)) {
                        $pmSubject = "Yeni Ürün Talebi: " . ($requester['name'] ?? '') . " " . ($requester['surname'] ?? '');
                        $pmGreeting = "<strong>" . e($requester['name'] . " " . $requester['surname']) . "</strong> tarafından yeni bir ürün talebi oluşturuldu.";

                        $pmBody = "
                            <div style='font-family: sans-serif; max-width: 650px; margin: 0 auto; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;'>
                                $commonHeader
                                <div style='padding: 30px;'>
                                    <p style='font-size: 16px; color: #374151;'>$pmGreeting</p>
                                    $commonDetails
                                    <div style='margin-top: 30px; text-align: center;'>
                                        <a href='$directUrl' style='display: inline-block; padding: 12px 24px; background-color: #1a56db; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 15px; box-shadow: 0 4px 6px rgba(26, 86, 219, 0.2);'>
                                            📦 Talebi Görüntüle ve Onayla
                                        </a>
                                    </div>
                                </div>
                                $commonFooter
                            </div>
                        ";
                        send_mail($pmEmail, $pmSubject, $pmBody);
                    }
                }
            }
        }

        // Get info for printing if approved
        $printData = null;
        if (hasRole(ROLE_ADMIN, ROLE_USER)) {
            $customer = Database::fetchOne("SELECT name, address, phone, email FROM tbl_dp_customers WHERE id = ?", [$customerId]);
            $printData = [
                'sender' => get_setting('kargo_gonderici', ''),
                'customer_name' => $customer['name'] ?? '—',
                'customer_address' => $customer['address'] ?? '—',
                'customer_phone' => $customer['phone'] ?? '—',
                'customer_email' => $customer['email'] ?? '—'
            ];
        }

        jsonResponse(true, 'Çıkış kaydedildi.', ['print_data' => $printData]);

    case 'save_batch':
        $batchId = sanitize($_POST['batch_id'] ?? '');
        if (!$batchId)
            jsonResponse(false, 'Batch ID eksik.');

        $warehouseId = (int) ($_POST['warehouse_id'] ?? 0) ?: null;
        $requesterId = (int) ($_POST['requester_id'] ?? 0) ?: null;
        $customerId = (int) ($_POST['customer_id'] ?? 0) ?: null;
        $note = sanitize($_POST['note'] ?? '');
        $linesJson = $_POST['lines'] ?? '[]';
        $lines = json_decode($linesJson, true);

        if (hasRole(ROLE_ADMIN, ROLE_USER) && !$warehouseId)
            jsonResponse(false, 'Depo seçimi zorunludur.');
        if (empty($lines))
            jsonResponse(false, 'En az 1 ürün gereklidir.');

        // Sayım kontrolü
        if ($warehouseId && isInventoryOpen($warehouseId)) {
            jsonResponse(false, 'Bu depo için açık bir sayım oturumu bulunmaktadır. Sayım bitmeden stok hareketi yapılamaz.');
        }

        // Get the original order_no to preserve it
        $originalOrder = Database::fetchOne("SELECT order_no, created_at, created_by, requester_id, status, created_by_name FROM tbl_dp_stock_out WHERE batch_id=? LIMIT 1", [$batchId]);
        if (!$originalOrder)
            jsonResponse(false, 'Orijinal sipariş bulunamadı.');

        if (hasRole(ROLE_REQUESTER) && $originalOrder['requester_id'] != $_SESSION['dp_user_id']) {
            jsonResponse(false, 'Bu kaydı düzenleme yetkiniz yok.');
        }

        if ($originalOrder['status'] != 0 && hasRole(ROLE_REQUESTER)) {
            jsonResponse(false, 'Onaylanmış veya reddedilmiş talepler düzenlenemez.');
        }

        $orderNo = $originalOrder['order_no'];
        $createdAt = $originalOrder['created_at'];
        $createdBy = $originalOrder['created_by'];
        $createdByName = $originalOrder['created_by_name'];

        $currentUser = currentUser();
        $updatedBy = $currentUser['id'];
        $updatedByName = $currentUser['name'];

        Database::beginTransaction();
        try {
            // Remove existing items for this batch
            Database::execute("DELETE FROM tbl_dp_stock_out WHERE batch_id=?", [$batchId]);

            // Insert new items
            foreach ($lines as $line) {
                $productId = (int) ($line['product_id'] ?? 0);
                $quantity = (float) ($line['quantity'] ?? 0);
                $unitPrice = (float) ($line['unit_price'] ?? 0);
                $totalPrice = (float) ($line['total'] ?? 0);
                $currency = sanitize($line['currency'] ?? 'EUR');

                if (!$productId || $quantity <= 0)
                    continue;

                $status = (hasRole(ROLE_ADMIN, ROLE_USER)) ? 1 : 0;

                Database::insert(
                    "INSERT INTO tbl_dp_stock_out (batch_id, order_no, warehouse_id, requester_id, customer_id, product_id, quantity, currency, unit_price, total_price, note, created_at, created_by, created_by_name, updated_by, updated_by_name, status)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                    [$batchId, $orderNo, $warehouseId, $requesterId, $customerId, $productId, $quantity, $currency, $unitPrice, $totalPrice, $note, $createdAt, $createdBy, $createdByName, $updatedBy, $updatedByName, $status]
                );
            }
            Database::commit();

            // Get info for printing if approved
            $printData = null;
            if (hasRole(ROLE_ADMIN, ROLE_USER)) {
                $customer = Database::fetchOne("SELECT name, address, phone, email FROM tbl_dp_customers WHERE id = ?", [$customerId]);
                $printData = [
                    'sender' => get_setting('kargo_gonderici', ''),
                    'customer_name' => $customer['name'] ?? '—',
                    'customer_address' => $customer['address'] ?? '—',
                    'customer_phone' => $customer['phone'] ?? '—',
                    'customer_email' => $customer['email'] ?? '—'
                ];
            }

            jsonResponse(true, 'Sipariş başarıyla güncellendi.', ['print_data' => $printData]);
        } catch (Exception $e) {
            Database::rollBack();
            jsonResponse(false, 'Güncelleme sırasında bir hata oluştu: ' . $e->getMessage());
        }

    case 'edit':
        $id = (int) ($_POST['id'] ?? 0);
        $warehouseId = (int) ($_POST['warehouse_id'] ?? 0) ?: null;
        $requesterId = (int) ($_POST['requester_id'] ?? 0) ?: null;
        $customerId = (int) ($_POST['customer_id'] ?? 0) ?: null;
        $note = sanitize($_POST['note'] ?? '');

        // Düzenlemede modal yapısı gereği lines içindeki İLK ürünü alıyoruz (çünkü liste tablosundaki her satır tek ürünü temsil eder)
        $linesJson = $_POST['lines'] ?? '[]';
        $linesArr = json_decode($linesJson, true);
        $line = $linesArr[0] ?? null;

        if (!$id)
            jsonResponse(false, 'ID geçersiz.');
        if (hasRole(ROLE_ADMIN, ROLE_USER) && !$warehouseId)
            jsonResponse(false, 'Depo seçimi zorunludur.');
        if (!$line)
            jsonResponse(false, 'Ürün bilgisi eksik.');

        // Sayım kontrolü
        if ($warehouseId && isInventoryOpen($warehouseId)) {
            jsonResponse(false, 'Bu depo için açık bir sayım oturumu bulunmaktadır. Sayım bitmeden stok hareketi yapılamaz.');
        }

        $productId = (int) ($line['product_id'] ?? 0);
        $quantity = (float) ($line['quantity'] ?? 0);
        $unitPrice = (float) ($line['unit_price'] ?? 0);
        $totalPrice = (float) ($line['total'] ?? 0);

        $currentUser = currentUser();
        $userId = $currentUser['id'];
        $userName = $currentUser['name'];

        Database::execute(
            "UPDATE tbl_dp_stock_out SET 
                warehouse_id=?, requester_id=?, customer_id=?, product_id=?, 
                quantity=?, unit_price=?, total_price=?, note=?, updated_by=?, updated_by_name=? 
            WHERE id=?",
            [$warehouseId, $requesterId, $customerId, $productId, $quantity, $unitPrice, $totalPrice, $note, $userId, $userName, $id]
        );
        jsonResponse(true, 'Çıkış kaydı güncellendi.');

    case 'approve':
        requireRole(ROLE_ADMIN, ROLE_USER);
        $batchId = sanitize($_POST['batch_id'] ?? '');
        if (!$batchId)
            jsonResponse(false, 'Batch ID eksik.');
        Database::execute("UPDATE tbl_dp_stock_out SET status=1 WHERE batch_id=?", [$batchId]);

        // Get info for printing
        $info = Database::fetchOne(
            "SELECT c.name as customer_name, c.address, c.phone, c.email
             FROM tbl_dp_stock_out so
             LEFT JOIN tbl_dp_customers c ON c.id = so.customer_id
             WHERE so.batch_id = ? LIMIT 1",
            [$batchId]
        );

        jsonResponse(true, 'Kayıt onaylandı.', [
            'print_data' => [
                'sender' => get_setting('kargo_gonderici', ''),
                'customer_name' => $info['customer_name'] ?? '—',
                'customer_address' => $info['address'] ?? '—',
                'customer_phone' => $info['phone'] ?? '—',
                'customer_email' => $info['email'] ?? '—'
            ]
        ]);

    case 'reject':
        requireRole(ROLE_ADMIN, ROLE_USER);
        $batchId = sanitize($_POST['batch_id'] ?? '');
        if (!$batchId)
            jsonResponse(false, 'Batch ID eksik.');
        Database::execute("UPDATE tbl_dp_stock_out SET status=2 WHERE batch_id=?", [$batchId]);
        jsonResponse(true, 'Kayıt reddedildi.');

    case 'get_last_price':
        $productId = (int) ($_GET['product_id'] ?? 0);
        $warehouseId = (int) ($_GET['warehouse_id'] ?? 0);

        // Önce aynı depodaki son girişe bak, yoksa herhangi bir depodan al
        $row = null;
        if ($warehouseId) {
            $row = Database::fetchOne(
                "SELECT unit_price, currency FROM tbl_dp_stock_in WHERE product_id=? AND warehouse_id=? ORDER BY created_at DESC LIMIT 1",
                [$productId, $warehouseId]
            );
        }
        if (!$row) {
            $row = Database::fetchOne(
                "SELECT unit_price, currency FROM tbl_dp_stock_in WHERE product_id=? ORDER BY created_at DESC LIMIT 1",
                [$productId]
            );
        }

        if ($row) {
            $row['price_eur'] = toBaseCurrencyDisplay($row['unit_price'], $row['currency']);
        }

        jsonResponse(true, '', $row ?: ['unit_price' => 0, 'currency' => 'EUR', 'price_eur' => 0]);

    case 'recent':
        $rows = Database::fetchAll(
            "SELECT so.id, p.name AS product, w.name AS warehouse, so.quantity, p.unit, so.total_price, so.created_by_name,
                    DATE_FORMAT(so.created_at,'%d.%m.%Y') AS created_at
             FROM tbl_dp_stock_out so
             JOIN tbl_dp_products p ON p.id=so.product_id
             LEFT JOIN tbl_dp_warehouses w ON w.id=so.warehouse_id
             ORDER BY so.created_at DESC LIMIT 15"
        );
        jsonResponse(true, '', $rows);

    case 'pending_count':
        // Only Admins and Users can see pending counts for all
        $where = "so.status = 0";
        $params = [];
        if (hasRole(ROLE_REQUESTER)) {
            $where .= " AND so.requester_id = ?";
            $params[] = $_SESSION['dp_user_id'];
        }
        $count = Database::fetchOne("SELECT COUNT(DISTINCT so.batch_id) as c FROM tbl_dp_stock_out so WHERE $where", $params)['c'] ?? 0;
        jsonResponse(true, '', ['count' => (int) $count]);

    case 'list':
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(100, max(5, (int) ($_GET['per_page'] ?? 10)));
        $search = sanitize($_GET['search'] ?? '');
        $startDate = sanitize($_GET['start_date'] ?? '');
        $endDate = sanitize($_GET['end_date'] ?? '');
        $offset = ($page - 1) * $perPage;
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $where = "1=1";
        $params = [];
        if ($status !== '') {
            $where .= " AND so.status = ?";
            $params[] = (int) $status;
        }
        if ($search) {
            $where .= " AND (p.name LIKE ? OR w.name LIKE ? OR c.name LIKE ? OR r.name LIKE ? OR r.surname LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if (hasRole(ROLE_REQUESTER)) {
            $where .= " AND so.requester_id = ?";
            $params[] = $_SESSION['dp_user_id'];
        }

        if ($startDate) {
            $where .= " AND DATE(so.created_at) >= ?";
            $params[] = $startDate;
        }
        if ($endDate) {
            $where .= " AND DATE(so.created_at) <= ?";
            $params[] = $endDate;
        }
        $total = Database::fetchOne(
            "SELECT COUNT(*) AS c FROM tbl_dp_stock_out so
             JOIN tbl_dp_products p ON p.id=so.product_id
             LEFT JOIN tbl_dp_warehouses w ON w.id=so.warehouse_id 
             LEFT JOIN tbl_dp_requesters r ON r.id=so.requester_id
             LEFT JOIN tbl_dp_customers c ON c.id=so.customer_id
             WHERE $where",
            $params
        )['c'] ?? 0;
        $rows = Database::fetchAll(
            "SELECT so.id, so.batch_id, so.order_no, p.name AS product, p.unit, w.name AS warehouse,
                    r.name AS requester_name, r.surname AS requester_surname,
                    c.name AS customer, so.quantity, so.unit_price, so.total_price, so.note,
                    so.created_by_name, so.updated_by_name, so.status,
                    DATE_FORMAT(so.created_at,'%d.%m.%Y') AS created_at
             FROM tbl_dp_stock_out so
             JOIN tbl_dp_products p ON p.id=so.product_id
             LEFT JOIN tbl_dp_warehouses w ON w.id=so.warehouse_id
             LEFT JOIN tbl_dp_requesters r ON r.id=so.requester_id
             LEFT JOIN tbl_dp_customers c ON c.id=so.customer_id
             WHERE $where ORDER BY so.created_at DESC LIMIT $perPage OFFSET $offset",
            $params
        );
        foreach ($rows as &$r) {
            // For old records without currency, assume it was in the base currency at the time (simplified)
            // or just use the stored total_price as is.
            // But let's try to be as dynamic as possible.
            $r['total_price'] = toBaseCurrencyDisplay($r['total_price'], $r['currency'] ?: 'EUR');
            $r['unit_price'] = toBaseCurrencyDisplay($r['unit_price'], $r['currency'] ?: 'EUR');
        }
        jsonResponse(true, '', ['data' => $rows, 'total' => (int) $total]);

    case 'list_grouped':
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(100, max(5, (int) ($_GET['per_page'] ?? 10)));
        $search = sanitize($_GET['search'] ?? '');
        $offset = ($page - 1) * $perPage;
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $where = "so.batch_id IS NOT NULL";
        $params = [];

        if ($status !== '') {
            $where .= " AND so.status = ?";
            $params[] = (int) $status;
        }
        if ($search) {
            $where .= " AND (c.name LIKE ? OR w.name LIKE ? OR so.note LIKE ? OR EXISTS (
                SELECT 1 FROM tbl_dp_stock_out so2
                JOIN tbl_dp_products p ON p.id = so2.product_id
                WHERE so2.batch_id = so.batch_id AND p.name LIKE ?
            ))";
            $params = ["%$search%", "%$search%", "%$search%", "%$search%"];
        }

        if (hasRole(ROLE_REQUESTER)) {
            $where .= " AND so.requester_id = ?";
            $params[] = $_SESSION['dp_user_id'];
        }

        $total = Database::fetchOne(
            "SELECT COUNT(DISTINCT so.batch_id) AS c 
             FROM tbl_dp_stock_out so
             LEFT JOIN tbl_dp_customers c ON c.id=so.customer_id
             LEFT JOIN tbl_dp_warehouses w ON w.id=so.warehouse_id
             WHERE $where",
            $params
        )['c'] ?? 0;

        $rows = Database::fetchAll(
            "SELECT so.batch_id, so.order_no, MAX(so.customer_id) AS customer_id, c.name AS customer_name, w.name AS warehouse_name,
                    COUNT(so.id) AS item_count, SUM(so.total_price) AS total_eur,
                    MAX(so.created_at) AS created_at, so.note, MAX(so.created_by_name) AS created_by_name
             FROM tbl_dp_stock_out so
             LEFT JOIN tbl_dp_customers c ON c.id=so.customer_id
             LEFT JOIN tbl_dp_warehouses w ON w.id=so.warehouse_id
             WHERE $where 
             GROUP BY so.batch_id, so.order_no
             ORDER BY created_at DESC LIMIT $perPage OFFSET $offset",
            $params
        );

        // Dynamic conversion for grouped total & status
        foreach ($rows as &$r) {
            $batchItems = Database::fetchAll("SELECT total_price, currency, status FROM tbl_dp_stock_out WHERE batch_id=?", [$r['batch_id']]);
            $totalInBase = 0;
            $statusCounts = [0 => 0, 1 => 0, 2 => 0];
            foreach ($batchItems as $item) {
                $totalInBase += toBaseCurrencyDisplay($item['total_price'], $item['currency'] ?: 'EUR');
                $statusCounts[(int) $item['status']]++;
            }
            $r['total_eur'] = $totalInBase;
            $r['created_at_fmt'] = date('d.m.Y', strtotime($r['created_at']));

            // Determine aggregate status (if any pending, show pending)
            if ($statusCounts[0] > 0)
                $r['status'] = 0;
            elseif ($statusCounts[2] > 0)
                $r['status'] = 2;
            else
                $r['status'] = 1;
        }

        jsonResponse(true, '', ['data' => $rows, 'total' => (int) $total]);

    case 'get_batch':
        $batchId = sanitize($_GET['batch_id'] ?? '');
        if (!$batchId)
            jsonResponse(false, 'Batch ID eksik.');

        $items = Database::fetchAll(
            "SELECT so.*, p.name AS product_name, p.unit, w.name AS warehouse_name,
                    c.name AS customer_name, c.address AS customer_address, c.phone AS customer_phone,
                    r.name AS requester_name, r.surname AS requester_surname
             FROM tbl_dp_stock_out so
             JOIN tbl_dp_products p ON p.id=so.product_id
             LEFT JOIN tbl_dp_warehouses w ON w.id=so.warehouse_id
             LEFT JOIN tbl_dp_customers c ON c.id=so.customer_id
             LEFT JOIN tbl_dp_requesters r ON r.id=so.requester_id
             WHERE so.batch_id=?",
            [$batchId]
        );
        $totalBase = 0;
        foreach ($items as &$item) {
            $item['total_price_orig'] = $item['total_price'];
            $item['unit_price_orig'] = $item['unit_price'];
            $item['total_price'] = toBaseCurrencyDisplay($item['total_price'], $item['currency'] ?: 'EUR');
            $item['unit_price'] = toBaseCurrencyDisplay($item['unit_price'], $item['currency'] ?: 'EUR');
            $totalBase += (float) $item['total_price'];
        }
        $first = $items[0] ?? null;
        jsonResponse(true, '', [
            'items' => $items,
            'data_total_eur' => $totalBase,
            'sender_info' => get_setting('kargo_gonderici', ''),
            'customer_id' => $first['customer_id'] ?? null,
            'customer_name' => $first['customer_name'] ?? '—',
            'customer_address' => $first['customer_address'] ?? '—',
            'customer_phone' => $first['customer_phone'] ?? '—'
        ]);

    case 'export_excel':
        $search = sanitize($_GET['search'] ?? '');
        $startDate = sanitize($_GET['start_date'] ?? '');
        $endDate = sanitize($_GET['end_date'] ?? '');
        $where = "1=1";
        $params = [];

        if ($search) {
            $where .= " AND (p.name LIKE ? OR w.name LIKE ? OR c.name LIKE ? OR r.name LIKE ? OR r.surname LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if (hasRole(ROLE_REQUESTER)) {
            $where .= " AND so.requester_id = ?";
            $params[] = $_SESSION['dp_user_id'];
        }

        if ($startDate) {
            $where .= " AND DATE(so.created_at) >= ?";
            $params[] = $startDate;
        }
        if ($endDate) {
            $where .= " AND DATE(so.created_at) <= ?";
            $params[] = $endDate;
        }

        $rows = Database::fetchAll(
            "SELECT so.id, p.name AS product, p.unit, w.name AS warehouse,
                    CONCAT(COALESCE(r.name,''), ' ', COALESCE(r.surname,'')) AS requester,
                    c.name AS customer, so.quantity, so.unit_price, so.total_price, so.currency, so.note,
                    so.created_by_name, so.created_at
             FROM tbl_dp_stock_out so
             JOIN tbl_dp_products p ON p.id=so.product_id
             LEFT JOIN tbl_dp_warehouses w ON w.id=so.warehouse_id
             LEFT JOIN tbl_dp_requesters r ON r.id=so.requester_id
             LEFT JOIN tbl_dp_customers c ON c.id=so.customer_id
             WHERE $where ORDER BY so.created_at DESC",
            $params
        );

        $filename = "stok_cikis_" . date('Ymd_His') . ".csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');
        // UTF-8 BOM for Excel
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Stylish Header Rows
        fputcsv($output, ['DEPODAN ÇIKIŞ RAPORU'], ';');
        fputcsv($output, ['Rapor Tarihi:', date('d.m.Y H:i')], ';');

        $filterStr = [];
        if ($search)
            $filterStr[] = "Arama: $search";
        if ($startDate)
            $filterStr[] = "Başlangıç: $startDate";
        if ($endDate)
            $filterStr[] = "Bitiş: $endDate";
        fputcsv($output, ['Uygulanan Filtreler:', !empty($filterStr) ? implode(' | ', $filterStr) : 'Yok'], ';');

        fputcsv($output, [], ';'); // Empty row

        // Column Headers
        $headers = ['Sipariş No', 'İşlem No', 'Ürün', 'Birim', 'Depo', 'Talep Eden', 'Müşteri', 'Miktar', 'Birim Fiyat', 'Toplam Tutar', 'Para Birimi', 'Not', 'İşlemi Yapan', 'Tarih'];
        $isRequester = hasRole(ROLE_REQUESTER);
        if ($isRequester) {
            unset($headers[4]); // Remove 'Depo'
        }
        fputcsv($output, array_values($headers), ';');

        foreach ($rows as $r) {
            $rowData = [
                $r['order_no'] ?? '',
                $r['batch_id'] ?? '',
                $r['product'],
                $r['unit'],
                $r['warehouse'] ?? '— (Atanmadı)',
                trim($r['requester']),
                $r['customer'],
                $r['quantity'],
                number_format((float) $r['unit_price'], 4, ',', ''),
                number_format((float) $r['total_price'], 2, ',', ''),
                $r['currency'] ?: 'EUR',
                $r['note'],
                $r['created_by_name'],
                date('d.m.Y H:i', strtotime($r['created_at']))
            ];
            if ($isRequester) {
                unset($rowData[4]); // Remove 'warehouse'
            }
            fputcsv($output, array_values($rowData), ';');
        }
        fclose($output);
        exit;

    default:
        jsonResponse(false, 'Geçersiz işlem.');
}