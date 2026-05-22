<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('include/database.php');

$db = new Database();

// Check item_master columns
echo "<h3>item_master table columns:</h3>";
$columns = $db->getRows("SHOW COLUMNS FROM item_master");
echo "<pre>";
foreach ($columns as $col) {
    echo $col['Field'] . " - " . $col['Type'] . "\n";
}
echo "</pre>";

// Check if product 13 exists
echo "<h3>Product 13 data:</h3>";
$product = $db->getRow("SELECT * FROM item_master WHERE item_id = 13");
echo "<pre>";
print_r($product);
echo "</pre>";

// Try a simple update
echo "<h3>Testing simple update:</h3>";
try {
    $result = $db->updateRow("UPDATE item_master SET is_raw_material = 1 WHERE item_id = 13");
    echo "Update result: " . ($result ? "TRUE" : "FALSE") . "<br>";
    
    // Verify
    $check = $db->getRow("SELECT is_raw_material FROM item_master WHERE item_id = 13");
    echo "After update, is_raw_material = " . var_export($check['is_raw_material'] ?? 'NULL', true);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
