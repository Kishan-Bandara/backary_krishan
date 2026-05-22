<?php 
ob_start();
error_reporting (E_ALL ^ E_NOTICE);
session_start();
include('../include/database.php');
include('../include/check_login.php');
function filter($var)
{

    return preg_replace('/ [^a-za-z0-9\s@.]/',' ' , $var);
}
?>

<?php
$message = "";
$submitDate = "";
$payment_method = "";
$amount = "";
//form eke textbox values variable ekakata assign karagannawa

 $submitDate = $_POST['date'];
 $refNo = filter($_POST['refNo']);
 $payment_method = $_POST['payment_method'];
 $amount = str_replace(",", "",$_POST['amount']);
 $grn_h_id = $_POST['grnID'];
 $date = $_POST['date'];
  $user = $_SESSION['userid'];

if(!empty($submitDate) && !empty($refNo) && !empty($payment_method) && !empty($amount))
{
		$checkrefNo = $db->getRow('SELECT * FROM supplier_balance WHERE  code = ? ',[$refNo]);

			if($checkrefNo > 0)
				{

					$message = "Referance Number Already Exist";
						}	
				else
				 {
								try {
		   
		   $db = new Database();
		   $insertamount = $db->insertRow('INSERT INTO supplier_balance(code,grn_h_id,amount,amountDate,method,makeBy) VALUES(?,?,?,?,?,?)',[$refNo,$grn_h_id,$amount,$date,$payment_method,$user]);
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



