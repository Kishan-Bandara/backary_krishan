<?php
// Direct PDO connection for DDL statements
try {
    $pdo = new PDO("mysql:host=localhost;dbname=bakery;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = file_get_contents('2025-11-23_create_delivery_route_master.sql');

    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach($statements as $stmt) {
        if(!empty($stmt)) {
            try {
                $pdo->exec($stmt);
                echo 'Executed: ' . substr($stmt, 0, 50) . '...' . PHP_EOL;
            } catch(Exception $e) {
                echo 'Error: ' . $e->getMessage() . PHP_EOL;
            }
        }
    }

    echo 'Migration completed successfully.' . PHP_EOL;
} catch (Exception $e) {
    echo 'Migration failed: ' . $e->getMessage() . PHP_EOL;
}
?>



