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
$db = null;

$supplierId = isset($_GET['supplierID']) ? (int) $_GET['supplierID'] : 0;
if ($supplierId <= 0) {
    header('location:index.php');
    exit();
}

try {
    $db = new Database();
    try {
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
        // ignore table creation errors
    }
} catch (Exception $e) {
    $message = 'Database connection error: ' . $e->getMessage();
    $MessageClass = 'alert-danger';
}

$supplier = null;
if ($db) {
    try {
        $supplier = $db->getRow('SELECT * FROM supplier WHERE supplier_id = ?', [$supplierId]);
    } catch (Exception $e) {
        $message = 'Unable to load supplier record: ' . $e->getMessage();
        $MessageClass = 'alert-danger';
    }

    if (!$supplier) {
        header('location:index.php');
        exit();
    }
}

// Prepare view data
$formData = [
    'supplier_code' => $supplier['supplier_code'] ?? '',
    'supplier_name' => $supplier['supplier_name'] ?? '',
    'supplier_email' => $supplier['supplier_email'] ?? '',
    'supplier_phone' => $supplier['supplier_contact_no'] ?? '',
    'supplier_mobile' => $supplier['supplier_mobile'] ?? '',
    'address_line_1' => $supplier['address_line_1'] ?? ($supplier['supplier_address'] ?? ''),
    'address_line_2' => $supplier['address_line_2'] ?? '',
    'city' => $supplier['city'] ?? '',
    'postal_code' => $supplier['postal_code'] ?? '',
    'credit_limit' => $supplier['credit_limit'] ?? '',
    'account_hold' => $supplier['account_hold'] ?? 0,
    'abn_no' => $supplier['abn_no'] ?? '',
    'acn_no' => $supplier['acn_no'] ?? '',
    'vat_registered' => $supplier['vat_registered'] ?? 0,
    'gst_no' => $supplier['gst_no'] ?? '',
    'payment_terms_id' => $supplier['payment_terms_id'] ?? '',
    'supplier_price_type_id' => $supplier['supplier_price_type_id'] ?? '',
    'supplier_note' => $supplier['supplier_note'] ?? '',
    'supplier_remarks' => $supplier['supplier_remarks'] ?? '',
    'is_active' => $supplier['is_active'] ?? 1,
    'locked' => $supplier['locked'] ?? 0,
    'min_order_amount' => $supplier['min_order_amount'] ?? '',
    'emergency_contact_name' => $supplier['emergency_contact_name'] ?? '',
    'emergency_contact_email' => $supplier['emergency_contact_email'] ?? '',
    'emergency_contact_telephone' => $supplier['emergency_contact_telephone'] ?? '',
    'custom_url_link' => $supplier['custom_url_link'] ?? '',
    'google_map_link' => $supplier['google_map_link'] ?? '',
    'contact_name' => $supplier['contact_name'] ?? '',
    'contact_email' => $supplier['contact_email'] ?? '',
    'contact_telephone' => $supplier['contact_telephone'] ?? '',
    'legal_name' => $supplier['legal_name'] ?? '',
    'trading_name' => $supplier['trading_name'] ?? '',
];

$supplierAdditionalEmails = [];
if ($db) {
    try {
        $rows = $db->getRows('SELECT email_address FROM supplier_email_accounts WHERE supplier_id = ? ORDER BY id ASC', [$supplierId]) ?: [];
        foreach ($rows as $row) {
            $supplierAdditionalEmails[] = $row['email_address'];
        }
    } catch (Exception $e) {
        $supplierAdditionalEmails = [];
    }
}

$supplierCertificationDocument = [
    'file_path' => '',
    'file_name' => '',
    'updated_at' => '',
];
if ($db) {
    try {
        $row = $db->getRow('SELECT file_path, file_name, updated_at, created_at FROM supplier_certification_documents WHERE supplier_id = ? LIMIT 1', [$supplierId]);
        if ($row) {
            $supplierCertificationDocument = [
                'file_path' => $row['file_path'] ?? '',
                'file_name' => $row['file_name'] ?: (!empty($row['file_path']) ? basename((string) $row['file_path']) : ''),
                'updated_at' => $row['updated_at'] ?? $row['created_at'] ?? '',
            ];
        }
    } catch (Exception $e) {
        $supplierCertificationDocument = [
            'file_path' => '',
            'file_name' => '',
            'updated_at' => '',
        ];
    }
}

// Fetch shipping addresses
$shippingAddresses = [];
if ($db) {
    try {
        $shippingAddresses = $db->getRows('SELECT * FROM supplier_shipping_address WHERE supplier_id = ? ORDER BY is_default DESC, id ASC', [$supplierId]) ?: [];
    } catch (Exception $e) {
        $shippingAddresses = [];
    }
}

// Fetch payment options
$paymentOptions = [];
if ($db) {
    try {
        $cardOptions = $db->getRows('SELECT * FROM supplier_payment_options WHERE supplier_id = ? AND payment_type = "card"', [$supplierId]) ?: [];
        $bankOptions = $db->getRows('SELECT * FROM supplier_payment_options WHERE supplier_id = ? AND payment_type = "bank"', [$supplierId]) ?: [];
        $paymentOptions = array_merge($cardOptions, $bankOptions);
    } catch (Exception $e) {
        $paymentOptions = [];
    }
}

// Fetch purchase history
$purchaseHistory = [];
$salesTotal = 0.00;
$salesCount = 0;
$lastPurchaseDate = null;
if ($db) {
    try {
        $purchaseHistory = $db->getRows('SELECT * FROM purchase_invoice WHERE supplier_id = ? ORDER BY invoice_date DESC LIMIT 10', [$supplierId]) ?: [];
        $salesCount = count($purchaseHistory);
        foreach ($purchaseHistory as $row) {
            $salesTotal += (float)($row['total_amount'] ?? $row['net_total'] ?? 0);
            $dateStr = $row['invoice_date'] ?? $row['date'] ?? null;
            if ($dateStr) {
                $ts = strtotime((string)$dateStr);
                if ($ts !== false) {
                    if ($lastPurchaseDate === null || $ts > strtotime((string)$lastPurchaseDate)) {
                        $lastPurchaseDate = date('Y-m-d', $ts);
                    }
                }
            }
        }
    } catch (Exception $e) {
        $purchaseHistory = [];
    }
}

// Get currency symbol
ob_start();
include('currency.php');
$currencyContent = ob_get_clean();
preg_match('/\$currencySymbol\s*=\s*[\'"]([^\'"]*)[\'"]/i', $currencyContent, $matches);
$currencySymbol = $matches[1] ?? '$';

// Format badges
$isActiveBadge = $formData['is_active'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">Inactive</span>';
$accountHoldBadge = $formData['account_hold'] ? '<span class="badge badge-warning">On Hold</span>' : '<span class="badge badge-success">Active</span>';
$lockedBadge = $formData['locked'] ? '<span class="badge badge-danger">Locked</span>' : '<span class="badge badge-success">Unlocked</span>';
$creditLimitBadge = $formData['credit_limit'] !== '' ? $currencySymbol . ' ' . number_format((float)$formData['credit_limit'], 2) : '-';
$outstandingBadge = $supplier['supplier_outstanding_balance'] ?? 0.00;
$outstandingBadge = $currencySymbol . ' ' . number_format((float)$outstandingBadge, 2);
$vatBadge = $formData['vat_registered'] ? '<span class="badge badge-success">Registered</span>' : '<span class="badge badge-secondary">Not Registered</span>';

// Resolve payment terms name
$paymentTermsName = '-';
if ($db && !empty($formData['payment_terms_id'])) {
    try {
        $ptRow = $db->getRow('SELECT payment_terms_name FROM payment_terms WHERE payment_terms_id = ? LIMIT 1', [(int)$formData['payment_terms_id']]);
        if ($ptRow) {
            $paymentTermsName = h($ptRow['payment_terms_name']);
        }
    } catch (Exception $e) {
        // ignore
    }
}

// Resolve supplier price type
$priceTypeLabel = '-';
if ($db && !empty($formData['supplier_price_type_id'])) {
    try {
        $ptRow = $db->getRow('SELECT description FROM price_type WHERE id = ? LIMIT 1', [(int)$formData['supplier_price_type_id']]);
        if ($ptRow) {
            $priceTypeLabel = h($ptRow['description']);
        }
    } catch (Exception $e) {
        // ignore
    }
}

$salesTotalDisplay = $currencySymbol . ' ' . number_format($salesTotal, 2);

?>
<!DOCTYPE html>
<!--[if IE 8]> <html lang="en" class="ie8 no-js"> <![endif]-->
<!--[if IE 9]> <html lang="en" class="ie9 no-js"> <![endif]-->
<!--[if !IE]><!-->
<html lang="en">
<!--<![endif]-->

<head>
    <meta charset="utf-8" />
    <title>Supplier View</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <meta content="" name="description" />
    <meta content="" name="author" />
    <?php include('common/head.php'); ?>
    <style>
        .section-card {
            background: #ffffff;
            border: 1px solid #dde3ec;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 6px 18px rgba(52, 73, 94, 0.08);
            margin-bottom: 24px;
        }

        .section-card h4 {
            margin: -24px -24px 18px -24px;
            padding: 12px 16px;
            font-size: 15px;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #ffffff;
            background: linear-gradient(135deg, #3f51b5 0%, #00bcd4 100%);
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
            box-shadow: inset 0 -1px 0 rgba(255,255,255,0.15);
            text-shadow: 0 1px 0 rgba(0, 0, 0, 0.15);
        }

        .badge-soft-primary {
            background: #e3f2fd;
            color: #0d47a1;
            border-color: #bbdefb;
        }

        .equal-height {
            display: flex;
            align-items: stretch;
            flex-wrap: wrap;
        }

        .equal-height > [class*='col-'] {
            display: flex;
            flex-direction: column;
        }

        .equal-height .section-card {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
        }

        .section-card.section-odd h4 {
            background: linear-gradient(135deg, #3f51b5 0%, #00bcd4 100%);
        }

        .section-card.section-even h4 {
            background: linear-gradient(135deg, #8e24aa 0%, #ff7043 100%);
        }

        .striped-body {
            border-radius: 4px;
            overflow: hidden;
            border: none;
        }

        .striped-body .info-row {
            padding: 6px 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            color: #3e4b63;
        }

        .striped-body .info-row:nth-child(odd) {
            background: #f5f5f5;
        }

        .striped-body .info-row:nth-child(even) {
            background: #f7f7ff;
        }

        .striped-body .info-row + .info-row {
            border-top: none;
        }

        .section-card > .info-row {
            padding: 6px 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            color: #3e4b63;
        }

        .section-card > .info-row:nth-child(odd) {
            background: #ffffff;
        }

        .section-card > .info-row:nth-child(even) {
            background: #fbfbfc;
        }

        .shipping-view-list li {
            padding: 8px 10px;
            margin-bottom: 8px;
            background: #ffffff;
            border: 1px solid #f3f6fa;
        }

        .shipping-view-list li:nth-child(even) {
            background: #fbfbfc;
        }
    </style>
    <script>
        function goBackFallback() {
            if (document.referrer) {
                window.history.back();
            } else {
                window.location.href = 'manage-supplier.php';
            }
        }
    </script>
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
                            <a href="manage-supplier.php">Supplier Management</a>
                            <i class="fa fa-circle"></i>
                        </li>
                        <li>
                            <span>Supplier View</span>
                        </li>
                    </ul>
                    <div class="page-toolbar">
                        <a href="javascript:goBackFallback()" class="btn btn-default">
                            <i class="fa fa-arrow-left"></i> Back to List
                        </a>
                        <a href="edit-supplier.php?supplierID=<?php echo $supplierId; ?>" class="btn btn-primary">
                            <i class="fa fa-pencil"></i> Edit Supplier
                        </a>
                    </div>
                </div>

                <?php if ($message): ?>
                <div class="alert <?php echo $MessageClass; ?> alert-dismissable">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                    <?php echo h($message); ?>
                </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-12">
                        <div class="portlet light bordered">
                            <div class="portlet-title">
                                <div class="caption font-green">
                                    <i class="icon-eye font-green"></i>
                                    <span class="caption-subject bold uppercase">Supplier Details: <?php echo h($formData['supplier_name']); ?></span>
                                </div>
                            </div>
                            <div class="portlet-body">
                                <div class="tabbable-custom">
                                    <ul class="nav nav-tabs">
                                        <li class="active">
                                            <a href="#tab_profile" data-toggle="tab" aria-expanded="true">Supplier Profile</a>
                                        </li>
                                        <li>
                                            <a href="#tab_purchases" data-toggle="tab" aria-expanded="false">Purchase History</a>
                                        </li>
                                    </ul>
                                    <div class="tab-content">
                                        <div class="tab-pane active" id="tab_profile">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="section-card">
                                                        <h4>Supplier Overview</h4>
                                                        <div class="info-row"><span>Name</span><span><?php echo h($formData['supplier_name']); ?></span></div>
                                                        <?php if ($formData['legal_name'] !== ''): ?>
                                                            <div class="info-row"><span>Legal Name</span><span><?php echo h($formData['legal_name']); ?></span></div>
                                                        <?php endif; ?>
                                                        <?php if ($formData['trading_name'] !== ''): ?>
                                                            <div class="info-row"><span>Trading Name</span><span><?php echo h($formData['trading_name']); ?></span></div>
                                                        <?php endif; ?>
                                                        <?php if ($formData['supplier_remarks'] !== ''): ?>
                                                            <div class="info-row"><span>Remarks</span><span><?php echo h($formData['supplier_remarks']); ?></span></div>
                                                        <?php endif; ?>
                                                        <div class="info-row"><span>Code</span><span><?php echo h($formData['supplier_code']); ?></span></div>
                                                        <div class="info-row"><span>Status</span><span><?php echo $isActiveBadge; ?></span></div>
                                                        <div class="info-row"><span>Account</span><span><?php echo $accountHoldBadge; ?></span></div>
                                                        <div class="info-row"><span>Lock</span><span><?php echo $lockedBadge; ?></span></div>
                                                        <div class="info-row"><span>Credit Limit</span><span><?php echo $creditLimitBadge; ?></span></div>
                                                        <div class="info-row"><span>Outstanding</span><span><?php echo $outstandingBadge; ?></span></div>
                                                        <div class="info-row"><span>GST Status</span><span><?php echo $vatBadge; ?></span></div>
                                                        <?php if ($formData['abn_no'] !== ''): ?>
                                                            <div class="info-row"><span>ABN</span><span><?php echo h($formData['abn_no']); ?></span></div>
                                                        <?php endif; ?>
                                                        <?php if ($formData['acn_no'] !== ''): ?>
                                                            <div class="info-row"><span>ACN</span><span><?php echo h($formData['acn_no']); ?></span></div>
                                                        <?php endif; ?>
                                                        <?php if ($formData['gst_no'] !== ''): ?>
                                                            <div class="info-row"><span>GST No</span><span><?php echo h($formData['gst_no']); ?></span></div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($supplierCertificationDocument['file_path'])): ?>
                                                            <div class="info-row">
                                                                <span>Certification PDF</span>
                                                                <span>
                                                                    <a href="../<?php echo h($supplierCertificationDocument['file_path']); ?>" download="<?php echo h($supplierCertificationDocument['file_name']); ?>" target="_blank" rel="noopener">Download</a>
                                                                </span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>

                                                    <div class="section-card">
                                                        <h4>Purchase Summary</h4>
                                                        <div class="info-row"><span>Total Invoices</span><span><?php echo (int)$salesCount; ?></span></div>
                                                        <div class="info-row"><span>Total Purchased</span><span><?php echo h($salesTotalDisplay); ?></span></div>
                                                        <div class="info-row"><span>Last Purchase</span><span><?php echo h($lastPurchaseDate ?: '-'); ?></span></div>
                                                    </div>

                                                    <div class="section-card">
                                                        <h4>Contact</h4>
                                                        <div class="info-row"><span>Email</span><span><?php echo h($formData['supplier_email']); ?></span></div>
                                                        <?php if (!empty($supplierAdditionalEmails)): ?>
                                                            <div class="info-row"><span>Additional Emails</span><span><?php echo h(implode(', ', $supplierAdditionalEmails)); ?></span></div>
                                                        <?php endif; ?>
                                                        <div class="info-row"><span>Phone</span><span><?php echo h($formData['supplier_phone']); ?></span></div>
                                                        <div class="info-row"><span>Mobile</span><span><?php echo h($formData['supplier_mobile']); ?></span></div>
                                                        <?php if ($formData['contact_name'] !== ''): ?>
                                                            <div class="info-row"><span>Contact Person</span><span><?php echo h($formData['contact_name']); ?></span></div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="col-md-8">
                                                    <div class="row equal-height">
                                                        <div class="col-md-6">
                                                            <div class="section-card">
                                                                <h4>Billing Address</h4>
                                                                <p class="muted" style="margin-bottom: 6px;">
                                                                    <?php echo nl2br(h(trim($formData['address_line_1'] . "\n" . $formData['address_line_2']))); ?>
                                                                </p>
                                                                <div class="striped-body">
                                                                    <div class="info-row"><span>City</span><span><?php echo h($formData['city']); ?></span></div>
                                                                    <div class="info-row"><span>Postal Code</span><span><?php echo h($formData['postal_code']); ?></span></div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <div class="section-card">
                                                                <h4>Financial & Tax</h4>
                                                                <div class="info-row">
                                                                    <span>Payment Terms</span>
                                                                    <span><?php echo h($paymentTermsName); ?></span>
                                                                </div>
                                                                <div class="info-row">
                                                                    <span>Price Type</span>
                                                                    <span><?php echo h($priceTypeLabel); ?></span>
                                                                </div>
                                                                <?php if ($formData['min_order_amount'] !== ''): ?>
                                                                    <div class="info-row">
                                                                        <span>Min Order Amount</span>
                                                                        <span><?php echo $currencySymbol . ' ' . number_format((float)$formData['min_order_amount'], 2); ?></span>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="section-card">
                                                        <h4>Shipping Addresses</h4>
                                                        <?php if (!empty($shippingAddresses)): ?>
                                                        <ul class="shipping-view-list" style="list-style: none; padding: 0;">
                                                            <?php foreach ($shippingAddresses as $address): ?>
                                                            <li>
                                                                <strong><?php echo h($address['address_label'] ?: 'Address'); ?></strong>
                                                                <?php if ($address['is_default']): ?>
                                                                    <span class="badge badge-primary">Default</span>
                                                                <?php endif; ?>
                                                                <br>
                                                                <small class="text-muted">
                                                                    <?php echo nl2br(h($address['address_line_1'] . ($address['address_line_2'] ? "\n" . $address['address_line_2'] : '') . "\n" . $address['city'] . ", " . $address['postal_code'])); ?>
                                                                </small>
                                                                <?php if ($address['contact_person_name']): ?>
                                                                    <br><small>Contact: <?php echo h($address['contact_person_name']); ?> (<?php echo h($address['contact_person_phone']); ?>)</small>
                                                                <?php endif; ?>
                                                            </li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                        <?php else: ?>
                                                        <p class="text-muted">No shipping addresses found for this supplier.</p>
                                                        <?php endif; ?>
                                                    </div>

                                                    <?php if (!empty($paymentOptions)): ?>
                                                    <div class="section-card">
                                                        <h4>Payment Options</h4>
                                                        <div class="row">
                                                            <?php foreach ($paymentOptions as $option): ?>
                                                            <div class="col-md-6">
                                                                <div style="border: 1px solid #e7edf5; border-radius: 8px; padding: 16px; margin-bottom: 16px; background: #f9fbff;">
                                                                    <h5><?php echo $option['payment_type'] === 'card' ? 'Card Payment' : 'Bank Payment'; ?></h5>
                                                                    <?php if ($option['payment_type'] === 'card'): ?>
                                                                        <div><strong>Card:</strong> **** **** **** <?php echo substr(h($option['card_no']), -4); ?></div>
                                                                        <div><strong>Name:</strong> <?php echo h($option['card_name']); ?></div>
                                                                        <?php if ($option['exp_month'] && $option['exp_year']): ?>
                                                                            <div><strong>Expires:</strong> <?php echo str_pad($option['exp_month'], 2, '0', STR_PAD_LEFT) . '/' . $option['exp_year']; ?></div>
                                                                        <?php endif; ?>
                                                                    <?php else: ?>
                                                                        <div><strong>Bank:</strong> <?php echo h($option['bank_name']); ?></div>
                                                                        <div><strong>Branch:</strong> <?php echo h($option['branch']); ?></div>
                                                                        <div><strong>Account:</strong> ****<?php echo substr(h($option['account_no']), -4); ?></div>
                                                                        <div><strong>Holder:</strong> <?php echo h($option['account_holder']); ?></div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                    <?php endif; ?>

                                                    <?php if ($formData['supplier_note'] !== '' || $formData['emergency_contact_name'] !== ''): ?>
                                                    <div class="row equal-height">
                                                        <?php if ($formData['supplier_note'] !== ''): ?>
                                                        <div class="col-md-6">
                                                            <div class="section-card">
                                                                <h4>Notes</h4>
                                                                <p><?php echo nl2br(h($formData['supplier_note'])); ?></p>
                                                            </div>
                                                        </div>
                                                        <?php endif; ?>

                                                        <?php if ($formData['emergency_contact_name'] !== ''): ?>
                                                        <div class="col-md-6">
                                                            <div class="section-card">
                                                                <h4>Emergency Contact</h4>
                                                                <div class="info-row"><span>Name</span><span><?php echo h($formData['emergency_contact_name']); ?></span></div>
                                                                <div class="info-row"><span>Email</span><span><?php echo h($formData['emergency_contact_email']); ?></span></div>
                                                                <div class="info-row"><span>Phone</span><span><?php echo h($formData['emergency_contact_telephone']); ?></span></div>
                                                            </div>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php endif; ?>

                                                    <?php if ($formData['custom_url_link'] !== '' || $formData['google_map_link'] !== ''): ?>
                                                    <div class="section-card">
                                                        <h4>Links</h4>
                                                        <?php if ($formData['custom_url_link'] !== ''): ?>
                                                            <div class="info-row">
                                                                <span>Website</span>
                                                                <span><a href="<?php echo h($formData['custom_url_link']); ?>" target="_blank"><?php echo h($formData['custom_url_link']); ?> <i class="fa fa-external-link"></i></a></span>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if ($formData['google_map_link'] !== ''): ?>
                                                            <div class="info-row">
                                                                <span>Map Location</span>
                                                                <span><a href="<?php echo h($formData['google_map_link']); ?>" target="_blank">View on Map <i class="fa fa-external-link"></i></a></span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="tab-pane" id="tab_purchases">
                                            <div class="section-card">
                                                <h4>Purchase History</h4>
                                                <?php if (empty($purchaseHistory)): ?>
                                                    <p class="text-muted">No purchase history found.</p>
                                                <?php else: ?>
                                                    <div class="table-responsive">
                                                        <table class="table table-striped table-bordered">
                                                            <thead>
                                                                <tr>
                                                                    <th>Invoice #</th>
                                                                    <th>Date</th>
                                                                    <th>Total Amount</th>
                                                                    <th>Status</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($purchaseHistory as $invoice): ?>
                                                                <tr>
                                                                    <td><?php echo h($invoice['invoice_number'] ?? $invoice['id'] ?? '-'); ?></td>
                                                                    <td><?php echo h($invoice['invoice_date'] ?? $invoice['date'] ?? '-'); ?></td>
                                                                    <td><?php echo $currencySymbol . ' ' . number_format((float)($invoice['total_amount'] ?? $invoice['net_total'] ?? 0), 2); ?></td>
                                                                    <td><span class="badge badge-success">Completed</span></td>
                                                                </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include('common/footer.php'); ?>
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
        <script type="text/template" id="shippingAddressTemplate">
</body>
</html>



