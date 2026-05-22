-- Migration: Add amount field to delivery_route_master table
-- Date: 2026-02-25

ALTER TABLE delivery_route_master
ADD COLUMN amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER route_description;