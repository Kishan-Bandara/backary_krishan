<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('include/database.php');
include('include/check_login.php');

if (!isSuperAdmin()) {
    requirePermission('settings.permissions');
}

$db = new Database();
$roles = $db->getRows('SELECT * FROM user_levels ORDER BY user_level_id ASC');
$permissions = $db->getRows('SELECT * FROM permissions ORDER BY permission_key ASC');

$selectedRole = 0;
if (isset($_GET['role_id'])) {
    $selectedRole = (int) $_GET['role_id'];
} elseif (isset($_POST['role_id'])) {
    $selectedRole = (int) $_POST['role_id'];
}
if ($selectedRole === 0 && !empty($roles)) {
    $selectedRole = (int) $roles[0]['user_level_id'];
}

$message = '';
$messageClass = '';

if (isset($_POST['save_permissions'])) {
    $selectedRole = (int) $_POST['role_id'];
    $selectedPermissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];
    $selectedPermissions = array_map('intval', $selectedPermissions);

    $db->deleteRow('DELETE FROM role_permissions WHERE user_level_id = ?', [$selectedRole]);

    foreach ($selectedPermissions as $permissionId) {
        if ($permissionId > 0) {
            $db->insertRow(
                'INSERT INTO role_permissions (user_level_id, permission_id) VALUES (?, ?)',
                [$selectedRole, $permissionId]
            );
        }
    }

    if (isset($_SESSION['userlevel']) && (int) $_SESSION['userlevel'] === $selectedRole) {
        unset($_SESSION['permissions']);
        $_SESSION['permissions_loaded'] = false;
    }

    $message = 'Permissions updated successfully.';
    $messageClass = 'alert-success';
}

$assignedRows = [];
if ($selectedRole > 0) {
    $assignedRows = $db->getRows('SELECT permission_id FROM role_permissions WHERE user_level_id = ?', [$selectedRole]);
}
$assignedMap = [];
foreach ($assignedRows as $row) {
    $assignedMap[(int) $row['permission_id']] = true;
}

$groupedPermissions = [];
foreach ($permissions as $perm) {
    $key = $perm['permission_key'];
    $parts = explode('.', $key);
    $group = $parts[0] ?? 'other';
    if (!isset($groupedPermissions[$group])) {
        $groupedPermissions[$group] = [];
    }
    $groupedPermissions[$group][] = $perm;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Manage Role Permissions</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <meta content="" name="description" />
    <meta content="" name="author" />
    <?php include('common/head.php'); ?>
    <style>
        .permission-group { margin-bottom: 20px; }
        .permission-group .panel-heading { font-weight: 600; text-transform: capitalize; }
        .permission-item { padding: 8px 12px; border-bottom: 1px solid #f0f0f0; }
        .permission-item:last-child { border-bottom: 0; }
        .permission-row { display: flex; flex-wrap: wrap; }
        .permission-group .panel { height: 100%; display: flex; flex-direction: column; }
        .permission-group .panel-body { flex: 1; display: flex; flex-direction: column; }
    </style>
</head>
<body class="page-sidebar-closed-hide-logo page-content-white">
<?php include('common/manubar.php'); ?>
<div class="page-container">
    <div class="page-content-wrapper">
        <div class="page-content">
            <h3 class="page-title">Manage Role Permissions</h3>

            <?php if (!empty($message)) { ?>
                <div class="alert <?php echo $messageClass; ?>">
                    <?php echo $message; ?>
                </div>
            <?php } ?>

            <div class="portlet light bordered">
                <div class="portlet-title">
                    <div class="caption">
                        <i class="fa fa-lock"></i> Assign Permissions
                    </div>
                </div>
                <div class="portlet-body">
                    <form method="post" class="form-horizontal">
                        <div class="form-group">
                            <label class="control-label col-md-2">User Role</label>
                            <div class="col-md-6">
                                <select class="form-control" id="role_id" name="role_id">
                                    <?php foreach ($roles as $role) { ?>
                                        <option value="<?php echo $role['user_level_id']; ?>" <?php echo ((int) $role['user_level_id'] === $selectedRole) ? 'selected' : ''; ?>>
                                            <?php echo $role['user_level_name']; ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn default" id="changeRole">Load</button>
                            </div>
                        </div>

                        <div class="row permission-row">
                            <?php foreach ($groupedPermissions as $group => $perms) { ?>
                                <div class="col-md-4 permission-group">
                                    <div class="panel panel-default">
                                        <div class="panel-heading">
                                            <?php echo $group; ?>
                                        </div>
                                        <div class="panel-body" style="padding:0;">
                                            <?php foreach ($perms as $perm) { ?>
                                                <?php $permId = (int) $perm['permission_id']; ?>
                                                <div class="permission-item">
                                                    <label class="mt-checkbox mt-checkbox-outline">
                                                        <input type="checkbox" name="permissions[]" value="<?php echo $permId; ?>" <?php echo isset($assignedMap[$permId]) ? 'checked' : ''; ?> />
                                                        <?php echo $perm['permission_name']; ?>
                                                        <span></span>
                                                    </label>
                                                    <div style="font-size:12px;color:#888;"><?php echo $perm['permission_key']; ?></div>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>

                        <div class="form-actions">
                            <div class="row">
                                <div class="col-md-offset-2 col-md-10">
                                    <button type="submit" name="save_permissions" class="btn green">
                                        <i class="fa fa-check"></i> Save Permissions
                                    </button>
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

<!--[if lt IE 9]>
<script src="assets/global/plugins/respond.min.js"></script>
<script src="assets/global/plugins/excanvas.min.js"></script> 
<![endif]-->
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
<script src="assets/layouts/layout/scripts/demo.min.js" type="text/javascript"></script>
<script src="assets/layouts/global/scripts/quick-sidebar.min.js" type="text/javascript"></script>
<script>
    $(document).on('click', '#changeRole', function () {
        var roleId = $('#role_id').val();
        if (roleId) {
            window.location.href = 'manage-permissions.php?role_id=' + roleId;
        }
    });
</script>
</body>
</html>
