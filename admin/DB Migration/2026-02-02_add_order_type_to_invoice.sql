-- Add order_type column to invoice_hedder table
-- This column identifies the source of the order:
-- 'POS' = POS Sales
-- 'CART' = Cart Order (one-time specific date)
-- 'STANDING' = Standing Order (recurring)
-- 'ONLINE' = Online Order
-- NULL or empty = Legacy/Unknown

ALTER TABLE `invoice_hedder` 
ADD COLUMN `order_type` ENUM('POS','CART','STANDING','ONLINE') NULL DEFAULT NULL 
AFTER `invoice_h_status`;

-- Add shipping_address_id to link orders to specific shipping address
ALTER TABLE `invoice_hedder` 
ADD COLUMN `shipping_address_id` INT(11) NULL DEFAULT NULL 
AFTER `order_type`;

-- Add index for faster queries
ALTER TABLE `invoice_hedder` ADD INDEX `idx_order_type` (`order_type`);
ALTER TABLE `invoice_hedder` ADD INDEX `idx_shipping_address` (`shipping_address_id`);
