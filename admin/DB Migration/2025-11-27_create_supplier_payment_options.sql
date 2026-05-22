-- Create supplier_payment_options table similar to customer_payment_options
-- Date: 2025-11-27

CREATE TABLE supplier_payment_options (
  id INT AUTO_INCREMENT PRIMARY KEY,
  supplier_id INT NOT NULL,
  payment_type ENUM('card', 'bank') NOT NULL,
  card_no VARCHAR(20) DEFAULT NULL,
  card_name VARCHAR(100) DEFAULT NULL,
  exp_month VARCHAR(2) DEFAULT NULL,
  exp_year VARCHAR(4) DEFAULT NULL,
  bank_name VARCHAR(100) DEFAULT NULL,
  branch VARCHAR(100) DEFAULT NULL,
  account_no VARCHAR(50) DEFAULT NULL,
  account_holder VARCHAR(100) DEFAULT NULL,
  FOREIGN KEY (supplier_id) REFERENCES supplier(supplier_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;