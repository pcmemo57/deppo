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
        <?= e($pageTitle)?> —
        <?= e($siteName)?>
    </title>
    <?= $fontLinkTag?>
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
            --font-family: <?=e($fontFamilyCss)?>;
            --header-bg: <?=e($headerBg)?>;
            --header-color: <?=e($headerColor)?>;
        }

        body,
        * {
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

        /* Modal geniş */
        .modal-xl {
            max-width: 95%;
        }

        .swal2-popup {
            font-family: var(--font-family) !important;
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
                    <a href="<?= BASE_URL?>/index.php" class="nav-link fw-bold" style="color:var(--header-color)">
                        <i class="fas fa-warehouse me-1"></i>
                        <?= e($siteName)?>
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav ml-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                        data-bs-toggle="dropdown">
                        <span style="color:var(--header-color)">
                            <i class="fas fa-user-circle me-1"></i>
                            <?= e(currentUser()['name'])?>
                            <span class="badge ms-1
                        <?= currentUser()['role'] === ROLE_ADMIN ? 'bg-danger' :
    (currentUser()['role'] === ROLE_USER ? 'bg-info' : 'bg-warning')?>">
                                <?= currentUser()['role'] === ROLE_ADMIN ? 'Admin' :
    (currentUser()['role'] === ROLE_USER ? 'Yönetici' : 'Talep Eden')?>
                            </span>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <h6 class="dropdown-header">
                                <?= e(currentUser()['email'])?>
                            </h6>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item text-danger" href="<?= BASE_URL?>/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Çıkış Yap
                            </a></li>
                    </ul>
                </li>
            </ul>
        </nav>