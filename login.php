<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';

// Zaten giriş yapılmışsa dashboard'a yönlendir
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';
    $role     = sanitize($_POST['role']     ?? '');

    if (empty($email) || empty($password) || empty($role)) {
        $error = 'Lütfen tüm alanları doldurun.';
    } else {
        $user = null;
        $table = '';

        if ($role === ROLE_ADMIN) {
            $table = 'tbl_dp_admins';
        } elseif ($role === ROLE_USER) {
            $table = 'tbl_dp_users';
        } elseif ($role === ROLE_REQUESTER) {
            $table = 'tbl_dp_requesters';
        }

        if ($table) {
            // Talep edenler şifresiz (e-posta tabanlı)
            if ($role === ROLE_REQUESTER) {
                $user = Database::fetchOne(
                    "SELECT * FROM `$table` WHERE email = ? AND is_active = 1 AND hidden = 0",
                    [$email]
                );
                if (!$user) {
                    $error = 'Geçersiz e-posta veya erişim yetkiniz yok.';
                }
            } else {
                $user = Database::fetchOne(
                    "SELECT * FROM `$table` WHERE email = ? AND is_active = 1 AND hidden = 0",
                    [$email]
                );
                if (!$user || !verifyPassword($password, $user['password'])) {
                    $error = 'E-posta veya şifre hatalı.';
                    $user  = null;
                }
            }
        }

        if ($user && !$error) {
            // Başarılı giriş
            session_regenerate_id(true);
            $_SESSION['dp_user_id']    = $user['id'];
            $_SESSION['dp_user_name']  = $user['name'];
            $_SESSION['dp_user_email'] = $user['email'];
            $_SESSION['dp_role']       = $role;

            // Son giriş güncelle (requesters tablosu için farklı sütun yok, atlıyoruz)
            if ($role !== ROLE_REQUESTER) {
                Database::execute("UPDATE `$table` SET last_login = NOW() WHERE id = ?", [$user['id']]);
            }

            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }
    }
}

$siteName = get_setting('site_name', 'Depo Yönetim Sistemi');
$googleFont = get_setting('google_font', 'default');
$fontLink = '';
$fontFamily = "font-family: 'Source Sans Pro', sans-serif;";
$googleFonts = [
    'Roboto'      => 'https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap',
    'Open+Sans'   => 'https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700&display=swap',
    'Lato'        => 'https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700&display=swap',
    'Montserrat'  => 'https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap',
    'Poppins'     => 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap',
    'Nunito'      => 'https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap',
    'Raleway'     => 'https://fonts.googleapis.com/css2?family=Raleway:wght@300;400;600;700&display=swap',
    'Inter'       => 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap',
    'Ubuntu'      => 'https://fonts.googleapis.com/css2?family=Ubuntu:wght@300;400;500;700&display=swap',
    'Outfit'      => 'https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap',
];
if ($googleFont !== 'default' && isset($googleFonts[$googleFont])) {
    $fontLink   = '<link href="' . $googleFonts[$googleFont] . '" rel="stylesheet">';
    $fontFamily = "font-family: '" . str_replace('+', ' ', $googleFont) . "', sans-serif;";
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($siteName) ?> — Giriş</title>
    <?= $fontLink ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { <?= $fontFamily ?> }
        .login-page { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%); min-height: 100vh; }
        .login-box { width: 400px; }
        .card { border-radius: 15px; box-shadow: 0 20px 60px rgba(0,0,0,0.4); border: none; }
        .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 15px 15px 0 0 !important; padding: 30px; text-align: center; }
        .card-header h1 { color: #fff; font-size: 1.1rem; margin: 0; font-weight: 400; letter-spacing: 1px; }
        .card-header .brand-icon { font-size: 3rem; color: rgba(255,255,255,0.9); margin-bottom: 10px; }
        .card-body { padding: 30px; }
        .form-control { border-radius: 8px; border: 2px solid #e9ecef; transition: all 0.3s; }
        .form-control:focus { border-color: #667eea; box-shadow: 0 0 0 0.2rem rgba(102,126,234,0.25); }
        .btn-login { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 8px; padding: 12px; font-weight: 600; letter-spacing: 1px; width: 100%; transition: all 0.3s; }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102,126,234,0.4); }
        .role-selector label { cursor: pointer; }
        .role-selector input[type="radio"] { display: none; }
        .role-btn { border: 2px solid #dee2e6; border-radius: 8px; padding: 10px 15px; text-align: center; transition: all 0.3s; font-size: 0.85rem; }
        .role-selector input[type="radio"]:checked + .role-btn { border-color: #667eea; background: rgba(102,126,234,0.1); color: #667eea; }
        .role-btn i { display: block; font-size: 1.5rem; margin-bottom: 5px; }
        .input-group-text { background: #f8f9fa; border-radius: 0 8px 8px 0; border: 2px solid #e9ecef; border-left: 0; }
    </style>
</head>
<body class="login-page">
<div class="login-box mx-auto">
    <div class="card mt-5">
        <div class="card-header">
            <div class="brand-icon"><i class="fas fa-warehouse"></i></div>
            <h1><?= e($siteName) ?></h1>
            <small style="color:rgba(255,255,255,0.7)">Depo Yönetim Sistemi</small>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible">
                <i class="fas fa-exclamation-triangle me-2"></i> <?= e($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <form method="POST" id="loginForm">
                <input type="hidden" name="csrf" value="<?= generateCsrfToken() ?>">

                <!-- Rol Seçimi -->
                <div class="mb-3">
                    <label class="form-label fw-bold text-muted small">OTURUM TÜRÜ SEÇİN</label>
                    <div class="row g-2 role-selector">
                        <div class="col-4">
                            <label>
                                <input type="radio" name="role" value="admin" <?= (!isset($_POST['role']) || $_POST['role']==='admin') ? 'checked' : '' ?>>
                                <div class="role-btn">
                                    <i class="fas fa-user-shield"></i>
                                    Admin
                                </div>
                            </label>
                        </div>
                        <div class="col-4">
                            <label>
                                <input type="radio" name="role" value="user" <?= (isset($_POST['role']) && $_POST['role']==='user') ? 'checked' : '' ?>>
                                <div class="role-btn">
                                    <i class="fas fa-user-cog"></i>
                                    Yönetici
                                </div>
                            </label>
                        </div>
                        <div class="col-4">
                            <label>
                                <input type="radio" name="role" value="requester" <?= (isset($_POST['role']) && $_POST['role']==='requester') ? 'checked' : '' ?>>
                                <div class="role-btn">
                                    <i class="fas fa-user-tag"></i>
                                    Talep Eden
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">E-posta Adresi</label>
                    <div class="input-group">
                        <input type="email" name="email" class="form-control" placeholder="ornek@mail.com" value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
                        <span class="input-group-text"><i class="fas fa-envelope text-muted"></i></span>
                    </div>
                </div>

                <div class="mb-3" id="passwordGroup" style="<?= (isset($_POST['role']) && $_POST['role']==='requester') ? 'display:none' : '' ?>">
                    <label class="form-label">Şifre</label>
                    <div class="input-group">
                        <input type="password" name="password" class="form-control" placeholder="••••••••" id="passwordInput">
                        <span class="input-group-text cursor-pointer" id="togglePass" style="cursor:pointer">
                            <i class="fas fa-eye text-muted" id="eyeIcon"></i>
                        </span>
                    </div>
                </div>

                <button type="submit" class="btn btn-login text-white">
                    <i class="fas fa-sign-in-alt me-2"></i> GİRİŞ YAP
                </button>
            </form>
        </div>
    </div>
    <p class="text-center text-white-50 mt-3 small"><?= e(get_setting('footer_text', '© 2026')) ?></p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Şifre göster/gizle
document.getElementById('togglePass').addEventListener('click', function(){
    const inp = document.getElementById('passwordInput');
    const ico = document.getElementById('eyeIcon');
    if(inp.type === 'password'){
        inp.type = 'text';
        ico.classList.replace('fa-eye','fa-eye-slash');
    } else {
        inp.type = 'password';
        ico.classList.replace('fa-eye-slash','fa-eye');
    }
});

// Rol değişince şifre alanını gizle/göster
document.querySelectorAll('input[name="role"]').forEach(function(radio){
    radio.addEventListener('change', function(){
        document.getElementById('passwordGroup').style.display =
            this.value === 'requester' ? 'none' : '';
        document.getElementById('passwordInput').required = this.value !== 'requester';
    });
});
</script>
</body>
</html>
