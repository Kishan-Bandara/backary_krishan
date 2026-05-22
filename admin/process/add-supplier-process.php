<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

session_start();
include('../include/database.php');
include('../include/check_login.php');

$message = "";
$db = new Database();

try {
    $db->getRows('CREATE TABLE IF NOT EXISTS `supplier_email_accounts` (
        `id` int(10) NOT NULL AUTO_INCREMENT,
        `supplier_id` int(10) NOT NULL,
        `email_address` varchar(255) NOT NULL,
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `supplier_id` (`supplier_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
    $db->getRows('CREATE TABLE IF NOT EXISTS `supplier_certification_documents` (
        `id` int(10) NOT NULL AUTO_INCREMENT,
        `supplier_id` int(10) NOT NULL,
        `file_path` varchar(255) DEFAULT NULL,
        `file_name` varchar(255) DEFAULT NULL,
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `supplier_id` (`supplier_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
} catch (Exception $e) {
    // Ignore if table cannot be created yet.
}

// Get form data
$formData = [
    'supplier_code' => trim($_POST['supplier_code'] ?? ''),
    'supplier_name' => trim($_POST['supplier_name'] ?? ''),
    'supplier_email' => trim($_POST['supplier_email'] ?? ''),
    'supplier_phone' => trim($_POST['supplier_phone'] ?? ''),
    'supplier_mobile' => trim($_POST['supplier_mobile'] ?? ''),
    'address_line_1' => trim($_POST['address_line_1'] ?? ''),
    'address_line_2' => trim($_POST['address_line_2'] ?? ''),
    'city' => trim($_POST['city'] ?? ''),
    'postal_code' => trim($_POST['postal_code'] ?? ''),
    'credit_limit' => trim($_POST['credit_limit'] ?? ''),
    'account_hold' => isset($_POST['account_hold']) ? 1 : 0,
    'abn_no' => trim($_POST['abn_no'] ?? ''),
    'acn_no' => trim($_POST['acn_no'] ?? ''),
    'vat_registered' => isset($_POST['vat_registered']) ? 1 : 0,
    'gst_no' => trim($_POST['gst_no'] ?? ''),
    'payment_terms_id' => trim($_POST['payment_terms_id'] ?? ''),
    'supplier_price_type_id' => trim($_POST['supplier_price_type_id'] ?? ''),
    'supplier_note' => trim($_POST['supplier_note'] ?? ''),
    'is_active' => isset($_POST['is_active']) ? 1 : 0,
    'locked' => isset($_POST['locked']) ? 1 : 0,
    'min_order_amount' => trim($_POST['min_order_amount'] ?? ''),
    'emergency_contact_name' => trim($_POST['emergency_contact_name'] ?? ''),
    'emergency_contact_email' => trim($_POST['emergency_contact_email'] ?? ''),
    'emergency_contact_telephone' => trim($_POST['emergency_contact_telephone'] ?? ''),
    'custom_url_link' => trim($_POST['custom_url_link'] ?? ''),
    'google_map_link' => trim($_POST['google_map_link'] ?? ''),
    'contact_name' => trim($_POST['contact_name'] ?? ''),
    'contact_email' => trim($_POST['contact_email'] ?? ''),
    'contact_telephone' => trim($_POST['contact_telephone'] ?? ''),
    'supplier_additional_emails' => array_filter(array_map('trim', (array)($_POST['supplier_additional_emails'] ?? []))),
    'legal_name' => trim($_POST['legal_name'] ?? ''),
    'trading_name' => trim($_POST['trading_name'] ?? ''),
    'supplier_remarks' => trim($_POST['supplier_remarks'] ?? ''),
];

// Generate supplier code if empty
if ($formData['supplier_code'] === '') {
    $lastSupplier = $db->getRow('SELECT supplier_id FROM supplier ORDER BY supplier_id DESC LIMIT 1');
    $nextId = $lastSupplier ? $lastSupplier['supplier_id'] + 1 : 1;
    $formData['supplier_code'] = 'SUPP-' . str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
}

// Validate required fields
$errors = [];
if (empty($formData['supplier_name'])) {
    $errors[] = 'Supplier name is required.';
}

// Validate credit limit
$creditLimitValue = 0.00;
if ($formData['credit_limit'] !== '') {
    $normalizedLimit = str_replace([',', ' '], '', $formData['credit_limit']);
    if (!is_numeric($normalizedLimit)) {
        $errors[] = 'Credit limit must be a valid number.';
    } else {
        $creditLimitValue = number_format((float) $normalizedLimit, 2, '.', '');
    }
}

// Validate payment terms
$paymentTermsId = null;
if ($formData['payment_terms_id'] !== '') {
    if (!ctype_digit($formData['payment_terms_id'])) {
        $errors[] = 'Payment terms must be a numeric ID.';
    } else {
        $paymentTermsId = (int) $formData['payment_terms_id'];
    }
}

// Validate supplier price type
$supplierPriceTypeId = null;
if ($formData['supplier_price_type_id'] !== '') {
    if (!ctype_digit($formData['supplier_price_type_id'])) {
        $errors[] = 'Supplier price type must be a numeric ID.';
    } else {
        $supplierPriceTypeId = (int) $formData['supplier_price_type_id'];
    }
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

$paymentOptions = [];
if (isset($_POST['card_no']) && is_array($_POST['card_no'])) {
    foreach ($_POST['card_no'] as $index => $cardNo) {
        if (!empty($cardNo)) {
            $paymentOptions[] = [
                'type' => 'card',
                'card_no' => trim($cardNo),
                'card_name' => trim($_POST['card_name'][$index] ?? ''),
                'exp_month' => trim($_POST['exp_month'][$index] ?? ''),
                'exp_year' => trim($_POST['exp_year'][$index] ?? ''),
            ];
        }
    }
}
if (isset($_POST['bank_name']) && is_array($_POST['bank_name'])) {
    foreach ($_POST['bank_name'] as $index => $bankName) {
        if (!empty($bankName)) {
            $paymentOptions[] = [
                'type' => 'bank',
                'bank_name' => trim($bankName),
                'branch' => trim($_POST['branch'][$index] ?? ''),
                'account_no' => trim($_POST['account_no'][$index] ?? ''),
                'account_holder' => trim($_POST['account_holder'][$index] ?? ''),
            ];
        }
    }
}

$bankPaymentCount = 0;
foreach ($paymentOptions as $option) {
    if ($option['type'] === 'bank') {
        if ($option['bank_name'] === '' || $option['account_no'] === '' || $option['account_holder'] === '') {
            $errors[] = 'Bank payment entries must include bank name, account number, and account holder name.';
            break;
        }
        $bankPaymentCount++;
    }
}
if ($bankPaymentCount === 0) {
    $errors[] = 'At least one bank payment method is required.';
}

foreach ($formData['supplier_additional_emails'] as $additionalEmail) {
    if ($additionalEmail !== '' && !filter_var($additionalEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid additional supplier email address: ' . $additionalEmail;
    }
}

$uploadedCertificationPdf = null;
if (isset($_FILES['certification_pdf']) && (int) ($_FILES['certification_pdf']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    if ((int) $_FILES['certification_pdf']['error'] !== UPLOAD_ERR_OK || empty($_FILES['certification_pdf']['name'])) {
        $errors[] = 'Unable to upload the certification PDF.';
    } else {
        $tmpName = $_FILES['certification_pdf']['tmp_name'];
        $originalName = $_FILES['certification_pdf']['name'];
        $fileSize = (int) ($_FILES['certification_pdf']['size'] ?? 0);
        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
        $detectedMime = $finfo ? finfo_file($finfo, $tmpName) : ($_FILES['certification_pdf']['type'] ?? '');
        if ($finfo) {
            finfo_close($finfo);
        }

        if ($extension !== 'pdf') {
            $errors[] = 'Certification document must be a PDF file.';
        } elseif ($detectedMime !== '' && $detectedMime !== 'application/octet-stream' && !in_array($detectedMime, ['application/pdf', 'application/x-pdf'], true)) {
            $errors[] = 'Certification document must be a valid PDF file.';
        } elseif ($fileSize <= 0 || $fileSize > 5 * 1024 * 1024) {
            $errors[] = 'Certification PDF must be 5MB or less.';
        } else {
            $uploadedCertificationPdf = [
                'tmp_name' => $tmpName,
                'original_name' => $originalName,
            ];
        }
    }
}

// Check for duplicate supplier code
if ($formData['supplier_code'] !== '') {
    try {
        $existingCode = $db->getRow('SELECT supplier_id FROM supplier WHERE supplier_code = ? LIMIT 1', [$formData['supplier_code']]);
        if ($existingCode) {
            $errors[] = 'Supplier code already exists. Please use a different code.';
        }
    } catch (Exception $e) {
        $errors[] = 'Unable to validate supplier code uniqueness.';
    }
}

// Process shipping addresses
$shippingData = [];
if (isset($_POST['shipping_label']) && is_array($_POST['shipping_label'])) {
    foreach ($_POST['shipping_label'] as $index => $label) {
        $shippingData[] = [
            'label' => trim($label),
            'address_line_1' => trim($_POST['shipping_address_line_1'][$index] ?? ''),
            'address_line_2' => trim($_POST['shipping_address_line_2'][$index] ?? ''),
            'city' => trim($_POST['shipping_city'][$index] ?? ''),
            'postal_code' => trim($_POST['shipping_postal_code'][$index] ?? ''),
            'contact_no' => trim($_POST['shipping_contact_no'][$index] ?? ''),
            'attribute_1' => trim($_POST['shipping_attribute_1'][$index] ?? ''),
            'attribute_2' => trim($_POST['shipping_attribute_2'][$index] ?? ''),
            'attribute_3' => trim($_POST['shipping_attribute_3'][$index] ?? ''),
            'is_default' => isset($_POST['shipping_default']) && $_POST['shipping_default'] === (string)$index ? 1 : 0,
            'contact_person_name' => trim($_POST['shipping_contact_person_name'][$index] ?? ''),
            'contact_person_phone' => trim($_POST['shipping_contact_person_phone'][$index] ?? ''),
            'contact_person_email' => trim($_POST['shipping_contact_person_email'][$index] ?? ''),
            'remarks' => trim($_POST['shipping_remarks'][$index] ?? ''),
            'note_to_deliver' => trim($_POST['shipping_note_to_deliver'][$index] ?? ''),
            'delivery_start_time' => trim($_POST['shipping_delivery_start_time'][$index] ?? ''),
            'delivery_end_time' => trim($_POST['shipping_delivery_end_time'][$index] ?? ''),
            'delivery_route_id' => trim($_POST['shipping_delivery_route_id'][$index] ?? ''),
        ];
    }
}

if (empty($errors)) {
    try {
        $inserted = $db->insertRow(
            'INSERT INTO supplier (supplier_code, supplier_name, supplier_email, supplier_contact_person, supplier_contact_no, supplier_mobile, supplier_address, address_line_1, address_line_2, city, postal_code, supplier_note, credit_limit, account_hold, abn_no, acn_no, vat_registered, gst_no, payment_terms_id, supplier_price_type_id, is_active, locked, min_order_amount, emergency_contact_name, emergency_contact_email, emergency_contact_telephone, custom_url_link, google_map_link, contact_name, contact_email, contact_telephone, legal_name, trading_name, supplier_remarks)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $formData['supplier_code'],
                $formData['supplier_name'],
                $formData['supplier_email'] !== '' ? $formData['supplier_email'] : null,
                $formData['contact_name'] !== '' ? $formData['contact_name'] : null,
                $formData['supplier_phone'] !== '' ? $formData['supplier_phone'] : null,
                $formData['supplier_mobile'] !== '' ? $formData['supplier_mobile'] : null,
                $formData['address_line_1'] !== '' ? $formData['address_line_1'] : null,
                $formData['address_line_1'] !== '' ? $formData['address_line_1'] : null,
                $formData['address_line_2'] !== '' ? $formData['address_line_2'] : null,
                $formData['city'] !== '' ? $formData['city'] : null,
                $formData['postal_code'] !== '' ? $formData['postal_code'] : null,
                $formData['supplier_note'] !== '' ? $formData['supplier_note'] : null,
                $creditLimitValue,
                $formData['account_hold'],
                $formData['abn_no'] !== '' ? $formData['abn_no'] : null,
                $formData['acn_no'] !== '' ? $formData['acn_no'] : null,
                $formData['vat_registered'],
                $formData['gst_no'] !== '' ? $formData['gst_no'] : null,
                $paymentTermsId,
                $supplierPriceTypeId,
                $formData['is_active'],
                $formData['locked'],
                $formData['min_order_amount'] !== '' ? $formData['min_order_amount'] : null,
                $formData['emergency_contact_name'] !== '' ? $formData['emergency_contact_name'] : null,
                $formData['emergency_contact_email'] !== '' ? $formData['emergency_contact_email'] : null,
                $formData['emergency_contact_telephone'] !== '' ? $formData['emergency_contact_telephone'] : null,
                $formData['custom_url_link'] !== '' ? $formData['custom_url_link'] : null,
                $formData['google_map_link'] !== '' ? $formData['google_map_link'] : null,
                $formData['contact_name'] !== '' ? $formData['contact_name'] : null,
                $formData['contact_email'] !== '' ? $formData['contact_email'] : null,
                $formData['contact_telephone'] !== '' ? $formData['contact_telephone'] : null,
                $formData['legal_name'] !== '' ? $formData['legal_name'] : null,
                $formData['trading_name'] !== '' ? $formData['trading_name'] : null,
                $formData['supplier_remarks'] !== '' ? $formData['supplier_remarks'] : null,
            ]
        );

        if ($inserted) {
            $row = $db->getRow('SELECT LAST_INSERT_ID() AS id');
            $supplierId = (int) ($row['id'] ?? 0);

            if (!empty($formData['supplier_additional_emails'])) {
                foreach ($formData['supplier_additional_emails'] as $additionalEmail) {
                    if ($additionalEmail === '') {
                        continue;
                    }
                    $db->insertRow(
                        'INSERT INTO supplier_email_accounts (supplier_id, email_address) VALUES (?, ?)',
                        [$supplierId, $additionalEmail]
                    );
                }
            }

            $certificationPdfMessage = '';
            if ($uploadedCertificationPdf) {
                $certificationDir = dirname(__DIR__) . '/uploads/supplier_certifications';
                if (!is_dir($certificationDir)) {
                    mkdir($certificationDir, 0777, true);
                }

                $storedFileName = 'supplier_' . $supplierId . '_certification_' . time() . '.pdf';
                $targetPath = $certificationDir . '/' . $storedFileName;

                if (move_uploaded_file($uploadedCertificationPdf['tmp_name'], $targetPath)) {
                    $dbPath = 'uploads/supplier_certifications/' . $storedFileName;
                    try {
                        $db->insertRow(
                            'INSERT INTO supplier_certification_documents (supplier_id, file_path, file_name) VALUES (?, ?, ?)',
                            [$supplierId, $dbPath, $uploadedCertificationPdf['original_name']]
                        );
                        $certificationPdfMessage = ' Certification PDF uploaded.';
                    } catch (Exception $e) {
                        if (file_exists($targetPath)) {
                            @unlink($targetPath);
                        }
                        $certificationPdfMessage = ' Certification PDF could not be linked.';
                    }
                } else {
                    $certificationPdfMessage = ' Certification PDF could not be saved.';
                }
            }

            // Insert shipping addresses
            foreach ($shippingData as $address) {
                $db->insertRow(
                    'INSERT INTO supplier_shipping_address (supplier_id, address_label, address_line_1, address_line_2, city, postal_code, contact_no, attribute_1, attribute_2, attribute_3, is_default, contact_person_name, contact_person_phone, contact_person_email, remarks, note_to_deliver, delivery_start_time, delivery_end_time, delivery_route_id)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $supplierId,
                        $address['label'] !== '' ? $address['label'] : null,
                        $address['address_line_1'],
                        $address['address_line_2'] !== '' ? $address['address_line_2'] : null,
                        $address['city'] !== '' ? $address['city'] : null,
                        $address['postal_code'] !== '' ? $address['postal_code'] : null,
                        $address['contact_no'] !== '' ? $address['contact_no'] : null,
                        $address['attribute_1'] !== '' ? $address['attribute_1'] : null,
                        $address['attribute_2'] !== '' ? $address['attribute_2'] : null,
                        $address['attribute_3'] !== '' ? $address['attribute_3'] : null,
                        $address['is_default'],
                        $address['contact_person_name'] !== '' ? $address['contact_person_name'] : null,
                        $address['contact_person_phone'] !== '' ? $address['contact_person_phone'] : null,
                        $address['contact_person_email'] !== '' ? $address['contact_person_email'] : null,
                        $address['remarks'] !== '' ? $address['remarks'] : null,
                        $address['note_to_deliver'] !== '' ? $address['note_to_deliver'] : null,
                        $address['delivery_start_time'] !== '' ? $address['delivery_start_time'] : null,
                        $address['delivery_end_time'] !== '' ? $address['delivery_end_time'] : null,
                        $address['delivery_route_id'] !== '' ? (int)$address['delivery_route_id'] : null,
                    ]
                );
            }

            // Insert payment options
            foreach ($paymentOptions as $option) {
                if ($option['type'] === 'card') {
                    $db->insertRow(
                        'INSERT INTO supplier_payment_options (supplier_id, payment_type, card_no, card_name, exp_month, exp_year) VALUES (?, ?, ?, ?, ?, ?)',
                        [$supplierId, 'card', $option['card_no'], $option['card_name'], $option['exp_month'], $option['exp_year']]
                    );
                } elseif ($option['type'] === 'bank') {
                    $db->insertRow(
                        'INSERT INTO supplier_payment_options (supplier_id, payment_type, bank_name, branch, account_no, account_holder) VALUES (?, ?, ?, ?, ?, ?)',
                        [$supplierId, 'bank', $option['bank_name'], $option['branch'], $option['account_no'], $option['account_holder']]
                    );
                }
            }

            $message = 'Supplier created successfully.' . $certificationPdfMessage;
        } else {
            $message = "Failed to create supplier.";
        }
    } catch (Exception $e) {
        $message = "Error creating supplier: " . $e->getMessage();
    }
} else {
    $message = "Validation errors: " . implode(', ', $errors);
}

echo $message;
?>



