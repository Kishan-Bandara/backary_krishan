<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');
include('get_url.php');

date_default_timezone_set("Asia/Colombo");

$prefillItemId = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;

function load_supplier()
{
    $db = new Database();
    $rows = $db->getRows('SELECT * FROM supplier ORDER BY supplier_name ASC');
    $output = '<option value="">Select Supplier</option>';
    foreach ($rows as $row) {
        $output .= '<option value="' . $row['supplier_id'] . '">' . $row['supplier_name'] . '</option>';
    }
    return $output;
}

function load_products()
{
    global $prefillItemId;
    $db = new Database();
    $hasAllowInGrnColumn = (bool) $db->getRow("SHOW COLUMNS FROM item_master LIKE 'allow_in_grn'");
    $rows = $hasAllowInGrnColumn
        ? $db->getRows('SELECT * FROM item_master WHERE (allow_in_grn = 1 OR allow_in_grn IS NULL) ORDER BY item_name ASC')
        : $db->getRows('SELECT * FROM item_master ORDER BY item_name ASC');
    $output = '<option value="">Select Product...</option>';
    foreach ($rows as $row) {
        // include default purchase price and VAT as data attributes
        $price = isset($row['item_purchase_price']) ? $row['item_purchase_price'] : '0.00';
        $vat = isset($row['item_vat']) ? $row['item_vat'] : '0';
        $uom = htmlspecialchars($row['unit_of_measure'] ?? '');
        $selected = ((int)$row['item_id'] === (int)$prefillItemId) ? ' selected' : '';
        $output .= '<option value="' . $row['item_id'] . '" data-rate="' . $price . '" data-vat="' . $vat . '" data-uom="' . $uom . '"' . $selected . '>' . htmlspecialchars($row['item_name']) . '</option>';
    }
    return $output;
}

function generatePurchaseNoteCode()
{
    $db = new Database();
    $row = $db->getRow('SELECT MAX(purchase_note_id) AS purchase_note_id FROM purchase_note_header');
    $lastId = (int) ($row['purchase_note_id'] ?? 0);
    $newId = $lastId + 1;
    $randomNo = rand(100000, 999999);
    return 'PN' . $randomNo . $newId;
}

// Return active currency name and rate
function get_active_currency()
{
    $db = new Database();
    $row = $db->getRow('SELECT * FROM currency WHERE activated = ? LIMIT 1', ["Y"]);
    if ($row) {
        $rate = isset($row['rate']) ? number_format($row['rate'], 4) : '1.0000';
        return $row['currency'] . ' (Rate: ' . $rate . ')';
    }
    return 'N/A';
}

// Return active currency code only
function get_active_currency_code()
{
    $db = new Database();
    $row = $db->getRow('SELECT currency FROM currency WHERE activated = ? LIMIT 1', ["Y"]);
    return $row['currency'] ?? '';
}

// Return active currency rate only
function get_active_currency_rate()
{
    $db = new Database();
    $row = $db->getRow('SELECT rate FROM currency WHERE activated = ? LIMIT 1', ["Y"]);
    return isset($row['rate']) ? (float)$row['rate'] : 1.0;
}

$purchaseNoteCode = generatePurchaseNoteCode();
$today = date('Y-m-d');
$message = $_GET['message'] ?? '';
$type = $_GET['type'] ?? '';
// Active currency code for display
$active_currency_code = get_active_currency_code();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Create Purchase Note | WebStore</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <meta content="" name="description" />
    <meta content="" name="author" />
    
    <!-- Include Common Head -->
    <?php include('common/head.php'); ?>
    
    <!-- Select2 CSS -->
    <link href="assets/global/plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/global/plugins/select2/css/select2-bootstrap.min.css" rel="stylesheet" type="text/css" />
    
    <style>
        .select2-container--bootstrap .select2-selection--single {
            height: 34px;
        }
        .form-section-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            color: #d84a38;
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
            <div class="page-bar">
                <ul class="page-breadcrumb">
                    <li>
                        <a href="index.php">Home</a>
                        <i class="fa fa-circle"></i>
                    </li>
                    <li>
                        <a href="#">Purchase</a>
                        <i class="fa fa-circle"></i>
                    </li>
                    <li>
                        <span>Create Purchase Note</span>
                    </li>
                </ul>
            </div>
            
            <h3 class="page-title"> Create Purchase Note
                <small>new purchase order</small>
            </h3>

            <?php if (!empty($message)) { ?>
                <div class="alert <?php echo ($type === 'error') ? 'alert-danger' : 'alert-success'; ?> alert-dismissable">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                    <i class="fa <?php echo ($type === 'error') ? 'fa-warning' : 'fa-check'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php } ?>

            <form action="process/purchase-order-create-process.php" method="post" id="purchase_form">
                <div class="row">
                    <!-- Top Section: Header Info -->
                    <div class="col-md-12">
                        <div class="portlet light bordered">
                            <div class="portlet-title">
                                <div class="caption">
                                    <i class="icon-settings font-dark"></i>
                                    <span class="caption-subject font-dark sbold uppercase">General Information</span>
                                </div>
                            </div>
                            <div class="portlet-body form">
                                <div class="form-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label bold">Purchase Code</label>
                                                <input type="text" name="purchase_note_code" class="form-control" readonly value="<?php echo $purchaseNoteCode; ?>" />
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label bold">Purchase Date</label>
                                                <input type="date" name="purchase_date" class="form-control" required value="<?php echo $today; ?>" />
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label bold">Supplier <span class="required" aria-required="true"> * </span></label>
                                                <select name="supplier_id" class="form-control select2" required>
                                                    <?php echo load_supplier(); ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row" style="margin-top: 15px;">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="control-label">Remarks / Note</label>
                                                <textarea name="remarks" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row" style="margin-top:10px;">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="control-label bold">Active Currency</label>
                                                <div class="form-control-static"><strong><?php echo get_active_currency(); ?></strong></div>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="location_id" value="1" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Middle Section: Add Product -->
                    <div class="col-md-12">
                        <div class="portlet light bordered bg-inverse">
                            <div class="portlet-title">
                                <div class="caption">
                                    <i class="icon-basket font-red-sunglo"></i>
                                    <span class="caption-subject font-red-sunglo sbold uppercase">Add Products</span>
                                </div>
                            </div>
                            <div class="portlet-body form">
                                <div class="form-inline" role="form" style="padding-bottom: 15px; display: flex; flex-wrap: wrap; align-items: center;">
                                    <div class="form-group" style="width: 40%; margin-right: 15px; display: flex; align-items: center;">
                                        <label class="sr-only">Product</label>
                                        <select id="product_selector" class="form-control select2" style="width: 100%;">
                                            <?php echo load_products(); ?>
                                        </select>
                                    </div>
                                    <div class="form-group" style="margin-right: 15px;width: 269px !important;display: none;align-items: center;">
                                        <label class="sr-only">Quantity</label>
                                        <div class="input-group" style="width: 100%;">
                                            <input type="number" id="product_qty" class="form-control" placeholder="Qty" step="0.01" min="0.01" value="1.00">
                                            <span class="input-group-addon" id="product_uom_display" style="min-width:48px;">&mdash;</span>
                                        </div>
                                        <div style="display:none; font-size:11px;color:#7a8aa1;margin-top:4px; width:100%;" id="product_base_qty_preview">&nbsp;</div>
                                    </div>
                                    <div class="form-group" style="margin-right: 15px; width: 160px; display: flex; align-items: center;">
                                        <label class="sr-only">Order UOM</label>
                                        <select id="product_uom_select" class="form-control" disabled style="width: 100%;">
                                            <option value="">UOM</option>
                                        </select>
                                    </div>
                                    <div class="form-group" style="margin-right: 15px; width: 150px; display: flex; align-items: center;">
                                        <label class="sr-only">Unit Price</label>
                                        <div class="input-group" style="width: 100%;">
                                            <span class="input-group-addon"><?php echo htmlspecialchars($active_currency_code); ?></span>
                                            <input type="number" id="product_price" class="form-control" placeholder="Unit Price" step="0.01" min="0" >
                                        </div>
                                    </div>
                                    <div class="form-group" style="margin-right: 15px; width: 100px; display: flex; align-items: center;">
                                        <label class="sr-only">GST %</label>
                                        <input type="text" id="product_vat" class="form-control" placeholder="GST %" readonly style="width: 100%;">
                                    </div>
                                    <button type="button" class="btn red-sunglo" id="btn_add_product" style="height: 38px;">
                                        <i class="fa fa-plus"></i> Add to List
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Section: Item List -->
                    <div class="col-md-12">
                        <div class="portlet light bordered">
                            <div class="portlet-title">
                                <div class="caption">
                                    <i class="icon-list font-blue-madison"></i>
                                    <span class="caption-subject font-blue-madison sbold uppercase">Order Items</span>
                                </div>
                            </div>
                            <div class="portlet-body">
                                <div class="table-responsive">
                                    <table class="table table-hover table-bordered table-striped" id="item_list_table">
                                        <thead>
                                            <tr>
                                                <th style="width: 5%"> # </th>
                                                <th> Product Name </th>
                                                <th style="width: 12%" class="text-center"> UOM </th>
                                                <th style="width: 10%"> Requested Qty </th>
                                                <th style="width: 10%" class="text-center"><span id="base_qty_header_label">Base Qty</span></th>
                                                <th style="width: 12%"> Unit Price <small class="text-muted" style="font-weight:normal;">(per base UOM)</small> </th>
                                                <th style="width: 8%"> +GST </th>
                                                <th style="width: 12%"> Subtotal </th>
                                                <th style="width: 8%" class="text-center"> Action </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr id="empty_row">
                                                <td colspan="9" class="text-center text-muted">No items added yet.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="row" style="margin-top:10px;">
                                    <div class="col-md-6 col-md-offset-6">
                                        <table class="table table-condensed">
                                            <tr>
                                                <td style="text-align:right; font-weight:bold;">Sub Total (Excl. GST)</td>
                                                <td style="width:160px; text-align:right;" id="sub_total">0.00</td>
                                            </tr>
                                            <tr>
                                                <td style="text-align:right; font-weight:bold;">Total GST</td>
                                                <td style="text-align:right;" id="total_vat">0.00</td>
                                            </tr>
                                            <tr>
                                                <td style="text-align:right; font-weight:bold;">Grand Total</td>
                                                <td style="text-align:right; font-weight:bold;" id="grand_total">0.00</td>
                                            </tr>
                                        </table>
                                        <input type="hidden" name="total_ex_vat" id="total_ex_vat" value="0">
                                        <input type="hidden" name="total_vat" id="total_vat_hidden" value="0">
                                        <input type="hidden" name="grand_total" id="grand_total_hidden" value="0">
                                    </div>
                                </div>
                            </div>
                            <!-- Form Actions -->
                            <div class="form-actions right">
                                <a href="purchase-order-list.php" class="btn default">Cancel</a>
                                <button type="submit" class="btn blue purchase-submit-btn" id="btn_submit_order" name="submit_action" value="save" disabled>
                                    <i class="fa fa-check"></i> Save Purchase Note
                                </button>
                                <button type="submit" class="btn green-jungle purchase-submit-btn" id="btn_submit_order_email" name="submit_action" value="save_email" disabled>
                                    <i class="fa fa-envelope"></i> Save and Send Email
                                </button>
                                <button type="submit" class="btn purple purchase-submit-btn" id="btn_submit_order_print" name="submit_action" value="save_print" disabled>
                                    <i class="fa fa-print"></i> Save and Print
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include('common/footer.php'); ?>

<!-- Script includes -->
<script src="assets/global/plugins/jquery.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/js.cookie.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/bootstrap-hover-dropdown/bootstrap-hover-dropdown.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/jquery-slimscroll/jquery.slimscroll.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/jquery.blockui.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/uniform/jquery.uniform.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/bootstrap-switch/js/bootstrap-switch.min.js" type="text/javascript"></script>
<!-- Select2 -->
<script src="assets/global/plugins/select2/js/select2.full.min.js" type="text/javascript"></script>
<!-- Theme Scripts -->
<script src="assets/global/scripts/app.min.js" type="text/javascript"></script>
<script src="assets/layouts/layout/scripts/layout.min.js" type="text/javascript"></script>

<script>
$(document).ready(function() {
    // Active currency code from server
    var currencyCode = '<?php echo addslashes($active_currency_code); ?>';
    var prefillItemId = <?php echo (int)$prefillItemId; ?>;

    // Initialize Select2
    $('.select2').select2({
        placeholder: "Select option",
        allowClear: true,
        theme: "bootstrap"
    });

    var rowCount = 0;

    function parseNumber(val) {
        var clean = String(val || '').replace(/,/g, '');
        var num = parseFloat(clean);
        return isNaN(num) ? 0 : num;
    }

    // When product selection changes, auto-fill price, VAT and UOM fields
    $('#product_selector').on('change', function() {
        var selected = $('#product_selector option:selected');
        var rate = parseNumber(selected.data('rate'));
        var vat = parseNumber(selected.data('vat'));
        var uom = selected.data('uom') || '\u2014';
        $('#product_price').val(rate.toFixed(2));
        $('#product_vat').val(vat.toFixed(2));
        $('#product_uom_display').text(uom);
        loadProductUoms(selected.val());
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
                html += '<option value="' + u.uom_id + '" data-qpu="' + u.qty_per_uom + '" data-name="' + (u.uom_name || '') + '">' + (u.uom_name || ('#' + u.uom_id)) + (u.is_base ? ' (base)' : '') + (u.is_default_purchase ? ' \u2605' : '') + '</option>';
                if (u.is_default_purchase && defaultId === null) { defaultId = u.uom_id; }
                if (defaultId === null && u.is_base) { defaultId = u.uom_id; }
            });
            $sel.html(html).prop('disabled', currentProductUoms.length === 0);
            if (defaultId !== null) { $sel.val(defaultId); }
            setBaseQtyHeader(getCurrentBaseUomName());
            updateBaseQtyPreview();
        }).fail(function() {
            $sel.html('<option value="">UOM</option>').prop('disabled', true);
            setBaseQtyHeader('');
        });
    }

    function getSelectedUomMeta() {
        var $opt = $('#product_uom_select option:selected');
        if (!$opt.length || !$opt.val()) { return null; }
        return { uom_id: parseInt($opt.val(), 10), qty_per_uom: parseFloat($opt.data('qpu')) || 1, uom_name: $opt.data('name') || '' };
    }

    function getCurrentBaseUomName() {
        var baseName = '';
        currentProductUoms.forEach(function(u) { if (u.is_base) { baseName = u.uom_name; } });
        return baseName;
    }

    function setBaseQtyHeader(baseName) {
        $('#base_qty_header_label').text(baseName ? 'Base Qty (' + baseName + ')' : 'Base Qty');
    }

    function updateBaseQtyPreview() {
        var meta = getSelectedUomMeta();
        var qty = parseNumber($('#product_qty').val());
        if (!meta || !qty) { $('#product_base_qty_preview').html('&nbsp;'); return; }
        var baseQty = qty * meta.qty_per_uom;
        var baseName = '';
        currentProductUoms.forEach(function(u) { if (u.is_base) { baseName = u.uom_name; } });
        if (baseQty !== qty) {
            $('#product_base_qty_preview').text('= ' + baseQty.toFixed(2) + ' ' + baseName + ' (base)');
        } else {
            $('#product_base_qty_preview').html('&nbsp;');
        }
    }

    $('#product_qty, #product_uom_select').on('input change', updateBaseQtyPreview);

    function formatNumber(n) { return parseNumber(n).toFixed(2); }

    function formatCurrency(n) { return (currencyCode ? currencyCode + ' ' : '') + formatNumber(n); }

    function calculateRow($row) {
        var qty = parseNumber($row.find('.item-qty').val());
        var price = parseNumber($row.find('.item-price').val()); // per base UOM
        var vatRate = parseNumber($row.find('.item-vat-rate').val());

        // Recalculate base qty from line UOM conversion factor
        var qpu = parseNumber($row.find('input[name="line_qty_per_uom[]"]').val()) || 1;
        var baseQty = qty * qpu;

        // Unit price is stored per BASE UOM, so the line amount must use base qty
        var lineAmount = price * baseQty;
        var vatAmount = (lineAmount * vatRate) / 100;
        var lineTotal = lineAmount + vatAmount;

        $row.find('.vat-amount').text(formatCurrency(vatAmount));

        var $lineTotalValue = $row.find('.line-total-value');
        if ($lineTotalValue.length) {
            $lineTotalValue.text(formatCurrency(lineTotal));
        } else {
            // fallback for older rows
            $row.find('.line-total').contents().filter(function() {
                return this.nodeType === 3;
            }).first().replaceWith(formatCurrency(lineTotal));
        }

        $row.find('input[name="vat_amount[]"]').val(vatAmount.toFixed(2));
        $row.find('input[name="line_total[]"]').val(lineTotal.toFixed(2));
        $row.find('input[name="unit_price[]"]').val(price.toFixed(2));
        $row.find('input[name="requested_qty[]"]').val(qty);

        $row.find('.line-base-qty').text(baseQty.toFixed(2));
        $row.find('input[name="line_base_qty[]"]').val(baseQty.toFixed(4));
    }

    function getLineTotalFromRow($row) {
        var inputVal = $row.find('input[name="line_total[]"]').val();
        if (inputVal !== undefined) {
            return parseNumber(inputVal);
        }
        var textVal = $row.find('.line-total-value').text();
        if (textVal) {
            return parseNumber(textVal);
        }
        return parseNumber($row.find('.line-total').text());
    }

    function calculateTotals() {
        var totalEx = 0, totalVat = 0, grand = 0;
        $('#item_list_table tbody tr').each(function(){
            if ($(this).attr('id') && $(this).attr('id').indexOf('row_') === 0) {
                var vatAmt = parseNumber($(this).find('input[name="vat_amount[]"]').val());
                var lineTotal = getLineTotalFromRow($(this));
                var lineEx = lineTotal - vatAmt;
                totalEx += lineEx;
                totalVat += vatAmt;
                grand += lineTotal;
            }
        });
        $('#sub_total').text(formatCurrency(totalEx));
        $('#total_vat').text(formatCurrency(totalVat));
        $('#grand_total').text(formatCurrency(grand));
        $('#total_ex_vat').val(totalEx.toFixed(2));
        $('#total_vat_hidden').val(totalVat.toFixed(2));
        $('#grand_total_hidden').val(grand.toFixed(2));
    }

    function isTableEmpty() {
        var $trs = $('#item_list_table tbody tr');
        return $trs.length === 0 || ($trs.length === 1 && $trs.attr('id') === 'empty_row');
    }

    // Add Product Logic
    $('#btn_add_product').click(function() {
        var productId = $('#product_selector').val();
        var productName = $('#product_selector option:selected').text();
        var productQty = parseNumber($('#product_qty').val());
        var productPrice = parseNumber($('#product_price').val());
        var productVat = parseNumber($('#product_vat').val() || 0);

        // Validation
        if (!productId) {
            alert('Please select a product.');
            return;
        }
        if (!productQty || productQty <= 0) {
            alert('Please enter a valid quantity.');
            return;
        }

        // Use default price if not provided
        if (!productPrice) {
            productPrice = parseNumber($('#product_selector option:selected').data('rate'));
        }

        // Check if product already exists in list
        var existingRow = $('input[name="product_id[]"][value="' + productId + '"]');
        if (existingRow.length > 0) {
            alert('This product is already in the list. Please remove it first to change quantity.');
            return;
        }

        // Remove empty row text
        $('#empty_row').remove();

        rowCount++;

        var uomMeta = getSelectedUomMeta();
        var selectedUomId = uomMeta ? uomMeta.uom_id : '';
        var selectedUomName = uomMeta ? uomMeta.uom_name : ($('#product_selector option:selected').data('uom') || '');
        var selectedQpu = uomMeta ? uomMeta.qty_per_uom : 1;

        var newRow = `
            <tr id="row_${rowCount}">
                <td>${rowCount}</td>
                <td>
                    <strong>${productName}</strong>
                    <input type="hidden" name="product_id[]" value="${productId}">
                </td>
                <td class="text-center">
                    ${selectedUomName || '\u2014'}
                    <input type="hidden" name="line_uom_id[]" value="${selectedUomId || ''}">
                    <input type="hidden" name="line_qty_per_uom[]" value="${selectedQpu}">
                </td>
                <td>
                    <input type="number" name="requested_qty[]" value="${formatNumber(productQty)}" class="form-control input-sm item-qty" step="0.01" min="0.01">
                </td>
                <td class="text-center">
                    <span class="line-base-qty">${(productQty * selectedQpu).toFixed(2)}</span>
                    <input type="hidden" name="line_base_qty[]" value="${(productQty * selectedQpu).toFixed(4)}" class="item-base-qty-input">
                </td>
                <td>
                    <div class="input-group">
                        <span class="input-group-addon">${currencyCode}</span>
                        <input type="number" name="unit_price[]" value="${formatNumber(productPrice)}" class="form-control input-sm item-price" step="0.01" min="0">
                    </div>
                </td>
                <td>
                    <input type="hidden" name="vat_rate[]" value="${formatNumber(productVat)}" class="item-vat-rate">
                    <div class="vat-amount">0.00</div>
                    <input type="hidden" name="vat_amount[]" value="0.00">
                </td>
                <td class="text-right line-total">
                    <span class="line-total-value">0.00</span>
                    <input type="hidden" name="line_total[]" value="0.00">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-xs remove-row" data-row-id="row_${rowCount}">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;

        $('#item_list_table tbody').append(newRow);

        // Recalculate and attach events
        var $appended = $('#row_' + rowCount);
        calculateRow($appended);
        calculateTotals();

        // Reset inputs
        $('#product_selector').val('').trigger('change');
        $('#product_qty').val('1.00');
        $('#product_price').val('');
        $('#product_vat').val('');
        $('#product_uom_display').text('\u2014');
        
        // Enable submit button
        $('.purchase-submit-btn').prop('disabled', false);

        // ensure totals are recalculated
        calculateTotals();
    });

    // Update row when qty or price changes
    $(document).on('input', '.item-qty, .item-price', function() {
        var $row = $(this).closest('tr');
        calculateRow($row);
        calculateTotals();
    });

    // Remove Row Logic
    $(document).on('click', '.remove-row', function() {
        var rowId = $(this).data('row-id');
        $('#' + rowId).remove();

        // If table now empty, show the empty row and disable submit
        if (isTableEmpty()) {
            $('#item_list_table tbody').append('<tr id="empty_row"><td colspan="8" class="text-center text-muted">No items added yet.</td></tr>');
            $('.purchase-submit-btn').prop('disabled', true);
            rowCount = 0;
        }
        calculateTotals();
    });
    
    // Form Validation before submit
    $('#purchase_form').submit(function(e) {
        if (isTableEmpty()) {
            e.preventDefault();
            alert('Please add at least one product to the purchase note.');
            return false;
        }

        // final validation: ensure all prices and quantities are valid
        var valid = true;
        $('#item_list_table tbody tr').each(function(){
            if ($(this).attr('id') && $(this).attr('id').indexOf('row_') === 0) {
                var qty = parseFloat($(this).find('.item-qty').val()) || 0;
                var price = parseFloat($(this).find('.item-price').val()) || 0;
                if (qty <= 0 || price < 0) {
                    valid = false;
                }
            }
        });
        if (!valid) { e.preventDefault(); alert('Please make sure each item has a valid quantity and unit price.'); return false; }

        return true;
    });

    // Prefill from stock-report action button
    if (prefillItemId > 0) {
        var hasOption = $('#product_selector option[value="' + prefillItemId + '"]').length > 0;
        if (hasOption) {
            $('#product_selector').val(String(prefillItemId)).trigger('change');
            if (!$('#product_qty').val()) {
                $('#product_qty').val('1.00');
            }
            $('#btn_add_product').trigger('click');
        }
    }

    // If the page loads with existing rows (e.g., editing), recalc each row and totals
    $(function() {
        $('#item_list_table tbody tr').each(function(){
            if ($(this).attr('id') && $(this).attr('id').indexOf('row_') === 0) {
                calculateRow($(this));
            }
        });
        calculateTotals();
    });
});
</script>

</body>
</html>
