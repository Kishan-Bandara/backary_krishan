<?php
ob_start();
error_reporting (E_ALL ^ E_NOTICE);
session_start();
include('../include/database.php');
$output = '';  
 $db = new Database();

 function getTypes(){
    $db = new database();
    $typequery = $db->getRows('SELECT * FROM  payments_in_delivery pd INNER JOIN payment_method pm ON pd.paymentId = pm.id WHERE pd.deliveryId =  ? AND pm.website_status = ?',[$_POST["deliverType"],'Y']);
    return $typequery;

 }

 $data = getTypes();

foreach($data as $typequery) 
            {   

                $typeid = $typequery['paymentId']; 

                $query = $db->getRow('SELECT * FROM payment_method WHERE id = ? AND website_status = ?',[$typeid,'Y']);


                $output .= '<div class="radio"> <label> <input type="radio" name="payment_Type" class="payment_Type" value="'.$query['id'].'">'.$query['type'].'   <img src="'.$query['img'].'"> </label></div>';

            }
           
 echo $output;  


?>