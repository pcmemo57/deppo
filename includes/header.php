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
    'Roboto' => 'https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap',
    'Open+Sans' => 'https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700&display=swap',
    'Lato' => 'https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700&display=swap',
    'Montserrat' => 'https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap',
    'Poppins' => 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap',
    'Nunito' => 'https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap',
    'Raleway' => 'https://fonts.googleapis.com/css2?family=Raleway:wght@300;400;600;700&display=swap',
    'Inter' => 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap',
    'Ubuntu' => 'https://fonts.googleapis.com/css2?family=Ubuntu:wght@300;400;500;700&display=swap',
    'Outfit' => 'https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap',
];

$fontLinkTag = '';
$fontFamilyCss = "'Source Sans Pro', sans-serif";
if ($googleFont !== 'default' && isset($googleFonts[$googleFont])) {
    $fontLinkTag = '<link href="' . $googleFonts[$googleFont] . '" rel="stylesheet">';
    $fontFamilyCss = "'" . str_replace('+', ' ', $googleFont) . "', sans-serif";
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= e($pageTitle) ?> —
        <?= e($siteName) ?>
    </title>
    <?= $fontLinkTag ?>
    <!-- AdminLTE 3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Select2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
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
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="<?= BASE_URL ?>/index.php" class="nav-link fw-bold" style="color:var(--header-color)">
                        <i class="fas fa-warehouse me-1"></i>
                        <?= e($siteName) ?>
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav ml-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                        data-bs-toggle="dropdown">
                        <span style="color:var(--header-color)">
                            <i class="fas fa-user-circle me-1"></i>
                            <?= e(currentUser()['name']) ?>
                            <span class="badge ms-1
                        <?= currentUser()['role'] === ROLE_ADMIN ? 'bg-danger' :
                            (currentUser()['role'] === ROLE_USER ? 'bg-info' : 'bg-warning') ?>">
                                <?= currentUser()['role'] === ROLE_ADMIN ? 'Admin' :
                                    (currentUser()['role'] === ROLE_USER ? 'Yönetici' : 'Talep Eden') ?>
                            </span>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <h6 class="dropdown-header">
                                <?= e(currentUser()['email']) ?>
                            </h6>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Çıkış Yap
                            </a></li>
                    </ul>
                </li>
            </ul>
        </nav>