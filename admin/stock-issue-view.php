<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');
include('get_url.php');

$db = new Database();
$issueId = (int) ($_GET['id'] ?? 0);
$message = $_GET['message'] ?? '';
$type = $_GET['type'] ?? '';

if ($issueId <= 0) {
    redirect('stock-issue-list.php?message=' . urlencode('Invalid issue note.') . '&type=error');
}

$issue = $db->getRow('SELECT * FROM stock_issue_header WHERE issue_id = ?', [$issueId]);
if (!$issue) {
    redirect('stock-issue-list.php?message=' . urlencode('Issue note not found.') . '&type=error');
}

if (!isSuperAdmin() && (int) $issue['location_id'] !== (int) $_SESSION['location']) {
    redirect('stock-issue-list.php?message=' . urlencode('Access denied.') . '&type=error');
}

$location = $db->getRow('SELECT location_code, name, address, phone_no FROM location_master WHERE id = ?', [$issue['location_id']]);
$items = $db->getRows('SELECT sii.*, itm.item_name, itm.item_code FROM stock_issue_items sii JOIN item_master itm ON itm.item_id = sii.product_id WHERE sii.issue_id = ?', [$issueId]);

// Expected finished products
$expectedProducts = $db->getRows(
    'SELECT ep.*, itm.item_name, itm.item_code 
     FROM stock_issue_expected_products ep 
     JOIN item_master itm ON itm.item_id = ep.product_id 
     WHERE ep.issue_id = ?',
    [$issueId]
);

// Destination location
$toLocation = null;
if (!empty($issue['to_location_id'])) {
    $toLocation = $db->getRow('SELECT location_code, name FROM location_master WHERE id = ?', [$issue['to_location_id']]);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Stock Issue Note | WebStore</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <meta content="" name="description" />
    <meta content="" name="author" />
    <?php include('common/head.php'); ?>
    <style>
        .stat-box {
            background: #f9f9f9;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #eee;
            border-left: 3px solid #357e30;
        }
        .stat-box-title {
            text-transform: uppercase;
            font-size: 12px;
            font-weight: 600;
            color: #888;
            margin-bottom: 5px;
        }
        .stat-box-value {
            font-size: 16px;
            font-weight: 600;
            color: #333;
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
            <div class="page-bar">
                <ul class="page-breadcrumb">
                    <li>
                        <a href="index.php">Home</a>
                        <i class="fa fa-circle"></i>
                    </li>
                    <li>
                        <a href="stock-issue-list.php">Stock Issue Notes</a>
                        <i class="fa fa-circle"></i>
                    </li>
                    <li>
                        <span>Issue Note Details</span>
                    </li>
                </ul>
                <div class="page-toolbar">
                    <div class="btn-group pull-right">
                        <a href="stock-issue-list.php" class="btn btn-fit-height white btn-outline">
                            <i class="fa fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
            </div>

            <?php if (!empty($message)) { ?>
                <div class="alert <?php echo ($type === 'error') ? 'alert-danger' : 'alert-success'; ?> alert-dismissable" style="margin-top: 15px;">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php } ?>

            <h3 class="page-title"> Stock Issue Note
                <small><?php echo $issue['issue_code']; ?></small>
            </h3>

            <div class="row">
                <div class="col-md-12">
                    <div class="portlet light bordered">
                        <div class="portlet-title">
                            <div class="caption">
                                <i class="icon-docs font-dark"></i>
                                <span class="caption-subject font-dark sbold uppercase">Issue Summary</span>
                            </div>
                        </div>
                        <div class="portlet-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="stat-box">
                                        <div class="stat-box-title">Issue Code</div>
                                        <div class="stat-box-value"><?php echo $issue['issue_code']; ?></div>
                                        <div>Date: <strong><?php echo date('d M Y', strtotime($issue['issue_date'])); ?></strong></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="stat-box">
                                        <div class="stat-box-title">Location</div>
                                        <div class="stat-box-value"><?php echo trim(($location['location_code'] ?? '') . ' - ' . ($location['name'] ?? '')); ?></div>
                                        <div><?php echo $location['address'] ?? ''; ?></div>
                                        <div><?php echo $location['phone_no'] ?? ''; ?></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="stat-box">
                                        <div class="stat-box-title">Issued To</div>
                                        <div class="stat-box-value"><?php echo htmlspecialchars($issue['issued_to'] ?? ''); ?></div>
                                        <div>Status: <strong><?php echo $issue['status']; ?></strong></div>
                                        <?php if (!empty($issue['production_status'])) { ?>
                                            <div style="margin-top:5px;">Production: 
                                                <strong>
                                                    <?php 
                                                    $psBadge = 'default';
                                                    if ($issue['production_status'] === 'PENDING') $psBadge = 'warning';
                                                    elseif ($issue['production_status'] === 'PARTIALLY_RECEIVED') $psBadge = 'info';
                                                    elseif ($issue['production_status'] === 'COMPLETED') $psBadge = 'success';
                                                    ?>
                                                    <span class="label label-<?php echo $psBadge; ?>"><?php echo $issue['production_status']; ?></span>
                                                </strong>
                                            </div>
                                        <?php } ?>
                                        <?php if ($toLocation) { ?>
                                            <div style="margin-top:5px;">Destination: <strong><?php echo trim($toLocation['location_code'] . ' - ' . $toLocation['name']); ?></strong></div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($issue['remarks'])) { ?>
                                <div class="note note-info" style="margin-top: 10px;">
                                    <h4 class="block">Remarks</h4>
                                    <p><?php echo nl2br(htmlspecialchars($issue['remarks'])); ?></p>
                                </div>
                            <?php } ?>

                            <hr />

                            <h4><i class="icon-basket"></i> Issued Items</h4>
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover">
                                    <thead>
                                    <tr class="uppercase">
                                        <th> Item Code </th>
                                        <th> Item Name </th>
                                        <th class="text-center"> Qty </th>
                                        <th class="text-center"> Rate </th>
                                        <th class="text-center"> Total </th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (count($items) === 0) { ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No items found.</td>
                                        </tr>
                                    <?php } else { ?>
                                        <?php foreach ($items as $item) { ?>
                                            <tr>
                                                <td><?php echo $item['item_code']; ?></td>
                                                <td><?php echo $item['item_name']; ?></td>
                                                <td class="text-center"><strong><?php echo $item['qty']; ?></strong></td>
                                                <td class="text-center"><?php echo number_format((float) $item['rate'], 2); ?></td>
                                                <td class="text-center"><?php echo number_format((float) $item['total'], 2); ?></td>
                                            </tr>
                                        <?php } ?>
                                    <?php } ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php if ($expectedProducts && count($expectedProducts) > 0) { ?>
                                <hr />
                                <h4><i class="fa fa-industry"></i> Expected Finished Products</h4>
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover">
                                        <thead>
                                        <tr class="uppercase">
                                            <th> Item Code </th>
                                            <th> Product Name </th>
                                            <th class="text-center"> Expected Qty </th>
                                            <th class="text-center"> Received Qty </th>
                                            <th class="text-center"> Status </th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($expectedProducts as $ep) { 
                                            $epBadge = 'default';
                                            if ($ep['status'] === 'PENDING') $epBadge = 'warning';
                                            elseif ($ep['status'] === 'PARTIALLY_RECEIVED') $epBadge = 'info';
                                            elseif ($ep['status'] === 'COMPLETED') $epBadge = 'success';
                                        ?>
                                            <tr>
                                                <td><?php echo $ep['item_code']; ?></td>
                                                <td><?php echo $ep['item_name']; ?></td>
                                                <td class="text-center"><strong><?php echo $ep['expected_qty']; ?></strong></td>
                                                <td class="text-center"><?php echo $ep['received_qty']; ?></td>
                                                <td class="text-center"><span class="label label-<?php echo $epBadge; ?>"><?php echo $ep['status']; ?></span></td>
                                            </tr>
                                        <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php } ?>

            </div>

        </div>
    </div>
</div>

<?php include('common/footer.php'); ?>

<script src="assets/global/plugins/jquery.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/js.cookie.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/bootstrap-hover-dropdown/bootstrap-hover-dropdown.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/jquery-slimscroll/jquery.slimscroll.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/jquery.blockui.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/uniform/jquery.uniform.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/bootstrap-switch/js/bootstrap-switch.min.js" type="text/javascript"></script>
<script src="assets/global/scripts/app.min.js" type="text/javascript"></script>
<script src="assets/layouts/layout/scripts/layout.min.js" type="text/javascript"></script>

</body>
</html>
