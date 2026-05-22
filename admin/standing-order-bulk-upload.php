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

$db = new Database();
$successSoIds = [];

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

// Process upload
$uploadResults = null;
$uploadError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_standing_orders'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['so_import_csrf'] ?? '')) {
        $uploadError = 'Invalid form submission. Please try again.';
    } elseif (!isset($_FILES['so_file']) || $_FILES['so_file']['error'] !== UPLOAD_ERR_OK) {
        $uploadError = 'Please select a valid Excel file to upload.';
    } else {
        $fileExtension = strtolower(pathinfo($_FILES['so_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, ['xlsx', 'xls'])) {
            $uploadError = 'Only .xlsx and .xls files are supported.';
        } elseif ($_FILES['so_file']['size'] > 10 * 1024 * 1024) {
            $uploadError = 'File size exceeds 10MB limit.';
        } else {
            $uploadResults = processStandingOrderImport($db, $_FILES['so_file']['tmp_name']);
        }
    }
}

$_SESSION['so_import_csrf'] = bin2hex(random_bytes(32));

function processStandingOrderImport(Database $db, $filePath)
{
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
        return ['error' => 'PhpSpreadsheet library not found. Please run: composer require phpoffice/phpspreadsheet'];
    }

    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
    } catch (Exception $e) {
        return ['error' => 'Failed to read Excel file: ' . $e->getMessage()];
    }

    $sheet = $spreadsheet->getActiveSheet();
    $highestRow = $sheet->getHighestRow();

    if ($highestRow < 2) {
        return ['error' => 'The file appears to be empty (no data rows found).'];
    }
    if ($highestRow > 501) {
        return ['error' => 'Maximum 500 data rows allowed. Your file has ' . ($highestRow - 1) . ' rows.'];
    }

    // Read header row
    $headers = [];
    foreach ($sheet->getRowIterator(1, 1) as $row) {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);
        foreach ($cellIterator as $cell) {
            $colLetter = $cell->getColumn();
            $val = strtolower(trim((string)$cell->getValue()));
            $headers[$colLetter] = $val;
        }
    }

    // Map expected columns
    $colMap = [];
    $expectedColumns = [
        'customer_code' => ['customer_code', 'code', 'cust_code'],
        'customer_name' => ['customer_name', 'name', 'cust_name'],
        'item_code' => ['item_code', 'product_code', 'prod_code'],
        'item_name' => ['item_name', 'product_name', 'prod_name'],
        'mon_qty' => ['mon_qty', 'monday', 'mon'],
        'tue_qty' => ['tue_qty', 'tuesday', 'tue'],
        'wed_qty' => ['wed_qty', 'wednesday', 'wed'],
        'thu_qty' => ['thu_qty', 'thursday', 'thu'],
        'fri_qty' => ['fri_qty', 'friday', 'fri'],
        'sat_qty' => ['sat_qty', 'saturday', 'sat'],
        'sun_qty' => ['sun_qty', 'sunday', 'sun'],
        'shipping_address_label' => ['shipping_address_label', 'ship_address_label', 'address_label'],
        'delivery_amount' => ['delivery_amount', 'delivery_charge', 'delivery_cost'],
        'date_from' => ['date_from', 'start_date', 'from_date'],
        'date_to' => ['date_to', 'end_date', 'to_date'],
    ];

    foreach ($expectedColumns as $field => $aliases) {
        foreach ($headers as $ci => $headerVal) {
            if (in_array($headerVal, $aliases, true)) {
                $colMap[$field] = $ci;
                break;
            }
        }
    }

    if (!isset($colMap['customer_code'])) {
        return ['error' => 'Could not find "customer_code" column. Found headers: ' . implode(', ', array_values($headers))];
    }
    if (!isset($colMap['item_code'])) {
        return ['error' => 'Could not find "item_code" column. Found headers: ' . implode(', ', array_values($headers))];
    }

    function getSpreadsheetCellStringValue($cell)
    {
        if ($cell === null) {
            return '';
        }

        $value = $cell->getValue();

        if (is_object($value) && method_exists($value, 'format')) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value) && trim((string)$value) !== '') {
            try {
                if (\PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell)) {
                    $dateTime = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
                    return $dateTime->format('Y-m-d');
                }
            } catch (Exception $e) {
                // Fall back to raw string parsing below.
            }
        }

        return trim((string)$value);
    }

    function normalizeSpreadsheetDateValue($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime(str_replace('/', '-', $value));
        if ($timestamp === false || $timestamp <= 0) {
            return null;
        }

        return date('Y-m-d', $timestamp);
    }

    $results = [
        'total_rows' => 0,
        'orders_created' => 0,
        'orders_updated' => 0,
        'items_created' => 0,
        'skipped' => 0,
        'errors' => [],
        'rows' => [],
    ];

    // First pass: group rows by customer_code
    $customerGroups = [];
    $groupOrder = [];

    for ($rowNum = 2; $rowNum <= $highestRow; $rowNum++) {
        $customerCode = isset($colMap['customer_code']) ? trim((string)$sheet->getCell($colMap['customer_code'] . $rowNum)->getValue()) : '';

        if ($customerCode === '') {
            continue;
        }

        $results['total_rows']++;

        // Read all fields
        $rowData = [];
        foreach ($colMap as $field => $colLetter) {
            $rowData[$field] = getSpreadsheetCellStringValue($sheet->getCell($colLetter . $rowNum));
        }
        $rowData['_row'] = $rowNum;

        $codeKey = strtolower($customerCode);

        if (!isset($customerGroups[$codeKey])) {
            $customerGroups[$codeKey] = [
                'customer_code' => $customerCode,
                'customer_name' => $rowData['customer_name'] ?? '',
                'shipping_address_label' => $rowData['shipping_address_label'] ?? '',
                'delivery_amount' => $rowData['delivery_amount'] ?? '',
                'date_from' => $rowData['date_from'] ?? '',
                'date_to' => $rowData['date_to'] ?? '',
                'first_row' => $rowNum,
                'items' => [],
            ];
            $groupOrder[] = $codeKey;
        }

        // Add item
        $customerGroups[$codeKey]['items'][] = $rowData;
    }

    // Second pass: create/update standing orders
    $pdo = $db->getConnection();

    foreach ($groupOrder as $codeKey) {
        $group = $customerGroups[$codeKey];
        $customerCode = $group['customer_code'];
        $firstRow = $group['first_row'];

        // Look up customer
        $customer = $db->getRow('SELECT customer_id, customer_name FROM customer WHERE customer_code = ? LIMIT 1', [$customerCode]);
        if (!$customer) {
            $results['skipped']++;
            $results['rows'][] = [
                'row' => $firstRow,
                'customer' => $customerCode . ' (' . $group['customer_name'] . ')',
                'items' => 0,
                'status' => 'skipped',
                'action' => '',
                'message' => 'Customer code "' . $customerCode . '" not found in system',
            ];
            continue;
        }

        $customerId = (int)$customer['customer_id'];
        $customerName = $customer['customer_name'];

        // Resolve shipping address
        $shippingAddressId = null;
        $shipLabel = trim($group['shipping_address_label']);
        if ($shipLabel !== '') {
            $shipAddr = $db->getRow(
                'SELECT id FROM customer_shipping_address WHERE customer_id = ? AND address_label = ? LIMIT 1',
                [$customerId, $shipLabel]
            );
            if ($shipAddr) {
                $shippingAddressId = (int)$shipAddr['id'];
            }
            // If not found, try case-insensitive
            if (!$shippingAddressId) {
                $shipAddr = $db->getRow(
                    'SELECT id FROM customer_shipping_address WHERE customer_id = ? AND LOWER(address_label) = LOWER(?) LIMIT 1',
                    [$customerId, $shipLabel]
                );
                if ($shipAddr) {
                    $shippingAddressId = (int)$shipAddr['id'];
                }
            }
        }

        // Bulk upload relies only on the actual date range.
        $repeatInterval = null;
        $repeatUnitId = null;

        // Delivery amount
        $deliveryAmount = 0.00;
        if (!empty($group['delivery_amount'])) {
            $deliveryAmount = (float)str_replace([',', ' '], '', $group['delivery_amount']);
        }

        // Dates are mandatory for bulk upload invoice generation.
        $dateFrom = normalizeSpreadsheetDateValue($group['date_from'] ?? '');
        $dateTo = normalizeSpreadsheetDateValue($group['date_to'] ?? '');
        if ($dateFrom === null || $dateTo === null) {
            $message = 'Invalid or missing date range. Please provide valid date_from and date_to values in the first row for this customer.';
            $results['errors'][] = 'Row ' . $firstRow . ': ' . $message;
            $results['rows'][] = [
                'row' => $firstRow,
                'customer' => $customerCode . ' (' . $customerName . ')',
                'items' => 0,
                'status' => 'error',
                'action' => '',
                'message' => $message,
            ];
            continue;
        }
        if ($dateFrom > $dateTo) {
            $message = 'date_from cannot be after date_to.';
            $results['errors'][] = 'Row ' . $firstRow . ': ' . $message;
            $results['rows'][] = [
                'row' => $firstRow,
                'customer' => $customerCode . ' (' . $customerName . ')',
                'items' => 0,
                'status' => 'error',
                'action' => '',
                'message' => $message,
            ];
            continue;
        }

        // Validate items first
        $validItems = [];
        $itemErrors = [];
        $hasAllowInSalesColumn = (bool) $db->getRow("SHOW COLUMNS FROM item_master LIKE 'allow_in_sales'");
        foreach ($group['items'] as $itemRow) {
            $itemCode = trim($itemRow['item_code'] ?? '');
            if ($itemCode === '') {
                continue;
            }

            $productLookupQuery = $hasAllowInSalesColumn
                ? 'SELECT item_id, item_name, allow_in_sales FROM item_master WHERE item_code = ? LIMIT 1'
                : 'SELECT item_id, item_name FROM item_master WHERE item_code = ? LIMIT 1';
            $product = $db->getRow($productLookupQuery, [$itemCode]);
            if (!$product) {
                $itemErrors[] = 'Row ' . $itemRow['_row'] . ': Item code "' . $itemCode . '" not found';
                continue;
            }
            if ($hasAllowInSalesColumn && array_key_exists('allow_in_sales', $product) && $product['allow_in_sales'] !== null && (int) $product['allow_in_sales'] !== 1) {
                $itemErrors[] = 'Row ' . $itemRow['_row'] . ': "' . $product['item_name'] . '" is not allowed in sales';
                continue;
            }

            $monQty = (float)($itemRow['mon_qty'] ?? 0);
            $tueQty = (float)($itemRow['tue_qty'] ?? 0);
            $wedQty = (float)($itemRow['wed_qty'] ?? 0);
            $thuQty = (float)($itemRow['thu_qty'] ?? 0);
            $friQty = (float)($itemRow['fri_qty'] ?? 0);
            $satQty = (float)($itemRow['sat_qty'] ?? 0);
            $sunQty = (float)($itemRow['sun_qty'] ?? 0);

            $totalQty = $monQty + $tueQty + $wedQty + $thuQty + $friQty + $satQty + $sunQty;
            if ($totalQty <= 0) {
                $itemErrors[] = 'Row ' . $itemRow['_row'] . ': All quantities are zero for "' . $product['item_name'] . '"';
                continue;
            }

            $validItems[] = [
                'item_id' => (int)$product['item_id'],
                'item_name' => $product['item_name'],
                'mon_qty' => $monQty,
                'tue_qty' => $tueQty,
                'wed_qty' => $wedQty,
                'thu_qty' => $thuQty,
                'fri_qty' => $friQty,
                'sat_qty' => $satQty,
                'sun_qty' => $sunQty,
                '_row' => $itemRow['_row'],
            ];
        }

        if (!empty($itemErrors)) {
            foreach ($itemErrors as $err) {
                $results['errors'][] = $err;
            }
        }

        if (empty($validItems)) {
            $results['skipped']++;
            $results['rows'][] = [
                'row' => $firstRow,
                'customer' => $customerCode . ' (' . $customerName . ')',
                'items' => 0,
                'status' => 'skipped',
                'action' => '',
                'message' => 'No valid items found',
            ];
            continue;
        }

        try {
            $pdo->beginTransaction();

            // Check for existing active standing order
            $existing = $db->getRow('SELECT id FROM standing_order WHERE customer_id = ? AND active = 1 LIMIT 1', [$customerId]);
            $action = 'created';

            if ($existing && isset($existing['id'])) {
                $soId = (int)$existing['id'];
                $action = 'updated';
                $db->updateRow(
                    'UPDATE standing_order SET shipping_address_id = ?, DeliveryAmount = ?, RepeatInterval = ?, RepeatUnit = ?, date_from = ?, date_to = ?, updated_at = NOW() WHERE id = ?',
                    [$shippingAddressId, $deliveryAmount, $repeatInterval, $repeatUnitId, $dateFrom, $dateTo, $soId]
                );
                // Clear existing items
                $db->deleteRow('DELETE FROM standing_order_item WHERE standing_order_id = ?', [$soId]);
            } else {
                $db->insertRow(
                    'INSERT INTO standing_order (customer_id, shipping_address_id, active, DeliveryAmount, RepeatInterval, RepeatUnit, date_from, date_to, created_at, updated_at) VALUES (?, ?, 1, ?, ?, ?, ?, ?, NOW(), NOW())',
                    [$customerId, $shippingAddressId, $deliveryAmount, $repeatInterval, $repeatUnitId, $dateFrom, $dateTo]
                );
                $row = $db->getRow('SELECT id FROM standing_order WHERE customer_id = ? AND active = 1 ORDER BY id DESC LIMIT 1', [$customerId]);
                if (!$row || !isset($row['id'])) {
                    throw new Exception('Failed to create standing order');
                }
                $soId = (int)$row['id'];
            }

            // Insert items
            $itemCount = 0;
            foreach ($validItems as $item) {
                $db->insertRow(
                    'INSERT INTO standing_order_item (standing_order_id, item_id, mon_qty, tue_qty, wed_qty, thu_qty, fri_qty, sat_qty, sun_qty, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
                    [
                        $soId,
                        $item['item_id'],
                        $item['mon_qty'],
                        $item['tue_qty'],
                        $item['wed_qty'],
                        $item['thu_qty'],
                        $item['fri_qty'],
                        $item['sat_qty'],
                        $item['sun_qty'],
                    ]
                );
                $itemCount++;
            }

            $pdo->commit();

            if ($action === 'created') {
                $results['orders_created']++;
            } else {
                $results['orders_updated']++;
            }
            $results['items_created'] += $itemCount;
            $results['rows'][] = [
                'row' => $firstRow,
                'customer' => $customerCode . ' (' . $customerName . ')',
                'items' => $itemCount,
                'status' => $action,
                'action' => ucfirst($action),
                'message' => ucfirst($action) . ' with ' . $itemCount . ' item(s)',
                'standing_order_id' => $soId,
                'customer_id' => $customerId,
            ];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $results['errors'][] = 'Customer ' . $customerCode . ': ' . $e->getMessage();
            $results['rows'][] = [
                'row' => $firstRow,
                'customer' => $customerCode . ' (' . $customerName . ')',
                'items' => 0,
                'status' => 'error',
                'action' => '',
                'message' => $e->getMessage(),
            ];
        }
    }

    return $results;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Standing Order Bulk Upload</title>
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
                        <li><a href="standing-order.php">Standing Orders</a><i class="fa fa-circle"></i></li>
                        <li><span>Bulk Upload</span></li>
                    </ul>
                </div>

                <h1 class="page-title">Standing Order Bulk Upload</h1>

                <?php if ($uploadError): ?>
                    <div class="alert alert-danger"><i class="fa fa-warning"></i> <?php echo h($uploadError); ?></div>
                <?php endif; ?>

                <?php if ($uploadResults && isset($uploadResults['error'])): ?>
                    <div class="alert alert-danger"><i class="fa fa-warning"></i> <?php echo h($uploadResults['error']); ?></div>
                <?php endif; ?>

                <?php if ($uploadResults && !isset($uploadResults['error'])): ?>
                    <!-- Results -->
                    <div class="row">
                        <div class="col-md-2 col-sm-4">
                            <div class="stat-box" style="background:#34495e;">
                                <h3><?php echo (int)$uploadResults['total_rows']; ?></h3>
                                <p>Total Rows</p>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4">
                            <div class="stat-box" style="background:#27ae60;">
                                <h3><?php echo (int)$uploadResults['orders_created']; ?></h3>
                                <p>Orders Created</p>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4">
                            <div class="stat-box" style="background:#3498db;">
                                <h3><?php echo (int)$uploadResults['orders_updated']; ?></h3>
                                <p>Orders Updated</p>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4">
                            <div class="stat-box" style="background:#8e44ad;">
                                <h3><?php echo (int)$uploadResults['items_created']; ?></h3>
                                <p>Items Added</p>
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
                                <?php foreach ($uploadResults['errors'] as $err): ?>
                                    <li><?php echo h($err); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="portlet light bordered">
                        <div class="portlet-title">
                            <div class="caption font-green"><i class="fa fa-list"></i> Import Details</div>
                        </div>
                        <div class="portlet-body">
                            <table class="table table-striped table-bordered table-condensed">
                                <thead>
                                    <tr><th>Row</th><th>Customer</th><th>Items</th><th>Action</th><th>Status</th><th>Message</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($uploadResults['rows'] as $r): ?>
                                    <tr>
                                        <td><?php echo (int)$r['row']; ?></td>
                                        <td><?php echo h($r['customer']); ?></td>
                                        <td><?php echo (int)$r['items']; ?></td>
                                        <td><?php echo h($r['action']); ?></td>
                                        <td>
                                            <?php if ($r['status'] === 'created'): ?>
                                                <span class="result-created"><i class="fa fa-check"></i> Created</span>
                                            <?php elseif ($r['status'] === 'updated'): ?>
                                                <span class="result-updated"><i class="fa fa-refresh"></i> Updated</span>
                                            <?php elseif ($r['status'] === 'skipped'): ?>
                                                <span class="result-skipped"><i class="fa fa-exclamation-triangle"></i> Skipped</span>
                                            <?php else: ?>
                                                <span class="result-error"><i class="fa fa-times"></i> Error</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo h($r['message']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <?php
                    // Collect standing order IDs from successful rows
                    $successSoIds = [];
                    foreach ($uploadResults['rows'] as $r) {
                        if (in_array($r['status'], ['created', 'updated']) && !empty($r['standing_order_id'])) {
                            $successSoIds[] = (int)$r['standing_order_id'];
                        }
                    }
                    ?>
                    <?php if (!empty($successSoIds)): ?>
                    <button type="button" id="btn-generate-invoices" class="btn btn-success btn-lg" onclick="generateInvoices(this)">
                        <i class="fa fa-file-text-o"></i> Generate Invoices (<?php echo count($successSoIds); ?> order<?php echo count($successSoIds) > 1 ? 's' : ''; ?>)
                    </button>
                    <?php endif; ?>
                    <a href="standing-order.php" class="btn btn-default"><i class="fa fa-arrow-left"></i> Back to Standing Orders</a>
                    <a href="standing-order-bulk-upload.php" class="btn btn-primary"><i class="fa fa-upload"></i> Upload Another File</a>
                <?php else: ?>
                    <!-- Upload Form -->
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
                                            <li>Download the <a href="standing-order-bulk-sample.php"><strong>sample template</strong></a> first.</li>
                                            <li><strong>Blue columns (A–B)</strong>: Customer identification (customer_code is required).</li>
                                            <li><strong>Green columns (C–K)</strong>: Item code and daily quantities (Mon–Sun).</li>
                                            <li><strong>Orange columns (L–Q)</strong>: Order settings (shipping, delivery amount, dates) — fill only on the first row per customer.</li>
                                            <li>Each row = one item. Multiple items for the same customer share the same <strong>customer_code</strong>.</li>
                                            <li>If the customer already has an active standing order, it will be <strong>replaced</strong>.</li>
                                            <li>Maximum <strong>500 rows</strong> per upload. Only <strong>.xlsx / .xls</strong> files accepted.</li>
                                        </ul>
                                    </div>

                                    <form method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['so_import_csrf']); ?>">
                                        <input type="hidden" name="import_standing_orders" value="1">

                                        <div class="form-group">
                                            <label for="so_file"><strong>Select Excel File (.xlsx / .xls)</strong></label>
                                            <input type="file" name="so_file" id="so_file" class="form-control" accept=".xlsx,.xls" required>
                                        </div>

                                        <hr>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <a href="standing-order-bulk-sample.php" class="btn btn-success btn-block"><i class="fa fa-download"></i> Download Sample Template</a>
                                            </div>
                                            <div class="col-md-6">
                                                <button type="submit" class="btn btn-primary btn-block btn-lg">
                                                    <i class="fa fa-upload"></i> Upload & Import
                                                </button>
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
    <script>
    var _soIds = <?php echo !empty($successSoIds) ? json_encode(array_values($successSoIds)) : '[]'; ?>;
    var _csrfToken = '<?php echo isset($_SESSION["so_import_csrf"]) ? h($_SESSION["so_import_csrf"]) : ""; ?>';

    function generateInvoices(btn) {
        console.log('Generate Invoices clicked, soIds:', _soIds);
        if (!_soIds || !_soIds.length) {
            alert('No standing orders to generate invoices for.');
            return;
        }
        if (!confirm('Generate invoices for ' + _soIds.length + ' standing order(s)?\n\nThis will create invoices for all scheduled delivery days in their date ranges. Existing invoices will be skipped.')) {
            return;
        }
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Generating Invoices...';

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'process/generate-standing-order-invoices.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onreadystatechange = function() {
            if (xhr.readyState !== 4) return;
            console.log('Response status:', xhr.status, 'body:', xhr.responseText);
            if (xhr.status === 200) {
                try {
                    var resp = JSON.parse(xhr.responseText);
                } catch(e) {
                    alert('Invalid response from server:\n' + xhr.responseText.substring(0, 500));
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa fa-file-text-o"></i> Generate Invoices (' + _soIds.length + ' orders)';
                    return;
                }
                if (resp.status === 'success') {
                    var msg = 'Invoices generated successfully!\n\nTotal invoices created: ' + resp.total_invoices;
                    if (resp.results && resp.results.length) {
                        msg += '\n\nDetails:';
                        for (var i = 0; i < resp.results.length; i++) {
                            var r = resp.results[i];
                            msg += '\n- ' + (r.customer || 'SO #' + r.so_id) + ': ' + r.message;
                        }
                    }
                    alert(msg);
                    btn.className = 'btn btn-default btn-lg';
                    btn.innerHTML = '<i class="fa fa-check"></i> Invoices Generated (' + resp.total_invoices + ')';
                } else {
                    alert('Error: ' + (resp.message || 'Unknown error'));
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa fa-file-text-o"></i> Generate Invoices (' + _soIds.length + ' orders)';
                }
            } else {
                alert('Request failed (HTTP ' + xhr.status + ').\n' + xhr.responseText.substring(0, 300));
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-file-text-o"></i> Generate Invoices (' + _soIds.length + ' orders)';
            }
        };
        xhr.send(JSON.stringify({ standing_order_ids: _soIds, csrf_token: _csrfToken }));
    }
    </script>
</body>
</html>
