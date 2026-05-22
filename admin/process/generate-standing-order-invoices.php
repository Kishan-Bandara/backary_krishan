<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
header('Content-Type: application/json');

require_once(__DIR__ . '/../include/database.php');
include('../include/check_login.php');
include_once(__DIR__ . '/../include/delivery_rules.php');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data || !isset($data['standing_order_ids']) || !is_array($data['standing_order_ids'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request: standing_order_ids required']);
    exit;
}

// CSRF check
if (empty($data['csrf_token']) || $data['csrf_token'] !== ($_SESSION['so_import_csrf'] ?? '')) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token. Please reload the page.']);
    exit;
}

$soIds = array_map('intval', $data['standing_order_ids']);
$soIds = array_filter($soIds, function ($id) { return $id > 0; });
$soIds = array_unique($soIds);

if (empty($soIds)) {
    echo json_encode(['status' => 'error', 'message' => 'No valid standing order IDs provided']);
    exit;
}

if (count($soIds) > 200) {
    echo json_encode(['status' => 'error', 'message' => 'Maximum 200 standing orders per batch']);
    exit;
}

function normalizeStandingOrderDate($value)
{
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }

    $timestamp = strtotime(str_replace('/', '-', $value));
    if ($timestamp === false || $timestamp <= 0) {
        return null;
    }

    return date('Y-m-d', $timestamp);
}

try {
    $db = new Database();
    $pdo = $db->getConnection();
    $totalInvoices = 0;
    $orderResults = [];
    $dayNames = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
    $today = date('Y-m-d');

    // Global delivery-rule settings (used as fallback when shipping address has no override)
    $globalDeliverySettings = function_exists('getDeliveryRuleSettings') ? getDeliveryRuleSettings() : [];
    $globalApplyTo = $globalDeliverySettings['apply_to'] ?? 'gross';
    $globalSoDailyAvg = isset($globalDeliverySettings['standing_order_daily_avg_min']) ? (float)$globalDeliverySettings['standing_order_daily_avg_min'] : 0.0;
    $globalWeeklyAvgFree = isset($globalDeliverySettings['weekly_avg_free_delivery']) ? (float)$globalDeliverySettings['weekly_avg_free_delivery'] : 0.0;

    foreach ($soIds as $soId) {
        // Get standing order
        $so = $db->getRow('SELECT * FROM standing_order WHERE id = ? AND active = 1 LIMIT 1', [$soId]);
        if (!$so) {
            $orderResults[] = ['so_id' => $soId, 'status' => 'error', 'message' => 'Standing order not found or inactive'];
            continue;
        }

        $customerId = (int)$so['customer_id'];
        $deliveryAmount = (float)($so['DeliveryAmount'] ?? 0);
        $dateFrom = normalizeStandingOrderDate($so['date_from'] ?? '');
        $dateTo = normalizeStandingOrderDate($so['date_to'] ?? '');
        if ($dateFrom === null || $dateTo === null) {
            $orderResults[] = [
                'so_id' => $soId,
                'status' => 'error',
                'message' => 'Standing order is missing a valid date_from/date_to range',
            ];
            continue;
        }
        if ($dateFrom > $dateTo) {
            $orderResults[] = [
                'so_id' => $soId,
                'status' => 'error',
                'message' => 'Standing order date_from cannot be after date_to',
            ];
            continue;
        }

        // Get customer
        $customer = $db->getRow('SELECT * FROM customer WHERE customer_id = ?', [$customerId]);
        if (!$customer) {
            $orderResults[] = ['so_id' => $soId, 'status' => 'error', 'message' => 'Customer not found'];
            continue;
        }

        // Get shipping address
        $shippingAddressId = !empty($so['shipping_address_id']) ? (int)$so['shipping_address_id'] : 0;
        $shipping = null;
        if ($shippingAddressId > 0) {
            $shipping = $db->getRow('SELECT * FROM customer_shipping_address WHERE id = ? LIMIT 1', [$shippingAddressId]);
        }
        if (!$shipping) {
            $shipping = [
                'address_line_1' => $customer['address_line_1'] ?? '',
                'address_line_2' => $customer['address_line_2'] ?? '',
                'city' => $customer['city'] ?? '',
                'postal_code' => $customer['postal_code'] ?? '',
                'contact_no' => $customer['customer_mobile'] ?? $customer['customer_tell'] ?? '',
            ];
        }

        // Get standing order items
        $soItems = $db->getRows(
            'SELECT item_id, mon_qty, tue_qty, wed_qty, thu_qty, fri_qty, sat_qty, sun_qty FROM standing_order_item WHERE standing_order_id = ?',
            [$soId]
        );

        if (empty($soItems)) {
            $orderResults[] = ['so_id' => $soId, 'status' => 'skipped', 'message' => 'No items in standing order'];
            continue;
        }

        // ---- Delivery rule resolution for this standing order ----
        // Determine effective rule + thresholds: per-shipping-address overrides take precedence over globals.
        $effRuleId = (isset($shipping['delivery_rule_id']) && $shipping['delivery_rule_id'] !== null && $shipping['delivery_rule_id'] !== '')
            ? (int)$shipping['delivery_rule_id']
            : 0;
        $effSoDailyAvg = (isset($shipping['so_daily_average']) && $shipping['so_daily_average'] !== null && $shipping['so_daily_average'] !== '')
            ? (float)$shipping['so_daily_average']
            : $globalSoDailyAvg;
        $effWeeklyAvgFree = (isset($shipping['weekly_avg_free_delivery_override']) && $shipping['weekly_avg_free_delivery_override'] !== null && $shipping['weekly_avg_free_delivery_override'] !== '')
            ? (float)$shipping['weekly_avg_free_delivery_override']
            : $globalWeeklyAvgFree;

        // Pre-cache item prices and compute weekly value + daily average from the SO template.
        $itemPriceCache = [];
        $dayCols = ['mon_qty','tue_qty','wed_qty','thu_qty','fri_qty','sat_qty','sun_qty'];
        $weeklyValue = 0.0;
        $activeDayCount = 0;
        foreach ($dayCols as $col) {
            $dayTotal = 0.0;
            foreach ($soItems as $sit) {
                $iid = (int)$sit['item_id'];
                if (!array_key_exists($iid, $itemPriceCache)) {
                    $pr = $db->getRow('SELECT item_normal_selling_price FROM item_master WHERE item_id = ?', [$iid]);
                    $itemPriceCache[$iid] = (float)($pr['item_normal_selling_price'] ?? 0);
                }
                $dayTotal += (float)$sit[$col] * $itemPriceCache[$iid];
            }
            if ($dayTotal > 0) {
                $activeDayCount++;
                $weeklyValue += $dayTotal;
            }
        }
        $dailyAvg = $activeDayCount > 0 ? ($weeklyValue / $activeDayCount) : 0.0;
        // Free-delivery qualifications (any one is sufficient).
        $freeBySoDailyAvg = ($effSoDailyAvg > 0 && $dailyAvg >= $effSoDailyAvg);
        $freeByWeeklyAvg = ($effWeeklyAvgFree > 0 && $weeklyValue >= $effWeeklyAvgFree);
        $standingOrderFreeDelivery = ($freeBySoDailyAvg || $freeByWeeklyAvg);

        $deletedInvoiceCount = 0;
        $futureInvoices = $db->getRows(
            "SELECT invoice_h_id
             FROM invoice_hedder
             WHERE invoice_h_customer_id = ?
             AND invoice_h_order_note = 'Standing Order'
             AND invoice_h_delivery_date >= ?",
            [$customerId, $today]
        );

        if (!empty($futureInvoices)) {
            $pdo->beginTransaction();
            try {
                foreach ($futureInvoices as $invoice) {
                    $invoiceId = (int)$invoice['invoice_h_id'];
                    $db->deleteRow('DELETE FROM invoice_details WHERE invoice_h_id = ?', [$invoiceId]);
                    $db->deleteRow('DELETE FROM invoice_hedder WHERE invoice_h_id = ?', [$invoiceId]);
                    $deletedInvoiceCount++;
                }
                $pdo->commit();
            } catch (Exception $cleanupEx) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $cleanupEx;
            }
        }

        // Calculate date range
        $invoiceStartDate = $dateFrom;
        if ($invoiceStartDate < $today) {
            $invoiceStartDate = $today;
        }

        $invoiceEndDate = $dateTo;
        if ($invoiceStartDate > $invoiceEndDate) {
            $orderResults[] = [
                'so_id' => $soId,
                'customer' => $customer['customer_name'] ?? '',
                'status' => 'skipped',
                'message' => 'No future invoice dates remain within the date range. Cleared ' . $deletedInvoiceCount . ' future invoice(s).',
            ];
            continue;
        }

        $totalDaysToCreate = (int)((new DateTime($invoiceStartDate))->diff(new DateTime($invoiceEndDate))->days) + 1;

        $invoiceCount = 0;

        $pdo->beginTransaction();
        try {

            for ($i = 0; $i < $totalDaysToCreate; $i++) {
                $deliveryDate = date('Y-m-d', strtotime($invoiceStartDate . " +$i days"));
                $checkDay = (int)date('w', strtotime($deliveryDate));
                $dayColumn = $dayNames[$checkDay] . '_qty';

                // Get items for this day
                $dayItems = [];
                foreach ($soItems as $item) {
                    $qty = (float)$item[$dayColumn];
                    if ($qty > 0) {
                        $dayItems[] = ['item_id' => $item['item_id'], 'qty' => $qty];
                    }
                }

                if (empty($dayItems)) {
                    continue;
                }

                // Check if invoice already exists
                $existingInvoice = $db->getRow(
                    "SELECT invoice_h_id FROM invoice_hedder
                     WHERE invoice_h_customer_id = ?
                     AND invoice_h_order_note = 'Standing Order'
                     AND invoice_h_delivery_date = ?",
                    [$customerId, $deliveryDate]
                );

                if ($existingInvoice) {
                    continue;
                }

                // Calculate net value (use cached prices to avoid redundant queries)
                $netValue = 0;
                foreach ($dayItems as $it) {
                    $iid = (int)$it['item_id'];
                    if (!array_key_exists($iid, $itemPriceCache)) {
                        $priceRow = $db->getRow('SELECT item_normal_selling_price FROM item_master WHERE item_id = ?', [$iid]);
                        $itemPriceCache[$iid] = (float)($priceRow['item_normal_selling_price'] ?? 0);
                    }
                    $netValue += $it['qty'] * $itemPriceCache[$iid];
                }

                // Resolve per-day delivery fee from the delivery rule (if any).
                if ($standingOrderFreeDelivery) {
                    $perDayDeliveryCost = 0.0;
                } elseif ($effRuleId > 0 && function_exists('calculateDeliveryFeeForRule')) {
                    // VAT is not applied in standing-order generation, so net == gross for tier lookup.
                    $tierBase = $netValue;
                    $fee = calculateDeliveryFeeForRule($effRuleId, $tierBase);
                    $perDayDeliveryCost = ($fee !== null) ? (float)$fee : (float)$deliveryAmount;
                } else {
                    $perDayDeliveryCost = (float)$deliveryAmount;
                }
                $grossValue = $netValue + $perDayDeliveryCost;

                // Get next invoice code
                $nextInvId = $db->getRow('SELECT MAX(invoice_h_id) AS maxid FROM invoice_hedder');
                $nextId = (int)($nextInvId['maxid'] ?? 0) + 1;
                $invCode = 'INV' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

                // Create invoice header
                $db->insertRow(
                    "INSERT INTO invoice_hedder (invoice_h_code, invoice_h_customer_id, invoice_h_date, invoice_h_datetime, invoice_h_location, invoice_h_delivery_city, delivery_city_name, invoice_h_delivery_cost, invoice_h_delivery_mode, invoice_h_pay_type, invoice_h_coupun_code, invoice_h_coupon_type, invoice_h_coupon_rate, invoice_h_coupon_value, invoice_h_net_value, invoice_h_vat_value, invoice_h_gross_value, invoice_h_order_note, invoice_h_delivery_name, invoice_h_delivery_address, invoice_h_delivery_contact_no, invoice_h_delivery_date, invoice_h_delivery_time, invoice_h_status, add_by, invoice_h_approve_date, CustomerCurrencyId, CurrencyRate) VALUES (?, ?, ?, NOW(), 1, 0, ?, ?, 0, 1, '', '', 0, 0, ?, 0, ?, 'Standing Order', ?, ?, ?, ?, '10:00-12:00', 1, 'System', NOW(), '0', 1)",
                    [
                        $invCode,
                        $customerId,
                        $deliveryDate,
                        $shipping['city'] ?? '',
                        $perDayDeliveryCost,
                        $netValue,
                        $grossValue,
                        $customer['customer_name'] ?? '',
                        trim(($shipping['address_line_1'] ?? '') . ' ' . ($shipping['address_line_2'] ?? '')),
                        $shipping['contact_no'] ?? '',
                        $deliveryDate,
                    ]
                );

                $invRow = $db->getRow('SELECT LAST_INSERT_ID() AS id');
                $invId = (int)($invRow['id'] ?? 0);

                // Insert details
                foreach ($dayItems as $it) {
                    $iid = (int)$it['item_id'];
                    if (!array_key_exists($iid, $itemPriceCache)) {
                        $priceRow = $db->getRow('SELECT item_normal_selling_price FROM item_master WHERE item_id = ?', [$iid]);
                        $itemPriceCache[$iid] = (float)($priceRow['item_normal_selling_price'] ?? 0);
                    }
                    $price = $itemPriceCache[$iid];
                    $total = $it['qty'] * $price;
                    $db->insertRow(
                        "INSERT INTO invoice_details (invoice_h_id, invoice_d_item_id, invoice_d_qty, invoice_d_balance, invoice_d_item_price, invoice_d_vat, invoice_d_vat_rate, invoice_d_discount_value, invoice_d_discount_type, invoice_d_discount_total, invoice_d_item_total, order_note) VALUES (?, ?, ?, ?, ?, 'N', 0, 0, 0, 0, ?, 'Standing Order')",
                        [$invId, $it['item_id'], $it['qty'], $it['qty'], $price, $total]
                    );
                }
                $invoiceCount++;
            }

            $pdo->commit();
        } catch (Exception $generateEx) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $generateEx;
        }

        $totalInvoices += $invoiceCount;
        $orderResults[] = [
            'so_id' => $soId,
            'customer' => $customer['customer_name'],
            'status' => 'success',
            'invoices_created' => $invoiceCount,
            'message' => $invoiceCount . ' invoice(s) created after clearing ' . $deletedInvoiceCount . ' future invoice(s)',
        ];
    }

    echo json_encode([
        'status' => 'success',
        'total_invoices' => $totalInvoices,
        'results' => $orderResults,
        'message' => 'Total invoices created: ' . $totalInvoices,
    ]);
} catch (Exception $e) {
    error_log('Generate Standing Order Invoices Error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Failed: ' . $e->getMessage()]);
}
