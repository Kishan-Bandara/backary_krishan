<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('include/database.php');
include('include/check_login.php');

// Load PhpSpreadsheet
$autoloadPaths = [
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/DB Migration/vendor/autoload.php',
];
$loaded = false;
foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $loaded = true;
        break;
    }
}
if (!$loaded) {
    die('PhpSpreadsheet library not found.');
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$spreadsheet = new Spreadsheet();

// ─── Sheet 1: Standing Order Data ───
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Standing Orders');

$headers = [
    'A' => 'customer_code',
    'B' => 'customer_name',
    'C' => 'item_code',
    'D' => 'item_name',
    'E' => 'mon_qty',
    'F' => 'tue_qty',
    'G' => 'wed_qty',
    'H' => 'thu_qty',
    'I' => 'fri_qty',
    'J' => 'sat_qty',
    'K' => 'sun_qty',
    'L' => 'shipping_address_label',
    'M' => 'delivery_amount',
    'N' => 'repeat_interval',
    'O' => 'repeat_unit',
    'P' => 'date_from',
    'Q' => 'date_to',
];

// Write headers
foreach ($headers as $col => $label) {
    $sheet->setCellValue($col . '1', $label);
}

// Style headers
$headerRange = 'A1:Q1';
$sheet->getStyle($headerRange)->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);

// Customer columns (A-B) background - blue
$sheet->getStyle('A1:B1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('4472C4');
// Item + qty columns (C-K) background - green
$sheet->getStyle('C1:K1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('548235');
// Order settings columns (L-Q) background - orange
$sheet->getStyle('L1:Q1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('ED7D31');

// Sample data - Customer 1 with 2 items
$sampleData = [
    ['CUST-1000', 'Nine Yards', 'PROD-001', 'Sourdough Loaf', 10, 10, 10, 10, 10, 5, 0, 'Main Store', '15.00', 1, 'Week', '2025-01-01', '2025-12-31'],
    ['CUST-1000', 'Nine Yards', 'PROD-002', 'Croissant', 20, 20, 15, 15, 20, 10, 0, '', '', '', '', '', ''],
    ['CUST-1002', 'Small Cups Coffee', 'PROD-001', 'Sourdough Loaf', 5, 5, 5, 5, 5, 0, 0, 'CBD Outlet', '10.00', 1, 'Week', '2025-01-01', ''],
    ['CUST-1002', 'Small Cups Coffee', 'PROD-003', 'Banana Bread', 8, 8, 8, 8, 8, 4, 0, '', '', '', '', '', ''],
];

$row = 2;
foreach ($sampleData as $data) {
    $col = 'A';
    foreach ($data as $value) {
        $sheet->setCellValue($col . $row, $value);
        $col++;
    }
    $row++;
}

// Auto-size columns
foreach (range('A', 'Q') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// ─── Sheet 2: Instructions ───
$instrSheet = $spreadsheet->createSheet();
$instrSheet->setTitle('Instructions');

$instructions = [
    ['Standing Order Bulk Upload - Instructions'],
    [''],
    ['Column Descriptions:'],
    ['Column', 'Description', 'Required'],
    ['customer_code', 'Customer code from the system (e.g. CUST-1000)', 'YES'],
    ['customer_name', 'Customer name (for reference only, customer_code is used for matching)', 'No'],
    ['item_code', 'Product item code from the system', 'YES'],
    ['item_name', 'Product name (for reference only, item_code is used for matching)', 'No'],
    ['mon_qty', 'Monday quantity', 'No (default 0)'],
    ['tue_qty', 'Tuesday quantity', 'No (default 0)'],
    ['wed_qty', 'Wednesday quantity', 'No (default 0)'],
    ['thu_qty', 'Thursday quantity', 'No (default 0)'],
    ['fri_qty', 'Friday quantity', 'No (default 0)'],
    ['sat_qty', 'Saturday quantity', 'No (default 0)'],
    ['sun_qty', 'Sunday quantity', 'No (default 0)'],
    ['shipping_address_label', 'Shipping address label (must match an existing address for the customer)', 'No'],
    ['delivery_amount', 'Delivery charge amount', 'No (default 0)'],
    ['repeat_interval', 'Repeat interval number (e.g. 1)', 'No'],
    ['repeat_unit', 'Repeat unit: Day, Week, or Month', 'No'],
    ['date_from', 'Start date (YYYY-MM-DD)', 'No (defaults to today)'],
    ['date_to', 'End date (YYYY-MM-DD)', 'No (open-ended if empty)'],
    [''],
    ['Important Notes:'],
    ['1. Each row represents ONE item in a standing order.'],
    ['2. Multiple items for the same customer should have the same customer_code.'],
    ['3. Shipping address, delivery amount, repeat settings, and dates only need to be filled on the FIRST row for each customer.'],
    ['4. If the customer already has an active standing order, it will be REPLACED with the new data.'],
    ['5. Only products allowed in sales can be added.'],
    ['6. At least one day quantity must be greater than 0 for each item row.'],
    ['7. Customer must already exist in the system (matched by customer_code).'],
    ['8. Maximum 500 rows per upload.'],
];

$row = 1;
foreach ($instructions as $line) {
    $col = 'A';
    foreach ($line as $val) {
        $instrSheet->setCellValue($col . $row, $val);
        $col++;
    }
    $row++;
}

// Style title
$instrSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$instrSheet->getStyle('A3')->getFont()->setBold(true)->setSize(12);
$instrSheet->getStyle('A4:C4')->getFont()->setBold(true);
$instrSheet->getStyle('A4:C4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9E2F3');
$instrSheet->getStyle('A23')->getFont()->setBold(true)->setSize(12);

foreach (['A', 'B', 'C'] as $col) {
    $instrSheet->getColumnDimension($col)->setAutoSize(true);
}

// Switch back to first sheet
$spreadsheet->setActiveSheetIndex(0);

// Clean any buffered output before sending binary file
if (ob_get_level()) {
    ob_end_clean();
}

// Output
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="standing_order_bulk_template.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
