<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include('include/database.php');
include('include/check_login.php');

$db = new Database();

// ── Filters ────────────────────────────────────────────────────────────────
$dateFrom   = isset($_GET['date_from'])   ? $_GET['date_from']   : date('Y-m-01');
$dateTo     = isset($_GET['date_to'])     ? $_GET['date_to']     : date('Y-m-d');
$customerId = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;
$itemId     = isset($_GET['item_id'])     ? (int)$_GET['item_id']     : 0;

// Validate dates
if (!strtotime($dateFrom)) $dateFrom = date('Y-m-01');
if (!strtotime($dateTo))   $dateTo   = date('Y-m-d');

// ── Auto-migrate: ensure batch_id column exists on invoice_details ──────────
$colCheck = $db->getRow("SHOW COLUMNS FROM invoice_details LIKE 'batch_id'");
if (!$colCheck) {
    $db->insertRow("ALTER TABLE invoice_details ADD COLUMN `batch_id` INT(11) DEFAULT NULL AFTER `is_cart_item`", []);
}
// ── Auto-migrate: ensure batch_lineage table exists ─────────────────────────
$tblCheck = $db->getRow("SHOW TABLES LIKE 'batch_lineage'");
if (!$tblCheck) {
    $db->insertRow("CREATE TABLE `batch_lineage` (
        `lineage_id`         INT(11)       NOT NULL AUTO_INCREMENT,
        `finished_batch_id`  INT(11)       NOT NULL,
        `finished_item_id`   INT(11)       NOT NULL,
        `raw_batch_id`       INT(11)           NULL,
        `raw_item_id`        INT(11)       NOT NULL,
        `raw_qty_used`       DECIMAL(18,4) NOT NULL DEFAULT 0,
        `issue_id`           INT(11)           NULL,
        `created_at`         DATETIME      NOT NULL,
        `created_by`         VARCHAR(100)  NOT NULL DEFAULT '',
        PRIMARY KEY (`lineage_id`),
        KEY `idx_finished_batch` (`finished_batch_id`),
        KEY `idx_raw_batch`      (`raw_batch_id`),
        KEY `idx_issue`          (`issue_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", []);
}

// ── Filter options ───────────────────────────────────────────────────────────
$customers = $db->getRows(
    "SELECT DISTINCT c.customer_id, c.customer_name
     FROM customer c
     INNER JOIN invoice_hedder ih ON ih.invoice_h_customer_id = c.customer_id
     ORDER BY c.customer_name ASC", []
);

$items = $db->getRows(
    "SELECT DISTINCT im.item_id, im.item_name
     FROM item_master im
     INNER JOIN invoice_details id ON id.invoice_d_item_id = im.item_id
     ORDER BY im.item_name ASC", []
);

// ── Main report query ────────────────────────────────────────────────────────
// One row per invoice_details line (finished product dispatched).
// Raw material info aggregated via GROUP_CONCAT from batch_lineage.
$where   = "WHERE DATE(ih.invoice_h_delivery_date) BETWEEN ? AND ?
              AND ih.invoice_h_status = 1
              AND id.batch_id IS NOT NULL";
$params  = [$dateFrom, $dateTo];

if ($customerId > 0) {
    $where  .= " AND ih.invoice_h_customer_id = ?";
    $params[] = $customerId;
}
if ($itemId > 0) {
    $where  .= " AND id.invoice_d_item_id = ?";
    $params[] = $itemId;
}

$sql = "
SELECT
    id.invoice_d_id,
    fim.item_name                          AS finished_item,
    fim.item_code                          AS finished_item_code,
    id.invoice_d_qty                       AS qty,
    ih.invoice_h_code                      AS shipping_no,
    ih.invoice_h_delivery_date             AS shipping_date,
    c.customer_name,
    MIN(bl.created_at)                     AS production_date,
    /* Aggregated raw material info */
    GROUP_CONCAT(DISTINCT rim.item_name    ORDER BY rim.item_name SEPARATOR '<br>') AS raw_materials,
    GROUP_CONCAT(DISTINCT rb.batch_no      ORDER BY rb.batch_no   SEPARATOR '<br>') AS material_batches,
    GROUP_CONCAT(DISTINCT gh.grn_h_code    ORDER BY gh.grn_h_code SEPARATOR '<br>') AS grn_nos,
    GROUP_CONCAT(DISTINCT s.supplier_name  ORDER BY s.supplier_name SEPARATOR '<br>') AS suppliers
FROM invoice_details id
JOIN invoice_hedder  ih  ON ih.invoice_h_id        = id.invoice_h_id
JOIN customer        c   ON c.customer_id           = ih.invoice_h_customer_id
JOIN item_master     fim ON fim.item_id             = id.invoice_d_item_id
JOIN batch_master    fb  ON fb.batch_id             = id.batch_id
/* lineage joins — all LEFT so rows without lineage still appear */
LEFT JOIN batch_lineage  bl  ON bl.finished_batch_id = fb.batch_id
LEFT JOIN batch_master   rb  ON rb.batch_id          = bl.raw_batch_id
LEFT JOIN item_master    rim ON rim.item_id           = bl.raw_item_id
LEFT JOIN grn_details    gd  ON gd.batch_id           = rb.batch_id
LEFT JOIN grn_hedder     gh  ON gh.grn_h_id           = gd.grn_h_id
LEFT JOIN supplier       s   ON s.supplier_id         = gh.grn_h_supplier_id
$where
GROUP BY id.invoice_d_id,
         fim.item_name, fim.item_code, id.invoice_d_qty,
         ih.invoice_h_code, ih.invoice_h_delivery_date,
         c.customer_name
ORDER BY ih.invoice_h_delivery_date DESC, ih.invoice_h_id DESC, fim.item_name ASC
";

$rows = $db->getRows($sql, $params);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Batch Tracking Report | WebStore</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
    <style>
        .filter-card { background:#fff; border:1px solid #e1e6ee; border-radius:4px; padding:16px 18px; margin-bottom:18px; }
        .filter-card label { font-weight:600; font-size:12px; text-transform:uppercase; color:#666; }
        .report-table th { background:#2c3e50; color:#fff; font-size:11px; text-transform:uppercase; white-space:nowrap; }
        .report-table td { font-size:12px; vertical-align:top; padding:7px 10px !important; }
        .report-table tr:hover td { background:#f5f9ff; }
        .badge-batch { display:inline-block; padding:2px 7px; border-radius:10px; font-size:11px; font-weight:600; background:#e8f4fd; color:#1a6fa3; border:1px solid #b8d9f1; }
        .badge-grn   { display:inline-block; padding:2px 7px; border-radius:10px; font-size:11px; font-weight:600; background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; }
        .text-muted-sm { color:#aaa; font-style:italic; font-size:11px; }
        .multi-val { line-height:2; }
        .no-data { text-align:center; padding:40px; color:#aaa; font-style:italic; }
        .report-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
        .count-badge { background:#2c3e50; color:#fff; padding:3px 12px; border-radius:12px; font-size:12px; }
        @media print {
            .filter-card, .page-bar, .page-sidebar-wrapper, .navbar, .btn-export { display:none !important; }
            .report-table th { background:#444 !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
        }
    </style>
</head>
<body class="page-sidebar-closed-hide-logo page-content-white">
<?php include('common/manubar.php'); ?>
<div class="clearfix"> </div>
<div class="page-container">
    <div class="page-sidebar-wrapper">
        <?php include('common/sidebar.php'); ?>
    </div>
    <div class="page-content-wrapper">
        <div class="page-content">

            <div class="page-bar">
                <ul class="page-breadcrumb">
                    <li><a href="index.php">Home</a><i class="fa fa-circle"></i></li>
                    <li><span>Batch Tracking Report</span></li>
                </ul>
            </div>

            <h3 class="page-title">Item / Batch No Tracking
                <small>Full traceability from raw material to customer</small>
            </h3>

            <!-- Filters -->
            <div class="filter-card">
                <form method="GET" action="batch-tracking-report.php" id="filter_form">
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Date From</label>
                                <input type="date" name="date_from" class="form-control"
                                       value="<?php echo htmlspecialchars($dateFrom); ?>" />
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Date To</label>
                                <input type="date" name="date_to" class="form-control"
                                       value="<?php echo htmlspecialchars($dateTo); ?>" />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Customer</label>
                                <select name="customer_id" class="form-control">
                                    <option value="0">— All Customers —</option>
                                    <?php foreach ($customers as $cu) { ?>
                                        <option value="<?php echo $cu['customer_id']; ?>"
                                            <?php echo ($customerId == $cu['customer_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cu['customer_name']); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Finished Product</label>
                                <select name="item_id" class="form-control">
                                    <option value="0">— All Items —</option>
                                    <?php foreach ($items as $it) { ?>
                                        <option value="<?php echo $it['item_id']; ?>"
                                            <?php echo ($itemId == $it['item_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($it['item_name']); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label>&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fa fa-search"></i> Filter
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Report Table -->
            <div class="portlet light bordered">
                <div class="portlet-body">
                    <div class="report-header">
                        <div>
                            <strong>Results:</strong>
                            <span class="count-badge"><?php echo count($rows); ?> lines</span>
                            &nbsp;
                            <small class="text-muted">
                                <?php echo date('d M Y', strtotime($dateFrom)); ?>
                                &ndash;
                                <?php echo date('d M Y', strtotime($dateTo)); ?>
                            </small>
                        </div>
                        <div>
                            <button class="btn btn-sm btn-default btn-export" onclick="window.print()">
                                <i class="fa fa-print"></i> Print
                            </button>
                            <button class="btn btn-sm btn-success btn-export" id="btn_csv">
                                <i class="fa fa-download"></i> Export CSV
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover report-table" id="batch_report_table">
                            <thead>
                            <tr>
                                <th style="width:4%">#</th>
                                <th>Item Desc</th>
                                <th>Sales Shipping No</th>
                                <th>Sales Shipping Date</th>
                                <th>Customer Name</th>
                                <th class="text-center" style="width:5%">Qty</th>
                                <th>Production Date</th>
                                <th>Raw Materials</th>
                                <th>Material Batch</th>
                                <th>GRN</th>
                                <th>Supplier</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($rows)) { ?>
                                <tr>
                                    <td colspan="11" class="no-data">
                                        <i class="fa fa-inbox fa-2x"></i><br>
                                        No batch-tracked deliveries found for the selected filters.<br>
                                        <small>Only delivered invoices with batch numbers assigned will appear here.</small>
                                    </td>
                                </tr>
                            <?php } else { ?>
                                <?php $rowNum = 0; foreach ($rows as $r) { $rowNum++; ?>
                                <tr>
                                    <td class="text-center text-muted"><?php echo $rowNum; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($r['finished_item']); ?></strong>
                                        <?php if (!empty($r['finished_item_code'])) { ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($r['finished_item_code']); ?></small>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <a href="invoice.php?id=<?php echo $r['invoice_d_id']; ?>" target="_blank"
                                           class="badge-grn" style="text-decoration:none;">
                                            <?php echo htmlspecialchars($r['shipping_no']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo !empty($r['shipping_date']) && $r['shipping_date'] !== '0000-00-00'
                                        ? date('d M Y', strtotime($r['shipping_date'])) : '<span class="text-muted">—</span>'; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($r['customer_name']); ?></td>
                                    <td class="text-center"><strong><?php echo number_format((float)$r['qty'], 2); ?></strong></td>
                                    <td>
                                        <?php if (!empty($r['production_date'])) { ?>
                                            <?php echo date('d M Y', strtotime($r['production_date'])); ?>
                                            <br><small class="text-muted"><?php echo date('h:i A', strtotime($r['production_date'])); ?></small>
                                        <?php } else { ?>
                                            <span class="text-muted-sm">N/A</span>
                                        <?php } ?>
                                    </td>
                                    <td class="multi-val">
                                        <?php if (!empty($r['raw_materials'])) {
                                            $raws = explode('<br>', $r['raw_materials']);
                                            foreach ($raws as $raw) {
                                                echo '<span style="display:block;line-height:1.8;">' . htmlspecialchars(trim($raw)) . '</span>';
                                            }
                                        } else { ?>
                                            <span class="text-muted-sm">No lineage recorded</span>
                                        <?php } ?>
                                    </td>
                                    <td class="multi-val">
                                        <?php if (!empty($r['material_batches'])) {
                                            $batches = explode('<br>', $r['material_batches']);
                                            foreach ($batches as $b) {
                                                echo '<span class="badge-batch" style="display:inline-block;margin-bottom:3px;">' . htmlspecialchars(trim($b)) . '</span><br>';
                                            }
                                        } else { ?>
                                            <span class="text-muted-sm">—</span>
                                        <?php } ?>
                                    </td>
                                    <td class="multi-val">
                                        <?php if (!empty($r['grn_nos'])) {
                                            $grns = explode('<br>', $r['grn_nos']);
                                            foreach ($grns as $g) {
                                                echo '<span class="badge-grn" style="display:inline-block;margin-bottom:3px;">' . htmlspecialchars(trim($g)) . '</span><br>';
                                            }
                                        } else { ?>
                                            <span class="text-muted-sm">—</span>
                                        <?php } ?>
                                    </td>
                                    <td class="multi-val">
                                        <?php if (!empty($r['suppliers'])) {
                                            $sups = explode('<br>', $r['suppliers']);
                                            foreach ($sups as $sup) {
                                                echo '<span style="display:block;line-height:1.8;">' . htmlspecialchars(trim($sup)) . '</span>';
                                            }
                                        } else { ?>
                                            <span class="text-muted-sm">—</span>
                                        <?php } ?>
                                    </td>
                                </tr>
                                <?php } ?>
                            <?php } ?>
                            </tbody>
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
<script src="assets/global/scripts/app.min.js" type="text/javascript"></script>
<script src="assets/layouts/layout/scripts/layout.min.js" type="text/javascript"></script>

<script>
$(document).ready(function () {
    // CSV Export
    $('#btn_csv').on('click', function () {
        var rows = [];
        var headers = [];
        $('#batch_report_table thead th').each(function () {
            headers.push('"' + $(this).text().trim().replace(/"/g, '""') + '"');
        });
        rows.push(headers.join(','));

        $('#batch_report_table tbody tr').each(function () {
            var cols = [];
            $(this).find('td').each(function () {
                var txt = $(this).text().replace(/\s+/g, ' ').trim().replace(/"/g, '""');
                cols.push('"' + txt + '"');
            });
            if (cols.length > 1) rows.push(cols.join(','));
        });

        var blob = new Blob([rows.join('\n')], { type: 'text/csv;charset=utf-8;' });
        var url  = URL.createObjectURL(blob);
        var a    = document.createElement('a');
        a.href     = url;
        a.download = 'batch-tracking-report-<?php echo date('Y-m-d'); ?>.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    });
});
</script>
</body>
</html>
