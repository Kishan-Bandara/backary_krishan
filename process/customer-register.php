<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

session_start();
include(__DIR__ . '/../include/database.php');
function filter($var)
{

    return preg_replace('/ [^a-za-z0-9\s@.]/', ' ', $var);
}

function requestValue($key, $default = '')
{
    if (!isset($_POST[$key]) || is_array($_POST[$key])) {
        return $default;
    }

    return trim((string) $_POST[$key]);
}

function nullableDigits($value)
{
    $digits = preg_replace('/\D+/', '', (string) $value);
    return $digits !== '' ? $digits : null;
}

?>

<?php

$first_name = requestValue('firstname');
$last_name = requestValue('lastname');
$full_name = $first_name . " " . $last_name;
$email = requestValue('email');
$nic = requestValue('nic');
$landphone = requestValue('landline');
$mobile_no = requestValue('telephone');
$password = requestValue('password');
$confirm_password = requestValue('confirm');

$refer_email = requestValue('refer_email');

$home_name = requestValue('home_name');
$home_address = requestValue('home_address');
$home_city = requestValue('home_city');
$home_number = requestValue('home_telephone');


$company_name  = requestValue('office_name');
$company_address = requestValue('office_address');
$company_city = requestValue('office_city');
$company_number = requestValue('office_number');
$customerLandphone = nullableDigits($landphone);
$customerMobile = nullableDigits($mobile_no);
$db = new database();
$status = false;
$customer_register = false;
$office_address_status = false;
$home_address_status = false;
$email_message = "";
$email_title = "";
$message = "";
$Emailmessage = "";
$email_status = null;
$getLastid = 0;
  //general settings
  $query_general_settings = $db->getRow('SELECT * FROM general_settings LIMIT 1');
  $query_general_settings = is_array($query_general_settings) ? $query_general_settings : array();

  $SiteName = (string) ($query_general_settings['SiteName'] ?? 'Bakery Shop');
  $logo = site_url() . ltrim((string) ($query_general_settings['logo'] ?? ''), '/');
  $system_email = (string) ($query_general_settings['system_email'] ?? '');


  //store settings
  $query_store_settings = $db->getRow('SELECT * FROM location_master LIMIT 1');
  $query_store_settings = is_array($query_store_settings) ? $query_store_settings : array();

  $storeAdminEmail  = (string) ($query_store_settings['email'] ?? '');
  $storeAdminContactNo  = (string) ($query_store_settings['phone_no'] ?? '');


$active_code = md5(rand(0, 1000) . rand(0, 1000) . rand(0, 1000) . rand(0, 1000) . rand(0, 1000));



if (!empty($first_name) && !empty($last_name) && !empty($email) && !empty($password)) {

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address";
    } elseif (!empty($confirm_password) && $password !== $confirm_password) {
        $message = "Passwords do not match";
    } else {

    $query_email = $db->getRow('SELECT * FROM customer WHERE customer_email = ?', [$email]);

    $real_email = is_array($query_email) ? (string) ($query_email['customer_email'] ?? '') : '';

    if ($real_email != $email && empty($real_email)) {


        if (!empty($refer_email)) {

            $query = $db->insertRow('INSERT INTO refer_email(refer_by_email,refer_To_email)VALUES(?,?)', [$refer_email, $email]);
        }


        try {

            $query_customer_reg = $db->insertRow('INSERT INTO customer (customer_email, customer_name, customer_nic, customer_mobile, customer_tell, customer_avtive_code, is_active, locked, customer_password, new_customer) 
			VALUES(?,?,?,?,?,?,?,?,?,?)', [$email, $full_name, $nic, $customerMobile, $customerLandphone, $active_code, 0, 0, $password, 1]);

            $queryLastID = $db->getRow('SELECT customer_id FROM customer WHERE customer_email = ? ORDER BY customer_id DESC LIMIT 1', [$email]);

            $getLastid = (int) ($queryLastID['customer_id'] ?? 0);

            $customer_register = true;
        } catch (Exception $e) {
            $message = "Unable to create account right now";
        }
    } else {

        $message = "This email is already registered";
    }

    }

    if ($customer_register == true) {

        $status = true;
        $message = "Account Created.";
        $email_message = "Your account has been created. please check your email and click on the verification link";
        $email_title = "Account Activation";


        $to = $email;
        $subject = "purebeautyitaly Verification Email";
        $from = "account@purebeautyitaly.com";

        $Activation_url = site_url() . "account_activate.php?id=" . $getLastid . "&verification_code=" . $active_code;

       
        $siteUrl = site_url();
        if (!defined('DEMO')) {
            define("DEMO", false);
        }
        $template_file = "../emails/CustomerRegistration.php";
        $email_to = $email;
        $subject = $SiteName ." ". $email_title;

        $swap_var = array(
          "{LOGO}" => $logo,
          "{SITE_NAME}" => $SiteName,
          "{CUSTOMER_EMAIL}" => $email,
          "{ACTIVATION_URL}" => $Activation_url,
          "{STORE_EMAIL}" =>  $storeAdminEmail,
          "{STORE_CONTACTNO}" => $storeAdminContactNo
        );

        $headers = "From: " . $SiteName . " <" . $system_email . ">\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

                if (file_exists($template_file)) {
                    $Emailmessage = file_get_contents($template_file);
                } else {
                    $Emailmessage = '<p>Your account has been created.</p><p>Please verify your email using this link: <a href="' . htmlspecialchars($Activation_url, ENT_QUOTES, 'UTF-8') . '">Activate Account</a></p>';
                }

        foreach (array_keys($swap_var) as $key) {
          if (strlen($key) > 2 && trim($key) != "")
            $Emailmessage = str_replace($key, $swap_var[$key], $Emailmessage);
        }



                if (!empty($email_to) && mail($email_to, $subject, $Emailmessage, $headers)) {
                    $email_status = "success";
                    $email_title = "Account Activation";
                    $email_message = "Your account has been created. Please check your email for an activation link.";
                } else {
                    $email_status = "failed";
                    $message = "Account created, but verification email sending failed. Please check your inbox and spam folder, or contact support.";
                    if ($getLastid > 0) {
                        $db->updateRow('UPDATE customer SET is_active = 1 WHERE customer_id = ?', [$getLastid]);
                    }
                    $email_title = "Account Created";
                    $email_message = "Your account was created and activated automatically because the verification email could not be sent right now.";
                }


    } else {
    }
} else {

    $message = "Some required fields are missing";
}
$output =  array(
    'status' => $status,
    'message' => $message,
    'email_message' => $email_message,
    'email_title' => $email_title,
    'email_status'=>$email_status
);

echo json_encode($output, JSON_FORCE_OBJECT);

?>