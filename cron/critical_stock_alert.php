<?php
/**
 * Kritik stok seviyesinin altına düşen ürünler için e-posta bildirimi.
 * Bu dosya günde bir kez çalıştırılmalıdır.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

echo "Kritik stok bildirim işlemi başlatıldı: " . date('Y-m-d H:i:s') . "\n";

// 1. Bildirim e-postası kayıtlı mı kontrol et
$to = get_setting('critical_stock_notification_email', '');
if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
    echo "HATA: Kritik stok bildirim e-postası ayarlanmamış veya geçersiz.\n";
    exit;
}

// 2. Kritik stok seviyesindeki ürünleri getir
$alerts = getLowStockAlerts();
$globalLow = $alerts['global'];

// 3. Filtrele: Tedarik durumu 'Sipariş Verildi' (3) veya 'Tamamlandı' (5) olanları çıkar
$filteredGlobal = array_filter($globalLow, function($p) {
    return !in_array((int)($p['procurement_status'] ?? 0), [3, 5]);
});

if (empty($filteredGlobal)) {
    echo "Bildirim gönderilecek ürün bulunamadı (Tüm kritik stoklar sipariş edilmiş veya tamamlanmış olabilir).\n";
    exit;
}

// 4. Mail içeriğini hazırla
$subject = "Kritik Stok Bildirimi - " . get_setting('site_name', 'Depo Sistemi');
$siteName = get_setting('site_name', APP_NAME);

$body = "Sayın Yetkili,<br><br>";
$body .= "Aşağıdaki ürünlerin <b>toplam stok seviyeleri</b> belirlenen kritik sınırın altına düşmüştür:<br><br>";

$body .= "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%; font-family: sans-serif; font-size: 14px;'>";
$body .= "<tr style='background: #343a40; color: #ffffff;'><th>Ürün Adı</th><th>Ürün Kodu</th><th>Mevcut Toplam Stok</th><th>Kritik Eşik</th></tr>";
foreach ($filteredGlobal as $p) {
    $body .= "<tr>";
    $body .= "<td style='padding: 8px; border: 1px solid #dee2e6;'>" . e($p['name']) . "</td>";
    $body .= "<td style='padding: 8px; border: 1px solid #dee2e6;'><code>" . e($p['code'] ?: '—') . "</code></td>";
    $body .= "<td style='padding: 8px; border: 1px solid #dee2e6; font-weight: bold; color: #dc3545;'>" . formatQty($p['current_stock']) . " " . e($p['unit']) . "</td>";
    $body .= "<td style='padding: 8px; border: 1px solid #dee2e6;'>" . formatQty($p['stock_alarm']) . " " . e($p['unit']) . "</td>";
    $body .= "</tr>";
}
$body .= "</table><br>";

$body .= "Lütfen stok durumunu kontrol ederek gerekli sipariş işlemlerini başlatınız.<br><br>";
$body .= "İyi çalışmalar.<br><br><b>" . $siteName . "</b>";

// 5. Maili gönder
if (send_mail($to, $subject, $body, true)) {
    echo "BAŞARILI: {$to} adresine kritik stok listesi gönderildi.\n";
} else {
    echo "HATA: Bildirim maili gönderilemedi. Mail ayarlarını kontrol edin.\n";
}
