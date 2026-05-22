<?php
ob_start();
session_start();
include('../include/database.php');
include('../include/check_login.php');
include('../include/price_helpers.php');

header('Content-Type: application/json');
$db = new Database();
$productId = (int) ($_POST['product_id'] ?? 0);
$customerId = isset($_POST['customer_id']) ? (int) $_POST['customer_id'] : null;
$locationId = (int) ($_SESSION['location'] ?? 0);

if ($productId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product id']);
    exit;
}

// Determine price type from customer if provided
$price = null;
$vat = 0.00;
$currency = '';

if ($customerId) {
    $cust = $db->getRow('SELECT customer_price_type_id FROM customer WHERE customer_id = ? LIMIT 1', [$customerId]);
    $ptype = $cust['customer_price_type_id'] ?? null;
    if ($ptype) {
        $mapped = getProductPriceMapping($productId, (int)$ptype, $locationId, $db);
        if ($mapped !== null) {
            $price = (float)$mapped;
        }
    }
}

// If still not set, try default mapping for a default price type (e.g., price_type_id = 1) using location
if ($price === null) {
    // try to find any mapping for product at location
    $row = $db->getRow('SELECT price FROM product_price_mapping WHERE product_id = ? AND location_id = ? LIMIT 1', [$productId, $locationId]);
    if ($row && isset($row['price'])) {
        $price = (float)$row['price'];
    }
}

// fallback to global mapping
if ($price === null) {
    $row = $db->getRow('SELECT price FROM product_price_mapping WHERE product_id = ? AND location_id IS NULL LIMIT 1', [$productId]);
    if ($row && isset($row['price'])) {
        $price = (float)$row['price'];
    }
}

// last fallback to item master normal selling price
if ($price === null) {
    $p = $db->getRow('SELECT item_normal_selling_price, item_vat FROM item_master WHERE item_id = ? LIMIT 1', [$productId]);
    $price = (float) ($p['item_normal_selling_price'] ?? 0);
    $vat = (float) ($p['item_vat'] ?? 0);
} else {
    // fetch VAT from item master
    $p = $db->getRow('SELECT item_vat FROM item_master WHERE item_id = ? LIMIT 1', [$productId]);
    $vat = (float) ($p['item_vat'] ?? 0);
}

// currency
$cur = $db->getRow('SELECT currency FROM currency WHERE activated = ? LIMIT 1', ['Y']);
$currency = $cur['currency'] ?? '';

echo json_encode(['success' => true, 'price' => number_format($price, 2, '.', ''), 'vat' => $vat, 'currency' => $currency]);

