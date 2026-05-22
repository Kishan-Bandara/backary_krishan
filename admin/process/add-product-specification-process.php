<?php
ob_start();
error_reporting (E_ALL ^ E_NOTICE);
session_start();
include('../include/database.php');
include('../include/check_login.php');
include('../get_url.php');

date_default_timezone_set("Asia/Colombo");
$db = new Database();
function filter($var)
{

    return preg_replace('/ [^a-za-z0-9\s@.]/',' ' , $var);
}


$status = false;
$message = "";
$productId =  $_POST['productId'];
$key = $_POST['specification_key'];
$value = $_POST['specification_value'];

if($_POST['specification_key'] && $_POST['specification_value']){


    try {
					
       $query = $db->insertRow('INSERT INTO item_specification (`product_id`,`key`,`value`) VALUES(?,?,?)',[$productId,$key,$value]);
        $message = "Specification Added!";
        $status = true;
    } catch (Exception $e) {

        $message = $e->getMessage();
        
    }


}else{


    $message = "you should need to fill all the details";

}


$output =  array(
    'status' => $status,
    'message' => $message

);

echo json_encode($output, JSON_FORCE_OBJECT);


?>



