<?php
ob_start();
error_reporting (E_ALL ^ E_NOTICE);

session_start();
include('../include/database.php');
include('../include/check_login.php');
include('../get_url.php');

function filter($var)
{

  return preg_replace('[0-9]',' ' , $var);
}

?>
<?php 
//Database eken Table ekata Values daaganna Function eka

function getContent() {

  $search_name = $_POST['daterange'];

$orderdate = explode('-',$search_name);
$start_date = $orderdate[0];
$end_date   = $orderdate[1];


$start_date_order = explode('/',$start_date);
$end_date_order = explode('/',$end_date);

$start_month = $string = str_replace(' ', '', $start_date_order[0]);
$start_day = $string = str_replace(' ', '', $start_date_order[1]);
$start_year = $string = str_replace(' ', '', $start_date_order[2]);

$end_month = $string = str_replace(' ', '', $end_date_order[0]);
$end_day = $string = str_replace(' ', '', $end_date_order[1]);
$end_year = $string = str_replace(' ', '', $end_date_order[2]);

$real_start_date = $start_year."-".$start_month."-".$start_day;
$real_end_date = $end_year."-".$end_month."-".$end_day;


/*$real_start_date = "2016-07-01";
$real_end_date = "2016-12-13";*/


   $db = new Database();

     $hasCol = $db->getRow("SHOW COLUMNS FROM item_master LIKE 'low_stock_qty'");
       if (is_array($hasCol) && isset($hasCol['Field']) && $hasCol['Field'] === 'low_stock_qty') {
         $query = $db->getRows('SELECT item_master.item_id, item_master.item_name, item_master.item_normal_selling_price, item_master.item_image, item_master.item_group, item_master.item_type, item_master.item_category, item_master.item_code, item_master.item_purchase_price, COALESCE(MAX(item_master.low_stock_qty), 5) AS low_stock_qty FROM item_master INNER JOIN fifo ON item_master.item_id = fifo.ft_item GROUP BY fifo.ft_item HAVING SUM(fifo.ft_blanace) < COALESCE(MAX(item_master.low_stock_qty), 5)');
   } else {
       $query = $db->getRows('SELECT item_master.item_id, item_master.item_name, item_master.item_normal_selling_price, item_master.item_image, item_master.item_group, item_master.item_type, item_master.item_category, item_master.item_code, item_master.item_purchase_price, 5 AS low_stock_qty FROM item_master INNER JOIN fifo ON item_master.item_id = fifo.ft_item GROUP BY fifo.ft_item HAVING SUM(fifo.ft_blanace) < 5');
   }

  return $query;
}
      
   

#currency
$getcurrency = $db->getRow('SELECT * FROM currency WHERE activated = ? LIMIT 1 ',["Y"]);
$currency = $getcurrency['currency'];



?>


<?php 
$data = getContent();
$i = 0;
$db = new Database();

  foreach($data as $query) 
        { 

          // /*  $item_id = $query['invoice_d_item_id'];
          //  /* $query_item = $db->getRow('SELECT * FROM  item_master WHERE item_id = ?',[$item_id]);
          
          //   $item_name = $query_item['item_name'];*/
          //   $selling_price = $query['invoice_d_item_price'];
          //   $sold_qty = $query['invoice_d_qty'];
          //   $invoice_h_discount = $query['invoice_h_coupon_rate'];
          //   $invoice_h_discount_type = $query['invoice_h_coupon_type'];*/
/*$item_code = $query['code'];
*/
                    ?>              <td class="no" style="border-bottom: 1px solid #ccc; text-align:left;">asdas</td>
                                                <td class="desc" style="border-bottom: 1px solid #ccc;text-align:left;">asda</td>
                                                 <td class="unit text-right" style="border-bottom: 1px solid #ccc;">424</td>
                                                <td class="unit text-right" style="border-bottom: 1px solid #ccc;">1212</td>
                                               
                                                <td class="qty text-right" style="border-bottom: 1px solid #ccc;">0.00</td>
                                                <td class="qty text-right" style="border-bottom: 1px solid #ccc;">00.00</td>
                                                <td class="total text-right" style="border-bottom: 1px solid #ccc;">0.00</td>
                                            </tr> 
 

     <?php   }
 
                   



              

?>









