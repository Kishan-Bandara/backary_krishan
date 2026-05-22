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
    AND category_master.category_id IN(209,195,196,208,191)
    group by category_master.category_id
    ORDER BY category_master.category_id DESC', [1]);

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
    ORDER BY FIELD(item_mode, "Normal","offline","OutOfStock"),  item_id DESC limit 10', [$catgoryId]);

    return $query;
}

function TypeList()
{

    $db = new Database();
    $query = $db->getRows('SELECT * FROM type_master');
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