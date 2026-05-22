<?php
/**
 * AJAX endpoint: Get available batches for a product at a location
 * Returns JSON array of batches with available qty from FIFO
 */
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('../include/database.php');
include('../include/check_login.php');

header('Content-Type: application/json');

$productId = (int) ($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
$locationId = (int) ($_POST['location_id'] ?? $_GET['location_id'] ?? 0);

if ($productId <= 0 || $locationId <= 0) {
    echo json_encode(['batches' => []]);
    exit;
}

$db = new Database();

// Check if product has batch tracking enabled
$product = $db->getRow('SELECT batch_tracking FROM item_master WHERE item_id = ?', [$productId]);
if (!$product || ($product['batch_tracking'] ?? 'NONE') === 'NONE') {
    echo json_encode(['batches' => [], 'tracking' => 'NONE']);
    exit;
}

// Get available batches with stock from FIFO
$rows = $db->getRows(
    'SELECT bm.batch_id, bm.batch_no, bm.expiry_date, SUM(f.ft_blanace) AS available_qty
     FROM fifo f
     INNER JOIN batch_master bm ON bm.batch_id = f.batch_id
     WHERE f.ft_item = ? AND f.ft_location = ? AND f.ft_type = 1 AND f.ft_blanace > 0
     GROUP BY bm.batch_id, bm.batch_no, bm.expiry_date
     HAVING available_qty > 0
     ORDER BY bm.expiry_date ASC, bm.batch_no ASC',
    [$productId, $locationId]
);

$batches = [];
foreach ($rows as $row) {
    $batches[] = [
        'batch_id' => (int) $row['batch_id'],
        'batch_no' => $row['batch_no'],
        'expiry_date' => $row['expiry_date'],
        'available_qty' => (float) $row['available_qty']
    ];
}

echo json_encode([
    'batches' => $batches,
    'tracking' => $product['batch_tracking']
]);
