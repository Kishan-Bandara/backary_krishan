<?php
ob_start();
error_reporting (E_ALL ^ E_NOTICE);

session_start();
include('include/database.php');
include('include/check_login.php');
include('get_url.php');

function loadPhpSpreadsheetForExport()
{
    static $loaded = false;
    if ($loaded) {
        return true;
    }

    $autoloadPaths = [
        __DIR__ . '/vendor/autoload.php',
        __DIR__ . '/DB Migration/vendor/autoload.php',
    ];

    foreach ($autoloadPaths as $autoloadPath) {
        if (file_exists($autoloadPath)) {
            require_once $autoloadPath;
            $loaded = true;
            return true;
        }
    }

    return false;
}

function exportProductsXlsx(array $products)
{
    if (!loadPhpSpreadsheetForExport()) {
        header('HTTP/1.1 500 Internal Server Error');
        echo 'PhpSpreadsheet library not found. Please install via composer.';
        exit;
    }

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Products');

    $sheet->setCellValue('A1', 'Products Export');
    $sheet->setCellValue('A2', 'Generated: ' . date('Y-m-d H:i:s'));
    $headers = ['Product ID', 'Product Code', 'Product Name', 'Group', 'Type', 'Category', 'Stock Qty', 'Cost', 'Price', 'Unit'];
    $row = 4;
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . $row, $header);
        $col++;
    }

    $currentRow = 5;
    foreach ($products as $product) {
        $sheet->setCellValue('A' . $currentRow, $product['item_id'] ?? '');
        $sheet->setCellValue('B' . $currentRow, $product['item_code'] ?? '');
        $sheet->setCellValue('C' . $currentRow, $product['item_name'] ?? '');
        $sheet->setCellValue('D' . $currentRow, $product['group_name'] ?? '');
        $sheet->setCellValue('E' . $currentRow, $product['type_name'] ?? '');
        $sheet->setCellValue('F' . $currentRow, $product['category_name'] ?? '');
        $sheet->setCellValue('G' . $currentRow, $product['qty_balance'] ?? 0);
        $sheet->setCellValue('H' . $currentRow, $product['item_purchase_price'] ?? 0);
        $sheet->setCellValue('I' . $currentRow, $product['item_normal_selling_price'] ?? 0);
        $sheet->setCellValue('J' . $currentRow, $product['item_uom'] ?? '');
        $currentRow++;
    }

    foreach (range('A', 'J') as $columnId) {
        $sheet->getColumnDimension($columnId)->setAutoSize(true);
    }

    $filename = 'Products_' . date('Ymd_His') . '.xlsx';
    if (ob_get_length()) {
        ob_end_clean();
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

if (isset($_GET['export']) && $_GET['export'] === 'xlsx') {
    exportProductsXlsx(getContent());
}

//Delete values
if(isset($_GET['deleteID']))
{
   $deleteid = $_GET['deleteID'];

if($deleteid > 0)
{

     $db = new Database();
     $query_check_qty = $db->getRow('SELECT SUM(ft_blanace) as blanace FROM fifo WHERE ft_item = ?',[$deleteid]);
     $check_qty_frm_fifo = $query_check_qty['blanace'];

     $query_check_grn = $db->getRow('SELECT * FROM grn_details WHERE grn_d_item_id = ?',[$deleteid]);
     $check_grn_item_id = $query_check_grn['grn_d_id'];

   if($check_qty_frm_fifo>0 || $check_grn_item_id>0)
    {
        $message = "You can not delete this product";

    }
    else
    {

       
        $deleterowquery = $db->deleteRow('DELETE FROM item_master WHERE item_id = ?',[$deleteid]);
        $message = "Product has been Deleted";
    
       //redirect('manage-product.php');
    }


}
else
{

    $message = "check your  Product ID!";

}
}
//Database eken Table ekata Values daaganna Function eka
function getContent() {
    $db = new Database();
    $locationId = isset($_SESSION['location']) ? (int) $_SESSION['location'] : 0;
    $query = $db->getRows(
        'SELECT im.*, gm.group_name, tm.type_name, cm.category_name,
                COALESCE((
                    SELECT SUM(f.ft_blanace)
                    FROM fifo f
                    WHERE f.ft_item = im.item_id AND f.ft_location = ?
                ), 0) AS qty_balance
         FROM item_master im
         LEFT JOIN gorup_master gm ON gm.group_id = im.item_group
         LEFT JOIN type_master tm ON tm.type_id = im.item_type
         LEFT JOIN category_master cm ON cm.category_id = im.item_category
         ORDER BY im.item_name ASC',
        [$locationId]
    );
    return $query;
}

function getTaxOptions() {
    $db = new Database();
    return $db->getRows('SELECT code, name, rate FROM product_vat_master ORDER BY name ASC, code ASC');
}

function buildTaxOptionsMarkup($taxOptions, $selectedCode) {
    $output = '<option value="">No Tax</option>';
    foreach ($taxOptions as $taxOption) {
        $code = htmlspecialchars((string) ($taxOption['code'] ?? ''), ENT_QUOTES, 'UTF-8');
        $name = trim((string) ($taxOption['name'] ?? ''));
        $rate = number_format((float) ($taxOption['rate'] ?? 0), 2);
        $label = htmlspecialchars(($name !== '' ? $name : $code) . ' (' . $rate . '%)', ENT_QUOTES, 'UTF-8');
        $selected = ((string) $selectedCode === (string) ($taxOption['code'] ?? '')) ? ' selected' : '';
        $output .= '<option value="' . $code . '"' . $selected . '>' . $label . '</option>';
    }
    return $output;
}

$data = getContent();
$taxOptions = getTaxOptions();
if (empty($_SESSION['bulk_product_price_csrf'])) {
    $_SESSION['bulk_product_price_csrf'] = bin2hex(random_bytes(32));
}
$bulkPriceCsrf = $_SESSION['bulk_product_price_csrf'];




?>

<!DOCTYPE html>

<!--[if IE 8]> <html lang="en" class="ie8 no-js"> <![endif]-->
<!--[if IE 9]> <html lang="en" class="ie9 no-js"> <![endif]-->
<!--[if !IE]><!-->
<html lang="en">
    <!--<![endif]-->
    <!-- BEGIN HEAD -->


<head>
        <meta charset="utf-8" />
        <title>Manage Products</title>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta content="width=device-width, initial-scale=1" name="viewport" />
        <meta content="" name="description" />
        <meta content="" name="author" />
        <?php include('common/head.php'); ?>        <style>
            .dt-buttons .buttons-print,
            .dt-buttons .buttons-pdf,
            .dt-buttons .buttons-csv {
                display: none !important;
            }
        </style>         <!-- BEGIN PAGE LEVEL PLUGINS -->
        <link href="assets/global/plugins/datatables/datatables.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.css" rel="stylesheet" type="text/css" />
        <!-- END PAGE LEVEL PLUGINS -->
        <style>
            .bulk-price-modal .modal-dialog { width: 96%; max-width: 1500px; }
            .bulk-price-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 12px; }
            .bulk-price-toolbar .btn + .btn { margin-left: 8px; }
            .bulk-price-count { font-weight: 600; color: #3f5873; }
            .bulk-price-wrap { max-height: 68vh; overflow: auto; border: 1px solid #d9e1ea; }
            .bulk-price-table { width: 100%; min-width: 1100px; border-collapse: collapse; }
            .bulk-price-table thead th { background: #5b9bd5; color: #fff; border: 1px solid #7aa9d8; padding: 10px 12px; vertical-align: middle; position: sticky; z-index: 3; }
            .bulk-price-table thead tr:first-child th { top: 0; font-size: 13px; font-weight: 700; }
            .bulk-price-table thead tr:nth-child(2) th { top: 40px; font-weight: 500; }
            .bulk-price-table thead tr:nth-child(3) th { top: 81px; background: #6fa6d8; }
            .bulk-price-table tbody td { border: 1px solid #d9e1ea; padding: 8px 12px; background: #fff; }
            .bulk-price-table tbody tr:nth-child(even) td { background: #f6fbff; }
            .bulk-price-table tbody tr.bulk-price-row-dirty td { background: #fff5d6; }
            .bulk-price-table .filter-input,
            .bulk-price-table .price-input,
            .bulk-price-table .tax-select { width: 100%; height: 34px; border: 1px solid #c6d4e1; border-radius: 4px; padding: 6px 8px; color: #243240; }
            .bulk-price-table .price-input { text-align: right; }
            .bulk-price-alert { display: none; margin-bottom: 12px; }
            @media (max-width: 991px) {
                .bulk-price-modal .modal-dialog { width: auto; margin: 10px; }
                .bulk-price-toolbar { flex-direction: column; align-items: stretch; }
            }
        </style>
       </head>
    <!-- END HEAD -->

    <body class="page-sidebar-closed-hide-logo page-content-white page-sidebar-closed">
      <?php include('common/manubar.php'); ?>
        <!-- BEGIN HEADER & CONTENT DIVIDER -->
        <div class="clearfix"> </div>
        <!-- END HEADER & CONTENT DIVIDER -->
        <!-- BEGIN CONTAINER -->
        <div class="page-container">
             <div class="page-sidebar-wrapper">
           <?php include('common/sidebar.php'); ?>
            
            </div>
            <!-- END SIDEBAR -->
            <!-- BEGIN CONTENT -->
            <div class="page-content-wrapper">
                <!-- BEGIN CONTENT BODY -->
                <div class="page-content">
                    <!-- BEGIN PAGE HEADER-->
          
                    <!-- BEGIN PAGE BAR -->
                    <div class="page-bar">
                        <ul class="page-breadcrumb">
                            <li>
                                <a href="#">Home</a>
                                <i class="fa fa-circle"></i>
                            </li>
                            <li>
                                <a href="#">Products</a>
                                <i class="fa fa-circle"></i>
                            </li>
                            <li>
                                <span>List Products</span>
                            </li>
                        </ul>
                      
                    </div>
                    <!-- END PAGE BAR -->
                    <!-- BEGIN PAGE TITLE-->
                    <div class="alert <?php echo $MessageClass; ?> alert-dismissable">
                                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                                        <?php echo $CompanyMessage; ?>
                                    </div>
                    <!-- END PAGE TITLE-->
                    <!-- END PAGE HEADER-->
                  
                    <div class="row">
                        <div class="col-md-12">
                            <!-- BEGIN EXAMPLE TABLE PORTLET-->
                            <div class="portlet light bordered">
                                <div class="portlet-title">
                                    <div class="caption font-green">
                                        <i class="icon-settings font-green"></i>
                                        <span class="caption-subject bold uppercase">List Products</span>
                                    </div>
                                    <div class="actions">
                                        <a href="manage-product.php?export=xlsx" class="btn btn-sm btn-info"><i class="fa fa-file-excel-o"></i> Export Excel</a>
                                        <button type="button" class="btn btn-sm blue" data-toggle="modal" data-target="#bulkPriceModal">
                                            <i class="fa fa-pencil-square-o"></i> Bulk Price Change
                                        </button>
                                    </div>
                                </div>
                                <div class="portlet-body">
                                    <table class="table table-striped table-bordered table-hover dt-responsive" width="100%" id="sample_2">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th class="all">Product Code</th>
                                                <th class="all">Product Name</th>
                                                <th class="all">Group</th>
                                                <th class="all">Product Type</th>
                                                <th class="all">Category</th>
                                                 <th class="all">Product Quantity</th>
                                                <th class="all">Product Cost</th>
                                                <th class="all">Product Price</th>
                                                <th class="none">Product Unit</th>
                                                <th class="all">Action</th>
                                                
                                            </tr>
                                        </thead>
                                        <tbody>
                                           <?php foreach($data as $query) 
                                            { 
                                                $item_id = $query['item_id']; 
                                                ?> 
   
                                             <tr data-item-id="<?php echo (int) $item_id; ?>">
                                                <th></th>
                                                <td><?php echo  $query['item_code']; ?></td>
                                                <td><?php  echo  $query['item_name']; ?></td>
                                                <td><?php  echo  $query['group_name'] ?? ''; ?></td>
                                                 <td><?php  echo  $query['type_name'] ?? ''; ?></td>
                                                <td><?php  echo  $query['category_name'] ?? ''; ?></td>
                                                <td><?php  echo  number_format((float) ($query['qty_balance'] ?? 0), 2); ?></td>
                                                <td><?php include('currency.php');?> <?php echo number_format((float) ($query['item_purchase_price'] ?? 0), 2); ?> </td>
                                                <td class="product-price-cell"><?php include('currency.php');?> <?php echo number_format((float) ($query['item_normal_selling_price'] ?? 0), 2); ?> </td>
                                                <td><?php  echo  $query['item_uom']; ?> </td>
                                                <td>
                                                    <div class="btn-group">
                                            <a href="edit-product.php?pid=<?php echo $item_id; ?>" class="btn btn-xs btn-default"><i class="fa fa-pencil"></i></a>
                                            <a href="product-details.php?pid=<?php echo $item_id; ?>" class="btn btn-xs bg-olive"><i class="glyphicon glyphicon-search"></i></a>
                                            <a href="manage-product.php?deleteID=<?php echo $item_id; ?>" class="btn btn-xs btn-danger"><i class="glyphicon glyphicon-trash"></i></a>
                                        </div>
                                                </td>
                                            </tr>
                                            <?php }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <!-- END EXAMPLE TABLE PORTLET-->
                        </div>
                        
                    </div>

                    <div id="bulkPriceModal" class="modal fade bulk-price-modal" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    <h4 class="modal-title"><i class="fa fa-table"></i> Bulk Product Price Change</h4>
                                </div>
                                <div class="modal-body">
                                    <div id="bulkPriceAlert" class="alert bulk-price-alert"></div>
                                    <div class="bulk-price-toolbar">
                                        <div>
                                            <button type="button" class="btn btn-default btn-sm" id="bulkPriceResetFilters"><i class="fa fa-eraser"></i> Reset Filters</button>
                                            <button type="button" class="btn btn-default btn-sm" id="bulkPriceShowChanged"><i class="fa fa-filter"></i> Show Changed Only</button>
                                        </div>
                                        <div class="bulk-price-count">Changed Rows: <span id="bulkPriceChangedCount">0</span></div>
                                    </div>
                                    <div class="bulk-price-wrap">
                                        <table class="bulk-price-table" id="bulkPriceTable">
                                            <thead>
                                                <tr>
                                                    <th rowspan="2">Item Code</th>
                                                    <th rowspan="2">Item Group</th>
                                                    <th rowspan="2">Name</th>
                                                    <th colspan="2">Default Price</th>
                                                    <th rowspan="2">Tax</th>
                                                </tr>
                                                <tr>
                                                    <th>Price</th>
                                                    <th>Retail Price</th>
                                                </tr>
                                                <tr>
                                                    <th><input type="text" class="filter-input bulk-price-filter" data-filter="code" placeholder="Filter code"></th>
                                                    <th><input type="text" class="filter-input bulk-price-filter" data-filter="group" placeholder="Filter group"></th>
                                                    <th><input type="text" class="filter-input bulk-price-filter" data-filter="name" placeholder="Filter name"></th>
                                                    <th><input type="text" class="filter-input bulk-price-filter" data-filter="price" placeholder="Filter price"></th>
                                                    <th><input type="text" class="filter-input bulk-price-filter" data-filter="retail" placeholder="Filter retail"></th>
                                                    <th><input type="text" class="filter-input bulk-price-filter" data-filter="tax" placeholder="Filter tax"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($data as $product): ?>
                                                    <?php
                                                        $taxCode = trim((string) ($product['gst_vat_code'] ?? ''));
                                                        $taxFilter = $taxCode !== '' ? $taxCode : ((($product['item_vat'] ?? 'N') === 'Y') ? 'GST' : 'No Tax');
                                                    ?>
                                                    <tr data-item-id="<?php echo (int) ($product['item_id'] ?? 0); ?>" data-dirty="0">
                                                        <td data-filter-value="<?php echo htmlspecialchars((string) ($product['item_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($product['item_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td data-filter-value="<?php echo htmlspecialchars((string) ($product['group_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($product['group_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td data-filter-value="<?php echo htmlspecialchars((string) ($product['item_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($product['item_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td>
                                                            <input type="number" min="0" step="0.01" class="price-input bulk-editable bulk-price-input" data-field="price" data-original="<?php echo number_format((float) ($product['item_normal_selling_price'] ?? 0), 2, '.', ''); ?>" value="<?php echo number_format((float) ($product['item_normal_selling_price'] ?? 0), 2, '.', ''); ?>">
                                                        </td>
                                                        <td>
                                                            <input type="number" min="0" step="0.01" class="price-input bulk-editable bulk-retail-input" data-field="retail_price" data-original="<?php echo number_format((float) ($product['retail_price'] ?? 0), 2, '.', ''); ?>" value="<?php echo number_format((float) ($product['retail_price'] ?? 0), 2, '.', ''); ?>">
                                                        </td>
                                                        <td data-filter-value="<?php echo htmlspecialchars($taxFilter, ENT_QUOTES, 'UTF-8'); ?>">
                                                            <select class="tax-select bulk-editable bulk-tax-select" data-field="gst_vat_code" data-original="<?php echo htmlspecialchars($taxCode, ENT_QUOTES, 'UTF-8'); ?>">
                                                                <?php echo buildTaxOptionsMarkup($taxOptions, $taxCode); ?>
                                                            </select>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-primary" id="bulkPriceSaveBtn">
                                        <i class="fa fa-save"></i> Save Price Changes
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                  
                </div>
                <!-- END CONTENT BODY -->
            </div>
            <!-- END CONTENT -->
        
        </div>
        <!-- END CONTAINER -->
    <?php include('common/footer.php');?>
        <!--[if lt IE 9]>
<script src="assets/global/plugins/respond.min.js"></script>
<script src="assets/global/plugins/excanvas.min.js"></script> 
<![endif]-->
        <!-- BEGIN CORE PLUGINS -->
        <script src="assets/global/plugins/jquery.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/js.cookie.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/bootstrap-hover-dropdown/bootstrap-hover-dropdown.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/jquery-slimscroll/jquery.slimscroll.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/jquery.blockui.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/uniform/jquery.uniform.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/bootstrap-switch/js/bootstrap-switch.min.js" type="text/javascript"></script>
        <!-- END CORE PLUGINS -->
        <!-- BEGIN PAGE LEVEL PLUGINS -->
        <script src="assets/global/scripts/datatable.js" type="text/javascript"></script>
        <script src="assets/global/plugins/datatables/datatables.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.js" type="text/javascript"></script>
        <!-- END PAGE LEVEL PLUGINS -->
        <!-- BEGIN THEME GLOBAL SCRIPTS -->
        <script src="assets/global/scripts/app.min.js" type="text/javascript"></script>
        <!-- END THEME GLOBAL SCRIPTS -->
        <script type="text/javascript">
            // Restore pagination position after returning from edit-product.php
            $.extend($.fn.dataTable.defaults, { stateSave: true });
        </script>
        <!-- BEGIN PAGE LEVEL SCRIPTS -->
        <script src="assets/pages/scripts/table-datatables-responsive.min.js" type="text/javascript"></script>
        <!-- END PAGE LEVEL SCRIPTS -->
        <!-- BEGIN THEME LAYOUT SCRIPTS -->
        <script src="assets/layouts/layout/scripts/layout.min.js" type="text/javascript"></script>
        <script src="assets/layouts/layout/scripts/demo.min.js" type="text/javascript"></script>
        <script src="assets/layouts/global/scripts/quick-sidebar.min.js" type="text/javascript"></script>
        <!-- END THEME LAYOUT SCRIPTS -->
    <script type="text/javascript">
        jQuery(function($){
            $('.dt-buttons .buttons-print, .dt-buttons .buttons-pdf, .dt-buttons .buttons-csv').remove();
        });
    </script>
    <script type="text/javascript">
        (function ($) {
            function showBulkPriceAlert(type, message) {
                var $alert = $('#bulkPriceAlert');
                $alert.removeClass('alert-success alert-danger alert-info').addClass('alert-' + type).html(message).show();
            }

            function normalizeNumber(value) {
                var parsed = parseFloat(value);
                if (isNaN(parsed) || parsed < 0) {
                    return '0.00';
                }
                return parsed.toFixed(2);
            }

            function updateChangedCount() {
                var changedCount = $('#bulkPriceTable tbody tr[data-dirty="1"]').length;
                $('#bulkPriceChangedCount').text(changedCount);
                $('#bulkPriceSaveBtn').prop('disabled', changedCount === 0);
            }

            function markRowDirty($row) {
                var isDirty = false;
                $row.find('.bulk-editable').each(function () {
                    var $field = $(this);
                    var original = String($field.data('original') || '');
                    var current = $field.is('select') ? String($field.val() || '') : normalizeNumber($field.val());
                    if (original !== current) {
                        isDirty = true;
                        return false;
                    }
                });

                $row.attr('data-dirty', isDirty ? '1' : '0').toggleClass('bulk-price-row-dirty', isDirty);
                updateChangedCount();
            }

            function applyBulkPriceFilters() {
                var showChangedOnly = $('#bulkPriceShowChanged').data('changed-only') === 1;
                $('#bulkPriceTable tbody tr').each(function () {
                    var $row = $(this);
                    var visible = true;

                    $('.bulk-price-filter').each(function () {
                        var filterName = $(this).data('filter');
                        var filterValue = $.trim(String($(this).val() || '')).toLowerCase();
                        if (!filterValue) {
                            return;
                        }

                        var cellValue = '';
                        if (filterName === 'price') {
                            cellValue = normalizeNumber($row.find('.bulk-price-input').val()).toLowerCase();
                        } else if (filterName === 'retail') {
                            cellValue = normalizeNumber($row.find('.bulk-retail-input').val()).toLowerCase();
                        } else if (filterName === 'tax') {
                            cellValue = $.trim($row.find('.bulk-tax-select option:selected').text()).toLowerCase();
                        } else {
                            var selectorIndex = { code: 0, group: 1, name: 2 }[filterName];
                            cellValue = $.trim(String($row.children('td').eq(selectorIndex).data('filter-value') || '')).toLowerCase();
                        }

                        if (cellValue.indexOf(filterValue) === -1) {
                            visible = false;
                            return false;
                        }
                    });

                    if (visible && showChangedOnly && $row.attr('data-dirty') !== '1') {
                        visible = false;
                    }

                    $row.toggle(visible);
                });
            }

            $(document).ready(function () {
                updateChangedCount();

                $('#bulkPriceTable').on('input change', '.bulk-editable', function () {
                    markRowDirty($(this).closest('tr'));
                    applyBulkPriceFilters();
                });

                $('.bulk-price-filter').on('input change', applyBulkPriceFilters);

                $('#bulkPriceResetFilters').on('click', function () {
                    $('.bulk-price-filter').val('');
                    $('#bulkPriceShowChanged').data('changed-only', 0).removeClass('btn-warning').addClass('btn-default');
                    applyBulkPriceFilters();
                });

                $('#bulkPriceShowChanged').on('click', function () {
                    var changedOnly = $(this).data('changed-only') === 1 ? 0 : 1;
                    $(this).data('changed-only', changedOnly);
                    $(this).toggleClass('btn-warning', changedOnly === 1).toggleClass('btn-default', changedOnly !== 1);
                    applyBulkPriceFilters();
                });

                $('#bulkPriceModal').on('hidden.bs.modal', function () {
                    $('#bulkPriceAlert').hide().html('');
                });

                $('#bulkPriceSaveBtn').on('click', function () {
                    var changedRows = [];

                    $('#bulkPriceTable tbody tr[data-dirty="1"]').each(function () {
                        var $row = $(this);
                        changedRows.push({
                            item_id: parseInt($row.data('item-id'), 10) || 0,
                            price: normalizeNumber($row.find('.bulk-price-input').val()),
                            retail_price: normalizeNumber($row.find('.bulk-retail-input').val()),
                            gst_vat_code: String($row.find('.bulk-tax-select').val() || '')
                        });
                    });

                    if (!changedRows.length) {
                        showBulkPriceAlert('info', 'No price changes to save.');
                        return;
                    }

                    var $button = $(this);
                    var originalHtml = $button.html();
                    $button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

                    $.ajax({
                        url: 'process/bulk-update-product-prices.php',
                        method: 'POST',
                        data: JSON.stringify({
                            csrf_token: <?php echo json_encode($bulkPriceCsrf); ?>,
                            rows: changedRows
                        }),
                        contentType: 'application/json',
                        dataType: 'json'
                    }).done(function (response) {
                        if (!response || response.status !== true) {
                            showBulkPriceAlert('danger', (response && response.message) ? response.message : 'Failed to save bulk price changes.');
                            return;
                        }

                        $('#bulkPriceTable tbody tr[data-dirty="1"]').each(function () {
                            var $row = $(this);
                            var priceValue = normalizeNumber($row.find('.bulk-price-input').val());
                            var retailValue = normalizeNumber($row.find('.bulk-retail-input').val());
                            var taxValue = String($row.find('.bulk-tax-select').val() || '');
                            var itemId = parseInt($row.data('item-id'), 10) || 0;

                            $row.find('.bulk-price-input').attr('data-original', priceValue).data('original', priceValue);
                            $row.find('.bulk-retail-input').attr('data-original', retailValue).data('original', retailValue);
                            $row.find('.bulk-tax-select').attr('data-original', taxValue).data('original', taxValue);
                            $row.attr('data-dirty', '0').removeClass('bulk-price-row-dirty');

                            var $mainRow = $('#sample_2 tbody tr[data-item-id="' + itemId + '"]');
                            if ($mainRow.length) {
                                $mainRow.find('.product-price-cell').html('<?php ob_start(); include('currency.php'); echo trim(ob_get_clean()); ?> ' + priceValue);
                            }
                        });

                        updateChangedCount();
                        applyBulkPriceFilters();
                        showBulkPriceAlert('success', response.message || 'Bulk prices updated successfully.');
                    }).fail(function (xhr) {
                        var message = 'Failed to save bulk price changes.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        showBulkPriceAlert('danger', message);
                    }).always(function () {
                        $button.prop('disabled', false).html(originalHtml);
                    });
                });
            });
        })(jQuery);
    </script>
    <script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','www.google-analytics.com/analytics.js','ga');
  ga('create', 'UA-37564768-1', 'keenthemes.com');
  ga('send', 'pageview');
</script>
</body>



<!-- Mirrored from www.keenthemes.com/preview/metronic/theme/admin_1/table_datatables_responsive.html by HTTrack Website Copier/3.x [XR&CO'2010], Sun, 28 Feb 2016 06:22:49 GMT -->
<!-- Added by HTTrack --><meta http-equiv="content-type" content="text/html;charset=UTF-8" /><!-- /Added by HTTrack -->
</html>



