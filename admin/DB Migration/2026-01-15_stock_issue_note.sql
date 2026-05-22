-- Migration: Stock Issue Note
-- Date: 2026-01-15

START TRANSACTION;

CREATE TABLE IF NOT EXISTS `stock_issue_header` (
  `issue_id` int(10) NOT NULL,
  `issue_code` varchar(30) NOT NULL,
  `issue_date` date NOT NULL,
  `location_id` int(10) NOT NULL,
  `issued_to` varchar(100) DEFAULT NULL,
  `status` enum('ISSUED','CANCELLED') NOT NULL DEFAULT 'ISSUED',
  `remarks` text DEFAULT NULL,
  `created_by` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `stock_issue_items` (
  `issue_item_id` int(10) NOT NULL,
  `issue_id` int(10) NOT NULL,
  `product_id` int(10) NOT NULL,
  `qty` double(20,2) NOT NULL,
  `rate` double(20,2) NOT NULL DEFAULT 0.00,
  `total` double(20,2) NOT NULL DEFAULT 0.00
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

ALTER TABLE `stock_issue_header`
  ADD PRIMARY KEY (`issue_id`),
  ADD KEY `location_id` (`location_id`);

ALTER TABLE `stock_issue_items`
  ADD PRIMARY KEY (`issue_item_id`),
  ADD KEY `issue_id` (`issue_id`),
  ADD KEY `product_id` (`product_id`);

ALTER TABLE `stock_issue_header`
  MODIFY `issue_id` int(10) NOT NULL AUTO_INCREMENT;

ALTER TABLE `stock_issue_items`
  MODIFY `issue_item_id` int(10) NOT NULL AUTO_INCREMENT;

COMMIT;