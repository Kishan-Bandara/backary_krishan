<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');

date_default_timezone_set("Australia/Melbourne");

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function formatDateValue($value, $format = 'd/m/Y')
{
    $timestamp = strtotime((string) $value);
    if ($timestamp === false) {
        return '';
    }

    return date($format, $timestamp);
}

function getPackingSlipAutoloadPath()
{
    $paths = [
        __DIR__ . '/vendor/autoload.php',
        __DIR__ . '/DB Migration/vendor/autoload.php',
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }

    return null;
}

function getPackingSlipConfig(Database $db)
{
    $currencyRow = $db->getRow('SELECT * FROM currency WHERE activated = ? LIMIT 1', ['Y']);
    $settings = $db->getRow('SELECT * FROM general_settings WHERE id = 1');

    return [
        'currency_symbol' => $currencyRow['currency'] ?? '$',
        'company_name' => $settings['SiteName'] ?? 'GF Precinct',
        'company_logo' => $settings['logo'] ?? 'assets/layouts/layout/img/logo.avif',
        'company_address' => $settings['address'] ?? '',
        'company_email' => $settings['system_email'] ?? '',
        'company_phone' => $settings['contactUs'] ?? '',
        'packing_slip_settings' => [
            'deadline_1_label' => 'GFP Order Deadline is 12:30',
            'deadline_2_label' => 'Strada Order Deadline is 16:00',
            'notice_1' => 'Please note: All direct deliveries are made out of hours only. GF Precinct is not responsible for any issues related to out of hours deliveries.',
            'notice_2' => 'If you are subject to extraneous circumstances which may prevent this, please talk to a sales representative to discuss alternative delivery options.',
        ],
    ];
}

function resolvePackingSlipLogoSrc($companyLogo, $forPdf = false)
{
    $companyLogo = trim((string) $companyLogo);
    if ($companyLogo === '') {
        return '';
    }

    if (preg_match('/^(https?:)?\/\//i', $companyLogo) || strpos($companyLogo, 'data:') === 0) {
        return $companyLogo;
    }

    if (!$forPdf) {
        return $companyLogo;
    }

    $candidatePath = realpath(__DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($companyLogo, '/\\')));
    if ($candidatePath && file_exists($candidatePath)) {
        return 'file:///' . str_replace(DIRECTORY_SEPARATOR, '/', $candidatePath);
    }

    return $companyLogo;
}

function getPackingSlipStyles()
{
    return <<<CSS
        body {
            background: #f3f6f9;
        }

        .packing-slip {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            font-family: Arial, sans-serif;
            font-size: 12px;
            background: #fff;
        }

        .packing-slip-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }

        .packing-slip-header h1 {
            font-size: 28px;
            margin: 0;
            font-weight: normal;
        }

        .packing-slip-header .logo {
            text-align: right;
        }

        .packing-slip-header .logo img {
            max-height: 60px;
            max-width: 220px;
        }

        .customer-section {
            margin-bottom: 20px;
        }

        .customer-section h2 {
            font-size: 16px;
            font-weight: bold;
            margin: 0 0 10px 0;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 15px;
        }

        .info-col {
            flex: 1;
        }

        .info-col.right {
            text-align: right;
        }

        .info-label {
            font-weight: bold;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #333;
            padding: 6px 8px;
            text-align: left;
            font-size: 11px;
        }

        .items-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }

        .items-table .text-right {
            text-align: right;
        }

        .items-table .text-center {
            text-align: center;
        }

        .items-table tbody tr:nth-child(even) {
            background-color: #fafafa;
        }

        .totals-section {
            width: 250px;
            margin-left: auto;
            margin-bottom: 30px;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            border-bottom: 1px solid #ddd;
        }

        .totals-row.total {
            font-weight: bold;
        }

        .signature-section {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }

        .signature-box {
            flex: 1;
        }

        .signature-box label {
            font-weight: bold;
            display: block;
            margin-bottom: 30px;
        }

        .signature-line {
            border-bottom: 1px solid #333;
            height: 30px;
        }

        .footer-notes {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 11px;
        }

        .footer-notes .deadline {
            color: #c00;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .footer-notes .notice {
            color: #666;
            font-size: 10px;
            margin-top: 10px;
        }

        .bulk-portlet .portlet-body {
            padding-top: 10px;
        }

        .bulk-form {
            display: flex;
            align-items: flex-end;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }

        .bulk-form .form-group {
            margin-bottom: 0;
        }

        .bulk-summary {
            display: inline-block;
            padding: 8px 12px;
            background: #f5f8fb;
            border-left: 4px solid #3598dc;
            margin-bottom: 18px;
            font-size: 13px;
        }

        .bulk-results-table th,
        .bulk-results-table td {
            vertical-align: middle !important;
        }

        .bulk-empty {
            padding: 24px;
            text-align: center;
            background: #fafafa;
            border: 1px dashed #c8d4e3;
            color: #6b7b8c;
        }

        .bulk-empty i {
            font-size: 28px;
            display: block;
            margin-bottom: 10px;
        }

        .page-break {
            page-break-after: always;
        }

        .no-print {
            margin-bottom: 20px;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
                background: #fff;
            }

            .no-print {
                display: none !important;
            }

            .packing-slip {
                max-width: 100%;
                padding: 10mm;
                box-shadow: none;
            }

            .page-sidebar-wrapper,
            .page-bar,
            .page-header {
                display: none !important;
            }

            .page-content {
                margin: 0 !important;
                padding: 0 !important;
            }

            .page-container,
            .page-content-wrapper {
                margin: 0 !important;
                padding: 0 !important;
            }
        }

        @page {
            size: A4 portrait;
            margin: 10mm;
        }
CSS;
}

function fetchPackingSlipData(Database $db, $invoiceId)
{
    $invoice = $db->getRow('SELECT * FROM invoice_hedder WHERE invoice_h_id = ?', [$invoiceId]);
    if (!$invoice) {
        return null;
    }

    $customer = $db->getRow('SELECT * FROM customer WHERE customer_id = ?', [$invoice['invoice_h_customer_id']]);
    $shippingAddress = null;
    if (!empty($invoice['shipping_address_id'])) {
        $shippingAddress = $db->getRow('SELECT * FROM customer_shipping_address WHERE id = ?', [$invoice['shipping_address_id']]);
    }

    $routeName = '';
    if ($shippingAddress && !empty($shippingAddress['delivery_route_id'])) {
        $route = $db->getRow('SELECT route_name FROM delivery_route_master WHERE id = ?', [$shippingAddress['delivery_route_id']]);
        $routeName = $route['route_name'] ?? '';
    }

    $items = $db->getRows(
        'SELECT id.*, im.item_name, im.item_code, im.item_group, im.item_category,
                cm.category_name, tm.type_name
         FROM invoice_details id
         JOIN item_master im ON im.item_id = id.invoice_d_item_id
         LEFT JOIN category_master cm ON cm.category_id = im.item_category
         LEFT JOIN type_master tm ON tm.type_id = im.item_type
         WHERE id.invoice_h_id = ?
         ORDER BY tm.type_name ASC, cm.category_name ASC, im.item_name ASC',
        [$invoiceId]
    ) ?: [];

    $subtotal = 0.0;
    $totalQty = 0.0;
    foreach ($items as $item) {
        $subtotal += (float) ($item['invoice_d_item_total'] ?? 0);
        $totalQty += (float) ($item['invoice_d_qty'] ?? 0);
    }

    $invoiceDateRaw = $invoice['invoice_h_date'] ?? date('Y-m-d');
    $deliveryDateRaw = $invoice['invoice_h_delivery_date'] ?? '';
    if ($deliveryDateRaw === '' || substr((string) $deliveryDateRaw, 0, 10) === '0000-00-00') {
        $deliveryDateRaw = $invoiceDateRaw;
    }

    return [
        'invoice_id' => (int) $invoiceId,
        'invoice_code' => $invoice['invoice_h_code'] ?? '',
        'invoice_date_display' => formatDateValue($invoiceDateRaw),
        'delivery_date_display' => formatDateValue($deliveryDateRaw),
        'delivery_date_query' => formatDateValue($deliveryDateRaw, 'Y-m-d') ?: date('Y-m-d'),
        'po_number' => trim((string) ($invoice['invoice_h_purchase_order_no'] ?? '')) !== ''
            ? $invoice['invoice_h_purchase_order_no']
            : ($invoice['invoice_h_check_Ref'] ?? 'N/A'),
        'customer_name' => $customer['customer_name'] ?? 'Unknown Customer',
        'customer_address_line_1' => $customer['address_line_1'] ?? '',
        'customer_address_line_2' => $customer['address_line_2'] ?? '',
        'customer_city' => $customer['city'] ?? '',
        'customer_postal' => $customer['postal_code'] ?? '',
        'customer_phone' => $customer['customer_tell'] ?? '',
        'customer_mobile' => $customer['customer_mobile'] ?? '',
        'delivery_address' => $invoice['invoice_h_delivery_address'] ?? '',
        'delivery_contact' => $invoice['invoice_h_delivery_contact_no'] ?? ($customer['customer_tell'] ?? ''),
        'delivery_name' => $invoice['invoice_h_delivery_name'] ?? ($customer['customer_name'] ?? ''),
        'delivery_note_number' => ($invoice['invoice_h_code'] ?? '') . '-0',
        'route_name' => $routeName,
        'items' => $items,
        'total_qty' => $totalQty,
        'subtotal' => $subtotal,
        'delivery_cost' => (float) ($invoice['invoice_h_delivery_cost'] ?? 0),
        'vat_value' => (float) ($invoice['invoice_h_vat_value'] ?? 0),
        'gross_value' => (float) ($invoice['invoice_h_gross_value'] ?? $subtotal),
    ];
}

function fetchPackingSlipInvoicesByDate(Database $db, $selectedDate)
{
    return $db->getRows(
        'SELECT ih.invoice_h_id, ih.invoice_h_code, c.customer_name,
                CASE
                    WHEN ih.invoice_h_delivery_date IS NOT NULL
                         AND ih.invoice_h_delivery_date != ""
                         AND DATE(ih.invoice_h_delivery_date) != "0000-00-00"
                    THEN DATE(ih.invoice_h_delivery_date)
                    ELSE DATE(ih.invoice_h_date)
                END AS slip_date
         FROM invoice_hedder ih
         LEFT JOIN customer c ON c.customer_id = ih.invoice_h_customer_id
         WHERE CASE
                    WHEN ih.invoice_h_delivery_date IS NOT NULL
                         AND ih.invoice_h_delivery_date != ""
                         AND DATE(ih.invoice_h_delivery_date) != "0000-00-00"
                    THEN DATE(ih.invoice_h_delivery_date)
                    ELSE DATE(ih.invoice_h_date)
               END = ?
         ORDER BY slip_date ASC, c.customer_name ASC, ih.invoice_h_id ASC',
        [$selectedDate]
    ) ?: [];
}

function renderPackingSlipBody(array $slipData, array $config, $forPdf = false)
{
    $companyLogoSrc = resolvePackingSlipLogoSrc($config['company_logo'], $forPdf);
    $companyName = $config['company_name'];
    $companyPhone = $config['company_phone'];
    $companyEmail = $config['company_email'];
    $packingSlipSettings = $config['packing_slip_settings'];
    $currencySymbol = $config['currency_symbol'];

    ob_start();
    ?>
    <div class="packing-slip">
        <div class="packing-slip-header">
            <div>
                <h1>Packing Slip</h1>
            </div>
            <div class="logo">
                <?php if ($companyLogoSrc !== ''): ?>
                    <img src="<?php echo h($companyLogoSrc); ?>" alt="<?php echo h($companyName); ?>">
                <?php endif; ?>
            </div>
        </div>

        <div class="customer-section">
            <h2><?php echo h($slipData['customer_name']); ?></h2>
        </div>

        <div class="info-row">
            <div class="info-col">
                <div><span class="info-label">Bill to/Ship to</span></div>
                <div><?php echo h($slipData['delivery_name'] ?: $slipData['customer_name']); ?></div>
                <?php if (!empty($slipData['delivery_address'])): ?>
                    <div><?php echo nl2br(h($slipData['delivery_address'])); ?></div>
                <?php else: ?>
                    <?php if (!empty($slipData['customer_address_line_1'])): ?><div><?php echo h($slipData['customer_address_line_1']); ?></div><?php endif; ?>
                    <?php if (!empty($slipData['customer_address_line_2'])): ?><div><?php echo h($slipData['customer_address_line_2']); ?></div><?php endif; ?>
                    <?php if (!empty($slipData['customer_city']) || !empty($slipData['customer_postal'])): ?>
                        <div><?php echo h(trim($slipData['customer_city'] . ' ' . $slipData['customer_postal'])); ?></div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="info-col">
                <div><span class="info-label">Contact phone</span></div>
                <div><?php echo h($slipData['delivery_contact'] ?: $slipData['customer_mobile'] ?: $slipData['customer_phone'] ?: '-'); ?></div>
            </div>
            <div class="info-col right">
                <div><span class="info-label">Delivery note number:</span> <?php echo h($slipData['delivery_note_number']); ?></div>
                <div><span class="info-label">Date:</span> <?php echo h($slipData['delivery_date_display']); ?></div>
                <div><span class="info-label">Purchase order# :</span> <?php echo h($slipData['po_number']); ?></div>
                <div><span class="info-label">Route:</span> <?php echo h($slipData['route_name'] ?: 'N/A'); ?></div>
            </div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Product Type</th>
                    <th class="text-center">Sent</th>
                    <th>Item</th>
                    <th class="text-center">Ord.</th>
                    <th class="text-right">Price</th>
                    <th class="text-right">Total</th>
                    <th class="text-center">Rec'd</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($slipData['items'])): ?>
                    <tr>
                        <td colspan="7" class="text-center">No items found for this packing slip.</td>
                    </tr>
                <?php else: ?>
                    <?php $currentGroup = ''; ?>
                    <?php foreach ($slipData['items'] as $item): ?>
                        <?php
                        $group = $item['type_name'] ?? 'Uncategorized';
                        $showGroup = ($group !== $currentGroup);
                        $currentGroup = $group;
                        $itemTotal = (float) ($item['invoice_d_item_total'] ?? 0);
                        $itemQty = (float) ($item['invoice_d_qty'] ?? 0);
                        $itemPrice = (float) ($item['invoice_d_item_price'] ?? 0);
                        ?>
                        <tr>
                            <td><?php echo $showGroup ? h($group) : ''; ?></td>
                            <td class="text-center"><?php echo (int) $itemQty; ?></td>
                            <td><?php echo h($item['item_name'] ?? ''); ?></td>
                            <td class="text-center"><?php echo (int) $itemQty; ?></td>
                            <td class="text-right"><?php echo h($currencySymbol) . ' ' . number_format($itemPrice, 2); ?></td>
                            <td class="text-right"><?php echo h($currencySymbol) . ' ' . number_format($itemTotal, 2); ?></td>
                            <td class="text-center"></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr style="font-weight: bold; background-color: #f0f0f0;">
                    <td></td>
                    <td class="text-center"><?php echo (int) $slipData['total_qty']; ?></td>
                    <td colspan="1"></td>
                    <td class="text-center"><?php echo (int) $slipData['total_qty']; ?></td>
                    <td></td>
                    <td class="text-right"><?php echo h($currencySymbol) . ' ' . number_format($slipData['subtotal'], 2); ?></td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <div class="totals-section">
            <?php if ($slipData['delivery_cost'] > 0): ?>
                <div class="totals-row">
                    <span>Delivery</span>
                    <span><?php echo h($currencySymbol) . ' ' . number_format($slipData['delivery_cost'], 2); ?></span>
                </div>
            <?php endif; ?>
            <div class="totals-row">
                <span>GST</span>
                <span><?php echo h($currencySymbol) . ' ' . number_format($slipData['vat_value'], 2); ?></span>
            </div>
            <div class="totals-row total">
                <span>Total</span>
                <span><?php echo h($currencySymbol) . ' ' . number_format($slipData['gross_value'], 2); ?></span>
            </div>
        </div>

        <div class="signature-section">
            <div class="signature-box">
                <label>Received by:</label>
                <div class="signature-line"></div>
            </div>
            <div class="signature-box">
                <label>Sign:</label>
                <div class="signature-line"></div>
            </div>
            <div class="signature-box">
                <label>Time</label>
                <div class="signature-line"></div>
            </div>
        </div>

        <div class="footer-notes">
            <div class="deadline"><?php echo h($packingSlipSettings['deadline_1_label']); ?></div>
            <div class="deadline"><?php echo h($packingSlipSettings['deadline_2_label']); ?></div>
            <div class="notice"><?php echo h($packingSlipSettings['notice_1']); ?></div>
            <div class="notice" style="margin-top: 10px;"><?php echo h($packingSlipSettings['notice_2']); ?></div>
            <?php if ($companyPhone): ?>
                <div style="margin-top: 10px; font-weight: bold;">
                    Contact: <?php echo h($companyPhone); ?>
                    <?php if ($companyEmail): ?> | <?php echo h($companyEmail); ?><?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

$db = new Database();
$config = getPackingSlipConfig($db);
$message = trim((string) ($_GET['message'] ?? ''));
$type = trim((string) ($_GET['type'] ?? 'success'));
$invoiceId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$selectedDate = trim((string) ($_GET['selected_date'] ?? date('Y-m-d')));

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
    $selectedDate = date('Y-m-d');
    if ($message === '') {
        $message = 'Invalid date selected. Showing today instead.';
        $type = 'error';
    }
}

if (isset($_GET['download']) && $_GET['download'] === '1') {
    $matchingInvoices = fetchPackingSlipInvoicesByDate($db, $selectedDate);
    if (empty($matchingInvoices)) {
        header('Location: packing-slip.php?selected_date=' . urlencode($selectedDate) . '&message=' . urlencode('No packing slips found for the selected date.') . '&type=error');
        exit;
    }

    $autoloadPath = getPackingSlipAutoloadPath();
    if ($autoloadPath === null) {
        header('Location: packing-slip.php?selected_date=' . urlencode($selectedDate) . '&message=' . urlencode('PDF library not found. Please run composer install in admin.') . '&type=error');
        exit;
    }

    require_once($autoloadPath);

    $slips = [];
    foreach ($matchingInvoices as $matchingInvoice) {
        $slip = fetchPackingSlipData($db, (int) $matchingInvoice['invoice_h_id']);
        if ($slip) {
            $slips[] = $slip;
        }
    }

    if (empty($slips)) {
        header('Location: packing-slip.php?selected_date=' . urlencode($selectedDate) . '&message=' . urlencode('No printable packing slips found for the selected date.') . '&type=error');
        exit;
    }

    $pdfStyles = getPackingSlipStyles() . '\nbody { background: #fff; }\n.packing-slip { max-width: none; padding: 0; }';
    $pdfHtml = '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><style>' . $pdfStyles . '</style></head><body>';
    foreach ($slips as $index => $slip) {
        $pdfHtml .= renderPackingSlipBody($slip, $config, true);
        if ($index < count($slips) - 1) {
            $pdfHtml .= '<div class="page-break"></div>';
        }
    }
    $pdfHtml .= '</body></html>';

    $options = new \Dompdf\Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('defaultFont', 'Helvetica');

    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf->loadHtml($pdfHtml);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $dompdf->stream('packing-slips-' . $selectedDate . '.pdf', ['Attachment' => true]);
    exit;
}

$slipData = null;
$bulkInvoices = [];
if ($invoiceId > 0) {
    $slipData = fetchPackingSlipData($db, $invoiceId);
    if (!$slipData) {
        header('Location: manage-orders.php');
        exit;
    }
} else {
    $bulkInvoices = fetchPackingSlipInvoicesByDate($db, $selectedDate);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title><?php echo $slipData ? 'Packing Slip - ' . h($slipData['invoice_code']) : 'Packing Slip Bulk Download'; ?></title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
    <style>
        <?php echo getPackingSlipStyles(); ?>
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
                <div class="page-bar no-print">
                    <ul class="page-breadcrumb">
                        <li>
                            <a href="index.php">Home</a>
                            <i class="fa fa-circle"></i>
                        </li>
                        <li>
                            <a href="#">Reports</a>
                            <i class="fa fa-circle"></i>
                        </li>
                        <li>
                            <span><?php echo $slipData ? 'Packing Slip' : 'Packing Slip Bulk Download'; ?></span>
                        </li>
                    </ul>
                </div>

                <?php if ($message !== ''): ?>
                    <div class="alert <?php echo $type === 'error' ? 'alert-danger' : 'alert-success'; ?> alert-dismissable no-print">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                        <i class="fa <?php echo $type === 'error' ? 'fa-warning' : 'fa-check'; ?>"></i>
                        <?php echo h($message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($slipData): ?>
                    <div class="row no-print">
                        <div class="col-md-12">
                            <button type="button" class="btn btn-primary" onclick="window.print();">
                                <i class="fa fa-print"></i> Print Packing Slip
                            </button>
                            <a href="packing-slip.php?selected_date=<?php echo urlencode($slipData['delivery_date_query']); ?>" class="btn btn-success">
                                <i class="fa fa-calendar"></i> Selected Date Packing Slips
                            </a>
                            <a href="packing-slip.php?selected_date=<?php echo urlencode($slipData['delivery_date_query']); ?>&download=1" class="btn btn-info">
                                <i class="fa fa-download"></i> Download This Date PDF
                            </a>
                            <a href="manage-orders.php" class="btn btn-default">
                                <i class="fa fa-arrow-left"></i> Back to Orders
                            </a>
                        </div>
                    </div>

                    <?php echo renderPackingSlipBody($slipData, $config, false); ?>
                <?php else: ?>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="portlet light bordered bulk-portlet">
                                <div class="portlet-title">
                                    <div class="caption">
                                        <i class="fa fa-file-pdf-o font-blue"></i>
                                        <span class="caption-subject font-blue sbold uppercase">Packing Slips By Date</span>
                                    </div>
                                </div>
                                <div class="portlet-body">
                                    <form method="get" class="bulk-form">
                                        <div class="form-group">
                                            <label class="control-label bold">Select Date</label>
                                            <input type="date" name="selected_date" class="form-control" value="<?php echo h($selectedDate); ?>" required>
                                        </div>
                                        <button type="submit" class="btn btn-default">
                                            <i class="fa fa-search"></i> Load Packing Slips
                                        </button>
                                        <?php if (!empty($bulkInvoices)): ?>
                                            <a href="packing-slip.php?selected_date=<?php echo urlencode($selectedDate); ?>&download=1" class="btn btn-primary">
                                                <i class="fa fa-download"></i> Download 1 PDF File
                                            </a>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-primary" disabled>
                                                <i class="fa fa-download"></i> Download 1 PDF File
                                            </button>
                                        <?php endif; ?>
                                        <a href="manage-orders.php" class="btn btn-default">
                                            <i class="fa fa-arrow-left"></i> Back to Orders
                                        </a>
                                    </form>

                                    <div class="bulk-summary">
                                        Selected date: <strong><?php echo h(formatDateValue($selectedDate)); ?></strong>
                                        | Matching packing slips: <strong><?php echo count($bulkInvoices); ?></strong>
                                    </div>

                                    <?php if (empty($bulkInvoices)): ?>
                                        <div class="bulk-empty">
                                            <i class="fa fa-inbox"></i>
                                            No packing slips found for the selected date.
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped bulk-results-table">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 18%;">Invoice</th>
                                                        <th>Customer</th>
                                                        <th style="width: 18%;">Packing Slip Date</th>
                                                        <th style="width: 18%;" class="text-center">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($bulkInvoices as $bulkInvoice): ?>
                                                        <tr>
                                                            <td><?php echo h($bulkInvoice['invoice_h_code']); ?></td>
                                                            <td><?php echo h($bulkInvoice['customer_name'] ?: 'Unknown Customer'); ?></td>
                                                            <td><?php echo h(formatDateValue($bulkInvoice['slip_date'])); ?></td>
                                                            <td class="text-center">
                                                                <a href="packing-slip.php?id=<?php echo (int) $bulkInvoice['invoice_h_id']; ?>" target="_blank" class="btn btn-xs btn-default">
                                                                    <i class="fa fa-eye"></i> View Slip
                                                                </a>
                                                            </td>
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
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include('common/footer.php'); ?>
</body>
</html>
