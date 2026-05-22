<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (session_status() === PHP_SESSION_NONE) session_start();
include('include/database.php');

$db = new Database();
$date = '2026-04-16';
$dayColumn = 'thu_qty';

echo "=== Standing order data for {$date} ===" . PHP_EOL;
$standing = $db->getRows(
    "SELECT im.item_id, im.item_name, gm.group_name, COALESCE(im.item_weight_g,0) AS wt,
            SUM(soi.{$dayColumn}) AS standing_qty
     FROM standing_order_item soi
     JOIN standing_order so ON so.id = soi.standing_order_id
     JOIN item_master im ON im.item_id = soi.item_id
     LEFT JOIN gorup_master gm ON gm.group_id = im.item_group
     WHERE so.active = 1
       AND (so.date_from IS NULL OR so.date_from <= ?)
       AND (so.date_to IS NULL OR so.date_to >= ?)
       AND soi.{$dayColumn} > 0
     GROUP BY im.item_id
     ORDER BY gm.group_name, im.item_name",
    [$date, $date]
);
foreach ($standing as $r) {
    echo "  {$r['group_name']} | {$r['item_name']} | standing={$r['standing_qty']} | wt={$r['wt']}g" . PHP_EOL;
}

echo PHP_EOL . "=== Cart order data for {$date} ===" . PHP_EOL;
$cart = $db->getRows(
    "SELECT id.invoice_d_item_id AS item_id, im.item_name, gm.group_name,
            SUM(id.invoice_d_qty) AS cart_qty
     FROM invoice_details id
     JOIN invoice_hedder ih ON ih.invoice_h_id = id.invoice_h_id
     JOIN item_master im ON im.item_id = id.invoice_d_item_id
     LEFT JOIN gorup_master gm ON gm.group_id = im.item_group
     WHERE ih.invoice_h_delivery_date = ?
       AND ih.invoice_h_status = 1
       AND ih.order_type = 'CART'
     GROUP BY id.invoice_d_item_id
     ORDER BY gm.group_name, im.item_name",
    [$date]
);
foreach ($cart as $r) {
    echo "  {$r['group_name']} | {$r['item_name']} | cart={$r['cart_qty']}" . PHP_EOL;
}

echo PHP_EOL . "Data looks correct if it matches seed expectations." . PHP_EOL;
