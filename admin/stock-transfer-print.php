<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');
include('get_url.php');

date_default_timezone_set("Asia/Colombo");

$db = new Database();
$transferId = (int) ($_GET['id'] ?? 0);

if ($transferId <= 0) {
    redirect('stock-transfer-list.php?message=' . urlencode('Invalid transfer.') . '&type=error');
}

$transfer = $db->getRow('SELECT * FROM stock_transfer_header WHERE transfer_id = ?', [$transferId]);
if (!$transfer) {
    redirect('stock-transfer-list.php?message=' . urlencode('Transfer not found.') . '&type=error');
}

$fromLocation = $db->getRow('SELECT location_code, name, address, phone_no FROM location_master WHERE id = ?', [$transfer['from_location_id']]);
$toLocation   = $db->getRow('SELECT location_code, name, address, phone_no FROM location_master WHERE id = ?', [$transfer['to_location_id']]);

$items = $db->getRows(
    'SELECT sti.*, itm.item_name, itm.item_code, itm.unit_of_measure, itm.batch_tracking,
            bm.batch_no, bm.expiry_date
     FROM stock_transfer_items sti
     JOIN item_master itm ON itm.item_id = sti.product_id
     LEFT JOIN batch_master bm ON bm.batch_id = sti.batch_id
     WHERE sti.transfer_id = ?
     ORDER BY sti.transfer_item_id ASC',
    [$transferId]
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

// Transfer totals
$grandTotal = 0.0;
foreach ($items as $it) { $grandTotal += (float)$it['total']; }

// Status
$statusMap = ['PENDING' => 'Pending', 'COMPLETED' => 'Completed', 'CANCELLED' => 'Cancelled'];
$statusText = $statusMap[$transfer['status']] ?? $transfer['status'];
$statusColor = ['PENDING' => '#e67e22', 'COMPLETED' => '#357e30', 'CANCELLED' => '#c0392b'];
$statusBg    = ['PENDING' => '#fef9f0', 'COMPLETED' => '#e8f5e9', 'CANCELLED' => '#fdecea'];
$statusBorder = ['PENDING' => '#f5c99a', 'COMPLETED' => '#c8e6c9', 'CANCELLED' => '#f5c6cb'];
$sColor  = $statusColor[$transfer['status']]  ?? '#333';
$sBg     = $statusBg[$transfer['status']]     ?? '#f9f9f9';
$sBorder = $statusBorder[$transfer['status']] ?? '#eee';

// GRN linked
$grn_for_transfer = $db->getRow(
    'SELECT grn_h_code FROM grn_hedder WHERE grn_h_supplier_invoice_code = ? AND grn_h_location = ? ORDER BY grn_h_id DESC LIMIT 1',
    ['Stock Transfer: ' . $transfer['transfer_code'], $transfer['to_location_id']]
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <title>Stock Transfer <?php echo htmlspecialchars($transfer['transfer_code']); ?></title>
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
            background: #2980b9;
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
        .btn-print:hover { background: #1a6fa1; }
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
            border-bottom: 2px solid #2980b9;
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
        .doc-title-block { text-align: right; }
        .doc-title {
            font-size: 22px;
            font-weight: 800;
            color: #2980b9;
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
            background: <?php echo $sBg; ?>;
            color: <?php echo $sColor; ?>;
            border: 1px solid <?php echo $sBorder; ?>;
        }

        /* ── Transfer Route Banner ──────────────────────────── */
        .route-banner {
            display: flex;
            align-items: center;
            gap: 0;
            margin-bottom: 16px;
            border: 1px solid #d1ecf1;
            border-radius: 3px;
            overflow: hidden;
        }
        .route-from, .route-to {
            flex: 1;
            padding: 10px 14px;
            background: #f0f8ff;
        }
        .route-to { background: #f0fff4; }
        .route-arrow {
            padding: 10px 14px;
            background: #2980b9;
            color: #fff;
            font-size: 18px;
            line-height: 1;
        }
        .route-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #888;
            margin-bottom: 3px;
        }
        .route-name {
            font-size: 14px;
            font-weight: 700;
            color: #222;
        }
        .route-detail {
            font-size: 11px;
            color: #555;
            line-height: 1.5;
        }

        /* ── Meta Row ───────────────────────────────────────── */
        .meta-row {
            display: flex;
            gap: 10px;
            margin-bottom: 14px;
        }
        .meta-box {
            flex: 1;
            border: 1px solid #e0e0e0;
            border-top: 3px solid #2980b9;
            padding: 8px 12px;
            border-radius: 2px;
        }
        .meta-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: #888;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }
        .meta-value {
            font-size: 13px;
            font-weight: 600;
            color: #222;
        }

        /* ── Section Title ──────────────────────────────────── */
        .section-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: #2980b9;
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
            background: #2980b9;
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
        table.items-table thead th.text-right  { text-align: right; }
        table.items-table thead th.text-center { text-align: center; }
        table.items-table tbody tr:nth-child(even) { background: #f0f8ff; }
        table.items-table tbody td {
            padding: 7px 8px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        table.items-table tbody td.text-right  { text-align: right; }
        table.items-table tbody td.text-center { text-align: center; }
        table.items-table tbody td.item-name   { font-weight: 600; }

        /* ── Totals ─────────────────────────────────────────── */
        .totals-wrap {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
        }
        table.totals-table {
            width: 200px;
            border-collapse: collapse;
            font-size: 12px;
        }
        table.totals-table td { padding: 5px 8px; border-bottom: 1px solid #eee; }
        table.totals-table td:last-child { text-align: right; }
        table.totals-table tr.grand-total td {
            font-size: 14px;
            font-weight: 700;
            color: #2980b9;
            border-top: 2px solid #2980b9;
            border-bottom: 2px solid #2980b9;
        }

        /* ── Remarks ────────────────────────────────────────── */
        .remarks-box {
            border-left: 3px solid #2980b9;
            background: #f0f8ff;
            padding: 8px 12px;
            font-size: 12px;
            color: #444;
            margin-bottom: 14px;
        }
        .remarks-box strong {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #2980b9;
        }

        /* ── GRN Badge ──────────────────────────────────────── */
        .grn-badge {
            display: inline-block;
            padding: 3px 10px;
            background: #e8f5e9;
            border: 1px solid #c8e6c9;
            border-radius: 3px;
            font-size: 11px;
            color: #357e30;
            font-weight: 600;
            margin-bottom: 14px;
        }

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
    <a href="stock-transfer-view.php?id=<?php echo $transferId; ?>" class="btn-back">&#8592; Back</a>
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
                <?php if ($companyPhone)   echo '&#9990; ' . htmlspecialchars($companyPhone); ?>
                <?php if ($companyPhone && $companyEmail) echo '&nbsp;&nbsp;'; ?>
                <?php if ($companyEmail)   echo '&#9993; ' . htmlspecialchars($companyEmail); ?>
            </div>
        </div>
        <div class="doc-title-block">
            <div class="doc-title">Stock Transfer</div>
            <div class="doc-code"><?php echo htmlspecialchars($transfer['transfer_code']); ?></div>
            <div class="doc-status"><?php echo htmlspecialchars($statusText); ?></div>
        </div>
    </div>

    <!-- Meta: Date / Transfer Code -->
    <div class="meta-row">
        <div class="meta-box">
            <div class="meta-label">Transfer Code</div>
            <div class="meta-value"><?php echo htmlspecialchars($transfer['transfer_code']); ?></div>
        </div>
        <div class="meta-box">
            <div class="meta-label">Transfer Date</div>
            <div class="meta-value"><?php echo date('d M Y', strtotime($transfer['transfer_date'])); ?></div>
        </div>
        <div class="meta-box">
            <div class="meta-label">Status</div>
            <div class="meta-value" style="color:<?php echo $sColor; ?>;"><?php echo htmlspecialchars($statusText); ?></div>
        </div>
        <?php if (!empty($grn_for_transfer['grn_h_code'])) { ?>
        <div class="meta-box">
            <div class="meta-label">GRN Reference</div>
            <div class="meta-value"><?php echo htmlspecialchars($grn_for_transfer['grn_h_code']); ?></div>
        </div>
        <?php } ?>
    </div>

    <!-- Route Banner -->
    <div class="route-banner">
        <div class="route-from">
            <div class="route-label">From Location</div>
            <div class="route-name"><?php echo htmlspecialchars(trim(($fromLocation['location_code'] ?? '') . ' &ndash; ' . ($fromLocation['name'] ?? ''))); ?></div>
            <div class="route-detail">
                <?php if (!empty($fromLocation['address']))  echo htmlspecialchars($fromLocation['address']) . '<br>'; ?>
                <?php if (!empty($fromLocation['phone_no'])) echo '&#9990; ' . htmlspecialchars($fromLocation['phone_no']); ?>
            </div>
        </div>
        <div class="route-arrow">&#10132;</div>
        <div class="route-to">
            <div class="route-label">To Location</div>
            <div class="route-name"><?php echo htmlspecialchars(trim(($toLocation['location_code'] ?? '') . ' &ndash; ' . ($toLocation['name'] ?? ''))); ?></div>
            <div class="route-detail">
                <?php if (!empty($toLocation['address']))  echo htmlspecialchars($toLocation['address']) . '<br>'; ?>
                <?php if (!empty($toLocation['phone_no'])) echo '&#9990; ' . htmlspecialchars($toLocation['phone_no']); ?>
            </div>
        </div>
    </div>

    <!-- Remarks -->
    <?php if (!empty($transfer['remarks'])) { ?>
        <div class="remarks-box">
            <strong>Remarks:</strong> <?php echo nl2br(htmlspecialchars($transfer['remarks'])); ?>
        </div>
    <?php } ?>

    <!-- Items -->
    <div class="section-title">Items Transferred</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:4%">#</th>
                <th style="width:11%">Item Code</th>
                <th>Item Name</th>
                <th class="text-center" style="width:7%">UOM</th>
                <th class="text-center" style="width:9%">Qty</th>
                <th class="text-center" style="width:12%">Batch No</th>
                <th class="text-center" style="width:10%">Expiry</th>
                <th class="text-right"  style="width:10%">Rate</th>
                <th class="text-right"  style="width:10%">Total</th>
            </tr>
        </thead>
        <tbody>
        <?php $rowNum = 0; foreach ($items as $item) { $rowNum++; ?>
            <tr>
                <td><?php echo $rowNum; ?></td>
                <td><?php echo htmlspecialchars($item['item_code']); ?></td>
                <td class="item-name">
                    <?php echo htmlspecialchars($item['item_name']); ?>
                    <?php
                    $bt = $item['batch_tracking'] ?? 'NONE';
                    if ($bt === 'BATCH')  echo ' <span style="font-size:9px; background:#d1ecf1; color:#0c5460; padding:1px 5px; border-radius:8px;">Batch</span>';
                    if ($bt === 'SERIAL') echo ' <span style="font-size:9px; background:#d1ecf1; color:#0c5460; padding:1px 5px; border-radius:8px;">Serial</span>';
                    ?>
                </td>
                <td class="text-center"><?php echo htmlspecialchars($item['unit_of_measure'] ?? '—'); ?></td>
                <td class="text-center"><strong><?php echo number_format((float)$item['qty'], 2); ?></strong></td>
                <td class="text-center"><?php echo !empty($item['batch_no']) ? htmlspecialchars($item['batch_no']) : '—'; ?></td>
                <td class="text-center"><?php echo !empty($item['expiry_date']) ? date('d M Y', strtotime($item['expiry_date'])) : '—'; ?></td>
                <td class="text-right"><?php echo $currency . ' ' . number_format((float)$item['rate'], 2); ?></td>
                <td class="text-right"><?php echo $currency . ' ' . number_format((float)$item['total'], 2); ?></td>
            </tr>
        <?php } ?>
        <?php if (empty($items)) { ?>
            <tr><td colspan="9" style="text-align:center; color:#888; padding:16px;">No items found.</td></tr>
        <?php } ?>
        </tbody>
    </table>

    <!-- Totals -->
    <div class="totals-wrap">
        <table class="totals-table">
            <tr class="grand-total">
                <td>Transfer Value</td>
                <td><?php echo $currency . ' ' . number_format($grandTotal, 2); ?></td>
            </tr>
        </table>
    </div>

    <!-- Signature Lines -->
    <div class="sig-row">
        <div class="sig-block">Prepared By</div>
        <div class="sig-block">Dispatched By</div>
        <div class="sig-block">Received By</div>
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
(function () {
    var params = new URLSearchParams(window.location.search);
    if (params.get('autoprint') === '1') {
        window.addEventListener('load', function () { setTimeout(window.print, 400); });
    }
})();
</script>
</body>
</html>
