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




if(!empty($_POST['payment_Type'])){

  

    $query = $db->getRow('SELECT * FROM payment_method WHERE id = ?',[$_POST['payment_Type']]);


    $_SESSION["paymentId"]  = $query['id'];
    $_SESSION["paymentName"]= $query['type'];



}else{


}
