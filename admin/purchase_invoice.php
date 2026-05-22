<?php 
ob_start();
error_reporting (E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');
include('get_url.php');


?>
<?php

if(isset($_GET['id']))
{

    $get_id = $_GET['id'];

     if(!empty($get_id) && $get_id > 0)
     {

    $db = new Database();
    $query_vat_id = $db->getRow('SELECT * FROM product_vat_master ORDER BY id DESC LIMIT 1');
        if($query_vat_id)
        {
            $item_vat_value = $query_vat_id['rate'];

        }
    $query_grn_h_id = $db->getRow('SELECT * FROM grn_hedder WHERE grn_h_id = ?',[$get_id]);
    $grn_h_id = $query_grn_h_id['grn_h_id'];

        if($grn_h_id == $get_id)
        {

            $grn_code = $query_grn_h_id['grn_h_code'];
            $supplier_id = $query_grn_h_id['grn_h_supplier_id'];
            $pay_type = $query_grn_h_id['grn_h_pay_type'];
            $net_value = $query_grn_h_id['grn_h_net_value'];
            $vat_value = $query_grn_h_id['grn_h_vat_value'];
            $gross_value = $query_grn_h_id['grn_h_gross_value'];
            $date = $query_grn_h_id['grn_h_date'];
            $location_id = $query_grn_h_id['grn_h_location'];


            //wena location ekaka ekaknam redirect karnawa
             if($_SESSION['location'] != $location_id)
                        {
                            redirect('index.php');
                        }


                //supplier hoyagannawa

            if($supplier_id)
            {
                $query_supplier = $db->getRow('SELECT * FROM supplier WHERE supplier_id = ?',[$supplier_id]);
                $supplier_name = $query_supplier['supplier_name'];
                $supplier_address = $query_supplier['supplier_address'];
                $supplier_No = $query_supplier['supplier_contact_no'];
                $supplier_email = $query_supplier['supplier_email'];

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
                $query = $db->getRows('SELECT * FROM item_master itm JOIN grn_details grn  ON grn.grn_d_item_id = itm.item_id WHERE grn.grn_h_id = ?',[$get_id]);
                return $query;
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
        <title>Invoice</title>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta content="width=device-width, initial-scale=1" name="viewport" />
        <meta content="" name="description" />
        <meta content="" name="author" />
        <?php include('common/head.php'); ?>
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
                                <a href="#">Purchase</a>
                                <i class="fa fa-circle"></i>
                            </li>
                            <li>
                                <span>Invoice</span>
                            </li>
                        </ul>
                      
                    </div>
                    <!-- END PAGE BAR -->
                   
                    <!-- END PAGE HEADER-->
                  
                    <div class="row">
                        <div class="col-md-12">
                       <div class="invoice">
                        <div class="row invoice-logo" style="padding-top:10px;">
                            <div class="col-xs-6 invoice-logo-space">
                                <img src="assets/layouts/layout/img/invoice_logo.png" class="img-responsive" alt=""> </div>
                            <div class="col-xs-6">
                                <p><small>No#</small> <?php echo $grn_code;?> 
                                    <span class="muted"> <?php echo $date; ?> </span>
                                </p>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-xs-4">
                                <h3>Supplier:</h3>
                                <ul class="list-unstyled">
                                    <li><strong><?php echo $supplier_name;?></strong></li>
                                    <li><?php echo $supplier_address;?></li>
                                    <li><?php echo $supplier_No;?></li>
                                    <li><?php echo $supplier_email;?></li>
                                </ul>
                            </div>
                           <div class="col-xs-4">
                               <!--  <h3>About:</h3>
                                <ul class="list-unstyled">
                                    <li> Drem psum dolor sit amet </li>
                                    <li> Laoreet dolore magna </li>
                                    <li> Consectetuer adipiscing elit </li>
                                    <li> Magna aliquam tincidunt erat volutpat </li>
                                    <li> Olor sit amet adipiscing eli </li>
                                    <li> Laoreet dolore magna </li>
                                </ul> -->
                            </div>
                            <div class="col-xs-4 invoice-payment">
                             <!--    <h3>Payment Details:</h3>
                                <ul class="list-unstyled">
                                    <li>
                                        <strong>V.A.T Reg #:</strong> 542554(DEMO)78 </li>
                                    <li>
                                        <strong>Account Name:</strong> FoodMaster Ltd </li>
                                    <li>
                                        <strong>SWIFT code:</strong> 45454DEMO545DEMO </li>
                                    <li>
                                        <strong>V.A.T Reg #:</strong> 542554(DEMO)78 </li>
                                    <li>
                                        <strong>Account Name:</strong> FoodMaster Ltd </li>
                                    <li>
                                        <strong>SWIFT code:</strong> 45454DEMO545DEMO </li>
                                </ul>  -->
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            
                                            <th> Item </th>
                                            
                                            <th class="hidden-xs"> Quantity </th>
                                            <th class="hidden-xs"> Unit Cost </th>
                                            <th> Total </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $data = getContent();
                                        foreach($data as $query) {$typeid = $query['item_id']; $item_name = $query['item_name']; ?> 
                                                
                                        <tr>
                                            
                                            <td><?php echo $query['item_name']; ?></td>
                                            
                                            <td class="hidden-xs"><?php echo $query['grn_d_qty']; ?></td>
                                            <td class="hidden-xs"><?php include('currency.php');?> <?php echo number_format((float)$query['item_purchase_price'],2,'.','') ; ?> </td>
                                            <td> <?php include('currency.php');?> <?php echo number_format((float)$query['grn_d_total'],2,'.','') ; ?> </td>
                                        </tr>

                                      <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="row">
                          <div class="col-xs-4">
                         <!--         <div class="well">
                                    <address>
                                        <strong>Loop, Inc.</strong>
                                        <br> 795 Park Ave, Suite 120
                                        <br> San Francisco, CA 94107
                                        <br>
                                        <abbr title="Phone">P:</abbr> (234) 145-1810 </address>
                                    <address>
                                        <strong>Full Name</strong>
                                        <br>
                                        <a href="mailto:#"> first.last@email.com </a>
                                    </address>
                                </div> -->
                            </div>
                            <div class="col-xs-8 invoice-block">
                                <ul class="list-unstyled amounts">
                                    <li>
                                        <strong>Sub - Total amount:</strong> <?php include('currency.php');?> <?php echo $gross_value;?> </li>
                                  <!-- <li>
                                        <strong>Discount:</strong> 0.00% </li> -->
                                    <li> 
                                        <strong>VAT+: </strong> <?php include('currency.php');?> <?php echo $vat_value;?> </li> 

                                    <li>
                                        <strong>Grand Total:</strong> <?php include('currency.php');?> <?php echo $net_value;?></li>
                                </ul>
                                <br>
                                <a class="btn btn-lg blue hidden-print margin-bottom-5" onclick="javascript:window.print();"> Print
                                    <i class="fa fa-print"></i>
                                </a>
                                <a class="btn btn-lg green hidden-print margin-bottom-5"> Submit Your Invoice
                                    <i class="fa fa-check"></i>
                                </a>
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
    <script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','www.google-analytics.com/analytics.js','ga');
  ga('create', 'UA-37564768-1', 'keenthemes.com');
  ga('send', 'pageview');
</script>
</body>

</html>



