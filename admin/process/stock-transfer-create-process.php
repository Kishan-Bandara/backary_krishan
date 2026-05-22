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
    redirect('stock-transfer-create.php?message=' . urlencode($message) . '&type=' . urlencode($type));
}

$transferCode = trim($_POST['transfer_code'] ?? '');
$transferDate = $_POST['transfer_date'] ?? date('Y-m-d');
$fromLocationId = (int) ($_POST['from_location_id'] ?? 0);
$toLocationId = (int) ($_POST['to_location_id'] ?? 0);
$remarks = $_POST['remarks'] ?? '';
$productIds = $_POST['product_id'] ?? [];
$transferQtys = $_POST['transfer_qty'] ?? [];
$batchIds = $_POST['batch_id'] ?? [];
$lineUomIds = $_POST['line_uom_id'] ?? [];
$lineQtyPerUoms = $_POST['line_qty_per_uom'] ?? [];
$lineBaseQtys = $_POST['line_base_qty'] ?? [];

if (!isSuperAdmin()) {
    $fromLocationId = (int) ($_SESSION['location'] ?? 0);
}

if ($fromLocationId <= 0 || $toLocationId <= 0) {
    redirectWithMessage('Please select both From and To locations.');
}

if ($fromLocationId === $toLocationId) {
    redirectWithMessage('From and To locations cannot be the same.');
}

if (empty($productIds) || empty($transferQtys)) {
    redirectWithMessage('Please add at least one item to transfer.');
}

$db = new Database();
ensureItemUomSchema($db);

$validItems = [];
for ($i = 0; $i < count($productIds); $i++) {
    $productId = (int) ($productIds[$i] ?? 0);
    $qty = (float) ($transferQtys[$i] ?? 0);
    $batchId = !empty($batchIds[$i]) ? (int) $batchIds[$i] : null;
    $uomId = isset($lineUomIds[$i]) && $lineUomIds[$i] !== '' ? (int) $lineUomIds[$i] : 0;
    $qtyPerUom = isset($lineQtyPerUoms[$i]) ? (float) $lineQtyPerUoms[$i] : 0;
    $baseQty = isset($lineBaseQtys[$i]) ? (float) $lineBaseQtys[$i] : 0;
    if ($qtyPerUom <= 0) { $qtyPerUom = 1.0; }
    if ($baseQty <= 0) { $baseQty = $qty * $qtyPerUom; }
    if ($productId > 0 && $qty > 0 && $baseQty > 0) {
        $validItems[] = [
            'product_id' => $productId,
            'qty' => $qty,
            'batch_id' => $batchId,
            'uom_id' => $uomId > 0 ? $uomId : null,
            'qty_per_uom' => $qtyPerUom,
            'base_qty' => $baseQty,
        ];
    }
}

if (count($validItems) === 0) {
    redirectWithMessage('Please enter at least one valid item quantity.');
}

// Validate stock availability for each item (FIFO is in BASE UOM)
foreach ($validItems as $item) {
    if ($item['batch_id']) {
        $stockRow = $db->getRow('SELECT SUM(ft_blanace) AS qty FROM fifo WHERE ft_item = ? AND ft_location = ? AND ft_type = 1 AND batch_id = ?', [$item['product_id'], $fromLocationId, $item['batch_id']]);
    } else {
        $stockRow = $db->getRow('SELECT SUM(ft_blanace) AS qty FROM fifo WHERE ft_item = ? AND ft_location = ? AND ft_type = 1', [$item['product_id'], $fromLocationId]);
    }
    $available = (float) ($stockRow['qty'] ?? 0);

    if ($available + 0.000001 < $item['base_qty']) {
        $itemRow = $db->getRow('SELECT item_name FROM item_master WHERE item_id = ?', [$item['product_id']]);
        $itemName = $itemRow['item_name'] ?? ('Item ID ' . $item['product_id']);
        redirectWithMessage('Not enough stock for ' . $itemName . '. Required (base): ' . $item['base_qty'] . ', Available: ' . $available);
    }
}

$createdBy = str_replace(',', '', $_SESSION['userid'] ?? '');
$createdAt = date('Y-m-d H:i:s');

$db->insertRow(
    'INSERT INTO stock_transfer_header (transfer_code, transfer_date, from_location_id, to_location_id, status, remarks, created_by, created_at) VALUES (?,?,?,?,?,?,?,?)',
    [$transferCode, $transferDate, $fromLocationId, $toLocationId, 'PENDING', $remarks, $createdBy, $createdAt]
);

$row = $db->getRow('SELECT transfer_id FROM stock_transfer_header ORDER BY transfer_id DESC LIMIT 1');
$transferId = (int) ($row['transfer_id'] ?? 0);

if ($transferId <= 0) {
    redirectWithMessage('Failed to create stock transfer.');
}

foreach ($validItems as $item) {
    $productId = $item['product_id'];
    $qtyToMoveBase = (float) $item['base_qty']; // FIFO works in base UOM
    $batchId = $item['batch_id'];

    $remaining = $qtyToMoveBase;
    $totalCost = 0;
    $movedQtyBase = 0;

    while ($remaining > 0) {
        if ($batchId) {
            $fifoRow = $db->getRow('SELECT * FROM fifo WHERE ft_item = ? AND ft_location = ? AND ft_type = 1 AND ft_blanace > 0 AND batch_id = ? ORDER BY ft_date ASC LIMIT 1', [$productId, $fromLocationId, $batchId]);
        } else {
            $fifoRow = $db->getRow('SELECT * FROM fifo WHERE ft_item = ? AND ft_location = ? AND ft_type = 1 AND ft_blanace > 0 ORDER BY ft_date ASC LIMIT 1', [$productId, $fromLocationId]);
        }
        if (!$fifoRow) {
            redirectWithMessage('Insufficient FIFO stock while processing transfer.');
        }

        $balance = (float) $fifoRow['ft_blanace'];
        $rate = (float) ($fifoRow['ft_rate'] ?? 0);
        $deduct = ($balance >= $remaining) ? $remaining : $balance;

        $newBalance = $balance - $deduct;
        $db->updateRow('UPDATE fifo SET ft_blanace = ? WHERE ft_id = ?', [$newBalance, $fifoRow['ft_id']]);

        $totalCost += ($deduct * $rate);
        $movedQtyBase += $deduct;
        $remaining -= $deduct;
    }

    // Average rate is per BASE UOM (consistent with FIFO storage)
    $avgRate = ($movedQtyBase > 0) ? ($totalCost / $movedQtyBase) : 0;
    $total = $avgRate * $movedQtyBase;

    // qty stored in entered (transfer) UOM; qty_base stored in base UOM
    $enteredQty = (float) $item['qty'];
    $qtyPerUom = (float) $item['qty_per_uom'];
    $movedEnteredQty = ($qtyPerUom > 0) ? ($movedQtyBase / $qtyPerUom) : $movedQtyBase;

    $db->insertRow(
        'INSERT INTO stock_transfer_items (transfer_id, product_id, qty, uom_id, qty_per_uom, qty_base, rate, total, batch_id) VALUES (?,?,?,?,?,?,?,?,?)',
        [$transferId, $productId, $movedEnteredQty, $item['uom_id'], $qtyPerUom, $movedQtyBase, $avgRate, $total, $batchId]
    );
}

redirect('stock-transfer-view.php?id=' . $transferId . '&message=' . urlencode('Stock transfer created. Awaiting receive confirmation.') . '&type=success');
