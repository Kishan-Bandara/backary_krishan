<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
if (isset($_SESSION['LoginStatus'])) {
    $LoginStatus = $_SESSION['LoginStatus'];
} else {
    $LoginStatus = "";
}
if ($LoginStatus != "login_success") {
    Redirect('login.php', false);
}

$is_cities = "";
$order_confirm = true;

(isset($_SESSION["contactPerson"])) ? $contactPerson = $_SESSION["contactPerson"] : $contactPerson = "";
(isset($_SESSION["address"])) ? $address = $_SESSION["address"] : $address = "";
(isset($_SESSION["address2"])) ? $address2 = $_SESSION["address2"] : $address2 = "";
(isset($_SESSION["cityByName"])) ? $cityByName = $_SESSION["cityByName"] : $cityByName = "";
(isset($_SESSION["contactNo"])) ? $contactNo = $_SESSION["contactNo"] : $contactNo = "";
(isset($_SESSION["EmailAddress"])) ? $EmailAddress = $_SESSION["EmailAddress"] : $EmailAddress = "";
(isset($_SESSION["OrderNote"])) ? $OrderNote = $_SESSION["OrderNote"] : $OrderNote = "";

function filter($var)
{

    return preg_replace('[0-9]', ' ', $var);
}

function contryList()
{

    $db = new database();
    $query = $db->getRows('SELECT * FROM country WHERE active = ?', [1]);
    return $query;
}

if (isset($_SESSION["is_Cities"])) {
    $is_cities = $_SESSION["is_Cities"];
}
if (isset($_SESSION["countryName"])) {
    $country_name_session = $_SESSION["countryName"];
} else {
    $country_name_session = "";
}
function getDeliveryType()
{
    (isset($_SESSION["countryId"])) ? $real_contry_id = $_SESSION["countryId"] : $real_contry_id = 0;
    $db = new database();
    $get_dispatch_type_query = $db->getRow('SELECT DispatchType FROM country WHERE pk_id = ?', [$real_contry_id]);
    if ($get_dispatch_type_query > 0) {
        $dispatch_type = $get_dispatch_type_query['DispatchType'];
    } else {
        $dispatch_type = "";
    }

    if (isset($_SESSION["delivery_Type"])) {
        $payment_type_id = $_SESSION["delivery_Type"];
    } else {
        $payment_type_id = "";
    }


    $payment_type_output = '';
    $db = new Database();
    $payment_type_query = $db->getRows('SELECT * FROM shipping_method WHERE DispatchType = ?', [$dispatch_type]);
    $payment_type_data = $payment_type_query;

    $mode_output = "";

    foreach ($payment_type_data as $payment_type_query) {
        $sel = "";
        $payment_typeID = $payment_type_query['id'];
        if ($payment_typeID == $payment_type_id) {

            $sel = "checked";
        }

        
        $mode_output .= '<div class="radio "> <label style="font-size: 18px;"> <input style="height: 20px;    width: 20px;" type="radio" ' . $sel . ' name="delivery_Type" class="payment_Type"  value="' . $payment_type_query['id'] . '"> ' .  $payment_type_query['title'] .    '</div>';
    }
    return $mode_output;
}

function mainCart()
{

    $db = new Database();

    if (!empty($_SESSION['SBCScart'])) {



        $total = 0;

        $linenumber = 0;
        $i = 0;


        foreach ($_SESSION['SBCScart'] as $SBCSitem) {
            $i = $i + 1;

            if ($SBCSitem['quantity'] != 0) {

                $session_item_id = str_replace(",", ".", $SBCSitem['item_id']);
                $session_item_name = $SBCSitem['item'];
                $session_item_image = $SBCSitem['item_image'];
                $imagepath = $SBCSitem['image_path'];

                $pricedecimal = str_replace(",", ".", $SBCSitem['price']);
                $qtydecimal = str_replace(",", ".", $SBCSitem['quantity']);
                $get_item_discount = str_replace(",", ".", $SBCSitem['item_discount']);

                $pricedecimal = (float) $pricedecimal;
                $qtydecimal = (float) $qtydecimal;
                $get_item_discount = $get_item_discount;

                $totaldecimal = $pricedecimal * $qtydecimal;

                $queryGetItem = $db->getRow('SELECT * FROM item_master WHERE item_id = ?', [$session_item_id]);

                $productPrice =  ($queryGetItem['item_normal_selling_price']) ? $queryGetItem['item_normal_selling_price'] : 0.00;
                $others_selling_price = ($queryGetItem['others_selling_price']) ? $queryGetItem['others_selling_price'] : 0.00;
                $item_discount = ($queryGetItem['item_discount']) ? $queryGetItem['item_discount'] : 0.00;
                $itemCode = $queryGetItem['item_id'];

                $saveAmount = (($pricedecimal) * $get_item_discount) / 100;
                $afterDiscountAmount = $pricedecimal - $saveAmount;
                $itemTotalDecimal = $afterDiscountAmount * $qtydecimal;

                if ($session_item_image) {

                    $productimage = $imagepath . "/" . $session_item_image;
                } else {
                    $productimage = "images/product_img/defult-img.png";
                }

               
            

                echo  ' <div class="ps-checkout__row ps-product">
                <div class="ps-product__name">' . $session_item_name . ' x <span>' . $qtydecimal . '</span></div>
                <div class="ps-product__price">' . currency($itemTotalDecimal) . '</div>
            </div>';



                // Total
                $total += $totaldecimal;
            }
            $linenumber++;
        }
    } else {

        echo "";
    }
}
function getCity()
{
    $city_id = $_SESSION["city"];
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
    if (isset($_SESSION["paymentId"])) {
        $payment_type_id = $_SESSION["paymentId"];
    } else {
        $payment_type_id = "";
    }
    $payment_type_output = '';
    $db = new Database();
    $payment_type_query = $db->getRows('SELECT * FROM payment_method WHERE website_status = ?', ['Y']);
    $payment_type_data = $payment_type_query;
    $mode_output = "";
    foreach ($payment_type_data as $payment_type_query) {
        $sel = "";
        $payment_typeID = $payment_type_query['id'];
        if ($payment_typeID == $payment_type_id) {

            $sel = "checked";
        }




        $mode_output .= '<div class="radio "> <label style="font-size:18px;"> <input type="radio" ' . $sel . ' style="width:20px; height:20px;" name="payment_Type" class="payment_Type" value="' . $payment_type_query['id'] . '"> ' . $payment_type_query['type'] .    '     <img src="' . $payment_type_query['img'] . '" > </label></div>';
    }
    return $mode_output;
}

?>

<!DOCTYPE html>
<html lang="en">


<head>
    <?php include('common/styles.php'); ?>
    <style>
        .checkout-page-wrapper {
            padding: 60px 0;
            background-color: #f7f7f7;
        }
        .checkout-card {
            background: #fff;
            border: 1px solid #e5e5e5;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            margin-bottom: 30px;
        }
        .checkout-card h3.ps-checkout__heading {
            font-family: 'Playfair Display', 'Georgia', serif;
            font-size: 24px;
            font-weight: 600;
            color: #111;
            margin-bottom: 25px;
            text-transform: uppercase;
            border-bottom: 2px solid #111;
            padding-bottom: 12px;
        }
        
        .auth-input, .auth-select, .auth-textarea {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ccc;
            font-size: 14px;
            color: #111;
            transition: all 0.3s ease;
            background: #fff;
            border-radius: 0;
        }
        .auth-input { height: 48px; }
        .auth-select { 
            height: 48px; 
            -webkit-appearance: none; 
            -moz-appearance: none; 
            appearance: none; 
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="10" height="5"><path fill="%23333" d="M0 0l5 5 5-5z"/></svg>') no-repeat right 15px center #fff; 
            background-size: 10px;
        }
        .auth-textarea { min-height: 100px; resize: vertical; }
        .auth-input:focus, .auth-select:focus, .auth-textarea:focus {
            border-color: #111;
            outline: none;
            box-shadow: none;
        }

        .auth-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
        }

        .order-summary-card {
            background: #f9f9f9;
            border: 1px solid #111;
            padding: 30px;
            color: #111;
        }
        .order-summary-card h3.ps-checkout__heading {
            font-family: 'Playfair Display', 'Georgia', serif;
            font-size: 22px;
            font-weight: 600;
            color: #111;
            margin-bottom: 20px;
            text-transform: uppercase;
            border-bottom: 1px solid #ddd;
            padding-bottom: 12px;
        }
        
        .ps-checkout__row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #ddd;
            font-size: 14px;
        }
        .ps-checkout__row.ps-title {
            font-weight: 600;
            text-transform: uppercase;
            color: #555;
        }
        .ps-checkout__row:last-child {
            border-bottom: none;
        }

        .ps-product__name {
            color: #111 !important;
        }
        
        .radio-styled {
            margin-bottom: 15px;
        }
        .radio-styled label {
            display: flex;
            align-items: center;
            cursor: pointer;
            font-size: 15px;
        }
        .radio-styled input[type="radio"] {
            margin-right: 12px;
            width: 18px;
            height: 18px;
            accent-color: #111;
        }
        
        .btn-black-checkout {
            background-color: #111;
            color: #fff;
            border: none;
            width: 100%;
            height: auto;
            padding: 16px 30px;
            font-weight: 600;
            font-size: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
            text-align: center;
            display: block;
        }
        .btn-black-checkout:hover {
            background-color: #333;
        }
    </style>
</head>

<body>
    <div class="ps-page">
        <?php include('common/header.php'); ?>
        <div class="checkout-page-wrapper">
            <div class="container">
                <ul class="ps-breadcrumb">
                    <li class="ps-breadcrumb__item"><a href="index.html">Home</a></li>
                    <li class="ps-breadcrumb__item active" aria-current="page"> Checkout</li>
                </ul>
                <h3 class="ps-checkout__title" style="font-family: 'Playfair Display', 'Georgia', serif; font-size: 32px; font-weight: 600; color: #111; margin-bottom: 30px; text-transform: uppercase;"> Checkout</h3>
                
                <div class="ps-checkout__content">

                    <form action="<?php echo site_url(); ?>payment.php" method="POST" id="dateForm">
                        <div class="row">
                            <div class="col-12 col-lg-8">
                                <div class="checkout-card">
                                    <h3 class="ps-checkout__heading">Billing details</h3>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="ps-checkout__group mb-4">
                                                <label class="auth-label">Country *</label>
                                                <select class="auth-select" name="cuntry" id="country">
                                                    <option>--Select country--</option>
                                                    <?php
                                                    $contryList = contryList();
                                                    foreach ($contryList as $query) {
                                                        $countryID = $query['pk_id'];
                                                        $countryName = $query['name'];
                                                        $sel = "";

                                                        if ($_SESSION["countryId"] == $countryID) {

                                                            $sel = "selected";
                                                        }
                                                    ?>
                                                        <option <?php echo $sel; ?> value="<?php echo $countryID; ?>"><?php echo $countryName; ?></option>

                                                    <?php } ?>
                                                </select>

                                            </div>
                                        </div>
                                        <div class="col-12 col-md-12">
                                            <div class="ps-checkout__group mb-4">
                                                <label class="auth-label">Contact Name *</label>
                                                <input class="auth-input txtSaveValue" type="text" name="contactPerson" required="" value="<?php echo $contactPerson; ?>">
                                            </div>
                                        </div>


                                        <div class="col-12">
                                            <div class="ps-checkout__group mb-4">
                                                <label class="auth-label">Street address *</label>
                                                <input class="auth-input txtSaveValue mb-3" type="text" name="address" id="address" required="" value="<?php echo $address; ?>">
                                                <input class="auth-input txtSaveValue" type="text" placeholder="Apartment, suite, unit, etc. (optional)" name="address2" value="<?php echo $address2; ?>">
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <div class="ps-checkout__group mb-4">
                                                <label class="auth-label">Town / City *</label>
                                                <?Php
                                                if ($is_cities == true) { ?>
                                                    <select class="auth-select select2" name="city" id="city" required>
                                                        <?php echo getCity(); ?>
                                                    </select>
                                                <?Php    } else {  ?>
                                                    <input type="text" placeholder="Town / City *" name="cityByName" class="auth-input txtSaveValue" value="<?php echo $cityByName; ?>">
                                                <?Php    } ?>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <div class="ps-checkout__group mb-4">
                                                <label class="auth-label">Country </label>
                                                <input class="auth-input" type="text" readonly="" value=" <?php echo $country_name_session; ?>">
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <div class="ps-checkout__group mb-4">
                                                <label class="auth-label">Contact Number *</label>
                                                <input class="auth-input txtSaveValue" type="text" name="contactNo" value="<?php echo $contactNo; ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <div class="ps-checkout__group mb-4">
                                                <label class="auth-label">Contact Email address *</label>
                                                <input class="auth-input txtSaveValue" type="email" name="EmailAddress" value="<?php echo $EmailAddress; ?>" required>
                                            </div>
                                        </div>
                                       
                                      
                                        <div class="col-12">
                                            <div class="ps-checkout__group mb-4">
                                                <label class="auth-label">Order notes (optional)</label>
                                                <textarea class="auth-textarea txtSaveValue" rows="4" placeholder="Notes about your order, e.g. special notes for delivery." name="OrderNote"><?php echo $OrderNote; ?></textarea>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="ps-checkout__group mb-2">
                                                <label class="auth-label">Delivery Method</label>
                                                <div class="radio-styled mt-2">
                                                    <?php echo getDeliveryType(); ?>
                                                </div>
                                            </div>
                                        </div>
                                       
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-lg-4">
                                <div class="order-summary-card">
                                    <h3 class="ps-checkout__heading">Your order</h3>
                                    <div class="ps-checkout__row">
                                        <div class="ps-title">Product</div>
                                        <div class="ps-title">Subtotal</div>
                                    </div>
                                    <?php echo mainCart(); ?>
                                  
                                    
                                    <div class="ps-checkout__row">
                                        <div class="ps-title">Subtotal</div>
                                        <div class="ps-product__price"><span id="subTot" style="font-weight:bold; color:#111;"></span></div>
                                    </div>
                                    <?php if (isset($_SESSION['coupon_display'])) { ?>
                                    <div class="ps-checkout__row">
                                        <div class="ps-title" style="color: #27ae60;">Coupon Discount</div>
                                        <div class="ps-product__price" style="color: #27ae60;"><?php echo currency($_SESSION['coupon_display']); ?></div>
                                    </div>
                                    <?php } ?>
                                    <div class="ps-checkout__row pb-4">
                                        <div class="ps-title"><span class="ps-title" id="DispatchDisplayTitle"></span>
                                        <pre id="shipping_description" style="color:#555; font-family: inherit; margin-bottom: 0px; font-size: 13px; white-space: pre-wrap;"></pre>
                                    </div>
                                        <div class="ps-checkout__checkbox text-right">
                                            <div class="form-check">
                                            <strong id="dispatch_value" style="color:#111;"></strong>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="ps-checkout__row" style="border-top: 1px solid #ddd; padding-top: 20px; font-size: 18px;">
                                        <div class="ps-title" style="color:#111; font-size: 16px;">Total</div>
                                        <div class="ps-product__price"><strong id="TotalAmount" style="color:#111; font-size: 20px;"></strong></div>
                                    </div>
                                    
                                    <div class="ps-checkout__payment mt-4">
                                        <div id="paymentMethod9923" class="radio-styled">
                                            <?php echo getPaymentType(); ?>
                                        </div>
                                        <button type="submit" class="btn-black-checkout" id="button-confirm" name="button-confirm" value="sub">Place order</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php include('common/footer.php'); ?>
    </div>


    <script data-cfasync="false" src="../../cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script>
    <script src="<?php echo site_url(); ?>plugins/jquery.min.js"></script>
    <script src="<?php echo site_url(); ?>plugins/popper.min.js"></script>
    <script src="<?php echo site_url(); ?>plugins/bootstrap4/js/bootstrap.min.js"></script>
    <script src="<?php echo site_url(); ?>plugins/select2/dist/js/select2.full.min.js"></script>
    <script src="<?php echo site_url(); ?>plugins/owl-carousel/owl.carousel.min.js"></script>
    <script src="<?php echo site_url(); ?>plugins/jquery-bar-rating/dist/jquery.barrating.min.js"></script>
    <script src="<?php echo site_url(); ?>plugins/lightGallery/dist/js/lightgallery-all.min.js"></script>
    <script src="<?php echo site_url(); ?>plugins/slick/slick/slick.min.js"></script>
    <script src="<?php echo site_url(); ?>plugins/noUiSlider/nouislider.min.js"></script>
    <!-- custom code-->
    <script src="<?php echo site_url(); ?>js/main.js"></script>
</body>


</html>
<script>
    $("input").change(function() {
        var key = $(this).attr('name');
        var value = $(this).val();
        $.ajax({

            type: 'POST',
            url: 'process/checkoutDataSaveSession.php',
            data: {
                key: key,
                value: value
            },
            success: function(response) {


            }
        });



    });

    function Calculation() {

        $.ajax({
            url: 'process/calculationProcess.php',
            type: 'POST',
            data: {},

            success: function(result) {
                var jsonobj = JSON.parse(result);
                $('#subTot').text(jsonobj.SubTotal);
                $('#subTotWithCoupn').text(jsonobj.SubWithCupon);
                $('#deliveryCharge').text(jsonobj.deliveryCharge);
                $('#TotalAmount').text(jsonobj.TotalAmount);
                $('#DispatchDisplayTitle').text(jsonobj.DispatchDisplayTitle);
                $('#dispatch_value').text(jsonobj.deliveryCharge);
                $('#shipping_description').text(jsonobj.shipping_description);

            }

        });
    }
    Calculation();

    function shippingContrySelect() {
        $("#country").change(function() {

            var contryId = this.value;

            $.ajax({
                url: 'process/country_select_process.php',
                type: 'POST',
                data: {
                    contryId: contryId
                },

                success: function(result) {
                    location.reload();
                }

            });



        });
    }
    shippingContrySelect();

    $(document).ready(function() {
        $("#city").change(function() {

            var city = $("#city option:selected").val();
            $.ajax({

                type: 'POST',
                url: 'process/deliveryCityAdd.php',
                data: {
                    city: city
                },
                success: function(response) {

                    Calculation();
                }
            });


        });
    });

    $(document).ready(function() {

        var deliverType = $('input[name="delivery_Type"]:checked').val();;

        $(".delivery_Type").change(function() {

            var deliverType = $('input[name="delivery_Type"]:checked').val();;


            $.ajax({
                url: "process/fetch_paymentType.php",
                method: "POST",
                data: {
                    deliverType: deliverType
                },
                dataType: "text",
                success: function(data) {
                    $('#paymentMethod9923').html(data);
                    Calculation();
                }
            });

            $.ajax({

                type: 'POST',
                url: 'process/deliveryTypeAdd.php',
                data: {
                    delivery_Type: deliverType
                },
                success: function(response) {

                    Calculation();


                }
            });


        });
    });

    $(document).ready(function() {
        $(document).on('change', '.payment_Type', function() {

            var payment_Type = $('input[name="payment_Type"]:checked').val();;


            $.ajax({

                type: 'POST',
                url: 'process/paymentTypeAdd.php',
                data: {
                    payment_Type: payment_Type
                },
                success: function(response) {


                }
            });


        });
    });
</script>