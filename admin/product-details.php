<?php 
ob_start();
error_reporting (E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');

// Handle AJAX requests for availability
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    ob_clean(); // Clear any buffered output
    header('Content-Type: application/json');
    $db = new Database();
    try {
        if ($_POST['action'] === 'get_availability') {
            $productId = $_POST['product_id'] ?? null;
            if (!$productId) {
                echo json_encode(['success' => false, 'message' => 'Product ID required']);
                exit;
            }
            $availability = $db->getRow('SELECT id, mon, tue, wed, thu, fri, sat, sun FROM product_availability WHERE product_id = ? LIMIT 1', [$productId]);
            echo json_encode(['success' => true, 'data' => $availability]);
            exit;
        }
        elseif ($_POST['action'] === 'save_availability') {
            $productId = $_POST['product_id'] ?? null;
            $mon = $_POST['mon'] ?? 0;
            $tue = $_POST['tue'] ?? 0;
            $wed = $_POST['wed'] ?? 0;
            $thu = $_POST['thu'] ?? 0;
            $fri = $_POST['fri'] ?? 0;
            $sat = $_POST['sat'] ?? 0;
            $sun = $_POST['sun'] ?? 0;

            if (!$productId) {
                echo json_encode(['success' => false, 'message' => 'Product ID required']);
                exit;
            }

            // Check if availability already exists
            $existing = $db->getRow('SELECT id FROM product_availability WHERE product_id = ? LIMIT 1', [$productId]);

            if ($existing) {
                // Update existing
                $db->updateRow('UPDATE product_availability SET mon=?, tue=?, wed=?, thu=?, fri=?, sat=?, sun=?, updated_at=NOW() WHERE product_id=?',
                    [$mon, $tue, $wed, $thu, $fri, $sat, $sun, $productId]);
                echo json_encode(['success' => true, 'message' => 'Availability updated successfully']);
            } else {
                // Insert new
                $db->insertRow('INSERT INTO product_availability (product_id, mon, tue, wed, thu, fri, sat, sun) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                    [$productId, $mon, $tue, $wed, $thu, $fri, $sat, $sun]);
                echo json_encode(['success' => true, 'message' => 'Availability added successfully']);
            }
            exit;
        }
        elseif ($_POST['action'] === 'save_low_stock_qty') {
            $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
            $lowStockQty = isset($_POST['low_stock_qty']) ? (int)$_POST['low_stock_qty'] : 5;

            if ($productId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Product ID required']);
                exit;
            }

            if ($lowStockQty < 0) {
                $lowStockQty = 0;
            }

            $db->updateRow('UPDATE item_master SET low_stock_qty = ? WHERE item_id = ?', [$lowStockQty, $productId]);
            echo json_encode(['success' => true, 'message' => 'Low stock qty updated successfully']);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

$db = new Database();

#currency
$getcurrency = $db->getRow('SELECT * FROM currency WHERE activated = ? LIMIT 1 ',["Y"]);
$currency = $getcurrency['currency'];

    $get_product_id = $_GET['pid'];
    if(!empty($get_product_id) || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']))) {
    $db = new Database();
    $get_real_produt_id = $db->getRow('SELECT * FROM item_master WHERE item_id = ? ',[$get_product_id]);
    $real_product_id = $get_real_produt_id['item_id'];

    }
    else
    {

        header('location:manage-product.php');
        exit();
    }

   

    if($get_product_id > 0 && $get_product_id == $real_product_id) {

            $product_name = $get_real_produt_id['item_name'];
            $product_code = $get_real_produt_id['item_code'];
            $product_cost_price = $get_real_produt_id['item_purchase_price'];
            $product_min_price = $get_real_produt_id['item_min_selling_price'];
            $product_normal_price = $get_real_produt_id['item_normal_selling_price'];
            $product_cash_price = $get_real_produt_id['item_cash_selling_price'];
            $product_credit_price = $get_real_produt_id['item_cradit_selling_price'];
            $product_cod = $get_real_produt_id['item_cod'];
            $product_weight = $get_real_produt_id['item_weight'];
            $product_order_qty_min = $get_real_produt_id['order_qty_min'];
            $product_order_qty_max = $get_real_produt_id['order_qty_max'];
            $product_low_stock_qty = isset($get_real_produt_id['low_stock_qty']) ? (int)$get_real_produt_id['low_stock_qty'] : 5;
            $product_pack_size = $get_real_produt_id['pack_size'];
            $product_acc_posting_grp_code = $get_real_produt_id['acc_posting_grp_code'];
            $product_gst_vat_code = $get_real_produt_id['gst_vat_code'];

            // Additional Product Information fields
            $product_wholesale_price = $get_real_produt_id['wholesale_price'];
            $product_retail_price = $get_real_produt_id['retail_price'];
            $product_item_weight_g = $get_real_produt_id['item_weight_g'];
            $product_pack_weight_g = $get_real_produt_id['pack_weight_g'];
            $product_minimum_order = $get_real_produt_id['minimum_order'];
            $product_unit_of_measure = $get_real_produt_id['unit_of_measure'];
            $product_pack_type = $get_real_produt_id['pack_type'];
            $product_live = $get_real_produt_id['live'];
            $product_nutritional_label = $get_real_produt_id['nutritional_label'];
            $product_specification = $get_real_produt_id['product_specification'];
            $product_default_label = $get_real_produt_id['default_label'];
            $product_seasonal_rule = $get_real_produt_id['seasonal_rule'];
            $product_food_declarations = $get_real_produt_id['food_declarations'];
            $product_avail_monday = $get_real_produt_id['avail_monday'];
            $product_avail_tuesday = $get_real_produt_id['avail_tuesday'];
            $product_avail_wednesday = $get_real_produt_id['avail_wednesday'];
            $product_avail_thursday = $get_real_produt_id['avail_thursday'];
            $product_avail_friday = $get_real_produt_id['avail_friday'];
            $product_avail_saturday = $get_real_produt_id['avail_saturday'];
            $product_avail_sunday = $get_real_produt_id['avail_sunday'];
            $product_hide_to_all_customers = $get_real_produt_id['hide_to_all_customers'];
            $product_sale_or_return = $get_real_produt_id['sale_or_return'];
            $product_is_raw_material = $get_real_produt_id['is_raw_material'];
            $product_batch_tracking = $get_real_produt_id['batch_tracking'] ?? 'NONE';

           

                   
               

                function itemParth()
                {
                    $get_product_id = $_GET['pid'];
                    $db = new Database();
                    $get_real_produt_id = $db->getRow('SELECT * FROM item_master WHERE item_id = ? ',[$get_product_id]);
                     $product_category_id = $get_real_produt_id['item_category'];
                     $product_group_id = $get_real_produt_id['item_group'];
                     $product_type_id = $get_real_produt_id['item_type'];
                        if($product_category_id)
                        {
                            #category
                            $get_real_category_id = $db->getRow('SELECT * FROM category_master WHERE  category_id = ? ',[$product_category_id]);
                            $product_category = $get_real_category_id['category_name'];
                            #group
                            $get_real_group_id = $db->getRow('SELECT * FROM gorup_master WHERE  group_id = ? ',[$product_group_id]);
                             $product_group = $get_real_group_id['group_name'];
                            #type
                            $get_real_type_id = $db->getRow('SELECT * FROM type_master WHERE  type_id = ? ',[$product_type_id]);
                            $product_type = $get_real_type_id['type_name'];
                            $db->Disconnect();
                           return $item_parth = $product_group." --> ".$product_type." --> ".$product_category;
                        }


                    

                }

                function itemUnit()
                    {
                        $get_product_id = $_GET['pid'];
                        $db = new Database();
                        $get_real_produt_id = $db->getRow('SELECT * FROM item_master WHERE item_id = ? ',[$get_product_id]);
                        $product_unit_id = $get_real_produt_id['item_uom'];

                        if($product_unit_id)
                        {

                                $get_real_uom_id = $db->getRow('SELECT * FROM item_uom WHERE  uom_id = ? ',[$product_unit_id]);
                                $db->Disconnect();
                                return $product_uom = $get_real_uom_id['uom_name'];

                        }



                    }

                function warranty()
                    {
                        $get_product_id = $_GET['pid'];
                        $db = new Database();
                        $get_real_produt_id = $db->getRow('SELECT * FROM item_master WHERE item_id = ? ',[$get_product_id]);
                        $product_warranty_id = $get_real_produt_id['item_warranty'];

                         if($product_warranty_id)
                        {

                                $get_real_warranty_id = $db->getRow('SELECT * FROM item_warranty WHERE  warranty_id = ? ',[$product_warranty_id]);
                                $db->Disconnect();
                                return $product_warranty = $get_real_warranty_id['warranty'];

                        }


                    }


                 function itemVat()
                    {
                        $get_product_id = $_GET['pid'];
                        $db = new Database();
                        $get_real_produt_id = $db->getRow('SELECT * FROM item_master WHERE item_id = ? ',[$get_product_id]);
                        $product_vat_id = $get_real_produt_id['item_vat'];
                        $gst_vat_code = $get_real_produt_id['gst_vat_code'];
                         $db->Disconnect();
                        if($product_vat_id == "Y")
                        {

                            $product_vat = "Included";
                        }
                        elseif($product_vat_id == "N")
                        {

                            $product_vat = "0.00%";

                        }
                        else
                        {

                            $product_vat = "Someting wrrong";

                        }
                        return $product_vat;

                    }

                    function description()
                    {

                        $get_product_id = $_GET['pid'];
                        $db = new Database();
                        $get_real_produt_id = $db->getRow('SELECT * FROM item_master WHERE item_id = ? ',[$get_product_id]);
                        $product_description = $get_real_produt_id['item_discription'];

                        if($product_description)
                        {
                            return $product_description;

                        }
                        else
                        {

                            return "There're no Product Description";

                        }
                         $db->Disconnect();


                    }


                    function qty()

                    {


                       
                        $get_product_id = $_GET['pid'];
                        $db = new Database();
                        $query_get_qty = $db->getRows('SELECT SUM(ft_blanace) as qty ,ft_location  FROM fifo WHERE ft_item = ? GROUP BY ft_location',[$get_product_id]);
                            
                        return $query_get_qty ; 

                    }

                    function batchTrackingLabel($trackingMode)
                    {
                        if($trackingMode === 'BATCH')
                        {
                            return 'Batch No Tracking';
                        }
                        if($trackingMode === 'SERIAL')
                        {
                            return 'Serial No Tracking';
                        }
                        return 'Disabled';
                    }

                    function trackedInventoryByLocation($productId)
                    {
                        $db = new Database();
                        return $db->getRows(
                            'SELECT f.ft_location, lm.location_code, lm.name AS location_name, bm.batch_no, bm.expiry_date, SUM(f.ft_blanace) AS qty
                             FROM fifo f
                             INNER JOIN batch_master bm ON bm.batch_id = f.batch_id
                             LEFT JOIN location_master lm ON lm.id = f.ft_location
                             WHERE f.ft_item = ? AND f.ft_type = 1 AND f.ft_blanace > 0 AND f.batch_id IS NOT NULL
                             GROUP BY f.ft_location, lm.location_code, lm.name, bm.batch_id, bm.batch_no, bm.expiry_date
                             ORDER BY lm.name ASC, bm.expiry_date ASC, bm.batch_no ASC',
                            [$productId]
                        );
                    }

                    function formatNullableNumber($value, $decimals = 2)
                    {
                        if($value === null || $value === '')
                        {
                            return 'N/A';
                        }
                        return number_format((float)$value, $decimals, '.', '');
                    }

                      function getContent() {
        $db = new Database();
        $query_invoice = $db->getRows('SELECT invH.invoice_h_id,invH.invoice_h_code , invH.invoice_h_customer_id , invH.invoice_h_date , invH.invoice_h_net_value , invH.add_by ,invH.invoice_h_status,invH.add_by, invH.invoice_h_location ,invD.invoice_d_item_id , invD.invoice_d_qty , invD.invoice_d_item_price , invD.invoice_d_vat , invD.invoice_d_vat_rate , invD.invoice_d_discount_value , invD.invoice_h_id 
                                            FROM invoice_hedder invH 
                                            JOIN invoice_details invD 
                                            ON invH.invoice_h_id = invD.invoice_h_id 
                                            WHERE invD.invoice_d_item_id = ? 
                                            AND invH.invoice_h_location = ?
                                            AND invH.invoice_h_status = ?
                                            ORDER BY invD.invoice_d_item_id DESC LIMIT 10',[$_GET['pid'],$_SESSION['location'],1]);
        return $query_invoice;
    }

    $product_category_path = itemParth();
    $product_unit_label = itemUnit();
    $product_warranty_label = warranty();
    $product_vat_label = itemVat();
    $product_description = description();
    $product_qty_by_location = qty();
    $product_batch_tracking_label = batchTrackingLabel($product_batch_tracking);
    $product_batch_inventory = trackedInventoryByLocation($get_product_id);
    $product_availability = getProductAvailability($get_product_id);
    }

    else
    {


        header('location:manage-product.php');
        exit();
    }

    function getProductAvailability($productId) {
        try {
            $db = new Database();
            $row = $db->getRow('SELECT id, mon, tue, wed, thu, fri, sat, sun FROM product_availability WHERE product_id = ? LIMIT 1', [$productId]);
            return $row ?: null;
        } catch (Exception $e) {
            return null;
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
        <title>Product Details</title>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta content="width=device-width, initial-scale=1" name="viewport" />
        <meta content="" name="description" />
        <meta content="" name="author" />
        <?php include('common/head.php'); ?>
        <style>
            .product-summary-grid { display: flex; flex-wrap: wrap; gap: 20px; }
            .product-summary-card { background: #ffffff; border: 1px solid #e7ecf1; border-radius: 8px; box-shadow: 0 8px 20px rgba(44, 62, 80, 0.08); padding: 2px; flex: 1 1 280px; transition: transform 0.2s ease, box-shadow 0.2s ease; }
            .product-summary-card:hover { transform: translateY(-4px); box-shadow: 0 12px 22px rgba(44, 62, 80, 0.12); }
            .product-summary-card h5 { font-size: 13px; letter-spacing: 0.08em; text-transform: uppercase; color: #6c757d; margin-top: 0; margin-bottom: 16px; }
            .product-meta-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; gap: 12px; padding: 8px 12px; background: #f8f9fa; border-radius: 4px; border-left: 4px solid #0066cc; transition: background 0.2s ease; }
            .product-meta-row:hover { background: #e9ecef; }
            .product-meta-label { font-size: 13px; color: #6e6e6e; font-weight: 500; }
            .product-meta-value { font-size: 15px; font-weight: 600; color: #2f3b52; text-align: right; }
            .product-meta-value small { font-weight: 400; color: #8896a5; }
            .barcode-wrapper { background: #f4f7fb; border-radius: 6px; padding: 12px; text-align: center; margin-top: 12px; }
            .barcode-wrapper img { max-width: 100%; height: auto; }
            .product-description-card .product-description { font-size: 14px; line-height: 1.7; color: #394b63; }
            .product-description-card { margin-top: 20px; }
            @media (max-width: 991px) { .product-summary-grid { flex-direction: column; } }
            /* Section card h5 headers with Australian-inspired blue design */
            .section-card.h4 {
                background: linear-gradient(90deg, #0066cc 0%, #004080 100%);
                color: #fff;
                padding: 12px 18px;
                border-radius: 6px 6px 0 0;
                font-size: 16px;
                font-weight: 600;
                letter-spacing: 0.05em;
                box-shadow: 0 2px 6px rgba(0,102,204,0.15);
                margin-bottom: 0;
                margin-top: 0;
            }
            /* Availability days styling */
            .availability-grid {
                display: grid;
                grid-template-columns: repeat(7, 1fr);
                gap: 8px;
                margin-top: 12px;
            }
            .day-item {
                padding: 8px 4px;
                border-radius: 4px;
                text-align: center;
                font-size: 12px;
                font-weight: 600;
                transition: all 0.2s ease;
            }
            .day-item.available {
                background: #28a745;
                color: white;
                border: 1px solid #218838;
            }
            .day-item.unavailable {
                background: #dc3545;
                color: white;
                border: 1px solid #c82333;
            }
            .day-label {
                display: block;
                font-size: 11px;
                margin-top: 2px;
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
                                <a href="index-2.html">Home</a>
                                <i class="fa fa-circle"></i>
                            </li>
                            <li>
                                <a href="#">Product</a>
                                <i class="fa fa-circle"></i>
                            </li>
                            <li>
                                <span>Product Details</span>
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
                            <div class="row">
  
  <div class="col-md-12 text-right"> <div class="btn-group btn-group-solid">

                                                               <div class="btn-group">

                                                <a title="" class="tip btn btn blue-madison" target="popup" href="print_barcode.php?pid=<?php echo $real_product_id; ?>" onclick="window.open('print_barcode.php?pid=<?php echo $real_product_id; ?>','name','width=auto,height=600')">

                                                </i> <span class="hidden-sm hidden-xs"> Print Barcode</span>

                                                </a>

                                                </div>
                                                             <div class="btn-group">

                                                <a title="" class="tip btn btn blue-madison" href="">

                                                <i class="fa fa-print"></i> <span class="hidden-sm hidden-xs"> Print Label</span>

                                                </a>

                                                </div>
                                                                    
                                                                <div class="btn-group">

                                                <a title="" class="tip btn btn-warning tip" href="edit-product.php?pid=<?php echo $real_product_id; ?> ">

                                                <i class="fa fa-edit"></i> <span class="hidden-sm hidden-xs">Edit</span>

                                                </a>

                                                </div>

                                                                      <div class="btn-group">

                                                <a title="" class="tip btn btn red" href="">

                                                <i class="fa fa-trash-o"></i> <span class="hidden-sm hidden-xs"> Delete</span>

                                                </a>

                                                </div>
                                                              
                                                            </div>
                           </div>
</div>
                         
                      
                             <div class="portlet-body">
                                <div class="tabbable-custom ">
                                        <ul class="nav nav-tabs ">
                                            <li class="active">
                                                <a href="#tab_5_1" data-toggle="tab" aria-expanded="true">Product Details </a>
                                            </li>
                                            <li class="">
                                                <a href="#tab_5_2" data-toggle="tab" aria-expanded="false"> Sales </a>
                                            </li>
                                            <li class="">
                                                <a href="#tab_5_3" data-toggle="tab" aria-expanded="false"> Purchases </a>
                                            </li>
                                            <li class="">
                                                <a href="#tab_5_4" data-toggle="tab" aria-expanded="false"> Availability </a>
                                            </li>
                                        </ul>
                                        <div class="tab-content">
                                            <div class="tab-pane active" id="tab_5_1">
                                               <div class="portlet light bordered">
                                            <div class="portlet-title">
                                                <div class="caption">
                                                    <i class="fa-fw fa fa-file-text-o nb"></i>
                                                    <span class="caption-subject font-red-sunglo bold uppercase"><?php echo htmlspecialchars($product_name, ENT_QUOTES, 'UTF-8'); ?></span>
                                                    
                                                </div>
                                               
                                            </div>
                                            <div class="portlet-body form">
                                                <!-- BEGIN FORM-->
                                                <div class="row">
                                                    <div class="col-lg-8 col-md-12">
                                                        <div class="product-summary-grid">
                                                            <div class="product-summary-card">
                                                                <h5 class="section-card h4">Identifiers</h5>
                                                                <div class="product-meta-row">
                                                                    <span class="product-meta-label">Product Name</span>
                                                                    <span class="product-meta-value"><?php echo htmlspecialchars($product_name, ENT_QUOTES, 'UTF-8'); ?></span>
                                                                </div>
                                                                <div class="product-meta-row">
                                                                    <span class="product-meta-label">Product Code</span>
                                                                    <span class="product-meta-value"><?php echo htmlspecialchars($product_code, ENT_QUOTES, 'UTF-8'); ?></span>
                                                                </div>
                                                                <div class="product-meta-row">
                                                                    <span class="product-meta-label">Category Path</span>
                                                                    <span class="product-meta-value"><?php echo $product_category_path ? htmlspecialchars($product_category_path, ENT_QUOTES, 'UTF-8') : 'N/A'; ?></span>
                                                                </div>
                                                                <div class="product-meta-row">
                                                                    <span class="product-meta-label">Warranty</span>
                                                                    <span class="product-meta-value"><?php echo $product_warranty_label ? htmlspecialchars($product_warranty_label, ENT_QUOTES, 'UTF-8') : 'N/A'; ?></span>
                                                                </div>
                                                                <div class="barcode-wrapper">
                                                                    <img src="barcode_process.php?pid=<?php echo urlencode($product_code); ?>" alt="Barcode for <?php echo htmlspecialchars($product_name, ENT_QUOTES, 'UTF-8'); ?>" />
                                                                </div>
                                                            </div>
                                                            <div class="product-summary-card">
                                                                <h5 class="section-card h4">Pricing</h5>
                                                                <div class="product-meta-row">
                                                                    <span class="product-meta-label">Cost Price</span>
                                                                    <span class="product-meta-value"><?php echo htmlspecialchars($currency, ENT_QUOTES, 'UTF-8'); ?> <?php echo number_format((float)$product_cost_price, 2, '.', ''); ?></span>
                                                                </div>
                                                                <div class="product-meta-row">
                                                                    <span class="product-meta-label">Wholesale Price</span>
                                                                    <span class="product-meta-value"><?php echo $product_wholesale_price ? htmlspecialchars($currency, ENT_QUOTES, 'UTF-8') . ' ' . number_format((float)$product_wholesale_price, 2, '.', '') : 'N/A'; ?></span>
                                                                </div>
                                                                <div class="product-meta-row">
                                                                    <span class="product-meta-label">Retail Price</span>
                                                                    <span class="product-meta-value"><?php echo $product_retail_price ? htmlspecialchars($currency, ENT_QUOTES, 'UTF-8') . ' ' . number_format((float)$product_retail_price, 2, '.', '') : 'N/A'; ?></span>
                                                                </div>
                                                                <div class="product-meta-row">
                                                                    <span class="product-meta-label">Normal Selling Price</span>
                                                                    <span class="product-meta-value"><?php echo htmlspecialchars($currency, ENT_QUOTES, 'UTF-8'); ?> <?php echo number_format((float)$product_normal_price, 2, '.', ''); ?></span>
                                                                </div>
                                                               
                                                              
                                                              
                                                            </div>
                                                            <div class="product-summary-card">
                                                                <h5 class="section-card h4">Logistics &amp; Compliance</h5>
                                                                <div class="product-meta-row">
                                                                    <span class="product-meta-label">Unit of Measure</span>
                                                                    <span class="product-meta-value"><?php echo $product_unit_label ? htmlspecialchars($product_unit_label, ENT_QUOTES, 'UTF-8') : 'N/A'; ?></span>
                                                                </div>
                                                                <div class="product-meta-row">
                                                                    <span class="product-meta-label">Pack Size</span>
                                                                    <span class="product-meta-value"><?php echo !empty($product_pack_size) ? htmlspecialchars($product_pack_size, ENT_QUOTES, 'UTF-8') : 'N/A'; ?></span>
                                                                </div>
                                                                <div class="product-meta-row">
                                                                    <span class="product-meta-label">Order Qty (Min)</span>
                                                                    <span class="product-meta-value"><?php echo formatNullableNumber($product_order_qty_min); ?></span>
                                                                </div>
                                                                <div class="product-meta-row">
                                                                    <span class="product-meta-label">Order Qty (Max)</span>
                                                                    <span class="product-meta-value"><?php echo formatNullableNumber($product_order_qty_max); ?></span>
                                                                </div>
                                                                <div class="product-meta-row" style="align-items:flex-start;">
                                                                    <span class="product-meta-label">Low Stock Qty</span>
                                                                    <span class="product-meta-value" style="width:100%; max-width:280px;">
                                                                        <form id="lowStockQtyForm" style="display:flex; gap:8px; align-items:center; margin:0;">
                                                                            <input type="hidden" name="product_id" value="<?php echo (int)$get_product_id; ?>">
                                                                            <input type="number" class="form-control" name="low_stock_qty" min="0" step="1" value="<?php echo (int)$product_low_stock_qty; ?>" style="max-width:110px;">
                                                                            <button type="submit" class="btn btn-xs btn-primary">Save</button>
                                                                        </form>
                                                                    </span>
                                                                </div>
                                                                <div class="product-meta-row">
                                                                    <span class="product-meta-label">Weight</span>
                                                                    <span class="product-meta-value"><?php echo formatNullableNumber($product_weight); ?> <small>kg</small></span>
                                                                </div>
                                                                <div class="product-meta-row">
                                                                    <span class="product-meta-label">Item Weight</span>
                                                                    <span class="product-meta-value"><?php echo $product_item_weight_g ? formatNullableNumber($product_item_weight_g) . ' <small>g</small>' : 'N/A'; ?></span>
                                                                </div>
                                                                <div class="product-meta-row">
                                                                    <span class="product-meta-label">Pack Weight</span>
                                                                    <span class="product-meta-value"><?php echo $product_pack_weight_g ? formatNullableNumber($product_pack_weight_g) . ' <small>g</small>' : 'N/A'; ?></span>
                                                                </div>
                                                                <div class="product-meta-row">
                                                                    <span class="product-meta-label">Minimum Order Qty</span>
                                                                    <span class="product-meta-value"><?php echo $product_minimum_order ? formatNullableNumber($product_minimum_order) : 'N/A'; ?></span>
                                                                </div>
                                                                <div class="product-meta-row">
                                                                    <span class="product-meta-label">Unit of Measure</span>
                                                                    <span class="product-meta-value"><?php echo $product_unit_of_measure ? htmlspecialchars($product_unit_of_measure, ENT_QUOTES, 'UTF-8') : 'N/A'; ?></span>
                                                                </div>
                                                                <div class="product-meta-row">
                                                                    <span class="product-meta-label">Pack Type</span>
                                                                    <span class="product-meta-value"><?php echo $product_pack_type ? htmlspecialchars($product_pack_type, ENT_QUOTES, 'UTF-8') : 'N/A'; ?></span>
                                                                </div>
                                                                <div class="product-meta-row">
                                                                    <span class="product-meta-label">Live Status</span>
                                                                    <span class="product-meta-value"><?php echo $product_live === 'yes' ? 'Live' : ($product_live === 'no' ? 'Not Live' : 'N/A'); ?></span>
                                                                </div>
                                                                <div class="product-meta-row">
                                                                    <span class="product-meta-label">Acc Posting Group</span>
                                                                    <span class="product-meta-value"><?php echo !empty($product_acc_posting_grp_code) ? htmlspecialchars($product_acc_posting_grp_code, ENT_QUOTES, 'UTF-8') : 'N/A'; ?></span>
                                                                </div>
                                                                <div class="product-meta-row">
                                                                    <span class="product-meta-label">GST Code</span>
                                                                    <span class="product-meta-value"><?php echo !empty($product_gst_vat_code) ? htmlspecialchars($product_gst_vat_code, ENT_QUOTES, 'UTF-8') : 'N/A'; ?></span>
                                                                </div>
                                                                <div class="product-meta-row">
                                                                    <span class="product-meta-label">Cash On Delivery</span>
                                                                    <span class="product-meta-value"><?php $codLabel = ($product_cod === 'enable') ? 'Enabled' : (($product_cod === 'disable') ? 'Disabled' : $product_cod); echo htmlspecialchars($codLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-4 col-md-12">
                                                        <div class="product-summary-card">
                                                            <h5 class="section-card h4">Additional Information</h5>
                                                            <div class="product-meta-row">
                                                                <span class="product-meta-label">Nutritional Label</span>
                                                                <span class="product-meta-value"><?php echo $product_nutritional_label ? htmlspecialchars($product_nutritional_label, ENT_QUOTES, 'UTF-8') : 'N/A'; ?></span>
                                                            </div>
                                                            <div class="product-meta-row">
                                                                <span class="product-meta-label">Product Specification</span>
                                                                <span class="product-meta-value"><?php echo $product_specification ? htmlspecialchars($product_specification, ENT_QUOTES, 'UTF-8') : 'N/A'; ?></span>
                                                            </div>
                                                            <div class="product-meta-row">
                                                                <span class="product-meta-label">Default Label</span>
                                                                <span class="product-meta-value"><?php echo $product_default_label ? htmlspecialchars($product_default_label, ENT_QUOTES, 'UTF-8') : 'N/A'; ?></span>
                                                            </div>
                                                            <div class="product-meta-row">
                                                                <span class="product-meta-label">Seasonal Rule</span>
                                                                <span class="product-meta-value"><?php echo $product_seasonal_rule ? htmlspecialchars($product_seasonal_rule, ENT_QUOTES, 'UTF-8') : 'N/A'; ?></span>
                                                            </div>
                                                            <div class="product-meta-row">
                                                                <span class="product-meta-label">Food Declarations</span>
                                                                <span class="product-meta-value"><?php echo $product_food_declarations ? htmlspecialchars($product_food_declarations, ENT_QUOTES, 'UTF-8') : 'N/A'; ?></span>
                                                            </div>
                                                            <div class="product-meta-row">
                                                                <span class="product-meta-label">Hide to All Customers</span>
                                                                <span class="product-meta-value"><?php echo $product_hide_to_all_customers == 1 ? 'Yes' : 'No'; ?></span>
                                                            </div>
                                                            <div class="product-meta-row">
                                                                <span class="product-meta-label">Sale or Return</span>
                                                                <span class="product-meta-value"><?php echo $product_sale_or_return == 1 ? 'Yes' : 'No'; ?></span>
                                                            </div>
                                                            <div class="product-meta-row">
                                                                <span class="product-meta-label">Raw Material</span>
                                                                <span class="product-meta-value"><?php echo $product_is_raw_material == 1 ? '<span class="label label-success">Yes</span>' : '<span class="label label-default">No</span>'; ?></span>
                                                            </div>
                                                            <div class="product-meta-row">
                                                                <span class="product-meta-label">Batch / Serial Tracking</span>
                                                                <span class="product-meta-value">
                                                                    <?php if ($product_batch_tracking === 'NONE') { ?>
                                                                        <span class="label label-default"><?php echo htmlspecialchars($product_batch_tracking_label, ENT_QUOTES, 'UTF-8'); ?></span>
                                                                    <?php } else { ?>
                                                                        <span class="label label-info"><?php echo htmlspecialchars($product_batch_tracking_label, ENT_QUOTES, 'UTF-8'); ?></span>
                                                                    <?php } ?>
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="product-summary-card">
                                                            <h5 class="section-card h4">Availability Days</h5>
                                                            <div class="availability-grid">
                                                                <div class="day-item <?php echo $product_avail_monday == 1 ? 'available' : 'unavailable'; ?>">
                                                                    <span class="day-label">Mon</span>
                                                                </div>
                                                                <div class="day-item <?php echo $product_avail_tuesday == 1 ? 'available' : 'unavailable'; ?>">
                                                                    <span class="day-label">Tue</span>
                                                                </div>
                                                                <div class="day-item <?php echo $product_avail_wednesday == 1 ? 'available' : 'unavailable'; ?>">
                                                                    <span class="day-label">Wed</span>
                                                                </div>
                                                                <div class="day-item <?php echo $product_avail_thursday == 1 ? 'available' : 'unavailable'; ?>">
                                                                    <span class="day-label">Thu</span>
                                                                </div>
                                                                <div class="day-item <?php echo $product_avail_friday == 1 ? 'available' : 'unavailable'; ?>">
                                                                    <span class="day-label">Fri</span>
                                                                </div>
                                                                <div class="day-item <?php echo $product_avail_saturday == 1 ? 'available' : 'unavailable'; ?>">
                                                                    <span class="day-label">Sat</span>
                                                                </div>
                                                                <div class="day-item <?php echo $product_avail_sunday == 1 ? 'available' : 'unavailable'; ?>">
                                                                    <span class="day-label">Sun</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="product-summary-card">
                                                            <h5 class="section-card h4">Stock by Location</h5>
                                                            <div class="table-responsive">
                                                                <table class="table table-striped table-condensed">
                                                                  
                                                                    <tbody>
                                                                        <?php $qtyData = $product_qty_by_location; ?>
                                                                        <?php if(!empty($qtyData)) { foreach($qtyData as $query_get_qty) { 
                                            $location_id = $query_get_qty['ft_location'];
                                            $query_location_id =  $db->getRow('SELECT * FROM location_master WHERE id = ?',[$location_id]);
                                            $location_name = $query_location_id['name'];
                                                                        ?>
                                                                        <tr>
                                                                            <td><?php echo htmlspecialchars($location_name, ENT_QUOTES, 'UTF-8'); ?></td>
                                                                            <td class="text-right"><strong><?php echo formatNullableNumber($query_get_qty['qty'], 3); ?></strong></td>
                                                                        </tr>
                                                                        <?php } } else { ?>
                                                                        <tr>
                                                                            <td colspan="2" class="text-center text-muted">No stock recorded</td>
                                                                        </tr>
                                                                        <?php } ?>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                        <?php if (($product_batch_tracking ?? 'NONE') !== 'NONE') { ?>
                                                        <div class="product-summary-card">
                                                            <h5 class="section-card h4"><?php echo $product_batch_tracking === 'SERIAL' ? 'Serial No Inventory' : 'Batch Inventory'; ?></h5>
                                                            <div class="table-responsive">
                                                                <table class="table table-striped table-condensed">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Location</th>
                                                                            <th><?php echo $product_batch_tracking === 'SERIAL' ? 'Serial No' : 'Batch No'; ?></th>
                                                                            <th>Expiry</th>
                                                                            <th class="text-right">Available Qty</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php if (!empty($product_batch_inventory)) { foreach ($product_batch_inventory as $inventoryRow) {
                                                                            $trackedLocationLabel = trim(($inventoryRow['location_code'] ?? '') . ' - ' . ($inventoryRow['location_name'] ?? ''));
                                                                            $trackedExpiry = (!empty($inventoryRow['expiry_date']) && $inventoryRow['expiry_date'] !== '0000-00-00') ? date('d M Y', strtotime($inventoryRow['expiry_date'])) : 'N/A';
                                                                        ?>
                                                                        <tr>
                                                                            <td><?php echo htmlspecialchars($trackedLocationLabel, ENT_QUOTES, 'UTF-8'); ?></td>
                                                                            <td><?php echo htmlspecialchars($inventoryRow['batch_no'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                                            <td><?php echo htmlspecialchars($trackedExpiry, ENT_QUOTES, 'UTF-8'); ?></td>
                                                                            <td class="text-right"><strong><?php echo formatNullableNumber($inventoryRow['qty'], 3); ?></strong></td>
                                                                        </tr>
                                                                        <?php } } else { ?>
                                                                        <tr>
                                                                            <td colspan="4" class="text-center text-muted">No tracked inventory recorded yet.</td>
                                                                        </tr>
                                                                        <?php } ?>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                        <?php } ?>
                                                    </div>
                                                    <div class="col-sm-12">
                                                        <div class="product-summary-card product-description-card">
                                                            <h5 class="section-card h4">Description</h5>
                                                            <div class="product-description"><?php echo $product_description; ?></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <!-- END FORM-->

                                            </div>
                                        </div>
                                            </div>
                                            <div class="tab-pane" id="tab_5_2">
                                             <div class="portlet-body">
                                    <table class="table table-striped table-bordered table-hover dt-responsive" width="100%" id="sample_2">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th class="all">Invoice Date</th>
                                                <th class="all">Invoice No.</th>
                                                <th class="all">Customer Name</th>
                                                <th class="all">Biller</th>
                                                <th class="all">Product(Qty)</th>
                                                <th class="all">Grand Total</th>
                                                <th class="all">Location</th>
                                                <th class="all">Payment Status</th>
                                                
                                                
                                            
                                            </tr>
                                        </thead>
                                        <tbody>
                                              <?php $data = getContent();
                                        foreach($data as $query_invoice)
                                         { 
                                            $invoice_h_id = $query_invoice['invoice_h_id'];
                                            $customer_id = $query_invoice['invoice_h_customer_id'];
                                            $query_invoice_customer = $db->getRow('SELECT * FROM customer WHERE customer_id = ?',[$customer_id]);
                                            $query_invoice_amount = $db->getRow('SELECT *  FROM invoice_hedder WHERE invoice_h_id = ?',[$invoice_h_id]);
                                            $net_value = $query_invoice_amount['invoice_h_net_value'];
                                            $invoice_status = $query_invoice['invoice_h_status'];
                                            

                                            $query_customer_amount = $db->getRow('SELECT SUM(amount) as customer_amount FROM customer_balance WHERE invoice_h_id = ?',[$invoice_h_id]);
                                            $amount = $query_customer_amount['customer_amount'];
                                           

                                            $item_d_vat_rate = $query_invoice['invoice_d_vat_rate'];
                                            $item_d_vat_has = $query_invoice['invoice_d_vat'];
                                            $item_selling_price = $query_invoice['invoice_d_item_price'];
                                            $item_qty = $query_invoice['invoice_d_qty'];
                                            $invoice_location_id = $query_invoice['invoice_h_location'];
                                            $invoice_biller_id = $query_invoice['add_by'];

                                            $query_biller_id = $db->getRow('SELECT * FROM users WHERE userid = ?',[$invoice_biller_id]);
                                            $invoice_biller = $query_biller_id['first_name']." ".$query_biller_id['last_name'];

                                            $item_gross_value = ($item_selling_price * $item_qty);
                                            $invoice_d_vat_value = 0.00;
                                            if($item_d_vat_has == "Y"){

                                                $invoice_d_vat_value = ($item_gross_value * $item_d_vat_rate) / 100;

                                            }

                                            $query_location_id = $db->getRow('SELECT * FROM location_master WHERE id = ?',[$invoice_location_id]);
                                            $invoice_location_name  =  $query_location_id['name'];                                       

                                            $item_net_value = $item_gross_value + $invoice_d_vat_value;

                                            $query_item_select = $db->getRow('SELECT * FROM item_master WHERE item_id = ?',[$get_product_id]);
                                            $item_uom_id = $query_item_select['item_uom'];

                                            $query_uom = $db->getRow('SELECT uom_name uom FROM item_uom left join item_master on item_uom = uom_id WHERE item_uom = ?',[$item_uom_id]);
                                            $item_uom = $query_uom['uom'];
                                            
                                            if($query_invoice['invoice_d_qty'] > 1 && $item_uom_id == 1 ){

                                                $item_uom = "items";

                                            }

                                            if($item_d_vat_rate)
                                            {
                                                $item_d_vat_rate = $query_invoice['invoice_d_vat_rate']."%";

                                            }else{

                                                $item_d_vat_rate = "0.00%";

                                            }


                                           if($amount)
                                                {

                                                    $amount = $amount;
                                                }
                                                else
                                                {
                                                    $amount = 0;

                                                }

                                                $style = "";
                                                $status = "";
                                            if($net_value == $amount || $amount > $net_value )
                                            {

                                                $style = "lbl_Payment_status_paid";
                                                $status = "Paid";

                                            }
                                            elseif ($net_value > $amount && $amount != 0) {
                                                
                                                $style = "lbl_Payment_status_partial";
                                                $status = "Partial";
                                            }
                                            elseif ($amount == 0) {
                                                 $style = "lbl_Payment_status_pending";
                                                 $status = "Pending";
                                            }
                                            else
                                            {

                                                 $style = "lbl_Payment_status_pending";
                                                 $status = "Error";
                                            }

                                                #order Status

                                                if($invoice_status == 1)
                                                {
                                                    $order_status = "Acepct";

                                                }
                                                elseif($invoice_status == 0)
                                                {
                                                    $order_status = "Pending";

                                                }
                                                elseif($invoice_status == -1)
                                                {

                                                    $order_status = "Canceled";
                                                }
                                                else
                                                {
                                                    $order_status = "Something Wrong";

                                                }



                                         ?> 
                                             <tr>
                                                <th></th>

                                                <td><?php echo $query_invoice['invoice_h_date'];?></td>
                                                <td><?php echo $query_invoice['invoice_h_code'];?></td>
                                                <td><?php echo $query_invoice_customer['customer_name'];?></td>
                                                <td><?php echo $invoice_biller;?></td>
                                                <td><?php echo $query_invoice['invoice_d_qty']." ".$item_uom;?></td>
                                                <td><?php echo $currency." ".number_format((float)$item_net_value,2,'.',''); ?></td>
                                                <td><?php echo $invoice_location_name; ?></td>
                                                <td><span class="<?php echo $style; ?>"><?php echo "$status";?> </span></td>
                                            </tr>
                                        
                                            <?php }
                                            ?>
                                            
                                        </tbody>                                   
                                    </table>
                                </div>
                                            </div>
                                            <div class="tab-pane " id="tab_5_3">
                                               <p> Undercontrouction.. </p>
                                            </div>
                                            <div class="tab-pane" id="tab_5_4">
                                                <div class="portlet light bordered">
                                                    <div class="portlet-title">
                                                        <div class="caption">
                                                            <i class="fa fa-calendar"></i>
                                                            <span class="caption-subject font-blue bold uppercase">Delivery Availability</span>
                                                        </div>
                                                        <div class="actions">
                                                            <?php if ($product_availability): ?>
                                                                <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#editAvailabilityModal">
                                                                    <i class="fa fa-edit"></i> Edit Availability
                                                                </button>
                                                            <?php else: ?>
                                                                <button type="button" class="btn btn-success" data-toggle="modal" data-target="#addAvailabilityModal">
                                                                    <i class="fa fa-plus"></i> Add Availability
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="portlet-body">
                                                        <?php if ($product_availability): ?>
                                                            <div class="row">
                                                                <div class="col-md-12">
                                                                    <div class="table-responsive">
                                                                        <table class="table table-bordered table-striped">
                                                                            <thead>
                                                                                <tr>
                                                                                    <th class="text-center">Monday</th>
                                                                                    <th class="text-center">Tuesday</th>
                                                                                    <th class="text-center">Wednesday</th>
                                                                                    <th class="text-center">Thursday</th>
                                                                                    <th class="text-center">Friday</th>
                                                                                    <th class="text-center">Saturday</th>
                                                                                    <th class="text-center">Sunday</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                <tr>
                                                                                    <td class="text-center">
                                                                                        <span class="label label-<?php echo $product_availability['mon'] ? 'success' : 'danger'; ?>">
                                                                                            <i class="fa fa-<?php echo $product_availability['mon'] ? 'check' : 'times'; ?>"></i>
                                                                                            <?php echo $product_availability['mon'] ? 'Available' : 'Not Available'; ?>
                                                                                        </span>
                                                                                    </td>
                                                                                    <td class="text-center">
                                                                                        <span class="label label-<?php echo $product_availability['tue'] ? 'success' : 'danger'; ?>">
                                                                                            <i class="fa fa-<?php echo $product_availability['tue'] ? 'check' : 'times'; ?>"></i>
                                                                                            <?php echo $product_availability['tue'] ? 'Available' : 'Not Available'; ?>
                                                                                        </span>
                                                                                    </td>
                                                                                    <td class="text-center">
                                                                                        <span class="label label-<?php echo $product_availability['wed'] ? 'success' : 'danger'; ?>">
                                                                                            <i class="fa fa-<?php echo $product_availability['wed'] ? 'check' : 'times'; ?>"></i>
                                                                                            <?php echo $product_availability['wed'] ? 'Available' : 'Not Available'; ?>
                                                                                        </span>
                                                                                    </td>
                                                                                    <td class="text-center">
                                                                                        <span class="label label-<?php echo $product_availability['thu'] ? 'success' : 'danger'; ?>">
                                                                                            <i class="fa fa-<?php echo $product_availability['thu'] ? 'check' : 'times'; ?>"></i>
                                                                                            <?php echo $product_availability['thu'] ? 'Available' : 'Not Available'; ?>
                                                                                        </span>
                                                                                    </td>
                                                                                    <td class="text-center">
                                                                                        <span class="label label-<?php echo $product_availability['fri'] ? 'success' : 'danger'; ?>">
                                                                                            <i class="fa fa-<?php echo $product_availability['fri'] ? 'check' : 'times'; ?>"></i>
                                                                                            <?php echo $product_availability['fri'] ? 'Available' : 'Not Available'; ?>
                                                                                        </span>
                                                                                    </td>
                                                                                    <td class="text-center">
                                                                                        <span class="label label-<?php echo $product_availability['sat'] ? 'success' : 'danger'; ?>">
                                                                                            <i class="fa fa-<?php echo $product_availability['sat'] ? 'check' : 'times'; ?>"></i>
                                                                                            <?php echo $product_availability['sat'] ? 'Available' : 'Not Available'; ?>
                                                                                        </span>
                                                                                    </td>
                                                                                    <td class="text-center">
                                                                                        <span class="label label-<?php echo $product_availability['sun'] ? 'success' : 'danger'; ?>">
                                                                                            <i class="fa fa-<?php echo $product_availability['sun'] ? 'check' : 'times'; ?>"></i>
                                                                                            <?php echo $product_availability['sun'] ? 'Available' : 'Not Available'; ?>
                                                                                        </span>
                                                                                    </td>
                                                                                </tr>
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="alert alert-info">
                                                                <h4><i class="fa fa-info-circle"></i> No Availability Settings</h4>
                                                                <p>This product doesn't have delivery availability restrictions set. By default, it can be delivered on all days of the week.</p>
                                                                <p>Click "Add Availability" to set specific delivery days for this product.</p>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
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
        <!-- BEGIN PAGE LEVEL SCRIPTS -->
        <script src="assets/pages/scripts/table-datatables-responsive.min.js" type="text/javascript"></script>
        <!-- END PAGE LEVEL SCRIPTS -->
        <!-- BEGIN THEME LAYOUT SCRIPTS -->
        <script src="assets/layouts/layout/scripts/layout.min.js" type="text/javascript"></script>
        <script src="assets/layouts/layout/scripts/demo.min.js" type="text/javascript"></script>
        <script src="assets/layouts/global/scripts/quick-sidebar.min.js" type="text/javascript"></script>
        <!-- END THEME LAYOUT SCRIPTS -->

<!-- Add Availability Modal -->
<div class="modal fade" id="addAvailabilityModal" tabindex="-1" role="dialog" aria-labelledby="addAvailabilityModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="addAvailabilityModalLabel"><i class="fa fa-plus"></i> Add Delivery Availability</h4>
            </div>
            <form id="addAvailabilityForm">
                <div class="modal-body">
                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($get_product_id, ENT_QUOTES, 'UTF-8'); ?>">
                    <p>Set which days of the week this product can be delivered:</p>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="mon" value="1" checked> Monday
                                </label>
                            </div>
                            <div class="form-group">
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="tue" value="1" checked> Tuesday
                                </label>
                            </div>
                            <div class="form-group">
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="wed" value="1" checked> Wednesday
                                </label>
                            </div>
                            <div class="form-group">
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="thu" value="1" checked> Thursday
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="fri" value="1" checked> Friday
                                </label>
                            </div>
                            <div class="form-group">
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="sat" value="1" checked> Saturday
                                </label>
                            </div>
                            <div class="form-group">
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="sun" value="1" checked> Sunday
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Availability</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Availability Modal -->
<div class="modal fade" id="editAvailabilityModal" tabindex="-1" role="dialog" aria-labelledby="editAvailabilityModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="editAvailabilityModalLabel"><i class="fa fa-edit"></i> Edit Delivery Availability</h4>
            </div>
            <form id="editAvailabilityForm">
                <div class="modal-body">
                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($get_product_id, ENT_QUOTES, 'UTF-8'); ?>">
                    <p>Set which days of the week this product can be delivered:</p>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="mon" value="1" <?php echo ($product_availability && $product_availability['mon']) ? 'checked' : ''; ?>> Monday
                                </label>
                            </div>
                            <div class="form-group">
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="tue" value="1" <?php echo ($product_availability && $product_availability['tue']) ? 'checked' : ''; ?>> Tuesday
                                </label>
                            </div>
                            <div class="form-group">
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="wed" value="1" <?php echo ($product_availability && $product_availability['wed']) ? 'checked' : ''; ?>> Wednesday
                                </label>
                            </div>
                            <div class="form-group">
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="thu" value="1" <?php echo ($product_availability && $product_availability['thu']) ? 'checked' : ''; ?>> Thursday
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="fri" value="1" <?php echo ($product_availability && $product_availability['fri']) ? 'checked' : ''; ?>> Friday
                                </label>
                            </div>
                            <div class="form-group">
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="sat" value="1" <?php echo ($product_availability && $product_availability['sat']) ? 'checked' : ''; ?>> Saturday
                                </label>
                            </div>
                            <div class="form-group">
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="sun" value="1" <?php echo ($product_availability && $product_availability['sun']) ? 'checked' : ''; ?>> Sunday
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Update Availability</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Handle Add Availability Form
$('#addAvailabilityForm').on('submit', function(e) {
    e.preventDefault();
    
    var formData = new FormData(this);
    formData.append('action', 'save_availability');
    
    var $form = $(this);
    // Convert checkboxes to 0/1
    var days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
    days.forEach(function(day) {
        formData.set(day, $form.find('[name="' + day + '"]').is(':checked') ? '1' : '0');
    });
    
    $.ajax({
        url: 'product-details.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            try {
                var result = JSON.parse(response);
              
                if (result.success) {
                    $('#addAvailabilityModal').modal('hide');
                    location.reload(); // Reload to show updated availability
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (e) {
                alert('Error parsing response. Please check the console for details.');
            }
        },
        error: function() {
            alert('Error saving availability');
        }
    });
});

// Handle Edit Availability Form
$('#editAvailabilityForm').on('submit', function(e) {
    e.preventDefault();
    
    var formData = new FormData(this);
    formData.append('action', 'save_availability');
    
    var $form = $(this);
    // Convert checkboxes to 0/1
    var days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
    days.forEach(function(day) {
        formData.set(day, $form.find('[name="' + day + '"]').is(':checked') ? '1' : '0');
    });
    
    $.ajax({
        url: 'product-details.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
             $('#editAvailabilityModal').modal('hide');
                    location.reload(); // Reload to show updated availability
        },
        error: function() {
            alert('Error updating availability');
        }
    });
});

// Handle Low Stock Qty Save
$('#lowStockQtyForm').on('submit', function(e) {
    e.preventDefault();

    var formData = new FormData(this);
    formData.append('action', 'save_low_stock_qty');

    $.ajax({
        url: '',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert(response.message || 'Low stock qty updated');
            } else {
                alert(response.message || 'Failed to update low stock qty');
            }
        },
        error: function() {
            alert('Error updating low stock qty');
        }
    });
});
</script>

</body>

</html>



