<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

session_start();
include('include/database.php');
include('include/check_login.php');
include('include/customer_access.php');
include('include/delivery_route_groups.php');
include('include/delivery_rules.php');

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$canManageCustomerAccess = canManageCustomerStatusAccess();

function filter($var)
{
    return preg_replace('/[^a-zA-Z0-9\s@.]/', '', $var);
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

function getDeliveryRoutesForCustomerSafe($customerId) {
    try {
        return getDeliveryRoutesForCustomer($customerId);
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

function getShippingAddressAvailability($shippingAddressId) {
    try {
        $db = new Database();
        $row = $db->getRow('SELECT id, mon, tue, wed, thu, fri, sat, sun FROM shipping_address_availability WHERE shipping_address_id = ? LIMIT 1', [$shippingAddressId]);
        return $row ?: null;
    } catch (Exception $e) {
        return null;
    }
}

function getShippingAddressDayRoute($shippingAddressId) {
    try {
        $db = new Database();
        $row = $db->getRow('SELECT * FROM shipping_address_day_route WHERE shipping_address_id = ? LIMIT 1', [(int)$shippingAddressId]);
        return $row ?: null;
    } catch (Exception $e) {
        return null;
    }
}

function getAttachmentExtension($fileName)
{
    return strtolower((string) pathinfo((string) $fileName, PATHINFO_EXTENSION));
}

function isImageAttachment($fileName)
{
    return in_array(getAttachmentExtension($fileName), ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
}

function getAttachmentIconClass($fileName)
{
    $extension = getAttachmentExtension($fileName);

    if ($extension === 'pdf') {
        return 'fa-file-pdf-o';
    }

    if (isImageAttachment($fileName)) {
        return 'fa-file-image-o';
    }

    return 'fa-file-o';
}

// Handle AJAX requests for shipping address availability
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    $db = new Database();

    if ($_POST['action'] === 'get_shipping_availability') {
        $shippingAddressId = filter($_POST['shipping_address_id'] ?? '');
        if ($shippingAddressId) {
            $availability = $db->getRow('SELECT id, mon, tue, wed, thu, fri, sat, sun FROM shipping_address_availability WHERE shipping_address_id = ? LIMIT 1', [$shippingAddressId]);
            echo json_encode(['success' => true, 'data' => $availability]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid shipping address ID']);
        }
        exit();
    }

    elseif ($_POST['action'] === 'save_shipping_availability') {
        $shippingAddressId = filter($_POST['shipping_address_id'] ?? '');
        if ($shippingAddressId) {
            try {
                $data = [
                    'mon' => isset($_POST['mon']) ? 1 : 0,
                    'tue' => isset($_POST['tue']) ? 1 : 0,
                    'wed' => isset($_POST['wed']) ? 1 : 0,
                    'thu' => isset($_POST['thu']) ? 1 : 0,
                    'fri' => isset($_POST['fri']) ? 1 : 0,
                    'sat' => isset($_POST['sat']) ? 1 : 0,
                    'sun' => isset($_POST['sun']) ? 1 : 0,
                ];

                // Check if availability already exists
                $existing = $db->getRow('SELECT id FROM shipping_address_availability WHERE shipping_address_id = ? LIMIT 1', [$shippingAddressId]);

                if ($existing) {
                    $db->updateRow('UPDATE shipping_address_availability SET mon=?, tue=?, wed=?, thu=?, fri=?, sat=?, sun=? WHERE shipping_address_id=?',
                        [$data['mon'], $data['tue'], $data['wed'], $data['thu'], $data['fri'], $data['sat'], $data['sun'], $shippingAddressId]);
                    echo json_encode(['success' => true, 'message' => 'Delivery availability updated successfully']);
                } else {
                    $db->insertRow('INSERT INTO shipping_address_availability (shipping_address_id, mon, tue, wed, thu, fri, sat, sun) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                        [$shippingAddressId, $data['mon'], $data['tue'], $data['wed'], $data['thu'], $data['fri'], $data['sat'], $data['sun']]);
                    echo json_encode(['success' => true, 'message' => 'Delivery availability added successfully']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid shipping address ID']);
        }
        exit();
    }

    elseif ($_POST['action'] === 'save_day_route') {
        $shippingAddressId = (int)($_POST['shipping_address_id'] ?? 0);
        if ($shippingAddressId > 0) {
            try {
                $days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
                $routeIds = [];
                foreach ($days as $day) {
                    $val = isset($_POST[$day . '_route_id']) && $_POST[$day . '_route_id'] !== '' ? (int)$_POST[$day . '_route_id'] : null;
                    $routeIds[$day . '_route_id'] = $val;
                }
                $existing = $db->getRow('SELECT id FROM shipping_address_day_route WHERE shipping_address_id = ? LIMIT 1', [$shippingAddressId]);
                if ($existing) {
                    $db->updateRow(
                        'UPDATE shipping_address_day_route SET mon_route_id=?, tue_route_id=?, wed_route_id=?, thu_route_id=?, fri_route_id=?, sat_route_id=?, sun_route_id=? WHERE shipping_address_id=?',
                        [$routeIds['mon_route_id'], $routeIds['tue_route_id'], $routeIds['wed_route_id'], $routeIds['thu_route_id'], $routeIds['fri_route_id'], $routeIds['sat_route_id'], $routeIds['sun_route_id'], $shippingAddressId]
                    );
                } else {
                    $db->insertRow(
                        'INSERT INTO shipping_address_day_route (shipping_address_id, mon_route_id, tue_route_id, wed_route_id, thu_route_id, fri_route_id, sat_route_id, sun_route_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                        [$shippingAddressId, $routeIds['mon_route_id'], $routeIds['tue_route_id'], $routeIds['wed_route_id'], $routeIds['thu_route_id'], $routeIds['fri_route_id'], $routeIds['sat_route_id'], $routeIds['sun_route_id']]
                    );
                }
                echo json_encode(['success' => true, 'message' => 'Delivery routes updated successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid shipping address ID']);
        }
        exit();
    }

    elseif ($_POST['action'] === 'delete_attachment') {
        $attachmentId = (int) ($_POST['attachment_id'] ?? 0);
        $customerId = (int) ($_POST['customer_id'] ?? 0);

        if ($attachmentId > 0 && $customerId > 0) {
            try {
                $attachment = $db->getRow('SELECT id, file_path FROM customer_attachments WHERE id = ? AND customer_id = ? LIMIT 1', [$attachmentId, $customerId]);
                if (!$attachment) {
                    echo json_encode(['success' => false, 'message' => 'Attachment not found']);
                    exit();
                }

                if (!empty($attachment['file_path'])) {
                    $filePath = dirname(__DIR__) . '/' . $attachment['file_path'];
                    if (file_exists($filePath)) {
                        @unlink($filePath);
                    }
                }

                $db->updateRow('DELETE FROM customer_attachments WHERE id = ? AND customer_id = ?', [$attachmentId, $customerId]);
                echo json_encode(['success' => true, 'message' => 'Attachment deleted successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Delete failed: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid attachment request']);
        }
        exit();
    }

    elseif ($_POST['action'] === 'delete_compliance_pdf') {
        $documentId = (int) ($_POST['document_id'] ?? 0);

        if ($documentId > 0) {
            try {
                $document = $db->getRow('SELECT id, file_path FROM customer_compliance_documents WHERE id = ? LIMIT 1', [$documentId]);
                if (!$document) {
                    echo json_encode(['success' => false, 'message' => 'Compliance document not found']);
                    exit();
                }

                if (!empty($document['file_path'])) {
                    $filePath = dirname(__DIR__) . '/' . ltrim((string) $document['file_path'], '/');
                    if (file_exists($filePath)) {
                        @unlink($filePath);
                    }
                }

                $db->updateRow('DELETE FROM customer_compliance_documents WHERE id = ?', [$documentId]);
                echo json_encode(['success' => true, 'message' => 'Compliance document deleted successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Delete failed: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid compliance document request']);
        }
        exit();
    }
}

$message = '';
$MessageClass = '';
$status = false;
$db = null;

$getCustomerID = filter($_GET['customerID'] ?? '');
if ($getCustomerID === '') {
    header('location:manage-customer.php');
    exit();
}

$db = new Database();
$customer = $db->getRow('SELECT * FROM customer WHERE customer_id = ? LIMIT 1', [$getCustomerID]);
if (!$customer) {
    header('location:manage-customer.php');
    exit();
}

// Auto-add line discount columns if missing
try {
    $lineDiscountIdCol = $db->getRow("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customer' AND COLUMN_NAME = 'line_discount_id'");
    if (!$lineDiscountIdCol) {
        $db->insertRow("ALTER TABLE customer ADD COLUMN line_discount_id INT(10) NULL DEFAULT NULL");
    }

    $lineDiscountPctCol = $db->getRow("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customer' AND COLUMN_NAME = 'line_discount_percentage'");
    if (!$lineDiscountPctCol) {
        $db->insertRow("ALTER TABLE customer ADD COLUMN line_discount_percentage DECIMAL(10,2) NULL DEFAULT NULL");
    }
} catch (Exception $e) {
    // Ignore migration failures and continue gracefully
}

// Create customer_attachments table if it doesn't exist
try {
    $db->getRows('CREATE TABLE IF NOT EXISTS `customer_attachments` (
        `id` int(10) NOT NULL AUTO_INCREMENT,
        `customer_id` int(10) NOT NULL,
        `file_path` varchar(255) DEFAULT NULL,
        `content` text DEFAULT NULL,
        `file_name` varchar(255) DEFAULT NULL,
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `customer_id` (`customer_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
} catch (Exception $e) {
    // Table might already exist or creation failed, continue
}

try {
    $db->getRows('CREATE TABLE IF NOT EXISTS `customer_compliance_documents` (
        `id` int(10) NOT NULL AUTO_INCREMENT,
        `customer_id` int(10) NOT NULL,
        `file_path` varchar(255) DEFAULT NULL,
        `file_name` varchar(255) DEFAULT NULL,
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `customer_id` (`customer_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
    // Drop unique constraint if it exists (migration for multi-document support)
    try {
        $db->getRows('ALTER TABLE `customer_compliance_documents` DROP INDEX `customer_id`');
        $db->getRows('ALTER TABLE `customer_compliance_documents` ADD KEY `customer_id` (`customer_id`)');
    } catch (Exception $e) { /* index may not exist or already non-unique */ }
    $db->getRows('CREATE TABLE IF NOT EXISTS `customer_email_accounts` (
        `id` int(10) NOT NULL AUTO_INCREMENT,
        `customer_id` int(10) NOT NULL,
        `email_address` varchar(255) NOT NULL,
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `customer_id` (`customer_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
    $db->getRows('CREATE TABLE IF NOT EXISTS `customer_compliance_contact_emails` (
        `id` int(10) NOT NULL AUTO_INCREMENT,
        `customer_id` int(10) NOT NULL,
        `email_address` varchar(255) NOT NULL,
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `customer_id` (`customer_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
    $db->getRows('CREATE TABLE IF NOT EXISTS `shipping_address_additional_emails` (
        `id` int(10) NOT NULL AUTO_INCREMENT,
        `shipping_address_id` int(10) NOT NULL,
        `email_address` varchar(255) NOT NULL,
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `shipping_address_id` (`shipping_address_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
} catch (Exception $e) {
    // Table might already exist or creation failed, continue
}

$formData = [
    'customer_code' => $customer['customer_code'] ?? '',
    'customer_name' => $customer['customer_name'] ?? '',
    'customer_email' => $customer['customer_email'] ?? '',
    'customer_phone' => $customer['customer_tell'] ?? '',
    'customer_mobile' => $customer['customer_mobile'] ?? '',
    'address_line_1' => $customer['address_line_1'] ?? '',
    'address_line_2' => $customer['address_line_2'] ?? '',
    'city' => $customer['city'] ?? '',
    'state' => $customer['state'] ?? '',
    'country' => $customer['country'] ?? '',
    'postal_code' => $customer['postal_code'] ?? '',
    'credit_limit' => $customer['credit_limit'] ?? '',
    'account_hold' => (int) ($customer['account_hold'] ?? 0),
    'abn_no' => $customer['abn_no'] ?? '',
    'acn_no' => $customer['acn_no'] ?? '',
    'vat_registered' => (int) ($customer['vat_registered'] ?? 0),
    'gst_no' => $customer['gst_no'] ?? '',
    'payment_terms_id' => $customer['payment_terms_id'] ?? '',
    'customer_price_type_id' => $customer['customer_price_type_id'] ?? '',
    'customer_note' => $customer['customer_note'] ?? '',
    'customer_additional_emails' => [],
    'compliance_contact_emails' => [],
    'is_active' => (int) ($customer['is_active'] ?? 1),
    'locked' => (int) ($customer['locked'] ?? 0),
    'customer_discount' => $customer['customer_discount'] ?? '',
    'line_discount_id' => $customer['line_discount_id'] ?? '',
    'outstanding_balance' => $customer['customer_outstanding_balance'] ?? '',
    'repeat_interval' => $customer['RepeatInterval'] ?? '',
    'repeat_unit' => $customer['RepeatUnit'] ?? '',
    'legal_name' => $customer['legal_name'] ?? '',
    'trading_name' => $customer['trading_name'] ?? '',
    'customer_remarks' => $customer['customer_remarks'] ?? '',
    'min_order_amount' => $customer['min_order_amount'] ?? '',
    'emergency_contact_name' => $customer['emergency_contact_name'] ?? '',
    'emergency_contact_email' => $customer['emergency_contact_email'] ?? '',
    'emergency_contact_telephone' => $customer['emergency_contact_telephone'] ?? '',
    'custom_url_link' => $customer['custom_url_link'] ?? '',
    'google_map_link' => $customer['google_map_link'] ?? '',
    'contact_name' => $customer['contact_name'] ?? '',
    'contact_email' => $customer['contact_email'] ?? '',
    'contact_telephone' => $customer['contact_telephone'] ?? '',
];

$customerAdditionalEmailRows = $db->getRows('SELECT email_address FROM customer_email_accounts WHERE customer_id = ? ORDER BY id ASC', [$getCustomerID]);
$formData['customer_additional_emails'] = array_map(function ($row) {
    return $row['email_address'];
}, $customerAdditionalEmailRows);

$complianceContactEmailRows = $db->getRows('SELECT email_address FROM customer_compliance_contact_emails WHERE customer_id = ? ORDER BY id ASC', [$getCustomerID]);
$formData['compliance_contact_emails'] = array_map(function ($row) {
    return $row['email_address'];
}, $complianceContactEmailRows);

$shippingData = [];
$shippingRows = $db->getRows('SELECT * FROM customer_shipping_address WHERE customer_id = ? ORDER BY is_default DESC, id ASC', [$getCustomerID]);
foreach ($shippingRows as $row) {
    $addrId = $row['id'];
    $addrAdditionalEmails = [];
    try {
        $emailRows = $db->getRows('SELECT email_address FROM shipping_address_additional_emails WHERE shipping_address_id = ? ORDER BY id ASC', [$addrId]);
        $addrAdditionalEmails = array_map(function($r) { return $r['email_address']; }, $emailRows);
    } catch (Exception $e) { /* table may not exist yet on first load */ }
    $shippingData[] = [
        'id' => $addrId,
        'label' => $row['address_label'] ?? '',
        'address_line_1' => $row['address_line_1'] ?? '',
        'address_line_2' => $row['address_line_2'] ?? '',
        'city' => $row['city'] ?? '',
        'state' => $row['state'] ?? '',
        'country' => $row['country'] ?? '',
        'postal_code' => $row['postal_code'] ?? '',
        'contact_no' => $row['contact_no'] ?? '',
        'attribute_1' => $row['attribute_1'] ?? '',
        'attribute_2' => $row['attribute_2'] ?? '',
        'attribute_3' => $row['attribute_3'] ?? '',
        'is_default' => (int) ($row['is_default'] ?? 0),
        'contact_person_name' => $row['contact_person_name'] ?? '',
        'contact_person_phone' => $row['contact_person_phone'] ?? '',
        'contact_person_email' => $row['contact_person_email'] ?? '',
        'remarks' => $row['remarks'] ?? '',
        'note_to_deliver' => $row['note_to_deliver'] ?? '',
        'delivery_start_time' => $row['delivery_time_from'] ?? '',
        'delivery_end_time' => $row['delivery_time_till'] ?? '',
        'has_door_key' => (int) ($row['has_door_key'] ?? 0),
        'has_shop_alarm' => (int) ($row['has_shop_alarm'] ?? 0),
        'delivery_route_id' => $row['delivery_route_id'] ?? '',
        'delivery_route_group_id' => $row['delivery_route_group_id'] ?? '',
        'delivery_rule_id' => $row['delivery_rule_id'] ?? '',
        'so_daily_average' => $row['so_daily_average'] ?? '',
        'min_cart_order_override' => $row['min_cart_order_override'] ?? '',
        'weekly_avg_free_delivery_override' => $row['weekly_avg_free_delivery_override'] ?? '',
        'additional_emails' => $addrAdditionalEmails,
    ];
}

$attachments = [];
$attachmentRows = $db->getRows('SELECT * FROM customer_attachments WHERE customer_id = ? ORDER BY created_at ASC', [$getCustomerID]);
foreach ($attachmentRows as $row) {
    $displayName = $row['file_name'] ?: basename((string) $row['file_path']);
    $attachments[] = [
        'id' => $row['id'],
        'file_path' => $row['file_path'],
        'content' => $row['content'],
        'file_name' => $row['file_name'],
        'created_at' => $row['created_at'],
        'display_name' => $displayName,
        'is_image' => !empty($row['file_path']) && isImageAttachment($displayName ?: $row['file_path']),
        'icon_class' => getAttachmentIconClass($displayName ?: $row['file_path']),
    ];
}

$maxAttachmentFiles = 5;
$existingAttachmentCount = 0;
foreach ($attachments as $attachment) {
    if (!empty($attachment['file_path'])) {
        $existingAttachmentCount++;
    }
}

$complianceDocuments = [];

try {
    $complianceDocumentRows = $db->getRows('SELECT * FROM customer_compliance_documents WHERE customer_id = ? ORDER BY id ASC', [$getCustomerID]);
    foreach ($complianceDocumentRows as $row) {
        $complianceDocuments[] = [
            'id' => $row['id'] ?? 0,
            'file_path' => $row['file_path'] ?? '',
            'file_name' => $row['file_name'] ?: (!empty($row['file_path']) ? basename((string) $row['file_path']) : ''),
            'updated_at' => $row['updated_at'] ?? $row['created_at'] ?? '',
        ];
    }
} catch (Exception $e) {
    // Continue without compliance documents if the table is unavailable.
}

if (empty($shippingData)) {
    $shippingData[] = [
        'label' => 'Primary',
        'address_line_1' => $formData['address_line_1'],
        'address_line_2' => $formData['address_line_2'],
        'city' => $formData['city'],
        'state' => $formData['state'],
        'country' => $formData['country'],
        'postal_code' => $formData['postal_code'],
        'contact_no' => $formData['customer_phone'],
        'attribute_1' => '',
        'attribute_2' => '',
        'attribute_3' => '',
        'is_default' => 1,
        'contact_person_name' => '',
        'contact_person_phone' => '',
        'contact_person_email' => '',
        'remarks' => '',
        'note_to_deliver' => '',
        'delivery_start_time' => '',
        'delivery_end_time' => '',
        'has_door_key' => 0,
        'has_shop_alarm' => 0,
        'delivery_route_id' => '',
        'delivery_route_group_id' => '',
        'delivery_rule_id' => '',
        'so_daily_average' => '',
        'min_cart_order_override' => '',
        'weekly_avg_free_delivery_override' => '',
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['customer_code'] = trim($_POST['customer_code'] ?? $formData['customer_code']);
    $formData['customer_name'] = trim($_POST['customer_name'] ?? '');
    $formData['customer_email'] = trim($_POST['customer_email'] ?? '');
    $formData['customer_phone'] = trim($_POST['customer_phone'] ?? '');
    $formData['customer_mobile'] = trim($_POST['customer_mobile'] ?? '');
    $formData['customer_additional_emails'] = [];
    $submittedAdditionalEmails = array_filter(array_map('trim', (array)($_POST['customer_additional_emails'] ?? [])));
    foreach ($submittedAdditionalEmails as $submittedAdditionalEmail) {
        if ($submittedAdditionalEmail === '') {
            continue;
        }
        if (!filter_var($submittedAdditionalEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid additional email address: ' . $submittedAdditionalEmail;
        } else {
            $formData['customer_additional_emails'][] = $submittedAdditionalEmail;
        }
    }
    $formData['compliance_contact_emails'] = [];
    $submittedComplianceContactEmails = array_filter(array_map('trim', (array)($_POST['compliance_contact_emails'] ?? [])));
    foreach ($submittedComplianceContactEmails as $cce) {
        if ($cce === '') {
            continue;
        }
        if (!filter_var($cce, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid compliance contact email: ' . $cce;
        } else {
            $formData['compliance_contact_emails'][] = $cce;
        }
    }
    $formData['address_line_1'] = trim($_POST['address_line_1'] ?? '');
    $formData['address_line_2'] = trim($_POST['address_line_2'] ?? '');
    $formData['city'] = trim($_POST['city'] ?? '');
    $formData['state'] = trim($_POST['state'] ?? '');
    $formData['country'] = trim($_POST['country'] ?? '');
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
    $customerAccessFlags = normalizeCustomerStatusFlags($_POST, $customer);
    $formData['is_active'] = $customerAccessFlags['is_active'];
    $formData['locked'] = $customerAccessFlags['locked'];
    $formData['customer_discount'] = trim($_POST['customer_discount'] ?? '');
    $formData['line_discount_id'] = trim($_POST['line_discount_id'] ?? '');
    $formData['outstanding_balance'] = trim($_POST['outstanding_balance'] ?? '');
    $formData['repeat_interval'] = trim($_POST['repeat_interval'] ?? '');
    $formData['repeat_unit'] = trim($_POST['repeat_unit'] ?? '');
    $formData['legal_name'] = trim($_POST['legal_name'] ?? '');
    $formData['trading_name'] = trim($_POST['trading_name'] ?? '');
    $formData['customer_remarks'] = trim($_POST['customer_remarks'] ?? '');
    $formData['min_order_amount'] = trim($_POST['min_order_amount'] ?? '');
    $formData['emergency_contact_name'] = trim($_POST['emergency_contact_name'] ?? '');
    $formData['emergency_contact_email'] = trim($_POST['emergency_contact_email'] ?? '');
    $formData['emergency_contact_telephone'] = trim($_POST['emergency_contact_telephone'] ?? '');
    $formData['custom_url_link'] = trim($_POST['custom_url_link'] ?? '');
    $formData['google_map_link'] = trim($_POST['google_map_link'] ?? '');
    $formData['contact_name'] = trim($_POST['contact_name'] ?? '');
    $formData['contact_email'] = trim($_POST['contact_email'] ?? '');
    $formData['contact_telephone'] = trim($_POST['contact_telephone'] ?? '');

    $shippingLabels = $_POST['shipping_label'] ?? [];
    $shippingLine1 = $_POST['shipping_address_line_1'] ?? [];
    $shippingLine2 = $_POST['shipping_address_line_2'] ?? [];
    $shippingCity = $_POST['shipping_city'] ?? [];
    $shippingState = $_POST['shipping_state'] ?? [];
    $shippingCountry = $_POST['shipping_country'] ?? [];
    $shippingPostal = $_POST['shipping_postal_code'] ?? [];
    $shippingContact = $_POST['shipping_contact_no'] ?? [];
    $shippingAttr1 = $_POST['shipping_attribute_1'] ?? [];
    $shippingAttr2 = $_POST['shipping_attribute_2'] ?? [];
    $shippingAttr3 = $_POST['shipping_attribute_3'] ?? [];
    $shippingContactPersonName = $_POST['shipping_contact_person_name'] ?? [];
    $shippingContactPersonEmail = $_POST['shipping_contact_person_email'] ?? [];
    $shippingContactPersonPhone = $_POST['shipping_contact_person_phone'] ?? [];
    $shippingRemarks = $_POST['shipping_remarks'] ?? [];
    $shippingNoteToDeliver = $_POST['shipping_note_to_deliver'] ?? [];
    $shippingTimeFrom = $_POST['shipping_delivery_start_time'] ?? [];
    $shippingTimeTill = $_POST['shipping_delivery_end_time'] ?? [];
    $shippingDoorKey = $_POST['shipping_has_door_key'] ?? [];
    $shippingShopAlarm = $_POST['shipping_has_shop_alarm'] ?? [];
    $shippingDeliveryRouteGroup = $_POST['shipping_delivery_route_group_id'] ?? [];
    $shippingDeliveryRule = $_POST['shipping_delivery_rule_id'] ?? [];
    $shippingSoDailyAvg = $_POST['shipping_so_daily_average'] ?? [];
    $shippingMinCartOverride = $_POST['shipping_min_cart_order_override'] ?? [];
    $shippingWeeklyAvgOverride = $_POST['shipping_weekly_avg_free_delivery_override'] ?? [];
    $defaultIndex = $_POST['shipping_default'] ?? '0';
    $shippingAdditionalEmails = $_POST['shipping_additional_emails'] ?? [];

    $shippingExistingIds = $_POST['shipping_existing_id'] ?? [];

    $shippingCollected = [];
    foreach ($shippingLine1 as $idx => $line1) {
        $line1 = trim($line1);
        if ($line1 === '') {
            continue;
        }

        $shippingCollected[] = [
            'id' => isset($shippingExistingIds[$idx]) ? (int)$shippingExistingIds[$idx] : null,
            'label' => trim($shippingLabels[$idx] ?? ''),
            'address_line_1' => $line1,
            'address_line_2' => trim($shippingLine2[$idx] ?? ''),
            'city' => trim($shippingCity[$idx] ?? ''),
            'state' => trim($shippingState[$idx] ?? ''),
            'country' => trim($shippingCountry[$idx] ?? ''),
            'postal_code' => trim($shippingPostal[$idx] ?? ''),
            'contact_no' => trim($shippingContact[$idx] ?? ''),
            'attribute_1' => trim($shippingAttr1[$idx] ?? ''),
            'attribute_2' => trim($shippingAttr2[$idx] ?? ''),
            'attribute_3' => trim($shippingAttr3[$idx] ?? ''),
            'is_default' => ((string) $defaultIndex === (string) $idx) ? 1 : 0,
            'contact_person_name' => trim($shippingContactPersonName[$idx] ?? ''),
            'contact_person_phone' => trim($shippingContactPersonPhone[$idx] ?? ''),
            'contact_person_email' => trim($shippingContactPersonEmail[$idx] ?? ''),
            'remarks' => trim($shippingRemarks[$idx] ?? ''),
            'note_to_deliver' => trim($shippingNoteToDeliver[$idx] ?? ''),
            'delivery_start_time' => trim($shippingTimeFrom[$idx] ?? ''),
            'delivery_end_time' => trim($shippingTimeTill[$idx] ?? ''),
            'has_door_key' => isset($shippingDoorKey[$idx]) ? 1 : 0,
            'has_shop_alarm' => isset($shippingShopAlarm[$idx]) ? 1 : 0,
            'delivery_route_group_id' => trim($shippingDeliveryRouteGroup[$idx] ?? ''),
            'delivery_rule_id' => trim($shippingDeliveryRule[$idx] ?? ''),
            'so_daily_average' => trim($shippingSoDailyAvg[$idx] ?? ''),
            'min_cart_order_override' => trim($shippingMinCartOverride[$idx] ?? ''),
            'weekly_avg_free_delivery_override' => trim($shippingWeeklyAvgOverride[$idx] ?? ''),
            'additional_emails' => array_values(array_filter(array_map('trim', (array)($shippingAdditionalEmails[$idx] ?? [])))),
        ];
    }

    if (empty($shippingCollected)) {
        $shippingCollected[] = [
            'label' => ($formData['customer_name'] !== '' ? $formData['customer_name'] . ' - Primary' : 'Primary'),
            'address_line_1' => $formData['address_line_1'],
            'address_line_2' => $formData['address_line_2'],
            'city' => $formData['city'],
            'state' => $formData['state'],
            'country' => $formData['country'],
            'postal_code' => $formData['postal_code'],
            'contact_no' => $formData['customer_phone'],
            'attribute_1' => '',
            'attribute_2' => '',
            'attribute_3' => '',
            'is_default' => 1,
        ];
    }

    $hasDefault = false;
    foreach ($shippingCollected as $index => $address) {
        if ($address['is_default'] && !$hasDefault) {
            $hasDefault = true;
        } elseif ($address['is_default'] && $hasDefault) {
            $shippingCollected[$index]['is_default'] = 0;
        }
    }
    if (!$hasDefault && !empty($shippingCollected)) {
        $shippingCollected[0]['is_default'] = 1;
    }
    $shippingData = array_values($shippingCollected);

    $errors = [];

    if ($formData['customer_name'] === '') {
        $errors[] = 'Customer name is required.';
    }

    if ($formData['address_line_1'] === '') {
        $errors[] = 'Address line 1 is required.';
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

    $discountValue = 0.00;
    if ($formData['customer_discount'] !== '') {
        if (!is_numeric($formData['customer_discount']) || $formData['customer_discount'] < 0 || $formData['customer_discount'] > 100) {
            $errors[] = 'Customer discount must be a number between 0 and 100.';
        } else {
            $discountValue = number_format((float) $formData['customer_discount'], 2, '.', '');
        }
    }

    $outstandingValue = 0.00;
    if ($formData['outstanding_balance'] !== '') {
        $normalizedOutstanding = str_replace([',', ' '], '', $formData['outstanding_balance']);
        if (!is_numeric($normalizedOutstanding)) {
            $errors[] = 'Outstanding balance must be a valid number.';
        } else {
            $outstandingValue = number_format((float) $normalizedOutstanding, 2, '.', '');
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

    $uploadedCompliancePdfs = [];
    if (isset($_FILES['compliance_pdf']) && is_array($_FILES['compliance_pdf']['name'])) {
        $existingComplianceCount = count($complianceDocuments);
        $maxComplianceDocs = 5;
        $slotsAvailable = max(0, $maxComplianceDocs - $existingComplianceCount);
        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
        $allowedPdfMime = ['application/pdf', 'application/x-pdf'];
        $maxCompliancePdfSize = 5 * 1024 * 1024;

        foreach ($_FILES['compliance_pdf']['name'] as $i => $originalName) {
            if ((int) ($_FILES['compliance_pdf']['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ((int) $_FILES['compliance_pdf']['error'][$i] !== UPLOAD_ERR_OK || empty($originalName)) {
                $errors[] = 'Unable to upload compliance document ' . ($i + 1) . '.';
                continue;
            }
            $tmpName = $_FILES['compliance_pdf']['tmp_name'][$i];
            $fileSize = (int) ($_FILES['compliance_pdf']['size'][$i] ?? 0);
            $extension = getAttachmentExtension($originalName);
            $detectedMime = $finfo ? finfo_file($finfo, $tmpName) : ($_FILES['compliance_pdf']['type'][$i] ?? '');

            if ($extension !== 'pdf') {
                $errors[] = 'Compliance document must be a PDF file.';
            } elseif ($detectedMime !== '' && $detectedMime !== 'application/octet-stream' && !in_array($detectedMime, $allowedPdfMime, true)) {
                $errors[] = 'Compliance document must be a valid PDF file.';
            } elseif ($fileSize <= 0 || $fileSize > $maxCompliancePdfSize) {
                $errors[] = 'Compliance PDF must be 5MB or less.';
            } elseif (count($uploadedCompliancePdfs) >= $slotsAvailable) {
                $errors[] = 'Maximum 5 compliance documents allowed. Some files were not uploaded.';
                break;
            } else {
                $uploadedCompliancePdfs[] = [
                    'tmp_name' => $tmpName,
                    'original_name' => $originalName,
                ];
            }
        }

        if ($finfo) {
            finfo_close($finfo);
        }
    }

    // Ensure customer code: generate if blank, validate format and uniqueness
    $generatedCustomerCode = false;
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
        } else {
            try {
                $existing = $db->getRow('SELECT customer_id FROM customer WHERE customer_code = ? AND customer_id != ? LIMIT 1', [$formData['customer_code'], $getCustomerID]);
                if ($existing) {
                    $errors[] = 'Customer code already exists. Please use a different code.';
                }
            } catch (Exception $e) {
                $errors[] = 'Unable to validate customer code uniqueness.';
            }
        }
    }

    if (empty($errors)) {
        try {
            $lineDiscountPercentageValue = null;
            if ($formData['line_discount_id'] !== '') {
                $selectedLineDiscount = $db->getRow('SELECT percentage FROM discount_code WHERE id = ? LIMIT 1', [(int)$formData['line_discount_id']]);
                if ($selectedLineDiscount && isset($selectedLineDiscount['percentage']) && is_numeric($selectedLineDiscount['percentage'])) {
                    $lineDiscountPercentageValue = number_format((float)$selectedLineDiscount['percentage'], 2, '.', '');
                }
            }

            $updateData = [
                'customer_code' => $formData['customer_code'] !== '' ? $formData['customer_code'] : null,
                'customer_email' => $formData['customer_email'] !== '' ? $formData['customer_email'] : null,
                'is_active' => $formData['is_active'],

                'locked' => $formData['locked'],
                'customer_name' => $formData['customer_name'],
                'address_line_1' => $formData['address_line_1'],
                'address_line_2' => $formData['address_line_2'] !== '' ? $formData['address_line_2'] : null,
                'city' => $formData['city'] !== '' ? $formData['city'] : null,
                'state' => $formData['state'] !== '' ? $formData['state'] : null,
                'country' => $formData['country'] !== '' ? $formData['country'] : null,
                'postal_code' => $formData['postal_code'] !== '' ? $formData['postal_code'] : null,
                'customer_discount' => $discountValue,
                'customer_tell' => $formData['customer_phone'] !== '' ? $formData['customer_phone'] : null,
                'customer_mobile' => $formData['customer_mobile'] !== '' ? $formData['customer_mobile'] : null,
                'customer_note' => $formData['customer_note'] !== '' ? $formData['customer_note'] : null,
                'customer_outstanding_balance' => $outstandingValue,
                'credit_limit' => $creditLimitValue,
                'account_hold' => $formData['account_hold'],
                'abn_no' => $formData['abn_no'] !== '' ? $formData['abn_no'] : null,
                'acn_no' => $formData['acn_no'] !== '' ? $formData['acn_no'] : null,
                'vat_registered' => $formData['vat_registered'],
                'gst_no' => $formData['gst_no'] !== '' ? $formData['gst_no'] : null,
                'payment_terms_id' => $formData['payment_terms_id'] !== '' ? (int) $formData['payment_terms_id'] : null,
                'customer_price_type_id' => $formData['customer_price_type_id'] !== '' ? (int) $formData['customer_price_type_id'] : null,
                'RepeatInterval' => $formData['repeat_interval'] !== '' ? (int)$formData['repeat_interval'] : null,
                'RepeatUnit' => $formData['repeat_unit'] !== '' ? $formData['repeat_unit'] : null,
                'legal_name' => $formData['legal_name'] !== '' ? $formData['legal_name'] : null,
                'trading_name' => $formData['trading_name'] !== '' ? $formData['trading_name'] : null,
                'customer_remarks' => $formData['customer_remarks'] !== '' ? $formData['customer_remarks'] : null,
                'min_order_amount' => $formData['min_order_amount'] !== '' ? $formData['min_order_amount'] : null,
                'emergency_contact_name' => $formData['emergency_contact_name'] !== '' ? $formData['emergency_contact_name'] : null,
                'emergency_contact_email' => $formData['emergency_contact_email'] !== '' ? $formData['emergency_contact_email'] : null,
                'emergency_contact_telephone' => $formData['emergency_contact_telephone'] !== '' ? $formData['emergency_contact_telephone'] : null,
                'custom_url_link' => $formData['custom_url_link'] !== '' ? $formData['custom_url_link'] : null,
                'google_map_link' => $formData['google_map_link'] !== '' ? $formData['google_map_link'] : null,
                'line_discount_id' => $formData['line_discount_id'] !== '' ? (int)$formData['line_discount_id'] : null,
                'line_discount_percentage' => $lineDiscountPercentageValue,
                'contact_name' => $formData['contact_name'] !== '' ? $formData['contact_name'] : null,
                'contact_email' => $formData['contact_email'] !== '' ? $formData['contact_email'] : null,
                'contact_telephone' => $formData['contact_telephone'] !== '' ? $formData['contact_telephone'] : null,
            ];

            $db->updateRow('UPDATE customer SET ' . implode(', ', array_map(function ($key) { return $key . ' = ?'; }, array_keys($updateData))) . ' WHERE customer_id = ?', array_merge(array_values($updateData), [$getCustomerID]));

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
                    $fileName = 'customer_' . $getCustomerID . '_' . time() . '.' . $extension;
                    $targetPath = $logoDir . '/' . $fileName;
                    if (move_uploaded_file($_FILES['customer_logo']['tmp_name'], $targetPath)) {
                        $logoDbPath = '../images/customer_logo/' . $fileName;
                        $db->updateRow('UPDATE customer SET customer_logo = ? WHERE customer_id = ?', [$logoDbPath, $getCustomerID]);
                    }
                }
            }

            $compliancePdfUploaded = false;
            if (!empty($uploadedCompliancePdfs)) {
                $complianceDir = dirname(__DIR__) . '/uploads/customer_compliance';
                if (!is_dir($complianceDir)) {
                    mkdir($complianceDir, 0777, true);
                }

                foreach ($uploadedCompliancePdfs as $pdf) {
                    $storedFileName = 'customer_' . $getCustomerID . '_compliance_' . time() . '_' . mt_rand(1000, 9999) . '.pdf';
                    $targetPath = $complianceDir . '/' . $storedFileName;
                    if (!move_uploaded_file($pdf['tmp_name'], $targetPath)) {
                        throw new RuntimeException('Failed to save compliance PDF.');
                    }
                    $dbPath = 'uploads/customer_compliance/' . $storedFileName;
                    $db->insertRow(
                        'INSERT INTO customer_compliance_documents (customer_id, file_path, file_name) VALUES (?, ?, ?)',
                        [$getCustomerID, $dbPath, $pdf['original_name']]
                    );
                    $compliancePdfUploaded = true;
                }
            }

            if (!empty($formData['customer_additional_emails'])) {
                $db->updateRow('DELETE FROM customer_email_accounts WHERE customer_id = ?', [$getCustomerID]);
                foreach ($formData['customer_additional_emails'] as $additionalEmail) {
                    $db->insertRow('INSERT INTO customer_email_accounts (customer_id, email_address) VALUES (?, ?)', [$getCustomerID, $additionalEmail]);
                }
            } else {
                $db->updateRow('DELETE FROM customer_email_accounts WHERE customer_id = ?', [$getCustomerID]);
            }

            $db->updateRow('DELETE FROM customer_compliance_contact_emails WHERE customer_id = ?', [$getCustomerID]);
            foreach ($formData['compliance_contact_emails'] as $cce) {
                $db->insertRow('INSERT INTO customer_compliance_contact_emails (customer_id, email_address) VALUES (?, ?)', [$getCustomerID, $cce]);
            }

            // Smart update shipping addresses: update existing, insert new, delete removed
            // This preserves shipping_address IDs so linked availability data survives.
            $existingShippingIds = array_map(function($r) { return (int)$r['id']; },
                $db->getRows('SELECT id FROM customer_shipping_address WHERE customer_id = ?', [$getCustomerID]));

            $keptIds = [];
            $addressIdMap = []; // maps form index => actual DB id
            foreach ($shippingData as $formIdx => $address) {
                $addrId = isset($address['id']) ? (int)$address['id'] : 0;
                if ($addrId && in_array($addrId, $existingShippingIds)) {
                    // UPDATE existing address
                    $db->updateRow(
                        'UPDATE customer_shipping_address SET address_label=?, address_line_1=?, address_line_2=?, city=?, state=?, country=?, postal_code=?, contact_no=?, attribute_1=?, attribute_2=?, attribute_3=?, is_default=?, contact_person_name=?, contact_person_phone=?, contact_person_email=?, remarks=?, note_to_deliver=?, delivery_time_from=?, delivery_time_till=?, has_door_key=?, has_shop_alarm=?, delivery_route_id=?, delivery_route_group_id=?, delivery_rule_id=?, so_daily_average=?, min_cart_order_override=?, weekly_avg_free_delivery_override=? WHERE id=? AND customer_id=?',
                        [
                            $address['label'] !== '' ? $address['label'] : null,
                            $address['address_line_1'],
                            $address['address_line_2'] !== '' ? $address['address_line_2'] : null,
                            $address['city'] !== '' ? $address['city'] : null,
                            $address['state'] !== '' ? $address['state'] : null,
                            $address['country'] !== '' ? $address['country'] : null,
                            $address['postal_code'] !== '' ? $address['postal_code'] : null,
                            $address['contact_no'] !== '' ? $address['contact_no'] : null,
                            $address['attribute_1'] !== '' ? $address['attribute_1'] : null,
                            $address['attribute_2'] !== '' ? $address['attribute_2'] : null,
                            $address['attribute_3'] !== '' ? $address['attribute_3'] : null,
                            $address['is_default'] ? 1 : 0,
                            $address['contact_person_name'] !== '' ? $address['contact_person_name'] : null,
                            $address['contact_person_phone'] !== '' ? $address['contact_person_phone'] : null,
                            $address['contact_person_email'] !== '' ? $address['contact_person_email'] : null,
                            $address['remarks'] !== '' ? $address['remarks'] : null,
                            (($address['note_to_deliver'] ?? '') !== '') ? $address['note_to_deliver'] : null,
                            $address['delivery_start_time'] !== '' ? $address['delivery_start_time'] : null,
                            $address['delivery_end_time'] !== '' ? $address['delivery_end_time'] : null,
                            $address['has_door_key'] ? 1 : 0,
                            $address['has_shop_alarm'] ? 1 : 0,
                            $address['delivery_route_id'] !== '' ? (int)$address['delivery_route_id'] : null,
                            $address['delivery_route_group_id'] !== '' ? (int)$address['delivery_route_group_id'] : null,
                            $address['delivery_rule_id'] !== '' ? (int)$address['delivery_rule_id'] : null,
                            ($address['so_daily_average'] !== '' && is_numeric($address['so_daily_average'])) ? (float)$address['so_daily_average'] : null,
                            ($address['min_cart_order_override'] !== '' && is_numeric($address['min_cart_order_override'])) ? (float)$address['min_cart_order_override'] : null,
                            ($address['weekly_avg_free_delivery_override'] !== '' && is_numeric($address['weekly_avg_free_delivery_override'])) ? (float)$address['weekly_avg_free_delivery_override'] : null,
                            $addrId,
                            $getCustomerID,
                        ]
                    );
                    $keptIds[] = $addrId;
                    $addressIdMap[$formIdx] = $addrId;
                } else {
                    // INSERT new address
                    $db->insertRow(
                        'INSERT INTO customer_shipping_address (customer_id, address_label, address_line_1, address_line_2, city, state, country, postal_code, contact_no, attribute_1, attribute_2, attribute_3, is_default, contact_person_name, contact_person_phone, contact_person_email, remarks, note_to_deliver, delivery_time_from, delivery_time_till, has_door_key, has_shop_alarm, delivery_route_id, delivery_route_group_id, delivery_rule_id, so_daily_average, min_cart_order_override, weekly_avg_free_delivery_override)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                        [
                            $getCustomerID,
                            $address['label'] !== '' ? $address['label'] : null,
                            $address['address_line_1'],
                            $address['address_line_2'] !== '' ? $address['address_line_2'] : null,
                            $address['city'] !== '' ? $address['city'] : null,
                            $address['state'] !== '' ? $address['state'] : null,
                            $address['country'] !== '' ? $address['country'] : null,
                            $address['postal_code'] !== '' ? $address['postal_code'] : null,
                            $address['contact_no'] !== '' ? $address['contact_no'] : null,
                            $address['attribute_1'] !== '' ? $address['attribute_1'] : null,
                            $address['attribute_2'] !== '' ? $address['attribute_2'] : null,
                            $address['attribute_3'] !== '' ? $address['attribute_3'] : null,
                            $address['is_default'] ? 1 : 0,
                            $address['contact_person_name'] !== '' ? $address['contact_person_name'] : null,
                            $address['contact_person_phone'] !== '' ? $address['contact_person_phone'] : null,
                            $address['contact_person_email'] !== '' ? $address['contact_person_email'] : null,
                            $address['remarks'] !== '' ? $address['remarks'] : null,
                            (($address['note_to_deliver'] ?? '') !== '') ? $address['note_to_deliver'] : null,
                            $address['delivery_start_time'] !== '' ? $address['delivery_start_time'] : null,
                            $address['delivery_end_time'] !== '' ? $address['delivery_end_time'] : null,
                            $address['has_door_key'] ? 1 : 0,
                            $address['has_shop_alarm'] ? 1 : 0,
                            $address['delivery_route_id'] !== '' ? (int)$address['delivery_route_id'] : null,
                            $address['delivery_route_group_id'] !== '' ? (int)$address['delivery_route_group_id'] : null,
                            $address['delivery_rule_id'] !== '' ? (int)$address['delivery_rule_id'] : null,
                            ($address['so_daily_average'] !== '' && is_numeric($address['so_daily_average'])) ? (float)$address['so_daily_average'] : null,
                            ($address['min_cart_order_override'] !== '' && is_numeric($address['min_cart_order_override'])) ? (float)$address['min_cart_order_override'] : null,
                            ($address['weekly_avg_free_delivery_override'] !== '' && is_numeric($address['weekly_avg_free_delivery_override'])) ? (float)$address['weekly_avg_free_delivery_override'] : null,
                        ]
                    );
                    $newAddrId = (int)$db->getConnection()->lastInsertId();
                    $keptIds[] = $newAddrId;
                    $addressIdMap[$formIdx] = $newAddrId;
                }
            }

            // Delete removed addresses and their availability data
            $removedIds = array_diff($existingShippingIds, $keptIds);
            foreach ($removedIds as $removedId) {
                $db->updateRow('DELETE FROM shipping_address_day_route WHERE shipping_address_id = ?', [$removedId]);
                $db->updateRow('DELETE FROM shipping_address_availability WHERE shipping_address_id = ?', [$removedId]);
                $db->updateRow('DELETE FROM shipping_address_additional_emails WHERE shipping_address_id = ?', [$removedId]);
                $db->updateRow('DELETE FROM customer_shipping_address WHERE id = ? AND customer_id = ?', [$removedId, $getCustomerID]);
            }

            // Save day-route data for shipping addresses
            $shippingDayRoutePost = $_POST['shipping_day_route'] ?? [];
            foreach ($shippingDayRoutePost as $idx => $dr) {
                $addrId = isset($addressIdMap[$idx]) ? (int)$addressIdMap[$idx] : 0;
                if ($addrId <= 0) continue;
                $days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
                $routeVals = [];
                foreach ($days as $day) {
                    $routeVals[] = (isset($dr[$day]) && $dr[$day] !== '') ? (int)$dr[$day] : null;
                }
                $existingDr = $db->getRow('SELECT id FROM shipping_address_day_route WHERE shipping_address_id = ? LIMIT 1', [$addrId]);
                if ($existingDr) {
                    $db->updateRow(
                        'UPDATE shipping_address_day_route SET mon_route_id=?, tue_route_id=?, wed_route_id=?, thu_route_id=?, fri_route_id=?, sat_route_id=?, sun_route_id=? WHERE shipping_address_id=?',
                        array_merge($routeVals, [$addrId])
                    );
                } else {
                    $db->insertRow(
                        'INSERT INTO shipping_address_day_route (shipping_address_id, mon_route_id, tue_route_id, wed_route_id, thu_route_id, fri_route_id, sat_route_id, sun_route_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                        array_merge([$addrId], $routeVals)
                    );
                }
            }

            // Save additional emails for shipping addresses
            foreach ($shippingCollected as $formIdx => $address) {
                $addrId = isset($addressIdMap[$formIdx]) ? (int)$addressIdMap[$formIdx] : 0;
                if ($addrId <= 0) continue;
                $db->updateRow('DELETE FROM shipping_address_additional_emails WHERE shipping_address_id = ?', [$addrId]);
                foreach ($address['additional_emails'] as $addrEmail) {
                    if ($addrEmail !== '' && filter_var($addrEmail, FILTER_VALIDATE_EMAIL)) {
                        $db->insertRow('INSERT INTO shipping_address_additional_emails (shipping_address_id, email_address) VALUES (?, ?)', [$addrId, $addrEmail]);
                    }
                }
            }

            // Handle attachments (files and notes)
            $attachmentDir = dirname(__DIR__) . '/uploads/customer_attachments';
            if (!is_dir($attachmentDir)) {
                mkdir($attachmentDir, 0777, true);
            }

            // Handle attachment deletions first so file slot count is accurate for new uploads
            $deletedCount = 0;
            if (isset($_POST['delete_attachments'])) {
                foreach ($_POST['delete_attachments'] as $attachmentId) {
                    $attachment = $db->getRow('SELECT * FROM customer_attachments WHERE id = ? AND customer_id = ?', [$attachmentId, $getCustomerID]);
                    if ($attachment) {
                        if (!empty($attachment['file_path'])) {
                            $filePath = dirname(__DIR__) . '/' . $attachment['file_path'];
                            if (file_exists($filePath)) {
                                unlink($filePath);
                            }
                        }
                        $db->updateRow('DELETE FROM customer_attachments WHERE id = ?', [$attachmentId]);
                        $deletedCount++;
                    }
                }
            }

            $uploadedAttachmentCount = 0;
            $uploadedNoteCount = 0;
            $attachmentUploadWarnings = [];

            $attachmentCountRow = $db->getRow('SELECT COUNT(*) AS total FROM customer_attachments WHERE customer_id = ? AND file_path IS NOT NULL AND file_path != ""', [$getCustomerID]);
            $currentAttachmentCount = (int) ($attachmentCountRow['total'] ?? 0);
            $availableAttachmentSlots = max(0, $maxAttachmentFiles - $currentAttachmentCount);

            // Process new file + note pairs
            if (isset($_FILES['attachment_files'])) {
                $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
                $allowedMimeByExtension = [
                    'jpg' => ['image/jpeg', 'image/pjpeg'],
                    'jpeg' => ['image/jpeg', 'image/pjpeg'],
                    'png' => ['image/png'],
                    'gif' => ['image/gif'],
                    'webp' => ['image/webp'],
                    'pdf' => ['application/pdf', 'application/x-pdf'],
                ];
                $maxFileSize = 5 * 1024 * 1024;

                for ($i = 0; $i < count($_FILES['attachment_files']['name']); $i++) {
                    if ($_FILES['attachment_files']['error'][$i] === UPLOAD_ERR_OK && !empty($_FILES['attachment_files']['name'][$i])) {
                        if ($uploadedAttachmentCount >= $availableAttachmentSlots) {
                            $attachmentUploadWarnings[] = 'Maximum ' . $maxAttachmentFiles . ' files allowed. Extra uploads were skipped.';
                            continue;
                        }

                        $tmpName = $_FILES['attachment_files']['tmp_name'][$i];
                        $originalName = $_FILES['attachment_files']['name'][$i];
                        $fileSize = (int) ($_FILES['attachment_files']['size'][$i] ?? 0);

                        $extension = getAttachmentExtension($originalName);
                        $detectedMime = $finfo ? finfo_file($finfo, $tmpName) : ($_FILES['attachment_files']['type'][$i] ?? '');

                        if (!isset($allowedMimeByExtension[$extension])) {
                            $attachmentUploadWarnings[] = 'Invalid file skipped: ' . h($originalName);
                            continue;
                        }

                        if ($detectedMime !== '' && $detectedMime !== 'application/octet-stream' && !in_array($detectedMime, $allowedMimeByExtension[$extension], true)) {
                            $attachmentUploadWarnings[] = 'Invalid file skipped: ' . h($originalName);
                            continue;
                        }

                        if ($fileSize <= 0 || $fileSize > $maxFileSize) {
                            $attachmentUploadWarnings[] = 'File exceeds 5MB limit and was skipped: ' . h($originalName);
                            continue;
                        }

                        $fileName = 'customer_' . $getCustomerID . '_attachment_' . time() . '_' . $i . '.' . $extension;
                        $targetPath = $attachmentDir . '/' . $fileName;
                        if (move_uploaded_file($tmpName, $targetPath)) {
                            $dbPath = 'uploads/customer_attachments/' . $fileName;
                            $pairNote = trim((string)($_POST['attachment_file_notes'][$i] ?? ''));
                            $db->insertRow('INSERT INTO customer_attachments (customer_id, file_path, file_name, content) VALUES (?, ?, ?, ?)',
                                [$getCustomerID, $dbPath, $originalName, $pairNote !== '' ? $pairNote : null]);
                            $uploadedAttachmentCount++;
                            if ($pairNote !== '') {
                                $uploadedNoteCount++;
                            }
                        }
                    }
                }

                if ($finfo) {
                    finfo_close($finfo);
                }
            }

            $status = true;
            $MessageClass = 'alert-success';
            $message = 'Customer updated successfully.';
            if ($generatedCustomerCode) {
                $message .= ' Generated code: ' . h($formData['customer_code']) . '.';
            }
            if ($uploadedAttachmentCount > 0) {
                $message .= ' ' . $uploadedAttachmentCount . ' file(s) uploaded.';
            }
            if ($uploadedNoteCount > 0) {
                $message .= ' ' . $uploadedNoteCount . ' note(s) added with file uploads.';
            }
            if ($compliancePdfUploaded) {
                $message .= ' Compliance PDF uploaded.';
            }
            if ($deletedCount > 0) {
                $message .= ' ' . $deletedCount . ' attachment(s) deleted.';
            }
            if (!empty($attachmentUploadWarnings)) {
                $MessageClass = 'alert-warning';
                $message .= ' ' . implode(' ', array_unique($attachmentUploadWarnings));
            }

            $_SESSION['flash_customer_message'] = $message;
            $_SESSION['flash_customer_class'] = $MessageClass;
            header('location:edit-customer.php?customerID=' . urlencode((string)$getCustomerID));
            exit();
        } catch (Exception $e) {
            $status = false;
            $MessageClass = 'alert-danger';
            $message = 'Unable to update the customer: ' . $e->getMessage();
        }
    } else {
        $message = implode("\n", $errors);
        $MessageClass = 'alert-danger';
    }
}

if ($message !== '' && !$MessageClass) {
    $MessageClass = $status ? 'alert-success' : 'alert-danger';
}

if (isset($_SESSION['flash_customer_message'])) {
    $message = (string) $_SESSION['flash_customer_message'];
    $MessageClass = (string) ($_SESSION['flash_customer_class'] ?? 'alert-success');
    unset($_SESSION['flash_customer_message'], $_SESSION['flash_customer_class']);
}

$countries = $db->getRows('SELECT * FROM countries ORDER BY country_name ASC');
$paymentTerms = $db->getRows('SELECT * FROM payment_terms ORDER BY payment_terms_name ASC');
$priceTypes = $db->getRows('SELECT * FROM price_type ORDER BY description ASC');
$discountCodes = $db->getRows('SELECT id, code, description, percentage FROM discount_code ORDER BY code ASC');

// Build country options for JavaScript
$countryOptions = '';
foreach ($countries as $country) {
    $countryOptions .= '<option value="' . addslashes(h($country['country_name'])) . '">' . addslashes(h($country['country_name'])) . '</option>';
}

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
        <title>Edit Customer</title>
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
        </style>
       </head>
    <!-- END HEAD -->
<!-- textboxes filter only numbers  -->
  <SCRIPT language=Javascript>
       <!--
       function isNumberKey(evt)
       {
          var charCode = (evt.which) ? evt.which : evt.keyCode;
          if (charCode != 46 && charCode > 31 
            && (charCode < 48 || charCode > 57))
             return false;

          return true;
       }
       //-->
    </SCRIPT>
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
                                <a href="#">Edit Customer</a>
                               
                            </li>
                         
                        </ul>
                      
                    </div>
                    <!-- END PAGE BAR -->
                    <!-- BEGIN PAGE TITLE-->
                    <div class="alert <?php echo $MessageClass; ?> alert-dismissable">
                                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                                        <?php echo $message; ?>
                                    </div>
                    <!-- END PAGE TITLE-->
                    <!-- END PAGE HEADER-->
                  
                    <div class="row">
                        <div class="col-md-12">
                        <div class="portlet box blue ">
                                            <div class="portlet-title">
                                                <div class="caption">
                                                    <i class="fa fa-gift"></i>Edit Customer Details</div>
                                                <div class="tools">
                                                    <a href="javascript:;" class="collapse" data-original-title="" title=""> </a>
                                                    <a href="#portlet-config" data-toggle="modal" class="config" data-original-title="" title=""> </a>
                                                    <a href="javascript:;" class="reload" data-original-title="" title=""> </a>
                                                    <a href="javascript:;" class="remove" data-original-title="" title=""> </a>
                                                </div>
                                            </div>
                                            <div class="portlet-body form">
                                                <!-- BEGIN FORM-->
                                                <form action="" class="form-horizontal form-bordered form-row-stripped" method="POST" enctype="multipart/form-data">
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
                                                                            <input type="text" class="form-control" name="customer_code" value="<?php echo h($formData['customer_code']); ?>" placeholder="Customer Code">
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Customer Name <span style="color: red;">*</span></label>
                                                                            <input type="text" class="form-control" name="customer_name" value="<?php echo h($formData['customer_name']); ?>" placeholder="Customer Name" required>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Legal Name</label>
                                                                            <input type="text" class="form-control" name="legal_name" value="<?php echo h($formData['legal_name']); ?>" placeholder="Legal Name">
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Trading Name</label>
                                                                            <input type="text" class="form-control" name="trading_name" value="<?php echo h($formData['trading_name']); ?>" placeholder="Trading Name">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div style="border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 15px;">
                                                                            <h6 style="color: #667eea; margin-top: 0; margin-bottom: 10px;"><i class="fa fa-phone"></i> Contact Information</h6>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Email</label>
                                                                            <input type="email" class="form-control" name="customer_email" value="<?php echo h($formData['customer_email']); ?>" placeholder="Email">
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Additional Email Accounts</label>
                                                                            <div id="customer-additional-emails">
                                                                                <?php foreach ($formData['customer_additional_emails'] as $additionalEmail): ?>
                                                                                    <div class="input-group additional-email-row" style="margin-bottom: 8px;">
                                                                                        <input type="email" class="form-control" name="customer_additional_emails[]" value="<?php echo h($additionalEmail); ?>" placeholder="Additional email address">
                                                                                        <span class="input-group-btn">
                                                                                            <button type="button" class="btn btn-danger remove-additional-email"><i class="fa fa-trash"></i></button>
                                                                                        </span>
                                                                                    </div>
                                                                                <?php endforeach; ?>
                                                                            </div>
                                                                            <button type="button" class="btn btn-xs btn-primary" id="add-additional-email" style="margin-top: 5px;">
                                                                                <i class="fa fa-plus"></i> Add Email
                                                                            </button>
                                                                            <span class="help-block">Add extra email addresses for customer notifications.</span>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Contact Phone</label>
                                                                            <input type="text" class="form-control" name="customer_phone" value="<?php echo h($formData['customer_phone']); ?>" placeholder="Contact Phone">
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Contact Mobile</label>
                                                                            <input type="text" class="form-control" name="customer_mobile" value="<?php echo h($formData['customer_mobile']); ?>" placeholder="Contact Mobile">
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
                                                                            <input type="text" class="form-control" name="address_line_1" value="<?php echo h($formData['address_line_1']); ?>" placeholder="Address Line 1" required>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Address Line 2</label>
                                                                            <input type="text" class="form-control" name="address_line_2" value="<?php echo h($formData['address_line_2']); ?>" placeholder="Address Line 2">
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">City / Town</label>
                                                                            <input type="text" class="form-control" name="city" value="<?php echo h($formData['city']); ?>" placeholder="City / Town">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div style="border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 15px;">
                                                                            <h6 style="color: #28a745; margin-top: 0; margin-bottom: 10px;"><i class="fa fa-globe"></i> Location & Postal</h6>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">State</label>
                                                                            <input type="text" class="form-control" name="state" value="<?php echo h($formData['state']); ?>" placeholder="State">
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Country</label>
                                                                            <select class="form-control select2" name="country">
                                                                                <option value="">Select Country</option>
                                                                                <?php foreach ($countries as $country): ?>
                                                                                    <option value="<?php echo h($country['country_name']); ?>" <?php echo ($formData['country'] === $country['country_name']) ? 'selected' : ''; ?>><?php echo h($country['country_name']); ?></option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Postal Code</label>
                                                                            <input type="text" class="form-control" name="postal_code" value="<?php echo h($formData['postal_code']); ?>" placeholder="Postal Code">
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
                                                                            <input type="text" class="form-control autonumeric" name="credit_limit" value="<?php echo h($formData['credit_limit']); ?>" placeholder="Credit Limit">
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Outstanding Balance</label>
                                                                            <input type="text" class="form-control autonumeric" name="outstanding_balance" value="<?php echo h($formData['outstanding_balance']); ?>" placeholder="Outstanding Balance">
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Order Discount (%)</label>
                                                                            <input type="number" class="form-control" name="customer_discount" value="<?php echo h($formData['customer_discount']); ?>" placeholder="Discount" min="0" max="100" step="0.01">
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Line Discount (%)</label>
                                                                            <select class="form-control select2" name="line_discount_id">
                                                                                <option value="">-- No Line Discount --</option>
                                                                                <?php foreach ($discountCodes as $dc): ?>
                                                                                    <option value="<?php echo (int)$dc['id']; ?>" <?php echo ((string)$formData['line_discount_id'] === (string)$dc['id']) ? 'selected' : ''; ?>>
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
                                                                                    <option value="<?php echo h($term['payment_terms_id']); ?>" <?php echo ($formData['payment_terms_id'] == $term['payment_terms_id']) ? 'selected' : ''; ?>><?php echo h($term['payment_terms_name']); ?></option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Customer Price Type</label>
                                                                            <select class="form-control select2" name="customer_price_type_id">
                                                                                <option value="">Select Price Type</option>
                                                                                <?php foreach ($priceTypes as $type): ?>
                                                                                    <option value="<?php echo h($type['id']); ?>" <?php echo ($formData['customer_price_type_id'] == $type['id']) ? 'selected' : ''; ?>><?php echo h($type['description']); ?></option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Min Order Amount</label>
                                                                            <input type="text" class="form-control autonumeric" name="min_order_amount" value="<?php echo h($formData['min_order_amount']); ?>" placeholder="Min Order Amount">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div style="border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 15px;">
                                                                            <h6 style="color: #fd7e14; margin-top: 0; margin-bottom: 10px;"><i class="fa fa-gavel"></i> Compliance & Registration</h6>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">ABN</label>
                                                                            <input type="text" class="form-control" name="abn_no" value="<?php echo h($formData['abn_no']); ?>" placeholder="ABN">
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">ACN</label>
                                                                            <input type="text" class="form-control" name="acn_no" value="<?php echo h($formData['acn_no']); ?>" placeholder="ACN">
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">GST Registered</label>
                                                                            <div class="checkbox-list">
                                                                                <label class="checkbox-inline">
                                                                                    <input type="checkbox" name="vat_registered" value="1" <?php echo $formData['vat_registered'] ? 'checked' : ''; ?>> Yes
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">GST Number</label>
                                                                            <input type="text" class="form-control" name="gst_no" value="<?php echo h($formData['gst_no']); ?>" placeholder="GST Number">
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
                                                                                <?php foreach ($formData['compliance_contact_emails'] as $cce): ?>
                                                                                    <div class="input-group compliance-contact-email-row" style="margin-bottom: 8px;">
                                                                                        <input type="email" class="form-control" name="compliance_contact_emails[]" value="<?php echo h($cce); ?>" placeholder="Contact email address">
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
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Compliance PDF <span class="text-muted" style="font-weight: normal; font-size: 12px;">(<?php echo count($complianceDocuments); ?>/5)</span></label>
                                                                            <?php if (count($complianceDocuments) < 5): ?>
                                                                                <input type="file" class="form-control" name="compliance_pdf[]" accept=".pdf,application/pdf" multiple>
                                                                                <span class="help-block">PDF only, max 5MB each. Up to <?php echo 5 - count($complianceDocuments); ?> more file(s) can be added.</span>
                                                                            <?php else: ?>
                                                                                <div class="text-muted" style="margin-top: 6px; font-size: 13px;">Maximum of 5 compliance documents reached. Delete one to upload another.</div>
                                                                            <?php endif; ?>
                                                                            <?php if (!empty($complianceDocuments)): ?>
                                                                                <div style="margin-top: 10px;">
                                                                                    <?php foreach ($complianceDocuments as $doc): ?>
                                                                                        <div class="well well-sm" style="margin-top: 6px; margin-bottom: 6px; background: #fff8e1; border-color: #ffe082;">
                                                                                            <div style="font-weight: 600; color: #555; margin-bottom: 6px;">
                                                                                                <i class="fa fa-file-pdf-o" style="color: #dc3545;"></i>
                                                                                                <?php echo h($doc['file_name']); ?>
                                                                                            </div>
                                                                                            <a href="../<?php echo h($doc['file_path']); ?>" class="btn btn-sm btn-default" download="<?php echo h($doc['file_name']); ?>" target="_blank" rel="noopener">
                                                                                                <i class="fa fa-download"></i> Download
                                                                                            </a>
                                                                                            <button type="button" class="btn btn-sm btn-danger delete-compliance-pdf-btn" data-document-id="<?php echo (int) $doc['id']; ?>" style="margin-left: 8px;">
                                                                                                <i class="fa fa-trash"></i> Delete
                                                                                            </button>
                                                                                            <?php if (!empty($doc['updated_at'])): ?>
                                                                                                <span class="text-muted" style="margin-left: 10px; font-size: 12px;">Updated: <?php echo h(date('Y-m-d H:i', strtotime($doc['updated_at']))); ?></span>
                                                                                            <?php endif; ?>
                                                                                        </div>
                                                                                    <?php endforeach; ?>
                                                                                </div>
                                                                            <?php else: ?>
                                                                                <div class="text-muted" style="margin-top: 8px;">No compliance documents uploaded.</div>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row" style="margin-top: 10px;">
                                                                    <div class="col-md-6">
                                                                        <div style="border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 15px;">
                                                                            <h6 style="color: #fd7e14; margin-top: 0; margin-bottom: 10px;"><i class="fa fa-user"></i> Contact Information</h6>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Contact Name</label>
                                                                            <input type="text" class="form-control" name="contact_name" value="<?php echo h($formData['contact_name']); ?>" placeholder="Contact Name">
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Contact Email</label>
                                                                            <input type="email" class="form-control" name="contact_email" value="<?php echo h($formData['contact_email']); ?>" placeholder="Contact Email">
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Contact Telephone</label>
                                                                            <input type="text" class="form-control" name="contact_telephone" value="<?php echo h($formData['contact_telephone']); ?>" placeholder="Contact Telephone">
                                                                        </div>
                                                                        <div style="border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 15px; margin-top: 15px;">
                                                                            <h6 style="color: #fd7e14; margin-top: 0; margin-bottom: 10px;"><i class="fa fa-link"></i> Links</h6>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Custom URL Link</label>
                                                                            <input type="url" class="form-control" name="custom_url_link" value="<?php echo h($formData['custom_url_link']); ?>" placeholder="Custom URL Link">
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Google Map Link</label>
                                                                            <input type="url" class="form-control" name="google_map_link" value="<?php echo h($formData['google_map_link']); ?>" placeholder="Google Map Link">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div style="border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 15px;">
                                                                            <h6 style="color: #fd7e14; margin-top: 0; margin-bottom: 10px;"><i class="fa fa-phone"></i> Emergency Contact</h6>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Emergency Contact Name</label>
                                                                            <input type="text" class="form-control" name="emergency_contact_name" value="<?php echo h($formData['emergency_contact_name']); ?>" placeholder="Emergency Contact Name">
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Emergency Contact Email</label>
                                                                            <input type="email" class="form-control" name="emergency_contact_email" value="<?php echo h($formData['emergency_contact_email']); ?>" placeholder="Emergency Contact Email">
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Emergency Contact Telephone</label>
                                                                            <input type="text" class="form-control" name="emergency_contact_telephone" value="<?php echo h($formData['emergency_contact_telephone']); ?>" placeholder="Emergency Contact Telephone">
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
                                                                                Super admin can change customer activation and account lock here.
                                                                            </div>
                                                                            <div class="form-group" style="margin-bottom: 10px;">
                                                                                <label class="control-label" style="font-weight: 600; color: #555;">Active</label>
                                                                                <div class="checkbox-list">
                                                                                    <label class="checkbox-inline">
                                                                                        <input type="checkbox" name="is_active" value="1" <?php echo $formData['is_active'] ? 'checked' : ''; ?>> Yes
                                                                                    </label>
                                                                                </div>
                                                                            </div>
                                                                            <div class="form-group" style="margin-bottom: 10px;">
                                                                                <label class="control-label" style="font-weight: 600; color: #555;">Locked</label>
                                                                                <div class="checkbox-list">
                                                                                    <label class="checkbox-inline">
                                                                                        <input type="checkbox" name="locked" value="1" <?php echo $formData['locked'] ? 'checked' : ''; ?>> Yes
                                                                                    </label>
                                                                                </div>
                                                                            </div>
                                                                        <?php else: ?>
                                                                            <div class="alert alert-warning" style="margin-bottom: 15px; padding: 10px 12px;">
                                                                                Only super admin can change activation or account lock for a customer.
                                                                            </div>
                                                                            <div class="form-group" style="margin-bottom: 10px;">
                                                                                <label class="control-label" style="font-weight: 600; color: #555;">Active</label>
                                                                                <div class="form-control-static">
                                                                                    <span class="label <?php echo $formData['is_active'] ? 'label-success' : 'label-default'; ?>">
                                                                                        <?php echo $formData['is_active'] ? 'Yes' : 'No'; ?>
                                                                                    </span>
                                                                                </div>
                                                                            </div>
                                                                            <div class="form-group" style="margin-bottom: 10px;">
                                                                                <label class="control-label" style="font-weight: 600; color: #555;">Locked</label>
                                                                                <div class="form-control-static">
                                                                                    <span class="label <?php echo $formData['locked'] ? 'label-danger' : 'label-success'; ?>">
                                                                                        <?php echo $formData['locked'] ? 'Yes' : 'No'; ?>
                                                                                    </span>
                                                                                </div>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div style="border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 15px;">
                                                                            <h6 style="color: #dc3545; margin-top: 0; margin-bottom: 10px;"><i class="fa fa-clock"></i> Repeat Settings</h6>
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Repeat Interval</label>
                                                                            <input type="number" class="form-control" name="repeat_interval" value="<?php echo h($formData['repeat_interval']); ?>" placeholder="e.g. 7" min="1">
                                                                        </div>
                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Repeat Unit</label>
                                                                            <select class="form-control" name="repeat_unit">
                                                                                <option value="">Select Unit</option>
                                                                                <?php
                                                                                $repeatUnits = getRepeatUnits();
                                                                                foreach ($repeatUnits as $unit) {
                                                                                    $selected = $formData['repeat_unit'] == $unit['id'] ? 'selected' : '';
                                                                                    echo '<option value="' . h($unit['id']) . '" ' . $selected . '>' . h($unit['display_name']) . '</option>';
                                                                                }
                                                                                ?>
                                                                            </select>
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
                                                                            <textarea class="form-control summernote" name="customer_note" rows="3"><?php echo h($formData['customer_note']); ?></textarea>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="section-card">
                                                            <h4 class="section-title">
                                                                <i class="fa fa-truck"></i> Shipping Addresses
                                                                <button type="button" class="btn btn-xs btn-primary pull-right" id="add-shipping-address">
                                                                    <i class="fa fa-plus"></i> Add Address
                                                                </button>
                                                            </h4>
                                                            <div id="shipping-addresses">
                                                                <?php foreach ($shippingData as $index => $address): ?>
                                                                    <div class="shipping-address-item panel panel-default" data-index="<?php echo $index; ?>" style="margin-bottom: 20px; border: 1px solid #e7ecf1;">
                                                                        <div class="panel-heading" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 15px;">
                                                                            <h5 class="panel-title" style="margin: 0; font-size: 14px;">
                                                                                <i class="fa fa-map-marker"></i>
                                                                                <strong><?php echo h($address['label'] ?: 'Shipping Address ' . ($index + 1)); ?></strong>
                                                                                <?php if ($address['is_default']): ?>
                                                                                    <span class="badge" style="background: #28a745; margin-left: 10px;">Default</span>
                                                                                <?php endif; ?>
                                                                                <button type="button" class="btn btn-xs btn-danger pull-right remove-shipping-address" style="margin-top: -2px;">
                                                                                    <i class="fa fa-trash"></i> Remove
                                                                                </button>
                                                                            </h5>
                                                                        </div>
                                                                        <div class="panel-body" style="padding: 20px;">
                                                                            <?php if (isset($address['id']) && $address['id']): ?>
                                                                                <input type="hidden" name="shipping_existing_id[<?php echo $index; ?>]" value="<?php echo (int)$address['id']; ?>">
                                                                            <?php endif; ?>
                                                                            <!-- Address Information -->
                                                                            <div class="row">
                                                                                <div class="col-md-6">
                                                                                    <div style="border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 15px;">
                                                                                        <h6 style="color: #667eea; margin-top: 0; margin-bottom: 10px;"><i class="fa fa-home"></i> Address Details</h6>
                                                                                    </div>
                                                                                    <div class="form-group" style="margin-bottom: 10px;">
                                                                                        <label class="control-label" style="font-weight: 600; color: #555;">Label</label>
                                                                                        <input type="text" class="form-control" name="shipping_label[<?php echo $index; ?>]" value="<?php echo h($address['label']); ?>" placeholder="e.g., Main Store, Warehouse">
                                                                                    </div>
                                                                                    <div class="form-group" style="margin-bottom: 10px;">
                                                                                        <label class="control-label" style="font-weight: 600; color: #555;">Address Line 1 <span style="color: red;">*</span></label>
                                                                                        <input type="text" class="form-control" name="shipping_address_line_1[<?php echo $index; ?>]" value="<?php echo h($address['address_line_1']); ?>" placeholder="Street address" required>
                                                                                    </div>
                                                                                    <div class="form-group" style="margin-bottom: 10px;">
                                                                                        <label class="control-label" style="font-weight: 600; color: #555;">Address Line 2</label>
                                                                                        <input type="text" class="form-control" name="shipping_address_line_2[<?php echo $index; ?>]" value="<?php echo h($address['address_line_2']); ?>" placeholder="Apartment, suite, etc.">
                                                                                    </div>
                                                                                    <div class="row">
                                                                                        <div class="col-md-6">
                                                                                            <div class="form-group" style="margin-bottom: 10px;">
                                                                                                <label class="control-label" style="font-weight: 600; color: #555;">City</label>
                                                                                                <input type="text" class="form-control" name="shipping_city[<?php echo $index; ?>]" value="<?php echo h($address['city']); ?>" placeholder="City">
                                                                                            </div>
                                                                                        </div>
                                                                                        <div class="col-md-6">
                                                                                            <div class="form-group" style="margin-bottom: 10px;">
                                                                                                <label class="control-label" style="font-weight: 600; color: #555;">Postal Code</label>
                                                                                                <input type="text" class="form-control" name="shipping_postal_code[<?php echo $index; ?>]" value="<?php echo h($address['postal_code']); ?>" placeholder="Postal code">
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="row">
                                                                                        <div class="col-md-6">
                                                                                            <div class="form-group" style="margin-bottom: 10px;">
                                                                                                <label class="control-label" style="font-weight: 600; color: #555;">State</label>
                                                                                                <input type="text" class="form-control" name="shipping_state[<?php echo $index; ?>]" value="<?php echo h($address['state']); ?>" placeholder="State">
                                                                                            </div>
                                                                                        </div>
                                                                                        <div class="col-md-6">
                                                                                            <div class="form-group" style="margin-bottom: 10px;">
                                                                                                <label class="control-label" style="font-weight: 600; color: #555;">Country</label>
                                                                                                <select class="form-control select2" name="shipping_country[<?php echo $index; ?>]">
                                                                                                    <option value="">Select Country</option>
                                                                                                    <?php foreach ($countries as $country): ?>
                                                                                                        <option value="<?php echo h($country['country_name']); ?>" <?php echo ($address['country'] === $country['country_name']) ? 'selected' : ''; ?>><?php echo h($country['country_name']); ?></option>
                                                                                                    <?php endforeach; ?>
                                                                                                </select>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>

                                                                                <div class="col-md-6">
                                                                                    <div style="border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 15px;">
                                                                                        <h6 style="color: #667eea; margin-top: 0; margin-bottom: 10px;"><i class="fa fa-user"></i> Contact Information</h6>
                                                                                    </div>
                                                                                    <div class="form-group" style="margin-bottom: 10px;">
                                                                                        <label class="control-label" style="font-weight: 600; color: #555;">Contact Number</label>
                                                                                        <input type="text" class="form-control" name="shipping_contact_no[<?php echo $index; ?>]" value="<?php echo h($address['contact_no']); ?>" placeholder="Phone number">
                                                                                    </div>
                                                                                    <div class="form-group" style="margin-bottom: 10px;">
                                                                                        <label class="control-label" style="font-weight: 600; color: #555;">Contact Person Name</label>
                                                                                        <input type="text" class="form-control" name="shipping_contact_person_name[<?php echo $index; ?>]" value="<?php echo h($address['contact_person_name']); ?>" placeholder="Contact person">
                                                                                    </div>
                                                                                    <div class="form-group" style="margin-bottom: 10px;">
                                                                                        <label class="control-label" style="font-weight: 600; color: #555;">Contact Person Phone</label>
                                                                                        <input type="text" class="form-control" name="shipping_contact_person_phone[<?php echo $index; ?>]" value="<?php echo h($address['contact_person_phone']); ?>" placeholder="Contact phone">
                                                                                    </div>
                                                                                    <div class="form-group" style="margin-bottom: 10px;">
                                                                                        <label class="control-label" style="font-weight: 600; color: #555;">Contact Person Email</label>
                                                                                        <input type="email" class="form-control" name="shipping_contact_person_email[<?php echo $index; ?>]" value="<?php echo h($address['contact_person_email']); ?>" placeholder="Contact email">
                                                                                    </div>
                                                                                    <div class="form-group" style="margin-bottom: 10px;">
                                                                                        <label class="control-label" style="font-weight: 600; color: #555;">Additional Emails</label>
                                                                                        <div class="shipping-additional-emails" id="shipping-additional-emails-<?php echo $index; ?>">
                                                                                            <?php foreach ($address['additional_emails'] ?? [] as $addrEmail): ?>
                                                                                                <div class="input-group shipping-additional-email-row" style="margin-bottom: 8px;">
                                                                                                    <input type="email" class="form-control" name="shipping_additional_emails[<?php echo $index; ?>][]" value="<?php echo h($addrEmail); ?>" placeholder="Additional email address">
                                                                                                    <span class="input-group-btn">
                                                                                                        <button type="button" class="btn btn-danger remove-shipping-additional-email"><i class="fa fa-trash"></i></button>
                                                                                                    </span>
                                                                                                </div>
                                                                                            <?php endforeach; ?>
                                                                                        </div>
                                                                                        <button type="button" class="btn btn-xs btn-primary add-shipping-additional-email" data-index="<?php echo $index; ?>" style="margin-top: 5px;">
                                                                                            <i class="fa fa-plus"></i> Add Email
                                                                                        </button>
                                                                                    </div>
                                                                                    <div class="form-group" style="margin-bottom: 10px;">
                                                                                        <label class="control-label" style="font-weight: 600; color: #555;">Delivery Note</label>
                                                                                        <textarea class="form-control" name="shipping_remarks[<?php echo $index; ?>]" rows="2" placeholder="Additional notes"><?php echo h($address['remarks']); ?></textarea>
                                                                                    </div>
                                                                                    <div class="form-group" style="margin-bottom: 10px;">
                                                                                        <label class="control-label" style="font-weight: 600; color: #555;">Driver Note</label>
                                                                                        <textarea class="form-control" name="shipping_note_to_deliver[<?php echo $index; ?>]" rows="2" placeholder="Special instructions for delivery"><?php echo h($address['note_to_deliver']); ?></textarea>
                                                                                    </div>
                                                                                </div>
                                                                            </div>

                                                                            <!-- Delivery & Security Information -->
                                                                            <div class="row" style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #f0f0f0;">
                                                                                <div class="col-md-6">
                                                                                    <div style="border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 15px;">
                                                                                        <h6 style="color: #667eea; margin-top: 0; margin-bottom: 10px;"><i class="fa fa-clock-o"></i> Delivery Schedule</h6>
                                                                                    </div>
                                                                                    <div class="row">
                                                                                        <div class="col-md-6">
                                                                                            <div class="form-group" style="margin-bottom: 10px;">
                                                                                                <label class="control-label" style="font-weight: 600; color: #555;">Start Time</label>
                                                                                                <input type="time" class="form-control" name="shipping_delivery_start_time[<?php echo $index; ?>]" value="<?php echo h($address['delivery_start_time']); ?>">
                                                                                            </div>
                                                                                        </div>
                                                                                        <div class="col-md-6">
                                                                                            <div class="form-group" style="margin-bottom: 10px;">
                                                                                                <label class="control-label" style="font-weight: 600; color: #555;">End Time</label>
                                                                                                <input type="time" class="form-control" name="shipping_delivery_end_time[<?php echo $index; ?>]" value="<?php echo h($address['delivery_end_time']); ?>">
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="form-group" style="margin-bottom: 10px;">
                                                                                        <label class="control-label" style="font-weight: 600; color: #555;">Delivery Route Group</label>
                                                                                        <select class="form-control delivery-route-group-select" name="shipping_delivery_route_group_id[<?php echo $index; ?>]" data-addr-index="<?php echo $index; ?>">
                                                                                            <option value="">All Routes (no group filter)</option>
                                                                                            <?php foreach (getDeliveryRouteGroups(true) as $g): ?>
                                                                                                <option value="<?php echo (int) $g['id']; ?>" <?php echo ((string) ($address['delivery_route_group_id'] ?? '') === (string) $g['id']) ? 'selected' : ''; ?>>
                                                                                                    <?php echo h($g['name']); ?>
                                                                                                </option>
                                                                                            <?php endforeach; ?>
                                                                                        </select>
                                                                                        <span class="help-block" style="margin-bottom: 0; font-size: 11px;">Filters the day-route matrix below.</span>
                                                                                    </div>
                                                                                    <?php
                                                                                    $globalDeliverySettings = getDeliveryRuleSettings();
                                                                                    $allDeliveryRules = getDeliveryRules(true);
                                                                                    ?>
                                                                                    <div style="margin-top: 12px; padding: 12px; border: 1px solid #e7eaec; border-radius: 4px; background: #fbfcfd;">
                                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                                            <label class="control-label" style="font-weight: 600; color: #2ec4a5;">Delivery price rule</label>
                                                                                            <select class="form-control" name="shipping_delivery_rule_id[<?php echo $index; ?>]">
                                                                                                <option value="">— None —</option>
                                                                                                <?php foreach ($allDeliveryRules as $r): ?>
                                                                                                    <option value="<?php echo (int) $r['id']; ?>" <?php echo ((string) ($address['delivery_rule_id'] ?? '') === (string) $r['id']) ? 'selected' : ''; ?>><?php echo h($r['name']); ?></option>
                                                                                                <?php endforeach; ?>
                                                                                            </select>
                                                                                            <span class="help-block" style="margin-bottom: 0; font-size: 11px;">Manage rules in <a href="manage-delivery-rules.php" target="_blank">Delivery Rules</a>.</span>
                                                                                        </div>
                                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                                            <label class="control-label" style="font-weight: 600; color: #2ec4a5;">SO Daily Average</label>
                                                                                            <div class="input-group">
                                                                                                <span class="input-group-addon">$</span>
                                                                                                <input type="number" step="0.01" min="0" class="form-control" name="shipping_so_daily_average[<?php echo $index; ?>]" value="<?php echo h($address['so_daily_average']); ?>" placeholder="<?php echo h($globalDeliverySettings['standing_order_daily_avg_min']); ?>">
                                                                                            </div>
                                                                                            <span class="help-block" style="margin-bottom: 0; font-size: 11px;">Overrides global value (<?php echo h($globalDeliverySettings['standing_order_daily_avg_min'] !== null && $globalDeliverySettings['standing_order_daily_avg_min'] !== '' ? '$' . $globalDeliverySettings['standing_order_daily_avg_min'] : 'not set'); ?>).</span>
                                                                                        </div>
                                                                                        <div class="form-group" style="margin-bottom: 10px;">
                                                                                            <label class="control-label" style="font-weight: 600; color: #2ec4a5;">Minimum Cart Order for Delivery</label>
                                                                                            <div class="input-group">
                                                                                                <span class="input-group-addon">$</span>
                                                                                                <input type="number" step="0.01" min="0" class="form-control" name="shipping_min_cart_order_override[<?php echo $index; ?>]" value="<?php echo h($address['min_cart_order_override']); ?>" placeholder="<?php echo h($globalDeliverySettings['min_cart_order']); ?>">
                                                                                            </div>
                                                                                            <span class="help-block" style="margin-bottom: 0; font-size: 11px;">Overrides global value (<?php echo h($globalDeliverySettings['min_cart_order'] !== null && $globalDeliverySettings['min_cart_order'] !== '' ? '$' . $globalDeliverySettings['min_cart_order'] : 'not set'); ?>).</span>
                                                                                        </div>
                                                                                        <div class="form-group" style="margin-bottom: 0;">
                                                                                            <label class="control-label" style="font-weight: 600; color: #2ec4a5;">Weekly average for free delivery</label>
                                                                                            <div class="input-group">
                                                                                                <span class="input-group-addon">$</span>
                                                                                                <input type="number" step="0.01" min="0" class="form-control" name="shipping_weekly_avg_free_delivery_override[<?php echo $index; ?>]" value="<?php echo h($address['weekly_avg_free_delivery_override']); ?>" placeholder="<?php echo h($globalDeliverySettings['weekly_avg_free_delivery']); ?>">
                                                                                            </div>
                                                                                            <span class="help-block" style="margin-bottom: 0; font-size: 11px;">Overrides global value (<?php echo h($globalDeliverySettings['weekly_avg_free_delivery'] !== null && $globalDeliverySettings['weekly_avg_free_delivery'] !== '' ? '$' . $globalDeliverySettings['weekly_avg_free_delivery'] : 'not set'); ?>).</span>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>

                                                                                <div class="col-md-6">
                                                                                    <div style="border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 15px;">
                                                                                        <h6 style="color: #667eea; margin-top: 0; margin-bottom: 10px;"><i class="fa fa-shield"></i> Security & Settings</h6>
                                                                                    </div>
                                                                                    <div class="form-group" style="margin-bottom: 15px;">
                                                                                        <label class="control-label" style="font-weight: 600; color: #555;">Security Features</label>
                                                                                        <div class="checkbox-list" style="padding: 10px; background: #f8f9fa; border-radius: 4px;">
                                                                                            <label class="checkbox-inline" style="margin-right: 20px;">
                                                                                                <input type="checkbox" name="shipping_has_door_key[<?php echo $index; ?>]" value="1" <?php echo $address['has_door_key'] ? 'checked' : ''; ?>> <i class="fa fa-key"></i> Door Key Available
                                                                                            </label>
                                                                                            <label class="checkbox-inline">
                                                                                                <input type="checkbox" name="shipping_has_shop_alarm[<?php echo $index; ?>]" value="1" <?php echo $address['has_shop_alarm'] ? 'checked' : ''; ?>> <i class="fa fa-bell"></i> Shop Alarm
                                                                                            </label>
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="form-group" style="margin-bottom: 10px;">
                                                                                        <label class="control-label" style="font-weight: 600; color: #555;">Set as Default Address</label>
                                                                                        <div class="radio-list" style="padding: 10px; background: #f8f9fa; border-radius: 4px;">
                                                                                            <label class="radio-inline">
                                                                                                <input type="radio" name="shipping_default" value="<?php echo $index; ?>" <?php echo $address['is_default'] ? 'checked' : ''; ?>> <i class="fa fa-star"></i> Yes, use as default
                                                                                            </label>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>

                                                                            <!-- Delivery Availability Section -->
                                                                            <div style="margin-top: 20px; padding: 15px; border: 2px solid #667eea; border-radius: 8px; background: #f0f4ff;">
                                                                                <h6 style="color: #667eea; margin-top: 0; margin-bottom: 15px; font-size: 15px; font-weight: 700;"><i class="fa fa-calendar"></i> Delivery Availability</h6>
                                                                                <?php if (isset($address['id']) && $address['id']): ?>
                                                                                    <?php $availability = getShippingAddressAvailability($address['id']); ?>
                                                                                    <div class="shipping-availability-display" data-shipping-id="<?php echo $address['id']; ?>">
                                                                                        <?php if ($availability): ?>
                                                                                            <div class="row">
                                                                                                <div class="col-md-12">
                                                                                                    <div class="table-responsive">
                                                                                                        <table class="table table-bordered table-striped" style="margin-bottom: 10px;">
                                                                                                            <thead>
                                                                                                                <tr style="background: #f8f9fa;">
                                                                                                                    <th class="text-center" style="width: 14.28%;">Mon</th>
                                                                                                                    <th class="text-center" style="width: 14.28%;">Tue</th>
                                                                                                                    <th class="text-center" style="width: 14.28%;">Wed</th>
                                                                                                                    <th class="text-center" style="width: 14.28%;">Thu</th>
                                                                                                                    <th class="text-center" style="width: 14.28%;">Fri</th>
                                                                                                                    <th class="text-center" style="width: 14.28%;">Sat</th>
                                                                                                                    <th class="text-center" style="width: 14.28%;">Sun</th>
                                                                                                                </tr>
                                                                                                            </thead>
                                                                                                            <tbody>
                                                                                                                <tr>
                                                                                                                    <td class="text-center">
                                                                                                                        <span class="label label-<?php echo $availability['mon'] ? 'success' : 'danger'; ?> label-sm">
                                                                                                                            <i class="fa fa-<?php echo $availability['mon'] ? 'check' : 'times'; ?>"></i>
                                                                                                                        </span>
                                                                                                                    </td>
                                                                                                                    <td class="text-center">
                                                                                                                        <span class="label label-<?php echo $availability['tue'] ? 'success' : 'danger'; ?> label-sm">
                                                                                                                            <i class="fa fa-<?php echo $availability['tue'] ? 'check' : 'times'; ?>"></i>
                                                                                                                        </span>
                                                                                                                    </td>
                                                                                                                    <td class="text-center">
                                                                                                                        <span class="label label-<?php echo $availability['wed'] ? 'success' : 'danger'; ?> label-sm">
                                                                                                                            <i class="fa fa-<?php echo $availability['wed'] ? 'check' : 'times'; ?>"></i>
                                                                                                                        </span>
                                                                                                                    </td>
                                                                                                                    <td class="text-center">
                                                                                                                        <span class="label label-<?php echo $availability['thu'] ? 'success' : 'danger'; ?> label-sm">
                                                                                                                            <i class="fa fa-<?php echo $availability['thu'] ? 'check' : 'times'; ?>"></i>
                                                                                                                        </span>
                                                                                                                    </td>
                                                                                                                    <td class="text-center">
                                                                                                                        <span class="label label-<?php echo $availability['fri'] ? 'success' : 'danger'; ?> label-sm">
                                                                                                                            <i class="fa fa-<?php echo $availability['fri'] ? 'check' : 'times'; ?>"></i>
                                                                                                                        </span>
                                                                                                                    </td>
                                                                                                                    <td class="text-center">
                                                                                                                        <span class="label label-<?php echo $availability['sat'] ? 'success' : 'danger'; ?> label-sm">
                                                                                                                            <i class="fa fa-<?php echo $availability['sat'] ? 'check' : 'times'; ?>"></i>
                                                                                                                        </span>
                                                                                                                    </td>
                                                                                                                    <td class="text-center">
                                                                                                                        <span class="label label-<?php echo $availability['sun'] ? 'success' : 'danger'; ?> label-sm">
                                                                                                                            <i class="fa fa-<?php echo $availability['sun'] ? 'check' : 'times'; ?>"></i>
                                                                                                                        </span>
                                                                                                                    </td>
                                                                                                                </tr>
                                                                                                            </tbody>
                                                                                                        </table>
                                                                                                    </div>
                                                                                                    <button type="button" class="btn btn-sm btn-warning edit-shipping-availability" data-shipping-id="<?php echo $address['id']; ?>" data-toggle="modal" data-target="#editShippingAvailabilityModal" onclick="document.getElementById('edit_shipping_address_id').value='<?php echo (int)$address['id']; ?>';" style="margin-bottom: 10px;">
                                                                                                        <i class="fa fa-edit"></i> Edit Availability
                                                                                                    </button>
                                                                                                </div>
                                                                                            </div>
                                                                                        <?php else: ?>
                                                                                            <div class="alert alert-info" style="margin-bottom: 10px; padding: 10px;">
                                                                                                <i class="fa fa-info-circle"></i> No delivery availability set. Deliveries can be made any day.
                                                                                            </div>
                                                                                            <button type="button" class="btn btn-sm btn-success add-shipping-availability" data-shipping-id="<?php echo $address['id']; ?>" data-toggle="modal" data-target="#addShippingAvailabilityModal" onclick="document.getElementById('add_shipping_address_id').value='<?php echo (int)$address['id']; ?>';" style="margin-bottom: 10px; font-size: 13px; padding: 6px 16px;">
                                                                                                <i class="fa fa-plus"></i> Set Availability
                                                                                            </button>
                                                                                        <?php endif; ?>
                                                                                    </div>
                                                                                <?php else: ?>
                                                                                    <!-- Delivery availability will appear after this address is saved -->
                                                                                <?php endif; ?>
                                                                            </div>

                                                                            <!-- Delivery Route per Day -->
                                                                            <?php
                                                                            $dayRoute = (isset($address['id']) && $address['id']) ? getShippingAddressDayRoute($address['id']) : null;
                                                                            $allDeliveryRoutes = getAllActiveRoutesWithGroups();
                                                                            $dayAvailability = (isset($address['id']) && $address['id']) ? getShippingAddressAvailability($address['id']) : null;
                                                                            $dayKeysForAvail = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
                                                                            $dayEnabled = [];
                                                                            foreach ($dayKeysForAvail as $dkA) {
                                                                                // If no availability record set, all days are enabled (default behavior)
                                                                                $dayEnabled[$dkA] = $dayAvailability ? (int)($dayAvailability[$dkA] ?? 0) === 1 : true;
                                                                            }
                                                                            ?>
                                                                            <div style="margin-top: 20px; padding: 15px; border: 2px solid #28a745; border-radius: 8px; background: #f0fff4;">
                                                                                <h6 style="color: #28a745; margin-top: 0; margin-bottom: 12px; font-size: 15px; font-weight: 700;"><i class="fa fa-road"></i> Delivery Route per Day</h6>
                                                                                <p class="text-muted" style="font-size: 12px; margin-bottom: 10px;">Select which delivery route is used for each day. Days disabled below are not available based on the Delivery Availability above.</p>
                                                                                <?php if (!empty($allDeliveryRoutes)): ?>
                                                                                <div class="table-responsive">
                                                                                    <table class="table table-bordered day-route-table" data-addr-index="<?php echo $index; ?>" style="margin-bottom: 8px; font-size: 13px;">
                                                                                        <thead>
                                                                                            <tr style="background: #e8f5e9;">
                                                                                                <th style="width: 22%; padding: 6px 10px;"><i class="fa fa-road"></i> Route</th>
                                                                                                <th class="text-center" style="width: 9%; padding: 6px 4px;">All</th>
                                                                                                <?php foreach ($dayKeysForAvail as $dkH): ?>
                                                                                                <th class="text-center day-route-header" data-day="<?php echo $dkH; ?>" style="width: 9%; padding: 6px 4px; <?php echo $dayEnabled[$dkH] ? '' : 'color:#bbb; text-decoration:line-through; background:#f5f5f5;'; ?>"><?php echo ucfirst($dkH); ?></th>
                                                                                                <?php endforeach; ?>
                                                                                            </tr>
                                                                                        </thead>
                                                                                        <tbody>
                                                                                            <?php
                                                                                            $dayKeys = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
                                                                                            foreach ($allDeliveryRoutes as $route):
                                                                                                $rid = (int)$route['id'];
                                                                                                $rowGroupAttr = h(implode(',', $route['group_ids'] ?? []));
                                                                                            ?>
                                                                                            <tr class="day-route-row" data-route-id="<?php echo $rid; ?>" data-groups="<?php echo $rowGroupAttr; ?>">
                                                                                                <td style="padding: 6px 10px; font-weight: 600; vertical-align: middle;">
                                                                                                    <?php echo h($route['route_name']); ?>
                                                                                                </td>
                                                                                                <td class="text-center" style="padding: 6px 4px; vertical-align: middle;">
                                                                                                    <input type="checkbox" class="day-route-all"
                                                                                                        data-addr-index="<?php echo $index; ?>"
                                                                                                        data-route-id="<?php echo $rid; ?>"
                                                                                                        title="Select this route for all days"
                                                                                                        <?php
                                                                                                        // Check if this route is set for ALL 7 days
                                                                                                        $allChecked = $dayRoute !== null;
                                                                                                        foreach ($dayKeys as $dk) {
                                                                                                            if ((int)($dayRoute[$dk . '_route_id'] ?? 0) !== $rid) { $allChecked = false; break; }
                                                                                                        }
                                                                                                        echo $allChecked ? 'checked' : '';
                                                                                                        ?>
                                                                                                    >
                                                                                                </td>
                                                                                                <?php foreach ($dayKeys as $dk): ?>
                                                                                                <td class="text-center day-route-cell" data-day="<?php echo $dk; ?>" style="padding: 6px 4px; vertical-align: middle; <?php echo $dayEnabled[$dk] ? '' : 'background:#f5f5f5;'; ?>">
                                                                                                    <input type="radio"
                                                                                                        name="shipping_day_route[<?php echo $index; ?>][<?php echo $dk; ?>]"
                                                                                                        value="<?php echo $rid; ?>"
                                                                                                        class="day-route-radio"
                                                                                                        data-addr-index="<?php echo $index; ?>"
                                                                                                        data-day="<?php echo $dk; ?>"
                                                                                                        data-route-id="<?php echo $rid; ?>"
                                                                                                        <?php echo ((int)($dayRoute[$dk . '_route_id'] ?? 0) === $rid) ? 'checked' : ''; ?>
                                                                                                        <?php echo $dayEnabled[$dk] ? '' : 'disabled'; ?>
                                                                                                    >
                                                                                                </td>
                                                                                                <?php endforeach; ?>
                                                                                            </tr>
                                                                                            <?php endforeach; ?>
                                                                                            <!-- "None" row to clear a day -->
                                                                                            <tr style="background: #fff8f8;">
                                                                                                <td style="padding: 6px 10px; color: #999; font-style: italic; vertical-align: middle;">— No Route —</td>
                                                                                                <td class="text-center" style="padding: 6px 4px; vertical-align: middle;">
                                                                                                    <input type="checkbox" class="day-route-all"
                                                                                                        data-addr-index="<?php echo $index; ?>"
                                                                                                        data-route-id="0"
                                                                                                        title="Clear route for all days"
                                                                                                        <?php
                                                                                                        $allNone = true;
                                                                                                        foreach ($dayKeys as $dk) {
                                                                                                            if ((int)($dayRoute[$dk . '_route_id'] ?? 0) !== 0) { $allNone = false; break; }
                                                                                                        }
                                                                                                        echo ($dayRoute === null || $allNone) ? 'checked' : '';
                                                                                                        ?>
                                                                                                    >
                                                                                                </td>
                                                                                                <?php foreach ($dayKeys as $dk): ?>
                                                                                                <td class="text-center day-route-cell" data-day="<?php echo $dk; ?>" style="padding: 6px 4px; vertical-align: middle; <?php echo $dayEnabled[$dk] ? '' : 'background:#f5f5f5;'; ?>">
                                                                                                    <input type="radio"
                                                                                                        name="shipping_day_route[<?php echo $index; ?>][<?php echo $dk; ?>]"
                                                                                                        value=""
                                                                                                        class="day-route-radio"
                                                                                                        data-addr-index="<?php echo $index; ?>"
                                                                                                        data-day="<?php echo $dk; ?>"
                                                                                                        data-route-id="0"
                                                                                                        <?php
                                                                                                        $val = (int)($dayRoute[$dk . '_route_id'] ?? 0);
                                                                                                        echo ($dayRoute === null || $val === 0) ? 'checked' : '';
                                                                                                        ?>
                                                                                                        <?php echo $dayEnabled[$dk] ? '' : 'disabled'; ?>
                                                                                                    >
                                                                                                </td>
                                                                                                <?php endforeach; ?>
                                                                                            </tr>
                                                                                        </tbody>
                                                                                    </table>
                                                                                </div>
                                                                                <?php else: ?>
                                                                                <div class="alert alert-warning" style="padding: 10px; margin-bottom: 0;">
                                                                                    <i class="fa fa-exclamation-triangle"></i> No active delivery routes found. <a href="add-delivery-route.php">Add routes</a> first.
                                                                                </div>
                                                                                <?php endif; ?>
                                                                            </div>

                                                                            <!-- Custom Attributes (Collapsible) -->
                                                                            <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #f0f0f0; display: none;">
                                                                                <button type="button" class="btn btn-xs btn-info" data-toggle="collapse" data-target="#attributes-<?php echo $index; ?>" style="margin-bottom: 10px;">
                                                                                    <i class="fa fa-plus-circle"></i> Additional Attributes (Optional)
                                                                                </button>
                                                                                <div id="attributes-<?php echo $index; ?>" class="collapse">
                                                                                    <div class="row">
                                                                                        <div class="col-md-4">
                                                                                            <div class="form-group">
                                                                                                <label class="control-label" style="font-weight: 600; color: #555;">Attribute 1</label>
                                                                                                <input type="text" class="form-control" name="shipping_attribute_1[<?php echo $index; ?>]" value="<?php echo h($address['attribute_1']); ?>" placeholder="Custom attribute">
                                                                                            </div>
                                                                                        </div>
                                                                                        <div class="col-md-4">
                                                                                            <div class="form-group">
                                                                                                <label class="control-label" style="font-weight: 600; color: #555;">Attribute 2</label>
                                                                                                <input type="text" class="form-control" name="shipping_attribute_2[<?php echo $index; ?>]" value="<?php echo h($address['attribute_2']); ?>" placeholder="Custom attribute">
                                                                                            </div>
                                                                                        </div>
                                                                                        <div class="col-md-4">
                                                                                            <div class="form-group">
                                                                                                <label class="control-label" style="font-weight: 600; color: #555;">Attribute 3</label>
                                                                                                <input type="text" class="form-control" name="shipping_attribute_3[<?php echo $index; ?>]" value="<?php echo h($address['attribute_3']); ?>" placeholder="Custom attribute">
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>

                                                            <?php if (empty($shippingData)): ?>
                                                                <div class="alert alert-info" style="margin-top: 20px;">
                                                                    <i class="fa fa-info-circle"></i> No shipping addresses configured. Click "Add Address" to get started.
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>

                                                        <div class="panel panel-info" style="margin-bottom: 20px;">
                                                            <div class="panel-heading" style="background: linear-gradient(135deg, #17a2b8 0%, #20c997 100%); color: white; padding: 12px 15px;">
                                                                <h4 class="panel-title" style="margin: 0; font-size: 16px; font-weight: 600;">
                                                                    <i class="fa fa-paperclip"></i> Customer Attachments (Max 5 Files & Notes)
                                                                </h4>
                                                            </div>
                                                            <div class="panel-body" style="padding: 20px;">
                                                                <!-- Existing Attachments -->
                                                                <?php if (!empty($attachments)): ?>
                                                                    <div class="row" style="margin-bottom: 20px;">
                                                                        <div class="col-md-12">
                                                                            <h6 style="color: #17a2b8; margin-bottom: 15px;"><i class="fa fa-folder-open"></i> Existing Attachments</h6>
                                                                            <div class="alert alert-info" style="margin-bottom: 15px;">
                                                                                <i class="fa fa-info-circle"></i> <strong>Note:</strong> File and note are stored in one row. Click delete once to remove that row.
                                                                            </div>
                                                                            <div class="table-responsive">
                                                                                <table class="table table-bordered table-striped">
                                                                                    <thead>
                                                                                        <tr style="background: #f8f9fa;">
                                                                                            <th>File</th>
                                                                                            <th>Note</th>
                                                                                            <th>Uploaded</th>
                                                                                            <th>Action</th>
                                                                                        </tr>
                                                                                    </thead>
                                                                                    <tbody>
                                                                                        <?php foreach ($attachments as $attachment): ?>
                                                                                            <tr data-has-file="<?php echo !empty($attachment['file_path']) ? '1' : '0'; ?>">
                                                                                                <td>
                                                                                                    <?php if (!empty($attachment['file_path'])): ?>
                                                                                                        <?php if ($attachment['is_image']): ?>
                                                                                <a href="javascript:void(0);" class="attachment-image-thumb" data-fullsrc="../<?php echo h($attachment['file_path']); ?>" data-filename="<?php echo h($attachment['display_name']); ?>" title="Click to preview image">
                                                                                    <img src="../<?php echo h($attachment['file_path']); ?>" alt="<?php echo h($attachment['display_name']); ?>" style="max-width: 100px; max-height: 100px;" class="img-thumbnail">
                                                                                </a>
                                                                                <br><a href="../<?php echo h($attachment['file_path']); ?>" target="_blank" rel="noopener"><?php echo h($attachment['display_name']); ?></a>
                                                                                                        <?php else: ?>
                                                                                <a href="../<?php echo h($attachment['file_path']); ?>" target="_blank" rel="noopener" style="display: inline-flex; align-items: center; gap: 8px;">
                                                                                    <i class="fa <?php echo h($attachment['icon_class']); ?>" style="font-size: 22px;"></i>
                                                                                    <span><?php echo h($attachment['display_name']); ?></span>
                                                                                </a>
                                                                                                        <?php endif; ?>
                                                                                                    <?php else: ?>
                                                                                <span class="text-muted">No file</span>
                                                                                                    <?php endif; ?>
                                                                                                </td>
                                                                                                <td>
                                                                                                    <?php if (!empty($attachment['content'])): ?>
                                                                                <div style="max-height: 100px; overflow-y: auto; background: #f8f9fa; padding: 8px; border-radius: 4px;">
                                                                                    <?php echo nl2br(h($attachment['content'])); ?>
                                                                                </div>
                                                                                                    <?php else: ?>
                                                                                <span class="text-muted">No note</span>
                                                                                                    <?php endif; ?>
                                                                                                </td>
                                                                                                <td><?php echo !empty($attachment['created_at']) ? date('Y-m-d H:i', strtotime($attachment['created_at'])) : '-'; ?></td>
                                                                                                <td>
                                                                                                    <button type="button" class="btn btn-xs btn-danger delete-attachment-btn" data-attachment-id="<?php echo (int) $attachment['id']; ?>">
                                                                                                        <i class="fa fa-trash"></i> Delete
                                                                                                    </button>
                                                                                                </td>
                                                                                            </tr>
                                                                                        <?php endforeach; ?>
                                                                                    </tbody>
                                                                                </table>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                <?php endif; ?>

                                                                <!-- Add New Attachments -->
                                                                <div class="row">
                                                                    <div class="col-md-12">
                                                                        <h6 style="color: #17a2b8; margin-bottom: 15px;"><i class="fa fa-plus-circle"></i> Add New Attachments</h6>
                                                                    </div>
                                                                </div>

                                                                <!-- File + Note Uploads -->
                                                                <div class="row">
                                                                    <div class="col-md-12">
                                                                        <div style="border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 15px;">
                                                                            <h6 style="color: #17a2b8; margin-top: 0; margin-bottom: 10px;"><i class="fa fa-paperclip"></i> Upload File + Note (Max 5 files)</h6>
                                                                        </div>
                                                                        <div class="alert alert-info" style="margin-bottom: 15px;">
                                                                            <i class="fa fa-file-text-o"></i>
                                                                            Current files: <strong id="existing-attachment-count"><?php echo (int) $existingAttachmentCount; ?></strong> / <?php echo (int) $maxAttachmentFiles; ?>
                                                                            &nbsp;|&nbsp; Add note in the same row to save File + Note together
                                                                            &nbsp;|&nbsp; Allowed types: PDF, JPG, PNG, GIF, WebP &nbsp;|&nbsp; Max size: 5MB each
                                                                        </div>
                                                                        <div id="attachment-upload-container">
                                                                            <?php for ($i = 0; $i < 5; $i++): ?>
                                                                                <div class="form-group attachment-upload-group" style="margin-bottom: 16px; <?php echo $i >= 1 ? 'display: none;' : ''; ?>" data-index="<?php echo $i; ?>">
                                                                                    <div class="row">
                                                                                        <div class="col-md-6">
                                                                                            <label class="control-label" style="font-weight: 600; color: #555;">File <?php echo $i + 1; ?></label>
                                                                                            <input type="file" class="form-control attachment-file-input" name="attachment_files[]" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,application/pdf,image/jpeg,image/png,image/gif,image/webp" onchange="showNextAttachmentUpload(<?php echo $i; ?>, this)">
                                                                                            <div class="attachment-preview" style="margin-top: 8px; display: none;"></div>
                                                                                            <span class="help-block">Upload PDF or image files (max 5MB each)</span>
                                                                                        </div>
                                                                                        <div class="col-md-6">
                                                                                            <label class="control-label" style="font-weight: 600; color: #555;">Note for File <?php echo $i + 1; ?></label>
                                                                                            <textarea class="form-control" name="attachment_file_notes[]" rows="4" placeholder="Add note for this file (optional)"></textarea>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            <?php endfor; ?>
                                                                        </div>
                                                                        <div style="margin-top: 10px;">
                                                                            <button type="button" id="add-attachment-row" class="btn btn-default btn-sm">
                                                                                <i class="fa fa-plus"></i> Add New File + Note
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-actions">
                                                        <div class="row">
                                                            <div class="col-md-offset-3 col-md-9">
                                                                <button type="submit" class="btn green">
                                                                    <i class="fa fa-check"></i> Update Customer</button>
                                                            </div>
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
        <!-- BEGIN PAGE LEVEL PLUGINS -->
        <script src="assets/global/scripts/datatable.js" type="text/javascript"></script>
        <script src="assets/global/plugins/datatables/datatables.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.js" type="text/javascript"></script>
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
        <script src="assets/global/plugins/select2/js/select2.full.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/summernote/summernote.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/autonumeric/autoNumeric.js" type="text/javascript"></script>
        <script>
            $(document).ready(function() {
                console.log('edit-customer: document.ready running; jQuery v=' + (jQuery && jQuery.fn ? jQuery.fn.jquery : 'none'));

                var shippingIndex = <?php echo count($shippingData); ?>;

                $(document).on('click', '#add-additional-email', function(e) {
                    try {
                        e.preventDefault();
                        var html = '<div class="input-group additional-email-row" style="margin-bottom: 8px;">' +
                            '<input type="email" class="form-control" name="customer_additional_emails[]" placeholder="Additional email address">' +
                            '<span class="input-group-btn">' +
                                '<button type="button" class="btn btn-danger remove-additional-email"><i class="fa fa-trash"></i></button>' +
                            '</span>' +
                        '</div>';
                        $('#customer-additional-emails').append(html);
                    } catch (err) {
                        console.error('edit-customer: error adding additional email row', err);
                    }
                });

                $(document).on('click', '.remove-additional-email', function(e) {
                    try {
                        e.preventDefault();
                        $(this).closest('.additional-email-row').remove();
                    } catch (err) {
                        console.error('edit-customer: error removing additional email row', err);
                    }
                });

                $(document).on('click', '#add-compliance-contact-email', function(e) {
                    try {
                        e.preventDefault();
                        var html = '<div class="input-group compliance-contact-email-row" style="margin-bottom: 8px;">' +
                            '<input type="email" class="form-control" name="compliance_contact_emails[]" placeholder="Contact email address">' +
                            '<span class="input-group-btn">' +
                                '<button type="button" class="btn btn-danger remove-compliance-contact-email"><i class="fa fa-trash"></i></button>' +
                            '</span>' +
                        '</div>';
                        $('#compliance-contact-emails').append(html);
                    } catch (err) {
                        console.error('edit-customer: error adding compliance contact email row', err);
                    }
                });

                $(document).on('click', '.remove-compliance-contact-email', function(e) {
                    try {
                        e.preventDefault();
                        $(this).closest('.compliance-contact-email-row').remove();
                    } catch (err) {
                        console.error('edit-customer: error removing compliance contact email row', err);
                    }
                });

                // Delegated handler ensures it works even if DOM changes
                $(document).on('click', '#add-shipping-address', function(e) {
                    try {
                        e.preventDefault();
                        console.log('Add shipping address clicked');
                        var html = '<div class="shipping-address-item panel panel-default" data-index="' + shippingIndex + '" style="margin-bottom: 20px; border: 1px solid #e7ecf1;">' +
                            '<div class="panel-heading" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 15px;">' +
                                '<h5 class="panel-title" style="margin: 0; font-size: 14px;">' +
                                    '<i class="fa fa-map-marker"></i> ' +
                                    '<strong>Shipping Address ' + (shippingIndex + 1) + '</strong> ' +
                                    '<button type="button" class="btn btn-xs btn-danger pull-right remove-shipping-address" style="margin-top: -2px;">' +
                                        '<i class="fa fa-trash"></i> Remove' +
                                    '</button>' +
                                '</h5>' +
                            '</div>' +
                            '<div class="panel-body" style="padding: 20px;">' +
                                '<div class="row">' +
                                    '<div class="col-md-6">' +
                                        '<div style="border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 15px;">' +
                                            '<h6 style="color: #667eea; margin-top: 0; margin-bottom: 10px;"><i class="fa fa-home"></i> Address Details</h6>' +
                                        '</div>' +
                                        '<div class="form-group" style="margin-bottom: 10px;">' +
                                            '<label class="control-label" style="font-weight: 600; color: #555;">Label</label>' +
                                            '<input type="text" class="form-control" name="shipping_label[' + shippingIndex + ']" placeholder="e.g., Main Store, Warehouse">' +
                                        '</div>' +
                                        '<div class="form-group" style="margin-bottom: 10px;">' +
                                            '<label class="control-label" style="font-weight: 600; color: #555;">Address Line 1 <span style="color: red;">*</span></label>' +
                                            '<input type="text" class="form-control" name="shipping_address_line_1[' + shippingIndex + ']" placeholder="Street address" required>' +
                                        '</div>' +
                                        '<div class="form-group" style="margin-bottom: 10px;">' +
                                            '<label class="control-label" style="font-weight: 600; color: #555;">Address Line 2</label>' +
                                            '<input type="text" class="form-control" name="shipping_address_line_2[' + shippingIndex + ']" placeholder="Apartment, suite, etc.">' +
                                        '</div>' +
                                        '<div class="row">' +
                                            '<div class="col-md-6">' +
                                                '<div class="form-group" style="margin-bottom: 10px;">' +
                                                    '<label class="control-label" style="font-weight: 600; color: #555;">City</label>' +
                                                    '<input type="text" class="form-control" name="shipping_city[' + shippingIndex + ']" placeholder="City">' +
                                                '</div>' +
                                            '</div>' +
                                            '<div class="col-md-6">' +
                                                '<div class="form-group" style="margin-bottom: 10px;">' +
                                                    '<label class="control-label" style="font-weight: 600; color: #555;">Postal Code</label>' +
                                                    '<input type="text" class="form-control" name="shipping_postal_code[' + shippingIndex + ']" placeholder="Postal code">' +
                                                '</div>' +
                                            '</div>' +
                                        '</div>' +
                                        '<div class="row">' +
                                            '<div class="col-md-6">' +
                                                '<div class="form-group" style="margin-bottom: 10px;">' +
                                                    '<label class="control-label" style="font-weight: 600; color: #555;">State</label>' +
                                                    '<input type="text" class="form-control" name="shipping_state[' + shippingIndex + ']" placeholder="State">' +
                                                '</div>' +
                                            '</div>' +
                                            '<div class="col-md-6">' +
                                                '<div class="form-group" style="margin-bottom: 10px;">' +
                                                    '<label class="control-label" style="font-weight: 600; color: #555;">Country</label>' +
                                                    '<select class="form-control select2" name="shipping_country[' + shippingIndex + ']">' +
                                                        '<option value="">Select Country</option>' +
                                                        '<?php echo $countryOptions; ?>' +
                                                    '</select>' +
                                                '</div>' +
                                            '</div>' +
                                        '</div>' +
                                    '</div>' +
                                    '<div class="col-md-6">' +
                                        '<div style="border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 15px;">' +
                                            '<h6 style="color: #667eea; margin-top: 0; margin-bottom: 10px;"><i class="fa fa-user"></i> Contact Information</h6>' +
                                        '</div>' +
                                        '<div class="form-group" style="margin-bottom: 10px;">' +
                                            '<label class="control-label" style="font-weight: 600; color: #555;">Contact Number</label>' +
                                            '<input type="text" class="form-control" name="shipping_contact_no[' + shippingIndex + ']" placeholder="Phone number">' +
                                        '</div>' +
                                        '<div class="form-group" style="margin-bottom: 10px;">' +
                                            '<label class="control-label" style="font-weight: 600; color: #555;">Contact Person Name</label>' +
                                            '<input type="text" class="form-control" name="shipping_contact_person_name[' + shippingIndex + ']" placeholder="Contact person">' +
                                        '</div>' +
                                        '<div class="form-group" style="margin-bottom: 10px;">' +
                                            '<label class="control-label" style="font-weight: 600; color: #555;">Contact Person Phone</label>' +
                                            '<input type="text" class="form-control" name="shipping_contact_person_phone[' + shippingIndex + ']" placeholder="Contact phone">' +
                                        '</div>' +
                                        '<div class="form-group" style="margin-bottom: 10px;">' +
                                            '<label class="control-label" style="font-weight: 600; color: #555;">Contact Person Email</label>' +
                                            '<input type="email" class="form-control" name="shipping_contact_person_email[' + shippingIndex + ']" placeholder="Contact email">' +
                                        '</div>' +
                                        '<div class="form-group" style="margin-bottom: 10px;">' +
                                            '<label class="control-label" style="font-weight: 600; color: #555;">Additional Emails</label>' +
                                            '<div class="shipping-additional-emails" id="shipping-additional-emails-' + shippingIndex + '"></div>' +
                                            '<button type="button" class="btn btn-xs btn-primary add-shipping-additional-email" data-index="' + shippingIndex + '" style="margin-top: 5px;">' +
                                                '<i class="fa fa-plus"></i> Add Email' +
                                            '</button>' +
                                        '</div>' +
                                        '<div class="form-group" style="margin-bottom: 10px;">' +
                                            '<label class="control-label" style="font-weight: 600; color: #555;">Remarks</label>' +
                                            '<textarea class="form-control" name="shipping_remarks[' + shippingIndex + ']" rows="2" placeholder="Additional notes"></textarea>' +
                                        '</div>' +
                                        '<div class="form-group" style="margin-bottom: 10px;">' +
                                            '<label class="control-label" style="font-weight: 600; color: #555;">Driver Note</label>' +
                                            '<textarea class="form-control" name="shipping_note_to_deliver[' + shippingIndex + ']" rows="2" placeholder="Special instructions for delivery"></textarea>' +
                                        '</div>' +
                                    '</div>' +
                                '</div>' +
                                '<div class="row" style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #f0f0f0;">' +
                                    '<div class="col-md-6">' +
                                        '<div style="border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 15px;">' +
                                            '<h6 style="color: #667eea; margin-top: 0; margin-bottom: 10px;"><i class="fa fa-clock-o"></i> Delivery Schedule</h6>' +
                                        '</div>' +
                                        '<div class="row">' +
                                            '<div class="col-md-6">' +
                                                '<div class="form-group" style="margin-bottom: 10px;">' +
                                                    '<label class="control-label" style="font-weight: 600; color: #555;">Start Time</label>' +
                                                    '<input type="time" class="form-control" name="shipping_delivery_start_time[' + shippingIndex + ']">' +
                                                '</div>' +
                                            '</div>' +
                                            '<div class="col-md-6">' +
                                                '<div class="form-group" style="margin-bottom: 10px;">' +
                                                    '<label class="control-label" style="font-weight: 600; color: #555;">End Time</label>' +
                                                    '<input type="time" class="form-control" name="shipping_delivery_end_time[' + shippingIndex + ']">' +
                                                '</div>' +
                                            '</div>' +
                                        '</div>' +
                                        '<div class="form-group" style="margin-bottom: 10px;">' +
                                            '<label class="control-label" style="font-weight: 600; color: #555;">Delivery Route Group</label>' +
                                            '<select class="form-control delivery-route-group-select" name="shipping_delivery_route_group_id[' + shippingIndex + ']" data-addr-index="' + shippingIndex + '">' +
                                                '<option value="">All Routes (no group filter)</option>' +
                                                '<?php
                                                foreach (getDeliveryRouteGroups(true) as $g) {
                                                    echo '<option value=\"' . (int) $g['id'] . '\">' . h($g['name']) . '</option>';
                                                }
                                                ?>' +
                                            '</select>' +
                                            '<span class="help-block" style="margin-bottom: 0; font-size: 11px;">Filters the day-route matrix below.</span>' +
                                        '</div>' +
                                        '<div style="margin-top: 12px; padding: 12px; border: 1px solid #e7eaec; border-radius: 4px; background: #fbfcfd;">' +
                                            '<div class="form-group" style="margin-bottom: 10px;">' +
                                                '<label class="control-label" style="font-weight: 600; color: #2ec4a5;">Delivery price rule</label>' +
                                                '<select class="form-control" name="shipping_delivery_rule_id[' + shippingIndex + ']">' +
                                                    '<option value="">— None —</option>' +
                                                    '<?php
                                                    $allDeliveryRulesTpl = getDeliveryRules(true);
                                                    foreach ($allDeliveryRulesTpl as $r) {
                                                        echo '<option value=\"' . (int) $r['id'] . '\">' . h($r['name']) . '</option>';
                                                    }
                                                    ?>' +
                                                '</select>' +
                                                '<span class="help-block" style="margin-bottom: 0; font-size: 11px;">Manage rules in <a href="manage-delivery-rules.php" target="_blank">Delivery Rules</a>.</span>' +
                                            '</div>' +
                                            '<div class="form-group" style="margin-bottom: 10px;">' +
                                                '<label class="control-label" style="font-weight: 600; color: #2ec4a5;">SO Daily Average</label>' +
                                                '<div class="input-group"><span class="input-group-addon">$</span>' +
                                                    '<input type="number" step="0.01" min="0" class="form-control" name="shipping_so_daily_average[' + shippingIndex + ']" value="">' +
                                                '</div>' +
                                                '<span class="help-block" style="margin-bottom: 0; font-size: 11px;">Overrides global value.</span>' +
                                            '</div>' +
                                            '<div class="form-group" style="margin-bottom: 10px;">' +
                                                '<label class="control-label" style="font-weight: 600; color: #2ec4a5;">Minimum Cart Order for Delivery</label>' +
                                                '<div class="input-group"><span class="input-group-addon">$</span>' +
                                                    '<input type="number" step="0.01" min="0" class="form-control" name="shipping_min_cart_order_override[' + shippingIndex + ']" value="">' +
                                                '</div>' +
                                                '<span class="help-block" style="margin-bottom: 0; font-size: 11px;">Overrides global value.</span>' +
                                            '</div>' +
                                            '<div class="form-group" style="margin-bottom: 0;">' +
                                                '<label class="control-label" style="font-weight: 600; color: #2ec4a5;">Weekly average for free delivery</label>' +
                                                '<div class="input-group"><span class="input-group-addon">$</span>' +
                                                    '<input type="number" step="0.01" min="0" class="form-control" name="shipping_weekly_avg_free_delivery_override[' + shippingIndex + ']" value="">' +
                                                '</div>' +
                                                '<span class="help-block" style="margin-bottom: 0; font-size: 11px;">Overrides global value.</span>' +
                                            '</div>' +
                                        '</div>' +
                                    '</div>' +
                                    '<div class="col-md-6">' +
                                        '<div style="border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 15px;">' +
                                            '<h6 style="color: #667eea; margin-top: 0; margin-bottom: 10px;"><i class="fa fa-shield"></i> Security & Settings</h6>' +
                                        '</div>' +
                                        '<div class="form-group" style="margin-bottom: 15px;">' +
                                            '<label class="control-label" style="font-weight: 600; color: #555;">Security Features</label>' +
                                            '<div class="checkbox-list" style="padding: 10px; background: #f8f9fa; border-radius: 4px;">' +
                                                '<label class="checkbox-inline" style="margin-right: 20px;">' +
                                                    '<input type="checkbox" name="shipping_has_door_key[' + shippingIndex + ']" value="1"> <i class="fa fa-key"></i> Door Key Available' +
                                                '</label>' +
                                                '<label class="checkbox-inline">' +
                                                    '<input type="checkbox" name="shipping_has_shop_alarm[' + shippingIndex + ']" value="1"> <i class="fa fa-bell"></i> Shop Alarm' +
                                                '</label>' +
                                            '</div>' +
                                        '</div>' +
                                        '<div class="form-group" style="margin-bottom: 10px;">' +
                                            '<label class="control-label" style="font-weight: 600; color: #555;">Set as Default Address</label>' +
                                            '<div class="radio-list" style="padding: 10px; background: #f8f9fa; border-radius: 4px;">' +
                                                '<label class="radio-inline">' +
                                                    '<input type="radio" name="shipping_default" value="' + shippingIndex + '"> <i class="fa fa-star"></i> Yes, use as default' +
                                                '</label>' +
                                            '</div>' +
                                        '</div>' +
                                    '</div>' +
                                '</div>' +
                                '<div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #f0f0f0;">' +
                                    '<button type="button" class="btn btn-xs btn-info" data-toggle="collapse" data-target="#attributes-' + shippingIndex + '" style="margin-bottom: 10px;">' +
                                        '<i class="fa fa-plus-circle"></i> Additional Attributes (Optional)' +
                                    '</button>' +
                                    '<div id="attributes-' + shippingIndex + '" class="collapse">' +
                                        '<div class="row">' +
                                            '<div class="col-md-4">' +
                                                '<div class="form-group">' +
                                                    '<label class="control-label" style="font-weight: 600; color: #555;">Attribute 1</label>' +
                                                    '<input type="text" class="form-control" name="shipping_attribute_1[' + shippingIndex + ']" placeholder="Custom attribute">' +
                                                '</div>' +
                                            '</div>' +
                                            '<div class="col-md-4">' +
                                                '<div class="form-group">' +
                                                    '<label class="control-label" style="font-weight: 600; color: #555;">Attribute 2</label>' +
                                                    '<input type="text" class="form-control" name="shipping_attribute_2[' + shippingIndex + ']" placeholder="Custom attribute">' +
                                                '</div>' +
                                            '</div>' +
                                            '<div class="col-md-4">' +
                                                '<div class="form-group">' +
                                                    '<label class="control-label" style="font-weight: 600; color: #555;">Attribute 3</label>' +
                                                    '<input type="text" class="form-control" name="shipping_attribute_3[' + shippingIndex + ']" placeholder="Custom attribute">' +
                                                '</div>' +
                                            '</div>' +
                                        '</div>' +
                                    '</div>' +
                                '</div>' +
                            '</div>' +
                        '</div>';
                        var $new = $(html);
                        $('#shipping-addresses').append($new);
                        shippingIndex++;
                        try { $new.find('.select2').select2(); } catch (e1) { console.warn('select2 init failed for new element', e1); }
                        try {
                            var newAddrIndex = $new.find('.delivery-route-group-select').data('addr-index');
                            if (typeof newAddrIndex !== 'undefined' && typeof applyRouteGroupFilter === 'function') {
                                applyRouteGroupFilter(newAddrIndex);
                            }
                        } catch (e2) { console.warn('route group filter init failed for new element', e2); }
                    } catch (err) {
                        console.error('edit-customer: error in add-shipping-address handler', err);
                    }
                });

                $(document).on('click', '.remove-shipping-address', function(e) {
                    try {
                        e.preventDefault();
                        console.log('Remove shipping address clicked');
                        $(this).closest('.shipping-address-item').remove();
                    } catch (err) {
                        console.error('edit-customer: error in remove-shipping-address handler', err);
                    }
                });

                $(document).on('click', '.add-shipping-additional-email', function(e) {
                    try {
                        e.preventDefault();
                        var idx = $(this).data('index');
                        var html = '<div class="input-group shipping-additional-email-row" style="margin-bottom: 8px;">' +
                            '<input type="email" class="form-control" name="shipping_additional_emails[' + idx + '][]" placeholder="Additional email address">' +
                            '<span class="input-group-btn">' +
                                '<button type="button" class="btn btn-danger remove-shipping-additional-email"><i class="fa fa-trash"></i></button>' +
                            '</span>' +
                        '</div>';
                        $('#shipping-additional-emails-' + idx).append(html);
                    } catch (err) {
                        console.error('edit-customer: error adding shipping additional email', err);
                    }
                });

                $(document).on('click', '.remove-shipping-additional-email', function(e) {
                    try {
                        e.preventDefault();
                        $(this).closest('.shipping-additional-email-row').remove();
                    } catch (err) {
                        console.error('edit-customer: error removing shipping additional email', err);
                    }
                });

                // Shipping Address Availability Handlers
                $(document).on('click', '.add-shipping-availability', function(e) {
                    try {
                        e.preventDefault();
                        var shippingId = $(this).data('shipping-id');
                        $('#add_shipping_address_id').val(shippingId);
                        $('#addShippingAvailabilityModal').modal('show');
                    } catch (err) {
                        console.error('edit-customer: error in add-shipping-availability handler', err);
                    }
                });

                $(document).on('click', '.edit-shipping-availability', function(e) {
                    try {
                        e.preventDefault();
                        var shippingId = $(this).data('shipping-id');
                        $('#edit_shipping_address_id').val(shippingId);

                        // Load current availability
                        $.ajax({
                            url: '',
                            type: 'POST',
                            data: { action: 'get_shipping_availability', shipping_address_id: shippingId },
                            dataType: 'json',
                            success: function(response) {
                                if (response.success && response.data) {
                                    var data = response.data;
                                    $('#edit_mon').prop('checked', data.mon == 1);
                                    $('#edit_tue').prop('checked', data.tue == 1);
                                    $('#edit_wed').prop('checked', data.wed == 1);
                                    $('#edit_thu').prop('checked', data.thu == 1);
                                    $('#edit_fri').prop('checked', data.fri == 1);
                                    $('#edit_sat').prop('checked', data.sat == 1);
                                    $('#edit_sun').prop('checked', data.sun == 1);

                                    // Update icons
                                    updateAvailabilityIcons();
                                }
                                $('#editShippingAvailabilityModal').modal('show');
                            },
                            error: function(xhr, status, error) {
                                console.error('Error loading shipping availability:', error);
                                alert('Error loading availability data. Please try again.');
                            }
                        });
                    } catch (err) {
                        console.error('edit-customer: error in edit-shipping-availability handler', err);
                    }
                });

                function updateAvailabilityIcons() {
                    var days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
                    days.forEach(function(day) {
                        var isChecked = $('#edit_' + day).is(':checked');
                        var icon = $('#edit_' + day + '_icon');
                        if (isChecked) {
                            icon.removeClass('fa-times text-danger').addClass('fa-check-circle text-success');
                        } else {
                            icon.removeClass('fa-check-circle text-success').addClass('fa-times text-danger');
                        }
                    });
                }

                function renderAvailabilityLabel(value) {
                    var statusClass = value ? 'success' : 'danger';
                    var icon = value ? 'check' : 'times';
                    return '<span class="label label-' + statusClass + ' label-sm"><i class="fa fa-' + icon + '"></i></span>';
                }

                function applyDayRouteAvailability(shippingId, availability) {
                    var $display = $('.shipping-availability-display[data-shipping-id="' + shippingId + '"]');
                    if (!$display.length) { return; }
                    var $item = $display.closest('.shipping-address-item');
                    var $table = $item.find('.day-route-table');
                    if (!$table.length) { return; }
                    var days = ['mon','tue','wed','thu','fri','sat','sun'];
                    days.forEach(function(d) {
                        var enabled = availability ? !!parseInt(availability[d], 10) : true;
                        var $cells = $table.find('.day-route-cell[data-day="' + d + '"]');
                        var $header = $table.find('.day-route-header[data-day="' + d + '"]');
                        $cells.each(function() {
                            $(this).css('background', enabled ? '' : '#f5f5f5');
                            var $radios = $(this).find('input.day-route-radio');
                            $radios.prop('disabled', !enabled);
                            if (!enabled) {
                                // Clear any selection for an unavailable day
                                $radios.prop('checked', false);
                            }
                        });
                        $header.css({
                            color: enabled ? '' : '#bbb',
                            'text-decoration': enabled ? '' : 'line-through',
                            background: enabled ? '' : '#f5f5f5'
                        });
                    });
                }

                function updateShippingAvailabilityDisplay(shippingId, availability) {
                    applyDayRouteAvailability(shippingId, availability);
                    var $display = $('.shipping-availability-display[data-shipping-id="' + shippingId + '"]');
                    if (!$display.length) {
                        return;
                    }
                    if (!availability) {
                        $display.html('<div class="alert alert-info" style="margin-bottom: 10px; padding: 10px;"><i class="fa fa-info-circle"></i> No delivery availability set. Deliveries can be made any day.</div><button type="button" class="btn btn-sm btn-success add-shipping-availability" data-shipping-id="' + shippingId + '" data-toggle="modal" data-target="#addShippingAvailabilityModal" onclick="document.getElementById(\'add_shipping_address_id\').value=\'' + shippingId + '\';" style="margin-bottom: 10px; font-size: 13px; padding: 6px 16px;"><i class="fa fa-plus"></i> Set Availability</button>');
                        return;
                    }
                    var html = '<div class="row"><div class="col-md-12"><div class="table-responsive"><table class="table table-bordered table-striped" style="margin-bottom: 10px;"><thead><tr style="background: #f8f9fa;"><th class="text-center" style="width: 14.28%;">Mon</th><th class="text-center" style="width: 14.28%;">Tue</th><th class="text-center" style="width: 14.28%;">Wed</th><th class="text-center" style="width: 14.28%;">Thu</th><th class="text-center" style="width: 14.28%;">Fri</th><th class="text-center" style="width: 14.28%;">Sat</th><th class="text-center" style="width: 14.28%;">Sun</th></tr></thead><tbody><tr><td class="text-center">' + renderAvailabilityLabel(availability.mon) + '</td><td class="text-center">' + renderAvailabilityLabel(availability.tue) + '</td><td class="text-center">' + renderAvailabilityLabel(availability.wed) + '</td><td class="text-center">' + renderAvailabilityLabel(availability.thu) + '</td><td class="text-center">' + renderAvailabilityLabel(availability.fri) + '</td><td class="text-center">' + renderAvailabilityLabel(availability.sat) + '</td><td class="text-center">' + renderAvailabilityLabel(availability.sun) + '</td></tr></tbody></table></div><button type="button" class="btn btn-sm btn-warning edit-shipping-availability" data-shipping-id="' + shippingId + '" data-toggle="modal" data-target="#editShippingAvailabilityModal" onclick="document.getElementById(\'edit_shipping_address_id\').value=\'' + shippingId + '\';" style="margin-bottom: 10px;"><i class="fa fa-edit"></i> Edit Availability</button></div></div>';
                    $display.html(html);
                }

                $(document).on('change', '#editShippingAvailabilityModal input[type="checkbox"]', function() {
                    updateAvailabilityIcons();
                });

                // Handle Add Shipping Availability Form
                $('#addShippingAvailabilityForm').on('submit', function(e) {
                    try {
                        e.preventDefault();
                        var formData = $(this).serialize();

                        $.ajax({
                            url: '',
                            type: 'POST',
                            data: formData + '&action=save_shipping_availability',
                            dataType: 'json',
                            success: function(response) {
                                if (response.success) {
                                    $('#addShippingAvailabilityModal').modal('hide');
                                    updateShippingAvailabilityDisplay($('#add_shipping_address_id').val(), {
                                        mon: ($('#addShippingAvailabilityForm input[name="mon"]').is(':checked') ? 1 : 0),
                                        tue: ($('#addShippingAvailabilityForm input[name="tue"]').is(':checked') ? 1 : 0),
                                        wed: ($('#addShippingAvailabilityForm input[name="wed"]').is(':checked') ? 1 : 0),
                                        thu: ($('#addShippingAvailabilityForm input[name="thu"]').is(':checked') ? 1 : 0),
                                        fri: ($('#addShippingAvailabilityForm input[name="fri"]').is(':checked') ? 1 : 0),
                                        sat: ($('#addShippingAvailabilityForm input[name="sat"]').is(':checked') ? 1 : 0),
                                        sun: ($('#addShippingAvailabilityForm input[name="sun"]').is(':checked') ? 1 : 0)
                                    });
                                } else {
                                    alert('Error: ' + (response.message || 'Failed to save availability'));
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('Error saving shipping availability:', error);
                                alert('Error saving availability. Please try again.');
                            }
                        });
                    } catch (err) {
                        console.error('edit-customer: error in add shipping availability form handler', err);
                    }
                });

                // Handle Edit Shipping Availability Form
                $('#editShippingAvailabilityForm').on('submit', function(e) {
                    try {
                        e.preventDefault();
                        var formData = $(this).serialize();

                        $.ajax({
                            url: '',
                            type: 'POST',
                            data: formData + '&action=save_shipping_availability',
                            dataType: 'json',
                            success: function(response) {
                                if (response.success) {
                                    $('#editShippingAvailabilityModal').modal('hide');
                                    updateShippingAvailabilityDisplay($('#edit_shipping_address_id').val(), {
                                        mon: ($('#editShippingAvailabilityForm input[name="mon"]').is(':checked') ? 1 : 0),
                                        tue: ($('#editShippingAvailabilityForm input[name="tue"]').is(':checked') ? 1 : 0),
                                        wed: ($('#editShippingAvailabilityForm input[name="wed"]').is(':checked') ? 1 : 0),
                                        thu: ($('#editShippingAvailabilityForm input[name="thu"]').is(':checked') ? 1 : 0),
                                        fri: ($('#editShippingAvailabilityForm input[name="fri"]').is(':checked') ? 1 : 0),
                                        sat: ($('#editShippingAvailabilityForm input[name="sat"]').is(':checked') ? 1 : 0),
                                        sun: ($('#editShippingAvailabilityForm input[name="sun"]').is(':checked') ? 1 : 0)
                                    });
                                } else {
                                    alert('Error: ' + (response.message || 'Failed to update availability'));
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('Error updating shipping availability:', error);
                                alert('Error updating availability. Please try again.');
                            }
                        });
                    } catch (err) {
                        console.error('edit-customer: error in edit shipping availability form handler', err);
                    }
                });

                // Initialize plugins last so handler binding is not affected by errors
                try { $('.select2').select2(); } catch (e) { console.warn('select2 init failed', e); }
                try { $('.summernote').summernote(); } catch (e) { console.warn('summernote init failed', e); }
                try { $('.autonumeric').autoNumeric('init', {aSep: ',', aDec: '.', mDec: 2}); } catch (e) { console.warn('autonumeric init failed', e); }

                // --- Per-shipping-address Delivery Route Group filter ---
                // When the group dropdown changes, hide/show route <option>s in the route <select>
                // and rows in the day-route matrix that belong to that address.
                function applyRouteGroupFilter(addrIndex) {
                    var $groupSel = $('.delivery-route-group-select[data-addr-index="' + addrIndex + '"]').first();
                    if (!$groupSel.length) return;
                    var groupId = String($groupSel.val() || '');

                    function rowMatches($el) {
                        if (groupId === '') return true; // no filter
                        var groupsAttr = String($el.attr('data-groups') || '');
                        if (groupsAttr === '') return false;
                        var ids = groupsAttr.split(',').map(function(s){ return s.trim(); }).filter(Boolean);
                        return ids.indexOf(groupId) !== -1;
                    }

                    // Filter <option>s in the route <select>
                    var $routeSel = $('.delivery-route-select[data-addr-index="' + addrIndex + '"]').first();
                    if ($routeSel.length) {
                        var $opts = $routeSel.find('option[data-groups]');
                        var currentVal = $routeSel.val();
                        var currentStillVisible = false;
                        $opts.each(function() {
                            var $o = $(this);
                            var match = rowMatches($o);
                            $o.prop('disabled', !match).toggle(match);
                            if (match && String($o.val()) === String(currentVal)) {
                                currentStillVisible = true;
                            }
                        });
                        if (!currentStillVisible) {
                            $routeSel.val('');
                        }
                    }

                    // Filter rows in the day-route matrix
                    var $rows = $('table.day-route-table[data-addr-index="' + addrIndex + '"] tr.day-route-row');
                    $rows.each(function() {
                        var $tr = $(this);
                        var match = rowMatches($tr);
                        $tr.toggle(match);
                        if (!match) {
                            // clear any radio selections for hidden rows
                            $tr.find('input.day-route-radio').prop('checked', false);
                            $tr.find('input.day-route-all').prop('checked', false);
                        }
                    });
                }

                $(document).on('change', '.delivery-route-group-select', function() {
                    applyRouteGroupFilter($(this).data('addr-index'));
                });

                // Apply on initial page load for each existing group select
                $('.delivery-route-group-select').each(function() {
                    applyRouteGroupFilter($(this).data('addr-index'));
                });

                // --- Delivery Route per Day: "All" checkbox handler ---
                $(document).on('change', '.day-route-all', function() {
                    var addrIndex = $(this).data('addr-index');
                    var routeId = $(this).data('route-id');
                    if ($(this).is(':checked')) {
                        // Check all day radio buttons in this row
                        $('input.day-route-radio[data-addr-index="' + addrIndex + '"][data-route-id="' + routeId + '"]').prop('checked', true);
                        // Uncheck "All" checkboxes for other routes in the same address
                        $('.day-route-all[data-addr-index="' + addrIndex + '"]').not(this).prop('checked', false);
                    } else {
                        // Uncheck all radio buttons for this route
                        $('input.day-route-radio[data-addr-index="' + addrIndex + '"][data-route-id="' + routeId + '"]').prop('checked', false);
                    }
                });

                // When a radio button changes, update the "All" checkbox state for that row
                $(document).on('change', '.day-route-radio', function() {
                    var addrIndex = $(this).data('addr-index');
                    var routeId = $(this).data('route-id');
                    var days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
                    // Check if all days now have this route selected
                    var allSelected = true;
                    for (var i = 0; i < days.length; i++) {
                        var checked = $('input.day-route-radio[data-addr-index="' + addrIndex + '"][data-day="' + days[i] + '"]:checked').data('route-id');
                        if (String(checked) !== String(routeId)) { allSelected = false; break; }
                    }
                    $('.day-route-all[data-addr-index="' + addrIndex + '"][data-route-id="' + routeId + '"]').prop('checked', allSelected);
                    // Uncheck "All" for other routes if this day changed
                    $('.day-route-all[data-addr-index="' + addrIndex + '"]').not('[data-route-id="' + routeId + '"]').each(function() {
                        var otherRouteId = $(this).data('route-id');
                        var otherAllSelected = true;
                        for (var i = 0; i < days.length; i++) {
                            var checked2 = $('input.day-route-radio[data-addr-index="' + addrIndex + '"][data-day="' + days[i] + '"]:checked').data('route-id');
                            if (String(checked2) !== String(otherRouteId)) { otherAllSelected = false; break; }
                        }
                        $(this).prop('checked', otherAllSelected);
                    });
                });

                // Attachment functions
                window.showNextAttachmentUpload = function(currentIndex, input) {
                    var file = input && input.files ? input.files[0] : null;
                    var $group = $(input).closest('.attachment-upload-group');
                    var $preview = $group.find('.attachment-preview');

                    if (!file) {
                        $preview.hide().empty();
                        return;
                    }

                    var allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
                    var allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'];
                    var maxSize = 5 * 1024 * 1024;
                    var safeFileName = $('<div>').text(file.name || '').html();
                    var fileName = file.name || '';
                    var extension = fileName.indexOf('.') !== -1 ? fileName.split('.').pop().toLowerCase() : '';
                    var isPdf = extension === 'pdf' || file.type === 'application/pdf';
                    var isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp'].indexOf(extension) !== -1 || file.type.indexOf('image/') === 0;

                    if (allowedTypes.indexOf(file.type) === -1 && allowedExtensions.indexOf(extension) === -1) {
                        alert('Only PDF, JPG, PNG, GIF, and WebP files are allowed.');
                        input.value = '';
                        $preview.hide().empty();
                        return;
                    }

                    if (file.size > maxSize) {
                        alert('File size must be 5MB or less.');
                        input.value = '';
                        $preview.hide().empty();
                        return;
                    }

                    if (isPdf) {
                        $preview.html('<div class="alert alert-info" style="margin-bottom: 0; padding: 8px 12px;">' +
                            '<i class="fa fa-file-pdf-o" style="margin-right: 8px;"></i>' + safeFileName + '</div>').show();
                        return;
                    }

                    if (!isImage) {
                        $preview.html('<div class="alert alert-info" style="margin-bottom: 0; padding: 8px 12px;">' +
                            '<i class="fa fa-file-o" style="margin-right: 8px;"></i>' + safeFileName + '</div>').show();
                        return;
                    }

                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $preview.html('<img src="' + e.target.result + '" class="img-thumbnail" style="max-width:120px; max-height:120px; margin-right:8px;">' +
                            '<small>' + safeFileName + '</small>').show();
                    };
                    reader.readAsDataURL(file);
                };

                $('#add-attachment-row').on('click', function() {
                    var $hiddenRows = $('.attachment-upload-group:hidden');
                    if ($hiddenRows.length > 0) {
                        $hiddenRows.first().show();
                    }

                    if ($('.attachment-upload-group:hidden').length === 0) {
                        $(this).prop('disabled', true).addClass('disabled');
                    }
                });

                if ($('.attachment-upload-group:hidden').length === 0) {
                    $('#add-attachment-row').prop('disabled', true).addClass('disabled');
                }

                $(document).on('click', '.attachment-image-thumb', function() {
                    var fullSrc = $(this).data('fullsrc') || '';
                    var fileName = $(this).data('filename') || 'Attachment Image';
                    $('#attachmentPreviewTitle').text(fileName);
                    $('#attachmentPreviewImage').attr('src', fullSrc).attr('alt', fileName);
                    $('#attachmentPreviewModal').modal('show');
                });

                $(document).on('click', '.delete-attachment-btn', function() {
                    var $btn = $(this);
                    var attachmentId = parseInt($btn.data('attachment-id'), 10);
                    var customerId = <?php echo (int) $getCustomerID; ?>;

                    if (!attachmentId) {
                        return;
                    }

                    if (!confirm('Delete this attachment row?')) {
                        return;
                    }

                    $btn.prop('disabled', true);

                    $.ajax({
                        url: '',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'delete_attachment',
                            attachment_id: attachmentId,
                            customer_id: customerId
                        },
                        success: function(response) {
                            if (response && response.success) {
                                var $row = $btn.closest('tr');
                                var hadFile = String($row.data('has-file')) === '1';
                                $row.remove();

                                if (hadFile) {
                                    var $count = $('#existing-attachment-count');
                                    var countVal = parseInt($count.text(), 10);
                                    if (!isNaN(countVal) && countVal > 0) {
                                        $count.text(countVal - 1);
                                    }
                                }
                            } else {
                                alert((response && response.message) ? response.message : 'Failed to delete attachment');
                                $btn.prop('disabled', false);
                            }
                        },
                        error: function() {
                            alert('Error deleting attachment. Please try again.');
                            $btn.prop('disabled', false);
                        }
                    });
                });

                $(document).on('click', '.delete-compliance-pdf-btn', function() {
                    var $btn = $(this);
                    var documentId = parseInt($btn.data('document-id'), 10);

                    if (!documentId) {
                        return;
                    }

                    if (!confirm('Delete this compliance document?')) {
                        return;
                    }

                    $btn.prop('disabled', true);

                    $.ajax({
                        url: '',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'delete_compliance_pdf',
                            document_id: documentId
                        },
                        success: function(response) {
                            if (response && response.success) {
                                location.reload();
                            } else {
                                alert((response && response.message) ? response.message : 'Failed to delete compliance document');
                                $btn.prop('disabled', false);
                            }
                        },
                        error: function() {
                            alert('Error deleting compliance document. Please try again.');
                            $btn.prop('disabled', false);
                        }
                    });
                });

            });
        </script>

<div class="modal fade" id="attachmentPreviewModal" tabindex="-1" role="dialog" aria-labelledby="attachmentPreviewTitle">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: #17a2b8; color: white;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true" style="color: white;">&times;</span></button>
                <h4 class="modal-title" id="attachmentPreviewTitle">Attachment Preview</h4>
            </div>
            <div class="modal-body text-center" style="padding: 15px;">
                <img id="attachmentPreviewImage" src="" alt="Attachment preview" style="max-width: 100%; max-height: 75vh;" class="img-responsive center-block">
            </div>
        </div>
    </div>
</div>

<!-- Shipping Address Availability Modals -->
<div class="modal fade" id="addShippingAvailabilityModal" tabindex="-1" role="dialog" aria-labelledby="addShippingAvailabilityModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true" style="color: white;">&times;</span></button>
                <h4 class="modal-title" id="addShippingAvailabilityModalLabel"><i class="fa fa-plus"></i> Set Delivery Availability</h4>
            </div>
            <form id="addShippingAvailabilityForm">
                <div class="modal-body">
                    <input type="hidden" name="shipping_address_id" id="add_shipping_address_id">
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> <strong>Delivery Availability:</strong> Set which days deliveries can be made to this shipping address. By default, all days are available.
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="panel panel-default">
                                <div class="panel-heading" style="background: #f8f9fa;">
                                    <h5 class="panel-title" style="margin: 0;"><i class="fa fa-calendar"></i> Select Available Delivery Days</h5>
                                </div>
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="checkbox-inline" style="width: 100%; margin-bottom: 10px; padding: 10px; border: 1px solid #e7ecf1; border-radius: 4px; background: #f8f9fa;">
                                                    <input type="checkbox" name="mon" value="1" checked style="margin-right: 10px;">
                                                    <strong>Monday</strong>
                                                    <span class="pull-right"><i class="fa fa-check-circle text-success"></i></span>
                                                </label>
                                            </div>
                                            <div class="form-group">
                                                <label class="checkbox-inline" style="width: 100%; margin-bottom: 10px; padding: 10px; border: 1px solid #e7ecf1; border-radius: 4px; background: #f8f9fa;">
                                                    <input type="checkbox" name="tue" value="1" checked style="margin-right: 10px;">
                                                    <strong>Tuesday</strong>
                                                    <span class="pull-right"><i class="fa fa-check-circle text-success"></i></span>
                                                </label>
                                            </div>
                                            <div class="form-group">
                                                <label class="checkbox-inline" style="width: 100%; margin-bottom: 10px; padding: 10px; border: 1px solid #e7ecf1; border-radius: 4px; background: #f8f9fa;">
                                                    <input type="checkbox" name="wed" value="1" checked style="margin-right: 10px;">
                                                    <strong>Wednesday</strong>
                                                    <span class="pull-right"><i class="fa fa-check-circle text-success"></i></span>
                                                </label>
                                            </div>
                                            <div class="form-group">
                                                <label class="checkbox-inline" style="width: 100%; margin-bottom: 10px; padding: 10px; border: 1px solid #e7ecf1; border-radius: 4px; background: #f8f9fa;">
                                                    <input type="checkbox" name="thu" value="1" checked style="margin-right: 10px;">
                                                    <strong>Thursday</strong>
                                                    <span class="pull-right"><i class="fa fa-check-circle text-success"></i></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="checkbox-inline" style="width: 100%; margin-bottom: 10px; padding: 10px; border: 1px solid #e7ecf1; border-radius: 4px; background: #f8f9fa;">
                                                    <input type="checkbox" name="fri" value="1" checked style="margin-right: 10px;">
                                                    <strong>Friday</strong>
                                                    <span class="pull-right"><i class="fa fa-check-circle text-success"></i></span>
                                                </label>
                                            </div>
                                            <div class="form-group">
                                                <label class="checkbox-inline" style="width: 100%; margin-bottom: 10px; padding: 10px; border: 1px solid #e7ecf1; border-radius: 4px; background: #f8f9fa;">
                                                    <input type="checkbox" name="sat" value="1" checked style="margin-right: 10px;">
                                                    <strong>Saturday</strong>
                                                    <span class="pull-right"><i class="fa fa-check-circle text-success"></i></span>
                                                </label>
                                            </div>
                                            <div class="form-group">
                                                <label class="checkbox-inline" style="width: 100%; margin-bottom: 10px; padding: 10px; border: 1px solid #e7ecf1; border-radius: 4px; background: #f8f9fa;">
                                                    <input type="checkbox" name="sun" value="1" checked style="margin-right: 10px;">
                                                    <strong>Sunday</strong>
                                                    <span class="pull-right"><i class="fa fa-check-circle text-success"></i></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Availability</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Shipping Address Availability Modal -->
<div class="modal fade" id="editShippingAvailabilityModal" tabindex="-1" role="dialog" aria-labelledby="editShippingAvailabilityModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%); color: white;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true" style="color: white;">&times;</span></button>
                <h4 class="modal-title" id="editShippingAvailabilityModalLabel"><i class="fa fa-edit"></i> Edit Delivery Availability</h4>
            </div>
            <form id="editShippingAvailabilityForm">
                <div class="modal-body">
                    <input type="hidden" name="shipping_address_id" id="edit_shipping_address_id">
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> <strong>Delivery Availability:</strong> Modify which days deliveries can be made to this shipping address.
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="panel panel-default">
                                <div class="panel-heading" style="background: #f8f9fa;">
                                    <h5 class="panel-title" style="margin: 0;"><i class="fa fa-calendar"></i> Select Available Delivery Days</h5>
                                </div>
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="checkbox-inline" style="width: 100%; margin-bottom: 10px; padding: 10px; border: 1px solid #e7ecf1; border-radius: 4px; background: #f8f9fa;">
                                                    <input type="checkbox" name="mon" value="1" id="edit_mon" style="margin-right: 10px;">
                                                    <strong>Monday</strong>
                                                    <span class="pull-right"><i class="fa fa-check-circle text-success" id="edit_mon_icon"></i></span>
                                                </label>
                                            </div>
                                            <div class="form-group">
                                                <label class="checkbox-inline" style="width: 100%; margin-bottom: 10px; padding: 10px; border: 1px solid #e7ecf1; border-radius: 4px; background: #f8f9fa;">
                                                    <input type="checkbox" name="tue" value="1" id="edit_tue" style="margin-right: 10px;">
                                                    <strong>Tuesday</strong>
                                                    <span class="pull-right"><i class="fa fa-check-circle text-success" id="edit_tue_icon"></i></span>
                                                </label>
                                            </div>
                                            <div class="form-group">
                                                <label class="checkbox-inline" style="width: 100%; margin-bottom: 10px; padding: 10px; border: 1px solid #e7ecf1; border-radius: 4px; background: #f8f9fa;">
                                                    <input type="checkbox" name="wed" value="1" id="edit_wed" style="margin-right: 10px;">
                                                    <strong>Wednesday</strong>
                                                    <span class="pull-right"><i class="fa fa-check-circle text-success" id="edit_wed_icon"></i></span>
                                                </label>
                                            </div>
                                            <div class="form-group">
                                                <label class="checkbox-inline" style="width: 100%; margin-bottom: 10px; padding: 10px; border: 1px solid #e7ecf1; border-radius: 4px; background: #f8f9fa;">
                                                    <input type="checkbox" name="thu" value="1" id="edit_thu" style="margin-right: 10px;">
                                                    <strong>Thursday</strong>
                                                    <span class="pull-right"><i class="fa fa-check-circle text-success" id="edit_thu_icon"></i></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="checkbox-inline" style="width: 100%; margin-bottom: 10px; padding: 10px; border: 1px solid #e7ecf1; border-radius: 4px; background: #f8f9fa;">
                                                    <input type="checkbox" name="fri" value="1" id="edit_fri" style="margin-right: 10px;">
                                                    <strong>Friday</strong>
                                                    <span class="pull-right"><i class="fa fa-check-circle text-success" id="edit_fri_icon"></i></span>
                                                </label>
                                            </div>
                                            <div class="form-group">
                                                <label class="checkbox-inline" style="width: 100%; margin-bottom: 10px; padding: 10px; border: 1px solid #e7ecf1; border-radius: 4px; background: #f8f9fa;">
                                                    <input type="checkbox" name="sat" value="1" id="edit_sat" style="margin-right: 10px;">
                                                    <strong>Saturday</strong>
                                                    <span class="pull-right"><i class="fa fa-check-circle text-success" id="edit_sat_icon"></i></span>
                                                </label>
                                            </div>
                                            <div class="form-group">
                                                <label class="checkbox-inline" style="width: 100%; margin-bottom: 10px; padding: 10px; border: 1px solid #e7ecf1; border-radius: 4px; background: #f8f9fa;">
                                                    <input type="checkbox" name="sun" value="1" id="edit_sun" style="margin-right: 10px;">
                                                    <strong>Sunday</strong>
                                                    <span class="pull-right"><i class="fa fa-check-circle text-success" id="edit_sun_icon"></i></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Update Availability</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>

</html>



