<?php 
session_start();
unset($_SESSION['coupon_id']);
unset($_SESSION['coupon_code']);
unset($_SESSION['coupon_type']);
unset($_SESSION['coupon_rate']);
unset($_SESSION['coupon_message']);
unset($_SESSION['coupon_display']);
unset($_SESSION['coupon_value']);

echo "Coupon code has been removed";



?>