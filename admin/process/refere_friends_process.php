<?php
ob_start();
error_reporting (E_ALL ^ E_NOTICE);
session_start();
include('../include/database.php');
include('../include/check_login.php');
date_default_timezone_set("Asia/Colombo");



function filter($var)
{

    return preg_replace('/ [^a-za-z0-9\s@.]/',' ' , $var);
}

$nowDate = date("Y-m-d");
$nowTime = date("h:i:s");
$nowDateTime = date("Y-m-d h:i:s");

//location name : online store : 
$invoice_location = 1;
?>

<?php

$refer_f_name = $_POST['first_name'];
$refer_l_name = $_POST['last_name'];
$refer_email = $_POST['email'];
$customer_email = $_SESSION['Loginemail'];
$customer_name = $_SESSION['Loginusername'];


if($refer_f_name && $refer_email){

	 $to = "malith.sachinthana@gmail.com"; 
                        $subject="instagrocery New Refer";
                        $from = "noreply@instagrocery.lk";
                         
                        $headers  = 'MIME-Version: 1.0' . "\r\n";
                        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";


                        // Create email headers
                         $headers .= 'From: '.$from."\r\n".
                          'Reply-To: '.$from."\r\n" .
                          'X-Mailer: PHP/' . phpversion();


                        $body_message = '<htm> <body style="background-color: #efecec;
    padding: 20px;
    border-radius: 10px; width:450px; margin:auto;">';
                        $body_message .= '<div style="width:100%; height:100%; border-bottom: 1px solid #73af7d;"><img src="https://instagrocery.lk/image/logo.png" style="margin-left: auto;
  margin-right: auto;
  display: block;"> </div><br><br>';
                        $body_message .= 'instagrocery.lk New Refer. <br><br>';
                        $body_message .= 'Refered by   :'.$_SESSION['Loginusername'].' <br><br>';
                        $body_message .= 'Customer Email : '.$_SESSION['Loginemail'].'<br><br> ';
                        $body_message .= 'Refer to : '.$refer_f_name." ".$refer_l_name.'<br><br>';
                        $body_message .= 'Refer email address :'.$refer_email.'<br><br> ';
                      
                        $body_message .= '</body></html>';
                               
                           $mailsent = mail($to, $subject, $body_message, $headers);

                           $message = "Thank you for your co-oparation";









 						$to = $refer_email; 
                        $subject="instagrocery Refer to you";
                        $from = "noreply@instagrocery.lk";
                         
                        $headers  = 'MIME-Version: 1.0' . "\r\n";
                        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";


                        // Create email headers
                         $headers .= 'From: '.$from."\r\n".
                          'Reply-To: '.$from."\r\n" .
                          'X-Mailer: PHP/' . phpversion();


                        $body_message = '<table cellpadding="0" cellspacing="0" width="100%" style="background-color:#e0e0e0;font-family:arial;padding:10px 0">
    <tbody><tr>
        <td>
            <table cellpadding="0" cellspacing="0" style="margin:0 auto;max-width:700px;width:100%;background-color:#ffffff">
                
                <tbody><tr>
                    <td align="center" style="padding:10px 0">
                        
                        <img src="https://instagrocery.lk/image/email_logo.png">
                    </td>
                </tr>
                
                <tr>
                    <td>
 
    
    
        <table width="100%" cellpadding="0" cellspacing="0">
             <tbody><tr>
                 <td style="background-color:#1a9852;color:#ffffff;font-size:8px;padding:10px 0 10px 20px">
                     <h1 style="margin:0;text-align:center;">Welcome to Instagrocery</h1>
                 </td>
             </tr>
             <tr>
                 <td style="padding:30px 50px 15px">
                    
                       <span style="color:#505050;font-size:14px;font-weight:300">
                      Hello,
                     </span>
                     <span style="color:#7f4c19;font-size:15px;font-weight:600">
                     
                          '.$refer_f_name." ".$refer_l_name.'
                      
                      </span>
                   
                 </td>
             </tr>
             <tr>
                 <td>
                     <table cellpadding="0" cellspacing="0" style="padding:25px 50px;width:100%;background-color:#f5f5f5">
                     <thead>
                     	<p style="font-size:16px;text-align:center;"> Your friend <b>'.$customer_name.'</b> Referred you to Instagrocery.lk   </p>
                     	<p style="font-size:14x;">instagrocery.lk is the latest e-supermarket where you can shop for all your grocery, beverages and household needs online.</p>
                     </thead>
                    <tbody>



                    <table border="0"  style="width:100%;background-color:#f5f5f5">
      
  

  <tr>

    <td  width="20%" style="color:#606060;font-size:14px;padding-bottom:10px">
    <p valign="middle" align="center" height="45" bgcolor="#feae39" style="font-size:17px; font-weight:bold; color:#ffffff; text-transform:uppercase;"><a href="http://instagrocery.lk/register.php?refer_by='.$customer_email.'" target="_blank" style="line-height: 45px;
    display: block;
    border-radius: 5px;
    width: 150px;
    background: #258a29;
    color: #ffffff;
    text-decoration: none;">REGISTER NOW</a></p></td>
    <td  style="border-right: 1px solid #cecece; padding-right:20px; color:#202020;font-size:15px;font-weight:500;padding-bottom:10px" ></td>

    <td width="58%" rowspan="1" style="padding-left:40px;"> <span style="font-size:14px;color:303030">
    		<img src="http://instagrocery.lk/image/refer_friend.jpg" style="width:300px;">                   

                       </td>
  </tr>
  
</table>




                    
             
                <tr>
                    <td>
                       
                    </td>
                </tr>
            </tbody></table>
            
        </td>
    </tr>
</tbody> 
<div style="margin-left:15px; margin-right:15px;"><hr/> </div>
<div style="margin-bottom:10px;font-size:12px; margin-top:30px;padding-left:30px;"> If you have any questions about your account or any other matter,please feel free to contact us.<br>
Email : info@instagrocery.lk <br>
Hotline : 0755 525500 | 0755 525444 | 0117 240250 

 </div>
 <div style="text-align:center;font-weight:bold; margin-bottom:10px; font-family: cursive; font-size:14px;">Happy Shopping , instagrocery! </div>


</table>';
                               
                           $mailsent = mail($to, $subject, $body_message, $headers);





}else{

	$message = "Please enter the all details";
}



$output =  array('message' => $message
                 
                );

        echo json_encode($output,JSON_FORCE_OBJECT);

	




?>



