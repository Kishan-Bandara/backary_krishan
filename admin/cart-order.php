<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');
include('include/customer_access.php');
include('include/price_helpers.php');
include_once('include/delivery_rules.php');

$db = new Database();

// Auto-migrate: Add cart-order item flags if not exists
try {
    $col_check = $db->getRow("SHOW COLUMNS FROM invoice_details LIKE 'is_cart_item'");
    if (!$col_check) {
        $db->insertRow("ALTER TABLE `invoice_details` ADD COLUMN `is_cart_item` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = Added via cart, 0 = Standing order item' AFTER `order_note`", []);
    }

    $gift_col_check = $db->getRow("SHOW COLUMNS FROM invoice_details LIKE 'is_gift_item'");
    if (!$gift_col_check) {
        $db->insertRow("ALTER TABLE `invoice_details` ADD COLUMN `is_gift_item` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = Gift item with 100 percent discount, 0 = regular item' AFTER `is_cart_item`", []);
    }

    $purchase_order_col_check = $db->getRow("SHOW COLUMNS FROM invoice_hedder LIKE 'invoice_h_purchase_order_no'");
    if (!$purchase_order_col_check) {
        $db->insertRow("ALTER TABLE `invoice_hedder` ADD COLUMN `invoice_h_purchase_order_no` VARCHAR(100) NULL AFTER `invoice_h_code`", []);
    }
} catch (Exception $e) {
    // Ignore if already exists or permission issues
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    ob_clean(); // Discard any buffered output from includes (prevents JSON corruption on live server)
    header('Content-Type: application/json');
    try {
        if ($_POST['action'] === 'get_customer_shipping_addresses') {
            $customerId = $_POST['customer_id'] ?? null;
            if (!$customerId) {
                echo json_encode([]);
                exit;
            }
            if (getCustomerOrderEligibilityError($db, $customerId) !== null) {
                echo json_encode([]);
                exit;
            }
            $addresses = getCustomerShippingAddresses($customerId);
            echo json_encode($addresses);
            exit;
        } elseif ($_POST['action'] === 'get_customer_line_discount') {
            $customerId = (int) ($_POST['customer_id'] ?? 0);
            if ($customerId <= 0) {
                echo json_encode(['status' => 'error', 'discount' => 0]);
                exit;
            }

            $hasLineDiscountPercentageColumn = (bool) $db->getRow("SHOW COLUMNS FROM customer LIKE 'line_discount_percentage'");
            $hasLineDiscountColumn = (bool) $db->getRow("SHOW COLUMNS FROM customer LIKE 'line_discount_id'");
            $hasDiscountTable = (bool) $db->getRow("SHOW TABLES LIKE 'discount_code'");

            $discount = 0.0;

            if ($hasLineDiscountPercentageColumn) {
                $row = $db->getRow('SELECT line_discount_percentage FROM customer WHERE customer_id = ? LIMIT 1', [$customerId]);
                if ($row && isset($row['line_discount_percentage']) && $row['line_discount_percentage'] !== null && $row['line_discount_percentage'] !== '') {
                    $discount = (float) $row['line_discount_percentage'];
                }
            }

            if ($discount <= 0 && $hasLineDiscountColumn && $hasDiscountTable) {
                $row = $db->getRow(
                    'SELECT COALESCE(dc.percentage, 0) AS percentage
                     FROM customer c
                     LEFT JOIN discount_code dc ON dc.id = c.line_discount_id
                     WHERE c.customer_id = ?
                     LIMIT 1',
                    [$customerId]
                );
                if ($row && isset($row['percentage'])) {
                    $discount = (float) $row['percentage'];
                }
            }

            if ($discount < 0) {
                $discount = 0;
            }
            if ($discount > 100) {
                $discount = 100;
            }

            echo json_encode(['status' => 'success', 'discount' => $discount]);
            exit;
        } elseif ($_POST['action'] === 'get_customer_order_discount') {
            $customerId = (int) ($_POST['customer_id'] ?? 0);
            if ($customerId <= 0) {
                echo json_encode(['status' => 'error', 'discount' => 0]);
                exit;
            }

            $hasCustomerDiscountColumn = (bool) $db->getRow("SHOW COLUMNS FROM customer LIKE 'customer_discount'");
            $discount = 0.0;

            if ($hasCustomerDiscountColumn) {
                $row = $db->getRow('SELECT customer_discount FROM customer WHERE customer_id = ? LIMIT 1', [$customerId]);
                if ($row && isset($row['customer_discount']) && $row['customer_discount'] !== null && $row['customer_discount'] !== '') {
                    $discount = (float) $row['customer_discount'];
                }
            }

            if ($discount < 0) {
                $discount = 0;
            }
            if ($discount > 100) {
                $discount = 100;
            }

            echo json_encode(['status' => 'success', 'discount' => $discount]);
            exit;
        } elseif ($_POST['action'] === 'get_shipping_address_details') {
            $shippingAddressId = $_POST['shipping_address_id'] ?? null;
            if (!$shippingAddressId) {
                echo json_encode(['error' => 'Shipping address ID required']);
                exit;
            }
            $address = $db->getRow('SELECT csa.*, drm.route_name, drm.amount as route_amount
                                   FROM customer_shipping_address csa
                                   LEFT JOIN delivery_route_master drm ON csa.delivery_route_id = drm.id
                                   WHERE csa.id = ? LIMIT 1', [$shippingAddressId]);
            if (!$address) {
                echo json_encode(['error' => 'Shipping address not found']);
                exit;
            }
            // Attach delivery rule context: tiers + global fallback so the UI can preview live charges.
            $globalDel = function_exists('getDeliveryRuleSettings') ? getDeliveryRuleSettings() : [];
            $address['global_delivery_settings'] = [
                'apply_to' => $globalDel['apply_to'] ?? 'gross',
                'weekly_avg_free_delivery' => isset($globalDel['weekly_avg_free_delivery']) ? (float)$globalDel['weekly_avg_free_delivery'] : 0,
                'min_cart_order' => isset($globalDel['min_cart_order']) ? (float)$globalDel['min_cart_order'] : 0,
            ];
            $tiers = [];
            if (!empty($address['delivery_rule_id']) && function_exists('getDeliveryRuleTiers')) {
                $rawTiers = getDeliveryRuleTiers((int)$address['delivery_rule_id']);
                foreach ($rawTiers as $t) {
                    $tiers[] = [
                        'invoice_larger_than' => (float)$t['invoice_larger_than'],
                        'price' => (float)$t['price'],
                    ];
                }
                $rule = $db->getRow('SELECT name FROM delivery_rules WHERE id = ?', [(int)$address['delivery_rule_id']]);
                $address['delivery_rule_name'] = $rule['name'] ?? '';
            }
            $address['delivery_rule_tiers'] = $tiers;
            echo json_encode($address);
            exit;
        } elseif ($_POST['action'] === 'get_customer_order_dates') {
            // Get ALL order dates for a customer (for calendar highlighting)
            $customerId = $_POST['customer_id'] ?? null;
            if (!$customerId) {
                echo json_encode([]);
                exit;
            }
            if (getCustomerOrderEligibilityError($db, $customerId) !== null) {
                echo json_encode([]);
                exit;
            }
            $orders = $db->getRows(
                "SELECT invoice_h_id, invoice_h_code, invoice_h_delivery_date, invoice_h_order_note,
                        invoice_h_net_value, invoice_h_gross_value, invoice_h_status
                 FROM invoice_hedder 
                 WHERE invoice_h_customer_id = ? 
                 AND invoice_h_status IN (1, 2)
                 AND invoice_h_delivery_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                 ORDER BY invoice_h_delivery_date ASC",
                [$customerId]
            );
            $result = [];
            foreach ($orders as $order) {
                $result[] = [
                    'invoice_id' => $order['invoice_h_id'],
                    'invoice_code' => $order['invoice_h_code'],
                    'delivery_date' => $order['invoice_h_delivery_date'],
                    'order_note' => $order['invoice_h_order_note'],
                    'gross_value' => floatval($order['invoice_h_gross_value']),
                    'status' => $order['invoice_h_status']
                ];
            }
            echo json_encode($result);
            exit;
        } elseif ($_POST['action'] === 'get_standing_order_dates') {
            // Get all standing order invoices for a customer
            $customerId = $_POST['customer_id'] ?? null;
            
            if (!$customerId) {
                echo json_encode([]);
                exit;
            }
            if (getCustomerOrderEligibilityError($db, $customerId) !== null) {
                echo json_encode([]);
                exit;
            }
            
            // Get all standing order invoices for this customer (future and recent past)
            $orders = $db->getRows(
                "SELECT invoice_h_id, invoice_h_code, invoice_h_delivery_date, invoice_h_order_note,
                        invoice_h_net_value, invoice_h_gross_value, invoice_h_status
                 FROM invoice_hedder 
                 WHERE invoice_h_customer_id = ? 
                 AND invoice_h_order_note = 'Standing Order'
                 AND invoice_h_status IN (1, 2)
                 AND invoice_h_delivery_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                 ORDER BY invoice_h_delivery_date ASC",
                [$customerId]
            );
            
            $result = [];
            foreach ($orders as $order) {
                $result[] = [
                    'invoice_id' => $order['invoice_h_id'],
                    'invoice_code' => $order['invoice_h_code'],
                    'delivery_date' => $order['invoice_h_delivery_date'],
                    'order_note' => $order['invoice_h_order_note'],
                    'net_value' => floatval($order['invoice_h_net_value']),
                    'gross_value' => floatval($order['invoice_h_gross_value']),
                    'status' => $order['invoice_h_status']
                ];
            }
            
            echo json_encode($result);
            exit;
        } elseif ($_POST['action'] === 'load_existing_order') {
            try {
                // Load existing order for customer and date (or by invoice_id)
                $customerId = $_POST['customer_id'] ?? null;
                $orderDate = $_POST['order_date'] ?? null;
                $invoiceId = $_POST['invoice_id'] ?? null;

                error_log("load_existing_order called with: customerId=$customerId, orderDate=$orderDate, invoiceId=$invoiceId");

                if (!$customerId && !$invoiceId) {
                    echo json_encode(['status' => 'error', 'message' => 'Customer required']);
                    exit;
                }
            
            // Find existing order - by invoice_id or by date
            if ($invoiceId) {
                if ($customerId) {
                    $existingOrder = $db->getRow(
                        "SELECT invoice_h_id, invoice_h_code, invoice_h_customer_id, shipping_address_id, 
                                invoice_h_delivery_date, invoice_h_delivery_cost, invoice_h_delivery_address, invoice_h_coupon_value,
                                invoice_h_net_value, invoice_h_gross_value, invoice_h_pay_type, invoice_h_order_note,
                                invoice_h_coupon_type, invoice_h_coupon_rate, invoice_h_purchase_order_no
                         FROM invoice_hedder 
                         WHERE invoice_h_id = ?
                         AND invoice_h_customer_id = ?
                         AND invoice_h_status IN (1, 2)
                         LIMIT 1",
                        [$invoiceId, $customerId]
                    );
                } else {
                    $existingOrder = $db->getRow(
                        "SELECT invoice_h_id, invoice_h_code, invoice_h_customer_id, shipping_address_id, 
                                invoice_h_delivery_date, invoice_h_delivery_cost, invoice_h_delivery_address, invoice_h_coupon_value,
                                invoice_h_net_value, invoice_h_gross_value, invoice_h_pay_type, invoice_h_order_note,
                                invoice_h_coupon_type, invoice_h_coupon_rate, invoice_h_purchase_order_no
                         FROM invoice_hedder 
                         WHERE invoice_h_id = ?
                         AND invoice_h_status IN (1, 2)
                         LIMIT 1",
                        [$invoiceId]
                    );
                }
            } else if ($orderDate) {
                $existingOrder = $db->getRow(
                    "SELECT invoice_h_id, invoice_h_code, invoice_h_customer_id, shipping_address_id, 
                            invoice_h_delivery_date, invoice_h_delivery_cost, invoice_h_delivery_address, invoice_h_coupon_value,
                            invoice_h_net_value, invoice_h_gross_value, invoice_h_pay_type, invoice_h_order_note,
                            invoice_h_coupon_type, invoice_h_coupon_rate, invoice_h_purchase_order_no
                     FROM invoice_hedder 
                     WHERE invoice_h_customer_id = ? 
                     AND invoice_h_delivery_date = ?
                     AND invoice_h_status IN (1, 2)
                     ORDER BY invoice_h_id DESC LIMIT 1",
                    [$customerId, $orderDate]
                );
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Order date or invoice ID required']);
                exit;
            }
            
            if (!$existingOrder) {
                error_log("No existing order found for invoiceId=$invoiceId, customerId=$customerId, orderDate=$orderDate");
                echo json_encode(['status' => 'empty', 'message' => 'No existing order found']);
                exit;
            }

            $customerId = (int) ($existingOrder['invoice_h_customer_id'] ?? $customerId);
            $customerEligibilityError = getCustomerOrderEligibilityError($db, $customerId);
            if ($customerEligibilityError !== null) {
                echo json_encode(['status' => 'error', 'message' => $customerEligibilityError]);
                exit;
            }

            error_log("Found order: " . json_encode($existingOrder));
            
            // Get order items
            $orderItems = $db->getRows(
                "SELECT id.invoice_d_id, id.invoice_d_item_id, id.invoice_d_qty, id.invoice_d_item_price, 
                    id.invoice_d_item_total, id.invoice_d_discount_value, id.is_cart_item, id.is_gift_item,
                        COALESCE(im.item_name, 'Unknown Item') as item_name, COALESCE(im.item_code, '') as item_code,
                        COALESCE(im.gst_vat_code, '') as gst_code,
                        COALESCE(tm.type_name, '') as item_group
                 FROM invoice_details id
                 LEFT JOIN item_master im ON id.invoice_d_item_id = im.item_id
                 LEFT JOIN type_master tm ON im.item_type = tm.type_id
                 LEFT JOIN category_master cm ON im.item_category = cm.category_id
                 WHERE id.invoice_h_id = ?",
                [$existingOrder['invoice_h_id']]
            );
            
            // Look up original standing order quantities for this customer & delivery day
            $soQtyMap = []; // item_id => original SO qty for that day
            $deliveryDate = $existingOrder['invoice_h_delivery_date'];
            if ($deliveryDate) {
                try {
                    $dayOfWeek = strtolower(date('D', strtotime($deliveryDate))); // mon, tue, wed, etc.
                    $dayCol = $dayOfWeek . '_qty'; // e.g. mon_qty, tue_qty
                    
                    $soItems = $db->getRows(
                        "SELECT soi.item_id, soi.{$dayCol} AS so_qty
                         FROM standing_order so
                         JOIN standing_order_item soi ON soi.standing_order_id = so.id
                         WHERE so.customer_id = ? AND so.active = 1",
                        [$customerId]
                    );
                    if ($soItems) {
                        foreach ($soItems as $soItem) {
                            $soQtyMap[$soItem['item_id']] = floatval($soItem['so_qty']);
                        }
                    }
                } catch (Exception $e) {
                    error_log("Error loading standing order quantities: " . $e->getMessage());
                    // Continue without SO quantities
                }
            }
            
            $items = [];
            foreach ($orderItems as $item) {
                $itemId = $item['invoice_d_item_id'];
                // Load price tiers for this item
                $itemTiers = [];
                try {
                    $tiersCheck = $db->getRow("SHOW TABLES LIKE 'item_price_tiers'");
                    if ($tiersCheck) {
                        $tierRows = $db->getRows('SELECT min_qty, unit_price FROM item_price_tiers WHERE item_id = ? ORDER BY min_qty ASC', [$itemId]);
                        foreach ($tierRows as $tr) {
                            $itemTiers[] = ['min_qty' => floatval($tr['min_qty']), 'unit_price' => floatval($tr['unit_price'])];
                        }
                    }
                } catch (Exception $e) {}
                $items[] = [
                    'detail_id' => $item['invoice_d_id'],
                    'id' => $itemId,
                    'name' => $item['item_name'],
                    'code' => $item['item_code'],
                    'price' => floatval($item['invoice_d_item_price']),
                    'base_price' => floatval($item['invoice_d_item_price']),
                    'price_tiers' => $itemTiers,
                    'qty' => floatval($item['invoice_d_qty']),
                    'discount' => floatval($item['invoice_d_discount_value'] ?? 0),
                    'total' => floatval($item['invoice_d_item_total']),
                    'gst_code' => $item['gst_code'] ?? '',
                    'item_group' => $item['item_group'] ?? '',
                    'is_gift_item' => $item['is_gift_item'] ?? 0,
                    'is_cart_item' => $item['is_cart_item'] ?? 0,
                    'so_qty' => isset($soQtyMap[$itemId]) ? $soQtyMap[$itemId] : null,
                    'stock' => 9999 // Will be updated on client side
                ];
            }
            
            $subtotal = array_sum(array_column($items, 'total'));
            $discountValue = 0;
            if ($existingOrder['invoice_h_coupon_type'] == 'PCT') {
                $discountValue = floatval($existingOrder['invoice_h_coupon_rate'] ?? 0);
            } elseif ($existingOrder['invoice_h_coupon_type'] == 'SUM' && $subtotal > 0) {
                $discountValue = (floatval($existingOrder['invoice_h_coupon_value'] ?? 0) / $subtotal) * 100;
            }
            
            echo json_encode([
                'status' => 'success',
                'order' => [
                    'invoice_id' => $existingOrder['invoice_h_id'],
                    'invoice_code' => $existingOrder['invoice_h_code'],
                    'customer_id' => $existingOrder['invoice_h_customer_id'],
                    'shipping_address_id' => $existingOrder['shipping_address_id'],
                    'delivery_address' => $existingOrder['invoice_h_delivery_address'] ?? '',
                    'delivery_charge' => floatval($existingOrder['invoice_h_delivery_cost'] ?? 0),
                    'discount' => $discountValue,
                    'payment_method' => $existingOrder['invoice_h_pay_type'],
                    'order_note' => $existingOrder['invoice_h_order_note'],
                    'purchase_order_no' => $existingOrder['invoice_h_purchase_order_no'] ?? '',
                    'items' => $items
                ]
            ]);
            exit;
            } catch (Exception $e) {
                error_log("Error in load_existing_order: " . $e->getMessage());
                echo json_encode(['status' => 'error', 'message' => 'Error loading order: ' . $e->getMessage()]);
                exit;
            }
        } elseif ($_POST['action'] === 'get_products') {
            $categoryId = $_POST['category_id'] ?? '0';
            $location = $_POST['location'] ?? $_SESSION['location'];
            $customerId = $_POST['customer_id'] ?? null;
            $searchTerm = trim($_POST['search'] ?? '');
            $limit = isset($_POST['limit']) ? max(1, min(50, (int) $_POST['limit'])) : 0;
            $hasAllowInSalesColumn = (bool) $db->getRow("SHOW COLUMNS FROM item_master LIKE 'allow_in_sales'");
            
            // Only return products that are active and allowed in sales.
            $whereClause = 'item_active = "Y"';
            $params = [];

            if ($hasAllowInSalesColumn) {
                $whereClause .= ' AND (allow_in_sales = 1 OR allow_in_sales IS NULL)';
            }
            
            if ($categoryId && $categoryId != '0') {
                $whereClause .= ' AND item_category = ?';
                $params[] = $categoryId;
            }
            
            if ($searchTerm) {
                $whereClause .= ' AND (item_name LIKE ? OR item_code LIKE ?)';
                $params[] = '%' . $searchTerm . '%';
                $params[] = '%' . $searchTerm . '%';
            }

            $productsQuery =
                "SELECT im.*, tm.type_name, cm.category_name 
                 FROM item_master im 
                 LEFT JOIN type_master tm ON im.item_type = tm.type_id 
                 LEFT JOIN category_master cm ON im.item_category = cm.category_id 
                 WHERE $whereClause ORDER BY item_name ASC";

            if ($limit > 0) {
                $productsQuery .= ' LIMIT ' . (int) $limit;
            }
            
            $products = $db->getRows($productsQuery, $params);

            $hasCustomerPriceTable = (bool) $db->getRow("SHOW TABLES LIKE 'product_customer_price_mapping'");
            $hasPriceTiersTable = (bool) $db->getRow("SHOW TABLES LIKE 'item_price_tiers'");
            
            $result = [];
            foreach ($products as $product) {
                $stock = $db->getRow(
                    'SELECT SUM(ft_blanace) as qty FROM fifo WHERE ft_item = ? AND ft_location = ?',
                    [$product['item_id'], $location]
                );
                
                $price = $product['item_normal_selling_price'];
                
                // Customer-specific pricing (only if mapping table exists)
                if ($customerId && $hasCustomerPriceTable) {
                    $customerPrice = $db->getRow(
                        'SELECT price FROM product_customer_price_mapping WHERE item_id = ? AND customer_id = ?',
                        [$product['item_id'], $customerId]
                    );
                    if ($customerPrice && $customerPrice['price']) {
                        $price = $customerPrice['price'];
                    }
                }
                
                $type_name = trim((string) ($product['type_name'] ?? ''));

                // Load qty price tiers
                $priceTiers = [];
                try {
                    if ($hasPriceTiersTable) {
                        $tierRows = $db->getRows('SELECT min_qty, unit_price FROM item_price_tiers WHERE item_id = ? ORDER BY min_qty ASC', [$product['item_id']]);
                        foreach ($tierRows as $tr) {
                            $priceTiers[] = ['min_qty' => floatval($tr['min_qty']), 'unit_price' => floatval($tr['unit_price'])];
                        }
                    }
                } catch (Exception $e) { }

                $result[] = [
                    'item_id' => $product['item_id'],
                    'item_code' => $product['item_code'],
                    'item_name' => $product['item_name'],
                    'price' => floatval($price),
                    'price_tiers' => $priceTiers,
                    'stock_qty' => floatval($stock['qty'] ?? 0),
                    'category_id' => $product['item_category'],
                    'item_image' => $product['item_image'] ?? '',
                    'imageParth' => $product['imageParth'] ?? '',
                    'gst_code' => $product['gst_vat_code'] ?? '',
                    'item_group' => $type_name
                ];
            }

            $jsonFlags = 0;
            if (defined('JSON_UNESCAPED_UNICODE')) {
                $jsonFlags |= JSON_UNESCAPED_UNICODE;
            }
            if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
                $jsonFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
            }

            $jsonResult = json_encode($result, $jsonFlags);
            if ($jsonResult === false) {
                error_log('cart-order.php get_products json_encode failed: ' . json_last_error_msg());
                echo '[]';
            } else {
                echo $jsonResult;
            }
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

// Currency config
try {
    $currencyRow = $db->getRow('SELECT * FROM currency WHERE activated = ? LIMIT 1', ["Y"]);
    $CURRENCY_SYMBOL = isset($currencyRow['currency']) ? $currencyRow['currency'] : '$';
} catch(Exception $e) {
    $CURRENCY_SYMBOL = '$';
}

function getCustomers() {
    $db = new Database();
    return getOrderEligibleCustomers($db);
}

function getCategories() {
    $db = new Database();
    return $db->getRows('SELECT category_id, category_name FROM category_master ORDER BY category_name ASC');
}

function getGstRateMap() {
    $db = new Database();
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

function getCustomerShippingAddresses($customerId) {
    $db = new Database();
    if (getCustomerOrderEligibilityError($db, $customerId) !== null) {
        return [];
    }
    return $db->getRows('SELECT id, address_label, address_line_1, address_line_2, city, state, country, postal_code, 
                         contact_no, contact_person_name, contact_person_phone, contact_person_email, remarks, 
                         delivery_time_from, delivery_time_till, has_door_key, has_shop_alarm, delivery_route_id,
                         is_default, attribute_1, attribute_2, attribute_3 
                         FROM customer_shipping_address 
                         WHERE customer_id = ? ORDER BY is_default DESC, id ASC', [$customerId]);
}

function getDeliveryRoutes() {
    $db = new Database();
    return $db->getRows('SELECT id, route_name FROM delivery_route_master WHERE is_active = 1 ORDER BY route_name ASC');
}

function getPaymentMethods() {
    $db = new Database();
    return $db->getRows('SELECT * FROM payment_method WHERE website_status = ?', ['Y']);
}

function getCustomerLineDiscountMap() {
    $db = new Database();
    $result = [];

    try {
        $hasLineDiscountPercentageColumn = (bool) $db->getRow("SHOW COLUMNS FROM customer LIKE 'line_discount_percentage'");
        $hasLineDiscountColumn = (bool) $db->getRow("SHOW COLUMNS FROM customer LIKE 'line_discount_id'");
        $hasDiscountTable = (bool) $db->getRow("SHOW TABLES LIKE 'discount_code'");

        if (!$hasLineDiscountPercentageColumn && (!$hasLineDiscountColumn || !$hasDiscountTable)) {
            return $result;
        }

        if ($hasLineDiscountColumn && $hasDiscountTable) {
            $lineDiscountPercentageExpr = $hasLineDiscountPercentageColumn
                ? "COALESCE(NULLIF(c.line_discount_percentage, ''), 0)"
                : "0";

            $rows = $db->getRows(
                "SELECT c.customer_id,
                        {$lineDiscountPercentageExpr} AS customer_line_discount_percentage,
                        COALESCE(dc.percentage, 0) AS line_discount_percentage
                 FROM customer c
                 LEFT JOIN discount_code dc ON dc.id = c.line_discount_id"
            );
        } else {
            $lineDiscountPercentageExpr = $hasLineDiscountPercentageColumn
                ? "COALESCE(NULLIF(c.line_discount_percentage, ''), 0)"
                : "0";

            $rows = $db->getRows(
                "SELECT c.customer_id,
                        {$lineDiscountPercentageExpr} AS customer_line_discount_percentage,
                        0 AS line_discount_percentage
                 FROM customer c"
            );
        }

        foreach ($rows as $row) {
            $customerId = (int) ($row['customer_id'] ?? 0);
            if ($customerId <= 0) {
                continue;
            }
            $customerLineDiscount = (float) ($row['customer_line_discount_percentage'] ?? 0);
            $lineDiscount = (float) ($row['line_discount_percentage'] ?? 0);
            $result[$customerId] = ($customerLineDiscount > 0) ? $customerLineDiscount : $lineDiscount;
        }
    } catch (Exception $e) {
        // ignore and keep fallback empty map
    }

    return $result;
}

function getCustomerOrderDiscountMap() {
    $db = new Database();
    $result = [];

    try {
        $hasCustomerDiscountColumn = (bool) $db->getRow("SHOW COLUMNS FROM customer LIKE 'customer_discount'");
        if (!$hasCustomerDiscountColumn) {
            return $result;
        }

        $rows = $db->getRows(
            "SELECT customer_id, COALESCE(NULLIF(customer_discount, ''), 0) AS customer_discount
             FROM customer"
        );

        foreach ($rows as $row) {
            $customerId = (int) ($row['customer_id'] ?? 0);
            if ($customerId <= 0) {
                continue;
            }
            $result[$customerId] = (float) ($row['customer_discount'] ?? 0);
        }
    } catch (Exception $e) {
        // ignore and keep fallback empty map
    }

    return $result;
}

function generateOrderRef() {
    $db = new Database();
    $getpid = $db->getRow('SELECT max(invoice_h_id) as invoice_h_id FROM invoice_hedder');
    $randomNo = rand(1000000, 9999999);
    $oldpid = $getpid['invoice_h_id'] ?? 0;
    $newpid = $oldpid + 1;
    return "CART" . $randomNo . $newpid;
}

$customers = getCustomers();
$categories = getCategories();
$gstRateMap = getGstRateMap();
$customerLineDiscountMap = getCustomerLineDiscountMap();
$customerOrderDiscountMap = getCustomerOrderDiscountMap();
$deliveryRoutes = getDeliveryRoutes();
$payment_methods = getPaymentMethods();
$order_ref = generateOrderRef();
$today = date('Y-m-d');
$canEditOrderDiscount = function_exists('isSuperAdmin') ? isSuperAdmin() : ((int)($_SESSION['userlevel'] ?? 0) === 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Cart Order | STOCK MANAGEMENT SYSTEM</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
    <link href="assets/global/plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/global/plugins/select2/css/select2-bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/airbnb.css">
    
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #a5b4fc;
            --primary-bg: #eef2ff;
            --secondary: #8b5cf6;
            --accent: #06b6d4;
            --success: #10b981;
            --success-dark: #059669;
            --success-bg: #d1fae5;
            --danger: #ef4444;
            --danger-bg: #fee2e2;
            --warning: #f59e0b;
            --warning-bg: #fef3c7;
            --info: #3b82f6;
            --info-bg: #dbeafe;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            --shadow-2xl: 0 25px 50px -12px rgb(0 0 0 / 0.25);
            --radius: 8px;
            --radius-lg: 12px;
            --radius-xl: 16px;
        }

        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4c1d95 100%);
            min-height: 100vh;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(139, 92, 246, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(6, 182, 212, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(99, 102, 241, 0.2) 0%, transparent 60%);
            pointer-events: none;
            z-index: 0;
        }

        .page-content {
            padding-top: 0 !important;
            padding-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .main-container {
            max-width: 1700px;
            margin: 0 auto;
            padding: 0 12px;
        }

        /* Standard Card */
        .glass-card {
            background: #fff;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            overflow: hidden;
            margin-bottom: 16px;
        }

        /* Premium Header 
        .page-header {
              padding: 0 3px 1px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.08) 0%, rgba(139, 92, 246, 0.05) 100%);
            border-bottom: 1px solid rgba(99, 102, 241, 0.1);
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.1) 0%, transparent 70%);
            pointer-events: none;
        }*/

        .header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
            position: relative;
            z-index: 1;
        }

        .page-title h1 {
            font-size: 24px;
            font-weight: 800;
            color: var(--gray-900);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
            letter-spacing: -0.5px;
        }

        .page-title h1 .icon-wrap {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px -2px rgba(99, 102, 241, 0.5);
        }

        .page-title h1 .icon-wrap i {
            font-size: 20px;
            color: white;
        }

        .header-badges {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .badge-ref {
            background: linear-gradient(135deg, var(--gray-800) 0%, var(--gray-900) 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }

        .badge-ref i {
            color: var(--warning);
        }

        .badge-cart {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
            animation: pulse-badge 2s ease-in-out infinite;
        }

        @keyframes pulse-badge {
            0%, 100% { box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4); }
            50% { box-shadow: 0 4px 20px rgba(99, 102, 241, 0.6); }
        }

        /* Section Styles */
        .section-header {
            padding: 12px 24px;
            background: linear-gradient(135deg, var(--gray-50) 0%, white 100%);
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-header .icon-box {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--primary-bg) 0%, rgba(139, 92, 246, 0.1) 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .section-header .icon-box i {
            font-size: 16px;
            color: var(--primary);
        }

        .section-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            color: var(--gray-800);
            letter-spacing: -0.3px;
        }

        .section-body {
            padding: 20px;
        }

        /* Form Styles */
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            margin-bottom: 16px;
        }
        
        .form-row-2col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }
        
        .form-row-3col {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }
        
        @media (max-width: 768px) {
            .form-row-2col, .form-row-3col {
                grid-template-columns: 1fr;
            }
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 700;
            color: var(--gray-700);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }


        .form-label i {
            color: var(--primary);
            font-size: 14px;
        }

        .form-label.required::after {
            content: '*';
            color: var(--danger);
            margin-left: 2px;
        }

        .form-input, .form-select {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius);
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
        }

        .form-input:hover, .form-select:hover {
            border-color: var(--gray-300);
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
        }

        /* Input with inline button */
        .input-with-button {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .input-with-button .form-select {
            flex: 1;
        }

        .btn-edit-inline {
            padding: 10px 16px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
            flex-shrink: 0;
        }

        .btn-edit-inline:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }

        .btn-edit-inline:disabled {
            background: var(--gray-300);
            cursor: not-allowed;
            opacity: 0.6;
        }

        /* Flatpickr Order Date Highlighting */
        .flatpickr-day.has-order {
            background: var(--danger) !important;
            color: #fff !important;
            border-color: var(--danger) !important;
            font-weight: 700;
            position: relative;
        }

        .flatpickr-day.has-order:hover {
            background: #dc2626 !important;
            border-color: #dc2626 !important;
        }

        .flatpickr-day.has-order.selected {
            background: #b91c1c !important;
            border-color: #b91c1c !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.3);
        }

        .flatpickr-day.has-order::after {
            content: '';
            position: absolute;
            bottom: 2px;
            left: 50%;
            transform: translateX(-50%);
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: #fff;
        }

        .flatpickr-calendar {
            box-shadow: 0 8px 30px rgba(0,0,0,0.15) !important;
            border-radius: 12px !important;
        }

        .order-date-legend {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 6px;
            font-size: 11px;
            color: var(--gray-500);
        }

        .order-date-legend .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--danger);
            display: inline-block;
        }

        /* Shipping Address Card */
        .address-card {
            background: linear-gradient(135deg, var(--gray-50) 0%, white 100%);
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-lg);
            padding: 16px;
            margin-top: 16px;
            position: relative;
            overflow: hidden;
        }

        .address-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary), var(--accent));
        }

        .address-card.hidden {
            display: none;
        }

        .address-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--gray-200);
        }

        .address-label {
            font-weight: 800;
            color: var(--gray-900);
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .address-label i {
            color: var(--primary);
        }

        .address-badge {
            background: linear-gradient(135deg, var(--success) 0%, #34d399 100%);
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .address-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }

        .address-detail {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 10px 12px;
            background: white;
            border-radius: 8px;
            border: 1px solid var(--gray-100);
        }

        .address-detail .detail-icon {
            width: 32px;
            height: 32px;
            background: var(--primary-bg);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .address-detail .detail-icon i {
            color: var(--primary);
            font-size: 13px;
        }

        .address-detail .detail-text {
            font-size: 13px;
            color: var(--gray-700);
            font-weight: 500;
            line-height: 1.4;
        }

        /* Cart Layout - Full Width */
        .cart-layout {
            display: block;
            width: 100%;
        }

        /* Order Info Bar - Top Section with Address, Notes, Summary */
        .order-info-bar {
            display: grid;
            grid-template-columns: 1fr 1fr 320px;
            gap: 24px;
            padding: 20px;
        }

        @media (max-width: 1200px) {
            .order-info-bar {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 768px) {
            .order-info-bar {
                grid-template-columns: 1fr;
            }
        }

        .order-info-left {
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding: 4px 0;
        }

        .order-info-left .info-label {
            font-size: 12px;
            font-weight: 700;
            color: var(--danger);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .order-info-left .info-value {
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-800);
        }

        .order-info-left .info-row {
            display: flex;
            align-items: baseline;
            gap: 8px;
        }

        .order-info-left .info-row label {
            font-size: 13px;
            font-weight: 700;
            color: var(--gray-700);
            min-width: 110px;
        }

        .order-notes-section textarea {
            width: 100%;
            min-height: 120px;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 12px;
            font-size: 13px;
            color: var(--gray-600);
            resize: vertical;
            transition: border-color 0.2s;
        }

        .order-notes-section textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .order-notes-section textarea::placeholder {
            color: var(--gray-400);
        }

        .order-notes-section .char-count {
            text-align: right;
            font-size: 11px;
            color: var(--gray-400);
            margin-top: 4px;
        }

        /* Summary Panel - Compact Top Right */
        .summary-compact {
            background: white;
            border-radius: var(--radius);
            border: 1px solid var(--gray-200);
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        /* Cart Items */
        .cart-items-section {
            min-height: 400px;
        }

        .cart-empty {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray-400);
        }

        .cart-empty-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--gray-100) 0%, var(--gray-200) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
            color: var(--gray-300);
        }

        .cart-empty h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--gray-600);
            margin-bottom: 6px;
        }

        .cart-empty p {
            font-size: 14px;
            color: var(--gray-400);
        }

        /* Cart Table Layout */
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .cart-table thead th {
            background: var(--gray-50, #f9fafb);
            color: var(--gray-500);
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 10px 12px;
            text-align: left;
            border-bottom: 2px solid var(--gray-200);
            white-space: nowrap;
        }

        .cart-table tbody tr {
            border-bottom: 1px solid var(--gray-100);
            transition: background 0.15s;
        }

        .cart-table tbody tr:hover {
            background: rgba(99, 102, 241, 0.03);
        }

        .cart-table tbody tr.new-item {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.04) 0%, white 100%);
        }

        .cart-table tbody tr.cart-item-flagged {
            border-left: 3px solid var(--info);
        }

        .cart-table tbody tr.bc-selected > td {
            background: #dbeafe !important;
        }

        .cart-table tbody td {
            padding: 8px 12px;
            vertical-align: middle;
            color: var(--gray-700);
        }

        .cart-table .col-actions {
            width: 60px;
            white-space: nowrap;
        }

        .cart-table .col-group {
            min-width: 140px;
            font-size: 12px;
            color: var(--gray-600);
        }

        .cart-table .col-item {
            min-width: 180px;
        }

        .cart-table .col-item a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
        }

        .cart-table .col-item a:hover {
            text-decoration: underline;
        }

        .cart-table .col-code {
            font-size: 12px;
            color: var(--gray-500);
            font-weight: 500;
        }

        .cart-table .col-price {
            text-align: right;
            font-weight: 600;
            white-space: nowrap;
        }

        .cart-table .col-price input {
            width: 80px;
            text-align: right;
            border: 1px solid var(--gray-300);
            padding: 4px 6px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 600;
        }

        .cart-table .col-price input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.1);
        }

        .cart-table .col-discount {
            text-align: right;
            white-space: nowrap;
        }

        .cart-table .col-discount input {
            width: 60px;
            text-align: right;
            border: 1px solid var(--gray-300);
            padding: 4px 6px;
            border-radius: 4px;
            font-size: 13px;
        }

        .cart-table .col-discount input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.1);
        }

        .cart-table .col-gst,
        .cart-table .col-so {
            text-align: center;
            font-size: 13px;
        }

        .cart-table .col-ordered {
            text-align: center;
        }

        .cart-table .col-ordered input {
            width: 60px;
            text-align: center;
            border: 1px solid var(--gray-300);
            border-radius: 4px;
            padding: 4px 6px;
            font-size: 13px;
            font-weight: 700;
            -moz-appearance: textfield;
        }

        .cart-table .col-ordered input::-webkit-outer-spin-button,
        .cart-table .col-ordered input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .cart-table .col-ordered input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.1);
        }

        .cart-table .col-total {
            text-align: right;
            font-weight: 700;
            font-size: 14px;
            color: var(--gray-800);
            white-space: nowrap;
        }

        .cart-table .btn-action {
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px 6px;
            border-radius: 4px;
            color: var(--gray-400);
            font-size: 14px;
            transition: all 0.15s;
        }

        .cart-table .btn-action:hover {
            color: var(--danger);
            background: var(--danger-bg);
        }

        .cart-table .btn-action.btn-edit:hover {
            color: var(--primary);
            background: rgba(99, 102, 241, 0.1);
        }

        .cart-table .btn-action.btn-gift:hover,
        .cart-table .btn-action.btn-gift.active {
            color: #b45309;
            background: #fef3c7;
        }

        /* Cart Item Badges */
        .item-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-left: 6px;
            vertical-align: middle;
        }
        
        .cart-badge {
            background: var(--info-bg);
            color: var(--info);
        }
        
        .original-badge {
            background: var(--warning-bg);
            color: var(--warning);
        }
        
        .new-badge {
            background: var(--success-bg);
            color: var(--success);
        }

        .gift-badge {
            background: #fef3c7;
            color: #b45309;
        }

        /* Summary Panel - Compact inline */
        .summary-compact .summary-input-row {
            display: flex;
            gap: 12px;
            margin-bottom: 10px;
        }

        .summary-input-group {
            flex: 1;
        }

        .summary-input-group label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: var(--gray-500);
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .summary-input-group input {
            width: 100%;
            padding: 8px 10px;
            border: 2px solid var(--gray-200);
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s;
        }

        .summary-input-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px 0;
            font-size: 13px;
            color: var(--gray-600);
            border-bottom: 1px solid var(--gray-100);
        }

        .summary-row:last-of-type {
            border-bottom: none;
        }

        .summary-row span:first-child {
            font-weight: 500;
        }

        .summary-row span:last-child {
            font-weight: 700;
            color: var(--gray-800);
        }

        .summary-row.total {
            margin-top: 4px;
            padding: 8px 0;
            font-size: 15px;
            font-weight: 800;
            color: var(--gray-900);
            border-top: 2px solid var(--gray-300);
            border-bottom: 1px solid var(--gray-100);
        }

        .summary-row.total span:last-child {
            color: var(--gray-900);
            font-size: 18px;
        }

        .summary-row.terms {
            border-bottom: none;
            font-size: 12px;
        }

        .summary-row.terms span:first-child {
            font-weight: 600;
            color: var(--gray-500);
        }

        .summary-row.terms span:last-child {
            font-weight: 700;
            color: var(--gray-700);
        }

        .summary-row.delivery-check {
            font-size: 12px;
        }

        .summary-row.delivery-check label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
            color: var(--gray-700);
            cursor: pointer;
            font-size: 12px;
        }

        .summary-row.delivery-check input[type=checkbox] {
            accent-color: var(--primary);
            width: 16px;
            height: 16px;
        }

        /* Cart Actions Row */
        .cart-actions-row {
            display: flex;
            gap: 12px;
            margin-top: 16px;
            align-items: center;
        }

        .cart-actions-row .btn-checkout {
            margin-top: 0;
            flex: 1;
        }

        .cart-actions-row .btn-clear {
            margin-top: 0;
            width: auto;
            padding: 14px 20px;
        }

        /* Buttons */
        .btn-add-product {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: var(--radius);
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
        }

        .btn-add-product:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.5);
        }

        .btn-checkout {
            flex: 1;
            padding: 14px;
            background: linear-gradient(135deg, var(--success) 0%, #34d399 100%);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 0;
        }

        .btn-checkout:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.5);
        }

        .btn-checkout:disabled {
            background: var(--gray-300);
            cursor: not-allowed;
            box-shadow: none;
        }

        .btn-clear {
            width: auto;
            padding: 14px 20px;
            background: transparent;
            color: var(--gray-500);
            border: 2px solid var(--gray-200);
            border-radius: var(--radius);
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 0;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-clear:hover {
            border-color: var(--danger);
            color: var(--danger);
            background: var(--danger-bg);
        }

        /* Product Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: 10000;
            justify-content: center;
            align-items: center;
            padding: 24px;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-box {
            background: white;
            border-radius: var(--radius-xl);
            width: 100%;
            max-width: 1100px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            animation: modalIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow-2xl);
        }

        @keyframes modalIn {
            from { opacity: 0; transform: scale(0.95) translateY(-20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .modal-header {
            padding: 24px 28px;
            background: linear-gradient(135deg, var(--gray-50) 0%, white 100%);
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
            color: var(--gray-900);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .modal-header h3 .icon-wrap {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-header h3 .icon-wrap i {
            color: white;
            font-size: 18px;
        }

        .modal-close {
            background: var(--gray-100);
            border: none;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            cursor: pointer;
            font-size: 20px;
            color: var(--gray-500);
            transition: all 0.2s;
        }

        .modal-close:hover {
            background: var(--danger-bg);
            color: var(--danger);
            transform: rotate(90deg);
        }

        .modal-search {
            padding: 20px 28px;
            background: white;
            border-bottom: 1px solid var(--gray-100);
            display: flex;
            gap: 16px;
        }

        .search-input-wrap {
            flex: 1;
            position: relative;
        }

        .search-input-wrap i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
        }

        .modal-search input {
            width: 100%;
            padding: 14px 18px 14px 46px;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius);
            font-size: 15px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .modal-search input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .modal-search select {
            padding: 14px 18px;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius);
            font-size: 15px;
            font-weight: 600;
            min-width: 200px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .modal-search select:focus {
            outline: none;
            border-color: var(--primary);
        }

        .modal-body {
            flex: 1;
            overflow: hidden;
            display: flex;
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%);
            min-height: 0; /* Important for flex children to respect overflow */
        }

        /* Product List Layout (Left Side) */
        .products-list-container {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            border-right: 1px solid var(--gray-200);
            min-height: 0;
        }

        .products-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .product-list-item {
            background: white;
            border-radius: var(--radius);
            padding: 12px 16px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .product-list-item:hover {
            border-color: var(--primary-light);
            background: var(--primary-bg);
        }

        .product-list-item.selected {
            border-color: var(--primary);
            background: var(--primary-bg);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
        }

        .product-list-item.out-of-stock {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .product-list-item.out-of-stock:hover {
            border-color: transparent;
            background: white;
        }

        .product-list-img {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-bg) 0%, rgba(139, 92, 246, 0.1) 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
        }

        .product-list-img i {
            font-size: 20px;
            color: var(--primary-light);
        }

        .product-list-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-list-info {
            flex: 1;
            min-width: 0;
        }

        .product-list-code {
            font-size: 10px;
            color: var(--gray-400);
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .product-list-name {
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-800);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .product-list-price {
            font-weight: 800;
            font-size: 15px;
            color: var(--success);
            white-space: nowrap;
        }

        .product-list-stock {
            font-size: 10px;
            padding: 4px 8px;
            border-radius: 20px;
            font-weight: 700;
            white-space: nowrap;
        }

        .product-list-stock.in { background: var(--success-bg); color: var(--success); }
        .product-list-stock.low { background: var(--warning-bg); color: var(--warning); }
        .product-list-stock.out { background: var(--danger-bg); color: var(--danger); }

        /* Quantity Panel (Right Side) */
        .qty-panel {
            width: 280px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            background: white;
            min-height: 0;
            overflow: hidden;
        }

        .qty-panel-empty {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--gray-400);
            text-align: center;
        }

        .qty-panel-empty i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .qty-panel-empty p {
            font-size: 14px;
            margin: 0;
        }

        .qty-panel-content {
            display: none;
            flex-direction: column;
            flex: 1;
            min-height: 0;
            overflow-y: auto;
        }

        .qty-panel-content.active {
            display: flex;
        }

        .qty-panel-product {
            text-align: center;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--gray-100);
            margin-bottom: 12px;
            flex-shrink: 0;
        }

        .qty-panel-img {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-bg) 0%, rgba(139, 92, 246, 0.1) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            overflow: hidden;
        }

        .qty-panel-img i {
            font-size: 32px;
            color: var(--primary-light);
        }

        .qty-panel-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .qty-panel-code {
            font-size: 11px;
            color: var(--gray-400);
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .qty-panel-name {
            font-size: 16px;
            font-weight: 700;
            color: var(--gray-800);
            margin: 6px 0;
        }

        .qty-panel-price {
            font-size: 20px;
            font-weight: 800;
            color: var(--success);
        }

        .qty-panel-stock {
            font-size: 11px;
            color: var(--gray-500);
            margin-top: 4px;
        }

        .qty-panel-input-group {
            margin-bottom: 12px;
            flex-shrink: 0;
        }

        .qty-panel-input-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--gray-600);
            margin-bottom: 6px;
        }

        .qty-panel-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .qty-panel-controls button {
            width: 40px;
            height: 40px;
            border: 2px solid var(--gray-200);
            background: white;
            border-radius: var(--radius);
            font-size: 18px;
            cursor: pointer;
            transition: all 0.2s;
            color: var(--gray-600);
            flex-shrink: 0;
        }

        .qty-panel-controls button:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: var(--primary-bg);
        }

        .qty-panel-controls input {
            flex: 1;
            height: 40px;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius);
            text-align: center;
            font-size: 18px;
            font-weight: 700;
            color: var(--gray-800);
        }

        .qty-panel-controls input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .qty-panel-total {
            background: var(--gray-50);
            border-radius: var(--radius);
            padding: 12px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }

        .qty-panel-total span {
            font-size: 13px;
            color: var(--gray-600);
        }

        .qty-panel-total strong {
            font-size: 20px;
            font-weight: 800;
            color: var(--gray-900);
        }

        .btn-add-to-cart {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            flex-shrink: 0;
        }

        .btn-add-to-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
        }

        .btn-add-to-cart:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        @media (max-width: 768px) {
            .modal-body {
                flex-direction: column;
            }
            .products-list-container {
                border-right: none;
                border-bottom: 1px solid var(--gray-200);
                max-height: 50%;
            }
            .qty-panel {
                width: 100%;
            }
        }

        /* Legacy grid classes (hidden) */
        .products-grid {
            display: none;
        }

        .product-card {
            display: none;
        }

        /* Toast */
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--gray-900);
            color: white;
            padding: 18px 28px;
            border-radius: var(--radius);
            box-shadow: var(--shadow-2xl);
            display: flex;
            align-items: center;
            gap: 14px;
            z-index: 20000;
            transform: translateY(120px);
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 600;
            font-size: 15px;
        }

        .toast.show { transform: translateY(0); opacity: 1; }
        .toast.success { background: linear-gradient(135deg, var(--success) 0%, #34d399 100%); }
        .toast.error { background: linear-gradient(135deg, var(--danger) 0%, #f87171 100%); }

        /* Loading */
        .loading { 
            text-align: center; 
            padding: 60px; 
            color: var(--gray-400); 
        }
        
        .spinner { 
            width: 48px; 
            height: 48px; 
            border: 4px solid var(--gray-200); 
            border-top-color: var(--primary); 
            border-radius: 50%; 
            animation: spin 0.8s linear infinite; 
            margin: 0 auto 20px; 
        }
        
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Select2 Custom */
        .select2-container--bootstrap .select2-selection--single {
            height: 52px !important;
            padding: 12px 18px !important;
            border: 2px solid var(--gray-200) !important;
            border-radius: var(--radius) !important;
            transition: all 0.2s;
        }
        
        .select2-container--bootstrap .select2-selection--single:hover {
            border-color: var(--gray-300) !important;
        }
        
        .select2-container--bootstrap.select2-container--focus .select2-selection--single {
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1) !important;
        }
        
        .select2-container--bootstrap .select2-selection--single .select2-selection__rendered {
            line-height: 26px !important;
            padding: 0 !important;
            font-weight: 500;
        }
        
        .select2-container--bootstrap .select2-selection--single .select2-selection__arrow {
            height: 50px !important;
        }
        
        .select2-dropdown {
            border: 2px solid var(--gray-200) !important;
            border-radius: var(--radius) !important;
            box-shadow: var(--shadow-xl) !important;
            margin-top: 4px;
        }
        
        .select2-results__option--highlighted {
            background: var(--primary) !important;
        }

        /* Disabled state */
        .disabled-section {
            opacity: 0.5;
            pointer-events: none;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-title h1 {
                font-size: 24px;
            }
            .page-title h1 .icon-wrap {
                width: 44px;
                height: 44px;
            }
            .cart-table {
                font-size: 12px;
            }
            .cart-table thead th {
                padding: 8px 6px;
                font-size: 10px;
            }
            .cart-table tbody td {
                padding: 6px;
            }
            .cart-table .col-group,
            .cart-table .col-code {
                display: none;
            }
            .modal-search { flex-direction: column; }
            .products-grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); }
            .header-badges { flex-wrap: wrap; }
        }

        /* ── Business Central-style toolbar ─────────────────── */
        .bc-toolbar {
            display: flex;
            align-items: center;
            gap: 2px;
            padding: 6px 10px;
            background: #f3f4f6;
            border-bottom: 1px solid #e5e7eb;
            border-radius: 8px 8px 0 0;
            flex-wrap: wrap;
        }
        .bc-toolbar-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 500;
            background: transparent;
            border: 1px solid transparent;
            border-radius: 4px;
            cursor: pointer;
            color: #374151;
            white-space: nowrap;
            transition: background 0.15s, border-color 0.15s;
        }
        .bc-toolbar-btn:hover:not(:disabled) {
            background: #fff;
            border-color: #d1d5db;
        }
        .bc-toolbar-btn:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }
        .bc-toolbar-btn i { font-size: 12px; }
        .bc-toolbar-sep {
            width: 1px;
            height: 20px;
            background: #d1d5db;
            margin: 0 4px;
        }
        .bc-toolbar-search-wrap {
            position: relative;
            margin-left: auto;
        }
        .bc-toolbar-search-wrap input {
            padding: 4px 10px 4px 28px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 12px;
            width: 240px;
            background: #fff;
            outline: none;
        }
        .bc-toolbar-search-wrap input:focus { border-color: #6366f1; }
        .bc-toolbar-search-wrap i {
            position: absolute;
            left: 8px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 12px;
        }

        /* ── Inline-row (BC new-line row) ───────────────────── */
        .inline-add-row td {
            background: #f9fafb;
            border-top: 2px dashed #e5e7eb;
        }
        .inline-add-row:hover td { background: #f3f4f6; }
        .inline-search-cell { position: relative; min-width: 180px; }
        .inline-no-input {
            width: 100%;
            padding: 4px 8px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 12px;
            background: #fff;
            outline: none;
            transition: border-color 0.15s;
        }
        .inline-no-input:focus { border-color: #6366f1; box-shadow: 0 0 0 2px rgba(99,102,241,.15); }
        .inline-no-input::placeholder { color: #9ca3af; }

        /* ── Inline product dropdown (fixed-position overlay) ────── */
        .inline-product-dropdown {
            display: none;
            position: fixed;
            min-width: 540px;
            background: #fff;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            box-shadow: 0 8px 28px rgba(0,0,0,.18);
            z-index: 999999;
            overflow: hidden;
        }
        .inline-product-dropdown.show { display: block; }
        .inline-pd-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        .inline-pd-table thead th {
            padding: 5px 10px;
            background: #f3f4f6;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
            font-weight: 600;
            color: #374151;
            white-space: nowrap;
        }
        .inline-pd-table thead th.sort-col { cursor: default; }
        .inline-pd-table thead th .sort-icon { color: #6b7280; font-size: 10px; margin-left: 3px; }
        .inline-pd-row {
            cursor: pointer;
            transition: background 0.1s;
        }
        .inline-pd-row:hover { background: #eff6ff; }
        .inline-pd-row.focused { background: #dbeafe; }
        .inline-pd-row td {
            padding: 5px 10px;
            border-bottom: 1px solid #f3f4f6;
            color: #374151;
        }
        .inline-pd-row td:first-child { color: #4f46e5; font-weight: 600; }
        .inline-pd-empty {
            padding: 12px 10px;
            text-align: center;
            color: #9ca3af;
            font-size: 12px;
        }
        .inline-pd-footer {
            padding: 4px 10px;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            font-size: 11px;
            color: #6b7280;
            text-align: right;
        }
        .inline-desc-cell { color: #374151; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .inline-qty-input {
            width: 70px;
            padding: 3px 6px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 12px;
            text-align: center;
        }
        .inline-qty-input:focus { border-color: #6366f1; outline: none; }
        .inline-commit-btn {
            padding: 3px 8px;
            background: #4f46e5;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            display: none;
        }
        .inline-commit-btn:hover { background: #4338ca; }
        .inline-commit-btn.visible { display: inline-flex; align-items: center; gap: 4px; }
    </style>
</head>

<body class="page-sidebar-closed-hide-logo page-content-white">
    <?php include('common/manubar.php'); ?>
    
    <div class="page-content-wrapper">
        <div class="page-content">
            <div class="main-container">
                
                <!-- Header Card -->
                <div class="glass-card">
                    <div class="page-header" style="    margin: 3px 0 20px;">
                        <div class="header-row">
                            <div class="page-title">
                                <h1>
                                    <span class="icon-wrap"><i class="fa fa-shopping-cart"></i></span>
                                    Cart Order
                                </h1>
                            </div>
                            <div class="header-badges">
                                <span class="badge-ref"><i class="fa fa-tag"></i> <?php echo $order_ref; ?></span>
                                <span class="badge-cart" id="cartCountBadge"><i class="fa fa-shopping-basket"></i> 0 Items</span>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="redirectToStandingOrder" disabled>
                                    <i class="fa fa-calendar"></i> Standing Order
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customer & Delivery Section -->
                <div class="glass-card">
                    <div class="section-header">
                        <div class="icon-box"><i class="fa fa-user"></i></div>
                        <h3>Customer & Delivery Details</h3>
                    </div>
                    <div class="section-body">
                        <!-- Row 1: Customer and Delivery Date -->
                        <div class="form-row-2col">
                            <div class="form-group">
                                <label class="form-label required"><i class="fa fa-user"></i> Customer</label>
                                <select id="customerSelect" class="form-select">
                                    <option value="">Select Customer...</option>
                                    <?php foreach ($customers as $c): ?>
                                        <option value="<?php echo $c['customer_id']; ?>"><?php echo htmlspecialchars($c['customer_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label required"><i class="fa fa-calendar"></i> Delivery Date</label>
                                <input type="text" id="deliveryDatePicker" class="form-control" placeholder="Select date..." readonly disabled style="width: 45%;">
                                <input type="hidden" id="orderDate" value="<?php echo $today; ?>">
                                <div class="order-date-legend" id="dateLegend" style="display:none;">
                                    <span class="dot"></span> Has existing order (click to load)
                                </div>
                            </div>
                        </div>
                        
                        <!-- Row 2: Shipping Address and Payment -->
                        <div class="form-row-2col">
                            <div class="form-group">
                                <label class="form-label required"><i class="fa fa-map-marker"></i> Shipping Address</label>
                                <div class="input-with-button">
                                    <select id="shippingSelect" class="form-select" disabled>
                                        <option value="">Select shipping address...</option>
                                    </select>
                                    <button type="button" id="btnEditAddress" class="btn-edit-inline" title="Edit Primary Shipping Address" disabled>
                                        <i class="fa fa-edit"></i>
                                    </button>
                                </div>

                                <!-- Inline shipping address editor (hidden by default) -->
                                <div id="inlineAddressEditor" style="display:none; margin-top:10px;">
                                    <textarea id="shippingAddressTextarea" class="form-control" rows="3" placeholder="Enter delivery address (this will be used for this order)" style="width: 45%;"></textarea>
                                    <div style="margin-top:8px; display:flex; gap:8px;">
                                        <button type="button" id="btnSaveInlineAddress" class="btn btn-primary btn-sm">Save address</button>
                                        <button type="button" id="btnCancelInlineAddress" class="btn btn-default btn-sm">Cancel</button>
                                        <span id="inlineAddressHint" style="margin-left:auto; font-size:12px; color:#8492a6; align-self:center;">Saved address will be applied to this order only.</span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label required"><i class="fa fa-credit-card"></i> Payment Method</label>
                                <?php
                                    // Default to first payment method containing 'cash' (case-insensitive).
                                    // Fallback to the first available payment method if no explicit 'cash' entry exists.
                                    $defaultPaymentId = null;
                                    foreach ($payment_methods as $pm_tmp) {
                                        if ($defaultPaymentId === null && stripos($pm_tmp['type'], 'cash') !== false) {
                                            $defaultPaymentId = $pm_tmp['id'];
                                            break;
                                        }
                                    }
                                    if ($defaultPaymentId === null && !empty($payment_methods)) {
                                        $defaultPaymentId = $payment_methods[0]['id'];
                                    }
                                ?>
                                <select id="paymentMethod" class="form-select" style="width: 45%;">
                                    <option value="">Select Payment...</option>
                                    <?php foreach ($payment_methods as $pm): ?>
                                        <option value="<?php echo $pm['id']; ?>" <?php echo ($pm['id'] == $defaultPaymentId) ? 'selected' : ''; ?>><?php echo htmlspecialchars($pm['type']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <!-- Shipping Address Details Card -->
                        <div id="addressCard" class="address-card hidden">
                            <div class="address-card-header">
                                <span class="address-label"><i class="fa fa-building"></i> <span id="addrLabel">-</span></span>
                                <span class="address-badge" id="addrDefault" style="display:none;">DEFAULT</span>
                            </div>
                            <div class="address-details">
                                <div class="address-detail">
                                    <div class="detail-icon"><i class="fa fa-map-marker"></i></div>
                                    <span class="detail-text" id="addrFull">-</span>
                                </div>
                                <div class="address-detail">
                                    <div class="detail-icon"><i class="fa fa-user"></i></div>
                                    <span class="detail-text" id="addrContact">-</span>
                                </div>
                                <div class="address-detail">
                                    <div class="detail-icon"><i class="fa fa-phone"></i></div>
                                    <span class="detail-text" id="addrPhone">-</span>
                                </div>
                                <div class="address-detail">
                                    <div class="detail-icon"><i class="fa fa-truck"></i></div>
                                    <span class="detail-text" id="addrRoute">-</span>
                                </div>
                                <div class="address-detail">
                                    <div class="detail-icon"><i class="fa fa-clock-o"></i></div>
                                    <span class="detail-text" id="addrTime">-</span>
                                </div>
                                <div class="address-detail">
                                    <div class="detail-icon"><i class="fa fa-comment"></i></div>
                                    <span class="detail-text" id="addrRemarks">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Info Bar: Address + Notes + Summary -->
                <div class="glass-card">
                    <div class="order-info-bar">
                        <!-- Left: Delivery Info -->
                        <div class="order-info-left">
                            <div>
                                <div class="info-label"><i class="fa fa-map-marker"></i> To</div>
                                <div class="info-value" id="summaryAddress">-</div>
                            </div>
                            <div class="info-row">
                                <label>Order # :</label>
                                <span class="info-value" id="summaryOrderRef"><?php echo $order_ref; ?></span>
                            </div>
                            <div class="info-row">
                                <label>Purchase order# :</label>
                                <input type="text" id="purchaseOrderNumber" placeholder="" style="border: 1px solid var(--gray-300); border-radius: 4px; padding: 6px 10px; font-size: 13px; width: 180px;">
                            </div>
                        </div>

                        <!-- Middle: Order Notes -->
                        <div class="order-notes-section">
                            <textarea id="orderNotes" placeholder="Enter notes for this delivery here. These notes will be displayed at Production and Distribution. They may be edited in Pick & Pack prior to printing the invoice" maxlength="500"></textarea>
                            <div class="char-count"><span id="notesCharCount">0</span>/500 chars</div>
                        </div>

                        <!-- Right: Summary Totals -->
                        <div class="summary-compact">
                            <div class="summary-row">
                                <span>Subtotal:</span>
                                <span id="subtotal"><?php echo $CURRENCY_SYMBOL; ?>0.00</span>
                            </div>
                            <div class="summary-row delivery-check">
                                <label><input type="checkbox" id="deliveryCheckbox" checked> DELIVERY:</label>
                                <span id="deliveryDisplay"><?php echo $CURRENCY_SYMBOL; ?>0.00</span>
                            </div>
                            <div class="summary-row">
                                <span>Discount:</span>
                                <span id="discountDisplay">-<?php echo $CURRENCY_SYMBOL; ?>0.00</span>
                            </div>
                            <div class="summary-row">
                                <span>GST:</span>
                                <span id="gstDisplay"><?php echo $CURRENCY_SYMBOL; ?>0.00</span>
                            </div>
                            <div class="summary-row total">
                                <span>Total</span>
                                <span id="grandTotal"><?php echo $CURRENCY_SYMBOL; ?>0.00</span>
                            </div>
                            <div class="summary-row" style="border-bottom: none;">
                                <span>Total Pieces:</span>
                                <span id="totalPieces">0</span>
                            </div>
                            <div class="summary-row terms">
                                <span>Terms :</span>
                                <span id="summaryTerms">30 Days</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Delivery & Discount Inputs Row -->
                <div style="padding: 12px 20px; overflow: visible; margin-bottom: 16px;">
                    <div style="display: flex; gap: 20px; align-items: flex-start; flex-wrap: wrap;">
                        <div class="summary-input-group" style="flex: 0 0 180px;">
                            <label><i class="fa fa-truck"></i> Delivery Charge</label>
                            <input type="number" id="deliveryCharge" value="0" min="0" step="0.01">
                        </div>
                        <div class="summary-input-group" style="flex: 0 0 180px;">
                            <label><i class="fa fa-percent"></i> Discount (%)</label>
                            <input type="number" id="discountAmount" value="0" min="0" max="100" step="0.01" <?php echo $canEditOrderDiscount ? '' : 'readonly'; ?>>
                        </div>
                    </div>
                </div>

                <!-- Cart Items - Full Width Table -->
                <div class="cart-layout">
                    <div class="glass-card cart-items-section" id="cartSection" style="padding: 0; overflow: visible;">

                        <!-- BC-style toolbar -->
                        <div class="bc-toolbar">
                            <button type="button" class="bc-toolbar-btn" id="bcBtnNewLine" disabled>
                                <i class="fa fa-plus"></i> New Line
                            </button>
                            <button type="button" class="bc-toolbar-btn" id="bcBtnDeleteLine" disabled>
                                <i class="fa fa-times"></i> Delete Line
                            </button>
                            <div class="bc-toolbar-sep"></div>
                            <button type="button" class="bc-toolbar-btn" id="btnAddProduct" disabled>
                                <i class="fa fa-th-list"></i> Select items...
                            </button>
                            <button type="button" class="bc-toolbar-btn" id="btnRefreshProducts" title="Refresh product list">
                                <i class="fa fa-refresh"></i>
                            </button>
                            <!-- inline search (right side of toolbar) -->
                            <div class="bc-toolbar-search-wrap" id="bcSearchWrap" style="display:none;">
                                <i class="fa fa-search"></i>
                                <input type="text" id="quickSearchProduct" placeholder="Quick search..." autocomplete="off" disabled>
                                <div id="quickSearchDropdown" style="display:none; position:absolute; top:100%; left:0; right:0; background:#fff; border:1px solid #d1d5db; border-top:none; border-radius:0 0 4px 4px; max-height:220px; overflow-y:auto; z-index:20000;"></div>
                            </div>
                        </div>

                        <div style="overflow-x: auto;">
                        <table class="cart-table">
                            <thead>
                                <tr>
                                    <th>Product Type</th>
                                    <th style="min-width:160px;">No. / Item</th>
                                    <th>Item Code</th>
                                    <th style="text-align:right">Unit Price</th>
                                    <th style="text-align:right">Disc %</th>
                                    <th style="text-align:center">GST</th>
                                    <th style="text-align:center">SO</th>
                                    <th style="text-align:center; min-width:90px;">Quantity</th>
                                    <th style="text-align:right">Line Total</th>
                                    <th style="width:70px;"></th>
                                </tr>
                            </thead>
                            <tbody id="cartTableBody">
                                <tr>
                                    <td colspan="10">
                                        <div class="cart-empty">
                                            <div class="cart-empty-icon"><i class="fa fa-shopping-basket"></i></div>
                                            <h3>Your cart is empty</h3>
                                            <p>Select a customer first, then add products to get started</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        </div>
                    </div>

                    <!-- Checkout Actions -->
                    <div class="cart-actions-row">
                        <form id="checkoutForm" action="process/cart-order-process.php" method="POST" style="flex:1; display:flex; gap:12px;">
                            <input type="hidden" name="order_ref" value="<?php echo $order_ref; ?>">
                            <input type="hidden" name="order_type" value="CART">
                            <input type="hidden" name="order_date" id="orderDateHidden">
                            <input type="hidden" name="customer_id" id="customerIdHidden">
                            <input type="hidden" name="shipping_address_id" id="shippingIdHidden">
                            <input type="hidden" name="delivery_address_text" id="deliveryAddressText">
                            <input type="hidden" name="payment_method" id="paymentMethodHidden" value="<?php echo isset($defaultPaymentId) ? (int)$defaultPaymentId : ''; ?>">
                            <input type="hidden" name="cart_data" id="cartDataHidden">
                            <input type="hidden" name="subtotal" id="subtotalHidden">
                            <input type="hidden" name="delivery_charge" id="deliveryHidden">
                            <input type="hidden" name="discount" id="discountHidden">
                            <input type="hidden" name="purchase_order_no" id="purchaseOrderHidden">
                            <input type="hidden" name="grand_total" id="grandTotalHidden">

                            <button type="submit" class="btn-checkout" id="btnCheckout" disabled>
                                <i class="fa fa-check-circle"></i> Complete Order
                            </button>
                        </form>
                        <button type="button" class="btn-clear" id="btnClearCart">
                            <i class="fa fa-trash-o"></i> Clear Cart
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Modal -->
    <div class="modal-overlay" id="productModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3>
                    <span class="icon-wrap"><i class="fa fa-cube"></i></span>
                    Select Products
                </h3>
                <button type="button" class="modal-close" id="btnCloseModal"><i class="fa fa-times"></i></button>
            </div>
            <div class="modal-search">
                <div class="search-input-wrap">
                    <i class="fa fa-search"></i>
                    <input type="text" id="searchProduct" placeholder="Search by name or code...">
                </div>
                <select id="categoryFilter">
                    <option value="0">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="modal-body">
                <!-- Product List (Left) -->
                <div class="products-list-container">
                    <div class="products-list" id="productsList">
                        <div class="loading"><div class="spinner"></div><p>Loading products...</p></div>
                    </div>
                </div>
                <!-- Quantity Panel (Right) -->
                <div class="qty-panel">
                    <div class="qty-panel-empty" id="qtyPanelEmpty">
                        <i class="fa fa-hand-pointer-o"></i>
                        <p>Select a product from the list to add quantity</p>
                    </div>
                    <div class="qty-panel-content" id="qtyPanelContent">
                        <div class="qty-panel-product">
                            <div class="qty-panel-img" id="qtyPanelImg">
                                <i class="fa fa-cube"></i>
                            </div>
                            <div class="qty-panel-code" id="qtyPanelCode">-</div>
                            <div class="qty-panel-name" id="qtyPanelName">-</div>
                            <div class="qty-panel-price" id="qtyPanelPrice">$0.00</div>
                            <div class="qty-panel-stock" id="qtyPanelStock">0 available</div>
                        </div>
                        <div class="qty-panel-input-group">
                            <label>Quantity</label>
                            <div class="qty-panel-controls">
                                <button type="button" id="qtyMinus"><i class="fa fa-minus"></i></button>
                                <input type="number" id="qtyInput" value="1" min="1">
                                <button type="button" id="qtyPlus"><i class="fa fa-plus"></i></button>
                            </div>
                        </div>
                        <div class="qty-panel-total">
                            <span>Total</span>
                            <strong id="qtyPanelTotal">$0.00</strong>
                        </div>
                        <button type="button" class="btn-add-to-cart" id="btnAddToCart">
                            <i class="fa fa-cart-plus"></i> Add to Cart
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div class="toast" id="toast">
        <i class="fa fa-check-circle"></i>
        <span id="toastMessage">Success</span>
    </div>

    <?php include('common/footer.php'); ?>
    
<!-- Include Libs & Plugins
	============================================ -->
<!-- Placed at the end of the document so the pages load faster -->
    <script src="assets/global/plugins/jquery.min.js"></script>
    <script src="assets/global/plugins/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/global/plugins/select2/js/select2.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="assets/layouts/layout/scripts/layout.min.js"></script>

    <script>
    $(document).ready(function() {
        var cart = [];
        var products = [];
        var searchTerm = '';
        var currentCategory = '0';
        var selectedCustomerId = null;
        var selectedShippingId = null;
        var currencySymbol = '<?php echo $CURRENCY_SYMBOL; ?>';
        var gstRateMap = <?php echo json_encode($gstRateMap, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE); ?> || {};
        var customerLineDiscountMap = <?php echo json_encode($customerLineDiscountMap, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE); ?> || {};
        var customerOrderDiscountMap = <?php echo json_encode($customerOrderDiscountMap, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE); ?> || {};
        var canEditOrderDiscount = <?php echo $canEditOrderDiscount ? 'true' : 'false'; ?>;
        var lockedOrderDiscountPercent = 0;
        var editingInvoiceId = null; // Track if we're editing an existing order
        var originalOrderRef = '<?php echo $order_ref; ?>';
        var orderDatesMap = {}; // { 'YYYY-MM-DD': { invoice_id, invoice_code, gross_value, order_note } }
        var flatpickrInstance = null;

        function getCustomerLineDiscountPercent(customerId) {
            var id = (customerId || '').toString().trim();
            if (!id) return 0;

            if (Object.prototype.hasOwnProperty.call(customerLineDiscountMap, id)) {
                var byString = parseFloat(customerLineDiscountMap[id]);
                return isFinite(byString) ? Math.max(0, Math.min(100, byString)) : 0;
            }

            var numericId = parseInt(id, 10);
            if (isFinite(numericId) && Object.prototype.hasOwnProperty.call(customerLineDiscountMap, numericId)) {
                var byNumber = parseFloat(customerLineDiscountMap[numericId]);
                return isFinite(byNumber) ? Math.max(0, Math.min(100, byNumber)) : 0;
            }

            return 0;
        }

        function getActiveCustomerLineDiscountPercent() {
            return getCustomerLineDiscountPercent(selectedCustomerId);
        }

        function getCustomerOrderDiscountPercent(customerId) {
            var id = (customerId || '').toString().trim();
            if (!id) return 0;

            if (Object.prototype.hasOwnProperty.call(customerOrderDiscountMap, id)) {
                var byString = parseFloat(customerOrderDiscountMap[id]);
                return isFinite(byString) ? Math.max(0, Math.min(100, byString)) : 0;
            }

            var numericId = parseInt(id, 10);
            if (isFinite(numericId) && Object.prototype.hasOwnProperty.call(customerOrderDiscountMap, numericId)) {
                var byNumber = parseFloat(customerOrderDiscountMap[numericId]);
                return isFinite(byNumber) ? Math.max(0, Math.min(100, byNumber)) : 0;
            }

            return 0;
        }

        function getActiveCustomerOrderDiscountPercent() {
            return getCustomerOrderDiscountPercent(selectedCustomerId);
        }

        function setOrderDiscountInputValue(value) {
            var discount = parseFloat(value);
            if (!isFinite(discount)) {
                discount = 0;
            }
            discount = Math.max(0, Math.min(100, discount));
            if (!canEditOrderDiscount) {
                lockedOrderDiscountPercent = discount;
            }
            $('#discountAmount').val(discount.toFixed(2));
        }

        function isGiftItem(item) {
            return parseInt((item && item.is_gift_item) || 0, 10) === 1;
        }

        function applyActiveCustomerLineDiscountToCart() {
            var customerDiscount = getActiveCustomerLineDiscountPercent();
            console.log('[Disc%] applyActiveCustomerLineDiscountToCart', {
                customerId: selectedCustomerId,
                customerDiscount: customerDiscount,
                cartSize: cart.length
            });
            cart.forEach(function(item) {
                if (!item || isGiftItem(item)) {
                    return;
                }
                item.discount = customerDiscount;
            });
        }

        function normalizeCartLineDiscountsForRender() {
            var customerDiscount = getActiveCustomerLineDiscountPercent();
            cart.forEach(function(item) {
                if (!item) {
                    return;
                }

                if (isGiftItem(item)) {
                    item.discount = 100;
                    return;
                }

                item.discount = customerDiscount;
            });
        }

        function loadCustomerLineDiscount(customerId, callback) {
            var id = (customerId || '').toString().trim();
            if (!id) {
                if (typeof callback === 'function') {
                    callback(0);
                }
                return;
            }

            $.ajax({
                url: '',
                method: 'POST',
                data: { action: 'get_customer_line_discount', customer_id: id },
                dataType: 'json',
                success: function(response) {
                    var discount = 0;
                    if (response && response.status === 'success') {
                        discount = parseFloat(response.discount) || 0;
                    } else {
                        discount = getCustomerLineDiscountPercent(id);
                    }
                    discount = Math.min(100, Math.max(0, discount));
                    console.log('[Disc%] loadCustomerLineDiscount success', {
                        customerId: id,
                        response: response,
                        resolvedDiscount: discount
                    });
                    customerLineDiscountMap[id] = discount;
                    if (typeof callback === 'function') {
                        callback(discount);
                    }
                },
                error: function() {
                    var fallback = getCustomerLineDiscountPercent(id);
                    console.log('[Disc%] loadCustomerLineDiscount error fallback', {
                        customerId: id,
                        fallbackDiscount: fallback
                    });
                    if (typeof callback === 'function') {
                        callback(fallback);
                    }
                }
            });
        }

        function loadCustomerOrderDiscount(customerId, callback) {
            var id = (customerId || '').toString().trim();
            if (!id) {
                if (typeof callback === 'function') {
                    callback(0);
                }
                return;
            }

            $.ajax({
                url: '',
                method: 'POST',
                data: { action: 'get_customer_order_discount', customer_id: id },
                dataType: 'json',
                success: function(response) {
                    var discount = 0;
                    if (response && response.status === 'success') {
                        discount = parseFloat(response.discount) || 0;
                    } else {
                        discount = getCustomerOrderDiscountPercent(id);
                    }
                    discount = Math.min(100, Math.max(0, discount));
                    customerOrderDiscountMap[id] = discount;
                    if (typeof callback === 'function') {
                        callback(discount);
                    }
                },
                error: function() {
                    var fallback = getCustomerOrderDiscountPercent(id);
                    if (typeof callback === 'function') {
                        callback(fallback);
                    }
                }
            });
        }

        // Initialize Select2
        $('#customerSelect, #shippingSelect').select2({
            theme: 'bootstrap',
            allowClear: true,
            placeholder: 'Select...'
        });

        // Function to update quick search enabled state
        function updateQuickSearchState() {
            var customerSelected = selectedCustomerId && selectedCustomerId !== '';
            var dateSelected = $('#deliveryDatePicker').val() && $('#deliveryDatePicker').val() !== '';
            if (customerSelected && dateSelected) {
                $('#quickSearchProduct').prop('disabled', false);
                $('#bcSearchWrap').show();
                $('#bcBtnNewLine').prop('disabled', false);
                $('#btnAddProduct').prop('disabled', false);
            } else {
                $('#quickSearchProduct').prop('disabled', true).val('');
                $('#quickSearchDropdown').hide();
                if (!customerSelected) {
                    $('#bcSearchWrap').hide();
                    $('#bcBtnNewLine').prop('disabled', true);
                    $('#btnAddProduct').prop('disabled', true);
                }
            }
        }
        flatpickrInstance = flatpickr('#deliveryDatePicker', {
            dateFormat: 'Y-m-d',
            defaultDate: '<?php echo $today; ?>',
            minDate: 'today', // <-- prevent selecting past dates
            allowInput: false,
            onDayCreate: function(dObj, dStr, fp, dayElem) {
                var dateStr = dayElem.dateObj.getFullYear() + '-' +
                    String(dayElem.dateObj.getMonth() + 1).padStart(2, '0') + '-' +
                    String(dayElem.dateObj.getDate()).padStart(2, '0');
                if (orderDatesMap[dateStr]) {
                    dayElem.classList.add('has-order');
                    dayElem.title = orderDatesMap[dateStr].invoice_code +
                        ' (' + currencySymbol + orderDatesMap[dateStr].gross_value.toFixed(2) + ')';
                }
            },
            onChange: function(selectedDates, dateStr) {
                console.log('Calendar date changed to:', dateStr, 'orderDatesMap entry:', orderDatesMap[dateStr]);
                $('#orderDate').val(dateStr);
                updateQuickSearchState();
                // If this date has an existing order, load it
                if (orderDatesMap[dateStr]) {
                    loadOrderByInvoiceId(orderDatesMap[dateStr].invoice_id);
                } else {
                    // New date with no order — reset to new order mode
                    resetToNewOrder();
                }
            }
        });

        // Preselect customer from URL param if provided (cart link)
        var preselectCustomerId = <?php echo (int)($_GET['customer_id'] ?? 0); ?>;
        if (preselectCustomerId) {
            // set the select and trigger change to load shipping addresses
            $('#customerSelect').val(preselectCustomerId).trigger('change');
        }

        // If page opened with ?invoice_id=.. then auto-load that order for editing
        var initialEditInvoiceId = <?php echo (int)($_GET['invoice_id'] ?? 0); ?>;
        if (initialEditInvoiceId) {
            $.ajax({
                url: '',
                method: 'POST',
                data: { action: 'load_existing_order', invoice_id: initialEditInvoiceId },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success' && response.order) {
                        applyLoadedOrder(response.order);
                    } else {
                        showToast(response.message || 'Failed to load order', 'error');
                    }
                },
                error: function() {
                    showToast('Error loading order', 'error');
                }
            });
        }

        // Toast
        function showToast(msg, type) {
            var $t = $('#toast');
            var icon = type === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle';
            $t.find('i').removeClass().addClass('fa ' + icon);
            $t.removeClass('success error').addClass(type || 'success');
            $('#toastMessage').text(msg);
            $t.addClass('show');
            setTimeout(function() { $t.removeClass('show'); }, 3000);
        }

        // Load existing order for selected customer and date
        // Reset to new order mode
        function resetToNewOrder() {
            editingInvoiceId = null;
            showInlineRow = false;
            editingLineIndex = null;
            hideInlineDropdown();
            $('.badge-ref').html('<i class="fa fa-tag"></i> ' + originalOrderRef);
            $('input[name="order_ref"]').val(originalOrderRef);
            $('input[name="editing_invoice_id"]').remove();
            $('#btnCheckout').html('<i class="fa fa-check-circle"></i> Complete Order');
            $('#purchaseOrderNumber').val('');
            setOrderDiscountInputValue(getActiveCustomerOrderDiscountPercent());
            cart = [];
            renderCart();
            updateTotals();
            // Enable shipping address change for new orders
            $('#shippingSelect').prop('disabled', false).removeClass('disabled-section');
            $('#shippingSelect').closest('.form-group').find('small').remove();
        }

        // Apply order payload to UI when loading existing order
        function applyLoadedOrder(order) {
            editingInvoiceId = order.invoice_id;
            // Preselect customer (if provided) and trigger change to load addresses/dates
            if (order.customer_id) {
                selectedCustomerId = order.customer_id;
                $('#customerSelect').val(order.customer_id).trigger('change');
            }

            // Update order ref badge to show we're editing
            $('.badge-ref').html('<i class="fa fa-edit"></i> Editing: ' + order.invoice_code);
            $('input[name="order_ref"]').val(order.invoice_code);
            $('input[name="editing_invoice_id"]').remove();
            $('#checkoutForm').append('<input type="hidden" name="editing_invoice_id" value="' + order.invoice_id + '">');
            $('#btnCheckout').html('<i class="fa fa-save"></i> Update Order');

            // Set delivery date if available
            if (order.delivery_date) {
                if (flatpickrInstance) {
                    flatpickrInstance.setDate(order.delivery_date, false);
                }
                $('#orderDate').val(order.delivery_date);
            }

            // Delay setting shipping address to allow customer change to complete loading addresses
            setTimeout(function() {
                // Set shipping address if available (or show custom delivery address)
                if (order.shipping_address_id) {
                    $('#shippingSelect').val(order.shipping_address_id).trigger('change');
                    $('#deliveryAddressText').val('');
                } else if (order.delivery_address) {
                    // Clear shipping select and show custom delivery address
                    $('#shippingSelect').val('').trigger('change');
                    $('#shippingIdHidden').val('');
                    $('#deliveryAddressText').val(order.delivery_address);
                    $('#addrLabel').text('Custom Address');
                    $('#addrFull').text(order.delivery_address);
                    $('#summaryAddress').text(order.delivery_address);
                    $('#addrContact').text('-');
                    $('#addrPhone').text('-');
                    $('#addrRoute').text('-');
                    $('#addrTime').text('-');
                    $('#addrRemarks').text('-');
                    $('#addrDefault').hide();
                    $('#addressCard').removeClass('hidden');
                }

                // Set payment method
                if (order.payment_method) {
                    $('#paymentMethod').val(order.payment_method);
                }

                // Set delivery and discount
                $('#deliveryCharge').val(order.delivery_charge || 0);
                setOrderDiscountInputValue(getActiveCustomerOrderDiscountPercent());
                $('#purchaseOrderNumber').val(order.purchase_order_no || '');

                // Load cart items
                cart = [];
                let hasStandingOrderItems = false;
                order.items.forEach(function(item) {
                    cart.push({
                        id: item.id,
                        detail_id: item.detail_id,
                        name: item.name,
                        code: item.code,
                        price: item.price,
                        qty: item.qty,
                        discount: parseFloat(item.discount || 0),
                        stock: item.stock,
                        gst_code: item.gst_code || '',
                        item_group: item.item_group || '',
                        is_gift_item: parseInt(item.is_gift_item || 0, 10) === 1 ? 1 : 0,
                        is_cart_item: item.is_cart_item || 0,
                        is_original: true, // Mark as original item
                        so_qty: item.so_qty // Original standing order qty (null if not a SO item)
                    });
                    if (item.is_cart_item == 0) {
                        hasStandingOrderItems = true;
                    }
                });

                // If order has standing order items, disable shipping address change
                if (hasStandingOrderItems) {
                    $('#shippingSelect').prop('disabled', true).addClass('disabled-section');
                    $('#shippingSelect').closest('.form-group').append('<small class="text-muted" style="display:block;margin-top:4px;"><i class="fa fa-lock"></i> Shipping address cannot be changed for standing orders</small>');
                } else {
                    $('#shippingSelect').prop('disabled', false).removeClass('disabled-section');
                    $('#shippingSelect').closest('.form-group').find('small').remove();
                }

                applyActiveCustomerLineDiscountToCart();
                renderCart();
                updateTotals();
                updateQuickSearchState();
                showToast('Order loaded for editing', 'success');
            }, 500); // 500ms delay to allow customer change to complete
        }

        // Load order by invoice ID helper
        function loadOrderByInvoiceId(invoiceId) {
            console.log('Loading order by invoice ID:', invoiceId, 'for customer:', selectedCustomerId);
            $.ajax({
                url: '',
                method: 'POST',
                data: { action: 'load_existing_order', invoice_id: invoiceId, customer_id: selectedCustomerId },
                dataType: 'json',
                success: function(response) {
                    console.log('Order load response:', response);
                    if (response.status === 'success' && response.order) {
                        editingInvoiceId = response.order.invoice_id;

                        // Update order ref badge
                        $('.badge-ref').html('<i class="fa fa-edit"></i> Editing: ' + response.order.invoice_code);
                        $('input[name="order_ref"]').val(response.order.invoice_code);
                        $('input[name="editing_invoice_id"]').remove();
                        $('#checkoutForm').append('<input type="hidden" name="editing_invoice_id" value="' + response.order.invoice_id + '">');
                        $('#btnCheckout').html('<i class="fa fa-save"></i> Update Order');

                        // Set delivery date if available
                        if (response.order.delivery_date) {
                            if (flatpickrInstance) {
                                flatpickrInstance.setDate(response.order.delivery_date, false);
                            }
                            $('#orderDate').val(response.order.delivery_date);
                        }

                        // Set shipping address (with delay to allow customer change to complete if needed)
                        setTimeout(function() {
                            if (response.order.shipping_address_id) {
                                $('#shippingSelect').val(response.order.shipping_address_id).trigger('change');
                            }
                            // Set payment method
                            if (response.order.payment_method) {
                                $('#paymentMethod').val(response.order.payment_method);
                            }
                            // Set delivery and discount
                            $('#deliveryCharge').val(response.order.delivery_charge || 0);
                            setOrderDiscountInputValue(getActiveCustomerOrderDiscountPercent());
                            $('#purchaseOrderNumber').val(response.order.purchase_order_no || '');

                            // Load cart items
                            cart = [];
                            var hasStandingOrderItems = false;
                            response.order.items.forEach(function(item) {
                                cart.push({
                                    id: item.id,
                                    detail_id: item.detail_id,
                                    name: item.name,
                                    code: item.code,
                                    price: item.price,
                                    qty: item.qty,
                                    discount: parseFloat(item.discount || 0),
                                    stock: item.stock,
                                    gst_code: item.gst_code || '',
                                    item_group: item.item_group || '',
                                    is_gift_item: parseInt(item.is_gift_item || 0, 10) === 1 ? 1 : 0,
                                    is_cart_item: item.is_cart_item || 0,
                                    is_original: true,
                                    so_qty: item.so_qty
                                });
                                if (item.is_cart_item == 0) hasStandingOrderItems = true;
                            });

                            if (hasStandingOrderItems) {
                                $('#shippingSelect').prop('disabled', true).addClass('disabled-section');
                                $('#shippingSelect').closest('.form-group').find('small').remove();
                                $('#shippingSelect').closest('.form-group').append('<small class="text-muted" style="display:block;margin-top:4px;"><i class="fa fa-lock"></i> Shipping address cannot be changed for standing orders</small>');
                            }

                            applyActiveCustomerLineDiscountToCart();
                            renderCart();
                            updateTotals();
                            updateQuickSearchState();
                            showToast('Order loaded for editing', 'success');
                        }, 500); // Delay to allow any pending customer/address loading to complete
                    } else {
                        showToast(response.message || 'No order found for this date', 'info');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error loading order:', status, error, xhr.responseText);
                    showToast('Error loading order: ' + error, 'error');
                }
            });
        }

        // Load order dates for calendar highlighting
        function loadOrderDatesForCalendar() {
            orderDatesMap = {};
            $.ajax({
                url: '',
                method: 'POST',
                data: { action: 'get_customer_order_dates', customer_id: selectedCustomerId },
                dataType: 'json',
                success: function(orders) {
                    if (orders && orders.length > 0) {
                        orders.forEach(function(o) {
                            orderDatesMap[o.delivery_date] = {
                                invoice_id: o.invoice_id,
                                invoice_code: o.invoice_code,
                                gross_value: o.gross_value,
                                order_note: o.order_note
                            };
                        });
                        $('#dateLegend').show();
                    } else {
                        $('#dateLegend').hide();
                    }
                    // Refresh flatpickr to re-paint highlighted dates  
                    if (flatpickrInstance) {
                        flatpickrInstance.redraw();
                    }
                },
                error: function() {
                    $('#dateLegend').hide();
                }
            });
        }

        // Customer Change - Load Shipping Addresses
        $('#customerSelect').on('change', function() {
            selectedCustomerId = $(this).val();
            $('#shippingSelect').empty().append('<option value="">Select shipping address...</option>');
            $('#addressCard').addClass('hidden');
            selectedShippingId = null;
            orderDatesMap = {};
            $('#dateLegend').hide();
            resetToNewOrder();
            $('#quickSearchProduct').val('').prop('disabled', true);

            if (flatpickrInstance) {
                flatpickrInstance.clear();
                flatpickrInstance.redraw();
            }

            if (!selectedCustomerId) {
                setOrderDiscountInputValue(0);
                $('#shippingSelect').prop('disabled', true);
                $('#btnEditAddress').prop('disabled', true);
                $('#redirectToStandingOrder').prop('disabled', true);
                $('#deliveryDatePicker').disabled = true;
                $('#cartSection').addClass('disabled-section');
                $('#bcSearchWrap').hide();
                $('#bcBtnNewLine, #btnAddProduct').prop('disabled', true);
                updateQuickSearchState();
                return;
            }

            loadCustomerOrderDiscount(selectedCustomerId, function(discount) {
                setOrderDiscountInputValue(discount);
                updateTotals();
            });
            loadCustomerLineDiscount(selectedCustomerId, function() {
                applyActiveCustomerLineDiscountToCart();
                renderCart();
            });

            // Enable standing order redirect button
            $('#redirectToStandingOrder').prop('disabled', false);

            // Enable flatpickr
            if (flatpickrInstance) {
                flatpickrInstance.set('clickOpens', true);
                document.getElementById('deliveryDatePicker').disabled = false;
            }

            // Load shipping addresses
            $.ajax({
                url: '',
                method: 'POST',
                data: { action: 'get_customer_shipping_addresses', customer_id: selectedCustomerId },
                dataType: 'json',
                success: function(addresses) {
                    if (addresses && addresses.length > 0) {
                        addresses.forEach(function(addr) {
                            var label = addr.address_label || addr.address_line_1;
                            var def = addr.is_default == 1 ? ' (Default)' : '';
                            $('#shippingSelect').append('<option value="' + addr.id + '"' + (addr.is_default == 1 ? ' selected' : '') + '>' + label + def + '</option>');
                        });
                        $('#shippingSelect').prop('disabled', false).trigger('change');
                        $('#btnEditAddress').prop('disabled', false);
                    } else {
                        $('#shippingSelect').prop('disabled', true);
                        $('#btnEditAddress').prop('disabled', true);
                        showToast('No shipping addresses found for this customer', 'error');
                    }
                    $('#cartSection').removeClass('disabled-section');
                    $('#bcSearchWrap').show();
                    renderCart();
                    updateQuickSearchState();
                },
                error: function() {
                    showToast('Error loading addresses', 'error');
                }
            });

            // Load order dates for calendar highlighting
            loadOrderDatesForCalendar();

            // Load products for quick search
            loadProductsForQuickSearch();
        });

        // Redirect to Standing Order Button
        $('#redirectToStandingOrder').on('click', function() {
            if (!selectedCustomerId) {
                showToast('Please select a customer first', 'error');
                return;
            }
            // Redirect to standing order page with customer pre-selected
            window.location.href = 'standing-order.php?customer_id=' + selectedCustomerId;
        });

        // Edit Address Button - show inline textarea to edit/override delivery address
        $('#btnEditAddress').on('click', function() {
            var cid = $('#customerSelect').val();
            if (!cid) { showToast('Select customer first', 'error'); return; }
            // Pre-fill textarea with currently displayed address (addrFull) or empty
            var current = $('#addrFull').text();
            if (current && current !== '-') {
                $('#shippingAddressTextarea').val(current);
            } else {
                $('#shippingAddressTextarea').val('');
            }
            $('#inlineAddressEditor').slideDown(150);
            $('#shippingAddressTextarea').focus();
        });

        // Inline address editor actions
        $('#btnCancelInlineAddress').on('click', function() {
            $('#inlineAddressEditor').slideUp(120);
            $('#shippingAddressTextarea').val('');
        });

        $('#btnSaveInlineAddress').on('click', function() {
            var txt = $('#shippingAddressTextarea').val().trim();
            if (!txt) { showToast('Enter a delivery address', 'error'); return; }
            // Clear shipping select (we're using a custom address for this order)
            $('#shippingSelect').val('').trigger('change');
            $('#shippingIdHidden').val('');
            $('#deliveryAddressText').val(txt);
            // Update address card UI
            $('#addrLabel').text('Custom Address');
            $('#addrFull').text(txt);
            $('#addrContact').text('-');
            $('#addrPhone').text('-');
            $('#addrRoute').text('-');
            $('#addrTime').text('-');
            $('#addrRemarks').text('-');
            $('#addrDefault').hide();
            $('#addressCard').removeClass('hidden');
            $('#inlineAddressEditor').slideUp(120);
            showToast('Delivery address applied to this order', 'success');
        });

        // Shipping Address Change - Load Details
        $('#shippingSelect').on('change', function() {
            selectedShippingId = $(this).val();
            // reflect selection to hidden inputs; do NOT clobber a custom delivery text when the select is emptied
            $('#shippingIdHidden').val(selectedShippingId);
            $('#inlineAddressEditor').slideUp(120);

            // If user cleared the select (empty) keep any existing custom delivery text
            if (!selectedShippingId) {
                if (($('#deliveryAddressText').val() || '').toString().trim()) {
                    // show the custom address card
                    $('#addressCard').removeClass('hidden');
                } else {
                    $('#addressCard').addClass('hidden');
                }
                return;
            }

            // When a saved address is selected we intentionally clear any inline/custom override
            $('#deliveryAddressText').val('');

            $.ajax({
                url: '',
                method: 'POST',
                data: { action: 'get_shipping_address_details', shipping_address_id: selectedShippingId },
                dataType: 'json',
                success: function(addr) {
                    if (addr && !addr.error) {
                        $('#addrLabel').text(addr.address_label || 'Shipping Address');
                        if (addr.is_default == 1) {
                            $('#addrDefault').show();
                        } else {
                            $('#addrDefault').hide();
                        }
                        
                        var fullAddr = [addr.address_line_1, addr.address_line_2, addr.city, addr.state, addr.postal_code, addr.country].filter(Boolean).join(', ');
                        $('#addrFull').text(fullAddr || '-');
                        $('#addrContact').text(addr.contact_person_name || '-');
                        $('#addrPhone').text(addr.contact_person_phone || addr.contact_no || '-');
                        $('#addrRoute').text(addr.route_name || 'No Route');

                        // ---- Capture delivery rule context for live preview / enforcement ----
                        currentDeliveryRule = null;
                        if (addr.delivery_rule_tiers && addr.delivery_rule_tiers.length > 0) {
                            var g = addr.global_delivery_settings || {};
                            currentDeliveryRule = {
                                rule_id: parseInt(addr.delivery_rule_id, 10) || 0,
                                rule_name: addr.delivery_rule_name || '',
                                tiers: addr.delivery_rule_tiers,
                                min_cart: (addr.min_cart_order_override !== null && addr.min_cart_order_override !== '' && addr.min_cart_order_override !== undefined)
                                    ? parseFloat(addr.min_cart_order_override)
                                    : (parseFloat(g.min_cart_order) || 0),
                                weekly_free: (addr.weekly_avg_free_delivery_override !== null && addr.weekly_avg_free_delivery_override !== '' && addr.weekly_avg_free_delivery_override !== undefined)
                                    ? parseFloat(addr.weekly_avg_free_delivery_override)
                                    : (parseFloat(g.weekly_avg_free_delivery) || 0)
                            };
                            // Lock manual delivery input when a rule is in effect
                            $('#deliveryCharge').prop('readonly', true).attr('title', 'Calculated automatically from delivery rule "' + currentDeliveryRule.rule_name + '"');
                            $('#deliveryRuleHint').remove();
                            $('#deliveryCharge').after('<small id="deliveryRuleHint" class="text-muted" style="display:block;margin-top:4px;color:#2ec4a5;">Auto-calculated from rule: <strong>' + currentDeliveryRule.rule_name + '</strong></small>');
                        } else {
                            $('#deliveryCharge').prop('readonly', false).removeAttr('title');
                            $('#deliveryRuleHint').remove();
                            // Fallback: legacy route_amount behaviour
                            if (addr.route_amount && parseFloat(addr.route_amount) > 0) {
                                $('#deliveryCharge').val(parseFloat(addr.route_amount).toFixed(2));
                            }
                        }
                        updateTotals(); // Recalculate totals (now rule-aware)

                        var time = '';
                        if (addr.delivery_time_from && addr.delivery_time_till) {
                            time = addr.delivery_time_from + ' - ' + addr.delivery_time_till;
                        }
                        $('#addrTime').text(time || 'Any time');
                        $('#addrRemarks').text(addr.remarks || '-');
                        
                        $('#addressCard').removeClass('hidden');

                        // Update summary address display
                        $('#summaryAddress').text(fullAddr || '-');
                    }
                }
            });
        });

        // Order notes char counter
        $('#orderNotes').on('input', function() {
            $('#notesCharCount').text($(this).val().length);
        });

        // Refresh products button
        $('#btnRefreshProducts').on('click', function() {
            loadProductsForQuickSearch();
            showToast('Product list refreshed', 'success');
        });

        // Open/Close Product Modal
        function openModal() {
            if (!selectedCustomerId) {
                showToast('Please select a customer first', 'error');
                return;
            }
            $('#productModal').addClass('active');
            resetQtyPanel();
            loadProducts();
        }

        function closeModal() {
            $('#productModal').removeClass('active');
            resetQtyPanel();
        }

        $('#btnAddProduct').on('click', openModal);
        $('#btnCloseModal').on('click', closeModal);
        $('#productModal').on('click', function(e) {
            if (e.target === this) closeModal();
        });
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });

        // Load Products
        function loadProducts() {
            $('#productsList').html('<div class="loading"><div class="spinner"></div><p>Loading...</p></div>');
            resetQtyPanel();
            $.ajax({
                url: '',
                method: 'POST',
                data: {
                    action: 'get_products',
                    category_id: currentCategory,
                    location: '<?php echo $_SESSION['location']; ?>',
                    customer_id: selectedCustomerId
                },
                dataType: 'json',
                success: function(data) {
                    products = data || [];
                    renderProducts();
                },
                error: function() {
                    $('#productsList').html('<div class="loading"><p>Error loading products</p></div>');
                }
            });
        }

        // Load Products for Quick Search
        function loadProductsForQuickSearch(searchTerm, callback) {
            if (typeof searchTerm === 'function') {
                callback = searchTerm;
                searchTerm = '';
            }

            searchTerm = (searchTerm || '').toString().trim();
            var requestData = {
                action: 'get_products',
                category_id: '0', // All categories
                location: '<?php echo $_SESSION['location']; ?>',
                customer_id: selectedCustomerId,
                search: searchTerm
            };

            if (searchTerm.length > 0) {
                requestData.limit = 20;
            }

            $.ajax({
                url: '',
                method: 'POST',
                data: requestData,
                dataType: 'json',
                success: function(data) {
                    products = data || [];
                    if (callback) callback();
                },
                error: function() {
                    products = [];
                    if (callback) callback();
                }
            });
        }

        // Selected product for qty panel
        var selectedProduct = null;

        // Reset Qty Panel
        function resetQtyPanel() {
            selectedProduct = null;
            $('#qtyPanelEmpty').show();
            $('#qtyPanelContent').removeClass('active');
            $('.product-list-item').removeClass('selected');
        }

        // Render Products as List
        function renderProducts() {
            var filtered = products.filter(function(p) {
                if (searchTerm) {
                    return p.item_name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                           p.item_code.toLowerCase().includes(searchTerm.toLowerCase());
                }
                return true;
            });

            if (filtered.length === 0) {
                $('#productsList').html('<div class="loading"><p>No products found</p></div>');
                return;
            }

            var html = '';
            filtered.forEach(function(p) {
                var stock = parseFloat(p.stock_qty || 0);
                var stockClass = stock <= 0 ? 'out' : (stock <= 10 ? 'low' : 'in');
                var stockText = stock + ' in stock';

                // Check for product image
                var imgHtml = '';
                if (p.imageParth && p.imageParth.trim() !== '') {
                    imgHtml = '<img src="' + p.imageParth + '" alt="' + escapeHtml(p.item_name) + '" onerror="this.parentElement.innerHTML=\'<i class=fa fa-cube></i>\'">';
                } else if (p.item_image && p.item_image.trim() !== '') {
                    imgHtml = '<img src="uploads/products/' + p.item_image + '" alt="' + escapeHtml(p.item_name) + '" onerror="this.parentElement.innerHTML=\'<i class=fa fa-cube></i>\'">';
                } else {
                    imgHtml = '<i class="fa fa-cube"></i>';
                }

                html += '<div class="product-list-item" ' +
                        'data-id="' + p.item_id + '" data-name="' + escapeHtml(p.item_name) + '" ' +
                        'data-code="' + p.item_code + '" data-price="' + p.price + '" data-stock="' + stock + '" ' +
                        'data-gst="' + (p.gst_code || '') + '" data-group="' + (p.item_group || '') + '" ' +
                        'data-image="' + (p.imageParth || (p.item_image ? 'uploads/products/' + p.item_image : '')) + '">' +
                        '<div class="product-list-img">' + imgHtml + '</div>' +
                        '<div class="product-list-info">' +
                        '<div class="product-list-code">' + p.item_code + '</div>' +
                        '<div class="product-list-name">' + escapeHtml(p.item_name) + '</div>' +
                        '</div>' +
                        '<span class="product-list-price">' + currencySymbol + parseFloat(p.price).toFixed(2) + '</span>' +
                        '<span class="product-list-stock ' + stockClass + '">' + stockText + '</span>' +
                        '</div>';
            });
            $('#productsList').html(html);
        }

        // Category Filter
        $('#categoryFilter').on('change', function() {
            currentCategory = $(this).val();
            loadProducts();
        });

        // Search
        var searchTimeout;
        $('#searchProduct').on('input', function() {
            clearTimeout(searchTimeout);
            searchTerm = $(this).val();
            searchTimeout = setTimeout(function() {
                renderProducts();
                resetQtyPanel();
            }, 300);
        });

        // Quick Search
        var quickSearchTimeout;
        $('#quickSearchProduct').on('input', function() {
            var searchTerm = $(this).val().trim();
            clearTimeout(quickSearchTimeout);
            if (searchTerm.length < 1) {
                $('#quickSearchDropdown').hide();
                return;
            }
            quickSearchTimeout = setTimeout(function() {
                loadProductsForQuickSearch(searchTerm, function() {
                    filterAndShowDropdown(searchTerm);
                });
            }, 250);
        });

        function filterAndShowDropdown(searchTerm) {
            var normalizedSearchTerm = (searchTerm || '').toString().toLowerCase();
            var filtered = products.filter(function(p) {
                var itemName = ((p && p.item_name) || '').toString().toLowerCase();
                var itemCode = ((p && p.item_code) || '').toString().toLowerCase();

                return itemName.includes(normalizedSearchTerm) ||
                       itemCode.includes(normalizedSearchTerm);
            });
            if (filtered.length > 0) {
                var html = '';
                filtered.slice(0, 10).forEach(function(p) { // Limit to 10
                    html += '<div class="dropdown-item" data-id="' + p.item_id + '" data-name="' + escapeHtml(p.item_name) + '" data-code="' + p.item_code + '" data-price="' + p.price + '" data-stock="' + (p.stock_qty || 0) + '" data-gst="' + (p.gst_code || '') + '" data-group="' + escapeHtml(p.item_group || '') + '" style="padding: 8px 10px; cursor: pointer; border-bottom: 1px solid #eee;" onmouseover="this.style.backgroundColor=\'#f5f5f5\'" onmouseout="this.style.backgroundColor=\'white\'">' +
                            '<strong>' + escapeHtml(p.item_name) + '</strong> (' + p.item_code + ') - ' + currencySymbol + parseFloat(p.price).toFixed(2) +
                            '</div>';
                });
                $('#quickSearchDropdown').html(html).show();
            } else {
                $('#quickSearchDropdown').hide();
            }
        }

        // Hide quick-search toolbar dropdown when clicking outside (inline dropdown is handled below)
        // (consolidated into the inline handler block added above)

        // Select from dropdown
        $(document).on('click', '#quickSearchDropdown .dropdown-item', function() {
            var $item = $(this);
            var id = $item.data('id');
            var name = $item.data('name');
            var code = $item.data('code');
            var price = parseFloat($item.data('price'));
            var stock = parseFloat($item.data('stock'));
            var gst = $item.data('gst') || '';
            var group = $item.data('group') || '';

            // Add to cart
            var qty = 1;
            var existing = cart.find(function(i) { return i.id == id; });

            if (existing) {
                existing.qty = existing.qty + qty;
                existing.gst_code = gst || existing.gst_code || '';
                existing.item_group = group || existing.item_group || '';
                if (!isGiftItem(existing)) {
                    existing.discount = getActiveCustomerLineDiscountPercent();
                }
                if (!existing.is_cart_item) {
                    existing.is_cart_item = 1;
                }
                showToast(name + ' x' + existing.qty, 'success');
            } else {
                cart.push({
                    id: id,
                    name: name,
                    code: code,
                    price: price,
                    qty: qty,
                    stock: stock,
                    discount: getActiveCustomerLineDiscountPercent(),
                    gst_code: gst,
                    item_group: group,
                    is_gift_item: 0,
                    is_cart_item: 1,
                    is_original: false
                });
                showToast(name + ' added', 'success');
            }

            renderCart();
            $('#quickSearchProduct').val('');
            $('#quickSearchDropdown').hide();
        });

        // Enter key to add first item (quick-search toolbar)
        $('#quickSearchProduct').on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                var $first = $('#quickSearchDropdown .dropdown-item').first();
                if ($first.length) {
                    $first.click();
                }
            }
        });

        // ── BC toolbar buttons ────────────────────────────────────
        // "New Line" — show the inline add row and focus its input
        var showInlineRow = false;
        var editingLineIndex = null;

        $(document).on('click', '#bcBtnNewLine', function() {
            showInlineRow = true;
            editingLineIndex = null;
            renderCart();
            // Always (re)load the product list so the inline search has fresh data
            loadProductsForQuickSearch(function() {});
            var $input = $('#inlineItemNoInput');
            if ($input.length) {
                $input[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                setTimeout(function() { $input.focus(); }, 80);
            }
        });

        // "Delete Line" — remove last selected/focused cart row
        $(document).on('click', '#bcBtnDeleteLine', function() {
            var $focused = $('.cart-item.bc-selected');
            if ($focused.length) {
                var idx = parseInt($focused.data('index'), 10);
                cart.splice(idx, 1);
                renderCart();
                showToast('Line deleted', 'success');
            } else if (cart.length > 0) {
                // fall back: remove last item
                cart.splice(cart.length - 1, 1);
                renderCart();
                showToast('Last line deleted', 'success');
            }
        });

        // Click on a cart row to select it (for Delete Line)
        $(document).on('click', '.cart-item', function() {
            $('.cart-item').removeClass('bc-selected');
            $(this).addClass('bc-selected');
            $('#bcBtnDeleteLine').prop('disabled', false);
        });

        // ── Qty Price Tiers helper ────────────────────────────────
        function getTierPrice(priceTiers, basePrice, qty) {
            if (!priceTiers || priceTiers.length === 0) return basePrice;
            var matched = basePrice;
            // tiers are sorted ascending by min_qty; pick highest that still applies
            priceTiers.forEach(function(t) {
                if (qty >= t.min_qty) { matched = t.unit_price; }
            });
            return matched;
        }

        function getGstRateByCode(gstCode) {
            var code = (gstCode || '').toString().trim();
            if (!code) return 0;

            if (Object.prototype.hasOwnProperty.call(gstRateMap, code)) {
                var mappedRate = parseFloat(gstRateMap[code]);
                return isFinite(mappedRate) ? Math.max(0, mappedRate) : 0;
            }

            var numericMatch = code.match(/([0-9]+(?:\.[0-9]+)?)/);
            if (numericMatch && numericMatch[1]) {
                var parsed = parseFloat(numericMatch[1]);
                return isFinite(parsed) ? Math.max(0, parsed) : 0;
            }

            return 0;
        }

        function formatGstPercentByCode(gstCode) {
            var gstRate = getGstRateByCode(gstCode);
            return gstRate.toFixed(2) + '%';
        }

        function calculateLineNet(item) {
            var price = parseFloat(item && item.price) || 0;
            var qty = parseFloat(item && item.qty) || 0;
            var discount = parseFloat(item && item.discount) || 0;
            discount = Math.min(100, Math.max(0, discount));
            return price * qty * (1 - (discount / 100));
        }

        function calculateLineGst(item) {
            var lineNet = calculateLineNet(item);
            var gstRate = getGstRateByCode(item && item.gst_code);
            return lineNet * (gstRate / 100);
        }

        function calculateLineTotal(item) {
            return calculateLineNet(item) + calculateLineGst(item);
        }

        function parseEditableQty(value) {
            if (value === '' || value === null || typeof value === 'undefined') {
                return null;
            }

            var qty = parseInt(value, 10);
            if (!isFinite(qty) || qty < 1) {
                return null;
            }

            return qty;
        }

        function applyCartItemQuantity(idx, qty) {
            if (typeof cart[idx] === 'undefined') {
                return;
            }

            cart[idx].qty = qty;

            if ((!cart[idx].price_tiers || cart[idx].price_tiers.length === 0) && products.length > 0) {
                var match = products.find(function(p) { return p.item_id == cart[idx].id; });
                if (match && match.price_tiers && match.price_tiers.length > 0) {
                    cart[idx].price_tiers = match.price_tiers;
                    cart[idx].base_price = cart[idx].base_price || match.price;
                }
            }

            if (cart[idx].price_tiers && cart[idx].price_tiers.length > 0) {
                cart[idx].price = getTierPrice(cart[idx].price_tiers, cart[idx].base_price || cart[idx].price, qty);
            }
        }

        function setGiftItemState(item, enabled) {
            if (!item) return;

            if (enabled) {
                if (typeof item.gift_discount_backup === 'undefined') {
                    item.gift_discount_backup = parseFloat(item.discount || 0);
                }
                item.is_gift_item = 1;
                item.discount = 100;
                return;
            }

            var restoredDiscount = (typeof item.gift_discount_backup !== 'undefined') ? parseFloat(item.gift_discount_backup) : 0;
            if (!isFinite(restoredDiscount)) {
                restoredDiscount = 0;
            }

            item.is_gift_item = 0;
            item.discount = Math.min(100, Math.max(0, restoredDiscount));
            delete item.gift_discount_backup;
        }

        // ── Inline add row — selected product state ───────────────
        var inlineSelectedProduct = null;

        function resetInlineRow() {
            inlineSelectedProduct = null;
            editingLineIndex = null;
            $('#inlineItemNoInput').val('').focus();
            $('#inlineItemCodeCell').text('-');
            $('#inlineItemPriceInput').val('').attr('placeholder', '-').prop('readonly', true);
            $('#inlineItemDiscountInput').val('0').prop('readonly', false);
            $('#inlineItemGstCell').text('-');
            $('#inlineItemQtyInput').val(1);
            $('#inlineItemTotalCell').text('-');
            $('#bcInlineCommitBtn').removeClass('visible');
            hideInlineDropdown();
        }

        function prefillInlineEditorFromCartItem(item) {
            if (!item) {
                return;
            }

            var currentQty = Math.max(1, parseInt(item.qty, 10) || 1);
            var currentPrice = parseFloat(item.price || 0);
            var currentDiscount = Math.min(100, Math.max(0, parseFloat(item.discount || 0)));

            inlineSelectedProduct = {
                id: item.id,
                name: item.name,
                code: item.code,
                price: parseFloat(item.base_price || item.price || 0),
                stock: parseFloat(item.stock || 0),
                gst_code: item.gst_code || '',
                item_group: item.item_group || '',
                price_tiers: item.price_tiers || []
            };

            $('#inlineItemNoInput').val(item.name + ' (' + item.code + ')');
            $('#inlineItemCodeCell').text(item.code || '-');
            $('#inlineItemPriceInput').val(currentPrice.toFixed(2)).prop('readonly', false);
            $('#inlineItemDiscountInput').val(currentDiscount.toFixed(2)).prop('readonly', isGiftItem(item));
            $('#inlineItemGstCell').text(formatGstPercentByCode(item.gst_code));
            $('#inlineItemQtyInput').val(currentQty);
            $('#bcInlineCommitBtn').addClass('visible');
            updateInlineTotal();
        }

        function setInlineProduct(p) {
            inlineSelectedProduct = p;
            var qty = parseInt($('#inlineItemQtyInput').val()) || 1;
            var tierPrice = getTierPrice(p.price_tiers, p.price, qty);
            $('#inlineItemNoInput').val(p.name + ' (' + p.code + ')');
            $('#inlineItemCodeCell').text(p.code);
            $('#inlineItemPriceInput').val(tierPrice.toFixed(2)).prop('readonly', false);
            $('#inlineItemGstCell').text(formatGstPercentByCode(p.gst_code));
            updateInlineTotal();
            $('#bcInlineCommitBtn').addClass('visible');
            hideInlineDropdown();
            // Move focus to qty
            setTimeout(function() { $('#inlineItemQtyInput').select(); }, 50);
        }

        function updateInlineTotal() {
            if (!inlineSelectedProduct) return;
            var price = parseFloat($('#inlineItemPriceInput').val()) || 0;
            var qty = parseEditableQty($('#inlineItemQtyInput').val());
            var discount = parseFloat($('#inlineItemDiscountInput').val()) || 0;

            if (qty === null) {
                $('#inlineItemTotalCell').text('-');
                return;
            }

            var gstRate = getGstRateByCode(inlineSelectedProduct.gst_code || '');
            var lineNet = price * qty * (1 - discount / 100);
            var lineTotal = lineNet + (lineNet * gstRate / 100);
            $('#inlineItemTotalCell').text(currencySymbol + lineTotal.toFixed(2));
        }

        function commitInlineLine() {
            if (!inlineSelectedProduct) return;
            var price = parseFloat($('#inlineItemPriceInput').val()) || 0;
            var qty = Math.max(1, parseInt($('#inlineItemQtyInput').val()) || 1);
            var discountInput = parseFloat($('#inlineItemDiscountInput').val());
            var discount = isFinite(discountInput) ? Math.min(100, Math.max(0, discountInput)) : getActiveCustomerLineDiscountPercent();
            var p = inlineSelectedProduct;

            if (editingLineIndex !== null && typeof cart[editingLineIndex] !== 'undefined') {
                var existingLine = cart[editingLineIndex];
                var sameProduct = existingLine.id == p.id;
                var existingIsGift = isGiftItem(existingLine);
                var updatedLine = {
                    id: p.id,
                    name: p.name,
                    code: p.code,
                    price: price,
                    base_price: p.price,
                    price_tiers: p.price_tiers || [],
                    qty: qty,
                    discount: existingIsGift ? 100 : discount,
                    stock: p.stock,
                    gst_code: p.gst_code || '',
                    item_group: p.item_group || '',
                    is_gift_item: existingIsGift ? 1 : 0,
                    is_cart_item: sameProduct ? (existingLine.is_cart_item || 0) : 1,
                    is_original: sameProduct ? (existingLine.is_original === true) : false,
                    so_qty: sameProduct ? existingLine.so_qty : null
                };

                if (sameProduct && typeof existingLine.detail_id !== 'undefined') {
                    updatedLine.detail_id = existingLine.detail_id;
                }

                if (existingIsGift && typeof existingLine.gift_discount_backup !== 'undefined') {
                    updatedLine.gift_discount_backup = existingLine.gift_discount_backup;
                }

                cart[editingLineIndex] = updatedLine;
                editingLineIndex = null;
                inlineSelectedProduct = null;
                showInlineRow = false;
                renderCart();
                hideInlineDropdown();
                showToast(p.name + ' updated', 'success');
                return;
            }

            cart.push({
                id: p.id,
                name: p.name,
                code: p.code,
                price: price,
                base_price: p.price,
                price_tiers: p.price_tiers || [],
                qty: qty,
                discount: discount,
                stock: p.stock,
                gst_code: p.gst_code || '',
                item_group: p.item_group || '',
                is_gift_item: 0,
                is_cart_item: 1,
                is_original: false
            });
            showToast(p.name + ' added', 'success');
            // Keep inline row visible after commit so user can add next line
            inlineSelectedProduct = null;
            renderCart();
            hideInlineDropdown();
            setTimeout(function() { $('#inlineItemNoInput').val('').focus(); }, 50);
        }

        // ── Inline product dropdown (fixed-position) helpers ─────
        var $inlinePdOverlay = null;

        function getOrCreateDropdown() {
            if (!$inlinePdOverlay || !$inlinePdOverlay.length || !$.contains(document, $inlinePdOverlay[0])) {
                // Create once in <body> so it is never clipped by any ancestor
                $('body > #inlineProductDropdownOverlay').remove();
                $inlinePdOverlay = $(
                    '<div id="inlineProductDropdownOverlay" class="inline-product-dropdown">' +
                        '<table class="inline-pd-table">' +
                            '<thead><tr>' +
                                '<th class="sort-col">No. <span class="sort-icon"><i class="fa fa-sort-asc"></i></span></th>' +
                                '<th>Description</th>' +
                                '<th>Unit of Measure</th>' +
                                '<th style="text-align:right">Unit Price</th>' +
                            '</tr></thead>' +
                            '<tbody id="inlinePdBody"></tbody>' +
                        '</table>' +
                        '<div class="inline-pd-footer"><span id="inlinePdCount">0</span> result(s) — press <kbd>Enter</kbd> to select first</div>' +
                    '</div>'
                );
                $('body').append($inlinePdOverlay);
            }
            return $inlinePdOverlay;
        }

        function positionInlineDropdown() {
            var $input = $('#inlineItemNoInput');
            if (!$input.length) return;
            var rect = $input[0].getBoundingClientRect();
            var $dd = getOrCreateDropdown();
            var ddWidth = Math.max(540, rect.width);
            // Flip up if not enough space below
            var spaceBelow = window.innerHeight - rect.bottom;
            var ddHeight = Math.min(360, $dd.outerHeight(true) || 260);
            var top = spaceBelow > ddHeight ? (rect.bottom + 2) : (rect.top - ddHeight - 2);
            // Flip left if overflows viewport
            var left = rect.left;
            if (left + ddWidth > window.innerWidth - 8) {
                left = Math.max(8, window.innerWidth - ddWidth - 8);
            }
            $dd.css({ top: top + 'px', left: left + 'px', width: ddWidth + 'px' });
        }

        function showInlineDropdown() {
            getOrCreateDropdown().addClass('show');
            positionInlineDropdown();
        }

        function hideInlineDropdown() {
            if ($inlinePdOverlay) $inlinePdOverlay.removeClass('show');
        }

        // Reposition on scroll / resize
        $(window).on('scroll resize', function() {
            if ($inlinePdOverlay && $inlinePdOverlay.hasClass('show')) {
                positionInlineDropdown();
            }
        });

        // ── Inline search typing ──────────────────────────────────
        var inlineSearchTimeout;
        var inlineFocusIndex = -1;

        $(document).on('input', '#inlineItemNoInput', function() {
            var term = $(this).val().trim();
            clearTimeout(inlineSearchTimeout);
            inlineFocusIndex = -1;
            inlineSelectedProduct = null;
            $('#bcInlineCommitBtn').removeClass('visible');
            $('#inlineItemCodeCell').text('-');
            $('#inlineItemPriceInput').val('').attr('placeholder', '-').prop('readonly', true);
            $('#inlineItemGstCell').text('-');
            $('#inlineItemTotalCell').text('-');

            if (term.length < 1) {
                hideInlineDropdown();
                return;
            }

            inlineSearchTimeout = setTimeout(function() {
                loadProductsForQuickSearch(term, function() {
                    var normalizedTerm = term.toLowerCase();
                    var filtered = products.filter(function(p) {
                        var itemName = ((p && p.item_name) || '').toString().toLowerCase();
                        var itemCode = ((p && p.item_code) || '').toString().toLowerCase();

                        return itemName.includes(normalizedTerm) || itemCode.includes(normalizedTerm);
                    });

                    var $dd = getOrCreateDropdown();
                    if (filtered.length === 0) {
                        $dd.find('#inlinePdBody').html('<tr><td colspan="4" class="inline-pd-empty">No products found for "' + escapeHtml(term) + '"</td></tr>');
                        $dd.find('#inlinePdCount').text(0);
                    } else {
                        var rows = '';
                        filtered.slice(0, 15).forEach(function(p, i) {
                            var tiersJson = JSON.stringify(p.price_tiers || []).replace(/'/g, '&#39;');
                            rows += '<tr class="inline-pd-row" data-idx="' + i + '"' +
                                ' data-id="' + p.item_id + '"' +
                                ' data-name="' + escapeHtml(p.item_name) + '"' +
                                ' data-code="' + p.item_code + '"' +
                                ' data-price="' + p.price + '"' +
                                ' data-stock="' + (p.stock_qty || 0) + '"' +
                                ' data-gst="' + (p.gst_code || '') + '"' +
                                ' data-group="' + escapeHtml(p.item_group || '') + '"' +
                                ' data-tiers=\'' + tiersJson + '\'>' +
                                '<td>' + escapeHtml(p.item_code) + '</td>' +
                                '<td class="inline-desc-cell">' + escapeHtml(p.item_name) + '</td>' +
                                '<td>' + (p.unit || 'PCS') + '</td>' +
                                '<td style="text-align:right">' + currencySymbol + parseFloat(p.price).toFixed(2) + '</td>' +
                                '</tr>';
                        });
                        $dd.find('#inlinePdBody').html(rows);
                        $dd.find('#inlinePdCount').text(Math.min(filtered.length, 15) + (filtered.length > 15 ? ' of ' + filtered.length : ''));
                    }
                    showInlineDropdown();
                });
            }, 200);
        });

        // Select product from inline dropdown by click (delegate to body since it's appended there)
        $(document).on('click', '#inlineProductDropdownOverlay .inline-pd-row', function() {
            var tiers = [];
            try { tiers = JSON.parse($(this).attr('data-tiers') || '[]'); } catch(e) { tiers = []; }
            setInlineProduct({
                id: $(this).data('id'),
                name: $(this).data('name'),
                code: $(this).data('code'),
                price: parseFloat($(this).data('price')),
                stock: parseFloat($(this).data('stock') || 0),
                gst_code: $(this).data('gst') || '',
                item_group: $(this).data('group') || '',
                price_tiers: tiers
            });
        });

        // Keyboard navigation inside inline input
        $(document).on('keydown', '#inlineItemNoInput', function(e) {
            var $dd = getOrCreateDropdown();
            var $rows = $dd.find('.inline-pd-row');

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (!$rows.length) return;
                inlineFocusIndex = Math.min(inlineFocusIndex + 1, $rows.length - 1);
                $rows.removeClass('focused').eq(inlineFocusIndex).addClass('focused');
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (!$rows.length) return;
                inlineFocusIndex = Math.max(inlineFocusIndex - 1, 0);
                $rows.removeClass('focused').eq(inlineFocusIndex).addClass('focused');
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if ($rows.length) {
                    var $target = inlineFocusIndex >= 0 ? $rows.eq(inlineFocusIndex) : $rows.first();
                    if ($target.length) { $target.click(); }
                }
            } else if (e.key === 'Escape') {
                hideInlineDropdown();
            }
        });

        // Live qty/price change in inline row → apply tier price + update total
        $(document).on('focus click', '#inlineItemQtyInput, .qty-input', function() {
            this.select();
        });

        $(document).on('input', '#inlineItemQtyInput', function() {
            var qty = parseEditableQty($(this).val());

            if (qty !== null && inlineSelectedProduct && inlineSelectedProduct.price_tiers && inlineSelectedProduct.price_tiers.length > 0) {
                var tierPrice = getTierPrice(inlineSelectedProduct.price_tiers, inlineSelectedProduct.price, qty);
                $('#inlineItemPriceInput').val(tierPrice.toFixed(2));
            }
            updateInlineTotal();
        });

        $(document).on('blur change', '#inlineItemQtyInput', function() {
            var qty = parseEditableQty($(this).val());
            if (qty === null) {
                qty = 1;
                $(this).val(qty);
            }

            if (inlineSelectedProduct && inlineSelectedProduct.price_tiers && inlineSelectedProduct.price_tiers.length > 0) {
                var tierPrice = getTierPrice(inlineSelectedProduct.price_tiers, inlineSelectedProduct.price, qty);
                $('#inlineItemPriceInput').val(tierPrice.toFixed(2));
            }

            updateInlineTotal();
        });
        $(document).on('input change', '#inlineItemPriceInput', function() {
            updateInlineTotal();
        });

        // Commit button click
        $(document).on('click', '#bcInlineCommitBtn', function() {
            commitInlineLine();
        });

        // Enter in qty field → commit
        $(document).on('keydown', '#inlineItemQtyInput', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); commitInlineLine(); }
        });

        // Hide dropdowns when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#inlineAddRow').length &&
                !$(e.target).closest('#inlineProductDropdownOverlay').length) {
                hideInlineDropdown();
            }
            if (!$(e.target).closest('.bc-toolbar-search-wrap').length) {
                $('#quickSearchDropdown').hide();
            }
        });

        // Select Product from List
        $(document).on('click', '.product-list-item', function() {
            var $this = $(this);
            var id = $this.data('id');
            var name = $this.data('name');
            var code = $this.data('code');
            var price = parseFloat($this.data('price'));
            var stock = parseFloat($this.data('stock'));
            var gst = $this.data('gst') || '';
            var group = $this.data('group') || '';
            var image = $this.data('image');

            // Highlight selected item
            $('.product-list-item').removeClass('selected');
            $this.addClass('selected');

            // Set selected product
            selectedProduct = { id: id, name: name, code: code, price: price, stock: stock, gst_code: gst, item_group: group, image: image };

            // Update qty panel
            $('#qtyPanelCode').text(code);
            $('#qtyPanelName').text(name);
            $('#qtyPanelPrice').text(currencySymbol + price.toFixed(2));
            $('#qtyPanelStock').text(stock + ' available');

            // Set image
            if (image && image.trim() !== '') {
                $('#qtyPanelImg').html('<img src="' + image + '" alt="' + escapeHtml(name) + '" onerror="this.parentElement.innerHTML=\'<i class=fa fa-cube></i>\'">');
            } else {
                $('#qtyPanelImg').html('<i class="fa fa-cube"></i>');
            }

            // Check if already in cart and set qty
            var existing = cart.find(function(i) { return i.id === id; });
            var defaultQty = existing ? 1 : 1;
            $('#qtyInput').val(defaultQty).attr('max', stock);
            updateQtyPanelTotal();

            // Show qty panel
            $('#qtyPanelEmpty').hide();
            $('#qtyPanelContent').addClass('active');
        });

        // Qty Panel Controls
        $('#qtyMinus').on('click', function() {
            var val = parseInt($('#qtyInput').val()) || 1;
            if (val > 1) {
                $('#qtyInput').val(val - 1);
                updateQtyPanelTotal();
            }
        });

        $('#qtyPlus').on('click', function() {
            if (!selectedProduct) return;
            var val = parseInt($('#qtyInput').val()) || 1;
            $('#qtyInput').val(val + 1);
            updateQtyPanelTotal();
        });

        $('#qtyInput').on('focus click', function() {
            this.select();
        });

        $('#qtyInput').on('input', function() {
            if (!selectedProduct) return;
            updateQtyPanelTotal();
        });

        $('#qtyInput').on('blur change', function() {
            if (!selectedProduct) return;
            var val = parseEditableQty($(this).val());
            if (val === null) {
                val = 1;
            }
            $(this).val(val);
            updateQtyPanelTotal();
        });

        function updateQtyPanelTotal() {
            if (!selectedProduct) return;
            var qty = parseEditableQty($('#qtyInput').val()) || 0;
            var gstRate = getGstRateByCode(selectedProduct.gst_code || '');
            var total = (selectedProduct.price * qty) * (1 + gstRate / 100);
            $('#qtyPanelTotal').text(currencySymbol + total.toFixed(2));
        }

        // Add to Cart Button
        $('#btnAddToCart').on('click', function() {
            if (!selectedProduct) return;
            
            var qty = parseInt($('#qtyInput').val()) || 1;
            var customerLineDiscount = getActiveCustomerLineDiscountPercent();
            var existing = cart.find(function(i) { return i.id === selectedProduct.id; });
            
            if (existing) {
                existing.qty = existing.qty + qty;
                existing.gst_code = selectedProduct.gst_code || existing.gst_code || '';
                existing.item_group = selectedProduct.item_group || existing.item_group || '';
                if (typeof existing.discount === 'undefined' || existing.discount === null || existing.discount === '') {
                    existing.discount = customerLineDiscount;
                }
                // If it was an original item and we're adding more, mark it as modified
                if (!existing.is_cart_item) {
                    existing.is_cart_item = 1;
                }
                showToast(selectedProduct.name + ' x' + existing.qty, 'success');
            } else {
                cart.push({
                    id: selectedProduct.id,
                    name: selectedProduct.name,
                    code: selectedProduct.code,
                    price: selectedProduct.price,
                    qty: qty,
                    discount: customerLineDiscount,
                    stock: selectedProduct.stock,
                    gst_code: selectedProduct.gst_code,
                    item_group: selectedProduct.item_group,
                    is_gift_item: 0,
                    is_cart_item: 1, // Flag as cart item
                    is_original: false // Mark as newly added
                });
                showToast(selectedProduct.name + ' added', 'success');
            }

            renderCart();
            resetQtyPanel();
            // Keep modal open for adding more products
        });

        // Render Cart
        function renderCart() {
            var totalItems = cart.reduce(function(s, i) { return s + i.qty; }, 0);
            $('#cartCountBadge').text(totalItems + ' Item' + (totalItems !== 1 ? 's' : ''));

            normalizeCartLineDiscountsForRender();

            console.log('[Disc%] renderCart snapshot', {
                customerId: selectedCustomerId,
                activeCustomerDiscount: getActiveCustomerLineDiscountPercent(),
                cartDiscounts: cart.map(function(i) {
                    return {
                        id: i.id,
                        name: i.name,
                        discount: i.discount,
                        is_gift_item: i.is_gift_item
                    };
                })
            });

            var canAdd = selectedCustomerId && selectedCustomerId !== '';
            $('#bcBtnNewLine').prop('disabled', !canAdd);
            $('#bcBtnDeleteLine').prop('disabled', cart.length === 0);
            $('#btnAddProduct').prop('disabled', !canAdd);

            // Only show inline row when explicitly requested
            if (!canAdd) {
                showInlineRow = false;
                editingLineIndex = null;
            }

            if (editingLineIndex !== null && (editingLineIndex < 0 || editingLineIndex >= cart.length)) {
                editingLineIndex = null;
            }

            var inlineCommitLabel = editingLineIndex !== null ? '<i class="fa fa-save"></i> Save' : '<i class="fa fa-check"></i> Add';
            var inlineCommitTitle = editingLineIndex !== null ? 'Save line changes' : 'Add to cart';

            // Build inline add row HTML
            var inlineRowHtml =
                '<tr class="inline-add-row" id="inlineAddRow">' +
                '<td class="col-group" style="color:#9ca3af; font-style:italic; font-size:12px;">-</td>' +
                '<td class="inline-search-cell">' +
                    '<input type="text" id="inlineItemNoInput" class="inline-no-input" placeholder="Type No. or name..." autocomplete="off">' +
                '</td>' +
                '<td class="col-code" id="inlineItemCodeCell" style="color:#6b7280; font-size:12px;">-</td>' +
                '<td class="col-price"><input type="number" id="inlineItemPriceInput" class="item-price-input" style="width:80px;" min="0" step="0.01" value="" readonly placeholder="-"></td>' +
                '<td class="col-discount"><input type="number" id="inlineItemDiscountInput" class="inline-discount-input" min="0" max="100" step="0.01" value="' + getActiveCustomerLineDiscountPercent().toFixed(2) + '" placeholder="0"></td>' +
                '<td class="col-gst" id="inlineItemGstCell">-</td>' +
                '<td class="col-so">-</td>' +
                '<td class="col-ordered"><input type="number" id="inlineItemQtyInput" class="inline-qty-input" min="1" value="1"></td>' +
                '<td class="col-total" id="inlineItemTotalCell">-</td>' +
                '<td class="col-actions">' +
                    '<button type="button" class="inline-commit-btn" id="bcInlineCommitBtn" title="' + inlineCommitTitle + '">' + inlineCommitLabel + '</button>' +
                '</td>' +
                '</tr>';

            if (cart.length === 0) {
                $('#cartTableBody').html(
                    '<tr><td colspan="10">' +
                    '<div class="cart-empty">' +
                    '<div class="cart-empty-icon"><i class="fa fa-shopping-basket"></i></div>' +
                    '<h3>Your cart is empty</h3>' +
                    '<p>Select a customer and click <strong>+ New Line</strong> to add products</p>' +
                    '</div></td></tr>' +
                    (canAdd && showInlineRow ? inlineRowHtml : '')
                );
                $('#btnCheckout').prop('disabled', true);
            } else {
                var html = '';
                cart.forEach(function(item, idx) {
                    var isCartItem = item.is_cart_item == 1;
                    var isOriginal = item.is_original === true;
                    var isNew = !isOriginal;

                    var badgeHtml = '';
                    if (isCartItem) {
                        badgeHtml = ' <span class="item-badge cart-badge" title="Added via Cart"><i class="fa fa-shopping-cart"></i></span>';
                    } else if (isOriginal) {
                        badgeHtml = ' <span class="item-badge original-badge" title="Standing Order Item"><i class="fa fa-calendar"></i></span>';
                    }
                    if (isNew) {
                        badgeHtml = ' <span class="item-badge new-badge" title="Newly Added"><i class="fa fa-plus"></i> NEW</span>';
                    }
                    var giftItem = isGiftItem(item);
                    if (giftItem) {
                        badgeHtml += ' <span class="item-badge gift-badge" title="Gift Item"><i class="fa fa-gift"></i> Gift</span>';
                    }

                    var soQty = (item.so_qty !== null && item.so_qty !== undefined) ? item.so_qty : '-';
                    var rowClass = 'cart-item';
                    if (isNew) rowClass += ' new-item';
                    if (isCartItem) rowClass += ' cart-item-flagged';
                    var giftButtonClass = giftItem ? ' active' : '';
                    var giftButtonTitle = giftItem ? 'Remove gift item' : 'Mark as gift item';
                    var rowDiscount = parseFloat(item.discount);
                    if (!isFinite(rowDiscount)) {
                        rowDiscount = 0;
                    }
                    rowDiscount = Math.min(100, Math.max(0, rowDiscount));
                    item.discount = rowDiscount;

                    // Tier badge: show if a tier is active (price differs from base)
                    var tierBadge = '';
                    if (item.price_tiers && item.price_tiers.length > 0) {
                        var tierApplied = getTierPrice(item.price_tiers, item.base_price || item.price, item.qty);
                        if (Math.abs(tierApplied - (item.base_price || item.price)) > 0.0001) {
                            tierBadge = ' <span style="font-size:10px; background:#d1fae5; color:#065f46; border-radius:3px; padding:1px 5px; vertical-align:middle;" title="Qty price break applied"><i class="fa fa-tag"></i> Tier</span>';
                        }
                    }

                    html += '<tr class="' + rowClass + '" data-index="' + idx + '">' +
                            '<td class="col-group">' + escapeHtml(item.item_group || '-') + '</td>' +
                            '<td class="col-item"><a href="javascript:void(0)">' + escapeHtml(item.name) + '</a>' + badgeHtml + '</td>' +
                            '<td class="col-code">' + escapeHtml(item.code) + '</td>' +
                            '<td class="col-price"><input type="number" class="item-price-input" min="0" step="0.01" value="' + item.price.toFixed(2) + '">' + tierBadge + '</td>' +
                            '<td class="col-discount"><span class="discount-label">' + rowDiscount.toFixed(2) + '%</span></td>' +
                            '<td class="col-gst">' + formatGstPercentByCode(item.gst_code) + '</td>' +
                            '<td class="col-so">' + soQty + '</td>' +
                            '<td class="col-ordered"><input type="number" class="qty-input" min="1" value="' + item.qty + '"></td>' +
                            '<td class="col-total">' + currencySymbol + calculateLineTotal(item).toFixed(2) + '</td>' +
                            '<td class="col-actions">' +
                                '<button type="button" class="btn-action btn-gift item-gift-toggle' + giftButtonClass + '" title="' + giftButtonTitle + '"><i class="fa fa-gift"></i></button>' +
                                '<button type="button" class="btn-action item-remove" title="Delete"><i class="fa fa-trash"></i></button>' +
                                '<button type="button" class="btn-action btn-edit" title="Edit"><i class="fa fa-pencil"></i></button>' +
                            '</td>' +
                            '</tr>';

                    if (canAdd && showInlineRow && editingLineIndex === idx) {
                        html += inlineRowHtml;
                    }
                });
                html += (canAdd && showInlineRow && editingLineIndex === null) ? inlineRowHtml : '';
                $('#cartTableBody').html(html);
                $('#btnCheckout').prop('disabled', false);
            }

            if (canAdd && showInlineRow && editingLineIndex !== null && typeof cart[editingLineIndex] !== 'undefined') {
                prefillInlineEditorFromCartItem(cart[editingLineIndex]);
            }

            updateTotals();
        }

        // Qty Controls
        $(document).on('click', '.qty-minus', function() {
            var $item = $(this).closest('.cart-item');
            var idx = $item.data('index');
            var $input = $item.find('.qty-input');
            var cur = parseInt($input.val(), 10) || 1;
            var newVal = Math.max(1, cur - 1);
            $input.val(newVal);
            cart[idx].qty = newVal;
            renderCart();
        });

        $(document).on('click', '.qty-plus', function() {
            var $item = $(this).closest('.cart-item');
            var idx = $item.data('index');
            var $input = $item.find('.qty-input');
            var cur = parseInt($input.val(), 10) || 1;
            $input.val(cur + 1);
            cart[idx].qty = cur + 1;
            renderCart();
        });

        // Remove Item
        $(document).on('click', '.item-remove', function() {
            var idx = $(this).closest('.cart-item').data('index');

            if (editingLineIndex !== null) {
                if (editingLineIndex === idx) {
                    editingLineIndex = null;
                    showInlineRow = false;
                    hideInlineDropdown();
                } else if (idx < editingLineIndex) {
                    editingLineIndex--;
                }
            }

            cart.splice(idx, 1);
            renderCart();
            showToast('Item removed', 'success');
        });

        $(document).on('click', '.btn-edit', function() {
            var idx = $(this).closest('.cart-item').data('index');

            if (editingLineIndex === idx && showInlineRow) {
                editingLineIndex = null;
                showInlineRow = false;
                hideInlineDropdown();
                renderCart();
                return;
            }

            showInlineRow = true;
            editingLineIndex = idx;
            hideInlineDropdown();
            renderCart();
            loadProductsForQuickSearch(function() {});

            setTimeout(function() {
                prefillInlineEditorFromCartItem(cart[idx]);
                $('#inlineItemNoInput').focus().select();
            }, 0);
        });

        // Manual qty input handler
        $(document).on('input', '.qty-input', function() {
            var $item = $(this).closest('.cart-item');
            var idx = $item.data('index');
            var val = parseEditableQty($(this).val());

            if (val === null) {
                return;
            }

            applyCartItemQuantity(idx, val);
            $item.find('.item-price-input').val(cart[idx].price.toFixed(2));
            $item.find('.col-total').text(currencySymbol + calculateLineTotal(cart[idx]).toFixed(2));
            updateTotals();
        });

        $(document).on('blur change', '.qty-input', function() {
            var $item = $(this).closest('.cart-item');
            var idx = $item.data('index');
            var val = parseEditableQty($(this).val());

            if (val === null) {
                val = 1;
                $(this).val(val);
            }

            applyCartItemQuantity(idx, val);
            renderCart();
        });

        // Price input handler
        $(document).on('change blur', '.item-price-input', function() {
            var $item = $(this).closest('.cart-item');
            var idx = $item.data('index');
            var val = parseFloat($(this).val()) || 0;
            if (val < 0) val = 0;
            cart[idx].price = val;
            renderCart();
        });

        // Discount input handler (per-line)
        $(document).on('change blur', '.discount-input', function() {
            var $item = $(this).closest('.cart-item');
            var idx = $item.data('index');
            var val = parseFloat($(this).val()) || 0;
            val = Math.min(100, Math.max(0, val));
            cart[idx].discount = val;
            renderCart();
        });

        $(document).on('click', '.item-gift-toggle', function() {
            var idx = $(this).closest('.cart-item').data('index');
            var item = cart[idx];

            if (!item) {
                return;
            }

            setGiftItemState(item, !isGiftItem(item));
            renderCart();
            showToast(isGiftItem(item) ? 'Gift item enabled' : 'Gift item removed', 'success');
        });

        // Inline discount change — update inline total live
        $(document).on('input change', '#inlineItemDiscountInput', function() {
            updateInlineTotal();
        });

        // Clear Cart
        $('#btnClearCart').on('click', function() {
            if (cart.length > 0 && confirm('Clear all items?')) {
                cart = [];
                showInlineRow = false;
                editingLineIndex = null;
                hideInlineDropdown();
                renderCart();
                showToast('Cart cleared', 'success');
            }
        });

        // Update Totals
        // Holds the active delivery-rule context populated when a shipping address is selected.
        var currentDeliveryRule = null;

        function computeRuleDelivery(subtotal) {
            if (!currentDeliveryRule || !currentDeliveryRule.tiers || currentDeliveryRule.tiers.length === 0) {
                return null;
            }
            // Free delivery if subtotal meets the weekly_avg_free threshold (interpreted per-cart-order).
            if (currentDeliveryRule.weekly_free > 0 && subtotal >= currentDeliveryRule.weekly_free) {
                return 0;
            }
            var match = null;
            for (var i = 0; i < currentDeliveryRule.tiers.length; i++) {
                var t = currentDeliveryRule.tiers[i];
                if (subtotal >= parseFloat(t.invoice_larger_than)) {
                    if (match === null || parseFloat(t.invoice_larger_than) >= parseFloat(match.invoice_larger_than)) {
                        match = t;
                    }
                }
            }
            return match ? parseFloat(match.price) : null;
        }

        function updateTotals() {
            var subtotal = cart.reduce(function(s, i) { return s + calculateLineNet(i); }, 0);
            var itemGstTotal = cart.reduce(function(s, i) { return s + calculateLineGst(i); }, 0);

            // If a delivery rule is in effect, override the input with the rule-calculated charge.
            if (currentDeliveryRule) {
                var ruleFee = computeRuleDelivery(subtotal);
                if (ruleFee !== null) {
                    $('#deliveryCharge').val(ruleFee.toFixed(2));
                }
            }

            var delivery = parseFloat($('#deliveryCharge').val()) || 0;
            if (!isFinite(delivery) || delivery < 0) { showToast('Delivery must be 0 or positive', 'error'); delivery = 0; $('#deliveryCharge').val(0); }
            // If delivery checkbox is unchecked, set delivery to 0 for display
            if (!$('#deliveryCheckbox').is(':checked')) { delivery = 0; }
            if (!canEditOrderDiscount) {
                setOrderDiscountInputValue(lockedOrderDiscountPercent);
            }
            var discountPercentage = parseFloat($('#discountAmount').val()) || 0;
            if (!isFinite(discountPercentage) || discountPercentage < 0 || discountPercentage > 100) { showToast('Discount percentage must be between 0 and 100', 'error'); discountPercentage = 0; setOrderDiscountInputValue(0); }
            var discount = subtotal * discountPercentage / 100;
            var deliveryGstRate = getGstRateByCode('DEL');
            var deliveryGst = delivery * (deliveryGstRate / 100);
            var discountGst = subtotal > 0 ? (itemGstTotal * (discount / subtotal)) : 0;
            var gstTotal = Math.max(0, itemGstTotal + deliveryGst - discountGst);
            var total = Math.max(0, subtotal + delivery + gstTotal - discount);

            // Min cart order warning (rule-driven). Does not block typing; submit guard handles enforcement.
            $('#minCartWarning').remove();
            if (currentDeliveryRule && currentDeliveryRule.min_cart > 0 && subtotal > 0 && subtotal < currentDeliveryRule.min_cart) {
                $('#grandTotal').after('<div id="minCartWarning" style="color:#b71c1c;font-size:12px;margin-top:6px;">Minimum cart for delivery is ' + currencySymbol + currentDeliveryRule.min_cart.toFixed(2) + '. Add ' + currencySymbol + (currentDeliveryRule.min_cart - subtotal).toFixed(2) + ' more.</div>');
            }

            // Total pieces
            var totalPieces = cart.reduce(function(s, i) { return s + i.qty; }, 0);

            $('#subtotal').text(currencySymbol + subtotal.toFixed(2));
            $('#deliveryDisplay').text(currencySymbol + delivery.toFixed(2));
            $('#discountDisplay').text('-' + currencySymbol + discount.toFixed(2));
            $('#gstDisplay').text(currencySymbol + gstTotal.toFixed(2));
            $('#grandTotal').text(currencySymbol + total.toFixed(2));
            $('#totalPieces').text(totalPieces);

            // Update hidden fields
            $('#orderDateHidden').val($('#orderDate').val());
            $('#customerIdHidden').val(selectedCustomerId);
            $('#shippingIdHidden').val(selectedShippingId);
            // Sync inline textarea (if present) into hidden delivery address field so server receives it
            var inlineAddr = ($('#shippingAddressTextarea').length ? $('#shippingAddressTextarea').val().trim() : '');
            if (inlineAddr) {
                $('#deliveryAddressText').val(inlineAddr);
            }
            $('#paymentMethodHidden').val($('#paymentMethod').val());
            $('#cartDataHidden').val(JSON.stringify(cart));
            $('#subtotalHidden').val(subtotal.toFixed(2));
            $('#deliveryHidden').val(delivery.toFixed(2));
            $('#discountHidden').val(discountPercentage.toFixed(2));
            $('#purchaseOrderHidden').val(($('#purchaseOrderNumber').val() || '').trim());
            $('#grandTotalHidden').val(total.toFixed(2));
        }

        $('#deliveryCharge').on('input', updateTotals);
        $('#discountAmount').on('input', function() {
            if (!canEditOrderDiscount) {
                setOrderDiscountInputValue(lockedOrderDiscountPercent);
                return;
            }
            updateTotals();
        });
        $('#deliveryCheckbox').on('change', updateTotals);
        $('#purchaseOrderNumber').on('input change', updateTotals);
        $('#orderDate, #paymentMethod').on('change', updateTotals);

        // Form Validation
        $('#checkoutForm').on('submit', function(e) {
            // ensure inline textarea value is synced into the hidden field before validation
            var inlineAddr = ($('#shippingAddressTextarea').length ? $('#shippingAddressTextarea').val().trim() : '');
            if (inlineAddr) { $('#deliveryAddressText').val(inlineAddr); }

            if (cart.length === 0) {
                e.preventDefault();
                showToast('Add items to cart', 'error');
                return false;
            }
            // Enforce minimum cart order from active delivery rule.
            if (currentDeliveryRule && currentDeliveryRule.min_cart > 0) {
                var subForGuard = cart.reduce(function(s, i) { return s + (i.price * i.qty * (1 - ((i.discount || 0) / 100))); }, 0);
                if (subForGuard < currentDeliveryRule.min_cart) {
                    e.preventDefault();
                    showToast('Minimum cart order for delivery is ' + currencySymbol + currentDeliveryRule.min_cart.toFixed(2), 'error');
                    return false;
                }
            }
            if (!selectedCustomerId) {
                e.preventDefault();
                showToast('Select a customer', 'error');
                return false;
            }
            // allow either a saved shipping address OR an inline custom delivery address
            if (!selectedShippingId && !($('#deliveryAddressText').val() || '').toString().trim()) {
                e.preventDefault();
                showToast('Select a shipping address or enter a custom delivery address', 'error');
                return false;
            }
            if (!$('#paymentMethod').val()) {
                e.preventDefault();
                showToast('Select payment method', 'error');
                return false;
            }
            if (!$('#orderDate').val()) {
                e.preventDefault();
                showToast('Select order date', 'error');
                return false;
            }
            var deliveryVal = parseFloat($('#deliveryCharge').val()) || 0;
            if (!isFinite(deliveryVal) || deliveryVal < 0) {
                e.preventDefault();
                showToast('Invalid delivery amount', 'error');
                return false;            }
            updateTotals();
        });

        // Escape HTML
        function escapeHtml(text) {
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(text));
            return div.innerHTML;
        }

        // Initialize
        renderCart();
    });
    </script>
</body>
</html>
