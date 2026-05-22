<?php 
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');
include('get_url.php');

$db = new Database();

// Filter parameters
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

$where = ' WHERE 1=1';
$params = [];

if ($filter_type && in_array($filter_type, ['Product', 'Service'])) {
    $where .= ' AND c.complaint_type = ?';
    $params[] = $filter_type;
}
if ($filter_status && in_array($filter_status, ['Open', 'Assigned', 'In Progress', 'Closed'])) {
    $where .= ' AND c.status = ?';
    $params[] = $filter_status;
}

$complaints = $db->getRows(
    'SELECT c.*, 
            cust.customer_name, cust.customer_email,
            im.item_name,
            cpit.name AS product_issue_name,
            csit.name AS service_issue_name,
            u.username AS assigned_username
     FROM complaints c
     LEFT JOIN customer cust ON cust.customer_id = c.customer_id
     LEFT JOIN item_master im ON im.item_id = c.product_id
     LEFT JOIN complaint_product_issue_type cpit ON cpit.id = c.product_issue_type_id
     LEFT JOIN complaint_service_issue_type csit ON csit.id = c.service_issue_type_id
     LEFT JOIN users u ON u.userid = c.assigned_user_id'
    . $where .
    ' ORDER BY c.complaint_id DESC',
    $params
);

// Count stats
$stats = $db->getRow(
    'SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = "Open" THEN 1 ELSE 0 END) as open_count,
        SUM(CASE WHEN status = "Assigned" THEN 1 ELSE 0 END) as assigned_count,
        SUM(CASE WHEN status = "In Progress" THEN 1 ELSE 0 END) as progress_count,
        SUM(CASE WHEN status = "Closed" THEN 1 ELSE 0 END) as closed_count
     FROM complaints',
    []
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include('common/head.php'); ?>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/dt/dt-1.10.13/datatables.min.css" />
    <style>
        .stat-card { text-align: center; padding: 15px; border-radius: 8px; margin-bottom: 15px; color: #fff; }
        .stat-card h3 { margin: 0; font-size: 28px; }
        .stat-card p { margin: 5px 0 0; font-size: 13px; }
        .stat-open { background: #f39c12; }
        .stat-assigned { background: #3498db; }
        .stat-progress { background: #9b59b6; }
        .stat-closed { background: #27ae60; }
        .stat-total { background: #34495e; }
        .badge-open { background: #f39c12; color: #fff; padding: 3px 8px; border-radius: 3px; font-size: 12px; }
        .badge-assigned { background: #3498db; color: #fff; padding: 3px 8px; border-radius: 3px; font-size: 12px; }
        .badge-inprogress { background: #9b59b6; color: #fff; padding: 3px 8px; border-radius: 3px; font-size: 12px; }
        .badge-closed { background: #27ae60; color: #fff; padding: 3px 8px; border-radius: 3px; font-size: 12px; }
        .type-product { background: #e74c3c; color: #fff; padding: 3px 8px; border-radius: 3px; font-size: 12px; }
        .type-service { background: #2980b9; color: #fff; padding: 3px 8px; border-radius: 3px; font-size: 12px; }
    </style>
</head>
<body class="page-header-fixed">
    <?php include('common/manubar.php'); ?>
    <div class="page-container">
        <div class="page-content-wrapper">
            <div class="page-content">
                <div class="page-bar">
                    <ul class="page-breadcrumb">
                        <li><a href="index.php">Dashboard</a><i class="fa fa-angle-right"></i></li>
                        <li><span>Manage Complaints</span></li>
                    </ul>
                </div>

                <h1 class="page-title"> Complaints & Feedback </h1>

                <!-- Stats -->
                <div class="row">
                    <div class="col-md-2 col-sm-4 col-xs-6">
                        <div class="stat-card stat-total">
                            <h3><?php echo (int)$stats['total']; ?></h3>
                            <p>Total</p>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-4 col-xs-6">
                        <a href="?status=Open" style="text-decoration:none;">
                        <div class="stat-card stat-open">
                            <h3><?php echo (int)$stats['open_count']; ?></h3>
                            <p>Open</p>
                        </div>
                        </a>
                    </div>
                    <div class="col-md-2 col-sm-4 col-xs-6">
                        <a href="?status=Assigned" style="text-decoration:none;">
                        <div class="stat-card stat-assigned">
                            <h3><?php echo (int)$stats['assigned_count']; ?></h3>
                            <p>Assigned</p>
                        </div>
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-4 col-xs-6">
                        <a href="?status=In Progress" style="text-decoration:none;">
                        <div class="stat-card stat-progress">
                            <h3><?php echo (int)$stats['progress_count']; ?></h3>
                            <p>In Progress</p>
                        </div>
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-4 col-xs-6">
                        <a href="?status=Closed" style="text-decoration:none;">
                        <div class="stat-card stat-closed">
                            <h3><?php echo (int)$stats['closed_count']; ?></h3>
                            <p>Closed</p>
                        </div>
                        </a>
                    </div>
                </div>

                <!-- Filters -->
                <div class="row" style="margin-bottom: 15px;">
                    <div class="col-md-12">
                        <a href="manage-complaints.php" class="btn btn-default btn-sm <?php echo (empty($filter_type) && empty($filter_status)) ? 'active' : ''; ?>">All</a>
                        <a href="?type=Product" class="btn btn-danger btn-sm <?php echo ($filter_type == 'Product') ? 'active' : ''; ?>">Product</a>
                        <a href="?type=Service" class="btn btn-info btn-sm <?php echo ($filter_type == 'Service') ? 'active' : ''; ?>">Service</a>
                    </div>
                </div>

                <div class="portlet light bordered">
                    <div class="portlet-body">
                        <table class="table table-striped table-bordered" id="complaintsTable">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Customer</th>
                                    <th>Type</th>
                                    <th>Issue</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Assigned To</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($complaints) { foreach ($complaints as $c) {
                                    $statusClass = 'badge-open';
                                    if ($c['status'] == 'Assigned') $statusClass = 'badge-assigned';
                                    elseif ($c['status'] == 'In Progress') $statusClass = 'badge-inprogress';
                                    elseif ($c['status'] == 'Closed') $statusClass = 'badge-closed';

                                    $typeClass = ($c['complaint_type'] == 'Product') ? 'type-product' : 'type-service';

                                    $issue = '';
                                    if ($c['complaint_type'] == 'Product') {
                                        $issue = htmlspecialchars(($c['item_name'] ?? '') . ' - ' . ($c['product_issue_name'] ?? ''), ENT_QUOTES, 'UTF-8');
                                    } else {
                                        $issue = htmlspecialchars($c['service_issue_name'] ?? '', ENT_QUOTES, 'UTF-8');
                                    }
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($c['complaint_code'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($c['customer_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><span class="<?php echo $typeClass; ?>"><?php echo htmlspecialchars($c['complaint_type'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                    <td><?php echo $issue; ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($c['created_at'])); ?></td>
                                    <td><span class="<?php echo $statusClass; ?>"><?php echo htmlspecialchars($c['status'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                    <td><?php echo htmlspecialchars($c['assigned_username'] ?? 'Not Assigned', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <a href="resolve-complaint.php?id=<?php echo (int)$c['complaint_id']; ?>" class="btn btn-primary btn-xs"><i class="fa fa-eye"></i> View / Resolve</a>
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

    <script src="../plugins/jquery.min.js"></script>
    <script src="../plugins/bootstrap/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/v/dt/dt-1.10.13/datatables.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#complaintsTable').DataTable({ order: [[4, 'desc']] });
    });
    </script>
</body>
</html>
