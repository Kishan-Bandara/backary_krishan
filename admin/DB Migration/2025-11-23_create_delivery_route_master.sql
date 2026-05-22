-- Migration: Create delivery route master table and link to shipping addresses
-- Date: 2025-11-23

-- Create delivery_route_master table
CREATE TABLE IF NOT EXISTS delivery_route_master (
    id INT AUTO_INCREMENT PRIMARY KEY,
    route_name VARCHAR(100) NOT NULL,
    route_description TEXT,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_route_name (route_name)
);

-- Add delivery_route_id to customer_shipping_address table
ALTER TABLE customer_shipping_address
ADD COLUMN delivery_route_id INT DEFAULT NULL AFTER has_shop_alarm,
ADD CONSTRAINT fk_delivery_route FOREIGN KEY (delivery_route_id) REFERENCES delivery_route_master(id) ON DELETE SET NULL;

-- Insert some default delivery routes
INSERT INTO delivery_route_master (route_name, route_description, is_active) VALUES
('Route A - North', 'Northern delivery route covering suburbs north of the city', 1),
('Route B - South', 'Southern delivery route covering suburbs south of the city', 1),
('Route C - East', 'Eastern delivery route covering suburbs east of the city', 1),
('Route D - West', 'Western delivery route covering suburbs west of the city', 1),
('Route E - Central', 'Central delivery route for city center deliveries', 1),
('Route F - Rural', 'Rural delivery route for outlying areas', 1);