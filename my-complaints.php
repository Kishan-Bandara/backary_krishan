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

$complaints = $db->getRows(
    'SELECT c.*, 
            im.item_name,
            cpit.name AS product_issue_name,
            csit.name AS service_issue_name
     FROM complaints c
     LEFT JOIN item_master im ON im.item_id = c.product_id
     LEFT JOIN complaint_product_issue_type cpit ON cpit.id = c.product_issue_type_id
     LEFT JOIN complaint_service_issue_type csit ON csit.id = c.service_issue_type_id
     WHERE c.customer_id = ?
     ORDER BY c.complaint_id DESC',
    [$customer_id]
);
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
        .btn-view-complaint {
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
        .btn-view-complaint:hover {
            background-color: #333;
            color: #fff;
            text-decoration: none;
        }
        .btn-black {
            background-color: #111;
            color: #fff;
            padding: 12px 25px;
            font-size: 13px;
            text-transform: uppercase;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
            display: inline-block;
            border: none;
            margin-bottom: 25px;
        }
        .btn-black:hover {
            background-color: #333;
            color: #fff;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current, 
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: #111 !important;
            color: #fff !important;
            border-color: #111 !important;
        }

        .badge { font-weight: 600; font-size: 12px; padding: 6px 10px; border-radius: 0; text-transform: uppercase;}
        .badge-open { background: #f39c12; color: #fff; }
        .badge-assigned { background: #3498db; color: #fff; }
        .badge-inprogress { background: #9b59b6; color: #fff; }
        .badge-closed { background: #111; color: #fff; }
        .outcome-box { background: #fafafa; border-left: 4px solid #111; padding: 15px; margin-top: 10px; border-radius: 0;}
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
                                <li><a href="<?php echo site_url(); ?>complaint.php">Complaints & Feedback</a></li>
                                <li><a href="<?php echo site_url(); ?>my-complaints.php" class="active">My Complaints</a></li>
                                <li><a href="<?php echo site_url(); ?>logout.php?logout=777">Logout</a></li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="col-12 col-md-8 col-lg-9">
                        <div class="account-content-card">
                            <h2 class="title">My Complaints</h2>
                            <a href="<?php echo site_url(); ?>complaint.php" class="btn-black">+ New Complaint</a>

                            <table id="complaintsTable" class="display" cellspacing="0" width="100%">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Type</th>
                                        <th>Issue</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($complaints) { foreach ($complaints as $c) {
                                        $statusClass = 'badge-open';
                                        if ($c['status'] == 'Assigned') $statusClass = 'badge-assigned';
                                        elseif ($c['status'] == 'In Progress') $statusClass = 'badge-inprogress';
                                        elseif ($c['status'] == 'Closed') $statusClass = 'badge-closed';

                                        $issue = '';
                                        if ($c['complaint_type'] == 'Product') {
                                            $issue = htmlspecialchars($c['item_name'] . ' - ' . $c['product_issue_name'], ENT_QUOTES, 'UTF-8');
                                        } else {
                                            $issue = htmlspecialchars($c['service_issue_name'], ENT_QUOTES, 'UTF-8');
                                        }
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($c['complaint_code'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($c['complaint_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo $issue; ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($c['created_at'])); ?></td>
                                        <td><span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($c['status'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                        <td>
                                            <a href="#" class="btn-view-complaint" data-id="<?php echo (int)$c['complaint_id']; ?>" data-toggle="modal" data-target="#complaintDetailModal">View</a>
                                        </td>
                                    </tr>
                                    <?php } } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Complaint Detail Modal -->
        <div class="modal fade" id="complaintDetailModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Complaint Details</h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body" id="complaint-detail-body">
                        <p>Loading...</p>
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
    <script type="text/javascript" src="https://cdn.datatables.net/v/dt/dt-1.10.13/datatables.min.js"></script>
    <script src="<?php echo site_url(); ?>js/main.js"></script>
    <script>
    $(document).ready(function() {
        $('#complaintsTable').DataTable({ order: [[3, 'desc']] });

        $('.btn-view-complaint').click(function() {
            var cid = $(this).data('id');
            $('#complaint-detail-body').html('<p>Loading...</p>');
            $.ajax({
                url: 'process/complaint-detail.php',
                type: 'POST',
                data: { complaint_id: cid },
                dataType: 'json',
                success: function(data) {
                    if (data.status) {
                        var c = data.complaint;
                        var html = '<table class="table table-bordered">';
                        html += '<tr><th width="30%">Complaint Code</th><td>' + c.complaint_code + '</td></tr>';
                        html += '<tr><th>Type</th><td>' + c.complaint_type + '</td></tr>';
                        if (c.complaint_type === 'Product') {
                            html += '<tr><th>Product</th><td>' + (c.item_name || '-') + '</td></tr>';
                            html += '<tr><th>Issue Type</th><td>' + (c.product_issue_name || '-') + '</td></tr>';
                        } else {
                            html += '<tr><th>Issue Type</th><td>' + (c.service_issue_name || '-') + '</td></tr>';
                        }
                        html += '<tr><th>Details</th><td>' + c.complaint_text + '</td></tr>';
                        html += '<tr><th>Date of Purchase</th><td>' + (c.date_of_purchase || '-') + '</td></tr>';
                        html += '<tr><th>Invoice No</th><td>' + (c.invoice_no || '-') + '</td></tr>';
                        html += '<tr><th>Status</th><td><strong>' + c.status + '</strong></td></tr>';
                        html += '<tr><th>Submitted</th><td>' + c.created_at + '</td></tr>';
                        if (c.attachment) {
                            html += '<tr><th>Attachment</th><td><img src="' + c.attachment + '" style="max-width:300px; max-height:200px;" /></td></tr>';
                        }
                        if (c.customer_outcome_message) {
                            html += '<tr><th>Outcome</th><td><div class="outcome-box">' + c.customer_outcome_message + '</div></td></tr>';
                        }
                        html += '</table>';
                        $('#complaint-detail-body').html(html);
                    } else {
                        $('#complaint-detail-body').html('<p class="text-danger">Could not load complaint details.</p>');
                    }
                }
            });
        });
    });
    </script>
</body>
</html>
