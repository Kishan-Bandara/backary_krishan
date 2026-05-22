<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('../include/database.php');
include('../include/check_login.php');

if (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/json');

function sendJson($status, $message, array $extra = [])
{
    echo json_encode(array_merge([
        'status' => (bool) $status,
        'message' => $message,
    ], $extra));
    exit;
}

function normalizeMoney($value)
{
    $value = trim(str_replace(',', '', (string) $value));
    if ($value === '') {
        return '0.00';
    }
    if (!is_numeric($value)) {
        return null;
    }

    $amount = (float) $value;
    if ($amount < 0) {
        return null;
    }

    return number_format($amount, 2, '.', '');
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJson(false, 'Invalid request method.');
    }

    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) {
        sendJson(false, 'Invalid request payload.');
    }

    $csrfToken = (string) ($payload['csrf_token'] ?? '');
    if ($csrfToken === '' || $csrfToken !== ($_SESSION['bulk_product_price_csrf'] ?? '')) {
        sendJson(false, 'Invalid form submission. Please refresh the page and try again.');
    }

    $rows = $payload['rows'] ?? [];
    if (!is_array($rows) || empty($rows)) {
        sendJson(false, 'No product price changes were submitted.');
    }

    if (count($rows) > 2000) {
        sendJson(false, 'Too many rows submitted in one request.');
    }

    $db = new Database();
    $pdo = $db->getConnection();

    $taxRows = $db->getRows('SELECT code FROM product_vat_master');
    $validTaxCodes = [];
    foreach ($taxRows as $taxRow) {
        $code = trim((string) ($taxRow['code'] ?? ''));
        if ($code !== '') {
            $validTaxCodes[$code] = true;
        }
    }

    $validatedRows = [];
    foreach ($rows as $index => $row) {
        if (!is_array($row)) {
            sendJson(false, 'Invalid row data at position ' . ($index + 1) . '.');
        }

        $itemId = (int) ($row['item_id'] ?? 0);
        if ($itemId <= 0) {
            sendJson(false, 'Invalid product selected in row ' . ($index + 1) . '.');
        }

        $price = normalizeMoney($row['price'] ?? '0');
        $retailPrice = normalizeMoney($row['retail_price'] ?? '0');
        if ($price === null || $retailPrice === null) {
            sendJson(false, 'Price values must be valid positive numbers in row ' . ($index + 1) . '.');
        }

        $taxCode = trim((string) ($row['gst_vat_code'] ?? ''));
        if ($taxCode !== '' && !isset($validTaxCodes[$taxCode])) {
            sendJson(false, 'Invalid tax code selected in row ' . ($index + 1) . '.');
        }

        $product = $db->getRow('SELECT item_id FROM item_master WHERE item_id = ? LIMIT 1', [$itemId]);
        if (!$product) {
            sendJson(false, 'Product not found for row ' . ($index + 1) . '.');
        }

        $validatedRows[] = [
            'item_id' => $itemId,
            'price' => $price,
            'retail_price' => $retailPrice,
            'gst_vat_code' => $taxCode,
            'item_vat' => $taxCode === '' ? 'N' : 'Y',
        ];
    }

    $pdo->beginTransaction();

    foreach ($validatedRows as $validatedRow) {
        $db->updateRow(
            'UPDATE item_master SET item_normal_selling_price = ?, retail_price = ?, gst_vat_code = ?, item_vat = ? WHERE item_id = ?',
            [
                $validatedRow['price'],
                $validatedRow['retail_price'],
                $validatedRow['gst_vat_code'] !== '' ? $validatedRow['gst_vat_code'] : null,
                $validatedRow['item_vat'],
                $validatedRow['item_id'],
            ]
        );
    }

    $pdo->commit();

    sendJson(true, 'Updated prices for ' . count($validatedRows) . ' product(s).', [
        'updated_count' => count($validatedRows),
    ]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    sendJson(false, 'Bulk price update failed: ' . $e->getMessage());
}