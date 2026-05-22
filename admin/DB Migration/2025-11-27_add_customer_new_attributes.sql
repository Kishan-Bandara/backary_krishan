-- Add new columns to customer table for additional attributes
ALTER TABLE customer
ADD COLUMN min_order_amount DECIMAL(12,2) DEFAULT 0.00,
ADD COLUMN emergency_contact_name VARCHAR(100),
ADD COLUMN emergency_contact_email VARCHAR(100),
ADD COLUMN emergency_contact_telephone VARCHAR(50),
ADD COLUMN custom_url_link VARCHAR(255),
ADD COLUMN google_map_link VARCHAR(255),
ADD COLUMN contact_name VARCHAR(100),
ADD COLUMN contact_email VARCHAR(100),
ADD COLUMN contact_telephone VARCHAR(50);