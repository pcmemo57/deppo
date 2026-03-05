<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

header('Content-Type: application/json; charset=utf-8');

$action = sanitize($_POST['action'] ?? $_GET['action'] ?? '');

switch ($action) {
    case 'forgot_password':
        $email = sanitize($_POST['email'] ?? '');
        if (empty($email))
            jsonResponse(false, 'Lütfen e-posta adresinizi girin.');

        $user = null;
        $role = '';
        $tables = [
            ROLE_ADMIN => 'tbl_dp_admins',
            ROLE_USER => 'tbl_dp_users',
            ROLE_REQUESTER => 'tbl_dp_requesters'
        ];

        foreach ($tables as $r => $t) {
            $found = Database::fetchOne("SELECT * FROM `$t` WHERE email = ? AND is_active = 1 AND hidden = 0", [$email]);
            if ($found) {
                $user = $found;
                $role = $r;
                break;
            }
        }

        if (!$user) {
            // Güvenlik için e-posta yoksa da başarılıymış gibi davranılabilir ama bu projede doğrudan hata veriyoruz (mevcut login mantığına paralel)
            jsonResponse(false, 'Bu e-posta adresiyle kayıtlı aktif bir kullanıcı bulunamadı.');
        }

        $otp = rand(100000, 999999);
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Eskilerini temizle
        Database::execute("DELETE FROM tbl_dp_password_resets WHERE email = ?", [$email]);

        Database::insert(
            "INSERT INTO tbl_dp_password_resets (email, token, role, expires_at) VALUES (?, ?, ?, ?)",
            [$email, $otp, $role, $expires]
        );

        $subject = "🔐 Şifre Sıfırlama Kodu";
        $siteName = get_setting('site_name', APP_NAME);

        $fullName = e($user['name']);
        if (!empty($user['surname'])) {
            $fullName .= ' ' . e($user['surname']);
        }

        $body = "
            <div style='font-family: sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #eee; border-radius: 10px; overflow: hidden;'>
                <div style='background: #4e73df; color: #fff; padding: 20px; text-align: center;'>
                    <h2 style='margin: 0;'>$siteName</h2>
                </div>
                <div style='padding: 30px; line-height: 1.6; text-align: center;'>
                    <p style='text-align: left;'>Merhaba <strong>$fullName</strong>,</p>
                    <p style='text-align: left;'>Hesabınız için bir şifre sıfırlama talebi aldık. Şifrenizi sıfırlamak için kullanacağınız onay kodu aşağıdadır:</p>
                    
                    <div style='margin: 30px 0; padding: 20px; background: #f8f9fc; border-radius: 10px; display: inline-block;'>
                        <span style='font-size: 32px; font-weight: bold; letter-spacing: 10px; color: #4e73df;'>$otp</span>
                    </div>

                    <p style='font-size: 13px; color: #777;'>Bu kod 1 saat boyunca geçerli olacaktır. Eğer bu talebi siz yapmadıysanız lütfen bu e-postayı dikkate almayınız.</p>
                </div>
                <div style='background: #f8f9fc; padding: 15px; text-align: center; font-size: 12px; color: #999;'>
                    Bu otomatik bir e-postadır, lütfen yanıtlamayınız.
                </div>
            </div>
        ";

        if (send_mail($email, $subject, $body)) {
            jsonResponse(true, 'Şifre sıfırlama kodunuz e-posta adresinize gönderildi.');
        } else {
            jsonResponse(false, 'E-posta gönderilirken bir hata oluştu. Lütfen sistem yöneticisine başvurun.');
        }
        break;

    case 'verify_otp':
        $email = sanitize($_POST['email'] ?? '');
        $otp = sanitize($_POST['otp'] ?? '');

        if (empty($email) || empty($otp))
            jsonResponse(false, 'E-posta ve kod gereklidir.');

        $reset = Database::fetchOne(
            "SELECT * FROM tbl_dp_password_resets WHERE email = ? AND token = ? AND expires_at > NOW()",
            [$email, $otp]
        );

        if (!$reset) {
            jsonResponse(false, 'Geçersiz veya süresi dolmuş kod.');
        }

        jsonResponse(true, 'Kod doğrulandı.');
        break;

    case 'reset_password':
        $email = sanitize($_POST['email'] ?? '');
        $otp = sanitize($_POST['otp'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm'] ?? '';

        if (empty($email) || empty($otp))
            jsonResponse(false, 'E-posta ve kod gereklidir.');
        if (strlen($password) < 6)
            jsonResponse(false, 'Şifre en az 6 karakter olmalıdır.');
        if ($password !== $confirm)
            jsonResponse(false, 'Şifreler uyuşmuyor.');

        $reset = Database::fetchOne(
            "SELECT * FROM tbl_dp_password_resets WHERE email = ? AND token = ? AND expires_at > NOW()",
            [$email, $otp]
        );

        if (!$reset) {
            jsonResponse(false, 'Geçersiz veya süresi dolmuş işlem.');
        }

        $email = $reset['email'];
        $role = $reset['role'];
        $table = '';
        if ($role === ROLE_ADMIN)
            $table = 'tbl_dp_admins';
        elseif ($role === ROLE_USER)
            $table = 'tbl_dp_users';
        elseif ($role === ROLE_REQUESTER)
            $table = 'tbl_dp_requesters';

        if (empty($table))
            jsonResponse(false, 'Kullanıcı rolü hatası.');

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            Database::execute("UPDATE `$table` SET password = ? WHERE email = ?", [$hashedPassword, $email]);
            Database::execute("DELETE FROM tbl_dp_password_resets WHERE email = ? AND token = ?", [$email, $otp]);
            jsonResponse(true, 'Şifreniz başarıyla güncellendi. Giriş yapabilirsiniz.');
        } catch (Exception $e) {
            jsonResponse(false, 'Şifre güncellenirken bir hata oluştu: ' . $e->getMessage());
        }
        break;

    default:
        jsonResponse(false, 'Geçersiz işlem.');
}
