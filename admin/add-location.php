<?php 
ob_start();
error_reporting (E_ALL ^ E_NOTICE); 
include('include/database.php');
include('include/check_login.php');
include('get_url.php');
$message = "";
//Delete values
 $db = new Database();
$message = "";
if(isset($_POST['getDelete']))
{
   $deleteid = $_POST['deleteID'];

   
if($deleteid > 0)
{
    $query_check_location = $db->getRow('SELECT * FROM fifo WHERE ft_location = ?',[$deleteid]);
    $check_location_frm_fifo = $query_check_location['ft_location'];


    if($check_location_frm_fifo > 0)
   {

    
    
    redirect('add-location.php');
   }
   else {

    $deleterowquery = $db->deleteRow('DELETE FROM location_master WHERE id = ?',[$deleteid]);
    redirect('add-location.php');

   }
    
}
else
{

    redirect('add-location.php');

}


}

if(isset($_POST['sub']))
{



function filter($var)
{

    return preg_replace('/ [^a-za-z0-9\s@.]/',' ' , $var);
}


$location_name = filter($_POST['name']);
$location_code = trim($_POST['location_code']);
$contact_number = filter($_POST['number']);
$address = filter($_POST['address']);
$db = new Database();



if(!empty($location_name) && !empty($location_code) && !empty($contact_number) && !empty($address))
{

//check already existing location name
$check_location = $db->getRow('SELECT * FROM location_master WHERE name = ? ',[$location_name]);

//check already existing location code
$check_location_code = $db->getRow('SELECT * FROM location_master WHERE location_code = ? ',[$location_code]);

if($check_location > 0)
{
    $message = "This Warehouse Name Already Exists";
}
elseif($check_location_code > 0)
{
    $message = "This Location Code Already Exists";
}
else
{
    //insert into the Database
    try {

   $db = new Database();
   $insert_location = $db->insertRow('INSERT INTO location_master(name,location_code,address,phone_no) VALUES(?,?,?,?)',[$location_name,$location_code,$address,$contact_number]);

   $message = "New Warehouse created successfully";
   header('Location: manage-locations.php?success=1');
   exit();
   } catch (PDOEException $e) {
       
      $message= '$insert_locationp."<br>" . $e->getMessage()';
   }
   
   }
}
else
{
    $message = "Please you must enter empty field.";

}

 
}

//Database eken Table ekata Values daaganna Function eka
function getContent() {
    $db = new Database();
    $query = $db->getRows('SELECT * FROM location_master');
    return $query;
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
        <title>Add Locations| STOCK MANAGEMENT SYSTEM</title>
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
                                <a href="#">Add Warehouse</a>
                               
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
                                                  <span class="caption-subject font-red sbold uppercase">Add Warehouses</span>
                                                    </div>

                                                <div class="tools">
                                                    <a href="javascript:;" class="collapse" data-original-title="" title=""> </a>
                                                  
                                                </div>
                                            </div>
                                            <div class="portlet-body form">
                                                <!-- BEGIN FORM-->
                                                <form action="" class="form-horizontal form-bordered form-row-stripped" method="POST">
                                                    <div class="form-body">
                                               
                                                    <div class="form-group form-md-line-input">
                                                <label class="col-md-3 control-label" for="form_control_1">Warehouse
                                                    <span class="required" aria-required="true">*</span>
                                                </label>
                                                <div class="col-md-9">
                                                    <input type="text" class="form-control" placeholder="" name="name">
                                                    <div class="form-control-focus"> </div>
                                                    <span class="help-block"></span>
                                                </div>
                                            </div>
                                               <div class="form-group form-md-line-input">
                                                <label class="col-md-3 control-label" for="form_control_1">Location Code
                                                    <span class="required" aria-required="true">*</span>
                                                </label>
                                                <div class="col-md-9">
                                                    <input type="text" class="form-control" placeholder="e.g., MAIN, OUT1, OUT2" name="location_code" maxlength="20">
                                                    <div class="form-control-focus"> </div>
                                                    <span class="help-block">Unique code for the warehouse location</span>
                                                </div>
                                            </div>
                                               <div class="form-group form-md-line-input">
                                                <label class="col-md-3 control-label" for="form_control_1">Contact Number
                                                    <span class="required" aria-required="true">*</span>
                                                </label>
                                                <div class="col-md-9">
                                                    <input type="number" class="form-control" placeholder="" name="number">
                                                    <div class="form-control-focus"> </div>
                                                    <span class="help-block"></span>
                                                </div>
                                            </div>
                                               <div class="form-group form-md-line-input">
                                                <label class="col-md-3 control-label" for="form_control_1">Address
                                                    <span class="required" aria-required="true">*</span>
                                                </label>
                                                <div class="col-md-9">
                                                    <textarea class="form-control" name="address" rows="3" style="margin-top: 0px; margin-bottom: 0px; height: 95px;"></textarea>
                                                    <div class="form-control-focus"> </div>
                                                    <span class="help-block"></span>
                                                </div>
                                            </div>
                                                       
                                                    </div>
                                                    <div class="form-actions">
                                                        <div class="row">
                                                            <div class="col-md-offset-3 col-md-9">
                                                                <button type="submit" class="btn green" name="sub">
                                                                    <i class="fa fa-check"></i> Submit Location</button>
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
                                    <span class="caption-subject font-red sbold uppercase">Warehouses</span>
                                <p>Please use the table below to navigate or filter the results. You can download the table as excel and pdf. </p>
                                </div>
                                <div class="portlet-body">
                                    <table class="table table-striped table-bordered table-hover dt-responsive" width="100%" id="sample_2">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th class="all">Warehouse id</th>
                                                <th class="all">Warehouse</th>
                                                <th class="all">Contact No</th>
                                                <th class="none">Address</th>
                                                <th> </th>
                                                <th> </th>
                                            
                                            </tr>
                                        </thead>
                                        <tbody>
                                           <?php $data = getContent();
                                        foreach($data as $query) { $locationId = $query['id'];?> 
   
                                             <tr>
                                                <th></th>
                                                <td><?php echo  $query['id']; ?></td>
                                                <td><?php  echo  $query['name']; ?></td>
                                                <td><?php  echo  $query['phone_no']; ?></td>
                                                <td><?php  echo  $query['address']; ?></td>
                                               <td><form action="" method="POST" name=""><input type="hidden" name="deleteID" value="<?php echo $locationId; ?>"><button type="submit" name="getDelete" class="btn btn-primary btn-xs"><i class="fa fa-remove"></i> Delete</button></form> </td>
                                               <td><form action="edit-location.php" method="GET" name=""><input type="hidden" name="location_id" value="<?php echo $locationId; ?>"><button type="submit" class="btn btn-primary btn-xs" data-toggle="modal"  data-target="#myModal"><i class="fa fa-edit"></i> Edit</button></form></td>
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




