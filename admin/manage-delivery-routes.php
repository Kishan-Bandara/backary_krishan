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
$messageClass = 'alert-success';

if (isset($_GET['deleteID'])) {
    $deleteId = (int)$_GET['deleteID'];
    if ($deleteId > 0) {
        try {
            $db->updateRow('UPDATE customer_shipping_address SET delivery_route_id = NULL WHERE delivery_route_id = ?', [$deleteId]);
            $db->deleteRow('DELETE FROM delivery_route_master WHERE id = ?', [$deleteId]);
            header('Location: manage-delivery-routes.php?success=deleted');
            exit();
        } catch (Exception $e) {
            $message = 'Unable to delete route: ' . $e->getMessage();
            $messageClass = 'alert-danger';
        }
    }
}

if (isset($_GET['success'])) {
    if ($_GET['success'] === 'added') {
        $message = 'Delivery route added successfully.';
    } elseif ($_GET['success'] === 'updated') {
        $message = 'Delivery route updated successfully.';
    } elseif ($_GET['success'] === 'deleted') {
        $message = 'Delivery route deleted successfully.';
    }
}

$routes = $db->getRows('SELECT * FROM delivery_route_master ORDER BY route_name ASC');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Manage Delivery Routes | STOCK MANAGEMENT SYSTEM</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
    <link href="assets/global/plugins/datatables/datatables.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.css" rel="stylesheet" type="text/css" />
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
                    <li><span>Delivery Route Master</span></li>
                </ul>
                <div class="page-toolbar">
                    <a href="manage-delivery-route-groups.php" class="btn blue btn-sm"><i class="fa fa-tags"></i> Manage Groups</a>
                    <a href="add-delivery-route.php" class="btn green btn-sm"><i class="fa fa-plus"></i> Add Delivery Route</a>
                </div>
            </div>

            <?php if ($message !== ''): ?>
                <div class="alert <?php echo h($messageClass); ?> alert-dismissable">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                    <?php echo h($message); ?>
                </div>
            <?php endif; ?>

            <div class="portlet light bordered">
                <div class="portlet-title">
                    <div class="caption font-green">
                        <i class="icon-settings font-green"></i>
                        <span class="caption-subject bold uppercase">Delivery Routes</span>
                    </div>
                </div>
                <div class="portlet-body">
                    <table class="table table-striped table-bordered table-hover dt-responsive" width="100%" id="sample_2">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Route Name</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Groups</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($routes as $route): ?>
                            <tr>
                                <td><?php echo (int)$route['id']; ?></td>
                                <td><?php echo h($route['route_name']); ?></td>
                                <td><?php echo h($route['route_description'] ?? ''); ?></td>
                                <td><?php echo number_format((float)($route['amount'] ?? 0), 2); ?></td>
                                <td>
                                    <?php
                                        $groupNames = getRouteGroupNamesForRoute((int) $route['id']);
                                        if (empty($groupNames)) {
                                            echo '<span class="text-muted">-</span>';
                                        } else {
                                            foreach ($groupNames as $gn) {
                                                echo '<span class="label label-info" style="margin-right:3px;">' . h($gn) . '</span>';
                                            }
                                        }
                                    ?>
                                </td>
                                <td><?php echo ((int)$route['is_active'] === 1) ? 'Active' : 'Inactive'; ?></td>
                                <td>
                                    <a href="edit-delivery-route.php?id=<?php echo (int)$route['id']; ?>" class="btn btn-xs btn-default"><i class="fa fa-pencil"></i> Edit</a>
                                    <a href="manage-delivery-routes.php?deleteID=<?php echo (int)$route['id']; ?>" class="btn btn-xs btn-danger" onclick="return confirm('Delete this delivery route?');"><i class="fa fa-trash"></i> Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include('common/footer.php'); ?>
<script src="assets/global/plugins/jquery.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
<script src="assets/global/scripts/datatable.js" type="text/javascript"></script>
<script src="assets/global/plugins/datatables/datatables.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.js" type="text/javascript"></script>
<script src="assets/global/scripts/app.min.js" type="text/javascript"></script>
<script src="assets/pages/scripts/table-datatables-responsive.min.js" type="text/javascript"></script>
<script src="assets/layouts/layout/scripts/layout.min.js" type="text/javascript"></script>
</body>
</html>
