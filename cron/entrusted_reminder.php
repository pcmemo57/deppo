<?php
/**
 * Emanet iade hatırlatma cron işlemi
 * Bu dosya günde bir kez çalıştırılmalıdır.
 */

// CLI üzerinden çalıştırıldığını varsayıyoruz, dosya yolunu ayarla
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

echo "Emanet hatırlatma işlemi başlatıldı: " . date('Y-m-d H:i:s') . "\n";

// 1. Gecikmiş ve hala elinde ürün olan kayıtları getir
$overdueItems = Database::fetchAll("
    SELECT e.*, p.name AS product_name, p.unit, 
           r.name AS requester_name, r.surname AS requester_surname, r.email AS requester_email
    FROM tbl_dp_entrusted e
    JOIN tbl_dp_products p ON p.id = e.product_id
    JOIN tbl_dp_requesters r ON r.id = e.requester_id
    WHERE e.remaining_quantity > 0 
      AND e.expected_return_at IS NOT NULL 
      AND e.expected_return_at < CURDATE()
      AND r.email IS NOT NULL AND r.email != ''
    ORDER BY r.id
");

if (empty($overdueItems)) {
    echo "Gecikmiş emanet kaydı bulunamadı.\n";
    exit;
}

// 2. Kişi bazlı grupla (Aynı kişiye tek mail atmak için)
$grouped = [];
foreach ($overdueItems as $item) {
    $grouped[$item['requester_id']]['info'] = [
        'name' => $item['requester_name'] . ' ' . $item['requester_surname'],
        'email' => $item['requester_email']
    ];
    $grouped[$item['requester_id']]['items'][] = $item;
}

// 3. Mailleri gönder
$successCount = 0;
$failCount = 0;

foreach ($grouped as $reqId => $data) {
    $to = $data['info']['email'];
    $name = $data['info']['name'];
    $subject = "Emanet Ürün İade Hatırlatması - " . get_setting('site_name', 'Depo Sistemi');

    $body = "Sayın {$name},<br><br>";
    $body .= "Aşağıdaki emanet aldığınız ürünlerin iade süresi dolmuştur. Lütfen en kısa sürede depoya teslim ediniz veya durum hakkında bilgi veriniz:<br><br>";

    $body .= "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    $body .= "<tr style='background: #f4f4f4;'><th>Ürün</th><th>Kalan Miktar</th><th>Beklenen Tarih</th></tr>";

    foreach ($data['items'] as $item) {
        $dateFmt = date('d.m.Y', strtotime($item['expected_return_at']));
        $qtyFmt = formatQty($item['remaining_quantity']);
        $body .= "<tr><td>{$item['product_name']}</td><td>{$qtyFmt} {$item['unit']}</td><td>{$dateFmt}</td></tr>";
    }

    $body .= "</table><br>";
    $body .= "İyi çalışmalar.<br><br><b>" . get_setting('site_name', 'Depo Yönetim Sistemi') . "</b>";

    if (send_mail($to, $subject, $body, true)) {
        echo "Başarılı: {$to} adresine mail gönderildi.\n";
        $successCount++;
    } else {
        echo "HATA: {$to} adresine mail gönderilemedi.\n";
        $failCount++;
    }
}

echo "\nİşlem tamamlandı. Başarılı: {$successCount}, Hatalı: {$failCount}\n";
