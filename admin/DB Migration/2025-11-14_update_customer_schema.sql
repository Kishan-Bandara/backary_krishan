-- Migration: Update customer schema to support new attributes
-- Date: 2025-11-14

ALTER TABLE `customer`
  CHANGE COLUMN `customer_activated` `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  CHANGE COLUMN `customer_locked` `locked` TINYINT(1) NOT NULL DEFAULT 0,
  CHANGE COLUMN `customer_cradit_limite` `credit_limit` DECIMAL(12,2) NOT NULL DEFAULT 0.00;

ALTER TABLE `customer`
  MODIFY `customer_name` VARCHAR(150) DEFAULT NULL;

ALTER TABLE `customer`
  ADD COLUMN `customer_code` VARCHAR(30) DEFAULT NULL AFTER `customer_id`,
  ADD COLUMN `address_line_1` VARCHAR(255) DEFAULT NULL AFTER `customer_address`,
  ADD COLUMN `address_line_2` VARCHAR(255) DEFAULT NULL AFTER `address_line_1`,
  ADD COLUMN `city` VARCHAR(100) DEFAULT NULL AFTER `address_line_2`,
  ADD COLUMN `postal_code` VARCHAR(20) DEFAULT NULL AFTER `city`,
  ADD COLUMN `account_hold` TINYINT(1) NOT NULL DEFAULT 0 AFTER `credit_limit`,
  ADD COLUMN `abn_no` VARCHAR(32) DEFAULT NULL AFTER `account_hold`,
  ADD COLUMN `acn_no` VARCHAR(32) DEFAULT NULL AFTER `abn_no`,
  ADD COLUMN `vat_registered` TINYINT(1) NOT NULL DEFAULT 0 AFTER `acn_no`,
  ADD COLUMN `gst_no` VARCHAR(32) DEFAULT NULL AFTER `vat_registered`,
  ADD COLUMN `payment_terms_id` INT(10) DEFAULT NULL AFTER `gst_no`,
  ADD COLUMN `customer_logo` VARCHAR(255) DEFAULT NULL AFTER `payment_terms_id`,
  ADD COLUMN `customer_price_type_id` INT(10) DEFAULT NULL AFTER `customer_logo`;

ALTER TABLE `customer`
  ADD UNIQUE KEY `idx_customer_code` (`customer_code`);

CREATE TABLE IF NOT EXISTS `customer_shipping_address` (
  `id` INT(10) NOT NULL AUTO_INCREMENT,
  `customer_id` INT(10) NOT NULL,
  `address_label` VARCHAR(50) DEFAULT NULL,
  `address_line_1` VARCHAR(255) NOT NULL,
  `address_line_2` VARCHAR(255) DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `postal_code` VARCHAR(20) DEFAULT NULL,
  `contact_no` VARCHAR(50) DEFAULT NULL,
  `attribute_1` VARCHAR(100) DEFAULT NULL,
  `attribute_2` VARCHAR(100) DEFAULT NULL,
  `attribute_3` VARCHAR(100) DEFAULT NULL,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_customer_shipping_customer` (`customer_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
