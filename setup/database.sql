-- MariaDB dump 10.19  Distrib 10.4.21-MariaDB, for osx10.10 (x86_64)
--
-- Host: localhost    Database: deppo
-- ------------------------------------------------------
-- Server version	10.4.21-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `inventory_items`
--

DROP TABLE IF EXISTS `inventory_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `expected_qty` decimal(15,4) DEFAULT 0.0000,
  `counted_qty` decimal(15,4) DEFAULT 0.0000,
  `difference` decimal(15,4) DEFAULT 0.0000,
  `counted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `inventory_items_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `inventory_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `tbl_dp_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inventory_sessions`
--

DROP TABLE IF EXISTS `inventory_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `warehouse_id` int(11) NOT NULL,
  `status` enum('open','closed') COLLATE utf8mb4_unicode_ci DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `warehouse_id` (`warehouse_id`),
  CONSTRAINT `inventory_sessions_ibfk_1` FOREIGN KEY (`warehouse_id`) REFERENCES `tbl_dp_warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbl_dp_admins`
--

DROP TABLE IF EXISTS `tbl_dp_admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_dp_admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `hidden` tinyint(1) DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbl_dp_customers`
--

DROP TABLE IF EXISTS `tbl_dp_customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_dp_customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `contact` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `hidden` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=135 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbl_dp_entrusted`
--

DROP TABLE IF EXISTS `tbl_dp_entrusted`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_dp_entrusted` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_id` varchar(50) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `requester_id` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` decimal(10,3) NOT NULL,
  `remaining_quantity` decimal(10,3) NOT NULL,
  `expected_return_at` date DEFAULT NULL,
  `status` tinyint(1) DEFAULT 0 COMMENT '0:Açık, 1:İade, 2:Satış, 3:Kapalı',
  `note` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `batch_id` (`batch_id`),
  KEY `warehouse_id` (`warehouse_id`),
  KEY `product_id` (`product_id`),
  KEY `requester_id` (`requester_id`),
  CONSTRAINT `tbl_dp_entrusted_ibfk_1` FOREIGN KEY (`warehouse_id`) REFERENCES `tbl_dp_warehouses` (`id`),
  CONSTRAINT `tbl_dp_entrusted_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `tbl_dp_products` (`id`),
  CONSTRAINT `tbl_dp_entrusted_ibfk_3` FOREIGN KEY (`requester_id`) REFERENCES `tbl_dp_requesters` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbl_dp_entrusted_actions`
--

DROP TABLE IF EXISTS `tbl_dp_entrusted_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_dp_entrusted_actions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entrusted_id` int(11) NOT NULL,
  `action_type` enum('return','sale') NOT NULL,
  `quantity` decimal(10,3) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `entrusted_id` (`entrusted_id`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `tbl_dp_entrusted_actions_ibfk_1` FOREIGN KEY (`entrusted_id`) REFERENCES `tbl_dp_entrusted` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_dp_entrusted_actions_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `tbl_dp_customers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbl_dp_packing_list_items`
--

DROP TABLE IF EXISTS `tbl_dp_packing_list_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_dp_packing_list_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parcel_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` float NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbl_dp_packing_list_parcels`
--

DROP TABLE IF EXISTS `tbl_dp_packing_list_parcels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_dp_packing_list_parcels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `packing_list_id` int(11) NOT NULL,
  `parcel_no` int(11) NOT NULL,
  `weight_kg` float DEFAULT NULL,
  `width_cm` float DEFAULT NULL,
  `height_cm` float DEFAULT NULL,
  `length_cm` float DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbl_dp_packing_lists`
--

DROP TABLE IF EXISTS `tbl_dp_packing_lists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_dp_packing_lists` (
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbl_dp_password_resets`
--

DROP TABLE IF EXISTS `tbl_dp_password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_dp_password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbl_dp_products`
--

DROP TABLE IF EXISTS `tbl_dp_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_dp_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `unit` varchar(20) DEFAULT 'Adet',
  `stock_alarm` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `hidden` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `procurement_status` int(11) DEFAULT 0,
  `procurement_note` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=130 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbl_dp_requesters`
--

DROP TABLE IF EXISTS `tbl_dp_requesters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_dp_requesters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `surname` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `title` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `hidden` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `password` varchar(255) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbl_dp_settings`
--

DROP TABLE IF EXISTS `tbl_dp_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_dp_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=155 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbl_dp_stock_in`
--

DROP TABLE IF EXISTS `tbl_dp_stock_in`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_dp_stock_in` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `warehouse_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `quantity` decimal(10,3) NOT NULL DEFAULT 0.000,
  `unit_price` decimal(15,4) DEFAULT 0.0000,
  `currency` enum('TL','USD','EUR') DEFAULT 'EUR',
  `price_eur` decimal(15,4) DEFAULT 0.0000,
  `note` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_by_name` varchar(255) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_by_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `warehouse_id` (`warehouse_id`),
  KEY `product_id` (`product_id`),
  KEY `supplier_id` (`supplier_id`),
  CONSTRAINT `tbl_dp_stock_in_ibfk_1` FOREIGN KEY (`warehouse_id`) REFERENCES `tbl_dp_warehouses` (`id`),
  CONSTRAINT `tbl_dp_stock_in_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `tbl_dp_products` (`id`),
  CONSTRAINT `tbl_dp_stock_in_ibfk_3` FOREIGN KEY (`supplier_id`) REFERENCES `tbl_dp_suppliers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbl_dp_stock_out`
--

DROP TABLE IF EXISTS `tbl_dp_stock_out`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_dp_stock_out` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_id` varchar(50) DEFAULT NULL,
  `warehouse_id` int(11) NOT NULL,
  `requester_id` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` decimal(10,3) NOT NULL DEFAULT 0.000,
  `unit_price` decimal(15,4) DEFAULT 0.0000,
  `total_price` decimal(15,4) DEFAULT 0.0000,
  `note` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_name` varchar(255) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_by_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `order_no` int(11) DEFAULT NULL,
  `currency` enum('TL','USD','EUR') DEFAULT 'EUR',
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `warehouse_id` (`warehouse_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `tbl_dp_stock_out_ibfk_1` FOREIGN KEY (`warehouse_id`) REFERENCES `tbl_dp_warehouses` (`id`),
  CONSTRAINT `tbl_dp_stock_out_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `tbl_dp_products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbl_dp_suppliers`
--

DROP TABLE IF EXISTS `tbl_dp_suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_dp_suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `contact` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `hidden` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbl_dp_transfer_items`
--

DROP TABLE IF EXISTS `tbl_dp_transfer_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_dp_transfer_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transfer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` decimal(10,3) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `transfer_id` (`transfer_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `tbl_dp_transfer_items_ibfk_1` FOREIGN KEY (`transfer_id`) REFERENCES `tbl_dp_transfers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_dp_transfer_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `tbl_dp_products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbl_dp_transfers`
--

DROP TABLE IF EXISTS `tbl_dp_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_dp_transfers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source_warehouse_id` int(11) NOT NULL,
  `target_warehouse_id` int(11) NOT NULL,
  `note` text DEFAULT NULL,
  `transfer_date` datetime DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `created_by_name` varchar(255) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_by_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `source_warehouse_id` (`source_warehouse_id`),
  KEY `target_warehouse_id` (`target_warehouse_id`),
  CONSTRAINT `tbl_dp_transfers_ibfk_1` FOREIGN KEY (`source_warehouse_id`) REFERENCES `tbl_dp_warehouses` (`id`),
  CONSTRAINT `tbl_dp_transfers_ibfk_2` FOREIGN KEY (`target_warehouse_id`) REFERENCES `tbl_dp_warehouses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbl_dp_users`
--

DROP TABLE IF EXISTS `tbl_dp_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_dp_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `hidden` tinyint(1) DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbl_dp_warehouses`
--

DROP TABLE IF EXISTS `tbl_dp_warehouses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_dp_warehouses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `address` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `hidden` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-07 14:07:17
-- MariaDB dump 10.19  Distrib 10.4.21-MariaDB, for osx10.10 (x86_64)
--
-- Host: localhost    Database: deppo
-- ------------------------------------------------------
-- Server version	10.4.21-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `tbl_dp_admins`
--

LOCK TABLES `tbl_dp_admins` WRITE;
/*!40000 ALTER TABLE `tbl_dp_admins` DISABLE KEYS */;
INSERT INTO `tbl_dp_admins` VALUES (1,'Sistem Admini','admin@deppo.local','$2y$12$TjopMNBrom96ayGEHam5Wuv/KRON4fkvKOnBOdVGCo4PEkeeJwjsK',1,0,'2026-02-27 23:03:39','2026-02-26 10:13:15'),(2,'BİLİNMİYOR','bilinmiyor@pianogold.local','$2y$12$ul00Up8931/lyQ05ujKJB.T4LS79ELAAI5QaAR.iPy.updHNJfO3e',1,0,'2026-02-27 23:52:40','2026-02-26 11:10:28');
/*!40000 ALTER TABLE `tbl_dp_admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tbl_dp_settings`
--

LOCK TABLES `tbl_dp_settings` WRITE;
/*!40000 ALTER TABLE `tbl_dp_settings` DISABLE KEYS */;
INSERT INTO `tbl_dp_settings` VALUES (1,'mail_host','smtp.yandex.com','2026-02-26 11:01:29'),(2,'mail_port','587','2026-02-27 17:57:01'),(3,'mail_secure','tls','2026-02-27 17:57:01'),(4,'mail_user','pcmemo@yandex.com','2026-02-27 17:55:37'),(5,'mail_pass','tglelrxbadobyyet','2026-02-27 17:55:37'),(6,'mail_from','pcmemo@yandex.com','2026-02-27 17:55:37'),(7,'mail_from_name','Depo Yönetim Sistemi','2026-02-26 10:13:11'),(8,'site_name','Test Site','2026-02-26 10:36:16'),(9,'footer_text','© 2026 Depo Yönetim Sistemi','2026-02-26 10:13:11'),(10,'header_bg','#ff0000','2026-02-26 10:36:16'),(11,'header_color','#ffffff','2026-02-26 10:13:11'),(12,'footer_bg','#343a40','2026-02-26 10:13:11'),(13,'footer_color','#ffffff','2026-02-26 10:13:11'),(14,'google_font','Lato','2026-03-01 04:47:58'),(15,'usd_rate','43.8789','2026-02-27 17:56:17'),(16,'eur_rate','51.7939','2026-02-27 17:56:17'),(17,'currency_updated','2026-02-28 03:30:07','2026-02-28 00:30:07'),(127,'system_logo_width','100','2026-03-07 11:04:24'),(128,'system_logo_height','','2026-03-07 10:56:28'),(129,'system_logo','uploads/system/logo_1772434962.png','2026-03-07 11:03:30');
/*!40000 ALTER TABLE `tbl_dp_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tbl_dp_warehouses`
--

LOCK TABLES `tbl_dp_warehouses` WRITE;
/*!40000 ALTER TABLE `tbl_dp_warehouses` DISABLE KEYS */;
INSERT INTO `tbl_dp_warehouses` VALUES (1,'NURUOSMANİYE','','',1,0,'2026-03-01 09:20:15');
/*!40000 ALTER TABLE `tbl_dp_warehouses` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-07 14:07:17
