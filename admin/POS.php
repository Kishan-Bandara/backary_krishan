<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');
$nowDate = date("d/m/Y");

$db = new Database();

// Get active currency
$getcurrency = $db->getRow('SELECT * FROM currency WHERE activated = ? LIMIT 1', ["Y"]);
$currency_symbol = $getcurrency['currency'] ?? '$';

if (!empty($_SESSION["delivery_address"])) {
    $session_delivery_address = $_SESSION["delivery_address"];
} else {
    $session_delivery_address = "";
}
if (!empty($_SESSION["deliveryRate"])) {
    $delivery_rate = $_SESSION["deliveryRate"];
} else {
    $delivery_rate = "";
}

if (empty($_SESSION["customerid"])) {
    $_SESSION["customerid"] = 1;
}

//Database eken Table ekata Values daaganna Function eka 
function getProducts()
{
    $db = new Database();
    $query = $db->getRows('SELECT * FROM item_master ORDER BY item_name ASC');
    return $query;
}

function getCategories()
{
    $db = new Database();
    $query = $db->getRows('SELECT * FROM category_master ORDER BY category_name ASC');
    return $query;
}

function load_customer()
{
    $output = '';
    $db = new Database();
    $query = $db->getRows('SELECT * FROM customer');
    $data = $query;
    foreach ($data as $query) {
        $id = $query['customer_id'];
        $output .= '<option value="' . $query['customer_id'] . '">' . $query['customer_name'] . '</option>';
    }
    return $output;
}

function load_city()
{
    if (!empty($_SESSION["cityId"])) {
        $city_id = $_SESSION["cityId"];
    } else {
        $city_id = 29;
    }

    $city_output = '<option value="">--- Please Select ---</option>';
    $db = new Database();
    $cityquery = $db->getRows('SELECT * FROM city_master');
    $citydata = $cityquery;

    $city_output = '<option value="">--- Please Select ---</option>';
    foreach ($citydata as $cityquery) {
        $sel = "";
        $cityid = $cityquery['id'];
        if ($cityid == $city_id) {
            $sel = "selected";
        }
        $city_output .= '<option ' . $sel . ' value="' . $cityquery['id'] . '">' . $cityquery['city'] . '</option>';
    }
    return $city_output;
}

function getPaymentType()
{
    if (!empty($_SESSION["paymentId"])) {
        $payment_type_id = $_SESSION["paymentId"];
    } else {
        $payment_type_id = "";
    }

    $db = new Database();
    $payment_type_query = $db->getRows('SELECT * FROM payment_method WHERE website_status = ?', ['Y']);
    $payment_type_data = $payment_type_query;
    $mode_output = "";
    foreach ($payment_type_data as $payment_type_query) {
        $sel = "";
        $payment_typeID = $payment_type_query['id'];
        if ($payment_typeID == $payment_type_id) {
            $sel = "selected";
        }
        $mode_output .= '<option ' . $sel . ' value="' . $payment_type_query['id'] . '">' . $payment_type_query['type'] . '</option>';
    }
    return $mode_output;
}

function getdeliverymode()
{
    if (!empty($_SESSION["deliveryModeId"])) {
        $mode_id = $_SESSION["deliveryModeId"];
    } else {
        $mode_id = "";
    }

    $output = '<option value="">--- Please Select ---</option>';
    $db = new Database();
    $query = $db->getRows('SELECT * FROM delivery_master');
    $data = $query;
    foreach ($data as $query) {
        $sel = "";
        $mode_ID = $query['id'];
        if ($mode_ID == $mode_id) {
            $sel = "selected";
        }
        $output .= '<option ' . $sel . ' value="' . $query['id'] . '">' . $query['method'] . '</option>';
    }
    return $output;
}

function getCustomers()
{
    if (!empty($_SESSION["customerid"])) {
        $customer_id = $_SESSION["customerid"];
    }

    $output = '<option value="">--- Please Select ---</option>';
    $db = new Database();
    $customer_query = $db->getRows('SELECT * FROM customer ORDER BY customer_id ASC');
    $customer_query_data = $customer_query;

    foreach ($customer_query_data as $customer_query) {
        $sel = "";
        $customerID = $customer_query['customer_id'];
        if ($customerID == $customer_id) {
            $sel = "selected";
        }
        $output .= '<option ' . $sel . ' value="' . $customer_query['customer_id'] . '">' . $customer_query['customer_name'] . ' (' . $customerID . ')</option>';
    }
    return $output;
}

function load_location()
{
    $location_output = '';
    $db = new Database();
    if (isSuperAdmin()) {
        $query1 = $db->getRows('SELECT * FROM location_master ORDER BY name ASC');
    } else {
        $query1 = $db->getRows('SELECT * FROM location_master WHERE id = ?', [$_SESSION['location']]);
    }
    $data1 = $query1;
    foreach ($data1 as $query1) {
        $selected = ((int) $query1['id'] === (int) $_SESSION['location']) ? ' selected' : '';
        $location_output .= '<option value="' . $query1['id'] . '"' . $selected . '>' . $query1['name'] . '</option>';
    }
    return $location_output;
}


function load_hold_orders()
{
    $output = '';
    $db = new Database();
    $query1 = $db->getRows('SELECT * FROM temp_cart WHERE cart_h_status = 1 AND cart_h_location = ? ORDER BY cart_h_pk_id ASC LIMIT 5', [$_SESSION['location']]);
    $data1 = $query1;
    $i = 0;
    foreach ($data1 as $query1) {
        $i++;
        $output .= ' <input type="button" id="hold_btn_id" class="btn btn-icon-only blue hold_btn_id" value="' . $i . '" data-invoice-code = "' . $query1['temp_cart_code'] . '" >';
    }
    return $output;
}

function getReferance()
{
    //parana id eka search karala aluth id ekak hadagannawa.
    $db = new Database();
    $getpid = $db->getRow('SELECT max(invoice_h_id) as invoice_h_id FROM invoice_hedder');
    $randomNo = rand(1000000, 9999999);

    $oldpid = $getpid['invoice_h_id']; // This might throw a warning if null, but logic follows existing code
    $newpid =  $oldpid + 1;

    // product code ekak hadagannawa
    echo $refaranceCode = "SAL" . $randomNo . $newpid;
}

$vat_value = 0;
?>
<!DOCTYPE html>
<!--[if IE 8]> <html lang="en" class="ie8 no-js"> <![endif]-->
<!--[if IE 9]> <html lang="en" class="ie9 no-js"> <![endif]-->
<!--[if !IE]><!-->
<html lang="en">
<!--<![endif]-->

<head>
    <meta charset="utf-8" />
    <title>POS | Touch Interface</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <meta content="" name="description" />
    <meta content="" name="author" />
    <?php include('common/head.php'); ?>
    <!-- BEGIN PAGE LEVEL PLUGINS -->
    <link href="assets/global/plugins/datatables/datatables.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.css" rel="stylesheet" type="text/css" />
    <link href="assets/global/plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/global/plugins/select2/css/select2-bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
    <link href="assets/global/plugins/celander/bootstrap-datetimepicker.min.css" rel="stylesheet">
    <link href="assets/global/plugins/celander/datepicker.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/global/plugins/touch_keypad/jquery.numpad.css">

    <style type="text/css">
        body {
            overflow-y: hidden;
            /* Hide main scroll bar for fullscreen feel */
            padding-top: 70px;
            /* Account for fixed header */
        }

        .pos-container {
            display: flex;
            height: calc(100vh - 70px);
            /* Subtract header height */
            width: 100%;
            overflow: hidden;
            background-color: #f1f4f7;
        }

        /* Left Column: Products */
        .pos-products-area {
            flex: 0 0 65%;
            max-width: 65%;
            display: flex;
            flex-direction: column;
            border-right: 1px solid #dce1e6;
            padding: 10px;
        }

        .pos-search-bar {
            margin-bottom: 10px;
            display: flex;
            gap: 10px;
        }

        .categories-scroller {
            overflow-x: auto;
            white-space: nowrap;
            padding-bottom: 10px;
            margin-bottom: 10px;
            -webkit-overflow-scrolling: touch;
        }

        .categories-scroller::-webkit-scrollbar {
            height: 6px;
        }

        .categories-scroller::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 3px;
        }

        .btn-category {
            border-radius: 20px !important;
            padding: 8px 15px;
            margin-right: 5px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            border: none;
            background: #fff;
            color: #555;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.2s;
        }

        .btn-category.active,
        .btn-category:hover,
        .btn-category:active,
        .btn-category:focus {
            background: #3598dc;
            color: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .products-grid {
            flex: 1;
            overflow-y: auto;
            padding: 5px;
            background-color: #e9edef;
            border-radius: 6px;
            border: 1px solid #dce1e6;
            display: flex;
            flex-wrap: wrap;
            align-content: flex-start;
        }

        .product-card-btn {
            width: calc(20% - 10px);
            /* 5 items per row */
            margin: 5px;
            height: 100px;
            background: #fff !important;
            border: 1px solid #e1e1e1 !important;
            border-radius: 8px !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            display: flex !important;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 5px !important;
            white-space: normal !important;
            text-align: center;
            transition: transform 0.1s;
            overflow: hidden;
        }

        .product-card-btn:active {
            transform: scale(0.96);
        }

        .product-card-btn.disabled {
            opacity: 0.6;
            background: #f9f9f9 !important;
        }

        .prod-name {
            font-size: 12px;
            font-weight: 600;
            color: #333;
            line-height: 1.3;
            max-height: 40px;
            overflow: hidden;
            margin-bottom: 4px;
        }

        .prod-price {
            font-size: 13px;
            color: #e7505a;
            font-weight: bold;
        }

        .prod-qty {
            font-size: 10px;
            color: #888;
        }


        /* Right Column: Cart */
        .pos-cart-area {
            flex: 0 0 35%;
            max-width: 35%;
            display: flex;
            flex-direction: column;
            background: #fff;
            border-left: 1px solid #ddd;
        }

        .cart-header {
            padding: 10px;
            background: #f9fafb;
            border-bottom: 1px solid #eee;
        }

        .cart-items-wrapper {
            flex: 1;
            overflow-y: auto;
            padding: 0;
        }

        .cart-table th {
            background: #f0f4f8;
            font-size: 12px;
            padding: 8px !important;
        }

        .cart-table td {
            vertical-align: middle !important;
            padding: 6px !important;
            font-size: 13px;
        }

        .cart-footer {
            padding: 10px;
            background: #f0f4f8;
            border-top: 1px solid #ddd;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .grand-total {
            font-size: 24px;
            font-weight: bold;
            color: #26a69a;
            text-align: right;
            margin-bottom: 10px;
        }

        .input-group-lg .form-control {
            font-weight: bold;
            font-size: 18px;
        }

        .nmpd-grid {
            border: none;
            padding: 20px;
        }

        .nmpd-grid>tbody>tr>td {
            border: none;
        }

        .qtyInput {
            display: block;
            width: 100%;
            padding: 6px 12px;
        }

        @media (max-width: 1200px) {
            .product-card-btn {
                width: calc(25% - 10px);
            }
        }

        @media (max-width: 991px) {
            .product-card-btn {
                width: calc(33.33% - 10px);
            }
        }
    </style>
</head>

<body class="page-sidebar-closed-hide-logo page-content-white page-sidebar-closed">
    <?php include('common/manubar.php'); ?>
    <div class="page-content-wrapper"  style="    margin-top: 46px;">
    <form method="POST" enctype="multipart/form-data" id="frn-add" action="process/add-sales-process.php" style="height: 100%;">
        <div class="pos-container">
            <!-- PRODUCTS AREA (LEFT) -->
            <div class="pos-products-area">
                <!-- Search & Barcode -->
                <div class="pos-search-bar">
                    <div class="input-group" style="width: 100%;">
                        <span class="input-group-addon"><i class="fa fa-barcode"></i></span>
                        <input type="text" name="Getbarcode" id="department_name" class="form-control barcode input-lg" placeholder="Scan barcode or type product name...">
                        <input type="hidden" name="itmQty" value="1">
                        <span class="input-group-btn">
                            <button class="btn btn-info input-lg input-group-btn" type="button" id="clear_cart"><i class="fa fa-trash"></i> Clear</button>
                        </span>
                    </div>
                </div>

                <!-- Categories -->
                <div class="categories-scroller text-center">
                    <button type="button" class="btn-category btn_category active" data-category-id="0">All</button>
                    <?php
                    $data = getCategories();
                    foreach ($data as $query) {
                        $category_id = $query['category_id'];
                        $category_name = $query['category_name'];
                    ?>
                        <button type="button" class="btn-category btn_category" data-category-id="<?php echo $category_id; ?>">
                            <?php echo $category_name; ?>
                        </button>
                    <?php } ?>
                </div>

                <!-- Product Grid -->
                <div class="products-grid" id="all_touch_item_display">
                    <?php $data = getProducts();
                    foreach ($data as $query) {
                        $item_id = $query['item_id'];
                        $query_item_id = $db->getRow('SELECT * FROM item_master WHERE item_id = ? ', [$item_id]);
                        $query_get_qty = $db->getRow('SELECT SUM(ft_blanace) as qty , ft_rate FROM fifo WHERE ft_item = ? AND ft_location = ? ', [$item_id, $_SESSION['location']]);
                        $master_item_name = $query_item_id['item_name'];
                        $master_item_code = $query_item_id['item_code'];
                        $master_item_vat = $query_item_id['item_vat'];
                        $master_item_qty = $query_get_qty['qty'];
                        $master_item_price = $query_item_id['item_normal_selling_price'];
                        $master_item_purchase_price = $query_get_qty['ft_rate']; // Added for purchase price

                        if ($master_item_vat == "Y") {
                            $vat_has = $vat_value . "%";
                        } else {
                            $vat_has = "0.00%";
                        }

                        if ($master_item_qty <= 0) {
                            $button_display_class = "disabled";
                        } else {
                            $button_display_class = "";
                        }
                    ?>
                        <button type="button" class="product-card-btn btnAddItemFromList <?php echo $button_display_class; ?>"
                            data-item-code="<?php echo $master_item_code; ?>"
                            data-item-id="<?php echo $item_id; ?>"
                            data-item-name="<?php echo $master_item_name; ?>"
                            data-item-vat-name="<?php echo $vat_has; ?>"
                            data-item-vat="<?php echo $master_item_vat; ?>"
                            data-item-price="<?php echo $master_item_price; ?>"
                            data-item-purchase-price="<?php echo $master_item_purchase_price; ?>">
                            <span class="prod-name"><?php echo $master_item_name; ?></span>
                            <span class="prod-price"><?php echo $currency_symbol; ?> <?php echo number_format($master_item_price, 2); ?></span>
                            <span class="prod-qty">Qty: <?php echo $master_item_qty; ?></span>
                        </button>
                    <?php } ?>
                </div>
            </div>

            <!-- CART AREA (RIGHT) -->
            <div class="pos-cart-area">
                <div class="cart-header">
                    <div class="row">
                        <div class="col-md-7">
                            <label>Customer</label>
                            <select class="form-control select2me customer_mode" id="customer" name="customer" style="width: 100%;">
                                <?php echo getCustomers(); ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label>Ref/Order #</label>
                            <input type="text" class="form-control input-sm" name="ref_Number" id="ref_Number" value="<?php echo getReferance(); ?>" required readonly>
                            <input type="hidden" name="order_date" id="order_date" value="<?php echo date('Y-m-d\TH:i'); ?>">
                        </div>
                    </div>
                </div>

                <div class="cart-items-wrapper">
                    <table class="table table-striped table-hover cart-table" id="myDatatable">
                        <thead>
                            <tr>
                                <th style="width: 30px;">#</th>
                                <th>Item</th>
                                <th style="width: 60px;">Qty</th>
                                <th style="width: 80px;">Price</th>
                                <th style="width: 80px;">Total</th>
                                <th style="width: 30px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Cart Items will be appended here via JS -->
                        </tbody>
                    </table>
                </div>

                <div class="cart-footer">
                    <div class="total-row">
                        <span>Sub Total:</span>
                        <span><input type="number" id="grossTot" name="txtSubTotal" class="form-control input-sm autoprice" readonly style="width: 100px; display:inline-block; text-align:right;"></span>
                    </div>

                    <div class="row" style="margin-bottom: 5px;">
                        <div class="col-md-6">
                            <select class="form-control input-sm" name="drpDiscounthMethod" id="drpDiscounthMethod" onchange="ShowHideDiv()">
                                <option value="1">No Discounts</option>
                                <option value="2">Discount (%)</option>
                                <option value="3">Discount (-)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <div id="dv_discounts_sum" style="display: none">
                                <input type="text" id="DiscountSUM_value" name="DiscountSUM_value" class="form-control input-sm update_discount" placeholder="Amount">
                            </div>
                            <div id="dv_discounts_pre" style="display: none">
                                <input type="text" id="Discountprecentage_value" name="Discountprecentage_value" class="form-control input-sm update_discount" placeholder="%">
                            </div>
                        </div>
                    </div>

                    <div class="grand-total">
                        <small>Total:</small> <span id='grandTot'>0.00</span>
                        <input type="hidden" id="grandTotTotHidden" name="netvalue">
                        <div style="font-size: 11px; color: #666; font-weight:normal;">Discount: <span id="discount_value_display">0.00</span></div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <label>Pay Amount</label>
                            <input type="text" id="pay_amount" name="pay_amount" class="form-control text-basic input-lg" autocomplete="off" placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label>Balance</label>
                            <input type="text" id="balance_text" readonly class="form-control input-lg" style="color: green; font-weight: bold;" value="0.00">
                        </div>
                    </div>

                    <div class="row" style="margin-top: 10px;">
                        <div class="col-md-6">
                            <select class="form-control payment_mode input-sm" name="drpPaymethMethod" id="drpPaymethMethod">
                                <?php echo getPaymentType(); ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <select class="form-control input-sm" name="drp_print_receipt" id="drp_print_receipt">
                                <option value="1">Print Receipt</option>
                                <option value="2">No Receipt</option>
                            </select>
                        </div>
                    </div>

                    <div class="row" style="margin-top: 10px;">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-success btn-lg btn-block" name="sales"><i class="fa fa-check-circle"></i> COMPLETE SALE</button>
                        </div>
                    </div>

                    <div id="delicery_info" style="display:none; margin-top:5px;"></div>
                </div>
            </div>
        </div>
    </form>
</div>
    <?php include('common/footer.php'); ?>
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
    <script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
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
    <script type="text/javascript" src="assets/global/plugins/celander/moment.js"></script>
    <script type="text/javascript" src="assets/global/plugins/celander/bootstrap-datetimepicker.min.js"></script>
    <script type="text/javascript" src="assets/global/plugins/celander/datepicker.js"></script>
    <script type="text/javascript" src="assets/global/plugins/autoComplete/typeahead.js"></script>
    <script type="text/javascript" src="assets/global/plugins/touch_keypad/jquery.numpad.js"></script>

    <script type="text/javascript">
        // Set NumPad defaults for jQuery mobile. 
        $.fn.numpad.defaults.gridTpl = '<table class="table modal-content"></table>';
        $.fn.numpad.defaults.backgroundTpl = '<div class="modal-backdrop in"></div>';
        $.fn.numpad.defaults.displayTpl = '<input type="text" class="form-control" />';
        $.fn.numpad.defaults.buttonNumberTpl = '<button type="button" class="btn btn-default"></button>';
        $.fn.numpad.defaults.buttonFunctionTpl = '<button type="button" class="btn" style="width: 100%;"></button>';
        $.fn.numpad.defaults.onKeypadCreate = function() {
            $(this).find('.done').addClass('btn-primary');
        };

        $(document).ready(function() {
            $('.text-basic').numpad();
            $('#numpadButton-btn').numpad({
                target: $('#numpadButton')
            });
        });
    </script>
    <script type="text/javascript">
        $(document).ready(function() {
            $('#example1').datepicker({
                format: "dd/mm/yyyy"
            });
        });
    </script>

    <script>
        var counter = 0;

        // CALCULATE totals
        function calculateTot() {
            var grandTotal = 0;
            var coupon_discount_value = 0.00;
            var tot = 0,
                vat_item_tot = 0,
                discount_value = 0.00;
            var deliveryRate = <?php echo json_encode($delivery_rate); ?>;
            var discount = 5.00;
            var total = 0;
            var coupon_rate = 0.00;
            var discount_charge_for_display = 0;
            var grandTotal_with_delivery = 0;

            var grandTot = 0;
            var subTot = 0;
            var itemDiscount = 0;
            var balance_amount = 0;

            $("#myDatatable tbody .custom-tr").each(function() {

                var item_discount_value = 0.00;
                var discount_type = $('#drpDiscounthMethod option:selected').val();
                var discount_value_pct = $('#Discountprecentage_value').val();
                var discount_value_sum = $('#DiscountSUM_value').val();
                var discount_value_POS = 0.00;

                if (discount_value_sum == "") {
                    discount_value_sum = 0.00;
                }
                var pay_amount = $('#pay_amount').val() ? parseFloat($('#pay_amount').val()) : 0.00;

                var QTY = parseFloat($(this).find('.qqt').val() || 0);
                var itmPrice = parseFloat($(this).find('.itmprice').val() || 0);
                var itemDiscount = parseFloat($(this).find('.itemDiscount').val() || 0);
                var fullItemDiscountRate = QTY * itemDiscount;
                var colgrossTot = QTY * itmPrice;
                var itemDiscountPrice = (colgrossTot * fullItemDiscountRate) / 100;
                var colTot = colgrossTot - itemDiscountPrice;

                subTot += colTot;
                $(this).find("td:eq(4)").html(colTot.toFixed(2)); // Total is in index 4 now

                if (discount_type == 2) {
                    discount_type = "PCT";
                    discount_value_POS = (subTot * discount_value_pct) / 100;
                    discount_charge_for_display = discount_value_POS;
                } else if (discount_type == 3) {
                    discount_type = "SUM";
                    discount_value_POS = discount_value_sum;
                    discount_charge_for_display = discount_value_POS;
                } else {
                    discount_type = "EOR";
                    discount_value_POS = 0.00;
                }

                total = (parseFloat(subTot));
                deliveryRate = (deliveryRate) ? deliveryRate : '0.00';

                // Simplified delivery logic based on original code, though "Delivery_rate" element is not in new HTML atm
                grandTotal = (total >= 1000) ? grandTotal = (parseFloat(total) - parseFloat(discount_value_POS)) : grandTotal = (parseFloat(total) - parseFloat(discount_value_POS) + parseFloat(deliveryRate));
                grandTotal_with_delivery = grandTotal;

                balance_amount = parseFloat(pay_amount) - parseFloat(grandTotal);
            });

            // Handle empty cart case
            if (subTot === 0) {
                grandTotal_with_delivery = 0;
                var pay_amount = $('#pay_amount').val() ? parseFloat($('#pay_amount').val()) : 0.00;
                balance_amount = pay_amount;
            }

            $("#balance_text").val(balance_amount.toFixed(2));
            $("#grossTot").val(subTot.toFixed(2));
            $('#grandTot').text(grandTotal_with_delivery.toFixed(2));
            $('#grandTotTotHidden').val(grandTotal_with_delivery.toFixed(2));
            $('#discount_value_display').text(discount_charge_for_display.toFixed(2));
        }

        jQuery('.update_discount').on('input', function() {
            calculateTot();
        });
        $("#drpDiscounthMethod").change(function() {
            calculateTot();
        });
        jQuery('#pay_amount').on('input', function() {
            calculateTot();
        });
        $(document).on('change', "input[id='pay_amount']", function() {
            calculateTot();
        });
        $(document).on('change', "#myDatatable tbody .custom-tr td input[id='unit_price'], #myDatatable tbody .custom-tr td input[id='unit_qty']", function() {
            calculateTot();
        });

        calculateTot();

        // ADD ITEM FROM LIST
        $(document).on('click', '.btnAddItemFromList', function() {
            var $btn = $(this);
            counter = counter + 1;
            var item_code = $btn.attr('data-item-code');
            var item_name = $btn.attr('data-item-name');
            var item_id = $btn.attr('data-item-id');
            var item_purchase_price = $btn.attr('data-item-purchase-price');
            var item_discount = 0;

            // get selected customer for price type
            var customerId = $('#customer').val() || null;

            // If item already added, just increment qty
            if (hasItemAdded(item_id)) {
                $('#qty_' + item_id).val(parseFloat($('#qty_' + item_id).val()) + 1);
                calculateTot();
                return;
            }

            // Fetch price from server (considers outlet-specific and customer price type)
            $.post('process/get-item-price.php', { product_id: item_id, customer_id: customerId }, function(response) {
                var item_price = 0.00;
                var item_vat = 0.00;
                if (response && response.success) {
                    item_price = response.price;
                    item_vat = response.vat;
                } else {
                    // fallback to attribute price if server fails
                    item_price = $btn.attr('data-item-price') || 0;
                    item_vat = $btn.attr('data-item-vat') || 0;
                }

                // append row with computed price
                $("#myDatatable tbody").append('<tr class="custom-tr" id="number' + item_id + '">' +
                    '<td>' + counter + '</td>' +
                    '<td>' +
                    '<input type="hidden" value="' + item_id + '" name="item_id[]">' +
                    '<input type="hidden" value="' + counter + '" name="row_id[]">' +
                    '<input type="hidden" value="' + item_name + '" name="item_name[]">' + item_name +
                    '<input type="hidden" name="itemDiscount[]" id="itemDiscount" value="' + item_discount + '" class="form-control itemDiscount">' +
                    '<input type="hidden" name="itmVat[]" id="itmVat"  value="' + item_vat + '" class="form-control">' +
                    '</td>' +
                    '<td><input type="number" name="qty[]" style="width:60px;" value="1" data-item-code-qty="' + item_code + '" id="qty_' + item_id + '" class="form-control qqt input-sm"></td>' +
                    '<td><input type="text" name="unit_price[]" style="width:80px;" value="' + item_price + '" id="unit_price" data-item-code-qty="' + item_code + '" class="form-control itmprice input-sm"></td>' +
                    '<td class="itmTot"></td>' +
                    '<td><a href="#" class="btn btn-danger btn-xs removeItm_1" data-item-code-qty ="' + item_code + '" ><i class="fa fa-trash-o"></i></a></td>' +
                    '</tr>');

                calculateTot();
            }, 'json').fail(function() {
                // fallback behavior
                var item_price = $btn.attr('data-item-price') || 0;
                var item_vat = $btn.attr('data-item-vat') || 0;
                $("#myDatatable tbody").append('<tr class="custom-tr" id="number' + item_id + '">' +
                    '<td>' + counter + '</td>' +
                    '<td>' +
                    '<input type="hidden" value="' + item_id + '" name="item_id[]">' +
                    '<input type="hidden" value="' + counter + '" name="row_id[]">' +
                    '<input type="hidden" value="' + item_name + '" name="item_name[]">' + item_name +
                    '<input type="hidden" name="itemDiscount[]" id="itemDiscount" value="' + item_discount + '" class="form-control itemDiscount">' +
                    '<input type="hidden" name="itmVat[]" id="itmVat"  value="' + item_vat + '" class="form-control">' +
                    '</td>' +
                    '<td><input type="number" name="qty[]" style="width:60px;" value="1" data-item-code-qty="' + item_code + '" id="qty_' + item_id + '" class="form-control qqt input-sm"></td>' +
                    '<td><input type="text" name="unit_price[]" style="width:80px;" value="' + item_price + '" id="unit_price" data-item-code-qty="' + item_code + '" class="form-control itmprice input-sm"></td>' +
                    '<td class="itmTot"></td>' +
                    '<td><a href="#" class="btn btn-danger btn-xs removeItm_1" data-item-code-qty ="' + item_code + '" ><i class="fa fa-trash-o"></i></a></td>' +
                    '</tr>');

                calculateTot();
            });
        });

        $(document).on('click', '.removeItm_1', function() {
            $(this).parents('.custom-tr').eq(0).remove();
            calculateTot();
        });

        function hasItemAdded(item_id) {
            var hasAdded = false;
            $('#myDatatable tbody tr td input[name="item_id[]"]').each(function() {
                if ($(this).val() == String(item_id)) {
                    hasAdded = true;
                }
            })
            return hasAdded;
        }

        // Auto price
        jQuery(function($) {
            $('.autoprice').autoNumeric('init');
        });

        // Payment method show/hide
        function ShowHideDiv() {
            var drpDiscounthMethod = document.getElementById("drpDiscounthMethod");
            var dv_discounts_sum = document.getElementById("dv_discounts_sum");
            var dv_discounts_pre = document.getElementById("dv_discounts_pre");

            if (drpDiscounthMethod.value == "2") {
                dv_discounts_sum.style.display = "none";
                dv_discounts_pre.style.display = "block";
            } else if (drpDiscounthMethod.value == "3") {
                dv_discounts_sum.style.display = "block";
                dv_discounts_pre.style.display = "none";
            } else {
                dv_discounts_sum.style.display = "none";
                dv_discounts_pre.style.display = "none";
            }
        }

        // Autocomplete
        $(function() {
            $("#department_name").autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: "fetchData.php",
                        type: 'post',
                        dataType: "json",
                        data: {
                            search: request.term
                        },
                        success: function(data) {
                            response(data);
                        }
                    });
                },
                select: function(event, ui) {
                    $('#department_name').val(ui.item.value);
                    return false;
                }
            });
        });

        // Category click
        $(".btn_category").click(function() {
            var cat_id = $(this).attr('data-category-id');
            $(".btn_category").removeClass('active');
            $(this).addClass('active');

            // If cat_id is 0, we could reload all or hande in PHP. 
            // Existing PHP implementation in 'process/get_touch_item_lists_process.php' likely expects cat_id.
            // The default load shows all.
            if (cat_id == 0) {
                location.reload();
                return;
            }

            $.ajax({
                type: 'POST',
                url: 'process/get_touch_item_lists_process.php',
                data: {
                    cat_id: cat_id
                },
                success: function(response) {
                    $("#all_touch_item_display").html(response);
                }
            });
            return false;
        });

        // Barcode enter
        $('.barcode').keydown(function(event) {
            if (event.keyCode == 13) {
                event.preventDefault();
                var barcode = $(this).val();
                $.ajax({
                    url: 'process/add-sales-barcode-process.php',
                    type: 'POST',
                    data: {
                        barcode: barcode
                    },
                    success: function(result) {
                        // reload page or cart part
                        alert('Item Added via Barcode');
                        location.reload();
                    }
                });
            }
        });

        // Clear cart
        $("#clear_cart").click(function() {
            $.ajax({
                url: 'process/cart-clear-process.php',
                type: 'POST',
                data: {},
                success: function(result) {
                    alert('Cart Cleared');
                    location.reload();
                }
            });
        });

        // Customer/City change logic (simplified from original to just reload or recalc)
        $('.customer_mode').change(function() {
            var customer_id = $(this).val();
            $.ajax({
                url: 'process/customer_process.php',
                type: 'POST',
                data: {
                    customer_id: customer_id
                },
                success: function(result) {
                    if (customer_id != 1) {
                        $('#delicery_info').show();
                    } else {
                        $('#delicery_info').hide();
                    }
                }
            });
        });
    </script>
</body>

</html>