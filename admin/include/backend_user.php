<?php
if (!function_exists('backendUserEscape')) {
    function backendUserEscape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('backendUserTrim')) {
    function backendUserTrim($value)
    {
        return trim((string) $value);
    }
}

if (!function_exists('backendUserFetchRoles')) {
    function backendUserFetchRoles(Database $db)
    {
        return $db->getRows('SELECT user_level_id, user_level_name FROM user_levels ORDER BY user_level_name ASC, user_level_id ASC');
    }
}

if (!function_exists('backendUserFetchLocations')) {
    function backendUserFetchLocations(Database $db)
    {
        return $db->getRows('SELECT id, location_code, name FROM location_master ORDER BY name ASC, id ASC');
    }
}

if (!function_exists('backendUserFetchRoleMap')) {
    function backendUserFetchRoleMap(array $roles)
    {
        $map = [];
        foreach ($roles as $role) {
            $map[(int) $role['user_level_id']] = $role;
        }
        return $map;
    }
}

if (!function_exists('backendUserFetchLocationMap')) {
    function backendUserFetchLocationMap(array $locations)
    {
        $map = [];
        foreach ($locations as $location) {
            $map[(int) $location['id']] = $location;
        }
        return $map;
    }
}

if (!function_exists('backendUserExists')) {
    function backendUserExists(Database $db, $username, $excludeUserId = 0)
    {
        $sql = 'SELECT userid FROM users WHERE LOWER(username) = LOWER(?)';
        $params = [backendUserTrim($username)];

        if ((int) $excludeUserId > 0) {
            $sql .= ' AND userid <> ?';
            $params[] = (int) $excludeUserId;
        }

        $sql .= ' LIMIT 1';
        return (bool) $db->getRow($sql, $params);
    }
}

if (!function_exists('backendUserFetchUsers')) {
    function backendUserFetchUsers(Database $db)
    {
        return $db->getRows(
            'SELECT u.*, ul.user_level_name, lm.name AS location_name, lm.location_code
             FROM users u
             LEFT JOIN user_levels ul ON ul.user_level_id = u.user_level
             LEFT JOIN location_master lm ON lm.id = u.location_status
             ORDER BY u.userid DESC'
        );
    }
}

if (!function_exists('backendUserFetchUser')) {
    function backendUserFetchUser(Database $db, $userId)
    {
        return $db->getRow('SELECT * FROM users WHERE userid = ? LIMIT 1', [(int) $userId]);
    }
}

if (!function_exists('backendUserDefaultFormData')) {
    function backendUserDefaultFormData()
    {
        return [
            'username' => '',
            'password' => '',
            'confirm_password' => '',
            'first_name' => '',
            'last_name' => '',
            'email' => '',
            'user_level' => '',
            'activated' => 'Y',
            'locked' => 'N',
            'location_status' => ''
        ];
    }
}

if (!function_exists('backendUserBuildFormData')) {
    function backendUserBuildFormData(array $source, array $defaults = [])
    {
        $data = array_merge(backendUserDefaultFormData(), $defaults);

        foreach (array_keys($data) as $key) {
            if (array_key_exists($key, $source)) {
                $data[$key] = backendUserTrim($source[$key]);
            }
        }

        return $data;
    }
}

if (!function_exists('backendUserValidate')) {
    function backendUserValidate(array $formData, array $roleMap, array $locationMap, $requirePassword = true)
    {
        $errors = [];

        if ($formData['username'] === '') {
            $errors[] = 'Username is required.';
        } elseif (!preg_match('/^[A-Za-z0-9._@-]{3,50}$/', $formData['username'])) {
            $errors[] = 'Username may contain letters, numbers, dot, underscore, dash, and @ only.';
        }

        if ($requirePassword) {
            if ($formData['password'] === '') {
                $errors[] = 'Password is required.';
            }
            if ($formData['confirm_password'] === '') {
                $errors[] = 'Confirm password is required.';
            }
        }

        if ($formData['password'] !== '' || $formData['confirm_password'] !== '') {
            if (strlen($formData['password']) < 4) {
                $errors[] = 'Password must be at least 4 characters long.';
            }
            if ($formData['password'] !== $formData['confirm_password']) {
                $errors[] = 'Password and confirm password must match.';
            }
        }

        if ($formData['first_name'] === '') {
            $errors[] = 'First name is required.';
        }

        $roleId = (int) $formData['user_level'];
        if ($roleId <= 0 || !isset($roleMap[$roleId])) {
            $errors[] = 'Please select a valid user role.';
        }

        $locationId = (int) $formData['location_status'];
        if ($locationId <= 0 || !isset($locationMap[$locationId])) {
            $errors[] = 'Please select a valid warehouse.';
        }

        if (!in_array($formData['activated'], ['Y', 'N'], true)) {
            $errors[] = 'Invalid activation status.';
        }

        if (!in_array($formData['locked'], ['Y', 'N'], true)) {
            $errors[] = 'Invalid lock status.';
        }

        if ($formData['email'] !== '' && !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        return $errors;
    }
}

if (!function_exists('backendUserRoleName')) {
    function backendUserRoleName(array $user)
    {
        return (string) ($user['user_level_name'] ?? '');
    }
}

if (!function_exists('backendUserLocationName')) {
    function backendUserLocationName(array $user)
    {
        $code = backendUserTrim($user['location_code'] ?? '');
        $name = backendUserTrim($user['location_name'] ?? '');

        if ($code !== '' && $name !== '') {
            return $code . ' - ' . $name;
        }

        return $name !== '' ? $name : $code;
    }
}
?>