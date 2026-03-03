<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireRole(ROLE_ADMIN, ROLE_USER);
header('Content-Type: application/json; charset=utf-8');

$action = sanitize($_POST['action'] ?? $_GET['action'] ?? '');

switch ($action) {
    case 'list':
        $rows = Database::fetchAll("
            SELECT pl.*, c.name as customer_name,
            (SELECT COUNT(*) FROM tbl_dp_packing_list_parcels WHERE packing_list_id = pl.id) as parcel_count
            FROM tbl_dp_packing_lists pl
            JOIN tbl_dp_customers c ON c.id = pl.customer_id
            ORDER BY pl.created_at DESC
        ");
        jsonResponse(true, '', $rows);

    case 'generate_no':
        $year = date('Y');
        $prefix = "PL-$year-";
        $last = Database::fetchOne("SELECT list_no FROM tbl_dp_packing_lists WHERE list_no LIKE ? ORDER BY id DESC LIMIT 1", ["$prefix%"]);
        $nextNum = 1;
        if ($last) {
            $lastNum = (int) str_replace($prefix, '', $last['list_no']);
            $nextNum = $lastNum + 1;
        }
        $newNo = $prefix . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
        jsonResponse(true, '', $newNo);

    case 'save':
        $id = (int) ($_POST['id'] ?? 0);
        $customerId = (int) ($_POST['customer_id'] ?? 0);
        $listNo = sanitize($_POST['list_no'] ?? '');
        $notes = sanitize($_POST['notes'] ?? '');
        $parcelsData = $_POST['parcels'] ?? '[]';
        $parcels = json_decode($parcelsData, true) ?: [];

        if (!$customerId || !$listNo) {
            jsonResponse(false, 'Müşteri ve Liste No zorunludur.');
        }

        try {
            Database::execute("START TRANSACTION");

            $totalWeight = 0;
            $totalVolDesi = 0;

            // Calculate totals first to have them for initial insert if needed
            foreach ($parcels as $p) {
                $width = (float) ($p['width'] ?? 0);
                $height = (float) ($p['height'] ?? 0);
                $length = (float) ($p['length'] ?? 0);
                $totalWeight += (float) ($p['weight'] ?? 0);
                $totalVolDesi += ($width * $height * $length) / 3000;
            }

            if ($id > 0) {
                Database::execute(
                    "UPDATE tbl_dp_packing_lists SET customer_id = ?, list_no = ?, notes = ?, total_weight_kg = ?, total_vol_desi = ?, total_parcels = ? WHERE id = ?",
                    [$customerId, $listNo, $notes, $totalWeight, $totalVolDesi, count($parcels), $id]
                );
                $packingListId = $id;
                Database::execute("DELETE FROM tbl_dp_packing_list_parcels WHERE packing_list_id = ?", [$packingListId]);
            } else {
                $packingListId = Database::insert(
                    "INSERT INTO tbl_dp_packing_lists (customer_id, list_no, notes, total_weight_kg, total_vol_desi, total_parcels, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$customerId, $listNo, $notes, $totalWeight, $totalVolDesi, count($parcels), $_SESSION['user_id']]
                );
            }

            foreach ($parcels as $index => $p) {
                $no = $index + 1;
                $weight = (float) ($p['weight'] ?? 0);
                $width = (float) ($p['width'] ?? 0);
                $height = (float) ($p['height'] ?? 0);
                $length = (float) ($p['length'] ?? 0);
                $items = $p['items'] ?? [];

                $parcelId = Database::insert(
                    "INSERT INTO tbl_dp_packing_list_parcels (packing_list_id, parcel_no, weight_kg, width_cm, height_cm, length_cm) VALUES (?, ?, ?, ?, ?, ?)",
                    [$packingListId, $no, $weight, $width, $height, $length]
                );

                foreach ($items as $item) {
                    $productId = (int) ($item['product_id'] ?? 0);
                    $qty = (float) ($item['quantity'] ?? 0);
                    if ($productId > 0 && $qty > 0) {
                        Database::insert("INSERT INTO tbl_dp_packing_list_items (parcel_id, product_id, quantity) VALUES (?, ?, ?)", [$parcelId, $productId, $qty]);
                    }
                }
            }

            Database::execute("COMMIT");
            jsonResponse(true, 'Çeki listesi kaydedildi.');
        } catch (Exception $e) {
            Database::execute("ROLLBACK");
            jsonResponse(false, 'Hata oluştu: ' . $e->getMessage());
        }

    case 'get':
        $id = (int) ($_GET['id'] ?? 0);
        $pl = Database::fetchOne("
            SELECT pl.*, c.name as customer_name 
            FROM tbl_dp_packing_lists pl
            JOIN tbl_dp_customers c ON c.id = pl.customer_id
            WHERE pl.id = ?
        ", [$id]);
        if (!$pl)
            jsonResponse(false, 'Liste bulunamadı.');

        $parcels = Database::fetchAll("SELECT * FROM tbl_dp_packing_list_parcels WHERE packing_list_id = ? ORDER BY parcel_no ASC", [$id]);
        foreach ($parcels as &$p) {
            $p['items'] = Database::fetchAll("
                SELECT i.*, pr.name as product_name, pr.code as product_code, pr.unit
                FROM tbl_dp_packing_list_items i
                JOIN tbl_dp_products pr ON pr.id = i.product_id
                WHERE i.parcel_id = ?
            ", [$p['id']]);
        }

        jsonResponse(true, '', ['list' => $pl, 'parcels' => $parcels]);

    case 'delete':
        $id = (int) ($_POST['id'] ?? 0);
        Database::execute("DELETE FROM tbl_dp_packing_lists WHERE id = ?", [$id]);
        jsonResponse(true, 'Liste silindi.');

    case 'send_email':
        $id = (int) ($_POST['id'] ?? 0);
        $to = sanitize($_POST['to'] ?? '');
        $pdfData = $_POST['pdf_data'] ?? '';

        if (!$id || !$to || !$pdfData) {
            jsonResponse(false, 'Gerekli bilgiler eksik.');
        }

        $pl = Database::fetchOne("
            SELECT pl.list_no, c.name as customer_name 
            FROM tbl_dp_packing_lists pl
            JOIN tbl_dp_customers c ON c.id = pl.customer_id
            WHERE pl.id = ?
        ", [$id]);

        if (!$pl) {
            jsonResponse(false, 'Liste bulunamadı.');
        }

        $subject = "{$pl['customer_name']} Çeki Listesi";
        $body = "
            <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <h2 style='color: #1a56db;'>Sayın İlgili,</h2>
                <p><b>{$pl['customer_name']}</b> firmasına ait <b>{$pl['list_no']}</b> numaralı çeki listesi (packing list) ekte tarafınıza sunulmuştur.</p>
                <p>Detayları ekteki PDF dosyasından inceleyebilirsiniz.</p>
                <br>
                <p>İyi çalışmalar dileriz.</p>
                <hr style='border: 0; border-top: 1px solid #eee;'>
                <p style='font-size: 0.8rem; color: #777;'>Bu e-posta " . APP_NAME . " üzerinden otomatik olarak gönderilmiştir.</p>
            </div>
        ";

        $attachments = [
            [
                'data' => $pdfData,
                'name' => "Ceki-Listesi-{$pl['list_no']}.pdf",
                'type' => 'application/pdf'
            ]
        ];

        $success = send_mail($to, $subject, $body, true, [], $attachments);

        if ($success) {
            jsonResponse(true, 'E-posta başarıyla gönderildi.');
        } else {
            jsonResponse(false, 'E-posta gönderilirken bir hata oluştu.');
        }

    default:
        jsonResponse(false, 'Geçersiz işlem.');
}
