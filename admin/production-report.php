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
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'delivery'; // 'production' or 'delivery'
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

// If filtering by production date, delivery date = production date + 1 day
if ($filter_type === 'production') {
    $delivery_date_from = date('Y-m-d', strtotime($date_from . ' +1 day'));
    $delivery_date_to = date('Y-m-d', strtotime($date_to . ' +1 day'));
} else {
    $delivery_date_from = $date_from;
    $delivery_date_to = $date_to;
}

// Format dates for display
$display_date_from = date('d/m/Y', strtotime($date_from));
$display_date_to = date('d/m/Y', strtotime($date_to));

// Query all order items for the delivery date range with product details
$query = $db->getRows(
    "SELECT 
        ih.invoice_h_delivery_date,
        ih.invoice_h_delivery_time,
        im.item_id,
        im.item_name,
        im.item_weight,
        im.item_weight_g,
        im.pack_weight_g,
        im.pack_size,
        im.pack_type,
        im.unit_of_measure,
        COALESCE(cm.category_name, 'Uncategorized') AS category_name,
        COALESCE(cm.category_id, 0) AS category_id,
        COALESCE(tm.type_name, 'Uncategorized') AS type_name,
        SUM(id.invoice_d_qty) AS total_qty
     FROM invoice_details id
     JOIN invoice_hedder ih ON ih.invoice_h_id = id.invoice_h_id
     JOIN item_master im ON im.item_id = id.invoice_d_item_id
     LEFT JOIN category_master cm ON cm.category_id = im.item_category
     LEFT JOIN type_master tm ON tm.type_id = im.item_type
     WHERE ih.invoice_h_delivery_date BETWEEN ? AND ?
       AND ih.invoice_h_status = 1
       AND im.is_raw_material = 0
     GROUP BY ih.invoice_h_delivery_date, ih.invoice_h_delivery_time, im.item_id
     ORDER BY ih.invoice_h_delivery_time ASC, cm.category_name ASC, im.item_name ASC",
    [$delivery_date_from, $delivery_date_to]
);

/**
 * Resolve the display weight for a product item.
 * 
 * Weight priority cascade:
 *   1. item_weight_g  (grams, set via "Weight (grams)" field in product edit)
 *   2. pack_weight_g  (grams, set via "Pack Weight (grams)" field)
 *   3. item_weight    (always stored in KG as per "Product Weight (Kg)" label)
 *
 * Returns: ['value_g' => int grams for calculation, 'display' => 'xxx g' string]
 */
function resolveItemWeight($row) {
    // Priority 1: item_weight_g (grams) — most specific per-item weight
    if (!empty($row['item_weight_g']) && $row['item_weight_g'] > 0) {
        $grams = (int)$row['item_weight_g'];
        return ['value_g' => $grams, 'display' => number_format($grams) . ' g'];
    }
    
    // Priority 2: pack_weight_g (grams) — pack-level weight
    if (!empty($row['pack_weight_g']) && $row['pack_weight_g'] > 0) {
        $grams = (int)$row['pack_weight_g'];
        return ['value_g' => $grams, 'display' => number_format($grams) . ' g'];
    }
    
    // Priority 3: item_weight (stored in KG per the form label "Product Weight (Kg)")
    if (isset($row['item_weight']) && floatval($row['item_weight']) > 0) {
        $kg = floatval($row['item_weight']);
        $grams = (int)round($kg * 1000);
        return ['value_g' => $grams, 'display' => number_format($grams) . ' g'];
    }
    
    // No weight available
    return ['value_g' => 0, 'display' => '-'];
}

// Organize data: time_slot -> items grouped by category (prep recipe)
$timeSlots = [];
$grandTotalWeight = 0;
$grandTotalPieces = 0;
$datesSummary = [];

foreach ($query as $row) {
    $deliveryDate = $row['invoice_h_delivery_date'];
    $deliveryTime = $row['invoice_h_delivery_time'] ?: 'No Time';
    $timeSlotKey = $deliveryTime . ' ' . date('d/m/Y', strtotime($deliveryDate));
    
    $qty = (int)$row['total_qty'];
    $packSize = is_numeric($row['pack_size']) ? (int)$row['pack_size'] : 0;
    
    // Calculate weight using the priority cascade
    $weightInfo = resolveItemWeight($row);
    $itemWeightG = $weightInfo['value_g'];
    $weightDisplay = $weightInfo['display'];
    
    $totalItemWeight = $qty * $itemWeightG;
    $grandTotalWeight += $totalItemWeight;
    $grandTotalPieces += $qty;
    
    // Track per-date summary
    if (!isset($datesSummary[$deliveryDate])) {
        $datesSummary[$deliveryDate] = ['weight' => 0, 'pieces' => 0];
    }
    $datesSummary[$deliveryDate]['weight'] += $totalItemWeight;
    $datesSummary[$deliveryDate]['pieces'] += $qty;
    
    // Build order text (e.g. "10 Omega-Red" or "580 Buckwheat Burger [4x145] Buns (Pack of 4)")
    $itemName = $row['item_name'];
    if ($packSize > 1 && $qty > 0) {
        $numPacks = ceil($qty / $packSize);
        $packBreakdown = "[{$packSize}x{$numPacks}]";
        $packPos = stripos($itemName, '(Pack');
        if ($packPos !== false) {
            $orderText = $qty . ' ' . trim(substr($itemName, 0, $packPos)) . ' ' . $packBreakdown . ' ' . substr($itemName, $packPos);
        } else {
            $orderText = $qty . ' ' . $packBreakdown . ' ' . $itemName;
        }
    } else {
        $orderText = $qty . ' ' . $itemName;
    }
    
    if (!isset($timeSlots[$timeSlotKey])) {
        $timeSlots[$timeSlotKey] = [
            'delivery_time' => $deliveryTime,
            'delivery_date' => $deliveryDate,
            'categories' => [],
            'total_weight' => 0,
            'total_pieces' => 0
        ];
    }
    
    $catName = $row['category_name'];
    if (!isset($timeSlots[$timeSlotKey]['categories'][$catName])) {
        $timeSlots[$timeSlotKey]['categories'][$catName] = [];
    }
    
    $timeSlots[$timeSlotKey]['categories'][$catName][] = [
        'item_id' => $row['item_id'],
        'item_name' => $itemName,
        'total_qty' => $qty,
        'order_text' => $orderText,
        'item_weight_g' => $itemWeightG,
        'weight_display' => $weightDisplay,
        'pack_size' => $packSize,
        'delivery_date' => $deliveryDate,
        'category_name' => $catName,
        'type_name' => $row['type_name']
    ];
    
    $timeSlots[$timeSlotKey]['total_weight'] += $totalItemWeight;
    $timeSlots[$timeSlotKey]['total_pieces'] += $qty;
}

// Grand total weight in kg
$grandTotalWeightKg = number_format($grandTotalWeight / 1000, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Production Report - <?php echo $display_date_from; ?></title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
    
    <!-- Daterangepicker -->
    <link rel="stylesheet" type="text/css" href="assets/global/plugins/bootstrap-daterangepicker/daterangepicker.min.css" />
    
    <style>
        /* ======= PRODUCTION REPORT STYLES ======= */
        .production-report-wrapper {
            font-family: 'Open Sans', Arial, sans-serif;
            font-size: 13px;
            color: #333;
        }
        
        /* Top Filter Bar */
        .report-filter-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 15px;
            background: #fff;
            border: 1px solid #e1e1e1;
            border-radius: 4px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        
        .report-filter-bar .filter-label {
            font-weight: 600;
            font-size: 15px;
            color: #333;
            white-space: nowrap;
        }
        
        .report-filter-bar .date-range-input {
            padding: 6px 12px;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 13px;
            cursor: pointer;
            background: #fff;
            min-width: 220px;
        }
        
        .report-filter-bar .filter-select {
            padding: 6px 12px;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 13px;
            background: #fff;
        }
        
        .report-filter-bar .filter-actions {
            margin-left: auto;
            display: flex;
            gap: 6px;
            align-items: center;
        }
        
        .btn-more {
            background: #4CAF50;
            color: #fff;
            border: none;
            padding: 6px 16px;
            border-radius: 3px;
            font-size: 13px;
            cursor: pointer;
        }
        
        .btn-more:hover {
            background: #45a049;
        }
        
        .btn-icon {
            background: none;
            border: 1px solid #ccc;
            border-radius: 3px;
            padding: 5px 10px;
            cursor: pointer;
            color: #666;
            font-size: 14px;
        }
        
        .btn-icon:hover {
            background: #f5f5f5;
        }
        
        /* Time Slot Accordion */
        .time-slot-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            background: #f9f9f9;
            border: 1px solid #e1e1e1;
            border-radius: 4px;
            margin-bottom: 0;
            cursor: pointer;
            user-select: none;
        }
        
        .time-slot-header:hover {
            background: #f0f0f0;
        }
        
        .time-slot-header .btn-remove {
            color: #e67e22;
            font-weight: bold;
            cursor: pointer;
            font-size: 16px;
            background: none;
            border: none;
            padding: 0 5px;
        }

        .time-slot-header .btn-remove:hover {
            color: #d35400;
        }
        
        .time-slot-header .toggle-icon {
            color: #4CAF50;
            font-size: 12px;
            transition: transform 0.2s;
        }
        
        .time-slot-header .toggle-icon.collapsed {
            transform: rotate(-90deg);
        }
        
        .time-slot-header .slot-label {
            font-weight: 600;
            font-size: 14px;
            color: #333;
        }
        
        .time-slot-content {
            border: 1px solid #e1e1e1;
            border-top: none;
            border-radius: 0 0 4px 4px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        /* Summary & Table Layout */
        .report-body {
            display: flex;
            gap: 0;
        }
        
        /* Left Summary Panel */
        .summary-panel {
            min-width: 140px;
            max-width: 160px;
            padding: 12px 10px;
            border-right: 1px solid #e1e1e1;
            background: #fafafa;
            font-size: 12px;
        }
        
        .summary-date {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 4px;
        }
        
        .summary-date .btn-remove-date {
            color: #e67e22;
            font-weight: bold;
            cursor: pointer;
            font-size: 14px;
            background: none;
            border: none;
            padding: 0;
        }
        
        .summary-date .date-label {
            font-weight: 700;
            font-size: 13px;
            color: #333;
        }
        
        .summary-weight {
            padding-left: 8px;
            color: #555;
            font-size: 12px;
            margin-bottom: 2px;
        }
        
        .summary-weight::before {
            content: '• ';
            color: #999;
        }
        
        .summary-pieces {
            padding-left: 8px;
            font-weight: 600;
            font-size: 13px;
            color: #333;
        }
        
        /* Main Data Table */
        .report-table-container {
            flex: 1;
            overflow-x: auto;
        }
        
        .production-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        
        .production-table thead th {
            padding: 8px 6px;
            text-align: left;
            font-weight: 600;
            font-size: 11px;
            color: #666;
            background: #fafafa;
            border-bottom: 2px solid #ddd;
            white-space: nowrap;
            position: relative;
        }
        
        .production-table thead th .col-search {
            color: #aaa;
            cursor: pointer;
            margin-left: 3px;
            font-size: 11px;
        }
        
        .production-table thead th .col-search:hover {
            color: #4CAF50;
        }
        
        .production-table thead th.col-prep-recipe { width: 14%; }
        .production-table thead th.col-boxes { width: 5%; text-align: center; }
        .production-table thead th.col-trays { width: 5%; text-align: center; }
        .production-table thead th.col-late { width: 4%; text-align: center; }
        .production-table thead th.col-order { width: 22%; }
        .production-table thead th.col-parent { width: 14%; }
        .production-table thead th.col-item { width: 16%; }
        .production-table thead th.col-weight { width: 8%; text-align: right; }
        .production-table thead th.col-dist-date { width: 10%; text-align: center; }
        
        .production-table tbody td {
            padding: 7px 6px;
            vertical-align: middle;
            border-bottom: 1px solid #eee;
            font-size: 12px;
            color: #333;
        }
        
        .production-table tbody tr:hover {
            background: #f8fff8;
        }
        
        .production-table tbody td.text-center { text-align: center; }
        .production-table tbody td.text-right { text-align: right; }
        
        .production-table tbody td.col-prep-recipe {
            color: #333;
            font-weight: 500;
        }
        
        .production-table tbody td.col-order {
            color: #555;
        }
        
        .production-table tbody td.col-parent {
            color: #999;
            font-style: italic;
        }
        
        .production-table tbody td.col-item {
            font-weight: 500;
        }
        
        .production-table tbody td.col-weight {
            text-align: right;
            font-weight: 600;
        }
        
        .production-table tbody td.col-dist-date {
            text-align: center;
            color: #555;
        }
        
        /* Column search input */
        .col-search-input {
            display: none;
            width: 100%;
            padding: 3px 5px;
            font-size: 11px;
            border: 1px solid #ccc;
            border-radius: 3px;
            margin-top: 3px;
        }
        
        .col-search-input.active {
            display: block;
        }
        
        /* Print icon in header */
        .print-btn-float {
            position: absolute;
            right: 10px;
            top: 10px;
        }
        
        /* Responsive */
        
        @media print {
            body {
                margin: 0;
                padding: 0;
                font-size: 10px;
            }
            
            .no-print,
            .report-filter-bar,
            .page-sidebar-wrapper,
            .page-bar,
            .page-header,
            .page-footer,
            .btn-remove,
            .btn-remove-date,
            .col-search,
            .col-search-input {
                display: none !important;
            }
            
            .page-content {
                margin: 0 !important;
                padding: 5px !important;
            }
            
            .page-container,
            .page-content-wrapper {
                margin: 0 !important;
                padding: 0 !important;
            }
            
            .time-slot-header {
                background: #f0f0f0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .production-table {
                font-size: 10px;
            }
            
            .production-table thead th,
            .production-table tbody td {
                padding: 4px 5px;
            }
            
            .summary-panel {
                min-width: 100px;
            }
        }
        
        @page {
            size: A4 landscape;
            margin: 8mm;
        }
        
        /* Late badge */
        .late-badge {
            display: inline-block;
            background: #ff9800;
            color: #fff;
            border-radius: 3px;
            padding: 1px 5px;
            font-size: 10px;
            font-weight: 600;
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 10px;
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
                <!-- Page Breadcrumb -->
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
                            <span>Production Report</span>
                        </li>
                    </ul>
                </div>
                
                <div class="production-report-wrapper">
                    <!-- Filter Bar -->
                    <form method="get" id="filterForm">
                        <div class="report-filter-bar">
                            <span class="filter-label">By Production Date</span>
                            <input type="text" 
                                   class="date-range-input" 
                                   id="dateRangePicker" 
                                   value="<?php echo $display_date_from . ' - ' . $display_date_to; ?>" 
                                   readonly />
                            <input type="hidden" name="date_from" id="dateFrom" value="<?php echo $date_from; ?>" />
                            <input type="hidden" name="date_to" id="dateTo" value="<?php echo $date_to; ?>" />
                            
                            <select name="filter_type" class="filter-select" id="filterType" onchange="document.getElementById('filterForm').submit();">
                                <option value="delivery" <?php echo $filter_type === 'delivery' ? 'selected' : ''; ?>>By Delivery Date</option>
                                <option value="production" <?php echo $filter_type === 'production' ? 'selected' : ''; ?>>By Production Date</option>
                            </select>
                            
                            <div class="filter-actions">
                                <button type="submit" class="btn-more">
                                    <i class="fa fa-search"></i> Generate
                                </button>
                                <button type="button" class="btn-icon" onclick="window.print();" title="Print">
                                    <i class="fa fa-print"></i>
                                </button>
                                <button type="button" class="btn-icon" onclick="exportReport();" title="Export Excel">
                                    <i class="fa fa-file-excel-o"></i>
                                </button>
                                <button type="button" class="btn-icon" title="Help">
                                    <i class="fa fa-question-circle"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <?php if (empty($timeSlots)): ?>
                        <!-- Empty State -->
                        <div class="empty-state">
                            <i class="fa fa-inbox"></i>
                            <h4>No Orders Found</h4>
                            <p>No accepted orders found for the selected date range.<br>
                            Try changing the date filter or check if orders have been accepted.</p>
                        </div>
                    <?php else: ?>
                        <?php $slotIndex = 0; foreach ($timeSlots as $slotKey => $slotData): $slotIndex++; ?>
                        <!-- Time Slot Accordion -->
                        <div class="time-slot-section" id="slot-<?php echo $slotIndex; ?>">
                            <div class="time-slot-header" onclick="toggleSlot(<?php echo $slotIndex; ?>)">
                                <button type="button" class="btn-remove" onclick="event.stopPropagation(); removeSlot(<?php echo $slotIndex; ?>);" title="Remove">&times;</button>
                                <i class="fa fa-caret-down toggle-icon" id="toggle-icon-<?php echo $slotIndex; ?>"></i>
                                <span class="slot-label"><?php echo htmlspecialchars($slotKey); ?></span>
                            </div>
                            
                            <div class="time-slot-content" id="slot-content-<?php echo $slotIndex; ?>">
                                <div class="report-body">
                                    <!-- Left Summary Panel -->
                                    <div class="summary-panel">
                                        <?php 
                                        // Get unique delivery dates for this slot
                                        $slotDates = [];
                                        foreach ($slotData['categories'] as $catItems) {
                                            foreach ($catItems as $item) {
                                                $d = $item['delivery_date'];
                                                if (!isset($slotDates[$d])) {
                                                    $slotDates[$d] = ['weight' => 0, 'pieces' => 0];
                                                }
                                                $slotDates[$d]['weight'] += $item['total_qty'] * $item['item_weight_g'];
                                                $slotDates[$d]['pieces'] += $item['total_qty'];
                                            }
                                        }
                                        foreach ($slotDates as $dd => $dSummary): 
                                        ?>
                                        <div class="summary-block" style="margin-bottom: 10px;">
                                            <div class="summary-date">
                                                <button type="button" class="btn-remove-date" title="Remove">&times;</button>
                                                <span class="date-label"><?php echo date('d/m/Y', strtotime($dd)); ?></span>
                                            </div>
                                            <div class="summary-weight">
                                                <?php echo number_format($dSummary['weight'] / 1000, 2); ?> (kg)
                                            </div>
                                            <div class="summary-pieces">
                                                <?php echo number_format($dSummary['pieces']); ?> Pieces
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <!-- Main Data Table -->
                                    <div class="report-table-container">
                                        <table class="production-table" id="prodTable-<?php echo $slotIndex; ?>">
                                            <thead>
                                                <tr>
                                                    <th class="col-prep-recipe">
                                                        Prep Recipe 
                                                        <i class="fa fa-search col-search" onclick="event.stopPropagation(); toggleColSearch(this);"></i>
                                                        <input type="text" class="col-search-input" placeholder="Search..." onkeyup="filterTableColumn(this, <?php echo $slotIndex; ?>, 0)" />
                                                    </th>
                                                    <th class="col-boxes">Boxes</th>
                                                    <th class="col-trays">Trays</th>
                                                    <th class="col-late"><span style="color: #4CAF50;">+</span><br>Late</th>
                                                    <th class="col-order">
                                                        Order
                                                        <i class="fa fa-search col-search" onclick="event.stopPropagation(); toggleColSearch(this);"></i>
                                                        <input type="text" class="col-search-input" placeholder="Search..." onkeyup="filterTableColumn(this, <?php echo $slotIndex; ?>, 4)" />
                                                    </th>
                                                    <th class="col-parent">
                                                        Parent
                                                        <i class="fa fa-search col-search" onclick="event.stopPropagation(); toggleColSearch(this);"></i>
                                                        <input type="text" class="col-search-input" placeholder="Search..." onkeyup="filterTableColumn(this, <?php echo $slotIndex; ?>, 5)" />
                                                    </th>
                                                    <th class="col-item">
                                                        Item
                                                        <i class="fa fa-search col-search" onclick="event.stopPropagation(); toggleColSearch(this);"></i>
                                                        <input type="text" class="col-search-input" placeholder="Search..." onkeyup="filterTableColumn(this, <?php echo $slotIndex; ?>, 6)" />
                                                    </th>
                                                    <th class="col-weight">Item<br>Weight</th>
                                                    <th class="col-dist-date">Distribution Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                foreach ($slotData['categories'] as $catName => $items):
                                                    $catFirst = true;
                                                    foreach ($items as $idx => $item):
                                                        $isFirstInGroup = ($idx === 0);
                                                ?>
                                                <tr data-category="<?php echo htmlspecialchars($catName); ?>">
                                                    <td class="col-prep-recipe">
                                                        <?php echo $catFirst ? htmlspecialchars($catName) : ''; ?>
                                                        <?php $catFirst = false; ?>
                                                    </td>
                                                    <td class="text-center"><!-- Boxes --></td>
                                                    <td class="text-center"><!-- Trays --></td>
                                                    <td class="text-center"><!-- Late --></td>
                                                    <td class="col-order">
                                                        <?php echo htmlspecialchars($item['order_text']); ?>
                                                    </td>
                                                    <td class="col-parent">
                                                        <!-- Parent: Not implemented yet -->
                                                    </td>
                                                    <td class="col-item">
                                                        <?php echo htmlspecialchars($item['item_name']); ?>
                                                    </td>
                                                    <td class="col-weight">
                                                        <?php echo '<strong>' . htmlspecialchars($item['weight_display']) . '</strong>'; ?>
                                                    </td>
                                                    <td class="col-dist-date">
                                                        <?php echo date('d/m/Y', strtotime($item['delivery_date'])); ?>
                                                    </td>
                                                </tr>
                                                <?php 
                                                    endforeach;
                                                endforeach; 
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include('common/footer.php'); ?>
    
    <!-- Daterangepicker JS -->
    <script type="text/javascript" src="assets/global/plugins/moment.min.js"></script>
    <script type="text/javascript" src="assets/global/plugins/bootstrap-daterangepicker/daterangepicker.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Initialize daterangepicker
        $('#dateRangePicker').daterangepicker({
            locale: {
                format: 'DD/MM/YYYY',
                separator: ' - '
            },
            startDate: moment('<?php echo $date_from; ?>', 'YYYY-MM-DD'),
            endDate: moment('<?php echo $date_to; ?>', 'YYYY-MM-DD'),
            opens: 'right',
            autoUpdateInput: true
        }, function(start, end) {
            $('#dateFrom').val(start.format('YYYY-MM-DD'));
            $('#dateTo').val(end.format('YYYY-MM-DD'));
            $('#dateRangePicker').val(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
        });
        
        // Update filter label based on selection
        updateFilterLabel();
        
        $('#filterType').on('change', function() {
            updateFilterLabel();
        });
    });
    
    function updateFilterLabel() {
        var filterType = $('#filterType').val();
        var label = filterType === 'production' ? 'By Production Date' : 'By Delivery Date';
        $('.filter-label').text(label);
    }
    
    // Toggle time slot collapse
    function toggleSlot(slotId) {
        var content = document.getElementById('slot-content-' + slotId);
        var icon = document.getElementById('toggle-icon-' + slotId);
        
        if (content.style.display === 'none') {
            content.style.display = 'block';
            icon.classList.remove('collapsed');
        } else {
            content.style.display = 'none';
            icon.classList.add('collapsed');
        }
    }
    
    // Remove a time slot section
    function removeSlot(slotId) {
        var section = document.getElementById('slot-' + slotId);
        if (section) {
            section.style.display = 'none';
        }
    }
    
    // Toggle column search input
    function toggleColSearch(icon) {
        var input = icon.nextElementSibling;
        if (input.classList.contains('active')) {
            input.classList.remove('active');
            input.value = '';
            // Reset filter
            var tableId = input.closest('table').id;
            var slotIdx = tableId.replace('prodTable-', '');
            resetTableFilter(slotIdx);
        } else {
            input.classList.add('active');
            input.focus();
        }
    }
    
    // Filter table by column
    function filterTableColumn(input, slotIdx, colIdx) {
        var filter = input.value.toLowerCase();
        var table = document.getElementById('prodTable-' + slotIdx);
        var rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(function(row) {
            var cell = row.cells[colIdx];
            if (cell) {
                var text = cell.textContent || cell.innerText;
                // Also check data-category attribute for prep recipe grouping
                if (colIdx === 0 && filter) {
                    var category = row.getAttribute('data-category') || '';
                    row.style.display = category.toLowerCase().indexOf(filter) > -1 ? '' : 'none';
                } else {
                    row.style.display = text.toLowerCase().indexOf(filter) > -1 ? '' : 'none';
                }
            }
        });
    }
    
    // Reset all column filters for a table
    function resetTableFilter(slotIdx) {
        var table = document.getElementById('prodTable-' + slotIdx);
        var rows = table.querySelectorAll('tbody tr');
        rows.forEach(function(row) {
            row.style.display = '';
        });
    }
    
    // Export to Excel
    function exportReport() {
        var tables = document.querySelectorAll('.production-table');
        if (tables.length === 0) {
            alert('No data to export');
            return;
        }
        
        var html = '<html><head><meta charset="utf-8"></head><body>';
        html += '<h2>Production Report - <?php echo $display_date_from . " to " . $display_date_to; ?></h2>';
        
        tables.forEach(function(table) {
            html += table.outerHTML;
            html += '<br/>';
        });
        
        html += '</body></html>';
        
        var blob = new Blob([html], { type: 'application/vnd.ms-excel' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'production_report_<?php echo $date_from; ?>_to_<?php echo $date_to; ?>.xls';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
    </script>
</body>
</html>
