-- Standing Orders schema
-- Run on MySQL/MariaDB

CREATE TABLE IF NOT EXISTS `standing_order` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `customer_id` INT(11) NOT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `DeliveryAmount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_so_customer` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `standing_order_item` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `standing_order_id` INT(11) NOT NULL,
  `item_id` INT(11) NOT NULL,
  `mon_qty` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `tue_qty` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `wed_qty` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `thu_qty` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `fri_qty` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `sat_qty` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `sun_qty` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_soi_soid` (`standing_order_id`),
  KEY `idx_soi_item` (`item_id`),
  CONSTRAINT `fk_soi_so` FOREIGN KEY (`standing_order_id`) REFERENCES `standing_order`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
