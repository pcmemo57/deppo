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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_items`
--

LOCK TABLES `inventory_items` WRITE;
/*!40000 ALTER TABLE `inventory_items` DISABLE KEYS */;
INSERT INTO `inventory_items` VALUES (1,1,1,0.0000,5.0000,5.0000,'2026-03-07 15:12:30',''),(2,1,3,0.0000,45.0000,45.0000,'2026-03-07 15:12:34',''),(3,1,45,0.0000,56.0000,56.0000,'2026-03-07 15:12:39',''),(4,1,32,0.0000,34.0000,34.0000,'2026-03-07 15:12:44','');
/*!40000 ALTER TABLE `inventory_items` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_sessions`
--

LOCK TABLES `inventory_sessions` WRITE;
/*!40000 ALTER TABLE `inventory_sessions` DISABLE KEYS */;
INSERT INTO `inventory_sessions` VALUES (1,1,'closed','2026-03-07 15:12:22','2026-03-07 15:12:50',1,'');
/*!40000 ALTER TABLE `inventory_sessions` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `tbl_dp_admins`
--

LOCK TABLES `tbl_dp_admins` WRITE;
/*!40000 ALTER TABLE `tbl_dp_admins` DISABLE KEYS */;
INSERT INTO `tbl_dp_admins` VALUES (1,'Sistem Admini','admin@deppo.local','$2y$12$TjopMNBrom96ayGEHam5Wuv/KRON4fkvKOnBOdVGCo4PEkeeJwjsK',1,0,'2026-03-10 12:44:30','2026-02-26 10:13:15'),(2,'BİLİNMİYOR','bilinmiyor@pianogold.local','$2y$12$ul00Up8931/lyQ05ujKJB.T4LS79ELAAI5QaAR.iPy.updHNJfO3e',1,0,'2026-02-27 23:52:40','2026-02-26 11:10:28');
/*!40000 ALTER TABLE `tbl_dp_admins` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `tbl_dp_customers`
--

LOCK TABLES `tbl_dp_customers` WRITE;
/*!40000 ALTER TABLE `tbl_dp_customers` DISABLE KEYS */;
INSERT INTO `tbl_dp_customers` VALUES (1,'NURUOSMANİYE MAĞAZA',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(2,'NİŞANTAŞI MAĞAZA',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(3,'ANTALYA MAĞAZA',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(4,'YAĞMUR HANIM',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(5,'FURKAN KUYUM BURSA',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(6,'DİJİTAL',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(7,'MUHASEBE',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(8,'VOUGE MARMARİS',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(9,'CARİNE ANKARA',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(10,'D DİAMOND BEYLİKDÜZÜ',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(11,'İSPANYA TUR',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(12,'İTALYA FUAR',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(13,'HULUSİ BAĞCI KUYUMCULUK',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(14,'HS KUYUM GAZİANTEP',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(15,'NURUOSMANİYE TOPTAN',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(16,'D DİAMOND ANKARA',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(17,'ENDERİN SAKARYA',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(18,'ANTALYA FUAR',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(19,'ANKARA D DİAMOND',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(20,'İSRAİL DANDİ JEWELLERY',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(21,'KARADENİZ TUR',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(22,'KOSOVA FİGOLD',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(23,'LÜXEN METROPOL',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(24,'ADİL İDİL İZMİR',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(25,'BAHADIR KUYUM İSTANBUL',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(26,'ANKARA ONR',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(27,'ATÖLYE',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(28,'SABİHA GÖKÇEN BLUE DİAMOND',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(29,'BAKIRCI NAZİLLİ',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(30,'AYDIN KUYUM DİDİM',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(31,'MERO BULGARİSTAN',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(32,'TODOR BULGARİSTAN',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(33,'ROSE KUYUM',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(34,'ALTIN İŞ KUYUM',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(35,'TRABZON JEWELİNA KUYUMCULUK',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(36,'AZERBEYCAN LEVATA',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(37,'TSENKA',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(38,'TRAUM ALMANYA',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(39,'ISPARTA ALTINİŞ',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(40,'İZMİR FUAR',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(41,'ADANA PASHA',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(42,'DENİZLİ SAVAŞ',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(43,'BOLU YAMAN',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(44,'YALÇINLAR İSTANBUL',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(45,'ANTEP GÜLENLER',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(46,'ANTALYA DOĞUŞ',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(47,'ANTALYA XXX',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(48,'KABAN BULGARİSTAN',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(49,'KANADA VANİLES',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(50,'İRAN GİSELO',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(51,'DİDO BULGARİSTAN',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(52,'DERVİŞOĞLU',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(53,'ADİL DİRENÇ',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(54,'İSTANBUL FUAR',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(55,'MANDARİN FUAR',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(56,'TRABZON KARALTIN',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(57,'MODEL KUYUMCULUK',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(58,'DEMİR CONTRY BLUE DİAMOND',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(59,'ARTHESDAM JEWELRY',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(60,'SEOİDİN İRLANDA',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(61,'ÇANTA POP',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(62,'ORDU BLUE DİAMOND',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(63,'ÖZYER KUYUMCULUK',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(64,'ADRES KUYUMCULUK',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(65,'MECİDİYE KUYUMCULUK',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(66,'İLKER KARAOĞLAN HATAY',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(67,'NEVŞEHİR UZER KUYUM',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(68,'KONYA ÖZBOYACI',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(69,'DENİZLİ SELİN',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(70,'AKSARAY AKBAŞLAR',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(71,'ANKARA ALTINKAYNAK KIZILAY',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(72,'ANKARA ALTINKAYNAK ULUS',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(73,'ANKARA MALL BLUEDİAMOND',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(74,'ANKARA BENLİ',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(75,'BOLU CENGİZ',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(76,'DENİZLİ BAĞCI',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(77,'PİANO KAYA KEMER',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(78,'SİNA TRABZON',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(79,'MURAT KUYUM ORDU',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(80,'OLİMPİA',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(81,'ELİT GOLD',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(82,'BURHAN',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(83,'ELDORO',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(84,'BLOOM',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(85,'SARKİZ',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(86,'POP',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(87,'EDİRNE BLUE DİAMOND',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(88,'STOK SAYIM',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(89,'LUSHA KUYUMCULUK',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(90,'TERAS  STÜDYO',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(91,'GALATA BODRUM',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(92,'SELİM DERVİŞOĞLU BARTIN',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(93,'ÖZYER FETHİYE',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(94,'ANTALYA TUR',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(95,'GİNKA',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(96,'SİNA KÜÇÜKÇEKMECE',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(97,'BAYCAN ÇANTA',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(98,'TİTANİC OTEL YENİ',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(99,'ROSE KUYUM YENİ',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(100,'KILIÇ KUYUM',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(101,'MERSİN SERDAR AKSU',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(102,'ROYAL KUNDU',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(103,'GALATA KUŞADASI',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(104,'ÇANTA TANITIM',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(105,'TENGRİ MOĞOLİSTAN',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(106,'SİVAS SİNA',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(107,'UK KIRKLARELİ',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(108,'ZÜMRÜT KUYUM',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(109,'BOLU YAMANER ETKİNLİK',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(110,'FAY YAMANER ÇANAKKALE',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(111,'DENİZLİ SAFİR',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(112,'ANKARA SERKAN GÜNER',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(113,'BAKIRKÖY DERYA SEVGEN',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(114,'ARMAN',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(115,'NİXA',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(116,'GALYA',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(117,'MK ORO',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:27'),(118,'ANTALYA RB',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:28'),(119,'KEMİT MISIR',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:28'),(120,'KIBRIS NAZ KUYUM',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:28'),(121,'TURAN GOLD AZERBEYCAN',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:28'),(122,'BELLA GOLD URLA',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:28'),(123,'CANTEKİN KIRKLARELİ',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:28'),(124,'VELVET PETRİCH',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:28'),(125,'GÖKMEN',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:28'),(126,'İDA',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:28'),(127,'AHLATÇI',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:28'),(128,'DENİZLİ ZERAFET',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:28'),(129,'YADE MEDİKAL',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:28'),(130,'MERİ',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:28'),(131,'AYTAÇ KAMAR',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:28'),(132,'MEYDAN KUYUM B.ÇEKMECE',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:28'),(133,'BAYRAMPAŞA GÜLENLER',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:28'),(134,'METRO GOLD ZEYTİNBURNU',NULL,NULL,NULL,NULL,1,0,'2026-03-01 09:14:28');
/*!40000 ALTER TABLE `tbl_dp_customers` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `tbl_dp_entrusted`
--

LOCK TABLES `tbl_dp_entrusted` WRITE;
/*!40000 ALTER TABLE `tbl_dp_entrusted` DISABLE KEYS */;
/*!40000 ALTER TABLE `tbl_dp_entrusted` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `tbl_dp_entrusted_actions`
--

LOCK TABLES `tbl_dp_entrusted_actions` WRITE;
/*!40000 ALTER TABLE `tbl_dp_entrusted_actions` DISABLE KEYS */;
/*!40000 ALTER TABLE `tbl_dp_entrusted_actions` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_dp_packing_list_items`
--

LOCK TABLES `tbl_dp_packing_list_items` WRITE;
/*!40000 ALTER TABLE `tbl_dp_packing_list_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `tbl_dp_packing_list_items` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_dp_packing_list_parcels`
--

LOCK TABLES `tbl_dp_packing_list_parcels` WRITE;
/*!40000 ALTER TABLE `tbl_dp_packing_list_parcels` DISABLE KEYS */;
/*!40000 ALTER TABLE `tbl_dp_packing_list_parcels` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_dp_packing_lists`
--

LOCK TABLES `tbl_dp_packing_lists` WRITE;
/*!40000 ALTER TABLE `tbl_dp_packing_lists` DISABLE KEYS */;
/*!40000 ALTER TABLE `tbl_dp_packing_lists` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `tbl_dp_password_resets`
--

LOCK TABLES `tbl_dp_password_resets` WRITE;
/*!40000 ALTER TABLE `tbl_dp_password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `tbl_dp_password_resets` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=134 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_dp_products`
--

LOCK TABLES `tbl_dp_products` WRITE;
/*!40000 ALTER TABLE `tbl_dp_products` DISABLE KEYS */;
INSERT INTO `tbl_dp_products` VALUES (1,'ANTALYA SİPARİŞ FORM','','',NULL,'Adet',3,1,0,'2026-03-01 09:00:52',0,NULL),(2,'ANTALYA TAMİR FORM',NULL,NULL,NULL,'Adet',3,1,0,'2026-03-01 09:00:52',0,NULL),(3,'BEYAZ PIERCING PLEKSİ NOKTALI',NULL,NULL,NULL,'Adet',0,1,0,'2026-03-01 09:00:52',0,NULL),(4,'CHARM TABLA',NULL,NULL,NULL,'Adet',3,1,0,'2026-03-01 09:00:52',0,NULL),(5,'DELİM SERTİFİKASI İNG',NULL,NULL,NULL,'Adet',20,1,0,'2026-03-01 09:00:52',0,NULL),(6,'DELİM SERTİFİKASI RUS',NULL,NULL,NULL,'Adet',20,1,0,'2026-03-01 09:00:52',0,NULL),(7,'DELİM SERTİFİKASI TR',NULL,NULL,NULL,'Adet',20,1,0,'2026-03-01 09:00:52',0,NULL),(8,'DISPLAY ALTIN',NULL,NULL,NULL,'Adet',20,1,0,'2026-03-01 09:00:52',0,NULL),(9,'DISPLAY PIERCING',NULL,NULL,NULL,'Adet',20,1,0,'2026-03-01 09:00:52',0,NULL),(10,'FAME TABLA',NULL,NULL,NULL,'Adet',0,1,0,'2026-03-01 09:00:52',0,NULL),(11,'HAZALINK TABLA',NULL,NULL,NULL,'Adet',0,1,0,'2026-03-01 09:00:52',0,NULL),(12,'HEDİYE KARTI',NULL,NULL,NULL,'Adet',100,1,0,'2026-03-01 09:00:52',0,NULL),(13,'INFINITY STANDI',NULL,NULL,NULL,'Adet',3,1,0,'2026-03-01 09:00:52',0,NULL),(14,'KANVAS ÇANTA',NULL,NULL,NULL,'Adet',10,1,0,'2026-03-01 09:00:52',0,NULL),(15,'KARGO KUTUSU ONLINE',NULL,NULL,NULL,'Adet',300,1,0,'2026-03-01 09:00:52',0,NULL),(16,'KATALOG TÜRKÇE VE İNGİLİZCE',NULL,NULL,NULL,'Adet',0,1,0,'2026-03-01 09:00:52',0,NULL),(17,'KIRMIZI FULAR',NULL,NULL,NULL,'Adet',50,1,0,'2026-03-01 09:00:52',0,NULL),(18,'KIRMIZI POŞET',NULL,NULL,NULL,'Adet',2000,1,0,'2026-03-01 09:00:52',0,NULL),(19,'MUG BURUN',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(20,'MUG DUDAK',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(21,'MUG GÖGÜS',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(22,'MUG KULAK',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(23,'MÜŞTERİ BİLGİ FORMU İNG',NULL,NULL,NULL,'Adet',3,1,0,'2026-03-01 09:00:52',0,NULL),(24,'MÜŞTERİ BİLGİ FORMU TR',NULL,NULL,NULL,'Adet',100,1,0,'2026-03-01 09:00:52',0,NULL),(25,'NİŞANTAŞI SİPARİŞ FORM',NULL,NULL,NULL,'Adet',3,1,0,'2026-03-01 09:00:52',0,NULL),(26,'NİŞANTAŞI TAMİR FORM',NULL,NULL,NULL,'Adet',3,1,0,'2026-03-01 09:00:52',0,NULL),(27,'PENS / BİR UCU KAPALI MAKAS',NULL,NULL,NULL,'Adet',3,1,0,'2026-03-01 09:00:52',0,NULL),(28,'PENS / İKİ UCU KAPALI MAKAS',NULL,NULL,NULL,'Adet',3,1,0,'2026-03-01 09:00:52',0,NULL),(29,'PENS / OVAL UÇLU MAKAS',NULL,NULL,NULL,'Adet',3,1,0,'2026-03-01 09:00:52',0,NULL),(30,'PENS / PIANO PIM',NULL,NULL,NULL,'Adet',0,1,0,'2026-03-01 09:00:52',0,NULL),(31,'PENS / TOP TUTUCU',NULL,NULL,NULL,'Adet',3,1,0,'2026-03-01 09:00:52',0,NULL),(32,'PENS / TUTMA SIKMA PENSİ',NULL,NULL,NULL,'Adet',3,1,0,'2026-03-01 09:00:52',0,NULL),(33,'PENS / UCU EĞİK MAKAS',NULL,NULL,NULL,'Adet',3,1,0,'2026-03-01 09:00:52',0,NULL),(34,'PENS / UCU KALIN MAKAS',NULL,NULL,NULL,'Adet',3,1,0,'2026-03-01 09:00:52',0,NULL),(35,'PENS / ÜÇGEN MAKAS',NULL,NULL,NULL,'Adet',3,1,0,'2026-03-01 09:00:52',0,NULL),(36,'PIANO DERGİ',NULL,NULL,NULL,'Adet',200,1,0,'2026-03-01 09:00:52',0,NULL),(37,'PIANO KIRMIZI KESE',NULL,NULL,NULL,'Adet',200,1,0,'2026-03-01 09:00:52',0,NULL),(38,'PIANO METAL KUTU',NULL,NULL,NULL,'Adet',3500,1,0,'2026-03-01 09:00:52',0,NULL),(39,'PIANO SİYAH KESE',NULL,NULL,NULL,'Adet',250,1,0,'2026-03-01 09:00:52',0,NULL),(40,'PIANO TİŞÖRT',NULL,NULL,NULL,'Adet',1,1,0,'2026-03-01 09:00:52',0,NULL),(41,'PIANO ÜRÜN TEMİZLEME BEZİ',NULL,NULL,NULL,'Adet',0,1,0,'2026-03-01 09:00:52',0,NULL),(42,'PIANO ÜRÜN TEMİZLEME BEZİ BÜYÜK',NULL,NULL,NULL,'Adet',0,1,0,'2026-03-01 09:00:52',0,NULL),(43,'PIERCING BAKIM TALİMATI İNG',NULL,NULL,NULL,'Adet',100,1,0,'2026-03-01 09:00:52',0,NULL),(44,'PIERCING BAKIM TALİMATI RUS',NULL,NULL,NULL,'Adet',100,1,0,'2026-03-01 09:00:52',0,NULL),(45,'PIERCING BAKIM TALİMATI TR',NULL,NULL,NULL,'Adet',100,1,0,'2026-03-01 09:00:52',0,NULL),(46,'PIERCING CAM ŞİŞE',NULL,NULL,NULL,'Adet',100,1,0,'2026-03-01 09:00:52',0,NULL),(47,'PIRLANTA GERDANLIK KUTUSU',NULL,NULL,NULL,'Adet',10,1,0,'2026-03-01 09:00:52',0,NULL),(48,'PIRLANTA KELEPÇE KUTUSU',NULL,NULL,NULL,'Adet',10,1,0,'2026-03-01 09:00:52',0,NULL),(49,'PIRLANTA KOLYE KUTUSU',NULL,NULL,NULL,'Adet',10,1,0,'2026-03-01 09:00:52',0,NULL),(50,'PIRLANTA YÜZÜK KUTUSU',NULL,NULL,NULL,'Adet',10,1,0,'2026-03-01 09:00:52',0,NULL),(51,'PİERCİNG İĞNESİ TUTUCU',NULL,NULL,NULL,'Adet',4,1,0,'2026-03-01 09:00:52',0,NULL),(52,'SİLİKON KIRMIZI KULAK',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(53,'SİLİKON SİYAH BURUN',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(54,'SİLİKON SİYAH DİL',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(55,'SİLİKON SİYAH DUDAK',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(56,'SİLİKON SİYAH GÖBEK',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(57,'SİLİKON SİYAH GÖGÜS',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(58,'SİLİKON SİYAH KAŞ',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(59,'SİLİKON SİYAH KULAK',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(60,'SİLİKON TEN BURUN',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(61,'SİLİKON TEN DİL',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(62,'SİLİKON TEN DUDAK',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(63,'SİLİKON TEN GENİTAL',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(64,'SİLİKON TEN GÖBEK',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(65,'SİLİKON TEN GÖGÜS',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(66,'SİLİKON TEN KAŞ',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(67,'SİLİKON TEN KULAK',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(68,'SİYAH KESE PED KUTU İÇİ',NULL,NULL,NULL,'Adet',0,1,0,'2026-03-01 09:00:52',0,NULL),(69,'SİYAH PLEKSİ BÜYÜK',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(70,'SİYAH PLEKSİ KÜÇÜK',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(71,'SİYAH PLEKSİ ORTA',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(72,'STUDYO / ALET DEZENFEKTAN',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(73,'STUDYO / ATIK KOVA',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(74,'STUDYO / AYNA',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(75,'STUDYO / BATİKON',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(76,'STUDYO / ÇÖP KOVASI',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(77,'STUDYO / EL DEZENFEKTAN',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(78,'STUDYO / ELDİVEN L',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(79,'STUDYO / ELDİVEN M',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(80,'STUDYO / ELDİVEN S',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(81,'STUDYO / IŞIKLI KALEM',NULL,NULL,NULL,'Adet',3,1,0,'2026-03-01 09:00:52',0,NULL),(82,'STUDYO / İĞNE GRİ',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(83,'STUDYO / İĞNE PEMBE',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(84,'STUDYO / İĞNE YEŞİL',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(85,'STUDYO / KULAK ÇUBUĞU',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(86,'STUDYO / KÜRDAN',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(87,'STUDYO / KÜVET BÖBREK',NULL,NULL,NULL,'Adet',3,1,0,'2026-03-01 09:00:52',0,NULL),(88,'STUDYO / KÜVET KARE',NULL,NULL,NULL,'Adet',3,1,0,'2026-03-01 09:00:52',0,NULL),(89,'STUDYO / MÜREKKEP',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(90,'STUDYO / PAMUK',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(91,'STUDYO / SARGI BEZİ',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(92,'STUDYO / SEDYE ÖRTÜ',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(93,'STUDYO / SERUM FİZYOLOJİK',NULL,NULL,NULL,'Adet',50,1,0,'2026-03-01 09:00:52',0,NULL),(94,'STUDYO / SETUP ÖRTÜSÜ',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(95,'STUDYO / SİYAH ÖNLÜK',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(96,'STUDYO / STERİL POŞET',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(97,'STUDYO / TABURE',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(98,'STUDYO / VAZELİN',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(99,'STUDYO / YÜZEY DEZENFEKTAN',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(100,'TELEFON TUTACAĞI',NULL,NULL,NULL,'Adet',200,1,0,'2026-03-01 09:00:52',0,NULL),(101,'ÜRÜN SERTİFİKASI',NULL,NULL,NULL,'Adet',2000,1,0,'2026-03-01 09:00:52',0,NULL),(102,'YÜZÜK ÖLÇER',NULL,NULL,NULL,'Adet',5,1,0,'2026-03-01 09:00:52',0,NULL),(103,'ONLİNE KARGO KUTUSU KÜÇÜK',NULL,NULL,NULL,'Adet',0,1,0,'2026-03-01 09:00:52',0,NULL),(104,'MOCHA  STAND',NULL,NULL,NULL,'Adet',0,1,0,'2026-03-01 09:00:52',0,NULL),(105,'PİANO CÜZDAN',NULL,NULL,NULL,'Adet',0,1,0,'2026-03-01 09:00:52',0,NULL),(106,'PİANO PLAJ ÇANTA',NULL,NULL,NULL,'Adet',0,1,0,'2026-03-01 09:00:52',0,NULL),(107,'PİANO EŞARP',NULL,NULL,NULL,'Adet',0,1,0,'2026-03-01 09:00:52',0,NULL),(108,'RENKLİ ÜRÜN KUTUSU',NULL,NULL,NULL,'Adet',0,1,0,'2026-03-01 09:00:52',0,NULL),(109,'BEYAZ PLEKSİ BÜYÜK',NULL,NULL,NULL,'Adet',0,1,0,'2023-12-03 21:00:00',0,NULL),(110,'BEYAZ PLEKSİ KÜÇÜK',NULL,NULL,NULL,'Adet',0,1,0,'2023-12-03 21:00:00',0,NULL),(111,'BEYAZ PLEKSİ ORTA',NULL,NULL,NULL,'Adet',0,1,0,'2023-12-03 21:00:00',0,NULL),(112,'DISPLAY DIAMOND',NULL,NULL,NULL,'Adet',0,1,0,'2023-12-03 21:00:00',0,NULL),(113,'KATALOG İNG',NULL,NULL,NULL,'Adet',0,1,0,'2023-12-03 21:00:00',0,NULL),(114,'KATALOG TR',NULL,NULL,NULL,'Adet',0,1,0,'2023-12-03 21:00:00',0,NULL),(115,'KİTAP İYOT',NULL,NULL,NULL,'Adet',0,1,0,'2024-08-17 21:00:00',0,NULL),(116,'KÜPE STAND',NULL,NULL,NULL,'Adet',0,1,0,'2025-08-20 21:00:00',0,NULL),(117,'NURUOSMANİYE SİPARİŞ FORM',NULL,NULL,NULL,'Adet',0,1,0,'2023-12-03 21:00:00',0,NULL),(118,'NURUOSMANİYE TAMİR FORM',NULL,NULL,NULL,'Adet',0,1,0,'2023-12-03 21:00:00',0,NULL),(119,'PIANO AJANDA',NULL,NULL,NULL,'Adet',0,1,0,'2023-12-03 21:00:00',0,NULL),(120,'PIANO BEYAZ POŞET',NULL,NULL,NULL,'Adet',0,1,0,'2023-12-03 21:00:00',0,NULL),(121,'PIANO BLOKNOT',NULL,NULL,NULL,'Adet',0,1,0,'2023-12-03 21:00:00',0,NULL),(122,'PİERCİNG PLEKSİ',NULL,NULL,NULL,'Adet',0,1,0,'2023-12-03 21:00:00',0,NULL),(123,'STUDYO / TEL TOKA',NULL,NULL,NULL,'Adet',0,1,0,'2023-12-03 21:00:00',0,NULL),(124,'TOPTAN BİLEKLİK',NULL,NULL,NULL,'Adet',0,1,0,'2023-12-03 21:00:00',0,NULL),(125,'TOPTAN KOLYELİK',NULL,NULL,NULL,'Adet',0,1,0,'2023-12-03 21:00:00',0,NULL),(126,'TOPTAN KULAK',NULL,NULL,NULL,'Adet',0,1,0,'2023-12-03 21:00:00',0,NULL),(127,'TOPTAN KÜPELİK',NULL,NULL,NULL,'Adet',0,1,0,'2023-12-03 21:00:00',0,NULL),(128,'YENİ PİANO DERGİ',NULL,NULL,NULL,'Adet',0,1,0,'2025-04-06 21:00:00',0,NULL),(129,'YÜZÜK STANDI',NULL,NULL,NULL,'Adet',0,1,0,'2023-12-03 21:00:00',0,NULL),(130,'Sitrin Taşlı Yüzük','','',NULL,'Adet',0,1,0,'2026-03-07 12:13:32',0,NULL),(131,'sdsddsds','','',NULL,'Adet',0,1,0,'2026-03-07 13:00:03',0,NULL),(132,'123','','',NULL,'Adet',0,1,0,'2026-03-07 13:22:10',0,NULL),(133,'121221','','',NULL,'Adet',0,1,0,'2026-03-07 13:34:00',0,'');
/*!40000 ALTER TABLE `tbl_dp_products` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `tbl_dp_requesters`
--

LOCK TABLES `tbl_dp_requesters` WRITE;
/*!40000 ALTER TABLE `tbl_dp_requesters` DISABLE KEYS */;
INSERT INTO `tbl_dp_requesters` VALUES (1,'EROL','Çantacı',NULL,NULL,1,0,'2026-03-01 09:11:59',NULL,NULL,NULL),(2,'ÜMİT','Çantacı',NULL,NULL,1,0,'2026-03-01 09:11:59',NULL,NULL,NULL),(3,'EREN','Çantacı',NULL,NULL,1,0,'2026-03-01 09:11:59',NULL,NULL,NULL),(4,'MURAT','MURAT','','',1,0,'2026-03-01 09:11:59',NULL,NULL,NULL),(5,'ALİ','KARAAHMETOĞLU','','',1,0,'2026-03-01 09:11:59',NULL,NULL,NULL),(6,'BEYZA','Dijital',NULL,NULL,1,0,'2026-03-01 09:11:59',NULL,NULL,NULL),(7,'ESİN','Niş.',NULL,NULL,1,0,'2026-03-01 09:11:59',NULL,NULL,NULL),(8,'MELEK','Ant.',NULL,NULL,1,0,'2026-03-01 09:11:59',NULL,NULL,NULL),(9,'AYTAÇ','KAMAR',NULL,NULL,1,0,'2026-03-01 09:11:59',NULL,NULL,NULL),(10,'HAZAL','KAYA','','',1,0,'2026-03-01 09:11:59',NULL,NULL,NULL),(11,'YASEMİN','YASEMİN','','',1,0,'2026-03-01 09:26:07',NULL,NULL,NULL),(12,'ESİN','ESİN','','',1,0,'2026-03-01 09:26:46',NULL,NULL,NULL),(13,'BAYCAN','BAYCAN','','',1,0,'2026-03-01 09:30:10',NULL,NULL,NULL),(14,'BENSU','BENSU','','',1,0,'2026-03-01 09:30:21',NULL,NULL,NULL),(15,'SAFFET','SAFFET','','',1,0,'2026-03-01 09:30:31',NULL,NULL,NULL),(16,'BERKE','BERKE','','',1,0,'2026-03-01 09:30:55',NULL,NULL,NULL),(17,'ALPER','ALPER','','',1,0,'2026-03-01 09:31:36',NULL,NULL,NULL),(18,'ELİF','ELİF','','',1,0,'2026-03-01 09:31:45',NULL,NULL,NULL),(19,'KAAN','KAAN','','',1,0,'2026-03-01 09:31:54',NULL,NULL,NULL),(20,'SERDAR','AKÇİMEN','','',1,0,'2026-03-01 09:32:14',NULL,NULL,NULL);
/*!40000 ALTER TABLE `tbl_dp_requesters` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=230 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_dp_settings`
--

LOCK TABLES `tbl_dp_settings` WRITE;
/*!40000 ALTER TABLE `tbl_dp_settings` DISABLE KEYS */;
INSERT INTO `tbl_dp_settings` VALUES (1,'mail_host','smtp.yandex.com','2026-02-26 11:01:29'),(2,'mail_port','587','2026-02-27 17:57:01'),(3,'mail_secure','tls','2026-02-27 17:57:01'),(4,'mail_user','pcmemo@yandex.com','2026-02-27 17:55:37'),(5,'mail_pass','tglelrxbadobyyet','2026-02-27 17:55:37'),(6,'mail_from','pcmemo@yandex.com','2026-02-27 17:55:37'),(7,'mail_from_name','Depo Yönetim Sistemi','2026-02-26 10:13:11'),(8,'site_name','Test Site','2026-02-26 10:36:16'),(9,'footer_text','© 2026 Depo Yönetim Sistemi','2026-02-26 10:13:11'),(10,'header_bg','#ff0000','2026-02-26 10:36:16'),(11,'header_color','#ffffff','2026-02-26 10:13:11'),(12,'footer_bg','#343a40','2026-02-26 10:13:11'),(13,'footer_color','#ffffff','2026-02-26 10:13:11'),(14,'google_font','Lato','2026-03-01 04:47:58'),(15,'usd_rate','43.9846','2026-03-07 11:09:47'),(16,'eur_rate','50.9822','2026-03-07 11:09:47'),(17,'currency_updated','2026-03-09 07:45:44','2026-03-09 04:45:44'),(127,'system_logo_width','220','2026-03-09 04:44:28'),(128,'system_logo_height','','2026-03-07 10:56:28'),(129,'system_logo','uploads/system/logo_1773031453.png','2026-03-09 04:44:13'),(200,'last_update_check','2026-03-10','2026-03-10 06:35:57'),(223,'allow_passive_with_stock','0','2026-03-10 09:44:57'),(224,'show_bulk_stock_update_to_user','0','2026-03-10 09:45:09'),(225,'kargo_gonderici','','2026-03-10 09:44:57');
/*!40000 ALTER TABLE `tbl_dp_settings` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_dp_stock_in`
--

LOCK TABLES `tbl_dp_stock_in` WRITE;
/*!40000 ALTER TABLE `tbl_dp_stock_in` DISABLE KEYS */;
INSERT INTO `tbl_dp_stock_in` VALUES (1,1,133,5,12.000,23.0000,'TL',0.4511,'',1,1,'Sistem Admini',NULL,NULL,'2026-03-07 13:34:19');
/*!40000 ALTER TABLE `tbl_dp_stock_in` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_dp_stock_out`
--

LOCK TABLES `tbl_dp_stock_out` WRITE;
/*!40000 ALTER TABLE `tbl_dp_stock_out` DISABLE KEYS */;
INSERT INTO `tbl_dp_stock_out` VALUES (1,'SO-20260307163609-7685',1,17,64,133,1.000,0.4511,0.4511,'',1,'Sistem Admini',NULL,NULL,'2026-03-07 13:36:09',1,'EUR',1);
/*!40000 ALTER TABLE `tbl_dp_stock_out` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_dp_suppliers`
--

LOCK TABLES `tbl_dp_suppliers` WRITE;
/*!40000 ALTER TABLE `tbl_dp_suppliers` DISABLE KEYS */;
INSERT INTO `tbl_dp_suppliers` VALUES (1,'BİLİNMİYOR','','','','',1,0,'2026-03-01 09:16:39'),(2,'Roberto Bravo','','ozdemir@robertobravo.com','','Mollafenari Mahallesi Türk Ocağı Caddesi No.1 Kat:3 Cağaloğlu',1,0,'2026-03-07 12:13:45'),(3,'ewwewe','','','','',1,0,'2026-03-07 13:00:17'),(4,'12121','','','','',1,0,'2026-03-07 13:22:18'),(5,'2323','','','','',1,0,'2026-03-07 13:34:10');
/*!40000 ALTER TABLE `tbl_dp_suppliers` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `tbl_dp_transfer_items`
--

LOCK TABLES `tbl_dp_transfer_items` WRITE;
/*!40000 ALTER TABLE `tbl_dp_transfer_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `tbl_dp_transfer_items` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `tbl_dp_transfers`
--

LOCK TABLES `tbl_dp_transfers` WRITE;
/*!40000 ALTER TABLE `tbl_dp_transfers` DISABLE KEYS */;
/*!40000 ALTER TABLE `tbl_dp_transfers` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `tbl_dp_users`
--

LOCK TABLES `tbl_dp_users` WRITE;
/*!40000 ALTER TABLE `tbl_dp_users` DISABLE KEYS */;
INSERT INTO `tbl_dp_users` VALUES (1,'Murat Pianogold','murat@pianogold.com','$2y$12$rjHsLoSsxyyAZqKNq/2Su.Zib2TdRPh9Ff/pt90RL7V8hCWeYQWm6',1,0,'2026-03-10 09:40:13','2026-02-26 11:22:23');
/*!40000 ALTER TABLE `tbl_dp_users` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_dp_warehouses`
--

LOCK TABLES `tbl_dp_warehouses` WRITE;
/*!40000 ALTER TABLE `tbl_dp_warehouses` DISABLE KEYS */;
INSERT INTO `tbl_dp_warehouses` VALUES (1,'NURUOSMANİYE','','',1,0,'2026-03-01 09:20:15'),(2,'KUYUMCUKENT','','',1,0,'2026-03-07 13:35:28');
/*!40000 ALTER TABLE `tbl_dp_warehouses` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-10 13:20:05
