<?php
ob_start();
error_reporting (E_ALL ^ E_NOTICE);

session_start();
include('../include/database.php');
include('../include/check_login.php');


function filter($var)
{

    return preg_replace('/ [^a-za-z0-9\s@.]/',' ' , $var);
}

?>

<?php
$db = new database();
$old_password = $_POST['oldPassword'];
$new_password = $_POST['newPassword'];
$new_password_1 = $_POST['ConfiromPassword'];


	if($new_password == $new_password_1){

		$query_cehck_password = $db->getRow('SELECT * FROM customer WHERE customer_id = ? AND customer_password = ?',[$_SESSION['Loginuserid'],$old_password]);
		$real_customer_id = $query_cehck_password['customer_id'];

		if($real_customer_id == $_SESSION['Loginuserid']){
			

			try {
				
				$query_update_password = $db->updateRow('UPDATE customer SET customer_password = ? WHERE customer_id = ?',[$new_password,$real_customer_id]);
				$message = "Password has been changed";

			} catch (Exception $e) {
				
				$message = $e;
			}


		}else{

			$message = "You have no permission";
		}




	}else{

		$message = "password did not matched";
	}

	echo $message;
?>