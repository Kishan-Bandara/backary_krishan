-- Migration: Create repeat_units master table
-- Date: 2025-11-22

CREATE TABLE IF NOT EXISTS `repeat_units` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  `display_name` VARCHAR(50) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert standard repeat units
INSERT INTO `repeat_units` (`name`, `display_name`) VALUES
('day', 'Day'),
('week', 'Week'),
('month', 'Month');