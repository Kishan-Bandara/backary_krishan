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

// Handle POST actions: add, update, delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    if ($action === 'add') {
        $price_type_id = intval($_POST['price_type_id'] ?? 0);
        $customer_id = intval($_POST['customer_id'] ?? 0);
        if ($price_type_id && $customer_id) {
            try {
                $db->insertRow('INSERT INTO price_type_customer_mapping (price_type_id, customer_id) VALUES (?, ?)', [$price_type_id, $customer_id]);
                $message = 'Mapping added.';
                $MessageClass = 'alert-success';
            } catch (Exception $e) {
                $message = 'Error adding mapping: ' . $e->getMessage();
                $MessageClass = 'alert-danger';
            }
        } else {
            $message = 'Please select both Price Type and Customer.';
            $MessageClass = 'alert-warning';
        }
    }

    if ($action === 'update') {
        $id = intval($_POST['id'] ?? 0);
        $price_type_id = intval($_POST['price_type_id'] ?? 0);
        if ($id && $price_type_id) {
            try {
                $db->updateRow('UPDATE price_type_customer_mapping SET price_type_id = ? WHERE id = ?', [$price_type_id, $id]);
                $message = 'Mapping updated.';
                $MessageClass = 'alert-success';
            } catch (Exception $e) {
                $message = 'Error updating mapping: ' . $e->getMessage();
                $MessageClass = 'alert-danger';
            }
        } else {
            $message = 'Missing required fields for update.';
            $MessageClass = 'alert-warning';
        }
    }

    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id) {
            try {
                $db->insertRow('DELETE FROM price_type_customer_mapping WHERE id = ?', [$id]);
                $message = 'Mapping deleted.';
                $MessageClass = 'alert-success';
            } catch (Exception $e) {
                $message = 'Error deleting mapping: ' . $e->getMessage();
                $MessageClass = 'alert-danger';
            }
        } else {
            $message = 'Missing id for delete.';
            $MessageClass = 'alert-warning';
        }
    }
}

$q = trim($_GET['q'] ?? '');
$params = [];
$where = '';
if ($q !== '') {
    $where = "WHERE (c.customer_name LIKE CONCAT('%', ?, '%') OR pt.description LIKE CONCAT('%', ?, '%'))";
    $params = [$q, $q];
}

$sql = "SELECT pcm.id, pcm.price_type_id, pcm.customer_id, pt.description AS price_type, c.customer_name
        FROM price_type_customer_mapping pcm
        LEFT JOIN price_type pt ON pt.id = pcm.price_type_id
        LEFT JOIN customer c ON c.customer_id = pcm.customer_id
        $where
        ORDER BY c.customer_name, pt.description";

$mappings = [];
$priceTypes = [];
$customers = [];
if ($db) {
    try {
        $mappings = $db->getRows($sql, $params) ?: [];
    } catch (Exception $e) {
        if ($message === '') { $message = 'Unable to load mappings: ' . $e->getMessage(); $MessageClass = 'alert-danger'; }
    }

    try {
        $priceTypes = $db->getRows('SELECT id, description FROM price_type ORDER BY description') ?: [];
    } catch (Exception $e) {
        // ignore
    }

    try {
        $customers = $db->getRows('SELECT customer_id AS id, customer_name FROM customer ORDER BY customer_name') ?: [];
    } catch (Exception $e) {
        // ignore
    }
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Price Type -> Customer Mapping</title>
    <?php include('common/head.php'); ?>
    <!-- Select2 CSS (ensure this file exists in your assets) -->
    <link href="assets/global/plugins/select2/select2.min.css" rel="stylesheet" />
    <style>
        .section-card { padding: 18px; margin-bottom: 18px; }
        .small-input { max-width: 220px; display: inline-block; }
        table td { vertical-align: middle; }
        .mapping-card .card-header { background: linear-gradient(90deg,#4e73df,#1cc88a); color:#fff; }
        .mapping-card .card-body { padding: 1.25rem; }
        .mapping-btn { width:100%; }
        /* ensure select2 matches form-control height */
        .select2-container .select2-selection--single { height: calc(2.25rem + 2px); }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 2.25rem; }
        /* Keep search input and buttons on one line */
        .input-group {
            display: flex !important;
            flex-wrap: nowrap !important;
            align-items: stretch;
                margin-bottom: 18px;
                background: #f8f9fc;
                border-radius: 6px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.04);
                padding: 10px 16px;
        }
        .input-group .form-control {
              min-width: 0; /* allow flex to shrink on small widths */
              flex: 1 1 auto;
              border-radius: 4px;
              border: 1px solid #d1d3e2;
              background: #fff;
              max-width: 340px;
        }
        .input-group-prepend, .input-group-append {
            flex: 0 0 auto;
        }
        .input-group-append .btn, .input-group-prepend .input-group-text { white-space: nowrap; }
            .input-group-addon {
                background: #e2e6ea;
                border-radius: 4px 0 0 4px;
                border: 1px solid #d1d3e2;
                color: #4e73df;
                font-weight: bold;
                display: flex;
                align-items: center;
                padding: 0 12px;
            }
            .input-group-btn .btn {
                margin-left: 6px;
                border-radius: 4px;
            }
            #search-q:focus {
                box-shadow: 0 0 0 2px #4e73df33;
                border-color: #4e73df;
            }
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
                        <li><span>Price Type Customer Mapping</span></li>
                    </ul>
                </div>

                <?php if ($message !== ''): ?>
                    <div class="alert <?php echo h($MessageClass ?: 'alert-info'); ?> alert-dismissable">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                        <?php echo nl2br(h($message)); ?>
                    </div>
                <?php endif; ?>

                <h3 class="page-title">Price Type &rarr; Customer Mapping</h3>

                    <div class="card section-card mapping-card">
                        <div class="card-body">
                            <!-- Search row (single-row input + buttons) -->
                                <form method="get" class="mb-3">
                                    <div class="input-group" id="search-group">
                                        <span class="input-group-addon"><i class="fa fa-search"></i></span>
                                        <input id="search-q" type="text" name="q" class="form-control" placeholder="Search customer or price type" value="<?php echo htmlspecialchars($q); ?>">
                                        <span class="input-group-btn">
                                            <button class="btn btn-primary" type="submit"><i class="fa fa-search"></i> Search</button>
                                            <button id="clear-search" class="btn btn-default" type="button">Clear</button>
                                        </span>
                                    </div>
                                </form>

                            <!-- Add mapping row (single-line: price type + customer + map) -->
                            <form method="post" class="mb-0">
                                <input type="hidden" name="action" value="add">
                                <div class="form-row align-items-center">
                                    <div class="col-md-4 mb-2">
                                        <select name="price_type_id" class="form-control select2">
                                            <option value="">-- Select Price Type --</option>
                                            <?php foreach ($priceTypes as $pt): ?>
                                                <option value="<?php echo $pt['id']; ?>"><?php echo htmlspecialchars($pt['description']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <select name="customer_id" class="form-control select2">
                                            <option value="">-- Select Customer --</option>
                                            <?php foreach ($customers as $c): ?>
                                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['customer_name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <button class="btn btn-success mapping-btn" type="submit"><i class="fa fa-link"></i> Map</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Existing Mappings</h5>
                            <table id="mappings-table" class="table table-striped table-bordered">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Customer</th>
                                    <th>Price Type</th>
                                    <th style="width:220px">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($mappings)): ?>
                                    <tr><td colspan="4" class="text-center">No mappings found.</td></tr>
                                <?php else: foreach ($mappings as $map): ?>
                                    <tr>
                                        <td><?php echo $map['id']; ?></td>
                                        <td><?php echo htmlspecialchars($map['customer_name'] ?? ''); ?></td>
                                        <td>
                                            <form method="post" class="form-inline">
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="id" value="<?php echo $map['id']; ?>">
                                                <select name="price_type_id" class="form-control small-input">
                                                    <?php foreach ($priceTypes as $pt): ?>
                                                        <option value="<?php echo $pt['id']; ?>" <?php echo ($pt['id'] == $map['price_type_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($pt['description']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button class="btn btn-sm btn-primary ml-2">Save</button>
                                            </form>
                                        </td>
                                        <td>
                                            <form method="post" onsubmit="return confirm('Delete this mapping?');" style="display:inline-block;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $map['id']; ?>">
                                                <button class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                                </tbody>
                            </table>
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

<script>
    // Initialize Select2 and small UI tweaks
    (function(){
        try {
            if (window.jQuery) {
                jQuery(function($){
                    $('.select2').select2({ width: '100%', placeholder: 'Select...' });
                    // improve small input spacing if needed
                    $('.small-input').css('min-width','180px');
                    // clear search button
                    $('#clear-search').on('click', function(){
                        $('#search-q').val('');
                        window.location.href = 'price_type_customer_mapping.php';
                    });
                    // init DataTable for mappings
                    if ($.fn.DataTable) {
                        $('#mappings-table').DataTable({
                            responsive: true,
                            pageLength: 10,
                            lengthChange: false,
                            ordering: true
                        });
                    }
                });
            }
        } catch (e) { console && console.log && console.log(e); }
    })();
</script>
</body>
</html>



