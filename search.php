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
ensureMasterWebsiteStatusColumns($db);

function masterRouteValue(array $row, $idKey, $slugKey = 'clean_url')
{
    $cleanUrl = trim((string) ($row[$slugKey] ?? ''));
    if ($cleanUrl !== '') {
        return $cleanUrl;
    }

    return trim((string) ($row[$idKey] ?? ''));
}

function productRouteValue(array $row)
{
    $routeValue = trim((string) ($row['url'] ?? ''));
    if ($routeValue !== '') {
        return $routeValue;
    }

    return trim((string) ($row['item_id'] ?? ''));
}

function Categories()
{

    $db = new Database();
    $query = $db->getRows('SELECT * FROM type_master WHERE website_status = ? ORDER BY type_name ASC', ['Y']);
    return $query;
}

function subCategories($typeId)
{

    $db = new Database();
    $query = $db->getRows('SELECT cat.* FROM category_master cat INNER JOIN type_master typ ON typ.type_id = cat.type_id WHERE cat.type_id = ? AND cat.website_status = ? AND typ.website_status = ? ORDER BY cat.category_name ASC', [$typeId, 'Y', 'Y']);
    return $query;
}
$category_name = 0;
$categoryId = 0;
$subCategory = 0;

function url_segment($segment)
{


    if (isset($_GET['cat'])) {

        $url = $_GET['cat'];
        $segment_array = explode('/', $url);

        if (isset($segment_array[$segment - 1])) {

            return $segment_array[$segment - 1];
        }
    }
}



if (isset($_GET['cat'])) {


    $cat_id = url_segment(2);
    $cat_id = preg_replace('/[^A-Za-z0-9\-]/', ' ', $cat_id ?? '');
    $cat_id = preg_replace('/\s+/', ' ', $cat_id);
    $cat_id = trim($cat_id);

    $sub_cat_id = url_segment(3);
    $sub_cat_id = preg_replace('/[^A-Za-z0-9\-]/', ' ', $sub_cat_id ?? '');
    $sub_cat_id = preg_replace('/\s+/', ' ', $sub_cat_id);
    $sub_cat_id = trim($sub_cat_id);

    if ($cat_id === '') {
        $cat_id = preg_replace('/[^A-Za-z0-9\-]/', ' ', (string) ($_GET['cat'] ?? ''));
        $cat_id = preg_replace('/\s+/', ' ', $cat_id);
        $cat_id = trim($cat_id);
    }

    if ($sub_cat_id === '') {
        $sub_cat_id = preg_replace('/[^A-Za-z0-9\-]/', ' ', (string) ($_GET['subCat'] ?? ''));
        $sub_cat_id = preg_replace('/\s+/', ' ', $sub_cat_id);
        $sub_cat_id = trim($sub_cat_id);
    }
}

(isset($_GET["search_query"])) ? $src_key1  = $_GET["search_query"] : $src_key1 = 0;
(isset($_GET["search_query"])) ? $customer_id = "%" . $_GET['search_query'] . "%" : $customer_id = 0;
$cat_id = !empty($cat_id) ? $cat_id : 0;
$sub_cat = !empty($sub_cat_id) ? $sub_cat_id : ((isset($_GET["subCat"])) ? $_GET["subCat"] : 0);
(isset($_GET["page"])) ? $page = $_GET["page"] : $page = 0;


if ($cat_id) {

    if (ctype_digit((string) $cat_id)) {
        $query_category = $db->getRow('SELECT * FROM type_master WHERE type_id = ? AND website_status = ?', [(int) $cat_id, 'Y']);
    } else {
        $query_category = $db->getRow('SELECT * FROM type_master WHERE clean_url = ? AND website_status = ?', [$cat_id, 'Y']);
    }
    (isset($query_category['type_name'])) ? $category_name = $query_category['type_name'] : $category_name = 0;
    (isset($query_category['type_id'])) ? $categoryId = $query_category['type_id'] : $categoryId = 0;
}


if ($sub_cat) {

    if (ctype_digit((string) $sub_cat)) {
        $query_sub_category = $db->getRow('SELECT cat.* FROM category_master cat INNER JOIN type_master typ ON typ.type_id = cat.type_id WHERE cat.category_id = ? AND cat.website_status = ? AND typ.website_status = ?', [(int) $sub_cat, 'Y', 'Y']);
    } else {
        $query_sub_category = $db->getRow('SELECT cat.* FROM category_master cat INNER JOIN type_master typ ON typ.type_id = cat.type_id WHERE cat.clean_url = ? AND cat.website_status = ? AND typ.website_status = ?', [$sub_cat, 'Y', 'Y']);
    }
    $sub_category_name = $query_sub_category['category_name'] ?? 0;
    $sub_category_cat_id = $query_sub_category['type_id'] ?? 0;
    $subCategory = $query_sub_category['category_id'] ?? 0;


    $query_category_cat_query = $db->getRow('SELECT * FROM type_master WHERE type_id = ? AND website_status = ?', [$sub_category_cat_id, 'Y']);
    $sub_category_cat_name = $query_category_cat_query['type_name'] ?? 0;
}


if (isset($_GET['src'])) {


    $src = $_GET['src'];

    $src_key = "%" .  $src . "%";
}



function getProducts()
{

    global $categoryId;
    global $subCategory;
    global $src_key;
    $page_no = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $row_per_page = 50;
    $start = ($page_no - 1) * $row_per_page;

    $query = "SELECT DISTINCT i.* FROM item_master i
    WHERE i.item_active = ?
    AND (
        (
            i.item_type IS NOT NULL
            AND i.item_type <> 0
            AND EXISTS (
                SELECT 1
                FROM type_master typ
                WHERE typ.type_id = i.item_type
                AND typ.website_status = ?
            )
            AND (
                i.item_category IS NULL
                OR i.item_category = 0
                OR EXISTS (
                    SELECT 1
                    FROM category_master cat
                    WHERE cat.category_id = i.item_category
                    AND cat.website_status = ?
                )
            )
        )
        OR EXISTS (
            SELECT 1
            FROM ItemMapping im
            INNER JOIN type_master typ ON typ.type_id = im.typeId
            LEFT JOIN category_master cat ON cat.category_id = im.categoryId
            WHERE im.itemId = i.item_id
            AND typ.website_status = ?
            AND (
                im.categoryId = 0
                OR cat.category_id IS NULL
                OR cat.website_status = ?
            )
        )
    )";
    $params = ['Y', 'Y', 'Y', 'Y', 'Y'];

    if ($categoryId) {
        $query .= " AND (
            i.item_type = ?
            OR EXISTS (
                SELECT 1
                FROM ItemMapping im
                WHERE im.itemId = i.item_id
                AND im.typeId = ?
            )
        )";
        $params[] = $categoryId;
        $params[] = $categoryId;
    }

    if ($subCategory) {
        $query .= " AND (
            i.item_category = ?
            OR EXISTS (
                SELECT 1
                FROM ItemMapping im
                WHERE im.itemId = i.item_id
                AND im.categoryId = ?
            )
        )";
        $params[] = $subCategory;
        $params[] = $subCategory;
    }

    if (!empty($src_key)) {
        $query .= " AND i.item_name LIKE ?";
        $params[] = $src_key;
    }

    $query .= " ORDER BY FIELD(i.item_mode, 'Normal','offline','OutOfStock'), i.item_id LIMIT {$start}, {$row_per_page}";

    $db = new database();
    $queryRun = $db->getRows($query, $params);
    return $queryRun;
}

function getProductCount()
{
    global $categoryId;
    global $subCategory;
    global $src_key;

    $query = "SELECT COUNT(DISTINCT i.item_id) as count FROM item_master i
    WHERE i.item_active = ?
    AND (
        (
            i.item_type IS NOT NULL
            AND i.item_type <> 0
            AND EXISTS (
                SELECT 1
                FROM type_master typ
                WHERE typ.type_id = i.item_type
                AND typ.website_status = ?
            )
            AND (
                i.item_category IS NULL
                OR i.item_category = 0
                OR EXISTS (
                    SELECT 1
                    FROM category_master cat
                    WHERE cat.category_id = i.item_category
                    AND cat.website_status = ?
                )
            )
        )
        OR EXISTS (
            SELECT 1
            FROM ItemMapping im
            INNER JOIN type_master typ ON typ.type_id = im.typeId
            LEFT JOIN category_master cat ON cat.category_id = im.categoryId
            WHERE im.itemId = i.item_id
            AND typ.website_status = ?
            AND (
                im.categoryId = 0
                OR cat.category_id IS NULL
                OR cat.website_status = ?
            )
        )
    )";
    $params = ['Y', 'Y', 'Y', 'Y', 'Y'];

    if ($categoryId) {
        $query .= " AND (
            i.item_type = ?
            OR EXISTS (
                SELECT 1
                FROM ItemMapping im
                WHERE im.itemId = i.item_id
                AND im.typeId = ?
            )
        )";
        $params[] = $categoryId;
        $params[] = $categoryId;
    }

    if ($subCategory) {
        $query .= " AND (
            i.item_category = ?
            OR EXISTS (
                SELECT 1
                FROM ItemMapping im
                WHERE im.itemId = i.item_id
                AND im.categoryId = ?
            )
        )";
        $params[] = $subCategory;
        $params[] = $subCategory;
    }

    if (!empty($src_key)) {
        $query .= " AND i.item_name LIKE ?";
        $params[] = $src_key;
    }

    $db = new Database();
    $queryRowCount = $db->getRow($query, $params);

    return (int) ($queryRowCount['count'] ?? 0);
}

if ($page) {

    $page_no = $_GET['page'];
} else {

    $page_no = 1;
}

$row_per_page = 10;

$start = ($page_no - 1) * $row_per_page;

$total_rows = getProductCount();


// first page
$first = '<a href="?page=1" class="Next">First </i></a>';

// last page
$last_page_no = ceil($total_rows / $row_per_page);

$last = '<a href="?page=' . $last_page_no . '" class="Previous">Last</a>';

// next page
if ($page_no >= $last_page_no) {
    $next = "<li>Next</li>";
} else {
    $next_page_no = $page_no + 1;
    $next = '<li class="active"><a href="?page=' . $next_page_no . '">Next</a></li>';
}

// previous page
if ($page_no <= 1) {
    $prev = "<li>Previous</li>";
} else {
    $prev_page_no = $page_no - 1;
    $prev =  '<li class="active"><a href="?page=' . $prev_page_no . '">Prev</a></li>';
}


?>
<!DOCTYPE html>
<html lang="en">


<head>
    <?php include('common/styles.php'); ?>
    <style>
        /* ===== Black & White Theme - Search/Category Page ===== */
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
        .ps-categogy.ps-categogy--separate { padding-top: 24px; padding-bottom: 60px; }

        .ps-breadcrumb {
            list-style: none; padding: 0; margin: 0 0 22px;
            display: flex; gap: 6px; font-size: 12px;
            text-transform: uppercase; letter-spacing: .14em; color: var(--bw-muted);
        }
        .ps-breadcrumb__item a { color: var(--bw-muted); text-decoration: none; }
        .ps-breadcrumb__item.active { color: var(--bw-text); font-weight: 600; }
        .ps-breadcrumb__item + .ps-breadcrumb__item::before { content: '/'; margin-right: 6px; color: #ccc; }

        .ps-categogy__filter a {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 18px; border: 1px solid var(--bw-border);
            border-radius: 999px; color: var(--bw-text); font-size: 12px;
            text-transform: uppercase; letter-spacing: .14em; font-weight: 600;
            background: #fff; text-decoration: none;
        }
        .ps-categogy__filter a:hover { background: var(--bw-accent); color: #fff; border-color: var(--bw-accent); }

        /* Sidebar widget */
        .ps-categogy--separate .ps-categogy__main {
            background: transparent;
            margin-bottom: 0;
            padding-top: 8px;
        }
        .ps-categogy--separate .ps-categogy__main .container {
            overflow: visible;
        }
        .ps-categogy--separate .ps-categogy__widget {
            position: static;
            transform: none;
            width: 100%;
            max-width: 100%;
            height: auto;
            overflow: visible;
            z-index: 1;
        }
        .ps-categogy__widget {
            background: var(--bw-card);
            border: 1px solid var(--bw-border);
            border-radius: 4px;
            padding: 22px;
        }
        .ps-widget__title {
            font-size: 13px; font-weight: 700; color: var(--bw-text);
            text-transform: uppercase; letter-spacing: .14em;
            margin: 0 0 16px; padding-bottom: 10px;
            border-bottom: 1px solid var(--bw-line);
        }
        .ps-widget__category .menu--mobile,
        .ps-widget__category .menu--mobile ul { list-style: none; padding: 0; margin: 0; }
        .ps-widget__category .menu--mobile > li {
            border-bottom: 1px solid var(--bw-line);
        }
        .ps-widget__category .menu--mobile > li > a {
            display: block; padding: 12px 0; color: var(--bw-text);
            font-weight: 600; font-size: 14px; text-decoration: none;
        }
        .ps-widget__category .menu--mobile > li > a:hover { color: var(--bw-muted); }
        .ps-widget__category .sub-menu li a {
            display: block; padding: 6px 0 6px 14px; color: var(--bw-muted);
            font-size: 13px; text-decoration: none;
        }
        .ps-widget__category .sub-menu li a:hover { color: var(--bw-text); }
        .sub-toggle { float: right; cursor: pointer; color: #999; }

        /* Product grid */
        .ps-categogy--separate .ps-categogy__product,
        .ps-categogy--separate .ps-categogy__main.active .ps-categogy__product {
            padding-left: 0;
        }
        .ps-categogy__product { padding-top: 0; }
        .ps-categogy--separate .ps-product--standard {
            margin: 0 10px 20px;
        }
        .ps-categogy--separate .ps-product--standard .ps-product__content {
            display: flex;
            flex-direction: column;
            min-height: 136px;
        }
        .ps-categogy--separate .ps-product--standard .ps-product__meta {
            margin-bottom: 14px;
        }
        .ps-categogy--separate .ps-product--standard .ps-product__thumbnail figure img {
            width: 100% !important;
            height: 260px !important;
            object-fit: cover;
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

        /* Pagination */
        .ps-pagination .pagination {
            display: flex; gap: 6px; padding: 30px 0; list-style: none; margin: 0;
        }
        .ps-pagination .pagination li a,
        .ps-pagination .pagination li span {
            display: inline-block; padding: 8px 14px;
            border: 1px solid var(--bw-border); color: var(--bw-text);
            text-decoration: none; border-radius: 4px; font-size: 13px;
            font-weight: 600;
        }
        .ps-pagination .pagination li.active a {
            background: var(--bw-accent); color: #fff; border-color: var(--bw-accent);
        }

        .ps-delivery {
            background: var(--bw-card); border: 1px solid var(--bw-border);
            border-radius: 4px; padding: 18px 22px; margin-top: 18px;
        }
        .ps-delivery__content {
            display: flex; align-items: center; justify-content: space-between; gap: 14px;
        }
        .ps-delivery__more {
            color: var(--bw-text); font-weight: 600; font-size: 12px;
            text-transform: uppercase; letter-spacing: .14em; text-decoration: underline;
        }

        @media (max-width: 575px) {
            .ps-product--standard .ps-product__thumbnail figure img { height: 200px !important; }
        }

        @media (min-width: 992px) {
            .ps-categogy--separate .ps-categogy__main .container {
                display: flex;
                align-items: flex-start;
                gap: 28px;
            }
            .ps-categogy--separate .ps-categogy__widget {
                flex: 0 0 280px;
                position: sticky;
                top: 24px;
                max-height: calc(100vh - 48px);
                overflow-y: auto;
            }
            .ps-categogy--separate .ps-categogy__product {
                flex: 1 1 auto;
                min-width: 0;
            }
        }
    </style>
</head>

<body>
    <div class="ps-page">
        <?php include('common/header.php'); ?>
        <div class="ps-categogy ps-categogy--separate">
            <div class="container">
                <ul class="ps-breadcrumb">
                    <li class="ps-breadcrumb__item"><a href="index.php">Home</a></li>
                    <li class="ps-breadcrumb__item active" aria-current="page">Shop</li>
                </ul>
                <div class="ps-categogy__content">
                    <div class="ps-categogy__wrapper">
                        <div class="ps-categogy__filter"> <a href="#" id="collapse-filter"><i class="fa fa-filter"></i><i class="fa fa-times"></i>Filter</a></div>
                    
                       
                      
                    </div>
                </div>
            </div>
            <div class="ps-categogy__main">
                <div class="container">
                    <div class="ps-categogy__widget"><a href="#" id="close-widget-product"><i class="fa fa-times"></i></a>
                        <div class="ps-widget ps-widget--product">
                            <div class="ps-widget__block">
                                <h4 class="ps-widget__title">Categories</h4><a class="ps-block-control" href="#"><i class="fa fa-angle-down"></i></a>
                                <div class="ps-widget__content ps-widget__category">
                                    <ul class="menu--mobile">
                                    <?php
                                        $data = Categories();
                                        foreach ($data as $query) {

                                            $typeRoute = masterRouteValue($query, 'type_id');

                                            if ($typeRoute === '') {
                                                continue;
                                            }


                                        ?>

                                    <li><a href="<?php echo site_url() . "products/" . $typeRoute; ?>"><?php echo $query['type_name']; ?></a></li>
                                        <?php } ?>
                                    </ul>
                                </div>
                            </div>
                          
                        </div>
                    </div>
                    <div class="ps-categogy__product">
                        <div class="row m-0">
                        <?php

$dataNewProduct = getProducts();

foreach ($dataNewProduct as $query) {

    $productName = $query['item_name'];
    $itemId = (int) ($query['item_id'] ?? 0);
    $productRoute = productRouteValue($query);
    $productPrice =  ($query['item_normal_selling_price']) ? $query['item_normal_selling_price'] : 0.00;
    $others_selling_price = ($query['others_selling_price']) ? $query['others_selling_price'] : 0.00;
    $item_discount = ($query['item_discount']) ? $query['item_discount'] : 0.00;
    $isDiscountHas = $query['item_promotion_status'];
    $productImage = $query['item_image'];
    $imagepath = $query['imageParth'];
    $productDescription = $query['item_discription'];
    if ($productImage) {
        $cleanPath = trim((string)$imagepath, " /\\");
        $cleanName = ltrim((string)$productImage, "/\\");
        $productimage = ($cleanPath !== '' ? $cleanPath . "/" : "") . $cleanName;
    } else {
        $productimage = "images/product_img/defult-img.png";
    }
    
    if ($item_discount > 0) {
        $discount_amout = (($productPrice) * $item_discount) / 100;
        $discount_amout = $productPrice - $discount_amout;
    } else {
        $discount_amout =  $productPrice;
    }

    $productHref = site_url() . "product/" . rawurlencode($productRoute);



?>
                            <div class="col-6 col-lg-4 p-0">
                                <div class="ps-product ps-product--standard">
                                    <div class="ps-product__thumbnail"><a class="ps-product__image" href="<?php echo $productHref; ?>">
                                            <figure><img src="<?php echo site_url() . $productimage; ?>" alt="<?php echo htmlspecialchars($productName); ?>" onerror="this.onerror=null;this.src='<?php echo site_url(); ?>images/product_img/defult-img.png';" /><img src="<?php echo site_url() . $productimage; ?>" alt="<?php echo htmlspecialchars($productName); ?>" onerror="this.onerror=null;this.src='<?php echo site_url(); ?>images/product_img/defult-img.png';" />
                                            </figure>
                                        </a>
                                        <?php if ($item_discount > 0) { ?>
                                            <div class="ps-product__badge">
                                                <div class="ps-badge ps-badge--hot"><?php echo $item_discount; ?>% OFF</div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                    <div class="ps-product__content">
                                        <h5 class="ps-product__title"><a href="<?php echo $productHref; ?>"><?php echo $productName; ?></a></h5>
                                        <div class="ps-product__meta"><span class="ps-product__price sale"><?php echo currency($discount_amout); ?></span><br>
                                        <?php if ($item_discount > 0) { ?>
                                            <span class="ps-product__del"><?php echo currency($productPrice); ?></span>
                                        <?php } ?>
                                        </div>
                                        <div class="ps-featured-card__actions">
                                            <button
                                                type="button"
                                                class="ps-featured-addcart"
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
                            <?php  } ?>
                        </div>
                        <div class="ps-pagination">
                            <ul class="pagination">
                            <?php echo $prev; ?>
                               <?php echo $next; ?>
                            </ul>
                        </div>
                        <div class="ps-delivery" data-background="img/promotion/banner-delivery-2.jpg">
                            <div class="ps-delivery__content">
                                <div class="ps-delivery__text"> <i class="icon-shield-check"></i><span> <strong>100% Secure delivery </strong>without contacting the courier</span></div><a class="ps-delivery__more" href="#">More</a>
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