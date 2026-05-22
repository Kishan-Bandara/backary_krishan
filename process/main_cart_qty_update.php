<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

session_start();
include('../include/database.php');


date_default_timezone_set("Asia/Colombo");
$db = new Database();
function filter($var)
{

	return preg_replace('/ [^a-za-z0-9\s@.]/', ' ', $var);
}

$nowDate = date("Y-m-d");
$nowTime = date("h:i:s");
$nowDateTime = date("Y-m-d h:i:s");
$barcode_id = $_POST['item_id'];
$get_item_quantity = $_POST['quantity'];
$qty_status  = false;
if ($barcode_id) {



	try {

		$query_check_barcode_item = $db->getRow('SELECT * FROM item_master WHERE item_id = ?', [$barcode_id]);
		$get_item_id = $query_check_barcode_item['item_id'];
		$get_item_name =  $query_check_barcode_item['item_name'];
		$get_item_image =  $query_check_barcode_item['item_image'];
		$get_item_price =  $query_check_barcode_item['item_normal_selling_price'];
		$get_item_vat_has =  $query_check_barcode_item['item_vat'];
		$get_item_code =  $query_check_barcode_item['item_code'];
		$get_item_discount = $query_check_barcode_item['item_discount'];

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
		$get_item_location = $query_check_location['ft_location'];
		$get_real_item_qty = $query_check_location['qty'];




		if (!empty($_SESSION['SBCScart'])) {


			$total = 0;
			// For finding session elements line number
			$linenumber = 0;
			$i = 0;

			// Run loop for cart array 
			foreach ($_SESSION['SBCScart'] as $SBCSitem) {
				$i = $i + 1;
				// Don't list items with 0 qty
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
				if (empty($barcode_id) || empty($get_item_id) || empty($get_item_name) || empty($get_item_price)) {

					$message = "Item Found but Some values are missing";
				} else {



					$message = "Product Added!";
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
					if (!empty($_SESSION['SBCScart'])) {
						// Look for item
						foreach ($_SESSION['SBCScart'] as $k => $SBCSproduct) {
							// Yes we found it
							if ($get_item_id == $SBCSproduct['item_id']) {
								$SBCSexist = true;
								$SBCScount = $k;
								break;
							}
						}
					}

					// If we found same item
					if ($SBCSexist) {

						// Update quantity
						$_SESSION['SBCScart'][$SBCScount]['quantity'] = $get_item_quantity;

						unset($_SESSION['coupon_id']);
						unset($_SESSION['coupon_code']);
						unset($_SESSION['coupon_type']);
						unset($_SESSION['coupon_rate']);
						unset($_SESSION['coupon_message']);
						unset($_SESSION['coupon_display']);
						unset($_SESSION['coupon_value']);
					}
				}
			} else {

				$message = "item not found on this Location";
			}
		} else {

			$message = "Product Out of Stock";
		}
	} else {


		$message = $barcode_id . " " . "No Found!";
	}
} else {


	$message = "Worng Barcode number";
}


echo $message;
