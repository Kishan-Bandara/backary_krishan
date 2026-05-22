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

$category_id = filter($_POST['pcategory']);
$product_discount = filter($_POST['discount_by_cat']);
$message = "";

if($category_id){




				try {
					
					$query_update = $db->updateRow('UPDATE item_master SET item_promotion_status = 1 , item_discount = ? WHERE item_category = ?',[$product_discount,$category_id]);
					$message = "Promotion added";
				} catch (Exception $e) {

					$message = "Query Running error";
					
				}

		
}else{

	$message = "category id error";
}

echo $message;

?>



