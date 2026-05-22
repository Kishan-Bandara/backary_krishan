<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');
if (isset($_SESSION['LoginStatus'])) {
    $LoginStatus = $_SESSION['LoginStatus'];
} else {
    $LoginStatus = "";
}
if ($LoginStatus != "login_success") {
    Redirect('login.php', false);
}
function filter($var)
{
    return preg_replace('[0-9]', ' ', $var);
}
$order_id = 0;
if (isset($_GET['order_id'])) {
    $order_id = filter($_GET['order_id']);
}
$db = new Database();
$query_cehck_order_id = $db->getRow('SELECT * FROM invoice_hedder WHERE invoice_h_id = ? AND invoice_h_customer_id = ?', [$order_id, $session_user_id]);

$order_number = $query_cehck_order_id['invoice_h_code'] ?? '';
$order_amount = $query_cehck_order_id['invoice_h_gross_value'] ?? '';
$transaction_type = $query_cehck_order_id['invoice_h_pay_type'] ?? '';

if ($transaction_type) {

    $query_pay_type = $db->getRow('SELECT * FROM payment_method WHERE id = ?', [$transaction_type]);

    $transaction_type = $query_pay_type['type'];
}

$merchant_id         = $_POST['merchant_id'];
$order_id            = $_POST['order_id'];
$payhere_amount      = $_POST['payhere_amount'];
$payhere_currency    = $_POST['payhere_currency'];
$status_code         = $_POST['status_code'];
$md5sig              = $_POST['md5sig'];
echo $status_code;
$merchant_secret = 'MjMwOTI4MDM1MzM1MTY4NTExNzMyMzI0NzUxOTA4NjMxMDA5NTI0'; // Replace with your Merchant Secret (Can be found on your PayHere account's Settings page)

$local_md5sig = strtoupper(
    md5(
        $merchant_id . 
        $order_id . 
        $payhere_amount . 
        $payhere_currency . 
        $status_code . 
        strtoupper(md5($merchant_secret)) 
    ) 
);
       
if (($local_md5sig === $md5sig) AND ($status_code == 2) ){
        //TODO: Update your database as payment success
        echo "asdadadfsfsdfsds";
}


?>
<!DOCTYPE html>
<html lang="en">


<head>
    <?php include('common/styles.php'); ?>
</head>

<body>
    <div class="ps-page">
        <?php include('common/header.php'); ?>
        <div class="container">

<div class="row">
    <div class="col-md-6 col-md-offset-3">
        <p style="    text-align: left;
margin-left: 5px;"><img src="<?php echo site_url(); ?>assets/img/icon/order_complated.gif" style="width:40%; float:right;"> </p>
        <br><br>
        <h2 style="color:black;font-size: 24px;margin-top:9px;margin-bottom:10px;    letter-spacing: 1.8px;  text-transform: uppercase;">Congratulations!</h2>
        <p style="font-size:18px;color:black;">Your order has been placed. </p>

        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <td class="text-left">Transaction</td>
                    <td class="text-left">Status</td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-left">Order No</td>
                    <td class="text-left"><?php echo $order_number; ?></td>
                </tr>
                <tr>
                    <td class="text-left">Amount</td>
                    <td class="text-left"><?php echo currency($order_amount); ?></td>
                </tr>
                <tr>
                    <td class="text-left">Transaction Status</td>
                    <td class="text-left">Transaction Successfully done</td>
                </tr>
                <tr>
                    <td class="text-left">Transaction Type</td>
                    <td class="text-left"><?php echo $transaction_type; ?></td>
                </tr>
            </tbody>
        </table>
        <p><a href="index.php"><button type="" class="btn" id="button-confirm" value=""> BACK TO HOME</button></p>
        </p>
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