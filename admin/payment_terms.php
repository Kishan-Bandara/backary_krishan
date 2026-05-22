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

function getPaymentTermsColumns(Database $db)
{
    $columns = [];

    try {
        $rows = $db->getRows('SHOW COLUMNS FROM `payment_terms`') ?: [];
        foreach ($rows as $row) {
            if (!empty($row['Field'])) {
                $columns[$row['Field']] = true;
            }
        }
    } catch (Exception $e) {
        return [];
    }

    return $columns;
}

function ensurePaymentTermsTable(Database $db)
{
    try {
        $db->getRows('CREATE TABLE IF NOT EXISTS `payment_terms` (
            `payment_terms_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `payment_terms_name` VARCHAR(100) NOT NULL,
            `net_days` INT UNSIGNED DEFAULT NULL,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`payment_terms_id`),
            UNIQUE KEY `uq_payment_terms_name` (`payment_terms_name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
    } catch (Exception $e) {
        return getPaymentTermsColumns($db);
    }

    $columns = getPaymentTermsColumns($db);

    if (!isset($columns['net_days'])) {
        try {
            $db->getRows('ALTER TABLE `payment_terms` ADD COLUMN `net_days` INT UNSIGNED DEFAULT NULL AFTER `payment_terms_name`');
            $columns['net_days'] = true;
        } catch (Exception $e) {
            // Continue with the columns that do exist.
        }
    }

    if (!isset($columns['is_active'])) {
        try {
            $db->getRows('ALTER TABLE `payment_terms` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `net_days`');
            $columns['is_active'] = true;
        } catch (Exception $e) {
            // Continue with the columns that do exist.
        }
    }

    return $columns;
}

$message = '';
$messageClass = '';
$db = null;
$paymentTermsColumns = [];
$supportsNetDays = false;
$supportsIsActive = false;

try {
    $db = new Database();
    $paymentTermsColumns = ensurePaymentTermsTable($db);
    $supportsNetDays = isset($paymentTermsColumns['net_days']);
    $supportsIsActive = isset($paymentTermsColumns['is_active']);
} catch (Exception $e) {
    $message = 'Database connection error: ' . $e->getMessage();
    $messageClass = 'alert-danger';
}

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editRow = null;
$formData = [
    'payment_terms_name' => '',
    'net_days' => '',
    'is_active' => 1,
];

if ($db && $editId > 0) {
    $selectColumns = ['payment_terms_id', 'payment_terms_name'];
    if ($supportsNetDays) {
        $selectColumns[] = 'net_days';
    }
    if ($supportsIsActive) {
        $selectColumns[] = 'is_active';
    }

    try {
        $editRow = $db->getRow('SELECT ' . implode(', ', $selectColumns) . ' FROM payment_terms WHERE payment_terms_id = ? LIMIT 1', [$editId]);
        if ($editRow) {
            $formData['payment_terms_name'] = $editRow['payment_terms_name'] ?? '';
            $formData['net_days'] = $supportsNetDays && isset($editRow['net_days']) && $editRow['net_days'] !== null ? (string) $editRow['net_days'] : '';
            $formData['is_active'] = $supportsIsActive ? (int) ($editRow['is_active'] ?? 1) : 1;
        }
    } catch (Exception $e) {
        $editRow = null;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    $action = trim((string) ($_POST['action'] ?? ''));
    $name = trim((string) ($_POST['payment_terms_name'] ?? ''));
    $netDaysRaw = trim((string) ($_POST['net_days'] ?? ''));
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $targetId = (int) ($_POST['payment_terms_id'] ?? 0);

    $formData['payment_terms_name'] = $name;
    $formData['net_days'] = $netDaysRaw;
    $formData['is_active'] = $isActive;

    $errors = [];
    if ($name === '') {
        $errors[] = 'Payment term name is required.';
    } elseif (mb_strlen($name) > 100) {
        $errors[] = 'Payment term name must be 100 characters or less.';
    }

    $netDaysValue = null;
    if ($supportsNetDays && $netDaysRaw !== '') {
        if (!ctype_digit($netDaysRaw)) {
            $errors[] = 'Net days must be a whole number.';
        } else {
            $netDaysValue = (int) $netDaysRaw;
        }
    }

    if ($action !== 'add' && $action !== 'update') {
        $errors[] = 'Invalid payment terms action.';
    }

    if ($action === 'update' && $targetId <= 0) {
        $errors[] = 'Invalid payment term selected for edit.';
    }

    if (empty($errors)) {
        try {
            $duplicateParams = [$name];
            $duplicateSql = 'SELECT payment_terms_id FROM payment_terms WHERE payment_terms_name = ?';
            if ($action === 'update') {
                $duplicateSql .= ' AND payment_terms_id != ?';
                $duplicateParams[] = $targetId;
            }
            $duplicateSql .= ' LIMIT 1';

            $duplicate = $db->getRow($duplicateSql, $duplicateParams);
            if ($duplicate) {
                $errors[] = 'Payment term name already exists.';
            }
        } catch (Exception $e) {
            $errors[] = 'Unable to validate payment term uniqueness.';
        }
    }

    if (empty($errors)) {
        try {
            if ($action === 'add') {
                $insertFields = ['payment_terms_name'];
                $insertValues = [$name];

                if ($supportsNetDays) {
                    $insertFields[] = 'net_days';
                    $insertValues[] = $netDaysValue;
                }
                if ($supportsIsActive) {
                    $insertFields[] = 'is_active';
                    $insertValues[] = $isActive;
                }

                $db->insertRow(
                    'INSERT INTO payment_terms (' . implode(', ', $insertFields) . ') VALUES (' . implode(', ', array_fill(0, count($insertFields), '?')) . ')',
                    $insertValues
                );
                $message = 'Payment term added successfully.';
                $messageClass = 'alert-success';
                $formData = [
                    'payment_terms_name' => '',
                    'net_days' => '',
                    'is_active' => 1,
                ];
                $editRow = null;
                $editId = 0;
            } else {
                $updateParts = ['payment_terms_name = ?'];
                $updateValues = [$name];

                if ($supportsNetDays) {
                    $updateParts[] = 'net_days = ?';
                    $updateValues[] = $netDaysValue;
                }
                if ($supportsIsActive) {
                    $updateParts[] = 'is_active = ?';
                    $updateValues[] = $isActive;
                }

                $updateValues[] = $targetId;
                $db->updateRow(
                    'UPDATE payment_terms SET ' . implode(', ', $updateParts) . ' WHERE payment_terms_id = ?',
                    $updateValues
                );
                $message = 'Payment term updated successfully.';
                $messageClass = 'alert-success';
                $editId = $targetId;
                $editRow = [
                    'payment_terms_id' => $targetId,
                    'payment_terms_name' => $name,
                    'net_days' => $netDaysValue,
                    'is_active' => $isActive,
                ];
            }
        } catch (Exception $e) {
            $message = 'Unable to save payment term: ' . $e->getMessage();
            $messageClass = 'alert-danger';
        }
    } else {
        $message = implode("\n", $errors);
        $messageClass = 'alert-warning';
    }
}

$paymentTerms = [];
if ($db) {
    $selectColumns = ['payment_terms_id', 'payment_terms_name'];
    if ($supportsNetDays) {
        $selectColumns[] = 'net_days';
    }
    if ($supportsIsActive) {
        $selectColumns[] = 'is_active';
    }

    try {
        $paymentTerms = $db->getRows('SELECT ' . implode(', ', $selectColumns) . ' FROM payment_terms ORDER BY payment_terms_name ASC') ?: [];
    } catch (Exception $e) {
        if ($message === '') {
            $message = 'Unable to load payment terms: ' . $e->getMessage();
            $messageClass = 'alert-danger';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Payment Terms</title>
    <?php include('common/head.php'); ?>
    <style>
        .section-card {
            padding: 18px;
            margin-bottom: 18px;
            background: #fff;
            border: 1px solid #e4eaf1;
            border-radius: 8px;
            box-shadow: 0 4px 14px rgba(32, 51, 74, 0.05);
        }

        .term-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }

        .term-badge.active {
            background: #e8f7ee;
            color: #1d7f43;
        }

        .term-badge.inactive {
            background: #fdeceb;
            color: #b3362d;
        }

        .page-note {
            margin-top: 8px;
            color: #6b7785;
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
                        <li><a href="manage-settings.php">Settings</a><i class="fa fa-circle"></i></li>
                        <li><span>Payment Terms</span></li>
                    </ul>
                </div>

                <h3 class="page-title"><i class="fa fa-calendar-check-o"></i> Payment Terms Master Data</h3>
                <p class="page-note">Manage the payment terms used in customer and supplier master records.</p>

                <?php if ($message !== ''): ?>
                    <div class="alert <?php echo h($messageClass ?: 'alert-info'); ?> alert-dismissable">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                        <?php echo nl2br(h($message)); ?>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-7">
                        <div class="section-card">
                            <h4>Existing Payment Terms</h4>
                            <?php if (empty($paymentTerms)): ?>
                                <p class="text-muted">No payment terms defined yet.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th style="width: 10%;">ID</th>
                                                <th>Payment Term</th>
                                                <?php if ($supportsNetDays): ?><th style="width: 18%;">Net Days</th><?php endif; ?>
                                                <?php if ($supportsIsActive): ?><th style="width: 16%;">Status</th><?php endif; ?>
                                                <th style="width: 16%;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($paymentTerms as $paymentTerm): ?>
                                                <tr>
                                                    <td><?php echo (int) $paymentTerm['payment_terms_id']; ?></td>
                                                    <td><?php echo h($paymentTerm['payment_terms_name']); ?></td>
                                                    <?php if ($supportsNetDays): ?>
                                                        <td><?php echo $paymentTerm['net_days'] !== null && $paymentTerm['net_days'] !== '' ? (int) $paymentTerm['net_days'] . ' days' : '-'; ?></td>
                                                    <?php endif; ?>
                                                    <?php if ($supportsIsActive): ?>
                                                        <td>
                                                            <span class="term-badge <?php echo !empty($paymentTerm['is_active']) ? 'active' : 'inactive'; ?>">
                                                                <?php echo !empty($paymentTerm['is_active']) ? 'Active' : 'Inactive'; ?>
                                                            </span>
                                                        </td>
                                                    <?php endif; ?>
                                                    <td>
                                                        <a class="btn btn-xs btn-default" href="payment_terms.php?edit=<?php echo (int) $paymentTerm['payment_terms_id']; ?>">
                                                            <i class="fa fa-pencil"></i> Edit
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <div class="section-card">
                            <h4><?php echo $editRow ? 'Edit Payment Term' : 'Add Payment Term'; ?></h4>
                            <form method="post">
                                <input type="hidden" name="action" value="<?php echo $editRow ? 'update' : 'add'; ?>">
                                <?php if ($editRow): ?>
                                    <input type="hidden" name="payment_terms_id" value="<?php echo (int) $editRow['payment_terms_id']; ?>">
                                <?php endif; ?>

                                <div class="form-group">
                                    <label>Payment Term Name</label>
                                    <input type="text" name="payment_terms_name" class="form-control" value="<?php echo h($formData['payment_terms_name']); ?>" maxlength="100" required>
                                </div>

                                <?php if ($supportsNetDays): ?>
                                    <div class="form-group">
                                        <label>Net Days</label>
                                        <input type="number" name="net_days" class="form-control" min="0" step="1" value="<?php echo h($formData['net_days']); ?>" placeholder="Example: 30">
                                        <span class="help-block">Leave blank if this term does not use a net-days value.</span>
                                    </div>
                                <?php endif; ?>

                                <?php if ($supportsIsActive): ?>
                                    <div class="form-group">
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" name="is_active" value="1" <?php echo !empty($formData['is_active']) ? 'checked' : ''; ?>> Active
                                            </label>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div>
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fa fa-save"></i> <?php echo $editRow ? 'Update Payment Term' : 'Add Payment Term'; ?>
                                    </button>
                                    <?php if ($editRow): ?>
                                        <a href="payment_terms.php" class="btn btn-default">Cancel</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
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