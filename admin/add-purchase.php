<?php 
ob_start();
error_reporting (E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');
date_default_timezone_set("Asia/Colombo");

?>
<?php
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


 //UOM values Dropdown ekata dagannawa
function load_uom()  
 {  
      
      $uom_output = '';  
      $db = new Database();
      $uomquery = $db->getRows('SELECT * FROM item_uom');
      $uomdata = $uomquery;
      $uom_output = '<option value="">Select UOM</option>';
        foreach($uomdata as $uomquery) 
            {   

                $uomid = $uomquery['uom_id']; 
                $uom_output .= '<option value="'.$uomquery['uom_id'].'">'.$uomquery['uom_name'].'</option>';

            }
            return $uom_output;  
 }  


//parana id eka search karala aluth id ekak hadagannawa.
$db = new Database();
$getpid = $db->getRow('SELECT max(item_id) as item_id FROM item_master');

$oldpid = $getpid['item_id'];

if($getpid > 0)
{

$newpid =  $oldpid + 1 ; 
}

// product code ekak hadagannawa

$pcode = "IG0".$newpid;

//Database eken Table ekata Values daaganna Function eka
function getProducts() {
    $db = new Database();
    $hasAllowInGrnColumn = (bool) $db->getRow("SHOW COLUMNS FROM item_master LIKE 'allow_in_grn'");
    $query = $hasAllowInGrnColumn
        ? $db->getRows('SELECT * FROM item_master WHERE (allow_in_grn = 1 OR allow_in_grn IS NULL)')
        : $db->getRows('SELECT * FROM item_master');
    return $query;
}

function load_supplier()  
 {  
      
      $output = '';  
      $db = new Database();
      $grpquery = $db->getRows('SELECT * FROM supplier');
      $grpdata = $grpquery;
        foreach($grpdata as $grpquery) 
            {   

                $grpid = $grpquery['supplier_id']; 
                $output .= '<option value="'.$grpquery['supplier_id'].'">'.$grpquery['supplier_name'].'</option>';

            }
            return $output;  
 }  

 function load_location()  
 {  
      
      $output = '';  
      $db = new Database();
      $query = $db->getRows('SELECT * FROM location_master');
      $data = $query;
        foreach($data as $query) 
            {   

                $id = $query['supplier_id']; 
                $output .= '<option value="'.$query['id'].'">'.$query['name'].'</option>';

            }
            return $output;  
 }  



function load_payment_method()  
 {  
      
      $output = '';  
      $db = new Database();
      $query = $db->getRows('SELECT * FROM payment_method');
      $data = $query;
        foreach($data as $query) 
            {   

                $id = $query['id']; 
                $output .= '<option value="'.$query['id'].'">'.$query['type'].'</option>';

            }
            return $output;  
 }  
function getReferance()
{

//parana id eka search karala aluth id ekak hadagannawa.
$db = new Database();
$getpid = $db->getRow('SELECT max(grn_h_id) as grn_h_id FROM grn_hedder');
$randomNo = rand(1000000,9999999);

$oldpid = $getpid['grn_h_id'];
if($getpid > 0)
{

$newpid =  $oldpid + 1 ; 
}

// product code ekak hadagannawa

echo $refaranceCode = "PUR".$randomNo.$newpid;

}


//Vat eka 

$query_vat = $db->getRow('SELECT * FROM product_vat_master');
$vat_value = $query_vat['rate'];



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
        <title>Add Purchase</title>
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

    <!-- serial number added -->

<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="">ADD SERIAL NUMBERS</h4>
      </div>
      <form method="POST" id="frmAddamount">
      <div class="modal-body">

        <div class="row">
            <div class="col-md-12">
                  <div> 
                  <small>If you have serial numbers please enter below otherwise skip or keep the blank thexboxes</small>
                  </div>  
                    <div class="row" style="margin-top:10px;">
                         <div class="col-xs-12">
                            <div class="form-group">
    Undercontruction
</div>
 
                         </div>

  
                      </div>
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Add</button>
      </div>
     
  </form>
    </div>
  </div>
</div>
<!-- end of the serial number added -->

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
                                <a href="#">GRN</a>
                                <i class="fa fa-circle"></i>
                            </li>
                            <li>
                                <span>Add GRN</span>
                            </li>
                        </ul>
                      
                    </div>
                    <!-- END PAGE BAR -->
                    <!-- BEGIN PAGE TITLE-->
                    <div class="alert alert-success alert-dismissable">
                                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                                        Thank you for trying Stock Manager System. We hope, you will like it. If you find any error/bug or suggestions, please call to +94 0775 489 978 or email to info@switchtech.lk
.
                                    </div>
                    <!-- END PAGE TITLE-->
                    <!-- END PAGE HEADER-->
                  
                    <div class="row">
                        <div class="col-md-12">
                        <div class="portlet box blue-hoki ">
                                            <div class="portlet-title ">
                                                <div class="caption">
                                                    <i class="fa fa-plus"></i>New Parchase</div>
                                                <div class="tools">
                                                    <a href="javascript:;" class="collapse" data-original-title="" title=""> </a>
                                                    <a href="#portlet-config" data-toggle="modal" class="config" data-original-title="" title=""> </a>
                                                    <a href="javascript:;" class="reload" data-original-title="" title=""> </a>
                                                    <a href="javascript:;" class="remove" data-original-title="" title=""> </a>
                                                </div>
                                            </div>
                                            <div class="portlet-body form">
                                                <!-- BEGIN FORM-->
                                                <div class="form-body">
                                                    <div class="row">
                                                        <!-- start left layer -->
                                                        <div class="col-md-6">
                                                <div class="portlet light bordered">
                                                    <div class="portlet-title">
                                                        <div class="caption">
                                                           </i>Select Product</div>
                                                        
                                                    </div>
                                                    <div class="portlet-body form">
                                                           <!-- Tab Start -->
                                                    <div class="portlet-body">
                                    <ul class="nav nav-tabs">
                                        <li class="active">
                                            <a href="#tab_1_1" data-toggle="tab" aria-expanded="true">List Product</a>
                                        </li>
                                        <li class="">
                                            <a href="#tab_1_2" data-toggle="tab" aria-expanded="false">Add Product</a>
                                        </li>
                                       
                                    </ul>
                                    <div class="tab-content">
                                        <div class="tab-pane fade active in" id="tab_1_1">
                                           <!-- BEGIN EXAMPLE TABLE PORTLET-->
                            <div class="portlet light bordered">
                                <div class="portlet-title">
                                   
                                    <div class="tools"> </div>
                                </div>
                                <div class="portlet-body">
                                    <table class="table table-striped table-bordered table-hover dt-responsive" width="100%" id="sample_2">
                                        <thead>
                                            <tr>
                                                <th>Product code</th>
                                                
                                                <th class="all">Product Name</th>
                                                 <th class="all">Stock</th>
                                                <th class="all">Unit Price</th>
                                                <th  class="all"></th>
                                                
                                            
                                            </tr>
                                        </thead>
                                        <tbody>
                                           
                                           <?php $data = getProducts();
                                        foreach($data as $query) { $item_id = $query['item_id'];
                                        
                                            $query_get_qty = $db->getRow('SELECT SUM(ft_blanace) as qty FROM fifo WHERE ft_item = ? AND ft_location = ?',[$item_id,$_SESSION['location']]);
                                                    
                                                    if(empty($query_get_qty['qty'])||$query_get_qty['qty'] == 0.00 ){
                                                        
                                                         $get_qty = "<span style='color:red;'>0.00 </span>";
                                                        
                                                    }else{
                                                        
                                                       $get_qty = $query_get_qty['qty'];
                                                    }
                                                    
                                                    
                                        
                                        ?> 
                                              
                                             <tr>
                                                <td><?php echo  $query['item_code']; ?></td>
                                                <td><?php  echo  $query['item_name']; ?></td>
                                                
                                                 <td><?php echo  $get_qty; ?></td>
                                                <td><?php include('currency.php');?>  <?php echo  $query['item_purchase_price']; ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-primary btn-xs btnAddItemFromList" title="" data-toggle="tooltip" data-item-id="<?php echo $item_id; ?>"  data-item-code="<?php echo  $query['item_code']; ?>" data-item-vat="<?php echo  $query['item_vat']; ?>" data-item-name="<?php  echo  $query['item_name']; ?>" data-item-price="<?php echo  $query['item_purchase_price']; ?>" data-placement="top" data-original-title="Purchase"><i class="fa fa-shopping-cart"></i></button>
                                                </td>
                                                <!--<td><form action="" method="post">
                                                                <input type="hidden" name="product_id" value="<?php echo $item_id; ?>">
                                                                <button type="submit" class="btn btn-primary btn-xs" onclick="myFunction()" title="" data-toggle="tooltip" data-placement="top" data-original-title="Purchase"><i class="fa fa-shopping-cart"></i></button>

                                                            </form>
                                                </td>-->
                                                
                                            </tr>
                                            <?php }
                                            ?>
   
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <!-- END EXAMPLE TABLE PORTLET-->
                                        </div>
                                        <div class="tab-pane fade" id="tab_1_2">
                                             <div class="row">
                        <div class="col-md-12">
                        <!-- start Page -->
                        <div class="portlet light bordered form-fit ">
                                            <div class="portlet-title">
                                                <div class="caption">
                                                    <i class="fa fa-gift"></i>Add Product</div>
                                                <div class="tools">
                                                    <a href="javascript:;" class="collapse" data-original-title="" title=""> </a>
                                                    <a href="#portlet-config" data-toggle="modal" class="config" data-original-title="" title=""> </a>
                                                    <a href="javascript:;" class="reload" data-original-title="" title=""> </a>
                                                    <a href="javascript:;" class="remove" data-original-title="" title=""> </a>
                                                </div>
                                            </div>
                                            <div class="portlet-body form">
                                                <!-- BEGIN FORM -->
                                                <form class="form-horizontal form-bordered form-row-stripped frmAddproduct" method="POST" id="frmAddproduct" name="frmAddproduct"> 
                                                   <div class="form-horizontal form-bordered form-row-stripped">
                                                    <div class="form-body">
                                                        <div class="form-group">
                                                            <label class="control-label col-md-3">Product code</label>
                                                            <div class="col-md-9">
                                                                <input type="text" value="<?php echo $pcode; ?>" class="form-control"  name="pcode">
                                                                
                                                            </div>
                                                        </div>
                                                        <div class="form-group">
                                                            <label class="control-label col-md-3">Product Name*</label>
                                                            <div class="col-md-9">
                                                                <input type="text" class="form-control" name="pname" id="pname"> </div>
                                                        </div>
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
                                                         <div class="form-group">
                                                            <label class="control-label col-md-3">Product Unit *</label>
                                                            <div class="col-md-9">
                                                                <select class="form-control select2_category" name="punit">
                                                                   <?php echo load_uom(); ?>
                                                                   
                                                                </select>
                                                            </div>
                                                        </div>
                                                          <div class="form-group">
                                                            <label class="control-label col-md-3">Purchase Price*</label>
                                                            <div class="col-md-9">
                                                           <div class="input-group">
                                                    <span class="input-group-addon">
                                                        <i class="fa"><?php include('currency.php'); ?>.</i>
                                                    </span>
                                                    <input type="text" class="form-control autoprice" placeholder="0.00" id="purchaseprice" name="purchaseprice" data-a-sep="," data-a-dec="."> </div>
                                                            </div>
                                                        </div>
                                                        
                                                         <div class="form-group">
                                                            <label class="control-label col-md-3">Normal Selling Price*</label>
                                                            <div class="col-md-9">
                                                           <div class="input-group">
                                                    <span class="input-group-addon">
                                                        <i class="fa"><?php include('currency.php'); ?>.</i>
                                                    </span>
                                                    <input type="text" class="form-control autoprice" placeholder="0.00" name="normalsellingprice" data-a-sep="," data-a-dec="."> </div>
                                                            </div>
                                                        </div>
                                                        
                                                      
                                                       

                                                    </div>
                                                    <div class="form-actions">
                                                        <div class="row">
                                                            <div class="col-md-offset-3 col-md-9">
                                                                <button type="submit" class="btn blue" name="sub" id="sub">
                                                                    <i class="fa fa-check"></i>Add Product</button>
                                                                    <div id="response"> </div>
                                                               
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                               </form>
                                                <!-- END FORM-->
                                            </div>
                                        </div>
                        <!-- end page -->
                        </div>
                        
                    </div>
                  
                                        </div>
                                      
                                       
                                    </div>
                                    <div class="clearfix margin-bottom-20"> </div>
                                   
                                  
                                </div>
                                                    <!-- Tab End -->
                                                    </div>
                                                </div>
                                            </div>
                                                        <!-- end left layer -->
                                                                 <!-- start right layer -->
                                                                 <form action="process/add-purchase-process.php" method="POST" enctype="multipart/form-data">
                                                        <div class="col-md-6">
                                                <div class="portlet light bordered"  style="margin-bottom:10px;">
                                                    <div class="portlet-title">
                                                        <div class="caption">Purchase Order</div>
                                                        
                                                    </div>
                                                    <div class="row">
                                                     
                                            <div class="table-responsive">
                                                  <table class="table" style="margin-bottom: 0px;">
                                                    <tr>
                                                        <td><div class="form-group">
                                                <label><strong>Reference No</strong></label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" placeholder="" name="RefNo" readonly value="<?php echo getReferance();?>">
                                                   <span class="input-group-addon">
                                                        <i class="fa fa-key"></i>
                                                    </span>
                                                </div>
                                            </div> </td>
                                                        <td><div class="form-group">
                                                <label><strong>Date</strong></label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" placeholder="" value="<?php echo date("Y-m-d h:i:sa"); ?>" readonly >
                                                   <span class="input-group-addon">
                                                        <i class="fa fa-calendar-check-o"></i>
                                                    </span>
                                                </div></td>
                                                    </tr>
                                                  </table>
                                                </div>          
                                        </div>
                                                   <div class="box-background"> 
                                                     <div class="portlet-body form">
                                                           <div class="row">
                                                                 <div class="col-md-12">
                                                                
                                                                <div class="row static-info">
                                                                    <div class="col-md-5"> <div class="form-group">
                                                <label><strong>Supplier</strong></label>
                                                <select class="form-control" name="supplier">
                                                       <?php echo load_supplier() ;?>
                                                      
                                                    </select> </div> </div>
                                                                    <div class="col-md-7"><div class="form-group">
                                                <label><strong>Location</strong></label>
                                                <select class="form-control"  name="location">
                                                        <?php echo load_location(); ?>
                                                   
                                                    </select> </div></div>
                                                                </div>
                                                               
                                                            </div>
                                                    </div>
                                                   </div>
                                                </div>
                                            </div>

                                            <!-- Start product table -->
                                               <div id="cart_content">
                                    <table class="table table-bordered table-hover myDatatable" id="myDatatable">
    <thead><!-- Table head -->
    <tr>
        <!-- <th class="active ">Sl</th>-->
        <th class="active col-sm-4">Product</th>
        <th class="active col-sm-2">Qty</th>
        <th class="active ">Unit Price</th>
        <th class="active ">Total</th>
        <th class="active col-sm-1">Action</th>

    </tr>
    </thead><!-- / Table head -->
    <tbody><!-- / Table body -->
            <!-- / start cart loop -->
       
            <!-- / end cart loop -->



            <tfoot> 
        <!--get all sub category if not this empty-->
        <tr>
            <td colspan="2" class="text-right active">
                <strong>Total: </strong>
            </td>
            <td colspan="3" class="text-left active ">
               <strong id='cartItemTot'>0.00</strong> 
               <input type="hidden" name="grossTot" id="grossTot">
            </td>
        </tr>
                <tr>
            <td colspan="2" class="text-right active">
                <strong>+VAT: </strong>
            </td>
            <td colspan="3" class="text-left active ">
               <strong id='vatTot'>0.00</strong> 
               <input type="hidden" name="vatTotHidden" id="vatTotHidden">
            </td>

        </tr>
         <tr>
            <td colspan="2" class="text-right active">
                <strong>Grand Total: </strong>
            </td>
            <td colspan="3" class="text-left active ">
               <strong id='grandTot'>0.00</strong> 
               <input type="hidden" name="grandTotTotHidden" id="grandTotTotHidden">
               
            </td>

        </tr>

        <tr>
            <td colspan="2" class="text-right active">
                <strong>Supplier Invoice No#</strong>
            </td>
            <td colspan="3" class="text-left active">
                <input type="text" name="purchase_ref" class="form-control">
            </td>
        </tr>

        <tr>
            <td colspan="2" class="text-right active">
                <strong>Payment Method </strong>
            </td>
            <td colspan="3" class="text-left active">
                <select name="payment_method" class="form-control" id="payment_type">
                    <?php echo load_payment_method();?>
                </select>
            </td>
        </tr>
       <tr class="" id="payment" style="display:none">
           <td colspan="2" class="text-right active">
               <strong>Payment Reference(cheque/card)</strong>
           </td>
           <td colspan="3" class="text-left active">
              <input type="text" name="payment_ref" class="form-control">
           </td>
       </tr>

        <tr>
            <td colspan="3" class="text-right active">

            </td>
            <td colspan="3" class="text-left active">
                <button type="submit" id="btn_purchase"  name="Purchase"class="btn bbtn btn-primary btn-block ">Purchase
                </button>
            </td>
        </tr>
</tfoot>
        </tbody><!-- / Table body -->
</table> <!-- / Table -->
                              </div>

                                            <!-- end product table -->
                                            </form>
                                                        <!-- end right layer -->
                                                    </div>
                                                    </div>
                                             
                                                <!-- END FORM-->
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
        <!-- Auto Numaric Function -->
         <script src="assets/global/plugins/numaricFunction/autoNumeric.js" type="text/javascript"></script>
         
         <!-- Notification function -->
        <script src="assets/global/plugins/notification/jquery.bootstrap-growl.js"></script>
</body>

</html> 


  
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

<!-- auto price set -->
<script type="text/javascript">
jQuery(function($) {
    $('.autoprice').autoNumeric('init');
});

</script>



<script type="text/javascript">
$(document).on('submit','form.frmAddproduct',function(){
  
var data = $(this).serialize();
      $.ajax({
  
 type : 'POST',
 url  : 'process/add-product-process.php',
 mimeType: "multipart/form-data",
 data: new FormData(this), 
 contentType: false,       
 cache: false,       
 processData:false,   
  success :  function(data)
       {
        
       
        $(function () {
                    setTimeout(function() {
                    $.bootstrapGrowl(data, { 
                        type: 'success',
                        align: 'right'
                    });

                }, 1000);
                
             /*  clearInput();*/
            });

      
       }
  });
  return false;


});

</script>


<script>

    counter = 0;
    $('.btnAddItemFromList').click(function() {
             counter = counter + 1;
    var item_code = $(this).attr('data-item-code');
    var item_name = $(this).attr('data-item-name');
    var item_price = $(this).attr('data-item-price');
    var item_id  = $(this).attr('data-item-id');
    var item_vat = $(this).attr('data-item-vat');
    var item_var_rate = 0.00;
    if(item_vat == "Y"){
       item_var_rate = <?php echo json_encode($vat_value); ?>;
    }else{

        item_var_rate = 0.00;
    }
    
    
    
    var item_vat_value = (parseFloat(item_var_rate) / 100) * parseFloat(item_price);
    if(!hasItemAdded(item_id)) {
      $("#myDatatable tbody").append('<tr class="custom-tr">'+
            //'<td class="vertical-td">'+counter+'</td>'+
            '<td class="vertical-td"><input type="hidden" value="'+item_id+'" name="item_id[]"><a data-toggle="modal" data-target="#myModal" ><input type="hidden" value="'+item_name+'" name="item_name[]">'+item_name+'</a></td>' +
            '<td class="vertical-td">'+

                '<input type="text" name="qty[]"  value="1" id="qty" class="form-control">'+

            '</td>'+
            '<td class="vertical-td">'+

                '<input type="text" readonly name="price[]" value="'+item_price+'" id="price" class="form-control">'+
                '<input type="hidden" name="itmVat[]" id="itmVat" data-vat="' + item_vat + '" value="' + item_var_rate + '" class="form-control">'+
 
            '</td>'+
            '<td class="vertical-td itmTot"></td>'+

            '<td class="vertical-td">'+
                '<a href="" class="btn btn-danger btn-xs" title="" data-toggle="tooltip" data-placement="top"  data-row-id="'+counter+'" onclick="return confirm("Are you sure want to delete this record ?");" data-original-title="Delete"><i class="fa fa-trash-o"></i></a></td>'+

        '</tr>');
      }
      calculateTot();
      countSerial();
    });
    
function calculateTot() {
   var grandTotal = 0;
    var tot = 0, vat_item_tot = 0, vat_percentage = <?php echo json_encode($vat_value); ?>;
    $("#myDatatable tbody .custom-tr").each(function () {
        console.log('1st level');
        $(this).find(".itmTot").text((parseFloat($(this).find("td input[id='price']").val()) * parseFloat($(this).find("td input[id='qty']").val())));
        tot += (parseFloat($(this).find("td input[id='price']").val()) * parseFloat($(this).find("td input[id='qty']").val()));
        if ($(this).find("input[name='itmVat[]']").attr('data-vat') == "Y") {
            vat_item_tot += (parseFloat($(this).find("td input[id='price']").val()) * parseFloat($(this).find("td input[id='qty']").val()));
        }
    });
    $("#grossTot").val(tot);
    $('#cartItemTot').text(tot);
    $('#vatTot').text(vat_item_tot * (vat_percentage / 100));
    $('#vatTotHidden').val(vat_item_tot * (vat_percentage / 100));

    grandTotal = (tot);
    $("#grandTot").text(tot + vat_item_tot * (vat_percentage / 100));
    $("#grandTotTotHidden").val(tot + vat_item_tot * (vat_percentage / 100));
}
    function hasItemAdded(item_id){
        var hasAdded = false;
        $('#myDatatable tbody tr td input[name="item_id[]"]').each(function() {
            if($(this).val() == String(item_id)) {
                hasAdded = true;
            }
        })
        return hasAdded;
    }

      $(document).on('change', "#myDatatable tbody .custom-tr td input[id='price'], #myDatatable tbody .custom-tr td input[id='qty']", function (){
            calculateTot();
      });

</script>



