<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('include/database.php');
include('include/check_login.php');

$db = new Database();

// ── Resolve alert message ─────────────────────────────────────────────────────
$alertType    = '';
$alertMessage = '';

if (isset($_GET['success'])) {
    $alertType = 'success';
    switch ($_GET['success']) {
        case 'created': $alertMessage = 'Unit of Measure created successfully.'; break;
        case 'updated': $alertMessage = 'Unit of Measure updated successfully.'; break;
        case 'deleted': $alertMessage = 'Unit of Measure deleted successfully.'; break;
        default:        $alertMessage = 'Operation completed successfully.'; break;
    }
}
if (isset($_GET['error'])) {
    $alertType = 'danger';
    switch ($_GET['error']) {
        case 'missing_fields':  $alertMessage = 'UOM Name is required.'; break;
        case 'duplicate_name':  $alertMessage = 'This UOM Name already exists. Please use a unique name.'; break;
        case 'invalid_id':      $alertMessage = 'Invalid record ID.'; break;
        case 'in_use':          $alertMessage = 'Cannot delete: this UOM is assigned to one or more products.'; break;
        default:                $alertMessage = 'An error occurred. Please try again.'; break;
    }
}

// ── Fetch all UOM rows ────────────────────────────────────────────────────────
$uomList = $db->getRows('SELECT uom_id, uom_name FROM item_uom ORDER BY uom_name ASC');

// ── Fetch single record for edit modal ────────────────────────────────────────
$editRecord = null;
if (isset($_GET['editID']) && intval($_GET['editID']) > 0) {
    $editRecord = $db->getRow('SELECT uom_id, uom_name FROM item_uom WHERE uom_id = ?', [intval($_GET['editID'])]);
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
    <title>Unit of Measure Maintenance | STOCK MANAGEMENT SYSTEM</title>
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
                    <li><span>Unit of Measure Maintenance</span></li>
                </ul>
                <div class="page-toolbar">
                    <div class="btn-group pull-right">
                        <button type="button" class="btn green btn-sm" data-toggle="modal" data-target="#addUOMModal">
                            <i class="fa fa-plus"></i> Add New UOM
                        </button>
                    </div>
                </div>
            </div>

            <!-- Company message bar -->
            <div class="alert <?php echo $MessageClass; ?> alert-dismissable">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                <?php echo $CompanyMessage; ?>
            </div>

            <!-- Alert messages from redirect -->
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
                                <i class="fa fa-balance-scale font-green"></i>
                                <span class="caption-subject bold uppercase">Unit of Measure List</span>
                            </div>
                        </div>
                        <div class="portlet-body">
                            <table class="table table-striped table-bordered table-hover dt-responsive" width="100%" id="uom_table">
                                <thead>
                                    <tr>
                                        <th style="width:60px;">#</th>
                                        <th class="all">UOM Name</th>
                                        <th class="all" style="width:160px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($uomList): ?>
                                        <?php foreach ($uomList as $row): ?>
                                        <tr>
                                            <td><?php echo (int)$row['uom_id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['uom_name']); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="uom-maintenance.php?editID=<?php echo $row['uom_id']; ?>"
                                                       class="btn btn-xs btn-default" title="Edit">
                                                        <i class="fa fa-pencil"></i> Edit
                                                    </a>
                                                    <button type="button"
                                                        class="btn btn-xs btn-danger btn-delete-uom"
                                                        data-id="<?php echo $row['uom_id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($row['uom_name'], ENT_QUOTES); ?>"
                                                        title="Delete">
                                                        <i class="fa fa-trash"></i> Delete
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">No units of measure found. Click "Add New UOM" to get started.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <!-- END MAIN TABLE PORTLET -->

        </div><!-- end page-content -->
    </div><!-- end page-content-wrapper -->
</div><!-- end page-container -->

<!-- ═══════════════════════════════════════════════════════════
     ADD MODAL
════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="addUOMModal" tabindex="-1" role="dialog" aria-labelledby="addUOMModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="process/uom-process.php" method="POST" id="addUOMForm">
                <input type="hidden" name="action" value="create">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" id="addUOMModalLabel"><i class="fa fa-plus"></i> Add New Unit of Measure</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>UOM Name <span class="required">*</span></label>
                        <input type="text" name="uom_name" class="form-control" placeholder="e.g. KG, Litre, Piece, Box" maxlength="100" required>
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
<div class="modal fade" id="editUOMModal" tabindex="-1" role="dialog" aria-labelledby="editUOMModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="process/uom-process.php" method="POST" id="editUOMForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="uom_id" id="edit_uom_id" value="">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" id="editUOMModalLabel"><i class="fa fa-pencil"></i> Edit Unit of Measure</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>UOM Name <span class="required">*</span></label>
                        <input type="text" name="uom_name" id="edit_uom_name" class="form-control" maxlength="100" required>
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
<div class="modal fade" id="deleteUOMModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <form action="process/uom-process.php" method="POST" id="deleteUOMForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="uom_id" id="delete_uom_id" value="">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-trash text-danger"></i> Confirm Delete</h4>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete UOM <strong id="delete_uom_label"></strong>?</p>
                    <p class="text-danger"><small>This cannot be undone. UOMs in use by products cannot be deleted.</small></p>
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

    // ── DataTable ──────────────────────────────────────────────
    $('#uom_table').DataTable({
        responsive: true,
        order: [[1, 'asc']],
        columnDefs: [{ orderable: false, targets: [2] }]
    });

    // ── Open Edit Modal via URL param (server-side prefill) ────
    <?php if ($editRecord): ?>
    $('#edit_uom_id').val('<?php echo (int)$editRecord['uom_id']; ?>');
    $('#edit_uom_name').val('<?php echo addslashes($editRecord['uom_name']); ?>');
    $('#editUOMModal').modal('show');
    <?php endif; ?>

    // ── Delete button → populate delete modal ──────────────────
    $(document).on('click', '.btn-delete-uom', function () {
        var id   = $(this).data('id');
        var name = $(this).data('name');
        $('#delete_uom_id').val(id);
        $('#delete_uom_label').text(name);
        $('#deleteUOMModal').modal('show');
    });
});
</script>

</body>
</html>
