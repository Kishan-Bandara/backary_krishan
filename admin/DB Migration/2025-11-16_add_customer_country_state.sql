-- Migration: Add country and state fields to customer and customer_shipping_address tables

ALTER TABLE customer
  ADD COLUMN country VARCHAR(64) DEFAULT NULL,
  ADD COLUMN state VARCHAR(64) DEFAULT NULL;

ALTER TABLE customer_shipping_address
  ADD COLUMN country VARCHAR(64) DEFAULT NULL,
  ADD COLUMN state VARCHAR(64) DEFAULT NULL;

-- If you want to backfill existing data, add UPDATE statements here.
-- Example:
-- UPDATE customer SET country = 'Sri Lanka' WHERE country IS NULL;
-- UPDATE customer_shipping_address SET country = 'Sri Lanka' WHERE country IS NULL;
