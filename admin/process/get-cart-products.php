<?php
/**
 * Get products for cart order page
 * Returns products with stock and pricing information
 */
ob_start();
error_reporting(0);
session_start();

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Check includes
if (!file_exists('../include/database.php')) {
    echo json_encode(['error' => 'Database file not found']);
    exit;
}

include('../include/database.php');

// For AJAX product loading, we don't need full permission check
// Just verify the session exists
if (!isset($_SESSION['Status']) || $_SESSION['Status'] !== 'login_success') {
    // Return empty array instead of error for unauthenticated requests
    // The main page will handle redirect if needed
    echo json_encode([]);
    exit;
}

try {
    $db = new Database();
    
    $category_id = isset($_GET['category_id']) ? $_GET['category_id'] : null;
    $location = isset($_GET['location']) ? $_GET['location'] : (isset($_SESSION['location']) ? $_SESSION['location'] : 1);
    $customer_id = isset($_GET['customer_id']) ? $_GET['customer_id'] : null;
    $hasAllowInSalesColumn = (bool) $db->getRow("SHOW COLUMNS FROM item_master LIKE 'allow_in_sales'");

    // Build query based on category filter - only products allowed in sales
    $query = 'SELECT * FROM item_master WHERE item_active = "Y"';
    $params = [];
    if ($hasAllowInSalesColumn) {
        $query .= ' AND (allow_in_sales = 1 OR allow_in_sales IS NULL)';
    }
    if ($category_id && $category_id != '0') {
        $query .= ' AND item_category = ?';
        $params[] = $category_id;
    }
    $query .= ' ORDER BY item_name ASC';
    $products = $db->getRows($query, $params);
    
    // Check if products query returned results
    if ($products === false) {
        $products = [];
    }
    
    $result = [];
    
    foreach ($products as $product) {
        // Get stock quantity from FIFO (note: column is ft_blanace - typo in database)
        $stock = $db->getRow(
            'SELECT SUM(ft_blanace) as qty FROM fifo WHERE ft_item = ? AND ft_location = ?',
            [$product['item_id'], $location]
        );
        
        // Get price - use normal selling price or customer-specific if available
        $price = isset($product['item_normal_selling_price']) ? $product['item_normal_selling_price'] : 0;
        
        // If customer is selected, check for customer-specific pricing only if the table exists
        $has_customer_price_table = (bool) $db->getRow("SHOW TABLES LIKE 'product_customer_price_mapping'");
        if ($customer_id && $has_customer_price_table) {
            // Check for customer-specific price mapping
            $customer_price = $db->getRow(
                'SELECT price FROM product_customer_price_mapping WHERE item_id = ? AND customer_id = ?',
                [$product['item_id'], $customer_id]
            );
            
            if ($customer_price && isset($customer_price['price']) && $customer_price['price']) {
                $price = $customer_price['price'];
            }
        }
        
        $result[] = [
            'item_id' => $product['item_id'],
            'item_code' => isset($product['item_code']) ? $product['item_code'] : '',
            'item_name' => isset($product['item_name']) ? $product['item_name'] : '',
            'price' => floatval($price),
            'stock_qty' => floatval(isset($stock['qty']) ? $stock['qty'] : 0),
            'category_id' => isset($product['item_category']) ? $product['item_category'] : 0,
            'vat' => isset($product['item_vat']) ? $product['item_vat'] : 0
        ];
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load products: ' . $e->getMessage()]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode(['error' => 'PHP Error: ' . $e->getMessage()]);
}
