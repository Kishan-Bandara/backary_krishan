<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');
include('get_url.php');

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$db = new Database();
$id = (int) ($_GET['id'] ?? 0);
$message = $_GET['message'] ?? '';
$type = $_GET['type'] ?? '';

if ($id <= 0) {
    redirect('manage-purchase-returns.php?message=' . urlencode('Invalid return id') . '&type=error');
}

$header = $db->getRow('SELECT * FROM purchase_return_header WHERE pr_h_id = ?', [$id]);
if (!$header) {
    redirect('manage-purchase-returns.php?message=' . urlencode('Return not found') . '&type=error');
}

if (!isSuperAdmin() && (int)$header['location_id'] !== (int)($_SESSION['location'] ?? 0)) {
    redirect('access_denied.php');
}

$supplier = $db->getRow('SELECT * FROM supplier WHERE supplier_id = ?', [$header['supplier_id']]);
$location = $db->getRow('SELECT name, phone_no, address FROM location_master WHERE id = ?', [$header['location_id']]);
$grn = $db->getRow('SELECT grn_h_code FROM grn_hedder WHERE grn_h_id = ?', [$header['grn_h_id']]);
$items = $db->getRows('SELECT prd.*, im.item_name, im.item_code FROM purchase_return_details prd JOIN item_master im ON im.item_id = prd.item_id WHERE prd.pr_h_id = ?', [$id]) ?: [];

$cur = $db->getRow('SELECT currency FROM currency WHERE activated = ? LIMIT 1', ['Y']);
$currency = $cur['currency'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Purchase Return Note | WebStore</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
    <style>
        @media print {
            body { font-size: 12px; }
            .no-print { display: none; }
            .page-bar, .alert, .page-sidebar-wrapper, .manubar { display: none; }
            .page-content { margin: 0; padding: 0; }
            .portlet { border: none; box-shadow: none; }
            .table th, .table td { padding: 5px; border: 1px solid #ddd; }
        }
        .return-note-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            margin: -15px -15px 20px -15px;
            border-radius: 5px 5px 0 0;
        }
        .return-note-header h2 {
            margin: 0;
            font-size: 28px;
            font-weight: 300;
        }
        .return-note-header .subtitle {
            font-size: 14px;
            opacity: 0.9;
        }
        .info-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .info-section h4 {
            margin-top: 0;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 5px;
        }
        .table-responsive {
            border: 1px solid #dee2e6;
            border-radius: 5px;
            overflow: hidden;
        }
        .table {
            margin-bottom: 0;
        }
        .table thead th {
            background: #e9ecef;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
        }
        .totals-table {
            width: 300px;
            margin-left: auto;
        }
        .totals-table th {
            background: #f8f9fa;
            width: 50%;
        }
        .totals-table .grand-total {
            background: #28a745;
            color: white;
            font-size: 18px;
            font-weight: bold;
        }
        .btn-print {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-print:hover {
            background: #0056b3;
        }
    </style>
</head>
<body class="page-sidebar-closed-hide-logo page-content-white">
<?php include('common/manubar.php'); ?>
<div class="clearfix"> </div>
<div class="page-container">
    <div class="page-sidebar-wrapper">
        <?php include('common/sidebar.php'); ?>
    </div>
    <div class="page-content-wrapper">
        <div class="page-content">
            <div class="page-bar no-print">
                <ul class="page-breadcrumb">
                    <li><a href="index.php">Home</a><i class="fa fa-circle"></i></li>
                    <li><a href="manage-purchase-returns.php">Purchase Returns</a><i class="fa fa-circle"></i></li>
                    <li><span>Return Note</span></li>
                </ul>
            </div>

            <?php if (!empty($message)) { ?>
                <div class="alert <?php echo ($type === 'error') ? 'alert-danger' : 'alert-success'; ?> alert-dismissable no-print">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                    <?php echo h($message); ?>
                </div>
            <?php } ?>

            <div class="portlet light bordered">
                <div class="return-note-header">
                    <h2>Purchase Return Note</h2>
                    <div class="subtitle">Return Code: <?php echo h($header['pr_h_code']); ?> | Date: <?php echo h($header['pr_date']); ?></div>
                </div>
                <div class="portlet-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="info-section">
                                <h4><i class="fa fa-truck"></i> Supplier Details</h4>
                                <p><strong><?php echo h($supplier['supplier_name'] ?? ''); ?></strong></p>
                                <p><i class="fa fa-map-marker"></i> <?php echo h($supplier['supplier_address'] ?? ''); ?></p>
                                <p><i class="fa fa-phone"></i> <?php echo h($supplier['supplier_contact_no'] ?? ''); ?></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-section">
                                <h4><i class="fa fa-info-circle"></i> Return Information</h4>
                                <p><strong>Return No:</strong> <?php echo h($header['pr_h_code']); ?></p>
                                <p><strong>Date:</strong> <?php echo h($header['pr_date']); ?></p>
                                <p><strong>GRN Reference:</strong> <?php echo h($grn['grn_h_code'] ?? ''); ?></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-section">
                                <h4><i class="fa fa-building"></i> Location Details</h4>
                                <p><strong><?php echo h($location['name'] ?? ''); ?></strong></p>
                                <p><i class="fa fa-map-marker"></i> <?php echo h($location['address'] ?? ''); ?></p>
                                <p><i class="fa fa-phone"></i> <?php echo h($location['phone_no'] ?? ''); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th><i class="fa fa-hashtag"></i> Item Code</th>
                                    <th><i class="fa fa-box"></i> Item Name</th>
                                    <th class="text-right"><i class="fa fa-cubes"></i> Quantity</th>
                                    <th class="text-right"><i class="fa fa-dollar-sign"></i> Rate</th>
                                    <th class="text-right"><i class="fa fa-percent"></i> GST</th>
                                    <th class="text-right"><i class="fa fa-calculator"></i> Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $it) { ?>
                                    <tr>
                                        <td><?php echo h($it['item_code']); ?></td>
                                        <td><?php echo h($it['item_name']); ?></td>
                                        <td class="text-right"><?php echo number_format((float)$it['pr_d_qty'], 2); ?></td>
                                        <td class="text-right"><?php echo h($currency); ?> <?php echo number_format((float)$it['pr_d_rate'], 2); ?></td>
                                        <td class="text-right"><?php echo h($currency); ?> <?php echo number_format((float)$it['pr_d_vat'], 2); ?></td>
                                        <td class="text-right"><strong><?php echo h($currency); ?> <?php echo number_format((float)$it['pr_d_total'], 2); ?></strong></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="row">
                        <div class="col-md-4 col-md-offset-8">
                            <table class="table table-bordered totals-table">
                                <tr>
                                    <th>Sub Total</th>
                                    <td class="text-right"><?php echo h($currency); ?> <?php echo number_format((float)$header['pr_net'], 2); ?></td>
                                </tr>
                                <tr>
                                    <th>Total GST</th>
                                    <td class="text-right"><?php echo h($currency); ?> <?php echo number_format((float)$header['pr_vat'], 2); ?></td>
                                </tr>
                                <tr class="grand-total">
                                    <th>Grand Total</th>
                                    <td class="text-right"><?php echo h($currency); ?> <?php echo number_format((float)$header['pr_gross'], 2); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="text-center no-print" style="margin-top: 30px;">
                        <button onclick="window.print()" class="btn-print">
                            <i class="fa fa-print"></i> Print Return Note
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<?php include('common/footer.php'); ?>
<script src="assets/global/plugins/jquery.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/js.cookie.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/bootstrap-hover-dropdown/bootstrap-hover-dropdown.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/jquery-slimscroll/jquery.slimscroll.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/jquery.blockui.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/uniform/jquery.uniform.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/bootstrap-switch/js/bootstrap-switch.min.js" type="text/javascript"></script>
<script src="assets/global/scripts/app.min.js" type="text/javascript"></script>
<script src="assets/layouts/layout/scripts/layout.min.js" type="text/javascript"></script>
</body>
</html>
