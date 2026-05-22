<?php 
ob_start();
error_reporting (E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');


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
        <title>Sales Report</title>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta content="width=device-width, initial-scale=1" name="viewport" />
        <meta content="" name="description" />
        <meta content="" name="author" />
        <?php include('common/head.php'); ?>
        <link href="assets/global/plugins/bootstrap-daterangepicker/daterangepicker.min.css" rel="stylesheet" type="text/css" />
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
                                <a href="#">Report</a>
                                <i class="fa fa-circle"></i>
                            </li>
                            <li>
                                <span>Sales Report</span>
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
                       
                
                  
                  <div class="col-md-3" style=" padding: 15px;" >
                    <div class="input-group search-page" style="background: #f7f9fa; margin: -16px 0px 8px; padding: 16px; width: 100%;border-radius: 9px;">
                        <form method="POST" name="frn-src" id="frn-src">
              <div class="input-group">
                <span class="input-group-addon"><i class="ion-calendar"></i></span>
                <input type="text" class="form-control" id="daterangepicker1" name="daterange">
                  <span class="input-group-btn">
                        <button type="submit" class="btn btn-primary" name="sub" id="sub"><i class="fa fa-search"></i></button>
                    </span>
              </div>
                                                </form>
                 </div>

                      

                           <div class="panel panel-default alt with-footer">
      <div class="panel-heading">
        <h3 style="text-align:center;">Local Sales Amount</h3>
        <div class="panel-ctrls">
        <div id="example_filter" class="dataTables_filter pull-right"><label class="panel-ctrls-center"></label></div><i class="separator"></i><div class="dataTables_length pull-left" id="example_length"><a class="btn btn-social btn-instagram" id="url_selector"><i class="ion-printer"></i></a><label class="panel-ctrls-center"></label></div></div>
      </div>
      <div class="panel-body p-n">
        <div id="example_wrapper" class="dataTables_wrapper form-inline no-footer"><div class="row"><div class="col-sm-6"></div><div class="col-sm-6"></div></div>
        <div class="table-responsive">
            <h2 style="color:red;text-align:center;" id="output_lkr">LKR 0.00</h2>
                </div>
                
      </div>
      </div>
    </div>
                        



                 
                  </div>
               

               

                
                <div class="row">
  <div class="col-md-8">
    <div class="panel panel-default alt with-footer">
      <div class="panel-heading">
        <h3>Sales Reports</h3>
        <div class="panel-ctrls">
        <div id="example_filter" class="dataTables_filter pull-right"><label class="panel-ctrls-center"></label></div><i class="separator"></i><div class="dataTables_length pull-left" id="example_length"><a class="btn btn-social btn-instagram" id="url_selector"><i class="ion-printer"></i></a><label class="panel-ctrls-center"></label></div></div>
      </div>
      <div class="panel-body p-n">
        <div id="example_wrapper" class="dataTables_wrapper form-inline no-footer"><div class="row"><div class="col-sm-6"></div><div class="col-sm-6"></div></div>
        <div class="table-responsive" id="search-report">
           
                </div>
                
      </div>
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


     <!-- BEGIN PAGE LEVEL PLUGINS -->
        <script src="assets/global/plugins/moment.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/bootstrap-daterangepicker/daterangepicker.min.js" type="text/javascript"></script>
       
        <!-- END PAGE LEVEL PLUGINS -->
    
     <!-- BEGIN PAGE LEVEL SCRIPTS -->
        <script src="assets/pages/scripts/demo-pickers.js" type="text/javascript"></script>
        <!-- END PAGE LEVEL SCRIPTS -->
</body>

</html>
<!-- search query goto the page  -->
<script>
 $(document).on('submit','#frn-src', function()
    {
      
       var data = $(this).serialize();
       
  
   $.ajax({
    
   type : 'POST',
   url  : 'process/report-sales.php',
   data : data,
   beforeSend: function()
   { 
    $("#error").fadeOut();
    $("#sub").html('Searching ...');

   },
   success :  function(response)
      {      

      $("#sub").html('<i class="fa fa-search"></i>');
      $("#search-report").html(response);
      
            var total_amount = 0;
            $('.grand_tot').each(function(){
                total_amount += parseFloat($(this).text().split(',').join('').trim()); 
            });
            document.getElementById('output_lkr').innerHTML= total_amount.toFixed(2);

      }
   });
    return false;
    });





</script>



