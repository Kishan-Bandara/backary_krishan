<?php
echo "Starting test...\n";
try {
    include 'include/database.php';
    echo "Database.php included\n";
    $db = new Database();
    echo "Database connected successfully\n";

    $result = $db->datab->query('SHOW TABLES');
    $tables = $result->fetchAll(PDO::FETCH_COLUMN);
    echo "Total tables: " . count($tables) . "\n";

    $standingTables = array_filter($tables, function($t) {
        return strpos($t, 'standing') !== false;
    });
    echo "Standing tables: " . implode(', ', $standingTables) . "\n";

    if (in_array('standing_order', $tables)) {
        $count = $db->datab->query('SELECT COUNT(*) FROM standing_order')->fetchColumn();
        echo "Standing orders count: $count\n";
    }

} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
echo "Test completed\n";
?>



