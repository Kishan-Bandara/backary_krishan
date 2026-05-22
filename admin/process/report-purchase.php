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

  $query = $db->getRows('SELECT *
    FROM grn_hedder 
    WHERE grn_h_date  BETWEEN CAST(? AS DATE) AND CAST(? AS DATE) GROUP BY grn_h_id ORDER BY grn_h_date DESC',[$real_start_date,$real_end_date]);

  return $query;
}
      
   
function itemloop($invoice_h_id){

        $db = new Database();
       $item_query = $db->getRows('SELECT * FROM grn_details WHERE grn_h_id = ?',[$invoice_h_id]);

       return $item_query;
      }
#currency
$getcurrency = $db->getRow('SELECT * FROM currency WHERE activated = ? LIMIT 1 ',["Y"]);
$currency = $getcurrency['currency'];
?>


<?php 
$data = getContent();
$i = 0;
$output = '';

  foreach($data as $query) 
        { 

        

            $invoice_h_id =  $query['grn_h_id'];
            $invoice_date = $query['grn_h_date'];
            $invoice_net_value = $query['grn_h_net_value'];
            $invoice_code = $query['grn_h_code'];
            $item_vat_value = 0.00;

           



                   $output .= '<br> <hr/><table>
                                        <thead>
                                        <tr>
                                            <td class="no text-right">INVOICE # '.$invoice_code.' </td>
                                            <td class="desc" style="padding-left:3px;">Invoice Date: '.$invoice_date.'</td>
                                        </tr>
                                        </thead>
                                    </table>';

                               


$output .= '<table class="table table-striped" border="0" cellspacing="0" cellpadding="0" width="100%">
                                       <thead>
                                        <tr style="background-color: #ECECEC">
                                            <td class="no text-right">#</td>
                                            <td class="desc" >Description</td>
                                            <td class="unit " style="text-align:right;">Buying Price</td>
                                            <td class="unit text-right" style="text-align:right;">Selling Price</td>
                                            <td class="qty text-right" style="text-align:right;">Qty</td>
                                            <td class="qty text-right" style="text-align:right;">Tax</td>
                                            <td class="total text-right " style="text-align:right;">TOTAL</td>
                                        </tr>
                                        </thead>';
                                      
                                           
                                          $data_item = itemloop($invoice_h_id);


  foreach($data_item as $item_query) 
        { 


           $i = $i + 1;
                             
            $invoice_item_id =  $item_query['grn_d_item_id'];             
            $invoice_item_vat_has = $item_query['grn_d_vat'];
            $invoice_item_price = $item_query['grn_d_rate'];
            $invoice_item_qty = $item_query['grn_d_qty'];
            $invoice_item_vat = $item_query['grn_d_vat_rate'];
            $invoice_item_detail_id = $item_query['grn_d_id']; 
            if($invoice_item_id){

              $get_item = $db->getRow('SELECT * FROM item_master WHERE item_id = ?',[$invoice_item_id]);

              $item_name = $get_item['item_name'];
              $item_purchase_price = $get_item['item_purchase_price'];

            }

            #item Vat value
            if($invoice_item_vat){

              $item_vat_value = (($invoice_item_price * $invoice_item_qty) * $invoice_item_vat)/100;


            }else{


              $item_vat_value = 0.00;
            }


            #item Total
            $item_tot = ($invoice_item_price * $invoice_item_qty );
            $item_net_value = ($item_tot + $item_vat_value);           

                       $output .= '                 <tbody>
                                                                                                                        <tr>
                                                <td class="no" style="border-bottom: 1px solid #ccc; text-align:left;">'.$i.'</td>
                                                <td class="desc" style="border-bottom: 1px solid #ccc;text-align:left;">'.$item_name.'</td>
                                                <td class="unit text-right" style="border-bottom: 1px solid #ccc;">'.$currency." ".number_format((float)$item_purchase_price,2,'.','').'</td>
                                                <td class="unit text-right" style="border-bottom: 1px solid #ccc;">'.$currency." ".number_format((float)$invoice_item_price,2,'.','').'</td>
                                                <td class="qty text-right" style="border-bottom: 1px solid #ccc;">'.$invoice_item_qty.'</td>
                                                <td class="qty text-right" style="border-bottom: 1px solid #ccc;">'.$currency." ".number_format((float)$item_vat_value,2,'.','').'</td>
                                                <td class="total text-right" style="border-bottom: 1px solid #ccc;">'.$currency." ".number_format((float)$item_net_value,2,'.','').'</td>
                                            </tr>
                                        
                                        
                                        
                                                                                 
                                        
                                        

                                        </tbody>';
 }
$i = 0;
                                      
                   $output .= '                      <tfoot>

                                        
                                        <tr>
                                            <td colspan="4" ></td>
                                            <td colspan="2" width="200px" style="text-align:right;font-weight: 600;">Grand Total</td>
                                            <td  width="200px" style="text-align:right;font-weight: 600;">'.$currency." ".number_format((float)$invoice_net_value,2,'.','').'</td>
                                                                                    </tr>
                                                                              
                                        </tfoot>

                                        
                                    </table>';

       }
     echo $output; 
                   



              

?>









