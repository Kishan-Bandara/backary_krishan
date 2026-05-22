<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('include/database.php');
include('include/check_login.php');
include('include/delivery_route_groups.php');

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$db = new Database();
ensureDeliveryRouteGroupSchema($db);
$message = '';
$messageClass = '';
$selectedGroupIds = [];
$availableGroups = getDeliveryRouteGroups(false);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $routeName = trim($_POST['route_name'] ?? '');
    $routeDescription = trim($_POST['route_description'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $postedGroupIds = $_POST['group_ids'] ?? [];
    if (!is_array($postedGroupIds)) {
        $postedGroupIds = [];
    }
    $selectedGroupIds = array_values(array_unique(array_map('intval', $postedGroupIds)));

    if ($routeName === '') {
        $message = 'Route name is required.';
        $messageClass = 'alert-danger';
    } else {
        try {
            $existing = $db->getRow('SELECT id FROM delivery_route_master WHERE route_name = ? LIMIT 1', [$routeName]);
            if ($existing) {
                $message = 'Route name already exists.';
                $messageClass = 'alert-danger';
            } else {
                $db->insertRow(
                    'INSERT INTO delivery_route_master (route_name, route_description, amount, is_active) VALUES (?, ?, ?, ?)',
                    [$routeName, $routeDescription !== '' ? $routeDescription : null, $amount, $isActive]
                );
                $newRow = $db->getRow('SELECT LAST_INSERT_ID() AS id');
                $newRouteId = (int) ($newRow['id'] ?? 0);
                if ($newRouteId > 0 && !empty($selectedGroupIds)) {
                    saveDeliveryRouteGroupsForRoute($newRouteId, $selectedGroupIds);
                }
                header('Location: manage-delivery-routes.php?success=added');
                exit();
            }
        } catch (Exception $e) {
            $message = 'Unable to save route: ' . $e->getMessage();
            $messageClass = 'alert-danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Add Delivery Route | STOCK MANAGEMENT SYSTEM</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
    <link href="assets/global/plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/global/plugins/select2/css/select2-bootstrap.min.css" rel="stylesheet" type="text/css" />
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
                    <li><a href="manage-delivery-routes.php">Delivery Routes</a><i class="fa fa-circle"></i></li>
                    <li><span>Add Delivery Route</span></li>
                </ul>
            </div>

            <?php if ($message !== ''): ?>
                <div class="alert <?php echo h($messageClass ?: 'alert-info'); ?> alert-dismissable">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                    <?php echo h($message); ?>
                </div>
            <?php endif; ?>

            <div class="portlet light bordered form-fit">
                <div class="portlet-title">
                    <div class="caption"><span class="caption-subject font-green bold uppercase">Add Delivery Route</span></div>
                </div>
                <div class="portlet-body form">
                    <form action="" class="form-horizontal form-bordered" method="POST">
                        <div class="form-body">
                            <div class="form-group">
                                <label class="col-md-3 control-label">Route Name <span class="required">*</span></label>
                                <div class="col-md-7">
                                    <input type="text" class="form-control" name="route_name" required maxlength="100" value="<?php echo h($_POST['route_name'] ?? ''); ?>" placeholder="e.g. Route A - North">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-3 control-label">Description</label>
                                <div class="col-md-7">
                                    <textarea class="form-control" name="route_description" rows="4" placeholder="Optional route details"><?php echo h($_POST['route_description'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-3 control-label">Amount</label>
                                <div class="col-md-7">
                                    <input type="number" class="form-control" name="amount" value="<?php echo h($_POST['amount'] ?? '0.00'); ?>" step="0.01" min="0" placeholder="0.00">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-3 control-label">Groups</label>
                                <div class="col-md-7">
                                    <select class="form-control select2" name="group_ids[]" multiple style="width:100%">
                                        <?php foreach ($availableGroups as $g): ?>
                                            <option value="<?php echo (int) $g['id']; ?>" <?php echo in_array((int) $g['id'], $selectedGroupIds, true) ? 'selected' : ''; ?>>
                                                <?php echo h($g['name']); ?><?php echo ((int) $g['is_active'] === 0) ? ' (inactive)' : ''; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span class="help-block">Optional. Manage groups in <a href="manage-delivery-route-groups.php">Delivery Route Groups</a>.</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-3 control-label">Status</label>
                                <div class="col-md-7">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="is_active" value="1" <?php echo (!isset($_POST['route_name']) || isset($_POST['is_active'])) ? 'checked' : ''; ?>> Active
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="form-actions">
                            <div class="row">
                                <div class="col-md-offset-3 col-md-9">
                                    <button type="submit" class="btn green"><i class="fa fa-check"></i> Save Route</button>
                                    <a href="manage-delivery-routes.php" class="btn default">Cancel</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include('common/footer.php'); ?>
<script src="assets/global/plugins/jquery.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/select2/js/select2.full.min.js" type="text/javascript"></script>
<script src="assets/global/scripts/app.min.js" type="text/javascript"></script>
<script src="assets/layouts/layout/scripts/layout.min.js" type="text/javascript"></script>
<script>
    jQuery(document).ready(function ($) {
        $('.select2').select2({ placeholder: 'Select groups', allowClear: true });
    });
</script>
</body>
</html>
