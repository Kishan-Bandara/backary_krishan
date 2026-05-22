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

$alertType = '';
$alertMessage = '';
if (isset($_GET['success'])) {
    $alertType = 'success';
    switch ($_GET['success']) {
        case 'updated':
            $updatedCount = (int)($_GET['rows'] ?? 0);
            $alertMessage = $updatedCount > 0
                ? ('Batch number and expiry date updated successfully for ' . $updatedCount . ' record(s).')
                : 'Batch number and expiry date updated successfully.';
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
            $alertMessage = 'Please select item code, location, and enter batch number.';
            break;
        case 'no_batch_found':
            $alertMessage = 'No batch records found for selected item and location.';
            break;
        case 'invalid_date':
            $alertMessage = 'Invalid expiry date format.';
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
    $itemId = (int)($_POST['item_id'] ?? 0);
    $locationId = (int)($_POST['location_id'] ?? 0);
    $newBatchNo = trim($_POST['new_batch_no'] ?? '');
    $newExpiryDate = trim($_POST['new_expiry_date'] ?? '');

    if ($itemId <= 0 || $locationId <= 0 || $newBatchNo === '') {
        header('Location: batch-expire-update.php?item_id=' . $itemId . '&location_id=' . $locationId . '&error=missing_fields');
        exit;
    }

    if ($newExpiryDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $newExpiryDate)) {
        header('Location: batch-expire-update.php?item_id=' . $itemId . '&location_id=' . $locationId . '&error=invalid_date');
        exit;
    }

    $batchRows = $db->getRows(
        'SELECT DISTINCT bm.batch_id
         FROM batch_master bm
         INNER JOIN fifo f ON f.batch_id = bm.batch_id
         WHERE bm.product_id = ? AND f.ft_item = ? AND f.ft_location = ?',
        [$itemId, $itemId, $locationId]
    );

    if (!$batchRows) {
        header('Location: batch-expire-update.php?item_id=' . $itemId . '&location_id=' . $locationId . '&error=no_batch_found');
        exit;
    }

    $updatedCount = 0;
    foreach ($batchRows as $batchRow) {
        $batchId = (int)($batchRow['batch_id'] ?? 0);
        if ($batchId <= 0) {
            continue;
        }
        $ok = $db->updateRow(
            'UPDATE batch_master SET batch_no = ?, expiry_date = ? WHERE batch_id = ? AND product_id = ?',
            [$newBatchNo, ($newExpiryDate !== '' ? $newExpiryDate : null), $batchId, $itemId]
        );
        if ($ok !== false) {
            $updatedCount++;
        }
    }

    if ($updatedCount <= 0) {
        header('Location: batch-expire-update.php?item_id=' . $itemId . '&location_id=' . $locationId . '&error=save_failed');
        exit;
    }

    header('Location: batch-expire-update.php?item_id=' . $itemId . '&location_id=' . $locationId . '&success=updated&rows=' . $updatedCount);
    exit;
}

$selectedItemId = (int)($_GET['item_id'] ?? 0);
$selectedLocationId = (int)($_GET['location_id'] ?? 0);

$items = $db->getRows("SELECT item_id, item_code, item_name FROM item_master WHERE item_active = 'Y' ORDER BY item_code ASC");
$locations = $db->getRows('SELECT id, location_code, name FROM location_master ORDER BY name ASC');


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Batch Expire Update | STOCK MANAGEMENT SYSTEM</title>
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
                    <li><span>Batch Expire Update</span></li>
                </ul>
            </div>

            <h3 class="page-title">Batch Expire Update
                <small>update batch number and expiry date by item and location</small>
            </h3>

            <?php if (!empty($CompanyMessage)) { ?>
            <div class="alert <?php echo $MessageClass; ?> alert-dismissable">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                <?php echo $CompanyMessage; ?>
            </div>
            <?php } ?>

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
                        <span class="caption-subject bold uppercase">Select Item & Location</span>
                    </div>
                </div>
                <div class="portlet-body form">
                    <form method="get" action="batch-expire-update.php" class="form-horizontal">
                        <div class="form-body">
                            <div class="row">
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label class="control-label col-md-4">Item Code</label>
                                        <div class="col-md-8">
                                            <select name="item_id" class="form-control" required>
                                                <option value="">-- Select Item --</option>
                                                <?php foreach ($items as $it) { ?>
                                                    <option value="<?php echo (int)$it['item_id']; ?>" <?php echo ($selectedItemId === (int)$it['item_id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($it['item_code'] . ' - ' . $it['item_name']); ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label class="control-label col-md-4">Location</label>
                                        <div class="col-md-8">
                                            <select name="location_id" class="form-control" required>
                                                <option value="">-- Select Location --</option>
                                                <?php foreach ($locations as $loc) { ?>
                                                    <option value="<?php echo (int)$loc['id']; ?>" <?php echo ($selectedLocationId === (int)$loc['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($loc['location_code'] . ' - ' . $loc['name']); ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2 text-right">
                                    <button type="submit" class="btn blue"><i class="fa fa-search"></i> Load</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($selectedItemId > 0 && $selectedLocationId > 0) { ?>
            <div class="portlet light bordered">
                <div class="portlet-title">
                    <div class="caption font-green">
                        <i class="fa fa-pencil font-green"></i>
                        <span class="caption-subject bold uppercase">Update Batch Details</span>
                    </div>
                </div>
                <div class="portlet-body form">
                    <form method="post" action="batch-expire-update.php" class="form-horizontal">
                        <input type="hidden" name="item_id" value="<?php echo $selectedItemId; ?>" />
                        <input type="hidden" name="location_id" value="<?php echo $selectedLocationId; ?>" />
                        <div class="form-body">
                            <div class="row">
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label class="control-label col-md-4">Batch No</label>
                                        <div class="col-md-8">
                                            <input type="text" name="new_batch_no" class="form-control" maxlength="100" required />
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label class="control-label col-md-4">Expire Date</label>
                                        <div class="col-md-8">
                                            <input type="date" name="new_expiry_date" class="form-control" />
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2 text-right">
                                    <button type="submit" class="btn green"><i class="fa fa-save"></i> Update</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php } ?>
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
