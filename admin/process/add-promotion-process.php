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

    return preg_replace('/[^0-9]/',' ' , $var);
}

$product_id = filter($_POST['item']);
$product_discount = filter($_POST['discount']);
$promo_type = filter($_POST['pro_type']);
$message = "";

if($product_id){

	$query_itm_qty = $db->getRow('SELECT * FROM fifo  WHERE ft_location = ? AND ft_blanace > 0 AND ft_item = ? GROUP BY ft_item',[$_SESSION['location'],$product_id]);

		if($query_itm_qty['ft_blanace'] > 0){


				try {
					
					$query_update = $db->updateRow('UPDATE item_master SET item_promotion_status = 1 , item_discount = ? , item_product_of_day = ? WHERE item_id = ?',[$product_discount,$promo_type,$product_id]);
					$message = "Promotion added";
				} catch (Exception $e) {

					$message = "Query Running error";
					
				}

		}else{
			$message = "you should need to purchase this product";
		}
}else{

	$message = "Product id error";
}

echo $message;

?>



