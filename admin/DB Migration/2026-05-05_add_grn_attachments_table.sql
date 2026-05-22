-- Migration: Create grn_attachments table
-- Date: 2026-05-05

CREATE TABLE IF NOT EXISTS `grn_attachments` (
    `attachment_id` INT(11)      NOT NULL AUTO_INCREMENT,
    `grn_h_id`      INT(11)      NOT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `stored_name`   VARCHAR(255) NOT NULL,
    `file_path`     VARCHAR(500) NOT NULL,
    `file_size`     INT(11)      NOT NULL DEFAULT 0,
    `uploaded_by`   VARCHAR(100) NOT NULL DEFAULT '',
    `created_at`    DATETIME     NOT NULL,
    PRIMARY KEY (`attachment_id`),
    KEY `idx_grn_h_id` (`grn_h_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='File attachments uploaded during GRN creation';
