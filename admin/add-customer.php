<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('include/database.php');
include('include/check_login.php');
include('include/customer_access.php');
include('include/delivery_route_groups.php');

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function getRepeatUnits() {
    try {
        $db = new Database();
        return $db->getRows('SELECT id, name, display_name FROM repeat_units ORDER BY id ASC');
    } catch (Exception $e) {
        return [];
    }
}

function getDeliveryRoutes() {
    try {
        $db = new Database();
        return $db->getRows('SELECT id, route_name FROM delivery_route_master WHERE is_active = 1 ORDER BY route_name ASC');
    } catch (Exception $e) {
        return [];
    }
}

function generateCustomerCode($db) {
    for ($i = 0; $i < 10; $i++) {
        $code = 'CUST-' . date('Ymd') . '-' . sprintf('%04d', mt_rand(0, 9999));
        $existing = $db->getRow('SELECT customer_id FROM customer WHERE customer_code = ? LIMIT 1', [$code]);
        if (!$existing) {
            return $code;
        }
    }
    $row = $db->getRow('SELECT MAX(customer_id) AS id FROM customer');
    $nextId = (int) ($row['id'] ?? 0) + 1;
    return 'CUST-' . str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
}

$message = '';
$MessageClass = '';
$status = false;
$db = null;
$generatedCode = 'CUST-00001';

try {
    $db = new Database();
    $row = $db->getRow('SELECT MAX(customer_id) AS id FROM customer');
    $nextId = (int) ($row['id'] ?? 0) + 1;
    $generatedCode = 'CUST-' . str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
} catch (Exception $e) {
    $message = 'Database connection error: ' . $e->getMessage();
    $MessageClass = 'alert-danger';
}

$canManageCustomerAccess = canManageCustomerStatusAccess();

$formData = [
    'customer_code' => $generatedCode,
    'customer_name' => '',
    'legal_name' => '',
    'trading_name' => '',
    'customer_email' => '',
    'customer_phone' => '',
    'customer_mobile' => '',
    'address_line_1' => '',
    'address_line_2' => '',
    'city' => '',
    'postal_code' => '',
    'credit_limit' => '',
    'account_hold' => 0,
    'abn_no' => '',
    'acn_no' => '',
    'vat_registered' => 0,
    'gst_no' => '',
    'payment_terms_id' => '',
    'customer_price_type_id' => '',
    'customer_note' => '',
    'customer_remarks' => '',
    'customer_additional_emails' => [],
    'is_active' => $canManageCustomerAccess ? 1 : 0,
    'locked' => 0,
    'repeat_interval' => '',
    'repeat_unit' => '',
    'min_order_amount' => '',
    'emergency_contact_name' => '',
    'emergency_contact_email' => '',
    'emergency_contact_telephone' => '',
    'custom_url_link' => '',
    'google_map_link' => '',
    'contact_name' => '',
    'contact_email' => '',
    'contact_telephone' => '',
    'compliance_contact_emails' => [],
    'default_shipping_additional_emails' => [],
];

// Shipping addresses are managed in edit-customer.php only.
// A default Primary address is auto-created from billing info on save.

$countries = [];
$paymentTerms = [];
$priceTypes = [];
$discountCodes = [];

try {
    $db = new Database();
    $countries = $db->getRows('SELECT * FROM countries ORDER BY country_name ASC');
    $paymentTerms = $db->getRows('SELECT * FROM payment_terms ORDER BY payment_terms_name ASC');
    $priceTypes = $db->getRows('SELECT * FROM price_type ORDER BY description ASC');
    $discountCodes = $db->getRows('SELECT id, code, description, percentage FROM discount_code ORDER BY code ASC');
    $db->getRows('CREATE TABLE IF NOT EXISTS `customer_compliance_documents` (
        `id` int(10) NOT NULL AUTO_INCREMENT,
        `customer_id` int(10) NOT NULL,
        `file_path` varchar(255) DEFAULT NULL,
        `file_name` varchar(255) DEFAULT NULL,
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `customer_id` (`customer_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $db->getRows('CREATE TABLE IF NOT EXISTS `customer_email_accounts` (
        `id` int(10) NOT NULL AUTO_INCREMENT,
        `customer_id` int(10) NOT NULL,
        `email_address` varchar(255) NOT NULL,
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `customer_id` (`customer_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $db->getRows('CREATE TABLE IF NOT EXISTS `customer_compliance_contact_emails` (
        `id` int(10) NOT NULL AUTO_INCREMENT,
        `customer_id` int(10) NOT NULL,
        `email_address` varchar(255) NOT NULL,
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `customer_id` (`customer_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $db->getRows('CREATE TABLE IF NOT EXISTS `shipping_address_additional_emails` (
        `id` int(10) NOT NULL AUTO_INCREMENT,
        `shipping_address_id` int(10) NOT NULL,
        `email_address` varchar(255) NOT NULL,
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `shipping_address_id` (`shipping_address_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
} catch (Exception $e) {
    // ignore
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$db) {
        try {
            $db = new Database();
        } catch (Exception $e) {
            $message = 'Database connection error: ' . $e->getMessage();
            $MessageClass = 'alert-danger';
        }
    }

    $errors = [];

    $formData['customer_code'] = trim($_POST['customer_code'] ?? $formData['customer_code']);
    $formData['customer_name'] = trim($_POST['customer_name'] ?? '');
    $formData['legal_name'] = trim($_POST['legal_name'] ?? '');
    $formData['trading_name'] = trim($_POST['trading_name'] ?? '');
    $formData['customer_email'] = trim($_POST['customer_email'] ?? '');
    $formData['customer_phone'] = trim($_POST['customer_phone'] ?? '');
    $formData['customer_mobile'] = trim($_POST['customer_mobile'] ?? '');
    $formData['address_line_1'] = trim($_POST['address_line_1'] ?? '');
    $formData['address_line_2'] = trim($_POST['address_line_2'] ?? '');
    $formData['city'] = trim($_POST['city'] ?? '');
    $formData['postal_code'] = trim($_POST['postal_code'] ?? '');
    $formData['credit_limit'] = trim($_POST['credit_limit'] ?? '');
    $formData['account_hold'] = isset($_POST['account_hold']) ? 1 : 0;
    $formData['abn_no'] = trim($_POST['abn_no'] ?? '');
    $formData['acn_no'] = trim($_POST['acn_no'] ?? '');
    $formData['vat_registered'] = isset($_POST['vat_registered']) ? 1 : 0;
    $formData['gst_no'] = trim($_POST['gst_no'] ?? '');
    $formData['payment_terms_id'] = trim($_POST['payment_terms_id'] ?? '');
    $formData['customer_price_type_id'] = trim($_POST['customer_price_type_id'] ?? '');
    $formData['customer_note'] = trim($_POST['customer_note'] ?? '');
    $formData['customer_remarks'] = trim($_POST['customer_remarks'] ?? '');
    $customerAccessFlags = normalizeCustomerStatusFlags($_POST);
    $formData['is_active'] = $customerAccessFlags['is_active'];
    $formData['locked'] = $customerAccessFlags['locked'];
    $formData['repeat_interval'] = trim($_POST['repeat_interval'] ?? '');
    $formData['repeat_unit'] = trim($_POST['repeat_unit'] ?? '');
    $formData['min_order_amount'] = trim($_POST['min_order_amount'] ?? '');
    $formData['emergency_contact_name'] = trim($_POST['emergency_contact_name'] ?? '');
    $formData['emergency_contact_email'] = trim($_POST['emergency_contact_email'] ?? '');
    $formData['emergency_contact_telephone'] = trim($_POST['emergency_contact_telephone'] ?? '');
    $formData['custom_url_link'] = trim($_POST['custom_url_link'] ?? '');
    $formData['google_map_link'] = trim($_POST['google_map_link'] ?? '');
    $formData['line_discount_id'] = trim($_POST['line_discount_id'] ?? '');
    $formData['contact_name'] = trim($_POST['contact_name'] ?? '');
    $formData['contact_email'] = trim($_POST['contact_email'] ?? '');
    $formData['contact_telephone'] = trim($_POST['contact_telephone'] ?? '');
    $formData['customer_additional_emails'] = array_values(array_filter(array_map('trim', (array)($_POST['customer_additional_emails'] ?? []))));
    $formData['compliance_contact_emails'] = [];
    $submittedComplianceContactEmails = array_filter(array_map('trim', (array)($_POST['compliance_contact_emails'] ?? [])));
    foreach ($submittedComplianceContactEmails as $submittedComplianceContactEmail) {
        if ($submittedComplianceContactEmail === '') {
            continue;
        }
        if (!filter_var($submittedComplianceContactEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid compliance contact email: ' . $submittedComplianceContactEmail;
            continue;
        }
        $formData['compliance_contact_emails'][] = $submittedComplianceContactEmail;
    }

    $formData['default_shipping_additional_emails'] = [];
    $submittedShippingAdditionalEmails = [];
    $postedShippingAdditionalEmails = $_POST['shipping_additional_emails'] ?? [];
    if (is_array($postedShippingAdditionalEmails)) {
        $submittedShippingAdditionalEmails = array_filter(array_map('trim', (array)($postedShippingAdditionalEmails[0] ?? [])));
    }
    foreach ($submittedShippingAdditionalEmails as $submittedShippingAdditionalEmail) {
        if ($submittedShippingAdditionalEmail === '') {
            continue;
        }
        if (!filter_var($submittedShippingAdditionalEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid shipping additional email: ' . $submittedShippingAdditionalEmail;
            continue;
        }
        $formData['default_shipping_additional_emails'][] = $submittedShippingAdditionalEmail;
    }

    // Auto-create a single default Primary shipping address from billing info
    $shippingData = [
        [
            'label' => ($formData['customer_name'] !== '' ? $formData['customer_name'] . ' - Primary' : 'Primary'),
            'address_line_1' => $formData['address_line_1'],
            'address_line_2' => $formData['address_line_2'],
            'city' => $formData['city'],
            'postal_code' => $formData['postal_code'],
            'contact_no' => $formData['customer_phone'],
            'is_default' => 1,
            'additional_emails' => $formData['default_shipping_additional_emails'],
        ],
    ];

    $paymentOptions = [];
    $cardNos = $_POST['card_no'] ?? [];
    $cardNames = $_POST['card_name'] ?? [];
    $expMonths = $_POST['exp_month'] ?? [];
    $expYears = $_POST['exp_year'] ?? [];
    foreach ($cardNos as $idx => $cardNo) {
        if (trim($cardNo) !== '') {
            $paymentOptions[] = [
                'type' => 'card',
                'card_no' => trim($cardNo),
                'card_name' => trim($cardNames[$idx] ?? ''),
                'exp_month' => trim($expMonths[$idx] ?? ''),
                'exp_year' => trim($expYears[$idx] ?? ''),
            ];
        }
    }
    $bankNames = $_POST['bank_name'] ?? [];
    $branches = $_POST['branch'] ?? [];
    $accountNos = $_POST['account_no'] ?? [];
    $accountHolders = $_POST['account_holder'] ?? [];
    foreach ($bankNames as $idx => $bankName) {
        $bankName = trim($bankName);
        $branch = trim($branches[$idx] ?? '');
        $accountNo = trim($accountNos[$idx] ?? '');
        $accountHolder = trim($accountHolders[$idx] ?? '');

        if ($bankName === '' && $branch === '' && $accountNo === '' && $accountHolder === '') {
            continue;
        }

        if ($bankName !== '' && $accountNo !== '' && $accountHolder !== '') {
            $paymentOptions[] = [
                'type' => 'bank',
                'bank_name' => $bankName,
                'branch' => $branch,
                'account_no' => $accountNo,
                'account_holder' => $accountHolder,
            ];
        }
    }

    if ($formData['customer_name'] === '') {
        $errors[] = 'Customer name is required.';
    }

    if ($formData['address_line_1'] === '') {
        $errors[] = 'Address line 1 is required.';
    }
    if ($formData['abn_no'] === '') {
        $errors[] = 'ABN is required.';
    }
    if ($formData['acn_no'] === '') {
        $errors[] = 'ACN is required.';
    }
    if ($formData['customer_phone'] === '') {
        $errors[] = 'Contact phone is required.';
    }
    if ($formData['contact_name'] === '') {
        $errors[] = 'Contact person name is required.';
    }
    if ($formData['contact_email'] === '') {
        $errors[] = 'Contact email is required.';
    } elseif (!filter_var($formData['contact_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Contact email must be a valid email address.';
    }
    if ($formData['contact_telephone'] === '') {
        $errors[] = 'Contact telephone is required.';
    }

    if ($formData['customer_code'] === '') {
        $formData['customer_code'] = $generatedCode;
    }

    $creditLimitValue = 0.00;
    if ($formData['credit_limit'] !== '') {
        $normalizedLimit = str_replace([',', ' '], '', $formData['credit_limit']);
        if (!is_numeric($normalizedLimit)) {
            $errors[] = 'Credit limit must be a valid number.';
        } else {
            $creditLimitValue = number_format((float) $normalizedLimit, 2, '.', '');
        }
    }

    $paymentTermsId = null;
    if ($formData['payment_terms_id'] !== '') {
        if (!ctype_digit($formData['payment_terms_id'])) {
            $errors[] = 'Payment terms must be a numeric ID.';
        } else {
            $paymentTermsId = (int) $formData['payment_terms_id'];
        }
    }

    $customerPriceTypeId = null;
    if ($formData['customer_price_type_id'] !== '') {
        if (!ctype_digit($formData['customer_price_type_id'])) {
            $errors[] = 'Customer price type must be a numeric ID.';
        } else {
            $customerPriceTypeId = (int) $formData['customer_price_type_id'];
        }
    }

    $generatedCustomerCode = false;
    // Ensure customer code: generate if blank, validate format and uniqueness
    if ($formData['customer_code'] === '') {
        try {
            $formData['customer_code'] = generateCustomerCode($db);
            $generatedCustomerCode = true;
        } catch (Exception $e) {
            $errors[] = 'Unable to generate customer code.';
        }
    } else {
        if (!preg_match('/^[A-Za-z0-9-_]{1,30}$/', $formData['customer_code'])) {
            $errors[] = 'Customer code may only contain letters, numbers, dash or underscore and be max 30 characters.';
        }
    }

    if ($db && $formData['customer_code'] !== '') {
        try {
            $existingCode = $db->getRow('SELECT customer_id FROM customer WHERE customer_code = ? LIMIT 1', [$formData['customer_code']]);
            if ($existingCode) {
                $errors[] = 'Customer code already exists. Please use a different code.';
            }
        } catch (Exception $e) {
            $errors[] = 'Unable to validate customer code uniqueness.';
        }
    }

    // Validate repeat unit
    if ($formData['repeat_unit'] !== '') {
        $repeatUnits = getRepeatUnits();
        $validIds = array_column($repeatUnits, 'id');
        if (!in_array((int)$formData['repeat_unit'], $validIds)) {
            $errors[] = 'Invalid repeat unit selected.';
        }
    }

    if (empty($errors) && $db) {
        try {
            $inserted = $db->insertRow(
                'INSERT INTO customer (customer_code, customer_email, customer_password, is_active, locked, customer_title, customer_name, customer_nic, customer_avtive_code, customer_address, address_line_1, address_line_2, city, postal_code, customer_discount, customer_tell, customer_mobile, customer_note, customer_outstanding_balance, credit_limit, account_hold, abn_no, acn_no, vat_registered, gst_no, payment_terms_id, customer_logo, customer_price_type_id, new_customer, RepeatInterval, RepeatUnit, legal_name, trading_name, customer_remarks, min_order_amount, emergency_contact_name, emergency_contact_email, emergency_contact_telephone, custom_url_link, google_map_link, contact_name, contact_email, contact_telephone, line_discount_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, 0.00, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $formData['customer_code'] !== '' ? $formData['customer_code'] : null,
                    $formData['customer_email'] !== '' ? $formData['customer_email'] : null,
                    '',
                    $formData['is_active'],
                    $formData['locked'],

                    null,
                    $formData['customer_name'],
                    '',
                    '',
                    $formData['address_line_1'],
                    $formData['address_line_1'],
                    $formData['address_line_2'] !== '' ? $formData['address_line_2'] : null,
                    $formData['city'] !== '' ? $formData['city'] : null,
                    $formData['postal_code'] !== '' ? $formData['postal_code'] : null,
                    $formData['customer_phone'] !== '' ? $formData['customer_phone'] : null,
                    $formData['customer_mobile'] !== '' ? $formData['customer_mobile'] : null,
                    $formData['customer_note'] !== '' ? $formData['customer_note'] : null,
                    $creditLimitValue,
                    $formData['account_hold'],
                    $formData['abn_no'] !== '' ? $formData['abn_no'] : null,
                    $formData['acn_no'] !== '' ? $formData['acn_no'] : null,
                    $formData['vat_registered'],
                    $formData['gst_no'] !== '' ? $formData['gst_no'] : null,
                    $paymentTermsId,
                    null,
                    $customerPriceTypeId,
                    $formData['repeat_interval'] !== '' ? (int)$formData['repeat_interval'] : null,
                    $formData['repeat_unit'] !== '' ? $formData['repeat_unit'] : null,
                    $formData['legal_name'] !== '' ? $formData['legal_name'] : null,
                    $formData['trading_name'] !== '' ? $formData['trading_name'] : null,
                    $formData['customer_remarks'] !== '' ? $formData['customer_remarks'] : null,
                    $formData['min_order_amount'] !== '' ? $formData['min_order_amount'] : null,
                    $formData['emergency_contact_name'] !== '' ? $formData['emergency_contact_name'] : null,
                    $formData['emergency_contact_email'] !== '' ? $formData['emergency_contact_email'] : null,
                    $formData['emergency_contact_telephone'] !== '' ? $formData['emergency_contact_telephone'] : null,
                    $formData['custom_url_link'] !== '' ? $formData['custom_url_link'] : null,
                    $formData['google_map_link'] !== '' ? $formData['google_map_link'] : null,
                    $formData['contact_name'] !== '' ? $formData['contact_name'] : null,
                    $formData['contact_email'] !== '' ? $formData['contact_email'] : null,
                    $formData['contact_telephone'] !== '' ? $formData['contact_telephone'] : null,
                    $formData['line_discount_id'] !== '' ? (int)$formData['line_discount_id'] : null,
                ]
            );

            if ($inserted) {
                $row = $db->getRow('SELECT LAST_INSERT_ID() AS id');
                $customerId = (int) ($row['id'] ?? 0);

                if (!empty($_FILES['customer_logo']['name']) && $_FILES['customer_logo']['error'] === UPLOAD_ERR_OK) {
                    $allowedMime = ['image/jpeg', 'image/png'];
                    if (in_array($_FILES['customer_logo']['type'], $allowedMime, true)) {
                        $extension = strtolower(pathinfo($_FILES['customer_logo']['name'], PATHINFO_EXTENSION));
                        if (!in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
                            $extension = $extension === 'jpeg' ? 'jpg' : 'png';
                        }
                        $logoDir = dirname(__DIR__) . '/images/customer_logo';
                        if (!is_dir($logoDir)) {
                            mkdir($logoDir, 0777, true);
                        }
                        $fileName = 'customer_' . $customerId . '_' . time() . '.' . $extension;
                        $targetPath = $logoDir . '/' . $fileName;
                        if (move_uploaded_file($_FILES['customer_logo']['tmp_name'], $targetPath)) {
                            $logoDbPath = '../images/customer_logo/' . $fileName;
                            $db->updateRow('UPDATE customer SET customer_logo = ? WHERE customer_id = ?', [$logoDbPath, $customerId]);
                        }
                    }
                }

                if (!empty($_FILES['compliance_pdf']['name']) && $_FILES['compliance_pdf']['error'] === UPLOAD_ERR_OK) {
                    $extension = strtolower(pathinfo($_FILES['compliance_pdf']['name'], PATHINFO_EXTENSION));
                    if ($extension === 'pdf') {
                        $tmpName = $_FILES['compliance_pdf']['tmp_name'];
                        $fileSize = (int) ($_FILES['compliance_pdf']['size'] ?? 0);
                        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
                        $detectedMime = $finfo ? finfo_file($finfo, $tmpName) : ($_FILES['compliance_pdf']['type'] ?? '');
                        if ($finfo) {
                            finfo_close($finfo);
                        }

                        if ($fileSize > 0 && $fileSize <= 5 * 1024 * 1024 && ($detectedMime === '' || in_array($detectedMime, ['application/pdf', 'application/x-pdf'], true))) {
                            $complianceDir = dirname(__DIR__) . '/uploads/customer_compliance';
                            if (!is_dir($complianceDir)) {
                                mkdir($complianceDir, 0777, true);
                            }
                            $fileName = 'customer_' . $customerId . '_compliance_' . time() . '.pdf';
                            $targetPath = $complianceDir . '/' . $fileName;
                            if (move_uploaded_file($tmpName, $targetPath)) {
                                $dbPath = 'uploads/customer_compliance/' . $fileName;
                                $db->insertRow('INSERT INTO customer_compliance_documents (customer_id, file_path, file_name) VALUES (?, ?, ?)', [$customerId, $dbPath, $_FILES['compliance_pdf']['name']]);
                            }
                        }
                    }
                }

                if (!empty($formData['customer_additional_emails'])) {
                    foreach ($formData['customer_additional_emails'] as $additionalEmail) {
                        if (filter_var($additionalEmail, FILTER_VALIDATE_EMAIL)) {
                            $db->insertRow('INSERT INTO customer_email_accounts (customer_id, email_address) VALUES (?, ?)', [$customerId, $additionalEmail]);
                        }
                    }
                }

                if (!empty($formData['compliance_contact_emails'])) {
                    foreach ($formData['compliance_contact_emails'] as $complianceContactEmail) {
                        $db->insertRow('INSERT INTO customer_compliance_contact_emails (customer_id, email_address) VALUES (?, ?)', [$customerId, $complianceContactEmail]);
                    }
                }

                // Auto-create a default Primary shipping address from billing info
                foreach ($shippingData as $address) {
                    $shippingInserted = $db->insertRow(
                        'INSERT INTO customer_shipping_address (customer_id, address_label, address_line_1, address_line_2, city, postal_code, contact_no, is_default)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                        [
                            $customerId,
                            $address['label'] !== '' ? $address['label'] : 'Primary',
                            $address['address_line_1'],
                            $address['address_line_2'] !== '' ? $address['address_line_2'] : null,
                            $address['city'] !== '' ? $address['city'] : null,
                            $address['postal_code'] !== '' ? $address['postal_code'] : null,
                            $address['contact_no'] !== '' ? $address['contact_no'] : null,
                            1,
                        ]
                    );

                    if ($shippingInserted && !empty($address['additional_emails'])) {
                        $shippingAddressRow = $db->getRow('SELECT LAST_INSERT_ID() AS id');
                        $shippingAddressId = (int) ($shippingAddressRow['id'] ?? 0);
                        if ($shippingAddressId > 0) {
                            foreach ($address['additional_emails'] as $shippingAdditionalEmail) {
                                $db->insertRow('INSERT INTO shipping_address_additional_emails (shipping_address_id, email_address) VALUES (?, ?)', [$shippingAddressId, $shippingAdditionalEmail]);
                            }
                        }
                    }
                }

                foreach ($paymentOptions as $option) {
                    if ($option['type'] === 'card') {
                        $db->insertRow(
                            'INSERT INTO customer_payment_options (customer_id, payment_type, card_no, card_name, exp_month, exp_year) VALUES (?, ?, ?, ?, ?, ?)',
                            [$customerId, 'card', $option['card_no'], $option['card_name'], $option['exp_month'], $option['exp_year']]
                        );
                    } elseif ($option['type'] === 'bank') {
                        $db->insertRow(
                            'INSERT INTO customer_payment_options (customer_id, payment_type, bank_name, branch, account_no, account_holder) VALUES (?, ?, ?, ?, ?, ?)',
                            [$customerId, 'bank', $option['bank_name'], $option['branch'], $option['account_no'], $option['account_holder']]
                        );
                    }
                }

                // After successful create, redirect to edit page for the new customer
                header('Location: edit-customer.php?customerID=' . $customerId);
                exit;
            }
        } catch (Exception $e) {
            $status = false;
            $MessageClass = 'alert-danger';
            $message = 'Unable to save the customer: ' . $e->getMessage();
        }
    } else {
        if (!$MessageClass) {
            $MessageClass = 'alert-danger';
        }
        if (!empty($errors)) {
            $message = implode("\n", $errors);
        }
    }

    if (!$status && empty($message) && !empty($errors)) {
        $message = implode("\n", $errors);
        $MessageClass = 'alert-danger';
    }
}

if ($message !== '' && !$MessageClass) {
    $MessageClass = $status ? 'alert-success' : 'alert-danger';
}

?>
?>


<!DOCTYPE html>

<!--[if IE 8]> <html lang="en" class="ie8 no-js"> <![endif]-->
<!--[if IE 9]> <html lang="en" class="ie9 no-js"> <![endif]-->
<!--[if !IE]><!-->
<html lang="en">
<!--<![endif]-->
<!-- BEGIN HEAD -->


<head>
    <meta charset="utf-8" />
    <title>Add Customer</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <meta content="" name="description" />
    <meta content="" name="author" />
    <?php include('common/head.php'); ?>
    <link href="assets/global/plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/global/plugins/select2/css/select2-bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/global/plugins/summernote/summernote.css" rel="stylesheet" type="text/css" />
    <style>
        .section-card h4 {
            background: linear-gradient(to right, #0056a0, #007cba);
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .section-card {
            margin-bottom: 30px;
        }
        .form-actions { display: flex; justify-content: flex-end; gap: 12px; }

        .alert { margin-top: 20px; }
        @media (max-width: 767px) {
            .form-actions { flex-direction: column; align-items: stretch; }

        }
    </style>
</head>
<body class="page-sidebar-closed-hide-logo page-content-white">
      <?php include('common/manubar.php'); ?>
        <!-- BEGIN HEADER & CONTENT DIVIDER -->
        <div class="clearfix"> </div>
        <!-- END HEADER & CONTENT DIVIDER -->
        <!-- BEGIN CONTAINER -->
        <div class="page-container">
             <div class="page-sidebar-wrapper">
           <?php include('common/sidebar.php'); ?>
            
            </div>
            <!-- END SIDEBAR -->
            <!-- BEGIN CONTENT -->
            <div class="page-content-wrapper">
                <!-- BEGIN CONTENT BODY -->
                <div class="page-content">
                    <!-- BEGIN PAGE HEADER-->
          
                    <!-- BEGIN PAGE BAR -->
                    <div class="page-bar">
                        <ul class="page-breadcrumb">
                            <li>
                                <a href="index.php">Home</a>
                                <i class="fa fa-circle"></i>
                            </li>
                            <li>
                                <a href="#">Add Customer</a>
                               
                            </li>
                         
                        </ul>
                      
                    </div>
                    <!-- END PAGE BAR -->
                    <!-- BEGIN PAGE TITLE-->
                    <?php if ($message !== ''): ?>
                        <div class="alert <?php echo h($MessageClass); ?> alert-dismissable">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                            <?php echo nl2br(h($message)); ?>
                        </div>
                    <?php endif; ?>
                    <!-- END PAGE TITLE-->
                    <!-- END PAGE HEADER-->
                  
                    <div class="row">
                        <div class="col-md-12">
                        <div class="portlet box blue-hoki ">
                                            <div class="portlet-title">
                                                <div class="caption">
                                                    <i class="fa fa-gift"></i>Add Customer</div>
                                                <div class="tools">
                                                    <a href="javascript:;" class="collapse" data-original-title="" title=""> </a>
                                                    <a href="#portlet-config" data-toggle="modal" class="config" data-original-title="" title=""> </a>
                                                    <a href="javascript:;" class="reload" data-original-title="" title=""> </a>
                                                    <a href="javascript:;" class="remove" data-original-title="" title=""> </a>
                                                </div>
                                            </div>
                                            <div class="portlet-body form">
                                                <!-- BEGIN FORM-->
                                                <form id="addCustomerForm" action="" class="form-horizontal form-bordered form-row-stripped" method="POST" enctype="multipart/form-data">
                                                    <div class="form-body">
                                                        <div class="panel panel-primary" style="margin-bottom: 20px;">
                                                            <div class="panel-heading" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 15px;">
                                                                <h4 class="panel-title" style="margin: 0; font-size: 16px; font-weight: 600;">
                                                                    <i class="fa fa-user"></i> Basic Information
                                                                </h4>
                                                            </div>
                                                            <div class="panel-body" style="padding: 20px;">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <div style="border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 15px;">
                                                                            <h6 style="color: #667eea; margin-top: 0; margin-bottom: 10px;"><i class="fa fa-id-card"></i> Customer Details</h6>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Customer Code</label>
                                                                            <input type="text" class="form-control" name="customer_code" placeholder="Auto-generated if left blank">
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Customer Name <span style="color: red;">*</span></label>
                                                                            <input type="text" class="form-control" name="customer_name" placeholder="Customer Name" required>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Legal Name</label>
                                                                            <input type="text" class="form-control" name="legal_name" placeholder="Legal Name">
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Trading Name</label>
                                                                            <input type="text" class="form-control" name="trading_name" placeholder="Trading Name">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div style="border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 15px;">
                                                                            <h6 style="color: #667eea; margin-top: 0; margin-bottom: 10px;"><i class="fa fa-phone"></i> Contact Information</h6>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Email</label>
                                                                            <input type="email" class="form-control" name="customer_email" placeholder="Email">
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Additional Email Accounts</label>
                                                                            <div id="customer-additional-email-list">
                                                                                <?php
                                                                                $customerAdditionalEmails = $formData['customer_additional_emails'] ?? [];
                                                                                if (empty($customerAdditionalEmails)) {
                                                                                    $customerAdditionalEmails = [''];
                                                                                }
                                                                                foreach ($customerAdditionalEmails as $emailValue): ?>
                                                                                    <div class="input-group additional-email-row" style="margin-bottom: 8px;">
                                                                                        <input type="email" class="form-control" name="customer_additional_emails[]" value="<?php echo h($emailValue); ?>" placeholder="Additional email address">
                                                                                        <span class="input-group-btn">
                                                                                            <button type="button" class="btn btn-danger remove-additional-email" title="Remove"><i class="fa fa-trash"></i></button>
                                                                                        </span>
                                                                                    </div>
                                                                                <?php endforeach; ?>
                                                                            </div>
                                                                            <button type="button" class="btn btn-default btn-sm" id="add-customer-additional-email"><i class="fa fa-plus"></i> Add Email</button>
                                                                            <span class="help-block">Add other email addresses to send notifications to the customer.</span>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Contact Phone <span style="color: red;">*</span></label>
                                                                            <input type="text" class="form-control" name="customer_phone" placeholder="Contact Phone" required>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Contact Mobile</label>
                                                                            <input type="text" class="form-control" name="customer_mobile" placeholder="Contact Mobile">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-md-12">
                                                                        <div style="border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 15px;">
                                                                            <h6 style="color: #667eea; margin-top: 0; margin-bottom: 10px;"><i class="fa fa-sticky-note"></i> Additional Notes</h6>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Customer Remarks</label>
                                                                            <textarea class="form-control" name="customer_remarks" rows="3" placeholder="Customer Remarks"><?php echo h($formData['customer_remarks']); ?></textarea>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="panel panel-success" style="margin-bottom: 20px;">
                                                            <div class="panel-heading" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 12px 15px;">
                                                                <h4 class="panel-title" style="margin: 0; font-size: 16px; font-weight: 600;">
                                                                    <i class="fa fa-map-marker"></i> Primary Billing Address
                                                                </h4>
                                                            </div>
                                                            <div class="panel-body" style="padding: 20px;">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <div style="border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 15px;">
                                                                            <h6 style="color: #28a745; margin-top: 0; margin-bottom: 10px;"><i class="fa fa-home"></i> Address Details</h6>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Address Line 1 <span style="color: red;">*</span></label>
                                                                            <input type="text" class="form-control" name="address_line_1" placeholder="Address Line 1" required>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Address Line 2</label>
                                                                            <input type="text" class="form-control" name="address_line_2" placeholder="Address Line 2">
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">City / Town</label>
                                                                            <input type="text" class="form-control" name="city" placeholder="City / Town">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div style="border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 15px;">
                                                                            <h6 style="color: #28a745; margin-top: 0; margin-bottom: 10px;"><i class="fa fa-globe"></i> Location & Postal</h6>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">State</label>
                                                                            <input type="text" class="form-control" name="state" value="" placeholder="State">
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Country</label>
                                                                            <select class="form-control select2" name="country">
                                                                                <option value="">Select Country</option>
                                                                                <?php foreach ($countries as $country): ?>
                                                                                    <option value="<?php echo h($country['country_name']); ?>"><?php echo h($country['country_name']); ?></option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Postal Code</label>
                                                                            <input type="text" class="form-control" name="postal_code" placeholder="Postal Code">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-md-12">
                                                                        <div style="border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 15px;">
                                                                            <h6 style="color: #28a745; margin-top: 0; margin-bottom: 10px;"><i class="fa fa-image"></i> Branding</h6>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Customer Logo</label>
                                                                            <input type="file" class="form-control" name="customer_logo" accept="image/*">
                                                                            <span class="help-block">Upload customer logo (JPG, PNG)</span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="panel panel-warning" style="margin-bottom: 20px;">
                                                            <div class="panel-heading" style="background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%); color: white; padding: 12px 15px;">
                                                                <h4 class="panel-title" style="margin: 0; font-size: 16px; font-weight: 600;">
                                                                    <i class="fa fa-calculator"></i> Account & Compliance
                                                                </h4>
                                                            </div>
                                                            <div class="panel-body" style="padding: 20px;">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <div style="border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 15px;">
                                                                            <h6 style="color: #fd7e14; margin-top: 0; margin-bottom: 10px;"><i class="fa fa-credit-card"></i> Financial Settings</h6>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Credit Limit</label>
                                                                            <input type="text" class="form-control autonumeric" name="credit_limit" placeholder="Credit Limit">
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Line Discount (%)</label>
                                                                            <select class="form-control select2" name="line_discount_id">
                                                                                <option value="">-- No Line Discount --</option>
                                                                                <?php foreach ($discountCodes as $dc): ?>
                                                                                    <option value="<?php echo (int)$dc['id']; ?>">
                                                                                        <?php echo h($dc['code']); ?> &ndash; <?php echo h($dc['description']); ?> (<?php echo number_format((float)$dc['percentage'], 2); ?>%)
                                                                                    </option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Payment Terms</label>
                                                                            <select class="form-control select2" name="payment_terms_id">
                                                                                <option value="">Select Payment Terms</option>
                                                                                <?php foreach ($paymentTerms as $term): ?>
                                                                                    <option value="<?php echo h($term['payment_terms_id']); ?>"><?php echo h($term['payment_terms_name']); ?></option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Customer Price Type</label>
                                                                            <select class="form-control select2" name="customer_price_type_id">
                                                                                <option value="">Select Price Type</option>
                                                                                <?php foreach ($priceTypes as $type): ?>
                                                                                    <option value="<?php echo h($type['id']); ?>"><?php echo h($type['description']); ?></option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Min Order Amount</label>
                                                                            <input type="text" class="form-control autonumeric" name="min_order_amount" placeholder="Min Order Amount">
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">ABN No <span style="color: red;">*</span></label>
                                                                            <input type="text" class="form-control" name="abn_no" placeholder="ABN No" required>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">ACN No <span style="color: red;">*</span></label>
                                                                            <input type="text" class="form-control" name="acn_no" placeholder="ACN No" required>
                                                                        </div>
                                                                         <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Account Hold</label>
                                                                            <div class="checkbox-list">
                                                                                <label class="checkbox-inline">
                                                                                    <input type="checkbox" name="account_hold" value="1" <?php echo $formData['account_hold'] ? 'checked' : ''; ?>> Yes
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Contact Emails</label>
                                                                            <div id="compliance-contact-emails">
                                                                                <?php foreach ($formData['compliance_contact_emails'] as $complianceContactEmail): ?>
                                                                                    <div class="input-group compliance-contact-email-row" style="margin-bottom: 8px;">
                                                                                        <input type="email" class="form-control" name="compliance_contact_emails[]" value="<?php echo h($complianceContactEmail); ?>" placeholder="Contact email address">
                                                                                        <span class="input-group-btn">
                                                                                            <button type="button" class="btn btn-danger remove-compliance-contact-email"><i class="fa fa-trash"></i></button>
                                                                                        </span>
                                                                                    </div>
                                                                                <?php endforeach; ?>
                                                                            </div>
                                                                            <button type="button" class="btn btn-xs btn-primary" id="add-compliance-contact-email" style="margin-top: 5px;">
                                                                                <i class="fa fa-plus"></i> Add Email
                                                                            </button>
                                                                            <span class="help-block">Add contact email addresses for compliance notifications.</span>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Compliance PDF</label>
                                                                            <input type="file" class="form-control" name="compliance_pdf" accept=".pdf,application/pdf">
                                                                            <span class="help-block">Upload PDF only. Maximum size: 5MB. This file will be stored with the customer record.</span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div style="border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 15px;">
                                                                            <h6 style="color: #fd7e14; margin-top: 0; margin-bottom: 10px;"><i class="fa fa-user"></i> Contact Information</h6>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Contact Name <span style="color: red;">*</span></label>
                                                                            <input type="text" class="form-control" name="contact_name" placeholder="Contact Name" required>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Contact Email <span style="color: red;">*</span></label>
                                                                            <input type="email" class="form-control" name="contact_email" placeholder="Contact Email" required>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Contact Telephone <span style="color: red;">*</span></label>
                                                                            <input type="text" class="form-control" name="contact_telephone" placeholder="Contact Telephone" required>
                                                                        </div>
                                                                        <div style="border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 15px; margin-top: 15px;">
                                                                            <h6 style="color: #fd7e14; margin-top: 0; margin-bottom: 10px;"><i class="fa fa-phone"></i> Emergency Contact</h6>
                                                                        </div>
                                                                        <div class="row">
                                                                            <div class="col-md-12">
                                                                                <div class="form-group" style="margin-bottom: 10px;">
                                                                                    <label class="control-label" style="font-weight: 600; color: #555;">Emergency Contact Name</label>
                                                                                    <input type="text" class="form-control" name="emergency_contact_name" placeholder="Emergency Contact Name">
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-md-6">
                                                                                <div class="form-group" style="margin-bottom: 10px;">
                                                                                    <label class="control-label" style="font-weight: 600; color: #555;">Emergency Contact Email</label>
                                                                                    <input type="email" class="form-control" name="emergency_contact_email" placeholder="Emergency Contact Email">
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-md-6">
                                                                                <div class="form-group" style="margin-bottom: 10px;">
                                                                                    <label class="control-label" style="font-weight: 600; color: #555;">Emergency Contact Telephone</label>
                                                                                    <input type="text" class="form-control" name="emergency_contact_telephone" placeholder="Emergency Contact Telephone">
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                       
                                                                    </div>
                                                                </div>
                                                            </div>

                                                        <div class="panel panel-danger" style="margin-bottom: 20px;">
                                                            <div class="panel-heading" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 12px 15px;">
                                                                <h4 class="panel-title" style="margin: 0; font-size: 16px; font-weight: 600;">
                                                                    <i class="fa fa-cogs"></i> Status & Access
                                                                </h4>
                                                            </div>
                                                            <div class="panel-body" style="padding: 20px;">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <div style="border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 15px;">
                                                                            <h6 style="color: #dc3545; margin-top: 0; margin-bottom: 10px;"><i class="fa fa-toggle-on"></i> Account Status</h6>
                                                                        </div>
                                                                        <?php if ($canManageCustomerAccess): ?>
                                                                            <div class="alert alert-info" style="margin-bottom: 15px; padding: 10px 12px;">
                                                                                Only super admin can activate or lock customer accounts for ordering.
                                                                            </div>
                                                                            <div class="row">
                                                                                <div class="col-md-6">
                                                                                    <div class="form-group" style="margin-bottom: 10px;">
                                                                                        <label class="control-label" style="font-weight: 600; color: #555;">Active</label>
                                                                                        <div class="checkbox-list">
                                                                                            <label class="checkbox-inline">
                                                                                                <input type="checkbox" name="is_active" value="1" <?php echo $formData['is_active'] ? 'checked' : ''; ?>> Yes
                                                                                            </label>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-md-6">
                                                                                    <div class="form-group" style="margin-bottom: 10px;">
                                                                                        <label class="control-label" style="font-weight: 600; color: #555;">Locked</label>
                                                                                        <div class="checkbox-list">
                                                                                            <label class="checkbox-inline">
                                                                                                <input type="checkbox" name="locked" value="1" <?php echo $formData['locked'] ? 'checked' : ''; ?>> Yes
                                                                                            </label>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        <?php else: ?>
                                                                            <div class="alert alert-warning" style="margin-bottom: 15px; padding: 10px 12px;">
                                                                                New customers stay inactive until a super admin enables access. Account lock can also only be changed by super admin.
                                                                            </div>
                                                                            <div class="row">
                                                                                <div class="col-md-6">
                                                                                    <div class="form-group" style="margin-bottom: 10px;">
                                                                                        <label class="control-label" style="font-weight: 600; color: #555;">Active</label>
                                                                                        <div class="form-control-static">
                                                                                            <span class="label label-default">Pending Super Admin Approval</span>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-md-6">
                                                                                    <div class="form-group" style="margin-bottom: 10px;">
                                                                                        <label class="control-label" style="font-weight: 600; color: #555;">Locked</label>
                                                                                        <div class="form-control-static">
                                                                                            <span class="label label-success">No</span>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div style="border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 15px;">
                                                                            <h6 style="color: #dc3545; margin-top: 0; margin-bottom: 10px;"><i class="fa fa-clock"></i> Repeat Settings</h6>
                                                                        </div>
                                                                        <div class="row">
                                                                            <div class="col-md-6">
                                                                                <div class="form-group" style="margin-bottom: 10px;">
                                                                                    <label class="control-label" style="font-weight: 600; color: #555;">Repeat Interval</label>
                                                                                    <input type="number" class="form-control" name="repeat_interval" placeholder="e.g. 7" min="1">
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-md-6">
                                                                                <div class="form-group" style="margin-bottom: 10px;">
                                                                                    <label class="control-label" style="font-weight: 600; color: #555;">Repeat Unit</label>
                                                                                    <select class="form-control" name="repeat_unit">
                                                                                        <option value="">Select Unit</option>
                                                                                        <?php
                                                                                        $repeatUnits = getRepeatUnits();
                                                                                        foreach ($repeatUnits as $unit) {
                                                                                            echo '<option value="' . h($unit['id']) . '">' . h($unit['display_name']) . '</option>';
                                                                                        }
                                                                                        ?>
                                                                                    </select>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-md-12">
                                                                        <div style="border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 15px;">
                                                                            <h6 style="color: #dc3545; margin-top: 0; margin-bottom: 10px;"><i class="fa fa-sticky-note"></i> Additional Notes</h6>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Customer Note</label>
                                                                            <textarea class="form-control" rows="3" name="customer_note" placeholder="Enter any additional notes about the customer"></textarea>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                        <div class="panel panel-info" style="margin-bottom: 20px;">
                                                            <div class="panel-heading" style="background: linear-gradient(135deg, #17a2b8 0%, #20c997 100%); color: white; padding: 12px 15px;">
                                                                <h4 class="panel-title" style="margin: 0; font-size: 16px; font-weight: 600;">
                                                                    <i class="fa fa-truck"></i> Shipping Addresses
                                                                </h4>
                                                            </div>
                                                            <div class="panel-body" style="padding: 20px;">
                                                                <div class="alert alert-info" style="margin-bottom: 10px;">
                                                                    <i class="fa fa-info-circle"></i>
                                                                    <strong>Note:</strong> A default shipping address will be created automatically from the billing address.
                                                                    You can add notification emails for that default shipping address below. More shipping addresses, delivery availability, and delivery details can still be configured later in <strong>Edit Mode</strong>.
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-md-12">
                                                                        <div class="form-group" style="margin-bottom: 15px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Default Shipping Address Emails</label>
                                                                            <div class="shipping-additional-emails" id="shipping-additional-emails-0">
                                                                                <?php foreach ($formData['default_shipping_additional_emails'] as $shippingAdditionalEmail): ?>
                                                                                    <div class="input-group shipping-additional-email-row" style="margin-bottom: 8px;">
                                                                                        <input type="email" class="form-control" name="shipping_additional_emails[0][]" value="<?php echo h($shippingAdditionalEmail); ?>" placeholder="Additional email address">
                                                                                        <span class="input-group-btn">
                                                                                            <button type="button" class="btn btn-danger remove-shipping-additional-email"><i class="fa fa-trash"></i></button>
                                                                                        </span>
                                                                                    </div>
                                                                                <?php endforeach; ?>
                                                                            </div>
                                                                            <button type="button" class="btn btn-xs btn-primary add-shipping-additional-email" data-index="0" style="margin-top: 5px;">
                                                                                <i class="fa fa-plus"></i> Add Email
                                                                            </button>
                                                                            <span class="help-block">These emails are saved on the primary shipping address created from the billing address.</span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Custom URL Link</label>
                                                                            <input type="url" class="form-control" name="custom_url_link" placeholder="Custom URL Link">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Google Map Link</label>
                                                                            <input type="url" class="form-control" name="google_map_link" placeholder="Google Map Link">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="panel panel-default" style="margin-bottom: 20px;">
                                                            <div class="panel-heading" style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%); color: white; padding: 12px 15px;">
                                                                <h4 class="panel-title" style="margin: 0; font-size: 16px; font-weight: 600;">
                                                                    <i class="fa fa-credit-card"></i> Payment Options
                                                                </h4>
                                                            </div>
                                                            <div class="panel-body" style="padding: 20px;">
                                                                <p class="text-muted" style="margin-top: -10px;">Payment options are optional. Add payment methods only if you want to store them now.</p>
                                                                <div id="paymentOptions">
                                                                    <!-- Payment options will be added here -->
                                                                </div>
                                                                <button type="button" class="btn btn-default" id="addCardPayment"><i class="fa fa-plus"></i> Add Card Payment</button>
                                                                <button type="button" class="btn btn-default" id="addBankPayment"><i class="fa fa-plus"></i> Add Bank Payment</button>
                                                            </div>
                                                        </div>

                                                        <div class="form-actions">
                                                            <button type="submit" class="btn green" name="sub"><i class="fa fa-check"></i> Add Customer</button>
                                                        </div>
                                                    </div>
                                                </form>
                                                <!-- END FORM-->
                                            </div>
                                        </div>
                        </div>
                        
                    </div>
                  
                </div>
                <!-- END CONTENT BODY -->
            </div>
            <!-- END CONTENT -->
           
        </div>
        <!-- END CONTAINER -->
     <?php include('common/footer.php');?>
        <!--[if lt IE 9]>
<script src="assets/global/plugins/respond.min.js"></script>
<script src="assets/global/plugins/excanvas.min.js"></script> 
<![endif]-->
        <!-- BEGIN CORE PLUGINS -->
        <script src="assets/global/plugins/jquery.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/js.cookie.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/bootstrap-hover-dropdown/bootstrap-hover-dropdown.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/jquery-slimscroll/jquery.slimscroll.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/jquery.blockui.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/uniform/jquery.uniform.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/bootstrap-switch/js/bootstrap-switch.min.js" type="text/javascript"></script>
        <!-- END CORE PLUGINS -->
        <script src="assets/global/plugins/select2/js/select2.full.min.js" type="text/javascript"></script>
        <!-- END PAGE LEVEL PLUGINS -->
        <!-- BEGIN THEME GLOBAL SCRIPTS -->
        <script src="assets/global/scripts/app.min.js" type="text/javascript"></script>
        <!-- END THEME GLOBAL SCRIPTS -->
        <!-- BEGIN PAGE LEVEL SCRIPTS -->
        <script src="assets/pages/scripts/table-datatables-responsive.min.js" type="text/javascript"></script>
        <!-- END PAGE LEVEL SCRIPTS -->
        <!-- BEGIN THEME LAYOUT SCRIPTS -->
        <script src="assets/layouts/layout/scripts/layout.min.js" type="text/javascript"></script>
        <script src="assets/layouts/layout/scripts/demo.min.js" type="text/javascript"></script>
        <script src="assets/layouts/global/scripts/quick-sidebar.min.js" type="text/javascript"></script>
        <!-- END THEME LAYOUT SCRIPTS -->
        <script type="text/template" id="cardPaymentTemplate">
            <div class="payment-option-item" data-type="card" style="border: 1px solid #e7edf5; border-radius: 8px; padding: 16px; margin-bottom: 16px; background: #f9fbff;">
                <button type="button" class="btn btn-xs red remove-payment-option" style="position: absolute; top: 12px; right: 12px;"><i class="fa fa-trash"></i></button>
                <h5>Card Payment</h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Card Number</label>
                            <input type="text" class="form-control" name="card_no[]" placeholder="Card Number">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Name on Card</label>
                            <input type="text" class="form-control" name="card_name[]" placeholder="Name on Card">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Exp Month</label>
                            <select class="form-control" name="exp_month[]">
                                <option value="">Month</option>
                                <?php for($m=1; $m<=12; $m++): ?>
                                    <option value="<?php echo $m; ?>"><?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Exp Year</label>
                            <select class="form-control" name="exp_year[]">
                                <option value="">Year</option>
                                <?php for($y=date('Y'); $y<=date('Y')+10; $y++): ?>
                                    <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </script>
        <script type="text/template" id="bankPaymentTemplate">
            <div class="payment-option-item" data-type="bank" style="border: 1px solid #e7edf5; border-radius: 8px; padding: 16px; margin-bottom: 16px; background: #f9fbff;">
                <button type="button" class="btn btn-xs red remove-payment-option" style="position: absolute; top: 12px; right: 12px;"><i class="fa fa-trash"></i></button>
                <h5>Bank Payment</h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Bank Name</label>
                            <input type="text" class="form-control" name="bank_name[]" placeholder="Bank Name">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Branch</label>
                            <input type="text" class="form-control" name="branch[]" placeholder="Branch">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Account Number</label>
                            <input type="text" class="form-control" name="account_no[]" placeholder="Account Number">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Account Holder</label>
                            <input type="text" class="form-control" name="account_holder[]" placeholder="Account Holder Name">
                        </div>
                    </div>
                </div>
            </div>
        </script>
        <script>
            $(document).ready(function() {
                try { $('.select2').select2(); } catch (e) { console.warn('select2 init failed', e); }

                $('#addCardPayment').on('click', function() {
                    var template = $('#cardPaymentTemplate').html();
                    $('#paymentOptions').append(template);
                });

                $('#addBankPayment').on('click', function() {
                    var template = $('#bankPaymentTemplate').html();
                    $('#paymentOptions').append(template);
                });

                $('#paymentOptions').on('click', '.remove-payment-option', function() {
                    $(this).closest('.payment-option-item').remove();
                });

                $('#add-customer-additional-email').on('click', function() {
                    var template = $('#additionalCustomerEmailTemplate').html();
                    $('#customer-additional-email-list').append(template);
                });

                $('#customer-additional-email-list').on('click', '.remove-additional-email', function() {
                    if ($('#customer-additional-email-list .additional-email-row').length === 1) {
                        $(this).closest('.additional-email-row').find('input').val('');
                        return;
                    }
                    $(this).closest('.additional-email-row').remove();
                });

                $(document).on('click', '#add-compliance-contact-email', function(e) {
                    e.preventDefault();
                    var html = '<div class="input-group compliance-contact-email-row" style="margin-bottom: 8px;">' +
                        '<input type="email" class="form-control" name="compliance_contact_emails[]" placeholder="Contact email address">' +
                        '<span class="input-group-btn">' +
                            '<button type="button" class="btn btn-danger remove-compliance-contact-email"><i class="fa fa-trash"></i></button>' +
                        '</span>' +
                    '</div>';
                    $('#compliance-contact-emails').append(html);
                });

                $(document).on('click', '.remove-compliance-contact-email', function(e) {
                    e.preventDefault();
                    $(this).closest('.compliance-contact-email-row').remove();
                });

                $(document).on('click', '.add-shipping-additional-email', function(e) {
                    e.preventDefault();
                    var idx = $(this).data('index');
                    var html = '<div class="input-group shipping-additional-email-row" style="margin-bottom: 8px;">' +
                        '<input type="email" class="form-control" name="shipping_additional_emails[' + idx + '][]" placeholder="Additional email address">' +
                        '<span class="input-group-btn">' +
                            '<button type="button" class="btn btn-danger remove-shipping-additional-email"><i class="fa fa-trash"></i></button>' +
                        '</span>' +
                    '</div>';
                    $('#shipping-additional-emails-' + idx).append(html);
                });

                $(document).on('click', '.remove-shipping-additional-email', function(e) {
                    e.preventDefault();
                    $(this).closest('.shipping-additional-email-row').remove();
                });

            });
        </script>
        <script type="text/template" id="additionalCustomerEmailTemplate">
            <div class="input-group additional-email-row" style="margin-bottom: 8px;">
                <input type="email" class="form-control" name="customer_additional_emails[]" placeholder="Additional email address">
                <span class="input-group-btn">
                    <button type="button" class="btn btn-danger remove-additional-email" title="Remove"><i class="fa fa-trash"></i></button>
                </span>
            </div>
        </script>
</body>

</html>



