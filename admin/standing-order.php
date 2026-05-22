<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');
include('include/customer_access.php');

$db = new Database();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    try {
        if ($_POST['action'] === 'get_product_availability') {
            $productId = $_POST['product_id'] ?? null;
            if (!$productId) {
                echo json_encode(['mon' => 1, 'tue' => 1, 'wed' => 1, 'thu' => 1, 'fri' => 1, 'sat' => 1, 'sun' => 1]);
                exit;
            }
            $availability = getProductAvailability($productId);
            echo json_encode($availability);
            exit;
        } elseif ($_POST['action'] === 'get_customer_shipping_addresses') {
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
        } elseif ($_POST['action'] === 'get_shipping_address_details') {
            $shippingAddressId = $_POST['shipping_address_id'] ?? null;
            if (!$shippingAddressId) {
                echo json_encode(['error' => 'Shipping address ID required']);
                exit;
            }
            $db = new Database();
            $address = $db->getRow('SELECT csa.*, COALESCE(sa.mon, 1) as mon, COALESCE(sa.tue, 1) as tue, COALESCE(sa.wed, 1) as wed, COALESCE(sa.thu, 1) as thu, COALESCE(sa.fri, 1) as fri, COALESCE(sa.sat, 1) as sat, COALESCE(sa.sun, 1) as sun, drm.route_name, drm.amount as route_amount
                                   FROM customer_shipping_address csa
                                   LEFT JOIN shipping_address_availability sa ON csa.id = sa.shipping_address_id
                                   LEFT JOIN delivery_route_master drm ON csa.delivery_route_id = drm.id
                                   WHERE csa.id = ? LIMIT 1', [$shippingAddressId]);
            if (!$address) {
                echo json_encode(['error' => 'Shipping address not found']);
                exit;
            }
            // Attach per-day route amounts from shipping_address_day_route
            $dayRouteRow = $db->getRow('SELECT
                sdr.mon_route_id, sdr.tue_route_id, sdr.wed_route_id, sdr.thu_route_id, sdr.fri_route_id, sdr.sat_route_id, sdr.sun_route_id,
                rm_mon.route_name AS mon_route_name, rm_mon.amount AS mon_route_amount,
                rm_tue.route_name AS tue_route_name, rm_tue.amount AS tue_route_amount,
                rm_wed.route_name AS wed_route_name, rm_wed.amount AS wed_route_amount,
                rm_thu.route_name AS thu_route_name, rm_thu.amount AS thu_route_amount,
                rm_fri.route_name AS fri_route_name, rm_fri.amount AS fri_route_amount,
                rm_sat.route_name AS sat_route_name, rm_sat.amount AS sat_route_amount,
                rm_sun.route_name AS sun_route_name, rm_sun.amount AS sun_route_amount
                FROM shipping_address_day_route sdr
                LEFT JOIN delivery_route_master rm_mon ON rm_mon.id = sdr.mon_route_id
                LEFT JOIN delivery_route_master rm_tue ON rm_tue.id = sdr.tue_route_id
                LEFT JOIN delivery_route_master rm_wed ON rm_wed.id = sdr.wed_route_id
                LEFT JOIN delivery_route_master rm_thu ON rm_thu.id = sdr.thu_route_id
                LEFT JOIN delivery_route_master rm_fri ON rm_fri.id = sdr.fri_route_id
                LEFT JOIN delivery_route_master rm_sat ON rm_sat.id = sdr.sat_route_id
                LEFT JOIN delivery_route_master rm_sun ON rm_sun.id = sdr.sun_route_id
                WHERE sdr.shipping_address_id = ? LIMIT 1', [(int)$shippingAddressId]);
            $address['day_routes'] = $dayRouteRow ?: null;
            echo json_encode($address);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

$db = new Database();

// Currency config (used across UI)
try {
    $currencyRow = $db->getRow('SELECT * FROM currency WHERE activated = ? LIMIT 1',["Y"]);
    $CURRENCY_SYMBOL = isset($currencyRow['currency']) ? $currencyRow['currency'] : 'AUD';
    $CURRENCY_RATE = isset($currencyRow['rate']) ? (float)$currencyRow['rate'] : 1.0;
} catch(Exception $e){
    $CURRENCY_SYMBOL = 'AUD';
    $CURRENCY_RATE = 1.0;
}

function getCustomers() {
    try {
        $db = new Database();
        return getOrderEligibleCustomers($db);
    } catch (Exception $e) {
        return [];
    }
}

function getCategories() {
    try {
        $db = new Database();
        return $db->getRows('SELECT category_id, category_name FROM category_master ORDER BY category_name ASC');
    } catch (Exception $e) {
        return [];
    }
}

function getItemsByCategory($categoryId) {
    try {
        $db = new Database();
        $hasAllowInSalesColumn = (bool) $db->getRow("SHOW COLUMNS FROM item_master LIKE 'allow_in_sales'");
        $query = 'SELECT item_id, item_name, item_normal_selling_price, gst_vat_code FROM item_master WHERE item_category = ? AND item_active = "Y"';
        if ($hasAllowInSalesColumn) {
            $query .= ' AND (allow_in_sales = 1 OR allow_in_sales IS NULL)';
        }
        $query .= ' ORDER BY item_name ASC';
        return $db->getRows($query, [$categoryId]);
    } catch (Exception $e) {
        return [];
    }
} 

function getGstRateMap() {
    $db = new Database();
    $rates = [];

    try {
        $hasDstCodeTable = (bool) $db->getRow("SHOW TABLES LIKE 'DST_Code'");
        if ($hasDstCodeTable) {
            $rows = $db->getRows('SELECT Code, GSTPercentage FROM DST_Code');
            foreach ($rows as $row) {
                $code = trim((string)($row['Code'] ?? ''));
                if ($code === '') {
                    continue;
                }
                $rates[$code] = (float)($row['GSTPercentage'] ?? 0);
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
                $code = trim((string)($row['code'] ?? ''));
                if ($code === '' || isset($rates[$code])) {
                    continue;
                }
                $rates[$code] = (float)($row['rate'] ?? 0);
            }
        }
    } catch (Exception $e) {
        // ignore fallback errors
    }

    return $rates;
}

function resolveGstRateFromCode($code, array $rateMap) {
    $code = trim((string)$code);
    if ($code === '') {
        return 0.0;
    }
    if (isset($rateMap[$code])) {
        return max(0.0, (float)$rateMap[$code]);
    }
    if (preg_match('/([0-9]+(?:\.[0-9]+)?)/', $code, $m)) {
        return max(0.0, (float)($m[1] ?? 0));
    }
    return 0.0;
}

function getRepeatUnits() {
    try {
        $db = new Database();
        return $db->getRows('SELECT id, name, display_name FROM repeat_units ORDER BY id ASC');
    } catch (Exception $e) {
        return [];
    }
}

function getCustomerShippingAddresses($customerId) {
    try {
        $db = new Database();
        if (getCustomerOrderEligibilityError($db, $customerId) !== null) {
            return [];
        }
        return $db->getRows('SELECT id, is_default, address_label, address_line_1, address_line_2, city, state, country, postal_code, contact_no, contact_person_name, contact_person_phone, contact_person_email, remarks, delivery_time_from, delivery_time_till, has_door_key, has_shop_alarm, delivery_route_id, attribute_1, attribute_2, attribute_3 FROM customer_shipping_address WHERE customer_id = ? ORDER BY is_default DESC, id ASC', [$customerId]);
    } catch (Exception $e) {
        return [];
    }
}

function getShippingAddressAvailability($shippingAddressId) {
    try {
        $db = new Database();
        $row = $db->getRow('SELECT id, mon, tue, wed, thu, fri, sat, sun FROM shipping_address_availability WHERE shipping_address_id = ? LIMIT 1', [$shippingAddressId]);
        return $row ?: ['mon' => 1, 'tue' => 1, 'wed' => 1, 'thu' => 1, 'fri' => 1, 'sat' => 1, 'sun' => 1];
    } catch (Exception $e) {
        return ['mon' => 1, 'tue' => 1, 'wed' => 1, 'thu' => 1, 'fri' => 1, 'sat' => 1, 'sun' => 1];
    }
}

function getDeliveryRoutes() {
    try {
        $db = new Database();
        return $db->getRows('SELECT id, route_name FROM delivery_route_master WHERE is_active = 1 ORDER BY route_name ASC');
    } catch (Exception $e) {
        return [];
    }
}

function getProductAvailability($productId) {
    try {
        $db = new Database();
        $row = $db->getRow('SELECT mon, tue, wed, thu, fri, sat, sun FROM product_availability WHERE product_id = ? LIMIT 1', [$productId]);
        return $row ?: ['mon' => 1, 'tue' => 1, 'wed' => 1, 'thu' => 1, 'fri' => 1, 'sat' => 1, 'sun' => 1];
    } catch (Exception $e) {
        return ['mon' => 1, 'tue' => 1, 'wed' => 1, 'thu' => 1, 'fri' => 1, 'sat' => 1, 'sun' => 1];
    }
}

$customers = getCustomers();
$categories = getCategories();
$gstRateMap = getGstRateMap();

// Fetch Delivery GST rate from DST_Code where Code = 'DEL'
$deliveryGstRate = 0;
try {
    $db = new Database();
    $hasDstCodeTable = (bool) $db->getRow("SHOW TABLES LIKE 'DST_Code'");
    if ($hasDstCodeTable) {
        $delRow = $db->getRow("SELECT GSTPercentage FROM DST_Code WHERE Code = 'DEL' LIMIT 1");
        $deliveryGstRate = $delRow ? (float)($delRow['GSTPercentage'] ?? 0) : 0;
    }
} catch (Exception $e) {
    $deliveryGstRate = 0;
}

// CSRF for standing-order delete
if (empty($_SESSION['delete_so_csrf'])) {
    $_SESSION['delete_so_csrf'] = bin2hex(random_bytes(32));
}
$deleteSoCsrf = $_SESSION['delete_so_csrf'];

// CSRF for standing-order copy
if (empty($_SESSION['copy_so_csrf'])) {
    $_SESSION['copy_so_csrf'] = bin2hex(random_bytes(32));
}
$copySoCsrf = $_SESSION['copy_so_csrf'];

// Load all active standing orders for the management table
function getAllActiveStandingOrders() {
    try {
        $db = new Database();
        return $db->getRows(
            "SELECT so.id, so.customer_id, so.date_from, so.date_to, so.active,
                    c.customer_name,
                    csa.address_label,
                    (SELECT COUNT(*) FROM standing_order_item WHERE standing_order_id = so.id) AS item_count,
                    (SELECT COUNT(*) FROM invoice_hedder
                     WHERE invoice_h_customer_id = so.customer_id
                       AND invoice_h_order_note = 'Standing Order'
                       AND invoice_h_delivery_date > CURDATE()) AS pending_future_count
             FROM standing_order so
             JOIN customer c ON c.customer_id = so.customer_id
             LEFT JOIN customer_shipping_address csa ON csa.id = so.shipping_address_id
             WHERE so.active = 1
             ORDER BY c.customer_name ASC"
        );
    } catch (Exception $e) {
        return [];
    }
}

$allActiveStandingOrders = getAllActiveStandingOrders();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Standing Orders</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
    <!-- Select2 for searchable dropdowns -->
    <link rel="stylesheet" href="assets/global/plugins/select2/css/select2.min.css"/>
    <link rel="stylesheet" href="assets/global/plugins/select2/css/select2-bootstrap.min.css"/>
    <style>
        .so-toolbar { display:flex; align-items:center; gap:10px; }
        .so-toolbar .deadline { color:#b30000; font-weight:700; text-align:center; margin-left:auto; }
        .so-toolbar .form-control { border-radius:4px; border:1px solid #ccc; }
        .so-toolbar .select2-container { min-width:200px; }
        @media(max-width:768px){ .so-toolbar .select2-container { min-width:150px; } .so-toolbar { flex-wrap:wrap; } .so-toolbar .deadline { margin-left:0; width:100%; text-align:left; margin-top:5px; } }
        .so-toolbar .deadline { color:#b30000; font-weight:700; text-align:center; width:100%; }
        .so-day { width:56px; text-align:center; }
        .so-table th,.so-table td{ vertical-align:middle!important; }
        .so-table tbody tr:hover{ background:#fcfcfd; }
        .so-sticky-actions{ position:sticky; top:0; background:#fff; z-index:5; padding:12px 0; border-bottom:1px solid #e6e8eb; }
        .so-total-badge{ font-weight:700; }
        .so-group-title{ font-weight:700; padding:12px; background:#f8f9fb; border-left:4px solid #5b9bd5; }
    /* Refined compact qty input design */
    .so-qty-input, .so-day-input{ width:56px; height:34px; padding:4px 10px; border:1px solid #ced4da; border-radius:6px; background:#fff; font-weight:600; color:#243240; text-align:center; line-height:24px; transition:border-color .15s, box-shadow .15s; }
    .so-quick-add .so-qty-input{ margin-right:6px; }
    .so-qty-input:focus, .so-day-input:focus{ outline:none; border-color:#4a89c7; box-shadow:0 0 0 2px rgba(74,137,199,.25); }
    .so-qty-input:hover, .so-day-input:hover{ border-color:#4a89c7; }
    /* Remove number spinners for cleaner look */
    input[type=number].so-qty-input::-webkit-inner-spin-button,
    input[type=number].so-qty-input::-webkit-outer-spin-button,
    input[type=number].so-day-input::-webkit-inner-spin-button,
    input[type=number].so-day-input::-webkit-outer-spin-button{ -webkit-appearance: none; margin:0; }
    input[type=number].so-qty-input,
    input[type=number].so-day-input{ -moz-appearance:textfield; appearance:textfield; }
    /* Compact on very small screens */
    @media(max-width:600px){ .so-qty-input, .so-day-input{ width:48px; height:32px; padding:3px 6px; font-size:11px; } }
        .so-right{ text-align:right; }
        .so-muted{ color:#666; font-size:12px; }
        .so-quick-add .form-control{ min-width:56px; }
        .so-quick-add .so-qty-input{ width:58px; }
        .so-summary{ font-weight:700; }
        .so-remove{ color:#c9302c; cursor:pointer; }
        .so-header-chip{ display:inline-block; background:#eef3ff; color:#3553a4; padding:4px 8px; border-radius:12px; font-size:12px; }
        @media(max-width:1200px){ .so-day,.so-qty-input{ width:44px; } }
        .so-delivery { text-align:right; margin-top:8px; font-weight:700; }
        .so-disabled { opacity:0.5; pointer-events:none; }
        .so-no-customer { text-align:center; padding:40px; color:#666; font-style:italic; }
        .so-address-placeholder{ padding:25px; text-align:center; color:#7a7a7a; border:1px dashed #d5d8dc; background:#fcfcfc; border-radius:8px; margin-bottom:10px; }
        .so-address-placeholder i{ font-size:36px; display:block; margin-bottom:8px; color:#9aa3b4; }
        /* Hide legacy repeat-interval controls when explicit From/To dates are used */
        #so-repeat-controls{ display: none !important; }
        /* Standing order management table */
        .so-mgmt-table { width:100%; border-collapse:collapse; }
        .so-mgmt-table th { background:#5b9bd5; color:#fff; padding:9px 12px; font-size:12px; font-weight:700; text-align:left; }
        .so-mgmt-table td { border-bottom:1px solid #e6eaf0; padding:9px 12px; vertical-align:middle; font-size:13px; }
        .so-mgmt-table tbody tr:hover td { background:#f6fbff; }
        .so-mgmt-filters { margin-bottom:16px; padding:14px 12px 4px; border:1px solid #e6eaf0; border-radius:6px; background:#f8fafc; }
        .so-mgmt-filters label { display:block; font-size:12px; font-weight:700; color:#4b5b6b; margin-bottom:6px; }
        .so-mgmt-filters .form-control { height:34px; }
        .so-mgmt-filters .select2-container { width:100% !important; }
        .so-mgmt-filters .btn { margin-top:24px; }
        .so-mgmt-table-wrap .dataTables_wrapper .dataTables_length,
        .so-mgmt-table-wrap .dataTables_wrapper .dataTables_filter { margin-bottom:12px; }
        .so-mgmt-table-wrap .dataTables_wrapper .dataTables_filter input { margin-left:6px; }
        .so-mgmt-table-wrap .dataTables_wrapper .dataTables_length select { margin:0 6px; }
        @media(max-width:768px){ .so-mgmt-filters .btn { margin-top:10px; } }
        .btn-so-delete { background:#e74c3c; color:#fff; border:none; border-radius:4px; padding:5px 12px; font-size:12px; cursor:pointer; }
        .btn-so-delete:hover { background:#c0392b; }
        .btn-so-copy { background:#27ae60; color:#fff; border:none; border-radius:4px; padding:5px 12px; font-size:12px; cursor:pointer; margin-right:4px; }
        .btn-so-copy:hover { background:#1e8449; }
        .btn-so-edit { background:#3498db; color:#fff; border:none; border-radius:4px; padding:5px 12px; font-size:12px; cursor:pointer; margin-right:4px; }
        .btn-so-edit:hover { background:#2980b9; }
    </style>
</head>
<body class="page-sidebar-closed-hide-logo page-content-white page-sidebar-closed">
<?php include('common/manubar.php'); ?>
<div class="clearfix"></div>
<div class="page-container">
    <div class="page-sidebar-wrapper">
        <?php include('common/sidebar.php'); ?>
    </div>
    <div class="page-content-wrapper">
        <div class="page-content">
            <div class="page-bar">
                <ul class="page-breadcrumb">
                    <li><a href="#">Orders</a> <i class="fa fa-circle"></i></li>
                    <li><span>Standing Orders</span></li>
                </ul>
            </div>

            <div class="portlet light">
                <div class="portlet-title so-sticky-actions">
                    <div class="caption so-toolbar">
                        <span class="caption-subject bold uppercase">Standing Order for:</span>
                        <select id="so-customer" class="form-control input-sm select2" style="max-width:360px;" data-placeholder="Search customer...">
                            <option value=""></option>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?php echo (int)$c['customer_id']; ?>"><?php echo htmlspecialchars($c['customer_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select id="so-shipping-address" class="form-control input-sm select2" style="max-width:360px;" data-placeholder="Select shipping address...">
                            <option value=""></option>
                        </select>
                        <div class="deadline hidden-xs"><span class="so-header-chip">GFP deadline 12:30</span> &nbsp; <span class="so-header-chip">Strada deadline 16:00</span></div>
                    </div>
                    <div class="actions">
                        <a href="javascript:void(0)" id="so-save" class="btn btn-circle btn-success"><i class="fa fa-check"></i> Save</a>
                        <a href="javascript:void(0)" id="so-cancel" class="btn btn-circle btn-default">Cancel</a>
                    </div>
                </div>
                <div class="portlet-body">
                    <div id="so-no-customer-msg" class="so-no-customer">
                        <i class="fa fa-user" style="font-size:48px; margin-bottom:15px; display:block;"></i>
                        Please select a customer to start creating a standing order.
                    </div>
                    <div id="so-no-address-msg" class="so-no-customer" style="display:none;">
                        <i class="fa fa-map-marker" style="font-size:48px; margin-bottom:15px; display:block;"></i>
                        Please select a shipping address to continue with the standing order.
                    </div>

                    <!-- Shipping Address Details -->
                    <div id="so-address-details" class="row" style="margin-bottom:10px;">
                        <div class="col-md-12">
                            <div class="portlet light">
                                <div class="portlet-title">
                                    <div class="caption">
                                        <i class="fa fa-map-marker"></i>
                                        <span class="caption-subject bold uppercase">Shipping Address Details</span>
                                        <span id="default-badge" class="badge badge-success" style="display:none; margin-left:10px;">DEFAULT</span>
                                    </div>
                                </div>
                                <div class="portlet-body">
                                    <div id="address-details-placeholder" class="so-address-placeholder">
                                        <i id="address-details-placeholder-icon" class="fa fa-info-circle"></i>
                                        <p id="address-details-placeholder-text" style="margin:0;">Select a customer to load shipping addresses.</p>
                                    </div>
                                    <div id="address-details-content" style="display:none;">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h5 id="address-label" style="margin:0 0 5px 0;"></h5>
                                                <p id="address-full" style="margin:0 0 5px 0; font-size:12px;"></p>
                                                <small><i class="fa fa-user"></i> <span id="contact-person"></span> | <i class="fa fa-phone"></i> <span id="contact-phone"></span> | <i class="fa fa-envelope"></i> <span id="contact-email"></span></small>
                                            </div>
                                            <div class="col-md-6 text-right">
                                                <small>
                                                    <i class="fa fa-calendar"></i> <span id="delivery-availability"></span><br>
                                                    <i class="fa fa-clock-o"></i> <span id="delivery-time"></span><br>
                                                    <i class="fa fa-shield"></i> <span id="security-features"></span> | <i class="fa fa-truck"></i> <span id="delivery-route"></span><br>
                                                    <i class="fa fa-comment"></i> <span id="address-remarks"></span>
                                                </small>
                                                <div id="address-attributes" style="margin-top:5px; opacity:0.7;">
                                                    <small id="attribute-1" style="display:inline-block; margin-right:10px;"></small>
                                                    <small id="attribute-2" style="display:inline-block; margin-right:10px;"></small>
                                                    <small id="attribute-3" style="display:inline-block;"></small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="so-cart-section" class="so-disabled">

                        <div class="row so-quick-add so-quick-add-box" style="margin-bottom:15px;">
                        <div class="col-md-4">
                            <label>Item</label>
                            <select id="qa-item" class="form-control input-sm select2" data-placeholder="Search product...">
                                <option value=""></option>
                                <?php foreach ($categories as $cat): $items = getItemsByCategory($cat['category_id']); ?>
                                    <?php if (count($items)>0): ?>
                                        <optgroup label="<?php echo htmlspecialchars($cat['category_name']); ?>">
                                            <?php foreach ($items as $it): $unitConv = (float)$it['item_normal_selling_price'] * $CURRENCY_RATE; ?>
                                                <?php
                                                    $gstCode = trim((string)($it['gst_vat_code'] ?? ''));
                                                    $gstRate = resolveGstRateFromCode($gstCode, $gstRateMap);
                                                ?>
                                                <option data-price="<?php echo $unitConv; ?>" data-gst-code="<?php echo htmlspecialchars($gstCode, ENT_QUOTES, 'UTF-8'); ?>" data-gst-rate="<?php echo $gstRate; ?>" value="<?php echo (int)$it['item_id']; ?>">
                                                    <?php echo htmlspecialchars($it['item_name']); ?> (<?php echo htmlspecialchars($CURRENCY_SYMBOL); ?> <?php echo number_format($unitConv,2); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <?php
                                    // Include uncategorized items
                                    $uncatDb = new Database();
                                    $uncatHasAllowInSalesColumn = (bool) $uncatDb->getRow("SHOW COLUMNS FROM item_master LIKE 'allow_in_sales'");
                                    $uncatQuery = 'SELECT item_id, item_name, item_normal_selling_price, gst_vat_code FROM item_master WHERE (item_category IS NULL OR item_category = 0) AND item_active = "Y"';
                                    if ($uncatHasAllowInSalesColumn) {
                                        $uncatQuery .= ' AND (allow_in_sales = 1 OR allow_in_sales IS NULL)';
                                    }
                                    $uncatQuery .= ' ORDER BY item_name ASC';
                                    $uncatItems = $uncatDb->getRows($uncatQuery);
                                    if (!empty($uncatItems)):
                                ?>
                                    <optgroup label="Uncategorized">
                                        <?php foreach ($uncatItems as $it): $unitConv = (float)$it['item_normal_selling_price'] * $CURRENCY_RATE; ?>
                                            <?php
                                                $gstCode = trim((string)($it['gst_vat_code'] ?? ''));
                                                $gstRate = resolveGstRateFromCode($gstCode, $gstRateMap);
                                            ?>
                                            <option data-price="<?php echo $unitConv; ?>" data-gst-code="<?php echo htmlspecialchars($gstCode, ENT_QUOTES, 'UTF-8'); ?>" data-gst-rate="<?php echo $gstRate; ?>" value="<?php echo (int)$it['item_id']; ?>">
                                                <?php echo htmlspecialchars($it['item_name']); ?> (<?php echo htmlspecialchars($CURRENCY_SYMBOL); ?> <?php echo number_format($unitConv,2); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label>Qty per day</label>
                            <div class="form-inline">
                                <?php $days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun']; foreach($days as $d): ?>
                                    <input type="number" min="0" class="form-control input-sm so-qty-input qa-day" placeholder="<?php echo $d; ?>" />
                                <?php endforeach; ?>
                                <a id="qa-add" class="btn btn-warning btn-sm" style="margin-left:8px;"><i class="fa fa-plus"></i> Add item</a>
                            </div>
                            <div class="so-muted">Cost per day: <span id="qa-cost-per-day">$0.00</span> &nbsp; Total this week: <span id="qa-total-week" class="so-total-badge">$0.00</span></div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped so-table" id="so-table">
                            <thead>
                                <tr>
                                    <th style="width:40%">Item</th>
                                    <th class="text-center">Mon</th>
                                    <th class="text-center">Tue</th>
                                    <th class="text-center">Wed</th>
                                    <th class="text-center">Thu</th>
                                    <th class="text-center">Fri</th>
                                    <th class="text-center">Sat</th>
                                    <th class="text-center">Sun</th>
                                    <th class="text-center">Total</th>
                                    <th class="text-center">GST %</th>
                                    <th class="so-right" style="width:110px;">Cost</th>
                                    <th class="text-center" style="width:40px;">Del</th>
                                </tr>
                            </thead>
                            <tbody id="so-tbody">
                                <!-- Rows added dynamically -->
                            </tbody>
                            <tfoot>
                                <!-- Row 1: Total pieces -->
                                <tr style="background:#f5f7fa;">
                                    <th class="so-right" style="font-size:12px; color:#555;">Total pieces</th>
                                    <?php foreach($days as $d): ?>
                                        <th class="text-center so-col-total" data-day="<?php echo $d; ?>" style="font-weight:700;">0</th>
                                    <?php endforeach; ?>
                                    <th class="text-center so-week-total so-summary" style="font-weight:700;">0</th>
                                    <th class="text-center" style="font-weight:700;">—</th>
                                    <th class="so-right so-grand-cost so-summary" style="font-weight:700;"><?php echo htmlspecialchars($CURRENCY_SYMBOL); ?> 0.00</th>
                                    <th></th>
                                </tr>
                                <!-- Row 2: Cost per day -->
                                <tr style="background:#eef3ff;">
                                    <th class="so-right" style="font-size:12px; color:#3553a4;">Cost per day</th>
                                    <?php foreach($days as $d): ?>
                                        <th class="text-center so-day-cost" style="font-weight:700; color:#3553a4;">—</th>
                                    <?php endforeach; ?>
                                    <th class="text-center so-week-cost so-summary" style="font-weight:700; color:#3553a4;">—</th>
                                    <th class="text-center" style="font-weight:700; color:#3553a4;">—</th>
                                    <th class="so-right so-grand-cost-label so-summary" style="font-weight:700; color:#3553a4;"><?php echo htmlspecialchars($CURRENCY_SYMBOL); ?> 0.00</th>
                                    <th></th>
                                </tr>
                                <!-- Row 3: Delivery -->
                                <tr style="background:#fff8e1;">
                                    <th class="so-right" style="font-size:12px; color:#856404;">Delivery</th>
                                    <?php foreach($days as $d): ?>
                                        <th class="text-center so-delivery-check" style="color:#28a745;">—</th>
                                    <?php endforeach; ?>
                                    <th class="text-center so-delivery-count" style="font-weight:700; color:#856404;">0</th>
                                    <th class="text-center" style="font-weight:700; color:#856404;"><?php echo $deliveryGstRate > 0 ? number_format($deliveryGstRate, 2) . '%' : '—'; ?></th>
                                    <th class="so-right" style="font-weight:700; color:#856404;">
                                        <input type="number" id="so-delivery-input" class="form-control input-sm" style="display:inline-block; width:70px; text-align:right;" min="0" step="0.01" value="3.00">
                                    </th>
                                    <th></th>
                                </tr>
                                <!-- Row 4: Cost per day with delivery -->
                                <tr style="background:#e8f5e9; border-top:2px solid #28a745;">
                                    <th class="so-right" style="font-size:12px; color:#155724; font-weight:700;">Cost per day with delivery</th>
                                    <?php foreach($days as $d): ?>
                                        <th class="text-center so-day-cost-with-delivery" style="font-weight:700; color:#155724;">—</th>
                                    <?php endforeach; ?>
                                    <th class="text-center so-week-cost-with-delivery so-summary" style="font-weight:700; color:#155724;">—</th>
                                    <th class="text-center" style="font-weight:700; color:#155724;">—</th>
                                    <th class="so-right so-total-with-delivery so-summary" style="font-weight:700; color:#155724;"><?php echo htmlspecialchars($CURRENCY_SYMBOL); ?> 0.00</th>
                                    <th></th>
                                </tr>
                                <!-- Row 5: Total GST -->
                                <tr style="background:#fff3cd; border-top:2px solid #ffc107;">
                                    <th class="so-right" style="font-size:12px; color:#856404; font-weight:700;">Total GST</th>
                                    <?php foreach($days as $d): ?>
                                        <th class="text-center so-day-gst" style="font-weight:700; color:#856404;">—</th>
                                    <?php endforeach; ?>
                                    <th class="text-center so-week-gst so-summary" style="font-weight:700; color:#856404;">—</th>
                                    <th class="text-center" style="font-weight:700; color:#856404;">—</th>
                                    <th class="so-right so-total-gst so-summary" style="font-weight:700; color:#856404;"><?php echo htmlspecialchars($CURRENCY_SYMBOL); ?> 0.00</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="so-delivery" style="text-align:right; margin-top:10px; font-size:13px; font-weight:600;">
                        <span style="margin-right:15px;">
                            <i class="fa fa-calendar"></i>
                            From: <input type="date" id="so-date-from" class="form-control input-sm" style="display:inline-block; width:150px;" value="<?php echo date('Y-m-d'); ?>">
                            &nbsp;To: <input type="date" id="so-date-to" class="form-control input-sm" style="display:inline-block; width:150px;" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
                        </span>
                        <span id="so-repeat-controls" style="display:none;">
                            Repeat: <input type="number" id="so-repeat-interval" class="form-control input-sm" style="display:inline-block; width:60px;" min="1" placeholder="7">
                            <select id="so-repeat-unit" class="form-control input-sm" style="display:inline-block; width:100px;">
                                <option value="">Select Unit</option>
                                <?php
                                $repeatUnits = getRepeatUnits();
                                foreach ($repeatUnits as $unit) {
                                    echo '<option value="' . htmlspecialchars($unit['id']) . '">' . htmlspecialchars($unit['display_name']) . '</option>';
                                }
                                ?>
                            </select>
                        </span>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== Standing Orders Management Table ===== -->
<div class="page-container" style="padding-top:0;">
    <div class="page-content-wrapper">
        <div class="page-content">
            <div class="portlet light bordered" style="margin-top:16px;">
                <div class="portlet-title">
                    <div class="caption font-blue">
                        <i class="fa fa-list icon-blue"></i>
                        <span class="caption-subject bold uppercase">Active Standing Orders</span>
                    </div>
                </div>
                <div class="portlet-body">
                    <div id="soMgmtAlert" style="display:none;" class="alert"></div>
                    <?php if (empty($allActiveStandingOrders)): ?>
                        <p class="text-muted" style="padding:20px;">No active standing orders found.</p>
                    <?php else: ?>
                    <?php
                        $activeSoCustomers = [];
                        foreach ($allActiveStandingOrders as $so) {
                            $customerId = (int) ($so['customer_id'] ?? 0);
                            $customerName = trim((string) ($so['customer_name'] ?? ''));
                            if ($customerId > 0 && $customerName !== '' && !isset($activeSoCustomers[$customerId])) {
                                $activeSoCustomers[$customerId] = $customerName;
                            }
                        }
                        natcasesort($activeSoCustomers);
                    ?>
                    <div class="row so-mgmt-filters">
                        <div class="col-md-4 col-sm-6">
                            <label for="so-mgmt-filter-customer">Customer</label>
                            <select id="so-mgmt-filter-customer" class="form-control input-sm" data-placeholder="All Customers">
                                <option value="">All Customers</option>
                                <?php foreach ($activeSoCustomers as $customerId => $customerName): ?>
                                    <option value="<?php echo (int) $customerId; ?>"><?php echo htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label for="so-mgmt-filter-date-from">Date From</label>
                            <input type="date" id="so-mgmt-filter-date-from" class="form-control input-sm" />
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label for="so-mgmt-filter-date-to">Date To</label>
                            <input type="date" id="so-mgmt-filter-date-to" class="form-control input-sm" />
                        </div>
                        <div class="col-md-2 col-sm-6">
                            <button type="button" class="btn btn-default btn-sm btn-block" id="so-mgmt-filter-reset"><i class="fa fa-refresh"></i> Reset</button>
                        </div>
                    </div>
                    <div class="table-responsive so-mgmt-table-wrap">
                        <table id="soMgmtTable" class="so-mgmt-table table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Customer</th>
                                    <th>Shipping Address</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Items</th>
                                    <th>Future Pending Orders</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="soMgmtTbody">
                                <?php foreach ($allActiveStandingOrders as $so): ?>
                                <tr id="so-row-<?php echo (int)$so['id']; ?>"
                                    data-customer-id="<?php echo (int) ($so['customer_id'] ?? 0); ?>"
                                    data-date-from="<?php echo htmlspecialchars($so['date_from'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                    data-date-to="<?php echo htmlspecialchars($so['date_to'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    <td><?php echo (int)$so['id']; ?></td>
                                    <td><?php echo htmlspecialchars($so['customer_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($so['address_label'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($so['date_from'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($so['date_to'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo (int)$so['item_count']; ?></td>
                                    <td><?php echo (int)$so['pending_future_count']; ?></td>
                                    <td>
                                        <button class="btn-so-edit" data-customer-id="<?php echo (int)$so['customer_id']; ?>" title="Edit this standing order">
                                            <i class="fa fa-pencil"></i> Edit
                                        </button>
                                        <?php if (function_exists('isSuperAdmin') && isSuperAdmin()): ?>
                                        <button class="btn-so-copy"
                                            data-so-id="<?php echo (int)$so['id']; ?>"
                                            data-customer="<?php echo htmlspecialchars($so['customer_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-date-from="<?php echo htmlspecialchars($so['date_from'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            data-date-to="<?php echo htmlspecialchars($so['date_to'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            title="Copy this standing order to another customer">
                                            <i class="fa fa-copy"></i> Copy
                                        </button>
                                        <button class="btn-so-delete"
                                            data-so-id="<?php echo (int)$so['id']; ?>"
                                            data-customer="<?php echo htmlspecialchars($so['customer_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-pending="<?php echo (int)$so['pending_future_count']; ?>"
                                            title="Delete standing order and future pending orders">
                                            <i class="fa fa-trash"></i> Delete
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Copy Standing Order Modal -->
<div class="modal fade" id="copySOModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-copy"></i> Copy Standing Order</h4>
            </div>
            <div class="modal-body">
                <p>Copying from: <strong id="copySOSourceLabel"></strong></p>
                <div id="copySOAlert" style="display:none;" class="alert"></div>
                <div class="form-group">
                    <label>Target Customer <span class="text-danger">*</span></label>
                    <select id="copySOCustomer" class="form-control select2" data-placeholder="Search customer..." style="width:100%;">
                        <option value=""></option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?php echo (int)$c['customer_id']; ?>"><?php echo htmlspecialchars($c['customer_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" id="copySOAddressGroup" style="display:none;">
                    <label>Shipping Address</label>
                    <select id="copySOAddress" class="form-control select2" data-placeholder="Select shipping address..." style="width:100%;">
                        <option value="">-- No address / use default --</option>
                    </select>
                </div>
                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label>Start Date <span class="text-danger">*</span></label>
                            <input type="date" id="copySODateFrom" class="form-control" min="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label>End Date</label>
                            <input type="date" id="copySODateTo" class="form-control" min="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="copySOConfirmBtn"><i class="fa fa-copy"></i> Copy Standing Order</button>
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
<!-- Dependencies for Select2 -->
<script src="assets/global/plugins/select2/js/select2.full.min.js"></script>

<script>
(function(){
    var CURR_SYM = <?php echo json_encode($CURRENCY_SYMBOL); ?>;
    var gstRateMap = <?php echo json_encode($gstRateMap); ?>;
    var DELIVERY_GST_RATE = <?php echo json_encode($deliveryGstRate); ?>;
    function getDeliveryGstRate() { return DELIVERY_GST_RATE; }
    var currentAddressDetails = null;
    var pendingShippingAddressId = null; // Track address to auto-select after addresses load
    var addressesLoaded = false; // Track if shipping addresses have been loaded
    var addressDetailRequestId = 0; // Track latest address detail request to ignore stale responses
    var soMgmtDataTable = null;
    function fmt(n){ return Number(n).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}); }
    function fmtPct(n){ return Number(n || 0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}) + '%'; }
    function sum(arr){ return arr.reduce(function(a,b){return a + (parseFloat(b)||0);},0); }

    function initStandingOrderManagementTable() {
        if (!window.jQuery || !jQuery.fn || !jQuery.fn.DataTable) {
            return;
        }

        var $table = jQuery('#soMgmtTable');
        if (!$table.length) {
            return;
        }

        if (jQuery.fn.dataTable.isDataTable($table)) {
            soMgmtDataTable = $table.DataTable();
            return;
        }

        var $customerFilter = jQuery('#so-mgmt-filter-customer');
        var $dateFromFilter = jQuery('#so-mgmt-filter-date-from');
        var $dateToFilter = jQuery('#so-mgmt-filter-date-to');

        if (jQuery.fn.select2 && !$customerFilter.hasClass('select2-hidden-accessible')) {
            $customerFilter.select2({
                width: '100%',
                theme: 'bootstrap',
                placeholder: $customerFilter.data('placeholder') || 'All Customers',
                minimumResultsForSearch: 0
            });
        }

        jQuery.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            if (settings.nTable !== $table.get(0)) {
                return true;
            }

            var rowNode = settings.aoData && settings.aoData[dataIndex] ? settings.aoData[dataIndex].nTr : null;
            if (!rowNode) {
                return true;
            }

            var selectedCustomer = $customerFilter.val() || '';
            var rowCustomerId = rowNode.getAttribute('data-customer-id') || '';
            if (selectedCustomer && rowCustomerId !== selectedCustomer) {
                return false;
            }

            var filterFrom = $dateFromFilter.val() || '';
            var filterTo = $dateToFilter.val() || '';
            var rowFrom = rowNode.getAttribute('data-date-from') || '';
            var rowTo = rowNode.getAttribute('data-date-to') || '';

            if (filterFrom && rowTo && rowTo < filterFrom) {
                return false;
            }
            if (filterTo && rowFrom && rowFrom > filterTo) {
                return false;
            }

            return true;
        });

        soMgmtDataTable = $table.DataTable({
            responsive: true,
            autoWidth: false,
            order: [],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'All']],
            columnDefs: [
                { targets: 7, orderable: false, searchable: false }
            ]
        });

        $customerFilter.on('change', function() {
            soMgmtDataTable.draw();
        });

        $dateFromFilter.add($dateToFilter).on('change input', function() {
            soMgmtDataTable.draw();
        });

        jQuery('#so-mgmt-filter-reset').on('click', function() {
            $customerFilter.val('');
            if ($customerFilter.hasClass('select2-hidden-accessible')) {
                $customerFilter.trigger('change.select2');
            }
            $dateFromFilter.val('');
            $dateToFilter.val('');
            soMgmtDataTable.search('').columns().search('');
            soMgmtDataTable.draw();
        });
    }

    function enableCart(){
        document.getElementById('so-cart-section').classList.remove('so-disabled');
        document.getElementById('so-no-customer-msg').style.display = 'none';
        document.getElementById('so-no-address-msg').style.display = 'none';
    }

    function disableCart(reason){
        document.getElementById('so-cart-section').classList.add('so-disabled');
        if(reason === 'no-customer'){
            document.getElementById('so-no-customer-msg').style.display = 'block';
            document.getElementById('so-no-address-msg').style.display = 'none';
            resetAddressDetails('select-customer');
        } else if(reason === 'no-address'){
            document.getElementById('so-no-customer-msg').style.display = 'none';
            document.getElementById('so-no-address-msg').style.display = 'block';
            resetAddressDetails('select-address');
        } else {
            resetAddressDetails();
        }
        // Clear table and reset all inputs
        document.getElementById('so-tbody').innerHTML = '';
        document.getElementById('so-delivery-input').value = '3.00';
        document.getElementById('so-repeat-interval').value = '';
        document.getElementById('so-repeat-unit').value = '';
        document.getElementById('so-date-from').value = new Date().toISOString().slice(0,10);
        document.getElementById('so-date-to').value = '';
        // Clear quick-add inputs
        document.querySelectorAll('.qa-day').forEach(function(input){ input.value = ''; });
        jQuery('#qa-item').val(null).trigger('change');
        document.getElementById('qa-total-week').textContent = CURR_SYM + ' 0.00';
        document.getElementById('qa-cost-per-day').textContent = CURR_SYM + ' 0.00';
        recalcFoot();
    }

    function resetAddressDetails(mode, customMessage){
        currentAddressDetails = null;
        var placeholder = document.getElementById('address-details-placeholder');
        var placeholderText = document.getElementById('address-details-placeholder-text');
        var placeholderIcon = document.getElementById('address-details-placeholder-icon');
        var content = document.getElementById('address-details-content');
        var badge = document.getElementById('default-badge');
        if (badge) { badge.style.display = 'none'; }
        if (content) { content.style.display = 'none'; }
        if (placeholder) { placeholder.style.display = 'block'; }
        if (placeholderText) {
            var text = 'Address details will appear here once selected.';
            if (mode === 'select-customer') {
                text = 'Select a customer to load shipping addresses.';
            } else if (mode === 'select-address') {
                text = 'Select a shipping address to view delivery instructions.';
            } else if (mode === 'loading') {
                text = 'Loading shipping address details...';
            } else if (mode === 'error') {
                text = 'Unable to load shipping address details. Please try again.';
            }
            placeholderText.textContent = customMessage || text;
        }
        if (placeholderIcon) {
            var iconClass = 'fa fa-info-circle';
            if (mode === 'loading') {
                iconClass = 'fa fa-spinner fa-spin';
            } else if (mode === 'error') {
                iconClass = 'fa fa-exclamation-triangle';
            }
            placeholderIcon.className = iconClass;
        }
        document.getElementById('address-label').textContent = '';
        document.getElementById('address-full').textContent = '';
        document.getElementById('contact-person').textContent = '';
        document.getElementById('contact-phone').textContent = '';
        document.getElementById('contact-email').textContent = '';
        document.getElementById('delivery-availability').textContent = 'Not set';
        document.getElementById('delivery-time').textContent = 'Not set';
        document.getElementById('security-features').textContent = 'Not set';
        document.getElementById('delivery-route').textContent = 'Not assigned';
        document.getElementById('address-remarks').textContent = 'None';
        document.getElementById('attribute-1').textContent = '—';
        document.getElementById('attribute-2').textContent = '—';
        document.getElementById('attribute-3').textContent = '—';
        var attrSection = document.getElementById('address-attributes');
        if (attrSection) { attrSection.style.opacity = '0.6'; }
    }

    function getProductAvailability(productId) {
        var availability = {mon: 1, tue: 1, wed: 1, thu: 1, fri: 1, sat: 1, sun: 1}; // Default fallback
        try {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'standing-order.php', false); // Synchronous
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.send('action=get_product_availability&product_id=' + encodeURIComponent(productId));
            
            if (xhr.status === 200) {
                var parsed = JSON.parse(xhr.responseText);
                if (parsed && typeof parsed === 'object') {
                    availability = parsed;
                }
            }
        } catch (e) {
            console.error('Exception in getProductAvailability:', e);
        }
        return availability;
    }

    function updateQuickAddAvailability(productId) {
        var availability = getProductAvailability(productId);
        var dayKeys = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
        var inputs = document.querySelectorAll('.qa-day');

        for (var i = 0; i < 7; i++) {
            var productAvailable = availability[dayKeys[i]] == 1;
            var addressAvailable = currentAddressDetails ? (currentAddressDetails[dayKeys[i]] == 1) : true;
            var isAvailable = productAvailable && addressAvailable;
            inputs[i].disabled = !isAvailable;
            inputs[i].classList.toggle('so-disabled', !isAvailable);
            if (!isAvailable) {
                inputs[i].value = '';
                inputs[i].style.opacity = '0.5';
            } else {
                inputs[i].style.opacity = '1';
            }
        }
    }

    function recalcRow(tr){
        var price = parseFloat(tr.dataset.price||0);
        var gstRate = parseFloat(tr.dataset.gstRate||0);
        var qtys = Array.from(tr.querySelectorAll('.so-day-input')).map(function(i){return parseFloat(i.value)||0});
        var totalQty = qtys.reduce(function(a,b){return a+b;},0);
        var costNet = totalQty * price;
        var costGross = costNet * (1 + (gstRate / 100));
        tr.querySelector('.so-row-total').textContent = totalQty;
        tr.querySelector('.so-row-gst').textContent = fmtPct(gstRate);
        tr.querySelector('.so-row-cost').textContent = CURR_SYM + ' ' + fmt(costGross);
        recalcFoot();
    }

    function addItemToTable(itemId, itemName, price, qtys, gstRate) {
        gstRate = parseFloat(gstRate) || 0;
        var tbody = document.getElementById('so-tbody');
        var existingRow = tbody.querySelector('tr[data-item-id="' + itemId + '"]');
        if (existingRow) {
            existingRow.setAttribute('data-gst-rate', gstRate);
            // Update existing
            var inputs = existingRow.querySelectorAll('.so-day-input');
            for (var i = 0; i < 7; i++) {
                if (qtys[i] > 0) {
                    inputs[i].value = (parseFloat(inputs[i].value) || 0) + qtys[i];
                }
            }
            recalcRow(existingRow);
        } else {
            // Add new row
            var tr = document.createElement('tr');
            tr.setAttribute('data-item-id', itemId);
            tr.setAttribute('data-price', price);
            tr.setAttribute('data-gst-rate', gstRate);
            var html = '<td>' + itemName + '<div class="so-muted">@ ' + CURR_SYM + ' ' + fmt(price) + '</div></td>';

            // Get product availability
            var availability = getProductAvailability(itemId);
            var dayKeys = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

            for (var i = 0; i < 7; i++) {
                var productAvailable = availability[dayKeys[i]] == 1;
                var addressAvailable = currentAddressDetails ? (currentAddressDetails[dayKeys[i]] == 1) : true;
                var isAvailable = productAvailable && addressAvailable;
                var disabledAttr = isAvailable ? '' : ' disabled';
                var opacityStyle = isAvailable ? '' : ' style="opacity:0.5"';
                var inputClass = 'form-control input-sm so-qty-input so-day-input' + (isAvailable ? '' : ' so-disabled');
                var val = isAvailable ? (qtys[i] || 0) : 0;
                html += '<td class="text-center"><input type="number" min="0" class="' + inputClass + '" value="' + val + '"' + disabledAttr + opacityStyle + '></td>';
            }
            html += '<td class="text-center so-row-total so-summary">0</td>';
            html += '<td class="text-center so-row-gst so-summary">' + fmtPct(gstRate) + '</td>';
            html += '<td class="so-right so-row-cost so-summary">' + CURR_SYM + ' 0.00</td>';
            html += '<td class="text-center"><i class="fa fa-trash-o so-remove" title="Remove item"></i></td>';
            tr.innerHTML = html;
            tbody.appendChild(tr);
            recalcRow(tr);
        }
    }

    function recalcFoot(){
        var table = document.getElementById('so-table');
        var rows = Array.from(table.querySelectorAll('#so-tbody tr')).filter(function(r){return r.dataset.itemId});
        var dayIdx = [0,1,2,3,4,5,6];
        var dayTotals = dayIdx.map(function(){return 0});
        var dayCosts  = dayIdx.map(function(){return 0});
        var dayGst    = dayIdx.map(function(){return 0});
        var weekQty = 0; var grand = 0;
        var grandGst = 0;
        rows.forEach(function(r){
            var price = parseFloat(r.dataset.price||0);
            var gstRate = parseFloat(r.dataset.gstRate||0);
            var qtys = Array.from(r.querySelectorAll('.so-day-input')).map(function(i){return parseFloat(i.value)||0});
            qtys.forEach(function(q,ix){
                var itemNet = q * price;
                var itemGst = itemNet * (gstRate / 100);
                dayTotals[ix] += q;
                dayCosts[ix] += itemNet + itemGst;
                dayGst[ix] += itemGst;
            });
            var tot = qtys.reduce(function(a,b){return a+b;},0);
            var rowNet = tot * price;
            var rowGst = rowNet * (gstRate / 100);
            weekQty += tot;
            grand += rowNet + rowGst;
            grandGst += rowGst;
        });

        var deliveryInput = document.getElementById('so-delivery-input');
        var deliveryAmount = parseFloat(deliveryInput ? deliveryInput.value : 0) || 0;

        // Row 1: Total pieces
        var colTotals = table.querySelectorAll('tfoot .so-col-total');
        dayTotals.forEach(function(v,i){ colTotals[i].textContent = v > 0 ? v : '\u2014'; });
        table.querySelector('tfoot .so-week-total').textContent = weekQty > 0 ? weekQty : '\u2014';
        table.querySelector('tfoot .so-grand-cost').textContent = CURR_SYM + ' ' + fmt(grand);

        // Row 2: Cost per day
        var dayCostEls = table.querySelectorAll('tfoot .so-day-cost');
        var weekCostTotal = 0;
        dayCosts.forEach(function(c,i){
            dayCostEls[i].textContent = c > 0 ? (CURR_SYM + ' ' + fmt(c)) : '\u2014';
            weekCostTotal += c;
        });
        table.querySelector('tfoot .so-week-cost').textContent = grand > 0 ? (CURR_SYM + ' ' + fmt(grand)) : '\u2014';
        table.querySelector('tfoot .so-grand-cost-label').textContent = CURR_SYM + ' ' + fmt(grand);

        // Row 3: Delivery checkmarks — show checkmark for each day that has at least one item
        var delivCheckEls = table.querySelectorAll('tfoot .so-delivery-check');
        var delivDayCount = 0;
        dayTotals.forEach(function(v,i){
            if (v > 0) {
                delivCheckEls[i].innerHTML = '<i class="fa fa-check-square" style="color:#28a745; font-size:16px;" title="Delivery day"></i>';
                delivDayCount++;
            } else {
                delivCheckEls[i].innerHTML = '<i class="fa fa-square-o" style="color:#ccc; font-size:16px;"></i>';
            }
        });
        table.querySelector('tfoot .so-delivery-count').textContent = delivDayCount > 0 ? delivDayCount : '\u2014';

        // Row 4: Cost per day with delivery (add delivery+GST only to days that have items)
        var deliveryGstRate = getDeliveryGstRate();
        var deliveryAmountWithGst = deliveryAmount * (1 + (deliveryGstRate / 100));
        var dayDelivCostEls = table.querySelectorAll('tfoot .so-day-cost-with-delivery');
        var weekCostWithDeliv = 0;
        dayCosts.forEach(function(c,i){
            var withDel = c > 0 ? (c + deliveryAmountWithGst) : 0;
            dayDelivCostEls[i].textContent = withDel > 0 ? (CURR_SYM + ' ' + fmt(withDel)) : '\u2014';
            weekCostWithDeliv += withDel;
        });
        table.querySelector('tfoot .so-week-cost-with-delivery').textContent = weekCostWithDeliv > 0 ? (CURR_SYM + ' ' + fmt(weekCostWithDeliv)) : '\u2014';
        table.querySelector('tfoot .so-total-with-delivery').textContent = CURR_SYM + ' ' + fmt(weekCostWithDeliv);

        // Row 5: Total GST
        var dayGstEls = table.querySelectorAll('tfoot .so-day-gst');
        var weekGstTotal = 0;
        dayGst.forEach(function(g,i){
            var dayDeliveryGst = dayTotals[i] > 0 ? (deliveryAmount * (deliveryGstRate / 100)) : 0;
            var totalDayGst = g + dayDeliveryGst;
            dayGstEls[i].textContent = totalDayGst > 0 ? (CURR_SYM + ' ' + fmt(totalDayGst)) : '\u2014';
            weekGstTotal += totalDayGst;
        });
        table.querySelector('tfoot .so-week-gst').textContent = weekGstTotal > 0 ? (CURR_SYM + ' ' + fmt(weekGstTotal)) : '\u2014';
        table.querySelector('tfoot .so-total-gst').textContent = CURR_SYM + ' ' + fmt(weekGstTotal);

        // Legacy element support (in case referenced elsewhere)
        var legacyEl = document.getElementById('so-total-with-delivery');
        if (legacyEl) { legacyEl.textContent = CURR_SYM + ' ' + fmt(weekCostWithDeliv); }
    }

    document.addEventListener('input', function(e){
        if(e.target.classList.contains('so-day-input')){
            var tr = e.target.closest('tr');
            recalcRow(tr);
        }
        if(e.target.classList.contains('qa-day')){
            var selOpt = (document.getElementById('qa-item').selectedOptions[0]||{});
            var price = 0; if(selOpt && selOpt.getAttribute){ price = parseFloat(selOpt.getAttribute('data-price'))||0; }
            var gstRate = 0; if(selOpt && selOpt.getAttribute){ gstRate = parseFloat(selOpt.getAttribute('data-gst-rate'))||0; }
            var qtys = Array.from(document.querySelectorAll('.qa-day')).map(function(i){return parseFloat(i.value)||0});
            var tot = qtys.reduce(function(a,b){return a+b;},0);
            var perDay = sum(qtys)/7.0;
            var unitWithGst = price * (1 + (gstRate / 100));
            document.getElementById('qa-total-week').textContent = CURR_SYM+' '+fmt(tot*unitWithGst);
            document.getElementById('qa-cost-per-day').textContent = CURR_SYM+' '+fmt(perDay*unitWithGst);
        }
        if(e.target.id === 'so-delivery-input'){
            recalcFoot();
        }
    });



    document.getElementById('qa-add').addEventListener('click', function(){
        var sel = document.getElementById('qa-item');
        if(!sel.value){ alert('Select an item first'); return; }
        var selOpt = sel.selectedOptions[0];
        var itemName = selOpt.textContent.split(' (')[0]; // Extract name
        var price = parseFloat(selOpt.getAttribute('data-price')) || 0;
        var gstRate = parseFloat(selOpt.getAttribute('data-gst-rate')) || 0;
        var qtys = Array.from(document.querySelectorAll('.qa-day')).map(function(i){ return parseFloat(i.value) || 0; });
        if (qtys.every(q => q === 0)) { alert('Enter at least one quantity'); return; }
        addItemToTable(sel.value, itemName, price, qtys, gstRate);
        
        // Clear quick-add inputs after adding
        document.querySelectorAll('.qa-day').forEach(function(input){ input.value = ''; });
        jQuery('#qa-item').val(null).trigger('change');
        document.getElementById('qa-total-week').textContent = CURR_SYM + ' 0.00';
        document.getElementById('qa-cost-per-day').textContent = CURR_SYM + ' 0.00';
    });

    document.getElementById('so-save').addEventListener('click', function(){
        var customerId = document.getElementById('so-customer').value||'';
        if(!customerId){ alert('Please select a customer.'); return; }
        var shippingAddressId = document.getElementById('so-shipping-address').value||'';
        if(!shippingAddressId){ alert('Please select a shipping address.'); return; }
        
        // Validate that no quantities are set for unavailable days
        var hasInvalidQuantities = false;
        document.querySelectorAll('#so-tbody tr[data-item-id]').forEach(function(r){
            r.querySelectorAll('.so-day-input').forEach(function(i, index){ 
                if (i.disabled && parseFloat(i.value) > 0) {
                    hasInvalidQuantities = true;
                }
            });
        });
        if (hasInvalidQuantities) {
            alert('Cannot save: Some items have quantities set for unavailable delivery days. Please adjust the quantities.');
            return;
        }
        
        var data = [];
        document.querySelectorAll('#so-tbody tr[data-item-id]').forEach(function(r){
            var hasValue = false;
            var obj = { item_id: r.dataset.itemId, price: parseFloat(r.dataset.price||0), qty: [] };
            r.querySelectorAll('.so-day-input').forEach(function(i){ 
                var val = parseFloat(i.value)||0;
                if (val > 0) hasValue = true;
                obj.qty.push(val); 
            });
            if(hasValue) {
                data.push(obj);
            }
        });
        var deliveryAmount = parseFloat(document.getElementById('so-delivery-input').value) || 0;
        if (!isFinite(deliveryAmount) || deliveryAmount < 0) { alert('Delivery must be 0 or positive'); return; }
        var repeatInterval = parseInt(document.getElementById('so-repeat-interval').value) || null;
        var repeatUnit = document.getElementById('so-repeat-unit').value || null;
        var dateFrom = document.getElementById('so-date-from').value || null;
        var dateTo = document.getElementById('so-date-to').value || null;
        if (!dateFrom || !dateTo) {
            alert('Please select From Date and To Date.');
            return;
        }
        if (dateFrom > dateTo) {
            alert('From Date cannot be after To Date.');
            return;
        }
        var payload = { customer_id: customerId, shipping_address_id: shippingAddressId, items: data, delivery_amount: deliveryAmount, repeat_interval: repeatInterval, repeat_unit: repeatUnit, date_from: dateFrom, date_to: dateTo };
        fetch('process/save-standing-order.php', { method:'POST', headers:{'Content-Type':'application/json', 'X-Requested-With': 'XMLHttpRequest'}, credentials: 'same-origin', body: JSON.stringify(payload) })
            .then(function(r){ return r.text(); })
            .then(function(t){
                var j = null;
                try { j = JSON.parse(t); } catch (e) {}
                if (!j) {
                    alert('Failed to save: ' + (t ? t.substring(0, 200) : 'No response'));
                    return;
                }
                alert(j.message||'Saved'); 
                if(j.status === 'success') {
                     // Optionally reset form or redirect
                }
            })
            .catch(function(e){ alert('Failed to save: ' + (e && e.message ? e.message : 'Unknown error')); });
    });

    document.getElementById('so-cancel').addEventListener('click', function(){
        if(confirm('Are you sure you want to cancel? Any unsaved changes will be lost.')) {
            window.location.href = 'index.php';
        }
    });

    // Initialize cart as disabled
    disableCart('no-customer');

    // Auto-calculate To date when repeat interval/unit or From date changes
    function recalcToDate() {
        var fromDate = document.getElementById('so-date-from').value;
        var interval = parseInt(document.getElementById('so-repeat-interval').value);
        var unit = document.getElementById('so-repeat-unit').value;
        if (!fromDate || !interval || interval <= 0 || !unit) return;
        var d = new Date(fromDate + 'T00:00:00');
        if (unit == '1') { // Days
            d.setDate(d.getDate() + interval);
        } else if (unit == '2') { // Weeks
            d.setDate(d.getDate() + (interval * 7));
        } else if (unit == '3') { // Months
            d.setMonth(d.getMonth() + interval);
        }
        document.getElementById('so-date-to').value = d.toISOString().slice(0, 10);
    }

    document.getElementById('so-repeat-interval').addEventListener('input', recalcToDate);
    document.getElementById('so-repeat-unit').addEventListener('change', recalcToDate);
    document.getElementById('so-date-from').addEventListener('change', recalcToDate);

    // Preselect customer from URL param if provided (supports customer_id or customerID)
    var preselectCustomer = <?php echo (int)($_GET['customer_id'] ?? $_GET['customerID'] ?? 0); ?>;
    if (preselectCustomer) {
        var soCustomerSelect = document.getElementById('so-customer');
        if (soCustomerSelect) {
            soCustomerSelect.value = preselectCustomer;
        }
    }

    var copySODropdownAdapter = null;

    function getCopySODropdownAdapter() {
        if (copySODropdownAdapter !== null) {
            return copySODropdownAdapter;
        }

        if (!window.jQuery || !jQuery.fn || !jQuery.fn.select2 || !jQuery.fn.select2.amd) {
            copySODropdownAdapter = false;
            return copySODropdownAdapter;
        }

        try {
            var amd = jQuery.fn.select2.amd;
            var Utils = amd.require('select2/utils');
            var Dropdown = amd.require('select2/dropdown');
            var DropdownSearch = amd.require('select2/dropdown/search');
            var CloseOnSelect = amd.require('select2/dropdown/closeOnSelect');
            var AttachContainer = amd.require('select2/dropdown/attachContainer');

            copySODropdownAdapter = Utils.Decorate(Dropdown, DropdownSearch);
            copySODropdownAdapter = Utils.Decorate(copySODropdownAdapter, CloseOnSelect);
            copySODropdownAdapter = Utils.Decorate(copySODropdownAdapter, AttachContainer);
        } catch (e) {
            console.error('Failed to build copy modal Select2 dropdown adapter:', e);
            copySODropdownAdapter = false;
        }

        return copySODropdownAdapter;
    }

    function initCopySOSelect2() {
        if (!window.jQuery || !jQuery.fn || !jQuery.fn.select2) {
            return;
        }

        var $modalParent = jQuery('#copySOModal .modal-content');
        var dropdownAdapter = getCopySODropdownAdapter();
        jQuery('#copySOCustomer, #copySOAddress').each(function(){
            var $select = jQuery(this);
            if ($select.data('select2')) {
                $select.select2('destroy');
            }
            var options = {
                width: '100%',
                theme: 'bootstrap',
                allowClear: true,
                placeholder: $select.data('placeholder') || 'Search...',
                minimumResultsForSearch: 0
            };

            if (dropdownAdapter) {
                options.dropdownAdapter = dropdownAdapter;
            } else {
                options.dropdownParent = $modalParent;
            }

            $select.select2(options);
        });
    }

    if(window.jQuery && jQuery.fn && jQuery.fn.select2){
        jQuery('#so-customer, #so-shipping-address, #qa-item').each(function(){
            jQuery(this).select2({
                width: '100%',
                theme: 'bootstrap',
                allowClear: true,
                placeholder: jQuery(this).data('placeholder') || 'Search...',
                minimumResultsForSearch: 0
            });
        });

        initCopySOSelect2();

        setShippingAddressDisabled(true);

        if (preselectCustomer) {
            // Update Select2 UI
            jQuery('#so-customer').val(preselectCustomer).trigger('change');
        }
    }

    initStandingOrderManagementTable();

    // Handle product selection in quick-add
    jQuery('#qa-item').on('change', function(){
        var productId = jQuery(this).val();
        if(productId){
            updateQuickAddAvailability(productId);
        } else {
            // No product selected - still respect address availability
            var days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
            var dayInputs = document.querySelectorAll('.qa-day');
            days.forEach(function(day, index) {
                var addrAvailable = currentAddressDetails ? (currentAddressDetails[day] == 1) : true;
                var input = dayInputs[index];
                if (input) {
                    input.disabled = !addrAvailable;
                    input.classList.toggle('so-disabled', !addrAvailable);
                    input.style.opacity = addrAvailable ? '1' : '0.5';
                    if (!addrAvailable) {
                        input.value = '';
                        input.placeholder = day.charAt(0).toUpperCase() + day.slice(1) + ' (Unavailable)';
                    } else {
                        input.placeholder = day.charAt(0).toUpperCase() + day.slice(1);
                    }
                }
            });
        }
    });

    // Remove row handler
    document.getElementById('so-table').addEventListener('click', function(e){
        if(e.target && e.target.classList.contains('so-remove')){
            var tr = e.target.closest('tr');
            if(tr && confirm('Are you sure you want to remove this item?')){ 
                tr.remove();
                recalcFoot();
            }
        }
    });

    function setShippingAddressDisabled(isDisabled) {
        if (!window.jQuery) {
            var addrSelect = document.getElementById('so-shipping-address');
            if (addrSelect) {
                addrSelect.disabled = !!isDisabled;
            }
            return;
        }

        var $addrSelect = jQuery('#so-shipping-address');
        if ($addrSelect.length === 0) return;

        $addrSelect.prop('disabled', !!isDisabled);
        if (isDisabled) {
            $addrSelect.attr('disabled', 'disabled');
        } else {
            $addrSelect.removeAttr('disabled');
        }

        if ($addrSelect.data('select2')) {
            var $container = $addrSelect.next('.select2-container');
            $container.toggleClass('select2-container--disabled', !!isDisabled);
            $container.find('.select2-selection').attr('aria-disabled', isDisabled ? 'true' : 'false');
        }
    }

    // Load shipping addresses for selected customer
    function loadShippingAddresses(customerId) {
        var $addrSelect = jQuery('#so-shipping-address');
        var addrSelect = $addrSelect.get(0);
        if (!addrSelect) return;

        $addrSelect.html('<option value="">Loading...</option>');
        setShippingAddressDisabled(true);

        jQuery.ajax({
            url: 'standing-order.php',
            method: 'POST',
            data: { action: 'get_customer_shipping_addresses', customer_id: customerId },
            dataType: 'json'
        }).done(function(addresses) {
            if (!Array.isArray(addresses)) {
                addresses = [];
            }

            $addrSelect.html(addresses.length > 0
                ? '<option value="">Select shipping address...</option>'
                : '<option value="">No shipping address found</option>'
            );

            var defaultAddressId = '';
            addresses.forEach(function(addr) {
                var option = document.createElement('option');
                option.value = addr.id;
                var label = addr.address_label || addr.address_line_1 || 'Shipping Address';
                var defText = addr.is_default == 1 ? ' (Default)' : '';
                option.textContent = label + (addr.address_line_1 ? ' - ' + addr.address_line_1 : '') + (addr.city ? ', ' + addr.city : '') + defText;
                addrSelect.appendChild(option);
                if (!defaultAddressId && addr.is_default == 1) {
                    defaultAddressId = String(addr.id);
                }
            });

            setShippingAddressDisabled(addresses.length === 0);
            addressesLoaded = true;

            var selectId = '';
            if (pendingShippingAddressId) {
                selectId = String(pendingShippingAddressId);
                pendingShippingAddressId = null;
            } else if (addresses.length > 0) {
                selectId = defaultAddressId || String(addresses[0].id);
            }

            $addrSelect.val(selectId).trigger('change');
        }).fail(function(xhr, statusText, errorThrown) {
            console.error('Error loading shipping addresses:', statusText, errorThrown, xhr && xhr.responseText ? xhr.responseText.substring(0, 300) : '');
            $addrSelect.html('<option value="">Error loading addresses</option>');
            setShippingAddressDisabled(true);
            addressesLoaded = false;
            pendingShippingAddressId = null;
            $addrSelect.val('').trigger('change');
        });
    }

    // Handle shipping address selection
    jQuery('#so-shipping-address').on('change', function(){
        var addrId = jQuery(this).val() || '';
        if(!addrId) {
            // Disable cart when no address
            disableCart('no-address');
            // Enable all day inputs when no address is selected
            updateDayInputsAvailability(null);
            return;
        }
        resetAddressDetails('loading');
        // Enable cart when address selected
        enableCart();
        // Load and display address details
        loadShippingAddressDetails(addrId);
    });

    // Load shipping address details
    function loadShippingAddressDetails(addressId) {
        var myRequestId = ++addressDetailRequestId;
        console.log('Loading address details for id=' + addressId + ' (request #' + myRequestId + ')');
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'standing-order.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                // Ignore stale responses - only process the latest request
                if (myRequestId !== addressDetailRequestId) {
                    console.log('Ignoring stale address detail response (request #' + myRequestId + ', latest is #' + addressDetailRequestId + ')');
                    return;
                }
                if (xhr.status === 200) {
                    try {
                        var addr = JSON.parse(xhr.responseText);
                        if (addr.error) {
                            console.error('Error loading address details:', addr.error);
                            resetAddressDetails('error', addr.error);
                            return;
                        }
                        displayAddressDetails(addr);
                    } catch (e) {
                        console.error('Error parsing address details:', e);
                        resetAddressDetails('error', 'Invalid response received for shipping address.');
                    }
                } else {
                    console.error('Failed to load shipping address details. HTTP status:', xhr.status);
                    resetAddressDetails('error');
                }
            }
        };
        xhr.send('action=get_shipping_address_details&shipping_address_id=' + encodeURIComponent(addressId));
    }

    // Display address details in the UI
    function displayAddressDetails(addr) {
        currentAddressDetails = addr; // Store for later use
        console.log('Address availability loaded:', {mon: addr.mon, tue: addr.tue, wed: addr.wed, thu: addr.thu, fri: addr.fri, sat: addr.sat, sun: addr.sun});
        var placeholder = document.getElementById('address-details-placeholder');
        var content = document.getElementById('address-details-content');
        if (placeholder) { placeholder.style.display = 'none'; }
        if (content) { content.style.display = 'block'; }
        
        // Show default badge if applicable
        var defaultBadge = document.getElementById('default-badge');
        if (addr.is_default == 1) {
            defaultBadge.style.display = 'inline-block';
        } else {
            defaultBadge.style.display = 'none';
        }
        document.getElementById('address-label').textContent = addr.address_label || '';
        var fullAddr = (addr.address_line_1 || '') + (addr.address_line_2 ? ', ' + addr.address_line_2 : '') +
                      (addr.city ? ', ' + addr.city : '') + (addr.state ? ', ' + addr.state : '') +
                      (addr.country ? ', ' + addr.country : '') + (addr.postal_code ? ' ' + addr.postal_code : '');
        document.getElementById('address-full').textContent = fullAddr;
        document.getElementById('contact-person').textContent = addr.contact_person_name || '';
        var phoneToShow = addr.contact_person_phone || addr.contact_no || '';
        document.getElementById('contact-phone').textContent = phoneToShow;
        document.getElementById('contact-email').textContent = addr.contact_person_email || '';
        var deliveryTimeText = 'Not set';
        if (addr.delivery_time_from || addr.delivery_time_till) {
            var fromTime = addr.delivery_time_from || 'Start';
            var tillTime = addr.delivery_time_till || 'End';
            deliveryTimeText = fromTime + ' - ' + tillTime;
        }
        document.getElementById('delivery-time').textContent = deliveryTimeText;
        document.getElementById('delivery-route').textContent = addr.route_name || 'Not assigned';
        
        // Set delivery amount and show per-day route info
        var dr = addr.day_routes;
        var dayKeys = ['mon','tue','wed','thu','fri','sat','sun'];
        var dayLabels = {mon:'Mon',tue:'Tue',wed:'Wed',thu:'Thu',fri:'Fri',sat:'Sat',sun:'Sun'};
        if (dr) {
            // Build per-day route display
            var routeLines = [];
            var amounts = [];
            dayKeys.forEach(function(d) {
                var rname = dr[d + '_route_name'];
                var ramount = parseFloat(dr[d + '_route_amount'] || 0);
                if (rname) {
                    routeLines.push(dayLabels[d] + ': ' + rname + ' ($' + ramount.toFixed(2) + ')');
                    amounts.push(ramount);
                }
            });
            // Show per-day routes below the delivery-route span
            var routeEl = document.getElementById('delivery-route');
            var perDayId = 'per-day-routes-display';
            var existing = document.getElementById(perDayId);
            if (existing) existing.parentNode.removeChild(existing);
            if (routeLines.length > 0) {
                var span = document.createElement('span');
                span.id = perDayId;
                span.style.cssText = 'display:block; font-size:11px; color:#28a745; margin-top:2px;';
                span.textContent = routeLines.join(' | ');
                routeEl.parentNode.insertBefore(span, routeEl.nextSibling);
                // Use first day's amount as default delivery input (user can adjust)
                if (amounts.length > 0) {
                    document.getElementById('so-delivery-input').value = amounts[0].toFixed(2);
                    if (typeof updateStandingOrderTotals === 'function') updateStandingOrderTotals();
                }
            }
        } else if (addr.route_amount && parseFloat(addr.route_amount) > 0) {
            // Fallback: use address-level route amount
            document.getElementById('so-delivery-input').value = parseFloat(addr.route_amount).toFixed(2);
            if (typeof updateStandingOrderTotals === 'function') updateStandingOrderTotals();
        }
        
        var security = [];
        if (addr.has_door_key == 1) security.push('Door Key');
        if (addr.has_shop_alarm == 1) security.push('Shop Alarm');
        document.getElementById('security-features').textContent = security.length > 0 ? security.join(', ') : 'None';
        var availability = [];
        if (addr.mon == 1) availability.push('Mon');
        if (addr.tue == 1) availability.push('Tue');
        if (addr.wed == 1) availability.push('Wed');
        if (addr.thu == 1) availability.push('Thu');
        if (addr.fri == 1) availability.push('Fri');
        if (addr.sat == 1) availability.push('Sat');
        if (addr.sun == 1) availability.push('Sun');
        document.getElementById('delivery-availability').textContent = availability.length ? availability.join(', ') : 'Not configured';
        var remarksText = (addr.remarks && addr.remarks.trim() !== '') ? addr.remarks : 'None';
        document.getElementById('address-remarks').textContent = remarksText;
        
        // Display attributes if any exist
        var hasAttributes = false;
        if (addr.attribute_1 && addr.attribute_1.trim() !== '') {
            document.getElementById('attribute-1').textContent = addr.attribute_1;
            hasAttributes = true;
        } else {
            document.getElementById('attribute-1').textContent = '—';
        }
        if (addr.attribute_2 && addr.attribute_2.trim() !== '') {
            document.getElementById('attribute-2').textContent = addr.attribute_2;
            hasAttributes = true;
        } else {
            document.getElementById('attribute-2').textContent = '—';
        }
        if (addr.attribute_3 && addr.attribute_3.trim() !== '') {
            document.getElementById('attribute-3').textContent = addr.attribute_3;
            hasAttributes = true;
        } else {
            document.getElementById('attribute-3').textContent = '—';
        }
        
        // Always show the attributes section, but highlight if no data
        var attrSection = document.getElementById('address-attributes');
        attrSection.style.display = 'block';
        if (!hasAttributes) {
            attrSection.style.opacity = '0.6';
        } else {
            attrSection.style.opacity = '1';
        }
        
        // Enable/disable day inputs based on availability
        updateDayInputsAvailability(addr);
        
        document.getElementById('so-address-details').style.display = 'block';
    }

    // Update day inputs availability based on shipping address
    function updateDayInputsAvailability(addr) {
        var dayInputs = document.querySelectorAll('.qa-day');
        var days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
        
        // Also combine with product availability if a product is selected in quick-add
        var qaProductId = jQuery('#qa-item').val();
        var qaProductAvail = qaProductId ? getProductAvailability(qaProductId) : null;

        // Update quick-add row
        days.forEach(function(day, index) {
            var addrAvailable = addr ? (addr[day] == 1) : true;
            var productAvailable = qaProductAvail ? (qaProductAvail[day] == 1) : true;
            var isAvailable = addrAvailable && productAvailable;
            var qaInput = dayInputs[index];
            
            if (qaInput) {
                qaInput.disabled = !isAvailable;
                qaInput.classList.toggle('so-disabled', !isAvailable);
                if (isAvailable) {
                    qaInput.style.opacity = '1';
                    qaInput.placeholder = day.charAt(0).toUpperCase() + day.slice(1);
                } else {
                    qaInput.style.opacity = '0.5';
                    qaInput.value = '';
                    qaInput.placeholder = day.charAt(0).toUpperCase() + day.slice(1) + ' (Unavailable)';
                }
            }
        });
        
        // Update ALL item rows in the table (not just first row)
        var tableRows = document.querySelectorAll('#so-tbody tr[data-item-id]');
        tableRows.forEach(function(row) {
            var rowInputs = row.querySelectorAll('.so-day-input');
            var productId = row.getAttribute('data-item-id');
            var productAvailability = getProductAvailability(productId);
            
            days.forEach(function(day, index) {
                var addrAvailable = addr ? (addr[day] == 1) : true;
                var productAvailable = productAvailability[day] == 1;
                var isAvailable = addrAvailable && productAvailable;
                var input = rowInputs[index];
                if (input) {
                    input.disabled = !isAvailable;
                    input.classList.toggle('so-disabled', !isAvailable);
                    input.style.opacity = isAvailable ? '1' : '0.5';
                    if (!isAvailable) {
                        input.value = 0;
                    }
                }
            });
            recalcRow(row);
        });
        recalcFoot();
    }

    // Load existing standing order when selecting customer
    // ── Copy standing order ──
    var COPY_SO_CSRF = <?php echo json_encode($copySoCsrf); ?>;
    var copySOSourceId = 0;

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.btn-so-copy')) return;
        var btn      = e.target.closest('.btn-so-copy');
        copySOSourceId = parseInt(btn.getAttribute('data-so-id'), 10);
        var customer = btn.getAttribute('data-customer');
        var dfrom    = btn.getAttribute('data-date-from') || '';
        var dto      = btn.getAttribute('data-date-to')   || '';

        document.getElementById('copySOSourceLabel').textContent = customer + ' (SO #' + copySOSourceId + ')';
        document.getElementById('copySOAlert').style.display = 'none';

        // Pre-fill dates from source
        var today = '<?php echo date('Y-m-d'); ?>';
        document.getElementById('copySODateFrom').value = dfrom >= today ? dfrom : today;
        document.getElementById('copySODateTo').value   = dto || '';

        // Reset customer/address selects
        jQuery('#copySOCustomer').val(null).trigger('change.select2');
        jQuery('#copySOAddress').val('').trigger('change.select2');
        document.getElementById('copySOAddressGroup').style.display = 'none';

        jQuery('#copySOModal').modal('show');
    });

    jQuery('#copySOModal').on('shown.bs.modal', function() {
        initCopySOSelect2();
    });

    // Load shipping addresses when target customer changes in modal
    jQuery(document).on('change', '#copySOCustomer', function() {
        var cid = this.value;
        var addrGroup  = document.getElementById('copySOAddressGroup');
        var addrSelect = document.getElementById('copySOAddress');
        addrSelect.innerHTML = '<option value="">-- No address / use default --</option>';
        jQuery('#copySOAddress').trigger('change.select2');
        if (!cid) { addrGroup.style.display = 'none'; return; }
        fetch('standing-order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            body: 'action=get_customer_shipping_addresses&customer_id=' + encodeURIComponent(cid)
        })
        .then(function(r) { return r.json(); })
        .then(function(addrs) {
            if (Array.isArray(addrs) && addrs.length > 0) {
                addrs.forEach(function(a) {
                    var opt = document.createElement('option');
                    opt.value = a.id;
                    var defText = a.is_default == 1 ? ' (Default)' : '';
                    opt.textContent = a.address_label + ' — ' + a.address_line_1 + (a.city ? ', ' + a.city : '') + defText;
                    if (a.is_default == 1) opt.selected = true;
                    addrSelect.appendChild(opt);
                });
                addrGroup.style.display = 'block';
                jQuery('#copySOAddress').trigger('change.select2');
            } else {
                addrGroup.style.display = 'none';
            }
        })
        .catch(function() { addrGroup.style.display = 'none'; });
    });

    document.getElementById('copySOConfirmBtn').addEventListener('click', function() {
        var targetCustomerId = parseInt(document.getElementById('copySOCustomer').value, 10) || 0;
        var targetAddressId  = parseInt(document.getElementById('copySOAddress').value,   10) || 0;
        var dateFrom         = document.getElementById('copySODateFrom').value;
        var dateTo           = document.getElementById('copySODateTo').value;
        var alertEl          = document.getElementById('copySOAlert');

        if (!targetCustomerId) {
            alertEl.className = 'alert alert-warning';
            alertEl.textContent = 'Please select a target customer.';
            alertEl.style.display = 'block';
            return;
        }
        if (!dateFrom) {
            alertEl.className = 'alert alert-warning';
            alertEl.textContent = 'Please enter a start date.';
            alertEl.style.display = 'block';
            return;
        }

        var btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Copying...';

        fetch('process/copy-standing-order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            body: JSON.stringify({
                csrf_token:         COPY_SO_CSRF,
                source_so_id:       copySOSourceId,
                target_customer_id: targetCustomerId,
                target_address_id:  targetAddressId,
                date_from:          dateFrom,
                date_to:            dateTo
            })
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res && res.status === true) {
                jQuery('#copySOModal').modal('hide');
                var globalAlert = document.getElementById('soMgmtAlert');
                globalAlert.className = 'alert alert-success';
                globalAlert.textContent = res.message || 'Standing order copied successfully.';
                globalAlert.style.display = 'block';
                globalAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                setTimeout(function() { globalAlert.style.display = 'none'; }, 7000);
                // Reload page after short delay so new row appears in table
                setTimeout(function() { location.reload(); }, 1500);
            } else {
                alertEl.className = 'alert alert-danger';
                alertEl.textContent = (res && res.message) ? res.message : 'Failed to copy standing order.';
                alertEl.style.display = 'block';
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-copy"></i> Copy Standing Order';
            }
        })
        .catch(function() {
            alertEl.className = 'alert alert-danger';
            alertEl.textContent = 'Network error. Please try again.';
            alertEl.style.display = 'block';
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-copy"></i> Copy Standing Order';
        });
    });

    // Reset modal state when closed
    jQuery('#copySOModal').on('hidden.bs.modal', function() {
        document.getElementById('copySOAlert').style.display = 'none';
        document.getElementById('copySOConfirmBtn').disabled = false;
        document.getElementById('copySOConfirmBtn').innerHTML = '<i class="fa fa-copy"></i> Copy Standing Order';
    });

    // ── Delete standing order ──
    document.addEventListener('click', function(e) {
        // Edit button: pre-select customer in the form above
        if (e.target.closest('.btn-so-edit')) {
            var btn = e.target.closest('.btn-so-edit');
            var cid = btn.getAttribute('data-customer-id');
            jQuery('#so-customer').val(String(cid)).trigger('change');
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return;
        }
        // Delete button
        if (e.target.closest('.btn-so-delete')) {
            var btn = e.target.closest('.btn-so-delete');
            var soId     = btn.getAttribute('data-so-id');
            var customer = btn.getAttribute('data-customer');
            var pending  = parseInt(btn.getAttribute('data-pending'), 10) || 0;
            var msg = 'Delete standing order for "' + customer + '"?';
            if (pending > 0) {
                msg += '\n\n' + pending + ' future pending order(s) will also be permanently deleted.';
            }
            msg += '\n\nThis action cannot be undone.';
            if (!confirm(msg)) return;

            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';

            fetch('process/delete-standing-order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                body: JSON.stringify({ csrf_token: <?php echo json_encode($deleteSoCsrf); ?>, standing_order_id: parseInt(soId, 10) })
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                var alertEl = document.getElementById('soMgmtAlert');
                if (res && res.status === true) {
                    var row = document.getElementById('so-row-' + soId);
                    if (row) {
                        if (soMgmtDataTable) {
                            soMgmtDataTable.row(row).remove().draw(false);
                        } else {
                            row.remove();
                        }
                    }
                    alertEl.className = 'alert alert-success';
                    alertEl.textContent = res.message || 'Standing order deleted.';
                } else {
                    alertEl.className = 'alert alert-danger';
                    alertEl.textContent = (res && res.message) ? res.message : 'Failed to delete standing order.';
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa fa-trash"></i> Delete';
                }
                alertEl.style.display = 'block';
                alertEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                setTimeout(function() { alertEl.style.display = 'none'; }, 6000);
            })
            .catch(function() {
                alert('Network error. Please try again.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-trash"></i> Delete';
            });
        }
    });

    // Load existing standing order when selecting customer
    jQuery('#so-customer').on('change', function(){
        var cid = jQuery(this).val() || '';
        if(!cid) {
            disableCart('no-customer');
            // Clear shipping address dropdown
            var $addrSelect = jQuery('#so-shipping-address');
            $addrSelect.html('<option value="">Select shipping address...</option>');
            setShippingAddressDisabled(true);
            $addrSelect.val('').trigger('change');
            return;
        }
        // Load shipping addresses for this customer
        loadShippingAddresses(cid);
        // Show no address message
        addressesLoaded = false;
        pendingShippingAddressId = null;
        disableCart('no-address');
        // Clear table
        document.getElementById('so-tbody').innerHTML = '';
        recalcFoot();
        fetch('process/get-standing-order.php?customer_id='+encodeURIComponent(cid), { headers: {'X-Requested-With': 'XMLHttpRequest'}, credentials: 'same-origin' })
            .then(function(r){ return r.json(); })
            .then(function(j){
                if(!j || j.status!=='success' || !j.data) return;
                var items = j.data.items||[];
                items.forEach(function(row){
                    // Need item name and price
                    var itemId = row.item_id;
                    // Fetch item details
                    var selOpt = document.querySelector('#qa-item option[value="' + itemId + '"]');
                    if (!selOpt) return;
                    var itemName = selOpt.textContent.split(' (')[0];
                    var price = parseFloat(selOpt.getAttribute('data-price')) || 0;
                    var gstRate = parseFloat(selOpt.getAttribute('data-gst-rate')) || 0;
                    addItemToTable(itemId, itemName, price, row.qty, gstRate);
                });
                // Set delivery amount
                var deliveryInput = document.getElementById('so-delivery-input');
                if(deliveryInput && j.data.delivery_amount !== undefined){
                    deliveryInput.value = j.data.delivery_amount;
                }
                // Set repeat interval from customer defaults
                var repeatIntervalInput = document.getElementById('so-repeat-interval');
                if(repeatIntervalInput){
                    repeatIntervalInput.value = (j.data.repeat_interval !== null && j.data.repeat_interval !== undefined) ? j.data.repeat_interval : '';
                }
                var repeatUnitSelect = document.getElementById('so-repeat-unit');
                if(repeatUnitSelect){
                    repeatUnitSelect.value = j.data.repeat_unit || '';
                }
                // Set dates
                var dateFromInput = document.getElementById('so-date-from');
                var dateToInput = document.getElementById('so-date-to');
                if(dateFromInput && j.data.date_from){
                    dateFromInput.value = j.data.date_from;
                }
                if(dateToInput && j.data.date_to){
                    dateToInput.value = j.data.date_to;
                }
                // If dates not set but repeat info exists, auto-calculate To date
                if(dateFromInput && repeatIntervalInput && repeatUnitSelect && !j.data.date_to) {
                    recalcToDate();
                }
                // Set shipping address if available
                if(j.data.shipping_address_id) {
                    if (addressesLoaded) {
                        // Addresses already loaded, select directly
                        jQuery('#so-shipping-address').val(String(j.data.shipping_address_id)).trigger('change');
                    } else {
                        // Addresses not loaded yet, queue for when they arrive
                        pendingShippingAddressId = String(j.data.shipping_address_id);
                    }
                }
                recalcFoot();

                // After items are added, re-apply address availability in case
                // address details loaded before items (items would have used stale/null state)
                if (currentAddressDetails) {
                    console.log('Re-applying address availability after standing order items loaded');
                    updateDayInputsAvailability(currentAddressDetails);
                }
            })
            .catch(function(){ /* ignore */ });
    });

    // Trigger change after handlers are bound to load data for preselected customer
    if (preselectCustomer) {
        setTimeout(function() {
            if (window.jQuery) {
                jQuery('#so-customer').trigger('change');
            } else {
                var el = document.getElementById('so-customer');
                if (el) {
                    el.dispatchEvent(new Event('change'));
                }
            }
        }, 0);
    }
})();
</script>
</body>
</html>



