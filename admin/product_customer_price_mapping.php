<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

session_start();
include('include/database.php');
include('include/check_login.php');

function h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

$message = '';
$MessageClass = '';

try { $db = new Database();
    // Ensure product_customer_price_mapping table exists. Create minimal table if missing.
    $has_pcm = (bool) $db->getRow("SHOW TABLES LIKE 'product_customer_price_mapping'");
    if (!$has_pcm) {
        try {
            $db->insertRow(
                "CREATE TABLE IF NOT EXISTS product_customer_price_mapping (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    product_id INT NOT NULL,
                    customer_id INT NOT NULL,
                    price DECIMAL(22,2) NOT NULL DEFAULT 0.00,
                    UNIQUE KEY product_customer_unique (product_id, customer_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
            $message = 'Created missing table `product_customer_price_mapping`.'; $MessageClass = 'alert-success';
        } catch (Exception $e) {
            $message = 'product_customer_price_mapping table missing and could not be created: ' . $e->getMessage(); $MessageClass = 'alert-warning';
        }
    }
} catch (Exception $e) { $db = null; $message = 'Database error: '.$e->getMessage(); $MessageClass='alert-danger'; }

// handle POST actions for add/update/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db && isset($_POST['action'])) {
    $act = $_POST['action'];
    if ($act === 'add') {
        $productId = (int)($_POST['product_id'] ?? 0);
        $customerId = (int)($_POST['customer_id'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);
        if ($productId > 0 && $customerId > 0) {
            try {
                $db->insertRow('INSERT INTO product_customer_price_mapping (product_id, customer_id, price) VALUES (?, ?, ?)', [$productId, $customerId, $price]);
                $message = 'Mapping added.'; $MessageClass = 'alert-success';
            } catch (Exception $e) { $message = 'Unable to add mapping: '.$e->getMessage(); $MessageClass='alert-danger'; }
        } else { $message = 'Select product and customer.'; $MessageClass='alert-warning'; }
    } elseif ($act === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);
        if ($id > 0) {
            try { $db->updateRow('UPDATE product_customer_price_mapping SET price = ? WHERE id = ?', [$price, $id]); $message='Mapping updated.'; $MessageClass='alert-success'; } catch (Exception $e) { $message='Unable to update: '.$e->getMessage(); $MessageClass='alert-danger'; }
        }
    } elseif ($act === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try { $db->insertRow('DELETE FROM product_customer_price_mapping WHERE id = ?', [$id]); $message='Mapping deleted.'; $MessageClass='alert-success'; } catch (Exception $e) { $message='Unable to delete: '.$e->getMessage(); $MessageClass='alert-danger'; }
        }
    }
}

// load lists for UI
$products = [];
$customers = [];
$mappings = [];
$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
if ($db) {
    try { $products = $db->getRows('SELECT item_id, item_name FROM item_master ORDER BY item_name ASC') ?: []; } catch (Exception $e) { $products = []; }
    try { $customers = $db->getRows('SELECT customer_id, customer_name FROM customer ORDER BY customer_name ASC') ?: []; } catch (Exception $e) { $customers = []; }
    try {
        if ($q !== '') {
            $like = '%'.str_replace('%','\\%',$q).'%';
            $mappings = $db->getRows('SELECT pcm.id, pcm.product_id, pcm.customer_id, pcm.price, im.item_name, c.customer_name FROM product_customer_price_mapping pcm LEFT JOIN item_master im ON pcm.product_id = im.item_id LEFT JOIN customer c ON pcm.customer_id = c.customer_id WHERE im.item_name LIKE ? OR c.customer_name LIKE ? ORDER BY im.item_name ASC', [$like, $like]) ?: [];
        } else {
            $mappings = $db->getRows('SELECT pcm.id, pcm.product_id, pcm.customer_id, pcm.price, im.item_name, c.customer_name FROM product_customer_price_mapping pcm LEFT JOIN item_master im ON pcm.product_id = im.item_id LEFT JOIN customer c ON pcm.customer_id = c.customer_id ORDER BY im.item_name ASC') ?: [];
        }
    } catch (Exception $e) { $mappings = []; }
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Product - Customer Price Mapping</title>
    <?php include('common/head.php'); ?>
    <style>
        .section-card { padding: 14px; margin-bottom: 14px; }
    </style>
</head>
<body class="page-sidebar-closed-hide-logo page-content-white">
    <?php include('common/manubar.php'); ?>
    <div class="clearfix"></div>
    <div class="page-container">
        <?php include('common/sidebar.php'); ?>
        <div class="page-content-wrapper">
            <div class="page-content">
                <div class="page-bar"><ul class="page-breadcrumb"><li><a href="index.php">Home</a><i class="fa fa-circle"></i></li><li><span>Product-Customer Price Mapping</span></li></ul></div>

                <?php if ($message !== ''): ?>
                    <div class="alert <?php echo h($MessageClass ?: 'alert-info'); ?> alert-dismissable">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                        <?php echo nl2br(h($message)); ?>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-12">
                        <div class="section-card">
                            <h4>Manage Product - Customer Price Overrides</h4>

                            <form method="get" class="form-inline" style="margin-bottom:8px; display:inline-block;">
                                <div class="form-group"><input type="text" name="q" class="form-control input-sm" placeholder="Search product or customer" value="<?php echo h($q); ?>"></div>
                                <button class="btn btn-default btn-sm" type="submit">Search</button>
                                <?php if ($q !== ''): ?><a href="product_customer_price_mapping.php" class="btn btn-link btn-sm">Clear</a><?php endif; ?>
                            </form>

                            <form method="post" class="form-inline" style="margin-top:10px; margin-bottom:12px;">
                                <input type="hidden" name="action" value="add">
                                <div class="form-group" style="margin-right:6px;">
                                    <select name="product_id" class="form-control">
                                        <option value="">-- Product --</option>
                                        <?php foreach ($products as $p): ?>
                                            <option value="<?php echo (int)$p['item_id']; ?>"><?php echo h($p['item_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group" style="margin-right:6px;">
                                    <select name="customer_id" class="form-control">
                                        <option value="">-- Customer --</option>
                                        <?php foreach ($customers as $c): ?>
                                            <option value="<?php echo (int)$c['customer_id']; ?>"><?php echo h($c['customer_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group" style="margin-right:6px;"><input type="text" name="price" class="form-control" placeholder="Price"></div>
                                <button class="btn btn-success" type="submit">Add Override</button>
                            </form>

                            <?php if (empty($mappings)): ?>
                                <p class="muted">No overrides defined.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead><tr><th>Product</th><th>Customer</th><th>Price</th><th></th></tr></thead>
                                        <tbody>
                                            <?php foreach ($mappings as $m): ?>
                                                <tr>
                                                    <td><?php echo h($m['item_name'] ?: 'ID:'.$m['product_id']); ?></td>
                                                    <td><?php echo h($m['customer_name'] ?: 'ID:'.$m['customer_id']); ?></td>
                                                    <td><?php echo number_format((float)$m['price'],2); ?></td>
                                                    <td style="text-align:right;">
                                                        <form method="post" style="display:inline-block; margin-right:6px;">
                                                            <input type="hidden" name="action" value="update">
                                                            <input type="hidden" name="id" value="<?php echo (int)$m['id']; ?>">
                                                            <input type="text" name="price" value="<?php echo h(number_format((float)$m['price'],2,'.','')); ?>" class="form-control input-sm" style="width:90px; display:inline-block;">
                                                            <button type="submit" class="btn btn-sm btn-primary" style="margin-left:6px;">Save</button>
                                                        </form>
                                                        <form method="post" style="display:inline-block;" onsubmit="return confirm('Delete this override?');">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="id" value="<?php echo (int)$m['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                                        </form>
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
</body>
</html>



