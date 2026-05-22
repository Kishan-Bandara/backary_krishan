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

// Toggle status
if (isset($_GET['toggleID'])) {
    $toggleId = (int) $_GET['toggleID'];
    if ($toggleId > 0) {
        try {
            $row = $db->getRow('SELECT is_active FROM delivery_route_groups WHERE id = ? LIMIT 1', [$toggleId]);
            if ($row) {
                $newStatus = ((int) $row['is_active'] === 1) ? 0 : 1;
                $db->updateRow('UPDATE delivery_route_groups SET is_active = ? WHERE id = ?', [$newStatus, $toggleId]);
            }
        } catch (Exception $e) {
            // ignore
        }
    }
    header('Location: manage-delivery-route-groups.php?success=updated');
    exit();
}

// Delete group
if (isset($_GET['deleteID'])) {
    $deleteId = (int) $_GET['deleteID'];
    if ($deleteId > 0) {
        try {
            $db->updateRow('DELETE FROM delivery_route_group_map WHERE group_id = ?', [$deleteId]);
            $db->updateRow('UPDATE customer SET delivery_route_group_id = NULL WHERE delivery_route_group_id = ?', [$deleteId]);
            $db->deleteRow('DELETE FROM delivery_route_groups WHERE id = ?', [$deleteId]);
            header('Location: manage-delivery-route-groups.php?success=deleted');
            exit();
        } catch (Exception $e) {
            $message = 'Unable to delete group: ' . $e->getMessage();
            $messageClass = 'alert-danger';
        }
    }
}

// Add or update group
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_group'])) {
    $editId = (int) ($_POST['group_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $routeIds = $_POST['route_ids'] ?? [];
    if (!is_array($routeIds)) {
        $routeIds = [];
    }

    if ($name === '') {
        $message = 'Group name is required.';
        $messageClass = 'alert-danger';
    } else {
        try {
            if ($editId > 0) {
                $existing = $db->getRow('SELECT id FROM delivery_route_groups WHERE name = ? AND id != ? LIMIT 1', [$name, $editId]);
                if ($existing) {
                    $message = 'Group name already exists.';
                    $messageClass = 'alert-danger';
                } else {
                    $db->updateRow(
                        'UPDATE delivery_route_groups SET name = ?, description = ?, is_active = ? WHERE id = ?',
                        [$name, $description !== '' ? $description : null, $isActive, $editId]
                    );
                    // Sync mapping
                    $db->updateRow('DELETE FROM delivery_route_group_map WHERE group_id = ?', [$editId]);
                    foreach ($routeIds as $rid) {
                        $rid = (int) $rid;
                        if ($rid > 0) {
                            $db->insertRow('INSERT IGNORE INTO delivery_route_group_map (group_id, route_id) VALUES (?, ?)', [$editId, $rid]);
                        }
                    }
                    header('Location: manage-delivery-route-groups.php?success=updated');
                    exit();
                }
            } else {
                $existing = $db->getRow('SELECT id FROM delivery_route_groups WHERE name = ? LIMIT 1', [$name]);
                if ($existing) {
                    $message = 'Group name already exists.';
                    $messageClass = 'alert-danger';
                } else {
                    $db->insertRow(
                        'INSERT INTO delivery_route_groups (name, description, is_active) VALUES (?, ?, ?)',
                        [$name, $description !== '' ? $description : null, $isActive]
                    );
                    $newRow = $db->getRow('SELECT LAST_INSERT_ID() AS id');
                    $newId = (int) ($newRow['id'] ?? 0);
                    if ($newId > 0) {
                        foreach ($routeIds as $rid) {
                            $rid = (int) $rid;
                            if ($rid > 0) {
                                $db->insertRow('INSERT IGNORE INTO delivery_route_group_map (group_id, route_id) VALUES (?, ?)', [$newId, $rid]);
                            }
                        }
                    }
                    header('Location: manage-delivery-route-groups.php?success=added');
                    exit();
                }
            }
        } catch (Exception $e) {
            $message = 'Unable to save group: ' . $e->getMessage();
            $messageClass = 'alert-danger';
        }
    }
}

if (isset($_GET['success'])) {
    if ($_GET['success'] === 'added') {
        $message = 'Delivery route group added successfully.';
    } elseif ($_GET['success'] === 'updated') {
        $message = 'Delivery route group updated successfully.';
    } elseif ($_GET['success'] === 'deleted') {
        $message = 'Delivery route group deleted successfully.';
    }
}

// Editing?
$editGroup = null;
$editRouteIds = [];
if (isset($_GET['editID'])) {
    $editId = (int) $_GET['editID'];
    if ($editId > 0) {
        $editGroup = $db->getRow('SELECT * FROM delivery_route_groups WHERE id = ? LIMIT 1', [$editId]);
        if ($editGroup) {
            $editRouteIds = getDeliveryRouteGroupIdsForRoute(0); // not used; placeholder
            $rows = $db->getRows('SELECT route_id FROM delivery_route_group_map WHERE group_id = ?', [$editId]) ?: [];
            $editRouteIds = array_map(function ($r) { return (int) $r['route_id']; }, $rows);
        }
    }
}

$allRoutes = $db->getRows('SELECT id, route_name, is_active FROM delivery_route_master ORDER BY route_name ASC') ?: [];
$groups = $db->getRows('SELECT * FROM delivery_route_groups ORDER BY name ASC') ?: [];

// Pre-compute mapped route counts for the table
$groupRouteCounts = [];
$mapRows = $db->getRows('SELECT group_id, COUNT(*) AS cnt FROM delivery_route_group_map GROUP BY group_id') ?: [];
foreach ($mapRows as $mr) {
    $groupRouteCounts[(int) $mr['group_id']] = (int) $mr['cnt'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Manage Delivery Route Groups | STOCK MANAGEMENT SYSTEM</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
    <link href="assets/global/plugins/datatables/datatables.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.css" rel="stylesheet" type="text/css" />
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
                    <li><span>Delivery Route Groups</span></li>
                </ul>
            </div>

            <?php if ($message !== ''): ?>
                <div class="alert <?php echo h($messageClass); ?> alert-dismissable">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                    <?php echo h($message); ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-5">
                    <div class="portlet light bordered">
                        <div class="portlet-title">
                            <div class="caption">
                                <i class="icon-tag font-green"></i>
                                <span class="caption-subject font-green bold uppercase">
                                    <?php echo $editGroup ? 'Edit Group' : 'Add Group'; ?>
                                </span>
                            </div>
                        </div>
                        <div class="portlet-body form">
                            <form action="manage-delivery-route-groups.php" method="POST">
                                <input type="hidden" name="group_id" value="<?php echo $editGroup ? (int) $editGroup['id'] : 0; ?>">
                                <div class="form-body">
                                    <div class="form-group">
                                        <label>Name <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="name" required maxlength="100" value="<?php echo h($editGroup['name'] ?? ''); ?>" placeholder="e.g. North Region">
                                    </div>
                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea class="form-control" name="description" rows="2" placeholder="Optional"><?php echo h($editGroup['description'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Routes in this group</label>
                                        <select class="form-control select2" name="route_ids[]" multiple style="width:100%">
                                            <?php foreach ($allRoutes as $r): ?>
                                                <option value="<?php echo (int) $r['id']; ?>" <?php echo in_array((int) $r['id'], $editRouteIds, true) ? 'selected' : ''; ?>>
                                                    <?php echo h($r['route_name']); ?><?php echo ((int) $r['is_active'] === 0) ? ' (inactive)' : ''; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <span class="help-block">Select one or more delivery routes to attach to this group.</span>
                                    </div>
                                    <div class="form-group">
                                        <label class="checkbox-inline">
                                            <input type="checkbox" name="is_active" value="1" <?php echo (!$editGroup || (int) $editGroup['is_active'] === 1) ? 'checked' : ''; ?>>
                                            Active
                                        </label>
                                    </div>
                                </div>
                                <div class="form-actions">
                                    <button type="submit" name="save_group" value="1" class="btn green">
                                        <i class="fa fa-check"></i> <?php echo $editGroup ? 'Update Group' : 'Save Group'; ?>
                                    </button>
                                    <?php if ($editGroup): ?>
                                        <a href="manage-delivery-route-groups.php" class="btn default">Cancel</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-7">
                    <div class="portlet light bordered">
                        <div class="portlet-title">
                            <div class="caption font-green">
                                <i class="icon-list font-green"></i>
                                <span class="caption-subject bold uppercase">Delivery Route Groups</span>
                            </div>
                        </div>
                        <div class="portlet-body">
                            <table class="table table-striped table-bordered table-hover" id="route_groups_table" width="100%">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Routes</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($groups as $g): ?>
                                    <tr>
                                        <td><?php echo (int) $g['id']; ?></td>
                                        <td><?php echo h($g['name']); ?></td>
                                        <td><?php echo h($g['description'] ?? ''); ?></td>
                                        <td><?php echo (int) ($groupRouteCounts[(int) $g['id']] ?? 0); ?></td>
                                        <td>
                                            <?php if ((int) $g['is_active'] === 1): ?>
                                                <span class="label label-success">Active</span>
                                            <?php else: ?>
                                                <span class="label label-default">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="manage-delivery-route-groups.php?editID=<?php echo (int) $g['id']; ?>" class="btn btn-xs btn-default"><i class="fa fa-pencil"></i> Edit</a>
                                            <a href="manage-delivery-route-groups.php?toggleID=<?php echo (int) $g['id']; ?>" class="btn btn-xs btn-warning"><i class="fa fa-toggle-on"></i> Toggle</a>
                                            <a href="manage-delivery-route-groups.php?deleteID=<?php echo (int) $g['id']; ?>" class="btn btn-xs btn-danger" onclick="return confirm('Delete this group? Customers and routes will be detached.');"><i class="fa fa-trash"></i> Delete</a>
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
    </div>
</div>
<?php include('common/footer.php'); ?>
<script src="assets/global/plugins/jquery.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/select2/js/select2.full.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/datatables/datatables.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.js" type="text/javascript"></script>
<script src="assets/global/scripts/app.min.js" type="text/javascript"></script>
<script src="assets/layouts/layout/scripts/layout.min.js" type="text/javascript"></script>
<script>
    jQuery(document).ready(function ($) {
        $('.select2').select2({ placeholder: 'Select routes', allowClear: true });
        if ($.fn.DataTable) {
            $('#route_groups_table').DataTable({ pageLength: 25, order: [[0, 'desc']] });
        }
    });
</script>
</body>
</html>
