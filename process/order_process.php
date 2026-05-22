<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

session_start();
include('../include/database.php');
include('../include/check_login.php');
$db = new database();

date_default_timezone_set("Asia/Colombo");

function filter($var)
{
  return preg_replace('/ [^a-za-z0-9\s@.]/', ' ', $var);
}

$nowDate = date("Y-m-d");
$nowTime = date("h:i:s");
$nowDateTime = date("Y-m-d h:i:s");
$emailSent = false;

//location name : online store : 
$invoice_location = 1;
$getLastid = 0;
$diliveryvalueWithCityRate = 0;
      
?>

<?php
if (isset($_POST["order_confirm"])) {

  $transaction_approve = 5;
  $invoice_h_status = false;
  $transactionType = 2;

  $customer_id = $session_user_id;
  $emailSend = true;

  // location details
  (isset($_SESSION["is_Cities"])) ? $is_Cities = $_SESSION["is_Cities"] : $is_Cities = "";
  (isset($_SESSION["contactPerson"])) ? $contactPerson = $_SESSION["contactPerson"] : $contactPerson = "";
  (isset($_SESSION["address"])) ? $address = $_SESSION["address"] : $address = "";
  (isset($_SESSION["address2"])) ? $address2 = $_SESSION["address2"] : $address2 = "";
  (isset($_SESSION["countryName"])) ? $countryName = $_SESSION["countryName"] : $countryName = "";
  (isset($_SESSION["city"])) ? $city = $_SESSION["city"] : $city = "";

  $is_City   = $is_Cities;
  $contactPerson   = $contactPerson;
  $address   = $address . " " . $address2;
  $Country   = $countryName;
  $location_id   = $city;


  (isset($_SESSION["OrderNote"])) ? $OrderNote = $_SESSION["OrderNote"] : $OrderNote = "";
  (isset($_SESSION["cityByName"])) ? $cityByName = $_SESSION["cityByName"] : $cityByName = "";
  (isset($_SESSION["coupon_type"])) ? $coupon_type = $_SESSION["coupon_type"] : $cityByName = "";
  (isset($_SESSION["coupon_rate"])) ? $coupon_rate = $_SESSION["coupon_rate"] : $coupon_rate = "";
  (isset($_SESSION["coupon_value"])) ? $coupon_value = $_SESSION["coupon_value"] : $coupon_value = "";
  (isset($_SESSION["addShipingCharges"])) ? $addShipingCharges = $_SESSION["addShipingCharges"] : $addShipingCharges = "";

  (isset($_SESSION["city"])) ? $delivery_city = $_SESSION["city"] : $delivery_city = "";
  (isset($_SESSION["delivery_Type"])) ? $OrderNote = $_SESSION["delivery_Type"] : $OrderNote = "";
  (isset($_SESSION["cityRate"])) ? $cityRate = $_SESSION["cityRate"] : $cityRate = "";
  (isset($_SESSION["CityPerKgRate"])) ? $CityPerKgRate = $_SESSION["CityPerKgRate"] : $CityPerKgRate = "";
  (isset($_SESSION["deliveryId"])) ? $delivery_mode_id = $_SESSION["deliveryId"] : $delivery_mode_id = "";
  (isset($_SESSION["SetRateMinOrder"])) ? $Delivery_rate_min_order_value = $_SESSION["SetRateMinOrder"] : $Delivery_rate_min_order_value = "";
  (isset($_SESSION["contactNo"])) ? $contactNo = $_SESSION["contactNo"] : $contactNo = "";
  (isset($_SESSION["EmailAddress"])) ? $DeliveryEmailAddress = $_SESSION["EmailAddress"] : $DeliveryEmailAddress = "";
  (isset($_SESSION['is_Online_Payment_done'])) ? $isOnlinePaymentdone = $_SESSION["is_Online_Payment_done"] : $isOnlinePaymentdone = false;
  (isset($_SESSION['online_pay_amount'])) ? $online_pay_amount = $_SESSION["online_pay_amount"] : $online_pay_amount = 0;
      //payment method
  $payment_type_id = $_SESSION["paymentId"];
  (isset($_SESSION["paymentName"])) ? $paymentName = $_SESSION["paymentName"] : $paymentName = "";
  
   
    
      $query = $db->getRow('SELECT * FROM payment_method WHERE id = ?', [$payment_type_id]);
        $processOrderWithoutPayment = false;
    if ($query["orderProcess"] == 1) {
      $processOrderWithoutPayment = true;
    }
  
  $payment_type_name = "";
  //general settings
  $query_general_settings = $db->getRow('SELECT * FROM general_settings LIMIT 1');

  $SiteName = $query_general_settings['SiteName'];
  $logo = site_url() . $query_general_settings['logo'];
  $system_email = $query_general_settings['system_email'];


  //store settings
  $query_store_settings = $db->getRow('SELECT * FROM location_master LIMIT 1');

  $storeAdminEmail  = $query_store_settings['email'];
  $storeAdminContactNo  = $query_store_settings['phone_no'];
  //base Currecy
  $query_base_currency = $db->getRow('SELECT * FROM currency WHERE primary_store_currency = ?', [1]);
  $System_base_currency = $query_base_currency['currency'];




  $delivery_min_value = 0;
  $customer_register = true;
  $order_process = true;


  //coupon code details

  if (!empty($_SESSION['coupon_id'])) {
    $coupon_code_id = $_SESSION['coupon_id'];
    $coupon_code = $_SESSION['coupon_code'];
    $coupon_code_type = $_SESSION['coupon_type'];
    $coupon_code_rate = $_SESSION['coupon_rate'];

    $query_check_coupon = $db->getRow('SELECT * FROM coupon_codes WHERE code = ?', [$coupon_code]);

    $coupon_code_limit = $query_check_coupon['limited'];
    $new_coupon_code_limit = ($coupon_code_limit - 1);
    $coupon_value = $coupon_value;
  } else {
    $coupon_code_id = 0;
    $coupon_code = "";
    $coupon_code_type = "";
    $coupon_code_rate = "";
    $coupon_value = 0.00;
    $new_coupon_code_limit = 0;
  }
  //coupon code check

  if ($customer_register == true) {
    $delivery_holder = $_SESSION["contactPerson"];
    $delivery_address = $address;
    $delivery_contact_no = $contactNo;
    $delivery_time = "";
    $delivery_date = "";
    $OrderNote;

  
  } else {
    // cusomter registration failed
  }


  if ($order_process == true) {
    //parana id eka search karala aluth id ekak hadagannawa.
    $db = new Database();
    $getpid = $db->getRow('SELECT max(invoice_h_id) as invoice_h_id FROM invoice_hedder');
    $randomNo = rand(100, 999);

    $oldpid = $getpid['invoice_h_id'];
    if ($getpid > 0) {

      $newpid =  $oldpid + 1;
    }

    // product code ekak hadagannawa
    $refaranceCode = str_pad($newpid, 5, '0', STR_PAD_LEFT);
    $refaranceCode = "INV" . $refaranceCode;

    if (!empty($_SESSION['SBCScart'])) {

      // Equal total to 0
      $total = 0;
      // For finding session elements line number
      $linenumber = 0;
      $i = 0;
      $Subtotal = 0;
      $totalWeight = 0;
      // Run loop for cart array 
      foreach ($_SESSION['SBCScart'] as $SBCSitem) {
        $i = $i + 1;

        if ($SBCSitem['quantity'] != 0) {

          $session_item_id = str_replace(",", ".", $SBCSitem['item_id']);

          $pricedecimal = str_replace(",", ".", $SBCSitem['price']);
          $qtydecimal = str_replace(",", ".", $SBCSitem['quantity']);
          $get_item_discount = str_replace(",", ".", $SBCSitem['item_discount']);

          $pricedecimal = (float) $pricedecimal;
          $qtydecimal = (float) $qtydecimal;
          $get_item_discount = $get_item_discount;

          $totaldecimal = $pricedecimal * $qtydecimal;

          $get_item_discount = (float) $get_item_discount;
          $discount_value = ($pricedecimal * ($get_item_discount * $qtydecimal)) / 100;
          $item_grand_total = ($totaldecimal - $discount_value);
          $totaldecimal = $item_grand_total;
          $getItemWeight = 0;
          // Total
          $Subtotal += $totaldecimal;
          $totalWeight += $getItemWeight;
        }
        $linenumber++;
      }
      $total;

      //coupon code check

      if (!empty($coupon_value)) {

        $coupon_value = $coupon_value;
      } else {
        $coupon_value = 0.00;
      }
      $SubTotalwithCouponCode = $Subtotal - $coupon_value;

      if ($SubTotalwithCouponCode > $Delivery_rate_min_order_value) {

        $rateForKg = $totalWeight * $CityPerKgRate;
        $diliveryvalueWithCityRate = $cityRate;
      }
      if ($addShipingCharges != 1) {

        $diliveryvalueWithCityRate = 0;
      }

      $TotalAmount = $SubTotalwithCouponCode + $diliveryvalueWithCityRate;
      //grand Total of the invoice
      
      $invoice_grand_total = $TotalAmount;


    
     try {

        $insert_invoice_h = $db->insertRow('INSERT INTO invoice_hedder (invoice_h_code,invoice_h_customer_id,invoice_h_date,invoice_h_location,
                     	invoice_h_delivery_city,invoice_h_delivery_cost,invoice_h_delivery_mode,invoice_h_pay_type,invoice_h_coupun_code,
                     	invoice_h_coupon_type,invoice_h_coupon_rate,invoice_h_coupon_value,invoice_h_net_value,invoice_h_gross_value,
                     	invoice_h_order_note,invoice_h_delivery_name,invoice_h_delivery_address,invoice_h_delivery_contact_no,invoice_h_delivery_date,
                     	invoice_h_delivery_time,invoice_h_status,invoice_h_datetime,delivery_city_name)VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
          $refaranceCode, $customer_id,
          $nowDate, $invoice_location, $location_id, $diliveryvalueWithCityRate, $delivery_mode_id, $payment_type_id, $coupon_code, $coupon_code_type, $coupon_code_rate,
          $coupon_value, $invoice_grand_total, $Subtotal, $OrderNote, $delivery_holder, $delivery_address, $delivery_contact_no, $delivery_date,
          $delivery_time, "0", $nowDateTime, $cityByName
        ]);


        $query_update_coupon = $db->updateRow('UPDATE coupon_codes SET limited = ? WHERE id = ?', [$new_coupon_code_limit, $coupon_code_id]);

        $queryLastID = $db->getRow('SELECT 	* FROM invoice_hedder ORDER BY invoice_h_id DESC LIMIT 1');
        $getLastid = $queryLastID['invoice_h_id'];
        $_SESSION['order_id'] = $getLastid;

        $invoice_h_status = true;

        /* if ($order_process == true && $isOnlinePaymentdone == true) {
          $insert_payment = $db->insertRow('INSERT INTO customer_balance (invoice_h_id,amount,amountDate,invoice_h_pay_type)VALUES(?,?,?,?)', [$getLastid, $online_pay_amount, $nowDate, $_SESSION["paymentId"]]);
        } */
        
      } catch (Exception $e) {

        echo $e;
      } 
     

      if ($invoice_h_status = true && $getLastid ) {

        // invoice Detials upload
        foreach ($_SESSION['SBCScart'] as $SBCSitem) {
          // Don't list items with 0 qty
          if ($SBCSitem['quantity'] != 0) {

            $session_item_id = str_replace(",", ".", $SBCSitem['item_id']);


            // We calculate total values with decimals
            $pricedecimal = str_replace(",", ".", $SBCSitem['price']);
            $qtydecimal = str_replace(",", ".", $SBCSitem['quantity']);
            $get_item_discount = str_replace(",", ".", $SBCSitem['item_discount']);

            $pricedecimal = (float) $pricedecimal;
            $qtydecimal = (float) $qtydecimal;
            $get_item_discount = (float) $get_item_discount;

            $discount_value = ($pricedecimal * ($get_item_discount * $qtydecimal)) / 100;

            $totaldecimal = ($pricedecimal * $qtydecimal);

            $item_grand_total = ($totaldecimal - $discount_value);


            try {

              // insert invoice Details
              $insertinvoice_d = $db->insertRow('INSERT INTO invoice_details (invoice_h_id,invoice_d_item_id,invoice_d_qty,invoice_d_balance,
							invoice_d_item_price,invoice_d_discount_value,invoice_d_discount_total,invoice_d_item_total) VALUES(?,?,?,?,?,?,?,?)', [
                $getLastid,
                $session_item_id, $qtydecimal, 0.00, $pricedecimal, $get_item_discount, $discount_value, $item_grand_total
              ]);

              $message = "Order Confirom";
              $_SESSION['SBCScart'] = "";
            } catch (Exception $e) {

              $message = '$insertType."<br>" . $e->getMessage()';
            }
          }
        }
      }


      if ($emailSend == true) {

        if ($invoice_h_status == true) {

          $email_order_code = $queryLastID['invoice_h_code'];
          $email_customer_id = $queryLastID['invoice_h_customer_id'];
          $email_order_date = $queryLastID['invoice_h_date'];
          $email_order_total = $queryLastID['invoice_h_gross_value'];
          $email_delevery_name = $queryLastID['invoice_h_delivery_name'];
          $email_delevery_address = $queryLastID['invoice_h_delivery_address'];
          $system_Invoice_url = site_url() . "admin/invoice.php?id=" . $getLastid;
          $siteUrl = site_url();
          define("DEMO", false);
          $template_file = "../emails/OrderPlacedEmailForAdmin.php";
          $email_to = $storeAdminEmail;
          $subject = $SiteName . " New Order";

          $swap_var = array(
            "{LOGO}" => $logo,
            "{SITE_NAME}" => $SiteName,
            "{ORDER_NUMBER}" => $email_order_code,
            "{ORDER_DATE}" => $email_order_date,
            "{CONTACT_PERSON}" =>  $email_delevery_name,
            "{ADDRESS}" => $email_delevery_address,
            "{PAYMENT_METHOD}" => $payment_type_name,
            "{CURRENCY}" => $System_base_currency,
            "{TOTAL_AMOUNT}" =>  $email_order_total,
            "{ORDER_URL}" => $system_Invoice_url,
            "{STORE_EMAIL}" => $storeAdminEmail,
            "{STORE_CONTACTNO}" => $storeAdminContactNo,
          );

          $headers = "From: " . $SiteName . " <" . $system_email . ">\r\n";
          $headers .= "MIME-Version: 1.0\r\n";
          $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

          if (file_exists($template_file))
            $message = file_get_contents($template_file);
          else
            die("unable to locate the template file");

          foreach (array_keys($swap_var) as $key) {
            if (strlen($key) > 2 && trim($key) != "")
              $message = str_replace($key, $swap_var[$key], $message);
          }


          mail($email_to, $subject, $message, $headers);
        }


        if ($invoice_h_status == true) {

          $email_order_code = $queryLastID['invoice_h_code'];
          $email_customer_id = $queryLastID['invoice_h_customer_id'];
          $email_order_date = $queryLastID['invoice_h_date'];
          $email_order_total = $queryLastID['invoice_h_gross_value'];
          $email_delevery_name = $queryLastID['invoice_h_delivery_name'];
          $email_delevery_address = $queryLastID['invoice_h_delivery_address'];
          $system_Invoice_url = site_url() . "admin/invoice.php?id=" . $getLastid;
          $siteUrl = site_url();
          define("DEMO", false);
          $template_file = "../emails/OrderPlacedEmailForCustomer.php";
          $email_to = $_SESSION["Loginemail"];
          $subject = $SiteName . " Your order has been placed";

          $swap_var = array(
            "{LOGO}" => $logo,
            "{SITE_NAME}" => $SiteName,
            "{ORDER_NUMBER}" => $email_order_code,
            "{ORDER_DATE}" => $email_order_date,
            "{CONTACT_PERSON}" =>  $email_delevery_name,
            "{ADDRESS}" => $email_delevery_address,
            "{PAYMENT_METHOD}" => $payment_type_name,
            "{CURRENCY}" => $System_base_currency,
            "{TOTAL_AMOUNT}" =>  $email_order_total,
            "{ORDER_URL}" => $system_Invoice_url,
            "{STORE_EMAIL}" => $storeAdminEmail,
            "{STORE_CONTACTNO}" => $storeAdminContactNo,
          );

          $headers = "From: " . $SiteName . " <" . $system_email . ">\r\n";
          $headers .= "MIME-Version: 1.0\r\n";
          $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

          if (file_exists($template_file))
            $message = file_get_contents($template_file);
          else
            die("unable to locate the template file");

          foreach (array_keys($swap_var) as $key) {
            if (strlen($key) > 2 && trim($key) != "")
              $message = str_replace($key, $swap_var[$key], $message);
          }


          if (mail($email_to, $subject, $message, $headers))
          $emailSent = true;
        
        }
      }
    }
  } else {
  }
  $output =  array(
    'message' => $transaction_approve,
    'payment_type' => $payment_type_id,
    'order_id' => $getLastid,
    'online_process' => $order_process,
    'referance_code' => $refaranceCode,
    'order_status' => $invoice_h_status,
    'emailSent' => $emailSent,
    'processOrderWithoutPayment'=> $processOrderWithoutPayment
  );

  echo json_encode($output, JSON_FORCE_OBJECT);
}


?>