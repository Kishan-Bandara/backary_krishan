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

// Handle mapping POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db && isset($_POST['mapping_action'])) {
    $sessionLocation = (int)($_SESSION['location'] ?? 0);
    $isSuper = isSuperAdmin();

    $mAction = $_POST['mapping_action'];
    if ($mAction === 'add') {
        $productId = (int)($_POST['product_id'] ?? 0);
        $ptypeId = (int)($_POST['price_type_id'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);
        $locationId = isset($_POST['location_id']) && $_POST['location_id'] !== '' ? (int)$_POST['location_id'] : null;
        if ($productId > 0 && $ptypeId > 0) {
            try {
                // Non-super users may only add mappings for their own location (cannot add global mappings)
                if (!$isSuper) {
                    if ($locationId === null) {
                        $message = 'You are not allowed to add a global mapping. Select your location.'; $MessageClass = 'alert-warning';
                    } elseif ($locationId !== $sessionLocation) {
                        $message = 'You can only add mappings for your own location.'; $MessageClass = 'alert-warning';
                    } else {
                        // proceed
                        $exists = $db->getRow('SELECT id FROM product_price_mapping WHERE product_id = ? AND price_type_id = ? AND location_id = ? LIMIT 1', [$productId, $ptypeId, $locationId]);
                        if ($exists) {
                            $message = 'Mapping already exists for the selected product/price type and location.'; $MessageClass = 'alert-warning';
                        } else {
                            $db->insertRow('INSERT INTO product_price_mapping (product_id, price_type_id, price, location_id) VALUES (?, ?, ?, ?)', [$productId, $ptypeId, $price, $locationId]);
                            $message = 'Mapping added.'; $MessageClass = 'alert-success';
                        }
                    }
                } else {
                    // Super admin may add mappings including global
                    $exists = $db->getRow('SELECT id FROM product_price_mapping WHERE product_id = ? AND price_type_id = ? AND (location_id = ? OR (location_id IS NULL AND ? IS NULL)) LIMIT 1', [$productId, $ptypeId, $locationId, $locationId]);
                    if ($exists) {
                        $message = 'Mapping already exists for the selected product/price type and location.'; $MessageClass = 'alert-warning';
                    } else {
                        $db->insertRow('INSERT INTO product_price_mapping (product_id, price_type_id, price, location_id) VALUES (?, ?, ?, ?)', [$productId, $ptypeId, $price, $locationId]);
                        $message = 'Mapping added.'; $MessageClass = 'alert-success';
                    }
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
                $mapRow = $db->getRow('SELECT location_id FROM product_price_mapping WHERE id = ? LIMIT 1', [$mapId]);
                $mapLocation = $mapRow ? $mapRow['location_id'] : null;
                if (!$isSuper) {
                    if ($mapLocation === null || (int)$mapLocation !== $sessionLocation) {
                        $message = 'You are not allowed to update this mapping.'; $MessageClass = 'alert-warning';
                    } else {
                        $db->updateRow('UPDATE product_price_mapping SET price = ? WHERE id = ?', [$price, $mapId]);
                        $message = 'Mapping updated.'; $MessageClass = 'alert-success';
                    }
                } else {
                    $db->updateRow('UPDATE product_price_mapping SET price = ? WHERE id = ?', [$price, $mapId]);
                    $message = 'Mapping updated.'; $MessageClass = 'alert-success';
                }
            } catch (Exception $e) {
                $message = 'Unable to update mapping: ' . $e->getMessage(); $MessageClass = 'alert-danger';
            }
        }
    } elseif ($mAction === 'delete') {
        $mapId = (int)($_POST['map_id'] ?? 0);
        if ($mapId > 0) {
            try {
                $mapRow = $db->getRow('SELECT location_id FROM product_price_mapping WHERE id = ? LIMIT 1', [$mapId]);
                $mapLocation = $mapRow ? $mapRow['location_id'] : null;
                if (!$isSuper) {
                    if ($mapLocation === null || (int)$mapLocation !== $sessionLocation) {
                        $message = 'You are not allowed to delete this mapping.'; $MessageClass = 'alert-warning';
                    } else {
                        $db->insertRow('DELETE FROM product_price_mapping WHERE id = ?', [$mapId]);
                        $message = 'Mapping deleted.'; $MessageClass = 'alert-success';
                    }
                } else {
                    $db->insertRow('DELETE FROM product_price_mapping WHERE id = ?', [$mapId]);
                    $message = 'Mapping deleted.'; $MessageClass = 'alert-success';
                }
            } catch (Exception $e) {
                $message = 'Unable to delete mapping: ' . $e->getMessage(); $MessageClass = 'alert-danger';
            }
        }
    }
}

// Load products, price types and locations
$products = [];
$priceTypes = [];
$locations = [];
if ($db) {
    try { $products = $db->getRows('SELECT item_id, item_name FROM item_master ORDER BY item_name ASC') ?: []; } catch (Exception $e) { $products = []; }
    try { $priceTypes = $db->getRows('SELECT id, description FROM price_type ORDER BY description') ?: []; } catch (Exception $e) { $priceTypes = []; }
    try { $locations = $db->getRows('SELECT id, name FROM location_master ORDER BY name') ?: []; } catch (Exception $e) { $locations = []; }
}

// Load existing mappings
$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

$mappings = [];
if ($db) {
    try {
        if ($q !== '') {
            $like = '%' . str_replace('%', '\\%', $q) . '%';
            $mappings = $db->getRows(
                'SELECT ppm.id, ppm.product_id, ppm.price_type_id, ppm.price, ppm.location_id, im.item_name, pt.description AS price_type_desc, lm.name AS location_name FROM product_price_mapping ppm LEFT JOIN item_master im ON ppm.product_id = im.item_id LEFT JOIN price_type pt ON ppm.price_type_id = pt.id LEFT JOIN location_master lm ON ppm.location_id = lm.id WHERE im.item_name LIKE ? OR pt.description LIKE ? ORDER BY im.item_name ASC',
                [$like, $like]
            ) ?: [];
        } else {
            $mappings = $db->getRows('SELECT ppm.id, ppm.product_id, ppm.price_type_id, ppm.price, ppm.location_id, im.item_name, pt.description AS price_type_desc, lm.name AS location_name FROM product_price_mapping ppm LEFT JOIN item_master im ON ppm.product_id = im.item_id LEFT JOIN price_type pt ON ppm.price_type_id = pt.id LEFT JOIN location_master lm ON ppm.location_id = lm.id ORDER BY im.item_name ASC') ?: [];
        }
    } catch (Exception $e) {
        $mappings = [];
    }
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Product Price Mappings</title>
    <?php include('common/head.php'); ?>
    <style>
        .section-card { padding: 16px; margin-bottom: 16px; }
    </style>
</head>
<body class="page-sidebar-closed-hide-logo page-content-white">
    <?php include('common/manubar.php'); ?>
    <div class="clearfix"></div>
    <div class="page-container">
        <?php include('common/sidebar.php'); ?>
        <div class="page-content-wrapper">
            <div class="page-content">
                <div class="page-bar"><ul class="page-breadcrumb"><li><a href="index.php">Home</a><i class="fa fa-circle"></i></li><li><span>Product Price Mapping</span></li></ul></div>

                <?php if ($message !== ''): ?>
                    <div class="alert <?php echo h($MessageClass ?: 'alert-info'); ?> alert-dismissable">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                        <?php echo nl2br(h($message)); ?>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-12">
                        <div class="section-card">
                            <h4>Manage Product - Price Type Mappings</h4>

                            <form method="get" class="form-inline" style="margin-bottom:8px; margin-right:6px; display:inline-block;">
                                <div class="form-group">
                                    <input type="text" name="q" class="form-control input-sm" placeholder="Search product or price type" value="<?php echo h($q); ?>">
                                </div>
                                <button type="submit" class="btn btn-default btn-sm">Search</button>
                                <?php if ($q !== ''): ?>
                                    <a href="product_price_mapping.php" class="btn btn-link btn-sm">Clear</a>
                                <?php endif; ?>
                            </form>

                            <form method="post" class="form-inline" style="margin-bottom:12px;">
                                <input type="hidden" name="mapping_action" value="add">
                                <div class="form-group" style="margin-right:6px;">
                                    <select name="product_id" class="form-control">
                                        <option value="">-- Select Product --</option>
                                        <?php foreach ($products as $p): ?>
                                            <option value="<?php echo (int)$p['item_id']; ?>"><?php echo h($p['item_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group" style="margin-right:6px;">
                                    <select name="price_type_id" class="form-control">
                                        <option value="">-- Select Price Type --</option>
                                        <?php foreach ($priceTypes as $pt): ?>
                                            <option value="<?php echo (int)$pt['id']; ?>"><?php echo h($pt['description']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group" style="margin-right:6px;">
                                    <?php if (isSuperAdmin()): ?>
                                    <select name="location_id" class="form-control">
                                        <option value="">-- All Locations --</option>
                                        <?php foreach ($locations as $loc): ?>
                                            <option value="<?php echo (int)$loc['id']; ?>"><?php echo h($loc['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php else: ?>
                                    <?php $sessLoc = (int)($_SESSION['location'] ?? 0); $locRow = null; foreach ($locations as $loc) { if ((int)$loc['id'] === $sessLoc) { $locRow = $loc; break; } } ?>
                                    <?php if ($locRow): ?>
                                        <input type="hidden" name="location_id" value="<?php echo (int)$locRow['id']; ?>">
                                        <input type="text" class="form-control" value="<?php echo h($locRow['name']); ?>" disabled>
                                    <?php else: ?>
                                        <input type="hidden" name="location_id" value="<?php echo $sessLoc; ?>">
                                        <input type="text" class="form-control" value="Location <?php echo $sessLoc; ?>" disabled>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="form-group" style="margin-right:6px;">
                                    <input type="text" name="price" class="form-control" placeholder="Price">
                                </div>
                                <button type="submit" class="btn btn-success">Add Mapping</button>
                            </form>

                            <?php if (empty($mappings)): ?>
                                <p class="muted">No mappings defined.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead><tr><th>Product</th><th>Price Type</th><th>Price</th><th>Location</th><th></th></tr></thead>
                                    <tbody>
                                        <?php foreach ($mappings as $map): ?>
                                            <tr>
                                                <td><?php echo h($map['item_name'] ?: 'ID:'.$map['product_id']); ?></td>
                                                <td><?php echo h($map['price_type_desc'] ?: 'ID:'.$map['price_type_id']); ?></td>
                                                <td><?php echo number_format((float)$map['price'],2); ?></td>
                                                <td><?php echo h($map['location_name'] ?? 'All Locations'); ?></td>
                                                <td style="text-align:right;">
                                                    <?php $canModify = isSuperAdmin() || ((int)$map['location_id'] === (int)($_SESSION['location'] ?? 0)); ?>
                                                    <?php if ($canModify): ?>
                                                    <form method="post" style="display:inline-block; margin-right:6px;">
                                                        <input type="hidden" name="mapping_action" value="update">
                                                        <input type="hidden" name="map_id" value="<?php echo (int)$map['id']; ?>">
                                                        <input type="text" name="price" value="<?php echo h(number_format((float)$map['price'],2,'.','')); ?>" style="width:90px; display:inline-block;" class="form-control input-sm">
                                                        <button type="submit" class="btn btn-sm btn-primary" style="margin-left:6px;">Save</button>
                                                    </form>
                                                    <form method="post" style="display:inline-block;" onsubmit="return confirm('Delete this mapping?');">
                                                        <input type="hidden" name="mapping_action" value="delete">
                                                        <input type="hidden" name="map_id" value="<?php echo (int)$map['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                                    </form>
                                                    <?php else: ?>
                                                        <span class="text-muted">No permission</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                </div>
                            <?php endif; ?>
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



