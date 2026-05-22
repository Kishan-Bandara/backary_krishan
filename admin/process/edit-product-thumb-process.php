<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

session_start();
include('../include/database.php');
include('../include/check_login.php');

$nowDate = date("Y-m-d");
$nowTime = date("h:i:s");
$nowDateTime = date("Y-m-d h:i:s");
$nowDateTime_2 = date("Y-m-d H:i:s");
$thisYear = date("Y");
$thisMonth = date("m");

$whiteSpace = '\s';
$pattern = '/[^a-zA-Z0-9'  . $whiteSpace . ']/u';

$message = "";
$status = false;
$productId = 0;
$imageTypeStatus = false;
//check/create image folder

if (isset($_POST['pid'])) {
	$productId = $_POST['pid'];


	$queryGestLastId = $db->getRow('SELECT * FROM item_master  WHERE item_id = ?',[$productId]);
	$productId = $queryGestLastId['item_id'];
	$productName = $queryGestLastId['item_name'];



	$currentFolder = "../../images/product_img";
	$currentFolderForDB = "images/product_img";


	if (!file_exists($currentFolder . "/" . $thisYear . "/" . $thisMonth)) {


		if (mkdir($currentFolder . "/" . $thisYear . "/" . $thisMonth, 0777, true)) {

			$img_path_1 = $currentFolder . "/" . $thisYear . "/" . $thisMonth . "/";
			$target_parth_DB = $currentFolderForDB . "/" . $thisYear . "/" . $thisMonth . "/";
		}
	} else {

		$img_path_1 = $currentFolder . "/" . $thisYear . "/" . $thisMonth . "/";
		$target_parth_DB = $currentFolderForDB . "/" . $thisYear . "/" . $thisMonth . "/";
	}


	if ($_FILES['img1']['size'] && $_FILES['img1']['error'] == 0) {

		if (!empty($_FILES['img1'])) {
			$img_name_1  = $_FILES["img1"]["name"];
			$img_type_1  = $_FILES["img1"]["type"];
			$img_size_1  = $_FILES["img1"]["size"];
			$img_temp_1 = $_FILES["img1"]["tmp_name"];
			$img_error_1 = $_FILES["img1"]["error"];



			list($img_width_1, $img_height_1) = getimagesize($img_temp_1);



			$productNameCleared = preg_replace($pattern, '', (string) $productName);

			$image_1_random_name = $string = str_replace(' ', '-', $productNameCleared) . "-" . $productId;

			if ($img_type_1 == "image/jpeg") {

				$target =  $img_path_1 . $image_1_random_name . '.jpg';
				$image_1_random_name = $image_1_random_name.'.jpg';
			} elseif ($img_type_1 == "image/png") {

				$target =  $img_path_1 . $image_1_random_name . '.png';
				$image_1_random_name = $image_1_random_name.'.png';
			} else {

				$target =  $img_path_1 . $image_1_random_name . '.jpg';
				$image_1_random_name = $image_1_random_name.'.jpg';
			}


			if ($img_type_1 == "image/png" || $img_type_1 == "image/jpeg") {

				try {


					if (move_uploaded_file($img_temp_1, $target)) {

						$updateImageDB = $db->updateRow('UPDATE item_master SET item_image = ? , imageParth = ? WHERE item_id = ?', [$image_1_random_name, $target_parth_DB, $productId]);
						$status = true;
					}
				} catch (Exception $e) {


					$message = "upload error";
					$error_style = "red";
					$error_font = "#FFF";
				}
			} else {

				$message = "Sorry! can not upload this file";
				$error_style = "red";
				$error_font = "#FFF";
			}
		}
	} else {

		$image_1_random_name =   "defult-img.png";
		$target_parth_DB = $currentFolderForDB . "/";
	}
}


$output =  array(
    'status' => $status,
    'message' => $message,
    'id' => $productId

);

echo json_encode($output, JSON_FORCE_OBJECT);



