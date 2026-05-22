<?php
echo "Starting test\n";
session_start();
// Set proper session variables for check_login.php
$_SESSION['Status'] = 'login_success';
$_SESSION['username'] = 'admin';
$_SESSION['first_name'] = 'Admin';
$_SESSION['password'] = 'password';
$_SESSION['userlevel'] = 'admin';
$_SESSION['activated'] = 'Y';
$_SESSION['locked'] = 'N';
$_SESSION['userid'] = 1;

echo "Session variables set\n";

// Simulate the JSON input
$_JSON_DATA = '{
    "customer_id": 1,
    "shipping_address_id": 1,
    "items": [{"item_id": 1, "qty": [1, 2, 3, 4, 5, 6, 7], "price": 10.00}],
    "delivery_amount": 5.00,
    "repeat_interval": 1,
    "repeat_unit": 2
}';

echo "Including save-standing-order.php\n";
include('process/save-standing-order.php');
echo "Included successfully\n";
?>



