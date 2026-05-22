<style>
/* ===== Custom Footer Design ===== */
.custom-footer {
    background-color: #ffffff;
    border-top: 1px solid #eaeaea;
    position: relative;
    padding: 0;
    font-family: inherit;
}
.custom-footer-container {
    display: flex;
    flex-wrap: wrap;
    max-width: 1200px;
    margin: 0 auto;
    width: 100%;
    align-items: stretch;
}
.custom-footer-left {
    flex: 0 0 35%;
    max-width: 35%;
    display: flex;
    align-items: center;
    justify-content: center;
    background-image: url('<?php echo site_url(); ?>img/footer_bg.png');
    background-size: cover;
    background-position: center center;
    background-repeat: no-repeat;
    padding: 40px;
    min-height: 300px;
}
.custom-footer-left img {
    max-width: 100%;
    height: auto;
    object-fit: contain;
    position: relative;
    z-index: 2;
}
.custom-footer-right {
    flex: 0 0 65%;
    max-width: 65%;
    padding: 60px 40px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.custom-footer-form-wrapper {
    margin-bottom: 50px;
    max-width: 500px;
}
.custom-footer-form-wrapper h4 {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 20px;
    color: #000;
}
.custom-footer-form .form-group {
    display: flex;
    margin-bottom: 10px;
}
.custom-footer-form .form-control {
    background-color: #e5e5e5;
    border: none;
    border-radius: 0;
    height: 45px;
    padding: 10px 15px;
    font-size: 14px;
    box-shadow: none;
    flex: 1;
}
.custom-footer-form .submit-btn {
    background-color: #999999;
    border: none;
    color: #000;
    font-size: 20px;
    font-weight: bold;
    width: 50px;
    height: 45px;
    cursor: pointer;
    transition: background-color 0.3s;
}
.custom-footer-form .submit-btn:hover {
    background-color: #777777;
}
.custom-footer-info-wrapper {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
}
.custom-footer-contact h5 {
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 5px;
    color: #000;
}
.custom-footer-contact p {
    font-size: 14px;
    color: #333;
    line-height: 1.5;
    margin: 0;
}
.custom-footer-social-copy {
    text-align: right;
}
.custom-footer-social {
    margin-bottom: 15px;
}
.custom-footer-social a {
    color: #000;
    font-size: 28px;
    margin-left: 15px;
    transition: color 0.3s;
    text-decoration: none;
}
.custom-footer-social a:hover {
    color: #555;
}
.custom-footer-copy {
    font-size: 13px;
    color: #666;
    margin: 0;
}

@media (max-width: 767px) {
    .custom-footer-left {
        flex: 0 0 100%;
        max-width: 100%;
        padding: 30px 20px 0;
    }
    .custom-footer-right {
        flex: 0 0 100%;
        max-width: 100%;
        padding: 40px 20px;
    }
    .custom-footer-info-wrapper {
        flex-direction: column;
        align-items: flex-start;
    }
    .custom-footer-social-copy {
        text-align: left;
        margin-top: 30px;
    }
    .custom-footer-social a {
        margin-left: 0;
        margin-right: 15px;
    }
}
</style>

<footer class="custom-footer">
    <div class="custom-footer-container">
        <div class="custom-footer-left">
            <img src="<?php echo $logo; ?>" alt="<?php echo htmlspecialchars($SiteName !== '' ? $SiteName : 'Site Logo'); ?>">
        </div>
        <div class="custom-footer-right">
            
            <div class="custom-footer-form-wrapper">
                <h4>Wholesale Enquiries</h4>
                <form class="custom-footer-form" action="#" method="POST">
                    <div class="form-group mb-2">
                        <input type="text" class="form-control" placeholder="Full Name" name="name" required>
                    </div>
                    <div class="form-group mb-0" style="display: flex;">
                        <input type="email" class="form-control" placeholder="Email Address" name="email" required>
                        <button type="submit" class="submit-btn">&gt;</button>
                    </div>
                </form>
            </div>

            <div class="custom-footer-info-wrapper">
                <div class="custom-footer-contact">
                    <h5><?php echo htmlspecialchars($SiteName !== '' ? $SiteName : 'GF Precinct Pty Ltd.'); ?></h5>
                    <p>
                        <?php echo nl2br(htmlspecialchars($system_address !== '' ? $system_address : "31 Parkhurst Drive,\nKnoxfield, Victoria, 3180")); ?><br>
                        <?php echo htmlspecialchars((!empty($system_email)) ? $system_email : 'admin@gfprecinct.com.au'); ?><br>
                        <?php echo htmlspecialchars((!empty($system_contactUs)) ? $system_contactUs : '(03) 9837 5943'); ?>
                    </p>
                </div>
                <div class="custom-footer-social-copy">
                    <div class="custom-footer-social">
                        <a href="#" target="_blank"><i class="fa fa-instagram"></i></a>
                        <a href="#" target="_blank"><i class="fa fa-facebook-square"></i></a>
                    </div>
                    <p class="custom-footer-copy">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($SiteName !== '' ? $SiteName : 'GF Precinct Pty Ltd.'); ?></p>
                </div>
            </div>

        </div>
    </div>
</footer>
<div class="ps-search">
    <div class="ps-search__content ps-search--mobile"><a class="ps-search__close" href="#" id="close-search"><i
                class="icon-cross"></i></a>
        <h3>Search</h3>
        <form action="<?php echo site_url(); ?>search.php" method="GET">
            <div class="ps-search-table">
                <div class="input-group">
                    <input class="form-control ps-input" type="text" name="src" id="search_query" autocomplete="off" placeholder="Search for products">
                    <div class="input-group-append"><a href="javascript:void(0)" class="search_query"><i class="fa fa-search"></i></a></div>
                </div>
            </div>
        </form>

    </div>
</div>
<div class="ps-navigation--footer">
    <div class="ps-nav__item"><a href="#" id="open-menu"><i class="icon-menu"></i></a><a href="#" id="close-menu"><i
                class="icon-cross"></i></a></div>
    <div class="ps-nav__item"><a href="<?php echo site_url(); ?>index.php"><i class="icon-home2"></i></a></div>
    <div class="ps-nav__item"><a href="<?php echo site_url(); ?>account.php"><i class="icon-user"></i></a></div>
    <div class="ps-nav__item"><a href="<?php echo site_url(); ?>cart.php"><i class="icon-cart-empty"></i></a></div>
</div>
<div class="ps-menu--slidebar">
    <div class="ps-menu__content">
           <ul class="menu--mobile">
            <?php
            $canBuildTypeMenu = function_exists('GetTypeMenu')
                && function_exists('GetTypeMenuSegment')
                && function_exists('GetCategoryMenu')
                && function_exists('GetCategoryMenuSegment');

            if ($canBuildTypeMenu) {
                $typeMenuItems = GetTypeMenu();
                foreach ($typeMenuItems as $query) {
                    $typeId = (int) ($query['type_id'] ?? 0);
                    $typeName = trim((string) ($query['type_name'] ?? ''));
                    $typeSegment = GetTypeMenuSegment($query);
                    $subMenuQuery = $typeId > 0 ? GetCategoryMenu($typeId) : array();

                    if ($typeName === '' || $typeSegment === '0') {
                        continue;
                    }

            ?>

                <li class="menu-item-has-children"><a href="<?php echo site_url() . 'products/' . $typeSegment; ?>"><?php echo htmlspecialchars($typeName, ENT_QUOTES, 'UTF-8'); ?></a><?php if (!empty($subMenuQuery)) { ?><span class="sub-toggle"><i class="fa fa-chevron-down"></i></span><?php } ?>
                    <?php if (!empty($subMenuQuery)) { ?>
                        <ul class="sub-menu">
                            <?php foreach ($subMenuQuery as $subMenu) {
                                $categoryName = trim((string) ($subMenu['category_name'] ?? ''));
                                $categorySegment = GetCategoryMenuSegment($subMenu);
                                if ($categoryName === '' || $categorySegment === '0') {
                                    continue;
                                }
                            ?>
                                <li><a href="<?php echo site_url() . 'products/' . $typeSegment . '/' . $categorySegment; ?>"><?php echo htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8'); ?></a></li>
                            <?php } ?>
                        </ul>
                    <?php } ?>
                </li>
            <?php
                }
            }

            $fallbackMenuItems = array(
                array('label' => 'About Us', 'url' => site_url() . 'page.php?name=About-us'),
                array('label' => 'Products', 'url' => site_url() . 'search.php'),
                array('label' => 'Find Us', 'url' => site_url() . 'page.php?name=Contact-us'),
                array('label' => 'Contact Us', 'url' => site_url() . 'page.php?name=Contact-us')
            );

            foreach ($fallbackMenuItems as $menuItem) {
            ?>
            <li class="menu-item-has-children"><a href="<?php echo htmlspecialchars($menuItem['url'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($menuItem['label'], ENT_QUOTES, 'UTF-8'); ?></a></li>
            <?php } ?>
        </ul>
    </div>

    <div class="ps-menu__footer">
        <div class="ps-menu__item">
            <ul class="ps-language-currency">
                <li class="menu-item-has-children"><a href="<?php echo site_url(); ?>account.php">My Account</a>
                </li>
                <li class="menu-item-has-children">
                    <div class="ps-language-currency">
                        <?php if (isset($_SESSION['LoginStatus'])) { ?>
                        <a class="" style="font-size: 14px;color: #5b6c8f;padding: 12px 0;"
                            href="<?php echo site_url(); ?>logout.php?logout=777">LOGOUT</a>
                        <?php } ?>
                    </div>
                </li>
            </ul>
        </div>
        <div class="ps-menu__item">
            <div class="ps-menu__contact">Need help? <strong><?php echo $system_contactUs; ?></strong></div>
        </div>
    </div>
</div>
<button class="btn scroll-top"><i class="fa fa-angle-double-up"></i></button>
<div class="ps-preloader" id="preloader">
    <div class="ps-preloader-section ps-preloader-left"></div>
    <div class="ps-preloader-section ps-preloader-right"></div>
</div>

<script>
document.addEventListener('click', function(event) {
    var trigger = event.target.closest('.search_query');
    if (!trigger) {
        return;
    }

    event.preventDefault();
    var form = trigger.closest('form');
    if (form) {
        form.submit();
    }
});
</script>