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

function getSupplierCertificationDocuments(Database $db, $supplierId)
{
    $documents = [];
    try {
        $rows = $db->getRows('SELECT * FROM supplier_certification_documents WHERE supplier_id = ? ORDER BY id ASC', [$supplierId]);
        foreach ($rows as $row) {
            $documents[] = [
                'id' => $row['id'] ?? 0,
                'file_path' => $row['file_path'] ?? '',
                'file_name' => $row['file_name'] ?: (!empty($row['file_path']) ? basename((string) $row['file_path']) : ''),
                'expire_date' => $row['expire_date'] ?? null,
                'updated_at' => $row['updated_at'] ?? $row['created_at'] ?? '',
            ];
        }
    } catch (Exception $e) {
        // continue without documents if the table is unavailable
    }
    return $documents;
}

if (isset($_POST['action']) && $_POST['action'] === 'update_certification_expire_date') {
    header('Content-Type: application/json');
    $documentId = (int) ($_POST['document_id'] ?? 0);
    $expireDate = trim($_POST['expire_date'] ?? '');
    if ($documentId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid document ID']);
        exit();
    }
    if ($expireDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $expireDate)) {
        echo json_encode(['success' => false, 'message' => 'Invalid date format']);
        exit();
    }
    try {
        $db = new Database();
        $db->updateRow(
            'UPDATE supplier_certification_documents SET expire_date = ? WHERE id = ?',
            [$expireDate === '' ? null : $expireDate, $documentId]
        );
        echo json_encode(['success' => true, 'message' => 'Expiry date updated']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()]);
    }
    exit();
}

if (isset($_POST['action']) && $_POST['action'] === 'delete_certification_pdf') {
    header('Content-Type: application/json');

    $documentId = (int) ($_POST['document_id'] ?? 0);
    if ($documentId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid certification document request']);
        exit();
    }

    try {
        $db = new Database();
        $document = $db->getRow('SELECT id, file_path FROM supplier_certification_documents WHERE id = ? LIMIT 1', [$documentId]);
        if (!$document) {
            echo json_encode(['success' => false, 'message' => 'Certification document not found']);
            exit();
        }

        if (!empty($document['file_path'])) {
            $filePath = dirname(__DIR__) . '/' . ltrim((string) $document['file_path'], '/');
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }

        $db->updateRow('DELETE FROM supplier_certification_documents WHERE id = ?', [$documentId]);
        echo json_encode(['success' => true, 'message' => 'Certification document deleted successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Delete failed: ' . $e->getMessage()]);
    }
    exit();
}

$message = '';
$MessageClass = '';
$db = null;

$supplierId = isset($_GET['supplierID']) ? (int) $_GET['supplierID'] : 0;
if ($supplierId <= 0) {
    header('location:index.php');
    exit();
}

try {
    $db = new Database();
    try {
        $db->getRows('CREATE TABLE IF NOT EXISTS `supplier_email_accounts` (
            `id` int(10) NOT NULL AUTO_INCREMENT,
            `supplier_id` int(10) NOT NULL,
            `email_address` varchar(255) NOT NULL,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `supplier_id` (`supplier_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
        $db->getRows('CREATE TABLE IF NOT EXISTS `supplier_certification_documents` (
            `id` int(10) NOT NULL AUTO_INCREMENT,
            `supplier_id` int(10) NOT NULL,
            `file_path` varchar(255) DEFAULT NULL,
            `file_name` varchar(255) DEFAULT NULL,
            `expire_date` date DEFAULT NULL,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `supplier_id` (`supplier_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
        // Migration: drop unique constraint if it exists to allow multiple documents
        try {
            $db->getRows('ALTER TABLE `supplier_certification_documents` DROP INDEX `supplier_id`');
            $db->getRows('ALTER TABLE `supplier_certification_documents` ADD KEY `supplier_id` (`supplier_id`)');
        } catch (Exception $e) { /* index may already be non-unique */ }
        // Migration: add expire_date column if not present
        try {
            $db->getRows('ALTER TABLE `supplier_certification_documents` ADD COLUMN `expire_date` DATE DEFAULT NULL');
        } catch (Exception $e) { /* column may already exist */ }
    } catch (Exception $e) {
        // ignore table creation errors
    }
} catch (Exception $e) {
    $message = 'Database connection error: ' . $e->getMessage();
    $MessageClass = 'alert-danger';
}

$supplier = null;
$formData = [];
if ($db) {
    try {
        $supplier = $db->getRow('SELECT * FROM supplier WHERE supplier_id = ?', [$supplierId]);
        if (!$supplier) {
            header('location:index.php');
            exit();
        }

        // Populate form data
        $formData = [
            'supplier_code' => $supplier['supplier_code'] ?? '',
            'supplier_name' => $supplier['supplier_name'] ?? '',
            'supplier_email' => $supplier['supplier_email'] ?? '',
            'supplier_phone' => $supplier['supplier_contact_no'] ?? '',
            'supplier_mobile' => $supplier['supplier_mobile'] ?? '',
            'address_line_1' => $supplier['address_line_1'] ?? ($supplier['supplier_address'] ?? ''),
            'address_line_2' => $supplier['address_line_2'] ?? '',
            'city' => $supplier['city'] ?? '',
            'postal_code' => $supplier['postal_code'] ?? '',
            'credit_limit' => $supplier['credit_limit'] ?? '',
            'account_hold' => $supplier['account_hold'] ?? 0,
            'abn_no' => $supplier['abn_no'] ?? '',
            'acn_no' => $supplier['acn_no'] ?? '',
            'vat_registered' => $supplier['vat_registered'] ?? 0,
            'gst_no' => $supplier['gst_no'] ?? '',
            'payment_terms_id' => $supplier['payment_terms_id'] ?? '',
            'supplier_price_type_id' => $supplier['supplier_price_type_id'] ?? '',
            'supplier_note' => $supplier['supplier_note'] ?? '',
            'supplier_remarks' => $supplier['supplier_remarks'] ?? '',
            'is_active' => $supplier['is_active'] ?? 1,
            'locked' => $supplier['locked'] ?? 0,
            'min_order_amount' => $supplier['min_order_amount'] ?? '',
            'emergency_contact_name' => $supplier['emergency_contact_name'] ?? '',
            'emergency_contact_email' => $supplier['emergency_contact_email'] ?? '',
            'emergency_contact_telephone' => $supplier['emergency_contact_telephone'] ?? '',
            'custom_url_link' => $supplier['custom_url_link'] ?? '',
            'google_map_link' => $supplier['google_map_link'] ?? '',
            'contact_name' => $supplier['contact_name'] ?? '',
            'contact_email' => $supplier['contact_email'] ?? '',
            'contact_telephone' => $supplier['contact_telephone'] ?? '',
            'legal_name' => $supplier['legal_name'] ?? '',
            'trading_name' => $supplier['trading_name'] ?? '',
        ];

        $formData['supplier_additional_emails'] = [];
        try {
            $rows = $db->getRows('SELECT email_address FROM supplier_email_accounts WHERE supplier_id = ? ORDER BY id ASC', [$supplierId]) ?: [];
            foreach ($rows as $row) {
                $formData['supplier_additional_emails'][] = $row['email_address'];
            }
        } catch (Exception $e) {
            $formData['supplier_additional_emails'] = [];
        }
    } catch (Exception $e) {
        $message = 'Unable to load supplier record: ' . $e->getMessage();
        $MessageClass = 'alert-danger';
    }
}

$certificationDocuments = $db ? getSupplierCertificationDocuments($db, $supplierId) : [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_supplier'])) {
    try {
        $updateData = [
            'supplier_code' => trim($_POST['supplier_code'] ?? ''),
            'supplier_name' => trim($_POST['supplier_name'] ?? ''),
            'supplier_email' => trim($_POST['supplier_email'] ?? ''),
            'supplier_contact_no' => trim($_POST['supplier_phone'] ?? ''),
            'supplier_mobile' => trim($_POST['supplier_mobile'] ?? ''),
            'address_line_1' => trim($_POST['address_line_1'] ?? ''),
            'address_line_2' => trim($_POST['address_line_2'] ?? ''),
            'city' => trim($_POST['city'] ?? ''),
            'postal_code' => trim($_POST['postal_code'] ?? ''),
            'credit_limit' => str_replace([',', '$'], '', $_POST['credit_limit'] ?? ''),
            'account_hold' => isset($_POST['account_hold']) ? 1 : 0,
            'abn_no' => trim($_POST['abn_no'] ?? ''),
            'acn_no' => trim($_POST['acn_no'] ?? ''),
            'vat_registered' => isset($_POST['vat_registered']) ? 1 : 0,
            'gst_no' => trim($_POST['gst_no'] ?? ''),
            'payment_terms_id' => (int)($_POST['payment_terms_id'] ?? 0),
            'supplier_price_type_id' => (int)($_POST['supplier_price_type_id'] ?? 0),
            'supplier_note' => trim($_POST['supplier_note'] ?? ''),
            'supplier_remarks' => trim($_POST['supplier_remarks'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'locked' => isset($_POST['locked']) ? 1 : 0,
            'min_order_amount' => str_replace([',', '$'], '', $_POST['min_order_amount'] ?? ''),
            'emergency_contact_name' => trim($_POST['emergency_contact_name'] ?? ''),
            'emergency_contact_email' => trim($_POST['emergency_contact_email'] ?? ''),
            'emergency_contact_telephone' => trim($_POST['emergency_contact_telephone'] ?? ''),
            'custom_url_link' => trim($_POST['custom_url_link'] ?? ''),
            'google_map_link' => trim($_POST['google_map_link'] ?? ''),
            'contact_name' => trim($_POST['contact_name'] ?? ''),
            'contact_email' => trim($_POST['contact_email'] ?? ''),
            'contact_telephone' => trim($_POST['contact_telephone'] ?? ''),
            'legal_name' => trim($_POST['legal_name'] ?? ''),
            'trading_name' => trim($_POST['trading_name'] ?? ''),
        ];

        $supplierAdditionalEmails = [];
        if (isset($_POST['supplier_additional_emails']) && is_array($_POST['supplier_additional_emails'])) {
            foreach ($_POST['supplier_additional_emails'] as $additionalEmail) {
                $additionalEmail = trim($additionalEmail);
                if ($additionalEmail === '') {
                    continue;
                }
                if (!filter_var($additionalEmail, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Invalid additional supplier email address: ' . $additionalEmail);
                }
                $supplierAdditionalEmails[] = $additionalEmail;
            }
        }

        $certificationExpireDate = null;
        $rawExpireDate = trim($_POST['certification_expire_date'] ?? '');
        if ($rawExpireDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawExpireDate)) {
            $certificationExpireDate = $rawExpireDate;
        }

        $uploadedCertificationPdfs = [];
        if (isset($_FILES['certification_pdf']) && is_array($_FILES['certification_pdf']['name'])) {
            $existingCount = count($certificationDocuments);
            $maxDocs = 5;
            $slotsAvailable = max(0, $maxDocs - $existingCount);
            $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
            foreach ($_FILES['certification_pdf']['name'] as $i => $originalName) {
                if ((int) ($_FILES['certification_pdf']['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                if ((int) $_FILES['certification_pdf']['error'][$i] !== UPLOAD_ERR_OK || empty($originalName)) {
                    throw new Exception('Unable to upload certification document ' . ($i + 1) . '.');
                }
                $tmpName = $_FILES['certification_pdf']['tmp_name'][$i];
                $fileSize = (int) ($_FILES['certification_pdf']['size'][$i] ?? 0);
                $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
                $detectedMime = $finfo ? finfo_file($finfo, $tmpName) : ($_FILES['certification_pdf']['type'][$i] ?? '');
                if ($extension !== 'pdf') {
                    throw new Exception('Certification document must be a PDF file.');
                }
                if ($detectedMime !== '' && $detectedMime !== 'application/octet-stream' && !in_array($detectedMime, ['application/pdf', 'application/x-pdf'], true)) {
                    throw new Exception('Certification document must be a valid PDF file.');
                }
                if ($fileSize <= 0 || $fileSize > 5 * 1024 * 1024) {
                    throw new Exception('Certification PDF must be 5MB or less.');
                }
                if (count($uploadedCertificationPdfs) >= $slotsAvailable) {
                    throw new Exception('Maximum 5 certification documents allowed.');
                }
                $uploadedCertificationPdfs[] = ['tmp_name' => $tmpName, 'original_name' => $originalName, 'expire_date' => $certificationExpireDate];
            }
            if ($finfo) {
                finfo_close($finfo);
            }
        }

        // Validate required fields
        if (empty($updateData['supplier_name'])) {
            throw new Exception('Supplier name is required');
        }

        // Update supplier record
        $db->updateRow('UPDATE supplier SET
            supplier_code = ?, supplier_name = ?, supplier_email = ?, supplier_contact_no = ?,
            supplier_mobile = ?, address_line_1 = ?, address_line_2 = ?, city = ?, postal_code = ?,
            credit_limit = ?, account_hold = ?, abn_no = ?, acn_no = ?, vat_registered = ?,
            gst_no = ?, payment_terms_id = ?, supplier_price_type_id = ?, supplier_note = ?,
            supplier_remarks = ?, is_active = ?, locked = ?, min_order_amount = ?,
            emergency_contact_name = ?, emergency_contact_email = ?, emergency_contact_telephone = ?,
            custom_url_link = ?, google_map_link = ?, contact_name = ?, contact_email = ?,
            contact_telephone = ?, legal_name = ?, trading_name = ?
            WHERE supplier_id = ?',
            array_merge(array_values($updateData), [$supplierId])
        );

        $db->updateRow('DELETE FROM supplier_email_accounts WHERE supplier_id = ?', [$supplierId]);
        foreach ($supplierAdditionalEmails as $additionalEmail) {
            $db->insertRow(
                'INSERT INTO supplier_email_accounts (supplier_id, email_address) VALUES (?, ?)',
                [$supplierId, $additionalEmail]
            );
        }

        $certificationPdfUploaded = false;
        if (!empty($uploadedCertificationPdfs)) {
            $certificationDir = dirname(__DIR__) . '/uploads/supplier_certifications';
            if (!is_dir($certificationDir)) {
                mkdir($certificationDir, 0777, true);
            }
            foreach ($uploadedCertificationPdfs as $pdf) {
                $storedFileName = 'supplier_' . $supplierId . '_certification_' . time() . '_' . mt_rand(1000, 9999) . '.pdf';
                $targetPath = $certificationDir . '/' . $storedFileName;
                if (!move_uploaded_file($pdf['tmp_name'], $targetPath)) {
                    throw new RuntimeException('Failed to save certification PDF.');
                }
                $dbPath = 'uploads/supplier_certifications/' . $storedFileName;
                $db->insertRow(
                    'INSERT INTO supplier_certification_documents (supplier_id, file_path, file_name, expire_date) VALUES (?, ?, ?, ?)',
                    [$supplierId, $dbPath, $pdf['original_name'], $pdf['expire_date']]
                );
                $certificationPdfUploaded = true;
            }
        }

        $message = 'Supplier updated successfully!';
        if ($certificationPdfUploaded) {
            $message .= ' Certification document(s) uploaded.';
        }
        $MessageClass = 'alert-success';

        // Refresh form data
        $supplier = $db->getRow('SELECT * FROM supplier WHERE supplier_id = ?', [$supplierId]);
        $formData = array_merge($formData, $updateData);
        $formData['supplier_additional_emails'] = $supplierAdditionalEmails;
        $certificationDocuments = getSupplierCertificationDocuments($db, $supplierId);

    } catch (Exception $e) {
        $message = 'Error updating supplier: ' . $e->getMessage();
        $MessageClass = 'alert-danger';
    }
}

// Get dropdown data
$paymentTerms = [];
$priceTypes = [];
if ($db) {
    try {
        $paymentTerms = $db->getRows('SELECT * FROM payment_terms ORDER BY payment_terms_name') ?: [];
        $priceTypes = $db->getRows('SELECT * FROM price_type ORDER BY description') ?: [];
    } catch (Exception $e) {
        // ignore
    }
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
    <title>Edit Supplier</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <meta content="" name="description" />
    <meta content="" name="author" />
    <?php include('common/head.php'); ?>
    <link href="assets/global/plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/global/plugins/select2/css/select2-bootstrap.min.css" rel="stylesheet" type="text/css" />
    <style>
        .section-card {
            background: #ffffff;
            border: 1px solid #dde3ec;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 6px 18px rgba(52, 73, 94, 0.08);
            margin-bottom: 24px;
        }

        .section-card h4 {
            margin: -24px -24px 18px -24px;
            padding: 12px 16px;
            font-size: 15px;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #ffffff;
            background: linear-gradient(135deg, #3f51b5 0%, #00bcd4 100%);
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
            box-shadow: inset 0 -1px 0 rgba(255,255,255,0.15);
            text-shadow: 0 1px 0 rgba(0, 0, 0, 0.15);
        }

        .equal-height {
            display: flex;
            align-items: stretch;
            flex-wrap: wrap;
        }

        .equal-height > [class*='col-'] {
            display: flex;
            flex-direction: column;
        }

        .equal-height .section-card {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
        }

        .form-group.required .control-label:after {
            content: " *";
            color: #ff0000;
        }
    </style>
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
                <div class="page-bar">
                    <ul class="page-breadcrumb">
                        <li>
                            <a href="index.php">Home</a>
                            <i class="fa fa-circle"></i>
                        </li>
                        <li>
                            <a href="manage-supplier.php">Supplier Management</a>
                            <i class="fa fa-circle"></i>
                        </li>
                        <li>
                            <span>Edit Supplier</span>
                        </li>
                    </ul>
                    <div class="page-toolbar">
                        <a href="supplier_view.php?supplierID=<?php echo $supplierId; ?>" class="btn btn-default">
                            <i class="fa fa-eye"></i> View Supplier
                        </a>
                        <a href="manage-supplier.php" class="btn btn-default">
                            <i class="fa fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>

                <?php if ($message): ?>
                <div class="alert <?php echo $MessageClass; ?> alert-dismissable">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                    <?php echo h($message); ?>
                </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-12">
                        <div class="portlet light bordered">
                            <div class="portlet-title">
                                <div class="caption font-green">
                                    <i class="icon-pencil font-green"></i>
                                    <span class="caption-subject bold uppercase">Edit Supplier: <?php echo h($formData['supplier_name']); ?></span>
                                </div>
                            </div>
                            <div class="portlet-body">
                                <form action="" method="POST" class="form-horizontal" enctype="multipart/form-data">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="section-card">
                                                <h4>Basic Information</h4>
                                                <div class="form-group required">
                                                    <label class="control-label col-md-4">Supplier Code</label>
                                                    <div class="col-md-8">
                                                        <input type="text" class="form-control" name="supplier_code" value="<?php echo h($formData['supplier_code']); ?>" placeholder="SUP001">
                                                    </div>
                                                </div>
                                                <div class="form-group required">
                                                    <label class="control-label col-md-4">Supplier Name</label>
                                                    <div class="col-md-8">
                                                        <input type="text" class="form-control" name="supplier_name" value="<?php echo h($formData['supplier_name']); ?>" required>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-4">Legal Name</label>
                                                    <div class="col-md-8">
                                                        <input type="text" class="form-control" name="legal_name" value="<?php echo h($formData['legal_name']); ?>">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-4">Trading Name</label>
                                                    <div class="col-md-8">
                                                        <input type="text" class="form-control" name="trading_name" value="<?php echo h($formData['trading_name']); ?>">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-4">Remarks</label>
                                                    <div class="col-md-8">
                                                        <input type="text" class="form-control" name="supplier_remarks" value="<?php echo h($formData['supplier_remarks']); ?>">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="section-card">
                                                <h4>Contact Information</h4>
                                                <div class="form-group">
                                                    <label class="control-label col-md-4">Email</label>
                                                    <div class="col-md-8">
                                                        <input type="email" class="form-control" name="supplier_email" value="<?php echo h($formData['supplier_email']); ?>">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-4">Additional Email Accounts</label>
                                                    <div class="col-md-8">
                                                        <div id="supplier-additional-emails">
                                                            <?php foreach ($formData['supplier_additional_emails'] as $email): ?>
                                                                <div class="input-group additional-email-row" style="margin-bottom: 8px;">
                                                                    <input type="email" class="form-control" name="supplier_additional_emails[]" value="<?php echo h($email); ?>" placeholder="Additional email address">
                                                                    <span class="input-group-btn">
                                                                        <button type="button" class="btn btn-danger remove-additional-email"><i class="fa fa-trash"></i></button>
                                                                    </span>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                        <button type="button" class="btn btn-xs btn-primary" id="add-supplier-additional-email" style="margin-top: 5px;">
                                                            <i class="fa fa-plus"></i> Add Email
                                                        </button>
                                                        <span class="help-block">Add extra email addresses for supplier notifications.</span>
                                                    </div>
                                                </div>
                                                <div class="form-group required">
                                                    <label class="control-label col-md-4">Phone</label>
                                                    <div class="col-md-8">
                                                        <input type="text" class="form-control" name="supplier_phone" value="<?php echo h($formData['supplier_phone']); ?>" required>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-4">Mobile</label>
                                                    <div class="col-md-8">
                                                        <input type="text" class="form-control" name="supplier_mobile" value="<?php echo h($formData['supplier_mobile']); ?>">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-4">Contact Person</label>
                                                    <div class="col-md-8">
                                                        <input type="text" class="form-control" name="contact_name" value="<?php echo h($formData['contact_name']); ?>">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-4">Contact Email</label>
                                                    <div class="col-md-8">
                                                        <input type="email" class="form-control" name="contact_email" value="<?php echo h($formData['contact_email']); ?>">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-4">Contact Phone</label>
                                                    <div class="col-md-8">
                                                        <input type="text" class="form-control" name="contact_telephone" value="<?php echo h($formData['contact_telephone']); ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="section-card">
                                                <h4>Billing Address</h4>
                                                <div class="form-group">
                                                    <label class="control-label col-md-4">Address Line 1</label>
                                                    <div class="col-md-8">
                                                        <input type="text" class="form-control" name="address_line_1" value="<?php echo h($formData['address_line_1']); ?>">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-4">Address Line 2</label>
                                                    <div class="col-md-8">
                                                        <input type="text" class="form-control" name="address_line_2" value="<?php echo h($formData['address_line_2']); ?>">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-4">City</label>
                                                    <div class="col-md-8">
                                                        <input type="text" class="form-control" name="city" value="<?php echo h($formData['city']); ?>">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-4">Postal Code</label>
                                                    <div class="col-md-8">
                                                        <input type="text" class="form-control" name="postal_code" value="<?php echo h($formData['postal_code']); ?>">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="section-card">
                                                <h4>Financial & Tax</h4>
                                                <div class="form-group">
                                                    <label class="control-label col-md-4">Credit Limit</label>
                                                    <div class="col-md-8">
                                                        <div class="input-group">
                                                            <span class="input-group-addon"><?php include('currency.php'); ?></span>
                                                            <input type="text" class="form-control autoprice" name="credit_limit" value="<?php echo h($formData['credit_limit']); ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-4">Payment Terms</label>
                                                    <div class="col-md-8">
                                                        <select class="form-control select2" name="payment_terms_id">
                                                            <option value="">Select Payment Terms</option>
                                                            <?php foreach ($paymentTerms as $term): ?>
                                                            <option value="<?php echo $term['payment_terms_id']; ?>" <?php echo $formData['payment_terms_id'] == $term['payment_terms_id'] ? 'selected' : ''; ?>>
                                                                <?php echo h($term['payment_terms_name']); ?>
                                                            </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-4">Price Type</label>
                                                    <div class="col-md-8">
                                                        <select class="form-control select2" name="supplier_price_type_id">
                                                            <option value="">Select Price Type</option>
                                                            <?php foreach ($priceTypes as $type): ?>
                                                            <option value="<?php echo $type['id']; ?>" <?php echo $formData['supplier_price_type_id'] == $type['id'] ? 'selected' : ''; ?>>
                                                                <?php echo h($type['description']); ?>
                                                            </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-4">Min Order Amount</label>
                                                    <div class="col-md-8">
                                                        <div class="input-group">
                                                            <span class="input-group-addon"><?php include('currency.php'); ?></span>
                                                            <input type="text" class="form-control autoprice" name="min_order_amount" value="<?php echo h($formData['min_order_amount']); ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-4">GST Registered</label>
                                                    <div class="col-md-8">
                                                        <div class="checkbox">
                                                            <label>
                                                                <input type="checkbox" name="vat_registered" value="1" <?php echo $formData['vat_registered'] ? 'checked' : ''; ?>> Yes
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-4">GST Number</label>
                                                    <div class="col-md-8">
                                                        <input type="text" class="form-control" name="gst_no" value="<?php echo h($formData['gst_no']); ?>">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-4">ABN</label>
                                                    <div class="col-md-8">
                                                        <input type="text" class="form-control" name="abn_no" value="<?php echo h($formData['abn_no']); ?>">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-4">ACN</label>
                                                    <div class="col-md-8">
                                                        <input type="text" class="form-control" name="acn_no" value="<?php echo h($formData['acn_no']); ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="section-card">
                                                <h4>Emergency Contact</h4>
                                                <div class="form-group">
                                                    <label class="control-label col-md-4">Name</label>
                                                    <div class="col-md-8">
                                                        <input type="text" class="form-control" name="emergency_contact_name" value="<?php echo h($formData['emergency_contact_name']); ?>">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-4">Email</label>
                                                    <div class="col-md-8">
                                                        <input type="email" class="form-control" name="emergency_contact_email" value="<?php echo h($formData['emergency_contact_email']); ?>">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-4">Phone</label>
                                                    <div class="col-md-8">
                                                        <input type="text" class="form-control" name="emergency_contact_telephone" value="<?php echo h($formData['emergency_contact_telephone']); ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="section-card">
                                                <h4>Links</h4>
                                                <div class="form-group">
                                                    <label class="control-label col-md-4">Website</label>
                                                    <div class="col-md-8">
                                                        <input type="url" class="form-control" name="custom_url_link" value="<?php echo h($formData['custom_url_link']); ?>" placeholder="https://">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-4">Map Location</label>
                                                    <div class="col-md-8">
                                                        <input type="url" class="form-control" name="google_map_link" value="<?php echo h($formData['google_map_link']); ?>" placeholder="https://maps.google.com/...">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="section-card">
                                                <h4>Status & Settings</h4>
                                                <div class="form-group">
                                                    <label class="control-label col-md-4">Active</label>
                                                    <div class="col-md-8">
                                                        <div class="checkbox">
                                                            <label>
                                                                <input type="checkbox" name="is_active" value="1" <?php echo $formData['is_active'] ? 'checked' : ''; ?>> Active supplier
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-4">Account Hold</label>
                                                    <div class="col-md-8">
                                                        <div class="checkbox">
                                                            <label>
                                                                <input type="checkbox" name="account_hold" value="1" <?php echo $formData['account_hold'] ? 'checked' : ''; ?>> On hold
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-4">Certification PDF <small class="text-muted">(<?php echo count($certificationDocuments); ?>/5)</small></label>
                                                    <div class="col-md-8">
                                                        <?php if (count($certificationDocuments) < 5): ?>
                                                            <input type="file" class="form-control" name="certification_pdf[]" accept=".pdf,application/pdf" multiple>
                                                            <span class="help-block">PDF only, max 5MB each. Up to <?php echo 5 - count($certificationDocuments); ?> more file(s) can be added.</span>
                                                            <div style="margin-top: 6px;">
                                                                <label style="font-size: 12px; color: #555; font-weight: 600;">Expiry Date <span class="text-muted" style="font-weight: normal;">(for new document(s))</span></label>
                                                                <input type="date" class="form-control" name="certification_expire_date" style="max-width: 200px;">
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="text-muted" style="margin-top: 6px;">Maximum of 5 certification documents reached. Delete one to upload another.</div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($certificationDocuments)): ?>
                                                            <?php foreach ($certificationDocuments as $doc): ?>
                                                                <div class="well well-sm" style="margin-top: 8px; margin-bottom: 6px; background: #fff8e1; border-color: #ffe082;">
                                                                    <div style="font-weight: 600; color: #555; margin-bottom: 6px;">
                                                                        <i class="fa fa-file-pdf-o" style="color: #dc3545;"></i>
                                                                        <?php echo h($doc['file_name']); ?>
                                                                    </div>
                                                                    <a href="../<?php echo h($doc['file_path']); ?>" class="btn btn-sm btn-default" download="<?php echo h($doc['file_name']); ?>" target="_blank" rel="noopener">
                                                                        <i class="fa fa-download"></i> Download
                                                                    </a>
                                                                    <button type="button" class="btn btn-sm btn-danger delete-certification-pdf-btn" data-document-id="<?php echo (int) $doc['id']; ?>" style="margin-left: 8px;">
                                                                        <i class="fa fa-trash"></i> Delete
                                                                    </button>
                                                                    <?php if (!empty($doc['updated_at'])): ?>
                                                                        <span class="text-muted" style="margin-left: 10px; font-size: 12px;">Updated: <?php echo h(date('Y-m-d H:i', strtotime($doc['updated_at']))); ?></span>
                                                                    <?php endif; ?>
                                                                    <div style="margin-top: 8px;">
                                                                        <label style="font-size: 12px; color: #777; margin-bottom: 3px; display: block;">Expiry Date:</label>
                                                                        <div class="input-group input-group-sm" style="max-width: 220px;">
                                                                            <input type="date" class="form-control cert-expire-date-input" value="<?php echo h($doc['expire_date'] ?? ''); ?>" data-document-id="<?php echo (int) $doc['id']; ?>">
                                                                            <span class="input-group-btn">
                                                                                <button type="button" class="btn btn-sm btn-primary update-cert-expire-date-btn" data-document-id="<?php echo (int) $doc['id']; ?>" title="Save expiry date">
                                                                                    <i class="fa fa-check"></i>
                                                                                </button>
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <div class="text-muted" style="margin-top: 8px;">No certification documents uploaded.</div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-4">Locked</label>
                                                    <div class="col-md-8">
                                                        <div class="checkbox">
                                                            <label>
                                                                <input type="checkbox" name="locked" value="1" <?php echo $formData['locked'] ? 'checked' : ''; ?>> Locked account
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="section-card">
                                                <h4>Notes</h4>
                                                <div class="form-group">
                                                    <label class="control-label col-md-2">Notes</label>
                                                    <div class="col-md-10">
                                                        <textarea class="form-control" rows="4" name="supplier_note"><?php echo h($formData['supplier_note']); ?></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-actions">
                                        <div class="row">
                                            <div class="col-md-offset-3 col-md-9">
                                                <button type="submit" name="save_supplier" class="btn btn-success">
                                                    <i class="fa fa-save"></i> Save Changes
                                                </button>
                                                <a href="supplier_view.php?supplierID=<?php echo $supplierId; ?>" class="btn btn-default">
                                                    <i class="fa fa-times"></i> Cancel
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include('common/footer.php'); ?>

      <!--[if lt IE 9]>
<script src="assets/global/plugins/respond.min.js"></script>
<script src="assets/global/plugins/excanvas.min.js"></script> 
<![endif]-->
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
        <script src="assets/global/plugins/select2/js/select2.full.min.js" type="text/javascript"></script>
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
        <script type="text/template" id="shippingAddressTemplate">
        </script>
        <script>
            $(document).ready(function() {
                $('#add-supplier-additional-email').on('click', function () {
                    var html = '<div class="input-group additional-email-row" style="margin-bottom: 8px;">' +
                        '<input type="email" class="form-control" name="supplier_additional_emails[]" placeholder="Additional email address">' +
                        '<span class="input-group-btn">' +
                            '<button type="button" class="btn btn-danger remove-additional-email"><i class="fa fa-trash"></i></button>' +
                        '</span>' +
                    '</div>';
                    $('#supplier-additional-emails').append(html);
                });

                $('#supplier-additional-emails').on('click', '.remove-additional-email', function () {
                    $(this).closest('.additional-email-row').remove();
                });

                $(document).on('click', '.update-cert-expire-date-btn', function() {
                    var $btn = $(this);
                    var documentId = parseInt($btn.data('document-id'), 10);
                    var $input = $btn.closest('.input-group').find('.cert-expire-date-input');
                    var expireDate = $input.val();

                    if (!documentId) { return; }

                    $btn.prop('disabled', true);

                    $.ajax({
                        url: '',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'update_certification_expire_date',
                            document_id: documentId,
                            expire_date: expireDate
                        },
                        success: function(response) {
                            if (response && response.success) {
                                $btn.removeClass('btn-primary').addClass('btn-success');
                                setTimeout(function() {
                                    $btn.removeClass('btn-success').addClass('btn-primary').prop('disabled', false);
                                }, 1500);
                            } else {
                                alert((response && response.message) ? response.message : 'Failed to update expiry date');
                                $btn.prop('disabled', false);
                            }
                        },
                        error: function() {
                            alert('Error updating expiry date. Please try again.');
                            $btn.prop('disabled', false);
                        }
                    });
                });

                $(document).on('click', '.delete-certification-pdf-btn', function() {
                    var $btn = $(this);
                    var documentId = parseInt($btn.data('document-id'), 10);

                    if (!documentId) {
                        return;
                    }

                    if (!confirm('Delete this certification document?')) {
                        return;
                    }

                    $btn.prop('disabled', true);

                    $.ajax({
                        url: '',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'delete_certification_pdf',
                            document_id: documentId
                        },
                        success: function(response) {
                            if (response && response.success) {
                                location.reload();
                            } else {
                                alert((response && response.message) ? response.message : 'Failed to delete certification document');
                                $btn.prop('disabled', false);
                            }
                        },
                        error: function() {
                            alert('Error deleting certification document. Please try again.');
                            $btn.prop('disabled', false);
                        }
                    });
                });

                $('.select2').select2({
                    placeholder: "Select an option",
                    allowClear: true
                });

                $('.autoprice').autoNumeric('init', {
                    aSep: ',',
                    dGroup: 3,
                    aDec: '.',
                    mDec: 2
                });
            });
        </script>
</body>
</html>



