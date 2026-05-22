<?php
/**
 * Batch Tracking Migration
 * Run this file once to add batch tracking support to the database.
 * URL: /admin/process/batch-tracking-migration.php
 */

include('../include/database.php');

$db = new Database();
$messages = [];

try {
    // 1. Add batch_tracking column to item_master
    $col = $db->getRow("SHOW COLUMNS FROM item_master LIKE 'batch_tracking'");
    if (!$col) {
        $db->insertRow("ALTER TABLE item_master ADD COLUMN batch_tracking ENUM('NONE','BATCH','SERIAL') NOT NULL DEFAULT 'NONE' AFTER is_raw_material");
        $messages[] = "Added batch_tracking column to item_master.";
    } else {
        $messages[] = "batch_tracking column already exists in item_master.";
    }

    // 2. Create batch_master table
    $db->insertRow("CREATE TABLE IF NOT EXISTS batch_master (
        batch_id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        batch_no VARCHAR(100) NOT NULL,
        expiry_date DATE DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_batch (product_id, batch_no),
        INDEX idx_product (product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
    $messages[] = "batch_master table created (or already exists).";

    // 3. Add batch_id column to fifo table
    $col = $db->getRow("SHOW COLUMNS FROM fifo LIKE 'batch_id'");
    if (!$col) {
        $db->insertRow("ALTER TABLE fifo ADD COLUMN batch_id INT DEFAULT NULL AFTER ft_type");
        $messages[] = "Added batch_id column to fifo.";
    } else {
        $messages[] = "batch_id column already exists in fifo.";
    }

    // 4. Add batch_id column to grn_details table
    $col = $db->getRow("SHOW COLUMNS FROM grn_details LIKE 'batch_id'");
    if (!$col) {
        $db->insertRow("ALTER TABLE grn_details ADD COLUMN batch_id INT DEFAULT NULL");
        $messages[] = "Added batch_id column to grn_details.";
    } else {
        $messages[] = "batch_id column already exists in grn_details.";
    }

    // 5. Add batch_id column to stock_transfer_items table
    $col = $db->getRow("SHOW COLUMNS FROM stock_transfer_items LIKE 'batch_id'");
    if (!$col) {
        $db->insertRow("ALTER TABLE stock_transfer_items ADD COLUMN batch_id INT DEFAULT NULL");
        $messages[] = "Added batch_id column to stock_transfer_items.";
    } else {
        $messages[] = "batch_id column already exists in stock_transfer_items.";
    }

    // 6. Add batch_id column to stock_issue_items table
    $col = $db->getRow("SHOW COLUMNS FROM stock_issue_items LIKE 'batch_id'");
    if (!$col) {
        $db->insertRow("ALTER TABLE stock_issue_items ADD COLUMN batch_id INT DEFAULT NULL");
        $messages[] = "Added batch_id column to stock_issue_items.";
    } else {
        $messages[] = "batch_id column already exists in stock_issue_items.";
    }

    echo "<h2>Batch Tracking Migration</h2>";
    echo "<ul>";
    foreach ($messages as $msg) {
        echo "<li style='color:green;'>&#10003; " . htmlspecialchars($msg) . "</li>";
    }
    echo "</ul>";
    echo "<p><strong>Migration completed successfully!</strong></p>";

} catch (Exception $e) {
    echo "<h2>Migration Error</h2>";
    echo "<p style='color:red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    if (!empty($messages)) {
        echo "<h3>Completed before error:</h3><ul>";
        foreach ($messages as $msg) {
            echo "<li>" . htmlspecialchars($msg) . "</li>";
        }
        echo "</ul>";
    }
}
