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

$item_id = $_POST['item_id'];
$item_qty = $_POST['item_qty'];
$item_price = $_POST['item_price'];
$invoice_id = $_POST['invoice_id'];
$qty_status = false;


#item name

$query_item_name = $db->getRow('SELECT * FROM item_master WHERE item_id = ?',[$item_id]);
$item_name = $query_item_name['item_name'];



#check invoice item

$query_invoice_item = $db->getRow('SELECT * FROM `invoice_details` WHERE invoice_h_id = ? AND  invoice_d_item_id = ?',[$invoice_id,$item_id]);
$invoice_d_id = $query_invoice_item['invoice_d_id'];
$invoice_item_qty = $query_invoice_item['invoice_d_qty'];
$invoice_item_price = $query_invoice_item['invoice_d_item_price'];
$invoice_item_vat_has = $query_invoice_item['invoice_d_vat'];
$invoice_item_vat_rate = $query_invoice_item['invoice_d_vat_rate'];


if($item_qty <= $invoice_item_qty){


	$qty_status = true;

}

	if($item_price > 0.00){


			$item_tot = $item_qty * $item_price;
			$item_vat_value = 0.00;
			$item_net_value = number_format((float)$item_tot,2, '.', '');

	}else{

			
		if($invoice_item_vat_has == "Y")
			{

				$invoice_item_vat_rate = $query_invoice_item['invoice_d_vat_rate'];

			}else{

					$invoice_item_vat_rate = "0.00";

			}

			$item_tot = $invoice_item_price * $item_qty;


			$item_vat_value = ($item_tot * $invoice_item_vat_rate)/100;

			$item_net_value = $item_tot+$item_vat_value;
			$item_net_value = number_format((float)$item_net_value, 2, '.', '');



	}



$output = array( 'item_name'=> $item_name,
				 'item_qty' => $item_qty,
				 'item_tot' => $item_tot,
				 'item_net_value' =>$item_net_value,
				 'item_price' => $invoice_item_price,
				 'item_vat_has' =>$invoice_item_vat_has,
				 'item_vat_rate' => $invoice_item_vat_rate,
				 'invoice_d_id' => $invoice_d_id,
				 'item_qty_status' => $qty_status);




echo json_encode($output,JSON_FORCE_OBJECT);



?>



