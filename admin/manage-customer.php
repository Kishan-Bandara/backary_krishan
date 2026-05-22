<?php
ob_start();
error_reporting (E_ALL ^ E_NOTICE);

session_start();
include('include/database.php');
include('include/check_login.php');
include('include/customer_access.php');
$message = "";
$db = new Database();
$canManageCustomerAccess = canManageCustomerStatusAccess();

if (isset($_GET['notice'])) {
    $message = trim((string) $_GET['notice']);
}

function getContent() {
    $db = new Database();
    if (isset($_SESSION['userid']) && (int) $_SESSION['userid'] === 4) {
        return $db->getRows('SELECT * FROM customer WHERE customer_id = 0');
    }

    return $db->getRows('SELECT * FROM customer ORDER BY customer_id DESC');
}

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

function exportCustomersXlsx(array $customers)
{
    if (!loadPhpSpreadsheetForExport()) {
        header('HTTP/1.1 500 Internal Server Error');
        echo 'PhpSpreadsheet library not found. Please install via composer.';
        exit;
    }

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Customers');

    $sheet->setCellValue('A1', 'Customers Export');
    $sheet->setCellValue('A2', 'Generated: ' . date('Y-m-d H:i:s'));
    $headers = ['Customer ID', 'Customer Code', 'Customer Name', 'Mobile', 'Landline', 'Email', 'NIC'];
    $row = 4;
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . $row, $header);
        $col++;
    }

    $currentRow = 5;
    foreach ($customers as $customer) {
        $sheet->setCellValue('A' . $currentRow, $customer['customer_id'] ?? '');
        $sheet->setCellValue('B' . $currentRow, $customer['customer_code'] ?? '');
        $sheet->setCellValue('C' . $currentRow, $customer['customer_name'] ?? '');
        $sheet->setCellValue('D' . $currentRow, $customer['customer_tell'] ?? '');
        $sheet->setCellValue('E' . $currentRow, $customer['customer_mobile'] ?? '');
        $sheet->setCellValue('F' . $currentRow, $customer['customer_email'] ?? '');
        $sheet->setCellValue('G' . $currentRow, $customer['customer_nic'] ?? '');
        $currentRow++;
    }

    foreach (range('A', 'G') as $columnId) {
        $sheet->getColumnDimension($columnId)->setAutoSize(true);
    }

    $filename = 'Customers_' . date('Ymd_His') . '.xlsx';
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
    exportCustomersXlsx(getContent());
}

if (isset($_GET['approveID'])) {
    $approveId = (int) $_GET['approveID'];

    if (!$canManageCustomerAccess) {
        $message = 'Only super admin can approve customers.';
    } elseif ($approveId <= 0) {
        $message = 'Check your customer ID!';
    } else {
        $customerToApprove = $db->getRow('SELECT customer_id, customer_name FROM customer WHERE customer_id = ? LIMIT 1', [$approveId]);
        if (!$customerToApprove) {
            $message = 'Customer not found.';
        } else {
            $db->updateRow('UPDATE customer SET is_active = 1, locked = 0 WHERE customer_id = ?', [$approveId]);
            header('Location:manage-customer.php?notice=' . urlencode('Customer approved successfully: ' . ($customerToApprove['customer_name'] ?? ('#' . $approveId))));
            exit();
        }
    }
}

//Delete values
if(isset($_GET['deleteID']))
{
   $deleteid = $_GET['deleteID'];

if($deleteid > 0)
{

    $query_invoice_h = $db->getRow('SELECT * FROM invoice_hedder WHERE  invoice_h_customer_id = ?',[$deleteid]);

    $delete_customer_info = $query_invoice_h['invoice_h_id'];

    if($delete_customer_info > 0){

        $message = "You can not delete this cusomer";
      
    }else{
    $deleterowquery = $db->deleteRow('DELETE FROM customer WHERE customer_id = ?',[$deleteid]);
    $deleterowquery = $db->deleteRow('DELETE FROM shipping_address WHERE fk_customer_id = ?',[$deleteid]);

    header('Location:manage-customer.php');
    exit();
    $message = "Customer personal details and other details deleted";
    }



}
else
{

    $message = "check your  customer ID!";

}
}
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
        <title>Customer List</title>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta content="width=device-width, initial-scale=1" name="viewport" />
        <meta content="" name="description" />
        <meta content="" name="author" />
        <?php include('common/head.php'); ?>
        <style>
            .dt-buttons .buttons-print,
            .dt-buttons .buttons-pdf,
            .dt-buttons .buttons-csv {
                display: none !important;
            }
        </style>
       </head>
    <!-- END HEAD -->

    <body class="page-sidebar-closed-hide-logo page-content-white">
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
                                <a href="index.php">Home</a>
                                <i class="fa fa-circle"></i>
                            </li>
                            <li>
                                <a href="#">Customer</a>
                                <i class="fa fa-circle"></i>
                            </li>
                            <li>
                                <span>List Customer</span>
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
                            <?php if($message){ ?>
                          <ul class="list-group">
                                        <li class="list-group-item bg-blue bg-font-blue"><?php echo $message; ?></li>
                                       
                                    </ul> <?php } ?>
                            <!-- BEGIN EXAMPLE TABLE PORTLET-->
                            <div class="portlet light bordered">
                                <div class="portlet-title">
                                    <div class="caption font-green">
                                        <i class="icon-settings font-green"></i>
                                        <span class="caption-subject bold uppercase">List of Customers</span>
                                    </div>
                                    <div class="actions">
                                        <a href="customer-bulk-sample.php" class="btn btn-sm btn-success"><i class="fa fa-download"></i> Download Template</a>
                                        <a href="customer-bulk-upload.php" class="btn btn-sm btn-primary"><i class="fa fa-upload"></i> Bulk Upload</a>
                                        <a href="manage-customer.php?export=xlsx" class="btn btn-sm btn-info"><i class="fa fa-file-excel-o"></i> Export Excel</a>
                                    </div>
                                </div>
                                <div class="portlet-body">
                                    <table class="table table-striped table-bordered table-hover dt-responsive" width="100%" id="sample_2">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th class="all">Customer id</th>
                                                <th class="all">Customer Code</th>
                                                <th class="all">Customer Name</th>
                                                <th class="all">Landline</th>
                                                <th class="all">Mobile</th>
                                                <th class="all">Email</th>
                                                <th class="all">Nic</th>
                                                <th class="all">Status</th>  
                                                <th class="all">Action</th>
                                                
                                            </tr>
                                        </thead>
                                        <tbody>
                                           <?php $data = getContent();
                                        foreach($data as $query) { $categoryid = $query['customer_id'];
                                            $isActive = (int) ($query['is_active'] ?? 0) === 1;
                                            $isLocked = (int) ($query['locked'] ?? 0) === 1;
                                        ?> 
   
                                             <tr>
                                                <th></th>
                                                <td><?php echo  $query['customer_id']; ?></td>
                                                <td><?php echo  $query['customer_code']; ?></td>
                                                <td><?php  echo  $query['customer_name']; ?></td>
                                                <td><?php  echo  $query['customer_tell']; ?></td>
                                                 <td><?php  echo  $query['customer_mobile']; ?></td>
                                                <td><?php  echo  $query['customer_email']; ?></td>
                                                <td><?php echo  $query['customer_nic']; ?> </td>
                                                <td>
                                                    <span class="label <?php echo $isActive ? 'label-success' : 'label-warning'; ?>">
                                                        <?php echo $isActive ? 'Active' : 'Pending'; ?>
                                                    </span>
                                                    <span class="label <?php echo $isLocked ? 'label-danger' : 'label-info'; ?>" style="margin-left: 4px;">
                                                        <?php echo $isLocked ? 'Locked' : 'Unlocked'; ?>
                                                    </span>
                                                </td>
                                                
                                                <td>
                                                <div class="btn-group">
                                                    <a href="customer_view.php?customerID=<?php echo $categoryid; ?>" class="btn btn-xs btn-default" title="Edit Customer"><i class="fa fa-pencil"></i></a>

                                                    <?php if ($canManageCustomerAccess && (!$isActive || $isLocked)): ?>
                                                        <a href="manage-customer.php?approveID=<?php echo $categoryid; ?>" class="btn btn-xs btn-primary" title="Approve Customer" onclick="return confirm('Approve this customer and unlock the account?');"><i class="fa fa-check"></i> Approve</a>
                                                    <?php endif; ?>

                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-xs btn-success dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Quick actions">
                                                            <i class="fa fa-shopping-cart"></i> <span class="caret"></span>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-right">
                                                            <li><a href="standing-order.php?customer_id=<?php echo $categoryid; ?>"><i class="fa fa-calendar"></i> Standing Order</a></li>
                                                            <li><a href="cart-order.php?customer_id=<?php echo $categoryid; ?>"><i class="fa fa-shopping-cart"></i> Cart</a></li>
                                                            <li><a href="customer_view.php?customerID=<?php echo $categoryid; ?>"><i class="fa fa-money"></i> Credits</a></li>
                                                            <li><a href="manage-orders.php?customer_id=<?php echo $categoryid; ?>"><i class="fa fa-list"></i> Total Orders</a></li>
                                                        </ul>
                                                    </div>

                                                    <a href="manage-customer.php?deleteID=<?php echo $categoryid; ?>" class="btn btn-xs btn-danger" title="Delete"><i class="glyphicon glyphicon-trash"></i></a>
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



