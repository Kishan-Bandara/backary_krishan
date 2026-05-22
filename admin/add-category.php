<?php
ob_start();
error_reporting (E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');


//Delete values
 $db = new Database();
ensureMasterWebsiteStatusColumns($db);
$message = "";
$CompanyMessage = "";
$MessageClass = 'hidden';
if(isset($_GET['toggleID']))
{
    $toggleid = (int) $_GET['toggleID'];

    if($toggleid > 0)
    {
        $statusRow = $db->getRow('SELECT website_status FROM category_master WHERE category_id = ?',[$toggleid]);
        if($statusRow)
        {
            $nextStatus = normalizeWebsiteStatus($statusRow['website_status']) == 'Y' ? 'N' : 'Y';
            $db->updateRow('UPDATE category_master SET website_status = ? WHERE category_id = ?',[$nextStatus,$toggleid]);
        }
        header('Location:add-category.php');
        exit();
    }
}
if(isset($_GET['deleteID']))
{
   $deleteid = $_GET['deleteID'];

   
if($deleteid > 0)
{
    $query_check_product = $db->getRow('SELECT * FROM item_master WHERE item_category = ?',[$deleteid]);
    $check_cat_frm_product = $query_check_product['item_category'];


    if($check_cat_frm_product)
   {

    $message = "Can't Delete this Category.";
   }
   else {

      $db = new Database();
    $deleterowquery = $db->deleteRow('DELETE FROM category_master WHERE category_id = ?',[$deleteid]);
    header('Location:add-category.php');
    exit();

   }
    
}
else
{

    $message = "check your  Category ID!";

}


}

$CompanyMessage = $message;
if(!empty($CompanyMessage))
{
    $MessageClass = 'alert-info';
}

//Database eken Table ekata Values daaganna Function eka
function getContent() {
    $db = new Database();
    ensureMasterWebsiteStatusColumns($db);
    $query = $db->getRows('SELECT catg.category_id, catg.category_name, catg.category_discription, catg.website_status, typ.type_name, grp.group_name FROM category_master catg JOIN type_master typ ON catg.type_id = typ.type_id JOIN gorup_master grp ON typ.group_id = grp.group_id ORDER BY grp.group_name ASC, typ.type_name ASC, catg.category_name ASC');
    return $query;
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

//$db->Disconnect();
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
        <title>Add Product Category | STOCK MANAGEMENT SYSTEM</title>
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
                                <a href="index.php">Home</a>
                                <i class="fa fa-circle"></i>
                            </li>
                            <li>
                                <a href="#">Add product category</a>
                               
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
<div class="portlet box blue ">
                                            <div class="portlet-title">
                                                <div class="caption">
                                                    <i class="fa fa-gift"></i>Product category</div>
                                                <div class="tools">
                                                    <a href="javascript:;" class="collapse" data-original-title="" title=""> </a>
                                                    <a href="#portlet-config" data-toggle="modal" class="config" data-original-title="" title=""> </a>
                                                    <a href="javascript:;" class="reload" data-original-title="" title=""> </a>
                                                    <a href="javascript:;" class="remove" data-original-title="" title=""> </a>
                                                </div>
                                            </div>
                                            <div class="portlet-body form">
                                                <!-- BEGIN FORM-->
                                                <form action="" class="form-horizontal form-bordered form-row-stripped" method="POST" id="frnAddcategory">
                                                      <div class="form-body">
                                                        <div class="form-group form-md-line-input">
                                                            <label class="control-label col-md-3 ">Group*</label>
                                                            <div class="col-md-9">
                                                                <select class="form-control" name="pgroup" id="pgroup">
                                                                  <?php echo load_groups()  ; ?>
                                           
                                                                </select>
                                                              <!-- <span class="help-block"> Group is required or need attention. </span> -->
                                                            </div>
                                                        </div>
                                               <div class="form-group form-md-line-input">
                                                            <label class="control-label col-md-3 ">Type*</label>
                                                            <div class="col-md-9">
                                                                <select class="form-control" name="ptype" id="ptype">
                                                                  <option value="">Select Type</option>
                                           
                                                                </select>
                                                              <!-- <span class="help-block"> Group is required or need attention. </span> -->
                                                            </div>
                                                        </div>
                                               
                                                    <div class="form-group form-md-line-input">
                                                <label class="col-md-3 control-label" for="form_control_1">Product category
                                                    <span class="required" aria-required="true">*</span>
                                                </label>
                                                <div class="col-md-9">
                                                    <input type="text" class="form-control" placeholder="" name="name">
                                                    <div class="form-control-focus"> </div>
                                                    <span class="help-block"></span>
                                                </div>
                                            </div>

                                            <div class="form-group form-md-line-input">
                                                <label class="col-md-3 control-label" for="category_website_status">Web Display</label>
                                                <div class="col-md-9">
                                                    <select class="form-control" name="website_status" id="category_website_status">
                                                        <option value="Y">True</option>
                                                        <option value="N">False</option>
                                                    </select>
                                                    <div class="form-control-focus"> </div>
                                                </div>
                                            </div>

                                                       
                                                    </div>
                                                    <div class="form-actions">
                                                        <div class="row">
                                                            <div class="col-md-offset-3 col-md-9">
                                                                <button type="submit" class="btn green" name="sub">
                                                                    <i class="fa fa-check"></i> Save product category</button>
                                                                    <?php echo $message; ?>
                                                                     
                                                               
                                                            </div>
                                                        </div>
                                                    </div>
                                                </form>
                                                <!-- END FORM-->
                                            </div>
                                        </div>

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
                                                <th class="all">Category ID</th>
                                                 <th class="all">Group Name</th>
                                                  <th class="all">Type Name</th>
                                                <th class="all">Category Name</th>
                                            <th class="all">Web Display</th>
                                                <th class="none">Discription</th>
                                                <th> </th>
                                            
                                            </tr>
                                        </thead>
                                        <tbody>
                                           
                                            <?php $data = getContent();
                                        foreach($data as $query) { $categoryid = $query['category_id']; ?> 
   
                                             <tr>
                                                <th></th>
                                                <td><?php echo  $query['category_id']; ?></td>
                                                <td><?php  echo  $query['group_name']; ?></td>
                                                <td><?php  echo  $query['type_name']; ?></td>
                                                <td><?php  echo  $query['category_name']; ?></td>
                                                <td><?php echo normalizeWebsiteStatus($query['website_status']) == 'Y' ? 'True' : 'False'; ?></td>
                                                <td><?php  echo  $query['category_discription']; ?></td>
                                                <td>
                                                    <a href="add-category.php?toggleID=<?php echo $categoryid; ?>"><?php echo normalizeWebsiteStatus($query['website_status']) == 'Y' ? 'Hide' : 'Show'; ?></a>
                                                    |
                                                    <a href="add-category.php?deleteID=<?php echo $categoryid; ?>">Delete</a>
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
        <!-- Notification function -->
        <script src="assets/global/plugins/notification/jquery.bootstrap-growl.js"></script>
    <script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','www.google-analytics.com/analytics.js','ga');
  ga('create', 'UA-37564768-1', 'keenthemes.com');
  ga('send', 'pageview');
</script>
</body>
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
 <!-- post eken data yawanawa -->
<script type="text/javascript">
$(document).ready(function()
{
 $(document).on('submit', '#frnAddcategory', function()
 {
  
  var data = $(this).serialize();
  
  
  $.ajax({
  
 type : 'POST',
 url  : 'process/add-category-process.php',
  data : data,
  success :  function(data)
       {
        $(function () {
                    setTimeout(function() {
                    $.bootstrapGrowl(data, { 
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
</html>



