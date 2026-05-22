<?php
require __DIR__ . '/../include/database.php';
try {
    $db = new Database();
    $rows = $db->getRows("SELECT invoice_h_delivery_date as d, COUNT(*) as cnt FROM invoice_hedder WHERE invoice_h_status = 1 GROUP BY invoice_h_delivery_date ORDER BY invoice_h_delivery_date DESC");
    foreach ($rows as $r) {
        echo ($r['d'] ?? 'NULL') . " " . $r['cnt'] . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
