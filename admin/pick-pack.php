<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('include/database.php');
include('include/check_login.php');

$db = new Database();

// Get selected date (default to today)
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Fetch all order items for the selected delivery date grouped by route, customer, category, item
$query = $db->getRows(
    'SELECT 
        COALESCE(drm.route_name, "No Route") AS route_name,
        COALESCE(drm.id, 0) AS route_id,
        c.customer_id,
        c.customer_name,
        COALESCE(cm.category_name, "Uncategorized") AS category_name,
        COALESCE(cm.category_id, 0) AS category_id,
        im.item_id,
        im.item_name,
        SUM(id.invoice_d_qty) AS total_qty
     FROM invoice_details id
     JOIN invoice_hedder ih ON ih.invoice_h_id = id.invoice_h_id
     JOIN customer c ON c.customer_id = ih.invoice_h_customer_id
     JOIN item_master im ON im.item_id = id.invoice_d_item_id
     LEFT JOIN category_master cm ON cm.category_id = im.item_category
     -- Use customers default shipping address (invoices may not store a shipping address id)
     LEFT JOIN customer_shipping_address csa ON csa.customer_id = c.customer_id AND csa.is_default = 1
     LEFT JOIN delivery_route_master drm ON drm.id = csa.delivery_route_id
     WHERE ih.invoice_h_delivery_date = ?
       AND ih.invoice_h_status = 1
     GROUP BY drm.id, c.customer_id, cm.category_id, im.item_id
     ORDER BY drm.route_name ASC, c.customer_name ASC, cm.category_name ASC, im.item_name ASC',
    [$selected_date]
);

// Organize data by route -> customer -> category -> items
$data = [];
foreach ($query as $row) {
    $routeName = $row['route_name'];
    $custId = $row['customer_id'];
    $custName = $row['customer_name'];
    $catName = $row['category_name'];
    $itemName = $row['item_name'];
    $qty = (int)$row['total_qty'];

    if (!isset($data[$routeName])) {
        $data[$routeName] = [];
    }
    if (!isset($data[$routeName][$custId])) {
        $data[$routeName][$custId] = [
            'name' => $custName,
            'categories' => []
        ];
    }
    if (!isset($data[$routeName][$custId]['categories'][$catName])) {
        $data[$routeName][$custId]['categories'][$catName] = [];
    }
    $data[$routeName][$custId]['categories'][$catName][] = [
        'item_name' => $itemName,
        'qty' => $qty
    ];
}

$formattedDate = date('d/m/Y', strtotime($selected_date));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Pick & Pack <?php echo $formattedDate; ?></title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
    
    <style>
        .pick-pack-report {
            max-width: 900px;
            margin: 0 auto;
            font-family: Arial, sans-serif;
            font-size: 13px;
        }
        
        .pick-pack-report h1 {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .pick-pack-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .pick-pack-table th {
            text-align: left;
            font-weight: bold;
            padding: 8px 6px;
            border-bottom: 2px solid #333;
            background: #f9f9f9;
        }
        
        .pick-pack-table td {
            padding: 6px;
            vertical-align: top;
            border-bottom: 1px solid #ddd;
        }
        
        .pick-pack-table .col-route { width: 12%; }
        .pick-pack-table .col-customer { width: 18%; }
        .pick-pack-table .col-group { width: 18%; }
        .pick-pack-table .col-item { width: 28%; }
        .pick-pack-table .col-ordered { width: 10%; text-align: center; }
        .pick-pack-table .col-made { width: 10%; text-align: center; }
        
        .pick-pack-table .text-center { text-align: center; }
        
        .pick-pack-table .made-input {
            width: 40px;
            height: 24px;
            text-align: center;
            border: 1px solid #999;
        }
        
        .no-print {
            margin-bottom: 20px;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 0;
                font-size: 11px;
            }
            
            .no-print {
                display: none !important;
            }
            
            .pick-pack-report {
                max-width: 100%;
                padding: 5mm;
            }
            
            .pick-pack-report h1 {
                font-size: 18px;
                margin-bottom: 10px;
            }
            
            .pick-pack-table th,
            .pick-pack-table td {
                padding: 4px 5px;
                font-size: 11px;
            }
            
            .pick-pack-table .made-input {
                width: 35px;
                height: 20px;
                border: 1px solid #333;
            }
            
            .page-sidebar-wrapper,
            .page-bar,
            .page-header,
            .page-footer {
                display: none !important;
            }
            
            .page-content {
                margin: 0 !important;
                padding: 0 !important;
            }
            
            .page-container,
            .page-content-wrapper {
                margin: 0 !important;
                padding: 0 !important;
            }
        }
        
        @page {
            size: A4 portrait;
            margin: 10mm;
        }
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
                <!-- Page Bar -->
                <div class="page-bar no-print">
                    <ul class="page-breadcrumb">
                        <li>
                            <a href="index.php">Home</a>
                            <i class="fa fa-circle"></i>
                        </li>
                        <li>
                            <a href="#">Reports</a>
                            <i class="fa fa-circle"></i>
                        </li>
                        <li>
                            <span>Pick & Pack</span>
                        </li>
                    </ul>
                </div>
                
                <!-- Filter Controls -->
                <div class="row no-print">
                    <div class="col-md-12">
                        <div class="portlet light bordered">
                            <div class="portlet-title">
                                <div class="caption">
                                    <i class="fa fa-list-alt"></i> Pick & Pack Report
                                </div>
                            </div>
                            <div class="portlet-body">
                                <form method="get" class="form-inline">
                                    <div class="form-group">
                                        <label>Delivery Date:</label>
                                        <input type="date" name="date" class="form-control" value="<?php echo $selected_date; ?>" />
                                    </div>
                                    <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Generate</button>
                                    <button type="button" class="btn btn-default" onclick="window.print();"><i class="fa fa-print"></i> Print</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Report Content -->
                <div class="pick-pack-report">
                    <h1>Pick & Pack <?php echo $formattedDate; ?></h1>
                    
                    <?php if (empty($data)) { ?>
                        <div class="alert alert-info">No orders found for the selected date.</div>
                    <?php } else { ?>
                        <table class="pick-pack-table">
                            <thead>
                                <tr>
                                    <th class="col-route">Route</th>
                                    <th class="col-customer">Customer</th>
                                    <th class="col-group">Item Group</th>
                                    <th class="col-item">Item</th>
                                    <th class="col-ordered text-center">Ordered</th>
                                    <th class="col-made text-center">Made</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                foreach ($data as $routeName => $customers):
                                    $routeFirst = true;
                                    $routeRowCount = 0;
                                    // Count total rows for this route
                                    foreach ($customers as $custData) {
                                        foreach ($custData['categories'] as $items) {
                                            $routeRowCount += count($items);
                                        }
                                    }
                                    
                                    foreach ($customers as $custId => $custData):
                                        $custFirst = true;
                                        $custRowCount = 0;
                                        foreach ($custData['categories'] as $items) {
                                            $custRowCount += count($items);
                                        }
                                        
                                        foreach ($custData['categories'] as $catName => $items):
                                            $catFirst = true;
                                            
                                            foreach ($items as $item):
                                ?>
                                <tr>
                                    <td><?php echo $routeFirst ? htmlspecialchars($routeName) : ''; $routeFirst = false; ?></td>
                                    <td><?php echo $custFirst ? htmlspecialchars($custData['name']) : ''; $custFirst = false; ?></td>
                                    <td><?php echo $catFirst ? htmlspecialchars($catName) : ''; $catFirst = false; ?></td>
                                    <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                    <td class="text-center"><?php echo $item['qty']; ?></td>
                                    <td class="text-center"><input type="text" class="made-input" /></td>
                                </tr>
                                <?php 
                                            endforeach;
                                        endforeach;
                                    endforeach;
                                endforeach;
                                ?>
                            </tbody>
                        </table>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include('common/footer.php'); ?>
</body>
</html>
