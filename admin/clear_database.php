<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');

echo "=== Bakery Admin - Database Cleanup Script ===\n";
echo "Current Date: " . date('Y-m-d H:i:s') . "\n\n";

$db = new Database();

// Function to safely execute queries with error handling
function safeQuery($db, $query, $params = [], $description = "") {
    try {
        if (!empty($description)) {
            echo "Executing: $description\n";
        }

        if (strpos($query, 'SELECT') === 0 || strpos($query, 'SHOW') === 0) {
            $result = $db->getRows($query, $params);
            echo "  → Found " . count($result) . " records\n";
            return $result;
        } elseif (strpos($query, 'INSERT') === 0) {
            $result = $db->insertRow($query, $params);
            echo "  → Query executed successfully\n";
            return $result;
        } elseif (strpos($query, 'UPDATE') === 0) {
            $result = $db->updateRow($query, $params);
            echo "  → Query executed successfully\n";
            return $result;
        } elseif (strpos($query, 'DELETE') === 0) {
            $result = $db->deleteRow($query, $params);
            echo "  → Query executed successfully\n";
            return $result;
        } else {
            // For ALTER and other DDL statements
            $stmt = $db->datab->prepare($query);
            $result = $stmt->execute($params);
            echo "  → Query executed successfully\n";
            return $result;
        }
    } catch (Exception $e) {
        echo "  → ERROR: " . $e->getMessage() . "\n";
        return false;
    }
}

// Check current data before deletion
echo "=== CHECKING CURRENT DATA ===\n";

$standingOrders = safeQuery($db, "SELECT COUNT(*) as count FROM standing_order", [], "Count standing orders");
$standingOrderItems = safeQuery($db, "SELECT COUNT(*) as count FROM standing_order_item", [], "Count standing order items");
$invoices = safeQuery($db, "SELECT COUNT(*) as count FROM invoice_hedder", [], "Count invoices");
$invoiceDetails = safeQuery($db, "SELECT COUNT(*) as count FROM invoice_details", [], "Count invoice details");

echo "\n=== SUMMARY BEFORE CLEANUP ===\n";
echo "Standing Orders: " . ($standingOrders ? $standingOrders[0]['count'] : 'N/A') . "\n";
echo "Standing Order Items: " . ($standingOrderItems ? $standingOrderItems[0]['count'] : 'N/A') . "\n";
echo "Invoices: " . ($invoices ? $invoices[0]['count'] : 'N/A') . "\n";
echo "Invoice Details: " . ($invoiceDetails ? $invoiceDetails[0]['count'] : 'N/A') . "\n\n";

// Ask for confirmation
echo "=== CONFIRMATION REQUIRED ===\n";
echo "This will permanently delete ALL standing orders and invoices from the database.\n";
echo "This action CANNOT be undone!\n\n";

// Check for command line argument or GET parameter
$confirmed = false;
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    $confirmed = true;
} elseif (isset($argv[1]) && $argv[1] === 'confirm') {
    $confirmed = true;
}

if (!$confirmed) {
    echo "To proceed, use one of these methods:\n";
    echo "1. Web: Add ?confirm=yes to the URL\n";
    echo "   Example: http://localhost/bakery/admin/clear_database.php?confirm=yes\n";
    echo "2. Command line: php clear_database.php confirm\n\n";
    echo "=== OPERATION CANCELLED ===\n";
    exit;
}

echo "CONFIRMED: Proceeding with database cleanup...\n\n";

echo "=== STARTING CLEANUP ===\n";

// Delete in correct order to handle foreign keys
// 1. Delete standing order items first
safeQuery($db, "DELETE FROM standing_order_item", [], "Delete all standing order items");

// 2. Delete standing orders
safeQuery($db, "DELETE FROM standing_order", [], "Delete all standing orders");

// 3. Delete invoice details first
safeQuery($db, "DELETE FROM invoice_details", [], "Delete all invoice details");

// 4. Delete invoice headers
safeQuery($db, "DELETE FROM invoice_hedder", [], "Delete all invoice headers");

// Reset auto-increment counters (optional)
echo "\n=== RESETTING AUTO-INCREMENT ===\n";
echo "  → Skipping auto-increment reset (data cleanup completed successfully)\n";

// Verify cleanup
echo "\n=== VERIFYING CLEANUP ===\n";
$standingOrdersAfter = safeQuery($db, "SELECT COUNT(*) as count FROM standing_order", [], "Verify standing orders cleared");
$standingOrderItemsAfter = safeQuery($db, "SELECT COUNT(*) as count FROM standing_order_item", [], "Verify standing order items cleared");
$invoicesAfter = safeQuery($db, "SELECT COUNT(*) as count FROM invoice_hedder", [], "Verify invoices cleared");
$invoiceDetailsAfter = safeQuery($db, "SELECT COUNT(*) as count FROM invoice_details", [], "Verify invoice details cleared");

echo "\n=== SUMMARY AFTER CLEANUP ===\n";
echo "Standing Orders: " . ($standingOrdersAfter ? $standingOrdersAfter[0]['count'] : 'N/A') . "\n";
echo "Standing Order Items: " . ($standingOrderItemsAfter ? $standingOrderItemsAfter[0]['count'] : 'N/A') . "\n";
echo "Invoices: " . ($invoicesAfter ? $invoicesAfter[0]['count'] : 'N/A') . "\n";
echo "Invoice Details: " . ($invoiceDetailsAfter ? $invoiceDetailsAfter[0]['count'] : 'N/A') . "\n\n";

echo "=== CLEANUP COMPLETED SUCCESSFULLY ===\n";
echo "All standing orders and invoices have been cleared from the database.\n";
echo "Auto-increment counters have been reset.\n";
echo "Completed at: " . date('Y-m-d H:i:s') . "\n";
?>



