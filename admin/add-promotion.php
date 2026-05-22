<?php 
ob_start();
error_reporting (E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');
   
?>
<?php
 $db = new Database();

 function load_products()  
 {  
      
      $output = '';  
      $db = new Database();
     $query = $db->getRows('SELECT ft_item FROM fifo  WHERE ft_location = ? AND ft_blanace > 0 GROUP BY ft_item',[$_SESSION['location']]);
      $data = $query;


     
        foreach($data as $query) 
            {   
                 $id = $query['ft_item']; 
                 $query_item_details = $db->getRow('SELECT * FROM item_master WHERE item_id = ?',[$id]);
                 if(isset($query_item_details['item_id'])){
                    $itmID = $query_item_details['item_id'];
                    $itmName = $query_item_details['item_name'];
                    $itmCode = $query_item_details['item_code'];
                    $output .= '<option value="'.$itmID.'">'.$itmName.' ('.$itmCode.')</option>';
                 }
                

            }
            return $output;  
 }  

// DB eke values Group Dropdown ekata Assign karannawa
 function load_groups()  
 {  
      
      $output = '';  
      $db = new Database();
      $grpquery = $db->getRows('SELECT * FROM gorup_master');
      $grpdata = $grpquery;
      $output = '<option value="">Select Group</option>';
        foreach($grpdata as $grpquery) 
            {   

                $grpid = $grpquery['group_id']; 
                $output .= '<option value="'.$grpquery['group_id'].'">'.$grpquery['group_name'].'</option>';

            }
            return $output;  
 }  
?>

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
        <title>Add Seles</title>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta content="width=device-width, initial-scale=1" name="viewport" />
        <meta content="" name="description" />
        <meta content="" name="author" />
        <?php include('common/head.php'); ?>
         <!-- BEGIN PAGE LEVEL PLUGINS -->
        <link href="assets/global/plugins/datatables/datatables.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.css" rel="stylesheet" type="text/css" />
        <!-- END PAGE LEVEL PLUGINS -->
        <link href="assets/global/plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/global/plugins/select2/css/select2-bootstrap.min.css" rel="stylesheet" type="text/css" />
       </head>
    <!-- END HEAD -->

    <body class="page-sidebar-closed-hide-logo page-content-white page-sidebar-closed">
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
                                <a href="#">Sales</a>
                                <i class="fa fa-circle"></i>
                            </li>
                            <li>
                                <span>Add Sales</span>
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
                   <form id="frn-add" method="POST">
                    <div class="row">
                        <div class="col-md-12">
                      

                                        <div class="portlet light bordered form-fit ">
                                            <div class="portlet-title">
                                                <div class="caption">
                                                    <i class="fa fa-gift"></i>Add Promotion item wise</div>
                                                <div class="tools">
                                                    <a href="javascript:;" class="collapse" data-original-title="" title=""> </a>
                                                    <a href="#portlet-config" data-toggle="modal" class="config" data-original-title="" title=""> </a>
                                                    <a href="javascript:;" class="reload" data-original-title="" title=""> </a>
                                                    <a href="javascript:;" class="remove" data-original-title="" title=""> </a>
                                                </div>
                                            </div>
                                            <div class="portlet-body form">
                                              
                                                   <div class="form-horizontal form-bordered form-row-stripped">
                                                    <div class="form-body">
                                                        <div class="form-group">
                                                            <label class="control-label col-md-3">Product</label>
                                                            <div class="col-md-9">
                                                                 <select class="form-control select2me select2-hidden-accessible" name="item" tabindex="-1" aria-hidden="true">
                                                   <?php echo load_products(); ?>
                                                    </select>
                                                            </div>
                                                        </div>
                                                    <div class="form-group">
                                                            <label class="control-label col-md-3">Promotion type</label>
                                                            <div class="col-md-9">
                                                                <select class="form-control" name="pro_type" id="pro_type">
                                                                  <option value="0">Promotion</option>
                                                                  <option value="1">Deal of the day</option>
                                           
                                                                </select>
                                                                <span class="help-block"> this  is required or need attention. </span>
                                                            </div>
                                                        </div>

                                                        <div class="form-group">
                                                            <label class="control-label col-md-3">Product Discount</label>
                                                            <div class="col-md-2">
                                                               <div class="input-group">
                                                        <input type="number" class="form-control" name="discount">
                                                        <span class="input-group-addon">
                                                            <i class="">%</i>
                                                        </span>
                                                    </div> </div>
                                                        </div>







                                                       

                                                    </div>
                                                    <div class="form-actions">
                                                        <div class="row">
                                                            <div class="col-md-offset-3 col-md-9">
                                                                <button type="submit" class="btn blue" name="sub" id="sub">
                                                                    <i class="fa fa-check"></i>Add Promotion</button>
                                                                    <div id="response"> </div>
                                                               
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                              
                                            </div>
                                        </div>



                    </div>
                  
                </div>
            </form>
                <!-- END CONTENT BODY -->


                            <!-- END PAGE HEADER-->
                   <form id="frn-add-promo-cat" method="POST">
                    <div class="row">
                        <div class="col-md-12">
                      

                                        <div class="portlet light bordered form-fit ">
                                            <div class="portlet-title">
                                                <div class="caption">
                                                    <i class="fa fa-gift"></i>Add Promotion catagory wise</div>
                                                <div class="tools">
                                                    <a href="javascript:;" class="collapse" data-original-title="" title=""> </a>
                                                    <a href="#portlet-config" data-toggle="modal" class="config" data-original-title="" title=""> </a>
                                                    <a href="javascript:;" class="reload" data-original-title="" title=""> </a>
                                                    <a href="javascript:;" class="remove" data-original-title="" title=""> </a>
                                                </div>
                                            </div>
                                            <div class="portlet-body form">
                                              
                                                   <div class="form-horizontal form-bordered form-row-stripped">

                                                     <div class="form-group">
                                                            <label class="control-label col-md-3">Group*</label>
                                                            <div class="col-md-9">
                                                                <select class="form-control" name="pgroup" id="pgroup">
                                                                  <?php echo load_groups()  ; ?>
                                           
                                                                </select>
                                                                <span class="help-block"> Category is required or need attention. </span>
                                                            </div>
                                                        </div>
                                                       <div class="form-group">
                                                            <label class="control-label col-md-3">Type*</label>
                                                            <div class="col-md-9">
                                                                <select class="form-control" name="ptype" id="ptype">
                                                                 <option value="">Select Type</option> 
                                                                </select>
                                                                <span class="help-block"> Category is required or need attention. </span>
                                                            </div>
                                                        </div>
                                                        <div class="form-group">
                                                            <label class="control-label col-md-3">Category</label>
                                                            <div class="col-md-9">
                                                                <select class="form-control select2_category" name="pcategory" id="pcategory">
                                                                   <option value="">Select Category</option> 
                                                                </select>
                                                            </div>
                                                        </div>
                                                    <div class="form-body">
                                                        

                                                        <div class="form-group">
                                                            <label class="control-label col-md-3">Product Discount</label>
                                                            <div class="col-md-2">
                                                               <div class="input-group">
                                                        <input type="number" class="form-control" name="discount_by_cat">
                                                        <span class="input-group-addon">
                                                            <i class="">%</i>
                                                        </span>
                                                    </div> </div>
                                                        </div>







                                                       

                                                    </div>
                                                    <div class="form-actions">
                                                        <div class="row">
                                                            <div class="col-md-offset-3 col-md-9">
                                                                <button type="submit" class="btn blue" name="sub" id="sub">
                                                                    <i class="fa fa-check"></i>Add Promotion</button>
                                                                    <div id="response"> </div>
                                                               
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                              
                                            </div>
                                        </div>



                    </div>
                  
                </div>
            </form>
                <!-- END CONTENT BODY -->
            </div>
            <!-- END CONTENT -->
          
        </div>
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
        <script src="assets/global/plugins/fuelux/js/spinner.min.js" type="text/javascript"></script>
        <!-- END PAGE LEVEL SCRIPTS -->
        <!-- BEGIN THEME LAYOUT SCRIPTS -->
        <script src="assets/layouts/layout/scripts/layout.min.js" type="text/javascript"></script>
        <script src="assets/layouts/layout/scripts/demo.min.js" type="text/javascript"></script>
        <script src="assets/layouts/global/scripts/quick-sidebar.min.js" type="text/javascript"></script>
        <!-- END THEME LAYOUT SCRIPTS -->
 
       <!-- Auto Numaric Function -->
         <script src="assets/global/plugins/numaricFunction/autoNumeric.js" type="text/javascript"></script>
         
         <!-- Notification function -->
        <script src="assets/global/plugins/notification/jquery.bootstrap-growl.js"></script>
        <script src="assets/global/plugins/select2/js/select2.full.min.js" type="text/javascript"></script>
       

</body>

</html> 


<script type="text/javascript">
$(document).ready(function()
{
 $(document).on('submit', '#frn-add', function()
 {
  
  var data = $(this).serialize();

  $.ajax({
  
  type : 'POST',
  url  : 'process/add-promotion-process.php',
  data : data,
  success :  function(response)
       {
            
            $(function () {
                    setTimeout(function() {
                    $.bootstrapGrowl(response, { 
                        type: 'success',
                        align: 'right'
                    });

                }, 1000);
                
              
            });



       }
  });
  return false;
 });

});

    </script>



<script type="text/javascript">
$(document).ready(function()
{
 $(document).on('submit', '#frn-add-promo-cat', function()
 {
  
  var data = $(this).serialize();

  $.ajax({
  
  type : 'POST',
  url  : 'process/add-promotion-by-category-process.php',
  data : data,
  success :  function(response)
       {
            
            $(function () {
                    setTimeout(function() {
                    $.bootstrapGrowl(response, { 
                        type: 'success',
                        align: 'right'
                    });

                }, 1000);
                
              
            });



       }
  });
  return false;
 });

});

    </script>



<!-- Type Droup downekata values assign karanawa  -->
<script>  
 $(document).ready(function(){  
      $('#pgroup').change(function(){  
           var group_id = $(this).val();  
           $.ajax({  
                url:"fetch_type.php",  
                method:"POST",  
                data:{groupId:group_id},  
                dataType:"text",  
                success:function(data)  
                {  
                     $('#ptype').html(data);  
                }  
           });  
      });  
 });  
 </script>  
<!-- category dropdown ekata values assign karnawa -->
 <script>  
 $(document).ready(function(){  
      $('#ptype').change(function(){  
           var type_id = $(this).val();  
           $.ajax({  
                url:"fetch_category.php",  
                method:"POST",  
                data:{typeId:type_id},  
                dataType:"text",  
                success:function(data)  
                {  
                     $('#pcategory').html(data);  
                }  
           });  
      });  
 });  
 </script>  




