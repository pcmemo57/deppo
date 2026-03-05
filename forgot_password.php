<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/functions.php';

$siteName = get_setting('site_name', 'Depo Yönetim Sistemi');
$googleFont = get_setting('google_font', 'default');
$fontFamily = "font-family: 'Source Sans Pro', sans-serif;";
if ($googleFont !== 'default') {
    $fontFamily = "font-family: '" . str_replace('+', ' ', $googleFont) . "', sans-serif;";
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($siteName) ?> — Şifre Kurtarma</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/css/adminlte.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/css/sweetalert2.min.css">
    <style>
        body {
            <?= $fontFamily ?>
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
        }

        .login-box {
            width: 450px;
        }

        .card {
            border-radius: 20px;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.5);
            border: none;
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 35px;
            text-align: center;
            color: #fff;
            border: none;
        }

        .card-header h3 {
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .card-body {
            padding: 40px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 14px;
            font-weight: 600;
            width: 100%;
            border-radius: 12px;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .form-control {
            border-radius: 12px;
            border: 2px solid #eef2f7;
            padding: 12px 15px;
            font-size: 0.95rem;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        /* OTP Inputs */
        .otp-container {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin: 25px 0;
        }

        .otp-input {
            width: 50px;
            height: 60px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 700;
            border-radius: 12px;
            border: 2px solid #eef2f7;
            transition: all 0.2s;
            background: #f8f9fc;
        }

        .otp-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            outline: none;
            background: #fff;
        }

        .step-content {
            display: none;
        }

        .step-content.active {
            display: block;
            animation: fadeIn 0.4s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <div class="login-box">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-shield-alt me-2"></i> Şifre Kurtarma</h3>
                <p class="mb-0 opacity-75 small" id="stepMessage">E-posta adresinizi girerek başlayın.</p>
            </div>
            <div class="card-body">
                <!-- Adım 1: E-posta -->
                <div id="step1" class="step-content active">
                    <form id="emailForm">
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted text-uppercase">E-posta Adresiniz</label>
                            <div class="input-group">
                                <input type="email" name="email" id="userEmail" class="form-control"
                                    placeholder="ornek@mail.com" required>
                                <span class="input-group-text bg-white border-start-0"
                                    style="border-radius: 0 12px 12px 0; border: 2px solid #eef2f7; border-left:0"><i
                                        class="fas fa-envelope text-muted"></i></span>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary text-white">
                            <i class="fas fa-paper-plane me-2"></i> DOĞRULAMA KODU GÖNDER
                        </button>
                    </form>
                </div>

                <!-- Adım 2: OTP -->
                <div id="step2" class="step-content">
                    <form id="otpForm">
                        <label class="form-label fw-bold small text-muted text-uppercase d-block text-center">6 Haneli
                            Onay Kodu</label>
                        <div class="otp-container">
                            <input type="text" class="otp-input" maxlength="1" pattern="\d*" inputmode="numeric">
                            <input type="text" class="otp-input" maxlength="1" pattern="\d*" inputmode="numeric">
                            <input type="text" class="otp-input" maxlength="1" pattern="\d*" inputmode="numeric">
                            <input type="text" class="otp-input" maxlength="1" pattern="\d*" inputmode="numeric">
                            <input type="text" class="otp-input" maxlength="1" pattern="\d*" inputmode="numeric">
                            <input type="text" class="otp-input" maxlength="1" pattern="\d*" inputmode="numeric">
                        </div>
                        <input type="hidden" name="otp" id="fullOtp">
                        <button type="submit" class="btn btn-primary text-white mb-3">
                            <i class="fas fa-check-circle me-2"></i> KODU DOĞRULA
                        </button>
                        <div class="text-center">
                            <button type="button" class="btn btn-link btn-sm text-muted text-decoration-none"
                                id="resendCode">Kodu tekrar gönder</button>
                        </div>
                    </form>
                </div>

                <!-- Adım 3: Yeni Şifre -->
                <div id="step3" class="step-content">
                    <form id="resetForm">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">Yeni Şifre</label>
                            <input type="password" name="password" id="password" class="form-control"
                                placeholder="••••••••" required minlength="6">
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted text-uppercase">Yeni Şifre
                                (Yeniden)</label>
                            <input type="password" name="confirm" class="form-control" placeholder="••••••••" required>
                        </div>
                        <button type="submit" class="btn btn-primary text-white">
                            <i class="fas fa-lock me-2"></i> ŞİFREYİ GÜNCELLE
                        </button>
                    </form>
                </div>

                <div class="mt-4 text-center">
                    <a href="login.php" class="text-muted small text-decoration-none"><i
                            class="fas fa-arrow-left me-2"></i> Giriş Sayfasına Dön</a>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= BASE_URL ?>/assets/vendor/js/jquery.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/vendor/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/vendor/js/sweetalert2.all.min.js"></script>
    <script>
        const steps = {
            1: { title: 'Şifre Kurtarma', msg: 'E-posta adresinizi girerek başlayın.' },
            2: { title: 'Onay Kodu', msg: 'E-postanıza gelen 6 haneli kodu girin.' },
            3: { title: 'Yeni Şifre', msg: 'Lütfen yeni ve güvenli bir şifre belirleyin.' }
        };

        function showStep(n) {
            $('.step-content').removeClass('active');
            $(`#step${n}`).addClass('active');
            $('#stepMessage').text(steps[n].msg);
            $('.card-header h3').html(`<i class="fas fa-${n === 1 ? 'shield-alt' : (n === 2 ? 'key' : 'user-shield')} me-2"></i> ${steps[n].title}`);
            if (n === 2) $('.otp-input').first().focus();
            if (n === 3) $('#password').focus();
        }

        // OTP Input Logic
        $('.otp-input').on('keyup', function (e) {
            const $this = $(this);
            if (e.key >= 0 && e.key <= 9) {
                $this.next('.otp-input').focus();
            } else if (e.key === 'Backspace') {
                $this.prev('.otp-input').focus();
            }
            updateFullOtp();
        });

        $('.otp-input').on('paste', function (e) {
            const data = e.originalEvent.clipboardData.getData('text');
            if (data.length === 6 && /^\d+$/.test(data)) {
                $('.otp-input').each(function (i) {
                    $(this).val(data[i]);
                });
                updateFullOtp();
                $('#otpForm').submit();
            }
        });

        function updateFullOtp() {
            let otp = '';
            $('.otp-input').each(function () { otp += $(this).val(); });
            $('#fullOtp').val(otp);
        }

        // Form Step 1: E-mail
        $('#emailForm').on('submit', function (e) {
            e.preventDefault();
            const btn = $(this).find('button');
            const originalHtml = btn.html();
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Gönderiliyor...');

            $.post('api/auth.php?action=forgot_password', { email: $('#userEmail').val() }, function (r) {
                btn.prop('disabled', false).html(originalHtml);
                if (r.success) {
                    showStep(2);
                } else {
                    Swal.fire({ icon: 'error', title: 'Hata!', text: r.message });
                }
            }, 'json');
        });

        // Form Step 2: OTP Verify
        $('#otpForm').on('submit', function (e) {
            e.preventDefault();
            const otp = $('#fullOtp').val();
            if (otp.length !== 6) {
                Swal.fire({ icon: 'warning', title: 'Eksik Kod', text: 'Lütfen 6 haneli kodu tam girin.' });
                return;
            }

            const btn = $(this).find('button');
            const originalHtml = btn.html();
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Doğrulanıyor...');

            $.post('api/auth.php?action=verify_otp', { email: $('#userEmail').val(), otp: otp }, function (r) {
                btn.prop('disabled', false).html(originalHtml);
                if (r.success) {
                    showStep(3);
                } else {
                    Swal.fire({ icon: 'error', title: 'Hata!', text: r.message });
                }
            }, 'json');
        });

        // Form Step 3: Reset Password
        $('#resetForm').on('submit', function (e) {
            e.preventDefault();
            const btn = $(this).find('button');
            const originalHtml = btn.html();
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Güncelleniyor...');

            const data = {
                email: $('#userEmail').val(),
                otp: $('#fullOtp').val(),
                password: $('#password').val(),
                confirm: $(this).find('[name="confirm"]').val(),
                action: 'reset_password'
            };

            $.post('api/auth.php', data, function (r) {
                btn.prop('disabled', false).html(originalHtml);
                if (r.success) {
                    Swal.fire({ icon: 'success', title: 'Başarılı!', text: r.message }).then(() => {
                        window.location.href = 'login.php';
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Hata!', text: r.message });
                }
            }, 'json');
        });

        $('#resendCode').on('click', function () {
            $('#emailForm').submit();
        });
    </script>
</body>

</html>