<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('include/database.php');
include('include/check_login.php');

requirePermission('settings.permissions');

$headers = [
    'item_code',
    'item_name',
    'item_group',
    'item_type',
    'item_category',
    'item_business_unit',
    'item_discription',
    'item_uom',
    'unit_of_measure',
    'order_qty_min',
    'order_qty_max',
    'item_purchase_price',
    'item_min_selling_price',
    'item_normal_selling_price',
    'others_selling_price',
    'item_cash_selling_price',
    'item_cradit_selling_price',
    'item_promotion_status',
    'item_promotion_price',
    'item_discount',
    'item_active',
    'item_warranty',
    'item_barcode',
    'is_hamper',
    'item_has_sirial',
    'item_vat',
    'item_dispay_home',
    'item_product_of_day',
    'item_cod',
    'item_mode',
    'item_weight',
    'low_stock_qty',
    'pack_size',
    'acc_posting_grp_code',
    'gst_vat_code',
    'immediate_pickups',
    'nutritional_label',
    'sale_or_return',
    'product_specification',
    'live',
    'hide_to_all_customers',
    'wholesale_price',
    'retail_price',
    'item_weight_g',
    'pack_weight_g',
    'minimum_order',
    'description',
    'default_label',
    'food_declarations',
    'seasonal_rule',
    'avail_monday',
    'avail_tuesday',
    'avail_wednesday',
    'avail_thursday',
    'avail_friday',
    'avail_saturday',
    'avail_sunday',
    'pack_type',
    'is_raw_material',
    'batch_tracking',
    'allow_in_sales',
    'allow_in_grn',
    'additional_uoms',
];

$sampleRows = [
    // New item (full details)
    ['ITM-1001', 'Butter Croissant', 1, 1, 2, '', 'Freshly baked butter croissant', 1, 'Each', 1, 100, 2.50, 3.80, 4.50, 4.50, 4.50, 4.50, 'N', 0.00, 0, 'Y', 0, '9300000000001', 0, 0, 0, 0, 0, 0, 0, 0.12, 10, 12, 'STD', 'GST10', 0, '', '', '', 1, 0, 4.20, 4.80, 120, 150, 1, '', '', '', '', 1, 1, 1, 1, 1, 0, 0, 'BOX', 0, 0, 1, 1, ''],
    // New item (minimal)
    ['ITM-1002', 'Chocolate Muffin', 1, 1, 2, '', '', 1, 'Each', 1, 100, 1.90, 3.00, 3.40, 3.40, 3.40, 3.40, 'N', 0.00, 0, 'Y', 0, '9300000000002', 0, 0, 0, 0, 0, 0, 0, 0.08, 5, 6, 'STD', '', 0, '', '', '', 1, 0, 3.00, 3.70, 80, 100, 1, '', '', '', '', 1, 1, 1, 1, 1, 0, 0, 'BOX', 0, 0, 1, 1, ''],
    // Update existing item (only changed fields; leave others blank)
    ['ITM-1001', 'Butter Croissant - Updated Name', '', '', '', '', '', '', '', '', '', 2.70, '', 4.80, '', '', '', '', '', '', 'Y', '', '', '', '', '', '', '', '', '', '', '', '', '', 'GST10', '', '', '', '', 1, '', 4.50, 5.00, '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
];

if (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment;filename="item_master_bulk_upload_template.csv"');
header('Cache-Control: max-age=0');

$output = fopen('php://output', 'w');
if ($output === false) {
    exit;
}

fputs($output, "\xEF\xBB\xBF");
fputcsv($output, $headers);
foreach ($sampleRows as $sampleRow) {
    fputcsv($output, $sampleRow);
}

fclose($output);
exit;
