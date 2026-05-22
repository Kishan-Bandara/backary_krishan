-- Migration: Create payment_terms table and seed basic data
-- Safe to run multiple times (IF NOT EXISTS + INSERT IGNORE)

CREATE TABLE IF NOT EXISTS `payment_terms` (
  `payment_terms_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_terms_name` VARCHAR(100) NOT NULL,
  `net_days` INT UNSIGNED DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`payment_terms_id`),
  UNIQUE KEY `uq_payment_terms_name` (`payment_terms_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed common payment terms; names align with UI expectations
INSERT IGNORE INTO `payment_terms` (`payment_terms_name`, `net_days`, `is_active`) VALUES
('Cash on Delivery', 0, 1),
('Prepaid', 0, 1),
('7 Days', 7, 1),
('14 Days', 14, 1),
('30 Days', 30, 1),
('45 Days', 45, 1),
('60 Days', 60, 1);
