<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

session_start();
include('include/database.php');
include('include/check_login.php');

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function getRepeatUnits() {
    try {
        $db = new Database();
        return $db->getRows('SELECT id, name, display_name FROM repeat_units ORDER BY id ASC');
    } catch (Exception $e) {
        return [];
    }
}

function getDeliveryRoutes() {
    try {
        $db = new Database();
        return $db->getRows('SELECT id, route_name FROM delivery_route_master WHERE is_active = 1 ORDER BY route_name ASC');
    } catch (Exception $e) {
        return [];
    }
}

function getPaymentTerms() {
    try {
        $db = new Database();
        return $db->getRows('SELECT payment_terms_id, payment_terms_name FROM payment_terms ORDER BY payment_terms_name ASC');
    } catch (Exception $e) {
        return [];
    }
}

function getPriceTypes() {
    try {
        $db = new Database();
        return $db->getRows('SELECT id, name FROM price_types ORDER BY name ASC');
    } catch (Exception $e) {
        return [];
    }
}

$message = '';
$MessageClass = '';
$status = false;
$db = null;
$generatedCode = 'SUPP-00001';

try {
    $db = new Database();
    $row = $db->getRow('SELECT MAX(supplier_id) AS id FROM supplier');
    $nextId = (int) ($row['id'] ?? 0) + 1;
    $generatedCode = 'SUPP-' . str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
} catch (Exception $e) {
    $message = 'Database connection error: ' . $e->getMessage();
    $MessageClass = 'alert-danger';
}

$formData = [
    'supplier_code' => $generatedCode,
    'supplier_name' => '',
    'legal_name' => '',
    'trading_name' => '',
    'supplier_email' => '',
    'supplier_phone' => '',
    'supplier_mobile' => '',
    'address_line_1' => '',
    'address_line_2' => '',
    'city' => '',
    'postal_code' => '',
    'credit_limit' => '',
    'account_hold' => 0,
    'abn_no' => '',
    'acn_no' => '',
    'vat_registered' => 0,
    'gst_no' => '',
    'payment_terms_id' => '',
    'supplier_price_type_id' => '',
    'supplier_note' => '',
    'supplier_remarks' => '',
    'is_active' => 1,
    'locked' => 0,
    'min_order_amount' => '',
    'emergency_contact_name' => '',
    'emergency_contact_email' => '',
    'emergency_contact_telephone' => '',
    'custom_url_link' => '',
    'google_map_link' => '',
    'contact_name' => '',
    'contact_email' => '',
    'contact_telephone' => '',
];

$shippingData = [
    [
        'label' => 'Primary',
        'address_line_1' => '',
        'address_line_2' => '',
        'city' => '',
        'postal_code' => '',
        'contact_no' => '',
        'contact_person_name' => '',
        'contact_person_email' => '',
        'contact_person_phone' => '',
        'remarks' => '',
        'note_to_deliver' => '',
        'delivery_start_time' => '',
        'delivery_end_time' => '',
        'has_door_key' => 0,
        'has_shop_alarm' => 0,
        'attribute_1' => '',
        'attribute_2' => '',
        'attribute_3' => '',
        'delivery_route_id' => '',
        'is_default' => 1,
    ],
];

$countries = [];
$paymentTerms = [];
$priceTypes = [];

try {
    $db = new Database();
    $countries = $db->getRows('SELECT * FROM countries ORDER BY country_name ASC');
    $paymentTerms = $db->getRows('SELECT * FROM payment_terms ORDER BY payment_terms_name ASC');
    $priceTypes = $db->getRows('SELECT * FROM price_type ORDER BY description ASC');
} catch (Exception $e) {
    // ignore
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
    <title>Add Supplier</title>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta content="width=device-width, initial-scale=1" name="viewport" />
        <meta content="" name="description" />
        <meta content="" name="author" />
        <?php include('common/head.php'); ?>
       </head>
    <!-- END HEAD -->
<!-- textboxes filter only numbers  -->
  <SCRIPT language=Javascript>
       <!--
       function isNumberKey(evt)
       {
          var charCode = (evt.which) ? evt.which : evt.keyCode;
          if (charCode != 46 && charCode > 31 
            && (charCode < 48 || charCode > 57))
             return false;

          return true;
       }
       //-->
    </SCRIPT>
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
                                <a href="#">Add Supplier</a>
                               
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
                                                    <i class="fa fa-gift"></i>Add Supplier</div>
                                                <div class="tools">
                                                    <a href="javascript:;" class="collapse" data-original-title="" title=""> </a>
                                                    <a href="#portlet-config" data-toggle="modal" class="config" data-original-title="" title=""> </a>
                                                    <a href="javascript:;" class="reload" data-original-title="" title=""> </a>
                                                    <a href="javascript:;" class="remove" data-original-title="" title=""> </a>
                                                </div>
                                            </div>
                                            <div class="portlet-body form">
                                                <!-- BEGIN FORM-->
                                                <form action="" id="frnAddsupplier" class="form-horizontal form-bordered form-row-stripped" method="POST" enctype="multipart/form-data">
                                                    <div class="form-body">
                                                        <div class="panel panel-primary" style="margin-bottom: 20px;">
                                                            <div class="panel-heading" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 15px;">
                                                                <h4 class="panel-title" style="margin: 0; font-size: 16px; font-weight: 600;">
                                                                    <i class="fa fa-user"></i> Basic Information
                                                                </h4>
                                                            </div>
                                                            <div class="panel-body" style="padding: 20px;">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <div style="border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 15px;">
                                                                            <h6 style="color: #667eea; margin-top: 0; margin-bottom: 10px;"><i class="fa fa-id-card"></i> Supplier Details</h6>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Supplier Code</label>
                                                                            <input type="text" class="form-control" name="supplier_code" value="<?php echo h($generatedCode); ?>" readonly>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Supplier Name <span style="color: red;">*</span></label>
                                                                            <input type="text" class="form-control" name="supplier_name" placeholder="Supplier Name" required>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Legal Name</label>
                                                                            <input type="text" class="form-control" name="legal_name" placeholder="Legal Name">
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Trading Name</label>
                                                                            <input type="text" class="form-control" name="trading_name" placeholder="Trading Name">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div style="border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 15px;">
                                                                            <h6 style="color: #667eea; margin-top: 0; margin-bottom: 10px;"><i class="fa fa-phone"></i> Contact Information</h6>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Email</label>
                                                                            <input type="email" class="form-control" name="supplier_email" placeholder="Email">
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Additional Email Accounts</label>
                                                                            <div id="supplier-additional-emails">
                                                                                <div class="input-group additional-email-row" style="margin-bottom: 8px;">
                                                                                    <input type="email" class="form-control" name="supplier_additional_emails[]" placeholder="Additional email address">
                                                                                    <span class="input-group-btn">
                                                                                        <button type="button" class="btn btn-danger remove-additional-email"><i class="fa fa-trash"></i></button>
                                                                                    </span>
                                                                                </div>
                                                                            </div>
                                                                            <button type="button" class="btn btn-xs btn-primary" id="add-supplier-additional-email" style="margin-top: 5px;">
                                                                                <i class="fa fa-plus"></i> Add Email
                                                                            </button>
                                                                            <span class="help-block">Add extra email addresses for supplier notifications.</span>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Phone</label>
                                                                            <input type="text" class="form-control" name="supplier_phone" placeholder="Phone">
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Mobile</label>
                                                                            <input type="text" class="form-control" name="supplier_mobile" placeholder="Mobile">
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Contact Name <span style="color: red;">*</span></label>
                                                                            <input type="text" class="form-control" name="contact_name" placeholder="Contact Name" required>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Contact Email <span style="color: red;">*</span></label>
                                                                            <input type="email" class="form-control" name="contact_email" placeholder="Contact Email" required>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Contact Telephone <span style="color: red;">*</span></label>
                                                                            <input type="text" class="form-control" name="contact_telephone" placeholder="Contact Telephone" required>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="panel panel-success" style="margin-bottom: 20px;">
                                                            <div class="panel-heading" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 12px 15px;">
                                                                <h4 class="panel-title" style="margin: 0; font-size: 16px; font-weight: 600;">
                                                                    <i class="fa fa-map-marker"></i> Address Information
                                                                </h4>
                                                            </div>
                                                            <div class="panel-body" style="padding: 20px;">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Address Line 1 <span style="color: red;">*</span></label>
                                                                            <input type="text" class="form-control" name="address_line_1" placeholder="Address Line 1" required>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Address Line 2</label>
                                                                            <input type="text" class="form-control" name="address_line_2" placeholder="Address Line 2">
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">City</label>
                                                                            <input type="text" class="form-control" name="city" placeholder="City">
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Postal Code</label>
                                                                            <input type="text" class="form-control" name="postal_code" placeholder="Postal Code">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Custom URL Link</label>
                                                                            <input type="url" class="form-control" name="custom_url_link" placeholder="Custom URL Link">
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Google Map Link</label>
                                                                            <input type="url" class="form-control" name="google_map_link" placeholder="Google Map Link">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="panel panel-warning" style="margin-bottom: 20px;">
                                                            <div class="panel-heading" style="background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%); color: white; padding: 12px 15px;">
                                                                <h4 class="panel-title" style="margin: 0; font-size: 16px; font-weight: 600;">
                                                                    <i class="fa fa-credit-card"></i> Financial & Compliance
                                                                </h4>
                                                            </div>
                                                            <div class="panel-body" style="padding: 20px;">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <div style="border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 15px;">
                                                                            <h6 style="color: #fd7e14; margin-top: 0; margin-bottom: 10px;"><i class="fa fa-money"></i> Financial Settings</h6>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Credit Limit</label>
                                                                            <input type="text" class="form-control" name="credit_limit" placeholder="Credit Limit">
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Min Order Amount</label>
                                                                            <input type="text" class="form-control" name="min_order_amount" placeholder="Min Order Amount">
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Payment Terms</label>
                                                                            <select class="form-control select2" name="payment_terms_id">
                                                                                <option value="">Select Payment Terms</option>
                                                                                <?php foreach ($paymentTerms as $term): ?>
                                                                                    <option value="<?php echo h($term['payment_terms_id']); ?>"><?php echo h($term['payment_terms_name']); ?></option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Supplier Price Type</label>
                                                                            <select class="form-control select2" name="supplier_price_type_id">
                                                                                <option value="">Select Price Type</option>
                                                                                <?php foreach ($priceTypes as $type): ?>
                                                                                    <option value="<?php echo h($type['id']); ?>"><?php echo h($type['description']); ?></option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div style="border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 15px;">
                                                                            <h6 style="color: #fd7e14; margin-top: 0; margin-bottom: 10px;"><i class="fa fa-gavel"></i> Compliance</h6>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">ABN No <span style="color: red;">*</span></label>
                                                                            <input type="text" class="form-control" name="abn_no" placeholder="ABN No" required>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">ACN No <span style="color: red;">*</span></label>
                                                                            <input type="text" class="form-control" name="acn_no" placeholder="ACN No" required>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="checkbox-inline">
                                                                                <input type="checkbox" name="vat_registered" value="1"> VAT Registered
                                                                            </label>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">GST No</label>
                                                                            <input type="text" class="form-control" name="gst_no" placeholder="GST No">
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Certification PDF</label>
                                                                            <input type="file" class="form-control" name="certification_pdf" accept=".pdf,application/pdf">
                                                                            <span class="help-block">Upload PDF only. Maximum size: 5MB.</span>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="checkbox-inline">
                                                                                <input type="checkbox" name="account_hold" value="1"> Account Hold
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="panel panel-danger" style="margin-bottom: 20px;">
                                                            <div class="panel-heading" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 12px 15px;">
                                                                <h4 class="panel-title" style="margin: 0; font-size: 16px; font-weight: 600;">
                                                                    <i class="fa fa-exclamation-triangle"></i> Emergency & Notes
                                                                </h4>
                                                            </div>
                                                            <div class="panel-body" style="padding: 20px;">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <div style="border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 15px;">
                                                                            <h6 style="color: #c82333; margin-top: 0; margin-bottom: 10px;"><i class="fa fa-phone"></i> Emergency Contact</h6>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Emergency Contact Name</label>
                                                                            <input type="text" class="form-control" name="emergency_contact_name" placeholder="Emergency Contact Name">
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Emergency Contact Email</label>
                                                                            <input type="email" class="form-control" name="emergency_contact_email" placeholder="Emergency Contact Email">
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Emergency Contact Telephone</label>
                                                                            <input type="text" class="form-control" name="emergency_contact_telephone" placeholder="Emergency Contact Telephone">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div style="border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 15px;">
                                                                            <h6 style="color: #c82333; margin-top: 0; margin-bottom: 10px;"><i class="fa fa-sticky-note"></i> Notes & Remarks</h6>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Supplier Note</label>
                                                                            <textarea class="form-control" name="supplier_note" rows="3" placeholder="Supplier Note"></textarea>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Supplier Remarks</label>
                                                                            <textarea class="form-control" name="supplier_remarks" rows="3" placeholder="Supplier Remarks"></textarea>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="checkbox-inline">
                                                                                <input type="checkbox" name="is_active" value="1" checked> Is Active
                                                                            </label>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="checkbox-inline">
                                                                                <input type="checkbox" name="locked" value="1"> Locked
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="panel panel-info" style="margin-bottom: 20px;">
                                                            <div class="panel-heading" style="background: linear-gradient(135deg, #17a2b8 0%, #20c997 100%); color: white; padding: 12px 15px;">
                                                                <h4 class="panel-title" style="margin: 0; font-size: 16px; font-weight: 600;">
                                                                    <i class="fa fa-truck"></i> Shipping Addresses
                                                                </h4>
                                                            </div>
                                                            <div class="panel-body" style="padding: 20px;">
                                                                <p class="text-muted" style="margin-top: -10px;">Capture one or more delivery addresses. Mark one as the default.</p>
                                                            <div id="shippingAddresses">
                                                                <?php foreach ($shippingData as $index => $address): ?>
                                                                <div class="shipping-address-item" data-index="<?php echo $index ?>">
                                                                    <button type="button" class="btn btn-xs red remove-shipping-address" <?php echo $index === 0 ? 'style="display:none;"' : '' ?>><i class="fa fa-trash"></i></button>
                                                                    <div class="shipping-address-controls">
                                                                        <div class="form-inline">
                                                                            <label class="default-indicator">
                                                                                <input type="radio" name="shipping_default" value="<?php echo $index ?>" <?php echo $address['is_default'] ? 'checked' : '' ?>> Default
                                                                            </label>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom:0;">
                                                                            <input type="text" class="form-control" name="shipping_label[<?= $index ?>]" placeholder="Label (e.g. Warehouse)" value="<?= h($address['label']) ?>">
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label>Address Line 1</label>
                                                                                <input type="text" class="form-control" name="shipping_address_line_1[<?= $index ?>]" value="<?= h($address['address_line_1']) ?>">
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label>Address Line 2</label>
                                                                                <input type="text" class="form-control" name="shipping_address_line_2[<?= $index ?>]" value="<?= h($address['address_line_2']) ?>">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-md-4">
                                                                            <div class="form-group">
                                                                                <label>City / Town</label>
                                                                                <input type="text" class="form-control" name="shipping_city[<?= $index ?>]" value="<?= h($address['city']) ?>">
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <div class="form-group">
                                                                                <label>Postal Code</label>
                                                                                <input type="text" class="form-control" name="shipping_postal_code[<?= $index ?>]" value="<?= h($address['postal_code']) ?>">
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <div class="form-group">
                                                                                <label>Contact Number</label>
                                                                                <input type="text" class="form-control" name="shipping_contact_no[<?= $index ?>]" value="<?= h($address['contact_no']) ?>">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row" style="display: none;">
                                                                        <div class="col-md-4">
                                                                            <div class="form-group">
                                                                                <label>Attribute 1</label>
                                                                                <input type="text" class="form-control" name="shipping_attribute_1[<?= $index ?>]" value="<?= h($address['attribute_1']) ?>">
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <div class="form-group">
                                                                                <label>Attribute 2</label>
                                                                                <input type="text" class="form-control" name="shipping_attribute_2[<?= $index ?>]" value="<?= h($address['attribute_2']) ?>">
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <div class="form-group">
                                                                                <label>Attribute 3</label>
                                                                                <input type="text" class="form-control" name="shipping_attribute_3[<?= $index ?>]" value="<?= h($address['attribute_3']) ?>">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-md-3">
                                                                            <div class="form-group">
                                                                                <label>Contact Person Name</label>
                                                                                <input type="text" class="form-control" name="shipping_contact_person_name[<?= $index ?>]" value="<?= h($address['contact_person_name']) ?>">
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-3">
                                                                            <div class="form-group">
                                                                                <label>Contact Person Phone</label>
                                                                                <input type="text" class="form-control" name="shipping_contact_person_phone[<?= $index ?>]" value="<?= h($address['contact_person_phone']) ?>">
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-3">
                                                                            <div class="form-group">
                                                                                <label>Contact Person Email</label>
                                                                                <input type="email" class="form-control" name="shipping_contact_person_email[<?= $index ?>]" value="<?= h($address['contact_person_email']) ?>">
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-3">
                                                                            <div class="form-group">
                                                                                <label>Remarks</label>
                                                                                <textarea class="form-control" name="shipping_remarks[<?= $index ?>]" rows="1"><?= h($address['remarks']) ?></textarea>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-md-12">
                                                                            <div class="form-group">
                                                                                <label>Note to Deliver</label>
                                                                                <textarea class="form-control" name="shipping_note_to_deliver[<?= $index ?>]" rows="2" placeholder="Special instructions for delivery"><?= h($address['note_to_deliver']) ?></textarea>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-md-3">
                                                                            <div class="form-group">
                                                                                <label>Delivery Start Time</label>
                                                                                <input type="time" class="form-control" name="shipping_delivery_start_time[<?= $index ?>]" value="<?= h($address['delivery_start_time']) ?>">
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-3">
                                                                            <div class="form-group">
                                                                                <label>Delivery End Time</label>
                                                                                <input type="time" class="form-control" name="shipping_delivery_end_time[<?= $index ?>]" value="<?= h($address['delivery_end_time']) ?>">
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-3">
                                                                            <div class="form-group">
                                                                                <label>Delivery Route</label>
                                                                                <select class="form-control" name="shipping_delivery_route_id[<?= $index ?>]">
                                                                                    <option value="">Select Route</option>
                                                                                    <?php
                                                                                    $deliveryRoutes = getDeliveryRoutes();
                                                                                    foreach ($deliveryRoutes as $route) {
                                                                                        $selected = ($address['delivery_route_id'] == $route['id']) ? 'selected' : '';
                                                                                        echo '<option value="' . h($route['id']) . '" ' . $selected . '>' . h($route['route_name']) . '</option>';
                                                                                    }
                                                                                    ?>
                                                                                </select>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                            <button type="button" class="btn btn-default" id="addShippingAddress"><i class="fa fa-plus"></i> Add Shipping Address</button>
                                                            </div>
                                                        </div>

                                                        <div class="panel panel-default" style="margin-bottom: 20px;">
                                                            <div class="panel-heading" style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%); color: white; padding: 12px 15px;">
                                                                <h4 class="panel-title" style="margin: 0; font-size: 16px; font-weight: 600;">
                                                                    <i class="fa fa-credit-card"></i> Payment Options
                                                                </h4>
                                                            </div>
                                                            <div class="panel-body" style="padding: 20px;">
                                                                <p class="text-muted" style="margin-top: -10px;">Add payment methods for this supplier.</p>
                                                                <div id="paymentOptions">
                                                                    <!-- Payment options will be added here -->
                                                                </div>
                                                                <button type="button" class="btn btn-default" id="addCardPayment"><i class="fa fa-plus"></i> Add Card Payment</button>
                                                                <button type="button" class="btn btn-default" id="addBankPayment"><i class="fa fa-plus"></i> Add Bank Payment</button>
                                                            </div>
                                                        </div>

                                                        <div class="form-actions">
                                                            <button type="submit" class="btn green" name="sub"><i class="fa fa-check"></i> Add Supplier</button>
                                                        </div>
                                                    </div>
                                                </form>
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
         <!-- Notification function -->
        <script src="assets/global/plugins/notification/jquery.bootstrap-growl.js"></script>
   

        <script type="text/template" id="shippingAddressTemplate">
            <div class="shipping-address-item" data-index="__INDEX__">
                <button type="button" class="btn btn-xs red remove-shipping-address"><i class="fa fa-trash"></i></button>
                <div class="shipping-address-controls">
                    <div class="form-inline">
                        <label class="default-indicator">
                            <input type="radio" name="shipping_default" value="__INDEX__"> Default
                        </label>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <input type="text" class="form-control" name="shipping_label[__INDEX__]" placeholder="Label (e.g. Warehouse)">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Address Line 1</label>
                            <input type="text" class="form-control" name="shipping_address_line_1[__INDEX__]">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Address Line 2</label>
                            <input type="text" class="form-control" name="shipping_address_line_2[__INDEX__]">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>City / Town</label>
                            <input type="text" class="form-control" name="shipping_city[__INDEX__]">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Postal Code</label>
                            <input type="text" class="form-control" name="shipping_postal_code[__INDEX__]">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Contact Number</label>
                            <input type="text" class="form-control" name="shipping_contact_no[__INDEX__]">
                        </div>
                    </div>
                </div>
                <div class="row" style="display: none;">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Attribute 1</label>
                            <input type="text" class="form-control" name="shipping_attribute_1[__INDEX__]">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Attribute 2</label>
                            <input type="text" class="form-control" name="shipping_attribute_2[__INDEX__]">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Attribute 3</label>
                            <input type="text" class="form-control" name="shipping_attribute_3[__INDEX__]">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Contact Person Name</label>
                            <input type="text" class="form-control" name="shipping_contact_person_name[__INDEX__]">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Contact Person Phone</label>
                            <input type="text" class="form-control" name="shipping_contact_person_phone[__INDEX__]">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Contact Person Email</label>
                            <input type="email" class="form-control" name="shipping_contact_person_email[__INDEX__]">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Remarks</label>
                            <textarea class="form-control" name="shipping_remarks[__INDEX__]" rows="1"></textarea>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Note to Deliver</label>
                            <textarea class="form-control" name="shipping_note_to_deliver[__INDEX__]" rows="2" placeholder="Special instructions for delivery"></textarea>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Delivery Start Time</label>
                            <input type="time" class="form-control" name="shipping_delivery_start_time[__INDEX__]">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Delivery End Time</label>
                            <input type="time" class="form-control" name="shipping_delivery_end_time[__INDEX__]">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Delivery Route</label>
                            <select class="form-control" name="shipping_delivery_route_id[__INDEX__]">
                                <option value="">Select Route</option>
                                <?php
                                $deliveryRoutes = getDeliveryRoutes();
                                foreach ($deliveryRoutes as $route) {
                                    echo '<option value="' . h($route['id']) . '">' . h($route['route_name']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </script>
        <script>
            (function ($) {
                var shippingIndex = <?php echo count($shippingData) ?>;

                $('#addShippingAddress').on('click', function () {
                    var template = $('#shippingAddressTemplate').html();
                    var newMarkup = template.replace(/__INDEX__/g, shippingIndex);
                    $('#shippingAddresses').append(newMarkup);
                    shippingIndex += 1;
                });

                $('#shippingAddresses').on('click', '.remove-shipping-address', function () {
                    var items = $('#shippingAddresses .shipping-address-item');
                    if (items.length === 1) {
                        return;
                    }
                    $(this).closest('.shipping-address-item').remove();
                    if ($('#shippingAddresses input[name="shipping_default"]:checked').length === 0) {
                        $('#shippingAddresses input[name="shipping_default"]').first().prop('checked', true);
                    }
                });

                $('#add-supplier-additional-email').on('click', function () {
                    var html = '<div class="input-group additional-email-row" style="margin-bottom: 8px;">' +
                        '<input type="email" class="form-control" name="supplier_additional_emails[]" placeholder="Additional email address">' +
                        '<span class="input-group-btn">' +
                            '<button type="button" class="btn btn-danger remove-additional-email"><i class="fa fa-trash"></i></button>' +
                        '</span>' +
                    '</div>';
                    $('#supplier-additional-emails').append(html);
                });

                $('#supplier-additional-emails').on('click', '.remove-additional-email', function () {
                    $(this).closest('.additional-email-row').remove();
                });
            })(jQuery);
        </script>
        <script type="text/template" id="cardPaymentTemplate">
            <div class="payment-option-item" data-type="card" style="border: 1px solid #e7edf5; border-radius: 8px; padding: 16px; margin-bottom: 16px; background: #f9fbff;">
                <button type="button" class="btn btn-xs red remove-payment-option" style="position: absolute; top: 12px; right: 12px;"><i class="fa fa-trash"></i></button>
                <h5>Card Payment</h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Card Number</label>
                            <input type="text" class="form-control" name="card_no[]" placeholder="Card Number">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Name on Card</label>
                            <input type="text" class="form-control" name="card_name[]" placeholder="Name on Card">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Exp Month</label>
                            <select class="form-control" name="exp_month[]">
                                <option value="">Month</option>
                                <?php for($m=1; $m<=12; $m++): ?>
                                    <option value="<?php echo $m; ?>"><?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Exp Year</label>
                            <select class="form-control" name="exp_year[]">
                                <option value="">Year</option>
                                <?php for($y=date('Y'); $y<=date('Y')+10; $y++): ?>
                                    <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </script>
        <script type="text/template" id="bankPaymentTemplate">
            <div class="payment-option-item" data-type="bank" style="border: 1px solid #e7edf5; border-radius: 8px; padding: 16px; margin-bottom: 16px; background: #f9fbff;">
                <button type="button" class="btn btn-xs red remove-payment-option" style="position: absolute; top: 12px; right: 12px;"><i class="fa fa-trash"></i></button>
                <h5>Bank Payment</h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Bank Name</label>
                            <input type="text" class="form-control" name="bank_name[]" placeholder="Bank Name">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Branch</label>
                            <input type="text" class="form-control" name="branch[]" placeholder="Branch">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Account Number</label>
                            <input type="text" class="form-control" name="account_no[]" placeholder="Account Number">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Account Holder</label>
                            <input type="text" class="form-control" name="account_holder[]" placeholder="Account Holder Name">
                        </div>
                    </div>
                </div>
            </div>
        </script>
        <script>
            $(document).ready(function() {
                try { $('.select2').select2(); } catch (e) { console.warn('select2 init failed', e); }

                $('#addCardPayment').on('click', function() {
                    var template = $('#cardPaymentTemplate').html();
                    $('#paymentOptions').append(template);
                });

                $('#addBankPayment').on('click', function() {
                    var template = $('#bankPaymentTemplate').html();
                    $('#paymentOptions').append(template);
                });

                $('#paymentOptions').on('click', '.remove-payment-option', function() {
                    $(this).closest('.payment-option-item').remove();
                });

                // Form submission
                $(document).on('submit', '#frnAddsupplier', function(e) {
                    e.preventDefault();
                    var formData = new FormData(this);
                    
                    $.ajax({
                        type: 'POST',
                        url: 'process/add-supplier-process.php',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(data) {
                            var growlType = data.indexOf('successfully') !== -1 ? 'success' : 'danger';
                            $.bootstrapGrowl(data, { 
                                type: growlType,
                                align: 'right'
                            });
                            if (data.indexOf('successfully') !== -1) {
                                clearInput();
                            }
                        },
                        error: function() {
                            $.bootstrapGrowl('Error occurred while saving supplier.', { 
                                type: 'danger',
                                align: 'right'
                            });
                        }
                    });
                    return false;
                });
            });

            function clearInput() {
                $("#frnAddsupplier :input").each(function() {
                    if ($(this).attr('name') !== 'supplier_code') {
                        $(this).val('');
                    }
                });
                // Clear shipping addresses except first one
                $('#shippingAddresses .shipping-address-item').not(':first').remove();
                $('#shippingAddresses .shipping-address-item:first input').val('');
                $('#shippingAddresses .shipping-address-item:first textarea').val('');
                $('#shippingAddresses .shipping-address-item:first select').val('');
                // Clear payment options
                $('#paymentOptions').empty();
                // Reset checkboxes
                $('#frnAddsupplier input[type="checkbox"]').prop('checked', false);
                $('#is_active').prop('checked', true);
            }
        </script>
</body>

</html>



