<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');
include('get_url.php');

$db = new Database();
$purchaseNoteId = (int) ($_GET['id'] ?? 0);
$message = $_GET['message'] ?? '';
$type = $_GET['type'] ?? '';
$autoPrint = isset($_GET['autoprint']) && $_GET['autoprint'] === '1';

if ($purchaseNoteId <= 0) {
    redirect('purchase-order-list.php?message=' . urlencode('Invalid purchase note.') . '&type=error');
}

$note = $db->getRow('SELECT * FROM purchase_note_header WHERE purchase_note_id = ?', [$purchaseNoteId]);
if (!$note) {
    redirect('purchase-order-list.php?message=' . urlencode('Purchase note not found.') . '&type=error');
}

// active currency code
function get_active_currency_code_view() {
    $db = new Database();
    $row = $db->getRow('SELECT currency FROM currency WHERE activated = ? LIMIT 1', ["Y"]);
    return $row['currency'] ?? '';
}
$active_currency_code = get_active_currency_code_view();

$supplier = $db->getRow('SELECT * FROM supplier WHERE supplier_id = ?', [$note['supplier_id']]);
$location = $db->getRow('SELECT name, phone_no, address FROM location_master WHERE id = ?', [$note['location_id']]);
$items = $db->getRows('SELECT pni.*, itm.item_name, itm.item_code, itm.item_purchase_price, itm.item_vat, uom.uom_name AS line_uom_name FROM purchase_note_items pni JOIN item_master itm ON itm.item_id = pni.product_id LEFT JOIN item_uom uom ON uom.uom_id = pni.uom_id WHERE pni.purchase_note_id = ?', [$purchaseNoteId]);

// Compute order totals (net, vat, gross) and balance totals (remaining amounts)
$order_net = 0.0;
$order_vat = 0.0;
$balance_net = 0.0;
$balance_vat = 0.0;
foreach ($items as $it) {
    $rate = isset($it['unit_price']) && $it['unit_price'] !== null
        ? (float) $it['unit_price']
        : (float) ($it['item_purchase_price'] ?? 0);
    $vatRate = isset($it['vat_rate']) && $it['vat_rate'] !== null
        ? (float) $it['vat_rate']
        : (float) ($it['item_vat'] ?? 0);
    $qtyRequested = (float) ($it['requested_qty'] ?? 0);
    $qtyReceived = (float) ($it['total_received_qty'] ?? 0);
    $balanceQty = max(0, $qtyRequested - $qtyReceived);

    $qpu = (float) ($it['qty_per_uom'] ?? 0);
    if ($qpu <= 0) { $qpu = 1.0; }
    $baseRequested = isset($it['requested_qty_base']) && $it['requested_qty_base'] !== null
        ? (float) $it['requested_qty_base']
        : ($qtyRequested * $qpu);
    $baseReceived = isset($it['total_received_qty_base']) && $it['total_received_qty_base'] !== null
        ? (float) $it['total_received_qty_base']
        : ($qtyReceived * $qpu);
    $baseBalance = isset($it['balance_qty_base']) && $it['balance_qty_base'] !== null
        ? (float) $it['balance_qty_base']
        : max(0, $baseRequested - $baseReceived);

    // unit_price is per base UOM
    $lineNet = (float) ($baseRequested * $rate);
    $lineVat = ($lineNet * $vatRate) / 100.0;
    $order_net += $lineNet;
    $order_vat += $lineVat;

    $balLineNet = (float) ($baseBalance * $rate);
    $balLineVat = ($balLineNet * $vatRate) / 100.0;
    $balance_net += $balLineNet;
    $balance_vat += $balLineVat;
}
$order_gross = $order_net + $order_vat;
$balance_gross = $balance_net + $balance_vat;

$grns = $db->getRows('SELECT grn_h_id, grn_h_code, grn_h_date, grn_h_net_value, grn_h_vat_value, grn_h_gross_value FROM grn_hedder WHERE purchase_note_id = ? ORDER BY grn_h_id DESC', [$purchaseNoteId]);

// Status Badge Logic
$statusLabel = 'default';
if ($note['status'] === 'OPEN') $statusLabel = 'info';
if ($note['status'] === 'PARTIALLY_RECEIVED') $statusLabel = 'warning';
if ($note['status'] === 'COMPLETED') $statusLabel = 'success';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Purchase Note View | WebStore</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <meta content="" name="description" />
    <meta content="" name="author" />
    <?php include('common/head.php'); ?>
    <style>
        .invoice-content-2 {
            padding: 20px;
            background: #fff;
        }
        .invoice-head {
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .invoice-logo {
            font-size: 24px;
            font-weight: 700;
            color: #333;
        }
        .invoice-desc {
            font-size: 14px;
            color: #777;
        }
        .stat-box {
            background: #f9f9f9;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #eee;
            border-left: 3px solid #666;
        }
        .stat-box.confirmed { border-left-color: #36c6d3; }
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
        .progress-xs {
            height: 8px;
            margin-bottom: 0;
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
                        <a href="purchase-order-list.php">Purchases</a>
                        <i class="fa fa-circle"></i>
                    </li>
                    <li>
                        <span>View Order</span>
                    </li>
                </ul>
                <div class="page-toolbar">
                    <div class="btn-group pull-right">
                        <a href="purchase-order-list.php" class="btn btn-fit-height white btn-outline dropdown-toggle">
                            <i class="fa fa-arrow-left"></i> Back to List
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

            <h3 class="page-title"> Purchase Order Details
                <small><?php echo $note['purchase_note_code']; ?></small>
            </h3>

            <div class="row">
                <div class="col-md-12">
                    <!-- Main Portlet -->
                    <div class="portlet light bordered">
                        <div class="portlet-title">
                            <div class="caption">
                                <i class="icon-docs font-dark"></i>
                                <span class="caption-subject font-dark sbold uppercase">Order #<?php echo $note['purchase_note_code']; ?></span>
                                <span class="label label-<?php echo $statusLabel; ?> sbold"><?php echo str_replace('_', ' ', $note['status']); ?></span>
                            </div>
                            <div class="actions">
                                <?php if ($note['status'] !== 'COMPLETED') { ?>
                                    <a href="grn-create.php?purchase_note_id=<?php echo $purchaseNoteId; ?>" class="btn btn-circle btn-default">
                                        <i class="fa fa-truck"></i> Receive Goods (GRN)
                                    </a>
                                <?php } ?>
                                <a href="purchase-order-print.php?id=<?php echo $purchaseNoteId; ?>" target="_blank" class="btn btn-circle btn-default">
                                    <i class="fa fa-print"></i> Print
                                </a>
                            </div>
                        </div>
                        <div class="portlet-body">
                            
                            <!-- Header Info Boxes -->
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="stat-box">
                                        <div class="stat-box-title">Supplier Details</div>
                                        <div class="stat-box-value"><?php echo $supplier['supplier_name']; ?></div>
                                        <div><i class="fa fa-phone"></i> <?php echo $supplier['supplier_contact_no']; ?></div>
                                        <div><i class="fa fa-envelope"></i> <?php echo $supplier['supplier_email']; ?></div>
                                        <div><?php echo $supplier['supplier_address']; ?></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="stat-box confirmed">
                                        <div class="stat-box-title">Order Info</div>
                                        <div class="stat-box-value"><?php echo $note['purchase_note_code']; ?></div>
                                        <div>Date: <strong><?php echo date('d M Y', strtotime($note['purchase_date'])); ?></strong></div>
                                        <div>Location: <strong><?php echo $location['name']; ?></strong></div>
                                        <div>Created By: <strong>Admin</strong></div>
                                        <div style="margin-top:8px;">Currency: <strong><?php echo htmlspecialchars($active_currency_code); ?></strong></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="stat-box">
                                        <div class="stat-box-title">Additional Info</div>
                                        <?php if(!empty($note['remarks'])) { ?>
                                            <div style="font-style: italic; color: #555;">
                                                "<?php echo nl2br(htmlspecialchars($note['remarks'])); ?>"
                                            </div>
                                        <?php } else { ?>
                                            <div class="text-muted">No remarks.</div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Items Table -->
                            <div class="row">
                                <div class="col-md-12">
                                    <h4><i class="icon-basket"></i> Ordered Items</h4>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered table-hover">
                                            <thead>
                                            <tr class="uppercase">
                                                <th> Product Code </th>
                                                <th> Product Name </th>
                                                <th class="text-center"> UOM </th>
                                                <th class="text-center"> Ordered </th>
                                                <th class="text-center"> Received </th>
                                                <th class="text-center"> Balance </th>                                                <th class="text-right"> Rate </th>
                                                <th class="text-right"> Balance Amount </th>                                                <th style="width: 20%;"> Progress </th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php foreach ($items as $item) { 
                                                $percent = 0;
                                                if($item['requested_qty'] > 0) {
                                                    $percent = ($item['total_received_qty'] / $item['requested_qty']) * 100;
                                                }
                                                $lineRate = isset($item['unit_price']) && $item['unit_price'] !== null
                                                    ? (float) $item['unit_price']
                                                    : (float) ($item['item_purchase_price'] ?? 0);
                                                $itQpu = (float) ($item['qty_per_uom'] ?? 0);
                                                if ($itQpu <= 0) { $itQpu = 1.0; }
                                                $itBalanceBase = isset($item['balance_qty_base']) && $item['balance_qty_base'] !== null
                                                    ? (float) $item['balance_qty_base']
                                                    : ((float) $item['balance_qty']) * $itQpu;
                                                $progressColor = 'info';
                                                if($percent >= 100) $progressColor = 'success';
                                                else if($percent > 0) $progressColor = 'warning';
                                            ?>
                                                <tr>
                                                    <td><?php echo $item['item_code']; ?></td>
                                                    <td><?php echo $item['item_name']; ?></td>
                                                    <td class="text-center"><?php echo htmlspecialchars($item['line_uom_name'] ?? $item['unit_of_measure'] ?? '—'); ?></td>
                                                    <td class="text-center bold"><?php echo $item['requested_qty']; ?></td>
                                                    <td class="text-center"><?php echo $item['total_received_qty']; ?></td>
                                                    <td class="text-center"><?php echo $item['balance_qty']; ?></td>
                                                    <td class="text-right"><?php echo htmlspecialchars($active_currency_code); ?> <?php echo number_format($lineRate, 2); ?></td>
                                                    <td class="text-right"><?php $balAmt = $itBalanceBase * $lineRate; echo htmlspecialchars($active_currency_code) . ' ' . number_format($balAmt,2); ?></td>
                                                    <td style="vertical-align: middle;">
                                                        <div class="progress progress-xs">
                                                            <div class="progress-bar progress-bar-<?php echo $progressColor; ?>" role="progressbar" aria-valuenow="<?php echo $percent; ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $percent; ?>%">
                                                                <span class="sr-only"> <?php echo number_format($percent); ?>% Complete </span>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Order & Balance Totals -->
                            <div class="row" style="margin-top:10px;">
                                <div class="col-md-4 col-md-offset-8">
                                    <table class="table table-bordered">
                                        <tr><th>Order Sub Total</th><td class="text-right"><?php echo htmlspecialchars($active_currency_code); ?> <?php echo number_format($order_net,2); ?></td></tr>
                                        <tr><th>Total GST</th><td class="text-right"><?php echo htmlspecialchars($active_currency_code); ?> <?php echo number_format($order_vat,2); ?></td></tr>
                                        <tr><th>Grand Total</th><td class="text-right"><strong><?php echo htmlspecialchars($active_currency_code); ?> <?php echo number_format($order_gross,2); ?></strong></td></tr>
                                        <tr><th style="border-top:2px solid #eee;">Balance Sub Total</th><td class="text-right" style="border-top:2px solid #eee;"><?php echo htmlspecialchars($active_currency_code); ?> <?php echo number_format($balance_net,2); ?></td></tr>
                                        <tr><th>Total Balance GST</th><td class="text-right"><?php echo htmlspecialchars($active_currency_code); ?> <?php echo number_format($balance_vat,2); ?></td></tr>
                                        <tr><th>Balance Due</th><td class="text-right"><strong><?php echo htmlspecialchars($active_currency_code); ?> <?php echo number_format($balance_gross,2); ?></strong></td></tr>
                                    </table>
                                </div>
                            </div>

                            <hr>

                            <!-- Related Documents -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="portlet box blue-hoki">
                                        <div class="portlet-title">
                                            <div class="caption">
                                                <i class="fa fa-share-alt"></i> Related GRNs (Invoices)
                                            </div>
                                        </div>
                                        <div class="portlet-body">
                                            <?php if (count($grns) === 0) { ?>
                                                <p class="text-muted">No Goods Received Notes (GRN) linked to this order yet.</p>
                                            <?php } else { ?>
                                                <div class="table-responsive">
                                                    <table class="table table-hover">
                                                        <thead>
                                                            <tr>
                                                                <th>GRN Code</th>
                                                                <th>Received Date</th>
                                                                <th class="text-right">Net</th>
                                                                <th class="text-right">VAT</th>
                                                                <th class="text-right">Gross</th>
                                                                <th>Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($grns as $grn) { ?>
                                                                <tr>
                                                                    <td><a href="purchase_note.php?id=<?php echo $grn['grn_h_id']; ?>"><strong><?php echo $grn['grn_h_code']; ?></strong></a></td>
                                                                    <td><?php echo date('d M Y', strtotime($grn['grn_h_date'])); ?></td>
                                                                    <td class="text-right"><?php include('currency.php'); ?> <?php echo number_format($grn['grn_h_net_value'],2); ?></td>
                                                                    <td class="text-right"><?php include('currency.php'); ?> <?php echo number_format($grn['grn_h_vat_value'],2); ?></td>
                                                                    <td class="text-right"><?php include('currency.php'); ?> <?php echo number_format($grn['grn_h_gross_value'],2); ?></td>
                                                                    <td>
                                                                        <a href="purchase_note.php?id=<?php echo $grn['grn_h_id']; ?>" class="btn btn-xs btn-default">
                                                                            View Details <i class="fa fa-arrow-right"></i>
                                                                        </a>
                                                                    </td>
                                                                </tr>
                                                            <?php } ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <!-- Potentially other related info like Payments could go here -->
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include('common/footer.php'); ?>

<!-- Bootstrap Scripts -->
<script src="assets/global/plugins/jquery.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/js.cookie.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/bootstrap-hover-dropdown/bootstrap-hover-dropdown.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/jquery-slimscroll/jquery.slimscroll.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/jquery.blockui.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/uniform/jquery.uniform.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/bootstrap-switch/js/bootstrap-switch.min.js" type="text/javascript"></script>
<!-- App Scripts -->
<script src="assets/global/scripts/app.min.js" type="text/javascript"></script>
<script src="assets/layouts/layout/scripts/layout.min.js" type="text/javascript"></script>
<?php if ($autoPrint) { ?>
<script>
$(function() {
    setTimeout(function() {
        window.print();
    }, 300);
});
</script>
<?php } ?>

</body>
</html>
