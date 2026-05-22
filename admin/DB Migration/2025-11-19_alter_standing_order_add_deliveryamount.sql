-- Adds DeliveryAmount to existing standing_order table (idempotent-style)
ALTER TABLE `standing_order`
  ADD COLUMN `DeliveryAmount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `active`;