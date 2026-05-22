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

$csrfToken       = isset($input['csrf_token'])        ? (string)$input['csrf_token']        : '';
$standingOrderId = isset($input['standing_order_id']) ? (int)$input['standing_order_id']     : 0;

// Validate CSRF
if (empty($_SESSION['delete_so_csrf']) || !hash_equals($_SESSION['delete_so_csrf'], $csrfToken)) {
    echo json_encode(['status' => false, 'message' => 'Security token mismatch']);
    exit;
}

// Restrict to super admin only
if (!function_exists('isSuperAdmin') || !isSuperAdmin()) {
    echo json_encode(['status' => false, 'message' => 'Only super admin can delete standing orders']);
    exit;
}

if ($standingOrderId <= 0) {
    echo json_encode(['status' => false, 'message' => 'Invalid standing order ID']);
    exit;
}

try {
    $db = new Database();

    // Confirm the standing order exists and is active
    $so = $db->getRow('SELECT id, customer_id FROM standing_order WHERE id = ?', [$standingOrderId]);
    if (!$so) {
        echo json_encode(['status' => false, 'message' => 'Standing order not found']);
        exit;
    }

    $customerId = (int)$so['customer_id'];
    $today      = date('Y-m-d');

    // Find all future Standing Order invoices for this customer (delivery_date > today)
    $futureInvoices = $db->getRows(
        "SELECT invoice_h_id FROM invoice_hedder
         WHERE invoice_h_customer_id = ?
           AND invoice_h_order_note = 'Standing Order'
           AND invoice_h_delivery_date > ?",
        [$customerId, $today]
    );

    $deletedInvoices = 0;
    foreach ($futureInvoices as $inv) {
        $invId = (int)$inv['invoice_h_id'];
        $db->deleteRow('DELETE FROM invoice_details WHERE invoice_h_id = ?', [$invId]);
        $db->deleteRow('DELETE FROM invoice_hedder  WHERE invoice_h_id = ?', [$invId]);
        $deletedInvoices++;
    }

    // Delete standing order items then the standing order itself
    // (FK cascade would handle items, but explicit delete is safer)
    $db->deleteRow('DELETE FROM standing_order_item WHERE standing_order_id = ?', [$standingOrderId]);
    $db->deleteRow('DELETE FROM standing_order       WHERE id = ?',               [$standingOrderId]);

    echo json_encode([
        'status'  => true,
        'message' => 'Standing order deleted. ' . $deletedInvoices . ' future pending order(s) also removed.',
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
exit;
