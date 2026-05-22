<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');
include('get_url.php');
require_once(__DIR__ . '/include/uom_helper.php');

date_default_timezone_set("Asia/Colombo");

$db = new Database();
ensureItemUomSchema($db);
$transferId = (int) ($_GET['id'] ?? 0);
$message = $_GET['message'] ?? '';
$type    = $_GET['type'] ?? '';

if ($transferId <= 0) {
    redirect('stock-transfer-receive-list.php?message=' . urlencode('Invalid transfer.') . '&type=error');
}

$transfer = $db->getRow('SELECT * FROM stock_transfer_header WHERE transfer_id = ?', [$transferId]);
if (!$transfer) {
    redirect('stock-transfer-receive-list.php?message=' . urlencode('Transfer not found.') . '&type=error');
}
if ($transfer['status'] !== 'PENDING') {
    redirect('stock-transfer-receive-list.php?message=' . urlencode('This transfer is not pending.') . '&type=error');
}

$sessionLocation  = (int) ($_SESSION['location'] ?? 0);
$isSuperAdminUser = function_exists('isSuperAdmin') ? isSuperAdmin() : false;
if (!$isSuperAdminUser && $sessionLocation !== (int) $transfer['to_location_id']) {
    redirect('stock-transfer-receive-list.php?message=' . urlencode('You are not authorised to receive this transfer.') . '&type=error');
}

$fromLocation = $db->getRow('SELECT location_code, name FROM location_master WHERE id = ?', [$transfer['from_location_id']]);
$toLocation   = $db->getRow('SELECT location_code, name FROM location_master WHERE id = ?', [$transfer['to_location_id']]);

$items = $db->getRows(
    'SELECT sti.*, itm.item_name, itm.item_code, itm.unit_of_measure, itm.batch_tracking,
            bm.batch_no, bm.expiry_date,
            iu.uom_name AS line_uom_name
     FROM stock_transfer_items sti
     JOIN item_master itm ON itm.item_id = sti.product_id
     LEFT JOIN batch_master bm ON bm.batch_id = sti.batch_id
     LEFT JOIN item_uom iu ON iu.uom_id = sti.uom_id
     WHERE sti.transfer_id = ?
     ORDER BY sti.transfer_item_id ASC',
    [$transferId]
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Receive Stock Transfer | WebStore</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
    <style>
        .stat-box {
            background: #f9f9f9;
            padding: 12px;
            border: 1px solid #eee;
            border-left: 3px solid #357e30;
        }
        .stat-box-title { font-size: 12px; text-transform: uppercase; color: #888; margin-bottom: 4px; }
        .stat-box-value { font-size: 17px; font-weight: 600; }
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

            <!-- Breadcrumb -->
            <div class="page-bar">
                <ul class="page-breadcrumb">
                    <li><a href="index.php">Home</a><i class="fa fa-circle"></i></li>
                    <li><a href="stock-transfer-receive-list.php">Receive Confirmation</a><i class="fa fa-circle"></i></li>
                    <li><span>Receive <?php echo htmlspecialchars($transfer['transfer_code']); ?></span></li>
                </ul>
            </div>

            <?php if (!empty($message)) { ?>
                <div class="alert <?php echo ($type === 'error') ? 'alert-danger' : 'alert-success'; ?> alert-dismissable">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php } ?>

            <h3 class="page-title">Receive Stock Transfer
                <small><?php echo htmlspecialchars($transfer['transfer_code']); ?></small>
            </h3>

            <form action="process/stock-transfer-receive-process.php" method="post" enctype="multipart/form-data" id="receive_form">
                <input type="hidden" name="transfer_id" value="<?php echo $transferId; ?>" />

                <div class="row">

                    <!-- Transfer Header -->
                    <div class="col-md-12">
                        <div class="portlet light bordered">
                            <div class="portlet-title">
                                <div class="caption">
                                    <i class="icon-docs font-dark"></i>
                                    <span class="caption-subject font-dark sbold uppercase">Transfer Details</span>
                                </div>
                            </div>
                            <div class="portlet-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="stat-box">
                                            <div class="stat-box-title">Transfer Code</div>
                                            <div class="stat-box-value"><?php echo htmlspecialchars($transfer['transfer_code']); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="stat-box">
                                            <div class="stat-box-title">Transfer Date</div>
                                            <div class="stat-box-value"><?php echo date('d M Y', strtotime($transfer['transfer_date'])); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="stat-box">
                                            <div class="stat-box-title">From Location</div>
                                            <div class="stat-box-value"><?php echo htmlspecialchars(trim(($fromLocation['location_code'] ?? '') . ' - ' . ($fromLocation['name'] ?? ''))); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="stat-box">
                                            <div class="stat-box-title">To Location</div>
                                            <div class="stat-box-value"><?php echo htmlspecialchars(trim(($toLocation['location_code'] ?? '') . ' - ' . ($toLocation['name'] ?? ''))); ?></div>
                                        </div>
                                    </div>
                                </div>
                                <?php if (!empty($transfer['remarks'])) { ?>
                                    <div class="note note-info" style="margin-top: 14px;">
                                        <h4 class="block">Remarks</h4>
                                        <p><?php echo nl2br(htmlspecialchars($transfer['remarks'])); ?></p>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>

                    <!-- Items -->
                    <div class="col-md-12">
                        <div class="portlet light bordered">
                            <div class="portlet-title">
                                <div class="caption">
                                    <i class="icon-basket font-blue-madison"></i>
                                    <span class="caption-subject font-blue-madison sbold uppercase">Items to Receive</span>
                                </div>
                            </div>
                            <div class="portlet-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover">
                                        <thead>
                                        <tr class="uppercase">
                                            <th style="width: 4%"> # </th>
                                            <th style="width: 10%"> Item Code </th>
                                            <th> Item Name </th>
                                            <th class="text-center" style="width: 8%"> UOM </th>
                                            <th class="text-center" style="width: 9%"> Transfer Qty </th>
                                            <th class="text-center" style="width: 9%"> Base Qty </th>
                                            <th class="text-center" style="width: 11%"> Receive Qty </th>
                                            <th class="text-center" style="width: 9%"> Batch No </th>
                                            <th class="text-center" style="width: 9%"> Expiry Date </th>
                                            <th class="text-right" style="width: 8%"> Rate </th>
                                            <th class="text-right" style="width: 8%"> Total </th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php if (empty($items)) { ?>
                                            <tr>
                                                <td colspan="11" class="text-center text-muted">No items found for this transfer.</td>
                                            </tr>
                                        <?php } else { ?>
                                            <?php $rowNum = 0; foreach ($items as $item) { $rowNum++;
                                                $lineUomName = !empty($item['line_uom_name']) ? $item['line_uom_name'] : ($item['unit_of_measure'] ?? '—');
                                                $qtyPerUom = (float) ($item['qty_per_uom'] ?? 0);
                                                if ($qtyPerUom <= 0) { $qtyPerUom = 1.0; }
                                                $transferQty = (float) $item['qty'];
                                                $transferQtyBase = (float) ($item['qty_base'] ?? ($transferQty * $qtyPerUom));
                                                ?>
                                                <tr>
                                                    <td><?php echo $rowNum; ?></td>
                                                    <td><?php echo htmlspecialchars($item['item_code']); ?></td>
                                                    <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                                                        <?php
                                                        $bt = $item['batch_tracking'] ?? 'NONE';
                                                        if ($bt === 'BATCH') echo ' <span class="label label-info" style="font-size:10px;">Batch</span>';
                                                        if ($bt === 'SERIAL') echo ' <span class="label label-info" style="font-size:10px;">Serial</span>';
                                                        ?>
                                                    </td>
                                                    <td class="text-center"><?php echo htmlspecialchars($lineUomName); ?></td>
                                                    <td class="text-center"><strong><?php echo number_format($transferQty, 2); ?></strong></td>
                                                    <td class="text-center">
                                                        <span><?php echo number_format($transferQtyBase, 2); ?></span>
                                                        <?php if (abs($transferQtyBase - $transferQty) > 0.000001) { ?>
                                                            <div style="font-size:10px;color:#7a8aa1;">base</div>
                                                        <?php } ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <input type="number" name="received_qty[<?php echo (int)$item['transfer_item_id']; ?>]"
                                                               class="form-control input-sm text-right received-qty-input"
                                                               min="0" step="0.01"
                                                               max="<?php echo (float)$transferQty; ?>"
                                                               data-transfer-qty="<?php echo (float)$transferQty; ?>"
                                                               data-qty-per-uom="<?php echo (float)$qtyPerUom; ?>"
                                                               value="<?php echo number_format($transferQty, 2, '.', ''); ?>"
                                                               required />
                                                        <?php if (abs($qtyPerUom - 1.0) > 0.000001) { ?>
                                                            <div class="received-base-preview" style="font-size:10px;color:#7a8aa1;margin-top:2px;">
                                                                = <span class="received-base-value"><?php echo number_format($transferQty * $qtyPerUom, 2); ?></span> base
                                                            </div>
                                                        <?php } ?>
                                                    </td>
                                                    <td class="text-center"><?php echo !empty($item['batch_no']) ? htmlspecialchars($item['batch_no']) : '<span class="text-muted">N/A</span>'; ?></td>
                                                    <td class="text-center"><?php echo !empty($item['expiry_date']) ? date('d M Y', strtotime($item['expiry_date'])) : '<span class="text-muted">—</span>'; ?></td>
                                                    <td class="text-right"><?php echo number_format((float)$item['rate'], 2); ?></td>
                                                    <td class="text-right"><?php echo number_format((float)$item['total'], 2); ?></td>
                                                </tr>
                                            <?php } ?>
                                        <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Attachments -->
                    <div class="col-md-12">
                        <div class="portlet light bordered">
                            <div class="portlet-title">
                                <div class="caption">
                                    <i class="fa fa-paperclip font-purple-soft"></i>
                                    <span class="caption-subject font-purple-soft sbold uppercase">Attachments
                                        <small style="font-weight:normal;">&mdash; optional, max 5 files</small>
                                    </span>
                                </div>
                            </div>
                            <div class="portlet-body">
                                <div class="form-group">
                                    <label>Received By <span class="text-danger">*</span></label>
                                    <input type="text" name="received_by" id="received_by"
                                           class="form-control" placeholder="Enter receiver's name"
                                           maxlength="100" required />
                                </div>
                                <div class="form-group">
                                    <label>Upload Files
                                        <span class="text-muted" style="font-weight:normal; font-size:12px;">
                                            PDF, Word, Excel, Image &mdash; max&nbsp;5 files, 10&nbsp;MB each
                                        </span>
                                    </label>
                                    <input type="file" name="transfer_attachments[]" id="transfer_attachments" multiple
                                           accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif"
                                           class="form-control" />
                                </div>
                                <div id="attachment_preview" style="display:none; margin-top:8px;">
                                    <strong>Selected files:</strong>
                                    <ul id="attachment_file_list" style="margin-top:6px; padding-left:20px; list-style:none;"></ul>
                                </div>
                            </div>
                        </div>
                    </div>

                </div><!-- /.row -->

                <div class="form-actions right">
                    <a href="stock-transfer-receive-list.php" class="btn default">Cancel</a>
                    <button type="submit" class="btn btn-success" id="btn_confirm_receive">
                        <i class="fa fa-check"></i> Confirm Receive
                    </button>
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

    // Live update of "base" preview as user edits Receive Qty
    $(document).on('input change', '.received-qty-input', function () {
        var $input = $(this);
        var qpu = parseFloat($input.data('qty-per-uom')) || 1;
        var val = parseFloat($input.val()) || 0;
        var $preview = $input.siblings('.received-base-preview').find('.received-base-value');
        if ($preview.length) {
            $preview.text((val * qpu).toFixed(2));
        }
    });

    // Attachment file picker validation
    $('#transfer_attachments').on('change', function () {
        var files = this.files;
        var $list = $('#attachment_file_list').empty();
        if (files.length === 0) { $('#attachment_preview').hide(); return; }
        if (files.length > 5) {
            alert('You can upload a maximum of 5 attachments.');
            this.value = '';
            $('#attachment_preview').hide();
            return;
        }
        var maxBytes = 10 * 1024 * 1024;
        for (var i = 0; i < files.length; i++) {
            if (files[i].size > maxBytes) {
                alert('"' + files[i].name + '" exceeds the 10 MB limit.');
                this.value = '';
                $list.empty();
                $('#attachment_preview').hide();
                return;
            }
            var sizeMb = (files[i].size / 1048576).toFixed(2);
            var ext = files[i].name.split('.').pop().toLowerCase();
            var icon = 'fa-file-o';
            if (ext === 'pdf')                                  icon = 'fa-file-pdf-o';
            else if (ext === 'doc' || ext === 'docx')           icon = 'fa-file-word-o';
            else if (ext === 'xls' || ext === 'xlsx')           icon = 'fa-file-excel-o';
            else if (['jpg','jpeg','png','gif'].indexOf(ext) !== -1) icon = 'fa-file-image-o';
            $list.append(
                '<li style="margin-bottom:4px;"><i class="fa ' + icon + ' text-muted"></i>&nbsp;' +
                $('<span>').text(files[i].name).html() +
                ' <span class="text-muted">(' + sizeMb + ' MB)</span></li>'
            );
        }
        $('#attachment_preview').show();
    });

    // Confirm before submitting
    $('#receive_form').on('submit', function (e) {
        // Validate received quantities
        var hasError = false;
        $('.received-qty-input').each(function () {
            var max = parseFloat($(this).data('transfer-qty')) || 0;
            var val = parseFloat($(this).val());
            if (isNaN(val) || val < 0) { hasError = true; return false; }
            if (val > max + 0.000001) { hasError = true; return false; }
        });
        if (hasError) {
            alert('Receive quantity must be between 0 and the transferred quantity for each item.');
            e.preventDefault();
            return false;
        }
        return confirm('Confirm receiving this stock transfer? This action cannot be undone.');
    });

});
</script>
</body>
</html>
