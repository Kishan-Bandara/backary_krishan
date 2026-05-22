-- Migration: add account_hold column to supplier table
-- Date: 2026-02-19

ALTER TABLE `supplier`
  ADD COLUMN `account_hold` TINYINT(1) DEFAULT 0;
