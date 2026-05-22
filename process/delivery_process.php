<?php
ob_start();
error_reporting (E_ALL ^ E_NOTICE);

session_start();
include('../include/database.php');
function filter($var)
{

    return preg_replace('/ [^a-za-z0-9\s@.]/',' ' , $var);
}

?>

<?php 
$db = new Database();

if($_POST['delivery_id'])
{
	$delivery_id = $_POST['delivery_id'];

	$query_check_delivery_id = $db->getRow('SELECT * FROM delivery_master WHERE id = ?',[$delivery_id]);
	$real_delivery_id = $query_check_delivery_id['id'];

	$real_delivery_name = $query_check_delivery_id['method'];
	$real_delivery_rate = $query_check_delivery_id['rate'];

	if(!empty($real_delivery_id) && $delivery_id == $real_delivery_id){

			$_SESSION["deliveryId"] = $real_delivery_id;
			$_SESSION["deliveryName"] = $real_delivery_name;
			
		


	}else{

		$message = "This delivery id not found on our system";

	}


}
  $output =  array('message' => $message
               		);

        echo json_encode($output,JSON_FORCE_OBJECT);

?>