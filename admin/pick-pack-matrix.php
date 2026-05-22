<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');

date_default_timezone_set("Australia/Melbourne");

$db = new Database();

// Get currency
$getcurrency = $db->getRow('SELECT * FROM currency WHERE activated = ? LIMIT 1', ["Y"]);
$currency = $getcurrency['currency'] ?? '$';

// Default to today's date if not specified
$report_date = isset($_GET['report_date']) ? $_GET['report_date'] : date('Y-m-d');
$formatted_date = date('d/m/Y', strtotime($report_date));

// Get all customers who have orders on the selected date
function getCustomersWithOrders($db, $date) {
    $query = $db->getRows(
        'SELECT DISTINCT c.customer_id, c.customer_name 
         FROM customer c 
         INNER JOIN invoice_hedder ih ON ih.invoice_h_customer_id = c.customer_id 
         WHERE ih.invoice_h_delivery_date = ? AND ih.invoice_h_status = 1
         ORDER BY c.customer_name ASC',
        [$date]
    );
    return $query ?: [];
}

// Get all products with orders on the selected date, grouped by type
function getProductsWithOrders($db, $date) {
    $query = $db->getRows(
        'SELECT DISTINCT im.item_id, im.item_name, im.item_category, 
                cm.category_name, tm.type_name
         FROM item_master im
         INNER JOIN invoice_details id ON id.invoice_d_item_id = im.item_id
         INNER JOIN invoice_hedder ih ON ih.invoice_h_id = id.invoice_h_id
         LEFT JOIN category_master cm ON cm.category_id = im.item_category
         LEFT JOIN type_master tm ON tm.type_id = im.item_type
         WHERE ih.invoice_h_delivery_date = ? AND ih.invoice_h_status = 1
         ORDER BY tm.type_name ASC, im.item_name ASC',
        [$date]
    );
    return $query ?: [];
}

// Get quantity for a specific product and customer on the date
function getQuantity($db, $date, $item_id, $customer_id) {
    $query = $db->getRow(
        'SELECT SUM(id.invoice_d_qty) as total_qty
         FROM invoice_details id
         INNER JOIN invoice_hedder ih ON ih.invoice_h_id = id.invoice_h_id
         WHERE ih.invoice_h_delivery_date = ? 
         AND ih.invoice_h_status = 1
         AND id.invoice_d_item_id = ?
         AND ih.invoice_h_customer_id = ?',
        [$date, $item_id, $customer_id]
    );
    return $query['total_qty'] ?? 0;
}

// Get total quantity for a product across all customers
function getProductTotal($db, $date, $item_id) {
    $query = $db->getRow(
        'SELECT SUM(id.invoice_d_qty) as total_qty
         FROM invoice_details id
         INNER JOIN invoice_hedder ih ON ih.invoice_h_id = id.invoice_h_id
         WHERE ih.invoice_h_delivery_date = ? 
         AND ih.invoice_h_status = 1
         AND id.invoice_d_item_id = ?',
        [$date, $item_id]
    );
    return $query['total_qty'] ?? 0;
}

$customers = getCustomersWithOrders($db, $report_date);
$products = getProductsWithOrders($db, $report_date);

// Build the matrix data
$matrix = [];
foreach ($products as $product) {
    $row = [
        'item_id' => $product['item_id'],
        'item_name' => $product['item_name'],
        'type_name' => $product['type_name'] ?? 'Uncategorized',
        'grand_total' => getProductTotal($db, $report_date, $product['item_id']),
        'quantities' => []
    ];
    
    foreach ($customers as $customer) {
        $qty = getQuantity($db, $report_date, $product['item_id'], $customer['customer_id']);
        $row['quantities'][$customer['customer_id']] = $qty;
    }
    
    $matrix[] = $row;
}

// Count stats
$rowCount = count($products);
$columnCount = count($customers);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Pick & Pack Matrix Report</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
    <link href="assets/global/plugins/bootstrap-datepicker/css/bootstrap-datepicker3.min.css" rel="stylesheet" type="text/css" />
    
    <style>
        .pick-pack-matrix {
            font-size: 11px;
            border-collapse: collapse;
            width: 100%;
        }
        
        .pick-pack-matrix th,
        .pick-pack-matrix td {
            border: 1px solid #ddd;
            padding: 4px 6px;
            text-align: center;
            vertical-align: bottom;
        }
        
        .pick-pack-matrix thead th {
            background-color: #f5f5f5;
            font-weight: 600;
        }
        
        /* Vertical column headers */
        .pick-pack-matrix .rotate-header {
            height: 140px;
            vertical-align: bottom;
            padding: 2px;
            width: 28px;
        }
        
        .pick-pack-matrix .rotate-header > div {
            writing-mode: vertical-lr;
            transform: rotate(180deg);
            height: 136px;
            width: 24px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            overflow: hidden;
            margin: 0 auto;
            padding: 0;
        }
        
        .pick-pack-matrix .rotate-header > div > span {
            white-space: normal;
            font-weight: 600;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .pick-pack-matrix .category-col {
            text-align: left;
            font-weight: 600;
              width: 150px;
              min-width: 150px;
              max-width: 150px;
        }
        
        .pick-pack-matrix .product-col {
            text-align: left;
              width: 500px;
              min-width: 500px;
              max-width: 500px;
        }
        
        .pick-pack-matrix .total-col {
            font-weight: 600;
            background-color: #f9f9f9;
            width: 70px;
            min-width: 70px;
            max-width: 70px;
            text-align: center;
        }
        
        .pick-pack-matrix tbody tr:hover {
            background-color: #f5f5f5;
        }
        
        .pick-pack-matrix .qty-cell {
            min-width: 30px;
        }
        
        .pick-pack-matrix tfoot td {
            font-weight: 700;
            background-color: #eaf0fb;
            border-top: 2px solid #666;
        }

        .pick-pack-matrix tfoot .total-label {
            text-align: right;
            font-size: 11px;
            font-weight: 700;
        }

        /* ── Print-only page header (hidden on screen) ── */
        .print-page-header {
            display: none;
        }
        
        .report-header {
            margin-bottom: 20px;
        }
        
        .report-header h2 {
            margin: 0 0 5px 0;
            font-size: 24px;
            font-weight: 600;
        }
        
        .report-header .time-stamp {
            color: #666;
            font-size: 12px;
        }
        
        .report-header .report-date {
            font-size: 18px;
            font-weight: 600;
            margin-top: 10px;
        }
        
        .report-stats {
            font-size: 12px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .date-filter-form {
            background: #f7f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .matrix-container {
            overflow-x: auto;
            margin-top: 20px;
        }

        /* ── A4 landscape print ───────────────────────────────── */
        @page {
            size: A4 landscape;
            margin: 8mm 8mm 8mm 8mm;
        }

        .print-page {
            page-break-after: always;
            page-break-inside: avoid;
        }
        .print-page:last-child {
            page-break-after: auto;
        }

        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            body {
                margin: 0 !important;
                padding: 0 !important;
                font-family: Arial, sans-serif !important;
                font-size: 8px !important;
                color: #000 !important;
                background: #fff !important;
            }

            /* ── Hide UI elements ── */
            .no-print,
            .page-sidebar-wrapper,
            .page-bar,
            .date-filter-form,
            .page-header,
            .report-header,
            .report-stats {
                display: none !important;
            }

            /* ── Strip layout wrappers ── */
            .page-container,
            .page-content-wrapper,
            .page-content,
            .portlet,
            .portlet-body,
            .row,
            .col-md-12 {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                border: none !important;
                box-shadow: none !important;
            }

            /* ── Per-page print header ── */
            .print-page-header {
                display: flex !important;
                justify-content: space-between;
                align-items: flex-end;
                border-bottom: 2px solid #000;
                margin-bottom: 3mm;
                padding-bottom: 1.5mm;
            }

            .print-page-header .ph-title {
                font-size: 13px !important;
                font-weight: 700 !important;
                color: #000 !important;
                letter-spacing: 0.4px;
            }

            .print-page-header .ph-date {
                font-size: 10px !important;
                font-weight: 600 !important;
                color: #000 !important;
                margin-top: 1mm;
            }

            .print-page-header .ph-meta {
                font-size: 7.5px !important;
                color: #000 !important;
                text-align: right;
            }

            /* ── Matrix container ── */
            .matrix-container {
                overflow: visible !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }

            /* ── Table shell ── */
            .pick-pack-matrix {
                font-size: 8px !important;
                width: 100% !important;
                table-layout: fixed;
                border-collapse: collapse;
                border: 1.5px solid #000 !important;
            }

            /* All cells base */
            .pick-pack-matrix th,
            .pick-pack-matrix td {
                padding: 2px 3px !important;
                border: 1px solid #888 !important;
                word-wrap: break-word;
                overflow: hidden;
                background-color: #fff !important;
                color: #000 !important;
            }

            /* ── Thead ── */
            .pick-pack-matrix thead th {
                background-color: #e8e8e8 !important;
                color: #000 !important;
                font-weight: 700 !important;
                border-color: #666 !important;
                font-size: 7px !important;
                vertical-align: bottom;
            }

            /* ── Fixed left columns ── */
            .pick-pack-matrix .category-col {
                width: 18mm !important;
                min-width: 18mm !important;
                max-width: 18mm !important;
                font-size: 7px !important;
                text-align: left !important;
                font-weight: 600 !important;
            }

            .pick-pack-matrix .product-col {
                width: 30mm !important;
                min-width: 30mm !important;
                max-width: 30mm !important;
                font-size: 7.5px !important;
                text-align: left !important;
            }

            /* ── Grand Total column ── */
            .pick-pack-matrix .total-col {
                width: 11mm !important;
                min-width: 11mm !important;
                max-width: 11mm !important;
                font-size: 8px !important;
                font-weight: 700 !important;
                background-color: #f0f0f0 !important;
                color: #000 !important;
                text-align: center !important;
            }

            /* ── Customer qty cells ── */
            .pick-pack-matrix .qty-cell {
                width: 11mm !important;
                min-width: 11mm !important;
                max-width: 11mm !important;
                font-size: 8px !important;
                text-align: center !important;
            }

            /* ── Type group divider rows ── */
            .pick-pack-matrix tbody tr.type-group-start td {
                border-top: 1.5px solid #333 !important;
            }

            .pick-pack-matrix tbody tr.type-group-start .category-col {
                background-color: #e8e8e8 !important;
                font-weight: 700 !important;
                color: #000 !important;
            }

            /* ── Rotated customer headers ── */
            .pick-pack-matrix .rotate-header {
                height: 90px !important;
                padding: 1px !important;
                width: 11mm !important;
            }

            .pick-pack-matrix .rotate-header > div {
                writing-mode: vertical-lr !important;
                transform: rotate(180deg) !important;
                width: calc(11mm - 2px) !important;
                height: 88px !important;
                display: flex;
                align-items: center;
                justify-content: flex-end;
                overflow: hidden;
                margin: 0 auto;
                padding: 0 !important;
            }

            .pick-pack-matrix .rotate-header > div > span {
                font-size: 6.5px !important;
                white-space: normal;
                font-weight: 600 !important;
                color: #000 !important;
            }

            /* ── Footer total row ── */
            .pick-pack-matrix tfoot tr td {
                background-color: #e8e8e8 !important;
                color: #000 !important;
                border-top: 2px solid #333 !important;
                border-color: #666 !important;
                font-weight: 700 !important;
                font-size: 8px !important;
                text-align: center !important;
            }

            .pick-pack-matrix tfoot .total-label {
                text-align: right !important;
                font-size: 7.5px !important;
                color: #000 !important;
            }
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
                            <span>Pick & Pack Matrix</span>
                        </li>
                    </ul>
                </div>
                
                <!-- Date Filter -->
                <div class="row no-print">
                    <div class="col-md-12">
                        <div class="date-filter-form">
                            <form method="GET" class="form-inline">
                                <div class="form-group">
                                    <label for="report_date" style="margin-right: 10px;">Delivery Date:</label>
                                    <input type="text" class="form-control" id="report_date" name="report_date" 
                                           value="<?php echo date('d/m/Y', strtotime($report_date)); ?>" 
                                           style="width: 150px;">
                                </div>
                                <button type="submit" class="btn btn-primary" style="margin-left: 10px;">
                                    <i class="fa fa-search"></i> Generate Report
                                </button>
                                <button type="button" class="btn btn-default" onclick="window.print();" style="margin-left: 10px;">
                                    <i class="fa fa-print"></i> Print
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Report Content -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="portlet light bordered">
                            <div class="portlet-body">
                                <!-- Report Header -->
                                <div class="report-header">
                                    <h2>Pick & Pack Matrix</h2>
                                    <div class="time-stamp">Time <?php echo date('H:i:s'); ?></div>
                                    <div class="report-date">Pick & Pack Matrix - <?php echo $formatted_date; ?></div>
                                </div>
                                
                                <div class="report-stats">
                                    Page Set 1 of 1<br>
                                    Row Count: <?php echo $rowCount; ?><br>
                                    Column Count: <?php echo $columnCount; ?>
                                </div>
                                
                                <?php if (empty($products) || empty($customers)): ?>
                                    <div class="alert alert-info">
                                        No orders found for <?php echo $formatted_date; ?>. Please select a different date.
                                    </div>
                                <?php else: ?>
                                    <?php 
                                        // Split customers into pages of 13 for A5 printing
                                        $customersChunks = array_chunk($customers, 20);
                                        $totalSets = count($customersChunks);
                                    ?>

                                    <?php foreach ($customersChunks as $setIndex => $custChunk): ?>
                                        <div class="matrix-container print-page">
                                            <!-- Screen stats (hidden in print) -->
                                            <div class="report-stats" style="margin-bottom:8px;">
                                                <strong>Page Set <?php echo ($setIndex + 1) . ' of ' . $totalSets; ?></strong><br>
                                                Row Count: <?php echo $rowCount; ?><br>
                                                Columns on page: <?php echo count($custChunk); ?> (Total: <?php echo $columnCount; ?>)
                                            </div>
                                            <!-- Print-only header -->
                                            <div class="print-page-header">
                                                <div>
                                                    <div class="ph-title">Pick &amp; Pack Matrix</div>
                                                    <div class="ph-date"><?php echo $formatted_date; ?></div>
                                                </div>
                                                <div class="ph-meta">
                                                    Page Set <?php echo ($setIndex + 1) . ' of ' . $totalSets; ?> &nbsp;|&nbsp;
                                                    Products: <?php echo $rowCount; ?> &nbsp;|&nbsp;
                                                    Customers on page: <?php echo count($custChunk); ?> of <?php echo $columnCount; ?>
                                                </div>
                                            </div>

                                            <table class="pick-pack-matrix">
                                                <thead>
                                                    <tr>
                                                        <th class="category-col"></th>
                                                        <th class="product-col"></th>
                                                        <th class="total-col">Grand Total</th>
                                                        <?php foreach ($custChunk as $customer): ?>
                                                            <th class="rotate-header">
                                                                <div>
                                                                    <span><?php echo htmlspecialchars($customer['customer_name']); ?></span>
                                                                </div>
                                                            </th>
                                                        <?php endforeach; ?>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php 
                                                    $currentType = '';
                                                    foreach ($matrix as $row): 
                                                        $showType = ($row['type_name'] !== $currentType);
                                                        $currentType = $row['type_name'];
                                                    ?>
                                                        <tr<?php echo $showType ? ' class="type-group-start"' : ''; ?>>
                                                            <td class="category-col">
                                                                <?php echo $showType ? htmlspecialchars($row['type_name']) : ''; ?>
                                                            </td>
                                                            <td class="product-col">
                                                                <?php echo htmlspecialchars($row['item_name']); ?>
                                                            </td>
                                                            <td class="total-col">
                                                                <?php echo $row['grand_total'] > 0 ? (int)$row['grand_total'] : ''; ?>
                                                            </td>
                                                            <?php foreach ($custChunk as $customer): ?>
                                                                <td class="qty-cell">
                                                                    <?php 
                                                                    $qty = $row['quantities'][$customer['customer_id']] ?? 0;
                                                                    echo $qty > 0 ? (int)$qty : '';
                                                                    ?>
                                                                </td>
                                                            <?php endforeach; ?>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td class="category-col total-label" colspan="2">Total</td>
                                                        <td class="total-col">
                                                            <?php
                                                            $grandAllTotal = array_sum(array_column($matrix, 'grand_total'));
                                                            echo $grandAllTotal > 0 ? (int)$grandAllTotal : '';
                                                            ?>
                                                        </td>
                                                        <?php foreach ($custChunk as $customer): ?>
                                                            <td class="qty-cell">
                                                                <?php
                                                                $colTotal = 0;
                                                                foreach ($matrix as $row) {
                                                                    $colTotal += $row['quantities'][$customer['customer_id']] ?? 0;
                                                                }
                                                                echo $colTotal > 0 ? (int)$colTotal : '';
                                                                ?>
                                                            </td>
                                                        <?php endforeach; ?>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include('common/footer.php'); ?>
    <script src="assets/global/plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js" type="text/javascript"></script>
    
    <script>
    $(document).ready(function() {
        // Initialize datepicker
        $('#report_date').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,
            todayHighlight: true
        });
        
        // Convert date format on form submit
        $('form').on('submit', function(e) {
            var dateVal = $('#report_date').val();
            if (dateVal) {
                // Convert dd/mm/yyyy to yyyy-mm-dd
                var parts = dateVal.split('/');
                if (parts.length === 3) {
                    var newDate = parts[2] + '-' + parts[1] + '-' + parts[0];
                    $('#report_date').val(newDate);
                }
            }
        });
    });
    </script>
</body>
</html>
