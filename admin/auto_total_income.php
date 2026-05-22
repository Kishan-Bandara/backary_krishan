<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');
include('get_url.php');
date_default_timezone_set("Asia/Colombo");

// --- PRODUCTS ---
$r = $db->getRow('SELECT COUNT(item_id) AS val FROM item_master');
$productCount = $r['val'];

// --- SUPPLIERS ---
$r = $db->getRow('SELECT COUNT(supplier_id) AS val FROM supplier WHERE is_active = 1');
$supplierCount = $r['val'];

// --- CUSTOMERS ---
$r = $db->getRow('SELECT COUNT(customer_id) AS val FROM customer WHERE new_customer = 0');
$customerCount = $r['val'];

// --- STAFF ---
$r = $db->getRow("SELECT COUNT(userid) AS val FROM users WHERE activated = 'Y'");
$staffCount = $r['val'];

// --- PENDING TRANSFERS ---
$pendingTransfers = 0;
try {
    $r = $db->getRow("SELECT COUNT(transfer_id) AS val FROM stock_transfer_header WHERE status = 'PENDING'");
    $pendingTransfers = $r['val'] ?? 0;
} catch(Exception $e) { $pendingTransfers = 0; }

// --- PENDING ORDERS ---
$date = date("Y-m-d");
if (isSuperAdmin()) {
    $r = $db->getRow('SELECT COUNT(invoice_h_id) AS val FROM invoice_hedder WHERE invoice_h_status = 0 AND CAST(invoice_h_date AS DATE) = CAST(? AS DATE)', [$date]);
} else {
    $r = $db->getRow('SELECT COUNT(invoice_h_id) AS val FROM invoice_hedder WHERE invoice_h_status = 0 AND CAST(invoice_h_date AS DATE) = CAST(? AS DATE) AND invoice_h_location = ?', [$date, $_SESSION['location']]);
}
$pendingOrders = $r['val'];
?>

<!-- ====== SECOND ROW: 6 Light Info Cards ====== -->
<div style="display:grid; grid-template-columns:repeat(6, 1fr); gap:14px; margin-bottom:20px;">
    
    <div style="background:#fff; border-radius:12px; padding:16px 18px; display:flex; align-items:center; gap:14px; box-shadow:0 2px 8px rgba(0,0,0,0.04);">
        <div style="width:38px; height:38px; background:#eef2ff; border-radius:10px; display:flex; align-items:center; justify-content:center;">
            <i class="fa fa-cube" style="font-size:16px; color:#667eea;"></i>
        </div>
        <div>
            <div style="font-size:20px; font-weight:700; color:#2d3748; line-height:1;"><?php echo $productCount; ?></div>
            <div style="font-size:11px; color:#8492a6; text-transform:uppercase; letter-spacing:0.8px; font-weight:600;">Products</div>
        </div>
    </div>

    <div style="background:#fff; border-radius:12px; padding:16px 18px; display:flex; align-items:center; gap:14px; box-shadow:0 2px 8px rgba(0,0,0,0.04);">
        <div style="width:38px; height:38px; background:#e0f7fa; border-radius:10px; display:flex; align-items:center; justify-content:center;">
            <i class="fa fa-truck" style="font-size:16px; color:#0083B0;"></i>
        </div>
        <div>
            <div style="font-size:20px; font-weight:700; color:#2d3748; line-height:1;"><?php echo $supplierCount; ?></div>
            <div style="font-size:11px; color:#8492a6; text-transform:uppercase; letter-spacing:0.8px; font-weight:600;">Suppliers</div>
        </div>
    </div>

    <div style="background:#fff; border-radius:12px; padding:16px 18px; display:flex; align-items:center; gap:14px; box-shadow:0 2px 8px rgba(0,0,0,0.04);">
        <div style="width:38px; height:38px; background:#e8f5e9; border-radius:10px; display:flex; align-items:center; justify-content:center;">
            <i class="fa fa-users" style="font-size:16px; color:#43a047;"></i>
        </div>
        <div>
            <div style="font-size:20px; font-weight:700; color:#2d3748; line-height:1;"><?php echo $customerCount; ?></div>
            <div style="font-size:11px; color:#8492a6; text-transform:uppercase; letter-spacing:0.8px; font-weight:600;">Customers</div>
        </div>
    </div>

    <div style="background:#fff; border-radius:12px; padding:16px 18px; display:flex; align-items:center; gap:14px; box-shadow:0 2px 8px rgba(0,0,0,0.04);">
        <div style="width:38px; height:38px; background:#fff3e0; border-radius:10px; display:flex; align-items:center; justify-content:center;">
            <i class="fa fa-user-secret" style="font-size:16px; color:#f57c00;"></i>
        </div>
        <div>
            <div style="font-size:20px; font-weight:700; color:#2d3748; line-height:1;"><?php echo $staffCount; ?></div>
            <div style="font-size:11px; color:#8492a6; text-transform:uppercase; letter-spacing:0.8px; font-weight:600;">Staff</div>
        </div>
    </div>

    <div style="background:#fff; border-radius:12px; padding:16px 18px; display:flex; align-items:center; gap:14px; box-shadow:0 2px 8px rgba(0,0,0,0.04);">
        <div style="width:38px; height:38px; background:#f3e5f5; border-radius:10px; display:flex; align-items:center; justify-content:center;">
            <i class="fa fa-exchange" style="font-size:16px; color:#8e24aa;"></i>
        </div>
        <div>
            <div style="font-size:20px; font-weight:700; color:#2d3748; line-height:1;"><?php echo $pendingTransfers; ?></div>
            <div style="font-size:11px; color:#8492a6; text-transform:uppercase; letter-spacing:0.8px; font-weight:600;">Pending Transfers</div>
        </div>
    </div>

    <div style="background:#fff; border-radius:12px; padding:16px 18px; display:flex; align-items:center; gap:14px; box-shadow:0 2px 8px rgba(0,0,0,0.04);">
        <div style="width:38px; height:38px; background:#fce4ec; border-radius:10px; display:flex; align-items:center; justify-content:center;">
            <i class="fa fa-clock-o" style="font-size:16px; color:#e53935;"></i>
        </div>
        <div>
            <div style="font-size:20px; font-weight:700; color:#2d3748; line-height:1;"><?php echo $pendingOrders; ?></div>
            <div style="font-size:11px; color:#8492a6; text-transform:uppercase; letter-spacing:0.8px; font-weight:600;">Pending Orders</div>
        </div>
    </div>

</div>



