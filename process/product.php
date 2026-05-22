<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
function filter($var)
{

    return preg_replace('[0-9]', ' ', $var);
}

function limit_text($text, $limit)
{
    if (str_word_count($text, 0) > $limit) {
        $words = str_word_count($text, 2);
        $pos = array_keys($words);
        $text = substr($text, 0, $pos[$limit]) . '...';
    }
    return $text;
}
$db = new Database();


if (isset($_GET['url'])) {


    $url = $_GET['url'];



    $checkProductQuery = $db->getRow('SELECT * FROM item_master im INNER JOIN item_warranty iw ON iw.warranty_id = im.item_warranty WHERE im.url = ? ', [$url]);

    if ($checkProductQuery['item_id']) {

        $productName = $checkProductQuery['item_name'];
        $itemId = $checkProductQuery['item_id'];
        $productPrice = $checkProductQuery['item_normal_selling_price'];
        $others_selling_price = $checkProductQuery['others_selling_price'];
        $item_specification =  $checkProductQuery['item_discription'];
        $item_warranty = $checkProductQuery['warranty'];
        $item_mode = $checkProductQuery['item_mode'];
        $itemSubCat =  $checkProductQuery['item_category'];

        $discount = (($others_selling_price - $productPrice) * 100) / $others_selling_price;


        $getMinMonthlyplan = $db->getRow('SELECT * FROM product_settlement_plan WHERE productId = ? ORDER BY installment ASC', [$itemId]);

        $min_monthlty_palan = $getMinMonthlyplan['installment'];


        $today = date('Y-m-d');
        $estimate_delivery_date =  date('Y-M-D', strtotime($today . ' + 2 days'));

        if ($item_mode == "Normal") {

            $stock_avalibility =   '<span class="availability">in Stock</span>';
            $buy_button = '<button  data-item-id="' . $itemId . '" class="add-to-cart "  type="button" id="btnSubmit">Buy Now</button>';
        } else {
            $stock_avalibility = '<span class="availability" style="background:#750d0d;">Out Stock</span>';
            $buy_button = '<button class="add-to-cart " data-item-id="' . $itemId . '" type="button" disabled="" id="btnSubmit" style="background: gainsboro;color: white;">Buy Now</button>';
        }
    } else {

        #redirect Code
    }
} else {


    #redirect Code
}

function reletedItems()
{
    global $itemSubCat;
    $db = new Database();
    $query = $db->getRows('SELECT *  from item_master 
        INNER JOIN fifo ON item_master.item_id = fifo.ft_item WHERE item_master.item_active = "Y" AND item_master.item_category = ?  group by fifo.ft_item having sum(fifo.ft_blanace) >0 ORDER BY item_master.item_id DESC limit 5', [$itemSubCat]);

    return $query;
}



function getProductImages()
{
    global $itemId;
    $db = new database();
    $query = $db->getRows('SELECT * FROM productimages WHERE itemId = ? ORDER BY Id DESC', [$itemId]);

    return $query;
}

function getBanks()
{
    global $itemId;
    $db = new database();
    $query = $db->getRows('SELECT  * FROM banks b 
        INNER JOIN product_settlement_plan ps 
        ON ps.bankId = b.Id 
        WHERE ps.productId = ? 
        GROUP BY b.Id', [$itemId]);

    return $query;
}

function itemSpecification()
{
    global $itemId;
    $db = new database();
    $query = $db->getRows('SELECT * FROM item_specification WHERE product_id = ? ORDER BY Id ASC', [$itemId]);

    return $query;
}





?>
<!doctype html>
<html class="no-js" lang="zxx">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">

    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title><?php echo $productName; ?> | Online Shopping in Sri Lanka</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <?php include('common/styles.php'); ?>
</head>

<body>
    <!--[if lt IE 8]>
		<p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
	<![endif]-->

    <div class="body-wrapper">
        <?php include('common/header.php'); ?>

        <div class="content-wraper">
            <div class="container">
                <div class="row single-product-area">
                    <div class="col-lg-5 col-md-6">
                        <!-- Product Details Left -->
                        <div class="product-details-left">
                            <div class="product-details-images slider-navigation-1">
                                <?php $data = getProductImages();
                                $i = 0;

                                foreach ($data as $query) {

                                ?>
                                    <div class="lg-image">
                                        <img src="<?php echo site_url() . $query['imagePath'] . $query['image']; ?>">
                                    </div>
                                <?php } ?>
                            </div>
                            <div class="product-details-thumbs slider-thumbs-1">
                                <?php $data = getProductImages();
                                $i = 0;

                                foreach ($data as $query) {


                                ?>
                                    <div class="sm-image"><img src="<?php echo site_url() . $query['imagePath'] . $query['image']; ?>" style="width:100px;"></div>
                                <?php } ?>
                            </div>
                        </div>
                        <!--// Product Details Left -->
                    </div>

                    <div class="col-lg-7 col-md-6 shop-wrapper">
                        <div class="product-details-view-content sp-normal-content ">
                            <div class="product-info">
                                <h2 style="font-size:30px;"><?php echo $productName ?></h2>


                                <div class="price-box pt-20">
                                    <?php if ($others_selling_price > 0) { ?>



                                        <div class="" style="margin-bottom:5px;"><span style="text-decoration: line-through;"><?php echo currency($others_selling_price); ?></span><span class="product-page-discount"><?php echo number_format($discount, 0) . "% OFF"; ?></div>
                                    <?php } ?>


                                    <span class="new-price new-price-2 "><?php echo currency($productPrice); ?></span>
                                    <?php echo $stock_avalibility; ?>



                                </div>
                                <div class="custom-elements1 ">

                                    <ul class="basic-list">
                                        <li><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABEAAAARCAMAAAAMs7fIAAAA2FBMVEUAAABERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERU8ealAAAAR3RSTlMAAQIEBwgJCgsMDQ4PExQVFhweHyEiJTIzNjdFS0xNXWFsdHV7fIWLj5GSmpueoKOoqq2ytLm+wMHMztrg4uTo6+3z9ff7/fW6ew0AAAC0SURBVBgZRcGLQsFQAAbgH1mG6KRcqrkcYtMkuozKpXXO/vd/o5yd4fuQynmf+6ibw0khCsWlCKMCjsLRzVv83nh6Rqaq7hKSya2qwGq/+jT8RQvW/WxNYz3rwLrez2nMf+vIrJYJyWQZ4cjd/cT6b7NzkckPqxNNPSnLPKwevx9E46r2xT5SjqbR+SC1AyOgsW3yYIoDwZT3QkMA8KQck8rR5FjKR1gBB5AMcFZUJbjqAsY/8hUhkKKelowAAAAASUVORK5CYII=" alt="Warranty" title="Warranty"> <?php echo $item_warranty; ?></li>
                                        <li><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABEAAAARCAMAAAAMs7fIAAAA2FBMVEUAAABERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERU8ealAAAAR3RSTlMAAQIEBwgJCgsMDQ4PExQVFhweHyEiJTIzNjdFS0xNXWFsdHV7fIWLj5GSmpueoKOoqq2ytLm+wMHMztrg4uTo6+3z9ff7/fW6ew0AAAC0SURBVBgZRcGLQsFQAAbgH1mG6KRcqrkcYtMkuozKpXXO/vd/o5yd4fuQynmf+6ibw0khCsWlCKMCjsLRzVv83nh6Rqaq7hKSya2qwGq/+jT8RQvW/WxNYz3rwLrez2nMf+vIrJYJyWQZ4cjd/cT6b7NzkckPqxNNPSnLPKwevx9E46r2xT5SjqbR+SC1AyOgsW3yYIoDwZT3QkMA8KQck8rR5FjKR1gBB5AMcFZUJbjqAsY/8hUhkKKelowAAAAASUVORK5CYII=" alt="Warranty" title="Warranty"> Eligible for Cash on Delivery.</li>
                                        <li><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABEAAAARCAMAAAAMs7fIAAAA2FBMVEUAAABERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERU8ealAAAAR3RSTlMAAQIEBwgJCgsMDQ4PExQVFhweHyEiJTIzNjdFS0xNXWFsdHV7fIWLj5GSmpueoKOoqq2ytLm+wMHMztrg4uTo6+3z9ff7/fW6ew0AAAC0SURBVBgZRcGLQsFQAAbgH1mG6KRcqrkcYtMkuozKpXXO/vd/o5yd4fuQynmf+6ibw0khCsWlCKMCjsLRzVv83nh6Rqaq7hKSya2qwGq/+jT8RQvW/WxNYz3rwLrez2nMf+vIrJYJyWQZ4cjd/cT6b7NzkckPqxNNPSnLPKwevx9E46r2xT5SjqbR+SC1AyOgsW3yYIoDwZT3QkMA8KQck8rR5FjKR1gBB5AMcFZUJbjqAsY/8hUhkKKelowAAAAASUVORK5CYII=" alt="Warranty" title="Warranty"> Islandwide delivery. Estimated delivery date: <?php echo $estimate_delivery_date; ?>.</li>
                                    </ul>



                                </div>
                                <?php if ($min_monthlty_palan > 0) { ?>


                                    <div class="emisummary">
                                        <p style="margin-bottom: 2px;">Payment Plans starting from <b><?php echo currency($min_monthlty_palan); ?></b> per month. <a href="#popup1" data-lity="" title="View Easy Payment Plan details">view details</a></p>

                                        <?php
                                        $dataBankIcons = getBanks();

                                        foreach ($dataBankIcons as $query) {



                                        ?>

                                            <a class="emilogos t-collapse-trigger-external" href="#popup1" style="border-color: #E10531">
                                                <img src="<?php echo site_url() . $query['path'] . $query['image']; ?>" alt="<?php echo $query['name']; ?> Cards" title="<?php echo $query['name']; ?> Cards" style="border-color: #fda947">
                                            </a>
                                        <?php } ?>

                                    </div>
                                <?php } ?>

                                <div class="product-desc">
                                    <?php echo $item_discription;; ?>
                                </div>
                                <div class="single-add-to-cart">
                                    <form id="frn-item" class="cart-quantity">
                                        <div class="quantity">
                                            <label>Quantity</label>
                                            <div class="cart-plus-minus">
                                                <input class="cart-plus-minus-box" value="1" type="text" name="quantity">
                                                <div class="dec qtybutton"><i class="fa fa-angle-down"></i></div>
                                                <div class="inc qtybutton"><i class="fa fa-angle-up"></i></div>
                                            </div>
                                        </div>
                                        <button  data-item-id="<?php echo $itemId; ?>" class="add-to-cart "  type="button" id="btnSubmit">Buy Now</button>
                                        <input type="hidden" name="item_id" value="<?php echo $itemId; ?>">
                                    </form>
                                </div>
                                <div class="product-additional-info">
                                    <div class="product-social-sharing">
                                        <ul>
                                            <li class="facebook"><a href="#"><i class="fa fa-facebook"></i>Facebook</a></li>
                                            <li class="twitter"><a href="#"><i class="fa fa-twitter"></i>Twitter</a></li>
                                            <li class="google-plus"><a href="#"><i class="fa fa-google-plus"></i>Google +</a></li>
                                            <li class="instagram"><a href="#"><i class="fa fa-instagram"></i>Instagram</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- content-wraper end -->
        <!-- Begin Product Area -->
        <div class="product-area pt-40">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="li-product-tab">
                            <ul class="nav li-product-menu">
                                <li><a class="active" data-toggle="tab" href="#description"><span>Product Details</span></a></li>
                                <li><a data-toggle="tab" href="#product-Specifications"><span>Product Specifications</span></a></li>

                            </ul>
                        </div>
                        <!-- Begin Li's Tab Menu Content Area -->
                    </div>
                </div>
                <div class="tab-content">
                    <div id="description" class="tab-pane active show" role="tabpanel">
                        <div class="product-description">
                            <?php echo $item_specification; ?>







                        </div>
                    </div>
                    <div id="product-Specifications" class="tab-pane " role="tabpanel">
                        <div class="product-details-manufacturer">

                            <div class="row">
                                <div class="col-md-4">

                                </div>
                            </div>
                            <div class="specifications">
                                <ul style=" ">
                                    <?php

                                    $data = itemSpecification();
                                    $i = 0;

                                    foreach ($data as $query) {

                                    ?>



                                        <li style="">
                                            <span class="Specifications" style="margin:2px; border-bottom: 4px dotted #ccc;">
                                                <div class="key" style="margin:2px; border-bottom: 1px dotted #ccc;  width: 150;height:50px;"><?php echo limit_text($query['key'], 20); ?> </div>
                                                <div class="value" style="margin:2px; border-bottom: 1px dotted #ccc;    width: 260px;height:50px;"><?php echo limit_text($query['value'], 10); ?></div>

                                            </span>

                                        </li>



                                    <?php
                                    } ?>

                                </ul>

                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
        <!-- Product Area End Here -->
        <section class="product-area li-laptop-product pt-60 pb-45 pt-sm-50 pt-xs-60">
            <div class="container">
                <div class="row">

                    <div class="col-lg-12">
                        <div class="li-section-title">
                            <h2>
                                <span>Related items</span>
                            </h2>

                        </div>
                        <div class="row">
                            <div class="product-active owl-carousel">
                                <?php
                                $dataproducts = reletedItems();

                                foreach ($dataproducts as $productQuery) {

                                    $productPrice = $productQuery['item_normal_selling_price'];

                                    $productImage = $productQuery['item_image'];
                                    $imagepath = $productQuery['imageParth'];
                                    $others_selling_price = $productQuery['others_selling_price'];

                                    if ($productImage) {

                                        $productimage = $imagepath . "/" . $productImage;
                                    } else {
                                        $productimage = "images/product_img/defult-img.png";
                                    }
                                    $discount = (($others_selling_price - $productPrice) * 100) / $others_selling_price;
                                ?>
                                    <div class="col-lg-12">
                                        <!-- single-product-wrap start -->
                                        <div class="single-product-wrap">
                                            <div class="product-image">
                                                <a href="<?php echo  site_url() . "product/" . $productQuery['url']; ?>">
                                                    <img src="<?php echo site_url() . $productimage; ?>" alt="<?php echo $productQuery['item_name']; ?>">
                                                </a>

                                            </div>
                                            <div class="product_desc">
                                                <div class="product_desc_info">

                                                    <h4><a class="product_name" href="#"><?php echo $productQuery['item_name']; ?></a></h4>
                                                    <div class="price-box">
                                                        <span class="new-price new-price-2"><?php echo currency($productPrice); ?></span>
                                                        <?php if ($others_selling_price) { ?>
                                                            <span class="old-price" style="font-size:11px;"><?php echo currency($others_selling_price); ?></span>
                                                            <span class="discount-percentage">-<?php echo number_format($discount, 0); ?>%</span>
                                                        <?php } ?>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                        <!-- single-product-wrap end -->
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <?php include('common/footer.php'); ?>
        <!-- Footer Area End Here -->

    </div>

    <div id="popup1" class="overlay">

        <div class="popup">
            <h2>Installment plans</h2>
            <a class="close" href="#">&times;</a>
            <div class="content" style="padding:20px;">
                <div class="panel-group wrap" id="accordion" role="tablist" aria-multiselectable="true">

                    <?php

                    function getSettlements($bankId)
                    {

                        global $itemId;
                        $db = new database();
                        $query = $db->getRows('SELECT * FROM product_settlement_plan WHERE productId = ? AND bankId = ? ORDER BY bankId ASC', [$itemId, $bankId]);
                        return $query;
                    }


                    $dataBanks = getBanks();
                    $bankCount = 0;
                    foreach ($dataBanks as $query) {
                        $bankCount++;
                        $bankId = $query['bankId'];


                        $settlementsQuery =  getSettlements($bankId);

                    ?>
                        <div class="panel">
                            <div class="panel-heading" role="tab" id="headingOne">
                                <h4 class="panel-title">
                                    <a role="button" data-toggle="collapse" data-parent="#accordion" href="#bankId<?php echo $bankCount; ?>" aria-expanded="true" aria-controls="collapseOne">
                                        <?php echo $query['name']; ?> Cards
                                    </a>
                                </h4>
                            </div>
                            <div id="bankId<?php echo $bankCount; ?>" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingOne" style="margin-bottom:5px;">
                                <div class="panel-body emi-content-inner">
                                    <table>
                                        <tbody>
                                            <tr>
                                                <th>Plan (months)</th>
                                                <th>Installment</th>
                                            </tr>
                                            <?php

                                            foreach ($settlementsQuery as $settlement) { ?>
                                                <tr>
                                                    <td><?php echo $settlement['months']; ?></td>
                                                    <td class="emi-installment" data-emipercentage="105" data-emiplan="6" data-fee="true">
                                                        <?php echo currency($settlement['installment']); ?> </td>
                                                </tr>


                                            <?php } ?>

                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <!-- end of panel -->
                    <?php   } ?>





                </div>
            </div>
        </div>
    </div>

    <div id="myModal" class="modal fade" role="dialog">
        <div class="notify successbox">
            <h1>Success!</h1>
         
            <p>Thanks so much for your message. We check e-mail frequently and will try our best to respond to your inquiry.</p>
            <div class="actions">
            <button type="button" class="btn btn-sky "><a href="#">Continue</a></button>
                <button type="button" class="btn btn-sunny "><a href="#">Go to Secure Payment</a></button>
                            </div>
        </div>
    </div>

    
    <!-- Body Wrapper End Here -->
    <!-- jQuery-V1.12.4 -->
    <script src="<?php echo site_url(); ?>js/vendor/jquery-1.12.4.min.js"></script>
    <!-- Popper js -->
    <script src="<?php echo site_url(); ?>js/vendor/popper.min.js"></script>
    <!-- Bootstrap V4.1.3 Fremwork js -->
    <script src="<?php echo site_url(); ?>js/bootstrap.min.js"></script>
    <!-- Ajax Mail js -->
    <script src="<?php echo site_url(); ?>js/ajax-mail.js"></script>
    <!-- Meanmenu js -->
    <script src="<?php echo site_url(); ?>js/jquery.meanmenu.min.js"></script>
    <!-- Wow.min js -->
    <script src="<?php echo site_url(); ?>js/wow.min.js"></script>
    <!-- Slick Carousel js -->
    <script src="<?php echo site_url(); ?>js/slick.min.js"></script>
    <!-- Owl Carousel-2 js -->
    <script src="<?php echo site_url(); ?>js/owl.carousel.min.js"></script>
    <!-- Magnific popup js -->
    <script src="<?php echo site_url(); ?>js/jquery.magnific-popup.min.js"></script>
    <!-- Isotope js -->
    <script src="<?php echo site_url(); ?>js/isotope.pkgd.min.js"></script>
    <!-- Imagesloaded js -->
    <script src="<?php echo site_url(); ?>js/imagesloaded.pkgd.min.js"></script>
    <!-- Mixitup js -->
    <script src="<?php echo site_url(); ?>js/jquery.mixitup.min.js"></script>
    <!-- Countdown -->
    <script src="<?php echo site_url(); ?>js/jquery.countdown.min.js"></script>
    <!-- Counterup -->
    <script src="<?php echo site_url(); ?>js/jquery.counterup.min.js"></script>
    <!-- Waypoints -->
    <script src="<?php echo site_url(); ?>js/waypoints.min.js"></script>
    <!-- Barrating -->
    <script src="<?php echo site_url(); ?>js/jquery.barrating.min.js"></script>
    <!-- Jquery-ui -->
    <script src="<?php echo site_url(); ?>js/jquery-ui.min.js"></script>
    <!-- Venobox -->
    <script src="<?php echo site_url(); ?>js/venobox.min.js"></script>
    <!-- Nice Select js -->
    <script src="<?php echo site_url(); ?>js/jquery.nice-select.min.js"></script>
    <!-- ScrollUp js -->
    <script src="<?php echo site_url(); ?>js/scrollUp.min.js"></script>
    <!-- Main/Activator js -->
    <script src="<?php echo site_url(); ?>js/main.js"></script>
</body>
<script>
    $(document).ready(function() {
        $('.collapse.in').prev('.panel-heading').addClass('active');
        $('#accordion, #bs-collapse')
            .on('show.bs.collapse', function(a) {
                $(a.target).prev('.panel-heading').addClass('active');
            })
            .on('hide.bs.collapse', function(a) {
                $(a.target).prev('.panel-heading').removeClass('active');
            });
    });
</script>

</html>

<script>
    $('document').ready(function() {

        $("#btnSubmit").click(function() {
            
            var item_id = $("input[name=item_id]").val();
            var quantity = $("input[name=quantity]").val();

            $.ajax({
                type: "POST",
                url: '<?php echo  site_url(); ?>process/add-item-session.php',
                data: {
                    item_id: item_id,
                    quantity: quantity
                },
                success: function(data) {
                    
                    $('#myModal').modal('show'); 

                }
            });



            return false;

        });


    });
</script>