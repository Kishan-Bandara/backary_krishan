-- Update existing location record with a default location code
-- Run this after applying the migration

UPDATE `location_master` SET `location_code` = 'MAIN' WHERE `id` = 1;