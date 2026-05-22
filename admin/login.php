<?php
ob_start();
error_reporting (E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
if(isset($_SESSION['Status']))
{
if($_SESSION['Status'] == "login_success" &&  $_SESSION['activated'] == "Y" && $_SESSION['locked'] == "N")
{
echo "<script type='text/javascript'>window.location.href = 'index.php';</script>";

}
}

/*
if(isset($_POST['sub']))
{


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


      
           $userLevel = $getRow['user_level'];
           $activated = $getRow['activated'];
           $locked = $getRow['locked'];
           $userid = $getRow['userid'];
     

     // store values to session
    $_SESSION['userid'] = $userid;
    $_SESSION['username']= $username;
    $_SESSION['password']= $password;
    $_SESSION['userlevel']= $userLevel;
    $_SESSION['activated']= $activated;
    $_SESSION['locked']= $locked;
    $_SESSION['Status']="login_success";
    $_SESSION['location'] = $location;
    
    if($activated == "Y" && $locked == "N")
    {

        header('Location:index.php');
    }
    else
    {

        $error = "Sorry!! your account has been locked. please contact office department ";
        $error_title = "Locked!";
    }


  }
else
{


    $error = "Sorry!! Username or password incorrect.";
    $error_title = "Incorrected!";
}


}*/

function load_location()  
 {  
      
      $output = '';  
      $db = new Database();
      $query = $db->getRows('SELECT * FROM location_master');
      $data = $query;
        foreach($data as $query) 
            {   

                $id = $query['supplier_id']; 
                $output .= '<option value="'.$query['id'].'">'.$query['name'].'</option>';

            }
            return $output;  
 }  

?>

<!DOCTYPE html>

<!--[if IE 8]> <html lang="en" class="ie8 no-js"> <![endif]-->
<!--[if IE 9]> <html lang="en" class="ie9 no-js"> <![endif]-->
<!--[if !IE]><!-->
<html lang="en">
    <!--<![endif]-->
    <!-- BEGIN HEAD -->

<head>
        <meta charset="utf-8" />
        <title>User Login </title>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta content="width=device-width, initial-scale=1" name="viewport" />
        <meta content="" name="description" />
        <meta content="" name="author" />
        <!-- BEGIN GLOBAL MANDATORY STYLES -->
        <link href="http://fonts.googleapis.com/css?family=Open+Sans:400,300,600,700&amp;subset=all" rel="stylesheet" type="text/css" />
        <link href="assets/global/plugins/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/global/plugins/simple-line-icons/simple-line-icons.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/global/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/global/plugins/uniform/css/uniform.default.css" rel="stylesheet" type="text/css" />
        <link href="assets/global/plugins/bootstrap-switch/css/bootstrap-switch.min.css" rel="stylesheet" type="text/css" />
        <!-- END GLOBAL MANDATORY STYLES -->
        <!-- BEGIN PAGE LEVEL PLUGINS -->
        <link href="assets/global/plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/global/plugins/select2/css/select2-bootstrap.min.css" rel="stylesheet" type="text/css" />
        <!-- END PAGE LEVEL PLUGINS -->
        <!-- BEGIN THEME GLOBAL STYLES -->
        <link href="assets/global/css/components.min.css" rel="stylesheet" id="style_components" type="text/css" />
        <link href="assets/global/css/plugins.min.css" rel="stylesheet" type="text/css" />
        <!-- END THEME GLOBAL STYLES -->
        <!-- BEGIN PAGE LEVEL STYLES -->
        <link href="assets/pages/css/login-2.min.css" rel="stylesheet" type="text/css" />
        <!-- END PAGE LEVEL STYLES -->
        <!-- BEGIN THEME LAYOUT STYLES -->
        <!-- END THEME LAYOUT STYLES -->
        <link rel="shortcut icon" href="favicon.ico" />
    <style>
    .login .content .form-control {
        background-color: #ffffff;
    background-image: none;
    border: 1px solid #999999;
    border-radius: 0;
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.075) inset;
    color: #333333;
    display: block;
    font-size: 14px;
    height: 34px;
    line-height: 1.42857;
    padding: 6px 12px;
    transition: border-color 0.15s ease-in-out 0s, box-shadow 0.15s ease-in-out 0s;
    width: 100%;
}

.login .content .forget-form, .login .content .login-form {
    padding: 0;
    margin: 0;
    padding: 10px;
    background: white;
}
    </style>
    </head>
    <!-- END HEAD -->

    <body class=" login" style="background-color:#090023;">
        <div id="message"></div>
      
        <div class="logo" style="  margin: 1px auto 0; ">
            <a href="">
                <img src="assets/layouts/layout/img/logo.avif" alt="" /> </a>
        </div>
        <!-- END LOGO -->
        <!-- BEGIN LOGIN -->
        <div class="content" style="    margin: 1px auto;">
            <!-- BEGIN LOGIN FORM -->
            <form class="login-form" id="login_form" method="POST">
           <!--      <div class="form-title">
                    <span class="form-title"><center><?php  $error_title; ?></center></span> <br>
                    <span class="form-title" style="font-size:14px;text-align:center;"><?php  $error ;?></span>
                </div> -->
                <div class="alert alert-danger display-hide">
                    <button class="close" data-close="alert"></button>
                    <span> Enter any username and password. </span>
                </div>
                <div class="form-group">
                    <!--ie8, ie9 does not support html5 placeholder, so we just show field title for that-->
                    <label class="control-label visible-ie8 visible-ie9">Username</label>
                    <input class="form-control form-control-solid placeholder-no-fix" type="text" autocomplete="off" placeholder="Username" name="username" value="admin"/> </div>
                <div class="form-group">
                    <label class="control-label visible-ie8 visible-ie9">Password</label>
                    <input class="form-control form-control-solid placeholder-no-fix" type="password" autocomplete="off" placeholder="Password" name="password" value="admin"/ > </div>
                    <div class="form-group" >
                                                <label ><strong style="color:white;">Select Location</strong></label>
                                                <select class="form-control" name="location" >
                                                   <?php echo load_location(); ?> 
                                                    
                                                </select>
                                            </div>
                <div class="form-actions">
                    <button type="submit" class="btn red btn-block uppercase" name="sub" id="btn-login" >Login</button>
                </div>
               
                
                
            </form>
            <!-- END LOGIN FORM -->
           
            

        </div>
       
        <!-- END LOGIN -->
        <!--[if lt IE 9]>
<script src="assets/global/plugins/respond.min.js"></script>
<script src="assets/global/plugins/excanvas.min.js"></script> 
<![endif]-->
        <!-- BEGIN CORE PLUGINS -->
        <script src="assets/global/plugins/jquery.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
   
</body>

</html>

<script>
$('document').ready(function()
{ 

  $(document).on('submit','#login_form', function()
    {

       var data = $(this).serialize();
    
   $.ajax({
    
   type : 'POST',
   url  : 'process/login-process.php',
   data : data,
   beforeSend: function()
   { 
    $("#error").fadeOut();
    $("#btn-login").html('sending ...');
   },
   success :  function(response)
      {  

        var jsonobj = JSON.parse(response);
     
             if(jsonobj.response =="ok"){
         
     setTimeout(' window.location.href = "index.php"; ',4000);
             $("#message").fadeIn(1000, function(){  
      $("#message").html('<div class="alert alert-dismissable alert-success"><i class="ion-close-round"></i>&nbsp; <div style="text-align:center;"><strong>'+jsonobj.message_title+'</strong><p>'+jsonobj.message+'</p></div><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button></div>');    
    
           $("#btn-login").html('<span class="glyphicon glyphicon-log-in"></span> &nbsp; Sign In');
         });


     }
     else if(jsonobj.response =="lock"){
         
      $("#message").fadeIn(1000, function(){  
      $("#message").html('<div class="alert alert-dismissable alert-warning"><i class="ion-close-round"></i>&nbsp; <div style="text-align:center;"><strong>'+jsonobj.message_title+'</strong><p>'+jsonobj.message+'</p></div><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button></div>');    
    
           $("#btn-login").html('<span class="glyphicon glyphicon-log-in"></span> &nbsp; Sign In');
         });
     }
     else{

        $("#message").fadeIn(1000, function(){      
          $("#message").html('<div class="alert alert-dismissable alert-danger"><i class="ion-close-round"></i>&nbsp; <div style="text-align:center;"><strong>'+jsonobj.message_title+'</strong><p>'+jsonobj.message+'</p></div><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button></div>');
           $("#btn-login").html('<span class="glyphicon glyphicon-log-in"></span> &nbsp; Sign In');
         });

     }
     }
   });
    return false;

    });


});

</script>



