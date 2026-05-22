<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('../include/database.php');
include('../include/check_login.php');

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => ''
];

function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function ensureProductIngredientsTable($db) {
    $exists = $db->getRow("SHOW TABLES LIKE 'product_ingredients'");
    if (!$exists) {
        $db->insertRow(
            "CREATE TABLE `product_ingredients` (
                `id` int(10) NOT NULL AUTO_INCREMENT,
                `product_id` int(10) NOT NULL,
                `ingredient_id` int(10) NOT NULL,
                `quantity` decimal(12,4) NOT NULL DEFAULT 0.0000,
                `process_step` int(3) NOT NULL DEFAULT 1,
                `process_note` varchar(255) DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq_product_ingredient` (`product_id`,`ingredient_id`),
                KEY `idx_product` (`product_id`),
                KEY `idx_ingredient` (`ingredient_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }
}

try {
    $db = new Database();
    $action = isset($_POST['action']) ? trim($_POST['action']) : '';
    ensureProductIngredientsTable($db);

    switch ($action) {
        case 'add':
            $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
            $ingredientId = isset($_POST['ingredient_id']) ? (int)$_POST['ingredient_id'] : 0;
            $quantity = isset($_POST['quantity']) ? (float)$_POST['quantity'] : 0;
            $processStep = isset($_POST['process_step']) ? (int)$_POST['process_step'] : 1;
            $processNote = isset($_POST['process_note']) ? trim($_POST['process_note']) : '';

            if ($productId <= 0) {
                throw new Exception('Invalid product ID');
            }
            if ($ingredientId <= 0) {
                throw new Exception('Please select an ingredient');
            }
            if ($quantity <= 0) {
                throw new Exception('Quantity must be greater than 0');
            }

            // Check if product exists and is a finished product
            $product = $db->getRow('SELECT item_id FROM item_master WHERE item_id = ? AND is_raw_material = 0', [$productId]);
            if (!$product) {
                throw new Exception('Invalid finished product');
            }

            // Check if ingredient exists and is a raw material
            $ingredient = $db->getRow('SELECT item_id FROM item_master WHERE item_id = ? AND is_raw_material = 1', [$ingredientId]);
            if (!$ingredient) {
                throw new Exception('Invalid raw material');
            }

            // Check if this ingredient already exists for this product
            $existing = $db->getRow('SELECT id FROM product_ingredients WHERE product_id = ? AND ingredient_id = ?', [$productId, $ingredientId]);
            if ($existing) {
                // Update existing quantity
                $db->updateRow(
                    'UPDATE product_ingredients SET quantity = ?, process_step = ?, process_note = ? WHERE id = ?',
                    [$quantity, $processStep, $processNote ?: null, $existing['id']]
                );
                $response['message'] = 'Ingredient quantity updated successfully';
            } else {
                // Insert new
                $db->insertRow(
                    'INSERT INTO product_ingredients (product_id, ingredient_id, quantity, process_step, process_note) VALUES (?, ?, ?, ?, ?)',
                    [$productId, $ingredientId, $quantity, $processStep, $processNote ?: null]
                );
                $response['message'] = 'Ingredient added successfully';
            }

            $response['success'] = true;
            break;

        case 'update':
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $quantity = isset($_POST['quantity']) ? (float)$_POST['quantity'] : 0;
            $processStep = isset($_POST['process_step']) ? (int)$_POST['process_step'] : 1;
            $processNote = isset($_POST['process_note']) ? trim($_POST['process_note']) : '';

            if ($id <= 0) {
                throw new Exception('Invalid ingredient ID');
            }
            if ($quantity <= 0) {
                throw new Exception('Quantity must be greater than 0');
            }

            $db->updateRow(
                'UPDATE product_ingredients SET quantity = ?, process_step = ?, process_note = ? WHERE id = ?',
                [$quantity, $processStep, $processNote ?: null, $id]
            );

            $response['success'] = true;
            $response['message'] = 'Ingredient updated successfully';
            break;

        case 'delete':
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

            if ($id <= 0) {
                throw new Exception('Invalid ingredient ID');
            }

            $db->deleteRow('DELETE FROM product_ingredients WHERE id = ?', [$id]);

            $response['success'] = true;
            $response['message'] = 'Ingredient removed successfully';
            break;

        case 'get_ingredients':
            $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

            if ($productId <= 0) {
                throw new Exception('Invalid product ID');
            }

            $ingredients = $db->getRows(
                'SELECT pi.*, im.item_name, im.item_code 
                 FROM product_ingredients pi 
                 LEFT JOIN item_master im ON im.item_id = pi.ingredient_id 
                 WHERE pi.product_id = ? 
                 ORDER BY pi.process_step ASC, pi.id ASC',
                [$productId]
            );

            $response['success'] = true;
            $response['data'] = $ingredients;
            break;

        case 'calculate_requirements':
            $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
            $qty = isset($_POST['quantity']) ? (float)$_POST['quantity'] : 1;

            if ($productId <= 0) {
                throw new Exception('Invalid product ID');
            }

            $ingredients = $db->getRows(
                'SELECT pi.*, im.item_name, im.item_code 
                 FROM product_ingredients pi 
                 LEFT JOIN item_master im ON im.item_id = pi.ingredient_id 
                 WHERE pi.product_id = ? 
                 ORDER BY pi.process_step ASC',
                [$productId]
            );

            $requirements = [];
            $totalQty = 0;

            foreach ($ingredients as $ing) {
                $requiredQty = $ing['quantity'] * $qty;
                $requirements[] = [
                    'ingredient_id' => $ing['ingredient_id'],
                    'item_name' => $ing['item_name'],
                    'item_code' => $ing['item_code'],
                    'per_unit' => $ing['quantity'],
                    'required_qty' => $requiredQty,
                    'process_step' => $ing['process_step']
                ];
                $totalQty += $requiredQty;
            }

            $response['success'] = true;
            $response['data'] = [
                'requirements' => $requirements,
                'total_qty' => $totalQty,
                'production_qty' => $qty
            ];
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

if (!isAjaxRequest() && in_array($action, ['add', 'update', 'delete'], true) && $response['success']) {
    if (!empty($_POST['product_id'])) {
        $pid = (int)$_POST['product_id'];
        header('Location: ../product-ingredients.php?pid=' . $pid);
        exit;
    }
    if (!empty($_SERVER['HTTP_REFERER'])) {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
    header('Location: ../product-ingredients.php');
    exit;
}

echo json_encode($response);
