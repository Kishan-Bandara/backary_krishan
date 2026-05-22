<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('../include/database.php');
include('../include/check_login.php');
function filter($var)
{

    return preg_replace('/ [^a-za-z0-9\s@.]/', ' ', $var);
}

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

//parana id eka search karala aluth id ekak hadagannawa.
$db = new Database();
$getpid = $db->getRow('SELECT max(item_id) as item_id FROM item_master');

$oldpid = $getpid['item_id'];

if ($getpid > 0) {

    $newpid =  $oldpid + 1;
}

// product code ekak hadagannawa

$pcode = "PD00" . $newpid;


if (!empty($_POST['pname']) &&  !empty(filter($_POST['pgroup'])) && !empty(filter($_POST['ptype'])) && !empty(filter($_POST['pcategory'])) && !empty($_POST['purchaseprice']) && !empty($_POST['normalsellingprice'])) {

    if (!empty($_POST['pcode'])) {
        $product_code = $_POST['pcode'];
    } else {
        $product_code = "";
    };
    if (!empty($_POST['pname'])) {
        $product_name = $_POST['pname'];
    } else {
        $product_name = "";
    };
    if (!empty($_POST['pgroup'])) {
        $product_group = filter($_POST['pgroup']);
    } else {
        $product_group = "";
    };
    if (!empty($_POST['ptype'])) {
        $product_type = filter($_POST['ptype']);
    } else {
        $product_type = "";
    };
    if (!empty($_POST['pcategory'])) {
        $product_category = filter($_POST['pcategory']);
    } else {
        $product_category = "";
    };
    if (!empty($_POST['punit'])) {
        $product_uom = filter($_POST['punit']);
    } else {
        $product_uom = "";
    };
    if (!empty($_POST['purchaseprice'])) {
        $purchase_price = str_replace(",", "", $_POST['purchaseprice']);;
    } else {
        $purchase_price = "";
    };
    if (!empty($_POST['minsellingprice'])) {
        $min_selling_price = str_replace(",", "", $_POST['minsellingprice']);
    } else {
        $min_selling_price = "";
    };
    if (!empty($_POST['normalsellingprice'])) {
        $normal_selling_price = str_replace(",", "", $_POST['normalsellingprice']);
    } else {
        $normal_selling_price = "";
    };
    if (!empty($_POST['cashprice'])) {
        $cash_selling_price = str_replace(",", "", $_POST['cashprice']);
    } else {
        $cash_selling_price = "";
    };
    if (!empty($_POST['creditprice'])) {
        $credit_selling_price = str_replace(",", "", $_POST['creditprice']);
    } else {
        $credit_selling_price = "";
    };
    if (!empty($_POST['warranty'])) {
        $product_warranty = filter($_POST['warranty']);
    } else {
        $product_warranty = "";
    };
    if (!empty($_POST['sirial'])) {
        $product_sirial = filter($_POST['sirial']);
    } else {
        $product_sirial = "";
    };
    if (!empty($_POST['vat'])) {
        $product_VAT = str_replace(",", "", $_POST['vat']);
    } else {
        $product_VAT = "N";
    };
    if (!empty($_POST['discription'])) {
        $product_discription = $_POST['discription'];
    } else {
        $product_discription = "";
    };
    if (!empty($_POST['productCod'])) {
        $productCod = $_POST['productCod'];
    } else {
        $productCod = "";
    };
    if (!empty($_POST['othersellingPrice'])) {
        $othersellingPrice = $_POST['othersellingPrice'];
    } else {
        $othersellingPrice = "";
    };



    try {

        $insertproduct = $db->insertRow('INSERT INTO item_master(item_code,item_name,item_group,item_type,item_category,item_discription,item_uom,item_purchase_price,item_min_selling_price,item_normal_selling_price,item_cash_selling_price,item_cradit_selling_price,item_warranty,item_has_sirial,item_vat,item_cod,others_selling_price) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [$product_code, $product_name, $product_group, $product_type, $product_category, $product_discription, $product_uom, $purchase_price, $min_selling_price, $normal_selling_price, $cash_selling_price, $credit_selling_price, $product_warranty, $product_sirial, $product_VAT, $productCod, $othersellingPrice]);

        $message = "New record created successfully";
        $status = true;
    } catch (Exception $e) {

        $message =   'Message: ' . $e->getMessage();
    }


    if ($status == true) {

        $queryGestLastId = $db->getRow('SELECT * FROM item_master ORDER BY item_id DESC LIMIT 1');
        $productId = $queryGestLastId['item_id'];
        $productName = $queryGestLastId['item_name'];
    }



    if ($status == true) {


        //check/create image folder

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
                   
                } elseif ($img_type_1 == "image/png") {

                    $target =  $img_path_1 . $image_1_random_name . '.png';
                   
                } else {

                    $target =  $img_path_1 . $image_1_random_name . '.jpg';
                   
                }

               
                if ($img_type_1 == "image/png" || $img_type_1 == "image/jpeg") {

                    try {
        
                        if (move_uploaded_file($img_temp_1, $target)) {
        
        
                            $updateImageDB = $db->updateRow('UPDATE item_master SET item_image = ? , imageParth = ? WHERE item_id = ?', [$image_1_random_name, $target_parth_DB, $productId]);
                        }
                    } catch (Exception $e) {
        
        
                        $img_message_1 = "upload error";
                        $error_style = "red";
                        $error_font = "#FFF";
                    }
                } else {
        
                    $img_message_1 = "Sorry! can not upload this file";
                    $error_style = "red";
                    $error_font = "#FFF";
                } 


                
            }
        } else {

            $image_1_random_name =   "defult-img.png";
            $target_parth_DB = $currentFolderForDB."/";
          
        }


            
   


    }
} else {

    $message = "(*) required Feild! ";
}


$output =  array(
    'status' => $status,
    'message' => $message,
    'id' => $productId

);

echo json_encode($output, JSON_FORCE_OBJECT);



