<?php
require __DIR__ . '/../include/database.php';

try {
    $db = new Database();

    $row = $db->getRow('SELECT MAX(customer_id) AS id FROM customer');
    $startId = ((int)($row['id'] ?? 0)) + 1;

    $created = [];

    for ($i = 0; $i < 10; $i++) {
        $customerId = $startId + $i;
        $customerCode = 'SEEDCUST-' . str_pad((string)$customerId, 5, '0', STR_PAD_LEFT);
        $customerName = 'Sample Customer ' . ($i + 1);
        $customerEmail = 'sample.customer.' . $customerId . '@example.com';
        $customerNic = str_pad((string)$customerId, 10, '0', STR_PAD_LEFT);
        $activeCode = md5($customerCode . '-' . microtime(true) . '-' . $i);
        $phone = '070' . str_pad((string)(1000000 + $i), 7, '0', STR_PAD_LEFT);

        $inserted = $db->insertRow(
            'INSERT INTO customer (customer_code, customer_email, customer_password, is_active, locked, customer_title, customer_name, customer_nic, customer_avtive_code, customer_address, address_line_1, address_line_2, city, postal_code, customer_discount, customer_tell, customer_mobile, customer_note, customer_outstanding_balance, credit_limit, account_hold, abn_no, acn_no, vat_registered, gst_no, payment_terms_id, customer_logo, customer_price_type_id, new_customer, RepeatInterval, RepeatUnit, legal_name, trading_name, customer_remarks, min_order_amount, emergency_contact_name, emergency_contact_email, emergency_contact_telephone, custom_url_link, google_map_link, contact_name, contact_email, contact_telephone)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, 0.00, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $customerCode,
                $customerEmail,
                'seed123',
                1,
                0,
                null,
                $customerName,
                $customerNic,
                $activeCode,
                'No. ' . (100 + $i) . ', Sample Street',
                'No. ' . (100 + $i) . ', Sample Street',
                null,
                'Colombo',
                '10000',
                (int)$phone,
                (int)$phone,
                'Seeded on 2026-02-19',
                0.00,
                0,
                null,
                null,
                0,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                0.00,
                null,
                null,
                null,
                null,
                null,
                $customerName,
                $customerEmail,
                $phone,
            ]
        );

        if (!$inserted) {
            throw new Exception('Failed to insert customer: ' . $customerName);
        }

        $newRow = $db->getRow('SELECT LAST_INSERT_ID() AS id');
        $newCustomerId = (int)($newRow['id'] ?? 0);
        if ($newCustomerId <= 0) {
            throw new Exception('Failed to read inserted customer id for: ' . $customerName);
        }

        $db->insertRow(
            'INSERT INTO customer_shipping_address (customer_id, address_label, address_line_1, address_line_2, city, postal_code, contact_no, attribute_1, attribute_2, attribute_3, is_default, contact_person_name, contact_person_phone, contact_person_email, remarks, note_to_deliver, delivery_time_from, delivery_time_till, has_door_key, has_shop_alarm, delivery_route_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $newCustomerId,
                'Primary',
                'No. ' . (100 + $i) . ', Sample Street',
                null,
                'Colombo',
                '10000',
                $phone,
                null,
                null,
                null,
                1,
                $customerName,
                $phone,
                $customerEmail,
                'Default address created by seed script',
                null,
                '09:00:00',
                '17:00:00',
                0,
                0,
                null,
            ]
        );

        $created[] = [
            'id' => $newCustomerId,
            'code' => $customerCode,
            'name' => $customerName,
            'email' => $customerEmail,
        ];
    }

    echo "Created " . count($created) . " customers successfully." . PHP_EOL;
    foreach ($created as $c) {
        echo $c['id'] . ' | ' . $c['code'] . ' | ' . $c['name'] . ' | ' . $c['email'] . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
