<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (session_status() === PHP_SESSION_NONE) session_start();
include('include/database.php');

$db = new Database();

echo "=== GROUPS ===" . PHP_EOL;
$groups = $db->getRows('SELECT group_id, group_name FROM gorup_master ORDER BY group_id LIMIT 10');
foreach ($groups as $g) echo $g['group_id'] . ' - ' . $g['group_name'] . PHP_EOL;

echo PHP_EOL . "=== ITEMS (first 15 active) ===" . PHP_EOL;
$items = $db->getRows("SELECT item_id, item_name, item_group, COALESCE(item_weight_g,0) as wt, item_active FROM item_master WHERE item_active='Y' ORDER BY item_id LIMIT 15");
foreach ($items as $i) echo $i['item_id'] . ' | grp=' . $i['item_group'] . ' | wt=' . $i['wt'] . 'g | ' . $i['item_name'] . PHP_EOL;

echo PHP_EOL . "=== CUSTOMERS (first 5 active) ===" . PHP_EOL;
$custs = $db->getRows("SELECT customer_id, customer_name FROM customer ORDER BY customer_id LIMIT 5");
foreach ($custs as $c) echo $c['customer_id'] . ' - ' . $c['customer_name'] . PHP_EOL;

echo PHP_EOL . "=== EXISTING STANDING ORDERS ===" . PHP_EOL;
$sos = $db->getRows('SELECT id, customer_id, active, date_from, date_to FROM standing_order ORDER BY id LIMIT 5');
foreach ($sos as $s) echo 'SO#' . $s['id'] . ' cust=' . $s['customer_id'] . ' active=' . $s['active'] . ' from=' . $s['date_from'] . ' to=' . $s['date_to'] . PHP_EOL;

echo PHP_EOL . "=== TODAY ===" . PHP_EOL;
echo date('Y-m-d l') . PHP_EOL;
echo 'Day column: ' . strtolower(date('D')) . '_qty' . PHP_EOL;

echo PHP_EOL . "=== SELLING PRODUCTS (group 1) ===" . PHP_EOL;
$selling = $db->getRows("SELECT item_id, item_name, COALESCE(item_weight_g,0) as wt FROM item_master WHERE item_active='Y' AND item_group=1 ORDER BY item_id LIMIT 20");
foreach ($selling as $i) echo $i['item_id'] . ' | wt=' . $i['wt'] . 'g | ' . $i['item_name'] . PHP_EOL;

echo PHP_EOL . "=== BREAD-LIKE ITEMS ===" . PHP_EOL;
$bread = $db->getRows("SELECT item_id, item_name, item_group, COALESCE(item_weight_g,0) as wt FROM item_master WHERE item_active='Y' AND (item_name LIKE '%bread%' OR item_name LIKE '%loaf%' OR item_name LIKE '%roll%' OR item_name LIKE '%bun%') ORDER BY item_id LIMIT 20");
foreach ($bread as $i) echo $i['item_id'] . ' | grp=' . $i['item_group'] . ' | wt=' . $i['wt'] . 'g | ' . $i['item_name'] . PHP_EOL;

echo PHP_EOL . "=== CUSTOMER SHIPPING ADDRESSES FOR CUST 1-5 ===" . PHP_EOL;
$addrs = $db->getRows("SELECT id, customer_id, is_default FROM customer_shipping_address WHERE customer_id IN (1,2,3,4,5) ORDER BY customer_id, id");
foreach ($addrs as $a) echo 'addr=' . $a['id'] . ' cust=' . $a['customer_id'] . ' default=' . $a['is_default'] . PHP_EOL;

echo PHP_EOL . "=== INVOICE HEDDER COLUMNS ===" . PHP_EOL;
$cols = $db->getRows("SHOW COLUMNS FROM invoice_hedder");
foreach ($cols as $c) echo $c['Field'] . ' (' . $c['Type'] . ')' . PHP_EOL;

