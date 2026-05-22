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
    <style>
        .ps-account {
            padding: 80px 0;
            background-color: #fafafa;
        }
        .auth-card {
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            padding: 50px 40px;
            max-width: 500px;
            margin: 0 auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }
        .auth-card__title {
            font-family: 'Playfair Display', 'Georgia', serif;
            font-size: 32px;
            font-weight: 400;
            text-align: center;
            letter-spacing: 0.02em;
            color: #111;
            margin-bottom: 30px;
            text-transform: uppercase;
        }
        .auth-card .ps-form__group {
            margin-bottom: 25px;
        }
        .auth-card .ps-form__label {
            font-size: 14px;
            font-weight: 600;
            color: #111;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 10px;
            display: block;
        }
        .auth-card .ps-form__input {
            height: 50px;
            border: 1px solid #d1d1d1;
            border-radius: 0;
            font-size: 15px;
            color: #111;
            padding: 0 15px;
            background: #fafafa;
        }
        .auth-card .ps-form__input:focus {
            border-color: #111;
            background: #fff;
            box-shadow: none;
        }
        .auth-card .input-group-append {
            border: 1px solid #d1d1d1;
            border-left: none;
            background: #fafafa;
            display: flex;
            align-items: center;
            padding: 0 15px;
        }
        .auth-card .input-group-append .fa {
            color: #6b6b6b;
            font-size: 18px;
            text-decoration: none;
        }
        .auth-card .ps-btn--warning {
            width: 100%;
            height: 50px;
            background: #111;
            color: #fff;
            border: none;
            border-radius: 0;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        .auth-card .ps-btn--warning:hover {
            background: #333;
            color: #fff;
        }
        .auth-card .ps-form__submit {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            margin-top: 10px;
        }
        .auth-card .ps-form__submit .ps-btn,
        .auth-card .ps-form__submit button {
            display: block !important;
            width: 100% !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        .auth-card .ps-account__link {
            display: block;
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #6b6b6b;
            text-decoration: underline;
        }
        .auth-card .ps-account__link:hover {
            color: #111;
        }
        .auth-card__divider {
            text-align: center;
            margin: 30px 0;
            position: relative;
        }
        .auth-card__divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            border-top: 1px solid #e5e5e5;
            z-index: 1;
        }
        .auth-card__divider span {
            position: relative;
            z-index: 2;
            background: #fff;
            padding: 0 15px;
            color: #6b6b6b;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
        .auth-card__register-btn {
            display: block;
            width: 100%;
            height: 50px;
            line-height: 48px;
            text-align: center;
            border: 1px solid #111;
            color: #111;
            background: transparent;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .auth-card__register-btn:hover {
            background: #111;
            color: #fff;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="ps-page">
        <?php include('common/header.php'); ?>
        <div class="ps-account">
            <div class="container">
                <div class="auth-card">
                    <form id="login-frm" method="post" autocomplete="off">
                        <h2 class="auth-card__title">Login</h2>
                        <div class="ps-form__group">
                            <label class="ps-form__label">Email address *</label>
                            <input class="form-control ps-form__input" type="email" name="email" id="email" autocomplete="username" required>
                        </div>
                        <div class="ps-form__group">
                            <label class="ps-form__label">Password *</label>
                            <div class="input-group">
                                <input class="form-control ps-form__input" type="password" name="password" id="password" autocomplete="current-password" required>
                                <div class="input-group-append">
                                    <a class="fa fa-eye-slash toogle-password" href="javascript:void(0);"></a>
                                </div>
                            </div>
                        </div>
                        <div id="output"></div>
                        <div class="ps-form__submit">
                            <button type="submit" class="ps-btn ps-btn--warning">Log in</button>
                        </div>
                        <a class="ps-account__link" href="lost-password.php">Lost your password?</a>
                    </form>
                    
                    <div class="auth-card__divider">
                        <span>New Customer?</span>
                    </div>
                    
                    <a href="register.php" class="auth-card__register-btn">Register Now</a>
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
<script type="text/javascript">
    $(document).ready(function() {
        $(document).on('submit', '#login-frm', function() {


            var data = $(this).serialize();


            $.ajax({

                type: 'POST',
                url: 'process/login.php',
                data: data,
                success: function(response) {

                    var jsonobj = JSON.parse(response);
                    $("#output").fadeIn(1000, function() {
                        $("#output").html('<div class="' + jsonobj.class + '"><strong>' + jsonobj.message_title + '</strong>' + jsonobj.message + '</div>');

                    });

                    if (jsonobj.status == 1) {


                        setTimeout(' window.location.href = "index.php"; ', 1000);

                    }



                }
            });
            return false;
        });

    });
</script>