<?php
ob_start();
error_reporting (E_ALL ^ E_NOTICE);
session_start();
include('../include/database.php');
include('../include/check_login.php');
include('../get_url.php');

date_default_timezone_set("Asia/Colombo");
$db = new Database();
function filter($var)
{

    return preg_replace('/ [^a-za-z0-9\s@.]/',' ' , $var);
}

$nowDate = date("Y-m-d");
$nowTime = date("h:i:s");
$nowDateTime = date("Y-m-d h:i:s");
if(!empty($_POST['barcode'])){

	$barcode_id = $_POST['barcode'];
}



//$get_item_quantity = $_GET['itmQty'];

if(!empty($barcode_id)){


 $_SESSION['SBCScart']  = "";

	$query_get_hold_invoice = $db->getRow('SELECT * FROM temp_cart WHERE temp_cart_code = ? AND  cart_h_status = 1 AND cart_h_location = ?',[$barcode_id,$_SESSION['location']]);





	$invoice_h_id = $query_get_hold_invoice['cart_h_pk_id'];
	$invoice_h_code = $query_get_hold_invoice['temp_cart_code'];
	$invoice_customer_id = $query_get_hold_invoice['cart_h_customer_id'];
	$invoice_date = $query_get_hold_invoice['cart_h_datetime'];
	$invoice_city = $query_get_hold_invoice['cart_h_delivery_city'];
	$invoice_discount_type = $query_get_hold_invoice['temp_cart_discount_type'];
	$invoice_discount_rate = $query_get_hold_invoice['temp_cart_discount_rate'];
	$invoice_pay_type = $query_get_hold_invoice['cart_h_pay_type'];




		if(1 == 1)
		{

						
					if(1 == 1){


						function getProducts() {
							$barcode_id = $_POST['barcode'];
									    $db = new Database();
									  	$query_check_barcode_item = $db->getRow('SELECT * FROM temp_cart WHERE temp_cart_code = ? AND  cart_h_status = 1 AND cart_h_location = ?',[$barcode_id,$_SESSION['location']]);
									  
										$invoice_h_id = $query_check_barcode_item['cart_h_pk_id'];
										$invoice_h_code = $query_check_barcode_item['temp_cart_code'];

									    $query = $db->getRows('SELECT temp.temp_car_d_id , temp.temp_car_h_id , temp.temp_car_d_item_id , temp.temp_car_d_qty , temp.temp_car_d_item_price , temp.temp_car_d_discount_type ,
		temp.temp_car_d_discount_total , item.item_id , item.item_name , item.is_hamper, item.item_vat , item.item_code
		FROM temp_cart_details temp
		INNER JOIN  item_master item    ON  temp.temp_car_d_item_id = item.item_id WHERE temp.temp_car_h_id =  ?',[$invoice_h_id]);
									    return $query;
									}


								$data = getProducts();
                                      
                                        foreach($data as $query)
                                         { 
                                         	

                                         		try {

																	
														$get_item_id = $query['item_id'];
														$get_item_qty = $query['temp_car_d_qty'];
														$get_item_id = $query['item_id'];
														$get_item_name =  $query['item_name'];
														$get_item_price =  $query['temp_car_d_item_price'];
														$get_item_vat_has =  $query['item_vat'];
														$get_item_code =  $query['item_code'];
														$is_it_hamper = $query['is_hamper'];



																	
																} catch (Exception $e) {
																	
																	$message = 'Message:' .$e->getMessage();
																}
																										


                                       		$hamper_item[] = $query['item_id'];

                                       		 for($i=0;$i<count($hamper_item);$i++)
												{
														$get_item_id = $query['item_id'];
														$get_item_qty = $query['temp_car_d_qty'];
														$get_item_id = $query['item_id'];
														$get_item_name =  $query['item_name'];
														$get_item_price =  $query['temp_car_d_item_price'];
														$get_item_vat_has =  $query['item_vat'];
														$get_item_code =  $query['item_code'];
														$is_it_hamper = $query['is_hamper'];

													$hamper_item_id = $hamper_item [$i]; 

													$query_get_hamper_item = $db->getRow('SELECT * FROM item_master WHERE item_id = ?',[$hamper_item_id]);

													$query_get_inV_d = $db->getRow('SELECT * FROM temp_cart_details WHERE temp_car_h_id	= ? AND temp_car_d_item_id = ?',[$query_get_hold_invoice['cart_h_pk_id'],$get_item_id]);
																	


																	$get_item_name =  $query_get_hamper_item['item_name'];
																	
																	$get_item_vat_has =  $query_get_hamper_item['item_vat'];
																	$get_item_code =  $query_get_hamper_item['item_code'];
																	$is_it_hamper = $query_get_hamper_item['is_hamper'];
																	$get_item_price =  $query_get_inV_d['temp_car_d_item_price'];
																	$get_item_qty = $query_get_inV_d['temp_car_d_qty'];	
																		if($get_item_vat_has == "Y")
																			{

																				$query_get_vat = $db->getRow('SELECT * FROM product_vat_master ORDER BY id DESC LIMIT 1');
																				$get_item_vat_value = $query_get_vat['rate'];

																			} else {
																			
																			$get_item_vat_value = "0.00";

																			}



																			# Take values
	/*	$get_item_id;
		$get_item_name;
		$get_item_price;
		$get_item_vat_has;
		$get_item_vat_value;*/
		$get_item_quantity = 1;
		$uniquid = rand();

		
		$SBCSexist = false;
		$SBCScount = 0;
		
		// If SESSION Generated?
		if($_SESSION['SBCScart']!="")
		{
			// Look for item
			foreach($_SESSION['SBCScart'] as $SBCSproduct)
			{
				// Yes we found it
				if($hamper_item_id == $SBCSproduct['item_id']) {
					$SBCSexist = true;
					break;
				}
				$SBCScount++;
			}
		}
		
		// If we found same item
		if($SBCSexist)
		{
		
			// Update quantity
			// $_SESSION['SBCScart'][$SBCScount]['quantity'] = $get_item_qty;
			
		} else {
		
			// If we do not found, insert new
			$SBCSmycartrow = array(
				'item' => $get_item_name,
				'item_id' => $hamper_item_id,
				'price' => $get_item_price,
				'quantity' => $get_item_qty,
				'item_vat_has' => $get_item_vat_has,
				'item_vat_value' => $get_item_vat_value,
				'item_code' => $get_item_code,
				'uniquid' => $uniquid
			);
			
			// If session not exist, create
			if (!isset($_SESSION['SBCScart']))
				$_SESSION['SBCScart'] = array();

			// Add item to cart
			$_SESSION['SBCScart'][] = $SBCSmycartrow;

		
		}












												}



                                         }	








					}else{





					}


		} else {


			$message = $barcode_id." "."No Found!";

		}



} else {


	$message = "Worng Barcode number";


}


/*echo $message;*/


					
	?>



