<?php
/**
 * Real-time Git Sync Streamer
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Çıktı tamponlamasını kapat
if (function_exists('apache_setenv')) {
    apache_setenv('no-gzip', '1');
}
ini_set('zlib.output_compression', '0');
ini_set('implicit_flush', '1');
while (ob_get_level()) {
    ob_end_flush();
}
ob_implicit_flush(true);

echo "<html><head><style>
    body { background: #000; color: #0f0; font-family: monospace; padding: 10px; font-size: 14px; line-height: 1.5; }
    .success { color: #0f0; font-weight: bold; }
    .error { color: #f00; font-weight: bold; }
    .info { color: #0af; }
</style></head><body>";

function log_msg($msg, $class = '')
{
    echo "<div class='$class'>$msg</div>";
    echo str_pad('', 4096); // Browser buffering bypass
    flush();
}

log_msg(">>> DEPPO Git Senkronizasyonu Baslatiliyor...", "info");

$repoUrl = "https://github.com/pcmemo57/deppo.git";
$is_git_repo = is_dir(__DIR__ . '/../.git');

chdir(__DIR__ . '/..');
$currentDir = str_replace('\\', '/', getcwd());

// Git Güvenlik Ayarı (Farklı kullanıcı sahipliği hatasını çözmek için)
log_msg("--- Git güvenlik ayarları yapılandırılıyor...");
exec("git config --global --add safe.directory $currentDir 2>&1", $sec_out, $sec_res);

$commands = [];
if (!$is_git_repo) {
    log_msg("--- Yeni Git deposu olusturuluyor...");
    $commands[] = ["cmd" => "git init", "msg" => "Git baslatildi."];
    $commands[] = ["cmd" => "git remote add origin $repoUrl", "msg" => "Uzak depo baglandi."];
} else {
    log_msg("--- Mevcut depo guncelleniyor...");
}

$commands[] = ["cmd" => "git fetch --all", "msg" => "Buluttaki degisiklikler sorgulandi."];
$commands[] = ["cmd" => "git reset --hard origin/main", "msg" => "Dosyalar GitHub haliyle esitlendi."];
$commands[] = ["cmd" => "git clean -fd", "msg" => "Gereksiz dosyalar temizlendi."];

$output = [];
$res = 0;

foreach ($commands as $step) {
    log_msg("> " . $step['cmd']);

    $handle = popen($step['cmd'] . " 2>&1", 'r');
    while (!feof($handle)) {
        $line = fgets($handle);
        if ($line) {
            log_msg("  " . trim($line));
        }
    }
    $res = pclose($handle);

    if ($res !== 0) {
        log_msg("!!! HATA: Adim basarisiz oldu.", "error");
        exit;
    }
    log_msg("OK: " . $step['msg'], "success");
}

log_msg("==========================================", "info");
log_msg("TEBRİKLER: Tüm dosyalar başarıyla güncellendi!", "success");
log_msg("Simdi bu pencereyi kapatip kuruluma devam edebilirsiniz.", "info");

echo "<script>window.scrollTo(0, document.body.scrollHeight);</script>";
echo "</body></html>";
