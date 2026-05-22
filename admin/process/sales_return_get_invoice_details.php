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

if(!empty($_POST['invoice_id'])){

$invoice_id = $_POST['invoice_id'];
$query_get_h_details = $db->getRow('SELECT * FROM invoice_hedder WHERE invoice_h_id = ?',[$invoice_id ]);

$invoice_date = $query_get_h_details['invoice_h_date'];
$invoice_location  = $query_get_h_details['invoice_h_location'];
$invoice_pay_type = $query_get_h_details['invoice_h_pay_type'];
$invoice_id = $query_get_h_details['invoice_h_id'];
$invoice_customer_id = $query_get_h_details['invoice_h_customer_id'];


$query_customer_id = $db->getRow('SELECT * FROM customer WHERE customer_id = ?',[$invoice_customer_id]);
$customer_name = $query_customer_id['customer_name'];


$query_invoice_location = $db->getRow('SELECT * FROM location_master WHERE 	id = ?',[$invoice_location]);
$location_name = $query_invoice_location['name'];


$query_invlice_pay_type = $db->getRow('SELECT * FROM payment_method WHERE id = ?',[$invoice_pay_type]);
$payment_type = $query_invlice_pay_type['type'];

#item loop
 $invoice_item = '';  

 $query = $db->getRows('SELECT invoice_d_item_id FROM invoice_details WHERE invoice_h_id = ?',[$invoice_id]);
 $data = $query;
 $invoice_item = '<option value="">Select Sub vote</option>';
foreach($data as $query) 
            {   
            	$inv_d_item_id = $query['invoice_d_item_id'];

            	$query_item_id = $db->getRow('SELECT * FROM item_master WHERE item_id = ?',[$inv_d_item_id]);
            	$item_name = $query_item_id['item_name'];

                
                $invoice_item .= '<option value="'.$query['invoice_d_item_id'].'">'.$item_name.'</option>';

            }
           




$output = array( 'invoice_date'=> $invoice_date,
				 'invoice_location' => $location_name,
				 'invoice_pay_type' => $payment_type,
				 'invoice_id' => $invoice_id,
				 'invoice_item' =>$invoice_item,
                 'invoice_customer' => $customer_name);




echo json_encode($output,JSON_FORCE_OBJECT);


}




?>



