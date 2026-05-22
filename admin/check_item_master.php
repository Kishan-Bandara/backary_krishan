<?php
include 'include/database.php';
try {
    $db = new Database();
    echo "Connected to database\n";

    // Check if new columns exist
    $result = $db->datab->query("DESCRIBE item_master");
    $columns = $result->fetchAll(PDO::FETCH_COLUMN);
    echo "Current columns in item_master:\n";
    foreach($columns as $col) {
        echo "- $col\n";
    }

    // Check for new columns
    $newColumns = [
        'nutritional_label', 'sale_or_return', 'product_specification', 'live',
        'hide_to_all_customers', 'wholesale_price', 'retail_price', 'item_weight_g',
        'pack_weight_g', 'minimum_order', 'description', 'default_label',
        'food_declarations', 'seasonal_rule', 'avail_monday', 'avail_tuesday',
        'avail_wednesday', 'avail_thursday', 'avail_friday', 'avail_saturday',
        'avail_sunday', 'unit_of_measure', 'pack_type'
    ];

    $missing = [];
    foreach($newColumns as $col) {
        if (!in_array($col, $columns)) {
            $missing[] = $col;
        }
    }

    if (empty($missing)) {
        echo "\nAll new columns are present!\n";
    } else {
        echo "\nMissing columns: " . implode(', ', $missing) . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>



