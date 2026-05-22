<?php
/**
 * Returns the list of UOMs available for an item, including the base UOM.
 * Used by purchase-order-create.php and grn-create.php to populate per-line UOM dropdowns.
 *
 * Request: GET item_id
 * Response: { ok: true, base_uom_id, uoms: [ { uom_id, uom_name, qty_per_uom, is_default_purchase, is_default_sales, is_base } ] }
 */
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('../include/database.php');
include('../include/check_login.php');
require_once(__DIR__ . '/../include/uom_helper.php');

header('Content-Type: application/json; charset=utf-8');

$itemId = (int) ($_GET['item_id'] ?? 0);
if ($itemId <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Missing item_id']);
    exit;
}

$db = new Database();
ensureItemUomSchema($db);

$product = $db->getRow('SELECT item_uom FROM item_master WHERE item_id = ?', [$itemId]);
if (!$product) {
    echo json_encode(['ok' => false, 'error' => 'Item not found']);
    exit;
}

$list = getItemUomList($db, $itemId);

echo json_encode([
    'ok' => true,
    'base_uom_id' => (int) ($product['item_uom'] ?? 0),
    'uoms' => $list,
]);
