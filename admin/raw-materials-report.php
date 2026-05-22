<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');

$db = new Database();

// Get finished products (that have ingredients)
$productsWithRecipe = $db->getRows(
    'SELECT DISTINCT im.item_id, im.item_name, im.item_code,
            (SELECT COUNT(*) FROM product_ingredients pi WHERE pi.product_id = im.item_id) as ingredient_count
     FROM item_master im 
     WHERE im.is_raw_material = 0 
     HAVING ingredient_count > 0
     ORDER BY im.item_name ASC'
);

// Process form submission
$reportData = [];
$productionList = [];
$grandTotalByIngredient = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_report'])) {
    $productIds = isset($_POST['product_ids']) ? $_POST['product_ids'] : [];
    $quantities = isset($_POST['quantities']) ? $_POST['quantities'] : [];
    
    foreach ($productIds as $index => $productId) {
        $qty = isset($quantities[$index]) ? (float)$quantities[$index] : 0;
        if ($productId > 0 && $qty > 0) {
            $productionList[] = [
                'product_id' => (int)$productId,
                'quantity' => $qty
            ];
        }
    }
    
    // Calculate raw materials needed
    foreach ($productionList as &$item) {
        $product = $db->getRow('SELECT item_id, item_name, item_code FROM item_master WHERE item_id = ?', [$item['product_id']]);
        $ingredients = $db->getRows(
            'SELECT pi.*, im.item_name, im.item_code 
             FROM product_ingredients pi 
             LEFT JOIN item_master im ON im.item_id = pi.ingredient_id 
             WHERE pi.product_id = ? 
             ORDER BY pi.process_step ASC',
            [$item['product_id']]
        );
        
        $item['product_name'] = $product['item_name'];
        $item['product_code'] = $product['item_code'];
        $item['ingredients'] = [];
        $item['total_raw'] = 0;
        
        foreach ($ingredients as $ing) {
            $required = $ing['quantity'] * $item['quantity'];
            $item['ingredients'][] = [
                'ingredient_id' => $ing['ingredient_id'],
                'item_name' => $ing['item_name'],
                'item_code' => $ing['item_code'],
                'per_unit' => $ing['quantity'],
                'required' => $required,
                'process_step' => $ing['process_step']
            ];
            $item['total_raw'] += $required;
            
            // Aggregate by ingredient
            if (!isset($grandTotalByIngredient[$ing['ingredient_id']])) {
                $grandTotalByIngredient[$ing['ingredient_id']] = [
                    'ingredient_id' => $ing['ingredient_id'],
                    'item_name' => $ing['item_name'],
                    'item_code' => $ing['item_code'],
                    'total_required' => 0
                ];
            }
            $grandTotalByIngredient[$ing['ingredient_id']]['total_required'] += $required;
        }
    }
    unset($item);
    
    $reportData = $productionList;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Raw Materials Report | Production Planning</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
    <link href="assets/global/plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/global/plugins/select2/css/select2-bootstrap.min.css" rel="stylesheet" type="text/css" />
    <style>
        .report-header {
            background: linear-gradient(90deg, #028d7aff 0%, #066c74ff 100%);
            color: #fff;
            padding: 15px 20px;
            border-radius: 8px 8px 0 0;
            margin-bottom: 0;
        }
        .report-header h4 {
            margin: 0;
            font-weight: 600;
        }
        .report-card {
            background: #fff;
            border: 1px solid #dde3ec;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        .report-card .card-body {
            padding: 20px;
        }
        .production-row {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            border: 1px solid #e9ecef;
        }
        .btn-add-row {
            background: #28a745;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-remove-row {
            background: #dc3545;
            color: #fff;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-table th {
            background: #f4f6fb;
            padding: 12px;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.05em;
            color: #5d6d8a;
            border-bottom: 2px solid #e1e7f0;
        }
        .summary-table td {
            padding: 12px;
            border-bottom: 1px solid #eef2f7;
        }
        .summary-table tr:hover {
            background: #f9fbfd;
        }
        .grand-total-row {
            background: #e8f5e9 !important;
            font-weight: bold;
        }
        .grand-total-row td {
            color: #2e7d32;
            border-top: 2px solid #4caf50;
        }
        .product-section {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 0 8px 8px 0;
        }
        .product-section h5 {
            color: #e65100;
            margin-top: 0;
            margin-bottom: 15px;
        }
        .badge-qty {
            background: #17a2b8;
            color: #fff;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 13px;
        }
        .grand-summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 20px;
            border-radius: 8px;
        }
        .grand-summary h4 {
            margin-top: 0;
        }
        @media print {
            .no-print { display: none !important; }
            .report-card { box-shadow: none; border: 1px solid #ddd; }
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
                <div class="page-bar no-print">
                    <ul class="page-breadcrumb">
                        <li><a href="index.php">Home</a><i class="fa fa-circle"></i></li>
                        <li><a href="manage-product.php">Products</a><i class="fa fa-circle"></i></li>
                        <li><span>Raw Materials Report</span></li>
                    </ul>
                </div>

                <h3 class="page-title">Raw Materials Report <small>Production Planning</small></h3>

                <!-- Production Input Form -->
                <div class="report-card no-print">
                    <div class="report-header">
                        <h4><i class="fa fa-list-alt"></i> Enter Production Requirements</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" id="productionForm">
                            <div id="productionRows">
                                <div class="production-row" data-row="0">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label>Finished Product</label>
                                            <select name="product_ids[]" class="form-control product-select" required>
                                                <option value="">-- Select Product --</option>
                                                <?php foreach ($productsWithRecipe as $p): ?>
                                                    <option value="<?php echo $p['item_id']; ?>">
                                                        <?php echo htmlspecialchars($p['item_name']); ?>
                                                        (<?php echo $p['ingredient_count']; ?> ingredients)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label>Production Quantity</label>
                                            <input type="number" name="quantities[]" class="form-control" min="1" step="1" value="1" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label>&nbsp;</label>
                                            <button type="button" class="btn btn-remove-row form-control" onclick="removeRow(this)" style="display:none;">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row" style="margin-top:15px;">
                                <div class="col-md-6">
                                    <button type="button" class="btn btn-add-row" onclick="addRow()">
                                        <i class="fa fa-plus"></i> Add Another Product
                                    </button>
                                </div>
                                <div class="col-md-6 text-right">
                                    <button type="submit" name="generate_report" class="btn btn-primary btn-lg">
                                        <i class="fa fa-calculator"></i> Generate Report
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if (!empty($reportData)): ?>
                <!-- Print Button -->
                <div class="row no-print" style="margin-bottom:20px;">
                    <div class="col-md-12 text-right">
                        <button onclick="window.print()" class="btn btn-default">
                            <i class="fa fa-print"></i> Print Report
                        </button>
                    </div>
                </div>

                <!-- Grand Summary - Total Raw Materials -->
                <div class="report-card">
                    <div class="report-header">
                        <h4><i class="fa fa-cube"></i> Total Raw Materials Required</h4>
                    </div>
                    <div class="card-body">
                        <table class="summary-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Raw Material</th>
                                    <th>Code</th>
                                    <th style="text-align:right">Total Required (g)</th>
                                    <th style="text-align:right">Total Required (kg)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $counter = 1;
                                $grandTotal = 0;
                                foreach ($grandTotalByIngredient as $ing): 
                                    $grandTotal += $ing['total_required'];
                                ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($ing['item_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($ing['item_code'] ?? '-'); ?></td>
                                    <td style="text-align:right"><?php echo number_format($ing['total_required'], 2); ?> g</td>
                                    <td style="text-align:right"><?php echo number_format($ing['total_required'] / 1000, 4); ?> kg</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="grand-total-row">
                                    <td colspan="3"><strong>GRAND TOTAL</strong></td>
                                    <td style="text-align:right"><strong><?php echo number_format($grandTotal, 2); ?> g</strong></td>
                                    <td style="text-align:right"><strong><?php echo number_format($grandTotal / 1000, 4); ?> kg</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Detailed Breakdown by Product -->
                <div class="report-card">
                    <div class="report-header">
                        <h4><i class="fa fa-list"></i> Detailed Breakdown by Product</h4>
                    </div>
                    <div class="card-body">
                        <?php foreach ($reportData as $product): ?>
                        <div class="product-section">
                            <h5>
                                <i class="fa fa-cube"></i> <?php echo htmlspecialchars($product['product_name']); ?>
                                <span class="badge-qty"><?php echo number_format($product['quantity']); ?> units</span>
                            </h5>
                            <table class="summary-table">
                                <thead>
                                    <tr>
                                        <th>Ingredient</th>
                                        <th>Process</th>
                                        <th style="text-align:right">Per Unit (g)</th>
                                        <th style="text-align:right">Required (g)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($product['ingredients'] as $ing): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($ing['item_name']); ?></td>
                                        <td>Step <?php echo $ing['process_step']; ?></td>
                                        <td style="text-align:right"><?php echo number_format($ing['per_unit'], 4); ?></td>
                                        <td style="text-align:right"><?php echo number_format($ing['required'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="grand-total-row">
                                        <td colspan="3"><strong>Subtotal for <?php echo htmlspecialchars($product['product_name']); ?></strong></td>
                                        <td style="text-align:right"><strong><?php echo number_format($product['total_raw'], 2); ?> g</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                <div class="alert alert-warning">
                    <i class="fa fa-exclamation-triangle"></i> No valid production entries found. Please select at least one product with a quantity.
                </div>
                <?php endif; ?>

                <?php if (empty($productsWithRecipe)): ?>
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> No products with recipes found. 
                    <a href="product-ingredients.php">Add ingredients to your products</a> first.
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <?php include('common/footer.php'); ?>
    <script src="assets/global/plugins/jquery.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/select2/js/select2.full.min.js" type="text/javascript"></script>
    <script src="assets/global/scripts/app.min.js" type="text/javascript"></script>
    <script src="assets/layouts/layout/scripts/layout.min.js" type="text/javascript"></script>

    <script>
    var rowCount = 1;

    function addRow() {
        var template = `
        <div class="production-row" data-row="${rowCount}">
            <div class="row">
                <div class="col-md-6">
                    <label>Finished Product</label>
                    <select name="product_ids[]" class="form-control product-select" required>
                        <option value="">-- Select Product --</option>
                        <?php foreach ($productsWithRecipe as $p): ?>
                        <option value="<?php echo $p['item_id']; ?>"><?php echo htmlspecialchars($p['item_name']); ?> (<?php echo $p['ingredient_count']; ?> ingredients)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Production Quantity</label>
                    <input type="number" name="quantities[]" class="form-control" min="1" step="1" value="1" required>
                </div>
                <div class="col-md-2">
                    <label>&nbsp;</label>
                    <button type="button" class="btn btn-remove-row form-control" onclick="removeRow(this)">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
        `;
        $('#productionRows').append(template);
        rowCount++;
        updateRemoveButtons();
    }

    function removeRow(btn) {
        $(btn).closest('.production-row').remove();
        updateRemoveButtons();
    }

    function updateRemoveButtons() {
        var rows = $('.production-row');
        if (rows.length > 1) {
            rows.find('.btn-remove-row').show();
        } else {
            rows.find('.btn-remove-row').hide();
        }
    }

    $(document).ready(function() {
        updateRemoveButtons();
    });
    </script>
</body>
</html>
