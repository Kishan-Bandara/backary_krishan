<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('../include/database.php');
include('../include/check_login.php');
include('../get_url.php');

date_default_timezone_set("Asia/Colombo");

function redirectWithMessage($grnId, $message, $type = 'error')
{
    redirect('purchase-return-create.php?grn_id=' . (int)$grnId . '&message=' . urlencode($message) . '&type=' . urlencode($type));
}

$grnId = (int) ($_POST['grn_id'] ?? 0);
$returnNo = trim($_POST['return_no'] ?? '');
$returnDate = $_POST['return_date'] ?? date('Y-m-d');
$remarks = trim($_POST['remarks'] ?? '');
$grnDetailIds = $_POST['grn_d_id'] ?? [];
$returnQtys = $_POST['return_qty'] ?? [];

if ($grnId <= 0 || $returnNo === '') {
    redirectWithMessage($grnId, 'Invalid return request.');
}

$db = new Database();
$grn = $db->getRow('SELECT * FROM grn_hedder WHERE grn_h_id = ?', [$grnId]);
if (!$grn) {
    redirectWithMessage($grnId, 'GRN not found.');
}

if (!isSuperAdmin() && (int) $grn['grn_h_location'] !== (int) ($_SESSION['location'] ?? 0)) {
    redirect('access_denied.php');
}

$validLines = [];
for ($i = 0; $i < count($grnDetailIds); $i++) {
    $grnDetailId = (int) ($grnDetailIds[$i] ?? 0);
    $qty = (float) ($returnQtys[$i] ?? 0);
    if ($grnDetailId > 0 && $qty > 0) {
        $validLines[] = ['grn_d_id' => $grnDetailId, 'qty' => $qty];
    }
}

if (count($validLines) === 0) {
    redirectWithMessage($grnId, 'Please enter at least one return quantity.');
}

// Validate and compute totals
$netTotal = 0.0;
$vatTotal = 0.0;
$lines = [];

foreach ($validLines as $line) {
    $grnDetailId = $line['grn_d_id'];
    $qty = (float) $line['qty'];

    $gd = $db->getRow('SELECT * FROM grn_details WHERE grn_d_id = ?', [$grnDetailId]);
    if (!$gd || (int)$gd['grn_h_id'] !== $grnId) {
        redirectWithMessage($grnId, 'Invalid GRN item selection.');
    }

    $itemId = (int) $gd['grn_d_item_id'];
    $rate = (float) ($gd['grn_d_rate'] ?? 0);
    $vatRate = (float) ($gd['grn_d_vat_rate'] ?? 0);

    $returnedRow = $db->getRow('SELECT COALESCE(SUM(pr_d_qty),0) AS qty FROM purchase_return_details WHERE grn_d_id = ?', [$grnDetailId]);
    $alreadyReturned = (float) ($returnedRow['qty'] ?? 0);
    $balanceQty = (float) ($gd['grn_d_qty'] ?? 0) - $alreadyReturned;
    if ($qty > $balanceQty) {
        redirectWithMessage($grnId, 'Return quantity cannot exceed balance for an item.');
    }

    // Check stock availability
    $stockRow = $db->getRow('SELECT SUM(ft_blanace) AS qty FROM fifo WHERE ft_item = ? AND ft_location = ? AND ft_type = 1', [$itemId, (int)$grn['grn_h_location']]);
    $available = (float) ($stockRow['qty'] ?? 0);
    if ($available < $qty) {
        $itemRow = $db->getRow('SELECT item_name FROM item_master WHERE item_id = ?', [$itemId]);
        $itemName = $itemRow['item_name'] ?? ('Item ID ' . $itemId);
        redirectWithMessage($grnId, 'Not enough stock for ' . $itemName . '. Available: ' . $available);
    }

    $lineNet = $qty * $rate;
    $lineVat = ($lineNet * $vatRate) / 100.0;
    $lineTotal = $lineNet + $lineVat;

    $netTotal += $lineNet;
    $vatTotal += $lineVat;

    $lines[] = [
        'grn_d_id' => $grnDetailId,
        'item_id' => $itemId,
        'qty' => $qty,
        'rate' => $rate,
        'vat_rate' => $vatRate,
        'vat' => $lineVat,
        'total' => $lineTotal
    ];
}

$grossTotal = $netTotal + $vatTotal;
$createdBy = str_replace(',', '', $_SESSION['userid'] ?? '');
$returnDateTime = $returnDate . ' ' . date('H:i:s');

$db->insertRow(
    'INSERT INTO purchase_return_header (pr_h_code, grn_h_id, supplier_id, location_id, pr_date, pr_net, pr_vat, pr_gross, created_by, remarks) VALUES (?,?,?,?,?,?,?,?,?,?)',
    [$returnNo, $grnId, (int)$grn['grn_h_supplier_id'], (int)$grn['grn_h_location'], $returnDateTime, $netTotal, $vatTotal, $grossTotal, $createdBy, $remarks]
);

$row = $db->getRow('SELECT pr_h_id FROM purchase_return_header ORDER BY pr_h_id DESC LIMIT 1');
$prId = (int) ($row['pr_h_id'] ?? 0);
if ($prId <= 0) {
    redirectWithMessage($grnId, 'Failed to create purchase return.');
}

foreach ($lines as $ln) {
    $db->insertRow(
        'INSERT INTO purchase_return_details (pr_h_id, grn_d_id, item_id, pr_d_qty, pr_d_rate, pr_d_vat_rate, pr_d_vat, pr_d_total) VALUES (?,?,?,?,?,?,?,?)',
        [$prId, $ln['grn_d_id'], $ln['item_id'], $ln['qty'], $ln['rate'], $ln['vat_rate'], $ln['vat'], $ln['total']]
    );

    // Reduce FIFO stock (similar to stock issue)
    $remaining = $ln['qty'];
    while ($remaining > 0) {
        $fifoRow = $db->getRow('SELECT * FROM fifo WHERE ft_item = ? AND ft_location = ? AND ft_type = 1 AND ft_blanace > 0 ORDER BY ft_date ASC LIMIT 1', [$ln['item_id'], (int)$grn['grn_h_location']]);
        if (!$fifoRow) {
            redirectWithMessage($grnId, 'Insufficient FIFO stock while processing return.');
        }
        $balance = (float) $fifoRow['ft_blanace'];
        $deduct = ($balance >= $remaining) ? $remaining : $balance;
        $newBalance = $balance - $deduct;
        $db->updateRow('UPDATE fifo SET ft_blanace = ? WHERE ft_id = ?', [$newBalance, $fifoRow['ft_id']]);
        $remaining -= $deduct;
    }
}

redirect('purchase-return-note.php?id=' . $prId . '&message=' . urlencode('Purchase return created successfully') . '&type=success');
