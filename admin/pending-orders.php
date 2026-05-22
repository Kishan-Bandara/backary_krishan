<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('include/database.php');
include('include/check_login.php');

$db = new Database();

if (empty($_SESSION['delete_order_csrf'])) {
    $_SESSION['delete_order_csrf'] = bin2hex(random_bytes(32));
}
$deleteOrderCsrf = $_SESSION['delete_order_csrf'];
$today = date('Y-m-d');

// Get currency
$getcurrency = $db->getRow('SELECT * FROM currency WHERE activated = ? LIMIT 1', ["Y"]);
$currency_symbol = $getcurrency['currency'] ?? '$';

// Get date range (default to today)
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : $today;
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : $today;

// Fetch orders
$orders = $db->getRows(
    "SELECT 
        ih.invoice_h_id,
        ih.invoice_h_code,
        ih.invoice_h_delivery_date AS delivery_date,
        ih.invoice_h_gross_value AS total,
        ih.invoice_h_status AS status,
        ih.invoice_h_delivery_cost AS delivery_cost,
        ih.invoice_h_order_note AS po_number,
        c.customer_id,
        c.customer_name,
        COALESCE(csa.address_line_1, ih.invoice_h_delivery_address, '') AS address_line_1,
        COALESCE(csa.address_line_2, '') AS address_line_2,
        COALESCE(csa.city, '') AS city,
        COALESCE(drm.route_name, 'No Route') AS route_name,
        (SELECT SUM(invoice_d_item_total) FROM invoice_details WHERE invoice_h_id = ih.invoice_h_id) AS items_total,
        (SELECT MAX(is_cart_item) FROM invoice_details WHERE invoice_h_id = ih.invoice_h_id) AS has_cart
     FROM invoice_hedder ih
     JOIN customer c ON c.customer_id = ih.invoice_h_customer_id
     LEFT JOIN customer_shipping_address csa ON csa.customer_id = c.customer_id AND csa.is_default = 1
     LEFT JOIN delivery_route_master drm ON drm.id = csa.delivery_route_id
     WHERE ih.invoice_h_delivery_date BETWEEN ? AND ?
     ORDER BY ih.invoice_h_delivery_date ASC, c.customer_name ASC",
    [$date_from, $date_to]
);

// Get status label
function getStatusLabel($status) {
    switch ((int)$status) {
        case 0: return ['label' => 'Pending', 'class' => 'label-warning'];
        case 1: return ['label' => 'Invoiced', 'class' => 'label-success'];
        case -1: return ['label' => 'Cancelled', 'class' => 'label-danger'];
        default: return ['label' => 'Unknown', 'class' => 'label-default'];
    }
}

$formattedDateRange = date('d/m/Y', strtotime($date_from)) . ' - ' . date('d/m/Y', strtotime($date_to));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Pending Orders</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
    <link rel="stylesheet" href="assets/global/plugins/bootstrap-daterangepicker/daterangepicker.css" />
    
    <style>
        .pending-orders-report {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        
        .report-header {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 5px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .report-header h1 {
            font-size: 18px;
            font-weight: bold;
            margin: 0;
            white-space: nowrap;
        }
        
        .report-header .form-control {
            display: inline-block;
            width: auto;
        }
        
        .pending-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .pending-table th {
            text-align: left;
            font-weight: normal;
            padding: 10px 8px;
            background: #f9f9f9;
            border-bottom: 2px solid #ddd;
            font-size: 11px;
            color: #666;
        }
        
        .pending-table th .search-icon {
            color: #999;
            margin-left: 5px;
            cursor: pointer;
        }
        
        .pending-table td {
            padding: 8px;
            vertical-align: middle;
            border-bottom: 1px solid #eee;
            font-size: 12px;
        }
        
        .pending-table .col-edit { width: 8%; text-align: center; }
        .pending-table .col-date { width: 9%; }
        .pending-table .col-customer { width: 16%; }
        .pending-table .col-box { width: 7%; }
        .pending-table .col-address { width: 16%; }
        .pending-table .col-route { width: 10%; }
        .pending-table .col-items { width: 8%; text-align: right; }
        .pending-table .col-delivery { width: 7%; text-align: right; }
        .pending-table .col-po { width: 10%; }
        .pending-table .col-total { width: 8%; text-align: right; }
        .pending-table .col-status { width: 8%; text-align: center; }
        
        .pending-table .text-right { text-align: right; }
        .pending-table .text-center { text-align: center; }
        
        .pending-table tbody tr:hover {
            background: #fffde7;
        }
        
        .pending-table tbody tr.selected {
            background: linear-gradient(to right, #8BC34A 0%, #8BC34A 3px, #fffde7 3px, #fffde7 100%);
        }
        
        .pending-table .edit-icon {
            color: #2196F3;
            cursor: pointer;
        }
        
        .pending-table .edit-icon:hover {
            color: #1976D2;
        }
        
        .label {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }
        
        .label-success { background: #4CAF50; color: #fff; }
        .label-warning { background: #ff9800; color: #fff; }
        .label-danger { background: #f44336; color: #fff; }
        .label-default { background: #9e9e9e; color: #fff; }

        /* Cart icon for orders containing cart items */
        .cart-icon { margin-left: 6px; font-size: 12px; color: #555; vertical-align: middle; }
        
        .filter-row input {
            width: 100%;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-size: 11px;
        }
        
        .no-print {
            margin-bottom: 15px;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 0;
                font-size: 10px;
            }
            
            .no-print, .report-header, .filter-row, .col-edit {
                display: none !important;
            }
            
            .pending-orders-report {
                padding: 5mm;
            }
            
            .pending-table th,
            .pending-table td {
                padding: 4px 5px;
                font-size: 10px;
            }
            
            .page-sidebar-wrapper,
            .page-bar,
            .page-header,
            .page-footer {
                display: none !important;
            }
            
            .page-content {
                margin: 0 !important;
                padding: 0 !important;
            }
            
            .page-container,
            .page-content-wrapper {
                margin: 0 !important;
                padding: 0 !important;
            }
        }
        
        @page {
            size: A4 landscape;
            margin: 8mm;
        }
    </style>
</head>
<body class="page-sidebar-closed-hide-logo page-content-white">
    <?php include('common/manubar.php'); ?>
    <div class="clearfix"></div>
    <div class="page-container">
        <div class="page-sidebar-wrapper">
            <?php include('common/sidebar.php'); ?>
        </div>
        
        <div class="page-content-wrapper">
            <div class="page-content">
                <!-- Page Bar -->
                <div class="page-bar no-print">
                    <ul class="page-breadcrumb">
                        <li>
                            <a href="index.php">Home</a>
                            <i class="fa fa-circle"></i>
                        </li>
                        <li>
                            <a href="#">Reports</a>
                            <i class="fa fa-circle"></i>
                        </li>
                        <li>
                            <span>Pending Orders</span>
                        </li>
                    </ul>
                </div>
                
                <!-- Report Content -->
                <div class="pending-orders-report">
                    <!-- Report Header -->
                    <div class="report-header">
                        <h1>Pending Orders</h1>
                        <form method="get" class="form-inline" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                            <input type="text" id="daterange" class="form-control" style="width: 220px;" />
                            <input type="hidden" name="date_from" id="date_from" value="<?php echo $date_from; ?>" />
                            <input type="hidden" name="date_to" id="date_to" value="<?php echo $date_to; ?>" />
                            <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Filter</button>
                            <button type="button" class="btn btn-default" onclick="window.print();"><i class="fa fa-print"></i></button>
                        </form>
                    </div>
                    
                    <?php if (empty($orders)) { ?>
                        <div class="alert alert-info">No orders found for the selected date range.</div>
                    <?php } else { ?>
                        <table class="pending-table" id="pendingTable">
                            <thead>
                                <tr>
                                    <th class="col-edit"></th>
                                    <th class="col-date">Date</th>
                                    <th class="col-customer">Customer <i class="fa fa-search search-icon"></i></th>
                                    <th class="col-box">Box Code</th>
                                    <th class="col-address">Address <i class="fa fa-search search-icon"></i></th>
                                    <th class="col-route">Route <i class="fa fa-search search-icon"></i></th>
                                    <th class="col-items text-right">Items</th>
                                    <th class="col-delivery text-right">Delivery</th>
                                    <th class="col-po">PO Number <i class="fa fa-search search-icon"></i></th>
                                    <th class="col-total text-right">Total</th>
                                    <th class="col-status text-center">Status</th>
                                </tr>
                                <tr class="filter-row">
                                    <th></th>
                                    <th></th>
                                    <th><input type="text" id="filterCustomer" placeholder="Filter..." /></th>
                                    <th></th>
                                    <th><input type="text" id="filterAddress" placeholder="Filter..." /></th>
                                    <th><input type="text" id="filterRoute" placeholder="Filter..." /></th>
                                    <th></th>
                                    <th></th>
                                    <th><input type="text" id="filterPO" placeholder="Filter..." /></th>
                                    <th></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): 
                                    $addressParts = array_filter([$order['address_line_1'], $order['address_line_2'], $order['city']]);
                                    $address = implode(', ', $addressParts);
                                    $statusInfo = getStatusLabel($order['status']);
                                    $deliveryDate = date('d/m/Y', strtotime($order['delivery_date']));
                                    $itemsTotal = $order['items_total'] ?? 0;
                                    $deliveryCost = $order['delivery_cost'] ?? 0;
                                    $canDeleteCartOrder = !empty($order['has_cart'])
                                        && (int) $order['status'] === 0
                                        && ($order['delivery_date'] > $today);
                                ?>
                                <tr data-customer="<?php echo htmlspecialchars(strtolower($order['customer_name'])); ?>"
                                    data-address="<?php echo htmlspecialchars(strtolower($address)); ?>"
                                    data-route="<?php echo htmlspecialchars(strtolower($order['route_name'])); ?>"
                                    data-po="<?php echo htmlspecialchars(strtolower($order['po_number'] ?? '')); ?>">
                                    <td class="col-edit text-center">
                                        <a href="order-detail.php?order_id=<?php echo $order['invoice_h_id']; ?>" class="btn btn-xs btn-info" title="View Order" target="_blank">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        <a href="cart-order.php?invoice_id=<?php echo $order['invoice_h_id']; ?>" class="btn btn-xs btn-primary" title="Edit Order">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                        <?php if ($canDeleteCartOrder): ?>
                                            <a href="#" class="btn btn-xs btn-danger btn-delete-order" title="Delete Cart Order" data-id="<?php echo (int) $order['invoice_h_id']; ?>" data-code="<?php echo htmlspecialchars($order['invoice_h_code']); ?>">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $deliveryDate; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($order['customer_name']); ?>
                                        <?php if (!empty($order['has_cart'])): ?>
                                            <i class="fa fa-shopping-cart cart-icon" title="Cart Order"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td></td>
                                    <td><?php echo htmlspecialchars($address); ?></td>
                                    <td><?php echo htmlspecialchars($order['route_name']); ?></td>
                                    <td class="text-right"><?php echo $currency_symbol . number_format($itemsTotal, 2); ?></td>
                                    <td class="text-right"><?php echo $deliveryCost > 0 ? $currency_symbol . number_format($deliveryCost, 2) : ''; ?></td>
                                    <td><?php echo htmlspecialchars($order['po_number'] ?? ''); ?></td>
                                    <td class="text-right"><?php echo $currency_symbol . number_format($order['total'], 2); ?></td>
                                    <td class="text-center">
                                        <span class="label <?php echo $statusInfo['class']; ?>"><?php echo $statusInfo['label']; ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include('common/footer.php'); ?>
    <script src="assets/global/plugins/moment.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/bootstrap-daterangepicker/daterangepicker.min.js" type="text/javascript"></script>
    
    <script>
    $(document).ready(function() {
        var DELETE_ORDER_CSRF = <?php echo json_encode($deleteOrderCsrf); ?>;

        // Date range picker
        $('#daterange').daterangepicker({
            startDate: moment('<?php echo $date_from; ?>'),
            endDate: moment('<?php echo $date_to; ?>'),
            locale: {
                format: 'DD/MM/YYYY'
            }
        }, function(start, end) {
            $('#date_from').val(start.format('YYYY-MM-DD'));
            $('#date_to').val(end.format('YYYY-MM-DD'));
        });
        
        // Set initial display
        $('#daterange').val('<?php echo date('d/m/Y', strtotime($date_from)); ?> - <?php echo date('d/m/Y', strtotime($date_to)); ?>');
        
        // Filter functionality
        function applyFilters() {
            var filterCustomer = $('#filterCustomer').val().toLowerCase();
            var filterAddress = $('#filterAddress').val().toLowerCase();
            var filterRoute = $('#filterRoute').val().toLowerCase();
            var filterPO = $('#filterPO').val().toLowerCase();
            
            $('#pendingTable tbody tr').each(function() {
                var row = $(this);
                var show = true;
                
                if (filterCustomer && row.data('customer').indexOf(filterCustomer) === -1) show = false;
                if (filterAddress && row.data('address').indexOf(filterAddress) === -1) show = false;
                if (filterRoute && row.data('route').indexOf(filterRoute) === -1) show = false;
                if (filterPO && row.data('po').indexOf(filterPO) === -1) show = false;
                
                row.toggle(show);
            });
        }
        
        $('.filter-row input').on('keyup', applyFilters);
        
        // Row selection
        $('#pendingTable tbody tr').on('click', function(e) {
            if (!$(e.target).is('a, i')) {
                $('#pendingTable tbody tr').removeClass('selected');
                $(this).addClass('selected');
            }
        });

        $('#pendingTable').on('click', '.btn-delete-order', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var $btn = $(this);
            var invId = $btn.data('id');
            var invCode = $btn.data('code');

            if (!confirm('Delete cart order ' + invCode + '?\n\nThis action cannot be undone.')) {
                return;
            }

            $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

            $.ajax({
                url: 'process/delete-order.php',
                method: 'POST',
                contentType: 'application/json',
                dataType: 'json',
                data: JSON.stringify({ csrf_token: DELETE_ORDER_CSRF, invoice_id: invId })
            }).done(function(res) {
                if (res && res.status === true) {
                    window.location.reload();
                } else {
                    alert((res && res.message) ? res.message : 'Failed to delete cart order.');
                    $btn.prop('disabled', false).html('<i class="fa fa-trash"></i>');
                }
            }).fail(function() {
                alert('Network error. Please try again.');
                $btn.prop('disabled', false).html('<i class="fa fa-trash"></i>');
            });
        });
    });
    </script>
</body>
</html>
