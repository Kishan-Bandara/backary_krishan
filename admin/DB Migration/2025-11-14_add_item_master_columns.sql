-- Migration: Add missing inventory fields to item_master
-- Date: 2025-11-14

ALTER TABLE `item_master`
  ADD COLUMN `order_qty_min` decimal(10,2) DEFAULT NULL AFTER `item_uom`,
  ADD COLUMN `order_qty_max` decimal(10,2) DEFAULT NULL AFTER `order_qty_min`;

ALTER TABLE `item_master`
  ADD COLUMN `pack_size` varchar(50) DEFAULT NULL AFTER `item_weight`,
  ADD COLUMN `acc_posting_grp_code` varchar(50) DEFAULT NULL AFTER `pack_size`,
  ADD COLUMN `gst_vat_code` varchar(50) DEFAULT NULL AFTER `acc_posting_grp_code`;
