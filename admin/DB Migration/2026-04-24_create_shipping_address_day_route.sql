-- Migration: Create shipping_address_day_route table
-- Date: 2026-04-24
-- Purpose: Store the delivery route assigned to each day of the week per shipping address

CREATE TABLE IF NOT EXISTS `shipping_address_day_route` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `shipping_address_id` INT(11) NOT NULL,
  `mon_route_id` INT(11) DEFAULT NULL,
  `tue_route_id` INT(11) DEFAULT NULL,
  `wed_route_id` INT(11) DEFAULT NULL,
  `thu_route_id` INT(11) DEFAULT NULL,
  `fri_route_id` INT(11) DEFAULT NULL,
  `sat_route_id` INT(11) DEFAULT NULL,
  `sun_route_id` INT(11) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_shipping_address` (`shipping_address_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
