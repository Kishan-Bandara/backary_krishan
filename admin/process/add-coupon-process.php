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

    return preg_replace('/[^0-9]/',' ' , $var);
}

$coupon_code = $_POST['coupon_code'];
$coupon_type = $_POST['type'];
$coupon_value = $_POST['value'];
$coupon_limit = filter($_POST['limit']);
$coupon_min_value = $_POST['minum_value'];

$query_check = $db->getRow('SELECT * FROM coupon_codes WHERE code = ?',[$coupon_code]);

if($query_check['code'] == $coupon_code){


$message = "Please try to use another name";

}else{


	if($coupon_type == "SUM"){

			try {

		$query = $db->insertRow('INSERT INTO coupon_codes (code,type,rate,offer_value,limited) VALUES(?,?,?,?,?)',[$coupon_code,$coupon_type,$coupon_value,$coupon_min_value,$coupon_limit]);
		$message = "Coupon code added!";
	} catch (Exception $e) {
		
		$message = "please try again";
	}



	}else if($coupon_type == "PCT"){

			try {

		$query = $db->insertRow('INSERT INTO coupon_codes (code,type,rate,offer_value,limited) VALUES(?,?,?,?,?)',[$coupon_code,$coupon_type,$coupon_value,$coupon_min_value,$coupon_limit]);
		$message = "Coupon code added!";
	} catch (Exception $e) {
		
		$message = "please try again";
	}


	}else{

		$message = "Coupon Type did not matched";

	}

echo $message;
	
}
?>



