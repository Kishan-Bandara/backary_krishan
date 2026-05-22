-- Migration: add supplier_mobile column to supplier table
-- Run on your database (e.g. using phpMyAdmin or mysql CLI)

ALTER TABLE `supplier`
  ADD COLUMN `supplier_mobile` VARCHAR(20) DEFAULT NULL AFTER `supplier_contact_no`;

-- Optional: if you want to populate supplier_mobile from contact_telephone where appropriate,
-- run an UPDATE after checking your data, for example:
-- UPDATE supplier SET supplier_mobile = contact_telephone WHERE supplier_mobile IS NULL AND contact_telephone IS NOT NULL;
