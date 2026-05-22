-- Migration: Add Location Code to location_master table
-- Date: 2026-01-15

START TRANSACTION;

-- Add location_code column to location_master table
ALTER TABLE `location_master`
ADD COLUMN `location_code` varchar(20) NOT NULL DEFAULT '' AFTER `id`;

-- Add unique index on location_code
ALTER TABLE `location_master`
ADD UNIQUE KEY `location_code` (`location_code`);

COMMIT;