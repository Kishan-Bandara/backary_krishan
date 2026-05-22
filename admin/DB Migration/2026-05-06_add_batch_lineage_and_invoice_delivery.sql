-- Migration: Batch lineage + Invoice delivery tracking
-- Date: 2026-05-06
--
-- 1. New table: batch_lineage  (parent raw batches -> finished product batch)
-- 2. invoice_details.batch_id      INT(11)      NULL  (which batch was delivered)
-- 3. invoice_hedder.delivery_status VARCHAR(20) DEFAULT 'PENDING'
-- 4. invoice_hedder.delivered_at    DATETIME     NULL
-- 5. invoice_hedder.delivered_by    VARCHAR(100) NULL

CREATE TABLE IF NOT EXISTS `batch_lineage` (
    `lineage_id`         INT(11)        NOT NULL AUTO_INCREMENT,
    `finished_batch_id`  INT(11)        NOT NULL,
    `finished_item_id`   INT(11)        NOT NULL,
    `raw_batch_id`       INT(11)            NULL,
    `raw_item_id`        INT(11)        NOT NULL,
    `raw_qty_used`       DECIMAL(18, 4) NOT NULL DEFAULT 0,
    `issue_id`           INT(11)            NULL,
    `created_at`         DATETIME       NOT NULL,
    `created_by`         VARCHAR(100)   NOT NULL DEFAULT '',
    PRIMARY KEY (`lineage_id`),
    KEY `idx_finished_batch` (`finished_batch_id`),
    KEY `idx_raw_batch`      (`raw_batch_id`),
    KEY `idx_issue`          (`issue_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Lineage map: which raw-material batches went into each finished-product batch';

ALTER TABLE `invoice_details`
    ADD COLUMN `batch_id` INT(11) DEFAULT NULL AFTER `is_cart_item`;

ALTER TABLE `invoice_hedder`
    ADD COLUMN `delivery_status` VARCHAR(20) NOT NULL DEFAULT 'PENDING' AFTER `invoice_h_status`,
    ADD COLUMN `delivered_at`    DATETIME    DEFAULT NULL              AFTER `delivery_status`,
    ADD COLUMN `delivered_by`    VARCHAR(100) DEFAULT NULL              AFTER `delivered_at`;
