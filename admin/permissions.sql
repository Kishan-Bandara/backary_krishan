-- RBAC permissions tables and seed data

CREATE TABLE IF NOT EXISTS `permissions` (
  `permission_id` int(11) NOT NULL AUTO_INCREMENT,
  `permission_key` varchar(100) NOT NULL,
  `permission_name` varchar(150) NOT NULL,
  `description` text NULL,
  PRIMARY KEY (`permission_id`),
  UNIQUE KEY `permission_key` (`permission_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `role_permissions` (
  `user_level_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  PRIMARY KEY (`user_level_id`, `permission_id`),
  KEY `permission_id` (`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `permissions` (`permission_key`, `permission_name`, `description`) VALUES
('dashboard.view', 'View Dashboard', 'Access dashboard'),
('purchase.supplier.create', 'Create Supplier', 'Add suppliers'),
('purchase.supplier.view', 'View Supplier', 'Manage suppliers'),
('purchase.purchase.create', 'Create Purchase Note', 'Create purchase notes'),
('purchase.purchase.view', 'View Purchase Notes', 'List purchase notes'),
('purchase.purchase.add', 'Add Purchase', 'Add purchase entries'),
('purchase.purchase.history', 'Purchase History', 'View purchase history'),
('purchase.return.create', 'Create Purchase Return', 'Create purchase return notes'),
('purchase.return.view', 'View Purchase Returns', 'View purchase return notes'),
('stock.transfer.create', 'Create Stock Transfer', 'Create stock transfers'),
('stock.transfer.view', 'View Stock Transfers', 'List stock transfers'),
('stock.issue.create', 'Create Stock Issue', 'Create stock issues'),
('stock.issue.view', 'View Stock Issues', 'List stock issues'),
('orders.create', 'Create Sales', 'Add new sales'),
('orders.view', 'View Orders', 'Manage orders'),
('product.create', 'Create Product', 'Add products'),
('product.view', 'View Products', 'List products'),
('product.price_map', 'Product Price Mapping', 'Price type mapping'),
('product.standing_orders', 'Standing Orders', 'Standing order management'),
('item_master.group.create', 'Create Group', 'Add product groups'),
('item_master.type.create', 'Create Type', 'Add product types'),
('item_master.category.create', 'Create Category', 'Add categories'),
('item_master.price_types', 'Price Types', 'Manage price types'),
('warehouse.create', 'Create Warehouse', 'Add warehouses'),
('warehouse.view', 'View Warehouses', 'Manage warehouses'),
('customer.create', 'Create Customer', 'Add customers'),
('customer.view', 'View Customers', 'Manage customers'),
('customer.price_map', 'Customer Price Mapping', 'Price type mapping'),
('users.create', 'Create Backend Users', 'Add new backend login users'),
('users.view', 'View Backend Users', 'List backend login users'),
('users.edit', 'Edit Backend Users', 'Update backend login users'),
('users.delete', 'Delete Backend Users', 'Remove backend login users'),
('settings.permissions', 'Manage Role Permissions', 'Assign permissions to roles');

-- Example role permissions for user_level_id = 2 (adjust as needed)
-- INSERT INTO `role_permissions` (`user_level_id`, `permission_id`)
-- SELECT 2, permission_id FROM permissions WHERE permission_key IN (
--   'dashboard.view',
--   'purchase.supplier.view',
--   'purchase.purchase.view',
--   'orders.view',
--   'product.view',
--   'customer.view',
--   'warehouse.view'
-- );
