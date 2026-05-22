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

if (isset($_GET['name'])) {

    $url = $_GET['name'];

    $checkQuery = $db->getRow('SELECT * FROM custompage WHERE pageUrl = ? ', [$url]);

    if ($checkQuery && !empty($checkQuery['id'])) {
        $body = $checkQuery['body'];
    } else {
        $body = '<p>Page not found.</p>';
        // redirect logic can be added here if needed
    }
} else {


    #redirect Code
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
                <div class="container">       <?php echo $body;?></div>
     
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