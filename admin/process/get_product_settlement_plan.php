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
function getBanks()
{
    global $itemId;
    $db = new database();
    $query = $db->getRows('SELECT  * FROM banks b 
        INNER JOIN product_settlement_plan ps 
        ON ps.bankId = b.Id 
        WHERE ps.productId = ? 
        GROUP BY b.Id', [$itemId]);

    return $query;
}





function getSettlements($bankId)
{

    global $itemId;
    $db = new database();
    $query = $db->getRows('SELECT * FROM product_settlement_plan WHERE productId = ? AND bankId = ? ORDER BY bankId ASC', [$itemId, $bankId]);
    return $query;
}


$dataBanks = getBanks();
$bankCount = 0;
foreach ($dataBanks as $query) {
    $bankCount++;
    $bankId = $query['bankId'];


    $settlementsQuery =  getSettlements($bankId); ?>


    <tr id="image-row0">
        <td class="text-left"> <?php echo $query['name']; ?> Cards</td>
        <td class="text-left">
            <?php  foreach ($settlementsQuery as $settlement) { ?>

                <div><?php echo $settlement['months']." Month's"; ?> -  <?php echo  "Rs ".number_format($settlement['installment'],2); ?> <a href="#"  style="font-size:12px;color:red;" class="delete_settlement" data-id="<?php echo $settlement['Id'];?>">delete</a></div>

            <?php }?>


        </td>
       
    </tr>





<?php }
?>



