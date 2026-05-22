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
$transactionType = 1 ;

if(isset($_POST['Purchase']))
{
$RefNo = $_POST['RefNo'];
$invoiceDate = $nowDate;
$invoiceTime = $nowTime;
$supplier = $_POST['supplier'];
$location = $_POST['location'];
$item_id = $_POST['item_id'];
$item = $_POST['item_name'];
$item_qnt = $_POST['qty'];
$gTotal = $_POST['grossTot'];
$net_value = $_POST['grandTotTotHidden'];
$vat_tot = $_POST['vatTotHidden'];
$invDate = $nowDateTime;
$supplier_invoice_code = $_POST['purchase_ref'];
$paymentMethod = $_POST['payment_method'];
$add_by = str_replace(",", "",$_SESSION['userid']) ;
	if(!empty($item) && $transactionType == 1)
	{
		//inserting GRN_HEDDER DETAILS
		$db = new Database();

		$insertproduct = $db->insertRow('INSERT INTO grn_hedder (grn_h_code,grn_h_supplier_id,grn_h_supplier_invoice_code,grn_h_date,grn_h_pay_type,grn_h_net_value,grn_h_vat_value,grn_h_gross_value,add_by,grn_h_location) VALUES(?,?,?,?,?,?,?,?,?,?)',[$RefNo,$supplier,$supplier_invoice_code,$nowDateTime,$paymentMethod,$net_value,$vat_tot,$gTotal,$add_by,$location]);
		$queryLastID = $db->getRow('SELECT grn_h_id as grn_h_id FROM grn_hedder ORDER BY grn_h_id DESC LIMIT 1');
 		$getLastid = $queryLastID['grn_h_id'];
			for($i=0;$i<count($item);$i++)
			{
					    $item_code = $item_id [$i];
				  	    $item_name=$item[$i];
				  	    $item_tax_rate = $_POST['itmVat'][$i]; 
			            $quantity=$item_qnt[$i];
					    $price=$_POST['price'][$i];
						$tot = ($quantity * $price);
			

	
   					$insertproduct = $db->insertRow('INSERT INTO grn_details (grn_h_id,grn_d_item_id,grn_d_qty,grn_d_blance,grn_d_rate,grn_d_total,grn_d_vat_rate) VALUES(?,?,?,?,?,?,?)',[$getLastid,$item_code,$quantity,$quantity,$price,$tot,$item_tax_rate]);		
  					$insertfifo = $db->insertRow('INSERT INTO fifo (ft_location,ft_document,ft_item,ft_qty,ft_blanace,ft_rate,ft_date,ft_type) VALUES(?,?,?,?,?,?,?,?)',[$location,$getLastid,$item_code,$quantity,$quantity,$price,$nowDateTime,$transactionType]);

  					 

			}


   					$query_grn_h_new_id = $db->getRow('SELECT grn_h_id as grn_h_id FROM grn_hedder ORDER BY grn_h_id DESC LIMIT 1');
   					$get_new_grn_id = $query_grn_h_new_id['grn_h_id'];
   					redirect('purchase_invoice.php?id='.$get_new_grn_id.'');
			
	}
	else
	{

		
		redirect('add-purchase.php');
	}

}

?> 



