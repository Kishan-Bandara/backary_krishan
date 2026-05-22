-- Migration: add delivery_start_time and delivery_end_time to supplier_shipping_address
-- Date: 2026-02-19

ALTER TABLE `supplier_shipping_address`
  ADD COLUMN `delivery_start_time` VARCHAR(10) DEFAULT NULL,
  ADD COLUMN `delivery_end_time` VARCHAR(10) DEFAULT NULL;
