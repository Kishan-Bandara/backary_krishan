<?php
/**
 * Unit of Measure helper (Business Central style).
 *
 * Concepts:
 *   - Every product has a base UOM stored in item_master.unit_of_measure (string, e.g. "Kilogram").
 *     The legacy item_master.item_uom ("Product Unit") is preserved as-is and is NOT used by this logic.
 *   - The unit_of_measure string is mapped to (or auto-created in) the item_uom table so the rest
 *     of the schema keeps working with uom_id foreign keys.
 *   - The base UOM is implicit in item_unit_of_measure with qty_per_uom = 1.
 *   - Additional alternative UOMs are stored in item_unit_of_measure with their qty_per_uom.
 *   - One alternative UOM may be flagged as default purchase UOM and one as default sales UOM.
 *
 * Conversion rule:
 *   base_qty = entered_qty * qty_per_uom
 */

if (!class_exists('Database')) {
    require_once __DIR__ . '/database.php';
}

/**
 * Ensures that all UOM-related schema (table + columns) exists.
 * Safe to call multiple times.
 */
function ensureItemUomSchema(Database $db): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    // 1) item_unit_of_measure table
    $tableExists = $db->getRow("SHOW TABLES LIKE 'item_unit_of_measure'");
    if (!$tableExists) {
        $db->insertRow(
            "CREATE TABLE `item_unit_of_measure` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `item_id` INT(10) NOT NULL,
                `uom_id` INT(10) NOT NULL,
                `qty_per_uom` DECIMAL(18,6) NOT NULL DEFAULT 1.000000,
                `is_default_purchase` TINYINT(1) NOT NULL DEFAULT 0,
                `is_default_sales` TINYINT(1) NOT NULL DEFAULT 0,
                `created_at` DATETIME NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq_item_uom` (`item_id`, `uom_id`),
                KEY `idx_item` (`item_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            []
        );
    }

    // 2) purchase_note_items extra columns
    addColumnIfMissing($db, 'purchase_note_items', 'uom_id', "INT(10) NULL DEFAULT NULL AFTER `requested_qty`");
    addColumnIfMissing($db, 'purchase_note_items', 'qty_per_uom', "DECIMAL(18,6) NULL DEFAULT NULL");
    addColumnIfMissing($db, 'purchase_note_items', 'requested_qty_base', "DECIMAL(18,6) NULL DEFAULT NULL");
    addColumnIfMissing($db, 'purchase_note_items', 'total_received_qty_base', "DECIMAL(18,6) NULL DEFAULT NULL");
    addColumnIfMissing($db, 'purchase_note_items', 'balance_qty_base', "DECIMAL(18,6) NULL DEFAULT NULL");
    addColumnIfMissing($db, 'purchase_note_items', 'unit_price', "DECIMAL(18,6) NULL DEFAULT NULL");
    addColumnIfMissing($db, 'purchase_note_items', 'vat_rate', "DECIMAL(10,4) NULL DEFAULT NULL");
    addColumnIfMissing($db, 'purchase_note_items', 'vat_amount', "DECIMAL(18,6) NULL DEFAULT NULL");
    addColumnIfMissing($db, 'purchase_note_items', 'line_total', "DECIMAL(18,6) NULL DEFAULT NULL");

    // 3) grn_details extra columns
    addColumnIfMissing($db, 'grn_details', 'uom_id', "INT(10) NULL DEFAULT NULL AFTER `grn_d_qty`");
    addColumnIfMissing($db, 'grn_details', 'qty_per_uom', "DECIMAL(18,6) NULL DEFAULT NULL");
    addColumnIfMissing($db, 'grn_details', 'grn_d_qty_base', "DECIMAL(18,6) NULL DEFAULT NULL");

    // 4) item_master extra column for additional UOM names (CSV of UOM names)
    addColumnIfMissing($db, 'item_master', 'additional_uoms', "VARCHAR(500) NULL DEFAULT NULL");

    // 5) stock_transfer_items extra columns (UOM-aware transfers/receives)
    $stockTransferTableExists = $db->getRow("SHOW TABLES LIKE 'stock_transfer_items'");
    if ($stockTransferTableExists) {
        addColumnIfMissing($db, 'stock_transfer_items', 'uom_id', "INT(10) NULL DEFAULT NULL AFTER `qty`");
        addColumnIfMissing($db, 'stock_transfer_items', 'qty_per_uom', "DECIMAL(18,6) NULL DEFAULT NULL");
        addColumnIfMissing($db, 'stock_transfer_items', 'qty_base', "DECIMAL(18,6) NULL DEFAULT NULL");
        addColumnIfMissing($db, 'stock_transfer_items', 'received_qty_base', "DECIMAL(18,6) NULL DEFAULT NULL");
    }

    // 6) stock_issue_items extra columns (UOM-aware issues)
    $stockIssueTableExists = $db->getRow("SHOW TABLES LIKE 'stock_issue_items'");
    if ($stockIssueTableExists) {
        addColumnIfMissing($db, 'stock_issue_items', 'uom_id', "INT(10) NULL DEFAULT NULL AFTER `qty`");
        addColumnIfMissing($db, 'stock_issue_items', 'qty_per_uom', "DECIMAL(18,6) NULL DEFAULT NULL");
        addColumnIfMissing($db, 'stock_issue_items', 'qty_base', "DECIMAL(18,6) NULL DEFAULT NULL");
    }
}

function addColumnIfMissing(Database $db, string $table, string $column, string $definition): void
{
    $exists = $db->getRow("SHOW COLUMNS FROM `{$table}` LIKE ?", [$column]);
    if (!$exists) {
        $db->insertRow("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}", []);
    }
}

/**
 * Resolves the base UOM name (item_master.unit_of_measure string) to a uom_id
 * in the item_uom table. If a matching row does not exist, it is auto-created.
 * Returns 0 when the name is empty / unresolvable.
 */
function resolveBaseUomIdFromString(Database $db, ?string $unitName): int
{
    $name = trim((string) $unitName);
    if ($name === '') {
        return 0;
    }
    $existing = $db->getRow('SELECT uom_id FROM item_uom WHERE uom_name = ? LIMIT 1', [$name]);
    if ($existing && (int) $existing['uom_id'] > 0) {
        return (int) $existing['uom_id'];
    }
    $db->insertRow('INSERT INTO item_uom (uom_name) VALUES (?)', [$name]);
    $row = $db->getRow('SELECT uom_id FROM item_uom WHERE uom_name = ? ORDER BY uom_id DESC LIMIT 1', [$name]);
    return (int) ($row['uom_id'] ?? 0);
}

/**
 * Returns the base UOM id for an item by reading item_master.unit_of_measure and
 * resolving it through resolveBaseUomIdFromString().
 */
function getBaseUomIdForItem(Database $db, int $itemId): int
{
    $product = $db->getRow('SELECT unit_of_measure FROM item_master WHERE item_id = ?', [$itemId]);
    if (!$product) {
        return 0;
    }
    return resolveBaseUomIdFromString($db, $product['unit_of_measure'] ?? '');
}

/**
 * Returns all available UOMs for an item, including the implicit base UOM.
 * Each row: ['uom_id', 'uom_name', 'qty_per_uom', 'is_default_purchase', 'is_default_sales', 'is_base'].
 */
function getItemUomList(Database $db, int $itemId): array
{
    ensureItemUomSchema($db);

    $baseUomId = getBaseUomIdForItem($db, $itemId);

    $rows = $db->getRows(
        'SELECT iuom.uom_id, iuom.qty_per_uom, iuom.is_default_purchase, iuom.is_default_sales, u.uom_name
         FROM item_unit_of_measure iuom
         LEFT JOIN item_uom u ON u.uom_id = iuom.uom_id
         WHERE iuom.item_id = ?
         ORDER BY iuom.is_default_purchase DESC, u.uom_name ASC',
        [$itemId]
    ) ?: [];

    $list = [];
    $seen = [];

    if ($baseUomId > 0) {
        $base = $db->getRow('SELECT uom_id, uom_name FROM item_uom WHERE uom_id = ?', [$baseUomId]);
        if ($base) {
            $list[] = [
                'uom_id' => (int) $base['uom_id'],
                'uom_name' => (string) $base['uom_name'],
                'qty_per_uom' => 1.0,
                'is_default_purchase' => 0,
                'is_default_sales' => 0,
                'is_base' => 1,
            ];
            $seen[(int) $base['uom_id']] = true;
        }
    }

    foreach ($rows as $row) {
        $uid = (int) $row['uom_id'];
        if (isset($seen[$uid])) {
            // already represented as base — update flags
            foreach ($list as &$entry) {
                if ($entry['uom_id'] === $uid) {
                    $entry['is_default_purchase'] = (int) $row['is_default_purchase'];
                    $entry['is_default_sales'] = (int) $row['is_default_sales'];
                }
            }
            unset($entry);
            continue;
        }
        $list[] = [
            'uom_id' => $uid,
            'uom_name' => (string) $row['uom_name'],
            'qty_per_uom' => (float) $row['qty_per_uom'],
            'is_default_purchase' => (int) $row['is_default_purchase'],
            'is_default_sales' => (int) $row['is_default_sales'],
            'is_base' => 0,
        ];
        $seen[$uid] = true;
    }

    return $list;
}

/**
 * Returns the default purchase UOM record for the product.
 * Falls back to the base UOM if no explicit default is set.
 */
function getDefaultPurchaseUom(Database $db, int $itemId): array
{
    $list = getItemUomList($db, $itemId);
    foreach ($list as $row) {
        if ($row['is_default_purchase']) {
            return $row;
        }
    }
    return $list[0] ?? [
        'uom_id' => 0,
        'uom_name' => '',
        'qty_per_uom' => 1.0,
        'is_default_purchase' => 0,
        'is_default_sales' => 0,
        'is_base' => 1,
    ];
}

/**
 * Returns the qty_per_uom for the given (item, uom) pair.
 * Returns 1.0 when the uom is the base UOM, or 0.0 when not allowed.
 */
function getItemUomConversion(Database $db, int $itemId, int $uomId): float
{
    $list = getItemUomList($db, $itemId);
    foreach ($list as $row) {
        if ($row['uom_id'] === $uomId) {
            return (float) $row['qty_per_uom'];
        }
    }
    return 0.0;
}

/**
 * Persists the alternative UOMs for a product.
 * $rows is an array of ['uom_id' => int, 'qty_per_uom' => float, 'is_default_purchase' => 0/1, 'is_default_sales' => 0/1].
 * The base UOM (item_master.item_uom) is filtered out automatically.
 */
function saveItemAlternativeUoms(Database $db, int $itemId, array $rows): void
{
    ensureItemUomSchema($db);

    $baseUomId = getBaseUomIdForItem($db, $itemId);

    $db->deleteRow('DELETE FROM item_unit_of_measure WHERE item_id = ?', [$itemId]);

    $now = date('Y-m-d H:i:s');
    $seen = [];
    $defaultPurchaseAssigned = false;
    $defaultSalesAssigned = false;

    foreach ($rows as $row) {
        $uomId = (int) ($row['uom_id'] ?? 0);
        $uomName = trim((string) ($row['uom_name'] ?? ''));
        $qtyPerUom = (float) ($row['qty_per_uom'] ?? 0);
        // If uom_id is missing but a name is provided, resolve / auto-create it
        if ($uomId <= 0 && $uomName !== '') {
            $uomId = resolveBaseUomIdFromString($db, $uomName);
        }
        if ($uomId <= 0 || $qtyPerUom <= 0) {
            continue;
        }
        if ($uomId === $baseUomId) {
            // base is implicit; ignore alt entries for base
            continue;
        }
        if (isset($seen[$uomId])) {
            continue;
        }
        $seen[$uomId] = true;

        $isDefaultPurchase = !empty($row['is_default_purchase']) && !$defaultPurchaseAssigned ? 1 : 0;
        $isDefaultSales = !empty($row['is_default_sales']) && !$defaultSalesAssigned ? 1 : 0;
        if ($isDefaultPurchase) {
            $defaultPurchaseAssigned = true;
        }
        if ($isDefaultSales) {
            $defaultSalesAssigned = true;
        }

        $db->insertRow(
            'INSERT INTO item_unit_of_measure (item_id, uom_id, qty_per_uom, is_default_purchase, is_default_sales, created_at) VALUES (?,?,?,?,?,?)',
            [$itemId, $uomId, $qtyPerUom, $isDefaultPurchase, $isDefaultSales, $now]
        );
    }
}

/**
 * Persists the "Additional UOM" multi-select on the product form as a CSV of
 * UOM names on item_master.additional_uoms.
 *
 * Also auto-creates an item_uom row for each name and syncs item_master.item_uom
 * from the base UOM string (unit_of_measure) so legacy joins on item_uom keep working.
 */
function saveProductAdditionalUoms(Database $db, int $itemId, array $names): void
{
    ensureItemUomSchema($db);

    // Normalise + dedupe names
    $clean = [];
    $seen = [];
    foreach ($names as $n) {
        $n = trim((string) $n);
        if ($n === '') { continue; }
        $key = strtolower($n);
        if (isset($seen[$key])) { continue; }
        $seen[$key] = true;
        // Auto-create item_uom row if missing
        resolveBaseUomIdFromString($db, $n);
        $clean[] = $n;
    }

    $csv = implode(',', $clean);
    $db->updateRow('UPDATE item_master SET additional_uoms = ? WHERE item_id = ?', [$csv, $itemId]);

    // Sync legacy item_master.item_uom column from the base unit_of_measure string
    $baseId = getBaseUomIdForItem($db, $itemId);
    if ($baseId > 0) {
        $db->updateRow('UPDATE item_master SET item_uom = ? WHERE item_id = ?', [$baseId, $itemId]);
    }
}

/**
 * Returns the list of additional UOM names selected for the product.
 */
function getProductAdditionalUomNames(Database $db, int $itemId): array
{
    $row = $db->getRow('SELECT additional_uoms FROM item_master WHERE item_id = ?', [$itemId]);
    $csv = trim((string) ($row['additional_uoms'] ?? ''));
    if ($csv === '') { return []; }
    $parts = array_map('trim', explode(',', $csv));
    return array_values(array_filter($parts, function ($v) { return $v !== ''; }));
}
