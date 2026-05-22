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
$purchaseNoteId = (int) ($_GET['purchase_note_id'] ?? 0);
$message = $_GET['message'] ?? '';
$type = $_GET['type'] ?? '';
$lineMode = (int) ($_GET['line_mode'] ?? 0);

if ($purchaseNoteId <= 0) {
    redirect('purchase-order-list.php?message=' . urlencode('Invalid purchase note.') . '&type=error');
}

$note = $db->getRow('SELECT * FROM purchase_note_header WHERE purchase_note_id = ?', [$purchaseNoteId]);
if (!$note) {
    redirect('purchase-order-list.php?message=' . urlencode('Purchase note not found.') . '&type=error');
}

if ($note['status'] === 'COMPLETED') {
    redirect('purchase-order-view.php?id=' . $purchaseNoteId . '&message=' . urlencode('Purchase note already completed.') . '&type=error');
}

$supplier = $db->getRow('SELECT * FROM supplier WHERE supplier_id = ?', [$note['supplier_id']]);
$items = $db->getRows('SELECT pni.*, itm.item_name, itm.item_code, itm.item_purchase_price, itm.item_vat, itm.batch_tracking, itm.unit_of_measure AS base_uom_name, u_line.uom_name AS line_uom_name FROM purchase_note_items pni JOIN item_master itm ON itm.item_id = pni.product_id LEFT JOIN item_uom u_line ON u_line.uom_id = pni.uom_id WHERE pni.purchase_note_id = ? ORDER BY CASE WHEN COALESCE(pni.total_received_qty, 0) > 0 THEN 0 ELSE 1 END, pni.purchase_note_item_id ASC', [$purchaseNoteId]);

if ($lineMode === 1) {
    $displayItems = [];
    $nextEntryLine = null;
    $itemById = [];

    foreach ($items as $lineItem) {
        $itemById[(int) $lineItem['purchase_note_item_id']] = $lineItem;
        if ($nextEntryLine === null && ((float) ($lineItem['balance_qty'] ?? 0) > 0)) {
            $nextEntryLine = $lineItem;
        }
    }

    $historyRows = $db->getRows(
        'SELECT gd.purchase_note_item_id, gd.grn_d_qty, gd.grn_h_id, gd.uom_id, bm.batch_no, bm.expiry_date, u.uom_name
         FROM grn_details gd
         INNER JOIN grn_hedder gh ON gh.grn_h_id = gd.grn_h_id
         LEFT JOIN batch_master bm ON bm.batch_id = gd.batch_id
         LEFT JOIN item_uom u ON u.uom_id = gd.uom_id
         WHERE gh.purchase_note_id = ?
         ORDER BY gd.grn_h_id DESC',
        [$purchaseNoteId]
    );

    $isFirstHistoryRow = true;
    foreach ($historyRows as $historyRow) {
        $pniId = (int) ($historyRow['purchase_note_item_id'] ?? 0);
        if (!isset($itemById[$pniId])) {
            continue;
        }

        $historyItem = $itemById[$pniId];
        $historyItem['_line_history'] = 1;
        $historyItem['_saved_qty'] = (float) ($historyRow['grn_d_qty'] ?? 0);
        $historyItem['_saved_uom_name'] = trim((string) ($historyRow['uom_name'] ?? ''));
        $historyItem['_saved_batch_no'] = trim((string) ($historyRow['batch_no'] ?? ''));
        $historyItem['_saved_expiry_date'] = trim((string) ($historyRow['expiry_date'] ?? ''));
        $historyItem['_latest_edit'] = $isFirstHistoryRow ? 1 : 0;
        $isFirstHistoryRow = false;
        $displayItems[] = $historyItem;
    }

    if ($nextEntryLine !== null) {
        $nextEntryLine['_line_history'] = 0;
        $nextEntryLine['_line_entry'] = 1;
        $displayItems[] = $nextEntryLine;
    }

    $items = $displayItems;
}
// Resolve base uom_id per item (auto-creates item_uom rows as needed)
require_once(__DIR__ . '/include/uom_helper.php');
foreach ($items as &$__it) {
    $__it['base_uom_id'] = resolveBaseUomIdFromString($db, $__it['base_uom_name'] ?? '');
}
unset($__it);
// active currency code
$cur = $db->getRow('SELECT currency FROM currency WHERE activated = ? LIMIT 1', ['Y']);
$active_currency_code = $cur['currency'] ?? ''; 

function generateGrnCode()
{
    $db = new Database();
    $row = $db->getRow('SELECT MAX(grn_h_id) AS grn_h_id FROM grn_hedder');
    $lastId = (int) ($row['grn_h_id'] ?? 0);
    $newId = $lastId + 1;
    $randomNo = rand(100000, 999999);
    return 'GRN' . $randomNo . $newId;
}

$grnCode = generateGrnCode();
$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Create GRN | WebStore</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <meta content="" name="description" />
    <meta content="" name="author" />
    <?php include('common/head.php'); ?>
    <style>
        .input-xsmall { width: 80px !important; }
        .table-input { margin: 0; height: 30px; padding: 5px; }
        .balance-qty { font-weight: bold; color: #d84a38; }
        .table > tbody > tr > td { vertical-align: middle; }
        .batch-split-table { width: 100%; margin: 5px 0; }
        .batch-split-table td { padding: 4px; border: none !important; }
        .batch-split-table input { height: 28px; font-size: 12px; }
        .btn-add-batch-split { font-size: 11px; padding: 2px 8px; }
        .batch-split-container { background: #f5f5f5; padding: 8px; border-radius: 4px; }
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
                        <a href="purchase-order-list.php">Purchases</a>
                        <i class="fa fa-circle"></i>
                    </li>
                     <li>
                        <a href="purchase-order-view.php?id=<?php echo $purchaseNoteId; ?>">Order <?php echo $note['purchase_note_code']; ?></a>
                        <i class="fa fa-circle"></i>
                    </li>
                    <li>
                        <span>Create GRN</span>
                    </li>
                </ul>
            </div>
            
            <?php if (!empty($message)) { ?>
                <div class="alert <?php echo ($type === 'error') ? 'alert-danger' : 'alert-success'; ?> alert-dismissable">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php } ?>

            <h3 class="page-title"> Receive Goods (GRN)
                <small>against Purchase Order <strong><?php echo $note['purchase_note_code']; ?></strong></small>
            </h3>

            <form action="process/grn-create-process.php" method="post" enctype="multipart/form-data" id="grn_form">
                <input type="hidden" name="purchase_note_id" value="<?php echo $purchaseNoteId; ?>" />
                <input type="hidden" name="submit_action" id="submit_action" value="process" />
                
                <div class="row">
                    <!-- Header Info -->
                    <div class="col-md-12">
                        <div class="portlet light bordered">
                            <div class="portlet-title">
                                <div class="caption">
                                    <i class="icon-settings font-dark"></i>
                                    <span class="caption-subject font-dark sbold uppercase">GRN Details</span>
                                </div>
                            </div>
                            <div class="portlet-body form">
                                <div class="form-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label bold">GRN Number</label>
                                                <input type="text" class="form-control" name="grn_code" readonly value="<?php echo $grnCode; ?>" />
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label bold">Received Date</label>
                                                <input type="date" class="form-control" name="grn_date" value="<?php echo $today; ?>" required />
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label bold">Supplier</label>
                                                <input type="text" class="form-control" readonly value="<?php echo $supplier['supplier_name'] ?? ''; ?>" />
                                            </div>
                                        </div>
                                    </div>
                                    <?php if(!empty($note['remarks'])) { ?>
                                    <div class="row" style="margin-top: 10px;">
                                        <div class="col-md-12">
                                            <div class="note note-info">
                                                <h4 class="block">PO Remarks</h4>
                                                <p> <?php echo nl2br(htmlspecialchars($note['remarks'])); ?> </p>
                                            </div>
                                        </div>
                                    </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="col-md-12">
                        <div class="portlet light bordered">
                            <div class="portlet-title">
                                <div class="caption">
                                    <i class="icon-basket font-blue-madison"></i>
                                    <span class="caption-subject font-blue-madison sbold uppercase">Pending Items to Receive</span>
                                </div>
                                <div class="actions">
                                    <button type="button" class="btn btn-sm" style="background-color: #357e30; color: white; border-color: #2c6626;" id="btn_receive_all">
                                        <i class="fa fa-check-square-o"></i> Receive All Remaining
                                    </button>
                                </div>
                            </div>
                            <div class="portlet-body">
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped table-bordered" id="grn_items_table">
                                        <thead>
                                        <tr class="uppercase">
                                            <th style="width: 10%"> Product Code </th>
                                            <th> Product Name </th>
                                            <th class="text-center" style="width: 9%"> Requested </th>
                                            <th class="text-center" style="width: 9%"> Received </th>
                                            <th class="text-center" style="width: 9%"> Balance </th>
                                            <th class="text-center" style="width: 11%; background: #357e30; color: white; border-left: 3px solid #2c6626;"> Receive Now </th>
                                            <th class="text-center" style="width: 10%; background: #357e30; color: white;"> UOM </th>
                                            <th class="text-center" style="width: 9%"> Batch No </th>
                                            <th class="text-center" style="width: 9%"> Expiry Date </th>
                                            <th class="text-right" style="width: 7%">Rate <small style="font-weight:normal;opacity:.7;">(per base)</small></th>
                                            <th class="text-right" style="width: 7%">Line Net</th>
                                            <th class="text-right" style="width: 7%">Line GST</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php 
                                        $hasPending = false;
                                        foreach ($items as $item) { 
                                            // Provide visual distinction if item is fully received
                                            $isCompleted = ($item['balance_qty'] <= 0);
                                            $isHistoryRow = ((int) ($item['_line_history'] ?? 0) === 1);
                                            $isLatestHistory = ((int) ($item['_latest_edit'] ?? 0) === 1);
                                            $isEditable = (!$isCompleted && !$isHistoryRow);
                                            $rowClass = trim(($isCompleted ? 'active ' : '') . ($isHistoryRow ? 'warning' : ''));
                                            if ($isEditable) $hasPending = true;
                                            $rateVal = number_format(isset($item['unit_price']) && $item['unit_price'] !== null ? (float) $item['unit_price'] : (float) ($item['item_purchase_price'] ?? 0), 2, '.', '');
                                            $vatVal = number_format(isset($item['vat_rate']) && $item['vat_rate'] !== null ? (float) $item['vat_rate'] : (float) ($item['item_vat'] ?? 0), 2, '.', '');
                                            $batchTracking = $item['batch_tracking'] ?? 'NONE';
                                            $isBatchTracked = ($batchTracking === 'BATCH' || $batchTracking === 'SERIAL');
                                            $poUomId = (int) ($item['uom_id'] ?? 0);
                                            $poQtyPerUom = (float) ($item['qty_per_uom'] ?? 0);
                                            if ($poQtyPerUom <= 0) { $poQtyPerUom = 1.0; }
                                            $baseUomId = (int) ($item['base_uom_id'] ?? 0);
                                            if ($poUomId === 0) { $poUomId = $baseUomId; }
                                            $poUomName = $item['line_uom_name'] ?: ($item['base_uom_name'] ?: '');
                                            $baseUomName = $item['base_uom_name'] ?: '';
                                            $balancePoUom = (float) $item['balance_qty'];
                                            $balanceBase = (float) ($item['balance_qty_base'] ?? 0);
                                            if ($balanceBase <= 0) { $balanceBase = $balancePoUom * $poQtyPerUom; }
                                            $historySavedQty = (float) ($item['_saved_qty'] ?? 0);
                                            $historySavedUom = trim((string) ($item['_saved_uom_name'] ?? ''));
                                            $historySavedBatch = trim((string) ($item['_saved_batch_no'] ?? ''));
                                            $historySavedExpiry = trim((string) ($item['_saved_expiry_date'] ?? ''));
                                        ?>
                                            <tr class="<?php echo $rowClass; ?> grn-item-row" data-rate="<?php echo $rateVal; ?>" data-vatrate="<?php echo $vatVal; ?>" data-batch-tracking="<?php echo $batchTracking; ?>" data-product-id="<?php echo $item['product_id']; ?>" data-pni-id="<?php echo $item['purchase_note_item_id']; ?>" data-po-uom-id="<?php echo $poUomId; ?>" data-po-qpu="<?php echo number_format($poQtyPerUom, 6, '.', ''); ?>" data-base-uom-id="<?php echo $baseUomId; ?>" data-balance-base="<?php echo number_format($balanceBase, 4, '.', ''); ?>">
                                                <td><?php echo $item['item_code']; ?></td>
                                                <td>
                                                    <?php echo $item['item_name']; ?>
                                                    <?php if($isCompleted) { echo '<span class="label label-xs label-success pull-right">Completed</span>'; } ?>
                                                    <?php if($isHistoryRow) { echo '<span class="label label-xs label-warning pull-right" style="margin-left:4px;">Saved Line</span>'; } ?>
                                                    <?php if($isLatestHistory) { echo '<span class="label label-xs label-danger pull-right" style="margin-left:4px;">Last Edit</span>'; } ?>
                                                    <?php if($isBatchTracked && $isEditable) { echo '<span class="label label-xs label-info pull-right" style="margin-right:4px;">' . ($batchTracking === 'SERIAL' ? 'Serial' : 'Batch') . ' Tracked</span>'; } ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php echo $item['requested_qty']; ?> <?php echo htmlspecialchars($poUomName); ?>
                                                    <?php if ($poQtyPerUom != 1.0): ?><div class="text-muted" style="font-size:11px;">= <?php echo number_format(((float)$item['requested_qty']) * $poQtyPerUom, 2); ?> <?php echo htmlspecialchars($baseUomName); ?></div><?php endif; ?>
                                                </td>
                                                <td class="text-center"><?php echo $isHistoryRow ? number_format($historySavedQty, 2) : $item['total_received_qty']; ?></td>
                                                <td class="text-center">
                                                    <span class="balance-qty" id="balance_<?php echo $item['purchase_note_item_id']; ?>">
                                                        <?php echo $item['balance_qty']; ?>
                                                    </span>
                                                    <?php if ($poQtyPerUom != 1.0): ?><div class="text-muted" style="font-size:11px;">= <?php echo number_format($balanceBase, 2); ?> <?php echo htmlspecialchars($baseUomName); ?></div><?php endif; ?>
                                                </td>
                                                <td class="text-center" style="background: #357e30; color: white; border-left: 3px solid #2c6626;">
                                                    <?php if($isEditable) { ?>
                                                        <input type="hidden" name="purchase_note_item_id[]" value="<?php echo $item['purchase_note_item_id']; ?>" />
                                                        <input type="number" 
                                                               step="0.01" 
                                                               min="0" 
                                                               name="received_qty[]" 
                                                               class="form-control table-input text-center receive-input" 
                                                               placeholder="0"
                                                               data-balance="<?php echo $item['balance_qty']; ?>"
                                                        />
                                                        <div class="text-muted line-base-preview" style="font-size:11px;color:#fff;opacity:.85;">&nbsp;</div>
                                                    <?php } else { ?>
                                                        <?php if($isHistoryRow) { ?>
                                                            <span class="label label-xs label-warning"><?php echo number_format($historySavedQty, 2); ?></span>
                                                        <?php } else { ?>
                                                            <i class="fa fa-check font-green"></i>
                                                        <?php } ?>
                                                    <?php } ?>
                                                </td>
                                                <td class="text-center" style="background: #357e30; color: white;">
                                                    <?php if($isEditable) { ?>
                                                        <select name="line_uom_id[]" class="form-control table-input receive-uom-select" style="min-width:90px;" disabled>
                                                            <option value="<?php echo $poUomId; ?>" data-qpu="<?php echo number_format($poQtyPerUom, 6, '.', ''); ?>"><?php echo htmlspecialchars($poUomName); ?></option>
                                                        </select>
                                                        <input type="hidden" name="line_qty_per_uom[]" class="line-qpu-hidden" value="<?php echo number_format($poQtyPerUom, 6, '.', ''); ?>" />
                                                    <?php } else { ?>
                                                        <span class="text-muted"><?php echo htmlspecialchars($isHistoryRow && $historySavedUom !== '' ? $historySavedUom : $poUomName); ?></span>
                                                    <?php } ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if($isEditable && $isBatchTracked) { ?>
                                                        <input type="text" 
                                                               name="batch_no[]" 
                                                               class="form-control table-input text-center batch-input" 
                                                               placeholder="Batch #"
                                                        />
                                                    <?php } else { ?>
                                                        <span class="text-muted"><?php echo $isHistoryRow ? htmlspecialchars($historySavedBatch !== '' ? $historySavedBatch : 'N/A') : 'N/A'; ?></span>
                                                    <?php } ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if($isEditable && $isBatchTracked) { ?>
                                                        <input type="date" 
                                                               name="expiry_date[]" 
                                                               class="form-control table-input text-center expiry-input" 
                                                        />
                                                    <?php } else { ?>
                                                        <span class="text-muted"><?php echo $isHistoryRow ? htmlspecialchars($historySavedExpiry !== '' ? $historySavedExpiry : 'N/A') : 'N/A'; ?></span>
                                                    <?php } ?>
                                                </td>
                                                <td class="text-right"><?php echo htmlspecialchars($active_currency_code); ?> <?php echo $rateVal; ?></td>
                                                <td class="text-right"><span class="line-net" id="line_net_<?php echo $item['purchase_note_item_id']; ?>">0.00</span></td>
                                                <td class="text-right"><span class="line-vat" id="line_vat_<?php echo $item['purchase_note_item_id']; ?>">0.00</span></td>
                                            </tr>
                                            <?php if(!$isCompleted && $isBatchTracked) { ?>
                                            <!-- Extra batch rows container for splitting into multiple batches -->
                                            <tr class="batch-extra-row-container" data-parent-pni="<?php echo $item['purchase_note_item_id']; ?>" style="display:none;">
                                                <td colspan="12" style="padding: 0;"></td>
                                            </tr>
                                            <?php } ?>
                                        <?php } ?>
                                        </tbody>
                                        <?php if(!$hasPending) { ?>
                                            <tfoot>
                                                <tr>
                                                    <td colspan="12" class="text-center text-success bold" style="padding: 20px;">
                                                        All items in this Purchase Note have been fully received!
                                                    </td>
                                                </tr>
                                            </tfoot>
                                        <?php } ?>
                                    </table>

                                    <!-- Totals -->
                                    <div class="row" style="margin-top:10px;">
                                        <div class="col-md-4 col-md-offset-8">
                                            <table class="table table-bordered">
                                                <tr><th>Sub Total</th><td class="text-right"><?php echo htmlspecialchars($active_currency_code); ?> <span id="grn_net_total">0.00</span></td></tr>
                                                <tr><th>Total VAT</th><td class="text-right"><?php echo htmlspecialchars($active_currency_code); ?> <span id="grn_vat_total">0.00</span></td></tr>
                                                <tr><th>Grand Total</th><td class="text-right"><strong><?php echo htmlspecialchars($active_currency_code); ?> <span id="grn_gross_total">0.00</span></strong></td></tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-actions right">
                                <a href="purchase-order-view.php?id=<?php echo $purchaseNoteId; ?>" class="btn default">Cancel</a>
                                <?php if($hasPending) { ?>
                                    <button type="submit" class="btn btn-info" id="btn_save_line">
                                        <i class="fa fa-save"></i> Save Line &amp; Load Remaining
                                    </button>
                                    <button type="submit" class="btn" style="background-color: #357e30; color: white; border-color: #2c6626;" id="btn_save_grn">
                                        <i class="fa fa-save"></i> Save & Process GRN
                                    </button>
                                <?php } ?>
                            </div>
                        </div>
                    </div>

                    <!-- Attachments -->
                    <div class="col-md-12">
                        <div class="portlet light bordered">
                            <div class="portlet-title">
                                <div class="caption">
                                    <i class="fa fa-paperclip font-purple-soft"></i>
                                    <span class="caption-subject font-purple-soft sbold uppercase">Attachments <small style="font-weight:normal;">&mdash; optional, max 5 files</small></span>
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
                                    <label>Upload Files <span class="text-muted" style="font-weight:normal; font-size:12px;">PDF, Word, Excel, Image &mdash; max&nbsp;5 files, 10&nbsp;MB each</span></label>
                                    <input type="file" name="grn_attachments[]" id="grn_attachments" multiple
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
                </div>
            </form>

        </div>
    </div>
</div>

<?php include('common/footer.php'); ?>

<!-- Scripts -->
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

    // Lazy-load alternative UOMs for each line and enable the dropdown
    $('#grn_items_table tbody tr.grn-item-row').each(function() {
        var $row = $(this);
        var productId = $row.data('product-id');
        var poUomId = parseInt($row.data('po-uom-id'), 10) || 0;
        var poQpu = parseFloat($row.data('po-qpu')) || 1;
        var $sel = $row.find('.receive-uom-select');
        if (!$sel.length) { return; }
        $.getJSON('process/get-item-uoms.php', { item_id: productId }, function(resp) {
            if (!resp || !resp.ok) { return; }
            var html = '';
            (resp.uoms || []).forEach(function(u) {
                var sel = (parseInt(u.uom_id, 10) === poUomId) ? ' selected' : '';
                html += '<option value="' + u.uom_id + '" data-qpu="' + u.qty_per_uom + '"' + sel + '>' + (u.uom_name || ('#' + u.uom_id)) + (u.is_base ? ' (base)' : '') + '</option>';
            });
            if (html === '') {
                html = '<option value="' + poUomId + '" data-qpu="' + poQpu + '">' + ($row.find('.receive-uom-select option:first').text() || '') + '</option>';
            }
            $sel.html(html).prop('disabled', false);
            updateLineBasePreview($row);
        });
    });

    function getRowQpu($row) {
        var $opt = $row.find('.receive-uom-select option:selected');
        if ($opt && $opt.length) {
            var qpu = parseFloat($opt.data('qpu'));
            if (qpu > 0) { return qpu; }
        }
        return parseFloat($row.data('po-qpu')) || 1;
    }

    function updateLineBasePreview($row) {
        var qty = parseFloat($row.find('.receive-input').val()) || 0;
        var qpu = getRowQpu($row);
        var $hidden = $row.find('.line-qpu-hidden');
        if ($hidden.length) { $hidden.val(qpu.toFixed(6)); }
        var $preview = $row.find('.line-base-preview');
        if (!$preview.length) { return; }
        if (qty <= 0) { $preview.html('&nbsp;'); return; }
        var baseQty = qty * qpu;
        if (qpu === 1) { $preview.html('&nbsp;'); return; }
        $preview.text('= ' + baseQty.toFixed(2) + ' base');
    }

    function calculateTotals() {
        var netTotal = 0.0;
        var vatTotal = 0.0;
        $('#grn_items_table tbody tr.grn-item-row').each(function() {
            var row = $(this);
            var rate = parseFloat(row.data('rate') || 0);
            var vatrate = parseFloat(row.data('vatrate') || 0);
            var input = row.find('.receive-input');
            var qty = parseFloat(input.val()) || 0;
            var qpu = getRowQpu(row);
            var baseQty = qty * qpu;
            // Stored item_purchase_price is per base UOM, so line total uses base qty
            var lineNet = baseQty * rate;
            var lineVat = (lineNet * vatrate) / 100.0;
            row.find('.line-net').text(lineNet.toFixed(2));
            row.find('.line-vat').text(lineVat.toFixed(2));
            netTotal += lineNet;
            vatTotal += lineVat;
        });
        $('#grn_net_total').text(netTotal.toFixed(2));
        $('#grn_vat_total').text(vatTotal.toFixed(2));
        $('#grn_gross_total').text((netTotal + vatTotal).toFixed(2));
    }

    // "Receive All" functionality
    $('#btn_receive_all').click(function() {
        $('.receive-input').each(function() {
            var balance = $(this).data('balance');
            $(this).val(balance);
            updateLineBasePreview($(this).closest('tr.grn-item-row'));
        });
        calculateTotals();
        validateForm();
    });

    // Input Validation & recalc totals (validate against base balance)
    $(document).on('input', '.receive-input', function() {
        var input = $(this);
        var $row = input.closest('tr.grn-item-row');
        var val = parseFloat(input.val());
        var max = parseFloat(input.data('balance'));
        var qpu = getRowQpu($row);
        var balanceBase = parseFloat($row.data('balance-base')) || (max * qpu);
        var enteredBase = (val || 0) * qpu;

        if (enteredBase > balanceBase + 0.0001) {
            var maxAllowedInUom = balanceBase / qpu;
            alert('Receive quantity exceeds the available balance (' + balanceBase.toFixed(2) + ' base units). Max in selected UOM: ' + maxAllowedInUom.toFixed(2));
            input.val(maxAllowedInUom.toFixed(2));
        }
        updateLineBasePreview($row);
        calculateTotals();
        validateForm();
    });

    $(document).on('change', '.receive-uom-select', function() {
        updateLineBasePreview($(this).closest('tr.grn-item-row'));
        calculateTotals();
    });

    // Validate if at least one item has quantity > 0
    function validateForm() {
        var hasQty = false;
        $('.receive-input').each(function() {
            var val = parseFloat($(this).val());
            if(val > 0) hasQty = true;
        });

        if(!hasQty) {
            $('#btn_save_grn').prop('disabled', false);
        } else {
            $('#btn_save_grn').prop('disabled', false);
        }
    }
    
    // Form Submit Intercept — validate qty only (batch no and expiry date are optional)
    $('#btn_save_grn').on('click', function() {
        $('#submit_action').val('process');
    });

    $('#btn_save_line').on('click', function() {
        $('#submit_action').val('line_save');
    });

    $('#grn_form').submit(function(e) {
        if (!$('#submit_action').val()) {
            $('#submit_action').val('process');
        }

        var hasQty = false;

        $('.grn-item-row').each(function() {
            var qty = parseFloat($(this).find('.receive-input').val()) || 0;
            if (qty > 0) {
                hasQty = true;
            }
        });

        if(!hasQty) {
            e.preventDefault();
            alert('Please enter a received quantity for at least one item.');
            return;
        }
    });

    // Attachment file picker validation
    $('#grn_attachments').on('change', function() {
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
            var icon = 'fa-file-o';
            var ext = files[i].name.split('.').pop().toLowerCase();
            if (ext === 'pdf') icon = 'fa-file-pdf-o';
            else if (ext === 'doc' || ext === 'docx') icon = 'fa-file-word-o';
            else if (ext === 'xls' || ext === 'xlsx') icon = 'fa-file-excel-o';
            else if (['jpg','jpeg','png','gif'].indexOf(ext) !== -1) icon = 'fa-file-image-o';
            $list.append('<li style="margin-bottom:4px;"><i class="fa ' + icon + ' text-muted"></i>&nbsp;' + $('<span>').text(files[i].name).html() + ' <span class="text-muted">(' + sizeMb + ' MB)</span></li>');
        }
        $('#attachment_preview').show();
    });

    // Initialize totals on page load
    calculateTotals();

});
</script>

</body>
</html>
