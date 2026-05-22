
        <!-- BEGIN GLOBAL MANDATORY STYLES -->
        <link href="http://fonts.googleapis.com/css?family=Open+Sans:400,300,600,700&amp;subset=all" rel="stylesheet" type="text/css" />
        <link href='https://fonts.googleapis.com/css?family=Ubuntu' rel='stylesheet' type='text/css'>
        <link href="assets/global/plugins/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/global/plugins/simple-line-icons/simple-line-icons.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/global/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/global/plugins/uniform/css/uniform.default.css" rel="stylesheet" type="text/css" />
        <link href="assets/global/plugins/bootstrap-switch/css/bootstrap-switch.min.css" rel="stylesheet" type="text/css" />
        <!-- END GLOBAL MANDATORY STYLES -->
        <!-- BEGIN PAGE LEVEL PLUGINS -->
        <link href="assets/global/plugins/bootstrap-daterangepicker/daterangepicker.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/global/plugins/morris/morris.css" rel="stylesheet" type="text/css" />
        <link href="assets/global/plugins/fullcalendar/fullcalendar.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/global/plugins/jqvmap/jqvmap/jqvmap.css" rel="stylesheet" type="text/css" />
        <!-- END PAGE LEVEL PLUGINS -->
        <!-- BEGIN THEME GLOBAL STYLES -->
        <link href="assets/global/css/components.min.css" rel="stylesheet" id="style_components" type="text/css" />
        <link href="assets/global/css/plugins.min.css" rel="stylesheet" type="text/css" />
        <!-- END THEME GLOBAL STYLES -->
        <!-- BEGIN THEME LAYOUT STYLES -->
        <link href="assets/layouts/layout/css/layout.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/layouts/layout/css/themes/darkblue.min.css" rel="stylesheet" type="text/css" id="style_color" />
        <link href="assets/layouts/layout/css/custom.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/layouts/layout/css/styles.css" rel="stylesheet" type="text/css" />
        <link href="assets/layouts/layout/css/animate.css" rel="stylesheet" type="text/css" />
        
        <!-- Ajax Dropdown for product add  -->
         <script src="assets/global/plugins/DynamicDrp/jquery.min.js" type="text/javascript"></script>
        
        <!-- auto Price set  -->
         <script type="text/javascript" src="assets/global/plugins/numaricFunction/autoNumeric.js"></script>
        
         <!-- BEGIN PAGE LEVEL STYLES -->
        <link href="assets/pages/css/invoice.min.css" rel="stylesheet" type="text/css" />
        <!-- END PAGE LEVEL STYLES -->
        

        <!-- END THEME LAYOUT STYLES -->
        <link rel="shortcut icon" href="favicon.ico" /> 

        <link href="assets/layouts/layout/css/celander_jquery-ui.css" rel="stylesheet" type="text/css" />

<style>
/* Override sidebar layout to hide sidebar and use full width */
.page-sidebar-wrapper { display: none !important; }
.page-content-wrapper .page-content { margin-left: 0 !important; }
.page-header.navbar .menu-toggler { display: none !important; }

/* Hide logo */
.page-logo { display: none !important; }

/* Fix for fixed navbar - ensure content doesn't overlap */
.page-header-fixed .page-container {
    margin-top: 46px !important;
}

/* Page header - fixed at top */
.page-header.navbar {
    position: fixed !important;
    top: 0;
    left: 0;
    right: 0;
    z-index: 9995;
    height: 46px;
    background-color: #2b3643;
}

/* Header inner flex layout */
.page-header-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 46px;
    padding: 0 10px;
    position: relative;
}

/* Horizontal menu - always visible, NO overflow hidden */
.hor-menu {
    display: block !important;
    flex: 1;
    margin: 0;
    position: static;
}

.hor-menu .navbar-nav {
    display: flex;
    flex-wrap: nowrap;
    margin: 0;
    padding: 0;
    list-style: none;
}

.hor-menu .navbar-nav > li {
    position: relative;
    flex-shrink: 0;
}

.hor-menu .navbar-nav > li > a {
    display: block;
    padding: 14px 10px;
    color: #c6cfda;
    font-size: 13px;
    text-decoration: none;
    white-space: nowrap;
}

.hor-menu .navbar-nav > li > a:hover {
    background-color: #3f4f62;
    color: #fff;
}

.hor-menu .navbar-nav > li.active > a {
    background-color: #364150;
    color: #fff;
}

/* Dropdown menu styles - VISIBLE OUTSIDE HEADER */
.hor-menu .navbar-nav > li > .dropdown-menu {
    display: none;
    position: absolute;
    top: 46px; /* Same as header height */
    left: 0;
    margin: 0;
    padding: 0;
    background-color: #3f4f62;
    border: 0;
    border-radius: 0 0 4px 4px;
    z-index: 99999;
    min-width: 200px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
    list-style: none;
}

.hor-menu .navbar-nav > li:hover > .dropdown-menu,
.hor-menu .navbar-nav > li.open > .dropdown-menu {
    display: block;
}

.hor-menu .dropdown-menu > li {
    list-style: none;
}

.hor-menu .dropdown-menu > li > a {
    color: #c6cfda;
    padding: 10px 15px;
    display: block;
    white-space: nowrap;
    text-decoration: none;
}

.hor-menu .dropdown-menu > li > a:hover {
    background-color: #4b5d71;
    color: #fff;
}

.hor-menu .dropdown-menu > li > a i {
    margin-right: 8px;
    width: 16px;
}

/* Submenu (nested dropdown) handling */
.dropdown-submenu {
    position: relative;
}

.dropdown-submenu > .dropdown-menu {
    display: none;
    position: absolute;
    top: 0;
    left: 100%;
    margin: 0;
    padding: 0;
    background-color: #3f4f62;
    border-radius: 0 4px 4px 4px;
    z-index: 100000;
    min-width: 200px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
}

.dropdown-submenu:hover > .dropdown-menu {
    display: block;
}

.dropdown-submenu > a:after {
    content: "";
    display: inline-block;
    float: right;
    width: 0;
    height: 0;
    border-style: solid;
    border-width: 5px 0 5px 5px;
    border-color: transparent transparent transparent #ccc;
    margin-top: 6px;
    margin-left: 10px;
}

.dropdown-submenu:hover > a:after {
    border-left-color: #fff;
}

/* Divider in dropdown */
.hor-menu .dropdown-menu > li.divider {
    height: 1px;
    margin: 5px 0;
    background-color: #4b5d71;
}

/* Top menu (user dropdown) */
.top-menu {
    flex-shrink: 0;
    margin-left: 10px;
    position: relative;
}

.top-menu .navbar-nav {
    margin: 0;
    padding: 0;
}

.top-menu .navbar-nav > li {
    display: inline-block;
    position: relative;
}

/* User dropdown */
.dropdown-user .dropdown-toggle {
    display: flex;
    align-items: center;
    padding: 8px 10px;
    color: #c6cfda;
    text-decoration: none;
    cursor: pointer;
}

.dropdown-user .dropdown-toggle img {
    width: 30px;
    height: 30px;
    margin-right: 8px;
    border-radius: 50%;
}

.dropdown-user .dropdown-toggle .username {
    margin-right: 5px;
}

.dropdown-user .dropdown-menu {
    display: none;
    position: absolute;
    top: 46px;
    right: 0;
    left: auto;
    margin: 0;
    padding: 5px 0;
    background-color: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    z-index: 100000;
    min-width: 160px;
    list-style: none;
}

.dropdown-user:hover .dropdown-menu,
.dropdown-user.open .dropdown-menu {
    display: block;
}

.dropdown-user .dropdown-menu > li > a {
    display: block;
    padding: 10px 15px;
    color: #333;
    text-decoration: none;
}

.dropdown-user .dropdown-menu > li > a:hover {
    background-color: #f5f5f5;
}

.dropdown-user .dropdown-menu > li > a i {
    margin-right: 8px;
    width: 16px;
}

/* ==================== PAGE BAR STYLES ==================== */
.page-bar {
    background-color: #00bcd4;
    padding: 10px 15px;
    margin: 0 -20px 20px -20px;
    min-height: 40px;
}

.page-bar .page-breadcrumb {
    display: none !important;
}

/* ==================== RESPONSIVE - Keep menu same, just adjust sizing ==================== */
@media (max-width: 1199px) {
    .hor-menu .navbar-nav > li > a {
        padding: 14px 8px;
        font-size: 11px;
    }
}

@media (max-width: 991px) {
    .hor-menu .navbar-nav > li > a {
        padding: 14px 6px;
        font-size: 10px;
    }
    
    .top-menu .username {
        display: none;
    }
    
    .page-bar {
        margin: 0 -20px 15px -20px;
        padding: 8px 15px;
    }
}

@media (max-width: 767px) {
    .page-header-inner {
        padding: 0 5px;
    }
    
    .hor-menu .navbar-nav > li > a {
        padding: 14px 4px;
        font-size: 9px;
    }
    
    .dropdown-user .dropdown-toggle img {
        width: 24px;
        height: 24px;
        margin-right: 0;
    }
    
    .dropdown-user .dropdown-toggle .username,
    .dropdown-user .dropdown-toggle .fa-angle-down {
        display: none;
    }
    
    .page-bar {
        margin: 0 -10px 15px -10px;
        padding: 8px 10px;
    }
}

.page-content-wrapper{
     margin-top: 48px;
}
</style>



