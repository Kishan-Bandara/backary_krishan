<?php 
ob_start();
error_reporting (E_ALL ^ E_NOTICE);
session_start();
include('../include/database.php');
include('../include/check_login.php');
include('../get_url.php');
function filter($var)
{

    return preg_replace('/ [^a-za-z0-9\s@.]/',' ' , $var);
}
?>

<?php
date_default_timezone_set("Asia/Colombo");
$message = "";
$nowDate = date("Y-m-d");
$nowTime = date("h:i:s");
$nowDateTime = date("Y-m-d h:i:s");
$transactionType = 2 ;
$db = new Database();
//form eke textbox values variable ekakata assign karagannawa
	if(1==1)
	{
		 $order_status = $_POST['status'];
 		 $invoice_h_id = $_POST['invoiceId'];

 		
 			    $query_invoice_h = $db->getRow('SELECT * FROM invoice_hedder WHERE invoice_h_id = ?',[$invoice_h_id]);
		 		$invoice_h_status = $query_invoice_h['invoice_h_status'];

 		

		 	if($order_status == 1 && $invoice_h_id)
		 	{

		 		
		 		$invoice_h_location = $query_invoice_h['invoice_h_location'];
		 		
		 		

		 			if($invoice_h_status==0)
		 			{
		 					
		 				function getContent() {
		 								$invoice_h_id = $_POST['invoiceId'];
									    $db = new Database();
									    $query_invoice_d = $db->getRows('SELECT * FROM invoice_details WHERE invoice_h_id = ?',[$invoice_h_id]);
									    return $query_invoice_d;

										}

										$data = getContent();
                                        foreach($data as $query_invoice_d)
                                         { 
										  $invoice_real_h_id = $query_invoice_d['invoice_h_id'];
										  $invoice_real_d_item = $query_invoice_d['invoice_d_item_id'];
										  $invoice_real_d_qty = $query_invoice_d['invoice_d_qty'];
										  $invoice_real_d_rate = $query_invoice_d['invoice_d_item_price'];


										  	$query_get_qty_real = $db->getRow('SELECT SUM(ft_blanace) as qty FROM fifo WHERE ft_item = ? AND ft_type = 1 AND ft_location = ?',[$invoice_real_d_item,$_SESSION['location']]);
											$real_qty = $query_get_qty_real['qty'];
												  		
													if($real_qty >= $invoice_real_d_qty)
													{
														//radious the FIFO balance
										  	
														for($x=0; $x<$invoice_real_d_qty;$x++)
														{

															$query_get_fifo_balance_id = $db->getRow("SELECT * FROM fifo WHERE ft_item = ? AND ft_type = 1 AND ft_location = ? HAVING ft_blanace > 0 ORDER BY ft_date ASC",[$invoice_real_d_item,$_SESSION['location']]);
															$fifo_old_id = $query_get_fifo_balance_id['ft_id'];
															$fifo_old_balance = $query_get_fifo_balance_id['ft_blanace'];
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
															$insert_fifo = $db->insertRow('INSERT INTO fifo (ft_location,ft_document,ft_item,ft_qty,ft_rate,ft_date,ft_type) VALUES(?,?,?,?,?,?,?)',[$invoice_h_location,$invoice_h_id,$invoice_real_d_item,$invoice_real_d_qty,$invoice_real_d_rate,$nowDateTime,$transactionType]);
															
															$message = "acepct";
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
		 								 }

		 										$query_order_confirom = $db->updateRow('UPDATE invoice_hedder SET invoice_h_status = ? , invoice_h_approve_date = ? WHERE invoice_h_id = ?',[1,$nowDateTime,$invoice_h_id]);
		 										
		 			}		
		 			else
		 			{

		 					$message = "Already set the action for this order!";
		 					/*redirect('manage-orders.php');*/
		 					

		 			}

		 		}
	
		 	elseif($order_status == -1 && $invoice_h_id)
		 	{
		 		$query_order_cancel = $db->updateRow('UPDATE invoice_hedder SET invoice_h_status = ? WHERE invoice_h_id = ?',[-1,$invoice_h_id]);
		 		$message = "order has been canceled!";
		 		/*redirect('manage-orders.php');*/
		 		

		 	}
		 	else
		 	{


		 		$message = "Empty Values!";
		 		/*redirect('manage-orders.php');*/
		 		
		 	}



	}
	echo $message; 
 $db->disconnect();

?>



