<?php
ob_start();
error_reporting (E_ALL ^ E_NOTICE);
session_start();
include('../include/database.php');
include('../include/check_login.php');
include('../get_url.php');

date_default_timezone_set("Asia/Colombo");
function filter($var)
{

    return preg_replace('/ [^a-za-z0-9\s@.]/',' ' , $var);
}

$nowDate = date("Y-m-d");
$nowTime = date("h:i:s");
$nowDateTime = date("Y-m-d h:i:s");

$db = new Database();

if(!empty($_POST['item_id'])){

	$item_id = $_POST['item_id'];

	$query_item_details = $db->getRow('SELECT * FROM item_master WHERE item_id = ?',[$item_id]);

	$item_name = $query_item_details['item_name'];




	$output = array( 'item_name'=> $item_name);




echo json_encode($output,JSON_FORCE_OBJECT);

	}


?>



