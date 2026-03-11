-- Migration for Warehouse Specific Alarms
CREATE TABLE IF NOT EXISTS `tbl_dp_product_warehouse_alarms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `stock_alarm` decimal(10,3) DEFAULT 0.000,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_warehouse` (`product_id`,`warehouse_id`),
  KEY `product_id` (`product_id`),
  KEY `warehouse_id` (`warehouse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
