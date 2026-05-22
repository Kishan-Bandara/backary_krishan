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
  $payment_type = $_POST['payment_type'];
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
    FROM invoice_hedder 
    WHERE invoice_h_date  BETWEEN CAST(? AS DATE) AND CAST(? AS DATE) AND invoice_h_status = 1 AND  invoice_h_location = ? AND invoice_h_pay_type = ? GROUP BY invoice_h_id ORDER BY invoice_h_date DESC',[$real_start_date,$real_end_date,$_SESSION['location'],$payment_type]);

  return $query;
}
      
   
function itemloop($invoice_h_id){

        $db = new Database();
       $item_query = $db->getRows('SELECT * FROM invoice_details WHERE invoice_h_id = ?',[$invoice_h_id]);

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

        

            $invoice_h_id =  $query['invoice_h_id'];
            $invoice_date = $query['invoice_h_date'];
            $invoice_net_value = $query['invoice_h_gross_value'];
            $invoice_code = $query['invoice_h_code'];
            $customer_id = $query['invoice_h_customer_id'];
            $item_vat_value = 0.00;

              $query_customer = $db->getRow('SELECT * FROM customer WHERE customer_id = ?',[$customer_id]);



                   $output .= '<br> <hr/><table>
                                        <thead>
                                        <tr>
                                            <td class="no text-right">INVOICE # '.$invoice_code.' </td>
                                            <td class="desc" style="padding-left:3px;">Invoice Date: '.$invoice_date.'</td>
                                        </tr>
                                        </thead>
                                    </table>';

                               


$output .= ' <table class="table table-striped" border="0" cellspacing="0" cellpadding="0" width="100%">
                                       <thead>
                                        <tr style="background-color: #ECECEC">
                                            <td class="no text-right">#</td>
                                            <td class="desc" >Invoice No</td>
                                            <td class="unit " style="text-align:right;">Customer Name</td>
                                            <td class="unit text-right" style="text-align:right;">Invoice Date</td>
                                            <td class="qty text-right" style="text-align:right;">Total</td>
                                          
                                        </tr>
                                        </thead>';



                                         $output .= '                 <tbody>
                                                                                                                        <tr>
                                                <td class="no" style="border-bottom: 1px solid #ccc; text-align:left;"></td>
                                                <td class="desc" style="border-bottom: 1px solid #ccc;text-align:left;">'.$query['invoice_h_code'].'</td>
                                                <td class="unit text-right" style="border-bottom: 1px solid #ccc;">'.$query_customer['customer_name'].'</td>
                                                <td class="unit text-right" style="border-bottom: 1px solid #ccc;">'.$query['invoice_h_date'].'</td>
                                                <td class="qty text-right" style="border-bottom: 1px solid #ccc;">LKR <span class="grand_tot">'.$query['invoice_h_gross_value'].'</span></td>
                                                
                                            </tr>';
                                        
                                        
                                        
                                                                                 
                                        
                                        

                                      $output .= '  </tbody> </table>';

       }
     echo $output; 
                   



              

?>









