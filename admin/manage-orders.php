<?php 
ob_start();
error_reporting (E_ALL ^ E_NOTICE);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('include/database.php');
include('include/check_login.php');

date_default_timezone_set("Asia/Colombo");
$today = date('Y-m-d');

// CSRF for individual order deletion
if (empty($_SESSION['delete_order_csrf'])) {
    $_SESSION['delete_order_csrf'] = bin2hex(random_bytes(32));
}
$deleteOrderCsrf = $_SESSION['delete_order_csrf'];
$canDeleteOrders = isSuperAdmin();

// AJAX: Return JSON of order-count per delivery_date for calendar dots
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Discard any buffered output from includes (whitespace, notices, etc.)
    if (ob_get_level()) ob_end_clean();
    // Suppress error display so warnings don't corrupt JSON output
    ini_set('display_errors', 0);
    error_reporting(0);
    header('Content-Type: application/json');

    $locationId = isset($_SESSION['location']) ? $_SESSION['location'] : 0;

    try {
        $db2 = new Database();

        if ($_POST['action'] === 'get_order_date_counts') {
            $month = isset($_POST['month']) ? (int)$_POST['month'] : (int)date('m');
            $year  = isset($_POST['year'])  ? (int)$_POST['year']  : (int)date('Y');
            $startDate = sprintf('%04d-%02d-01', $year, $month);
            $endDate   = date('Y-m-t', strtotime($startDate));

            if (isSuperAdmin()) {
                $rows = $db2->getRows(
                    "SELECT invoice_h_delivery_date AS d, COUNT(*) AS cnt 
                     FROM invoice_hedder 
                     WHERE invoice_h_delivery_date BETWEEN ? AND ?
                     GROUP BY invoice_h_delivery_date",
                    [$startDate, $endDate]
                );
            } else {
                $rows = $db2->getRows(
                    "SELECT invoice_h_delivery_date AS d, COUNT(*) AS cnt 
                     FROM invoice_hedder 
                     WHERE invoice_h_location = ? AND invoice_h_delivery_date BETWEEN ? AND ?
                     GROUP BY invoice_h_delivery_date",
                    [$locationId, $startDate, $endDate]
                );
            }
            $result = [];
            if ($rows) {
                foreach ($rows as $r) {
                    $result[$r['d']] = (int)$r['cnt'];
                }
            }
            echo json_encode($result);
            exit;
        }

        // Auto-migrate delivery columns (idempotent)
        $colCheckDel = $db2->getRow("SHOW COLUMNS FROM invoice_hedder LIKE 'delivery_status'");
        if (!$colCheckDel) {
            $db2->insertRow("ALTER TABLE invoice_hedder ADD COLUMN `delivery_status` VARCHAR(20) NOT NULL DEFAULT 'PENDING' AFTER `invoice_h_status`", []);
            $db2->insertRow("ALTER TABLE invoice_hedder ADD COLUMN `delivered_at`    DATETIME    DEFAULT NULL AFTER `delivery_status`", []);
            $db2->insertRow("ALTER TABLE invoice_hedder ADD COLUMN `delivered_by`    VARCHAR(100) DEFAULT NULL AFTER `delivered_at`", []);
        }

        if ($_POST['action'] === 'get_orders_by_date') {
            $deliveryDate = isset($_POST['delivery_date']) ? $_POST['delivery_date'] : $today;
            $statusFilter = isset($_POST['status_filter']) ? $_POST['status_filter'] : 'all';

            if (isSuperAdmin()) {
                    $query  = "SELECT h.*, c.customer_name, c.account_hold, c.locked,
                                  IFNULL((SELECT SUM(amount) FROM customer_balance WHERE invoice_h_id = h.invoice_h_id), 0) AS paid_amount
                           FROM invoice_hedder h
                           LEFT JOIN customer c ON c.customer_id = h.invoice_h_customer_id
                           WHERE h.invoice_h_delivery_date = ?";
                $params = [$deliveryDate];
            } else {
                        $query  = "SELECT h.*, c.customer_name, c.account_hold, c.locked,
                                      IFNULL((SELECT SUM(amount) FROM customer_balance WHERE invoice_h_id = h.invoice_h_id), 0) AS paid_amount
                               FROM invoice_hedder h
                           LEFT JOIN customer c ON c.customer_id = h.invoice_h_customer_id
                           WHERE h.invoice_h_location = ? AND h.invoice_h_delivery_date = ?";
                $params = [$locationId, $deliveryDate];
            }

            switch ($statusFilter) {
                case 'pending':   $query .= ' AND h.invoice_h_status = 0';  break;
                case 'accepted':  $query .= ' AND h.invoice_h_status = 1';  break;
                case 'cancelled': $query .= ' AND h.invoice_h_status = -1'; break;
            }
            $query .= ' ORDER BY h.invoice_h_id DESC';
            $orders = $db2->getRows($query, $params);

            // Get currency
            $cur = $db2->getRow("SELECT currency FROM currency WHERE activated = 'Y' LIMIT 1");
            $currency = $cur ? $cur['currency'] : '$';

            $result = [];
            if ($orders) {
                foreach ($orders as $o) {
                    $net  = floatval($o['invoice_h_net_value']);
                    $paid = floatval($o['paid_amount']);
                    if ($paid >= $net) { $payStatus = 'Paid'; $payClass = 'paid'; }
                    elseif ($paid > 0) { $payStatus = 'Partial'; $payClass = 'partial'; }
                    else               { $payStatus = 'Pending'; $payClass = 'pending'; }

                    $os = (int)$o['invoice_h_status'];
                    $isHold = (isset($o['account_hold']) && (int)$o['account_hold'] === 1)
                        || (isset($o['locked']) && (int)$o['locked'] === 1);
                    if ($isHold && $os !== -1) {
                        $orderStatus = 'Hold';
                    } elseif ($os === 1) {
                        $orderStatus = 'Accept';
                    } elseif ($os === 0) {
                        $orderStatus = 'Pending';
                    } elseif ($os === -1) {
                        $orderStatus = 'Canceled';
                    } else {
                        $orderStatus = 'Unknown';
                    }

                    $result[] = [
                        'id'              => $o['invoice_h_id'],
                        'code'            => $o['invoice_h_code'],
                        'customer'        => isset($o['customer_name']) ? $o['customer_name'] : 'N/A',
                        'datetime'        => $o['invoice_h_datetime'],
                        'delivery_date'   => $o['invoice_h_delivery_date'],
                        'gross'           => number_format(floatval($o['invoice_h_gross_value']), 2),
                        'order_status'    => $orderStatus,
                        'status_code'     => $os,
                        'pay_status'      => $payStatus,
                        'pay_class'       => $payClass,
                        'order_note'      => isset($o['invoice_h_order_note']) ? $o['invoice_h_order_note'] : '',
                        'currency'        => $currency,
                        'delivery_status' => isset($o['delivery_status']) ? $o['delivery_status'] : 'PENDING'
                    ];
                }
            }
            echo json_encode(['orders' => $result, 'total' => count($result), 'currency' => $currency]);
            exit;
        }

    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage(), 'orders' => [], 'total' => 0]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Manage Orders | STOCK MANAGEMENT SYSTEM</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/airbnb.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-500: #6b7280;
            --gray-700: #374151;
            --gray-900: #111827;
            --radius: 10px;
        }

        body { font-family: 'Inter', sans-serif; }

        .mo-container { max-width: 1600px; margin: 0 auto; padding: 0 15px; }

        /* Header Card */
        .mo-header {
            background: #fff;
            border-radius: var(--radius);
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            border: 1px solid var(--gray-200);
            padding: 18px 24px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }
        .mo-header h1 {
            font-size: 22px;
            font-weight: 800;
            color: var(--gray-900);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .mo-header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .mo-header h1 .icon-wrap {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, var(--primary), #8b5cf6);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 16px;
        }
        .mo-date-badge {
            background: linear-gradient(135deg, var(--primary), #8b5cf6);
            color: #fff;
            padding: 8px 18px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 14px;
        }

        /* Top Row - Calendar + Stats */
        .mo-top-row {
            display: grid;
            grid-template-columns: 340px 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }
        @media (max-width: 900px) {
            .mo-top-row { grid-template-columns: 1fr; }
        }

        .mo-card {
            background: #fff;
            border-radius: var(--radius);
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            border: 1px solid var(--gray-200);
            overflow: hidden;
        }
        .mo-card-header {
            padding: 14px 18px;
            border-bottom: 1px solid var(--gray-200);
            font-weight: 700;
            font-size: 14px;
            color: var(--gray-700);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .mo-card-header i { color: var(--primary); }
        .mo-card-body { padding: 16px 18px; }

        /* Calendar Overrides */
        #calendarWrap .flatpickr-calendar {
            box-shadow: none !important;
            border: none !important;
            width: 100% !important;
        }
        .flatpickr-day.has-orders {
            background: var(--danger) !important;
            color: #fff !important;
            border-color: var(--danger) !important;
            font-weight: 700;
            position: relative;
        }
        .flatpickr-day.has-orders:hover {
            background: #dc2626 !important;
            border-color: #dc2626 !important;
        }
        .flatpickr-day.has-orders.selected {
            background: #b91c1c !important;
            border-color: #b91c1c !important;
            box-shadow: 0 0 0 3px rgba(239,68,68,0.3);
        }
        .flatpickr-day.has-orders::after {
            content: '';
            position: absolute;
            bottom: 2px; left: 50%;
            transform: translateX(-50%);
            width: 4px; height: 4px;
            border-radius: 50%;
            background: #fff;
        }
        .cal-legend {
            display: flex; gap: 12px; margin-top: 10px; font-size: 11px; color: var(--gray-500);
        }
        .cal-legend span { display: flex; align-items: center; gap: 4px; }
        .cal-legend .dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
        .cal-legend .dot.red { background: var(--danger); }
        .cal-legend .dot.blue { background: var(--primary); }

        /* Stat Boxes */
        .mo-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
        }
        .mo-stat {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            border-left: 3px solid var(--gray-300);
        }
        .mo-stat:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .mo-stat.active { border-left-color: var(--primary); background: #eef2ff; }
        .mo-stat.stat-all     { border-left-color: var(--primary); }
        .mo-stat.stat-pending { border-left-color: var(--warning); }
        .mo-stat.stat-accept  { border-left-color: var(--success); }
        .mo-stat.stat-cancel  { border-left-color: var(--danger); }
        .mo-stat-count { font-size: 28px; font-weight: 800; color: var(--gray-900); }
        .mo-stat-label { font-size: 11px; font-weight: 600; color: var(--gray-500); text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; }

        /* Filter buttons */
        .mo-filters {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            align-items: center;
        }
        .mo-filter-btn {
            padding: 7px 14px;
            border: 1px solid var(--gray-200);
            background: #fff;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            color: var(--gray-700);
        }
        .mo-filter-btn:hover { border-color: var(--primary); color: var(--primary); }
        .mo-filter-btn.active { background: var(--primary); color: #fff; border-color: var(--primary); }

        .mo-table-header {
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
        .mo-table-tools {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-left: auto;
        }
        .mo-search {
            position: relative;
            display: flex;
            align-items: center;
            min-width: 260px;
            max-width: 360px;
            width: 100%;
        }
        .mo-search i {
            position: absolute;
            left: 12px;
            color: var(--gray-500);
            font-size: 12px;
            pointer-events: none;
        }
        .mo-search input {
            width: 100%;
            height: 38px;
            border: 1px solid var(--gray-200);
            border-radius: 999px;
            padding: 0 14px 0 34px;
            font-size: 13px;
            color: var(--gray-700);
            background: #fff;
        }
        .mo-search input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99,102,241,0.12);
        }
        @media (max-width: 640px) {
            .mo-table-tools {
                width: 100%;
            }
            .mo-search {
                min-width: 100%;
                max-width: none;
            }
        }

        /* Order Table */
        .mo-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .mo-table thead th {
            background: var(--gray-50);
            padding: 12px 14px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--gray-500);
            border-bottom: 2px solid var(--gray-200);
            text-align: left;
            white-space: nowrap;
        }
        .mo-table tbody td {
            padding: 12px 14px;
            font-size: 13px;
            color: var(--gray-700);
            border-bottom: 1px solid var(--gray-100);
            vertical-align: middle;
        }
        .mo-table tbody tr:hover { background: var(--gray-50); }

        .badge-status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-accept  { background: #d1fae5; color: #065f46; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-cancel  { background: #fee2e2; color: #991b1b; }
        .badge-hold    { background: #fde68a; color: #92400e; }
        .badge-unknown { background: var(--gray-100); color: var(--gray-500); }

        .badge-pay { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; }
        .badge-pay.paid    { background: #d1fae5; color: #065f46; }
        .badge-pay.partial { background: #fef3c7; color: #92400e; }
        .badge-pay.pending { background: #fee2e2; color: #991b1b; }

        .mo-btn {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            border: 1px solid var(--gray-200);
            background: #fff;
            color: var(--gray-700);
            cursor: pointer;
            transition: all 0.15s;
            text-decoration: none;
            margin: 1px;
        }
        .mo-btn:hover { border-color: var(--primary); color: var(--primary); }
        .mo-btn.green { border-color: var(--success); color: var(--success); }
        .mo-btn.green:hover { background: var(--success); color: #fff; }
        .mo-btn.blue { border-color: var(--primary); color: var(--primary); }
        .mo-btn.blue:hover { background: var(--primary); color: #fff; }
        .mo-btn.dark { border-color: var(--gray-700); color: var(--gray-700); }
        .mo-btn.dark:hover { background: var(--gray-700); color: #fff; }
        .mo-btn.red { border-color: var(--danger); color: var(--danger); }
        .mo-btn.red:hover { background: var(--danger); color: #fff; }

        .mo-empty {
            text-align: center;
            padding: 50px 20px;
            color: var(--gray-500);
        }
        .mo-empty i { font-size: 48px; margin-bottom: 12px; display: block; color: var(--gray-300); }
        .mo-empty p { font-size: 14px; margin: 0; }

        .mo-loading {
            text-align: center;
            padding: 40px;
            color: var(--gray-500);
        }
        .mo-loading i { font-size: 24px; margin-bottom: 8px; }

        .invoice-link { color: var(--primary); font-weight: 600; text-decoration: none; }
        .invoice-link:hover { text-decoration: underline; }
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
                <div class="mo-container">

                    <!-- Header -->
                    <div class="mo-header">
                        <h1>
                            <span class="icon-wrap"><i class="fa fa-list-alt"></i></span>
                            Manage Orders
                        </h1>
                        <div class="mo-header-actions">
                            <a href="packing-slip.php?selected_date=<?php echo $today; ?>" class="btn btn-sm btn-primary">
                                <i class="fa fa-file-pdf-o"></i> Packing Slips By Date
                            </a>
                            <span class="mo-date-badge" id="selectedDateBadge">
                                <i class="fa fa-calendar"></i> <?php echo date('D, M d, Y'); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Top Row: Calendar + Stats -->
                    <div class="mo-top-row">
                        <!-- Calendar Card -->
                        <div class="mo-card">
                            <div class="mo-card-header">
                                <i class="fa fa-calendar"></i> Delivery Calendar
                            </div>
                            <div class="mo-card-body">
                                <div id="calendarWrap">
                                    <div id="orderCalendar"></div>
                                </div>
                                <div class="cal-legend">
                                    <span><span class="dot red"></span> Has orders</span>
                                    <span><span class="dot blue"></span> Selected</span>
                                </div>
                            </div>
                        </div>

                        <!-- Stats + Filters Card -->
                        <div class="mo-card">
                            <div class="mo-card-header">
                                <i class="fa fa-bar-chart"></i> Summary for <span id="summaryDateLabel"><?php echo date('M d, Y'); ?></span>
                            </div>
                            <div class="mo-card-body">
                                <div class="mo-stats" id="statsContainer">
                                    <div class="mo-stat stat-all active" data-filter="all">
                                        <div class="mo-stat-count" id="countAll">-</div>
                                        <div class="mo-stat-label">Total Orders</div>
                                    </div>
                                    <div class="mo-stat stat-accept" data-filter="accepted">
                                        <div class="mo-stat-count" id="countAccepted">-</div>
                                        <div class="mo-stat-label">Accepted</div>
                                    </div>
                                    <div class="mo-stat stat-pending" data-filter="pending">
                                        <div class="mo-stat-count" id="countPending">-</div>
                                        <div class="mo-stat-label">Pending</div>
                                    </div>
                                    <div class="mo-stat stat-cancel" data-filter="cancelled">
                                        <div class="mo-stat-count" id="countCancelled">-</div>
                                        <div class="mo-stat-label">Cancelled</div>
                                    </div>
                                </div>
                                <div style="margin-top: 16px;">
                                    <div class="mo-filters">
                                        <span style="font-size:12px;font-weight:600;color:var(--gray-500);margin-right:4px;"><i class="fa fa-filter"></i> Filter:</span>
                                        <button class="mo-filter-btn active" data-filter="all">All</button>
                                        <button class="mo-filter-btn" data-filter="accepted"><i class="fa fa-check"></i> Accepted</button>
                                        <button class="mo-filter-btn" data-filter="pending"><i class="fa fa-clock-o"></i> Pending</button>
                                        <button class="mo-filter-btn" data-filter="cancelled"><i class="fa fa-times"></i> Cancelled</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Orders Table Card -->
                    <div class="mo-card">
                        <div class="mo-card-header mo-table-header">
                            <span><i class="fa fa-shopping-cart"></i> Orders — <span id="tableDate"><?php echo date('M d, Y'); ?></span></span>
                            <div class="mo-table-tools">
                                <label class="mo-search" for="orderSearch">
                                    <i class="fa fa-search"></i>
                                    <input type="text" id="orderSearch" placeholder="Search invoice, customer, status..." autocomplete="off">
                                </label>
                                <span style="font-size:12px;color:var(--gray-500);" id="orderCountLabel">0 orders</span>
                            </div>
                        </div>
                        <div class="mo-card-body" style="padding:0;">
                            <div id="ordersTableWrap">
                                <div class="mo-loading"><i class="fa fa-spinner fa-spin"></i><br>Loading orders...</div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <?php include('common/footer.php'); ?>

    <!-- Order Status Modal -->
    <div id="myModal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Order Status</h4>
                </div>
                <form method="POST" id="order-frm">
                    <div class="modal-body">
                        <small>Note: You cannot change after you submit order status. Contact administrator if there is an issue.</small>
                        <div style="margin-top:10px;">
                            <label>Select Order Status</label>
                            <select name="status" class="form-control">
                                <option value="1">Accept Order</option>
                                <option value="-1">Cancel Order</option>
                            </select>
                        </div>
                        <input type="hidden" name="invoiceId" id="modalInvoiceId">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Submit Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Order Note Modal -->
    <div id="orderNote" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Customer Order Note</h4>
                </div>
                <div class="modal-body"><p id="order_note"></p></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/global/plugins/jquery.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/js.cookie.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/bootstrap-hover-dropdown/bootstrap-hover-dropdown.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/jquery-slimscroll/jquery.slimscroll.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/jquery.blockui.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/uniform/jquery.uniform.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/bootstrap-switch/js/bootstrap-switch.min.js" type="text/javascript"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="assets/global/scripts/app.min.js" type="text/javascript"></script>
    <script src="assets/layouts/layout/scripts/layout.min.js" type="text/javascript"></script>

    <script>
    $(document).ready(function() {
        var selectedDate = '<?php echo $today; ?>';
        var currentFilter = 'all';
        var orderDateCounts = {};
        var calendarInstance = null;
        var loadedOrders = [];
        var currentCurrency = '$';
        var canDeleteOrders = <?php echo $canDeleteOrders ? 'true' : 'false'; ?>;

        // ── Initialize Flatpickr (inline calendar) ──
        calendarInstance = flatpickr('#orderCalendar', {
            inline: true,
            dateFormat: 'Y-m-d',
            defaultDate: selectedDate,
            onDayCreate: function(dObj, dStr, fp, dayElem) {
                var ds = dayElem.dateObj.getFullYear() + '-' +
                    String(dayElem.dateObj.getMonth() + 1).padStart(2, '0') + '-' +
                    String(dayElem.dateObj.getDate()).padStart(2, '0');
                if (orderDateCounts[ds]) {
                    dayElem.classList.add('has-orders');
                    dayElem.title = orderDateCounts[ds] + ' order(s)';
                }
            },
            onChange: function(selectedDates, dateStr) {
                selectedDate = dateStr;
                currentFilter = 'all';
                updateDateLabels();
                loadOrders();
                setActiveFilter('all');
            },
            onMonthChange: function(selectedDates, dateStr, instance) {
                loadCalendarDots(instance.currentMonth + 1, instance.currentYear);
            },
            onYearChange: function(selectedDates, dateStr, instance) {
                loadCalendarDots(instance.currentMonth + 1, instance.currentYear);
            }
        });
        // ── Helpers ──
        function formatDisplayDate(dateStr) {
            var d = new Date(dateStr + 'T00:00:00');
            return d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
        }

        function updateDateLabels() {
            var nice = formatDisplayDate(selectedDate);
            $('#selectedDateBadge').html('<i class="fa fa-calendar"></i> ' + nice);
            $('#summaryDateLabel').text(nice);
            $('#tableDate').text(nice);
        }

        function setActiveFilter(f) {
            currentFilter = f;
            $('.mo-filter-btn').removeClass('active');
            $('.mo-filter-btn[data-filter="' + f + '"]').addClass('active');
            $('.mo-stat').removeClass('active');
            $('.mo-stat[data-filter="' + f + '"]').addClass('active');
        }

        function escapeHtml(t) {
            if (!t) return '';
            var d = document.createElement('div');
            d.appendChild(document.createTextNode(t));
            return d.innerHTML;
        }

        function getSearchTerm() {
            return $.trim($('#orderSearch').val() || '').toLowerCase();
        }

        function buildOrderSearchText(order) {
            return [
                order.id,
                order.code,
                order.customer,
                order.datetime,
                order.delivery_date,
                order.order_status,
                order.pay_status,
                order.gross,
                order.order_note,
                order.delivery_status
            ].join(' ').toLowerCase();
        }

        function getVisibleOrders() {
            var term = getSearchTerm();
            if (!term) {
                return loadedOrders.slice();
            }
            return loadedOrders.filter(function(order) {
                return buildOrderSearchText(order).indexOf(term) !== -1;
            });
        }

        function updateOrderCountLabel(visibleCount, totalCount) {
            if (visibleCount === totalCount) {
                $('#orderCountLabel').text(visibleCount + ' order(s)');
                return;
            }
            $('#orderCountLabel').text(visibleCount + ' of ' + totalCount + ' order(s)');
        }

        function renderOrders(orders) {
            var searchTerm = $('#orderSearch').val() || '';
            var cur = currentCurrency;

            updateOrderCountLabel(orders.length, loadedOrders.length);

            if (orders.length === 0) {
                var emptyMessage = 'No orders found for this date';
                if (loadedOrders.length > 0 && $.trim(searchTerm) !== '') {
                    emptyMessage = 'No orders match "' + escapeHtml($.trim(searchTerm)) + '"';
                }
                $('#ordersTableWrap').html(
                    '<div class="mo-empty"><i class="fa fa-inbox"></i><p>' + emptyMessage + '</p></div>'
                );
                return;
            }

            var html = '<table class="mo-table"><thead><tr>' +
                '<th>Invoice</th><th>Customer</th><th>Order Date</th>' +
                '<th>Delivery</th><th>Total</th><th>Status</th><th>Payment</th><th>Actions</th>' +
                '</tr></thead><tbody>';

            orders.forEach(function(o) {
                var statusBadge = '';
                if (o.order_status === 'Accept')   statusBadge = '<span class="badge-status badge-accept">Accepted</span>';
                else if (o.order_status === 'Pending')  statusBadge = '<span class="badge-status badge-pending">Pending</span>';
                else if (o.order_status === 'Hold')     statusBadge = '<span class="badge-status badge-hold">Hold</span>';
                else if (o.order_status === 'Canceled') statusBadge = '<span class="badge-status badge-cancel">Cancelled</span>';
                else statusBadge = '<span class="badge-status badge-unknown">' + escapeHtml(o.order_status) + '</span>';

                var payBadge = '<span class="badge-pay ' + o.pay_class + '">' + o.pay_status + '</span>';

                var actions = '';
                if (o.status_code == 0) {
                    actions += '<button class="mo-btn dark btn-order-status" data-id="' + o.id + '"><i class="fa fa-gavel"></i> Status</button> ';
                }
                if (o.order_note) {
                    actions += '<button class="mo-btn blue btn-order-note" data-note="' + escapeHtml(o.order_note) + '"><i class="fa fa-comment"></i></button> ';
                }
                actions += '<a href="order-detail.php?order_id=' + o.id + '" target="_blank" class="mo-btn green"><i class="fa fa-eye"></i> Details</a> ';
                actions += '<a href="invoice.php?id=' + o.id + '" target="_blank" class="mo-btn blue"><i class="fa fa-file-text"></i> Invoice</a> ';
                actions += '<a href="packing-slip.php?id=' + o.id + '" target="_blank" class="mo-btn"><i class="fa fa-file-text-o"></i></a> ';
                var isStandingOrder = (o.order_note === 'Standing Order');
                var todayStr = '<?php echo $today; ?>';
                if (canDeleteOrders && (o.status_code == 0 || isStandingOrder) && o.delivery_date > todayStr) {
                    actions += '<button class="mo-btn red btn-delete-order" data-id="' + o.id + '" data-code="' + escapeHtml(o.code) + '"><i class="fa fa-trash"></i> Delete</button>';
                }
                if (o.status_code == 1) {
                    if (o.delivery_status === 'DELIVERED') {
                        actions += '<a href="invoice-delivery.php?id=' + o.id + '" class="mo-btn" style="background:#27ae60;color:#fff;"><i class="fa fa-truck"></i> Delivered</a>';
                    } else {
                        actions += '<a href="invoice-delivery.php?id=' + o.id + '" class="mo-btn" style="background:#f39c12;color:#fff;"><i class="fa fa-truck"></i> Mark Delivered</a>';
                    }
                }

                html += '<tr>' +
                    '<td><a href="invoice.php?id=' + o.id + '" target="_blank" class="invoice-link">' + escapeHtml(o.code) + '</a></td>' +
                    '<td>' + escapeHtml(o.customer) + '</td>' +
                    '<td>' + escapeHtml(o.datetime) + '</td>' +
                    '<td>' + escapeHtml(o.delivery_date) + '</td>' +
                    '<td>' + cur + ' ' + o.gross + '</td>' +
                    '<td>' + statusBadge + '</td>' +
                    '<td>' + payBadge + '</td>' +
                    '<td>' + actions + '</td>' +
                    '</tr>';
            });

            html += '</tbody></table>';
            $('#ordersTableWrap').html(html);
        }

        function refreshVisibleOrders() {
            renderOrders(getVisibleOrders());
        }

        // ── Load calendar dots (order counts per date) ──
        function loadCalendarDots(month, year) {
            $.post('manage-orders.php', { action: 'get_order_date_counts', month: month, year: year }, function(data) {
                orderDateCounts = data;
                if (calendarInstance) calendarInstance.redraw();
            }, 'json').fail(function(xhr, status, err) {
                console.error('Calendar dots AJAX error:', status, err, xhr.responseText);
            });
        }

        // ── Load orders for selected date ──
        function loadOrders() {
            $('#ordersTableWrap').html('<div class="mo-loading"><i class="fa fa-spinner fa-spin"></i><br>Loading orders...</div>');

            $.post('manage-orders.php', {
                action: 'get_orders_by_date',
                delivery_date: selectedDate,
                status_filter: currentFilter
            }, function(resp) {
                loadedOrders = resp.orders || [];
                currentCurrency = resp.currency || '$';
                var orders = loadedOrders;

                // Update stats
                var all = 0, accepted = 0, pending = 0, cancelled = 0;
                orders.forEach(function(o) {
                    all++;
                    if (o.status_code == 1)       accepted++;
                    else if (o.status_code == 0)   pending++;
                    else if (o.status_code == -1)  cancelled++;
                });

                // If filter is 'all', update stat counts; otherwise, show filtered total
                if (currentFilter === 'all') {
                    $('#countAll').text(all);
                    $('#countAccepted').text(accepted);
                    $('#countPending').text(pending);
                    $('#countCancelled').text(cancelled);
                }
                refreshVisibleOrders();

            }, 'json').fail(function() {
                loadedOrders = [];
                currentCurrency = '$';
                updateOrderCountLabel(0, 0);
                $('#ordersTableWrap').html('<div class="mo-empty"><i class="fa fa-exclamation-triangle"></i><p>Error loading orders. Please try again.</p></div>');
            });
        }

        // ── Filter button clicks ──
        $(document).on('click', '.mo-filter-btn', function() {
            setActiveFilter($(this).data('filter'));
            loadOrders();
        });
        $(document).on('click', '.mo-stat', function() {
            setActiveFilter($(this).data('filter'));
            loadOrders();
        });
        $(document).on('input', '#orderSearch', function() {
            refreshVisibleOrders();
        });

        // ── Order Status modal ──
        $(document).on('click', '.btn-order-status', function() {
            $('#modalInvoiceId').val($(this).data('id'));
            $('#myModal').modal('show');
        });

        // ── Order Note modal ──
        $(document).on('click', '.btn-order-note', function() {
            $('#order_note').text($(this).data('note'));
            $('#orderNote').modal('show');
        });

        // ── Delete individual order ──
        var DELETE_ORDER_CSRF = <?php echo json_encode($deleteOrderCsrf); ?>;
        $(document).on('click', '.btn-delete-order', function() {
            var $btn    = $(this);
            var invId   = $btn.data('id');
            var invCode = $btn.data('code');
            if (!confirm('Delete order ' + invCode + '?\n\nThis action cannot be undone.')) return;
            $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
            $.ajax({
                url: 'process/delete-order.php',
                method: 'POST',
                contentType: 'application/json',
                dataType: 'json',
                data: JSON.stringify({ csrf_token: DELETE_ORDER_CSRF, invoice_id: invId })
            }).done(function(res) {
                if (res && res.status === true) {
                    loadOrders();
                    loadCalendarDots(calendarInstance.currentMonth + 1, calendarInstance.currentYear);
                } else {
                    alert((res && res.message) ? res.message : 'Failed to delete order.');
                    $btn.prop('disabled', false).html('<i class="fa fa-trash"></i> Delete');
                }
            }).fail(function() {
                alert('Network error. Please try again.');
                $btn.prop('disabled', false).html('<i class="fa fa-trash"></i> Delete');
            });
        });

        // ── Submit order status ──
        $(document).on('submit', '#order-frm', function(e) {
            e.preventDefault();
            $.ajax({
                type: 'POST',
                url: 'process/order-status-process.php',
                data: $(this).serialize(),
                success: function() {
                    $('#myModal').modal('hide');
                    loadOrders();
                    // Refresh calendar dots
                    var m = calendarInstance.currentMonth + 1;
                    var y = calendarInstance.currentYear;
                    loadCalendarDots(m, y);
                }
            });
        });

        // ── Initial load ──
        var initMonth = parseInt('<?php echo date('m'); ?>');
        var initYear  = parseInt('<?php echo date('Y'); ?>');
        loadCalendarDots(initMonth, initYear);
        loadOrders();
    });
    </script>
</body>
</html>





