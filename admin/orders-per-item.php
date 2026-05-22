<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('include/database.php');
include('include/check_login.php');

$db = new Database();

// Get parameters
$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$selected_week = isset($_GET['week']) ? (int)$_GET['week'] : (int)date('W');
$selected_item = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;

// Get all products for dropdown
$products = $db->getRows('SELECT item_id, item_name FROM item_master WHERE item_active = "Y" ORDER BY item_name ASC');

// Calculate week start and end dates
$weekStart = new DateTime();
$weekStart->setISODate($selected_year, $selected_week, 1); // Monday
$weekEnd = clone $weekStart;
$weekEnd->modify('+6 days'); // Sunday

$weekStartStr = $weekStart->format('Y-m-d');
$weekEndStr = $weekEnd->format('Y-m-d');

// Generate array of dates for the week
$weekDates = [];
$currentDate = clone $weekStart;
for ($i = 0; $i < 7; $i++) {
    $weekDates[] = [
        'date' => $currentDate->format('Y-m-d'),
        'day_name' => $currentDate->format('D'),
        'day_short' => strtolower($currentDate->format('D')),
        'display' => $currentDate->format('d/m') . "\n(" . $currentDate->format('D') . ')'
    ];
    $currentDate->modify('+1 day');
}

// Get selected product name
$selectedProductName = '';
if ($selected_item > 0) {
    $productRow = $db->getRow('SELECT item_name FROM item_master WHERE item_id = ?', [$selected_item]);
    $selectedProductName = $productRow['item_name'] ?? '';
}

// Get orders for selected item in the week
$orders = [];
$customerTotals = [];

if ($selected_item > 0) {
    $query = $db->getRows(
        "SELECT 
            c.customer_id,
            c.customer_name,
            COALESCE(csa.address_line_1, '') AS address_line_1,
            COALESCE(csa.city, '') AS city,
            ih.invoice_h_delivery_date AS delivery_date,
            ih.order_type,
            SUM(id.invoice_d_qty) AS qty
         FROM invoice_details id
         JOIN invoice_hedder ih ON ih.invoice_h_id = id.invoice_h_id
         JOIN customer c ON c.customer_id = ih.invoice_h_customer_id
         LEFT JOIN customer_shipping_address csa ON csa.customer_id = c.customer_id AND csa.is_default = 1
         WHERE id.invoice_d_item_id = ?
           AND ih.invoice_h_delivery_date BETWEEN ? AND ?
           AND ih.invoice_h_status = 1
         GROUP BY c.customer_id, ih.invoice_h_delivery_date, ih.order_type
         ORDER BY c.customer_name ASC, ih.invoice_h_delivery_date ASC",
        [$selected_item, $weekStartStr, $weekEndStr]
    );
    
    foreach ($query as $row) {
        $custId = $row['customer_id'];
        $date = $row['delivery_date'];
        $qty = (int)$row['qty'];
        $orderType = $row['order_type'] ?? '';
        
        if (!isset($orders[$custId])) {
            $orders[$custId] = [
                'name' => $row['customer_name'],
                'address' => trim($row['address_line_1'] . ', ' . $row['city'], ', '),
                'dates' => [],
                'cart_dates' => [],
                'total' => 0
            ];
        }
        
        // Accumulate qty for same date (standing + cart)
        if (!isset($orders[$custId]['dates'][$date])) {
            $orders[$custId]['dates'][$date] = 0;
        }
        $orders[$custId]['dates'][$date] += $qty;
        $orders[$custId]['total'] += $qty;
        
        // Track if this date has cart order
        if ($orderType === 'CART') {
            $orders[$custId]['cart_dates'][$date] = true;
        }
    }
}

// Get customer availability (shipping_address_availability)
$customerAvailability = [];
if (!empty($orders)) {
    $customerIds = array_keys($orders);
    $placeholders = implode(',', array_fill(0, count($customerIds), '?'));
    
    $availRows = $db->getRows(
        "SELECT csa.customer_id, saa.mon, saa.tue, saa.wed, saa.thu, saa.fri, saa.sat, saa.sun
         FROM customer_shipping_address csa
         JOIN shipping_address_availability saa ON saa.shipping_address_id = csa.id
         WHERE csa.customer_id IN ($placeholders) AND csa.is_default = 1",
        $customerIds
    );
    
    foreach ($availRows as $row) {
        $customerAvailability[$row['customer_id']] = [
            'mon' => (int)$row['mon'],
            'tue' => (int)$row['tue'],
            'wed' => (int)$row['wed'],
            'thu' => (int)$row['thu'],
            'fri' => (int)$row['fri'],
            'sat' => (int)$row['sat'],
            'sun' => (int)$row['sun']
        ];
    }
}

// Generate week options for dropdown
$weekOptions = [];
for ($w = 1; $w <= 53; $w++) {
    $ws = new DateTime();
    $ws->setISODate($selected_year, $w, 1);
    $we = clone $ws;
    $we->modify('+6 days');
    $weekOptions[$w] = "Week $w " . $ws->format('D d/m') . ' - ' . $we->format('D d/m');
}

$formattedWeekRange = $weekStart->format('D d/m') . ' - ' . $weekEnd->format('D d/m');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Orders Per Item - <?php echo htmlspecialchars($selectedProductName); ?></title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
    
    <style>
        .by-item-report {
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
        
        .report-header .item-select {
            min-width: 250px;
        }
        
        .filter-row {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .by-item-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .by-item-table th {
            text-align: center;
            font-weight: normal;
            padding: 10px 8px;
            background: #f9f9f9;
            border-bottom: 2px solid #ddd;
            font-size: 11px;
            color: #666;
        }
        
        .by-item-table th.col-customer {
            text-align: left;
            width: 35%;
        }
        
        .by-item-table th.col-day {
            width: 9%;
        }
        
        .by-item-table th.col-total {
            width: 6%;
        }
        
        .by-item-table td {
            padding: 10px 8px;
            vertical-align: middle;
            border-bottom: 1px solid #eee;
            font-size: 12px;
            text-align: center;
        }
        
        .by-item-table td.col-customer {
            text-align: left;
        }
        
        .by-item-table tbody tr:hover {
            background: linear-gradient(to right, #8BC34A 0%, #8BC34A 3px, #f0fff0 3px, #f0fff0 100%);
        }
        
        .by-item-table .unavailable {
            color: #2196F3;
            font-size: 11px;
        }
        
        .by-item-table .order-qty {
            color: #4CAF50;
            font-weight: bold;
        }
        
        .by-item-table .order-qty .fa {
            margin-right: 3px;
        }
        
        .by-item-table .total-col {
            font-weight: bold;
            background: #fafafa;
        }
        
        .btn-export {
            background: #4CAF50;
            color: #fff;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn-export:hover {
            background: #45a049;
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
            
            .no-print, .report-header, .filter-row {
                display: none !important;
            }
            
            .by-item-report {
                padding: 5mm;
            }
            
            .by-item-table th,
            .by-item-table td {
                padding: 5px;
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
                            <span>Orders Per Item</span>
                        </li>
                    </ul>
                </div>
                
                <!-- Report Content -->
                <div class="by-item-report">
                    <!-- Report Header -->
                    <div class="report-header">
                        <h1>By Item</h1>
                        <form method="get" class="form-inline" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                            <select name="item_id" class="form-control item-select" required>
                                <option value="">-- Select Product --</option>
                                <?php foreach ($products as $prod): ?>
                                    <option value="<?php echo $prod['item_id']; ?>" <?php echo $selected_item == $prod['item_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($prod['item_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i></button>
                            <button type="button" class="btn-export" onclick="exportToExcel();"><i class="fa fa-file-excel-o"></i> Export</button>
                            <button type="button" class="btn btn-default" onclick="window.print();"><i class="fa fa-print"></i></button>
                        </form>
                    </div>
                    
                    <!-- Filter Row -->
                    <div class="filter-row">
                        <form method="get" class="form-inline" style="display: flex; gap: 10px; align-items: center;">
                            <input type="hidden" name="item_id" value="<?php echo $selected_item; ?>" />
                            <select name="year" class="form-control" onchange="this.form.submit()">
                                <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $selected_year == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
                            <select name="week" class="form-control" onchange="this.form.submit()">
                                <?php foreach ($weekOptions as $wNum => $wLabel): ?>
                                    <option value="<?php echo $wNum; ?>" <?php echo $selected_week == $wNum ? 'selected' : ''; ?>><?php echo $wLabel; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                    
                    <?php if ($selected_item == 0) { ?>
                        <div class="alert alert-info">Please select a product to view orders.</div>
                    <?php } elseif (empty($orders)) { ?>
                        <div class="alert alert-warning">No orders found for "<?php echo htmlspecialchars($selectedProductName); ?>" in Week <?php echo $selected_week; ?>.</div>
                    <?php } else { ?>
                        <table class="by-item-table" id="byItemTable">
                            <thead>
                                <tr>
                                    <th class="col-customer">Customer</th>
                                    <?php foreach ($weekDates as $wd): ?>
                                        <th class="col-day">
                                            <?php echo $wd['display']; ?>
                                        </th>
                                    <?php endforeach; ?>
                                    <th class="col-total">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $custId => $custData): 
                                    $avail = $customerAvailability[$custId] ?? null;
                                ?>
                                <tr>
                                    <td class="col-customer">
                                        <?php echo htmlspecialchars($custData['name']); ?>
                                        <?php if ($custData['address']): ?>
                                            <span style="color:#888;"><?php echo htmlspecialchars($custData['address']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <?php foreach ($weekDates as $wd): 
                                        $dayKey = $wd['day_short'];
                                        $isAvailable = ($avail === null) || (isset($avail[$dayKey]) && $avail[$dayKey] == 1);
                                        $orderQty = $custData['dates'][$wd['date']] ?? 0;
                                        $isCartOrder = isset($custData['cart_dates'][$wd['date']]);
                                    ?>
                                        <td>
                                            <?php if (!$isAvailable && $orderQty == 0): ?>
                                                <span class="unavailable">Unavailable</span>
                                            <?php elseif ($orderQty > 0): ?>
                                                <span class="order-qty"><?php if ($isCartOrder): ?><i class="fa fa-shopping-cart"></i><?php endif; ?><?php echo $orderQty; ?></span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <td class="total-col"><?php echo $custData['total']; ?></td>
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
    
    <script>
    function exportToExcel() {
        var table = document.getElementById('byItemTable');
        if (!table) {
            alert('No data to export');
            return;
        }
        
        var html = table.outerHTML;
        var blob = new Blob([html], { type: 'application/vnd.ms-excel' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'orders_per_item_<?php echo $selected_item; ?>_week<?php echo $selected_week; ?>.xls';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
    </script>
</body>
</html>
