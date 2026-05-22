<?php
require_once '../include/database.php';
$db = new Database();
$customers = $db->getRows('SELECT customer_id, customer_name FROM customer LIMIT 5');
echo 'First 5 customers:\n';
foreach($customers as $cust) {
    echo "ID: {$cust['customer_id']}, Name: {$cust['customer_name']}\n";
}
?>



