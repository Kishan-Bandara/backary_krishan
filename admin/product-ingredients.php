<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');

$db = new Database();

// Get finished products (non-raw materials)
$finishedProducts = $db->getRows('SELECT item_id, item_name, item_code FROM item_master WHERE is_raw_material = 0 ORDER BY item_name ASC');

// Get raw materials
$rawMaterials = $db->getRows('SELECT item_id, item_name, item_code FROM item_master WHERE is_raw_material = 1 ORDER BY item_name ASC');

// Get selected product if any
$selectedProductId = isset($_GET['pid']) ? (int)$_GET['pid'] : 0;
$selectedProduct = null;
$ingredients = [];

if ($selectedProductId > 0) {
    // include item_weight so we can use product-specific sample weight when available
    $selectedProduct = $db->getRow('SELECT item_id, item_name, item_code, item_weight FROM item_master WHERE item_id = ? AND is_raw_material = 0', [$selectedProductId]);
    if ($selectedProduct) {
        $ingredients = $db->getRows(
            'SELECT pi.*, im.item_name, im.item_code 
             FROM product_ingredients pi 
             LEFT JOIN item_master im ON im.item_id = pi.ingredient_id 
             WHERE pi.product_id = ? 
             ORDER BY pi.process_step ASC, pi.id ASC',
            [$selectedProductId]
        );
    }
}

// Default sample weight (g) used to calculate scaled quantities in the Ingredients List
$sampleWeight = 180;
if ($selectedProduct && !empty($selectedProduct['item_weight']) && $selectedProduct['item_weight'] > 0) {
    $sampleWeight = (float)$selectedProduct['item_weight'];
}
// Show sample column only when sample weight is greater than 0
$showSampleColumn = ($sampleWeight > 0);

// Calculate totals
$totalQty = 0;
foreach ($ingredients as $ing) {
    $totalQty += $ing['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Product Ingredients | Recipe Management</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
    <link href="assets/global/plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/global/plugins/select2/css/select2-bootstrap.min.css" rel="stylesheet" type="text/css" />
    <style>
        .recipe-header {
            background: linear-gradient(90deg, #028d7aff 0%, #066c74ff 100%);
            color: #fff;
            padding: 15px 20px;
            border-radius: 8px 8px 0 0;
            margin-bottom: 0;
        }
        .recipe-header h4 {
            margin: 0;
            font-weight: 600;
        }
        .recipe-card {
            background: #fff;
            border: 1px solid #dde3ec;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        .recipe-card .card-body {
            padding: 20px;
        }
        .ingredients-table {
            width: 100%;
            border-collapse: collapse;
        }
        .ingredients-table th {
            background: #f4f6fb;
            padding: 12px;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.05em;
            color: #5d6d8a;
            border-bottom: 2px solid #e1e7f0;
        }
        .ingredients-table td {
            padding: 12px;
            border-bottom: 1px solid #eef2f7;
            vertical-align: middle;
        }
        .ingredients-table tr:hover {
            background: #f9fbfd;
        }
        .process-row {
            background: #e8f5e9 !important;
            font-weight: 600;
        }
        .process-row td {
            color: #2e7d32;
            border-bottom: 2px solid #c8e6c9;
        }
        .total-row {
            background: #fff3e0;
            font-weight: bold;
        }
        .total-row td {
            border-top: 2px solid #ffb74d;
            color: #e65100;
        }
        .btn-add-ingredient {
            background: #028d7a;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-add-ingredient:hover {
            background: #026d5a;
            color: #fff;
        }
        .btn-delete {
            background: #dc3545;
            color: #fff;
            border: none;
            padding: 4px 10px;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-delete:hover {
            background: #c82333;
        }
        .percentage-badge {
            background: #17a2b8;
            color: #fff;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        .add-form-row {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
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
                <div class="page-bar">
                    <ul class="page-breadcrumb">
                        <li><a href="index.php">Home</a><i class="fa fa-circle"></i></li>
                        <li><a href="manage-product.php">Products</a><i class="fa fa-circle"></i></li>
                        <li><span>Product Ingredients</span></li>
                    </ul>
                </div>

                <h3 class="page-title">Product Ingredients <small>Recipe / Bill of Materials</small></h3>

                <!-- Product Selection -->
                <div class="recipe-card">
                    <div class="recipe-header">
                        <h4><i class="fa fa-search"></i> Select Finished Product</h4>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="">
                            <div class="row">
                                <div class="col-md-8">
                                    <select name="pid" id="product_select" class="form-control select2" required>
                                        <option value="">-- Select a Finished Product --</option>
                                        <?php foreach ($finishedProducts as $fp): ?>
                                            <option value="<?php echo $fp['item_id']; ?>" <?php echo ($selectedProductId == $fp['item_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($fp['item_name']); ?> 
                                                <?php if ($fp['item_code']): ?>(<?php echo htmlspecialchars($fp['item_code']); ?>)<?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-primary"><i class="fa fa-arrow-right"></i> Load Recipe</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($selectedProduct): ?>
                <!-- Add Ingredient Form -->
                <div class="recipe-card">
                    <div class="recipe-header">
                        <h4><i class="fa fa-plus"></i> Add Ingredient to: <?php echo htmlspecialchars($selectedProduct['item_name']); ?></h4>
                    </div>
                    <div class="card-body">
                        <form id="addIngredientForm" class="add-form-row" method="POST" action="process/product-ingredients-process.php">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?php echo $selectedProductId; ?>">
                            <div class="row">
                                <div class="col-md-4">
                                    <label>Raw Material <span class="text-danger">*</span></label>
                                    <select name="ingredient_id" id="ingredient_select" class="form-control select2" required>
                                        <option value="">-- Select Raw Material --</option>
                                        <?php foreach ($rawMaterials as $rm): ?>
                                            <option value="<?php echo $rm['item_id']; ?>">
                                                <?php echo htmlspecialchars($rm['item_name']); ?>
                                                <?php if ($rm['item_code']): ?>(<?php echo htmlspecialchars($rm['item_code']); ?>)<?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label>Quantity (g) <span class="text-danger">*</span></label>
                                    <input type="number" name="quantity" id="quantity" class="form-control" step="0.0001" min="0" placeholder="0.0000" required>
                                </div>
                                <div class="col-md-2">
                                    <label>Process Step</label>
                                    <select name="process_step" id="process_step" class="form-control">
                                        <option value="1">Process 1</option>
                                        <option value="2">Process 2</option>
                                        <option value="3">Process 3</option>
                                        <option value="4">Process 4</option>
                                        <option value="5">Process 5</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label>Process Note</label>
                                    <input type="text" name="process_note" id="process_note" class="form-control" placeholder="e.g. Add to mixer">
                                </div>
                                <div class="col-md-1">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-add-ingredient form-control"><i class="fa fa-plus"></i></button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Ingredients List -->
                <div class="recipe-card">
                    <div class="recipe-header">
                        <h4><i class="fa fa-list"></i> Ingredients List - <?php echo htmlspecialchars($selectedProduct['item_name']); ?></h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($ingredients)): ?>
                            <div class="empty-state">
                                <i class="fa fa-leaf"></i>
                                <p>No ingredients added yet.<br>Use the form above to add raw materials to this recipe.</p>
                            </div>
                        <?php else: ?>
                            <table class="ingredients-table" id="ingredientsTable">
                                <thead>
                                    <tr>
                                        <th style="width:40px">#</th>
                                        <th>Ingredient</th>
                                        <th style="width:120px">Qty (1 unit)</th>
                                        <?php if ($showSampleColumn): ?>
                                            <th id="sampleHeader" style="width:120px">Qty (<?php echo (int)$sampleWeight; ?>g)</th>
                                        <?php endif; ?>
                                        <th style="width:80px">%</th>
                                        <th style="width:100px">Process</th>
                                        <th>Process Note</th>
                                        <th style="width:80px">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $currentProcess = 0;
                                    $counter = 1;
                                    foreach ($ingredients as $ing): 
                                        // Show process header if process changes
                                        if ($ing['process_step'] != $currentProcess):
                                            $currentProcess = $ing['process_step'];
                                    ?>
                                        <tr class="process-row">
                                            <td colspan="<?php echo ($showSampleColumn ? 8 : 7); ?>">
                                                <i class="fa fa-cogs"></i> Process <?php echo $currentProcess; ?>
                                                <?php if (!empty($ing['process_note'])): ?>
                                                    - <?php echo htmlspecialchars($ing['process_note']); ?>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                    <tr data-id="<?php echo $ing['id']; ?>">
                                        <td><?php echo $counter++; ?></td>
                                        <td><?php echo htmlspecialchars($ing['item_name']); ?></td>
                                        <td><?php echo number_format($ing['quantity'], 4); ?> g</td>
                                        <?php if ($showSampleColumn): ?>
                                        <td class="sample-qty"><?php echo number_format($ing['quantity'] * $sampleWeight, 0); ?> g</td>
                                        <?php endif; ?>
                                        <td>
                                            <span class="percentage-badge">
                                                <?php echo ($totalQty > 0) ? number_format(($ing['quantity'] / $totalQty) * 100, 1) : 0; ?>%
                                            </span>
                                        </td>
                                        <td>Step <?php echo $ing['process_step']; ?></td>
                                        <td><?php echo htmlspecialchars($ing['process_note'] ?? '-'); ?></td>
                                        <td>
                                            <button class="btn-delete btn-delete-ingredient" data-id="<?php echo $ing['id']; ?>">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="total-row">
                                        <td colspan="2"><strong>TOTAL</strong></td>
                                        <td><strong><?php echo number_format($totalQty, 4); ?> g</strong></td>
                                        <?php if ($showSampleColumn): ?>
                                            <td><strong class="sample-total"><?php echo number_format($totalQty * $sampleWeight, 0); ?> g</strong></td>
                                        <?php endif; ?>
                                        <td><span class="percentage-badge">100%</span></td>
                                        <td colspan="3"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Raw Materials Calculator -->
                <div class="recipe-card">
                    <div class="recipe-header">
                        <h4><i class="fa fa-calculator"></i> Raw Materials Calculator</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2">
                                <label>Production Quantity</label>
                                <input type="number" id="production_qty" class="form-control" value="1" min="1" step="1">
                            </div>
                            <div class="col-md-2">
                                <label>Sample Size (g)</label>
                                <input type="number" id="sample_weight" class="form-control" value="<?php echo htmlspecialchars($sampleWeight); ?>" min="1" step="1">
                            </div>
                            <div class="col-md-2">
                                <label>&nbsp;</label>
                                <button type="button" id="calculateBtn" class="btn btn-primary form-control">
                                    <i class="fa fa-refresh"></i> Calculate
                                </button>
                            </div>
                            <div class="col-md-6">
                                <label>Total Raw Materials Needed</label>
                                <div id="calculatedResult" class="well" style="padding:10px; background:#f9f9f9; border-radius:4px;">
                                    <strong>Total: <span id="totalNeeded"><?php echo number_format($totalQty, 2); ?></span> g</strong>
                                    <span class="text-muted"> for 1 unit(s)</span>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($ingredients)): ?>
                        <hr>
                        <h5>Detailed Breakdown:</h5>
                        <table class="table table-striped" id="calculationTable">
                            <thead>
                                <tr>
                                    <th>Ingredient</th>
                                    <th>Per Unit (g)</th>
                                    <th>Required Qty (g)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ingredients as $ing): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($ing['item_name']); ?></td>
                                    <td class="per-unit"><?php echo number_format($ing['quantity'], 4); ?></td>
                                    <td class="required-qty"><?php echo number_format($ing['quantity'], 4); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>
                </div>

                <?php else: ?>
                <!-- No Product Selected -->
                <div class="recipe-card">
                    <div class="card-body">
                        <div class="empty-state">
                            <i class="fa fa-cubes"></i>
                            <h4>Select a Product</h4>
                            <p>Choose a finished product from the dropdown above to manage its ingredients/recipe.</p>
                        </div>
                    </div>
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
    $(document).ready(function() {
        // Initialize Select2
        $('.select2').select2({
            placeholder: "Select an option",
            allowClear: true
        });

        // Add Ingredient Form Submit
        $('#addIngredientForm').on('submit', function(e) {
            e.preventDefault();
            
            var formData = {
                action: 'add',
                product_id: $('input[name="product_id"]').val(),
                ingredient_id: $('#ingredient_select').val(),
                quantity: $('#quantity').val(),
                process_step: $('#process_step').val(),
                process_note: $('#process_note').val()
            };

            if (!formData.ingredient_id || !formData.quantity) {
                alert('Please select an ingredient and enter quantity.');
                return;
            }

            $.ajax({
                url: 'process/product-ingredients-process.php',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response && response.success) {
                        location.reload();
                    } else {
                        alert((response && response.message) ? response.message : 'Error adding ingredient');
                    }
                },
                error: function(xhr, status, error) {
                    var msg = 'Error communicating with server';
                    if (xhr && xhr.responseText) {
                        msg += '\n' + xhr.responseText;
                    }
                    alert(msg);
                }
            });
        });

        // Delete Ingredient
        $('.btn-delete-ingredient').on('click', function() {
            if (!confirm('Are you sure you want to remove this ingredient?')) return;
            
            var id = $(this).data('id');
            var row = $(this).closest('tr');
            
            $.ajax({
                url: 'process/product-ingredients-process.php',
                method: 'POST',
                data: { action: 'delete', id: id },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.message || 'Error deleting ingredient');
                    }
                },
                error: function() {
                    alert('Error communicating with server');
                }
            });
        });

        // Calculator
        var showSampleColumn = <?php echo ($showSampleColumn ? 'true' : 'false'); ?>;

        function updateSampleColumns(sampleWeight) {
            if (!showSampleColumn) return;

            $('#ingredientsTable tbody tr').each(function() {
                var row = $(this);
                // skip process rows
                if (row.hasClass('process-row')) return;
                var perUnitText = row.find('td').eq(2).text().replace(/[^0-9.\-]/g, '');
                var perUnit = parseFloat(perUnitText) || 0;
                var sampleQty = perUnit * sampleWeight;
                row.find('.sample-qty').text(sampleQty.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0}) + ' g');
            });

            // update sample total
            var totalPerUnit = <?php echo $totalQty; ?>;
            var totalSample = totalPerUnit * sampleWeight;
            $('.sample-total').text(totalSample.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0}) + ' g');
        }

        $('#calculateBtn').on('click', function() {
            var qty = parseInt($('#production_qty').val()) || 1;
            var totalPerUnit = <?php echo $totalQty; ?>;
            var totalNeeded = totalPerUnit * qty;
            var sampleWeight = parseFloat($('#sample_weight').val()) || <?php echo $sampleWeight; ?>;

            $('#totalNeeded').text(totalNeeded.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $('#calculatedResult .text-muted').text(' for ' + qty + ' unit(s)');

            // Update table required qty
            $('#calculationTable tbody tr').each(function() {
                var perUnit = parseFloat($(this).find('.per-unit').text().replace(/,/g, ''));
                var required = perUnit * qty;
                $(this).find('.required-qty').text(required.toLocaleString('en-US', {minimumFractionDigits: 4, maximumFractionDigits: 4}));
            });

            // Update sample columns
            updateSampleColumns(sampleWeight);
        });

        // If sample weight changed, update sample columns immediately
        $('#sample_weight').on('change keyup', function() {
            var sampleWeight = parseFloat($(this).val()) || <?php echo $sampleWeight; ?>;
            if (showSampleColumn) {
                $('#sampleHeader').text('Qty (' + Math.round(sampleWeight) + 'g)');
                updateSampleColumns(sampleWeight);
            }
        });

        // initialize sample columns & header on page load
        if (showSampleColumn) {
            $('#sampleHeader').text('Qty (' + Math.round(<?php echo $sampleWeight; ?>) + 'g)');
            updateSampleColumns(<?php echo $sampleWeight; ?>);
        }
    });
    </script>
</body>
</html>
