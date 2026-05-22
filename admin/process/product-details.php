<?php 
ob_start();
error_reporting (E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');
?>



<?php
#currency
$getcurrency = $db->getRow('SELECT * FROM currency WHERE activated = ? LIMIT 1 ',["Y"]);
$currency = $getcurrency['currency'];

    $get_product_id = $_GET['pid'];
    if(!empty($get_product_id))
    {
    $db = new Database();
    $get_real_produt_id = $db->getRow('SELECT * FROM item_master WHERE item_id = ? ',[$get_product_id]);
    $real_product_id = $get_real_produt_id['item_id'];

    }
    else
    {

        header('location:manage-product.php');
        exit();
    }

   

    if($get_product_id > 0 && $get_product_id == $real_product_id) {

            $product_name = $get_real_produt_id['item_name'];
            $product_code = $get_real_produt_id['item_code'];
            $product_cost_price = $get_real_produt_id['item_purchase_price'];
            $product_min_price = $get_real_produt_id['item_min_selling_price'];
            $product_normal_price = $get_real_produt_id['item_normal_selling_price'];
            $product_cash_price = $get_real_produt_id['item_cash_selling_price'];
            $product_credit_price = $get_real_produt_id['item_cradit_selling_price'];

           

                   
               

                function itemParth()
                {
                    $get_product_id = $_GET['pid'];
                    $db = new Database();
                    $get_real_produt_id = $db->getRow('SELECT * FROM item_master WHERE item_id = ? ',[$get_product_id]);
                     $product_category_id = $get_real_produt_id['item_category'];
                     $product_group_id = $get_real_produt_id['item_group'];
                     $product_type_id = $get_real_produt_id['item_type'];
                        if($product_category_id)
                        {
                            #category
                            $get_real_category_id = $db->getRow('SELECT * FROM category_master WHERE  category_id = ? ',[$product_category_id]);
                            $product_category = $get_real_category_id['category_name'];
                            #group
                            $get_real_group_id = $db->getRow('SELECT * FROM gorup_master WHERE  group_id = ? ',[$product_group_id]);
                             $product_group = $get_real_group_id['group_name'];
                            #type
                            $get_real_type_id = $db->getRow('SELECT * FROM type_master WHERE  type_id = ? ',[$product_type_id]);
                            $product_type = $get_real_type_id['type_name'];
                            $db->Disconnect();
                           return $item_parth = $product_group." --> ".$product_type." --> ".$product_category;
                        }


                    

                }

                function itemUnit()
                    {
                        $get_product_id = $_GET['pid'];
                        $db = new Database();
                        $get_real_produt_id = $db->getRow('SELECT * FROM item_master WHERE item_id = ? ',[$get_product_id]);
                        $product_unit_id = $get_real_produt_id['item_uom'];

                        if($product_unit_id)
                        {

                                $get_real_uom_id = $db->getRow('SELECT * FROM item_uom WHERE  uom_id = ? ',[$product_unit_id]);
                                $db->Disconnect();
                                return $product_uom = $get_real_uom_id['uom_name'];

                        }



                    }

                function warranty()
                    {
                        $get_product_id = $_GET['pid'];
                        $db = new Database();
                        $get_real_produt_id = $db->getRow('SELECT * FROM item_master WHERE item_id = ? ',[$get_product_id]);
                        $product_warranty_id = $get_real_produt_id['item_warranty'];

                         if($product_warranty_id)
                        {

                                $get_real_warranty_id = $db->getRow('SELECT * FROM item_warranty WHERE  warranty_id = ? ',[$product_warranty_id]);
                                $db->Disconnect();
                                return $product_warranty = $get_real_warranty_id['warranty'];

                        }


                    }


                 function itemVat()
                    {
                        $get_product_id = $_GET['pid'];
                        $db = new Database();
                        $get_real_produt_id = $db->getRow('SELECT * FROM item_master WHERE item_id = ? ',[$get_product_id]);
                        $product_vat_id = $get_real_produt_id['item_vat'];
                         $db->Disconnect();
                        if($product_vat_id == "Y")
                        {

                            $product_vat = "Included";
                        }
                        elseif($product_vat_id == "N")
                        {

                            $product_vat = "No Vat 0.00%";

                        }
                        else
                        {

                            $product_vat = "Someting wrrong";

                        }
                        return $product_vat;

                    }

                    function description()
                    {

                        $get_product_id = $_GET['pid'];
                        $db = new Database();
                        $get_real_produt_id = $db->getRow('SELECT * FROM item_master WHERE item_id = ? ',[$get_product_id]);
                        $product_description = $get_real_produt_id['item_discription'];

                        if($product_description)
                        {
                            return $product_description;

                        }
                        else
                        {

                            return "There're no Product Description";

                        }
                         $db->Disconnect();


                    }


                    function qty()

                    {


                       
                        $get_product_id = $_GET['pid'];
                        $db = new Database();
                        $query_get_qty = $db->getRows('SELECT SUM(ft_blanace) as qty ,ft_location  FROM fifo WHERE ft_item = ? GROUP BY ft_location',[$get_product_id]);
                            
                        return $query_get_qty ; 

                    }

                      function getContent() {
        $db = new Database();
        $query_invoice = $db->getRows('SELECT invH.invoice_h_id,invH.invoice_h_code , invH.invoice_h_customer_id , invH.invoice_h_date , invH.invoice_h_net_value , invH.add_by ,invH.invoice_h_status,invH.add_by, invH.invoice_h_location ,invD.invoice_d_item_id , invD.invoice_d_qty , invD.invoice_d_item_price , invD.invoice_d_vat , invD.invoice_d_vat_rate , invD.invoice_d_discount_value , invD.invoice_h_id 
                                            FROM invoice_hedder invH 
                                            JOIN invoice_details invD 
                                            ON invH.invoice_h_id = invD.invoice_h_id 
                                            WHERE invD.invoice_d_item_id = ? 
                                            AND invH.invoice_h_location = ?
                                            AND invH.invoice_h_status = ?
                                            ORDER BY invD.invoice_d_item_id DESC LIMIT 10',[$_GET['pid'],$_SESSION['location'],1]);
        return $query_invoice;
    }
    }

    else
    {


        header('location:manage-product.php');
        exit();
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
        <title>Product Details</title>
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
                                <a href="#">Product</a>
                                <i class="fa fa-circle"></i>
                            </li>
                            <li>
                                <span>Product Details</span>
                            </li>
                        </ul>
                      
                    </div>
                    <!-- END PAGE BAR -->
                    <!-- BEGIN PAGE TITLE-->
                   <div class="alert alert-success alert-dismissable">
                                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                                         Thank you for trying Stock Manager System. We hope, you will like it. If you find any error/bug or suggestions, please call to +94 0775 489 978 or email to info@switchtech.lk
                                    </div>
                    <!-- END PAGE TITLE-->
                    <!-- END PAGE HEADER-->
                  
                    <div class="row">

                        <div class="col-md-12">
                            <div class="row">
  
  <div class="col-md-12 text-right"> <div class="btn-group btn-group-solid">

                                                               <div class="btn-group">

                                                <a title="" class="tip btn btn blue-madison" target="popup" href="print_barcode.php?pid=<?php echo $real_product_id; ?>" onclick="window.open('print_barcode.php?pid=<?php echo $real_product_id; ?>','name','width=auto,height=600')">

                                                </i> <span class="hidden-sm hidden-xs"> Print Barcode</span>

                                                </a>

                                                </div>
                                                             <div class="btn-group">

                                                <a title="" class="tip btn btn blue-madison" href="">

                                                <i class="fa fa-print"></i> <span class="hidden-sm hidden-xs"> Print Label</span>

                                                </a>

                                                </div>
                                                                    
                                                                <div class="btn-group">

                                                <a title="" class="tip btn btn-warning tip" href="edit-product.php?pid=<?php echo $real_product_id; ?> ">

                                                <i class="fa fa-edit"></i> <span class="hidden-sm hidden-xs">Edit</span>

                                                </a>

                                                </div>

                                                                      <div class="btn-group">

                                                <a title="" class="tip btn btn red" href="">

                                                <i class="fa fa-trash-o"></i> <span class="hidden-sm hidden-xs"> Delete</span>

                                                </a>

                                                </div>
                                                              
                                                            </div>
                           </div>
</div>
                         
                      
                             <div class="portlet-body">
                                <div class="tabbable-custom ">
                                        <ul class="nav nav-tabs ">
                                            <li class="active">
                                                <a href="#tab_5_1" data-toggle="tab" aria-expanded="true">Product Details </a>
                                            </li>
                                            <li class="">
                                                <a href="#tab_5_2" data-toggle="tab" aria-expanded="false"> Sales </a>
                                            </li>
                                            <li class="">
                                                <a href="#tab_5_3" data-toggle="tab" aria-expanded="false"> Purchases </a>
                                            </li>
                                        </ul>
                                        <div class="tab-content">
                                            <div class="tab-pane active" id="tab_5_1">
                                               <div class="portlet light bordered">
                                            <div class="portlet-title">
                                                <div class="caption">
                                                    <i class="fa-fw fa fa-file-text-o nb"></i>
                                                    <span class="caption-subject font-red-sunglo bold uppercase"><?php echo $product_name;?></span>
                                                    
                                                </div>
                                               
                                            </div>
                                            <div class="portlet-body form">
                                                <!-- BEGIN FORM-->
                                                <div class="row">
                                                    <div class="col-sm-7">
                                                        <div class="table-responsive">
                                    <table class="table table-borderless table-striped dfTable table-right-left">
                                        <tbody>
                                        <tr>
                                            <td colspan="2" style="background-color:#FFF;"></td>
                                        </tr>
                                        <tr>
                                            <td style="width:30%;">Barcode</td>
                                            <td style="width:70%;"><i><small><img src="barcode_process.php?pid=<?php echo $product_code;?>"></small> </i></td>
                                        </tr>
                                        <tr>
                                            <td>Product Name</td>
                                            <td style="text-align: left; font-weight: bold;"><?php echo $product_name;?></td>
                                        </tr>
                                        <tr>
                                            <td>Product Code</td>
                                            <td style="text-align: left; font-weight: bold;"><?php echo $product_code;?></td>
                                        </tr>
                                        <tr>
                                            <td>Product Category</td>
                                            <td style="text-align: left; font-weight: bold;"><?php echo itemParth(); ?></td>
                                        </tr>
                                        <tr>
                                            <td>Product Unit</td>
                                            <td style="text-align: left; font-weight: bold;"><?php echo itemUnit(); ?></td>
                                        </tr>
                                                                                <tr>
                                            <td>Product Cost Price</td>
                                            <td style="text-align: left; font-weight: bold;"><?php include('currency.php'); ?> <?php echo $product_cost_price; ?></td>
                                        </tr>
                                        <tr>
                                            <td>Product Minimum Selling Price</td>
                                            <td style="text-align: left; font-weight: bold;"><?php include('currency.php'); ?> <?php echo $product_min_price; ?></td>
                                            </tr>
                                        
                                        <tr>
                                            <td>Product Normal Selling Price</td>
                                            <td style="text-align: left; font-weight: bold;"><?php include('currency.php'); ?> <?php echo $product_normal_price; ?></td>
                                        </tr>
                                                                                    <tr>
                                                <td>Product Cash Selling Price</td>
                                                <td style="text-align: left; font-weight: bold;"><?php include('currency.php'); ?> <?php echo $product_cash_price; ?></td>
                                            </tr>
                                            <tr>
                                                <td>Product Credit Selling Price</td>
                                                <td style="text-align: left; font-weight: bold;"><?php include('currency.php'); ?> <?php echo $product_credit_price; ?></td>
                                            </tr>
                                                                                                                           
                                             </tr>
                                                                                                                            <tr>
                                                <td>Product Warranty</td>
                                                <td style="text-align: left; font-weight: bold;"><?php echo warranty(); ?></td>
                                            </tr>
                                                   </tr>
                                                                                                                            <tr>
                                                <td>+GST</td>
                                                <td style="text-align: left; font-weight: bold;"><?php echo itemVat(); ?></td>
                                            </tr>                              
                                        </tbody>
                                    </table>
                                </div>
                                                    </div>
                                                    <div class="col-sm-5">
                                                        <div class="table-responsive">
                                                <table class="table table-bordered table-striped table-condensed dfTable two-columns">
                                                    <thead>
                                                    <tr>
                                                        <th>Location Name</th>
                                                        <th>Quantity</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php $data = qty();
                                        foreach($data as $query_get_qty)
                                         { 
                                            
                                            $location_id = $query_get_qty['ft_location'];
                                            $query_location_id =  $db->getRow('SELECT * FROM location_master WHERE id = ?',[$location_id]);
                                            $location_name = $query_location_id['name'];

                                            ?>
                                                    <tr><td><?php echo $location_name; ?> </td><td><strong><?php echo $query_get_qty['qty']; ?></strong></td></tr>
                                                    <?php } ?>
                                                </tbody>
                                                </table>
                                            </div>
                                                    </div>
                                                    <div class="col-sm-12">

                                                                <div class="panel panel-primary"><div class="panel-heading">Product Description</div><div class="panel-body"><?php echo description(); ?></div></div>
                            </div>
                                                </div>
                                                <!-- END FORM-->

                                            </div>
                                        </div>
                                            </div>
                                            <div class="tab-pane" id="tab_5_2">
                                             <div class="portlet-body">
                                    <table class="table table-striped table-bordered table-hover dt-responsive" width="100%" id="sample_2">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th class="all">Invoice Date</th>
                                                <th class="all">Invoice No.</th>
                                                <th class="all">Customer Name</th>
                                                <th class="all">Biller</th>
                                                <th class="all">Product(Qty)</th>
                                                <th class="all">Grand Total</th>
                                                <th class="all">Location</th>
                                                <th class="all">Payment Status</th>
                                                
                                                
                                            
                                            </tr>
                                        </thead>
                                        <tbody>
                                              <?php $data = getContent();
                                        foreach($data as $query_invoice)
                                         { 
                                            $invoice_h_id = $query_invoice['invoice_h_id'];
                                            $customer_id = $query_invoice['invoice_h_customer_id'];
                                            $query_invoice_customer = $db->getRow('SELECT * FROM customer WHERE customer_id = ?',[$customer_id]);
                                            $query_invoice_amount = $db->getRow('SELECT *  FROM invoice_hedder WHERE invoice_h_id = ?',[$invoice_h_id]);
                                            $net_value = $query_invoice_amount['invoice_h_net_value'];
                                            $invoice_status = $query_invoice['invoice_h_status'];
                                            

                                            $query_customer_amount = $db->getRow('SELECT SUM(amount) as customer_amount FROM customer_balance WHERE invoice_h_id = ?',[$invoice_h_id]);
                                            $amount = $query_customer_amount['customer_amount'];
                                           

                                            $item_d_vat_rate = $query_invoice['invoice_d_vat_rate'];
                                            $item_d_vat_has = $query_invoice['invoice_d_vat'];
                                            $item_selling_price = $query_invoice['invoice_d_item_price'];
                                            $item_qty = $query_invoice['invoice_d_qty'];
                                            $invoice_location_id = $query_invoice['invoice_h_location'];
                                            $invoice_biller_id = $query_invoice['add_by'];

                                            $query_biller_id = $db->getRow('SELECT * FROM users WHERE userid = ?',[$invoice_biller_id]);
                                            $invoice_biller = $query_biller_id['first_name']." ".$query_biller_id['last_name'];

                                            $item_gross_value = ($item_selling_price * $item_qty);
                                            $invoice_d_vat_value = 0.00;
                                            if($item_d_vat_has == "Y"){

                                                $invoice_d_vat_value = ($item_gross_value * $item_d_vat_rate) / 100;

                                            }

                                            $query_location_id = $db->getRow('SELECT * FROM location_master WHERE id = ?',[$invoice_location_id]);
                                            $invoice_location_name  =  $query_location_id['name'];                                       

                                            $item_net_value = $item_gross_value + $invoice_d_vat_value;

                                            $query_item_select = $db->getRow('SELECT * FROM item_master WHERE item_id = ?',[$get_product_id]);
                                            $item_uom_id = $query_item_select['item_uom'];

                                            $query_uom = $db->getRow('SELECT uom_name uom FROM item_uom left join item_master on item_uom = uom_id WHERE item_uom = ?',[$item_uom_id]);
                                            $item_uom = $query_uom['uom'];
                                            
                                            if($query_invoice['invoice_d_qty'] > 1 && $item_uom_id == 1 ){

                                                $item_uom = "items";

                                            }

                                            if($item_d_vat_rate)
                                            {
                                                $item_d_vat_rate = $query_invoice['invoice_d_vat_rate']."%";

                                            }else{

                                                $item_d_vat_rate = "0.00%";

                                            }


                                           if($amount)
                                                {

                                                    $amount = $amount;
                                                }
                                                else
                                                {
                                                    $amount = 0;

                                                }

                                                $style = "";
                                                $status = "";
                                            if($net_value == $amount || $amount > $net_value )
                                            {

                                                $style = "lbl_Payment_status_paid";
                                                $status = "Paid";

                                            }
                                            elseif ($net_value > $amount && $amount != 0) {
                                                
                                                $style = "lbl_Payment_status_partial";
                                                $status = "Partial";
                                            }
                                            elseif ($amount == 0) {
                                                 $style = "lbl_Payment_status_pending";
                                                 $status = "Pending";
                                            }
                                            else
                                            {

                                                 $style = "lbl_Payment_status_pending";
                                                 $status = "Error";
                                            }

                                                #order Status

                                                if($invoice_status == 1)
                                                {
                                                    $order_status = "Acepct";

                                                }
                                                elseif($invoice_status == 0)
                                                {
                                                    $order_status = "Pending";

                                                }
                                                elseif($invoice_status == -1)
                                                {

                                                    $order_status = "Canceled";
                                                }
                                                else
                                                {
                                                    $order_status = "Something Wrong";

                                                }



                                         ?> 
                                             <tr>
                                                <th></th>

                                                <td><?php echo $query_invoice['invoice_h_date'];?></td>
                                                <td><?php echo $query_invoice['invoice_h_code'];?></td>
                                                <td><?php echo $query_invoice_customer['customer_name'];?></td>
                                                <td><?php echo $invoice_biller;?></td>
                                                <td><?php echo $query_invoice['invoice_d_qty']." ".$item_uom;?></td>
                                                <td><?php echo $currency." ".number_format((float)$item_net_value,2,'.',''); ?></td>
                                                <td><?php echo $invoice_location_name; ?></td>
                                                <td><span class="<?php echo $style; ?>"><?php echo "$status";?> </span></td>
                                            </tr>
                                        
                                            <?php }
                                            ?>
                                            
                                        </tbody>                                   
                                    </table>
                                </div>
                                            </div>
                                            <div class="tab-pane " id="tab_5_3">
                                               <p> Undercontrouction.. </p>
                                            </div>
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



