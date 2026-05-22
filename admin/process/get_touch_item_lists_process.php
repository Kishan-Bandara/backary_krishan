<?php 
include('../include/database.php');
include('../include/check_login.php'); // Ensure session is checked/started
// ob_start() and session_start() are usually handled in check_login or before, but check_login does session_start() if not started? 
// The POS.php includes check_login. Here we are in AJAX.
// check_login.php usually redirects if not logged in. For AJAX this might redirect the response body, which is fine (handled as error or full page load in DIV).

// If check_login.php starts session:
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL ^ E_NOTICE);

$db = new Database();
$cat_id = isset($_POST['cat_id']) ? $_POST['cat_id'] : 0;
$vat_value = 0; 

// Query items
if($cat_id == 0 || $cat_id == "") {
    $query_items = $db->getRows('SELECT * FROM item_master ORDER BY item_name ASC');
} else {
    // Note column is item_category
    $query_items = $db->getRows('SELECT * FROM item_master WHERE item_category = ? ORDER BY item_name ASC', [$cat_id]);
}

if(!empty($query_items)) {
    foreach($query_items as $query) { 
        $item_id = $query['item_id']; 
        $master_item_name = $query['item_name'];
        $master_item_code = $query['item_code'];
        $master_item_vat = $query['item_vat'];
        $master_item_price = $query['item_normal_selling_price'];
        
        if($master_item_vat == "Y") {
            $vat_has = $vat_value."%";
        } else {
            $vat_has = "0.00%";
        }

        // Get QTY from FIFO
        $loc = isset($_SESSION['location']) ? $_SESSION['location'] : 1;
        $query_get_qty = $db->getRow('SELECT SUM(ft_blanace) as qty , ft_rate FROM fifo WHERE ft_item = ? AND ft_location = ? ',[$item_id, $loc]);
        
        $master_item_qty = $query_get_qty['qty'];
        if(empty($master_item_qty)) $master_item_qty = 0;
        
        $master_item_purchase_price = $query_get_qty['ft_rate'];

        if($master_item_qty <= 0){
            $button_display_class = "disabled";
        } else {
            $button_display_class = "";
        }
        
        // Output HTML
        // Note: We use double quotes for HTML attributes, so we use single quotes for PHP echo or escape.
?>
    <button type="button" class="product-card-btn btnAddItemFromList <?php echo $button_display_class; ?>" 
            data-item-code="<?php echo $master_item_code; ?>" 
            data-item-id="<?php echo $item_id; ?>" 
            data-item-name="<?php echo $master_item_name; ?>" 
            data-item-vat-name="<?php echo $vat_has; ?>" 
            data-item-vat="<?php echo $master_item_vat; ?>" 
            data-item-price="<?php echo $master_item_price; ?>"
            data-item-purchase-price="<?php echo $master_item_purchase_price; ?>"
    >
        <span class="prod-name"><?php echo $master_item_name; ?></span>
        <span class="prod-price">LKR <?php echo number_format($master_item_price, 2); ?></span>
        <span class="prod-qty">Qty: <?php echo $master_item_qty; ?></span>
    </button>
<?php 
    }
} else {
    echo '<div class="col-md-12"><p>No products found in this category.</p></div>';
}
?>