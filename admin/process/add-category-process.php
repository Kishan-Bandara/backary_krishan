<?php 
ob_start();
error_reporting (E_ALL ^ E_NOTICE);
session_start();
include('../include/database.php');
include('../include/check_login.php');
function filter($var)
{

    return preg_replace('/ [^a-za-z0-9\s@.]/',' ' , $var);
}
?>
<?php
$type_id = filter($_POST['ptype']);
$catName = $_POST['name'];
$db = new Database();
ensureMasterWebsiteStatusColumns($db);
$websiteStatus = normalizeWebsiteStatus($_POST['website_status'] ?? 'Y');

if(!empty($catName) && !empty($type_id))
{
	//check alrady category Name
$checkcategory = $db->getRow('SELECT * FROM category_master WHERE category_name = ? AND type_id = ? ',[$catName,$type_id]);

if($checkcategory > 0)
{

    $message = "Product Category Information Already Exist";

}
   else
   {
    //insert into the Database 
    try {
   
   $db = new Database();
    $insertType = $db->insertRow('INSERT INTO category_master(type_id,category_name,website_status) VALUES(?,?,?)',[$type_id,$catName,$websiteStatus]);

   $message = "New record created successfully";

  
    } catch (PDOException $e) {
       
      $message= '$insertType."<br>" . $e->getMessage()';
   }
   }



}
else
{

$message = "Please enter (*) required field.";

}

echo $message;


?>



