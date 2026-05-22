<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('include/database.php');
include('include/check_login.php');

$db = new Database();

if (!function_exists('xeroExportHtml')) {
    function xeroExportHtml($value)
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('xeroInvoiceTaxType')) {
    function xeroInvoiceTaxType(array $row)
    {
        if (!empty($row['gst_vat_code'])) {
            return $row['gst_vat_code'];
        }
        if (($row['invoice_d_vat'] ?? '') === 'Y') {
            return 'GST on Income';
        }
        return 'GST Free Income';
    }
}

if (!function_exists('xeroInvoiceCurrency')) {
    function xeroInvoiceCurrency(array $row, $activeCurrency)
    {
        return (!empty($row['CustomerCurrencyId']) && $row['CustomerCurrencyId'] !== '0')
            ? $row['CustomerCurrencyId']
            : $activeCurrency;
    }
}

if (!function_exists('xeroInvoiceLineTotal')) {
    function xeroInvoiceLineTotal(array $row)
    {
        if (isset($row['invoice_d_item_total']) && $row['invoice_d_item_total'] !== null) {
            return (float)$row['invoice_d_item_total'];
        }

        return (((float)($row['invoice_d_qty'] ?? 0) * (float)($row['invoice_d_item_price'] ?? 0)) - (float)($row['invoice_d_discount_total'] ?? 0));
    }
}

// Get active currency
$currencyRow = $db->getRow('SELECT currency FROM currency WHERE activated = ? LIMIT 1', ['Y']);
$activeCurrency = $currencyRow['currency'] ?? 'AUD';

// Get parameters
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to   = isset($_GET['date_to'])   ? $_GET['date_to']   : '';
$export    = isset($_GET['export'])    && $_GET['export'] === '1';

// Validate dates
$validDates = false;
if ($date_from && $date_to) {
    $df = DateTime::createFromFormat('Y-m-d', $date_from);
    $dt = DateTime::createFromFormat('Y-m-d', $date_to);
    if ($df && $dt && $df <= $dt) {
        $validDates = true;
    }
}

// Fetch invoice data if dates are valid
$rows = [];
if ($validDates) {
    $rows = $db->getRows(
        "SELECT 
            ih.invoice_h_id,
            ih.invoice_h_code,
            ih.invoice_h_date,
            ih.invoice_h_delivery_date,
            ih.invoice_h_order_note,
            ih.invoice_h_delivery_address,
            ih.delivery_city_name,
            ih.CustomerCurrencyId,
            c.customer_name,
            c.customer_email,
            c.address_line_1,
            c.address_line_2,
            c.city,
            c.state,
            c.postal_code,
            c.country,
            id.invoice_d_qty,
            id.invoice_d_item_price,
            id.invoice_d_vat,
            id.invoice_d_vat_rate,
            id.invoice_d_discount_total,
            id.invoice_d_item_total,
            im.item_code,
            im.item_name,
            im.gst_vat_code
         FROM invoice_details id
         JOIN invoice_hedder ih ON ih.invoice_h_id = id.invoice_h_id
         JOIN customer c ON c.customer_id = ih.invoice_h_customer_id
         JOIN item_master im ON im.item_id = id.invoice_d_item_id
         WHERE ih.invoice_h_date BETWEEN ? AND ?
           AND ih.invoice_h_status = 1
         ORDER BY ih.invoice_h_date ASC, ih.invoice_h_code ASC, id.invoice_d_id ASC",
        [$date_from, $date_to]
    );
}

// ----- CSV Export -----
if ($export && $validDates) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="Xero_Sales_Invoices_' . $date_from . '_to_' . $date_to . '.csv"');

    $out = fopen('php://output', 'w');
    // Xero header
    fputcsv($out, [
        '*ContactName','EmailAddress',
        'POAddressLine1','POAddressLine2','POAddressLine3','POAddressLine4',
        'POCity','PORegion','POPostalCode','POCountry',
        '*InvoiceNumber','Reference','*InvoiceDate','*DueDate',
        'InventoryItemCode','*Description','*Quantity','*UnitAmount',
        'Discount','Total','*AccountCode','*TaxType',
        'TrackingName1','TrackingOption1','TrackingName2','TrackingOption2',
        'Currency','BrandingTheme'
    ]);

    foreach ($rows as $r) {
        $taxType = xeroInvoiceTaxType($r);
        $currency = xeroInvoiceCurrency($r, $activeCurrency);
        $lineTotal = xeroInvoiceLineTotal($r);

        // Format dates as DD/MM/YYYY for Xero
        $invoiceDate = date('d/m/Y', strtotime($r['invoice_h_date']));
        $dueDate     = !empty($r['invoice_h_delivery_date'])
            ? date('d/m/Y', strtotime($r['invoice_h_delivery_date']))
            : $invoiceDate;

        // Discount as percentage or blank
        $discount = ($r['invoice_d_discount_total'] > 0)
            ? number_format((float)$r['invoice_d_discount_total'], 2, '.', '')
            : '';

        fputcsv($out, [
            $r['customer_name'],                         // *ContactName
            $r['customer_email'] ?? '',                  // EmailAddress
            $r['address_line_1'] ?? '',                  // POAddressLine1
            $r['address_line_2'] ?? '',                  // POAddressLine2
            '',                                          // POAddressLine3
            '',                                          // POAddressLine4
            $r['city'] ?? '',                            // POCity
            $r['state'] ?? '',                           // PORegion
            $r['postal_code'] ?? '',                     // POPostalCode
            $r['country'] ?? '',                         // POCountry
            $r['invoice_h_code'],                        // *InvoiceNumber
            $r['invoice_h_order_note'] ?? '',             // Reference
            $invoiceDate,                                // *InvoiceDate
            $dueDate,                                    // *DueDate
            $r['item_code'] ?? '',                       // InventoryItemCode
            $r['item_name'],                             // *Description
            (int)$r['invoice_d_qty'],                    // *Quantity
            number_format((float)$r['invoice_d_item_price'], 2, '.', ''), // *UnitAmount
            $discount,                                   // Discount
            number_format($lineTotal, 2, '.', ''),       // Total
            '200',                                       // *AccountCode (Sales)
            $taxType,                                    // *TaxType
            '',                                          // TrackingName1
            '',                                          // TrackingOption1
            '',                                          // TrackingName2
            '',                                          // TrackingOption2
            $currency,                                   // Currency
            ''                                           // BrandingTheme
        ]);
    }

    fclose($out);
    exit;
}

// Format display dates
$display_date_from = $date_from ? date('d/m/Y', strtotime($date_from)) : '';
$display_date_to   = $date_to   ? date('d/m/Y', strtotime($date_to))   : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Xero Invoice Export</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
    <style>
        .export-filter-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            background: #fff;
            border: 1px solid #e1e1e1;
            border-radius: 4px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        .export-filter-bar label {
            font-weight: 600;
            margin-bottom: 0;
        }
        .export-filter-bar input[type="date"] {
            padding: 6px 10px;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 13px;
        }
        .preview-table {
            font-size: 12px;
        }
        .preview-table th {
            white-space: nowrap;
            background: #f5f5f5;
        }
        .badge-count {
            font-size: 14px;
            padding: 5px 12px;
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
                <!-- Breadcrumb -->
                <div class="page-bar">
                    <ul class="page-breadcrumb">
                        <li><a href="index.php">Home</a> <i class="fa fa-circle"></i></li>
                        <li><a href="#">Reports</a> <i class="fa fa-circle"></i></li>
                        <li><span>Xero Invoice Export</span></li>
                    </ul>
                </div>

                <h1 class="page-title">Xero Invoice Export</h1>

                <!-- Filter Bar -->
                <form method="get" class="export-filter-bar">
                    <label for="date_from">From:</label>
                    <input type="date" id="date_from" name="date_from" value="<?php echo xeroExportHtml($date_from); ?>" required />

                    <label for="date_to">To:</label>
                    <input type="date" id="date_to" name="date_to" value="<?php echo xeroExportHtml($date_to); ?>" required />

                    <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Preview</button>

                    <?php if ($validDates && count($rows) > 0) { ?>
                        <a href="?date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&export=1"
                           class="btn btn-success btn-sm">
                            <i class="fa fa-download"></i> Download CSV
                        </a>
                    <?php } ?>
                </form>

                <?php if ($validDates) { ?>
                    <?php
                    // Count unique invoices
                    $invoiceIds = array_unique(array_column($rows, 'invoice_h_id'));
                    $invoiceCount = count($invoiceIds);
                    $lineCount = count($rows);
                    $grandTotalsByCurrency = [];
                    foreach ($rows as $summaryRow) {
                        $summaryCurrency = xeroInvoiceCurrency($summaryRow, $activeCurrency);
                        if (!isset($grandTotalsByCurrency[$summaryCurrency])) {
                            $grandTotalsByCurrency[$summaryCurrency] = 0.0;
                        }
                        $grandTotalsByCurrency[$summaryCurrency] += xeroInvoiceLineTotal($summaryRow);
                    }
                    $grandTotalLines = [];
                    foreach ($grandTotalsByCurrency as $summaryCurrency => $summaryAmount) {
                        $grandTotalLines[] = xeroExportHtml($summaryCurrency) . ' ' . number_format((float)$summaryAmount, 2);
                    }
                    ?>
                    <div style="margin-bottom:10px;">
                        <span class="badge badge-count bg-blue-steel"><?php echo $invoiceCount; ?> Invoice(s)</span>
                        <span class="badge badge-count bg-grey-cascade"><?php echo $lineCount; ?> Line(s)</span>
                        <span style="margin-left:10px; color:#888;">
                            <?php echo $display_date_from; ?> &mdash; <?php echo $display_date_to; ?>
                        </span>
                    </div>

                    <?php if ($lineCount > 0) { ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover preview-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Invoice #</th>
                                    <th>Date</th>
                                    <th>Due Date</th>
                                    <th>Customer</th>
                                    <th>Item Code</th>
                                    <th>Description</th>
                                    <th class="text-right">Qty</th>
                                    <th class="text-right">Unit Price</th>
                                    <th class="text-right">Discount</th>
                                    <th class="text-right">Total</th>
                                    <th>Tax Type</th>
                                    <th>Currency</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 1; foreach ($rows as $r) {
                                    $taxType = xeroInvoiceTaxType($r);
                                    $lineTotal = xeroInvoiceLineTotal($r);
                                    $currency = xeroInvoiceCurrency($r, $activeCurrency);
                                ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td><?php echo xeroExportHtml($r['invoice_h_code']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($r['invoice_h_date'])); ?></td>
                                    <td><?php echo !empty($r['invoice_h_delivery_date']) ? date('d/m/Y', strtotime($r['invoice_h_delivery_date'])) : '-'; ?></td>
                                    <td><?php echo xeroExportHtml($r['customer_name']); ?></td>
                                    <td><?php echo xeroExportHtml($r['item_code']); ?></td>
                                    <td><?php echo xeroExportHtml($r['item_name']); ?></td>
                                    <td class="text-right"><?php echo (int)$r['invoice_d_qty']; ?></td>
                                    <td class="text-right"><?php echo number_format((float)$r['invoice_d_item_price'], 2); ?></td>
                                    <td class="text-right"><?php echo $r['invoice_d_discount_total'] > 0 ? number_format((float)$r['invoice_d_discount_total'], 2) : '-'; ?></td>
                                    <td class="text-right"><?php echo number_format($lineTotal, 2); ?></td>
                                    <td><?php echo xeroExportHtml($taxType); ?></td>
                                    <td><?php echo xeroExportHtml($currency); ?></td>
                                </tr>
                                <?php } ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="10" class="text-right">Grand Total</th>
                                    <th class="text-right"><?php echo implode('<br>', $grandTotalLines); ?></th>
                                    <th></th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php } else { ?>
                        <div class="alert alert-warning">No invoices found for the selected date range.</div>
                    <?php } ?>
                <?php } elseif ($date_from || $date_to) { ?>
                    <div class="alert alert-danger">Please enter valid From and To dates (From must not be after To).</div>
                <?php } ?>

            </div>
        </div>
    </div>
    <?php include('common/footer.php'); ?>
</body>
</html>
<?php ob_end_flush(); ?>
