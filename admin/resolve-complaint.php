<?php 
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');
include('get_url.php');

$db = new Database();

$complaint_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($complaint_id <= 0) {
    header('Location: manage-complaints.php');
    exit;
}

$complaint = $db->getRow(
    'SELECT c.*, 
            cust.customer_name, cust.customer_email, cust.customer_mobile,
            im.item_name,
            cpit.name AS product_issue_name,
            csit.name AS service_issue_name,
            crr.name AS resolve_reason_name,
            rim.item_name AS resolve_material_name,
            rs.supplier_name AS resolve_supplier_name,
            u.username AS assigned_username
     FROM complaints c
     LEFT JOIN customer cust ON cust.customer_id = c.customer_id
     LEFT JOIN item_master im ON im.item_id = c.product_id
     LEFT JOIN complaint_product_issue_type cpit ON cpit.id = c.product_issue_type_id
     LEFT JOIN complaint_service_issue_type csit ON csit.id = c.service_issue_type_id
     LEFT JOIN complaint_resolve_reason crr ON crr.id = c.resolve_reason_id
     LEFT JOIN item_master rim ON rim.item_id = c.resolve_material_id
     LEFT JOIN supplier rs ON rs.supplier_id = c.resolve_supplier_id
     LEFT JOIN users u ON u.userid = c.assigned_user_id
     WHERE c.complaint_id = ?',
    [$complaint_id]
);

if (!$complaint) {
    header('Location: manage-complaints.php');
    exit;
}

// Get backend users for assignment dropdown
$users = $db->getRows('SELECT userid, username, first_name FROM users WHERE activated = ? ORDER BY username', ['Y']);

// Get resolve reasons
$resolve_reasons = $db->getRows('SELECT * FROM complaint_resolve_reason WHERE is_active = 1', []);

// Get materials (items)
$materials = $db->getRows('SELECT item_id, item_name FROM item_master WHERE item_active = ? ORDER BY item_name', ['Y']);

// Get suppliers
$suppliers = $db->getRows('SELECT supplier_id, supplier_name FROM supplier ORDER BY supplier_name', []);

// Handle form submission
$success_msg = '';
$error_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'assign') {
        $assigned_user_id = isset($_POST['assigned_user_id']) ? (int)$_POST['assigned_user_id'] : 0;
        if ($assigned_user_id > 0) {
            $db->updateRow(
                'UPDATE complaints SET assigned_user_id = ?, status = ?, updated_at = NOW() WHERE complaint_id = ?',
                [$assigned_user_id, 'Assigned', $complaint_id]
            );

            // Send email to assigned user
            $assigned_user = $db->getRow('SELECT * FROM users WHERE userid = ?', [$assigned_user_id]);
            if ($assigned_user && !empty($assigned_user['email'])) {
                try {
                    require_once(__DIR__ . '/include/EmailService.php');
                    $emailService = new EmailService();
                    if ($emailService->isEnabled()) {
                        $subject = 'Complaint Assigned to You - ' . htmlspecialchars($complaint['complaint_code'], ENT_QUOTES, 'UTF-8');
                        $body = '<h3>A complaint has been assigned to you</h3>';
                        $body .= '<p><strong>Complaint Code:</strong> ' . htmlspecialchars($complaint['complaint_code'], ENT_QUOTES, 'UTF-8') . '</p>';
                        $body .= '<p><strong>Type:</strong> ' . htmlspecialchars($complaint['complaint_type'], ENT_QUOTES, 'UTF-8') . '</p>';
                        $body .= '<p><strong>Customer:</strong> ' . htmlspecialchars($complaint['customer_name'], ENT_QUOTES, 'UTF-8') . '</p>';
                        $body .= '<p><strong>Details:</strong> ' . htmlspecialchars($complaint['complaint_text'], ENT_QUOTES, 'UTF-8') . '</p>';
                        $body .= '<p>Please login to the admin panel to review and resolve this complaint.</p>';
                        $emailService->send($assigned_user['email'], $subject, $body);
                    }
                } catch (Exception $e) {
                    // Email send failure should not block assignment
                }
            }

            $success_msg = 'Complaint assigned successfully.';
            // Refresh complaint data
            header('Location: resolve-complaint.php?id=' . $complaint_id . '&msg=assigned');
            exit;
        } else {
            $error_msg = 'Please select a user to assign.';
        }
    }

    if ($action === 'update_status') {
        $new_status = isset($_POST['status']) ? $_POST['status'] : '';
        if (in_array($new_status, ['In Progress', 'Closed'])) {
            $db->updateRow(
                'UPDATE complaints SET status = ?, updated_at = NOW() WHERE complaint_id = ?',
                [$new_status, $complaint_id]
            );
            header('Location: resolve-complaint.php?id=' . $complaint_id . '&msg=status_updated');
            exit;
        }
    }

    if ($action === 'resolve') {
        $resolve_reason_id = isset($_POST['resolve_reason_id']) ? (int)$_POST['resolve_reason_id'] : null;
        $resolve_material_id = isset($_POST['resolve_material_id']) ? (int)$_POST['resolve_material_id'] : null;
        $resolve_supplier_id = isset($_POST['resolve_supplier_id']) ? (int)$_POST['resolve_supplier_id'] : null;
        $customer_outcome_message = isset($_POST['customer_outcome_message']) ? trim($_POST['customer_outcome_message']) : '';

        if (empty($customer_outcome_message)) {
            $error_msg = 'Please enter a customer outcome message.';
        } else {
            // If reason is not "Materials", clear material/supplier
            $reason = $db->getRow('SELECT * FROM complaint_resolve_reason WHERE id = ?', [$resolve_reason_id]);
            if (!$reason || $reason['name'] !== 'Materials') {
                $resolve_material_id = null;
                $resolve_supplier_id = null;
            }

            $db->updateRow(
                'UPDATE complaints SET resolve_reason_id = ?, resolve_material_id = ?, resolve_supplier_id = ?, customer_outcome_message = ?, status = ?, resolved_at = NOW(), updated_at = NOW() WHERE complaint_id = ?',
                [$resolve_reason_id, $resolve_material_id, $resolve_supplier_id, $customer_outcome_message, 'Closed', $complaint_id]
            );
            header('Location: resolve-complaint.php?id=' . $complaint_id . '&msg=resolved');
            exit;
        }
    }
}

// Check for redirect messages
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'assigned') $success_msg = 'Complaint assigned successfully. Email notification sent.';
    if ($_GET['msg'] === 'status_updated') $success_msg = 'Status updated successfully.';
    if ($_GET['msg'] === 'resolved') $success_msg = 'Complaint resolved and closed successfully.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include('common/head.php'); ?>
    <style>
        .detail-table th { width: 200px; background: #f8f9fa; }
        .section-title { border-bottom: 2px solid #e74c3c; padding-bottom: 8px; margin: 20px 0 15px; color: #333; }
        .badge-open { background: #f39c12; color: #fff; padding: 4px 12px; border-radius: 4px; }
        .badge-assigned { background: #3498db; color: #fff; padding: 4px 12px; border-radius: 4px; }
        .badge-inprogress { background: #9b59b6; color: #fff; padding: 4px 12px; border-radius: 4px; }
        .badge-closed { background: #27ae60; color: #fff; padding: 4px 12px; border-radius: 4px; }
        .material-fields { display: none; }
    </style>
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
                        <li><span><?php echo htmlspecialchars($complaint['complaint_code'], ENT_QUOTES, 'UTF-8'); ?></span></li>
                    </ul>
                </div>

                <?php if ($success_msg) { ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success_msg, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php } ?>
                <?php if ($error_msg) { ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_msg, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php } ?>

                <div class="row">
                    <!-- Complaint Details -->
                    <div class="col-md-7">
                        <div class="portlet light bordered">
                            <div class="portlet-title">
                                <div class="caption"><i class="fa fa-file-text-o"></i> Complaint Details</div>
                            </div>
                            <div class="portlet-body">
                                <?php
                                    $statusClass = 'badge-open';
                                    if ($complaint['status'] == 'Assigned') $statusClass = 'badge-assigned';
                                    elseif ($complaint['status'] == 'In Progress') $statusClass = 'badge-inprogress';
                                    elseif ($complaint['status'] == 'Closed') $statusClass = 'badge-closed';
                                ?>
                                <table class="table table-bordered detail-table">
                                    <tr><th>Complaint Code</th><td><?php echo htmlspecialchars($complaint['complaint_code'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
                                    <tr><th>Status</th><td><span class="<?php echo $statusClass; ?>"><?php echo htmlspecialchars($complaint['status'], ENT_QUOTES, 'UTF-8'); ?></span></td></tr>
                                    <tr><th>Type</th><td><?php echo htmlspecialchars($complaint['complaint_type'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
                                    <tr><th>Customer</th><td><?php echo htmlspecialchars($complaint['customer_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($complaint['customer_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>)</td></tr>
                                    <?php if ($complaint['complaint_type'] == 'Product') { ?>
                                        <tr><th>Product</th><td><?php echo htmlspecialchars($complaint['item_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td></tr>
                                        <tr><th>Issue Type</th><td><?php echo htmlspecialchars($complaint['product_issue_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td></tr>
                                    <?php } else { ?>
                                        <tr><th>Issue Type</th><td><?php echo htmlspecialchars($complaint['service_issue_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td></tr>
                                    <?php } ?>
                                    <tr><th>Complaint Details</th><td><?php echo nl2br(htmlspecialchars($complaint['complaint_text'], ENT_QUOTES, 'UTF-8')); ?></td></tr>
                                    <tr><th>Date of Purchase</th><td><?php echo $complaint['date_of_purchase'] ? htmlspecialchars($complaint['date_of_purchase'], ENT_QUOTES, 'UTF-8') : '-'; ?></td></tr>
                                    <tr><th>Invoice No</th><td><?php echo $complaint['invoice_no'] ? htmlspecialchars($complaint['invoice_no'], ENT_QUOTES, 'UTF-8') : '-'; ?></td></tr>
                                    <tr><th>Submitted</th><td><?php echo htmlspecialchars($complaint['created_at'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
                                    <?php if ($complaint['attachment']) { ?>
                                        <tr><th>Attachment</th><td><a href="../<?php echo htmlspecialchars($complaint['attachment'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank"><img src="../<?php echo htmlspecialchars($complaint['attachment'], ENT_QUOTES, 'UTF-8'); ?>" style="max-width:300px; max-height:200px; border:1px solid #ddd; padding:3px;" /></a></td></tr>
                                    <?php } ?>
                                    <?php if ($complaint['assigned_username']) { ?>
                                        <tr><th>Assigned To</th><td><?php echo htmlspecialchars($complaint['assigned_username'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
                                    <?php } ?>
                                </table>

                                <?php if ($complaint['status'] == 'Closed' && $complaint['customer_outcome_message']) { ?>
                                    <h4 class="section-title">Resolution</h4>
                                    <table class="table table-bordered detail-table">
                                        <?php if ($complaint['resolve_reason_name']) { ?>
                                            <tr><th>Reason</th><td><?php echo htmlspecialchars($complaint['resolve_reason_name'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
                                        <?php } ?>
                                        <?php if ($complaint['resolve_material_name']) { ?>
                                            <tr><th>Material</th><td><?php echo htmlspecialchars($complaint['resolve_material_name'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
                                        <?php } ?>
                                        <?php if ($complaint['resolve_supplier_name']) { ?>
                                            <tr><th>Supplier</th><td><?php echo htmlspecialchars($complaint['resolve_supplier_name'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
                                        <?php } ?>
                                        <tr><th>Customer Outcome</th><td><?php echo nl2br(htmlspecialchars($complaint['customer_outcome_message'], ENT_QUOTES, 'UTF-8')); ?></td></tr>
                                        <tr><th>Resolved At</th><td><?php echo htmlspecialchars($complaint['resolved_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td></tr>
                                    </table>
                                <?php } ?>
                            </div>
                        </div>
                    </div>

                    <!-- Actions Panel -->
                    <div class="col-md-5">
                        <?php if ($complaint['status'] !== 'Closed') { ?>
                        
                        <!-- Assign User -->
                        <div class="portlet light bordered">
                            <div class="portlet-title">
                                <div class="caption"><i class="fa fa-user"></i> Assign User</div>
                            </div>
                            <div class="portlet-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="assign">
                                    <div class="form-group">
                                        <label>Assign to Backend User</label>
                                        <select class="form-control" name="assigned_user_id" required>
                                            <option value="">-- Select User --</option>
                                            <?php if ($users) { foreach ($users as $u) { ?>
                                                <option value="<?php echo (int)$u['userid']; ?>" <?php echo ($complaint['assigned_user_id'] == $u['userid']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($u['username'] . ($u['first_name'] ? ' (' . $u['first_name'] . ')' : ''), ENT_QUOTES, 'UTF-8'); ?>
                                                </option>
                                            <?php } } ?>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-block">Assign & Notify</button>
                                </form>
                            </div>
                        </div>

                        <!-- Update Status -->
                        <div class="portlet light bordered">
                            <div class="portlet-title">
                                <div class="caption"><i class="fa fa-refresh"></i> Update Status</div>
                            </div>
                            <div class="portlet-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_status">
                                    <div class="form-group">
                                        <label>Change Status</label>
                                        <select class="form-control" name="status" required>
                                            <option value="">-- Select Status --</option>
                                            <option value="In Progress" <?php echo ($complaint['status'] == 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                                            <option value="Closed">Closed</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-warning btn-block">Update Status</button>
                                </form>
                            </div>
                        </div>

                        <!-- Resolve -->
                        <div class="portlet light bordered">
                            <div class="portlet-title">
                                <div class="caption"><i class="fa fa-check-circle"></i> Resolve & Close</div>
                            </div>
                            <div class="portlet-body">
                                <form method="POST" id="resolveForm">
                                    <input type="hidden" name="action" value="resolve">
                                    <div class="form-group">
                                        <label>Reason</label>
                                        <select class="form-control" name="resolve_reason_id" id="resolve_reason_id">
                                            <option value="">-- Select Reason --</option>
                                            <?php if ($resolve_reasons) { foreach ($resolve_reasons as $rr) { ?>
                                                <option value="<?php echo (int)$rr['id']; ?>" data-name="<?php echo htmlspecialchars($rr['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?php echo htmlspecialchars($rr['name'], ENT_QUOTES, 'UTF-8'); ?>
                                                </option>
                                            <?php } } ?>
                                        </select>
                                    </div>
                                    <div class="material-fields" id="material-fields">
                                        <div class="form-group">
                                            <label>Select Material</label>
                                            <select class="form-control" name="resolve_material_id">
                                                <option value="">-- Select Material --</option>
                                                <?php if ($materials) { foreach ($materials as $m) { ?>
                                                    <option value="<?php echo (int)$m['item_id']; ?>"><?php echo htmlspecialchars($m['item_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                                <?php } } ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Select Supplier</label>
                                            <select class="form-control" name="resolve_supplier_id">
                                                <option value="">-- Select Supplier --</option>
                                                <?php if ($suppliers) { foreach ($suppliers as $s) { ?>
                                                    <option value="<?php echo (int)$s['supplier_id']; ?>"><?php echo htmlspecialchars($s['supplier_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                                <?php } } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Customer Outcome Message <span class="text-danger">*</span></label>
                                        <textarea class="form-control" name="customer_outcome_message" rows="4" required placeholder="Write the resolution message that will be shown to the customer..."></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-success btn-block">Resolve & Close Complaint</button>
                                </form>
                            </div>
                        </div>
                        <?php } else { ?>
                            <div class="portlet light bordered">
                                <div class="portlet-body text-center" style="padding: 30px;">
                                    <i class="fa fa-check-circle" style="font-size: 60px; color: #27ae60;"></i>
                                    <h3 style="color: #27ae60;">Complaint Closed</h3>
                                    <p>This complaint has been resolved.</p>
                                </div>
                            </div>
                        <?php } ?>

                        <a href="manage-complaints.php" class="btn btn-default btn-block"><i class="fa fa-arrow-left"></i> Back to Complaints</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../plugins/jquery.min.js"></script>
    <script src="../plugins/bootstrap/js/bootstrap.min.js"></script>
    <script>
    $(document).ready(function() {
        // Show/hide material fields based on resolve reason
        $('#resolve_reason_id').change(function() {
            var selectedName = $(this).find(':selected').data('name');
            if (selectedName === 'Materials') {
                $('#material-fields').slideDown();
            } else {
                $('#material-fields').slideUp();
            }
        });
    });
    </script>
</body>
</html>
