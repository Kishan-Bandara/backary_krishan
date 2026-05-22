<?php 
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('include/database.php');
include('include/check_login.php');

$db = new Database();

// Get current month/year from query params or default to current
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');

// Clamp month and adjust year
if ($month < 1)  { $month = 12; $year--; }
if ($month > 12) { $month = 1;  $year++; }

// Get order counts grouped by delivery date for this month
$startDate = sprintf('%04d-%02d-01', $year, $month);
$endDate   = date('Y-m-t', strtotime($startDate)); // last day of month

$orderCounts = [];
$rows = $db->getRows(
    "SELECT invoice_h_delivery_date AS d, COUNT(*) AS cnt 
     FROM invoice_hedder 
     WHERE invoice_h_location = ? 
       AND invoice_h_delivery_date BETWEEN ? AND ?
     GROUP BY invoice_h_delivery_date",
    [$_SESSION['location'], $startDate, $endDate]
);
if ($rows) {
    foreach ($rows as $row) {
        $orderCounts[$row['d']] = (int)$row['cnt'];
    }
}

// Also get status breakdown per day
$statusRows = $db->getRows(
    "SELECT invoice_h_delivery_date AS d, invoice_h_status AS st, COUNT(*) AS cnt 
     FROM invoice_hedder 
     WHERE invoice_h_location = ? 
       AND invoice_h_delivery_date BETWEEN ? AND ?
     GROUP BY invoice_h_delivery_date, invoice_h_status",
    [$_SESSION['location'], $startDate, $endDate]
);
$statusCounts = [];
if ($statusRows) {
    foreach ($statusRows as $row) {
        $statusCounts[$row['d']][$row['st']] = (int)$row['cnt'];
    }
}

// Amended orders: any invoice with at least one cart item
$amendedCounts = [];
$amendedRows = $db->getRows(
    "SELECT h.invoice_h_delivery_date AS d, COUNT(DISTINCT h.invoice_h_id) AS cnt
     FROM invoice_hedder h
     INNER JOIN invoice_details id ON id.invoice_h_id = h.invoice_h_id
     WHERE h.invoice_h_location = ?
       AND h.invoice_h_delivery_date BETWEEN ? AND ?
       AND id.is_cart_item = 1
     GROUP BY h.invoice_h_delivery_date",
    [$_SESSION['location'], $startDate, $endDate]
);
if ($amendedRows) {
    foreach ($amendedRows as $row) {
        $amendedCounts[$row['d']] = (int)$row['cnt'];
    }
}

// Calendar calculations
$daysInMonth  = (int)date('t', strtotime($startDate));
$firstDayOfWeek = (int)date('w', strtotime($startDate)); // 0=Sun, 6=Sat
$monthName = date('F', strtotime($startDate));

// Navigation
$prevMonth = $month - 1;
$prevYear  = $year;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
$nextMonth = $month + 1;
$nextYear  = $year;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

$today = date('Y-m-d');
?>
<!DOCTYPE html>
<!--[if IE 8]> <html lang="en" class="ie8 no-js"> <![endif]-->
<!--[if IE 9]> <html lang="en" class="ie9 no-js"> <![endif]-->
<!--[if !IE]><!-->
<html lang="en">
<!--<![endif]-->
<head>
    <meta charset="utf-8" />
    <title>Order Calendar | STOCK MANAGEMENT SYSTEM</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
    <style>
        .calendar-wrapper {
            background: #fff;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12);
            overflow: hidden;
        }
        .calendar-header {
            background: #36c6d3;
            color: #fff;
            padding: 20px 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .calendar-header h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .calendar-header .nav-btn {
            background: rgba(255,255,255,0.2);
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 8px 16px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .calendar-header .nav-btn:hover {
            background: rgba(255,255,255,0.35);
        }
        .calendar-header .today-btn {
            background: rgba(255,255,255,0.25);
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 8px 20px;
            font-size: 14px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.2s;
        }
        .calendar-header .today-btn:hover {
            background: rgba(255,255,255,0.4);
        }
        .calendar-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .calendar-table thead th {
            background: #f7f8fa;
            color: #666;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            padding: 12px 8px;
            text-align: center;
            border-bottom: 2px solid #e7ecf1;
        }
        .calendar-table thead th:first-child { color: #e7505a; }
        .calendar-table thead th:last-child  { color: #3598dc; }
        .calendar-table td {
            border: 1px solid #e7ecf1;
            vertical-align: top;
            height: 110px;
            padding: 6px 8px;
            position: relative;
            transition: background 0.15s;
        }
        .calendar-table td:hover {
            background: #f5f9fc;
        }
        .calendar-table td.empty {
            background: #fafbfc;
        }
        .calendar-table td.empty:hover {
            background: #fafbfc;
        }
        .calendar-table td.today {
            background: #eef7ff;
            border-color: #e7505a; /* make today's cell border red */
        }
        .day-number {
            font-size: 14px;
            font-weight: 600;
            color: #555;
            display: inline-block;
            margin-bottom: 4px;
        }
        /* Days with orders: no background; use black text per request */
        .has-orders .day-number {
            color: #000;
            width: 28px;
            height: 28px;
            line-height: 28px;
            text-align: center;
            border-radius: 50%;
            background: transparent;
        }
        /* Current date: red badge and highest priority */
        .today .day-number {
            background: #e7505a;
            color: #fff;
            width: 28px;
            height: 28px;
            line-height: 28px;
            text-align: center;
            border-radius: 50%;
        }
        /* Tighten status-dot spacing and make the label compact (e.g. 3A) */
        .status-dot { font-size: 11px; padding: 2px 6px; }
        .day-cell-link {
            display: block;
            width: 100%;
            height: 100%;
            text-decoration: none;
            color: inherit;
            cursor: pointer;
        }
        .day-cell-link:hover, .day-cell-link:focus {
            text-decoration: none;
            color: inherit;
        }
        .order-badge {
            display: inline-block;
            background: #36c6d3;
            color: #fff;
            font-size: 18px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 4px;
            margin-top: 4px;
            line-height: 1.3;
        }
        .order-badge.no-orders {
            background: #eee;
            color: #aaa;
            font-size: 14px;
            font-weight: 400;
        }
        .order-label {
            display: block;
            font-size: 11px;
            color: #888;
            margin-top: 2px;
        }
        .status-dots {
            margin-top: 6px;
        }
        .status-dot {
            display: inline-block;
            font-size: 11px;
            padding: 1px 6px;
            border-radius: 3px;
            margin-right: 3px;
            margin-bottom: 2px;
            font-weight: 600;
        }
        .status-dot.pending   { background: #f0e68c; color: #8a6d3b; }
        .status-dot.accepted  { background: #dff0d8; color: #3c763d; }
        .status-dot.cancelled { background: #f2dede; color: #a94442; }

        .calendar-legend {
            padding: 12px 20px;
            border-top: 1px solid #e7ecf1;
            background: #fafbfc;
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }
        .calendar-legend .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #666;
        }
        .legend-color {
            width: 14px;
            height: 14px;
            border-radius: 3px;
            display: inline-block;
        }
        .legend-color.pending   { background: #f0e68c; }
        .legend-color.accepted  { background: #dff0d8; }
        .legend-color.cancelled { background: #f2dede; }
        .legend-color.total     { background: #36c6d3; }

        /* Month summary cards */
        .summary-cards {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .summary-card {
            flex: 1;
            min-width: 150px;
            background: #fff;
            border-radius: 4px;
            padding: 18px 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            text-align: center;
            border-top: 3px solid #36c6d3;
        }
        .summary-card.pending   { border-top-color: #f0ad4e; }
        .summary-card.accepted  { border-top-color: #26c281; }
        .summary-card.cancelled { border-top-color: #e7505a; }
        .summary-card .card-value {
            font-size: 28px;
            font-weight: 700;
            color: #333;
        }
        .summary-card .card-label {
            font-size: 13px;
            color: #888;
            margin-top: 4px;
        }

        @media (max-width: 768px) {
            .calendar-table td {
                height: 80px;
                padding: 4px;
            }
            .order-badge { font-size: 14px; padding: 2px 8px; }
            .status-dots { display: none; }
            .summary-cards { flex-direction: column; }
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
                <!-- Breadcrumb -->
                <div class="page-bar">
                    <ul class="page-breadcrumb">
                        <li><a href="index.php">Home</a><i class="fa fa-circle"></i></li>
                        <li><a href="manage-orders.php">Orders</a><i class="fa fa-circle"></i></li>
                        <li><a>Order Calendar</a></li>
                    </ul>
                </div>

                <h3 class="page-title">Order Calendar</h3>

                <?php
                // Summary calculations
                $totalOrders = array_sum($orderCounts);
                $totalPending = 0;
                $totalCancelled = 0;
                $totalAmended = array_sum($amendedCounts);
                foreach ($statusCounts as $date => $statuses) {
                    foreach ($statuses as $st => $cnt) {
                        if ($st == 0)  $totalPending   += $cnt;
                        if ($st == -1) $totalCancelled  += $cnt;
                    }
                }
                ?>

                <!-- Summary Cards -->
                <div class="summary-cards">
                    <div class="summary-card">
                        <div class="card-value"><?php echo $totalOrders; ?></div>
                        <div class="card-label">Total Orders</div>
                    </div>
                    <div class="summary-card pending">
                        <div class="card-value"><?php echo $totalPending; ?></div>
                        <div class="card-label">Pending</div>
                    </div>
                    <div class="summary-card accepted">
                        <div class="card-value"><?php echo $totalAmended; ?></div>
                        <div class="card-label">Amended</div>
                    </div>
                    <div class="summary-card cancelled">
                        <div class="card-value"><?php echo $totalCancelled; ?></div>
                        <div class="card-label">Cancelled</div>
                    </div>
                </div>

                <!-- Calendar -->
                <div class="calendar-wrapper">
                    <div class="calendar-header">
                        <a href="order-calendar.php?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" class="nav-btn">
                            <i class="fa fa-chevron-left"></i>
                        </a>
                        <div style="text-align:center;">
                            <h2><?php echo $monthName . ' ' . $year; ?></h2>
                        </div>
                        <div>
                            <a href="order-calendar.php" class="today-btn" style="margin-right:8px;">Today</a>
                            <a href="order-calendar.php?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" class="nav-btn">
                                <i class="fa fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>

                    <table class="calendar-table">
                        <thead>
                            <tr>
                                <th>Sun</th>
                                <th>Mon</th>
                                <th>Tue</th>
                                <th>Wed</th>
                                <th>Thu</th>
                                <th>Fri</th>
                                <th>Sat</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $day = 1;
                        $started = false;

                        // Max 6 rows possible
                        for ($row = 0; $row < 6; $row++) {
                            if ($day > $daysInMonth) break;
                            echo '<tr>';
                            for ($col = 0; $col < 7; $col++) {
                                if (!$started && $col < $firstDayOfWeek) {
                                    echo '<td class="empty"></td>';
                                    continue;
                                }
                                $started = true;
                                if ($day > $daysInMonth) {
                                    echo '<td class="empty"></td>';
                                    continue;
                                }

                                $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
                                $isToday = ($dateStr === $today);
                                $count = isset($orderCounts[$dateStr]) ? $orderCounts[$dateStr] : 0;
                                $pending   = isset($statusCounts[$dateStr][0])  ? $statusCounts[$dateStr][0]  : 0;
                                $amended   = isset($amendedCounts[$dateStr])    ? $amendedCounts[$dateStr]    : 0;
                                $cancelled = isset($statusCounts[$dateStr][-1]) ? $statusCounts[$dateStr][-1] : 0;

                                $tdClass = trim(($isToday ? 'today' : '') . ($count > 0 ? ' has-orders' : ''));

                                // Link to pending-orders with this date
                                $linkUrl = 'pending-orders.php?date_from=' . $dateStr . '&date_to=' . $dateStr;

                                echo '<td class="' . $tdClass . '">';
                                echo '<a href="' . $linkUrl . '" class="day-cell-link" title="View orders for ' . date('M j, Y', strtotime($dateStr)) . '">';
                                echo '<span class="day-number">' . $day . '</span>';

                                if ($count > 0) {
                                    echo '<br><span class="order-badge">' . $count . '</span>';
                                    echo '<span class="order-label">order' . ($count != 1 ? 's' : '') . '</span>';
                                    echo '<div class="status-dots">';
                                    if ($pending > 0)   echo '<span class="status-dot pending">' . $pending . 'WA</span>';
                                    if ($amended > 0)   echo '<span class="status-dot accepted">' . $amended . 'A</span>';
                                    if ($cancelled > 0) echo '<span class="status-dot cancelled">' . $cancelled . 'C</span>';
                                    echo '</div>';
                                }

                                echo '</a>';
                                echo '</td>';
                                $day++;
                            }
                            echo '</tr>';
                        }
                        ?>
                        </tbody>
                    </table>

                    <div class="calendar-legend">
                        <div class="legend-item"><span class="legend-color total"></span> Total Orders</div>
                        <div class="legend-item"><span class="legend-color pending"></span> Pending</div>
                        <div class="legend-item"><span class="legend-color accepted"></span> Amended</div>
                        <div class="legend-item"><span class="legend-color cancelled"></span> Cancelled</div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php include('common/footer.php'); ?>

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
    <script src="assets/layouts/global/scripts/quick-sidebar.min.js" type="text/javascript"></script>
</body>
</html>
