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
$customer_id = $_POST['customer_id'];
$_SESSION['userid'];
$_SESSION['username'];

$status = 0;

$query_check = $db->getRow('SELECT * FROM coupon_codes WHERE code = ?',[$coupon_code]);


if($customer_id){


	$query_get_email = $db->getRow('SELECT * FROM refer_email WHERE pk_id = ?',[$customer_id]);

	$cusotmer_email = $query_get_email['refer_by_email'];


	$query_get_customer_name = $db->getRow('SELECT * FROM customer WHERE customer_email = ?',[$cusotmer_email]);

	$customer_name = $query_get_customer_name['customer_name'];

}


if($query_check['code'] == $coupon_code){


$message = "Please try to use another name";

}else{



	if($coupon_type == "SUM"){

			try {

		$query = $db->insertRow('INSERT INTO coupon_codes (code,type,rate,offer_value,limited) VALUES(?,?,?,?,?)',[$coupon_code,$coupon_type,$coupon_value,$coupon_min_value,$coupon_limit]);
		$message = "Coupon code added!";
		$update_query = $db->updateRow('UPDATE refer_email SET coupon = ? , add_by = ? , add_name = ? WHERE pk_id = ?',[$coupon_code,$_SESSION['userid'],$_SESSION['username'],$customer_id]);
		$status = 1;
	} catch (Exception $e) {
		
		$message = "please try again";
	}



	}else if($coupon_type == "PCT"){



			try {

		$query = $db->insertRow('INSERT INTO coupon_codes (code,type,rate,offer_value,limited) VALUES(?,?,?,?,?)',[$coupon_code,$coupon_type,$coupon_value,$coupon_min_value,$coupon_limit]);
		$message = "Coupon code added!";
		$update_query = $db->updateRow('UPDATE refer_email SET coupon = ? WHERE pk_id = ?',[$coupon_code,$customer_id]);
		$status = 1;
	} catch (Exception $e) {
		
		$message = "please try again";
	}






	}else{

		$message = "Coupon Type did not matched";

	}

	if($status == 1){



						$to = $cusotmer_email; 
                        $subject="instagrocery Coupon Code For You";
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
                 <td style="background-color:#51a42d;color:#ffffff;font-size:8px;padding:10px 0 10px 20px">
                     <h1 style="margin:0;text-align:center;">Thanks From Instagrocery Team</h1>
                 </td>
             </tr>
             <tr>
                 <td style="padding:30px 50px 15px">
                    
                       <span style="color:#505050;font-size:14px;font-weight:300">
                      Hello,
                     </span>
                     <span style="color:#fe6400;font-size:15px;font-weight:600">
                     
                          '.$customer_name.'
                      
                      </span>
                   
                 </td>
             </tr>
             <tr>
                 <td>
                     <table cellpadding="0" cellspacing="0" style="padding:25px 50px;width:100%;background-color:#f5f5f5">
                     <thead>
                     	<p style="font-size:16px;text-align:center; font-weight:bold;"> Thank you for reffering your friend !<br>Your have been rewarded with a coupon value LKR 200/-  </p>
                     	<p style="font-size:14x; color:#fe6400;">instagrocery.lk is the latest e-supermarket where you can shop for all your grocery, beverages and household needs online.</p>
                     </thead>
                    <tbody>



                    <table border="0"  style="width:100%;background-color:#f5f5f5">
      
  

  <tr>

    <td  width="40%" style="color:#606060;font-size:14px;padding-bottom:10px">
    <p>Use this coupon code at the checkout</p>
    <p valign="middle" align="center" height="45" bgcolor="#feae39" style="font-size:17px; font-weight:bold; color:#ffffff; text-transform:uppercase;"><span style="line-height: 37px;
    display: block;
    border-radius: 5px;
    width: 136px;
    background: #258a29;
    color: #ffffff;
    text-decoration: none;">'.$coupon_code.'</span></p>
    <p>Minimum purchase value LKR 1000/-</p>
    </td>
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
<div style="margin-bottom:10px;font-size:12px; margin-top:30px;padding-left:30px;"> If you have any questions about your account or any other issues,please feel free to contact us.<br><br>
Email : support@instagrocery.lk <br><br>
Hotline : 0755 525500 | 0755 525444 | 0117 240250 

 </div>
 <div style="text-align:center;font-weight:bold; margin-bottom:10px;  font-size:14px;">Happy Shopping - instagrocery.lk </div>


</table>';
                               
                           $mailsent = mail($to, $subject, $body_message, $headers);


			
		}


	
}

echo $message;
?>



