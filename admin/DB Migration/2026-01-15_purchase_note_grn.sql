-- Migration: Purchase Note + GRN linkage
-- Date: 2026-01-15

START TRANSACTION;

-- 1) Add linking columns to existing GRN tables
ALTER TABLE `grn_hedder`
  ADD COLUMN `purchase_note_id` int(10) DEFAULT NULL AFTER `grn_h_code`;

ALTER TABLE `grn_details`
  ADD COLUMN `purchase_note_item_id` int(10) DEFAULT NULL AFTER `grn_d_item_id`;

-- 2) Create Purchase Note tables
CREATE TABLE IF NOT EXISTS `purchase_note_header` (
  `purchase_note_id` int(10) NOT NULL,
  `purchase_note_code` varchar(30) NOT NULL,
  `purchase_date` date NOT NULL,
  `supplier_id` int(10) NOT NULL,
  `location_id` int(10) NOT NULL DEFAULT 1,
  `status` enum('OPEN','PARTIALLY_RECEIVED','COMPLETED') NOT NULL DEFAULT 'OPEN',
  `created_by` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `purchase_note_items` (
  `purchase_note_item_id` int(10) NOT NULL,
  `purchase_note_id` int(10) NOT NULL,
  `product_id` int(10) NOT NULL,
  `requested_qty` double(20,2) NOT NULL,
  `total_received_qty` double(20,2) NOT NULL DEFAULT 0.00,
  `balance_qty` double(20,2) NOT NULL DEFAULT 0.00
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 3) Indexes
ALTER TABLE `grn_hedder`
  ADD KEY `purchase_note_id` (`purchase_note_id`);

ALTER TABLE `grn_details`
  ADD KEY `purchase_note_item_id` (`purchase_note_item_id`);

ALTER TABLE `purchase_note_header`
  ADD PRIMARY KEY (`purchase_note_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `location_id` (`location_id`);

ALTER TABLE `purchase_note_items`
  ADD PRIMARY KEY (`purchase_note_item_id`),
  ADD KEY `purchase_note_id` (`purchase_note_id`),
  ADD KEY `product_id` (`product_id`);

-- 4) Auto increment
ALTER TABLE `purchase_note_header`
  MODIFY `purchase_note_id` int(10) NOT NULL AUTO_INCREMENT;

ALTER TABLE `purchase_note_items`
  MODIFY `purchase_note_item_id` int(10) NOT NULL AUTO_INCREMENT;

COMMIT;
