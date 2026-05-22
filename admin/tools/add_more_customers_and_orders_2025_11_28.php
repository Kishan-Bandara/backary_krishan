<?php
require __DIR__ . '/../include/database.php';
try {
    $db = new Database();

    // Determine starting customer id
    $row = $db->getRow('SELECT MAX(customer_id) AS id FROM customer');
    $startId = ((int)($row['id'] ?? 0)) + 1;

    $date = '2025-11-28';
    $now = $date . ' 10:00:00';

    $added = [];

    // Reuse some item IDs that exist in the DB (fallbacks if missing)
    $items = $db->getRows('SELECT item_id FROM item_master ORDER BY item_id ASC LIMIT 10');
    $itemIds = array_column($items, 'item_id');
    if (empty($itemIds)) {
        throw new Exception('No items found in item_master to create invoice details.');
    }

    for ($i = 1; $i <= 20; $i++) {
        $custId = $startId + $i - 1;
        $custCode = 'TSTPG-' . date('Ymd') . '-' . $i;
        $custName = 'Test Page Cust ' . $i;
        $custEmail = 'test.page.' . $i . '@example.com';
        $custNic = str_pad((string)($custId % 100000), 10, '0', STR_PAD_LEFT);
        $activeCode = md5($custCode);

        // Skip if this code already exists
        $existing = $db->getRow('SELECT customer_id FROM customer WHERE customer_code = ?', [$custCode]);
        if ($existing) {
            echo "Customer code $custCode already exists, skipping\n";
            $newCustId = $existing['customer_id'];
        } else {
            $insertCustomer = $db->insertRow('INSERT INTO customer (customer_id, customer_code, customer_email, customer_password, is_active, locked, customer_title, customer_name, customer_nic, customer_avtive_code, customer_address, address_line_1, address_line_2, city, postal_code, customer_discount, customer_tell, customer_mobile, customer_note, customer_outstanding_balance, credit_limit, account_hold, abn_no, acn_no, vat_registered, gst_no, payment_terms_id, customer_logo, customer_price_type_id, new_customer) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                $custId,
                $custCode,
                $custEmail,
                'test',
                1,
                0,
                NULL,
                $custName,
                $custNic,
                $activeCode,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                0,
                0,
                0,
                NULL,
                0.00,
                0.00,
                0,
                NULL,
                NULL,
                0,
                NULL,
                NULL,
                NULL,
                NULL,
                1
            ]);
            echo "Inserted customer $custName with id $custId\n";
            $newCustId = $custId;
        }

        // Create invoice for this customer
        $code = 'EXTRA-20251128-' . $i;
        $existingInv = $db->getRow('SELECT invoice_h_id FROM invoice_hedder WHERE invoice_h_code = ?', [$code]);
        if ($existingInv) {
            echo "Invoice $code exists, skipping\n";
            continue;
        }

        // pick 1-3 items randomly from itemIds
        $countItems = min(3, count($itemIds));
        shuffle($itemIds);
        $chosen = array_slice($itemIds, 0, $countItems);

        $net = 0.0;
        foreach ($chosen as $itemId) {
            $itm = $db->getRow('SELECT item_normal_selling_price FROM item_master WHERE item_id = ?', [$itemId]);
            $price = $itm['item_normal_selling_price'] ?? 0;
            $qty = rand(1, 6);
            $net += $price * $qty;
        }
        $gross = $net;

        // Insert invoice header
        $db->insertRow('INSERT INTO invoice_hedder (invoice_h_code, invoice_h_customer_id, invoice_h_date, invoice_h_datetime, invoice_h_location, invoice_h_delivery_date, invoice_h_delivery_time, invoice_h_status, invoice_h_net_value, invoice_h_gross_value, add_by, invoice_h_delivery_name, invoice_h_delivery_address, invoice_h_delivery_contact_no) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            $code,
            $newCustId,
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

        $row = $db->getRow('SELECT invoice_h_id FROM invoice_hedder WHERE invoice_h_code = ? LIMIT 1', [$code]);
        $invoice_h_id = $row['invoice_h_id'] ?? null;
        if (!$invoice_h_id) {
            echo "Failed to create invoice $code\n";
            continue;
        }

        foreach ($chosen as $itemId) {
            $itm = $db->getRow('SELECT item_normal_selling_price FROM item_master WHERE item_id = ?', [$itemId]);
            $price = $itm['item_normal_selling_price'] ?? 0;
            $qty = rand(1, 6);
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

        echo "Inserted invoice $code for customer $newCustId\n";
        $added[] = ['customer_id' => $newCustId, 'customer_name' => $custName, 'invoice_code' => $code];
    }

    echo "\nDone. Added " . count($added) . " customers and invoices.\n";
    foreach ($added as $a) {
        echo "Customer {$a['customer_id']} - {$a['customer_name']} -> Invoice {$a['invoice_code']}\n";
    }

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
