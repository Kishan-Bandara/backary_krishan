<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('include/database.php');
include('include/check_login.php');

$db = new Database();

// ── Auto-create table if it doesn't exist ─────────────────────────────────────
$db->insertRow("
    CREATE TABLE IF NOT EXISTS `discount_code` (
        `id`          INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        `code`        VARCHAR(50)  NOT NULL,
        `description` VARCHAR(255) NOT NULL DEFAULT '',
        `percentage`  DECIMAL(5,2) NOT NULL DEFAULT '0.00',
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_code` (`code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8
");

// ── Resolve alert message ─────────────────────────────────────────────────────
$alertType    = '';
$alertMessage = '';

if (isset($_GET['success'])) {
    $alertType = 'success';
    switch ($_GET['success']) {
        case 'created': $alertMessage = 'Discount code created successfully.'; break;
        case 'updated': $alertMessage = 'Discount code updated successfully.'; break;
        case 'deleted': $alertMessage = 'Discount code deleted successfully.'; break;
        default:        $alertMessage = 'Operation completed successfully.'; break;
    }
}
if (isset($_GET['error'])) {
    $alertType = 'danger';
    switch ($_GET['error']) {
        case 'missing_fields':  $alertMessage = 'Discount Code and Description are required.'; break;
        case 'duplicate_code':  $alertMessage = 'This Discount Code already exists. Please use a unique code.'; break;
        case 'invalid_id':      $alertMessage = 'Invalid record ID.'; break;
        default:                $alertMessage = 'An error occurred. Please try again.'; break;
    }
}

// ── Fetch all rows ────────────────────────────────────────────────────────────
$discountCodes = $db->getRows('SELECT id, code, description, percentage FROM discount_code ORDER BY code ASC');

// ── Fetch single record for edit modal (server-side prefill) ──────────────────
$editRecord = null;
if (isset($_GET['editID']) && intval($_GET['editID']) > 0) {
    $editRecord = $db->getRow('SELECT id, code, description, percentage FROM discount_code WHERE id = ?', [intval($_GET['editID'])]);
}
?>
<!DOCTYPE html>
<!--[if IE 8]> <html lang="en" class="ie8 no-js"> <![endif]-->
<!--[if IE 9]> <html lang="en" class="ie9 no-js"> <![endif]-->
<!--[if !IE]><!-->
<html lang="en">
<!--<![endif]-->
<head>
    <meta charset="utf-8" />
    <title>Discount Code Maintenance | STOCK MANAGEMENT SYSTEM</title>
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

            <!-- PAGE BAR -->
            <div class="page-bar">
                <ul class="page-breadcrumb">
                    <li><a href="index.php">Home</a><i class="fa fa-circle"></i></li>
                    <li><a href="#">Settings</a><i class="fa fa-circle"></i></li>
                    <li><span>Discount Code Maintenance</span></li>
                </ul>
                <div class="page-toolbar">
                    <div class="btn-group pull-right">
                        <button type="button" class="btn green btn-sm" data-toggle="modal" data-target="#addDiscountModal">
                            <i class="fa fa-plus"></i> Add New Discount Code
                        </button>
                    </div>
                </div>
            </div>

            <!-- Company message bar -->
            <div class="alert <?php echo $MessageClass; ?> alert-dismissable">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                <?php echo $CompanyMessage; ?>
            </div>

            <!-- Alert messages -->
            <?php if ($alertMessage): ?>
            <div class="alert alert-<?php echo $alertType; ?> alert-dismissable">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                <?php echo htmlspecialchars($alertMessage); ?>
            </div>
            <?php endif; ?>

            <!-- MAIN TABLE PORTLET -->
            <div class="row">
                <div class="col-md-12">
                    <div class="portlet light bordered">
                        <div class="portlet-title">
                            <div class="caption font-green">
                                <i class="fa fa-tag font-green"></i>
                                <span class="caption-subject bold uppercase">Discount Code List</span>
                            </div>
                        </div>
                        <div class="portlet-body">
                            <table class="table table-striped table-bordered table-hover dt-responsive" width="100%" id="discount_table">
                                <thead>
                                    <tr>
                                        <th style="width:60px;">#</th>
                                        <th class="all">Discount Code</th>
                                        <th class="all">Description</th>
                                        <th class="all" style="width:160px;">Percentage (%)</th>
                                        <th class="all" style="width:160px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($discountCodes): ?>
                                        <?php foreach ($discountCodes as $row): ?>
                                        <tr>
                                            <td><?php echo (int)$row['id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['code']); ?></td>
                                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                                            <td><?php echo number_format((float)$row['percentage'], 2); ?>%</td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="discount-code.php?editID=<?php echo $row['id']; ?>"
                                                       class="btn btn-xs btn-default" title="Edit">
                                                        <i class="fa fa-pencil"></i> Edit
                                                    </a>
                                                    <button type="button"
                                                        class="btn btn-xs btn-danger btn-delete-discount"
                                                        data-id="<?php echo $row['id']; ?>"
                                                        data-code="<?php echo htmlspecialchars($row['code'], ENT_QUOTES); ?>"
                                                        title="Delete">
                                                        <i class="fa fa-trash"></i> Delete
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No discount codes found. Click "Add New Discount Code" to get started.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- end page-content -->
    </div><!-- end page-content-wrapper -->
</div><!-- end page-container -->

<!-- ═══════════════════════════════════════════════════════════
     ADD MODAL
════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="addDiscountModal" tabindex="-1" role="dialog" aria-labelledby="addDiscountModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="process/discount-code-process.php" method="POST" id="addDiscountForm">
                <input type="hidden" name="action" value="create">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" id="addDiscountModalLabel"><i class="fa fa-plus"></i> Add New Discount Code</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Discount Code <span class="required">*</span></label>
                        <input type="text" name="code" class="form-control" placeholder="e.g. SAVE10" maxlength="50" required>
                    </div>
                    <div class="form-group">
                        <label>Description <span class="required">*</span></label>
                        <input type="text" name="description" class="form-control" placeholder="e.g. 10% off all orders" maxlength="255" required>
                    </div>
                    <div class="form-group">
                        <label>Percentage (%) <span class="required">*</span></label>
                        <input type="number" name="percentage" class="form-control" placeholder="e.g. 10.00" step="0.01" min="0" max="100" value="0.00" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     EDIT MODAL
════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="editDiscountModal" tabindex="-1" role="dialog" aria-labelledby="editDiscountModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="process/discount-code-process.php" method="POST" id="editDiscountForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id" value="">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" id="editDiscountModalLabel"><i class="fa fa-pencil"></i> Edit Discount Code</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Discount Code <span class="required">*</span></label>
                        <input type="text" name="code" id="edit_code" class="form-control" maxlength="50" required>
                    </div>
                    <div class="form-group">
                        <label>Description <span class="required">*</span></label>
                        <input type="text" name="description" id="edit_description" class="form-control" maxlength="255" required>
                    </div>
                    <div class="form-group">
                        <label>Percentage (%) <span class="required">*</span></label>
                        <input type="number" name="percentage" id="edit_percentage" class="form-control" step="0.01" min="0" max="100" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     DELETE MODAL
════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="deleteDiscountModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <form action="process/discount-code-process.php" method="POST" id="deleteDiscountForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete_id" value="">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-trash text-danger"></i> Confirm Delete</h4>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete discount code <strong id="delete_code_label"></strong>?</p>
                    <p class="text-danger"><small>This action cannot be undone.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="fa fa-trash"></i> Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include('common/footer.php'); ?>

<!-- CORE PLUGINS -->
<script src="assets/global/plugins/jquery.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/js.cookie.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/bootstrap-hover-dropdown/bootstrap-hover-dropdown.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/jquery-slimscroll/jquery.slimscroll.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/jquery.blockui.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/uniform/jquery.uniform.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/bootstrap-switch/js/bootstrap-switch.min.js" type="text/javascript"></script>
<!-- PAGE LEVEL PLUGINS -->
<script src="assets/global/scripts/datatable.js" type="text/javascript"></script>
<script src="assets/global/plugins/datatables/datatables.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.js" type="text/javascript"></script>
<!-- THEME SCRIPTS -->
<script src="assets/global/scripts/app.min.js" type="text/javascript"></script>
<script src="assets/pages/scripts/table-datatables-responsive.min.js" type="text/javascript"></script>
<script src="assets/layouts/layout/scripts/layout.min.js" type="text/javascript"></script>
<script src="assets/layouts/layout/scripts/demo.min.js" type="text/javascript"></script>
<script src="assets/layouts/global/scripts/quick-sidebar.min.js" type="text/javascript"></script>

<script>
$(document).ready(function () {

    $('#discount_table').DataTable({
        responsive: true,
        order: [[1, 'asc']],
        columnDefs: [{ orderable: false, targets: [4] }]
    });

    // Server-side prefill edit modal via ?editID=
    <?php if ($editRecord): ?>
    $('#edit_id').val('<?php echo (int)$editRecord['id']; ?>');
    $('#edit_code').val('<?php echo addslashes($editRecord['code']); ?>');
    $('#edit_description').val('<?php echo addslashes($editRecord['description']); ?>');
    $('#edit_percentage').val('<?php echo (float)$editRecord['percentage']; ?>');
    $('#editDiscountModal').modal('show');
    <?php endif; ?>

    // Delete button → populate delete modal
    $(document).on('click', '.btn-delete-discount', function () {
        $('#delete_id').val($(this).data('id'));
        $('#delete_code_label').text($(this).data('code'));
        $('#deleteDiscountModal').modal('show');
    });
});
</script>

</body>
</html>
