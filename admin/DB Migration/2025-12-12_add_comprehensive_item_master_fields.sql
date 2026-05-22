-- Migration: Add comprehensive product fields to item_master table
-- Date: 2025-12-12
-- Adds all missing columns from the provided schema

ALTER TABLE `item_master`
  ADD COLUMN `nutritional_label` VARCHAR(255) DEFAULT NULL AFTER `immediate_pickups`,
  ADD COLUMN `sale_or_return` BOOLEAN NOT NULL DEFAULT FALSE AFTER `nutritional_label`,
  ADD COLUMN `product_specification` VARCHAR(255) DEFAULT NULL AFTER `sale_or_return`,
  ADD COLUMN `live` VARCHAR(3) NOT NULL DEFAULT 'yes' CHECK (live IN ('yes', 'no')) AFTER `product_specification`,
  ADD COLUMN `hide_to_all_customers` BOOLEAN NOT NULL DEFAULT FALSE AFTER `live`,
  ADD COLUMN `wholesale_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `hide_to_all_customers`,
  ADD COLUMN `retail_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `wholesale_price`,
  ADD COLUMN `item_weight_g` INTEGER DEFAULT NULL AFTER `retail_price`,
  ADD COLUMN `pack_weight_g` INTEGER DEFAULT NULL AFTER `item_weight_g`,
  ADD COLUMN `minimum_order` INTEGER DEFAULT NULL AFTER `pack_weight_g`,
  ADD COLUMN `description` TEXT DEFAULT NULL AFTER `minimum_order`,
  ADD COLUMN `default_label` VARCHAR(255) DEFAULT NULL AFTER `description`,
  ADD COLUMN `food_declarations` TEXT DEFAULT NULL AFTER `default_label`,
  ADD COLUMN `seasonal_rule` VARCHAR(255) DEFAULT NULL AFTER `food_declarations`,
  ADD COLUMN `avail_monday` SMALLINT NOT NULL DEFAULT 1 CHECK (avail_monday IN (0,1)) AFTER `seasonal_rule`,
  ADD COLUMN `avail_tuesday` SMALLINT NOT NULL DEFAULT 1 CHECK (avail_tuesday IN (0,1)) AFTER `avail_monday`,
  ADD COLUMN `avail_wednesday` SMALLINT NOT NULL DEFAULT 1 CHECK (avail_wednesday IN (0,1)) AFTER `avail_tuesday`,
  ADD COLUMN `avail_thursday` SMALLINT NOT NULL DEFAULT 1 CHECK (avail_thursday IN (0,1)) AFTER `avail_wednesday`,
  ADD COLUMN `avail_friday` SMALLINT NOT NULL DEFAULT 1 CHECK (avail_friday IN (0,1)) AFTER `avail_thursday`,
  ADD COLUMN `avail_saturday` SMALLINT NOT NULL DEFAULT 1 CHECK (avail_saturday IN (0,1)) AFTER `avail_friday`,
  ADD COLUMN `avail_sunday` SMALLINT NOT NULL DEFAULT 1 CHECK (avail_sunday IN (0,1)) AFTER `avail_saturday`,
  ADD COLUMN `unit_of_measure` VARCHAR(20) NOT NULL DEFAULT 'Gram' AFTER `avail_sunday`,
  ADD COLUMN `pack_type` VARCHAR(20) NOT NULL DEFAULT 'Bag' AFTER `unit_of_measure`;

-- Update existing records to have proper default values
UPDATE `item_master` SET
  `live` = 'yes',
  `avail_monday` = 1,
  `avail_tuesday` = 1,
  `avail_wednesday` = 1,
  `avail_thursday` = 1,
  `avail_friday` = 1,
  `avail_saturday` = 1,
  `avail_sunday` = 1,
  `unit_of_measure` = 'Gram',
  `pack_type` = 'Bag'
WHERE `live` IS NULL;