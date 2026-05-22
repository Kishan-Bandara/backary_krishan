-- Add note_to_deliver column to customer_shipping_address table
ALTER TABLE customer_shipping_address
ADD COLUMN note_to_deliver TEXT;