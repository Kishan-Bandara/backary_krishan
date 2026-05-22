<?php 
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');
include('get_url.php');
date_default_timezone_set("Asia/Colombo");

// Get currency
$currencyRow = $db->getRow('SELECT * FROM currency WHERE activated = ? LIMIT 1', ["Y"]);
$currSymbol = $currencyRow ? $currencyRow['currency'] : 'AUD';

// ====== LAST 7 DAYS SALES (for bar chart) ======
$last7 = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $label = date('D', strtotime("-$i days"));
    if (isSuperAdmin()) {
        $r = $db->getRow('SELECT COALESCE(SUM(invoice_h_gross_value),0) AS val FROM invoice_hedder WHERE invoice_h_status = 1 AND CAST(invoice_h_date AS DATE) = CAST(? AS DATE)', [$d]);
    } else {
        $r = $db->getRow('SELECT COALESCE(SUM(invoice_h_gross_value),0) AS val FROM invoice_hedder WHERE invoice_h_status = 1 AND CAST(invoice_h_date AS DATE) = CAST(? AS DATE) AND invoice_h_location = ?', [$d, $_SESSION['location']]);
    }
    $last7[] = ['label' => $label, 'value' => floatval($r['val'])];
}
$chartLabels = json_encode(array_column($last7, 'label'));
$chartData = json_encode(array_column($last7, 'value'));

// ====== PAYMENTS BY TYPE (this month, for donut) ======
if (isSuperAdmin()) {
    $payRows = $db->getRows('SELECT pm.type AS ptype, COALESCE(SUM(ih.invoice_h_gross_value),0) AS val FROM invoice_hedder ih LEFT JOIN payment_method pm ON ih.invoice_h_pay_type = pm.id WHERE ih.invoice_h_status = 1 AND MONTH(ih.invoice_h_date)=MONTH(CURRENT_DATE()) AND YEAR(ih.invoice_h_date)=YEAR(CURRENT_DATE()) GROUP BY ih.invoice_h_pay_type ORDER BY val DESC');
} else {
    $payRows = $db->getRows('SELECT pm.type AS ptype, COALESCE(SUM(ih.invoice_h_gross_value),0) AS val FROM invoice_hedder ih LEFT JOIN payment_method pm ON ih.invoice_h_pay_type = pm.id WHERE ih.invoice_h_status = 1 AND MONTH(ih.invoice_h_date)=MONTH(CURRENT_DATE()) AND YEAR(ih.invoice_h_date)=YEAR(CURRENT_DATE()) AND ih.invoice_h_location = ? GROUP BY ih.invoice_h_pay_type ORDER BY val DESC', [$_SESSION['location']]);
}
$payLabels = []; $payData = [];
$payColors = ['#4facfe','#43e97b','#f093fb','#f7971e','#eb3349','#667eea','#00B4DB','#ffd200'];
foreach ($payRows as $idx => $pr) {
    $payLabels[] = $pr['ptype'] ?: 'Unknown';
    $payData[] = floatval($pr['val']);
}
if (empty($payLabels)) { $payLabels = ['No Data']; $payData = [0]; }
$payLabelsJson = json_encode($payLabels);
$payDataJson = json_encode($payData);
$payColorsJson = json_encode(array_slice($payColors, 0, count($payLabels)));

// ====== TOP 5 SELLING PRODUCTS (this month) ======
if (isSuperAdmin()) {
    $topProducts = $db->getRows('SELECT im.item_name, im.item_code, SUM(id.invoice_d_qty) AS total_qty FROM invoice_details id INNER JOIN invoice_hedder ih ON id.invoice_h_id = ih.invoice_h_id INNER JOIN item_master im ON id.invoice_d_item_id = im.item_id WHERE ih.invoice_h_status = 1 AND MONTH(ih.invoice_h_date)=MONTH(CURRENT_DATE()) AND YEAR(ih.invoice_h_date)=YEAR(CURRENT_DATE()) GROUP BY id.invoice_d_item_id ORDER BY total_qty DESC LIMIT 5');
} else {
    $topProducts = $db->getRows('SELECT im.item_name, im.item_code, SUM(id.invoice_d_qty) AS total_qty FROM invoice_details id INNER JOIN invoice_hedder ih ON id.invoice_h_id = ih.invoice_h_id INNER JOIN item_master im ON id.invoice_d_item_id = im.item_id WHERE ih.invoice_h_status = 1 AND MONTH(ih.invoice_h_date)=MONTH(CURRENT_DATE()) AND YEAR(ih.invoice_h_date)=YEAR(CURRENT_DATE()) AND ih.invoice_h_location = ? GROUP BY id.invoice_d_item_id ORDER BY total_qty DESC LIMIT 5', [$_SESSION['location']]);
}

// ====== TOP 5 CUSTOMERS (this month) ======
if (isSuperAdmin()) {
    $topCustomers = $db->getRows('SELECT c.customer_name, c.customer_email, SUM(ih.invoice_h_gross_value) AS total_val FROM invoice_hedder ih INNER JOIN customer c ON ih.invoice_h_customer_id = c.customer_id WHERE ih.invoice_h_status = 1 AND MONTH(ih.invoice_h_date)=MONTH(CURRENT_DATE()) AND YEAR(ih.invoice_h_date)=YEAR(CURRENT_DATE()) GROUP BY ih.invoice_h_customer_id ORDER BY total_val DESC LIMIT 5');
} else {
    $topCustomers = $db->getRows('SELECT c.customer_name, c.customer_email, SUM(ih.invoice_h_gross_value) AS total_val FROM invoice_hedder ih INNER JOIN customer c ON ih.invoice_h_customer_id = c.customer_id WHERE ih.invoice_h_status = 1 AND MONTH(ih.invoice_h_date)=MONTH(CURRENT_DATE()) AND YEAR(ih.invoice_h_date)=YEAR(CURRENT_DATE()) AND ih.invoice_h_location = ? GROUP BY ih.invoice_h_customer_id ORDER BY total_val DESC LIMIT 5', [$_SESSION['location']]);
}

// ====== LOW STOCK ALERT ======
// Graceful fallback when DB migration not applied: use per-product low_stock_qty when present
$hasLowStockCol = $db->getRow("SHOW COLUMNS FROM item_master LIKE 'low_stock_qty'");
if (is_array($hasLowStockCol) && isset($hasLowStockCol['Field']) && $hasLowStockCol['Field'] === 'low_stock_qty') {
    $lowStockItems = $db->getRows('SELECT im.item_name, im.item_code, COALESCE(SUM(f.ft_blanace),0) AS stock_left, COALESCE(MAX(im.low_stock_qty), 5) AS low_stock_qty FROM item_master im INNER JOIN fifo f ON im.item_id = f.ft_item GROUP BY f.ft_item HAVING SUM(f.ft_blanace) < COALESCE(MAX(im.low_stock_qty), 5) ORDER BY stock_left ASC LIMIT 5');
} else {
    $lowStockItems = $db->getRows('SELECT im.item_name, im.item_code, COALESCE(SUM(f.ft_blanace),0) AS stock_left FROM item_master im INNER JOIN fifo f ON im.item_id = f.ft_item GROUP BY f.ft_item HAVING SUM(f.ft_blanace) < 5 ORDER BY stock_left ASC LIMIT 5');
}
$lowStockCount = count($lowStockItems);

// ====== PENDING STOCK TRANSFERS ======
$pendingTransferCount = 0;
try {
    $r = $db->getRow("SELECT COUNT(transfer_id) AS val FROM stock_transfer_header WHERE status = 'PENDING'");
    $pendingTransferCount = $r['val'] ?? 0;
} catch(Exception $e) { $pendingTransferCount = 0; }

// ====== RECENT 10 SALES ======
if (isSuperAdmin()) {
    $recentSales = $db->getRows('SELECT ih.*, c.customer_name, pm.type AS pay_type FROM invoice_hedder ih LEFT JOIN customer c ON ih.invoice_h_customer_id = c.customer_id LEFT JOIN payment_method pm ON ih.invoice_h_pay_type = pm.id ORDER BY ih.invoice_h_id DESC LIMIT 10');
} else {
    $recentSales = $db->getRows('SELECT ih.*, c.customer_name, pm.type AS pay_type FROM invoice_hedder ih LEFT JOIN customer c ON ih.invoice_h_customer_id = c.customer_id LEFT JOIN payment_method pm ON ih.invoice_h_pay_type = pm.id WHERE ih.invoice_h_location = ? ORDER BY ih.invoice_h_id DESC LIMIT 10', [$_SESSION['location']]);
}

?>

<!DOCTYPE html>

<!--[if IE 8]> <html lang="en" class="ie8 no-js"> <![endif]-->
<!--[if IE 9]> <html lang="en" class="ie9 no-js"> <![endif]-->
<!--[if !IE]><!-->
<html lang="en">
    <!--<![endif]-->
    <!-- BEGIN HEAD -->


<head>
        <meta charset="utf-8" />
        <title>STOCK MANAGER SYSTEM</title>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta content="width=device-width, initial-scale=1" name="viewport" />
        <meta content="" name="description" />
        <meta content="" name="author" />
        <?php include('common/head.php'); ?>
        
       </head>

    <body class="page-header-fixed page-sidebar-closed-hide-logo" style="background:#f0f2f8;">
      <?php include('common/manubar.php'); ?>
        <div class="clearfix"></div>
        <div class="page-container">
            <div class="page-sidebar-wrapper">
                <?php include('common/sidebar.php'); ?>
            </div>
            <div class="page-content-wrapper">
                <div class="page-content" style="padding:20px 25px;">

                    <?php if(!empty($CompanyMessage)) { ?>
                    <div style="background:#fff; border-radius:12px; padding:14px 20px; margin-bottom:16px; box-shadow:0 2px 8px rgba(0,0,0,0.04); border-left:4px solid #667eea;">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <?php echo $CompanyMessage; ?>
                    </div>
                    <?php } ?>

<style>
/* ====== DASHBOARD GLOBAL STYLES ====== */
* { box-sizing: border-box; }

.db-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.05);
    overflow: hidden;
}
.db-card-header {
    padding: 16px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #f0f0f5;
}
.db-card-header h5 {
    margin: 0;
    font-size: 14px;
    font-weight: 700;
    color: #2d3748;
    display: flex;
    align-items: center;
    gap: 8px;
}
.db-card-header .badge-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 22px;
    height: 22px;
    border-radius: 11px;
    font-size: 11px;
    font-weight: 700;
    color: #fff;
    padding: 0 7px;
}
.db-card-header .header-tag {
    font-size: 11px;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 8px;
    cursor: pointer;
}

/* Top Selling List */
.top-list { list-style: none; margin: 0; padding: 0; }
.top-list li {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    border-bottom: 1px solid #f5f6fa;
    gap: 12px;
}
.top-list li:last-child { border-bottom: none; }
.top-list .rank {
    width: 28px; height: 28px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700; color: #fff;
    flex-shrink: 0;
}
.top-list .item-info { flex: 1; min-width: 0; }
.top-list .item-name {
    font-size: 13px; font-weight: 600; color: #2d3748;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.top-list .item-sub {
    font-size: 11px; color: #8492a6;
}
.top-list .item-val {
    font-size: 13px; font-weight: 700;
    flex-shrink: 0;
    border-radius: 20px; padding: 4px 12px;
}

/* Alert Cards */
.alert-card-body { padding: 0; }
.alert-item {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 20px;
    border-bottom: 1px solid #f5f6fa;
}
.alert-item:last-child { border-bottom: none; }
.alert-empty {
    padding: 30px 20px;
    text-align: center;
    color: #43a047;
    font-size: 13px;
    font-weight: 600;
}
.alert-empty i { margin-right: 6px; }

/* Recent Sales Table */
.recent-table { width: 100%; border-collapse: collapse; }
.recent-table thead th {
    padding: 12px 18px;
    font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 1px;
    color: #8492a6; background: #f8f9fe;
    border: none;
}
.recent-table tbody td {
    padding: 13px 18px;
    font-size: 13px; color: #4a5568;
    border-bottom: 1px solid #f0f0f5;
    vertical-align: middle;
}
.recent-table tbody tr:hover { background: #f8f9fe; }
.recent-table a { color: #eb3349; font-weight: 600; text-decoration: none; }
.recent-table a:hover { text-decoration: underline; }

.badge-sm {
    display: inline-block; padding: 4px 12px;
    border-radius: 20px; font-size: 10px;
    font-weight: 700; text-transform: uppercase;
}
.badge-green { background: #e6ffed; color: #22863a; }
.badge-orange { background: #fff3e0; color: #f57c00; }
.badge-red { background: #ffeef0; color: #e53e3e; }
.badge-blue { background: #e3f2fd; color: #1976d2; }

.time-ago { color: #8492a6; font-size: 12px; }

/* Animations */
@keyframes fadeUp { from { opacity:0; transform:translateY(15px); } to { opacity:1; transform:translateY(0); } }
.anim-1 { animation: fadeUp 0.4s ease forwards; }
.anim-2 { animation: fadeUp 0.4s 0.08s ease forwards; opacity:0; }
.anim-3 { animation: fadeUp 0.4s 0.16s ease forwards; opacity:0; }
.anim-4 { animation: fadeUp 0.4s 0.24s ease forwards; opacity:0; }
.anim-5 { animation: fadeUp 0.4s 0.32s ease forwards; opacity:0; }

/* Responsive grid fallback */
@media (max-width: 992px) {
    .dash-grid-6 { grid-template-columns: repeat(3, 1fr) !important; }
    .dash-grid-4 { grid-template-columns: repeat(2, 1fr) !important; }
    .dash-grid-3 { grid-template-columns: 1fr !important; }
}
@media (max-width: 576px) {
    .dash-grid-6 { grid-template-columns: repeat(2, 1fr) !important; }
    .dash-grid-4 { grid-template-columns: 1fr !important; }
}
</style>

<!-- ====== ROW 1: Top KPI Cards (auto-refresh) ====== -->
<div id="autoRef" class="anim-1"></div>

<!-- ====== ROW 2: Info Cards (auto-refresh) ====== -->
<div id="autoIncome" class="anim-2"></div>

<!-- ====== ROW 3: Charts + Top Selling + Top Customers ====== -->
<div style="display:grid; grid-template-columns: 5fr 3fr 4fr 4fr; gap:16px; margin-bottom:16px;" class="dash-grid-4 anim-3">
    
    <!-- Last 7 Days Sales Chart -->
    <div class="db-card">
        <div class="db-card-header">
            <h5><i class="fa fa-bar-chart" style="color:#4facfe;"></i> Last 7 Days Sales</h5>
        </div>
        <div style="padding:16px 16px 10px;">
            <canvas id="salesChart" height="200"></canvas>
        </div>
    </div>

    <!-- Payments Donut -->
    <div class="db-card">
        <div class="db-card-header">
            <h5><i class="fa fa-credit-card" style="color:#43e97b;"></i> Payments</h5>
        </div>
        <div style="padding:16px; display:flex; align-items:center; justify-content:center;">
            <canvas id="paymentChart" height="200"></canvas>
        </div>
    </div>

    <!-- Top Selling Products -->
    <div class="db-card">
        <div class="db-card-header">
            <h5><i class="fa fa-trophy" style="color:#f7971e;"></i> Top Selling</h5>
            <span class="header-tag" style="background:#fff3e0; color:#f57c00;">This Month</span>
        </div>
        <ul class="top-list">
            <?php if(empty($topProducts)): ?>
                <li style="justify-content:center; color:#8492a6; padding:30px;">No sales this month</li>
            <?php else: 
                $rankColors = ['#eb3349','#f7971e','#f7971e','#667eea','#667eea'];
                foreach($topProducts as $idx => $tp): ?>
                <li>
                    <span class="rank" style="background:<?php echo $rankColors[$idx] ?? '#8492a6'; ?>;"><?php echo $idx + 1; ?></span>
                    <div class="item-info">
                        <div class="item-name"><?php echo htmlspecialchars($tp['item_name']); ?></div>
                        <div class="item-sub"><?php echo $tp['item_code']; ?></div>
                    </div>
                    <span class="item-val" style="background:#e8f5e9; color:#2e7d32;"><?php echo intval($tp['total_qty']); ?></span>
                </li>
            <?php endforeach; endif; ?>
        </ul>
    </div>

    <!-- Top Customers -->
    <div class="db-card">
        <div class="db-card-header">
            <h5><i class="fa fa-star" style="color:#ffd200;"></i> Top Customers</h5>
        </div>
        <ul class="top-list">
            <?php if(empty($topCustomers)): ?>
                <li style="justify-content:center; color:#8492a6; padding:30px;">No customers this month</li>
            <?php else: 
                $custColors = ['#eb3349','#f7971e','#f7971e','#667eea','#667eea'];
                foreach($topCustomers as $idx => $tc): ?>
                <li>
                    <span class="rank" style="background:<?php echo $custColors[$idx] ?? '#8492a6'; ?>;"><?php echo $idx + 1; ?></span>
                    <div class="item-info">
                        <div class="item-name"><?php echo htmlspecialchars($tc['customer_name']); ?></div>
                        <div class="item-sub"><?php echo $tc['customer_email'] ?: '—'; ?></div>
                    </div>
                    <span class="item-val" style="background:transparent; color:#eb3349; font-weight:800;"><?php echo $currSymbol; ?><?php echo number_format($tc['total_val'],2); ?></span>
                </li>
            <?php endforeach; endif; ?>
        </ul>
    </div>

</div>

<!-- ====== ROW 4: Alert Cards ====== -->
<div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:16px; margin-bottom:16px;" class="dash-grid-3 anim-4">
    
    <!-- Low Stock Alert -->
    <div class="db-card">
        <div class="db-card-header">
            <h5><i class="fa fa-exclamation-triangle" style="color:#f57c00;"></i> Low Stock Alert</h5>
            <span class="badge-count" style="background:#f57c00;"><?php echo $lowStockCount; ?></span>
        </div>
        <div class="alert-card-body">
            <?php if($lowStockCount == 0): ?>
                <div class="alert-empty"><i class="fa fa-check-circle"></i> All stocks are healthy</div>
            <?php else: 
                foreach($lowStockItems as $ls): ?>
                <div class="alert-item">
                    <span style="font-size:13px; color:#2d3748; font-weight:600;"><?php echo htmlspecialchars($ls['item_name']); ?></span>
                    <span class="badge-sm badge-red"><?php echo intval($ls['stock_left']); ?> Left</span>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- Pending Stock Transfers -->
    <div class="db-card">
        <div class="db-card-header">
            <h5><i class="fa fa-exchange" style="color:#8e24aa;"></i> Pending Stock Transfers</h5>
            <span class="badge-count" style="background:#8e24aa;"><?php echo $pendingTransferCount; ?></span>
        </div>
        <div class="alert-card-body">
            <?php if($pendingTransferCount == 0): ?>
                <div class="alert-empty"><i class="fa fa-check-circle"></i> No pending transfers</div>
            <?php else: ?>
                <div class="alert-item">
                    <span style="font-size:13px; color:#2d3748; font-weight:600;"><?php echo $pendingTransferCount; ?> transfer(s) awaiting action</span>
                    <a href="stock-transfer-receive-list.php" style="color:#8e24aa; font-weight:600; text-decoration:none; font-size:12px;">View <i class="fa fa-arrow-right"></i></a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="db-card">
        <div class="db-card-header">
            <h5><i class="fa fa-bolt" style="color:#eb3349;"></i> Quick Actions</h5>
        </div>
        <div style="padding:16px 20px; display:grid; grid-template-columns:1fr 1fr; gap:10px;">
            <a href="POS.php" style="display:flex; align-items:center; gap:8px; padding:10px 14px; background:#eef2ff; border-radius:10px; text-decoration:none; color:#667eea; font-size:12px; font-weight:600; transition:all 0.2s;">
                <i class="fa fa-desktop"></i> POS Terminal
            </a>
            <a href="manage-orders.php" style="display:flex; align-items:center; gap:8px; padding:10px 14px; background:#ffeef0; border-radius:10px; text-decoration:none; color:#eb3349; font-size:12px; font-weight:600; transition:all 0.2s;">
                <i class="fa fa-shopping-cart"></i> Orders
            </a>
            <a href="manage-product.php" style="display:flex; align-items:center; gap:8px; padding:10px 14px; background:#e8f5e9; border-radius:10px; text-decoration:none; color:#2e7d32; font-size:12px; font-weight:600; transition:all 0.2s;">
                <i class="fa fa-barcode"></i> Products
            </a>
            <a href="manage-customer.php" style="display:flex; align-items:center; gap:8px; padding:10px 14px; background:#fff3e0; border-radius:10px; text-decoration:none; color:#f57c00; font-size:12px; font-weight:600; transition:all 0.2s;">
                <i class="fa fa-users"></i> Customers
            </a>
            <a href="purchase-history.php" style="display:flex; align-items:center; gap:8px; padding:10px 14px; background:#e0f7fa; border-radius:10px; text-decoration:none; color:#00838f; font-size:12px; font-weight:600; transition:all 0.2s;">
                <i class="fa fa-truck"></i> Purchases
            </a>
            <a href="stock-report.php" style="display:flex; align-items:center; gap:8px; padding:10px 14px; background:#f3e5f5; border-radius:10px; text-decoration:none; color:#8e24aa; font-size:12px; font-weight:600; transition:all 0.2s;">
                <i class="fa fa-bar-chart"></i> Reports
            </a>
        </div>
    </div>

</div>

<!-- ====== ROW 5: Recent Sales Table ====== -->
<div class="db-card anim-5" style="margin-bottom:20px;">
    <div class="db-card-header">
        <h5><i class="fa fa-list" style="color:#4facfe;"></i> Recent Sales <span class="badge-count" style="background:#4facfe;">10</span></h5>
        <a href="manage-orders.php" style="font-size:12px; color:#667eea; font-weight:600; text-decoration:none;">View All <i class="fa fa-arrow-right"></i></a>
    </div>
    <div style="overflow-x:auto;">
        <table class="recent-table">
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Customer</th>
                    <th>Cashier</th>
                    <th>Items</th>
                    <th>Amount</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($recentSales as $sale):
                    // Get item count
                    $itemCountR = $db->getRow('SELECT COUNT(invoice_d_id) AS cnt FROM invoice_details WHERE invoice_h_id = ?', [$sale['invoice_h_id']]);
                    $itemCount = $itemCountR['cnt'] ?? 0;
                    
                    // Status badge
                    $sBadge = 'badge-orange'; $sText = 'Pending';
                    if ($sale['invoice_h_status'] == 1) { $sBadge = 'badge-green'; $sText = 'Completed'; }
                    elseif ($sale['invoice_h_status'] == -1) { $sBadge = 'badge-red'; $sText = 'Cancelled'; }
                    elseif ($sale['invoice_h_status'] == 3) { $sBadge = 'badge-blue'; $sText = 'Quotation'; }
                    
                    // Time ago
                    $invoiceDate = $sale['invoice_h_datetime'] ?? $sale['invoice_h_date'];
                    $timeAgo = '';
                    if ($invoiceDate) {
                        $diff = time() - strtotime($invoiceDate);
                        if ($diff < 60) $timeAgo = 'Just now';
                        elseif ($diff < 3600) $timeAgo = floor($diff/60) . ' min ago';
                        elseif ($diff < 86400) $timeAgo = floor($diff/3600) . ' hour ago';
                        else $timeAgo = floor($diff/86400) . ' day ago';
                    }

                    // Cashier
                    $cashierName = $sale['add_by'] ?: '—';
                    if (is_numeric($sale['add_by'])) {
                        $cRow = $db->getRow('SELECT first_name FROM users WHERE userid = ?', [$sale['add_by']]);
                        $cashierName = $cRow['first_name'] ?? $sale['add_by'];
                    }
                ?>
                <tr>
                    <td><a href="receipt.php?id=<?php echo $sale['invoice_h_id']; ?>" target="_blank"><?php echo $sale['invoice_h_code']; ?></a></td>
                    <td style="font-weight:600;"><?php echo htmlspecialchars($sale['customer_name'] ?: 'Walk-in'); ?></td>
                    <td><?php echo htmlspecialchars($cashierName); ?></td>
                    <td style="text-align:center;"><?php echo $itemCount; ?></td>
                    <td style="font-weight:700; color:#eb3349;"><?php echo $currSymbol; ?><?php echo number_format($sale['invoice_h_gross_value'], 2); ?></td>
                    <td><?php echo $sale['pay_type'] ?: '—'; ?></td>
                    <td><span class="badge-sm <?php echo $sBadge; ?>"><?php echo $sText; ?></span></td>
                    <td class="time-ago"><?php echo $timeAgo; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

                </div>
            </div>
        </div>

    <?php include('common/footer.php');?>

    <!--[if lt IE 9]>
    <script src="assets/global/plugins/respond.min.js"></script>
    <script src="assets/global/plugins/excanvas.min.js"></script> 
    <![endif]-->
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
    <script src="assets/layouts/layout/scripts/demo.min.js" type="text/javascript"></script>

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <script>
    // ====== Auto-Refresh ======
    function loadKPI() { $('#autoRef').load('auto_order_counts.php'); }
    function loadInfo() { $('#autoIncome').load('auto_total_income.php'); }
    loadKPI(); loadInfo();
    setInterval(loadKPI, 30000);
    setInterval(loadInfo, 30000);

    // ====== Bar Chart: Last 7 Days Sales ======
    var salesCtx = document.getElementById('salesChart').getContext('2d');
    new Chart(salesCtx, {
        type: 'bar',
        data: {
            labels: <?php echo $chartLabels; ?>,
            datasets: [{
                label: 'Sales',
                data: <?php echo $chartData; ?>,
                backgroundColor: function(ctx) {
                    var gradient = salesCtx.createLinearGradient(0, 0, 0, 200);
                    gradient.addColorStop(0, 'rgba(79,172,254,0.8)');
                    gradient.addColorStop(1, 'rgba(0,242,254,0.4)');
                    return gradient;
                },
                borderColor: '#4facfe',
                borderWidth: 1,
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#2d3748',
                    titleFont: { size: 12, weight: '600' },
                    bodyFont: { size: 12 },
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(ctx) { return '<?php echo $currSymbol; ?>' + ctx.parsed.y.toLocaleString(undefined, {minimumFractionDigits:2}); }
                    }
                }
            },
            scales: {
                x: { 
                    grid: { display: false },
                    ticks: { color: '#8492a6', font: { size: 11, weight: '600' } }
                },
                y: { 
                    grid: { color: '#f0f0f5', drawBorder: false },
                    ticks: { 
                        color: '#8492a6', font: { size: 10 },
                        callback: function(val) {
                            if (val >= 1000) return '<?php echo $currSymbol; ?>' + (val/1000).toFixed(0) + 'K';
                            return '<?php echo $currSymbol; ?>' + val;
                        }
                    }
                }
            }
        }
    });

    // ====== Donut Chart: Payment Methods ======
    var payCtx = document.getElementById('paymentChart').getContext('2d');
    new Chart(payCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo $payLabelsJson; ?>,
            datasets: [{
                data: <?php echo $payDataJson; ?>,
                backgroundColor: <?php echo $payColorsJson; ?>,
                borderWidth: 2,
                borderColor: '#fff',
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 14,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        font: { size: 11, weight: '600' },
                        color: '#4a5568'
                    }
                },
                tooltip: {
                    backgroundColor: '#2d3748',
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(ctx) { return ctx.label + ': <?php echo $currSymbol; ?>' + ctx.parsed.toLocaleString(undefined, {minimumFractionDigits:2}); }
                    }
                }
            }
        }
    });
    </script>

</body>
</html>



