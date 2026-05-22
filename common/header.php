<?php

session_start();
include('include/check_login.php');
include('include/IpTracker.php');
$db = new Database();

$total = 0;
$linenumber = 0;
if (!empty($_SESSION['SBCScart'])) {
    foreach ($_SESSION['SBCScart'] as $SBCSitem) {
        if ($SBCSitem['quantity'] != 0) {
            $pricedecimal = (float) str_replace(",", ".", $SBCSitem['price']);
            $qtydecimal = (float) str_replace(",", ".", $SBCSitem['quantity']);
            $discount_value = ($SBCSitem['price'] * ($SBCSitem['item_discount'] * $SBCSitem['quantity'])) / 100;
            $totaldecimal = $pricedecimal * $qtydecimal - $discount_value;
            $total += $totaldecimal;
            $linenumber++;
        }
    }
}

$query_general_settings = $db->getRow('SELECT * FROM general_settings LIMIT 1');
$SiteName = $query_general_settings['SiteName'];
$logo = site_url() . $query_general_settings['logo'];
$system_email = $query_general_settings['system_email'];
$system_contactUs = $query_general_settings['contactUs'];
$maintainMode = $query_general_settings['maintainMode'];
$system_address = $query_general_settings['address'];
if ($maintainMode == 1) {
    echo "<script type='text/javascript'>window.location.href = 'maintenance.php';</script>";
}

$homeHeaderProductsLink = site_url() . 'search.php';
$homeHeaderArtisanLink = site_url() . 'page.php?name=Artisan';
$homeHeaderLocationsLink = site_url() . 'page.php?name=Contact-us';
$homeHeaderPickupLink = site_url() . 'cart.php';
$homeHeaderFaqLink = site_url() . 'page.php?name=FAQs';
$homeHeaderContactLink = site_url() . 'page.php?name=Contact-us';
$homeHeaderStoryLink = site_url() . 'page.php?name=About-us';
?>
<style>
    /* ===== Sonoma-style solid header (subpages) ===== */
    .ps-home-header {
        position: relative; /* no overlap */
        z-index: 1000;
        padding: 22px 40px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #1a1a1a; /* solid color */
        color: #ffffff;
    }
    .ps-home-header.is-scrolled {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        width: 100%;
        background: rgba(20, 20, 20, 0.92);
        backdrop-filter: blur(6px);
        padding: 14px 40px;
        animation: slideDown .3s ease;
        box-shadow: 0 2px 16px rgba(0,0,0,0.25);
    }
    @keyframes slideDown { from { transform: translateY(-100%); } to { transform: translateY(0); } }

    .ps-home-header__logo img {
        height: 56px;
        width: auto;
        border-radius: 6px;
    }

    .ps-home-header__nav {
        display: flex;
        align-items: center;
        gap: 48px;
    }
    .ps-home-header__nav a {
        color: #ffffff;
        font-size: 13px;
        font-weight: 600;
        letter-spacing: 0.18em;
        text-transform: uppercase;
        text-decoration: none;
        transition: opacity .2s;
    }
    .ps-home-header__nav a:hover { opacity: 0.7; color: #fff; text-decoration: none; }

    .ps-home-header__right {
        display: flex;
        align-items: center;
        gap: 32px;
    }

    .ps-home-header__menu-btn,
    .ps-home-header__menu-btn:focus {
        background: transparent;
        border: 0;
        padding: 6px;
        cursor: pointer;
        outline: none;
        color: #fff;
    }
    .ps-home-header__menu-btn span {
        display: block;
        width: 28px;
        height: 2px;
        background: #fff;
        margin: 5px 0;
        transition: all .2s;
    }

    .ps-home-header__pickup {
        color: #fff;
        font-size: 13px;
        font-weight: 600;
        letter-spacing: 0.18em;
        text-transform: uppercase;
        text-decoration: none;
    }
    .ps-home-header__pickup:hover { color: #fff; opacity: 0.7; text-decoration: none; }
    .ps-home-header__pickup .arrow { display: inline-block; transform: translateY(-1px); margin-left: 4px; }

    .ps-home-header__account {
        color: #fff;
        font-size: 13px;
        font-weight: 600;
        letter-spacing: 0.18em;
        text-transform: uppercase;
        text-decoration: none;
    }
    .ps-home-header__account:hover { color: #fff; opacity: 0.7; text-decoration: none; }

    /* Full-screen overlay menu */
    .ps-home-overlay {
        position: fixed;
        inset: 0;
        background: #8a8a72;
        z-index: 2000;
        opacity: 0;
        visibility: hidden;
        transition: opacity .3s ease, visibility .3s ease;
        overflow-y: auto;
        padding: 22px 40px 60px;
        color: #fff;
    }
    .ps-home-overlay.is-open { opacity: 1; visibility: visible; }
    .ps-home-overlay__top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 90px;
    }
    .ps-home-overlay__close {
        background: transparent;
        border: 0;
        color: #fff;
        font-size: 36px;
        line-height: 1;
        cursor: pointer;
        padding: 6px 12px;
    }
    .ps-home-overlay__body {
        display: grid;
        grid-template-columns: 1.4fr 1fr;
        gap: 60px;
        max-width: 1100px;
        margin: 0 auto;
    }
    .ps-home-overlay__primary a {
        display: block;
        color: #fff;
        font-size: 56px;
        font-weight: 700;
        line-height: 1.4;
        text-decoration: none;
        letter-spacing: -0.01em;
    }
    .ps-home-overlay__primary a:hover { opacity: 0.8; color: #fff; text-decoration: none; }
    .ps-home-overlay__primary .ext { font-size: 28px; vertical-align: super; margin-left: 6px; opacity: 0.85; }

    .ps-home-overlay__secondary { display: flex; flex-direction: column; gap: 36px; }
    .ps-home-overlay__group h6 {
        color: rgba(255,255,255,0.55);
        font-size: 13px;
        font-weight: 600;
        letter-spacing: 0.12em;
        margin: 0 0 14px;
        padding-bottom: 10px;
        border-bottom: 1px solid rgba(255,255,255,0.25);
    }
    .ps-home-overlay__group a {
        display: block;
        color: #fff;
        font-size: 18px;
        font-weight: 500;
        padding: 6px 0;
        text-decoration: none;
    }
    .ps-home-overlay__group a:hover { color: #fff; opacity: 0.8; text-decoration: none; }
    .ps-home-overlay__group a .ext { font-size: 14px; margin-left: 4px; }

    @media (max-width: 991px) {
        .ps-home-header { padding: 16px 20px; }
        .ps-home-header__nav { display: none; }
        .ps-home-header__right { gap: 18px; }
        .ps-home-overlay { padding: 18px 24px 40px; }
        .ps-home-overlay__top { margin-bottom: 40px; }
        .ps-home-overlay__body { grid-template-columns: 1fr; gap: 40px; }
        .ps-home-overlay__primary a { font-size: 36px; }
    }
</style>

<header class="ps-home-header" id="psHomeHeader">
    <div class="ps-home-header__logo">
        <a href="<?php echo site_url(); ?>index.php">
            <img src="<?php echo $logo; ?>" alt="<?php echo htmlspecialchars($SiteName, ENT_QUOTES, 'UTF-8'); ?>">
        </a>
    </div>

    <nav class="ps-home-header__nav">
        <a href="<?php echo site_url(); ?>page.php?name=About-us">About us</a>
        <a href="<?php echo site_url(); ?>search.php">Products</a>
        <a href="<?php echo site_url(); ?>page.php?name=Contact-us">Find Us</a>
        <a href="<?php echo site_url(); ?>page.php?name=Contact-us">Contact</a>
    </nav>

    <div class="ps-home-header__right">
        <?php if (!isset($_SESSION['LoginStatus']) || $_SESSION['LoginStatus'] !== 'login_success') : ?>
            <a class="ps-home-header__account" href="<?php echo site_url(); ?>login.php">Login</a>
        <?php else : ?>
            <a class="ps-home-header__account" href="<?php echo site_url(); ?>account.php">My Account</a>
            <a class="ps-home-header__account" href="<?php echo site_url(); ?>logout.php?logout=777">Logout</a>
        <?php endif; ?>
        <button type="button" class="ps-home-header__menu-btn" id="psHomeMenuOpen" aria-label="Open menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <a class="ps-home-header__pickup" href="<?php echo $homeHeaderPickupLink; ?>">
            Cart<span class="arrow">&#8599;</span>
            <?php if ($linenumber > 0) { ?>
                <span class="badge" style="background:#fff;color:#333;border-radius:50%;padding:2px 7px;margin-left:6px;font-size:11px;"><?php echo $linenumber; ?></span>
            <?php } ?>
        </a>
    </div>
</header>

<div class="ps-home-overlay" id="psHomeOverlay" aria-hidden="true">
    <div class="ps-home-overlay__top">
        <div class="ps-home-header__logo">
            <a href="<?php echo site_url(); ?>index.php">
                <img src="<?php echo $logo; ?>" alt="<?php echo htmlspecialchars($SiteName, ENT_QUOTES, 'UTF-8'); ?>" style="height:56px;border-radius:6px;">
            </a>
        </div>
        <button type="button" class="ps-home-overlay__close" id="psHomeMenuClose" aria-label="Close menu">&times;</button>
    </div>

    <div class="ps-home-overlay__body">
        <div class="ps-home-overlay__primary">
            <a href="<?php echo site_url(); ?>page.php?name=About-us">About us</a>
            <a href="<?php echo site_url(); ?>search.php">Products</a>
            <a href="<?php echo site_url(); ?>page.php?name=Contact-us">Find Us</a>
            <a href="<?php echo site_url(); ?>page.php?name=Contact-us">Contact</a>
        </div>

        <div class="ps-home-overlay__secondary">
            <div class="ps-home-overlay__group">
                <h6>More</h6>
                <a href="<?php echo $homeHeaderFaqLink; ?>">FAQs</a>
                <a href="<?php echo $homeHeaderContactLink; ?>">Contact</a>
                <a href="<?php echo $homeHeaderStoryLink; ?>">Our Story<span class="ext">&#8599;</span></a>
                <?php if (!isset($_SESSION['LoginStatus']) || $_SESSION['LoginStatus'] !== 'login_success') : ?>
                    <a href="<?php echo site_url(); ?>login.php">Login</a>
                    <a href="<?php echo site_url(); ?>register.php">Register</a>
                <?php else : ?>
                    <a href="<?php echo site_url(); ?>account.php">My Account</a>
                    <a href="<?php echo site_url(); ?>logout.php?logout=777">Logout</a>
                <?php endif; ?>
            </div>

            <div class="ps-home-overlay__group">
                <h6>Connect</h6>
                <a href="#" target="_blank">Instagram<span class="ext">&#8599;</span></a>
                <a href="#" target="_blank">Facebook<span class="ext">&#8599;</span></a>
            </div>

            <div class="ps-home-overlay__group">
                <h6>Get In Touch</h6>
                <?php if (!empty($system_contactUs)) { ?>
                    <a href="tel:<?php echo htmlspecialchars(preg_replace('/\s+/', '', $system_contactUs)); ?>"><?php echo htmlspecialchars($system_contactUs); ?><span class="ext">&#8599;</span></a>
                <?php } ?>
                <?php if (!empty($system_email)) { ?>
                    <a href="mailto:<?php echo htmlspecialchars($system_email); ?>"><?php echo htmlspecialchars($system_email); ?><span class="ext">&#8599;</span></a>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    var openBtn = document.getElementById('psHomeMenuOpen');
    var closeBtn = document.getElementById('psHomeMenuClose');
    var overlay = document.getElementById('psHomeOverlay');
    var header = document.getElementById('psHomeHeader');

    if (openBtn && overlay) {
        openBtn.addEventListener('click', function(e){
            e.preventDefault();
            overlay.classList.add('is-open');
            overlay.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        });
    }
    if (closeBtn && overlay) {
        closeBtn.addEventListener('click', function(e){
            e.preventDefault();
            overlay.classList.remove('is-open');
            overlay.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        });
    }
    document.addEventListener('keydown', function(e){
        if (e.key === 'Escape' && overlay && overlay.classList.contains('is-open')) {
            overlay.classList.remove('is-open');
            document.body.style.overflow = '';
        }
    });

    // Sticky-on-scroll behavior
    if (header) {
        window.addEventListener('scroll', function(){
            if (window.scrollY > 80) {
                header.classList.add('is-scrolled');
            } else {
                header.classList.remove('is-scrolled');
            }
        }, { passive: true });
    }
})();
</script>
