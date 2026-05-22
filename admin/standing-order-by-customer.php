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
$selected_customer = isset($_GET['customer']) ? $_GET['customer'] : '';

// Calculate week start and end dates
$weekStart = new DateTime();
$weekStart->setISODate($selected_year, $selected_week, 1); // Monday
$weekEnd = clone $weekStart;
$weekEnd->modify('+6 days'); // Sunday

$weekStartStr = $weekStart->format('Y-m-d');
$weekEndStr = $weekEnd->format('Y-m-d');

// Generate array of dates for the week
$weekDates = [];
$dayNames = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
$currentDate = clone $weekStart;
for ($i = 0; $i < 7; $i++) {
    $weekDates[] = [
        'date' => $currentDate->format('Y-m-d'),
        'day_key' => $dayNames[$i],
        'day_name' => $currentDate->format('D')
    ];
    $currentDate->modify('+1 day');
}

// Fetch customers for dropdown (only customers with standing orders in the selected week)
$customers = $db->getRows("SELECT DISTINCT c.customer_id, c.customer_name 
                           FROM customer c 
                           JOIN invoice_hedder ih ON ih.invoice_h_customer_id = c.customer_id 
                           WHERE ih.invoice_h_delivery_date BETWEEN ? AND ?
                             AND (ih.invoice_h_order_note = 'Standing Order' OR ih.order_type = 'STANDING')
                           ORDER BY c.customer_name", [$weekStartStr, $weekEndStr]);

// Build customer filter
$customerFilter = '';
$params = [$weekStartStr, $weekEndStr];
if ($selected_customer !== '') {
    $customerFilter = ' AND c.customer_id = ?';
    $params[] = $selected_customer;
}

// Fetch standing orders grouped by customer, category, item, and date
$query = $db->getRows(
    "SELECT 
        c.customer_id,
        c.customer_name,
        COALESCE(csa.address_line_1, '') AS address_line_1,
        COALESCE(csa.address_line_2, '') AS address_line_2,
        COALESCE(csa.city, '') AS city,
        COALESCE(cm.category_name, 'Uncategorized') AS category_name,
        COALESCE(cm.category_id, 0) AS category_id,
        im.item_id,
        im.item_name,
        ih.invoice_h_delivery_date AS delivery_date,
        SUM(id.invoice_d_qty) AS total_qty,
        MAX(COALESCE(id.is_cart_item, 0)) AS is_cart_item
     FROM invoice_details id
     JOIN invoice_hedder ih ON ih.invoice_h_id = id.invoice_h_id
     JOIN customer c ON c.customer_id = ih.invoice_h_customer_id
     LEFT JOIN customer_shipping_address csa ON csa.customer_id = c.customer_id AND csa.is_default = 1
     JOIN item_master im ON im.item_id = id.invoice_d_item_id
     LEFT JOIN category_master cm ON cm.category_id = im.item_category
     WHERE ih.invoice_h_delivery_date BETWEEN ? AND ?
       AND ih.invoice_h_status = 1
       AND (ih.invoice_h_order_note = 'Standing Order' OR ih.order_type = 'STANDING')
       $customerFilter
     GROUP BY c.customer_id, cm.category_id, im.item_id, ih.invoice_h_delivery_date
     ORDER BY c.customer_name ASC, cm.category_name ASC, im.item_name ASC, ih.invoice_h_delivery_date ASC",
    $params
);

// Organize data by customer -> category -> item -> date
$data = [];
foreach ($query as $row) {
    $custId = $row['customer_id'];
    $custName = $row['customer_name'];
    $address = trim($row['address_line_1'] . ' ' . $row['address_line_2'] . ', ' . $row['city'], ', ');
    $catName = $row['category_name'];
    $catId = $row['category_id'];
    $itemId = $row['item_id'];
    $itemName = $row['item_name'];
    $date = $row['delivery_date'];
    $qty = (int)$row['total_qty'];
    $isCartItem = (int)$row['is_cart_item'];
    
    if (!isset($data[$custId])) {
        $data[$custId] = [
            'name' => $custName,
            'address' => $address,
            'categories' => [],
            'totals' => array_fill_keys($dayNames, 0),
            'grand_total' => 0
        ];
    }
    
    if (!isset($data[$custId]['categories'][$catId])) {
        $data[$custId]['categories'][$catId] = [
            'name' => $catName,
            'items' => []
        ];
    }
    
    if (!isset($data[$custId]['categories'][$catId]['items'][$itemId])) {
        $data[$custId]['categories'][$catId]['items'][$itemId] = [
            'name' => $itemName,
            'days' => array_fill_keys($dayNames, 0),
            'total' => 0,
            'is_cart_item' => 0
        ];
    }
    
    // Track if any row for this item is a cart item
    if ($isCartItem) {
        $data[$custId]['categories'][$catId]['items'][$itemId]['is_cart_item'] = 1;
    }
    
    // Find the day key for this date
    foreach ($weekDates as $wd) {
        if ($wd['date'] === $date) {
            $dayKey = $wd['day_key'];
            $data[$custId]['categories'][$catId]['items'][$itemId]['days'][$dayKey] += $qty;
            $data[$custId]['categories'][$catId]['items'][$itemId]['total'] += $qty;
            $data[$custId]['totals'][$dayKey] += $qty;
            $data[$custId]['grand_total'] += $qty;
            break;
        }
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
    <title>Standing Order by Customer</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
    
    <style>
        .standing-report {
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
        
        .standing-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .standing-table th {
            text-align: center;
            font-weight: normal;
            padding: 10px 8px;
            background: #f9f9f9;
            border-bottom: 2px solid #ddd;
            font-size: 11px;
            color: #666;
        }
        
        .standing-table th.col-group {
            text-align: left;
            width: 18%;
        }
        
        .standing-table th.col-item {
            text-align: left;
            width: 25%;
        }
        
        .standing-table th.col-day {
            width: 6%;
        }
        
        .standing-table th.col-total {
            width: 7%;
            font-weight: bold;
        }
        
        .standing-table td {
            padding: 6px 8px;
            vertical-align: middle;
            border-bottom: 1px solid #eee;
            font-size: 12px;
            text-align: center;
        }
        
        .standing-table td.col-group,
        .standing-table td.col-item {
            text-align: left;
        }
        
        .standing-table .customer-header {
            background: #e8f5e9;
            border-bottom: 2px solid #4CAF50;
        }
        
        .standing-table .customer-header td {
            padding: 10px 8px;
            font-weight: normal;
        }
        
        .standing-table .customer-header a {
            color: #1565C0;
            text-decoration: none;
            font-weight: 500;
        }
        
        .standing-table .customer-header a:hover {
            text-decoration: underline;
        }
        
        .standing-table .total-row {
            background: #fffde7;
        }
        
        .standing-table .total-row td {
            font-weight: bold;
            border-top: 1px solid #ddd;
            border-bottom: 2px solid #ddd;
            color: #333;
        }
        
        .standing-table .item-row:hover {
            background: #f5f5f5;
        }
        
        .standing-table .text-right {
            text-align: right;
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
            
            .no-print, .report-header {
                display: none !important;
            }
            
            .standing-report {
                padding: 5mm;
            }
            
            .standing-report::before {
                content: "Standing Order by Customer - <?php echo $formattedWeekRange; ?>";
                display: block;
                font-size: 16px;
                font-weight: bold;
                margin-bottom: 15px;
            }
            
            .standing-table th,
            .standing-table td {
                padding: 3px 4px;
                font-size: 9px;
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
                            <span>Standing Order by Customer</span>
                        </li>
                    </ul>
                </div>
                
                <!-- Report Content -->
                <div class="standing-report">
                    <!-- Report Header -->
                    <div class="report-header">
                        <h1>Standing Order by Customer</h1>
                        <form method="get" class="form-inline" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
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
                            <select name="customer" class="form-control" onchange="this.form.submit()">
                                <option value="">Show All</option>
                                <?php foreach ($customers as $cust): ?>
                                    <option value="<?php echo $cust['customer_id']; ?>" <?php echo $selected_customer == $cust['customer_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cust['customer_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn-export" onclick="exportToExcel();"><i class="fa fa-file-excel-o"></i> Export</button>
                            <button type="button" class="btn btn-default" onclick="window.print();"><i class="fa fa-print"></i></button>
                        </form>
                    </div>
                    
                    <?php if (empty($data)) { ?>
                        <div class="alert alert-info">No standing orders found for Week <?php echo $selected_week; ?>, <?php echo $selected_year; ?>.</div>
                    <?php } else { ?>
                        <table class="standing-table" id="standingTable">
                            <thead>
                                <tr>
                                    <th class="col-group">Item Group</th>
                                    <th class="col-item">Item</th>
                                    <?php foreach ($weekDates as $wd): ?>
                                        <th class="col-day"><?php echo $wd['day_name']; ?></th>
                                    <?php endforeach; ?>
                                    <th class="col-total">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data as $custId => $custData): ?>
                                    <!-- Customer Header Row -->
                                    <tr class="customer-header">
                                        <td colspan="9">
                                            <a href="customer_view.php?customer_id=<?php echo $custId; ?>"><?php echo htmlspecialchars($custData['name']); ?> - <?php echo htmlspecialchars($custData['address']); ?></a>
                                        </td>
                                    </tr>
                                    
                                    <?php foreach ($custData['categories'] as $catId => $catData): 
                                        $isFirstItem = true;
                                    ?>
                                        <?php foreach ($catData['items'] as $itemId => $item): ?>
                                        <tr class="item-row">
                                            <td class="col-group"><?php echo $isFirstItem ? htmlspecialchars($catData['name']) : ''; $isFirstItem = false; ?></td>
                                            <td class="col-item">
                                                <?php echo htmlspecialchars($item['name']); ?>
                                                <?php if (!empty($item['is_cart_item'])): ?>
                                                    <i class="fa fa-shopping-cart" style="color:#f39c12; margin-left:5px;" title="Cart Item"></i>
                                                <?php endif; ?>
                                            </td>
                                            <?php foreach ($dayNames as $dayKey): ?>
                                                <td><?php echo $item['days'][$dayKey] > 0 ? $item['days'][$dayKey] : ''; ?></td>
                                            <?php endforeach; ?>
                                            <td class="text-right"><?php echo $item['total']; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                    
                                    <!-- Customer Total Row -->
                                    <tr class="total-row">
                                        <td colspan="2"><strong>Total</strong></td>
                                        <?php foreach ($dayNames as $dayKey): ?>
                                            <td><?php echo $custData['totals'][$dayKey] > 0 ? $custData['totals'][$dayKey] : ''; ?></td>
                                        <?php endforeach; ?>
                                        <td class="text-right"><?php echo $custData['grand_total']; ?></td>
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
        var table = document.getElementById('standingTable');
        if (!table) {
            alert('No data to export');
            return;
        }
        
        var html = table.outerHTML;
        var blob = new Blob([html], { type: 'application/vnd.ms-excel' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'standing_order_by_customer_week<?php echo $selected_week; ?>_<?php echo $selected_year; ?>.xls';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
    </script>
</body>
</html>
