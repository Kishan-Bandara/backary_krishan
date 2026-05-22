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

  if($_GET['status'] == true){


$db = new database();
  function getitems(){

    $db = new database();

    $query = $db->getRows('SELECT * FROM item_master');

    return $query;

  }


  $data = getitems();

  foreach($data as $query){

    $url = $query['url'];

    if(empty($url)){


    

        $guestId = $query['item_id'];
        $product_name = $query['item_name'];
      
        $titleCleared = preg_replace($pattern, '', (string) $product_name);
      
        $urlPerm = $string = str_replace(' ', '-', $titleCleared)."-".$guestId;

        $updateQuery = $db->updateRow('UPDATE item_master SET url = ? WHERE item_id = ?',[$urlPerm,$guestId]);

        echo $urlPerm." <br>";
    }


  }

}
?>



