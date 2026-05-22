<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('include/database.php');
include('include/check_login.php');
include('include/uom_helper.php');
ensureItemUomSchema(new Database());

function escape($value)
{
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function normalizeRelativePath($path)
{
	return str_replace('\\', '/', trim((string) $path));
}

function buildProductThumbnailPreviewUrl($imagePath, $imageName)
{
	$imagePath = normalizeRelativePath($imagePath);
	$imageName = normalizeRelativePath($imageName);

	if ($imagePath === '' && $imageName === '') {
		return '';
	}

	if ($imageName !== '' && preg_match('#^https?://#i', $imageName)) {
		return $imageName;
	}

	if ($imagePath !== '' && preg_match('#^https?://#i', $imagePath)) {
		return rtrim($imagePath, '/') . ($imageName !== '' ? '/' . ltrim($imageName, '/') : '');
	}

	$candidates = [];
	if ($imagePath !== '' && $imageName !== '') {
		$candidates[] = rtrim($imagePath, '/') . '/' . ltrim($imageName, '/');
	}
	if ($imageName !== '') {
		$candidates[] = $imageName;
		$candidates[] = 'images/product_img/' . ltrim($imageName, '/');
		$candidates[] = 'image/product_img/' . ltrim($imageName, '/');
	}
	if ($imagePath !== '') {
		$candidates[] = $imagePath;
	}

	$resolvedPath = '';
	foreach ($candidates as $candidate) {
		$relativePath = ltrim(normalizeRelativePath($candidate), '/');
		if ($relativePath === '') {
			continue;
		}

		$filePath = dirname(__DIR__) . '/' . $relativePath;
		if (is_file($filePath)) {
			$resolvedPath = $relativePath . '?v=' . filemtime($filePath);
			break;
		}
		if ($resolvedPath === '') {
			$resolvedPath = $relativePath;
		}
	}

	if ($resolvedPath === '') {
		return '';
	}

	return '../' . ltrim($resolvedPath, '/');
}

function formatAutoNumericValue($value)
{
	if ($value === null || $value === '') {
		return '';
	}

	return number_format((float) $value, 2, '.', ',');
}

function formatNumberValue($value, $decimals = 2)
{
	if ($value === null || $value === '') {
		return '';
	}

	return number_format((float) $value, $decimals, '.', '');
}

function buildOptions(array $rows, $valueKey, $labelKey, $selectedValue = null, $placeholder = null)
{
	$options = '';

	if ($placeholder !== null) {
		$options .= '<option value="">' . escape($placeholder) . '</option>';
	}

	foreach ($rows as $row) {
		$value = (string) ($row[$valueKey] ?? '');
		$label = escape($row[$labelKey] ?? '');
		$selected = ((string) $selectedValue === $value) ? ' selected' : '';
		$options .= '<option value="' . $value . '"' . $selected . '>' . $label . '</option>';
	}

	return $options;
}

function buildVatOptions(array $rows, $selectedValue = null)
{
	$options = '<option value="">Select GST Code</option>';

	foreach ($rows as $row) {
		$rowCode = $row['Code'] ?? ($row['code'] ?? '');
		$rowName = $row['CodeDescription'] ?? ($row['name'] ?? '');
		$rowRate = $row['GSTPercentage'] ?? ($row['rate'] ?? null);

		$code = escape($rowCode);
		$name = escape($rowName);
		$rate = formatNumberValue($rowRate);
		$selected = ((string) $selectedValue === (string) $rowCode) ? ' selected' : '';
		$label = trim($code . ($name !== '' ? ' - ' . $name : '') . ($rate !== '' ? ' (' . $rate . '%)' : ''));
		$options .= '<option value="' . $code . '"' . $selected . '>' . $label . '</option>';
	}

	return $options;
}

$productId = isset($_GET['pid']) ? trim($_GET['pid']) : '';

if ($productId === '') {
	header('Location: manage-product.php');
	exit();
}

$db = new Database();
$product = $db->getRow(
	'SELECT im.*, gm.group_name, tm.type_name, cm.category_name
	 FROM item_master im
	 LEFT JOIN gorup_master gm ON gm.group_id = im.item_group
	 LEFT JOIN type_master tm ON tm.type_id = im.item_type
	 LEFT JOIN category_master cm ON cm.category_id = im.item_category
	 WHERE im.item_id = ? LIMIT 1',
	[$productId]
);

if (!$product) {
	header('Location: manage-product.php');
	exit();
}

$groups = $db->getRows('SELECT group_id, group_name FROM gorup_master ORDER BY group_name ASC');
$types = $db->getRows('SELECT type_id, type_name FROM type_master WHERE group_id = ? ORDER BY type_name ASC', [$product['item_group']]);
$categories = $db->getRows('SELECT category_id, category_name FROM category_master WHERE type_id = ? ORDER BY category_name ASC', [$product['item_type']]);
$businessUnits = $db->getRows('SELECT business_unit_id, business_unit_name FROM business_unit_master ORDER BY business_unit_name ASC');
$uoms = $db->getRows('SELECT uom_id, uom_name FROM item_uom ORDER BY uom_name ASC');
$existingAltUoms = $db->getRows('SELECT iuom.uom_id, iuom.qty_per_uom, iuom.is_default_purchase, iuom.is_default_sales, u.uom_name FROM item_unit_of_measure iuom LEFT JOIN item_uom u ON u.uom_id = iuom.uom_id WHERE iuom.item_id = ? ORDER BY iuom.id ASC', [$productId]) ?: [];
$warranties = $db->getRows('SELECT warranty_id, warranty FROM item_warranty ORDER BY warranty ASC');
$vatCodes = $db->getRows('SELECT Code, CodeDescription, GSTPercentage FROM DST_Code ORDER BY Code ASC');

$groupOptions = buildOptions($groups, 'group_id', 'group_name', $product['item_group'], 'Select Group');
$typeOptions = buildOptions($types, 'type_id', 'type_name', $product['item_type'], 'Select Type');
$businessUnitOptions = buildOptions($businessUnits, 'business_unit_id', 'business_unit_name', $product['item_business_unit'], 'Select Business Unit');
$categoryOptions = buildOptions($categories, 'category_id', 'category_name', $product['item_category'], 'Select Category');
$uomOptions = buildOptions($uoms, 'uom_id', 'uom_name', $product['item_uom'], 'Select UOM');
$warrantyOptions = '<option value="">No warranty</option>' . buildOptions($warranties, 'warranty_id', 'warranty', $product['item_warranty']);
$vatOptions = buildVatOptions($vatCodes, $product['gst_vat_code'] ?? null);

$productImages = $db->getRows('SELECT Id, imagePath, image FROM productimages WHERE itemId = ? ORDER BY Id DESC', [$productId]);
$productSpecifications = $db->getRows('SELECT `key` AS spec_key, `value` AS spec_value FROM item_specification WHERE product_id = ? ORDER BY Id DESC', [$productId]);

$productStatus = $product['item_mode'] ?? 'Normal';
$immediatePickup = $product['immediate_pickups'] ?? 'No';
$hasSerial = $product['item_has_sirial'] ?? 'N';

$thumbnailPath = $product['imageParth'] ?? '';
$thumbnailName = $product['item_image'] ?? '';
$thumbnailUrl = buildProductThumbnailPreviewUrl($thumbnailPath, $thumbnailName);

$currencySymbol = '';
$currencyRow = $db->getRow('SELECT currency FROM currency WHERE activated = ? LIMIT 1', ['Y']);
if ($currencyRow && !empty($currencyRow['currency'])) {
	$currencySymbol = $currencyRow['currency'];
}

?>

<!DOCTYPE html>
<!--[if IE 8]> <html lang="en" class="ie8 no-js"> <![endif]-->
<!--[if IE 9]> <html lang="en" class="ie9 no-js"> <![endif]-->
<!--[if !IE]><!-->
<html lang="en">
<!--<![endif]-->
<head>
	<meta charset="utf-8" />
	<title>Edit Product | STOCK MANAGEMENT</title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta content="width=device-width, initial-scale=1" name="viewport" />
	<meta content="Edit product" name="description" />
	<meta content="" name="author" />
	<?php include('common/head.php'); ?>
	<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.15/dist/summernote.min.css" rel="stylesheet">
	<style>
		.page-title { margin-top: 10px; }
		.product-form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(380px, 1fr)); gap: 24px; }
		.product-form-card { background: #ffffff; border: 1px solid #dde3ec; border-radius: 8px; padding: 24px; box-shadow: 0 6px 18px rgba(52, 73, 94, 0.08); }
		.product-form-card.full-width { grid-column: 1 / -1; }
		.product-form-card h5 { font-size: 13px; letter-spacing: 0.08em; text-transform: uppercase; color: #60718b; margin-top: 0; margin-bottom: 18px; }
		.product-form-card .form-group { margin-bottom: 18px; }
		.product-form-card .control-label { font-weight: 600; color: #2f3b52; }
		.product-form-card .help-block { font-size: 12px; color: #97a4b8; margin-top: 6px; }
		.product-form-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; }
		.product-thumb-preview { width: 100%; max-width: 240px; border-radius: 8px; border: 1px solid #e1e7f0; background: #f9fbfd; padding: 12px; display: flex; align-items: center; justify-content: center; }
		.product-thumb-preview img { max-width: 100%; border-radius: 6px; }
		.product-images-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 16px; }
		.product-images-grid img { width: 100%; border-radius: 6px; border: 1px solid #e1e7f0; }
		.specifications-table { width: 100%; border-collapse: collapse; }
		.specifications-table th, .specifications-table td { padding: 8px 12px; border-bottom: 1px solid #eef2f7; }
		.specifications-table th { background: #f4f6fb; text-transform: uppercase; font-size: 12px; letter-spacing: 0.05em; color: #5d6d8a; }
		/* Colorful card header for customer detail page */
		.customer-card-header {
			background: linear-gradient(90deg, #028d7aff 0%, #066c74ff 100%);
			color: #fff;
			padding: 4px 4px;
			border-radius: 8px 8px 0 0;
			font-size: 20px;
			font-weight: 700;
			letter-spacing: 0.08em;
			box-shadow: 0 2px 8px rgba(255,126,95,0.12);
			margin-bottom: 0;
			display: flex;
			align-items: center;
			gap: 12px;
		}
		.customer-card-header .fa {
			font-size: 22px;
			margin-right: 10px;
			opacity: 0.85;
		}
		/* Section card h4 headers with same color design as customer header */
	
		@media (max-width: 1366px) { .product-form-grid { grid-template-columns: 1fr; } }
		@media (max-width: 767px) { .product-form-card { padding: 18px; } }

		
	</style>
</head>
<body class="page-sidebar-closed-hide-logo page-content-white">
<?php include('common/manubar.php'); ?>
<div class="clearfix"></div>
<div class="page-container">
	<div class="page-sidebar-wrapper">
		<?php include('common/sidebar.php'); ?>
	</div>
	<div class="page-content-wrapper">
		<div class="page-content">
			<div class="page-bar">
				<ul class="page-breadcrumb">
					<li>
						<a href="index.php">Home</a>
						<i class="fa fa-circle"></i>
					</li>
					<li>
						<a href="manage-product.php">Products</a>
						<i class="fa fa-circle"></i>
					</li>
					<li>
						<span>Edit Product</span>
					</li>
				</ul>
			</div>

			<h3 class="page-title">Edit Product
				<small><?php echo escape($product['item_name']); ?></small>
			</h3>

			<?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
				<div class="alert alert-success"><i class="fa fa-check"></i> Product successfully updated!</div>
			<?php endif; ?>
			<?php if (isset($_GET['error'])): ?>
				<div class="alert alert-danger"><i class="fa fa-warning"></i> Error: <?php echo escape($_GET['error']); ?></div>
			<?php endif; ?>

			<div class="portlet light bordered form-fit">
				<div class="customer-card-header">
					<i class="fa fa-user"></i>
					Product Details
				</div>
				<div class="portlet-body form">
					<form class="form-horizontal form-bordered" id="frmEditProduct" method="POST" enctype="multipart/form-data" action="process/edit-product-process.php">
						<input type="hidden" name="pid" value="<?php echo escape($productId); ?>">
						<div class="form-body">
							<div class="product-form-grid">
								<div class="product-form-card">
									<h4 class="section-card h4">General Details</h4>
									<div class="row">
										<div class="col-md-6">
											<div class="form-group">
												<label class="control-label" for="pcode">Product Code</label>
												<input type="text" class="form-control" name="pcode" id="pcode" value="<?php echo escape($product['item_code']); ?>">
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label class="control-label" for="pname">Product Name<span style="color:#e7505a;">*</span></label>
												<input type="text" class="form-control" name="pname" id="pname" value="<?php echo escape($product['item_name']); ?>" required>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-6">
											<div class="form-group">
												<label class="control-label" for="pgroup">Group<span style="color:#e7505a;">*</span></label>
												<select class="form-control" name="pgroup" id="pgroup" onchange="handleProductGroupChange(this.value)" required>
													<?php echo $groupOptions; ?>
												</select>
												<span class="help-block">Selecting a group refreshes the type list.</span>
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label class="control-label" for="ptype">Type<span style="color:#e7505a;">*</span></label>
												<select class="form-control" name="ptype" id="ptype" onchange="handleProductTypeChange(this.value)" required>
													<?php echo $typeOptions; ?>
												</select>
												<span class="help-block">Choose the product type.</span>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-6">
											<div class="form-group">
												<label class="control-label" for="pbusinessunit">Business Unit</label>
                                                <select class="form-control" name="pbusinessunit" id="pbusinessunit">
                                                    <?php echo $businessUnitOptions; ?>
                                                </select>
                                                <span class="help-block">Optional business unit for product reporting.</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label" for="pcategory">Category</label>
												<select class="form-control" name="pcategory" id="pcategory">
													<?php echo $categoryOptions ?: '<option value="">Select Category</option>'; ?>
												</select>
											</div>
										</div>
									</div>
								</div>

								<div class="product-form-card">
									<h5 class="section-card h4">Pricing</h5>
									<div class="row">
										<div class="col-md-12">
											<div class="form-group">
												<label class="control-label" for="purchaseprice">Purchase Price<span style="color:#95a5a6;"> (Optional)</span></label>
												<div class="input-group">
													<span class="input-group-addon"><i class="fa"><?php echo escape($currencySymbol); ?>.</i></span>
													<input type="text" class="form-control autoprice" id="purchaseprice" name="purchaseprice" value="<?php echo formatAutoNumericValue($product['item_purchase_price']); ?>" data-a-sep="," data-a-dec=".">
												</div>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-12">
											<div class="form-group">
												<label class="control-label" for="normalsellingprice">Standard Selling Price<span style="color:#95a5a6;"> (Optional)</span></label>
												<div class="input-group">
													<span class="input-group-addon"><i class="fa"><?php echo escape($currencySymbol); ?>.</i></span>
													<input type="text" class="form-control autoprice" id="normalsellingprice" name="normalsellingprice" value="<?php echo formatAutoNumericValue($product['item_normal_selling_price']); ?>" data-a-sep="," data-a-dec=".">
												</div>
											</div>
										</div>
									</div>
									<div class="row">
									</div>
								</div>

								<div class="product-form-card">
									<h5 class="section-card h4">Logistics &amp; Compliance</h5>
									<div class="row">
										<div class="col-md-6">
											<div class="form-group">
												<label class="control-label" for="productWeight">Product Weight (Kg)</label>
												<div class="input-group">
													<input type="number" class="form-control" name="productWeight" id="productWeight" min="0" step="0.001" value="<?php echo formatNumberValue($product['item_weight'], 3); ?>">
													<span class="input-group-addon">Kg</span>
												</div>
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label class="control-label" for="pack_size">Pack Size</label>
												<input type="text" class="form-control" name="pack_size" id="pack_size" value="<?php echo escape($product['pack_size']); ?>" placeholder="e.g. 12 pcs / carton">
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-6">
											<div class="form-group">
												<label class="control-label" for="order_qty_min">Order Qty (Min)</label>
												<input type="number" class="form-control" name="order_qty_min" id="order_qty_min" min="0" step="0.01" value="<?php echo formatNumberValue($product['order_qty_min']); ?>">
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label class="control-label" for="order_qty_max">Order Qty (Max)</label>
												<input type="number" class="form-control" name="order_qty_max" id="order_qty_max" min="0" step="0.01" value="<?php echo formatNumberValue($product['order_qty_max']); ?>">
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-6">
											<div class="form-group">
												<label class="control-label" for="low_stock_qty">Low Stock Qty</label>
												<input type="number" class="form-control" name="low_stock_qty" id="low_stock_qty" min="0" step="1" value="<?php echo escape($product['low_stock_qty'] ?? 5); ?>">
												<span class="help-block">Reorder threshold used by low-stock alerts and reports.</span>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-6">
											<div class="form-group">
												<label class="control-label" for="acc_posting_grp_code">Accounting Posting Group</label>
												<input type="text" class="form-control" name="acc_posting_grp_code" id="acc_posting_grp_code" value="<?php echo escape($product['acc_posting_grp_code']); ?>">
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label class="control-label" for="vat">GST Inclusion</label>
												<select class="form-control" name="vat" id="vat">
													<option value="N" <?php echo ($product['item_vat'] === 'N') ? 'selected' : ''; ?>>No Included +Tax</option>
													<option value="Y" <?php echo ($product['item_vat'] === 'Y') ? 'selected' : ''; ?>>Included +Tax</option>
												</select>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-6">
											<div class="form-group">
												<label class="control-label" for="gst_vat_code">GST Code</label>
												<select class="form-control" name="gst_vat_code" id="gst_vat_code">
													<?php echo $vatOptions; ?>
												</select>
												<span class="help-block">Select the tax mapping for this product.</span>
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label class="control-label" for="productCod">Cash On Delivery</label>
												<select class="form-control" name="productCod" id="productCod">
													<option value="enable" <?php echo ($product['item_cod'] === 'enable') ? 'selected' : ''; ?>>Enable</option>
													<option value="disable" <?php echo ($product['item_cod'] === 'disable') ? 'selected' : ''; ?>>Disable</option>
												</select>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-6">
											<div class="form-group">
												<label class="control-label" for="warranty">Warranty Months</label>
												<select class="form-control" name="warranty" id="warranty">
													<?php echo $warrantyOptions; ?>
												</select>
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label class="control-label">Track Serial Numbers</label>
												<div class="radio-list">
													<label class="radio-inline"><input type="radio" name="sirial" value="Y" <?php echo ($hasSerial === 'Y') ? 'checked' : ''; ?>> Yes</label>
													<label class="radio-inline"><input type="radio" name="sirial" value="N" <?php echo ($hasSerial !== 'Y') ? 'checked' : ''; ?>> No</label>
												</div>
											</div>
										</div>
									</div>
								</div>

								<div class="product-form-card full-width" id="altUomCard">
									<h5 class="section-card h4">Alternative Units of Measure</h5>
									<p class="help-block" style="margin-bottom:12px;">The <strong>Unit of Measure</strong> selected on this form is the <strong>base UOM</strong> (qty per unit = 1). Add other units like Box, Carton, Pack and the conversion factor in base units. Mark one as default for purchase and one for sales.</p>
									<div id="baseUomDisplay" style="margin-bottom:10px;font-weight:600;color:#28527a;">Base UOM: <span id="baseUomLabel">(select Unit of Measure above)</span></div>
						<div class="row" style="margin-bottom: 18px;">
							<div class="col-md-6">
								<div class="form-group">
									<label class="control-label" for="unit_of_measure">Unit of Measure</label>
									<?php
									$currentBaseUom = $product['unit_of_measure'] ?? '';
									$baseUomOptions = $db->getRows('SELECT uom_id, uom_name FROM item_uom ORDER BY uom_name ASC') ?: [];
									$baseUomNames = array_map(function($r){ return $r['uom_name']; }, $baseUomOptions);
									if ($currentBaseUom !== '' && !in_array($currentBaseUom, $baseUomNames, true)) {
										$baseUomOptions[] = ['uom_id' => 0, 'uom_name' => $currentBaseUom];
									}
									?>
									<select class="form-control" name="unit_of_measure" id="unit_of_measure">
										<option value="">Select Unit of Measure</option>
										<?php foreach ($baseUomOptions as $uomRow): $uomName = $uomRow['uom_name']; $sel = ($currentBaseUom === $uomName) ? ' selected' : ''; ?>
											<option value="<?php echo htmlspecialchars($uomName, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $sel; ?>><?php echo htmlspecialchars($uomName, ENT_QUOTES, 'UTF-8'); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
							</div>
							<div class="col-md-6">
								<div class="form-group">
									<label class="control-label" for="additional_uoms">Additional UOM <span style="color:#95a5a6;font-weight:normal;">(used as options in Alternative Units of Measure)</span></label>
									<?php
									require_once(__DIR__ . '/include/uom_helper.php');
									$selectedAdditionalUoms = getProductAdditionalUomNames($db, $productId);
									$additionalUomRows = $db->getRows('SELECT uom_id, uom_name FROM item_uom ORDER BY uom_name ASC') ?: [];
									$additionalUomCandidates = array_map(function($r){ return $r['uom_name']; }, $additionalUomRows);
									foreach ($selectedAdditionalUoms as $n) { if (!in_array($n, $additionalUomCandidates, true)) { $additionalUomCandidates[] = $n; } }
									?>
									<select class="form-control" name="additional_uoms[]" id="additional_uoms" multiple size="5">
										<?php foreach ($additionalUomCandidates as $cand): $sel = in_array($cand, $selectedAdditionalUoms, true) ? ' selected' : ''; ?>
										<option value="<?php echo htmlspecialchars($cand, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $sel; ?>><?php echo htmlspecialchars($cand, ENT_QUOTES, 'UTF-8'); ?></option>
										<?php endforeach; ?>
									</select>
									<p class="help-block" style="margin-top:4px;">Hold Ctrl (Cmd on Mac) to select multiple. The base Unit of Measure is excluded automatically.</p>
								</div>
							</div>
						</div>
									<table class="table table-bordered" id="altUomTable" style="margin-bottom:8px;">
										<thead style="background:#f4f6fb;">
											<tr>
												<th style="width:32%">UOM</th>
												<th style="width:22%">Qty per UOM (in base)</th>
												<th style="width:18%;text-align:center;">Default Purchase</th>
												<th style="width:18%;text-align:center;">Default Sales</th>
												<th style="width:10%;text-align:center;">&nbsp;</th>
											</tr>
										</thead>
										<tbody></tbody>
									</table>
									<button type="button" class="btn btn-sm btn-default" id="btnAddAltUom"><i class="fa fa-plus"></i> Add Unit</button>
									<input type="hidden" name="alt_uoms_json" id="alt_uoms_json" value='<?php echo htmlspecialchars(json_encode($existingAltUoms), ENT_QUOTES, "UTF-8"); ?>'>
									<script>
										window.__ALL_UOMS__ = <?php echo json_encode($uoms); ?>;
										window.__EXISTING_ALT_UOMS__ = <?php echo json_encode($existingAltUoms); ?>;
									</script>
								</div>

								<div class="product-form-card">
									<h5 class="section-card h4">Status &amp; Fulfilment</h5>
									<div class="form-group">
										<label class="control-label" for="productStatus">Product Status</label>
										<select class="form-control" name="productStatus" id="productStatus">
											<option value="Normal" <?php echo ($productStatus === 'Normal') ? 'selected' : ''; ?>>Normal</option>
											<option value="Offline" <?php echo ($productStatus === 'Offline') ? 'selected' : ''; ?>>Offline</option>
											<option value="OutofStock" <?php echo ($productStatus === 'OutofStock') ? 'selected' : ''; ?>>Out of Stock</option>
										</select>
									</div>
									<div class="form-group">
										<label class="control-label" for="ImmediatePickup">Immediate Pickup</label>
										<select class="form-control" name="ImmediatePickup" id="ImmediatePickup">
											<option value="No" <?php echo ($immediatePickup === 'No') ? 'selected' : ''; ?>>No</option>
											<option value="Yes" <?php echo ($immediatePickup === 'Yes') ? 'selected' : ''; ?>>Yes</option>
										</select>
									</div>
									<div class="form-group">
										<label class="control-label" for="is_raw_material">Raw Material</label>
										<select class="form-control" name="is_raw_material" id="is_raw_material">
											<option value="0" <?php echo (($product['is_raw_material'] ?? 0) == 0) ? 'selected' : ''; ?>>No</option>
											<option value="1" <?php echo (($product['is_raw_material'] ?? 0) == 1) ? 'selected' : ''; ?>>Yes</option>
										</select>
										<span class="help-block">Mark as raw material to show in Purchase Order items.</span>
									</div>
									<div class="form-group">
										<label class="control-label">Allow In Sales</label>
										<div>
											<label style="font-weight:normal;"><input type="checkbox" name="allow_in_sales" id="allow_in_sales" value="1" <?php echo (($product['allow_in_sales'] ?? 1) == 1) ? 'checked' : ''; ?>> Allow this product to be added to Sales / Cart Orders</label>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label">Allow In GRN</label>
										<div>
											<label style="font-weight:normal;"><input type="checkbox" name="allow_in_grn" id="allow_in_grn" value="1" <?php echo (($product['allow_in_grn'] ?? 1) == 1) ? 'checked' : ''; ?>> Allow this product to be added to Purchase / GRN</label>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label" for="batch_tracking">Batch / Serial Tracking</label>
										<select class="form-control" name="batch_tracking" id="batch_tracking">
											<option value="NONE" <?php echo (($product['batch_tracking'] ?? 'NONE') === 'NONE') ? 'selected' : ''; ?>>Disabled</option>
											<option value="BATCH" <?php echo (($product['batch_tracking'] ?? 'NONE') === 'BATCH') ? 'selected' : ''; ?>>Batch No Tracking</option>
											<option value="SERIAL" <?php echo (($product['batch_tracking'] ?? 'NONE') === 'SERIAL') ? 'selected' : ''; ?>>Serial No Tracking</option>
										</select>
										<span class="help-block">Enable to track batch numbers or serial numbers during GRN, transfers, and issues.</span>
									</div>
								</div>

								<div class="product-form-card full-width">
									<h5 class="section-card h4">Additional Product Information</h5>
									<div class="row">
										<div class="col-lg-6 col-md-12">
											<div class="form-group">
												<label class="control-label" for="wholesale_price">Wholesale Price</label>
												<input type="number" class="form-control autoprice" placeholder="0.00" id="wholesale_price" name="wholesale_price" value="<?php echo formatAutoNumericValue($product['wholesale_price'] ?? ''); ?>" data-a-sep="," data-a-dec="." step="0.01" min="0">
											</div>
										</div>
										<div class="col-lg-6 col-md-12">
											<div class="form-group">
												<label class="control-label" for="retail_price">Retail Price</label>
												<input type="number" class="form-control autoprice" placeholder="0.00" id="retail_price" name="retail_price" value="<?php echo formatAutoNumericValue($product['retail_price'] ?? ''); ?>" data-a-sep="," data-a-dec="." step="0.01" min="0">
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-lg-6 col-md-12">
											<div class="form-group">
												<label class="control-label" for="item_weight_g">Weight (grams)</label>
												<input type="number" class="form-control" name="item_weight_g" id="item_weight_g" placeholder="0" value="<?php echo escape($product['item_weight_g'] ?? ''); ?>" min="0">
											</div>
										</div>
										<div class="col-lg-6 col-md-12">
											<div class="form-group">
												<label class="control-label" for="pack_weight_g">Pack Weight (grams)</label>
												<input type="number" class="form-control" name="pack_weight_g" id="pack_weight_g" placeholder="0" value="<?php echo escape($product['pack_weight_g'] ?? ''); ?>" min="0">
											</div>
										</div>
									</div>
									<div class="row">
                                        <div class="col-lg-6 col-md-12">
                                            <div class="form-group">
                                                <label class="control-label" for="minimum_order">Minimum Order Quantity</label>
                                                <input type="number" class="form-control" name="minimum_order" id="minimum_order" placeholder="0" value="<?php echo escape($product['minimum_order'] ?? ''); ?>" min="0">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
										<div class="col-lg-6 col-md-12">
											<div class="form-group">
												<label class="control-label" for="pack_type">Pack Type</label>
												<select class="form-control" name="pack_type" id="pack_type">
													<option value="Bag" <?php echo (($product['pack_type'] ?? '') === 'Bag') ? 'selected' : ''; ?>>Bag</option>
													<option value="Box" <?php echo (($product['pack_type'] ?? '') === 'Box') ? 'selected' : ''; ?>>Box</option>
													<option value="Carton" <?php echo (($product['pack_type'] ?? '') === 'Carton') ? 'selected' : ''; ?>>Carton</option>
													<option value="Packet" <?php echo (($product['pack_type'] ?? '') === 'Packet') ? 'selected' : ''; ?>>Packet</option>
													<option value="Tray" <?php echo (($product['pack_type'] ?? '') === 'Tray') ? 'selected' : ''; ?>>Tray</option>
													<option value="Bottle" <?php echo (($product['pack_type'] ?? '') === 'Bottle') ? 'selected' : ''; ?>>Bottle</option>
												</select>
											</div>
										</div>
										<div class="col-lg-6 col-md-12">
											<div class="form-group">
												<label class="control-label" for="live">Live Status</label>
												<select class="form-control" name="live" id="live">
													<option value="yes" <?php echo (($product['live'] ?? 'yes') === 'yes') ? 'selected' : ''; ?>>Live</option>
													<option value="no" <?php echo (($product['live'] ?? 'yes') === 'no') ? 'selected' : ''; ?>>Not Live</option>
												</select>
											</div>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label" for="nutritional_label">Nutritional Label</label>
										<input type="text" class="form-control" name="nutritional_label" id="nutritional_label" value="<?php echo escape($product['nutritional_label'] ?? ''); ?>" placeholder="e.g. Contains gluten, nuts">
									</div>
									<div class="form-group">
										<label class="control-label" for="product_specification">Product Specification</label>
										<input type="text" class="form-control" name="product_specification" id="product_specification" value="<?php echo escape($product['product_specification'] ?? ''); ?>" placeholder="e.g. Organic, Halal certified">
									</div>
									<div class="form-group">
										<label class="control-label" for="default_label">Default Label</label>
										<input type="text" class="form-control" name="default_label" id="default_label" value="<?php echo escape($product['default_label'] ?? ''); ?>" placeholder="e.g. Fresh Baked Daily">
									</div>
									<div class="form-group">
										<label class="control-label" for="seasonal_rule">Seasonal Rule</label>
										<input type="text" class="form-control" name="seasonal_rule" id="seasonal_rule" value="<?php echo escape($product['seasonal_rule'] ?? ''); ?>" placeholder="e.g. Available only in winter">
									</div>
									<div class="form-group">
										<label class="control-label" for="food_declarations">Food Declarations</label>
										<textarea class="form-control" rows="3" name="food_declarations" id="food_declarations" placeholder="Allergen information, ingredients"><?php echo escape($product['food_declarations'] ?? ''); ?></textarea>
									</div>
									<div class="form-group">
										<label class="control-label">Availability Days</label>
										<div class="row">
											<div class="col-sm-2"><label><input type="checkbox" name="avail_monday" value="1" <?php echo (($product['avail_monday'] ?? 1) == 1) ? 'checked' : ''; ?>> Mon</label></div>
											<div class="col-sm-2"><label><input type="checkbox" name="avail_tuesday" value="1" <?php echo (($product['avail_tuesday'] ?? 1) == 1) ? 'checked' : ''; ?>> Tue</label></div>
											<div class="col-sm-2"><label><input type="checkbox" name="avail_wednesday" value="1" <?php echo (($product['avail_wednesday'] ?? 1) == 1) ? 'checked' : ''; ?>> Wed</label></div>
											<div class="col-sm-2"><label><input type="checkbox" name="avail_thursday" value="1" <?php echo (($product['avail_thursday'] ?? 1) == 1) ? 'checked' : ''; ?>> Thu</label></div>
											<div class="col-sm-2"><label><input type="checkbox" name="avail_friday" value="1" <?php echo (($product['avail_friday'] ?? 1) == 1) ? 'checked' : ''; ?>> Fri</label></div>
											<div class="col-sm-2"><label><input type="checkbox" name="avail_saturday" value="1" <?php echo (($product['avail_saturday'] ?? 1) == 1) ? 'checked' : ''; ?>> Sat</label></div>
										</div>
										<div class="row">
											<div class="col-sm-2"><label><input type="checkbox" name="avail_sunday" value="1" <?php echo (($product['avail_sunday'] ?? 1) == 1) ? 'checked' : ''; ?>> Sun</label></div>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label" for="hide_to_all_customers">Hide to All Customers</label>
										<select class="form-control" name="hide_to_all_customers" id="hide_to_all_customers">
											<option value="0" <?php echo (($product['hide_to_all_customers'] ?? 0) == 0) ? 'selected' : ''; ?>>No</option>
											<option value="1" <?php echo (($product['hide_to_all_customers'] ?? 0) == 1) ? 'selected' : ''; ?>>Yes</option>
										</select>
									</div>
									<div class="form-group">
										<label class="control-label" for="sale_or_return">Sale or Return</label>
										<select class="form-control" name="sale_or_return" id="sale_or_return">
											<option value="0" <?php echo (($product['sale_or_return'] ?? 0) == 0) ? 'selected' : ''; ?>>No</option>
											<option value="1" <?php echo (($product['sale_or_return'] ?? 0) == 1) ? 'selected' : ''; ?>>Yes</option>
										</select>
									</div>
								</div>

								<div class="product-form-card full-width">
									<h5 class="section-card h4">Media &amp; Descriptions</h5>
									<div class="row">
										<div class="col-md-4">
											<div class="form-group">
												<label class="control-label">Current Thumbnail</label>
												<div class="product-thumb-preview">
													<?php if ($thumbnailUrl !== ''): ?>
														<img src="<?php echo escape($thumbnailUrl); ?>" alt="Product Thumbnail">
													<?php else: ?>
														<span>No thumbnail uploaded</span>
													<?php endif; ?>
												</div>
											</div>
										</div>
										<div class="col-md-8">
											<div class="form-group">
												<label class="control-label" for="img1">Replace Thumbnail</label>
												<div class="fileinput fileinput-new" data-provides="fileinput">
													<span class="btn btn-default btn-file">
														<span class="fileinput-new">Choose</span>
														<span class="fileinput-exists">Change</span>
														<input type="file" name="img1" id="img1">
													</span>
													<span class="fileinput-filename"></span>
													<a href="#" class="close fileinput-exists" data-dismiss="fileinput" style="float: none">×</a>
												</div>
												<span class="help-block">Leave empty to keep current image.</span>
											</div>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label" for="discription">Description</label>
										<textarea class="form-control" rows="4" name="discription" id="discription"><?php echo escape($product['item_discription']); ?></textarea>
									</div>
									<div class="form-group">
										<label class="control-label" for="discription2">Description 2</label>
										<textarea class="form-control" rows="4" name="discription2" id="discription2"><?php echo escape($product['item_discription_2'] ?? ''); ?></textarea>
									</div>
								</div>
								<div class="product-form-card full-width" id="priceTiersCard">
									<h5 class="section-card h4"><i class="fa fa-tags"></i> Qty Price Breaks <small style="font-weight:400; color:#97a4b8; font-size:12px; text-transform:none; letter-spacing:0;">— set a lower price when a customer orders above a certain quantity</small></h5>
									<input type="hidden" name="price_tiers_json" id="price_tiers_json" value="<?php
										$existingTiers = [];
										try {
											$tableExists = $db->getRow("SHOW TABLES LIKE 'item_price_tiers'");
											if ($tableExists) {
												$existingTiers = $db->getRows('SELECT min_qty, unit_price FROM item_price_tiers WHERE item_id = ? ORDER BY min_qty ASC', [$productId]) ?: [];
											}
										} catch (Exception $e) { $existingTiers = []; }
										echo htmlspecialchars(json_encode($existingTiers), ENT_QUOTES);
									?>">
									<table class="table table-bordered" id="priceTiersTable" style="max-width:520px;">
										<thead>
											<tr style="background:#f4f6fb;">
												<th style="width:180px;">Min Qty (≥)</th>
												<th>Unit Price</th>
												<th style="width:50px;"></th>
											</tr>
										</thead>
										<tbody id="priceTiersTbody"></tbody>
									</table>
									<button type="button" class="btn btn-primary btn-sm" id="btnAddTier"><i class="fa fa-plus"></i> Add Tier</button>
									<p class="help-block" style="margin-top:8px;">Add rows to set a lower price when a customer orders above a certain quantity.</p>
								</div>
							</div>
						</div>
					<!-- /product-form-grid -->						<div class="form-actions">
							<div class="product-form-actions">
								<button type="button" class="btn default" onclick="window.location.href='product-details.php?pid=<?php echo escape($productId); ?>'">Cancel</button>
								<button type="submit" class="btn blue" id="btnSubmit">
									<i class="fa fa-check"></i> Update Product
								</button>
							</div>
						</div>
						<div id="response"></div>
					</form>
				</div>
			</div>

			<?php if (!empty($productImages)): ?>
				<div class="portlet light bordered">
					<div class="portlet-title">
						<div class="caption">
							<i class="fa fa-image"></i>
							<span class="caption-subject font-dark uppercase">Gallery Images</span>
						</div>
					</div>
					<div class="portlet-body">
						<div class="product-images-grid">
							<?php foreach ($productImages as $image):
								$pathPrefix = $image['imagePath'] ?? '';
								$pathFile = $image['image'] ?? '';
								$path = '';
								if ($pathFile !== '') {
									$path = rtrim($pathPrefix, '/') . '/' . ltrim($pathFile, '/');
								}
								?>
								<div>
									<?php if ($path !== '' && $path !== '/'): ?>
										<img src="<?php echo escape($path); ?>" alt="Gallery Image">
									<?php else: ?>
										<span>No image path configured</span>
									<?php endif; ?>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<?php if (!empty($productSpecifications)): ?>
				<div class="portlet light bordered">
					<div class="portlet-title">
						<div class="caption">
							<i class="fa fa-list"></i>
							<span class="caption-subject font-dark uppercase">Specifications</span>
						</div>
					</div>
					<div class="portlet-body">
						<div class="table-responsive">
							<table class="specifications-table">
								<thead>
								<tr>
									<th>Attribute</th>
									<th>Value</th>
								</tr>
								</thead>
								<tbody>
								<?php foreach ($productSpecifications as $spec): ?>
									<tr>
										<td><?php echo escape($spec['spec_key']); ?></td>
										<td><?php echo escape($spec['spec_value']); ?></td>
									</tr>
								<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			<?php endif; ?>

		</div>
	</div>
</div>

<?php include('common/footer.php'); ?>

<!--[if lt IE 9]>
<script src="assets/global/plugins/respond.min.js"></script>
<script src="assets/global/plugins/excanvas.min.js"></script>
<![endif]-->
<script src="assets/global/plugins/jquery.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/js.cookie.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/bootstrap-hover-dropdown/bootstrap-hover-dropdown.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/jquery-slimscroll/jquery.slimscroll.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/jquery.blockui.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/uniform/jquery.uniform.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/bootstrap-switch/js/bootstrap-switch.min.js" type="text/javascript"></script>
<script src="assets/global/scripts/datatable.js" type="text/javascript"></script>
<script src="assets/global/plugins/datatables/datatables.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.js" type="text/javascript"></script>
<script src="assets/global/scripts/app.min.js" type="text/javascript"></script>
<script src="assets/pages/scripts/table-datatables-responsive.min.js" type="text/javascript"></script>
<script src="assets/layouts/layout/scripts/layout.min.js" type="text/javascript"></script>
<script src="assets/layouts/layout/scripts/demo.min.js" type="text/javascript"></script>
<script src="assets/layouts/global/scripts/quick-sidebar.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/numaricFunction/autoNumeric.js" type="text/javascript"></script>
<script src="assets/global/plugins/notification/jquery.bootstrap-growl.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.15/dist/summernote.min.js"></script>
<script src="assets/custom/product-alt-uom.js"></script>

<script>
	(function ($) {
		function initializeEditors() {
			$('#discription').summernote();
			$('#discription2').summernote();
		}

		function initAutoNumeric() {
			$('.autoprice').autoNumeric('init');
		}

		function loadTypes(groupId, selectedType, selectedCategory) {
			$('#ptype').html('<option value="">Loading types...</option>');
			$('#pcategory').html('<option value="">Select Category</option>');

			if (!groupId) {
				$('#ptype').html('<option value="">Select Type</option>');
				$('#pcategory').html('<option value="">Select Category</option>');
				return;
			}

			$.ajax({
				url: 'fetch_type.php',
				method: 'POST',
				cache: false,
				dataType: 'html',
				data: { groupId: groupId },
				success: function (data) {
					$('#ptype').html(data && $.trim(data) !== '' ? data : '<option value="">Select Type</option>');
					if (selectedType) {
						$('#ptype').val(selectedType);
					}
					loadCategories($('#ptype').val(), selectedCategory);
				},
				error: function () {
					$('#ptype').html('<option value="">Select Type</option>');
					$('#pcategory').html('<option value="">Select Category</option>');
				}
			});
		}

		function loadCategories(typeId, selectedCategory) {
			$('#pcategory').html('<option value="">Loading categories...</option>');

			if (!typeId) {
				$('#pcategory').html('<option value="">Select Category</option>');
				return;
			}

			$.ajax({
				url: 'fetch_category.php',
				method: 'POST',
				cache: false,
				dataType: 'html',
				data: { typeId: typeId },
				success: function (data) {
					$('#pcategory').html(data && $.trim(data) !== '' ? data : '<option value="">Select Category</option>');
					if (selectedCategory) {
						$('#pcategory').val(selectedCategory);
					}
				},
				error: function () {
					$('#pcategory').html('<option value="">Select Category</option>');
				}
			});
		}

		function syncRawMaterialFromGroup() {
			var selectedGroupText = $.trim($('#pgroup option:selected').text());
			if (/raw\s*mat/i.test(selectedGroupText)) {
				$('#is_raw_material').val('1').trigger('change');
			}
		}

		window.handleProductGroupChange = function (groupId) {
			syncRawMaterialFromGroup();
			loadTypes(groupId, null, null);
		};

		window.handleProductTypeChange = function (typeId) {
			loadCategories(typeId, null);
		};

		$(document).ready(function () {
			initializeEditors();
			initAutoNumeric();

			var initialType = <?php echo json_encode((string) ($product['item_type'] ?? '')); ?>;
			var initialCategory = <?php echo json_encode((string) ($product['item_category'] ?? '')); ?>;

			$('#pgroup').on('change', function () {
				syncRawMaterialFromGroup();
				loadTypes($(this).val(), null, null);
			});

			$('#ptype').on('change', function () {
				loadCategories($(this).val(), null);
			});

			syncRawMaterialFromGroup();

			// Ensure dependent selects refresh on load
			if ($('#pgroup').val()) {
				loadTypes($('#pgroup').val(), initialType, initialCategory);
			}

			$('#frmEditProduct').on('submit', function (e) {
				e.preventDefault();

				var formData = new FormData(this);
				$('#btnSubmit').prop('disabled', true);

				$.ajax({
					type: 'POST',
					url: 'process/edit-product-process.php',
					data: formData,
					dataType: 'json',
					contentType: false,
					cache: false,
					processData: false,
					success: function (data) {
						$('#btnSubmit').prop('disabled', false);

						var message = data.message || 'Updated';
						var type = data.status ? 'success' : 'danger';

						$.bootstrapGrowl(message, {
							type: type,
							align: 'right',
							delay: 4000
						});

						if (data.status) {
							$('#response').html('<div class="alert alert-success">' + message + '</div>');
							window.location.href = 'manage-product.php?msg=updated';
						} else {
							$('#response').html('<div class="alert alert-danger">' + message + '</div>');
						}
					},
					error: function (xhr) {
						$('#btnSubmit').prop('disabled', false);
						var message = 'Unexpected error occurred';
						if (xhr.responseJSON && xhr.responseJSON.message) {
							message = xhr.responseJSON.message;
						} else if (xhr.responseText) {
							message = xhr.responseText;
						}
						$.bootstrapGrowl(message, {
							type: 'danger',
							align: 'right',
							delay: 4000
						});
						$('#response').html('<div class="alert alert-danger">' + message + '</div>');
					}
				});
			});
		});
	})(jQuery);
</script>
<script>
// ── Qty Price Tiers ──────────────────────────────────────────
(function() {
    var tiers = [];
    try { tiers = JSON.parse($('#price_tiers_json').val() || '[]'); } catch(e) { tiers = []; }

    function syncHidden() {
        $('#price_tiers_json').val(JSON.stringify(tiers));
    }

    function renderTiers() {
        var tbody = $('#priceTiersTbody');
        tbody.empty();
        if (tiers.length === 0) {
            tbody.append('<tr id="priceTiersEmpty"><td colspan="3" style="color:#aaa; font-style:italic; text-align:center;">No tiers set — standard price always applies</td></tr>');
            return;
        }
        tiers.forEach(function(t, i) {
            tbody.append(
                '<tr data-idx="' + i + '">' +
                '<td><input type="number" class="form-control input-sm tier-qty" min="1" step="1" value="' + t.min_qty + '" style="width:110px;"></td>' +
                '<td><input type="number" class="form-control input-sm tier-price" min="0" step="0.01" value="' + t.unit_price + '" style="width:120px;"></td>' +
                '<td style="text-align:center;"><button type="button" class="btn btn-danger btn-xs tier-remove" title="Remove"><i class="fa fa-trash"></i></button></td>' +
                '</tr>'
            );
        });
        syncHidden();
    }

    renderTiers();

    $('#btnAddTier').on('click', function() {
        tiers.push({ min_qty: 1, unit_price: 0 });
        renderTiers();
        $('#priceTiersTbody tr:last .tier-qty').focus().select();
    });

    $(document).on('change input', '.tier-qty', function() {
        var idx = $(this).closest('tr').data('idx');
        tiers[idx].min_qty = Math.max(1, parseInt($(this).val()) || 1);
        syncHidden();
    });

    $(document).on('change input', '.tier-price', function() {
        var idx = $(this).closest('tr').data('idx');
        tiers[idx].unit_price = Math.max(0, parseFloat($(this).val()) || 0);
        syncHidden();
    });

    $(document).on('click', '.tier-remove', function() {
        var idx = $(this).closest('tr').data('idx');
        tiers.splice(idx, 1);
        renderTiers();
    });
})();
</script>

</body>
</html>




