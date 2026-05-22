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
  $whiteSpace = '\s';
  $pattern = '/[^a-zA-Z0-9'  . $whiteSpace . ']/u';

function items(){

    $db = new database();

    $query = $db->getRows('SELECT * FROM item_master WHERE url = ?',[""]);

return $query;
}

function types(){

    $db = new database();

    $query = $db->getRows('SELECT * FROM type_master',[""]);

return $query;
}
function category(){

    $db = new database();

    $query = $db->getRows('SELECT * FROM category_master',[""]);

return $query;
}


$data = items();

foreach($data as $query){

    $item_id = $query['item_id'];
    $item_name = $query['item_name'];

    $createGuestIdQuery = $db->getRow('SELECT * FROM item_master WHERE item_id = ?',[$item_id]);

    $guestId = $createGuestIdQuery ['item_id'];

    $titleCleared = preg_replace($pattern, '', (string) $item_name);
  
    
    $urlPerm = $string = str_replace(' ', '-', $titleCleared)."-".$guestId;

    $updateQuery = $db->updateRow('UPDATE item_master SET url = ? WHERE item_id = ?',[$urlPerm,$guestId]);


}


$data = types();

foreach($data as $query){

    $item_id = $query['type_id'];
    $item_name = $query['type_name'];

    $createGuestIdQuery = $db->getRow('SELECT * FROM type_master WHERE type_id = ?',[$item_id]);

    $guestId = $createGuestIdQuery ['type_id'];

    $titleCleared = preg_replace($pattern, '', (string) $item_name);
  
    
    $urlPerm = $string = str_replace(' ', '-', $titleCleared)."-".$guestId;

    $updateQuery = $db->updateRow('UPDATE type_master SET clean_url = ? WHERE type_id = ?',[$urlPerm,$guestId]);


}

$data = category();

foreach($data as $query){

    $item_id = $query['category_id'];
    $item_name = $query['category_name'];

    $createGuestIdQuery = $db->getRow('SELECT * FROM category_master WHERE category_id = ?',[$item_id]);

    $guestId = $createGuestIdQuery ['category_id'];

    $titleCleared = preg_replace($pattern, '', (string) $item_name);
  
    
    $urlPerm = $string = str_replace(' ', '-', $titleCleared)."-".$guestId;

    $updateQuery = $db->updateRow('UPDATE category_master SET clean_url = ? WHERE category_id = ?',[$urlPerm,$guestId]);


}


?>



