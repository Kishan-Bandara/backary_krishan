<?php
ob_start();
error_reporting (E_ALL ^ E_NOTICE);

session_start();
include('../include/database.php');

function filter($var)
{

    return preg_replace('/ [^a-za-z0-9\s@.]/',' ' , $var);
}

?>

<?php 
$db = new Database();

if($_POST['delivery_id'])
{
	$id = $_POST['delivery_id'];

	$query = $db->getRow('SELECT * FROM delivery_master WHERE id = ?',[$id]);
	$real_id = $query['id'];

	$real_delivery_name = $query['method'];


	if(!empty($real_id) && $id == $real_id){

		 	$customer_id = $_SESSION["customerid"];
			$_SESSION["deliveryModeId"] = $real_id;
			$_SESSION["delivery_address"] = $real_delivery_name;
			$delivery_mode = $real_id;
			
			if($delivery_mode == 1){


	$name_title = "Name";

	$query_delevery_home_details = $db->getRow('SELECT * FROM shipping_address WHERE fk_customer_id = ? AND fk_delivery_method = ? ',[$customer_id,$delivery_mode]);
	$delivery_name = $query_delevery_home_details['name'];
	$delivery_address = $query_delevery_home_details['address'];
	$delivery_real_city = $query_delevery_home_details['fk_city'];
	$delivery_contact_no = $query_delevery_home_details['contact_no'];

	$query_delevery_home_city_name = $db->getRow('SELECT * FROM city_master WHERE id = ?',[$delivery_real_city]);
	$delivery_real_city = $query_delevery_home_city_name['city'];
	$delivery_full_address = $delivery_address.",".$delivery_real_city;

	$_SESSION["delivery_address"] = $delivery_full_address;

}else if($delivery_mode == 2){

	$name_title = "Company Name";

	$query_delevery_office_details = $db->getRow('SELECT * FROM shipping_address WHERE fk_customer_id = ? AND fk_delivery_method = ? ',[$customer_id,$delivery_mode]);
	$delivery_name = $query_delevery_office_details['name'];
	$delivery_address = $query_delevery_office_details['address'];
	$delivery_real_city = $query_delevery_office_details['fk_city'];
	$delivery_contact_no = $query_delevery_office_details['contact_no'];

	$query_delevery_office_city_name = $db->getRow('SELECT * FROM city_master WHERE id = ?',[$delivery_real_city]);
	$delivery_real_city = $query_delevery_office_city_name['city'];

	$delivery_full_address = $delivery_address.",".$delivery_real_city;
	$_SESSION["delivery_address"] = $delivery_full_address;

}else if($delivery_mode == 3){

	$name_title = "Name";
	$delivery_name = "";
	$delivery_address = "";
	$delivery_real_city = $_SESSION["cityName"];
	$delivery_contact_no = "";
	$delivery_full_address = "";
	$_SESSION["delivery_address"] = $delivery_full_address;
}else{

	$name_title = "Name";
	$delivery_name = "";
	$delivery_address = "";
	$delivery_real_city = $_SESSION["cityName"];
	$delivery_contact_no = "";
	$delivery_full_address = "";
	$_SESSION["delivery_address"] = $delivery_full_address;

}


			
		

	}else{

		$message = "This payment id not found on our system";

	}


}
  $output =  array('name_title' => $name_title,
 				  'delevery_name' =>$delivery_name,
 				  'delivery_address' => $delivery_full_address,
 				  'delivery_contact_no' => $delivery_contact_no,
 				  'message' =>$message);

        echo json_encode($output,JSON_FORCE_OBJECT);

?>



