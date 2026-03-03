<?php
/**
 * Uygulama Header'ı (AdminLTE 3)
 * $pageTitle ve $siteName değişkenleri index.php'de set edilir
 */
$pageTitle = $pageTitle ?? 'Kontrol Paneli';
$siteName = get_setting('site_name', 'Depo Yönetim Sistemi');
$headerBg = get_setting('header_bg', '#343a40');
$headerColor = get_setting('header_color', '#ffffff');
$googleFont = get_setting('google_font', 'default');

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
];

$fontLinkTag = '';
$fontFamilyCss = "'Source Sans Pro', sans-serif";
$selectedFont = ($googleFont === 'default') ? 'Source+Sans+Pro' : $googleFont;

if (isset($googleFonts[$selectedFont])) {
    $fontLinkTag = '<link href="' . $googleFonts[$selectedFont] . '" rel="stylesheet">';
    $fontFamilyCss = "'" . str_replace('+', ' ', $selectedFont) . "', sans-serif";
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= generateCsrfToken() ?>">
    <title>
        <?= e($pageTitle) ?> —
        <?= e($siteName) ?>
    </title>
    <?= $fontLinkTag ?>
    <!-- AdminLTE 3 -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/css/adminlte.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/css/all.min.css">
    <!-- Select2 -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/css/select2.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/css/select2-bootstrap-5-theme.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/css/sweetalert2.min.css">
    <style>
        :root {
            --font-family:
                <?= $fontFamilyCss ?>
            ;
            --header-bg:
                <?= e($headerBg) ?>
            ;
            --header-color:
                <?= e($headerColor) ?>
            ;
        }

        body {
            font-family: var(--font-family) !important;
        }

        .main-header {
            background: var(--header-bg) !important;
        }

        .main-header .navbar-brand,
        .main-header .nav-link {
            color: var(--header-color) !important;
        }

        .nav-sidebar .nav-link {
            transition: all 0.2s;
        }

        .nav-sidebar .nav-link:hover {
            background: rgba(255, 255, 255, 0.08);
        }

        .content-wrapper {
            min-height: calc(100vh - 120px);
        }

        /* Sidebar ikonlarını soldan hizala ve metne bitiştir */
        .nav-sidebar .nav-link {
            display: flex !important;
            align-items: center !important;
            padding-left: 1rem !important;
        }

        .nav-sidebar .nav-icon {
            margin-right: 0.75rem !important;
            text-align: left !important;
            width: auto !important;
            margin-left: 0 !important;
            font-size: 1.1rem !important;
        }


        /* Standardize input-group heights and alignment */
        .input-group-text {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Binler ayıracı gösteren input */
        .price-format {
            text-align: right;
        }

        /* Select2 resimli seçenek */
        .select2-product-img {
            width: 30px;
            height: 30px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 8px;
            vertical-align: middle;
        }

        /* Bootstrap Switch Custom Styling */
        .form-switch .form-check-input {
            cursor: pointer;
            width: 2.5em;
            height: 1.25em;
        }

        /* Standardize status badges (pill style) */
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid transparent;
        }

        .status-badge.active {
            background: #ecfdf5;
            color: #059669;
            border-color: #a7f3d0;
        }

        .status-badge.active:hover {
            background: #d1fae5;
            border-color: #6ee7b7;
        }

        .status-badge.inactive {
            background: #fef2f2;
            color: #dc2626;
            border-color: #fecaca;
        }

        .status-badge.inactive:hover {
            background: #fee2e2;
            border-color: #fca5a5;
        }

        /* Modal Status Button Group (Segmented Toggle) */
        .status-btn-group {
            display: flex;
            background: #edf2f7;
            padding: 4px;
            border-radius: 10px;
            gap: 4px;
            width: fit-content;
        }

        .status-btn-item {
            padding: 6px 16px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            color: #718096;
            background: transparent;
        }

        .status-btn-item.active-state {
            background: #fff;
            color: #059669;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .status-btn-item.inactive-state {
            background: #fff;
            color: #dc2626;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .modal-xl {
            max-width: 95%;
        }

        .swal2-popup {
            font-family: var(--font-family) !important;
        }

        /* Sayısal alanları sağa hizalama zorlaması */
        .num-align {
            text-align: right !important;
        }

        /* ══════════════════════════════════════════
           GLOBAL FORM VE PREMIUM INPUT STİLLERİ
        ══════════════════════════════════════════ */
        .form-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
            display: block;
        }

        .form-control,
        .form-select {
            border: 1.5px solid #d1d9e6 !important;
            border-radius: 8px !important;
            padding: 9px 13px;
            font-size: 0.88rem;
            color: #1f2937;
            background: #fff;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        /* File input height fix */
        input[type="file"].form-control {
            height: auto !important;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #1a56db !important;
            box-shadow: 0 0 0 3px rgba(26, 86, 219, 0.12) !important;
            outline: none;
        }

        /* İkonlu input wrapper */
        .input-icon-wrap {
            position: relative;
        }

        .input-icon-wrap .field-icon {
            position: absolute;
            left: 11px;
            top: 50%;
            transform: translateY(-50%);
            color: #9aa5be;
            font-size: 0.82rem;
            z-index: 5;
            pointer-events: none;
        }

        .input-icon-wrap .form-control,
        .input-icon-wrap .form-select {
            padding-left: 32px !important;
        }

        .input-icon-wrap textarea.form-control {
            padding-left: 13px !important;
        }

        /* Select2 Bootstrap 5 Custom Styling (Global) */
        .select2-container--bootstrap-5 .select2-selection {
            border: 1.5px solid #d1d9e6 !important;
            border-radius: 8px !important;
            min-height: 40px !important;
            padding: 6px 10px 6px 32px !important;
            font-size: 0.88rem !important;
            background: #fff !important;
            display: flex !important;
            align-items: center !important;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            padding-left: 0 !important;
            line-height: normal !important;
            color: #1f2937 !important;
        }

        .select2-container--bootstrap-5.select2-container--focus .select2-selection,
        .select2-container--bootstrap-5.select2-container--open .select2-selection {
            border-color: #1a56db !important;
            box-shadow: 0 0 0 3px rgba(26, 86, 219, 0.12) !important;
        }

        /* Genel Form ve Buton Boşlukları (Bitişik elemanları ayır) */
        .table td .btn+.btn,
        .table td .badge+.badge,
        .table td .status-badge+.status-badge {
            margin-left: 0.35rem;
        }

        .input-group+.btn,
        .btn+.input-group,
        .btn+.btn {
            margin-left: 0.25rem;
        }

        /* ───────────────────────────────────────────
           GLOBAL CARD HEADER VE ARAÇ ÇUBUĞU HİZALAMA
        ─────────────────────────────────────────── */
        .card-header .card-title {
            font-size: 1.75rem !important;
            display: flex;
            align-items: center;
        }

        .card-header .card-title i {
            font-size: 1.5rem;
            margin-right: 5px;
            /* İkon ve yazı arası 5px */
        }

        .card-header .card-tools {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Tüm header araçlarını aynı yüksekliğe sabitle (Input, Select, Butonlar) */
        .card-header .card-tools .form-select-sm,
        .card-header .card-tools .input-group-sm .form-control,
        .card-header .card-tools .input-group-sm .input-group-text,
        .card-header .card-tools .btn-sm {
            height: 32px !important;
            line-height: inherit;
            font-size: 0.8125rem;
            padding-top: 0;
            padding-bottom: 0;
            box-sizing: border-box;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        /* input-group-text (büyüteç) özelleştirmesi */
        .card-header .card-tools .input-group-sm .input-group-text {
            background: #f4f6f9;
            border-color: #ced4da;
            color: #6c757d;
            padding: 0 10px;
        }

        /* Tüm butonlarda ikon ve metin arası 5px boşluk (Modern Flexbox Gap Çözümü) */
        .btn {
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        /* blok butonlar için display: flex */
        .btn.w-100,
        .btn.btn-block {
            display: flex !important;
        }

        /* Buton içindeki ikonların varsayılan marjinlerini sıfırla (Çakışmaları önlemek için) */
        .btn i,
        .btn .fas,
        .btn .far,
        .btn .fab,
        .btn .fa {
            margin: 0 !important;
        }

        /* Navbar Kur Rozetleri Padding Artırma */
        #nav-usd-rate,
        #nav-eur-rate,
        #btnNavbarUpdateCurrency {
            padding: 6px 14px !important;
            font-size: 0.8125rem;
        }
    </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">

        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-dark" style="background:var(--header-bg)">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                        <i class="fas fa-bars" style="color:var(--header-color)"></i>
                    </a>
                </li>
                <li class="nav-item d-none d-md-inline-block">
                    <?php
                    $trMonths = ["", "Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran", "Temmuz", "Ağustos", "Eylül", "Ekim", "Kasım", "Aralık"];
                    $trDays = ["Pazar", "Pazartesi", "Salı", "Çarşamba", "Perşembe", "Cuma", "Cumartesi"];
                    $displayDate = date('j') . ' ' . $trMonths[date('n')] . ' ' . date('Y') . ' (' . $trDays[date('w')] . ')';
                    $usd = (float) get_setting('usd_rate', '0');
                    $eur = (float) get_setting('eur_rate', '0');
                    $lastUpdate = get_setting('currency_updated', '');
                    $updateTime = $lastUpdate ? date('H:i', strtotime($lastUpdate)) : '--:--';
                    ?>
                    <div class="nav-link fw-bold d-flex align-items-center"
                        style="color:var(--header-color); cursor: default;">
                        <i class="far fa-calendar-alt me-2" style="margin-right: 10px;"></i>
                        <span><?= $displayDate ?></span>
                        <span class="mx-3" style="opacity: 0.5;">|</span>
                        <div class="d-flex align-items-center" style="gap: 10px;">
                            <span class="badge bg-success shadow-sm" id="nav-usd-rate">
                                <i class="fas fa-dollar-sign me-1"></i>USD: <?= formatPrice($usd) ?>
                            </span>
                            <span class="badge bg-primary shadow-sm" id="nav-eur-rate">
                                <i class="fas fa-euro-sign me-1"></i>EUR: <?= formatPrice($eur) ?>
                            </span>
                            <button id="btnNavbarUpdateCurrency" class="badge bg-info border-0 shadow-sm"
                                style="cursor: pointer; font-weight: 500;">
                                <i class="fas fa-sync-alt me-1"></i>Güncelle (<?= $updateTime ?>)
                            </button>
                        </div>
                    </div>
                </li>
            </ul>
            <ul class="navbar-nav ml-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                        data-bs-toggle="dropdown">
                        <span style="color:var(--header-color)">
                            <i class="fas fa-user-circle me-1"></i>
                            <?= e(currentUser()['name']) ?>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-right shadow-sm border-0"
                        style="min-width: 200px; right: 0; left: auto;">
                        <li>
                            <div class="px-3 py-2 border-bottom mb-1 bg-light rounded-top">
                                <div class="fw-bold small text-dark"><?= e(currentUser()['name']) ?></div>
                                <div class="text-muted" style="font-size: 0.75rem;"><?= e(currentUser()['email']) ?>
                                </div>
                                <div class="mt-2 text-center">
                                    <span class="badge w-100 py-2
                                        <?= currentUser()['role'] === ROLE_ADMIN ? 'bg-danger' :
                                            (currentUser()['role'] === ROLE_USER ? 'bg-info' : 'bg-warning') ?>">
                                        <i class="fas fa-shield-alt me-1"></i>
                                        <?= currentUser()['role'] === ROLE_ADMIN ? 'Admin' :
                                            (currentUser()['role'] === ROLE_USER ? 'Yönetici' : 'Talep Eden') ?>
                                    </span>
                                </div>
                            </div>
                        </li>
                        <li><a class="dropdown-item text-danger py-2" href="<?= BASE_URL ?>/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Çıkış Yap
                            </a></li>
                    </ul>
                </li>
            </ul>
        </nav>