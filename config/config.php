<?php
/**
 * Uygulama Sabitleri
 */

define('APP_NAME', 'Depo Yönetim Sistemi');
define('APP_VERSION', '1.2.40');
define('ROOT_PATH', dirname(__DIR__));
define('BASE_URL', '/deppo');
define('UPLOAD_PATH', ROOT_PATH . '/images/UrunResim/');
define('UPLOAD_URL', BASE_URL . '/images/UrunResim/');

// İzin verilen resim uzantıları
define('ALLOWED_IMAGE_EXT', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Oturum etiket sabitleri
define('ROLE_ADMIN', 'admin');
define('ROLE_USER', 'user');
define('ROLE_REQUESTER', 'requester');

// Sayfa başına varsayılan satır sayısı
define('DEFAULT_PER_PAGE', 25);

// Zaman dilimi
date_default_timezone_set('Europe/Istanbul');