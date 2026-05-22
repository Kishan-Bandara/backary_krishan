<?php
include('../include/database.php');

try {
    $db = new Database();

    // Set existing invalid shipping_address_id values to NULL
    $db->updateRow("UPDATE standing_order SET shipping_address_id = NULL WHERE shipping_address_id = 0 OR shipping_address_id NOT IN (SELECT id FROM customer_shipping_address)");

    // Make shipping_address_id the same type as customer_shipping_address.id (int(10))
    $db->updateRow("ALTER TABLE `standing_order` MODIFY COLUMN `shipping_address_id` INT(10) NULL");

    echo "Migration completed successfully! (Foreign key constraint skipped for compatibility)\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>



