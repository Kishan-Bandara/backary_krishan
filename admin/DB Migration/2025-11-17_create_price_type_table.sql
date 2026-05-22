-- Migration: create price_type table
-- Created: 2025-11-17

CREATE TABLE IF NOT EXISTS `price_type` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `description` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample seed data (optional)
INSERT INTO `price_type` (`description`) VALUES
  ('Retail'),
  ('Wholesale'),
  ('Trade');

-- End of migration
