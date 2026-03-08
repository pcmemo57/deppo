-- Test Migration v1.2.31
-- Create and then Drop a temp table to test multi-statement execution
CREATE TABLE IF NOT EXISTS `tbl_tmp_test` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `val` varchar(50) DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add a column to our settings just for a split second 
-- (Wait, actually let's just do a simple drop to verify the requirement)
DROP TABLE IF EXISTS `tbl_tmp_test`;
