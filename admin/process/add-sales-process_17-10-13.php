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

    return preg_replace('/ [^a-za-z0-9\s@.]/',' ' , $var);
}

$nowDate = date("Y-m-d");
$nowTime = date("h:i:s");
$nowDateTime = date("Y-m-d h:i:s");

//location name : online store : 
$invoice_location = 1;
?>

<?php

$delivery_holder = "";

$delivery_contact_no = "";

$delivery_date = "";
$invoice_h_status = false;
$transactionType = 2;

$order_note = "";



if(1==1){
  $message = "";
	$customer_id = $_POST['customer'];
  $order_note = $_POST['txtOrderNote'];
  $delivery_address = $_POST['delivery_address'];
  $delivery_time = $_POST['time'];
  $delivery_mode_id = $_POST["Delivery_Type"];
  $payment_type_id = $_POST["drpPaymethMethod"];
  $delivery_date = $_POST["delivery_date"];

  // location details
	$location_id   = $_SESSION["cityId"];
	$location_rate = $_SESSION["deliveryRate"];

  //discounts
  $coupon_code_type = $_POST['discount_type'];
  $discount_value_sum = $_POST['discount_value_sum'];
  $discount_value_precentange = $_POST['discount_value_precentage'];



   
  if($coupon_code_type == 2){

    $coupon_code_type = "PCT";
    $coupon_code_rate = $discount_value_precentange;
    $coupon_code = "POS_SYS";

  }else if($coupon_code_type == 3){

    $coupon_code_type = "SUM";
    $coupon_code_rate = $discount_value_sum;
    $coupon_code = "POS_SYS";
  }else{

    $coupon_code_type = "";
    $coupon_code_rate = "";
    $coupon_code = "";
  }



$delivery_date_order = explode('/',$delivery_date);


$delivery_month = $delivery_date_order[0];
$delivery_day = $delivery_date_order[1];
$delivery_year = $delivery_date_order[2];


$real_delivery_date = $delivery_year."-".$delivery_month."-".$delivery_day;





	//coupon code details
/*
if(!empty($_SESSION['coupon_id'])){
	$coupon_code_id = $_SESSION['coupon_id'];
	$coupon_code = $_SESSION['coupon_code'];
	$coupon_code_type = $_SESSION['coupon_type'];
	$coupon_code_rate = $_SESSION['coupon_rate'];

  $query_check_coupon = $db->getRow('SELECT * FROM coupon_codes WHERE code = ?',[$coupon_code]);

  $coupon_code_limit = $query_check_coupon['limited'];
  $new_coupon_code_limit = ($coupon_code_limit-1);

}else{

	$coupon_code = "";
	$coupon_code_type = "";
	$coupon_code_rate = "";
}*/
	


if($delivery_mode_id == 1){

  $name_title = "Name";

  $query_delevery_home_details = $db->getRow('SELECT * FROM shipping_address WHERE fk_customer_id = ? AND fk_delivery_method = ? ',[$customer_id,$delivery_mode_id]);
  $delivery_holder = $query_delevery_home_details['name'];
  $delivery_address = $query_delevery_home_details['address'];
  $delivery_real_city = $query_delevery_home_details['fk_city'];
  $delivery_contact_no = $query_delevery_home_details['contact_no'];

  $query_delevery_home_city_name = $db->getRow('SELECT * FROM city_master WHERE id = ?',[$delivery_real_city]);
  $delivery_real_city = $query_delevery_home_city_name['city'];


}else if($delivery_mode_id == 2){

  $name_title = "Company Name";

  $query_delevery_office_details = $db->getRow('SELECT * FROM shipping_address WHERE fk_customer_id = ? AND fk_delivery_method = ? ',[$customer_id,$delivery_mode_id]);
  $delivery_holder = $query_delevery_office_details['name'];
  $delivery_address = $query_delevery_office_details['address'];
  $delivery_real_city = $query_delevery_office_details['fk_city'];
  $delivery_contact_no = $query_delevery_office_details['contact_no'];

  $query_delevery_office_city_name = $db->getRow('SELECT * FROM city_master WHERE id = ?',[$delivery_real_city]);
  $delivery_real_city = $query_delevery_office_city_name['city'];

}else if($delivery_mode_id == 3){

  $name_title = "Name";
  $delivery_holder = "";
  $delivery_address = "";
  $delivery_real_city = $_SESSION["cityName"];
  $delivery_contact_no = "";
}else{

  $name_title = "Name";
  $delivery_holder = "";
  $delivery_address = "";
  $delivery_real_city = $_SESSION["cityName"];
  $delivery_contact_no = "";

}




	//payment method

	$payment_type_name = $_SESSION["paymentName"];


//parana id eka search karala aluth id ekak hadagannawa.
					$db = new Database();
					$getpid = $db->getRow('SELECT max(invoice_h_id) as invoice_h_id FROM invoice_hedder');
					$randomNo = rand(100,999);

					$oldpid = $getpid['invoice_h_id'];
					if($getpid > 0)
					{

					$newpid =  $oldpid + 1 ; 
					}

					// product code ekak hadagannawa
          $refaranceCode = str_pad($newpid, 5, '0', STR_PAD_LEFT);
					$refaranceCode = "INV".$refaranceCode;




		if(!empty($_SESSION['SBCScart'])){

				
					
					
                        
                        // Equal total to 0
                        $total = 0;
                        // For finding session elements line number
                        $linenumber = 0;
                        $i = 0;
                        
                        // Run loop for cart array 
                        foreach($_SESSION['SBCScart'] as $SBCSitem) 
                        {
                           // Don't list items with 0 qty
                            if($SBCSitem['quantity']!=0) {


                            $session_item_id = str_replace(",",".",$SBCSitem['item_id']); 
                           
                                
                            // We calculate total values with decimals
                            $pricedecimal = str_replace(",",".",$SBCSitem['price']); 
                            $qtydecimal = str_replace(",",".",$SBCSitem['quantity']); 
                            $get_item_discount = 0.00; 

                          	$pricedecimal = (float)$pricedecimal; 
                          	$qtydecimal = (float)$qtydecimal; 
                            $get_item_discount =(float)$get_item_discount; 

                            $discount_value = ($pricedecimal * ($get_item_discount * $qtydecimal))/100;

                            $totaldecimal = ($pricedecimal*$qtydecimal);
                            
                            $item_grand_total = ($totaldecimal - 0.00);
                                                         
                                
                            
                            // Write cart to screen
                           
                        
                            // Total
                     		$total += $item_grand_total;
                            
                            }
                         
                        }
                       $total ;


                      //location rate checking
                      ($total > 1000)? $location_rate = "0.00" : $location_rate = $_SESSION["deliveryRate"];

                      	
                      	//coupon code add value checking

                      if($coupon_code_type == "PCT"){

                     	$coupon_discount_value = (float)$total * (float)($coupon_code_rate/100); 
                      

                      }else if($coupon_code_type == "SUM"){

                      	$coupon_discount_value = $coupon_code_rate;
                      }else{

                      	$coupon_discount_value = "0.00";

                      }

                      //grand Total of the invoice
                     $invoice_grand_total = ($total-$coupon_discount_value) + $location_rate;

                     try {

                     	$insert_invoice_h = $db->insertRow('INSERT INTO invoice_hedder (invoice_h_code,invoice_h_customer_id,invoice_h_date,invoice_h_location,
                     	invoice_h_delivery_city,invoice_h_delivery_cost,invoice_h_delivery_mode,invoice_h_pay_type,
                      invoice_h_net_value,invoice_h_gross_value,
                     	invoice_h_order_note,invoice_h_delivery_name,invoice_h_delivery_address,invoice_h_delivery_contact_no,invoice_h_delivery_date,
                     	invoice_h_delivery_time,invoice_h_status,invoice_h_datetime,invoice_h_coupun_code,invoice_h_coupon_type,invoice_h_coupon_rate,invoice_h_coupon_value)VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',[$refaranceCode,$customer_id,
                     	$nowDate,$invoice_location,$location_id,$location_rate,$delivery_mode_id,$payment_type_id,
                     	$total,$invoice_grand_total,$order_note,$delivery_holder,$delivery_address,$delivery_contact_no,$real_delivery_date,
                     	$delivery_time,"0",$nowDateTime,$coupon_code,$coupon_code_type,$coupon_code_rate,$coupon_discount_value]);


                   
			
			$queryLastID = $db->getRow('SELECT 	* FROM invoice_hedder ORDER BY invoice_h_id DESC LIMIT 1');
 	    $getLastid = $queryLastID['invoice_h_id'];


 				$invoice_h_status = true;
 			
                     	
                     } catch (Exception $e) {
                     	
                      echo $e;
                     	
                     }

                        if($invoice_h_status == true){

                        $email_order_code = $queryLastID['invoice_h_code'];
                        $email_customer_id = $queryLastID['invoice_h_customer_id'];
                        $email_order_date = $queryLastID['invoice_h_date'];
                        $email_order_total = $queryLastID['invoice_h_gross_value'];
                        $email_delevery_name = $queryLastID['invoice_h_delivery_name'];
                        $email_delevery_address = $queryLastID['invoice_h_delivery_address'];



                        $to = "malith.sachinthana@gmail.com"; 
                        $subject="instagrocery New Order";
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
                        $body_message .= '<div style="width:100%; height:100%; border-bottom: 1px solid #73af7d;"><img src="https://instagrocery.lk/home/image/logo.png" style="margin-left: auto;
  margin-right: auto;
  display: block;"> </div><br><br>';
                        $body_message .= 'New order from instagrocery.lk. <br><br>';
                        $body_message .= 'Order number   :'.$email_order_code.' <br><br>';
                        $body_message .= 'Order Date : '.$email_order_date.'<br><br> ';
                        $body_message .= 'Contact Name : '.$email_delevery_name.'<br><br>';
                        $body_message .= 'Delivery Address :'.$email_delevery_address.'<br><br> ';
                        $body_message .= 'Payment Method :'.$payment_type_name.'<br><br> ';
                        $body_message .= '<p style="    text-align: center;
    font-weight: bold;
    font-size: 20px;"> Invoice Total : LKR '.$email_order_total.'</p>';
                        $body_message .= ' <p valign="middle" align="center" height="45" bgcolor="#feae39" style="font-size:17px; font-weight:bold; color:#ffffff; text-transform:uppercase;"><a href="https://instagrocery.lk/home/admin/invoice.php?id='.$getLastid.'" target="_blank" style="line-height: 45px;
    display: block;
    border-radius: 5px;
    width: 150px;
    background: #258a29;
    color: #ffffff;
    text-decoration: none;">VIEW MORE</a></p>';
                        $body_message .= '</body></html>';
                               
                           $mailsent = mail($to, $subject, $body_message, $headers);

                     }
                     





                     if($invoice_h_status = true && $getLastid){

                     	   // invoice Detials upload
                        foreach($_SESSION['SBCScart'] as $SBCSitem) 
                        {
                           // Don't list items with 0 qty
                            if($SBCSitem['quantity']!=0) {

                            $session_item_id = str_replace(",",".",$SBCSitem['item_id']); 
                           
                                
                            // We calculate total values with decimals
                            $pricedecimal = str_replace(",",".",$SBCSitem['price']); 
                            $qtydecimal = str_replace(",",".",$SBCSitem['quantity']); 
                            $get_item_discount = str_replace(",",".",0.00); 

                          	$pricedecimal = (float)$pricedecimal; 
                          	$qtydecimal = (float)$qtydecimal; 
                            $get_item_discount =(float)$get_item_discount; 

                             $discount_value = ($pricedecimal * ($get_item_discount * $qtydecimal))/100;

                            $totaldecimal = ($pricedecimal*$qtydecimal);
                            
                            $item_grand_total = ($totaldecimal - 0.00);
                           
                            






                            try {

                            	// insert invoice Details
							$insertinvoice_d = $db->insertRow('INSERT INTO invoice_details (invoice_h_id,invoice_d_item_id,invoice_d_qty,invoice_d_balance,
							invoice_d_item_price,invoice_d_discount_value,invoice_d_discount_total,invoice_d_item_total) VALUES(?,?,?,?,?,?,?,?)',[$getLastid,
							$session_item_id,$qtydecimal,0.00,$pricedecimal,$get_item_discount,$discount_value,$item_grand_total]);

							 	$message ="Order Confirom";
						    $_SESSION['SBCScart'] = "";
							
                            	
                            } catch (Exception $e) {
                            	
                            	$message = '$insertType."<br>" . $e->getMessage()';

                            }




                            }
                         
                        }

                     }

                 
}



}
	


$output =  array('message' => $message,
                  'order_id' => $getLastid);

        echo json_encode($output,JSON_FORCE_OBJECT);



?>



