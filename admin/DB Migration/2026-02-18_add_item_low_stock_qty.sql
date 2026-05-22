-- Migration: add low_stock_qty to item_master
-- Run this on your MySQL server (phpMyAdmin or CLI)

ALTER TABLE `item_master`
  ADD COLUMN `low_stock_qty` INT NOT NULL DEFAULT 5 AFTER `item_weight`;

-- Safety: ensure existing rows have a sensible value
UPDATE `item_master` SET `low_stock_qty` = 5 WHERE `low_stock_qty` IS NULL;

-- End of migration
