<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('../include/database.php');
include('../include/check_login.php');
include('../get_url.php');
require_once(__DIR__ . '/../include/uom_helper.php');

date_default_timezone_set("Asia/Colombo");

function redirectWithMessage($message, $type = 'error')
{
    redirect('stock-issue-create.php?message=' . urlencode($message) . '&type=' . urlencode($type));
}

$issueCode = trim($_POST['issue_code'] ?? '');
$issueDate = $_POST['issue_date'] ?? date('Y-m-d');
$locationId = (int) ($_POST['location_id'] ?? 0);
$issuedTo = trim($_POST['issued_to'] ?? '');
$remarks = $_POST['remarks'] ?? '';
$productIds = $_POST['product_id'] ?? [];
$issueQtys = $_POST['issue_qty'] ?? [];
$batchIds = $_POST['batch_id'] ?? [];
$lineUomIds = $_POST['line_uom_id'] ?? [];
$lineQtyPerUom = $_POST['line_qty_per_uom'] ?? [];
$lineBaseQty = $_POST['line_base_qty'] ?? [];
$toLocationId = !empty($_POST['to_location_id']) ? (int) $_POST['to_location_id'] : null;
$expectedProductIds = $_POST['expected_product_id'] ?? [];
$expectedQtys = $_POST['expected_qty'] ?? [];

if (!isSuperAdmin()) {
    $locationId = (int) ($_SESSION['location'] ?? 0);
}

if ($locationId <= 0) {
    redirectWithMessage('Please select a location.');
}

if (empty($productIds) || empty($issueQtys)) {
    redirectWithMessage('Please add at least one item to issue.');
}

$db = new Database();
ensureItemUomSchema($db);

$validItems = [];
for ($i = 0; $i < count($productIds); $i++) {
    $productId = (int) ($productIds[$i] ?? 0);
    $qty = (float) ($issueQtys[$i] ?? 0);
    $batchId = !empty($batchIds[$i]) ? (int) $batchIds[$i] : null;
    $uomId = !empty($lineUomIds[$i]) ? (int) $lineUomIds[$i] : null;
    $qtyPerUom = (float) ($lineQtyPerUom[$i] ?? 0);
    if ($qtyPerUom <= 0) { $qtyPerUom = 1.0; }
    $baseQty = (float) ($lineBaseQty[$i] ?? 0);
    if ($baseQty <= 0) { $baseQty = $qty * $qtyPerUom; }
    if ($productId > 0 && $qty > 0) {
        $validItems[] = [
            'product_id' => $productId,
            'qty' => $qty,
            'batch_id' => $batchId,
            'uom_id' => $uomId,
            'qty_per_uom' => $qtyPerUom,
            'base_qty' => $baseQty,
        ];
    }
}

if (count($validItems) === 0) {
    redirectWithMessage('Please enter at least one valid item quantity.');
}

// Validate stock availability for each item (in BASE UOM)
foreach ($validItems as $item) {
    if ($item['batch_id']) {
        $stockRow = $db->getRow('SELECT SUM(ft_blanace) AS qty FROM fifo WHERE ft_item = ? AND ft_location = ? AND ft_type = 1 AND batch_id = ?', [$item['product_id'], $locationId, $item['batch_id']]);
    } else {
        $stockRow = $db->getRow('SELECT SUM(ft_blanace) AS qty FROM fifo WHERE ft_item = ? AND ft_location = ? AND ft_type = 1', [$item['product_id'], $locationId]);
    }
    $available = (float) ($stockRow['qty'] ?? 0);

    if (($item['base_qty'] - $available) > 0.000001) {
        $itemRow = $db->getRow('SELECT item_name FROM item_master WHERE item_id = ?', [$item['product_id']]);
        $itemName = $itemRow['item_name'] ?? ('Item ID ' . $item['product_id']);
        redirectWithMessage('Not enough stock for ' . $itemName . '. Required (base): ' . $item['base_qty'] . ', Available (base): ' . $available);
    }
}

$createdBy = str_replace(',', '', $_SESSION['userid'] ?? '');
$createdAt = date('Y-m-d H:i:s');

// Determine if we have expected finished products
$validExpected = [];
for ($i = 0; $i < count($expectedProductIds); $i++) {
    $expProdId = (int) ($expectedProductIds[$i] ?? 0);
    $expQty = (float) ($expectedQtys[$i] ?? 0);
    if ($expProdId > 0 && $expQty > 0) {
        $validExpected[] = ['product_id' => $expProdId, 'qty' => $expQty];
    }
}

$productionStatus = count($validExpected) > 0 ? 'PENDING' : null;
$destLocation = $toLocationId ?: $locationId; // default to same location if not specified

$db->insertRow(
    'INSERT INTO stock_issue_header (issue_code, issue_date, location_id, to_location_id, issued_to, status, production_status, remarks, created_by, created_at) VALUES (?,?,?,?,?,?,?,?,?,?)',
    [$issueCode, $issueDate, $locationId, count($validExpected) > 0 ? $destLocation : null, $issuedTo, 'ISSUED', $productionStatus, $remarks, $createdBy, $createdAt]
);

$row = $db->getRow('SELECT issue_id FROM stock_issue_header ORDER BY issue_id DESC LIMIT 1');
$issueId = (int) ($row['issue_id'] ?? 0);

if ($issueId <= 0) {
    redirectWithMessage('Failed to create stock issue note.');
}

foreach ($validItems as $item) {
    $productId = $item['product_id'];
    $qtyPerUom = (float) $item['qty_per_uom'];
    if ($qtyPerUom <= 0) { $qtyPerUom = 1.0; }
    $qtyToIssueBase = (float) $item['base_qty']; // FIFO operates in BASE UOM

    $remaining = $qtyToIssueBase;
    $totalCost = 0;
    $issuedQtyBase = 0;

    $batchId = $item['batch_id'];

    while ($remaining > 0) {
        if ($batchId) {
            $fifoRow = $db->getRow('SELECT * FROM fifo WHERE ft_item = ? AND ft_location = ? AND ft_type = 1 AND ft_blanace > 0 AND batch_id = ? ORDER BY ft_date ASC LIMIT 1', [$productId, $locationId, $batchId]);
        } else {
            $fifoRow = $db->getRow('SELECT * FROM fifo WHERE ft_item = ? AND ft_location = ? AND ft_type = 1 AND ft_blanace > 0 ORDER BY ft_date ASC LIMIT 1', [$productId, $locationId]);
        }
        if (!$fifoRow) {
            redirectWithMessage('Insufficient FIFO stock while processing issue.');
        }

        $balance = (float) $fifoRow['ft_blanace'];
        $rate = (float) ($fifoRow['ft_rate'] ?? 0);
        $deduct = ($balance >= $remaining) ? $remaining : $balance;

        $newBalance = $balance - $deduct;
        $db->updateRow('UPDATE fifo SET ft_blanace = ? WHERE ft_id = ?', [$newBalance, $fifoRow['ft_id']]);

        $totalCost += ($deduct * $rate);
        $issuedQtyBase += $deduct;
        $remaining -= $deduct;
    }

    $avgRate = ($issuedQtyBase > 0) ? ($totalCost / $issuedQtyBase) : 0;
    $total = $avgRate * $issuedQtyBase; // cost in base UOM
    $issuedQtyEntered = ($qtyPerUom > 0) ? ($issuedQtyBase / $qtyPerUom) : $issuedQtyBase;

    $db->insertRow(
        'INSERT INTO stock_issue_items (issue_id, product_id, qty, uom_id, qty_per_uom, qty_base, rate, total, batch_id) VALUES (?,?,?,?,?,?,?,?,?)',
        [$issueId, $productId, $issuedQtyEntered, $item['uom_id'], $qtyPerUom, $issuedQtyBase, $avgRate, $total, $batchId]
    );
}

// Save expected finished products
foreach ($validExpected as $exp) {
    $db->insertRow(
        'INSERT INTO stock_issue_expected_products (issue_id, product_id, expected_qty, received_qty, status) VALUES (?,?,?,?,?)',
        [$issueId, $exp['product_id'], $exp['qty'], 0, 'PENDING']
    );
}

redirect('stock-issue-view.php?id=' . $issueId . '&message=' . urlencode('Stock issue note created successfully') . '&type=success');
