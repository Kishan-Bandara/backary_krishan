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

$invoice_location = $_SESSION['location'];
$session_user_f_name =  $_SESSION['first_name'];
include('../include/price_helpers.php');
?>

<?php

$all_item_total_amount = 0;
$invoice_h_status = false;
$transactionType = 2;





  $message = "";
  $refaranceCode = $_POST['ref_Number'];
  $customer_id = $_POST['customer'];
  $payment_type_id = $_POST["drpPaymethMethod"];
  $drp_print_receipt = $_POST['drp_print_receipt'];


   $item_id = $_POST['item_id'];
   $item = $_POST['item_name'];
   $item_discount = $_POST['itemDiscount'];
   $item_qty = $_POST['qty'];
   $unit_price = $_POST['unit_price'];


  //discounts
  $coupon_code_type = $_POST['drpDiscounthMethod'];
  $discount_value_sum = $_POST['DiscountSUM_value'];
  $discount_value_precentange = $_POST['Discountprecentage_value'];

    $sub_total = $_POST['txtSubTotal'];
    $net_value = $_POST['netvalue'];
    
    
    $order_date = $_POST['order_date'];
	$orderdate = explode('T', $order_date);
	$order_date = $orderdate[0];
	$order_time   = $orderdate[1];
    $order_date_time = $orderdate[0] . " " . $orderdate[1];

  if($coupon_code_type == 2){

    $coupon_code_type = "PCT";
    $copun_code_rate = $discount_value_precentange;
    $coupn_code_value =  $sub_total*$copun_code_rate/100;
    $coupn_code_value = $coupn_code_value;

  }else if($coupon_code_type == 3){

    $coupon_code_type = "SUM";
    $copun_code_rate = $discount_value_sum;
    $coupn_code_value = $copun_code_rate;

  }else{

    $coupon_code_type = "";
    $copun_code_rate = "";
     $coupn_code_value = "0";
  }




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
                    //$refaranceCode = str_pad($newpid, 5, '0', STR_PAD_LEFT);
				//	$refaranceCode = "INV".$refaranceCode;


            if(!empty($item))
                   {

                      $check_code = $db->getRow('SELECT invoice_h_id FROM invoice_hedder WHERE invoice_h_code = ? ',[$refaranceCode]);

                     $ref_id_has = $check_code['invoice_h_id'];


                      if(empty($ref_id_has)){


                        $insert_inv_h = $db->insertRow('INSERT INTO invoice_hedder(invoice_h_code,invoice_h_customer_id,invoice_h_date,invoice_h_datetime,invoice_h_location,invoice_h_pay_type,invoice_h_coupon_type,invoice_h_coupon_rate,invoice_h_coupon_value
                        ,invoice_h_net_value,invoice_h_gross_value,invoice_h_status,add_by) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)',[$refaranceCode,$customer_id,$order_date,$order_date_time,$invoice_location,$payment_type_id,$coupon_code_type,$copun_code_rate,$coupn_code_value,$sub_total,$net_value,
                        1,$session_user_f_name]);

                        $queryLastID = $db->getRow('SELECT invoice_h_id as invoice_h_id FROM invoice_hedder ORDER BY invoice_h_id DESC LIMIT 1');
                       $getLastid = $queryLastID['invoice_h_id'];


                        if($getLastid){

                          for($i=0;$i<count($item);$i++)
                              {

                                 $item_name=$item [$i];
                                 $item_id = $_POST['item_id'] [$i];
                                
                                 $item_discount = $_POST['itemDiscount'] [$i];
                                 
                                 $item_qty = $_POST['qty'] [$i];
                                 
                                 // Enforce server-side price resolution (ignore client-submitted unit price)
                                 $unit_price = 0;
                                 $computedPrice = null;
                                 if (!empty($customer_id)) {
                                     $cust = $db->getRow('SELECT customer_price_type_id FROM customer WHERE customer_id = ? LIMIT 1', [$customer_id]);
                                     $ptype = $cust['customer_price_type_id'] ?? null;
                                     if ($ptype) {
                                         $mapped = getProductPriceMapping($item_id, (int)$ptype, $invoice_location, $db);
                                         if ($mapped !== null) {
                                             $computedPrice = (float)$mapped;
                                         }
                                     }
                                 }
                                 if ($computedPrice === null) {
                                     $row = $db->getRow('SELECT price FROM product_price_mapping WHERE product_id = ? AND location_id = ? LIMIT 1', [$item_id, $invoice_location]);
                                     if ($row && isset($row['price'])) { $computedPrice = (float)$row['price']; }
                                 }
                                 if ($computedPrice === null) {
                                     $row = $db->getRow('SELECT price FROM product_price_mapping WHERE product_id = ? AND location_id IS NULL LIMIT 1', [$item_id]);
                                     if ($row && isset($row['price'])) { $computedPrice = (float)$row['price']; }
                                 }
                                 if ($computedPrice === null) {
                                     $p = $db->getRow('SELECT item_normal_selling_price FROM item_master WHERE item_id = ? LIMIT 1', [$item_id]);
                                     $computedPrice = (float) ($p['item_normal_selling_price'] ?? 0);
                                 }
                                 $unit_price = $computedPrice;

                                 if($item_discount){

                                     $item_discount_value = ($unit_price * ($item_discount * $item_qty))/100;
                                 }else{

                                    $item_discount_value = 0;
                                 }
                                


                                 $item_total_amount = $item_qty*$unit_price;

                                $item_net_amount = $item_total_amount-$item_discount_value;

                                  $insert_inv_d = $db->insertRow('INSERT INTO invoice_details(invoice_h_id,invoice_d_item_id,invoice_d_qty,invoice_d_item_price,invoice_d_discount_value,invoice_d_discount_total,invoice_d_item_total)VALUES(?,?,?,?,?,?,?)',
                                  [$getLastid,$item_id,$item_qty,$unit_price,$item_discount,$item_discount_value,$item_net_amount]);


                                   //Fifo Updates

                              $query_get_qty_real = $db->getRow('SELECT SUM(ft_blanace) as qty FROM fifo WHERE ft_item = ? AND ft_type = 1 AND ft_location = ?',[$item_id,$_SESSION['location']]);
                              $real_qty = $query_get_qty_real['qty'];





                         if($real_qty >= $item_qty)
                          {
                            //radious the FIFO balance
                        
                            for($x=0; $x<$item_qty;$x++)
                            {

                              $query_get_fifo_balance_id = $db->getRow("SELECT * FROM fifo WHERE ft_item = ? AND ft_type = 1 AND ft_location = ? HAVING ft_blanace > 0 ORDER BY ft_date ASC",[$item_id,$_SESSION['location']]);
                              $fifo_old_id = $query_get_fifo_balance_id['ft_id'];
                              $fifo_old_balance = $query_get_fifo_balance_id['ft_blanace'];
                              $itemPurchasePrice = $query_get_fifo_balance_id['ft_rate'];
                                  $fifo_new_balance = ($fifo_old_balance - 1);
                                  $fifo_new_balance_reset = ($fifo_old_balance + 1);

                                  if($fifo_new_balance >= 0){
                                
                                  try {

                                      $query_update_fifo_balance = $db->updateRow('UPDATE fifo SET ft_blanace = ? WHERE ft_id = ? AND ft_type = 1 AND ft_location = ?',[$fifo_new_balance,$fifo_old_id,$_SESSION['location']]);
                                      
                                  } catch (Exception $e) {
                                      

                                      $message= '$query_update_fifo_balance."<br>" . $e->getMessage()';
                                    }
                                  }
                            }


                            try {
                              
                              // insert FIFO
                              $insert_fifo = $db->insertRow('INSERT INTO fifo (ft_location,ft_document,ft_item,ft_qty,ft_rate,ft_date,ft_type) VALUES(?,?,?,?,?,?,?)',[$_SESSION['location'],$getLastid,$item_id,$item_qty,$itemPurchasePrice,$nowDateTime,$transactionType]);
                              
                           $message = "acepct";

                              $order_process_status = 1;
                              /*redirect('manage-orders.php');*/

                            } catch (Exception $e) {

                              $message= '$insert_fifo."<br>" . $e->getMessage()';
                              
                            }
                     
                          }
                          else
                          {

                            $message = "not enough quntity";
                            /*redirect('manage-orders.php');*/
                            
                          }




                          $all_item_total_amount+= $item_net_amount ;




                              }
                   $all_item_total_amount_with_net = $all_item_total_amount- $coupn_code_value;


                                  if($getLastid){

                                $query_update = $db->updateRow('UPDATE invoice_hedder SET invoice_h_gross_value = ? WHERE invoice_h_id = ?',[$all_item_total_amount_with_net,$getLastid]);
                              }
                              

                                if($payment_type_id == 6 && $getLastid){

                                  $query_insert_payment = $db->insertRow('INSERT INTO customer_balance (invoice_h_id,amount,amountDate,invoice_h_pay_type,makeBy) VALUES(?,?,?,?,?)',[$getLastid,$net_value,$nowDateTime,$payment_type_id,$session_user_f_name]);


                              }
                               
                                if($drp_print_receipt == 1){

                                  redirect('receipt.php?id='.$getLastid.'&print');
                                }else{

                                  redirect('receipt.php?id='.$getLastid);
                                }
                              
                         }
                      }else{
                          
                          echo "Please enter the anther Refferance number";
                      }




                   }




                   





/*$output =  array('message' => $message,
                  'order_id' => $getLastid);

        echo json_encode($output,JSON_FORCE_OBJECT);
*/


?>



