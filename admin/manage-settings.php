<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('include/database.php');
include('include/check_login.php');
require_once(__DIR__ . '/../include/front_web_settings.php');

requirePermission('settings.permissions');

$db = new Database();

function humanizeSettingsField($fieldName)
{
    $fieldName = str_replace('_file', '', (string) $fieldName);
    $fieldName = str_replace('_', ' ', $fieldName);
    return ucwords(trim($fieldName));
}

function deleteManagedFrontWebFile($relativePath)
{
    $relativePath = trim((string) $relativePath);

    if ($relativePath === '' || strpos(str_replace('\\', '/', $relativePath), 'uploads/frontweb/') !== 0) {
        return;
    }

    $fullPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

    if (is_file($fullPath)) {
        @unlink($fullPath);
    }
}

function handleFrontWebImageUpload($fieldName, $subDirectory, $currentPath, &$errorMessage)
{
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return $currentPath;
    }

    if ($_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        $errorMessage = 'Failed to upload ' . humanizeSettingsField($fieldName) . '.';
        return $currentPath;
    }

    $allowedExtensions = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'ico');
    $allowedMimeTypes = array(
        'jpg' => array('image/jpeg', 'image/pjpeg'),
        'jpeg' => array('image/jpeg', 'image/pjpeg'),
        'png' => array('image/png'),
        'gif' => array('image/gif'),
        'webp' => array('image/webp'),
        'avif' => array('image/avif'),
        'ico' => array('image/x-icon', 'image/vnd.microsoft.icon', 'application/octet-stream')
    );

    $extension = strtolower(pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExtensions, true)) {
        $errorMessage = 'Invalid file type for ' . humanizeSettingsField($fieldName) . '.';
        return $currentPath;
    }

    $detectedMime = '';

    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        if ($finfo) {
            $detectedMime = finfo_file($finfo, $_FILES[$fieldName]['tmp_name']);
            finfo_close($finfo);
        }
    }

    if ($detectedMime !== '' && !in_array($detectedMime, $allowedMimeTypes[$extension], true)) {
        $errorMessage = 'Invalid image content for ' . humanizeSettingsField($fieldName) . '.';
        return $currentPath;
    }

    $relativeDirectory = 'uploads/frontweb/' . trim($subDirectory, '/\\');
    $uploadDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDirectory) . DIRECTORY_SEPARATOR;

    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
        $errorMessage = 'Unable to create the upload folder for ' . humanizeSettingsField($fieldName) . '.';
        return $currentPath;
    }

    $baseName = preg_replace('/[^a-z0-9]+/i', '-', pathinfo($_FILES[$fieldName]['name'], PATHINFO_FILENAME));
    $baseName = strtolower(trim((string) $baseName, '-'));

    if ($baseName === '') {
        $baseName = str_replace('_', '-', $fieldName);
    }

    $newFileName = $baseName . '-' . date('YmdHis') . '-' . mt_rand(1000, 9999) . '.' . $extension;
    $destination = $uploadDirectory . $newFileName;

    if (!move_uploaded_file($_FILES[$fieldName]['tmp_name'], $destination)) {
        $errorMessage = 'Unable to save ' . humanizeSettingsField($fieldName) . '.';
        return $currentPath;
    }

    deleteManagedFrontWebFile($currentPath);

    return $relativeDirectory . '/' . $newFileName;
}

function renderImageUploadField($label, $fieldName, $currentPath, $fallbackPath, $helpText = '')
{
    static $cacheBust = null;

    if ($cacheBust === null) {
        $cacheBust = time();
    }

    $previewUrl = frontWebAdminAssetUrl($currentPath, $fallbackPath);
    $currentValue = frontWebResolvedValue($currentPath, $fallbackPath);
    ?>
    <div class="form-group">
        <label class="control-label col-md-3"><?php echo htmlspecialchars($label); ?></label>
        <div class="col-md-9">
            <div class="settings-image-preview">
                <?php if ($previewUrl !== '') { ?>
                    <img src="<?php echo htmlspecialchars($previewUrl); ?>?t=<?php echo $cacheBust; ?>" alt="<?php echo htmlspecialchars($label); ?>">
                <?php } else { ?>
                    <div class="settings-empty-preview">No image uploaded</div>
                <?php } ?>
            </div>
            <input type="file" name="<?php echo htmlspecialchars($fieldName); ?>" class="form-control">
            <?php if ($currentValue !== '') { ?>
                <p class="help-block">Current path: <?php echo htmlspecialchars($currentValue); ?></p>
            <?php } ?>
            <?php if ($helpText !== '') { ?>
                <p class="help-block"><?php echo htmlspecialchars($helpText); ?></p>
            <?php } ?>
        </div>
    </div>
    <?php
}

function getHomeSliderItems($db)
{
    try {
        return $db->getRows('SELECT id, image, path, link, text, active FROM home_slider ORDER BY id DESC');
    } catch (Exception $exception) {
        return array();
    }
}

function buildSliderPreviewUrl($path, $image)
{
    $path = trim(str_replace('\\', '/', (string) $path));
    $image = trim(str_replace('\\', '/', (string) $image));

    if ($path === '' && $image === '') {
        return '';
    }

    $relativePath = rtrim($path, '/');
    if ($relativePath !== '' && $image !== '') {
        $relativePath .= '/' . ltrim($image, '/');
    } elseif ($image !== '') {
        $relativePath = ltrim($image, '/');
    }

    return frontWebAdminAssetUrl($relativePath, '');
}

ensureFrontWebSettingsTable($db);

$settings = getGeneralSettings($db, true);
$frontSettings = getFrontWebSettings($db, true);
$homeSliderItems = getHomeSliderItems($db);

$message = '';
$messageClass = '';

if (isset($_POST['save_settings'])) {
    $siteName = trim($_POST['SiteName'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $systemEmail = trim($_POST['system_email'] ?? '');
    $contactUs = trim($_POST['contactUs'] ?? '');

    $headerNotice = trim($_POST['header_notice'] ?? '');
    $heroBadge = trim($_POST['hero_badge'] ?? '');
    $heroTitle = trim($_POST['hero_title'] ?? '');
    $heroButtonLabel = trim($_POST['hero_button_label'] ?? '');
    $heroButtonLink = trim($_POST['hero_button_link'] ?? '');

    $bannerOneBadge = trim($_POST['banner_one_badge'] ?? '');
    $bannerOneTitle = trim($_POST['banner_one_title'] ?? '');
    $bannerOneButtonLabel = trim($_POST['banner_one_button_label'] ?? '');
    $bannerOneButtonLink = trim($_POST['banner_one_button_link'] ?? '');

    $bannerTwoBadge = trim($_POST['banner_two_badge'] ?? '');
    $bannerTwoTitle = trim($_POST['banner_two_title'] ?? '');
    $bannerTwoButtonLabel = trim($_POST['banner_two_button_label'] ?? '');
    $bannerTwoButtonLink = trim($_POST['banner_two_button_link'] ?? '');

    $promoBadge = trim($_POST['promo_badge'] ?? '');
    $promoTitle = trim($_POST['promo_title'] ?? '');
    $promoDescription = trim($_POST['promo_description'] ?? '');
    $promoButtonLabel = trim($_POST['promo_button_label'] ?? '');
    $promoButtonLink = trim($_POST['promo_button_link'] ?? '');

    $logoPath = $settings['logo'] ?? '';
    $footerLogoPath = $settings['footerLogo'] ?? '';
    $favIconPath = $settings['favIcon'] ?? '';
    $heroImagePath = $frontSettings['hero_image'] ?? '';
    $bannerOneImagePath = $frontSettings['banner_one_image'] ?? '';
    $bannerTwoImagePath = $frontSettings['banner_two_image'] ?? '';
    $promoImagePath = $frontSettings['promo_image'] ?? '';

    $uploadError = '';

    $logoPath = handleFrontWebImageUpload('logo_file', 'branding', $logoPath, $uploadError);
    if ($uploadError === '') {
        $footerLogoPath = handleFrontWebImageUpload('footer_logo_file', 'branding', $footerLogoPath, $uploadError);
    }
    if ($uploadError === '') {
        $favIconPath = handleFrontWebImageUpload('fav_icon_file', 'branding', $favIconPath, $uploadError);
    }
    if ($uploadError === '') {
        $heroImagePath = handleFrontWebImageUpload('hero_image_file', 'banners', $heroImagePath, $uploadError);
    }
    if ($uploadError === '') {
        $bannerOneImagePath = handleFrontWebImageUpload('banner_one_image_file', 'banners', $bannerOneImagePath, $uploadError);
    }
    if ($uploadError === '') {
        $bannerTwoImagePath = handleFrontWebImageUpload('banner_two_image_file', 'banners', $bannerTwoImagePath, $uploadError);
    }
    if ($uploadError === '') {
        $promoImagePath = handleFrontWebImageUpload('promo_image_file', 'banners', $promoImagePath, $uploadError);
    }

    if ($uploadError !== '') {
        $message = $uploadError;
        $messageClass = 'alert-danger';
    } else {
        try {
            $generalSettingsRow = $db->getRow('SELECT id FROM general_settings WHERE id = 1');

            if ($generalSettingsRow) {
                $db->updateRow(
                    'UPDATE general_settings SET SiteName = ?, logo = ?, footerLogo = ?, favIcon = ?, address = ?, system_email = ?, contactUs = ? WHERE id = 1',
                    array($siteName, $logoPath, $footerLogoPath, $favIconPath, $address, $systemEmail, $contactUs)
                );
            } else {
                $db->insertRow(
                    'INSERT INTO general_settings (id, SiteName, logo, footerLogo, favIcon, address, system_email, contactUs) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                    array(1, $siteName, $logoPath, $footerLogoPath, $favIconPath, $address, $systemEmail, $contactUs)
                );
            }

            $frontSettingsRow = $db->getRow('SELECT id FROM front_web_settings WHERE id = 1');

            if ($frontSettingsRow) {
                $db->updateRow(
                    'UPDATE front_web_settings SET header_notice = ?, hero_badge = ?, hero_title = ?, hero_button_label = ?, hero_button_link = ?, hero_image = ?, banner_one_image = ?, banner_one_badge = ?, banner_one_title = ?, banner_one_button_label = ?, banner_one_button_link = ?, banner_two_image = ?, banner_two_badge = ?, banner_two_title = ?, banner_two_button_label = ?, banner_two_button_link = ?, promo_image = ?, promo_badge = ?, promo_title = ?, promo_description = ?, promo_button_label = ?, promo_button_link = ? WHERE id = 1',
                    array(
                        $headerNotice,
                        $heroBadge,
                        $heroTitle,
                        $heroButtonLabel,
                        $heroButtonLink,
                        $heroImagePath,
                        $bannerOneImagePath,
                        $bannerOneBadge,
                        $bannerOneTitle,
                        $bannerOneButtonLabel,
                        $bannerOneButtonLink,
                        $bannerTwoImagePath,
                        $bannerTwoBadge,
                        $bannerTwoTitle,
                        $bannerTwoButtonLabel,
                        $bannerTwoButtonLink,
                        $promoImagePath,
                        $promoBadge,
                        $promoTitle,
                        $promoDescription,
                        $promoButtonLabel,
                        $promoButtonLink
                    )
                );
            } else {
                $db->insertRow(
                    'INSERT INTO front_web_settings (id, header_notice, hero_badge, hero_title, hero_button_label, hero_button_link, hero_image, banner_one_image, banner_one_badge, banner_one_title, banner_one_button_label, banner_one_button_link, banner_two_image, banner_two_badge, banner_two_title, banner_two_button_label, banner_two_button_link, promo_image, promo_badge, promo_title, promo_description, promo_button_label, promo_button_link) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    array(
                        1,
                        $headerNotice,
                        $heroBadge,
                        $heroTitle,
                        $heroButtonLabel,
                        $heroButtonLink,
                        $heroImagePath,
                        $bannerOneImagePath,
                        $bannerOneBadge,
                        $bannerOneTitle,
                        $bannerOneButtonLabel,
                        $bannerOneButtonLink,
                        $bannerTwoImagePath,
                        $bannerTwoBadge,
                        $bannerTwoTitle,
                        $bannerTwoButtonLabel,
                        $bannerTwoButtonLink,
                        $promoImagePath,
                        $promoBadge,
                        $promoTitle,
                        $promoDescription,
                        $promoButtonLabel,
                        $promoButtonLink
                    )
                );
            }

            $settings = getGeneralSettings($db, true);
            $frontSettings = getFrontWebSettings($db, true);
            $message = 'Front Web settings saved successfully.';
            $messageClass = 'alert-success';
        } catch (Exception $exception) {
            $message = 'Unable to save settings: ' . $exception->getMessage();
            $messageClass = 'alert-danger';
        }
    }
}

$siteName = $settings['SiteName'] ?? '';
$address = $settings['address'] ?? '';
$systemEmail = $settings['system_email'] ?? '';
$contactUs = $settings['contactUs'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Front Web Settings</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
    <style>
        .settings-form .form-group { margin-bottom: 20px; }
        .settings-image-preview {
            align-items: center;
            background: #fafbfd;
            border: 2px dashed #dfe4ea;
            border-radius: 8px;
            display: flex;
            justify-content: center;
            margin-bottom: 12px;
            min-height: 140px;
            padding: 18px;
            text-align: center;
        }
        .settings-image-preview img {
            max-height: 180px;
            max-width: 100%;
        }
        .settings-empty-preview {
            color: #98a2b3;
            font-style: italic;
        }
        .section-note {
            color: #667085;
            margin-bottom: 20px;
        }
        .settings-slider-grid {
            display: grid;
            gap: 18px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            margin-top: 18px;
        }
        .settings-slider-card {
            background: #fff;
            border: 1px solid #e4e7ec;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(16, 24, 40, 0.06);
        }
        .settings-slider-card img {
            display: block;
            height: 140px;
            object-fit: cover;
            width: 100%;
        }
        .settings-slider-meta {
            padding: 14px;
        }
        .settings-slider-meta p {
            color: #475467;
            margin: 0 0 8px;
            word-break: break-word;
        }
        .settings-slider-actions {
            display: flex;
            gap: 12px;
            margin-top: 10px;
        }
        .settings-slider-status {
            display: inline-block;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 999px;
            background: #ecfdf3;
            color: #027a48;
        }
    </style>
</head>
<body class="page-sidebar-closed-hide-logo page-content-white">
<?php include('common/manubar.php'); ?>
<div class="page-container">
    <div class="page-content-wrapper">
        <div class="page-content">
            <h3 class="page-title"><i class="fa fa-desktop"></i> Front Web Settings</h3>

            <?php if (!empty($message)) { ?>
                <div class="alert <?php echo $messageClass; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php } ?>

            <form method="post" class="form-horizontal settings-form" enctype="multipart/form-data">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-cogs"></i> Brand & Contact
                        </div>
                    </div>
                    <div class="portlet-body">
                        <p class="section-note">Control the public site identity, contact details, and shared branding images from one place.</p>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label col-md-3">Site Name</label>
                                    <div class="col-md-9">
                                        <input type="text" name="SiteName" class="form-control" value="<?php echo htmlspecialchars($siteName); ?>" placeholder="Bakery Shop">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-md-3">System Email</label>
                                    <div class="col-md-9">
                                        <input type="email" name="system_email" class="form-control" value="<?php echo htmlspecialchars($systemEmail); ?>" placeholder="info@example.com">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-md-3">Contact Number</label>
                                    <div class="col-md-9">
                                        <input type="text" name="contactUs" class="form-control" value="<?php echo htmlspecialchars($contactUs); ?>" placeholder="+94 77 123 4567">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-md-3">Address</label>
                                    <div class="col-md-9">
                                        <textarea name="address" class="form-control" rows="4" placeholder="Store address shown on the website"><?php echo htmlspecialchars($address); ?></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <?php renderImageUploadField('Header Logo', 'logo_file', $settings['logo'] ?? '', 'assets/img/logo/logo.avif', 'Recommended transparent PNG or WEBP.'); ?>
                                <?php renderImageUploadField('Footer Logo', 'footer_logo_file', $settings['footerLogo'] ?? '', '', 'Displayed above the footer contact block.'); ?>
                                <?php renderImageUploadField('Favicon', 'fav_icon_file', $settings['favIcon'] ?? '', 'assets/img/logo/logo.avif', 'Allowed formats: JPG, PNG, GIF, WEBP, AVIF, ICO.'); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-home"></i> Header & Hero Banner
                        </div>
                    </div>
                    <div class="portlet-body">
                        <p class="section-note">Update the top welcome text and the main hero section shown on the home page.</p>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label col-md-3">Header Notice</label>
                                    <div class="col-md-9">
                                        <input type="text" name="header_notice" class="form-control" value="<?php echo htmlspecialchars($frontSettings['header_notice']); ?>" placeholder="Welcome message shown at the very top">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-md-3">Hero Badge</label>
                                    <div class="col-md-9">
                                        <input type="text" name="hero_badge" class="form-control" value="<?php echo htmlspecialchars($frontSettings['hero_badge']); ?>" placeholder="Fresh Daily">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-md-3">Hero Title</label>
                                    <div class="col-md-9">
                                        <input type="text" name="hero_title" class="form-control" value="<?php echo htmlspecialchars($frontSettings['hero_title']); ?>" placeholder="Quality Bakery Products">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-md-3">Button Label</label>
                                    <div class="col-md-9">
                                        <input type="text" name="hero_button_label" class="form-control" value="<?php echo htmlspecialchars($frontSettings['hero_button_label']); ?>" placeholder="Shop Now">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-md-3">Button Link</label>
                                    <div class="col-md-9">
                                        <input type="text" name="hero_button_link" class="form-control" value="<?php echo htmlspecialchars($frontSettings['hero_button_link']); ?>" placeholder="search.php or https://example.com">
                                        <p class="help-block">Use an internal path like search.php or a full external URL.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <?php renderImageUploadField('Hero Image', 'hero_image_file', $frontSettings['hero_image'] ?? '', '', 'Shown on the right side of the main home banner.'); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="portlet light bordered">
                            <div class="portlet-title">
                                <div class="caption">
                                    <i class="fa fa-image"></i> Banner One
                                </div>
                            </div>
                            <div class="portlet-body">
                                <?php renderImageUploadField('Banner Image', 'banner_one_image_file', $frontSettings['banner_one_image'] ?? '', '', 'Left banner below the category icons.'); ?>
                                <div class="form-group">
                                    <label class="control-label col-md-3">Badge</label>
                                    <div class="col-md-9">
                                        <input type="text" name="banner_one_badge" class="form-control" value="<?php echo htmlspecialchars($frontSettings['banner_one_badge']); ?>" placeholder="Fresh Daily">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-md-3">Title</label>
                                    <div class="col-md-9">
                                        <input type="text" name="banner_one_title" class="form-control" value="<?php echo htmlspecialchars($frontSettings['banner_one_title']); ?>" placeholder="Best Quality Products">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-md-3">Button Label</label>
                                    <div class="col-md-9">
                                        <input type="text" name="banner_one_button_label" class="form-control" value="<?php echo htmlspecialchars($frontSettings['banner_one_button_label']); ?>" placeholder="Shop Now">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-md-3">Button Link</label>
                                    <div class="col-md-9">
                                        <input type="text" name="banner_one_button_link" class="form-control" value="<?php echo htmlspecialchars($frontSettings['banner_one_button_link']); ?>" placeholder="search.php or https://example.com">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="portlet light bordered">
                            <div class="portlet-title">
                                <div class="caption">
                                    <i class="fa fa-image"></i> Banner Two
                                </div>
                            </div>
                            <div class="portlet-body">
                                <?php renderImageUploadField('Banner Image', 'banner_two_image_file', $frontSettings['banner_two_image'] ?? '', '', 'Right banner below the category icons.'); ?>
                                <div class="form-group">
                                    <label class="control-label col-md-3">Badge</label>
                                    <div class="col-md-9">
                                        <input type="text" name="banner_two_badge" class="form-control" value="<?php echo htmlspecialchars($frontSettings['banner_two_badge']); ?>" placeholder="Hot & Spicy">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-md-3">Title</label>
                                    <div class="col-md-9">
                                        <input type="text" name="banner_two_title" class="form-control" value="<?php echo htmlspecialchars($frontSettings['banner_two_title']); ?>" placeholder="Freshly Baked Pastry">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-md-3">Button Label</label>
                                    <div class="col-md-9">
                                        <input type="text" name="banner_two_button_label" class="form-control" value="<?php echo htmlspecialchars($frontSettings['banner_two_button_label']); ?>" placeholder="Shop Now">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-md-3">Button Link</label>
                                    <div class="col-md-9">
                                        <input type="text" name="banner_two_button_link" class="form-control" value="<?php echo htmlspecialchars($frontSettings['banner_two_button_link']); ?>" placeholder="search.php or https://example.com">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-bullhorn"></i> Full Width Promotion Banner
                        </div>
                    </div>
                    <div class="portlet-body">
                        <p class="section-note">Configure the large promotional banner shown in the middle of the home page.</p>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label col-md-3">Badge</label>
                                    <div class="col-md-9">
                                        <input type="text" name="promo_badge" class="form-control" value="<?php echo htmlspecialchars($frontSettings['promo_badge']); ?>" placeholder="Fresh Everyday">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-md-3">Title</label>
                                    <div class="col-md-9">
                                        <input type="text" name="promo_title" class="form-control" value="<?php echo htmlspecialchars($frontSettings['promo_title']); ?>" placeholder="Best Quality Bakery Products">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-md-3">Description</label>
                                    <div class="col-md-9">
                                        <textarea name="promo_description" class="form-control" rows="4" placeholder="Short supporting copy"><?php echo htmlspecialchars($frontSettings['promo_description']); ?></textarea>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-md-3">Button Label</label>
                                    <div class="col-md-9">
                                        <input type="text" name="promo_button_label" class="form-control" value="<?php echo htmlspecialchars($frontSettings['promo_button_label']); ?>" placeholder="Shop Now">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-md-3">Button Link</label>
                                    <div class="col-md-9">
                                        <input type="text" name="promo_button_link" class="form-control" value="<?php echo htmlspecialchars($frontSettings['promo_button_link']); ?>" placeholder="search.php or https://example.com">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <?php renderImageUploadField('Background Image', 'promo_image_file', $frontSettings['promo_image'] ?? '', '', 'Used as the full-width background image.'); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-sliders"></i> Home Page Slider
                        </div>
                    </div>
                    <div class="portlet-body">
                        <p class="section-note">The root home page carousel uses the slider gallery below. Use the slider manager to add, replace, or remove slides without touching code.</p>
                        <div class="settings-slider-actions">
                            <a href="Mainslider.php" class="btn blue">
                                <i class="fa fa-photo"></i> Manage Slider Images
                            </a>
                        </div>

                        <?php if (!empty($homeSliderItems)) { ?>
                            <div class="settings-slider-grid">
                                <?php foreach ($homeSliderItems as $sliderItem) {
                                    $sliderPreview = buildSliderPreviewUrl($sliderItem['path'] ?? '', $sliderItem['image'] ?? '');
                                    $sliderLink = trim((string) ($sliderItem['link'] ?? ''));
                                    $sliderText = trim((string) ($sliderItem['text'] ?? ''));
                                ?>
                                <div class="settings-slider-card">
                                    <?php if ($sliderPreview !== '') { ?>
                                        <img src="<?php echo htmlspecialchars($sliderPreview); ?>?t=<?php echo time(); ?>" alt="Slider <?php echo (int) ($sliderItem['id'] ?? 0); ?>">
                                    <?php } else { ?>
                                        <div class="settings-image-preview"><div class="settings-empty-preview">No preview available</div></div>
                                    <?php } ?>
                                    <div class="settings-slider-meta">
                                        <span class="settings-slider-status"><?php echo !empty($sliderItem['active']) ? 'Active' : 'Inactive'; ?></span>
                                        <?php if ($sliderText !== '') { ?><p><strong>Text:</strong> <?php echo htmlspecialchars($sliderText); ?></p><?php } ?>
                                        <?php if ($sliderLink !== '') { ?><p><strong>Link:</strong> <?php echo htmlspecialchars($sliderLink); ?></p><?php } ?>
                                    </div>
                                </div>
                                <?php } ?>
                            </div>
                        <?php } else { ?>
                            <div class="settings-image-preview" style="margin-top: 18px;">
                                <div class="settings-empty-preview">No slider images uploaded yet.</div>
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <div class="form-actions">
                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" name="save_settings" class="btn green">
                                <i class="fa fa-check"></i> Save Front Web Settings
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include('common/footer.php'); ?>
</body>
</html>