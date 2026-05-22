<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');
include('get_url.php');

date_default_timezone_set("Asia/Colombo");

$db = new Database();
$issueId = (int) ($_GET['issue_id'] ?? 0);
$message = $_GET['message'] ?? '';
$type = $_GET['type'] ?? '';

if ($issueId <= 0) {
    redirect('production-receive-list.php?message=' . urlencode('Invalid issue note.') . '&type=error');
}

$issue = $db->getRow('SELECT * FROM stock_issue_header WHERE issue_id = ?', [$issueId]);
if (!$issue) {
    redirect('production-receive-list.php?message=' . urlencode('Issue note not found.') . '&type=error');
}

if (!in_array($issue['production_status'], ['PENDING', 'PARTIALLY_RECEIVED'])) {
    redirect('production-receive-list.php?message=' . urlencode('This production has already been completed.') . '&type=error');
}

$destinationLocationId = !empty($issue['to_location_id']) ? (int) $issue['to_location_id'] : (int) $issue['location_id'];
$location = $db->getRow('SELECT location_code, name FROM location_master WHERE id = ?', [$issue['location_id']]);
$toLocation = $db->getRow('SELECT location_code, name FROM location_master WHERE id = ?', [$destinationLocationId]);

// Get issued raw materials (with batch information for lineage)
$issuedItems = $db->getRows(
    'SELECT sii.*, itm.item_name, itm.item_code, itm.batch_tracking,
            bm.batch_no AS raw_batch_no, bm.expiry_date AS raw_expiry
     FROM stock_issue_items sii 
     JOIN item_master itm ON itm.item_id = sii.product_id 
     LEFT JOIN batch_master bm ON bm.batch_id = sii.batch_id
     WHERE sii.issue_id = ?',
    [$issueId]
);

// Get expected finished products
$expectedProducts = $db->getRows(
    'SELECT ep.*, itm.item_name, itm.item_code, itm.batch_tracking 
     FROM stock_issue_expected_products ep 
     JOIN item_master itm ON itm.item_id = ep.product_id 
     WHERE ep.issue_id = ? AND ep.status != ?',
    [$issueId, 'COMPLETED']
);

function batchTrackingLabel($trackingMode)
{
    if ($trackingMode === 'BATCH') {
        return 'Batch No Tracking';
    }
    if ($trackingMode === 'SERIAL') {
        return 'Serial No Tracking';
    }
    return 'Disabled';
}

$trackedExpectedProducts = [];
$currentTrackedInventory = [];

if ($expectedProducts) {
    foreach ($expectedProducts as $expectedProduct) {
        $trackingMode = $expectedProduct['batch_tracking'] ?? 'NONE';
        if (in_array($trackingMode, ['BATCH', 'SERIAL'], true)) {
            $trackedExpectedProducts[(int) $expectedProduct['product_id']] = [
                'item_code' => $expectedProduct['item_code'],
                'item_name' => $expectedProduct['item_name'],
                'tracking' => $trackingMode
            ];
        }
    }
}

if (!empty($trackedExpectedProducts)) {
    $placeholders = implode(',', array_fill(0, count($trackedExpectedProducts), '?'));
    $inventoryRows = $db->getRows(
        'SELECT f.ft_item AS product_id, bm.batch_no, bm.expiry_date, SUM(f.ft_blanace) AS qty
         FROM fifo f
         INNER JOIN batch_master bm ON bm.batch_id = f.batch_id
         WHERE f.ft_location = ? AND f.ft_type = 1 AND f.ft_blanace > 0 AND f.ft_item IN (' . $placeholders . ')
         GROUP BY f.ft_item, bm.batch_id, bm.batch_no, bm.expiry_date
         ORDER BY f.ft_item ASC, bm.expiry_date ASC, bm.batch_no ASC',
        array_merge([$destinationLocationId], array_keys($trackedExpectedProducts))
    );

    foreach ($inventoryRows as $inventoryRow) {
        $currentTrackedInventory[(int) $inventoryRow['product_id']][] = $inventoryRow;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Confirm Production Receive | WebStore</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
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
        .receive-qty-input {
            width: 100px;
            text-align: center;
            font-weight: 600;
        }
        .remaining { color: #c9302c; font-weight: 600; }
        .completed-badge { color: #3c763d; font-weight: 600; }
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
                        <a href="production-receive-list.php">Production Receive</a>
                        <i class="fa fa-circle"></i>
                    </li>
                    <li>
                        <span>Confirm Receive</span>
                    </li>
                </ul>
                <div class="page-toolbar">
                    <div class="btn-group pull-right">
                        <a href="production-receive-list.php" class="btn btn-fit-height white btn-outline">
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

            <h3 class="page-title"> Confirm Production Receive
                <small><?php echo htmlspecialchars($issue['issue_code']); ?></small>
            </h3>

            <!-- Issue Summary -->
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
                                <div class="col-md-3">
                                    <div class="stat-box">
                                        <div class="stat-box-title">Issue Code</div>
                                        <div class="stat-box-value"><?php echo $issue['issue_code']; ?></div>
                                        <div>Date: <strong><?php echo date('d M Y', strtotime($issue['issue_date'])); ?></strong></div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-box">
                                        <div class="stat-box-title">From Location</div>
                                        <div class="stat-box-value"><?php echo trim(($location['location_code'] ?? '') . ' - ' . ($location['name'] ?? '')); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-box">
                                        <div class="stat-box-title">To Location (Finished Products)</div>
                                        <div class="stat-box-value"><?php echo trim(($toLocation['location_code'] ?? '') . ' - ' . ($toLocation['name'] ?? '')); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-box">
                                        <div class="stat-box-title">Production Status</div>
                                        <div class="stat-box-value">
                                            <span class="label label-warning"><?php echo $issue['production_status']; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Issued Raw Materials (read-only summary) -->
                            <h4><i class="icon-basket"></i> Issued Raw Materials</h4>
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                    <tr class="uppercase">
                                        <th> Item Code </th>
                                        <th> Item Name </th>
                                        <th> Batch No </th>
                                        <th class="text-center"> Qty Issued </th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php if ($issuedItems) { foreach ($issuedItems as $item) { ?>
                                        <tr>
                                            <td><?php echo $item['item_code']; ?></td>
                                            <td><?php echo $item['item_name']; ?></td>
                                            <td><?php echo !empty($item['raw_batch_no']) ? '<span class="label label-info">' . htmlspecialchars($item['raw_batch_no']) . '</span>' : '<span class="text-muted">N/A</span>'; ?></td>
                                            <td class="text-center"><strong><?php echo $item['qty']; ?></strong></td>
                                        </tr>
                                    <?php } } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($trackedExpectedProducts)) { ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="portlet light bordered">
                        <div class="portlet-title">
                            <div class="caption">
                                <i class="fa fa-tags font-blue"></i>
                                <span class="caption-subject font-blue sbold uppercase">Current Batch / Serial Inventory</span>
                            </div>
                        </div>
                        <div class="portlet-body">
                            <div class="note note-info">
                                Current tracked inventory at destination location: <strong><?php echo htmlspecialchars(trim(($toLocation['location_code'] ?? '') . ' - ' . ($toLocation['name'] ?? ''))); ?></strong>
                            </div>
                            <?php foreach ($trackedExpectedProducts as $trackedProductId => $trackedProduct) { ?>
                                <h4>
                                    <strong><?php echo htmlspecialchars($trackedProduct['item_code'] . ' - ' . $trackedProduct['item_name']); ?></strong>
                                    <small><?php echo htmlspecialchars(batchTrackingLabel($trackedProduct['tracking'])); ?></small>
                                </h4>
                                <div class="table-responsive" style="margin-bottom: 20px;">
                                    <table class="table table-striped table-bordered table-condensed">
                                        <thead>
                                        <tr class="uppercase">
                                            <th><?php echo $trackedProduct['tracking'] === 'SERIAL' ? 'Serial No' : 'Batch No'; ?></th>
                                            <th>Expiry Date</th>
                                            <th class="text-right">Available Qty</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php if (!empty($currentTrackedInventory[$trackedProductId])) { foreach ($currentTrackedInventory[$trackedProductId] as $inventoryRow) {
                                            $trackedExpiry = (!empty($inventoryRow['expiry_date']) && $inventoryRow['expiry_date'] !== '0000-00-00') ? date('d M Y', strtotime($inventoryRow['expiry_date'])) : 'N/A';
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($inventoryRow['batch_no']); ?></td>
                                                <td><?php echo htmlspecialchars($trackedExpiry); ?></td>
                                                <td class="text-right"><strong><?php echo number_format((float) $inventoryRow['qty'], 2); ?></strong></td>
                                            </tr>
                                        <?php } } else { ?>
                                            <tr>
                                                <td colspan="3" class="text-center text-muted">No tracked inventory available yet at this location.</td>
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
            <?php } ?>

            <!-- Receive Form -->
            <div class="row">
                <div class="col-md-12">
                    <div class="portlet light bordered">
                        <div class="portlet-title">
                            <div class="caption">
                                <i class="fa fa-industry font-green-jungle"></i>
                                <span class="caption-subject font-green-jungle sbold uppercase">Receive Finished Products</span>
                            </div>
                        </div>
                        <div class="portlet-body">
                            <form action="process/production-receive-process.php" method="post" id="receive_form" onsubmit="return confirm('Confirm receiving these finished products? This will create a GRN and add stock automatically.');">
                                <input type="hidden" name="issue_id" value="<?php echo $issueId; ?>">
                                <?php if (!empty($trackedExpectedProducts)) { ?>
                                <div class="note note-warning">
                                    Batch or serial tracked finished products require a batch or serial number when you confirm receive.
                                </div>
                                <?php } ?>
                                
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover">
                                        <thead>
                                        <tr class="uppercase">
                                            <th style="width:5%"> # </th>
                                            <th> Item Code </th>
                                            <th> Finished Product </th>
                                            <th class="text-center"> Tracking </th>
                                            <th class="text-center"> Expected </th>
                                            <th class="text-center"> Already Received </th>
                                            <th class="text-center"> Remaining </th>
                                            <th style="width:180px;"> Batch / Serial No </th>
                                            <th style="width:160px;"> Expiry Date </th>
                                            <th class="text-center" style="width:150px;"> Receive Now </th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php if ($expectedProducts && count($expectedProducts) > 0) { 
                                            $idx = 0;
                                            foreach ($expectedProducts as $ep) { 
                                                $idx++;
                                                $remaining = (float)$ep['expected_qty'] - (float)$ep['received_qty'];
                                                $trackingMode = $ep['batch_tracking'] ?? 'NONE';
                                                $isTracked = in_array($trackingMode, ['BATCH', 'SERIAL'], true);
                                                $trackingLabel = $trackingMode === 'SERIAL' ? 'Serial No' : 'Batch No';
                                        ?>
                                            <tr>
                                                <td><?php echo $idx; ?></td>
                                                <td><?php echo $ep['item_code']; ?></td>
                                                <td><strong><?php echo $ep['item_name']; ?></strong></td>
                                                <td class="text-center">
                                                    <?php if ($isTracked) { ?>
                                                        <span class="label label-info"><?php echo htmlspecialchars(batchTrackingLabel($trackingMode)); ?></span>
                                                    <?php } else { ?>
                                                        <span class="text-muted">Disabled</span>
                                                    <?php } ?>
                                                </td>
                                                <td class="text-center"><?php echo $ep['expected_qty']; ?></td>
                                                <td class="text-center"><?php echo $ep['received_qty']; ?></td>
                                                <td class="text-center remaining"><?php echo number_format($remaining, 2); ?></td>
                                                <td>
                                                    <?php if ($isTracked) { ?>
                                                        <input type="text" name="batch_no[]" class="form-control" data-tracking-required="1" data-tracking-label="<?php echo htmlspecialchars($trackingLabel); ?>" placeholder="<?php echo htmlspecialchars($trackingLabel); ?>" />
                                                    <?php } else { ?>
                                                        <input type="hidden" name="batch_no[]" value="" />
                                                        <span class="text-muted">N/A</span>
                                                    <?php } ?>
                                                </td>
                                                <td>
                                                    <?php if ($isTracked) { ?>
                                                        <input type="date" name="expiry_date[]" class="form-control" value="" />
                                                    <?php } else { ?>
                                                        <input type="hidden" name="expiry_date[]" value="" />
                                                        <span class="text-muted">N/A</span>
                                                    <?php } ?>
                                                </td>
                                                <td class="text-center">
                                                    <input type="hidden" name="expected_id[]" value="<?php echo $ep['id']; ?>">
                                                    <input type="hidden" name="product_id[]" value="<?php echo $ep['product_id']; ?>">
                                                    <input type="number" name="receive_qty[]" class="form-control receive-qty-input" 
                                                           step="0.01" min="0" max="<?php echo $remaining; ?>" 
                                                           value="<?php echo number_format($remaining, 2, '.', ''); ?>" 
                                                           placeholder="0" />
                                                </td>
                                            </tr>
                                        <?php } } else { ?>
                                            <tr>
                                                <td colspan="10" class="text-center text-muted">No pending products to receive.</td>
                                            </tr>
                                        <?php } ?>
                                        </tbody>
                                    </table>
                                </div>

                                <?php if ($expectedProducts && count($expectedProducts) > 0) { ?>

                                <!-- Raw Material Lineage Picker -->
                                <?php
                                // Pre-encode issued raw items for JS / repeated use
                                $rawForLineage = [];
                                if ($issuedItems) {
                                    foreach ($issuedItems as $ri) {
                                        $rawForLineage[] = $ri;
                                    }
                                }
                                ?>
                                <?php if (!empty($rawForLineage)) { ?>
                                <div class="portlet light bordered" style="margin-top:15px; border-left:3px solid #f39c12;">
                                    <div class="portlet-title">
                                        <div class="caption">
                                            <i class="fa fa-link font-yellow-gold"></i>
                                            <span class="caption-subject font-yellow-gold sbold uppercase">Raw Material &rarr; Finished Batch Lineage</span>
                                        </div>
                                    </div>
                                    <div class="portlet-body">
                                        <div class="note note-warning" style="margin-bottom:10px;">
                                            For each finished product, tick the raw material batches that were consumed to make it.
                                            By default, all are pre-selected. The lineage will be saved when you confirm receive.
                                        </div>
                                        <?php foreach ($expectedProducts as $epIdx => $ep) { ?>
                                            <div style="border:1px solid #eee; padding:10px 12px; margin-bottom:10px; border-radius:3px; background:#fafafa;">
                                                <div style="font-weight:600; color:#357e30; margin-bottom:6px;">
                                                    <?php echo htmlspecialchars($ep['item_code'] . ' — ' . $ep['item_name']); ?>
                                                </div>
                                                <table class="table table-condensed table-bordered" style="margin-bottom:0; background:#fff;">
                                                    <thead>
                                                    <tr class="uppercase">
                                                        <th style="width:5%;"><input type="checkbox" class="lineage-toggle-all" data-target="lineage-ep-<?php echo $ep['id']; ?>" checked /></th>
                                                        <th>Raw Item</th>
                                                        <th>Batch No</th>
                                                        <th class="text-center" style="width:14%;">Issued Qty</th>
                                                        <th class="text-center" style="width:18%;">Qty Used (override)</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <?php foreach ($rawForLineage as $ri) { ?>
                                                        <tr>
                                                            <td class="text-center">
                                                                <input type="checkbox"
                                                                       name="linked_raw[<?php echo $ep['id']; ?>][]"
                                                                       value="<?php echo $ri['issue_item_id'] ?? $ri['id'] ?? ''; ?>"
                                                                       class="lineage-ep-<?php echo $ep['id']; ?>"
                                                                       checked />
                                                            </td>
                                                            <td><?php echo htmlspecialchars($ri['item_code'] . ' — ' . $ri['item_name']); ?></td>
                                                            <td><?php echo !empty($ri['raw_batch_no']) ? '<span class="label label-info">' . htmlspecialchars($ri['raw_batch_no']) . '</span>' : '<span class="text-muted">N/A</span>'; ?></td>
                                                            <td class="text-center"><?php echo number_format((float)$ri['qty'], 2); ?></td>
                                                            <td>
                                                                <input type="number" step="0.0001" min="0"
                                                                       name="linked_qty[<?php echo $ep['id']; ?>][<?php echo $ri['issue_item_id'] ?? $ri['id'] ?? ''; ?>]"
                                                                       value="<?php echo number_format((float)$ri['qty'], 4, '.', ''); ?>"
                                                                       class="form-control input-sm" />
                                                            </td>
                                                        </tr>
                                                    <?php } ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                                <?php } ?>

                                <div class="note note-info">
                                    <h4 class="block"><i class="fa fa-info-circle"></i> What happens on confirm?</h4>
                                    <p>
                                        1. A <strong>GRN (Goods Received Note)</strong> will be automatically created for the received finished products.<br/>
                                        2. Stock (FIFO) will be added to the destination location: <strong><?php echo trim(($toLocation['location_code'] ?? '') . ' - ' . ($toLocation['name'] ?? '')); ?></strong>.<br/>
                                        3. The production status will be updated accordingly.<br/>
                                        4. The <strong>raw-material &rarr; finished-batch lineage</strong> will be saved for traceability reports.
                                    </p>
                                </div>

                                <div class="form-actions right">
                                    <a href="production-receive-list.php" class="btn default">Cancel</a>
                                    <button type="submit" class="btn btn-success" id="btn_confirm">
                                        <i class="fa fa-check"></i> Confirm Receive & Create GRN
                                    </button>
                                </div>
                                <?php } ?>
                            </form>
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

<script>
$(document).ready(function() {
    // Validate at least one qty > 0 before submit
    $('#receive_form').on('submit', function(e) {
        var hasQty = false;
        var missingTrackingLabel = '';
        $('input[name="receive_qty[]"]').each(function() {
            if (parseFloat($(this).val()) > 0) {
                hasQty = true;
            }
        });
        if (!hasQty) {
            e.preventDefault();
            alert('Please enter at least one receive quantity greater than 0.');
            return false;
        }

        $('input[name="batch_no[]"][data-tracking-required="1"]').each(function() {
            var row = $(this).closest('tr');
            var qty = parseFloat(row.find('input[name="receive_qty[]"]').val()) || 0;
            if (qty > 0 && $.trim($(this).val()) === '') {
                missingTrackingLabel = $(this).data('trackingLabel') || 'Batch / Serial No';
                return false;
            }
        });

        if (missingTrackingLabel !== '') {
            e.preventDefault();
            alert('Please enter ' + missingTrackingLabel + ' for all tracked items with a receive quantity.');
            return false;
        }
    });

    // Lineage: master toggle per finished product table
    $('.lineage-toggle-all').on('change', function () {
        var target = $(this).data('target');
        $('input.' + target).prop('checked', this.checked);
    });
});
</script>
</body>
</html>
