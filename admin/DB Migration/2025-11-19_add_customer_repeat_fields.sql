-- Migration: Add repeat interval fields to customer table
-- Date: 2025-11-19

ALTER TABLE customer
  ADD COLUMN RepeatInterval INT NULL COMMENT 'e.g., 7',
  ADD COLUMN RepeatUnit VARCHAR(20) NULL COMMENT 'Days, Weeks, Months';