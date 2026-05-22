-- Create supplier_shipping_address table similar to customer_shipping_address
-- Date: 2025-11-27

CREATE TABLE supplier_shipping_address (
  id INT AUTO_INCREMENT PRIMARY KEY,
  supplier_id INT NOT NULL,
  address_label VARCHAR(100) DEFAULT NULL,
  address_line_1 VARCHAR(255) DEFAULT NULL,
  address_line_2 VARCHAR(255) DEFAULT NULL,
  city VARCHAR(100) DEFAULT NULL,
  postal_code VARCHAR(20) DEFAULT NULL,
  contact_no VARCHAR(20) DEFAULT NULL,
  attribute_1 VARCHAR(100) DEFAULT NULL,
  attribute_2 VARCHAR(100) DEFAULT NULL,
  attribute_3 VARCHAR(100) DEFAULT NULL,
  is_default TINYINT(1) DEFAULT 0,
  contact_person_name VARCHAR(100) DEFAULT NULL,
  contact_person_phone VARCHAR(20) DEFAULT NULL,
  contact_person_email VARCHAR(100) DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  note_to_deliver TEXT DEFAULT NULL,
  delivery_time_from TIME DEFAULT NULL,
  delivery_time_till TIME DEFAULT NULL,
  has_door_key TINYINT(1) DEFAULT 0,
  has_shop_alarm TINYINT(1) DEFAULT 0,
  delivery_route_id INT DEFAULT NULL,
  FOREIGN KEY (supplier_id) REFERENCES supplier(supplier_id) ON DELETE CASCADE,
  FOREIGN KEY (delivery_route_id) REFERENCES delivery_route_master(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;