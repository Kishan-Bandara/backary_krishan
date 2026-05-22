CREATE TABLE shipping_address_availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shipping_address_id INT NOT NULL,
    mon TINYINT(1) DEFAULT 1,
    tue TINYINT(1) DEFAULT 1,
    wed TINYINT(1) DEFAULT 1,
    thu TINYINT(1) DEFAULT 1,
    fri TINYINT(1) DEFAULT 1,
    sat TINYINT(1) DEFAULT 1,
    sun TINYINT(1) DEFAULT 1
) ENGINE=MyISAM DEFAULT CHARSET=latin1;