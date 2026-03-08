-- Test Migration v1.2.32
-- Create a table and LEAVE it there to verify migration success
CREATE TABLE IF NOT EXISTS `tbl_migration_test_final` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `test_val` varchar(100) DEFAULT 'Success',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Also add a test record
INSERT INTO `tbl_migration_test_final` (`test_val`) VALUES ('Migration 1.2.32 worked!');
