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

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function generateBulkCustomerCode($db) {
    for ($i = 0; $i < 10; $i++) {
        $code = 'CUST-' . date('Ymd') . '-' . sprintf('%04d', mt_rand(0, 9999));
        $existing = $db->getRow('SELECT customer_id FROM customer WHERE customer_code = ? LIMIT 1', [$code]);
        if (!$existing) {
            return $code;
        }
    }
    $row = $db->getRow('SELECT MAX(customer_id) AS id FROM customer');
    $nextId = (int)($row['id'] ?? 0) + 1;
    return 'CUST-' . str_pad((string)$nextId, 5, '0', STR_PAD_LEFT);
}

// Fetch delivery routes for name → id mapping
$deliveryRoutes = [];
try {
    $routes = $db->getRows('SELECT id, route_name FROM delivery_route_master WHERE is_active = 1');
    foreach ($routes as $r) {
        $deliveryRoutes[strtolower(trim($r['route_name']))] = (int)$r['id'];
    }
} catch (Exception $e) {}

// Process upload
$uploadResults = null;
$uploadError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_customers'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['customer_import_csrf'] ?? '')) {
        $uploadError = 'Invalid form submission. Please try again.';
    } elseif (!isset($_FILES['customer_file']) || $_FILES['customer_file']['error'] !== UPLOAD_ERR_OK) {
        $uploadError = 'Please select a valid Excel file to upload.';
    } else {
        $fileExtension = strtolower(pathinfo($_FILES['customer_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, ['xlsx', 'xls'])) {
            $uploadError = 'Only .xlsx and .xls files are supported.';
        } elseif ($_FILES['customer_file']['size'] > 10 * 1024 * 1024) {
            $uploadError = 'File size exceeds 10MB limit.';
        } else {
            $uploadResults = processCustomerImport($db, $_FILES['customer_file']['tmp_name'], $deliveryRoutes);
        }
    }
}

$_SESSION['customer_import_csrf'] = bin2hex(random_bytes(32));

function processCustomerImport(Database $db, $filePath, $deliveryRoutes)
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
    $colIndex = 1;
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
        'customer_name' => ['customer_name', 'name', 'cust_name', 'company_name'],
        'legal_name' => ['legal_name'],
        'trading_name' => ['trading_name'],
        'email' => ['email', 'customer_email', 'e-mail'],
        'phone' => ['phone', 'telephone', 'landline', 'customer_tell'],
        'mobile' => ['mobile', 'customer_mobile', 'cell'],
        'address_line_1' => ['address_line_1', 'address1', 'address'],
        'address_line_2' => ['address_line_2', 'address2'],
        'city' => ['city'],
        'postal_code' => ['postal_code', 'postcode', 'zip'],
        'country' => ['country'],
        'state' => ['state'],
        'credit_limit' => ['credit_limit'],
        'abn_no' => ['abn_no', 'abn'],
        'gst_no' => ['gst_no', 'gst'],
        'customer_note' => ['customer_note', 'note', 'notes'],
        'contact_name' => ['contact_name'],
        'contact_email' => ['contact_email'],
        'contact_telephone' => ['contact_telephone', 'contact_phone'],
        'ship_address_label' => ['ship_address_label', 'shipping_label'],
        'ship_address_line_1' => ['ship_address_line_1', 'ship_address1', 'shipping_address'],
        'ship_address_line_2' => ['ship_address_line_2', 'ship_address2'],
        'ship_city' => ['ship_city', 'shipping_city'],
        'ship_postal_code' => ['ship_postal_code', 'ship_postcode', 'shipping_postcode'],
        'ship_country' => ['ship_country', 'shipping_country'],
        'ship_state' => ['ship_state', 'shipping_state'],
        'ship_contact_person' => ['ship_contact_person', 'shipping_contact'],
        'ship_contact_phone' => ['ship_contact_phone', 'shipping_phone'],
        'ship_contact_email' => ['ship_contact_email', 'shipping_email'],
        'ship_delivery_route' => ['ship_delivery_route', 'delivery_route', 'route'],
        'ship_remarks' => ['ship_remarks', 'shipping_remarks', 'shipping_notes'],
    ];

    foreach ($expectedColumns as $field => $aliases) {
        foreach ($headers as $ci => $headerVal) {
            if (in_array($headerVal, $aliases, true)) {
                $colMap[$field] = $ci;
                break;
            }
        }
    }

    if (!isset($colMap['customer_name'])) {
        return ['error' => 'Could not find "customer_name" column. Found headers: ' . implode(', ', array_values($headers))];
    }

    $results = [
        'total_rows' => 0,
        'customers_created' => 0,
        'addresses_created' => 0,
        'skipped' => 0,
        'errors' => [],
        'rows' => [],
    ];

    // First pass: group rows by customer
    $customerGroups = [];
    $rowOrder = [];

    for ($rowNum = 2; $rowNum <= $highestRow; $rowNum++) {
        $customerName = isset($colMap['customer_name']) ? trim((string)$sheet->getCell($colMap['customer_name'] . $rowNum)->getValue()) : '';

        if ($customerName === '') {
            continue;
        }

        $results['total_rows']++;

        // Read all customer fields
        $rowData = [];
        foreach ($colMap as $field => $colLetter) {
            $rowData[$field] = trim((string)$sheet->getCell($colLetter . $rowNum)->getValue());
        }
        $rowData['_row'] = $rowNum;

        // Determine if this is a new customer row or just an extra shipping address
        $isCustomerRow = false;
        // If address_line_1 is filled or email is filled or phone is filled → customer row
        $hasCustomerData = !empty($rowData['address_line_1']) || !empty($rowData['email']) || !empty($rowData['phone']) || !empty($rowData['mobile']) || !empty($rowData['credit_limit']);

        // Check if we already have this customer name
        $nameKey = strtolower($customerName);

        if (!isset($customerGroups[$nameKey])) {
            // First occurrence → this is the customer master row
            $isCustomerRow = true;
            $customerGroups[$nameKey] = [
                'master' => $rowData,
                'shipping' => [],
            ];
            $rowOrder[] = $nameKey;
        }

        // Collect shipping address if present
        $shipAddr1 = $rowData['ship_address_line_1'] ?? '';
        if ($shipAddr1 !== '') {
            $customerGroups[$nameKey]['shipping'][] = $rowData;
        } elseif ($isCustomerRow && !empty($rowData['address_line_1'])) {
            // Auto-create a default shipping address from billing address
            $customerGroups[$nameKey]['shipping'][] = [
                'ship_address_label' => $customerName . ' - Primary',
                'ship_address_line_1' => $rowData['address_line_1'] ?? '',
                'ship_address_line_2' => $rowData['address_line_2'] ?? '',
                'ship_city' => $rowData['city'] ?? '',
                'ship_postal_code' => $rowData['postal_code'] ?? '',
                'ship_country' => $rowData['country'] ?? '',
                'ship_state' => $rowData['state'] ?? '',
                'ship_contact_person' => $rowData['contact_name'] ?? '',
                'ship_contact_phone' => $rowData['phone'] ?? '',
                'ship_contact_email' => $rowData['email'] ?? '',
                'ship_delivery_route' => '',
                'ship_remarks' => '',
                '_auto_primary' => true,
                '_row' => $rowNum,
            ];
        }
    }

    // Second pass: insert customers and their shipping addresses
    $pdo = $db->getConnection();

    foreach ($rowOrder as $nameKey) {
        $group = $customerGroups[$nameKey];
        $master = $group['master'];
        $rowNum = $master['_row'];

        $customerName = $master['customer_name'];
        $customerCode = $master['customer_code'] ?? '';
        $email = $master['email'] ?? '';

        // Validate
        if ($customerName === '') {
            $results['skipped']++;
            $results['rows'][] = ['row' => $rowNum, 'name' => '(empty)', 'status' => 'skipped', 'message' => 'Customer name is empty'];
            continue;
        }

        // Check for duplicate email
        if ($email !== '') {
            $existingEmail = $db->getRow('SELECT customer_id FROM customer WHERE customer_email = ? LIMIT 1', [$email]);
            if ($existingEmail) {
                $results['skipped']++;
                $results['rows'][] = ['row' => $rowNum, 'name' => $customerName, 'status' => 'skipped', 'message' => 'Email "' . $email . '" already exists (customer #' . $existingEmail['customer_id'] . ')'];
                continue;
            }
        }

        // Check for duplicate code
        if ($customerCode !== '') {
            $existingCode = $db->getRow('SELECT customer_id FROM customer WHERE customer_code = ? LIMIT 1', [$customerCode]);
            if ($existingCode) {
                $results['skipped']++;
                $results['rows'][] = ['row' => $rowNum, 'name' => $customerName, 'status' => 'skipped', 'message' => 'Customer code "' . $customerCode . '" already exists'];
                continue;
            }
        }

        // Generate code if empty
        if ($customerCode === '') {
            $customerCode = generateBulkCustomerCode($db);
        }

        // credit limit
        $creditLimit = 0.00;
        if (!empty($master['credit_limit'])) {
            $creditLimit = (float)str_replace([',', ' '], '', $master['credit_limit']);
        }

        try {
            $pdo->beginTransaction();

            $db->insertRow(
                'INSERT INTO customer (
                    customer_code, customer_name, legal_name, trading_name,
                    customer_email, customer_password, customer_tell, customer_mobile,
                    customer_address, address_line_1, address_line_2, city, postal_code,
                    country, state, credit_limit, abn_no, gst_no,
                    customer_note, contact_name, contact_email, contact_telephone,
                    is_active, locked, customer_nic, customer_avtive_code,
                    customer_discount, customer_outstanding_balance, new_customer
                ) VALUES (
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    1, 0, ?, ?,
                    0, 0.00, 1
                )',
                [
                    $customerCode,
                    $customerName,
                    !empty($master['legal_name']) ? $master['legal_name'] : null,
                    !empty($master['trading_name']) ? $master['trading_name'] : null,
                    $email !== '' ? $email : null,
                    '',
                    !empty($master['phone']) ? $master['phone'] : null,
                    !empty($master['mobile']) ? $master['mobile'] : null,
                    !empty($master['address_line_1']) ? $master['address_line_1'] : null,
                    !empty($master['address_line_1']) ? $master['address_line_1'] : null,
                    !empty($master['address_line_2']) ? $master['address_line_2'] : null,
                    !empty($master['city']) ? $master['city'] : null,
                    !empty($master['postal_code']) ? $master['postal_code'] : null,
                    !empty($master['country']) ? $master['country'] : null,
                    !empty($master['state']) ? $master['state'] : null,
                    $creditLimit,
                    !empty($master['abn_no']) ? $master['abn_no'] : null,
                    !empty($master['gst_no']) ? $master['gst_no'] : null,
                    !empty($master['customer_note']) ? $master['customer_note'] : null,
                    !empty($master['contact_name']) ? $master['contact_name'] : null,
                    !empty($master['contact_email']) ? $master['contact_email'] : null,
                    !empty($master['contact_telephone']) ? $master['contact_telephone'] : null,
                    '',
                    '',
                ]
            );

            $newRow = $db->getRow('SELECT LAST_INSERT_ID() AS id');
            $customerId = (int)($newRow['id'] ?? 0);

            // Insert shipping addresses
            $addrCount = 0;
            $isFirst = true;
            foreach ($group['shipping'] as $ship) {
                $shipAddr1 = $ship['ship_address_line_1'] ?? '';
                if ($shipAddr1 === '') {
                    continue;
                }

                $routeId = null;
                $routeName = strtolower(trim($ship['ship_delivery_route'] ?? ''));
                if ($routeName !== '' && isset($deliveryRoutes[$routeName])) {
                    $routeId = $deliveryRoutes[$routeName];
                }

                $db->insertRow(
                    'INSERT INTO customer_shipping_address (
                        customer_id, address_label, address_line_1, address_line_2,
                        city, postal_code, country, state,
                        contact_person_name, contact_person_phone, contact_person_email,
                        delivery_route_id, remarks, is_default
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $customerId,
                        !empty($ship['ship_address_label']) ? $ship['ship_address_label'] : 'Address ' . ($addrCount + 1),
                        $shipAddr1,
                        !empty($ship['ship_address_line_2']) ? $ship['ship_address_line_2'] : null,
                        !empty($ship['ship_city']) ? $ship['ship_city'] : null,
                        !empty($ship['ship_postal_code']) ? $ship['ship_postal_code'] : null,
                        !empty($ship['ship_country']) ? $ship['ship_country'] : null,
                        !empty($ship['ship_state']) ? $ship['ship_state'] : null,
                        !empty($ship['ship_contact_person']) ? $ship['ship_contact_person'] : null,
                        !empty($ship['ship_contact_phone']) ? $ship['ship_contact_phone'] : null,
                        !empty($ship['ship_contact_email']) ? $ship['ship_contact_email'] : null,
                        $routeId,
                        !empty($ship['ship_remarks']) ? $ship['ship_remarks'] : null,
                        $isFirst ? 1 : 0,
                    ]
                );
                $addrCount++;
                $isFirst = false;
            }

            $pdo->commit();

            $results['customers_created']++;
            $results['addresses_created'] += $addrCount;
            $results['rows'][] = [
                'row' => $rowNum,
                'name' => $customerName,
                'code' => $customerCode,
                'addresses' => $addrCount,
                'status' => 'created',
                'message' => 'Created with ' . $addrCount . ' shipping address(es)',
            ];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $results['errors'][] = 'Row ' . $rowNum . ' (' . $customerName . '): ' . $e->getMessage();
            $results['rows'][] = [
                'row' => $rowNum,
                'name' => $customerName,
                'status' => 'error',
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
    <title>Bulk Upload Customers</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
    <style>
        .result-created { color: #27ae60; font-weight: bold; }
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
                        <li><a href="manage-customer.php">Customer</a><i class="fa fa-circle"></i></li>
                        <li><span>Bulk Upload</span></li>
                    </ul>
                </div>

                <h1 class="page-title">Bulk Upload Customers</h1>

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
                                <h3><?php echo (int)$uploadResults['customers_created']; ?></h3>
                                <p>Customers Created</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-4">
                            <div class="stat-box" style="background:#3498db;">
                                <h3><?php echo (int)$uploadResults['addresses_created']; ?></h3>
                                <p>Shipping Addresses</p>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4">
                            <div class="stat-box" style="background:#f39c12;">
                                <h3><?php echo (int)$uploadResults['skipped']; ?></h3>
                                <p>Skipped</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-4">
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
                                    <tr><th>Row</th><th>Customer Name</th><th>Code</th><th>Addresses</th><th>Status</th><th>Message</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($uploadResults['rows'] as $r): ?>
                                    <tr>
                                        <td><?php echo (int)$r['row']; ?></td>
                                        <td><?php echo h($r['name']); ?></td>
                                        <td><?php echo h($r['code'] ?? ''); ?></td>
                                        <td><?php echo (int)($r['addresses'] ?? 0); ?></td>
                                        <td>
                                            <?php if ($r['status'] === 'created'): ?>
                                                <span class="result-created"><i class="fa fa-check"></i> Created</span>
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

                    <a href="manage-customer.php" class="btn btn-default"><i class="fa fa-arrow-left"></i> Back to Customer List</a>
                    <a href="customer-bulk-upload.php" class="btn btn-primary"><i class="fa fa-upload"></i> Upload Another File</a>
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
                                            <li>Download the <a href="customer-bulk-sample.php"><strong>sample template</strong></a> first.</li>
                                            <li>Fill in customer details in <strong>blue columns (A–T)</strong> and shipping addresses in <strong>green columns (U–AF)</strong>.</li>
                                            <li>To add multiple shipping addresses for one customer, repeat the <strong>customer_name</strong> on a new row and fill only the shipping columns.</li>
                                            <li>Maximum <strong>500 rows</strong> per upload.</li>
                                            <li>Only <strong>.xlsx</strong> and <strong>.xls</strong> files are accepted.</li>
                                        </ul>
                                    </div>

                                    <form method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['customer_import_csrf']); ?>">
                                        <input type="hidden" name="import_customers" value="1">

                                        <div class="form-group">
                                            <label for="customer_file"><strong>Select Excel File (.xlsx / .xls)</strong></label>
                                            <input type="file" name="customer_file" id="customer_file" class="form-control" accept=".xlsx,.xls" required>
                                        </div>

                                        <hr>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <a href="customer-bulk-sample.php" class="btn btn-success btn-block"><i class="fa fa-download"></i> Download Sample Template</a>
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
</body>
</html>
