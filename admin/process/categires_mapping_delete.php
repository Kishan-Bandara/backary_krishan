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






if($_POST['id']){
    $Id = $_POST['id'];
  

    try {
					
        $query = $db->deleteRow('DELETE FROM ItemMapping WHERE Id = ?',[$Id]);
         $message = "mapping has been Removed!";
         $status = true;
     } catch (Exception $e) {
 
         $message = $e->getMessage();
         
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



