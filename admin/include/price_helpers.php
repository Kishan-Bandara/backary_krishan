<?php
// Helper functions for product price mappings
require_once __DIR__ . '/database.php';

function getProductPriceMapping($productId, $priceTypeId, $locationId = null, $db = null)
{
    $db = $db ?: new Database();

    // first try specific location mapping
    if ($locationId !== null) {
        $row = $db->getRow('SELECT price FROM product_price_mapping WHERE product_id = ? AND price_type_id = ? AND location_id = ? LIMIT 1', [$productId, $priceTypeId, $locationId]);
        if ($row && isset($row['price'])) {
            return (float)$row['price'];
        }
    }

    // fallback to global mapping (location_id IS NULL)
    $row = $db->getRow('SELECT price FROM product_price_mapping WHERE product_id = ? AND price_type_id = ? AND location_id IS NULL LIMIT 1', [$productId, $priceTypeId]);
    if ($row && isset($row['price'])) {
        return (float)$row['price'];
    }

    return null;
}
