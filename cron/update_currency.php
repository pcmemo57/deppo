<?php
/**
 * Cron — TCMB Döviz Kuru Güncelleyici
 * Çalıştırmak için: php /path/to/deppo/cron/update_currency.php
 * Crontab örneği (günde 3 kez): 0 9,12,17 * * * php /Applications/XAMPP/xamppfiles/htdocs/deppo/cron/update_currency.php
 */

define('CLI_MODE', PHP_SAPI === 'cli');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

function log_msg(string $msg): void
{
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg;
    echo $line . PHP_EOL;
    if (!CLI_MODE)
        error_log($line);
}

log_msg('TCMB kur güncelleme başladı.');

$urls = [
    'https://www.tcmb.gov.tr/kurlar/today.xml',
    'https://www.tcmb.gov.tr/kurlar/' . date('Ym') . '/' . date('dmy') . '.xml',
];

$xml = null;
foreach ($urls as $url) {
    $xml = @simplexml_load_file($url);
    if ($xml) {
        log_msg("XML alındı: $url");
        break;
    }
}

if (!$xml) {
    log_msg('HATA: TCMB verisine ulaşılamadı.');
    exit(1);
}

$usd = null;
$eur = null;

foreach ($xml->Currency as $cur) {
    $code = (string)$cur->attributes()->CurrencyCode;
    $rate = (float)$cur->ForexSelling;
    if ($code === 'USD' && $rate > 0)
        $usd = $rate;
    if ($code === 'EUR' && $rate > 0)
        $eur = $rate;
}

if (!$usd || !$eur) {
    log_msg('HATA: USD veya EUR kuru bulunamadı.');
    exit(1);
}

set_setting('usd_rate', (string)$usd);
set_setting('eur_rate', (string)$eur);
set_setting('currency_updated', date('Y-m-d H:i:s'));

log_msg("USD: $usd TL");
log_msg("EUR: $eur TL");
log_msg('Kurlar başarıyla güncellendi.');
exit(0);