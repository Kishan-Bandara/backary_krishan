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
$userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : (int) ($_POST['user_id'] ?? 0);
$userRow = $userId > 0 ? backendUserFetchUser($db, $userId) : null;

if (!$userRow) {
    echo "<script type='text/javascript'>window.location.href = 'manage-user.php';</script>";
    exit;
}

$roles = backendUserFetchRoles($db);
$locations = backendUserFetchLocations($db);
$roleMap = backendUserFetchRoleMap($roles);
$locationMap = backendUserFetchLocationMap($locations);

$message = '';
$messageClass = 'alert-success';
$formData = backendUserBuildFormData($userRow, [
    'password' => '',
    'confirm_password' => ''
]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = backendUserBuildFormData($_POST, ['password' => '', 'confirm_password' => '']);

    try {
        $errors = backendUserValidate($formData, $roleMap, $locationMap, false);

        if (backendUserExists($db, $formData['username'], $userId)) {
            $errors[] = 'This username already exists.';
        }

        if ($userId === (int) ($_SESSION['userid'] ?? 0)) {
            if ($formData['activated'] !== 'Y') {
                $errors[] = 'You cannot deactivate the currently logged in user.';
            }
            if ($formData['locked'] !== 'N') {
                $errors[] = 'You cannot lock the currently logged in user.';
            }
        }

        if (!empty($errors)) {
            throw new Exception(implode(' ', $errors));
        }

        if ($formData['password'] !== '') {
            $db->updateRow(
                'UPDATE users SET username = ?, password = ?, first_name = ?, last_name = ?, email = ?, user_level = ?, activated = ?, locked = ?, location_status = ? WHERE userid = ?',
                [
                    $formData['username'],
                    $formData['password'],
                    $formData['first_name'],
                    $formData['last_name'],
                    $formData['email'],
                    (int) $formData['user_level'],
                    $formData['activated'],
                    $formData['locked'],
                    (int) $formData['location_status'],
                    $userId
                ]
            );
        } else {
            $db->updateRow(
                'UPDATE users SET username = ?, first_name = ?, last_name = ?, email = ?, user_level = ?, activated = ?, locked = ?, location_status = ? WHERE userid = ?',
                [
                    $formData['username'],
                    $formData['first_name'],
                    $formData['last_name'],
                    $formData['email'],
                    (int) $formData['user_level'],
                    $formData['activated'],
                    $formData['locked'],
                    (int) $formData['location_status'],
                    $userId
                ]
            );
        }

        if ($userId === (int) ($_SESSION['userid'] ?? 0)) {
            $_SESSION['username'] = $formData['username'];
            $_SESSION['first_name'] = $formData['first_name'];
            $_SESSION['activated'] = $formData['activated'];
            $_SESSION['locked'] = $formData['locked'];
            $_SESSION['userlevel'] = (int) $formData['user_level'];
            $_SESSION['location'] = (int) $formData['location_status'];
            unset($_SESSION['permissions']);
            $_SESSION['permissions_loaded'] = false;
        }

        header('Location: manage-user.php?updated=1');
        exit;
    } catch (Exception $e) {
        $message = $e->getMessage() ?: 'Unable to update backend user.';
        $messageClass = 'alert-danger';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Edit Backend User</title>
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
                    <li><span>Backend Users</span><i class="fa fa-circle"></i></li>
                    <li><a href="manage-user.php">Manage Backend Users</a><i class="fa fa-circle"></i></li>
                    <li><span>Edit Backend User</span></li>
                </ul>
            </div>

            <h3 class="page-title">Edit Backend User</h3>

            <?php if ($message !== '') { ?>
                <div class="alert <?php echo $messageClass; ?>"><?php echo backendUserEscape($message); ?></div>
            <?php } ?>

            <div class="portlet light bordered form-fit">
                <div class="portlet-title">
                    <div class="caption">
                        <i class="fa fa-pencil font-blue"></i>
                        <span class="caption-subject font-blue sbold uppercase">Update Backend User</span>
                    </div>
                </div>
                <div class="portlet-body form">
                    <form action="" method="post" class="form-horizontal form-bordered form-row-stripped">
                        <input type="hidden" name="user_id" value="<?php echo (int) $userId; ?>">
                        <div class="form-body">
                            <div class="form-group form-md-line-input">
                                <label class="col-md-3 control-label">Username <span class="required">*</span></label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" name="username" value="<?php echo backendUserEscape($formData['username']); ?>" maxlength="50">
                                    <div class="form-control-focus"></div>
                                </div>
                            </div>
                            <div class="form-group form-md-line-input">
                                <label class="col-md-3 control-label">New Password</label>
                                <div class="col-md-6">
                                    <input type="password" class="form-control" name="password" value="">
                                    <div class="form-control-focus"></div>
                                    <span class="help-block">Leave blank to keep the current password.</span>
                                </div>
                            </div>
                            <div class="form-group form-md-line-input">
                                <label class="col-md-3 control-label">Confirm Password</label>
                                <div class="col-md-6">
                                    <input type="password" class="form-control" name="confirm_password" value="">
                                    <div class="form-control-focus"></div>
                                </div>
                            </div>
                            <div class="form-group form-md-line-input">
                                <label class="col-md-3 control-label">First Name <span class="required">*</span></label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" name="first_name" value="<?php echo backendUserEscape($formData['first_name']); ?>" maxlength="50">
                                    <div class="form-control-focus"></div>
                                </div>
                            </div>
                            <div class="form-group form-md-line-input">
                                <label class="col-md-3 control-label">Last Name</label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" name="last_name" value="<?php echo backendUserEscape($formData['last_name']); ?>" maxlength="50">
                                    <div class="form-control-focus"></div>
                                </div>
                            </div>
                            <div class="form-group form-md-line-input">
                                <label class="col-md-3 control-label">Email</label>
                                <div class="col-md-6">
                                    <input type="email" class="form-control" name="email" value="<?php echo backendUserEscape($formData['email']); ?>" maxlength="100">
                                    <div class="form-control-focus"></div>
                                </div>
                            </div>
                            <div class="form-group form-md-line-input">
                                <label class="col-md-3 control-label">User Role <span class="required">*</span></label>
                                <div class="col-md-6">
                                    <select class="form-control" name="user_level">
                                        <option value="">Select Role</option>
                                        <?php foreach ($roles as $role) { ?>
                                            <option value="<?php echo (int) $role['user_level_id']; ?>" <?php echo ((string) $formData['user_level'] === (string) $role['user_level_id']) ? 'selected' : ''; ?>><?php echo backendUserEscape($role['user_level_name']); ?></option>
                                        <?php } ?>
                                    </select>
                                    <div class="form-control-focus"></div>
                                </div>
                            </div>
                            <div class="form-group form-md-line-input">
                                <label class="col-md-3 control-label">Warehouse <span class="required">*</span></label>
                                <div class="col-md-6">
                                    <select class="form-control" name="location_status">
                                        <option value="">Select Warehouse</option>
                                        <?php foreach ($locations as $location) { ?>
                                            <option value="<?php echo (int) $location['id']; ?>" <?php echo ((string) $formData['location_status'] === (string) $location['id']) ? 'selected' : ''; ?>><?php echo backendUserEscape($location['location_code'] . ' - ' . $location['name']); ?></option>
                                        <?php } ?>
                                    </select>
                                    <div class="form-control-focus"></div>
                                </div>
                            </div>
                            <div class="form-group form-md-line-input">
                                <label class="col-md-3 control-label">Activated</label>
                                <div class="col-md-6">
                                    <select class="form-control" name="activated">
                                        <option value="Y" <?php echo $formData['activated'] === 'Y' ? 'selected' : ''; ?>>Yes</option>
                                        <option value="N" <?php echo $formData['activated'] === 'N' ? 'selected' : ''; ?>>No</option>
                                    </select>
                                    <div class="form-control-focus"></div>
                                </div>
                            </div>
                            <div class="form-group form-md-line-input">
                                <label class="col-md-3 control-label">Locked</label>
                                <div class="col-md-6">
                                    <select class="form-control" name="locked">
                                        <option value="N" <?php echo $formData['locked'] === 'N' ? 'selected' : ''; ?>>No</option>
                                        <option value="Y" <?php echo $formData['locked'] === 'Y' ? 'selected' : ''; ?>>Yes</option>
                                    </select>
                                    <div class="form-control-focus"></div>
                                </div>
                            </div>
                        </div>
                        <div class="form-actions">
                            <div class="row">
                                <div class="col-md-offset-3 col-md-9">
                                    <button type="submit" class="btn green"><i class="fa fa-check"></i> Update User</button>
                                    <a href="manage-user.php" class="btn default">Back</a>
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
</body>
</html>