<?php
require_once __DIR__ . '/../include/database.php';
$db = new Database();

// Check if column exists
$col = $db->getRow("SELECT COUNT(*) as c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'product_price_mapping' AND column_name = 'location_id'");
if ((int)$col['c'] === 0) {
    echo "Adding column location_id...\n";
    $db->insertRow("ALTER TABLE product_price_mapping ADD COLUMN location_id INT NULL AFTER price_type_id");
} else {
    echo "Column location_id already exists.\n";
}

// Drop old unique key if exists
$idx = $db->getRow("SELECT COUNT(*) as c FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'product_price_mapping' AND index_name = 'uq_product_price_type'");
if ((int)$idx['c'] > 0) {
    echo "Dropping old unique key uq_product_price_type...\n";
    $db->insertRow("ALTER TABLE product_price_mapping DROP INDEX uq_product_price_type");
} else {
    echo "Old unique key not found or already removed.\n";
}

// Add new unique key
$idx2 = $db->getRow("SELECT COUNT(*) as c FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'product_price_mapping' AND index_name = 'uq_product_price_type_location'");
if ((int)$idx2['c'] === 0) {
    echo "Adding unique key uq_product_price_type_location...\n";
    $db->insertRow("ALTER TABLE product_price_mapping ADD UNIQUE KEY uq_product_price_type_location (product_id, price_type_id, location_id)");
} else {
    echo "Unique key uq_product_price_type_location already exists.\n";
}

// Add index on location_id
$idx3 = $db->getRow("SELECT COUNT(*) as c FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'product_price_mapping' AND index_name = 'idx_location'" );
if ((int)$idx3['c'] === 0) {
    echo "Adding index idx_location...\n";
    $db->insertRow("ALTER TABLE product_price_mapping ADD KEY idx_location (location_id)");
} else {
    echo "Index idx_location already exists.\n";
}

echo "Migration complete.\n";
