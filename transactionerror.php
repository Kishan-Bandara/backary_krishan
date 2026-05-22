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
    margin-left: 5px;"><img src="<?php echo site_url(); ?>assets/img/icon/error.gif" style="width:20%;"> </p>

                    <h2 style="color:black;font-size: 24px;margin-top:9px;margin-bottom:10px;    letter-spacing: 1.8px;  text-transform: uppercase;">SORRY!</h2>
                    <p style="font-size:18px;color:black;">Your payment was not successful.</p>
                    <p style="line-height: 0.8;color:black;font-size:11px; font-weight:bold;">Please contact your bank for more details.</p>
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