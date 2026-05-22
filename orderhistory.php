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
function getContent()
{
  $db = new Database();
  $query = $db->getRows('SELECT * FROM invoice_hedder WHERE invoice_h_customer_id = ?', [$_SESSION['Loginuserid']]);
  return $query;
}

$db = new Database();

?>
<!DOCTYPE html>
<html lang="en">


<head>
    <?php include('common/styles.php'); ?>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/dt/dt-1.10.13/datatables.min.css" />
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
        
        table.dataTable {
            font-size: 14px;
            width: 100% !important;
            border-collapse: collapse !important;
            border: 1px solid #e5e5e5;
        }
        table.dataTable thead th {
            background-color: #111;
            color: #fff;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 15px;
            border-bottom: none !important;
        }
        table.dataTable tbody td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        table.dataTable tbody tr:hover {
            background-color: #fafafa;
        }
        .btn-view-invoice {
            background-color: #111;
            color: #fff;
            padding: 8px 15px;
            font-size: 12px;
            text-transform: uppercase;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
            display: inline-block;
        }
        .btn-view-invoice:hover {
            background-color: #333;
            color: #fff;
            text-decoration: none;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current, 
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: #111 !important;
            color: #fff !important;
            border-color: #111 !important;
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
                                <li><a href="<?php echo site_url(); ?>orderhistory.php" class="active">Order History</a></li>
                                <li><a href="<?php echo site_url(); ?>complaint.php">Complaints & Feedback</a></li>
                                <li><a href="<?php echo site_url(); ?>my-complaints.php">My Complaints</a></li>
                                <li><a href="<?php echo site_url(); ?>logout.php?logout=777">Logout</a></li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="col-12 col-md-8 col-lg-9">
                        <div class="account-content-card">
                            <h2 class="title">Order History</h2>

                            <table id="orderHistory" class="display" cellspacing="0" width="100%">
                                <thead>
                                    <tr>
                                    <th>Date</th>
                                    <th>Order No</th>
                                    <th>Amount</th>
                                    <th>Delivery Mode</th>
                                    <th>Payment Type</th>
                                    <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $data = getContent();
                                    foreach ($data as $query) {
                                    $inv_id = $query['invoice_h_id'];
                                    $inv_date = $query['invoice_h_date'];
                                    $inv_code = $query['invoice_h_code'];
                                    $inv_amount = $query['invoice_h_gross_value'];
                                    $inv_dilevery_method = $query['invoice_h_delivery_mode'];
                                    $inv_pay_type_id = $query['invoice_h_pay_type'];

                                    $query_pay_type = $db->getRow('SELECT * FROM payment_method WHERE id = ?', [$inv_pay_type_id]);

                                    if(!empty($query_pay_type['type'])) {
                                        $inv_pay_type_name = $query_pay_type['type'];
                                    }else{
                                        $query_inv_dilevery_method = "no data";
                                    }
                                
                                    $query_inv_dilevery_method = $db->getRow('SELECT * FROM delivery_master WHERE id = ?', [$inv_dilevery_method]);

                                    if(!empty($query_inv_dilevery_method['method'])) {
                                        $inv_dilevery_mode_name = $query_inv_dilevery_method['method'];
                                    }else{
                                        $inv_dilevery_mode_name = "no data";
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo $inv_date; ?></td>
                                        <td><?php echo $inv_code; ?></td>
                                        <td><?php echo "LKR " . $inv_amount; ?></td>
                                        <td><?php echo $inv_dilevery_mode_name; ?></td>
                                        <td><?php echo $inv_pay_type_name; ?></td>
                                        <td><a href="" data-toggle="modal" data-target="#myModal" data-receipt-id="<?php echo $inv_id; ?>" class="btnReceiptid btn-view-invoice">VIEW INVOICE</a>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
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
    <script type="text/javascript" src="https://cdn.datatables.net/v/dt/dt-1.10.13/datatables.min.js"></script>
    <!-- custom code-->
    <script src="<?php echo site_url(); ?>js/main.js"></script>
</body>


</html>
<script type="text/javascript">
$(document).ready(function() {
    $('#orderHistory').DataTable();
} );
    </script>
	