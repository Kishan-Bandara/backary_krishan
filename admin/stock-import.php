<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('include/database.php');
include('include/check_login.php');
include('get_url.php');

date_default_timezone_set("Asia/Colombo");

// Super admin only
if (!isSuperAdmin()) {
    header('Location: access_denied.php');
    exit;
}

$db = new Database();

// Fetch groups and types for default assignment
$groups = $db->getRows('SELECT * FROM gorup_master ORDER BY group_name');
$types = $db->getRows('SELECT t.*, g.group_name FROM type_master t JOIN gorup_master g ON t.group_id = g.group_id ORDER BY g.group_name, t.type_name');
$locations = $db->getRows('SELECT * FROM location_master ORDER BY id');

// Default location
$defaultLocationId = 1;
if (isset($_SESSION['location']) && (int)$_SESSION['location'] > 0) {
    $defaultLocationId = (int)$_SESSION['location'];
}

// Process upload
$uploadResults = null;
$uploadError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_stock'])) {
    // CSRF token check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['stock_import_csrf'] ?? '')) {
        $uploadError = 'Invalid form submission. Please try again.';
    } elseif (!isset($_FILES['stock_file']) || $_FILES['stock_file']['error'] !== UPLOAD_ERR_OK) {
        $uploadError = 'Please select a valid Excel file to upload.';
    } else {
        $fileExtension = strtolower(pathinfo($_FILES['stock_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, ['xlsx', 'xls'])) {
            $uploadError = 'Only .xlsx and .xls files are supported.';
        } else {
            // Validate file size (max 10MB)
            if ($_FILES['stock_file']['size'] > 10 * 1024 * 1024) {
                $uploadError = 'File size exceeds 10MB limit.';
            } else {
                $uploadResults = processStockImport($db, $_FILES['stock_file']['tmp_name'], $_POST);
            }
        }
    }
}

// Generate CSRF token
$_SESSION['stock_import_csrf'] = bin2hex(random_bytes(32));

function processStockImport(Database $db, $filePath, $postData)
{
    // Load PhpSpreadsheet
    $autoloadPaths = [
        __DIR__ . '/vendor/autoload.php',
        __DIR__ . '/DB Migration/vendor/autoload.php',
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
        return ['error' => 'PhpSpreadsheet library not found. Please run: composer require phpoffice/phpspreadsheet'];
    }

    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
    } catch (Exception $e) {
        return ['error' => 'Failed to read Excel file: ' . $e->getMessage()];
    }

    $sheet = $spreadsheet->getActiveSheet();
    $highestRow = $sheet->getHighestRow();
    $highestCol = $sheet->getHighestColumn();

    // Read header row to auto-map columns
    $headers = [];
    $colIndex = 1;
    foreach ($sheet->getRowIterator(1, 1) as $row) {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);
        foreach ($cellIterator as $cell) {
            $val = strtolower(trim((string)$cell->getValue()));
            $headers[$colIndex] = $val;
            $colIndex++;
        }
    }

    // Map columns by common header names
    $columnMap = autoMapColumns($headers);

    if (!isset($columnMap['sku'])) {
        return ['error' => 'Could not find a SKU/Item Code column in the Excel file. Found headers: ' . implode(', ', array_values($headers))];
    }
    if (!isset($columnMap['qty'])) {
        return ['error' => 'Could not find a Qty/Quantity column in the Excel file. Found headers: ' . implode(', ', array_values($headers))];
    }

    $locationId = (int)($postData['location_id'] ?? 1);
    $defaultGroupId = (int)($postData['default_group_id'] ?? 0);
    $defaultTypeId = (int)($postData['default_type_id'] ?? 0);
    $batchPrefix = trim($postData['batch_prefix'] ?? 'IMP');
    $importDate = date('Y-m-d H:i:s');
    $batchNo = $batchPrefix . '-' . date('Ymd-His');

    $results = [
        'total_rows' => 0,
        'stock_updated' => 0,
        'new_products' => 0,
        'skipped' => 0,
        'errors' => [],
        'rows' => [],
    ];

    for ($rowNum = 2; $rowNum <= $highestRow; $rowNum++) {
        $sku = getWorksheetCellText($sheet, $columnMap['sku'], $rowNum);

        if ($sku === '') {
            continue;
        }

        $results['total_rows']++;

        $productName = isset($columnMap['name']) ? getWorksheetCellText($sheet, $columnMap['name'], $rowNum) : '';
        $qty = getWorksheetCellNumber($sheet, $columnMap['qty'], $rowNum);
        $costPrice = isset($columnMap['cost']) ? getWorksheetCellNumber($sheet, $columnMap['cost'], $rowNum) : 0;
        $sellPrice = isset($columnMap['sell_price']) ? getWorksheetCellNumber($sheet, $columnMap['sell_price'], $rowNum) : 0;
        $uom = isset($columnMap['uom']) ? getWorksheetCellText($sheet, $columnMap['uom'], $rowNum) : 'EA';
        $barcode = isset($columnMap['barcode']) ? getWorksheetCellText($sheet, $columnMap['barcode'], $rowNum) : '';
        $category = isset($columnMap['category']) ? getWorksheetCellText($sheet, $columnMap['category'], $rowNum) : '';

        if ($qty <= 0) {
            $results['skipped']++;
            $results['rows'][] = [
                'row' => $rowNum,
                'sku' => $sku,
                'name' => $productName,
                'status' => 'skipped',
                'message' => 'Qty is zero or empty',
            ];
            continue;
        }

        try {
            // Check if SKU exists in item_master
            $existingProduct = $db->getRow('SELECT item_id, item_name, item_purchase_price, batch_tracking FROM item_master WHERE item_code = ?', [$sku]);

            if ($existingProduct) {
                // SKU EXISTS → Add stock with batch
                $productId = (int)$existingProduct['item_id'];
                $rate = $costPrice > 0 ? $costPrice : (float)$existingProduct['item_purchase_price'];
                $batchTracking = $existingProduct['batch_tracking'] ?? 'NONE';

                $batchId = createOrFindBatch($db, $productId, $batchNo, $batchTracking);

                // Insert FIFO stock record (ft_type=1 for inbound)
                $db->insertRow(
                    'INSERT INTO fifo (ft_location, ft_document, ft_item, ft_qty, ft_blanace, ft_rate, ft_date, ft_type, batch_id) VALUES (?,?,?,?,?,?,?,?,?)',
                    [$locationId, 0, $productId, $qty, $qty, $rate, $importDate, 1, $batchId]
                );

                $results['stock_updated']++;
                $results['rows'][] = [
                    'row' => $rowNum,
                    'sku' => $sku,
                    'name' => $existingProduct['item_name'],
                    'qty' => $qty,
                    'status' => 'updated',
                    'message' => 'Stock added (' . $qty . ' units @ ' . number_format($rate, 2) . ')',
                ];
            } else {
                // SKU DOES NOT EXIST → Create new product then add stock
                if ($productName === '') {
                    $productName = 'Product ' . $sku;
                }

                $newProductId = createNewProduct($db, $sku, $productName, $costPrice, $sellPrice, $uom, $barcode, $category, $defaultGroupId, $defaultTypeId);

                $batchId = createOrFindBatch($db, $newProductId, $batchNo, 'BATCH');

                // Insert FIFO stock record
                $rate = $costPrice > 0 ? $costPrice : 0;
                $db->insertRow(
                    'INSERT INTO fifo (ft_location, ft_document, ft_item, ft_qty, ft_blanace, ft_rate, ft_date, ft_type, batch_id) VALUES (?,?,?,?,?,?,?,?,?)',
                    [$locationId, 0, $newProductId, $qty, $qty, $rate, $importDate, 1, $batchId]
                );

                $results['new_products']++;
                $results['rows'][] = [
                    'row' => $rowNum,
                    'sku' => $sku,
                    'name' => $productName,
                    'qty' => $qty,
                    'status' => 'created',
                    'message' => 'New product created & stock added (' . $qty . ' units)',
                ];
            }
        } catch (Exception $e) {
            $results['errors'][] = 'Row ' . $rowNum . ' (' . $sku . '): ' . $e->getMessage();
            $results['rows'][] = [
                'row' => $rowNum,
                'sku' => $sku,
                'name' => $productName,
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    return $results;
}

function getWorksheetCell($sheet, $columnIndex, $rowNum)
{
    $cellReference = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex((int)$columnIndex) . $rowNum;

    return $sheet->getCell($cellReference);
}

function resolveWorksheetCellValue($cell)
{
    try {
        return $cell->getCalculatedValue();
    } catch (Exception $e) {
        $value = $cell->getValue();
        if (is_string($value) && strncmp($value, '=', 1) === 0) {
            $formattedValue = $cell->getFormattedValue();
            if ($formattedValue !== '') {
                return $formattedValue;
            }
        }

        return $value;
    }
}

function getWorksheetCellText($sheet, $columnIndex, $rowNum)
{
    return trim((string)resolveWorksheetCellValue(getWorksheetCell($sheet, $columnIndex, $rowNum)));
}

function getWorksheetCellNumber($sheet, $columnIndex, $rowNum)
{
    $cell = getWorksheetCell($sheet, $columnIndex, $rowNum);
    $value = resolveWorksheetCellValue($cell);

    if (is_numeric($value)) {
        return (float)$value;
    }

    $stringValue = trim((string)$value);
    if ($stringValue === '') {
        $stringValue = trim((string)$cell->getFormattedValue());
    }

    return normalizeImportedNumber($stringValue);
}

function normalizeImportedNumber($value)
{
    $value = trim((string)$value);
    if ($value === '') {
        return 0.0;
    }

    $value = preg_replace('/\s+/u', '', $value);
    $value = preg_replace('/[^0-9,\.\-]/', '', $value);

    if ($value === '' || $value === '-') {
        return 0.0;
    }

    $lastComma = strrpos($value, ',');
    $lastDot = strrpos($value, '.');

    if ($lastComma !== false && $lastDot !== false) {
        if ($lastComma > $lastDot) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } else {
            $value = str_replace(',', '', $value);
        }
    } elseif ($lastComma !== false) {
        if (substr_count($value, ',') === 1) {
            $value = str_replace(',', '.', $value);
        } else {
            $value = str_replace(',', '', $value);
        }
    }

    return is_numeric($value) ? (float)$value : 0.0;
}

function createOrFindBatch(Database $db, $productId, $batchNo, $batchTracking)
{
    if ($batchTracking === 'NONE') {
        // Enable batch tracking for imports
        $db->updateRow('UPDATE item_master SET batch_tracking = ? WHERE item_id = ? AND batch_tracking = ?', ['BATCH', $productId, 'NONE']);
    }

    $existingBatch = $db->getRow('SELECT batch_id FROM batch_master WHERE product_id = ? AND batch_no = ?', [$productId, $batchNo]);
    if ($existingBatch) {
        return (int)$existingBatch['batch_id'];
    }

    $db->insertRow(
        'INSERT INTO batch_master (product_id, batch_no, expiry_date) VALUES (?,?,?)',
        [$productId, $batchNo, null]
    );

    $newBatch = $db->getRow('SELECT batch_id FROM batch_master WHERE product_id = ? AND batch_no = ?', [$productId, $batchNo]);
    return (int)($newBatch['batch_id'] ?? 0);
}

function createNewProduct(Database $db, $sku, $name, $costPrice, $sellPrice, $uom, $barcode, $category, $defaultGroupId, $defaultTypeId)
{
    // Generate URL-safe slug
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
    $lastRow = $db->getRow('SELECT MAX(item_id) as max_id FROM item_master');
    $nextId = ((int)($lastRow['max_id'] ?? 0)) + 1;
    $urlSlug = $slug . '-' . $nextId;

    $db->insertRow(
        'INSERT INTO item_master (
            item_code, item_name, item_group, item_type, item_category,
            item_discription, item_uom, item_purchase_price,
            item_normal_selling_price, item_barcode, item_active,
            item_vat, url, item_mode, live, batch_tracking
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
        [
            $sku,
            $name,
            $defaultGroupId > 0 ? $defaultGroupId : null,
            $defaultTypeId > 0 ? $defaultTypeId : null,
            $category !== '' ? $category : null,
            $name,
            $uom !== '' ? $uom : 'EA',
            $costPrice,
            $sellPrice > 0 ? $sellPrice : $costPrice,
            $barcode !== '' ? $barcode : $sku,
            'Y',
            0,
            $urlSlug,
            'goods',
            1,
            'BATCH'
        ]
    );

    $newProduct = $db->getRow('SELECT item_id FROM item_master WHERE item_code = ? ORDER BY item_id DESC LIMIT 1', [$sku]);
    return (int)($newProduct['item_id'] ?? 0);
}

function autoMapColumns($headers)
{
    $map = [];
    $normalizedHeaders = [];
    $exactMatches = [
        'sku' => ['sku', 'item code', 'product code', 'stock code', 'item no', 'product no', 'code'],
        'name' => ['name', 'item name', 'product name', 'description', 'product description', 'item description'],
        'qty' => ['qty', 'quantity', 'stock', 'stock qty', 'stock quantity', 'on hand', 'qty in hand', 'quantity in hand', 'available qty', 'available quantity', 'inventory qty', 'inventory quantity', 'opening stock', 'opening qty', 'count'],
        'cost' => ['cost', 'purchase price', 'buy price', 'unit cost', 'cost price', 'landed cost'],
        'sell_price' => ['sell price', 'selling price', 'sale price', 'retail price', 'rrp', 'unit price', 'price'],
        'uom' => ['uom', 'unit', 'unit of measure', 'measure'],
        'barcode' => ['barcode', 'bar code', 'ean', 'upc', 'gtin'],
        'category' => ['category', 'cat', 'type', 'group'],
    ];
    $patterns = [
        'sku' => '/\b(sku|item code|product code|stock code|item no|product no|code)\b/i',
        'name' => '/\b(name|item name|product name|description|product description|item description)\b/i',
        'qty' => '/\b(qty|quantity|on hand|available|inventory|count|opening stock|opening qty)\b/i',
        'cost' => '/\b(cost|purchase price|buy price|unit cost|cost price|landed cost)\b/i',
        'sell_price' => '/\b(sell|selling price|sale price|retail|rrp|unit price|price)\b/i',
        'uom' => '/\b(uom|unit|unit of measure|measure)\b/i',
        'barcode' => '/\b(barcode|bar code|ean|upc|gtin)\b/i',
        'category' => '/\b(category|cat|type|group)\b/i',
    ];

    foreach ($headers as $colIndex => $headerVal) {
        $normalizedHeaders[$colIndex] = normalizeImportHeader($headerVal);
    }

    foreach ($normalizedHeaders as $colIndex => $headerVal) {
        if ($headerVal === '') {
            continue;
        }
        foreach ($exactMatches as $field => $aliases) {
            if (!isset($map[$field]) && in_array($headerVal, $aliases, true)) {
                $map[$field] = $colIndex;
            }
        }
    }

    foreach ($normalizedHeaders as $colIndex => $headerVal) {
        if ($headerVal === '') {
            continue;
        }
        foreach ($patterns as $field => $pattern) {
            if (isset($map[$field])) {
                continue;
            }

            if ($field === 'qty' && strpos($headerVal, 'stock') !== false) {
                if (preg_match('/\b(code|name|category|group|type|barcode|uom|unit|price|cost|description)\b/i', $headerVal)) {
                    continue;
                }

                $map[$field] = $colIndex;
                continue;
            }

            if (preg_match($pattern, $headerVal)) {
                $map[$field] = $colIndex;
            }
        }
    }

    return $map;
}

function normalizeImportHeader($header)
{
    $header = strtolower(trim((string)$header));
    $header = preg_replace('/[^a-z0-9]+/i', ' ', $header);

    return trim(preg_replace('/\s+/', ' ', $header));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Stock Import | Admin Panel</title>
    <?php include('common/head.php'); ?>
    <style>
        .import-box {
            background: #fff;
            border-radius: 12px;
            padding: 28px;
            margin-bottom: 24px;
            box-shadow: 0 4px 18px rgba(15, 23, 42, 0.06);
            border: 1px solid rgba(148, 163, 184, 0.18);
        }
        .import-box h3 {
            margin: 0 0 20px;
            font-size: 1.15rem;
            font-weight: 700;
            color: #1e293b;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 12px;
        }
        .import-box h3 i {
            margin-right: 10px;
            color: #3b82f6;
        }
        .upload-zone {
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 40px 20px;
            text-align: center;
            background: #f8fafc;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .upload-zone:hover, .upload-zone.dragover {
            border-color: #3b82f6;
            background: #eff6ff;
        }
        .upload-zone i {
            font-size: 48px;
            color: #94a3b8;
            margin-bottom: 12px;
        }
        .upload-zone p {
            color: #64748b;
            margin: 8px 0 0;
        }
        .stat-card {
            border-radius: 12px;
            padding: 18px 20px;
            color: #fff;
            margin-bottom: 16px;
            min-height: 90px;
        }
        .stat-card h5 {
            margin: 0 0 6px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            opacity: 0.88;
        }
        .stat-card strong {
            font-size: 30px;
            line-height: 1;
            display: block;
        }
        .bg-stat-total { background: linear-gradient(135deg, #0f4c5c 0%, #2c7a7b 100%); }
        .bg-stat-updated { background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%); }
        .bg-stat-new { background: linear-gradient(135deg, #059669 0%, #34d399 100%); }
        .bg-stat-skip { background: linear-gradient(135deg, #d97706 0%, #fbbf24 100%); }
        .bg-stat-error { background: linear-gradient(135deg, #b91c1c 0%, #ef4444 100%); }
        .result-table th {
            text-transform: uppercase;
            font-size: 11.5px;
            letter-spacing: 0.05em;
            color: #64748b;
            background: #f8fafc;
            border-top: none;
        }
        .result-table td {
            vertical-align: middle;
            color: #334155;
            font-size: 13px;
        }
        .badge-created { background: #dcfce7; color: #166534; padding: 5px 12px; border-radius: 20px; font-weight: 600; font-size: 12px; }
        .badge-updated { background: #dbeafe; color: #1e40af; padding: 5px 12px; border-radius: 20px; font-weight: 600; font-size: 12px; }
        .badge-skipped { background: #fef3c7; color: #92400e; padding: 5px 12px; border-radius: 20px; font-weight: 600; font-size: 12px; }
        .badge-error-row { background: #fee2e2; color: #991b1b; padding: 5px 12px; border-radius: 20px; font-weight: 600; font-size: 12px; }
        .settings-grid label {
            font-weight: 600;
            color: #334155;
            font-size: 13px;
            margin-bottom: 6px;
        }
        .settings-grid .form-control {
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            padding: 8px 12px;
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
                <div class="container-fluid">
                    <br>
                    <div class="row">
                        <div class="col-sm-12" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                            <div>
                                <h4 class="page-title m-0 font-weight-bold" style="font-size:1.5rem; color:#1e293b;">
                                    <i class="fa fa-upload text-primary"></i> Stock Import from Excel
                                </h4>
                                <p style="margin:6px 0 0; color:#64748b;">Upload customer stock report. Existing SKUs get stock added; new SKUs create products automatically.</p>
                            </div>
                            <span class="badge" style="background:#fee2e2; color:#991b1b; padding:8px 16px; border-radius:20px; font-size:13px; font-weight:600;">
                                <i class="fa fa-lock"></i> Super Admin Only
                            </span>
                        </div>
                    </div>

                    <?php if ($uploadError): ?>
                    <div class="row" style="margin-top:16px;">
                        <div class="col-lg-12">
                            <div class="alert alert-danger" style="border-radius:10px;">
                                <i class="fa fa-exclamation-triangle"></i> <?php echo htmlspecialchars($uploadError); ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($uploadResults && isset($uploadResults['error'])): ?>
                    <div class="row" style="margin-top:16px;">
                        <div class="col-lg-12">
                            <div class="alert alert-danger" style="border-radius:10px;">
                                <i class="fa fa-exclamation-triangle"></i> <?php echo htmlspecialchars($uploadResults['error']); ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($uploadResults && !isset($uploadResults['error'])): ?>
                    <!-- RESULTS -->
                    <div class="row" style="margin-top:20px;">
                        <div class="col-lg-12">
                            <div class="alert alert-success" style="border-radius:10px;">
                                <i class="fa fa-check-circle"></i> Import completed successfully at <?php echo date('M d, Y h:i A'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-2 col-sm-4">
                            <div class="stat-card bg-stat-total">
                                <h5>Total Rows</h5>
                                <strong><?php echo (int)$uploadResults['total_rows']; ?></strong>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-4">
                            <div class="stat-card bg-stat-updated">
                                <h5>Stock Updated</h5>
                                <strong><?php echo (int)$uploadResults['stock_updated']; ?></strong>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-4">
                            <div class="stat-card bg-stat-new">
                                <h5>New Products</h5>
                                <strong><?php echo (int)$uploadResults['new_products']; ?></strong>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4">
                            <div class="stat-card bg-stat-skip">
                                <h5>Skipped</h5>
                                <strong><?php echo (int)$uploadResults['skipped']; ?></strong>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4">
                            <div class="stat-card bg-stat-error">
                                <h5>Errors</h5>
                                <strong><?php echo count($uploadResults['errors']); ?></strong>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($uploadResults['errors'])): ?>
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="alert alert-warning" style="border-radius:10px;">
                                <strong>Errors encountered:</strong>
                                <ul style="margin:8px 0 0; padding-left:20px;">
                                    <?php foreach ($uploadResults['errors'] as $err): ?>
                                        <li><?php echo htmlspecialchars($err); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="import-box">
                                <h3><i class="fa fa-list-alt"></i> Import Details</h3>
                                <div class="table-responsive">
                                    <table class="table table-hover result-table mb-0">
                                        <thead>
                                            <tr>
                                                <th>Row</th>
                                                <th>SKU</th>
                                                <th>Product Name</th>
                                                <th>Qty</th>
                                                <th>Status</th>
                                                <th>Details</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($uploadResults['rows'] as $row): ?>
                                                <tr>
                                                    <td><?php echo (int)$row['row']; ?></td>
                                                    <td><strong><?php echo htmlspecialchars($row['sku']); ?></strong></td>
                                                    <td><?php echo htmlspecialchars($row['name'] ?? ''); ?></td>
                                                    <td><?php echo isset($row['qty']) ? number_format((float)$row['qty'], 2) : '-'; ?></td>
                                                    <td>
                                                        <?php
                                                        $badgeMap = [
                                                            'created' => 'badge-created',
                                                            'updated' => 'badge-updated',
                                                            'skipped' => 'badge-skipped',
                                                            'error' => 'badge-error-row',
                                                        ];
                                                        $badgeCls = $badgeMap[$row['status']] ?? 'badge-skipped';
                                                        ?>
                                                        <span class="<?php echo $badgeCls; ?>">
                                                            <?php echo ucfirst(htmlspecialchars($row['status'])); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($row['message'] ?? ''); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- UPLOAD FORM -->
                    <form method="post" enctype="multipart/form-data" id="importForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['stock_import_csrf']); ?>">

                        <div class="row" style="margin-top:16px;">
                            <div class="col-lg-8">
                                <div class="import-box">
                                    <h3><i class="fa fa-file-excel-o"></i> Upload Stock Report</h3>
                                    <div class="upload-zone" id="uploadZone" onclick="document.getElementById('stockFile').click();">
                                        <i class="fa fa-cloud-upload"></i>
                                        <h4 style="color:#334155; margin:0 0 4px;">Drop Excel file here or click to browse</h4>
                                        <p>Supported formats: .xlsx, .xls (Max 10MB)</p>
                                        <p id="selectedFileName" style="color:#3b82f6; font-weight:600; display:none;"></p>
                                    </div>
                                    <input type="file" name="stock_file" id="stockFile" accept=".xlsx,.xls" style="display:none;">

                                    <div style="margin-top:20px; background:#f0f9ff; border-radius:10px; padding:16px; border:1px solid #bfdbfe;">
                                        <h5 style="margin:0 0 8px; color:#1e40af; font-size:13px;"><i class="fa fa-info-circle"></i> Expected Excel Columns</h5>
                                        <p style="margin:0; color:#334155; font-size:13px; line-height:1.6;">
                                            The system auto-detects columns by header name. Ensure your Excel has at least:
                                        </p>
                                        <ul style="margin:8px 0 0; padding-left:18px; color:#334155; font-size:13px; line-height:1.8;">
                                            <li><strong>SKU / Item Code</strong> — (Required) matched against item_master</li>
                                            <li><strong>Name / Description</strong> — used for new products</li>
                                            <li><strong>Qty / Quantity / Stock</strong> — stock quantity to add</li>
                                            <li><strong>Cost / Purchase Price</strong> — cost rate per unit</li>
                                            <li><strong>Sell Price / Selling Price</strong> — selling price for new products</li>
                                            <li><strong>UOM / Unit</strong> — unit of measure (default: EA)</li>
                                            <li><strong>Barcode</strong> — optional barcode/EAN</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="import-box">
                                    <h3><i class="fa fa-cog"></i> Import Settings</h3>
                                    <div class="settings-grid">
                                        <div class="form-group">
                                            <label>Location</label>
                                            <select name="location_id" class="form-control">
                                                <?php foreach ($locations as $loc): ?>
                                                    <option value="<?php echo (int)$loc['id']; ?>" <?php echo ((int)$loc['id'] === $defaultLocationId) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($loc['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Default Group (for new products)</label>
                                            <select name="default_group_id" class="form-control">
                                                <option value="0">-- None --</option>
                                                <?php foreach ($groups as $grp): ?>
                                                    <option value="<?php echo (int)$grp['group_id']; ?>">
                                                        <?php echo htmlspecialchars($grp['group_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Default Type (for new products)</label>
                                            <select name="default_type_id" class="form-control">
                                                <option value="0">-- None --</option>
                                                <?php foreach ($types as $tp): ?>
                                                    <option value="<?php echo (int)$tp['type_id']; ?>">
                                                        <?php echo htmlspecialchars($tp['group_name'] . ' → ' . $tp['type_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Batch No. Prefix</label>
                                            <input type="text" name="batch_prefix" class="form-control" value="IMP" placeholder="e.g. IMP, CUST, STK">
                                            <small class="text-muted">Batch will be: PREFIX-YYYYMMDD-HHMMSS</small>
                                        </div>
                                        <hr>
                                        <button type="submit" name="import_stock" value="1" class="btn btn-primary btn-block" style="border-radius:10px; padding:12px; font-weight:700; font-size:15px;" id="btnImport" disabled>
                                            <i class="fa fa-upload"></i> Import Stock
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <script>
        var resizefunc = [];
    </script>
    <?php include('common/footer.php'); ?>
    <script>
        var fileInput = document.getElementById('stockFile');
        var uploadZone = document.getElementById('uploadZone');
        var btnImport = document.getElementById('btnImport');
        var fileNameDisplay = document.getElementById('selectedFileName');

        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                fileNameDisplay.textContent = this.files[0].name;
                fileNameDisplay.style.display = 'block';
                btnImport.disabled = false;
                uploadZone.style.borderColor = '#22c55e';
                uploadZone.style.background = '#f0fdf4';
            } else {
                fileNameDisplay.style.display = 'none';
                btnImport.disabled = true;
                uploadZone.style.borderColor = '#cbd5e1';
                uploadZone.style.background = '#f8fafc';
            }
        });

        // Drag and drop
        uploadZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.add('dragover');
        });
        uploadZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('dragover');
        });
        uploadZone.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('dragover');
            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                var event = new Event('change');
                fileInput.dispatchEvent(event);
            }
        });

        // Confirm before import
        document.getElementById('importForm').addEventListener('submit', function(e) {
            if (!confirm('Are you sure you want to import this stock file? This will add stock and may create new products.')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
