-- Migration: Create countries table and seed basic data
-- Safe to run multiple times (IF NOT EXISTS + INSERT IGNORE)

CREATE TABLE IF NOT EXISTS `countries` (
  `country_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `country_name` VARCHAR(100) NOT NULL,
  `iso2` CHAR(2) DEFAULT NULL,
  `iso3` CHAR(3) DEFAULT NULL,
  `phone_code` VARCHAR(10) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`country_id`),
  UNIQUE KEY `uq_countries_name` (`country_name`),
  KEY `idx_countries_iso2` (`iso2`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed a few commonly used countries
INSERT IGNORE INTO `countries` (`country_name`, `iso2`, `iso3`, `phone_code`) VALUES
('Australia', 'AU', 'AUS', '61'),
('New Zealand', 'NZ', 'NZL', '64'),
('United States', 'US', 'USA', '1'),
('United Kingdom', 'GB', 'GBR', '44'),
('Canada', 'CA', 'CAN', '1'),
('Sri Lanka', 'LK', 'LKA', '94');
