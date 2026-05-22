<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

session_start();
include('../include/database.php');


date_default_timezone_set("Asia/Colombo");
$db = new Database();
function filter($var)
{

	return preg_replace('/[^a-za-z0-9\s@.]/', ' ', $var);
}
$title = "";
$message = "";
$status =false;

$nowDate = date("Y-m-d");
$nowTime = date("h:i:s");
$nowDateTime = date("Y-m-d h:i:s");
$barcode_id = $_POST['item_id'] ?? null;
$get_item_quantity = $_POST['quantity'] ?? 0;
$qty_status  = false;
if ($barcode_id) {



	try {

		$query_check_barcode_item = $db->getRow('SELECT * FROM item_master WHERE item_id = ?', [$barcode_id]);
		$get_item_id = $query_check_barcode_item['item_id'] ?? null;
		$get_item_name =  $query_check_barcode_item['item_name'] ?? '';
		$get_item_image =  $query_check_barcode_item['item_image'] ?? '';
		$get_item_price =  $query_check_barcode_item['item_normal_selling_price'] ?? 0;
		$get_item_vat_has =  $query_check_barcode_item['item_vat'] ?? 'N';
		$get_item_code =  $query_check_barcode_item['item_code'] ?? '';
		$get_item_discount = $query_check_barcode_item['item_discount'] ?? 0;
		$imagepath = $query_check_barcode_item['imageParth'] ?? '';
		$itemWeight = $query_check_barcode_item['item_weight'] ?? 0;

		if (empty($get_item_discount)) {

			$get_item_discount = 0.00;
		}
		if ($get_item_vat_has == "Y") {

			$query_get_vat = $db->getRow('SELECT * FROM product_vat_master ORDER BY id DESC LIMIT 1');
			$get_item_vat_value = $query_get_vat['rate'];
		} else {

			$get_item_vat_value = "0.00";
		}
	} catch (Exception $e) {

		$message = 'Message:' . $e->getMessage();
	}

	if ($get_item_id) {

		$query_check_location = $db->getRow('SELECT SUM(ft_blanace) as qty , ft_location FROM fifo  WHERE ft_location = 1 AND ft_type = 1 AND ft_blanace > 0 AND ft_item = ?', [$get_item_id]);
	    $get_item_location = $query_check_location['ft_location'] ?? 0;
	    $get_real_item_qty = $query_check_location['qty'] ?? 0;




		if (!empty($_SESSION['SBCScart'])) {

			
			$total = 0;
		
			$linenumber = 0;
			$i = 0;

			
			foreach ($_SESSION['SBCScart'] as $SBCSitem) {
				$i = $i + 1;
				
			
				if ($SBCSitem['quantity'] != 0) {
					

					if ($SBCSitem['quantity'] < $get_real_item_qty) {

						$qty_status = false;
					} else {
						$qty_status = true;
					}
				} else {

					$qty_status = true;
				}
			}
		} else {

			 $qty_status = true;
		}






		if ($get_real_item_qty >= $get_item_quantity) {
			

			if ($get_item_location) {




			$message = "found";
				/////////////////////////////////////
				// Add item to cart
				/////////////////////////////////////
				if (empty($barcode_id) || empty($get_item_id) || empty($get_item_name) || $get_item_price === '' || $get_item_price === null) {

					$message = "Item Found but Some values are missing";
				} else {



					$title = "Item was added in your cart!";
					$message = $get_item_name." was added to your shopping cart.";
					$status = true;
					# Take values
					$get_item_id;
					$get_item_name;
					$get_item_price;
					$get_item_vat_has;
					$get_item_vat_value;
					$get_item_quantity;
					$uniquid = rand();


					$SBCSexist = false;
					$SBCScount = 0;

					// If SESSION Generated?
					if (isset($_SESSION['SBCScart'])) {
						// Look for item
						foreach ($_SESSION['SBCScart'] as $SBCSproduct) {
							// Yes we found it
							if ($get_item_id == $SBCSproduct['item_id']) {
								$SBCSexist = true;
								break;
							}
							$SBCScount++;
						}
					}

					// If we found same item
					if ($SBCSexist) {

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
							'uniquid' => $uniquid,
							'item_image' => $get_item_image,
							'image_path'=>$imagepath,
							'item_discount' => $get_item_discount,
							'item_weight' => $itemWeight
						);

						// If session not exist, create
						if (!isset($_SESSION['SBCScart']))
							$_SESSION['SBCScart'] = array();

						// Add item to cart
						$_SESSION['SBCScart'][] = $SBCSmycartrow;

						$title = "Item was added in your cart!";
						$message = $get_item_name." item has added to the cart Successfully! If you wish to shop more, click on Continue";
						$status = true;
					}
				}
			} else {

				$message = "There're no item in found. if you need this item please contact us our Sales Agent via  Call or Live chat.";
				$title = "No found!";
						
						
			}
		} else {

			$title = "Product Out of Stock";
			$message = " This product out of stock. Please call our hotline for order this product";
		}
	} else {


		$message = $barcode_id . " " . "No Found!";
		$title = "Processing Error!";
	}
} else {

	$title = "Processing Error!";
	$title = "Worng Barcode number";
}




$output =  array(
	'title' => $title,
	'message' => $message,
	'status' => $status
   );



echo json_encode($output,JSON_FORCE_OBJECT);