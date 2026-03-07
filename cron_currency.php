<?php
/**
 * CRON - Döviz Kurlarını Güncelleme (CLI)
 * Bu dosya arayüzden bağımsız olarak, komut satırı (CMD/Terminal)
 * veya arka plan görevleriyle çalıştırılmak üzere tasarlanmıştır.
 */

// Sadece komut satırından çalışsın (isteğe bağlı güvenlik önlemi)
if (php_sapi_name() !== 'cli' && !isset($_GET['force'])) {
    die("Bu script sadece komut satırından veya zamanlanmış görevler ile çalıştırılabilir.\n");
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';

echo "Kur güncelleme işlemi başlatılıyor...\n";

// TCMB XML bağlantısı
$xml = @simplexml_load_file('https://www.tcmb.gov.tr/kurlar/today.xml');
if (!$xml) {
    // Alternatif URL
    $xml = @simplexml_load_file('https://www.tcmb.gov.tr/kurlar/' . date('Ym') . '/' . date('dmy') . '.xml');
}

if (!$xml) {
    die("HATA: TCMB bağlantısı sağlanamadı.\n");
}

$usd = null;
$eur = null;

foreach ($xml->Currency as $cur) {
    $code = (string) $cur->attributes()->CurrencyCode;
    if ($code === 'USD') {
        $usd = (float) $cur->ForexSelling;
    }
    if ($code === 'EUR') {
        $eur = (float) $cur->ForexSelling;
    }
}

if (!$usd || !$eur) {
    die("HATA: Kur verisi ayrıştırılamadı.\n");
}

// Yeni kurları veritabanına kaydet
set_setting('usd_rate', (string) $usd);
set_setting('eur_rate', (string) $eur);
set_setting('currency_updated', date('Y-m-d H:i:s'));

echo "Başarılı: Kurlar güncellendi.\n";
echo "USD: " . number_format($usd, 4, ',', '.') . "\n";
echo "EUR: " . number_format($eur, 4, ',', '.') . "\n";
echo "Tarih: " . date('Y-m-d H:i:s') . "\n";
exit(0);
