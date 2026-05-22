-- Migration: Add allow_in_sales and allow_in_grn columns to item_master
-- Date: 2026-05-04

ALTER TABLE `item_master`
  ADD COLUMN `allow_in_sales` TINYINT(1) NOT NULL DEFAULT 1
    COMMENT 'Whether the product can be used in sales and cart orders' AFTER `is_raw_material`;

ALTER TABLE `item_master`
  ADD COLUMN `allow_in_grn` TINYINT(1) NOT NULL DEFAULT 1
    COMMENT 'Whether the product can be used in purchase and GRN flows' AFTER `allow_in_sales`;