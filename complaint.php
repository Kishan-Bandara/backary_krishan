<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');

if (isset($_SESSION['LoginStatus'])) {
    $LoginStatus = $_SESSION['LoginStatus'];
} else {
    $LoginStatus = "";
}
if ($LoginStatus != "login_success") {
    Redirect('login.php', false);
}

$db = new Database();
$customer_id = $_SESSION['Loginuserid'];

// Get customer purchased products from invoices
$purchased_products = $db->getRows(
    'SELECT DISTINCT im.item_id, im.item_name 
     FROM invoice_details id 
     INNER JOIN invoice_hedder ih ON ih.invoice_h_id = id.invoice_h_id 
     INNER JOIN item_master im ON im.item_id = id.invoice_d_item_id 
     WHERE ih.invoice_h_customer_id = ? 
     ORDER BY im.item_name ASC',
    [$customer_id]
);

// Get product issue types
$product_issue_types = $db->getRows('SELECT * FROM complaint_product_issue_type WHERE is_active = 1 ORDER BY name', []);

// Get service issue types  
$service_issue_types = $db->getRows('SELECT * FROM complaint_service_issue_type WHERE is_active = 1 ORDER BY name', []);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include('common/styles.php'); ?>
    <style>
        .account-page-wrapper {
            padding: 60px 0;
            background-color: #f7f7f7;
        }
        .account-sidebar-nav {
            background: #fff;
            border: 1px solid #e5e5e5;
            padding: 30px;
            margin-bottom: 30px;
        }
        .account-sidebar-nav .nav-title {
            font-family: 'Playfair Display', 'Georgia', serif;
            font-size: 20px;
            text-transform: uppercase;
            font-weight: 600;
            border-bottom: 2px solid #111;
            padding-bottom: 12px;
            margin-bottom: 20px;
            color: #111;
        }
        .account-sidebar-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .account-sidebar-nav ul li {
            margin-bottom: 10px;
            border-bottom: 1px solid #f1f1f1;
        }
        .account-sidebar-nav ul li:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .account-sidebar-nav ul li a {
            color: #555;
            font-size: 15px;
            font-weight: 500;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 10px 0;
            transition: all 0.3s ease;
        }
        .account-sidebar-nav ul li a:hover,
        .account-sidebar-nav ul li a.active {
            color: #111;
            font-weight: 600;
            padding-left: 8px;
        }
        
        .account-content-card {
            background: #fff;
            border: 1px solid #e5e5e5;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            min-height: 100%;
        }
        .account-content-card h2.title {
            font-family: 'Playfair Display', 'Georgia', serif;
            font-size: 30px;
            font-weight: 600;
            color: #111;
            margin-bottom: 30px;
            text-transform: uppercase;
        }
        
        .complaint-section { display: none; }
        .complaint-section.active { display: block; }
        .complaint-type-btn { 
            cursor: pointer; 
            border: 1px solid #e5e5e5; 
            padding: 25px; 
            text-align: center; 
            transition: all 0.3s ease; 
            background: #fff;
        }
        .complaint-type-btn:hover, .complaint-type-btn.selected { 
            border-color: #111; 
            background: #fafafa;
            box-shadow: inset 0 0 0 2px #111;
        }
        .complaint-type-btn i { font-size: 35px; color: #111; }
        .complaint-type-btn h5 { margin-top: 15px; font-weight: 600; font-family: 'Playfair Display', 'Georgia', serif; font-size: 18px; color: #111; text-transform: uppercase;}
        .complaint-type-btn small { color: #666; font-size: 13px; }

        .auth-input, .auth-select, .auth-textarea {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ccc;
            font-size: 14px;
            color: #111;
            transition: all 0.3s ease;
            background: #fff;
            border-radius: 0;
        }
        .auth-input { height: 48px; }
        .auth-select { height: 48px; -webkit-appearance: none; -moz-appearance: none; appearance: none; background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="10" height="5"><path fill="%23333" d="M0 0l5 5 5-5z"/></svg>') no-repeat right 15px center #fff; background-size: 10px;}
        .auth-textarea { min-height: 100px; resize: vertical; }
        .auth-input:focus, .auth-select:focus, .auth-textarea:focus {
            border-color: #111;
            outline: none;
            box-shadow: none;
        }
        .form-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .btn-black {
            background-color: #111;
            color: #fff;
            border: none;
            height: auto;
            padding: 14px 35px;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-black:hover {
            background-color: #333;
        }
    </style>
</head>
<body>
    <div class="ps-page">
        <?php include('common/header.php'); ?>
        <br>
        <div class="account-page-wrapper">
            <div class="container">
                <div class="row">
                    <div class="col-12 col-md-4 col-lg-3">
                        <div class="account-sidebar-nav">
                            <h4 class="nav-title">My Account</h4>
                            <ul>
                                <li><a href="<?php echo site_url(); ?>dashboard.php">Dashboard</a></li>
                                <li><a href="<?php echo site_url(); ?>account.php">Personal Details</a></li>
                                <li><a href="<?php echo site_url(); ?>passwordchange.php">Password Change</a></li>
                                <li><a href="<?php echo site_url(); ?>orderhistory.php">Order History</a></li>
                                <li><a href="<?php echo site_url(); ?>complaint.php" class="active">Complaints & Feedback</a></li>
                                <li><a href="<?php echo site_url(); ?>my-complaints.php">My Complaints</a></li>
                                <li><a href="<?php echo site_url(); ?>logout.php?logout=777">Logout</a></li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="col-12 col-md-8 col-lg-9">
                        <div class="account-content-card">
                            <h2 class="title">Submit a Complaint / Feedback</h2>
                            <div id="alert-area"></div>
                            
                            <!-- Step 1: Choose Type -->
                            <div class="row mb-5 mt-4">
                                <div class="col-sm-6 mb-3 mb-sm-0">
                                    <div class="complaint-type-btn" data-type="Product" id="btn-product">
                                        <i class="fa fa-shopping-bag"></i>
                                        <h5>Product Complaint</h5>
                                        <small>Issues with product quality, taste, price</small>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="complaint-type-btn" data-type="Service" id="btn-service">
                                        <i class="fa fa-headphones"></i>
                                        <h5>Service Complaint</h5>
                                        <small>Issues with delivery, staff service</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Product Complaint Form -->
                            <div id="product-section" class="complaint-section">
                                <form method="POST" id="product-complaint-form" enctype="multipart/form-data">
                                    <input type="hidden" name="complaint_type" value="Product">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group required">
                                                <label class="control-label">Select Product</label>
                                                <select class="auth-select" name="product_id" required>
                                                    <option value="">-- Select Product --</option>
                                                    <?php if ($purchased_products) { foreach ($purchased_products as $p) { ?>
                                                        <option value="<?php echo (int)$p['item_id']; ?>"><?php echo htmlspecialchars($p['item_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                                    <?php } } ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group required">
                                                <label class="control-label">Issue Type</label>
                                                <select class="auth-select" name="product_issue_type_id" required>
                                                    <option value="">-- Select Issue Type --</option>
                                                    <?php if ($product_issue_types) { foreach ($product_issue_types as $it) { ?>
                                                        <option value="<?php echo (int)$it['id']; ?>"><?php echo htmlspecialchars($it['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                                    <?php } } ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group required">
                                        <label class="control-label">Complaint Details</label>
                                        <textarea class="auth-textarea" name="complaint_text" rows="4" required placeholder="Describe your issue..."></textarea>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label class="control-label">Date of Purchase</label>
                                                <input type="date" class="auth-input" name="date_of_purchase">
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label class="control-label">Invoice No (if available)</label>
                                                <input type="text" class="auth-input" name="invoice_no" placeholder="Invoice number">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label">Attachment (Photo)</label>
                                        <input type="file" class="auth-input" style="padding-top: 10px;" name="attachment" accept="image/*">
                                    </div>
                                    <div class="buttons clearfix mt-4">
                                        <button type="submit" class="btn-black">Submit Product Complaint</button>
                                    </div>
                                </form>
                            </div>

                            <!-- Service Complaint Form -->
                            <div id="service-section" class="complaint-section">
                                <form method="POST" id="service-complaint-form" enctype="multipart/form-data">
                                    <input type="hidden" name="complaint_type" value="Service">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group required">
                                                <label class="control-label">Issue Type</label>
                                                <select class="auth-select" name="service_issue_type_id" required>
                                                    <option value="">-- Select Issue Type --</option>
                                                    <?php if ($service_issue_types) { foreach ($service_issue_types as $it) { ?>
                                                        <option value="<?php echo (int)$it['id']; ?>"><?php echo htmlspecialchars($it['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                                    <?php } } ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label class="control-label">Date of Purchase</label>
                                                <input type="date" class="auth-input" name="date_of_purchase">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group required">
                                        <label class="control-label">Issue Details</label>
                                        <textarea class="auth-textarea" name="complaint_text" rows="4" required placeholder="Describe your issue..."></textarea>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label class="control-label">Invoice No (if available)</label>
                                                <input type="text" class="auth-input" name="invoice_no" placeholder="Invoice number">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label">Attachment (Photo)</label>
                                        <input type="file" class="auth-input" style="padding-top: 10px;" name="attachment" accept="image/*">
                                    </div>
                                    <div class="buttons clearfix mt-4">
                                        <button type="submit" class="btn-black">Submit Service Complaint</button>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include('common/footer.php'); ?>
    </div>

    <script src="<?php echo site_url(); ?>plugins/jquery.min.js"></script>
    <script src="<?php echo site_url(); ?>plugins/popper.min.js"></script>
    <script src="<?php echo site_url(); ?>plugins/bootstrap4/js/bootstrap.min.js"></script>
    <script src="<?php echo site_url(); ?>plugins/select2/dist/js/select2.full.min.js"></script>
    <script src="<?php echo site_url(); ?>plugins/owl-carousel/owl.carousel.min.js"></script>
    <script src="<?php echo site_url(); ?>plugins/jquery-bar-rating/dist/jquery.barrating.min.js"></script>
    <script src="<?php echo site_url(); ?>plugins/lightGallery/dist/js/lightgallery-all.min.js"></script>
    <script src="<?php echo site_url(); ?>plugins/slick/slick/slick.min.js"></script>
    <script src="<?php echo site_url(); ?>plugins/noUiSlider/nouislider.min.js"></script>
    <script src="<?php echo site_url(); ?>js/main.js"></script>
    <script>
    $(document).ready(function() {
        // Type selection
        $('.complaint-type-btn').click(function() {
            $('.complaint-type-btn').removeClass('selected');
            $(this).addClass('selected');
            var type = $(this).data('type');
            $('.complaint-section').removeClass('active');
            if (type === 'Product') {
                $('#product-section').addClass('active');
            } else {
                $('#service-section').addClass('active');
            }
        });

        // Product complaint submit
        $('#product-complaint-form').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            $.ajax({
                url: 'process/complaint-submit.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(data) {
                    $('#alert-area').html('<div class="alert ' + data.class + '">' + data.message + '</div>');
                    if (data.status) {
                        $('#product-complaint-form')[0].reset();
                        setTimeout(function() { window.location.href = 'my-complaints.php'; }, 2000);
                    }
                },
                error: function() {
                    $('#alert-area').html('<div class="alert alert-danger">Something went wrong. Please try again.</div>');
                }
            });
        });

        // Service complaint submit
        $('#service-complaint-form').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            $.ajax({
                url: 'process/complaint-submit.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(data) {
                    $('#alert-area').html('<div class="alert ' + data.class + '">' + data.message + '</div>');
                    if (data.status) {
                        $('#service-complaint-form')[0].reset();
                        setTimeout(function() { window.location.href = 'my-complaints.php'; }, 2000);
                    }
                },
                error: function() {
                    $('#alert-area').html('<div class="alert alert-danger">Something went wrong. Please try again.</div>');
                }
            });
        });
    });
    </script>
</body>
</html>
