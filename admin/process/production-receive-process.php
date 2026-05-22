<?php
/**
 * Production Receive Process
 * ==========================
 * When finished products are received from kitchen production:
 * 1. Update stock_issue_expected_products received_qty & status
 * 2. Create GRN header + details for the received finished products
 * 3. Insert FIFO entries at the destination location (stock in)
 * 4. Update stock_issue_header production_status
 */
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('../include/database.php');
include('../include/check_login.php');
include('../get_url.php');

date_default_timezone_set("Asia/Colombo");

function redirectWithMessage($message, $type = 'error', $issueId = 0)
{
    if ($issueId > 0) {
        redirect('production-receive-confirm.php?issue_id=' . $issueId . '&message=' . urlencode($message) . '&type=' . urlencode($type));
    }
    redirect('production-receive-list.php?message=' . urlencode($message) . '&type=' . urlencode($type));
}

$issueId = (int) ($_POST['issue_id'] ?? 0);
$expectedIds = $_POST['expected_id'] ?? [];
$productIds = $_POST['product_id'] ?? [];
$receiveQtys = $_POST['receive_qty'] ?? [];
$batchNos = $_POST['batch_no'] ?? [];
$expiryDates = $_POST['expiry_date'] ?? [];
$linkedRaw   = $_POST['linked_raw'] ?? [];   // [expected_id => [issue_item_id, ...]]
$linkedQty   = $_POST['linked_qty'] ?? [];   // [expected_id => [issue_item_id => qty]]

if ($issueId <= 0) {
    redirectWithMessage('Invalid stock issue note.');
}

$db = new Database();

// Validate issue exists and is pending
$issue = $db->getRow('SELECT * FROM stock_issue_header WHERE issue_id = ?', [$issueId]);
if (!$issue) {
    redirectWithMessage('Stock issue note not found.');
}

if (!in_array($issue['production_status'], ['PENDING', 'PARTIALLY_RECEIVED'])) {
    redirectWithMessage('This production has already been completed.', 'error', $issueId);
}

$toLocationId = (int) ($issue['to_location_id'] ?? $issue['location_id']);
$createdBy = str_replace(',', '', $_SESSION['userid'] ?? '');
$createdAt = date('Y-m-d H:i:s');

// Build valid receive items
$receiveItems = [];
for ($i = 0; $i < count($expectedIds); $i++) {
    $expId = (int) ($expectedIds[$i] ?? 0);
    $prodId = (int) ($productIds[$i] ?? 0);
    $qty = (float) ($receiveQtys[$i] ?? 0);
    $batchNo = trim((string) ($batchNos[$i] ?? ''));
    $expiryDate = trim((string) ($expiryDates[$i] ?? ''));

    if ($expId > 0 && $prodId > 0 && $qty > 0) {
        $productRow = $db->getRow('SELECT item_name, batch_tracking FROM item_master WHERE item_id = ?', [$prodId]);
        $itemName = $productRow['item_name'] ?? ('Product ID ' . $prodId);
        $batchTracking = $productRow['batch_tracking'] ?? 'NONE';

        if (in_array($batchTracking, ['BATCH', 'SERIAL'], true) && $batchNo === '') {
            $fieldLabel = $batchTracking === 'SERIAL' ? 'serial number' : 'batch number';
            redirectWithMessage('Please enter a ' . $fieldLabel . ' for ' . $itemName . '.', 'error', $issueId);
        }

        // Validate against expected record
        $expRow = $db->getRow('SELECT * FROM stock_issue_expected_products WHERE id = ? AND issue_id = ?', [$expId, $issueId]);
        if (!$expRow) {
            redirectWithMessage('Invalid expected product record.', 'error', $issueId);
        }

        $remaining = (float) $expRow['expected_qty'] - (float) $expRow['received_qty'];
        if ($qty > $remaining + 0.01) { // small tolerance for floating point
            redirectWithMessage('Receive quantity for ' . $itemName . ' exceeds remaining (' . number_format($remaining, 2) . ').', 'error', $issueId);
        }

        $receiveItems[] = [
            'expected_id' => $expId,
            'product_id' => $prodId,
            'qty' => $qty,
            'expected_row' => $expRow,
            'item_name' => $itemName,
            'batch_tracking' => $batchTracking,
            'batch_no' => $batchNo,
            'expiry_date' => $expiryDate !== '' ? $expiryDate : null
        ];
    }
}

if (count($receiveItems) === 0) {
    redirectWithMessage('Please enter at least one receive quantity.', 'error', $issueId);
}

// ============================================================
// 1. Generate GRN code
// ============================================================
$lastGrn = $db->getRow('SELECT grn_h_id FROM grn_hedder ORDER BY grn_h_id DESC LIMIT 1');
$lastGrnId = (int) ($lastGrn['grn_h_id'] ?? 0);
$newGrnId = $lastGrnId + 1;
$randomNo = rand(1000, 9999);
$grnCode = 'GRN' . $randomNo . $newGrnId;

// ============================================================
// 2. Create GRN header
// ============================================================
$db->insertRow(
    'INSERT INTO grn_hedder (grn_h_code, purchase_note_id, grn_h_supplier_id, grn_h_supplier_invoice_code, grn_h_date, grn_h_pay_type, grn_h_net_value, grn_h_vat_value, grn_h_gross_value, add_by, grn_h_location) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
    [$grnCode, 0, 0, 'Production: ' . $issue['issue_code'], $createdAt, null, 0, 0, 0, $createdBy, $toLocationId]
);

$grnRow = $db->getRow('SELECT grn_h_id FROM grn_hedder ORDER BY grn_h_id DESC LIMIT 1');
$grnId = (int) ($grnRow['grn_h_id'] ?? 0);

if ($grnId <= 0) {
    redirectWithMessage('Failed to create GRN record.', 'error', $issueId);
}

// ============================================================
// 3. Process each received product
// ============================================================
$totalNet = 0;
$totalVat = 0;

// Auto-migrate: batch_lineage table
$lineageTblCheck = $db->getRow("SHOW TABLES LIKE 'batch_lineage'");
if (!$lineageTblCheck) {
    $db->insertRow("CREATE TABLE `batch_lineage` (
        `lineage_id`         INT(11)        NOT NULL AUTO_INCREMENT,
        `finished_batch_id`  INT(11)        NOT NULL,
        `finished_item_id`   INT(11)        NOT NULL,
        `raw_batch_id`       INT(11)            NULL,
        `raw_item_id`        INT(11)        NOT NULL,
        `raw_qty_used`       DECIMAL(18,4)  NOT NULL DEFAULT 0,
        `issue_id`           INT(11)            NULL,
        `created_at`         DATETIME       NOT NULL,
        `created_by`         VARCHAR(100)   NOT NULL DEFAULT '',
        PRIMARY KEY (`lineage_id`),
        KEY `idx_finished_batch` (`finished_batch_id`),
        KEY `idx_raw_batch`      (`raw_batch_id`),
        KEY `idx_issue`          (`issue_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", []);
}

foreach ($receiveItems as $item) {
    $productId = $item['product_id'];
    $qty = $item['qty'];
    $expId = $item['expected_id'];
    $expRow = $item['expected_row'];
    $batchTracking = $item['batch_tracking'];
    $batchId = null;

    // Get product cost rate (use purchase price from item_master as default rate)
    $itemRow = $db->getRow('SELECT item_purchase_price, item_vat FROM item_master WHERE item_id = ?', [$productId]);
    $rate = (float) ($itemRow['item_purchase_price'] ?? 0);
    $vatRate = 0; // Production receives typically no VAT
    $lineTotal = $qty * $rate;
    $vatAmount = ($lineTotal * $vatRate) / 100;

    if (in_array($batchTracking, ['BATCH', 'SERIAL'], true)) {
        $existingBatch = $db->getRow('SELECT batch_id FROM batch_master WHERE product_id = ? AND batch_no = ?', [$productId, $item['batch_no']]);
        if ($existingBatch) {
            $batchId = (int) ($existingBatch['batch_id'] ?? 0);
            if (!empty($item['expiry_date'])) {
                $db->updateRow('UPDATE batch_master SET expiry_date = ? WHERE batch_id = ? AND expiry_date IS NULL', [$item['expiry_date'], $batchId]);
            }
        } else {
            $db->insertRow(
                'INSERT INTO batch_master (product_id, batch_no, expiry_date) VALUES (?,?,?)',
                [$productId, $item['batch_no'], $item['expiry_date']]
            );
            $batchRow = $db->getRow('SELECT batch_id FROM batch_master WHERE product_id = ? AND batch_no = ?', [$productId, $item['batch_no']]);
            $batchId = (int) ($batchRow['batch_id'] ?? 0);
        }

        if ($batchId <= 0) {
            redirectWithMessage('Failed to save batch information for ' . $item['item_name'] . '.', 'error', $issueId);
        }
    }

    // 3a. Insert FIFO entry at destination location (stock IN)
    $db->insertRow(
        'INSERT INTO fifo (ft_location, ft_document, ft_item, ft_qty, ft_blanace, ft_rate, ft_date, ft_type, batch_id) VALUES (?,?,?,?,?,?,?,?,?)',
        [$toLocationId, $grnId, $productId, $qty, $qty, $rate, $createdAt, 1, $batchId]
    );

    // 3b. Insert GRN detail line
    $db->insertRow(
        'INSERT INTO grn_details (grn_h_id, grn_d_item_id, purchase_note_item_id, grn_d_qty, grn_d_blance, grn_d_rate, grn_d_vat, grn_d_vat_rate, grn_d_total, batch_id) VALUES (?,?,?,?,?,?,?,?,?,?)',
        [$grnId, $productId, 0, $qty, $qty, $rate, $vatAmount, $vatRate, $lineTotal, $batchId]
    );

    // 3b-1. Save raw-material -> finished batch lineage (if finished is tracked)
    if ($batchId !== null && $batchId > 0 && isset($linkedRaw[$expId]) && is_array($linkedRaw[$expId])) {
        foreach ($linkedRaw[$expId] as $issueItemId) {
            $issueItemId = (int) $issueItemId;
            if ($issueItemId <= 0) { continue; }
            $rawRow = $db->getRow(
                'SELECT product_id, batch_id, qty FROM stock_issue_items WHERE issue_item_id = ? AND issue_id = ?',
                [$issueItemId, $issueId]
            );
            if (!$rawRow) { continue; }
            $rawItemId  = (int) $rawRow['product_id'];
            $rawBatchId = !empty($rawRow['batch_id']) ? (int) $rawRow['batch_id'] : null;
            $rawQtyUsed = isset($linkedQty[$expId][$issueItemId])
                ? (float) $linkedQty[$expId][$issueItemId]
                : (float) $rawRow['qty'];
            $db->insertRow(
                'INSERT INTO batch_lineage (finished_batch_id, finished_item_id, raw_batch_id, raw_item_id, raw_qty_used, issue_id, created_at, created_by) VALUES (?,?,?,?,?,?,?,?)',
                [$batchId, $productId, $rawBatchId, $rawItemId, $rawQtyUsed, $issueId, $createdAt, $createdBy]
            );
        }
    }

    $totalNet += $lineTotal;
    $totalVat += $vatAmount;

    // 3c. Update expected product record
    $newReceivedQty = (float) $expRow['received_qty'] + $qty;
    $expectedQty = (float) $expRow['expected_qty'];

    if ($newReceivedQty >= $expectedQty) {
        $newStatus = 'COMPLETED';
        $newReceivedQty = $expectedQty; // cap at expected
    } else {
        $newStatus = 'PARTIALLY_RECEIVED';
    }

    $db->updateRow(
        'UPDATE stock_issue_expected_products SET received_qty = ?, status = ? WHERE id = ?',
        [$newReceivedQty, $newStatus, $expId]
    );
}

// ============================================================
// 4. Update GRN totals
// ============================================================
$totalGross = $totalNet + $totalVat;
$db->updateRow(
    'UPDATE grn_hedder SET grn_h_net_value = ?, grn_h_vat_value = ?, grn_h_gross_value = ? WHERE grn_h_id = ?',
    [$totalNet, $totalVat, $totalGross, $grnId]
);

// ============================================================
// 5. Update stock_issue_header production_status
// ============================================================
$allExpected = $db->getRows(
    'SELECT status FROM stock_issue_expected_products WHERE issue_id = ?',
    [$issueId]
);

$allCompleted = true;
$anyReceived = false;
foreach ($allExpected as $e) {
    if ($e['status'] !== 'COMPLETED') {
        $allCompleted = false;
    }
    if ($e['status'] === 'COMPLETED' || $e['status'] === 'PARTIALLY_RECEIVED') {
        $anyReceived = true;
    }
}

if ($allCompleted) {
    $newProductionStatus = 'COMPLETED';
} elseif ($anyReceived) {
    $newProductionStatus = 'PARTIALLY_RECEIVED';
} else {
    $newProductionStatus = 'PENDING';
}

$db->updateRow(
    'UPDATE stock_issue_header SET production_status = ? WHERE issue_id = ?',
    [$newProductionStatus, $issueId]
);

// ============================================================
// 6. Redirect with success
// ============================================================
$receivedCount = count($receiveItems);
$successMsg = "Production receive confirmed! {$receivedCount} product(s) received. GRN created: {$grnCode}. Stock added to destination location.";

redirect('production-receive-list.php?message=' . urlencode($successMsg) . '&type=success');
