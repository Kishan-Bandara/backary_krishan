<?php 
ob_start();
error_reporting (E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');


?>
<?php

if(isset($_GET['id']))
{

    $get_id = $_GET['id'];

     if(!empty($get_id) && $get_id > 0)
     {

        $location = $_SESSION['location'];

    $db = new Database();
    $query_vat_id = $db->getRow('SELECT * FROM product_vat_master ORDER BY id DESC LIMIT 1');
        if($query_vat_id)
        {
            $item_vat_value = $query_vat_id['rate'];

        }
    $query_invoice_h_id = $db->getRow('SELECT * FROM invoice_hedder WHERE invoice_h_id = ?',[$get_id]);
    $invoice_h_id = $query_invoice_h_id['invoice_h_id'];

        if($invoice_h_id == $get_id)
        {

            $invoice_code = $query_invoice_h_id['invoice_h_code'];
            $pay_type = $query_invoice_h_id['invoice_h_pay_type'];
            $net_value = $query_invoice_h_id['invoice_h_net_value'];
            $vat_value = $query_invoice_h_id['invoice_h_vat_value'];
            $gross_value = $query_invoice_h_id['invoice_h_gross_value'];
            $date = $query_invoice_h_id['invoice_h_datetime'];
            $delivery_charge = $query_invoice_h_id['invoice_h_delivery_cost'];
            $delivery_mode = $query_invoice_h_id['invoice_h_delivery_mode'];
            $customer_id = $query_invoice_h_id['invoice_h_customer_id'];
            $inv_added = $query_invoice_h_id['add_by'];
            $inv_location = $query_invoice_h_id['invoice_h_delivery_city'];
            $customer_address = $query_invoice_h_id['invoice_h_delivery_address'];
                


                                            $query_customer_name = $db->getRow('SELECT * FROM customer WHERE customer_id =?',[$customer_id]);
                                            $customer_name = $query_customer_name['customer_name'];

                                            if($delivery_mode){

                                             $query_delivery_mode = $db->getRow('SELECT * FROM delivery_master WHERE id =?',[$delivery_mode]);
                                            $delivery_mode_name = $query_delivery_mode['method'];

                                             $query_location = $db->getRow('SELECT * FROM city_master WHERE id =?',[$inv_location]); 
                                             $inv_location = $query_location['city'];
                                            }
            

            // paytype eka hoyagannawa
             if($pay_type)
            {
                $query_paytype = $db->getRow('SELECT * FROM payment_method WHERE id = ?',[$pay_type]);
                $pay_type = $query_paytype['type'];

            }

            //item tika loop karanwa

              function getContent() {
                $get_id = $_GET['id'];
                $db = new Database();
                $query = $db->getRows('SELECT * FROM item_master itm JOIN invoice_details inv  ON inv.invoice_d_item_id = itm.item_id WHERE inv.invoice_h_id = ?',[$get_id]);
                return $query;
            }

            //get Location details 

            $query_location = $db->getRow('SELECT * FROM location_master WHERE id = ?',[$location]);
            $location_address = $query_location['address'];

            // Load invoice/receipt settings
            $invoice_logo = 'assets/layouts/layout/img/logo.avif';
            $receipt_name = '';
            $receipt_email = '';
            $receipt_phone = '';
            $receipt_address = '';
            try {
                $s = $db->getRow('SELECT * FROM invoice_settings WHERE id = 1');
                if ($s) {
                    if (!empty($s['invoice_logo']) && file_exists($s['invoice_logo'])) {
                        $invoice_logo = $s['invoice_logo'];
                    }
                    $receipt_name = $s['receipt_name'] ?? '';
                    $receipt_email = $s['receipt_email'] ?? '';
                    $receipt_phone = $s['receipt_phone'] ?? '';
                    $receipt_address = $s['receipt_address'] ?? '';
                }
            } catch(Exception $e) {
                // ignore
            }

        }
        else
        {
            echo "Invoice ID did not match.";

        }

     }
     else
     {

        echo"wrong invoice ID.";

     }

}




?>
<!DOCTYPE html>

<!--[if IE 8]> <html lang="en" class="ie8 no-js"> <![endif]-->
<!--[if IE 9]> <html lang="en" class="ie9 no-js"> <![endif]-->
<!--[if !IE]><!-->
<html lang="en">
    <!--<![endif]-->
    <!-- BEGIN HEAD -->


<head>
        <meta charset="utf-8" />
        <title>Receipt</title>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta content="width=device-width, initial-scale=1" name="viewport" />
        <meta content="" name="description" />
        <meta content="" name="author" />
        <?php include('common/head.php'); ?>
        <style>
            .invoice{
                  font-family: Roboto,sans-serif;
                 font-weight: 400;
                 font-style: none;
            }
        .table > thead:first-child > tr:first-child > th, .table > thead:first-child > tr:first-child > td, .table-striped thead tr.primary:nth-child(2n+1) th {
    background-color: transparent;
    border-color:transparent;
    border-top: 0px solid ;
    vertical-align: bottom;
    border-bottom: 2px solid #ddd;
    color: black;
    text-align: left;
}

#wrapper { min-width: 250px; margin: 0 auto; }
#wrapper img { max-width: 300px; width: auto; }

h2, h3, p { margin: 5px 0; }
.left { width:60%; float:left; text-align:left; margin-bottom: 3px; }
.right { width:40%; float:right; text-align:right; margin-bottom: 3px; }
.table, .totals { width: 100%; margin:10px 0; }
.table th { border-bottom: 1px solid #000; }
.table td { padding:0; }
.totals td { width: 24%; padding:0; }
.table td:nth-child(2) { overflow:hidden; }

@media print {
    body { text-transform: uppercase; }
    #buttons { display: none; }
    #wrapper { width: 100%; margin: 0; font-size:9px; }
    #wrapper img { max-width:300px; width: 80%; }
}

        </style>
       </head>
    <!-- END HEAD -->

    <body class="page-sidebar-closed-hide-logo page-content-white">
      <?php include('common/manubar.php'); ?>
        <!-- BEGIN HEADER & CONTENT DIVIDER -->
        <div class="clearfix"> </div>
        <!-- END HEADER & CONTENT DIVIDER -->
        <!-- BEGIN CONTAINER -->
        <div class="page-container">
             <div class="page-sidebar-wrapper">
           <?php include('common/sidebar.php'); ?>
            
            </div>
            <!-- END SIDEBAR -->
            <!-- BEGIN CONTENT -->
            <div class="page-content-wrapper">
                <!-- BEGIN CONTENT BODY -->
                <div class="page-content">
                    <!-- BEGIN PAGE HEADER-->
          
                    <!-- BEGIN PAGE BAR -->
                    <div class="page-bar">
                        <ul class="page-breadcrumb">
                            <li>
                                <a href="">Home</a>
                                <i class="fa fa-circle"></i>
                            </li>
                            <li>
                                <a href="#">Sales</a>
                                <i class="fa fa-circle"></i>
                            </li>
                            <li>
                                <span>Receipt</span>
                            </li>
                        </ul>
                      
                    </div>
                    <!-- END PAGE BAR -->
                   
                    <!-- END PAGE HEADER-->
                  
                    <div class="row">
                        <div class="col-md-12">
                       <div class="invoice">
                       <div class="container">
   
                  <div id="wrapper" style="max-width: 400px; margin:0 auto; text-align:center; color:#000; font-family: Arial, Helvetica, sans-serif; font-size:12px;">

    <?php if (!empty($invoice_logo) && file_exists($invoice_logo)) { ?>
        <img src="<?php echo htmlspecialchars($invoice_logo); ?>?t=<?php echo time(); ?>" alt="Logo" />
    <?php } ?>
    <h2><strong><?php echo htmlspecialchars($receipt_name ?: 'Your Business Name'); ?></strong></h2>
    <?php if ($receipt_phone || $receipt_address || $receipt_email) { ?>
        <p>
            <?php if ($receipt_phone) { echo htmlspecialchars($receipt_phone) . '<br>'; } ?>
            <?php if ($receipt_address) { echo nl2br(htmlspecialchars($receipt_address)) . '<br>'; } ?>
            <?php if ($receipt_email) { echo htmlspecialchars($receipt_email); } ?>
        </p>
    <?php } ?>
    <span class="left">Location : <?php echo $inv_location; ?></span> 
    <span class="right">Inv No.: <?php echo $invoice_code;?></span>
    <span class="left">Customer: <?php echo $customer_name; ?></span> 
    <span class="right">Date: <?php echo $date; ?></span>    <div style="clear:both;"></div>
   <?php if($customer_address){ ?> <span class="left">Address: <?php echo $customer_address; ?></span> <?php } ?>




    <table class="table" cellspacing="0" border="0"  style="margin:3px;"> 
    <thead> 
    <tr> 
        <th><em>#</em></th> 
        <th>Item</th> 
        <th style="text-align:right;">Price</th> 
        <th style="text-align:right;">Qty</th>
        <th style="text-align:right;">Amount</th> 
    </tr> 
    </thead> 
    <tbody>      
        <?php $data = getContent();
        $i = 0;
                                        foreach($data as $query) {
                                            $typeid = $query['item_id']; 
                                            $item_name = $query['item_name']; 
                                            $item_price = $query['invoice_d_item_price'];
                                            $item_qty = $query['invoice_d_qty'];
                                            $item_qty = $query['invoice_d_qty'];
                                            $invoice_d_item_total=$query['invoice_d_item_total'];
                                            $invoice_d_discount_value=$query['invoice_d_discount_value'];
                                            if($invoice_d_discount_value>0){
                                                $discount_value = ($item_price * $invoice_d_discount_value) / 100;
                                                $item_priceWithDiscount = $item_price- $discount_value;
                                            }else{
                                                $item_priceWithDiscount = $item_price;
                                            }
                                           
                                            $i = $i + 1;

                                            ?> 

              <tr>
        <td style="text-align:left;      padding: 2px; font-size:12px;" colspan="1"><?php echo $i; ?></td>    
        <td style="text-align:left;      padding: 2px; font-size:12px;" colspan="5"><?php echo $query['item_name']; ?></td>

        <tr>
            
             <td style="text-align:left; font-size:12px;  padding: 2px;  padding-left:10px; " colspan="2"><i><?php  $query['item_code'];; ?></i></td>
            <td style="text-align:right;   font-size:12px;  padding: 2px;" ><?php include('currency.php');?> <?php echo number_format($item_priceWithDiscount,2); ?></td>
            <td  style="text-align:center; font-size:12px;    padding: 2px;"><?php echo $query['invoice_d_qty']; ?></td>
            <td  style="text-align:right; font-size:12px;    padding: 2px;" ><?php include('currency.php');?> <?php echo number_format($invoice_d_item_total,2); ?></td>
        </tr>
         </tr>



            <?php } ?>
            
        </tbody> 
    </table> 
    
    <table class="totals" cellspacing="0" border="0" style="margin-bottom:5px;">
    <tbody>

    <tr>
    <td style="text-align:left;"></td><td style="text-align:right; padding-right:1.5%; border-right: 1px solid #000;font-weight:bold;"></td>
    <td style="text-align:left; padding-left:1.5%;"></td><td style="text-align:right;font-weight:bold;"></td>
    </tr>
    <tr>
    <td colspan="2" style="text-align:left; font-weight:bold; border-top:1px solid #000; padding-top:5px;">Gross Amount</td><td colspan="2" style="border-top:1px solid #000; padding-top:5px; text-align:right; font-weight:bold;"><?php include('currency.php');?> <?php echo  $gross_value; ?></td>
    </tr>
   <tr>    
    <td colspan="2" style="text-align:left; font-weight:bold; padding-top:5px;">Promotion Discount</td><td colspan="2" style="padding-top:5px; text-align:right; font-weight:bold;"><?php include('currency.php');?> <?php echo $query_invoice_h_id['invoice_h_coupon_value'];; ?></td>
    </tr>
    <tr>    
    <td colspan="2" style="text-align:left; font-weight:bold; padding-top:5px;">Delivery Charge</td><td colspan="2" style="padding-top:5px; text-align:right; font-weight:bold;"><?php include('currency.php');?> <?php echo $delivery_charge; ?></td>
    </tr>

    <tr><td colspan="2" style="text-align:left; font-weight:bold; padding-top:5px;">Net Amount</td><td colspan="2" style="padding-top:5px; text-align:right; font-weight:bold;"><?php include('currency.php');?> <?php echo $net_value;  ?></td>
    </tr>
        
    </tbody>
    </table>
        
    <div style="border-top:1px solid #000; padding-top:10px;">
        <p style="text-align:right;margin: 0px 0; font-size:8.5px;"><?php if($inv_added) { ?> Agent : <?php echo $inv_added; } ?> </p><br>
        <p style="text-align:left;margin: 0px 0; font-size:8.5px;">Customer Signature : ......................................... </p><br><br>
        <p style="text-align:center;margin: 0px 0; font-weight:bold;">Thank you for your business!</p>   
        <p style="padding:10px; text-align: inherit; margin: 0px 0; padding: 1px;font-size:10px; font-weight:bold;">IMPORTANT NOTICE: In case of price discrepancy,return the item & the bill within 7 days for refund of difference </p>
        <p style="text-align:center;margin: 0px 0;"> --------</p>
        <p style="text-align:center;margin: 0px 0;font-size:8.5px;"> Copyright © <?php echo date("Y");?>. Regoora.com.</p>
         </div>
    
    <div id="buttons" style="padding-top:10px; text-transform:uppercase;">
    <span class="left"><a href="" style="width:90%; display:block; font-size:12px; text-decoration: none; text-align:center; color:#000; background-color:#4FA950; border:2px solid #4FA950; padding: 10px 1px; font-weight:bold;" id="email">Email</a></span>
    <span class="right"><button type="button" onclick="window.print();return false;" style="width:100%; cursor:pointer; font-size:12px; background-color:#FFA93C; color:#000; text-align: center; border:1px solid #FFA93C; padding: 10px 1px; font-weight:bold;">Print</button></span>
    <div style="clear:both;"></div>
    <a href="pos.php" style="width:95%; display:block; font-size:12px; text-decoration: none; text-align:center; color:#FFF; background-color:#007FFF; border:2px solid #007FFF; padding: 10px 1px; margin: 5px auto 10px auto; font-weight:bold;">Back to POS</a>
    
   
    <div style="clear:both;"></div>
    </div>
</div>     
                    </div>
                        </div>
                        
                    </div>
                  
                </div>
                <!-- END CONTENT BODY -->
            </div>
            <!-- END CONTENT -->
          
        </div>
        <!-- END CONTAINER -->
    <?php include('common/footer.php');?>
        <!--[if lt IE 9]>
<script src="assets/global/plugins/respond.min.js"></script>
<script src="assets/global/plugins/excanvas.min.js"></script> 
<![endif]-->
        <!-- BEGIN CORE PLUGINS -->
        <script src="assets/global/plugins/jquery.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/js.cookie.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/bootstrap-hover-dropdown/bootstrap-hover-dropdown.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/jquery-slimscroll/jquery.slimscroll.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/jquery.blockui.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/uniform/jquery.uniform.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/bootstrap-switch/js/bootstrap-switch.min.js" type="text/javascript"></script>
        <!-- END CORE PLUGINS -->
        <!-- BEGIN PAGE LEVEL PLUGINS -->
        <script src="assets/global/scripts/datatable.js" type="text/javascript"></script>
        <script src="assets/global/plugins/datatables/datatables.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.js" type="text/javascript"></script>
        <!-- END PAGE LEVEL PLUGINS -->
        <!-- BEGIN THEME GLOBAL SCRIPTS -->
        <script src="assets/global/scripts/app.min.js" type="text/javascript"></script>
        <!-- END THEME GLOBAL SCRIPTS -->
        <!-- BEGIN PAGE LEVEL SCRIPTS -->
        <script src="assets/pages/scripts/table-datatables-responsive.min.js" type="text/javascript"></script>
        <!-- END PAGE LEVEL SCRIPTS -->
        <!-- BEGIN THEME LAYOUT SCRIPTS -->
        <script src="assets/layouts/layout/scripts/layout.min.js" type="text/javascript"></script>
        <script src="assets/layouts/layout/scripts/demo.min.js" type="text/javascript"></script>
        <script src="assets/layouts/global/scripts/quick-sidebar.min.js" type="text/javascript"></script>
        <!-- END THEME LAYOUT SCRIPTS -->
  
</body>

</html>



