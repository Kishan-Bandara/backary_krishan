-- SMTP Email Settings table
-- Stores SMTP configuration for the email service
-- Run this migration on the bakery database

CREATE TABLE IF NOT EXISTS `smtp_settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `smtp_host` VARCHAR(255) NOT NULL DEFAULT '',
  `smtp_port` INT(11) NOT NULL DEFAULT 587,
  `smtp_username` VARCHAR(255) NOT NULL DEFAULT '',
  `smtp_password` VARCHAR(255) NOT NULL DEFAULT '',
  `smtp_encryption` ENUM('tls','ssl','none') NOT NULL DEFAULT 'tls',
  `smtp_from_email` VARCHAR(255) NOT NULL DEFAULT '',
  `smtp_from_name` VARCHAR(255) NOT NULL DEFAULT '',
  `smtp_reply_to_email` VARCHAR(255) NOT NULL DEFAULT '',
  `smtp_reply_to_name` VARCHAR(255) NOT NULL DEFAULT '',
  `smtp_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default row if not exists
INSERT INTO `smtp_settings` (`id`, `smtp_host`, `smtp_port`, `smtp_encryption`, `smtp_enabled`)
SELECT 1, '', 587, 'tls', 0
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `smtp_settings` WHERE `id` = 1);

-- Email log table for tracking sent emails
CREATE TABLE IF NOT EXISTS `email_log` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `to_email` VARCHAR(255) NOT NULL,
  `to_name` VARCHAR(255) DEFAULT '',
  `subject` VARCHAR(500) NOT NULL,
  `template_type` VARCHAR(50) NOT NULL COMMENT 'cart_order, standing_order, etc.',
  `reference_id` INT(11) DEFAULT NULL COMMENT 'invoice_h_id or standing_order id',
  `status` ENUM('sent','failed') NOT NULL DEFAULT 'sent',
  `error_message` TEXT DEFAULT NULL,
  `sent_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email_reference` (`template_type`, `reference_id`),
  KEY `idx_email_date` (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
