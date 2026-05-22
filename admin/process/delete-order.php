<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
session_start();
include('../include/database.php');
include('../include/check_login.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$csrfToken = isset($input['csrf_token']) ? (string)$input['csrf_token'] : '';
$invoiceId = isset($input['invoice_id']) ? (int)$input['invoice_id']    : 0;

// Validate CSRF
if (empty($_SESSION['delete_order_csrf']) || !hash_equals($_SESSION['delete_order_csrf'], $csrfToken)) {
    echo json_encode(['status' => false, 'message' => 'Security token mismatch']);
    exit;
}

if ($invoiceId <= 0) {
    echo json_encode(['status' => false, 'message' => 'Invalid order ID']);
    exit;
}

try {
    $db = new Database();

    if (!isSuperAdmin()) {
        echo json_encode(['status' => false, 'message' => 'Only super admin can delete orders']);
        exit;
    }

    $order = $db->getRow(
        'SELECT invoice_h_id, invoice_h_status, invoice_h_order_note, invoice_h_delivery_date FROM invoice_hedder WHERE invoice_h_id = ?',
        [$invoiceId]
    );

    if (!$order) {
        echo json_encode(['status' => false, 'message' => 'Order not found']);
        exit;
    }

    $orderStatus   = (int)$order['invoice_h_status'];
    $isStandingOrder = (trim($order['invoice_h_order_note']) === 'Standing Order');
    $deliveryDate  = $order['invoice_h_delivery_date'];
    $today         = date('Y-m-d');

    // Allow deletion only when delivery date is strictly in the future
    $canDelete = ($deliveryDate > $today) && ($orderStatus === 0 || $isStandingOrder);
    if (!$canDelete) {
        echo json_encode(['status' => false, 'message' => 'Cannot delete: delivery date has already passed or is today']);
        exit;
    }

    $db->deleteRow('DELETE FROM invoice_details WHERE invoice_h_id = ?', [$invoiceId]);
    $db->deleteRow('DELETE FROM invoice_hedder  WHERE invoice_h_id = ?', [$invoiceId]);

    echo json_encode(['status' => true, 'message' => 'Order deleted successfully']);

} catch (Exception $e) {
    echo json_encode(['status' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
exit;
