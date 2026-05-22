<?php
$db = new PDO('mysql:host=localhost;dbname=bakery', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$files = [
    '2025-11-27_update_supplier_schema.sql',
    '2025-11-27_create_supplier_shipping_address.sql',
    '2025-11-27_create_supplier_payment_options.sql'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $sql = file_get_contents($file);
        try {
            $db->exec($sql);
            echo "Executed $file successfully.\n";
        } catch (Exception $e) {
            echo "Error in $file: " . $e->getMessage() . "\n";
        }
    } else {
        echo "File $file not found.\n";
    }
}
?>



