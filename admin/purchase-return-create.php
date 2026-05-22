<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');
include('get_url.php');

date_default_timezone_set("Asia/Colombo");

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$db = new Database();
$grnId = (int) ($_GET['grn_id'] ?? 0);
$message = $_GET['message'] ?? '';
$type = $_GET['type'] ?? '';

// active currency
$cur = $db->getRow('SELECT currency FROM currency WHERE activated = ? LIMIT 1', ['Y']);
$active_currency_code = $cur['currency'] ?? '';

function generatePurchaseReturnCode()
{
    $db = new Database();
    $row = $db->getRow('SELECT MAX(pr_h_id) AS pr_h_id FROM purchase_return_header');
    $lastId = (int) ($row['pr_h_id'] ?? 0);
    $newId = $lastId + 1;
    return 'PRN' . str_pad((string) $newId, 5, '0', STR_PAD_LEFT);
}

$purchaseReturnCode = generatePurchaseReturnCode();
$today = date('Y-m-d');

$grn = null;
$supplier = null;
$location = null;
$items = [];
$returnedMap = [];

if ($grnId > 0) {
    $grn = $db->getRow('SELECT * FROM grn_hedder WHERE grn_h_id = ?', [$grnId]);

    if ($grn) {
        if (!isSuperAdmin() && (int) $grn['grn_h_location'] !== (int) ($_SESSION['location'] ?? 0)) {
            redirect('access_denied.php');
        }

        $supplier = $db->getRow('SELECT * FROM supplier WHERE supplier_id = ?', [$grn['grn_h_supplier_id']]);
        $location = $db->getRow('SELECT name, phone_no, address FROM location_master WHERE id = ?', [$grn['grn_h_location']]);
        $items = $db->getRows('SELECT gd.*, im.item_name, im.item_code FROM grn_details gd JOIN item_master im ON im.item_id = gd.grn_d_item_id WHERE gd.grn_h_id = ?', [$grnId]) ?: [];

        if (!empty($items)) {
            $ids = array_map(function($row) { return (int) $row['grn_d_id']; }, $items);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $rows = $db->getRows('SELECT grn_d_id, COALESCE(SUM(pr_d_qty),0) AS returned_qty FROM purchase_return_details WHERE grn_d_id IN (' . $placeholders . ') GROUP BY grn_d_id', $ids) ?: [];
            foreach ($rows as $r) {
                $returnedMap[(int) $r['grn_d_id']] = (float) $r['returned_qty'];
            }
        }
    } else {
        $message = 'GRN not found.';
        $type = 'error';
    }
}

if (isSuperAdmin()) {
    $grnOptions = $db->getRows('SELECT grn_h_id, grn_h_code FROM grn_hedder ORDER BY grn_h_id DESC') ?: [];
} else {
    $grnOptions = $db->getRows('SELECT grn_h_id, grn_h_code FROM grn_hedder WHERE grn_h_location = ? ORDER BY grn_h_id DESC', [$_SESSION['location'] ?? 0]) ?: [];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Purchase Return | WebStore</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
    <style>
        .table-input { height: 30px; padding: 5px; }
        .balance-qty { font-weight: bold; color: #d84a38; }
        .table > tbody > tr > td { vertical-align: middle; }
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
                    <li><a href="purchase-history.php">Purchases</a><i class="fa fa-circle"></i></li>
                    <li><span>Purchase Return</span></li>
                </ul>
            </div>

            <?php if (!empty($message)) { ?>
                <div class="alert <?php echo ($type === 'error') ? 'alert-danger' : 'alert-success'; ?> alert-dismissable">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                    <?php echo h($message); ?>
                </div>
            <?php } ?>

            <h3 class="page-title"> Purchase Return
                <small>Return items to supplier</small>
            </h3>

            <div class="portlet light bordered">
                <div class="portlet-title">
                    <div class="caption"><i class="icon-refresh font-dark"></i> <span class="caption-subject font-dark sbold uppercase">Select GRN</span></div>
                </div>
                <div class="portlet-body">
                    <form method="get" class="form-inline">
                        <div class="form-group" style="margin-right:10px;">
                            <label class="control-label">GRN</label>
                            <select name="grn_id" class="form-control">
                                <option value="">-- Select GRN --</option>
                                <?php foreach ($grnOptions as $opt) { ?>
                                    <option value="<?php echo (int) $opt['grn_h_id']; ?>" <?php echo ($grnId === (int) $opt['grn_h_id']) ? 'selected' : ''; ?>><?php echo h($opt['grn_h_code']); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Load GRN</button>
                    </form>
                </div>
            </div>

            <?php if ($grn): ?>
            <form action="process/purchase-return-create-process.php" method="post" id="purchase_return_form">
                <input type="hidden" name="grn_id" value="<?php echo (int) $grnId; ?>" />
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption"><i class="icon-docs font-dark"></i> <span class="caption-subject font-dark sbold uppercase">Return Details</span></div>
                    </div>
                    <div class="portlet-body form">
                        <div class="form-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label bold">Return No</label>
                                        <input type="text" class="form-control" name="return_no" readonly value="<?php echo h($purchaseReturnCode); ?>" />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label bold">Return Date</label>
                                        <input type="date" class="form-control" name="return_date" value="<?php echo h($today); ?>" required />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label bold">Supplier</label>
                                        <input type="text" class="form-control" readonly value="<?php echo h($supplier['supplier_name'] ?? ''); ?>" />
                                    </div>
                                </div>
                            </div>
                            <div class="row" style="margin-top:10px;">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label bold">GRN Code</label>
                                        <input type="text" class="form-control" readonly value="<?php echo h($grn['grn_h_code'] ?? ''); ?>" />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label bold">Location</label>
                                        <input type="text" class="form-control" readonly value="<?php echo h($location['name'] ?? ''); ?>" />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label bold">Currency</label>
                                        <input type="text" class="form-control" readonly value="<?php echo h($active_currency_code); ?>" />
                                    </div>
                                </div>
                            </div>
                            <div class="row" style="margin-top:10px;">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label bold">Remarks</label>
                                        <input type="text" class="form-control" name="remarks" placeholder="Optional remarks" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption"><i class="icon-basket font-blue-madison"></i> <span class="caption-subject font-blue-madison sbold uppercase">Return Items</span></div>
                    </div>
                    <div class="portlet-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped table-bordered" id="return_items_table">
                                <thead>
                                    <tr class="uppercase">
                                        <th>Product Code</th>
                                        <th>Product Name</th>
                                        <th class="text-center">Received Qty</th>
                                        <th class="text-center">Returned Qty</th>
                                        <th class="text-center">Balance</th>
                                        <th class="text-center">Return Now</th>
                                        <th class="text-right">Rate</th>
                                        <th class="text-right">GST %</th>
                                        <th class="text-right">Line Net</th>
                                        <th class="text-right">Line GST</th>
                                        <th class="text-right">Line Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                $hasReturnable = false;
                                foreach ($items as $item) {
                                    $returnedQty = $returnedMap[(int) $item['grn_d_id']] ?? 0.0;
                                    $balanceQty = max(0, (float) $item['grn_d_qty'] - $returnedQty);
                                    if ($balanceQty > 0) { $hasReturnable = true; }
                                    $rateVal = number_format((float) ($item['grn_d_rate'] ?? 0), 2, '.', '');
                                    $vatVal = number_format((float) ($item['grn_d_vat_rate'] ?? 0), 2, '.', '');
                                ?>
                                    <tr data-rate="<?php echo $rateVal; ?>" data-vatrate="<?php echo $vatVal; ?>">
                                        <td><?php echo h($item['item_code']); ?></td>
                                        <td><?php echo h($item['item_name']); ?></td>
                                        <td class="text-center"><?php echo h($item['grn_d_qty']); ?></td>
                                        <td class="text-center"><?php echo number_format($returnedQty, 2); ?></td>
                                        <td class="text-center"><span class="balance-qty"><?php echo number_format($balanceQty, 2); ?></span></td>
                                        <td class="text-center">
                                            <input type="hidden" name="grn_d_id[]" value="<?php echo (int) $item['grn_d_id']; ?>" />
                                            <input type="number" step="0.01" min="0" max="<?php echo $balanceQty; ?>" name="return_qty[]" class="form-control table-input text-center return-input" data-balance="<?php echo $balanceQty; ?>" placeholder="0" />
                                        </td>
                                        <td class="text-right"><?php echo h($active_currency_code); ?> <?php echo $rateVal; ?></td>
                                        <td class="text-right"><?php echo $vatVal; ?></td>
                                        <td class="text-right"><span class="line-net">0.00</span></td>
                                        <td class="text-right"><span class="line-vat">0.00</span></td>
                                        <td class="text-right"><span class="line-total">0.00</span></td>
                                    </tr>
                                <?php } ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="row" style="margin-top:10px;">
                            <div class="col-md-4 col-md-offset-8">
                                <table class="table table-bordered">
                                    <tr><th>Sub Total</th><td class="text-right"><?php echo h($active_currency_code); ?> <span id="pr_net_total">0.00</span></td></tr>
                                    <tr><th>Total GST</th><td class="text-right"><?php echo h($active_currency_code); ?> <span id="pr_vat_total">0.00</span></td></tr>
                                    <tr><th>Grand Total</th><td class="text-right"><strong><?php echo h($active_currency_code); ?> <span id="pr_gross_total">0.00</span></strong></td></tr>
                                </table>
                            </div>
                        </div>

                        <div class="form-actions right">
                            <a href="purchase-history.php" class="btn default">Cancel</a>
                            <?php if ($hasReturnable): ?>
                                <button type="submit" class="btn btn-danger" id="btn_save_return"><i class="fa fa-save"></i> Save Purchase Return</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>
            <?php endif; ?>

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
    function calculateTotals() {
        var netTotal = 0.0;
        var vatTotal = 0.0;
        $('#return_items_table tbody tr').each(function() {
            var row = $(this);
            var rate = parseFloat(row.data('rate') || 0);
            var vatrate = parseFloat(row.data('vatrate') || 0);
            var qty = parseFloat(row.find('.return-input').val()) || 0;
            var lineNet = qty * rate;
            var lineVat = (lineNet * vatrate) / 100.0;
            var lineTotal = lineNet + lineVat;
            row.find('.line-net').text(lineNet.toFixed(2));
            row.find('.line-vat').text(lineVat.toFixed(2));
            row.find('.line-total').text(lineTotal.toFixed(2));
            netTotal += lineNet;
            vatTotal += lineVat;
        });
        $('#pr_net_total').text(netTotal.toFixed(2));
        $('#pr_vat_total').text(vatTotal.toFixed(2));
        $('#pr_gross_total').text((netTotal + vatTotal).toFixed(2));
    }

    $(document).on('input', '.return-input', function() {
        var input = $(this);
        var val = parseFloat(input.val()) || 0;
        var max = parseFloat(input.data('balance')) || 0;
        if (val > max) {
            alert('Return quantity cannot exceed balance of ' + max);
            input.val(max);
        }
        calculateTotals();
    });

    calculateTotals();
});
</script>
</body>
</html>
