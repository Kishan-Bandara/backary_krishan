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

function validate($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
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


// Server-side validation — check mandatory fields
$missingFields = [];
if (empty($_POST['pcode'])) $missingFields[] = 'Product Code';
if (empty($_POST['pname'])) $missingFields[] = 'Product Name';
if (empty(filter($_POST['pgroup']))) $missingFields[] = 'Group';
if (empty(filter($_POST['ptype']))) $missingFields[] = 'Type';

if (empty($missingFields)) {

    if (!empty($_POST['pcode'])) {
        $product_code = $_POST['pcode'];
    } else {
        $product_code = "";
    };
    if (!empty($_POST['pname'])) {
        $product_name = validate($_POST['pname']);
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
    // Legacy item_master.item_uom (Product Unit) field is removed from the form.
    // It will be auto-synced from the "Unit of Measure" string after insert (see saveProductAdditionalUoms).
    $product_uom = null;
    if (!empty($_POST['pbusinessunit']) && is_numeric($_POST['pbusinessunit'])) {
        $product_business_unit = (int) $_POST['pbusinessunit'];
    } else {
        $product_business_unit = null;
    };
    if (!empty($_POST['purchaseprice'])) {
        $purchase_price = str_replace(",", "", $_POST['purchaseprice']);;
    } else {
        $purchase_price = 0;
    };
    if (!empty($_POST['minsellingprice'])) {
        $min_selling_price = str_replace(",", "", $_POST['minsellingprice']);
    } else {
        $min_selling_price = "";
    };
    if (!empty($_POST['normalsellingprice'])) {
        $normal_selling_price = str_replace(",", "", $_POST['normalsellingprice']);
    } else {
        $normal_selling_price = 0;
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

    if (!empty($_POST['productWeight'])) {
        $productWeight = $_POST['productWeight'];
    } else {
        $productWeight = "";
    };
    if (isset($_POST['order_qty_min']) && $_POST['order_qty_min'] !== '') {
        $orderQtyMin = str_replace(",", "", $_POST['order_qty_min']);
    } else {
        $orderQtyMin = null;
    };
    if (isset($_POST['order_qty_max']) && $_POST['order_qty_max'] !== '') {
        $orderQtyMax = str_replace(",", "", $_POST['order_qty_max']);
    } else {
        $orderQtyMax = null;
    };
    if (isset($_POST['low_stock_qty']) && $_POST['low_stock_qty'] !== '') {
        $lowStockQty = (int) $_POST['low_stock_qty'];
    } else {
        $lowStockQty = 5;
    }
    if (!empty($_POST['pack_size'])) {
        $packSize = trim($_POST['pack_size']);
    } else {
        $packSize = null;
    };
    if (!empty($_POST['acc_posting_grp_code'])) {
        $accPostingGrpCode = trim($_POST['acc_posting_grp_code']);
    } else {
        $accPostingGrpCode = null;
    };
    if (!empty($_POST['gst_inclusion'])) {
        $gstInclusion = $_POST['gst_inclusion'];
    } else {
        $gstInclusion = "N";
    };
    if (!empty($_POST['gst_code'])) {
        $gstCode = trim($_POST['gst_code']);
    } else {
        $gstCode = null;
    };
    if (!empty($_POST['discription2'])) {
        $product_discription2 = $_POST['discription2'];
    } else {
        $product_discription2 = "";
    };
    if (!empty($_POST['wholesale_price'])) {
        $wholesalePrice = str_replace(",", "", $_POST['wholesale_price']);
    } else {
        $wholesalePrice = 0.00;
    };
    if (!empty($_POST['retail_price'])) {
        $retailPrice = str_replace(",", "", $_POST['retail_price']);
    } else {
        $retailPrice = 0.00;
    };
    if (!empty($_POST['item_weight_g'])) {
        $itemWeightG = (int)$_POST['item_weight_g'];
    } else {
        $itemWeightG = null;
    };
    if (!empty($_POST['pack_weight_g'])) {
        $packWeightG = (int)$_POST['pack_weight_g'];
    } else {
        $packWeightG = null;
    };
    if (!empty($_POST['minimum_order'])) {
        $minimumOrder = (int)$_POST['minimum_order'];
    } else {
        $minimumOrder = null;
    };
    if (!empty($_POST['unit_of_measure'])) {
        $unitOfMeasure = $_POST['unit_of_measure'];
    } else {
        $unitOfMeasure = 'Gram';
    };
    if (!empty($_POST['pack_type'])) {
        $packType = $_POST['pack_type'];
    } else {
        $packType = 'Bag';
    };
    if (!empty($_POST['live'])) {
        $live = $_POST['live'];
    } else {
        $live = 'yes';
    };
    if (!empty($_POST['nutritional_label'])) {
        $nutritionalLabel = trim($_POST['nutritional_label']);
    } else {
        $nutritionalLabel = null;
    };
    if (!empty($_POST['product_specification'])) {
        $productSpecification = trim($_POST['product_specification']);
    } else {
        $productSpecification = null;
    };
    if (!empty($_POST['default_label'])) {
        $defaultLabel = trim($_POST['default_label']);
    } else {
        $defaultLabel = null;
    };
    if (!empty($_POST['seasonal_rule'])) {
        $seasonalRule = trim($_POST['seasonal_rule']);
    } else {
        $seasonalRule = null;
    };
    if (!empty($_POST['food_declarations'])) {
        $foodDeclarations = $_POST['food_declarations'];
    } else {
        $foodDeclarations = null;
    };
    $availMonday = isset($_POST['avail_monday']) ? 1 : 0;
    $availTuesday = isset($_POST['avail_tuesday']) ? 1 : 0;
    $availWednesday = isset($_POST['avail_wednesday']) ? 1 : 0;
    $availThursday = isset($_POST['avail_thursday']) ? 1 : 0;
    $availFriday = isset($_POST['avail_friday']) ? 1 : 0;
    $availSaturday = isset($_POST['avail_saturday']) ? 1 : 0;
    $availSunday = isset($_POST['avail_sunday']) ? 1 : 0;
    $hideToAllCustomers = isset($_POST['hide_to_all_customers']) ? (int)$_POST['hide_to_all_customers'] : 0;
    $saleOrReturn = isset($_POST['sale_or_return']) ? (int)$_POST['sale_or_return'] : 0;
    $isRawMaterial = isset($_POST['is_raw_material']) ? (int)$_POST['is_raw_material'] : 0;
    $allowInSales = isset($_POST['allow_in_sales']) ? 1 : 0;
    $allowInGrn = isset($_POST['allow_in_grn']) ? 1 : 0;
    $batchTracking = isset($_POST['batch_tracking']) && in_array($_POST['batch_tracking'], ['NONE','BATCH','SERIAL']) ? $_POST['batch_tracking'] : 'NONE';
    
    // Product Status
    if (!empty($_POST['productStatus'])) {
        $productStatus = $_POST['productStatus'];
    } else {
        $productStatus = 'Normal';
    };
    
    // Immediate Pickup
    if (!empty($_POST['ImmediatePickup'])) {
        $immediatePickup = $_POST['ImmediatePickup'];
    } else {
        $immediatePickup = 'No';
    };

    $createGuestIdQuery = $db->getRow('SELECT (item_id+1)  as Id FROM item_master ORDER BY item_id DESC LIMIT 1');

    $guestId = $createGuestIdQuery ['Id'];

    $titleCleared = preg_replace($pattern, '', (string) $product_name);
  
    $urlPerm = $string = str_replace(' ', '-', $titleCleared)."-".$guestId;

    try {

    $insertproduct = $db->insertRow('INSERT INTO item_master(item_code,item_name,item_group,item_type,item_category,item_business_unit,item_discription,item_uom,item_purchase_price,item_normal_selling_price,item_warranty,item_has_sirial,item_vat,item_cod,url,item_weight,order_qty_min,order_qty_max,low_stock_qty,pack_size,acc_posting_grp_code,gst_vat_code,nutritional_label,sale_or_return,product_specification,live,hide_to_all_customers,wholesale_price,retail_price,item_weight_g,pack_weight_g,minimum_order,description,default_label,food_declarations,seasonal_rule,avail_monday,avail_tuesday,avail_wednesday,avail_thursday,avail_friday,avail_saturday,avail_sunday,unit_of_measure,pack_type,item_mode,immediate_pickups,is_raw_material,allow_in_sales,allow_in_grn,batch_tracking) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [$product_code, $product_name, $product_group, $product_type, $product_category, $product_business_unit, $product_discription, $product_uom, $purchase_price, $normal_selling_price, $product_warranty, $product_sirial, $gstInclusion, $productCod, $urlPerm, $productWeight, $orderQtyMin, $orderQtyMax, $lowStockQty, $packSize, $accPostingGrpCode, $gstCode, $nutritionalLabel, $saleOrReturn, $productSpecification, $live, $hideToAllCustomers, $wholesalePrice, $retailPrice, $itemWeightG, $packWeightG, $minimumOrder, $product_discription2, $defaultLabel, $foodDeclarations, $seasonalRule, $availMonday, $availTuesday, $availWednesday, $availThursday, $availFriday, $availSaturday, $availSunday, $unitOfMeasure, $packType, $productStatus, $immediatePickup, $isRawMaterial, $allowInSales, $allowInGrn, $batchTracking]);

        $message = "New record created successfully";
        $status = true;
    } catch (Exception $e) {

        $message =   'Message: ' . $e->getMessage();
    }


    if ($status == true) {

        $queryGestLastId = $db->getRow('SELECT * FROM item_master ORDER BY item_id DESC LIMIT 1');
        $productId = $queryGestLastId['item_id'];
        $productName = $queryGestLastId['item_name'];

        // Save alternative units of measure (Business Central style)
        require_once(__DIR__ . '/../include/uom_helper.php');
        $altUomsRaw = $_POST['alt_uoms_json'] ?? '[]';
        $altUomsArr = json_decode($altUomsRaw, true);
        if (!is_array($altUomsArr)) { $altUomsArr = []; }
    // Persist additional UOM names first so item_uom rows exist and item_master.item_uom is synced
    $additionalUomNames = $_POST['additional_uoms'] ?? [];
    if (!is_array($additionalUomNames)) { $additionalUomNames = []; }
    try { saveProductAdditionalUoms($db, (int) $productId, $additionalUomNames); } catch (Exception $e) { /* ignore */ }
        $priceTiersJson = $_POST['price_tiers_json'] ?? '[]';
        $priceTiers = json_decode($priceTiersJson, true);
        if (is_array($priceTiers) && count($priceTiers) > 0) {
            // Ensure table exists
            $db->insertRow("CREATE TABLE IF NOT EXISTS item_price_tiers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                item_id INT NOT NULL,
                min_qty DECIMAL(10,2) NOT NULL,
                unit_price DECIMAL(10,4) NOT NULL,
                INDEX idx_item_id (item_id)
            ) ENGINE=InnoDB", []);
            $db->insertRow('DELETE FROM item_price_tiers WHERE item_id = ?', [$productId]);
            foreach ($priceTiers as $tier) {
                $minQty = max(1, floatval($tier['min_qty'] ?? 1));
                $unitPrice = max(0, floatval($tier['unit_price'] ?? 0));
                $db->insertRow('INSERT INTO item_price_tiers (item_id, min_qty, unit_price) VALUES (?,?,?)', [$productId, $minQty, $unitPrice]);
            }
        }
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

    $message = "Please fill required fields: " . implode(', ', $missingFields);
}


$output =  array(
    'status' => $status,
    'message' => $message,
    'id' => $productId

);

echo json_encode($output, JSON_FORCE_OBJECT);



