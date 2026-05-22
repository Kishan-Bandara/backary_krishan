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

if($_POST['city_id'])
{
	$city_id = $_POST['city_id'];

	$query_check_city_id = $db->getRow('SELECT * FROM city_master WHERE id = ?',[$city_id]);
	$real_city_id = $query_check_city_id['id'];
	$real_city_rate_flag = $query_check_city_id['flag'];
	$real_city_name = $query_check_city_id['city'];
	$real_city_rate = $query_check_city_id['rate'];

	if(!empty($real_city_id) && $city_id == $real_city_id){

			$_SESSION["cityId"] = $real_city_id;
			$_SESSION["cityName"] = $real_city_name;
			if($real_city_rate_flag == 1){

				$message = "free delivery to ".$real_city_name;
				$delivery_rate = $query_check_city_id['rate'];
				$_SESSION["deliveryRate"] = $delivery_rate;

			}else{


				$message = "delivery charge added Rs ".$real_city_rate;
				$delivery_rate = $real_city_rate;
				$_SESSION["deliveryRate"] = $delivery_rate;

			}
		


	}else{

		$message = "This city id not found on our system";

	}


}
  $output =  array('message' => $message,
               		'rate' => $delivery_rate);

        echo json_encode($output,JSON_FORCE_OBJECT);

?>



