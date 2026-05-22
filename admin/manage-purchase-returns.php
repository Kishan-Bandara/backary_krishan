<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');
include('get_url.php');

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$db = new Database();
$message = $_GET['message'] ?? '';
$type = $_GET['type'] ?? '';

$locationId = (int) ($_SESSION['location'] ?? 0);

if (isSuperAdmin()) {
    $returns = $db->getRows('SELECT pr.*, s.supplier_name, g.grn_h_code, l.name AS location_name FROM purchase_return_header pr LEFT JOIN supplier s ON pr.supplier_id = s.supplier_id LEFT JOIN grn_hedder g ON pr.grn_h_id = g.grn_h_id LEFT JOIN location_master l ON pr.location_id = l.id ORDER BY pr.pr_h_id DESC') ?: [];
} else {
    $returns = $db->getRows('SELECT pr.*, s.supplier_name, g.grn_h_code, l.name AS location_name FROM purchase_return_header pr LEFT JOIN supplier s ON pr.supplier_id = s.supplier_id LEFT JOIN grn_hedder g ON pr.grn_h_id = g.grn_h_id LEFT JOIN location_master l ON pr.location_id = l.id WHERE pr.location_id = ? ORDER BY pr.pr_h_id DESC', [$locationId]) ?: [];
}

$cur = $db->getRow('SELECT currency FROM currency WHERE activated = ? LIMIT 1', ['Y']);
$currency = $cur['currency'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Purchase Returns | WebStore</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
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
                    <li><a href="index.php">Home</a><i class="fa fa-circle"></i></li>
                    <li><span>Purchase Returns</span></li>
                </ul>
            </div>

            <?php if (!empty($message)) { ?>
                <div class="alert <?php echo ($type === 'error') ? 'alert-danger' : 'alert-success'; ?> alert-dismissable">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                    <?php echo h($message); ?>
                </div>
            <?php } ?>

            <h3 class="page-title"> Purchase Returns</h3>

            <div class="portlet light bordered">
                <div class="portlet-title">
                    <div class="caption"><i class="icon-list font-dark"></i> <span class="caption-subject font-dark sbold uppercase">Return List</span></div>
                    <div class="actions">
                        <a href="purchase-return-create.php" class="btn btn-circle btn-default"><i class="fa fa-plus"></i> New Return</a>
                    </div>
                </div>
                <div class="portlet-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr class="uppercase">
                                    <th>Return No</th>
                                    <th>Date</th>
                                    <th>Supplier</th>
                                    <th>GRN</th>
                                    <th>Location</th>
                                    <th class="text-right">Total</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($returns)) { ?>
                                    <tr><td colspan="7" class="text-center text-muted">No returns found.</td></tr>
                                <?php } else { ?>
                                    <?php foreach ($returns as $row) { ?>
                                        <tr>
                                            <td><?php echo h($row['pr_h_code']); ?></td>
                                            <td><?php echo h($row['pr_date']); ?></td>
                                            <td><?php echo h($row['supplier_name'] ?? ''); ?></td>
                                            <td><?php echo h($row['grn_h_code'] ?? ''); ?></td>
                                            <td><?php echo h($row['location_name'] ?? ''); ?></td>
                                            <td class="text-right"><?php echo h($currency); ?> <?php echo number_format((float)$row['pr_gross'],2); ?></td>
                                            <td><a href="purchase-return-note.php?id=<?php echo (int)$row['pr_h_id']; ?>" class="btn btn-xs btn-default">View</a></td>
                                        </tr>
                                    <?php } ?>
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
