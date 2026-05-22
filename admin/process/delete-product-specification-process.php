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
$db = new database();

if(isset($_POST['Id']) && isset($_POST['productId'])) {

  

    $Id = $_POST['Id'];
    $productId = $_POST['productId'];
  

    $queryCheck = $db->getRow('SELECT * FROM item_specification WHERE Id = ? AND product_id = ?',[$Id,$productId]);


        if($queryCheck['Id']>0){

          
                
                try{

                    $deleteRecode = $db->deleteRow('DELETE FROM item_specification WHERE Id = ?',[$Id]);
                    $status = true;

                }catch (Exception $e) {
                    $message = $e->getMessage();

                }

               


        }else{

            $message = "you have an error!";

        }

}
$output =  array(
    'status' => $status,
    'message' => $message,
    'id' => $productId

);
echo json_encode($output, JSON_FORCE_OBJECT);

?>



