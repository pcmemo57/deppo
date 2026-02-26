<?php
/**
 * Veritabanı Kurulum Scripti
 * Sadece bir kez çalıştırılır.
 */

// Doğrudan bağlantı — DB yoksa oluştur
$host = 'localhost';
$user = 'root';
$pass = '';
$dbName = 'deppo';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbName`");

    $sql = <<<SQL

    -- Ayarlar
    CREATE TABLE IF NOT EXISTS `tbl_dp_settings` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `setting_key` VARCHAR(100) NOT NULL UNIQUE,
        `setting_value` TEXT,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Admin kullanıcıları
    CREATE TABLE IF NOT EXISTS `tbl_dp_admins` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `email` VARCHAR(150) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `is_active` TINYINT(1) DEFAULT 1,
        `hidden` TINYINT(1) DEFAULT 0,
        `last_login` DATETIME DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Program yöneticileri (normal kullanıcı)
    CREATE TABLE IF NOT EXISTS `tbl_dp_users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `email` VARCHAR(150) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `is_active` TINYINT(1) DEFAULT 1,
        `hidden` TINYINT(1) DEFAULT 0,
        `last_login` DATETIME DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Talep edenler
    CREATE TABLE IF NOT EXISTS `tbl_dp_requesters` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `surname` VARCHAR(100) NOT NULL,
        `email` VARCHAR(150),
        `title` VARCHAR(100),
        `is_active` TINYINT(1) DEFAULT 1,
        `hidden` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Depolar (Lokasyonlar)
    CREATE TABLE IF NOT EXISTS `tbl_dp_warehouses` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(150) NOT NULL,
        `address` TEXT,
        `description` TEXT,
        `is_active` TINYINT(1) DEFAULT 1,
        `hidden` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Müşteriler
    CREATE TABLE IF NOT EXISTS `tbl_dp_customers` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(150) NOT NULL,
        `contact` VARCHAR(100),
        `email` VARCHAR(150),
        `phone` VARCHAR(30),
        `address` TEXT,
        `is_active` TINYINT(1) DEFAULT 1,
        `hidden` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Tedarikçiler
    CREATE TABLE IF NOT EXISTS `tbl_dp_suppliers` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(150) NOT NULL,
        `contact` VARCHAR(100),
        `email` VARCHAR(150),
        `phone` VARCHAR(30),
        `address` TEXT,
        `is_active` TINYINT(1) DEFAULT 1,
        `hidden` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Ürünler
    CREATE TABLE IF NOT EXISTS `tbl_dp_products` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(200) NOT NULL,
        `code` VARCHAR(50),
        `description` TEXT,
        `image` VARCHAR(255),
        `unit` VARCHAR(20) DEFAULT 'Adet',
        `is_active` TINYINT(1) DEFAULT 1,
        `hidden` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Ürün girişleri (depoya stok girişi)
    CREATE TABLE IF NOT EXISTS `tbl_dp_stock_in` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `warehouse_id` INT NOT NULL,
        `product_id` INT NOT NULL,
        `supplier_id` INT,
        `quantity` DECIMAL(10,3) NOT NULL DEFAULT 0,
        `unit_price` DECIMAL(15,4) DEFAULT 0,
        `currency` ENUM('TL','USD','EUR') DEFAULT 'EUR',
        `price_eur` DECIMAL(15,4) DEFAULT 0,
        `note` TEXT,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_by` INT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`warehouse_id`)  REFERENCES `tbl_dp_warehouses`(`id`),
        FOREIGN KEY (`product_id`)    REFERENCES `tbl_dp_products`(`id`),
        FOREIGN KEY (`supplier_id`)   REFERENCES `tbl_dp_suppliers`(`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Depodan çıkış
    CREATE TABLE IF NOT EXISTS `tbl_dp_stock_out` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `warehouse_id` INT NOT NULL,
        `requester_id` INT,
        `customer_id` INT,
        `product_id` INT NOT NULL,
        `quantity` DECIMAL(10,3) NOT NULL DEFAULT 0,
        `unit_price` DECIMAL(15,4) DEFAULT 0,
        `total_price` DECIMAL(15,4) DEFAULT 0,
        `note` TEXT,
        `created_by` INT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`warehouse_id`)  REFERENCES `tbl_dp_warehouses`(`id`),
        FOREIGN KEY (`product_id`)    REFERENCES `tbl_dp_products`(`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Depolar arası transfer
    CREATE TABLE IF NOT EXISTS `tbl_dp_transfers` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `source_warehouse_id` INT NOT NULL,
        `target_warehouse_id` INT NOT NULL,
        `note` TEXT,
        `transfer_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `created_by` INT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`source_warehouse_id`) REFERENCES `tbl_dp_warehouses`(`id`),
        FOREIGN KEY (`target_warehouse_id`) REFERENCES `tbl_dp_warehouses`(`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Transfer kalemleri
    CREATE TABLE IF NOT EXISTS `tbl_dp_transfer_items` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `transfer_id` INT NOT NULL,
        `product_id` INT NOT NULL,
        `quantity` DECIMAL(10,3) NOT NULL,
        FOREIGN KEY (`transfer_id`) REFERENCES `tbl_dp_transfers`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`product_id`)  REFERENCES `tbl_dp_products`(`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;

    // Çoklu sorgu çalıştır
    $pdo->exec($sql);

    // Varsayılan ayarlar
    $defaults = [
        'mail_host' => 'smtp.gmail.com',
        'mail_port' => '587',
        'mail_secure' => 'tls',
        'mail_user' => '',
        'mail_pass' => '',
        'mail_from' => '',
        'mail_from_name' => 'Depo Yönetim Sistemi',
        'site_name' => 'Depo Yönetim Sistemi',
        'footer_text' => '© 2026 Depo Yönetim Sistemi',
        'header_bg' => '#343a40',
        'header_color' => '#ffffff',
        'footer_bg' => '#343a40',
        'footer_color' => '#ffffff',
        'google_font' => 'default',
        'usd_rate' => '0',
        'eur_rate' => '0',
        'currency_updated' => '',
    ];

    $stmt = $pdo->prepare('INSERT IGNORE INTO tbl_dp_settings (setting_key, setting_value) VALUES (?, ?)');
    foreach ($defaults as $key => $val) {
        $stmt->execute([$key, $val]);
    }

    // Varsayılan admin
    $adminHash = password_hash('Admin123!', PASSWORD_BCRYPT, ['cost' => 12]);
    $pdo->prepare(
        'INSERT IGNORE INTO tbl_dp_admins (name, email, password) VALUES (?, ?, ?)'
    )->execute(['Sistem Admini', 'admin@deppo.local', $adminHash]);

    echo '<h2 style="color:green">✓ Kurulum başarılı!</h2>';
    echo '<p>Varsayılan admin: <b>admin@deppo.local</b> / <b>Admin123!</b></p>';
    echo '<p>Giriş için: <a href="../login.php">../login.php</a></p>';
    echo '<p style="color:red"><b>Güvenlik:</b> Bu dosyayı (setup/install.php) sunucudan silin!</p>';

}
catch (PDOException $e) {
    echo '<h2 style="color:red">✗ Kurulum hatası:</h2>';
    echo '<pre>' . e($e->getMessage()) . '</pre>';
}

function e($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}