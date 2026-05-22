-- Migration: create complaint tables and lookup masters
-- Date: 2026-04-10

CREATE TABLE IF NOT EXISTS `complaint_product_issue_type` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `complaint_service_issue_type` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `complaint_resolve_reason` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `complaint_default_assignment` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `complaint_type` VARCHAR(20) NOT NULL,
  `user_id` INT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_complaint_default_assignment_type` (`complaint_type`),
  KEY `idx_complaint_default_assignment_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `complaints` (
  `complaint_id` INT NOT NULL AUTO_INCREMENT,
  `complaint_code` VARCHAR(50) NOT NULL,
  `customer_id` INT NOT NULL,
  `complaint_type` VARCHAR(20) NOT NULL,
  `product_id` INT DEFAULT NULL,
  `product_issue_type_id` INT DEFAULT NULL,
  `service_issue_type_id` INT DEFAULT NULL,
  `complaint_text` TEXT NOT NULL,
  `date_of_purchase` DATE DEFAULT NULL,
  `invoice_no` VARCHAR(100) DEFAULT NULL,
  `attachment` VARCHAR(255) DEFAULT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'Open',
  `assigned_user_id` INT DEFAULT NULL,
  `resolve_reason_id` INT DEFAULT NULL,
  `resolve_material_id` INT DEFAULT NULL,
  `resolve_supplier_id` INT DEFAULT NULL,
  `customer_outcome_message` TEXT DEFAULT NULL,
  `resolved_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`complaint_id`),
  UNIQUE KEY `uq_complaints_code` (`complaint_code`),
  KEY `idx_complaints_customer` (`customer_id`),
  KEY `idx_complaints_product` (`product_id`),
  KEY `idx_complaints_product_issue_type` (`product_issue_type_id`),
  KEY `idx_complaints_service_issue_type` (`service_issue_type_id`),
  KEY `idx_complaints_assigned_user` (`assigned_user_id`),
  KEY `idx_complaints_resolve_reason` (`resolve_reason_id`),
  KEY `idx_complaints_resolve_material` (`resolve_material_id`),
  KEY `idx_complaints_resolve_supplier` (`resolve_supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
