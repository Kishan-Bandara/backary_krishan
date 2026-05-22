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
$db = new Database();

//location name : online store : 
$invoice_location = $_SESSION['location'];

if(!empty($_SESSION['temp_cart_code'])){

  $temp_cart_code = $_SESSION['temp_cart_code'];


  $check_temp_cart_query = $db->getRow('SELECT * FROM temp_cart WHERE temp_cart_code = ? AND cart_h_status = 1',[$temp_cart_code]);
  $old_inv_id = $check_temp_cart_query['cart_h_pk_id'];
  $old_temp_cart_code = $check_temp_cart_query['temp_cart_code'];


  if(!empty($old_temp_cart_code)){


    $temp_invoice_has = 1;
  }

}else{

  $temp_cart_code = "";
  $old_temp_cart_code = "";
  $temp_invoice_has = 0;

}






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






//parana id eka search karala aluth id ekak hadagannawa.
					
					$getpid = $db->getRow('SELECT max(cart_h_pk_id) as cart_h_pk_id FROM temp_cart');
					$randomNo = rand(100,999);

					$oldpid = $getpid['cart_h_pk_id'];
					if($getpid > 0)
					{

					$newpid =  $oldpid + 1 ; 
					}else{

						$newpid = 1;
					}

					// product code ekak hadagannawa
          $refaranceCode = $randomNo.$newpid;
					$refaranceCode = $refaranceCode;




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

                      	if($temp_invoice_has == 1){



                     try {


                      $update_invoice_h = $db->updateRow('UPDATE temp_cart SET cart_h_customer_id = ? , cart_h_delivery_city = ? , cart_h_pay_type = ? , temp_cart_discount_type = ? ,temp_cart_discount_rate = ? WHERE cart_h_pk_id = ?',[$customer_id,$location_id,$payment_type_id,$coupon_code_type,$coupon_code_rate,$old_inv_id]);


                   
       
                           
                             $getLastid = $old_inv_id;


                             $invoice_h_status = true;
      
                      
                     } catch (Exception $e) {
                      
                      echo $e;
                      
                     }




                        }else{



                     try {

                      $insert_invoice_h = $db->insertRow('INSERT INTO temp_cart (cart_h_customer_id,cart_h_datetime,cart_h_location,cart_h_delivery_city,cart_h_pay_type,temp_cart_discount_type,temp_cart_discount_rate,cart_h_status,temp_cart_code)VALUES(?,?,?,?,?,?,?,?,?)',[$customer_id,$nowDateTime,$invoice_location,$location_id,$payment_type_id,$coupon_code_type,$coupon_code_rate,1,$refaranceCode]);


                   
       
                             $queryLastID = $db->getRow('SELECT   * FROM temp_cart ORDER BY cart_h_pk_id DESC LIMIT 1');
                             $getLastid = $queryLastID['cart_h_pk_id'];


                             $invoice_h_status = true;
      
                      
                     } catch (Exception $e) {
                      
                      echo $e;
                      
                     }



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
                           
                            


                            if($temp_invoice_has == 1){


                              try {

                                                       // insert invoice Details
                                                       $updateinvoice_d = $db->updateRow('UPDATE temp_cart_details SET temp_car_d_qty = ? ,temp_car_d_item_price = ? WHERE temp_car_h_id = ? AND temp_car_d_item_id = ?',[$qtydecimal,$pricedecimal,$getLastid,$session_item_id]);

                                                       $message ="Order has been Hold";
                                                         $_SESSION['SBCScart'] = "";
              
                              
                                               } catch (Exception $e) {
                              
                                                     $message = '$updateinvoice_d."<br>" . $e->getMessage()';

                                         }




                            }else{




                                       try {

                                                       // insert invoice Details
                                                       $insertinvoice_d = $db->insertRow('INSERT INTO temp_cart_details (temp_car_h_id,temp_car_d_item_id,temp_car_d_qty,temp_car_d_item_price) VALUES(?,?,?,?)',[$getLastid,$session_item_id,$qtydecimal,$pricedecimal]);

                                                       $message ="Order has been Hold";
                                                         $_SESSION['SBCScart'] = "";
              
                              
                                               } catch (Exception $e) {
                              
                                                     $message = '$insertinvoice_d."<br>" . $e->getMessage()';

                                         }



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



