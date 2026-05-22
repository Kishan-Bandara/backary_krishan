-- Migration: create CRM activity line table
-- Date: 2026-04-07

CREATE TABLE IF NOT EXISTS crm_activity_line (
    activity_line_id INT NOT NULL AUTO_INCREMENT,
    activity_id INT NOT NULL,
    line_type VARCHAR(100) DEFAULT NULL,
    description TEXT NOT NULL,
    activity_percentage DECIMAL(5,2) NOT NULL DEFAULT 0,
    priority VARCHAR(20) NOT NULL DEFAULT 'Low',
    date_formula VARCHAR(30) DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (activity_line_id),
    KEY idx_crm_activity_line_activity (activity_id),
    CONSTRAINT fk_crm_activity_line_activity FOREIGN KEY (activity_id) REFERENCES crm_activity_master(activity_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;