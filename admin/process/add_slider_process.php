<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

session_start();
include('../include/database.php');
include('../include/check_login.php');

header('Content-Type: application/json');

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

if (isset($_POST['sliderUrl'])) {
 
    $currentFolder = "../../assets/img/slider/";
    $currentFolderForDB = "assets/img/slider/";
    if (isset($_POST['sliderUrl'])) {
        $sliderUrl = $_POST['sliderUrl'];
    } else {
        $sliderUrl = "";
    };
    if (isset($_POST['sliderContain'])) {
        $sliderContain = $_POST['sliderContain'];
    } else {
        $sliderContain = "";
    };

    if (!is_dir($currentFolder) && !mkdir($currentFolder, 0755, true) && !is_dir($currentFolder)) {
        $message = "Unable to create the slider upload folder";
    }

    $img_path_1 = $currentFolder;
    $target_parth_DB = $currentFolderForDB;

    if ($message === '' && !empty($_FILES['uploadMainImage']['size']) && $_FILES['uploadMainImage']['error'] == 0) {
     
        if (!empty($_FILES['uploadMainImage'])) {
            $img_name_1  = $_FILES["uploadMainImage"]["name"];
            $img_type_1  = $_FILES["uploadMainImage"]["type"];
            $img_size_1  = $_FILES["uploadMainImage"]["size"];
            $img_temp_1 = $_FILES["uploadMainImage"]["tmp_name"];
            $img_error_1 = $_FILES["uploadMainImage"]["error"];



            list($img_width_1, $img_height_1) = getimagesize($img_temp_1);



            $productNameCleared = rand(10,100000);

            $image_1_random_name = $string = str_replace(' ', '-', $productNameCleared);

            if ($img_type_1 == "image/jpeg") {

                $target =  $img_path_1 . $image_1_random_name . '.jpg';
                $image_1_random_name = $image_1_random_name . '.jpg';
            } elseif ($img_type_1 == "image/png") {

                $target =  $img_path_1 . $image_1_random_name . '.png';
                $image_1_random_name = $image_1_random_name . '.png';
            } else {

                $target =  $img_path_1 . $image_1_random_name . '.jpg';
                $image_1_random_name = $image_1_random_name . '.jpg';
            }


            if ($img_type_1 == "image/png" || $img_type_1 == "image/jpeg") {
               
                try {


                    if (move_uploaded_file($img_temp_1, $target)) {
                        $updateImageDB = $db->insertRow('INSERT INTO home_slider(`image` , `path`,`link`,`active`,`text`) VALUES (?,?,?,?,?)', [$image_1_random_name, $target_parth_DB, $sliderUrl, 1,$sliderContain]);
                        $status = true;
                        $message = 'Slider image uploaded successfully';
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
        if ($message === '') {
            $message = 'Please select a JPG or PNG image to upload';
        }
    }
}


$output =  array(
    'status' => $status,
    'message' => $message

);

echo json_encode($output, JSON_FORCE_OBJECT);



