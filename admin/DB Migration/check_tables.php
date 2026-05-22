<?php
include('../include/database.php');

$db = new Database();

echo "Checking standing_order table:\n";
try {
    $columns = $db->getRows('DESCRIBE standing_order');
    foreach($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nChecking foreign keys on standing_order:\n";
try {
    $fks = $db->getRows("SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'standing_order' AND TABLE_SCHEMA = 'bakery' AND REFERENCED_TABLE_NAME IS NOT NULL");
    foreach($fks as $fk) {
        echo $fk['CONSTRAINT_NAME'] . ": " . $fk['COLUMN_NAME'] . " -> " . $fk['REFERENCED_TABLE_NAME'] . "." . $fk['REFERENCED_COLUMN_NAME'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>



