<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');

date_default_timezone_set("Asia/Colombo");

$db = new Database();

// Date filter handling
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to   = isset($_GET['date_to'])   ? $_GET['date_to']   : date('Y-m-d');
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query
$where = "WHERE pnh.purchase_date BETWEEN ? AND ?";
$params = [$date_from, $date_to];

if (!empty($status_filter)) {
    $where .= " AND pnh.status = ?";
    $params[] = $status_filter;
}

$query = "SELECT
        pnh.purchase_note_code AS document_number,
        pnh.purchase_date,
        s.supplier_name AS vendor_name,
        im.item_name,
        im.item_code,
        pni.requested_qty AS ordered_qty,
        pni.total_received_qty AS received_qty,
        pni.balance_qty,
        pnh.status
    FROM purchase_note_items pni
    INNER JOIN purchase_note_header pnh ON pnh.purchase_note_id = pni.purchase_note_id
    INNER JOIN supplier s ON s.supplier_id = pnh.supplier_id
    INNER JOIN item_master im ON im.item_id = pni.product_id
    $where
    ORDER BY pnh.purchase_date DESC, pnh.purchase_note_code ASC, im.item_name ASC";

$rows = $db->getRows($query, $params);
if (!$rows) $rows = [];

// Summary calculations
$totalOrdered  = 0;
$totalReceived = 0;
$totalBalance  = 0;
foreach ($rows as $r) {
    $totalOrdered  += (float)$r['ordered_qty'];
    $totalReceived += (float)$r['received_qty'];
    $totalBalance  += (float)$r['balance_qty'];
}

// Currency
try {
    $currRow = $db->getRow('SELECT * FROM currency WHERE activated = ? LIMIT 1', ['Y']);
    $CURRENCY_SYMBOL = isset($currRow['currency']) ? $currRow['currency'] : 'AUD';
} catch (Exception $e) {
    $CURRENCY_SYMBOL = 'AUD';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Purchase Order Report | STOCK MANAGEMENT SYSTEM</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
    <link href="assets/global/plugins/datatables/datatables.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.css" rel="stylesheet" type="text/css" />
    <link href="assets/global/plugins/bootstrap-daterangepicker/daterangepicker.min.css" rel="stylesheet" type="text/css" />
    <style>
        .summary-card { background: #fff; border-radius: 4px; padding: 18px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center; border-top: 3px solid #36c6d3; }
        .summary-card .card-value { font-size: 28px; font-weight: 700; color: #333; }
        .summary-card .card-label { font-size: 13px; color: #888; margin-top: 4px; }
        .summary-cards { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; }
        .summary-card.ordered { border-top-color: #36c6d3; }
        .summary-card.received { border-top-color: #26c281; }
        .summary-card.balance { border-top-color: #f0ad4e; }
        .summary-card.docs { border-top-color: #e7505a; }
        .status-open { color: #f0ad4e; font-weight: 600; }
        .status-partial { color: #36c6d3; font-weight: 600; }
        .status-completed { color: #26c281; font-weight: 600; }
        .filter-row { margin-bottom: 15px; }
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
                <div class="page-bar">
                    <ul class="page-breadcrumb">
                        <li><a href="index.php">Home</a><i class="fa fa-circle"></i></li>
                        <li><a href="#">Reports</a><i class="fa fa-circle"></i></li>
                        <li><span>Purchase Order Report</span></li>
                    </ul>
                </div>

                <h3 class="page-title"> Purchase Order Report
                    <small>purchase order tracking</small>
                </h3>

                <!-- Filters -->
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-filter font-dark"></i>
                            <span class="caption-subject font-dark sbold uppercase">Filters</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <form method="GET" action="" class="form-inline filter-row">
                            <div class="form-group" style="margin-right: 15px;">
                                <label>Date Range: </label>
                                <input type="text" class="form-control input-sm" id="date_range" name="date_range"
                                       style="width: 220px; margin-left: 5px;"
                                       value="<?php echo date('m/d/Y', strtotime($date_from)) . ' - ' . date('m/d/Y', strtotime($date_to)); ?>" />
                                <input type="hidden" name="date_from" id="date_from" value="<?php echo $date_from; ?>" />
                                <input type="hidden" name="date_to" id="date_to" value="<?php echo $date_to; ?>" />
                            </div>
                            <div class="form-group" style="margin-right: 15px;">
                                <label>Status: </label>
                                <select name="status" class="form-control input-sm" style="margin-left: 5px;">
                                    <option value="">All</option>
                                    <option value="OPEN" <?php echo ($status_filter == 'OPEN') ? 'selected' : ''; ?>>Open</option>
                                    <option value="PARTIALLY_RECEIVED" <?php echo ($status_filter == 'PARTIALLY_RECEIVED') ? 'selected' : ''; ?>>Partially Received</option>
                                    <option value="COMPLETED" <?php echo ($status_filter == 'COMPLETED') ? 'selected' : ''; ?>>Completed</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fa fa-search"></i> Filter
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="summary-cards">
                    <div class="summary-card ordered">
                        <div class="card-value"><?php echo number_format($totalOrdered, 2); ?></div>
                        <div class="card-label">Total Ordered Qty</div>
                    </div>
                    <div class="summary-card received">
                        <div class="card-value"><?php echo number_format($totalReceived, 2); ?></div>
                        <div class="card-label">Total Received Qty</div>
                    </div>
                    <div class="summary-card balance">
                        <div class="card-value"><?php echo number_format($totalBalance, 2); ?></div>
                        <div class="card-label">Total Balance Qty</div>
                    </div>
                    <div class="summary-card docs">
                        <?php
                        $uniqueDocs = [];
                        foreach ($rows as $r) { $uniqueDocs[$r['document_number']] = 1; }
                        ?>
                        <div class="card-value"><?php echo count($uniqueDocs); ?></div>
                        <div class="card-label">Purchase Orders</div>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-file-text font-dark"></i>
                            <span class="caption-subject font-dark sbold uppercase">Purchase Order Details</span>
                        </div>
                        <div class="actions">
                            <button class="btn btn-xs btn-info" id="btn_export_csv" style="margin-right:5px;">
                                <i class="fa fa-download"></i> Export CSV
                            </button>
                            <button class="btn btn-xs btn-default" onclick="window.print();">
                                <i class="fa fa-print"></i> Print
                            </button>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover" id="po_table" width="100%">
                                <thead>
                                    <tr class="uppercase">
                                        <th style="width:5%">#</th>
                                        <th>Document Number</th>
                                        <th>Date</th>
                                        <th>Vendor Name</th>
                                        <th>Item Name</th>
                                        <th class="text-center">Ordered Qty</th>
                                        <th class="text-center">Received Qty</th>
                                        <th class="text-center">Balance Qty</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                $idx = 0;
                                foreach ($rows as $row) {
                                    $idx++;
                                    $statusClass = 'status-open';
                                    $statusLabel = $row['status'];
                                    if ($row['status'] == 'COMPLETED') {
                                        $statusClass = 'status-completed';
                                        $statusLabel = 'Completed';
                                    } elseif ($row['status'] == 'PARTIALLY_RECEIVED') {
                                        $statusClass = 'status-partial';
                                        $statusLabel = 'Partial';
                                    } else {
                                        $statusLabel = 'Open';
                                    }
                                ?>
                                    <tr>
                                        <td><?php echo $idx; ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['document_number']); ?></strong></td>
                                        <td><?php echo date('Y-m-d', strtotime($row['purchase_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['vendor_name']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($row['item_name']); ?>
                                            <?php if (!empty($row['item_code'])) { ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($row['item_code']); ?></small>
                                            <?php } ?>
                                        </td>
                                        <td class="text-center"><?php echo number_format((float)$row['ordered_qty'], 2); ?></td>
                                        <td class="text-center"><?php echo number_format((float)$row['received_qty'], 2); ?></td>
                                        <td class="text-center">
                                            <?php
                                            $bal = (float)$row['balance_qty'];
                                            echo '<span style="color:' . ($bal > 0 ? '#f0ad4e' : '#26c281') . '; font-weight:600;">' . number_format($bal, 2) . '</span>';
                                            ?>
                                        </td>
                                        <td class="text-center"><span class="<?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span></td>
                                    </tr>
                                <?php } ?>
                                </tbody>
                                <tfoot>
                                    <tr class="active">
                                        <th colspan="5" class="text-right"><strong>Totals</strong></th>
                                        <th class="text-center"><strong><?php echo number_format($totalOrdered, 2); ?></strong></th>
                                        <th class="text-center"><strong><?php echo number_format($totalReceived, 2); ?></strong></th>
                                        <th class="text-center"><strong><?php echo number_format($totalBalance, 2); ?></strong></th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php include('common/footer.php'); ?>

    <script src="assets/global/plugins/jquery.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/js.cookie.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/bootstrap-hover-dropdown/bootstrap-hover-dropdown.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/jquery-slimscroll/jquery.slimscroll.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/jquery.blockui.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/uniform/jquery.uniform.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/bootstrap-switch/js/bootstrap-switch.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/datatables/datatables.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.js" type="text/javascript"></script>
    <script src="assets/global/plugins/moment.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/bootstrap-daterangepicker/daterangepicker.min.js" type="text/javascript"></script>
    <script src="assets/global/scripts/app.min.js" type="text/javascript"></script>
    <script src="assets/layouts/layout/scripts/layout.min.js" type="text/javascript"></script>

    <script>
    jQuery(document).ready(function() {
        // DateRangePicker
        $('#date_range').daterangepicker({
            opens: 'right',
            startDate: moment('<?php echo $date_from; ?>'),
            endDate: moment('<?php echo $date_to; ?>'),
            locale: { format: 'MM/DD/YYYY' }
        }, function(start, end) {
            $('#date_from').val(start.format('YYYY-MM-DD'));
            $('#date_to').val(end.format('YYYY-MM-DD'));
        });

        // DataTable
        var table = jQuery('#po_table').DataTable({
            "order": [[1, "asc"]],
            "pageLength": 50,
            "language": {
                "emptyTable": "No purchase orders found for the selected period."
            }
        });

        // CSV Export
        $('#btn_export_csv').on('click', function() {
            var csv = [];
            var headerRow = [];
            $('#po_table thead tr th').each(function() {
                headerRow.push('"' + $(this).text().trim().replace(/"/g, '""') + '"');
            });
            csv.push(headerRow.join(','));

            table.rows({ search: 'applied' }).every(function() {
                var row = [];
                $(this.node()).find('td').each(function() {
                    row.push('"' + $(this).text().trim().replace(/"/g, '""') + '"');
                });
                csv.push(row.join(','));
            });

            var csvContent = csv.join('\n');
            var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            var link = document.createElement('a');
            link.setAttribute('href', URL.createObjectURL(blob));
            link.setAttribute('download', 'purchase_order_report_' + new Date().toISOString().slice(0,10) + '.csv');
            link.click();
        });
    });
    </script>
</body>
</html>
