<?php
ob_start();
error_reporting (E_ALL ^ E_NOTICE);
session_start();
include('../include/database.php');
include('../include/check_login.php');
include('../get_url.php');

date_default_timezone_set("Asia/Colombo");
$db = new Database();
function filter($var)
{

    return preg_replace('/ [^a-za-z0-9\s@.]/',' ' , $var);
}

$status = false;
$message = "";






if($_POST['drp_group_id'] && $_POST['drp_type_id'] && $_POST['drp_category_id'] && $_POST['productId'] ){
    $group_id = $_POST['drp_group_id'];
    $type_id = $_POST['drp_type_id'];
    $category_id = $_POST['drp_category_id'];
    $productId = $_POST['productId'];
    
    
    $queryGet = $db->getRow('SELECT * FROM ItemMapping WHERE itemId = ? AND groupId = ? AND typeId = ? AND categoryId = ?',[$productId,$group_id,$type_id,$category_id]);
    
    if($queryGet['id']){
         $message = "This Mapping alrady added to the item.";
    }else{
            try {
					
        $query = $db->insertRow('INSERT INTO ItemMapping (`itemId`,`groupId`,`typeId`,`categoryId`) VALUES(?,?,?,?)',[$productId,$group_id,$type_id,$category_id]);
         $message = "Item Category has been mapped.";
         $status = true;
     } catch (Exception $e) {
 
         $message = $e->getMessage();
         
     }

    }



}else{

    $message = "you should need to fill all the details";

}
$output =  array(
    'status' => $status,
    'message' => $message

);

echo json_encode($output, JSON_FORCE_OBJECT);
?>



