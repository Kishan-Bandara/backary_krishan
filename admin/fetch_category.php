<?php
ob_start();
error_reporting (E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
$output = '';  
 $db = new Database();
 $categoryquery = $db->getRows('SELECT * FROM  category_master WHERE type_id =  ?',[$_POST["typeId"]]);
 $categorydata = $categoryquery;
 $output = '<option value="">Select Category</option>';
foreach($categorydata as $categoryquery) 
            {   

                $categoryid = $categoryquery['type_id']; 
                $output .= '<option value="'.$categoryquery['category_id'].'">'.$categoryquery['category_name'].'</option>';

            }
           
 echo $output;  


?>



