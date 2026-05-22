<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');

function getPurchaseNotes()
{
    $db = new Database();
    $location = 1;
    return $db->getRows('SELECT * FROM purchase_note_header WHERE location_id = ? ORDER BY purchase_note_id DESC', [$location]);
}

function getSupplierName($supplierId)
{
    $db = new Database();
    $row = $db->getRow('SELECT supplier_name FROM supplier WHERE supplier_id = ?', [$supplierId]);
    return $row['supplier_name'] ?? '';
}

function getUserName($userId)
{
    if (empty($userId)) {
        return '';
    }
    $db = new Database();
    $row = $db->getRow('SELECT first_name, last_name FROM users WHERE userid = ?', [$userId]);
    if (!$row) {
        return $userId;
    }
    return trim($row['first_name'] . ' ' . $row['last_name']);
}

function getPurchaseStats()
{
    $db = new Database();
    $location = 1;
    
    $stats = [
        'total' => 0,
        'open' => 0,
        'partial' => 0,
        'completed' => 0
    ];
    
    $notes = $db->getRows('SELECT status FROM purchase_note_header WHERE location_id = ?', [$location]);
    
    foreach ($notes as $note) {
        $stats['total']++;
        if ($note['status'] === 'OPEN') $stats['open']++;
        elseif ($note['status'] === 'PARTIALLY_RECEIVED') $stats['partial']++;
        elseif ($note['status'] === 'COMPLETED') $stats['completed']++;
    }
    
    return $stats;
}

$message = $_GET['message'] ?? '';
$type = $_GET['type'] ?? '';
$stats = getPurchaseStats();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Purchase Orders | WebStore</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <meta content="" name="description" />
    <meta content="" name="author" />
    <?php include('common/head.php'); ?>
    <link href="assets/global/plugins/datatables/datatables.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.css" rel="stylesheet" type="text/css" />
    <style>
        .stat-box {
            background: #f9f9f9;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #eee;
            border-left: 3px solid #666;
            text-align: center;
        }
        .stat-box.open { border-left-color: #f39c12; }
        .stat-box.partial { border-left-color: #36c6d3; }
        .stat-box.completed { border-left-color: #26a69a; }
        .stat-box.total { border-left-color: #95a5a6; }
        .stat-box-title {
            text-transform: uppercase;
            font-size: 12px;
            font-weight: 600;
            color: #888;
            margin-bottom: 5px;
        }
        .stat-box-value {
            font-size: 24px;
            font-weight: 600;
            color: #333;
        }
        .table-hover > tbody > tr:hover {
            background-color: #f5f5f5;
        }
    </style>
</head>
<body class="page-sidebar-closed-hide-logo page-content-white">
<?php include('common/manubar.php'); ?>
<div class="clearfix"> </div>
<div class="page-container">
    <div class="page-sidebar-wrapper">
        <?php include('common/sidebar.php'); ?>
    </div>
    <div class="page-content-wrapper">
        <div class="page-content">
            <!-- Breadcrumb -->
            <div class="page-bar">
                <ul class="page-breadcrumb">
                    <li>
                        <a href="index.php">Home</a>
                        <i class="fa fa-circle"></i>
                    </li>
                    <li>
                        <span>Purchase Orders</span>
                    </li>
                </ul>
                <div class="page-toolbar">
                    <div class="btn-group pull-right">
                        <a href="purchase-order-create.php" class="btn btn-fit-height green">
                            <i class="fa fa-plus"></i> New Purchase Order
                        </a>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <?php if (!empty($message)) { ?>
                <div class="alert <?php echo ($type === 'error') ? 'alert-danger' : 'alert-success'; ?> alert-dismissable" style="margin-top: 15px;">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php } ?>

            <h3 class="page-title"> Purchase Orders
                <small>manage your purchase notes and goods receipts</small>
            </h3>

            <!-- Summary Stats -->
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-box total">
                        <div class="stat-box-title">Total Orders</div>
                        <div class="stat-box-value"><?php echo $stats['total']; ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box open">
                        <div class="stat-box-title">Open Orders</div>
                        <div class="stat-box-value"><?php echo $stats['open']; ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box partial">
                        <div class="stat-box-title">Partially Received</div>
                        <div class="stat-box-value"><?php echo $stats['partial']; ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box completed">
                        <div class="stat-box-title">Completed</div>
                        <div class="stat-box-value"><?php echo $stats['completed']; ?></div>
                    </div>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="row">
                <div class="col-md-12">
                    <div class="portlet light bordered">
                        <div class="portlet-title">
                            <div class="caption">
                                <i class="icon-docs font-dark"></i>
                                <span class="caption-subject font-dark sbold uppercase">Purchase Orders</span>
                            </div>
                            <div class="actions">
                                <a href="purchase-order-create.php" class="btn btn-circle btn-default">
                                    <i class="fa fa-plus"></i> Create New
                                </a>
                            </div>
                        </div>
                        <div class="portlet-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover dt-responsive" width="100%" id="purchase_orders_table">
                                    <thead>
                                    <tr class="uppercase">
                                        <th> Order Code </th>
                                        <th> Supplier </th>
                                        <th> Date </th>
                                        <th> Status </th>
                                        <th> Created By </th>
                                        <th> Actions </th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach (getPurchaseNotes() as $note) {
                                        $status = $note['status'];
                                        $statusClass = 'default';
                                        $statusIcon = 'question';
                                        if ($status === 'OPEN') {
                                            $statusClass = 'warning';
                                            $statusIcon = 'clock-o';
                                        } elseif ($status === 'PARTIALLY_RECEIVED') {
                                            $statusClass = 'info';
                                            $statusIcon = 'hourglass-half';
                                        } elseif ($status === 'COMPLETED') {
                                            $statusClass = 'success';
                                            $statusIcon = 'check';
                                        }
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo $note['purchase_note_code']; ?></strong>
                                            </td>
                                            <td><?php echo getSupplierName($note['supplier_id']); ?></td>
                                            <td><?php echo date('d M Y', strtotime($note['purchase_date'])); ?></td>
                                            <td>
                                                <span class="label label-<?php echo $statusClass; ?> sbold">
                                                    <i class="fa fa-<?php echo $statusIcon; ?>"></i> <?php echo str_replace('_', ' ', $status); ?>
                                                </span>
                                            </td>
                                            <td><?php echo getUserName($note['created_by']); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="purchase-order-view.php?id=<?php echo $note['purchase_note_id']; ?>" class="btn btn-xs btn-default" title="View Details">
                                                        <i class="fa fa-eye"></i> View
                                                    </a>
                                                    <?php if ($status !== 'COMPLETED') { ?>
                                                        <a href="grn-create.php?purchase_note_id=<?php echo $note['purchase_note_id']; ?>" class="btn btn-xs btn-success" title="Receive Goods">
                                                            <i class="fa fa-truck"></i> Receive
                                                        </a>
                                                    <?php } else { ?>
                                                        <button class="btn btn-xs btn-default disabled" title="Order Completed">
                                                            <i class="fa fa-check"></i> Done
                                                        </button>
                                                    <?php } ?>
                                                </div>
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

        </div>
    </div>
</div>

<?php include('common/footer.php'); ?>

<!-- Scripts -->
<script src="assets/global/plugins/jquery.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/js.cookie.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/bootstrap-hover-dropdown/bootstrap-hover-dropdown.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/jquery-slimscroll/jquery.slimscroll.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/jquery.blockui.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/uniform/jquery.uniform.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/bootstrap-switch/js/bootstrap-switch.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/datatables/datatables.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.js" type="text/javascript"></script>
<script src="assets/global/scripts/app.min.js" type="text/javascript"></script>
<script src="assets/layouts/layout/scripts/layout.min.js" type="text/javascript"></script>

<script>
jQuery(document).ready(function () {
    if (jQuery().dataTable) {
        jQuery('#purchase_orders_table').DataTable({
            "order": [[ 2, "desc" ]], // Sort by date descending
            "pageLength": 25,
            "language": {
                "emptyTable": "No purchase orders found."
            }
        });
    }
});
</script>

</body>
</html>
