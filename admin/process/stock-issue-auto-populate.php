<?php
/**
 * Auto-populate expected finished products from recipes (product_ingredients table).
 * Given the raw materials being issued, find which finished products use them
 * and calculate how many can be produced.
 */
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('../include/database.php');
include('../include/check_login.php');

header('Content-Type: application/json');

$rawMaterialsJson = $_POST['raw_materials'] ?? '[]';
$rawMaterials = json_decode($rawMaterialsJson, true);

if (!is_array($rawMaterials) || count($rawMaterials) === 0) {
    echo json_encode(['products' => []]);
    exit;
}

$db = new Database();

// Build a map of raw material id => issued qty
$issuedMap = [];
foreach ($rawMaterials as $rm) {
    $id = (int)($rm['product_id'] ?? 0);
    $qty = (float)($rm['qty'] ?? 0);
    if ($id > 0 && $qty > 0) {
        $issuedMap[$id] = $qty;
    }
}

if (count($issuedMap) === 0) {
    echo json_encode(['products' => []]);
    exit;
}

// Find all finished products that use ANY of these raw materials as ingredients
$placeholders = implode(',', array_fill(0, count($issuedMap), '?'));
$params = array_keys($issuedMap);

$recipes = $db->getRows(
    "SELECT pi.product_id, pi.ingredient_id, pi.quantity as qty_per_unit, im.item_name, im.item_code
     FROM product_ingredients pi
     JOIN item_master im ON im.item_id = pi.product_id
     WHERE pi.ingredient_id IN ($placeholders)
     ORDER BY pi.product_id",
    $params
);

if (!$recipes || count($recipes) === 0) {
    echo json_encode(['products' => []]);
    exit;
}

// Group by product_id and calculate max producible quantity
// For each finished product, find the limiting ingredient
$productIngredients = [];
foreach ($recipes as $r) {
    $pid = (int)$r['product_id'];
    if (!isset($productIngredients[$pid])) {
        $productIngredients[$pid] = [
            'product_id' => $pid,
            'name' => trim($r['item_code'] . ' - ' . $r['item_name']),
            'ingredients' => []
        ];
    }
    $productIngredients[$pid]['ingredients'][] = [
        'ingredient_id' => (int)$r['ingredient_id'],
        'qty_per_unit' => (float)$r['qty_per_unit']
    ];
}

$results = [];
foreach ($productIngredients as $pid => $info) {
    $maxQty = PHP_FLOAT_MAX;
    foreach ($info['ingredients'] as $ing) {
        $ingId = $ing['ingredient_id'];
        $perUnit = $ing['qty_per_unit'];
        if ($perUnit <= 0) continue;

        if (isset($issuedMap[$ingId])) {
            $possible = floor($issuedMap[$ingId] / $perUnit);
            $maxQty = min($maxQty, $possible);
        } else {
            // This ingredient wasn't issued, so we can't fully calculate
            // but we still suggest based on what was issued
            $maxQty = min($maxQty, 0);
        }
    }

    if ($maxQty > 0 && $maxQty < PHP_FLOAT_MAX) {
        $results[] = [
            'product_id' => $pid,
            'name' => $info['name'],
            'qty' => $maxQty
        ];
    }
}

echo json_encode(['products' => $results]);
