<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('include/database.php');
include('include/check_login.php');
include('include/backend_user.php');

$db = new Database();
$message = '';
$messageClass = 'alert-success';

if (isset($_GET['created'])) {
    $message = 'Backend user created successfully.';
}
if (isset($_GET['updated'])) {
    $message = 'Backend user updated successfully.';
}

if (isset($_GET['deleteID'])) {
    try {
        requirePermission('users.delete');

        $deleteId = (int) $_GET['deleteID'];
        if ($deleteId <= 0) {
            throw new Exception('Invalid backend user selected.');
        }
        if ($deleteId === (int) ($_SESSION['userid'] ?? 0)) {
            throw new Exception('You cannot delete the currently logged in user.');
        }

        $userRow = backendUserFetchUser($db, $deleteId);
        if (!$userRow) {
            throw new Exception('Backend user was not found.');
        }
        if ((int) ($userRow['userid'] ?? 0) === 1) {
            throw new Exception('Default admin user cannot be deleted.');
        }

        $db->deleteRow('DELETE FROM users WHERE userid = ?', [$deleteId]);
        header('Location: manage-user.php?deleted=1');
        exit;
    } catch (Exception $e) {
        $message = $e->getMessage() ?: 'Unable to delete backend user.';
        $messageClass = 'alert-danger';
    }
}

if (isset($_GET['deleted'])) {
    $message = 'Backend user deleted successfully.';
}

$users = backendUserFetchUsers($db);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Manage Backend Users</title>
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
                    <li><span>Backend Users</span><i class="fa fa-circle"></i></li>
                    <li><span>Manage Backend Users</span></li>
                </ul>
                <?php if (hasPermission('users.create')) { ?>
                <div class="page-toolbar">
                    <div class="btn-group pull-right">
                        <a href="add-user.php" class="btn green btn-sm"><i class="fa fa-user-plus"></i> Add Backend User</a>
                    </div>
                </div>
                <?php } ?>
            </div>

            <h3 class="page-title">Manage Backend Users</h3>

            <?php if ($message !== '') { ?>
                <div class="alert <?php echo $messageClass; ?>"><?php echo backendUserEscape($message); ?></div>
            <?php } ?>

            <div class="portlet light bordered">
                <div class="portlet-title">
                    <div class="caption font-green">
                        <i class="fa fa-users font-green"></i>
                        <span class="caption-subject bold uppercase">Backend User List</span>
                    </div>
                </div>
                <div class="portlet-body">
                    <table class="table table-striped table-bordered table-hover dt-responsive" width="100%" id="backend_user_table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Warehouse</th>
                                <th>Active</th>
                                <th>Locked</th>
                                <th style="width:170px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user) { ?>
                                <tr>
                                    <td><?php echo (int) $user['userid']; ?></td>
                                    <td><?php echo backendUserEscape($user['username']); ?></td>
                                    <td><?php echo backendUserEscape(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))); ?></td>
                                    <td><?php echo backendUserEscape($user['email']); ?></td>
                                    <td><?php echo backendUserEscape(backendUserRoleName($user)); ?></td>
                                    <td><?php echo backendUserEscape(backendUserLocationName($user)); ?></td>
                                    <td><?php echo ($user['activated'] ?? 'N') === 'Y' ? 'Yes' : 'No'; ?></td>
                                    <td><?php echo ($user['locked'] ?? 'N') === 'Y' ? 'Yes' : 'No'; ?></td>
                                    <td>
                                        <?php if (hasPermission('users.edit')) { ?>
                                            <a href="edit-user.php?user_id=<?php echo (int) $user['userid']; ?>" class="btn btn-xs btn-primary"><i class="fa fa-pencil"></i> Edit</a>
                                        <?php } ?>
                                        <?php if (hasPermission('users.delete')) { ?>
                                            <a href="manage-user.php?deleteID=<?php echo (int) $user['userid']; ?>" class="btn btn-xs btn-danger" onclick="return confirm('Delete this backend user?');"><i class="fa fa-trash"></i> Delete</a>
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
<script src="assets/layouts/layout/scripts/demo.min.js" type="text/javascript"></script>
<script src="assets/layouts/global/scripts/quick-sidebar.min.js" type="text/javascript"></script>
<script>
    jQuery(function ($) {
        $('#backend_user_table').DataTable({
            order: [[0, 'desc']]
        });
    });
</script>
</body>
</html>