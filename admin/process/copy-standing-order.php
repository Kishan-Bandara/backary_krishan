<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
session_start();
include('../include/database.php');
include('../include/check_login.php');
include('../include/customer_access.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$csrfToken         = isset($input['csrf_token'])          ? (string)$input['csrf_token']          : '';
$sourceSOId        = isset($input['source_so_id'])        ? (int)$input['source_so_id']            : 0;
$targetCustomerId  = isset($input['target_customer_id'])  ? (int)$input['target_customer_id']      : 0;
$targetAddressId   = isset($input['target_address_id'])   ? (int)$input['target_address_id']       : 0;
$dateFrom          = isset($input['date_from'])           ? trim($input['date_from'])               : '';
$dateTo            = isset($input['date_to'])             ? trim($input['date_to'])                 : '';

// Validate CSRF
if (empty($_SESSION['copy_so_csrf']) || !hash_equals($_SESSION['copy_so_csrf'], $csrfToken)) {
    echo json_encode(['status' => false, 'message' => 'Security token mismatch']);
    exit;
}

// Restrict to super admin only
if (!function_exists('isSuperAdmin') || !isSuperAdmin()) {
    echo json_encode(['status' => false, 'message' => 'Only super admin can copy standing orders']);
    exit;
}

if ($sourceSOId <= 0) {
    echo json_encode(['status' => false, 'message' => 'Invalid source standing order']);
    exit;
}
if ($targetCustomerId <= 0) {
    echo json_encode(['status' => false, 'message' => 'Please select a target customer']);
    exit;
}

// Validate dates
$today = date('Y-m-d');
if (empty($dateFrom) || $dateFrom < $today) {
    echo json_encode(['status' => false, 'message' => 'Start date must be today or in the future']);
    exit;
}
if (!empty($dateTo) && $dateTo < $dateFrom) {
    echo json_encode(['status' => false, 'message' => 'End date must be on or after start date']);
    exit;
}

try {
    $db = new Database();

    // Load source standing order
    $sourceSO = $db->getRow('SELECT * FROM standing_order WHERE id = ? LIMIT 1', [$sourceSOId]);
    if (!$sourceSO) {
        echo json_encode(['status' => false, 'message' => 'Source standing order not found']);
        exit;
    }

    // Prevent copying to the same customer
    if ((int)$sourceSO['customer_id'] === $targetCustomerId) {
        echo json_encode(['status' => false, 'message' => 'Cannot copy to the same customer. Use Edit instead.']);
        exit;
    }

    // Validate target customer
    $customerEligibilityError = getCustomerOrderEligibilityError($db, $targetCustomerId, $targetCustomer);
    if ($customerEligibilityError !== null) {
        echo json_encode(['status' => false, 'message' => $customerEligibilityError]);
        exit;
    }
    $targetCustomerName = (string) ($targetCustomer['customer_name'] ?? 'Selected customer');

    // Validate target shipping address if provided
    if ($targetAddressId > 0) {
        $addrCheck = $db->getRow('SELECT id FROM customer_shipping_address WHERE id = ? AND customer_id = ? LIMIT 1', [$targetAddressId, $targetCustomerId]);
        if (!$addrCheck) {
            echo json_encode(['status' => false, 'message' => 'Shipping address does not belong to the selected customer']);
            exit;
        }
    }

    // Load source items
    $sourceItems = $db->getRows(
        'SELECT item_id, mon_qty, tue_qty, wed_qty, thu_qty, fri_qty, sat_qty, sun_qty FROM standing_order_item WHERE standing_order_id = ?',
        [$sourceSOId]
    );
    if (empty($sourceItems)) {
        echo json_encode(['status' => false, 'message' => 'Source standing order has no items to copy']);
        exit;
    }

    // Check if target customer already has an active standing order — deactivate it first
    $existingSO = $db->getRow('SELECT id FROM standing_order WHERE customer_id = ? AND active = 1 LIMIT 1', [$targetCustomerId]);
    if ($existingSO) {
        $db->updateRow('UPDATE standing_order SET active = 0, updated_at = NOW() WHERE id = ?', [(int)$existingSO['id']]);
    }

    // Insert new standing order for target customer
    $db->insertRow(
        'INSERT INTO standing_order (customer_id, shipping_address_id, active, DeliveryAmount, RepeatInterval, RepeatUnit, date_from, date_to, created_at, updated_at)
         VALUES (?, ?, 1, ?, ?, ?, ?, ?, NOW(), NOW())',
        [
            $targetCustomerId,
            $targetAddressId > 0 ? $targetAddressId : null,
            (float)$sourceSO['DeliveryAmount'],
            $sourceSO['RepeatInterval'] ?: null,
            $sourceSO['RepeatUnit'] ?: null,
            $dateFrom ?: null,
            $dateTo   ?: null,
        ]
    );

    $newSORow = $db->getRow('SELECT LAST_INSERT_ID() AS id');
    $newSOId  = (int)($newSORow['id'] ?? 0);
    if ($newSOId <= 0) {
        echo json_encode(['status' => false, 'message' => 'Failed to create new standing order']);
        exit;
    }

    // Copy items
    foreach ($sourceItems as $item) {
        $db->insertRow(
            'INSERT INTO standing_order_item (standing_order_id, item_id, mon_qty, tue_qty, wed_qty, thu_qty, fri_qty, sat_qty, sun_qty, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
            [
                $newSOId,
                (int)$item['item_id'],
                (float)$item['mon_qty'],
                (float)$item['tue_qty'],
                (float)$item['wed_qty'],
                (float)$item['thu_qty'],
                (float)$item['fri_qty'],
                (float)$item['sat_qty'],
                (float)$item['sun_qty'],
            ]
        );
    }

    // Generate invoices for the date range (reuse same logic as save-standing-order.php)
    $invoiceCount = 0;
    if (!empty($dateFrom)) {
        $invoiceStartDate = $dateFrom;
        $invoiceEndDate   = $dateTo ?: date('Y-m-d', strtotime($dateFrom . ' +7 days'));

        $totalDays = (int)(new DateTime($invoiceStartDate))->diff(new DateTime($invoiceEndDate))->days;
        if ($totalDays <= 0) $totalDays = 7;

        // Load shipping address for invoice fields
        $shipping = null;
        if ($targetAddressId > 0) {
            $shipping = $db->getRow('SELECT * FROM customer_shipping_address WHERE id = ? LIMIT 1', [$targetAddressId]);
        }
        if (!$shipping) {
            $shipping = [
                'address_line_1' => $targetCustomer['address_line_1'] ?? '',
                'address_line_2' => $targetCustomer['address_line_2'] ?? '',
                'city'           => $targetCustomer['city']           ?? '',
                'contact_no'     => $targetCustomer['customer_mobile'] ?? '',
            ];
        }

        // Reload items with prices
        $soItems = $db->getRows(
            'SELECT soi.item_id, soi.mon_qty, soi.tue_qty, soi.wed_qty, soi.thu_qty, soi.fri_qty, soi.sat_qty, soi.sun_qty
             FROM standing_order_item soi WHERE soi.standing_order_id = ?',
            [$newSOId]
        );

        $dayNames       = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
        $deliveryAmount = (float)$sourceSO['DeliveryAmount'];

        for ($i = 0; $i < $totalDays; $i++) {
            $deliveryDate = date('Y-m-d', strtotime($invoiceStartDate . " +$i days"));
            $dow          = (int)date('w', strtotime($deliveryDate));
            $dayCol       = $dayNames[$dow] . '_qty';

            $dayItems = [];
            foreach ($soItems as $item) {
                $qty = (float)$item[$dayCol];
                if ($qty > 0) $dayItems[] = ['item_id' => (int)$item['item_id'], 'qty' => $qty];
            }
            if (empty($dayItems)) continue;

            // Skip if invoice already exists
            $exists = $db->getRow(
                "SELECT invoice_h_id FROM invoice_hedder WHERE invoice_h_customer_id = ? AND invoice_h_order_note = 'Standing Order' AND invoice_h_delivery_date = ?",
                [$targetCustomerId, $deliveryDate]
            );
            if ($exists) continue;

            $netValue = 0;
            foreach ($dayItems as $di) {
                $pr = $db->getRow('SELECT item_normal_selling_price FROM item_master WHERE item_id = ?', [$di['item_id']]);
                $netValue += $di['qty'] * (float)($pr['item_normal_selling_price'] ?? 0);
            }
            $grossValue = $netValue + $deliveryAmount;

            $nextId  = (int)($db->getRow('SELECT MAX(invoice_h_id) AS m FROM invoice_hedder')['m'] ?? 0) + 1;
            $invCode = 'INV' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

            $db->insertRow(
                "INSERT INTO invoice_hedder (invoice_h_code, invoice_h_customer_id, invoice_h_date, invoice_h_datetime,
                    invoice_h_location, invoice_h_delivery_city, delivery_city_name, invoice_h_delivery_cost,
                    invoice_h_delivery_mode, invoice_h_pay_type, invoice_h_coupun_code, invoice_h_coupon_type,
                    invoice_h_coupon_rate, invoice_h_coupon_value, invoice_h_net_value, invoice_h_vat_value,
                    invoice_h_gross_value, invoice_h_order_note, invoice_h_delivery_name, invoice_h_delivery_address,
                    invoice_h_delivery_contact_no, invoice_h_delivery_date, invoice_h_delivery_time,
                    invoice_h_status, add_by, invoice_h_approve_date, CustomerCurrencyId, CurrencyRate)
                 VALUES (?, ?, ?, NOW(), 1, 0, ?, ?, 0, 1, '', '', 0, 0, ?, 0, ?, 'Standing Order', ?, ?, ?, ?, '10:00-12:00', 1, 'System', NOW(), '0', 1)",
                [
                    $invCode,
                    $targetCustomerId,
                    $deliveryDate,
                    $shipping['city'] ?? '',
                    $deliveryAmount,
                    $netValue,
                    $grossValue,
                    $targetCustomerName,
                    trim(($shipping['address_line_1'] ?? '') . ' ' . ($shipping['address_line_2'] ?? '')),
                    $shipping['contact_no'] ?? '',
                    $deliveryDate,
                ]
            );

            $invId = (int)($db->getRow('SELECT LAST_INSERT_ID() AS id')['id'] ?? 0);
            foreach ($dayItems as $di) {
                $pr    = $db->getRow('SELECT item_normal_selling_price FROM item_master WHERE item_id = ?', [$di['item_id']]);
                $price = (float)($pr['item_normal_selling_price'] ?? 0);
                $total = $di['qty'] * $price;
                $db->insertRow(
                    "INSERT INTO invoice_details (invoice_h_id, invoice_d_item_id, invoice_d_qty, invoice_d_balance,
                        invoice_d_item_price, invoice_d_vat, invoice_d_vat_rate, invoice_d_discount_value,
                        invoice_d_discount_type, invoice_d_discount_total, invoice_d_item_total, order_note)
                     VALUES (?, ?, ?, ?, ?, 'N', 0, 0, 0, 0, ?, 'Standing Order')",
                    [$invId, $di['item_id'], $di['qty'], $di['qty'], $price, $total]
                );
            }
            $invoiceCount++;
        }
    }

    echo json_encode([
        'status'  => true,
        'message' => 'Standing order copied to ' . htmlspecialchars($targetCustomerName, ENT_QUOTES, 'UTF-8') .
                     '. ' . $invoiceCount . ' invoice(s) generated.',
        'new_so_id' => $newSOId,
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
exit;
