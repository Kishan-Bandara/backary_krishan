<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
require_once(__DIR__ . '/include/front_web_settings.php');

$siteUrl = site_url();
$db = new Database();
ensureMasterWebsiteStatusColumns($db);
$generalSettings = getGeneralSettings($db);
$frontSettings = getFrontWebSettings($db);
$siteName = trim($generalSettings['SiteName'] ?? '');
$siteName = $siteName !== '' ? $siteName : 'Bakery Shop';

$homeBannerItems = array(
    array(
        'image' => frontWebAssetUrl($siteUrl, $frontSettings['banner_one_image'] ?? '', ''),
        'link' => frontWebLinkUrl($siteUrl, $frontSettings['banner_one_button_link'] ?? '', 'search.php'),
        'title' => trim($frontSettings['banner_one_title'] ?? ''),
        'badge' => trim($frontSettings['banner_one_badge'] ?? ''),
        'button_label' => trim($frontSettings['banner_one_button_label'] ?? '')
    ),
    array(
        'image' => frontWebAssetUrl($siteUrl, $frontSettings['banner_two_image'] ?? '', ''),
        'link' => frontWebLinkUrl($siteUrl, $frontSettings['banner_two_button_link'] ?? '', 'search.php'),
        'title' => trim($frontSettings['banner_two_title'] ?? ''),
        'badge' => trim($frontSettings['banner_two_badge'] ?? ''),
        'button_label' => trim($frontSettings['banner_two_button_label'] ?? '')
    )
);
$homeBannerItems = array_values(array_filter($homeBannerItems, function ($bannerItem) {
    return
        trim((string) ($bannerItem['image'] ?? '')) !== '' ||
        trim((string) ($bannerItem['title'] ?? '')) !== '' ||
        trim((string) ($bannerItem['badge'] ?? '')) !== '' ||
        trim((string) ($bannerItem['button_label'] ?? '')) !== '';
}));

$promoBadge = trim($frontSettings['promo_badge'] ?? '');
$promoTitle = trim($frontSettings['promo_title'] ?? '');
$promoTitle = $promoTitle !== '' ? $promoTitle : 'Best Quality Bakery Products';
$promoDescription = trim($frontSettings['promo_description'] ?? '');
$promoButtonLabel = trim($frontSettings['promo_button_label'] ?? '');
$promoButtonLabel = $promoButtonLabel !== '' ? $promoButtonLabel : 'Shop Now';
$promoLinkUrl = frontWebLinkUrl($siteUrl, $frontSettings['promo_button_link'] ?? '', 'search.php');
$promoImageUrl = frontWebAssetUrl($siteUrl, $frontSettings['promo_image'] ?? '', '');
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

function homeProductImageUrl($siteUrl, array $row)
{
    return frontWebProductImageFromRow($siteUrl, $row, 'imageParth', 'item_image', 'images/product_img/defult-img.png');
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
    INNER JOIN type_master ON type_master.type_id = category_master.type_id
    INNER JOIN gorup_master ON gorup_master.group_id = type_master.group_id
    INNER JOIN item_master ON item_master.item_category = category_master.category_id
    INNER JOIN fifo ON item_master.item_id = fifo.ft_item
    WHERE item_master.item_dispay_home = 1 
    AND category_master.website_status = "Y"
    AND type_master.website_status = "Y"
    AND gorup_master.website_status = "Y"
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
    INNER JOIN type_master ON type_master.type_id = category_master.type_id
    INNER JOIN gorup_master ON gorup_master.group_id = type_master.group_id
    INNER JOIN fifo ON item_master.item_id = fifo.ft_item
    WHERE item_master.item_dispay_home = 1 AND
    category_master.website_status = "Y" AND
    type_master.website_status = "Y" AND
    gorup_master.website_status = "Y" AND
    category_master.category_id = ?
    group by fifo.ft_item 
    having sum(fifo.ft_blanace) >0 
    ORDER BY FIELD(item_mode, "Normal","offline","OutOfStock"),  item_id DESC limit 6', [$catgoryId]);

    return $query;
}

function TypeList()
{

    $db = new Database();
    $query = $db->getRows('SELECT type_master.* FROM type_master INNER JOIN gorup_master ON gorup_master.group_id = type_master.group_id WHERE type_master.website_status = ? AND gorup_master.website_status = ? ORDER BY type_master.type_name ASC', ['Y', 'Y']);
    return $query;
}

function SubCategoryList()
{

    $db = new Database();
    $query = $db->getRows('SELECT type_master.* FROM type_master INNER JOIN gorup_master ON gorup_master.group_id = type_master.group_id WHERE type_master.website_status = ? AND gorup_master.website_status = ? AND type_master.type_id IN(1,2,3,6)', ['Y', 'Y']);
    return $query;
}


?>
<!DOCTYPE html>
<html lang="en">
     <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>

<head>
    <?php include('common/styles.php'); ?>
    <style>
        @import url('https://fonts.cdnfonts.com/css/playlist');

        .ps-promo__fallback {
            min-height: 220px;
            border-radius: 12px;
            padding: 28px;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            gap: 12px;
            background: linear-gradient(135deg, #fff1df 0%, #fdbc63 100%);
            color: #4a2a12;
            box-shadow: 0 14px 32px rgba(0, 0, 0, 0.08);
        }

        .ps-promo__fallback--secondary {
            background: linear-gradient(135deg, #fde9d8 0%, #f99c8f 100%);
        }

        .ps-promo__fallback:hover {
            color: #4a2a12;
            text-decoration: none;
        }
        .ptext {
            font-size: 22px;
            line-height: 1.6em;
            color: #1e2022;
        }
        .ps-promo__fallback-badge {
            display: inline-flex;
            align-self: flex-start;
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.7);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .ps-promo__fallback-title {
            margin: 0;
            font-size: 28px;
            line-height: 1.2;
            color: inherit;
        }

        .ps-promo__fallback-cta {
            display: inline-flex;
            align-self: flex-start;
            padding: 10px 18px;
            border-radius: 999px;
            background: #ffffff;
            color: #4a2a12;
            font-weight: 700;
        }

        /* ===== Custom Categories Section ===== */
        .custom-category-section {
            padding: 0 0 60px;
            background: #fff;
        }

        /* Hero intro band: black panel with bread loaves overlapping slider */
        .ps-home,
        .ps-home--1,
        .ps-home__content,
        .ps-section--banner,
        .ps-banner--container {
            overflow: visible !important;
        }
        .ps-hero-intro {
            position: relative;
            background: #000;
            color: #fff;
            padding: 100px 0 90px;
            margin-top: -30px; /* Pull it slightly into the slider or eliminate any gap */
            margin-bottom: 60px;
            overflow: visible;
        }
        .ps-hero-intro__inner {
            position: relative;
            display: flex;
            align-items: center;
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .ps-hero-intro__text {
            flex: 1 1 50%;
            max-width: 560px;
        }
        .ps-hero-intro__eyebrow {
            display: block;
            text-align: center;
            font-family: 'Playfair Display', 'Georgia', serif;
            font-size: 14px;
            letter-spacing: 0.35em;
            text-transform: uppercase;
            color: #fff;
            margin-bottom: 8px;
            opacity: 0.85;
        }
        .ps-hero-intro__title {
            font-family: 'Playfair Display', 'Georgia', serif;
            font-size: 54px;
            line-height: 1.05;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #fff;
            margin: 0 0 28px;
            text-align: center;
            font-weight: 400;
        }
        .ps-hero-intro__title::before,
        .ps-hero-intro__title::after {
            content: '\2766';
            display: inline-block;
            margin: 0 16px;
            font-size: 26px;
            vertical-align: middle;
            opacity: 0.85;
        }
        .ps-hero-intro__copy p {
            font-size: 17px;
            line-height: 1.55;
            color: #fff;
            margin: 0 0 18px;
            font-weight: 600;
        }
        .ps-hero-intro__copy p:last-child {
            margin-bottom: 0;
        }
        .ps-hero-intro__media {
            flex: 1 1 50%;
            position: relative;
            min-height: 500px;
        }
        .ps-hero-intro__loaf {
            position: absolute;
            display: block;
            max-width: none;
            height: auto;
            filter: drop-shadow(0 15px 30px rgba(0, 0, 0, 0.4));
        }
        .ps-hero-intro__loaf--one {
            top: -240px;
            left: 8%;
            width: 360px;
            z-index: 2;
        }
        .ps-hero-intro__loaf--two {
            top: -180px;
            left: 45%;
            width: 380px;
            z-index: 1;
        }
        @media (max-width: 991px) {
            .ps-hero-intro {
                padding: 60px 0 60px;
            }
            .ps-hero-intro__inner {
                flex-direction: column;
                text-align: center;
            }
            .ps-hero-intro__title {
                font-size: 38px;
            }
            .ps-hero-intro__media {
                width: 100%;
                min-height: 480px;
                margin-top: 40px;
            }
            .ps-hero-intro__loaf--one {
                top: -140px;
                left: 10%;
                width: 320px;
            }
            .ps-hero-intro__loaf--two {
                top: -100px;
                left: 45%;
                width: 350px;
            }
        }
        @media (max-width: 575px) {
            .ps-hero-intro {
                padding: 40px 0 40px;
            }
            .ps-hero-intro__title {
                font-size: 28px;
            }
            .ps-hero-intro__title::before,
            .ps-hero-intro__title::after {
                margin: 0 8px;
                font-size: 18px;
            }
            .ps-hero-intro__copy p {
                font-size: 15px;
            }
            .ps-hero-intro__media {
                min-height: 480px;
            }
            .ps-hero-intro__loaf--one {
                top: -120px;
                left: -5%;
                width: 280px;
            }
            .ps-hero-intro__loaf--two {
                top: -90px;
                left: 35%;
                width: 300px;
            }
        }

        .custom-category-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 30px;
        }
        .custom-category-item {
            text-align: center;
            flex: 0 1 calc(25% - 22.5px);
            min-width: 200px;
        }
        .custom-category-link {
            display: block;
            text-decoration: none !important;
        }
        .custom-category-link:hover .custom-category-img-wrap img {
            transform: scale(1.05);
        }
        .custom-category-img-wrap {
            overflow: hidden;
            margin-bottom: 15px;
        }
        .custom-category-img-wrap img {
            width: 100%;
            height: auto;
            display: auto; /* Fixes any inline spacing */
            transition: transform 0.3s ease;
        }
        .custom-category-name {
            color: #1a1c18;
            font-size: 22px;
            font-weight: 700;
            margin: 0;
            text-transform: capitalize;
        }

        @media (max-width: 991px) {
            .custom-category-item {
                flex: 0 1 calc(50% - 15px);
            }
        }
        @media (max-width: 575px) {
            .custom-category-item {
                flex: 0 1 100%;
            }
            .custom-category-name {
                font-size: 20px;
            }
        }

        .ps-section--featured .ps-product__content {
            display: flex;
            flex-direction: column;
            min-height: 136px;
        }
        .ps-section--featured .ps-product__meta {
            margin-bottom: 14px;
        }
        .ps-featured-card__actions {
            margin-top: auto;
        }
        .ps-featured-addcart {
            width: 100%;
            padding: 10px 16px;
            border: 1px solid #111111;
            border-radius: 999px;
            background: #111111;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            transition: all 0.2s ease;
        }
        .ps-featured-addcart:hover,
        .ps-featured-addcart:focus {
            background: #ffffff;
            color: #111111;
            text-decoration: none;
            outline: none;
        }
        .ps-featured-addcart[disabled],
        .ps-featured-addcart.is-loading {
            opacity: 0.7;
            cursor: wait;
        }
        .ps-featured-addcart.is-added {
            background: #ffffff;
            color: #111111;
        }
        .ps-featured-addcart.is-error {
            background: #ffffff;
            color: #b42318;
            border-color: #b42318;
        }

        .slider-custom-text {
            position: absolute;
            top: 50%;
            left: 10%;
            transform: translateY(-50%);
            font-size: 110px;
            font-weight: normal;
            color: #000000;
            text-shadow: 3px 3px 8px rgba(0, 0, 0, 0.4), 1px 1px 2px rgba(255, 255, 255, 0.7);
            z-index: 10;
            font-family: 'Playlist', 'Playlist Caps', 'Caveat', cursive !important;
            max-width: 600px;
            line-height: 1;
        }

        @media (max-width: 991px) {
            .slider-custom-text {
                font-size: 72px;
                left: 5%;
            }
        }
        @media (max-width: 575px) {
            .slider-custom-text {
                font-size: 48px;
                left: 5%;
            }
        }
    </style>
</head>

<body>
    <div class="ps-page">
        <?php include('common/header_home.php'); ?>
        <div class="ps-home ps-home--1">

            <section class="ps-section--banner ps-banner--container">
               <div class="container2">
  
  <div id="myCarousel" class="carousel slide" data-ride="carousel">
 

    <!-- Wrapper for slides -->
    <div class="carousel-inner">
        <?php

                    $data4 = mainSlider();
                    $i=0;
                    foreach ($data4 as $query) {
                        $i = $i+1;
                    if($i==1){
                        $active = "active";
                    }else{
                        $active = "";
                    }
                    $sliderImage = $siteUrl . ltrim((string) ($query['path'] ?? ''), '/') . ($query['image'] ?? '');
                    $sliderLink = frontWebLinkUrl($siteUrl, trim((string) ($query['link'] ?? '')), '#');
                    
                    ?>
      <div class="item <?php echo $active;?>">
        <?php if ($sliderLink !== '#') { ?>
        <a href="<?php echo htmlspecialchars($sliderLink); ?>">
        <?php } ?>
        <img class="ps-banner__image" src="<?php echo htmlspecialchars($sliderImage); ?>" alt="<?php echo htmlspecialchars($siteName); ?>" />
        <?php if ($i == 1) { ?>
        <div class="slider-custom-text">
            <i>The perfect pizza base</i>
        </div>
        <?php } ?>
        <?php if ($sliderLink !== '#') { ?>
        </a>
        <?php } ?>
      </div>
 <?php } ?>
     
    </div>

    <!-- Left and right controls -->
    <a class="left carousel-control" href="#myCarousel" data-slide="prev">
      <span class="glyphicon glyphicon-chevron-left"></span>
      <span class="sr-only">Previous</span>
    </a>
    <a class="right carousel-control" href="#myCarousel" data-slide="next">
      <span class="glyphicon glyphicon-chevron-right"></span>
      <span class="sr-only">Next</span>
    </a>
  </div>
</div>
            </section>
            <div class="ps-home__content">
                <div class="ps-hero-intro">
                    <div class="ps-hero-intro__inner">
                        <div class="ps-hero-intro__text">
                            <span class="ps-hero-intro__eyebrow">&#10086;</span>
                            <h2 class="ps-hero-intro__title">Gluten Free<br>Artisan Bakers</h2>
                            <div class="ps-hero-intro__copy">
                                <p>We believe in baking delicious, fresh, bakery products, free from preservatives and additives. All our products are handcrafted in a gluten free environment at our bakery.</p>
                                <p>We are artisan bakers, passionately working with different flours, creating new recipes for your enjoyment.</p>
                            </div>
                        </div>
                        <div class="ps-hero-intro__media">
                            <img class="ps-hero-intro__loaf ps-hero-intro__loaf--one" src="<?php echo site_url(); ?>img/Precinct_Loaf_Fruit_VIS_LR_v2.png" alt="Gluten Free Fruit Loaf" />
                            <img class="ps-hero-intro__loaf ps-hero-intro__loaf--two" src="<?php echo site_url(); ?>img/Precinct_Loaf_Buckwheat_VIS_LR.png" alt="Gluten Free Buckwheat and Chia Loaf" />
                        </div>
                    </div>
                </div>

                <div class="container">
                   
                    <section class="custom-category-section">
                        <div class="custom-category-grid">
                            <?php
                                $hardcodedCategories = [
                                    ['type_name' => 'GFP', 'image' => 'gfp.png', 'clean_url' => 'gfp'],
                                    ['type_name' => 'STRADA', 'image' => 'strada.jpg', 'clean_url' => 'strada'],
                                    ['type_name' => 'PALIO', 'image' => 'palio.png', 'clean_url' => 'palio']
                                ];
                                foreach ($hardcodedCategories as $query) {
                                    $CategoryName = $query['type_name'];
                                    $CategoryImage = $query['image'];
                                    $clean_url = $query['clean_url'];
                                ?>
                                <div class="custom-category-item">
                                    <a class="custom-category-link" href="<?php echo site_url()."products/".$clean_url?>">
                                        <div class="custom-category-img-wrap">
                                            <img src="<?php echo site_url()."img/category/" . $CategoryImage; ?>" alt="<?php echo htmlspecialchars($CategoryName); ?>">
                                        </div>
                                        <h3 class="custom-category-name"><?php echo htmlspecialchars($CategoryName); ?></h3>
                                    </a>
                                </div>
                                <?php } ?>
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

                                    $productimage = homeProductImageUrl($siteUrl, $query);

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
                                                    <figure><img style="" src="<?php echo $productimage; ?>" alt="<?php echo $productName; ?>" /><img style="" src="<?php  echo $productimage; ?>" alt="<?php echo $productName; ?>" />
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


					<?php if (!empty($homeBannerItems)) { ?>
					<div class="ps-promo">
                        <div class="row">
                            <?php foreach ($homeBannerItems as $bannerIndex => $bannerItem) { ?>
                            <div class="col-12 col-md-6">
                                <div class="ps-promo__item">
                                    <?php if (trim((string) ($bannerItem['image'] ?? '')) !== '') { ?>
                                    <a href="<?php echo htmlspecialchars($bannerItem['link']); ?>">
                                        <img class="ps-promo__banner" src="<?php echo htmlspecialchars($bannerItem['image']); ?>" alt="<?php echo htmlspecialchars($bannerItem['title'] !== '' ? $bannerItem['title'] : $siteName); ?>" />
                                    </a>
                                    <?php } else { ?>
                                    <a class="ps-promo__fallback<?php echo $bannerIndex % 2 === 1 ? ' ps-promo__fallback--secondary' : ''; ?>" href="<?php echo htmlspecialchars($bannerItem['link']); ?>">
                                        <?php if (trim((string) ($bannerItem['badge'] ?? '')) !== '') { ?>
                                            <span class="ps-promo__fallback-badge"><?php echo htmlspecialchars($bannerItem['badge']); ?></span>
                                        <?php } ?>
                                        <h3 class="ps-promo__fallback-title"><?php echo htmlspecialchars($bannerItem['title'] !== '' ? $bannerItem['title'] : $siteName); ?></h3>
                                        <?php if (trim((string) ($bannerItem['button_label'] ?? '')) !== '') { ?>
                                            <span class="ps-promo__fallback-cta"><?php echo htmlspecialchars($bannerItem['button_label']); ?></span>
                                        <?php } ?>
                                    </a>
                                    <?php } ?>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
					<?php } ?>
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
                                            <div class="ps-banner" style="background:#FD8D27;"><?php if ($promoImageUrl !== '') { ?><img class="ps-banner__overlay" src="<?php echo htmlspecialchars($promoImageUrl); ?>" alt="<?php echo htmlspecialchars($promoTitle); ?>" /><?php } ?>
                                                <div class="ps-banner__block">
                                                    <div class="ps-banner__content">
                                                        <h2 class="ps-banner__title"><?php echo nl2br(htmlspecialchars($promoTitle)); ?></h2>
                                                        <?php if ($promoBadge !== '') { ?>
                                                        <div class="ps-banner__btn-group">
                                                            <div class="ps-banner__btn"><img src="img/icon/icon11.png" alt="alt" /><?php echo htmlspecialchars($promoBadge); ?></div>
                                                        </div>
                                                        <?php } ?>
                                                        <?php if ($promoDescription !== '') { ?>
                                                        <p style="max-width: 320px; margin-bottom: 18px;"><?php echo nl2br(htmlspecialchars($promoDescription)); ?></p>
                                                        <?php } ?>
                                                        <a class="bg-white ps-banner__shop" href="<?php echo htmlspecialchars($promoLinkUrl); ?>"><?php echo htmlspecialchars($promoButtonLabel); ?></a>
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

                                                $productimage = homeProductImageUrl($siteUrl, $query);

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
                                                                <figure><img style="" src="<?php echo $productimage; ?>" alt="<?php echo $productName; ?>" /><img style="" src="<?php echo $productimage; ?>" alt="<?php echo $productName; ?>" /></figure>
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

                                    $itemId = (int) ($query['item_id'] ?? 0);
                                    $productName = $query['item_name'];
                                    $productPrice =  ($query['item_normal_selling_price']) ? $query['item_normal_selling_price'] : 0.00;
                                    $others_selling_price = ($query['others_selling_price']) ? $query['others_selling_price'] : 0.00;
                                    $item_discount = ($query['item_discount']) ? $query['item_discount'] : 0.00;
                                    $isDiscountHas = $query['item_promotion_status'];
                                    $productImage = $query['item_image'];
                                    $imagepath = $query['imageParth'];

                                    $productimage = homeProductImageUrl($siteUrl, $query);

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
                                                        <figure><img style="" src="<?php echo $productimage; ?>" alt="<?php echo $productName; ?>" /><img style="" src="<?php echo $productimage; ?>" alt="<?php echo $productName; ?>" />
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

                                                    <div class="ps-featured-card__actions">
                                                        <button
                                                            type="button"
                                                            class="ps-featured-addcart js-featured-addcart"
                                                            data-item-id="<?php echo $itemId; ?>"
                                                            data-default-label="Add to Cart"
                                                            onclick="var button=this;if(button.disabled||button.classList.contains('is-loading')){return false;}var defaultLabel=button.getAttribute('data-default-label')||button.textContent.trim();button.setAttribute('data-default-label',defaultLabel);button.disabled=true;button.classList.remove('is-added','is-error');button.classList.add('is-loading');button.textContent='Adding...';fetch('<?php echo site_url(); ?>process/add-item-session.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},body:'item_id=<?php echo $itemId; ?>&quantity=1'}).then(function(response){return response.json();}).then(function(response){var isSuccess=response&&(response.status===true||response.status===1||response.status==='1'||response.status==='true');button.classList.remove('is-loading');if(isSuccess){button.classList.add('is-added');button.textContent='Added';}else{button.classList.add('is-error');button.textContent='Try Again';}window.setTimeout(function(){button.disabled=false;button.classList.remove('is-added','is-error');button.textContent=defaultLabel;},1600);}).catch(function(){button.classList.remove('is-loading');button.classList.add('is-error');button.textContent='Try Again';window.setTimeout(function(){button.disabled=false;button.classList.remove('is-error');button.textContent=defaultLabel;},1600);});return false;"
                                                            <?php echo $itemId <= 0 ? 'disabled' : ''; ?>>
                                                            Add to Cart
                                                        </button>
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