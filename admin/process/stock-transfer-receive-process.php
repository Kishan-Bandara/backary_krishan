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
    redirect('stock-transfer-receive-list.php?message=' . urlencode($message) . '&type=' . urlencode($type));
}

$transferId  = (int) ($_POST['transfer_id'] ?? 0);
$receivedBy  = substr(trim($_POST['received_by'] ?? ''), 0, 100);
if ($transferId <= 0) {
    redirectWithMessage('Invalid transfer.');
}
if ($receivedBy === '') {
    redirectWithMessage('Please enter the name of the receiver.');
}

$db = new Database();
ensureItemUomSchema($db);
$transfer = $db->getRow('SELECT * FROM stock_transfer_header WHERE transfer_id = ?', [$transferId]);
if (!$transfer) {
    redirectWithMessage('Transfer not found.');
}

if ($transfer['status'] !== 'PENDING') {
    redirectWithMessage('Transfer is not pending.', 'error');
}

$locationId = (int) ($_SESSION['location'] ?? 0);
$isSuperAdminUser = function_exists('isSuperAdmin') ? isSuperAdmin() : false;
if (!$isSuperAdminUser && $locationId !== (int) $transfer['to_location_id']) {
    redirectWithMessage('You are not authorized to confirm this transfer.', 'error');
}

$createdAt = date('Y-m-d H:i:s');
$createdBy = $_SESSION['userid'] ?? '';

$items = $db->getRows('SELECT * FROM stock_transfer_items WHERE transfer_id = ?', [$transferId]);
if (!$items || count($items) === 0) {
    redirectWithMessage('No transfer items found.', 'error');
}

// Ensure received_qty column exists on stock_transfer_items
$rqCol = $db->getRow("SHOW COLUMNS FROM stock_transfer_items LIKE 'received_qty'");
if (!$rqCol) {
    $db->insertRow("ALTER TABLE stock_transfer_items ADD COLUMN `received_qty` double(20,2) NOT NULL DEFAULT 0.00 AFTER `qty`", []);
}

$receivedQtyInput = $_POST['received_qty'] ?? [];
if (!is_array($receivedQtyInput)) { $receivedQtyInput = []; }

// Insert destination fifo and build data for GRN lines
$grnLines = [];
foreach ($items as $item) {
    $productId = (int) $item['product_id'];
    $qty = (float) $item['qty']; // entered (transfer) UOM
    $rate = (float) $item['rate']; // per BASE UOM
    $batchId = !empty($item['batch_id']) ? (int) $item['batch_id'] : null;
    $itemRowId = (int) $item['transfer_item_id'];
    $uomId = !empty($item['uom_id']) ? (int) $item['uom_id'] : null;
    $qtyPerUom = (float) ($item['qty_per_uom'] ?? 0);
    if ($qtyPerUom <= 0) { $qtyPerUom = 1.0; }
    $qtyBase = (float) ($item['qty_base'] ?? ($qty * $qtyPerUom));

    if ($qty <= 0) {
        continue;
    }

    // Determine received quantity (in entered UOM); default to full qty if not posted
    $recvQty = isset($receivedQtyInput[$itemRowId]) ? (float) $receivedQtyInput[$itemRowId] : $qty;
    if ($recvQty < 0) { $recvQty = 0; }
    if ($recvQty > $qty) { $recvQty = $qty; }

    // Convert receive qty to BASE UOM for FIFO
    $recvQtyBase = $recvQty * $qtyPerUom;

    // Persist received qty back on the transfer item (entered + base)
    $db->updateRow('UPDATE stock_transfer_items SET received_qty = ?, received_qty_base = ? WHERE transfer_item_id = ?', [$recvQty, $recvQtyBase, $itemRowId]);

    if ($recvQty <= 0) {
        // nothing received for this line
        continue;
    }

    $db->insertRow(
        'INSERT INTO fifo (ft_location, ft_document, ft_item, ft_qty, ft_blanace, ft_rate, ft_date, ft_type, batch_id) VALUES (?,?,?,?,?,?,?,?,?)',
        [$transfer['to_location_id'], $transferId, $productId, $recvQtyBase, $recvQtyBase, $rate, $createdAt, 1, $batchId]
    );

    // get VAT rate from item master
    $itemRow = $db->getRow('SELECT item_vat FROM item_master WHERE item_id = ?', [$productId]);
    $vatRate = (float) ($itemRow['item_vat'] ?? 0);
    // Rate is per BASE UOM, so totals are computed on base qty
    $lineTotal = $recvQtyBase * $rate;
    $vatAmount = ($lineTotal * $vatRate) / 100;

    $grnLines[] = [
        'product_id' => $productId,
        'qty' => $recvQty,
        'qty_base' => $recvQtyBase,
        'uom_id' => $uomId,
        'qty_per_uom' => $qtyPerUom,
        'rate' => $rate,
        'vat_rate' => $vatRate,
        'vat_amount' => $vatAmount,
        'line_total' => $lineTotal,
        'batch_id' => $batchId,
    ];
}

// Create GRN header for this receive
if (count($grnLines) > 0) {
    // generate GRN code
    $last = $db->getRow('SELECT grn_h_id FROM grn_hedder ORDER BY grn_h_id DESC LIMIT 1');
    $lastId = (int) ($last['grn_h_id'] ?? 0);
    $newId = $lastId + 1;
    $randomNo = rand(1000, 9999);
    $grnCode = 'GRN' . $randomNo . $newId;

    $db->insertRow(
        'INSERT INTO grn_hedder (grn_h_code, purchase_note_id, grn_h_supplier_id, grn_h_supplier_invoice_code, grn_h_date, grn_h_pay_type, grn_h_net_value, grn_h_vat_value, grn_h_gross_value, add_by, grn_h_location) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
        [$grnCode, 0, 0, 'Stock Transfer: ' . $transfer['transfer_code'], $createdAt, null, 0, 0, 0, $createdBy, $transfer['to_location_id']]
    );

    $grnRow = $db->getRow('SELECT grn_h_id FROM grn_hedder ORDER BY grn_h_id DESC LIMIT 1');
    $grnId = (int) ($grnRow['grn_h_id'] ?? 0);

    $totalNet = 0; $totalVat = 0;
    foreach ($grnLines as $line) {
        $db->insertRow(
            'INSERT INTO grn_details (grn_h_id, grn_d_item_id, purchase_note_item_id, grn_d_qty, grn_d_blance, grn_d_rate, grn_d_vat, grn_d_vat_rate, grn_d_total, batch_id, uom_id, qty_per_uom, grn_d_qty_base) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [$grnId, $line['product_id'], 0, $line['qty'], $line['qty_base'], $line['rate'], $line['vat_amount'], $line['vat_rate'], $line['line_total'], $line['batch_id'], $line['uom_id'], $line['qty_per_uom'], $line['qty_base']]
        );
        $totalNet += $line['line_total'];
        $totalVat += $line['vat_amount'];
    }

    $totalGross = $totalNet + $totalVat;
    $db->updateRow('UPDATE grn_hedder SET grn_h_net_value = ?, grn_h_vat_value = ?, grn_h_gross_value = ? WHERE grn_h_id = ?', [$totalNet, $totalVat, $totalGross, $grnId]);
}

// Finally mark transfer completed — auto-migrate received_by column if needed
$colCheck = $db->getRow("SHOW COLUMNS FROM stock_transfer_header LIKE 'received_by'");
if (!$colCheck) {
    $db->insertRow("ALTER TABLE stock_transfer_header ADD COLUMN `received_by` VARCHAR(100) NOT NULL DEFAULT '' AFTER `status`", []);
}
$db->updateRow('UPDATE stock_transfer_header SET status = ?, received_by = ? WHERE transfer_id = ?', ['COMPLETED', $receivedBy, $transferId]);

// ── Attachments ───────────────────────────────────────────────────────────────
$attTableCheck = $db->getRow("SHOW TABLES LIKE 'stock_transfer_attachments'");
if (!$attTableCheck) {
    $db->insertRow("CREATE TABLE `stock_transfer_attachments` (
        `attachment_id` INT(11)      NOT NULL AUTO_INCREMENT,
        `transfer_id`   INT(11)      NOT NULL,
        `original_name` VARCHAR(255) NOT NULL,
        `stored_name`   VARCHAR(255) NOT NULL,
        `file_path`     VARCHAR(500) NOT NULL,
        `file_size`     INT(11)      NOT NULL DEFAULT 0,
        `uploaded_by`   VARCHAR(100) NOT NULL DEFAULT '',
        `created_at`    DATETIME     NOT NULL,
        PRIMARY KEY (`attachment_id`),
        KEY `idx_transfer_id` (`transfer_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", []);
}

if (!empty($_FILES['transfer_attachments']['name'][0])) {
    $uploadDir   = dirname(__DIR__) . '/uploads/stock_transfer_attachments/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $allowedExts = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif'];
    $maxBytes    = 10 * 1024 * 1024;
    $fileCount   = min(5, count($_FILES['transfer_attachments']['name']));

    for ($fi = 0; $fi < $fileCount; $fi++) {
        if ((int) $_FILES['transfer_attachments']['error'][$fi] !== UPLOAD_ERR_OK) { continue; }
        $fileSize     = (int) $_FILES['transfer_attachments']['size'][$fi];
        $tmpName      = $_FILES['transfer_attachments']['tmp_name'][$fi];
        $originalName = basename($_FILES['transfer_attachments']['name'][$fi]);
        if ($fileSize <= 0 || $fileSize > $maxBytes) { continue; }
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts, true)) { continue; }
        if (!is_uploaded_file($tmpName)) { continue; }
        $storedName = 'st_' . $transferId . '_' . time() . '_' . $fi . '.' . $ext;
        if (move_uploaded_file($tmpName, $uploadDir . $storedName)) {
            $db->insertRow(
                'INSERT INTO stock_transfer_attachments (transfer_id, original_name, stored_name, file_path, file_size, uploaded_by, created_at) VALUES (?,?,?,?,?,?,?)',
                [$transferId, $originalName, $storedName, 'uploads/stock_transfer_attachments/' . $storedName, $fileSize, $createdBy, $createdAt]
            );
        }
    }
}
// ─────────────────────────────────────────────────────────────────────────────

redirect('stock-transfer-receive-list.php?message=' . urlencode('Stock transfer received successfully and GRN created: ' . ($grnCode ?? '')) . '&type=success');
