-- SQL to add is_raw_material column to item_master table if it doesn't exist
-- Run this query in phpMyAdmin or MySQL console

-- Add is_raw_material column
ALTER TABLE `item_master` 
ADD COLUMN IF NOT EXISTS `is_raw_material` TINYINT(1) NOT NULL DEFAULT 0 
COMMENT 'Mark product as raw material for Purchase Order items';

-- Note: If your MySQL version doesn't support "IF NOT EXISTS" for columns, 
-- use this query instead (will error if column exists, which is safe):
-- ALTER TABLE `item_master` ADD COLUMN `is_raw_material` TINYINT(1) NOT NULL DEFAULT 0;
