<?php
include('../include/database.php');
$db = new Database();
$rows = $db->getRows('SELECT id, customer_id, shipping_address_id FROM standing_order LIMIT 5');
print_r($rows);
?>



