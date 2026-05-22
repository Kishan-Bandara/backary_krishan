-- Migration: Add date_from and date_to columns to standing_order table
-- These allow specifying a date range for invoice generation instead of calculating from today

ALTER TABLE `standing_order`
ADD COLUMN `date_from` DATE NULL DEFAULT NULL AFTER `RepeatUnit`,
ADD COLUMN `date_to` DATE NULL DEFAULT NULL AFTER `date_from`;
