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
    return htmlspecialchars((string)$val, ENT_QUOTES | ENT_HTML5, 'UTF-8');
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
 * Para birimi dönüşümü (TL/USD → EUR)
 */
function toEur(float $amount, string $currency): float
{
    if ($currency === 'EUR')
        return $amount;
    if ($currency === 'USD') {
        $usdRate = (float)get_setting('usd_rate', '1');
        $eurRate = (float)get_setting('eur_rate', '1');
        if ($eurRate <= 0)
            return $amount;
        return $amount / $eurRate * $usdRate;
    }
    // TL
    $eurRate = (float)get_setting('eur_rate', '1');
    if ($eurRate <= 0)
        return $amount;
    return $amount / $eurRate;
}

/**
 * Mail gönderme (PHPMailer wrapper)
 */
function send_mail(string $to, string $subject, string $body, bool $isHtml = true): bool
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
        $mail->Port = (int)get_setting('mail_port', '587');
        $mail->CharSet = 'UTF-8';
        $mail->setFrom(get_setting('mail_from', get_setting('mail_user', '')), get_setting('mail_from_name', APP_NAME));
        $mail->addAddress($to);
        $mail->isHTML($isHtml);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->send();
        return true;
    }
    catch (\Exception $e) {
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