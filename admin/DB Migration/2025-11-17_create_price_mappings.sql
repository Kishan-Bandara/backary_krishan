-- Migration: create product_price_mapping and price_type_customer_mapping
-- Created: 2025-11-17

-- Table: product_price_mapping
CREATE TABLE IF NOT EXISTS `product_price_mapping` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `price_type_id` INT UNSIGNED NOT NULL,
  `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_product_price_type` (`product_id`, `price_type_id`),
  KEY `idx_product` (`product_id`),
  KEY `idx_price_type` (`price_type_id`),
  CONSTRAINT `fk_ppm_price_type` FOREIGN KEY (`price_type_id`) REFERENCES `price_type`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `price_type_customer_mapping` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `price_type_id` INT UNSIGNED NOT NULL,
  `customer_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_price_type_customer` (`price_type_id`, `customer_id`),
  KEY `idx_price_type` (`price_type_id`),
  KEY `idx_customer` (`customer_id`),

  CONSTRAINT `fk_ptcm_price_type`
    FOREIGN KEY (`price_type_id`) REFERENCES `price_type`(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Sample data
INSERT INTO `product_price_mapping` (`product_id`, `price_type_id`, `price`) VALUES
  (1, 1, 100.00);

INSERT INTO `price_type_customer_mapping` (`price_type_id`, `customer_id`) VALUES
  (1, 1);

-- End of migration

-- NOTE: The `price_type_customer_mapping` table references the `customer(customer_id)`
-- column. If your `customer` table does not exist yet in this database, adding a
-- foreign key inline can fail (error: referenced table not found).

-- To add the FK safely after you confirm the `customer` table exists and is InnoDB,
-- run the following SQL manually (uncomment and execute):

-- ALTER TABLE `price_type_customer_mapping`
--   ADD CONSTRAINT `fk_ptcm_customer`
--     FOREIGN KEY (`customer_id`) REFERENCES `customer`(`customer_id`)
--     ON DELETE CASCADE ON UPDATE CASCADE;

