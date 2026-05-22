<?php
/**
 * AJAX endpoint: Check batch tracking status for multiple products
 * POST: product_ids[] = array of product IDs
 * Returns JSON object keyed by product_id with batch_tracking value
 */
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('../include/database.php');
include('../include/check_login.php');

header('Content-Type: application/json');

$productIds = $_POST['product_ids'] ?? [];
if (!is_array($productIds) || empty($productIds)) {
    echo json_encode([]);
    exit;
}

$db = new Database();

// Sanitize IDs
$cleanIds = array_map('intval', $productIds);
$cleanIds = array_filter($cleanIds, function ($id) { return $id > 0; });

if (empty($cleanIds)) {
    echo json_encode([]);
    exit;
}

$placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
$rows = $db->getRows(
    "SELECT item_id, batch_tracking FROM item_master WHERE item_id IN ($placeholders)",
    array_values($cleanIds)
);

$result = [];
foreach ($rows as $row) {
    $result[$row['item_id']] = $row['batch_tracking'] ?? 'NONE';
}

echo json_encode($result);
