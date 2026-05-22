<?php
ob_start();
session_start();
require_once '../include/database.php';
function filter($var)
{

    return preg_replace('/ [^a-za-z0-9\s@.]/',' ' , $var);
}

 $username = filter($_POST['username']);
 $password = filter($_POST['password']);
 $location = filter($_POST['location']);
 $upass_sha1 = sha1($password);


    


   $db = new Database();
   $getRow = $db->getRow('SELECT * FROM users WHERE username = ? AND password = ?',[$username , $password]);

  if($getRow > 0)
  {


      
           $userLevel = (int) $getRow['user_level'];
           $activated = $getRow['activated'];
           $locked = $getRow['locked'];
           $userid = $getRow['userid']; 
           $user_first_name = $getRow['first_name']; 
           $user_location_status = (int) $getRow['location_status']; 



    $location = (int) filter($_POST['location']);

    if ($userLevel !== 1) {
      $location = ($user_location_status > 0) ? $user_location_status : $location;
    } else {
      if ($location <= 0) {
        $location = ($user_location_status > 0) ? $user_location_status : 1;
      }
    }



     // store values to session
    $_SESSION['userid'] = $userid;
    $_SESSION['username']= $username;
    $_SESSION['password']= $password;
    $_SESSION['userlevel']= $userLevel;
    $_SESSION['activated']= $activated;
    $_SESSION['locked']= $locked;
    $_SESSION['Status']="login_success";
    $_SESSION['location'] = $location;
    $_SESSION['first_name'] = $user_first_name;
    

    if($activated == "Y" && $locked == "N")
    {

        $message = " Please Wait.....  You are now redirecting to control panel.";
        $message_title = "Welcome ".$user_first_name;
        $response = "ok";
    }
    else
    {

        $message = " Sorry! Your account has been locked. please contact office department ";
        $message_title = "Locked!";
        $response = "lock";
    }


  }
else
{


    $message = " Sorry! Username or password incorrect.";
    $message_title = "Incorrected!";
    $response = "error";
}



  $output =  array('message' => $message,
                    'message_title' => $message_title,
                    'response' => $response
                  );

        echo json_encode($output,JSON_FORCE_OBJECT);

?>



