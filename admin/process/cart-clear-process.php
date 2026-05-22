<?php
session_start();


unset($_SESSION['SBCScart']);
unset($_SESSION["delivery_address"]);
unset($_SESSION["customerid"]);
unset($_SESSION["cityId"]);
unset($_SESSION["paymentId"]);
unset($_SESSION["temp_cart_code"]);
echo "Cart has been cleared!";

?>



