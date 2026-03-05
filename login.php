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
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    // Rol artık manuel seçilmiyor, e-postadan bulacağız

    if (empty($email)) {
        $error = 'Lütfen e-posta adresinizi girin.';
    } else {
        $user = null;
        $table = '';
        $role = '';

        // Sırayla tabloları kontrol et
        $roles_to_check = [
            ROLE_ADMIN => 'tbl_dp_admins',
            ROLE_USER => 'tbl_dp_users',
            ROLE_REQUESTER => 'tbl_dp_requesters'
        ];

        foreach ($roles_to_check as $r => $t) {
            $found = Database::fetchOne(
                "SELECT * FROM `$t` WHERE email = ? AND is_active = 1 AND hidden = 0",
                [$email]
            );
            if ($found) {
                $user = $found;
                $table = $t;
                $role = $r;
                break;
            }
        }

        if (!$user) {
            $error = 'Bu e-posta adresiyle kayıtlı aktif bir kullanıcı bulunamadı.';
        } else {
            // Şifre kontrolü (Tüm roller için şifre zorunlu hale getirildi)
            if (empty($password)) {
                $error = 'Lütfen şifrenizi girin.';
                $login_allowed = false;
            } elseif (!verifyPassword($password, $user['password'])) {
                $error = 'Şifre hatalı.';
                $login_allowed = false;
            } else {
                $login_allowed = true;
            }

            if ($login_allowed) {
                // Başarılı giriş
                session_regenerate_id(true);
                $_SESSION['dp_user_id'] = $user['id'];
                $_SESSION['dp_user_name'] = $user['name'];
                $_SESSION['dp_user_email'] = $user['email'];
                $_SESSION['dp_role'] = $role;

                // Son giriş güncelle
                Database::execute("UPDATE `$table` SET last_login = NOW() WHERE id = ?", [$user['id']]);

                header('Location: ' . BASE_URL . '/index.php');
                exit;
            }
        }
    }
}

$siteName = get_setting('site_name', 'Depo Yönetim Sistemi');
$googleFont = get_setting('google_font', 'default');
$fontLink = '';
$fontFamily = "font-family: 'Source Sans Pro', sans-serif;";
$googleFonts = [
    'Roboto' => BASE_URL . '/assets/vendor/fonts/Roboto.css',
    'Open+Sans' => BASE_URL . '/assets/vendor/fonts/Open_Sans.css',
    'Lato' => BASE_URL . '/assets/vendor/fonts/Lato.css',
    'Montserrat' => BASE_URL . '/assets/vendor/fonts/Montserrat.css',
    'Poppins' => BASE_URL . '/assets/vendor/fonts/Poppins.css',
    'Nunito' => BASE_URL . '/assets/vendor/fonts/Nunito.css',
    'Raleway' => BASE_URL . '/assets/vendor/fonts/Raleway.css',
    'Inter' => BASE_URL . '/assets/vendor/fonts/Inter.css',
    'Ubuntu' => BASE_URL . '/assets/vendor/fonts/Ubuntu.css',
    'Outfit' => BASE_URL . '/assets/vendor/fonts/Outfit.css',
    'Source+Sans+Pro' => BASE_URL . '/assets/vendor/fonts/Source_Sans_Pro.css',
    'Playfair+Display' => BASE_URL . '/assets/vendor/fonts/Playfair_Display.css',
    'Merriweather' => BASE_URL . '/assets/vendor/fonts/Merriweather.css',
    'Oswald' => BASE_URL . '/assets/vendor/fonts/Oswald.css',
    'Quicksand' => BASE_URL . '/assets/vendor/fonts/Quicksand.css',
    'Fira+Sans' => BASE_URL . '/assets/vendor/fonts/Fira_Sans.css',
    'Josefin+Sans' => BASE_URL . '/assets/vendor/fonts/Josefin_Sans.css',
    'Space+Grotesk' => BASE_URL . '/assets/vendor/fonts/Space_Grotesk.css',
    'Lora' => BASE_URL . '/assets/vendor/fonts/Lora.css',
    'Cabin' => BASE_URL . '/assets/vendor/fonts/Cabin.css',
    'Zilla+Slab' => BASE_URL . '/assets/vendor/fonts/Zilla_Slab.css',
];
$selectedFont = ($googleFont === 'default') ? 'Source+Sans+Pro' : $googleFont;

if (isset($googleFonts[$selectedFont])) {
    $fontVersion = '1.1.9'; // Cache buster
    $fontLink = '<link href="' . $googleFonts[$selectedFont] . '?v=' . $fontVersion . '" rel="stylesheet">';
    $fontFamily = "font-family: '" . str_replace('+', ' ', $selectedFont) . "', sans-serif;";
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($siteName) ?> — Giriş</title>
    <?= $fontLink ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/css/adminlte.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/css/all.min.css">
    <style>
        body,
        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        .brand-text,
        .btn,
        .form-control {
            <?= $fontFamily ?>
        }

        .login-page {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
        }

        .login-box {
            width: 400px;
        }

        .card {
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            border: none;
        }

        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px 15px 0 0 !important;
            padding: 30px;
            text-align: center;
        }

        .card-header h1 {
            color: #fff;
            font-size: 1.1rem;
            margin: 0;
            font-weight: 400;
            letter-spacing: 1px;
        }

        .card-header .brand-icon {
            font-size: 3rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 10px;
        }

        .card-body {
            padding: 30px;
        }

        .form-control {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
            letter-spacing: 1px;
            width: 100%;
            transition: all 0.3s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .role-selector label {
            cursor: pointer;
        }

        .role-selector input[type="radio"] {
            display: none;
        }

        .role-btn {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 10px 15px;
            text-align: center;
            transition: all 0.3s;
            font-size: 0.85rem;
        }

        .role-selector input[type="radio"]:checked+.role-btn {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }

        .role-btn i {
            display: block;
            font-size: 1.5rem;
            margin-bottom: 5px;
        }

        .input-group-text {
            background: #f8f9fa;
            border-radius: 0 8px 8px 0;
            border: 2px solid #e9ecef;
            border-left: 0;
        }
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

                    <!-- Rol seçimi kaldırıldı, e-posta ile otomatik tespit ediliyor -->

                    <div class="mb-3">
                        <label class="form-label">E-posta Adresi</label>
                        <div class="input-group">
                            <input type="email" name="email" id="emailInput" class="form-control"
                                placeholder="ornek@mail.com" value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
                            <span class="input-group-text"><i class="fas fa-envelope text-muted"></i></span>
                        </div>
                    </div>

                    <div class="mb-3" id="passwordGroup">
                        <label class="form-label">Şifre</label>
                        <div class="input-group">
                            <input type="password" name="password" class="form-control" placeholder="••••••••"
                                id="passwordInput">
                            <span class="input-group-text cursor-pointer" id="togglePass" style="cursor:pointer">
                                <i class="fas fa-eye text-muted" id="eyeIcon"></i>
                            </span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-login text-white">
                        <i class="fas fa-sign-in-alt me-2"></i> GİRİŞ YAP
                    </button>
                    <div class="text-center mt-3">
                        <a href="forgot_password.php" class="text-muted small">Şifremi Unuttum</a>
                    </div>
                </form>
            </div>
        </div>
        <p class="text-center text-white-50 mt-3 small"><?= e(get_setting('footer_text', '© 2026')) ?></p>
    </div>

    <script src="<?= BASE_URL ?>/assets/vendor/js/bootstrap.bundle.min.js"></script>
    <script>
        // Şifre göster/gizle
        document.getElementById('togglePass').addEventListener('click', function () {
            const inp = document.getElementById('passwordInput');
            const ico = document.getElementById('eyeIcon');
            if (inp.type === 'password') {
                inp.type = 'text';
                ico.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                inp.type = 'password';
                ico.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });

        // Şifre alanı her zaman görünür olmalı
        const passwordInput = document.getElementById('passwordInput');
        passwordInput.required = true;

        // Sayfa yüklendiğinde mevcut e-posta varsa kontrol et
        window.addEventListener('load', function () {
            const emailInput = document.getElementById('emailInput');
            if (emailInput.value) {
                emailInput.dispatchEvent(new Event('input'));
            }
        });
    </script>
</body>

</html>