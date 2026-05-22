<?php
/**
 * Cart Order Process
 * Processes cart orders and creates invoice records
 */
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('../include/database.php');
include('../include/check_login.php');
include('../include/customer_access.php');
include('../include/price_helpers.php');
include_once('../include/delivery_rules.php');

date_default_timezone_set("Asia/Colombo");

$db = new Database();

function getOrderGstRateMap($db)
{
    $rates = [];

    try {
        $hasDstCodeTable = (bool) $db->getRow("SHOW TABLES LIKE 'DST_Code'");
        if ($hasDstCodeTable) {
            $rows = $db->getRows('SELECT Code, GSTPercentage FROM DST_Code');
            foreach ($rows as $row) {
                $code = trim((string) ($row['Code'] ?? ''));
                if ($code === '') {
                    continue;
                }
                $rates[$code] = (float) ($row['GSTPercentage'] ?? 0);
            }
        }
    } catch (Exception $e) {
        // ignore and continue fallback
    }

    try {
        $hasLegacyVatTable = (bool) $db->getRow("SHOW TABLES LIKE 'product_vat_master'");
        if ($hasLegacyVatTable) {
            $rows = $db->getRows('SELECT code, rate FROM product_vat_master');
            foreach ($rows as $row) {
                $code = trim((string) ($row['code'] ?? ''));
                if ($code === '' || isset($rates[$code])) {
                    continue;
                }
                $rates[$code] = (float) ($row['rate'] ?? 0);
            }
        }
    } catch (Exception $e) {
        // ignore fallback errors
    }

    return $rates;
}

function resolveGstRateFromCode($gstCode, array $rateMap)
{
    $code = trim((string) $gstCode);
    if ($code === '') {
        return 0.0;
    }

    if (isset($rateMap[$code])) {
        return max(0.0, (float) $rateMap[$code]);
    }

    if (preg_match('/([0-9]+(?:\.[0-9]+)?)/', $code, $matches)) {
        return max(0.0, (float) ($matches[1] ?? 0));
    }

    return 0.0;
}

try {
    $giftColCheck = $db->getRow("SHOW COLUMNS FROM invoice_details LIKE 'is_gift_item'");
    if (!$giftColCheck) {
        $db->insertRow("ALTER TABLE `invoice_details` ADD COLUMN `is_gift_item` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = Gift item with 100 percent discount, 0 = regular item'");
    }

    $purchaseOrderColCheck = $db->getRow("SHOW COLUMNS FROM invoice_hedder LIKE 'invoice_h_purchase_order_no'");
    if (!$purchaseOrderColCheck) {
        $db->insertRow("ALTER TABLE `invoice_hedder` ADD COLUMN `invoice_h_purchase_order_no` VARCHAR(100) NULL AFTER `invoice_h_code`");
    }
} catch (Exception $e) {
    // Continue; tracked SQL migration should normally create this column.
}

$nowDate = date("Y-m-d");
$nowTime = date("h:i:s");
$nowDateTime = date("Y-m-d H:i:s");
$invoice_location = $_SESSION['location'];
$session_user_f_name = $_SESSION['first_name'];

try {
    // Get form data
    $order_ref = $_POST['order_ref'];
    $order_type = $_POST['order_type'] ?? 'CART'; // CART, POS, STANDING, ONLINE
    $order_date = $_POST['order_date'] ?? $nowDate;

    // --- validate delivery date (no past dates allowed) ---
    $order_date_ts = @strtotime($order_date);
    if ($order_date_ts === false || $order_date_ts === null) {
        throw new Exception('Invalid delivery date');
    }
    $order_date = date('Y-m-d', $order_date_ts);
    if (strtotime($order_date) < strtotime($nowDate)) {
        throw new Exception('Delivery date cannot be in the past');
    }

    $customer_id = $_POST['customer_id'];
    $shipping_address_id = $_POST['shipping_address_id'] ?? null;
    $delivery_address_text = trim($_POST['delivery_address_text'] ?? '');
    // normalize shipping id to null when empty
    $shipping_address_id = (!empty($shipping_address_id) ? (int)$shipping_address_id : null);
    $payment_method = $_POST['payment_method'];
    $purchase_order_no = trim((string)($_POST['purchase_order_no'] ?? ''));
    $cart_data = json_decode($_POST['cart_data'], true);
    $subtotal = floatval($_POST['subtotal']);
    $delivery_charge = floatval($_POST['delivery_charge'] ?? 0);
    $discount = floatval($_POST['discount']);
    $grand_total = floatval($_POST['grand_total']);
    $editing_invoice_id = isset($_POST['editing_invoice_id']) ? (int)$_POST['editing_invoice_id'] : null;

    if (!function_exists('isSuperAdmin') || !isSuperAdmin()) {
        $hasCustomerDiscountColumn = (bool) $db->getRow("SHOW COLUMNS FROM customer LIKE 'customer_discount'");
        $discount = 0.0;
        if ($hasCustomerDiscountColumn) {
            $customerDiscountRow = $db->getRow('SELECT customer_discount FROM customer WHERE customer_id = ? LIMIT 1', [$customer_id]);
            if ($customerDiscountRow && isset($customerDiscountRow['customer_discount']) && $customerDiscountRow['customer_discount'] !== null && $customerDiscountRow['customer_discount'] !== '') {
                $discount = (float) $customerDiscountRow['customer_discount'];
            }
        }
    }
    
    if (empty($cart_data) || !is_array($cart_data)) {
        throw new Exception('Cart is empty');
    }
    
    if (empty($customer_id)) {
        throw new Exception('Customer is required');
    }

    $customerEligibilityError = getCustomerOrderEligibilityError($db, $customer_id);
    if ($customerEligibilityError !== null) {
        throw new Exception($customerEligibilityError);
    }
    
    // shipping_address_id OR delivery_address_text must be provided
    if (empty($shipping_address_id) && empty($delivery_address_text)) {
        throw new Exception('Shipping address is required');
    }

    // If a shipping_address_id was provided validate it belongs to the customer
    $shippingAddressRow = null;
    if (!empty($shipping_address_id)) {
        $shippingAddressRow = $db->getRow(
            'SELECT id, address_label, address_line_1, address_line_2, city, state, country, postal_code,
                    delivery_rule_id, so_daily_average, min_cart_order_override, weekly_avg_free_delivery_override
             FROM customer_shipping_address
             WHERE id = ? AND customer_id = ?
             LIMIT 1',
            [$shipping_address_id, $customer_id]
        );
        if (!$shippingAddressRow) {
            throw new Exception('Invalid shipping address');
        }
    }

    // invoice_h_delivery_address value to store (must be non-null)
    if (!empty($delivery_address_text)) {
        $invoice_delivery_address = $delivery_address_text;
    } elseif (!empty($shippingAddressRow)) {
        $parts = [];
        foreach (['address_label', 'address_line_1', 'address_line_2', 'city', 'state', 'country', 'postal_code'] as $field) {
            $value = trim((string)($shippingAddressRow[$field] ?? ''));
            if ($value !== '') {
                $parts[] = $value;
            }
        }
        $invoice_delivery_address = trim(implode(', ', $parts));
    } else {
        $invoice_delivery_address = '';
    }

    if ($invoice_delivery_address === '') {
        throw new Exception('Delivery address is required');
    }
    
    // Calculate discount type - will be set after calculating subtotal
    $coupon_code_type = "";
    $coupon_code_rate = "";
    $coupon_code_value = "0";
    
    // Check if we're editing an existing order
    if ($editing_invoice_id) {
        // Verify the invoice exists and belongs to this customer
        $existing_invoice = $db->getRow('SELECT invoice_h_id FROM invoice_hedder WHERE invoice_h_id = ? AND invoice_h_customer_id = ?', [$editing_invoice_id, $customer_id]);
        if (!$existing_invoice) {
            throw new Exception('Invoice not found or does not belong to this customer');
        }
        
        $invoice_h_id = $editing_invoice_id;
        
        // Update invoice header (store delivery address text when provided)
        $db->updateRow(
            'UPDATE invoice_hedder SET 
                invoice_h_delivery_date = ?, 
                invoice_h_delivery_address = ?,
                shipping_address_id = ?, 
                invoice_h_pay_type = ?, 
                invoice_h_purchase_order_no = ?,
                invoice_h_coupon_type = ?, 
                invoice_h_coupon_rate = ?, 
                invoice_h_coupon_value = ?,
                updated_at = NOW()
            WHERE invoice_h_id = ?',
            [$order_date, $invoice_delivery_address, $shipping_address_id, $payment_method, $purchase_order_no, $coupon_code_type, $coupon_code_rate, $coupon_code_value, $invoice_h_id]
        );
        
        // Get existing invoice details to restore stock for removed/changed items
        $existing_details = $db->getRows('SELECT invoice_d_item_id, invoice_d_qty FROM invoice_details WHERE invoice_h_id = ?', [$invoice_h_id]);
        
        // Build a map of existing items
        $existingItemsMap = [];
        foreach ($existing_details as $ed) {
            $existingItemsMap[$ed['invoice_d_item_id']] = floatval($ed['invoice_d_qty']);
        }
        
        // Delete existing invoice details
        $db->deleteRow('DELETE FROM invoice_details WHERE invoice_h_id = ?', [$invoice_h_id]);
        
    } else {
        // RULE: 1 customer can only have 1 order per day
        // Check if an order already exists for this customer on this delivery date
        $existing_order = $db->getRow(
            'SELECT invoice_h_id, invoice_h_code FROM invoice_hedder 
             WHERE invoice_h_customer_id = ? 
             AND invoice_h_delivery_date = ? 
             AND invoice_h_status IN (1, 2)',
            [$customer_id, $order_date]
        );
        
        if (!empty($existing_order['invoice_h_id'])) {
            throw new Exception('An order already exists for this customer on ' . $order_date . ' (Order: ' . $existing_order['invoice_h_code'] . '). Please edit the existing order instead.');
        }
        
        // Check if order ref already exists for new orders
        $check_code = $db->getRow('SELECT invoice_h_id FROM invoice_hedder WHERE invoice_h_code = ?', [$order_ref]);
        
        if (!empty($check_code['invoice_h_id'])) {
            throw new Exception('Order reference already exists');
        }
        
        // Insert invoice header with order_type, delivery address text (optional) and shipping_address_id
        $insert_result = $db->insertRow(
            'INSERT INTO invoice_hedder(
                invoice_h_code, invoice_h_customer_id, invoice_h_date, invoice_h_datetime, 
                invoice_h_delivery_date, invoice_h_delivery_address, invoice_h_location, invoice_h_pay_type, invoice_h_coupon_type, 
                invoice_h_coupon_rate, invoice_h_coupon_value, invoice_h_purchase_order_no, invoice_h_net_value, invoice_h_gross_value, 
                invoice_h_status, add_by, order_type, shipping_address_id
            ) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                $order_ref, $customer_id, $nowDate, $nowDateTime,
                $order_date, $invoice_delivery_address, $invoice_location, $payment_method, $coupon_code_type, 
                $coupon_code_rate, $coupon_code_value, $purchase_order_no, $grand_total, $subtotal,
                1, $session_user_f_name, $order_type, $shipping_address_id
            ]
        );
        
        // Get the inserted invoice ID
        $queryLastID = $db->getRow('SELECT invoice_h_id FROM invoice_hedder ORDER BY invoice_h_id DESC LIMIT 1');
        $invoice_h_id = $queryLastID['invoice_h_id'];
        
        $existingItemsMap = [];
    }
    
    if (!$invoice_h_id) {
        throw new Exception('Failed to create order');
    }
    
    // Process each cart item
    $gstRateMap = getOrderGstRateMap($db);
$calculatedNet = 0.0;
$calculatedGst = 0.0;
    $hasAllowInSalesColumn = (bool) $db->getRow("SHOW COLUMNS FROM item_master LIKE 'allow_in_sales'");
    foreach ($cart_data as $item) {
        $item_id = $item['id'];
        $item_qty = floatval($item['qty']);
        $item_price = floatval($item['price']);
        $item_discount_pct = isset($item['discount']) ? min(100, max(0, floatval($item['discount']))) : 0;
        $is_cart_item = isset($item['is_cart_item']) ? (int)$item['is_cart_item'] : 0;
        $is_gift_item = !empty($item['is_gift_item']) ? 1 : 0;

        if ($is_gift_item) {
            $item_discount_pct = 100;
        }

        $productLookupQuery = $hasAllowInSalesColumn
            ? 'SELECT item_name, allow_in_sales FROM item_master WHERE item_id = ? LIMIT 1'
            : 'SELECT item_name FROM item_master WHERE item_id = ? LIMIT 1';
        $prodCheck = $db->getRow($productLookupQuery, [$item_id]);
        if (!$prodCheck) {
            throw new Exception('Product not found: ' . $item_id);
        }
        if ($hasAllowInSalesColumn && array_key_exists('allow_in_sales', $prodCheck) && $prodCheck['allow_in_sales'] !== null && (int) $prodCheck['allow_in_sales'] !== 1) {
            throw new Exception('Cart orders cannot include products disabled for sales: ' . ($prodCheck['item_name'] ?? $item_id));
        }

        // Use the price from cart data (allows custom pricing for cart orders)
        $computedPrice = $item_price;

        // For validation, ensure it's a reasonable price (not negative, not zero for cart items)
        if ($computedPrice <= 0 && $is_cart_item) {
            throw new Exception('Invalid price for item: ' . ($prodCheck['item_name'] ?? $item_id));
        }

        $unit_price = $computedPrice;
        $item_discount_total = round($item_qty * $unit_price * $item_discount_pct / 100, 4);
        $item_total = round($item_qty * $unit_price - $item_discount_total, 4);
        $item_gst_rate = resolveGstRateFromCode($item['gst_code'] ?? '', $gstRateMap);
        $item_gst_total = round($item_total * ($item_gst_rate / 100), 4);
        $calculatedNet += $item_total;
        $calculatedGst += $item_gst_total;
        
        // Calculate stock difference for FIFO adjustment (only for editing)
        $old_qty = isset($existingItemsMap[$item_id]) ? $existingItemsMap[$item_id] : 0;
        $qty_diff = $item_qty - $old_qty;
        
        // Insert invoice detail with is_cart_item flag
        $db->insertRow(
            'INSERT INTO invoice_details(
                invoice_h_id, invoice_d_item_id, invoice_d_qty, invoice_d_item_price,
                invoice_d_discount_value, invoice_d_discount_total, invoice_d_item_total, is_cart_item, is_gift_item
            ) VALUES(?,?,?,?,?,?,?,?,?)',
            [$invoice_h_id, $item_id, $item_qty, $unit_price, $item_discount_pct, $item_discount_total, $item_total, $is_cart_item, $is_gift_item]
        );
        
        // Remove from existing items map (so we know what was removed)
        unset($existingItemsMap[$item_id]);
        
        // Update FIFO stock
        $query_get_qty_real = $db->getRow(
            'SELECT SUM(ft_blanace) as qty FROM fifo WHERE ft_item = ? AND ft_type = 1 AND ft_location = ?',
            [$item_id, $invoice_location]
        );
        $real_qty = $query_get_qty_real['qty'];
        
        if ($real_qty >= $item_qty) {
            // Reduce FIFO balance
            $remaining_qty = $item_qty;
            
            while ($remaining_qty > 0) {
                $fifo_record = $db->getRow(
                    "SELECT * FROM fifo WHERE ft_item = ? AND ft_type = 1 AND ft_location = ? AND ft_blanace > 0 ORDER BY ft_date ASC LIMIT 1",
                    [$item_id, $invoice_location]
                );
                
                if (!$fifo_record) break;
                
                $fifo_id = $fifo_record['ft_id'];
                $fifo_balance = $fifo_record['ft_blanace'];
                
                if ($fifo_balance <= $remaining_qty) {
                    // Use entire FIFO record
                    $db->updateRow('UPDATE fifo SET ft_blanace = 0 WHERE ft_id = ?', [$fifo_id]);
                    $remaining_qty -= $fifo_balance;
                } else {
                    // Partial use of FIFO record
                    $new_balance = $fifo_balance - $remaining_qty;
                    $db->updateRow('UPDATE fifo SET ft_blanace = ? WHERE ft_id = ?', [$new_balance, $fifo_id]);
                    $remaining_qty = 0;
                }
            }
        }
    }
    
    // Recalculate and update invoice header with delivery and totals
    if (!is_numeric($delivery_charge) || $delivery_charge < 0) {
        throw new Exception('Invalid delivery charge');
    }
    if (!is_numeric($discount) || $discount < 0 || $discount > 100) {
        $discount = 0;
    }

    // ---- Delivery rule integration (cart order) ----
    // Resolve effective rule + thresholds: per-shipping-address overrides take precedence over globals.
    $globalDelSettings = function_exists('getDeliveryRuleSettings') ? getDeliveryRuleSettings() : [];
    $globalApplyTo = $globalDelSettings['apply_to'] ?? 'gross';
    $globalMinCart = isset($globalDelSettings['min_cart_order']) ? (float)$globalDelSettings['min_cart_order'] : 0.0;
    $globalWeeklyFree = isset($globalDelSettings['weekly_avg_free_delivery']) ? (float)$globalDelSettings['weekly_avg_free_delivery'] : 0.0;

    $effRuleId = 0;
    $effMinCart = $globalMinCart;
    $effWeeklyFree = $globalWeeklyFree;
    if (!empty($shippingAddressRow)) {
        if (isset($shippingAddressRow['delivery_rule_id']) && $shippingAddressRow['delivery_rule_id'] !== null && $shippingAddressRow['delivery_rule_id'] !== '') {
            $effRuleId = (int)$shippingAddressRow['delivery_rule_id'];
        }
        if (isset($shippingAddressRow['min_cart_order_override']) && $shippingAddressRow['min_cart_order_override'] !== null && $shippingAddressRow['min_cart_order_override'] !== '') {
            $effMinCart = (float)$shippingAddressRow['min_cart_order_override'];
        }
        if (isset($shippingAddressRow['weekly_avg_free_delivery_override']) && $shippingAddressRow['weekly_avg_free_delivery_override'] !== null && $shippingAddressRow['weekly_avg_free_delivery_override'] !== '') {
            $effWeeklyFree = (float)$shippingAddressRow['weekly_avg_free_delivery_override'];
        }
    }

    // Enforce minimum cart order threshold (uses NET subtotal before delivery & discount).
    if ($effMinCart > 0 && $calculatedNet < $effMinCart) {
        throw new Exception('Minimum cart order for delivery is $' . number_format($effMinCart, 2) . '. Current subtotal is $' . number_format($calculatedNet, 2) . '.');
    }

    // If a delivery rule is assigned, override the posted delivery_charge with the computed tier fee.
    if ($effRuleId > 0 && function_exists('calculateDeliveryFeeForRule')) {
        // Tier base mirrors the configured apply_to. VAT is not calculated here, so net == gross.
        $tierBase = $calculatedNet;
        // Free delivery if this single order already meets the (interpreted) free-delivery threshold.
        if ($effWeeklyFree > 0 && $calculatedNet >= $effWeeklyFree) {
            $delivery_charge = 0.0;
        } else {
            $fee = calculateDeliveryFeeForRule($effRuleId, $tierBase);
            if ($fee !== null) {
                $delivery_charge = (float)$fee;
            }
            // If no tier matched we keep the user-supplied value as a fallback.
        }
    }

    // Calculate discount
    if ($discount > 0) {
        $coupon_code_type = "PCT";
        $coupon_code_rate = $discount;
        $coupon_code_value = $calculatedNet * $discount / 100;
    }

    // GST should include delivery GST (DEL code) and reflect discount impact proportionally.
    $deliveryGstRate = resolveGstRateFromCode('DEL', $gstRateMap);
    $deliveryGstTotal = round($delivery_charge * ($deliveryGstRate / 100), 4);
    $discountGstTotal = 0.0;
    if ($calculatedNet > 0 && $coupon_code_value > 0) {
        $discountGstTotal = round($calculatedGst * ($coupon_code_value / $calculatedNet), 4);
    }
    $calculatedGstAdjusted = max(0, $calculatedGst + $deliveryGstTotal - $discountGstTotal);

    // Ensure discount does not exceed amount. Gross includes adjusted GST.
    $grossTotal = max(0, $calculatedNet + $delivery_charge + $calculatedGstAdjusted - $coupon_code_value);

    $db->updateRow('UPDATE invoice_hedder SET invoice_h_delivery_cost = ?, invoice_h_coupon_type = ?, invoice_h_coupon_rate = ?, invoice_h_coupon_value = ?, invoice_h_net_value = ?, invoice_h_gross_value = ? WHERE invoice_h_id = ?', [$delivery_charge, $coupon_code_type, $coupon_code_rate, $coupon_code_value, $calculatedNet, $grossTotal, $invoice_h_id]);

    // Clear cart session
    unset($_SESSION['cart_items']);
    unset($_SESSION['cart_customer_id']);
    
    // --- Send order confirmation email (non-blocking) ---
    try {
        require_once(__DIR__ . '/../include/EmailService.php');
        $emailService = new EmailService();
        if ($emailService->isEnabled()) {
            $emailOrderType = ($order_type === 'STANDING') ? 'standing_order' : 'cart_order';
            $emailService->sendOrderConfirmation($invoice_h_id, $emailOrderType);
        }
    } catch (Exception $emailEx) {
        // Email failure should never block order creation
        error_log('Cart Order Email Error: ' . $emailEx->getMessage());
    }
    
    // Redirect to order detail page after successful cart order creation
    header("Location: ../order-detail.php?order_id=" . $invoice_h_id);
    exit;
    
} catch (Exception $e) {
    // Redirect back with error
    header("Location: ../cart-order.php?error=" . urlencode($e->getMessage()));
    exit;
}
