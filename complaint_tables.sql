-- =============================================
-- Customer Complaint & Feedback System Tables
-- =============================================

-- Master: Product Complaint Issue Types (Quality, Taste, Price)
CREATE TABLE IF NOT EXISTS `complaint_product_issue_type` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `is_active` int(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `complaint_product_issue_type` (`name`, `is_active`) VALUES 
('Quality', 1),
('Taste', 1),
('Price', 1);

-- Master: Service Complaint Issue Types (Delivery, Person)
CREATE TABLE IF NOT EXISTS `complaint_service_issue_type` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `is_active` int(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `complaint_service_issue_type` (`name`, `is_active`) VALUES 
('Delivery', 1),
('Person', 1);

-- Master: Complaint Resolve Reasons
CREATE TABLE IF NOT EXISTS `complaint_resolve_reason` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `is_active` int(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `complaint_resolve_reason` (`name`, `is_active`) VALUES 
('Materials', 1),
('Not Proven', 1);

-- Default assignee per complaint type
CREATE TABLE IF NOT EXISTS `complaint_default_assignment` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `complaint_type` enum('Product','Service') NOT NULL,
  `user_id` int(10) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_complaint_type` (`complaint_type`),
  KEY `idx_default_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT IGNORE INTO `complaint_default_assignment` (`complaint_type`, `user_id`) VALUES
('Product', NULL),
('Service', NULL);

-- Main Complaints Table
CREATE TABLE IF NOT EXISTS `complaints` (
  `complaint_id` int(10) NOT NULL AUTO_INCREMENT,
  `complaint_code` varchar(20) NOT NULL,
  `customer_id` int(10) NOT NULL,
  `complaint_type` enum('Product','Service') NOT NULL,
  `product_id` int(10) DEFAULT NULL,
  `product_issue_type_id` int(10) DEFAULT NULL,
  `service_issue_type_id` int(10) DEFAULT NULL,
  `complaint_text` text NOT NULL,
  `date_of_purchase` date DEFAULT NULL,
  `invoice_no` varchar(50) DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `status` enum('Open','Assigned','In Progress','Closed') NOT NULL DEFAULT 'Open',
  `assigned_user_id` int(10) DEFAULT NULL,
  `resolve_reason_id` int(10) DEFAULT NULL,
  `resolve_material_id` int(10) DEFAULT NULL,
  `resolve_supplier_id` int(10) DEFAULT NULL,
  `customer_outcome_message` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `resolved_at` datetime DEFAULT NULL,
  PRIMARY KEY (`complaint_id`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_status` (`status`),
  KEY `idx_assigned_user` (`assigned_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
