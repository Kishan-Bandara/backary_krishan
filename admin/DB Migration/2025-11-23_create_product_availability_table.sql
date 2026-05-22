-- Migration: Create product_availability table
-- Date: 2025-11-23

CREATE TABLE IF NOT EXISTS `product_availability` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `product_id` INT(11) NOT NULL,
  `mon` TINYINT(1) NOT NULL DEFAULT 1,
  `tue` TINYINT(1) NOT NULL DEFAULT 1,
  `wed` TINYINT(1) NOT NULL DEFAULT 1,
  `thu` TINYINT(1) NOT NULL DEFAULT 1,
  `fri` TINYINT(1) NOT NULL DEFAULT 1,
  `sat` TINYINT(1) NOT NULL DEFAULT 1,
  `sun` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_product` (`product_id`),
  FOREIGN KEY (`product_id`) REFERENCES `item_master` (`item_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default availability for existing products (all days available)
INSERT INTO `product_availability` (`product_id`, `mon`, `tue`, `wed`, `thu`, `fri`, `sat`, `sun`)
SELECT `item_id`, 1, 1, 1, 1, 1, 1, 1
FROM `item_master`
WHERE `item_id` NOT IN (SELECT `product_id` FROM `product_availability`);