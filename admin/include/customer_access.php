<?php

if (!function_exists('canManageCustomerStatusAccess')) {
    function canManageCustomerStatusAccess()
    {
        return function_exists('isSuperAdmin') && isSuperAdmin();
    }
}

if (!function_exists('normalizeCustomerStatusFlags')) {
    function normalizeCustomerStatusFlags(array $submitted, array $currentValues = [])
    {
        if (canManageCustomerStatusAccess()) {
            return [
                'is_active' => isset($submitted['is_active']) ? 1 : 0,
                'locked' => isset($submitted['locked']) ? 1 : 0,
            ];
        }

        if (!empty($currentValues)) {
            return [
                'is_active' => (int) ($currentValues['is_active'] ?? 0),
                'locked' => (int) ($currentValues['locked'] ?? 0),
            ];
        }

        return [
            'is_active' => 0,
            'locked' => 0,
        ];
    }
}

if (!function_exists('getOrderEligibleCustomers')) {
    function getOrderEligibleCustomers(Database $db)
    {
        return $db->getRows(
            'SELECT customer_id, customer_name
             FROM customer
             WHERE COALESCE(is_active, 0) = 1
               AND COALESCE(locked, 0) = 0
             ORDER BY customer_name ASC'
        );
    }
}

if (!function_exists('getCustomerOrderEligibilityError')) {
    function getCustomerOrderEligibilityError(Database $db, $customerId, &$customer = null)
    {
        $customerId = (int) $customerId;
        if ($customerId <= 0) {
            return 'Customer is required';
        }

        $customer = $db->getRow(
            'SELECT customer_id, customer_name, is_active, locked, RepeatInterval, RepeatUnit
             FROM customer
             WHERE customer_id = ?
             LIMIT 1',
            [$customerId]
        );

        if (!$customer) {
            return 'Customer not found';
        }

        if ((int) ($customer['is_active'] ?? 0) !== 1) {
            return 'Customer must be active before using cart or standing orders';
        }

        if ((int) ($customer['locked'] ?? 0) === 1) {
            return 'Customer account is locked. Unlock it before using cart or standing orders';
        }

        return null;
    }
}
