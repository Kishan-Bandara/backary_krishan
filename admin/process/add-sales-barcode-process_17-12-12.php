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

	try {

		$query_check_barcode_item = $db->getRow('SELECT * FROM item_master WHERE item_code = ?',[$barcode_id]);
		$get_item_id = $query_check_barcode_item['item_id'];
		$get_item_name =  $query_check_barcode_item['item_name'];
		$get_item_price =  $query_check_barcode_item['item_normal_selling_price'];
		$get_item_vat_has =  $query_check_barcode_item['item_vat'];
		$get_item_code =  $query_check_barcode_item['item_code'];
			if($get_item_vat_has == "Y")
				{

					$query_get_vat = $db->getRow('SELECT * FROM product_vat_master ORDER BY id DESC LIMIT 1');
					$get_item_vat_value = $query_get_vat['rate'];

				} else {
				
				$get_item_vat_value = "0.00";

				}



		
	} catch (Exception $e) {
		
		$message = 'Message:' .$e->getMessage();
	}

		if($get_item_id)
		{

			$query_check_location = $db->getRow('SELECT * FROM fifo  WHERE ft_location = ? AND ft_type = 1 AND ft_blanace > 0 AND ft_item = ?',[$_SESSION['location'],$get_item_id]);
			$get_item_location = $query_check_location['ft_location'];

			if($get_item_location)
			{


				$message = "found";
				/////////////////////////////////////
	// Add item to cart
	/////////////////////////////////////
	if (empty($barcode_id) || empty($get_item_id ) || empty($get_item_name) || empty($get_item_price)) 
	{ 

		$message = "Item Found but Some values are missing";


	} else {



		$message = "Product Added!";
		# Take values
		$get_item_id;
		$get_item_name;
		$get_item_price;
		$get_item_vat_has;
		$get_item_vat_value;
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
				if($get_item_id == $SBCSproduct['item_id']) {
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
			$_SESSION['SBCScart'][$SBCScount]['quantity'] += $get_item_quantity;
			
		} else {
		
			// If we do not found, insert new
			$SBCSmycartrow = array(
				'item' => $get_item_name,
				'item_id' => $get_item_id,
				'price' => $get_item_price,
				'quantity' => $get_item_quantity,
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




			} else {

				$message = "item not found on this Location";

			}


		} else {


			$message = $barcode_id." "."No Found!";

		}



} else {


	$message = "Worng Barcode number";


}


echo $message;


					
	?>



