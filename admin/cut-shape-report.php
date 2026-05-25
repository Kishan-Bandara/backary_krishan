<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('include/database.php');
include('include/check_login.php');

$db = new Database();

// Get selected date (default to today)
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$selected_business_unit = isset($_GET['business_unit']) ? trim($_GET['business_unit']) : '';
$formattedDate = date('d/m/Y', strtotime($selected_date));

// Load business units for the filter dropdown
$businessUnits = $db->getRows('SELECT business_unit_id, business_unit_name FROM business_unit_master ORDER BY business_unit_name ASC');
$businessUnitOptions = '<option value="">All Business Units</option>';
foreach ($businessUnits as $unit) {
    $unitId = (string)($unit['business_unit_id'] ?? '');
    $selected = ($selected_business_unit === $unitId) ? ' selected' : '';
    $unitName = htmlspecialchars($unit['business_unit_name'] ?? '', ENT_QUOTES, 'UTF-8');
    $businessUnitOptions .= "<option value=\"{$unitId}\"{$selected}>{$unitName}</option>";
}

$businessUnitFilter = '';
$businessUnitParams = [];
if ($selected_business_unit !== '' && ctype_digit($selected_business_unit)) {
    $businessUnitFilter = ' AND im.item_business_unit = ? ';
    $businessUnitParams[] = (int)$selected_business_unit;
}

// Determine day-of-week column for standing order lookup
$dayIndex = (int)date('w', strtotime($selected_date)); // 0=Sun, 1=Mon ...
$dayNames = [0 => 'sun', 1 => 'mon', 2 => 'tue', 3 => 'wed', 4 => 'thu', 5 => 'fri', 6 => 'sat'];
$dayColumn = $dayNames[$dayIndex] . '_qty';

// AJAX: item-wise customer quantity drill-down
if (isset($_GET['ajax']) && $_GET['ajax'] === 'item_customer_qty') {
    header('Content-Type: application/json; charset=utf-8');

    $itemId = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;
    if ($itemId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid item selected.']);
        exit;
    }

    $standingCustomerRows = $db->getRows(
        "SELECT
            c.customer_id,
            c.customer_name,
            SUM(soi.{$dayColumn}) AS standing_qty
         FROM standing_order_item soi
         JOIN standing_order so ON so.id = soi.standing_order_id
         JOIN customer c ON c.customer_id = so.customer_id
         JOIN item_master im ON im.item_id = soi.item_id
         WHERE soi.item_id = ?
           AND so.active = 1
           AND (so.date_from IS NULL OR so.date_from <= ?)
           AND (so.date_to IS NULL OR so.date_to >= ?)
           AND soi.{$dayColumn} > 0
           {$businessUnitFilter}
         GROUP BY c.customer_id, c.customer_name
         ORDER BY c.customer_name ASC",
        array_merge([$itemId, $selected_date, $selected_date], $businessUnitParams)
    );

    $cartCustomerRows = $db->getRows(
        "SELECT
            c.customer_id,
            c.customer_name,
            GROUP_CONCAT(DISTINCT COALESCE(NULLIF(ih.invoice_h_code, ''), ih.invoice_h_id) ORDER BY ih.invoice_h_id DESC SEPARATOR ', ') AS order_nos,
            SUM(id.invoice_d_qty) AS cart_qty
         FROM invoice_details id
         JOIN invoice_hedder ih ON ih.invoice_h_id = id.invoice_h_id
         JOIN customer c ON c.customer_id = ih.invoice_h_customer_id
         JOIN item_master im ON im.item_id = id.invoice_d_item_id
         WHERE id.invoice_d_item_id = ?
           AND ih.invoice_h_delivery_date = ?
           AND ih.invoice_h_status = 1
           AND ih.order_type = 'CART'
           {$businessUnitFilter}
         GROUP BY c.customer_id, c.customer_name
         ORDER BY c.customer_name ASC",
        array_merge([$itemId, $selected_date], $businessUnitParams)
    );

    $customerMap = [];

    foreach ($standingCustomerRows as $row) {
        $customerId = (int)$row['customer_id'];
        if (!isset($customerMap[$customerId])) {
            $customerMap[$customerId] = [
                'customer_id' => $customerId,
                'customer_name' => $row['customer_name'] ?: 'Unknown Customer',
                'order_nos' => '',
                'standing_qty' => 0,
                'cart_qty' => 0,
                'total_qty' => 0,
            ];
        }
        $customerMap[$customerId]['standing_qty'] = (float)$row['standing_qty'];
    }

    foreach ($cartCustomerRows as $row) {
        $customerId = (int)$row['customer_id'];
        if (!isset($customerMap[$customerId])) {
            $customerMap[$customerId] = [
                'customer_id' => $customerId,
                'customer_name' => $row['customer_name'] ?: 'Unknown Customer',
                'order_nos' => '',
                'standing_qty' => 0,
                'cart_qty' => 0,
                'total_qty' => 0,
            ];
        }
        $customerMap[$customerId]['order_nos'] = trim((string)($row['order_nos'] ?? ''));
        $customerMap[$customerId]['cart_qty'] = (float)$row['cart_qty'];
    }

    foreach ($customerMap as $customerId => $customerData) {
        $customerMap[$customerId]['total_qty'] = $customerData['standing_qty'] + $customerData['cart_qty'];
    }

    usort($customerMap, function ($a, $b) {
        return strcasecmp($a['customer_name'], $b['customer_name']);
    });

    echo json_encode([
        'success' => true,
        'rows' => array_values($customerMap),
    ]);
    exit;
}

// ──────────────────────────────────────────────
// 1. Standing order quantities for the selected date
//    (Original / amended qty – baseline planned quantity)
// ──────────────────────────────────────────────
$standingRows = $db->getRows(
    "SELECT
        im.item_id,
        im.item_name,
        im.item_type,
        tm.type_name,
        COALESCE(im.item_weight_g, 0) AS item_weight_g,
        SUM(soi.{$dayColumn}) AS standing_qty
     FROM standing_order_item soi
     JOIN standing_order so ON so.id = soi.standing_order_id
     JOIN item_master im ON im.item_id = soi.item_id
     LEFT JOIN type_master tm ON tm.type_id = im.item_type
     WHERE so.active = 1
       AND (so.date_from IS NULL OR so.date_from <= ?)
       AND (so.date_to IS NULL OR so.date_to >= ?)
       AND soi.{$dayColumn} > 0
       {$businessUnitFilter}
     GROUP BY im.item_id
     ORDER BY tm.type_name ASC, im.item_name ASC",
    array_merge([$selected_date, $selected_date], $businessUnitParams)
);

// Build standing data keyed by item_id
$standingByItem = [];
foreach ($standingRows as $row) {
    $standingByItem[(int)$row['item_id']] = [
        'item_name'    => $row['item_name'],
        'type_id'      => (int)$row['item_type'],
        'type_name'    => $row['type_name'] ?: 'Uncategorized',
        'item_weight_g'=> (float)$row['item_weight_g'],
        'standing_qty' => (float)$row['standing_qty'],
    ];
}

// ──────────────────────────────────────────────
// 2. Cart order quantities (late orders) for the selected date
// ──────────────────────────────────────────────
$cartRows = $db->getRows(
    "SELECT
        id.invoice_d_item_id AS item_id,
        im.item_name,
        im.item_type,
        tm.type_name,
        COALESCE(im.item_weight_g, 0) AS item_weight_g,
        SUM(id.invoice_d_qty) AS cart_qty
     FROM invoice_details id
     JOIN invoice_hedder ih ON ih.invoice_h_id = id.invoice_h_id
     JOIN item_master im ON im.item_id = id.invoice_d_item_id
     LEFT JOIN type_master tm ON tm.type_id = im.item_type
     WHERE ih.invoice_h_delivery_date = ?
       AND ih.invoice_h_status = 1
       AND ih.order_type = 'CART'
       {$businessUnitFilter}
     GROUP BY id.invoice_d_item_id
     ORDER BY tm.type_name ASC, im.item_name ASC",
    array_merge([$selected_date], $businessUnitParams)
);

$cartByItem = [];
foreach ($cartRows as $row) {
    $cartByItem[(int)$row['item_id']] = [
        'item_name'    => $row['item_name'],
        'type_id'      => (int)$row['item_type'],
        'type_name'    => $row['type_name'] ?: 'Uncategorized',
        'item_weight_g'=> (float)$row['item_weight_g'],
        'cart_qty'     => (float)$row['cart_qty'],
    ];
}

// ──────────────────────────────────────────────
// 3. Merge into product types → items structure
// ──────────────────────────────────────────────
$allItemIds = array_unique(array_merge(array_keys($standingByItem), array_keys($cartByItem)));
$groups = []; // type_name => [ 'items' => [...], 'total_weight_g' => ..., 'original_total_qty' => ..., 'total_qty' => ..., 'late_total_qty' => ... ]

foreach ($allItemIds as $itemId) {
    $standing  = $standingByItem[$itemId] ?? null;
    $cart      = $cartByItem[$itemId] ?? null;

    $groupName   = $standing['type_name'] ?? $cart['type_name'] ?? 'Uncategorized';
    $itemName    = $standing['item_name'] ?? $cart['item_name'] ?? '';
    $weightG     = $standing['item_weight_g'] ?? $cart['item_weight_g'] ?? 0;
    $standingQty = $standing['standing_qty'] ?? 0;
    $cartQty     = $cart['cart_qty'] ?? 0;
    $totalQty    = $standingQty + $cartQty;

    if (!isset($groups[$groupName])) {
        $groups[$groupName] = [
            'items'              => [],
            'total_weight_g'     => 0,
            'original_total_qty' => 0,
            'total_qty'          => 0,
            'late_total_qty'     => 0,
        ];
    }

    $groups[$groupName]['items'][] = [
        'item_id'      => $itemId,
        'item_name'    => $itemName,
        'standing_qty' => $standingQty,
        'cart_qty'     => $cartQty,
        'total_qty'    => $totalQty,
        'weight_g'     => $weightG,
    ];

    $groups[$groupName]['total_weight_g']     += $weightG * $totalQty;
    $groups[$groupName]['original_total_qty'] += $standingQty;
    $groups[$groupName]['total_qty']          += $totalQty;
    $groups[$groupName]['late_total_qty']     += $cartQty;
}

// Sort product types by name
ksort($groups);

function loadPhpSpreadsheetForReportExport()
{
    static $loaded = false;

    if ($loaded) {
        return true;
    }

    $autoloadPaths = [
        __DIR__ . '/vendor/autoload.php',
        __DIR__ . '/DB Migration/vendor/autoload.php',
    ];

    foreach ($autoloadPaths as $autoloadPath) {
        if (file_exists($autoloadPath)) {
            require_once $autoloadPath;
            $loaded = true;

            return true;
        }
    }

    return false;
}

function exportCutShapeReportXlsx($groups, $selectedDate, $formattedDate)
{
    if (!loadPhpSpreadsheetForReportExport()) {
        throw new RuntimeException('PhpSpreadsheet library not found for Excel export.');
    }

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Cut & Shape Report');

    $sheet->mergeCells('A1:F1');
    $sheet->setCellValue('A1', 'Cut & Shape Report');
    $sheet->mergeCells('A2:F2');
    $sheet->setCellValue('A2', 'Delivery Date: ' . $formattedDate);

    $sheet->getStyle('A1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 16],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
    ]);
    $sheet->getStyle('A2')->applyFromArray([
        'font' => ['bold' => true, 'size' => 11],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
    ]);

    $headerRow = 4;
    $headers = [
        'A' => 'Product Type',
        'B' => 'Total Weight',
        'C' => 'Item Name',
        'D' => 'Original / Amended Qty',
        'E' => 'Late Order Qty',
        'F' => 'Total',
    ];

    foreach ($headers as $column => $label) {
        $sheet->setCellValue($column . $headerRow, $label);
    }

    $sheet->getStyle('A4:F4')->applyFromArray([
        'font' => ['bold' => true, 'size' => 11],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'F0F0F0'],
        ],
        'borders' => [
            'bottom' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                'color' => ['rgb' => '999999'],
            ],
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => 'D9D9D9'],
            ],
        ],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
        ],
    ]);

    $currentRow = 5;

    foreach ($groups as $groupName => $group) {
        $totalWeightKg = $group['total_weight_g'] > 0 ? round($group['total_weight_g'] / 1000, 2) : 0;
        $weightLabel = $totalWeightKg . 'kg';
        $itemCount = count($group['items']);
        $groupStartRow = $currentRow;
        $groupEndRow = $groupStartRow + $itemCount;

        $sheet->mergeCells('A' . $groupStartRow . ':A' . $groupEndRow);
        $sheet->mergeCells('B' . $groupStartRow . ':B' . $groupEndRow);
        $sheet->setCellValue('A' . $groupStartRow, $groupName);
        $sheet->setCellValue('B' . $groupStartRow, $weightLabel);
        $sheet->setCellValue('D' . $groupStartRow, $group['original_total_qty']);
        $sheet->setCellValue('E' . $groupStartRow, $group['late_total_qty']);
        $sheet->setCellValue('F' . $groupStartRow, $group['total_qty']);

        $sheet->getStyle('A' . $groupStartRow . ':F' . $groupStartRow)->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D6E4D2'],
            ],
            'font' => ['bold' => true],
        ]);

        $sheet->getStyle('A' . $groupStartRow . ':B' . $groupEndRow)->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D6E4D2'],
            ],
            'font' => ['bold' => true],
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP,
            ],
        ]);

        $sheet->getStyle('B' . $groupStartRow)->getFont()->setBold(false);
        $sheet->getStyle('D' . $groupStartRow . ':F' . $groupStartRow)->getFont()->setBold(true);

        foreach ($group['items'] as $itemIndex => $item) {
            $row = $groupStartRow + 1 + $itemIndex;

            $sheet->setCellValue('C' . $row, $item['item_name']);
            $sheet->setCellValue('D' . $row, $item['standing_qty']);
            $sheet->setCellValue('E' . $row, $item['cart_qty']);
            $sheet->setCellValue('F' . $row, $item['total_qty']);

            if ($item['cart_qty'] > 0) {
                $sheet->getStyle('D' . $row)->getFont()->setStrikethrough(true);
                $sheet->getStyle('E' . $row)->getFont()->getColor()->setRGB('721C24');
            }
        }

        $sheet->getStyle('A' . $groupStartRow . ':F' . $groupEndRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'DDDDDD'],
                ],
            ],
        ]);

        $sheet->getStyle('A' . $groupStartRow . ':B' . $groupEndRow)->applyFromArray([
            'borders' => [
                'bottom' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                    'color' => ['rgb' => 'AAC4A0'],
                ],
            ],
        ]);

        $sheet->getStyle('C' . $groupEndRow . ':F' . $groupEndRow)->applyFromArray([
            'borders' => [
                'bottom' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                    'color' => ['rgb' => 'AAC4A0'],
                ],
            ],
        ]);

        $currentRow = $groupEndRow + 1;
    }

    $sheet->getStyle('D5:F' . max(5, $currentRow - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    $sheet->getColumnDimension('A')->setWidth(20);
    $sheet->getColumnDimension('B')->setWidth(16);
    $sheet->getColumnDimension('C')->setWidth(36);
    $sheet->getColumnDimension('D')->setWidth(18);
    $sheet->getColumnDimension('E')->setWidth(18);
    $sheet->getColumnDimension('F')->setWidth(18);

    $filename = 'Cut_Shape_Report_' . $selectedDate . '.xlsx';

    if (ob_get_length()) {
        ob_end_clean();
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// ──────────────────────────────────────────────
// 4. Export
// ──────────────────────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'xlsx') {
    exportCutShapeReportXlsx($groups, $selected_date, $formattedDate);
}

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename = 'Cut_Shape_Report_' . $selected_date . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');

    $out = fopen('php://output', 'w');
    // Title
    fputcsv($out, ['Cut & Shape Report', $formattedDate]);
    fputcsv($out, []);
    // Header row
    fputcsv($out, ['Product Type', 'Total Weight', 'Item Name', 'Original / Amended Qty', 'Late Order Qty', 'Total']);

    foreach ($groups as $groupName => $group) {
        $totalWeightKg = $group['total_weight_g'] > 0 ? round($group['total_weight_g'] / 1000, 2) : 0;
        $weightLabel = $totalWeightKg . 'kg';

        // Group summary row
        fputcsv($out, [
            $groupName,
            $weightLabel,
            '',
            $group['original_total_qty'],
            $group['late_total_qty'],
            $group['total_qty'],
        ]);

        foreach ($group['items'] as $item) {
            fputcsv($out, [
                '',
                '',
                $item['item_name'],
                $item['standing_qty'],
                $item['cart_qty'],
                $item['total_qty'],
            ]);
        }
    }

    fclose($out);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Cut &amp; Shape Report – <?php echo $formattedDate; ?></title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>

    <style>
        .cs-report { max-width: 1100px; margin: 0 auto; font-family: Arial, sans-serif; font-size: 13px; }
        .cs-report h1 { font-size: 22px; font-weight: bold; margin-bottom: 4px; }
        .cs-report h1 small { font-size: 14px; color: #666; font-weight: normal; margin-left: 10px; }

        .cs-table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        .cs-table th {
            text-align: left; font-weight: 700; padding: 8px 10px;
            border-bottom: 2px solid #999; font-size: 12px;
            text-transform: uppercase; letter-spacing: 0.03em;
            background: #f0f0f0; color: #333;
        }
        .cs-table td { padding: 6px 10px; border-bottom: 2px solid #d0d0d0; vertical-align: middle; }

        /* Product type header row */
        .cs-table .group-row td { background: #d6e4d2; font-weight: 700; border-bottom: 3px solid #aac4a0; }
        .cs-table td.group-label { vertical-align: top; border-bottom: 3px solid #aac4a0; }
        .cs-table .group-weight { font-weight: normal; color: #555; font-size: 12px; }

        /* Item rows */
        .cs-table .item-row td { background: #fff; }
        .cs-table .item-row:nth-child(even) td { background: #fafafa; }
        .cs-table .item-row.group-end td { border-bottom: 3px solid #aac4a0; }

        /* Qty columns alignment */
        .cs-table .col-qty { text-align: center; width: 11%; }
        .cs-table .col-name { width: 22%; }
        .cs-table .col-group { width: 12%; }
        .cs-table .col-weight { width: 22%; }

        /* Late qty highlight (reddish) */
        .cs-table td.late-highlight { color: #721c24; }
        .cs-table td.original-overridden { text-decoration: line-through; }
        /* Total highlight */
        .cs-table td.total-highlight { font-weight: 700; }
        /* Original total highlight */
        .cs-table td.orig-total { font-weight: 700; }
        .cs-table .item-link { color: #2f3b52; text-decoration: underline; cursor: pointer; }
        .cs-table .item-link:hover { color: #1d4f8f; }

        .no-print { margin-bottom: 16px; }

        @media print {
            .no-print, .page-sidebar-wrapper, .page-bar, .page-header, .page-footer { display: none !important; }
            .page-content { margin: 0 !important; padding: 0 !important; }
            .page-container, .page-content-wrapper { margin: 0 !important; padding: 0 !important; }
            .cs-report { max-width: 100%; padding: 5mm; }
            .cs-report h1 { font-size: 18px; }
            .cs-table th, .cs-table td { padding: 4px 6px; font-size: 11px; }
        }
        @page { size: A4 landscape; margin: 10mm; }
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
                        <li><span>Cut &amp; Shape Report</span></li>
                    </ul>
                </div>

                <!-- Controls -->
                <div class="row no-print">
                    <div class="col-md-12">
                        <div class="portlet light bordered">
                            <div class="portlet-title">
                                <div class="caption"><i class="fa fa-pie-chart"></i> Cut &amp; Shape Report</div>
                            </div>
                            <div class="portlet-body">
                                <form method="get" class="form-inline">
                                    <div class="form-group" style="margin-right:12px;">
                                        <label>Delivery Date: &nbsp;</label>
                                        <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($selected_date); ?>" />
                                    </div>
                                    <div class="form-group" style="margin-right:12px;">
                                        <label>Business Unit: &nbsp;</label>
                                        <select name="business_unit" class="form-control">
                                            <?php echo $businessUnitOptions; ?>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Generate</button>
                                    <button type="button" class="btn btn-default" onclick="window.print();"><i class="fa fa-print"></i> Print</button>
                                    <a href="cut-shape-report.php?date=<?php echo urlencode($selected_date); ?>&business_unit=<?php echo urlencode($selected_business_unit); ?>&export=xlsx" class="btn btn-success"><i class="fa fa-file-excel-o"></i> Export Excel</a>
                                    <a href="cut-shape-report.php?date=<?php echo urlencode($selected_date); ?>&business_unit=<?php echo urlencode($selected_business_unit); ?>&export=csv" class="btn btn-default"><i class="fa fa-download"></i> Export CSV</a>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Report Body -->
                <div class="cs-report">
                    <h1>Cut &amp; Shape Report <small><?php echo $formattedDate; ?></small></h1>

                    <?php if (empty($groups)): ?>
                        <div class="alert alert-info">No orders found for the selected date.</div>
                    <?php else: ?>
                        <table class="cs-table">
                            <thead>
                                <tr>
                                    <th class="col-group">Product Type</th>
                                    <th class="col-weight">Total Weight</th>
                                    <th class="col-name">Item Name</th>
                                    <th class="col-qty">Original / Amended Qty</th>
                                    <th class="col-qty">Late Order Qty</th>
                                    <th class="col-qty">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($groups as $groupName => $group):
                                $totalWeightKg = $group['total_weight_g'] > 0 ? round($group['total_weight_g'] / 1000, 2) : 0;
                                $weightLabel = $totalWeightKg . 'kg';
                                $itemCount = count($group['items']);
                            ?>
                                <!-- Group header row -->
                                <tr class="group-row">
                                    <td rowspan="<?php echo $itemCount + 1; ?>" class="group-label"><?php echo htmlspecialchars($groupName); ?></td>
                                    <td rowspan="<?php echo $itemCount + 1; ?>" class="group-label"><span class="group-weight"><?php echo htmlspecialchars($weightLabel); ?></span></td>
                                    <td></td>
                                    <td class="col-qty orig-total"><?php echo (int)$group['original_total_qty']; ?></td>
                                    <td class="col-qty total-highlight"><?php echo (int)$group['late_total_qty']; ?></td>
                                    <td class="col-qty total-highlight"><?php echo (int)$group['total_qty']; ?></td>
                                </tr>
                                <?php foreach ($group['items'] as $itemIndex => $item): ?>
                                <tr class="item-row<?php echo $itemIndex === $itemCount - 1 ? ' group-end' : ''; ?>">
                                    <!-- group & weight cells consumed by rowspan -->
                                    <td>
                                        <a href="#" class="item-link item-detail-link" data-item-id="<?php echo (int)$item['item_id']; ?>" data-item-name="<?php echo htmlspecialchars($item['item_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars($item['item_name']); ?>
                                        </a>
                                    </td>
                                    <td class="col-qty<?php echo $item['cart_qty'] > 0 ? ' original-overridden' : ''; ?>"><?php echo (int)$item['standing_qty']; ?></td>
                                    <td class="col-qty<?php echo $item['cart_qty'] > 0 ? ' late-highlight' : ''; ?>"><?php echo (int)$item['cart_qty']; ?></td>
                                    <td class="col-qty total-highlight"><?php echo (int)$item['total_qty']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <div id="itemCustomerQtyModal" class="modal fade no-print" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-md">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                                <h4 class="modal-title">Customer Order Qty - <span id="itemCustomerQtyTitle"></span></h4>
                            </div>
                            <div class="modal-body">
                                <div id="itemCustomerQtyLoading" style="display:none;">Loading...</div>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped" id="itemCustomerQtyTable">
                                        <thead>
                                            <tr>
                                                <th>Customer</th>
                                                <th style="width:170px;">Order No</th>
                                                <th style="width:90px; text-align:center;">Standing</th>
                                                <th style="width:90px; text-align:center;">Cart</th>
                                                <th style="width:90px; text-align:center;">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody id="itemCustomerQtyBody"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php include('common/footer.php'); ?>
    <script>
        (function ($) {
            var reportDate = '<?php echo addslashes($selected_date); ?>';
            var selectedBusinessUnit = '<?php echo addslashes($selected_business_unit); ?>';
            var hasBootstrapModal = !!($.fn && $.fn.modal);

            function openItemCustomerQtyModal() {
                if (hasBootstrapModal) {
                    $('#itemCustomerQtyModal').modal('show');
                    return;
                }

                $('#itemCustomerQtyModal').show().addClass('in').attr('aria-hidden', 'false');
                if (!$('#itemCustomerQtyBackdrop').length) {
                    $('body').append('<div id="itemCustomerQtyBackdrop" class="modal-backdrop fade in"></div>');
                }
                $('body').addClass('modal-open');
            }

            function closeItemCustomerQtyModal() {
                if (hasBootstrapModal) {
                    $('#itemCustomerQtyModal').modal('hide');
                    return;
                }

                $('#itemCustomerQtyModal').hide().removeClass('in').attr('aria-hidden', 'true');
                $('#itemCustomerQtyBackdrop').remove();
                $('body').removeClass('modal-open');
            }

            $(document).on('click', '.item-detail-link', function (e) {
                e.preventDefault();

                var itemId = parseInt($(this).data('item-id'), 10) || 0;
                var itemName = $(this).data('item-name') || 'Item';

                if (!itemId) {
                    return;
                }

                $('#itemCustomerQtyTitle').text(itemName + ' (' + reportDate + ')');
                $('#itemCustomerQtyBody').html('');
                $('#itemCustomerQtyLoading').show();
                openItemCustomerQtyModal();

                $.ajax({
                    url: 'cut-shape-report.php',
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        ajax: 'item_customer_qty',
                        item_id: itemId,
                        date: reportDate,
                        business_unit: selectedBusinessUnit
                    }
                }).done(function (res) {
                    var rows = (res && res.success && res.rows) ? res.rows : [];
                    var html = '';

                    if (!rows.length) {
                        html = '<tr><td colspan="5" class="text-center text-muted">No customer orders found.</td></tr>';
                    } else {
                        $.each(rows, function (_, row) {
                            html += '<tr>' +
                                '<td>' + $('<div>').text(row.customer_name || '').html() + '</td>' +
                                '<td>' + $('<div>').text(row.order_nos || '-').html() + '</td>' +
                                '<td style="text-align:center;">' + parseInt(row.standing_qty || 0, 10) + '</td>' +
                                '<td style="text-align:center;">' + parseInt(row.cart_qty || 0, 10) + '</td>' +
                                '<td style="text-align:center;"><strong>' + parseInt(row.total_qty || 0, 10) + '</strong></td>' +
                                '</tr>';
                        });
                    }

                    $('#itemCustomerQtyBody').html(html);
                }).fail(function () {
                    $('#itemCustomerQtyBody').html('<tr><td colspan="5" class="text-center text-danger">Failed to load customer quantities.</td></tr>');
                }).always(function () {
                    $('#itemCustomerQtyLoading').hide();
                });
            });

            $(document).on('click', '#itemCustomerQtyModal .close, #itemCustomerQtyBackdrop', function () {
                closeItemCustomerQtyModal();
            });

            $(document).on('keydown', function (e) {
                if (e.key === 'Escape' && $('#itemCustomerQtyModal').is(':visible')) {
                    closeItemCustomerQtyModal();
                }
            });
        })(jQuery);
    </script>
</body>
</html>
