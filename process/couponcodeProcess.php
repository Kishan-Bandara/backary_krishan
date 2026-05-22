<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

session_start();
include('../include/database.php');


date_default_timezone_set("Asia/Colombo");
$db = new Database();
function filter($var)
{

    return preg_replace('/ [^a-za-z0-9\s@.]/', ' ', $var);
}
$title = "";
$message = "";
$status = false;

$nowDate = date("Y-m-d");
$nowTime = date("h:i:s");
$nowDateTime = date("Y-m-d h:i:s");



$status = false;

if (!empty($_SESSION['SBCScart'])) {


    $total = 0;
    // For finding session elements line number
    $linenumber = 0;
    $i = 0;

    // Run loop for cart array 
    foreach ($_SESSION['SBCScart'] as $SBCSitem) {
        $i = $i + 1;
        // Don't list items with 0 qty
        if ($SBCSitem['quantity'] != 0) {

            $session_item_id = str_replace(",", ".", $SBCSitem['item_id']);
            $session_item_name = $SBCSitem['item'];
            $session_item_image = $SBCSitem['item_image'];

            // We calculate total values with decimals
            $pricedecimal = $SBCSitem['price'];
            $qtydecimal = $SBCSitem['quantity'];
            $get_item_discount = $SBCSitem['item_discount'];

            $pricedecimal = (float) $pricedecimal;
            $qtydecimal = (float) $qtydecimal;
            $get_item_discount = number_format((float) $get_item_discount, 2, '.', '');

            $totaldecimal = $pricedecimal * $qtydecimal;



            // Total
            $total += $totaldecimal;
        }
        $linenumber++;
    }
}



if ($_POST['coupon_code']) {

    $coupon_code = $_POST['coupon_code'];


    try {

        $query_src = $db->getRow('SELECT * FROM coupon_codes WHERE code =?', [$coupon_code]);
    } catch (Exception $e) {

        $message = "Database Error. query can not inject";
    }

    $real_coupon_id = $query_src['id'];
    $real_coupon_code = $query_src['code'];
    $real_coupon_type = $query_src['type'];
    $real_coupon_rate = $query_src['rate'];
    $real_coupon_offer = $query_src['offer_value'];
    $real_coupon_limite = $query_src['limited'];
    $coupon_code_user = $query_src['user_id'];


    if ($real_coupon_type == "PCT") {

        $real_coupon_code_display_message = $real_coupon_code . " coupon code used: " . $real_coupon_rate . "% Saved";

        $couponValue = ($total * $real_coupon_rate) / 100;
        $real_copun_code_display_feild =  $couponValue;
    } else {

        $real_coupon_code_display_message = $real_coupon_code . " coupon code used: - Rs " . $real_coupon_rate . " Saved";
        $real_copun_code_display_feild = $real_coupon_offer ;
        $couponValue = $real_coupon_offer;
    }



    if ($coupon_code == $real_coupon_code) {




        if ($coupon_code_user > 0) {
            
            if ($session_user_id == $coupon_code_user) {


                if ($real_coupon_limite == 0) {

                    $message = "This coupon code expired";
                } else {

                    if ($real_coupon_offer == $total ||  $real_coupon_offer < $total) {

                        $_SESSION['coupon_id']     = $real_coupon_id;
                        $_SESSION['coupon_code']   = $real_coupon_code;
                        $_SESSION['coupon_type']   = $real_coupon_type;
                        $_SESSION['coupon_rate']   = $real_coupon_rate;
                        $_SESSION['coupon_message'] = $real_coupon_code_display_message;
                        $_SESSION['coupon_display'] = $real_copun_code_display_feild;
                        $_SESSION['coupon_value'] = $couponValue;

                        $message = "Congratulationscoupon your code added ";
                        $status = true;
                    } else {


                        $message = "Coupon code can be used only for purchases above Rs " . $real_coupon_offer;
                    }
                }
            } else {

                $message = "You have no permission use for this coupon code!";
            }
        } else {

           

            if ($real_coupon_limite == 0) {

                $message = "This coupon code expired";
            } else {

                if ($real_coupon_offer == $total ||  $real_coupon_offer < $total) {

                    $_SESSION['coupon_id']     = $real_coupon_id;
                    $_SESSION['coupon_code']   = $real_coupon_code;
                    $_SESSION['coupon_type']   = $real_coupon_type;
                    $_SESSION['coupon_rate']   = $real_coupon_rate;
                    $_SESSION['coupon_message'] = $real_coupon_code_display_message;
                    $_SESSION['coupon_display'] = $real_copun_code_display_feild;
                    $_SESSION['coupon_value'] = $couponValue;

                    $message = "Congratulationscoupon your code added ";
                    $status = true;
                } else {


                    $message = "Coupon code can be used only for purchases above Rs " . $real_coupon_offer;
                }
            }
        }
    } else {

        $message = "coupon code did not match";
    }
} else {
    $message = "Please enter the coupon code";
}







$output =  array(
    'status' => $status,
    'message' => $message
);

echo json_encode($output, JSON_FORCE_OBJECT);
