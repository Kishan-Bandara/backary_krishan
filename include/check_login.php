<?php
//store session into variable

if(isset($_SESSION['LoginStatus']))
{
(isset($_SESSION["Loginemail"]))? $session_user_email = $_SESSION["Loginemail"]:$session_user_email="";
(isset($_SESSION["Loginpassword"]))? $session_user_password = $_SESSION["Loginpassword"]:$session_user_password="";
(isset($_SESSION["Loginactivated"]))? $session_user_activeed = $_SESSION["Loginactivated"]:$session_user_activeed="";
(isset($_SESSION["Loginlocked"]))? $session_user_locked = $_SESSION["Loginlocked"]:$session_user_locked="";
(isset($_SESSION["LoginStatus"]))? $session_user_status = $_SESSION["LoginStatus"]:$session_user_status="";
(isset($_SESSION["Loginuserid"]))? $session_user_id = $_SESSION["Loginuserid"]:$session_user_id=0;

//check in db account lock or not

$db = new Database();
    $session_user_check_status= $db->getRow('SELECT * FROM customer WHERE customer_id = ? ',[$session_user_id]);
    $session_user_real_activeted = (int) ($session_user_check_status['is_active'] ?? $session_user_check_status['customer_activated'] ?? 0);
    $session_user_real_locked = (int) ($session_user_check_status['locked'] ?? $session_user_check_status['customer_locked'] ?? 0);
    $session_user_real_name = $session_user_check_status['customer_name'] ?? '';

	if($session_user_status !== "login_success" || $session_user_real_locked == 1 || $session_user_real_activeted == 0){

	session_destroy();

		}


}else{


	

}




?>