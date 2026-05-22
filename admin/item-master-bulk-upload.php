<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('include/database.php');
include('include/check_login.php');
include('get_url.php');

requirePermission('settings.permissions');

date_default_timezone_set('Asia/Colombo');

$db = new Database();

function escapeHtml($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function normalizeHeaderLabel($header)
{
    $header = strtolower(trim((string) $header));
    $header = preg_replace('/[^a-z0-9]+/i', '_', $header);
    return trim((string) $header, '_');
}

function normalizeImportedNumber($value)
{
    $value = trim((string) $value);
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

    return is_numeric($value) ? (float) $value : 0.0;
}

function columnLettersToIndex($letters)
{
    $letters = strtoupper(trim((string) $letters));
    if ($letters === '') {
        return 0;
    }

    $index = 0;
    $length = strlen($letters);
    for ($position = 0; $position < $length; $position++) {
        $charCode = ord($letters[$position]);
        if ($charCode < 65 || $charCode > 90) {
            return 0;
        }
        $index = ($index * 26) + ($charCode - 64);
    }

    return $index;
}

function extractFirstSheetRowsFromXlsx($filePath, &$error)
{
    $error = '';
    if (!class_exists('ZipArchive')) {
        $error = 'ZipArchive extension is required for .xlsx imports.';
        return [];
    }

    $zip = new ZipArchive();
    if ($zip->open($filePath) !== true) {
        $error = 'Unable to open uploaded .xlsx file.';
        return [];
    }

    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    if ($sheetXml === false) {
        $zip->close();
        $error = 'Unable to find first worksheet in .xlsx file.';
        return [];
    }

    $sharedStrings = [];
    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedXml !== false) {
        $shared = @simplexml_load_string($sharedXml);
        if ($shared !== false && isset($shared->si)) {
            foreach ($shared->si as $sharedItem) {
                if (isset($sharedItem->t)) {
                    $sharedStrings[] = (string) $sharedItem->t;
                } else {
                    $text = '';
                    if (isset($sharedItem->r)) {
                        foreach ($sharedItem->r as $run) {
                            $text .= (string) $run->t;
                        }
                    }
                    $sharedStrings[] = $text;
                }
            }
        }
    }

    $sheet = @simplexml_load_string($sheetXml);
    $zip->close();
    if ($sheet === false || !isset($sheet->sheetData)) {
        $error = 'Unable to parse worksheet data.';
        return [];
    }

    $rows = [];
    foreach ($sheet->sheetData->row as $rowNode) {
        $rowNumber = (int) ($rowNode['r'] ?? 0);
        if ($rowNumber <= 0) {
            continue;
        }

        foreach ($rowNode->c as $cellNode) {
            $cellRef = (string) ($cellNode['r'] ?? '');
            if ($cellRef === '' || !preg_match('/^([A-Z]+)\d+$/i', $cellRef, $matches)) {
                continue;
            }

            $columnIndex = columnLettersToIndex($matches[1]);
            if ($columnIndex <= 0) {
                continue;
            }

            $cellType = (string) ($cellNode['t'] ?? '');
            $value = '';

            if ($cellType === 's') {
                $sharedIndex = (int) ($cellNode->v ?? 0);
                $value = $sharedStrings[$sharedIndex] ?? '';
            } elseif ($cellType === 'inlineStr') {
                $value = (string) ($cellNode->is->t ?? '');
                if ($value === '' && isset($cellNode->is->r)) {
                    foreach ($cellNode->is->r as $runNode) {
                        $value .= (string) $runNode->t;
                    }
                }
            } else {
                $value = (string) ($cellNode->v ?? '');
            }

            $rows[$rowNumber][$columnIndex] = $value;
        }
    }

    return $rows;
}

function extractRowsFromCsv($filePath, &$error)
{
    $error = '';
    $rows = [];
    $handle = @fopen($filePath, 'r');
    if ($handle === false) {
        $error = 'Unable to open uploaded CSV file.';
        return [];
    }

    // Auto-detect delimiter: read first line to check for tabs vs commas
    $firstLine = fgets($handle);
    if ($firstLine === false) {
        fclose($handle);
        return [];
    }
    $firstLine = ltrim($firstLine, "\xEF\xBB\xBF"); // strip BOM for detection
    $tabCount   = substr_count($firstLine, "\t");
    $commaCount = substr_count($firstLine, ',');
    $delimiter  = ($tabCount > $commaCount) ? "\t" : ',';
    rewind($handle);

    $rowNumber = 1;
    $firstRow = true;
    while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
        foreach ($data as $columnOffset => $value) {
            $value = (string) $value;
            // Strip UTF-8 BOM from the very first cell
            if ($firstRow && $columnOffset === 0) {
                $value = ltrim($value, "\xEF\xBB\xBF");
            }
            $rows[$rowNumber][$columnOffset + 1] = $value;
        }
        $firstRow = false;
        $rowNumber++;
    }

    fclose($handle);
    return $rows;
}

function resolveCellValue(array $rows, $columnIndex, $rowNumber)
{
    return $rows[(int) $rowNumber][(int) $columnIndex] ?? '';
}

function getItemMasterColumns(Database $db)
{
    $columns = [];
    $rows = $db->getRows('SHOW COLUMNS FROM item_master');
    if (!is_array($rows)) {
        return $columns;
    }
    foreach ($rows as $row) {
        $fieldName = trim((string) ($row['Field'] ?? ''));
        if ($fieldName !== '') {
            $columns[$fieldName] = true;
        }
    }
    return $columns;
}

function mapImportColumns(array $headers)
{
    $aliases = [
        'item_code' => ['item_code', 'code', 'sku', 'product_code', 'item_no'],
        'item_name' => ['item_name', 'name', 'product_name', 'description'],
        'item_group' => ['item_group', 'group_id', 'group'],
        'item_type' => ['item_type', 'type_id', 'type'],
        'item_category' => ['item_category', 'category_id', 'category'],
        'item_purchase_price' => ['item_purchase_price', 'purchase_price', 'cost', 'cost_price'],
        'item_normal_selling_price' => ['item_normal_selling_price', 'selling_price', 'normal_selling_price', 'sale_price', 'price'],
        'retail_price' => ['retail_price', 'rrp'],
        'gst_vat_code' => ['gst_vat_code', 'tax_code', 'vat_code'],
        'item_active' => ['item_active', 'active', 'status'],
        'item_uom' => ['item_uom', 'uom_id'],
        'unit_of_measure' => ['unit_of_measure', 'uom', 'uom_name'],
        'item_business_unit' => ['item_business_unit', 'business_unit', 'business_unit_id'],
        'item_barcode' => ['item_barcode', 'barcode'],
        'item_weight' => ['item_weight', 'weight'],
        'low_stock_qty' => ['low_stock_qty', 'low_stock', 'minimum_stock'],
        'pack_size' => ['pack_size'],
        'acc_posting_grp_code' => ['acc_posting_grp_code', 'posting_group', 'posting_grp'],
        'item_mode' => ['item_mode', 'mode'],
        'immediate_pickups' => ['immediate_pickups', 'immediate_pickup'],
        'live' => ['live'],
        'wholesale_price' => ['wholesale_price'],
        'batch_tracking' => ['batch_tracking'],
        'allow_in_sales' => ['allow_in_sales'],
        'allow_in_grn' => ['allow_in_grn'],
        'is_raw_material' => ['is_raw_material'],
        'pack_type' => ['pack_type'],
        'additional_uoms' => ['additional_uoms'],
    ];

    $map = [];
    foreach ($aliases as $field => $candidateHeaders) {
        foreach ($headers as $columnIndex => $headerValue) {
            if (in_array($headerValue, $candidateHeaders, true)) {
                $map[$field] = $columnIndex;
                break;
            }
        }
    }

    return $map;
}

function parseYesNoValue($value, $defaultValue = null)
{
    $normalized = strtoupper(trim((string) $value));
    if ($normalized === '') {
        return $defaultValue;
    }

    if (in_array($normalized, ['Y', 'YES', '1', 'TRUE'], true)) {
        return 'Y';
    }
    if (in_array($normalized, ['N', 'NO', '0', 'FALSE'], true)) {
        return 'N';
    }

    return $defaultValue;
}

function parseEnabledFlag($value, $defaultValue = null)
{
    $normalized = strtoupper(trim((string) $value));
    if ($normalized === '') {
        return $defaultValue;
    }

    if (in_array($normalized, ['1', 'Y', 'YES', 'ENABLE', 'ENABLED', 'TRUE'], true)) {
        return 1;
    }
    if (in_array($normalized, ['0', 'N', 'NO', 'DISABLE', 'DISABLED', 'FALSE'], true)) {
        return 0;
    }

    return $defaultValue;
}

function parseBatchTracking($value)
{
    $normalized = strtoupper(trim((string) $value));
    if (in_array($normalized, ['NONE', 'BATCH', 'SERIAL'], true)) {
        return $normalized;
    }
    return null;
}

function parseActiveFlag($value)
{
    $normalized = strtoupper(trim((string) $value));
    if ($normalized === '') {
        return null;
    }

    if (in_array($normalized, ['Y', 'YES', '1', 'ACTIVE', 'TRUE'], true)) {
        return 'Y';
    }
    if (in_array($normalized, ['N', 'NO', '0', 'INACTIVE', 'FALSE'], true)) {
        return 'N';
    }

    return null;
}

function resolveUomId(Database $db, $columns, $itemUomRaw, $uomNameRaw)
{
    if (!isset($columns['item_uom'])) {
        return null;
    }

    $itemUomRaw = trim((string) $itemUomRaw);
    if ($itemUomRaw !== '' && ctype_digit($itemUomRaw)) {
        return (int) $itemUomRaw;
    }

    $uomNameRaw = trim((string) $uomNameRaw);
    if ($uomNameRaw !== '') {
        $row = $db->getRow('SELECT uom_id FROM item_uom WHERE LOWER(TRIM(uom_name)) = LOWER(TRIM(?)) LIMIT 1', [$uomNameRaw]);
        if ($row && isset($row['uom_id'])) {
            return (int) $row['uom_id'];
        }
    }

    return null;
}

function buildSafeUrlSlug($itemName, $itemCode)
{
    $base = trim((string) $itemName);
    if ($base === '') {
        $base = trim((string) $itemCode);
    }
    $base = preg_replace('/[^a-zA-Z0-9]+/', '-', $base);
    $base = trim((string) $base, '-');
    if ($base === '') {
        $base = 'product';
    }

    return strtolower($base) . '-' . date('YmdHis') . '-' . mt_rand(1000, 9999);
}

function createItemMasterRecord(Database $db, array $columns, array $payload)
{
    $insertColumns = [];
    $insertValues = [];

    $insertColumns[] = 'item_code';
    $insertValues[] = $payload['item_code'];

    $insertColumns[] = 'item_name';
    $insertValues[] = $payload['item_name'];

    if (isset($columns['item_group']) && $payload['item_group'] !== null) {
        $insertColumns[] = 'item_group';
        $insertValues[] = $payload['item_group'];
    }
    if (isset($columns['item_type']) && $payload['item_type'] !== null) {
        $insertColumns[] = 'item_type';
        $insertValues[] = $payload['item_type'];
    }
    if (isset($columns['item_category']) && $payload['item_category'] !== null) {
        $insertColumns[] = 'item_category';
        $insertValues[] = $payload['item_category'];
    }
    if (isset($columns['item_business_unit']) && $payload['item_business_unit'] !== null) {
        $insertColumns[] = 'item_business_unit';
        $insertValues[] = $payload['item_business_unit'];
    }
    if (isset($columns['item_purchase_price']) && $payload['item_purchase_price'] !== null) {
        $insertColumns[] = 'item_purchase_price';
        $insertValues[] = $payload['item_purchase_price'];
    }
    if (isset($columns['item_normal_selling_price']) && $payload['item_normal_selling_price'] !== null) {
        $insertColumns[] = 'item_normal_selling_price';
        $insertValues[] = $payload['item_normal_selling_price'];
    }
    if (isset($columns['retail_price']) && $payload['retail_price'] !== null) {
        $insertColumns[] = 'retail_price';
        $insertValues[] = $payload['retail_price'];
    }
    if (isset($columns['gst_vat_code'])) {
        $insertColumns[] = 'gst_vat_code';
        $insertValues[] = $payload['gst_vat_code'];
    }
    if (isset($columns['item_vat'])) {
        $insertColumns[] = 'item_vat';
        $insertValues[] = $payload['item_vat'];
    }
    if (isset($columns['item_active'])) {
        $insertColumns[] = 'item_active';
        $insertValues[] = $payload['item_active'] === null ? 'Y' : $payload['item_active'];
    }
    if (isset($columns['item_warranty'])) {
        $insertColumns[] = 'item_warranty';
        $insertValues[] = $payload['item_warranty'];
    }
    if (isset($columns['item_has_sirial'])) {
        $insertColumns[] = 'item_has_sirial';
        $insertValues[] = $payload['item_has_sirial'];
    }
    if (isset($columns['item_barcode']) && $payload['item_barcode'] !== '') {
        $insertColumns[] = 'item_barcode';
        $insertValues[] = $payload['item_barcode'];
    }
    if (isset($columns['item_uom']) && $payload['item_uom'] !== null) {
        $insertColumns[] = 'item_uom';
        $insertValues[] = $payload['item_uom'];
    }
    if (isset($columns['unit_of_measure']) && $payload['unit_of_measure'] !== '') {
        $insertColumns[] = 'unit_of_measure';
        $insertValues[] = $payload['unit_of_measure'];
    }
    if (isset($columns['item_discription'])) {
        $insertColumns[] = 'item_discription';
        $insertValues[] = $payload['item_name'];
    }
    if (isset($columns['url'])) {
        $insertColumns[] = 'url';
        $insertValues[] = buildSafeUrlSlug($payload['item_name'], $payload['item_code']);
    }
    if (isset($columns['item_mode'])) {
        $insertColumns[] = 'item_mode';
        $insertValues[] = $payload['item_mode'];
    }
    if (isset($columns['live'])) {
        $insertColumns[] = 'live';
        $insertValues[] = $payload['live'];
    }
    if (isset($columns['batch_tracking'])) {
        $insertColumns[] = 'batch_tracking';
        $insertValues[] = $payload['batch_tracking'];
    }
    if (isset($columns['item_cod'])) {
        $insertColumns[] = 'item_cod';
        $insertValues[] = $payload['item_cod'];
    }
    if (isset($columns['item_weight'])) {
        $insertColumns[] = 'item_weight';
        $insertValues[] = $payload['item_weight'];
    }
    if (isset($columns['low_stock_qty'])) {
        $insertColumns[] = 'low_stock_qty';
        $insertValues[] = $payload['low_stock_qty'];
    }
    if (isset($columns['immediate_pickups'])) {
        $insertColumns[] = 'immediate_pickups';
        $insertValues[] = $payload['immediate_pickups'];
    }
    if (isset($columns['pack_size']) && $payload['pack_size'] !== null) {
        $insertColumns[] = 'pack_size';
        $insertValues[] = $payload['pack_size'];
    }
    if (isset($columns['acc_posting_grp_code']) && $payload['acc_posting_grp_code'] !== '') {
        $insertColumns[] = 'acc_posting_grp_code';
        $insertValues[] = $payload['acc_posting_grp_code'];
    }
    if (isset($columns['wholesale_price']) && $payload['wholesale_price'] !== null) {
        $insertColumns[] = 'wholesale_price';
        $insertValues[] = $payload['wholesale_price'];
    }
    if (isset($columns['pack_type']) && $payload['pack_type'] !== '') {
        $insertColumns[] = 'pack_type';
        $insertValues[] = $payload['pack_type'];
    }
    if (isset($columns['allow_in_sales']) && $payload['allow_in_sales'] !== null) {
        $insertColumns[] = 'allow_in_sales';
        $insertValues[] = $payload['allow_in_sales'];
    }
    if (isset($columns['allow_in_grn']) && $payload['allow_in_grn'] !== null) {
        $insertColumns[] = 'allow_in_grn';
        $insertValues[] = $payload['allow_in_grn'];
    }
    if (isset($columns['is_raw_material']) && $payload['is_raw_material'] !== null) {
        $insertColumns[] = 'is_raw_material';
        $insertValues[] = $payload['is_raw_material'];
    }
    if (isset($columns['additional_uoms']) && $payload['additional_uoms'] !== '') {
        $insertColumns[] = 'additional_uoms';
        $insertValues[] = $payload['additional_uoms'];
    }
    if (isset($columns['avail_monday'])) {
        $insertColumns[] = 'avail_monday';
        $insertValues[] = 1;
    }
    if (isset($columns['avail_tuesday'])) {
        $insertColumns[] = 'avail_tuesday';
        $insertValues[] = 1;
    }
    if (isset($columns['avail_wednesday'])) {
        $insertColumns[] = 'avail_wednesday';
        $insertValues[] = 1;
    }
    if (isset($columns['avail_thursday'])) {
        $insertColumns[] = 'avail_thursday';
        $insertValues[] = 1;
    }
    if (isset($columns['avail_friday'])) {
        $insertColumns[] = 'avail_friday';
        $insertValues[] = 1;
    }
    if (isset($columns['avail_saturday'])) {
        $insertColumns[] = 'avail_saturday';
        $insertValues[] = 1;
    }
    if (isset($columns['avail_sunday'])) {
        $insertColumns[] = 'avail_sunday';
        $insertValues[] = 1;
    }

    $escapedColumns = array_map(function ($columnName) {
        return '`' . $columnName . '`';
    }, $insertColumns);

    $placeholders = implode(',', array_fill(0, count($insertColumns), '?'));
    $sql = 'INSERT INTO item_master (' . implode(',', $escapedColumns) . ') VALUES (' . $placeholders . ')';

    $db->insertRow($sql, $insertValues);
}

function updateItemMasterRecord(Database $db, array $columns, $itemId, array $payload)
{
    $setClauses = [];
    $params = [];

    if ($payload['item_name'] !== '') {
        $setClauses[] = '`item_name` = ?';
        $params[] = $payload['item_name'];
    }

    if (isset($columns['item_group']) && $payload['item_group'] !== null) {
        $setClauses[] = '`item_group` = ?';
        $params[] = $payload['item_group'];
    }
    if (isset($columns['item_type']) && $payload['item_type'] !== null) {
        $setClauses[] = '`item_type` = ?';
        $params[] = $payload['item_type'];
    }
    if (isset($columns['item_category']) && $payload['item_category'] !== null) {
        $setClauses[] = '`item_category` = ?';
        $params[] = $payload['item_category'];
    }
    if (isset($columns['item_business_unit']) && $payload['item_business_unit'] !== null) {
        $setClauses[] = '`item_business_unit` = ?';
        $params[] = $payload['item_business_unit'];
    }
    if (isset($columns['item_purchase_price']) && $payload['item_purchase_price'] !== null) {
        $setClauses[] = '`item_purchase_price` = ?';
        $params[] = $payload['item_purchase_price'];
    }
    if (isset($columns['item_normal_selling_price']) && $payload['item_normal_selling_price'] !== null) {
        $setClauses[] = '`item_normal_selling_price` = ?';
        $params[] = $payload['item_normal_selling_price'];
    }
    if (isset($columns['retail_price']) && $payload['retail_price'] !== null) {
        $setClauses[] = '`retail_price` = ?';
        $params[] = $payload['retail_price'];
    }
    if (isset($columns['gst_vat_code'])) {
        $setClauses[] = '`gst_vat_code` = ?';
        $params[] = $payload['gst_vat_code'];
    }
    if (isset($columns['item_vat'])) {
        $setClauses[] = '`item_vat` = ?';
        $params[] = $payload['item_vat'];
    }
    if (isset($columns['item_active']) && $payload['item_active'] !== null) {
        $setClauses[] = '`item_active` = ?';
        $params[] = $payload['item_active'];
    }
    if (isset($columns['item_barcode']) && $payload['item_barcode'] !== '') {
        $setClauses[] = '`item_barcode` = ?';
        $params[] = $payload['item_barcode'];
    }
    if (isset($columns['item_uom']) && $payload['item_uom'] !== null) {
        $setClauses[] = '`item_uom` = ?';
        $params[] = $payload['item_uom'];
    }
    if (isset($columns['unit_of_measure']) && $payload['unit_of_measure'] !== '') {
        $setClauses[] = '`unit_of_measure` = ?';
        $params[] = $payload['unit_of_measure'];
    }
    if (isset($columns['item_weight']) && $payload['item_weight'] !== null) {
        $setClauses[] = '`item_weight` = ?';
        $params[] = $payload['item_weight'];
    }
    if (isset($columns['low_stock_qty']) && $payload['low_stock_qty'] !== null) {
        $setClauses[] = '`low_stock_qty` = ?';
        $params[] = $payload['low_stock_qty'];
    }
    if (isset($columns['pack_size']) && $payload['pack_size'] !== null) {
        $setClauses[] = '`pack_size` = ?';
        $params[] = $payload['pack_size'];
    }
    if (isset($columns['acc_posting_grp_code']) && $payload['acc_posting_grp_code'] !== '') {
        $setClauses[] = '`acc_posting_grp_code` = ?';
        $params[] = $payload['acc_posting_grp_code'];
    }
    if (isset($columns['item_mode']) && $payload['item_mode'] !== '') {
        $setClauses[] = '`item_mode` = ?';
        $params[] = $payload['item_mode'];
    }
    if (isset($columns['immediate_pickups']) && $payload['immediate_pickups'] !== '') {
        $setClauses[] = '`immediate_pickups` = ?';
        $params[] = $payload['immediate_pickups'];
    }
    if (isset($columns['live']) && $payload['live'] !== '') {
        $setClauses[] = '`live` = ?';
        $params[] = $payload['live'];
    }
    if (isset($columns['wholesale_price']) && $payload['wholesale_price'] !== null) {
        $setClauses[] = '`wholesale_price` = ?';
        $params[] = $payload['wholesale_price'];
    }
    if (isset($columns['pack_type']) && $payload['pack_type'] !== '') {
        $setClauses[] = '`pack_type` = ?';
        $params[] = $payload['pack_type'];
    }
    if (isset($columns['batch_tracking']) && $payload['batch_tracking'] !== null) {
        $setClauses[] = '`batch_tracking` = ?';
        $params[] = $payload['batch_tracking'];
    }
    if (isset($columns['allow_in_sales']) && $payload['allow_in_sales'] !== null) {
        $setClauses[] = '`allow_in_sales` = ?';
        $params[] = $payload['allow_in_sales'];
    }
    if (isset($columns['allow_in_grn']) && $payload['allow_in_grn'] !== null) {
        $setClauses[] = '`allow_in_grn` = ?';
        $params[] = $payload['allow_in_grn'];
    }
    if (isset($columns['is_raw_material']) && $payload['is_raw_material'] !== null) {
        $setClauses[] = '`is_raw_material` = ?';
        $params[] = $payload['is_raw_material'];
    }
    if (isset($columns['additional_uoms']) && $payload['additional_uoms'] !== '') {
        $setClauses[] = '`additional_uoms` = ?';
        $params[] = $payload['additional_uoms'];
    }

    if (empty($setClauses)) {
        return;
    }

    $params[] = (int) $itemId;
    $db->updateRow('UPDATE item_master SET ' . implode(', ', $setClauses) . ' WHERE item_id = ?', $params);
}

function processItemMasterImport(Database $db, $filePath, $extension)
{
    $rows = [];
    $parseError = '';

    if ($extension === 'xlsx') {
        $rows = extractFirstSheetRowsFromXlsx($filePath, $parseError);
    } elseif ($extension === 'csv') {
        $rows = extractRowsFromCsv($filePath, $parseError);
    } else {
        return ['error' => 'Unsupported file format. Please upload .xlsx or .csv'];
    }

    if ($parseError !== '') {
        return ['error' => $parseError];
    }

    if (empty($rows)) {
        return ['error' => 'The file has no readable rows.'];
    }

    $highestRow = (int) max(array_keys($rows));

    if ($highestRow < 2) {
        return ['error' => 'The file has no data rows.'];
    }
    if ($highestRow > 2001) {
        return ['error' => 'Maximum 2000 data rows allowed.'];
    }

    $headers = [];
    if (isset($rows[1]) && is_array($rows[1])) {
        foreach ($rows[1] as $columnIndex => $headerValue) {
            $headers[(int) $columnIndex] = normalizeHeaderLabel($headerValue);
        }
    }

    $columnMap = mapImportColumns($headers);
    if (!isset($columnMap['item_code'])) {
        return ['error' => 'Required column not found: item_code'];
    }

    $itemMasterColumns = getItemMasterColumns($db);

    $validTaxCodes = [];
    try {
        $taxCodeRows = $db->getRows('SELECT code FROM product_vat_master');
        foreach ($taxCodeRows as $taxCodeRow) {
            $code = trim((string) ($taxCodeRow['code'] ?? ''));
            if ($code !== '') {
                $validTaxCodes[$code] = true;
            }
        }
    } catch (Exception $exception) {
    }

    $results = [
        'total_rows' => 0,
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => [],
        'rows' => [],
    ];

    for ($rowNumber = 2; $rowNumber <= $highestRow; $rowNumber++) {
        $itemCode = trim((string) resolveCellValue($rows, $columnMap['item_code'], $rowNumber));
        if ($itemCode === '') {
            continue;
        }

        $results['total_rows']++;

        $itemName = isset($columnMap['item_name']) ? trim((string) resolveCellValue($rows, $columnMap['item_name'], $rowNumber)) : '';
        $itemGroup = isset($columnMap['item_group']) ? trim((string) resolveCellValue($rows, $columnMap['item_group'], $rowNumber)) : '';
        $itemType = isset($columnMap['item_type']) ? trim((string) resolveCellValue($rows, $columnMap['item_type'], $rowNumber)) : '';
        $itemCategory = isset($columnMap['item_category']) ? trim((string) resolveCellValue($rows, $columnMap['item_category'], $rowNumber)) : '';
        $itemBusinessUnit = isset($columnMap['item_business_unit']) ? trim((string) resolveCellValue($rows, $columnMap['item_business_unit'], $rowNumber)) : '';
        $purchasePrice = isset($columnMap['item_purchase_price']) ? normalizeImportedNumber(resolveCellValue($rows, $columnMap['item_purchase_price'], $rowNumber)) : null;
        $sellingPrice = isset($columnMap['item_normal_selling_price']) ? normalizeImportedNumber(resolveCellValue($rows, $columnMap['item_normal_selling_price'], $rowNumber)) : null;
        $retailPrice = isset($columnMap['retail_price']) ? normalizeImportedNumber(resolveCellValue($rows, $columnMap['retail_price'], $rowNumber)) : null;
        $wholesalePrice = isset($columnMap['wholesale_price']) ? normalizeImportedNumber(resolveCellValue($rows, $columnMap['wholesale_price'], $rowNumber)) : null;
        $taxCode = isset($columnMap['gst_vat_code']) ? trim((string) resolveCellValue($rows, $columnMap['gst_vat_code'], $rowNumber)) : '';
        $activeFlag = isset($columnMap['item_active']) ? parseActiveFlag(resolveCellValue($rows, $columnMap['item_active'], $rowNumber)) : null;
        $itemBarcode = isset($columnMap['item_barcode']) ? trim((string) resolveCellValue($rows, $columnMap['item_barcode'], $rowNumber)) : '';
        $itemWeight = isset($columnMap['item_weight']) ? normalizeImportedNumber(resolveCellValue($rows, $columnMap['item_weight'], $rowNumber)) : 0;
        $lowStockQty = isset($columnMap['low_stock_qty']) ? (int) normalizeImportedNumber(resolveCellValue($rows, $columnMap['low_stock_qty'], $rowNumber)) : 5;
        $packSize = isset($columnMap['pack_size']) ? normalizeImportedNumber(resolveCellValue($rows, $columnMap['pack_size'], $rowNumber)) : null;
        $postingGroup = isset($columnMap['acc_posting_grp_code']) ? trim((string) resolveCellValue($rows, $columnMap['acc_posting_grp_code'], $rowNumber)) : '';
        $itemModeRaw = isset($columnMap['item_mode']) ? trim((string) resolveCellValue($rows, $columnMap['item_mode'], $rowNumber)) : '';
        $immediatePickupRaw = isset($columnMap['immediate_pickups']) ? trim((string) resolveCellValue($rows, $columnMap['immediate_pickups'], $rowNumber)) : '';
        $liveRaw = isset($columnMap['live']) ? trim((string) resolveCellValue($rows, $columnMap['live'], $rowNumber)) : '';
        $batchTrackingRaw = isset($columnMap['batch_tracking']) ? trim((string) resolveCellValue($rows, $columnMap['batch_tracking'], $rowNumber)) : '';
        $allowInSales = isset($columnMap['allow_in_sales']) ? parseEnabledFlag(resolveCellValue($rows, $columnMap['allow_in_sales'], $rowNumber), null) : null;
        $allowInGrn = isset($columnMap['allow_in_grn']) ? parseEnabledFlag(resolveCellValue($rows, $columnMap['allow_in_grn'], $rowNumber), null) : null;
        $isRawMaterial = isset($columnMap['is_raw_material']) ? parseEnabledFlag(resolveCellValue($rows, $columnMap['is_raw_material'], $rowNumber), null) : null;
        $packType = isset($columnMap['pack_type']) ? trim((string) resolveCellValue($rows, $columnMap['pack_type'], $rowNumber)) : '';
        $additionalUoms = isset($columnMap['additional_uoms']) ? trim((string) resolveCellValue($rows, $columnMap['additional_uoms'], $rowNumber)) : '';
        $itemUomRaw = isset($columnMap['item_uom']) ? resolveCellValue($rows, $columnMap['item_uom'], $rowNumber) : '';
        $uomNameRaw = isset($columnMap['unit_of_measure']) ? trim((string) resolveCellValue($rows, $columnMap['unit_of_measure'], $rowNumber)) : '';

        $itemMode = in_array($itemModeRaw, ['Normal', 'Offline', 'OutofStock'], true) ? $itemModeRaw : 'Normal';
        $immediatePickup = in_array($immediatePickupRaw, ['Yes', 'No'], true) ? $immediatePickupRaw : 'No';
        $live = strtolower($liveRaw) === 'no' ? 'no' : 'yes';
        $batchTracking = parseBatchTracking($batchTrackingRaw);
        if ($batchTracking === null) {
            $batchTracking = 'NONE';
        }

        if ($taxCode !== '' && !empty($validTaxCodes) && !isset($validTaxCodes[$taxCode])) {
            $results['skipped']++;
            $results['rows'][] = [
                'row' => $rowNumber,
                'item_code' => $itemCode,
                'item_name' => $itemName,
                'status' => 'skipped',
                'message' => 'Invalid gst_vat_code: ' . $taxCode,
            ];
            continue;
        }

        $payload = [
            'item_code' => $itemCode,
            'item_name' => $itemName,
            'item_group' => ctype_digit($itemGroup) ? (int) $itemGroup : null,
            'item_type' => ctype_digit($itemType) ? (int) $itemType : null,
            'item_category' => ctype_digit($itemCategory) ? (int) $itemCategory : null,
            'item_business_unit' => ctype_digit($itemBusinessUnit) ? (int) $itemBusinessUnit : null,
            'item_purchase_price' => $purchasePrice,
            'item_normal_selling_price' => $sellingPrice,
            'retail_price' => $retailPrice,
            'wholesale_price' => $wholesalePrice,
            'gst_vat_code' => $taxCode !== '' ? $taxCode : null,
            'item_vat' => $taxCode !== '' ? 'Y' : 'N',
            'item_active' => $activeFlag,
            'item_warranty' => 1,
            'item_has_sirial' => 'N',
            'item_barcode' => $itemBarcode,
            'item_weight' => $itemWeight,
            'low_stock_qty' => $lowStockQty > 0 ? $lowStockQty : 5,
            'pack_size' => $packSize,
            'acc_posting_grp_code' => $postingGroup,
            'item_mode' => $itemMode,
            'item_cod' => 'enable',
            'immediate_pickups' => $immediatePickup,
            'live' => $live,
            'batch_tracking' => $batchTracking,
            'allow_in_sales' => $allowInSales,
            'allow_in_grn' => $allowInGrn,
            'is_raw_material' => $isRawMaterial,
            'pack_type' => $packType,
            'additional_uoms' => $additionalUoms,
            'item_uom' => resolveUomId($db, $itemMasterColumns, $itemUomRaw, $uomNameRaw),
            'unit_of_measure' => $uomNameRaw,
        ];

        try {
            $existing = $db->getRow('SELECT item_id, item_name FROM item_master WHERE item_code = ? ORDER BY item_id DESC LIMIT 1', [$itemCode]);

            if ($existing) {
                if ($payload['item_name'] === '') {
                    $payload['item_name'] = trim((string) ($existing['item_name'] ?? ''));
                }

                updateItemMasterRecord($db, $itemMasterColumns, (int) $existing['item_id'], $payload);

                $results['updated']++;
                $results['rows'][] = [
                    'row' => $rowNumber,
                    'item_code' => $itemCode,
                    'item_name' => $payload['item_name'],
                    'status' => 'updated',
                    'message' => 'Updated existing item',
                ];
            } else {
                if ($payload['item_name'] === '') {
                    $results['skipped']++;
                    $results['rows'][] = [
                        'row' => $rowNumber,
                        'item_code' => $itemCode,
                        'item_name' => '',
                        'status' => 'skipped',
                        'message' => 'item_name required for new item',
                    ];
                    continue;
                }

                createItemMasterRecord($db, $itemMasterColumns, $payload);

                $results['created']++;
                $results['rows'][] = [
                    'row' => $rowNumber,
                    'item_code' => $itemCode,
                    'item_name' => $payload['item_name'],
                    'status' => 'created',
                    'message' => 'Created new item',
                ];
            }
        } catch (Exception $exception) {
            $errorText = 'Row ' . $rowNumber . ' (' . $itemCode . '): ' . $exception->getMessage();
            $results['errors'][] = $errorText;
            $results['rows'][] = [
                'row' => $rowNumber,
                'item_code' => $itemCode,
                'item_name' => $itemName,
                'status' => 'error',
                'message' => $exception->getMessage(),
            ];
        }
    }

    return $results;
}

$uploadResults = null;
$uploadError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_item_master'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['item_master_import_csrf'] ?? '')) {
        $uploadError = 'Invalid form submission. Please try again.';
    } elseif (!isset($_FILES['item_file']) || $_FILES['item_file']['error'] !== UPLOAD_ERR_OK) {
        $uploadError = 'Please select a valid Excel file to upload.';
    } else {
        $extension = strtolower(pathinfo($_FILES['item_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['xlsx', 'csv'], true)) {
            $uploadError = 'Only .xlsx and .csv files are supported on this server.';
        } elseif ($_FILES['item_file']['size'] > 10 * 1024 * 1024) {
            $uploadError = 'File size exceeds 10MB limit.';
        } else {
            $uploadResults = processItemMasterImport($db, $_FILES['item_file']['tmp_name'], $extension);
        }
    }
}

$_SESSION['item_master_import_csrf'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Item Master Bulk Upload</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
    <style>
        .result-created { color: #27ae60; font-weight: bold; }
        .result-updated { color: #3498db; font-weight: bold; }
        .result-skipped { color: #f39c12; font-weight: bold; }
        .result-error { color: #e74c3c; font-weight: bold; }
        .stat-box { padding: 15px; border-radius: 6px; text-align: center; color: #fff; margin-bottom: 10px; }
        .stat-box h3 { margin: 0; font-size: 26px; }
        .stat-box p { margin: 5px 0 0; font-size: 13px; }
    </style>
</head>
<body class="page-header-fixed">
    <?php include('common/manubar.php'); ?>
    <div class="page-container">
        <div class="page-sidebar-wrapper">
            <?php include('common/sidebar.php'); ?>
        </div>
        <div class="page-content-wrapper">
            <div class="page-content">
                <div class="page-bar">
                    <ul class="page-breadcrumb">
                        <li><a href="index.php">Home</a><i class="fa fa-circle"></i></li>
                        <li><span>Settings</span><i class="fa fa-circle"></i></li>
                        <li><span>Item Master Bulk Upload</span></li>
                    </ul>
                </div>

                <h1 class="page-title">Item Master Bulk Upload</h1>

                <?php if ($uploadError): ?>
                    <div class="alert alert-danger"><i class="fa fa-warning"></i> <?php echo escapeHtml($uploadError); ?></div>
                <?php endif; ?>

                <?php if ($uploadResults && isset($uploadResults['error'])): ?>
                    <div class="alert alert-danger"><i class="fa fa-warning"></i> <?php echo escapeHtml($uploadResults['error']); ?></div>
                <?php endif; ?>

                <?php if ($uploadResults && !isset($uploadResults['error'])): ?>
                    <div class="row">
                        <div class="col-md-2 col-sm-4">
                            <div class="stat-box" style="background:#34495e;">
                                <h3><?php echo (int)$uploadResults['total_rows']; ?></h3>
                                <p>Total Rows</p>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4">
                            <div class="stat-box" style="background:#27ae60;">
                                <h3><?php echo (int)$uploadResults['created']; ?></h3>
                                <p>Created</p>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4">
                            <div class="stat-box" style="background:#3498db;">
                                <h3><?php echo (int)$uploadResults['updated']; ?></h3>
                                <p>Updated</p>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4">
                            <div class="stat-box" style="background:#f39c12;">
                                <h3><?php echo (int)$uploadResults['skipped']; ?></h3>
                                <p>Skipped</p>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4">
                            <div class="stat-box" style="background:#e74c3c;">
                                <h3><?php echo count($uploadResults['errors']); ?></h3>
                                <p>Errors</p>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($uploadResults['errors'])): ?>
                        <div class="alert alert-danger">
                            <strong>Errors:</strong>
                            <ul style="margin:5px 0 0;">
                                <?php foreach ($uploadResults['errors'] as $error): ?>
                                    <li><?php echo escapeHtml($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="portlet light bordered">
                        <div class="portlet-title">
                            <div class="caption font-green"><i class="fa fa-list"></i> Import Details</div>
                        </div>
                        <div class="portlet-body table-responsive">
                            <table class="table table-striped table-bordered table-condensed">
                                <thead>
                                    <tr><th>Row</th><th>Item Code</th><th>Item Name</th><th>Status</th><th>Message</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($uploadResults['rows'] as $row): ?>
                                    <tr>
                                        <td><?php echo (int)$row['row']; ?></td>
                                        <td><?php echo escapeHtml($row['item_code']); ?></td>
                                        <td><?php echo escapeHtml($row['item_name']); ?></td>
                                        <td>
                                            <?php if ($row['status'] === 'created'): ?>
                                                <span class="result-created"><i class="fa fa-check"></i> Created</span>
                                            <?php elseif ($row['status'] === 'updated'): ?>
                                                <span class="result-updated"><i class="fa fa-refresh"></i> Updated</span>
                                            <?php elseif ($row['status'] === 'skipped'): ?>
                                                <span class="result-skipped"><i class="fa fa-exclamation-triangle"></i> Skipped</span>
                                            <?php else: ?>
                                                <span class="result-error"><i class="fa fa-times"></i> Error</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo escapeHtml($row['message']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <a href="item-master-bulk-upload.php" class="btn btn-primary"><i class="fa fa-upload"></i> Upload Another File</a>
                <?php else: ?>
                    <div class="row">
                        <div class="col-md-8 col-md-offset-2">
                            <div class="portlet light bordered">
                                <div class="portlet-title">
                                    <div class="caption font-green"><i class="fa fa-cloud-upload"></i> <span class="caption-subject bold uppercase">Upload Excel File</span></div>
                                </div>
                                <div class="portlet-body">
                                    <div class="alert alert-info">
                                        <strong><i class="fa fa-info-circle"></i> Instructions:</strong>
                                        <ul style="margin:5px 0 0; padding-left:20px;">
                                            <li>Download the <a href="item-master-bulk-sample.php"><strong>sample template</strong></a>.</li>
                                            <li>Required column: <strong>item_code</strong>.</li>
                                            <li>For new items, <strong>item_name</strong> is required.</li>
                                            <li>Use valid <strong>gst_vat_code</strong> values if tax code is provided.</li>
                                            <li>Max <strong>2000 rows</strong> and file size <strong>10MB</strong>.</li>
                                            <li>Supported file formats: <strong>.xlsx</strong>, <strong>.csv</strong>.</li>
                                        </ul>
                                    </div>

                                    <form method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="csrf_token" value="<?php echo escapeHtml($_SESSION['item_master_import_csrf']); ?>">
                                        <input type="hidden" name="import_item_master" value="1">

                                        <div class="form-group">
                                            <label for="item_file"><strong>Select Excel/CSV File</strong></label>
                                            <input type="file" name="item_file" id="item_file" class="form-control" accept=".xlsx,.csv" required>
                                        </div>

                                        <hr>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <a href="item-master-bulk-sample.php" class="btn btn-success btn-block"><i class="fa fa-download"></i> Download Sample Template</a>
                                            </div>
                                            <div class="col-md-6">
                                                <button type="submit" class="btn btn-primary btn-block btn-lg"><i class="fa fa-upload"></i> Upload & Import</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include('common/footer.php'); ?>
    <script src="assets/global/plugins/jquery.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
    <script src="assets/global/scripts/app.min.js" type="text/javascript"></script>
    <script src="assets/layouts/layout/scripts/layout.min.js" type="text/javascript"></script>
</body>
</html>
