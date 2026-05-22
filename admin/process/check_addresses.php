<?php
require_once '../include/database.php';
$db = new Database();
$addresses = $db->getRows('SELECT * FROM customer_shipping_address WHERE customer_id = 1');
echo 'Shipping addresses for customer 1: ' . count($addresses) . "\n";
foreach($addresses as $addr) {
    print_r($addr);
    echo "\n";
}
?>



