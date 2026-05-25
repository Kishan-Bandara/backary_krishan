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

// ─────────────────────────────────────────────────────────────────────────────
// TEMPLATE COLUMN POSITIONS (1-based, fixed)
// Col 1  → Product ID      (used for item match/create)
// Col 2  → Product Code    (SKU)
// Col 3  → Product Name
// Col 4  → Group
// Col 5  → Type
// Col 6  → Category
// Col 7  → Stock Qty
// Col 8  → Batch No
// Col 9  → Expiry Date
// Col 10 → UOM
// Col 11 → Additional UOM
// ─────────────────────────────────────────────────────────────────────────────
define('COL_PRODUCT_ID',      1);
define('COL_PRODUCT_CODE',    2);
define('COL_PRODUCT_NAME',    3);
define('COL_GROUP',           4);
define('COL_TYPE',            5);
define('COL_CATEGORY',        6);
define('COL_STOCK_QTY',       7);
define('COL_BATCH_NO',        8);
define('COL_EXPIRY_DATE',     9);
define('COL_UOM',            10);
define('COL_ADDITIONAL_UOM', 11);

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

    // Check if batch_master table exists
    $batchTableExists = $db->getRow("SHOW TABLES LIKE 'batch_master'");
    if (!$batchTableExists) {
        return ['error' => '<strong>batch_master table does not exist!</strong><br>Please run the batch tracking migration first:<br><a href="process/batch-tracking-migration.php" target="_blank" style="color:#007bff;">Click here to run migration</a><br><br>Or manually run: <code style="background:#f0f0f0;padding:4px">CREATE TABLE IF NOT EXISTS batch_master ( batch_id INT AUTO_INCREMENT PRIMARY KEY, product_id INT NOT NULL, batch_no VARCHAR(100) NOT NULL, expiry_date DATE DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY unique_batch (product_id, batch_no), INDEX idx_product (product_id) ) ENGINE=InnoDB DEFAULT CHARSET=utf8</code>'];
    }

    $sheet      = $spreadsheet->getActiveSheet();
    $highestRow = $sheet->getHighestRow();

    // Validate header row against expected template
    $headerSku = strtolower(trim(getWorksheetCellText($sheet, COL_PRODUCT_CODE, 1)));
    $headerQty = strtolower(trim(getWorksheetCellText($sheet, COL_STOCK_QTY, 1)));
    if (strpos($headerSku, 'code') === false && strpos($headerSku, 'sku') === false) {
        return ['error' => 'Column B does not look like "Product Code". Found: "' . $headerSku . '". Please use the correct template.'];
    }
    if (strpos($headerQty, 'qty') === false && strpos($headerQty, 'stock') === false) {
        return ['error' => 'Column G does not look like "Stock Qty". Found: "' . $headerQty . '". Please use the correct template.'];
    }

    $locationId = (int)($postData['location_id'] ?? 1);
    $importDate = date('Y-m-d H:i:s');

    $results = [
        'total_rows'    => 0,
        'stock_updated' => 0,
        'new_products'  => 0,
        'skipped'       => 0,
        'errors'        => [],
        'rows'          => [],
        'table_operations' => [
            'item_master' => ['inserted' => 0, 'updated' => 0],
            'batch_master' => ['inserted' => 0],
            'fifo' => ['inserted' => 0],
        ],
    ];

    for ($rowNum = 2; $rowNum <= $highestRow; $rowNum++) {
        $sku = getWorksheetCellText($sheet, COL_PRODUCT_CODE, $rowNum);

        if ($sku === '') {
            continue;
        }

        $results['total_rows']++;

        // Read all template columns
        $excelProductId = (int)getWorksheetCellNumber($sheet, COL_PRODUCT_ID,    $rowNum); // Col A (reference only)
        $productName    = getWorksheetCellText($sheet,   COL_PRODUCT_NAME,   $rowNum);
        $groupName      = getWorksheetCellText($sheet,   COL_GROUP,           $rowNum);
        $typeName       = getWorksheetCellText($sheet,   COL_TYPE,            $rowNum);
        $category       = getWorksheetCellText($sheet,   COL_CATEGORY,        $rowNum);
        $qty            = getWorksheetCellNumber($sheet, COL_STOCK_QTY,       $rowNum);
        $batchNo        = getWorksheetCellText($sheet,   COL_BATCH_NO,        $rowNum);
        $expiryRaw      = getWorksheetCellText($sheet,   COL_EXPIRY_DATE,     $rowNum);
        $uom            = getWorksheetCellText($sheet,   COL_UOM,             $rowNum);
        $additionalUom  = getWorksheetCellText($sheet,   COL_ADDITIONAL_UOM,  $rowNum);

        // Parse expiry date (handles dd/mm/yyyy or Excel serial)
        $expiryDate = parseExcelDate($sheet, COL_EXPIRY_DATE, $rowNum, $expiryRaw);

        // Leave batch no as null if not provided
        if ($batchNo === '') {
            $batchNo = null;
        }

        if ($qty <= 0) {
            $results['skipped']++;
            $results['rows'][] = [
                'row'     => $rowNum,
                'sku'     => $sku,
                'name'    => $productName,
                'qty'     => $qty,
                'batch'   => $batchNo,
                'expiry'  => $expiryDate,
                'status'  => 'skipped',
                'message' => 'Stock Qty is zero or empty',
            ];
            continue;
        }

        try {
            // Match strictly by Product Code (SKU) to resolve the real item_id from item_master
            $existingProduct = $db->getRow(
                'SELECT item_id, item_name, item_purchase_price, batch_tracking FROM item_master WHERE item_code = ?',
                [$sku]
            );

            if ($existingProduct) {
                // ── EXISTING PRODUCT → Add stock ──────────────────────────────
                $itemId        = (int)$existingProduct['item_id'];
                $rate          = (float)$existingProduct['item_purchase_price'];
                $batchTracking = $existingProduct['batch_tracking'] ?? 'NONE';

                // Update additional_uoms if provided in Excel
                if ($additionalUom !== '') {
                    $db->updateRow(
                        'UPDATE item_master SET additional_uoms = ? WHERE item_id = ?',
                        [$additionalUom, $itemId]
                    );
                    $results['table_operations']['item_master']['updated']++;
                }

                $batchId = null;
                if ($batchNo !== null) {
                    $batchId = createOrFindBatch($db, $itemId, $batchNo, $expiryDate, $batchTracking);
                    if ($batchTracking === 'NONE') {
                        $results['table_operations']['item_master']['updated']++;
                    }
                    $results['table_operations']['batch_master']['inserted']++;
                }

                $db->insertRow(
                    'INSERT INTO fifo (ft_location, ft_document, ft_item, ft_qty, ft_blanace, ft_rate, ft_date, ft_type, batch_id) VALUES (?,?,?,?,?,?,?,?,?)',
                    [$locationId, 0, $itemId, $qty, $qty, $rate, $importDate, 1, $batchId]
                );
                $results['table_operations']['fifo']['inserted']++;

                $results['stock_updated']++;
                $results['rows'][] = [
                    'row'     => $rowNum,
                    'sku'     => $sku,
                    'name'    => $existingProduct['item_name'],
                    'qty'     => $qty,
                    'batch'   => $batchNo,
                    'expiry'  => $expiryDate,
                    'status'  => 'updated',
                    'message' => 'Stock added (' . $qty . ' units, Batch: ' . $batchNo . ')',
                ];
            } else {
                // ── NEW PRODUCT → Create then add stock ───────────────────────
                if ($productName === '') {
                    $productName = 'Product ' . $sku;
                }

                $groupId   = resolveGroupId($db, $groupName);
                $typeId    = resolveTypeId($db, $typeName, $groupId);

                $newItemId = createNewProduct(
                    $db, $sku, $productName,
                    0, 0,
                    $uom !== '' ? $uom : 'EA',
                    $sku,
                    $category,
                    $groupId,
                    $typeId,
                    $additionalUom,
                    0
                );
                $results['table_operations']['item_master']['inserted']++;

                $batchId = null;
                if ($batchNo !== null) {
                    $batchId = createOrFindBatch($db, $newItemId, $batchNo, $expiryDate, 'BATCH');
                    $results['table_operations']['batch_master']['inserted']++;
                }

                $db->insertRow(
                    'INSERT INTO fifo (ft_location, ft_document, ft_item, ft_qty, ft_blanace, ft_rate, ft_date, ft_type, batch_id) VALUES (?,?,?,?,?,?,?,?,?)',
                    [$locationId, 0, $newItemId, $qty, $qty, 0, $importDate, 1, $batchId]
                );
                $results['table_operations']['fifo']['inserted']++;

                $results['new_products']++;
                $results['rows'][] = [
                    'row'     => $rowNum,
                    'sku'     => $sku,
                    'name'    => $productName,
                    'qty'     => $qty,
                    'batch'   => $batchNo,
                    'expiry'  => $expiryDate,
                    'status'  => 'created',
                    'message' => 'New product created & stock added (' . $qty . ' units, Batch: ' . $batchNo . ')',
                ];
            }
        } catch (Exception $e) {
            $results['errors'][] = 'Row ' . $rowNum . ' (' . $sku . '): ' . $e->getMessage();
            $results['rows'][] = [
                'row'     => $rowNum,
                'sku'     => $sku,
                'name'    => $productName ?? '',
                'qty'     => $qty ?? 0,
                'batch'   => $batchNo ?? '',
                'expiry'  => $expiryDate ?? '',
                'status'  => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    return $results;
}

// ─── Parse expiry date from Excel (serial number or dd/mm/yyyy string) ────────
function parseExcelDate($sheet, $colIndex, $rowNum, $rawText)
{
    if ($rawText === '') return null;

    $cell = getWorksheetCell($sheet, $colIndex, $rowNum);
    $raw  = $cell->getValue();
    if (is_numeric($raw) && $raw > 1000) {
        try {
            $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($raw);
            return $dt->format('Y-m-d');
        } catch (Exception $e) {}
    }

    foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'm/d/Y'] as $fmt) {
        $dt = DateTime::createFromFormat($fmt, $rawText);
        if ($dt) return $dt->format('Y-m-d');
    }

    return $rawText;
}

// ─── Resolve group_id by name ─────────────────────────────────────────────────
function resolveGroupId(Database $db, $groupName)
{
    if ($groupName === '') return null;
    $row = $db->getRow('SELECT group_id FROM gorup_master WHERE group_name = ? LIMIT 1', [$groupName]);
    return $row ? (int)$row['group_id'] : null;
}

// ─── Resolve type_id by name ──────────────────────────────────────────────────
function resolveTypeId(Database $db, $typeName, $groupId)
{
    if ($typeName === '') return null;
    if ($groupId) {
        $row = $db->getRow('SELECT type_id FROM type_master WHERE type_name = ? AND group_id = ? LIMIT 1', [$typeName, $groupId]);
    } else {
        $row = $db->getRow('SELECT type_id FROM type_master WHERE type_name = ? LIMIT 1', [$typeName]);
    }
    return $row ? (int)$row['type_id'] : null;
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

function createOrFindBatch(Database $db, $productId, $batchNo, $expiryDate, $batchTracking)
{
    // Validate product_id first
    if (!$productId || (int)$productId <= 0) {
        throw new Exception('Cannot create batch: product_id is invalid (' . var_export($productId, true) . '). Ensure product was created successfully.');
    }

    // Get product_id from item_master to ensure it exists
    $product = $db->getRow('SELECT item_id FROM item_master WHERE item_id = ?', [$productId]);
    if (!$product) {
        throw new Exception('Product with ID ' . $productId . ' not found in item_master.');
    }

    $productId = (int)$product['item_id'];

    if ($batchTracking === 'NONE') {
        // Enable batch tracking for imports
        $db->updateRow('UPDATE item_master SET batch_tracking = ? WHERE item_id = ? AND batch_tracking = ?', ['BATCH', $productId, 'NONE']);
    }

    if ($batchNo === null || $batchNo === '') {
        $existingBatch = $db->getRow('SELECT batch_id FROM batch_master WHERE product_id = ? AND batch_no IS NULL', [$productId]);
    } else {
        $existingBatch = $db->getRow('SELECT batch_id FROM batch_master WHERE product_id = ? AND batch_no = ?', [$productId, $batchNo]);
    }
    if ($existingBatch) {
        return (int)$existingBatch['batch_id'];
    }

    $db->insertRow(
        'INSERT INTO batch_master (product_id, batch_no, expiry_date) VALUES (?,?,?)',
        [$productId, $batchNo, $expiryDate]
    );

    if ($batchNo === null || $batchNo === '') {
        $newBatch = $db->getRow('SELECT batch_id FROM batch_master WHERE product_id = ? AND batch_no IS NULL ORDER BY batch_id DESC LIMIT 1', [$productId]);
    } else {
        $newBatch = $db->getRow('SELECT batch_id FROM batch_master WHERE product_id = ? AND batch_no = ? ORDER BY batch_id DESC LIMIT 1', [$productId, $batchNo]);
    }
    return (int)($newBatch['batch_id'] ?? 0);
}

function createNewProduct(Database $db, $sku, $name, $costPrice, $sellPrice, $uom, $barcode, $category, $groupId, $typeId, $additionalUom = '', $excelProductId = 0)
{
    // Generate URL-safe slug
    $slug    = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
    $lastRow = $db->getRow('SELECT MAX(item_id) as max_id FROM item_master');
    $nextId  = ((int)($lastRow['max_id'] ?? 0)) + 1;
    $urlSlug = $slug . '-' . $nextId;

    if ((int)$excelProductId > 0) {
        $db->insertRow(
            'INSERT INTO item_master (
                item_id, item_code, item_name, item_group, item_type, item_category,
                item_discription, item_uom, additional_uoms,
                item_purchase_price, item_normal_selling_price,
                item_barcode, item_active, item_vat, url, item_mode, live, batch_tracking
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                (int)$excelProductId,
                $sku,
                $name,
                $groupId,
                $typeId,
                $category !== '' ? $category : null,
                $name,
                $uom !== '' ? $uom : 'EA',
                $additionalUom !== '' ? $additionalUom : null,
                $costPrice,
                $sellPrice > 0 ? $sellPrice : $costPrice,
                $barcode !== '' ? $barcode : $sku,
                'Y',
                0,
                $urlSlug,
                'goods',
                'yes',
                'BATCH',
            ]
        );
    } else {
        $db->insertRow(
            'INSERT INTO item_master (
                item_code, item_name, item_group, item_type, item_category,
                item_discription, item_uom, additional_uoms,
                item_purchase_price, item_normal_selling_price,
                item_barcode, item_active, item_vat, url, item_mode, live, batch_tracking
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                $sku,
                $name,
                $groupId,
                $typeId,
                $category !== '' ? $category : null,
                $name,
                $uom !== '' ? $uom : 'EA',
                $additionalUom !== '' ? $additionalUom : null,
                $costPrice,
                $sellPrice > 0 ? $sellPrice : $costPrice,
                $barcode !== '' ? $barcode : $sku,
                'Y',
                0,
                $urlSlug,
                'goods',
                'yes',
                'BATCH',
            ]
        );
    }

    $newProduct = $db->getRow('SELECT item_id FROM item_master WHERE item_code = ? ORDER BY item_id DESC LIMIT 1', [$sku]);
    $newProductId = (int)($newProduct['item_id'] ?? 0);
    if ($newProductId <= 0) {
        throw new Exception('Failed to create product for SKU "' . $sku . '". Insert may have failed or SKU not found after insertion.');
    }
    return $newProductId;
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
                                <h3><i class="fa fa-database"></i> Database Tables Affected</h3>
                                <div class="table-responsive">
                                    <table class="table table-bordered mb-0">
                                        <thead style="background:#f8fafc;">
                                            <tr>
                                                <th>Table Name</th>
                                                <th style="text-align:center;">Rows Inserted</th>
                                                <th style="text-align:center;">Rows Updated</th>
                                                <th style="text-align:center;">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $totalInserted = 0;
                                            $totalUpdated = 0;
                                            foreach ($uploadResults['table_operations'] as $table => $ops):
                                                $inserted = (int)$ops['inserted'];
                                                $updated = (int)($ops['updated'] ?? 0);
                                                $total = $inserted + $updated;
                                                $totalInserted += $inserted;
                                                $totalUpdated += $updated;
                                                if ($total > 0):
                                            ?>
                                                <tr>
                                                    <td><strong><?php echo htmlspecialchars($table); ?></strong></td>
                                                    <td style="text-align:center; color:#059669;"><strong><?php echo $inserted; ?></strong></td>
                                                    <td style="text-align:center; color:#1d4ed8;"><strong><?php echo $updated; ?></strong></td>
                                                    <td style="text-align:center; background:#f8fafc;"><strong><?php echo $total; ?></strong></td>
                                                </tr>
                                            <?php endif; endforeach; ?>
                                            <tr style="background:#f0f9ff; font-weight:bold;">
                                                <td>TOTAL</td>
                                                <td style="text-align:center; color:#059669;"><?php echo $totalInserted; ?></td>
                                                <td style="text-align:center; color:#1d4ed8;"><?php echo $totalUpdated; ?></td>
                                                <td style="text-align:center; background:#e0f2fe;"><?php echo ($totalInserted + $totalUpdated); ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div style="margin-top:12px; padding:10px; background:#f0fdf4; border-radius:8px; border-left:4px solid #22c55e; font-size:13px;">
                                    <i class="fa fa-info-circle" style="color:#15803d;"></i>
                                    <strong style="color:#15803d;">Tables Modified:</strong>
                                    <ul style="margin:6px 0 0 20px; padding:0;">
                                        <li><strong>item_master:</strong> Stores product/item information</li>
                                        <li><strong>batch_master:</strong> Tracks batch numbers and expiry dates</li>
                                        <li><strong>fifo:</strong> Records stock transactions</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

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
                                                <th>Batch No</th>
                                                <th>Expiry Date</th>
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
                                                    <td><?php echo htmlspecialchars($row['batch'] ?? '-'); ?></td>
                                                    <td><?php echo htmlspecialchars($row['expiry'] ?? '-'); ?></td>
                                                    <td>
                                                        <?php
                                                        $badgeMap = [
                                                            'created' => 'badge-created',
                                                            'updated' => 'badge-updated',
                                                            'skipped' => 'badge-skipped',
                                                            'error'   => 'badge-error-row',
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
                                    <h3><i class="fa fa-file-excel-o"></i> Upload Stock Report (Standard Template)</h3>
                                    <div class="upload-zone" id="uploadZone" onclick="document.getElementById('stockFile').click();">
                                        <i class="fa fa-cloud-upload"></i>
                                        <h4 style="color:#334155; margin:0 0 4px;">Drop Excel file here or click to browse</h4>
                                        <p>Supported formats: .xlsx, .xls (Max 10MB)</p>
                                        <p id="selectedFileName" style="color:#3b82f6; font-weight:600; display:none;"></p>
                                    </div>
                                    <input type="file" name="stock_file" id="stockFile" accept=".xlsx,.xls" style="display:none;">

                                    <div style="margin-top:20px; background:#f0f9ff; border-radius:10px; padding:16px; border:1px solid #bfdbfe;">
                                        <h5 style="margin:0 0 10px; color:#1e40af; font-size:13px;"><i class="fa fa-info-circle"></i> Required Template Column Order</h5>
                                        <table class="table table-sm mb-0" style="font-size:12px;">
                                            <thead><tr><th>Col</th><th>Header</th><th>Required?</th><th>Notes</th></tr></thead>
                                            <tbody>
                                                <tr><td>A</td><td>Product ID</td><td>Reference only</td><td>Imported product is resolved by Product Code (SKU)</td></tr>
                                                <tr><td>B</td><td>Product Code</td><td><span style="color:red">&#10004; Required</span></td><td>Matched against existing SKUs</td></tr>
                                                <tr><td>C</td><td>Product Name</td><td>Recommended</td><td>Used when creating new product</td></tr>
                                                <tr><td>D</td><td>Group</td><td>Optional</td><td>Must match group name exactly</td></tr>
                                                <tr><td>E</td><td>Type</td><td>Optional</td><td>Must match type name exactly</td></tr>
                                                <tr><td>F</td><td>Category</td><td>Optional</td><td></td></tr>
                                                <tr><td>G</td><td>Stock Qty</td><td><span style="color:red">&#10004; Required</span></td><td>Rows with 0 qty are skipped</td></tr>
                                                <tr><td>H</td><td>Batch No</td><td>Optional</td><td>Auto-generated as IMP-YYYYMMDD if empty</td></tr>
                                                <tr><td>I</td><td>Expiry Date</td><td>Optional</td><td>dd/mm/yyyy format</td></tr>
                                                <tr><td>J</td><td>UOM</td><td>Optional</td><td>Default: EA</td></tr>
                                                <tr><td>K</td><td>Additional UOM</td><td>Optional</td><td>e.g. 10 KG Box</td></tr>
                                            </tbody>
                                        </table>
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
                                        <div class="alert alert-info" style="border-radius:10px; font-size:12px; padding:10px 14px;">
                                            <i class="fa fa-info-circle"></i>
                                            <strong>Group, Type &amp; Batch No</strong> are read directly from the Excel template columns D, E &amp; H.
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
