<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

session_start();
include('include/database.php');
include('include/check_login.php');

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$message = '';
$MessageClass = '';

try {
    $db = new Database();
} catch (Exception $e) {
    $db = null;
    $message = 'Database connection error: ' . $e->getMessage();
    $MessageClass = 'alert-danger';
}

// Handle Add / Update / Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $desc = trim($_POST['description'] ?? '');
        if ($desc !== '') {
            try {
                $db->insertRow('INSERT INTO price_type (description) VALUES (?)', [$desc]);
                $message = 'Price type added.';
                $MessageClass = 'alert-success';
            } catch (Exception $e) {
                $message = 'Unable to add: ' . $e->getMessage();
                $MessageClass = 'alert-danger';
            }
        } else {
            $message = 'Description cannot be empty.';
            $MessageClass = 'alert-warning';
        }
    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $desc = trim($_POST['description'] ?? '');
        if ($id > 0 && $desc !== '') {
            try {
                $db->updateRow('UPDATE price_type SET description = ? WHERE id = ?', [$desc, $id]);
                $message = 'Price type updated.';
                $MessageClass = 'alert-success';
            } catch (Exception $e) {
                $message = 'Unable to update: ' . $e->getMessage();
                $MessageClass = 'alert-danger';
            }
        } else {
            $message = 'Invalid input for update.';
            $MessageClass = 'alert-warning';
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $db->insertRow('DELETE FROM price_type WHERE id = ?', [$id]);
                $message = 'Price type deleted.';
                $MessageClass = 'alert-success';
            } catch (Exception $e) {
                $message = 'Unable to delete: ' . $e->getMessage();
                $MessageClass = 'alert-danger';
            }
        } else {
            $message = 'Invalid id for delete.';
            $MessageClass = 'alert-warning';
        }
    }

    // After POST, refresh list below
}

$priceTypes = [];
if ($db) {
    try {
        $priceTypes = $db->getRows('SELECT id, description FROM price_type ORDER BY description') ?: [];
    } catch (Exception $e) {
        $priceTypes = [];
        if ($message === '') {
            $message = 'Unable to load price types: ' . $e->getMessage();
            $MessageClass = 'alert-danger';
        }
    }
}

$editRow = null;
if (isset($_GET['edit']) && $db) {
    $eid = (int)$_GET['edit'];
    if ($eid > 0) {
        try {
            $editRow = $db->getRow('SELECT id, description FROM price_type WHERE id = ? LIMIT 1', [$eid]);
        } catch (Exception $e) {
            $editRow = null;
        }
    }
}

// Load products and locations for mapping UI
$products = [];
$locations = [];
if ($db) {
    try {
        $products = $db->getRows('SELECT item_id, item_name FROM item_master ORDER BY item_name ASC') ?: [];
    } catch (Exception $e) {
        $products = [];
    }
    try {
        $locations = $db->getRows('SELECT id, name FROM location_master ORDER BY name') ?: [];
    } catch (Exception $e) {
        $locations = [];
    }
}

// Handle product-price mapping POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db && isset($_POST['mapping_action'])) {
    $mAction = $_POST['mapping_action'];
    if ($mAction === 'add') {
        $productId = (int)($_POST['product_id'] ?? 0);
        $ptypeId = (int)($_POST['price_type_id_map'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);
        $locationId = isset($_POST['location_id']) && $_POST['location_id'] !== '' ? (int)$_POST['location_id'] : null;
        if ($productId > 0 && $ptypeId > 0) {
            try {
                $exists = $db->getRow('SELECT id FROM product_price_mapping WHERE product_id = ? AND price_type_id = ? AND (location_id = ? OR (location_id IS NULL AND ? IS NULL)) LIMIT 1', [$productId, $ptypeId, $locationId, $locationId]);
                if ($exists) {
                    $message = 'Mapping already exists for the selected product/price type and location.'; $MessageClass = 'alert-warning';
                } else {
                    $db->insertRow('INSERT INTO product_price_mapping (product_id, price_type_id, price, location_id) VALUES (?, ?, ?, ?)', [$productId, $ptypeId, $price, $locationId]);
                    $message = 'Mapping added.'; $MessageClass = 'alert-success';
                }
            } catch (Exception $e) {
                $message = 'Unable to add mapping: ' . $e->getMessage(); $MessageClass = 'alert-danger';
            }
        } else {
            $message = 'Select product and price type.'; $MessageClass = 'alert-warning';
        }
    } elseif ($mAction === 'update') {
        $mapId = (int)($_POST['map_id'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);
        if ($mapId > 0) {
            try {
                $db->updateRow('UPDATE product_price_mapping SET price = ? WHERE id = ?', [$price, $mapId]);
                $message = 'Mapping updated.'; $MessageClass = 'alert-success';
            } catch (Exception $e) {
                $message = 'Unable to update mapping: ' . $e->getMessage(); $MessageClass = 'alert-danger';
            }
        }
    } elseif ($mAction === 'delete') {
        $mapId = (int)($_POST['map_id'] ?? 0);
        if ($mapId > 0) {
            try {
                $db->insertRow('DELETE FROM product_price_mapping WHERE id = ?', [$mapId]);
                $message = 'Mapping deleted.'; $MessageClass = 'alert-success';
            } catch (Exception $e) {
                $message = 'Unable to delete mapping: ' . $e->getMessage(); $MessageClass = 'alert-danger';
            }
        }
    }

    // reload mappings below
}

// Load existing mappings (join product and price type)
$mappings = [];
if ($db) {
    try {
        $mappings = $db->getRows('SELECT ppm.id, ppm.product_id, ppm.price_type_id, ppm.price, im.item_name, pt.description AS price_type_desc FROM product_price_mapping ppm LEFT JOIN item_master im ON ppm.product_id = im.item_id LEFT JOIN price_type pt ON ppm.price_type_id = pt.id ORDER BY im.item_name ASC') ?: [];
    } catch (Exception $e) {
        $mappings = [];
    }
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Price Types</title>
    <?php include('common/head.php'); ?>
    <style>
        .section-card { padding: 18px; margin-bottom: 18px; }
        .info-row { display:flex; justify-content:space-between; padding:8px 0; }
    </style>
</head>
<body class="page-sidebar-closed-hide-logo page-content-white">
    <?php include('common/manubar.php'); ?>
    <div class="clearfix"></div>
    <div class="page-container">
        <?php include('common/sidebar.php'); ?>
        <div class="page-content-wrapper">
            <div class="page-content">
                <div class="page-bar">
                    <ul class="page-breadcrumb">
                        <li><a href="index.php">Home</a><i class="fa fa-circle"></i></li>
                        <li><span>Price Types</span></li>
                    </ul>
                </div>

                <?php if ($message !== ''): ?>
                    <div class="alert <?php echo h($MessageClass ?: 'alert-info'); ?> alert-dismissable">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                        <?php echo nl2br(h($message)); ?>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6">
                        <div class="section-card">
                            <h4>Existing Price Types</h4>
                            <?php if (empty($priceTypes)): ?>
                                <p class="muted">No price types defined.</p>
                            <?php else: ?>
                                <table class="table table-striped">
                                    <thead><tr><th>ID</th><th>Description</th><th></th></tr></thead>
                                    <tbody>
                                        <?php foreach ($priceTypes as $pt): ?>
                                            <tr>
                                                <td><?php echo (int)$pt['id']; ?></td>
                                                <td><?php echo h($pt['description']); ?></td>
                                                <td style="text-align:right;">
                                                    <a class="btn btn-xs btn-default" href="price_types.php?edit=<?php echo (int)$pt['id']; ?>">Edit</a>
                                                    <form method="post" style="display:inline-block; margin-left:6px;" onsubmit="return confirm('Delete this price type?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?php echo (int)$pt['id']; ?>">
                                                        <button type="submit" class="btn btn-xs btn-danger">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="section-card">
                            <h4><?php echo $editRow ? 'Edit Price Type' : 'Add Price Type'; ?></h4>
                            <form method="post">
                                <?php if ($editRow): ?>
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="id" value="<?php echo (int)$editRow['id']; ?>">
                                <?php else: ?>
                                    <input type="hidden" name="action" value="add">
                                <?php endif; ?>

                                <div class="form-group">
                                    <label>Description</label>
                                    <input type="text" name="description" class="form-control" value="<?php echo $editRow ? h($editRow['description']) : ''; ?>">
                                </div>
                                <div>
                                    <button class="btn btn-primary" type="submit"><?php echo $editRow ? 'Update' : 'Add'; ?></button>
                                    <?php if ($editRow): ?><a href="price_types.php" class="btn btn-default">Cancel</a><?php endif; ?>
                                </div>
                            </form>
                        </div>

                        <div class="section-card" style="margin-top:12px;">
                            <h4>Product - Price Type Mapping</h4>
                            <p class="muted">Manage mappings on the dedicated page: <a href="product_price_mapping.php">Product Price Mappings</a></p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <?php include('common/footer.php'); ?>
     <!-- BEGIN CORE PLUGINS -->
        <script src="assets/global/plugins/jquery.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/js.cookie.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/bootstrap-hover-dropdown/bootstrap-hover-dropdown.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/jquery-slimscroll/jquery.slimscroll.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/jquery.blockui.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/uniform/jquery.uniform.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/bootstrap-switch/js/bootstrap-switch.min.js" type="text/javascript"></script>
        <!-- END CORE PLUGINS -->
        <!-- BEGIN PAGE LEVEL PLUGINS -->
        <script src="assets/global/scripts/datatable.js" type="text/javascript"></script>
        <script src="assets/global/plugins/datatables/datatables.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.js" type="text/javascript"></script>
        <!-- END PAGE LEVEL PLUGINS -->
        <!-- BEGIN THEME GLOBAL SCRIPTS -->
        <script src="assets/global/scripts/app.min.js" type="text/javascript"></script>
        <!-- END THEME GLOBAL SCRIPTS -->
        <!-- BEGIN PAGE LEVEL SCRIPTS -->
        <script src="assets/pages/scripts/table-datatables-responsive.min.js" type="text/javascript"></script>
        <!-- END PAGE LEVEL SCRIPTS -->
        <!-- BEGIN THEME LAYOUT SCRIPTS -->
        <script src="assets/layouts/layout/scripts/layout.min.js" type="text/javascript"></script>
        <script src="assets/layouts/layout/scripts/demo.min.js" type="text/javascript"></script>
        <script src="assets/layouts/global/scripts/quick-sidebar.min.js" type="text/javascript"></script>
        <!-- END THEME LAYOUT SCRIPTS -->
<!-- Dependencies for Select2 -->
<script src="assets/global/plugins/select2/select2.full.min.js"></script>

</body>
</html>



