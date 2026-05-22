<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
header('Content-Type: application/json');

error_log('save-standing-order.php started');

require_once(__DIR__ . '/../include/database.php');
include('../include/check_login.php');
include('../include/customer_access.php');

error_log('Database and login includes loaded');

function validateRepeatUnit($repeatUnit) {
    if (empty($repeatUnit)) {
        error_log('Repeat unit is empty, allowing');
        return true; // Allow empty
    }
    try {
        $db = new Database();
        $unit = $db->getRow('SELECT id FROM repeat_units WHERE id = ? LIMIT 1', [$repeatUnit]);
        $valid = !empty($unit);
        error_log('Validating repeat unit: ' . $repeatUnit . ' - Valid: ' . ($valid ? 'yes' : 'no'));
        return $valid;
    } catch (Exception $e) {
        error_log('Error validating repeat unit: ' . $e->getMessage());
        return false;
    }
}

$raw = file_get_contents('php://input');
// For testing, if no input, read from test file
if(empty($raw)){
    $raw = file_get_contents(__DIR__ . '/test_data.json');
}
$data = json_decode($raw, true);
if(!$data){
    error_log('Invalid JSON received: ' . $raw);
    echo json_encode(['status'=>'error','message'=>'Invalid JSON']);
    exit;
}

error_log('Standing Order Save Data: ' . print_r($data, true));

$customerId = isset($data['customer_id']) ? (int)$data['customer_id'] : 0;
$shippingAddressId = isset($data['shipping_address_id']) ? (int)$data['shipping_address_id'] : 0;
$items = isset($data['items']) && is_array($data['items']) ? $data['items'] : [];
$deliveryAmount = isset($data['delivery_amount']) ? (float)$data['delivery_amount'] : 0.0;
if (!is_numeric($deliveryAmount) || $deliveryAmount < 0) {
    error_log('Invalid delivery amount provided: ' . var_export($deliveryAmount, true));
    echo json_encode(['status'=>'error','message'=>'Invalid delivery amount']);
    exit;
}
$repeatInterval = isset($data['repeat_interval']) ? (int)$data['repeat_interval'] : null;
$repeatUnit = isset($data['repeat_unit']) ? $data['repeat_unit'] : null;
$dateFrom = isset($data['date_from']) && !empty($data['date_from']) ? $data['date_from'] : date('Y-m-d');
$dateTo = isset($data['date_to']) && !empty($data['date_to']) ? $data['date_to'] : null;

error_log("Parsed data - Customer: $customerId, Shipping: $shippingAddressId, Items: " . count($items) . ", Repeat: $repeatInterval/$repeatUnit, DateFrom: $dateFrom, DateTo: $dateTo");

if($customerId <= 0){
    error_log('Customer ID validation failed: ' . $customerId);
    echo json_encode(['status'=>'error','message'=>'Missing customer']);
    exit;
}
// Shipping address is optional - will use customer address as fallback
if (!validateRepeatUnit($repeatUnit)) {
    error_log('Repeat unit validation failed for: ' . $repeatUnit);
    echo json_encode(['status'=>'error','message'=>'Invalid repeat unit']);
    exit;
}

error_log('Starting database operations');

try{
    $db = new Database();
    error_log('Database connection established');

    $customerEligibilityError = getCustomerOrderEligibilityError($db, $customerId);
    if ($customerEligibilityError !== null) {
        error_log('Customer eligibility validation failed: ' . $customerEligibilityError);
        echo json_encode(['status'=>'error','message'=>$customerEligibilityError]);
        exit;
    }

    $customer = $db->getRow('SELECT * FROM customer WHERE customer_id = ?', [$customerId]);
    if(!$customer){
        error_log('Customer not found for ID: ' . $customerId);
        echo json_encode(['status'=>'error','message'=>'Customer not found']);
        exit;
    }
    error_log('Customer found: ' . $customer['customer_name']);

    $shippingAddress = null;
    if ($shippingAddressId > 0) {
        $shippingAddress = $db->getRow('SELECT * FROM customer_shipping_address WHERE id = ? AND customer_id = ?', [$shippingAddressId, $customerId]);
        if(!$shippingAddress){
            error_log('Shipping address not found for ID: ' . $shippingAddressId . ' and customer: ' . $customerId);
            echo json_encode(['status'=>'error','message'=>'Shipping address not found or does not belong to customer']);
            exit;
        }
        error_log('Shipping address found: ' . $shippingAddress['address_line_1']);
    } else {
        error_log('No shipping address selected; will use customer address as fallback');
    }

    // Ensure single active standing order per customer
    $existing = $db->getRow('SELECT id FROM standing_order WHERE customer_id = ? AND active = 1 LIMIT 1', [$customerId]);
    if($existing && isset($existing['id'])){
        $soId = (int)$existing['id'];
        error_log('Updating existing standing order ID: ' . $soId);
        $db->updateRow('UPDATE standing_order SET shipping_address_id = ?, DeliveryAmount = ?, RepeatInterval = ?, RepeatUnit = ?, date_from = ?, date_to = ?, updated_at = NOW() WHERE id = ?', [$shippingAddressId > 0 ? $shippingAddressId : null, $deliveryAmount, $repeatInterval, $repeatUnit, $dateFrom, $dateTo, $soId]);
    } else {
        error_log('Creating new standing order for customer: ' . $customerId);
        $db->insertRow('INSERT INTO standing_order (customer_id, shipping_address_id, active, DeliveryAmount, RepeatInterval, RepeatUnit, date_from, date_to, created_at, updated_at) VALUES (?, ?, 1, ?, ?, ?, ?, ?, NOW(), NOW())', [$customerId, $shippingAddressId > 0 ? $shippingAddressId : null, $deliveryAmount, $repeatInterval, $repeatUnit, $dateFrom, $dateTo]);
        $row = $db->getRow('SELECT id FROM standing_order WHERE customer_id = ? AND active = 1 ORDER BY id DESC LIMIT 1', [$customerId]);
        if (!$row || !isset($row['id'])) {
            error_log('Failed to retrieve standing order ID after insert');
            throw new Exception('Failed to create standing order');
        }
        $soId = (int)$row['id'];
        error_log('New standing order created with ID: ' . $soId);
    }

    // Clear previous items for this standing order
    $db->deleteRow('DELETE FROM standing_order_item WHERE standing_order_id = ?', [$soId]);
    error_log('Cleared previous items for standing order: ' . $soId);

    $totalLines = 0; $totalWeeklyQty = 0.0;
    $hasAllowInSalesColumn = (bool) $db->getRow("SHOW COLUMNS FROM item_master LIKE 'allow_in_sales'");
    foreach($items as $it){
        $itemId = isset($it['item_id']) ? (int)$it['item_id'] : 0;
        error_log('Processing item: ' . print_r($it, true));
        if($itemId <= 0) {
            error_log('Skipping item with invalid ID: ' . $itemId);
            continue;
        }

        $productLookupQuery = $hasAllowInSalesColumn
            ? 'SELECT item_name, allow_in_sales FROM item_master WHERE item_id = ? LIMIT 1'
            : 'SELECT item_name FROM item_master WHERE item_id = ? LIMIT 1';
        $prod = $db->getRow($productLookupQuery, [$itemId]);
        if (!$prod) {
            error_log('Invalid product id in standing order: ' . $itemId);
            echo json_encode(['status'=>'error','message'=>'Invalid product: '.$itemId]);
            exit;
        }
        if ($hasAllowInSalesColumn && array_key_exists('allow_in_sales', $prod) && $prod['allow_in_sales'] !== null && (int) $prod['allow_in_sales'] !== 1) {
            error_log('Attempt to add sales-disabled product to standing order: ' . $itemId);
            echo json_encode(['status'=>'error','message'=>'Standing orders can only contain products allowed in sales. Offending item: ' . ($prod['item_name'] ?? $itemId)]);
            exit;
        }

        $qty = isset($it['qty']) && is_array($it['qty']) ? $it['qty'] : [];
        // Normalize qty array to 7 positions Mon..Sun
        $q = [];
        for($i=0;$i<7;$i++){ $q[$i] = isset($qty[$i]) ? (float)$qty[$i] : 0.0; }
        $lineSum = array_sum($q);
        if($lineSum <= 0){ 
            error_log('Skipping item with zero quantity: ' . $itemId);
            continue; 
        }
        $params = [
            $soId,
            $itemId,
            $q[0], $q[1], $q[2], $q[3], $q[4], $q[5], $q[6]
        ];
        error_log('Inserting standing order item with params: ' . print_r($params, true));
        $db->insertRow(
            'INSERT INTO standing_order_item (standing_order_id, item_id, mon_qty, tue_qty, wed_qty, thu_qty, fri_qty, sat_qty, sun_qty, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?, NOW(), NOW())',
            $params
        );
        error_log('Inserted standing order item: ' . $itemId . ' with quantities: ' . implode(',', $q));
        $totalLines++; $totalWeeklyQty += $lineSum;
    }
    error_log('Total lines processed: ' . $totalLines . ', Total weekly quantity: ' . $totalWeeklyQty);

    // Update existing future invoices with new quantities and recalculated amounts
    $isUpdate = isset($existing) && isset($existing['id']);
    if ($isUpdate) {
        $today = date('Y-m-d');

        // Load per-day route amounts for this shipping address (for updating existing invoices)
        $updateDayRouteAmounts = ['mon' => null, 'tue' => null, 'wed' => null, 'thu' => null, 'fri' => null, 'sat' => null, 'sun' => null];
        if ($shippingAddressId > 0) {
            try {
                $udr = $db->getRow('SELECT rm_mon.amount AS mon_amount, rm_tue.amount AS tue_amount, rm_wed.amount AS wed_amount, rm_thu.amount AS thu_amount, rm_fri.amount AS fri_amount, rm_sat.amount AS sat_amount, rm_sun.amount AS sun_amount
                    FROM shipping_address_day_route sdr
                    LEFT JOIN delivery_route_master rm_mon ON rm_mon.id = sdr.mon_route_id
                    LEFT JOIN delivery_route_master rm_tue ON rm_tue.id = sdr.tue_route_id
                    LEFT JOIN delivery_route_master rm_wed ON rm_wed.id = sdr.wed_route_id
                    LEFT JOIN delivery_route_master rm_thu ON rm_thu.id = sdr.thu_route_id
                    LEFT JOIN delivery_route_master rm_fri ON rm_fri.id = sdr.fri_route_id
                    LEFT JOIN delivery_route_master rm_sat ON rm_sat.id = sdr.sat_route_id
                    LEFT JOIN delivery_route_master rm_sun ON rm_sun.id = sdr.sun_route_id
                    WHERE sdr.shipping_address_id = ? LIMIT 1', [$shippingAddressId]);
                if ($udr) {
                    foreach (['mon','tue','wed','thu','fri','sat','sun'] as $d) {
                        $amt = $udr[$d . '_amount'];
                        $updateDayRouteAmounts[$d] = ($amt !== null) ? (float)$amt : null;
                    }
                }
            } catch (Exception $e) {
                error_log('Could not load update day route amounts: ' . $e->getMessage());
            }
        }

        // Get all future standing order invoices for this customer
        $futureInvoices = $db->getRows(
            "SELECT invoice_h_id, invoice_h_delivery_date, invoice_h_delivery_cost
             FROM invoice_hedder
             WHERE invoice_h_customer_id = ?
             AND invoice_h_order_note = 'Standing Order'
             AND invoice_h_delivery_date >= ?
             ORDER BY invoice_h_delivery_date ASC",
            [$customerId, $today]
        );

        if (!empty($futureInvoices)) {
            // Get the day of week for each invoice date and map to standing order quantities
            $dayNames = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];

            foreach ($futureInvoices as $invoice) {
                $deliveryDate = $invoice['invoice_h_delivery_date'];
                $invoiceId = $invoice['invoice_h_id'];
                $deliveryCost = (float)$invoice['invoice_h_delivery_cost'];

                // Get day of week (0=Sunday, 1=Monday, ..., 6=Saturday)
                $dayOfWeek = (int)date('w', strtotime($deliveryDate));
                $dayColumn = $dayNames[$dayOfWeek] . '_qty';

                // Get standing order items with quantities for this day
                $soItems = $db->getRows(
                    "SELECT soi.item_id, soi.{$dayColumn} as qty, im.item_normal_selling_price
                     FROM standing_order_item soi
                     JOIN item_master im ON soi.item_id = im.item_id
                     WHERE soi.standing_order_id = ? AND soi.{$dayColumn} > 0",
                    [$soId]
                );

                if (!empty($soItems)) {
                    // Delete existing invoice details
                    $db->deleteRow('DELETE FROM invoice_details WHERE invoice_h_id = ?', [$invoiceId]);

                    // Calculate new net value and insert new details
                    $newNetValue = 0;
                    foreach ($soItems as $item) {
                        $qty = (float)$item['qty'];
                        $price = (float)$item['item_normal_selling_price'];
                        $total = $qty * $price;
                        $newNetValue += $total;

                        // Insert new invoice detail
                        $db->insertRow(
                            'INSERT INTO invoice_details (invoice_h_id, invoice_d_item_id, invoice_d_qty, invoice_d_balance, invoice_d_item_price, invoice_d_vat, invoice_d_vat_rate, invoice_d_discount_value, invoice_d_discount_type, invoice_d_discount_total, invoice_d_item_total, order_note) VALUES (?, ?, ?, ?, ?, \'N\', 0, 0, 0, 0, ?, \'\')',
                            [$invoiceId, $item['item_id'], $qty, $qty, $price, $total]
                        );
                    }

                    // Update invoice header with new amounts
                    // Use per-day route amount if configured, otherwise keep existing delivery cost
                    $updDayName = $dayNames[$dayOfWeek];
                    $updEffectiveDelivery = ($updateDayRouteAmounts[$updDayName] !== null) ? $updateDayRouteAmounts[$updDayName] : $deliveryCost;
                    $newGrossValue = $newNetValue + $updEffectiveDelivery;
                    $db->updateRow(
                        'UPDATE invoice_hedder SET invoice_h_net_value = ?, invoice_h_gross_value = ?, invoice_h_delivery_cost = ? WHERE invoice_h_id = ?',
                        [$newNetValue, $newGrossValue, $updEffectiveDelivery, $invoiceId]
                    );
                }
            }
        }
    }

    // Handle existing invoices when updating - remove invoices beyond the date range
    $isUpdate = isset($existing) && isset($existing['id']);
    if ($isUpdate && $dateTo) {
        $cutoffDate = $dateTo;
        // Also calculate cutoff from start date if needed
        $today = date('Y-m-d');

        // Delete invoices that are beyond the To date
        $excessInvoices = $db->getRows(
            "SELECT invoice_h_id
             FROM invoice_hedder 
             WHERE invoice_h_customer_id = ? 
             AND invoice_h_order_note = 'Standing Order' 
             AND invoice_h_delivery_date > ?
             ORDER BY invoice_h_delivery_date ASC",
            [$customerId, $cutoffDate]
        );

        if(!empty($excessInvoices)){
            foreach($excessInvoices as $inv){
                // Delete invoice details first
                $db->deleteRow('DELETE FROM invoice_details WHERE invoice_h_id = ?', [$inv['invoice_h_id']]);
                // Delete invoice header
                $db->deleteRow('DELETE FROM invoice_hedder WHERE invoice_h_id = ?', [$inv['invoice_h_id']]);
            }
            error_log("Deleted " . count($excessInvoices) . " excess invoices beyond cutoff date: $cutoffDate");
        }
    }

    $invoiceCount = 0;
    
    // Create invoices for ALL days based on repeat interval
    if (!empty($items)) {
        // Get customer shipping address
        $shipping = null;
        if ($shippingAddressId > 0) {
            $shipping = $db->getRow('SELECT * FROM customer_shipping_address WHERE id = ? LIMIT 1', [$shippingAddressId]);
        }
        if(!$shipping){
            $shipping = [
                'address_line_1' => $customer['address_line_1'] ?? '',
                'address_line_2' => $customer['address_line_2'] ?? '',
                'city' => $customer['city'] ?? '',
                'postal_code' => $customer['postal_code'] ?? '',
                'contact_no' => $customer['customer_mobile'] ?? $customer['customer_tell'] ?? '',
            ];
        }

        // Load per-day route amounts for this shipping address
        $dayRouteAmounts = ['mon' => null, 'tue' => null, 'wed' => null, 'thu' => null, 'fri' => null, 'sat' => null, 'sun' => null];
        if ($shippingAddressId > 0) {
            try {
                $dayRouteRow = $db->getRow('SELECT
                    rm_mon.amount AS mon_amount, rm_tue.amount AS tue_amount, rm_wed.amount AS wed_amount,
                    rm_thu.amount AS thu_amount, rm_fri.amount AS fri_amount, rm_sat.amount AS sat_amount, rm_sun.amount AS sun_amount
                    FROM shipping_address_day_route sdr
                    LEFT JOIN delivery_route_master rm_mon ON rm_mon.id = sdr.mon_route_id
                    LEFT JOIN delivery_route_master rm_tue ON rm_tue.id = sdr.tue_route_id
                    LEFT JOIN delivery_route_master rm_wed ON rm_wed.id = sdr.wed_route_id
                    LEFT JOIN delivery_route_master rm_thu ON rm_thu.id = sdr.thu_route_id
                    LEFT JOIN delivery_route_master rm_fri ON rm_fri.id = sdr.fri_route_id
                    LEFT JOIN delivery_route_master rm_sat ON rm_sat.id = sdr.sat_route_id
                    LEFT JOIN delivery_route_master rm_sun ON rm_sun.id = sdr.sun_route_id
                    WHERE sdr.shipping_address_id = ? LIMIT 1', [$shippingAddressId]);
                if ($dayRouteRow) {
                    foreach (['mon','tue','wed','thu','fri','sat','sun'] as $d) {
                        $amt = $dayRouteRow[$d . '_amount'];
                        $dayRouteAmounts[$d] = ($amt !== null) ? (float)$amt : null;
                    }
                }
            } catch (Exception $e) {
                error_log('Could not load day route amounts: ' . $e->getMessage());
            }
        }

        // Get standing order items
        $soItems = $db->getRows('SELECT item_id, mon_qty, tue_qty, wed_qty, thu_qty, fri_qty, sat_qty, sun_qty FROM standing_order_item WHERE standing_order_id = ?', [$soId]);

        $today = date('Y-m-d');
        $currentDayOfWeek = (int)date('w'); // 0=Sunday, 1=Monday, ..., 6=Saturday
        $dayNames = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
        
        // Use date_from and date_to for invoice creation range
        $invoiceStartDate = $dateFrom ? $dateFrom : $today;
        // Ensure start date is not in the past
        if ($invoiceStartDate < $today) {
            $invoiceStartDate = $today;
        }
        
        // Calculate end date from date_to or fallback to repeat interval
        $invoiceEndDate = null;
        if ($dateTo) {
            $invoiceEndDate = $dateTo;
        } elseif ($repeatInterval > 0 && $repeatUnit) {
            $startDT = new DateTime($invoiceStartDate);
            if ($repeatUnit == 1) {
                $startDT->modify("+{$repeatInterval} days");
            } elseif ($repeatUnit == 2) {
                $days = $repeatInterval * 7;
                $startDT->modify("+{$days} days");
            } elseif ($repeatUnit == 3) {
                $startDT->modify("+{$repeatInterval} months");
            }
            $invoiceEndDate = $startDT->format('Y-m-d');
        } else {
            // Default to 7 days from start
            $invoiceEndDate = date('Y-m-d', strtotime($invoiceStartDate . ' +7 days'));
        }
        
        $totalDaysToCreate = (int)((new DateTime($invoiceStartDate))->diff(new DateTime($invoiceEndDate))->days);
        if ($totalDaysToCreate <= 0) $totalDaysToCreate = 7;
        
        error_log("Creating invoices from $invoiceStartDate to $invoiceEndDate ($totalDaysToCreate days)");
        
        // Loop through all days in the date range and create invoices for each day that has items
        for($i = 0; $i < $totalDaysToCreate; $i++){
            $deliveryDate = date('Y-m-d', strtotime($invoiceStartDate . " +$i days"));
            $checkDay = (int)date('w', strtotime($deliveryDate)); // Get day of week for this date
            $dayColumn = $dayNames[$checkDay] . '_qty';
            
            // Get items for this day
            $dayItems = [];
            foreach($soItems as $item){
                $qty = (float)$item[$dayColumn];
                if($qty > 0){
                    $dayItems[] = ['item_id' => $item['item_id'], 'qty' => $qty];
                }
            }
            
            // Skip if no items for this day
            if(empty($dayItems)){
                continue;
            }
            
            // Check if invoice already exists for this customer and delivery date
            $existingInvoice = $db->getRow(
                "SELECT invoice_h_id FROM invoice_hedder 
                 WHERE invoice_h_customer_id = ? 
                 AND invoice_h_order_note = 'Standing Order' 
                 AND invoice_h_delivery_date = ?",
                [$customerId, $deliveryDate]
            );
            
            if($existingInvoice){
                // Invoice already exists for this date, skip creation
                error_log("Invoice already exists for customer $customerId on $deliveryDate, skipping");
                continue;
            }
            
            // Calculate net value
            $netValue = 0;
            foreach($dayItems as $it){
                $priceRow = $db->getRow('SELECT item_normal_selling_price FROM item_master WHERE item_id = ?', [$it['item_id']]);
                $price = (float)($priceRow['item_normal_selling_price'] ?? 0);
                $netValue += $it['qty'] * $price;
            }
            // Use per-day route amount if configured, otherwise fall back to the standing order delivery amount
            $dayName = $dayNames[$checkDay];
            $effectiveDeliveryAmount = ($dayRouteAmounts[$dayName] !== null) ? $dayRouteAmounts[$dayName] : $deliveryAmount;
            $grossValue = $netValue + $effectiveDeliveryAmount;

            // Get next invoice code
            $nextInvId = $db->getRow('SELECT MAX(invoice_h_id) AS maxid FROM invoice_hedder');
            $nextId = (int)($nextInvId['maxid'] ?? 0) + 1;
            $invCode = 'INV' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
            
            // Create invoice header
            $db->insertRow('INSERT INTO invoice_hedder (invoice_h_code, invoice_h_customer_id, invoice_h_date, invoice_h_datetime, invoice_h_location, invoice_h_delivery_city, delivery_city_name, invoice_h_delivery_cost, invoice_h_delivery_mode, invoice_h_pay_type, invoice_h_coupun_code, invoice_h_coupon_type, invoice_h_coupon_rate, invoice_h_coupon_value, invoice_h_net_value, invoice_h_vat_value, invoice_h_gross_value, invoice_h_order_note, invoice_h_delivery_name, invoice_h_delivery_address, invoice_h_delivery_contact_no, invoice_h_delivery_date, invoice_h_delivery_time, invoice_h_status, add_by, invoice_h_approve_date, CustomerCurrencyId, CurrencyRate) VALUES (?, ?, ?, NOW(), 1, 0, ?, ?, 0, 1, \'\', \'\', 0, 0, ?, 0, ?, \'Standing Order\', ?, ?, ?, ?, \'10:00-12:00\', 1, \'System\', NOW(), \'0\', 1)', [
                $invCode,
                $customerId,
                $deliveryDate,
                $shipping['city'] ?? '',
                $effectiveDeliveryAmount,
                $netValue,
                $grossValue,
                $customer['customer_name'] ?? '',
                trim(($shipping['address_line_1'] ?? '') . ' ' . ($shipping['address_line_2'] ?? '')),
                $shipping['contact_no'] ?? '',
                $deliveryDate
            ]);

            // Get the actual invoice_h_id that was auto-generated
            $invRow = $db->getRow('SELECT LAST_INSERT_ID() AS id');
            $invId = (int)($invRow['id'] ?? 0);

            // Insert details
            foreach($dayItems as $it){
                $priceRow = $db->getRow('SELECT item_normal_selling_price FROM item_master WHERE item_id = ?', [$it['item_id']]);
                $price = (float)($priceRow['item_normal_selling_price'] ?? 0);
                $total = $it['qty'] * $price;
                $db->insertRow('INSERT INTO invoice_details (invoice_h_id, invoice_d_item_id, invoice_d_qty, invoice_d_balance, invoice_d_item_price, invoice_d_vat, invoice_d_vat_rate, invoice_d_discount_value, invoice_d_discount_type, invoice_d_discount_total, invoice_d_item_total, order_note) VALUES (?, ?, ?, ?, ?, \'N\', 0, 0, 0, 0, ?, \'Standing Order\')', [
                    $invId,
                    $it['item_id'],
                    $it['qty'],
                    $it['qty'],
                    $price,
                    $total
                ]);
            }
            $invoiceCount++;
            error_log("Created standing order invoice: $invId for date: $deliveryDate (day: " . $dayNames[$checkDay] . ")");
        }
    }

    // --- Send standing order confirmation email (non-blocking) ---
    $emailSent = false;
    $emailError = '';
    try {
        require_once(__DIR__ . '/../include/EmailService.php');
        $emailService = new EmailService();
        if ($emailService->isEnabled()) {
            $emailSent = $emailService->sendStandingOrderSummary($soId);
            if (!$emailSent) {
                $emailError = $emailService->getLastError();
            }
        }
    } catch (Exception $emailEx) {
        // Email failure should never block standing order creation
        $emailError = $emailEx->getMessage();
        error_log('Standing Order Email Error: ' . $emailError);
    }

    $responseMsg = 'Standing order saved. Lines: '.$totalLines.', Weekly Qty: '.$totalWeeklyQty.', Invoices created: '.$invoiceCount;
    if ($emailSent) {
        $responseMsg .= '. Confirmation email sent to customer.';
    } elseif (!empty($emailError)) {
        $responseMsg .= '. Email not sent: ' . $emailError;
    }

    echo json_encode([
        'status' => 'success',
        'message' => $responseMsg,
        'standing_order_id' => $soId,
        'customer_id' => $customerId,
        'email_sent' => $emailSent
    ]);

} catch(Exception $e){
    error_log('Standing Order Save Error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    echo json_encode(['status'=>'error','message'=>'Save failed: '.$e->getMessage()]);
}



