<?php
/**
 * Invoice Delivery Process
 * Saves the chosen batch_id per invoice line and marks the invoice as DELIVERED.
 * Auto-migrates the required columns on first hit so the page never breaks.
 */
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('../include/database.php');
include('../include/check_login.php');
include('../get_url.php');

date_default_timezone_set("Asia/Colombo");

function redirectBack($invoiceId, $message, $type = 'error')
{
    if ($invoiceId > 0) {
        redirect('invoice-delivery.php?id=' . $invoiceId . '&message=' . urlencode($message) . '&type=' . urlencode($type));
    }
    redirect('manage-invoices.php?message=' . urlencode($message) . '&type=' . urlencode($type));
}

$invoiceId  = (int) ($_POST['invoice_h_id'] ?? 0);
$detailIds  = $_POST['invoice_d_id'] ?? [];
$batchIds   = $_POST['batch_id'] ?? [];

if ($invoiceId <= 0) {
    redirectBack(0, 'Invalid invoice.');
}

$db = new Database();

// Auto-migrate: invoice_details.batch_id
$colCheck = $db->getRow("SHOW COLUMNS FROM invoice_details LIKE 'batch_id'");
if (!$colCheck) {
    $db->insertRow("ALTER TABLE invoice_details ADD COLUMN `batch_id` INT(11) DEFAULT NULL AFTER `is_cart_item`", []);
}
// Auto-migrate: invoice_hedder delivery columns
$colCheck2 = $db->getRow("SHOW COLUMNS FROM invoice_hedder LIKE 'delivery_status'");
if (!$colCheck2) {
    $db->insertRow("ALTER TABLE invoice_hedder ADD COLUMN `delivery_status` VARCHAR(20) NOT NULL DEFAULT 'PENDING' AFTER `invoice_h_status`", []);
    $db->insertRow("ALTER TABLE invoice_hedder ADD COLUMN `delivered_at`    DATETIME    DEFAULT NULL              AFTER `delivery_status`", []);
    $db->insertRow("ALTER TABLE invoice_hedder ADD COLUMN `delivered_by`    VARCHAR(100) DEFAULT NULL              AFTER `delivered_at`", []);
}

$invoice = $db->getRow('SELECT invoice_h_id, delivery_status FROM invoice_hedder WHERE invoice_h_id = ?', [$invoiceId]);
if (!$invoice) {
    redirectBack(0, 'Invoice not found.');
}
if (($invoice['delivery_status'] ?? 'PENDING') === 'DELIVERED') {
    redirectBack($invoiceId, 'Invoice is already delivered.');
}

// Validate that every batch-tracked line has a batch chosen
for ($i = 0; $i < count($detailIds); $i++) {
    $detailId = (int) ($detailIds[$i] ?? 0);
    if ($detailId <= 0) { continue; }
    $row = $db->getRow(
        'SELECT id.invoice_d_item_id, itm.batch_tracking, itm.item_name
         FROM invoice_details id
         JOIN item_master itm ON itm.item_id = id.invoice_d_item_id
         WHERE id.invoice_d_id = ? AND id.invoice_h_id = ?',
        [$detailId, $invoiceId]
    );
    if (!$row) { continue; }
    $bt = $row['batch_tracking'] ?? 'NONE';
    if (in_array($bt, ['BATCH', 'SERIAL'], true)) {
        $bid = (int) ($batchIds[$i] ?? 0);
        if ($bid <= 0) {
            redirectBack($invoiceId, 'Please select a batch for "' . $row['item_name'] . '".');
        }
    }
}

// Save batch_id per line
for ($i = 0; $i < count($detailIds); $i++) {
    $detailId = (int) ($detailIds[$i] ?? 0);
    if ($detailId <= 0) { continue; }
    $bidRaw = $batchIds[$i] ?? '';
    $bid = ($bidRaw === '' || $bidRaw === null) ? null : (int) $bidRaw;
    $db->updateRow(
        'UPDATE invoice_details SET batch_id = ? WHERE invoice_d_id = ? AND invoice_h_id = ?',
        [$bid, $detailId, $invoiceId]
    );
}

// Mark delivered
$deliveredBy = str_replace(',', '', $_SESSION['userid'] ?? '');
$deliveredAt = date('Y-m-d H:i:s');
$db->updateRow(
    'UPDATE invoice_hedder SET delivery_status = ?, delivered_at = ?, delivered_by = ? WHERE invoice_h_id = ?',
    ['DELIVERED', $deliveredAt, $deliveredBy, $invoiceId]
);

redirect('manage-invoices.php?message=' . urlencode('Invoice marked as Delivered successfully.') . '&type=success');
