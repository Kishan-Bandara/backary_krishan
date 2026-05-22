<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
function filter($var)
{

    return preg_replace('[0-9]', ' ', $var);
}
$db = new Database();
$order_confirm = true;
$_SESSION["paymentProcess"] = false;
$paymentProcessTrue = false;
if (isset($_SESSION["paymentId"]) && isset($_POST["button-confirm"])) {
    $query = $db->getRow('SELECT * FROM payment_method WHERE id = ?', [$_SESSION['paymentId']]);
    $orderProcess  = $query['orderProcess'];
  
    if ($orderProcess == 1) {
        $paymentProcessTrue = true;
        $_SESSION["paymentProcess"] = true;
        $order_confirm = true;
    } else {

        //redirect
        
    }
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
        <div class="ps-page--notfound">
            <div class="container">
                
                <div class="ps-page__content">
                    <div class="row">
                        <div class="col-12 col-md-6 col-lg-5"><img src="assets/img/paymentLoading.gif" ></div>
                        <div class="col-12 col-md-6 col-lg-7">
                            <h1 class="ps-page__name" style="font-size: 110px;">Processing..</h1>
                            <p>Please wait.. we are checking for your payment informations..</p>
                           
                        </div>
                    </div>
                </div>
                <form method="post" action="https://www.payhere.lk/pay/checkout" id="postsubmit">   
                <input type="hidden" name="merchant_id" value="221009">    <!-- Replace your Merchant ID -->
                <input type="hidden" name="return_url" value="https://purebeautyitaly.com/transaction.php">
                <input type="hidden" name="cancel_url" value="https://purebeautyitaly.com/checkout.php">
                <input type="hidden" name="notify_url" value="https://purebeautyitaly.com/process/paymentProcess.php">  
                <input type="hidden" name="order_id" value="0">
                <input type="hidden" name="items" value="">
                <input type="hidden" name="currency" value="<?php echo $_SESSION['currency'];?>">
                <input type="hidden" name="amount" value="<?php echo $_SESSION["TotalAmount"] ?? 0; ?>">  
                <input type="hidden" name="first_name" value="<?php echo $_SESSION["contactPerson"] ?? ''; ?>">
                <input type="hidden" name="last_name" value="">
                <input type="hidden" name="email" value="<?php echo $_SESSION["EmailAddress"] ?? ''; ?>">
                <input type="hidden" name="phone" value="<?php echo $_SESSION["contactNo"] ?? ''; ?>">
                <input type="hidden" name="address" value="<?php echo ($_SESSION["address"] ?? '') . " " . ($_SESSION["address2"] ?? ''); ?>">
                <input type="hidden" name="city" value="<?php echo $_SESSION["cityByName"] ?? ''; ?>">
                <input type="hidden" name="country" value="<?php echo $_SESSION["countryName"] ?? ''; ?>">
               
</form> 
            </div>
           
        </div>
        <br>
        <?php include('common/footer.php'); ?>
    </div>


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


    <script type="text/javascript">
     var order_confirm = <?php echo json_encode($order_confirm); ?>;
    var paymentProcessTrue = <?php echo json_encode($paymentProcessTrue); ?>;
        
     $.ajax({
                url: 'process/order_process.php',
                type: 'POST',
                data: {
                    order_confirm: order_confirm,
                    paymentProcessTrue: paymentProcessTrue
                },
                beforeSend: function() {

                },
                success: function(result) {
                  
                    var jsonobj = JSON.parse(result);
                    var order_message = jsonobj.message;

                    if (jsonobj.order_status == true) {
                            if(jsonobj.payment_type == 8){
                                 $("input[name='order_id']").val(jsonobj.order_id);
                                 $('#postsubmit').submit();
                            }else{
                                if(jsonobj.processOrderWithoutPayment == true){
                                    window.location.replace("transaction.php?order_id=" + jsonobj.order_id);
                                }else{
                                   window.location.replace("transactionerror.php");
                                }
                                
                            }
                        
                    } else {
                        window.location.replace("transactionerror.php");
                    }

                }

            });
    </script>

