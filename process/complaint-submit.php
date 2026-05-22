<?php
session_start();
include('../include/database.php');

$output = array('status' => false, 'message' => '', 'class' => 'alert-danger');

// Check login
if (empty($_SESSION['LoginStatus']) || $_SESSION['LoginStatus'] != 'login_success') {
    $output['message'] = 'Please login to submit a complaint.';
    echo json_encode($output, JSON_FORCE_OBJECT);
    exit;
}

$db = new Database();
$customer_id = (int)$_SESSION['Loginuserid'];
$complaint_type = isset($_POST['complaint_type']) ? trim($_POST['complaint_type']) : '';

if (!in_array($complaint_type, ['Product', 'Service'])) {
    $output['message'] = 'Invalid complaint type.';
    echo json_encode($output, JSON_FORCE_OBJECT);
    exit;
}

$complaint_text = isset($_POST['complaint_text']) ? trim($_POST['complaint_text']) : '';
if (empty($complaint_text)) {
    $output['message'] = 'Please enter complaint details.';
    echo json_encode($output, JSON_FORCE_OBJECT);
    exit;
}

$date_of_purchase = !empty($_POST['date_of_purchase']) ? $_POST['date_of_purchase'] : null;
$invoice_no = !empty($_POST['invoice_no']) ? trim($_POST['invoice_no']) : null;

// Generate complaint code
$lastComplaint = $db->getRow('SELECT MAX(complaint_id) as max_id FROM complaints', []);
$newId = ($lastComplaint && $lastComplaint['max_id']) ? $lastComplaint['max_id'] + 1 : 1;
$complaint_code = 'CMP' . str_pad($newId, 5, '0', STR_PAD_LEFT);

// Handle file upload
$attachment = null;
if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $file_type = $_FILES['attachment']['type'];
    $file_size = $_FILES['attachment']['size'];
    
    if (!in_array($file_type, $allowed_types)) {
        $output['message'] = 'Only image files (JPG, PNG, GIF, WEBP) are allowed.';
        echo json_encode($output, JSON_FORCE_OBJECT);
        exit;
    }
    if ($file_size > 5 * 1024 * 1024) {
        $output['message'] = 'File size must be less than 5MB.';
        echo json_encode($output, JSON_FORCE_OBJECT);
        exit;
    }

    // Validate actual file content
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $real_type = $finfo->file($_FILES['attachment']['tmp_name']);
    if (!in_array($real_type, $allowed_types)) {
        $output['message'] = 'Invalid file type.';
        echo json_encode($output, JSON_FORCE_OBJECT);
        exit;
    }

    $upload_dir = '../uploads/complaints/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    $ext = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
    $safe_ext = preg_replace('/[^a-zA-Z0-9]/', '', $ext);
    $filename = $complaint_code . '_' . time() . '.' . $safe_ext;
    $target = $upload_dir . $filename;
    
    if (move_uploaded_file($_FILES['attachment']['tmp_name'], $target)) {
        $attachment = 'uploads/complaints/' . $filename;
    }
}

// Build insert based on type
$product_id = null;
$product_issue_type_id = null;
$service_issue_type_id = null;

if ($complaint_type === 'Product') {
    $product_id = !empty($_POST['product_id']) ? (int)$_POST['product_id'] : null;
    $product_issue_type_id = !empty($_POST['product_issue_type_id']) ? (int)$_POST['product_issue_type_id'] : null;
    
    if (empty($product_id)) {
        $output['message'] = 'Please select a product.';
        echo json_encode($output, JSON_FORCE_OBJECT);
        exit;
    }
    if (empty($product_issue_type_id)) {
        $output['message'] = 'Please select an issue type.';
        echo json_encode($output, JSON_FORCE_OBJECT);
        exit;
    }

    $purchasedProduct = $db->getRow(
        'SELECT id.invoice_d_item_id
         FROM invoice_details id
         INNER JOIN invoice_hedder ih ON ih.invoice_h_id = id.invoice_h_id
         WHERE ih.invoice_h_customer_id = ? AND id.invoice_d_item_id = ?
         LIMIT 1',
        [$customer_id, $product_id]
    );

    if (!$purchasedProduct) {
        $output['message'] = 'The selected product is not in your purchase history.';
        echo json_encode($output, JSON_FORCE_OBJECT);
        exit;
    }
} else {
    $service_issue_type_id = !empty($_POST['service_issue_type_id']) ? (int)$_POST['service_issue_type_id'] : null;
    if (empty($service_issue_type_id)) {
        $output['message'] = 'Please select an issue type.';
        echo json_encode($output, JSON_FORCE_OBJECT);
        exit;
    }
}

$defaultAssignment = null;
$assigned_user_id = null;
$initial_status = 'Open';

try {
    $defaultAssignment = $db->getRow(
        'SELECT u.userid AS assigned_user_id, u.username, u.first_name, u.last_name, u.email
         FROM complaint_default_assignment cda
         INNER JOIN users u ON u.userid = cda.user_id
         WHERE cda.complaint_type = ? AND u.activated = ? AND u.locked = ?
         LIMIT 1',
        [$complaint_type, 'Y', 'N']
    );

    if (!empty($defaultAssignment['assigned_user_id'])) {
        $assigned_user_id = (int)$defaultAssignment['assigned_user_id'];
        $initial_status = 'Assigned';
    }
} catch (Exception $e) {
    $defaultAssignment = null;
}

try {
    $db->insertRow(
        'INSERT INTO complaints (complaint_code, customer_id, complaint_type, product_id, product_issue_type_id, service_issue_type_id, complaint_text, date_of_purchase, invoice_no, attachment, status, assigned_user_id, created_at) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
        [$complaint_code, $customer_id, $complaint_type, $product_id, $product_issue_type_id, $service_issue_type_id, $complaint_text, $date_of_purchase, $invoice_no, $attachment, $initial_status, $assigned_user_id]
    );

    $savedComplaint = $db->getRow('SELECT complaint_id FROM complaints WHERE complaint_code = ? LIMIT 1', [$complaint_code]);
    $savedComplaintId = !empty($savedComplaint['complaint_id']) ? (int)$savedComplaint['complaint_id'] : null;

    if ($assigned_user_id && !empty($defaultAssignment['email'])) {
        try {
            require_once(__DIR__ . '/../admin/include/EmailService.php');

            $assigneeName = trim(($defaultAssignment['first_name'] ?? '') . ' ' . ($defaultAssignment['last_name'] ?? ''));
            if ($assigneeName === '') {
                $assigneeName = $defaultAssignment['username'];
            }

            $emailService = new EmailService();
            if ($emailService->isEnabled()) {
                $subject = 'New ' . $complaint_type . ' Complaint Assigned - ' . $complaint_code;
                $body = '<h3>New complaint assigned to you</h3>';
                $body .= '<p><strong>Complaint Code:</strong> ' . htmlspecialchars($complaint_code, ENT_QUOTES, 'UTF-8') . '</p>';
                $body .= '<p><strong>Complaint Type:</strong> ' . htmlspecialchars($complaint_type, ENT_QUOTES, 'UTF-8') . '</p>';
                $body .= '<p><strong>Customer:</strong> ' . htmlspecialchars($_SESSION['Loginusername'] ?? ('Customer #' . $customer_id), ENT_QUOTES, 'UTF-8') . '</p>';
                $body .= '<p><strong>Complaint Note:</strong><br>' . nl2br(htmlspecialchars($complaint_text, ENT_QUOTES, 'UTF-8')) . '</p>';
                if (!empty($invoice_no)) {
                    $body .= '<p><strong>Invoice No:</strong> ' . htmlspecialchars($invoice_no, ENT_QUOTES, 'UTF-8') . '</p>';
                }
                if ($savedComplaintId) {
                    $body .= '<p><a href="' . htmlspecialchars(site_url() . 'admin/resolve-complaint.php?id=' . $savedComplaintId, ENT_QUOTES, 'UTF-8') . '">Open complaint in admin panel</a></p>';
                }

                $emailService->send(
                    array('email' => $defaultAssignment['email'], 'name' => $assigneeName),
                    $subject,
                    $body,
                    array(),
                    'complaint_assignment',
                    $savedComplaintId
                );
            }
        } catch (Exception $emailException) {
            error_log('Complaint default assignment email failed: ' . $emailException->getMessage());
        }
    }

    $output['status'] = true;
    if ($assigned_user_id) {
        $output['message'] = 'Your complaint (' . $complaint_code . ') has been submitted and assigned to the responsible person.';
    } else {
        $output['message'] = 'Your complaint (' . $complaint_code . ') has been submitted successfully. We will review it shortly.';
    }
    $output['class'] = 'alert-success';
} catch (Exception $e) {
    $output['message'] = 'Failed to submit complaint. Please try again.';
}

echo json_encode($output, JSON_FORCE_OBJECT);
