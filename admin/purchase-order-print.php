<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');
include('get_url.php');

date_default_timezone_set("Asia/Colombo");

$db = new Database();
$purchaseNoteId = (int) ($_GET['id'] ?? 0);

if ($purchaseNoteId <= 0) {
    redirect('purchase-order-list.php?message=' . urlencode('Invalid purchase note.') . '&type=error');
}

$note = $db->getRow('SELECT * FROM purchase_note_header WHERE purchase_note_id = ?', [$purchaseNoteId]);
if (!$note) {
    redirect('purchase-order-list.php?message=' . urlencode('Purchase note not found.') . '&type=error');
}

$supplier  = $db->getRow('SELECT * FROM supplier WHERE supplier_id = ?', [$note['supplier_id']]);
$location  = $db->getRow('SELECT name, phone_no, address FROM location_master WHERE id = ?', [$note['location_id']]);
$items     = $db->getRows(
    'SELECT pni.*, itm.item_name, itm.item_code, itm.item_purchase_price, itm.item_vat, itm.unit_of_measure
     FROM purchase_note_items pni
     JOIN item_master itm ON itm.item_id = pni.product_id
     WHERE pni.purchase_note_id = ?',
    [$purchaseNoteId]
);

// Invoice settings (company branding)
$s = null;
try { $s = $db->getRow('SELECT * FROM invoice_settings WHERE id = 1'); } catch (Exception $e) {}
$companyName    = $s['receipt_name']    ?? 'Your Company';
$companyAddress = $s['receipt_address'] ?? '';
$companyPhone   = $s['receipt_phone']   ?? '';
$companyEmail   = $s['receipt_email']   ?? '';
$companyFooter  = $s['receipt_footer']  ?? '';
$logoPath       = $s['invoice_logo']    ?? '';

// Active currency
$currRow = $db->getRow('SELECT currency FROM currency WHERE activated = ? LIMIT 1', ['Y']);
$currency = $currRow['currency'] ?? '';

// Compute totals
$order_net = $order_vat = $order_gross = 0.0;
foreach ($items as $it) {
    $lineRate = isset($it['unit_price']) && $it['unit_price'] !== null
        ? (float) $it['unit_price']
        : (float) ($it['item_purchase_price'] ?? 0);
    $lineVatRate = isset($it['vat_rate']) && $it['vat_rate'] !== null
        ? (float) $it['vat_rate']
        : (float) ($it['item_vat'] ?? 0);
    $itQpu = (float) ($it['qty_per_uom'] ?? 0);
    if ($itQpu <= 0) { $itQpu = 1.0; }
    $itBaseQty = isset($it['requested_qty_base']) && $it['requested_qty_base'] !== null
        ? (float) $it['requested_qty_base']
        : ((float)($it['requested_qty'] ?? 0)) * $itQpu;
    // unit_price is per base UOM
    $lineNet  = $itBaseQty * $lineRate;
    $lineVat  = $lineNet * $lineVatRate / 100;
    $order_net   += $lineNet;
    $order_vat   += $lineVat;
}
$order_gross = $order_net + $order_vat;

// Status
$statusMap = ['OPEN' => 'Open', 'PARTIALLY_RECEIVED' => 'Partially Received', 'COMPLETED' => 'Completed', 'CANCELLED' => 'Cancelled'];
$statusText = $statusMap[$note['status']] ?? $note['status'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <title>Purchase Order <?php echo htmlspecialchars($note['purchase_note_code']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <style>
        /* ── Reset ─────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Arial', sans-serif;
            font-size: 13px;
            color: #222;
            background: #e0e0e0;
        }

        /* ── A4 Paper ───────────────────────────────────────── */
        .a4-page {
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            background: #fff;
            padding: 15mm 15mm 20mm 15mm;
            box-shadow: 0 4px 24px rgba(0,0,0,0.18);
            position: relative;
        }

        /* ── Print Button ───────────────────────────────────── */
        .print-bar {
            width: 210mm;
            margin: 0 auto 8px auto;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }
        .btn-print {
            background: #357e30;
            color: #fff;
            border: none;
            padding: 8px 20px;
            font-size: 13px;
            border-radius: 3px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-print:hover { background: #2a6425; }
        .btn-back {
            background: #6c757d;
            color: #fff;
            border: none;
            padding: 8px 16px;
            font-size: 13px;
            border-radius: 3px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-back:hover { background: #545b62; color: #fff; }

        /* ── Header ─────────────────────────────────────────── */
        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #357e30;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .company-logo img {
            max-height: 60px;
            max-width: 160px;
            object-fit: contain;
        }
        .company-name {
            font-size: 20px;
            font-weight: 700;
            color: #333;
            line-height: 1.2;
        }
        .company-sub {
            font-size: 11px;
            color: #666;
            margin-top: 3px;
            line-height: 1.6;
        }
        .doc-title-block {
            text-align: right;
        }
        .doc-title {
            font-size: 22px;
            font-weight: 800;
            color: #357e30;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .doc-code {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-top: 4px;
        }
        .doc-status {
            display: inline-block;
            margin-top: 6px;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            background: #e8f5e9;
            color: #357e30;
            border: 1px solid #c8e6c9;
        }

        /* ── Info Grid ──────────────────────────────────────── */
        .info-grid {
            display: flex;
            gap: 10px;
            margin-bottom: 16px;
        }
        .info-block {
            flex: 1;
            border: 1px solid #e0e0e0;
            border-top: 3px solid #357e30;
            padding: 10px 12px;
            border-radius: 2px;
        }
        .info-block.secondary { border-top-color: #6c757d; }
        .info-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: #888;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        .info-main {
            font-size: 14px;
            font-weight: 700;
            color: #222;
            margin-bottom: 2px;
        }
        .info-detail {
            font-size: 11px;
            color: #555;
            line-height: 1.6;
        }

        /* ── Section Title ──────────────────────────────────── */
        .section-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: #357e30;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }

        /* ── Items Table ────────────────────────────────────── */
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            font-size: 12px;
        }
        table.items-table thead tr {
            background: #357e30;
            color: #fff;
        }
        table.items-table thead th {
            padding: 7px 8px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        table.items-table thead th.text-right { text-align: right; }
        table.items-table thead th.text-center { text-align: center; }
        table.items-table tbody tr:nth-child(even) { background: #f7faf7; }
        table.items-table tbody tr:hover { background: #edf7ed; }
        table.items-table tbody td {
            padding: 7px 8px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        table.items-table tbody td.text-right { text-align: right; }
        table.items-table tbody td.text-center { text-align: center; }
        table.items-table tbody td.item-name { font-weight: 600; }

        /* ── Totals ─────────────────────────────────────────── */
        .totals-wrap {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
        }
        table.totals-table {
            width: 240px;
            border-collapse: collapse;
            font-size: 12px;
        }
        table.totals-table td { padding: 5px 8px; border-bottom: 1px solid #eee; }
        table.totals-table td:last-child { text-align: right; }
        table.totals-table tr.grand-total td {
            font-size: 14px;
            font-weight: 700;
            color: #357e30;
            border-top: 2px solid #357e30;
            border-bottom: 2px solid #357e30;
        }

        /* ── Remarks ────────────────────────────────────────── */
        .remarks-box {
            border-left: 3px solid #357e30;
            background: #f9fef9;
            padding: 8px 12px;
            font-size: 12px;
            color: #444;
            margin-bottom: 20px;
        }
        .remarks-box strong { font-size: 11px; text-transform: uppercase; letter-spacing: 0.4px; color: #357e30; }

        /* ── Signature ──────────────────────────────────────── */
        .sig-row {
            display: flex;
            gap: 20px;
            margin-top: 30px;
            padding-top: 10px;
        }
        .sig-block {
            flex: 1;
            border-top: 1px solid #333;
            padding-top: 6px;
            font-size: 11px;
            color: #444;
        }

        /* ── Footer ─────────────────────────────────────────── */
        .doc-footer {
            position: absolute;
            bottom: 12mm;
            left: 15mm;
            right: 15mm;
            border-top: 1px solid #eee;
            padding-top: 6px;
            font-size: 10px;
            color: #999;
            display: flex;
            justify-content: space-between;
        }

        /* ── Print Media ─────────────────────────────────────── */
        @media print {
            body { background: #fff; }
            .print-bar { display: none !important; }
            .a4-page {
                width: 100%;
                margin: 0;
                padding: 10mm 12mm 18mm 12mm;
                box-shadow: none;
                min-height: auto;
            }
            @page { size: A4 portrait; margin: 0; }
            table.items-table { page-break-inside: auto; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

<!-- Print / Back Buttons -->
<div class="print-bar">
    <a href="purchase-order-view.php?id=<?php echo $purchaseNoteId; ?>" class="btn-back">&#8592; Back</a>
    <button class="btn-print" onclick="window.print()">&#128438; Print / Save PDF</button>
</div>

<!-- A4 Page -->
<div class="a4-page">

    <!-- Header -->
    <div class="doc-header">
        <div class="company-info">
            <?php if (!empty($logoPath) && file_exists($logoPath)) { ?>
                <div class="company-logo" style="margin-bottom:6px;">
                    <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="Logo"/>
                </div>
            <?php } ?>
            <div class="company-name"><?php echo htmlspecialchars($companyName); ?></div>
            <div class="company-sub">
                <?php if ($companyAddress) echo nl2br(htmlspecialchars($companyAddress)) . '<br>'; ?>
                <?php if ($companyPhone)  echo '&#9990; ' . htmlspecialchars($companyPhone); ?>
                <?php if ($companyPhone && $companyEmail) echo '&nbsp;&nbsp;'; ?>
                <?php if ($companyEmail)  echo '&#9993; ' . htmlspecialchars($companyEmail); ?>
            </div>
        </div>
        <div class="doc-title-block">
            <div class="doc-title">Purchase Order</div>
            <div class="doc-code"><?php echo htmlspecialchars($note['purchase_note_code']); ?></div>
        </div>
    </div>

    <!-- Info Grid -->
    <div class="info-grid">
        <div class="info-block">
            <div class="info-label">Supplier</div>
            <div class="info-main"><?php echo htmlspecialchars($supplier['supplier_name'] ?? ''); ?></div>
            <div class="info-detail">
                <?php if (!empty($supplier['supplier_contact_no'])) echo '&#9990; ' . htmlspecialchars($supplier['supplier_contact_no']) . '<br>'; ?>
                <?php if (!empty($supplier['supplier_email']))      echo '&#9993; ' . htmlspecialchars($supplier['supplier_email'])      . '<br>'; ?>
                <?php if (!empty($supplier['supplier_address']))    echo htmlspecialchars($supplier['supplier_address']); ?>
            </div>
        </div>
        <div class="info-block secondary">
            <div class="info-label">Order Details</div>
            <div class="info-main"><?php echo htmlspecialchars($note['purchase_note_code']); ?></div>
            <div class="info-detail">
                Date: <strong><?php echo date('d M Y', strtotime($note['purchase_date'])); ?></strong><br>
                Location: <strong><?php echo htmlspecialchars($location['name'] ?? ''); ?></strong><br>
                Currency: <strong><?php echo htmlspecialchars($currency); ?></strong>
            </div>
        </div>
        <div class="info-block secondary">
            <div class="info-label">Order Location</div>
            <div class="info-main"><?php echo htmlspecialchars($location['name'] ?? ''); ?></div>
            <div class="info-detail">
                <?php if (!empty($location['phone_no'])) echo '&#9990; ' . htmlspecialchars($location['phone_no']) . '<br>'; ?>
                <?php if (!empty($location['address']))  echo htmlspecialchars($location['address']); ?>
            </div>
        </div>
    </div>

    <!-- Remarks -->
    <?php if (!empty($note['remarks'])) { ?>
        <div class="remarks-box">
            <strong>Remarks:</strong> <?php echo nl2br(htmlspecialchars($note['remarks'])); ?>
        </div>
    <?php } ?>

    <!-- Items -->
    <div class="section-title">Ordered Items</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:4%">#</th>
                <th style="width:11%">Item Code</th>
                <th>Item Name</th>
                <th class="text-center" style="width:7%">UOM</th>
                <th class="text-center" style="width:10%">Ordered Qty</th>
                <th class="text-right"  style="width:12%">Unit Price</th>
                <th class="text-right"  style="width:10%">Amount</th>
            </tr>
        </thead>
        <tbody>
        <?php $rowNum = 0; foreach ($items as $item) { $rowNum++;
            $lineRate = isset($item['unit_price']) && $item['unit_price'] !== null
                ? (float) $item['unit_price']
                : (float) ($item['item_purchase_price'] ?? 0);
            $itQpu = (float) ($item['qty_per_uom'] ?? 0);
            if ($itQpu <= 0) { $itQpu = 1.0; }
            $itBaseQty = isset($item['requested_qty_base']) && $item['requested_qty_base'] !== null
                ? (float) $item['requested_qty_base']
                : ((float)($item['requested_qty'] ?? 0)) * $itQpu;
            $lineAmt = $itBaseQty * $lineRate;
        ?>
            <tr>
                <td><?php echo $rowNum; ?></td>
                <td><?php echo htmlspecialchars($item['item_code']); ?></td>
                <td class="item-name"><?php echo htmlspecialchars($item['item_name']); ?></td>
                <td class="text-center"><?php echo htmlspecialchars($item['unit_of_measure'] ?? ''); ?></td>
                <td class="text-center"><?php echo number_format((float)$item['requested_qty'], 2); ?></td>
                <td class="text-right"><?php echo $currency . ' ' . number_format($lineRate, 2); ?></td>
                <td class="text-right"><?php echo $currency . ' ' . number_format($lineAmt, 2); ?></td>
            </tr>
        <?php } ?>
        <?php if (empty($items)) { ?>
            <tr><td colspan="7" style="text-align:center; color:#888; padding:16px;">No items found.</td></tr>
        <?php } ?>
        </tbody>
    </table>

    <!-- Totals -->
    <div class="totals-wrap">
        <table class="totals-table">
            <tr>
                <td>Sub Total</td>
                <td><?php echo $currency . ' ' . number_format($order_net, 2); ?></td>
            </tr>
            <tr>
                <td>Total GST</td>
                <td><?php echo $currency . ' ' . number_format($order_vat, 2); ?></td>
            </tr>
            <tr class="grand-total">
                <td>Grand Total</td>
                <td><?php echo $currency . ' ' . number_format($order_gross, 2); ?></td>
            </tr>
        </table>
    </div>

    <!-- Signature Lines -->
    <div class="sig-row">
        <div class="sig-block">Prepared By</div>
        <div class="sig-block">Approved By</div>
    </div>

    <!-- Footer -->
    <div class="doc-footer">
        <span>
            <?php if (!empty($companyFooter)) echo htmlspecialchars($companyFooter); ?>
        </span>
        <span>Printed: <?php echo date('d M Y, h:i A'); ?></span>
    </div>

</div><!-- /.a4-page -->

<script>
// Auto-print if ?autoprint=1
(function () {
    var params = new URLSearchParams(window.location.search);
    if (params.get('autoprint') === '1') {
        window.addEventListener('load', function () { setTimeout(window.print, 400); });
    }
})();
</script>
</body>
</html>
