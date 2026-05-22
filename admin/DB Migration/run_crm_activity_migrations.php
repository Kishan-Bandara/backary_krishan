<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=bakery;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $files = [
        '2026-04-07_01_create_crm_activity_master.sql',
        '2026-04-07_02_create_crm_activity_line.sql'
    ];

    foreach ($files as $file) {
        if (!file_exists($file)) {
            echo "File {$file} not found.\n";
            continue;
        }

        $sql = file_get_contents($file);
        if ($sql === false) {
            echo "Unable to read {$file}.\n";
            continue;
        }

        try {
            $pdo->exec($sql);
            echo "Executed {$file} successfully.\n";
        } catch (Exception $e) {
            echo "Error in {$file}: " . $e->getMessage() . "\n";
        }
    }

    echo "CRM activity migrations completed.\n";
} catch (Exception $e) {
    echo 'Migration failed: ' . $e->getMessage() . "\n";
}
?>