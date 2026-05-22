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

// Fetch all order items for the selected delivery date
$query = $db->getRows(
    "SELECT 
        COALESCE(cm.category_name, 'Uncategorized') AS category_name,
        COALESCE(cm.category_id, 0) AS category_id,
        im.item_id,
        im.item_name,
        c.customer_id,
        c.customer_name,
        COALESCE(drm.route_name, 'No Route') AS route_name,
        COALESCE(csa.address_line_1, '') AS address_line_1,
        COALESCE(csa.address_line_2, '') AS address_line_2,
        COALESCE(csa.city, '') AS city,
        id.invoice_d_qty AS qty,
        ih.invoice_h_id
     FROM invoice_details id
     JOIN invoice_hedder ih ON ih.invoice_h_id = id.invoice_h_id
     JOIN customer c ON c.customer_id = ih.invoice_h_customer_id
     JOIN item_master im ON im.item_id = id.invoice_d_item_id
     LEFT JOIN category_master cm ON cm.category_id = im.item_category
     LEFT JOIN customer_shipping_address csa ON csa.customer_id = c.customer_id AND csa.is_default = 1
     LEFT JOIN delivery_route_master drm ON drm.id = csa.delivery_route_id
     WHERE ih.invoice_h_delivery_date = ?
       AND ih.invoice_h_status = 1
     ORDER BY cm.category_name ASC, im.item_name ASC, c.customer_name ASC",
    [$selected_date]
);

// Organize data by category -> product -> customers
$data = [];
$productTotals = [];

foreach ($query as $row) {
    $catName = $row['category_name'];
    $itemId = $row['item_id'];
    $itemName = $row['item_name'];
    $qty = (int)$row['qty'];
    
    // Build address string
    $addressParts = array_filter([$row['address_line_1'], $row['address_line_2'], $row['city']]);
    $address = implode(', ', $addressParts);

    if (!isset($data[$catName])) {
        $data[$catName] = [];
    }
    if (!isset($data[$catName][$itemId])) {
        $data[$catName][$itemId] = [
            'name' => $itemName,
            'customers' => [],
            'total_qty' => 0
        ];
    }
    
    $data[$catName][$itemId]['customers'][] = [
        'customer_name' => $row['customer_name'],
        'route_name' => $row['route_name'],
        'address' => $address,
        'qty' => $qty,
        'late_qty' => 0 // Placeholder for late orders if tracked
    ];
    $data[$catName][$itemId]['total_qty'] += $qty;
}

$formattedDate = date('d/m/Y', strtotime($selected_date));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Pick List for: <?php echo $formattedDate; ?></title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
    
    <style>
        .pick-list-report {
            max-width: 900px;
            margin: 0 auto;
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        
        .pick-list-report h1 {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .category-header {
            font-size: 14px;
            font-weight: bold;
            margin-top: 25px;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #333;
        }
        
        .product-header {
            font-size: 12px;
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 8px;
            color: #333;
        }
        
        .product-header .total-qty {
            font-weight: bold;
        }
        
        .pick-list-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .pick-list-table th {
            text-align: left;
            font-weight: bold;
            padding: 5px 6px;
            border-bottom: 1px solid #999;
            font-size: 11px;
            background: #f5f5f5;
        }
        
        .pick-list-table td {
            padding: 4px 6px;
            vertical-align: top;
            border-bottom: 1px solid #ddd;
            font-size: 11px;
        }
        
        .pick-list-table .col-ordered { width: 8%; text-align: center; }
        .pick-list-table .col-late { width: 6%; text-align: center; }
        .pick-list-table .col-sent { width: 6%; text-align: center; }
        .pick-list-table .col-customer { width: 30%; }
        .pick-list-table .col-route { width: 15%; }
        .pick-list-table .col-address { width: 35%; }
        
        .pick-list-table .text-center { text-align: center; }
        
        .pick-list-table .sent-input {
            width: 30px;
            height: 18px;
            text-align: center;
            border: 1px solid #999;
            font-size: 10px;
        }
        
        .late-input {
            width: 30px;
            height: 18px;
            text-align: center;
            border: 1px solid #999;
            font-size: 10px;
        }
        
        .no-print {
            margin-bottom: 20px;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 0;
                font-size: 10px;
            }
            
            .no-print {
                display: none !important;
            }
            
            .pick-list-report {
                max-width: 100%;
                padding: 5mm;
            }
            
            .pick-list-report h1 {
                font-size: 16px;
                margin-bottom: 10px;
            }
            
            .category-header {
                font-size: 12px;
                margin-top: 15px;
            }
            
            .product-header {
                font-size: 11px;
                margin-top: 10px;
            }
            
            .pick-list-table th,
            .pick-list-table td {
                padding: 3px 4px;
                font-size: 10px;
            }
            
            .pick-list-table .sent-input,
            .late-input {
                width: 25px;
                height: 16px;
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
            
            .category-header {
                page-break-after: avoid;
            }
            
            .product-header {
                page-break-after: avoid;
            }
            
            .pick-list-table {
                page-break-inside: auto;
            }
            
            .pick-list-table tr {
                page-break-inside: avoid;
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
                            <span>Pick List</span>
                        </li>
                    </ul>
                </div>
                
                <!-- Filter Controls -->
                <div class="row no-print">
                    <div class="col-md-12">
                        <div class="portlet light bordered">
                            <div class="portlet-title">
                                <div class="caption">
                                    <i class="fa fa-list"></i> Pick List Report
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
                <div class="pick-list-report">
                    <h1>Pick List for : <?php echo $formattedDate; ?></h1>
                    
                    <?php if (empty($data)) { ?>
                        <div class="alert alert-info">No orders found for the selected date.</div>
                    <?php } else { ?>
                        <?php foreach ($data as $catName => $products): ?>
                            <div class="category-header"><?php echo htmlspecialchars($catName); ?></div>
                            
                            <?php foreach ($products as $itemId => $product): 
                                $totalQty = $product['total_qty'];
                                $lateTotal = 0; // Sum of late orders if tracked
                            ?>
                                <div class="product-header">
                                    <span class="total-qty"><?php echo $totalQty; ?></span> | <?php echo $lateTotal; ?> <?php echo htmlspecialchars($product['name']); ?>
                                </div>
                                
                                <table class="pick-list-table">
                                    <thead>
                                        <tr>
                                            <th class="col-ordered text-center">Ordered</th>
                                            <th class="col-late text-center">+ Late</th>
                                            <th class="col-sent text-center">Sent</th>
                                            <th class="col-customer">Customer</th>
                                            <th class="col-route">Route</th>
                                            <th class="col-address">Address</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($product['customers'] as $cust): ?>
                                        <tr>
                                            <td class="text-center"><?php echo $cust['qty']; ?></td>
                                            <td class="text-center"><input type="text" class="late-input" /></td>
                                            <td class="text-center"><input type="text" class="sent-input" value="<?php echo $cust['qty']; ?>" /></td>
                                            <td><?php echo htmlspecialchars($cust['customer_name']); ?></td>
                                            <td><?php echo htmlspecialchars($cust['route_name']); ?></td>
                                            <td><?php echo htmlspecialchars($cust['address']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include('common/footer.php'); ?>
</body>
</html>
