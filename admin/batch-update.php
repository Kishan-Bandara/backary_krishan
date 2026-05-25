<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('include/database.php');
include('include/check_login.php');

if (function_exists('hasPermission') && !hasPermission('settings.permissions')) {
    if (function_exists('requirePermission')) {
        requirePermission('settings.permissions');
    } else {
        header('Location: access_denied.php');
        exit;
    }
}

$db = new Database();

function buildBatchUpdateUrl($params = []) {
    return 'batch-update.php' . (!empty($params) ? ('?' . http_build_query($params)) : '');
}

$selectedItemId = (int)($_GET['item_id'] ?? 0);
$selectedLocationId = (int)($_GET['location_id'] ?? 0);
$searchText = trim($_GET['search'] ?? '');
$filterMissingBatch = !empty($_GET['missing_batch']);

$alertType = '';
$alertMessage = '';
if (isset($_GET['success'])) {
    $alertType = 'success';
    switch ($_GET['success']) {
        case 'updated':
            $alertMessage = 'Batch number and stock quantity updated successfully.';
            break;
        case 'batch_split':
            $alertMessage = 'Batch record split: New batch created with new batch number and assigned qty. Remaining balance kept in original batch.';
            break;
        default:
            $alertMessage = 'Operation completed successfully.';
            break;
    }
}
if (isset($_GET['error'])) {
    $alertType = 'danger';
    switch ($_GET['error']) {
        case 'missing_fields':
            $alertMessage = 'Please provide the batch number and stock quantity.';
            break;
        case 'invalid_batch':
            $alertMessage = 'Selected batch record was not found.';
            break;
        case 'invalid_qty':
            $alertMessage = 'Invalid quantity. Please enter a valid number.';
            break;
        case 'invalid_date':
            $alertMessage = 'Invalid expiry date. Please use a valid date.';
            break;
        case 'qty_exceeds_max':
            $alertMessage = 'Stock quantity exceeds the maximum available for this item.';
            break;
        case 'duplicate_batch':
            $alertMessage = 'That batch number already exists for this item.';
            break;
        case 'batch_no_required':
            $alertMessage = 'Please enter a batch number to assign to this record.';
            break;
        case 'save_failed':
            $alertMessage = 'Failed to update batch details. Please try again.';
            break;
        default:
            $alertMessage = 'An error occurred. Please try again.';
            break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fifoId = (int)($_POST['fifo_id'] ?? 0);
    $newBatchNo = trim($_POST['new_batch_no'] ?? '');
    $newStockQty = trim($_POST['new_stock_qty'] ?? '');
    $newExpiryDate = trim($_POST['new_expiry_date'] ?? '');

    $returnParams = [];
    $returnItemId = (int)($_POST['return_item_id'] ?? 0);
    $returnLocationId = (int)($_POST['return_location_id'] ?? 0);
    $returnSearchText = trim($_POST['return_search'] ?? '');
    $returnMissingBatch = !empty($_POST['return_missing_batch']);
    if ($returnItemId > 0) {
        $returnParams['item_id'] = $returnItemId;
    }
    if ($returnLocationId > 0) {
        $returnParams['location_id'] = $returnLocationId;
    }
    if ($returnSearchText !== '') {
        $returnParams['search'] = $returnSearchText;
    }
    if ($returnMissingBatch) {
        $returnParams['missing_batch'] = '1';
    }

    if ($fifoId <= 0 || $newStockQty === '') {
        header('Location: ' . buildBatchUpdateUrl(array_merge($returnParams, ['error' => 'missing_fields'])));
        exit;
    }
    if ($newBatchNo === '') {
        header('Location: ' . buildBatchUpdateUrl(array_merge($returnParams, ['error' => 'batch_no_required'])));
        exit;
    }

    if (!is_numeric($newStockQty) || (float)$newStockQty < 0) {
        header('Location: ' . buildBatchUpdateUrl(array_merge($returnParams, ['error' => 'invalid_qty'])));
        exit;
    }

    // Validate expiry date if provided
    $expiryDateSave = null;
    if ($newExpiryDate !== '') {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $newExpiryDate)) {
            header('Location: ' . buildBatchUpdateUrl(array_merge($returnParams, ['error' => 'invalid_date'])));
            exit;
        }
        $expiryDateSave = $newExpiryDate;
    }

    $fifoInfo = $db->getRow(
        'SELECT ft_id, ft_location, ft_item, batch_id, ft_blanace FROM fifo WHERE ft_id = ? AND ft_type = 1 LIMIT 1',
        [$fifoId]
    );
    if (!$fifoInfo) {
        header('Location: ' . buildBatchUpdateUrl(array_merge($returnParams, ['error' => 'invalid_batch'])));
        exit;
    }

    $itemId = (int)$fifoInfo['ft_item'];
    $locationId = (int)$fifoInfo['ft_location'];
    $currentBatchId = (int)($fifoInfo['batch_id'] ?? 0);
    $currentQty = (float)($fifoInfo['ft_blanace'] ?? 0);
    $currentBatchNo = '';
    if ($currentBatchId > 0) {
        $currentBatchRow = $db->getRow('SELECT batch_no FROM batch_master WHERE batch_id = ? LIMIT 1', [$currentBatchId]);
        $currentBatchNo = trim((string)($currentBatchRow['batch_no'] ?? ''));
    }

    $totalItemQty = $db->getRow(
        'SELECT COALESCE(SUM(ft_blanace), 0) AS total_qty FROM fifo WHERE ft_item = ? AND ft_type = 1',
        [$itemId]
    );
    $maxQty = (float)($totalItemQty['total_qty'] ?? 0);

    if ((float)$newStockQty > $maxQty) {
        header('Location: ' . buildBatchUpdateUrl(array_merge($returnParams, ['error' => 'qty_exceeds_max'])));
        exit;
    }

    $newQty = (float)$newStockQty;

    $targetBatch = $db->getRow(
        'SELECT batch_id FROM batch_master WHERE product_id = ? AND batch_no = ? LIMIT 1',
        [$itemId, $newBatchNo]
    );
    $targetBatchId = (int)($targetBatch['batch_id'] ?? 0);

    if ($targetBatchId <= 0) {
        $ok = $db->insertRow(
            'INSERT INTO batch_master (product_id, batch_no, expiry_date) VALUES (?, ?, ?)',
            [$itemId, $newBatchNo, $expiryDateSave]
        );
        if ($ok === false) {
            header('Location: ' . buildBatchUpdateUrl(array_merge($returnParams, ['error' => 'save_failed'])));
            exit;
        }
        $targetBatchId = (int)$db->lastInsertId();
        if ($targetBatchId <= 0) {
            header('Location: ' . buildBatchUpdateUrl(array_merge($returnParams, ['error' => 'save_failed'])));
            exit;
        }
    } else {
        $db->updateRow('UPDATE batch_master SET expiry_date = ? WHERE batch_id = ?', [$expiryDateSave, $targetBatchId]);
    }

    if ($newQty < $currentQty) {
        $remainingQty = $currentQty - $newQty;

        $ok = $db->insertRow(
            'INSERT INTO fifo (ft_item, ft_type, ft_location, batch_id, ft_blanace) VALUES (?, ?, ?, ?, ?)',
            [$itemId, 1, $locationId, $targetBatchId, $newQty]
        );
        if ($ok === false) {
            header('Location: ' . buildBatchUpdateUrl(array_merge($returnParams, ['error' => 'save_failed'])));
            exit;
        }

        $ok = $db->updateRow('UPDATE fifo SET ft_blanace = ? WHERE ft_id = ? AND ft_type = 1', [$remainingQty, $fifoId]);
        if ($ok === false) {
            header('Location: ' . buildBatchUpdateUrl(array_merge($returnParams, ['error' => 'save_failed'])));
            exit;
        }

        header('Location: ' . buildBatchUpdateUrl(array_merge($returnParams, ['success' => 'batch_split'])));
        exit;
    }

    $ok = $db->updateRow(
        'UPDATE fifo SET ft_blanace = ?, batch_id = ? WHERE ft_id = ? AND ft_type = 1',
        [$newQty, $targetBatchId, $fifoId]
    );
    if ($ok === false) {
        header('Location: ' . buildBatchUpdateUrl(array_merge($returnParams, ['error' => 'save_failed'])));
        exit;
    }

    header('Location: ' . buildBatchUpdateUrl(array_merge($returnParams, ['success' => 'updated'])));
    exit;
}

$items = $db->getRows("SELECT item_id, item_code, item_name FROM item_master WHERE item_active = 'Y' ORDER BY item_code ASC");
$locations = $db->getRows('SELECT id, location_code, name FROM location_master ORDER BY name ASC');

$whereClauses = ['f.ft_type = 1', 'f.ft_blanace > 0'];
$whereParams = [];

if ($selectedItemId > 0) {
    $whereClauses[] = 'im.item_id = ?';
    $whereParams[] = $selectedItemId;
}

if ($selectedLocationId > 0) {
    $whereClauses[] = 'lm.id = ?';
    $whereParams[] = $selectedLocationId;
}

if ($searchText !== '') {
    $searchLike = '%' . $searchText . '%';
    $whereClauses[] = '(im.item_name LIKE ? OR im.item_code LIKE ? OR IFNULL(bm.batch_no, "") LIKE ?)';
    $whereParams[] = $searchLike;
    $whereParams[] = $searchLike;
    $whereParams[] = $searchLike;
}

if ($filterMissingBatch) {
    $whereClauses[] = "(f.batch_id IS NULL OR bm.batch_no IS NULL OR bm.batch_no = '')";
}

$batchRows = $db->getRows(
    "SELECT
        f.ft_id,
        f.batch_id AS fifo_batch_id,
        bm.batch_id,
        im.item_id,
        im.item_code,
        im.item_name,
        bm.batch_no,
        bm.expiry_date,
        f.ft_blanace AS qty,
        CONCAT(lm.location_code, ' - ', lm.name) AS locations
     FROM fifo f
     INNER JOIN item_master im ON im.item_id = f.ft_item
     INNER JOIN location_master lm ON lm.id = f.ft_location
     LEFT JOIN batch_master bm ON bm.batch_id = f.batch_id
     WHERE " . implode(' AND ', $whereClauses) . "
     ORDER BY im.item_name ASC, bm.expiry_date ASC, bm.batch_no ASC",
    $whereParams
);

// Calculate total qty per item for max validation
$itemMaxQtyMap = [];
$missingBatchCount = 0;
foreach ($batchRows as $row) {
    if (empty($row['batch_no'])) {
        $missingBatchCount++;
    }
    $itemId = (int)$row['item_id'];
    if (!isset($itemMaxQtyMap[$itemId])) {
        $itemTotalRow = $db->getRow(
            'SELECT COALESCE(SUM(ft_blanace), 0) AS total_qty FROM fifo WHERE ft_item = ? AND ft_type = 1',
            [$itemId]
        );
        $itemMaxQtyMap[$itemId] = (float)($itemTotalRow['total_qty'] ?? 0);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Batch Update | STOCK MANAGEMENT SYSTEM</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
</head>
<body class="page-sidebar-closed-hide-logo page-content-white">

<?php include('common/manubar.php'); ?>
<div class="clearfix"></div>

<div class="page-container">
    <div class="page-sidebar-wrapper">
        <?php include('common/sidebar.php'); ?>
    </div>

    <div class="page-content-wrapper">
        <div class="page-content">
            <div class="page-bar">
                <ul class="page-breadcrumb">
                    <li><a href="index.php">Home</a><i class="fa fa-circle"></i></li>
                    <li><a href="#">Settings</a><i class="fa fa-circle"></i></li>
                    <li><span>Batch Update</span></li>
                </ul>
            </div>

            <h3 class="page-title">Batch Update
                <small>update batch numbers and stock quantities (max qty = total item qty)</small>
            </h3>

            <?php if ($alertMessage): ?>
            <div class="alert alert-<?php echo $alertType; ?> alert-dismissable">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                <?php echo htmlspecialchars($alertMessage); ?>
            </div>
            <?php endif; ?>

            <div class="portlet light bordered">
                <div class="portlet-title">
                    <div class="caption font-blue">
                        <i class="fa fa-filter font-blue"></i>
                        <span class="caption-subject bold uppercase">Search Batch Records</span>
                    </div>
                </div>
                <div class="portlet-body form">
                    <form method="get" action="batch-update.php" class="form-horizontal">
                        <div class="form-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label col-md-4">Item</label>
                                        <div class="col-md-8">
                                            <select name="item_id" class="form-control">
                                                <option value="">-- All Items --</option>
                                                <?php foreach ($items as $it) { ?>
                                                    <option value="<?php echo (int)$it['item_id']; ?>" <?php echo ($selectedItemId === (int)$it['item_id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($it['item_code'] . ' - ' . $it['item_name']); ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label col-md-4">Location</label>
                                        <div class="col-md-8">
                                            <select name="location_id" class="form-control">
                                                <option value="">-- All Locations --</option>
                                                <?php foreach ($locations as $loc) { ?>
                                                    <option value="<?php echo (int)$loc['id']; ?>" <?php echo ($selectedLocationId === (int)$loc['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($loc['location_code'] . ' - ' . $loc['name']); ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label col-md-3">Search</label>
                                        <div class="col-md-9">
                                            <div class="input-group">
                                                <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($searchText); ?>" placeholder="Item code/name or batch no" />
                                                <span class="input-group-btn">
                                                    <button type="submit" class="btn blue"><i class="fa fa-search"></i></button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 text-right">
                                    <label style="margin-right:15px; font-weight:normal; cursor:pointer; vertical-align:middle;">
                                        <input type="checkbox" name="missing_batch" value="1" <?php echo $filterMissingBatch ? 'checked' : ''; ?> style="margin-right:5px;" />
                                        Show only records with <strong>no batch number</strong>
                                        <?php if (!$filterMissingBatch && $missingBatchCount > 0) { ?>
                                            <span class="badge" style="background:#e67e22; color:#fff; margin-left:5px;"><?php echo $missingBatchCount; ?> missing</span>
                                        <?php } ?>
                                    </label>
                                    <a href="batch-update.php" class="btn default">Reset</a>
                                    <button type="submit" class="btn blue"><i class="fa fa-search"></i> Search</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="portlet light bordered">
                <div class="portlet-title">
                    <div class="caption font-blue">
                        <i class="fa fa-tags font-blue"></i>
                        <span class="caption-subject bold uppercase">Batch & Stock Qty Update</span>
                    </div>
                    <?php if ($filterMissingBatch && !empty($batchRows)) { ?>
                    <div class="actions">
                        <span class="badge" style="background:#e67e22; color:#fff; font-size:13px; padding:6px 12px;">
                            <i class="fa fa-exclamation-triangle"></i> <?php echo count($batchRows); ?> record(s) with no batch number — enter a batch below and click Assign
                        </span>
                    </div>
                    <?php } ?>
                </div>
                <div class="portlet-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr class="uppercase">
                                    <th style="width:5%">#</th>
                                    <th>Item</th>
                                    <th style="width:210px;">Current Batch No</th>
                                    <th style="width:210px;">New Batch No</th>
                                    <th style="width:130px;">New Stock Qty</th>
                                    <th style="width:150px;" class="text-center">Expiry Date <small class="text-muted">(editable)</small></th>
                                    <th style="width:110px;" class="text-center">Current Qty</th>
                                    <th>Locations</th>
                                    <th style="width:120px;" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (!empty($batchRows)) { ?>
                                <?php $idx = 0; foreach ($batchRows as $row) { $idx++; 
                                    $itemId = (int)$row['item_id'];
                                    $maxQty = isset($itemMaxQtyMap[$itemId]) ? $itemMaxQtyMap[$itemId] : 0;
                                    $isMissingBatch = ((int)($row['fifo_batch_id'] ?? 0) <= 0) || empty($row['batch_no']);
                                ?>
                                <tr <?php echo $isMissingBatch ? 'class="warning"' : ''; ?>>
                                    <td><?php echo $idx; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['item_name']); ?></strong>
                                        <?php if (!empty($row['item_code'])) { ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($row['item_code']); ?></small>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <?php if ($isMissingBatch) { ?>
                                            <span class="label label-warning"><i class="fa fa-exclamation-circle"></i> NULL</span>
                                        <?php } else { ?>
                                            <?php echo htmlspecialchars($row['batch_no']); ?>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <form method="post" action="batch-update.php" class="form-inline" style="display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
                                            <input type="hidden" name="fifo_id" value="<?php echo (int)$row['ft_id']; ?>" />
                                            <input type="hidden" name="return_item_id" value="<?php echo (int)$selectedItemId; ?>" />
                                            <input type="hidden" name="return_location_id" value="<?php echo (int)$selectedLocationId; ?>" />
                                            <input type="hidden" name="return_search" value="<?php echo htmlspecialchars($searchText); ?>" />
                                            <input type="hidden" name="return_missing_batch" value="<?php echo $filterMissingBatch ? '1' : '0'; ?>" />
                                            <input type="text" name="new_batch_no" class="form-control input-sm" maxlength="100" required
                                                value="<?php echo htmlspecialchars((string)$row['batch_no']); ?>"
                                                placeholder="<?php echo $isMissingBatch ? 'Enter new batch no...' : ''; ?>"
                                                style="min-width:140px;<?php echo $isMissingBatch ? ' border-color:#e67e22;' : ''; ?>" />
                                    </td>
                                    <td>
                                            <input type="number" name="new_stock_qty" class="form-control input-sm" step="0.01" min="0" max="<?php echo number_format($maxQty, 2, '.', ''); ?>" required value="<?php echo number_format((float)$row['qty'], 2, '.', ''); ?>" style="min-width:120px;" />
                                    </td>
                                    <td class="text-center">
                                        <?php
                                            $expiryVal = (!empty($row['expiry_date']) && $row['expiry_date'] !== '0000-00-00') ? $row['expiry_date'] : '';
                                        ?>
                                            <input type="date" name="new_expiry_date" class="form-control input-sm" value="<?php echo htmlspecialchars($expiryVal); ?>" style="min-width:130px;" />
                                    </td>
                                    <td class="text-center"><strong><?php echo number_format((float)$row['qty'], 2); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['locations'] ?: '-'); ?></td>
                                    <td class="text-center">
                                            <?php if ($isMissingBatch) { ?>
                                            <button type="submit" class="btn btn-xs orange"><i class="fa fa-plus"></i> Assign</button>
                                            <?php } else { ?>
                                            <button type="submit" class="btn btn-xs green"><i class="fa fa-save"></i> Update</button>
                                            <?php } ?>
                                        </form>
                                    </td>
                                </tr>
                                <?php } ?>
                            <?php } else { ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted">
                                        <?php if ($filterMissingBatch) { ?>
                                            <i class="fa fa-check-circle" style="color:#27ae60;"></i> All records have batch numbers assigned.
                                        <?php } else { ?>
                                            No batch rows found with positive stock.
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
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
