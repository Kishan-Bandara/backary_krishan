<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

session_start();
include('../include/database.php');


date_default_timezone_set("Asia/Colombo");
$db = new Database();
function filter($var)
{

    return preg_replace('/[^A-Za-z0-9\-]/', ' ', $var);
}
$title = "";
$message = "";
$status = false;

$nowDate = date("Y-m-d");
$nowTime = date("h:i:s");
$nowDateTime = date("Y-m-d h:i:s");
$_SESSION["TotalAmount"] = 0;

(isset($_SESSION["deliveryRate"])) ? $delivery_rate = $_SESSION["deliveryRate"] : $delivery_rate = 0.00;
(isset($_SESSION["coupon_type"])) ? $coupon_type = $_SESSION["coupon_type"] : $coupon_type = "";
(isset($_SESSION["coupon_rate"])) ? $coupon_rate = $_SESSION["coupon_rate"] : $coupon_rate = 0.00;
(isset($_SESSION["coupon_value"])) ? $coupon_value = $_SESSION["coupon_value"] : $coupon_value = 0.00;
(isset($_SESSION["cityName"])) ? $delivery_city = $_SESSION["cityName"] : $delivery_city = "";
(isset($_SESSION["deliveryId"])) ? $delivery_mode = $_SESSION["deliveryId"] : $delivery_mode = 0;
(isset($_SESSION["delivery_area_price"])) ? $delivery_min_value = $_SESSION["delivery_area_price"] : $delivery_min_value = 0;
(isset($_SESSION["paymentId"])) ? $payment_type = $_SESSION["paymentId"] : $payment_type = 0;
(isset($_SESSION["Loginuserid"])) ? $customer_id = $_SESSION["Loginuserid"] : $customer_id = 0;

(isset($_SESSION["item_weight"])) ? $deliveryWeight = $_SESSION["item_weight"] : $deliveryWeight = 0;
(isset($_SESSION["cityRate"])) ? $cityRate = $_SESSION["cityRate"] : $cityRate = 0;
(isset($_SESSION["CityPerKgRate"])) ? $CityPerKgRate = $_SESSION["CityPerKgRate"] : $CityPerKgRate = 0;
(isset($_SESSION["countryId"])) ? $real_contry_id = $_SESSION["countryId"] : $real_contry_id = 0;
(isset($_SESSION["city"])) ? $city = $_SESSION["city"] : $city = 0;

$Subtotal = 0;
if (!empty($_SESSION['SBCScart'])) {
    $total = 0;
    $totalWeight = 0;
    $linenumber = 0;
    $i = 0;

    foreach ($_SESSION['SBCScart'] as $SBCSitem) {
        $i = $i + 1;

        if ($SBCSitem['quantity'] != 0) {

            $session_item_id = str_replace(",", ".", $SBCSitem['item_id']);
            $pricedecimal = str_replace(",", ".", $SBCSitem['price']);
            $qtydecimal = str_replace(",", ".", $SBCSitem['quantity']);
            $get_item_discount = str_replace(",", ".", $SBCSitem['item_discount']);
            $getItemWeight = $SBCSitem['item_weight'];
            $pricedecimal = (float) $pricedecimal;
            $qtydecimal = (float) $qtydecimal;
            $get_item_discount = $get_item_discount;
            $discount_value = ($pricedecimal * ($get_item_discount * $qtydecimal)) / 100;
            $totaldecimal = $pricedecimal * $qtydecimal;
            $totaldecimal = $totaldecimal-$discount_value;

            // Total
            $Subtotal += $totaldecimal;
            $totalWeight += $getItemWeight;
        }
        $linenumber++;
    }
} else {

    $Subtotal = 0;
}

$TotalAmounText = $pricedecimal;
//coupon code check

if (!empty($coupon_value)) {

    $coupon_value = $coupon_value;
} else {
    $coupon_value = 0.00;
}
$SubTotalwithCouponCode = $Subtotal - $coupon_value;

//Delivery/Shipping Charges Add

$get_dispatch_type_query = $db->getRow('SELECT DispatchType FROM country WHERE pk_id = ?', [$real_contry_id]);
if ($get_dispatch_type_query > 0) {
    $dispatch_type = $get_dispatch_type_query['DispatchType'];
} else {
    $dispatch_type = "";
}

$DispatchDisplayTitle = "";
$shipping_description = "";
$DispatchAmount = 0;
$dispatch_value = 0;


if ($delivery_mode) {
    $get_shippingCharges_query = $db->getRow('SELECT * FROM shipping_method WHERE id = ?', [$delivery_mode]);
} else {
    $get_shippingCharges_query = $db->getRow('SELECT * FROM shipping_method WHERE active = ? AND DispatchType = ?', [1, $dispatch_type]);
}

if ($dispatch_type == "Ship") {

    $DispatchDisplayTitle = "Shipping Charges";
    $shipping_description = $get_shippingCharges_query['title'] . "-" . $get_shippingCharges_query['provider'] . "-" . $get_shippingCharges_query['attribute1'];
    $dispatch_value = $get_shippingCharges_query['rate'];
    $_SESSION["cityRate"] = $get_shippingCharges_query['rate'];
} else if ($dispatch_type == "Delivery") {
    $DispatchDisplayTitle = "Delivery Charges";
    $shipping_description = "";
    if (!empty($city)) {
        $query = $db->getRow('SELECT * FROM city_master WHERE id = ?', [$city]);
        $area_id = $query['area'];
        $query2 = $db->getRow('SELECT * FROM delivery_area WHERE pk_id = ?', [$area_id]);
        if ($query['rate'] > 0) {
            if ($query['SetRateMinOrder'] < $SubTotalwithCouponCode) {
                $_SESSION["cityRate"]  =  $query['rate'];
            } else {
                $_SESSION["cityRate"]  =  0.00;
            }
        } else {
            if ($query2['SetRateMinOrder'] < $SubTotalwithCouponCode) {
                $_SESSION["cityRate"]  = $query2['rate'];
            } else {
                $_SESSION["cityRate"]  =  0.00;
            }
        }
        $dispatch_value = $_SESSION["cityRate"];
    } else {

        $_SESSION["city"] = "";
        $_SESSION["cityRate"] = 0;
    }
} else {
}


$TotalAmount = $SubTotalwithCouponCode + $dispatch_value;

/*if($delivery_mode != 1){

    $diliveryvalueWithCityRate = 0.00;
    $_SESSION["cityRate"] = 0.00;
}else{
    $diliveryvalueWithCityRate = currency($dispatch_value);
}
*/
$diliveryvalueWithCityRate = currency($dispatch_value);

$SubTotalwithCouponCodeDisplay = currency($SubTotalwithCouponCode);
$SubtotalDisplay = currency($Subtotal);
$TotalAmounText = currency($TotalAmount);
$_SESSION["TotalAmount"] = (float) filter_var($TotalAmounText, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION ) ; // float(55.35) 

$output =  array(
    'SubTotal' => $SubtotalDisplay,
    'SubWithCupon' => $SubTotalwithCouponCodeDisplay,
    'deliveryCharge' => $diliveryvalueWithCityRate,
    'TotalAmount' => $TotalAmounText,
    'DispatchDisplayTitle' => $DispatchDisplayTitle,
    'shipping_description' => $shipping_description,
    'dispatch_value' => $totaldecimal

);

echo json_encode($output, JSON_FORCE_OBJECT);
