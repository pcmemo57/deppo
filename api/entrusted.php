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
        jsonResponse(false, 'Güvenlik doğrulaması başarısız (CSRF). Lütfen sayfayı yenileyiniz.');
    }
}

$action = sanitize($_POST['action'] ?? $_GET['action'] ?? '');

switch ($action) {
    case 'add':
        $warehouseId = (int) ($_POST['warehouse_id'] ?? 0);
        $requesterId = (int) ($_POST['requester_id'] ?? 0) ?: null;
        $expectedReturn = sanitize($_POST['expected_return_at'] ?? '') ?: null;
        $note = sanitize($_POST['note'] ?? '');
        $linesJson = $_POST['lines'] ?? '[]';
        $lines = json_decode($linesJson, true);

        if (!$warehouseId)
            jsonResponse(false, 'Depo seçimi zorunludur.');
        if (empty($lines))
            jsonResponse(false, 'En az 1 ürün gereklidir.');

        $currentUser = currentUser();
        $userId = $currentUser['id'];
        $userName = $currentUser['name'];
        $batchId = 'ENT-' . date('YmdHis') . '-' . rand(1000, 9999);

        Database::beginTransaction();
        try {
            foreach ($lines as $line) {
                $productId = (int) ($line['product_id'] ?? 0);
                $quantity = (float) ($line['quantity'] ?? 0);

                if (!$productId || $quantity <= 0)
                    continue;

                // 1. Create Entrustment record
                Database::insert(
                    "INSERT INTO tbl_dp_entrusted (batch_id, warehouse_id, requester_id, product_id, quantity, remaining_quantity, expected_return_at, note, created_by, created_by_name)
                     VALUES (?,?,?,?,?,?,?,?,?,?)",
                    [$batchId, $warehouseId, $requesterId, $productId, $quantity, $quantity, $expectedReturn, $note, $userId, $userName]
                );

                // 2. [NEW] Create Stock Out record (Deduct from stock)
                $priceData = Database::fetchOne("SELECT unit_price, currency FROM tbl_dp_stock_in WHERE product_id=? ORDER BY created_at DESC LIMIT 1", [$productId]);
                $unitPrice = $priceData ? (float) $priceData['unit_price'] : 0;
                $currency = $priceData ? $priceData['currency'] : 'EUR';
                $totalPrice = $unitPrice * $quantity;

                Database::insert(
                    "INSERT INTO tbl_dp_stock_out (batch_id, warehouse_id, requester_id, product_id, quantity, currency, unit_price, total_price, note, created_by, created_by_name)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?)",
                    ['SO-' . $batchId, $warehouseId, $requesterId, $productId, $quantity, $currency, $unitPrice, $totalPrice, "Emanet Çıkışı: " . $note, $userId, $userName]
                );
            }
            Database::commit();
        } catch (Exception $e) {
            Database::rollBack();
            jsonResponse(false, 'Kaydetme hatası: ' . $e->getMessage());
        }

        // --- E-posta Bildirimi ---
        if ($requesterId) {
            $requester = Database::fetchOne("SELECT name, surname, email FROM tbl_dp_requesters WHERE id=?", [$requesterId]);
            if ($requester && !empty($requester['email'])) {
                $warehouse = Database::fetchOne("SELECT name FROM tbl_dp_warehouses WHERE id=?", [$warehouseId]);
                $warehouseName = $warehouse['name'] ?? '—';

                $items = Database::fetchAll(
                    "SELECT e.*, p.name AS product_name, p.unit, so.unit_price, so.total_price, so.currency
                     FROM tbl_dp_entrusted e
                     JOIN tbl_dp_products p ON p.id=e.product_id
                     JOIN tbl_dp_stock_out so ON so.batch_id = CONCAT('SO-', e.batch_id) AND so.product_id = e.product_id
                     WHERE e.batch_id=?",
                    [$batchId]
                );

                if (!empty($items)) {
                    $totalInBase = 0;
                    $tableRows = "";
                    foreach ($items as $item) {
                        $displayUnitPrice = toBaseCurrencyDisplay($item['unit_price'], $item['currency'] ?: 'EUR');
                        $displayTotalPrice = toBaseCurrencyDisplay($item['total_price'], $item['currency'] ?: 'EUR');
                        $totalInBase += $displayTotalPrice;
                        $tableRows .= "
                            <tr style='border-bottom: 1px solid #eee;'>
                                <td style='padding: 10px; border: 1px solid #e5e7eb;'>" . e($item['product_name']) . "</td>
                                <td style='padding: 10px; border: 1px solid #e5e7eb; text-align: right;'>" . formatQty($item['quantity']) . " " . e($item['unit']) . "</td>
                                <td style='padding: 10px; border: 1px solid #e5e7eb; text-align: right;'>" . number_format($displayUnitPrice, 2, ',', '.') . " " . getCurrencySymbol() . "</td>
                                <td style='padding: 10px; border: 1px solid #e5e7eb; text-align: right; font-weight: bold;'>" . number_format($displayTotalPrice, 2, ',', '.') . " " . getCurrencySymbol() . "</td>
                            </tr>
                        ";
                    }

                    $subject = "📋 Yeni Emanet Bilgilendirmesi: " . $batchId;
                    $body = "
                        <div style='font-family: sans-serif; max-width: 650px; margin: 0 auto; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;'>
                            <div style='background-color: #343a40; color: #ffffff; padding: 20px; text-align: center;'>
                                <h2 style='margin: 0;'>" . e(get_setting('site_name', APP_NAME)) . "</h2>
                            </div>
                            <div style='padding: 30px;'>
                                <p style='font-size: 16px; color: #374151;'>Sayın <strong>{$requester['name']} {$requester['surname']}</strong>,</p>
                                <p style='color: #6b7280; line-height: 1.5;'>Adınıza yeni bir emanet kaydı oluşturulmuştur. Ürün listesi aşağıdadır.</p>
                                
                                <div style='margin: 20px 0; padding: 15px; background-color: #f8fafc; border-left: 4px solid #10b981;'>
                                    <p style='margin: 0; font-size: 14px;'><strong>Depo:</strong> $warehouseName</p>
                                    <p style='margin: 5px 0 0 0; font-size: 14px;'><strong>Emanet No:</strong> $batchId</p>
                                    <p style='margin: 5px 0 0 0; font-size: 14px;'><strong>Beklenen İade:</strong> " . ($expectedReturn ? date('d.m.Y', strtotime($expectedReturn)) : '—') . "</p>
                                    <p style='margin: 5px 0 0 0; font-size: 14px;'><strong>Not:</strong> " . ($note ?: '—') . "</p>
                                </div>

                                <table style='width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px;'>
                                    <thead>
                                        <tr style='background-color: #f1f5f9;'>
                                            <th style='padding: 12px; border: 1px solid #e5e7eb; text-align: left;'>Ürün</th>
                                            <th style='padding: 12px; border: 1px solid #e5e7eb; text-align: right;'>Miktar</th>
                                            <th style='padding: 12px; border: 1px solid #e5e7eb; text-align: right;'>Birim Fiyat</th>
                                            <th style='padding: 12px; border: 1px solid #e5e7eb; text-align: right;'>Toplam (" . get_setting('base_currency', 'EUR') . ")</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        $tableRows
                                    </tbody>
                                    <tfoot>
                                        <tr style='background-color: #f8fafc;'>
                                            <td colspan='3' style='padding: 12px; border: 1px solid #e5e7eb; text-align: right; font-weight: bold;'>EMANET TOPLAM DEĞERİ</td>
                                            <td style='padding: 12px; border: 1px solid #e5e7eb; text-align: right; font-weight: bold; color: #059669; font-size: 16px;'>" . number_format($totalInBase, 2, ',', '.') . " " . getCurrencySymbol() . "</td>
                                        </tr>
                                    </tfoot>
                                </table>
                                
                                <div style='margin-top: 30px; padding: 15px; background-color: #ecfdf5; border-radius: 6px; color: #065f46; font-size: 14px;'>
                                    Bu ürünler geri iade edilmek üzere emanet olarak verilmiştir.
                                </div>
                            </div>
                            <div style='background-color: #f3f4f6; color: #9ca3af; padding: 15px; text-align: center; font-size: 12px;'>
                                Bu e-posta otomatik olarak gönderilmiştir. Lütfen yanıtlamayınız.
                            </div>
                        </div>
                    ";

                    send_mail($requester['email'], $subject, $body);
                }
            }
        }

        jsonResponse(true, 'Emanet kaydı oluşturuldu ve stoktan düşüldü.');

    case 'list':
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(100, max(5, (int) ($_GET['per_page'] ?? 10)));
        $search = sanitize($_GET['search'] ?? '');
        $offset = ($page - 1) * $perPage;

        $where = "1=1";
        $params = [];
        if ($search) {
            $where .= " AND (p.name LIKE ? OR w.name LIKE ? OR r.name LIKE ? OR r.surname LIKE ?)";
            $params = ["%$search%", "%$search%", "%$search%", "%$search%"];
        }

        $total = Database::fetchOne(
            "SELECT COUNT(*) AS c FROM tbl_dp_entrusted e
             JOIN tbl_dp_products p ON p.id=e.product_id
             JOIN tbl_dp_warehouses w ON w.id=e.warehouse_id
             LEFT JOIN tbl_dp_requesters r ON r.id=e.requester_id
             WHERE $where",
            $params
        )['c'] ?? 0;

        $rows = Database::fetchAll(
            "SELECT e.*, p.name AS product_name, p.unit, w.name AS warehouse_name,
                    r.name AS requester_name, r.surname AS requester_surname,
                    DATE_FORMAT(e.created_at,'%d.%m.%Y %H:%i') AS created_at_fmt,
                    DATE_FORMAT(e.expected_return_at,'%d.%m.%Y') AS expected_return_at_fmt
             FROM tbl_dp_entrusted e
             JOIN tbl_dp_products p ON p.id=e.product_id
             JOIN tbl_dp_warehouses w ON w.id=e.warehouse_id
             LEFT JOIN tbl_dp_requesters r ON r.id=e.requester_id
             WHERE $where ORDER BY e.created_at DESC LIMIT $perPage OFFSET $offset",
            $params
        );
        jsonResponse(true, '', ['data' => $rows, 'total' => (int) $total]);

    case 'process_action':
        $id = (int) ($_POST['id'] ?? 0);
        $type = sanitize($_POST['type'] ?? ''); // 'return' or 'sale'
        $qty = (float) ($_POST['quantity'] ?? 0);
        $customerId = (int) ($_POST['customer_id'] ?? 0) ?: null;
        $note = sanitize($_POST['note'] ?? '');

        if (!$id)
            jsonResponse(false, 'ID geçersiz.');
        if ($qty <= 0)
            jsonResponse(false, 'Miktar sıfırdan büyük olmalıdır.');
        if ($type === 'sale' && !$customerId)
            jsonResponse(false, 'Satış işlemi için müşteri seçilmelidir.');

        $entrusted = Database::fetchOne(
            "SELECT e.*, p.name AS product_name, p.unit, 
                    r.name AS requester_name, r.surname AS requester_surname, r.email AS requester_email
             FROM tbl_dp_entrusted e
             JOIN tbl_dp_products p ON p.id=e.product_id
             LEFT JOIN tbl_dp_requesters r ON r.id=e.requester_id
             WHERE e.id=?",
            [$id]
        );

        if (!$entrusted)
            jsonResponse(false, 'Kayıt bulunamadı.');
        if ($qty > $entrusted['remaining_quantity'])
            jsonResponse(false, 'Miktar emanet kalanından büyük olamaz.');

        $currentUser = currentUser();
        $userId = $currentUser['id'];
        $userName = $currentUser['name'];

        // 1. Log the action
        Database::insert(
            "INSERT INTO tbl_dp_entrusted_actions (entrusted_id, action_type, quantity, customer_id, note, created_by, created_by_name)
             VALUES (?,?,?,?,?,?,?)",
            [$id, $type, $qty, $customerId, $note, $userId, $userName]
        );

        // 2. Update remaining quantity
        $newRemaining = $entrusted['remaining_quantity'] - $qty;
        $status = 0; // Açık
        if ($newRemaining <= 0) {
            $status = ($type === 'return') ? 1 : 2;
        }

        Database::execute(
            "UPDATE tbl_dp_entrusted SET remaining_quantity=?, status=? WHERE id=?",
            [$newRemaining, $status, $id]
        );

        // 3. Stok Etkisi
        if ($type === 'return') {
            // IADE: Ürün geri geldi, Stock In kaydı oluştur (Stoğu artır)
            $priceData = Database::fetchOne("SELECT unit_price, currency FROM tbl_dp_stock_in WHERE product_id=? ORDER BY created_at DESC LIMIT 1", [$entrusted['product_id']]);
            $unitPrice = $priceData ? (float) $priceData['unit_price'] : 0;
            $currency = $priceData ? $priceData['currency'] : 'EUR';
            $priceInBase = toBaseCurrencyDisplay($unitPrice, $currency);

            Database::insert(
                "INSERT INTO tbl_dp_stock_in (warehouse_id, product_id, quantity, unit_price, currency, price_eur, note, created_by)
                 VALUES (?,?,?,?,?,?,?,?)",
                [$entrusted['warehouse_id'], $entrusted['product_id'], $qty, $unitPrice, $currency, $priceInBase, "Emanetten İade: " . $note, $userId]
            );
        } else if ($type === 'sale') {
            // SATIŞ: Ürün zaten emanet verildiğinde stoktan düşmüştü.
        }

        // 4. E-posta Bildirimi
        $mailSent = false;

        if (!empty($entrusted['requester_email'])) {
            $actionTitle = ($type === 'return') ? 'Emanet İade Alındı' : 'Emanet Müşteriye Çıkış Yapıldı';
            $actionColor = ($type === 'return') ? '#10b981' : '#ef4444';
            $actionIcon = ($type === 'return') ? '↩️' : '🚚';

            $priceData = Database::fetchOne("SELECT unit_price, currency FROM tbl_dp_stock_in WHERE product_id=? ORDER BY created_at DESC LIMIT 1", [$entrusted['product_id']]);
            $unitPriceOrig = $priceData ? (float) $priceData['unit_price'] : 0;
            $currencyOrig = $priceData ? $priceData['currency'] : 'EUR';
            $unitPriceDisplay = toBaseCurrencyDisplay($unitPriceOrig, $currencyOrig);
            $totalInBase = $unitPriceDisplay * $qty;

            $customerRow = '';
            if ($type === 'sale' && $customerId) {
                $customerData = Database::fetchOne("SELECT name FROM tbl_dp_customers WHERE id=?", [$customerId]);
                $customerName = $customerData['name'] ?? '—';
                $customerRow = "
                    <tr style='background-color: #f9fafb;'>
                        <td style='padding: 12px; border: 1px solid #e5e7eb; font-weight: bold;'>Müşteri</td>
                        <td style='padding: 12px; border: 1px solid #e5e7eb;'>$customerName</td>
                    </tr>
                ";
            }

            $subject = "$actionIcon $actionTitle: " . $entrusted['product_name'];
            $body = "
                <div style='font-family: sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;'>
                    <div style='background-color: #343a40; color: #ffffff; padding: 20px; text-align: center;'>
                        <h2 style='margin: 0;'>" . e(get_setting('site_name', APP_NAME)) . "</h2>
                    </div>
                    <div style='padding: 30px;'>
                        <p style='font-size: 16px; color: #374151;'>Sayın <strong>{$entrusted['requester_name']} {$entrusted['requester_surname']}</strong>,</p>
                        <p style='color: #6b7280; line-height: 1.5;'>Emanetinizde bulunan ürün hakkında yeni bir işlem gerçekleştirilmiştir.</p>
                        
                        <table style='width: 100%; border-collapse: collapse; margin-top: 20px;'>
                            <tr style='background-color: #f9fafb;'>
                                <td style='padding: 12px; border: 1px solid #e5e7eb; font-weight: bold; width: 150px;'>Ürün</td>
                                <td style='padding: 12px; border: 1px solid #e5e7eb;'>{$entrusted['product_name']}</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px; border: 1px solid #e5e7eb; font-weight: bold;'>İşlem Türü</td>
                                <td style='padding: 12px; border: 1px solid #e5e7eb; color: $actionColor; font-weight: bold;'>$actionTitle</td>
                            </tr>
                            $customerRow
                            <tr style='background-color: " . ($customerRow ? "#ffffff" : "#f9fafb") . ";'>
                                <td style='padding: 12px; border: 1px solid #e5e7eb; font-weight: bold;'>İşlem Miktarı</td>
                                <td style='padding: 12px; border: 1px solid #e5e7eb;'>" . formatQty($qty) . " {$entrusted['unit']}</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px; border: 1px solid #e5e7eb; font-weight: bold;'>Birim Değeri</td>
                                <td style='padding: 12px; border: 1px solid #e5e7eb;'>" . number_format($unitPriceDisplay, 2, ',', '.') . " " . getCurrencySymbol() . "</td>
                            </tr>
                            <tr style='background-color: #f9fafb;'>
                                <td style='padding: 12px; border: 1px solid #e5e7eb; font-weight: bold;'>İşlem Tutarı</td>
                                <td style='padding: 12px; border: 1px solid #e5e7eb; font-weight: bold; color: #1e40af;'>" . number_format($totalInBase, 2, ',', '.') . " " . getCurrencySymbol() . "</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px; border: 1px solid #e5e7eb; font-weight: bold;'>Kalan Emanet</td>
                                <td style='padding: 12px; border: 1px solid #e5e7eb; font-weight: bold; color: #1a56db;'>" . formatQty($newRemaining) . " {$entrusted['unit']}</td>
                            </tr>
                            <tr style='background-color: #f9fafb;'>
                                <td style='padding: 12px; border: 1px solid #e5e7eb; font-weight: bold;'>İşlem Notu</td>
                                <td style='padding: 12px; border: 1px solid #e5e7eb;'>" . ($note ?: '—') . "</td>
                            </tr>
                        </table>
                        
                        <div style='margin-top: 30px; padding: 15px; background-color: #eff6ff; border-radius: 6px; color: #1e40af; font-size: 14px;'>
                            İşlem kayıtları ve stok durumu güncellenmiştir. Herhangi bir sorunuz varsa lütfen depo sorumlusu ile iletişime geçin.
                        </div>
                    </div>
                    <div style='background-color: #f3f4f6; color: #9ca3af; padding: 15px; text-align: center; font-size: 12px;'>
                        Bu e-posta otomatik olarak gönderilmiştir. Lütfen yanıtlamayınız.
                    </div>
                </div>
            ";

            $mailSent = send_mail($entrusted['requester_email'], $subject, $body);
        }

        $msg = 'İşlem başarıyla tamamlandı.';
        if (!empty($entrusted['requester_id'])) {
            if (!empty($entrusted['requester_email'])) {
                $msg .= $mailSent ? ' E-posta bildirimi gönderildi.' : ' Ancak e-posta gönderilemedi! Lütfen ayarları kontrol edin.';
            } else {
                $msg .= ' Ancak talep edenin e-posta adresi kayıtlı olmadığı için bildirim gönderilemedi.';
            }
        }

        jsonResponse(true, $msg, ['mail_sent' => $mailSent]);

    case 'get_history':
        $id = (int) ($_GET['id'] ?? 0);
        $rows = Database::fetchAll(
            "SELECT a.*, c.name AS customer_name,
                    DATE_FORMAT(a.created_at,'%d.%m.%Y %H:%i') AS created_at_fmt
             FROM tbl_dp_entrusted_actions a
             LEFT JOIN tbl_dp_customers c ON c.id=a.customer_id
             WHERE a.entrusted_id=? ORDER BY a.created_at DESC",
            [$id]
        );
        jsonResponse(true, '', $rows);

    default:
        jsonResponse(false, 'Geçersiz işlem.');
}
