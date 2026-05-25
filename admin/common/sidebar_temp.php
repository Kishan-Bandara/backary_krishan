
            
                <!-- DOC: Set data-auto-scroll="false" to disable the sidebar from auto scrolling/focusing -->
                <!-- DOC: Change data-auto-speed="200" to adjust the sub menu slide up/down speed -->
                <div class="page-sidebar navbar-collapse collapse">
                    <!-- BEGIN SIDEBAR MENU -->
                    <!-- DOC: Apply "page-sidebar-menu-light" class right after "page-sidebar-menu" to enable light sidebar menu style(without borders) -->
                    <!-- DOC: Apply "page-sidebar-menu-hover-submenu" class right after "page-sidebar-menu" to enable hoverable(hover vs accordion) sub menu mode -->
                    <!-- DOC: Apply "page-sidebar-menu-closed" class right after "page-sidebar-menu" to collapse("page-sidebar-closed" class must be applied to the body element) the sidebar sub menu mode -->
                    <!-- DOC: Set data-auto-scroll="false" to disable the sidebar from auto scrolling/focusing -->
                    <!-- DOC: Set data-keep-expand="true" to keep the submenues expanded -->
                    <!-- DOC: Set data-auto-speed="200" to adjust the sub menu slide up/down speed -->
                    <ul class="page-sidebar-menu  page-sidebar-menu-closed" data-keep-expanded="false" data-auto-scroll="true" data-slide-speed="200" style="padding-top: 20px">
                        <!-- DOC: To remove the sidebar toggler from the sidebar you just need to completely remove the below "sidebar-toggler-wrapper" LI element -->
                        <li class="sidebar-toggler-wrapper hide">
                            <!-- BEGIN SIDEBAR TOGGLER BUTTON -->
                            <div class="sidebar-toggler"> </div>
                            <!-- END SIDEBAR TOGGLER BUTTON -->
                        </li>
                        <!-- DOC: To remove the search box from the sidebar you just need to completely remove the below "sidebar-search-wrapper" LI element -->
                        <li class="sidebar-search-wrapper">
                            <!-- BEGIN RESPONSIVE QUICK SEARCH FORM -->
                            <!-- DOC: Apply "sidebar-search-bordered" class the below search form to have bordered search box -->
                            <!-- DOC: Apply "sidebar-search-bordered sidebar-search-solid" class the below search form to have bordered & solid search box -->
                           
                            <!-- END RESPONSIVE QUICK SEARCH FORM -->
                        </li>
                        <li class="nav-item start active open">
                            <a href="index.php" class="nav-link nav-toggle">
                                <i class="icon-home"></i>
                                <span class="title">Dashboard</span>
                                <span class="selected"></span>
                               
                            </a>
                           
                        </li>
                       
                      <li class="nav-item">
                            <a href="manage-purchase.php" class="nav-link nav-toggle">
                                <i class="fa fa-truck"></i>
                                <span class="title">Purchase</span>
                                <span class="arrow "></span>
                            </a>
                            <ul class="sub-menu">
                                <li class="nav-item">
                                    <a href="javascript:;" class="nav-link nav-toggle">
                                        <i class="fa fa-users"></i> Supplier
                                        <span class="arrow"></span>
                                    </a>
                                    <ul class="sub-menu">
                                       
                                        <li class="nav-item">
                                            <a href="add-supplier.php" class="nav-link">
                                                <i class="fa fa-plus"></i> Add Supplier</a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="manage-supplier.php" class="nav-link">
                                                <i class="fa fa-retweet"></i> Manage Supplier</a>
                                        </li>
                                    
                                    </ul>

                                </li>


                                 <li class="nav-item">
                                    <a href="javascript:;" class="nav-link nav-toggle">
                                        <i class="icon-settings"></i> Purchase
                                        <span class="arrow"></span>
                                    </a>
                                    <ul class="sub-menu">
                                       
                                        <li class="nav-item">

                                                <i class="icon-link"></i> Purchase History</a>
                                        </li>
                                    
                                    </ul>
                                    
                                </li>
                               
                               
                            </ul>

                        </li>
                        <!-- Inventory (stock) -->
                        <li class="nav-item">
                            <a href="javascript:;" class="nav-link nav-toggle">
                                <i class="fa fa-archive"></i>
                                <span class="title">Inventory</span>
                                <span class="arrow"></span>
                            </a>
                            <ul class="sub-menu">
                                <li class="nav-item">
                                    <a href="stock-issue-create.php" class="nav-link">
                                        <i class="fa fa-plus-circle"></i>
                                        <span class="title">Create Stock Issue</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="stock-issue-list.php" class="nav-link">
                                        <i class="fa fa-list"></i>
                                        <span class="title">Stock Issue Notes</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="production-receive-list.php" class="nav-link">
                                        <i class="fa fa-check-circle"></i>
                                        <span class="title">Production Receive</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="stock-transfer-list.php" class="nav-link">
                                        <i class="fa fa-exchange"></i>
                                        <span class="title">Stock Transfers</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="stock-transfer-receive-list.php" class="nav-link">
                                        <i class="fa fa-truck"></i>
                                        <span class="title">Transfer Receive</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                           <li class="nav-item  ">
                            <a href="javascript:;" class="nav-link nav-toggle">
                                <i class="fa fa-shopping-cart"></i>
                                <span class="title">Sales</span>
                                <span class="arrow"></span>
                            </a>
                            <ul class="sub-menu">
                                 <li class="nav-item  ">
                                    <a href="POS.php" class="nav-link ">
                                        <i class="fa fa-th-list"></i>
                                        <span class="title">New Order</span>
                                        
                                    </a>
                                </li>

                                <li class="nav-item  ">
                                    <a href="manage-orders.php" class="nav-link ">
                                        <i class="fa fa-th-list"></i>
                                        <span class="title">Manage Order</span>
                                        
                                    </a>
                                </li>
                                    <li class="nav-item  ">
                                    <a href="manage-invoices.php" class="nav-link ">
                                        <i class="fa fa fa-list-alt"></i>
                                        <span class="title">Manage Invoice</span>
                                        
                                    </a>
                                </li>
                              
                               
                              
                               
                            </ul>
                        </li>
                        <li class="nav-item  ">
                            <a href="javascript:;" class="nav-link nav-toggle">
                                <i class="fa fa-barcode"></i>
                                <span class="title">Product</span>
                                <span class="arrow"></span>
                            </a>
                            <ul class="sub-menu">

                                <li class="nav-item  ">
                                    <a href="add-product.php" class="nav-link ">
                                        <i class="fa fa-plus-circle"></i>
                                        <span class="title">Add Product</span>
                                    </a>
                                </li>
                                <li class="nav-item  ">
                                    <a href="manage-product.php" class="nav-link ">
                                        <i class="fa fa-barcode"></i>
                                        <span class="title">List Product</span>
                                        
                                    </a>
                                </li>
                                    <li class="nav-item  ">
                                    <a href="product-barcode-print.php" class="nav-link ">
                                        <i class="fa fa fa-tags"></i>
                                        <span class="title">Print Barcode/Label</span>
                                        
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="javascript:;" class="nav-link nav-toggle">
                                        <i class="fa fa-database"></i>
                                        <span class="title">Item Category</span>
                                        <span class="arrow"></span>
                                    </a>
                                    <ul class="sub-menu">
                                        <li class="nav-item">
                                            <a href="add-group.php" class="nav-link">
                                                <i class="fa fa-chain"></i>
                                                <span class="title">Add Group</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="add-type.php" class="nav-link">
                                                <i class="fa fa fa-bars"></i>
                                                <span class="title">Add Type</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="add-category.php" class="nav-link">
                                                <i class="fa fa-folder-open"></i>
                                                <span class="title">Add Category</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="price_types.php" class="nav-link">
                                                <i class="fa fa-folder-open"></i>
                                                <span class="title">Add Price Types</span>
                                            </a>
                                        </li>
                                    </ul>
                                </li> 
                                </li>
                                <li class="nav-item  "> 
                                    <a href="return_sales.php" class="nav-link ">
                                        <i class="fa fa-th-large"></i>
                                        <span class="title">Sales Return</span>
                                        
                                    </a>

                                </li>
                                    
                               
                              
                               
                            </ul>
                        </li>
<!-- Item Master moved to Product as Item Category -->
                                   
                            </ul>
                        </li>
                        <li class="nav-item  ">
                            <a href="javascript:;" class="nav-link nav-toggle">
                                <i class="fa fa-heart-o"></i>
                                <span class="title">Promotions</span>
                                <span class="arrow"></span>
                            </a>
                            <ul class="sub-menu">
                                <li class="nav-item  ">
                                    <a href="add-promotion.php" class="nav-link ">
                                        <i class="fa fa-plus-circle"></i>
                                        <span class="title">Add Promotion</span>
                                    </a>
                                </li>
                                <li class="nav-item  ">
                                    <a href="manage-promotions.php" class="nav-link ">
                                        <i class="fa fa-heart"></i>
                                        <span class="title">Manage Promotions</span>
                                    </a>
                                </li>

                             
                            </ul>
                        </li>

                        <li class="nav-item  ">
                            <a href="javascript:;" class="nav-link nav-toggle">
                                <i class="fa fa-heart-o"></i>
                                <span class="title">Coupon Codes</span>
                                <span class="arrow"></span>
                            </a>
                            <ul class="sub-menu">
                                <li class="nav-item  ">
                                    <a href="add-coupons.php" class="nav-link ">
                                        <i class="fa fa-plus-circle"></i>
                                        <span class="title">Add Coupon</span>
                                    </a>
                                </li>
                                <li class="nav-item  ">
                                    <a href="manage-coupons.php" class="nav-link ">
                                        <i class="fa fa-heart"></i>
                                        <span class="title">Manage Coupons</span>
                                    </a>
                                </li>

                             
                            </ul>
                        </li>
                        <li class="nav-item  ">
                            <a href="javascript:;" class="nav-link nav-toggle">
                                <i class="fa fa-users"></i>
                                <span class="title">Customer</span>
                                <span class="arrow"></span>
                            </a>
                            <ul class="sub-menu">
                                <li class="nav-item  ">
                                    <a href="add-customer.php" class="nav-link ">
                                        <i class="fa fa-user-plus"></i>
                                        <span class="title">Add Customer</span>
                                    </a>
                                </li>
                                <li class="nav-item  ">
                                    <a href="manage-customer.php" class="nav-link ">
                                        <i class="fa fa-users"></i>
                                        <span class="title">List Customer</span>
                                    </a>
                                </li>
                             
                             
                            </ul>
                        </li>

                        <li class="nav-item">
                            <a href="javascript:;" class="nav-link nav-toggle">
                                <i class="fa fa-address-book"></i>
                                <span class="title">CRM</span>
                                <span class="arrow"></span>
                            </a>
                            <ul class="sub-menu">
                                <li class="nav-item">
                                    <a href="crm-dashboard.php" class="nav-link ">
                                        <i class="fa fa-line-chart"></i>
                                        <span class="title">CRM Dashboard</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="crm.php?type=person" class="nav-link ">
                                        <i class="fa fa-address-book"></i>
                                        <span class="title">Contact Entry</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="crm-opportunity.php" class="nav-link ">
                                        <i class="fa fa-briefcase"></i>
                                        <span class="title">Opportunity Entry</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="crm-masters.php" class="nav-link ">
                                        <i class="fa fa-cogs"></i>
                                        <span class="title">CRM Masters</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="crm-list.php" class="nav-link ">
                                        <i class="fa fa-list"></i>
                                        <span class="title">Contact List</span>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Production & Stock Management -->
                        <li class="nav-item">
                            <a href="javascript:;" class="nav-link nav-toggle">
                                <i class="fa fa-industry"></i>
                                <span class="title">Production</span>
                                <span class="arrow"></span>
                            </a>
                            <ul class="sub-menu">
                                <li class="nav-item">
                                    <a href="product-ingredients.php" class="nav-link">
                                        <i class="fa fa-puzzle-piece"></i>
                                        <span class="title">Product Recipes</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="raw-materials-report.php" class="nav-link">
                                        <i class="fa fa-calculator"></i>
                                        <span class="title">Raw Materials Report</span>
                                    </a>
                                </li>
                                        <i class="fa fa-puzzle-piece"></i>
                                        <span class="title">Product Recipes</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="raw-materials-report.php" class="nav-link">
                                        <i class="fa fa-calculator"></i>
                                        <span class="title">Raw Materials Report</span>
                                    </a>
                                </li>
                            </ul>
                        </li>

                         <li class="nav-item  ">
                            <a href="javascript:;" class="nav-link nav-toggle">
                                <i class="fa fa-cog"></i>
                                <span class="title">Settings</span>
                                <span class="arrow"></span>
                            </a>
                            <ul class="sub-menu">
                                <li class="nav-item  ">
                                    <a href="" class="nav-link ">
                                        <i class="fa fa fa-cog"></i>
                                        <span class="title">System Settings</span>
                                    </a>
                                </li>
                                <li class="nav-item  ">
                                    <a href="" class="nav-link ">
                                        <i class="fa fa fa-th-large"></i>
                                        <span class="title">Home Slider</span>
                                    </a>
                                </li>
                                <li class="nav-item  ">
                                    <a href="" class="nav-link ">
                                        <i class="fa fa fa-th-large"></i>
                                        <span class="title">Change Logo</span>
                                    </a>
                                </li>
                                  <li class="nav-item  ">
                                    <a href="" class="nav-link ">
                                        <i class="fa fa-money"></i>
                                        <span class="title">Currencies</span>
                                    </a>
                                </li>
                                 <li class="nav-item  ">
                                    <a href="" class="nav-link ">
                                        <i class="fa fa-plus-circle"></i>
                                        <span class="title">+VAT Rules</span>
                                    </a>
                                </li>
                                 <li class="nav-item  ">
                                    <a href="add-location.php" class="nav-link ">
                                        <i class="fa fa-building-o"></i>
                                        <span class="title">Locations</span>
                                    </a>
                                </li>
                                <li class="nav-item  ">
                                    <a href="batch-update.php" class="nav-link ">
                                        <i class="fa fa-tags"></i>
                                        <span class="title">Batch Update</span>
                                    </a>
                                </li>
                                
                             
                             
                            </ul>
                        </li>
                        
                        <li class="nav-item  ">
                            <a href="" class="nav-link nav-toggle">
                                <i class="fa fa-bar-chart-o"></i>
                                <span class="title">Report</span>
                                <span class="arrow"></span>
                            </a>
                            <ul class="sub-menu">
                                <li class="nav-item  ">
                                    <a href="sales_report.php" class="nav-link ">
                                        <i class="fa fa-heart"></i>
                                        <span class="title"> Sales Report</span>
                                    </a>
                                </li>
                               
                                 <li class="nav-item  ">
                                    <a href="" class="nav-link ">
                                        <i class="fa fa-building"></i>
                                        <span class="title"> Location Stock Report</span>
                                    </a>
                                </li>
                                 <li class="nav-item  ">
                                    <a href="" class="nav-link ">
                                        <i class="fa fa-barcode"></i>
                                        <span class="title"> Product Report</span>
                                    </a>
                                </li>
                              
                                 <li class="nav-item  ">
                                    <a href="" class="nav-link ">
                                        <i class="fa fa-calendar"></i>
                                        <span class="title">Purchase Report</span>
                                    </a>
                                </li>
                                <li class="nav-item  ">
                                    <a href="stock-report.php" class="nav-link ">
                                        <i class="fa fa-cubes"></i>
                                        <span class="title">Stock Report</span>
                                    </a>
                                </li>
                                <li class="nav-item  ">
                                    <a href="purchase-order-report.php" class="nav-link ">
                                        <i class="fa fa-shopping-cart"></i>
                                        <span class="title">Purchase Order Report</span>
                                    </a>
                                </li>
                                <li class="nav-item  ">
                                    <a href="cut-shape-report.php" class="nav-link ">
                                        <i class="fa fa-pie-chart"></i>
                                        <span class="title">Cut &amp; Shape Report</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    
                            </ul>
                        </li>
                    </ul>
                    <!-- END SIDEBAR MENU -->
                    <!-- END SIDEBAR MENU -->
                </div>
               
            



