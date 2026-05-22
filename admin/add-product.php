<?php 
ob_start();
error_reporting (E_ALL ^ E_NOTICE);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('include/database.php');
include('include/check_login.php');
include('include/uom_helper.php');
ensureItemUomSchema(new Database());
function filter($var)
{

    return preg_replace('/ [^a-za-z0-9\s@.]/',' ' , $var);
}

function load_all_uoms_array()
{
    $db = new Database();
    return $db->getRows('SELECT uom_id, uom_name FROM item_uom ORDER BY uom_name ASC') ?: [];
}
?>
<?php

// Business Unit dropdown loader
function load_business_units()
{
    $output = '<option value="">Select Business Unit</option>';
    $db = new Database();
    $rows = $db->getRows('SELECT business_unit_id, business_unit_name FROM business_unit_master ORDER BY business_unit_name ASC');
    foreach ($rows as $row) {
        $id = htmlspecialchars($row['business_unit_id'], ENT_QUOTES, 'UTF-8');
        $name = htmlspecialchars($row['business_unit_name'], ENT_QUOTES, 'UTF-8');
        $output .= '<option value="' . $id . '">' . $name . '</option>';
    }
    return $output;
}

// GST code dropdown loader
function load_gst()  
 {  
      $gst_output = '';  
      $db = new Database();
      $gstquery = $db->getRows('SELECT Code, CodeDescription, GSTPercentage FROM DST_Code ORDER BY Code ASC');
      $gstdata = $gstquery;
      $gst_output = '<option value="">Select GST Code</option>';
        foreach($gstdata as $gstrow) 
            {   
                $code = htmlspecialchars($gstrow['Code'], ENT_QUOTES, 'UTF-8');
                $label = htmlspecialchars($gstrow['CodeDescription'], ENT_QUOTES, 'UTF-8');
                $rate = number_format((float)$gstrow['GSTPercentage'], 2, '.', '');
                $gst_output .= '<option value="'.$code.'">'.$code.' - '.$label.' ('.$rate.'%)</option>';

            }
            return $gst_output;  
 }  

?>
<?php 
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
<?php
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


 //warranty values Dropdown ekata dagannawa
function load_warranty()  
 {  
      
      $warranty_output = '';  
      $db = new Database();
      $warrantyquery = $db->getRows('SELECT * FROM item_warranty');
      $warrantydata = $warrantyquery;
      //$warranty_output = '<option value="">Select Group</option>';
        foreach($warrantydata as $warrantyquery) 
            {   

                $uomid = $warrantyquery['warranty_id']; 
                $warranty_output .= '<option value="'.$warrantyquery['warranty_id'].'">'.$warrantyquery['warranty'].'</option>';

            }
            return $warranty_output;  
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
        <title>Add Product | STOCK MANAGMENT</title>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">s
        <meta content="width=device-width, initial-scale=1" name="viewport" />
        <meta content="" name="description" />
        <meta content="" name="author" />
        <?php include('common/head.php'); ?>

        <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.15/dist/summernote.min.css" rel="stylesheet">
        <style>
            .page-title { margin-top: 10px; }
            .product-form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(380px, 1fr)); gap: 24px; }
            .product-form-card { background: #ffffff; border: 1px solid #dde3ec; border-radius: 8px; padding: 24px; box-shadow: 0 6px 18px rgba(52, 73, 94, 0.08); }
            .product-form-card.full-width { grid-column: 1 / -1; }
            .product-form-card h5 { font-size: 13px; letter-spacing: 0.08em; text-transform: uppercase; color: #60718b; margin-top: 0; margin-bottom: 18px; }
            .product-form-card .form-group { margin-bottom: 18px; }
            .product-form-card .control-label { font-weight: 600; color: #2f3b52; }
            .product-form-card .help-block { font-size: 12px; color: #97a4b8; margin-top: 6px; }
            .product-form-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; }
            .product-form-row { grid-column: 1 / -1; margin: 0 -12px; }
            .product-form-row > [class*="col-"] { padding-left: 12px; padding-right: 12px; }
            .product-form-row .product-form-card { height: 100%; }
            .product-thumb-preview { width: 100%; max-width: 240px; border-radius: 8px; border: 1px solid #e1e7f0; background: #f9fbfd; padding: 12px; display: flex; align-items: center; justify-content: center; }
            .product-thumb-preview img { max-width: 100%; border-radius: 6px; }
            .product-images-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 16px; }
            .product-images-grid img { width: 100%; border-radius: 6px; border: 1px solid #e1e7f0; }
            .specifications-table { width: 100%; border-collapse: collapse; }
            .specifications-table th, .specifications-table td { padding: 8px 12px; border-bottom: 1px solid #eef2f7; }
            .specifications-table th { background: #f4f6fb; text-transform: uppercase; font-size: 12px; letter-spacing: 0.05em; color: #5d6d8a; }
            /* Colorful card header for customer detail page */
            .customer-card-header {
                background: linear-gradient(90deg, #028d7aff 0%, #066c74ff 100%);
                color: #fff;
                padding: 4px 4px;
                border-radius: 8px 8px 0 0;
                font-size: 20px;
                font-weight: 700;
                letter-spacing: 0.08em;
                box-shadow: 0 2px 8px rgba(255,126,95,0.12);
                margin-bottom: 0;
                display: flex;
                align-items: center;
                gap: 12px;
            }
            .customer-card-header .fa {
                font-size: 22px;
                margin-right: 10px;
                opacity: 0.85;
            }
            /* Section card h4 headers with same color design as customer header */
            .section-card {
                background: linear-gradient(90deg, #028d7aff 0%, #066c74ff 100%);
                color: #fff;
                padding: 8px 16px;
                border-radius: 8px 8px 0 0;
                font-size: 16px;
                font-weight: 600;
                letter-spacing: 0.08em;
                box-shadow: 0 2px 8px rgba(255,126,95,0.12);
                margin-bottom: 0;
                display: flex;
                align-items: center;
                gap: 12px;
            }
            .section-card.h4 {
                font-size: 14px;
                padding: 6px 12px;
            }
            .section-card .fa {
                font-size: 18px;
                opacity: 0.85;
            }
            @media (max-width: 1366px) { .product-form-grid { grid-template-columns: 1fr; } }
            @media (max-width: 991px) { .product-form-row { margin: 0; } }
            @media (max-width: 767px) { .product-form-card { padding: 18px; } }
        </style>
    
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
          
                    <div class="page-bar">
                        <ul class="page-breadcrumb">
                            <li>
                                <a href="index.php">Home</a>
                                <i class="fa fa-circle"></i>
                            </li>
                            <li>
                                <a href="manage-product.php">Products</a>
                                <i class="fa fa-circle"></i>
                            </li>
                            <li>
                                <span>Add Product</span>
                            </li>
                        </ul>
                    </div>
                  
                    <div class="alert <?php echo $MessageClass; ?> alert-dismissable">
                                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                                        <?php echo $CompanyMessage; ?>
                                    </div>
                   
                    <h3 class="page-title">Add Product
                        <small></small>
                    </h3>
                 
                    <div class="portlet light bordered form-fit">
                        <div class="customer-card-header">
                            <i class="fa fa-plus"></i>
                            Product Details
                        </div>
                        <div class="portlet-body form">
                            <!-- BEGIN FORM -->
                            <form class="form-horizontal form-bordered form-row-stripped" method="POST" id="frmAddproduct" enctype="multipart/form-data">
                                <div class="form-body">
                                    <div class="product-form-grid">
                                        <div class="product-form-card">
                                            <h4 class="section-card h4">General Details</h4>
                                                                <div class="row">
                                                                    <div class="col-lg-6 col-md-12">
                                                                        <div class="form-group">
                                                                            <label class="control-label" for="pcode">Product Code<span style="color:#e7505a;">*</span></label>
                                                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($pcode, ENT_QUOTES, 'UTF-8'); ?>" name="pcode" id="pcode" required>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-6 col-md-12">
                                                                        <div class="form-group">
                                                                            <label class="control-label" for="pname">Product Name<span style="color:#e7505a;">*</span></label>
                                                                            <input type="text" class="form-control" name="pname" id="pname" placeholder="e.g. Classic White Bread" required>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-lg-6 col-md-12">
                                                                        <div class="form-group">
                                                                            <label class="control-label" for="pgroup">Group<span style="color:#e7505a;">*</span></label>
                                                                            <select class="form-control" name="pgroup" id="pgroup" required>
                                                                                <?php echo load_groups(); ?>
                                                                            </select>
                                                                            <span class="help-block">Select a group to load relevant product types.</span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-6 col-md-12">
                                                                        <div class="form-group">
                                                                            <label class="control-label" for="ptype">Type<span style="color:#e7505a;">*</span></label>
                                                                            <select class="form-control" name="ptype" id="ptype" required>
                                                                                <option value="">Select Type</option>
                                                                            </select>
                                                                            <span class="help-block">Choose a type to unlock category options.</span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-lg-6 col-md-12">
                                                                        <div class="form-group">
                                                                            <label class="control-label" for="pbusinessunit">Business Unit<span style="color:#e7505a;">*</span></label>
                                                                            <select class="form-control select2_category" name="pbusinessunit" id="pbusinessunit" required>
                                                                                <?php echo load_business_units(); ?>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-6 col-md-12">
                                                                        <div class="form-group">
                                                                            <label class="control-label" for="pcategory">Category</label>
                                                                            <select class="form-control select2_category" name="pcategory" id="pcategory">
                                                                                <option value="">Select Category</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                        <div class="product-form-card">
                                            <h5 class="section-card h4">Pricing</h5>
                                                                <div class="row">
                                                                    <div class="col-lg-6 col-md-12">
                                                                        <div class="form-group">
                                                                            <label class="control-label" for="purchaseprice">Purchase Price<span style="color:#95a5a6;"> (Optional)</span></label>
                                                                            <div class="input-group">
                                                                                <span class="input-group-addon"><i class="fa"><?php include('currency.php'); ?>.</i></span>
                                                                                <input type="text" class="form-control autoprice" placeholder="0.00" id="purchaseprice" name="purchaseprice" data-a-sep="," data-a-dec=".">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-lg-6 col-md-12">
                                                                        <div class="form-group">
                                                                            <label class="control-label" for="normalsellingprice">Standard Selling Price<span style="color:#95a5a6;"> (Optional)</span></label>
                                                                            <div class="input-group">
                                                                                <span class="input-group-addon"><i class="fa"><?php include('currency.php'); ?>.</i></span>
                                                                                <input type="text" class="form-control autoprice" placeholder="0.00" id="normalsellingprice" name="normalsellingprice" data-a-sep="," data-a-dec=".">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                        <div class="product-form-card">
                                            <h5 class="section-card h4">Logistics &amp; Compliance</h5>
                                                                <div class="row">
                                                                    <div class="col-lg-6 col-md-12">
                                                                        <div class="form-group">
                                                                            <label class="control-label" for="productWeight">Product Weight (Kg)</label>
                                                                            <div class="input-group">
                                                                                <input type="number" class="form-control" name="productWeight" id="productWeight" placeholder="0.000" min="0" step="0.001">
                                                                                <span class="input-group-addon">Kg</span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-6 col-md-12">
                                                                        <div class="form-group">
                                                                            <label class="control-label" for="pack_size">Pack Size</label>
                                                                            <input type="text" class="form-control" name="pack_size" id="pack_size" placeholder="e.g. 12 pcs / carton">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-lg-6 col-md-12">
                                                                        <div class="form-group">
                                                                            <label class="control-label" for="order_qty_min">Order Qty (Min)</label>
                                                                            <input type="number" class="form-control" name="order_qty_min" id="order_qty_min" placeholder="0" min="0" step="0.01">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-6 col-md-12">
                                                                        <div class="form-group">
                                                                            <label class="control-label" for="order_qty_max">Order Qty (Max)</label>
                                                                            <input type="number" class="form-control" name="order_qty_max" id="order_qty_max" placeholder="0" min="0" step="0.01">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-lg-6 col-md-12">
                                                                        <div class="form-group">
                                                                            <label class="control-label" for="low_stock_qty">Low stock qty</label>
                                                                            <input type="number" class="form-control" name="low_stock_qty" id="low_stock_qty" placeholder="5" min="0" step="1" value="5">
                                                                            <span class="help-block">Reorder level; used by low-stock reports and alerts.</span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-lg-6 col-md-12">
                                                                        <div class="form-group">
                                                                            <label class="control-label" for="acc_posting_grp_code">Accounting Posting Group</label>
                                                                            <input type="text" class="form-control" name="acc_posting_grp_code" id="acc_posting_grp_code" placeholder="e.g. RETAIL-FOOD">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-6 col-md-12">
                                                                        <div class="form-group">
                                                                            <label class="control-label" for="gst_inclusion">GST Inclusion</label>
                                                                            <select class="form-control select2_category" name="gst_inclusion" id="gst_inclusion">
                                                                                <option value="N">Not Included +Tax</option>
                                                                                <option value="Y">Included +Tax</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-lg-6 col-md-12">
                                                                        <div class="form-group">
                                                                            <label class="control-label" for="gst_code">GST Code</label>
                                                                            <select class="form-control" name="gst_code" id="gst_code">
                                                                                <?php echo load_gst(); ?>
                                                                            </select>
                                                                            <span class="help-block">Link the product to a GST code for reporting.</span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-6 col-md-12">
                                                                        <div class="form-group">
                                                                            <label class="control-label" for="productCod">Cash On Delivery</label>
                                                                            <select class="form-control select2_category" name="productCod" id="productCod">
                                                                                <option value="enable">Enable</option>
                                                                                <option value="disable">Disable</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-lg-6 col-md-12">
                                                                        <div class="form-group">
                                                                            <label class="control-label" for="warranty">Warranty Months</label>
                                                                            <select class="form-control select2_category" name="warranty" id="warranty">
                                                                                <option value="">No warranty</option>
                                                                                <?php echo load_warranty(); ?>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-6 col-md-12">
                                                                        <div class="form-group">
                                                                            <label class="control-label">Track Serial Numbers</label>
                                                                            <div class="radio-list">
                                                                                <label class="radio-inline"><input type="radio" name="sirial" id="sirial_yes" value="Y" checked> Yes</label>
                                                                                <label class="radio-inline"><input type="radio" name="sirial" id="sirial_no" value="N"> No</label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                        <div class="product-form-card full-width" id="altUomCard">
                                            <h5 class="section-card h4">Alternative Units of Measure</h5>
                                            <p class="help-block" style="margin-bottom:12px;">The <strong>Unit of Measure</strong> selected on this form is the <strong>base UOM</strong> (qty per unit = 1). Add other units like Box, Carton, Pack and the conversion factor in base units. Mark one as default for purchase and one for sales.</p>
                                            <div id="baseUomDisplay" style="margin-bottom:10px;font-weight:600;color:#28527a;">Base UOM: <span id="baseUomLabel">(select Unit of Measure above)</span></div>
                                            <table class="table table-bordered" id="altUomTable" style="margin-bottom:8px;">
                                                <thead style="background:#f4f6fb;">
                                                    <tr>
                                                        <th style="width:32%">UOM</th>
                                                        <th style="width:22%">Qty per UOM (in base)</th>
                                                        <th style="width:18%;text-align:center;">Default Purchase</th>
                                                        <th style="width:18%;text-align:center;">Default Sales</th>
                                                        <th style="width:10%;text-align:center;">&nbsp;</th>
                                                    </tr>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                            <button type="button" class="btn btn-sm btn-default" id="btnAddAltUom"><i class="fa fa-plus"></i> Add Unit</button>
                                            <input type="hidden" name="alt_uoms_json" id="alt_uoms_json" value="[]">
                                            <script>window.__ALL_UOMS__ = <?php echo json_encode(load_all_uoms_array()); ?>;</script>
                                        </div>

                                        <div class="row product-form-row">
                                            <div class="col-md-6">
                                                <div class="product-form-card">
                                                    <h5 class="section-card h4">Status &amp; Fulfilment</h5>
                                                    <div class="form-group">
                                                        <label class="control-label" for="productStatus">Product Status</label>
                                                        <select class="form-control" name="productStatus" id="productStatus">
                                                            <option value="Normal" selected>Normal</option>
                                                            <option value="Offline">Offline</option>
                                                            <option value="OutofStock">Out of Stock</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="control-label" for="ImmediatePickup">Immediate Pickup</label>
                                                        <select class="form-control" name="ImmediatePickup" id="ImmediatePickup">
                                                            <option value="No" selected>No</option>
                                                            <option value="Yes">Yes</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="control-label" for="is_raw_material">Raw Material</label>
                                                        <select class="form-control" name="is_raw_material" id="is_raw_material">
                                                            <option value="0" selected>No</option>
                                                            <option value="1">Yes</option>
                                                        </select>
                                                        <span class="help-block">Mark as raw material to show in Purchase Order items.</span>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="control-label">Allow In Sales</label>
                                                        <div>
                                                            <label style="font-weight:normal;"><input type="checkbox" name="allow_in_sales" id="allow_in_sales" value="1" checked> Allow this product to be added to Sales / Cart Orders</label>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="control-label">Allow In GRN</label>
                                                        <div>
                                                            <label style="font-weight:normal;"><input type="checkbox" name="allow_in_grn" id="allow_in_grn" value="1" checked> Allow this product to be added to Purchase / GRN</label>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="control-label" for="batch_tracking">Batch / Serial Tracking</label>
                                                        <select class="form-control" name="batch_tracking" id="batch_tracking">
                                                            <option value="NONE" selected>Disabled</option>
                                                            <option value="BATCH">Batch No Tracking</option>
                                                            <option value="SERIAL">Serial No Tracking</option>
                                                        </select>
                                                        <span class="help-block">Enable to track batch numbers or serial numbers during GRN, transfers, and issues.</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="product-form-card" id="priceTiersCard">
                                                    <h5 class="section-card h4"><i class="fa fa-tags"></i> Qty Price Breaks <small style="font-weight:400; color:#97a4b8; font-size:12px; text-transform:none; letter-spacing:0;">- set a lower price when a customer orders above a certain quantity</small></h5>
                                                    <input type="hidden" name="price_tiers_json" id="price_tiers_json" value="[]">
                                                    <table class="table table-bordered" id="priceTiersTable" style="max-width:520px;">
                                                        <thead>
                                                            <tr style="background:#f4f6fb;">
                                                                <th style="width:180px;">Min Qty (&ge;)</th>
                                                                <th>Unit Price</th>
                                                                <th style="width:50px;"></th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="priceTiersTbody">
                                                            <tr id="priceTiersEmpty"><td colspan="3" style="color:#aaa; font-style:italic; text-align:center;">No tiers set - standard price always applies</td></tr>
                                                        </tbody>
                                                    </table>
                                                    <button type="button" class="btn btn-primary btn-sm" id="btnAddTier"><i class="fa fa-plus"></i> Add Tier</button>
                                                    <p class="help-block" style="margin-top:12px;">Click Add Tier to define a minimum quantity and special unit price.</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="product-form-card full-width">
                                            <h5 class="section-card h4">Additional Product Information</h5>
                                            <div class="row">
                                                <div class="col-lg-6 col-md-12">
                                                    <div class="form-group">
                                                        <label class="control-label" for="wholesale_price">Wholesale Price</label>
                                                        <input type="number" class="form-control autoprice" placeholder="0.00" id="wholesale_price" name="wholesale_price" data-a-sep="," data-a-dec="." step="0.01" min="0">
                                                    </div>
                                                </div>
                                                <div class="col-lg-6 col-md-12">
                                                    <div class="form-group">
                                                        <label class="control-label" for="retail_price">Retail Price</label>
                                                        <input type="number" class="form-control autoprice" placeholder="0.00" id="retail_price" name="retail_price" data-a-sep="," data-a-dec="." step="0.01" min="0">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-lg-6 col-md-12">
                                                    <div class="form-group">
                                                        <label class="control-label" for="item_weight_g">Weight (grams)</label>
                                                        <input type="number" class="form-control" name="item_weight_g" id="item_weight_g" placeholder="0" min="0">
                                                    </div>
                                                </div>
                                                <div class="col-lg-6 col-md-12">
                                                    <div class="form-group">
                                                        <label class="control-label" for="pack_weight_g">Pack Weight (grams)</label>
                                                        <input type="number" class="form-control" name="pack_weight_g" id="pack_weight_g" placeholder="0" min="0">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-lg-6 col-md-12">
                                                    <div class="form-group">
                                                        <label class="control-label" for="minimum_order">Minimum Order Quantity</label>
                                                        <input type="number" class="form-control" name="minimum_order" id="minimum_order" placeholder="0" min="0">
                                                    </div>
                                                </div>
                                                <div class="col-lg-6 col-md-12">
                                                    <div class="form-group">
                                                        <label class="control-label" for="unit_of_measure">Unit of Measure</label>
                                                        <select class="form-control" name="unit_of_measure" id="unit_of_measure">
                                                            <option value="">Select Unit of Measure</option>
                                                            <?php foreach (load_all_uoms_array() as $uomRow): ?>
                                                                <option value="<?php echo htmlspecialchars($uomRow['uom_name'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($uomRow['uom_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="form-group">
                                                        <label class="control-label" for="additional_uoms">Additional UOM <span style="color:#95a5a6;font-weight:normal;">(used as options in Alternative Units of Measure)</span></label>
                                                        <select class="form-control" name="additional_uoms[]" id="additional_uoms" multiple size="5">
                                                            <?php foreach (load_all_uoms_array() as $uomRow): ?>
                                                                <option value="<?php echo htmlspecialchars($uomRow['uom_name'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($uomRow['uom_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <p class="help-block" style="margin-top:4px;">Hold Ctrl (Cmd on Mac) to select multiple. The base Unit of Measure is excluded automatically.</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-lg-6 col-md-12">
                                                    <div class="form-group">
                                                        <label class="control-label" for="pack_type">Pack Type</label>
                                                        <select class="form-control" name="pack_type" id="pack_type">
                                                            <option value="Bag">Bag</option>
                                                            <option value="Box">Box</option>
                                                            <option value="Carton">Carton</option>
                                                            <option value="Packet">Packet</option>
                                                            <option value="Tray">Tray</option>
                                                            <option value="Bottle">Bottle</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6 col-md-12">
                                                    <div class="form-group">
                                                        <label class="control-label" for="live">Live Status</label>
                                                        <select class="form-control" name="live" id="live">
                                                            <option value="yes" selected>Live</option>
                                                            <option value="no">Not Live</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label" for="nutritional_label">Nutritional Label</label>
                                                <input type="text" class="form-control" name="nutritional_label" id="nutritional_label" placeholder="e.g. Contains gluten, nuts">
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label" for="product_specification">Product Specification</label>
                                                <input type="text" class="form-control" name="product_specification" id="product_specification" placeholder="e.g. Organic, Halal certified">
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label" for="default_label">Default Label</label>
                                                <input type="text" class="form-control" name="default_label" id="default_label" placeholder="e.g. Fresh Baked Daily">
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label" for="seasonal_rule">Seasonal Rule</label>
                                                <input type="text" class="form-control" name="seasonal_rule" id="seasonal_rule" placeholder="e.g. Available only in winter">
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label" for="food_declarations">Food Declarations</label>
                                                <textarea class="form-control" rows="3" name="food_declarations" id="food_declarations" placeholder="Allergen information, ingredients"></textarea>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label">Availability Days</label>
                                                <div class="row">
                                                    <div class="col-sm-2"><label><input type="checkbox" name="avail_monday" value="1" checked> Mon</label></div>
                                                    <div class="col-sm-2"><label><input type="checkbox" name="avail_tuesday" value="1" checked> Tue</label></div>
                                                    <div class="col-sm-2"><label><input type="checkbox" name="avail_wednesday" value="1" checked> Wed</label></div>
                                                    <div class="col-sm-2"><label><input type="checkbox" name="avail_thursday" value="1" checked> Thu</label></div>
                                                    <div class="col-sm-2"><label><input type="checkbox" name="avail_friday" value="1" checked> Fri</label></div>
                                                    <div class="col-sm-2"><label><input type="checkbox" name="avail_saturday" value="1" checked> Sat</label></div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-sm-2"><label><input type="checkbox" name="avail_sunday" value="1" checked> Sun</label></div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label" for="hide_to_all_customers">Hide to All Customers</label>
                                                <select class="form-control" name="hide_to_all_customers" id="hide_to_all_customers">
                                                    <option value="0" selected>No</option>
                                                    <option value="1">Yes</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label" for="sale_or_return">Sale or Return</label>
                                                <select class="form-control" name="sale_or_return" id="sale_or_return">
                                                    <option value="0" selected>No</option>
                                                    <option value="1">Yes</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="product-form-card full-width">
                                            <h5 class="section-card h4">Media &amp; Descriptions</h5>
                                                                <div class="form-group">
                                                                    <label class="control-label" for="img1">Product Thumb<span style="color:#e7505a;">*</span></label>
                                                                    <div class="fileinput fileinput-new" data-provides="fileinput">
                                                                        <span class="btn btn-default btn-file">
                                                                            <span class="fileinput-new">Choose</span>
                                                                            <span class="fileinput-exists">Change</span>
                                                                            <input type="file" name="img1" id="img1">
                                                                        </span>
                                                                        <span class="fileinput-filename"></span>
                                                                        <a href="#" class="close fileinput-exists" data-dismiss="fileinput" style="float: none">×</a>
                                                                    </div>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label class="control-label" for="discription">Description</label>
                                                                    <textarea class="form-control" rows="4" name="discription" id="discription"></textarea>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label class="control-label" for="discription2">Description 2</label>
                                                                    <textarea class="form-control" rows="4" name="discription2" id="discription2"></textarea>
                                                                </div>
                                                            </div>
                                                        </div>

                                                    </div>
                                                    <div class="form-actions">
                                                        <div class="product-form-actions">
                                                            <button type="submit" class="btn blue" name="sub" id="sub">
                                                                <i class="fa fa-check"></i> Add Product
                                                            </button>
                                                            <div id="response"></div>
                                                        </div>
                                                    </div>
                                                </form>
                                                <!-- END FORM-->
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
        <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.15/dist/summernote.min.js"></script>
        <script src="assets/custom/product-alt-uom.js"></script>

  
        <script>
    $(document).ready(function() {
        $('#discription').summernote();
    });
    $(document).ready(function() {
        $('#discription2').summernote();
    });
  </script>


<!-- Type Droup downekata values assign karanawa  -->

<script>  
 $(document).ready(function(){  
     function syncRawMaterialFromGroup() {
         var selectedGroupText = $.trim($('#pgroup option:selected').text());
         if (/raw\s*mat/i.test(selectedGroupText)) {
             $('#is_raw_material').val('1').trigger('change');
         }
     }

      $('#pgroup').change(function(){  
           var group_id = $(this).val();  
         syncRawMaterialFromGroup();
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

         syncRawMaterialFromGroup();
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

<!-- Dynamic required field indicators based on Raw Material selection -->
<script type="text/javascript">
$(document).ready(function() {
    function updatePriceFieldLabels() {
        // Purchase Price label
        var purchasePriceLabel = $('label[for="purchaseprice"]');
        // Sales Price label
        var salesPriceLabel = $('label[for="normalsellingprice"]');

        purchasePriceLabel.html('Purchase Price<span style="color:#95a5a6;"> (Optional)</span>');
        salesPriceLabel.html('Standard Selling Price<span style="color:#95a5a6;"> (Optional)</span>');
    }
    
    // Update on page load
    updatePriceFieldLabels();
    
    // Update when Raw Material dropdown changes
    $('#is_raw_material').on('change', function() {
        updatePriceFieldLabels();
    });
});
</script>
<!-- <script type="text/javascript">
                     $(function () {
                    setTimeout(function() {
                    $.bootstrapGrowl("This is another test.", { 
                        type: 'success',
                        align: 'center'
                    });

                }, 1000);
                
               
            });

        </script> -->



<script type="text/javascript">
(function() {
    var form = document.getElementById('frmAddproduct');
    if (!form) {
        return;
    }

    form.setAttribute('novalidate', 'novalidate');

    function notify(type, message) {
        if (window.jQuery && jQuery.bootstrapGrowl) {
            jQuery.bootstrapGrowl(message, {
                type: type,
                align: 'right',
                delay: 4000
            });
        } else {
            var textMessage = String(message).replace(/<[^>]*>?/gm, '');
            alert(textMessage);
        }
    }

    function setFieldError(fieldId, hasError) {
        var el = document.getElementById(fieldId);
        if (!el) {
            return;
        }
        if (hasError) {
            el.classList.add('has-error');
            el.style.borderColor = '#e7505a';
        } else {
            el.classList.remove('has-error');
            el.style.borderColor = '';
        }
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        if (form.dataset.submitting === '1') {
            return;
        }

        var errors = [];

        var pname = (document.getElementById('pname') || {}).value || '';
        var pcode = (document.getElementById('pcode') || {}).value || '';
        var pgroup = (document.getElementById('pgroup') || {}).value || '';
        var ptype = (document.getElementById('ptype') || {}).value || '';
        var purchaseprice = (document.getElementById('purchaseprice') || {}).value || '';
        var normalsellingprice = (document.getElementById('normalsellingprice') || {}).value || '';

        pname = pname.trim();
        pcode = pcode.trim();
        purchaseprice = purchaseprice.trim();
        normalsellingprice = normalsellingprice.trim();

        if (pcode === '') {
            errors.push('Product Code is required');
            setFieldError('pcode', true);
        } else {
            setFieldError('pcode', false);
        }

        if (pname === '') {
            errors.push('Product Name is required');
            setFieldError('pname', true);
        } else {
            setFieldError('pname', false);
        }

        if (pgroup === '') {
            errors.push('Group is required');
            setFieldError('pgroup', true);
        } else {
            setFieldError('pgroup', false);
        }

        if (ptype === '') {
            errors.push('Type is required');
            setFieldError('ptype', true);
        } else {
            setFieldError('ptype', false);
        }

        var pbusinessunit = (document.getElementById('pbusinessunit') || {}).value || '';
        if (pbusinessunit === '') {
            errors.push('Business Unit is required');
            setFieldError('pbusinessunit', true);
        } else {
            setFieldError('pbusinessunit', false);
        }

        setFieldError('purchaseprice', false);
        setFieldError('normalsellingprice', false);

        if (errors.length > 0) {
            notify('danger', '<strong>Please fix the following:</strong><br>' + errors.join('<br>'));
            return;
        }

        var submitBtn = document.getElementById('sub');
        var originalBtnText = submitBtn ? submitBtn.innerHTML : '';
        if (submitBtn) {
            submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Adding...';
            submitBtn.disabled = true;
        }

        form.dataset.submitting = '1';

        fetch('process/add-product-process.php', {
            method: 'POST',
            body: new FormData(form)
        })
            .then(function(response) { return response.text(); })
            .then(function(text) {
                if (submitBtn) {
                    submitBtn.innerHTML = originalBtnText;
                    submitBtn.disabled = false;
                }
                form.dataset.submitting = '0';

                try {
                    var jsonobj = JSON.parse(text);
                    var notificationType = jsonobj.status == true ? 'success' : 'danger';
                    notify(notificationType, jsonobj.message || 'Saved');

                    if (jsonobj.status == true) {
                        setTimeout(function() {
                            window.location.replace('edit-product.php?pid=' + jsonobj.id + '#tab_5_2');
                        }, 1500);
                    }
                } catch (err) {
                    notify('danger', 'Error processing response. Please try again.');
                }
            })
            .catch(function() {
                if (submitBtn) {
                    submitBtn.innerHTML = originalBtnText;
                    submitBtn.disabled = false;
                }
                form.dataset.submitting = '0';
                notify('danger', 'Error submitting form. Please try again.');
            });
    });
})();
</script>
<script>
// ── Qty Price Tiers ──────────────────────────────────────────
(function() {
    var tiers = [];

    function syncHidden() {
        $('#price_tiers_json').val(JSON.stringify(tiers));
    }

    function renderTiers() {
        var tbody = $('#priceTiersTbody');
        tbody.empty();
        if (tiers.length === 0) {
            tbody.append('<tr id="priceTiersEmpty"><td colspan="3" style="color:#aaa; font-style:italic; text-align:center;">No tiers set — standard price always applies</td></tr>');
            return;
        }
        tiers.forEach(function(t, i) {
            tbody.append(
                '<tr data-idx="' + i + '">' +
                '<td><input type="number" class="form-control input-sm tier-qty" min="1" step="1" value="' + t.min_qty + '" style="width:110px;"></td>' +
                '<td><input type="number" class="form-control input-sm tier-price" min="0" step="0.01" value="' + t.unit_price + '" style="width:120px;"></td>' +
                '<td style="text-align:center;"><button type="button" class="btn btn-danger btn-xs tier-remove" title="Remove"><i class="fa fa-trash"></i></button></td>' +
                '</tr>'
            );
        });
        syncHidden();
    }

    $('#btnAddTier').on('click', function() {
        tiers.push({ min_qty: 1, unit_price: 0 });
        renderTiers();
        // focus first new row
        $('#priceTiersTbody tr:last .tier-qty').focus().select();
    });

    $(document).on('change input', '.tier-qty', function() {
        var idx = $(this).closest('tr').data('idx');
        tiers[idx].min_qty = Math.max(1, parseInt($(this).val()) || 1);
        syncHidden();
    });

    $(document).on('change input', '.tier-price', function() {
        var idx = $(this).closest('tr').data('idx');
        tiers[idx].unit_price = Math.max(0, parseFloat($(this).val()) || 0);
        syncHidden();
    });

    $(document).on('click', '.tier-remove', function() {
        var idx = $(this).closest('tr').data('idx');
        tiers.splice(idx, 1);
        renderTiers();
    });
})();
</script>
</body>

</html>



