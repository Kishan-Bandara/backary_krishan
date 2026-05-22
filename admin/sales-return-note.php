<?php 
ob_start();
error_reporting (E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');
include('get_url.php');


?>
<?php
#currency
$getcurrency = $db->getRow('SELECT * FROM currency WHERE activated = ? LIMIT 1 ',["Y"]);
$currency = $getcurrency['currency'];
$location = $_SESSION['location'];
if(isset($_GET['id']) && $_GET['id'] > 0)
{

    $get_id = $_GET['id'];

     if(!empty($get_id) && $get_id > 0)
     {

    $db = new Database();
   
    $query_srn_h_id = $db->getRow('SELECT * FROM sales_return_hedder WHERE sales_return_h_id = ?',[$get_id]);
    $srn_h_id = $query_srn_h_id['sales_return_h_id'];

        if($srn_h_id == $get_id)
        {

            $srs_h_code = $query_srn_h_id['sales_return_h_code'];
            $srs_h_invoice = $query_srn_h_id['sales_return_h_invoice'];
            $srs_h_total = $query_srn_h_id['sales_retrun_h_total'];
            $srs_date = $query_srn_h_id['sales_retrun_h_date'];
            $srs_added_by = $query_srn_h_id['sales_return_user'];
            $srs_location_id = $query_srn_h_id['sales_return_location'];
            $invoice_id = $query_srn_h_id['sales_return_h_invoice'];

            //get Location details 

            $query_location = $db->getRow('SELECT * FROM location_master WHERE id = ?',[$location]);
            $location_address = $query_location['address'];
   
           


            // get customer details 
            if($srs_h_invoice){

                $query_customer = $db->getRow('SELECT * FROM customer itm JOIN invoice_hedder grn ON grn.invoice_h_customer_id = itm.customer_id WHERE grn.invoice_h_id = ?',[$invoice_id]);
                $customer_name = $query_customer['customer_name'];

                $customer_address = $query_customer['customer_address'];

                if($customer_address){

                    $customer_address = $query_customer['customer_address'];
                }else{

                    $customer_address = "-";
                }

            }
            //item tika loop karanwa

              function getContent() {
                $get_id = $_GET['id'];
                $db = new Database();
                $query = $db->getRows('SELECT * FROM invoice_details invd JOIN sales_return_details srn  ON srn.sales_return_d_invoice_item = invd.invoice_d_id WHERE srn.sales_return_d_h_id = ?',[$get_id]);

                return $query;
            }



        }
        else
        {
           redirect('index.php');

        }

     }
     else
     {

        redirect('index.php');

     }

}else{

    redirect('index.php');
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
        <title>Sales Return note</title>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta content="width=device-width, initial-scale=1" name="viewport" />
        <meta content="" name="description" />
        <meta content="" name="author" />
        <?php include('common/head.php'); ?>
         <!-- BEGIN PAGE LEVEL STYLES -->
        <link href="assets/pages/css/invoice-2.min.css" rel="stylesheet" type="text/css" />
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
                                <a href="index-2.html">Home</a>
                                <i class="fa fa-circle"></i>
                            </li>
                            <li>
                                <a href="#">Sales Return</a>
                                <i class="fa fa-circle"></i>
                            </li>
                            <li>
                                <span>Note</span>
                            </li>
                        </ul>
                      
                    </div>
                    <!-- END PAGE BAR -->
                   
                    <!-- END PAGE HEADER-->
                  
                    <div class="row">
                        <div class="col-md-12">
                      <div class="invoice-content-2 bordered">
                        <div class="row invoice-head">
                            <div class="col-md-7 col-xs-6">
                                <div class="invoice-logo">
                                    <img src="assets/layouts/layout/img/invoice_logo.png" class="img-responsive" alt="">
                                    <h1 class="uppercase">Sales Return Note</h1>
                                </div>
                            </div>
                            <div class="col-md-5 col-xs-6">
                                <div class="company-address">
                                  
                            </div>
                        </div>
                        <div class="row invoice-cust-add">
                            <div class="col-xs-3">
                                <h2 class="invoice-title uppercase">Customer</h2>
                                <p class="invoice-desc"><?php echo $customer_name; ?></p>
                            </div>
                            <div class="col-xs-3">
                                <h2 class="invoice-title uppercase">Date</h2>
                                <p class="invoice-desc"><?php echo $srs_date; ?></p>
                            </div>
                            <div class="col-xs-6">
                                <h2 class="invoice-title uppercase">Address</h2>
                                <p class="invoice-desc inv-address"><?php echo $customer_address; ?></p>
                            </div>
                        </div>
                        <div class="row invoice-body">
                            <div class="col-xs-12 table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th class="invoice-title uppercase">Description</th>
                                            <th class="invoice-title uppercase text-center">item Rate</th>
                                            <th class="invoice-title uppercase text-center">Qty</th>
                                            <th class="invoice-title uppercase text-center">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                         <?php $data = getContent();
                                        foreach($data as $query)
                                         { 
                                            $itm_id = $query['sales_return_d_item_id'];

                                            $query_itm_info = $db->getRow('SELECT * FROM item_master WHERE item_id = ?',[$itm_id]);
                                            $item_name = $query_itm_info['item_name'];
                                            $item_code = $query_itm_info['item_code'];
                                            $item_description = $query_itm_info['item_discription'];

                                            ?> 
                                        <tr>
                                            <td style="font-size:12px;">
                                                <h3><?php echo $item_name." - ".$item_code; ?></h3>
                                                
                                            </td>
                                            <td class="text-center sbold" style="font-size:12px;"><?php echo $currency.$query['sales_return_d_rate']; ?></td>
                                            <td class="text-center sbold" style="font-size:12px;"><?php echo $query['sales_return_d_qty']; ?></td>
                                            <td class="text-center sbold" style="font-size:12px;"><?php echo $currency.$query['invoice_d_item_price']; ?></td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="row invoice-subtotal">
                            <div class="col-xs-3">
                                <h2 class="invoice-title uppercase" style="margin-bottom: 1px;">...................................</h2>
                                <p class="invoice-desc" style="margin-top: 0px;">Prepaired By</p>
                            </div>
                            <div class="col-xs-3">
                                <h2 class="invoice-title uppercase" style="margin-bottom: 1px;">...................................</h2>
                                <p class="invoice-desc" style="margin-top: 0px;">Approved By</p>
                            </div>
                            <div class="col-xs-6" style="text-align:right;">
                                <h2 class="invoice-title uppercase" style="margin-bottom: 1px;">Total</h2>
                                <p class="invoice-desc grand-total" style="margin-top: 1px;"><?php echo $currency." ".$srs_h_total; ?></p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12">
                                <a class="btn btn-lg green-haze hidden-print uppercase print-btn" onclick="javascript:window.print();">Print</a>
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



