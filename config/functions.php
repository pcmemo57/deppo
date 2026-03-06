<?php
/**
 * Yardımcı Fonksiyonlar
 */

require_once __DIR__ . '/database.php';

/**
 * XSS temizleme
 */
function e(mixed $val): string
{
    return htmlspecialchars((string) $val, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function sanitize(string $input): string
{
    return trim(strip_tags($input));
}

/**
 * Fiyat formatlama — Türkçe: 1.234,56
 */
function formatPrice(float $amount, int $decimals = 2): string
{
    return number_format($amount, $decimals, ',', '.');
}

function formatQty(float $qty): string
{
    return number_format($qty, 0, ',', '.');
}

/**
 * Ayar okuma
 */
function get_setting(string $key, string $default = ''): string
{
    static $cache = [];
    if (!isset($cache[$key])) {
        $row = Database::fetchOne('SELECT setting_value FROM tbl_dp_settings WHERE setting_key = ?', [$key]);
        $cache[$key] = $row ? $row['setting_value'] : $default;
    }
    return $cache[$key];
}

/**
 * Ayar kaydetme
 */
function set_setting(string $key, string $value): void
{
    Database::execute(
        'INSERT INTO tbl_dp_settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
        [$key, $value]
    );
}

/**
 * Hareket kontrolü — kayıt silinip silinemeyeceğini belirler
 * Hareket görülen kayıt hidden=1 ile gizlenir
 */
function hasMovement(string $table, string $idColumn, int $id): bool
{
    $movements = [
        'tbl_dp_products' => [
            ['tbl_dp_stock_in', 'product_id'],
            ['tbl_dp_stock_out', 'product_id'],
            ['tbl_dp_transfer_items', 'product_id'],
        ],
        'tbl_dp_warehouses' => [
            ['tbl_dp_stock_in', 'warehouse_id'],
            ['tbl_dp_stock_out', 'warehouse_id'],
            ['tbl_dp_transfers', 'source_warehouse_id'],
            ['tbl_dp_transfers', 'target_warehouse_id'],
        ],
        'tbl_dp_customers' => [
            ['tbl_dp_stock_out', 'customer_id'],
        ],
        'tbl_dp_suppliers' => [
            ['tbl_dp_stock_in', 'supplier_id'],
        ],
        'tbl_dp_requesters' => [
            ['tbl_dp_stock_out', 'requester_id'],
        ],
        'tbl_dp_admins' => [],
        'tbl_dp_users' => [],
    ];

    if (!isset($movements[$table]))
        return false;

    foreach ($movements[$table] as [$refTable, $refCol]) {
        $count = Database::fetchOne(
            "SELECT COUNT(*) AS cnt FROM `$refTable` WHERE `$refCol` = ?",
            [$id]
        );
        if ($count && $count['cnt'] > 0)
            return true;
    }
    return false;
}

/**
 * Resim yükleme
 */
function uploadImage(array $file, string $prefix = ''): string|false
{
    if ($file['error'] !== UPLOAD_ERR_OK)
        return false;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_IMAGE_EXT))
        return false;
    if ($file['size'] > MAX_FILE_SIZE)
        return false;

    $filename = $prefix . uniqid() . '.' . $ext;
    $dest = UPLOAD_PATH . $filename;
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return $filename;
    }
    return false;
}

/**
 * Convert price between different currencies using system rates
 */
function convertPrice($amount, $from, $to)
{
    if ($from === $to)
        return (float) $amount;

    $rates = [
        'TL' => 1.0,
        'USD' => (float) get_setting('usd_rate', '0'),
        'EUR' => (float) get_setting('eur_rate', '0')
    ];

    if ($to === 'TL') {
        return (float) $amount * $rates[$from];
    }

    if ($from === 'TL') {
        return (float) $amount / ($rates[$to] ?: 1);
    }

    // Bridge via TL
    $tlAmount = (float) $amount * $rates[$from];
    return $tlAmount / ($rates[$to] ?: 1);
}

/**
 * Convert amount to system's base currency for display
 */
function toBaseCurrencyDisplay($amount, $fromCurrency)
{
    $baseCurrency = get_setting('base_currency', 'EUR');
    return convertPrice($amount, $fromCurrency, $baseCurrency);
}

/**
 * Para birimi dönüşümü (TL/USD/EUR → Seçilen Base Currency)
 */
function toEur(float $amount, string $currency): float
{
    return toBaseCurrencyDisplay($amount, $currency);
}

/**
 * Belirli bir depo ve ürün için mevcut stoğu hesapla
 */
function getProductStock(int $productId, int $warehouseId = 0): float
{
    $whereIn = "product_id = ? AND is_active = 1";
    $whereOut = "product_id = ?";
    $paramsIn = [$productId];
    $paramsOut = [$productId];

    if ($warehouseId > 0) {
        $whereIn .= " AND warehouse_id = ?";
        $whereOut .= " AND warehouse_id = ?";
        $paramsIn[] = $warehouseId;
        $paramsOut[] = $warehouseId;
    }

    $in = Database::fetchOne("SELECT SUM(quantity) as qty FROM tbl_dp_stock_in WHERE $whereIn", $paramsIn)['qty'] ?? 0;
    $out = Database::fetchOne("SELECT SUM(quantity) as qty FROM tbl_dp_stock_out WHERE $whereOut AND status=1", $paramsOut)['qty'] ?? 0;
    return (float) round($in - $out, 3);
}

/**
 * Deponun tamamen boş olup olmadığını kontrol et
 * (Her bir ürünün bakiyesinin tam olarak 0 olması gerekir)
 */
function isWarehouseEmpty(int $warehouseId): bool
{
    $sql = "SELECT COUNT(*) as cnt FROM (
                SELECT product_id, SUM(q) as stock FROM (
                    SELECT product_id, quantity as q FROM tbl_dp_stock_in WHERE warehouse_id = ? AND is_active = 1
                    UNION ALL
                    SELECT product_id, -quantity as q FROM tbl_dp_stock_out WHERE warehouse_id = ? AND status=1
                ) t GROUP BY product_id HAVING ROUND(SUM(q), 3) != 0
            ) final";
    $res = Database::fetchOne($sql, [$warehouseId, $warehouseId]);
    return ($res['cnt'] ?? 0) == 0;
}

/**
 * Mail gönderme (PHPMailer wrapper)
 * @param array $attachments [ ['data' => 'base64...', 'name' => 'filename.pdf', 'type' => 'application/pdf'], ... ]
 *                          veya [ ['path' => '/path/to/file', 'name' => 'filename.pdf'], ... ]
 */
function send_mail(string $to, string $subject, string $body, bool $isHtml = true, array $embeddedImages = [], array $attachments = []): bool
{
    require_once ROOT_PATH . '/PHPMailer/src/PHPMailer.php';
    require_once ROOT_PATH . '/PHPMailer/src/SMTP.php';
    require_once ROOT_PATH . '/PHPMailer/src/Exception.php';

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = get_setting('mail_host', 'smtp.gmail.com');
        $mail->SMTPAuth = true;
        $mail->Username = get_setting('mail_user', '');
        $mail->Password = get_setting('mail_pass', '');
        $mail->SMTPSecure = get_setting('mail_secure', 'tls');
        $mail->Port = (int) get_setting('mail_port', '587');
        $mail->CharSet = 'UTF-8';
        $mail->setFrom(get_setting('mail_from', get_setting('mail_user', '')), get_setting('mail_from_name', APP_NAME));
        $mail->addAddress($to);
        $mail->isHTML($isHtml);

        // Ekli Resimler (CID)
        foreach ($embeddedImages as $cid => $path) {
            if (file_exists($path)) {
                $mail->addEmbeddedImage($path, $cid);
            }
        }

        // Ekler (Attachments)
        foreach ($attachments as $att) {
            if (isset($att['data'])) {
                // Base64 veri eki
                $data = $att['data'];
                if (str_contains($data, ';base64,')) {
                    $data = explode(';base64,', $data)[1];
                }
                $decoded = base64_decode($data);
                $mail->addStringAttachment($decoded, $att['name'], 'base64', $att['type'] ?? 'application/octet-stream');
            } elseif (isset($att['path']) && file_exists($att['path'])) {
                $mail->addAttachment($att['path'], $att['name'] ?? basename($att['path']));
            }
        }

        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->send();
        return true;
    } catch (\Exception $e) {
        error_log('Mail hatası: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * JSON cevap döndür ve çık
 */
function jsonResponse(bool $success, string $message = '', mixed $data = null): never
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

/**
 * AJAX mı?
 */
function isAjax(): bool
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Aktif sayfa kontrolü (sidebar için)
 */
function isActivePage(string $page): string
{
    return ($_GET['page'] ?? 'dashboard') === $page ? 'active' : '';
}

/**
 * Rastgele güvenli şifre özeti
 */
function hashPassword(string $password): string
{
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

function verifyPassword(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}
/**
 * Resmi boyutlandır — Thumbnail oluşturma
 */
function resize_image(string $src, string $dst, int $width, int $height): bool
{
    $info = @getimagesize($src);
    if (!$info)
        return false;
    $type = $info[2];

    switch ($type) {
        case IMAGETYPE_JPEG:
            $img = @imagecreatefromjpeg($src);
            break;
        case IMAGETYPE_PNG:
            $img = @imagecreatefrompng($src);
            break;
        case IMAGETYPE_GIF:
            $img = @imagecreatefromgif($src);
            break;
        case IMAGETYPE_WEBP:
            $img = @imagecreatefromwebp($src);
            break;
        default:
            return false;
    }

    if (!$img)
        return false;

    $w = imagesx($img);
    $h = imagesy($img);
    $ratio = max($width / $w, $height / $h);
    $new_w = (int) ($w * $ratio);
    $new_h = (int) ($h * $ratio);

    $thumb = imagecreatetruecolor($width, $height);

    if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP) {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
    }

    imagecopyresampled($thumb, $img, (int) (($width - $new_w) / 2), (int) (($height - $new_h) / 2), 0, 0, $new_w, $new_h, $w, $h);

    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($thumb, $dst, 85);
            break;
        case IMAGETYPE_PNG:
            imagepng($thumb, $dst, 8);
            break;
        case IMAGETYPE_GIF:
            imagegif($thumb, $dst);
            break;
        case IMAGETYPE_WEBP:
            imagewebp($thumb, $dst, 80);
            break;
    }

    imagedestroy($img);
    imagedestroy($thumb);
    return true;
}

/**
 * Get base currency symbol
 */
function getCurrencySymbol($currency = null)
{
    if (!$currency) {
        $currency = get_setting('base_currency', 'EUR');
    }
    switch ($currency) {
        case 'USD':
            return '$';
        case 'EUR':
            return '€';
        case 'TL':
            return 'TL';
        default:
            return $currency;
    }
}

/**
 * Belirli bir depo için açık bir sayım oturumu olup olmadığını kontrol et
 */
function isInventoryOpen(int $warehouseId): bool
{
    $row = Database::fetchOne("SELECT id FROM inventory_sessions WHERE warehouse_id = ? AND status = 'open'", [$warehouseId]);
    return !empty($row);
}
