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


$itemId  = $_POST['productId'];
function categires()
{
    global $itemId;
    $db = new database();
    $query = $db->getRows('SELECT im.id as id, g.group_name as groupName , t.type_name as typeName , c.category_name categoryName FROM ItemMapping  im  
Inner Join gorup_master g
ON im.groupId = g.group_id
Inner Join type_master t 
ON im.typeId = t.type_id
Inner Join category_master c 
ON im.categoryId = c.category_id
WHERE im.itemId = ?', [$itemId]);

    return $query;
}



$datacategires = categires();
$bankCount = 0;
foreach ($datacategires  as $query) {

 ?>


 <tr>
        <td><?php echo $query['groupName']; ?></td>
        <td><?php echo $query['typeName']; ?></td>
        <td><?php echo $query['categoryName']; ?></td>
        <td><a href="#" class="categoryDelete" data-id="<?php echo $query['id']; ?>" onclick="categoryDelete(<?php echo $query['id']; ?>)">Delete</a></td>
    </tr>





<?php }
?>



