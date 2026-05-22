<?php
ob_start();
error_reporting (E_ALL ^ E_NOTICE);
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
        $statusRow = $db->getRow('SELECT website_status FROM type_master WHERE type_id = ?',[$toggleid]);
        if($statusRow)
        {
            $nextStatus = normalizeWebsiteStatus($statusRow['website_status']) == 'Y' ? 'N' : 'Y';
            $db->updateRow('UPDATE type_master SET website_status = ? WHERE type_id = ?',[$nextStatus,$toggleid]);
        }
        header('Location:add-type.php');
        exit();
    }
}
if(isset($_GET['deleteID']))
{
   $deleteid = $_GET['deleteID'];

   
if($deleteid > 0)
{
    $query_check_cat = $db->getRow('SELECT * FROM category_master WHERE type_id = ?',[$deleteid]);
    $check_type_frm_cat = $query_check_cat['type_id'];


    if($check_type_frm_cat)
   {

    $message = "Can't Delete this type.";
   }
   else {

     $db = new Database();
    $deleterowquery = $db->deleteRow('DELETE FROM type_master WHERE type_id = ?',[$deleteid]);
    header('Location:add-type.php');
    exit();

   }
    
}
else
{

    $message = "check your  Type ID!";

}


}
if(isset($_POST['sub']))
{



function filter($var)
{

    return preg_replace('/ [^a-za-z0-9\s@.]/',' ' , $var);
}

$group_id = filter($_POST['pgroup']);
$typeName = $_POST['name'];
$websiteStatus = normalizeWebsiteStatus($_POST['website_status'] ?? 'Y');
$db = new Database();

//check alrady Group Name
$checktype = $db->getRow('SELECT * FROM type_master WHERE type_name = ? AND group_id = ?',[$typeName , $group_id]);

if($checktype > 0)
{

    $message = "Product Type Information Already Exist";

}
   else
   {
    //insert into the Database 
    try {
   
   $db = new Database();
    $insertType = $db->insertRow('INSERT INTO type_master(group_id,type_name,website_status) VALUES(?,?,?)',[$group_id,$typeName,$websiteStatus]);

   $message = "New record created successfully";

  
    } catch (PDOException $e) {
       
      $message= '$insertType."<br>" . $e->getMessage()';
   }
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
    $query = $db->getRows('SELECT typ.type_id, typ.type_name, typ.type_discription, typ.website_status, grp.group_name FROM type_master typ JOIN gorup_master grp ON typ.group_id = grp.group_id ORDER BY grp.group_name ASC, typ.type_name ASC');
    return $query;
}

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
        <title>Add Product Types | STOCK MANAGEMENT SYSTEM</title>
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
                                <a href="#">Add product Types</a>
                               
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
<div class="portlet light bordered form-fit ">
                                            <div class="portlet-title">
                                                <div class="caption">
                                                    <i class="fa fa-gift"></i>Product Types</div>
                                                <div class="tools">
                                                    <a href="javascript:;" class="collapse" data-original-title="" title=""> </a>
                                                    <a href="#portlet-config" data-toggle="modal" class="config" data-original-title="" title=""> </a>
                                                    <a href="javascript:;" class="reload" data-original-title="" title=""> </a>
                                                    <a href="javascript:;" class="remove" data-original-title="" title=""> </a>
                                                </div>
                                            </div>
                                            <div class="portlet-body form">
                                                <!-- BEGIN FORM-->
                                                <form action="" class="form-horizontal form-bordered form-row-stripped" method="POST">
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
                                                <label class="col-md-3 control-label" for="form_control_1">Product Type
                                                    <span class="required" aria-required="true">*</span>
                                                </label>
                                                <div class="col-md-9">
                                                    <input type="text" class="form-control" placeholder="" name="name">
                                                    <div class="form-control-focus"> </div>
                                                    <span class="help-block"></span>
                                                </div>
                                            </div>

                                                <div class="form-group form-md-line-input">
                                                    <label class="col-md-3 control-label" for="type_website_status">Web Display</label>
                                                    <div class="col-md-9">
                                                        <select class="form-control" name="website_status" id="type_website_status">
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
                                                                    <i class="fa fa-check"></i> Save product Type</button>
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
                                                <th class="all">Type ID</th>
                                                <th class="all">Group Name</th>
                                                <th class="all">Type Name</th>
                                                <th class="all">Web Display</th>
                                                <th class="none">Discription</th>
                                                <th> </th>
                                            
                                            </tr>
                                        </thead>
                                        <tbody>
                                           
                                            <?php $data = getContent();
                                        foreach($data as $query) { $typeid = $query['type_id']; ?> 
   
                                             <tr>
                                                <th></th>
                                                <td><?php echo  $query['type_id']; ?></td>
                                                <td><?php  echo  $query['group_name']; ?></td>
                                                <td><?php  echo  $query['type_name']; ?></td>
                                                <td><?php echo normalizeWebsiteStatus($query['website_status']) == 'Y' ? 'True' : 'False'; ?></td>
                                                <td><?php  echo  $query['type_discription']; ?></td>
                                                <td>
                                                    <a href="add-type.php?toggleID=<?php echo $typeid; ?>"><?php echo normalizeWebsiteStatus($query['website_status']) == 'Y' ? 'Hide' : 'Show'; ?></a>
                                                    |
                                                    <a href="add-type.php?deleteID=<?php echo $typeid; ?>">Delete</a>
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

</body>

</html>




