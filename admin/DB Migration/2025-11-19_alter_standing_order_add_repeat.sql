-- Migration: Add repeat interval fields to standing_order table
-- Date: 2025-11-19

ALTER TABLE standing_order
  ADD COLUMN RepeatInterval INT NULL COMMENT 'e.g., 7',
  ADD COLUMN RepeatUnit VARCHAR(20) NULL COMMENT 'Days, Weeks, Months';