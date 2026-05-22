<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

session_start();
include('include/database.php');
include('include/check_login.php');

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$message = '';
$MessageClass = '';
$status = false;
$db = null;

$customerId = isset($_GET['customerID']) ? (int) $_GET['customerID'] : 0;

if ($customerId <= 0) {
    header('location:index.php');
    exit();
}

try {
    $db = new Database();
} catch (Exception $e) {
    $message = 'Database connection error: ' . $e->getMessage();
    $MessageClass = 'alert-danger';
}

$customer = null;

if ($db) {
    try {
        $customer = $db->getRow('SELECT * FROM customer WHERE customer_id = ?', [$customerId]);
    } catch (Exception $e) {
        $message = 'Unable to load customer record: ' . $e->getMessage();
        $MessageClass = 'alert-danger';
    }

    if (!$customer) {
        header('location:index.php');
        exit();
    }

    if ((int) ($customer['new_customer'] ?? 0) === 0) {
        try {
            $db->updateRow('UPDATE customer SET new_customer = 1 WHERE customer_id = ?', [$customerId]);
            $customer['new_customer'] = 1;
        } catch (Exception $e) {
            // Silently ignore failures when toggling the new_customer flag
        }
    }
}

$formData = [
    'customer_code' => $customer['customer_code'] ?? '',
    'customer_name' => $customer['customer_name'] ?? '',
    'customer_email' => $customer['customer_email'] ?? '',
    'customer_phone' => $customer['customer_tell'] ?? '',
    'customer_mobile' => $customer['customer_mobile'] ?? '',
    'address_line_1' => $customer['address_line_1'] ?? ($customer['customer_address'] ?? ''),
    'address_line_2' => $customer['address_line_2'] ?? '',
    'city' => $customer['city'] ?? '',
    'postal_code' => $customer['postal_code'] ?? '',
    'credit_limit' => $customer['credit_limit'] !== null ? number_format((float) $customer['credit_limit'], 2, '.', '') : '0.00',
    'account_hold' => (int) ($customer['account_hold'] ?? 0),
    'abn_no' => $customer['abn_no'] ?? '',
    'acn_no' => $customer['acn_no'] ?? '',
    'vat_registered' => (int) ($customer['vat_registered'] ?? 0),
    'gst_no' => $customer['gst_no'] ?? '',
    'payment_terms_id' => $customer['payment_terms_id'] !== null ? (string) $customer['payment_terms_id'] : '',
    'customer_price_type_id' => $customer['customer_price_type_id'] !== null ? (string) $customer['customer_price_type_id'] : '',
    'customer_note' => $customer['customer_note'] ?? '',
    'is_active' => (int) ($customer['is_active'] ?? 1),
    'locked' => (int) ($customer['locked'] ?? 0),
    'customer_logo' => $customer['customer_logo'] ?? '',
    'customer_outstanding_balance' => $customer['customer_outstanding_balance'] ?? 0,
];

$shippingFormData = [];

if ($db && $customer) {
    try {
        $rows = $db->getRows(
            'SELECT * FROM customer_shipping_address WHERE customer_id = ? ORDER BY is_default DESC, id ASC',
            [$customerId]
        );
    } catch (Exception $e) {
        $rows = [];
    }

    if (!empty($rows)) {
        foreach ($rows as $row) {
            $shippingFormData[] = [
                'id' => (int) ($row['id'] ?? 0),
                'label' => $row['address_label'] ?? '',
                'address_line_1' => $row['address_line_1'] ?? '',
                'address_line_2' => $row['address_line_2'] ?? '',
                'city' => $row['city'] ?? '',
                'postal_code' => $row['postal_code'] ?? '',
                'contact_no' => $row['contact_no'] ?? '',
                'attribute_1' => $row['attribute_1'] ?? '',
                'attribute_2' => $row['attribute_2'] ?? '',
                'attribute_3' => $row['attribute_3'] ?? '',
                'is_default' => (int) ($row['is_default'] ?? 0),
            ];
        }
    }
}

if (empty($shippingFormData)) {
    $shippingFormData[] = [
        'id' => 0,
        'label' => 'Primary',
        'address_line_1' => $formData['address_line_1'],
        'address_line_2' => $formData['address_line_2'],
        'city' => $formData['city'],
        'postal_code' => $formData['postal_code'],
        'contact_no' => $formData['customer_phone'],
        'attribute_1' => '',
        'attribute_2' => '',
        'attribute_3' => '',
        'is_default' => 1,
    ];
}

$originalShippingIds = array_values(array_filter(array_map(
    static fn($address) => (int) $address['id'],
    $shippingFormData
)));

$newLogoUploaded = false;
$newLogoPath = null;
$paymentTermsId = $formData['payment_terms_id'] !== '' ? (int) $formData['payment_terms_id'] : null;
$customerPriceTypeId = $formData['customer_price_type_id'] !== '' ? (int) $formData['customer_price_type_id'] : null;
$creditLimitValue = $formData['credit_limit'] !== '' ? $formData['credit_limit'] : '0.00';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    $formData['customer_code'] = trim($_POST['customer_code'] ?? $formData['customer_code']);
    $formData['customer_name'] = trim($_POST['customer_name'] ?? $formData['customer_name']);
    $formData['customer_email'] = trim($_POST['customer_email'] ?? $formData['customer_email']);
    $formData['customer_phone'] = trim($_POST['customer_phone'] ?? $formData['customer_phone']);
    $formData['customer_mobile'] = trim($_POST['customer_mobile'] ?? $formData['customer_mobile']);
    $formData['address_line_1'] = trim($_POST['address_line_1'] ?? $formData['address_line_1']);
    $formData['address_line_2'] = trim($_POST['address_line_2'] ?? $formData['address_line_2']);
    $formData['city'] = trim($_POST['city'] ?? $formData['city']);
    $formData['postal_code'] = trim($_POST['postal_code'] ?? $formData['postal_code']);
    $formData['credit_limit'] = trim($_POST['credit_limit'] ?? $formData['credit_limit']);
    $formData['account_hold'] = isset($_POST['account_hold']) ? 1 : 0;
    $formData['abn_no'] = trim($_POST['abn_no'] ?? $formData['abn_no']);
    $formData['acn_no'] = trim($_POST['acn_no'] ?? $formData['acn_no']);
    $formData['vat_registered'] = isset($_POST['vat_registered']) ? 1 : 0;
    $formData['gst_no'] = trim($_POST['gst_no'] ?? $formData['gst_no']);
    $formData['payment_terms_id'] = trim($_POST['payment_terms_id'] ?? $formData['payment_terms_id']);
    $formData['customer_price_type_id'] = trim($_POST['customer_price_type_id'] ?? $formData['customer_price_type_id']);
    $formData['customer_note'] = trim($_POST['customer_note'] ?? $formData['customer_note']);
    $formData['is_active'] = isset($_POST['is_active']) ? 1 : 0;
    $formData['locked'] = isset($_POST['locked']) ? 1 : 0;

    if ($formData['customer_code'] === '') {
        $errors[] = 'Customer code is required.';
    }

    if ($formData['customer_name'] === '') {
        $errors[] = 'Customer name is required.';
    }

    if ($formData['address_line_1'] === '') {
        $errors[] = 'Address line 1 is required.';
    }

    if ($formData['credit_limit'] !== '') {
        $normalizedLimit = str_replace([',', ' '], '', $formData['credit_limit']);
        if (!is_numeric($normalizedLimit)) {
            $errors[] = 'Credit limit must be a valid number.';
        } else {
            $creditLimitValue = number_format((float) $normalizedLimit, 2, '.', '');
        }
    } else {
        $creditLimitValue = '0.00';
    }

    if ($formData['payment_terms_id'] !== '') {
        if (!ctype_digit($formData['payment_terms_id'])) {
            $errors[] = 'Payment terms must be a numeric ID.';
        } else {
            $paymentTermsId = (int) $formData['payment_terms_id'];
        }
    } else {
        $paymentTermsId = null;
    }

    if ($formData['customer_price_type_id'] !== '') {
        if (!ctype_digit($formData['customer_price_type_id'])) {
            $errors[] = 'Customer price type must be a numeric ID.';
        } else {
            $customerPriceTypeId = (int) $formData['customer_price_type_id'];
        }
    } else {
        $customerPriceTypeId = null;
    }

    $shippingIds = $_POST['shipping_id'] ?? [];
    $shippingLabels = $_POST['shipping_label'] ?? [];
    $shippingLine1 = $_POST['shipping_address_line_1'] ?? [];
    $shippingLine2 = $_POST['shipping_address_line_2'] ?? [];
    $shippingCity = $_POST['shipping_city'] ?? [];
    $shippingPostal = $_POST['shipping_postal_code'] ?? [];
    $shippingContact = $_POST['shipping_contact_no'] ?? [];
    $shippingAttr1 = $_POST['shipping_attribute_1'] ?? [];
    $shippingAttr2 = $_POST['shipping_attribute_2'] ?? [];
    $shippingAttr3 = $_POST['shipping_attribute_3'] ?? [];
    $defaultIndex = $_POST['shipping_default'] ?? '';

    $shippingCollected = [];

    foreach ($shippingLine1 as $idx => $line1) {
        $line1 = trim((string) $line1);
        $existingId = isset($shippingIds[$idx]) ? (int) $shippingIds[$idx] : 0;

        if ($line1 === '' && $existingId === 0) {
            continue;
        }

        if ($line1 === '' && $existingId > 0) {
            continue;
        }

        if ($line1 === '') {
            $errors[] = 'Shipping address line 1 is required for all entries.';
            continue;
        }

        $shippingCollected[] = [
            'id' => $existingId,
            'label' => trim($shippingLabels[$idx] ?? ''),
            'address_line_1' => $line1,
            'address_line_2' => trim($shippingLine2[$idx] ?? ''),
            'city' => trim($shippingCity[$idx] ?? ''),
            'postal_code' => trim($shippingPostal[$idx] ?? ''),
            'contact_no' => trim($shippingContact[$idx] ?? ''),
            'attribute_1' => trim($shippingAttr1[$idx] ?? ''),
            'attribute_2' => trim($shippingAttr2[$idx] ?? ''),
            'attribute_3' => trim($shippingAttr3[$idx] ?? ''),
            'is_default' => ((string) $defaultIndex === (string) $idx) ? 1 : 0,
        ];
    }

    if (empty($shippingCollected)) {
        $shippingCollected[] = [
            'id' => 0,
            'label' => ($formData['customer_name'] !== '' ? $formData['customer_name'] . ' - Primary' : 'Primary'),
            'address_line_1' => $formData['address_line_1'],
            'address_line_2' => $formData['address_line_2'],
            'city' => $formData['city'],
            'postal_code' => $formData['postal_code'],
            'contact_no' => $formData['customer_phone'],
            'attribute_1' => '',
            'attribute_2' => '',
            'attribute_3' => '',
            'is_default' => 1,
        ];
    } else {
        $hasDefault = false;
        foreach ($shippingCollected as $i => $address) {
            if ($address['is_default']) {
                if (!$hasDefault) {
                    $hasDefault = true;
                } else {
                    $shippingCollected[$i]['is_default'] = 0;
                }
            }
        }
        if (!$hasDefault) {
            $shippingCollected[0]['is_default'] = 1;
        }
    }

    if ($db && $formData['customer_code'] !== '' && $formData['customer_code'] !== ($customer['customer_code'] ?? '')) {
        try {
            $existingCode = $db->getRow(
                'SELECT customer_id FROM customer WHERE customer_code = ? AND customer_id <> ? LIMIT 1',
                [$formData['customer_code'], $customerId]
            );
            if ($existingCode) {
                $errors[] = 'Customer code already exists. Please choose a different code.';
            }
        } catch (Exception $e) {
            $errors[] = 'Unable to validate the customer code.';
        }
    }

    $customerLogoPathOriginal = $customer['customer_logo'] ?? null;
    $customerLogoPathToSave = $customerLogoPathOriginal;

    if (!empty($_FILES['customer_logo']['name']) && $_FILES['customer_logo']['error'] === UPLOAD_ERR_OK) {
        $allowedExtensions = ['jpg', 'jpeg', 'png'];
        $extension = strtolower(pathinfo($_FILES['customer_logo']['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExtensions, true)) {
            $errors[] = 'Customer logo must be a JPG or PNG file.';
        } else {
            $logoDir = dirname(__DIR__) . '/images/customer_logo';
            if (!is_dir($logoDir) && !mkdir($logoDir, 0777, true) && !is_dir($logoDir)) {
                $errors[] = 'Unable to create directory for customer logos.';
            } else {
                $extension = $extension === 'jpeg' ? 'jpg' : $extension;
                $fileName = 'customer_' . $customerId . '_' . time() . '.' . $extension;
                $targetPath = $logoDir . '/' . $fileName;
                if (!move_uploaded_file($_FILES['customer_logo']['tmp_name'], $targetPath)) {
                    $errors[] = 'Failed to upload the customer logo.';
                } else {
                    $newLogoUploaded = true;
                    $newLogoPath = 'images/customer_logo/' . $fileName;
                    $customerLogoPathToSave = $newLogoPath;
                }
            }
        }
    }

    $originalIdsInput = trim($_POST['original_shipping_ids'] ?? implode(',', $originalShippingIds));
    $originalIds = array_filter(array_map('intval', array_filter(explode(',', $originalIdsInput))));
    $providedIds = array_values(array_filter(array_map(
        static fn($address) => (int) ($address['id'] ?? 0),
        $shippingCollected
    )));
    $deleteIds = array_diff($originalIds, $providedIds);

    if (empty($errors) && $db) {
        try {
            $db->updateRow(
                'UPDATE customer
                 SET customer_code = ?, customer_email = ?, is_active = ?, locked = ?, customer_name = ?, customer_address = ?, address_line_1 = ?, address_line_2 = ?, city = ?, postal_code = ?, customer_tell = ?, customer_mobile = ?, customer_note = ?, credit_limit = ?, account_hold = ?, abn_no = ?, acn_no = ?, vat_registered = ?, gst_no = ?, payment_terms_id = ?, customer_price_type_id = ?, customer_logo = ?
                 WHERE customer_id = ?',
                [
                    $formData['customer_code'] !== '' ? $formData['customer_code'] : null,
                    $formData['customer_email'] !== '' ? $formData['customer_email'] : null,
                    $formData['is_active'],
                    $formData['locked'],
                    $formData['customer_name'] !== '' ? $formData['customer_name'] : null,
                    $formData['address_line_1'] !== '' ? $formData['address_line_1'] : null,
                    $formData['address_line_1'] !== '' ? $formData['address_line_1'] : null,
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
                    $customerPriceTypeId,
                    $customerLogoPathToSave,
                    $customerId,
                ]
            );

            if (!empty($deleteIds)) {
                $placeholders = implode(',', array_fill(0, count($deleteIds), '?'));
                $params = array_merge(array_values($deleteIds), [$customerId]);
                $db->deleteRow(
                    "DELETE FROM customer_shipping_address WHERE id IN ($placeholders) AND customer_id = ?",
                    $params
                );
            }

            foreach ($shippingCollected as $address) {
                if ($address['id'] > 0) {
                    $db->updateRow(
                        'UPDATE customer_shipping_address
                         SET address_label = ?, address_line_1 = ?, address_line_2 = ?, city = ?, postal_code = ?, contact_no = ?, attribute_1 = ?, attribute_2 = ?, attribute_3 = ?, is_default = ?
                         WHERE id = ? AND customer_id = ?',
                        [
                            $address['label'] !== '' ? $address['label'] : null,
                            $address['address_line_1'],
                            $address['address_line_2'] !== '' ? $address['address_line_2'] : null,
                            $address['city'] !== '' ? $address['city'] : null,
                            $address['postal_code'] !== '' ? $address['postal_code'] : null,
                            $address['contact_no'] !== '' ? $address['contact_no'] : null,
                            $address['attribute_1'] !== '' ? $address['attribute_1'] : null,
                            $address['attribute_2'] !== '' ? $address['attribute_2'] : null,
                            $address['attribute_3'] !== '' ? $address['attribute_3'] : null,
                            $address['is_default'] ? 1 : 0,
                            $address['id'],
                            $customerId,
                        ]
                    );
                } else {
                    $db->insertRow(
                        'INSERT INTO customer_shipping_address (customer_id, address_label, address_line_1, address_line_2, city, postal_code, contact_no, attribute_1, attribute_2, attribute_3, is_default)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                        [
                            $customerId,
                            $address['label'] !== '' ? $address['label'] : null,
                            $address['address_line_1'],
                            $address['address_line_2'] !== '' ? $address['address_line_2'] : null,
                            $address['city'] !== '' ? $address['city'] : null,
                            $address['postal_code'] !== '' ? $address['postal_code'] : null,
                            $address['contact_no'] !== '' ? $address['contact_no'] : null,
                            $address['attribute_1'] !== '' ? $address['attribute_1'] : null,
                            $address['attribute_2'] !== '' ? $address['attribute_2'] : null,
                            $address['attribute_3'] !== '' ? $address['attribute_3'] : null,
                            $address['is_default'] ? 1 : 0,
                        ]
                    );
                }
            }

            if ($newLogoUploaded && $customerLogoPathOriginal && $customerLogoPathOriginal !== $customerLogoPathToSave) {
                $oldPath = dirname(__DIR__) . '/' . $customerLogoPathOriginal;
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }

            $customer = $db->getRow('SELECT * FROM customer WHERE customer_id = ?', [$customerId]);
            $rows = $db->getRows(
                'SELECT * FROM customer_shipping_address WHERE customer_id = ? ORDER BY is_default DESC, id ASC',
                [$customerId]
            );

            $formData['customer_code'] = $customer['customer_code'] ?? '';
            $formData['customer_name'] = $customer['customer_name'] ?? '';
            $formData['customer_email'] = $customer['customer_email'] ?? '';
            $formData['customer_phone'] = $customer['customer_tell'] ?? '';
            $formData['customer_mobile'] = $customer['customer_mobile'] ?? '';
            $formData['address_line_1'] = $customer['address_line_1'] ?? ($customer['customer_address'] ?? '');
            $formData['address_line_2'] = $customer['address_line_2'] ?? '';
            $formData['city'] = $customer['city'] ?? '';
            $formData['postal_code'] = $customer['postal_code'] ?? '';
            $formData['credit_limit'] = $customer['credit_limit'] !== null ? number_format((float) $customer['credit_limit'], 2, '.', '') : '0.00';
            $formData['account_hold'] = (int) ($customer['account_hold'] ?? 0);
            $formData['abn_no'] = $customer['abn_no'] ?? '';
            $formData['acn_no'] = $customer['acn_no'] ?? '';
            $formData['vat_registered'] = (int) ($customer['vat_registered'] ?? 0);
            $formData['gst_no'] = $customer['gst_no'] ?? '';
            $formData['payment_terms_id'] = $customer['payment_terms_id'] !== null ? (string) $customer['payment_terms_id'] : '';
            $formData['customer_price_type_id'] = $customer['customer_price_type_id'] !== null ? (string) $customer['customer_price_type_id'] : '';
            $formData['customer_note'] = $customer['customer_note'] ?? '';
            $formData['is_active'] = (int) ($customer['is_active'] ?? 1);
            $formData['locked'] = (int) ($customer['locked'] ?? 0);
            $formData['customer_logo'] = $customer['customer_logo'] ?? '';
            $formData['customer_outstanding_balance'] = $customer['customer_outstanding_balance'] ?? 0;

            $shippingFormData = [];
            if (!empty($rows)) {
                foreach ($rows as $row) {
                    $shippingFormData[] = [
                        'id' => (int) ($row['id'] ?? 0),
                        'label' => $row['address_label'] ?? '',
                        'address_line_1' => $row['address_line_1'] ?? '',
                        'address_line_2' => $row['address_line_2'] ?? '',
                        'city' => $row['city'] ?? '',
                        'postal_code' => $row['postal_code'] ?? '',
                        'contact_no' => $row['contact_no'] ?? '',
                        'attribute_1' => $row['attribute_1'] ?? '',
                        'attribute_2' => $row['attribute_2'] ?? '',
                        'attribute_3' => $row['attribute_3'] ?? '',
                        'is_default' => (int) ($row['is_default'] ?? 0),
                    ];
                }
            }

            if (empty($shippingFormData)) {
                $shippingFormData[] = [
                    'id' => 0,
                    'label' => 'Primary',
                    'address_line_1' => $formData['address_line_1'],
                    'address_line_2' => $formData['address_line_2'],
                    'city' => $formData['city'],
                    'postal_code' => $formData['postal_code'],
                    'contact_no' => $formData['customer_phone'],
                    'attribute_1' => '',
                    'attribute_2' => '',
                    'attribute_3' => '',
                    'is_default' => 1,
                ];
            }

            $originalShippingIds = array_values(array_filter(array_map(
                static fn($address) => (int) $address['id'],
                $shippingFormData
            )));

            $status = true;
            $message = 'Customer updated successfully.';
            $MessageClass = 'alert-success';
        } catch (Exception $e) {
            $MessageClass = 'alert-danger';
            $message = 'Unable to update the customer: ' . $e->getMessage();
            $status = false;

            if ($newLogoUploaded && $newLogoPath) {
                $newLogoAbsolute = dirname(__DIR__) . '/' . $newLogoPath;
                if (is_file($newLogoAbsolute)) {
                    @unlink($newLogoAbsolute);
                }
            }
        }
    } else {
        if ($newLogoUploaded && $newLogoPath) {
            $newLogoAbsolute = dirname(__DIR__) . '/' . $newLogoPath;
            if (is_file($newLogoAbsolute)) {
                @unlink($newLogoAbsolute);
            }
        }

        if (empty($message) && !empty($errors)) {
            $MessageClass = 'alert-danger';
            $message = implode("\n", $errors);
        }
    }
}

$invoiceHistory = [];

if ($db) {
    try {
        $invoiceHistory = $db->getRows(
            'SELECT * FROM invoice_hedder WHERE invoice_h_customer_id = ? ORDER BY invoice_h_id DESC',
            [$customerId]
        );
    } catch (Exception $e) {
        $invoiceHistory = [];
    }
}

$creditLimitDisplay = 'LKR ' . number_format((float) $formData['credit_limit'], 2);
$outstandingDisplay = 'LKR ' . number_format((float) ($formData['customer_outstanding_balance'] ?? 0), 2);
$accountHoldLabel = $formData['account_hold'] ? 'On Hold' : 'Open';
$vatRegisteredLabel = $formData['vat_registered'] ? 'Registered' : 'Not Registered';
$isActiveLabel = $formData['is_active'] ? 'Active' : 'Inactive';
$lockedLabel = $formData['locked'] ? 'Locked' : 'Unlocked';

?>
<!DOCTYPE html>
<!--[if IE 8]> <html lang="en" class="ie8 no-js"> <![endif]-->
<!--[if IE 9]> <html lang="en" class="ie9 no-js"> <![endif]-->
<!--[if !IE]><!-->
<html lang="en">
<!--<![endif]-->
<head>
    <meta charset="utf-8" />
    <title>Customer</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <meta content="" name="description" />
    <meta content="" name="author" />
    <?php include('common/head.php'); ?>
    <style>
        .section-card { background: #ffffff; border: 1px solid #dde3ec; border-radius: 8px; padding: 24px; box-shadow: 0 6px 18px rgba(52, 73, 94, 0.08); margin-bottom: 24px; }
        .section-card h4 { margin-top: 0; margin-bottom: 18px; font-size: 15px; font-weight: 600; letter-spacing: 0.06em; text-transform: uppercase; color: #5d6d8a; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 13px; color: #4a5a73; }
        .info-row span:first-child { font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: #6e7a90; }
        .shipping-address-item { border: 1px solid #e7edf5; border-radius: 8px; padding: 16px; margin-bottom: 16px; background: #f9fbff; position: relative; }
        .shipping-address-item .remove-shipping-address { position: absolute; top: 12px; right: 12px; }
        .shipping-address-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .shipping-address-controls .form-group { margin-bottom: 0; }
        .form-actions { display: flex; justify-content: flex-end; gap: 12px; }
        .shipping-view-list { list-style: none; padding: 0; margin: 0; }
        .shipping-view-list li { border: 1px solid #e7edf5; border-radius: 8px; padding: 14px 16px; margin-bottom: 10px; background: #f7faff; }
        .shipping-view-list strong { display: block; font-size: 12px; color: #4a5a73; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.05em; }
        .customer-logo-preview img { max-width: 100%; height: auto; border-radius: 6px; margin-bottom: 14px; border: 1px solid #dbe2ef; }
        @media (max-width: 767px) {
            .form-actions { flex-direction: column; align-items: stretch; }
            .shipping-address-controls { flex-direction: column; align-items: flex-start; gap: 8px; }
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
                <div class="page-bar">
                    <ul class="page-breadcrumb">
                        <li>
                            <a href="index.php">Home</a>
                            <i class="fa fa-circle"></i>
                        </li>
                        <li>
                            <span>Customer</span>
                        </li>
                    </ul>
                </div>

                <?php if ($message !== ''): ?>
                    <div class="alert <?php echo h($MessageClass ?: 'alert-info'); ?> alert-dismissable">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                        <?php echo nl2br(h($message)); ?>
                    </div>
                <?php endif; ?>

                <div class="portlet-body">
                    <div class="tabbable-custom">
                        <ul class="nav nav-tabs">
                            <li class="active">
                                <a href="#tab_profile" data-toggle="tab" aria-expanded="true">Customer Profile</a>
                            </li>
                            <li>
                                <a href="#tab_sales" data-toggle="tab" aria-expanded="false">Customer Sales</a>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane active" id="tab_profile">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="section-card">
                                            <h4>Customer Overview</h4>
                                            <?php if ($formData['customer_logo']): ?>
                                                <div class="customer-logo-preview">
                                                    <img src="<?php echo h($formData['customer_logo']); ?>" alt="Customer Logo">
                                                </div>
                                            <?php endif; ?>
                                            <div class="info-row"><span>Code</span><span><?php echo h($formData['customer_code']); ?></span></div>
                                            <div class="info-row"><span>Status</span><span><?php echo h($isActiveLabel); ?></span></div>
                                            <div class="info-row"><span>Account</span><span><?php echo h($accountHoldLabel); ?></span></div>
                                            <div class="info-row"><span>Lock</span><span><?php echo h($lockedLabel); ?></span></div>
                                            <div class="info-row"><span>Credit Limit</span><span><?php echo h($creditLimitDisplay); ?></span></div>
                                            <div class="info-row"><span>Outstanding</span><span><?php echo h($outstandingDisplay); ?></span></div>
                                            <div class="info-row"><span>VAT Status</span><span><?php echo h($vatRegisteredLabel); ?></span></div>
                                            <?php if ($formData['abn_no'] !== ''): ?>
                                                <div class="info-row"><span>ABN</span><span><?php echo h($formData['abn_no']); ?></span></div>
                                            <?php endif; ?>
                                            <?php if ($formData['acn_no'] !== ''): ?>
                                                <div class="info-row"><span>ACN</span><span><?php echo h($formData['acn_no']); ?></span></div>
                                            <?php endif; ?>
        ... (content too long for this patch)



