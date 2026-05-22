<?php 
ob_start();
error_reporting (E_ALL ^ E_NOTICE);
session_start();
include('../include/database.php');
include('../include/check_login.php');
date_default_timezone_set("Asia/Colombo");
function filter($var)
{

    return preg_replace('/ [^a-za-z0-9\s@.]/',' ' , $var);
}
?>

<?php
$nowDate = date("Y-m-d");
$nowTime = date("h:i:s");
$nowDateTime = date("Y-m-d h:i:s");

$message = "";
$submitDate = "";
$payment_method = "";
$amount = "";
$checkRef= "";
$CardRef= "";
$invoice_h_id = "";
//form eke textbox values variable ekakata assign karagannawa

 $submitDate = $_POST['date'];
 $refNo = filter($_POST['refNo']);
 $payment_method = $_POST['payment_method'];
 $amount = $_POST['amount'];
 $checkRef = $_POST['txtChequeRef'];
 $CardRef = $_POST['txtCardRef'];
 $invoice_h_id = $_POST['invoiceID'];
 $date = $_POST['date'];
 $user = $_SESSION['userid'];
 $datetime = $date." ".$nowTime;

if(!empty($submitDate) && !empty($refNo) && !empty($payment_method) && !empty($amount))
{
		$checkrefNo = $db->getRow('SELECT * FROM customer_balance WHERE  code = ? ',[$refNo]);

			if($checkrefNo > 0)
				{

					$message = "Referance Number Already Exist";
						}	
				else
				 {
								try {
		   
		   $db = new Database();
		   $insertamount = $db->insertRow('INSERT INTO customer_balance(code,invoice_h_id,amount,amountDate,invoice_h_pay_type,invoice_h_check_Ref,invoice_h_card_Ref,makeBy) VALUES(?,?,?,?,?,?,?,?)',
		   [$refNo,$invoice_h_id,$amount,$datetime,$payment_method,$checkRef,$CardRef,$user]);
		   $message = "Amount ".$amount." Added";
		  

		  
		   } catch (PDOEException $e) {
		       
		      $message= '$insertcustomer."<br>" . $e->getMessage()';
		   }
				 		

								}				

}
else
{

	$message = "Please fill in the information";

}

echo $message; 

?>



