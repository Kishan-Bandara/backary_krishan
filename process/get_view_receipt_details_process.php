<?php
ob_start();
error_reporting (E_ALL ^ E_NOTICE);

session_start();
include('../include/database.php');
?>
<?php

if(isset($_POST['receipt_id'])){

$receipt_h_id =$_POST['receipt_id'];
//Database eken Table ekata Values daaganna Function eka
function getContent() {
    $db = new Database();
    $receipt_h_id = $_POST['receipt_id'];
    $query = $db->getRows('SELECT * FROM invoice_details WHERE invoice_h_id = ?',[$receipt_h_id]);
    return $query;
    $db->Disconnect();
}
$db = new Database();
$output = '';
$data = getContent();
$i = 0;
       foreach($data as $query) 
        { 
          $i++;
          
          $item_id = $query['invoice_d_item_id'];
          $item_qty = $query['invoice_d_qty'];
          $item_price = $query['invoice_d_item_price'];
          $item_discount = $query['invoice_d_discount_value'];
          $item_total = $query['invoice_d_item_total'];

          $query_item_id = $db->getRow('SELECT * FROM item_master WHERE item_id = ?',[$item_id]);
          $item_name = $query_item_id['item_name'];

          $output.= ' <tr>
                                    <td>'.$i.'</td>
                                    <td>'.$item_name.'</td>
                                    <td>LKR '.$item_price.'</td>
                                    <td>'.$item_qty.'</td>
                                    <td>- '.$item_discount.'%</td>
                                    <td>LKR '.$item_total.'<td>
                                </tr>';

                     
                                       


  }
    echo $output;    



}else {echo "error";}


                                   
?>