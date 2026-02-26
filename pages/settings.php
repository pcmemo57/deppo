<?php
/**
 * Ayarlar Sayfası (sadece admin)
 */
requireRole(ROLE_ADMIN);

$googleFontList = [
    'default' => 'AdminLTE Varsayılanı (Source Sans Pro)',
    'Roboto' => 'Roboto',
    'Open+Sans' => 'Open Sans',
    'Lato' => 'Lato',
    'Montserrat' => 'Montserrat',
    'Poppins' => 'Poppins',
    'Nunito' => 'Nunito',
    'Raleway' => 'Raleway',
    'Inter' => 'Inter',
    'Ubuntu' => 'Ubuntu',
    'Outfit' => 'Outfit',
];
?>

<div class="row">
    <div class="col-12">
        <div class="card card-primary card-tabs">
            <div class="card-header p-0 border-bottom-0">
                <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-mail"><i
                                class="fas fa-envelope me-1"></i>Mail Ayarları</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-appearance"><i
                                class="fas fa-paint-brush me-1"></i>Görünüm</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-currency"><i
                                class="fas fa-money-bill-wave me-1"></i>Döviz Kurları</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-general"><i
                                class="fas fa-cog me-1"></i>Genel</a></li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">

                    <!-- ═══════════ MAIL ═══════════ -->
                    <div class="tab-pane fade show active" id="tab-mail">
                        <form id="formMail">
                            <input type="hidden" name="action" value="save_mail">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">SMTP Host</label>
                                        <input type="text" name="mail_host" class="form-control"
                                            value="<?= e(get_setting('mail_host', 'smtp.gmail.com'))?>"
                                            placeholder="smtp.gmail.com">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">SMTP Port</label>
                                        <input type="number" name="mail_port" class="form-control"
                                            value="<?= e(get_setting('mail_port', '587'))?>" placeholder="587">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Şifreleme</label>
                                        <select name="mail_secure" class="form-select">
                                            <?php foreach (['tls' => 'TLS', 'ssl' => 'SSL', '' => 'Yok'] as $v => $l): ?>
                                            <option value="<?= e($v)?>"
                                                <?=get_setting('mail_secure', 'tls') === $v ? 'selected' : ''?>>
                                                <?= e($l)?>
                                            </option>
                                            <?php
endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">SMTP Kullanıcı (E-posta)</label>
                                        <input type="email" name="mail_user" class="form-control"
                                            value="<?= e(get_setting('mail_user', ''))?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">SMTP Şifre</label>
                                        <input type="password" name="mail_pass" class="form-control"
                                            placeholder="••••••••" autocomplete="new-password">
                                        <small class="text-muted">Değiştirmek istemiyorsanız boş bırakın.</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Gönderen Ad</label>
                                        <input type="text" name="mail_from_name" class="form-control"
                                            value="<?= e(get_setting('mail_from_name', APP_NAME))?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Gönderen E-posta</label>
                                        <input type="email" name="mail_from" class="form-control"
                                            value="<?= e(get_setting('mail_from', ''))?>">
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary"><i
                                        class="fas fa-save me-1"></i>Kaydet</button>
                                <button type="button" class="btn btn-outline-secondary" id="btnTestMail">
                                    <i class="fas fa-paper-plane me-1"></i>Test Maili Gönder
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- ═══════════ GÖRÜNÜM ═══════════ -->
                    <div class="tab-pane fade" id="tab-appearance">
                        <form id="formAppearance">
                            <input type="hidden" name="action" value="save_appearance">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card card-outline card-secondary mb-3">
                                        <div class="card-header">
                                            <h6 class="m-0">Header</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label class="form-label">Arkaplan Rengi</label>
                                                <div class="d-flex align-items-center gap-2">
                                                    <input type="color" name="header_bg"
                                                        class="form-control form-control-color"
                                                        value="<?= e(get_setting('header_bg', '#343a40'))?>"
                                                        style="width:60px;height:38px">
                                                    <input type="text" name="header_bg_hex" class="form-control"
                                                        value="<?= e(get_setting('header_bg', '#343a40'))?>"
                                                        maxlength="7" placeholder="#343a40">
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Yazı Rengi</label>
                                                <div class="d-flex align-items-center gap-2">
                                                    <input type="color" name="header_color"
                                                        class="form-control form-control-color"
                                                        value="<?= e(get_setting('header_color', '#ffffff'))?>"
                                                        style="width:60px;height:38px">
                                                    <input type="text" name="header_color_hex" class="form-control"
                                                        value="<?= e(get_setting('header_color', '#ffffff'))?>"
                                                        maxlength="7" placeholder="#ffffff">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card card-outline card-secondary mb-3">
                                        <div class="card-header">
                                            <h6 class="m-0">Footer</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label class="form-label">Footer Metni</label>
                                                <input type="text" name="footer_text" class="form-control"
                                                    value="<?= e(get_setting('footer_text', ''))?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Arkaplan Rengi</label>
                                                <div class="d-flex align-items-center gap-2">
                                                    <input type="color" name="footer_bg"
                                                        class="form-control form-control-color"
                                                        value="<?= e(get_setting('footer_bg', '#343a40'))?>"
                                                        style="width:60px;height:38px">
                                                    <input type="text" name="footer_bg_hex" class="form-control"
                                                        value="<?= e(get_setting('footer_bg', '#343a40'))?>"
                                                        maxlength="7" placeholder="#343a40">
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Yazı Rengi</label>
                                                <div class="d-flex align-items-center gap-2">
                                                    <input type="color" name="footer_color"
                                                        class="form-control form-control-color"
                                                        value="<?= e(get_setting('footer_color', '#ffffff'))?>"
                                                        style="width:60px;height:38px">
                                                    <input type="text" name="footer_color_hex" class="form-control"
                                                        value="<?= e(get_setting('footer_color', '#ffffff'))?>"
                                                        maxlength="7" placeholder="#ffffff">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Google Font Seçimi</label>
                                        <select name="google_font" class="form-select">
                                            <?php foreach ($googleFontList as $fontKey => $fontLabel): ?>
                                            <option value="<?= e($fontKey)?>"
                                                <?=get_setting('google_font', 'default') === $fontKey ? 'selected' : ''?>>
                                                <?= e($fontLabel)?>
                                            </option>
                                            <?php
endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary"><i
                                    class="fas fa-save me-1"></i>Kaydet</button>
                        </form>
                    </div>

                    <!-- ═══════════ DÖVİZ KURLARI ═══════════ -->
                    <div class="tab-pane fade" id="tab-currency">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card card-outline card-info">
                                    <div class="card-header">
                                        <h6 class="m-0">TCMB Kurları</h6>
                                        <small class="text-muted">
                                            Son güncelleme:
                                            <?= e(get_setting('currency_updated') ?: 'Henüz güncellenmedi')?>
                                        </small>
                                    </div>
                                    <div class="card-body">
                                        <div class="row text-center mb-3">
                                            <div class="col-6">
                                                <div class="p-3 bg-light rounded">
                                                    <span class="text-muted d-block small">USD / TL</span>
                                                    <span class="h4 text-success" id="usdDisplay">
                                                        <?= e(get_setting('usd_rate', '0') > 0 ? formatPrice((float)get_setting('usd_rate', '0')) : '—')?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="p-3 bg-light rounded">
                                                    <span class="text-muted d-block small">EUR / TL</span>
                                                    <span class="h4 text-primary" id="eurDisplay">
                                                        <?= e(get_setting('eur_rate', '0') > 0 ? formatPrice((float)get_setting('eur_rate', '0')) : '—')?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <button class="btn btn-info w-100" id="btnUpdateCurrency">
                                            <i class="fas fa-sync-alt me-1"></i>TCMB'den Kurları Güncelle
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card card-outline card-warning">
                                    <div class="card-header">
                                        <h6 class="m-0">Manuel Kur Girişi</h6>
                                    </div>
                                    <div class="card-body">
                                        <form id="formCurrency">
                                            <input type="hidden" name="action" value="save_currency">
                                            <div class="mb-3">
                                                <label class="form-label">USD / TL</label>
                                                <input type="text" name="usd_rate" class="form-control price-format"
                                                    value="<?= e(formatPrice((float)get_setting('usd_rate', '0')))?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">EUR / TL</label>
                                                <input type="text" name="eur_rate" class="form-control price-format"
                                                    value="<?= e(formatPrice((float)get_setting('eur_rate', '0')))?>">
                                            </div>
                                            <button type="submit" class="btn btn-warning w-100">
                                                <i class="fas fa-save me-1"></i>Manuel Kaydet
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ═══════════ GENEL ═══════════ -->
                    <div class="tab-pane fade" id="tab-general">
                        <form id="formGeneral">
                            <input type="hidden" name="action" value="save_general">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Site Adı</label>
                                        <input type="text" name="site_name" class="form-control"
                                            value="<?= e(get_setting('site_name', APP_NAME))?>">
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary"><i
                                    class="fas fa-save me-1"></i>Kaydet</button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Color picker ↔ hex input senkronizasyonu
    $('input[type="color"]').on('input', function () {
        var hexInput = $(this).next('input[type="text"]');
        hexInput.val($(this).val());
    });
    $('input[name$="_hex"]').on('input', function () {
        var val = $(this).val();
        if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
            $(this).prev('input[type="color"]').val(val);
            // Hidden alanı da güncelle
            var n = $(this).attr('name').replace('_hex', '');
            $(this).closest('form').find('input[name="' + n + '"]').val(val);
        }
    });

    // Form gönderimi (genel fonksiyon)
    function submitSettingsForm(formId) {
        var form = $('#' + formId);
        var data = form.serializeArray();
        // color hex'leri ana alana yaz
        form.find('input[name$="_hex"]').each(function () {
            var n = $(this).attr('name').replace('_hex', '');
            data = data.filter(function (d) { return d.name !== n; });
            data.push({ name: n, value: $(this).val() });
        });

        $.post('<?= BASE_URL?>/api/settings.php', $.param(data), function (r) {
            if (r.success) { showSuccess(r.message || 'Kaydedildi!'); setTimeout(() => location.reload(), 1200); }
            else showError(r.message || 'Bir hata oluştu.');
        }, 'json').fail(function () { showError('Bağlantı hatası.'); });
    }

    $('#formMail').on('submit', function (e) { e.preventDefault(); submitSettingsForm('formMail'); });
    $('#formAppearance').on('submit', function (e) { e.preventDefault(); submitSettingsForm('formAppearance'); });
    $('#formCurrency').on('submit', function (e) { e.preventDefault(); submitSettingsForm('formCurrency'); });
    $('#formGeneral').on('submit', function (e) { e.preventDefault(); submitSettingsForm('formGeneral'); });

    // Test maili
    $('#btnTestMail').on('click', function () {
        Swal.fire({
            title: 'Test Maili Gönder',
            input: 'email',
            inputPlaceholder: 'test@ornek.com',
            showCancelButton: true,
            confirmButtonText: 'Gönder',
            cancelButtonText: 'İptal'
        }).then(function (result) {
            if (result.isConfirmed && result.value) {
                $.post('<?= BASE_URL?>/api/settings.php', { action: 'test_mail', to: result.value }, function (r) {
                    if (r.success) showSuccess('Test maili gönderildi!');
                    else showError(r.message || 'Mail gönderilemedi.');
                }, 'json');
            }
        });
    });

    // TCMB kurları güncelle
    $('#btnUpdateCurrency').on('click', function () {
        var btn = $(this).html('<i class="fas fa-spinner fa-spin me-1"></i>Güncelleniyor...').prop('disabled', true);
        $.post('<?= BASE_URL?>/api/settings.php', { action: 'update_currency' }, function (r) {
            if (r.success) {
                showSuccess('Kurlar güncellendi!');
                if (r.data) {
                    $('#usdDisplay').text(r.data.usd_formatted);
                    $('#eurDisplay').text(r.data.eur_formatted);
                }
                setTimeout(() => location.reload(), 1500);
            } else showError(r.message || 'Güncelleme başarısız.');
        }, 'json').always(function () {
            btn.html('<i class="fas fa-sync-alt me-1"></i>TCMB\'den Kurları Güncelle').prop('disabled', false);
        });
    });
</script>