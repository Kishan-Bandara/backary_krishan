<?php
if (!function_exists('startsWith')) {
    function startsWith($haystack, $needle) {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

if (!function_exists('initUserPermissions')) {
    function initUserPermissions($userId, $userLevel) {
        if (isSuperAdmin()) {
            $_SESSION['permissions'] = ['*'];
            $_SESSION['permissions_loaded'] = true;
            return;
        }

        if (!empty($_SESSION['permissions_loaded'])) {
            return;
        }

        $permissions = [];
        try {
            $db = new Database();
            $rows = $db->getRows(
                'SELECT p.permission_key FROM permissions p INNER JOIN role_permissions rp ON rp.permission_id = p.permission_id WHERE rp.user_level_id = ?',
                [$userLevel]
            );

            foreach ($rows as $row) {
                if (!empty($row['permission_key'])) {
                    $permissions[] = $row['permission_key'];
                }
            }
        } catch (Exception $e) {
            $permissions = [];
        }

        $_SESSION['permissions'] = array_values(array_unique($permissions));
        $_SESSION['permissions_loaded'] = true;
    }
}

if (!function_exists('getGrantedPermissions')) {
    function getGrantedPermissions() {
        return $_SESSION['permissions'] ?? [];
    }
}

if (!function_exists('ensurePermissionExists')) {
    function ensurePermissionExists($key, $name, $description = '') {
        static $ensured = [];

        if (isset($ensured[$key])) {
            return;
        }

        try {
            $db = new Database();
            $existing = $db->getRow('SELECT permission_id FROM permissions WHERE permission_key = ? LIMIT 1', [$key]);
            if (!$existing) {
                $db->insertRow(
                    'INSERT INTO permissions (permission_key, permission_name, description) VALUES (?, ?, ?)',
                    [$key, $name, $description]
                );
            }
            $ensured[$key] = true;
        } catch (Exception $e) {
            $ensured[$key] = false;
        }
    }
}

if (!function_exists('ensureCrmPermissions')) {
    function ensureCrmPermissions() {
        ensurePermissionExists('crm.view', 'View CRM', 'Access CRM menu and dashboard');
        ensurePermissionExists('crm.person.create', 'Create Person Master', 'Add CRM contact persons');
        ensurePermissionExists('crm.person.view', 'View Person Master', 'Manage CRM contact persons');
        ensurePermissionExists('crm.company.create', 'Create Company Master', 'Add CRM companies');
        ensurePermissionExists('crm.company.view', 'View Company Master', 'Manage CRM companies');
    }
}

if (!function_exists('ensureUserAdminPermissions')) {
    function ensureUserAdminPermissions() {
        ensurePermissionExists('users.create', 'Create Backend Users', 'Add new backend login users');
        ensurePermissionExists('users.view', 'View Backend Users', 'List backend login users');
        ensurePermissionExists('users.edit', 'Edit Backend Users', 'Update backend login users');
        ensurePermissionExists('users.delete', 'Delete Backend Users', 'Remove backend login users');
    }
}

ensureCrmPermissions();
ensureUserAdminPermissions();

if (!function_exists('permissionMatches')) {
    function permissionMatches($required, $granted) {
        if ($granted === '*') {
            return true;
        }
        if ($granted === $required) {
            return true;
        }
        if (substr($granted, -2) === '.*') {
            $prefix = substr($granted, 0, -2);
            return $required === $prefix || startsWith($required, $prefix . '.');
        }
        return false;
    }
}

if (!function_exists('hasPermission')) {
    function hasPermission($permission) {
        if (isSuperAdmin()) {
            return true;
        }
        $granted = getGrantedPermissions();
        foreach ($granted as $perm) {
            if (permissionMatches($permission, $perm)) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('hasAnyPermission')) {
    function hasAnyPermission(array $permissions) {
        foreach ($permissions as $permission) {
            if (hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('denyAccess')) {
    function denyAccess() {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Access denied.']);
            exit;
        }

        echo "<script type='text/javascript'>window.location.href = 'access_denied.php';</script>";
        exit;
    }
}

if (!function_exists('requirePermission')) {
    function requirePermission($permission) {
        if (!hasPermission($permission)) {
            denyAccess();
        }
    }
}

if (!function_exists('getPagePermissionMap')) {
    function getPagePermissionMap() {
        return [
            'index.php' => 'dashboard.view',
            'add-supplier.php' => 'purchase.supplier.create',
            'manage-supplier.php' => 'purchase.supplier.view',
            'purchase-order-create.php' => 'purchase.purchase.create',
            'purchase-order-list.php' => 'purchase.purchase.view',
            'add-purchase.php' => 'purchase.purchase.add',
            'purchase-history.php' => 'purchase.purchase.history',
            'purchase-return-create.php' => 'purchase.return.create',
            'manage-purchase-returns.php' => 'purchase.return.view',
            'purchase-return-note.php' => 'purchase.return.view',
            'stock-transfer-create.php' => 'stock.transfer.create',
            'stock-transfer-list.php' => 'stock.transfer.view',
            'stock-issue-create.php' => 'stock.issue.create',
            'stock-issue-list.php' => 'stock.issue.view',
            'POS.php' => 'orders.create',
            'manage-orders.php' => 'orders.view',
            'add-product.php' => 'product.create',
            'manage-product.php' => 'product.view',
            'product_price_mapping.php' => 'product.price_map',
            'standing-order.php' => 'product.standing_orders',
            'add-group.php' => 'item_master.group.create',
            'add-type.php' => 'item_master.type.create',
            'add-category.php' => 'item_master.category.create',
            'price_types.php' => 'item_master.price_types',
            'add-location.php' => 'warehouse.create',
            'manage-locations.php' => 'warehouse.view',
            'add-customer.php' => 'customer.create',
            'manage-customer.php' => 'customer.view',
            'price_type_customer_mapping.php' => 'customer.price_map',
            'crm.php' => 'crm.view',
            'crm-list.php' => 'crm.view',
            'crm-masters.php' => 'crm.view',
            'crm-opportunity.php' => 'crm.view',
            'crm-opportunity-update.php' => 'crm.view',
            'add-person.php' => 'crm.person.create',
            'manage-person.php' => 'crm.person.view',
            'edit-person.php' => 'crm.person.view',
            'add-company.php' => 'crm.company.create',
            'manage-company.php' => 'crm.company.view',
            'edit-company.php' => 'crm.company.view',
            'add-user.php' => 'users.create',
            'manage-user.php' => 'users.view',
            'edit-user.php' => 'users.edit',
            'manage-permissions.php' => 'settings.permissions',
            'manage-settings.php' => 'settings.permissions',
            'payment_terms.php' => 'settings.permissions',
            'invoice-settings.php' => 'settings.permissions',
            'smtp-settings.php' => 'settings.permissions'
        ];
    }
}

if (!function_exists('requirePagePermission')) {
    function requirePagePermission() {
        $map = getPagePermissionMap();
        $page = basename($_SERVER['SCRIPT_NAME']);
        if (isset($map[$page])) {
            requirePermission($map[$page]);
        }
    }
}
