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

// Fetch all orders grouped by category, item, and date
$query = $db->getRows(
    "SELECT 
        COALESCE(cm.category_name, 'Uncategorized') AS category_name,
        COALESCE(cm.category_id, 0) AS category_id,
        im.item_id,
        im.item_name,
        ih.invoice_h_delivery_date AS delivery_date,
        COUNT(DISTINCT ih.invoice_h_customer_id) AS customer_count,
        SUM(id.invoice_d_qty) AS total_qty
     FROM invoice_details id
     JOIN invoice_hedder ih ON ih.invoice_h_id = id.invoice_h_id
     JOIN item_master im ON im.item_id = id.invoice_d_item_id
     LEFT JOIN category_master cm ON cm.category_id = im.item_category
     WHERE ih.invoice_h_delivery_date BETWEEN ? AND ?
       AND ih.invoice_h_status = 1
     GROUP BY cm.category_id, im.item_id, ih.invoice_h_delivery_date
     ORDER BY cm.category_name ASC, im.item_name ASC, ih.invoice_h_delivery_date ASC",
    [$weekStartStr, $weekEndStr]
);

// Organize data by category -> item -> date
$data = [];
foreach ($query as $row) {
    $catName = $row['category_name'];
    $catId = $row['category_id'];
    $itemId = $row['item_id'];
    $itemName = $row['item_name'];
    $date = $row['delivery_date'];
    $qty = (int)$row['total_qty'];
    $custCount = (int)$row['customer_count'];
    
    if (!isset($data[$catName])) {
        $data[$catName] = [
            'category_id' => $catId,
            'items' => [],
            'totals' => array_fill_keys($dayNames, ['qty' => 0, 'customers' => 0]),
            'grand_total' => 0
        ];
    }
    
    if (!isset($data[$catName]['items'][$itemId])) {
        $data[$catName]['items'][$itemId] = [
            'name' => $itemName,
            'days' => array_fill_keys($dayNames, ['qty' => 0, 'customers' => 0]),
            'total' => 0
        ];
    }
    
    // Find the day key for this date
    foreach ($weekDates as $wd) {
        if ($wd['date'] === $date) {
            $dayKey = $wd['day_key'];
            $data[$catName]['items'][$itemId]['days'][$dayKey] = [
                'qty' => $qty,
                'customers' => $custCount
            ];
            $data[$catName]['items'][$itemId]['total'] += $qty;
            
            // Update category totals
            $data[$catName]['totals'][$dayKey]['qty'] += $qty;
            $data[$catName]['totals'][$dayKey]['customers'] += $custCount;
            $data[$catName]['grand_total'] += $qty;
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

// Format display value with customer count
function formatQtyDisplay($qty, $customers) {
    if ($qty == 0) return '';
    if ($customers <= 1) return (string)$qty;
    $avg = $customers > 0 ? round($qty / $customers) : 0;
    return $qty . ' <span class="customer-count">[' . $customers . 'x' . $avg . ']</span>';
}

$formattedWeekRange = $weekStart->format('D d/m') . ' - ' . $weekEnd->format('D d/m');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Total Standing Orders</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
    
    <style>
        .standing-orders-report {
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
            width: 15%;
        }
        
        .standing-table th.col-item {
            text-align: left;
            width: 25%;
        }
        
        .standing-table th.col-day {
            width: 8%;
        }
        
        .standing-table th.col-total {
            width: 7%;
            font-weight: bold;
        }
        
        .standing-table td {
            padding: 8px;
            vertical-align: middle;
            border-bottom: 1px solid #eee;
            font-size: 12px;
            text-align: center;
        }
        
        .standing-table td.col-group,
        .standing-table td.col-item {
            text-align: left;
        }
        
        .standing-table .customer-count {
            color: #4CAF50;
            font-size: 10px;
        }
        
        .standing-table .total-row {
            background: #f5f5f5;
            font-weight: bold;
        }
        
        .standing-table .total-row td {
            border-top: 1px solid #ccc;
            border-bottom: 2px solid #ccc;
        }
        
        .standing-table .item-row:hover {
            background: #fffde7;
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
            
            .standing-orders-report {
                padding: 5mm;
            }
            
            .standing-orders-report::before {
                content: "Total Standing Orders - <?php echo $formattedWeekRange; ?>";
                display: block;
                font-size: 16px;
                font-weight: bold;
                margin-bottom: 15px;
            }
            
            .standing-table th,
            .standing-table td {
                padding: 4px 5px;
                font-size: 9px;
            }
            
            .standing-table .customer-count {
                font-size: 8px;
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
                            <span>Total Standing Orders</span>
                        </li>
                    </ul>
                </div>
                
                <!-- Report Content -->
                <div class="standing-orders-report">
                    <!-- Report Header -->
                    <div class="report-header">
                        <h1>Total Standing Orders</h1>
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
                                <?php foreach ($data as $catName => $catData): 
                                    $isFirstItem = true;
                                ?>
                                    <?php foreach ($catData['items'] as $itemId => $item): ?>
                                    <tr class="item-row">
                                        <td class="col-group"><?php echo $isFirstItem ? htmlspecialchars($catName) : ''; $isFirstItem = false; ?></td>
                                        <td class="col-item"><?php echo htmlspecialchars($item['name']); ?></td>
                                        <?php foreach ($dayNames as $dayKey): 
                                            $dayData = $item['days'][$dayKey];
                                        ?>
                                            <td><?php echo formatQtyDisplay($dayData['qty'], $dayData['customers']); ?></td>
                                        <?php endforeach; ?>
                                        <td class="text-right"><strong><?php echo $item['total']; ?></strong></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <!-- Category Total Row -->
                                    <tr class="total-row">
                                        <td></td>
                                        <td><strong>Total</strong></td>
                                        <?php foreach ($dayNames as $dayKey): 
                                            $totData = $catData['totals'][$dayKey];
                                        ?>
                                            <td><strong><?php echo formatQtyDisplay($totData['qty'], $totData['customers']); ?></strong></td>
                                        <?php endforeach; ?>
                                        <td class="text-right"><strong><?php echo $catData['grand_total']; ?></strong></td>
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
        a.download = 'total_standing_orders_week<?php echo $selected_week; ?>_<?php echo $selected_year; ?>.xls';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
    </script>
</body>
</html>
