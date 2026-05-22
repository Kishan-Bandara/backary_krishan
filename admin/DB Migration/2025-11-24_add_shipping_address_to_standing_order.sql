-- Migration: Add shipping_address_id to standing_order table
-- Run on MySQL/MariaDB

ALTER TABLE `standing_order`
ADD COLUMN `shipping_address_id` INT(11) NOT NULL AFTER `customer_id`,
ADD KEY `idx_so_shipping_address` (`shipping_address_id`),
ADD CONSTRAINT `fk_so_shipping_address` FOREIGN KEY (`shipping_address_id`) REFERENCES `customer_shipping_address` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;