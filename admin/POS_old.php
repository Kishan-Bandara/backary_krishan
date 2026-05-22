<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');
$nowDate = date("d/m/Y");
?>
<?php
$db = new Database();
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
    //$query = $db->getRows('SELECT * FROM fifo  WHERE ft_location = ?  GROUP BY ft_item HAVING COUNT(ft_blanace) > 1',[$_SESSION['location']]);
    $query = $db->getRows('SELECT ft_item FROM fifo  WHERE ft_location = ? AND ft_blanace > 0 GROUP BY ft_item', [$_SESSION['location']]);
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

    $city_output = '';
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


    $mode_output = '<option value="">--- Please Select ---</option>';
    $db = new Database();
    $payment_type_query = $db->getRows('SELECT * FROM payment_method ');
    $payment_type_data = $payment_type_query;



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
    $query1 = $db->getRows('SELECT * FROM location_master WHERE id = ?', [$_SESSION['location']]);
    $data1 = $query1;
    foreach ($data1 as $query1) {
        $location_output .= '<option value="' . $query1['id'] . '">' . $query1['name'] . '</option>';
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

    $oldpid = $getpid['invoice_h_id'];
    if ($getpid > 0) {

        $newpid =  $oldpid + 1;
    }

    // product code ekak hadagannawa

    echo $refaranceCode = "SAL" . $randomNo . $newpid;
}


//Vat eka 

$query_vat = $db->getRow('SELECT * FROM product_vat_master');
$vat_value = 0;

function cart()
{
    if (!empty($_SESSION['SBCScart'])) {

        /////////////////////////////////////
        // List cart items
        /////////////////////////////////////

        // We store order detail in HTML


        // Equal total to 0
        $total = 0;
        // For finding session elements line number
        $linenumber = 0;
        $i = 0;

        // Run loop for cart array 
        foreach ($_SESSION['SBCScart'] as $SBCSitem) {
            $i = $i + 1;
            // Don't list items with 0 qty
            if ($SBCSitem['quantity'] != 0) {

                // We calculate total values with decimals
                $pricedecimal = str_replace(",", ".", $SBCSitem['price']);
                $qtydecimal = str_replace(",", ".", $SBCSitem['quantity']);

                $pricedecimal = (float) $pricedecimal;
                $qtydecimal = (float) $qtydecimal;

                $totaldecimal = $pricedecimal * $qtydecimal;


                // Write cart to screen
                // echo $SBCSitem['item']."<br>".$SBCSitem['price']."<br>".$SBCSitem['quantity']."<br>".$SBCSitem['item_id']."<br>";
                echo '<tr class="custom-tr" id="number' . $SBCSitem['item_id'] . '">

                              <td class="vertical-td">
       
        <a data-toggle="modal" data-target="#myModal">' . $i . '</a>
        </td>

    <td class="vertical-td">
        <input type="hidden" value="' . $SBCSitem['item_id'] . '" name="item_id[]">
         <input type="hidden" value="' . $i . '" name="row_id[]">
        <a data-toggle="modal" data-target="#myModal">
            <input type="hidden" value="' . $SBCSitem['item'] . '" name="item_name[]">' . $SBCSitem['item'] . '</a>
        </td>
        <td class="vertical-td">' . $SBCSitem['item_vat_value'] . '%<input type="hidden" name="itmVatRate[]" id="itmVatRate" data-vat="' . $SBCSitem['item_vat_value'] . '" value="' . $SBCSitem['item_vat_value'] . '" class="form-control"></td>
        <td class="vertical-td"><input type="text" id="unit_qty" name="qty[]" onchange="QtyUpdate()" style="width:50px;" value="' . $SBCSitem['quantity'] . '" data-item-code-qty="' . $SBCSitem['item_code'] . '" id="qty" class="form-control qqt"></td>
        <td class="vertical-td"><input type="text" name="unit_price[]" style="width:150px;" value="' . $pricedecimal . '" id="unit_price" data-item-code-qty="' . $SBCSitem['item_code'] . '" class="form-control itmprice"><input type="hidden" name="itmVat[]" id="itmVat" data-vat="' . $SBCSitem['item_vat_has'] . '" value="' . $SBCSitem['item_vat_has'] . '" class="form-control"></td>
        <td class="vertical-td itmTot">' . $totaldecimal . '</td>
        <td class="vertical-td"><a href="" class="btn btn-danger btn-xs removeItm" title="" data-toggle="tooltip" data-placement="top" data-row-id="1" data-item-code-qty ="' . $SBCSitem['item_code'] . '" onclick="return confirm(" are="" you="" sure="" want="" to="" delete="" this="" record="" ?");"="" data-original-title="Delete"><i class="fa fa-trash-o"></i></a></td>
    </tr>';


                // Total
                $total += $totaldecimal;
            }
            $linenumber++;
        }
    } else {

        echo "";
    }
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
    <link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">

    <link href="assets/global/plugins/celander/bootstrap-datetimepicker.min.css" rel="stylesheet">
    <link href="assets/global/plugins/celander/datepicker.css" rel="stylesheet">

    <style>
        .modal-dialog {
            margin: 0px auto;
        }

        .modal-backdrop.fade {
            opacity: 0.1;
            filter: alpha(opacity=0.1);
        }

        .modal-content {
            position: relative;
            background-color: #FFF;
            border: 1px solid #CECECE;
            border-radius: 0px;
            -webkit-box-shadow: none;
            box-shadow: none;
            background-clip: padding-box;
            outline: 0;
        }


        .modal-header {
            padding: 11px 15px;
            background-color: #F8F8F8;
            background: -webkit-linear-gradient(top, #F8F8F8, #F2F2F2);
            background: -moz-linear-gradient(top, #f8f8f8, #f2f2f2);
            background: -ms-linear-gradient(top, #f8f8f8, #f2f2f2);
            background: -o-linear-gradient(top, #f8f8f8, #f2f2f2);
            background: linear-gradient(top, #f8f8f8, #f2f2f2);
        }
    </style>
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

                <!-- END PAGE HEADER-->
                <form method="POST" enctype="multipart/form-data" id="frn-add">
                    <div class="row">
                        <div class="col-md-12">

                            <div class="portlet box blue-hoki ">
                                <div class="portlet-title ">
                                    <div class="caption">
                                        <i class="fa fa-plus"></i>New Sales</div>
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
                                            <div class="col-md-8">
                                                <div class="portlet light bordered">

                                                    <div class="portlet-body form">
                                                        <div class="row">
                                                            <div class="col-md-8">
                                                                <div class="form-group">



                                                                    <?php echo load_hold_orders(); ?>



                                                                </div>
                                                            </div>


                                                            <div class="col-md-4" style="    text-align: right;">
                                                                <div style=""><input type="button" id="clear_cart" class="btn  red " value="CLEAR CART"></div>
                                                            </div>
                                                        </div>
                                                        <!-- Tab Start -->
                                                        <div class="portlet-body">
                                                            <ul class="nav nav-tabs">
                                                                <li class="active">
                                                                    <a href="#tab_1_1" data-toggle="tab" aria-expanded="true">Shopping Cart</a>
                                                                </li>
                                                                <li class="">
                                                                    <a href="#tab_1_2" data-toggle="tab" aria-expanded="false">Search Product</a>
                                                                </li>

                                                            </ul>
                                                            <div class="tab-content">
                                                                <div class="tab-pane fade active in" id="tab_1_1">
                                                                    <!-- BEGIN EXAMPLE TABLE PORTLET-->
                                                                    <div class="portlet light bordered">
                                                                        <div class="portlet-title">
                                                                            <div class="row">

                                                                                <div class="col-md-5">


                                                                                    <div class="input-group barcode ">
                                                                                        <span class="input-group-btn">
                                                                                            <button type="button" class="btn btn-info btnAddItemFromList">Barcode</button>
                                                                                        </span>
                                                                                        <input type="text" name="Getbarcode" id="department_name" class="form-control barcode" placeholder="Scan Product Barcode">
                                                                                        <input type="hidden" name="itmQty" value="1">

                                                                                    </div>
                                                                                    <div id="result"> </div>




                                                                                    <!-- /input-group -->
                                                                                </div>
                                                                                <!-- /.col-lg-6 -->

                                                                            </div>
                                                                            <div class="tools"> </div>
                                                                        </div>

                                                                        <div id="cart_content">



                                                                            <table class="table table-bordered table-hover " id="myDatatable">
                                                                                <thead>
                                                                                    <!-- Table head -->
                                                                                    <tr>
                                                                                        <th class="active ">#</th>
                                                                                        <th class="active col-sm-6">Product</th>
                                                                                        <th class="active ">VAT+</th>
                                                                                        <th class="active ">Qty</th>
                                                                                        <th class="active ">Unit Price</th>
                                                                                        <th class="active">Total</th>
                                                                                        <th class="active">Action</th>

                                                                                    </tr>
                                                                                </thead><!-- / Table head -->
                                                                                <tbody>
                                                                                    <!-- / Table body -->

                                                                                    <?php echo cart(); ?>

                                                                                    <!--get all sub category if not this empty-->




                                                                                </tbody><!-- / Table body -->
                                                                            </table> <!-- / Table -->
                                                                        </div>
                                                                    </div>
                                                                    <!-- END EXAMPLE TABLE PORTLET-->
                                                                </div>
                                                                <div class="tab-pane fade" id="tab_1_2">
                                                                    <div class="portlet-body">
                                                                        <table class="table table-bordered table-hover" width="100%" id="sample_2">
                                                                            <thead>
                                                                                <tr>
                                                                                    <th class="all"></th>
                                                                                    <th class="all">Product Code</th>
                                                                                    <th class="all">Product Name</th>
                                                                                    <th class="all">Product Qty</th>


                                                                                    <th class="all"></th>


                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>

                                                                                <?php $data = getProducts();
                                                                                foreach ($data as $query) {
                                                                                    $item_id = $query['ft_item'];

                                                                                    $query_item_id = $db->getRow('SELECT * FROM item_master WHERE item_id = ?', [$item_id]);
                                                                                    $query_get_qty = $db->getRow('SELECT SUM(ft_blanace) as qty , ft_rate FROM fifo WHERE ft_item = ? AND ft_location = ?', [$item_id, $_SESSION['location']]);
                                                                                    $master_item_name = $query_item_id['item_name'];
                                                                                    $master_item_code = $query_item_id['item_code'];
                                                                                    $master_item_vat = $query_item_id['item_vat'];
                                                                                    $master_item_qty = $query_get_qty['qty'];
                                                                                    $master_item_price = $query_get_qty['ft_rate'];

                                                                                    if ($master_item_vat == "Y") {

                                                                                        $vat_has = $vat_value . "%";
                                                                                    } else {
                                                                                        $vat_has = "0.00%";
                                                                                    }

                                                                                ?>

                                                                                    <tr>
                                                                                        <td></td>
                                                                                        <td><?php echo  $master_item_code; ?></td>
                                                                                        <td><?php echo  $master_item_name; ?></td>
                                                                                        <td><?php echo  $master_item_qty; ?></td>


                                                                                        <td>


                                                                                            <button type="button" class="btn btn-primary btn-xs btnAddItemToPopUp" title="" data-toggle="modal" data-target="#myModal" data-toggle="tooltip" data-item-code-pop="<?php echo  $master_item_code; ?>" data-item-id="<?php echo $item_id; ?>" data-item-name="<?php echo $master_item_name; ?>" data-item-vat-name="<?php echo $vat_has; ?>" data-item-vat="<?php echo  $master_item_vat; ?>" data-item-price="<?php echo  $master_item_price; ?>" data-placement="top" data-original-title="Purchase"><i class="fa fa-shopping-cart"></i></button>
                                                                                        </td>


                                                                                    </tr>
                                                                                <?php }
                                                                                ?>

                                                                            </tbody>
                                                                        </table>
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


                                            <div class="col-md-4 col-sm-12">
                                                <div class="col-md-12">
                                                    <div class="box flash animated">
                                                        <div class="portlet box blue-hoki">
                                                            <div class="portlet-title">
                                                                <div class="caption">
                                                                    <i class=""></i>Order Summary </div>

                                                            </div>

                                                            <div class="portlet-body">

                                                                <div class="row" style="padding:0px;">
                                                                    <div class="col-md-12">
                                                                        <div class="form-group">
                                                                            <label>Order No#</label>
                                                                            <input type="text" class="form-control" name="freNo" value="<?php echo getReferance(); ?>" readonly=""> </div>
                                                                    </div>

                                                                </div>

                                                            </div>

                                                        </div>
                                                        <div class="box-body" style="padding-bottom:5px;">

                                                            <div class="row">

                                                                <div class="col-md-12">


                                                                    <div class="form-group">

                                                                        <div class="col-md-12" style="padding-left: 5px;padding-right: 5px;">
                                                                            <select class="form-control select2me select2-hidden-accessible customer_mode" id="customer" name="customer" tabindex="-1" aria-hidden="true">
                                                                                <?php echo getCustomers(); ?>
                                                                            </select>
                                                                        </div>
                                                                    </div>


                                                                </div>


                                                            </div>
                                                        </div>
                                                        <div class="box-background" style="margin-top:20px; padding:10px;">
                                                            <div class="box-body">
                                                                <div class="row">
                                                                    <div class="col-md-12">
                                                                        <div class="form-group">

                                                                            <div class="input-group" style="padding-right:10px; ">
                                                                                <span class="input-group-addon">
                                                                                    <strong> Sub Total </strong>
                                                                                </span>
                                                                                <input type="text" id="grossTot" name="txtSubTotal" class="form-control autoprice"> </div>

                                                                        </div>

                                                                        <div class="form-group">

                                                                            <div class="col-md-12" style="padding-left: 1px;padding-right: 1px;">
                                                                                <select class="form-control select2me select2-hidden-accessible cities" name="cities" id="cities" tabindex="-1" aria-hidden="true">
                                                                                    <?php echo load_city(); ?>
                                                                                </select>
                                                                            </div>
                                                                        </div>

                                                                        <br> <br>
                                                                        <div class="form-group ">


                                                                            <select class="form-control" name="drpDiscounthMethod" id="drpDiscounthMethod" onchange="ShowHideDivForDiscount()">
                                                                                <option value="1">No Discounts</option>
                                                                                <option value="2">Discount Type (%)</option>
                                                                                <option value="3">Discount Type (-)</option>
                                                                            </select>

                                                                        </div>

                                                                        <div class="form-group" id="dv_discounts_sum" style="display: none">

                                                                            <div class="input-group" style="padding-right:10px; ">
                                                                                <span class="input-group-addon">
                                                                                    <strong>% </strong>
                                                                                </span>
                                                                                <input type="text" id="Discountprecentage_value" name="Discountprecentage_value" class="form-control update_discount"> </div>
                                                                        </div>
                                                                        <div class="form-group" id="dv_discounts_pre" style="display: none">

                                                                            <div class="input-group" style="padding-right:10px; ">
                                                                                <span class="input-group-addon">
                                                                                    <strong>-</strong>
                                                                                </span>
                                                                                <input type="text" id="DiscountSUM_value" name="DiscountSUM_value" class="form-control update_discount"> </div>
                                                                        </div>







                                                                    </div>

                                                                </div>
                                                                <!-- /.box-body -->
                                                            </div>

                                                        </div>
                                                        <div class="box-body">
                                                            <div class="row">
                                                                <div class="col-md-12">

                                                                    <div class="form-group" style="margin-top:10px;color:green;">
                                                                        <label class="col-sm-5 control-label" style="text-align:left;"></label>
                                                                        <div class="col-sm-7">
                                                                            <p style="text-align:left;font-size: 15px;">Discount Value : <span id="discount_value_display"> 0.00</span></p>


                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-12">

                                                                    <div class="form-group">
                                                                        <label class="col-sm-5 control-label" style="padding-top: 25px">Grand Total :</label>
                                                                        <div class="col-sm-7">
                                                                            <h1 id='grandTot'>LKR 0.00</h1>
                                                                            <input type="hidden" class="form-control" id="grandTotTotHidden" name="netvalue">

                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="box-background" style="margin-top:20px; padding:10px;">
                                                            <div class="box-body">
                                                                <div class="row ">
                                                                    <div class="col-md-12 ">
                                                                       
                                                                            <div class="form-group ">


                                                                                <select class="form-control payment_mode" name="drpPaymethMethod" id="drpPaymethMethod" onchange="ShowHideDiv()">
                                                                                    <?php echo getPaymentType(); ?>
                                                                                </select>
                                                                                
                                                                            </div>

                                                                                <div class="form-group " id="dvcheque" style="display:none;">
                                                                                    <label for="input-payment-postcode" class="control-label">Cheque Ref</label>
                                                                                    <div class="form-group">
                                                                                        <input class="form-control form-control-inline " size="" id="txtchequeNo" id="txtchequeNo" name="text" type="text" value="">

                                                                                    </div>
                                                                                </div>

                                                                                <div class="form-group " id="dvcard" style="display:none;">
                                                                                    <label for="input-payment-postcode" class="control-label">Card Ref</label>
                                                                                    <div class="form-group">
                                                                                        <input class="form-control form-control-inline " size="" id="txtcardNo" id="txtcardNo" name="text" type="text" value="">

                                                                                    </div>
                                                                                </div>
                                                                         
                                                                                <div id="delicery_info">

                                                                            <div class="form-group ">


                                                                                <select class="form-control delivery_mode" name="Delivery_Type" id="Delivery_Type" onchange="ShowHideDiv()">
                                                                                    <?php echo getdeliverymode(); ?>
                                                                                </select>

                                                                            </div>
                                                                            <div class="row">
                                                                                <div class="col-sm-6">

                                                                                    <div class="form-group required">
                                                                                        <label for="input-payment-postcode" class="control-label">Delivery Date</label>
                                                                                        <div class="form-group">
                                                                                            <input class="form-control form-control-inline input-medium date-picker" size="16" id="example1" name="date" type="text" value="<?php echo $nowDate; ?>">

                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-sm-6">

                                                                                    <div class="form-group required">
                                                                                        <label for="input-payment-postcode" class="control-label">Delivery Time Slot</label>
                                                                                        <select name="time" id="time" class="form-control">
                                                                                            <option value="9.00 AM - 10.00 AM">9.00 AM - 10.00 AM</option>
                                                                                            <option value="10.00 AM - 11.00 AM">10.00 AM - 11.00 AM</option>
                                                                                            <option value="11.00 AM - 12.00 PM">11.00 AM - 12.00 PM</option>
                                                                                            <option value="12.00 PM - 1.00 PM">12.00 PM - 1.00 PM</option>
                                                                                            <option value="1.00 PM - 2.00 PM">1.00 PM - 2.00 PM</option>
                                                                                            <option value="2.00 PM - 3.00 PM">2.00 PM - 3.00 PM</option>
                                                                                            <option value="3.00 PM - 4.00 PM">3.00 PM - 4.00 PM</option>
                                                                                            <option value="4.00 PM - 5.00 PM">4.00 PM - 5.00 PM</option>
                                                                                            <option value="5.00 PM - 6.00 PM">5.00 PM - 6.00 PM</option>
                                                                                            <option value="6.00 PM - 7.00 PM">6.00 PM - 7.00 PM</option>
                                                                                            <option value="7.00 PM - 8.00 PM">7.00 PM - 8.00 PM</option>
                                                                                            <option value="8.00 PM - 9.00 PM">8.00 PM - 9.00 PM</option>
                                                                                            <option value="9.00 PM - 10.00 PM">9.00 PM - 10.00 PM</option>

                                                                                        </select>
                                                                                    </div>
                                                                                </div>
                                                                            </div>



                                                                            <div class="tabbable-custom nav-justified" style="padding-top:10px;">
                                                                                <ul class="nav nav-tabs nav-justified">
                                                                                    <li class="active">
                                                                                        <a href="#tab_1_1_1" data-toggle="tab">Shipping Address </a>
                                                                                    </li>
                                                                                    <li>
                                                                                        <a href="#tab_1_1_2" data-toggle="tab"> Order Note </a>
                                                                                    </li>

                                                                                </ul>
                                                                                <div class="tab-content">
                                                                                    <div class="tab-pane active" id="tab_1_1_1">
                                                                                        <textarea class="form-control" rows="3" id="delivery_address" name="delivery_address"><?php echo $session_delivery_address; ?></textarea>

                                                                                    </div>
                                                                                    <div class="tab-pane" id="tab_1_1_2">
                                                                                        <textarea class="form-control" rows="3" id="txtOrderNote" name="txtOrderNote"></textarea>
                                                                                    </div>

                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                        <input type="submit" class="btn blue btn-block" id="button-confirm" name="sales" value="Submit Order">
                                                                        <input type="submit" class="btn yellow btn-block" id="button-hold" name="button-hold" value="Hold this Order">
                                                                        <input type="submit" class="btn red btn-block" id="" name="sales" value="Cancel">
                                                                    </div>

                                                                </div>
                                                                <!-- /.box-body -->
                                                            </div>

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
    </div>
    <!-- END CONTAINER -->
    <?php include('common/footer.php'); ?>

    <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    <h4 class="modal-title" id="myModalLabel">Add Quantity</h4>
                </div>
                <div class="modal-body">
                    <input type="text" name="qqtAdding" style="width:150px;" value="1" id="qqtAdding" class="form-control ">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary btnAddItemFromList" data-item-code="9''ASSO-BAL-10PCS">Add to cart</button>
                </div>
            </div>
        </div>
    </div>
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


</body>

</html>
<script type="text/javascript">
    // When the document is ready
    $(document).ready(function() {

        $('#example1').datepicker({
            format: "dd/mm/yyyy"
        });

    });
</script>

<script>
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



        $("#myDatatable tbody .custom-tr").each(function() {

            var item_discount_value = 0.00;
            var discount_type = $('#drpDiscounthMethod option:selected').val();
            var discount_value_pct = $('#Discountprecentage_value').val();
            var discount_value_sum = $('#DiscountSUM_value').val();
            var discount_value_POS = 0.00;

            if (discount_value_sum == "") {
                discount_value_sum = 0.00;
            }
            $(this).find(".itmTot").text((parseFloat($(this).find("td input[id='unit_price']").val()) * parseFloat($(this).find("td input[id='unit_qty']").val()) - 0.00).toFixed(2));

            tot += (parseFloat($(this).find("td input[id='unit_price']").val()) * parseFloat($(this).find("td input[id='unit_qty']").val()));
            $(this).find(".itmTot").each(function() {
                total += parseInt($(this).text());
            });


            if (discount_type == 2) {

                discount_type = "PCT";
                discount_value_POS = (total * discount_value_pct) / 100;
                discount_charge_for_display = discount_value_POS;


            } else if (discount_type == 3) {

                discount_type = "SUM";
                discount_value_POS = discount_value_sum;
                discount_charge_for_display = discount_value_POS;

            } else {

                discount_type = "EOR";
                discount_value_POS = 0.00;

            }



            deliveryRate = (deliveryRate) ? deliveryRate : '0.00';

            grandTotal = (total >= 1000) ? grandTotal = (parseFloat(total) - parseFloat(discount_value_POS)) : grandTotal = (parseFloat(total) - parseFloat(discount_value_POS) + parseFloat(deliveryRate));
            grandTotal_with_delivery = grandTotal;


        });

        $("#grossTot").val(total.toFixed(2));
        $('#cartItemTot').text(tot.toFixed(2));
        $('#grandTot').text(grandTotal_with_delivery.toFixed(2));
        $('#discount_value_display').text(discount_charge_for_display.toFixed(2));
        if (total > 1000) {

            $("#Delivery_rate").text(0.00);
        } else {


            $("#Delivery_rate").text(deliveryRate);
        }

        if (total < 1000 && coupon_rate && coupon_type) {
            $.ajax({
                url: 'process/coupon_remove_process.php',
                success: function(response) {
                    alert(response);
                    location.reload();

                }
            });
        }


    }

    jQuery('.update_discount').on('input', function() {

        calculateTot();

    });


    $("#drpDiscounthMethod").change(function() {


        calculateTot();


    });





    $(document).on('change', "#myDatatable tbody .custom-tr td input[id='unit_price'], #myDatatable tbody .custom-tr td input[id='unit_qty']", function() {
        calculateTot();
    });


    calculateTot();
</script>


<script>
    $('.btnAddItemToPopUp').click(function() {
        counter = counter + 1;
        var barcode_id = $(this).attr('data-item-code-pop');
        $('.btnAddItemFromList').attr('data-item-code', barcode_id);



    });
</script>

<script>
    counter = 0;
    $('.btnAddItemFromList').click(function() {
        counter = counter + 1;
        var barcode = $(this).attr('data-item-code');
        var item_qty = $("#qqtAdding").val();
        $('#myModal').modal('toggle');
        $.ajax({
            url: 'process/add-sales-barcode-process.php',
            type: 'POST',
            data: {
                barcode: barcode,
                itmQty: item_qty
            },
            success: function(result) {
                $('#myDatatable').load(document.URL + ' #myDatatable', function() {
                    $("#Getbarcode").val("");
                    calculateTot();

                });

            }

        });
    });
</script>


<script>
    counter = 0;
    $('.hold_btn_id').click(function() {
        counter = counter + 1;
        var barcode = $(this).attr('data-invoice-code');

        $.ajax({
            url: 'process/hold_invoice_add_session_process.php',
            type: 'POST',
            data: {
                barcode: barcode
            },
            success: function(result) {
                $('#myDatatable').load(document.URL + ' #myDatatable', function() {
                    $("#Getbarcode").val("");
                    calculateTot();

                });

            }

        });
    });
</script>






<script>
    counter = 0;
    $('.removeItm').click(function() {
        var itmQty = 0;
        var item_code = $(this).attr('data-item-code-qty');

        $.ajax({
            url: 'process/add-sales-qty-process.php',
            type: 'POST',
            data: {
                barcode: item_code,
                itmQty: itmQty
            },
            success: function(result) {

                $('#myDatatable').load(document.URL + ' #myDatatable');
            }

        });
    });
</script>


<!-- auto price set -->
<script type="text/javascript">
    jQuery(function($) {
        $('.autoprice').autoNumeric('init');
    });
</script>

<!-- payment method -->
<script type="text/javascript">
    function ShowHideDiv() {

        var drpPaymethMethod = document.getElementById("drpPaymethMethod");
        var dvcheque = document.getElementById("dvcheque");

        dvcheque.style.display = drpPaymethMethod.value == "2" ? "block" : "none";
        dvcard.style.display = drpPaymethMethod.value == "3" ? "block" : "none";
    }
</script>

<script type="text/javascript">
        function ShowHideDivForDiscount() {
            var drpPaymethMethod = document.getElementById("drpDiscounthMethod");
            var dv_discounts_sum = document.getElementById("dv_discounts_sum");
           
            dv_discounts_sum.style.display = drpPaymethMethod.value == "2" ? "block" : "none";
            dv_discounts_pre.style.display = drpPaymethMethod.value == "3" ? "block" : "none";
        }
    </script>



<!-- barcode src -->
<script type="text/javascript">
    $(function() {
        var availableTags = <?php include('search.php'); ?>;
        $("#department_name").autocomplete({
            source: availableTags,
            autoFocus: true
        });
    });
</script>



<!-- barcode src values sent using ajax -->
<script>
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

                    //$("#result").text(result);
                    $('#myDatatable').load(document.URL + ' #myDatatable', function() {
                        $("#Getbarcode").val("");
                        calculateTot();

                    });


                }

            });
        }
    });



    $(".hold_btn_id").click(function() {


        var barcode = $(this).attr('data-invoice-code');



        if (barcode) {


            $.ajax({
                url: 'process/hold_invoice_add_session_process.php',
                type: 'POST',
                data: {
                    barcode: barcode
                },
                beforeSend: function() {



                },
                success: function(result) {



                    /*   var jsonobj = JSON.parse(result);
                       
                       window.location.replace("receipt.php?id="+jsonobj.order_id);*/

                }

            });

        } else {


            alert('Please fill the all details');

        }


    });


















    function clearInput() {
        $("#frnAdd :input").each(function() {
            $(this).val('');
        });
    }
</script>
<!-- qty update using ajax -->
<script>
    $(document).on('change', '.qqt', function() {
        var itmQty = $(this).val();
        var item_code = $(this).attr('data-item-code-qty');

        $.ajax({
            url: 'process/add-sales-qty-process.php',
            type: 'POST',
            data: {
                barcode: item_code,
                itmQty: itmQty
            },
            success: function(result) {

                calculateTot();
            }

        });

    });


    $('.itmprice').change(function() {
        var itmprice = $(this).val();
        var item_code = $(this).attr('data-item-code-qty');

        $.ajax({
            url: 'process/add-sales-item_price-process.php',
            type: 'POST',
            data: {
                barcode: item_code,
                itmprice: itmprice
            },
            success: function(result) {

                /*$('#myDatatable').load(document.URL +  ' #myDatatable');*/
                alert(result);
                calculateTot();
            }

        });

    });



    $(function() {
        $("#department_name").focus();
    });
</script>


<!-- city  change funtion -->
<script>
    window.onload = GetAllProperties;

    function GetAllProperties() {


        var city_id = $('#cities').val();


        $.ajax({
            url: 'process/location_rate_process.php',
            type: 'POST',
            data: {
                city_id: city_id
            },

            success: function(result) {


                calculateTot();

                /*      var jsonobj = JSON.parse(result);
                $("#Delivery_rate").text(jsonobj.rate);*/

            }

        });



    }


    function cityGen() {
        $('.cities').change(function() {

            var city_id = $(this).val();


            $.ajax({
                url: 'process/location_rate_process.php',
                type: 'POST',
                data: {
                    city_id: city_id
                },

                success: function(result) {


                    calculateTot();
                    location.reload();
                    /*      var jsonobj = JSON.parse(result);
                    $("#Delivery_rate").text(jsonobj.rate);*/

                }

            });



        });
    }
    cityGen();
</script>

<!-- customer  change funtion -->
<script>
    function customerGen() {
        $('.customer').change(function() {

            var customer_id = $(this).val();


            $.ajax({
                url: 'process/location_rate_process.php',
                type: 'POST',
                data: {
                    customer_id: customer_id
                },

                success: function(result) {

                    var jsonobj = JSON.parse(result);
                    $("#Delivery_rate").text(jsonobj.rate);
                    calculateTot();
                    location.reload();

                }

            });



        });
    }
    cityGen();
</script>

<!-- payment Type change funtion -->
<script>
    function payment_mode() {
        $('.payment_mode').change(function() {


            var payment_id = $(this).val();


            $.ajax({
                url: 'process/payment_type_process.php',
                type: 'POST',
                data: {
                    payment_id: payment_id
                },

                success: function(result) {





                }

            });



        });
    }
    payment_mode();
</script>



<!-- change funtion -->
<script>
    $(document).ready(function() {
        $('.delivery_mode').change(function() {


            var delivery_id = $(this).val();


            $.ajax({
                url: 'process/delivery_mode_process.php',
                type: 'POST',
                data: {
                    delivery_id: delivery_id
                },

                success: function(result) {
                    var jsonobj = JSON.parse(result);

                    $("textarea#delivery_address").val(jsonobj.delivery_address);


                }

            });



        });
    });
</script>

<!-- customer change funtion -->
<script>
    $(document).ready(function() {

        var customer_id = <?php echo json_encode($_SESSION["customerid"]); ?>;

        if (customer_id == 1) {
            $('#delicery_info').hide();
        } else {

            $('#delicery_info').show();
        }

    });


    function customer_mode() {
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
    }
    customer_mode();
</script>


<script>
    $("#button-confirm").click(function() {


        var customer = $('#customer').val();
        var txtOrderNote = $("#txtOrderNote").val();
        var delivery_address = $("#delivery_address").val();
        var time = $('#time option:selected').text();
        var Delivery_Type = $('#Delivery_Type').val();
        var drpPaymethMethod = $('#drpPaymethMethod').val();
        var delivery_date = $("#example1").val();

        var discount_type = $("#drpDiscounthMethod").val();
        var discount_value_sum = $("#DiscountSUM_value").val();
        var discount_value_precentage = $("#Discountprecentage_value").val();


        var txtcardNo = $("#txtcardNo").val();
        var txtchequeNo = $("#txtchequeNo").val();



        if (customer == 1 || time && Delivery_Type && drpPaymethMethod && delivery_date) {


            $.ajax({
                url: 'process/add-sales-process.php',
                type: 'POST',
                data: {
                    customer: customer,
                    txtOrderNote: txtOrderNote,
                    delivery_address: delivery_address,
                    Delivery_Type: Delivery_Type,
                    drpPaymethMethod: drpPaymethMethod,
                    time: time,
                    delivery_date: delivery_date,
                    discount_type: discount_type,
                    discount_value_sum: discount_value_sum,
                    discount_value_precentage: discount_value_precentage,
                    txtcardNo:txtcardNo,
                    txtchequeNo:txtchequeNo
                },
                beforeSend: function() {

                    $("#button-confirm").val('sending ...');

                },
                success: function(result) {
                    alert(result);
                    var jsonobj = JSON.parse(result);

                    window.location.replace("receipt.php?id=" + jsonobj.order_id);

                }

            });

        } else {


            alert('Please fill the all details');

        }


    });


    $("#button-hold").click(function() {


        var customer = $('#customer').val();
        var txtOrderNote = $("#txtOrderNote").val();
        var delivery_address = $("#delivery_address").val();
        var time = $('#time option:selected').text();
        var Delivery_Type = $('#Delivery_Type').val();
        var drpPaymethMethod = $('#drpPaymethMethod').val();
        var delivery_date = $("#example1").val();

        var discount_type = $("#drpDiscounthMethod").val();
        var discount_value_sum = $("#DiscountSUM_value").val();
        var discount_value_precentage = $("#Discountprecentage_value").val();




        $.ajax({
            url: 'process/hold-sales-process.php',
            type: 'POST',
            data: {
                customer: customer,
                txtOrderNote: txtOrderNote,
                delivery_address: delivery_address,
                Delivery_Type: Delivery_Type,
                drpPaymethMethod: drpPaymethMethod,
                time: time,
                delivery_date: delivery_date,
                discount_type: discount_type,
                discount_value_sum: discount_value_sum,
                discount_value_precentage: discount_value_precentage
            },
            beforeSend: function() {

                $("#button-hold").val('sending ...');

            },
            success: function(result) {
                alert(result);
                var jsonobj = JSON.parse(result);


            }

        });




    });



    $("#clear_cart").click(function() {





        $.ajax({
            url: 'process/cart-clear-process.php',
            type: 'POST',
            data: {},
            beforeSend: function() {

                $("#clear_cart").val('sending ...');

            },
            success: function(result) {

                $("#clear_cart").val('CLEAR CART');
                alert(result);
                location.reload();
                $('#myDatatable').load(document.URL + ' #myDatatable');



            }

        });




    });
</script>





<script type="text/javascript">
    $(function() {

        // Single Select
        $("#department_name").autocomplete({
            source: function(request, response) {
                // Fetch data
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
                // Set selection
                $('#department_name').val(ui.item.value); // display the selected text
                $('#selectuser_id').val(ui.item.value); // save selected id to input
                return false;
            }
        });



    });

    function split(val) {
        return val.split(/,\s*/);
    }

    function extractLast(term) {
        return split(term).pop();
    }
</script>



