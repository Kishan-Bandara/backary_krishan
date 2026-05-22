-- Add is_cart_item column to invoice_details table
-- This column flags items that were added via cart order (not from standing order)
-- Run this migration on your database

ALTER TABLE `invoice_details` 
ADD COLUMN `is_cart_item` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = Added via cart, 0 = Standing order item'
AFTER `order_note`;

-- Add updated_at column to invoice_hedder if not exists (for tracking order updates)
ALTER TABLE `invoice_hedder` 
ADD COLUMN IF NOT EXISTS `updated_at` DATETIME NULL DEFAULT NULL COMMENT 'Last update timestamp'
AFTER `add_by`;
