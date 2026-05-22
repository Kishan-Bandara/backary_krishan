<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');
function filter($var)
{

  return preg_replace('[0-9]', ' ', $var);
}




if (empty($_SESSION['LoginStatus'])) {


  //echo "<script type='text/javascript'>window.location.href = 'login.php';</script>";
}

/*$delivery_rate = $_SESSION["deliveryRate"];
$coupon_type   = $_SESSION['coupon_type'];
$coupon_rate   = $_SESSION['coupon_rate'];
$delivery_city = $_SESSION['coupon_rate'];*/
(isset($_SESSION["deliveryRate"])) ? $delivery_rate = $_SESSION["deliveryRate"] : $delivery_rate = 0.00;
(isset($_SESSION["coupon_type"])) ? $coupon_type = $_SESSION["coupon_type"] : $coupon_type = "";
(isset($_SESSION["coupon_rate"])) ? $coupon_rate = $_SESSION["coupon_rate"] : $coupon_rate = 0.00;
(isset($_SESSION["cityName"])) ? $delivery_city = $_SESSION["cityName"] : $delivery_city = "";
(isset($_SESSION["deliveryId"])) ? $delivery_mode = $_SESSION["deliveryId"] : $delivery_mode = 0;
(isset($_SESSION["delivery_area_price"])) ? $delivery_min_value = $_SESSION["delivery_area_price"] : $delivery_min_value = 0;
(isset($_SESSION["paymentId"])) ? $payment_type = $_SESSION["paymentId"] : $payment_type = 0;
(isset($_SESSION["Loginuserid"])) ? $customer_id = $_SESSION["Loginuserid"] : $customer_id = 0;
$order_confirm = false;

$db = new Database();


if (!empty($session_user_status)) {

  $order_confirm = true;

  $query_get_customer = $db->getRow('SELECT * FROM customer WHERE customer_id = ?', [$session_user_id]);
  $customer_email = $query_get_customer['customer_email'];
  $customer_name = $query_get_customer['customer_name'];
  $customer_number = $query_get_customer['customer_mobile'];
} else {

  $order_confirm = false;
  $customer_email = "";
  $customer_name = "";
  $customer_number = "";
}



function getCity()
{
  $city_id = $_SESSION["cityId"];
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

function getDeliveryMode()
{
  $delivery_mode_id = $_SESSION["deliveryId"];
  $user_session = $_SESSION['LoginStatus'];

  $mode_output = '';

  if (!empty($user_session)) {

    $db = new Database();
    $modequery = $db->getRows('SELECT * FROM delivery_master');
    $modedata = $modequery;



    foreach ($modedata as $modequery) {
      $sel = "";
      $modeid = $modequery['id'];
      if ($modeid == $delivery_mode_id) {

        $sel = "checked";
      }




      $mode_output .= '<div class="radio"> <label> <input type="radio" ' . $sel . ' name="Delivery_Type" value="' . $modequery['id'] . '">' . $modequery['method'] . ' </label></div>';
    }
  } else {



    $db = new Database();
    $modequery = $db->getRows('SELECT * FROM delivery_master WHERE id = ?', [3]);
    $modedata = $modequery;



    foreach ($modedata as $modequery) {
      $sel = "";
      $modeid = $modequery['id'];
      if ($modeid == $delivery_mode_id) {

        $sel = "checked";
      }




      $mode_output .= '<div class="radio"> <label> <input type="radio" ' . $sel . ' name="Delivery_Type" value="' . $modequery['id'] . '">' . $modequery['method'] . ' </label></div>';
    }
  }

  return $mode_output;
}

function getPaymentType()
{
  $payment_type_id = $_SESSION["paymentId"];

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
    $mode_output .= '<div class="radio"> <label> <input type="radio" ' . $sel . ' name="payment_Type" value="' . $payment_type_query['id'] . '">' . $payment_type_query['type'] .    '     <img src="' . $payment_type_query['img'] . '" width="40px;"> </label></div>';
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



        echo  '<tr>
        <td class="col-sm-8 col-md-6" style="width:auto;">
          <div class="media">
            <a class="thumbnail pull-left" style=" margin-right: 10px;" href="#"> <img class="media-object" src="' . $productimage . '" style="width: 72px; height: 72px;"> </a>
            <div class="media-body">
              <h4 class="media-heading"><a href="#">' . $session_item_name . '</a></h4>
              
              <span>Status: </span><span class="text-success"><strong>In Stock</strong></span>
            </div>
          </div>
        </td>
        <td class="col-sm-1 col-md-1" style="text-align: center;width:auto;">
        <div class="def-number-input number-input safari_only">
        <button class="minus" onclick="this.parentNode.querySelector("input[type=number]").stepDown()"><i class="icon-minus"></i></button>
        <input type="number" class="quantity qqt" min="0" name="" value="' . $qtydecimal . '"  data-item-code-qty="' . $itemCode . '" />
        <button class="plus" onclick="this.parentNode.querySelector("input[type=number]").stepUp()"><i class="icon-plus"></i></button>
    </div>
       
        </td>
        <td class="col-sm-1 col-md-1 text-center" style="width:auto;">
        <strong>' . currency($afterDiscountAmount) . '</strong><br>
        <span class="offerprice">
                                You save:<br>
                                <span class="price" style="font-size:12px;color: gray;">
                                    <span class="price">' . currency($saveAmount) . '</span>                                </span>
                                <span class="yousave" style="color:red;font-size: 12px;">
                                    (' . number_format($item_discount, 2) . '% off)                                </span>
                            </span>
        </td>
        <td class="col-sm-1 col-md-1 text-center" style="width:auto;"><strong>' . currency($itemTotalDecimal) . '</strong></td>
        <td class="col-sm-1 col-md-1" style="width:auto;">
        <a href="#" class="remove_item" data-item-code-qty="' . $itemCode . '"><span class="glyphicon glyphicon-remove">X</span></a></td>
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
<html lang="en">


<head>
  <?php include('common/styles.php'); ?>
  <style>
    .ps-shopping {
      padding: 60px 0 80px;
      background: #fafafa;
    }
    .ps-shopping .ps-breadcrumb {
      margin-bottom: 25px;
      padding: 0;
      list-style: none;
      font-size: 13px;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: #6b6b6b;
    }
    .ps-shopping .ps-breadcrumb li {
      display: inline;
    }
    .ps-shopping .ps-breadcrumb li + li::before {
      content: "/";
      margin: 0 10px;
      color: #d1d1d1;
    }
    .ps-shopping .ps-breadcrumb a {
      color: #6b6b6b;
      text-decoration: none;
    }
    .ps-shopping .ps-breadcrumb a:hover { color: #111; }

    .ps-shopping__title {
      font-family: 'Playfair Display', 'Georgia', serif;
      font-size: 38px;
      font-weight: 400;
      letter-spacing: 0.04em;
      text-transform: uppercase;
      color: #111;
      margin: 0 0 35px;
    }

    .cart-card {
      background: #fff;
      border: 1px solid #e5e5e5;
      border-radius: 8px;
      padding: 30px;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.04);
    }

    .cart-table {
      width: 100%;
      border-collapse: collapse;
    }
    .cart-table thead th {
      font-size: 12px;
      font-weight: 700;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: #6b6b6b;
      border-bottom: 2px solid #111;
      padding: 14px 12px;
      text-align: left;
    }
    .cart-table thead th.text-end { text-align: right; }
    .cart-table thead th.text-center { text-align: center; }
    .cart-table tbody tr {
      border-bottom: 1px solid #e5e5e5;
      transition: background 0.2s ease;
    }
    .cart-table tbody tr:hover { background: #fafafa; }
    .cart-table tbody td {
      padding: 22px 12px;
      vertical-align: middle;
      font-size: 15px;
      color: #111;
    }
    .cart-product {
      display: flex;
      align-items: center;
      gap: 18px;
    }
    .cart-product__image {
      width: 90px;
      height: 90px;
      object-fit: cover;
      border-radius: 6px;
      border: 1px solid #e5e5e5;
      background: #fafafa;
    }
    .cart-product__info { flex: 1; min-width: 0; }
    .cart-product__name {
      font-size: 16px;
      font-weight: 600;
      color: #111;
      margin: 0 0 6px;
      text-decoration: none;
      display: block;
    }
    .cart-product__name:hover { color: #000; text-decoration: underline; }
    .cart-product__status {
      font-size: 12px;
      color: #1f8a3a;
      letter-spacing: 0.05em;
      text-transform: uppercase;
      font-weight: 600;
    }

    .cart-qty-input {
      width: 80px;
      height: 42px;
      border: 1px solid #d1d1d1;
      border-radius: 4px;
      background: #fff;
      text-align: center;
      font-size: 15px;
      font-weight: 600;
      color: #111;
      padding: 0 8px;
    }
    .cart-qty-input:focus { outline: none; border-color: #111; }

    .cart-price { font-weight: 700; font-size: 16px; }
    .cart-price__save {
      display: block;
      margin-top: 6px;
      font-size: 11px;
      color: #6b6b6b;
      font-weight: 500;
      letter-spacing: 0.04em;
    }
    .cart-price__save .save-pct { color: #b42318; font-weight: 600; }

    .cart-remove-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background: #fafafa;
      border: 1px solid #e5e5e5;
      color: #6b6b6b;
      font-size: 14px;
      transition: all 0.2s ease;
      text-decoration: none;
    }
    .cart-remove-btn:hover {
      background: #b42318;
      color: #fff;
      border-color: #b42318;
      text-decoration: none;
    }

    .cart-empty {
      text-align: center;
      padding: 60px 20px;
      color: #6b6b6b;
    }
    .cart-empty__icon {
      font-size: 60px;
      color: #d1d1d1;
      margin-bottom: 20px;
    }
    .cart-empty__title {
      font-family: 'Playfair Display', 'Georgia', serif;
      font-size: 26px;
      color: #111;
      margin: 0 0 10px;
    }
    .cart-empty__shop-btn {
      display: inline-block;
      margin-top: 22px;
      padding: 14px 40px;
      background: #111;
      color: #fff;
      font-size: 13px;
      font-weight: 700;
      letter-spacing: 0.15em;
      text-transform: uppercase;
      text-decoration: none;
      border-radius: 0;
      transition: background 0.2s ease;
    }
    .cart-empty__shop-btn:hover { background: #333; color: #fff; text-decoration: none; }

    .cart-summary {
      background: #111;
      color: #fff;
      padding: 30px;
      border-radius: 8px;
      position: sticky;
      top: 100px;
    }
    .cart-summary__title {
      font-family: 'Playfair Display', 'Georgia', serif;
      font-size: 22px;
      letter-spacing: 0.05em;
      text-transform: uppercase;
      margin: 0 0 25px;
      padding-bottom: 18px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.18);
      color: white;
    }
    .cart-summary__row {
      display: flex;
      justify-content: space-between;
      padding: 8px 0;
      font-size: 14px;
      color: rgba(255, 255, 255, 0.85);
    }
    .cart-summary__row--total {
      margin-top: 18px;
      padding-top: 18px;
      border-top: 1px solid rgba(255, 255, 255, 0.18);
      font-size: 18px;
      font-weight: 700;
      color: #fff;
    }
    .cart-summary__coupon {
      margin-top: 22px;
      padding-top: 22px;
      border-top: 1px solid rgba(255, 255, 255, 0.18);
    }
    .cart-summary__coupon label {
      font-size: 12px;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: rgba(255, 255, 255, 0.7);
      display: block;
      margin-bottom: 10px;
    }
    .cart-summary__coupon-row {
      display: flex;
      gap: 8px;
    }
    .cart-summary__coupon-input {
      flex: 1;
      height: 42px;
      border: 1px solid rgba(255, 255, 255, 0.3);
      background: rgba(255, 255, 255, 0.08);
      color: #fff;
      padding: 0 12px;
      font-size: 14px;
      border-radius: 4px;
    }
    .cart-summary__coupon-input::placeholder { color: rgba(255, 255, 255, 0.5); }
    .cart-summary__coupon-input:focus { outline: none; border-color: #fff; background: rgba(255, 255, 255, 0.14); }
    .cart-summary__coupon-btn {
      padding: 0 18px;
      height: 42px;
      background: #fff;
      color: #111;
      border: none;
      border-radius: 4px;
      font-size: 12px;
      font-weight: 700;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    .cart-summary__coupon-btn:hover { background: #e5e5e5; }
    .cart-summary__coupon-applied {
      font-size: 13px;
      color: #fff;
      padding: 12px 14px;
      background: rgba(255, 255, 255, 0.08);
      border-radius: 4px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .cart-summary__coupon-applied a {
      color: #ff8a8a;
      font-size: 12px;
      text-decoration: underline;
    }
    .cart-summary__checkout {
      display: block;
      width: 100%;
      margin-top: 25px;
      padding: 16px;
      background: #fff;
      color: #111;
      text-align: center;
      font-size: 14px;
      font-weight: 700;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      text-decoration: none;
      border-radius: 0;
      transition: all 0.2s ease;
      border: 2px solid #fff;
    }
    .cart-summary__checkout:hover {
      background: transparent;
      color: #fff;
      text-decoration: none;
    }
    .cart-summary__continue {
      display: block;
      text-align: center;
      margin-top: 14px;
      color: rgba(255, 255, 255, 0.7);
      font-size: 12px;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      text-decoration: underline;
    }
    .cart-summary__continue:hover { color: #fff; }

    @media (max-width: 991px) {
      .cart-summary { position: static; margin-top: 25px; }
      .cart-table thead { display: none; }
      .cart-table tbody tr {
        display: block;
        padding: 18px 0;
      }
      .cart-table tbody td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        text-align: right;
      }
      .cart-table tbody td::before {
        content: attr(data-label);
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #6b6b6b;
      }
      .cart-table tbody td:first-child::before { content: none; }
    }
  </style>
</head>

<body>
  <div class="ps-page">
    <?php include('common/header.php'); ?>
    <div class="ps-shopping">
      <div class="container">
        <ul class="ps-breadcrumb">
          <li class="ps-breadcrumb__item"><a href="index.php">Home</a></li>
          <li class="ps-breadcrumb__item active" aria-current="page">Shopping cart</li>
        </ul>
        <h3 class="ps-shopping__title">Shopping Cart</h3>

        <?php
        $cartItemCount = 0;
        $cartSubtotal = 0;
        if (!empty($_SESSION['SBCScart'])) {
          foreach ($_SESSION['SBCScart'] as $cItem) {
            if ($cItem['quantity'] != 0) {
              $cartItemCount++;
              $cPrice = (float) str_replace(",", ".", $cItem['price']);
              $cQty = (float) str_replace(",", ".", $cItem['quantity']);
              $cDisc = (float) str_replace(",", ".", $cItem['item_discount']);
              $cSave = ($cPrice * ($cDisc * $cQty)) / 100;
              $cartSubtotal += ($cPrice * $cQty) - $cSave;
            }
          }
        }
        ?>

        <div class="ps-shopping__content">
          <div class="row">
            <div class="col-12 col-lg-8">
              <div class="cart-card">
                <?php if ($cartItemCount > 0) { ?>
                <div class="ps-shopping__table_1 table-responsive">
                  <table class="cart-table">
                    <thead>
                      <tr>
                        <th>Product</th>
                        <th class="text-center">Quantity</th>
                        <th class="text-center">Price</th>
                        <th class="text-end" style="text-align:right;">Total</th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody>

                    <?php
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


                    ?>
                      <tr>
                        <td data-label="Product">
                          <div class="cart-product">
                            <img class="cart-product__image" src="<?php echo $productimage; ?>" alt="<?php echo htmlspecialchars($session_item_name); ?>">
                            <div class="cart-product__info">
                              <a class="cart-product__name" href="#"><?php echo $session_item_name; ?></a>
                              <span class="cart-product__status">In Stock</span>
                            </div>
                          </div>
                        </td>
                        <td data-label="Quantity" class="text-center" style="text-align:center;">
                          <input type="number" class="cart-qty-input qqt" min="0" value="<?php echo $qtydecimal; ?>" data-item-code-qty="<?php echo $itemCode; ?>" />
                        </td>
                        <td data-label="Price" class="text-center" style="text-align:center;">
                          <span class="cart-price"><?php echo currency($afterDiscountAmount); ?></span>
                          <?php if ($item_discount > 0) { ?>
                          <span class="cart-price__save">
                            Save <?php echo currency($saveAmount); ?>
                            <span class="save-pct">(<?php echo number_format($item_discount, 0); ?>% off)</span>
                          </span>
                          <?php } ?>
                        </td>
                        <td data-label="Total" class="text-end" style="text-align:right;">
                          <strong class="cart-price"><?php echo currency($itemTotalDecimal); ?></strong>
                        </td>
                        <td style="text-align:right;">
                          <a href="#" class="cart-remove-btn remove_item" data-item-code-qty="<?php echo $itemCode; ?>" title="Remove">&times;</a>
                        </td>
                      </tr>


                    <?php

                          // Total
                          $total += $totaldecimal;
                        }
                        $linenumber++;
                      }
                    }

                    ?>
                  </tbody>
                </table>
                </div>
                <?php } else { ?>
                <div class="cart-empty">
                  <div class="cart-empty__icon"><i class="icon-bag"></i> &#128722;</div>
                  <h4 class="cart-empty__title">Your cart is empty</h4>
                  <p>Looks like you haven't added anything to your cart yet.</p>
                  <a href="<?php echo site_url(); ?>search.php" class="cart-empty__shop-btn">Continue Shopping</a>
                </div>
                <?php } ?>
              </div>
            </div>

            <?php if ($cartItemCount > 0) { ?>
            <div class="col-12 col-lg-4">
              <div class="cart-summary">
                <h4 class="cart-summary__title">Order Summary</h4>
                <div class="cart-summary__row">
                  <span>Items (<?php echo $cartItemCount; ?>)</span>
                  <span><?php echo currency($cartSubtotal); ?></span>
                </div>
                <div class="cart-summary__row">
                  <span>Delivery</span>
                  <span>Calculated at checkout</span>
                </div>
                <div class="cart-summary__row cart-summary__row--total">
                  <span>Subtotal</span>
                  <span><?php echo currency($cartSubtotal); ?></span>
                </div>

                <div class="cart-summary__coupon">
                  <?php if (empty($_SESSION['coupon_id'])) { ?>
                    <label for="input-coupon">Have a coupon?</label>
                    <div class="cart-summary__coupon-row">
                      <input class="cart-summary__coupon-input" name="input-coupon" id="input-coupon" type="text" placeholder="Coupon code">
                      <button class="cart-summary__coupon-btn" type="button" id="btn-coupon">Apply</button>
                    </div>
                  <?php } else { ?>
                    <div class="cart-summary__coupon-applied">
                      <span><?php echo $_SESSION['coupon_message']; ?></span>
                      <a href="#" id="remove_coupon">Remove</a>
                    </div>
                  <?php } ?>
                </div>

                <a href="checkout.php" class="cart-summary__checkout">Proceed to Checkout</a>
                <a href="<?php echo site_url(); ?>search.php" class="cart-summary__continue">Continue Shopping</a>
              </div>
            </div>
            <?php } ?>
          </div>
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
  <script src="<?php echo site_url(); ?>js/numberInputs.js"></script>
</body>


</html>

<script>
  function calculateTot() {
    var grandTotal = 0;
    var coupon_discount_value = 0.00;
    var tot = 0,
      vat_item_tot = 0,
      discount_value = 0.00;
    var deliveryRate = <?php echo json_encode($delivery_rate); ?>;
    var delivery_min_value = <?php echo json_encode($delivery_min_value); ?>;
    var discount = 5.00;
    var total = 0;
    var coupon_rate = <?php echo json_encode($coupon_rate); ?>;
    var coupon_type = <?php echo json_encode($coupon_type); ?>;
    var item_discount_value = 0.00;
    $("#myDatatable tbody .custom-tr").each(function() {
      discount_value = parseFloat($(this).find("td input[id='unit_qty']").val()) * parseFloat($(this).find("td input[id='unit_discount']").val());
      item_discount_value = (parseFloat($(this).find("td input[id='unit_price']").val()) * discount_value) / 100;
      $(this).find(".itmTot").text((parseFloat($(this).find("td input[id='unit_price']").val()) * parseFloat($(this).find("td input[id='unit_qty']").val()) - item_discount_value).toFixed(2));

      tot += (parseFloat($(this).find("td input[id='unit_price']").val()) * parseFloat($(this).find("td input[id='unit_qty']").val()));
      $(this).find(".itmTot").each(function() {
        total += parseInt($(this).text());
      });


      if (coupon_type == "PCT") {

        coupon_discount_value = (parseFloat(total) * parseFloat(coupon_rate) / 100);
      } else if (coupon_type == "SUM") {

        coupon_discount_value = parseFloat(coupon_rate);
      } else {

        coupon_discount_value = 0.00;

      }

      deliveryRate = (deliveryRate) ? deliveryRate : '0.00';

      grandTotal = (total >= delivery_min_value) ? grandTotal = (parseFloat(total) - parseFloat(coupon_discount_value)) : grandTotal = (parseFloat(total) - parseFloat(coupon_discount_value) + parseFloat(deliveryRate));



    });

    $("#sub_tot").text(total.toFixed(2));
    $('#cartItemTot').text(tot.toFixed(2));
    $('#Grand_tot').text(grandTotal.toFixed(2));

    if (total > delivery_min_value) {

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


  $("#btn-coupon").click(function() {

    var coupon_code = $("#input-coupon").val();

    $.ajax({
      url: 'process/couponcodeProcess.php',
      type: 'POST',
      data: {
        coupon_code: coupon_code
      },

      success: function(result) {
        var jsonobj = JSON.parse(result);

        $("#couponMessage").text(jsonobj.message);
        $('#popUp').modal('show');
        if (jsonobj.status == true) {
          calculateTot();
          location.reload();
        }



      }

    });


  });

  $("#remove_coupon").click(function() {

    $.ajax({
      url: 'process/coupon_remove_process.php',
      success: function(response) {
        alert(response);
        Calculation();
        location.reload();


      }
    });

  });


  $(".remove_item").click(function() {

    var itmQty = 0;
    var item_code = $(this).attr('data-item-code-qty');

    $.ajax({
      url: 'process/main_cart_qty_update.php',
      type: 'POST',
      data: {
        item_id: item_code,
        quantity: itmQty
      },
      success: function(result) {


        Calculation();
        location.reload();



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


      }

    });
  }
  Calculation();



  function qtyUpdate() {
    $('.qqt').change(function() {
      var itmQty = $(this).val();
      var item_code = $(this).attr('data-item-code-qty');

      if(itmQty === "" || itmQty < 0) return;

      $.ajax({
        url: 'process/main_cart_qty_update.php',
        type: 'POST',
        data: {
          item_id: item_code,
          quantity: itmQty
        },
        success: function(result) {
          Calculation();
          location.reload();
        }
      });
    });
  }

  qtyUpdate();

  $('.qqtremv').click(function() {

    var itmQty = 0;
    var item_code = $(this).attr('data-item-code-rmv');

    $.ajax({
      url: 'process/main_cart_qty_update.php',
      type: 'POST',
      data: {
        item_id: item_code,
        quantity: itmQty
      },
      success: function(result) {
        Calculation();
      }

    });

  });
</script>