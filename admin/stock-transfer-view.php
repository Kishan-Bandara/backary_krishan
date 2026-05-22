<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');
include('get_url.php');

$db = new Database();
$transferId = (int) ($_GET['id'] ?? 0);
$message = $_GET['message'] ?? '';
$type = $_GET['type'] ?? '';

if ($transferId <= 0) {
    redirect('stock-transfer-list.php?message=' . urlencode('Invalid transfer.') . '&type=error');
}

$transfer = $db->getRow('SELECT * FROM stock_transfer_header WHERE transfer_id = ?', [$transferId]);
if (!$transfer) {
    redirect('stock-transfer-list.php?message=' . urlencode('Transfer not found.') . '&type=error');
}

$fromLocation = $db->getRow('SELECT location_code, name, address, phone_no FROM location_master WHERE id = ?', [$transfer['from_location_id']]);
$toLocation = $db->getRow('SELECT location_code, name, address, phone_no FROM location_master WHERE id = ?', [$transfer['to_location_id']]);
$items = $db->getRows('SELECT sti.*, itm.item_name, itm.item_code FROM stock_transfer_items sti JOIN item_master itm ON itm.item_id = sti.product_id WHERE sti.transfer_id = ?', [$transferId]);

// find any GRN created for this transfer
$grn_for_transfer = $db->getRow('SELECT * FROM grn_hedder WHERE grn_h_supplier_invoice_code = ? AND grn_h_location = ? ORDER BY grn_h_id DESC LIMIT 1', ['Stock Transfer: ' . $transfer['transfer_code'], $transfer['to_location_id']]);

$statusLabel = 'label-default';
if ($transfer['status'] === 'PENDING') $statusLabel = 'label-warning';
if ($transfer['status'] === 'COMPLETED') $statusLabel = 'label-success';
if ($transfer['status'] === 'CANCELLED') $statusLabel = 'label-danger';

$canConfirmReceive = false;
if ($transfer['status'] === 'PENDING') {
    $sessionLocation = (int) ($_SESSION['location'] ?? 0);
    $canConfirmReceive = (function_exists('isSuperAdmin') && isSuperAdmin()) || $sessionLocation === (int) $transfer['to_location_id'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Stock Transfer View | WebStore</title>
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
                        <a href="stock-transfer-list.php">Stock Transfers</a>
                        <i class="fa fa-circle"></i>
                    </li>
                    <li>
                        <span>Transfer Details</span>
                    </li>
                </ul>
                <div class="page-toolbar">
                    <div class="btn-group pull-right">
                        <a href="stock-transfer-list.php" class="btn btn-fit-height white btn-outline">
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

            <h3 class="page-title"> Stock Transfer Details
                <small><?php echo $transfer['transfer_code']; ?></small>
            </h3>

            <div class="row">
                <div class="col-md-12">
                    <div class="portlet light bordered">
                        <div class="portlet-title">
                            <div class="caption">
                                <i class="icon-docs font-dark"></i>
                                <span class="caption-subject font-dark sbold uppercase">Transfer Summary</span>
                                <span class="label <?php echo $statusLabel; ?> sbold" style="margin-left:10px;">
                                    <?php echo str_replace('_', ' ', $transfer['status']); ?>
                                </span>
                            </div>
                            <div class="actions">
                                <a href="stock-transfer-print.php?id=<?php echo $transferId; ?>" target="_blank" class="btn btn-circle btn-default">
                                    <i class="fa fa-print"></i> Print
                                </a>
                                <?php if ($canConfirmReceive) { ?>
                                    <a href="stock-transfer-receive.php?id=<?php echo $transferId; ?>" class="btn btn-circle btn-success">
                                        <i class="fa fa-check"></i> Confirm Receive
                                    </a>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="portlet-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="stat-box">
                                        <div class="stat-box-title">Transfer Code</div>
                                        <div class="stat-box-value"><?php echo $transfer['transfer_code']; ?></div>
                                        <div>Date: <strong><?php echo date('d M Y', strtotime($transfer['transfer_date'])); ?></strong></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="stat-box">
                                        <div class="stat-box-title">From Location</div>
                                        <div class="stat-box-value"><?php echo trim(($fromLocation['location_code'] ?? '') . ' - ' . ($fromLocation['name'] ?? '')); ?></div>
                                        <div><?php echo $fromLocation['address'] ?? ''; ?></div>
                                        <div><?php echo $fromLocation['phone_no'] ?? ''; ?></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="stat-box">
                                        <div class="stat-box-title">To Location</div>
                                        <div class="stat-box-value"><?php echo trim(($toLocation['location_code'] ?? '') . ' - ' . ($toLocation['name'] ?? '')); ?></div>
                                        <div><?php echo $toLocation['address'] ?? ''; ?></div>
                                        <div><?php echo $toLocation['phone_no'] ?? ''; ?></div>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($transfer['remarks'])) { ?>
                                <div class="note note-info" style="margin-top: 10px;">
                                    <h4 class="block">Remarks</h4>
                                    <p><?php echo nl2br(htmlspecialchars($transfer['remarks'])); ?></p>
                                </div>
                            <?php } ?>

                            <hr />

                            <?php if ($grn_for_transfer) { ?>
                                <div class="alert alert-info">
                                    <strong>GRN Created:</strong> <a href="purchase_note.php?id=<?php echo $grn_for_transfer['grn_h_id']; ?>"><?php echo htmlspecialchars($grn_for_transfer['grn_h_code']); ?></a>
                                </div>
                            <?php } ?>

                            <h4><i class="icon-basket"></i> Items Transferred</h4>
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

                        </div>
                    </div>
                </div>
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
