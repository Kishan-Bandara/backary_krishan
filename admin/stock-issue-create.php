<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');
include('get_url.php');
require_once(__DIR__ . '/include/uom_helper.php');
ensureItemUomSchema(new Database());

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
    $rows = $db->getRows('SELECT item_id, item_code, item_name, batch_tracking FROM item_master WHERE is_raw_material = 1 AND item_active = "Y" ORDER BY item_name ASC');
    $output = '<option value="">Select Product...</option>';
    foreach ($rows as $row) {
        $label = trim($row['item_code'] . ' - ' . $row['item_name']);
        $output .= '<option value="' . $row['item_id'] . '" data-batch-tracking="' . htmlspecialchars($row['batch_tracking'] ?? 'NONE') . '">' . htmlspecialchars($label) . '</option>';
    }
    return $output;
}

function load_finished_products()
{
    $db = new Database();
    $rows = $db->getRows('SELECT item_id, item_code, item_name FROM item_master WHERE (is_raw_material = 0 OR is_raw_material IS NULL) AND item_active = "Y" ORDER BY item_name ASC');
    $output = '<option value="">Select Finished Product...</option>';
    foreach ($rows as $row) {
        $label = trim($row['item_code'] . ' - ' . $row['item_name']);
        $output .= '<option value="' . $row['item_id'] . '">' . htmlspecialchars($label) . '</option>';
    }
    return $output;
}

function generateIssueCode()
{
    $db = new Database();
    $row = $db->getRow('SELECT MAX(issue_id) AS issue_id FROM stock_issue_header');
    $lastId = (int) ($row['issue_id'] ?? 0);
    $newId = $lastId + 1;
    $randomNo = rand(100000, 999999);
    return 'SI' . $randomNo . $newId;
}

$issueCode = generateIssueCode();
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
    <title>Create Stock Issue Note | WebStore</title>
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
                        <a href="stock-issue-list.php">Stock Issue Notes</a>
                        <i class="fa fa-circle"></i>
                    </li>
                    <li>
                        <span>Create Issue Note</span>
                    </li>
                </ul>
            </div>

            <?php if (!empty($message)) { ?>
                <div class="alert <?php echo ($type === 'error') ? 'alert-danger' : 'alert-success'; ?> alert-dismissable">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php } ?>

            <h3 class="page-title"> Stock Issue Note
                <small>issue stock from a location</small>
            </h3>

            <form action="process/stock-issue-create-process.php" method="post" id="stock_issue_form">
                <input type="hidden" name="location_id" id="location_id" value="<?php echo $defaultLocation; ?>" />

                <div class="row">
                    <div class="col-md-12">
                        <div class="portlet light bordered">
                            <div class="portlet-title">
                                <div class="caption">
                                    <i class="icon-settings font-dark"></i>
                                    <span class="caption-subject font-dark sbold uppercase">Issue Details</span>
                                </div>
                            </div>
                            <div class="portlet-body form">
                                <div class="form-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label bold">Issue Code</label>
                                                <input type="text" name="issue_code" class="form-control" readonly value="<?php echo $issueCode; ?>" />
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label bold">Issue Date</label>
                                                <input type="date" name="issue_date" class="form-control" required value="<?php echo $today; ?>" />
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label bold">Location</label>
                                                <select id="location_select" class="form-control select2" required <?php echo $isAdmin ? '' : 'disabled'; ?> >
                                                    <?php echo load_locations($defaultLocation, $isAdmin ? null : $defaultLocation); ?>
                                                </select>
                                                <?php if (!$isAdmin) { ?>
                                                    <span class="help-block">Staff can issue stock only from their assigned location.</span>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label bold">Issued To Reason</label>
                                                <input type="text" name="issued_to" class="form-control" value="Production Used" />
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label bold">Send Finished Products To</label>
                                                <select name="to_location_id" id="to_location_select" class="form-control select2">
                                                    <option value="">Same Location (default)</option>
                                                    <?php
                                                    $allLocations = (new Database())->getRows('SELECT * FROM location_master ORDER BY name ASC');
                                                    foreach ($allLocations as $loc) {
                                                        $lbl = trim($loc['location_code'] . ' - ' . $loc['name']);
                                                        echo '<option value="' . $loc['id'] . '">' . htmlspecialchars($lbl) . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label">Remarks</label>
                                                <input type="text" name="remarks" class="form-control" placeholder="Optional notes about this issue" />
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
                                        <input type="number" id="product_qty" class="form-control" step="0.01" min="0" placeholder="Qty" />
                                        <div id="product_base_qty_preview" style="display:none; font-size:11px;color:#888;margin-top:4px;"></div>
                                    </div>
                                    <div class="col-md-2">
                                        <label>Issue UOM</label>
                                        <select id="product_uom_select" class="form-control" disabled>
                                            <option value="">--</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2" id="batch_selector_container" style="display:none;">
                                        <label>Batch <span class="label label-info" style="font-size:10px;">Batch Tracked</span></label>
                                        <select id="batch_selector" class="form-control select2">
                                            <option value="">Select Batch...</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Available <small>(base UOM)</small></label>
                                        <div class="stat-box">
                                            <div class="stat-box-title">Current Stock</div>
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
                                            <th style="width: 10%" class="text-center"> Batch </th>
                                            <th style="width: 9%" class="text-center"> Qty </th>
                                            <th style="width: 9%" class="text-center"> UOM </th>
                                            <th style="width: 10%" class="text-center"> Base Qty </th>
                                            <th style="width: 11%" class="text-center"> Available <small>(base)</small> </th>
                                            <th style="width: 8%" class="text-center"> Action </th>
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
                                    <a href="stock-issue-list.php" class="btn default">Cancel</a>
                                    <button type="submit" class="btn" style="background-color: #357e30; color: white; border-color: #2c6626;" id="btn_submit" disabled>
                                        <i class="fa fa-save"></i> Save Issue Note
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ======= EXPECTED FINISHED PRODUCTS SECTION ======= -->
                    <div class="col-md-12">
                        <div class="portlet light bordered">
                            <div class="portlet-title">
                                <div class="caption">
                                    <i class="fa fa-industry font-green-jungle"></i>
                                    <span class="caption-subject font-green-jungle sbold uppercase">Expected Finished Products</span>
                                    <span class="caption-helper"> — what the kitchen will produce from these raw materials</span>
                                </div>
                                <div class="actions">
                                    <button type="button" class="btn btn-xs btn-info" id="btn_auto_populate">
                                        <i class="fa fa-magic"></i> Auto-Populate from Recipes
                                    </button>
                                </div>
                            </div>
                            <div class="portlet-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>Finished Product</label>
                                        <select id="expected_product_selector" class="form-control select2">
                                            <?php echo load_finished_products(); ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Expected Qty</label>
                                        <input type="number" id="expected_product_qty" class="form-control" step="0.01" min="0" placeholder="Qty" />
                                    </div>
                                    <div class="col-md-3" style="padding-top: 25px;">
                                        <button type="button" class="btn" style="background-color: #1ba39c; color: white;" id="btn_add_expected">
                                            <i class="fa fa-plus"></i> Add Expected Product
                                        </button>
                                    </div>
                                </div>
                                <hr />
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover" id="expected_table">
                                        <thead>
                                        <tr class="uppercase">
                                            <th style="width: 5%"> # </th>
                                            <th> Finished Product </th>
                                            <th style="width: 15%" class="text-center"> Expected Qty </th>
                                            <th style="width: 10%" class="text-center"> Action </th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr id="expected_empty_row">
                                            <td colspan="4" class="text-center text-muted">No expected products added yet. (Optional — leave empty if not tracking production output)</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- ======= END EXPECTED FINISHED PRODUCTS ======= -->

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
    var currentProductUoms = [];

    function updateHiddenLocation() {
        $('#location_id').val($('#location_select').val());
    }

    function loadProductUoms(productId) {
        currentProductUoms = [];
        var $sel = $('#product_uom_select');
        $sel.html('<option value="">--</option>').prop('disabled', true);
        $('#product_base_qty_preview').text('');
        if (!productId) { return; }
        $.ajax({
            type: 'GET',
            url: 'process/get-item-uoms.php',
            data: { item_id: productId },
            dataType: 'json',
            success: function(res) {
                if (!res || !res.ok || !res.uoms) { return; }
                currentProductUoms = res.uoms;
                var html = '';
                var defaultUomId = '';
                $.each(res.uoms, function(i, u) {
                    html += '<option value="' + u.uom_id + '" data-qpu="' + u.qty_per_uom + '" data-name="' + u.uom_name + '">' + u.uom_name + (parseFloat(u.qty_per_uom) !== 1 ? ' (1 ' + u.uom_name + ' = ' + u.qty_per_uom + ' base)' : '') + '</option>';
                    if (u.is_base) { defaultUomId = u.uom_id; }
                });
                $sel.html(html).prop('disabled', false);
                if (defaultUomId) { $sel.val(defaultUomId); }
                updateBaseQtyPreview();
            }
        });
    }

    function getSelectedUomMeta() {
        var $opt = $('#product_uom_select option:selected');
        if (!$opt.length || !$opt.val()) { return null; }
        return {
            uom_id: $opt.val(),
            qty_per_uom: parseFloat($opt.data('qpu')) || 1,
            uom_name: $opt.data('name') || ''
        };
    }

    function updateBaseQtyPreview() {
        var qty = parseFloat($('#product_qty').val());
        var meta = getSelectedUomMeta();
        if (!meta || !qty || qty <= 0 || meta.qty_per_uom === 1) {
            $('#product_base_qty_preview').text('');
            return;
        }
        var base = qty * meta.qty_per_uom;
        $('#product_base_qty_preview').text('= ' + base + ' base');
    }

    $('#product_qty, #product_uom_select').on('input change', updateBaseQtyPreview);

    function getSelectedBatchTracking() {
        var selected = $('#product_selector option:selected');
        return selected.data('batch-tracking') || 'NONE';
    }

    function fetchAvailableStock() {
        var productId = $('#product_selector').val();
        var locationId = $('#location_select').val();
        if (!productId || !locationId) {
            $('#available_qty').text('0');
            $('#batch_selector_container').hide();
            return;
        }

        var tracking = getSelectedBatchTracking();

        if (tracking === 'BATCH' || tracking === 'SERIAL') {
            $.ajax({
                type: 'POST',
                url: 'process/get-product-batches.php',
                data: { product_id: productId, location_id: locationId },
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
                data: { product_id: productId, location_id: locationId },
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

    $('#location_select').on('change', function() {
        updateHiddenLocation();
        fetchAvailableStock();
    });

    $('#product_selector').on('change', function() {
        loadProductUoms($(this).val());
        fetchAvailableStock();
    });

    $('#btn_add_item').on('click', function() {
        var locationId = $('#location_select').val();
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
        var uomName = uomMeta ? uomMeta.uom_name : '';
        var EPS = 0.000001;

        if (!locationId) {
            alert('Please select a location.');
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

        var baseQty = qty * qtyPerUom;

        if (tracking === 'BATCH' || tracking === 'SERIAL') {
            batchId = $('#batch_selector').val();
            if (!batchId) {
                alert('Please select a batch for this batch-tracked product.');
                return;
            }
            var selectedBatchOpt = $('#batch_selector option:selected');
            batchNo = selectedBatchOpt.data('batch-no');
            var batchAvail = parseFloat(selectedBatchOpt.data('qty') || 0);
            if (baseQty - batchAvail > EPS) {
                alert('Quantity exceeds available batch stock. Required (base): ' + baseQty + ', Available (base): ' + batchAvail);
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
            if (!isNaN(available) && (baseQty - available) > EPS) {
                alert('Quantity exceeds available stock. Required (base): ' + baseQty + ', Available (base): ' + available);
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
                    <input type="hidden" name="line_uom_id[]" value="' + uomId + '">\
                    <input type="hidden" name="line_qty_per_uom[]" value="' + qtyPerUom + '">\
                    <input type="hidden" name="line_base_qty[]" value="' + baseQty + '">\
                </td>\
                <td class="text-center">' + batchDisplay + '</td>\
                <td class="text-center">' + qty + '<input type="hidden" name="issue_qty[]" value="' + qty + '"></td>\
                <td class="text-center">' + (uomName || '-') + '</td>\
                <td class="text-center">' + baseQty + '</td>\
                <td class="text-center">' + available + '</td>\
                <td class="text-center">\
                    <button type="button" class="btn btn-xs btn-danger remove-row" data-row-id="row_' + rowCount + '"><i class="fa fa-trash"></i></button>\
                </td>\
            </tr>';

        $('#items_table tbody').append(newRow);
        $('#btn_submit').prop('disabled', false);

        $('#product_selector').val('').trigger('change');
        $('#product_qty').val('');
        $('#available_qty').text('0');
        $('#batch_selector_container').hide();
        $('#product_uom_select').html('<option value="">--</option>').prop('disabled', true);
        $('#product_base_qty_preview').text('');
        currentProductUoms = [];

        $('#location_select').prop('disabled', true);
        updateHiddenLocation();
    });

    $(document).on('click', '.remove-row', function() {
        var rowId = $(this).data('row-id');
        $('#' + rowId).remove();

        if ($('#items_table tbody tr').length === 0) {
            $('#items_table tbody').append('<tr id="empty_row"><td colspan="8" class="text-center text-muted">No items added yet.</td></tr>');
            $('#btn_submit').prop('disabled', true);
            rowCount = 0;
            $('#location_select').prop('disabled', false);
        }
    });

    $('#stock_issue_form').on('submit', function(e) {
        if ($('#items_table tbody tr').length === 0 || $('#empty_row').length > 0) {
            e.preventDefault();
            alert('Please add at least one item to issue.');
            return;
        }
    });

    updateHiddenLocation();

    // ===== Expected Finished Products =====
    var expectedRowCount = 0;

    $('#btn_add_expected').on('click', function() {
        var productId = $('#expected_product_selector').val();
        var productName = $('#expected_product_selector option:selected').text();
        var qty = parseFloat($('#expected_product_qty').val());

        if (!productId) {
            alert('Please select a finished product.');
            return;
        }
        if (!qty || qty <= 0) {
            alert('Please enter a valid expected quantity.');
            return;
        }

        var existing = $('input[name="expected_product_id[]"][value="' + productId + '"]');
        if (existing.length > 0) {
            alert('This product is already in the expected list.');
            return;
        }

        $('#expected_empty_row').remove();
        expectedRowCount++;

        var row = '\
            <tr id="exp_row_' + expectedRowCount + '">\
                <td>' + expectedRowCount + '</td>\
                <td><strong>' + productName + '</strong><input type="hidden" name="expected_product_id[]" value="' + productId + '"></td>\
                <td class="text-center">' + qty + '<input type="hidden" name="expected_qty[]" value="' + qty + '"></td>\
                <td class="text-center">\
                    <button type="button" class="btn btn-xs btn-danger remove-expected" data-row-id="exp_row_' + expectedRowCount + '"><i class="fa fa-trash"></i></button>\
                </td>\
            </tr>';

        $('#expected_table tbody').append(row);

        $('#expected_product_selector').val('').trigger('change');
        $('#expected_product_qty').val('');
    });

    $(document).on('click', '.remove-expected', function() {
        var rowId = $(this).data('row-id');
        $('#' + rowId).remove();

        if ($('#expected_table tbody tr').length === 0) {
            $('#expected_table tbody').append('<tr id="expected_empty_row"><td colspan="4" class="text-center text-muted">No expected products added yet.</td></tr>');
            expectedRowCount = 0;
        }
    });

    // Auto-populate expected products from recipes (BOM)
    $('#btn_auto_populate').on('click', function() {
        var rawMaterials = [];
        $('input[name="product_id[]"]').each(function(i) {
            rawMaterials.push({
                product_id: $(this).val(),
                qty: parseFloat($('input[name="issue_qty[]"]').eq(i).val())
            });
        });

        if (rawMaterials.length === 0) {
            alert('Add raw material items first, then auto-populate.');
            return;
        }

        $.ajax({
            type: 'POST',
            url: 'process/stock-issue-auto-populate.php',
            data: { raw_materials: JSON.stringify(rawMaterials) },
            dataType: 'json',
            success: function(res) {
                if (!res || !res.products || res.products.length === 0) {
                    alert('No matching recipes found for the issued raw materials. You can add expected products manually.');
                    return;
                }

                // Clear existing expected
                $('#expected_table tbody').empty();
                expectedRowCount = 0;

                $.each(res.products, function(i, p) {
                    expectedRowCount++;
                    var row = '\
                        <tr id="exp_row_' + expectedRowCount + '">\
                            <td>' + expectedRowCount + '</td>\
                            <td><strong>' + p.name + '</strong><input type="hidden" name="expected_product_id[]" value="' + p.product_id + '"></td>\
                            <td class="text-center">' + p.qty + '<input type="hidden" name="expected_qty[]" value="' + p.qty + '"></td>\
                            <td class="text-center">\
                                <button type="button" class="btn btn-xs btn-danger remove-expected" data-row-id="exp_row_' + expectedRowCount + '"><i class="fa fa-trash"></i></button>\
                            </td>\
                        </tr>';
                    $('#expected_table tbody').append(row);
                });

                alert('Auto-populated ' + res.products.length + ' finished product(s) from recipes.');
            },
            error: function() {
                alert('Error fetching recipe data.');
            }
        });
    });
});
</script>
</body>
</html>
