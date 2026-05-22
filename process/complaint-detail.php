<?php
session_start();
include('../include/database.php');

$output = array('status' => false, 'message' => 'Unauthorized');

if (empty($_SESSION['LoginStatus']) || $_SESSION['LoginStatus'] != 'login_success') {
    echo json_encode($output, JSON_FORCE_OBJECT);
    exit;
}

$db = new Database();
$customer_id = (int)$_SESSION['Loginuserid'];
$complaint_id = isset($_POST['complaint_id']) ? (int)$_POST['complaint_id'] : 0;

if ($complaint_id <= 0) {
    $output['message'] = 'Invalid complaint.';
    echo json_encode($output, JSON_FORCE_OBJECT);
    exit;
}

$complaint = $db->getRow(
    'SELECT c.*, 
            im.item_name,
            cpit.name AS product_issue_name,
            csit.name AS service_issue_name
     FROM complaints c
     LEFT JOIN item_master im ON im.item_id = c.product_id
     LEFT JOIN complaint_product_issue_type cpit ON cpit.id = c.product_issue_type_id
     LEFT JOIN complaint_service_issue_type csit ON csit.id = c.service_issue_type_id
     WHERE c.complaint_id = ? AND c.customer_id = ?',
    [$complaint_id, $customer_id]
);

if ($complaint) {
    // Sanitize output
    $complaint['complaint_code'] = htmlspecialchars($complaint['complaint_code'], ENT_QUOTES, 'UTF-8');
    $complaint['complaint_type'] = htmlspecialchars($complaint['complaint_type'], ENT_QUOTES, 'UTF-8');
    $complaint['complaint_text'] = htmlspecialchars($complaint['complaint_text'], ENT_QUOTES, 'UTF-8');
    $complaint['item_name'] = htmlspecialchars($complaint['item_name'] ?? '', ENT_QUOTES, 'UTF-8');
    $complaint['product_issue_name'] = htmlspecialchars($complaint['product_issue_name'] ?? '', ENT_QUOTES, 'UTF-8');
    $complaint['service_issue_name'] = htmlspecialchars($complaint['service_issue_name'] ?? '', ENT_QUOTES, 'UTF-8');
    $complaint['status'] = htmlspecialchars($complaint['status'], ENT_QUOTES, 'UTF-8');
    $complaint['customer_outcome_message'] = htmlspecialchars($complaint['customer_outcome_message'] ?? '', ENT_QUOTES, 'UTF-8');
    $complaint['invoice_no'] = htmlspecialchars($complaint['invoice_no'] ?? '', ENT_QUOTES, 'UTF-8');

    $output['status'] = true;
    $output['complaint'] = $complaint;
} else {
    $output['message'] = 'Complaint not found.';
}

echo json_encode($output);
