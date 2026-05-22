<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
function filter($var)
{

    return preg_replace('[0-9]', ' ', $var);
}
if (isset($_SESSION['LoginStatus'])) {
    $LoginStatus = $_SESSION['LoginStatus'];
} else {
    $LoginStatus = "";
}
if ($LoginStatus != "login_success") {
    Redirect('login.php', false);
}
$db = new database();
if (!empty($_SESSION['Loginuserid'])) {

    $customer_id = $_SESSION['Loginuserid'];
    $query_get_detials = $db->getRow('SELECT * FROM customer WHERE customer_id = ?', [$customer_id]);

    $customer_name = $query_get_detials['customer_name'];
    $customer_email = $query_get_detials['customer_email'];
    $customer_nic = $query_get_detials['customer_nic'];
    $customer_tell = $query_get_detials['customer_tell'];
    $customer_mobile = $query_get_detials['customer_mobile'];
}

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
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .account-content-card p.lead {
            font-size: 15px;
            color: #666;
            margin-bottom: 30px;
        }
        fieldset legend {
            font-family: 'Playfair Display', 'Georgia', serif;
            font-size: 22px;
            color: #111;
            border-bottom: 1px solid #eee;
            padding-bottom: 12px;
            margin-bottom: 25px;
            width: 100%;
        }
        .auth-input {
            width: 100%;
            height: 48px;
            padding: 0 15px;
            border: 1px solid #ccc;
            font-size: 14px;
            color: #111;
            transition: all 0.3s ease;
            background: #fff;
        }
        .auth-input:focus {
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
        <div class="account-page-wrapper">
            <div class="container">
                <div class="row">
                    <div class="col-12 col-md-4 col-lg-3">
                        <div class="account-sidebar-nav">
                            <h4 class="nav-title">My Account</h4>
                            <ul>
                                <li><a href="<?php echo site_url(); ?>dashboard.php">Dashboard</a></li>
                                <li><a href="<?php echo site_url(); ?>account.php" class="active">Personal Details</a></li>
                                <li><a href="<?php echo site_url(); ?>passwordchange.php">Password Change</a></li>
                                <li><a href="<?php echo site_url(); ?>orderhistory.php">Order History</a></li>
                                <li><a href="<?php echo site_url(); ?>complaint.php">Complaints & Feedback</a></li>
                                <li><a href="<?php echo site_url(); ?>my-complaints.php">My Complaints</a></li>
                                <li><a href="<?php echo site_url(); ?>logout.php?logout=777">Logout</a></li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="col-12 col-md-8 col-lg-9">
                        <div class="account-content-card">
                            <h2 class="title">Personal Details</h2>
                            <p class="lead">Hello, <strong><?php echo htmlspecialchars($customer_name); ?>!</strong> Update your account information here.</p>
                            
                            <form method="POST" id="frn-info">
                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <fieldset>
                                            <legend>Personal Info</legend>
                                            <div class="form-group required">
                                                <label for="input-firstname">Full Name</label>
                                                <input type="text" required="" class="auth-input" id="input-firstname" placeholder="Name" value="<?php echo htmlspecialchars($customer_name); ?>" name="name">
                                            </div>

                                            <div class="form-group required">
                                                <label for="input-email">E-Mail Address</label>
                                                <input type="email" required="" class="auth-input" id="input-email" placeholder="E-Mail" value="<?php echo htmlspecialchars($customer_email); ?>" name="email">
                                            </div>

                                            <div class="form-group required">
                                                <label for="input-nic">NIC Number</label>
                                                <input type="text" required="" class="auth-input" id="input-nic" placeholder="NIC number" value="<?php echo htmlspecialchars($customer_nic); ?>" name="nic">
                                            </div>
                                        </fieldset>
                                    </div>

                                    <div class="col-md-6 mb-4">
                                        <fieldset>
                                            <legend>Contact Info</legend>
                                            <div class="form-group required">
                                                <label for="input-telephone">Telephone</label>
                                                <input type="tel" class="auth-input" id="input-telephone" placeholder="Telephone" value="<?php echo htmlspecialchars($customer_tell); ?>" name="telephone">
                                            </div>
                                            <div class="form-group required">
                                                <label for="input-mobile">Mobile Phone</label>
                                                <input type="tel" required="" class="auth-input" id="input-mobile" placeholder="Mobile phone" value="<?php echo htmlspecialchars($customer_mobile); ?>" name="mobilephone">
                                            </div>
                                        </fieldset>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <button type="submit" class="btn-black">Save Changes</button>
                                    </div>
                                </div>
                            </form>
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
<script type="text/javascript">
    $(document).ready(function() {
        $(document).on('submit', '#frn-info', function() {
            var data = $(this).serialize();
            $.ajax({

                type: 'POST',
                url: 'process/update-personal-details.php',
                data: data,
                success: function(data) {
                    alert(data);
                }
            });
            return false;
        });

    });
</script>