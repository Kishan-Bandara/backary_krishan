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
$transactionType = 2 ;

if(isset($_POST['sales']))
{
$RefNo = str_replace(",", "",$_POST['freNo']);
$invoiceDate = $nowDate;
$invoiceTime = $nowTime;
$customer = str_replace(",", "",$_POST['customer']);
$location = str_replace(",", "",$_POST['location']);
$item_id = str_replace(",", "",$_POST['item_id']);
$item_name =str_replace(",", "",$_POST['item_name']);
$item_qty =str_replace(",", "",$_POST['qty']) ;
$item_price = str_replace(",", "",$_POST['price']);
$item_sub_tot = str_replace(",", "",$_POST['txtSubTotal']) ;
$item_vat_tot = str_replace(",", "",$_POST['txtVatTotal']) ;
$net_value = str_replace(",", "",$_POST['netvalue']) ;
$payment_method = str_replace(",", "",$_POST['drpPaymethMethod']) ;
$order_Note = str_replace(",", "",$_POST['txtOrderNote']) ;
$Shipping_address = str_replace(",", "",$_POST['txtShippingAddress']) ;
$cheque_Ref = str_replace(",", "",$_POST['txtChequeRef']) ;
$card_Ref = str_replace(",", "",$_POST['txtCardRef']) ;
$add_by = str_replace(",", "",$_SESSION['userid']) ;
$vat_has = str_replace(",", "",$_POST['itmVat']) ;
$item_vat_rate= str_replace(",", "",$_POST['itmVatRate']) ;




	
	if(!empty($item_id) && $transactionType == 2)
	{
		#get Payment Status
				$query_get_payment_status = $db->getRow('SELECT * FROM payment_method WHERE id = ?',[$payment_method]);
				$payment_method_status = $query_get_payment_status['status'];
				$payment_method_status_name = $query_get_payment_status['type'];
				$payment_method_status_id = $query_get_payment_status['id'];



			if($payment_method_status == "Y")
			{

				$inv_status = 1;

				#inserting Invoice_H Values
				
				try {
				
			$insert_invoice_h = $db->insertRow('INSERT INTO invoice_hedder (invoice_h_code,invoice_h_customer_id,invoice_h_date,invoice_h_location,
			invoice_h_pay_type,invoice_h_net_value,invoice_h_vat_value,invoice_h_gross_value,invoice_h_check_Ref,invoice_h_card_Ref,invoice_h_order_note,invoice_h_shipping_address,invoice_h_status,add_by) 
			VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)',[$RefNo,$customer,$nowDateTime,$location,$payment_method,$net_value,$item_vat_tot,$item_sub_tot,$cheque_Ref,$card_Ref,$order_Note,$Shipping_address,$inv_status,$add_by]);
			
			$queryLastID = $db->getRow('SELECT 	invoice_h_id as invoice_h_id FROM invoice_hedder ORDER BY invoice_h_id DESC LIMIT 1');
 			$getLastid = $queryLastID['invoice_h_id'];
 			
 			$_SESSION['SBCScart'] = "";
 			
			} catch (Exception $e) {

				$message= '$insertType."<br>" . $e->getMessage()';
				
			}


				#Creating Payment Code
					$random_No = rand(10000,99999);
					$customer_balance_ref_no = "PAID-".$random_No.$getLastid;

			#inserting Customer Balance Values

				try {

				$insert_customer_balance = $db->insertRow('INSERT INTO customer_balance (code,invoice_h_id,amount,amountDate,invoice_h_pay_type,invoice_h_check_Ref,invoice_h_card_Ref,makeBy) VALUES(?,?,?,?,?,?,?,?)',[$customer_balance_ref_no,$getLastid,$net_value,$nowDateTime,$payment_method,$cheque_Ref,$card_Ref,$add_by]);
				
			} catch (Exception $e) {

				$message= '$insert_customer_balance"<br>" . $e->getMessage()';
				
			}
			
			#insert item-details
				if($getLastid)
				{
					
					for($i=0;$i<count($item_id);$i++) {

							
								

						$item_id_c = $item_id [$i];
				  	    $quantity_c = $item_qty[$i];
					    $price_c = $_POST['price'][$i];
						$tot_itm_value_c = (($quantity_c * $price_c)+$vat_value);
						$vat_has_c = $vat_has[$i];
						$vat_value_c = $item_vat_rate[$i];

						# set Vat to the Item
								if($vat_has_c =="Y")
								{

									$query_vat_value_id = $db->getRow('SELECT * FROM product_vat_master ORDER BY id DESC LIMIT 1');
									$vat_value = $query_vat_value_id['rate'];

								}
								else
								{

									$vat_value = "0.00";
								}

						//radious the FIFO balance
						for($x=0; $x<$quantity_c;$x++)
						{
							$query_get_fifo_balance_id = $db->getRow("SELECT *, DATE_FORMAT(ft_date,'%d/%m/%Y %T:%f') AS fifodate FROM fifo WHERE ft_item = ? AND ft_type = 1 AND ft_location = ? HAVING ft_blanace > 0 ORDER BY ft_date ASC",[$item_id_c,$_SESSION['location']]);
							echo $fifo_old_id = $query_get_fifo_balance_id['ft_id'];
							$fifo_old_balance = $query_get_fifo_balance_id['ft_blanace'];
					        $fifo_new_balance = ($fifo_old_balance - 1);
					        $fifo_new_balance_reset = ($fifo_old_balance + 1);

					        if($fifo_new_balance >= 0){
					    	
						    	try {

							    		$query_update_fifo_balance = $db->updateRow('UPDATE fifo SET ft_blanace = ? WHERE ft_id = ? AND ft_type = 1 AND ft_location = ?',[$fifo_new_balance,$fifo_old_id,$_SESSION['location']]);
							    		
							    } catch (Exception $e) {
							    		

							    		$message= '$insertType."<br>" . $e->getMessage()';
							    	}
					    		}
						}
						

					    
						// insert invoice details
					  
						try {
							
								// insert invoice Details
							$insertinvoice_d = $db->insertRow('INSERT INTO invoice_details (invoice_h_id,invoice_d_item_id,invoice_d_qty,invoice_d_balance,invoice_d_item_price,
						    invoice_d_vat,invoice_d_vat_rate,invoice_d_sales_rap) VALUES(?,?,?,?,?,?,?,?)',[$getLastid,$item_id_c,$quantity_c,$quantity_c,$price_c,$vat_has_c,$vat_value,$add_by]);

							 	// insert FIFO
							$insert_fifo = $db->insertRow('INSERT INTO fifo (ft_location,ft_document,ft_item,ft_qty,ft_blanace,ft_rate,ft_date,ft_type) VALUES(?,?,?,?,?,?,?,?)',[$location,$getLastid,$item_id_c,$quantity_c,0.00,$price_c,$nowDateTime,$transactionType]);
							
						} catch (Exception $e) {
							
							$message= '$insertType."<br>" . $e->getMessage()';
						}

					
				}
				if($payment_method_status == "N")
						{

							redirect('invoice.php?id='.$getLastid.'');
							
						}
						else
						{
							redirect('receipt.php?id='.$getLastid.'');
							
						}
			}

			}
			else
			{
						#CODE FOR PENDING ORDERS
				$inv_status = 0;
				try {
				
			$insert_invoice_h = $db->insertRow('INSERT INTO invoice_hedder (invoice_h_code,invoice_h_customer_id,invoice_h_date,invoice_h_location,
			invoice_h_pay_type,invoice_h_net_value,invoice_h_vat_value,invoice_h_gross_value,invoice_h_check_Ref,invoice_h_card_Ref,invoice_h_order_note,invoice_h_shipping_address,invoice_h_status,add_by) 
			VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)',[$RefNo,$customer,$nowDateTime,$location,$payment_method,$net_value,$item_vat_tot,$item_sub_tot,$cheque_Ref,$card_Ref,$order_Note,$Shipping_address,$inv_status,$add_by]);
			$invoice_header_insert_ok = true;
			$queryLastID = $db->getRow('SELECT 	invoice_h_id as invoice_h_id FROM invoice_hedder ORDER BY invoice_h_id DESC LIMIT 1');
 			$getLastid = $queryLastID['invoice_h_id'];
 			$invoice_added_status = true;
 			$_SESSION['SBCScart'] = "";
			} catch (Exception $e) {

				$message= '$insertType."<br>" . $e->getMessage()';
				
			}
			



			if($getLastid)
				{

					for($i=0;$i<count($item_id);$i++) {

							

						$item_id_c = $item_id [$i];
				  	    $quantity_c = $item_qty[$i];
					    $price_c = $_POST['price'][$i];
						$tot_itm_value_c = (($quantity_c * $price_c)+$vat_value);
						$vat_has_c = $vat_has[$i];
						$vat_value_c = $vat_value;

							
														# set Vat to the Item
								if($vat_has_c=="Y")
								{

									$query_vat_value_id = $db->getRow('SELECT * FROM product_vat_master ORDER BY id DESC LIMIT 1');
								$vat_value = $query_vat_value_id['rate'];

								}
								else
								{

									$vat_value = "0.00";
								}
						// insert invoice details
					  
						try {
							
								// insert invoice Details
							$insertinvoice_d = $db->insertRow('INSERT INTO invoice_details (invoice_h_id,invoice_d_item_id,invoice_d_qty,invoice_d_balance,invoice_d_item_price,
						    invoice_d_vat,invoice_d_vat_rate,invoice_d_sales_rap) VALUES(?,?,?,?,?,?,?,?)',[$getLastid,$item_id_c,$quantity_c,$quantity_c,$price_c,$vat_has_c,$vat_value,$add_by]);
							
							
						} catch (Exception $e) {
							
							$message= '$insertType."<br>" . $e->getMessage()';
						}

						

				}

				if($payment_method_status == "N")
						{

							redirect('invoice.php?id='.$getLastid.'');
						}
						else
						{
							redirect('receipt.php?id='.$getLastid.'');
						}
			}
				#CODE FOR PENDING ORDERS

			}

		

		}
	
	else
	{
			redirect('add-sales.php');
		

		}

}
else
	{
	
	redirect('add-sales.php');
		
	}




?>



