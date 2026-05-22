<?php
require __DIR__ . '/../include/database.php';
try {
    $db = new Database();
    echo "Customers:\n";
    $customers = $db->getRows('SELECT customer_id, customer_name FROM customer ORDER BY customer_id ASC LIMIT 20');
    foreach ($customers as $c) {
        echo $c['customer_id'] . ' - ' . $c['customer_name'] . PHP_EOL;
    }
    echo "\nItems:\n";
    $items = $db->getRows('SELECT item_id, item_name FROM item_master ORDER BY item_id ASC LIMIT 40');
    foreach ($items as $i) {
        echo $i['item_id'] . ' - ' . $i['item_name'] . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
