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
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

$spreadsheet = new Spreadsheet();

// ─── Sheet 1: Customer + Shipping Data ───
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Customers');

$headers = [
    'A' => 'customer_code',
    'B' => 'customer_name',
    'C' => 'legal_name',
    'D' => 'trading_name',
    'E' => 'email',
    'F' => 'phone',
    'G' => 'mobile',
    'H' => 'address_line_1',
    'I' => 'address_line_2',
    'J' => 'city',
    'K' => 'postal_code',
    'L' => 'country',
    'M' => 'state',
    'N' => 'credit_limit',
    'O' => 'abn_no',
    'P' => 'gst_no',
    'Q' => 'customer_note',
    'R' => 'contact_name',
    'S' => 'contact_email',
    'T' => 'contact_telephone',
    'U' => 'ship_address_label',
    'V' => 'ship_address_line_1',
    'W' => 'ship_address_line_2',
    'X' => 'ship_city',
    'Y' => 'ship_postal_code',
    'Z' => 'ship_country',
    'AA' => 'ship_state',
    'AB' => 'ship_contact_person',
    'AC' => 'ship_contact_phone',
    'AD' => 'ship_contact_email',
    'AE' => 'ship_delivery_route',
    'AF' => 'ship_remarks',
];

// Write headers
foreach ($headers as $col => $label) {
    $sheet->setCellValue($col . '1', $label);
}

// Style headers
$headerRange = 'A1:AF1';
$sheet->getStyle($headerRange)->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);

// Customer columns (A-T) background
$sheet->getStyle('A1:T1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('4472C4');
// Shipping columns (U-AF) background
$sheet->getStyle('U1:AF1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('548235');

// Add 3 sample rows
$sampleData = [
    [
        'CUST-0001', 'ABC Bakery', 'ABC Bakery Pty Ltd', 'ABC Bakes', 'info@abcbakery.com',
        '0312345678', '0412345678', '10 Main Street', 'Suite 2', 'Melbourne', '3000',
        'Australia', 'VIC', '5000.00', '12345678901', 'GST-001', 'Preferred customer',
        'John Smith', 'john@abcbakery.com', '0412345678',
        'Main Store', '10 Main Street', 'Suite 2', 'Melbourne', '3000', 'Australia', 'VIC',
        'John Smith', '0412345678', 'john@abcbakery.com', '', 'Ring bell on arrival',
    ],
    [
        '', 'XYZ Cafe', '', 'XYZ Coffee', 'hello@xyzcafe.com',
        '0398765432', '0498765432', '25 Queen St', '', 'Sydney', '2000',
        'Australia', 'NSW', '3000.00', '', '', '',
        'Jane Doe', 'jane@xyzcafe.com', '0498765432',
        'Cafe Front', '25 Queen St', '', 'Sydney', '2000', 'Australia', 'NSW',
        'Jane Doe', '0498765432', 'jane@xyzcafe.com', '', '',
    ],
    [
        '', 'XYZ Cafe', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '',
        '', '', '',
        'Warehouse', '100 Industrial Rd', 'Unit 5', 'Sydney', '2100', 'Australia', 'NSW',
        'Bob Lee', '0411111111', 'bob@xyzcafe.com', '', 'Use back entrance',
    ],
];

$rowNum = 2;
foreach ($sampleData as $row) {
    $colIdx = 0;
    foreach (array_keys($headers) as $col) {
        $sheet->setCellValue($col . $rowNum, $row[$colIdx] ?? '');
        $colIdx++;
    }
    $rowNum++;
}

// Auto-size columns
foreach (array_keys($headers) as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// ─── Sheet 2: Instructions ───
$instrSheet = $spreadsheet->createSheet();
$instrSheet->setTitle('Instructions');

$instructions = [
    ['Customer Bulk Upload - Instructions'],
    [''],
    ['COLUMNS A–T: Customer Details (blue headers)'],
    ['COLUMNS U–AF: Shipping Address Details (green headers)'],
    [''],
    ['RULES:'],
    ['1. customer_name (column B) is REQUIRED for every new customer row.'],
    ['2. customer_code (column A) is optional. If left blank, the system auto-generates one.'],
    ['3. email (column E) must be unique if provided. Duplicates will be skipped.'],
    ['4. To add MULTIPLE shipping addresses for the SAME customer:'],
    ['   - Fill in the customer_name (column B) with the EXACT same name on a new row.'],
    ['   - Leave columns A and C–T BLANK on the extra shipping row.'],
    ['   - Fill only the shipping columns (U–AF) on that row.'],
    ['   - See sample rows 2–4: XYZ Cafe has 2 shipping addresses.'],
    [''],
    ['5. address_line_1 (column H) is required for the customer billing address.'],
    ['6. ship_address_line_1 (column V) is required for each shipping address row.'],
    ['7. credit_limit (column N) should be a number (e.g., 5000.00).'],
    ['8. ship_delivery_route (column AE) should match an existing route name.'],
    [''],
    ['TIPS:'],
    ['- Save file as .xlsx before uploading.'],
    ['- Maximum 500 rows per upload.'],
    ['- The first row (headers) must NOT be changed or removed.'],
    ['- Empty rows are automatically skipped.'],
];

foreach ($instructions as $idx => $row) {
    $instrSheet->setCellValue('A' . ($idx + 1), $row[0]);
}
$instrSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$instrSheet->getStyle('A3')->getFont()->setBold(true);
$instrSheet->getStyle('A4')->getFont()->setBold(true);
$instrSheet->getStyle('A6')->getFont()->setBold(true);
$instrSheet->getStyle('A21')->getFont()->setBold(true);
$instrSheet->getColumnDimension('A')->setWidth(80);

// Switch back to first sheet
$spreadsheet->setActiveSheetIndex(0);

// Output
ob_end_clean();
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="customer_bulk_upload_template.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
