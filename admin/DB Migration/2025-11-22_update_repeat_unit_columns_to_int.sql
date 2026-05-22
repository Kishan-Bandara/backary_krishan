-- Migration: Change RepeatUnit columns to INT referencing repeat_units.id
-- Date: 2025-11-22

-- First, update existing string values to corresponding IDs
UPDATE `customer` SET `RepeatUnit` = CASE
    WHEN LOWER(TRIM(`RepeatUnit`)) = 'day' THEN 1
    WHEN LOWER(TRIM(`RepeatUnit`)) = 'week' THEN 2
    WHEN LOWER(TRIM(`RepeatUnit`)) = 'month' THEN 3
    ELSE NULL
END WHERE `RepeatUnit` IS NOT NULL;

UPDATE `standing_order` SET `RepeatUnit` = CASE
    WHEN LOWER(TRIM(`RepeatUnit`)) = 'day' THEN 1
    WHEN LOWER(TRIM(`RepeatUnit`)) = 'week' THEN 2
    WHEN LOWER(TRIM(`RepeatUnit`)) = 'month' THEN 3
    ELSE NULL
END WHERE `RepeatUnit` IS NOT NULL;

-- Change customer table RepeatUnit to INT
ALTER TABLE `customer` MODIFY COLUMN `RepeatUnit` INT(11) NULL;

-- Change standing_order table RepeatUnit to INT
ALTER TABLE `standing_order` MODIFY COLUMN `RepeatUnit` INT(11) NULL;

-- Add foreign key constraints (optional, but good for data integrity)
ALTER TABLE `customer` ADD CONSTRAINT `fk_customer_repeat_unit` FOREIGN KEY (`RepeatUnit`) REFERENCES `repeat_units` (`id`) ON DELETE SET NULL;
ALTER TABLE `standing_order` ADD CONSTRAINT `fk_standing_order_repeat_unit` FOREIGN KEY (`RepeatUnit`) REFERENCES `repeat_units` (`id`) ON DELETE SET NULL;