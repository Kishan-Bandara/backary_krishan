<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('../include/database.php');
include('../include/check_login.php');
include('../get_url.php');
require_once(__DIR__ . '/../include/uom_helper.php');

date_default_timezone_set("Asia/Colombo");

function redirectWithMessage($purchaseNoteId, $message, $type = 'error')
{
    redirect('grn-create.php?purchase_note_id=' . $purchaseNoteId . '&message=' . urlencode($message) . '&type=' . urlencode($type));
}

$purchaseNoteId = (int) ($_POST['purchase_note_id'] ?? 0);
$grnCode = trim($_POST['grn_code'] ?? '');
$grnDate = $_POST['grn_date'] ?? date('Y-m-d');
$submitAction = trim($_POST['submit_action'] ?? 'process');
$purchaseNoteItemIds = $_POST['purchase_note_item_id'] ?? [];
$receivedQtys = $_POST['received_qty'] ?? [];
$batchNos = $_POST['batch_no'] ?? [];
$expiryDates = $_POST['expiry_date'] ?? [];
$lineUomIds = $_POST['line_uom_id'] ?? [];
$lineQtyPerUoms = $_POST['line_qty_per_uom'] ?? [];

if ($purchaseNoteId <= 0) {
    redirect('purchase-order-list.php?message=' . urlencode('Invalid purchase note.') . '&type=error');
}

$db = new Database();
ensureItemUomSchema($db);
$note = $db->getRow('SELECT * FROM purchase_note_header WHERE purchase_note_id = ?', [$purchaseNoteId]);
if (!$note) {
    redirect('purchase-order-list.php?message=' . urlencode('Purchase note not found.') . '&type=error');
}

if ($note['status'] === 'COMPLETED') {
    redirect('purchase-order-view.php?id=' . $purchaseNoteId . '&message=' . urlencode('Purchase note already completed.') . '&type=error');
}

$validLines = [];
for ($i = 0; $i < count($purchaseNoteItemIds); $i++) {
    $itemId = (int) ($purchaseNoteItemIds[$i] ?? 0);
    $qty = (float) ($receivedQtys[$i] ?? 0);
    $batchNo = trim($batchNos[$i] ?? '');
    $expiryDate = trim($expiryDates[$i] ?? '');
    $uomId = (int) ($lineUomIds[$i] ?? 0);
    $qtyPerUom = (float) ($lineQtyPerUoms[$i] ?? 0);
    if ($itemId > 0 && $qty > 0) {
        $validLines[] = [
            'purchase_note_item_id' => $itemId,
            'received_qty' => $qty,
            'batch_no' => $batchNo,
            'expiry_date' => ($expiryDate !== '') ? $expiryDate : null,
            'uom_id' => $uomId > 0 ? $uomId : null,
            'qty_per_uom' => $qtyPerUom > 0 ? $qtyPerUom : 0,
        ];
    }
}

if (count($validLines) === 0) {
    redirectWithMessage($purchaseNoteId, 'Please enter at least one received quantity.');
}

$createdBy  = str_replace(',', '', $_SESSION['userid'] ?? '');
$receivedBy = substr(trim($_POST['received_by'] ?? ''), 0, 100);
$createdAt  = date('Y-m-d H:i:s');
$locationId = (int) $note['location_id'];
$supplierId = (int) $note['supplier_id'];

// Auto-migrate received_by column on grn_hedder if needed
$grnColCheck = $db->getRow("SHOW COLUMNS FROM grn_hedder LIKE 'received_by'");
if (!$grnColCheck) {
    $db->insertRow("ALTER TABLE grn_hedder ADD COLUMN `received_by` VARCHAR(100) NOT NULL DEFAULT '' AFTER add_by", []);
}

$db->insertRow(
    'INSERT INTO grn_hedder (grn_h_code, purchase_note_id, grn_h_supplier_id, grn_h_supplier_invoice_code, grn_h_date, grn_h_pay_type, grn_h_net_value, grn_h_vat_value, grn_h_gross_value, add_by, received_by, grn_h_location) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
    [$grnCode, $purchaseNoteId, $supplierId, '', $createdAt, null, 0, 0, 0, $createdBy, $receivedBy, $locationId]
);

$grnRow = $db->getRow('SELECT grn_h_id FROM grn_hedder ORDER BY grn_h_id DESC LIMIT 1');
$grnId = (int) ($grnRow['grn_h_id'] ?? 0);
if ($grnId <= 0) {
    redirectWithMessage($purchaseNoteId, 'Failed to create GRN.');
}

$transactionType = 1;

foreach ($validLines as $line) {
    $purchaseNoteItemId = $line['purchase_note_item_id'];
    $receivedQty = $line['received_qty'];
    $batchNo = $line['batch_no'];
    $expiryDate = $line['expiry_date'];
    $lineUomId = $line['uom_id'];
    $lineQtyPerUom = $line['qty_per_uom'];

    $itemRow = $db->getRow('SELECT * FROM purchase_note_items WHERE purchase_note_item_id = ?', [$purchaseNoteItemId]);
    if (!$itemRow || (int) $itemRow['purchase_note_id'] !== $purchaseNoteId) {
        redirectWithMessage($purchaseNoteId, 'Invalid purchase note item selection.');
    }

    $productId = (int) $itemRow['product_id'];
    $product = $db->getRow('SELECT item_purchase_price, item_vat, batch_tracking, unit_of_measure FROM item_master WHERE item_id = ?', [$productId]);

    // Determine PO line UOM + conversion (fallback to base)
    $poUomId = (int) ($itemRow['uom_id'] ?? 0);
    $poQtyPerUom = (float) ($itemRow['qty_per_uom'] ?? 0);
    if ($poQtyPerUom <= 0) { $poQtyPerUom = 1.0; }
    if ($poUomId <= 0) { $poUomId = resolveBaseUomIdFromString($db, $product['unit_of_measure'] ?? ''); }

    // Validate / resolve receive UOM. If invalid, fall back to PO UOM.
    if ($lineUomId === null || $lineUomId <= 0) {
        $lineUomId = $poUomId;
        $lineQtyPerUom = $poQtyPerUom;
    } else {
        $resolved = getItemUomConversion($db, $productId, $lineUomId);
        if ($resolved > 0) {
            $lineQtyPerUom = $resolved;
        } elseif ($lineQtyPerUom <= 0) {
            $lineQtyPerUom = ($lineUomId === $poUomId) ? $poQtyPerUom : 1.0;
        }
    }
    if ($lineQtyPerUom <= 0) { $lineQtyPerUom = 1.0; }

    // Convert PO line balance into base qty for the comparison
    $balanceBase = (float) ($itemRow['balance_qty_base'] ?? 0);
    if ($balanceBase <= 0) {
        $balanceBase = ((float) $itemRow['balance_qty']) * $poQtyPerUom;
    }
    $receivedQtyBase = $receivedQty * $lineQtyPerUom;
    if ($receivedQtyBase > $balanceBase + 0.0001) {
        redirectWithMessage($purchaseNoteId, 'Received quantity exceeds balance (in base units).');
    }

    // Pricing uses the saved PO line override when present, with item master as a fallback for old rows.
    $rate = isset($itemRow['unit_price']) && $itemRow['unit_price'] !== null
        ? (float) $itemRow['unit_price']
        : (float) ($product['item_purchase_price'] ?? 0);
    $vatRate = isset($itemRow['vat_rate']) && $itemRow['vat_rate'] !== null
        ? (float) $itemRow['vat_rate']
        : (float) ($product['item_vat'] ?? 0);
    $total = $receivedQtyBase * $rate;
    $vatAmount = ($total * $vatRate) / 100;
    $batchTracking = $product['batch_tracking'] ?? 'NONE';

    // Handle batch tracking — create or find batch_master record
    $batchId = null;
    if (($batchTracking === 'BATCH' || $batchTracking === 'SERIAL') && $batchNo !== '') {
        // Check if batch already exists for this product
        $existingBatch = $db->getRow('SELECT batch_id FROM batch_master WHERE product_id = ? AND batch_no = ?', [$productId, $batchNo]);
        if ($existingBatch) {
            $batchId = (int) $existingBatch['batch_id'];
            // Update expiry date if provided and not already set
            if ($expiryDate !== null) {
                $db->updateRow('UPDATE batch_master SET expiry_date = ? WHERE batch_id = ? AND expiry_date IS NULL', [$expiryDate, $batchId]);
            }
        } else {
            $db->insertRow(
                'INSERT INTO batch_master (product_id, batch_no, expiry_date) VALUES (?,?,?)',
                [$productId, $batchNo, $expiryDate]
            );
            $batchRow = $db->getRow('SELECT batch_id FROM batch_master WHERE product_id = ? AND batch_no = ?', [$productId, $batchNo]);
            $batchId = (int) ($batchRow['batch_id'] ?? 0);
        }
    }

    $db->insertRow(
        'INSERT INTO grn_details (grn_h_id, grn_d_item_id, purchase_note_item_id, grn_d_qty, grn_d_blance, grn_d_rate, grn_d_vat, grn_d_vat_rate, grn_d_total, batch_id, uom_id, qty_per_uom, grn_d_qty_base) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
        [$grnId, $productId, $purchaseNoteItemId, $receivedQty, $receivedQty, $rate, $vatAmount, $vatRate, $total, $batchId, $lineUomId, $lineQtyPerUom, $receivedQtyBase]
    );

    // FIFO is posted in BASE qty so stock is consistent across UOMs
    $db->insertRow(
        'INSERT INTO fifo (ft_location, ft_document, ft_item, ft_qty, ft_blanace, ft_rate, ft_date, ft_type, batch_id) VALUES (?,?,?,?,?,?,?,?,?)',
        [$locationId, $grnId, $productId, $receivedQtyBase, $receivedQtyBase, $rate, $createdAt, $transactionType, $batchId]
    );

    // Maintain both legacy (PO-UOM) and base-qty totals on the PO line
    $newTotalReceivedBase = (float) ($itemRow['total_received_qty_base'] ?? 0) + $receivedQtyBase;
    $requestedQtyBase = (float) ($itemRow['requested_qty_base'] ?? 0);
    if ($requestedQtyBase <= 0) {
        $requestedQtyBase = ((float) $itemRow['requested_qty']) * $poQtyPerUom;
    }
    $newBalanceBase = $requestedQtyBase - $newTotalReceivedBase;
    if ($newBalanceBase < 0) { $newBalanceBase = 0; }

    // PO-UOM legacy totals derived from base for consistency
    $newTotalReceivedPoUom = ($poQtyPerUom > 0) ? ($newTotalReceivedBase / $poQtyPerUom) : $newTotalReceivedBase;
    $newBalancePoUom = ((float) $itemRow['requested_qty']) - $newTotalReceivedPoUom;
    if ($newBalancePoUom < 0) { $newBalancePoUom = 0; }

    $db->updateRow(
        'UPDATE purchase_note_items SET total_received_qty = ?, balance_qty = ?, total_received_qty_base = ?, balance_qty_base = ? WHERE purchase_note_item_id = ?',
        [$newTotalReceivedPoUom, $newBalancePoUom, $newTotalReceivedBase, $newBalanceBase, $purchaseNoteItemId]
    );
}

$items = $db->getRows('SELECT balance_qty, total_received_qty FROM purchase_note_items WHERE purchase_note_id = ?', [$purchaseNoteId]);
$allCompleted = true;
$anyReceived = false;
foreach ($items as $item) {
    if ((float) $item['balance_qty'] > 0) {
        $allCompleted = false;
    }
    if ((float) $item['total_received_qty'] > 0) {
        $anyReceived = true;
    }
}

// Calculate totals for GRN header (sum of grn_details)
$totals = $db->getRow('SELECT COALESCE(SUM(grn_d_total),0) AS net, COALESCE(SUM(grn_d_vat),0) AS vat FROM grn_details WHERE grn_h_id = ?', [$grnId]);
$grn_net = (float) ($totals['net'] ?? 0);
$grn_vat = (float) ($totals['vat'] ?? 0);
$grn_gross = $grn_net + $grn_vat;

$db->updateRow('UPDATE grn_hedder SET grn_h_net_value = ?, grn_h_vat_value = ?, grn_h_gross_value = ? WHERE grn_h_id = ?', [$grn_net, $grn_vat, $grn_gross, $grnId]);

$status = 'OPEN';
if ($allCompleted) {
    $status = 'COMPLETED';
} elseif ($anyReceived) {
    $status = 'PARTIALLY_RECEIVED';
}

$db->updateRow('UPDATE purchase_note_header SET status = ? WHERE purchase_note_id = ?', [$status, $purchaseNoteId]);

// ── Attachments ──────────────────────────────────────────────────────────────
$tableCheck = $db->getRow("SHOW TABLES LIKE 'grn_attachments'");
if (!$tableCheck) {
    $db->insertRow("CREATE TABLE `grn_attachments` (
        `attachment_id` INT(11) NOT NULL AUTO_INCREMENT,
        `grn_h_id`      INT(11) NOT NULL,
        `original_name` VARCHAR(255) NOT NULL,
        `stored_name`   VARCHAR(255) NOT NULL,
        `file_path`     VARCHAR(500) NOT NULL,
        `file_size`     INT(11) NOT NULL DEFAULT 0,
        `uploaded_by`   VARCHAR(100) NOT NULL DEFAULT '',
        `created_at`    DATETIME NOT NULL,
        PRIMARY KEY (`attachment_id`),
        KEY `idx_grn_h_id` (`grn_h_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", []);
}

if (!empty($_FILES['grn_attachments']['name'][0])) {
    $uploadDir = dirname(__DIR__) . '/uploads/grn_attachments/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $allowedExts = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif'];
    $maxBytes    = 10 * 1024 * 1024;
    $fileCount   = min(5, count($_FILES['grn_attachments']['name']));

    for ($fi = 0; $fi < $fileCount; $fi++) {
        if ((int) $_FILES['grn_attachments']['error'][$fi] !== UPLOAD_ERR_OK) { continue; }
        $fileSize    = (int) $_FILES['grn_attachments']['size'][$fi];
        $tmpName     = $_FILES['grn_attachments']['tmp_name'][$fi];
        $originalName = basename($_FILES['grn_attachments']['name'][$fi]);
        if ($fileSize <= 0 || $fileSize > $maxBytes) { continue; }
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts, true)) { continue; }
        if (!is_uploaded_file($tmpName)) { continue; }
        $storedName = 'grn_' . $grnId . '_' . time() . '_' . $fi . '.' . $ext;
        if (move_uploaded_file($tmpName, $uploadDir . $storedName)) {
            $db->insertRow(
                'INSERT INTO grn_attachments (grn_h_id, original_name, stored_name, file_path, file_size, uploaded_by, created_at) VALUES (?,?,?,?,?,?,?)',
                [$grnId, $originalName, $storedName, 'uploads/grn_attachments/' . $storedName, $fileSize, $createdBy, $createdAt]
            );
        }
    }
}
// ─────────────────────────────────────────────────────────────────────────────

if ($submitAction === 'line_save') {
    if ($allCompleted) {
        redirect('purchase-order-view.php?id=' . $purchaseNoteId . '&message=' . urlencode('GRN saved and purchase note completed successfully') . '&type=success');
    }

    redirect('grn-create.php?purchase_note_id=' . $purchaseNoteId . '&line_mode=1&message=' . urlencode('Line saved. Remaining items loaded for next GRN entry.') . '&type=success');
}

redirect('purchase-order-view.php?id=' . $purchaseNoteId . '&message=' . urlencode('GRN created successfully') . '&type=success');
