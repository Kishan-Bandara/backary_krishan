<?php
// Test script for standing order saving
session_start();
$_SESSION['Status'] = 'login_success';
$_SESSION['userid'] = 1;
$_SESSION['username'] = 'Test User';
$_SESSION['first_name'] = 'Test';
$_SESSION['password'] = 'test';
$_SESSION['userlevel'] = 1;
$_SESSION['activated'] = 'Y';
$_SESSION['locked'] = 'N';

require_once '../include/database.php';
require_once '../include/check_login.php';

// Simulate JSON POST data for a standing order
$jsonData = json_encode([
    'customer_id' => 1, // Assuming customer ID 1 exists
    'delivery_amount' => 5.00,
    'repeat_interval' => 0, // No repeat for this test
    'repeat_unit' => null,
    'items' => [
        [
            'item_id' => 1, // Assuming item ID 1 exists
            'mon_qty' => 2,
            'tue_qty' => 0,
            'wed_qty' => 1,
            'thu_qty' => 0,
            'fri_qty' => 2,
            'sat_qty' => 0,
            'sun_qty' => 0
        ]
    ]
]);

// Simulate php://input
file_put_contents('php://input', $jsonData);

// Include the save-standing-order.php file
ob_start();
include 'save-standing-order.php';
$response = ob_get_clean();

echo "Response: " . $response . "\n";

// Check if invoices were created
$invoices = $db->getRows("SELECT * FROM invoice_hedder WHERE invoice_h_order_note = 'Standing Order' ORDER BY invoice_h_id DESC LIMIT 5");
echo "\nRecent Standing Order Invoices:\n";
foreach($invoices as $inv) {
    echo "ID: {$inv['invoice_h_id']}, Date: {$inv['invoice_h_delivery_date']}, Customer: {$inv['invoice_h_customer_id']}\n";
}
?>



