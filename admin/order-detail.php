<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');

$db = new Database();

// Get order ID from URL
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if (!$orderId) {
    die('Invalid order ID');
}

// Fetch order details
$order = $db->getRow('SELECT * FROM invoice_hedder WHERE invoice_h_id = ?', [$orderId]);
if (!$order) {
    die('Order not found');
}

// Fetch customer
$customer = $db->getRow('SELECT * FROM customer WHERE customer_id = ?', [$order['invoice_h_customer_id']]);

// Fetch shipping address details - first try shipping_address_id, then fallback to invoice header fields
$shippingAddress = null;
$deliveryCost = (float)($order['invoice_h_delivery_cost'] ?? 0);

// Prefer invoice-level delivery address when present (custom address should override customer's saved address)
if (!empty($order['invoice_h_delivery_address'])) {
    $shippingAddress = [
        'address_label' => 'Delivery Address',
        'address_line_1' => $order['invoice_h_delivery_address'],
        'address_line_2' => '',
        'city' => $order['delivery_city_name'] ?? '',
        'state' => '',
        'country' => '',
        'postal_code' => '',
        'contact_person_name' => $order['invoice_h_delivery_name'] ?? '',
        'contact_person_phone' => $order['invoice_h_delivery_contact_no'] ?? '',
        'contact_no' => $order['invoice_h_delivery_contact_no'] ?? '',
        'contact_person_email' => '',
        'remarks' => '',
        'delivery_time_from' => '',
        'delivery_time_till' => $order['invoice_h_delivery_time'] ?? '',
        'has_door_key' => 0,
        'has_shop_alarm' => 0,
        'route_name' => 'Not assigned',
        'mon' => 1, 'tue' => 1, 'wed' => 1, 'thu' => 1, 'fri' => 1, 'sat' => 1, 'sun' => 1
    ];
} elseif (!empty($order['shipping_address_id'])) {
    $shippingAddress = $db->getRow('SELECT csa.*, drm.route_name 
        FROM customer_shipping_address csa 
        LEFT JOIN delivery_route_master drm ON csa.delivery_route_id = drm.id 
        WHERE csa.id = ?', [$order['shipping_address_id']]);
}

// Fetch order items
$orderItems = $db->getRows('SELECT id.*, im.item_name FROM invoice_details id
                            LEFT JOIN item_master im ON id.invoice_d_item_id = im.item_id
                            WHERE id.invoice_h_id = ?', [$orderId]);

// Determine if any item is a cart item (is_cart_item = 1)
$hasCart = false;
if (!empty($orderItems)) {
    foreach ($orderItems as $it) {
        if (!empty($it['is_cart_item']) && $it['is_cart_item']) { $hasCart = true; break; }
    }
}

// Debug information (remove in production)
if (isset($_GET['debug'])) {
    echo "<!-- DEBUG INFO:\n";
    echo "Order ID: $orderId\n";
    echo "Order Items Count: " . count($orderItems) . "\n";
    echo "Order Data: " . print_r($order, true) . "\n";
    echo "-->";
}

// Currency config
$currencyRow = $db->getRow('SELECT * FROM currency WHERE activated = ? LIMIT 1', ["Y"]);
$CURRENCY_SYMBOL = isset($currencyRow['currency']) ? $currencyRow['currency'] : 'AUD';
$CURRENCY_RATE = isset($currencyRow['rate']) ? (float)$currencyRow['rate'] : 1.0;

function fmt($n) {
    return number_format($n, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Order Details - <?php echo htmlspecialchars($order['invoice_h_code']); ?></title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
    <style>
        .order-header { background:#f8f9fa; padding:15px; border-radius:8px; margin-bottom:20px; }
        .order-section { margin-bottom:20px; }
        .order-section h4 { border-bottom:2px solid #007bff; padding-bottom:5px; color:#007bff; }
        .detail-row { display:flex; margin-bottom:10px; }
        .detail-label { font-weight:bold; width:200px; flex-shrink:0; }
        .detail-value { flex:1; }
        .item-table th, .item-table td { padding:8px; text-align:left; }
        .item-table th { background:#e9ecef; }
        .total-row { font-weight:bold; background:#f8f9fa; }
        /* Cart icon for orders that include cart items */
        .cart-icon { margin-left:6px; font-size:14px; color:#555; vertical-align:middle; }
        /* Small icon inside item rows */
        .item-cart-icon { margin-left:6px; font-size:12px; color:#555; vertical-align:middle; }
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
                    <li><a href="manage-invoices.php">Invoices</a> <i class="fa fa-circle"></i></li>
                    <li><span>Order Details</span></li>
                </ul>
            </div>

            <div class="order-header">
                <h3>Order #<?php echo $order['invoice_h_code']; ?> <?php if (!empty($hasCart)): ?><i class="fa fa-shopping-cart cart-icon" title="Cart Order"></i><?php endif; ?> - <?php echo htmlspecialchars($customer['customer_name']); ?></h3>
                <p>Created: <?php echo date('Y-m-d H:i:s', strtotime($order['invoice_h_datetime'])); ?> | Status: <?php echo htmlspecialchars($order['invoice_h_status'] == 1 ? 'Accepted' : ($order['invoice_h_status'] == 0 ? 'Pending' : 'Cancelled')); ?></p>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <!-- Order Details -->
                    <div class="order-section">
                        <h4><i class="fa fa-shopping-cart"></i> Order Details</h4>
                        <div class="detail-row">
                            <div class="detail-label">Order ID:</div>
                            <div class="detail-value"><?php echo $order['invoice_h_code']; ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Order Date:</div>
                            <div class="detail-value"><?php echo !empty($order['invoice_h_date']) ? date('Y-m-d', strtotime($order['invoice_h_date'])) : 'N/A'; ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Customer:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($customer['customer_name']); ?> (ID: <?php echo $customer['customer_id']; ?>)</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Subtotal:</div>
                            <div class="detail-value"><?php echo $CURRENCY_SYMBOL; ?> <?php echo fmt($order['invoice_h_net_value']); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Delivery Charge:</div>
                            <div class="detail-value"><?php echo $CURRENCY_SYMBOL; ?> <?php echo fmt($deliveryCost); ?></div>
                        </div>
                        <?php if ((float)($order['invoice_h_coupon_value'] ?? 0) > 0): ?>
                        <div class="detail-row">
                            <div class="detail-label">Discount:</div>
                            <div class="detail-value">
                                - <?php echo $CURRENCY_SYMBOL; ?> <?php echo fmt($order['invoice_h_coupon_value']); ?>
                                <?php if ($order['invoice_h_coupon_type'] == 'PCT'): ?>
                                    (<?php echo fmt($order['invoice_h_coupon_rate']); ?>%)
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="detail-row">
                            <div class="detail-label">Total:</div>
                            <div class="detail-value"><strong><?php echo $CURRENCY_SYMBOL; ?> <?php echo fmt($order['invoice_h_gross_value']); ?></strong></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <!-- Shipping Details -->
                    <div class="order-section">
                        <h4><i class="fa fa-map-marker"></i> Shipping Details</h4>
                        <?php if ($shippingAddress): ?>
                            <div class="detail-row">
                                <div class="detail-label">Address Label:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($shippingAddress['address_label'] ?? ''); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Address:</div>
                                <div class="detail-value">
                                    <?php echo htmlspecialchars($shippingAddress['address_line_1'] ?? ''); ?><br>
                                    <?php if (!empty($shippingAddress['address_line_2'])) echo htmlspecialchars($shippingAddress['address_line_2']) . '<br>'; ?>
                                    <?php echo htmlspecialchars(($shippingAddress['city'] ?? '') . ', ' . ($shippingAddress['state'] ?? '') . ' ' . ($shippingAddress['postal_code'] ?? '')); ?><br>
                                    <?php echo htmlspecialchars($shippingAddress['country'] ?? ''); ?>
                                </div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Contact:</div>
                                <div class="detail-value">
                                    <?php echo htmlspecialchars($shippingAddress['contact_person_name'] ?? ''); ?><br>
                                    Phone: <?php echo htmlspecialchars($shippingAddress['contact_person_phone'] ?? $shippingAddress['contact_no'] ?? ''); ?><br>
                                    Email: <?php echo htmlspecialchars($shippingAddress['contact_person_email'] ?? ''); ?>
                                </div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Remarks:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($shippingAddress['remarks'] ?? 'None'); ?></div>
                            </div>
                        <?php else: ?>
                            <p>No shipping address found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <!-- Delivery Details -->
                    <div class="order-section">
                        <h4><i class="fa fa-truck"></i> Delivery Details</h4>
                        <?php if ($shippingAddress): ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="detail-row">
                                        <div class="detail-label">Delivery Days:</div>
                                        <div class="detail-value">
                                            <?php
                                            $days = [];
                                            if (!empty($shippingAddress['mon'])) $days[] = 'Mon';
                                            if (!empty($shippingAddress['tue'])) $days[] = 'Tue';
                                            if (!empty($shippingAddress['wed'])) $days[] = 'Wed';
                                            if (!empty($shippingAddress['thu'])) $days[] = 'Thu';
                                            if (!empty($shippingAddress['fri'])) $days[] = 'Fri';
                                            if (!empty($shippingAddress['sat'])) $days[] = 'Sat';
                                            if (!empty($shippingAddress['sun'])) $days[] = 'Sun';
                                            echo !empty($days) ? implode(', ', $days) : 'All Days';
                                            ?>
                                        </div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-label">Delivery Time:</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($shippingAddress['delivery_time_from'] . ' - ' . $shippingAddress['delivery_time_till']); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="detail-row">
                                        <div class="detail-label">Security:</div>
                                        <div class="detail-value">
                                            <?php
                                            $security = [];
                                            if ($shippingAddress['has_door_key']) $security[] = 'Door Key';
                                            if ($shippingAddress['has_shop_alarm']) $security[] = 'Shop Alarm';
                                            echo implode(', ', $security) ?: 'None';
                                            ?>
                                        </div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-label">Route:</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($shippingAddress['route_name'] ?? 'Not assigned'); ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <p>No delivery details available.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="order-section">
                <h4><i class="fa fa-list"></i> Order Items</h4>
                <?php if (empty($orderItems)): ?>
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle"></i>
                        <strong>No order items found!</strong><br>
                        This invoice does not have any associated items. This may indicate an issue with invoice creation.
                        <br><small>Invoice ID: <?php echo $orderId; ?>, Invoice Code: <?php echo htmlspecialchars($order['invoice_h_code']); ?></small>
                    </div>
                <?php else: ?>
                    <table class="table table-bordered item-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Rate</th>
                                <th style="text-align:right;">Disc %</th>
                                <th style="text-align:right;">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $grandTotal = 0;
                            foreach ($orderItems as $item):
                                $qty = $item['invoice_d_qty'];
                                $rate = $item['invoice_d_item_price'];
                                $discPct = floatval($item['invoice_d_discount_value'] ?? 0);
                                $amount = $item['invoice_d_item_total'] ?? ($qty * $rate * (1 - $discPct / 100));
                                $grandTotal += $amount;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['item_name']); ?> <?php if (!empty($item['is_cart_item'])): ?><i class="fa fa-shopping-cart item-cart-icon" title="Cart Item"></i><?php endif; ?><?php if (!empty($item['is_gift_item'])): ?> <span style="display:inline-block; margin-left:6px; padding:2px 7px; border-radius:10px; background:#fef3c7; color:#b45309; font-size:10px; font-weight:700; text-transform:uppercase;"><i class="fa fa-gift"></i> Gift</span><?php endif; ?></td>
                                <td><?php echo $qty; ?></td>
                                <td><?php echo $CURRENCY_SYMBOL; ?> <?php echo fmt($rate); ?></td>
                                <td style="text-align:right;"><?php echo $discPct > 0 ? fmt($discPct).'%' : '-'; ?></td>
                                <td style="text-align:right;"><?php echo $CURRENCY_SYMBOL; ?> <?php echo fmt($amount); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td colspan="4" style="text-align:right;">Total:</td>
                                <td style="text-align:right;"><?php echo $CURRENCY_SYMBOL; ?> <?php echo fmt($grandTotal); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                <?php endif; ?>
            </div>

            <div class="text-center" style="margin-top:20px;">
                <a href="packing-slip.php?id=<?php echo $orderId; ?>" target="_blank" class="btn btn-purple" style="background:#8E44AD;color:#fff;">
                    <i class="fa fa-file-text-o"></i> Print Packing Slip
                </a>
                <a href="invoice.php?id=<?php echo $orderId; ?>" target="_blank" class="btn btn-info">
                    <i class="fa fa-print"></i> Print Invoice
                </a>
                <a href="manage-orders.php" class="btn btn-default">
                    <i class="fa fa-arrow-left"></i> Back to Orders
                </a>
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
<script src="assets/global/plugins/select2/select2.full.min.js"></script>

</body>
</html>



