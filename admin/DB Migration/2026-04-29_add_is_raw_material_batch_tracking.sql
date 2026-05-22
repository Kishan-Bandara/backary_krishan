-- Migration: Add is_raw_material and batch_tracking columns to item_master
-- Date: 2026-04-29

ALTER TABLE `item_master`
  ADD COLUMN IF NOT EXISTS `is_raw_material` TINYINT(1) NOT NULL DEFAULT 0
    COMMENT 'Flag to identify raw materials for purchase orders' AFTER `immediate_pickups`,
  ADD COLUMN IF NOT EXISTS `batch_tracking` ENUM('NONE','BATCH','SERIAL') NOT NULL DEFAULT 'NONE'
    COMMENT 'Batch or serial number tracking mode' AFTER `is_raw_material`;
