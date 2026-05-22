<?php
require_once __DIR__ . '/../include/database.php';
$db = new Database();

// purchase_return_header table
$tbl = $db->getRow("SELECT COUNT(*) as c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'purchase_return_header'");
if ((int)$tbl['c'] === 0) {
    echo "Creating purchase_return_header...\n";
    $db->insertRow("CREATE TABLE purchase_return_header (\n        pr_h_id INT(10) NOT NULL AUTO_INCREMENT,\n        pr_h_code VARCHAR(30) NOT NULL,\n        grn_h_id INT(10) NOT NULL,\n        supplier_id INT(10) NOT NULL,\n        location_id INT(10) NOT NULL,\n        pr_date DATETIME NOT NULL,\n        pr_net DOUBLE(20,2) NOT NULL DEFAULT 0.00,\n        pr_vat DOUBLE(20,2) NOT NULL DEFAULT 0.00,\n        pr_gross DOUBLE(20,2) NOT NULL DEFAULT 0.00,\n        created_by VARCHAR(20) NOT NULL,\n        remarks TEXT NULL,\n        PRIMARY KEY (pr_h_id),\n        KEY grn_h_id (grn_h_id),\n        KEY supplier_id (supplier_id),\n        KEY location_id (location_id)\n    ) ENGINE=MyISAM DEFAULT CHARSET=latin1");
} else {
    echo "purchase_return_header already exists.\n";
}

// purchase_return_details table
$tbl2 = $db->getRow("SELECT COUNT(*) as c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'purchase_return_details'");
if ((int)$tbl2['c'] === 0) {
    echo "Creating purchase_return_details...\n";
    $db->insertRow("CREATE TABLE purchase_return_details (\n        pr_d_id INT(10) NOT NULL AUTO_INCREMENT,\n        pr_h_id INT(10) NOT NULL,\n        grn_d_id INT(10) NOT NULL,\n        item_id INT(10) NOT NULL,\n        pr_d_qty DOUBLE(20,2) NOT NULL,\n        pr_d_rate DOUBLE(20,2) NOT NULL,\n        pr_d_vat_rate DOUBLE(20,2) NOT NULL,\n        pr_d_vat DOUBLE(20,2) NOT NULL DEFAULT 0.00,\n        pr_d_total DOUBLE(20,2) NOT NULL,\n        PRIMARY KEY (pr_d_id),\n        KEY pr_h_id (pr_h_id),\n        KEY grn_d_id (grn_d_id),\n        KEY item_id (item_id)\n    ) ENGINE=MyISAM DEFAULT CHARSET=latin1");
} else {
    echo "purchase_return_details already exists.\n";
}

echo "Migration complete.\n";
