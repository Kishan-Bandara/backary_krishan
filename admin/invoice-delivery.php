<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');
include('get_url.php');

date_default_timezone_set("Asia/Colombo");

$db = new Database();
$invoiceId = (int) ($_GET['id'] ?? 0);
$message = $_GET['message'] ?? '';
$type = $_GET['type'] ?? '';

if ($invoiceId <= 0) {
    redirect('manage-invoices.php?message=' . urlencode('Invalid invoice.') . '&type=error');
}

// Auto-migrate columns (idempotent) so the page never breaks on first hit
$colCheck = $db->getRow("SHOW COLUMNS FROM invoice_details LIKE 'batch_id'");
if (!$colCheck) {
    $db->insertRow("ALTER TABLE invoice_details ADD COLUMN `batch_id` INT(11) DEFAULT NULL AFTER `is_cart_item`", []);
}
$colCheck2 = $db->getRow("SHOW COLUMNS FROM invoice_hedder LIKE 'delivery_status'");
if (!$colCheck2) {
    $db->insertRow("ALTER TABLE invoice_hedder ADD COLUMN `delivery_status` VARCHAR(20) NOT NULL DEFAULT 'PENDING' AFTER `invoice_h_status`", []);
    $db->insertRow("ALTER TABLE invoice_hedder ADD COLUMN `delivered_at`    DATETIME    DEFAULT NULL              AFTER `delivery_status`", []);
    $db->insertRow("ALTER TABLE invoice_hedder ADD COLUMN `delivered_by`    VARCHAR(100) DEFAULT NULL              AFTER `delivered_at`", []);
}

$invoice = $db->getRow('SELECT * FROM invoice_hedder WHERE invoice_h_id = ?', [$invoiceId]);
if (!$invoice) {
    redirect('manage-invoices.php?message=' . urlencode('Invoice not found.') . '&type=error');
}

$customer = $db->getRow('SELECT * FROM customer WHERE customer_id = ?', [$invoice['invoice_h_customer_id']]);
$location = $db->getRow('SELECT name, location_code FROM location_master WHERE id = ?', [$invoice['invoice_h_location']]);
$locationId = (int) $invoice['invoice_h_location'];

$items = $db->getRows(
    'SELECT id.*, itm.item_name, itm.item_code, itm.unit_of_measure, itm.batch_tracking,
            bm.batch_no AS picked_batch_no, bm.expiry_date AS picked_expiry
     FROM invoice_details id
     JOIN item_master itm ON itm.item_id = id.invoice_d_item_id
     LEFT JOIN batch_master bm ON bm.batch_id = id.batch_id
     WHERE id.invoice_h_id = ?
     ORDER BY id.invoice_d_id ASC',
    [$invoiceId]
);

// For each tracked product, pre-fetch available batches at this location
$batchOptions = [];
foreach ($items as $it) {
    $bt = $it['batch_tracking'] ?? 'NONE';
    if (!in_array($bt, ['BATCH', 'SERIAL'], true)) continue;
    $pid = (int) $it['invoice_d_item_id'];
    if (isset($batchOptions[$pid])) continue;
    $batchOptions[$pid] = $db->getRows(
        'SELECT bm.batch_id, bm.batch_no, bm.expiry_date, SUM(f.ft_blanace) AS available_qty
         FROM fifo f
         INNER JOIN batch_master bm ON bm.batch_id = f.batch_id
         WHERE f.ft_item = ? AND f.ft_location = ? AND f.ft_type = 1 AND f.ft_blanace > 0
         GROUP BY bm.batch_id, bm.batch_no, bm.expiry_date
         HAVING available_qty > 0
         ORDER BY bm.expiry_date ASC, bm.batch_no ASC',
        [$pid, $locationId]
    );
}

$deliveryStatus = $invoice['delivery_status'] ?? 'PENDING';
$isDelivered = ($deliveryStatus === 'DELIVERED');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Invoice Delivery | WebStore</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
    <style>
        .stat-box {
            background: #f9f9f9;
            padding: 12px;
            border: 1px solid #eee;
            border-left: 3px solid #2980b9;
        }
        .stat-box-title { font-size: 11px; text-transform: uppercase; color: #888; margin-bottom: 4px; }
        .stat-box-value { font-size: 16px; font-weight: 600; color: #333; }
        .badge-status { padding: 4px 12px; font-size: 12px; border-radius: 14px; font-weight: 600; }
        .badge-pending  { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .badge-delivered{ background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .table > tbody > tr > td { vertical-align: middle; }
        .batch-na { color:#999; font-style:italic; }
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
                    <li><a href="index.php">Home</a><i class="fa fa-circle"></i></li>
                    <li><a href="manage-invoices.php">Manage Invoices</a><i class="fa fa-circle"></i></li>
                    <li><span>Delivery — <?php echo htmlspecialchars($invoice['invoice_h_code']); ?></span></li>
                </ul>
            </div>

            <?php if (!empty($message)) { ?>
                <div class="alert <?php echo ($type === 'error') ? 'alert-danger' : 'alert-success'; ?> alert-dismissable">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php } ?>

            <h3 class="page-title">Invoice Delivery
                <small><?php echo htmlspecialchars($invoice['invoice_h_code']); ?></small>
            </h3>

            <form action="process/invoice-delivery-process.php" method="post" id="delivery_form">
                <input type="hidden" name="invoice_h_id" value="<?php echo $invoiceId; ?>" />

                <div class="row">
                    <div class="col-md-12">
                        <div class="portlet light bordered">
                            <div class="portlet-title">
                                <div class="caption">
                                    <i class="icon-docs font-dark"></i>
                                    <span class="caption-subject font-dark sbold uppercase">Invoice Details</span>
                                    <?php if ($isDelivered) { ?>
                                        <span class="badge-status badge-delivered" style="margin-left:10px;">Delivered</span>
                                    <?php } else { ?>
                                        <span class="badge-status badge-pending" style="margin-left:10px;">Pending Delivery</span>
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="portlet-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="stat-box">
                                            <div class="stat-box-title">Invoice No</div>
                                            <div class="stat-box-value"><?php echo htmlspecialchars($invoice['invoice_h_code']); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="stat-box">
                                            <div class="stat-box-title">Customer</div>
                                            <div class="stat-box-value"><?php echo htmlspecialchars($customer['customer_name'] ?? ''); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="stat-box">
                                            <div class="stat-box-title">Invoice Date</div>
                                            <div class="stat-box-value"><?php echo date('d M Y', strtotime($invoice['invoice_h_datetime'] ?? $invoice['invoice_h_date'])); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="stat-box">
                                            <div class="stat-box-title">Delivery Date</div>
                                            <div class="stat-box-value">
                                                <?php echo !empty($invoice['invoice_h_delivery_date']) ? date('d M Y', strtotime($invoice['invoice_h_delivery_date'])) : '<span class="text-muted">—</span>'; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($isDelivered) { ?>
                                    <div class="note note-success" style="margin-top:14px;">
                                        <strong>Delivered:</strong>
                                        <?php echo !empty($invoice['delivered_at']) ? date('d M Y, h:i A', strtotime($invoice['delivered_at'])) : ''; ?>
                                        <?php if (!empty($invoice['delivered_by'])) echo ' by <strong>' . htmlspecialchars($invoice['delivered_by']) . '</strong>'; ?>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="portlet light bordered">
                            <div class="portlet-title">
                                <div class="caption">
                                    <i class="icon-basket font-blue-madison"></i>
                                    <span class="caption-subject font-blue-madison sbold uppercase">Items &mdash; pick batch per line</span>
                                </div>
                            </div>
                            <div class="portlet-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover">
                                        <thead>
                                        <tr class="uppercase">
                                            <th style="width:5%">#</th>
                                            <th style="width:11%">Item Code</th>
                                            <th>Item Name</th>
                                            <th class="text-center" style="width:7%">UOM</th>
                                            <th class="text-center" style="width:8%">Qty</th>
                                            <th class="text-center" style="width:9%">Tracking</th>
                                            <th style="width:30%">Batch (select)</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php $rowNum = 0; foreach ($items as $it) { $rowNum++;
                                            $bt = $it['batch_tracking'] ?? 'NONE';
                                            $isTracked = in_array($bt, ['BATCH', 'SERIAL'], true);
                                            $pid = (int) $it['invoice_d_item_id'];
                                            $opts = $batchOptions[$pid] ?? [];
                                            $currentBatchId = (int) ($it['batch_id'] ?? 0);
                                        ?>
                                            <tr>
                                                <td><?php echo $rowNum; ?></td>
                                                <td><?php echo htmlspecialchars($it['item_code']); ?></td>
                                                <td><strong><?php echo htmlspecialchars($it['item_name']); ?></strong></td>
                                                <td class="text-center"><?php echo htmlspecialchars($it['unit_of_measure'] ?? ''); ?></td>
                                                <td class="text-center"><strong><?php echo number_format((float)$it['invoice_d_qty'], 2); ?></strong></td>
                                                <td class="text-center">
                                                    <?php if ($isTracked) { ?>
                                                        <span class="label label-info"><?php echo $bt; ?></span>
                                                    <?php } else { ?>
                                                        <span class="text-muted">None</span>
                                                    <?php } ?>
                                                </td>
                                                <td>
                                                    <input type="hidden" name="invoice_d_id[]" value="<?php echo $it['invoice_d_id']; ?>"/>
                                                    <?php if (!$isTracked) { ?>
                                                        <span class="batch-na">N/A &mdash; not batch tracked</span>
                                                        <input type="hidden" name="batch_id[]" value=""/>
                                                    <?php } elseif ($isDelivered) { ?>
                                                        <?php if (!empty($it['picked_batch_no'])) { ?>
                                                            <span class="label label-success" style="font-size:12px;">
                                                                <?php echo htmlspecialchars($it['picked_batch_no']); ?>
                                                            </span>
                                                            <?php if (!empty($it['picked_expiry'])) { ?>
                                                                <small class="text-muted">exp: <?php echo date('d M Y', strtotime($it['picked_expiry'])); ?></small>
                                                            <?php } ?>
                                                        <?php } else { ?>
                                                            <span class="text-muted">No batch recorded</span>
                                                        <?php } ?>
                                                        <input type="hidden" name="batch_id[]" value="<?php echo $currentBatchId ?: ''; ?>"/>
                                                    <?php } else { ?>
                                                        <select name="batch_id[]" class="form-control batch-select" data-required="1">
                                                            <option value="">— Select Batch —</option>
                                                            <?php foreach ($opts as $opt) {
                                                                $exp = !empty($opt['expiry_date']) && $opt['expiry_date'] !== '0000-00-00'
                                                                    ? ' (exp ' . date('d M Y', strtotime($opt['expiry_date'])) . ')'
                                                                    : '';
                                                                $avail = number_format((float)$opt['available_qty'], 2);
                                                            ?>
                                                                <option value="<?php echo $opt['batch_id']; ?>" <?php echo ($currentBatchId === (int)$opt['batch_id']) ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($opt['batch_no'] . $exp); ?> &mdash; available: <?php echo $avail; ?>
                                                                </option>
                                                            <?php } ?>
                                                        </select>
                                                        <?php if (empty($opts)) { ?>
                                                            <small class="text-danger">No batch stock available at this location.</small>
                                                        <?php } ?>
                                                    <?php } ?>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                        <?php if (empty($items)) { ?>
                                            <tr><td colspan="7" class="text-center text-muted">No items on this invoice.</td></tr>
                                        <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-actions right">
                    <a href="manage-invoices.php" class="btn default">Back</a>
                    <a href="invoice.php?id=<?php echo $invoiceId; ?>" class="btn btn-info" target="_blank">
                        <i class="fa fa-eye"></i> View Invoice
                    </a>
                    <?php if (!$isDelivered) { ?>
                        <button type="submit" class="btn btn-success" id="btn_mark_delivered">
                            <i class="fa fa-check"></i> Save Batches &amp; Mark Delivered
                        </button>
                    <?php } else { ?>
                        <button type="button" class="btn btn-success" disabled>
                            <i class="fa fa-check"></i> Already Delivered
                        </button>
                    <?php } ?>
                </div>

            </form>

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

<script>
$(document).ready(function () {
    $('#delivery_form').on('submit', function (e) {
        var missing = false;
        $('select.batch-select[data-required="1"]').each(function () {
            if (!$(this).val()) { missing = true; return false; }
        });
        if (missing) {
            e.preventDefault();
            alert('Please select a batch for every batch-tracked item before marking delivered.');
            return false;
        }
        return confirm('Confirm marking this invoice as DELIVERED? This action will save the chosen batches and cannot be undone.');
    });
});
</script>
</body>
</html>
