<?php
ob_start();
error_reporting (E_ALL ^ E_NOTICE);

session_start();
include('../include/database.php');
include('../include/check_login.php');



function filter($var)
{

    return preg_replace('/ [^a-zA-Z0-9\s@.]/',' ' , $var);
}
date_default_timezone_set("Asia/Colombo");
$nowDateTime = date("Y-m-d h:i:s");
?>
<?php
$db = new database();
$name = filter($_POST['name']);
$email = filter($_POST['email']);
$nic = filter($_POST['nic']);
$telephone = filter($_POST['telephone']);
$mobilephone  = filter($_POST['mobilephone']);

if(!empty($name) && !empty($email) && !empty($nic) && !empty($telephone) && !empty($mobilephone)){

	try {

		$query = $db->updateRow('UPDATE customer SET customer_name = ? , customer_email = ? , customer_nic = ? , customer_tell = ? , customer_mobile = ? WHERE customer_id = ?',[$name,$email,$nic,$telephone,$mobilephone,$_SESSION['Loginuserid']]);
		$query2 = $db->updateRow('UPDATE bakup_customer SET customer_name = ? , customer_email = ? , customer_nic = ? , customer_tell = ? , customer_mobile = ? , customer_update_date = ? WHERE customer_id = ?',[$name,$email,$nic,$telephone,$mobilephone,$nowDateTime,$_SESSION['Loginuserid']]);
		$message = "Account Updated!";
		
	} catch (Exception $e) {
		
		$message = $e;

	}


}else{

	$message = "Please Fill the all detials";
}
echo $message;
?>