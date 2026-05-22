<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

session_start();
include('../include/database.php');
include('../include/check_login.php');

header('Content-Type: application/json');

// Log all POST data for debugging
error_log('edit-product-process.php called. POST: ' . print_r($_POST, true));

$response = [
	'status' => false,
	'message' => 'Unable to update product.'
];

function ensureItemMasterRawMaterialColumn($db)
{
	$exists = $db->getRow("SHOW COLUMNS FROM item_master LIKE 'is_raw_material'");
	if (!$exists) {
		$db->insertRow("ALTER TABLE item_master ADD COLUMN is_raw_material TINYINT(1) NOT NULL DEFAULT 0");
	}
	$existsSales = $db->getRow("SHOW COLUMNS FROM item_master LIKE 'allow_in_sales'");
	if (!$existsSales) {
		$db->insertRow("ALTER TABLE item_master ADD COLUMN allow_in_sales TINYINT(1) NOT NULL DEFAULT 1");
	}
	$existsGrn = $db->getRow("SHOW COLUMNS FROM item_master LIKE 'allow_in_grn'");
	if (!$existsGrn) {
		$db->insertRow("ALTER TABLE item_master ADD COLUMN allow_in_grn TINYINT(1) NOT NULL DEFAULT 1");
	}
}

function sanitizeText($value)
{
	return trim((string) $value);
}

function normalizeAmount($value)
{
	if ($value === null) {
		return null;
	}

	$value = trim((string) $value);

	if ($value === '') {
		return null;
	}

	return str_replace(',', '', $value);
}

function normalizeNumber($value, $decimals = 2)
{
	$amount = normalizeAmount($value);
	if ($amount === null) {
		return null;
	}

	return number_format((float) $amount, $decimals, '.', '');
}

function uploadErrorMessage($errorCode)
{
	switch ((int) $errorCode) {
		case UPLOAD_ERR_INI_SIZE:
		case UPLOAD_ERR_FORM_SIZE:
			return 'The thumbnail file is too large.';
		case UPLOAD_ERR_PARTIAL:
			return 'The thumbnail upload was interrupted. Please try again.';
		case UPLOAD_ERR_NO_TMP_DIR:
			return 'The server is missing a temporary upload folder.';
		case UPLOAD_ERR_CANT_WRITE:
			return 'The server could not write the thumbnail to disk.';
		case UPLOAD_ERR_EXTENSION:
			return 'A server extension stopped the thumbnail upload.';
		default:
			return 'The thumbnail upload failed.';
	}
}

try {
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		throw new Exception('Invalid request method.');
	}

	if (!isset($_POST['pid']) || sanitizeText($_POST['pid']) === '') {
		throw new Exception('Missing product identifier.');
	}

	$productId = sanitizeText($_POST['pid']);

	$db = new Database();
	ensureItemMasterRawMaterialColumn($db);
	$product = $db->getRow('SELECT * FROM item_master WHERE item_id = ?', [$productId]);

	if (!$product) {
		throw new Exception('Product not found.');
	}

	$productCode = sanitizeText($_POST['pcode'] ?? '');
	$productName = sanitizeText($_POST['pname'] ?? '');
	$productGroup = sanitizeText($_POST['pgroup'] ?? '');
	$productType = sanitizeText($_POST['ptype'] ?? '');
	$productBusinessUnit = sanitizeText($_POST['pbusinessunit'] ?? '');
	if ($productBusinessUnit === '' || !is_numeric($productBusinessUnit)) {
		$productBusinessUnit = null;
	} else {
		$productBusinessUnit = (int) $productBusinessUnit;
	}
	$productCategory = sanitizeText($_POST['pcategory'] ?? '');
	// Legacy item_master.item_uom (Product Unit) is removed from the form;
	// it is auto-synced from the Unit of Measure string by saveProductAdditionalUoms() below.
	$productUom = null;
	$purchasePrice = normalizeAmount($_POST['purchaseprice'] ?? '');
	$minSellingPrice = normalizeAmount($_POST['minsellingprice'] ?? '');
	$normalSellingPrice = normalizeAmount($_POST['normalsellingprice'] ?? '');
	$cashPrice = normalizeAmount($_POST['cashprice'] ?? '');
	$creditPrice = normalizeAmount($_POST['creditprice'] ?? '');
	$otherSellingPrice = normalizeAmount($_POST['othersellingPrice'] ?? '');
	$purchasePrice = $purchasePrice === null ? 0 : $purchasePrice;
	$normalSellingPrice = $normalSellingPrice === null ? 0 : $normalSellingPrice;
	$productVat = sanitizeText($_POST['vat'] ?? 'N');
	$productWarranty = sanitizeText($_POST['warranty'] ?? '');
	$productDescription = $_POST['discription'] ?? '';
	$productCod = sanitizeText($_POST['productCod'] ?? 'disable');
	$productWeight = normalizeNumber($_POST['productWeight'] ?? null, 3);
	$orderQtyMin = normalizeNumber($_POST['order_qty_min'] ?? null);
	$orderQtyMax = normalizeNumber($_POST['order_qty_max'] ?? null);
	$lowStockQty = isset($_POST['low_stock_qty']) && $_POST['low_stock_qty'] !== '' ? (int) $_POST['low_stock_qty'] : 5;
	$packSize = sanitizeText($_POST['pack_size'] ?? '');
	$accPostingGrpCode = sanitizeText($_POST['acc_posting_grp_code'] ?? '');
	$gstVatCode = sanitizeText($_POST['gst_vat_code'] ?? '');
	$productStatus = sanitizeText($_POST['productStatus'] ?? 'Normal');
	$immediatePickup = sanitizeText($_POST['ImmediatePickup'] ?? 'No');
	$hasSerial = sanitizeText($_POST['sirial'] ?? 'N');

	// Additional Product Information fields
	$wholesalePrice = normalizeAmount($_POST['wholesale_price'] ?? '');
	$retailPrice = normalizeAmount($_POST['retail_price'] ?? '');
	$itemWeightG = normalizeNumber($_POST['item_weight_g'] ?? null);
	$packWeightG = normalizeNumber($_POST['pack_weight_g'] ?? null);
	$minimumOrder = normalizeNumber($_POST['minimum_order'] ?? null);
	$unitOfMeasure = sanitizeText($_POST['unit_of_measure'] ?? '');
	$packType = sanitizeText($_POST['pack_type'] ?? '');
	$live = sanitizeText($_POST['live'] ?? 'yes');
	$nutritionalLabel = sanitizeText($_POST['nutritional_label'] ?? '');
	$productSpecification = sanitizeText($_POST['product_specification'] ?? '');
	$defaultLabel = sanitizeText($_POST['default_label'] ?? '');
	$seasonalRule = sanitizeText($_POST['seasonal_rule'] ?? '');
	$foodDeclarations = $_POST['food_declarations'] ?? '';
	$availMonday = isset($_POST['avail_monday']) ? 1 : 0;
	$availTuesday = isset($_POST['avail_tuesday']) ? 1 : 0;
	$availWednesday = isset($_POST['avail_wednesday']) ? 1 : 0;
	$availThursday = isset($_POST['avail_thursday']) ? 1 : 0;
	$availFriday = isset($_POST['avail_friday']) ? 1 : 0;
	$availSaturday = isset($_POST['avail_saturday']) ? 1 : 0;
	$availSunday = isset($_POST['avail_sunday']) ? 1 : 0;
	$hideToAllCustomers = sanitizeText($_POST['hide_to_all_customers'] ?? '0');
	$saleOrReturn = sanitizeText($_POST['sale_or_return'] ?? '0');
	$isRawMaterial = sanitizeText($_POST['is_raw_material'] ?? '0');
	$isRawMaterial = in_array($isRawMaterial, ['0', '1', 0, 1], true) ? (int) $isRawMaterial : 0;
	$allowInSales = isset($_POST['allow_in_sales']) ? 1 : 0;
	$allowInGrn = isset($_POST['allow_in_grn']) ? 1 : 0;
	$batchTracking = sanitizeText($_POST['batch_tracking'] ?? 'NONE');
	$batchTracking = in_array($batchTracking, ['NONE', 'BATCH', 'SERIAL'], true) ? $batchTracking : 'NONE';

	if ($productName === '' || $productGroup === '' || $productType === '') {
		error_log("Validation failed: name='$productName', group='$productGroup', type='$productType'");
		throw new Exception('Please fill in all required fields.');
	}

	if (!in_array($productVat, ['Y', 'N'], true)) {
		throw new Exception('Invalid VAT selection.');
	}

	if (!in_array($productStatus, ['Normal', 'Offline', 'OutofStock'], true)) {
		$productStatus = 'Normal';
	}

	if (!in_array($immediatePickup, ['Yes', 'No'], true)) {
		$immediatePickup = 'No';
	}

	if (!in_array($hasSerial, ['Y', 'N'], true)) {
		$hasSerial = 'N';
	}

	if ($productCode !== '') {
		$existing = $db->getRow('SELECT item_id FROM item_master WHERE item_code = ? AND item_id <> ?', [$productCode, $productId]);
		if ($existing) {
			throw new Exception('Product code already in use.');
		}
	}

	$uploadedFile = $_FILES['img1'] ?? null;
	$allowedImageMimeTypes = [
		'image/jpeg' => 'jpg',
		'image/png' => 'png'
	];
	$thumbnailExtension = null;

	if ($uploadedFile && is_array($uploadedFile)) {
		$uploadError = (int) ($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE);
		if ($uploadError !== UPLOAD_ERR_NO_FILE && $uploadError !== UPLOAD_ERR_OK) {
			throw new Exception(uploadErrorMessage($uploadError));
		}

		if ($uploadError === UPLOAD_ERR_OK && !empty($uploadedFile['size'])) {
			$imageInfo = @getimagesize($uploadedFile['tmp_name']);
			$detectedMime = $imageInfo['mime'] ?? '';

			if (!$imageInfo || !isset($allowedImageMimeTypes[$detectedMime])) {
				throw new Exception('Thumbnail must be a JPG or PNG image.');
			}

			$thumbnailExtension = $allowedImageMimeTypes[$detectedMime];
		}
	}

	$updateParams = [
		$productCode,
		$productName,
		$productGroup,
		$productType,
		$productBusinessUnit,
		$productCategory !== '' ? $productCategory : null,
		$productUom,
		$purchasePrice,
		$minSellingPrice ?? 0,
		$normalSellingPrice,
		$otherSellingPrice ?? 0,
		$cashPrice ?? 0,
		$creditPrice ?? 0,
		$productVat,
		$productWarranty !== '' ? $productWarranty : null,
		$productDescription,
		$productCod,
		$productWeight,
		$orderQtyMin,
		$orderQtyMax,
		$lowStockQty,
		$packSize !== '' ? $packSize : null,
		$accPostingGrpCode !== '' ? $accPostingGrpCode : null,
		$gstVatCode !== '' ? $gstVatCode : null,
		$hasSerial,
		$immediatePickup,
		$productStatus,
		$wholesalePrice,
		$retailPrice,
		$itemWeightG,
		$packWeightG,
		$minimumOrder,
		$unitOfMeasure !== '' ? $unitOfMeasure : null,
		$packType !== '' ? $packType : null,
		$live,
		$nutritionalLabel !== '' ? $nutritionalLabel : null,
		$productSpecification !== '' ? $productSpecification : null,
		$defaultLabel !== '' ? $defaultLabel : null,
		$seasonalRule !== '' ? $seasonalRule : null,
		$foodDeclarations !== '' ? $foodDeclarations : null,
		$availMonday,
		$availTuesday,
		$availWednesday,
		$availThursday,
		$availFriday,
		$availSaturday,
		$availSunday,
		$hideToAllCustomers,
		$saleOrReturn,
		$isRawMaterial,
		$allowInSales,
		$allowInGrn,
		$batchTracking,
		$productId
	];

	$db->updateRow(
		'UPDATE item_master SET item_code = ?, item_name = ?, item_group = ?, item_type = ?, item_business_unit = ?, item_category = ?, item_uom = ?,
			item_purchase_price = ?, item_min_selling_price = ?, item_normal_selling_price = ?, others_selling_price = ?,
			item_cash_selling_price = ?, item_cradit_selling_price = ?, item_vat = ?, item_warranty = ?, item_discription = ?,
			item_cod = ?, item_weight = ?, order_qty_min = ?, order_qty_max = ?, low_stock_qty = ?, pack_size = ?, acc_posting_grp_code = ?,
			gst_vat_code = ?, item_has_sirial = ?, immediate_pickups = ?, item_mode = ?, wholesale_price = ?, retail_price = ?,
			item_weight_g = ?, pack_weight_g = ?, minimum_order = ?, unit_of_measure = ?, pack_type = ?, live = ?,
			nutritional_label = ?, product_specification = ?, default_label = ?, seasonal_rule = ?, food_declarations = ?,
			avail_monday = ?, avail_tuesday = ?, avail_wednesday = ?, avail_thursday = ?, avail_friday = ?, avail_saturday = ?,
			avail_sunday = ?, hide_to_all_customers = ?, sale_or_return = ?, is_raw_material = ?, allow_in_sales = ?, allow_in_grn = ?, batch_tracking = ? WHERE item_id = ?',
		$updateParams
	);

	// Ensure raw material flag is persisted even if other fields change in future schema
	$db->updateRow('UPDATE item_master SET is_raw_material = ? WHERE item_id = ?', [$isRawMaterial, $productId]);

	// Save alternative units of measure (Business Central style)
	require_once(__DIR__ . '/../include/uom_helper.php');
	$altUomsRaw = $_POST['alt_uoms_json'] ?? '[]';
	$altUomsArr = json_decode($altUomsRaw, true);
	if (!is_array($altUomsArr)) { $altUomsArr = []; }
	// Persist additional UOM names first so item_uom rows exist and item_master.item_uom is synced
	$additionalUomNames = $_POST['additional_uoms'] ?? [];
	if (!is_array($additionalUomNames)) { $additionalUomNames = []; }
	try { saveProductAdditionalUoms($db, (int) $productId, $additionalUomNames); } catch (Exception $e) { /* ignore */ }
	try { saveItemAlternativeUoms($db, (int) $productId, $altUomsArr); } catch (Exception $e) { /* ignore */ }
	$rawRow = $db->getRow('SELECT is_raw_material FROM item_master WHERE item_id = ?', [$productId]);
	if (!$rawRow || (int) ($rawRow['is_raw_material'] ?? 0) !== $isRawMaterial) {
		throw new Exception('Raw material flag was not saved.');
	}

	// Save price tiers
	$priceTiersJson = $_POST['price_tiers_json'] ?? '[]';
	$priceTiers = json_decode($priceTiersJson, true);
	// Ensure table exists
	$db->insertRow("CREATE TABLE IF NOT EXISTS item_price_tiers (
		id INT AUTO_INCREMENT PRIMARY KEY,
		item_id INT NOT NULL,
		min_qty DECIMAL(10,2) NOT NULL,
		unit_price DECIMAL(10,4) NOT NULL,
		INDEX idx_item_id (item_id)
	) ENGINE=InnoDB", []);
	$db->insertRow('DELETE FROM item_price_tiers WHERE item_id = ?', [$productId]);
	if (is_array($priceTiers)) {
		foreach ($priceTiers as $tier) {
			$minQty = max(1, floatval($tier['min_qty'] ?? 1));
			$unitPrice = max(0, floatval($tier['unit_price'] ?? 0));
			$db->insertRow('INSERT INTO item_price_tiers (item_id, min_qty, unit_price) VALUES (?,?,?)', [$productId, $minQty, $unitPrice]);
		}
	}

	if ($uploadedFile && is_array($uploadedFile) && !empty($uploadedFile['size']) && (int) $uploadedFile['error'] === UPLOAD_ERR_OK) {
		$currentYear = date('Y');
		$currentMonth = date('m');

		$baseDir = '../../images/product_img';
		$baseDirDb = 'images/product_img';
		$targetDir = $baseDir . '/' . $currentYear . '/' . $currentMonth;
		$targetDirDb = $baseDirDb . '/' . $currentYear . '/' . $currentMonth . '/';

		if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
			throw new Exception('Unable to create the thumbnail folder. Check write permissions for images/product_img.');
		}

		$fileTmp = $uploadedFile['tmp_name'];
		$productNameSafe = preg_replace('/[^a-zA-Z0-9\-]/', '-', strtolower($productName !== '' ? $productName : 'product'));
		$productNameSafe = trim(preg_replace('/-+/', '-', (string) $productNameSafe), '-');
		if ($productNameSafe === '') {
			$productNameSafe = 'product';
		}
		$fileName = $productNameSafe . '-' . $productId . '-' . date('YmdHis') . '.' . $thumbnailExtension;

		$destination = $targetDir . '/' . $fileName;

		if (!move_uploaded_file($fileTmp, $destination)) {
			throw new Exception('Failed to save the new thumbnail. Check write permissions for images/product_img.');
		}

		$db->updateRow('UPDATE item_master SET item_image = ?, imageParth = ? WHERE item_id = ?', [$fileName, $targetDirDb, $productId]);

		$previousImageName = trim((string) ($product['item_image'] ?? ''));
		$previousImagePath = trim(str_replace('\\', '/', (string) ($product['imageParth'] ?? '')));
		if ($previousImageName !== '') {
			$relativePreviousPath = $previousImagePath !== ''
				? rtrim($previousImagePath, '/') . '/' . ltrim($previousImageName, '/')
				: 'images/product_img/' . ltrim($previousImageName, '/');
			$absolutePreviousPath = dirname(__DIR__, 2) . '/' . ltrim($relativePreviousPath, '/');
			if (is_file($absolutePreviousPath) && realpath($absolutePreviousPath) !== realpath($destination)) {
				@unlink($absolutePreviousPath);
			}
		}

		$response['thumbnailUrl'] = '../' . $targetDirDb . $fileName . '?v=' . time();
	}

	$response['status'] = true;
	$response['message'] = 'Product successfully updated';
	$response['id'] = $productId;
	error_log('edit-product-process.php SUCCESS for pid=' . $productId);
	
	// If not an AJAX request, redirect to the product list after save
	if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
		header('Location: ../manage-product.php?msg=updated');
		exit;
	}
} catch (Exception $e) {
	$response['message'] = $e->getMessage();
	error_log('edit-product-process.php ERROR: ' . $e->getMessage());
	
	// If not an AJAX request, redirect back with error
	if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
		header('Location: ../edit-product.php?pid=' . urlencode($_POST['pid'] ?? '') . '&error=' . urlencode($e->getMessage()));
		exit;
	}
}

echo json_encode($response, JSON_FORCE_OBJECT);





