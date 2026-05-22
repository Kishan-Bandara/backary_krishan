<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

session_start();
include('../include/database.php');


date_default_timezone_set("Asia/Colombo");
$db = new Database();
function filter($var)
{

	return preg_replace('/ [^a-za-z0-9\s@.]/', ' ', $var);
}
$title = "";
$message = "";
$status =false;

$nowDate = date("Y-m-d");
$nowTime = date("h:i:s");
$nowDateTime = date("Y-m-d h:i:s");



if(!empty($_POST['delivery_Type'])){



    $query = $db->getRow('SELECT * FROM shipping_method WHERE id = ?',[$_POST['delivery_Type']]);


    $_SESSION["delivery_Type"]  = $query['id'];
    $_SESSION["deliveryId"]  = $query['id'];
    $_SESSION["addShipingCharges"]  = $query['addShipingCharges'];
}else{

   
}




?>