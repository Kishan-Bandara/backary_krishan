<?php
require_once '../include/database.php';
$db = new Database();

// Create a shipping address for customer 1
$db->insertRow('INSERT INTO customer_shipping_address (customer_id, address_label, address_line_1, address_line_2, city, postal_code, contact_no, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', [
    1, // customer_id
    'Default Address', // address_label
    '123 Test Street', // address_line_1
    'Apt 1', // address_line_2
    'Test City', // city
    '12345', // postal_code
    '123-456-7890', // contact_no
    1 // is_default
]);

echo "Shipping address created for customer 1\n";
$address = $db->getRow('SELECT * FROM customer_shipping_address WHERE customer_id = 1 LIMIT 1');
echo "Address ID: " . $address['id'] . "\n";
?>



