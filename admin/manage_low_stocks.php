<?php
ob_start();
error_reporting (E_ALL ^ E_NOTICE);

session_start();
include('include/database.php');
include('include/check_login.php');
include('get_url.php');

//Database eken Table ekata Values daaganna Function eka
function getContent() {
    $db = new Database();
    $hasCol = $db->getRow("SHOW COLUMNS FROM item_master LIKE 'low_stock_qty'");
    if (is_array($hasCol) && isset($hasCol['Field']) && $hasCol['Field'] === 'low_stock_qty') {
        $query = $db->getRows('SELECT item_master.item_id, item_master.item_name, item_master.item_normal_selling_price, item_master.item_image, item_master.item_group, item_master.item_type, item_master.item_category, item_master.item_code, item_master.item_purchase_price, COALESCE(MAX(item_master.low_stock_qty), 5) AS low_stock_qty FROM item_master INNER JOIN fifo ON item_master.item_id = fifo.ft_item GROUP BY fifo.ft_item HAVING SUM(fifo.ft_blanace) < COALESCE(MAX(item_master.low_stock_qty), 5)');
    } else {
        // fallback for DBs that haven't run the migration
        $query = $db->getRows('SELECT item_master.item_id, item_master.item_name, item_master.item_normal_selling_price, item_master.item_image, item_master.item_group, item_master.item_type, item_master.item_category, item_master.item_code, item_master.item_purchase_price, 5 AS low_stock_qty FROM item_master INNER JOIN fifo ON item_master.item_id = fifo.ft_item GROUP BY fifo.ft_item HAVING SUM(fifo.ft_blanace) < 5');
    }
    return $query;
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
        <title>Manage Low Stock Products</title>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta content="width=device-width, initial-scale=1" name="viewport" />
        <meta content="" name="description" />
        <meta content="" name="author" />
        <?php include('common/head.php'); ?>
         <!-- BEGIN PAGE LEVEL PLUGINS -->
        <link href="assets/global/plugins/datatables/datatables.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.css" rel="stylesheet" type="text/css" />
        <!-- END PAGE LEVEL PLUGINS -->
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
                                <a href="#">Home</a>
                                <i class="fa fa-circle"></i>
                            </li>
                            <li>
                                <a href="#">Products</a>
                                <i class="fa fa-circle"></i>
                            </li>
                            <li>
                                <span>Listlow stocks Products</span>
                            </li>
                        </ul>
                      
                    </div>
                    <!-- END PAGE BAR -->
                    <!-- BEGIN PAGE TITLE-->
                    <div class="alert <?php echo $MessageClass; ?> alert-dismissable">
                                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                                        <?php echo $CompanyMessage; ?>
                                    </div>
                    <!-- END PAGE TITLE-->
                    <!-- END PAGE HEADER-->
                  
                    <div class="row">
                        <div class="col-md-12">
                            <!-- BEGIN EXAMPLE TABLE PORTLET-->
                            <div class="portlet light bordered">
                                <div class="portlet-title">
                                    <div class="caption font-green">
                                        <i class="icon-settings font-green"></i>
                                     
                                    </div>
                                    <div class="tools"> </div>
                                </div>
                                <div class="portlet-body">
                                    <table class="table table-striped table-bordered table-hover dt-responsive" width="100%" id="sample_2">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th class="all">Product Code</th>
                                                <th class="all">Product Name</th>
                                                <th class="all">Group</th>
                                                <th class="all">Product Type</th>
                                                <th class="all">Category</th>
                                                 <th class="all">Product Quantity</th>
                                                <th class="all">Low Stock Threshold</th>
                                                <th class="all">Product Cost</th>
                                                <th class="all">Product Price</th>
                                                <th class="none">Product Unit</th>
                                                <th class="all">Action</th>
                                                
                                            </tr>
                                        </thead>
                                        <tbody>
                                           <?php $data = getContent();
                                        foreach($data as $query) 
                                            { 
                                                $item_id = $query['item_id']; 
                                                $item_group = $query['item_group'];
                                                $item_type = $query['item_type'];
                                                $item_category = $query['item_category'];
                                                    $db = new Database();
                                                    $item_grp_id = $db->getRow('SELECT * FROM gorup_master WHERE group_id = ?',[$item_group]);
                                                    $item_cat_id = $db->getRow('SELECT * FROM category_master WHERE category_id = ?',[$item_category]);
                                                    $item_type_id = $db->getRow('SELECT * FROM type_master WHERE type_id = ?',[$item_type]);
                                                    $item_grp_name = $item_grp_id['group_name'];
                                                    $item_cat_name = $item_type_id['type_name'];
                                                    $item_type_name =  $item_cat_id['category_name'];



                                                // qty eka gannawa 
                       
                        $qtyBalance = "";
                         // $id = $_GET['id'];      
                            //$db = new Database();
                            $query_get_qty = $db->getRow('SELECT SUM(ft_blanace) as qty FROM fifo WHERE ft_item = ?',[$item_id]);
                            $get_qty = $query_get_qty['qty'];
                         if($get_qty)
                            {
                                $qtyBalance = $get_qty;

                            }
                            else
                            {

                                $qtyBalance = "0.00";
                            }


                                                ?> 
   
                                             <tr>
                                                <th></th>
                                                <td><?php echo  $query['item_code']; ?></td>
                                                <td><?php  echo  $query['item_name']; ?></td>
                                                <td><?php  echo  $item_grp_name; ?></td>
                                                 <td><?php  echo  $item_type_name; ?></td>
                                                <td><?php  echo  $item_cat_name; ?></td>
                                                <td><?php  echo  $qtyBalance; ?></td>
                                                <td><?php echo intval($query['low_stock_qty'] ?? 5); ?></td>
                                                <td><?php include('currency.php');?> <?php  echo  number_format($query['item_purchase_price']); ?> </td>
                                                <td><?php include('currency.php');?> <?php  echo  number_format($query['item_normal_selling_price']); ?> </td>
                                                <td> </td>
                                                <td>
                                                    <div class="btn-group">
                                           
                                            <a href="product-details.php?pid=<?php echo $item_id; ?>" class="btn btn-xs bg-olive"><i class="glyphicon glyphicon-search"></i></a>
                                          
                                        </div>
                                                </td>
                                            </tr>
                                            <?php }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <!-- END EXAMPLE TABLE PORTLET-->
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



<!-- Mirrored from www.keenthemes.com/preview/metronic/theme/admin_1/table_datatables_responsive.html by HTTrack Website Copier/3.x [XR&CO'2010], Sun, 28 Feb 2016 06:22:49 GMT -->
<!-- Added by HTTrack --><meta http-equiv="content-type" content="text/html;charset=UTF-8" /><!-- /Added by HTTrack -->
</html>



