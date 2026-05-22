<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');
include('get_url.php');
date_default_timezone_set("Asia/Colombo");
$date = date("Y-m-d");

// Get currency
$currencyRow = $db->getRow('SELECT * FROM currency WHERE activated = ? LIMIT 1', ["Y"]);
$currSymbol = $currencyRow ? $currencyRow['currency'] : 'AUD';

// --- TODAY SALES ---
if (isSuperAdmin()) {
    $r = $db->getRow('SELECT COALESCE(SUM(invoice_h_gross_value),0) AS val FROM invoice_hedder WHERE invoice_h_status = 1 AND CAST(invoice_h_date AS DATE) = CAST(? AS DATE)', [$date]);
} else {
    $r = $db->getRow('SELECT COALESCE(SUM(invoice_h_gross_value),0) AS val FROM invoice_hedder WHERE invoice_h_status = 1 AND CAST(invoice_h_date AS DATE) = CAST(? AS DATE) AND invoice_h_location = ?', [$date, $_SESSION['location']]);
}
$todaySales = number_format($r['val'], 2);

// --- TODAY TRANSACTIONS ---
if (isSuperAdmin()) {
    $r = $db->getRow('SELECT COUNT(invoice_h_id) AS val FROM invoice_hedder WHERE CAST(invoice_h_date AS DATE) = CAST(? AS DATE)', [$date]);
} else {
    $r = $db->getRow('SELECT COUNT(invoice_h_id) AS val FROM invoice_hedder WHERE CAST(invoice_h_date AS DATE) = CAST(? AS DATE) AND invoice_h_location = ?', [$date, $_SESSION['location']]);
}
$todayTransactions = $r['val'];

// --- TODAY RETURNS ---
if (isSuperAdmin()) {
    $r = $db->getRow('SELECT COALESCE(SUM(sales_retrun_h_total),0) AS val FROM sales_return_hedder WHERE CAST(sales_retrun_h_date AS DATE) = CAST(? AS DATE)', [$date]);
} else {
    $r = $db->getRow('SELECT COALESCE(SUM(sales_retrun_h_total),0) AS val FROM sales_return_hedder WHERE CAST(sales_retrun_h_date AS DATE) = CAST(? AS DATE) AND sales_return_location = ?', [$date, $_SESSION['location']]);
}
$todayReturns = number_format($r['val'], 2);

// --- THIS MONTH ---
if (isSuperAdmin()) {
    $r = $db->getRow('SELECT COALESCE(SUM(invoice_h_gross_value),0) AS val FROM invoice_hedder WHERE invoice_h_status = 1 AND MONTH(invoice_h_date)=MONTH(CURRENT_DATE()) AND YEAR(invoice_h_date)=YEAR(CURRENT_DATE())');
} else {
    $r = $db->getRow('SELECT COALESCE(SUM(invoice_h_gross_value),0) AS val FROM invoice_hedder WHERE invoice_h_status = 1 AND MONTH(invoice_h_date)=MONTH(CURRENT_DATE()) AND YEAR(invoice_h_date)=YEAR(CURRENT_DATE()) AND invoice_h_location = ?', [$_SESSION['location']]);
}
$thisMonth = number_format($r['val'], 2);

// --- LAST MONTH for % change ---
if (isSuperAdmin()) {
    $r = $db->getRow('SELECT COALESCE(SUM(invoice_h_gross_value),0) AS val FROM invoice_hedder WHERE invoice_h_status = 1 AND YEAR(invoice_h_date)=YEAR(CURRENT_DATE - INTERVAL 1 MONTH) AND MONTH(invoice_h_date)=MONTH(CURRENT_DATE - INTERVAL 1 MONTH)');
} else {
    $r = $db->getRow('SELECT COALESCE(SUM(invoice_h_gross_value),0) AS val FROM invoice_hedder WHERE invoice_h_status = 1 AND YEAR(invoice_h_date)=YEAR(CURRENT_DATE - INTERVAL 1 MONTH) AND MONTH(invoice_h_date)=MONTH(CURRENT_DATE - INTERVAL 1 MONTH) AND invoice_h_location = ?', [$_SESSION['location']]);
}
$lastMonthVal = floatval($r['val']);
$thisMonthRaw = floatval(str_replace(',', '', $thisMonth));
$pctChange = ($lastMonthVal > 0) ? round((($thisMonthRaw - $lastMonthVal) / $lastMonthVal) * 100) : 0;
$pctSign = ($pctChange >= 0) ? '+' : '';

// --- CUSTOMERS TODAY ---
if (isSuperAdmin()) {
    $r = $db->getRow('SELECT COUNT(DISTINCT invoice_h_customer_id) AS val FROM invoice_hedder WHERE CAST(invoice_h_date AS DATE) = CAST(? AS DATE)', [$date]);
} else {
    $r = $db->getRow('SELECT COUNT(DISTINCT invoice_h_customer_id) AS val FROM invoice_hedder WHERE CAST(invoice_h_date AS DATE) = CAST(? AS DATE) AND invoice_h_location = ?', [$date, $_SESSION['location']]);
}
$customersToday = $r['val'];

// --- NET SALES (Today Sales - Today Returns) ---
$netSales = number_format(floatval(str_replace(',', '', $todaySales)) - floatval(str_replace(',', '', $todayReturns)), 2);
?>

<!-- ====== TOP ROW: 6 Gradient KPI Cards ====== -->
<div style="display:grid; grid-template-columns:repeat(6, 1fr); gap:14px; margin-bottom:16px;">
    
    <!-- Today Sales -->
    <div style="background:linear-gradient(135deg, #11998e, #38ef7d); border-radius:14px; padding:18px 18px 14px; color:#fff; position:relative; overflow:hidden; min-height:85px; box-shadow:0 4px 15px rgba(17,153,142,0.3);">
        <div style="position:absolute; top:12px; left:14px; width:36px; height:36px; background:rgba(255,255,255,0.2); border-radius:10px; display:flex; align-items:center; justify-content:center;">
            <i class="fa fa-dollar" style="font-size:16px;"></i>
        </div>
        <div style="margin-top:4px; text-align:right;">
            <div style="font-size:22px; font-weight:800; line-height:1.1;"><?php echo $currSymbol; ?><?php echo $todaySales; ?></div>
            <div style="font-size:11px; text-transform:uppercase; letter-spacing:1.5px; opacity:0.85; margin-top:4px; font-weight:600;">Today Sales</div>
        </div>
    </div>

    <!-- Transactions -->
    <div style="background:linear-gradient(135deg, #0083B0, #00B4DB); border-radius:14px; padding:18px 18px 14px; color:#fff; position:relative; overflow:hidden; min-height:85px; box-shadow:0 4px 15px rgba(0,131,176,0.3);">
        <div style="position:absolute; top:12px; left:14px; width:36px; height:36px; background:rgba(255,255,255,0.2); border-radius:10px; display:flex; align-items:center; justify-content:center;">
            <i class="fa fa-file-text-o" style="font-size:16px;"></i>
        </div>
        <div style="margin-top:4px; text-align:right;">
            <div style="font-size:22px; font-weight:800; line-height:1.1;"><?php echo $todayTransactions; ?></div>
            <div style="font-size:11px; text-transform:uppercase; letter-spacing:1.5px; opacity:0.85; margin-top:4px; font-weight:600;">Transactions</div>
        </div>
    </div>

    <!-- Returns -->
    <div style="background:linear-gradient(135deg, #56ab2f, #a8e063); border-radius:14px; padding:18px 18px 14px; color:#fff; position:relative; overflow:hidden; min-height:85px; box-shadow:0 4px 15px rgba(86,171,47,0.3);">
        <div style="position:absolute; top:12px; left:14px; width:36px; height:36px; background:rgba(255,255,255,0.2); border-radius:10px; display:flex; align-items:center; justify-content:center;">
            <i class="fa fa-undo" style="font-size:16px;"></i>
        </div>
        <div style="margin-top:4px; text-align:right;">
            <div style="font-size:22px; font-weight:800; line-height:1.1;"><?php echo $currSymbol; ?><?php echo $todayReturns; ?></div>
            <div style="font-size:11px; text-transform:uppercase; letter-spacing:1.5px; opacity:0.85; margin-top:4px; font-weight:600;">Returns</div>
        </div>
    </div>

    <!-- This Month -->
    <div style="background:linear-gradient(135deg, #f7971e, #ffd200); border-radius:14px; padding:18px 18px 14px; color:#fff; position:relative; overflow:hidden; min-height:85px; box-shadow:0 4px 15px rgba(247,151,30,0.3);">
        <div style="position:absolute; top:12px; left:14px; width:36px; height:36px; background:rgba(255,255,255,0.2); border-radius:10px; display:flex; align-items:center; justify-content:center;">
            <i class="fa fa-bar-chart" style="font-size:16px;"></i>
        </div>
        <div style="position:absolute; top:10px; right:14px;">
            <span style="background:rgba(255,255,255,0.25); padding:2px 8px; border-radius:10px; font-size:10px; font-weight:700;"><?php echo $pctSign.$pctChange; ?>%</span>
        </div>
        <div style="margin-top:4px; text-align:right;">
            <div style="font-size:22px; font-weight:800; line-height:1.1;"><?php echo $currSymbol; ?><?php echo $thisMonth; ?></div>
            <div style="font-size:11px; text-transform:uppercase; letter-spacing:1.5px; opacity:0.85; margin-top:4px; font-weight:600;">This Month</div>
        </div>
    </div>

    <!-- Customers Today -->
    <div style="background:linear-gradient(135deg, #667eea, #764ba2); border-radius:14px; padding:18px 18px 14px; color:#fff; position:relative; overflow:hidden; min-height:85px; box-shadow:0 4px 15px rgba(102,126,234,0.3);">
        <div style="position:absolute; top:12px; left:14px; width:36px; height:36px; background:rgba(255,255,255,0.2); border-radius:10px; display:flex; align-items:center; justify-content:center;">
            <i class="fa fa-users" style="font-size:16px;"></i>
        </div>
        <div style="margin-top:4px; text-align:right;">
            <div style="font-size:22px; font-weight:800; line-height:1.1;"><?php echo $customersToday; ?></div>
            <div style="font-size:11px; text-transform:uppercase; letter-spacing:1.5px; opacity:0.85; margin-top:4px; font-weight:600;">Customers Today</div>
        </div>
    </div>

    <!-- Net Sales -->
    <div style="background:linear-gradient(135deg, #eb3349, #f45c43); border-radius:14px; padding:18px 18px 14px; color:#fff; position:relative; overflow:hidden; min-height:85px; box-shadow:0 4px 15px rgba(235,51,73,0.3);">
        <div style="position:absolute; top:12px; left:14px; width:36px; height:36px; background:rgba(255,255,255,0.2); border-radius:10px; display:flex; align-items:center; justify-content:center;">
            <i class="fa fa-line-chart" style="font-size:16px;"></i>
        </div>
        <div style="margin-top:4px; text-align:right;">
            <div style="font-size:22px; font-weight:800; line-height:1.1;"><?php echo $currSymbol; ?><?php echo $netSales; ?></div>
            <div style="font-size:11px; text-transform:uppercase; letter-spacing:1.5px; opacity:0.85; margin-top:4px; font-weight:600;">Net Sales</div>
        </div>
    </div>

</div>



