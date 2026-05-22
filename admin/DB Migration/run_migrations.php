<?php

$files = [
    '2025-11-27_create_customer_payment_options.sql'
];

try {
    $pdo = new PDO("mysql:host=localhost;dbname=bakery;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    foreach ($files as $file) {
        $sql = file_get_contents($file);
        if ($sql === false) {
            echo "Error reading $file\n";
            continue;
        }

        $pdo->exec($sql);
        echo "Successfully executed $file\n";
    }

    echo "Migration completed.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>



