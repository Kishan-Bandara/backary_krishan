<?php 
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');
include('get_url.php');

$db = new Database();

$success_msg = '';
$error_msg = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_action = isset($_POST['form_action']) ? $_POST['form_action'] : '';
    if ($form_action === 'add_product_issue') {
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        if (empty($name)) {
            $error_msg = 'Name is required.';
        } else {
            $db->insertRow('INSERT INTO complaint_product_issue_type (name, is_active) VALUES (?, 1)', [$name]);
            $success_msg = 'Product issue type added.';
        }
    } elseif ($form_action === 'add_service_issue') {
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        if (empty($name)) {
            $error_msg = 'Name is required.';
        } else {
            $db->insertRow('INSERT INTO complaint_service_issue_type (name, is_active) VALUES (?, 1)', [$name]);
            $success_msg = 'Service issue type added.';
        }
    } elseif ($form_action === 'add_resolve_reason') {
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        if (empty($name)) {
            $error_msg = 'Name is required.';
        } else {
            $db->insertRow('INSERT INTO complaint_resolve_reason (name, is_active) VALUES (?, 1)', [$name]);
            $success_msg = 'Resolve reason added.';
        }
    } elseif ($form_action === 'toggle_product_issue') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $current = $db->getRow('SELECT is_active FROM complaint_product_issue_type WHERE id = ?', [$id]);
        if ($current) {
            $new_status = $current['is_active'] ? 0 : 1;
            $db->updateRow('UPDATE complaint_product_issue_type SET is_active = ? WHERE id = ?', [$new_status, $id]);
            $success_msg = 'Status updated.';
        }
    } elseif ($form_action === 'toggle_service_issue') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $current = $db->getRow('SELECT is_active FROM complaint_service_issue_type WHERE id = ?', [$id]);
        if ($current) {
            $new_status = $current['is_active'] ? 0 : 1;
            $db->updateRow('UPDATE complaint_service_issue_type SET is_active = ? WHERE id = ?', [$new_status, $id]);
            $success_msg = 'Status updated.';
        }
    } elseif ($form_action === 'toggle_resolve_reason') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $current = $db->getRow('SELECT is_active FROM complaint_resolve_reason WHERE id = ?', [$id]);
        if ($current) {
            $new_status = $current['is_active'] ? 0 : 1;
            $db->updateRow('UPDATE complaint_resolve_reason SET is_active = ? WHERE id = ?', [$new_status, $id]);
            $success_msg = 'Status updated.';
        }
    } elseif ($form_action === 'save_default_assignment') {
        $product_user_id = !empty($_POST['default_product_user_id']) ? (int)$_POST['default_product_user_id'] : null;
        $service_user_id = !empty($_POST['default_service_user_id']) ? (int)$_POST['default_service_user_id'] : null;

        $db->insertRow(
            'INSERT INTO complaint_default_assignment (complaint_type, user_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), updated_at = NOW()',
            ['Product', $product_user_id]
        );
        $db->insertRow(
            'INSERT INTO complaint_default_assignment (complaint_type, user_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), updated_at = NOW()',
            ['Service', $service_user_id]
        );

        $success_msg = 'Default responsible users saved.';
    }
}

// Fetch all data
$product_issue_types = $db->getRows('SELECT * FROM complaint_product_issue_type ORDER BY id', []);
$service_issue_types = $db->getRows('SELECT * FROM complaint_service_issue_type ORDER BY id', []);
$resolve_reasons = $db->getRows('SELECT * FROM complaint_resolve_reason ORDER BY id', []);
$backend_users = $db->getRows('SELECT userid, username, first_name, last_name, email FROM users WHERE activated = ? AND locked = ? ORDER BY username', ['Y', 'N']);

$default_assignments = array('Product' => '', 'Service' => '');
try {
    $default_assignment_rows = $db->getRows('SELECT complaint_type, user_id FROM complaint_default_assignment', []);
    foreach ($default_assignment_rows as $assignment_row) {
        $default_assignments[$assignment_row['complaint_type']] = $assignment_row['user_id'] ? (int)$assignment_row['user_id'] : '';
    }
} catch (Exception $e) {
    if (empty($error_msg)) {
        $error_msg = 'Complaint default assignment settings are not ready yet. Please apply the latest complaint SQL update.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include('common/head.php'); ?>
</head>
<body class="page-header-fixed">
    <?php include('common/manubar.php'); ?>
    <div class="page-container">
        <div class="page-content-wrapper">
            <div class="page-content">
                <div class="page-bar">
                    <ul class="page-breadcrumb">
                        <li><a href="index.php">Dashboard</a><i class="fa fa-angle-right"></i></li>
                        <li><a href="manage-complaints.php">Complaints</a><i class="fa fa-angle-right"></i></li>
                        <li><span>Complaint Masters</span></li>
                    </ul>
                </div>

                <h1 class="page-title"> Complaint Master Data </h1>

                <?php if ($success_msg) { ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success_msg, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php } ?>
                <?php if ($error_msg) { ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_msg, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php } ?>

                <div class="row">
                    <div class="col-md-12">
                        <div class="portlet light bordered">
                            <div class="portlet-title">
                                <div class="caption"><i class="fa fa-user-circle"></i> Default Responsible Users</div>
                            </div>
                            <div class="portlet-body">
                                <form method="POST">
                                    <input type="hidden" name="form_action" value="save_default_assignment">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Product Complaint Default User</label>
                                                <select class="form-control" name="default_product_user_id">
                                                    <option value="">-- Not Assigned --</option>
                                                    <?php if ($backend_users) { foreach ($backend_users as $user) { ?>
                                                        <option value="<?php echo (int)$user['userid']; ?>" <?php echo ((string)$default_assignments['Product'] === (string)$user['userid']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($user['username'] . (!empty($user['email']) ? ' - ' . $user['email'] : ''), ENT_QUOTES, 'UTF-8'); ?>
                                                        </option>
                                                    <?php } } ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Service Complaint Default User</label>
                                                <select class="form-control" name="default_service_user_id">
                                                    <option value="">-- Not Assigned --</option>
                                                    <?php if ($backend_users) { foreach ($backend_users as $user) { ?>
                                                        <option value="<?php echo (int)$user['userid']; ?>" <?php echo ((string)$default_assignments['Service'] === (string)$user['userid']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($user['username'] . (!empty($user['email']) ? ' - ' . $user['email'] : ''), ENT_QUOTES, 'UTF-8'); ?>
                                                        </option>
                                                    <?php } } ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-muted" style="margin-bottom:15px;">New complaints will be assigned automatically to these users, and email notifications will be sent immediately after complaint submission.</p>
                                    <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Default Assignment</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Product Issue Types -->
                    <div class="col-md-4">
                        <div class="portlet light bordered">
                            <div class="portlet-title">
                                <div class="caption"><i class="fa fa-shopping-bag"></i> Product Issue Types</div>
                            </div>
                            <div class="portlet-body">
                                <form method="POST" class="form-inline" style="margin-bottom:15px;">
                                    <input type="hidden" name="form_action" value="add_product_issue">
                                    <div class="input-group" style="width:100%;">
                                        <input type="text" class="form-control" name="name" placeholder="Add new type..." required>
                                        <span class="input-group-btn">
                                            <button class="btn btn-primary" type="submit"><i class="fa fa-plus"></i></button>
                                        </span>
                                    </div>
                                </form>
                                <table class="table table-striped table-condensed">
                                    <thead><tr><th>Name</th><th>Status</th><th></th></tr></thead>
                                    <tbody>
                                        <?php if ($product_issue_types) { foreach ($product_issue_types as $item) { ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <?php if ($item['is_active']) { ?>
                                                    <span class="label label-success">Active</span>
                                                <?php } else { ?>
                                                    <span class="label label-default">Inactive</span>
                                                <?php } ?>
                                            </td>
                                            <td>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="form_action" value="toggle_product_issue">
                                                    <input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>">
                                                    <input type="hidden" name="name" value="toggle">
                                                    <button type="submit" class="btn btn-xs btn-default" title="Toggle Status"><i class="fa fa-exchange"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php } } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Service Issue Types -->
                    <div class="col-md-4">
                        <div class="portlet light bordered">
                            <div class="portlet-title">
                                <div class="caption"><i class="fa fa-headphones"></i> Service Issue Types</div>
                            </div>
                            <div class="portlet-body">
                                <form method="POST" class="form-inline" style="margin-bottom:15px;">
                                    <input type="hidden" name="form_action" value="add_service_issue">
                                    <div class="input-group" style="width:100%;">
                                        <input type="text" class="form-control" name="name" placeholder="Add new type..." required>
                                        <span class="input-group-btn">
                                            <button class="btn btn-primary" type="submit"><i class="fa fa-plus"></i></button>
                                        </span>
                                    </div>
                                </form>
                                <table class="table table-striped table-condensed">
                                    <thead><tr><th>Name</th><th>Status</th><th></th></tr></thead>
                                    <tbody>
                                        <?php if ($service_issue_types) { foreach ($service_issue_types as $item) { ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <?php if ($item['is_active']) { ?>
                                                    <span class="label label-success">Active</span>
                                                <?php } else { ?>
                                                    <span class="label label-default">Inactive</span>
                                                <?php } ?>
                                            </td>
                                            <td>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="form_action" value="toggle_service_issue">
                                                    <input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>">
                                                    <input type="hidden" name="name" value="toggle">
                                                    <button type="submit" class="btn btn-xs btn-default" title="Toggle Status"><i class="fa fa-exchange"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php } } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Resolve Reasons -->
                    <div class="col-md-4">
                        <div class="portlet light bordered">
                            <div class="portlet-title">
                                <div class="caption"><i class="fa fa-check-circle"></i> Resolve Reasons</div>
                            </div>
                            <div class="portlet-body">
                                <form method="POST" class="form-inline" style="margin-bottom:15px;">
                                    <input type="hidden" name="form_action" value="add_resolve_reason">
                                    <div class="input-group" style="width:100%;">
                                        <input type="text" class="form-control" name="name" placeholder="Add new reason..." required>
                                        <span class="input-group-btn">
                                            <button class="btn btn-primary" type="submit"><i class="fa fa-plus"></i></button>
                                        </span>
                                    </div>
                                </form>
                                <table class="table table-striped table-condensed">
                                    <thead><tr><th>Name</th><th>Status</th><th></th></tr></thead>
                                    <tbody>
                                        <?php if ($resolve_reasons) { foreach ($resolve_reasons as $item) { ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <?php if ($item['is_active']) { ?>
                                                    <span class="label label-success">Active</span>
                                                <?php } else { ?>
                                                    <span class="label label-default">Inactive</span>
                                                <?php } ?>
                                            </td>
                                            <td>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="form_action" value="toggle_resolve_reason">
                                                    <input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>">
                                                    <input type="hidden" name="name" value="toggle">
                                                    <button type="submit" class="btn btn-xs btn-default" title="Toggle Status"><i class="fa fa-exchange"></i></button>
                                                </form>
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

    <script src="../plugins/jquery.min.js"></script>
    <script src="../plugins/bootstrap/js/bootstrap.min.js"></script>
</body>
</html>
