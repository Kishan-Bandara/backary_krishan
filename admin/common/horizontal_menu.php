<?php
$canDashboard = function_exists('hasPermission') ? hasPermission('dashboard.view') : true;
$canPurchase = function_exists('hasAnyPermission') ? hasAnyPermission([
    'purchase.*',
    'purchase.supplier.create',
    'purchase.supplier.view',
    'purchase.purchase.create',
    'purchase.purchase.view',
    'purchase.purchase.add',
    'purchase.purchase.history',
    'purchase.return.create',
    'purchase.return.view',
    'purchase.return.*'
]) : true;
$canStockTransfer = function_exists('hasAnyPermission') ? hasAnyPermission([
    'stock.*',
    'stock.transfer.create',
    'stock.transfer.view',
    'stock.issue.create',
    'stock.issue.view'
]) : true;
$canOrders = function_exists('hasAnyPermission') ? hasAnyPermission([
    'orders.*',
    'orders.create',
    'orders.view'
]) : true;
$canProduct = function_exists('hasAnyPermission') ? hasAnyPermission([
    'product.*',
    'product.create',
    'product.view',
    'product.price_map',
    'product.standing_orders'
]) : true;
$canItemMaster = function_exists('hasAnyPermission') ? hasAnyPermission([
    'item_master.*',
    'item_master.group.create',
    'item_master.type.create',
    'item_master.category.create',
    'item_master.price_types'
]) : true;
$canWarehouse = function_exists('hasAnyPermission') ? hasAnyPermission([
    'warehouse.*',
    'warehouse.create',
    'warehouse.view'
]) : true;
$canCustomer = function_exists('hasAnyPermission') ? hasAnyPermission([
    'customer.*',
    'customer.create',
    'customer.view',
    'customer.price_map'
]) : true;
$canCRM = function_exists('hasAnyPermission') ? hasAnyPermission([
    'crm.*',
    'crm.view',
    'crm.person.create',
    'crm.person.view',
    'crm.company.create',
    'crm.company.view'
]) : true;
$canReports = function_exists('hasPermission') ? hasPermission('reports.view') : true;
$canUserAdmin = function_exists('hasAnyPermission') ? hasAnyPermission([
    'users.*',
    'users.create',
    'users.view',
    'users.edit',
    'users.delete'
]) : true;
$canSettings = function_exists('hasAnyPermission') ? hasAnyPermission([
    'settings.permissions',
    'users.*',
    'users.create',
    'users.view',
    'users.edit',
    'users.delete'
]) : true; 
?>

<div class="hor-menu">
    <ul class="nav navbar-nav">
        <!-- Dashboard -->
        <?php if ($canDashboard) { ?>
        <li class="classic-menu-dropdown">
            <a href="index.php"> Dashboard </a>
        </li>
        <?php } ?>

        <!-- Purchase -->
        <?php if ($canPurchase) { ?>
        <li class="menu-dropdown classic-menu-dropdown ">
            <a href="javascript:;" data-hover="megamenu-dropdown" data-close-others="true" data-toggle="dropdown"> Purchase <i class="fa fa-angle-down"></i> </a>
            <ul class="dropdown-menu pull-left">
                <?php if (function_exists('hasAnyPermission') && hasAnyPermission(['purchase.supplier.create', 'purchase.supplier.view', 'purchase.supplier.*'])) { ?>
                <li class="dropdown-submenu">
                    <a href="javascript:;"> <i class="fa fa-users"></i> Supplier </a>
                    <ul class="dropdown-menu">
                        <?php if (function_exists('hasPermission') ? hasPermission('purchase.supplier.create') : true) { ?><li><a href="add-supplier.php"><i class="fa fa-plus"></i> Add Supplier</a></li><?php } ?>
                        <?php if (function_exists('hasPermission') ? hasPermission('purchase.supplier.view') : true) { ?><li><a href="manage-supplier.php"><i class="fa fa-retweet"></i> Manage Supplier</a></li><?php } ?>
                    </ul>
                </li>
                <?php } ?>
                <?php if (function_exists('hasAnyPermission') && hasAnyPermission(['purchase.purchase.create', 'purchase.purchase.view', 'purchase.purchase.add', 'purchase.purchase.history', 'purchase.purchase.*'])) { ?>
                <li class="dropdown-submenu">
                    <a href="javascript:;"> <i class="icon-settings"></i> Purchase </a>
                    <ul class="dropdown-menu">
                        <?php if (function_exists('hasPermission') ? hasPermission('purchase.purchase.create') : true) { ?><li><a href="purchase-order-create.php"><i class="fa fa-file-text-o"></i> Create Purchase Note</a></li><?php } ?>
                        <?php if (function_exists('hasPermission') ? hasPermission('purchase.purchase.view') : true) { ?><li><a href="purchase-order-list.php"><i class="fa fa-list"></i> Purchase Notes</a></li><?php } ?>
                        <?php if (function_exists('hasPermission') ? hasPermission('purchase.purchase.history') : true) { ?><li><a href="purchase-history.php"><i class="icon-link"></i> Purchase History</a></li><?php } ?>

                        <li class="divider"></li>
                        <?php if (function_exists('hasPermission') ? hasPermission('purchase.return.create') : true) { ?><li><a href="purchase-return-create.php"><i class="fa fa-undo"></i> Create Purchase Return</a></li><?php } ?>
                        <?php if (function_exists('hasPermission') ? hasPermission('purchase.return.view') : true) { ?><li><a href="manage-purchase-returns.php"><i class="fa fa-list"></i> Purchase Returns</a></li><?php } ?>
                    </ul>
                </li>
                <?php } ?>
            </ul>
        </li>
        <?php } ?>

        <!-- Stock -->
        <?php if ($canStockTransfer) { ?>
        <li class="menu-dropdown classic-menu-dropdown ">
            <a href="javascript:;" data-hover="megamenu-dropdown" data-close-others="true" data-toggle="dropdown"> Stock <i class="fa fa-angle-down"></i> </a>
            <ul class="dropdown-menu pull-left">
                <?php if (function_exists('hasPermission') ? hasPermission('stock.transfer.create') : true) { ?><li><a href="stock-transfer-create.php"><i class="fa fa-exchange"></i> Create Stock Transfer</a></li><?php } ?>
                <?php if (function_exists('hasPermission') ? hasPermission('stock.transfer.view') : true) { ?><li><a href="stock-transfer-list.php"><i class="fa fa-random"></i> Stock Transfers</a></li><?php } ?>
                <?php if (function_exists('hasPermission') ? hasPermission('stock.transfer.view') : true) { ?><li><a href="stock-transfer-receive-list.php"><i class="fa fa-check"></i> Receive Confirmation</a></li><?php } ?>
                 <li class="divider"></li>
                 <?php if (function_exists('hasPermission') ? hasPermission('stock.issue.view') : true) { ?><li><a href="production-receive-list.php"><i class="fa fa-list"></i> Production Receive</a></li><?php } ?>

                <li class="divider"></li>
                <?php if (function_exists('hasPermission') ? hasPermission('stock.issue.create') : true) { ?><li><a href="stock-issue-create.php"><i class="fa fa-minus-circle"></i> Create Stock Issue</a></li><?php } ?>
                <?php if (function_exists('hasPermission') ? hasPermission('stock.issue.view') : true) { ?><li><a href="stock-issue-list.php"><i class="fa fa-list"></i> Stock Issue Notes</a></li><?php } ?>
         
         
            </ul>
        </li>
        <?php } ?>

        <!-- Sales -->
        <?php if ($canOrders) { ?>
        <li class="menu-dropdown classic-menu-dropdown ">
            <a href="javascript:;" data-hover="megamenu-dropdown" data-close-others="true" data-toggle="dropdown"> Sales <i class="fa fa-angle-down"></i> </a>
            <ul class="dropdown-menu pull-left">
                <?php if (function_exists('hasPermission') ? hasPermission('orders.create') : true) { ?><li><a href="cart-order.php"><i class="fa fa-shopping-cart"></i> Cart Order</a></li><?php } ?>
                <li class="divider"></li>
                <?php if (function_exists('hasPermission') ? hasPermission('orders.view') : true) { ?><li><a href="manage-orders.php"><i class="fa fa-list"></i> Manage Orders</a></li><?php } ?>
                <?php if (function_exists('hasPermission') ? hasPermission('orders.view') : true) { ?><li><a href="manage-invoices.php"><i class="fa fa-file-invoice"></i> Manage Invoices</a></li><?php } ?>
                <?php if (function_exists('hasPermission') ? hasPermission('orders.view') : true) { ?><li><a href="order-calendar.php"><i class="fa fa-calendar"></i> Order Calendar</a></li><?php } ?>
                <li class="divider"></li>
                <?php if (function_exists('hasPermission') ? hasPermission('product.standing_orders') : true) { ?><li><a href="standing-order.php"><i class="fa fa-calendar-check-o"></i> Standing Orders</a></li><?php } ?>
                <?php if (function_exists('hasPermission') ? hasPermission('product.standing_orders') : true) { ?><li><a href="standing-order-bulk-upload.php"><i class="fa fa-upload"></i> Standing Order Bulk Upload</a></li><?php } ?>
            </ul>
        </li>
        <?php } ?>

        <!-- Product -->
        <?php if ($canProduct) { ?>
        <li class="menu-dropdown classic-menu-dropdown ">
            <a href="javascript:;" data-hover="megamenu-dropdown" data-close-others="true" data-toggle="dropdown"> Product <i class="fa fa-angle-down"></i> </a>
            <ul class="dropdown-menu pull-left">
                <?php if (function_exists('hasPermission') ? hasPermission('product.create') : true) { ?><li><a href="add-product.php"><i class="fa fa-plus-circle"></i> Add Product</a></li><?php } ?>
                <?php if (function_exists('hasPermission') ? hasPermission('product.view') : true) { ?><li><a href="manage-product.php"><i class="fa fa-barcode"></i> List Product</a></li><?php } ?>
                <?php if (function_exists('hasPermission') ? hasPermission('product.price_map') : true) { ?><li><a href="product_price_mapping.php"><i class="fa fa-tags"></i> Price Type Mapping</a></li><?php } ?>
                <li class="divider"></li>
                <li><a href="product-ingredients.php"><i class="fa fa-leaf"></i> Product Ingredients</a></li>
                <li><a href="raw-materials-report.php"><i class="fa fa-calculator"></i> Raw Materials Report</a></li>
                <?php if (function_exists('hasAnyPermission') && hasAnyPermission(['item_master.*','item_master.group.create','item_master.type.create','item_master.category.create','item_master.price_types'])) { ?>
                <li class="dropdown-submenu">
                    <a href="javascript:;"> <i class="fa fa-database"></i> Item Category </a>
                    <ul class="dropdown-menu">
                        <?php if (function_exists('hasPermission') ? hasPermission('item_master.group.create') : true) { ?><li><a href="add-group.php"><i class="fa fa-chain"></i> Add Group</a></li><?php } ?>
                        <?php if (function_exists('hasPermission') ? hasPermission('item_master.type.create') : true) { ?><li><a href="add-type.php"><i class="fa fa-bars"></i> Add Type</a></li><?php } ?>
                        <?php if (function_exists('hasPermission') ? hasPermission('item_master.category.create') : true) { ?><li><a href="add-category.php"><i class="fa fa-folder-open"></i> Add Category</a></li><?php } ?>
                        <?php if (function_exists('hasPermission') ? hasPermission('item_master.price_types') : true) { ?><li><a href="price_types.php"><i class="fa fa-folder-open"></i> Add Price Types</a></li><?php } ?>
                    </ul>
                </li>
                <?php } ?>
                <!-- Invoices was hidden in sidebar, keeping it hidden or omitted -->
            </ul>
        </li>
        <?php } ?> 

        <!-- Item Master moved to Product as Item Category -->

        <!-- Warehouse Management -->
        <?php if ($canWarehouse) { ?>
        <li class="menu-dropdown classic-menu-dropdown ">
            <a href="javascript:;" data-hover="megamenu-dropdown" data-close-others="true" data-toggle="dropdown"> Warehouse <i class="fa fa-angle-down"></i> </a>
            <ul class="dropdown-menu pull-left">
                <?php if (function_exists('hasPermission') ? hasPermission('warehouse.create') : true) { ?><li><a href="add-location.php"><i class="fa fa-plus"></i> Add Warehouse</a></li><?php } ?>
                <?php if (function_exists('hasPermission') ? hasPermission('warehouse.view') : true) { ?><li><a href="manage-locations.php"><i class="fa fa-building"></i> Manage Warehouses</a></li><?php } ?>
                <li class="divider"></li>
                <li><a href="add-delivery-route.php"><i class="fa fa-plus-circle"></i> Add Delivery Route</a></li>
                <li><a href="manage-delivery-routes.php"><i class="fa fa-road"></i> Manage Delivery Routes</a></li>
                <li><a href="manage-delivery-route-groups.php"><i class="fa fa-tags"></i> Delivery Route Groups</a></li>
                <li><a href="manage-delivery-rules.php"><i class="fa fa-truck"></i> Delivery Rules</a></li>
            </ul>
        </li>
        <?php } ?>

        <!-- Customer -->
        <?php if ($canCustomer) { ?>
        <li class="menu-dropdown classic-menu-dropdown ">
            <a href="javascript:;" data-hover="megamenu-dropdown" data-close-others="true" data-toggle="dropdown"> Customer <i class="fa fa-angle-down"></i> </a>
            <ul class="dropdown-menu pull-left">
                <?php if (function_exists('hasPermission') ? hasPermission('customer.create') : true) { ?><li><a href="add-customer.php"><i class="fa fa-user-plus"></i> Add Customer</a></li><?php } ?>
                <?php if (function_exists('hasPermission') ? hasPermission('customer.view') : true) { ?><li><a href="manage-customer.php"><i class="fa fa-users"></i> List Customer</a></li><?php } ?>
                <?php if (function_exists('hasPermission') ? hasPermission('customer.price_map') : true) { ?><li><a href="price_type_customer_mapping.php"><i class="fa fa-users"></i> Price Type Mapping</a></li><?php } ?>
            </ul>
        </li>
        <?php } ?>

        <!-- CRM -->
        <?php if ($canCRM) { ?>
        <li class="menu-dropdown classic-menu-dropdown ">
            <a href="javascript:;" data-hover="megamenu-dropdown" data-close-others="true" data-toggle="dropdown"> CRM <i class="fa fa-angle-down"></i> </a>
            <ul class="dropdown-menu pull-left">
                <?php if (function_exists('hasPermission') ? hasPermission('crm.view') : true) { ?><li><a href="crm-dashboard.php"><i class="fa fa-line-chart"></i> CRM Dashboard</a></li><?php } ?>
                <li class="divider"></li>
                <?php if (function_exists('hasAnyPermission') ? hasAnyPermission(['crm.person.create', 'crm.person.view', 'crm.company.create', 'crm.company.view']) : true) { ?><li><a href="crm.php?type=person"><i class="fa fa-address-book"></i> Contact Entry</a></li><?php } ?>
                <?php if (function_exists('hasPermission') ? hasPermission('crm.view') : true) { ?><li><a href="crm-opportunity.php"><i class="fa fa-briefcase"></i> Opportunity Entry</a></li><?php } ?>
                <?php if (function_exists('hasPermission') ? hasPermission('crm.view') : true) { ?><li><a href="crm-masters.php"><i class="fa fa-cogs"></i> CRM Masters</a></li><?php } ?>
                <?php if (function_exists('hasPermission') ? hasPermission('crm.view') : true) { ?><li><a href="crm-masters.php?tab=sales_cycle"><i class="fa fa-random"></i> Sales Cycles</a></li><?php } ?>
                <?php if (function_exists('hasPermission') ? hasPermission('crm.view') : true) { ?><li><a href="crm-masters.php?tab=activity"><i class="fa fa-flag"></i> Activities</a></li><?php } ?>
                <?php if (function_exists('hasAnyPermission') ? hasAnyPermission(['crm.person.view', 'crm.company.view']) : true) { ?><li><a href="crm-list.php"><i class="fa fa-list"></i> Contact List</a></li><?php } ?>
            </ul>
        </li>
        <?php } ?>

        <!-- Complaints -->
        <li class="menu-dropdown classic-menu-dropdown ">
            <a href="javascript:;" data-hover="megamenu-dropdown" data-close-others="true" data-toggle="dropdown"> Complaints <i class="fa fa-angle-down"></i> </a>
            <ul class="dropdown-menu pull-left">
                <li><a href="manage-complaints.php"><i class="fa fa-list"></i> All Complaints</a></li>
                <li><a href="manage-complaints.php?status=Open"><i class="fa fa-exclamation-circle"></i> Open Complaints</a></li>
                <li><a href="manage-complaints.php?status=Assigned"><i class="fa fa-user"></i> Assigned Complaints</a></li>
                <li class="divider"></li>
                <li><a href="complaint-masters.php"><i class="fa fa-cogs"></i> Complaint Masters</a></li>
            </ul>
        </li>

        <!-- Reports -->
        <?php if ($canReports) { ?>
        <li class="menu-dropdown classic-menu-dropdown ">
            <a href="javascript:;" data-hover="megamenu-dropdown" data-close-others="true" data-toggle="dropdown"> Reports <i class="fa fa-angle-down"></i> </a>
            <ul class="dropdown-menu pull-left">
                <li class="dropdown-submenu">
                    <a href="javascript:;"><i class="fa fa-shopping-basket"></i> Orders </a>
                    <ul class="dropdown-menu">
                        <li><a href="pending-orders.php"><i class="fa fa-clock-o"></i> Pending Orders</a></li>
                        <li><a href="total-standing-orders.php"><i class="fa fa-calendar"></i> Total Standing Orders</a></li>
                        <li><a href="standing-order-by-customer.php"><i class="fa fa-user"></i> Standing Order by Customer</a></li>
                        <li><a href="cart-order-by-customer.php"><i class="fa fa-shopping-cart"></i> Cart Order by Customer</a></li>
                        <li><a href="manage-orders.php"><i class="fa fa-file-text-o"></i> Total Orders</a></li>
                        <li><a href="orders-per-item.php"><i class="fa fa-cube"></i> Orders Per Item</a></li>
                    </ul>
                </li>
                <li class="dropdown-submenu">
                    <a href="javascript:;"><i class="fa fa-truck"></i> Delivery </a>
                    <ul class="dropdown-menu">
                        <li><a href="delivery-report.php"><i class="fa fa-truck"></i> Delivery Report</a></li>
                        <li><a href="packing-slip.php"><i class="fa fa-file-text-o"></i> Packing Slip</a></li>
                        <li><a href="pick-list.php"><i class="fa fa-list"></i> Packing List</a></li>
                        <li><a href="pick-pack.php"><i class="fa fa-list-alt"></i> Pick &amp; Pack</a></li>
                        <li><a href="pick-pack-matrix.php"><i class="fa fa-th-large"></i> Pick &amp; Pack Matrix</a></li>
                        <li><a href="driver-report.php"><i class="fa fa-id-card-o"></i> Driver Report</a></li>
                        <li><a href="cut-shape-report.php"><i class="fa fa-pie-chart"></i> Cut &amp; Shape Report</a></li>
                        <li><a href="production-report.php"><i class="fa fa-industry"></i> Production Report</a></li>
                    </ul>
                </li>
                <li class="dropdown-submenu">
                    <a href="javascript:;"><i class="fa fa-cubes"></i> Stock </a>
                    <ul class="dropdown-menu">
                        <li><a href="stock-report.php"><i class="fa fa-cubes"></i> Stock Report</a></li>
                        <li><a href="batch-tracking-report.php"><i class="fa fa-barcode"></i> Batch Tracking Report</a></li>
                    </ul>
                </li>
                <li class="dropdown-submenu">
                    <a href="javascript:;"><i class="fa fa-shopping-cart"></i> Procurement </a>
                    <ul class="dropdown-menu">
                        <li><a href="purchase-order-report.php"><i class="fa fa-shopping-cart"></i> Purchase Order Report</a></li>
                    </ul>
                </li>
                <li class="dropdown-submenu">
                    <a href="javascript:;"><i class="fa fa-money"></i> Finance </a>
                    <ul class="dropdown-menu">
                        <li><a href="xero-invoice-export.php"><i class="fa fa-file-excel-o"></i> Xero Invoice Export</a></li>
                    </ul>
                </li>
            </ul>
        </li>
        <?php } ?>

        <!-- Settings -->
        <?php if ($canSettings) { ?>
        <li class="menu-dropdown classic-menu-dropdown ">
            <a href="javascript:;" data-hover="megamenu-dropdown" data-close-others="true" data-toggle="dropdown"> Settings <i class="fa fa-angle-down"></i> </a>
            <ul class="dropdown-menu pull-left">
                <?php if (function_exists('hasPermission') ? hasPermission('settings.permissions') : true) { ?><li><a href="manage-settings.php"><i class="fa fa-cogs"></i> Front Web Settings</a></li><?php } ?>
                <?php if (function_exists('hasPermission') ? hasPermission('settings.permissions') : true) { ?><li><a href="payment_terms.php"><i class="fa fa-calendar-check-o"></i> Payment Terms</a></li><?php } ?>
                <?php if (function_exists('hasPermission') ? hasPermission('settings.permissions') : true) { ?><li><a href="invoice-settings.php"><i class="fa fa-file-text-o"></i> Invoice/Receipt Settings</a></li><?php } ?>
                <?php if (function_exists('hasPermission') ? hasPermission('settings.permissions') : true) { ?><li><a href="smtp-settings.php"><i class="fa fa-envelope"></i> SMTP Email Settings</a></li><?php } ?>
                <?php if (function_exists('hasPermission') ? hasPermission('settings.permissions') : true) { ?><li><a href="gst-maintenance.php"><i class="fa fa-money"></i> GST Maintenance</a></li><?php } ?>
                <?php if (function_exists('hasPermission') ? hasPermission('settings.permissions') : true) { ?><li><a href="uom-maintenance.php"><i class="fa fa-balance-scale"></i> Unit Of Measure</a></li><?php } ?>
                <?php if (function_exists('hasPermission') ? hasPermission('settings.permissions') : true) { ?><li><a href="batch-expire-update.php"><i class="fa fa-calendar-times-o"></i> Batch Expire Update</a></li><?php } ?>
                <?php if (function_exists('hasPermission') ? hasPermission('settings.permissions') : true) { ?><li><a href="item-master-bulk-upload.php"><i class="fa fa-upload"></i> Item Master Bulk Upload</a></li><?php } ?>
                <?php if (function_exists('hasPermission') ? hasPermission('settings.permissions') : true) { ?><li><a href="discount-code.php"><i class="fa fa-tag"></i> Discount Code</a></li><?php } ?>
                <?php if ($canUserAdmin && (function_exists('hasPermission') ? hasPermission('settings.permissions') : true)) { ?><li class="divider"></li><?php } ?>
                <?php if (function_exists('hasPermission') ? hasPermission('users.create') : true) { ?><li><a href="add-user.php"><i class="fa fa-user-plus"></i> Add Backend User</a></li><?php } ?>
                <?php if (function_exists('hasPermission') ? hasPermission('users.view') : true) { ?><li><a href="manage-user.php"><i class="fa fa-users"></i> Manage Backend Users</a></li><?php } ?>
                <?php if ((function_exists('hasPermission') ? hasPermission('settings.permissions') : true) && $canUserAdmin) { ?><li class="divider"></li><?php } ?>
                <?php if (function_exists('hasPermission') ? hasPermission('settings.permissions') : true) { ?><li><a href="manage-permissions.php"><i class="fa fa-lock"></i> Permissions</a></li><?php } ?>
            </ul>
        </li>
        <?php } ?>

    </ul>
</div>



