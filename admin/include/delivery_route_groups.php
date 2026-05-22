<?php
/**
 * Delivery Route Groups helper.
 *
 * Provides schema bootstrap and lookups for the delivery_route_groups,
 * delivery_route_group_map and customer.delivery_route_group_id fields.
 *
 * A Delivery Route Group is a many-to-many tag that can be attached to
 * any number of delivery routes. A customer can be assigned to a single
 * delivery route group; if assigned, only routes in that group should be
 * offered to the customer (shipping address selection, cart order, etc.).
 */

if (!function_exists('ensureDeliveryRouteGroupSchema')) {
    function ensureDeliveryRouteGroupSchema($db = null)
    {
        if ($db === null) {
            try {
                $db = new Database();
            } catch (Exception $e) {
                return;
            }
        }

        try {
            $db->getRows("CREATE TABLE IF NOT EXISTS `delivery_route_groups` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `description` VARCHAR(255) DEFAULT NULL,
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `name` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $db->getRows("CREATE TABLE IF NOT EXISTS `delivery_route_group_map` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `group_id` INT(11) NOT NULL,
                `route_id` INT(11) NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `group_route` (`group_id`,`route_id`),
                KEY `route_id` (`route_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // Ensure customer.delivery_route_group_id exists (legacy; no longer used by the UI but kept to avoid breakage)
            $col = $db->getRow("SHOW COLUMNS FROM customer LIKE 'delivery_route_group_id'");
            if (!$col) {
                $db->getRows("ALTER TABLE `customer` ADD COLUMN `delivery_route_group_id` INT(11) DEFAULT NULL");
            }

            // Ensure customer_shipping_address.delivery_route_group_id exists
            try {
                $col2 = $db->getRow("SHOW COLUMNS FROM customer_shipping_address LIKE 'delivery_route_group_id'");
                if (!$col2) {
                    $db->getRows("ALTER TABLE `customer_shipping_address` ADD COLUMN `delivery_route_group_id` INT(11) DEFAULT NULL");
                }
            } catch (Exception $e) {
                // table may not exist on first install
            }
        } catch (Exception $e) {
            // ignore – schema may already exist or DB unavailable
        }
    }
}

if (!function_exists('getDeliveryRouteGroups')) {
    function getDeliveryRouteGroups($activeOnly = true)
    {
        try {
            $db = new Database();
            ensureDeliveryRouteGroupSchema($db);
            $sql = 'SELECT id, name, description, is_active FROM delivery_route_groups';
            if ($activeOnly) {
                $sql .= ' WHERE is_active = 1';
            }
            $sql .= ' ORDER BY name ASC';
            return $db->getRows($sql) ?: [];
        } catch (Exception $e) {
            return [];
        }
    }
}

if (!function_exists('getDeliveryRouteGroupIdsForRoute')) {
    function getDeliveryRouteGroupIdsForRoute($routeId)
    {
        $routeId = (int) $routeId;
        if ($routeId <= 0) {
            return [];
        }
        try {
            $db = new Database();
            ensureDeliveryRouteGroupSchema($db);
            $rows = $db->getRows('SELECT group_id FROM delivery_route_group_map WHERE route_id = ?', [$routeId]) ?: [];
            $ids = [];
            foreach ($rows as $r) {
                $ids[] = (int) $r['group_id'];
            }
            return $ids;
        } catch (Exception $e) {
            return [];
        }
    }
}

if (!function_exists('saveDeliveryRouteGroupsForRoute')) {
    function saveDeliveryRouteGroupsForRoute($routeId, array $groupIds)
    {
        $routeId = (int) $routeId;
        if ($routeId <= 0) {
            return;
        }
        try {
            $db = new Database();
            ensureDeliveryRouteGroupSchema($db);
            $db->updateRow('DELETE FROM delivery_route_group_map WHERE route_id = ?', [$routeId]);
            $clean = [];
            foreach ($groupIds as $gid) {
                $gid = (int) $gid;
                if ($gid > 0 && !in_array($gid, $clean, true)) {
                    $clean[] = $gid;
                }
            }
            foreach ($clean as $gid) {
                $db->insertRow('INSERT INTO delivery_route_group_map (group_id, route_id) VALUES (?, ?)', [$gid, $routeId]);
            }
        } catch (Exception $e) {
            // ignore
        }
    }
}

if (!function_exists('getDeliveryRouteIdsForGroup')) {
    function getDeliveryRouteIdsForGroup($groupId)
    {
        $groupId = (int) $groupId;
        if ($groupId <= 0) {
            return [];
        }
        try {
            $db = new Database();
            ensureDeliveryRouteGroupSchema($db);
            $rows = $db->getRows('SELECT route_id FROM delivery_route_group_map WHERE group_id = ?', [$groupId]) ?: [];
            $ids = [];
            foreach ($rows as $r) {
                $ids[] = (int) $r['route_id'];
            }
            return $ids;
        } catch (Exception $e) {
            return [];
        }
    }
}

if (!function_exists('getCustomerDeliveryRouteGroupId')) {
    function getCustomerDeliveryRouteGroupId($customerId)
    {
        $customerId = (int) $customerId;
        if ($customerId <= 0) {
            return null;
        }
        try {
            $db = new Database();
            ensureDeliveryRouteGroupSchema($db);
            $row = $db->getRow('SELECT delivery_route_group_id FROM customer WHERE customer_id = ? LIMIT 1', [$customerId]);
            if ($row && $row['delivery_route_group_id'] !== null && $row['delivery_route_group_id'] !== '') {
                return (int) $row['delivery_route_group_id'];
            }
        } catch (Exception $e) {
            // ignore
        }
        return null;
    }
}

if (!function_exists('getDeliveryRoutesForCustomer')) {
    /**
     * Return active delivery routes available to the given customer.
     * If the customer is not assigned to a group, all active routes are returned.
     * If the customer is assigned to a group with no mapped routes, the
     * full active route list is returned (so the UI is never empty).
     */
    function getDeliveryRoutesForCustomer($customerId)
    {
        try {
            $db = new Database();
            ensureDeliveryRouteGroupSchema($db);
            $groupId = getCustomerDeliveryRouteGroupId($customerId);
            if ($groupId === null) {
                return $db->getRows('SELECT id, route_name FROM delivery_route_master WHERE is_active = 1 ORDER BY route_name ASC') ?: [];
            }
            $routes = $db->getRows(
                'SELECT drm.id, drm.route_name
                 FROM delivery_route_master drm
                 INNER JOIN delivery_route_group_map drgm ON drgm.route_id = drm.id
                 WHERE drm.is_active = 1 AND drgm.group_id = ?
                 ORDER BY drm.route_name ASC',
                [$groupId]
            ) ?: [];
            if (empty($routes)) {
                return $db->getRows('SELECT id, route_name FROM delivery_route_master WHERE is_active = 1 ORDER BY route_name ASC') ?: [];
            }
            return $routes;
        } catch (Exception $e) {
            return [];
        }
    }
}

if (!function_exists('getDeliveryRoutesForGroup')) {
    /**
     * Return active routes mapped to the given group.
     * If $groupId is empty/null, returns all active routes.
     * If group has no mapped routes, returns all active routes (fallback so UI is never empty).
     */
    function getDeliveryRoutesForGroup($groupId)
    {
        try {
            $db = new Database();
            ensureDeliveryRouteGroupSchema($db);
            $groupId = (int) $groupId;
            if ($groupId <= 0) {
                return $db->getRows('SELECT id, route_name FROM delivery_route_master WHERE is_active = 1 ORDER BY route_name ASC') ?: [];
            }
            $routes = $db->getRows(
                'SELECT drm.id, drm.route_name
                 FROM delivery_route_master drm
                 INNER JOIN delivery_route_group_map drgm ON drgm.route_id = drm.id
                 WHERE drm.is_active = 1 AND drgm.group_id = ?
                 ORDER BY drm.route_name ASC',
                [$groupId]
            ) ?: [];
            if (empty($routes)) {
                return $db->getRows('SELECT id, route_name FROM delivery_route_master WHERE is_active = 1 ORDER BY route_name ASC') ?: [];
            }
            return $routes;
        } catch (Exception $e) {
            return [];
        }
    }
}

if (!function_exists('getAllActiveRoutesWithGroups')) {
    /**
     * Return all active delivery routes with the list of group IDs each is mapped to.
     * Each item: ['id' => int, 'route_name' => string, 'group_ids' => int[]]
     */
    function getAllActiveRoutesWithGroups()
    {
        try {
            $db = new Database();
            ensureDeliveryRouteGroupSchema($db);
            $routes = $db->getRows('SELECT id, route_name FROM delivery_route_master WHERE is_active = 1 ORDER BY route_name ASC') ?: [];
            if (empty($routes)) {
                return [];
            }
            $maps = $db->getRows('SELECT route_id, group_id FROM delivery_route_group_map') ?: [];
            $byRoute = [];
            foreach ($maps as $m) {
                $byRoute[(int)$m['route_id']][] = (int)$m['group_id'];
            }
            foreach ($routes as &$r) {
                $rid = (int)$r['id'];
                $r['group_ids'] = $byRoute[$rid] ?? [];
            }
            unset($r);
            return $routes;
        } catch (Exception $e) {
            return [];
        }
    }
}

if (!function_exists('getRouteGroupNamesForRoute')) {
    function getRouteGroupNamesForRoute($routeId)
    {
        $routeId = (int) $routeId;
        if ($routeId <= 0) {
            return [];
        }
        try {
            $db = new Database();
            ensureDeliveryRouteGroupSchema($db);
            $rows = $db->getRows(
                'SELECT g.name FROM delivery_route_groups g
                 INNER JOIN delivery_route_group_map m ON m.group_id = g.id
                 WHERE m.route_id = ? ORDER BY g.name ASC',
                [$routeId]
            ) ?: [];
            $names = [];
            foreach ($rows as $r) {
                $names[] = $r['name'];
            }
            return $names;
        } catch (Exception $e) {
            return [];
        }
    }
}
