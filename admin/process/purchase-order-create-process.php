<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('../include/database.php');
include('../include/check_login.php');
include('../get_url.php');
require_once(__DIR__ . '/../include/uom_helper.php');

date_default_timezone_set("Asia/Colombo");

function redirectWithMessage($message, $type = 'error')
{
    redirect('purchase-order-create.php?message=' . urlencode($message) . '&type=' . urlencode($type));
}

function buildPurchaseNoteEmailRecipients(Database $db, int $supplierId): array
{
    $supplier = $db->getRow('SELECT supplier_name, supplier_email, contact_name FROM supplier WHERE supplier_id = ?', [$supplierId]) ?: [];
    $recipients = [];
    $seenEmails = [];

    $addRecipient = function ($email, $name = '') use (&$recipients, &$seenEmails) {
        $normalizedEmail = strtolower(trim((string) $email));
        if ($normalizedEmail === '' || !filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL) || isset($seenEmails[$normalizedEmail])) {
            return;
        }

        $seenEmails[$normalizedEmail] = true;
        $recipients[] = [
            'email' => $normalizedEmail,
            'name' => trim((string) $name),
        ];
    };

    if (!empty($supplier['supplier_email'])) {
        $addRecipient($supplier['supplier_email'], $supplier['contact_name'] ?: $supplier['supplier_name'] ?: 'Supplier');
    }

    try {
        $additionalRows = $db->getRows('SELECT email_address FROM supplier_email_accounts WHERE supplier_id = ? ORDER BY id ASC', [$supplierId]) ?: [];
        foreach ($additionalRows as $row) {
            if (!empty($row['email_address'])) {
                $addRecipient($row['email_address'], $supplier['supplier_name'] ?? 'Supplier');
            }
        }
    } catch (Exception $e) {
        // Ignore missing optional table in older databases.
    }

    return [
        'supplier' => $supplier,
        'recipients' => $recipients,
    ];
}

function buildPurchaseNoteEmailBody(array $note, array $supplier, array $location, array $items, string $currencyCode): string
{
    $rowsHtml = '';
    $subTotal = 0.0;
    $totalVat = 0.0;

    foreach ($items as $index => $item) {
        $qty = (float) ($item['requested_qty'] ?? 0);
        $qpu = (float) ($item['qty_per_uom'] ?? 0);
        if ($qpu <= 0) { $qpu = 1.0; }
        $baseQty = isset($item['requested_qty_base']) && $item['requested_qty_base'] !== null
            ? (float) $item['requested_qty_base']
            : ($qty * $qpu);
        $rate = isset($item['unit_price']) && $item['unit_price'] !== null
            ? (float) $item['unit_price']
            : (float) ($item['item_purchase_price'] ?? 0);
        $vatRate = isset($item['vat_rate']) && $item['vat_rate'] !== null
            ? (float) $item['vat_rate']
            : (float) ($item['item_vat'] ?? 0);
        // unit_price is per base UOM
        $lineNet = $baseQty * $rate;
        $lineVat = ($lineNet * $vatRate) / 100;
        $lineGross = $lineNet + $lineVat;

        $subTotal += $lineNet;
        $totalVat += $lineVat;

        $rowsHtml .= '<tr>'
            . '<td style="padding:8px;border:1px solid #dcdcdc;text-align:center;">' . ($index + 1) . '</td>'
            . '<td style="padding:8px;border:1px solid #dcdcdc;">' . htmlspecialchars((string) ($item['item_name'] ?? '')) . '</td>'
            . '<td style="padding:8px;border:1px solid #dcdcdc;text-align:center;">' . htmlspecialchars((string) ($item['item_code'] ?? '')) . '</td>'
            . '<td style="padding:8px;border:1px solid #dcdcdc;text-align:right;">' . number_format($qty, 2) . '</td>'
            . '<td style="padding:8px;border:1px solid #dcdcdc;text-align:right;">' . htmlspecialchars($currencyCode) . ' ' . number_format($rate, 2) . '</td>'
            . '<td style="padding:8px;border:1px solid #dcdcdc;text-align:right;">' . number_format($vatRate, 2) . '%</td>'
            . '<td style="padding:8px;border:1px solid #dcdcdc;text-align:right;">' . htmlspecialchars($currencyCode) . ' ' . number_format($lineGross, 2) . '</td>'
            . '</tr>';
    }

    $grandTotal = $subTotal + $totalVat;
    $supplierName = (string) ($supplier['supplier_name'] ?? 'Supplier');
    $locationName = (string) ($location['name'] ?? 'Main Location');
    $locationAddress = trim((string) ($location['address'] ?? ''));
    $locationPhone = trim((string) ($location['phone_no'] ?? ''));
    $remarks = trim((string) ($note['remarks'] ?? ''));

    return '<div style="font-family:Arial,sans-serif;font-size:14px;color:#222;line-height:1.5;">'
        . '<div style="background:#2f4050;color:#fff;padding:18px 20px;border-radius:6px 6px 0 0;">'
        . '<h2 style="margin:0;font-size:22px;">Purchase Note</h2>'
        . '<div style="margin-top:6px;font-size:13px;opacity:0.92;">' . htmlspecialchars((string) ($note['purchase_note_code'] ?? '')) . ' | ' . htmlspecialchars((string) ($note['purchase_date'] ?? '')) . '</div>'
        . '</div>'
        . '<div style="border:1px solid #dcdcdc;border-top:none;padding:20px;border-radius:0 0 6px 6px;">'
        . '<p style="margin-top:0;">Dear ' . htmlspecialchars($supplierName) . ',</p>'
        . '<p>Please find below the purchase note created for your items.</p>'
        . '<table style="width:100%;border-collapse:collapse;margin:18px 0;">'
        . '<tr>'
        . '<td style="width:50%;vertical-align:top;padding-right:12px;">'
        . '<div style="font-weight:bold;margin-bottom:6px;">Supplier</div>'
        . '<div>' . htmlspecialchars($supplierName) . '</div>'
        . (!empty($supplier['supplier_email']) ? '<div>' . htmlspecialchars((string) $supplier['supplier_email']) . '</div>' : '')
        . '</td>'
        . '<td style="width:50%;vertical-align:top;padding-left:12px;">'
        . '<div style="font-weight:bold;margin-bottom:6px;">Location</div>'
        . '<div>' . htmlspecialchars($locationName) . '</div>'
        . ($locationAddress !== '' ? '<div>' . nl2br(htmlspecialchars($locationAddress)) . '</div>' : '')
        . ($locationPhone !== '' ? '<div>' . htmlspecialchars($locationPhone) . '</div>' : '')
        . '</td>'
        . '</tr>'
        . '</table>'
        . '<table style="width:100%;border-collapse:collapse;margin:18px 0;">'
        . '<thead>'
        . '<tr style="background:#f3f6f9;">'
        . '<th style="padding:8px;border:1px solid #dcdcdc;">#</th>'
        . '<th style="padding:8px;border:1px solid #dcdcdc;text-align:left;">Item</th>'
        . '<th style="padding:8px;border:1px solid #dcdcdc;text-align:left;">Code</th>'
        . '<th style="padding:8px;border:1px solid #dcdcdc;text-align:right;">Qty</th>'
        . '<th style="padding:8px;border:1px solid #dcdcdc;text-align:right;">Rate</th>'
        . '<th style="padding:8px;border:1px solid #dcdcdc;text-align:right;">GST</th>'
        . '<th style="padding:8px;border:1px solid #dcdcdc;text-align:right;">Line Total</th>'
        . '</tr>'
        . '</thead>'
        . '<tbody>' . $rowsHtml . '</tbody>'
        . '</table>'
        . '<table style="width:320px;margin-left:auto;border-collapse:collapse;">'
        . '<tr><td style="padding:6px 8px;border:1px solid #dcdcdc;">Sub Total</td><td style="padding:6px 8px;border:1px solid #dcdcdc;text-align:right;">' . htmlspecialchars($currencyCode) . ' ' . number_format($subTotal, 2) . '</td></tr>'
        . '<tr><td style="padding:6px 8px;border:1px solid #dcdcdc;">Total GST</td><td style="padding:6px 8px;border:1px solid #dcdcdc;text-align:right;">' . htmlspecialchars($currencyCode) . ' ' . number_format($totalVat, 2) . '</td></tr>'
        . '<tr><td style="padding:6px 8px;border:1px solid #dcdcdc;font-weight:bold;">Grand Total</td><td style="padding:6px 8px;border:1px solid #dcdcdc;text-align:right;font-weight:bold;">' . htmlspecialchars($currencyCode) . ' ' . number_format($grandTotal, 2) . '</td></tr>'
        . '</table>'
        . ($remarks !== '' ? '<div style="margin-top:18px;"><div style="font-weight:bold;margin-bottom:6px;">Remarks</div><div>' . nl2br(htmlspecialchars($remarks)) . '</div></div>' : '')
        . '<p style="margin-top:24px;">Regards,<br>Bakery Admin</p>'
        . '</div>'
        . '</div>';
}

function sendPurchaseNoteEmail(Database $db, int $purchaseNoteId): array
{
    try {
        require_once(__DIR__ . '/../include/EmailService.php');

        $emailService = new EmailService();
        if (!$emailService->isEnabled()) {
            return [
                'sent' => false,
                'error' => $emailService->getLastError() ?: 'Email service is not enabled or not configured.',
            ];
        }

        $note = $db->getRow('SELECT * FROM purchase_note_header WHERE purchase_note_id = ?', [$purchaseNoteId]) ?: [];
        if (empty($note)) {
            return ['sent' => false, 'error' => 'Purchase note not found for emailing.'];
        }

        $recipientData = buildPurchaseNoteEmailRecipients($db, (int) ($note['supplier_id'] ?? 0));
        if (empty($recipientData['recipients'])) {
            return ['sent' => false, 'error' => 'No supplier email address found.'];
        }

        $location = $db->getRow('SELECT name, phone_no, address FROM location_master WHERE id = ?', [(int) ($note['location_id'] ?? 0)]) ?: [];
        $items = $db->getRows(
            'SELECT pni.*, im.item_name, im.item_code, im.item_purchase_price, im.item_vat
             FROM purchase_note_items pni
             JOIN item_master im ON im.item_id = pni.product_id
             WHERE pni.purchase_note_id = ?
             ORDER BY pni.purchase_note_item_id ASC',
            [$purchaseNoteId]
        ) ?: [];
        $currencyRow = $db->getRow('SELECT currency FROM currency WHERE activated = ? LIMIT 1', ['Y']);
        $currencyCode = (string) ($currencyRow['currency'] ?? '');

        $subject = 'Purchase Note - ' . (string) ($note['purchase_note_code'] ?? ('PN-' . $purchaseNoteId));
        $htmlBody = buildPurchaseNoteEmailBody($note, $recipientData['supplier'], $location, $items, $currencyCode);
        $sent = $emailService->send($recipientData['recipients'], $subject, $htmlBody, [], 'purchase_note', $purchaseNoteId);

        return [
            'sent' => $sent,
            'error' => $sent ? '' : $emailService->getLastError(),
        ];
    } catch (Exception $e) {
        error_log('Purchase Note Email Error: ' . $e->getMessage());
        return [
            'sent' => false,
            'error' => $e->getMessage(),
        ];
    }
}

if (!isset($_POST['purchase_note_code'], $_POST['purchase_date'], $_POST['supplier_id'], $_POST['location_id'])) {
    redirectWithMessage('Missing required fields.');
}

$purchaseNoteCode = trim($_POST['purchase_note_code']);
$purchaseDate = $_POST['purchase_date'];
$supplierId = (int) $_POST['supplier_id'];
$locationId = (int) $_POST['location_id'];
$remarks = $_POST['remarks'] ?? '';
$productIds = $_POST['product_id'] ?? [];
$requestedQtys = $_POST['requested_qty'] ?? [];
$unitPrices = $_POST['unit_price'] ?? [];
$vatRates = $_POST['vat_rate'] ?? [];
$vatAmounts = $_POST['vat_amount'] ?? [];
$lineTotals = $_POST['line_total'] ?? [];
$lineUomIds = $_POST['line_uom_id'] ?? [];
$lineQtyPerUoms = $_POST['line_qty_per_uom'] ?? [];
$lineBaseQtys = $_POST['line_base_qty'] ?? [];
$createdBy = str_replace(',', '', $_SESSION['userid'] ?? '');
$createdAt = date('Y-m-d H:i:s');
$submitAction = trim((string) ($_POST['submit_action'] ?? 'save'));

if ($supplierId <= 0) {
    redirectWithMessage('Please select a supplier.');
}

if (empty($productIds) || empty($requestedQtys)) {
    redirectWithMessage('Please add at least one item.');
}

$db = new Database();
$hasAllowInGrnColumn = (bool) $db->getRow("SHOW COLUMNS FROM item_master LIKE 'allow_in_grn'");
ensureItemUomSchema($db);

$validItems = [];
for ($i = 0; $i < count($productIds); $i++) {
    $productId = (int) ($productIds[$i] ?? 0);
    $qty = (float) ($requestedQtys[$i] ?? 0);
    $unitPrice = (float) ($unitPrices[$i] ?? 0);
    $vatRate = (float) ($vatRates[$i] ?? 0);
    $uomId = (int) ($lineUomIds[$i] ?? 0);
    $qtyPerUom = (float) ($lineQtyPerUoms[$i] ?? 0);
    $baseQty = (float) ($lineBaseQtys[$i] ?? 0);
    if ($qtyPerUom <= 0) { $qtyPerUom = 1.0; }
    if ($baseQty <= 0) { $baseQty = $qty * $qtyPerUom; }
    if ($unitPrice < 0) { $unitPrice = 0.0; }
    if ($vatRate < 0) { $vatRate = 0.0; }
    if ($productId > 0 && $qty > 0) {
        // unit_price is per BASE UOM, so line net uses base qty
        $lineNet = $baseQty * $unitPrice;
        $vatAmount = ($lineNet * $vatRate) / 100.0;
        $lineTotal = $lineNet + $vatAmount;
        $validItems[] = [
            'product_id' => $productId,
            'requested_qty' => $qty,
            'unit_price' => $unitPrice,
            'vat_rate' => $vatRate,
            'vat_amount' => $vatAmount,
            'line_total' => $lineTotal,
            'uom_id' => $uomId > 0 ? $uomId : null,
            'qty_per_uom' => $qtyPerUom,
            'base_qty' => $baseQty,
        ];
    }
}

if (count($validItems) === 0) {
    redirectWithMessage('Please enter at least one valid item quantity.');
}

$candidateProductIds = array_values(array_unique(array_map(static function ($item) {
    return (int) $item['product_id'];
}, $validItems)));

$allowedProductIds = [];
if (!empty($candidateProductIds)) {
    $placeholders = implode(',', array_fill(0, count($candidateProductIds), '?'));
    $productQuery = 'SELECT item_id FROM item_master WHERE item_id IN (' . $placeholders . ')';
    if ($hasAllowInGrnColumn) {
        $productQuery .= ' AND (allow_in_grn = 1 OR allow_in_grn IS NULL)';
    }

    $allowedRows = $db->getRows($productQuery, $candidateProductIds) ?: [];
    foreach ($allowedRows as $row) {
        $allowedProductIds[(int) $row['item_id']] = true;
    }
}

foreach ($validItems as $item) {
    if (!isset($allowedProductIds[(int) $item['product_id']])) {
        redirectWithMessage('One or more selected products are not allowed in Purchase / GRN.');
    }
}

$db->insertRow(
    'INSERT INTO purchase_note_header (purchase_note_code, purchase_date, supplier_id, location_id, remarks, status, created_by, created_at) VALUES (?,?,?,?,?,?,?,?)',
    [$purchaseNoteCode, $purchaseDate, $supplierId, $locationId, $remarks, 'OPEN', $createdBy, $createdAt]
);

$row = $db->getRow('SELECT purchase_note_id FROM purchase_note_header ORDER BY purchase_note_id DESC LIMIT 1');
$purchaseNoteId = (int) ($row['purchase_note_id'] ?? 0);

if ($purchaseNoteId <= 0) {
    redirectWithMessage('Failed to create purchase note.');
}

foreach ($validItems as $item) {
    $requestedQty = (float) $item['requested_qty'];
    $baseQty = (float) $item['base_qty'];
    $db->insertRow(
        'INSERT INTO purchase_note_items (purchase_note_id, product_id, requested_qty, unit_price, vat_rate, vat_amount, line_total, uom_id, qty_per_uom, requested_qty_base, total_received_qty, balance_qty, total_received_qty_base, balance_qty_base) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
        [$purchaseNoteId, $item['product_id'], $requestedQty, $item['unit_price'], $item['vat_rate'], $item['vat_amount'], $item['line_total'], $item['uom_id'], $item['qty_per_uom'], $baseQty, 0, $requestedQty, 0, $baseQty]
    );
}

$redirectMessage = 'Purchase note created successfully';
$redirectUrl = 'purchase-order-view.php?id=' . $purchaseNoteId;

if ($submitAction === 'save_email') {
    $emailResult = sendPurchaseNoteEmail($db, $purchaseNoteId);
    if (!empty($emailResult['sent'])) {
        $redirectMessage .= ' and email sent successfully';
    } else {
        $redirectMessage .= '. Email not sent';
        if (!empty($emailResult['error'])) {
            $redirectMessage .= ': ' . $emailResult['error'];
        }
    }
} elseif ($submitAction === 'save_print') {
    $redirectMessage .= '. Print dialog opened';
    $redirectUrl .= '&autoprint=1';
}

redirect($redirectUrl . '&message=' . urlencode($redirectMessage) . '&type=success');
