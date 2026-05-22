-- Migration: Stock Transfer
-- Date: 2026-01-15

START TRANSACTION;

CREATE TABLE IF NOT EXISTS `stock_transfer_header` (
  `transfer_id` int(10) NOT NULL,
  `transfer_code` varchar(30) NOT NULL,
  `transfer_date` date NOT NULL,
  `from_location_id` int(10) NOT NULL,
  `to_location_id` int(10) NOT NULL,
  `status` enum('PENDING','COMPLETED','CANCELLED') NOT NULL DEFAULT 'COMPLETED',
  `remarks` text DEFAULT NULL,
  `created_by` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `stock_transfer_items` (
  `transfer_item_id` int(10) NOT NULL,
  `transfer_id` int(10) NOT NULL,
  `product_id` int(10) NOT NULL,
  `qty` double(20,2) NOT NULL,
  `rate` double(20,2) NOT NULL DEFAULT 0.00,
  `total` double(20,2) NOT NULL DEFAULT 0.00
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

ALTER TABLE `stock_transfer_header`
  ADD PRIMARY KEY (`transfer_id`),
  ADD KEY `from_location_id` (`from_location_id`),
  ADD KEY `to_location_id` (`to_location_id`);

ALTER TABLE `stock_transfer_items`
  ADD PRIMARY KEY (`transfer_item_id`),
  ADD KEY `transfer_id` (`transfer_id`),
  ADD KEY `product_id` (`product_id`);

ALTER TABLE `stock_transfer_header`
  MODIFY `transfer_id` int(10) NOT NULL AUTO_INCREMENT;

ALTER TABLE `stock_transfer_items`
  MODIFY `transfer_item_id` int(10) NOT NULL AUTO_INCREMENT;

COMMIT;
