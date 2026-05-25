<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('include/database.php');
include('include/check_login.php');
include('get_url.php');

if (!isSuperAdmin()) {
    header('Location: access_denied.php');
    exit;
}

date_default_timezone_set('Asia/Colombo');

$db = new Database();

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

function normalizeHeaderLabel($header)
{
    $header = strtolower(trim((string) $header));
    $header = preg_replace('/[^a-z0-9]+/i', '_', $header);
    return trim($header, '_');
}

function getWorksheetCell($sheet, $columnIndex, $rowNum)
{
    $cellReference = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex((int) $columnIndex) . $rowNum;
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
    return trim((string) resolveWorksheetCellValue(getWorksheetCell($sheet, $columnIndex, $rowNum)));
}

function getWorksheetCellNumber($sheet, $columnIndex, $rowNum)
{
    $cell = getWorksheetCell($sheet, $columnIndex, $rowNum);
    $value = resolveWorksheetCellValue($cell);

    if (is_numeric($value)) {
        return (float) $value;
    }

    $stringValue = trim((string) $value);
    if ($stringValue === '') {
        $stringValue = trim((string) $cell->getFormattedValue());
    }

    return normalizeImportedNumber($stringValue);
}

function createProductUrlSlug(Database $db, $name)
{
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', (string) $name));
    $slug = trim($slug, '-');
    if ($slug === '') {
        $slug = 'product';
    }

    $lastRow = $db->getRow('SELECT MAX(item_id) AS max_id FROM item_master');
    $nextId = ((int) ($lastRow['max_id'] ?? 0)) + 1;

    return $slug . '-' . $nextId;
}

function processProductImport(Database $db, $filePath)
{
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
        return ['error' => 'PhpSpreadsheet library not found. Please run composer require phpoffice/phpspreadsheet'];
    }

    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
    } catch (Exception $e) {
        return ['error' => 'Failed to read Excel file: ' . $e->getMessage()];
    }

    $sheet = $spreadsheet->getActiveSheet();
    $highestRow = $sheet->getHighestRow();
    $highestColumn = $sheet->getHighestColumn();
    $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

    $headerMap = [];
    for ($col = 1; $col <= $highestColumnIndex; $col++) {
        $header = normalizeHeaderLabel(getWorksheetCellText($sheet, $col, 1));
        if ($header !== '') {
            $headerMap[$header] = $col;
        }
    }

    $requiredHeaders = ['item_code', 'item_name'];
    foreach ($requiredHeaders as $requiredHeader) {
        if (!isset($headerMap[$requiredHeader])) {
            return ['error' => 'Missing required column: ' . $requiredHeader . '. Please download the template and use the same headers.'];
        }
    }

    $results = [
        'total_rows' => 0,
        'inserted' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => [],
        'rows' => [],
        'table_operations' => [
            'item_master' => ['inserted' => 0, 'updated' => 0],
        ],
    ];

    $fields = [
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

    for ($rowNum = 2; $rowNum <= $highestRow; $rowNum++) {
        $itemCode = isset($headerMap['item_code']) ? trim((string) getWorksheetCellText($sheet, $headerMap['item_code'], $rowNum)) : '';
        $itemName = isset($headerMap['item_name']) ? trim((string) getWorksheetCellText($sheet, $headerMap['item_name'], $rowNum)) : '';

        if ($itemCode === '' && $itemName === '') {
            continue;
        }

        $results['total_rows']++;

        if ($itemCode === '') {
            $results['skipped']++;
            $results['rows'][] = [
                'row' => $rowNum,
                'code' => '',
                'name' => $itemName,
                'status' => 'skipped',
                'message' => 'Item Code is required',
            ];
            continue;
        }

        if ($itemName === '') {
            $itemName = 'Product ' . $itemCode;
        }

        $existing = $db->getRow('SELECT item_id FROM item_master WHERE item_code = ? LIMIT 1', [$itemCode]);

        $payload = [];
        foreach ($fields as $field) {
            if (!isset($headerMap[$field])) {
                $payload[$field] = null;
                continue;
            }

            $raw = trim((string) getWorksheetCellText($sheet, $headerMap[$field], $rowNum));
            if ($raw === '') {
                $payload[$field] = null;
                continue;
            }

            if (in_array($field, ['item_purchase_price', 'item_normal_selling_price', 'wholesale_price', 'retail_price', 'item_weight', 'item_weight_g', 'pack_weight_g', 'minimum_order', 'order_qty_min', 'order_qty_max', 'low_stock_qty', 'pack_size'], true)) {
                $payload[$field] = normalizeImportedNumber($raw);
            } elseif (in_array($field, ['avail_monday', 'avail_tuesday', 'avail_wednesday', 'avail_thursday', 'avail_friday', 'avail_saturday', 'avail_sunday', 'hide_to_all_customers', 'is_raw_material', 'allow_in_sales', 'allow_in_grn'], true)) {
                $payload[$field] = in_array(strtolower($raw), ['1', 'y', 'yes', 'true', 'on'], true) ? 1 : 0;
            } elseif ($field === 'batch_tracking') {
                $rawUpper = strtoupper($raw);
                $payload[$field] = in_array($rawUpper, ['NONE', 'BATCH', 'SERIAL'], true) ? $rawUpper : 'NONE';
            } else {
                $payload[$field] = $raw;
            }
        }

        $payload['item_name'] = $itemName;
        $payload['item_code'] = $itemCode;

        try {
            if ($existing) {
                $setClauses = [];
                $params = [];

                foreach ($fields as $field) {
                    if (!array_key_exists($field, $payload) || $payload[$field] === null) {
                        continue;
                    }

                    $setClauses[] = '`' . $field . '` = ?';
                    $params[] = $payload[$field];
                }

                if (!empty($setClauses)) {
                    $params[] = (int) $existing['item_id'];
                    $db->updateRow('UPDATE item_master SET ' . implode(', ', $setClauses) . ' WHERE item_id = ?', $params);
                    $results['updated']++;
                    $results['table_operations']['item_master']['updated']++;
                } else {
                    $results['skipped']++;
                }

                $results['rows'][] = [
                    'row' => $rowNum,
                    'code' => $itemCode,
                    'name' => $itemName,
                    'status' => 'updated',
                    'message' => 'Product updated',
                ];
            } else {
                $url = createProductUrlSlug($db, $itemName);

                $insertColumns = [
                    'item_code',
                    'item_name',
                    'url',
                    'item_active',
                    'item_mode',
                    'live',
                ];
                $insertValues = [
                    $itemCode,
                    $itemName,
                    $url,
                    $payload['item_active'] ?? 'Y',
                    $payload['item_mode'] ?? 'goods',
                    $payload['live'] ?? 'yes',
                ];

                foreach ($fields as $field) {
                    if ($field === 'item_name') {
                        continue;
                    }
                    if (!array_key_exists($field, $payload) || $payload[$field] === null) {
                        continue;
                    }

                    $insertColumns[] = $field;
                    $insertValues[] = $payload[$field];
                }

                $placeholders = implode(',', array_fill(0, count($insertColumns), '?'));
                $sql = 'INSERT INTO item_master (' . implode(',', $insertColumns) . ') VALUES (' . $placeholders . ')';
                $db->insertRow($sql, $insertValues);

                $results['inserted']++;
                $results['table_operations']['item_master']['inserted']++;
                $results['rows'][] = [
                    'row' => $rowNum,
                    'code' => $itemCode,
                    'name' => $itemName,
                    'status' => 'created',
                    'message' => 'New product created',
                ];
            }
        } catch (Exception $e) {
            $results['errors'][] = 'Row ' . $rowNum . ' (' . $itemCode . '): ' . $e->getMessage();
            $results['rows'][] = [
                'row' => $rowNum,
                'code' => $itemCode,
                'name' => $itemName,
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    return $results;
}

$uploadResults = null;
$uploadError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_product'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['product_import_csrf'] ?? '')) {
        $uploadError = 'Invalid form submission. Please try again.';
    } elseif (!isset($_FILES['product_file']) || $_FILES['product_file']['error'] !== UPLOAD_ERR_OK) {
        $uploadError = 'Please select a valid Excel file to upload.';
    } else {
        $fileExtension = strtolower(pathinfo($_FILES['product_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, ['xlsx', 'xls'])) {
            $uploadError = 'Only .xlsx and .xls files are supported.';
        } elseif ($_FILES['product_file']['size'] > 10 * 1024 * 1024) {
            $uploadError = 'File size exceeds 10MB limit.';
        } else {
            $uploadResults = processProductImport($db, $_FILES['product_file']['tmp_name']);
        }
    }
}

$_SESSION['product_import_csrf'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Product Import | Admin Panel</title>
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
        .bg-stat-created { background: linear-gradient(135deg, #059669 0%, #34d399 100%); }
        .bg-stat-updated { background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%); }
        .bg-stat-skip { background: linear-gradient(135deg, #d97706 0%, #fbbf24 100%); }
        .bg-stat-error { background: linear-gradient(135deg, #b91c1c 0%, #ef4444 100%); }
        .badge-created { background: #dcfce7; color: #166534; padding: 5px 12px; border-radius: 20px; font-weight: 600; font-size: 12px; }
        .badge-updated { background: #dbeafe; color: #1e40af; padding: 5px 12px; border-radius: 20px; font-weight: 600; font-size: 12px; }
        .badge-skipped { background: #fef3c7; color: #92400e; padding: 5px 12px; border-radius: 20px; font-weight: 600; font-size: 12px; }
        .badge-error-row { background: #fee2e2; color: #991b1b; padding: 5px 12px; border-radius: 20px; font-weight: 600; font-size: 12px; }
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
                                    <i class="fa fa-upload text-primary"></i> Product Import from Excel
                                </h4>
                                <p style="margin:6px 0 0; color:#64748b;">Upload an Excel file to add or update products in item_master.</p>
                            </div>
                            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                                <a href="process/product-import-template.php" class="btn btn-success" style="border-radius:10px; font-weight:700;">
                                    <i class="fa fa-download"></i> Download Template
                                </a>
                                <span class="badge" style="background:#fee2e2; color:#991b1b; padding:8px 16px; border-radius:20px; font-size:13px; font-weight:600;">
                                    <i class="fa fa-lock"></i> Super Admin Only
                                </span>
                            </div>
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
                                <strong><?php echo (int) $uploadResults['total_rows']; ?></strong>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4">
                            <div class="stat-card bg-stat-created">
                                <h5>Created</h5>
                                <strong><?php echo (int) $uploadResults['inserted']; ?></strong>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4">
                            <div class="stat-card bg-stat-updated">
                                <h5>Updated</h5>
                                <strong><?php echo (int) $uploadResults['updated']; ?></strong>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4">
                            <div class="stat-card bg-stat-skip">
                                <h5>Skipped</h5>
                                <strong><?php echo (int) $uploadResults['skipped']; ?></strong>
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
                                <h3><i class="fa fa-database"></i> Tables Affected</h3>
                                <div class="table-responsive">
                                    <table class="table table-bordered mb-0">
                                        <thead style="background:#f8fafc;">
                                            <tr>
                                                <th>Table</th>
                                                <th style="text-align:center;">Inserted</th>
                                                <th style="text-align:center;">Updated</th>
                                                <th style="text-align:center;">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $totalInserted = 0;
                                            $totalUpdated = 0;
                                            foreach ($uploadResults['table_operations'] as $table => $ops):
                                                $inserted = (int) ($ops['inserted'] ?? 0);
                                                $updated = (int) ($ops['updated'] ?? 0);
                                                $totalInserted += $inserted;
                                                $totalUpdated += $updated;
                                            ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($table); ?></strong></td>
                                                <td style="text-align:center; color:#059669;"><strong><?php echo $inserted; ?></strong></td>
                                                <td style="text-align:center; color:#1d4ed8;"><strong><?php echo $updated; ?></strong></td>
                                                <td style="text-align:center;"><strong><?php echo $inserted + $updated; ?></strong></td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <tr style="background:#f0f9ff; font-weight:bold;">
                                                <td>TOTAL</td>
                                                <td style="text-align:center; color:#059669;"><?php echo $totalInserted; ?></td>
                                                <td style="text-align:center; color:#1d4ed8;"><?php echo $totalUpdated; ?></td>
                                                <td style="text-align:center; background:#e0f2fe;"><?php echo $totalInserted + $totalUpdated; ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
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
                                                <th>Code</th>
                                                <th>Name</th>
                                                <th>Status</th>
                                                <th>Details</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($uploadResults['rows'] as $row): ?>
                                            <tr>
                                                <td><?php echo (int) $row['row']; ?></td>
                                                <td><strong><?php echo htmlspecialchars($row['code'] ?? ''); ?></strong></td>
                                                <td><?php echo htmlspecialchars($row['name'] ?? ''); ?></td>
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
                                                    <span class="<?php echo $badgeCls; ?>"><?php echo ucfirst(htmlspecialchars($row['status'])); ?></span>
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

                    <form method="post" enctype="multipart/form-data" id="importForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['product_import_csrf']); ?>">
                        <div class="row" style="margin-top:16px;">
                            <div class="col-lg-8">
                                <div class="import-box">
                                    <h3><i class="fa fa-file-excel-o"></i> Upload Product Template</h3>
                                    <div class="upload-zone" id="uploadZone" onclick="document.getElementById('productFile').click();">
                                        <i class="fa fa-cloud-upload"></i>
                                        <h4 style="color:#334155; margin:0 0 4px;">Drop Excel file here or click to browse</h4>
                                        <p>Supported formats: .xlsx, .xls (Max 10MB)</p>
                                        <p id="selectedFileName" style="color:#3b82f6; font-weight:600; display:none;"></p>
                                    </div>
                                    <input type="file" name="product_file" id="productFile" accept=".xlsx,.xls" style="display:none;">
                                    <div style="margin-top:20px; background:#f0f9ff; border-radius:10px; padding:16px; border:1px solid #bfdbfe;">
                                        <h5 style="margin:0 0 10px; color:#1e40af; font-size:13px;"><i class="fa fa-info-circle"></i> Required Columns</h5>
                                        <table class="table table-sm mb-0" style="font-size:12px;">
                                            <thead><tr><th>Col</th><th>Header</th><th>Required?</th><th>Notes</th></tr></thead>
                                            <tbody>
                                                <tr><td>A</td><td>item_code</td><td><span style="color:red">✔ Required</span></td><td>Product code / SKU</td></tr>
                                                <tr><td>B</td><td>item_name</td><td><span style="color:red">✔ Required</span></td><td>Product name</td></tr>
                                                <tr><td>C</td><td>item_group</td><td>Optional</td><td>Group ID or name</td></tr>
                                                <tr><td>D</td><td>item_type</td><td>Optional</td><td>Type ID or name</td></tr>
                                                <tr><td>E</td><td>item_category</td><td>Optional</td><td>Category</td></tr>
                                                <tr><td>F</td><td>unit_of_measure</td><td>Optional</td><td>Base UOM</td></tr>
                                                <tr><td>G</td><td>item_purchase_price</td><td>Optional</td><td>Purchase price</td></tr>
                                                <tr><td>H</td><td>item_normal_selling_price</td><td>Optional</td><td>Selling price</td></tr>
                                                <tr><td>I</td><td>batch_tracking</td><td>Optional</td><td>NONE / BATCH / SERIAL</td></tr>
                                                <tr><td>J</td><td>additional_uoms</td><td>Optional</td><td>Comma-separated values</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="import-box">
                                    <h3><i class="fa fa-cog"></i> Import Settings</h3>
                                    <div class="alert alert-info" style="border-radius:10px; font-size:12px; padding:10px 14px; margin-bottom:14px;">
                                        <i class="fa fa-info-circle"></i>
                                        Existing item codes will be updated. New item codes will create new products.
                                    </div>
                                    <hr>
                                    <button type="submit" name="import_product" value="1" class="btn btn-primary btn-block" style="border-radius:10px; padding:12px; font-weight:700; font-size:15px;" id="btnImport" disabled>
                                        <i class="fa fa-upload"></i> Import Products
                                    </button>
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
        var fileInput = document.getElementById('productFile');
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
                fileInput.dispatchEvent(new Event('change'));
            }
        });

        document.getElementById('importForm').addEventListener('submit', function(e) {
            if (!confirm('Are you sure you want to import these products?')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
