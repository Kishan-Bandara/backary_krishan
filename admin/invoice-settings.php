<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('include/database.php');
include('include/check_login.php');

requirePermission('settings.permissions');

$db = new Database();

// Check if invoice_settings table exists, if not create it
try {
    $db->getRow('SELECT 1 FROM invoice_settings LIMIT 1');
} catch (Exception $e) {
    // Create the table
    $db->updateRow('CREATE TABLE IF NOT EXISTS invoice_settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        invoice_logo VARCHAR(500) DEFAULT NULL,
        receipt_name VARCHAR(255) DEFAULT NULL,
        receipt_address TEXT DEFAULT NULL,
        receipt_phone VARCHAR(100) DEFAULT NULL,
        receipt_email VARCHAR(255) DEFAULT NULL,
        receipt_footer TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )', []);
    // Insert default row
    $db->insertRow('INSERT INTO invoice_settings (id, invoice_logo, receipt_name) VALUES (1, "assets/layouts/layout/img/invoice_logo.png", "BAKERY")', []);
}

$settings = $db->getRow('SELECT * FROM invoice_settings WHERE id = 1');

$message = '';
$messageClass = '';

// Handle logo upload
if (isset($_POST['save_invoice_settings'])) {
    $receipt_name = trim($_POST['receipt_name'] ?? '');
    $receipt_address = trim($_POST['receipt_address'] ?? '');
    $receipt_phone = trim($_POST['receipt_phone'] ?? '');
    $receipt_email = trim($_POST['receipt_email'] ?? '');
    $receipt_footer = trim($_POST['receipt_footer'] ?? '');
    
    $invoice_logo = $settings['invoice_logo'] ?? '';
    
    // Handle file upload
    if (isset($_FILES['invoice_logo_file']) && $_FILES['invoice_logo_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/invoice/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileName = $_FILES['invoice_logo_file']['name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];
        
        if (in_array($fileExt, $allowedExts)) {
            $newFileName = 'invoice_logo_' . time() . '.' . $fileExt;
            $uploadPath = $uploadDir . $newFileName;
            
            if (move_uploaded_file($_FILES['invoice_logo_file']['tmp_name'], $uploadPath)) {
                $invoice_logo = $uploadPath;
            } else {
                $message = 'Failed to upload logo file.';
                $messageClass = 'alert-danger';
            }
        } else {
            $message = 'Invalid file type. Allowed: jpg, jpeg, png, gif, webp, avif';
            $messageClass = 'alert-danger';
        }
    }
    
    if (empty($message)) {
        try {
            if ($settings) {
                $db->updateRow('UPDATE invoice_settings SET invoice_logo = ?, receipt_name = ?, receipt_address = ?, receipt_phone = ?, receipt_email = ?, receipt_footer = ? WHERE id = 1', 
                    [$invoice_logo, $receipt_name, $receipt_address, $receipt_phone, $receipt_email, $receipt_footer]);
            } else {
                $db->insertRow('INSERT INTO invoice_settings (id, invoice_logo, receipt_name, receipt_address, receipt_phone, receipt_email, receipt_footer) VALUES (1, ?, ?, ?, ?, ?, ?)', 
                    [$invoice_logo, $receipt_name, $receipt_address, $receipt_phone, $receipt_email, $receipt_footer]);
            }
            $settings = $db->getRow('SELECT * FROM invoice_settings WHERE id = 1');
            $message = 'Invoice/Receipt settings saved successfully.';
            $messageClass = 'alert-success';
        } catch (Exception $e) {
            $message = 'Unable to save settings: ' . $e->getMessage();
            $messageClass = 'alert-danger';
        }
    }
}

$invoice_logo = $settings['invoice_logo'] ?? '';
$receipt_name = $settings['receipt_name'] ?? '';
$receipt_address = $settings['receipt_address'] ?? '';
$receipt_phone = $settings['receipt_phone'] ?? '';
$receipt_email = $settings['receipt_email'] ?? '';
$receipt_footer = $settings['receipt_footer'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Invoice/Receipt Settings</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
    <style>
        .settings-form .form-group { margin-bottom: 20px; }
        .logo-preview { 
            border: 2px dashed #ddd; 
            padding: 20px; 
            text-align: center; 
            background: #f9f9f9;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .logo-preview img { 
            max-height: 150px; 
            max-width: 100%;
        }
        .logo-preview .no-logo {
            color: #999;
            font-style: italic;
        }
        .current-logo-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            display: block;
        }
        .upload-btn-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }
        .upload-btn-wrapper input[type=file] {
            font-size: 100px;
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
        }
        .file-name-display {
            margin-top: 10px;
            font-size: 13px;
            color: #666;
        }
        .receipt-preview {
            background: #fff;
            border: 1px solid #ddd;
            padding: 30px;
            max-width: 350px;
            margin: 20px auto;
            font-family: 'Courier New', monospace;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .receipt-preview .header {
            text-align: center;
            border-bottom: 1px dashed #333;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .receipt-preview .header img {
            max-height: 60px;
            margin-bottom: 10px;
        }
        .receipt-preview .header h3 {
            margin: 5px 0;
            font-size: 16px;
        }
        .receipt-preview .header p {
            margin: 3px 0;
            font-size: 11px;
            color: #666;
        }
    </style>
</head>
<body class="page-sidebar-closed-hide-logo page-content-white">
<?php include('common/manubar.php'); ?>
<div class="page-container">
    <div class="page-content-wrapper">
        <div class="page-content">
            <h3 class="page-title"><i class="fa fa-file-text-o"></i> Invoice/Receipt Settings</h3>

            <?php if (!empty($message)) { ?>
                <div class="alert <?php echo $messageClass; ?>">
                    <?php echo $message; ?>
                </div>
            <?php } ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="portlet light bordered">
                        <div class="portlet-title">
                            <div class="caption">
                                <i class="fa fa-image"></i> Invoice Logo & Receipt Details
                            </div>
                        </div>
                        <div class="portlet-body">
                            <form method="post" class="form-horizontal settings-form" enctype="multipart/form-data">
                                
                                <!-- Current Logo Preview -->
                                <div class="form-group">
                                    <label class="control-label col-md-3">Current Invoice Logo</label>
                                    <div class="col-md-9">
                                        <div class="logo-preview">
                                            <?php if ($invoice_logo && file_exists($invoice_logo)) { ?>
                                                <img src="<?php echo htmlspecialchars($invoice_logo); ?>?t=<?php echo time(); ?>" alt="Invoice Logo" />
                                                <p style="margin-top:10px; font-size:12px; color:#666;">
                                                    Path: <?php echo htmlspecialchars($invoice_logo); ?>
                                                </p>
                                            <?php } else { ?>
                                                <p class="no-logo"><i class="fa fa-image fa-3x" style="color:#ddd;"></i><br>No logo uploaded</p>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Upload New Logo -->
                                <div class="form-group">
                                    <label class="control-label col-md-3">Upload New Logo</label>
                                    <div class="col-md-9">
                                        <div class="upload-btn-wrapper">
                                            <button class="btn btn-info" type="button"><i class="fa fa-upload"></i> Choose File</button>
                                            <input type="file" name="invoice_logo_file" id="invoice_logo_file" accept="image/*" />
                                        </div>
                                        <div class="file-name-display" id="file-name-display">No file chosen</div>
                                        <p class="help-block">Allowed formats: JPG, JPEG, PNG, GIF, WEBP, AVIF. Recommended size: 400x150 pixels</p>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <!-- Receipt Name -->
                                <div class="form-group">
                                    <label class="control-label col-md-3">Receipt/Invoice Name</label>
                                    <div class="col-md-9">
                                        <input type="text" name="receipt_name" class="form-control" value="<?php echo htmlspecialchars($receipt_name); ?>" placeholder="e.g., BAKERY SHOP" />
                                        <p class="help-block">This name appears on printed receipts and invoices</p>
                                    </div>
                                </div>
                                
                                <!-- Receipt Address -->
                                <div class="form-group">
                                    <label class="control-label col-md-3">Receipt Address</label>
                                    <div class="col-md-9">
                                        <textarea name="receipt_address" class="form-control" rows="3" placeholder="123 Main Street, City, Country"><?php echo htmlspecialchars($receipt_address); ?></textarea>
                                    </div>
                                </div>
                                
                                <!-- Receipt Phone -->
                                <div class="form-group">
                                    <label class="control-label col-md-3">Receipt Phone</label>
                                    <div class="col-md-9">
                                        <input type="text" name="receipt_phone" class="form-control" value="<?php echo htmlspecialchars($receipt_phone); ?>" placeholder="e.g., +44 123 456 7890" />
                                    </div>
                                </div>
                                
                                <!-- Receipt Email -->
                                <div class="form-group">
                                    <label class="control-label col-md-3">Receipt Email</label>
                                    <div class="col-md-9">
                                        <input type="email" name="receipt_email" class="form-control" value="<?php echo htmlspecialchars($receipt_email); ?>" placeholder="e.g., info@bakery.com" />
                                    </div>
                                </div>
                                
                                <!-- Receipt Footer -->
                                <div class="form-group">
                                    <label class="control-label col-md-3">Receipt Footer Text</label>
                                    <div class="col-md-9">
                                        <textarea name="receipt_footer" class="form-control" rows="2" placeholder="Thank you for your purchase!"><?php echo htmlspecialchars($receipt_footer); ?></textarea>
                                    </div>
                                </div>

                                <div class="form-actions">
                                    <div class="row">
                                        <div class="col-md-offset-3 col-md-9">
                                            <button type="submit" name="save_invoice_settings" class="btn green btn-lg">
                                                <i class="fa fa-check"></i> Save Settings
                                            </button>
                                        </div>
                                    </div>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Preview Column -->
                <div class="col-md-4">
                    <div class="portlet light bordered">
                        <div class="portlet-title">
                            <div class="caption">
                                <i class="fa fa-eye"></i> Receipt Preview
                            </div>
                        </div>
                        <div class="portlet-body">
                            <div class="receipt-preview">
                                <div class="header">
                                    <?php if ($invoice_logo && file_exists($invoice_logo)) { ?>
                                        <img src="<?php echo htmlspecialchars($invoice_logo); ?>?t=<?php echo time(); ?>" alt="Logo" />
                                    <?php } ?>
                                    <h3><?php echo htmlspecialchars($receipt_name ?: 'Your Business Name'); ?></h3>
                                    <?php if ($receipt_address) { ?>
                                        <p><?php echo nl2br(htmlspecialchars($receipt_address)); ?></p>
                                    <?php } ?>
                                    <?php if ($receipt_phone) { ?>
                                        <p>Tel: <?php echo htmlspecialchars($receipt_phone); ?></p>
                                    <?php } ?>
                                    <?php if ($receipt_email) { ?>
                                        <p><?php echo htmlspecialchars($receipt_email); ?></p>
                                    <?php } ?>
                                </div>
                                <div style="text-align:center; color:#999; font-size:11px;">
                                    --------------------------------<br>
                                    SAMPLE ITEMS<br>
                                    --------------------------------<br>
                                    Bread Loaf x 2 .......... £4.00<br>
                                    Croissant x 3 ........... £6.00<br>
                                    Coffee Cake ............. £8.50<br>
                                    --------------------------------<br>
                                    TOTAL: £18.50<br>
                                    --------------------------------<br>
                                    <?php if ($receipt_footer) { ?>
                                        <br><?php echo nl2br(htmlspecialchars($receipt_footer)); ?>
                                    <?php } else { ?>
                                        <br>Thank you!
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('common/footer.php'); ?>

<script>
    // Display selected file name
    document.getElementById('invoice_logo_file').addEventListener('change', function() {
        var fileName = this.files[0] ? this.files[0].name : 'No file chosen';
        document.getElementById('file-name-display').textContent = fileName;
    });
</script>
</body>
</html>
