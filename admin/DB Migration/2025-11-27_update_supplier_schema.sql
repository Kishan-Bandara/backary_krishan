-- Migration to update supplier table with additional columns to match customer structure
-- Date: 2025-11-27

ALTER TABLE supplier MODIFY supplier_id INT(10) NOT NULL AUTO_INCREMENT;

ALTER TABLE supplier
ADD COLUMN legal_name VARCHAR(100) DEFAULT NULL AFTER supplier_name,
ADD COLUMN trading_name VARCHAR(100) DEFAULT NULL AFTER legal_name,
ADD COLUMN address_line_1 VARCHAR(255) DEFAULT NULL AFTER supplier_address,
ADD COLUMN address_line_2 VARCHAR(255) DEFAULT NULL AFTER address_line_1,
ADD COLUMN city VARCHAR(100) DEFAULT NULL AFTER address_line_2,
ADD COLUMN postal_code VARCHAR(20) DEFAULT NULL AFTER city,
ADD COLUMN abn_no VARCHAR(20) DEFAULT NULL AFTER supplier_cradit_limite,
ADD COLUMN acn_no VARCHAR(20) DEFAULT NULL AFTER abn_no,
ADD COLUMN vat_registered TINYINT(1) DEFAULT 0 AFTER acn_no,
ADD COLUMN gst_no VARCHAR(20) DEFAULT NULL AFTER vat_registered,
ADD COLUMN payment_terms_id INT DEFAULT NULL AFTER gst_no,
ADD COLUMN supplier_price_type_id INT DEFAULT NULL AFTER payment_terms_id,
ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER supplier_price_type_id,
ADD COLUMN locked TINYINT(1) DEFAULT 0 AFTER is_active,
ADD COLUMN RepeatInterval INT DEFAULT NULL AFTER locked,
ADD COLUMN RepeatUnit INT DEFAULT NULL AFTER RepeatInterval,
ADD COLUMN min_order_amount DECIMAL(10,2) DEFAULT NULL AFTER RepeatUnit,
ADD COLUMN emergency_contact_name VARCHAR(100) DEFAULT NULL AFTER min_order_amount,
ADD COLUMN emergency_contact_email VARCHAR(100) DEFAULT NULL AFTER emergency_contact_name,
ADD COLUMN emergency_contact_telephone VARCHAR(20) DEFAULT NULL AFTER emergency_contact_email,
ADD COLUMN custom_url_link VARCHAR(255) DEFAULT NULL AFTER emergency_contact_telephone,
ADD COLUMN google_map_link VARCHAR(255) DEFAULT NULL AFTER custom_url_link,
ADD COLUMN contact_name VARCHAR(100) DEFAULT NULL AFTER google_map_link,
ADD COLUMN contact_email VARCHAR(100) DEFAULT NULL AFTER contact_name,
ADD COLUMN contact_telephone VARCHAR(20) DEFAULT NULL AFTER contact_email,
ADD COLUMN supplier_remarks TEXT DEFAULT NULL AFTER supplier_note;

-- Rename outstanding balance column to match customer
ALTER TABLE supplier CHANGE supplier_outstanding_blance supplier_outstanding_balance DOUBLE(20,2) DEFAULT 0.00;

-- Rename credit limit column
ALTER TABLE supplier CHANGE supplier_cradit_limite credit_limit DOUBLE(20,2) DEFAULT 0.00;