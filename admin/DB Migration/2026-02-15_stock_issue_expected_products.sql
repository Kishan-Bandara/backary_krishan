-- ============================================================
-- Migration: Stock Issue Note → Expected Finished Products
-- Date: 2026-02-15
-- Purpose: 
--   When raw materials are issued to the kitchen via a Stock Issue Note,
--   we also track what finished products are expected back, with quantities
--   and the destination location.
--   On production completion, a "Production Receive Confirmation" creates
--   a GRN automatically to add finished products into stock.
-- ============================================================

-- 1. Add production-related columns to stock_issue_header
ALTER TABLE `stock_issue_header`
    ADD COLUMN `to_location_id` INT(10) DEFAULT NULL COMMENT 'Destination location for finished products' AFTER `location_id`,
    ADD COLUMN `production_status` ENUM('PENDING','PARTIALLY_RECEIVED','COMPLETED') DEFAULT NULL COMMENT 'NULL = no expected products, PENDING = awaiting finished goods' AFTER `status`;

-- 2. New table: expected finished products from a stock issue
CREATE TABLE IF NOT EXISTS `stock_issue_expected_products` (
    `id`            INT(10) NOT NULL AUTO_INCREMENT,
    `issue_id`      INT(10) NOT NULL COMMENT 'FK → stock_issue_header.issue_id',
    `product_id`    INT(10) NOT NULL COMMENT 'FK → item_master.item_id (finished product)',
    `expected_qty`  DOUBLE(20,2) NOT NULL DEFAULT 0.00,
    `received_qty`  DOUBLE(20,2) NOT NULL DEFAULT 0.00,
    `status`        ENUM('PENDING','PARTIALLY_RECEIVED','COMPLETED') NOT NULL DEFAULT 'PENDING',
    PRIMARY KEY (`id`),
    KEY `idx_issue_id` (`issue_id`),
    KEY `idx_product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
