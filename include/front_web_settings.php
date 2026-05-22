<?php

if (!function_exists('mergeSettingsWithDefaults')) {
    function mergeSettingsWithDefaults($defaults, $row)
    {
        if (!is_array($row)) {
            return $defaults;
        }

        foreach ($row as $key => $value) {
            if ($value !== null) {
                $defaults[$key] = $value;
            }
        }

        return $defaults;
    }
}

if (!function_exists('generalSettingsDefaults')) {
    function generalSettingsDefaults()
    {
        return array(
            'SiteName' => 'Bakery Shop',
            'logo' => '',
            'footerLogo' => '',
            'favIcon' => '',
            'address' => '',
            'maintainMode' => 0,
            'system_email' => '',
            'contactUs' => ''
        );
    }
}

if (!function_exists('frontWebSettingsDefaults')) {
    function frontWebSettingsDefaults()
    {
        return array(
            'id' => 1,
            'header_notice' => 'Welcome to Our Bakery Shop - Free Shipping on orders above $50',
            'hero_badge' => 'Fresh Daily',
            'hero_title' => 'Quality Bakery Products',
            'hero_button_label' => 'Shop Now',
            'hero_button_link' => 'search.php',
            'hero_image' => '',
            'banner_one_image' => '',
            'banner_one_badge' => 'Fresh Daily',
            'banner_one_title' => 'Best Quality Products',
            'banner_one_button_label' => 'Shop Now',
            'banner_one_button_link' => 'search.php',
            'banner_two_image' => '',
            'banner_two_badge' => 'Hot & Spicy',
            'banner_two_title' => 'Freshly Baked Pastry',
            'banner_two_button_label' => 'Shop Now',
            'banner_two_button_link' => 'search.php',
            'promo_image' => '',
            'promo_badge' => 'Fresh Everyday',
            'promo_title' => 'Best Quality Bakery Products',
            'promo_description' => "Handcrafted with love using the finest ingredients.\nOrder online and get delivered fresh to your doorstep.",
            'promo_button_label' => 'Shop Now',
            'promo_button_link' => 'search.php'
        );
    }
}

if (!function_exists('getGeneralSettings')) {
    function getGeneralSettings($db = null, $forceRefresh = false)
    {
        static $settingsCache = null;

        if (!$forceRefresh && is_array($settingsCache)) {
            return $settingsCache;
        }

        $settingsCache = generalSettingsDefaults();
        $ownsDb = false;

        if ($db === null) {
            $db = new Database();
            $ownsDb = true;
        }

        try {
            $row = $db->getRow('SELECT * FROM general_settings LIMIT 1');
            $settingsCache = mergeSettingsWithDefaults($settingsCache, $row);
            $settingsCache = normalizeGeneralSettingsAssets($settingsCache);
        } catch (Exception $exception) {
            $settingsCache = generalSettingsDefaults();
        }

        if ($ownsDb && method_exists($db, 'Disconnect')) {
            $db->Disconnect();
        }

        return $settingsCache;
    }
}

if (!function_exists('getFrontWebSettings')) {
    function getFrontWebSettings($db = null, $forceRefresh = false)
    {
        static $settingsCache = null;

        if (!$forceRefresh && is_array($settingsCache)) {
            return $settingsCache;
        }

        $settingsCache = frontWebSettingsDefaults();
        $ownsDb = false;

        if ($db === null) {
            $db = new Database();
            $ownsDb = true;
        }

        try {
            $row = $db->getRow('SELECT * FROM front_web_settings WHERE id = 1');
            $settingsCache = mergeSettingsWithDefaults($settingsCache, $row);
            $settingsCache = normalizeFrontWebSettings($settingsCache);
        } catch (Exception $exception) {
            $settingsCache = frontWebSettingsDefaults();
        }

        if ($ownsDb && method_exists($db, 'Disconnect')) {
            $db->Disconnect();
        }

        return $settingsCache;
    }
}

if (!function_exists('ensureFrontWebSettingsTable')) {
    function ensureFrontWebSettingsTable($db)
    {
        $db->updateRow('CREATE TABLE IF NOT EXISTS front_web_settings (
            id INT NOT NULL,
            header_notice VARCHAR(255) DEFAULT NULL,
            hero_badge VARCHAR(255) DEFAULT NULL,
            hero_title VARCHAR(255) DEFAULT NULL,
            hero_button_label VARCHAR(100) DEFAULT NULL,
            hero_button_link VARCHAR(500) DEFAULT NULL,
            hero_image VARCHAR(500) DEFAULT NULL,
            banner_one_image VARCHAR(500) DEFAULT NULL,
            banner_one_badge VARCHAR(255) DEFAULT NULL,
            banner_one_title VARCHAR(255) DEFAULT NULL,
            banner_one_button_label VARCHAR(100) DEFAULT NULL,
            banner_one_button_link VARCHAR(500) DEFAULT NULL,
            banner_two_image VARCHAR(500) DEFAULT NULL,
            banner_two_badge VARCHAR(255) DEFAULT NULL,
            banner_two_title VARCHAR(255) DEFAULT NULL,
            banner_two_button_label VARCHAR(100) DEFAULT NULL,
            banner_two_button_link VARCHAR(500) DEFAULT NULL,
            promo_image VARCHAR(500) DEFAULT NULL,
            promo_badge VARCHAR(255) DEFAULT NULL,
            promo_title VARCHAR(255) DEFAULT NULL,
            promo_description TEXT DEFAULT NULL,
            promo_button_label VARCHAR(100) DEFAULT NULL,
            promo_button_link VARCHAR(500) DEFAULT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci', array());

        $existingRow = $db->getRow('SELECT id FROM front_web_settings WHERE id = 1');

        if (!$existingRow) {
            $defaults = frontWebSettingsDefaults();
            $db->insertRow(
                'INSERT INTO front_web_settings (
                    id,
                    header_notice,
                    hero_badge,
                    hero_title,
                    hero_button_label,
                    hero_button_link,
                    hero_image,
                    banner_one_image,
                    banner_one_badge,
                    banner_one_title,
                    banner_one_button_label,
                    banner_one_button_link,
                    banner_two_image,
                    banner_two_badge,
                    banner_two_title,
                    banner_two_button_label,
                    banner_two_button_link,
                    promo_image,
                    promo_badge,
                    promo_title,
                    promo_description,
                    promo_button_label,
                    promo_button_link
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                array(
                    1,
                    $defaults['header_notice'],
                    $defaults['hero_badge'],
                    $defaults['hero_title'],
                    $defaults['hero_button_label'],
                    $defaults['hero_button_link'],
                    $defaults['hero_image'],
                    $defaults['banner_one_image'],
                    $defaults['banner_one_badge'],
                    $defaults['banner_one_title'],
                    $defaults['banner_one_button_label'],
                    $defaults['banner_one_button_link'],
                    $defaults['banner_two_image'],
                    $defaults['banner_two_badge'],
                    $defaults['banner_two_title'],
                    $defaults['banner_two_button_label'],
                    $defaults['banner_two_button_link'],
                    $defaults['promo_image'],
                    $defaults['promo_badge'],
                    $defaults['promo_title'],
                    $defaults['promo_description'],
                    $defaults['promo_button_label'],
                    $defaults['promo_button_link']
                )
            );
        }

        return getFrontWebSettings($db, true);
    }
}

if (!function_exists('frontWebResolvedValue')) {
    function frontWebResolvedValue($value, $fallback = '')
    {
        $resolvedValue = trim((string) $value);

        if ($resolvedValue === '') {
            $resolvedValue = trim((string) $fallback);
        }

        return $resolvedValue;
    }
}

if (!function_exists('frontWebWorkspaceFileExists')) {
    function frontWebWorkspaceFileExists($relativePath)
    {
        $relativePath = ltrim(str_replace('\\', '/', trim((string) $relativePath)), '/');

        if ($relativePath === '') {
            return false;
        }

        $absolutePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

        return is_file($absolutePath);
    }
}

if (!function_exists('frontWebNormalizeInternalLink')) {
    function frontWebNormalizeInternalLink($link)
    {
        $link = trim(str_replace('\\', '/', (string) $link));

        if ($link === '') {
            return '';
        }

        if (
            preg_match('#^(https?:)?//#i', $link) ||
            strpos($link, 'mailto:') === 0 ||
            strpos($link, 'tel:') === 0 ||
            strpos($link, '#') === 0
        ) {
            return $link;
        }

        $link = ltrim($link, '/');

        if (stripos($link, 'Front Web/') === 0) {
            $link = ltrim(substr($link, strlen('Front Web/')), '/');
        }

        return $link;
    }
}

if (!function_exists('frontWebNormalizeAssetPath')) {
    function frontWebNormalizeAssetPath($path)
    {
        $path = trim(str_replace('\\', '/', (string) $path));

        if ($path === '' || preg_match('#^(https?:)?//#i', $path)) {
            return $path;
        }

        $path = ltrim($path, '/');

        if (stripos($path, 'Front Web/') === 0) {
            $strippedPath = ltrim(substr($path, strlen('Front Web/')), '/');

            if ($strippedPath !== '' && frontWebWorkspaceFileExists($strippedPath)) {
                return $strippedPath;
            }

            if (frontWebWorkspaceFileExists($path)) {
                return $path;
            }

            return '';
        }

        if (!frontWebWorkspaceFileExists($path)) {
            return '';
        }

        return $path;
    }
}

if (!function_exists('normalizeGeneralSettingsAssets')) {
    function normalizeGeneralSettingsAssets(array $settings)
    {
        foreach (array('logo', 'footerLogo', 'favIcon') as $assetField) {
            if (isset($settings[$assetField])) {
                $settings[$assetField] = frontWebNormalizeAssetPath($settings[$assetField]);
            }
        }

        return $settings;
    }
}

if (!function_exists('normalizeFrontWebSettings')) {
    function normalizeFrontWebSettings(array $settings)
    {
        foreach (array('hero_image', 'banner_one_image', 'banner_two_image', 'promo_image') as $assetField) {
            if (isset($settings[$assetField])) {
                $settings[$assetField] = frontWebNormalizeAssetPath($settings[$assetField]);
            }
        }

        foreach (array('hero_button_link', 'banner_one_button_link', 'banner_two_button_link', 'promo_button_link') as $linkField) {
            if (isset($settings[$linkField])) {
                $settings[$linkField] = frontWebNormalizeInternalLink($settings[$linkField]);
            }
        }

        return $settings;
    }
}

if (!function_exists('frontWebAssetUrl')) {
    function frontWebAssetUrl($siteUrl, $path, $fallback = '')
    {
        $assetPath = frontWebResolvedValue(
            frontWebNormalizeAssetPath($path),
            frontWebNormalizeAssetPath($fallback)
        );

        if ($assetPath === '') {
            return '';
        }

        if (preg_match('#^(https?:)?//#i', $assetPath)) {
            return $assetPath;
        }

        return rtrim($siteUrl, '/') . '/' . ltrim($assetPath, '/');
    }
}

if (!function_exists('frontWebLinkUrl')) {
    function frontWebLinkUrl($siteUrl, $link, $fallback = '')
    {
        $target = frontWebResolvedValue(
            frontWebNormalizeInternalLink($link),
            frontWebNormalizeInternalLink($fallback)
        );

        if ($target === '') {
            return '#';
        }

        if (
            preg_match('#^(https?:)?//#i', $target) ||
            strpos($target, 'mailto:') === 0 ||
            strpos($target, 'tel:') === 0 ||
            strpos($target, '#') === 0
        ) {
            return $target;
        }

        return rtrim($siteUrl, '/') . '/' . ltrim($target, '/');
    }
}

if (!function_exists('frontWebAdminAssetUrl')) {
    function frontWebAdminAssetUrl($path, $fallback = '')
    {
        $assetPath = frontWebResolvedValue(
            frontWebNormalizeAssetPath($path),
            frontWebNormalizeAssetPath($fallback)
        );

        if ($assetPath === '') {
            return '';
        }

        if (preg_match('#^(https?:)?//#i', $assetPath)) {
            return $assetPath;
        }

        return '../' . ltrim($assetPath, '/');
    }
}

if (!function_exists('frontWebNormalizePath')) {
    function frontWebNormalizePath($path)
    {
        return str_replace('\\', '/', trim((string) $path));
    }
}

if (!function_exists('frontWebResolveProductImagePath')) {
    function frontWebResolveProductImagePath($imagePath, $imageName)
    {
        $imagePath = frontWebNormalizePath($imagePath);
        $imageName = frontWebNormalizePath($imageName);

        if ($imageName !== '' && preg_match('#^(https?:)?//#i', $imageName)) {
            return $imageName;
        }

        if ($imagePath !== '' && preg_match('#^(https?:)?//#i', $imagePath)) {
            return rtrim($imagePath, '/') . ($imageName !== '' ? '/' . ltrim($imageName, '/') : '');
        }

        $candidates = array();
        if ($imagePath !== '' && $imageName !== '') {
            $candidates[] = rtrim($imagePath, '/') . '/' . ltrim($imageName, '/');
        }
        if ($imagePath !== '') {
            $candidates[] = $imagePath;
        }
        if ($imageName !== '') {
            $candidates[] = $imageName;
            $candidates[] = 'images/product_img/' . ltrim($imageName, '/');
            $candidates[] = 'image/product_img/' . ltrim($imageName, '/');
        }

        $workspaceRoot = dirname(__DIR__, 2);
        $resolvedPath = '';

        foreach ($candidates as $candidate) {
            $relativePath = ltrim(frontWebNormalizePath($candidate), '/');
            if ($relativePath === '') {
                continue;
            }

            $absolutePath = $workspaceRoot . '/' . $relativePath;
            if (is_file($absolutePath)) {
                return $relativePath;
            }

            if ($resolvedPath === '') {
                $resolvedPath = $relativePath;
            }
        }

        return $resolvedPath;
    }
}

if (!function_exists('frontWebProductImageUrl')) {
    function frontWebProductImageUrl($siteUrl, $imagePath, $imageName, $fallback = 'images/product_img/defult-img.png')
    {
        $resolvedImage = frontWebResolveProductImagePath($imagePath, $imageName);

        if ($resolvedImage === '') {
            return frontWebAssetUrl($siteUrl, $fallback);
        }

        if (preg_match('#^(https?:)?//#i', $resolvedImage)) {
            return $resolvedImage;
        }

        return frontWebAssetUrl($siteUrl, $resolvedImage, $fallback);
    }
}

if (!function_exists('frontWebProductImageFromRow')) {
    function frontWebProductImageFromRow($siteUrl, array $row, $pathKey = 'imageParth', $imageKey = 'item_image', $fallback = 'images/product_img/defult-img.png')
    {
        return frontWebProductImageUrl(
            $siteUrl,
            isset($row[$pathKey]) ? $row[$pathKey] : '',
            isset($row[$imageKey]) ? $row[$imageKey] : '',
            $fallback
        );
    }
}