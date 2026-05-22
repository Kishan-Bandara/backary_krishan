<?php
file_put_contents('test_output.txt', "Testing database connection\n");
try {
    include 'include/database.php';
    file_put_contents('test_output.txt', "Database class included\n", FILE_APPEND);
    $db = new Database();
    file_put_contents('test_output.txt', "Database connected\n", FILE_APPEND);
    $tables = $db->datab->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    file_put_contents('test_output.txt', "Found " . count($tables) . " tables\n", FILE_APPEND);
    $standingTables = array_filter($tables, function($t) { return strpos($t, 'standing') !== false; });
    file_put_contents('test_output.txt', "Standing tables: " . implode(', ', $standingTables) . "\n", FILE_APPEND);
} catch (Exception $e) {
    file_put_contents('test_output.txt', "Error: " . $e->getMessage() . "\n", FILE_APPEND);
}
file_put_contents('test_output.txt', "Test complete\n", FILE_APPEND);
?>



