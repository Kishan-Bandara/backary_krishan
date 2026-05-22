<?php
/**
 * Delivery Rules helper.
 *
 * Provides schema bootstrap and lookups for global Delivery Rules settings,
 * named delivery rules and their tiered fee tables.
 *
 * Schema:
 *   delivery_rule_settings (singleton row, id = 1)
 *     - apply_to ENUM('net','gross')
 *     - weekly_avg_free_delivery DECIMAL(12,2)
 *     - standing_order_daily_avg_min DECIMAL(12,2)
 *     - min_cart_order DECIMAL(12,2)
 *   delivery_rules (id, name UNIQUE, sort_order, is_active)
 *   delivery_rule_tiers (id, rule_id, invoice_larger_than, price, sort_order)
 */

if (!function_exists('ensureDeliveryRulesSchema')) {
    function ensureDeliveryRulesSchema($db = null)
    {
        if ($db === null) {
            try {
                $db = new Database();
            } catch (Exception $e) {
                return;
            }
        }

        try {
            $db->getRows("CREATE TABLE IF NOT EXISTS `delivery_rule_settings` (
                `id` INT(11) NOT NULL DEFAULT 1,
                `apply_to` ENUM('net','gross') NOT NULL DEFAULT 'gross',
                `weekly_avg_free_delivery` DECIMAL(12,2) DEFAULT NULL,
                `standing_order_daily_avg_min` DECIMAL(12,2) DEFAULT NULL,
                `min_cart_order` DECIMAL(12,2) DEFAULT NULL,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $db->getRows("CREATE TABLE IF NOT EXISTS `delivery_rules` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(150) NOT NULL,
                `sort_order` INT(11) NOT NULL DEFAULT 0,
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `name` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $db->getRows("CREATE TABLE IF NOT EXISTS `delivery_rule_tiers` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `rule_id` INT(11) NOT NULL,
                `invoice_larger_than` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `price` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `sort_order` INT(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                KEY `rule_id` (`rule_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // Ensure singleton settings row exists
            $existing = $db->getRow('SELECT id FROM delivery_rule_settings WHERE id = 1');
            if (!$existing) {
                $db->insertRow('INSERT INTO delivery_rule_settings (id, apply_to) VALUES (1, ?)', ['gross']);
            }

            // Per-shipping-address delivery rule + overrides
            try {
                $cols = [
                    'delivery_rule_id' => "INT(11) DEFAULT NULL",
                    'so_daily_average' => "DECIMAL(12,2) DEFAULT NULL",
                    'min_cart_order_override' => "DECIMAL(12,2) DEFAULT NULL",
                    'weekly_avg_free_delivery_override' => "DECIMAL(12,2) DEFAULT NULL",
                ];
                foreach ($cols as $colName => $colDef) {
                    $col = $db->getRow("SHOW COLUMNS FROM customer_shipping_address LIKE '" . $colName . "'");
                    if (!$col) {
                        $db->getRows("ALTER TABLE `customer_shipping_address` ADD COLUMN `" . $colName . "` " . $colDef);
                    }
                }
            } catch (Exception $e) {
                // table may not exist yet
            }
        } catch (Exception $e) {
            // ignore – schema may already exist
        }
    }
}

if (!function_exists('getDeliveryRuleSettings')) {
    function getDeliveryRuleSettings()
    {
        try {
            $db = new Database();
            ensureDeliveryRulesSchema($db);
            $row = $db->getRow('SELECT * FROM delivery_rule_settings WHERE id = 1 LIMIT 1');
            if (!$row) {
                return [
                    'apply_to' => 'gross',
                    'weekly_avg_free_delivery' => null,
                    'standing_order_daily_avg_min' => null,
                    'min_cart_order' => null,
                ];
            }
            return $row;
        } catch (Exception $e) {
            return [
                'apply_to' => 'gross',
                'weekly_avg_free_delivery' => null,
                'standing_order_daily_avg_min' => null,
                'min_cart_order' => null,
            ];
        }
    }
}

if (!function_exists('saveDeliveryRuleSettings')) {
    function saveDeliveryRuleSettings(array $data)
    {
        try {
            $db = new Database();
            ensureDeliveryRulesSchema($db);
            $applyTo = ($data['apply_to'] ?? 'gross') === 'net' ? 'net' : 'gross';
            $weekly = ($data['weekly_avg_free_delivery'] ?? '') === '' ? null : (float) $data['weekly_avg_free_delivery'];
            $std = ($data['standing_order_daily_avg_min'] ?? '') === '' ? null : (float) $data['standing_order_daily_avg_min'];
            $minCart = ($data['min_cart_order'] ?? '') === '' ? null : (float) $data['min_cart_order'];
            $db->updateRow(
                'UPDATE delivery_rule_settings SET apply_to = ?, weekly_avg_free_delivery = ?, standing_order_daily_avg_min = ?, min_cart_order = ? WHERE id = 1',
                [$applyTo, $weekly, $std, $minCart]
            );
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

if (!function_exists('getDeliveryRules')) {
    function getDeliveryRules($activeOnly = false)
    {
        try {
            $db = new Database();
            ensureDeliveryRulesSchema($db);
            $sql = 'SELECT id, name, sort_order, is_active FROM delivery_rules';
            if ($activeOnly) {
                $sql .= ' WHERE is_active = 1';
            }
            $sql .= ' ORDER BY sort_order ASC, name ASC';
            return $db->getRows($sql) ?: [];
        } catch (Exception $e) {
            return [];
        }
    }
}

if (!function_exists('getDeliveryRuleTiers')) {
    function getDeliveryRuleTiers($ruleId)
    {
        $ruleId = (int) $ruleId;
        if ($ruleId <= 0) {
            return [];
        }
        try {
            $db = new Database();
            ensureDeliveryRulesSchema($db);
            return $db->getRows(
                'SELECT id, invoice_larger_than, price, sort_order FROM delivery_rule_tiers WHERE rule_id = ? ORDER BY invoice_larger_than ASC, sort_order ASC',
                [$ruleId]
            ) ?: [];
        } catch (Exception $e) {
            return [];
        }
    }
}

if (!function_exists('getDeliveryRulesWithTiers')) {
    function getDeliveryRulesWithTiers($activeOnly = false)
    {
        $rules = getDeliveryRules($activeOnly);
        foreach ($rules as &$rule) {
            $rule['tiers'] = getDeliveryRuleTiers((int) $rule['id']);
        }
        unset($rule);
        return $rules;
    }
}

if (!function_exists('saveDeliveryRule')) {
    /**
     * Insert or update a single rule with its tiers.
     * Returns the rule id, or 0 on failure.
     * $tiers: array of ['invoice_larger_than'=>x, 'price'=>y]
     */
    function saveDeliveryRule($ruleId, $name, array $tiers, $isActive = 1, $sortOrder = 0)
    {
        $name = trim((string) $name);
        if ($name === '') {
            return 0;
        }
        try {
            $db = new Database();
            ensureDeliveryRulesSchema($db);
            $ruleId = (int) $ruleId;
            $isActive = $isActive ? 1 : 0;
            $sortOrder = (int) $sortOrder;

            if ($ruleId > 0) {
                $db->updateRow(
                    'UPDATE delivery_rules SET name = ?, is_active = ?, sort_order = ? WHERE id = ?',
                    [$name, $isActive, $sortOrder, $ruleId]
                );
            } else {
                $db->insertRow(
                    'INSERT INTO delivery_rules (name, is_active, sort_order) VALUES (?, ?, ?)',
                    [$name, $isActive, $sortOrder]
                );
                $row = $db->getRow('SELECT LAST_INSERT_ID() AS id');
                $ruleId = (int) ($row['id'] ?? 0);
            }

            if ($ruleId <= 0) {
                return 0;
            }

            // Replace tiers
            $db->updateRow('DELETE FROM delivery_rule_tiers WHERE rule_id = ?', [$ruleId]);
            $sort = 0;
            foreach ($tiers as $t) {
                $threshold = isset($t['invoice_larger_than']) ? (float) $t['invoice_larger_than'] : 0.0;
                $price = isset($t['price']) ? (float) $t['price'] : 0.0;
                $db->insertRow(
                    'INSERT INTO delivery_rule_tiers (rule_id, invoice_larger_than, price, sort_order) VALUES (?, ?, ?, ?)',
                    [$ruleId, $threshold, $price, $sort]
                );
                $sort++;
            }
            return $ruleId;
        } catch (Exception $e) {
            return 0;
        }
    }
}

if (!function_exists('deleteDeliveryRule')) {
    function deleteDeliveryRule($ruleId)
    {
        $ruleId = (int) $ruleId;
        if ($ruleId <= 0) {
            return false;
        }
        try {
            $db = new Database();
            ensureDeliveryRulesSchema($db);
            $db->updateRow('DELETE FROM delivery_rule_tiers WHERE rule_id = ?', [$ruleId]);
            $db->deleteRow('DELETE FROM delivery_rules WHERE id = ?', [$ruleId]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

if (!function_exists('calculateDeliveryFeeForRule')) {
    /**
     * Given a rule id and an order total, return the matching tier price
     * (highest tier whose invoice_larger_than <= $orderTotal). Returns null
     * if no tier matches.
     */
    function calculateDeliveryFeeForRule($ruleId, $orderTotal)
    {
        $tiers = getDeliveryRuleTiers($ruleId);
        if (empty($tiers)) {
            return null;
        }
        $best = null;
        $bestThreshold = -1;
        foreach ($tiers as $t) {
            $threshold = (float) $t['invoice_larger_than'];
            if ($orderTotal >= $threshold && $threshold >= $bestThreshold) {
                $bestThreshold = $threshold;
                $best = (float) $t['price'];
            }
        }
        return $best;
    }
}
