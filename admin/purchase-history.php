<?php 
ob_start();
error_reporting (E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');


?>
<?php

    function getContent() {
	    $db = new Database();
	    $query_grn_h = $db->getRows('SELECT * FROM grn_hedder WHERE grn_h_location = ? ORDER BY grn_h_id DESC',[$_SESSION['location']]);
	    return $query_grn_h;
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
        <title>Purchase History | STOCK MANAGEMENT SYSTEM</title>
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
                                <a>Home</a>
                                <i class="fa fa-circle"></i>
                            </li>
                            <li>
                                <a>Purchase</a>
                                <i class="fa fa-circle"></i>
                               
                            </li>
                            <li>
                                <a>History</a>
                               
                            </li>
                           
                        </ul>
                      
                    </div>
                    <!-- END PAGE BAR -->
                    <div class="alert <?php echo $MessageClass; ?> alert-dismissable">
                                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                                        <?php echo $CompanyMessage; ?>
                                    </div>
                    <!-- BEGIN PAGE TITLE-->
                    <h3 class="page-title">Purchase History
                      
                    </h3>
                    <!-- END PAGE TITLE-->
                    <!-- END PAGE HEADER-->
                  
                    <div class="row">
                        <div class="col-md-12">
<div class="portlet light bordered form-fit ">
                                         
                            <!-- BEGIN EXAMPLE TABLE PORTLET-->
                            <div class="portlet light bordered">
                                <div class="portlet-title">
                                   
                                    <div class="tools"> </div>
                                </div>
                                <div class="portlet-body">
                                    <table class="table table-striped table-bordered table-hover dt-responsive" width="100%" id="sample_2">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th class="all">Purchase No.</th>
                                                <th class="all">Supplier Name</th>
                                                <th class="all">Purchase Date</th>
                                                <th class="all">Grand Total</th>
                                                <th class="all">Purchase By</th>
                                                <th class="all">Payment Status</th>
                                                <th class="all">Action</th>
                                                
                                            
                                            </tr>
                                        </thead>
                                        <tbody>
                                        	  <?php $data = getContent();
                                        foreach($data as $query_grn_h)
                                         { 
                                         	$grn_h_id = $query_grn_h['grn_h_id'];
                                         	$supid = $query_grn_h['grn_h_supplier_id'];
                                         	$query_grn_h_supplier = $db->getRow('SELECT * FROM supplier WHERE supplier_id = ?',[$supid]);
                                            $query_grn_amount = $db->getRow('SELECT SUM(amount) as supplier_amount FROM supplier_balance WHERE grn_h_id = ?',[$grn_h_id]);
                                            $amount = $query_grn_amount['supplier_amount'];
                                            $net_value = $query_grn_h['grn_h_net_value'];

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

                                                if($query_grn_h['add_by']){


                                                    $query_user = $db->getRow('SELECT * FROM users WHERE userid = ?',[$query_grn_h['add_by']]);

                                                    $added_user_name = $query_user['first_name']." ".$query_user['last_name'];
                                                }

                                         ?> 
                                             <tr>
                                                
                                                <th></th>
                                                <td><?php echo $query_grn_h['grn_h_code'];?></td>
                                                <td><?php echo $query_grn_h_supplier['supplier_name'];?></td>
                                                <td><?php echo $query_grn_h['grn_h_date'];?></td>
                                                <td><?php include('currency.php');?> <?php echo number_format($query_grn_h['grn_h_net_value']);?></td>
                                                <td><small><cite title="Source Title"><?php echo $added_user_name;?></cite></small></td>
                                                <td><span class="<?php echo $style; ?>"> <?php echo $status;?> </span></td>
                                               <td><div style="text-align:center"><a href='purchase_invoice.php?id=<?php echo $grn_h_id;?>'><div class="btn-group btn-group-xs btn-group-solid"><button type="button" class="btn dark btn-outline sbold uppercase">View GRN Note</button></div></a><a href="purchase_note.php?id=<?php echo $grn_h_id;?>"> <div class="btn-group btn-group-xs btn-group-solid"><button type="button" class="btn blue btn-outline">MAKE PAYMENT</button></div></a></div></td>

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

</body>

</html>



