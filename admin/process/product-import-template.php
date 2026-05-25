<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('../include/database.php');
include('../include/check_login.php');

if (!isSuperAdmin()) {
    header('Location: ../access_denied.php');
    exit;
}

$autoloadPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../DB Migration/vendor/autoload.php',
];

$loaded = false;
foreach ($autoloadPaths as $autoloadPath) {
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
        $loaded = true;
        break;
    }
}

if (!$loaded) {
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'PhpSpreadsheet library not found. Please install phpoffice/phpspreadsheet.';
    exit;
}

$headers = [
    'item_code',
    'item_name',
    'item_group',
    'item_type',
    'item_category',
    'item_business_unit',
    'item_discription',
    'item_uom',
    'unit_of_measure',
    'item_purchase_price',
    'item_normal_selling_price',
    'item_warranty',
    'item_has_sirial',
    'item_vat',
    'item_cod',
    'item_weight',
    'order_qty_min',
    'order_qty_max',
    'low_stock_qty',
    'pack_size',
    'acc_posting_grp_code',
    'gst_vat_code',
    'nutritional_label',
    'sale_or_return',
    'product_specification',
    'live',
    'hide_to_all_customers',
    'wholesale_price',
    'retail_price',
    'item_weight_g',
    'pack_weight_g',
    'minimum_order',
    'description',
    'default_label',
    'food_declarations',
    'seasonal_rule',
    'avail_monday',
    'avail_tuesday',
    'avail_wednesday',
    'avail_thursday',
    'avail_friday',
    'avail_saturday',
    'avail_sunday',
    'pack_type',
    'is_raw_material',
    'batch_tracking',
    'allow_in_sales',
    'allow_in_grn',
    'additional_uoms',
];

$sampleRows = [
    ['PRD-1001', 'Butter Croissant', 'Bakery', 'Pastry', 'Bread', '', 'Freshly baked butter croissant', 'EA', 'Each', 2.50, 3.80, '', '0', '', '9300000000001', '', 1, 100, 10, '', '', 'GST10', '', 0, '', 'Y', 0, 4.20, 4.80, '', '', 1, 'Classic croissant', '', '', '', 1, 1, 1, 1, 1, 1, 0, 'BOX', 0, 'BATCH', 1, 1, 'BOX,DOZEN'],
    ['PRD-1002', 'Chocolate Muffin', 'Bakery', 'Muffin', 'Cake', '', 'Soft chocolate muffin', 'EA', 'Each', 1.90, 3.00, '', '0', '', '9300000000002', '', 1, 100, 5, '', '', 'GST10', '', 0, '', 'Y', 0, 3.00, 3.70, '', '', 1, 'Chocolate muffin', '', '', '', 1, 1, 1, 1, 1, 1, 0, 'BOX', 0, 'NONE', 1, 1, ''],
];

$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Product Import');

foreach ($headers as $index => $header) {
    $sheet->setCellValueByColumnAndRow($index + 1, 1, $header);
}

foreach ($sampleRows as $rowIndex => $rowValues) {
    foreach ($rowValues as $colIndex => $value) {
        $sheet->setCellValueByColumnAndRow($colIndex + 1, $rowIndex + 2, $value);
    }
}

$headerCount = count($headers);
for ($col = 1; $col <= $headerCount; $col++) {
    $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col))->setAutoSize(true);
}

$sheet->freezePane('A2');
$sheet->setAutoFilter($sheet->calculateWorksheetDimension());

if (ob_get_length()) {
    ob_end_clean();
}

$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="product_import_template.xlsx"');
header('Cache-Control: max-age=0');
$writer->save('php://output');
exit;
