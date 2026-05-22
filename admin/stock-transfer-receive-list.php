<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');
include('get_url.php');

function getPendingTransfers($locationId, $isSuperAdmin)
{
    $db = new Database();
    if ($isSuperAdmin) {
        return $db->getRows('SELECT * FROM stock_transfer_header WHERE status = ? ORDER BY transfer_id DESC', ['PENDING']);
    }
    return $db->getRows('SELECT * FROM stock_transfer_header WHERE status = ? AND to_location_id = ? ORDER BY transfer_id DESC', ['PENDING', $locationId]);
}

function getLocationName($locationId)
{
    $db = new Database();
    $row = $db->getRow('SELECT location_code, name FROM location_master WHERE id = ?', [$locationId]);
    if ($row) {
        return trim($row['location_code'] . ' - ' . $row['name']);
    }
    return '';
}

function getUserName($userId)
{
    if (empty($userId)) {
        return '';
    }
    $db = new Database();
    $row = $db->getRow('SELECT first_name, last_name FROM users WHERE userid = ?', [$userId]);
    if (!$row) {
        return $userId;
    }
    return trim($row['first_name'] . ' ' . $row['last_name']);
}

$locationId = (int) ($_SESSION['location'] ?? 0);
$isSuperAdminUser = function_exists('isSuperAdmin') ? isSuperAdmin() : false;
$message = $_GET['message'] ?? '';
$type = $_GET['type'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Stock Transfer Receive Confirmation | WebStore</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
    <link href="assets/global/plugins/datatables/datatables.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.css" rel="stylesheet" type="text/css" />
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
                        <span>Receive Confirmation</span>
                    </li>
                </ul>
            </div>

            <?php if (!empty($message)) { ?>
                <div class="alert <?php echo ($type === 'error') ? 'alert-danger' : 'alert-success'; ?> alert-dismissable" style="margin-top: 15px;">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php } ?>

            <h3 class="page-title"> Stock Transfer Receive Confirmation
                <small>pending transfers to your location</small>
            </h3>

            <div class="row">
                <div class="col-md-12">
                    <div class="portlet light bordered">
                        <div class="portlet-title">
                            <div class="caption">
                                <i class="icon-docs font-dark"></i>
                                <span class="caption-subject font-dark sbold uppercase">Pending Transfers</span>
                            </div>
                        </div>
                        <div class="portlet-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover dt-responsive" id="receive_table" width="100%">
                                    <thead>
                                    <tr class="uppercase">
                                        <th> Transfer Code </th>
                                        <th> Date </th>
                                        <th> From </th>
                                        <th> To </th>
                                        <th> Created By </th>
                                        <th> Action </th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach (getPendingTransfers($locationId, $isSuperAdminUser) as $row) { ?>
                                        <tr>
                                            <td><strong><?php echo $row['transfer_code']; ?></strong></td>
                                            <td><?php echo date('d M Y', strtotime($row['transfer_date'])); ?></td>
                                            <td><?php echo getLocationName($row['from_location_id']); ?></td>
                                            <td><?php echo getLocationName($row['to_location_id']); ?></td>
                                            <td><?php echo getUserName($row['created_by']); ?></td>
                                            <td>
                                                <a href="stock-transfer-view.php?id=<?php echo $row['transfer_id']; ?>" class="btn btn-xs btn-default">
                                                    <i class="fa fa-eye"></i> View
                                                </a>
                                                <a href="stock-transfer-receive.php?id=<?php echo $row['transfer_id']; ?>" class="btn btn-xs btn-success">
                                                    <i class="fa fa-check"></i> Confirm Receive
                                                </a>
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
<script src="assets/global/plugins/datatables/datatables.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.js" type="text/javascript"></script>
<script src="assets/global/scripts/app.min.js" type="text/javascript"></script>
<script src="assets/layouts/layout/scripts/layout.min.js" type="text/javascript"></script>

<script>
jQuery(document).ready(function () {
    if (jQuery().dataTable) {
        jQuery('#receive_table').DataTable({
            "order": [[1, "desc"]],
            "pageLength": 25,
            "language": {
                "emptyTable": "No pending transfers found."
            }
        });
    }
});
</script>
</body>
</html>
