<?php
include('include/database.php');
$db = new Database();

echo "=== Invoice Details Check ===\n";

// Get the latest invoices
$invoices = $db->getRows('SELECT invoice_h_id, invoice_h_code, invoice_h_delivery_date FROM invoice_hedder WHERE invoice_h_order_note = "Standing Order" ORDER BY invoice_h_id DESC LIMIT 5');

foreach($invoices as $inv) {
    echo "\nInvoice: {$inv['invoice_h_code']} (ID: {$inv['invoice_h_id']}) - Date: {$inv['invoice_h_delivery_date']}\n";

    // Check invoice details for this invoice
    $details = $db->getRows('SELECT invoice_d_item_id, invoice_d_qty FROM invoice_details WHERE invoice_h_id = ?', [$inv['invoice_h_id']]);

    if (empty($details)) {
        echo "  ❌ No invoice details found!\n";
    } else {
        echo "  ✅ Found " . count($details) . " detail records:\n";
        foreach($details as $detail) {
            echo "    - Item ID: {$detail['invoice_d_item_id']}, Qty: {$detail['invoice_d_qty']}\n";
        }
    }
}

echo "\n=== Summary ===\n";
$totalInvoices = $db->getRow('SELECT COUNT(*) as count FROM invoice_hedder WHERE invoice_h_order_note = "Standing Order"');
$totalDetails = $db->getRow('SELECT COUNT(*) as count FROM invoice_details');

echo "Total Standing Order Invoices: " . ($totalInvoices ? $totalInvoices['count'] : 0) . "\n";
echo "Total Invoice Details: " . ($totalDetails ? $totalDetails['count'] : 0) . "\n";
?>



