-- Migration: create business_unit_master and map item_master to business unit

CREATE TABLE `business_unit_master` (
  `business_unit_id` int(10) NOT NULL AUTO_INCREMENT,
  `business_unit_name` varchar(100) NOT NULL,
  `business_unit_description` text DEFAULT NULL,
  PRIMARY KEY (`business_unit_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

ALTER TABLE `item_master`
  ADD COLUMN `item_business_unit` int(10) DEFAULT NULL AFTER `item_category`;

ALTER TABLE `item_master`
  ADD KEY `item_business_unit` (`item_business_unit`);


INSERT INTO `business_unit_master` (`business_unit_id`, `business_unit_name`, `business_unit_description`) VALUES
(1, 'GF', ''),
(2, 'STRADA', '');