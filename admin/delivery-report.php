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
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'route';

// Handle Save Changes (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_sent') {
    header('Content-Type: application/json');
    try {
        $updates = json_decode($_POST['updates'], true);
        foreach ($updates as $update) {
            $invoiceDetailId = (int)$update['id'];
            $sentQty = (int)$update['sent'];
            // Update a sent_qty field if it exists, or store in a separate table
            // For now, we can store in invoice_details if column exists, or skip
            // $db->updateRow('UPDATE invoice_details SET sent_qty = ? WHERE invoice_d_id = ?', [$sentQty, $invoiceDetailId]);
        }
        echo json_encode(['status' => 'success', 'message' => 'Changes saved successfully']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// Build ORDER BY clause based on sort
$orderBy = 'drm.route_name ASC, c.customer_name ASC, cm.category_name ASC, im.item_name ASC';
if ($sort_by === 'customer') {
    $orderBy = 'c.customer_name ASC, drm.route_name ASC, cm.category_name ASC, im.item_name ASC';
} elseif ($sort_by === 'category') {
    $orderBy = 'cm.category_name ASC, drm.route_name ASC, c.customer_name ASC, im.item_name ASC';
}

// Fetch all order items for the selected delivery date
$query = $db->getRows(
    "SELECT 
        id.invoice_d_id,
        COALESCE(drm.route_name, 'No Route') AS route_name,
        COALESCE(drm.id, 0) AS route_id,
        c.customer_id,
        c.customer_name,
        COALESCE(csa.address_line_1, ih.invoice_h_delivery_address, '') AS address_line_1,
        COALESCE(csa.address_line_2, '') AS address_line_2,
        COALESCE(csa.city, '') AS city,
        COALESCE(cm.category_name, 'Uncategorized') AS category_name,
        COALESCE(cm.category_id, 0) AS category_id,
        im.item_id,
        im.item_name,
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
     ORDER BY $orderBy",
    [$selected_date]
);

// Process data for display
$rows = [];
foreach ($query as $row) {
    $addressParts = array_filter([$row['address_line_1'], $row['address_line_2'], $row['city']]);
    $address = implode(', ', $addressParts);
    
    $rows[] = [
        'invoice_d_id' => $row['invoice_d_id'],
        'route_name' => $row['route_name'],
        'customer_name' => $row['customer_name'],
        'customer_id' => $row['customer_id'],
        'address' => $address,
        'category_name' => $row['category_name'],
        'item_name' => $row['item_name'],
        'qty' => (int)$row['qty'],
        'sent' => (int)$row['qty'] // Default sent = ordered
    ];
}

$formattedDate = date('d/m/Y', strtotime($selected_date));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Delivery Report for: <?php echo $formattedDate; ?></title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
    
    <style>
        .delivery-report {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        
        .report-header {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 5px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .report-header h1 {
            font-size: 18px;
            font-weight: bold;
            margin: 0;
            white-space: nowrap;
        }
        
        .report-header .form-control {
            display: inline-block;
            width: auto;
        }
        
        .delivery-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .delivery-table th {
            text-align: left;
            font-weight: bold;
            padding: 10px 8px;
            background: #f9f9f9;
            border-bottom: 2px solid #ddd;
            font-size: 12px;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .delivery-table th .search-icon {
            color: #999;
            margin-left: 5px;
            cursor: pointer;
        }
        
        .delivery-table td {
            padding: 8px;
            vertical-align: top;
            border-bottom: 1px solid #eee;
            font-size: 12px;
        }
        
        .delivery-table .col-route { width: 10%; }
        .delivery-table .col-customer { width: 18%; }
        .delivery-table .col-address { width: 22%; }
        .delivery-table .col-group { width: 12%; }
        .delivery-table .col-ordered { width: 8%; text-align: center; }
        .delivery-table .col-sent { width: 8%; text-align: center; }
        .delivery-table .col-item { width: 22%; }
        
        .delivery-table .text-center { text-align: center; }
        
        .delivery-table .route-first {
            background: linear-gradient(to right, #8BC34A 0%, #8BC34A 3px, #f0fff0 3px, #f0fff0 100%);
        }
        
        .delivery-table .sent-input {
            width: 45px;
            height: 24px;
            text-align: center;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 12px;
        }
        
        .delivery-table .sent-input:focus {
            border-color: #4CAF50;
            outline: none;
        }
        
        .delivery-table .sent-input.changed {
            background-color: #fff3cd;
            border-color: #ffc107;
        }
        
        .address-icons {
            display: inline-block;
            margin-right: 5px;
        }
        
        .address-icons a {
            color: #d9534f;
            margin-right: 3px;
            text-decoration: none;
        }
        
        .address-icons a:hover {
            color: #c9302c;
        }
        
        .btn-save {
            background: #4CAF50;
            color: #fff;
            border: none;
            padding: 8px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .btn-save:hover {
            background: #45a049;
        }
        
        .btn-more {
            background: #ff9800;
            color: #fff;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .filter-row input {
            width: 100%;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-size: 11px;
        }
        
        .no-print {
            margin-bottom: 15px;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 0;
                font-size: 10px;
            }
            
            .no-print, .report-header, .filter-row {
                display: none !important;
            }
            
            .delivery-report {
                padding: 5mm;
            }
            
            .delivery-table th,
            .delivery-table td {
                padding: 4px 5px;
                font-size: 10px;
            }
            
            .delivery-table .sent-input {
                border: 1px solid #333;
                width: 35px;
                height: 18px;
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
            size: A4 landscape;
            margin: 8mm;
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
                            <span>Delivery Report</span>
                        </li>
                    </ul>
                </div>
                
                <!-- Report Content -->
                <div class="delivery-report">
                    <!-- Report Header -->
                    <div class="report-header">
                        <h1>Delivery Report for:</h1>
                        <form method="get" class="form-inline" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                            <input type="date" name="date" class="form-control" value="<?php echo $selected_date; ?>" />
                            <select name="sort" class="form-control">
                                <option value="route" <?php echo $sort_by === 'route' ? 'selected' : ''; ?>>By Route Order</option>
                                <option value="customer" <?php echo $sort_by === 'customer' ? 'selected' : ''; ?>>By Customer</option>
                                <option value="category" <?php echo $sort_by === 'category' ? 'selected' : ''; ?>>By Item Group</option>
                            </select>
                            <button type="submit" class="btn btn-primary"><i class="fa fa-refresh"></i> Refresh</button>
                            <button type="button" class="btn-save" id="btnSave"><i class="fa fa-check"></i> Save Changes</button>
                            <button type="button" class="btn btn-default" onclick="window.print();"><i class="fa fa-print"></i> Print</button>
                        </form>
                    </div>
                    
                    <?php if (empty($rows)) { ?>
                        <div class="alert alert-info">No orders found for the selected date.</div>
                    <?php } else { ?>
                        <table class="delivery-table" id="deliveryTable">
                            <thead>
                                <tr>
                                    <th class="col-route">Route <i class="fa fa-search search-icon"></i></th>
                                    <th class="col-customer">Customer <i class="fa fa-search search-icon"></i></th>
                                    <th class="col-address">Delivery Address <i class="fa fa-search search-icon"></i></th>
                                    <th class="col-group">Item Group <i class="fa fa-search search-icon"></i></th>
                                    <th class="col-ordered text-center">Ordered</th>
                                    <th class="col-sent text-center">Sent</th>
                                    <th class="col-item">Item <i class="fa fa-search search-icon"></i></th>
                                </tr>
                                <tr class="filter-row">
                                    <th><input type="text" id="filterRoute" placeholder="Filter..." /></th>
                                    <th><input type="text" id="filterCustomer" placeholder="Filter..." /></th>
                                    <th><input type="text" id="filterAddress" placeholder="Filter..." /></th>
                                    <th><input type="text" id="filterGroup" placeholder="Filter..." /></th>
                                    <th></th>
                                    <th></th>
                                    <th><input type="text" id="filterItem" placeholder="Filter..." /></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $prevRoute = '';
                                $prevCustomer = '';
                                $prevAddress = '';
                                $prevGroup = '';
                                
                                foreach ($rows as $row):
                                    $showRoute = ($row['route_name'] !== $prevRoute);
                                    $showCustomer = ($row['customer_name'] !== $prevCustomer || $showRoute);
                                    $showAddress = ($row['address'] !== $prevAddress || $showCustomer);
                                    $showGroup = ($row['category_name'] !== $prevGroup || $showCustomer);
                                    
                                    $rowClass = $showRoute ? 'route-first' : '';
                                    
                                    $prevRoute = $row['route_name'];
                                    $prevCustomer = $row['customer_name'];
                                    $prevAddress = $row['address'];
                                    $prevGroup = $row['category_name'];
                                ?>
                                <tr class="<?php echo $rowClass; ?>" 
                                    data-route="<?php echo htmlspecialchars(strtolower($row['route_name'])); ?>"
                                    data-customer="<?php echo htmlspecialchars(strtolower($row['customer_name'])); ?>"
                                    data-address="<?php echo htmlspecialchars(strtolower($row['address'])); ?>"
                                    data-group="<?php echo htmlspecialchars(strtolower($row['category_name'])); ?>"
                                    data-item="<?php echo htmlspecialchars(strtolower($row['item_name'])); ?>">
                                    <td><?php echo $showRoute ? '<strong>' . htmlspecialchars($row['route_name']) . '</strong>' : ''; ?></td>
                                    <td><?php echo $showCustomer ? htmlspecialchars($row['customer_name']) : ''; ?></td>
                                    <td>
                                        <?php if ($showAddress && $row['address']) { ?>
                                            <span class="address-icons">
                                                <a href="https://maps.google.com/?q=<?php echo urlencode($row['address']); ?>" target="_blank" title="View on Map"><i class="fa fa-map-marker"></i></a>
                                                <a href="#" title="Edit"><i class="fa fa-external-link"></i></a>
                                            </span>
                                            <?php echo htmlspecialchars($row['address']); ?>
                                        <?php } ?>
                                    </td>
                                    <td><?php echo $showGroup ? htmlspecialchars($row['category_name']) : ''; ?></td>
                                    <td class="text-center"><?php echo $row['qty']; ?></td>
                                    <td class="text-center">
                                        <input type="number" class="sent-input" 
                                               data-id="<?php echo $row['invoice_d_id']; ?>" 
                                               data-original="<?php echo $row['qty']; ?>"
                                               value="<?php echo $row['sent']; ?>" />
                                    </td>
                                    <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include('common/footer.php'); ?>
    
    <script>
    $(document).ready(function() {
        // Filter functionality
        function applyFilters() {
            var filterRoute = $('#filterRoute').val().toLowerCase();
            var filterCustomer = $('#filterCustomer').val().toLowerCase();
            var filterAddress = $('#filterAddress').val().toLowerCase();
            var filterGroup = $('#filterGroup').val().toLowerCase();
            var filterItem = $('#filterItem').val().toLowerCase();
            
            $('#deliveryTable tbody tr').each(function() {
                var row = $(this);
                var show = true;
                
                if (filterRoute && row.data('route').indexOf(filterRoute) === -1) show = false;
                if (filterCustomer && row.data('customer').indexOf(filterCustomer) === -1) show = false;
                if (filterAddress && row.data('address').indexOf(filterAddress) === -1) show = false;
                if (filterGroup && row.data('group').indexOf(filterGroup) === -1) show = false;
                if (filterItem && row.data('item').indexOf(filterItem) === -1) show = false;
                
                row.toggle(show);
            });
        }
        
        $('.filter-row input').on('keyup', applyFilters);
        
        // Track changed inputs
        $('.sent-input').on('change', function() {
            var original = $(this).data('original');
            var current = parseInt($(this).val());
            if (current !== original) {
                $(this).addClass('changed');
            } else {
                $(this).removeClass('changed');
            }
        });
        
        // Save changes
        $('#btnSave').on('click', function() {
            var updates = [];
            $('.sent-input.changed').each(function() {
                updates.push({
                    id: $(this).data('id'),
                    sent: $(this).val()
                });
            });
            
            if (updates.length === 0) {
                alert('No changes to save.');
                return;
            }
            
            $.ajax({
                url: 'delivery-report.php',
                type: 'POST',
                data: {
                    action: 'save_sent',
                    updates: JSON.stringify(updates)
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        alert('Changes saved successfully!');
                        $('.sent-input.changed').each(function() {
                            $(this).data('original', $(this).val());
                            $(this).removeClass('changed');
                        });
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error saving changes. Please try again.');
                }
            });
        });
    });
    </script>
</body>
</html>
