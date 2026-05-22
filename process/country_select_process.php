<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('../include/database.php');

function filter($var)
{
    return preg_replace('/ [^a-za-z0-9\s@.]/', ' ', $var);
}

$db = new Database();

if (isset($_POST['contryId'])) {
    unset($_SESSION["delivery_Type"]);
    unset($_SESSION["cityRate"]);
    unset($_SESSION["deliveryId"]);
    
   $contry_id = $_POST['contryId'];
    $query_check_contry_id = $db->getRow('SELECT * FROM country WHERE pk_id = ?', [$contry_id]);
    $real_contry_id = $query_check_contry_id['pk_id'];

    $real_country_name = $query_check_contry_id['name'];
    if (!empty($real_contry_id) && $contry_id == $real_contry_id) {
        $_SESSION["countryId"] = $real_contry_id;
        $_SESSION["countryName"] = $real_country_name;
    }

    $check_Cities_query = $db->getRow('SELECT count(id) as count FROM city_master WHERE countryId = ?', [$contry_id]);
    if($check_Cities_query['count']>0){
        $_SESSION["is_Cities"] = true; 
    }else{
        $_SESSION["is_Cities"] = false; 
    }
}
