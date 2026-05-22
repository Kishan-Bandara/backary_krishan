-- Migration: Add is_gift_item flag to invoice_details
-- Date: 2026-05-04

ALTER TABLE `invoice_details`
  ADD COLUMN `is_gift_item` TINYINT(1) NOT NULL DEFAULT 0
    COMMENT '1 = Gift item with 100 percent discount, 0 = regular item' AFTER `is_cart_item`;