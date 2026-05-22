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




if(!empty($_POST['city'])){

    $_SESSION["city"] = $_POST['city'];

    $query = $db->getRow('SELECT * FROM city_master WHERE id = ?',[$_POST['city']]);

    $area_id = $query['area'];
    $query2 = $db->getRow('SELECT * FROM delivery_area WHERE pk_id = ?',[$area_id]);
    if($query2['rate'] > 0){

        $_SESSION["cityRate"]  =  $query2['rate'];
        $_SESSION["CityPerKgRate"]  =  $query2['FirstKgRate'];
        $_SESSION["SetRateMinOrder"] = $query2['SetRateMinOrder'];

    }else{

        $_SESSION["cityRate"]  = $query['rate'];
        $_SESSION["CityPerKgRate"]  = $query['perKgRate'];
        $_SESSION["SetRateMinOrder"] = $query['SetRateMinOrder'];
    }

}else{

    $_SESSION["city"] = "";
    $_SESSION["cityRate"] = "";
    $_SESSION["CityPerKgRate"] = "";
}

?>