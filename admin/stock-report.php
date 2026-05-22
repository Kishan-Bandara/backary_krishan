<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');

date_default_timezone_set("Asia/Colombo");

$db = new Database();
$viewMode = (isset($_GET['view']) && $_GET['view'] === 'batchwise') ? 'batchwise' : 'default';
$isBatchWiseView = ($viewMode === 'batchwise');

// Get all locations for column headers
$locations = $db->getRows('SELECT id, location_code, name FROM location_master ORDER BY id ASC');

// Build stock pivot: for each item, get qty per location
$items = $db->getRows(
    "SELECT im.item_id, im.item_code, im.item_name, COALESCE(im.low_stock_qty, 5) AS low_stock_qty, COALESCE(im.is_raw_material, 0) AS is_raw_material
     FROM item_master im
     WHERE im.item_active = 'Y'
     ORDER BY im.item_name ASC"
);

// Get stock grouped by item + location
$stockData = [];
$stockRows = $db->getRows(
    "SELECT ft_item, ft_location, SUM(ft_blanace) AS qty
     FROM fifo
     WHERE ft_type = 1 AND ft_blanace > 0
     GROUP BY ft_item, ft_location"
);
if ($stockRows) {
    foreach ($stockRows as $sr) {
        $stockData[(int)$sr['ft_item']][(int)$sr['ft_location']] = (float)$sr['qty'];
    }
}

$batchRows = [];
if ($isBatchWiseView) {
    $batchRows = $db->getRows(
        "SELECT im.item_id, im.item_code, im.item_name, COALESCE(im.low_stock_qty, 5) AS low_stock_qty,
                lm.location_code, lm.name AS location_name,
                bm.batch_id, bm.batch_no, bm.expiry_date,
                SUM(f.ft_blanace) AS qty
         FROM fifo f
         INNER JOIN item_master im ON im.item_id = f.ft_item
         INNER JOIN location_master lm ON lm.id = f.ft_location
         INNER JOIN batch_master bm ON bm.batch_id = f.batch_id
         WHERE f.ft_type = 1 AND f.ft_blanace > 0
         GROUP BY im.item_id, im.item_code, im.item_name, im.low_stock_qty,
                  lm.location_code, lm.name,
                  bm.batch_id, bm.batch_no, bm.expiry_date
         ORDER BY im.item_name ASC, lm.name ASC, bm.expiry_date ASC, bm.batch_no ASC"
    );
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
    <title>Stock Report | STOCK MANAGEMENT SYSTEM</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
    <link href="assets/global/plugins/datatables/datatables.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.css" rel="stylesheet" type="text/css" />
    <style>
        .stock-zero { color: #ccc; }
        .stock-positive { color: #357e30; font-weight: 600; }
        .stock-low { color: #c9302c; font-weight: 600; }
        .row-low-stock { background: #fff6f6 !important; }
        .status-pill { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 700; }
        .status-ok { background: #e8f8ef; color: #1d7a43; }
        .status-low { background: #fdeaea; color: #b52b27; }
        .summary-card { background: #fff; border-radius: 4px; padding: 18px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center; border-top: 3px solid #36c6d3; }
        .summary-card .card-value { font-size: 28px; font-weight: 700; color: #333; }
        .summary-card .card-label { font-size: 13px; color: #888; margin-top: 4px; }
        .summary-cards { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; }
        .summary-card.total { border-top-color: #36c6d3; }
        .summary-card.items { border-top-color: #f0ad4e; }
        .summary-card.locations { border-top-color: #26c281; }
        .btn-export { margin-right: 5px; }
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
                        <li><span>Stock Report</span></li>
                    </ul>
                </div>

                <h3 class="page-title"> Stock Report
                    <small>inventory across all locations</small>
                </h3>

                <?php
                // Summary calculations
                $totalStock = 0;
                $totalItems = 0;
                foreach ($items as $item) {
                    $itemTotal = 0;
                    foreach ($locations as $loc) {
                        $qty = isset($stockData[$item['item_id']][$loc['id']]) ? $stockData[$item['item_id']][$loc['id']] : 0;
                        $itemTotal += $qty;
                    }
                    if ($itemTotal > 0) $totalItems++;
                    $totalStock += $itemTotal;
                }
                ?>

                <!-- Summary Cards -->
                <div class="summary-cards">
                    <div class="summary-card total">
                        <div class="card-value"><?php echo number_format($totalStock, 2); ?></div>
                        <div class="card-label">Total Stock Units</div>
                    </div>
                    <div class="summary-card items">
                        <div class="card-value"><?php echo $totalItems; ?></div>
                        <div class="card-label">Items In Stock</div>
                    </div>
                    <div class="summary-card locations">
                        <div class="card-value"><?php echo count($locations); ?></div>
                        <div class="card-label">Locations</div>
                    </div>
                    <?php if ($isBatchWiseView) { ?>
                        <div class="summary-card items">
                            <div class="card-value"><?php echo count($batchRows); ?></div>
                            <div class="card-label">Batch Rows</div>
                        </div>
                    <?php } ?>
                </div>

                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-cubes font-dark"></i>
                            <span class="caption-subject font-dark sbold uppercase"><?php echo $isBatchWiseView ? 'Batch Wise Stock Report' : 'Stock by Location'; ?></span>
                        </div>
                        <div class="actions">
                            <?php if ($isBatchWiseView) { ?>
                                <a class="btn btn-xs btn-default btn-export" href="stock-report.php">
                                    <i class="fa fa-table"></i> Default View
                                </a>
                            <?php } else { ?>
                                <a class="btn btn-xs btn-warning btn-export" href="stock-report.php?view=batchwise">
                                    <i class="fa fa-tags"></i> Batch Wise View
                                </a>
                            <?php } ?>
                            <button class="btn btn-xs btn-info btn-export" id="btn_export_csv">
                                <i class="fa fa-download"></i> Export CSV
                            </button>
                            <button class="btn btn-xs btn-default" onclick="window.print();">
                                <i class="fa fa-print"></i> Print
                            </button>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <?php if ($isBatchWiseView) { ?>
                            <div class="alert alert-info" style="margin-bottom: 15px;">
                                Batch wise view is optional and shows only positive stock rows that have a saved batch number.
                            </div>
                        <?php } ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover" id="stock_table" width="100%">
                                <?php if ($isBatchWiseView) { ?>
                                    <thead>
                                        <tr class="uppercase">
                                            <th style="width:5%">#</th>
                                            <th>Item</th>
                                            <th class="text-center" style="width:140px;">Batch No</th>
                                            <th class="text-center" style="width:130px;">Expiry Date</th>
                                            <th class="text-center" style="width:180px;">Location</th>
                                            <th class="text-center" style="width:110px;">Qty</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $batchIdx = 0;
                                    $batchGrandTotal = 0;
                                    if (!empty($batchRows)) {
                                        foreach ($batchRows as $row) {
                                            $batchIdx++;
                                            $batchQty = (float) ($row['qty'] ?? 0);
                                            $batchGrandTotal += $batchQty;
                                            $locationLabel = trim(($row['location_code'] ?? '') . ' - ' . ($row['location_name'] ?? ''));
                                            $expiryText = !empty($row['expiry_date']) && $row['expiry_date'] !== '0000-00-00' ? $row['expiry_date'] : '-';
                                    ?>
                                        <tr>
                                            <td><?php echo $batchIdx; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($row['item_name']); ?></strong>
                                                <?php if (!empty($row['item_code'])) { ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($row['item_code']); ?></small>
                                                <?php } ?>
                                            </td>
                                            <td class="text-center"><?php echo htmlspecialchars($row['batch_no'] ?: '-'); ?></td>
                                            <td class="text-center"><?php echo htmlspecialchars($expiryText); ?></td>
                                            <td class="text-center"><?php echo htmlspecialchars($locationLabel); ?></td>
                                            <td class="text-center stock-positive"><strong><?php echo number_format($batchQty, 2); ?></strong></td>
                                        </tr>
                                    <?php
                                        }
                                    } else {
                                    ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">No batch-tracked stock found.</td>
                                        </tr>
                                    <?php } ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="active">
                                            <th colspan="5" class="text-right"><strong>Total Batch Stock</strong></th>
                                            <th class="text-center"><strong><?php echo number_format($batchGrandTotal, 2); ?></strong></th>
                                        </tr>
                                    </tfoot>
                                <?php } else { ?>
                                <thead>
                                    <tr class="uppercase">
                                        <th style="width:5%">#</th>
                                        <th>Item</th>
                                        <?php foreach ($locations as $loc) { ?>
                                            <th class="text-center"><?php echo htmlspecialchars($loc['name']); ?> Qty</th>
                                        <?php } ?>
                                        <th class="text-center" style="width:100px;"><strong>Total</strong></th>
                                        <th class="text-center" style="width:120px;">Low Stock Qty</th>
                                        <th class="text-center" style="width:110px;">Status</th>
                                        <th class="text-center" style="width:120px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                $idx = 0;
                                foreach ($items as $item) {
                                    $idx++;
                                    $rowTotal = 0;
                                    $cells = '';
                                    $lowStockQty = isset($item['low_stock_qty']) ? (float)$item['low_stock_qty'] : 5;
                                    foreach ($locations as $loc) {
                                        $qty = isset($stockData[$item['item_id']][$loc['id']]) ? $stockData[$item['item_id']][$loc['id']] : 0;
                                        $rowTotal += $qty;
                                        $cls = ($qty <= 0) ? 'stock-zero' : 'stock-positive';
                                        $cells .= '<td class="text-center ' . $cls . '">' . number_format($qty, 2) . '</td>';
                                    }
                                    $isLowStock = ($rowTotal <= $lowStockQty);
                                    $totalCls = $isLowStock ? 'stock-low' : (($rowTotal <= 0) ? 'stock-zero' : 'stock-positive');
                                    $rowClass = $isLowStock ? 'row-low-stock' : '';
                                    $statusHtml = $isLowStock
                                        ? '<span class="status-pill status-low"><i class="fa fa-exclamation-triangle"></i> Low</span>'
                                        : '<span class="status-pill status-ok"><i class="fa fa-check"></i> OK</span>';

                                    if ((int)$item['is_raw_material'] === 1) {
                                        $poBtn = '<a class="btn btn-xs btn-warning" href="purchase-order-create.php?item_id=' . (int)$item['item_id'] . '"><i class="fa fa-shopping-cart"></i> Create PO</a>';
                                    } else {
                                        $poBtn = '<button type="button" class="btn btn-xs btn-default" disabled title="PO is only for raw materials"><i class="fa fa-shopping-cart"></i> Create PO</button>';
                                    }
                                ?>
                                    <tr class="<?php echo $rowClass; ?>">
                                        <td><?php echo $idx; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                                            <?php if (!empty($item['item_code'])) { ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($item['item_code']); ?></small>
                                            <?php } ?>
                                        </td>
                                        <?php echo $cells; ?>
                                        <td class="text-center <?php echo $totalCls; ?>"><strong><?php echo number_format($rowTotal, 2); ?></strong></td>
                                        <td class="text-center <?php echo $isLowStock ? 'stock-low' : ''; ?>"><?php echo number_format($lowStockQty, 2); ?></td>
                                        <td class="text-center"><?php echo $statusHtml; ?></td>
                                        <td class="text-center"><?php echo $poBtn; ?></td>
                                    </tr>
                                <?php } ?>
                                </tbody>
                                <tfoot>
                                    <tr class="active">
                                        <th colspan="2" class="text-right"><strong>Location Totals</strong></th>
                                        <?php
                                        $grandTotal = 0;
                                        foreach ($locations as $loc) {
                                            $locTotal = 0;
                                            foreach ($items as $item) {
                                                $locTotal += isset($stockData[$item['item_id']][$loc['id']]) ? $stockData[$item['item_id']][$loc['id']] : 0;
                                            }
                                            $grandTotal += $locTotal;
                                            echo '<th class="text-center"><strong>' . number_format($locTotal, 2) . '</strong></th>';
                                        }
                                        ?>
                                        <th class="text-center"><strong><?php echo number_format($grandTotal, 2); ?></strong></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                                <?php } ?>
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
    <script src="assets/global/scripts/app.min.js" type="text/javascript"></script>
    <script src="assets/layouts/layout/scripts/layout.min.js" type="text/javascript"></script>

    <script>
    jQuery(document).ready(function() {
        var isBatchWiseView = <?php echo $isBatchWiseView ? 'true' : 'false'; ?>;
        var table = jQuery('#stock_table').DataTable({
            "order": [[1, "asc"]],
            "pageLength": 50,
            "language": {
                "emptyTable": "No items found."
            }
        });

        // CSV Export
        $('#btn_export_csv').on('click', function() {
            var csv = [];
            // Header row
            var headerRow = [];
            $('#stock_table thead tr th').each(function() {
                headerRow.push('"' + $(this).text().trim().replace(/"/g, '""') + '"');
            });
            csv.push(headerRow.join(','));

            // Data rows (all pages)
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
            var url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', (isBatchWiseView ? 'stock_report_batchwise_' : 'stock_report_') + new Date().toISOString().slice(0,10) + '.csv');
            link.click();
        });
    });
    </script>
</body>
</html>
