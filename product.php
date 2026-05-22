<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');

define('DEFAULT_PRODUCT_IMAGE_URL', 'https://upload.wikimedia.org/wikipedia/commons/6/65/No-Image-Placeholder.svg?utm_source=commons.wikimedia.org&utm_campaign=index&utm_content=original');
 
function productMediaUrl($siteUrl, $imagePath, $imageName, $fallback = DEFAULT_PRODUCT_IMAGE_URL)
{
    $imagePath = trim(str_replace('\\', '/', (string) $imagePath));
    $imageName = trim(str_replace('\\', '/', (string) $imageName));

    if ($imageName !== '' && preg_match('#^(https?:)?//#i', $imageName)) {
        return $imageName;
    }

    if ($imagePath !== '' && preg_match('#^(https?:)?//#i', $imagePath)) {
        return rtrim($imagePath, '/') . ($imageName !== '' ? '/' . ltrim($imageName, '/') : '');
    }

    $relativePath = '';
    if ($imagePath !== '' && $imageName !== '') {
        $relativePath = rtrim($imagePath, '/') . '/' . ltrim($imageName, '/');
    } elseif ($imageName !== '') {
        $relativePath = ltrim($imageName, '/');
    } elseif ($imagePath !== '') {
        $relativePath = ltrim($imagePath, '/');
    }

    if ($relativePath === '') {
        $relativePath = ltrim($fallback, '/');
    }

    if (preg_match('#^(https?:)?//#i', $relativePath)) {
        return $relativePath;
    }

    return rtrim($siteUrl, '/') . '/' . ltrim($relativePath, '/');
}

function productRouteSegment(array $productRow)
{
    $routeSegment = trim((string) ($productRow['url'] ?? ''));

    if ($routeSegment !== '') {
        return $routeSegment;
    }

    return (string) ($productRow['item_id'] ?? '');
}

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
$siteUrl = site_url();

$productName = '';
$productCode = '';
$itemId = 0;
$productPrice = 0.00;
$others_selling_price = 0.00;
$item_discount = 0.00;
$item_specification = '';
$item_warranty = '';
$item_mode = 'OutOfStock';
$itemSubCat = 0;
$immediate_pickup = 'No';
$discount_amout = 0.00;
$Category = '';
$estimate_delivery_date = date('Y-m-d', strtotime('+2 days'));
$stock_avalibility = '<span class="ps-badge ps-badge--sold">Sold out</span>';
$buy_button = '<button class="ps-btn ps-btn--warning" type="button" disabled="" style="background: gainsboro;color: white;">Out of Stock</button>';
$stock_immediate_pickup = '<span class="availability_Pickups demotbl" id="border-ani-table" style="background:#750d0d;"></span>';
$productImagePath = '';
$productImageName = '';
$productRouteValue = trim((string) ($_GET['url'] ?? ''));


if ($productRouteValue === '') {
    Redirect($siteUrl . 'search.php');
}

$numericProductId = ctype_digit($productRouteValue) ? (int) $productRouteValue : 0;
$checkProductQuery = $db->getRow(
    'SELECT im.*, iw.warranty
     FROM item_master im
     LEFT JOIN item_warranty iw ON iw.warranty_id = im.item_warranty
     WHERE im.url = ? OR im.item_id = ?
     LIMIT 1',
    [$productRouteValue, $numericProductId]
);

if (!$checkProductQuery || empty($checkProductQuery['item_id'])) {
    Redirect($siteUrl . 'search.php');
}

$productName = (string) ($checkProductQuery['item_name'] ?? '');
$productCode = (string) ($checkProductQuery['item_code'] ?? '');
$itemId = (int) ($checkProductQuery['item_id'] ?? 0);
$productPrice = !empty($checkProductQuery['item_normal_selling_price']) ? (float) $checkProductQuery['item_normal_selling_price'] : 0.00;
$others_selling_price = !empty($checkProductQuery['others_selling_price']) ? (float) $checkProductQuery['others_selling_price'] : 0.00;
$item_discount = !empty($checkProductQuery['item_discount']) ? (float) $checkProductQuery['item_discount'] : 0.00;
$item_specification = (string) ($checkProductQuery['item_discription'] ?? '');
$item_warranty = (string) ($checkProductQuery['warranty'] ?? '');
$item_mode = (string) ($checkProductQuery['item_mode'] ?? 'OutOfStock');
$itemSubCat = (int) ($checkProductQuery['item_category'] ?? 0);
$immediate_pickup = (string) ($checkProductQuery['immediate_pickups'] ?? 'No');
$productImagePath = (string) ($checkProductQuery['imageParth'] ?? '');
$productImageName = (string) ($checkProductQuery['item_image'] ?? '');

$discount_amout = $productPrice;
if ($item_discount > 0) {
    $discount_amout = $productPrice - (($productPrice * $item_discount) / 100);
}

$getCategory = $itemSubCat > 0 ? $db->getRow('SELECT * FROM category_master WHERE category_id = ?', [$itemSubCat]) : array();
$Category = (string) ($getCategory['category_name'] ?? '');

if ($item_mode === 'Normal') {
    $stock_avalibility = '<span class="ps-badge ps-badge--leftstock">in Stock</span>';
    $buy_button = '<button data-item-id="' . $itemId . '" class="ps-btn ps-btn--warning" type="button" id="btnSubmit">Buy Now</button>';
}

if ($immediate_pickup === 'Yes') {
    $stock_immediate_pickup = '<span class="availability_Pickups demotbl" id="border-ani-table">Immediate Pickup</span>';
}

function getProductImages()
{
    global $itemId, $productImagePath, $productImageName, $siteUrl;

    if (empty($itemId)) {
        return array(productMediaUrl($siteUrl, '', '', DEFAULT_PRODUCT_IMAGE_URL));
    }

    $db = new database();
    $query = $db->getRows('SELECT imagePath, image FROM productimages WHERE itemId = ? ORDER BY Id DESC', [$itemId]);

    $images = array();

    if ($productImageName !== '') {
        $images[] = productMediaUrl($siteUrl, $productImagePath, $productImageName, DEFAULT_PRODUCT_IMAGE_URL);
    }

    foreach ($query as $row) {
        $images[] = productMediaUrl($siteUrl, $row['imagePath'] ?? '', $row['image'] ?? '');
    }

    if (empty($images)) {
        $images[] = productMediaUrl($siteUrl, $productImagePath, $productImageName, DEFAULT_PRODUCT_IMAGE_URL);
    }

    return array_values(array_unique(array_filter($images)));
}

function getBanks()
{
    global $itemId;

    if (empty($itemId)) {
        return array();
    }

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

    if (empty($itemId)) {
        return array();
    }

    $db = new database();
    $query = $db->getRows('SELECT * FROM item_specification WHERE product_id = ? ORDER BY Id ASC', [$itemId]);

    return $query;
}

$productImages = getProductImages();
$itemSpecifications = itemSpecification();





?>
<!DOCTYPE html>
<html lang="en">


<head>
    <?php include('common/styles.php'); ?>
    <style>
        /* ===== Black & White Theme - Product Page ===== */
        :root {
            --bw-bg: #fafafa;
            --bw-card: #ffffff;
            --bw-text: #111111;
            --bw-muted: #6b6b6b;
            --bw-border: #e5e5e5;
            --bw-line: #ededed;
            --bw-accent: #000000;
        }
        body { background: var(--bw-bg); color: var(--bw-text); }
        .ps-page--product6 { padding: 30px 0 60px; }

        /* Card wrapper */
        .ps-product--full {
            background: var(--bw-card);
            padding: 40px;
            border-radius: 4px;
            border: 1px solid var(--bw-border);
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
            margin-top: 0;
        }

        /* Gallery */
        .ps-product--gallery { width: 100%; }
        .ps-product--gallery .ps-product__thumbnail {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            border: 1px solid var(--bw-border);
            background: #f4f4f4;
            border-radius: 4px;
            overflow: hidden;
            position: relative;
        }
        .ps-product--gallery .ps-product__thumbnail .ps-product__image {
            display: none;
        }
        .ps-product--gallery .ps-product__thumbnail .ps-product__image:first-child,
        .ps-product--gallery .ps-product__thumbnail.slick-initialized .ps-product__image {
            display: block;
        }
        .ps-product--gallery .ps-product__thumbnail .ps-product__image {
            background: #ffffff;
            text-align: center;
        }
        .ps-product--gallery .ps-product__thumbnail .ps-product__image img {
            display: block;
            width: 100%;
            height: 480px;
            object-fit: contain;
            background: #ffffff;
            padding: 18px;
            transition: transform .5s ease;
        }
        .ps-product--gallery .ps-product__thumbnail:hover .ps-product__image img { transform: scale(1.04); }
        .ps-product--gallery .ps-gallery--image {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            margin-top: 14px;
        }
        .ps-product--gallery .ps-gallery--image:not(.slick-initialized) {
            display: flex !important;
            gap: 8px;
            flex-wrap: wrap;
        }
        .ps-product--gallery .ps-gallery--image .ps-gallery__item {
            border-radius: 4px;
            overflow: hidden;
            border: 2px solid var(--bw-border);
            background: #f4f4f4;
            padding: 0;
            margin: 0 6px 0 0;
            cursor: pointer;
            transition: border-color .2s ease;
        }
        .ps-product--gallery .ps-gallery--image:not(.slick-initialized) .ps-gallery__item {
            width: 78px;
            flex: 0 0 78px;
            margin: 0;
        }
        .ps-product--gallery .ps-gallery--image .ps-gallery__item:hover,
        .ps-product--gallery .ps-gallery--image .slick-current .ps-gallery__item,
        .ps-product--gallery .ps-gallery--image .ps-gallery__item.is-active {
            border-color: var(--bw-accent);
        }
        .ps-product--gallery .ps-gallery--image img {
            width: 100%;
            height: 78px;
            object-fit: cover;
            display: block;
        }
        .ps-product--gallery .slick-track {
            margin-left: 0;
        }

        /* Product info */
        .ps-product__badge { margin-bottom: 10px; }
        .ps-badge.ps-badge--leftstock {
            display: inline-block; padding: 4px 12px; border-radius: 999px;
            background: #111; color: #fff; font-size: 11px; font-weight: 600;
            letter-spacing: .12em; text-transform: uppercase;
        }
        .ps-badge.ps-badge--sold {
            display: inline-block; padding: 4px 12px; border-radius: 999px;
            background: #eee; color: #555; font-size: 11px; font-weight: 600;
            letter-spacing: .12em; text-transform: uppercase;
        }
        .ps-product__branch a {
            background: transparent;
            color: var(--bw-muted);
            padding: 0;
            font-size: 12px; font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .15em;
            display: inline-block;
            margin-bottom: 12px;
        }
        .ps-product__title { margin-bottom: 18px; }
        .ps-product__title a {
            font-size: 30px;
            font-weight: 700;
            color: var(--bw-text);
            line-height: 1.25;
            letter-spacing: -0.01em;
        }
        .ps-product__title a:hover { color: var(--bw-text); opacity: .75; }

        .ps-product__meta {
            display: flex; align-items: center; gap: 14px;
            padding: 16px 0;
            border-top: 1px solid var(--bw-line);
            border-bottom: 1px solid var(--bw-line);
            margin-bottom: 24px;
        }
        .ps-product__price.sale {
            font-size: 26px; font-weight: 700; color: var(--bw-text);
        }
        .ps-product__del {
            background: var(--bw-accent); color: #fff;
            padding: 4px 10px; border-radius: 4px;
            font-size: 12px; font-weight: 700;
            letter-spacing: .08em;
        }

        /* Quantity + buy */
        .ps-product__quantity {
            background: transparent;
            padding: 0;
            border-radius: 0;
            margin-bottom: 30px;
            border: none;
        }
        .ps-product__quantity h6 {
            color: var(--bw-text);
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: .14em;
            margin-bottom: 14px;
        }
        .def-number-input {
            background: #fff;
            border-radius: 4px;
            border: 1px solid var(--bw-border);
            overflow: hidden;
            display: inline-flex !important;
            align-items: center;
            width: auto;
        }
        .def-number-input input.quantity {
            width: 60px; text-align: center; border: none; background: transparent;
            font-size: 16px; font-weight: 600; color: var(--bw-text);
            -moz-appearance: textfield;
            appearance: textfield;
        }
        .def-number-input input.quantity::-webkit-outer-spin-button,
        .def-number-input input.quantity::-webkit-inner-spin-button {
            -webkit-appearance: none; margin: 0;
        }
        .def-number-input button {
            background: transparent;
            color: var(--bw-text);
            border: none;
            padding: 10px 14px;
            cursor: pointer;
            transition: background .2s;
        }
        .def-number-input button:hover { background: #f0f0f0; }

        .ps-product__quantity .ps-btn,
        .ps-product__quantity .ps-btn--warning {
            background: var(--bw-accent) !important;
            color: #fff !important;
            border: 2px solid var(--bw-accent) !important;
            border-radius: 999px;
            padding: 12px 38px;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: .14em;
            transition: all .25s ease;
            box-shadow: none;
            margin-left: 18px;
        }
        .ps-product__quantity .ps-btn:hover {
            background: #fff !important;
            color: var(--bw-accent) !important;
            transform: translateY(-1px);
        }
        .ps-product__quantity .ps-btn[disabled] {
            background: #e8e8e8 !important; color: #999 !important;
            border-color: #e8e8e8 !important; cursor: not-allowed;
        }

        /* Tabs */
        .ps-tab-list {
            border-bottom: 1px solid var(--bw-border);
            margin-bottom: 24px;
            display: flex;
            gap: 4px;
        }
        .ps-tab-list .nav-link {
            font-size: 13px;
            font-weight: 600;
            color: var(--bw-muted);
            background: transparent;
            border: none;
            padding: 12px 20px;
            border-bottom: 2px solid transparent;
            text-transform: uppercase;
            letter-spacing: .14em;
            transition: all .2s;
        }
        .ps-tab-list .nav-link.active,
        .ps-tab-list .nav-link:hover {
            color: var(--bw-text);
            border-bottom-color: var(--bw-accent);
        }
        .ps-desc { font-size: 15px; line-height: 1.75; color: #444; }

        .ps-table.ps-table--oriented th {
            background: #fafafa; color: var(--bw-text);
            font-weight: 600; padding: 12px 16px;
            border: 1px solid var(--bw-border);
        }
        .ps-table.ps-table--oriented td {
            padding: 12px 16px; border: 1px solid var(--bw-border); color: #444;
        }

        /* SKU + Social */
        .ps-product__list { list-style: none; padding: 0; margin: 18px 0; }
        .ps-product__list .ps-list__title { font-weight: 600; color: var(--bw-text); margin-right: 6px; }
        .ps-product__list .ps-list__text { color: var(--bw-muted); }
        .ps-product__social { margin-top: 18px; }
        .ps-social.ps-social--color li a {
            background: #fff; color: var(--bw-text);
            border: 1px solid var(--bw-border);
            width: 36px; height: 36px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 999px; transition: all .2s;
        }
        .ps-social.ps-social--color li a:hover {
            background: var(--bw-accent); color: #fff; border-color: var(--bw-accent);
        }

        @media (max-width: 991px) {
            .ps-product--full { padding: 24px; }
            .ps-product--gallery .ps-product__thumbnail .ps-product__image img { height: 360px; }
            .ps-product__title a { font-size: 24px; }
            .ps-product__quantity .ps-btn { margin-left: 0; margin-top: 14px; display: block; }
        }
    </style>
</head>

<body>
    <div class="ps-page">
        <?php include('common/header.php'); ?>
        <div class="ps-page--product6 ">
            <hr>
            <div class="container">

                <div class="ps-page__content">
                    <div class="ps-product--detail ps-product--full">
                        <div class="row">
                            <div class="col-12 col-xl-5">
                                <div class="ps-product--gallery">
                                    <div class="ps-product__thumbnail">
                                        <?php foreach ($productImages as $img) { ?>
                                            <a class="ps-product__image" href="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>">
                                                <img src="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($productName, ENT_QUOTES, 'UTF-8'); ?>" onerror="this.onerror=null;this.src=<?php echo json_encode(DEFAULT_PRODUCT_IMAGE_URL); ?>;" />
                                            </a>
                                        <?php } ?>
                                    </div>
                                    <?php if (!empty($productImages)) { ?>
                                    <div class="ps-gallery--image">
                                        <?php foreach ($productImages as $img) { ?>
                                            <div class="ps-gallery__item">
                                                <img src="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($productName, ENT_QUOTES, 'UTF-8'); ?>" onerror="this.onerror=null;this.src=<?php echo json_encode(DEFAULT_PRODUCT_IMAGE_URL); ?>;" />
                                            </div>
                                        <?php } ?>
                                    </div>
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="col-12 col-xl-7">
                                <div class="ps-product__info">
                                    <div class="ps-product__badge">
                                        <?php echo $stock_avalibility; ?>

                                    </div>
                                    <div class="ps-product__branch"><a href="#"><?php echo $Category; ?></a></div>
                                    <div class="ps-product__title"><a href="#"><?php echo $productName; ?></a></div>

                                    <div class="ps-product__meta"><span class="ps-product__price sale"><?php echo currency($discount_amout); ?></span>
                                        <?php if ($item_discount > 0) { ?>
                                            <span class="ps-product__del"><?php echo number_format($item_discount, 0) . "% OFF"; ?></span>
                                        <?php } ?>
                                    </div>

                                    <div class="ps-product__quantity">
                                        <h6>Quantity</h6>
                                       
                                            <div class="d-md-flex align-items-center">
                                                <div class="def-number-input number-input safari_only">
                                                    <button class="minus" onclick="this.parentNode.querySelector('input[type=number]').stepDown()"><i class="icon-minus"></i></button>
                                                    <input class="quantity" min="0" name="quantity" value="1" type="number" />
                                                    <button class="plus" onclick="this.parentNode.querySelector('input[type=number]').stepUp()"><i class="icon-plus"></i></button>
                                                </div>
                                                <input type="hidden" name="item_id" value="<?php echo $itemId; ?>">
                                                <?php echo $buy_button; ?>
                                            </div>
                                        
                                    </div>
                                    <div class="ps-product__content">
                                        <ul class="nav nav-tabs ps-tab-list" id="productContentTabs" role="tablist">
                                            <li class="nav-item" role="presentation"><a class="nav-link active" id="description-tab" data-toggle="tab" href="#description-content" role="tab" aria-controls="description-content" aria-selected="true">Description</a></li>
                                            <li class="nav-item" role="presentation"><a class="nav-link" id="specification-tab" data-toggle="tab" href="#specification-content" role="tab" aria-controls="specification-content" aria-selected="false">Specification</a></li>
                                        </ul>
                                        <div class="tab-content" id="productContent">
                                            <div class="tab-pane fade show active" id="description-content" role="tabpanel" aria-labelledby="description-tab">
                                                <p class="ps-desc"><?php echo $item_specification;; ?> </p>
                                            </div>
                                            <div class="tab-pane fade" id="specification-content" role="tabpanel" aria-labelledby="specification-tab">
                                                <table class="table ps-table ps-table--oriented">
                                                    <tbody>
                                                        <?php if (empty($itemSpecifications)) { ?>
                                                            <tr>
                                                                <th class="ps-table__th">Info</th>
                                                                <td>No specifications available.</td>
                                                            </tr>
                                                        <?php } ?>
                                                        <?php foreach ($itemSpecifications as $query) { ?>
                                                            <tr>
                                                                <th class="ps-table__th"><?php echo limit_text($query['key'], 20); ?></th>
                                                                <td><?php echo limit_text($query['value'], 10); ?></td>
                                                            </tr>
                                                        <?php } ?>
                                                    </tbody>
                                                </table>
                                            </div>

                                        </div>
                                    </div>
                                    <div class="ps-product__type">
                                        <ul class="ps-product__list">

                                            <li> <span class="ps-list__title">SKU: </span><a class="ps-list__text" href="#"><?php echo $productCode; ?></a>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="ps-product__social">
                                        <ul class="ps-social ps-social--color">
                                            <li><a class="ps-social__link facebook" href="#"><i class="fa fa-facebook"> </i><span class="ps-tooltip">Facebook</span></a></li>
                                            <li><a class="ps-social__link twitter" href="#"><i class="fa fa-twitter"></i><span class="ps-tooltip">Twitter</span></a></li>
                                            <li><a class="ps-social__link pinterest" href="#"><i class="fa fa-pinterest-p"></i><span class="ps-tooltip">Pinterest</span></a></li>
                                            <li class="ps-social__linkedin"><a class="ps-social__link linkedin" href="#"><i class="fa fa-linkedin"></i><span class="ps-tooltip">Linkedin</span></a></li>
                                            <li class="ps-social__reddit"><a class="ps-social__link reddit-alien" href="#"><i class="fa fa-reddit-alien"></i><span class="ps-tooltip">Reddit Alien</span></a></li>
                                            <li class="ps-social__email"><a class="ps-social__link envelope" href="#"><i class="fa fa-envelope-o"></i><span class="ps-tooltip">Email</span></a></li>
                                            <li class="ps-social__whatsapp"><a class="ps-social__link whatsapp" href="#"><i class="fa fa-whatsapp"></i><span class="ps-tooltip">WhatsApp</span></a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
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
</body>


</html>
<script>
    (function($) {
        function initProductGalleryFallback() {
            var $gallery = $('.ps-product--gallery');
            var $main = $gallery.find('.ps-product__thumbnail');
            var $thumbs = $gallery.find('.ps-gallery--image');

            if (!$main.length) {
                return;
            }

            $main.css({ display: 'block', visibility: 'visible', opacity: 1 });

            if ($thumbs.length) {
                $thumbs.css({ display: 'block', visibility: 'visible', opacity: 1 });
            }

            if (typeof $.fn.slick === 'function' && !$main.hasClass('slick-initialized')) {
                if ($thumbs.length && !$thumbs.hasClass('slick-initialized')) {
                    $main.slick({
                        slidesToShow: 1,
                        slidesToScroll: 1,
                        arrows: false,
                        dots: false,
                        lazyLoad: 'ondemand',
                        asNavFor: '.ps-gallery--image'
                    });

                    $thumbs.slick({
                        slidesToShow: Math.min($thumbs.find('.ps-gallery__item').length, 5),
                        slidesToScroll: 1,
                        lazyLoad: 'ondemand',
                        asNavFor: '.ps-product--gallery .ps-product__thumbnail',
                        dots: false,
                        arrows: false,
                        focusOnSelect: true
                    });

                    $thumbs.find('.slick-slide').removeClass('slick-active');
                    $thumbs.find('.slick-slide').eq(0).addClass('slick-active');

                    $main.on('beforeChange.productGalleryFix', function(event, slick, currentSlide, nextSlide) {
                        $thumbs.find('.slick-slide').removeClass('slick-active');
                        $thumbs.find('.slick-slide').eq(nextSlide).addClass('slick-active');
                    });
                } else {
                    $main.slick({
                        slidesToShow: 1,
                        slidesToScroll: 1,
                        arrows: false,
                        dots: false,
                        lazyLoad: 'ondemand'
                    });
                }

                return;
            }

            if ($thumbs.length && !$main.hasClass('slick-initialized')) {
                $thumbs.find('.ps-gallery__item').off('click.productGalleryFix').on('click.productGalleryFix', function() {
                    var $selected = $(this).find('img');
                    var $current = $main.find('.ps-product__image:first-child img');

                    if ($selected.length && $current.length) {
                        $current.attr('src', $selected.attr('src'));
                        $current.attr('alt', $selected.attr('alt') || '');
                    }

                    $thumbs.find('.ps-gallery__item').removeClass('is-active');
                    $(this).addClass('is-active');
                });

                $thumbs.find('.ps-gallery__item').removeClass('is-active').eq(0).addClass('is-active');
            }
        }

        $(window).on('load', initProductGalleryFallback);
    })(jQuery);

    $('document').ready(function() {

       
            $("#btnSubmit").click(function(){
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
                   
                    var jsonobj = JSON.parse(data);

                    $('#product_order_text_message').text(jsonobj.message);
                    $('#product_order_text_title').text(jsonobj.title);
                   // $('#myModal').modal('show');
                    location.reload();


                }
            });



            return false;

        });


    });

    $('document').ready(function() {

        $("#coninue").click(function() {

           location.reload();

        });


    });
</script>