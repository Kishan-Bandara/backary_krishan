<?php
require __DIR__ . '/../include/database.php';
try {
    $db = new Database();

    $testInvoices = [
        ['customer_id' => 241, 'items' => [[1, 5], [2, 1]]],
        ['customer_id' => 289, 'items' => [[1, 3], [3, 2]]],
        ['customer_id' => 305, 'items' => [[2, 2], [1, 4]]],
        ['customer_id' => 306, 'items' => [[3, 5]]],
        ['customer_id' => 307, 'items' => [[1, 1], [2, 1], [3, 1]]],
    ];

    $date = '2025-11-28';
    $now = $date . ' 09:00:00';

    foreach ($testInvoices as $index => $inv) {
        $code = 'TEST-20251128-' . ($index + 1);
        // Skip if already exists
        $existing = $db->getRow('SELECT invoice_h_id FROM invoice_hedder WHERE invoice_h_code = ?', [$code]);
        if ($existing) {
            echo "Skipping existing invoice code: $code\n";
            continue;
        }

        // Calculate net and gross (simple sum of item price * qty)
        $net = 0.00;
        foreach ($inv['items'] as $it) {
            $itm = $db->getRow('SELECT item_normal_selling_price FROM item_master WHERE item_id = ?', [$it[0]]);
            $price = $itm['item_normal_selling_price'] ?? 0;
            $net += ($price * $it[1]);
        }
        $gross = $net; // ignore VAT for test

        // Insert invoice header
        $insert = $db->insertRow('INSERT INTO invoice_hedder (invoice_h_code, invoice_h_customer_id, invoice_h_date, invoice_h_datetime, invoice_h_location, invoice_h_delivery_date, invoice_h_delivery_time, invoice_h_status, invoice_h_net_value, invoice_h_gross_value, add_by, invoice_h_delivery_name, invoice_h_delivery_address, invoice_h_delivery_contact_no) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            $code,
            $inv['customer_id'],
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

        // Retrieve inserted invoice id
        $row = $db->getRow('SELECT invoice_h_id FROM invoice_hedder WHERE invoice_h_code = ? LIMIT 1', [$code]);
        $invoice_h_id = $row['invoice_h_id'] ?? null;
        if (!$invoice_h_id) {
            echo "Failed to create invoice $code\n";
            continue;
        }

        // Insert invoice details
        foreach ($inv['items'] as $it) {
            $itemId = $it[0];
            $qty = $it[1];
            $itm = $db->getRow('SELECT item_normal_selling_price FROM item_master WHERE item_id = ?', [$itemId]);
            $price = $itm['item_normal_selling_price'] ?? 0;
            $total = $price * $qty;

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
                $total
            ]);
        }

        echo "Inserted invoice $code for customer {$inv['customer_id']}\n";
    }

    echo "Done.\n";
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
