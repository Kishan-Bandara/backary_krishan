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


	$queryGestLastId = $db->getRow('SELECT * FROM item_master  WHERE item_id = ?', [$productId]);
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


	if (!empty($_FILES['uploadMainImage']['tmp_name']) && $_POST['pid']) {

		$packageId = $productId;
		function resizeImage($resourceType, $image_width, $image_height)
		{
			$resizeWidth = 770;
			$resizeHeight = 330;


			$scale_ratio = $image_width / $image_height;

			if (($resizeWidth / $resizeHeight) > $scale_ratio) {

				$resizeWidth = $resizeHeight * $scale_ratio;
			} else {

				$resizeHeight = $resizeWidth / $scale_ratio;
			}

			$imageLayer = imagecreatetruecolor($resizeWidth, $resizeHeight);
			imagecopyresampled($imageLayer, $resourceType, 0, 0, 0, 0, $resizeWidth, $resizeHeight, $image_width, $image_height);
			return $imageLayer;
		}


		$imageProcess = 0;
		if (is_array($_FILES)) {
			$fileName = $_FILES['uploadMainImage']['tmp_name'];
			$sourceProperties = getimagesize($fileName);

			$resizeFileName = md5(rand(0, 1000) . rand(0, 1000));

			$uploadPath = $img_path_1;
			$fileExt = pathinfo($_FILES['uploadMainImage']['name'], PATHINFO_EXTENSION);
			$uploadImageType = $sourceProperties[2];
			$sourceImageWidth = $sourceProperties[0];
			$sourceImageHeight = $sourceProperties[1];
			switch ($uploadImageType) {
				case IMAGETYPE_JPEG:
					$resourceType = imagecreatefromjpeg($fileName);
					$imageLayer = resizeImage($resourceType, $sourceImageWidth, $sourceImageHeight);
					imagejpeg($imageLayer, $uploadPath . "packageMainImg_" . $resizeFileName . '.' . $fileExt);
					break;

				case IMAGETYPE_GIF:
					$resourceType = imagecreatefromgif($fileName);
					$imageLayer = resizeImage($resourceType, $sourceImageWidth, $sourceImageHeight);
					imagegif($imageLayer, $uploadPath . "packageMainImg_" . $resizeFileName . '.' . $fileExt);
					break;

				case IMAGETYPE_PNG:
					$resourceType = imagecreatefrompng($fileName);
					$imageLayer = resizeImage($resourceType, $sourceImageWidth, $sourceImageHeight);
					imagepng($imageLayer, $uploadPath . "packageMainImg_" . $resizeFileName . '.' . $fileExt);
					break;

				default:
					$imageProcess = 0;
					break;
			}


			if ($fileExt == "jpg" || $fileExt == "png" || $fileExt == "jpeg") {

				$file = "";

				$image_name = "packageMainImg_" . $resizeFileName . '.' . $fileExt;
				move_uploaded_file($file, $uploadPath . $resizeFileName . "." . $fileExt);
				$imageProcess = 1;
			}

			if ($imageProcess == 1) {


				try {

					$update_query = $db->insertRow('INSERT INTO productimages(itemId,imagePath,image) values(?,?,?)',[$packageId,$target_parth_DB,$image_name]);
					$status = true;
					$message = "Picture Successfully updated!";
					$message_title = "";
					$class = "alert-success";
				} catch (Exception $e) {

					$message = $e;
					$message_title = "";
					$class = "alert-warning";
				}
			} else {

				$message = "Error!";
				$message_title = "";
				$class = "alert-warning";
			}
		} else {


			$message = "Error!";
			$message_title = "";
			$class = "alert-warning";
		}
		$imageProcess = 0;
	} 
}

$output =  array(
    'status' => $status,
    'message' => $message,
    'id' => $productId

);
echo json_encode($output, JSON_FORCE_OBJECT);



