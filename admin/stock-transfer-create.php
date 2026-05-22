<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');
include('get_url.php');

date_default_timezone_set("Asia/Colombo");

function load_locations($selectedId = null, $onlyLocationId = null)
{
    $db = new Database();
    if ($onlyLocationId) {
        $rows = $db->getRows('SELECT * FROM location_master WHERE id = ?', [$onlyLocationId]);
    } else {
        $rows = $db->getRows('SELECT * FROM location_master ORDER BY name ASC');
    }
    $output = '<option value="">Select Location</option>';
    foreach ($rows as $row) {
        $selected = ($selectedId !== null && (int) $selectedId === (int) $row['id']) ? ' selected' : '';
        $label = trim($row['location_code'] . ' - ' . $row['name']);
        $output .= '<option value="' . $row['id'] . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
    }
    return $output;
}

function load_products()
{
    $db = new Database();
    $rows = $db->getRows('SELECT item_id, item_code, item_name, batch_tracking, unit_of_measure FROM item_master ORDER BY item_name ASC');
    $output = '<option value="">Select Product...</option>';
    foreach ($rows as $row) {
        $label = trim($row['item_code'] . ' - ' . $row['item_name']);
        $uom = htmlspecialchars($row['unit_of_measure'] ?? '');
        $output .= '<option value="' . $row['item_id'] . '" data-batch-tracking="' . htmlspecialchars($row['batch_tracking'] ?? 'NONE') . '" data-uom="' . $uom . '">' . htmlspecialchars($label) . '</option>';
    }
    return $output;
}

function generateTransferCode()
{
    $db = new Database();
    $row = $db->getRow('SELECT MAX(transfer_id) AS transfer_id FROM stock_transfer_header');
    $lastId = (int) ($row['transfer_id'] ?? 0);
    $newId = $lastId + 1;
    $randomNo = rand(100000, 999999);
    return 'ST' . $randomNo . $newId;
}

$transferCode = generateTransferCode();
$today = date('Y-m-d');
$message = $_GET['message'] ?? '';
$type = $_GET['type'] ?? '';
$defaultLocation = (int) ($_SESSION['location'] ?? 1);
$isAdmin = isSuperAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Create Stock Transfer | WebStore</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <meta content="" name="description" />
    <meta content="" name="author" />
    <?php include('common/head.php'); ?>

    <link href="assets/global/plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/global/plugins/select2/css/select2-bootstrap.min.css" rel="stylesheet" type="text/css" />

    <style>
        .select2-container--bootstrap .select2-selection--single { height: 34px; }
        .stat-box {
            background: #f9f9f9;
            padding: 12px;
            border: 1px solid #eee;
            border-left: 3px solid #357e30;
        }
        .stat-box-title { font-size: 12px; text-transform: uppercase; color: #888; margin-bottom: 4px; }
        .stat-box-value { font-size: 18px; font-weight: 600; }
        .table-hover > tbody > tr:hover { background-color: #f5f5f5; }
        .available-badge { font-size: 12px; padding: 4px 8px; background: #e8f4fd; border: 1px solid #cfe6fb; color: #357e30; border-radius: 12px; }
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
                        <span>Create Transfer</span>
                    </li>
                </ul>
            </div>

            <?php if (!empty($message)) { ?>
                <div class="alert <?php echo ($type === 'error') ? 'alert-danger' : 'alert-success'; ?> alert-dismissable">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php } ?>

            <h3 class="page-title"> Stock Transfer
                <small>move stock between locations</small>
            </h3>

            <form action="process/stock-transfer-create-process.php" method="post" id="stock_transfer_form">
                <input type="hidden" name="from_location_id" id="from_location_id" value="<?php echo $defaultLocation; ?>" />
                <input type="hidden" name="to_location_id" id="to_location_id" value="" />

                <div class="row">
                    <div class="col-md-12">
                        <div class="portlet light bordered">
                            <div class="portlet-title">
                                <div class="caption">
                                    <i class="icon-settings font-dark"></i>
                                    <span class="caption-subject font-dark sbold uppercase">Transfer Details</span>
                                </div>
                            </div>
                            <div class="portlet-body form">
                                <div class="form-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <label class="control-label bold">Transfer Code</label>
                                                        <input type="text" name="transfer_code" class="form-control" readonly value="<?php echo $transferCode; ?>" />
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label bold">Transfer Date</label>
                                                        <input type="date" name="transfer_date" class="form-control" required value="<?php echo $today; ?>" />
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label">Remarks</label>
                                                        <input type="text" name="remarks" class="form-control" placeholder="Optional notes about this transfer" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                       
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label bold">From Location</label>
                                                <div class="row">
                                                    <div class="col-xs-9">
                                                        <select id="from_location_select" class="form-control select2" required <?php echo $isAdmin ? '' : 'disabled'; ?> >
                                                            <?php echo load_locations($defaultLocation, $isAdmin ? null : $defaultLocation); ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-xs-3">
                                                        <button type="button" id="btn_swap_locations" class="btn btn-default btn-block" title="Swap From / To" <?php echo $isAdmin ? '' : 'disabled'; ?>>
                                                            <i class="fa fa-exchange"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <?php if (!$isAdmin) { ?>
                                                    <span class="help-block">Staff can transfer stock only from their assigned location.</span>
                                                <?php } ?>
                                            </div>

                                            <div class="form-group">
                                                <label class="control-label bold">To Location</label>
                                                <select id="to_location_select" class="form-control select2" required>
                                                    <?php echo load_locations(); ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                   
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="portlet light bordered">
                            <div class="portlet-title">
                                <div class="caption">
                                    <i class="icon-basket font-blue-madison"></i>
                                    <span class="caption-subject font-blue-madison sbold uppercase">Add Items</span>
                                </div>
                            </div>
                            <div class="portlet-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label>Product</label>
                                        <select id="product_selector" class="form-control select2">
                                            <?php echo load_products(); ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label>Quantity</label>
                                        <div class="input-group">
                                            <input type="number" id="product_qty" class="form-control" step="0.01" min="0" placeholder="Qty" />
                                            <span class="input-group-addon" id="product_uom_display" style="display:none; min-width:48px;">—</span>
                                        </div>
                                        <div style="display:none; font-size:11px;color:#7a8aa1;margin-top:4px;" id="product_base_qty_preview">&nbsp;</div>
                                    </div>
                                    <div class="col-md-2">
                                        <label>Transfer UOM</label>
                                        <select id="product_uom_select" class="form-control" disabled style="width:100%;">
                                            <option value="">UOM</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3" id="batch_selector_container" style="display:none;">
                                        <label>Batch <span class="label label-info" style="font-size:10px;">Batch Tracked</span></label>
                                        <select id="batch_selector" class="form-control select2" style="width:100%;">
                                            <option value="">Select Batch...</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label>Available</label>
                                        <div class="stat-box">
                                            <div class="stat-box-title">Current Stock <small style="color:#7a8aa1;">(base UOM)</small></div>
                                            <div class="stat-box-value"><span id="available_qty" class="available-badge">0</span></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row" style="margin-top: 15px;">
                                    <div class="col-md-12 text-right">
                                        <button type="button" class="btn" style="background-color: #357e30; color: white; border-color: #2c6626;" id="btn_add_item">
                                            <i class="fa fa-plus"></i> Add Item
                                        </button>
                                    </div>
                                </div>
                                <hr />
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover" id="items_table">
                                        <thead>
                                        <tr class="uppercase">
                                            <th style="width: 5%"> # </th>
                                            <th> Product </th>
                                            <th style="width: 12%" class="text-center"> Batch </th>
                                            <th style="width: 9%" class="text-center"> Qty </th>
                                            <th style="width: 9%" class="text-center"> UOM </th>
                                            <th style="width: 9%" class="text-center"> Base Qty </th>
                                            <th style="width: 11%" class="text-center"> Available <small>(base)</small> </th>
                                            <th style="width: 9%" class="text-center"> Action </th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr id="empty_row">
                                            <td colspan="8" class="text-center text-muted">No items added yet.</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="form-actions right">
                                    <a href="stock-transfer-list.php" class="btn default">Cancel</a>
                                    <button type="submit" class="btn" style="background-color: #357e30; color: white; border-color: #2c6626;" id="btn_submit" disabled>
                                        <i class="fa fa-save"></i> Save Transfer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
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
<script src="assets/global/plugins/select2/js/select2.full.min.js" type="text/javascript"></script>
<script src="assets/global/scripts/app.min.js" type="text/javascript"></script>
<script src="assets/layouts/layout/scripts/layout.min.js" type="text/javascript"></script>

<script>
$(document).ready(function() {
    $('.select2').select2({
        placeholder: "Select option",
        allowClear: true,
        theme: "bootstrap"
    });

    var rowCount = 0;

    function updateHiddenLocations() {
        $('#from_location_id').val($('#from_location_select').val());
        $('#to_location_id').val($('#to_location_select').val());
    }

    function updateSwapButtonState() {
        var disabled = $('#from_location_select').is(':disabled');
        $('#btn_swap_locations').prop('disabled', disabled);
    }

    $('#btn_swap_locations').on('click', function() {
        if ($('#from_location_select').is(':disabled')) {
            alert('Cannot swap locations — From location is fixed for your account.');
            return;
        }
        var from = $('#from_location_select').val();
        var to = $('#to_location_select').val();
        $('#from_location_select').val(to).trigger('change');
        $('#to_location_select').val(from).trigger('change');
        updateHiddenLocations();
        fetchAvailableStock();
        updateSwapButtonState();
    });

    function getSelectedBatchTracking() {
        var selected = $('#product_selector option:selected');
        return selected.data('batch-tracking') || 'NONE';
    }

    function fetchAvailableStock() {
        var productId = $('#product_selector').val();
        var fromLocation = $('#from_location_select').val();
        if (!productId || !fromLocation) {
            $('#available_qty').text('0');
            $('#batch_selector_container').hide();
            return;
        }

        var tracking = getSelectedBatchTracking();

        if (tracking === 'BATCH' || tracking === 'SERIAL') {
            // Fetch batches
            $.ajax({
                type: 'POST',
                url: 'process/get-product-batches.php',
                data: { product_id: productId, location_id: fromLocation },
                dataType: 'json',
                success: function(res) {
                    var batches = (res && res.batches) ? res.batches : [];
                    var totalQty = 0;
                    var batchOptions = '<option value="">Select Batch...</option>';
                    for (var i = 0; i < batches.length; i++) {
                        var b = batches[i];
                        totalQty += b.available_qty;
                        var expiryText = b.expiry_date ? ' | Exp: ' + b.expiry_date : '';
                        batchOptions += '<option value="' + b.batch_id + '" data-batch-no="' + b.batch_no + '" data-expiry="' + (b.expiry_date || '') + '" data-qty="' + b.available_qty + '">' + b.batch_no + ' (Qty: ' + b.available_qty + expiryText + ')</option>';
                    }
                    $('#batch_selector').html(batchOptions);
                    $('#batch_selector_container').show();
                    $('#available_qty').text(totalQty);
                    $('#product_qty').attr('max', totalQty);
                }
            });
        } else {
            $('#batch_selector_container').hide();
            $.ajax({
                type: 'POST',
                url: 'process/stock-transfer-get-qty.php',
                data: { product_id: productId, location_id: fromLocation },
                dataType: 'json',
                success: function(res) {
                    var qty = (res && res.qty) ? res.qty : 0;
                    $('#available_qty').text(qty);
                    $('#product_qty').attr('max', qty);
                }
            });
        }
    }

    // Update available qty when batch is selected
    $('#batch_selector').on('change', function() {
        var selected = $(this).find('option:selected');
        var batchQty = parseFloat(selected.data('qty') || 0);
        if (batchQty > 0) {
            $('#available_qty').text(batchQty);
            $('#product_qty').attr('max', batchQty);
        }
    });

    $('#from_location_select, #to_location_select').on('change', function() {
        updateHiddenLocations();
        fetchAvailableStock();
    });

    $('#product_selector').on('change', function() {
        var uom = $(this).find('option:selected').data('uom') || '—';
        $('#product_uom_display').text(uom);
        loadProductUoms($(this).val());
        fetchAvailableStock();
    });

    var currentProductUoms = [];
    function loadProductUoms(productId) {
        var $sel = $('#product_uom_select');
        currentProductUoms = [];
        if (!productId) {
            $sel.html('<option value="">UOM</option>').prop('disabled', true);
            $('#product_base_qty_preview').html('&nbsp;');
            return;
        }
        $sel.html('<option value="">Loading...</option>').prop('disabled', true);
        $.getJSON('process/get-item-uoms.php', { item_id: productId }, function(resp) {
            if (!resp || !resp.ok) {
                $sel.html('<option value="">UOM</option>').prop('disabled', true);
                return;
            }
            currentProductUoms = resp.uoms || [];
            var html = '';
            var defaultId = null;
            currentProductUoms.forEach(function(u) {
                html += '<option value="' + u.uom_id + '" data-qpu="' + u.qty_per_uom + '" data-name="' + (u.uom_name || '') + '">' + (u.uom_name || ('#' + u.uom_id)) + (u.is_base ? ' (base)' : '') + (u.is_default_purchase ? ' ★' : '') + '</option>';
                if (u.is_base && defaultId === null) { defaultId = u.uom_id; }
            });
            $sel.html(html).prop('disabled', currentProductUoms.length === 0);
            if (defaultId !== null) { $sel.val(defaultId); }
            updateBaseQtyPreview();
        }).fail(function() {
            $sel.html('<option value="">UOM</option>').prop('disabled', true);
        });
    }

    function getSelectedUomMeta() {
        var $opt = $('#product_uom_select option:selected');
        if (!$opt.length || !$opt.val()) { return null; }
        return { uom_id: parseInt($opt.val(), 10), qty_per_uom: parseFloat($opt.data('qpu')) || 1, uom_name: $opt.data('name') || '' };
    }

    function updateBaseQtyPreview() {
        var meta = getSelectedUomMeta();
        var qty = parseFloat($('#product_qty').val()) || 0;
        if (!meta || !qty) { $('#product_base_qty_preview').html('&nbsp;'); return; }
        var baseQty = qty * meta.qty_per_uom;
        var baseName = '';
        currentProductUoms.forEach(function(u) { if (u.is_base) { baseName = u.uom_name; } });
        if (Math.abs(baseQty - qty) > 0.000001) {
            $('#product_base_qty_preview').text('= ' + baseQty.toFixed(2) + ' ' + baseName + ' (base)');
        } else {
            $('#product_base_qty_preview').html('&nbsp;');
        }
    }

    $('#product_qty, #product_uom_select').on('input change', updateBaseQtyPreview);

    $('#btn_add_item').on('click', function() {
        var fromLocation = $('#from_location_select').val();
        var toLocation = $('#to_location_select').val();
        var productId = $('#product_selector').val();
        var productName = $('#product_selector option:selected').text();
        var qty = parseFloat($('#product_qty').val());
        var available = parseFloat($('#available_qty').text());
        var tracking = getSelectedBatchTracking();
        var batchId = '';
        var batchNo = '';
        var batchDisplay = 'N/A';

        var uomMeta = getSelectedUomMeta();
        var qtyPerUom = uomMeta ? uomMeta.qty_per_uom : 1;
        var uomId = uomMeta ? uomMeta.uom_id : '';
        var uomName = uomMeta ? uomMeta.uom_name : ($('#product_selector option:selected').data('uom') || '—');
        var baseQty = (qty || 0) * qtyPerUom;

        if (!fromLocation || !toLocation) {
            alert('Please select both From and To locations.');
            return;
        }
        if (fromLocation === toLocation) {
            alert('From and To locations cannot be the same.');
            return;
        }
        if (!productId) {
            alert('Please select a product.');
            return;
        }
        if (!qty || qty <= 0) {
            alert('Please enter a valid quantity.');
            return;
        }

        if (tracking === 'BATCH' || tracking === 'SERIAL') {
            batchId = $('#batch_selector').val();
            if (!batchId) {
                alert('Please select a batch for this batch-tracked product.');
                return;
            }
            var selectedBatchOpt = $('#batch_selector option:selected');
            batchNo = selectedBatchOpt.data('batch-no');
            var batchAvail = parseFloat(selectedBatchOpt.data('qty') || 0);
            if (baseQty > batchAvail + 0.000001) {
                alert('Quantity (' + baseQty.toFixed(2) + ' base) exceeds available batch stock (' + batchAvail + ').');
                return;
            }
            batchDisplay = batchNo;
            available = batchAvail;

            // Check duplicate: same product + same batch
            var existingRow = $('input[name="product_id[]"][value="' + productId + '"]').closest('tr').find('input[name="batch_id[]"][value="' + batchId + '"]');
            if (existingRow.length > 0) {
                alert('This product with the same batch is already in the list.');
                return;
            }
        } else {
            if (!isNaN(available) && baseQty > available + 0.000001) {
                alert('Quantity (' + baseQty.toFixed(2) + ' base) exceeds available stock (' + available + ').');
                return;
            }
            var existingRow = $('input[name="product_id[]"][value="' + productId + '"]');
            if (existingRow.length > 0 && tracking === 'NONE') {
                alert('This product is already in the list. Remove it to change quantity.');
                return;
            }
        }

        $('#empty_row').remove();
        rowCount++;

        var newRow = '\
            <tr id="row_' + rowCount + '">\
                <td>' + rowCount + '</td>\
                <td><strong>' + productName + '</strong>\
                    <input type="hidden" name="product_id[]" value="' + productId + '">\
                    <input type="hidden" name="batch_id[]" value="' + batchId + '">\
                    <input type="hidden" name="line_uom_id[]" value="' + (uomId || '') + '">\
                    <input type="hidden" name="line_qty_per_uom[]" value="' + qtyPerUom + '">\
                    <input type="hidden" name="line_base_qty[]" value="' + baseQty.toFixed(4) + '">\
                </td>\
                <td class="text-center">' + batchDisplay + '</td>\
                <td class="text-center">' + qty + '<input type="hidden" name="transfer_qty[]" value="' + qty + '"></td>\
                <td class="text-center">' + uomName + '</td>\
                <td class="text-center">' + baseQty.toFixed(2) + '</td>\
                <td class="text-center">' + available + '</td>\
                <td class="text-center">\
                    <button type="button" class="btn btn-xs btn-danger remove-row" data-row-id="row_' + rowCount + '"><i class="fa fa-trash"></i></button>\
                </td>\
            </tr>';

        $('#items_table tbody').append(newRow);
        $('#btn_submit').prop('disabled', false);

        $('#product_selector').val('').trigger('change');
        $('#product_qty').val('');
        $('#product_uom_display').text('—');
        $('#available_qty').text('0');
        $('#batch_selector_container').hide();
        $('#product_uom_select').html('<option value="">UOM</option>').prop('disabled', true);
        $('#product_base_qty_preview').html('&nbsp;');
        currentProductUoms = [];

        $('#from_location_select').prop('disabled', true);
        $('#to_location_select').prop('disabled', true);
        $('#btn_swap_locations').prop('disabled', true);
        updateHiddenLocations();
    });

    $(document).on('click', '.remove-row', function() {
        var rowId = $(this).data('row-id');
        $('#' + rowId).remove();

        if ($('#items_table tbody tr').length === 0) {
            $('#items_table tbody').append('<tr id="empty_row"><td colspan="8" class="text-center text-muted">No items added yet.</td></tr>');
            $('#btn_submit').prop('disabled', true);
            rowCount = 0;
            $('#from_location_select').prop('disabled', false);
            $('#to_location_select').prop('disabled', false);
            $('#btn_swap_locations').prop('disabled', false);
        }
    });

    $('#stock_transfer_form').on('submit', function(e) {
        if ($('#items_table tbody tr').length === 0 || $('#empty_row').length > 0) {
            e.preventDefault();
            alert('Please add at least one item to transfer.');
            return;
        }
    });

    updateHiddenLocations();
});
</script>
</body>
</html>
