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
$db = new Database();

$nowDate = date("Y-m-d");
$nowTime = date("h:i:s");
$nowDateTime = date("Y-m-d h:i:s");
$transactionType = 3;
$redirect_srn_note = false;


$add_by = str_replace(",", "",$_SESSION['userid']) ;
$location = $_SESSION['location'];

if(!empty($_POST['invoice']) && !empty($_POST['return_no']) && !empty($_POST['return_date'])){



	$invoice_h_id = $_POST['invoice'];
	$sales_return_no = $_POST['return_no'];
	$return_date = $_POST['return_date'];

	$return_date = $return_date." ".$nowTime;


	if(!empty($_POST['item_id']) && $_POST['qty'] > 0 && $transactionType == 3){

			$item_id = $_POST['item_id'];
		    $item_qty = $_POST['qty'];
		    $grand_tot = $_POST['grandTotTotHidden'];
		    $iten_name = $_POST['item_name'];

		    try {

		    $insert_product = $db->insertRow('INSERT INTO sales_return_hedder(sales_return_h_code,sales_return_h_invoice,sales_retrun_h_date,
			sales_return_user,sales_return_location,sales_return_process,sales_retrun_h_total) VALUES(?,?,?,?,?,?,?)',[$sales_return_no,$invoice_h_id,$return_date,$add_by,$location,1,$grand_tot]);
		    	
		    $sales_h_insert_status = true;

		    } catch (Exception $e) {

		    	$sales_h_insert_status = false;
		    			
		    	
		    }
		
		    if($sales_h_insert_status == true && $sales_h_insert_status != false){

		    			$queryLastID = $db->getRow('SELECT sales_return_h_id as sales_return_h_id FROM sales_return_hedder ORDER BY sales_return_h_id DESC LIMIT 1');
						$getLastid = $queryLastID['sales_return_h_id'];

						if($getLastid){

							for($i=0;$i<count($iten_name);$i++)
								{
					   				 $item_id = $_POST['item_id'][$i];
				  	   				 $quantity=$_POST['qty'][$i];
					    			 $price =$_POST['price'][$i];
					    			 $vat_rate = $_POST['vatRate'][$i];
									 $invoice_d_id = $_POST['item_d_id'][$i];
									 $item_grand_total1 = $_POST['itmGrandTot'][$i];

									 #check invoice item

									 $query_invoice_item = $db->getRow('SELECT * FROM `invoice_details` WHERE invoice_h_id = ? AND  invoice_d_item_id = ?',[$invoice_h_id,$item_id]);
						 			 $invoice_item_qty = $query_invoice_item['invoice_d_qty'];
								
						 			 	try {

						 			 		$query_insert_d = $db->insertRow('INSERT INTO sales_return_details(sales_return_d_h_id,sales_return_d_item_id,sales_return_d_qty,sales_return_d_invoice_item,sales_return_d_rate,sales_return_d_vat_rate,sales_return_d_tot) VALUES(?,?,?,?,?,?,?)',[$getLastid,$item_id,$quantity,$invoice_d_id,$price,$vat_rate,$item_grand_total1]);
						 			 		
						 			 		$return_sales_d_status = true;
						 			 		
						 			 	} catch (Exception $e) {

						 			 		$return_sales_d_status = false;
						 			 		
						 			 	}

						 			 	try {

						 			 		$insertfifo = $db->insertRow('INSERT INTO fifo(ft_location,ft_document,ft_item,ft_qty,ft_blanace,ft_rate,ft_date,ft_type) VALUES(?,?,?,?,?,?,?,?)',[$location,$getLastid,$item_id,0.00,$quantity,$price,$nowDateTime,$transactionType]);
						 			 		
						 			 		$return_sales_fifo_status = true;
						 			 	} catch (Exception $e) {
						 			 		
						 			 		$return_sales_fifo_status = false;
						 			 	}

						 			 			if($sales_h_insert_status == true && $return_sales_d_status == true && $return_sales_fifo_status == true){

						 			 					$message_h = "Updated";
	    												$message_d = "successfully updated";
														$message_theme = "lime";
														$redirect_srn_note = true;

														$query_srn_h_new_id = $db->getRow('SELECT sales_return_h_id as sales_return_h_id FROM sales_return_hedder ORDER BY sales_return_h_id DESC LIMIT 1');
   														$get_new_srn_id = $query_srn_h_new_id['sales_return_h_id'];
   														
						 			 			}else{

						 			 					$message_h = "Insert Error";
	    												$message_d = "this recode did not update.";
														$message_theme = "ruby";


						 			 			}
						 			 			


								}

						}


		    }else{


		    			$message_h = "Database Error!";
	    				$message_d = "You should Wait for few minutes...";
						$message_theme = "tangerine";

		    }





	}else{

		$message_h = "Empty Fields!";
	    $message_d = "Please Add item to the cart";
		$message_theme = "tangerine";

	}


}else{

$message_h = "Empty Fields!";
$message_d = "Can not found some infomation";
$message_theme = "tangerine";


}


$output = array('message_h' => $message_h,
				'message_d' => $message_d,
				'message_theme' => $message_theme,
				'redirect_srn_note' => $redirect_srn_note,
				'new_srn_id' => $get_new_srn_id);




echo json_encode($output,JSON_FORCE_OBJECT);

?>



