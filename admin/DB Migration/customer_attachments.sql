-- Migration to add customer attachments table
-- Allows up to 5 images and notes per customer

CREATE TABLE IF NOT EXISTS `customer_attachments` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional cleanup for older schema versions that had `type`
SET @drop_idx_sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.statistics
      WHERE table_schema = DATABASE()
        AND table_name = 'customer_attachments'
        AND index_name = 'idx_customer_type'
    ),
    'ALTER TABLE `customer_attachments` DROP INDEX `idx_customer_type`',
    'SELECT 1'
  )
);
PREPARE stmt_drop_idx FROM @drop_idx_sql;
EXECUTE stmt_drop_idx;
DEALLOCATE PREPARE stmt_drop_idx;

SET @drop_type_sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.columns
      WHERE table_schema = DATABASE()
        AND table_name = 'customer_attachments'
        AND column_name = 'type'
    ),
    'ALTER TABLE `customer_attachments` DROP COLUMN `type`',
    'SELECT 1'
  )
);
PREPARE stmt_drop_type FROM @drop_type_sql;
EXECUTE stmt_drop_type;
DEALLOCATE PREPARE stmt_drop_type;