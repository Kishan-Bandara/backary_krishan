-- Create customer_payment_options table
CREATE TABLE customer_payment_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    payment_type ENUM('card', 'bank') NOT NULL,
    card_no VARCHAR(20),
    card_name VARCHAR(100),
    exp_month INT,
    exp_year INT,
    bank_name VARCHAR(100),
    branch VARCHAR(100),
    account_no VARCHAR(50),
    account_holder VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);