<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('include/database.php');
include('include/check_login.php');
include('get_url.php');

if (!function_exists('isSuperAdmin') || !isSuperAdmin()) {
    header('Location: access_denied.php');
    exit;
}

$db = new Database();
$messages = [];
$insertSql = '';
$error = null;

// ── Cleanup bad data from previous broken run ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cleanup_bad_data'])) {
    $pdo = $db->getConnection();
    try {
        // Delete fifo rows that have ft_date on 2026-04-09 and ft_document=0 (seed-imported)
        $stmt = $pdo->exec("DELETE FROM fifo WHERE ft_document = 0 AND ft_type = 1 AND ft_date LIKE '2026-04-09%'");
        $messages[] = 'Deleted ' . (int)$stmt . ' fifo rows from bad import.';

        // Delete batch_master rows that were created with wrong product_id=124
        $stmt = $pdo->exec("DELETE FROM batch_master WHERE product_id = 124");
        $messages[] = 'Deleted ' . (int)$stmt . ' bad batch_master rows (product_id=124).';

        // Delete duplicate item_master rows inserted by seed (item_id > the original max)
        // Only delete items with item_code starting with M- that were added by seed
        $stmt = $pdo->exec("DELETE t1 FROM item_master t1 INNER JOIN item_master t2 ON t1.item_code = t2.item_code AND t1.item_id > t2.item_id WHERE t1.item_code LIKE 'M-%'");
        $messages[] = 'Deleted ' . (int)$stmt . ' duplicate item_master rows.';
    } catch (Exception $e) {
        $error = 'Cleanup error: ' . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_queries'])) {
    if (!isset($_FILES['seed_file']) || $_FILES['seed_file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please upload a valid Excel file.';
    } else {
        $filePath = $_FILES['seed_file']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['seed_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls', 'csv'], true)) {
            $error = 'Unsupported file type. Use .xlsx, .xls or .csv.';
        } else {
            $fileData = loadSpreadsheet($filePath, $ext);
            if (isset($fileData['error'])) {
                $error = $fileData['error'];
            } else {
                $rows = $fileData['rows'];
                $headers = $fileData['headers'];
                $mapping = mapExcelColumns($headers);

                if (!isset($mapping['sku'])) {
                    $error = 'Unable to detect SKU column. Ensure there is a header like SKU, Item Code or Product Code.';
                } elseif (!isset($mapping['qty'])) {
                    $error = 'Unable to detect quantity column. Ensure there is a header like Qty, Quantity, Stock, Available, On Hand, Balance or Current Stock. Uploaded headers: ' . implode(', ', array_filter(array_map('trim', $headers), function ($header) {
                        return $header !== '';
                    }));
                } else {
                    $skuHeader = trim((string) ($headers[$mapping['sku']] ?? 'SKU'));
                    $qtyHeader = trim((string) ($headers[$mapping['qty']] ?? 'Quantity'));
                    $messages[] = 'Detected SKU column: ' . $skuHeader;
                    $messages[] = 'Detected quantity column: ' . $qtyHeader;

                    $currentTables = getCurrentTableColumns($db);
                    $batchExists = array_key_exists('batch_master', $currentTables);
                    $pdo = $db->getConnection();

                    $sqlStatements = [];
                    $supplierLookup = [];
                    $productLookup = [];
                    $batchLookup = [];
                    $successCount = 0;
                    $failCount = 0;
                    $failMessages = [];
                    $skippedRows = 0;
                    $skipDetails = [];
                    $infoDetails = [];
                    $skipReasonCounts = [
                        'empty_identity' => 0,
                        'product_insert_failed' => 0,
                    ];

                    foreach ($rows as $rowIndex => $row) {
                        $excelRow = $rowIndex + 2; // +2 because row 1 is header, array is 0-based
                        $sku = trim(mappedVal($row, $mapping, 'sku'));
                        $rowPreview = summarizeRowData($headers, $row, [$mapping['sku'] ?? null]);

                        $qty = (float) mappedVal($row, $mapping, 'qty', 0);
                        $supplierName = trim(mappedVal($row, $mapping, 'supplier_name'));
                        $supplierCode = trim(mappedVal($row, $mapping, 'supplier_code'));
                        $supplierEmail = trim(mappedVal($row, $mapping, 'supplier_email'));
                        $supplierPhone = trim(mappedVal($row, $mapping, 'supplier_phone'));
                        $supplierMobile = trim(mappedVal($row, $mapping, 'supplier_mobile'));
                        $supplierAddress = trim(mappedVal($row, $mapping, 'supplier_address'));
                        $productName = trim(mappedVal($row, $mapping, 'product_name'));
                        $costPrice = formatDecimal(mappedVal($row, $mapping, 'cost_price', 0));
                        $sellPrice = formatDecimal(mappedVal($row, $mapping, 'sell_price', 0));
                        $batchNo = trim(mappedVal($row, $mapping, 'batch_no'));
                        $expiry = trim(mappedVal($row, $mapping, 'expiry_date'));
                        $barcode = trim(mappedVal($row, $mapping, 'barcode'));
                        $uom = trim(mappedVal($row, $mapping, 'uom', 'EA'));
                        $category = trim(mappedVal($row, $mapping, 'category'));

                        // If both SKU and product name are empty, skip
                        if ($sku === '' && $productName === '') {
                            $skippedRows++;
                            $skipReasonCounts['empty_identity']++;
                            $skipDetails[] = 'Row ' . $excelRow . ': "' . $skuHeader . '" value is empty and product name is empty. Row data: ' . $rowPreview;
                            continue;
                        }

                        // Auto-generate SKU from product name if missing
                        if ($sku === '') {
                            $sku = 'AUTO-' . strtoupper(preg_replace('/[^A-Za-z0-9]/', '', substr($productName, 0, 20))) . '-' . $excelRow;
                            $infoDetails[] = 'Row ' . $excelRow . ': "' . $skuHeader . '" value is empty. Row data: ' . $rowPreview . '. Auto-generated SKU: ' . $sku;
                        }

                        // Convert Excel serial date to Y-m-d
                        $expiry = convertExcelDate($expiry);

                        if ($productName === '') {
                            $productName = 'Product ' . $sku;
                        }

                        // ── Supplier: find or insert ──
                        $supplierId = null;
                        if ($supplierName !== '' || $supplierCode !== '') {
                            $supplierKey = strtolower($supplierCode !== '' ? $supplierCode : $supplierName);
                            if (isset($supplierLookup[$supplierKey])) {
                                $supplierId = $supplierLookup[$supplierKey];
                            } else {
                                $supplierRow = findSupplier($db, $supplierCode, $supplierName, $currentTables['supplier'] ?? []);
                                if ($supplierRow) {
                                    $supplierId = $supplierRow['supplier_id'];
                                } else {
                                    // Insert new supplier and execute immediately
                                    $supplierSql = buildSupplierInsert($currentTables['supplier'] ?? [], null, $supplierCode, $supplierName, $supplierEmail, $supplierPhone, $supplierMobile, $supplierAddress);
                                    $sqlStatements[] = $supplierSql;
                                    $supplierId = executeAndGetId($pdo, $supplierSql, $successCount, $failCount, $failMessages);
                                }
                                $supplierLookup[$supplierKey] = $supplierId;
                            }
                        }

                        // ── Product: find or insert ──
                        $productId = null;
                        if (isset($productLookup[$sku])) {
                            $productId = $productLookup[$sku];
                        } else {
                            $productRow = findProduct($db, $sku);
                            if ($productRow) {
                                $productId = $productRow['item_id'];
                            } else {
                                // Insert new product and execute immediately
                                $productSql = buildItemInsert($currentTables['item_master'] ?? [], $sku, $productName, $barcode, $costPrice, $sellPrice, $uom, $category);
                                $sqlStatements[] = $productSql;
                                $productInsertError = null;
                                $productId = executeAndGetId($pdo, $productSql, $successCount, $failCount, $failMessages, $productInsertError);
                            }
                            $productLookup[$sku] = $productId;
                        }

                        if (!$productId) {
                            $skippedRows++;
                            $skipReasonCounts['product_insert_failed']++;
                            $skipMessage = 'Row ' . $excelRow . ' (SKU: ' . $sku . '): Product insert failed';
                            if (!empty($productInsertError)) {
                                $skipMessage .= '. DB error: ' . $productInsertError;
                            }
                            $skipDetails[] = $skipMessage;
                            continue;
                        }

                        // ── Batch: find or insert ──
                        $batchId = null;
                        if ($batchExists && $batchNo !== '') {
                            $batchKey = $productId . '||' . $batchNo;
                            if (isset($batchLookup[$batchKey])) {
                                $batchId = $batchLookup[$batchKey];
                            } else {
                                // Check if batch already exists in DB
                                $existingBatch = $db->getRow('SELECT batch_id FROM batch_master WHERE product_id = ? AND batch_no = ? LIMIT 1', [$productId, $batchNo]);
                                if ($existingBatch) {
                                    $batchId = $existingBatch['batch_id'];
                                } else {
                                    $batchSql = buildBatchInsert(null, $productId, $batchNo, $expiry);
                                    $sqlStatements[] = $batchSql;
                                    $batchId = executeAndGetId($pdo, $batchSql, $successCount, $failCount, $failMessages);
                                }
                                $batchLookup[$batchKey] = $batchId;
                            }
                        }

                        // ── FIFO stock insert ──
                        if ($qty > 0) {
                            $fifoSql = buildFifoInsert($currentTables['fifo'] ?? [], null, $qty, $costPrice, $productId, $batchId);
                            $sqlStatements[] = $fifoSql;
                            executeAndGetId($pdo, $fifoSql, $successCount, $failCount, $failMessages);
                        }
                    }

                    if (empty($sqlStatements)) {
                        $messages[] = 'No valid rows were found in the file.';
                    } else {
                        $insertSql = implode("\n", $sqlStatements);
                        $savePath = __DIR__ . '/seeds/stock_seed_queries.sql';
                        file_put_contents($savePath, $insertSql);
                        $messages[] = 'Generated ' . count($sqlStatements) . ' SQL statements.';
                        $messages[] = 'Executed: ' . $successCount . ' succeeded, ' . $failCount . ' failed.';
                        if ($skippedRows > 0) {
                            $reasonParts = [];
                            if ($skipReasonCounts['empty_identity'] > 0) {
                                $reasonParts[] = 'empty item code and empty product name: ' . $skipReasonCounts['empty_identity'];
                            }
                            if ($skipReasonCounts['product_insert_failed'] > 0) {
                                $reasonParts[] = 'product insert failed: ' . $skipReasonCounts['product_insert_failed'];
                            }
                            $messages[] = 'Skipped ' . $skippedRows . ' rows. Reasons: ' . implode(', ', $reasonParts) . '.';
                        }
                        foreach ($failMessages as $fm) {
                            $messages[] = 'SQL Error: ' . $fm;
                        }
                        foreach ($skipDetails as $sd) {
                            $messages[] = 'Skip: ' . $sd;
                        }
                        foreach ($infoDetails as $info) {
                            $messages[] = 'Info: ' . $info;
                        }
                    }
                }
            }
        }
    }
}

function mappedVal(array $row, array $mapping, string $key, $default = '')
{
    if (!isset($mapping[$key])) {
        return $default;
    }
    return $row[$mapping[$key]] ?? $default;
}

function executeAndGetId($pdo, $sql, &$successCount, &$failCount, &$failMessages, &$lastError = null)
{
    try {
        $pdo->exec($sql);
        $successCount++;
        $lastError = null;
        $id = $pdo->lastInsertId();
        return $id ? (int) $id : null;
    } catch (Exception $e) {
        $failCount++;
        $lastError = $e->getMessage();
        if (count($failMessages) < 20) {
            $failMessages[] = $e->getMessage();
        }
        return null;
    }
}

function convertExcelDate($value)
{
    if ($value === '' || $value === null) {
        return '';
    }
    // Already a date string like 2026-12-31
    if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
        return substr($value, 0, 10);
    }
    // Excel serial number (e.g. 46477)
    if (is_numeric($value) && (int) $value > 30000 && (int) $value < 100000) {
        $unix = ((int) $value - 25569) * 86400;
        return date('Y-m-d', $unix);
    }
    // Try common date formats
    foreach (['d/m/Y', 'm/d/Y', 'd-m-Y', 'd.m.Y'] as $fmt) {
        $dt = DateTime::createFromFormat($fmt, $value);
        if ($dt !== false) {
            return $dt->format('Y-m-d');
        }
    }
    return $value;
}

function formatDecimal($value)
{
    return number_format((float) str_replace([',', ' '], ['', ''], $value), 2, '.', '');
}

function loadSpreadsheet($filePath, $ext)
{
    if ($ext === 'csv') {
        $rows = [];
        if (($handle = fopen($filePath, 'r')) === false) {
            return ['error' => 'Unable to open CSV file.'];
        }
        while (($data = fgetcsv($handle, 0, ',')) !== false) {
            $rows[] = $data;
        }
        fclose($handle);
        if (empty($rows)) {
            return ['error' => 'CSV file is empty.'];
        }
        return ['headers' => $rows[0], 'rows' => array_slice($rows, 1)];
    }

    $autoload = __DIR__ . '/vendor/autoload.php';
    if (!file_exists($autoload)) {
        return ['error' => 'PhpSpreadsheet not installed. Run composer require phpoffice/phpspreadsheet'];
    }
    require_once $autoload;

    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
    } catch (Exception $e) {
        return ['error' => 'Unable to load Excel file: ' . $e->getMessage()];
    }

    $sheet = $spreadsheet->getActiveSheet();
    $rows = [];
    foreach ($sheet->getRowIterator() as $row) {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);
        $record = [];
        foreach ($cellIterator as $cell) {
            $record[] = trim((string) $cell->getValue());
        }
        $rows[] = $record;
    }
    if (empty($rows)) {
        return ['error' => 'Uploaded spreadsheet is empty.'];
    }
    return ['headers' => $rows[0], 'rows' => array_slice($rows, 1)];
}

function mapExcelColumns(array $headers)
{
    $map = [];
    $scores = [];

    foreach ($headers as $index => $header) {
        $key = normalizeHeader($header);
        if ($key === '') {
            continue;
        }

        if ($key === 'sku') {
            assignColumnMapping($map, $scores, 'sku', $index, 200);
        } elseif (strpos($key, 'item code') !== false || strpos($key, 'product code') !== false || strpos($key, 'stock code') !== false || strpos($key, 'material code') !== false) {
            assignColumnMapping($map, $scores, 'sku', $index, 180);
        } elseif (strpos($key, 'item no') !== false || strpos($key, 'product no') !== false) {
            assignColumnMapping($map, $scores, 'sku', $index, 170);
        } elseif ($key === 'code') {
            assignColumnMapping($map, $scores, 'sku', $index, 50);
        }

        if ($key === 'name' || strpos($key, 'item name') !== false || strpos($key, 'product name') !== false || strpos($key, 'item description') !== false || strpos($key, 'product description') !== false || $key === 'description') {
            assignColumnMapping($map, $scores, 'product_name', $index, 100);
        }
        if (isQuantityHeader($key)) {
            assignColumnMapping($map, $scores, 'qty', $index, 100);
        }
        if ($key === 'cost' || strpos($key, 'purchase price') !== false || strpos($key, 'buy price') !== false || strpos($key, 'unit cost') !== false) {
            assignColumnMapping($map, $scores, 'cost_price', $index, 100);
        }
        if ($key === 'sell' || strpos($key, 'selling price') !== false || strpos($key, 'sale price') !== false || strpos($key, 'retail') !== false) {
            assignColumnMapping($map, $scores, 'sell_price', $index, 100);
        }
        if ($key === 'batch' || strpos($key, 'batch no') !== false || strpos($key, 'batch number') !== false || strpos($key, 'lot no') !== false || strpos($key, 'lot number') !== false) {
            assignColumnMapping($map, $scores, 'batch_no', $index, 100);
        }
        if (strpos($key, 'expiry') !== false || strpos($key, 'expire date') !== false) {
            assignColumnMapping($map, $scores, 'expiry_date', $index, 100);
        }
        if ($key === 'barcode' || $key === 'ean' || $key === 'upc' || $key === 'gtin') {
            assignColumnMapping($map, $scores, 'barcode', $index, 100);
        }
        if ($key === 'uom' || $key === 'unit' || strpos($key, 'unit of measure') !== false) {
            assignColumnMapping($map, $scores, 'uom', $index, 100);
        }
        if ($key === 'category' || $key === 'cat' || $key === 'group' || $key === 'type') {
            assignColumnMapping($map, $scores, 'category', $index, 100);
        }
        if (strpos($key, 'supplier name') !== false || strpos($key, 'vendor name') !== false) {
            assignColumnMapping($map, $scores, 'supplier_name', $index, 150);
        }
        if (strpos($key, 'supplier code') !== false || strpos($key, 'vendor code') !== false) {
            assignColumnMapping($map, $scores, 'supplier_code', $index, 150);
        }
        if (strpos($key, 'supplier email') !== false || strpos($key, 'vendor email') !== false || $key === 'email') {
            assignColumnMapping($map, $scores, 'supplier_email', $index, 100);
        }
        if (strpos($key, 'supplier phone') !== false || strpos($key, 'vendor phone') !== false || $key === 'phone' || strpos($key, 'contact no') !== false) {
            assignColumnMapping($map, $scores, 'supplier_phone', $index, 100);
        }
        if (strpos($key, 'supplier mobile') !== false || strpos($key, 'vendor mobile') !== false || $key === 'mobile') {
            assignColumnMapping($map, $scores, 'supplier_mobile', $index, 100);
        }
        if (strpos($key, 'supplier address') !== false || strpos($key, 'vendor address') !== false || $key === 'address') {
            assignColumnMapping($map, $scores, 'supplier_address', $index, 100);
        }
    }
    return $map;
}

function normalizeHeader($header)
{
    $header = strtolower(trim((string) $header));
    $header = preg_replace('/[^a-z0-9]+/', ' ', $header);
    $header = preg_replace('/\s+/', ' ', $header);
    return trim($header);
}

function summarizeRowData(array $headers, array $row, array $excludeIndexes = [], $limit = 6)
{
    $parts = [];

    foreach ($row as $index => $value) {
        if (in_array($index, $excludeIndexes, true)) {
            continue;
        }

        $value = trim((string) $value);
        if ($value === '') {
            continue;
        }

        $label = trim((string) ($headers[$index] ?? ''));
        if ($label === '') {
            $label = 'Column ' . ($index + 1);
        }

        $parts[] = $label . ': ' . $value;
        if (count($parts) >= $limit) {
            break;
        }
    }

    return empty($parts) ? 'No other values in this row' : implode(' | ', $parts);
}

function isQuantityHeader($key)
{
    $positiveMatches = [
        'qty',
        'quantity',
        'stock',
        'available',
        'on hand',
        'in hand',
        'balance',
        'current stock',
        'stock balance',
        'stock qty',
        'available qty',
        'available stock',
        'closing stock',
        'physical stock',
        'remaining stock',
    ];

    $negativeMatches = [
        'code',
        'price',
        'cost',
        'sell',
        'rate',
        'name',
        'description',
        'batch',
        'expiry',
        'expire',
        'supplier',
        'vendor',
        'email',
        'phone',
        'mobile',
        'address',
        'barcode',
        'ean',
        'upc',
        'gtin',
        'uom',
        'unit of measure',
    ];

    foreach ($negativeMatches as $negativeMatch) {
        if (strpos($key, $negativeMatch) !== false) {
            return false;
        }
    }

    foreach ($positiveMatches as $positiveMatch) {
        if ($key === $positiveMatch || strpos($key, $positiveMatch) !== false) {
            return true;
        }
    }

    return false;
}

function assignColumnMapping(array &$map, array &$scores, $key, $index, $score)
{
    if (!isset($scores[$key]) || $score > $scores[$key]) {
        $map[$key] = $index;
        $scores[$key] = $score;
    }
}

function getCurrentTableColumns(Database $db)
{
    $tables = ['item_master', 'supplier', 'batch_master', 'fifo'];
    $result = [];
    foreach ($tables as $table) {
        try {
            $cols = $db->getRows('SHOW COLUMNS FROM ' . $table);
            if ($cols) {
                $result[$table] = array_column($cols, 'Field');
            }
        } catch (Exception $e) {
            // ignore missing tables
        }
    }
    return $result;
}

function getAutoIncrementId(Database $db, $table, $idColumn)
{
    try {
        $row = $db->getRow('SELECT MAX(' . $idColumn . ') AS max_id FROM ' . $table);
        return ((int) ($row['max_id'] ?? 0)) + 1;
    } catch (Exception $e) {
        return time();
    }
}

function findSupplier(Database $db, $code, $name, array $supplierColumns)
{
    if ($code !== '' && in_array('supplier_code', $supplierColumns, true)) {
        $row = $db->getRow('SELECT supplier_id FROM supplier WHERE supplier_code = ? LIMIT 1', [$code]);
        if ($row) {
            return $row;
        }
    }
    if ($name !== '' && in_array('supplier_name', $supplierColumns, true)) {
        return $db->getRow('SELECT supplier_id FROM supplier WHERE supplier_name = ? LIMIT 1', [$name]);
    }
    return null;
}

function findProduct(Database $db, $sku)
{
    return $db->getRow('SELECT item_id, item_name FROM item_master WHERE item_code = ? LIMIT 1', [$sku]);
}

function buildSupplierInsert(array $columns, $supplierId, $code, $name, $email, $phone, $mobile, $address)
{
    $fields = [];
    $values = [];

    foreach ($columns as $column) {
        switch ($column) {
            case 'supplier_id':
                if ($supplierId !== null) {
                    $fields[] = $column;
                    $values[] = $supplierId;
                }
                break;
            case 'supplier_code':
                $fields[] = $column;
                $values[] = $code !== '' ? $code : 'SUP' . $supplierId;
                break;
            case 'supplier_name':
                $fields[] = $column;
                $values[] = $name !== '' ? $name : 'Supplier ' . $supplierId;
                break;
            case 'supplier_email':
                $fields[] = $column;
                $values[] = $email !== '' ? $email : null;
                break;
            case 'supplier_contact_person':
                $fields[] = $column;
                $values[] = $name;
                break;
            case 'supplier_contact_no':
                $fields[] = $column;
                $values[] = $phone !== '' ? $phone : null;
                break;
            case 'supplier_mobile':
                $fields[] = $column;
                $values[] = $mobile !== '' ? $mobile : null;
                break;
            case 'supplier_address':
                $fields[] = $column;
                $values[] = $address !== '' ? $address : null;
                break;
            case 'supplier_note':
                $fields[] = $column;
                $values[] = null;
                break;
            case 'supplier_outstanding_blance':
                $fields[] = $column;
                $values[] = 0;
                break;
            case 'supplier_cradit_limite':
                $fields[] = $column;
                $values[] = 0;
                break;
        }
    }

    return buildInsertSql('supplier', $fields, $values);
}

function buildItemInsert(array $columns, $sku, $name, $barcode, $costPrice, $sellPrice, $uom, $category)
{
    $fields = [];
    $values = [];
    $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($name));
    $slug = trim($slug, '-');
    if ($slug === '') {
        $slug = 'product-' . strtolower($sku);
    }

    foreach ($columns as $column) {
        switch ($column) {
            case 'item_code':
                $fields[] = $column;
                $values[] = $sku;
                break;
            case 'item_name':
                $fields[] = $column;
                $values[] = $name;
                break;
            case 'item_group':
            case 'item_type':
            case 'item_category':
                $fields[] = $column;
                $values[] = null;
                break;
            case 'item_discription':
                $fields[] = $column;
                $values[] = $name;
                break;
            case 'item_uom':
                $fields[] = $column;
                $values[] = $uom !== '' ? $uom : 1;
                break;
            case 'item_purchase_price':
                $fields[] = $column;
                $values[] = $costPrice;
                break;
            case 'item_min_selling_price':
                $fields[] = $column;
                $values[] = $sellPrice;
                break;
            case 'item_normal_selling_price':
                $fields[] = $column;
                $values[] = $sellPrice;
                break;
            case 'item_barcode':
                $fields[] = $column;
                $values[] = $barcode !== '' ? $barcode : $sku;
                break;
            case 'item_image':
                $fields[] = $column;
                $values[] = '';
                break;
            case 'item_discount':
                $fields[] = $column;
                $values[] = 0;
                break;
            case 'item_active':
                $fields[] = $column;
                $values[] = 'Y';
                break;
            case 'item_warranty':
                $fields[] = $column;
                $values[] = '1';
                break;
            case 'is_hamper':
                $fields[] = $column;
                $values[] = 0;
                break;
            case 'item_has_sirial':
                $fields[] = $column;
                $values[] = 'N';
                break;
            case 'item_vat':
                $fields[] = $column;
                $values[] = 'N';
                break;
            case 'item_dispay_home':
                $fields[] = $column;
                $values[] = 1;
                break;
            case 'item_product_of_day':
                $fields[] = $column;
                $values[] = 0;
                break;
            case 'item_cod':
                $fields[] = $column;
                $values[] = 'enable';
                break;
            case 'item_mode':
                $fields[] = $column;
                $values[] = 'Normal';
                break;
            case 'view_count':
                $fields[] = $column;
                $values[] = 0;
                break;
            case 'url':
                $fields[] = $column;
                $values[] = $slug;
                break;
            case 'item_weight':
                $fields[] = $column;
                $values[] = 0;
                break;
            case 'low_stock_qty':
                $fields[] = $column;
                $values[] = 5;
                break;
            case 'immediate_pickups':
                $fields[] = $column;
                $values[] = 'No';
                break;
        }
    }

    return buildInsertSql('item_master', $fields, $values);
}

function buildBatchInsert($batchId, $productId, $batchNo, $expiryDate)
{
    $fields = [];
    $values = [];
    if ($batchId !== null) {
        $fields[] = 'batch_id';
        $values[] = $batchId;
    }
    $fields[] = 'product_id';
    $values[] = $productId;
    $fields[] = 'batch_no';
    $values[] = $batchNo;
    $fields[] = 'expiry_date';
    $values[] = $expiryDate !== '' ? $expiryDate : null;
    return buildInsertSql('batch_master', $fields, $values);
}

function buildFifoInsert(array $columns, $fifoId, $qty, $rate, $productId, $batchId)
{
    $fields = [];
    $values = [];
    $date = date('Y-m-d H:i:s');
    foreach ($columns as $column) {
        switch ($column) {
            case 'ft_id':
                if ($fifoId !== null) {
                    $fields[] = $column;
                    $values[] = $fifoId;
                }
                break;
            case 'ft_location':
                $fields[] = $column;
                $values[] = 1;
                break;
            case 'ft_document':
                $fields[] = $column;
                $values[] = 0;
                break;
            case 'ft_item':
                $fields[] = $column;
                $values[] = $productId;
                break;
            case 'ft_qty':
                $fields[] = $column;
                $values[] = $qty;
                break;
            case 'ft_blanace':
                $fields[] = $column;
                $values[] = $qty;
                break;
            case 'ft_rate':
                $fields[] = $column;
                $values[] = $rate;
                break;
            case 'ft_date':
                $fields[] = $column;
                $values[] = $date;
                break;
            case 'ft_type':
                $fields[] = $column;
                $values[] = 1;
                break;
            case 'batch_id':
                $fields[] = $column;
                $values[] = $batchId;
                break;
        }
    }
    return buildInsertSql('fifo', $fields, $values);
}

function buildInsertSql($table, array $fields, array $values)
{
    $escapedFields = array_map(function ($field) {
        return '`' . str_replace('`', '``', $field) . '`';
    }, $fields);

    $escapedValues = array_map(function ($value) {
        if ($value === null) {
            return 'NULL';
        }
        if (is_numeric($value) && !is_string($value)) {
            return $value;
        }
        return "'" . str_replace("'", "''", (string) $value) . "'";
    }, $values);

    return 'INSERT INTO `' . $table . '` (' . implode(', ', $escapedFields) . ') VALUES (' . implode(', ', $escapedValues) . ');';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Seed SQL Generator</title>
    <?php include('common/head.php'); ?>
    <style>
        .seed-page { padding: 24px; }
        .seed-card { background: #fff; border-radius: 12px; box-shadow: 0 4px 18px rgba(15,23,42,.08); border: 1px solid #e2e8f0; padding: 24px; margin-bottom: 20px; }
        .seed-card h3 { margin-top: 0; }
        .seed-card textarea { width: 100%; min-height: 360px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 14px; font-family: Menlo, Monaco, Consolas, 'Courier New', monospace; font-size: 13px; line-height: 1.5; }
        .seed-card .form-group { margin-bottom: 18px; }
        .seed-card label { display: block; margin-bottom: 6px; font-weight: 600; }
        .seed-card input[type=file] { display: block; }
        .seed-card .btn-submit { background: #1d4ed8; color: #fff; padding: 10px 18px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .seed-card .alert { padding: 14px 18px; border-radius: 10px; margin-bottom: 18px; }
        .seed-card .alert-success { background: #ecfdf5; color: #166534; }
        .seed-card .alert-danger { background: #fef2f2; color: #991b1b; }
        .seed-card .alert-warning { background: #fffbeb; color: #92400e; }
        .seed-card .alert-info { background: #eff6ff; color: #1d4ed8; }
        .seed-card .seed-note { color: #475569; font-size: 14px; margin-top: 10px; }
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
            <div class="page-content seed-page">
                <div class="seed-card">
                    <h3><i class="fa fa-database"></i> Stock Seed SQL Generator</h3>
                    <p class="seed-note">Upload the customer stock report. Missing suppliers and products will be inserted automatically. Stocks will be added with batch tracking.</p>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <?php foreach ($messages as $message): ?>
                        <?php
                            $alertClass = 'alert-success';
                            if (strpos($message, 'SQL Error:') === 0) {
                                $alertClass = 'alert-danger';
                            } elseif (strpos($message, 'Skipped ') === 0 || strpos($message, 'Skip:') === 0) {
                                $alertClass = 'alert-warning';
                            } elseif (strpos($message, 'Info:') === 0) {
                                $alertClass = 'alert-info';
                            } elseif (strpos($message, 'Executed:') === 0 && preg_match('/\b[1-9][0-9]* failed\b/', $message)) {
                                $alertClass = 'alert-danger';
                            }
                        ?>
                        <div class="alert <?php echo $alertClass; ?>"><?php echo htmlspecialchars($message); ?></div>
                    <?php endforeach; ?>

                    <!-- Cleanup section -->
                    <div style="background:#fef3c7;border:1px solid #f59e0b;border-radius:8px;padding:14px;margin-bottom:18px;">
                        <strong>Step 1:</strong> If you ran a previous import that failed, clean up bad data first.
                        <form method="post" style="margin-top:8px;">
                            <button type="submit" name="cleanup_bad_data" class="btn-submit" style="background:#dc2626;" onclick="return confirm('This will delete bad seed data from the previous run. Continue?')">Clean Up Bad Data</button>
                        </form>
                    </div>

                    <!-- Import section -->
                    <div style="background:#ecfdf5;border:1px solid #10b981;border-radius:8px;padding:14px;margin-bottom:18px;">
                        <strong>Step 2:</strong> Upload the Excel file to import stocks.
                        <form method="post" enctype="multipart/form-data" style="margin-top:8px;">
                            <div class="form-group">
                                <label for="seed_file">Upload Excel or CSV</label>
                                <input type="file" name="seed_file" id="seed_file" accept=".xlsx,.xls,.csv" required>
                            </div>
                            <button type="submit" name="generate_queries" class="btn-submit">Import Stocks</button>
                        </form>
                    </div>
                </div>

                <?php if ($insertSql !== ''): ?>
                    <div class="seed-card">
                        <h3><i class="fa fa-file-code-o"></i> Generated SQL</h3>
                        <p class="seed-note">The SQL file was saved to <strong>seeds/stock_seed_queries.sql</strong>.</p>
                        <textarea readonly><?php echo htmlspecialchars($insertSql); ?></textarea>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php include('common/footer.php'); ?>
</body>
</html>
