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

if($_POST['payment_id'])
{
	$payment_id = $_POST['payment_id'];

	$query_check_payment_id = $db->getRow('SELECT * FROM payment_method WHERE id = ?',[$payment_id]);
	$real_payment_id = $query_check_payment_id['id'];

	$real_payment_name = $query_check_payment_id['type'];


	if(!empty($real_payment_id) && $payment_id == $real_payment_id){

			$_SESSION["paymentId"] = $real_payment_id;
			$_SESSION["paymentName"] = $real_payment_name;
			
		


	}else{

		$message = "This payment id not found on our system";

	}


}
  $output =  array('message' => $message
               		);

        echo json_encode($output,JSON_FORCE_OBJECT);

?>



