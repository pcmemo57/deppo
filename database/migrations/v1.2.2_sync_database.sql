-- Depo Yönetim Sistemi - Veritabanı Senkronizasyon Migrasyonu
-- Sürüm: v1.2.2

-- 1. Eksik Tabloları Oluştur
CREATE TABLE IF NOT EXISTS `inventory_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `warehouse_id` int(11) NOT NULL,
  `status` enum('open','closed') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `warehouse_id` (`warehouse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `inventory_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `expected_qty` decimal(15,4) DEFAULT 0.0000,
  `counted_qty` decimal(15,4) DEFAULT 0.0000,
  `difference` decimal(15,4) DEFAULT 0.0000,
  `counted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbl_dp_packing_lists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `list_no` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `total_weight_kg` float DEFAULT 0,
  `total_vol_desi` float DEFAULT 0,
  `total_parcels` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `list_no` (`list_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbl_dp_packing_list_parcels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `packing_list_id` int(11) NOT NULL,
  `parcel_no` int(11) NOT NULL,
  `weight_kg` float DEFAULT NULL,
  `width_cm` float DEFAULT NULL,
  `height_cm` float DEFAULT NULL,
  `length_cm` float DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbl_dp_packing_list_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parcel_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` float NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbl_dp_password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at" timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Eksik Kolonları Ekle (MariaDB 10.2.19+ desteği gerektirir)
-- Ürünler
ALTER TABLE `tbl_dp_products` ADD COLUMN IF NOT EXISTS `procurement_status` int(11) DEFAULT 0;
ALTER TABLE `tbl_dp_products` ADD COLUMN IF NOT EXISTS `procurement_note` text;

-- Personel
ALTER TABLE `tbl_dp_requesters` ADD COLUMN IF NOT EXISTS `password" varchar(255) DEFAULT NULL;
ALTER TABLE `tbl_dp_requesters` ADD COLUMN IF NOT EXISTS `last_login` datetime DEFAULT NULL;

-- Stok Giriş
ALTER TABLE `tbl_dp_stock_in` ADD COLUMN IF NOT EXISTS `currency` enum('TL','USD','EUR') DEFAULT 'EUR';
ALTER TABLE `tbl_dp_stock_in` ADD COLUMN IF NOT EXISTS `price_eur` decimal(15,4) DEFAULT 0.0000;

-- Stok Çıkış
ALTER TABLE `tbl_dp_stock_out` ADD COLUMN IF NOT EXISTS `order_no` int(11) DEFAULT NULL;
ALTER TABLE `tbl_dp_stock_out` ADD COLUMN IF NOT EXISTS `currency` enum('TL','USD','EUR') DEFAULT 'EUR';
ALTER TABLE `tbl_dp_stock_out` ADD COLUMN IF NOT EXISTS `status` tinyint(1) DEFAULT 1;
