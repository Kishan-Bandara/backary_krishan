<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('include/database.php');
include('include/check_login.php');

// Permission check (graceful fallback)
if (function_exists('requirePermission')) {
    requirePermission('settings.permissions');
}

$db = new Database();

// Ensure table exists
try {
    $db->getRow('SELECT id FROM smtp_settings WHERE id = 1');
} catch (Exception $e) {
    // Table doesn't exist, create it
    $db->insertRow("CREATE TABLE IF NOT EXISTS `smtp_settings` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `smtp_host` VARCHAR(255) NOT NULL DEFAULT '',
        `smtp_port` INT(11) NOT NULL DEFAULT 587,
        `smtp_username` VARCHAR(255) NOT NULL DEFAULT '',
        `smtp_password` VARCHAR(255) NOT NULL DEFAULT '',
        `smtp_encryption` ENUM('tls','ssl','none') NOT NULL DEFAULT 'tls',
        `smtp_from_email` VARCHAR(255) NOT NULL DEFAULT '',
        `smtp_from_name` VARCHAR(255) NOT NULL DEFAULT '',
        `smtp_reply_to_email` VARCHAR(255) NOT NULL DEFAULT '',
        `smtp_reply_to_name` VARCHAR(255) NOT NULL DEFAULT '',
        `smtp_enabled` TINYINT(1) NOT NULL DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->insertRow("INSERT IGNORE INTO smtp_settings (id, smtp_port, smtp_encryption, smtp_enabled) VALUES (1, 587, 'tls', 0)");
}

$settings = $db->getRow('SELECT * FROM smtp_settings WHERE id = 1');
if (!$settings) {
    $db->insertRow("INSERT INTO smtp_settings (id, smtp_port, smtp_encryption, smtp_enabled) VALUES (1, 587, 'tls', 0)");
    $settings = $db->getRow('SELECT * FROM smtp_settings WHERE id = 1');
}

$message = '';
$messageClass = '';
$testResult = '';
$testClass = '';

// Handle AJAX test email
if (isset($_POST['action']) && $_POST['action'] === 'test_email') {
    header('Content-Type: application/json');
    $testEmail = trim($_POST['test_email'] ?? '');
    if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Please enter a valid email address.']);
        exit;
    }
    
    require_once('include/EmailService.php');
    $emailService = new EmailService();
    
    if (!$emailService->isEnabled()) {
        echo json_encode(['status' => 'error', 'message' => 'Email service is not enabled. Please save your settings first and enable SMTP.']);
        exit;
    }
    
    $sent = $emailService->sendTestEmail($testEmail);
    if ($sent) {
        echo json_encode(['status' => 'success', 'message' => 'Test email sent successfully to ' . $testEmail]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to send test email: ' . $emailService->getLastError()]);
    }
    exit;
}

// Handle save
if (isset($_POST['save_smtp'])) {
    $smtp_host           = trim($_POST['smtp_host'] ?? '');
    $smtp_port           = (int)($_POST['smtp_port'] ?? 587);
    $smtp_username       = trim($_POST['smtp_username'] ?? '');
    $smtp_password       = trim($_POST['smtp_password'] ?? '');
    $smtp_encryption     = $_POST['smtp_encryption'] ?? 'tls';
    $smtp_from_email     = trim($_POST['smtp_from_email'] ?? '');
    $smtp_from_name      = trim($_POST['smtp_from_name'] ?? '');
    $smtp_reply_to_email = trim($_POST['smtp_reply_to_email'] ?? '');
    $smtp_reply_to_name  = trim($_POST['smtp_reply_to_name'] ?? '');
    $smtp_enabled        = isset($_POST['smtp_enabled']) ? 1 : 0;

    // Validate
    if (empty($smtp_host) && $smtp_enabled) {
        $message = 'SMTP Host is required when email is enabled.';
        $messageClass = 'alert-danger';
    } elseif (empty($smtp_from_email) && $smtp_enabled) {
        $message = 'From Email is required when email is enabled.';
        $messageClass = 'alert-danger';
    } else {
        try {
            // If password field is empty and we have existing password, keep it
            if (empty($smtp_password) && !empty($settings['smtp_password'])) {
                $smtp_password = $settings['smtp_password'];
            }

            $db->updateRow(
                'UPDATE smtp_settings SET 
                    smtp_host = ?, smtp_port = ?, smtp_username = ?, smtp_password = ?,
                    smtp_encryption = ?, smtp_from_email = ?, smtp_from_name = ?,
                    smtp_reply_to_email = ?, smtp_reply_to_name = ?, smtp_enabled = ?
                WHERE id = 1',
                [$smtp_host, $smtp_port, $smtp_username, $smtp_password, $smtp_encryption, $smtp_from_email, $smtp_from_name, $smtp_reply_to_email, $smtp_reply_to_name, $smtp_enabled]
            );

            $settings = $db->getRow('SELECT * FROM smtp_settings WHERE id = 1');
            $message = 'SMTP settings saved successfully.';
            $messageClass = 'alert-success';
        } catch (Exception $e) {
            $message = 'Error saving settings: ' . $e->getMessage();
            $messageClass = 'alert-danger';
        }
    }
}

// Load email logs
$emailLogs = [];
try {
    $emailLogs = $db->getRows('SELECT * FROM email_log ORDER BY sent_at DESC LIMIT 50');
} catch (Exception $e) {
    // Table might not exist yet
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>SMTP Email Settings</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
    <style>
        .smtp-card { background: #fff; border-radius: 8px; border: 1px solid #e9ecef; padding: 25px; margin-bottom: 20px; }
        .smtp-card h4 { margin: 0 0 20px; color: #2c3e50; font-size: 16px; border-bottom: 2px solid #d4a762; padding-bottom: 10px; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-enabled { background: #d4edda; color: #155724; }
        .status-disabled { background: #f8d7da; color: #721c24; }
        .password-field { position: relative; }
        .password-toggle { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #6c757d; }
        .log-table td { font-size: 12px; }
        .log-sent { color: #27ae60; font-weight: 600; }
        .log-failed { color: #e74c3c; font-weight: 600; }
        .test-section { background: #f8f9fa; border: 1px dashed #d4a762; border-radius: 8px; padding: 20px; }
    </style>
</head>
<body class="page-sidebar-closed-hide-logo page-content-white">
<?php include('common/manubar.php'); ?>
<div class="page-container">
    <div class="page-sidebar-wrapper">
        <?php include('common/sidebar.php'); ?>
    </div>
    <div class="page-content-wrapper">
        <div class="page-content">
            <div class="page-bar">
                <ul class="page-breadcrumb">
                    <li><a href="index.php">Home</a> <i class="fa fa-circle"></i></li>
                    <li><a href="#">Settings</a> <i class="fa fa-circle"></i></li>
                    <li><span>SMTP Email Settings</span></li>
                </ul>
            </div>

            <h3 class="page-title">
                SMTP Email Settings
                <?php if ((int)($settings['smtp_enabled'] ?? 0) === 1) { ?>
                    <span class="status-badge status-enabled"><i class="fa fa-check-circle"></i> Enabled</span>
                <?php } else { ?>
                    <span class="status-badge status-disabled"><i class="fa fa-times-circle"></i> Disabled</span>
                <?php } ?>
            </h3>

            <?php if (!empty($message)) { ?>
                <div id="main-alert" class="alert <?php echo $messageClass; ?> alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <?php echo $message; ?>
                </div>
            <?php } else { ?>
                <!-- placeholder for JS-injected messages -->
                <div id="main-alert" style="display:none;" class="alert alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <span id="main-alert-msg"></span>
                </div>
            <?php } ?>
            
            <div id="test-alert" style="display:none;" class="alert alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <span id="test-alert-msg"></span>
            </div>

            <div id="test-alert" style="display:none;" class="alert alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <span id="test-alert-msg"></span>
            </div>

            <form method="POST" action="">
                <div class="row">
                    <!-- SMTP Server Settings -->
                    <div class="col-md-6">
                        <div class="smtp-card">
                            <h4><i class="fa fa-server"></i> SMTP Server</h4>
                            
                            <div class="form-group">
                                <label>SMTP Host <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="smtp_host" 
                                       value="<?php echo htmlspecialchars($settings['smtp_host'] ?? ''); ?>" 
                                       placeholder="e.g., smtp.gmail.com">
                                <small class="text-muted">Gmail: smtp.gmail.com | Outlook: smtp.office365.com | Yahoo: smtp.mail.yahoo.com</small>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Port <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="smtp_port" 
                                               value="<?php echo (int)($settings['smtp_port'] ?? 587); ?>">
                                        <small class="text-muted">TLS: 587 | SSL: 465</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Encryption</label>
                                        <select class="form-control" name="smtp_encryption">
                                            <option value="tls" <?php echo ($settings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS (Recommended)</option>
                                            <option value="ssl" <?php echo ($settings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                            <option value="none" <?php echo ($settings['smtp_encryption'] ?? '') === 'none' ? 'selected' : ''; ?>>None</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" class="form-control" name="smtp_username" 
                                       value="<?php echo htmlspecialchars($settings['smtp_username'] ?? ''); ?>"
                                       placeholder="Your email address or username">
                            </div>

                            <div class="form-group">
                                <label>Password</label>
                                <div class="password-field">
                                    <input type="password" class="form-control" name="smtp_password" id="smtp_password"
                                           value="" placeholder="<?php echo !empty($settings['smtp_password']) ? '••••••• (unchanged)' : 'Enter password'; ?>">
                                    <i class="fa fa-eye password-toggle" onclick="togglePassword()"></i>
                                </div>
                                <small class="text-muted">Leave blank to keep existing password. For Gmail, use an App Password.</small>
                            </div>

                            <div class="form-group">
                                <label class="mt-checkbox mt-checkbox-outline">
                                    <input type="checkbox" name="smtp_enabled" value="1" 
                                           <?php echo ((int)($settings['smtp_enabled'] ?? 0) === 1) ? 'checked' : ''; ?>>
                                    <strong> Enable SMTP Email Service</strong>
                                    <span></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Sender Settings -->
                    <div class="col-md-6">
                        <div class="smtp-card">
                            <h4><i class="fa fa-envelope"></i> Sender Information</h4>
                            
                            <div class="form-group">
                                <label>From Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="smtp_from_email" 
                                       value="<?php echo htmlspecialchars($settings['smtp_from_email'] ?? ''); ?>"
                                       placeholder="noreply@yourbakery.com">
                            </div>

                            <div class="form-group">
                                <label>From Name</label>
                                <input type="text" class="form-control" name="smtp_from_name" 
                                       value="<?php echo htmlspecialchars($settings['smtp_from_name'] ?? ''); ?>"
                                       placeholder="Your Bakery Name">
                            </div>

                            <div class="form-group">
                                <label>Reply-To Email</label>
                                <input type="email" class="form-control" name="smtp_reply_to_email" 
                                       value="<?php echo htmlspecialchars($settings['smtp_reply_to_email'] ?? ''); ?>"
                                       placeholder="replies@yourbakery.com (optional)">
                            </div>

                            <div class="form-group">
                                <label>Reply-To Name</label>
                                <input type="text" class="form-control" name="smtp_reply_to_name" 
                                       value="<?php echo htmlspecialchars($settings['smtp_reply_to_name'] ?? ''); ?>"
                                       placeholder="(optional)">
                            </div>
                        </div>

                        <!-- Test Email Section -->
                        <div class="test-section">
                            <h4 style="margin: 0 0 15px; color: #d4a762;"><i class="fa fa-paper-plane"></i> Send Test Email</h4>
                            <div class="input-group">
                                <input type="email" class="form-control" id="test_email" placeholder="Enter email to test...">
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-warning" id="btn-test-email" onclick="sendTestEmail()">
                                        <i class="fa fa-send"></i> Send Test
                                    </button>
                                </span>
                            </div>
                            <small class="text-muted">Save settings first, then send a test email to verify configuration.</small>
                        </div>
                    </div>
                </div>

                <div class="text-center" style="margin: 20px 0;">
                    <button type="submit" name="save_smtp" class="btn btn-lg btn-primary">
                        <i class="fa fa-save"></i> Save SMTP Settings
                    </button>
                </div>
            </form>

            <!-- Email Log -->
            <?php if (!empty($emailLogs)) { ?>
            <div class="smtp-card" style="margin-top: 30px;">
                <h4><i class="fa fa-history"></i> Email Log (Last 50)</h4>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover log-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>To</th>
                                <th>Subject</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Error</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($emailLogs as $log) { ?>
                            <tr>
                                <td><?php echo date('d M Y H:i', strtotime($log['sent_at'])); ?></td>
                                <td><?php echo htmlspecialchars($log['to_email']); ?></td>
                                <td><?php echo htmlspecialchars(mb_strimwidth($log['subject'], 0, 50, '...')); ?></td>
                                <td><span class="label label-default"><?php echo htmlspecialchars($log['template_type']); ?></span></td>
                                <td>
                                    <?php if ($log['status'] === 'sent') { ?>
                                        <span class="log-sent"><i class="fa fa-check-circle"></i> Sent</span>
                                    <?php } else { ?>
                                        <span class="log-failed"><i class="fa fa-times-circle"></i> Failed</span>
                                    <?php } ?>
                                </td>
                                <td><?php echo htmlspecialchars($log['error_message'] ?? '-'); ?></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php } ?>

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

<script>
function togglePassword() {
    var field = document.getElementById('smtp_password');
    var icon = event.target;
    if (field.type === 'password') {
        field.type = 'text';
        icon.className = 'fa fa-eye-slash password-toggle';
    } else {
        field.type = 'password';
        icon.className = 'fa fa-eye password-toggle';
    }
}

function sendTestEmail() {
    var email = document.getElementById('test_email').value.trim();
    if (!email) {
        alert('Please enter an email address');
        return;
    }

    var btn = document.getElementById('btn-test-email');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Sending...';

    $.ajax({
        url: 'smtp-settings.php',
        method: 'POST',
        data: { action: 'test_email', test_email: email },
        dataType: 'json',
        success: function(resp) {
            var testAlert = document.getElementById('test-alert');
            var testMsg = document.getElementById('test-alert-msg');
            var mainAlert = document.getElementById('main-alert');
            var mainMsg = document.getElementById('main-alert-msg');

            var cls = resp.status === 'success' ? 'alert alert-success alert-dismissible' : 'alert alert-danger alert-dismissible';
            var icon = resp.status === 'success' ? '<i class="fa fa-check-circle"></i>' : '<i class="fa fa-times-circle"></i>';
            var formatted = icon + ' ' + resp.message;

            testAlert.className = cls;
            testMsg.innerHTML = formatted;
            testAlert.style.display = 'block';

            // also mirror into main alert box for visibility
            if (mainAlert && mainMsg) {
                mainAlert.className = cls;
                mainMsg.innerHTML = formatted;
                mainAlert.style.display = 'block';
            }
        },
        error: function(xhr, textStatus, errorThrown) {
            var alertDiv = document.getElementById('test-alert');
            var msgSpan = document.getElementById('test-alert-msg');
            var mainAlert = document.getElementById('main-alert');
            var mainMsg = document.getElementById('main-alert-msg');

            var body = '';
            // try parse JSON response if available
            try {
                var json = JSON.parse(xhr.responseText);
                if (json && json.message) {
                    body = json.message;
                }
            } catch(e) {
                // not JSON
                body = xhr.responseText || errorThrown || textStatus;
            }
            if (!body) {
                body = 'Request failed: ' + (errorThrown || textStatus);
            }

            var formatted = '<i class="fa fa-times-circle"></i> ' + body;
            alertDiv.className = 'alert alert-danger alert-dismissible';
            msgSpan.innerHTML = formatted;
            alertDiv.style.display = 'block';

            if (mainAlert && mainMsg) {
                mainAlert.className = 'alert alert-danger alert-dismissible';
                mainMsg.innerHTML = formatted;
                mainAlert.style.display = 'block';
            }
        },
        complete: function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-send"></i> Send Test';
        }
    });
}
</script>
</body>
</html>
