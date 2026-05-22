<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('include/database.php');
include('include/check_login.php');

$db = new Database();

$selected_date  = isset($_GET['date'])     ? $_GET['date']         : date('Y-m-d');
$selected_route = isset($_GET['route_id']) ? (int)$_GET['route_id'] : 0;

// Routes for filter dropdown
$routes = $db->getRows(
    'SELECT id, route_name FROM delivery_route_master WHERE is_active = 1 ORDER BY route_name ASC'
);

// Fetch one row per invoice; deduplicate by customer+route in PHP
$params      = [$selected_date];
$routeWhere  = '';
if ($selected_route > 0) {
    $routeWhere = ' AND COALESCE(csa_inv.delivery_route_id, csa_def.delivery_route_id) = ?';
    $params[]   = $selected_route;
}

$raw = $db->getRows(
    "SELECT
        ih.invoice_h_id,
        COALESCE(drm.route_name, 'No Route') AS route_name,
        COALESCE(drm.id, 0)                  AS route_id,
        c.customer_id,
        c.customer_name,
        COALESCE(csa_inv.address_line_1, csa_def.address_line_1, ih.invoice_h_delivery_address, '') AS address_line_1,
        COALESCE(csa_inv.address_line_2, csa_def.address_line_2, '') AS address_line_2,
        COALESCE(csa_inv.city,           csa_def.city,           '') AS city,
        COALESCE(csa_inv.postal_code,    csa_def.postal_code,    '') AS postal_code,
        COALESCE(csa_inv.remarks,        csa_def.remarks,        '') AS note,
        COALESCE(csa_inv.note_to_deliver,csa_def.note_to_deliver,'') AS driver_note
     FROM invoice_hedder ih
     JOIN customer c
          ON c.customer_id = ih.invoice_h_customer_id
     LEFT JOIN customer_shipping_address csa_inv
          ON csa_inv.id = ih.shipping_address_id
     LEFT JOIN customer_shipping_address csa_def
          ON csa_def.customer_id = c.customer_id AND csa_def.is_default = 1
     LEFT JOIN delivery_route_master drm
          ON drm.id = COALESCE(csa_inv.delivery_route_id, csa_def.delivery_route_id)
     WHERE ih.invoice_h_delivery_date = ?
       AND ih.invoice_h_status = 1
       $routeWhere
     ORDER BY route_name ASC, c.customer_name ASC",
    $params
);

// Deduplicate: one row per (route_id, customer_id)
$rows = [];
$seen = [];
foreach ($raw as $row) {
    $key = $row['route_id'] . '_' . $row['customer_id'];
    if (isset($seen[$key])) {
        continue;
    }
    $seen[$key] = true;
    $addressParts = array_filter([
        $row['address_line_1'],
        $row['address_line_2'],
        $row['city'],
    ]);
    $rows[] = [
        'route_name'  => $row['route_name'],
        'route_id'    => (int)$row['route_id'],
        'customer_id' => $row['customer_id'],
        'company'     => $row['customer_name'],
        'postcode'    => $row['postal_code'],
        'address'     => implode(', ', $addressParts),
        'note'        => $row['note'],
        'driver_note' => $row['driver_note'],
    ];
}

// Group by route
$byRoute = [];
foreach ($rows as $row) {
    $byRoute[$row['route_name']][] = $row;
}

$formattedDate = date('d/m/Y', strtotime($selected_date));
$printedAt     = date('d/m/Y \a\t H:i:s');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Driver Report for: <?php echo htmlspecialchars($formattedDate); ?></title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>

    <style>
        /* ── Screen controls ────────────────────────────────────────────── */
        .driver-report-wrap {
            font-family: Arial, sans-serif;
            font-size: 13px;
        }

        .report-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 5px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .report-controls h1 {
            font-size: 18px;
            font-weight: bold;
            margin: 0;
            white-space: nowrap;
        }

        /* ── Route section ──────────────────────────────────────────────── */
        .route-section {
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-bottom: 30px;
            padding: 20px 24px 16px;
            background: #fff;
            page-break-inside: avoid;
        }

        .route-section + .route-section {
            page-break-before: always;
        }

        .route-header-title {
            font-size: 16px;
            font-weight: bold;
            margin: 0 0 2px;
        }

        .route-header-meta {
            font-size: 12px;
            color: #444;
            margin-bottom: 4px;
        }

        .route-header-total {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 16px;
        }

        /* ── Signature block ────────────────────────────────────────────── */
        .sig-block {
            
            width: 100%;
            max-width: 560px;
        }

        .sig-row {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            display: flex;
            align-items: flex-end;
            gap: 12px;
            flex-wrap: wrap;
        }

        .sig-row--two-cols {
            justify-content: space-between;
        }

        .sig-item {
            display: flex;
            align-items: flex-end;
            gap: 8px;
            width: 48%;
            min-width: 200px;
        }

        .sig-label {
            min-width: 70px;
        }

        .sig-line {
            flex: 1;
            border-bottom: 1.5px solid #333;
            margin-bottom: 2px;
            min-width: 120px;
        }

        .sig-line--full {
            min-width: 320px;
        }

        .sig-sign {
            font-size: 14px;
            font-weight: bold;
            margin-top: 6px;
            margin-bottom: 6px;
        }

        .sig-sign .sig-box {
            display: inline-block;
            width: 260px;
            height: 50px;
            border: 1.5px solid #333;
            vertical-align: middle;
            margin-left: 6px;
        }

        /* ── Table ──────────────────────────────────────────────────────── */
        .driver-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            border: 2px solid #333;
        }

        .driver-table th {
            text-align: left;
            font-size: 12px;
            font-weight: bold;
            padding: 6px 8px;
            border: 1px solid #999;
            border-bottom: 2px solid #333;
            background-color: #f0f4fa;
            color: #1a6bc4;
        }

        .driver-table td {
            font-size: 12px;
            padding: 7px 8px;
            border: 1px solid #ccc;
            vertical-align: top;
        }

        .driver-table tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .driver-table tbody tr:hover {
            background: #f0f4fa;
        }

        .driver-table .col-company  { width: 20%; }
        .driver-table .col-postcode { width: 8%; }
        .driver-table .col-address  { width: 26%; }
        .driver-table .col-note     { width: 18%; }
        .driver-table .col-dnote    { width: 18%; }
        .driver-table .col-boxes    { width: 10%; }

        .boxes-cell {
            text-align: center;
            white-space: nowrap;
        }

        .boxes-input {
            width: 64px;
            min-width: 64px;
            height: 28px;
            margin: 0 auto;
            padding: 3px 6px;
            text-align: center;
        }

        .boxes-print-value {
            display: none;
            min-width: 32px;
            font-weight: bold;
            text-align: center;
        }

        /* ── Print ──────────────────────────────────────────────────────── */
        @media print {
            body { margin: 0; padding: 0; }

            .no-print,
            .report-controls,
            .page-sidebar-wrapper,
            .page-bar,
            .page-header,
            .page-footer {
                display: none !important;
            }

            .page-content,
            .page-container,
            .page-content-wrapper {
                margin: 0 !important;
                padding: 0 !important;
            }

            .route-section {
                border: none;
                padding: 0;
                margin: 0;
            }

            .route-section + .route-section {
                page-break-before: always;
            }

            .route-section {
                display: flex;
                flex-direction: column;
                min-height: 273mm;
            }

            .sig-footer {
                margin-top: auto;
            }

            .driver-table {
                border: 2px solid #000;
            }

            .driver-table th {
                color: #000;
                border: 1px solid #666 !important;
                border-bottom: 2px solid #000 !important;
                background-color: #e8e8e8 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .driver-table td {
                border: 1px solid #999 !important;
            }

            .driver-table tbody tr:nth-child(even) {
                background: transparent;
            }

            .driver-table tbody tr:hover {
                background: transparent;
            }

            .boxes-input {
                display: none !important;
            }

            .boxes-print-value {
                display: inline-block;
            }
        }

        @page {
            size: A4 portrait;
            margin: 12mm;
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
                <div class="page-bar no-print">
                    <ul class="page-breadcrumb">
                        <li><a href="index.php">Home</a><i class="fa fa-circle"></i></li>
                        <li><a href="#">Reports</a><i class="fa fa-circle"></i></li>
                        <li><span>Driver Report</span></li>
                    </ul>
                </div>

                <div class="driver-report-wrap">

                    <!-- Controls -->
                    <div class="report-controls no-print">
                        <h1>Driver Report for:</h1>
                        <form method="get" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                            <input type="date" name="date" class="form-control"
                                   value="<?php echo htmlspecialchars($selected_date); ?>" />
                            <select name="route_id" class="form-control" style="width:auto;">
                                <option value="0">All Routes</option>
                                <?php foreach ($routes as $r): ?>
                                    <option value="<?php echo (int)$r['id']; ?>"
                                        <?php echo $selected_route === (int)$r['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($r['route_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-refresh"></i> Refresh
                            </button>
                            <button type="button" class="btn btn-default" onclick="window.print();">
                                <i class="fa fa-print"></i> Print
                            </button>
                        </form>
                    </div>

                    <?php if (empty($byRoute)): ?>
                        <div class="alert alert-info no-print">No orders found for the selected date / route.</div>
                    <?php else: ?>
                        <?php foreach ($byRoute as $routeName => $customers): ?>
                            <?php $total = count($customers); ?>
                            <div class="route-section">

                                <!-- Route header -->
                                <p class="route-header-title">
                                    <?php echo htmlspecialchars($routeName); ?>,
                                    <?php echo htmlspecialchars($formattedDate); ?>
                                </p>
                                <p class="route-header-meta">Printed on <?php echo $printedAt; ?></p>
                                <p class="route-header-total">Total: <?php echo $total; ?></p>

                                <hr style="border:none;border-top:1px solid #ccc;margin:12px 0;">

                                <!-- Customers table -->
                                <table class="driver-table">
                                    <thead>
                                        <tr>
                                            <th class="col-company">Company</th>
                                            <th class="col-postcode">Postcode</th>
                                            <th class="col-address">Address</th>
                                            <th class="col-note">Delivery Note</th>
                                            <th class="col-dnote">Driver Note</th>
                                            <th class="col-boxes">No of Boxes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($customers as $c): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($c['company']); ?></td>
                                                <td><?php echo htmlspecialchars($c['postcode']); ?></td>
                                                <td><?php echo nl2br(htmlspecialchars($c['address'])); ?></td>
                                                <td><?php echo nl2br(htmlspecialchars($c['note'])); ?></td>
                                                <td><?php echo nl2br(htmlspecialchars($c['driver_note'])); ?></td>
                                                <td class="boxes-cell">
                                                    <input
                                                        type="text"
                                                        class="form-control boxes-input js-boxes-input"
                                                        inputmode="numeric"
                                                        maxlength="4"
                                                        aria-label="No of Boxes for <?php echo htmlspecialchars($c['company']); ?>" />
                                                    <span class="boxes-print-value"></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>

                                <hr style="border:none;border-top:1px solid #ccc;margin:12px 0;">

                                <div class="sig-footer">
                                <div class="sig-block">
                                    <div class="sig-row sig-row--two-cols">
                                        <div class="sig-item">
                                            <span class="sig-label">Driver:</span>
                                            <span class="sig-line"></span>
                                        </div>
                                        <div class="sig-item">
                                            <span class="sig-label">Van Reg:</span>
                                            <span class="sig-line"></span>
                                        </div>
                                    </div>
                                    <div class="sig-row sig-row--two-cols">
                                        <div class="sig-item">
                                            <span class="sig-label">Time Out:</span>
                                            <span class="sig-line"></span>
                                        </div>
                                        <div class="sig-item">
                                            <span class="sig-label">Time In:</span>
                                            <span class="sig-line"></span>
                                        </div>
                                    </div>
                                    <div class="sig-row">
                                        <span class="sig-label">Report:</span>
                                        <span class="sig-line sig-line--full"></span>
                                    </div>
                                    <div class="sig-row">
                                        <span class="sig-label">Sign:</span>
                                        <span class="sig-line sig-line--full"></span>
                                    </div>
                                </div>
                                </div><!-- /.sig-footer -->

                            </div><!-- /.route-section -->
                        <?php endforeach; ?>
                    <?php endif; ?>

                </div><!-- /.driver-report-wrap -->
            </div><!-- /.page-content -->
        </div><!-- /.page-content-wrapper -->
    </div><!-- /.page-container -->

    <?php include('common/footer.php'); ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var inputs = document.querySelectorAll('.js-boxes-input');

            Array.prototype.forEach.call(inputs, function (input) {
                var printValue = input.parentNode.querySelector('.boxes-print-value');

                var syncValue = function () {
                    input.value = input.value.replace(/[^0-9]/g, '');
                    printValue.textContent = input.value;
                };

                input.addEventListener('input', syncValue);
                syncValue();
            });
        });
    </script>
</body>
</html>
