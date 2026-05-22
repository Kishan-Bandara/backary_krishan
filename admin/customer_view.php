<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

session_start();
include('include/database.php');
include('include/check_login.php');

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function getShippingAddressAvailability($shippingAddressId) {
    try {
        $db = new Database();
        $row = $db->getRow('SELECT id, mon, tue, wed, thu, fri, sat, sun FROM shipping_address_availability WHERE shipping_address_id = ? LIMIT 1', [$shippingAddressId]);
        return $row ?: null;
    } catch (Exception $e) {
        return null;
    }
}

function getShippingAddressDayRoutes($shippingAddressId) {
    try {
        $db = new Database();
        $row = $db->getRow('SELECT sdr.*, 
            rm_mon.route_name AS mon_route_name,
            rm_tue.route_name AS tue_route_name,
            rm_wed.route_name AS wed_route_name,
            rm_thu.route_name AS thu_route_name,
            rm_fri.route_name AS fri_route_name,
            rm_sat.route_name AS sat_route_name,
            rm_sun.route_name AS sun_route_name
            FROM shipping_address_day_route sdr
            LEFT JOIN delivery_route_master rm_mon ON rm_mon.id = sdr.mon_route_id
            LEFT JOIN delivery_route_master rm_tue ON rm_tue.id = sdr.tue_route_id
            LEFT JOIN delivery_route_master rm_wed ON rm_wed.id = sdr.wed_route_id
            LEFT JOIN delivery_route_master rm_thu ON rm_thu.id = sdr.thu_route_id
            LEFT JOIN delivery_route_master rm_fri ON rm_fri.id = sdr.fri_route_id
            LEFT JOIN delivery_route_master rm_sat ON rm_sat.id = sdr.sat_route_id
            LEFT JOIN delivery_route_master rm_sun ON rm_sun.id = sdr.sun_route_id
            WHERE sdr.shipping_address_id = ? LIMIT 1', [(int)$shippingAddressId]);
        return $row ?: null;
    } catch (Exception $e) {
        return null;
    }
}

function getRepeatIntervalName($interval, $unitId) {
    if (empty($interval) || empty($unitId)) {
        return '';
    }
    
    try {
        $db = new Database();
        $unit = $db->getRow('SELECT display_name FROM repeat_units WHERE id = ? LIMIT 1', [(int)$unitId]);
        if ($unit && !empty($unit['display_name'])) {
            $interval = (int)$interval;
            $unitName = $unit['display_name'];
            
            // Map common intervals to descriptive names
            if (strtolower($unitName) === 'day') {
                switch ($interval) {
                    case 1: return 'Daily';
                    case 7: return 'Weekly';
                    case 14: return 'Bi-weekly';
                    case 30: return 'Monthly';
                    default: return $interval . ' ' . ($interval > 1 ? 'Days' : 'Day');
                }
            } elseif (strtolower($unitName) === 'week') {
                switch ($interval) {
                    case 1: return 'Weekly';
                    case 2: return 'Bi-weekly';
                    case 4: return 'Monthly';
                    default: return $interval . ' ' . ($interval > 1 ? 'Weeks' : 'Week');
                }
            } elseif (strtolower($unitName) === 'month') {
                switch ($interval) {
                    case 1: return 'Monthly';
                    case 3: return 'Quarterly';
                    case 6: return 'Semi-annually';
                    case 12: return 'Annually';
                    default: return $interval . ' ' . ($interval > 1 ? 'Months' : 'Month');
                }
            }
            
            // Fallback
            return $interval . ' ' . $unitName . ($interval > 1 ? 's' : '');
        }
    } catch (Exception $e) {
        // Fall back to simple display
    }
    
    // Final fallback
    return $interval . ' ' . $unitId;
}

$message = '';
$MessageClass = '';
$db = null;

$customerId = isset($_GET['customerID']) ? (int) $_GET['customerID'] : 0;
if ($customerId <= 0) {
    header('location:index.php');
    exit();
}

try {
    $db = new Database();
} catch (Exception $e) {
    $message = 'Database connection error: ' . $e->getMessage();
    $MessageClass = 'alert-danger';
}

$customer = null;
if ($db) {
    try {
        $customer = $db->getRow('SELECT * FROM customer WHERE customer_id = ?', [$customerId]);
    } catch (Exception $e) {
        $message = 'Unable to load customer record: ' . $e->getMessage();
        $MessageClass = 'alert-danger';
    }

    if (!$customer) {
        header('location:index.php');
        exit();
    }
}

// Handle price type update from dropdown (only on this view page)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_price_type']) && $db) {
    $newPriceTypeId = (int)($_POST['price_type_id'] ?? 0);
    if ($newPriceTypeId > 0) {
        try {
            $existing = $db->getRow('SELECT id FROM price_type_customer_mapping WHERE customer_id = ? LIMIT 1', [$customerId]);
            if ($existing && !empty($existing['id'])) {
                $db->updateRow('UPDATE price_type_customer_mapping SET price_type_id = ? WHERE id = ?', [$newPriceTypeId, $existing['id']]);
            } else {
                $db->insertRow('INSERT INTO price_type_customer_mapping (price_type_id, customer_id) VALUES (?, ?)', [$newPriceTypeId, $customerId]);
            }
            $message = 'Price type updated successfully.';
            $MessageClass = 'alert-success';
        } catch (Exception $e) {
            $message = 'Unable to update price type: ' . $e->getMessage();
            $MessageClass = 'alert-danger';
        }
    } else {
        $message = 'Please select a valid price type.';
        $MessageClass = 'alert-warning';
    }

    // Refresh mapping info for display below
    try {
        $shippingAddresses = $db->getRows('SELECT * FROM customer_shipping_address WHERE customer_id = ? ORDER BY is_default DESC, id ASC', [$customerId]) ?: [];
    } catch (Exception $e) {
        $shippingAddresses = [];
    }
    try {
        $invoiceHistory = $db->getRows('SELECT * FROM invoice_hedder WHERE invoice_h_customer_id = ? ORDER BY invoice_h_id DESC', [$customerId]) ?: [];
    } catch (Exception $e) {
        $invoiceHistory = [];
    }
}

// Prepare view data
$formData = [
    'customer_code' => $customer['customer_code'] ?? '',
    'customer_name' => $customer['customer_name'] ?? '',
    'customer_email' => $customer['customer_email'] ?? '',
    'customer_phone' => $customer['customer_tell'] ?? '',
    'customer_mobile' => $customer['customer_mobile'] ?? '',
    'address_line_1' => $customer['address_line_1'] ?? ($customer['customer_address'] ?? ''),
    'address_line_2' => $customer['address_line_2'] ?? '',
    'city' => $customer['city'] ?? '',
    'postal_code' => $customer['postal_code'] ?? '',
    'credit_limit' => $customer['credit_limit'] !== null ? number_format((float) $customer['credit_limit'], 2, '.', '') : '0.00',
    'account_hold' => (int) ($customer['account_hold'] ?? 0),
    'abn_no' => $customer['abn_no'] ?? '',
    'acn_no' => $customer['acn_no'] ?? '',
    'vat_registered' => (int) ($customer['vat_registered'] ?? 0),
    'gst_no' => $customer['gst_no'] ?? '',
    'payment_terms_id' => $customer['payment_terms_id'] !== null ? (string) $customer['payment_terms_id'] : '',
    'customer_price_type_id' => $customer['customer_price_type_id'] !== null ? (string) $customer['customer_price_type_id'] : '',
    'customer_note' => $customer['customer_note'] ?? '',
    'is_active' => (int) ($customer['is_active'] ?? 1),
    'locked' => (int) ($customer['locked'] ?? 0),
    'customer_logo' => $customer['customer_logo'] ?? '',
    'customer_outstanding_balance' => $customer['customer_outstanding_balance'] ?? 0,
    'repeat_interval' => $customer['RepeatInterval'] ?? '',
    'repeat_unit' => $customer['RepeatUnit'] ?? '',
    'legal_name' => $customer['legal_name'] ?? '',
    'trading_name' => $customer['trading_name'] ?? '',
    'customer_remarks' => $customer['customer_remarks'] ?? '',
];

$customerAdditionalEmails = [];
if ($db) {
    try {
        $rows = $db->getRows('SELECT email_address FROM customer_email_accounts WHERE customer_id = ? ORDER BY id ASC', [$customerId]);
        foreach ($rows as $row) {
            if (!empty($row['email_address'])) {
                $customerAdditionalEmails[] = $row['email_address'];
            }
        }
    } catch (Exception $e) {
        $customerAdditionalEmails = [];
    }
}

$shippingAddresses = [];
if ($db) {
    try {
        $shippingAddresses = $db->getRows(
            'SELECT * FROM customer_shipping_address WHERE customer_id = ? ORDER BY is_default DESC, id ASC',
            [$customerId]
        ) ?: [];
    } catch (Exception $e) {
        $shippingAddresses = [];
    }
}

$invoiceHistory = [];
if ($db) {
    try {
        $invoiceHistory = $db->getRows(
            'SELECT * FROM invoice_hedder WHERE invoice_h_customer_id = ? ORDER BY invoice_h_id DESC',
            [$customerId]
        ) ?: [];
    } catch (Exception $e) {
        $invoiceHistory = [];
    }
}

$complianceDocument = [
    'file_path' => '',
    'file_name' => '',
    'updated_at' => '',
];
if ($db) {
    try {
        $complianceDocumentRow = $db->getRow('SELECT file_path, file_name, updated_at, created_at FROM customer_compliance_documents WHERE customer_id = ? LIMIT 1', [$customerId]);
        if ($complianceDocumentRow) {
            $complianceDocument = [
                'file_path' => $complianceDocumentRow['file_path'] ?? '',
                'file_name' => $complianceDocumentRow['file_name'] ?: (!empty($complianceDocumentRow['file_path']) ? basename((string) $complianceDocumentRow['file_path']) : ''),
                'updated_at' => $complianceDocumentRow['updated_at'] ?? $complianceDocumentRow['created_at'] ?? '',
            ];
        }
    } catch (Exception $e) {
        $complianceDocument = [
            'file_path' => '',
            'file_name' => '',
            'updated_at' => '',
        ];
    }
}

// Use currency from currency.php (DB-driven), fallback to LKR
$currencySymbol = (function () {
    ob_start();
    @include 'currency.php';
    $sym = trim(ob_get_clean());
    return $sym !== '' ? $sym : 'LKR';
})();

// Fetch standing orders data
$activeStandingOrders = [];
$historicalStandingOrders = [];
if ($db) {
    try {
        // Get all standing orders for this customer
        $allStandingOrders = $db->getRows(
            'SELECT so.*, sa.address_label, sa.city, sa.state, sa.country 
             FROM standing_order so 
             LEFT JOIN customer_shipping_address sa ON so.shipping_address_id = sa.id 
             WHERE so.customer_id = ? 
             ORDER BY so.created_at DESC',
            [$customerId]
        ) ?: [];
        
        foreach ($allStandingOrders as $so) {
            // Get items for this standing order
            $items = $db->getRows(
                'SELECT soi.*, im.item_name, im.item_code 
                 FROM standing_order_item soi 
                 JOIN item_master im ON soi.item_id = im.item_id 
                 WHERE soi.standing_order_id = ? 
                 ORDER BY im.item_name ASC',
                [$so['id']]
            ) ?: [];
            
            // Format items display
            $itemsDisplay = [];
            foreach ($items as $item) {
                $dayQtys = [];
                $days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
                foreach ($days as $day) {
                    $qty = (float)($item[$day . '_qty'] ?? 0);
                    if ($qty > 0) {
                        $dayQtys[] = ucfirst(substr($day, 0, 3)) . ': ' . $qty;
                    }
                }
                if (!empty($dayQtys)) {
                    $itemsDisplay[] = h($item['item_name']) . ' (' . implode(', ', $dayQtys) . ')';
                }
            }
            
            // Calculate next delivery date (simplified - just add repeat interval to today)
            $nextDelivery = null;
            if (!empty($so['RepeatInterval']) && !empty($so['RepeatUnit'])) {
                $interval = (int)$so['RepeatInterval'];
                $unit = strtolower($so['RepeatUnit']);
                
                if ($unit === 'day') {
                    $nextDelivery = date('Y-m-d', strtotime("+$interval days"));
                } elseif ($unit === 'week') {
                    $nextDelivery = date('Y-m-d', strtotime("+$interval weeks"));
                } elseif ($unit === 'month') {
                    $nextDelivery = date('Y-m-d', strtotime("+$interval months"));
                }
            }
            
            // Format schedule display
            $schedule = getRepeatIntervalName($so['RepeatInterval'] ?? '', $so['RepeatUnit'] ?? '');
            if (empty($schedule)) {
                $schedule = 'Custom Schedule';
            }
            
            $orderData = [
                'id' => 'SO-' . str_pad($so['id'], 4, '0', STR_PAD_LEFT),
                'schedule' => $schedule,
                'items' => implode('; ', $itemsDisplay),
                'start_date' => date('Y-m-d', strtotime($so['created_at'])),
                'next_delivery' => $nextDelivery,
                'status' => (int)($so['active'] ?? 0) ? 'Active' : 'Inactive',
                'delivery_amount' => $currencySymbol . ' ' . number_format((float)($so['DeliveryAmount'] ?? 0), 2),
                'shipping_address' => !empty($so['address_label']) ? h($so['address_label']) : 'Default Address'
            ];
            
            if ((int)($so['active'] ?? 0)) {
                $activeStandingOrders[] = $orderData;
            } else {
                // For historical orders, add end date (when it was deactivated)
                $orderData['end_date'] = date('Y-m-d', strtotime($so['updated_at']));
                $orderData['last_delivered'] = $orderData['end_date']; // Simplified
                $historicalStandingOrders[] = $orderData;
            }
        }
    } catch (Exception $e) {
        $activeStandingOrders = [];
        $historicalStandingOrders = [];
    }
}

// Resolve payment terms name from id
$paymentTermsName = '-';
if ($db) {
    try {
        if ($formData['payment_terms_id'] !== '') {
            $ptn = $db->getRow('SELECT payment_terms_name FROM payment_terms WHERE payment_terms_id = ? LIMIT 1', [(int)$formData['payment_terms_id']]);
            if ($ptn && isset($ptn['payment_terms_name']) && $ptn['payment_terms_name'] !== '') {
                $paymentTermsName = $ptn['payment_terms_name'];
            }
        }
    } catch (Exception $e) {
        // leave as '-'
    }
}

$creditLimitDisplay = $currencySymbol . ' ' . number_format((float) $formData['credit_limit'], 2);
$outstandingDisplay = $currencySymbol . ' ' . number_format((float) ($formData['customer_outstanding_balance'] ?? 0), 2);
$accountHoldLabel = $formData['account_hold'] ? 'On Hold' : 'Open';
$vatRegisteredLabel = $formData['vat_registered'] ? 'Registered' : 'Not Registered';
$isActiveLabel = $formData['is_active'] ? 'Active' : 'Inactive';
$lockedLabel = $formData['locked'] ? 'Locked' : 'Unlocked';

// Badges for colorful UI
$isActiveBadge = '<span class="badge-soft ' . ($formData['is_active'] ? 'badge-soft-success' : 'badge-soft-danger') . '">' . h($isActiveLabel) . '</span>';
$accountHoldBadge = '<span class="badge-soft ' . ($formData['account_hold'] ? 'badge-soft-danger' : 'badge-soft-success') . '">' . h($accountHoldLabel) . '</span>';
$lockedBadge = '<span class="badge-soft ' . ($formData['locked'] ? 'badge-soft-warning' : 'badge-soft-info') . '">' . h($lockedLabel) . '</span>';
$vatBadge = '<span class="badge-soft ' . ($formData['vat_registered'] ? 'badge-soft-purple' : 'badge-soft-secondary') . '">' . h($vatRegisteredLabel) . '</span>';
// Amount badges
$creditLimitBadge = '<span class="badge-soft badge-soft-primary">' . h($creditLimitDisplay) . '</span>';
$outstandingAmount = (float)($formData['customer_outstanding_balance'] ?? 0);
$outstandingBadge = '<span class="badge-soft ' . ($outstandingAmount > 0 ? 'badge-soft-danger' : 'badge-soft-success') . '">' . h($outstandingDisplay) . '</span>';

// Compute sales summary from invoice history with resilient field fallbacks
$salesCount = 0;
$salesTotal = 0.0;
$lastInvoiceDate = null; // string
foreach ($invoiceHistory as $row) {
    $salesCount++;
    $total = 0.0;
    if (isset($row['invoice_total']) && is_numeric($row['invoice_total'])) {
        $total = (float)$row['invoice_total'];
    } elseif (isset($row['grand_total']) && is_numeric($row['grand_total'])) {
        $total = (float)$row['grand_total'];
    } elseif (isset($row['total']) && is_numeric($row['total'])) {
        $total = (float)$row['total'];
    }
    $salesTotal += $total;
    $dateStr = $row['invoice_date'] ?? ($row['invoice_h_date'] ?? ($row['date'] ?? null));
    if ($dateStr) {
        $ts = strtotime((string)$dateStr);
        if ($ts !== false) {
            if ($lastInvoiceDate === null || $ts > strtotime((string)$lastInvoiceDate)) {
                $lastInvoiceDate = date('Y-m-d', $ts);
            }
        }
    }
}
$salesTotalDisplay = $currencySymbol . ' ' . number_format($salesTotal, 2);

// Resolve customer's price type description.
$priceTypeLabel = '-';
if ($db) {
    try {
        $priceTypeRow = $db->getRow(
            'SELECT pt.id, pt.description FROM price_type_customer_mapping pcm JOIN price_type pt ON pcm.price_type_id = pt.id WHERE pcm.customer_id = ? LIMIT 1',
            [$customerId]
        );
    } catch (Exception $e) {
        $priceTypeRow = null;
    }

    // Fallback: if mapping not found, try customer's own price type id
    if (empty($priceTypeRow) && !empty($customer['customer_price_type_id'])) {
        try {
            $priceTypeRow = $db->getRow('SELECT id, description FROM price_type WHERE id = ? LIMIT 1', [(int)$customer['customer_price_type_id']]);
        } catch (Exception $e) {
            $priceTypeRow = null;
        }
    }

    if (!empty($priceTypeRow) && !empty($priceTypeRow['description'])) {
        $priceTypeLabel = h($priceTypeRow['description']);
    } else {
        // If we still don't have a description, show the raw id if present
        if (isset($customer['customer_price_type_id']) && $customer['customer_price_type_id'] !== '') {
            $priceTypeLabel = h((string)$customer['customer_price_type_id']);
        }
    }
}

// Fetch list of available price types for dropdown
$priceTypes = [];
$currentPriceTypeId = null;
if ($db) {
    try {
        $priceTypes = $db->getRows('SELECT id, description FROM price_type ORDER BY description') ?: [];
    } catch (Exception $e) {
        $priceTypes = [];
    }

    if (!empty($priceTypeRow) && !empty($priceTypeRow['id'])) {
        $currentPriceTypeId = (int)$priceTypeRow['id'];
    } elseif (!empty($customer['customer_price_type_id'])) {
        $currentPriceTypeId = (int)$customer['customer_price_type_id'];
    }
}

?>
<!DOCTYPE html>
<!--[if IE 8]> <html lang="en" class="ie8 no-js"> <![endif]-->
<!--[if IE 9]> <html lang="en" class="ie9 no-js"> <![endif]-->
<!--[if !IE]><!-->
<html lang="en">
<!--<![endif]-->

<head>
    <meta charset="utf-8" />
    <title>Customer View</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <meta content="" name="description" />
    <meta content="" name="author" />
    <?php include('common/head.php'); ?>
    <style>
        .section-card {
            background: #ffffff;
            border: 1px solid #dde3ec;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 6px 18px rgba(52, 73, 94, 0.08);
            margin-bottom: 24px;
        }

        .section-card h4 {
            /* Make all tile headers colorful */
            margin: -24px -24px 18px -24px; /* stretch header to card edges */
            padding: 12px 16px;
            font-size: 15px;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #ffffff;
            background: linear-gradient(135deg, #3f51b5 0%, #00bcd4 100%); /* indigo to cyan */
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
            box-shadow: inset 0 -1px 0 rgba(255,255,255,0.15);
            text-shadow: 0 1px 0 rgba(0, 0, 0, 0.15);
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 13px;
            color: #4a5a73;
        }

        .info-row span:first-child {
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #6e7a90;
        }

        .shipping-view-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .shipping-view-list li {
            border: 1px solid #e7edf5;
            border-radius: 8px;
            padding: 14px 16px;
            margin-bottom: 10px;
            background: #f7faff;
        }

        .shipping-view-list strong {
            display: block;
            font-size: 12px;
            color: #4a5a73;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .customer-logo-preview img {
            max-width: 100%;
            height: auto;
            border-radius: 6px;
            margin-bottom: 14px;
            border: 1px solid #dbe2ef;
        }

        .muted {
            color: #7b8aa0;
        }

        /* Soft badges for colorful, subtle status/values */
        .badge-soft {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid transparent;
            line-height: 1.4;
            vertical-align: middle;
        }

        .badge-soft-success {
            background: #e8f5e9;
            color: #1b5e20;
            border-color: #c8e6c9;
        }

        .badge-soft-danger {
            background: #ffebee;
            color: #b71c1c;
            border-color: #ffcdd2;
        }

        .badge-soft-warning {
            background: #fff8e1;
            color: #8d6e63;
            border-color: #ffe0b2;
        }

        .badge-soft-info {
            background: #e0f7fa;
            color: #006064;
            border-color: #b2ebf2;
        }

        .badge-soft-purple {
            background: #f3e5f5;
            color: #4a148c;
            border-color: #e1bee7;
        }

        .badge-soft-secondary {
            background: #eceff1;
            color: #37474f;
            border-color: #cfd8dc;
        }

        .badge-soft-primary {
            background: #e3f2fd;
            color: #0d47a1;
            border-color: #bbdefb;
        }

        /* Equal-height helper: make sibling columns' .section-card match heights */
        .equal-height {
            display: flex;
            align-items: stretch;
            flex-wrap: wrap;
        }

        .equal-height > [class*='col-'] {
            display: flex;
            flex-direction: column;
        }

        .equal-height .section-card {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
        }

        /* Even / Odd section header color pairing (JS will add classes) */
        .section-card.section-odd h4 {
            background: linear-gradient(135deg, #3f51b5 0%, #00bcd4 100%); /* indigo -> cyan */
        }

        .section-card.section-even h4 {
            background: linear-gradient(135deg, #8e24aa 0%, #ff7043 100%); /* purple -> orange */
        }

        /* Table-like striped rows for billing address body */
        .striped-body {
            border-radius: 4px;
            overflow: hidden;
            border: none; /* lighter look */
        }

        .striped-body .info-row {
            padding: 6px 8px; /* reduced padding */
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            color: #3e4b63;
        }

        .striped-body .info-row:nth-child(odd) {
            background: #f5f5f5; /* subtle, nearly white */
        }

        .striped-body .info-row:nth-child(even) {
            background: #f7f7ff; /* very subtle tint */
        }

        .striped-body .info-row + .info-row {
            border-top: none; /* remove dividing border for a cleaner look */
        }

        /* Apply similar compact striping to other section-card direct info rows */
        .section-card > .info-row {
            padding: 6px 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            color: #3e4b63;
        }

        .section-card > .info-row:nth-child(odd) {
            background: #ffffff;
        }

        .section-card > .info-row:nth-child(even) {
            background: #fbfbfc;
        }

        /* Shipping addresses list: make items compact and subtly striped */
        .shipping-view-list li {
            padding: 8px 10px;
            margin-bottom: 8px;
            background: #ffffff;
            border: 1px solid #f3f6fa;
        }

        .shipping-view-list li:nth-child(even) {
            background: #fbfbfc;
        }
    </style>
    <script>
        // Safeguard for pages that may be linked from elsewhere
        function goBackFallback() {
            if (document.referrer) {
                window.history.back();
            } else {
                window.location.href = 'manage-customer.php';
            }
        }
    </script>
    <script>
        // Tag section-card elements as odd/even for consistent header striping
        (function() {
            try {
                var cards = document.querySelectorAll('.section-card');
                for (var i = 0; i < cards.length; i++) {
                    cards[i].classList.remove('section-odd', 'section-even');
                    cards[i].classList.add((i % 2 === 0) ? 'section-odd' : 'section-even');
                }
            } catch (e) {
                // Ignore errors on older browsers
            }
        })();
    </script>
    <meta name="robots" content="noindex, nofollow" />
    <meta http-equiv="Cache-Control" content="no-store" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
</head>

<body class="page-sidebar-closed-hide-logo page-content-white">
    <?php if ($db) {
        include('common/manubar.php');
    } else { ?>
        <div class="page-header navbar navbar-fixed-top">
            <div class="page-header-inner ">
                <div class="page-logo">
                    <a href="index.php">Dashboard</a>
                </div>
                <div class="page-top">
                    <div class="top-menu">
                        <ul class="nav navbar-nav pull-right">
                            <li class="dropdown dropdown-user">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" data-close-others="true">
                                    <span class="username username-hide-on-mobile"> Customer View </span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>
    <div class="clearfix"></div>
    <div class="page-container">
        <div class="page-sidebar-wrapper">
            <?php if ($db) {
                include('common/sidebar.php');
            } else { ?>
                <div class="page-sidebar navbar-collapse collapse">
                    <ul class="page-sidebar-menu" data-keep-expanded="false" data-auto-scroll="true" data-slide-speed="200">
                        <li class="heading">
                            <h3 class="uppercase">Menu</h3>
                        </li>
                        <li class="nav-item start">
                            <a href="index.php" class="nav-link ">
                                <i class="icon-home"></i>
                                <span class="title">Home</span>
                            </a>
                        </li>
                    </ul>
                </div>
            <?php } ?>
        </div>
        <div class="page-content-wrapper">
            <div class="page-content">
                <div class="page-bar">
                    <ul class="page-breadcrumb">
                        <li>
                            <a href="index.php">Home</a>
                            <i class="fa fa-circle"></i>
                        </li>
                        <li>
                            <a href="manage-customer.php">Customers</a>
                            <i class="fa fa-circle"></i>
                        </li>
                        <li>
                            <span>View</span>
                        </li>
                    </ul>
                    <div class="page-toolbar">
                        <div class="btn-group pull-right">
                            <a class="btn btn-fit-height blue" href="edit-customer.php?customerID=<?php echo (int)$customerId; ?>">
                                <i class="fa fa-pencil"></i> Edit
                            </a>
                            <button class="btn btn-fit-height default" onclick="goBackFallback()">
                                <i class="fa fa-arrow-left"></i> Back
                            </button>
                        </div>
                    </div>
                </div>

                <?php if ($message !== ''): ?>
                    <div class="alert <?php echo h($MessageClass ?: 'alert-info'); ?> alert-dismissable">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                        <?php echo nl2br(h($message)); ?>
                    </div>
                <?php endif; ?>

                <div class="portlet-body">
                    <div class="tabbable-custom">
                        <ul class="nav nav-tabs">
                            <li class="active">
                                <a href="#tab_profile" data-toggle="tab" aria-expanded="true">Customer Profile</a>
                            </li>
                            <li>
                                <a href="#tab_sales" data-toggle="tab" aria-expanded="false">Customer Sales</a>
                            </li>
                            <li>
                                <a href="#tab_standing" data-toggle="tab" aria-expanded="false">Standing Orders</a>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane active" id="tab_profile">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="section-card">
                                            <h4>Customer Overview</h4>
                                            <?php if ($formData['customer_logo']): ?>
                                                <div class="customer-logo-preview">
                                                    <img src="<?php echo h($formData['customer_logo']); ?>" alt="Customer Logo">
                                                </div>
                                            <?php endif; ?>
                                            <div class="info-row"><span>Name</span><span><?php echo h($formData['customer_name']); ?></span></div>
                                            <?php if ($formData['legal_name'] !== ''): ?>
                                                <div class="info-row"><span>Legal Name</span><span><?php echo h($formData['legal_name']); ?></span></div>
                                            <?php endif; ?>
                                            <?php if ($formData['trading_name'] !== ''): ?>
                                                <div class="info-row"><span>Trading Name</span><span><?php echo h($formData['trading_name']); ?></span></div>
                                            <?php endif; ?>
                                            <?php if ($formData['customer_remarks'] !== ''): ?>
                                                <div class="info-row"><span>Remarks</span><span><?php echo h($formData['customer_remarks']); ?></span></div>
                                            <?php endif; ?>
                                            <div class="info-row"><span>Code</span><span><?php echo h($formData['customer_code']); ?></span></div>
                                            <div class="info-row"><span>Status</span><span><?php echo $isActiveBadge; ?></span></div>
                                            <div class="info-row"><span>Account</span><span><?php echo $accountHoldBadge; ?></span></div>
                                            <div class="info-row"><span>Lock</span><span><?php echo $lockedBadge; ?></span></div>
                                            <?php if ($formData['abn_no'] !== ''): ?>
                                                <div class="info-row"><span>ABN</span><span><?php echo h($formData['abn_no']); ?></span></div>
                                            <?php endif; ?>
                                            <?php if ($formData['acn_no'] !== ''): ?>
                                                <div class="info-row"><span>ACN</span><span><?php echo h($formData['acn_no']); ?></span></div>
                                            <?php endif; ?>
                                            <?php if ($formData['gst_no'] !== ''): ?>
                                                <div class="info-row"><span>GST No</span><span><?php echo h($formData['gst_no']); ?></span></div>
                                            <?php endif; ?>
                                            <?php if (!empty($customerAdditionalEmails)): ?>
                                                <div class="info-row"><span>Additional Emails</span><span><?php echo h(implode(', ', $customerAdditionalEmails)); ?></span></div>
                                            <?php endif; ?>
                                            <?php if (!empty($complianceDocument['file_path'])): ?>
                                                <div class="info-row">
                                                    <span>Compliance PDF</span>
                                                    <span>
                                                        <a href="../<?php echo h($complianceDocument['file_path']); ?>" download="<?php echo h($complianceDocument['file_name']); ?>" target="_blank" rel="noopener">Download</a>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($formData['repeat_interval'] !== ''): ?>
                                                <div class="info-row"><span>Repeat Interval</span><span><?php echo h(getRepeatIntervalName($formData['repeat_interval'], $formData['repeat_unit'])); ?></span></div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="section-card">
                                            <h4>Sales Summary</h4>
                                            <div class="info-row"><span>Total Invoices</span><span><?php echo (int)$salesCount; ?></span></div>
                                            <div class="info-row"><span>Total Billed</span><span><?php echo h($salesTotalDisplay); ?></span></div>
                                            <div class="info-row"><span>Last Invoice</span><span><?php echo h($lastInvoiceDate ?: '-'); ?></span></div>
                                        </div>

                                        <div class="section-card">
                                            <h4>Contact</h4>
                                            <div class="info-row"><span>Email</span><span><?php echo h($formData['customer_email']); ?></span></div>
                                            <div class="info-row"><span>Phone</span><span><?php echo h($formData['customer_phone']); ?></span></div>
                                            <div class="info-row"><span>Mobile</span><span><?php echo h($formData['customer_mobile']); ?></span></div>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="row equal-height">
                                            <div class="col-md-6">
                                                <div class="section-card">
                                                    <h4>Billing Address</h4>
                                                    <p class="muted" style="margin-bottom: 6px;">
                                                            <?php echo nl2br(h(trim($formData['address_line_1'] . "\n" . $formData['address_line_2']))); ?>
                                                        </p>
                                                        <div class="striped-body">
                                                            <div class="info-row"><span>City</span><span><?php echo h($formData['city']); ?></span></div>
                                                            <div class="info-row"><span>State</span><span><?php echo isset($customer['state']) ? h($customer['state']) : '-'; ?></span></div>
                                                            <div class="info-row"><span>Country</span><span><?php echo isset($customer['country']) ? h($customer['country']) : '-'; ?></span></div>
                                                            <div class="info-row"><span>Postal Code</span><span><?php echo h($formData['postal_code']); ?></span></div>
                                                        </div>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="section-card">
                                                    <h4>Financial & Tax</h4>
                                                    <div class="info-row">
                                                        <span>Payment Terms</span>
                                                        <span><?php echo h($paymentTermsName); ?></span>
                                                    </div>
                                                    <div class="info-row">
                                                        <span>Price Type</span>
                                                        <span>
                                                            <?php if ($db && !empty($priceTypes)): ?>
                                                                <form method="post" class="form-inline" style="display:inline">
                                                                    <div class="form-group" style="margin-right:6px; display:inline-block;">
                                                                        <select name="price_type_id" class="form-control input-sm">
                                                                            <option value="">-- Select --</option>
                                                                            <?php foreach ($priceTypes as $pt): ?>
                                                                                <option value="<?php echo (int)$pt['id']; ?>" <?php echo ($currentPriceTypeId !== null && (int)$pt['id'] === (int)$currentPriceTypeId) ? 'selected' : ''; ?>><?php echo h($pt['description']); ?></option>
                                                                            <?php endforeach; ?>
                                                                        </select>
                                                                    </div>
                                                                    <div style="display:inline-block; vertical-align:middle;">
                                                                        <button type="submit" name="update_price_type" class="btn btn-primary btn-sm">Save</button>
                                                                    </div>
                                                                </form>
                                                            <?php else: ?>
                                                                <?php echo h($priceTypeLabel); ?>
                                                            <?php endif; ?>
                                                        </span>
                                                    </div>
                                                    <div class="info-row"><span>Credit Limit</span><span><?php echo $creditLimitBadge; ?></span></div>
                                                    <div class="info-row"><span>Outstanding</span><span><?php echo $outstandingBadge; ?></span></div>
                                                    <div class="info-row"><span>GST Status</span><span><?php echo $vatBadge; ?></span></div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="section-card">
                                            <h4>Shipping Addresses</h4>
                                            <?php if (empty($shippingAddresses)): ?>
                                                <p class="muted">No shipping addresses defined.</p>
                                            <?php else: ?>
                                                <ul class="shipping-view-list">
                                                    <?php foreach ($shippingAddresses as $ship): ?>
                                                        <li>
                                                            <strong>
                                                                <?php echo h($ship['address_label'] ?: 'Address'); ?>
                                                                <?php if (!empty($ship['is_default'])): ?>
                                                                    <span class="badge-soft badge-soft-primary">Default</span>
                                                                <?php endif; ?>
                                                            </strong>
                                                            <div><?php echo nl2br(h(trim(($ship['address_line_1'] ?? '') . "\n" . ($ship['address_line_2'] ?? '')))); ?></div>
                                                            <div class="muted">
                                                                <?php echo h(trim(($ship['city'] ?? ''))); ?>
                                                                <?php if (!empty($ship['state'])): ?>, <?php echo h($ship['state']); ?><?php endif; ?>
                                                                <?php if (!empty($ship['country'])): ?>, <?php echo h($ship['country']); ?><?php endif; ?>
                                                                <?php if (!empty($ship['postal_code'])): ?>, <?php echo h($ship['postal_code']); ?><?php endif; ?>
                                                            </div>
                                                            <?php if (!empty($ship['contact_no'])): ?>
                                                                <div class="muted">Contact: <?php echo h($ship['contact_no']); ?></div>
                                                            <?php endif; ?>
                                                            <?php if (!empty($ship['contact_person_name'])): ?>
                                                                <div class="muted">Contact Person: <?php echo h($ship['contact_person_name']); ?></div>
                                                            <?php endif; ?>
                                                            <?php if (!empty($ship['contact_person_phone'])): ?>
                                                                <div class="muted">Contact Phone: <?php echo h($ship['contact_person_phone']); ?></div>
                                                            <?php endif; ?>
                                                            <?php if (!empty($ship['contact_person_email'])): ?>
                                                                <div class="muted">Contact Email: <?php echo h($ship['contact_person_email']); ?></div>
                                                            <?php endif; ?>
                                                            <?php if (!empty($ship['remarks'])): ?>
                                                                <div class="muted">Remarks: <?php echo h($ship['remarks']); ?></div>
                                                            <?php endif; ?>
                                                            <?php if (!empty($ship['note_to_deliver'])): ?>
                                                                <div class="muted">Driver Note: <?php echo h($ship['note_to_deliver']); ?></div>
                                                            <?php endif; ?>
                                                            <?php if (!empty($ship['delivery_time_from']) || !empty($ship['delivery_time_till'])): ?>
                                                                <div class="muted">
                                                                    Delivery: <?php echo h($ship['delivery_time_from'] ?? ''); ?> - <?php echo h($ship['delivery_time_till'] ?? ''); ?>
                                                                </div>
                                                            <?php endif; ?>
                                                            <?php
                                                            $routeName = '';
                                                            if (!empty($ship['delivery_route_id']) && $db) {
                                                                try {
                                                                    $route = $db->getRow('SELECT route_name FROM delivery_route_master WHERE id = ? LIMIT 1', [(int)$ship['delivery_route_id']]);
                                                                    if ($route) {
                                                                        $routeName = $route['route_name'];
                                                                    }
                                                                } catch (Exception $e) {
                                                                    // Ignore
                                                                }
                                                            }
                                                            if ($routeName !== ''): ?>
                                                                <div class="muted">Delivery Route: <?php echo h($routeName); ?></div>
                                                            <?php endif; ?>
                                                            <?php if (!empty($ship['has_door_key']) || !empty($ship['has_shop_alarm'])): ?>
                                                                <div class="muted">
                                                                    <?php
                                                                    $security = [];
                                                                    if (!empty($ship['has_door_key'])) $security[] = 'Door Key';
                                                                    if (!empty($ship['has_shop_alarm'])) $security[] = 'Shop Alarm';
                                                                    echo 'Security: ' . h(implode(', ', $security));
                                                                    ?>
                                                                </div>
                                                            <?php endif; ?>
                                                            <?php
                                                            $availability = getShippingAddressAvailability($ship['id']);
                                                            if ($availability): ?>
                                                                <div class="muted">
                                                                    <strong>Delivery Days:</strong>
                                                                    <?php
                                                                    $days = [];
                                                                    $dayLabels = ['mon' => 'Mon', 'tue' => 'Tue', 'wed' => 'Wed', 'thu' => 'Thu', 'fri' => 'Fri', 'sat' => 'Sat', 'sun' => 'Sun'];
                                                                    foreach ($dayLabels as $key => $label) {
                                                                        if ($availability[$key]) {
                                                                            $days[] = $label;
                                                                        }
                                                                    }
                                                                    if (empty($days)) {
                                                                        echo '<span class="text-muted">No delivery days set</span>';
                                                                    } else {
                                                                        echo '<span class="text-success">' . h(implode(', ', $days)) . '</span>';
                                                                    }
                                                                    ?>
                                                                </div>
                                                            <?php else: ?>
                                                                <div class="muted">
                                                                    <strong>Delivery Days:</strong> <span class="text-muted">All days available</span>
                                                                </div>
                                                            <?php endif; ?>
                                                            <?php
                                                            $dayRouteData = getShippingAddressDayRoutes($ship['id']);
                                                            if ($dayRouteData):
                                                                $dayDisplayMap = ['mon' => 'Mon', 'tue' => 'Tue', 'wed' => 'Wed', 'thu' => 'Thu', 'fri' => 'Fri', 'sat' => 'Sat', 'sun' => 'Sun'];
                                                                $hasAnyRoute = false;
                                                                foreach (array_keys($dayDisplayMap) as $dk) {
                                                                    if (!empty($dayRouteData[$dk . '_route_name'])) { $hasAnyRoute = true; break; }
                                                                }
                                                                if ($hasAnyRoute):
                                                            ?>
                                                                <div class="muted" style="margin-top: 6px;">
                                                                    <strong>Delivery Routes:</strong>
                                                                    <div style="margin-top: 4px; overflow-x: auto;">
                                                                        <table style="font-size: 11px; border-collapse: collapse; min-width: 300px;">
                                                                            <tr>
                                                                                <?php foreach ($dayDisplayMap as $dk => $dl): ?>
                                                                                <td style="text-align:center; padding: 2px 6px; font-weight:600; border-bottom: 1px solid #ddd; color:#555;"><?php echo $dl; ?></td>
                                                                                <?php endforeach; ?>
                                                                            </tr>
                                                                            <tr>
                                                                                <?php foreach ($dayDisplayMap as $dk => $dl): ?>
                                                                                <td style="text-align:center; padding: 2px 6px; border-top: 1px solid #eee; font-size: 10px; color: #28a745;">
                                                                                    <?php echo !empty($dayRouteData[$dk . '_route_name']) ? h($dayRouteData[$dk . '_route_name']) : '<span style="color:#ccc;">—</span>'; ?>
                                                                                </td>
                                                                                <?php endforeach; ?>
                                                                            </tr>
                                                                        </table>
                                                                    </div>
                                                                </div>
                                                            <?php endif; endif; ?>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php endif; ?>
                                        </div>

                                        <?php if ($formData['customer_note'] !== ''): ?>
                                            <div class="section-card">
                                                <h4>Notes</h4>
                                                <div class="muted"><?php echo nl2br(h($formData['customer_note'])); ?></div>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Metadata section removed per requirements -->
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane" id="tab_sales">
                                <div class="section-card">
                                    <h4>Invoice History</h4>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered" id="invoice_history_table">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Number</th>
                                                    <th>Date</th>
                                                    <th>Total</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($invoiceHistory)): ?>
                                                    <tr>
                                                        <td colspan="5" class="text-center muted">No invoices found.</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($invoiceHistory as $row): ?>
                                                        <?php
                                                        $invId = $row['invoice_h_id'] ?? '';
                                                        $invNum = $row['invoice_number'] ?? ($row['invoice_h_number'] ?? ($row['invoice_no'] ?? ''));
                                                        $invDate = $row['invoice_date'] ?? ($row['invoice_h_date'] ?? ($row['date'] ?? ''));
                                                        $invTotal = $row['invoice_total'] ?? ($row['total'] ?? ($row['grand_total'] ?? ''));
                                                        $invTotalVal = is_numeric($invTotal) ? (float)$invTotal : 0.0;
                                                        $invTotalDisplay = $currencySymbol . ' ' . number_format($invTotalVal, 2);
                                                        $invStatus = $row['status'] ?? ($row['invoice_status'] ?? '');
                                                        ?>
                                                        <tr>
                                                            <td><?php echo h($invId); ?></td>
                                                            <td><?php echo h($invNum); ?></td>
                                                            <td><?php echo h($invDate); ?></td>
                                                            <td><?php echo h($invTotalDisplay); ?></td>
                                                            <td><?php echo h($invStatus); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane" id="tab_standing">
                                <div class="section-card">
                                    <h4>Currently Active Standing Orders</h4>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered" id="active_standing_orders_table">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Schedule</th>
                                                    <th>Items</th>
                                                    <th>Start Date</th>
                                                    <th>Next Delivery</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($activeStandingOrders)): ?>
                                                    <tr>
                                                        <td colspan="6" class="text-center muted">No active standing orders found.</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($activeStandingOrders as $order): ?>
                                                        <tr>
                                                            <td><?php echo h($order['id']); ?></td>
                                                            <td><?php echo h($order['schedule']); ?></td>
                                                            <td><?php echo h($order['items']); ?></td>
                                                            <td><?php echo h($order['start_date']); ?></td>
                                                            <td><?php echo h($order['next_delivery'] ?: '-'); ?></td>
                                                            <td><span class="badge-soft badge-soft-success"><?php echo h($order['status']); ?></span></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="section-card">
                                    <h4>Standing Orders History</h4>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered" id="history_standing_orders_table">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Schedule</th>
                                                    <th>Items</th>
                                                    <th>Start Date</th>
                                                    <th>End Date</th>
                                                    <th>Last Delivered</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($historicalStandingOrders)): ?>
                                                    <tr>
                                                        <td colspan="7" class="text-center muted">No historical standing orders found.</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($historicalStandingOrders as $order): ?>
                                                        <tr>
                                                            <td><?php echo h($order['id']); ?></td>
                                                            <td><?php echo h($order['schedule']); ?></td>
                                                            <td><?php echo h($order['items']); ?></td>
                                                            <td><?php echo h($order['start_date']); ?></td>
                                                            <td><?php echo h($order['end_date'] ?: '-'); ?></td>
                                                            <td><?php echo h($order['last_delivered'] ?: '-'); ?></td>
                                                            <td><span class="badge-soft badge-soft-secondary"><?php echo h($order['status']); ?></span></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php include('common/footer.php'); ?>
<script src="assets/global/plugins/respond.min.js"></script>
<script src="assets/global/plugins/excanvas.min.js"></script> 

        <!-- BEGIN CORE PLUGINS -->
        <script src="assets/global/plugins/jquery.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/js.cookie.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/bootstrap-hover-dropdown/bootstrap-hover-dropdown.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/jquery-slimscroll/jquery.slimscroll.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/jquery.blockui.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/uniform/jquery.uniform.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/bootstrap-switch/js/bootstrap-switch.min.js" type="text/javascript"></script>
        <!-- END CORE PLUGINS -->
        <!-- BEGIN PAGE LEVEL PLUGINS -->
        <script src="assets/global/scripts/datatable.js" type="text/javascript"></script>
        <script src="assets/global/plugins/datatables/datatables.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.js" type="text/javascript"></script>
        <!-- END PAGE LEVEL PLUGINS -->
        <!-- BEGIN THEME GLOBAL SCRIPTS -->
        <script src="assets/global/scripts/app.min.js" type="text/javascript"></script>
        <!-- END THEME GLOBAL SCRIPTS -->
        <!-- BEGIN PAGE LEVEL SCRIPTS -->
        <script src="assets/pages/scripts/table-datatables-responsive.min.js" type="text/javascript"></script>
        <!-- END PAGE LEVEL SCRIPTS -->
        <!-- BEGIN THEME LAYOUT SCRIPTS -->
        <script src="assets/layouts/layout/scripts/layout.min.js" type="text/javascript"></script>
        <script src="assets/layouts/layout/scripts/demo.min.js" type="text/javascript"></script>
        <script src="assets/layouts/global/scripts/quick-sidebar.min.js" type="text/javascript"></script>
        <!-- END THEME LAYOUT SCRIPTS -->
    <script>
        // Init DataTable if available
        (function() {
            if (window.jQuery && jQuery.fn && jQuery.fn.DataTable) {
                jQuery('#invoice_history_table').DataTable({
                    pageLength: 10,
                    order: [
                        [0, 'desc']
                    ]
                });
            }
        })();
    </script>
</body>

</html>



