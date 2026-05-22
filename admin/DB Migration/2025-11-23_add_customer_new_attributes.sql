-- Migration: Add new customer attributes and shipping address fields
-- Date: 2025-11-23

-- Add new fields to customer table
ALTER TABLE customer
  ADD COLUMN legal_name VARCHAR(150) DEFAULT NULL AFTER customer_name,
  ADD COLUMN trading_name VARCHAR(150) DEFAULT NULL AFTER legal_name,
  ADD COLUMN customer_remarks TEXT DEFAULT NULL AFTER customer_note;

-- Add new fields to customer_shipping_address table
ALTER TABLE customer_shipping_address
  ADD COLUMN contact_person_name VARCHAR(100) DEFAULT NULL AFTER contact_no,
  ADD COLUMN contact_person_email VARCHAR(100) DEFAULT NULL AFTER contact_person_name,
  ADD COLUMN contact_person_phone VARCHAR(50) DEFAULT NULL AFTER contact_person_email,
  ADD COLUMN remarks TEXT DEFAULT NULL AFTER contact_person_phone,
  ADD COLUMN delivery_time_from TIME DEFAULT NULL AFTER remarks,
  ADD COLUMN delivery_time_till TIME DEFAULT NULL AFTER delivery_time_from,
  ADD COLUMN has_door_key TINYINT(1) NOT NULL DEFAULT 0 AFTER delivery_time_till,
  ADD COLUMN has_shop_alarm TINYINT(1) NOT NULL DEFAULT 0 AFTER has_door_key;