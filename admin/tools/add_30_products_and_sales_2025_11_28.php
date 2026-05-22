<?php
require __DIR__ . '/../include/database.php';
try {
    $db = new Database();

    $date = '2025-11-28';
    $now = $date . ' 11:00:00';

    // get max item id
    $row = $db->getRow('SELECT MAX(item_id) as id FROM item_master');
    $startId = ((int)($row['id'] ?? 0)) + 1;

    // Customers who have orders on the date
    $custRows = $db->getRows('SELECT DISTINCT invoice_h_customer_id FROM invoice_hedder WHERE invoice_h_delivery_date = ? AND invoice_h_status = 1', [$date]);
    $custIds = array_map(function($r){ return $r['invoice_h_customer_id']; }, $custRows);
    if (empty($custIds)) {
        throw new Exception('No customers found for the date to assign sales to.');
    }

    $insertedProducts = [];
    // Insert 30 products
    for ($i = 1; $i <= 30; $i++) {
        $itemId = $startId + $i - 1;
        $code = 'TP' . date('Ymd') . '-' . $i;
        $name = 'Test Product ' . $i;
        $price = round(rand(100, 1000) / 10, 2); // 10.0 to 100.0
        $purchase = round($price * 0.6, 2);

        // avoid duplicates
        $existing = $db->getRow('SELECT item_id FROM item_master WHERE item_code = ?', [$code]);
        if ($existing) {
            echo "Product $code exists, skipping\n";
            $insertedProducts[] = $existing['item_id'];
            continue;
        }

        $columns = 'item_id, item_code, item_name, item_group, item_type, item_category, item_discription, item_uom, item_purchase_price, item_min_selling_price, item_normal_selling_price, others_selling_price, item_cash_selling_price, item_cradit_selling_price, item_promotion_status, item_promotion_price, item_image, imageParth, item_discount, item_active, item_warranty, item_barcode, is_hamper, item_has_sirial, item_vat, item_dispay_home, item_product_of_day, item_cod, item_mode, view_count, url, item_weight, immediate_pickups';
        $params = [
            $itemId,
            $code,
            $name,
            1,
            1,
            1,
            'Test product for pagination',
            1,
            $purchase,
            0.00,
            $price,
            $price,
            0.00,
            0.00,
            0,
            0.00,
            $name . '.png',
            'images/product_img/test/',
            0.00,
            'Y',
            '1',
            NULL,
            0,
            'N',
            'N',
            1,
            0,
            'enable',
            'Normal',
            0,
            $name . '-',
            0.5,
            'No'
        ];

        $placeholders = implode(', ', array_fill(0, count($params), '?'));
        $sql = 'INSERT INTO item_master (' . $columns . ') VALUES (' . $placeholders . ')';

        if (substr_count($sql, '?') !== count($params)) {
            echo "Placeholder/params mismatch for item $code\n";
            throw new Exception('Placeholder/params mismatch for item insertion');
        }

        $db->insertRow($sql, $params);

        $insertedProducts[] = $itemId;
        echo "Inserted product $name (ID: $itemId, Price: $price)\n";
    }

    // Create sales (one invoice per product) assigned to customers circularly
    $customerCount = count($custIds);
    $count = 0;
    foreach ($insertedProducts as $idx => $itemId) {
        $count++;
        $custId = $custIds[$idx % $customerCount];
        $invoiceCode = 'PRD-20251128-' . ($idx + 1);

        $existingInv = $db->getRow('SELECT invoice_h_id FROM invoice_hedder WHERE invoice_h_code = ?', [$invoiceCode]);
        if ($existingInv) {
            echo "Invoice $invoiceCode exists, skipping\n";
            continue;
        }

        $itm = $db->getRow('SELECT item_normal_selling_price FROM item_master WHERE item_id = ?', [$itemId]);
        $price = $itm['item_normal_selling_price'] ?? 0;
        $qty = rand(1,5);
        $net = $price * $qty;
        $gross = $net;

        // Insert header
        $db->insertRow('INSERT INTO invoice_hedder (invoice_h_code, invoice_h_customer_id, invoice_h_date, invoice_h_datetime, invoice_h_location, invoice_h_delivery_date, invoice_h_delivery_time, invoice_h_status, invoice_h_net_value, invoice_h_gross_value, add_by, invoice_h_delivery_name, invoice_h_delivery_address, invoice_h_delivery_contact_no) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            $invoiceCode,
            $custId,
            $date,
            $now,
            1,
            $date,
            'AM',
            1,
            $net,
            $gross,
            'test-script',
            'Test Delivery',
            'Test Address',
            '0000000000'
        ]);

        $row = $db->getRow('SELECT invoice_h_id FROM invoice_hedder WHERE invoice_h_code = ? LIMIT 1', [$invoiceCode]);
        $invoice_h_id = $row['invoice_h_id'] ?? null;
        if (!$invoice_h_id) {
            echo "Failed to create invoice $invoiceCode\n";
            continue;
        }

        $db->insertRow('INSERT INTO invoice_details (invoice_h_id, invoice_d_item_id, invoice_d_qty, invoice_d_item_price, invoice_d_vat, invoice_d_vat_rate, invoice_d_discount_value, invoice_d_discount_type, invoice_d_discount_total, invoice_d_item_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            $invoice_h_id,
            $itemId,
            $qty,
            $price,
            'N',
            0.00,
            0.00,
            0,
            0.00,
            $net
        ]);

        echo "Inserted invoice $invoiceCode for customer $custId with item $itemId (qty $qty)\n";
    }

    echo "\nDone. Inserted " . count($insertedProducts) . " products and associated invoices.\n";

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
