<?php
/**
 * Ayarlar Sayfası (sadece admin)
 */
requireRole(ROLE_ADMIN);

$activeTab = $_GET['tab'] ?? 'general';
$tabs = ['general', 'appearance', 'currency', 'data-mgmt', 'pdf', 'backup', 'mail'];
if (!in_array($activeTab, $tabs)) {
    $activeTab = 'general';
}

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
    'Playfair+Display' => 'Playfair Display',
    'Merriweather' => 'Merriweather',
    'Oswald' => 'Oswald',
    'Quicksand' => 'Quicksand',
    'Fira+Sans' => 'Fira Sans',
    'Josefin+Sans' => 'Josefin Sans',
    'Space+Grotesk' => 'Space Grotesk',
    'Lora' => 'Lora',
    'Cabin' => 'Cabin',
    'Zilla+Slab' => 'Zilla Slab',
];
?>

<style>
    /* ══════════════════════════════════════════
       CUSTOM SEGMENTED TABS STYLING
    ══════════════════════════════════════════ */
    .settings-card {
        border: none !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05) !important;
        border-radius: 12px !important;
        overflow: hidden;
    }

    .settings-tabs-wrapper {
        background: #f8fafc;
        padding: 10px 15px 0 15px;
        border-bottom: 1px solid #e2e8f0;
    }

    .nav-segmented {
        display: flex;
        background: #e2e8f0;
        padding: 4px;
        border-radius: 10px;
        border: none !important;
        gap: 2px;
        width: fit-content;
        margin-bottom: 10px;
    }

    .nav-segmented .nav-item {
        margin: 0;
    }

    .nav-segmented .nav-link {
        border: none !important;
        border-radius: 8px !important;
        padding: 8px 18px !important;
        font-size: 0.85rem !important;
        font-weight: 600 !important;
        color: #64748b !important;
        background: transparent !important;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .nav-segmented .nav-link i {
        font-size: 0.95rem;
        opacity: 0.8;
    }

    .nav-segmented .nav-link:hover {
        color: #1e293b !important;
        background: rgba(255, 255, 255, 0.4) !important;
    }

    .nav-segmented .nav-link.active {
        background: #fff !important;
        color: #1a56db !important;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08) !important;
    }

    /* Özel renkli tablar */
    .nav-segmented .nav-link.tab-danger.active {
        color: #dc2626 !important;
    }

    .nav-segmented .nav-link.tab-backup.active {
        color: #059669 !important;
    }

    .card-tabs .card-body {
        padding: 25px !important;
    }
</style>

<div class="row">
    <div class="col-12">
        <div class="card card-primary card-tabs settings-card">
            <div class="settings-tabs-wrapper">
                <ul class="nav nav-tabs nav-segmented" id="settingsTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link <?= $activeTab === 'general' ? 'active' : '' ?>" data-bs-toggle="tab"
                            href="#tab-general">
                            <i class="fas fa-cog"></i>Genel
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeTab === 'appearance' ? 'active' : '' ?>" data-bs-toggle="tab"
                            href="#tab-appearance">
                            <i class="fas fa-paint-brush"></i>Görünüm
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeTab === 'currency' ? 'active' : '' ?>" data-bs-toggle="tab"
                            href="#tab-currency">
                            <i class="fas fa-money-bill-wave"></i>Döviz Kurları
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link tab-danger <?= $activeTab === 'data-mgmt' ? 'active' : '' ?>"
                            data-bs-toggle="tab" href="#tab-data-mgmt">
                            <i class="fas fa-database text-danger"></i>Veri Yönetimi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeTab === 'pdf' ? 'active' : '' ?>" data-bs-toggle="tab"
                            href="#tab-pdf">
                            <i class="fas fa-file-pdf"></i>PDF Ayarları
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link tab-backup <?= $activeTab === 'backup' ? 'active' : '' ?>"
                            data-bs-toggle="tab" href="#tab-backup">
                            <i class="fas fa-hdd text-success"></i>Yedekleme
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeTab === 'mail' ? 'active' : '' ?>" data-bs-toggle="tab"
                            href="#tab-mail">
                            <i class="fas fa-envelope"></i>Mail Ayarları
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">

                    <!-- ═══════════ MAIL ═══════════ -->
                    <div class="tab-pane fade <?= $activeTab === 'mail' ? 'show active' : '' ?>" id="tab-mail">
                        <form id="formMail">
                            <input type="hidden" name="action" value="save_mail">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">SMTP Host</label>
                                        <div class="input-icon-wrap">
                                            <i class="fas fa-server field-icon"></i>
                                            <input type="text" name="mail_host" class="form-control"
                                                value="<?= e(get_setting('mail_host', 'smtp.gmail.com')) ?>"
                                                placeholder="smtp.gmail.com">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">SMTP Port</label>
                                        <div class="input-icon-wrap">
                                            <i class="fas fa-plug field-icon"></i>
                                            <input type="number" name="mail_port" class="form-control"
                                                value="<?= e(get_setting('mail_port', '587')) ?>" placeholder="587">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Şifreleme</label>
                                        <div class="input-icon-wrap">
                                            <i class="fas fa-lock field-icon"></i>
                                            <select name="mail_secure" class="form-select select2-simple">
                                                <?php foreach (['tls' => 'TLS', 'ssl' => 'SSL', '' => 'Yok'] as $v => $l): ?>
                                                    <option value="<?= e($v) ?>" <?= get_setting('mail_secure', 'tls') === $v ? 'selected' : '' ?>>
                                                        <?= e($l) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">SMTP Kullanıcı (E-posta)</label>
                                        <div class="input-icon-wrap">
                                            <i class="fas fa-user-tag field-icon"></i>
                                            <input type="email" name="mail_user" class="form-control"
                                                value="<?= e(get_setting('mail_user', '')) ?>">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">SMTP Şifre</label>
                                        <div class="input-icon-wrap">
                                            <i class="fas fa-key field-icon"></i>
                                            <input type="password" name="mail_pass" class="form-control"
                                                placeholder="••••••••" autocomplete="new-password">
                                        </div>
                                        <small class="text-muted">Değiştirmek istemiyorsanız boş bırakın.</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Gönderen Ad</label>
                                        <div class="input-icon-wrap">
                                            <i class="fas fa-signature field-icon"></i>
                                            <input type="text" name="mail_from_name" class="form-control"
                                                value="<?= e(get_setting('mail_from_name', APP_NAME)) ?>">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Gönderen E-posta</label>
                                        <div class="input-icon-wrap">
                                            <i class="fas fa-at field-icon"></i>
                                            <input type="email" name="mail_from" class="form-control"
                                                value="<?= e(get_setting('mail_from', '')) ?>">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Program Yöneticisi E-posta</label>
                                        <div class="input-icon-wrap">
                                            <i class="fas fa-user-shield field-icon"></i>
                                            <input type="email" name="program_manager_email" class="form-control"
                                                value="<?= e(get_setting('program_manager_email', '')) ?>"
                                                placeholder="yonetici@ornek.com">
                                        </div>
                                        <small class="text-muted">Ürün taleplerinde bu adrese de bilgi maili
                                            gönderilir.</small>
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
                    <div class="tab-pane fade <?= $activeTab === 'appearance' ? 'show active' : '' ?>"
                        id="tab-appearance">
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
                                                        value="<?= e(get_setting('header_bg', '#343a40')) ?>"
                                                        style="width:60px;height:38px">
                                                    <input type="text" name="header_bg_hex" class="form-control"
                                                        value="<?= e(get_setting('header_bg', '#343a40')) ?>"
                                                        maxlength="7" placeholder="#343a40">
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Yazı Rengi</label>
                                                <div class="d-flex align-items-center gap-2">
                                                    <input type="color" name="header_color"
                                                        class="form-control form-control-color"
                                                        value="<?= e(get_setting('header_color', '#ffffff')) ?>"
                                                        style="width:60px;height:38px">
                                                    <input type="text" name="header_color_hex" class="form-control"
                                                        value="<?= e(get_setting('header_color', '#ffffff')) ?>"
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
                                                <div class="input-icon-wrap">
                                                    <i class="fas fa-quote-left field-icon"></i>
                                                    <input type="text" name="footer_text" class="form-control"
                                                        value="<?= e(get_setting('footer_text', '')) ?>">
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Arkaplan Rengi</label>
                                                <div class="d-flex align-items-center gap-2">
                                                    <input type="color" name="footer_bg"
                                                        class="form-control form-control-color"
                                                        value="<?= e(get_setting('footer_bg', '#343a40')) ?>"
                                                        style="width:60px;height:38px">
                                                    <input type="text" name="footer_bg_hex" class="form-control"
                                                        value="<?= e(get_setting('footer_bg', '#343a40')) ?>"
                                                        maxlength="7" placeholder="#343a40">
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Yazı Rengi</label>
                                                <div class="d-flex align-items-center gap-2">
                                                    <input type="color" name="footer_color"
                                                        class="form-control form-control-color"
                                                        value="<?= e(get_setting('footer_color', '#ffffff')) ?>"
                                                        style="width:60px;height:38px">
                                                    <input type="text" name="footer_color_hex" class="form-control"
                                                        value="<?= e(get_setting('footer_color', '#ffffff')) ?>"
                                                        maxlength="7" placeholder="#ffffff">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card card-outline card-secondary mb-3">
                                        <div class="card-header">
                                            <h6 class="m-0">Sistem Logosu</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3 text-center">
                                                <?php $logo = get_setting('system_logo'); ?>
                                                <div id="logo-preview-container"
                                                    class="mb-2 p-2 bg-light rounded d-flex align-items-center justify-content-center"
                                                    style="height: 100px; border: 1px dashed #ddd;">
                                                    <?php if ($logo && file_exists(__DIR__ . '/../' . $logo)): ?>
                                                        <img src="<?= BASE_URL . '/' . $logo ?>?t=<?= time() ?>"
                                                            id="logo-preview"
                                                            style="max-height: 80px; max-width: 100%; object-fit: contain;">
                                                    <?php else: ?>
                                                        <div id="logo-placeholder" class="text-muted small">Logo Yüklenmedi
                                                        </div>
                                                        <img src="" id="logo-preview"
                                                            style="max-height: 80px; max-width: 100%; object-fit: contain; display: none;">
                                                    <?php endif; ?>
                                                </div>
                                                <div class="input-group">
                                                    <input type="file" name="system_logo_file" id="system_logo_file"
                                                        class="form-control" accept="image/*">
                                                    <button type="button" class="btn btn-primary" id="btnUploadLogo">
                                                        <i class="fas fa-upload"></i>
                                                    </button>
                                                </div>
                                                <div class="row g-2 mt-2">
                                                    <div class="col-6">
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-text">En (px)</span>
                                                            <input type="number" name="system_logo_width"
                                                                id="system_logo_width" class="form-control"
                                                                value="<?= e(get_setting('system_logo_width', '')) ?>"
                                                                placeholder="Oto">
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-text">Boy (px)</span>
                                                            <input type="number" name="system_logo_height"
                                                                id="system_logo_height" class="form-control"
                                                                value="<?= e(get_setting('system_logo_height', '')) ?>"
                                                                placeholder="Oto">
                                                        </div>
                                                    </div>
                                                </div>
                                                <small class="text-muted d-block mt-1">Önerilen: En 200px / Boy 40px.
                                                    Boş bırakılırsa orijinal boyutu kullanılır.</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Google Font Seçimi</label>
                                        <div class="input-icon-wrap">
                                            <i class="fas fa-font field-icon"></i>
                                            <select name="google_font" class="form-select select2-simple">
                                                <?php foreach ($googleFontList as $fontKey => $fontLabel): ?>
                                                    <option value="<?= e($fontKey) ?>" <?= get_setting('google_font', 'default') === $fontKey ? 'selected' : '' ?>>
                                                        <?= e($fontLabel) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary"><i
                                    class="fas fa-save me-1"></i>Kaydet</button>
                        </form>
                    </div>

                    <!-- ═══════════ DÖVİZ KURLARI ═══════════ -->
                    <div class="tab-pane fade <?= $activeTab === 'currency' ? 'show active' : '' ?>" id="tab-currency">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card card-outline card-info">
                                    <div class="card-header">
                                        <h6 class="m-0">TCMB Kurları</h6>
                                        <small class="text-muted">
                                            Son güncelleme:
                                            <?= e(get_setting('currency_updated') ?: 'Henüz güncellenmedi') ?>
                                        </small>
                                    </div>
                                    <div class="card-body">
                                        <div class="row text-center mb-3">
                                            <div class="col-6">
                                                <div class="p-3 bg-light rounded">
                                                    <span class="text-muted d-block small">USD / TL</span>
                                                    <span class="h4 text-success" id="usdDisplay">
                                                        <?= e(get_setting('usd_rate', '0') > 0 ? formatPrice((float) get_setting('usd_rate', '0')) : '—') ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="p-3 bg-light rounded">
                                                    <span class="text-muted d-block small">EUR / TL</span>
                                                    <span class="h4 text-primary" id="eurDisplay">
                                                        <?= e(get_setting('eur_rate', '0') > 0 ? formatPrice((float) get_setting('eur_rate', '0')) : '—') ?>
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
                                        <h6 class="m-0">Manuel Kur Girişi & Ayarlar</h6>
                                    </div>
                                    <div class="card-body">
                                        <form id="formCurrency">
                                            <input type="hidden" name="action" value="save_currency">
                                            <div class="mb-3">
                                                <label class="form-label">Varsayılan Para Birimi (Sistem Geneli)</label>
                                                <div class="input-icon-wrap">
                                                    <i class="fas fa-coins field-icon"></i>
                                                    <select name="base_currency" class="form-select select2-simple">
                                                        <option value="EUR" <?= get_setting('base_currency', 'EUR') === 'EUR' ? 'selected' : '' ?>>EUR (Euro)</option>
                                                        <option value="USD" <?= get_setting('base_currency', 'EUR') === 'USD' ? 'selected' : '' ?>>USD (Amerikan Doları)
                                                        </option>
                                                        <option value="TL" <?= get_setting('base_currency', 'EUR') === 'TL' ? 'selected' : '' ?>>TL (Türk Lirası)</option>
                                                    </select>
                                                </div>
                                                <small class="text-muted">Tüm hesaplamalar ve görüntülenecek ana birim
                                                    budur.</small>
                                            </div>
                                            <hr>
                                            <div class="mb-3">
                                                <label class="form-label">USD / TL</label>
                                                <input type="text" name="usd_rate" class="form-control price-format"
                                                    value="<?= e(formatPrice((float) get_setting('usd_rate', '0'))) ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">EUR / TL</label>
                                                <input type="text" name="eur_rate" class="form-control price-format"
                                                    value="<?= e(formatPrice((float) get_setting('eur_rate', '0'))) ?>">
                                            </div>
                                            <button type="submit" class="btn btn-warning w-100">
                                                <i class="fas fa-save me-1"></i>Kaydet
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ═══════════ GENEL ═══════════ -->
                    <div class="tab-pane fade <?= $activeTab === 'general' ? 'show active' : '' ?>" id="tab-general">
                        <form id="formGeneral">
                            <input type="hidden" name="action" value="save_general">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Site Adı</label>
                                        <div class="input-icon-wrap">
                                            <i class="fas fa-globe field-icon"></i>
                                            <input type="text" name="site_name" class="form-control"
                                                value="<?= e(get_setting('site_name', APP_NAME)) ?>">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Kargo Gönderici Bilgisi</label>
                                        <div class="input-icon-wrap">
                                            <i class="fas fa-truck field-icon"></i>
                                            <textarea name="kargo_gonderici" class="form-control" rows="3"
                                                placeholder="Adres etiketinde görünecek gönderici bilgisi..."><?= e(get_setting('kargo_gonderici', '')) ?></textarea>
                                        </div>
                                        <small class="text-muted">Adres etiketi yazdırılırken en üstte "GÖNDERİCİ"
                                            olarak bu bilgi yazılır.</small>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check form-switch p-0" style="padding-left: 2.5em !important;">
                                            <input type="hidden" name="allow_passive_with_stock" value="0">
                                            <input class="form-check-input" type="checkbox"
                                                name="allow_passive_with_stock" value="1" id="allow_passive_stock"
                                                <?= get_setting('allow_passive_with_stock', '0') === '1' ? 'checked' : '' ?> style="margin-left: -2.5em;">
                                            <label class="form-check-label fw-bold" for="allow_passive_stock">Ürün
                                                varken depo pasif edilsin mi?</label>
                                            <div class="small text-muted">Bu ayar kapalıyken, içinde ürün bulunan
                                                depolar pasif hale getirilemez.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary"><i
                                    class="fas fa-save me-1"></i>Kaydet</button>
                        </form>
                    </div>

                    <!-- ═══════════ PDF AYARLARI ═══════════ -->
                    <div class="tab-pane fade <?= $activeTab === 'pdf' ? 'show active' : '' ?>" id="tab-pdf">
                        <form id="formPdf">
                            <input type="hidden" name="action" value="save_pdf">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label class="form-label d-flex justify-content-between">
                                            <span>PDF Çözünürlük Ölçeği (Scale)</span>
                                            <span id="range-scale-val"
                                                class="badge bg-primary"><?= e(get_setting('pdf_scale', '1.5')) ?></span>
                                        </label>
                                        <input type="range" name="pdf_scale" class="form-range" min="1" max="3"
                                            step="0.1" value="<?= e(get_setting('pdf_scale', '1.5')) ?>"
                                            oninput="$('#range-scale-val').text(this.value)">
                                        <div class="small text-muted mt-1">Daha yüksek değerler daha net görüntü sağlar
                                            ancak dosya boyutunu katlayarak artırır. (Önerilen: 1.5)</div>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label d-flex justify-content-between">
                                            <span>Resim Kalitesi (Quality)</span>
                                            <span id="range-quality-val"
                                                class="badge bg-primary"><?= e(get_setting('pdf_quality', '0.8')) ?></span>
                                        </label>
                                        <input type="range" name="pdf_quality" class="form-range" min="0.1" max="1"
                                            step="0.05" value="<?= e(get_setting('pdf_quality', '0.8')) ?>"
                                            oninput="$('#range-quality-val').text(this.value)">
                                        <div class="small text-muted mt-1">PDF içindeki ürün resimlerinin kalitesini
                                            belirler. (Önerilen: 0.8)</div>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary"><i
                                    class="fas fa-save me-1"></i>Kaydet</button>
                        </form>
                    </div>

                    <!-- ═══════════ VERİ YÖNETİMİ ═══════════ -->
                    <div class="tab-pane fade <?= $activeTab === 'data-mgmt' ? 'show active' : '' ?>"
                        id="tab-data-mgmt">
                        <div class="card card-outline card-primary mb-4">
                            <div class="card-header">
                                <h6 class="m-0 text-primary fw-bold"><i class="fas fa-file-excel me-1"></i>
                                    Toplu Veri İçe Aktar (Excel)</h6>
                            </div>
                            <div class="card-body">
                                <div class="row align-items-center mb-3">
                                    <div class="col-md-5">
                                        <label class="form-label">Aktarılacak Tablo Türü</label>
                                        <select id="importType" class="form-select select2-simple">
                                            <option value="products">Ürünler</option>
                                            <option value="customers">Müşteriler</option>
                                            <option value="suppliers">Tedarikçiler</option>
                                        </select>
                                    </div>
                                    <div class="col-md-7 text-md-end mt-3 mt-md-0 pt-md-4">
                                        <button type="button" class="btn btn-outline-success"
                                            onclick="downloadImportTemplate()">
                                            <i class="fas fa-download me-1"></i> Örnek Şablonu İndir
                                        </button>
                                    </div>
                                </div>
                                <hr>
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <label class="form-label">Excel Dosyası (.xlsx)</label>
                                        <input class="form-control" type="file" id="importFile" accept=".xlsx, .xls">
                                        <div class="small text-muted mt-1">Önce yukarıdan şablonu indirin, doldurup
                                            buraya yükleyin.</div>
                                    </div>
                                    <div class="col-md-4 text-md-end mt-3 mt-md-0 pt-md-4">
                                        <button type="button" class="btn btn-primary w-100" id="btnImportData">
                                            <i class="fas fa-upload me-1"></i> İçe Aktar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card card-outline card-danger">
                            <div class="card-header">
                                <h6 class="m-0 text-danger text-bold"><i class="fas fa-exclamation-triangle me-1"></i>
                                    Sistem Verilerini Sıfırla (Kritik Bölge)</h6>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-warning border-left-warning shadow-sm">
                                    <h5><strong>DİKKAT!</strong> Tüm Verileri Silme İşlemi</h5>
                                    <p class="mb-0">Bu işlem; ürünleri, stok hareketlerini, müşterileri, tedarikçileri
                                        ve tüm diğer operasyonel verileri <strong>kalıcı olarak silecektir.</strong></p>
                                    <p class="mb-0 mt-1">Admin kullanıcıları, program kullanıcıları ve sistem ayarları
                                        bu işlemden <strong>etkinmez.</strong></p>
                                </div>
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <div class="bg-light p-3 rounded d-flex align-items-center shadow-sm border">
                                            <div class="form-check form-switch mb-0 d-flex align-items-center">
                                                <input class="form-check-input" type="checkbox" id="selectAllCats"
                                                    style="transform: scale(1.4); margin-right: 15px;"
                                                    onchange="toggleAllSelection(this)">
                                                <label class="form-check-label fw-bold mb-0 text-primary ms-2"
                                                    for="selectAllCats"
                                                    style="cursor:pointer; font-size: 1.1rem; margin-left: 20px;">Tümünü
                                                    Seç</label>
                                            </div>
                                            <div class="ms-auto">
                                                <p class="text-muted mb-0 small" style="margin-left: 20px;"><i
                                                        class="fas fa-info-circle me-1"></i> Silmek istediğiniz alanları
                                                    aşağıdan işaretleyin.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input sel-del-check" type="checkbox"
                                                value="stock_in" id="checkStockIn">
                                            <label class="form-check-label" for="checkStockIn">Depo Giriş
                                                Hareketleri</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input sel-del-check" type="checkbox"
                                                value="stock_out" id="checkStockOut">
                                            <label class="form-check-label" for="checkStockOut">Depo Çıkış
                                                Hareketleri</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input sel-del-check" type="checkbox"
                                                value="entrusted" id="checkEntrusted">
                                            <label class="form-check-label" for="checkEntrusted">Emanet
                                                İşlemleri</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input sel-del-check" type="checkbox"
                                                value="transfers" id="checkTransfers">
                                            <label class="form-check-label" for="checkTransfers">Transferler</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input sel-del-check" type="checkbox"
                                                value="products" id="checkProducts">
                                            <label class="form-check-label" for="checkProducts">Ürün Tanımları</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input sel-del-check" type="checkbox"
                                                value="inventory" id="checkInventory">
                                            <label class="form-check-label" for="checkInventory">Depo Sayımı</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input sel-del-check" type="checkbox"
                                                value="packing_lists" id="checkPackingLists">
                                            <label class="form-check-label" for="checkPackingLists">Çeki
                                                Listeleri</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input sel-del-check" type="checkbox"
                                                value="customers" id="checkCustomers">
                                            <label class="form-check-label" for="checkCustomers">Müşteri
                                                Tanımları</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input sel-del-check" type="checkbox"
                                                value="suppliers" id="checkSuppliers">
                                            <label class="form-check-label" for="checkSuppliers">Tedarikçi
                                                Tanımları</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input sel-del-check" type="checkbox"
                                                value="requesters" id="checkRequesters">
                                            <label class="form-check-label" for="checkRequesters">Talep Eden
                                                Kişiler</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input sel-del-check" type="checkbox"
                                                value="warehouses" id="checkWarehouses">
                                            <label class="form-check-label" for="checkWarehouses">Depo Tanımları</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-danger btn-lg p-3 shadow-sm border-0"
                                        onclick="clearSelectiveData()">
                                        <i class="fas fa-trash-alt me-2"></i> Seçilen Verileri Kalıcı Olarak Sil
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ═══════════ YEDEKLEME ═══════════ -->
                    <div class="tab-pane fade <?= $activeTab === 'backup' ? 'show active' : '' ?>" id="tab-backup">
                        <div class="card card-outline card-primary">
                            <div class="card-header">
                                <h6 class="m-0 text-bold"><i class="fas fa-database me-1"></i> Veritabanı Yedekleri</h6>
                                <div class="card-tools">
                                    <button class="btn btn-primary btn-sm shadow-sm" onclick="createBackup()">
                                        <i class="fas fa-plus-circle me-1"></i> Yeni Yedek Oluştur
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="ps-3">Dosya Adı</th>
                                                <th>Boyut</th>
                                                <th>Tarih</th>
                                                <th class="text-end pe-3">İşlemler</th>
                                            </tr>
                                        </thead>
                                        <tbody id="backupTableBody">
                                            <tr>
                                                <td colspan="4" class="text-center text-muted p-4">Yükleniyor...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        // Select2 Simple Init
        $('.select2-simple').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('body'),
            minimumResultsForSearch: Infinity
        });
    });

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
            data = data.filter(function (d) { return d.name !== n; });
            data.push({ name: n, value: $(this).val() });
        }
    });

    // Form gönderimi (genel fonksiyon)
    function submitSettingsForm(formId) {
        var form = $('#' + formId);
        var formData = new FormData(form[0]);
        // color hex'leri ana alana yaz
        form.find('input[name$="_hex"]').each(function () {
            var n = $(this).attr('name').replace('_hex', '');
            formData.set(n, $(this).val());
        });

        var btn = form.find('button[type="submit"]');
        var originalBtnText = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Kaydediliyor...');

        $.ajax({
            url: '<?= BASE_URL ?>/api/settings.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (r) {
                btn.prop('disabled', false).html(originalBtnText);
                if (r.success) {
                    showSuccess(r.message || 'Kaydedildi!');
                    var activeTab = $('.nav-segmented .nav-link.active').attr('href').replace('#tab-', '');
                    setTimeout(() => location.href = '<?= BASE_URL ?>/index.php?page=settings&tab=' + activeTab, 1200);
                }
                else showError(r.message || 'Bir hata oluştu.');
            },
            error: function () {
                btn.prop('disabled', false).html(originalBtnText);
                showError('Bağlantı hatası.');
            }
        });
    }

    $('#formMail').on('submit', function (e) { e.preventDefault(); submitSettingsForm('formMail'); });
    $('#formAppearance').on('submit', function (e) { e.preventDefault(); submitSettingsForm('formAppearance'); });
    $('#formPdf').on('submit', function (e) { e.preventDefault(); submitSettingsForm('formPdf'); });
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
                $.post('<?= BASE_URL ?>/api/settings.php', { action: 'test_mail', to: result.value }, function (r) {
                    if (r.success) showSuccess('Test maili gönderildi!');
                    else showError(r.message || 'Mail gönderilemedi.');
                }, 'json');
            }
        });
    });

    // Logo Upload
    $('#btnUploadLogo').on('click', function () {
        var fileInput = $('#system_logo_file')[0];
        if (fileInput.files.length === 0) {
            Swal.fire('Uyarı', 'Lütfen bir resim dosyası seçin.', 'warning');
            return;
        }

        var formData = new FormData();
        formData.append('action', 'save_logo');
        formData.append('system_logo_file', fileInput.files[0]);
        formData.append('system_logo_width', $('#system_logo_width').val());
        formData.append('system_logo_height', $('#system_logo_height').val());

        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: '<?= BASE_URL ?>/api/settings.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (r) {
                if (r.success) {
                    showSuccess(r.message);
                    if (r.data && r.data.url) {
                        $('#logo-preview').attr('src', r.data.url + '?t=' + new Date().getTime()).show();
                        $('#logo-placeholder').hide();
                    }
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showError(r.message);
                }
            },
            error: function () {
                showError('Yükleme sırasında bir hata oluştu.');
            },
            complete: function () {
                btn.prop('disabled', false).html('<i class="fas fa-upload"></i>');
            }
        });
    });

    // Logo Preview on select
    $('#system_logo_file').on('change', function () {
        var input = this;
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $('#logo-preview').attr('src', e.target.result).show();
                $('#logo-placeholder').hide();
            }
            reader.readAsDataURL(input.files[0]);
        }
    });

    // Tab persistence - URL update on click
    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        var tabId = $(e.target).attr('href').replace('#tab-', '');
        var newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?page=settings&tab=' + tabId;
        window.history.replaceState({ path: newUrl }, '', newUrl);
    });

    // TCMB kurları güncelle
    $('#btnUpdateCurrency').on('click', function () {
        var btn = $(this).html('<i class="fas fa-spinner fa-spin me-1"></i>Güncelleniyor...').prop('disabled', true);
        $.post('<?= BASE_URL ?>/api/settings.php', { action: 'update_currency' }, function (r) {
            if (r.success) {
                showSuccess('Kurlar güncellendi!');
                if (r.data) {
                    $('#usdDisplay').text(r.data.usd_formatted);
                    $('#eurDisplay').text(r.data.eur_formatted);
                }
                var activeTab = $('.nav-segmented .nav-link.active').attr('href').replace('#tab-', '');
                setTimeout(() => location.href = '<?= BASE_URL ?>/index.php?page=settings&tab=' + activeTab, 1500);
            } else showError(r.message || 'Güncelleme başarısız.');
        }, 'json').always(function () {
            btn.html('<i class="fas fa-sync-alt me-1"></i>TCMB\'den Kurları Güncelle').prop('disabled', false);
        });
    });

    // Veri Yönetimi (Seçmeli Silme)
    function toggleAllSelection(el) {
        $('.sel-del-check').prop('checked', $(el).is(':checked'));
    }

    function clearSelectiveData() {
        var selected = [];
        $('.sel-del-check:checked').each(function () {
            selected.push($(this).val());
        });

        if (selected.length === 0) {
            Swal.fire('Uyarı', 'Lütfen silinecek en az bir kategori seçin.', 'warning');
            return;
        }

        var text = selected.length === $('.sel-del-check').length
            ? "Sistemdeki TÜM veriler (seçili kategoriler) kalıcı olarak silinecektir!"
            : "Seçilen kategorilerdeki veriler kalıcı olarak silinecektir!";

        Swal.fire({
            title: 'Emin misiniz?',
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Evet, Sil!',
            cancelButtonText: 'Vazgeç'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Onay Gerekli',
                    text: 'İşlemi onaylamak için "SIL" yazın:',
                    input: 'text',
                    inputAttributes: { autocapitalize: 'off' },
                    showCancelButton: true,
                    confirmButtonText: 'Onayla',
                    cancelButtonText: 'İptal',
                    showLoaderOnConfirm: true,
                    preConfirm: (inputValue) => {
                        if (inputValue !== 'SIL') {
                            Swal.showValidationMessage('Hatalı onay kelimesi!');
                            return false;
                        }
                        return $.post('<?= BASE_URL ?>/api/system_tasks.php', {
                            task: 'clear_selective_data',
                            categories: JSON.stringify(selected)
                        }).then(response => {
                            if (!response.success) {
                                throw new Error(response.message || 'Bir hata oluştu');
                            }
                            return response;
                        }).catch(error => {
                            Swal.showValidationMessage(`İşlem Başarısız: ${error}`);
                        });
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((res) => {
                    if (res.isConfirmed) {
                        Swal.fire({
                            title: 'Başarılı!',
                            text: 'Veriler temizlendi.',
                            icon: 'success'
                        }).then(() => {
                            location.reload();
                        });
                    }
                });
            }
        });
    }

    // Yedekleme İşlemleri
    function loadBackups() {
        $.get('<?= BASE_URL ?>/api/backup.php', { action: 'list' }, function (r) {
            if (r.success) {
                if (r.data.length === 0) {
                    $('#backupTableBody').html('<tr><td colspan="4" class="text-center text-muted p-4">Henüz yedek bulunmuyor.</td></tr>');
                    return;
                }
                var html = '';
                $.each(r.data, function (i, b) {
                    html += '<tr>';
                    html += '<td class="ps-3 fw-bold text-primary"><i class="fas fa-file-alt me-2 text-muted" style="margin-right: 5px"></i>' + b.filename + '</td>';
                    html += '<td><span class="badge bg-secondary">' + b.size + '</span></td>';
                    html += '<td>' + b.date + '</td>';
                    html += '<td class="text-end pe-3">';
                    html += '<div class="btn-group d-flex justify-content-end">';
                    html += '<a href="<?= BASE_URL ?>/api/backup.php?action=download&filename=' + b.filename + '" class="btn btn-sm btn-outline-success" title="İndir"><i class="fas fa-download"></i></a>';
                    html += '<button class="btn btn-sm btn-outline-warning" onclick="restoreBackup(\'' + b.filename + '\')" title="Geri Yükle"><i class="fas fa-undo"></i></button>';
                    html += '<button class="btn btn-sm btn-outline-danger" onclick="deleteBackup(\'' + b.filename + '\')" title="Sil"><i class="fas fa-trash"></i></button>';
                    html += '</div></td></tr>';
                });
                $('#backupTableBody').html(html);
            } else {
                $('#backupTableBody').html('<tr><td colspan="4" class="text-center text-danger p-4">Hata: ' + r.message + '</td></tr>');
            }
        }, 'json');
    }

    function createBackup() {
        Swal.fire({
            title: 'Yedek Oluşturuluyor...',
            text: 'Lütfen bekleyin, veritabanı yedeği alınıyor.',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.post('<?= BASE_URL ?>/api/backup.php', { action: 'create' }, function (r) {
            if (r.success) {
                showSuccess(r.message);
                loadBackups();
            } else {
                showError(r.message + (r.data && r.data.error ? "\n\n" + r.data.error : ""));
            }
        }, 'json');
    }

    function deleteBackup(filename) {
        confirmAction(filename + ' yedeği kalıcı olarak silinecektir! Emin misiniz?', function () {
            $.post('<?= BASE_URL ?>/api/backup.php', { action: 'delete', filename: filename }, function (r) {
                if (r.success) {
                    showSuccess(r.message);
                    loadBackups();
                } else {
                    showError(r.message);
                }
            }, 'json');
        });
    }

    function restoreBackup(filename) {
        Swal.fire({
            title: '⚠️ DİKKAT!',
            text: 'Veritabanı ' + filename + ' yedeğine geri döndürülecektir. Mevcut verileriniz backup anındaki haline döner. Devam etmek istiyor musunuz?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f39c12',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Evet, Geri Yükle!',
            cancelButtonText: 'İptal'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Geri Yükleniyor...',
                    text: 'İşlem sırasında sayfayı kapatmayın.',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                $.post('<?= BASE_URL ?>/api/backup.php', { action: 'restore', filename: filename }, function (r) {
                    if (r.success) {
                        Swal.fire('Tamamlandı', r.message, 'success').then(() => location.reload());
                    } else {
                        showError(r.message + (r.data && r.data.error ? "\n\n" + r.data.error : ""));
                    }
                }, 'json');
            }
        });
    }

    // Tab açıldığında yedekleri yükle
    $('a[href="#tab-backup"]').on('shown.bs.tab', function (e) {
        loadBackups();
    });

    // ═══════════ EXCEL İÇE AKTAR (IMPORT) İŞLEMLERİ ═══════════
    function downloadImportTemplate() {
        var type = $('#importType').val();
        var wb = XLSX.utils.book_new();
        var data = [];
        var sheetName = "";
        var fileName = "";

        if (type === 'products') {
            sheetName = "Urunler";
            fileName = "Ornek_Urun_Sablonu.xlsx";
            data = [
                ["Ürün Adı", "Ürün Kodu", "Birim (Adet vb.)", "Açıklama", "Alarm Seviyesi"],
                ["Örnek Koli Bandı", "BND-001", "Adet", "Şeffaf koli bandı", 50]
            ];
        } else if (type === 'customers') {
            sheetName = "Musteriler";
            fileName = "Ornek_Musteri_Sablonu.xlsx";
            data = [
                ["Ad / Ünvan", "Yetkili Kişi", "E-posta", "Telefon", "Adres"],
                ["Örnek Yazılım A.Ş.", "Mehmet Yılmaz", "info@ornek.com", "05554443322", "Örnek Mah. Test Sk. No:1"]
            ];
        } else if (type === 'suppliers') {
            sheetName = "Tedarikciler";
            fileName = "Ornek_Tedarikci_Sablonu.xlsx";
            data = [
                ["Ad / Ünvan", "Yetkili Kişi", "E-posta", "Telefon", "Adres"],
                ["Örnek Kargo Ltd.", "Ayşe Demir", "support@kargo.com", "02123334455", "Kargo Sokak No:2"]
            ];
        }

        var ws = XLSX.utils.aoa_to_sheet(data);
        // Sütun genişlikleri
        ws['!cols'] = [{ wch: 30 }, { wch: 20 }, { wch: 20 }, { wch: 30 }, { wch: 50 }];

        XLSX.utils.book_append_sheet(wb, ws, sheetName);
        XLSX.writeFile(wb, fileName);
    }

    $('#btnImportData').on('click', function () {
        var fileInput = document.getElementById('importFile');
        if (!fileInput.files || fileInput.files.length === 0) {
            showError("Lütfen önce bir Excel (.xlsx) dosyası seçin.");
            return;
        }

        var btn = $(this);
        var originalBtnText = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Dosya Okunuyor...');

        var file = fileInput.files[0];
        var reader = new FileReader();

        reader.onload = function (e) {
            try {
                var data = new Uint8Array(e.target.result);
                var workbook = XLSX.read(data, { type: 'array' });

                // İlk sayfayı al
                var firstSheetName = workbook.SheetNames[0];
                var worksheet = workbook.Sheets[firstSheetName];

                // JSON formatına çevir (İlk satır başlık kabul edilir)
                var jsonRows = XLSX.utils.sheet_to_json(worksheet, { defval: "" });

                if (jsonRows.length === 0) {
                    showError("Dosya içi boş görünüyor. Örnek şablona göre doldurun.");
                    btn.prop('disabled', false).html(originalBtnText);
                    return;
                }

                // API'ye gönder
                btn.html('<i class="fas fa-spinner fa-spin me-1"></i> Sunucuya Kaydediliyor...');
                var type = $('#importType').val();

                $.post('<?= BASE_URL ?>/api/import.php', {
                    type: type,
                    data: JSON.stringify(jsonRows)
                }, function (r) {
                    btn.prop('disabled', false).html(originalBtnText);

                    if (r.success) {
                        var msg = r.message;
                        if (r.data && r.data.errors && r.data.errors.length > 0) {
                            msg += "\n\nHatalar:\n" + r.data.errors.slice(0, 10).join("\n");
                            if (r.data.errors.length > 10) msg += "\n... ve " + (r.data.errors.length - 10) + " hata daha.";
                            showInfo(msg);
                            setTimeout(() => { location.reload(); }, 3000);
                        } else {
                            showSuccess(msg);
                            setTimeout(() => { location.reload(); }, 1500);
                        }
                    } else {
                        // Tamamen başarısız
                        var errorMsg = r.message;
                        if (r.data && r.data.errors && r.data.errors.length > 0) {
                            errorMsg += "\n\nHata Detayları:\n" + r.data.errors.slice(0, 10).join("\n");
                            if (r.data.errors.length > 10) errorMsg += "\n... ve " + (r.data.errors.length - 10) + " hata daha.";
                        }
                        showError(errorMsg);
                    }
                }, 'json').fail(function () {
                    btn.prop('disabled', false).html(originalBtnText);
                    showError("Sunucu bağlantı hatası oluştu.");
                });

            } catch (ex) {
                btn.prop('disabled', false).html(originalBtnText);
                showError("Excel dosyası okunurken hata: " + ex.message);
            }
        };

        reader.onerror = function () {
            btn.prop('disabled', false).html(originalBtnText);
            showError("Dosya okunurken donanımsal hata oluştu.");
        };

        reader.readAsArrayBuffer(file);
    });
</script>