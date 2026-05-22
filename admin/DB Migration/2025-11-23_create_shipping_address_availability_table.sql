-- Migration: Create shipping_address_availability table
-- Date: 2025-11-23

CREATE TABLE IF NOT EXISTS `shipping_address_availability` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `shipping_address_id` INT(11) NOT NULL,
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
  UNIQUE KEY `unique_shipping_address` (`shipping_address_id`),
  FOREIGN KEY (`shipping_address_id`) REFERENCES `customer_shipping_address` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;