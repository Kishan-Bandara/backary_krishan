-- Migration: create CRM activity master table
-- Date: 2026-04-07

CREATE TABLE IF NOT EXISTS crm_activity_master (
    activity_id INT NOT NULL AUTO_INCREMENT,
    activity_code VARCHAR(50) NOT NULL,
    description VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (activity_id),
    UNIQUE KEY uq_crm_activity_code (activity_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;