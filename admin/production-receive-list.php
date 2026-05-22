<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');
include('get_url.php');

date_default_timezone_set("Asia/Colombo");

$db = new Database();
$locationId = (int) ($_SESSION['location'] ?? 0);
$isSuperAdminUser = function_exists('isSuperAdmin') ? isSuperAdmin() : false;
$message = $_GET['message'] ?? '';
$type = $_GET['type'] ?? '';

// Get all stock issues with production_status PENDING or PARTIALLY_RECEIVED
if ($isSuperAdminUser) {
    $pendingIssues = $db->getRows(
        "SELECT sih.*, lm.location_code, lm.name AS location_name, 
                tlm.location_code AS to_location_code, tlm.name AS to_location_name
         FROM stock_issue_header sih
         LEFT JOIN location_master lm ON lm.id = sih.location_id
         LEFT JOIN location_master tlm ON tlm.id = sih.to_location_id
         WHERE sih.production_status IN ('PENDING','PARTIALLY_RECEIVED')
         ORDER BY sih.issue_id DESC"
    );
} else {
    $pendingIssues = $db->getRows(
        "SELECT sih.*, lm.location_code, lm.name AS location_name,
                tlm.location_code AS to_location_code, tlm.name AS to_location_name
         FROM stock_issue_header sih
         LEFT JOIN location_master lm ON lm.id = sih.location_id
         LEFT JOIN location_master tlm ON tlm.id = sih.to_location_id
         WHERE sih.production_status IN ('PENDING','PARTIALLY_RECEIVED')
           AND (sih.to_location_id = ? OR sih.location_id = ?)
         ORDER BY sih.issue_id DESC",
        [$locationId, $locationId]
    );
}

function getUserName($userId)
{
    if (empty($userId)) return '';
    $db = new Database();
    $row = $db->getRow('SELECT first_name, last_name FROM users WHERE userid = ?', [$userId]);
    if (!$row) return $userId;
    return trim($row['first_name'] . ' ' . $row['last_name']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Production Receive Confirmation | WebStore</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
    <link href="assets/global/plugins/datatables/datatables.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.css" rel="stylesheet" type="text/css" />
    <style>
        .status-pending { color: #c9302c; font-weight: 600; }
        .status-partial { color: #ec971f; font-weight: 600; }
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
                        <span>Production Receive Confirmation</span>
                    </li>
                </ul>
            </div>

            <?php if (!empty($message)) { ?>
                <div class="alert <?php echo ($type === 'error') ? 'alert-danger' : 'alert-success'; ?> alert-dismissable" style="margin-top: 15px;">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php } ?>

            <h3 class="page-title"> Production Receive Confirmation
                <small>confirm finished products from kitchen production</small>
            </h3>

            <div class="row">
                <div class="col-md-12">
                    <div class="portlet light bordered">
                        <div class="portlet-title">
                            <div class="caption">
                                <i class="fa fa-industry font-dark"></i>
                                <span class="caption-subject font-dark sbold uppercase">Pending Productions</span>
                            </div>
                        </div>
                        <div class="portlet-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover dt-responsive" id="production_table" width="100%">
                                    <thead>
                                    <tr class="uppercase">
                                        <th> Issue Code </th>
                                        <th> Issue Date </th>
                                        <th> From Location </th>
                                        <th> To Location </th>
                                        <th> Issued To </th>
                                        <th> Created By </th>
                                        <th> Status </th>
                                        <th style="width:220px;"> Action </th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php if ($pendingIssues) { foreach ($pendingIssues as $row) { 
                                        $statusClass = $row['production_status'] === 'PENDING' ? 'status-pending' : 'status-partial';
                                    ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($row['issue_code']); ?></strong></td>
                                            <td><?php echo date('d M Y', strtotime($row['issue_date'])); ?></td>
                                            <td><?php echo htmlspecialchars(trim(($row['location_code'] ?? '') . ' - ' . ($row['location_name'] ?? ''))); ?></td>
                                            <td><?php echo htmlspecialchars(trim(($row['to_location_code'] ?? '') . ' - ' . ($row['to_location_name'] ?? ''))); ?></td>
                                            <td><?php echo htmlspecialchars($row['issued_to'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars(getUserName($row['created_by'])); ?></td>
                                            <td><span class="<?php echo $statusClass; ?>"><?php echo $row['production_status']; ?></span></td>
                                            <td>
                                                <a href="stock-issue-view.php?id=<?php echo $row['issue_id']; ?>" class="btn btn-xs btn-default">
                                                    <i class="fa fa-eye"></i> View
                                                </a>
                                                <a href="production-receive-confirm.php?issue_id=<?php echo $row['issue_id']; ?>" class="btn btn-xs btn-success">
                                                    <i class="fa fa-check"></i> Confirm Receive
                                                </a>
                                            </td>
                                        </tr>
                                    <?php } } ?>
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
    jQuery('#production_table').DataTable({
        "order": [[1, "desc"]],
        "pageLength": 25,
        "language": {
            "emptyTable": "No pending productions found."
        }
    });
});
</script>
</body>
</html>
