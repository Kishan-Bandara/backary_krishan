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

if($_POST['customer_id'])
{
	$payment_id = $_POST['customer_id'];

	$query_check_payment_id = $db->getRow('SELECT * FROM customer WHERE customer_id = ?',[$payment_id]);
	$real_payment_id = $query_check_payment_id['customer_id'];

	$real_payment_name = $query_check_payment_id['customer_name'];


	if(!empty($real_payment_id) && $payment_id == $real_payment_id){

			$_SESSION["customerid"] = $real_payment_id;
			$_SESSION["customername"] = $real_payment_name;
			
		


	}else{

		$message = "This payment id not found on our system";

	}


}
  $output =  array('message' => $message
               		);

        echo json_encode($output,JSON_FORCE_OBJECT);

?>



