<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
function filter($var)
{

    return preg_replace('[0-9]', ' ', $var);
}


function mainSlider()
{

    $db = new database();
    $query = $db->getRows('SELECT * FROM home_slider WHERE active = 1');
    return $query;
}



function newProducts()
{

    $display = 'item_master.item_active = "Y" AND';
    $db = new Database();
    $query = $db->getRows('SELECT *  from item_master 
    INNER JOIN fifo ON item_master.item_id = fifo.ft_item WHERE ' . $display . ' item_master.item_dispay_home = ? AND item_master.item_mode = ? group by fifo.ft_item having sum(fifo.ft_blanace) >0 ORDER BY item_id DESC limit 24', [1, "Normal"]);

    return $query;
}

function bestSeller()
{
    $db = new Database();
    $query = $db->getRows('SELECT *  from item_master 
    INNER JOIN fifo ON item_master.item_id = fifo.ft_item WHERE item_master.item_active = "Y" AND item_master.item_dispay_home = ? group by fifo.ft_item having sum(fifo.ft_blanace) >0 ORDER BY RAND( ) limit 24', [1]);

    return $query;
}


function categories()
{
    $db = new Database();
    $query = $db->getRows('SELECT category_master.value1 ,category_master.value2 ,category_master.category_id , category_master.category_name from category_master 
    INNER JOIN item_master ON item_master.item_category = category_master.category_id
    INNER JOIN fifo ON item_master.item_id = fifo.ft_item
    WHERE item_master.item_dispay_home = 1 
    AND category_master.category_id IN(209)
    group by category_master.category_id
    ORDER BY category_master.category_id DESC');
    return $query;
}


function productLists($catgoryId)
{
    $db = new Database();
    $query = $db->getRows('SELECT *  from item_master 
    INNER JOIN category_master ON category_master.category_id = item_master.item_category
    INNER JOIN fifo ON item_master.item_id = fifo.ft_item
    WHERE item_master.item_dispay_home = 1 AND
    category_master.category_id = ?
    group by fifo.ft_item 
    having sum(fifo.ft_blanace) >0 
    ORDER BY FIELD(item_mode, "Normal","offline","OutOfStock"),  item_id DESC limit 6', [$catgoryId]);

    return $query;
}

function TypeList()
{

    $db = new Database();
    $query = $db->getRows('SELECT * FROM type_master');
    return $query;
}

function SubCategoryList()
{

    $db = new Database();
    $query = $db->getRows('SELECT * FROM type_master limit 6');
    return $query;
}


function Banners($groupId)
{

    $db = new Database();
    $query = $db->getRows('SELECT * FROM banners WHERE group_id = ? ORDER BY SelectedOrder ASC', [$groupId]);
    $data = $query;
    foreach ($data as $query) {

        $columnSize = "col-md-" . $query['columns'];
        $image = $query['imagePath'].$query['image'];
        echo '<div class="col-12 ' . $columnSize . '">
    <div class="ps-promo__item"><img class="ps-promo__banner" src="'.$image.'" />
     
    </div>
    </div>';
    }
}

function Reviews()
{
    $db = new Database();
    $query = $db->getRows('SELECT * FROM reviews ORDER BY id DESC limit 20');
    return $query;
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
        <div class="ps-home ps-home--1">

            <section class="ps-section--banner ps-banner--container">
                <div class="ps-section__overlay">
                    <div class="ps-section__loading"></div>
                </div>
                <div class="owl-carousel" data-owl-auto="false" data-owl-loop="true" data-owl-speed="15000" data-owl-gap="0" data-owl-nav="true" data-owl-dots="true" data-owl-item="1" data-owl-item-xs="1" data-owl-item-sm="1" data-owl-item-md="1" data-owl-item-lg="1" data-owl-duration="1000" data-owl-mousedrag="on">
                    <?php

                    $data4 = mainSlider();
                    foreach ($data4 as $query) {
                    ?>
                        <div class="ps-banner" style="background:#10317800;">
                            <div class="container">
                                <div class="ps-banner__block">
                                    <div class="ps-banner__content">
                                        <?php echo $query['text']  ?>


                                    </div>
                                    <div class="ps-banner__thumnail ps-banner__fluid"><img class="ps-banner__image" src="<?php echo $query['path'] . $query['image']; ?>" alt="alt" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </section>
            <div class="ps-home__content">
                <div class="container">
                   
                    <section class="ps-section--categories">
                        
                        <div class="ps-section__content">
                            <div class="ps-categories__list">
                            <?php
                                $dataSubCategory = SubCategoryList();
                                foreach ($dataSubCategory as $query) {
                                    $CategoryName = $query['type_name'];
                                    $CategoryImage = $query['image'];
                                    $clean_url = $query['clean_url'];
                                ?>
                                <div class="ps-categories__item"><a class="ps-categories__link" href="<?php echo site_url()."/products/".$clean_url?>"><img src="<?php echo site_url() . $CategoryImage; ?>" alt=""></a><a class="ps-categories__name" href="<?php echo site_url()."/products/".$clean_url?>"><?php echo $CategoryName; ?></a></div>
                                <?php } ?>
                            </div>
                           
                        </div>
                    </section>
                    
                </div>
                <section class="ps-section--latest">
                    <div class="container">
                        <h3 class="ps-section__title">Latest products</h3>
                        <div class="ps-section__carousel">
                            <div class="owl-carousel" data-owl-auto="false" data-owl-loop="true" data-owl-speed="13000" data-owl-gap="0" data-owl-nav="true" data-owl-dots="true" data-owl-item="5" data-owl-item-xs="2" data-owl-item-sm="2" data-owl-item-md="3" data-owl-item-lg="5" data-owl-item-xl="5" data-owl-duration="1000" data-owl-mousedrag="on">
                                <?php

                                $dataNewProduct = newProducts();

                                foreach ($dataNewProduct as $query) {

                                    $productName = $query['item_name'];
                                    $productPrice =  ($query['item_normal_selling_price']) ? $query['item_normal_selling_price'] : 0.00;
                                    $others_selling_price = ($query['others_selling_price']) ? $query['others_selling_price'] : 0.00;
                                    $item_discount = ($query['item_discount']) ? $query['item_discount'] : 0.00;
                                    $isDiscountHas = $query['item_promotion_status'];
                                    $productImage = $query['item_image'];
                                    $imagepath = $query['imageParth'];

                                    if ($productImage) {

                                        $productimage = $imagepath . "/" . $productImage;
                                    } else {
                                        $productimage = "images/product_img/defult-img.png";
                                    }

                                    if ($item_discount > 0) {
                                        $discount_amout = (($productPrice) * $item_discount) / 100;
                                        $discount_amout = $productPrice - $discount_amout;
                                    } else {
                                        $discount_amout = $productPrice;;
                                    }



                                ?>

                                    <div class="ps-section__product">
                                        <div class="ps-product ps-product--standard">
                                            <div class="ps-product__thumbnail"><a class="ps-product__image" href="<?php echo  site_url() . "product/" . $query['url']; ?>">
                                                    <figure><img src="<?php echo $productimage; ?>" alt="<?php echo $productName; ?>" /><img src="<?php echo $productimage; ?>" alt="<?php echo $productName; ?>" />
                                                    </figure>
                                                </a>
                                                <?php
                                                if ($item_discount > 0) { ?>
                                                    <div class="ps-product__badge">
                                                        <div class="ps-badge ps-badge--hot"><?php echo $item_discount; ?>% OFF</div>
                                                    </div>
                                                <?php  } ?>
                                            </div>
                                            <div class="ps-product__content">
                                                <h5 class="ps-product__title"><a href="<?php echo  site_url() . "product/" . $query['url']; ?>"><?php echo $productName; ?></a></h5>

                                                <div class="ps-product__meta"><span class="ps-product__price sale"><?php echo currency($discount_amout); ?></span><br>
                                                    <?php
                                                    if ($item_discount > 0) { ?>
                                                        <span class="ps-product__del"><?php echo currency($productPrice); ?></span>
                                                    <?php  } ?>
                                                </div>

                                            </div>
                                        </div>

                                    </div>

                                <?php  } ?>

                            </div>



                        </div>
                    </div>
                </section>
                <div class="container">


                    <div class="ps-promo">
                        <div class="row">
                            <?php echo Banners("group_1"); ?>
                        </div>
                    </div>
                    <?php
                    $categories =  categories();

                    foreach ($categories as $query) {
                        $catgoryId = $query['category_id'];
                        $dataproducts = productLists($catgoryId);

                    ?>
                        <div class="ps-home--block">
                            <div class="row">
                                <div class="col-12 col-md-4">
                                    <div class="ps-block__image">
                                        <section class="ps-home__banner">
                                            <div class="ps-banner" style="background:#FD8D27;"><img class="ps-banner__overlay" src="img/promotion/bg-banner16.jpg" alt="alt" />
                                                <div class="ps-banner__block">
                                                    <div class="ps-banner__content">
                                                        <h2 class="ps-banner__title">Take care <br />of yourself and your skin </h2>
                                                        <div class="ps-banner__btn-group">
                                                            <div class="ps-banner__btn"><img src="img/icon/icon11.png" alt="alt" />Best quality</div>
                                                            <div class="ps-banner__btn"><img src="img/icon/icon12.png" alt="alt" />Day &amp; Night</div>
                                                        </div><a class="bg-white ps-banner__shop" href="#">Sunscreens</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </section>
                                    </div>
                                </div>
                                <div class="col-12 col-md-8">
                                    <div class="ps-block__product">
                                        <div class="row m-0">
                                            <?php foreach ($dataproducts as $query) {

                                                $productName = $query['item_name'];
                                                $productPrice =  ($query['item_normal_selling_price']) ? $query['item_normal_selling_price'] : 0.00;
                                                $others_selling_price = ($query['others_selling_price']) ? $query['others_selling_price'] : 0.00;
                                                $item_discount = ($query['item_discount']) ? $query['item_discount'] : 0.00;
                                                $isDiscountHas = $query['item_promotion_status'];
                                                $productImage = $query['item_image'];
                                                $imagepath = $query['imageParth'];

                                                if ($productImage) {

                                                    $productimage = $imagepath . "/" . $productImage;
                                                } else {
                                                    $productimage = "images/product_img/defult-img.png";
                                                }

                                                if ($item_discount > 0) {
                                                    $discount_amout = (($productPrice) * $item_discount) / 100;
                                                    $discount_amout = $productPrice - $discount_amout;
                                                } else {
                                                    $discount_amout = $productPrice;;
                                                }

                                            ?>
                                                <div class="col-6 col-lg-4 p-0">
                                                    <div class="ps-product ps-product--standard">
                                                        <div class="ps-product__thumbnail"><a class="ps-product__image" href="<?php echo  site_url() . "product/" . $query['url']; ?>">
                                                                <figure><img src="<?php echo $productimage; ?>" alt="<?php echo $productName; ?>" /><img src="<?php echo $productimage; ?>" alt="<?php echo $productName; ?>" /></figure>
                                                            </a>
                                                            <?php
                                                            if ($item_discount > 0) { ?>
                                                                <div class="ps-product__badge">
                                                                    <div class="ps-badge ps-badge--hot"><?php echo $item_discount; ?>% OFF</div>
                                                                </div>
                                                            <?php  } ?>


                                                        </div>
                                                        <div class="ps-product__content">
                                                            <h5 class="ps-product__title"><a href="<?php echo  site_url() . "product/" . $query['url']; ?>"><?php echo $productName; ?></a></h5>
                                                            <div class="ps-product__meta"><span class="ps-product__price sale"><?php echo currency($discount_amout); ?></span>
                                                            </div>


                                                        </div>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php  } ?>
                    <section class="ps-section--featured">
                        <h3 class="ps-section__title">Featured products</h3>
                        <div class="ps-section__content">
                            <div class="row m-0">
                                <?php

                                $databestSeller = bestSeller();

                                foreach ($databestSeller as $query) {

                                    $productName = $query['item_name'];
                                    $productPrice =  ($query['item_normal_selling_price']) ? $query['item_normal_selling_price'] : 0.00;
                                    $others_selling_price = ($query['others_selling_price']) ? $query['others_selling_price'] : 0.00;
                                    $item_discount = ($query['item_discount']) ? $query['item_discount'] : 0.00;
                                    $isDiscountHas = $query['item_promotion_status'];
                                    $productImage = $query['item_image'];
                                    $imagepath = $query['imageParth'];

                                    if ($productImage) {

                                        $productimage = $imagepath . "/" . $productImage;
                                    } else {
                                        $productimage = "images/product_img/defult-img.png";
                                    }

                                    if ($item_discount > 0) {
                                        $discount_amout = (($productPrice) * $item_discount) / 100;
                                        $discount_amout = $productPrice - $discount_amout;
                                    } else {
                                        $discount_amout = $productPrice;;
                                    }



                                ?>

                                    <div class="col-6 col-md-4 col-lg-2dot4 p-0">
                                        <div class="ps-section__product">
                                            <div class="ps-product ps-product--standard">
                                                <div class="ps-product__thumbnail"><a class="ps-product__image" href="<?php echo  site_url() . "product/" . $query['url']; ?>">
                                                        <figure><img src="<?php echo $productimage; ?>" alt="<?php echo $productName; ?>" /><img src="<?php echo $productimage; ?>" alt="<?php echo $productName; ?>" />
                                                        </figure>
                                                    </a>
                                                    <?php
                                                    if ($item_discount > 0) { ?>
                                                        <div class="label_product">
                                                            <span class="label_sale"><?php echo $item_discount; ?>% OFF</span>
                                                        </div>
                                                    <?php  } ?>
                                                </div>
                                                <div class="ps-product__content">
                                                    <h5 class="ps-product__title"><a href="<?php echo  site_url() . "product/" . $query['url']; ?>"><?php echo $productName; ?></a></h5>
                                                    <div class="ps-product__meta"><span class="ps-product__price"><?php echo currency($discount_amout); ?></span>
                                                    </div>



                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                <?php  } ?>

                            </div>


                            <div class="ps-shop__more"><a href="#">Show all</a></div>
                        </div>
                    </section>
                </div>
                <section class="ps-section--reviews" data-background="img/roundbg.png">
                    <h3 class="ps-section__title"> <img src="img/quote-icon.png" alt>Latest reviews</h3>
                    <div class="ps-section__content">
                        <div class="owl-carousel" data-owl-auto="false" data-owl-loop="true" data-owl-speed="13000" data-owl-gap="0" data-owl-nav="true" data-owl-dots="true" data-owl-item="5" data-owl-item-xs="1" data-owl-item-sm="1" data-owl-item-md="3" data-owl-item-lg="5" data-owl-item-xl="5" data-owl-duration="1000" data-owl-mousedrag="on">
                        <?php
                                $data = Reviews();
                                foreach ($data as $query) { 
                                    $reviewer = $query['customer'];
                                    $description = $query['description'];
                                    $rating = $query['rating'];
                                    ?>
                        
                        <div class="ps-review">
                                <div class="ps-review__text"><?php echo $description;?></div>
                                <div class="ps-review__name"><?php echo $reviewer;?></div>
                                <div class="ps-review__review">
                                    <select class="ps-rating" data-read-only="true">
                                      <?php
                                     for($i =1; $i <= $rating; $i++) {
                                      ?>
                                    <option value="<?php echo $i;?>"><?php echo $i;?></option>
                                     <?php } ?>
                                    </select>
                                </div>
                            </div>
                                <?php } ?>
                      
                         
                           
                        </div>
                    </div>
                </section>

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