<?php
session_start();
require_once '../include/database.php';

$username = isset($_POST['email']) ? trim((string) $_POST['email']) : '';
$password = isset($_POST['password']) ? (string) $_POST['password'] : '';

 $upass_sha1 = sha1($password);
  $status = 0;


if(!empty($username) && !empty($password)){

     $db = new Database();
   $getRow = $db->getRow('SELECT * FROM customer WHERE customer_email = ? AND customer_password = ?',[$username , $password]);

  if($getRow)
  {


           $user_name = $getRow['customer_name'];
           $user_email = $getRow['customer_email'];
           $activated = (int) ($getRow['is_active'] ?? $getRow['customer_activated'] ?? 0);
           $locked = (int) ($getRow['locked'] ?? $getRow['customer_locked'] ?? 0);
           $userid = $getRow['customer_id'];
     

     // store values to session
    $_SESSION['Loginuserid'] = $userid;
    $_SESSION['Loginusername']= $user_name;
    $_SESSION['Loginemail']= $user_email;
    $_SESSION['Loginpassword']= $password;
    $_SESSION['Loginactivated']= $activated;
    $_SESSION['Loginlocked']= $locked;
    $_SESSION['LoginStatus']="login_success";
  
    
    
    if($activated === 1 && $locked === 0)
    {

        $message = " Please wait... now you're redirecting..";
        $message_title = "Success";
        $status = 1;
        $class="alert alert-success";
    }
    else if($locked === 1)
    {

      $message = " Your account has been locked. please contact office department ";
      $message_title = "Locked!";
      $class="alert alert-warning";

    }
    else
    {

      $message = " Your account is not activated yet. Please use the verification link from your email.";
      $message_title = "Not Activated";
        $class="alert alert-warning";

    }


  }
else
{


    $message = " Username or password incorrect.";
    $message_title = "Incorrected!";
    $class="alert alert-danger";
}

}else{


}

  $output =  array('status' => $status,
                 'message' => $message,
                 'message_title' => $message_title,
                 'class' => $class);

        echo json_encode($output,JSON_FORCE_OBJECT);

?>